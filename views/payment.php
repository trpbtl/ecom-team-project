<?php
require_once __DIR__ . '/../core/core.php';
require_login();
require_once __DIR__ . '/../controllers/CartController.php';

$checkout = $_SESSION['checkout'] ?? null;
if (!$checkout) redirect('views/checkout.php');

$cc = new CartController();
$items = $cc->getCartItems(get_cart_key());
$subtotal = $cc->getCartTotal(get_cart_key());
$total = round($subtotal + $checkout['delivery']['fee'], 2);

// If the cart changed after checkout, go back so the amount is right
if (!$items || abs($total - $checkout['amount']) > 0.009) {
    unset($_SESSION['checkout']);
    flash('error', 'Your cart changed. Please confirm your delivery details again.');
    redirect('views/checkout.php');
}
$d = $checkout['delivery'];
$pageTitle = 'Payment';
require __DIR__ . '/layout/header.php';
?>

<div class="max-w-3xl mx-auto">
    <a href="<?= url('views/checkout.php') ?>" class="text-sm text-muted hover:underline">← Change delivery details</a>
    <h1 class="font-display font-extrabold text-3xl mt-2 mb-6">Review and pay</h1>

    <section class="card p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-muted"><th class="py-2">Item</th><th class="text-right">Qty</th><th class="text-right">Unit price</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr class="border-t border-line align-top">
                        <td class="py-3"><p class="font-medium"><?= e($it['name']) ?></p><?php include __DIR__ . '/partials/design_summary.php'; ?></td>
                        <td class="py-3 text-right tabular-nums"><?= (int) $it['quantity'] ?></td>
                        <td class="py-3 text-right tabular-nums"><?= money($it['unit_price']) ?></td>
                        <td class="py-3 text-right tabular-nums"><?= money($it['line_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="tabular-nums">
                    <tr class="border-t border-line"><td colspan="3" class="pt-3 text-right text-muted">Subtotal</td><td class="pt-3 text-right"><?= money($subtotal) ?></td></tr>
                    <tr><td colspan="3" class="text-right text-muted"><?= $d['option'] === 'express' ? 'Express' : 'Standard' ?> delivery</td><td class="text-right"><?= money($d['fee']) ?></td></tr>
                    <tr><td colspan="3" class="pt-2 text-right font-semibold">Total</td><td class="pt-2 text-right font-display font-extrabold text-xl"><?= money($total) ?></td></tr>
                </tfoot>
            </table>
        </div>
        <p class="text-sm text-muted mt-4">Delivering to <?= e($d['recipient_name']) ?> (<?= e($d['recipient_phone']) ?>), <?= e($d['address']) ?>, <?= e($d['city']) ?>.</p>
    </section>

    <section class="card p-6 mt-6 flex flex-col gap-3">
        <?php if (PAYSTACK_PUBLIC_KEY === ''): ?>
            <div class="alert alert-error">Paystack test keys are not set yet. Add PAYSTACK_PUBLIC_KEY and PAYSTACK_SECRET_KEY (see the README).</div>
        <?php else: ?>
            <button type="button" id="pay-button" class="btn btn-primary self-start text-base !px-6 !py-3">Pay <?= money($total) ?> with Paystack</button>
            <p class="text-sm text-muted">Test mode: no real money moves. Use Paystack's test card or test mobile money in the window that opens.</p>
        <?php endif; ?>
    </section>
</div>

<?php if (PAYSTACK_PUBLIC_KEY !== ''): ?>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
    document.getElementById('pay-button').addEventListener('click', function () {
        const button = this;
        button.disabled = true;
        button.textContent = 'Opening Paystack…';
        const handler = PaystackPop.setup({
            key: <?= json_encode(PAYSTACK_PUBLIC_KEY) ?>,
            email: <?= json_encode($_SESSION['customer_email']) ?>,
            amount: <?= (int) round($total * 100) ?>,          // pesewas: GH₵1 = 100
            currency: 'GHS',
            ref: <?= json_encode($checkout['reference']) ?>,   // our reference; the server checks it again
            channels: ['mobile_money', 'card'],
            callback: function (response) {
                // Not trusted on its own: process_payment_action.php verifies with Paystack's API first
                window.location = <?= json_encode(url('actions/process_payment_action.php?ref=')) ?> + encodeURIComponent(response.reference);
            },
            onClose: function () {
                button.disabled = false;
                button.textContent = 'Pay <?= money($total) ?> with Paystack';
            }
        });
        handler.openIframe();
    });
</script>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>
