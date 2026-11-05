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
session_regenerate_id(true);
$c = $result['customer'];
$_SESSION['customer_id'] = (int) $c['customer_id'];
$_SESSION['customer_name'] = $c['full_name'];
$_SESSION['customer_email'] = $c['email'];
$_SESSION['user_role'] = (int) $c['user_role'];
(new CartController())->attachCustomer(get_cart_key(), (int) $c['customer_id']);

// Go back to the page that asked for login, if it was on this site
$next = $_SESSION['after_login'] ?? '';
unset($_SESSION['after_login']);
if ($next !== '' && str_starts_with($next, BASE_URL . '/') && !str_contains($next, '://')) {
    header('Location: ' . $next);
    exit;
}

if (is_admin()) redirect('views/admin/dashboard.php');
if (is_delivery()) redirect('views/delivery/my_deliveries.php');
redirect('index.php');
