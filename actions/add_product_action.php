<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();
require_post('views/admin/product.php');

$fields = ['category_id', 'name', 'description', 'material', 'base_price', 'keywords', 'design_type'];

try {
    $image = save_uploaded_image('image', 'products');
} catch (InvalidArgumentException $e) {
    remember_input($fields);
    flash('error', $e->getMessage());
    redirect('views/admin/product.php');
}

$result = (new ProductController())->addProduct($_POST, $image);

if (!$result['success']) {
    if ($image) @unlink(APP_ROOT . '/images/products/' . $image);   // don't keep an image for a product that wasn't saved
    remember_input($fields);
    flash('error', $result['error']);
    redirect('views/admin/product.php');
}

forget_input();
flash('success', 'Product added.');
redirect('views/admin/product.php');
