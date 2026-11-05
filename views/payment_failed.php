<?php
require_once __DIR__ . '/../core/core.php';
$pageTitle = 'Payment not completed';
require __DIR__ . '/layout/header.php';
?>

<div class="max-w-xl mx-auto card p-8 text-center">
    <h1 class="font-display font-extrabold text-3xl">Payment not completed</h1>
    <p class="text-muted mt-2">No order was placed. Your cart is still saved. The reason is shown above.</p>
    <div class="flex justify-center gap-3 mt-6">
        <a href="<?= url('views/cart.php') ?>" class="btn btn-ghost">Back to cart</a>
        <a href="<?= url('views/checkout.php') ?>" class="btn btn-primary">Try again</a>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
