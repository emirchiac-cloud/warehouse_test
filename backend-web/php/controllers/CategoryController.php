<?php
// controllers/CategoryController.php
// Controller managing category creation, deletion, and retrieval

require_once __DIR__ . '/../data/CategoryData.php';

class CategoryController {

    /**
     * Lists all categories or categories with aggregated product counts.
     */
    public static function index(bool $withCounts = false): array {
        return $withCounts ? CategoryData::getWithCounts() : CategoryData::getAll();
    }

    /**
     * Creates a new category.
     */
    public static function store(string $name, string $description = ''): array {
        $name = trim($name);
        $description = trim($description);

        if (empty($name)) {
            throw new InvalidArgumentException("Category name is required.");
        }

        $id = CategoryData::create($name, $description);
        return CategoryData::getById($id);
    }

    /**
     * Deletes a category by ID.
     */
    public static function destroy(int $id): bool {
        return CategoryData::delete($id);
    }
}
