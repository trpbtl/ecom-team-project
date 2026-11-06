<?php
/**
 * Controller: products and categories.
 * Owner: Hagen Dickson
 */
require_once __DIR__ . '/../classes/ProductClass.php';
require_once __DIR__ . '/Validate.php';
require_once __DIR__ . '/DesignTypes.php';

class ProductController
{
    private ProductClass $model;

    public function __construct()
    {
        $this->model = new ProductClass();
    }

    /* ----- Categories ----- */
    public function addCategory(string $name): array
    {
        $name = Validate::clean($name);
        if (!Validate::text($name, 2, 60)) return Validate::fail('Category name must be 2 to 60 characters.');
        if ($this->model->categoryNameExists($name)) return Validate::fail('That category already exists.');
        $this->model->addCategory($name);
        return ['success' => true];
    }

    public function updateCategory($id, string $name): array
    {
        $id = Validate::positiveInt($id);
        $name = Validate::clean($name);
        if (!$id || !$this->model->getCategoryById($id)) return Validate::fail('That category was not found.');
        if (!Validate::text($name, 2, 60)) return Validate::fail('Category name must be 2 to 60 characters.');
        if ($this->model->categoryNameExists($name, $id)) return Validate::fail('Another category already has that name.');
        $this->model->updateCategory($id, $name);
        return ['success' => true];
    }

    public function getAllCategories(): array
    {
        return $this->model->getAllCategories();
    }

    public function getCategoryById($id): ?array
    {
        $id = Validate::positiveInt($id);
        return $id ? $this->model->getCategoryById($id) : null;
    }

    /* ----- Products ----- */
    /** Validates product fields. $image is the already-saved file name (the upload itself happens in the action). */
    private function cleanProduct(array $in, ?string $image): array
    {
        $d = [
            'category_id'     => Validate::positiveInt($in['category_id'] ?? null),
            'name'            => Validate::clean($in['name'] ?? ''),
            'description'     => trim(strip_tags((string) ($in['description'] ?? ''))),
            'material'        => Validate::clean($in['material'] ?? ''),
            'base_price'      => filter_var($in['base_price'] ?? '', FILTER_VALIDATE_FLOAT),
            'image'           => $image,
            'keywords'        => Validate::clean($in['keywords'] ?? ''),
            'design_type'     => is_string($in['design_type'] ?? null) && DesignTypes::get($in['design_type']) ? $in['design_type'] : null,
        ];
        $d['is_customizable'] = $d['design_type'] ? 1 : 0;   // products with a template open in the 3D designer
        if (!$d['category_id'] || !$this->model->getCategoryById($d['category_id'])) $d['error'] = 'Choose a category.';
        elseif (!Validate::text($d['name'], 2, 150))  $d['error'] = 'Product name must be 2 to 150 characters.';
        elseif ($d['base_price'] === false || $d['base_price'] <= 0 || $d['base_price'] > 100000) $d['error'] = 'Enter a price between 0.01 and 100,000.';
        elseif (mb_strlen($d['description']) > 2000)  $d['error'] = 'Description must be 2,000 characters or fewer.';
        elseif (mb_strlen($d['material']) > 100)      $d['error'] = 'Material must be 100 characters or fewer.';
        elseif (mb_strlen($d['keywords']) > 255)      $d['error'] = 'Keywords must be 255 characters or fewer.';
        return $d;
    }

    public function addProduct(array $in, ?string $image): array
    {
        $d = $this->cleanProduct($in, $image);
        if (isset($d['error'])) return Validate::fail($d['error']);
        $id = $this->model->addProduct($d);
        return ['success' => true, 'product_id' => $id];
    }

    public function updateProduct($id, array $in, ?string $newImage): array
    {
        $id = Validate::positiveInt($id);
        $existing = $id ? $this->model->getProductById($id) : null;
        if (!$existing) return Validate::fail('That product was not found.');
        $d = $this->cleanProduct($in, $newImage ?? $existing['image']);   // keep the old image if no new one was uploaded
        if (isset($d['error'])) return Validate::fail($d['error']);
        $this->model->updateProduct($id, $d);
        return ['success' => true, 'product_id' => $id];
    }

    public function getProductById($id): ?array
    {
        $id = Validate::positiveInt($id);
        return $id ? $this->model->getProductById($id) : null;
    }

    public function getAllProducts(): array
    {
        return $this->model->getAllProducts();
    }

    public function getFeaturedProducts(int $limit = 8): array
    {
        return $this->model->getFeaturedProducts($limit);
    }

    public function getProductsByCategory($categoryId): array
    {
        $id = Validate::positiveInt($categoryId);
        return $id ? $this->model->getProductsByCategory($id) : [];
    }

    public function searchProducts(string $query): array
    {
        $query = Validate::clean($query);
        if (mb_strlen($query) < 2 || mb_strlen($query) > 60) {
            return [];
        }
        return $this->model->searchProducts($query);
    }
}
