<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

// Return to the page the button was on (only pages on this site)
$back = $_POST['back'] ?? '';
$back = (is_string($back) && str_starts_with($back, '/') && !str_starts_with($back, '//')) ? $back : url('index.php');

require_post('index.php');

$cart = new CartController();
$cartKey = get_cart_key();
$result = $cart->addProduct($_POST['product_id'] ?? '', $_POST['quantity'] ?? 1, $cartKey, $_SESSION['customer_id'] ?? null);

// Sent by js/cart.js in the background: answer with JSON, the page does not reload
if (wants_json()) {
    $sum = $cart->getCartSummary($cartKey);
    json_out([
        'success' => $result['success'],
        'message' => $result['success'] ? $result['name'] . ' was added to your cart.' : $result['error'],
        'count'   => $sum['count'],
        'total'   => $sum['total'],
    ], $result['success'] ? 200 : 422);
}

// Without JavaScript: the classic form post and redirect
flash($result['success'] ? 'success' : 'error', $result['success'] ? $result['name'] . ' was added to your cart.' : $result['error']);
header('Location: ' . $back);
exit;
