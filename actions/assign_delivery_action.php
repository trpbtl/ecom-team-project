<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/DeliveryController.php';

require_admin();
require_post('views/admin/orders.php');

$id = (int) ($_POST['order_id'] ?? 0);
$result = (new DeliveryController())->assign($id, $_POST['staff_id'] ?? '');
flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Delivery assigned to ' . $result['name'] . '.' : $result['error']);
redirect('views/admin/order_detail.php?order_id=' . $id);
