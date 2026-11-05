<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/DeliveryController.php';

require_delivery();
$back = is_admin() && !empty($_POST['order_id'])
    ? 'views/admin/order_detail.php?order_id=' . (int) $_POST['order_id']
    : 'views/delivery/my_deliveries.php';
require_post($back);

$result = (new DeliveryController())->updateStatus(
    $_POST['delivery_id'] ?? '', (string) ($_POST['status'] ?? ''), (int) $_SESSION['customer_id'], is_admin()
);
flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Delivery status updated.' : $result['error']);
redirect($back);
