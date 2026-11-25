<?php
/** Admin: add a Packe delivery rider from the Riders page. */
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/DeliveryController.php';

require_admin();
require_post('views/admin/riders.php');

$result = (new DeliveryController())->addRider($_POST);
if ($result['success']) {
    forget_input();
    flash('success', $result['name'] . ' was added as a Packe rider. You can now assign them to orders.');
} else {
    remember_input(['full_name', 'phone', 'area']);
    flash('error', $result['error']);
}
redirect('views/admin/riders.php');
