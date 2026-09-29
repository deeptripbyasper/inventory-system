<?php
/**
 * Category Management
 */

$pageTitle = 'Product Categories';
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
            $description = trim($_POST['description'] ?? '');

            if (empty($name)) {
                $errors[] = 'Category name is required.';
            } else {
                $exists = $db->fetchOne("SELECT id FROM `categories` WHERE `name` = ?", "s", [$name]);
                if ($exists) {
                    $errors[] = "Category '{$name}' already exists.";
                } else {
                    $db->insert("INSERT INTO `categories` (`name`, `description`) VALUES (?, ?)", "ss", [$name, $description]);
                    setFlash('success', "Category '{$name}' added successfully.");
                    header("Location: " . BASE_URL . "/modules/categories/index.php");
                    exit;
                }
            }
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($name)) {
                $errors[] = 'Category name cannot be empty.';
            } else {
                $exists = $db->fetchOne("SELECT id FROM `categories` WHERE `name` = ? AND `id` != ?", "si", [$name, $id]);
                if ($exists) {
                    $errors[] = "Category '{$name}' is already used by another category.";
                } else {
                    $db->execute("UPDATE `categories` SET `name` = ?, `description` = ? WHERE `id` = ?", "ssi", [$name, $description, $id]);
                    setFlash('success', "Category '{$name}' updated successfully.");
                    header("Location: " . BASE_URL . "/modules/categories/index.php");
                    exit;
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $db->execute("DELETE FROM `categories` WHERE `id` = ?", "i", [$id]);
            setFlash('info', 'Category deleted.');
            header("Location: " . BASE_URL . "/modules/categories/index.php");
            exit;
        }
    }
}

// Fetch all categories with total product counts & leftover stock
$categories = $db->fetchAll("
    SELECT 
        c.*,
        COUNT(DISTINCT p.id) as product_count,
        COALESCE(SUM(b.current_quantity), 0) as total_units
    FROM `categories` c
    LEFT JOIN `products` p ON c.id = p.category_id AND p.status = 'active'
    LEFT JOIN `product_batches` b ON p.id = b.product_id
    GROUP BY c.id
    ORDER BY c.name ASC
");
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-tags" style="color: var(--primary);"></i>
            <span>Product Categories</span>
        </h1>
        <p>Organize products into logical inventory groups, department classifications, and shelf sections</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addCategoryModal')">
            <i class="fa-solid fa-plus"></i>
            <span>Add Category</span>
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

<!-- Categories Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Active Products</th>
                    <th>Total Leftover Stock</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <strong><?= e($cat['name']) ?></strong>
                            </td>
                            <td><?= e($cat['description'] ?? '-') ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/modules/products/index.php?category=<?= $cat['id'] ?>">
                                    <?= $cat['product_count'] ?> products
                                </a>
                            </td>
                            <td><strong><?= number_format($cat['total_units']) ?></strong> units</td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick='editCategory(<?= json_encode($cat) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <span>Edit</span>
                                </button>
                                <form action="<?= BASE_URL ?>/modules/categories/index.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete category <?= e(addslashes($cat['name'])) ?>?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            No categories registered yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal-backdrop" id="addCategoryModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="card-title">
                <i class="fa-solid fa-plus-circle" style="color: var(--primary);"></i>
                <span>Add Category</span>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeModal('addCategoryModal')">&times;</button>
        </div>
        <form action="<?= BASE_URL ?>/modules/categories/index.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="add_name">Category Name <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="add_name" name="name" class="form-control" placeholder="e.g. Pharmaceuticals" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="add_description">Description</label>
                    <textarea id="add_description" name="description" class="form-control" rows="3" placeholder="Category notes..."></textarea>
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addCategoryModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal-backdrop" id="editCategoryModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="card-title">
                <i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i>
                <span>Edit Category</span>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeModal('editCategoryModal')">&times;</button>
        </div>
        <form action="<?= BASE_URL ?>/modules/categories/index.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_name">Category Name <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editCategoryModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Category</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(cat) {
    document.getElementById('edit_id').value = cat.id;
    document.getElementById('edit_name').value = cat.name;
    document.getElementById('edit_description').value = cat.description || '';
    openModal('editCategoryModal');
}
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
