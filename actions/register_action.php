<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/CartController.php';

require_post('views/register.php');

$result = (new CustomerController())->register($_POST);

if (!$result['success']) {
    remember_input(['full_name', 'email', 'phone', 'business_name', 'city', 'address']);
    flash('error', $result['error']);
    redirect('views/register.php');
}

forget_input();
session_regenerate_id(true);
$c = $result['customer'];
$_SESSION['customer_id'] = (int) $c['customer_id'];
$_SESSION['customer_name'] = $c['full_name'];
$_SESSION['customer_email'] = $c['email'];
$_SESSION['user_role'] = (int) $c['user_role'];
(new CartController())->attachCustomer(get_cart_key(), (int) $c['customer_id']);

flash('success', 'Welcome to Packe, ' . $c['full_name'] . '! Your account is ready.');
redirect('views/account/my_account.php');
