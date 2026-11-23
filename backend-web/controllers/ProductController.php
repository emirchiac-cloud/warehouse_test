<?php
// controllers/ProductController.php
// Controller managing product lifecycle, stock updates, image upload, and REST endpoints

require_once __DIR__ . '/../data/ProductData.php';
require_once __DIR__ . '/../data/CategoryData.php';

class ProductController {

    /**
     * Lists all products with optional filters.
     */
    public static function index(array $filters = []): array {
        return ProductData::getAll($filters);
    }

    /**
     * Shows a single product by ID.
     */
    public static function show(int $id): ?array {
        return ProductData::getById($id);
    }

    /**
     * Handles product creation with optional file upload.
     */
    public static function store(array $input, array $files = [], int $userId = 1, string $platform = 'WEB'): array {
        $sku      = trim($input['sku'] ?? '');
        $name     = trim($input['name'] ?? '');
        $catId    = (int)($input['category_id'] ?? 0);
        $price    = (float)($input['unit_price'] ?? 0);
        $qty      = (int)($input['quantity'] ?? 0);
        $minStock = isset($input['min_stock_threshold']) ? (int)$input['min_stock_threshold'] : 5;
        $desc     = trim($input['description'] ?? '');

        if (empty($sku) || empty($name) || $catId <= 0) {
            throw new InvalidArgumentException("SKU, Name, and Category are required.");
        }

        $imagePath = self::processImageUpload($files['image'] ?? null);

        $newId = ProductData::create([
            'category_id'         => $catId,
            'sku'                 => $sku,
            'name'                => $name,
            'description'         => $desc,
            'unit_price'          => $price,
            'quantity'            => $qty,
            'min_stock_threshold' => $minStock,
            'image_path'          => $imagePath
        ], $userId, $platform);

        return ProductData::getById($newId);
    }

    /**
     * Handles product detail and stock updates.
     */
    public static function update(int $id, array $input, array $files = [], int $userId = 1, string $platform = 'WEB'): array {
        $curr = ProductData::getById($id);
        if (!$curr) {
            throw new RuntimeException("Product not found.");
        }

        $sku      = trim($input['sku'] ?? $curr['sku']);
        $name     = trim($input['name'] ?? $curr['name']);
        $catId    = (int)($input['category_id'] ?? $curr['category_id']);
        $price    = isset($input['unit_price']) ? (float)$input['unit_price'] : (float)$curr['unit_price'];
        $qty      = isset($input['quantity']) ? (int)$input['quantity'] : (int)$curr['quantity'];
        $minStock = isset($input['min_stock_threshold']) ? (int)$input['min_stock_threshold'] : (int)$curr['min_stock_threshold'];
        $desc     = isset($input['description']) ? trim($input['description']) : ($curr['description'] ?? '');

        $uploaded = self::processImageUpload($files['image'] ?? null);
        $imagePath = $uploaded ?: $curr['image_path'];

        ProductData::update($id, [
            'category_id'         => $catId,
            'sku'                 => $sku,
            'name'                => $name,
            'description'         => $desc,
            'unit_price'          => $price,
            'quantity'            => $qty,
            'min_stock_threshold' => $minStock,
            'image_path'          => $imagePath
        ], $userId, $platform);

        return ProductData::getById($id);
    }

    /**
     * Quick stock adjustment (+1, -1, or arbitrary delta).
     */
    public static function adjustStock(int $id, int $delta, int $userId = 1, string $platform = 'WEB'): array {
        return ProductData::adjustStock($id, $delta, $userId, $platform);
    }

    /**
     * Deletes a product by ID.
     */
    public static function destroy(int $id, int $userId = 1, string $platform = 'WEB'): bool {
        return ProductData::delete($id, $userId, $platform);
    }

    /**
     * Handles image file validation and storage in uploads/products/.
     */
    private static function processImageUpload(?array $file): string {
        if (!$file || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return 'uploads/products/placeholder.png';
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            throw new InvalidArgumentException("Invalid image format. Allowed: JPG, PNG, WEBP, GIF.");
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            throw new InvalidArgumentException("Image exceeds 5MB size limit.");
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'png'
        };

        $targetDir = __DIR__ . '/../uploads/products/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $targetDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new RuntimeException("Failed to save uploaded image.");
        }

        return 'uploads/products/' . $filename;
    }
}
