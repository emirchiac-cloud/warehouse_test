<?php
// api/logs.php
// REST API endpoint for stock logs audit trail, delegating to LogController

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../controllers/LogController.php';

try {
    $platform   = isset($_GET['platform']) ? trim($_GET['platform']) : '';
    $actionType = isset($_GET['action_type']) ? trim($_GET['action_type']) : '';
    $limit      = isset($_GET['limit']) ? min((int)$_GET['limit'], 200) : 50;

    $logs = LogController::index($limit, $actionType, $platform);

    http_response_code(200);
    echo json_encode($logs);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error retrieving audit logs: ' . $e->getMessage()]);
}
