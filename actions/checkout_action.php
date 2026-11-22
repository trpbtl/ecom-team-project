<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/OrderController.php';

require_login();
require_post('views/checkout.php');

$cartKey = get_cart_key();
// Delivery fee, Packe Pro discount and the amount to pay are all worked out on the server
$result = (new OrderController())->prepareCheckout($_POST, $cartKey, (int) $_SESSION['customer_id']);

if (!$result['success']) {
    remember_input(['recipient_name', 'recipient_phone', 'address', 'city', 'delivery_option', 'carrier', 'delivery_lat', 'delivery_lng']);
    flash('error', $result['error']);
    redirect('views/checkout.php');
}

forget_input();
$_SESSION['checkout'] = [
    'delivery'  => $result['delivery'],
    'amount'    => $result['amount'],
    'reference' => 'PACKE-' . time() . '-' . bin2hex(random_bytes(4)),   // our own reference, checked again after payment
];
redirect('views/payment.php');
