<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/DeliveryController.php';

require_admin();
require_post('views/admin/orders.php');

$id = (int) ($_POST['order_id'] ?? 0);
$result = (new DeliveryController())->assign($id, $_POST['staff_id'] ?? '');
flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Delivery assigned to ' . $result['name'] . '.' : $result['error']);
// From the Riders page, go back there; otherwise to the order
redirect(($_POST['back'] ?? '') === 'riders' ? 'views/admin/riders.php' : 'views/admin/order_detail.php?order_id=' . $id);
