<?php
// backend-web/login.php
// Back-Office Web Portal Login Page

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already authenticated
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/php/controllers/AuthController.php';

$error = $_GET['error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = trim($_POST['email'] ?? ($_POST['username'] ?? ''));
    $password  = trim($_POST['password'] ?? '');

    try {
        AuthController::login($userInput, $password);
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Unified Inventory Management</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d1b2a 0%, #1b263b 50%, #415a77 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 440px;
            width: 100%;
        }
        .login-header {
            background: #1b263b;
            color: #ffffff;
            padding: 30px 24px;
            text-align: center;
        }
        .login-header h4 {
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .login-header p {
            font-size: 0.85rem;
            color: #778da9;
            margin-bottom: 0;
        }
        .login-body {
            padding: 30px 28px;
        }
        .btn-primary-custom {
            background: #1b263b;
            border-color: #1b263b;
            padding: 10px;
            font-weight: 600;
        }
        .btn-primary-custom:hover {
            background: #0d1b2a;
            border-color: #0d1b2a;
        }
        .demo-creds {
            background: #f8f9fa;
            border: 1px dashed #ced4da;
            border-radius: 8px;
            padding: 12px;
            font-size: 0.82rem;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <i class="bi bi-boxes fs-1 text-info"></i>
        <h4 class="mt-2">Centro Escolar México</h4>
        <p>Unified Inventory Management System</p>
    </div>
    <div class="login-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                <div><?= htmlspecialchars($error) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <div class="mb-3">
                <label for="email" class="form-label text-secondary fw-semibold">Email or Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="text" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? 'admin@warehouse.com') ?>" placeholder="admin@warehouse.com or admin" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label text-secondary fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" value="admin123" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary-custom text-white w-100 rounded-3 shadow-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Portal
            </button>
        </form>

        <div class="demo-creds text-muted">
            <div class="fw-bold text-dark mb-1"><i class="bi bi-key-fill text-warning"></i> Demo Accounts (inventory_system.users):</div>
            <div><strong>Admin:</strong> <code>admin@warehouse.com</code> / <code>admin123</code></div>
            <div><strong>Staff:</strong> <code>staff@warehouse.com</code> / <code>staff123</code></div>
            <div><strong>Staff:</strong> <code>maria@warehouse.com</code> / <code>staff123</code></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
