<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();
require_post('views/admin/category.php');

$result = (new ProductController())->addCategory($_POST['category_name'] ?? '');
flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Category added.' : $result['error']);
redirect('views/admin/category.php');
