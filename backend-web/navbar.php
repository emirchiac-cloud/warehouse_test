<?php
// backend-web/navbar.php
$activePage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center fw-bold" href="index.php">
            <i class="bi bi-boxes text-info fs-3 me-2"></i>
            <div>
                <span class="fs-5">CEMJC Warehouse</span>
                <span class="badge bg-secondary ms-2" style="font-size: 0.65rem; font-weight: normal;">Unified System</span>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'index.php' ? 'active fw-bold text-white' : '' ?>" href="index.php">
                        <i class="bi bi-speedometer2 me-1"></i> Inventory Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'categories.php' ? 'active fw-bold text-white' : '' ?>" href="categories.php">
                        <i class="bi bi-tags me-1"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'audit_logs.php' ? 'active fw-bold text-white' : '' ?>" href="audit_logs.php">
                        <i class="bi bi-journal-text me-1"></i> Stock Audit Logs
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center text-white">
                <div class="me-3 text-end d-none d-md-block">
                    <div class="fw-semibold small"><?= htmlspecialchars($currentUser['full_name']) ?></div>
                    <span class="badge <?= $currentUser['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?> text-uppercase" style="font-size: 0.65rem;">
                        <?= htmlspecialchars($currentUser['role']) ?>
                    </span>
                </div>
                <a href="php/ac/logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>
    </div>
</nav>
