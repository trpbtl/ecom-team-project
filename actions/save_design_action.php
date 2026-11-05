<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomizationController.php';
require_once __DIR__ . '/../controllers/CartController.php';

$productId = (int) ($_POST['product_id'] ?? 0);
$back = 'views/customize.php?product_id=' . $productId;
require_post($back);

try {
    $logo = save_uploaded_image('logo', 'designs', 5 * 1024 * 1024);   // PNG/JPG/GIF/WebP up to 5 MB
} catch (InvalidArgumentException $e) {
    flash('error', $e->getMessage());
    redirect($back);
}

$cartKey = get_cart_key();
$customerId = $_SESSION['customer_id'] ?? null;
$result = (new CustomizationController())->saveDesign($_POST, $logo, $cartKey, $customerId);

if (!$result['success']) {
    if ($logo) @unlink(APP_ROOT . '/images/designs/' . $logo);
    flash('error', $result['error']);
    redirect($back);
}

(new CartController())->addDesign($result['product_id'], $result['customization_id'], $result['quantity'], $cartKey, $customerId);
flash('success', 'Your box design was saved and added to your cart.');
redirect('views/cart.php');
