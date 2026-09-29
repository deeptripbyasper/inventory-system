<?php
/**
 * Product Catalog & Stock Visibility (Product-Wise)
 */

$pageTitle = 'Product Catalog & Stock';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

// Filter parameters
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$stockFilter = $_GET['stock_status'] ?? 'all';

// Fetch Categories for dropdown
$categories = $db->fetchAll("SELECT * FROM `categories` ORDER BY `name` ASC");

// Build Product-Wise Stock Query
$sql = "
    SELECT 
        p.id,
        p.sku,
        p.barcode,
        p.name,
        p.unit,
        p.min_stock_alert,
        p.default_selling_price,
        p.status,
        c.name as category_name,
        COALESCE(SUM(b.current_quantity), 0) as total_leftover_stock,
        COUNT(b.id) as total_batches,
        MIN(CASE WHEN b.current_quantity > 0 THEN b.expiry_date ELSE NULL END) as nearest_expiry,
        COALESCE(SUM(b.current_quantity * b.purchase_price), 0) as stock_cost_value,
        COALESCE(SUM(b.current_quantity * b.selling_price), 0) as stock_retail_value
    FROM `products` p
    LEFT JOIN `categories` c ON p.category_id = c.id
    LEFT JOIN `product_batches` b ON p.id = b.product_id
    WHERE p.status = 'active'
";

$params = [];
$types = "";

if ($categoryId > 0) {
    $sql .= " AND p.category_id = ?";
    $types .= "i";
    $params[] = $categoryId;
}

$sql .= " GROUP BY p.id, p.sku, p.barcode, p.name, p.unit, p.min_stock_alert, p.default_selling_price, p.status, c.name";

if ($stockFilter === 'low') {
    $sql .= " HAVING total_leftover_stock > 0 AND total_leftover_stock <= p.min_stock_alert";
} elseif ($stockFilter === 'out') {
    $sql .= " HAVING total_leftover_stock = 0";
} elseif ($stockFilter === 'instock') {
    $sql .= " HAVING total_leftover_stock > p.min_stock_alert";
}

$sql .= " ORDER BY p.name ASC";

$products = $db->fetchAll($sql, $types, $params);
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-box-open" style="color: var(--primary);"></i>
            <span>Product Catalog & Stock Visibility</span>
        </h1>
        <p>Monitor product-wise leftover inventory, batch counts, nearest expiry dates and valuation</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-secondary" onclick="exportTableToCSV('productsTable', 'product_stock_catalog.csv')">
            <i class="fa-solid fa-file-csv"></i>
            <span>Export CSV</span>
        </button>
        <a href="<?= BASE_URL ?>/modules/products/add.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            <span>Add New Product</span>
        </a>
    </div>
</div>

<!-- Filter & Search Controls -->
<div class="filter-bar">
    <div class="filter-group">
        <form action="<?= BASE_URL ?>/modules/products/index.php" method="GET" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
            <select name="category" class="form-select" style="width: auto; min-width: 180px;" onchange="this.form.submit()">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="stock_status" class="form-select" style="width: auto; min-width: 160px;" onchange="this.form.submit()">
                <option value="all" <?= $stockFilter === 'all' ? 'selected' : '' ?>>All Stock Statuses</option>
                <option value="instock" <?= $stockFilter === 'instock' ? 'selected' : '' ?>>In Stock</option>
                <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low Stock (Alert)</option>
                <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Out of Stock</option>
            </select>

            <?php if ($categoryId > 0 || $stockFilter !== 'all'): ?>
                <a href="<?= BASE_URL ?>/modules/products/index.php" class="btn btn-secondary btn-sm" title="Clear Filters">
                    <i class="fa-solid fa-xmark"></i> Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="search-input-wrapper">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" class="form-control" placeholder="Search product name, SKU, barcode..." data-table-search="productsTable">
    </div>
</div>

<!-- Product-Wise Stock Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table" id="productsTable">
            <thead>
                <tr>
                    <th>Product & Category</th>
                    <th>SKU / Barcode</th>
                    <th>Leftover Stock</th>
                    <th>Stock Status</th>
                    <th>Nearest Expiry</th>
                    <th>Selling Price</th>
                    <th>Inventory Value</th>
                    <th class="no-export" style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $prod): 
                        $leftover = (int)$prod['total_leftover_stock'];
                        $minAlert = (int)$prod['min_stock_alert'];
                        $expInfo = $prod['nearest_expiry'] ? getExpiryStatus($prod['nearest_expiry']) : null;
                    ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/modules/products/view.php?id=<?= $prod['id'] ?>" style="font-weight: 700; font-size: 0.95rem;">
                                    <?= e($prod['name']) ?>
                                </a><br>
                                <small style="color: var(--text-muted); font-size: 0.78rem;">
                                    <i class="fa-solid fa-tag"></i> <?= e($prod['category_name'] ?? 'Uncategorized') ?> 
                                    (<?= $prod['total_batches'] ?> Batches)
                                </small>
                            </td>
                            <td>
                                <code><?= e($prod['sku']) ?></code><br>
                                <?php if (!empty($prod['barcode'])): ?>
                                    <small style="color: var(--text-muted);"><i class="fa-solid fa-barcode"></i> <?= e($prod['barcode']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size: 1.1rem; font-weight: 800; color: <?= $leftover <= $minAlert ? 'var(--danger)' : 'var(--text-primary)' ?>;">
                                    <?= number_format($leftover) ?>
                                </span> 
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?= e($prod['unit']) ?></span>
                            </td>
                            <td>
                                <?= getStockStatusBadge($leftover, $minAlert) ?>
                            </td>
                            <td>
                                <?php if ($expInfo): ?>
                                    <span class="badge <?= $expInfo['badge_class'] ?>" title="Expiry Date: <?= formatDate($prod['nearest_expiry']) ?>">
                                        <i class="fa-solid <?= $expInfo['icon'] ?>"></i>
                                        <?= formatDate($prod['nearest_expiry']) ?> (<?= $expInfo['days_left'] < 0 ? abs($expInfo['days_left']).'d ago' : $expInfo['days_left'].'d' ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No Active Batches</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: var(--primary);"><?= formatCurrency($prod['default_selling_price']) ?></strong>
                            </td>
                            <td>
                                <span title="Cost: <?= formatCurrency($prod['stock_cost_value']) ?>">
                                    <?= formatCurrency($prod['stock_retail_value']) ?>
                                </span>
                            </td>
                            <td class="no-export" style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.35rem;">
                                    <a href="<?= BASE_URL ?>/modules/batches/restock.php?product_id=<?= $prod['id'] ?>" 
                                       class="btn btn-success btn-sm" title="Restock / Add New Batch">
                                        <i class="fa-solid fa-plus"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/modules/products/view.php?id=<?= $prod['id'] ?>" 
                                       class="btn btn-secondary btn-sm" title="View Details & Batches">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/modules/products/edit.php?id=<?= $prod['id'] ?>" 
                                       class="btn btn-secondary btn-sm" title="Edit Product">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            <i class="fa-solid fa-box-open" style="font-size: 2rem; margin-bottom: 0.75rem; opacity: 0.5;"></i>
                            <p>No products found matching your search or filters.</p>
                            <a href="<?= BASE_URL ?>/modules/products/add.php" class="btn btn-primary btn-sm" style="margin-top: 0.5rem;">
                                Add Your First Product
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
