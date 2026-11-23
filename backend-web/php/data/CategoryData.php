<?php
// data/CategoryData.php
// Data access layer for categories table

require_once __DIR__ . '/../core/connection.php';

class CategoryData {

    /**
     * Retrieves all categories sorted alphabetically.
     */
    public static function getAll(): array {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT id, name, description FROM categories ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Retrieves categories with associated product count and inventory valuation.
     */
    public static function getWithCounts(): array {
        $pdo = getDBConnection();
        $sql = "SELECT c.id, c.name, c.description,
                       COUNT(p.id) AS product_count,
                       COALESCE(SUM(p.quantity), 0) AS total_items,
                       COALESCE(SUM(p.quantity * p.unit_price), 0.00) AS total_valuation
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id
                GROUP BY c.id, c.name, c.description
                ORDER BY c.name ASC";
        return $pdo->query($sql)->fetchAll();
    }

    /**
     * Finds single category by ID.
     */
    public static function getById(int $id): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, name, description FROM categories WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Creates a new category record.
     */
    public static function create(string $name, string $description): int {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Deletes a category if it has no associated products.
     */
    public static function delete(int $id): bool {
        $pdo = getDBConnection();
        // Check if any products use this category
        $chk = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $chk->execute([$id]);
        if ($chk->fetchColumn() > 0) {
            throw new Exception("Cannot delete category containing active products. Reassign or delete products first.");
        }
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
