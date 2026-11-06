<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomizationController.php';
require_once __DIR__ . '/../controllers/CartController.php';

$productId = (int) ($_POST['product_id'] ?? 0);
$back = 'views/customize.php?product_id=' . $productId;
require_post($back);

/** Answer the designer: JSON when js/cart.js sent it in the background, otherwise a redirect. */
function design_reply(bool $ok, string $message, string $back, array $extra = []): void
{
    if (wants_json()) {
        $sum = (new CartController())->getCartSummary(get_cart_key());
        json_out(['success' => $ok, 'message' => $message, 'count' => $sum['count'], 'total' => $sum['total']] + $extra, $ok ? 200 : 422);
    }
    flash($ok ? 'success' : 'error', $message);
    redirect($ok ? 'views/cart.php' : $back);
}

try {
    $logo = save_uploaded_image('logo', 'designs', 5 * 1024 * 1024);   // PNG/JPG/GIF/WebP up to 5 MB
} catch (InvalidArgumentException $e) {
    design_reply(false, $e->getMessage(), $back, ['field' => 'logo', 'error' => $e->getMessage()]);
}

$cartKey = get_cart_key();
$customerId = $_SESSION['customer_id'] ?? null;
$result = (new CustomizationController())->saveDesign($_POST, $logo, $cartKey, $customerId);

if (!$result['success']) {
    if ($logo) @unlink(APP_ROOT . '/images/designs/' . $logo);
    design_reply(false, $result['error'], $back, isset($result['field']) ? ['field' => $result['field'], 'error' => $result['error']] : []);
}

(new CartController())->addDesign($result['product_id'], $result['customization_id'], $result['quantity'], $cartKey, $customerId);
design_reply(true, 'Your design (' . $result['quantity'] . ' ' . $result['noun'] . ') was added to your cart.', $back,
             ['line_total' => round($result['unit_price'] * $result['quantity'], 2)]);
