<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/BusinessController.php';

require_admin();
require_post('views/admin/businesses.php');

$result = (new BusinessController())->add($_POST);
if ($result['success']) {
    forget_input();
    flash('success', 'Business added.');
} else {
    remember_input(['full_name', 'email', 'phone', 'business_name', 'city', 'address', 'user_role']);
    flash('error', $result['error']);
}
redirect('views/admin/businesses.php');