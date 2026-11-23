<?php
// api/login.php
// REST API endpoint for user authentication, delegating to AuthController

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method Not Allowed. Only POST is accepted.'
    ]);
    exit;
}

require_once __DIR__ . '/../php/controllers/AuthController.php';

try {
    $rawInput = file_get_contents('php://input');
    $json = json_decode($rawInput, true);
    $data = is_array($json) ? $json : $_POST;

    $identifier = $data['email'] ?? ($data['username'] ?? ($data['user'] ?? ''));
    $password   = $data['password'] ?? ($data['pwd'] ?? '');

    $user = AuthController::login((string)$identifier, (string)$password);

    http_response_code(200);
    echo json_encode([
        'status'    => 'success',
        'id'        => $user['id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role'      => $user['role'],
        'message'   => 'Login successful.'
    ]);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (RuntimeException $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Authentication error: ' . $e->getMessage()]);
}
