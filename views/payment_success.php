<?php
require_once __DIR__ . '/../core/core.php';
require_login();
require_once __DIR__ . '/../controllers/OrderController.php';

$order = (new OrderController())->getOrderForViewer($_GET['order_id'] ?? null, (int) $_SESSION['customer_id'], is_admin());
if (!$order) {
    flash('error', 'That order was not found.');
    redirect('views/account/my_account.php');
}
$pageTitle = 'Payment successful';
require __DIR__ . '/layout/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="rounded-2xl bg-[#ecf7ee] border border-[#bfe3c6] p-6 mb-6">
        <h1 class="font-display font-extrabold text-3xl text-[#1d5b2c]">Payment successful</h1>
        <p class="text-[#1d5b2c] mt-1">Thank you! Paystack confirmed your payment and your order is now with our team. You can follow it in <a class="underline font-semibold" href="<?= url('views/account/my_account.php') ?>">My account</a>.</p>
    </div>
    <?php require __DIR__ . '/partials/invoice.php'; ?>
    <div class="flex gap-3 mt-6">
        <a href="<?= url('index.php') ?>" class="btn btn-ghost">Back to the shop</a>
        <a href="<?= url('views/customize.php') ?>" class="btn btn-primary">Design another box</a>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
