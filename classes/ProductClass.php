<?php
/**
 * Model: products and categories.
 */
require_once __DIR__ . '/../core/db_class.php';

class ProductClass extends Database
{
    private const SELECT_PRODUCT =
        'SELECT p.*, c.category_name FROM products p JOIN categories c ON c.category_id = p.category_id';

    /* ----- Categories ----- */
    public function addCategory(string $name): int
    {
        $this->execute('INSERT INTO categories (category_name) VALUES (?)', 's', [$name]);
        return $this->conn->insert_id;
    }

    public function updateCategory(int $id, string $name): bool
    {
        $this->execute('UPDATE categories SET category_name = ? WHERE category_id = ?', 'si', [$name, $id]);
        return true;
    }

    public function categoryNameExists(string $name, int $exceptId = 0): bool
    {
        return $this->fetchOne(
            'SELECT category_id FROM categories WHERE category_name = ? AND category_id <> ?',
            'si', [$name, $exceptId]
        ) !== null;
    }

    public function getAllCategories(): array
    {
        return $this->fetchAll(
            'SELECT c.*, COUNT(p.product_id) AS product_count
             FROM categories c LEFT JOIN products p ON p.category_id = c.category_id
             GROUP BY c.category_id ORDER BY c.category_name ASC'
        );
    }

    public function getCategoryById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM categories WHERE category_id = ?', 'i', [$id]);
    }

    /* ----- Products ----- */
    public function addProduct(array $d): int
    {
        $this->execute(
            'INSERT INTO products (category_id, name, description, material, base_price, image, keywords, is_customizable)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            'isssdssi',
            [$d['category_id'], $d['name'], $d['description'], $d['material'], $d['base_price'], $d['image'], $d['keywords'], $d['is_customizable']]
        );
        return $this->conn->insert_id;
    }

    public function updateProduct(int $id, array $d): bool
    {
        $this->execute(
            'UPDATE products SET category_id = ?, name = ?, description = ?, material = ?, base_price = ?,
                    image = ?, keywords = ?, is_customizable = ?
             WHERE product_id = ?',
            'isssdssii',
            [$d['category_id'], $d['name'], $d['description'], $d['material'], $d['base_price'], $d['image'], $d['keywords'], $d['is_customizable'], $id]
        );
        return true;
    }

    public function getProductById(int $id): ?array
    {
        return $this->fetchOne(self::SELECT_PRODUCT . ' WHERE p.product_id = ?', 'i', [$id]);
    }

    public function getAllProducts(): array
    {
        return $this->fetchAll(self::SELECT_PRODUCT . ' ORDER BY p.is_customizable DESC, p.name ASC');
    }

    public function getFeaturedProducts(int $limit = 6): array
    {
        return $this->fetchAll(self::SELECT_PRODUCT . ' ORDER BY p.is_customizable DESC, RAND() LIMIT ?', 'i', [$limit]);
    }

    public function getProductsByCategory(int $categoryId): array
    {
        return $this->fetchAll(self::SELECT_PRODUCT . ' WHERE p.category_id = ? ORDER BY p.name ASC', 'i', [$categoryId]);
    }

    public function searchProducts(string $query): array
    {
        $like = '%' . $query . '%';
        return $this->fetchAll(
            self::SELECT_PRODUCT . ' WHERE p.name LIKE ? OR p.keywords LIKE ? OR p.description LIKE ? ORDER BY p.name ASC',
            'sss', [$like, $like, $like]
        );
    }
}
