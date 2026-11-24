<?php
// backend-web/index.php
// Main Back-Office Management Portal Dashboard

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/php/controllers/ProductController.php';
require_once __DIR__ . '/php/controllers/CategoryController.php';
require_once __DIR__ . '/php/data/ProductData.php';

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
    <!-- Typeface -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --paper: #f2f2f2; --surface: #fff; --ink: #262626; --muted: #8a8a8a;
            --line: #ececec; --accent: #ef4a4a; --pink: #fde4e6; --ok: #1e9e5a; --ok-tint: #e2f6ea;
            --chip: #f3f3f3;
        }
        body { background: var(--paper); color: var(--ink); font-family: 'Figtree', system-ui, -apple-system, 'Segoe UI', sans-serif; font-variant-numeric: tabular-nums; }
        h1, h2, h3, h4, h5 { letter-spacing: -0.01em; }
        .text-muted { color: var(--muted) !important; }
        :focus-visible { outline: 3px solid rgba(239,74,74,.4); outline-offset: 2px; }

        .btn { border-radius: 10px; font-weight: 600; }
        .btn-primary { --bs-btn-bg: var(--ink); --bs-btn-border-color: var(--ink); --bs-btn-hover-bg: #000; --bs-btn-hover-border-color: #000; --bs-btn-active-bg: #000; --bs-btn-active-border-color: #000; }
        .btn-quiet { background: var(--chip); border: 1px solid transparent; color: var(--ink); }
        .btn-quiet:hover { background: #e9e9e9; color: var(--ink); }
        .form-control, .form-select, .input-group-text { border-radius: 10px; border-color: var(--line); background-color: var(--chip); }
        .form-control:focus, .form-select:focus { border-color: var(--ink); box-shadow: 0 0 0 .2rem rgba(38,38,38,.1); background-color: #fff; }

        .page-title { font-size: 1.7rem; font-weight: 600; margin: 0; }
        .crumbs { font-size: .95rem; color: var(--muted); }
        .crumbs a { color: var(--accent); text-decoration: none; }

        .card-x { background: var(--surface); border-radius: 16px; padding: 1.25rem 1.4rem; }
        .card-x h5 { font-size: 1.15rem; font-weight: 500; margin: 0 0 1rem; }

        .dash { display: grid; grid-template-columns: 250px minmax(0, 1fr) 300px; gap: 1rem; }
        .dash-stats { display: flex; flex-direction: column; gap: 1rem; }
        @media (max-width: 1199.98px) { .dash { grid-template-columns: 1fr 1fr; } .dash-stats { grid-column: 1 / -1; flex-direction: row; } .dash-stats > * { flex: 1; } }
        @media (max-width: 767.98px) { .dash { grid-template-columns: 1fr; } .dash-stats { flex-direction: column; } }

        .stat-label { color: var(--muted); font-size: .95rem; }
        .stat-value { font-size: 1.9rem; font-weight: 600; line-height: 1.25; margin-top: .3rem; }
        .stat-value small { font-size: .95rem; font-weight: 400; color: var(--muted); }
        .stat-alert .stat-value { color: var(--accent); }

        /* Stock by category */
        .cat-chart { display: flex; gap: .75rem; }
        .cat-col { flex: 1; text-align: center; min-width: 0; }
        .cat-name { font-size: .8rem; color: var(--muted); height: 2.5em; line-height: 1.25; overflow: hidden; }
        .cat-track { height: 140px; margin: .4rem 0; border-radius: 8px; background: linear-gradient(#fafafa, #e8e8e8); display: flex; align-items: flex-end; overflow: hidden; }
        .cat-fill { width: 100%; border-radius: 6px; }
        .hatch { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,.6) 0 3px, transparent 3px 8px); }
        .f0 { background-color: var(--accent); } .f1 { background-color: var(--accent); }
        .f2 { background-color: var(--ink); } .f3 { background-color: var(--ink); }
        .f4 { background-color: #8c8c8c; } .f5 { background-color: #8c8c8c; }
        .cat-pct { font-size: .85rem; font-weight: 700; }
        .cat-qty { font-size: .8rem; color: var(--muted); }

        /* Dark donut card */
        .card-dark { background: #2b2b2b; color: #fff; border-radius: 16px; padding: 1.25rem 1.4rem; display: flex; flex-direction: column; }
        .card-dark h5 { font-size: 1.15rem; font-weight: 500; margin: 0 0 1rem; }
        .donut { width: 190px; aspect-ratio: 1; border-radius: 50%; margin: .25rem auto 1rem; display: grid; place-items: center; }
        .donut-inner { width: 76%; aspect-ratio: 1; border-radius: 50%; background: #2b2b2b; display: grid; place-content: center; text-align: center; }
        .donut-inner small { color: #cfcfcf; }
        .donut-inner strong { font-size: 2.1rem; font-weight: 600; line-height: 1.1; }
        .donut-foot { display: flex; justify-content: space-between; margin-top: auto; }
        .donut-foot span { color: #cfcfcf; font-size: .9rem; display: block; }
        .donut-foot strong { font-weight: 600; }

        /* Table */
        .toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: 1rem; }
        .toolbar h5 { margin: 0; }
        .table { --bs-table-hover-bg: #fafafa; margin: 0; }
        .table thead th { background: transparent; color: var(--muted); font-size: .85rem; font-weight: 500; border-bottom: 1px solid var(--line); padding: .6rem .75rem; }
        .table td { padding: 1rem .75rem; border-color: var(--line); }
        .product-thumb { width: 46px; height: 46px; object-fit: cover; border-radius: 12px; background: var(--pink); }
        .sku { font-size: .8rem; color: var(--muted); }
        .cat-pill { font-size: .75rem; font-weight: 500; background: var(--chip); border-radius: 999px; padding: .1rem .6rem; }

        .row-low-stock > td { background: #fff6f6 !important; }
        .row-low-stock > td:first-child { box-shadow: inset 3px 0 0 var(--accent); }

        .stock-cell { min-width: 150px; }
        .meter { position: relative; height: 8px; background: var(--chip); border-radius: 999px; margin-top: .45rem; overflow: hidden; }
        .meter-fill { height: 100%; background: var(--ink); border-radius: 999px; transition: width .25s ease; }
        .meter-fill.is-low { background: var(--accent); }
        .meter::after { content: ""; position: absolute; left: 50%; top: 0; bottom: 0; width: 2px; background: rgba(255,255,255,.9); }
        .btn-qty { width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 9px; background: var(--chip); border: none; color: var(--ink); font-weight: 700; line-height: 1; }
        .btn-qty:hover { background: #e4e4e4; }
        .qty-val { min-width: 2.2ch; text-align: center; font-weight: 600; font-size: 1.05rem; }

        .status { display: inline-block; font-size: .85rem; font-weight: 500; padding: .25rem .8rem; border-radius: 999px; }
        .status-ok { color: var(--ok); background: var(--ok-tint); }
        .status-low { color: var(--accent); background: var(--pink); }

        .icon-btn { width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
        .icon-btn.danger:hover { background: var(--pink); color: var(--accent); }

        .modal-content { border: none; border-radius: 16px; overflow: hidden; }
        .modal-header.bg-primary, .modal-header.bg-dark { background: var(--ink) !important; }
        .form-label { font-size: .9rem; }
        @media (prefers-reduced-motion: reduce) { .meter-fill { transition: none; } }
    </style>
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>

<?php
// Figures for the category chart and the stock-health donut (based on the rows shown)
$byCat = [];
$lowShown = 0;
foreach ($products as $p) {
    $byCat[$p['category_name']] = ($byCat[$p['category_name']] ?? 0) + (int)$p['quantity'];
    if ((int)$p['quantity'] < (int)$p['min_stock_threshold']) $lowShown++;
}
arsort($byCat);
$byCat = array_slice($byCat, 0, 6, true);
$unitsShown = array_sum($byCat) ?: 1;
$maxCat = max($byCat ?: [1]) ?: 1;
$shown = count($products);
$healthy = $shown - $lowShown;
$lowPct = $shown ? round($lowShown / $shown * 100) : 0;
$healthyPct = $shown ? 100 - $lowPct : 0;
?>
<div class="container-fluid px-4 py-4">

    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
            <h1 class="page-title">Inventory</h1>
            <div class="crumbs"><a href="index.php">Dashboard</a> / Inventory</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="export_pdf.php<?= $filterCategory ? '?category_id=' . $filterCategory : '' ?>" class="btn btn-quiet btn-sm px-3" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i> PDF report</a>
            <a href="export_excel.php<?= $filterCategory ? '?category_id=' . $filterCategory : '' ?>" class="btn btn-quiet btn-sm px-3"><i class="bi bi-file-earmark-excel me-1"></i> Excel report</a>
            <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addProductModal"><i class="bi bi-plus-lg me-1"></i> Add product</button>
        </div>
    </div>

    <div class="dash mb-3">
        <div class="dash-stats">
            <div class="card-x">
                <div class="stat-label">Total SKUs</div>
                <div class="stat-value"><?= number_format($kpi['total_products']) ?> <small>products</small></div>
            </div>
            <div class="card-x">
                <div class="stat-label">Inventory value</div>
                <div class="stat-value" id="kpi-valuation" data-value="<?= (float)$kpi['total_valuation'] ?>">$<?= number_format($kpi['total_valuation'], 2) ?></div>
            </div>
            <div class="card-x stat-alert">
                <div class="stat-label">Needs restocking</div>
                <div class="stat-value"><span id="kpi-low" data-count="<?= (int)$kpi['low_stock_count'] ?>"><?= number_format($kpi['low_stock_count']) ?></span> <small>items</small></div>
            </div>
        </div>

        <div class="card-x">
            <h5>Stock by category</h5>
            <?php if ($byCat): ?>
            <div class="cat-chart">
                <?php $i = 0; foreach ($byCat as $name => $q): ?>
                    <div class="cat-col">
                        <div class="cat-name"><?= htmlspecialchars($name) ?></div>
                        <div class="cat-track"><div class="cat-fill f<?= $i ?> <?= $i % 2 ? 'hatch' : '' ?>" style="height: max(6%, <?= round($q / $maxCat * 100) ?>%)"></div></div>
                        <div class="cat-pct"><?= round($q / $unitsShown * 100) ?>%</div>
                        <div class="cat-qty"><?= number_format($q) ?> units</div>
                    </div>
                <?php $i++; endforeach; ?>
            </div>
            <?php else: ?>
                <div class="text-muted py-4">No stock to chart for these filters.</div>
            <?php endif; ?>
        </div>

        <div class="card-dark">
            <h5>Stock health</h5>
            <div class="donut" style="background: conic-gradient(var(--accent) 0 <?= $lowPct ?>%, #fff <?= $lowPct ?>% 100%);">
                <div class="donut-inner">
                    <small>Healthy</small>
                    <strong><?= $healthyPct ?>%</strong>
                </div>
            </div>
            <div class="donut-foot">
                <div><span>Healthy</span><strong><?= $healthy ?> items</strong></div>
                <div class="text-end"><span>Low stock</span><strong><?= $lowShown ?> items</strong></div>
            </div>
        </div>
    </div>

    <div class="card-x">
        <form method="GET" action="index.php" class="toolbar">
            <h5>Inventory items <span class="text-muted fs-6 fw-normal ms-1"><?= $shown ?> shown</span></h5>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <div class="input-group input-group-sm" style="width: 260px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search name, SKU or description" value="<?= htmlspecialchars($search) ?>" aria-label="Search products">
                </div>
                <select name="category_id" class="form-select form-select-sm" style="width: 180px;" onchange="this.form.submit()" aria-label="Filter by category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filterCategory === (int)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm px-3">Search</button>
                <?php if ($filterCategory || $search): ?><a href="index.php" class="btn btn-quiet btn-sm">Clear</a><?php endif; ?>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Unit price</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Value</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No products match these filters. Try a different search or clear the filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p):
                            $qty = (int)$p['quantity'];
                            $min = (int)$p['min_stock_threshold'];
                            $isLow = ($qty < $min);
                            $subtotal = (float)$p['unit_price'] * $qty;
                            $pct = min(100, (int)round($qty / max(1, $min * 2) * 100));
                            $imgSrc = $baseUrl . '/' . ltrim($p['image_path'] ?: 'uploads/products/placeholder.png', '/');
                        ?>
                            <tr class="<?= $isLow ? 'row-low-stock' : '' ?>" id="product-row-<?= $p['id'] ?>"
                                data-price="<?= (float)$p['unit_price'] ?>" data-min="<?= $min ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="" class="product-thumb me-3">
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                <span class="sku"><?= htmlspecialchars($p['sku']) ?></span>
                                                <span class="cat-pill"><?= htmlspecialchars($p['category_name']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">$<?= number_format($p['unit_price'], 2) ?></td>
                                <td class="stock-cell text-center">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <button class="btn-qty" onclick="adjustStock(<?= (int)$p['id'] ?>, -1)" title="Remove 1" aria-label="Remove 1 from stock">&minus;</button>
                                        <span class="qty-val" id="qty-val-<?= $p['id'] ?>"><?= $qty ?></span>
                                        <button class="btn-qty" onclick="adjustStock(<?= (int)$p['id'] ?>, 1)" title="Add 1" aria-label="Add 1 to stock">+</button>
                                    </div>
                                    <div class="meter" title="Minimum: <?= $min ?>">
                                        <div class="meter-fill <?= $isLow ? 'is-low' : '' ?>" id="meter-<?= $p['id'] ?>" style="width: <?= $pct ?>%"></div>
                                    </div>
                                    <div class="text-muted small mt-1">min. <?= $min ?></div>
                                </td>
                                <td class="text-center" id="status-badge-<?= $p['id'] ?>">
                                    <?php if ($isLow): ?>
                                        <span class="status status-low">Low stock</span>
                                    <?php else: ?>
                                        <span class="status status-ok">In stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold" id="subtotal-<?= $p['id'] ?>">$<?= number_format($subtotal, 2) ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button class="btn btn-quiet icon-btn" onclick="openEditModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)" title="Edit product" aria-label="Edit <?= htmlspecialchars($p['name']) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-quiet icon-btn danger" onclick="openDeleteModal(<?= (int)$p['id'] ?>, <?= htmlspecialchars(json_encode($p['name']), ENT_QUOTES) ?>)" title="Delete product" aria-label="Delete <?= htmlspecialchars($p['name']) ?>">
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

<!--  MODALS (Add, Edit, Delete) -->

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

function money(n) {
    return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

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
            const row = document.getElementById(`product-row-${productId}`);
            const price = parseFloat(row.dataset.price);
            const min = parseInt(row.dataset.min, 10);
            const oldQty = parseInt(document.getElementById(`qty-val-${productId}`).innerText, 10);
            const newQty = parseInt(data.quantity, 10);
            const wasLow = row.classList.contains('row-low-stock');
            const isLow = !!data.is_low_stock;

            document.getElementById(`qty-val-${productId}`).innerText = newQty;
            document.getElementById(`subtotal-${productId}`).innerText = money(price * newQty);

            const meter = document.getElementById(`meter-${productId}`);
            meter.style.width = Math.min(100, Math.round(newQty / Math.max(1, min * 2) * 100)) + '%';
            meter.classList.toggle('is-low', isLow);

            document.getElementById(`status-badge-${productId}`).innerHTML = isLow
                ? '<span class="status status-low">Low stock</span>'
                : '<span class="status status-ok">In stock</span>';
            row.classList.toggle('row-low-stock', isLow);

            // Keep the summary cards in step with the change
            const val = document.getElementById('kpi-valuation');
            val.dataset.value = parseFloat(val.dataset.value) + price * (newQty - oldQty);
            val.innerText = money(parseFloat(val.dataset.value));
            if (wasLow !== isLow) {
                const low = document.getElementById('kpi-low');
                low.dataset.count = Math.max(0, parseInt(low.dataset.count, 10) + (isLow ? 1 : -1));
                low.innerText = Number(low.dataset.count).toLocaleString();
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