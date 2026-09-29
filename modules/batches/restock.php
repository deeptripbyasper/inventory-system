<?php
/**
 * Restock Inventory & Add New Batch
 */

$pageTitle = 'Restock Inventory';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$errors = [];

$preSelectedProductId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

$products = $db->fetchAll("SELECT `id`, `name`, `sku`, `unit`, `default_selling_price` FROM `products` WHERE `status` = 'active' ORDER BY `name` ASC");
$suppliers = $db->fetchAll("SELECT * FROM `suppliers` ORDER BY `name` ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Security session expired. Please refresh.';
    } else {
        $productId = (int)($_POST['product_id'] ?? 0);
        $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $batchNo = trim($_POST['batch_no'] ?? '');
        $mfgDate = !empty($_POST['mfg_date']) ? $_POST['mfg_date'] : null;
        $expiryDate = trim($_POST['expiry_date'] ?? '');
        $purchasePrice = (float)($_POST['purchase_price'] ?? 0.00);
        $sellingPrice = (float)($_POST['selling_price'] ?? 0.00);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $invoiceRef = trim($_POST['invoice_reference'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($productId <= 0) {
            $errors[] = 'Please select a product to restock.';
        }
        if (empty($batchNo)) {
            $errors[] = 'Batch number is required.';
        }
        if (empty($expiryDate)) {
            $errors[] = 'Expiry date is required for stock tracking.';
        }
        if ($quantity <= 0) {
            $errors[] = 'Quantity must be at least 1 unit.';
        }

        if (empty($errors)) {
            $db->beginTransaction();

            $insertBatchSql = "
                INSERT INTO `product_batches` (`product_id`, `supplier_id`, `batch_no`, `mfg_date`, `expiry_date`, `purchase_price`, `selling_price`, `initial_quantity`, `current_quantity`, `status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')
            ";
            $batchId = $db->insert($insertBatchSql, "iisssddii", [
                $productId, $supplierId, $batchNo, $mfgDate, $expiryDate, $purchasePrice, $sellingPrice, $quantity, $quantity
            ]);

            if ($batchId) {
                // Log to stock_in_logs
                $db->execute("
                    INSERT INTO `stock_in_logs` (`product_id`, `batch_id`, `supplier_id`, `user_id`, `quantity`, `purchase_price`, `selling_price`, `invoice_reference`, `notes`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", "iiiiddsss", [
                    $productId, $batchId, $supplierId, $currentUser['id'] ?? null, $quantity, $purchasePrice, $sellingPrice, $invoiceRef, $notes
                ]);

                $db->commit();
                setFlash('success', "Batch '{$batchNo}' with {$quantity} units stocked in successfully!");
                header("Location: " . BASE_URL . "/modules/products/view.php?id=" . $productId);
                exit;
            } else {
                $db->rollback();
                $errors[] = "Failed to save restock batch: " . $db->getError();
            }
        }
    }
}
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-truck-ramp-box" style="color: var(--primary);"></i>
            <span>Restock Inventory (New Batch)</span>
        </h1>
        <p>Receive new shipments, assign lot/batch numbers, record supplier costs and set expiration dates</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/modules/batches/index.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Batches</span>
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>/modules/batches/restock.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-boxes-packing" style="color: var(--primary);"></i>
                <span>Shipment & Batch Ingestion</span>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" for="product_id">Select Product <span style="color: var(--danger);">*</span></label>
                    <select id="product_id" name="product_id" class="form-select" required onchange="updateProductDefaults(this)">
                        <option value="">Select product to restock...</option>
                        <?php 
                        $selectedProd = $_POST['product_id'] ?? $preSelectedProductId;
                        foreach ($products as $p): 
                        ?>
                            <option value="<?= $p['id'] ?>" 
                                    data-price="<?= $p['default_selling_price'] ?>"
                                    data-unit="<?= e($p['unit']) ?>"
                                    <?= $selectedProd == $p['id'] ? 'selected' : '' ?>>
                                <?= e($p['name']) ?> (SKU: <?= e($p['sku']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="supplier_id">Supplier / Vendor</label>
                    <select id="supplier_id" name="supplier_id" class="form-select">
                        <option value="">Select Supplier...</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= $sup['id'] ?>" <?= (isset($_POST['supplier_id']) && $_POST['supplier_id'] == $sup['id']) ? 'selected' : '' ?>>
                                <?= e($sup['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="batch_no">Batch / Lot Number <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="batch_no" name="batch_no" class="form-control" placeholder="e.g. BAT-2026-09A" required value="<?= e($_POST['batch_no'] ?? 'BAT-' . date('Ymd-Hi')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="mfg_date">Manufacturing Date</label>
                    <input type="date" id="mfg_date" name="mfg_date" class="form-control" value="<?= e($_POST['mfg_date'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="expiry_date">Expiry Date <span style="color: var(--danger);">*</span></label>
                    <input type="date" id="expiry_date" name="expiry_date" class="form-control" required value="<?= e($_POST['expiry_date'] ?? date('Y-m-d', strtotime('+1 year'))) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="quantity">Quantity Received <span style="color: var(--danger);">*</span></label>
                    <input type="number" min="1" id="quantity" name="quantity" class="form-control" required value="<?= e($_POST['quantity'] ?? '100') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="purchase_price">Unit Purchase Cost (<?= e(CURRENCY_SYMBOL) ?>)</label>
                    <input type="number" step="0.01" min="0" id="purchase_price" name="purchase_price" class="form-control" placeholder="0.00" value="<?= e($_POST['purchase_price'] ?? '0.00') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="selling_price">Batch Selling Price (<?= e(CURRENCY_SYMBOL) ?>)</label>
                    <input type="number" step="0.01" min="0" id="selling_price" name="selling_price" class="form-control" placeholder="0.00" value="<?= e($_POST['selling_price'] ?? '0.00') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="invoice_reference">Supplier Invoice / PO Reference</label>
                    <input type="text" id="invoice_reference" name="invoice_reference" class="form-control" placeholder="e.g. INV-SUP-88921" value="<?= e($_POST['invoice_reference'] ?? '') ?>">
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" for="notes">Receiving Notes</label>
                    <input type="text" id="notes" name="notes" class="form-control" placeholder="Storage room, temperature, delivery agent..." value="<?= e($_POST['notes'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>/modules/batches/index.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-truck-ramp-box"></i>
            <span>Confirm & Receive Stock</span>
        </button>
    </div>
</form>

<script>
function updateProductDefaults(select) {
    const selectedOption = select.options[select.selectedIndex];
    const defaultPrice = selectedOption.getAttribute('data-price');
    if (defaultPrice) {
        const sellingPriceInput = document.getElementById('selling_price');
        if (sellingPriceInput && (!sellingPriceInput.value || sellingPriceInput.value === '0.00')) {
            sellingPriceInput.value = defaultPrice;
        }
    }
}
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
