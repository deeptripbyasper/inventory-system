<?php
/**
 * Expiry Date Tracking & Shelf-Life Intelligence Report
 */

$pageTitle = 'Expiry Date Tracking';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

$filter = $_GET['status'] ?? 'all';
$critDays = defined('EXPIRY_CRITICAL_DAYS') ? EXPIRY_CRITICAL_DAYS : 30;
$warnDays = defined('EXPIRY_WARNING_DAYS') ? EXPIRY_WARNING_DAYS : 60;
$noticeDays = 90;

// 1. Expiry KPI Metrics across active leftover batches
$kpiExpired = $db->fetchOne("
    SELECT 
        COUNT(id) as batch_count,
        COALESCE(SUM(current_quantity), 0) as units,
        COALESCE(SUM(current_quantity * purchase_price), 0) as cost_loss
    FROM `product_batches`
    WHERE `current_quantity` > 0 AND `expiry_date` < CURDATE()
");

$kpiCritical = $db->fetchOne("
    SELECT 
        COUNT(id) as batch_count,
        COALESCE(SUM(current_quantity), 0) as units,
        COALESCE(SUM(current_quantity * purchase_price), 0) as cost_at_risk
    FROM `product_batches`
    WHERE `current_quantity` > 0 AND `expiry_date` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
", "i", [$critDays]);

$kpiWarning = $db->fetchOne("
    SELECT 
        COUNT(id) as batch_count,
        COALESCE(SUM(current_quantity), 0) as units,
        COALESCE(SUM(current_quantity * purchase_price), 0) as cost_at_risk
    FROM `product_batches`
    WHERE `current_quantity` > 0 AND `expiry_date` BETWEEN DATE_ADD(CURDATE(), INTERVAL ? DAY) AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
", "ii", [$critDays, $warnDays]);

$kpiSafe = $db->fetchOne("
    SELECT 
        COUNT(id) as batch_count,
        COALESCE(SUM(current_quantity), 0) as units,
        COALESCE(SUM(current_quantity * purchase_price), 0) as cost_value
    FROM `product_batches`
    WHERE `current_quantity` > 0 AND `expiry_date` > DATE_ADD(CURDATE(), INTERVAL ? DAY)
", "i", [$warnDays]);

// 2. Query Batches for the table based on selected filter
$sql = "
    SELECT 
        b.*,
        p.id as product_id,
        p.name as product_name,
        p.sku,
        p.barcode,
        p.unit,
        c.name as category_name,
        s.name as supplier_name,
        (b.current_quantity * b.purchase_price) as total_batch_cost,
        (b.current_quantity * b.selling_price) as total_batch_retail
    FROM `product_batches` b
    JOIN `products` p ON b.product_id = p.id
    LEFT JOIN `categories` c ON p.category_id = c.id
    LEFT JOIN `suppliers` s ON b.supplier_id = s.id
    WHERE b.current_quantity > 0
";

if ($filter === 'expired') {
    $sql .= " AND b.expiry_date < CURDATE()";
} elseif ($filter === 'critical') {
    $sql .= " AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$critDays} DAY)";
} elseif ($filter === 'warning') {
    $sql .= " AND b.expiry_date BETWEEN DATE_ADD(CURDATE(), INTERVAL {$critDays} DAY) AND DATE_ADD(CURDATE(), INTERVAL {$warnDays} DAY)";
} elseif ($filter === 'notice') {
    $sql .= " AND b.expiry_date BETWEEN DATE_ADD(CURDATE(), INTERVAL {$warnDays} DAY) AND DATE_ADD(CURDATE(), INTERVAL {$noticeDays} DAY)";
} elseif ($filter === 'safe') {
    $sql .= " AND b.expiry_date > DATE_ADD(CURDATE(), INTERVAL {$noticeDays} DAY)";
}

$sql .= " ORDER BY b.expiry_date ASC";

$expiryBatches = $db->fetchAll($sql);
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-calendar-xmark" style="color: var(--danger);"></i>
            <span>Expiry Date Tracking & Shelf-Life Watch</span>
        </h1>
        <p>Proactively monitor upcoming product expiries, calculate capital at risk, and write-off expired batches</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-secondary" onclick="exportTableToCSV('expiryTable', 'expiry_tracking_report.csv')">
            <i class="fa-solid fa-file-csv"></i>
            <span>Export CSV</span>
        </button>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fa-solid fa-print"></i>
            <span>Print Report</span>
        </button>
    </div>
</div>

<!-- Expiry KPI Summary Cards -->
<div class="stats-grid">
    <!-- Expired (Red Alert) -->
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Expired Stock (Past Expiry)</div>
            <div class="stat-value" style="color: var(--danger);"><?= (int)$kpiExpired['units'] ?> <span style="font-size: 0.95rem; font-weight: 500;">units</span></div>
            <div class="stat-subtext">
                Loss Value: <strong><?= formatCurrency($kpiExpired['cost_loss']) ?></strong> (<?= (int)$kpiExpired['batch_count'] ?> batches)
            </div>
        </div>
        <div class="stat-icon danger">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
    </div>

    <!-- Critical Expiry (≤ 30 Days) -->
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Critical Expiry (≤ <?= $critDays ?> Days)</div>
            <div class="stat-value" style="color: var(--warning-critical);"><?= (int)$kpiCritical['units'] ?> <span style="font-size: 0.95rem; font-weight: 500;">units</span></div>
            <div class="stat-subtext">
                Capital at Risk: <strong><?= formatCurrency($kpiCritical['cost_at_risk']) ?></strong>
            </div>
        </div>
        <div class="stat-icon orange">
            <i class="fa-solid fa-clock"></i>
        </div>
    </div>

    <!-- Warning Expiry (31-60 Days) -->
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Warning (<?= $critDays+1 ?> - <?= $warnDays ?> Days)</div>
            <div class="stat-value" style="color: var(--warning);"><?= (int)$kpiWarning['units'] ?> <span style="font-size: 0.95rem; font-weight: 500;">units</span></div>
            <div class="stat-subtext">
                Stock Value: <strong><?= formatCurrency($kpiWarning['cost_at_risk']) ?></strong>
            </div>
        </div>
        <div class="stat-icon warning">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
    </div>

    <!-- Safe Stock (> 60 Days) -->
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Safe & Fresh Stock</div>
            <div class="stat-value" style="color: var(--success);"><?= (int)$kpiSafe['units'] ?> <span style="font-size: 0.95rem; font-weight: 500;">units</span></div>
            <div class="stat-subtext">
                Total Safe Value: <strong><?= formatCurrency($kpiSafe['cost_value']) ?></strong>
            </div>
        </div>
        <div class="stat-icon success">
            <i class="fa-solid fa-circle-check"></i>
        </div>
    </div>
</div>

<!-- Filter Tabs & Search -->
<div class="filter-bar">
    <div class="filter-group">
        <a href="<?= BASE_URL ?>/modules/reports/expiry_report.php?status=all" 
           class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">
            All Active Batches
        </a>
        <a href="<?= BASE_URL ?>/modules/reports/expiry_report.php?status=expired" 
           class="btn btn-sm <?= $filter === 'expired' ? 'btn-primary' : 'btn-secondary' ?>" style="color: var(--danger);">
            <i class="fa-solid fa-skull-crossbones"></i> Already Expired (<?= (int)$kpiExpired['batch_count'] ?>)
        </a>
        <a href="<?= BASE_URL ?>/modules/reports/expiry_report.php?status=critical" 
           class="btn btn-sm <?= $filter === 'critical' ? 'btn-primary' : 'btn-secondary' ?>" style="color: var(--warning-critical);">
            <i class="fa-solid fa-clock"></i> Critical ≤ <?= $critDays ?>d (<?= (int)$kpiCritical['batch_count'] ?>)
        </a>
        <a href="<?= BASE_URL ?>/modules/reports/expiry_report.php?status=warning" 
           class="btn btn-sm <?= $filter === 'warning' ? 'btn-primary' : 'btn-secondary' ?>" style="color: var(--warning);">
            <i class="fa-solid fa-hourglass-half"></i> Warning ≤ <?= $warnDays ?>d (<?= (int)$kpiWarning['batch_count'] ?>)
        </a>
        <a href="<?= BASE_URL ?>/modules/reports/expiry_report.php?status=safe" 
           class="btn btn-sm <?= $filter === 'safe' ? 'btn-primary' : 'btn-secondary' ?>" style="color: var(--success);">
            <i class="fa-solid fa-shield-check"></i> Safe (> <?= $warnDays ?>d)
        </a>
    </div>

    <div class="search-input-wrapper">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" class="form-control" placeholder="Search product, batch, SKU..." data-table-search="expiryTable">
    </div>
</div>

<!-- Expiry Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table" id="expiryTable">
            <thead>
                <tr>
                    <th>Product & SKU</th>
                    <th>Category</th>
                    <th>Batch No</th>
                    <th>Supplier</th>
                    <th>Leftover Stock</th>
                    <th>Cost Value</th>
                    <th>Retail Price</th>
                    <th>Mfg Date</th>
                    <th>Expiry Date</th>
                    <th>Timeline Status</th>
                    <th class="no-export" style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($expiryBatches)): ?>
                    <?php foreach ($expiryBatches as $b): 
                        $expInfo = getExpiryStatus($b['expiry_date']);
                        $isExpired = $expInfo['status'] === 'expired';
                    ?>
                        <tr class="<?= $isExpired ? 'highlight-expired' : ($expInfo['status'] === 'critical' ? 'highlight-critical' : '') ?>">
                            <td>
                                <a href="<?= BASE_URL ?>/modules/products/view.php?id=<?= $b['product_id'] ?>" style="font-weight: 700;">
                                    <?= e($b['product_name']) ?>
                                </a><br>
                                <code><?= e($b['sku']) ?></code>
                            </td>
                            <td><?= e($b['category_name'] ?? 'General') ?></td>
                            <td>
                                <code><?= e($b['batch_no']) ?></code>
                            </td>
                            <td><?= e($b['supplier_name'] ?? 'Direct') ?></td>
                            <td>
                                <strong style="font-size: 1.05rem; color: <?= $isExpired ? 'var(--danger)' : 'var(--text-primary)' ?>;">
                                    <?= $b['current_quantity'] ?>
                                </strong> <?= e($b['unit']) ?>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: <?= $isExpired ? 'var(--danger)' : 'inherit' ?>;">
                                    <?= formatCurrency($b['total_batch_cost']) ?>
                                </span>
                            </td>
                            <td><?= formatCurrency($b['selling_price']) ?></td>
                            <td><?= formatDate($b['mfg_date']) ?></td>
                            <td>
                                <strong><?= formatDate($b['expiry_date']) ?></strong>
                            </td>
                            <td>
                                <span class="badge <?= $expInfo['badge_class'] ?>">
                                    <i class="fa-solid <?= $expInfo['icon'] ?>"></i>
                                    <?= $expInfo['label'] ?>
                                </span>
                            </td>
                            <td class="no-export" style="text-align: right;">
                                <?php if ($isExpired): ?>
                                    <a href="<?= BASE_URL ?>/modules/batches/adjust.php?batch_id=<?= $b['id'] ?>" 
                                       class="btn btn-danger btn-sm" title="Write-off / Dispose Expired Stock">
                                        <i class="fa-solid fa-trash-can"></i>
                                        <span>Dispose</span>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/modules/batches/adjust.php?batch_id=<?= $b['id'] ?>" 
                                       class="btn btn-secondary btn-sm" title="Adjust Stock">
                                        <i class="fa-solid fa-sliders"></i>
                                        <span>Adjust</span>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            <i class="fa-solid fa-shield-check" style="font-size: 2rem; color: var(--success); margin-bottom: 0.75rem; display: block;"></i>
                            No batches matching the "<?= e($filter) ?>" expiry filter criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
