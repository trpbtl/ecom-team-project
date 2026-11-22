<?php
/** Admin: save the Uber / Yango / Bolt driver that was booked for a partner delivery. */
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/DeliveryController.php';

require_admin();
require_post('views/admin/orders.php');

$id = (int) ($_POST['order_id'] ?? 0);
$result = (new DeliveryController())->bookPartner($id, $_POST);
flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Driver ' . $result['name'] . ' saved. The customer can now see their details.' : $result['error']);
redirect('views/admin/order_detail.php?order_id=' . $id);
