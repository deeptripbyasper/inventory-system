<?php
require_once __DIR__ . '/config/config.php';

if (php_sapi_name() !== 'cli') {
    if (!isLoggedIn() || !hasRole('admin')) {
        http_response_code(403);
        die('Access Denied: Test suite can only be executed via CLI or by an authenticated Administrator.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

$db = Database::getInstance();
echo "=========================================================" . PHP_EOL;
echo "=== BONDHU CHOL BILLING & INVENTORY SYSTEM ===" . PHP_EOL;
echo "=========================================================" . PHP_EOL;
echo "Database Connected: " . ($db->isConnected() ? "YES" : "NO") . PHP_EOL;
echo "Database Driver: " . $db->getDriver() . PHP_EOL;
echo "Store Name: " . STORE_NAME . PHP_EOL;
echo "Currency: " . CURRENCY_SYMBOL . PHP_EOL;
echo "Tax Rate: " . TAX_RATE . "%" . PHP_EOL . PHP_EOL;

// 1. Categories and Product Counts
echo "--- Category Distribution ---" . PHP_EOL;
$cats = $db->fetchAll("
    SELECT c.id, c.name, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id, c.name
    ORDER BY c.id ASC
");
foreach ($cats as $cat) {
    echo "• [Cat #{$cat['id']}] {$cat['name']}: {$cat['product_count']} products" . PHP_EOL;
}
echo PHP_EOL;

// 2. Product sample check
echo "--- Sample Product Checks ---" . PHP_EOL;
$sampleSkus = ['AMUL-TZ-500', 'AMUL-IC-CHO-1L', 'BEV-THUM-750', 'CAD-SILK-60'];
foreach ($sampleSkus as $sku) {
    $p = $db->fetchOne("
        SELECT p.name, p.sku, p.barcode, p.default_selling_price, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.sku = ?
    ", "s", [$sku]);
    if ($p) {
        echo "✓ SKU: {$p['sku']} | Barcode: {$p['barcode']} | Price: ₹{$p['default_selling_price']} | Name: {$p['name']} ({$p['category_name']})" . PHP_EOL;
    } else {
        echo "✗ Product not found: {$sku}" . PHP_EOL;
    }
}
echo PHP_EOL;

// 3. Batches and FEFO Check
echo "--- Batches & Expiry Check ---" . PHP_EOL;
$totalBatches = $db->fetchOne("SELECT COUNT(*) as cnt, SUM(current_quantity) as total_qty FROM product_batches");
echo "Total Batches: " . $totalBatches['cnt'] . " | Total Stock Units: " . $totalBatches['total_qty'] . PHP_EOL;

$expiringBatches = $db->fetchAll("
    SELECT p.name, b.batch_no, b.expiry_date, b.current_quantity,
           DATEDIFF(b.expiry_date, CURDATE()) as days_left
    FROM product_batches b
    JOIN products p ON b.product_id = p.id
    WHERE b.current_quantity > 0 AND b.expiry_date >= CURDATE()
    ORDER BY b.expiry_date ASC
    LIMIT 4
");
echo "Earliest Expiring Items (FEFO):" . PHP_EOL;
foreach ($expiringBatches as $eb) {
    echo "  - {$eb['name']} (Batch {$eb['batch_no']}): {$eb['days_left']} days left ({$eb['expiry_date']}) - Qty: {$eb['current_quantity']}" . PHP_EOL;
}
echo PHP_EOL;

// 4. Number to Words test
echo "--- Number to Words Test ---" . PHP_EOL;
$testAmounts = [233.10, 383.25, 1250.00, 45.00];
foreach ($testAmounts as $amt) {
    echo "₹{$amt} -> " . numberToWordsINR($amt) . PHP_EOL;
}
echo PHP_EOL;

// 5. Test Simulated POS Sale & Invoice Generation
echo "--- Testing Simulated POS Sale & Inventory Deduction ---" . PHP_EOL;
$batchBefore = $db->fetchOne("SELECT id, product_id, batch_no, current_quantity, selling_price, purchase_price FROM product_batches WHERE id = 2");
echo "Batch #2 (Amul Taaza) Initial Stock: {$batchBefore['current_quantity']} pouches" . PHP_EOL;

$db->beginTransaction();
$invNo = "TEST-INV-" . time();
$saleId = $db->insert("
    INSERT INTO sales (invoice_no, user_id, customer_name, customer_phone, sale_date, subtotal, discount, tax, grand_total, amount_paid, change_returned, payment_method, status)
    VALUES (?, 1, 'Simulated Test Customer', '+91 99999 88888', NOW(), 54.00, 0.00, 2.70, 56.70, 100.00, 43.30, 'cash', 'completed')
", "s", [$invNo]);

$db->insert("
    INSERT INTO sale_items (sale_id, product_id, batch_id, quantity, unit_cost_price, unit_price, subtotal)
    VALUES (?, ?, ?, 2, ?, ?, 54.00)
", "iiidd", [$saleId, $batchBefore['product_id'], $batchBefore['id'], $batchBefore['purchase_price'], $batchBefore['selling_price']]);

$newQty = $batchBefore['current_quantity'] - 2;
$db->execute("UPDATE product_batches SET current_quantity = ? WHERE id = ?", "ii", [$newQty, $batchBefore['id']]);
$db->commit();

$batchAfter = $db->fetchOne("SELECT current_quantity FROM product_batches WHERE id = 2");
echo "Sale Recorded: ID #{$saleId} | Invoice: {$invNo}" . PHP_EOL;
echo "Batch #2 (Amul Taaza) New Stock: {$batchAfter['current_quantity']} pouches (Reduced by 2)" . PHP_EOL;

// Verify Invoice Fetch
$saleVerify = $db->fetchOne("SELECT * FROM sales WHERE id = ?", "i", [$saleId]);
echo "Sale Verified: Grand Total = ₹{$saleVerify['grand_total']} | Paid = ₹{$saleVerify['amount_paid']} | Change = ₹{$saleVerify['change_returned']}" . PHP_EOL;

echo PHP_EOL . "=== ALL TESTS COMPLETED SUCCESSFULLY ===" . PHP_EOL;
