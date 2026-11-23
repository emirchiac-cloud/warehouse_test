<?php
// ac/update_user_info.php
// Action to update user display information (name and email)

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../data/UserData.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$rawInput = file_get_contents('php://input');
$json = json_decode($rawInput, true);
$data = is_array($json) ? $json : $_POST;

$fullName = trim($data['full_name'] ?? '');
$email    = trim($data['email'] ?? '');

if (empty($fullName) || empty($email)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Full name and email are required.']);
    exit;
}

try {
    UserData::updateProfile($userId, $fullName, $email);
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email']     = $email;
    echo json_encode(['status' => 'success', 'message' => 'User info updated successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
