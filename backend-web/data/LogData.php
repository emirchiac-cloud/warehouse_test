<?php
// data/LogData.php
// Data access layer for stock_logs audit trail table

require_once __DIR__ . '/../core/connection.php';

class LogData {

    /**
     * Inserts an audit log entry into stock_logs table.
     */
    public static function record(int $productId, int $userId, string $actionType, int $quantityChanged, string $platform = 'WEB'): bool {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO stock_logs (product_id, user_id, action_type, quantity_changed, platform, created_at)
                               VALUES (?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$productId, $userId, $actionType, $quantityChanged, strtoupper($platform)]);
    }

    /**
     * Retrieves audit log history with joined product name and user details.
     */
    public static function getAll(int $limit = 200, string $actionType = '', string $platform = ''): array {
        $pdo = getDBConnection();
        $sql = "SELECT l.id, l.product_id, l.user_id, l.action_type, l.quantity_changed, l.platform, l.created_at,
                       COALESCE(p.name, '[Deleted Product]') AS product_name,
                       p.sku,
                       COALESCE(u.full_name, 'System') AS user_name,
                       u.role AS user_role
                FROM stock_logs l
                LEFT JOIN products p ON l.product_id = p.id
                LEFT JOIN users u ON l.user_id = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($actionType)) {
            $sql .= " AND l.action_type = ?";
            $params[] = strtoupper($actionType);
        }

        if (!empty($platform)) {
            $sql .= " AND l.platform = ?";
            $params[] = strtoupper($platform);
        }

        $sql .= " ORDER BY l.id DESC LIMIT " . (int)$limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
