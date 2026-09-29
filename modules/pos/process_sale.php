<?php
/**
 * Process POS Checkout & Deduct Leftover Stock
 * Atomic Database Transaction (MySQL & SQLite compatible)
 */

require_once __DIR__ . '/../../config/config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/modules/pos/index.php");
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) {
    setFlash('danger', 'Security session expired. Please refresh and try again.');
    header("Location: " . BASE_URL . "/modules/pos/index.php");
    exit;
}

$cartJson = $_POST['cart_data'] ?? '[]';
if (is_array($cartJson)) {
    $cartItems = $cartJson;
} else {
    $cartItems = json_decode($cartJson, true);
}

if (empty($cartItems) || !is_array($cartItems)) {
    setFlash('danger', 'Cart is empty. Please add items before checking out.');
    header("Location: " . BASE_URL . "/modules/pos/index.php");
    exit;
}

$customerName = trim($_POST['customer_name'] ?? '');
if (empty($customerName)) {
    $customerName = 'Walk-in Customer';
}
$customerPhone = trim($_POST['customer_phone'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? 'cash';
$printAction = $_POST['print_action'] ?? 'thermal';

$discount = max(0, (float)($_POST['discount'] ?? 0.00));
$user = currentUser();

$db = Database::getInstance();
$db->beginTransaction();

try {
    $subtotal = 0.00;
    $validatedItems = [];

    // 1. Validate each cart item and lock/verify batch leftover quantity
    foreach ($cartItems as $item) {
        $productId = (int)($item['product_id'] ?? 0);
        $batchId = (int)($item['batch_id'] ?? 0);
        $qty = (int)($item['qty'] ?? 0);

        if ($productId <= 0 || $batchId <= 0 || $qty <= 0) {
            throw new Exception("Invalid cart item format.");
        }

        // Fetch fresh batch info from DB
        $batch = $db->fetchOne("SELECT * FROM `product_batches` WHERE `id` = ? AND `product_id` = ? FOR UPDATE", "ii", [$batchId, $productId]);
        if (!$batch) {
            throw new Exception("Batch record not found for product ID {$productId}.");
        }

        if ($batch['current_quantity'] < $qty) {
            throw new Exception("Insufficient leftover stock in Batch '{$batch['batch_no']}'. Available: {$batch['current_quantity']}, Requested: {$qty}.");
        }

        $unitPrice = (float)$batch['selling_price'];
        $unitCost = (float)$batch['purchase_price'];
        $itemSubtotal = $unitPrice * $qty;

        $subtotal += $itemSubtotal;

        $validatedItems[] = [
            'product_id' => $productId,
            'batch_id' => $batchId,
            'quantity' => $qty,
            'unit_cost' => $unitCost,
            'unit_price' => $unitPrice,
            'subtotal' => $itemSubtotal,
            'current_stock' => $batch['current_quantity']
        ];
    }

    // 2. Financial Totals
    $taxable = max(0, $subtotal - $discount);
    $tax = ($taxable * TAX_RATE) / 100;
    $grandTotal = $taxable + $tax;

    // Cash Paid & Change Calculations
    if ($paymentMethod === 'cash') {
        $amountPaid = isset($_POST['amount_paid']) && is_numeric($_POST['amount_paid']) && (float)$_POST['amount_paid'] > 0
            ? (float)$_POST['amount_paid']
            : $grandTotal;
        $changeReturned = max(0.00, $amountPaid - $grandTotal);
    } else {
        $amountPaid = $grandTotal;
        $changeReturned = 0.00;
    }

    // Generate Invoice Number: INV-YYYYMMDD-XXXX
    $datePrefix = date('Ymd');
    $randomSuffix = strtoupper(bin2hex(random_bytes(2)));
    $invoiceNo = "INV-{$datePrefix}-{$randomSuffix}";
    $saleDate = date('Y-m-d H:i:s');
    $userId = isset($user['id']) ? (int)$user['id'] : null;

    // 3. Insert Master Sale (12 parameters matched to sisssdddddds)
    $insertSaleSql = "
        INSERT INTO `sales` (`invoice_no`, `user_id`, `customer_name`, `customer_phone`, `sale_date`, `subtotal`, `discount`, `tax`, `grand_total`, `amount_paid`, `change_returned`, `payment_method`, `status`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')
    ";
    $saleId = $db->insert($insertSaleSql, "sisssdddddds", [
        $invoiceNo, 
        $userId, 
        $customerName, 
        $customerPhone, 
        $saleDate,
        $subtotal, 
        $discount, 
        $tax, 
        $grandTotal, 
        $amountPaid, 
        $changeReturned, 
        $paymentMethod
    ]);

    if (!$saleId) {
        $dbErr = $db->getError();
        throw new Exception("Failed to record sales invoice" . (!empty($dbErr) ? ": {$dbErr}" : "."));
    }

    // 4. Insert Items and Deduct Leftover Stock
    foreach ($validatedItems as $vi) {
        // Insert sale item
        $insertItemSql = "
            INSERT INTO `sale_items` (`sale_id`, `product_id`, `batch_id`, `quantity`, `unit_cost_price`, `unit_price`, `subtotal`)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        $db->insert($insertItemSql, "iiiiddd", [
            $saleId, $vi['product_id'], $vi['batch_id'], $vi['quantity'], $vi['unit_cost'], $vi['unit_price'], $vi['subtotal']
        ]);

        // Deduct leftover quantity from batch
        $newQty = $vi['current_stock'] - $vi['quantity'];
        $newStatus = ($newQty <= 0) ? 'sold_out' : 'available';

        $updateBatchSql = "UPDATE `product_batches` SET `current_quantity` = ?, `status` = ? WHERE `id` = ?";
        $db->execute($updateBatchSql, "isi", [$newQty, $newStatus, $vi['batch_id']]);
    }

    // All successful, commit transaction!
    $db->commit();

    setFlash('success', "Sale completed successfully! Invoice #{$invoiceNo} generated.");
    
    // Check if request is AJAX
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
           || (isset($_POST['ajax']) && $_POST['ajax'] === '1');

    $thermalUrl = BASE_URL . "/modules/pos/receipt.php?id=" . $saleId . "&mode=thermal&autoprint=1";
    $standardUrl = BASE_URL . "/modules/pos/receipt.php?id=" . $saleId . "&mode=standard&autoprint=1";
    $viewUrl = BASE_URL . "/modules/pos/receipt.php?id=" . $saleId;

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'sale_id' => $saleId,
            'invoice_no' => $invoiceNo,
            'customer_name' => $customerName,
            'payment_method' => $paymentMethod,
            'grand_total' => $grandTotal,
            'grand_total_formatted' => formatCurrency($grandTotal),
            'amount_paid' => $amountPaid,
            'change_returned' => $changeReturned,
            'items_count' => count($validatedItems),
            'thermal_url' => $thermalUrl,
            'standard_url' => $standardUrl,
            'view_url' => $viewUrl,
            'print_action' => $printAction
        ]);
        exit;
    }

    // Standard redirect based on selected print action
    $redirectUrl = $viewUrl;
    if ($printAction === 'thermal') {
        $redirectUrl = $thermalUrl;
    } elseif ($printAction === 'standard') {
        $redirectUrl = $standardUrl;
    }

    header("Location: " . $redirectUrl);
    exit;

} catch (Exception $e) {
    $db->rollback();
    
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
           || (isset($_POST['ajax']) && $_POST['ajax'] === '1');

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }

    setFlash('danger', 'Checkout Failed: ' . $e->getMessage());
    header("Location: " . BASE_URL . "/modules/pos/index.php");
    exit;
}
