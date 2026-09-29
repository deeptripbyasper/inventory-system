<?php
/**
 * Supplier & Vendor Management
 */

$pageTitle = 'Suppliers Directory';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$errors = [];

// Handle Add / Edit / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? 'add';

        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $contact = trim($_POST['contact_person'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (empty($name)) {
                $errors[] = 'Supplier company name is required.';
            } else {
                $db->insert("
                    INSERT INTO `suppliers` (`name`, `contact_person`, `phone`, `email`, `address`)
                    VALUES (?, ?, ?, ?, ?)
                ", "sssss", [$name, $contact, $phone, $email, $address]);

                setFlash('success', "Supplier '{$name}' added successfully.");
                header("Location: " . BASE_URL . "/modules/suppliers/index.php");
                exit;
            }
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $contact = trim($_POST['contact_person'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (empty($name)) {
                $errors[] = 'Supplier company name cannot be empty.';
            } else {
                $db->execute("
                    UPDATE `suppliers` 
                    SET `name` = ?, `contact_person` = ?, `phone` = ?, `email` = ?, `address` = ?
                    WHERE `id` = ?
                ", "sssssi", [$name, $contact, $phone, $email, $address, $id]);

                setFlash('success', "Supplier '{$name}' updated successfully.");
                header("Location: " . BASE_URL . "/modules/suppliers/index.php");
                exit;
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $db->execute("DELETE FROM `suppliers` WHERE `id` = ?", "i", [$id]);
            setFlash('info', 'Supplier deleted.');
            header("Location: " . BASE_URL . "/modules/suppliers/index.php");
            exit;
        }
    }
}

// Fetch all suppliers with supplied batch counts
$suppliers = $db->fetchAll("
    SELECT 
        s.*,
        COUNT(b.id) as total_batches,
        COALESCE(SUM(b.current_quantity), 0) as active_stock
    FROM `suppliers` s
    LEFT JOIN `product_batches` b ON s.id = b.supplier_id
    GROUP BY s.id
    ORDER BY s.name ASC
");
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-truck-field" style="color: var(--primary);"></i>
            <span>Suppliers & Vendor Directory</span>
        </h1>
        <p>Maintain vendor contacts, procurement channels, and delivered inventory lots</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addSupplierModal')">
            <i class="fa-solid fa-plus"></i>
            <span>Add Supplier</span>
        </button>
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

<!-- Suppliers Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Company Name</th>
                    <th>Contact Person</th>
                    <th>Phone / Email</th>
                    <th>Address</th>
                    <th>Batches Supplied</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($suppliers)): ?>
                    <?php foreach ($suppliers as $sup): ?>
                        <tr>
                            <td>
                                <strong><?= e($sup['name']) ?></strong>
                            </td>
                            <td><?= e($sup['contact_person'] ?? '-') ?></td>
                            <td>
                                <div><i class="fa-solid fa-phone" style="font-size: 0.75rem; color: var(--text-muted);"></i> <?= e($sup['phone'] ?? '-') ?></div>
                                <small style="color: var(--text-muted);"><i class="fa-solid fa-envelope" style="font-size: 0.75rem;"></i> <?= e($sup['email'] ?? '-') ?></small>
                            </td>
                            <td><?= e($sup['address'] ?? '-') ?></td>
                            <td>
                                <strong><?= $sup['total_batches'] ?></strong> batches 
                                <small>(<?= number_format($sup['active_stock']) ?> units in stock)</small>
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick='editSupplier(<?= json_encode($sup) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <span>Edit</span>
                                </button>
                                <form action="<?= BASE_URL ?>/modules/suppliers/index.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete supplier <?= e(addslashes($sup['name'])) ?>?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $sup['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            No suppliers registered yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal-backdrop" id="addSupplierModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="card-title">
                <i class="fa-solid fa-plus-circle" style="color: var(--primary);"></i>
                <span>Add New Supplier</span>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeModal('addSupplierModal')">&times;</button>
        </div>
        <form action="<?= BASE_URL ?>/modules/suppliers/index.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="add_sup_name">Company / Supplier Name <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="add_sup_name" name="name" class="form-control" placeholder="e.g. Apex Global Supplies" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="add_contact">Contact Person</label>
                        <input type="text" id="add_contact" name="contact_person" class="form-control" placeholder="e.g. John Smith">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_phone">Phone Number</label>
                        <input type="text" id="add_phone" name="phone" class="form-control" placeholder="+1 555-0199">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="add_email">Email Address</label>
                    <input type="email" id="add_email" name="email" class="form-control" placeholder="orders@supplier.com">
                </div>
                <div class="form-group">
                    <label class="form-label" for="add_address">Address / Warehouse Location</label>
                    <textarea id="add_address" name="address" class="form-control" rows="2" placeholder="Street, City, State..."></textarea>
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addSupplierModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal-backdrop" id="editSupplierModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="card-title">
                <i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i>
                <span>Edit Supplier</span>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeModal('editSupplierModal')">&times;</button>
        </div>
        <form action="<?= BASE_URL ?>/modules/suppliers/index.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_sup_id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_sup_name">Company / Supplier Name <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="edit_sup_name" name="name" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_contact">Contact Person</label>
                        <input type="text" id="edit_contact" name="contact_person" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_phone">Phone Number</label>
                        <input type="text" id="edit_phone" name="phone" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_email">Email Address</label>
                    <input type="email" id="edit_email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_address">Address / Warehouse Location</label>
                    <textarea id="edit_address" name="address" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editSupplierModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Supplier</button>
            </div>
        </form>
    </div>
</div>

<script>
function editSupplier(sup) {
    document.getElementById('edit_sup_id').value = sup.id;
    document.getElementById('edit_sup_name').value = sup.name;
    document.getElementById('edit_contact').value = sup.contact_person || '';
    document.getElementById('edit_phone').value = sup.phone || '';
    document.getElementById('edit_email').value = sup.email || '';
    document.getElementById('edit_address').value = sup.address || '';
    openModal('editSupplierModal');
}
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
