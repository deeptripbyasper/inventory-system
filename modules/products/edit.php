<?php
/**
 * Edit Existing Product
 */

$pageTitle = 'Edit Product';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$errors = [];

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $db->fetchOne("SELECT * FROM `products` WHERE `id` = ?", "i", [$productId]);

if (!$product) {
    setFlash('danger', 'Product not found.');
    header("Location: " . BASE_URL . "/modules/products/index.php");
    exit;
}

$categories = $db->fetchAll("SELECT * FROM `categories` ORDER BY `name` ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Security token invalid. Please refresh the page.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $sku = trim($_POST['sku'] ?? '');
        $barcode = trim($_POST['barcode'] ?? '');
        $unit = trim($_POST['unit'] ?? 'pcs');
        $minStock = (int)($_POST['min_stock_alert'] ?? 10);
        $defaultPrice = (float)($_POST['default_selling_price'] ?? 0.00);
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $errors[] = 'Product name is required.';
        }
        if (empty($sku)) {
            $errors[] = 'SKU is required.';
        }

        // Check if SKU exists on another product
        $existing = $db->fetchOne("SELECT id FROM `products` WHERE `sku` = ? AND `id` != ?", "si", [$sku, $productId]);
        if ($existing) {
            $errors[] = "SKU '{$sku}' is already assigned to another product.";
        }

        if (empty($errors)) {
            $updateSql = "
                UPDATE `products` 
                SET `category_id` = ?, `sku` = ?, `barcode` = ?, `name` = ?, `description` = ?, `unit` = ?, `min_stock_alert` = ?, `default_selling_price` = ?, `status` = ?
                WHERE `id` = ?
            ";
            $res = $db->execute($updateSql, "isssssidsi", [
                $categoryId, $sku, $barcode, $name, $description, $unit, $minStock, $defaultPrice, $status, $productId
            ]);

            $batchMsg = "";
            if (!empty($_POST['update_batches_price'])) {
                $db->execute("UPDATE `product_batches` SET `selling_price` = ? WHERE `product_id` = ? AND `status` = 'available'", "di", [$defaultPrice, $productId]);
                $batchMsg = " and updated selling rate to " . formatCurrency($defaultPrice) . " for active stock batches";
            }

            setFlash('success', "Product '{$name}' updated successfully{$batchMsg}!");
            header("Location: " . BASE_URL . "/modules/products/view.php?id=" . $productId);
            exit;
        }
    }
}
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i>
            <span>Edit Product: <?= e($product['name']) ?></span>
        </h1>
        <p>Update product specifications, category categorization, and alert thresholds</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/modules/products/view.php?id=<?= $product['id'] ?>" class="btn btn-secondary">
            <i class="fa-solid fa-eye"></i>
            <span>View Product</span>
        </a>
        <a href="<?= BASE_URL ?>/modules/products/index.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Catalog</span>
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

<form action="<?= BASE_URL ?>/modules/products/edit.php?id=<?= $productId ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-sliders" style="color: var(--primary);"></i>
                <span>Product Attributes</span>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" for="name">Product Name <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" required value="<?= e($_POST['name'] ?? $product['name']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">Select Category...</option>
                        <?php 
                        $selectedCat = $_POST['category_id'] ?? $product['category_id'];
                        foreach ($categories as $cat): 
                        ?>
                            <option value="<?= $cat['id'] ?>" <?= $selectedCat == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="sku">SKU Code <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="sku" name="sku" class="form-control" required value="<?= e($_POST['sku'] ?? $product['sku']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="barcode">Barcode / UPC</label>
                    <input type="text" id="barcode" name="barcode" class="form-control" value="<?= e($_POST['barcode'] ?? $product['barcode']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="unit">Unit of Measure</label>
                    <select id="unit" name="unit" class="form-select">
                        <?php 
                        $units = ['pcs', 'box', 'strip', 'bottle', 'can', 'carton', 'pack', 'tub', 'kg'];
                        $currentUnit = $_POST['unit'] ?? $product['unit'];
                        foreach ($units as $u):
                        ?>
                            <option value="<?= $u ?>" <?= $currentUnit === $u ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="default_selling_price">Default Selling Price (<?= e(CURRENCY_SYMBOL) ?>)</label>
                    <input type="number" step="0.01" min="0" id="default_selling_price" name="default_selling_price" class="form-control" value="<?= e($_POST['default_selling_price'] ?? $product['default_selling_price']) ?>" required>
                    <div style="margin-top: 0.5rem;">
                        <label style="display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.82rem; color: var(--text-secondary); cursor: pointer; line-height: 1.35;">
                            <input type="checkbox" name="update_batches_price" value="1" checked style="margin-top: 2px;">
                            <span><strong>Sync Active Stock:</strong> Update selling price for all active batches of this product to reflect immediately at POS terminal.</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="min_stock_alert">Low Stock Alert Threshold</label>
                    <input type="number" min="1" id="min_stock_alert" name="min_stock_alert" class="form-control" value="<?= e($_POST['min_stock_alert'] ?? $product['min_stock_alert']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Product Status</label>
                    <select id="status" name="status" class="form-select">
                        <?php $currentStatus = $_POST['status'] ?? $product['status']; ?>
                        <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $currentStatus === 'inactive' ? 'selected' : '' ?>>Inactive (Archived)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Product Description / Storage Info</label>
                <textarea id="description" name="description" class="form-control" rows="3"><?= e($_POST['description'] ?? $product['description']) ?></textarea>
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>/modules/products/index.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-floppy-disk"></i>
            <span>Update Product</span>
        </button>
    </div>
</form>

<?php include INCLUDES_PATH . '/footer.php'; ?>
