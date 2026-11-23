<?php
// backend-web/auth_check.php
// Session guard for protected portal pages

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) && isset($_SESSION['id'])) {
    $_SESSION['user_id'] = (int)$_SESSION['id'];
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$currentUser = [
    'id'        => (int)$_SESSION['user_id'],
    'full_name' => $_SESSION['full_name'] ?? ($_SESSION['user'] ?? 'Warehouse Staff'),
    'email'     => $_SESSION['email'] ?? '',
    'role'      => $_SESSION['role'] ?? 'staff'
];
