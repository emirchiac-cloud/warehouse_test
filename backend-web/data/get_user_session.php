<?php
// data/get_user_session.php
// Returns currently active user session data in JSON format

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'authenticated' => true,
        'user' => [
            'id'        => (int)$_SESSION['user_id'],
            'full_name' => $_SESSION['full_name'] ?? '',
            'email'     => $_SESSION['email'] ?? '',
            'role'      => $_SESSION['role'] ?? 'staff'
        ]
    ]);
} else {
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'authenticated' => false,
        'user' => null
    ]);
}
