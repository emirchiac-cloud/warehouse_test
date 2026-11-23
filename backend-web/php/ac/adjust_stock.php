<?php
// ac/adjust_stock.php
// Action handling quick stock increments and decrements via AJAX

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../controllers/ProductController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? 1;

$rawInput = file_get_contents('php://input');
$json = json_decode($rawInput, true);
$data = is_array($json) ? $json : $_POST;

$productId = (int)($data['id'] ?? ($data['product_id'] ?? 0));
$delta     = (int)($data['delta'] ?? 0);

if ($productId <= 0 || $delta === 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid product ID and non-zero delta are required.']);
    exit;
}

try {
    $result = ProductController::adjustStock($productId, $delta, $userId, 'WEB');
    echo json_encode([
        'status'              => 'success',
        'id'                  => $result['id'],
        'name'                => $result['name'],
        'new_quantity'        => $result['new_quantity'],
        'is_low_stock'        => $result['is_low_stock'],
        'min_stock_threshold' => $result['min_stock_threshold']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
