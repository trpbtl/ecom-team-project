<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

require_post('views/cart.php');

$ids = $_POST['remove'] ?? [];
$result = (new CartController())->removeItems(is_array($ids) ? $ids : [$ids], get_cart_key());
flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Removed from your cart.' : $result['error']);
redirect('views/cart.php');
