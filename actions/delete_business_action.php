<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/BusinessController.php';

require_admin();
require_post('views/admin/businesses.php');

$result = (new BusinessController())->delete((int) ($_POST['customer_id'] ?? 0), (int) $_SESSION['customer_id']);
flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Business deleted.' : $result['error']);
redirect('views/admin/businesses.php');