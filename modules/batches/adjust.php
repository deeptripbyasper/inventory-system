<?php
/**
 * Stock Adjustment & Expired Stock Write-off Handler
 */

$pageTitle = 'Stock Adjustment & Write-off';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$errors = [];

$batchId = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$batch = $db->fetchOne("
    SELECT 
        b.*,
        p.name as product_name,
        p.sku,
        p.unit,
        s.name as supplier_name
    FROM `product_batches` b
    JOIN `products` p ON b.product_id = p.id
    LEFT JOIN `suppliers` s ON b.supplier_id = s.id
    WHERE b.id = ?
", "i", [$batchId]);

if (!$batch) {
    setFlash('danger', 'Batch not found.');
    header("Location: " . BASE_URL . "/modules/batches/index.php");
    exit;
}

$expInfo = getExpiryStatus($batch['expiry_date']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Security token invalid. Please refresh.';
    } else {
        $type = $_POST['adjustment_type'] ?? 'subtraction';
        $quantity = (int)($_POST['quantity'] ?? 0);
        $reason = $_POST['reason'] ?? 'expired';
        $notes = trim($_POST['notes'] ?? '');

        if ($quantity <= 0) {
            $errors[] = 'Adjustment quantity must be greater than 0.';
        }

        if ($type === 'subtraction' && $quantity > $batch['current_quantity']) {
            $errors[] = "Cannot subtract {$quantity} units. Current leftover stock is only {$batch['current_quantity']} units.";
        }

        if (empty($errors)) {
            $db->beginTransaction();

            $newQty = ($type === 'subtraction') 
                ? ($batch['current_quantity'] - $quantity)
                : ($batch['current_quantity'] + $quantity);

            $newStatus = $batch['status'];
            if ($newQty <= 0) {
                $newStatus = ($reason === 'expired') ? 'discarded' : 'sold_out';
            } elseif ($batch['expiry_date'] < date('Y-m-d')) {
                $newStatus = 'expired';
            } else {
                $newStatus = 'available';
            }

            // Update Batch
            $updateSql = "UPDATE `product_batches` SET `current_quantity` = ?, `status` = ? WHERE `id` = ?";
            $db->execute($updateSql, "isi", [$newQty, $newStatus, $batchId]);

            // Log Adjustment
            $logSql = "
                INSERT INTO `stock_adjustments` (`product_id`, `batch_id`, `user_id`, `adjustment_type`, `quantity`, `reason`, `notes`)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            $db->insert($logSql, "iiisiss", [
                $batch['product_id'], $batchId, $currentUser['id'] ?? null, $type, $quantity, $reason, $notes
            ]);

            $db->commit();
            setFlash('success', "Stock adjusted successfully. Remaining leftover stock: {$newQty} {$batch['unit']}.");
            header("Location: " . BASE_URL . "/modules/products/view.php?id=" . $batch['product_id']);
            exit;
        }
    }
}

// Fetch adjustment history for this batch
$adjustmentLogs = $db->fetchAll("
    SELECT 
        sa.*,
        u.full_name as user_name
    FROM `stock_adjustments` sa
    LEFT JOIN `users` u ON sa.user_id = u.id
    WHERE sa.batch_id = ?
    ORDER BY sa.created_at DESC
", "i", [$batchId]);
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-sliders" style="color: var(--primary);"></i>
            <span>Stock Adjustment & Write-Off</span>
        </h1>
        <p>Batch: <code><?= e($batch['batch_no']) ?></code> for <strong><?= e($batch['product_name']) ?></strong></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/modules/products/view.php?id=<?= $batch['product_id'] ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Product</span>
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

<!-- Batch Summary Card -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Current Leftover Stock</div>
            <div class="stat-value" style="color: <?= $batch['current_quantity'] <= 0 ? 'var(--danger)' : 'var(--text-primary)' ?>;">
                <?= $batch['current_quantity'] ?> <span style="font-size: 0.95rem; font-weight: 500;"><?= e($batch['unit']) ?></span>
            </div>
            <div class="stat-subtext">
                Initial: <?= $batch['initial_quantity'] ?> units
            </div>
        </div>
        <div class="stat-icon primary">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Expiry Date & Status</div>
            <div class="stat-value" style="font-size: 1.35rem;">
                <?= formatDate($batch['expiry_date']) ?>
            </div>
            <div class="stat-subtext">
                <span class="badge <?= $expInfo['badge_class'] ?>">
                    <i class="fa-solid <?= $expInfo['icon'] ?>"></i>
                    <?= $expInfo['label'] ?>
                </span>
            </div>
        </div>
        <div class="stat-icon <?= $expInfo['status'] === 'expired' ? 'danger' : 'orange' ?>">
            <i class="fa-solid fa-calendar-xmark"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Batch Valuation</div>
            <div class="stat-value"><?= formatCurrency($batch['current_quantity'] * $batch['purchase_price']) ?></div>
            <div class="stat-subtext">
                Cost: <?= formatCurrency($batch['purchase_price']) ?> / <?= e($batch['unit']) ?>
            </div>
        </div>
        <div class="stat-icon info">
            <i class="fa-solid fa-calculator"></i>
        </div>
    </div>
</div>

<!-- Adjustment Form -->
<form action="<?= BASE_URL ?>/modules/batches/adjust.php?batch_id=<?= $batchId ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-pen-ruler" style="color: var(--primary);"></i>
                <span>Record Inventory Adjustment</span>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="adjustment_type">Action Type</label>
                    <select id="adjustment_type" name="adjustment_type" class="form-select">
                        <option value="subtraction">Subtract Stock (Write-off, Expiry, Damage)</option>
                        <option value="addition">Add Stock (Inventory Audit Recovery)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reason">Reason for Adjustment</label>
                    <select id="reason" name="reason" class="form-select">
                        <option value="expired" <?= $expInfo['status'] === 'expired' ? 'selected' : '' ?>>Expired Stock (Disposal / Write-off)</option>
                        <option value="damaged">Damaged / Broken Product</option>
                        <option value="lost">Lost / Inventory Shrinkage</option>
                        <option value="inventory_audit">Routine Audit Discrepancy</option>
                        <option value="found">Found Extra Stock</option>
                        <option value="other">Other Reason</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="quantity">Quantity to Adjust (<?= e($batch['unit']) ?>) <span style="color: var(--danger);">*</span></label>
                    <input type="number" min="1" max="<?= $batch['current_quantity'] > 0 ? $batch['current_quantity'] : 99999 ?>" 
                           id="quantity" name="quantity" class="form-control" 
                           value="<?= $expInfo['status'] === 'expired' ? $batch['current_quantity'] : '1' ?>" required>
                    <?php if ($expInfo['status'] === 'expired' && $batch['current_quantity'] > 0): ?>
                        <small style="color: var(--danger); font-weight: 600;">Pre-filled with all <?= $batch['current_quantity'] ?> expired units for write-off.</small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="notes">Adjustment Audit Notes / Authorization</label>
                <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="e.g. Disposed of expired batch as per safety guidelines. Authorized by Store Manager."></textarea>
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>/modules/batches/index.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-danger btn-lg">
            <i class="fa-solid fa-check"></i>
            <span>Confirm Adjustment</span>
        </button>
    </div>
</form>

<!-- Audit Logs Table -->
<?php if (!empty($adjustmentLogs)): ?>
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-clock-rotate-left" style="color: var(--text-muted);"></i>
            <span>Adjustment Audit Trail for this Batch</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Reason</th>
                    <th>Adjusted By</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($adjustmentLogs as $log): ?>
                    <tr>
                        <td><?= formatDateTime($log['created_at']) ?></td>
                        <td>
                            <span class="badge <?= $log['adjustment_type'] === 'subtraction' ? 'badge-danger' : 'badge-success' ?>">
                                <?= $log['adjustment_type'] === 'subtraction' ? '- Subtraction' : '+ Addition' ?>
                            </span>
                        </td>
                        <td><strong><?= $log['quantity'] ?></strong> <?= e($batch['unit']) ?></td>
                        <td><span style="text-transform: capitalize; font-weight: 600;"><?= str_replace('_', ' ', $log['reason']) ?></span></td>
                        <td><?= e($log['user_name'] ?? 'System') ?></td>
                        <td><?= e($log['notes']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include INCLUDES_PATH . '/footer.php'; ?>
