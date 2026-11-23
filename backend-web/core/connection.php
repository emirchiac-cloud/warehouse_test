<?php
// core/connection.php
// Centralized Database Connection using PDO for MySQL (inventory_system)

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_NAME')) define('DB_NAME', 'inventory_system');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

/**
 * Returns a shared PDO instance configured with error exceptions and associative fetches.
 */
function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    return $pdo;
}

// Global variable for backward compatibility
$conn = getDBConnection();
$pdo  = $conn;

/**
 * Computes base application URL for web assets and uploaded media.
 */
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    
    $parts = explode('/', trim(str_replace('\\', '/', $scriptDir), '/'));
    $baseParts = [];
    foreach ($parts as $part) {
        if ($part === 'api' || $part === 'config' || $part === 'core' || $part === 'controllers' || $part === 'data' || $part === 'ac') {
            break;
        }
        if ($part !== '') {
            $baseParts[] = $part;
        }
    }
    $basePath = !empty($baseParts) ? '/' . implode('/', $baseParts) : '';
    return rtrim($protocol . $host . $basePath, '/');
}
