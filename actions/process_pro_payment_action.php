<?php
/**
 * Paystack sends the customer here after paying for Packe Pro.
 * Like orders, the payment is verified with Paystack's API on the server before the month is added.
 */
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProController.php';

require_login();

$result = (new ProController())->completePayment(
    (string) ($_GET['ref'] ?? ''), $_SESSION['pro_checkout'] ?? null, (int) $_SESSION['customer_id']
);

if (!$result['success']) {
    flash('error', $result['error']);
    redirect('views/pro.php');
}

unset($_SESSION['pro_checkout']);
flash('success', 'Welcome to Packe Pro! Your 10% discount and free standard delivery now apply at checkout.');
redirect('views/pro.php');
