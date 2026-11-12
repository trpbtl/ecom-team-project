<?php
// Google sends the customer back here after they sign in (this exact address is registered in Google Cloud Console).
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/GoogleAuthController.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/CartController.php';

if (!GoogleAuthController::isEnabled()) redirect('views/login.php');

$google = (new GoogleAuthController())->finish($_GET);
if (!$google['success']) {
    flash('error', $google['error']);
    redirect('views/login.php');
}

$result = (new CustomerController())->socialSignIn($google['who']);
if ($result['success']) {
    // Known Google account (or a Packe account with the same verified email): log in
    start_customer_session($result['customer']);
    (new CartController())->attachCustomer(get_cart_key(), (int) $result['customer']['customer_id']);
    flash('success', 'Welcome back, ' . explode(' ', $result['customer']['full_name'])[0] . '!');
    redirect_after_login();
}

// New to Packe: we still need a phone number and city, so ask for them on one short page
$_SESSION['social_pending'] = $google['who'] + ['time' => time()];
redirect('views/complete_signup.php');
