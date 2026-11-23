<?php
// ac/logout.php
// Action clearing user session and redirecting to login

require_once __DIR__ . '/../controllers/AuthController.php';

AuthController::logout();

if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(['status' => 'success', 'message' => 'Logged out successfully.']);
    exit;
}

header("Location: ../login.php");
exit;
