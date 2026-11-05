<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/OrderController.php';

require_admin();
require_post('views/admin/orders.php');

$id = (int) ($_POST['order_id'] ?? 0);
$result = (new OrderController())->updateOrderStatus($id, (string) ($_POST['order_status'] ?? ''));
flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Order status updated.' : $result['error']);
redirect('views/admin/order_detail.php?order_id=' . $id);
