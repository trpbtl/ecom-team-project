<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

require_login();
require_post('views/account/change_pass.php');

$result = (new CustomerController())->changePassword(
    (int) $_SESSION['customer_id'],
    (string) ($_POST['current_password'] ?? ''),
    (string) ($_POST['new_password'] ?? ''),
    (string) ($_POST['confirm_password'] ?? '')
);

if (!$result['success']) {
    flash('error', $result['error']);
    redirect('views/account/change_pass.php');
}

session_regenerate_id(true);
flash('success', 'Your password has been saved.');
redirect('views/account/my_account.php');
