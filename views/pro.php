<?php
/** Packe Pro: the monthly membership page. Anyone can read it; paying needs an account. */
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProController.php';

$pro = new ProController();
$me = (int) ($_SESSION['customer_id'] ?? 0);
$active = $pro->getActive($me);
$history = $me ? $pro->getHistory($me) : [];

// A fresh reference for this visit's payment, checked again on the server after Paystack
if ($me && PAYSTACK_PUBLIC_KEY !== '') {
    $_SESSION['pro_checkout'] = $pro->startCheckout();
} elseif (!$me) {
    $_SESSION['after_login'] = url('views/pro.php');   // come back here after logging in
}
$pageTitle = 'Packe Pro';
require __DIR__ . '/layout/header.php';
?>

<section class="pro-hero">
    <div class="max-w-2xl">
        <p class="pro-badge">PACKE PRO</p>
        <h1 class="font-display font-extrabold text-4xl md:text-5xl mt-3 leading-tight">Pay less on every order.</h1>
        <p class="mt-3 text-lg opacity-90">For businesses that order packaging every month: 10% off all packaging and free standard delivery, for <?= money(ProController::PRICE) ?> a month.</p>
    </div>
    <div class="pro-price">
        <p class="font-display font-extrabold text-5xl tabular-nums"><?= money(ProController::PRICE) ?></p>
        <p class="opacity-80">per month · cancel any time (it simply runs out)</p>
    </div>
</section>

<div class="grid md:grid-cols-3 gap-4 mt-8">
    <div class="card p-6"><p class="font-display font-extrabold text-3xl text-packe-600">10% off</p><p class="mt-1 font-semibold">All packaging</p><p class="text-sm text-muted mt-1">Taken off your packaging subtotal at checkout, on every order.</p></div>
    <div class="card p-6"><p class="font-display font-extrabold text-3xl text-packe-600">Free</p><p class="mt-1 font-semibold">Standard delivery</p><p class="text-sm text-muted mt-1">Anywhere we deliver, up to 150 km from our workshop. Express is still available at its normal price.</p></div>
    <div class="card p-6"><p class="font-display font-extrabold text-3xl text-packe-600">30 days</p><p class="mt-1 font-semibold">Per payment</p><p class="text-sm text-muted mt-1">Renew early and the new month starts when the current one ends, so you never lose days.</p></div>
</div>

<section class="card p-6 mt-6 flex flex-col md:flex-row md:items-center gap-4 justify-between">
    <?php if ($active): ?>
        <div>
            <p class="label">Your membership</p>
            <p class="font-display font-bold text-2xl mt-1">Pro is active until <?= e(date('j F Y', strtotime($active['ends_at']))) ?></p>
            <p class="text-muted text-sm">Your discount and free delivery are applied automatically at checkout.</p>
        </div>
    <?php else: ?>
        <div>
            <p class="label">Is it worth it?</p>
            <p class="font-display font-bold text-2xl mt-1">Pro pays for itself from about <?= money(ProController::PRICE / ProController::DISCOUNT_RATE) ?> of packaging a month</p>
            <p class="text-muted text-sm">Or sooner: a single standard delivery is <?= money(DELIVERY_BASE) ?> or more.</p>
        </div>
    <?php endif; ?>

    <?php if (!$me): ?>
        <a href="<?= url('views/login.php') ?>" class="btn btn-primary !px-6 !py-3">Log in to join</a>
    <?php elseif (is_admin() || is_delivery()): ?>
        <p class="text-sm text-muted">Packe Pro is for customer accounts.</p>
    <?php elseif (PAYSTACK_PUBLIC_KEY === ''): ?>
        <div class="alert alert-error">Paystack test keys are not set yet.</div>
    <?php else: ?>
        <button type="button" id="pro-pay" class="btn btn-primary !px-6 !py-3"><?= $active ? 'Add another month' : 'Join Packe Pro' ?> · <?= money(ProController::PRICE) ?></button>
    <?php endif; ?>
</section>

<?php if ($history): ?>
    <section class="card p-6 mt-6">
        <h2 class="font-display font-bold text-xl mb-3">Membership payments</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-muted"><th class="py-2">Paid</th><th>Covers</th><th>Method</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                <?php foreach ($history as $h): ?>
                    <tr class="border-t border-line">
                        <td class="py-2"><?= e(date('j M Y', strtotime($h['created_at']))) ?></td>
                        <td><?= e(date('j M', strtotime($h['starts_at']))) ?> – <?= e(date('j M Y', strtotime($h['ends_at']))) ?></td>
                        <td><?= e(ucfirst(str_replace('_', ' ', (string) $h['channel']))) ?></td>
                        <td class="text-right tabular-nums"><?= money($h['amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php if ($me && !is_admin() && !is_delivery() && PAYSTACK_PUBLIC_KEY !== ''): ?>
<div id="paid-overlay" class="paid-overlay" role="status" aria-live="assertive" hidden>
    <div class="paid-spinner" aria-hidden="true"></div>
    <h2 class="paid-title">Payment received</h2>
    <p class="paid-text">Confirming your payment with Paystack and starting your Packe Pro membership…</p>
</div>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
    document.getElementById('pro-pay').addEventListener('click', function () {
        const button = this;
        button.disabled = true;
        PaystackPop.setup({
            key: <?= json_encode(PAYSTACK_PUBLIC_KEY) ?>,
            email: <?= json_encode($_SESSION['customer_email']) ?>,
            amount: <?= (int) round(ProController::PRICE * 100) ?>,   // pesewas
            currency: 'GHS',
            ref: <?= json_encode($_SESSION['pro_checkout']['reference']) ?>,
            channels: ['mobile_money', 'card'],
            metadata: { plan: 'Packe Pro (monthly)' },
            callback: function (response) {
                document.getElementById('paid-overlay').hidden = false;
                // Not trusted on its own: the server verifies with Paystack before adding the month
                window.location = <?= json_encode(url('actions/process_pro_payment_action.php?ref=')) ?> + encodeURIComponent(response.reference);
            },
            onClose: function () { button.disabled = false; }
        }).openIframe();
    });
</script>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>
