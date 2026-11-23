<?php
// backend-web/api/logs.php
// REST API for Stock Logs & Audit Trail

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';

try {
    $pdo = getDBConnection();

    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
    $platform  = isset($_GET['platform']) ? trim($_GET['platform']) : null;
    $limit     = isset($_GET['limit']) ? min((int)$_GET['limit'], 200) : 50;

    $sql = "SELECT sl.*, p.name AS product_name, p.sku AS product_sku, u.full_name AS user_name, u.email AS user_email 
            FROM stock_logs sl
            LEFT JOIN products p ON sl.product_id = p.id
            LEFT JOIN users u ON sl.user_id = u.id
            WHERE 1=1";
    $params = [];

    if ($productId !== null && $productId > 0) {
        $sql .= " AND sl.product_id = ?";
        $params[] = $productId;
    }

    if (!empty($platform) && in_array(strtoupper($platform), ['WEB', 'MOBILE'])) {
        $sql .= " AND sl.platform = ?";
        $params[] = strtoupper($platform);
    }

    $sql .= " ORDER BY sl.id DESC LIMIT " . $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode($logs);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error retrieving audit logs: ' . $e->getMessage()]);
}
