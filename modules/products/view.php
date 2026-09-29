<?php
/**
 * Detailed 360-Degree Product View
 * Shows all batches, leftover quantities, expiry timelines, and sales history
 */

$pageTitle = 'Product Details';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $db->fetchOne("
    SELECT 
        p.*, 
        c.name as category_name,
        COALESCE(SUM(b.current_quantity), 0) as total_leftover_stock,
        COALESCE(SUM(b.initial_quantity), 0) as total_initial_stock,
        COALESCE(SUM(b.current_quantity * b.purchase_price), 0) as total_cost_value,
        COALESCE(SUM(b.current_quantity * b.selling_price), 0) as total_retail_value
    FROM `products` p
    LEFT JOIN `categories` c ON p.category_id = c.id
    LEFT JOIN `product_batches` b ON p.id = b.product_id
    WHERE p.id = ?
    GROUP BY p.id, c.name
", "i", [$productId]);

if (!$product) {
    setFlash('danger', 'Product not found.');
    header("Location: " . BASE_URL . "/modules/products/index.php");
    exit;
}

// Fetch all batches for this product
$batches = $db->fetchAll("
    SELECT 
        b.*,
        s.name as supplier_name,
        (b.initial_quantity - b.current_quantity) as total_sold
    FROM `product_batches` b
    LEFT JOIN `suppliers` s ON b.supplier_id = s.id
    WHERE b.product_id = ?
    ORDER BY b.expiry_date ASC
", "i", [$productId]);

// Fetch recent sales of this product
$salesHistory = $db->fetchAll("
    SELECT 
        si.quantity,
        si.unit_price,
        si.subtotal,
        s.invoice_no,
        s.sale_date,
        s.customer_name,
        b.batch_no
    FROM `sale_items` si
    JOIN `sales` s ON si.sale_id = s.id
    LEFT JOIN `product_batches` b ON si.batch_id = b.id
    WHERE si.product_id = ?
    ORDER BY s.sale_date DESC
    LIMIT 10
", "i", [$productId]);
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-box-open" style="color: var(--primary);"></i>
            <span><?= e($product['name']) ?></span>
        </h1>
        <p>SKU: <code><?= e($product['sku']) ?></code> | Category: <strong><?= e($product['category_name'] ?? 'Uncategorized') ?></strong></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/modules/batches/restock.php?product_id=<?= $product['id'] ?>" class="btn btn-success">
            <i class="fa-solid fa-plus"></i>
            <span>Restock / New Batch</span>
        </a>
        <a href="<?= BASE_URL ?>/modules/products/edit.php?id=<?= $product['id'] ?>" class="btn btn-primary">
            <i class="fa-solid fa-pen-to-square"></i>
            <span>Edit Product</span>
        </a>
        <a href="<?= BASE_URL ?>/modules/products/index.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Products</span>
        </a>
    </div>
</div>

<!-- Product Quick KPI Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Total Leftover Stock</div>
            <div class="stat-value" style="color: <?= (int)$product['total_leftover_stock'] <= (int)$product['min_stock_alert'] ? 'var(--danger)' : 'var(--text-primary)' ?>;">
                <?= number_format($product['total_leftover_stock']) ?> <span style="font-size: 0.95rem; font-weight: 500;"><?= e($product['unit']) ?></span>
            </div>
            <div class="stat-subtext">
                Alert threshold: <?= $product['min_stock_alert'] ?> <?= e($product['unit']) ?>
            </div>
        </div>
        <div class="stat-icon <?= (int)$product['total_leftover_stock'] <= (int)$product['min_stock_alert'] ? 'danger' : 'primary' ?>">
            <i class="fa-solid fa-warehouse"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Stock Valuation (Cost)</div>
            <div class="stat-value"><?= formatCurrency($product['total_cost_value']) ?></div>
            <div class="stat-subtext">
                Retail Value: <strong><?= formatCurrency($product['total_retail_value']) ?></strong>
            </div>
        </div>
        <div class="stat-icon info">
            <i class="fa-solid fa-calculator"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Default Selling Price</div>
            <div class="stat-value"><?= formatCurrency($product['default_selling_price']) ?></div>
            <div class="stat-subtext">
                Per <?= e($product['unit']) ?>
            </div>
        </div>
        <div class="stat-icon success">
            <i class="fa-solid fa-tag"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Stock Status</div>
            <div style="margin-top: 0.25rem;">
                <?= getStockStatusBadge((int)$product['total_leftover_stock'], (int)$product['min_stock_alert']) ?>
            </div>
            <div class="stat-subtext">
                <?= count($batches) ?> Recorded Batches
            </div>
        </div>
        <div class="stat-icon secondary">
            <i class="fa-solid fa-layer-group"></i>
        </div>
    </div>
</div>

<!-- Batches Breakdown Table (Leftover stock per batch with Expiry Date) -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-layer-group" style="color: var(--primary);"></i>
            <span>Inventory Batches & Expiration Tracking</span>
        </div>
        <a href="<?= BASE_URL ?>/modules/batches/restock.php?product_id=<?= $product['id'] ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i>
            <span>Add Batch</span>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Batch No</th>
                    <th>Supplier</th>
                    <th>Mfg Date</th>
                    <th>Expiry Date</th>
                    <th>Purchase Cost</th>
                    <th>Selling Price</th>
                    <th>Initial Qty</th>
                    <th>Sold Qty</th>
                    <th>Leftover Stock</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($batches)): ?>
                    <?php foreach ($batches as $b): 
                        $expInfo = getExpiryStatus($b['expiry_date']);
                        $isExpired = $expInfo['status'] === 'expired';
                    ?>
                        <tr class="<?= $isExpired ? 'highlight-expired' : '' ?>">
                            <td>
                                <strong><?= e($b['batch_no']) ?></strong>
                            </td>
                            <td><?= e($b['supplier_name'] ?? 'Direct / N/A') ?></td>
                            <td><?= formatDate($b['mfg_date']) ?></td>
                            <td>
                                <span class="badge <?= $expInfo['badge_class'] ?>">
                                    <i class="fa-solid <?= $expInfo['icon'] ?>"></i>
                                    <?= formatDate($b['expiry_date']) ?> (<?= $expInfo['days_left'] < 0 ? abs($expInfo['days_left']).'d ago' : $expInfo['days_left'].'d' ?>)
                                </span>
                            </td>
                            <td><?= formatCurrency($b['purchase_price']) ?></td>
                            <td><strong><?= formatCurrency($b['selling_price']) ?></strong></td>
                            <td><?= $b['initial_quantity'] ?></td>
                            <td><?= $b['total_sold'] ?></td>
                            <td>
                                <strong style="font-size: 1.05rem; color: <?= $b['current_quantity'] <= 0 ? 'var(--danger)' : 'var(--text-primary)' ?>;">
                                    <?= $b['current_quantity'] ?>
                                </strong>
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
                            <td style="text-align: right;">
                                <a href="<?= BASE_URL ?>/modules/batches/adjust.php?batch_id=<?= $b['id'] ?>" 
                                   class="btn btn-secondary btn-sm" title="Adjust / Write Off Stock">
                                    <i class="fa-solid fa-sliders"></i>
                                    <span>Adjust</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            No inventory batches recorded for this product yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Product Sales History -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-clock-rotate-left" style="color: var(--info);"></i>
            <span>Recent Sales of this Product</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Date & Time</th>
                    <th>Customer</th>
                    <th>Batch Sold From</th>
                    <th>Quantity Sold</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($salesHistory)): ?>
                    <?php foreach ($salesHistory as $sh): ?>
                        <tr>
                            <td><strong><?= e($sh['invoice_no']) ?></strong></td>
                            <td><?= formatDateTime($sh['sale_date']) ?></td>
                            <td><?= e($sh['customer_name']) ?></td>
                            <td><code><?= e($sh['batch_no'] ?? 'N/A') ?></code></td>
                            <td><strong><?= $sh['quantity'] ?></strong> <?= e($product['unit']) ?></td>
                            <td><?= formatCurrency($sh['unit_price']) ?></td>
                            <td><strong style="color: var(--primary);"><?= formatCurrency($sh['subtotal']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            No sales recorded for this product yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
