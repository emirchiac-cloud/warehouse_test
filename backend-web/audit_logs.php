<?php
// backend-web/audit_logs.php
// Stock Logs Audit History View

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/php/controllers/LogController.php';

$filterPlatform = $_GET['platform'] ?? '';
$filterAction   = $_GET['action_type'] ?? '';

$logs = LogController::index(150, $filterAction, $filterPlatform);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Audit Trail - Back-Office Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .table-card { border: none; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,0.06); }
        .platform-mobile { background-color: #0d6efd; color: #fff; font-weight: bold; }
        .platform-web { background-color: #6f42c1; color: #fff; font-weight: bold; }
    </style>
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-text text-primary me-2"></i>Stock Audit Trail</h4>
            <small class="text-muted">Real-time immutable log of all inventory modifications from Web Portal and Native Android</small>
        </div>
    </div>

    <!-- Filters -->
    <div class="card table-card bg-white p-3 mb-4">
        <form method="GET" action="audit_logs.php" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="platform" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Platforms (WEB + MOBILE)</option>
                    <option value="WEB" <?= $filterPlatform === 'WEB' ? 'selected' : '' ?>>WEB (Back-Office Portal)</option>
                    <option value="MOBILE" <?= $filterPlatform === 'MOBILE' ? 'selected' : '' ?>>MOBILE (Android Floor App)</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="action_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Action Types</option>
                    <option value="CREATED" <?= $filterAction === 'CREATED' ? 'selected' : '' ?>>CREATED</option>
                    <option value="STOCK_IN" <?= $filterAction === 'STOCK_IN' ? 'selected' : '' ?>>STOCK_IN (+)</option>
                    <option value="STOCK_OUT" <?= $filterAction === 'STOCK_OUT' ? 'selected' : '' ?>>STOCK_OUT (-)</option>
                    <option value="UPDATED" <?= $filterAction === 'UPDATED' ? 'selected' : '' ?>>UPDATED</option>
                    <option value="DELETED" <?= $filterAction === 'DELETED' ? 'selected' : '' ?>>DELETED</option>
                </select>
            </div>
            <div class="col-md-4">
                <a href="audit_logs.php" class="btn btn-outline-secondary btn-sm">Reset Filters</a>
            </div>
        </form>
    </div>

    <div class="card table-card bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary text-uppercase small">
                    <tr>
                        <th class="ps-4">Log #</th>
                        <th>Timestamp</th>
                        <th>Product</th>
                        <th>User</th>
                        <th class="text-center">Platform</th>
                        <th class="text-center">Action</th>
                        <th class="pe-4 text-end">Quantity Change</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No audit logs found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="ps-4 text-muted small">#<?= $log['id'] ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($log['created_at']) ?></small></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($log['product_name'] ?? '[Deleted Product]') ?></div>
                                    <?php if (!empty($log['product_sku'])): ?>
                                        <small class="text-muted"><code><?= htmlspecialchars($log['product_sku']) ?></code></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></div>
                                    <small class="text-muted text-uppercase" style="font-size:0.7rem;"><?= htmlspecialchars($log['user_role'] ?? 'User') ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if ($log['platform'] === 'MOBILE'): ?>
                                        <span class="badge platform-mobile rounded-pill px-3 py-1">
                                            <i class="bi bi-phone me-1"></i> MOBILE
                                        </span>
                                    <?php else: ?>
                                        <span class="badge platform-web rounded-pill px-3 py-1">
                                            <i class="bi bi-laptop me-1"></i> WEB
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                        $badgeClass = 'bg-secondary';
                                        if ($log['action_type'] === 'STOCK_IN') $badgeClass = 'bg-success';
                                        elseif ($log['action_type'] === 'STOCK_OUT') $badgeClass = 'bg-warning text-dark';
                                        elseif ($log['action_type'] === 'CREATED') $badgeClass = 'bg-primary';
                                        elseif ($log['action_type'] === 'DELETED') $badgeClass = 'bg-danger';
                                    ?>
                                    <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-1">
                                        <?= htmlspecialchars($log['action_type']) ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end fw-bold">
                                    <?php if ((int)$log['quantity_changed'] > 0): ?>
                                        <span class="text-success">+<?= (int)$log['quantity_changed'] ?></span>
                                    <?php elseif ((int)$log['quantity_changed'] < 0): ?>
                                        <span class="text-danger"><?= (int)$log['quantity_changed'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
