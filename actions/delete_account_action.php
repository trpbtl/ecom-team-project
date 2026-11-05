<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

require_login();
require_post('views/account/delete_account.php');

if (($_POST['confirm'] ?? '') !== 'DELETE') {
    flash('error', 'Type DELETE in capitals to confirm.');
    redirect('views/account/delete_account.php');
}

$result = (new CustomerController())->deleteAccount((int) $_SESSION['customer_id'], (string) ($_POST['password'] ?? ''));

if (!$result['success']) {
    flash('error', $result['error']);
    redirect('views/account/delete_account.php');
}

$_SESSION = [];
session_destroy();
session_start();
flash('success', 'Your account has been deleted.');
redirect('index.php');
