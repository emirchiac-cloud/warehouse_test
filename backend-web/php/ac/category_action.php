<?php
// ac/category_action.php
// Action handling category addition and deletion from Back-Office web views

require_once __DIR__ . '/../controllers/CategoryController.php';

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$isJson = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

try {
    if ($action === 'create') {
        $name = $_POST['name'] ?? '';
        $desc = $_POST['description'] ?? '';
        $cat = CategoryController::store($name, $desc);
        if ($isJson) {
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode(['status' => 'success', 'data' => $cat, 'message' => 'Category created.']);
            exit;
        }
        header("Location: ../../categories.php?msg=created");
        exit;
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
        CategoryController::destroy($id);
        if ($isJson) {
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode(['status' => 'success', 'message' => 'Category deleted.']);
            exit;
        }
        header("Location: ../../categories.php?msg=deleted");
        exit;
    } else {
        throw new InvalidArgumentException("Invalid action.");
    }
} catch (Exception $e) {
    if ($isJson) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    header("Location: ../../categories.php?error=" . urlencode($e->getMessage()));
    exit;
}
