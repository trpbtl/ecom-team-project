<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

require_login();
require_post('views/account/edit_account.php');

$result = (new CustomerController())->updateProfile((int) $_SESSION['customer_id'], $_POST);

if (!$result['success']) {
    flash('error', $result['error']);
    redirect('views/account/edit_account.php');
}

$_SESSION['customer_name'] = $result['customer']['full_name'];
$_SESSION['customer_email'] = $result['customer']['email'];
flash('success', 'Your details have been updated.');
redirect('views/account/my_account.php');
