<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

require_post('views/cart.php');

$qty = $_POST['qty'] ?? [];   // qty[cart_id] = new quantity
$result = (new CartController())->updateQuantities(is_array($qty) ? $qty : [], get_cart_key());
flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Quantities updated.' : $result['error']);
redirect('views/cart.php');
