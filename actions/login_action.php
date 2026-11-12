<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/CartController.php';

require_post('views/login.php');

$result = (new CustomerController())->login($_POST['email'] ?? '', $_POST['password'] ?? '');

if (!$result['success']) {
    remember_input(['email']);
    flash('error', $result['error']);
    redirect('views/login.php');
}

forget_input();
start_customer_session($result['customer']);
(new CartController())->attachCustomer(get_cart_key(), (int) $result['customer']['customer_id']);
redirect_after_login();   // back to the page that asked for login, or the home page for the role
