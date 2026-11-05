<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

// Return to the page the button was on (only pages on this site)
$back = $_POST['back'] ?? '';
$back = (is_string($back) && str_starts_with($back, '/') && !str_starts_with($back, '//')) ? $back : url('index.php');

require_post('index.php');

$productId = $_POST['product_id'] ?? '';
$result = (new CartController())->addProduct(
    $productId, $_POST['quantity'] ?? 1, get_cart_key(), $_SESSION['customer_id'] ?? null
);

if (!empty($result['design'])) {
    redirect('views/customize.php?product_id=' . (int) $productId);
}
flash($result['success'] ? 'success' : 'error', $result['success'] ? $result['name'] . ' was added to your cart.' : $result['error']);
header('Location: ' . $back);
exit;
