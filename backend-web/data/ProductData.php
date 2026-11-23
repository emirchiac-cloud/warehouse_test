<?php
// data/ProductData.php
// Data access layer for products and inventory metrics

require_once __DIR__ . '/../core/connection.php';
require_once __DIR__ . '/LogData.php';

class ProductData {

    /**
     * Fetches products matching search query and category filter with sorting.
     */
    public static function getAll(array $filters = []): array {
        $pdo = getDBConnection();
        $sql = "SELECT p.*, c.name AS category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['low_stock_only'])) {
            $sql .= " AND p.quantity <= p.min_stock_threshold";
        }

        $sort = $filters['sort'] ?? 'low_stock_first';
        if ($sort === 'name') {
            $sql .= " ORDER BY p.name ASC";
        } elseif ($sort === 'quantity') {
            $sql .= " ORDER BY p.quantity ASC";
        } elseif ($sort === 'price') {
            $sql .= " ORDER BY p.unit_price DESC";
        } else {
            // Default: low-stock first, then newest
            $sql .= " ORDER BY (p.quantity <= p.min_stock_threshold) DESC, p.id DESC";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        $baseUrl = getBaseUrl();
        foreach ($products as &$p) {
            $p['is_low_stock'] = (int)$p['quantity'] <= (int)$p['min_stock_threshold'];
            $img = $p['image_path'] ?? '';
            if (empty($img)) {
                $p['image_url'] = $baseUrl . '/uploads/products/placeholder.png';
            } elseif (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
                $p['image_url'] = $img;
            } else {
                $cleanPath = ltrim(str_replace('uploads/products/', '', $img), '/');
                $p['image_url'] = $baseUrl . '/uploads/products/' . $cleanPath;
            }
        }
        unset($p);

        return $products;
    }

    /**
     * Finds single product by ID.
     */
    public static function getById(int $id): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.id 
                               WHERE p.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if ($p) {
            $baseUrl = getBaseUrl();
            $p['is_low_stock'] = (int)$p['quantity'] <= (int)$p['min_stock_threshold'];
            $img = $p['image_path'] ?? '';
            if (empty($img)) {
                $p['image_url'] = $baseUrl . '/uploads/products/placeholder.png';
            } elseif (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
                $p['image_url'] = $img;
            } else {
                $cleanPath = ltrim(str_replace('uploads/products/', '', $img), '/');
                $p['image_url'] = $baseUrl . '/uploads/products/' . $cleanPath;
            }
            return $p;
        }
        return null;
    }

    /**
     * Checks if a SKU already exists.
     */
    public static function skuExists(string $sku, int $excludeId = 0): bool {
        $pdo = getDBConnection();
        if ($excludeId > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ? AND id != ?");
            $stmt->execute([$sku, $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
            $stmt->execute([$sku]);
        }
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Creates new product record and logs CREATED audit action.
     */
    public static function create(array $data, int $userId = 1, string $platform = 'WEB'): int {
        $pdo = getDBConnection();

        if (self::skuExists($data['sku'])) {
            throw new Exception("Product SKU '{$data['sku']}' already exists.");
        }

        $stmt = $pdo->prepare("INSERT INTO products (category_id, sku, name, description, unit_price, quantity, min_stock_threshold, image_path, updated_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            (int)$data['category_id'],
            trim($data['sku']),
            trim($data['name']),
            trim($data['description'] ?? ''),
            (float)$data['unit_price'],
            (int)$data['quantity'],
            (int)($data['min_stock_threshold'] ?? 5),
            $data['image_path'] ?? 'uploads/products/placeholder.png'
        ]);
        $productId = (int)$pdo->lastInsertId();

        // Audit log
        LogData::record($productId, $userId, 'CREATED', (int)$data['quantity'], $platform);

        return $productId;
    }

    /**
     * Updates an existing product details and records UPDATED audit action.
     */
    public static function update(int $id, array $data, int $userId = 1, string $platform = 'WEB'): bool {
        $pdo = getDBConnection();
        $curr = self::getById($id);
        if (!$curr) {
            throw new Exception("Product not found.");
        }

        if (self::skuExists($data['sku'], $id)) {
            throw new Exception("Product SKU '{$data['sku']}' is already in use by another item.");
        }

        $stmt = $pdo->prepare("UPDATE products 
                               SET category_id = ?, sku = ?, name = ?, description = ?, unit_price = ?, quantity = ?, min_stock_threshold = ?, image_path = ?, updated_at = NOW() 
                               WHERE id = ?");
        $res = $stmt->execute([
            (int)$data['category_id'],
            trim($data['sku']),
            trim($data['name']),
            trim($data['description'] ?? ''),
            (float)$data['unit_price'],
            (int)$data['quantity'],
            (int)($data['min_stock_threshold'] ?? 5),
            $data['image_path'] ?? $curr['image_path'],
            $id
        ]);

        $diff = (int)$data['quantity'] - (int)$curr['quantity'];
        LogData::record($id, $userId, 'UPDATED', $diff, $platform);

        return $res;
    }

    /**
     * Quick stock adjustment (+delta or -delta).
     */
    public static function adjustStock(int $id, int $delta, int $userId = 1, string $platform = 'WEB'): array {
        $pdo = getDBConnection();
        $curr = self::getById($id);
        if (!$curr) {
            throw new Exception("Product not found.");
        }

        $newQty = max(0, (int)$curr['quantity'] + $delta);
        $action = $delta >= 0 ? 'STOCK_IN' : 'STOCK_OUT';

        $stmt = $pdo->prepare("UPDATE products SET quantity = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newQty, $id]);

        LogData::record($id, $userId, $action, $delta, $platform);

        $threshold = (int)$curr['min_stock_threshold'];
        return [
            'id'                  => $id,
            'name'                => $curr['name'],
            'previous_quantity'   => (int)$curr['quantity'],
            'new_quantity'        => $newQty,
            'min_stock_threshold' => $threshold,
            'is_low_stock'        => $newQty <= $threshold,
            'action_type'         => $action,
            'delta'               => $delta
        ];
    }

    /**
     * Deletes product and records DELETED log action.
     */
    public static function delete(int $id, int $userId = 1, string $platform = 'WEB'): bool {
        $pdo = getDBConnection();
        $curr = self::getById($id);
        if (!$curr) {
            throw new Exception("Product not found.");
        }

        // Delete associated uploaded image file if not placeholder
        if (!empty($curr['image_path']) && strpos($curr['image_path'], 'placeholder') === false) {
            $fullPath = __DIR__ . '/../' . ltrim($curr['image_path'], '/');
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        LogData::record($id, $userId, 'DELETED', -(int)$curr['quantity'], $platform);

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Calculates summary metrics for KPI dashboard cards.
     */
    public static function getMetrics(): array {
        $pdo = getDBConnection();
        $sql = "SELECT 
                    COUNT(*) AS total_skus,
                    COALESCE(SUM(quantity), 0) AS total_units,
                    COALESCE(SUM(quantity * unit_price), 0.00) AS total_valuation,
                    SUM(CASE WHEN quantity <= min_stock_threshold AND quantity > 0 THEN 1 ELSE 0 END) AS low_stock_count,
                    SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) AS out_of_stock_count
                FROM products";
        return $pdo->query($sql)->fetch() ?: [];
    }
}
