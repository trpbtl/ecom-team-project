<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/OrderController.php';
require_once __DIR__ . '/../controllers/CartController.php';

require_login();
require_post('views/checkout.php');

$cartKey = get_cart_key();
$result = (new OrderController())->prepareCheckout($_POST, $cartKey);

if (!$result['success']) {
    remember_input(['recipient_name', 'recipient_phone', 'address', 'city', 'delivery_option']);
    flash('error', $result['error']);
    redirect('views/checkout.php');
}

forget_input();
$delivery = $result['delivery'];
$_SESSION['checkout'] = [
    'delivery'  => $delivery,
    'amount'    => round((new CartController())->getCartTotal($cartKey) + $delivery['fee'], 2),
    'reference' => 'PACKE-' . time() . '-' . bin2hex(random_bytes(4)),   // our own reference, checked again after payment
];
redirect('views/payment.php');
