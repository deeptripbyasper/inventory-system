<?php
/**
 * Sidebar Navigation Component
 */
if (!defined('APP_INIT')) {
    require_once __DIR__ . '/../config/config.php';
}

$currentUri = $_SERVER['REQUEST_URI'];
$db = Database::getInstance();

// Calculate quick alert counts for sidebar badges
$sidebarExpiredCount = 0;
$sidebarLowStockCount = 0;

if ($db->isConnected()) {
    // Count batches that are expired or expiring within critical days
    $critDays = defined('EXPIRY_CRITICAL_DAYS') ? EXPIRY_CRITICAL_DAYS : 30;
    $expRes = $db->fetchOne("SELECT COUNT(*) as cnt FROM `product_batches` WHERE `current_quantity` > 0 AND `expiry_date` <= DATE_ADD(CURDATE(), INTERVAL ? DAY)", "i", [$critDays]);
    $sidebarExpiredCount = $expRes['cnt'] ?? 0;

    // Count products where total leftover stock <= min_stock_alert
    $lowRes = $db->fetchOne("
        SELECT COUNT(*) as cnt FROM (
            SELECT p.id, p.min_stock_alert, COALESCE(SUM(b.current_quantity), 0) as total_stock
            FROM products p
            LEFT JOIN product_batches b ON p.id = b.product_id
            WHERE p.status = 'active'
            GROUP BY p.id, p.min_stock_alert
            HAVING total_stock <= p.min_stock_alert
        ) as low_table
    ");
    $sidebarLowStockCount = $lowRes['cnt'] ?? 0;
}

function isNavActive($path) {
    global $currentUri;
    return strpos($currentUri, $path) !== false ? 'active' : '';
}
?>
<aside class="app-sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL ?>/modules/dashboard/index.php" class="brand-logo">
            <div class="brand-icon" style="background: linear-gradient(135deg, #3b82f6, #ec4899);">
                <i class="fa-solid fa-store"></i>
            </div>
            <span>Bondhu <span style="color: var(--primary);">Chol</span></span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Billing & Counter</div>
        
        <a href="<?= BASE_URL ?>/modules/pos/index.php" class="nav-item <?= isNavActive('/modules/pos/index.php') ?>">
            <i class="fa-solid fa-cash-register"></i>
            <span>Billing Terminal (POS)</span>
        </a>

        <a href="<?= BASE_URL ?>/modules/pos/invoices.php" class="nav-item <?= isNavActive('/modules/pos/invoices.php') ?>">
            <i class="fa-solid fa-receipt"></i>
            <span>Bills & Invoices</span>
        </a>

        <div class="nav-section-title">Core Operations</div>
        
        <a href="<?= BASE_URL ?>/modules/dashboard/index.php" class="nav-item <?= isNavActive('/modules/dashboard') ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        <div class="nav-section-title">Stock & Inventory</div>

        <a href="<?= BASE_URL ?>/modules/products/index.php" class="nav-item <?= isNavActive('/modules/products') ?>">
            <i class="fa-solid fa-box-open"></i>
            <span>Product Catalog</span>
        </a>

        <a href="<?= BASE_URL ?>/modules/batches/index.php" class="nav-item <?= isNavActive('/modules/batches') ?>">
            <i class="fa-solid fa-layer-group"></i>
            <span>Batches & Restock</span>
        </a>

        <div class="nav-section-title">Intelligence & Reports</div>

        <a href="<?= BASE_URL ?>/modules/reports/daily_sales.php" class="nav-item <?= isNavActive('daily_sales.php') ?>">
            <i class="fa-solid fa-calendar-day"></i>
            <span>Everyday Sales</span>
        </a>

        <a href="<?= BASE_URL ?>/modules/reports/monthly_sales.php" class="nav-item <?= isNavActive('monthly_sales.php') ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Monthly Sales</span>
        </a>

        <a href="<?= BASE_URL ?>/modules/reports/leftover_stock.php" class="nav-item <?= isNavActive('leftover_stock.php') ?>">
            <i class="fa-solid fa-warehouse"></i>
            <span>Leftover Stock</span>
            <?php if ($sidebarLowStockCount > 0): ?>
                <span class="nav-badge badge-warning"><?= $sidebarLowStockCount ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= BASE_URL ?>/modules/reports/expiry_report.php" class="nav-item <?= isNavActive('expiry_report.php') ?>">
            <i class="fa-solid fa-calendar-xmark"></i>
            <span>Expiry Date Watch</span>
            <?php if ($sidebarExpiredCount > 0): ?>
                <span class="nav-badge badge-danger"><?= $sidebarExpiredCount ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-section-title">Administration</div>

        <a href="<?= BASE_URL ?>/modules/categories/index.php" class="nav-item <?= isNavActive('/modules/categories') ?>">
            <i class="fa-solid fa-tags"></i>
            <span>Categories</span>
        </a>

        <a href="<?= BASE_URL ?>/modules/suppliers/index.php" class="nav-item <?= isNavActive('/modules/suppliers') ?>">
            <i class="fa-solid fa-truck-field"></i>
            <span>Suppliers</span>
        </a>

        <a href="<?= BASE_URL ?>/modules/settings/index.php" class="nav-item <?= isNavActive('/modules/settings') ?>">
            <i class="fa-solid fa-gear"></i>
            <span>System Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-avatar">
            <?= strtoupper(substr($currentUser['full_name'] ?? 'A', 0, 1)) ?>
        </div>
        <div class="user-info">
            <div class="user-name"><?= e($currentUser['full_name'] ?? 'User') ?></div>
            <div class="user-role"><?= e($currentUser['role'] ?? 'Cashier') ?></div>
        </div>
        <a href="<?= BASE_URL ?>/logout.php" title="Logout" style="color: var(--danger); font-size: 1.1rem; padding: 0.25rem;">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
    </div>
</aside>
