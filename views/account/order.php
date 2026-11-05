<?php
require_once __DIR__ . '/../../core/core.php';
require_login();
require_once __DIR__ . '/../../controllers/OrderController.php';

$order = (new OrderController())->getOrderForViewer($_GET['order_id'] ?? null, (int) $_SESSION['customer_id'], is_admin());
if (!$order) {
    flash('error', 'That order was not found.');
    redirect('views/account/my_account.php');
}
$pageTitle = 'Order ' . $order['invoice_no'];
require __DIR__ . '/../layout/header.php';
?>

<div class="max-w-3xl mx-auto">
    <a href="<?= url('views/account/my_account.php') ?>" class="text-sm text-muted hover:underline">← My account</a>
    <h1 class="font-display font-extrabold text-3xl mt-2 mb-6">Order <?= e($order['invoice_no']) ?></h1>
    <?php require __DIR__ . '/../partials/invoice.php'; ?>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
