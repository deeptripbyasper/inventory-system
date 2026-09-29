<?php
/**
 * Inventory Batches & Leftover Stock Management
 */

$pageTitle = 'Batches & Restock';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

$filter = $_GET['filter'] ?? 'all';
$critDays = defined('EXPIRY_CRITICAL_DAYS') ? EXPIRY_CRITICAL_DAYS : 30;
$warnDays = defined('EXPIRY_WARNING_DAYS') ? EXPIRY_WARNING_DAYS : 60;

$sql = "
    SELECT 
        b.*,
        p.name as product_name,
        p.sku,
        p.unit,
        c.name as category_name,
        s.name as supplier_name,
        (b.initial_quantity - b.current_quantity) as total_sold
    FROM `product_batches` b
    JOIN `products` p ON b.product_id = p.id
    LEFT JOIN `categories` c ON p.category_id = c.id
    LEFT JOIN `suppliers` s ON b.supplier_id = s.id
";

$where = [];

if ($filter === 'expired') {
    $where[] = "b.current_quantity > 0 AND b.expiry_date < CURDATE()";
} elseif ($filter === 'critical') {
    $where[] = "b.current_quantity > 0 AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$critDays} DAY)";
} elseif ($filter === 'warning') {
    $where[] = "b.current_quantity > 0 AND b.expiry_date BETWEEN DATE_ADD(CURDATE(), INTERVAL {$critDays} DAY) AND DATE_ADD(CURDATE(), INTERVAL {$warnDays} DAY)";
} elseif ($filter === 'available') {
    $where[] = "b.current_quantity > 0 AND b.expiry_date >= CURDATE()";
} elseif ($filter === 'soldout') {
    $where[] = "b.current_quantity <= 0";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY b.expiry_date ASC";

$batches = $db->fetchAll($sql);
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-layer-group" style="color: var(--primary);"></i>
            <span>Inventory Batches & Expiry Management</span>
        </h1>
        <p>Monitor leftover stock per lot/batch, expiration timelines, unit costs, and stock write-offs</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-secondary" onclick="exportTableToCSV('batchesTable', 'inventory_batches.csv')">
            <i class="fa-solid fa-file-csv"></i>
            <span>Export CSV</span>
        </button>
        <a href="<?= BASE_URL ?>/modules/batches/restock.php" class="btn btn-primary">
            <i class="fa-solid fa-truck-ramp-box"></i>
            <span>Restock / New Batch</span>
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <div class="filter-group">
        <a href="<?= BASE_URL ?>/modules/batches/index.php?filter=all" 
           class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">
            All Batches
        </a>
        <a href="<?= BASE_URL ?>/modules/batches/index.php?filter=available" 
           class="btn btn-sm <?= $filter === 'available' ? 'btn-primary' : 'btn-secondary' ?>">
            Active Leftover Stock
        </a>
        <a href="<?= BASE_URL ?>/modules/batches/index.php?filter=critical" 
           class="btn btn-sm <?= $filter === 'critical' ? 'btn-primary' : 'btn-secondary' ?>" style="color: var(--warning-critical);">
            <i class="fa-solid fa-clock"></i> Expiring ≤ <?= $critDays ?>d
        </a>
        <a href="<?= BASE_URL ?>/modules/batches/index.php?filter=expired" 
           class="btn btn-sm <?= $filter === 'expired' ? 'btn-primary' : 'btn-secondary' ?>" style="color: var(--danger);">
            <i class="fa-solid fa-triangle-exclamation"></i> Expired
        </a>
        <a href="<?= BASE_URL ?>/modules/batches/index.php?filter=soldout" 
           class="btn btn-sm <?= $filter === 'soldout' ? 'btn-primary' : 'btn-secondary' ?>">
            Sold Out
        </a>
    </div>

    <div class="search-input-wrapper">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" class="form-control" placeholder="Search batch no, product name, supplier..." data-table-search="batchesTable">
    </div>
</div>

<!-- Batches Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table" id="batchesTable">
            <thead>
                <tr>
                    <th>Batch No</th>
                    <th>Product Name & Category</th>
                    <th>Supplier</th>
                    <th>Expiry Date</th>
                    <th>Initial Qty</th>
                    <th>Sold Qty</th>
                    <th>Leftover Stock</th>
                    <th>Cost Value</th>
                    <th>Selling Rate</th>
                    <th>Status</th>
                    <th class="no-export" style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($batches)): ?>
                    <?php foreach ($batches as $b): 
                        $expInfo = getExpiryStatus($b['expiry_date']);
                        $isExpired = $expInfo['status'] === 'expired';
                        $leftoverValue = $b['current_quantity'] * $b['purchase_price'];
                    ?>
                        <tr class="<?= $isExpired ? 'highlight-expired' : ($expInfo['status'] === 'critical' ? 'highlight-critical' : '') ?>">
                            <td>
                                <code><?= e($b['batch_no']) ?></code>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/modules/products/view.php?id=<?= $b['product_id'] ?>" style="font-weight: 700;">
                                    <?= e($b['product_name']) ?>
                                </a><br>
                                <small style="color: var(--text-muted);"><?= e($b['category_name'] ?? 'General') ?> | SKU: <?= e($b['sku']) ?></small>
                            </td>
                            <td><?= e($b['supplier_name'] ?? 'Direct') ?></td>
                            <td>
                                <span class="badge <?= $expInfo['badge_class'] ?>">
                                    <i class="fa-solid <?= $expInfo['icon'] ?>"></i>
                                    <?= formatDate($b['expiry_date']) ?> (<?= $expInfo['days_left'] < 0 ? abs($expInfo['days_left']).'d ago' : $expInfo['days_left'].'d' ?>)
                                </span>
                            </td>
                            <td><?= $b['initial_quantity'] ?></td>
                            <td><?= $b['total_sold'] ?></td>
                            <td>
                                <strong style="font-size: 1.05rem; color: <?= $b['current_quantity'] <= 0 ? 'var(--danger)' : 'var(--text-primary)' ?>;">
                                    <?= $b['current_quantity'] ?>
                                </strong> <small><?= e($b['unit']) ?></small>
                            </td>
                            <td><?= formatCurrency($leftoverValue) ?></td>
                            <td>
                                <strong><?= formatCurrency($b['selling_price']) ?></strong>
                                <small style="color: var(--text-muted); display: block; font-size: 0.74rem;">/ <?= e($b['unit']) ?></small>
                            </td>
                            <td>
                                <?php if ($b['current_quantity'] <= 0): ?>
                                    <span class="badge badge-secondary">Sold Out</span>
                                <?php elseif ($isExpired): ?>
                                    <span class="badge badge-danger">Expired</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Available</span>
                                <?php endif; ?>
                            </td>
                            <td class="no-export" style="text-align: right;">
                                <a href="<?= BASE_URL ?>/modules/batches/adjust.php?batch_id=<?= $b['id'] ?>" 
                                   class="btn btn-secondary btn-sm" title="Stock Adjustment / Expired Write-Off">
                                    <i class="fa-solid fa-sliders"></i>
                                    <span>Adjust</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            No batches found for this filter criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
