<?php
/**
 * Paystack sends the customer here after the popup says "success".
 * We do not trust that: the controller asks Paystack's Verify API before recording anything.
 */
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/OrderController.php';

require_login();

$reference = (string) ($_GET['ref'] ?? '');
$expected = $_SESSION['checkout'] ?? null;

if (!$expected) {
    flash('error', 'Your checkout session has expired. Please check out again.');
    redirect('views/payment_failed.php');
}

$result = (new OrderController())->completePayment($reference, $expected, (int) $_SESSION['customer_id'], get_cart_key());

if (!$result['success']) {
    flash('error', $result['error']);
    redirect('views/payment_failed.php');
}

unset($_SESSION['checkout']);
redirect('views/payment_success.php?order_id=' . (int) $result['order_id']);
