<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();
require_post('views/admin/category.php');

$id = $_POST['category_id'] ?? '';
$result = (new ProductController())->updateCategory($id, $_POST['category_name'] ?? '');

if (!$result['success']) {
    flash('error', $result['error']);
    redirect('views/admin/category.php?edit_id=' . (int) $id);
}
flash('success', 'Category updated.');
redirect('views/admin/category.php');
