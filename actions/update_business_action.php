<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/BusinessController.php';

require_admin();
require_post('views/admin/businesses.php');

$id = (int) ($_POST['customer_id'] ?? 0);
$result = (new BusinessController())->update($id, $_POST, (int) $_SESSION['customer_id']);
flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Business updated.' : $result['error']);
redirect('views/admin/businesses.php' . ($result['success'] ? '' : '?edit_id=' . $id));