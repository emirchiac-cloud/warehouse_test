<?php
// controllers/LogController.php
// Controller for stock logs and audit history trail

require_once __DIR__ . '/../data/LogData.php';

class LogController {

    /**
     * Retrieves audit trail history with optional action_type and platform filters.
     */
    public static function index(int $limit = 200, string $actionType = '', string $platform = ''): array {
        return LogData::getAll($limit, $actionType, $platform);
    }

    /**
     * Records an audit log entry.
     */
    public static function log(int $productId, int $userId, string $actionType, int $quantityChanged, string $platform = 'WEB'): bool {
        return LogData::record($productId, $userId, $actionType, $quantityChanged, $platform);
    }
}
