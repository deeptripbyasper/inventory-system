<?php
/**
 * Leftover Stock & Inventory Valuation Report
 */

$pageTitle = 'Leftover Stock Report';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$stockFilter = $_GET['stock_filter'] ?? 'all';

$categories = $db->fetchAll("SELECT * FROM `categories` ORDER BY `name` ASC");

// 1. Overall Leftover Inventory Aggregations
$overallStats = $db->fetchOne("
    SELECT 
        COUNT(DISTINCT p.id) as total_products,
        COUNT(b.id) as total_batches,
        COALESCE(SUM(b.initial_quantity), 0) as total_initial_units,
        COALESCE(SUM(b.initial_quantity - b.current_quantity), 0) as total_sold_units,
        COALESCE(SUM(b.current_quantity), 0) as total_leftover_units,
        COALESCE(SUM(b.current_quantity * b.purchase_price), 0) as total_cost_value,
        COALESCE(SUM(b.current_quantity * b.selling_price), 0) as total_retail_value
    FROM `products` p
    LEFT JOIN `product_batches` b ON p.id = b.product_id
    WHERE p.status = 'active'
");

$totalLeftoverUnits = (int)($overallStats['total_leftover_units'] ?? 0);
$totalCostValuation = (float)($overallStats['total_cost_value'] ?? 0);
$totalRetailValuation = (float)($overallStats['total_retail_value'] ?? 0);
$unrealizedProfit = $totalRetailValuation - $totalCostValuation;

// 2. Fetch Leftover Batches with Product Details
$sql = "
    SELECT 
        b.id as batch_id,
        b.batch_no,
        b.mfg_date,
        b.expiry_date,
        b.purchase_price,
        b.selling_price,
        b.initial_quantity,
        b.current_quantity as leftover_quantity,
        (b.initial_quantity - b.current_quantity) as sold_quantity,
        (b.current_quantity * b.purchase_price) as leftover_cost_value,
        (b.current_quantity * b.selling_price) as leftover_retail_value,
        p.id as product_id,
        p.name as product_name,
        p.sku,
        p.barcode,
        p.unit,
        p.min_stock_alert,
        c.name as category_name,
        s.name as supplier_name
    FROM `product_batches` b
    JOIN `products` p ON b.product_id = p.id
    LEFT JOIN `categories` c ON p.category_id = c.id
    LEFT JOIN `suppliers` s ON b.supplier_id = s.id
    WHERE p.status = 'active'
";

$params = [];
$types = "";

if ($categoryId > 0) {
    $sql .= " AND p.category_id = ?";
    $types .= "i";
    $params[] = $categoryId;
}

if ($stockFilter === 'low') {
    $sql .= " AND b.current_quantity > 0 AND b.current_quantity <= p.min_stock_alert";
} elseif ($stockFilter === 'out') {
    $sql .= " AND b.current_quantity = 0";
} elseif ($stockFilter === 'available') {
    $sql .= " AND b.current_quantity > 0";
}

$sql .= " ORDER BY p.name ASC, b.expiry_date ASC";

$leftoverRows = $db->fetchAll($sql, $types, $params);
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-warehouse" style="color: var(--primary);"></i>
            <span>Leftover Stock & Inventory Valuation</span>
        </h1>
        <p>Comprehensive lot-wise audit of remaining warehouse quantities, cost investment and unrealized profit</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-secondary" onclick="exportTableToCSV('leftoverTable', 'leftover_stock_report.csv')">
            <i class="fa-solid fa-file-csv"></i>
            <span>Export CSV</span>
        </button>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fa-solid fa-print"></i>
            <span>Print Report</span>
        </button>
    </div>
</div>

<!-- Leftover Stock KPI Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Total Leftover Quantity</div>
            <div class="stat-value"><?= number_format($totalLeftoverUnits) ?> <span style="font-size: 0.95rem; font-weight: 500;">units</span></div>
            <div class="stat-subtext">
                Sold to date: <?= number_format((int)$overallStats['total_sold_units']) ?> units
            </div>
        </div>
        <div class="stat-icon primary">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Inventory Cost Valuation</div>
            <div class="stat-value"><?= formatCurrency($totalCostValuation) ?></div>
            <div class="stat-subtext">
                Actual capital tied up in stock
            </div>
        </div>
        <div class="stat-icon info">
            <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Potential Retail Value</div>
            <div class="stat-value"><?= formatCurrency($totalRetailValuation) ?></div>
            <div class="stat-subtext">
                Estimated revenue upon sale
            </div>
        </div>
        <div class="stat-icon success">
            <i class="fa-solid fa-tags"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Unrealized Gross Margin</div>
            <div class="stat-value" style="color: var(--success);"><?= formatCurrency($unrealizedProfit) ?></div>
            <div class="stat-subtext">
                Expected future gross profit
            </div>
        </div>
        <div class="stat-icon warning">
            <i class="fa-solid fa-chart-pie"></i>
        </div>
    </div>
</div>

<!-- Filter & Search Bar -->
<div class="filter-bar">
    <div class="filter-group">
        <form action="<?= BASE_URL ?>/modules/reports/leftover_stock.php" method="GET" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
            <select name="category" class="form-select" style="width: auto; min-width: 180px;" onchange="this.form.submit()">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="stock_filter" class="form-select" style="width: auto; min-width: 160px;" onchange="this.form.submit()">
                <option value="all" <?= $stockFilter === 'all' ? 'selected' : '' ?>>All Batches</option>
                <option value="available" <?= $stockFilter === 'available' ? 'selected' : '' ?>>Leftover Stock > 0</option>
                <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low Leftover Stock</option>
                <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Completely Depleted (0)</option>
            </select>

            <?php if ($categoryId > 0 || $stockFilter !== 'all'): ?>
                <a href="<?= BASE_URL ?>/modules/reports/leftover_stock.php" class="btn btn-secondary btn-sm" title="Clear Filters">
                    <i class="fa-solid fa-xmark"></i> Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="search-input-wrapper">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" class="form-control" placeholder="Search product, SKU, batch..." data-table-search="leftoverTable">
    </div>
</div>

<!-- Leftover Stock Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table" id="leftoverTable">
            <thead>
                <tr>
                    <th>Product & SKU</th>
                    <th>Category</th>
                    <th>Batch No</th>
                    <th>Initial Qty</th>
                    <th>Sold Qty</th>
                    <th>Leftover Stock</th>
                    <th>Leftover %</th>
                    <th>Cost Value</th>
                    <th>Retail Value</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($leftoverRows)): ?>
                    <?php foreach ($leftoverRows as $r): 
                        $leftover = (int)$r['leftover_quantity'];
                        $initial = (int)$r['initial_quantity'];
                        $sold = (int)$r['sold_quantity'];
                        $minAlert = (int)$r['min_stock_alert'];
                        $pctRemaining = $initial > 0 ? ($leftover / $initial) * 100 : 0;
                        $expInfo = getExpiryStatus($r['expiry_date']);
                    ?>
                        <tr class="<?= $leftover <= $minAlert && $leftover > 0 ? 'highlight-critical' : ($expInfo['status'] === 'expired' && $leftover > 0 ? 'highlight-expired' : '') ?>">
                            <td>
                                <a href="<?= BASE_URL ?>/modules/products/view.php?id=<?= $r['product_id'] ?>" style="font-weight: 700;">
                                    <?= e($r['product_name']) ?>
                                </a><br>
                                <code><?= e($r['sku']) ?></code>
                            </td>
                            <td><?= e($r['category_name'] ?? 'General') ?></td>
                            <td>
                                <code><?= e($r['batch_no']) ?></code><br>
                                <small style="color: var(--text-muted);"><?= e($r['supplier_name'] ?? 'Direct') ?></small>
                            </td>
                            <td><?= $initial ?> <?= e($r['unit']) ?></td>
                            <td><?= $sold ?></td>
                            <td>
                                <strong style="font-size: 1.1rem; color: <?= $leftover <= $minAlert ? 'var(--danger)' : 'var(--text-primary)' ?>;">
                                    <?= $leftover ?>
                                </strong> <?= e($r['unit']) ?>
                            </td>
                            <td style="min-width: 120px;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="flex: 1; height: 6px; background: var(--border-color); border-radius: 4px; overflow: hidden;">
                                        <div style="width: <?= min(100, $pctRemaining) ?>%; height: 100%; background: <?= $pctRemaining <= 20 ? 'var(--danger)' : 'var(--primary)' ?>;"></div>
                                    </div>
                                    <small style="font-weight: 700; width: 35px;"><?= round($pctRemaining) ?>%</small>
                                </div>
                            </td>
                            <td><?= formatCurrency($r['leftover_cost_value']) ?></td>
                            <td><strong style="color: var(--primary);"><?= formatCurrency($r['leftover_retail_value']) ?></strong></td>
                            <td>
                                <span class="badge <?= $expInfo['badge_class'] ?>">
                                    <i class="fa-solid <?= $expInfo['icon'] ?>"></i>
                                    <?= formatDate($r['expiry_date']) ?>
                                </span>
                            </td>
                            <td>
                                <?= getStockStatusBadge($leftover, $minAlert) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            No leftover stock records matching the selected criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
