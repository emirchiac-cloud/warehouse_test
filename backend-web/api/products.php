<?php
// api/products.php
// REST API endpoint for products, delegating to ProductController

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../php/controllers/ProductController.php';

// Detect HTTP method including _method override for multipart PUT/DELETE
$rawMethod = $_SERVER['REQUEST_METHOD'];
$method = $rawMethod;
if ($rawMethod === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
} elseif ($rawMethod === 'POST' && isset($_GET['_method'])) {
    $method = strtoupper($_GET['_method']);
}

try {
    // ----------------------------------------------------
    // 1. GET /api/products.php
    // ----------------------------------------------------
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $product = ProductController::show((int)$_GET['id']);
            if (!$product) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Product not found']);
                exit;
            }
            http_response_code(200);
            echo json_encode($product);
            exit;
        }

        $filters = [
            'category_id' => isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null,
            'search'      => isset($_GET['search']) ? trim($_GET['search']) : null,
            'sort'        => isset($_GET['sort']) ? trim($_GET['sort']) : 'low_stock_first'
        ];

        $products = ProductController::index($filters);
        http_response_code(200);
        echo json_encode($products);
        exit;
    }

    // ----------------------------------------------------
    // 2. POST /api/products.php (Create New Product)
    // ----------------------------------------------------
    if ($method === 'POST') {
        $userId = (int)($_POST['user_id'] ?? 1);
        $product = ProductController::store($_POST, $_FILES, $userId, 'MOBILE');

        http_response_code(201);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Product created successfully',
            'data'    => $product
        ]);
        exit;
    }

    // ----------------------------------------------------
    // 3. PUT /api/products.php (Update Details or Adjust Stock)
    // ----------------------------------------------------
    if ($method === 'PUT') {
        $input = [];
        $rawInput = file_get_contents('php://input');
        $json = json_decode($rawInput, true);
        if (is_array($json)) {
            $input = $json;
        } else {
            parse_str($rawInput, $input);
        }
        if (empty($input) && !empty($_POST)) {
            $input = $_POST;
        }

        $productId = (int)($input['id'] ?? ($_GET['id'] ?? 0));
        if ($productId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Product ID is required']);
            exit;
        }

        $userId = (int)($input['user_id'] ?? 1);

        // Quick Stock Adjustment check
        if (isset($input['delta']) || isset($input['stock_delta']) || isset($input['adjust_type'])) {
            $delta = 0;
            if (isset($input['delta'])) {
                $delta = (int)$input['delta'];
            } elseif (isset($input['stock_delta'])) {
                $delta = (int)$input['stock_delta'];
            } elseif (isset($input['adjust_type'])) {
                $delta = ($input['adjust_type'] === 'decrement') ? -1 : 1;
            }

            $platform = $input['platform'] ?? 'MOBILE';
            $res = ProductController::adjustStock($productId, $delta, $userId, $platform);
            http_response_code(200);
            echo json_encode([
                'status'              => 'success',
                'message'             => 'Stock adjusted successfully',
                'id'                  => $res['id'],
                'name'                => $res['name'],
                'quantity'            => $res['new_quantity'],
                'new_quantity'        => $res['new_quantity'],
                'min_stock_threshold' => $res['min_stock_threshold'],
                'is_low_stock'        => $res['is_low_stock']
            ]);
            exit;
        }

        // Full Details Update
        $updated = ProductController::update($productId, $input, $_FILES, $userId, 'MOBILE');
        http_response_code(200);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Product updated successfully',
            'data'    => $updated
        ]);
        exit;
    }

    // ----------------------------------------------------
    // 4. DELETE /api/products.php?id=X
    // ----------------------------------------------------
    if ($method === 'DELETE') {
        $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($productId <= 0) {
            $input = json_decode(file_get_contents('php://input'), true);
            $productId = (int)($input['id'] ?? 0);
        }

        if ($productId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Product ID is required']);
            exit;
        }

        $userId = (int)($_GET['user_id'] ?? 1);
        ProductController::destroy($productId, $userId, 'MOBILE');

        http_response_code(200);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Product deleted successfully'
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (RuntimeException $e) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
