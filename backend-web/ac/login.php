<?php
// ac/login.php
// Action handling user authentication for web forms and REST clients

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../controllers/AuthController.php';

// Detect JSON or form payload
$rawInput = file_get_contents('php://input');
$json = json_decode($rawInput, true);
$data = is_array($json) ? $json : $_POST;

$identifier = $data['email'] ?? ($data['username'] ?? ($data['user'] ?? ''));
$password   = $data['password'] ?? ($data['pwd'] ?? '');
$isJsonReq  = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) || is_array($json);

try {
    $user = AuthController::login((string)$identifier, (string)$password);

    if ($isJsonReq) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(200);
        echo json_encode([
            'status'    => 'success',
            'id'        => $user['id'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role'],
            'message'   => 'Login successful.'
        ]);
        exit;
    } else {
        header("Location: ../index.php");
        exit;
    }
} catch (InvalidArgumentException $e) {
    if ($isJsonReq) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    header("Location: ../login.php?error=" . urlencode($e->getMessage()));
    exit;
} catch (RuntimeException $e) {
    if ($isJsonReq) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    header("Location: ../login.php?error=" . urlencode($e->getMessage()));
    exit;
} catch (Exception $e) {
    if ($isJsonReq) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    header("Location: ../login.php?error=" . urlencode("Authentication error occurred."));
    exit;
}
