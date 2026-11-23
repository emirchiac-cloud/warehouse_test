<?php
// backend-web/categories.php
// Category Management View

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/controllers/CategoryController.php';

$msg = '';
$err = $_GET['error'] ?? '';

// Handle Category Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    try {
        CategoryController::store($name, $desc);
        $msg = 'Category added successfully!';
    } catch (Exception $e) {
        $err = $e->getMessage();
    }
}

// Handle Category Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    try {
        CategoryController::destroy($id);
        $msg = 'Category deleted successfully!';
    } catch (Exception $e) {
        $err = $e->getMessage();
    }
}

// Fetch categories with product counts
$categories = CategoryController::index(true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Back-Office Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .table-card { border: none; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,0.06); }
    </style>
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="bi bi-tags-fill text-primary me-2"></i>Product Categories</h4>
            <small class="text-muted">Manage taxonomy and categorization for warehouse inventory</small>
        </div>
        <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-circle me-1"></i> Add Category
        </button>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($err) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card table-card bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary text-uppercase small">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th class="text-center">Assigned Items</th>
                        <th class="pe-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="ps-4 text-muted fw-bold">#<?= $cat['id'] ?></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($cat['name']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($cat['description'] ?? 'No description') ?></td>
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill px-3"><?= (int)$cat['product_count'] ?> items</span>
                            </td>
                            <td class="pe-4 text-center">
                                <?php if ((int)$cat['product_count'] === 0): ?>
                                    <form method="POST" action="categories.php" onsubmit="return confirm('Delete this category?')" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small" title="Foreign key ON DELETE RESTRICT prevents deleting active category">In Use</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add Category -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="categories.php">
            <input type="hidden" name="action" value="add">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-folder-plus me-2"></i>New Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Packaging Materials">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Category overview..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Create Category</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
