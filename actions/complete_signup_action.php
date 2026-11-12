<?php
// Create the account for someone who continued with Google (see views/complete_signup.php).
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/CartController.php';

require_post('views/complete_signup.php');

$pending = $_SESSION['social_pending'] ?? null;
if (!$pending || time() - $pending['time'] > 1800) {
    unset($_SESSION['social_pending']);
    flash('error', 'Please continue with Google again to create your account.');
    redirect('views/login.php');
}

$result = (new CustomerController())->registerSocial($pending, $_POST);
if (!$result['success']) {
    remember_input(['full_name', 'email', 'phone', 'business_name', 'city', 'address']);
    flash('error', $result['error']);
    redirect('views/complete_signup.php');
}

unset($_SESSION['social_pending']);
forget_input();
start_customer_session($result['customer']);
(new CartController())->attachCustomer(get_cart_key(), (int) $result['customer']['customer_id']);
flash('success', 'Welcome to Packe, ' . $result['customer']['full_name'] . '! Your account is ready.');
redirect('views/account/my_account.php');
