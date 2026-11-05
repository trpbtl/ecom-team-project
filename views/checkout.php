<?php
require_once __DIR__ . '/../core/core.php';
require_login();
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/OrderController.php';

$cc = new CartController();
$items = $cc->getCartItems(get_cart_key());
if (!$items) {
    flash('error', 'Your cart is empty.');
    redirect('views/cart.php');
}
$subtotal = $cc->getCartTotal(get_cart_key());
$me = (new CustomerController())->getCustomer((int) $_SESSION['customer_id']);
$fees = ['standard' => OrderController::deliveryFee('standard'), 'express' => OrderController::deliveryFee('express')];
$chosen = ($_SESSION['old']['delivery_option'] ?? 'standard') === 'express' ? 'express' : 'standard';
$pageTitle = 'Checkout';
require __DIR__ . '/layout/header.php';
?>

<h1 class="font-display font-extrabold text-3xl mb-6">Checkout</h1>

<form id="checkout-form" action="<?= url('actions/checkout_action.php') ?>" method="POST" class="grid lg:grid-cols-[1fr_340px] gap-6 items-start" novalidate data-validate="checkout">
    <?= csrf_field() ?>
    <div class="flex flex-col gap-6 min-w-0">
        <section class="card p-6 grid sm:grid-cols-2 gap-4">
            <h2 class="sm:col-span-2 font-display font-bold text-xl">Who receives the order?</h2>
            <label class="field"><span>Recipient name</span>
                <input class="input" name="recipient_name" value="<?= old('recipient_name', $me['full_name']) ?>" maxlength="100" required>
                <small class="field-error" data-error-for="recipient_name"></small></label>
            <label class="field"><span>Contact number</span>
                <input class="input" type="tel" name="recipient_phone" value="<?= old('recipient_phone', $me['phone']) ?>" maxlength="15" required>
                <small class="field-error" data-error-for="recipient_phone"></small></label>
            <label class="field sm:col-span-2"><span>Delivery address</span>
                <input class="input" name="address" value="<?= old('address', (string) $me['address']) ?>" maxlength="255" placeholder="House number, street, landmark" required>
                <small class="field-error" data-error-for="address"></small></label>
            <label class="field"><span>City or town</span>
                <input class="input" name="city" value="<?= old('city', $me['city']) ?>" maxlength="60" required>
                <small class="field-error" data-error-for="city"></small></label>
        </section>

        <section class="card p-6">
            <h2 class="font-display font-bold text-xl mb-4">Delivery speed</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                <?php foreach (['standard' => ['Standard delivery', 'Reliable and affordable for regular orders.'], 'express' => ['Express rush', 'Priority production and delivery for urgent orders.']] as $opt => [$t, $d]): ?>
                    <label class="chip !items-start !p-4 cursor-pointer has-[:checked]:border-[#c2410c] has-[:checked]:shadow-[inset_0_0_0_1px_#c2410c]">
                        <input type="radio" name="delivery_option" value="<?= $opt ?>" data-fee="<?= $fees[$opt] ?>" <?= $chosen === $opt ? 'checked' : '' ?> class="mt-1 accent-[#c2410c]">
                        <span><span class="block font-semibold"><?= $t ?> · <span class="tabular-nums"><?= money($fees[$opt]) ?></span></span><span class="text-sm text-muted"><?= $d ?></span></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <aside class="card p-6 flex flex-col gap-3">
        <p class="label">Order summary</p>
        <ul class="text-sm divide-y divide-line">
            <?php foreach ($items as $it): ?>
                <li class="py-2 flex justify-between gap-3"><span><?= e($it['name']) ?> × <?= (int) $it['quantity'] ?></span><span class="tabular-nums"><?= money($it['line_total']) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <div class="flex justify-between tabular-nums border-t border-line pt-3"><span>Subtotal</span><span id="subtotal" data-value="<?= $subtotal ?>"><?= money($subtotal) ?></span></div>
        <div class="flex justify-between tabular-nums"><span>Delivery</span><span id="delivery-fee"><?= money($fees[$chosen]) ?></span></div>
        <div class="flex justify-between tabular-nums font-display font-extrabold text-xl"><span>Total</span><span id="grand-total"><?= money($subtotal + $fees[$chosen]) ?></span></div>
        <button class="btn btn-primary mt-2">Continue to payment</button>
        <p class="text-xs text-muted">You'll pay securely in Paystack's window by mobile money or card.</p>
    </aside>
</form>

<?php forget_input(); require __DIR__ . '/layout/footer.php'; ?>
