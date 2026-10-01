<?php
/**
 * Live Database Reset & Stock Zeroing Utility
 * Cleans all invoices/sales, zero-out stocks, and wipes adjustment logs.
 * Execution: CLI or Authenticated Admin via Web.
 */
require_once __DIR__ . '/config/config.php';

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    if (!isLoggedIn() || !hasRole('admin')) {
        // Allow execution if fresh install or with admin login
        if (file_exists(__DIR__ . '/config/install.lock')) {
            if (!isLoggedIn() || !hasRole('admin')) {
                http_response_code(403);
                die('<!DOCTYPE html><html><head><title>Access Denied</title><link rel="stylesheet" href="assets/css/style.css"></head><body style="padding:40px;text-align:center;"><h2>Access Denied</h2><p>You must be logged in as an Administrator to execute this maintenance utility.</p><a href="login.php" class="btn btn-primary">Login as Admin</a></body></html>');
            }
        }
    }
}

$db = Database::getInstance();
$driver = $db->getDriver();
$statusMsg = '';
$isSuccess = false;

if ($isCli || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset']))) {
    try {
        $db->beginTransaction();

        if ($driver === 'mysqli') {
            $db->query("SET FOREIGN_KEY_CHECKS = 0");
        }

        // 1. Wipe Sales & Invoices
        $db->execute("DELETE FROM `sale_items`");
        $db->execute("DELETE FROM `sales`");

        // 2. Wipe Adjustment & Inward Logs
        $db->execute("DELETE FROM `stock_adjustments`");
        $db->execute("DELETE FROM `stock_in_logs`");

        // 3. Set All Stock Quantities to 0
        $db->execute("UPDATE `product_batches` SET `current_quantity` = 0, `initial_quantity` = 0, `status` = 'sold_out'");

        // 4. Reset Auto-Increment sequences
        if ($driver === 'sqlite') {
            $db->execute("DELETE FROM `sqlite_sequence` WHERE `name` IN ('sales', 'sale_items', 'stock_adjustments', 'stock_in_logs')");
        } else {
            $db->query("ALTER TABLE `sale_items` AUTO_INCREMENT = 1");
            $db->query("ALTER TABLE `sales` AUTO_INCREMENT = 1");
            $db->query("ALTER TABLE `stock_adjustments` AUTO_INCREMENT = 1");
            $db->query("ALTER TABLE `stock_in_logs` AUTO_INCREMENT = 1");
            $db->query("SET FOREIGN_KEY_CHECKS = 1");
        }

        $db->commit();
        $isSuccess = true;
        $statusMsg = "Live database reset completed successfully! All stocks set to 0 and all invoices wiped.";
    } catch (Exception $e) {
        $db->rollback();
        $isSuccess = false;
        $statusMsg = "Reset failed: " . $e->getMessage();
    }
}

// Fetch Current Stats
$totalStock = $db->fetchOne("SELECT SUM(current_quantity) as total_qty, COUNT(*) as batch_count FROM product_batches");
$salesCount = $db->fetchOne("SELECT COUNT(*) as c FROM sales");
$itemsCount = $db->fetchOne("SELECT COUNT(*) as c FROM sale_items");
$productsCount = $db->fetchOne("SELECT COUNT(*) as c FROM products");

if ($isCli) {
    echo "=========================================================\n";
    echo "=== LIVE DATABASE RESET & STOCK ZEROING UTILITY ===\n";
    echo "=========================================================\n";
    echo "Driver: " . $driver . "\n";
    echo "Result: " . ($isSuccess ? "SUCCESS" : ($statusMsg ?: "Checked")) . "\n\n";
    echo "• Active Stock Units: " . ($totalStock['total_qty'] ?? 0) . "\n";
    echo "• Batches Zeroed: " . ($totalStock['batch_count'] ?? 0) . "\n";
    echo "• Invoices Remaining: " . ($salesCount['c'] ?? 0) . "\n";
    echo "• Products in Catalog: " . ($productsCount['c'] ?? 0) . "\n";
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Database Reset & Stock Zeroing | <?= defined('STORE_NAME') ? STORE_NAME : 'Inventory System' ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background: var(--bg-main, #f8fafc); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div style="max-width: 600px; width: 100%; background: #ffffff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); padding: 32px; border: 1px solid var(--border-color, #e2e8f0);">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 60px; height: 60px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px;">
                <i class="fa-solid fa-server"></i>
            </div>
            <h2 style="font-size: 22px; margin: 0 0 6px 0; color: #0f172a;">Live Database Maintenance</h2>
            <p style="color: #64748b; font-size: 14px; margin: 0;">Zero all product stocks and wipe test invoices for live rollout</p>
        </div>

        <?php if ($statusMsg): ?>
            <div style="padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; <?= $isSuccess ? 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;' ?>">
                <i class="fa-solid <?= $isSuccess ? 'fa-check-circle' : 'fa-triangle-exclamation' ?>"></i>
                <?= htmlspecialchars($statusMsg) ?>
            </div>
        <?php endif; ?>

        <div style="background: #f1f5f9; border-radius: 8px; padding: 18px; margin-bottom: 24px;">
            <h4 style="margin: 0 0 12px 0; font-size: 14px; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">Current Live Database State</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 14px;">
                <div><strong>Database Engine:</strong> <?= htmlspecialchars(strtoupper($driver)) ?></div>
                <div><strong>Total Products:</strong> <?= (int)($productsCount['c'] ?? 0) ?> items</div>
                <div><strong>Active Stock Units:</strong> <span style="font-weight: 700; color: <?= ((int)($totalStock['total_qty'] ?? 0) === 0) ? '#16a34a' : '#d97706' ?>;"><?= (int)($totalStock['total_qty'] ?? 0) ?></span></div>
                <div><strong>Invoices on Record:</strong> <span style="font-weight: 700; color: <?= ((int)($salesCount['c'] ?? 0) === 0) ? '#16a34a' : '#ef4444' ?>;"><?= (int)($salesCount['c'] ?? 0) ?></span></div>
            </div>
        </div>

        <?php if ((int)($totalStock['total_qty'] ?? 0) === 0 && (int)($salesCount['c'] ?? 0) === 0): ?>
            <div style="text-align: center; padding: 10px 0;">
                <p style="color: #16a34a; font-weight: 600; margin-bottom: 16px;">
                    <i class="fa-solid fa-circle-check"></i> Database is clean, all stocks are 0, and ready for fresh stock inward!
                </p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <a href="modules/batches/restock.php" class="btn btn-primary" style="padding: 10px 20px; text-decoration: none;">
                        <i class="fa-solid fa-truck-ramp-box"></i> Upload Fresh Stock
                    </a>
                    <a href="modules/dashboard/index.php" class="btn btn-secondary" style="padding: 10px 20px; text-decoration: none;">
                        <i class="fa-solid fa-gauge"></i> Go to Dashboard
                    </a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" onsubmit="return confirm('Are you sure you want to zero all stock quantities and wipe all existing invoices on the live server?');">
                <input type="hidden" name="confirm_reset" value="1">
                <div style="text-align: center;">
                    <button type="submit" class="btn btn-danger" style="background: #dc2626; color: #fff; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%;">
                        <i class="fa-solid fa-rotate-right"></i> Execute Reset on Live Server
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
