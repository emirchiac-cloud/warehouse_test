<?php
// ac/product_action.php
// Action handling product creation, update, and deletion from Back-Office web views

require_once __DIR__ . '/../controllers/ProductController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? 1;

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$isJson = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

try {
    if ($action === 'create') {
        $product = ProductController::store($_POST, $_FILES, $userId, 'WEB');
        if ($isJson) {
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode(['status' => 'success', 'data' => $product, 'message' => 'Product created successfully.']);
            exit;
        }
        header("Location: ../index.php?msg=created");
        exit;
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $product = ProductController::update($id, $_POST, $_FILES, $userId, 'WEB');
        if ($isJson) {
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode(['status' => 'success', 'data' => $product, 'message' => 'Product updated successfully.']);
            exit;
        }
        header("Location: ../index.php?msg=updated");
        exit;
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
        ProductController::destroy($id, $userId, 'WEB');
        if ($isJson) {
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode(['status' => 'success', 'message' => 'Product deleted successfully.']);
            exit;
        }
        header("Location: ../index.php?msg=deleted");
        exit;
    } else {
        throw new InvalidArgumentException("Invalid or missing action parameter.");
    }
} catch (Exception $e) {
    if ($isJson) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    header("Location: ../index.php?error=" . urlencode($e->getMessage()));
    exit;
}
