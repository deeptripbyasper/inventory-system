<?php
/**
 * Add New Product & Optional Initial Batch
 */

$pageTitle = 'Add New Product';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$errors = [];

// Fetch Categories & Suppliers
$categories = $db->fetchAll("SELECT * FROM `categories` ORDER BY `name` ASC");
$suppliers = $db->fetchAll("SELECT * FROM `suppliers` ORDER BY `name` ASC");

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
        $description = trim($_POST['description'] ?? '');

        // Auto-generate SKU if empty
        if (empty($sku)) {
            $sku = 'PRD-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 4)) . '-' . rand(100, 999);
        }

        if (empty($name)) {
            $errors[] = 'Product name is required.';
        }

        // Check if SKU exists
        $existing = $db->fetchOne("SELECT id FROM `products` WHERE `sku` = ?", "s", [$sku]);
        if ($existing) {
            $errors[] = "A product with SKU '{$sku}' already exists. Please choose a unique SKU.";
        }

        if (empty($errors)) {
            $db->beginTransaction();

            $insertProductSql = "
                INSERT INTO `products` (`category_id`, `sku`, `barcode`, `name`, `description`, `unit`, `min_stock_alert`, `default_selling_price`, `status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ";
            $productId = $db->insert($insertProductSql, "isssssid", [
                $categoryId, $sku, $barcode, $name, $description, $unit, $minStock, $defaultPrice
            ]);

            if ($productId) {
                // Check if initial batch was added
                $hasInitialBatch = isset($_POST['add_initial_batch']);
                if ($hasInitialBatch) {
                    $batchNo = trim($_POST['batch_no'] ?? 'BAT-' . date('Ymd') . '-01');
                    $mfgDate = !empty($_POST['mfg_date']) ? $_POST['mfg_date'] : null;
                    $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
                    $purchasePrice = (float)($_POST['purchase_price'] ?? 0.00);
                    $batchSellingPrice = (float)($_POST['batch_selling_price'] ?? $defaultPrice);
                    $quantity = (int)($_POST['initial_quantity'] ?? 0);
                    $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;

                    if (empty($expiryDate)) {
                        $expiryDate = date('Y-m-d', strtotime('+1 year')); // default fallback
                    }

                    if ($quantity > 0) {
                        $insertBatchSql = "
                            INSERT INTO `product_batches` (`product_id`, `supplier_id`, `batch_no`, `mfg_date`, `expiry_date`, `purchase_price`, `selling_price`, `initial_quantity`, `current_quantity`, `status`)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')
                        ";
                        $batchId = $db->insert($insertBatchSql, "iisssddii", [
                            $productId, $supplierId, $batchNo, $mfgDate, $expiryDate, $purchasePrice, $batchSellingPrice, $quantity, $quantity
                        ]);

                        // Record Stock-in log
                        if ($batchId) {
                            $db->execute("
                                INSERT INTO `stock_in_logs` (`product_id`, `batch_id`, `supplier_id`, `user_id`, `quantity`, `purchase_price`, `selling_price`, `invoice_reference`, `notes`)
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'INITIAL-STOCK', 'Initial batch upon product creation')
                            ", "iiiiidd", [
                                $productId, $batchId, $supplierId, $currentUser['id'] ?? null, $quantity, $purchasePrice, $batchSellingPrice
                            ]);
                        }
                    }
                }

                $db->commit();
                setFlash('success', "Product '{$name}' created successfully!");
                header("Location: " . BASE_URL . "/modules/products/view.php?id=" . $productId);
                exit;
            } else {
                $db->rollback();
                $errors[] = "Failed to save product: " . $db->getError();
            }
        }
    }
}
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-plus-circle" style="color: var(--primary);"></i>
            <span>Add New Product</span>
        </h1>
        <p>Register a new product with category, SKU, pricing & initial batch expiry tracking</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/modules/products/index.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Products</span>
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

<form action="<?= BASE_URL ?>/modules/products/add.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-box-open" style="color: var(--primary);"></i>
                <span>Master Product Information</span>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" for="name">Product Name <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Paracetamol 500mg Tablets" required value="<?= e($_POST['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">Select Category...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="sku">SKU Code</label>
                    <input type="text" id="sku" name="sku" class="form-control" placeholder="Leave blank to auto-generate" value="<?= e($_POST['sku'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="barcode">Barcode / UPC</label>
                    <input type="text" id="barcode" name="barcode" class="form-control" placeholder="e.g. 890103001001" value="<?= e($_POST['barcode'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="unit">Unit of Measure</label>
                    <select id="unit" name="unit" class="form-select">
                        <option value="pcs" <?= (isset($_POST['unit']) && $_POST['unit'] === 'pcs') ? 'selected' : '' ?>>pcs (Pieces)</option>
                        <option value="box" <?= (isset($_POST['unit']) && $_POST['unit'] === 'box') ? 'selected' : '' ?>>box (Box)</option>
                        <option value="strip" <?= (isset($_POST['unit']) && $_POST['unit'] === 'strip') ? 'selected' : '' ?>>strip (Strip)</option>
                        <option value="bottle" <?= (isset($_POST['unit']) && $_POST['unit'] === 'bottle') ? 'selected' : '' ?>>bottle (Bottle)</option>
                        <option value="can" <?= (isset($_POST['unit']) && $_POST['unit'] === 'can') ? 'selected' : '' ?>>can (Can)</option>
                        <option value="carton" <?= (isset($_POST['unit']) && $_POST['unit'] === 'carton') ? 'selected' : '' ?>>carton (Carton)</option>
                        <option value="pack" <?= (isset($_POST['unit']) && $_POST['unit'] === 'pack') ? 'selected' : '' ?>>pack (Pack)</option>
                        <option value="tub" <?= (isset($_POST['unit']) && $_POST['unit'] === 'tub') ? 'selected' : '' ?>>tub (Tub)</option>
                        <option value="kg" <?= (isset($_POST['unit']) && $_POST['unit'] === 'kg') ? 'selected' : '' ?>>kg (Kilogram)</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="default_selling_price">Default Selling Price (<?= e(CURRENCY_SYMBOL) ?>)</label>
                    <input type="number" step="0.01" min="0" id="default_selling_price" name="default_selling_price" class="form-control" placeholder="0.00" value="<?= e($_POST['default_selling_price'] ?? '0.00') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="min_stock_alert">Low Stock Alert Threshold</label>
                    <input type="number" min="1" id="min_stock_alert" name="min_stock_alert" class="form-control" value="<?= e($_POST['min_stock_alert'] ?? '10') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Product Description / Notes</label>
                <textarea id="description" name="description" class="form-control" rows="2" placeholder="Optional notes, dosage, storage guidelines..."><?= e($_POST['description'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- Optional Initial Stock Batch Card -->
    <div class="card">
        <div class="card-header" style="cursor: pointer;" onclick="document.getElementById('add_initial_batch').click()">
            <div class="card-title">
                <input type="checkbox" id="add_initial_batch" name="add_initial_batch" value="1" 
                       <?= isset($_POST['add_initial_batch']) ? 'checked' : 'checked' ?>
                       onclick="event.stopPropagation(); toggleBatchSection(this.checked)" 
                       style="width: 18px; height: 18px; accent-color: var(--primary);">
                <span>Add Initial Inventory Batch with Expiry Date</span>
            </div>
        </div>
        <div class="card-body" id="initialBatchSection">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="batch_no">Batch Number</label>
                    <input type="text" id="batch_no" name="batch_no" class="form-control" placeholder="e.g. BAT-2026-01" value="<?= e($_POST['batch_no'] ?? 'BAT-' . date('Ymd') . '-01') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="supplier_id">Supplier</label>
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
                    <label class="form-label" for="mfg_date">Manufacturing Date</label>
                    <input type="date" id="mfg_date" name="mfg_date" class="form-control" value="<?= e($_POST['mfg_date'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="expiry_date">Expiry Date <span style="color: var(--danger);">*</span></label>
                    <input type="date" id="expiry_date" name="expiry_date" class="form-control" value="<?= e($_POST['expiry_date'] ?? date('Y-m-d', strtotime('+1 year'))) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="purchase_price">Unit Purchase Cost (<?= e(CURRENCY_SYMBOL) ?>)</label>
                    <input type="number" step="0.01" min="0" id="purchase_price" name="purchase_price" class="form-control" placeholder="0.00" value="<?= e($_POST['purchase_price'] ?? '0.00') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="batch_selling_price">Batch Selling Price (<?= e(CURRENCY_SYMBOL) ?>)</label>
                    <input type="number" step="0.01" min="0" id="batch_selling_price" name="batch_selling_price" class="form-control" placeholder="0.00" value="<?= e($_POST['batch_selling_price'] ?? '0.00') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="initial_quantity">Initial Quantity (Units)</label>
                    <input type="number" min="0" id="initial_quantity" name="initial_quantity" class="form-control" value="<?= e($_POST['initial_quantity'] ?? '50') ?>">
                </div>
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
        <a href="<?= BASE_URL ?>/modules/products/index.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-floppy-disk"></i>
            <span>Save Product & Stock</span>
        </button>
    </div>
</form>

<script>
function toggleBatchSection(show) {
    const section = document.getElementById('initialBatchSection');
    if (section) {
        section.style.display = show ? 'block' : 'none';
    }
}
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
