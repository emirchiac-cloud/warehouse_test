<?php
// backend-web/api/products.php
// RESTful Endpoints for Products & Real-time Stock Updates

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Detect HTTP method including _method override for PUT/DELETE in multipart
$rawMethod = $_SERVER['REQUEST_METHOD'];
$method = $rawMethod;
if ($rawMethod === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
} elseif ($rawMethod === 'POST' && isset($_GET['_method'])) {
    $method = strtoupper($_GET['_method']);
}

$pdo = getDBConnection();
$baseUrl = getBaseUrl();
$uploadDir = __DIR__ . '/../uploads/products/';

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

try {
    // ----------------------------------------------------
    // 1. GET /api/products.php
    // ----------------------------------------------------
    if ($method === 'GET') {
        $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
        $search = isset($_GET['search']) ? trim($_GET['search']) : null;
        $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'low_stock_first';

        $sql = "SELECT p.*, c.name AS category_name 
                FROM products p 
                INNER JOIN categories c ON p.category_id = c.id 
                WHERE 1=1";
        $params = [];

        if ($categoryId !== null && $categoryId > 0) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }

        if (!empty($search)) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%" . $search . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Sorting: low-stock first or newest
        if ($sort === 'newest') {
            $sql .= " ORDER BY p.id DESC";
        } else {
            // Low-stock first, then id desc
            $sql .= " ORDER BY (p.quantity < p.min_stock_threshold) DESC, p.id DESC";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        // Format items with boolean flag and absolute image URL
        $formatted = [];
        foreach ($products as $row) {
            $isLowStock = ((int)$row['quantity'] < (int)$row['min_stock_threshold']);
            $imgPath = $row['image_path'] ?: 'uploads/products/placeholder.png';
            $imageUrl = $baseUrl . '/' . ltrim($imgPath, '/');

            $formatted[] = [
                'id'                  => (int)$row['id'],
                'category_id'         => (int)$row['category_id'],
                'category_name'       => $row['category_name'],
                'sku'                 => $row['sku'],
                'name'                => $row['name'],
                'description'         => $row['description'] ?? '',
                'unit_price'          => (float)$row['unit_price'],
                'quantity'            => (int)$row['quantity'],
                'min_stock_threshold' => (int)$row['min_stock_threshold'],
                'image_path'          => $imgPath,
                'image_url'           => $imageUrl,
                'is_low_stock'        => $isLowStock,
                'updated_at'          => $row['updated_at']
            ];
        }

        http_response_code(200);
        echo json_encode($formatted);
        exit;
    }

    // ----------------------------------------------------
    // 2. POST /api/products.php (Create New Product)
    // ----------------------------------------------------
    if ($method === 'POST') {
        $name        = trim($_POST['name'] ?? '');
        $sku         = trim($_POST['sku'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $unitPrice   = (float)($_POST['unit_price'] ?? 0.0);
        $quantity    = (int)($_POST['quantity'] ?? 0);
        $minThreshold= (int)($_POST['min_stock_threshold'] ?? 5);
        $userId      = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 1;
        $platform    = (isset($_POST['platform']) && strtoupper($_POST['platform']) === 'MOBILE') ? 'MOBILE' : 'WEB';

        if (empty($name) || empty($sku) || $categoryId <= 0) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Product Name, SKU, and Category are required.'
            ]);
            exit;
        }

        // Check SKU uniqueness
        $checkStmt = $pdo->prepare("SELECT id FROM products WHERE sku = ? LIMIT 1");
        $checkStmt->execute([$sku]);
        if ($checkStmt->fetch()) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'A product with this SKU already exists.'
            ]);
            exit;
        }

        // Handle Image Upload
        $savedImagePath = 'uploads/products/placeholder.png';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($file['size'] > $maxSize) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Image size exceeds maximum limit of 5MB.'
                ]);
                exit;
            }

            if (!in_array($ext, $allowedExtensions)) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Invalid image format. Allowed formats: JPG, PNG, WEBP.'
                ]);
                exit;
            }

            $fileName = 'item_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destPath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $savedImagePath = 'uploads/products/' . $fileName;
            }
        }

        // Insert into database
        $pdo->beginTransaction();

        $insertSql = "INSERT INTO products (category_id, sku, name, description, unit_price, quantity, min_stock_threshold, image_path) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([
            $categoryId,
            $sku,
            $name,
            $description,
            $unitPrice,
            $quantity,
            $minThreshold,
            $savedImagePath
        ]);
        $productId = (int)$pdo->lastInsertId();

        // Audit Trail: Record 'CREATED' in stock_logs
        $logSql = "INSERT INTO stock_logs (product_id, user_id, action_type, quantity_changed, platform) 
                   VALUES (?, ?, 'CREATED', ?, ?)";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute([$productId, $userId, $quantity, $platform]);

        $pdo->commit();

        $isLowStock = ($quantity < $minThreshold);
        http_response_code(201);
        echo json_encode([
            'status'       => 'success',
            'id'           => $productId,
            'name'         => $name,
            'sku'          => $sku,
            'quantity'     => $quantity,
            'image_path'   => $savedImagePath,
            'image_url'    => $baseUrl . '/' . ltrim($savedImagePath, '/'),
            'is_low_stock' => $isLowStock,
            'message'      => 'Product created successfully.'
        ]);
        exit;
    }

    // ----------------------------------------------------
    // 3. PUT /api/products.php (Update Details or Adjust Stock)
    // ----------------------------------------------------
    if ($method === 'PUT') {
        // Parse incoming body (supports JSON, x-www-form-urlencoded, or multipart with _method=PUT)
        $input = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
        } elseif (!empty($_POST)) {
            $input = $_POST;
        } else {
            parse_str(file_get_contents('php://input'), $input);
        }

        $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Product ID is required for update.'
            ]);
            exit;
        }

        // Fetch current product
        $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p INNER JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch();

        if (!$current) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Product not found.'
            ]);
            exit;
        }

        $userId   = isset($input['user_id']) ? (int)$input['user_id'] : 1;
        $platform = (isset($input['platform']) && strtoupper($input['platform']) === 'MOBILE') ? 'MOBILE' : 'WEB';

        $pdo->beginTransaction();

        // Check if this is a Quick Stock Adjustment (+1, -1, or adjust_type)
        if (isset($input['stock_delta']) || isset($input['action_type']) && in_array($input['action_type'], ['STOCK_IN', 'STOCK_OUT'])) {
            $delta = 0;
            $actionType = 'UPDATED';

            if (isset($input['stock_delta'])) {
                $delta = (int)$input['stock_delta'];
                $actionType = ($delta >= 0) ? 'STOCK_IN' : 'STOCK_OUT';
            } elseif ($input['action_type'] === 'STOCK_IN') {
                $qty = isset($input['amount']) ? (int)$input['amount'] : 1;
                $delta = abs($qty);
                $actionType = 'STOCK_IN';
            } elseif ($input['action_type'] === 'STOCK_OUT') {
                $qty = isset($input['amount']) ? (int)$input['amount'] : 1;
                $delta = -abs($qty);
                $actionType = 'STOCK_OUT';
            }

            $newQuantity = (int)$current['quantity'] + $delta;
            if ($newQuantity < 0) {
                $newQuantity = 0;
                $delta = 0 - (int)$current['quantity'];
            }

            $upStmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $upStmt->execute([$newQuantity, $id]);

            // Log adjustment
            $logStmt = $pdo->prepare("INSERT INTO stock_logs (product_id, user_id, action_type, quantity_changed, platform) VALUES (?, ?, ?, ?, ?)");
            $logStmt->execute([$id, $userId, $actionType, $delta, $platform]);

            $pdo->commit();

            $isLowStock = ($newQuantity < (int)$current['min_stock_threshold']);
            $imgPath = $current['image_path'] ?: 'uploads/products/placeholder.png';

            http_response_code(200);
            echo json_encode([
                'status'              => 'success',
                'id'                  => $id,
                'name'                => $current['name'],
                'sku'                 => $current['sku'],
                'category_name'       => $current['category_name'],
                'quantity'            => $newQuantity,
                'min_stock_threshold' => (int)$current['min_stock_threshold'],
                'quantity_changed'    => $delta,
                'action_type'         => $actionType,
                'image_path'          => $imgPath,
                'image_url'           => $baseUrl . '/' . ltrim($imgPath, '/'),
                'is_low_stock'        => $isLowStock,
                'message'             => 'Stock updated successfully.'
            ]);
            exit;
        }

        // Full Details Update
        $name        = isset($input['name']) ? trim($input['name']) : $current['name'];
        $sku         = isset($input['sku']) ? trim($input['sku']) : $current['sku'];
        $categoryId  = isset($input['category_id']) ? (int)$input['category_id'] : (int)$current['category_id'];
        $description = isset($input['description']) ? trim($input['description']) : $current['description'];
        $unitPrice   = isset($input['unit_price']) ? (float)$input['unit_price'] : (float)$current['unit_price'];
        $minThreshold= isset($input['min_stock_threshold']) ? (int)$input['min_stock_threshold'] : (int)$current['min_stock_threshold'];
        $newQty      = isset($input['quantity']) ? (int)$input['quantity'] : (int)$current['quantity'];
        
        $savedImagePath = $current['image_path'];
        // Check if a new file was uploaded in multipart
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $file['size'] <= 5 * 1024 * 1024) {
                $fileName = 'item_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                    $savedImagePath = 'uploads/products/' . $fileName;
                }
            }
        }

        $delta = $newQty - (int)$current['quantity'];
        $actionType = 'UPDATED';
        if ($delta > 0) $actionType = 'STOCK_IN';
        elseif ($delta < 0) $actionType = 'STOCK_OUT';

        $upStmt = $pdo->prepare("UPDATE products SET category_id = ?, sku = ?, name = ?, description = ?, unit_price = ?, quantity = ?, min_stock_threshold = ?, image_path = ? WHERE id = ?");
        $upStmt->execute([
            $categoryId,
            $sku,
            $name,
            $description,
            $unitPrice,
            $newQty,
            $minThreshold,
            $savedImagePath,
            $id
        ]);

        $logStmt = $pdo->prepare("INSERT INTO stock_logs (product_id, user_id, action_type, quantity_changed, platform) VALUES (?, ?, ?, ?, ?)");
        $logStmt->execute([$id, $userId, $actionType, $delta, $platform]);

        $pdo->commit();

        $isLowStock = ($newQty < $minThreshold);
        http_response_code(200);
        echo json_encode([
            'status'              => 'success',
            'id'                  => $id,
            'name'                => $name,
            'sku'                 => $sku,
            'quantity'            => $newQty,
            'min_stock_threshold' => $minThreshold,
            'image_path'          => $savedImagePath,
            'image_url'           => $baseUrl . '/' . ltrim($savedImagePath, '/'),
            'is_low_stock'        => $isLowStock,
            'message'             => 'Product updated successfully.'
        ]);
        exit;
    }

    // ----------------------------------------------------
    // 4. DELETE /api/products.php?id=X
    // ----------------------------------------------------
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Product ID is required for deletion.'
            ]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, name, sku, image_path FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Product not found.'
            ]);
            exit;
        }

        $userId   = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;
        $platform = (isset($_GET['platform']) && strtoupper($_GET['platform']) === 'MOBILE') ? 'MOBILE' : 'WEB';

        // Unlink image file if not placeholder
        if (!empty($product['image_path']) && strpos($product['image_path'], 'placeholder') === false) {
            $absPath = __DIR__ . '/../' . ltrim($product['image_path'], '/');
            if (file_exists($absPath)) {
                @unlink($absPath);
            }
        }

        // Delete record (cascades to stock_logs due to ON DELETE CASCADE)
        $delStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $delStmt->execute([$id]);

        http_response_code(200);
        echo json_encode([
            'status'  => 'success',
            'id'      => $id,
            'name'    => $product['name'],
            'message' => 'Product deleted successfully.'
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method Not Allowed.'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
