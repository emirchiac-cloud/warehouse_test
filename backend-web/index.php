<?php
// backend-web/index.php
// Main Back-Office Management Portal Dashboard

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/controllers/ProductController.php';
require_once __DIR__ . '/controllers/CategoryController.php';
require_once __DIR__ . '/data/ProductData.php';

$baseUrl = getBaseUrl();

// Fetch summary metrics for KPI cards
$metricData = ProductData::getMetrics();
$kpi = [
    'total_products'  => $metricData['total_skus'] ?? 0,
    'total_valuation' => $metricData['total_valuation'] ?? 0,
    'low_stock_count' => $metricData['low_stock_count'] ?? 0
];

// Fetch categories for filtering and modals
$categories = CategoryController::index();

// Handle filter parameters
$filterCategory = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$products = ProductController::index([
    'category_id' => $filterCategory ?: null,
    'search'      => $search ?: null,
    'sort'        => 'low_stock_first'
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard - Back-Office Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .card-kpi {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .card-kpi:hover {
            transform: translateY(-3px);
        }
        .kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
        }
        .table-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .product-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            background: #fff;
        }
        .badge-low-stock {
            background-color: #dc3545;
            color: #fff;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.75; }
            100% { opacity: 1; }
        }
        .row-low-stock {
            background-color: #fff8f8 !important;
        }
        .btn-qty {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>

<div class="container-fluid px-4 py-4">

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Products -->
        <div class="col-md-4">
            <div class="card card-kpi bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Catalog SKUs</div>
                        <h3 class="fw-bold mb-0 text-dark"><?= number_format($kpi['total_products']) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Inventory Valuation -->
        <div class="col-md-4">
            <div class="card card-kpi bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="kpi-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Inventory Valuation</div>
                        <h3 class="fw-bold mb-0 text-dark">$<?= number_format($kpi['total_valuation'], 2) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="col-md-4">
            <div class="card card-kpi bg-white p-3 border-start border-danger border-4">
                <div class="d-flex align-items-center">
                    <div class="kpi-icon bg-danger bg-opacity-10 text-danger me-3">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                    </div>
                    <div>
                        <div class="text-danger small fw-semibold text-uppercase">Low-Stock Alerts</div>
                        <h3 class="fw-bold mb-0 text-danger"><?= number_format($kpi['low_stock_count']) ?> Items Below Safe Minimum</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Actions & Table -->
    <div class="card table-card bg-white">
        <div class="card-header bg-white py-3 px-4 border-bottom">
            <div class="row align-items-center g-3">
                <div class="col-lg-4 col-md-12">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Warehouse Inventory</h5>
                    <small class="text-muted">Real-time stock synchronized with native Android app</small>
                </div>
                <div class="col-lg-8 col-md-12">
                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                        <!-- Add Product Button -->
                        <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="bi bi-plus-circle me-1"></i> Add Product
                        </button>

                        <!-- Download PDF Button -->
                        <a href="export_pdf.php<?= $filterCategory ? '?category_id=' . $filterCategory : '' ?>" class="btn btn-danger btn-sm rounded-pill px-3" target="_blank">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Download Valuation &amp; Low-Stock PDF
                        </a>

                        <!-- Download Excel Button -->
                        <a href="export_excel.php<?= $filterCategory ? '?category_id=' . $filterCategory : '' ?>" class="btn btn-success btn-sm rounded-pill px-3">
                            <i class="bi bi-file-earmark-excel me-1"></i> Download Excel Report (.xlsx)
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" action="index.php" class="row g-2 mt-3 pt-2 border-top">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, SKU, or description..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $filterCategory === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm flex-grow-1">Filter</button>
                    <?php if ($filterCategory || $search): ?>
                        <a href="index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary text-uppercase" style="font-size: 0.78rem; letter-spacing: 0.5px;">
                    <tr>
                        <th class="ps-4">Item</th>
                        <th>Category</th>
                        <th>SKU</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-center">Stock Count</th>
                        <th class="text-center">Min Threshold</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Subtotal Valuation</th>
                        <th class="pe-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No products found matching your filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): 
                            $isLow = ((int)$p['quantity'] < (int)$p['min_stock_threshold']);
                            $subtotal = (float)$p['unit_price'] * (int)$p['quantity'];
                            $imgSrc = $baseUrl . '/' . ltrim($p['image_path'] ?: 'uploads/products/placeholder.png', '/');
                        ?>
                            <tr class="<?= $isLow ? 'row-low-stock' : '' ?>" id="product-row-<?= $p['id'] ?>">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="product-thumb me-3">
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></div>
                                            <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;">
                                                <?= htmlspecialchars($p['description'] ?? '') ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['category_name']) ?></span>
                                </td>
                                <td><code><?= htmlspecialchars($p['sku']) ?></code></td>
                                <td class="text-end fw-semibold">$<?= number_format($p['unit_price'], 2) ?></td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button class="btn btn-outline-danger btn-qty" onclick="adjustStock(<?= $p['id'] ?>, -1)" title="Stock Out -1">
                                            -
                                        </button>
                                        <span class="fw-bold px-2 fs-6 text-dark" id="qty-val-<?= $p['id'] ?>"><?= (int)$p['quantity'] ?></span>
                                        <button class="btn btn-outline-success btn-qty" onclick="adjustStock(<?= $p['id'] ?>, 1)" title="Stock In +1">
                                            +
                                        </button>
                                    </div>
                                </td>
                                <td class="text-center text-muted fw-semibold"><?= (int)$p['min_stock_threshold'] ?></td>
                                <td class="text-center" id="status-badge-<?= $p['id'] ?>">
                                    <?php if ($isLow): ?>
                                        <span class="badge badge-low-stock rounded-pill px-3 py-1">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> LOW STOCK
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-75 rounded-pill px-3 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i> IN STOCK
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-dark" id="subtotal-<?= $p['id'] ?>">
                                    $<?= number_format($subtotal, 2) ?>
                                </td>
                                <td class="pe-4 text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick='openEditModal(<?= json_encode($p) ?>)' title="Edit Details">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="openDeleteModal(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')" title="Delete Product">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==========================================
     MODALS (Add, Edit, Delete)
     ========================================== -->

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="formAddProduct" enctype="multipart/form-data">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Register New Inventory Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Product Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Wireless Barcode Scanner">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">SKU (Unique Identifier) *</label>
                        <input type="text" name="sku" class="form-control" required placeholder="e.g. EL-SCN-005">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Category *</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Unit Price ($) *</label>
                        <input type="number" step="0.01" min="0" name="unit_price" class="form-control" required value="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Initial Quantity *</label>
                        <input type="number" min="0" name="quantity" class="form-control" required value="10">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Min Stock Threshold *</label>
                        <input type="number" min="1" name="min_stock_threshold" class="form-control" required value="5">
                        <small class="text-muted">Triggers low-stock hardware alert when stock &lt; threshold.</small>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Product Image (JPG, PNG, WEBP max 5MB)</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Specifications, model details, or storage rack location..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <input type="hidden" name="user_id" value="<?= $currentUser['id'] ?>">
                <input type="hidden" name="platform" value="WEB">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Save Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="formEditProduct" enctype="multipart/form-data">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="id" id="edit-id">
            <input type="hidden" name="user_id" value="<?= $currentUser['id'] ?>">
            <input type="hidden" name="platform" value="WEB">

            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Product Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Product Name *</label>
                        <input type="text" name="name" id="edit-name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">SKU *</label>
                        <input type="text" name="sku" id="edit-sku" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Category *</label>
                        <select name="category_id" id="edit-category" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Unit Price ($) *</label>
                        <input type="number" step="0.01" min="0" name="unit_price" id="edit-price" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Current Quantity *</label>
                        <input type="number" min="0" name="quantity" id="edit-quantity" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Min Stock Threshold *</label>
                        <input type="number" min="1" name="min_stock_threshold" id="edit-threshold" class="form-control" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Replace Image (Optional)</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="edit-description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i> Update Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Product Confirmation Modal -->
<div class="modal fade" id="deleteProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-1 d-block mb-3"></i>
                <h5>Are you sure you want to delete this product?</h5>
                <p class="text-muted fw-bold" id="delete-product-name"></p>
                <div class="alert alert-warning small mb-0 text-start">
                    <i class="bi bi-info-circle me-1"></i> This action permanently deletes the item record, associated image file, and cascades audit log entries.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">Delete Permanently</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
let deleteTargetId = null;

// Real-time stock adjustment via AJAX to /api/products.php
async function adjustStock(productId, delta) {
    try {
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('id', productId);
        formData.append('stock_delta', delta);
        formData.append('user_id', <?= $currentUser['id'] ?>);
        formData.append('platform', 'WEB');

        const res = await fetch('api/products.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.status === 'success') {
            // Update UI elements in place
            document.getElementById(`qty-val-${productId}`).innerText = data.quantity;
            
            // Update low-stock badge
            const statusCell = document.getElementById(`status-badge-${productId}`);
            const row = document.getElementById(`product-row-${productId}`);
            if (data.is_low_stock) {
                statusCell.innerHTML = '<span class="badge badge-low-stock rounded-pill px-3 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> LOW STOCK</span>';
                row.classList.add('row-low-stock');
            } else {
                statusCell.innerHTML = '<span class="badge bg-success bg-opacity-75 rounded-pill px-3 py-1"><i class="bi bi-check-circle-fill me-1"></i> IN STOCK</span>';
                row.classList.remove('row-low-stock');
            }
        } else {
            alert('Failed to update stock: ' + (data.message || 'Unknown error'));
        }
    } catch (err) {
        console.error(err);
        alert('Network error communicating with API.');
    }
}

// Add Product AJAX Handler
document.getElementById('formAddProduct').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
        const res = await fetch('api/products.php', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();
        if (result.status === 'success') {
            window.location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (err) {
        alert('Failed to save product: ' + err.message);
    }
});

// Open Edit Modal
function openEditModal(prod) {
    document.getElementById('edit-id').value = prod.id;
    document.getElementById('edit-name').value = prod.name;
    document.getElementById('edit-sku').value = prod.sku;
    document.getElementById('edit-category').value = prod.category_id;
    document.getElementById('edit-price').value = prod.unit_price;
    document.getElementById('edit-quantity').value = prod.quantity;
    document.getElementById('edit-threshold').value = prod.min_stock_threshold;
    document.getElementById('edit-description').value = prod.description || '';

    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}

// Edit Product AJAX Handler
document.getElementById('formEditProduct').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
        const res = await fetch('api/products.php', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();
        if (result.status === 'success') {
            window.location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (err) {
        alert('Failed to update product: ' + err.message);
    }
});

// Delete Modal Handlers
function openDeleteModal(id, name) {
    deleteTargetId = id;
    document.getElementById('delete-product-name').innerText = name;
    new bootstrap.Modal(document.getElementById('deleteProductModal')).show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    if (!deleteTargetId) return;
    try {
        const res = await fetch(`api/products.php?id=${deleteTargetId}&user_id=<?= $currentUser['id'] ?>&platform=WEB`, {
            method: 'DELETE'
        });
        const result = await res.json();
        if (result.status === 'success') {
            window.location.reload();
        } else {
            alert('Error deleting: ' + result.message);
        }
    } catch (err) {
        alert('Failed to delete product: ' + err.message);
    }
});
</script>
</body>
</html>
