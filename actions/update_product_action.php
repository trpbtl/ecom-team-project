<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();
require_post('views/admin/product.php');

$id = (int) ($_POST['product_id'] ?? 0);
$back = 'views/admin/product.php?edit_id=' . $id;

try {
    $image = save_uploaded_image('image', 'products');   // null = keep the current image
} catch (InvalidArgumentException $e) {
    flash('error', $e->getMessage());
    redirect($back);
}

$result = (new ProductController())->updateProduct($id, $_POST, $image);

if (!$result['success']) {
    if ($image) @unlink(APP_ROOT . '/images/products/' . $image);
    flash('error', $result['error']);
    redirect($back);
}

flash('success', 'Product updated.');
redirect('views/admin/product.php');
