<?php
require_once __DIR__ . '/../core/core.php';
require_login();
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/DeliveryPricing.php';
require_once __DIR__ . '/../controllers/ProController.php';

$cc = new CartController();
$items = $cc->getCartItems(get_cart_key());
if (!$items) {
    flash('error', 'Your cart is empty.');
    redirect('views/cart.php');
}
$subtotal = $cc->getCartTotal(get_cart_key());
$pro = new ProController();
$isPro = $pro->isActive((int) $_SESSION['customer_id']);
$discount = $pro->discountFor($subtotal, (int) $_SESSION['customer_id']);
$me =(new CustomerController())->getCustomer((int) $_SESSION['customer_id']);
$chosen = ($_SESSION['old']['delivery_option'] ?? 'standard') === 'express' ? 'express' : 'standard';

// Free map pieces: Leaflet (map library) + OpenStreetMap (map pictures + address search). See js/delivery-map.js.
$extraHead = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">';
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>'
              . '<script src="' . e(asset_url('js/delivery-map.js')) . '"></script>';
$pageTitle = 'Checkout';
require __DIR__ . '/layout/header.php';
?>

<h1 class="font-display font-extrabold text-3xl mb-6">Checkout</h1>

<form id="checkout-form" action="<?= url('actions/checkout_action.php') ?>" method="POST" class="grid lg:grid-cols-[1fr_340px] gap-6 items-start" novalidate data-validate="checkout">
    <?= csrf_field() ?>
    <div class="flex flex-col gap-6 min-w-0">

        <!-- 1. Where to deliver: the map works out the distance from Packe and the delivery prices -->
        <section class="card p-6 flex flex-col gap-4" aria-labelledby="where-title">
            <div>
                <h2 id="where-title" class="font-display font-bold text-xl"><span class="step-dot">1</span>Where should we deliver?</h2>
                <p class="text-sm text-muted mt-1">We deliver from our workshop at <?= e(DeliveryPricing::ORIGIN_LABEL) ?>. Your delivery price depends on the road distance.</p>
            </div>

            <div class="flex flex-wrap gap-2 relative" id="place-search">
                <label for="place-q" class="sr-only">Search for your area, street or landmark</label>
                <div class="relative flex-1 min-w-[220px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input id="place-q" type="search" class="input !pl-9" placeholder="Search your area, street or landmark" autocomplete="off" aria-controls="place-results">
                </div>
                <button type="button" id="place-go" class="btn btn-dark">Search</button>
                <button type="button" id="locate-me" class="btn btn-ghost">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/><circle cx="12" cy="12" r="8"/></svg>
                    Use my location
                </button>
                <ul id="place-results" class="place-results" role="listbox" aria-label="Places found" hidden></ul>
            </div>

            <div class="map-frame">
                <div id="delivery-map" class="delivery-map" role="application" aria-label="Map: tap to set your delivery spot"
                     data-origin-lat="<?= e(DELIVERY_ORIGIN_LAT) ?>" data-origin-lng="<?= e(DELIVERY_ORIGIN_LNG) ?>"
                     data-origin-label="<?= e(DeliveryPricing::ORIGIN_LABEL) ?>"
                     data-quote-url="<?= e(url('actions/delivery_quote.php')) ?>"></div>
                <div id="distance-pill" class="distance-pill" role="status" aria-live="polite">
                    <svg class="w-4 h-4 text-packe-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                    <span id="distance-text">Search, use your location, or tap the map</span>
                </div>
            </div>
            <p class="text-sm text-muted -mt-1">Tip: drag the orange pin onto your exact gate or building.</p>
            <small class="field-error -mt-3" data-error-for="delivery_location"></small>

            <input type="hidden" name="delivery_lat" id="delivery_lat" value="<?= old('delivery_lat') ?>">
            <input type="hidden" name="delivery_lng" id="delivery_lng" value="<?= old('delivery_lng') ?>">
        </section>

        <!-- 2. Who receives it -->
        <section class="card p-6 grid sm:grid-cols-2 gap-4">
            <h2 class="sm:col-span-2 font-display font-bold text-xl"><span class="step-dot">2</span>Who receives the order?</h2>
            <label class="field"><span>Recipient name</span>
                <input class="input" name="recipient_name" value="<?= old('recipient_name', $me['full_name']) ?>" maxlength="100" required>
                <small class="field-error" data-error-for="recipient_name"></small></label>
            <label class="field"><span>Contact number</span>
                <input class="input" type="tel" name="recipient_phone" value="<?= old('recipient_phone', $me['phone']) ?>" maxlength="15" required>
                <small class="field-error" data-error-for="recipient_phone"></small></label>
            <label class="field sm:col-span-2"><span>Delivery address</span>
                <input class="input" name="address" id="address" value="<?= old('address', (string) $me['address']) ?>" maxlength="255" placeholder="House number, street, landmark" required>
                <small class="field-error" data-error-for="address"></small></label>
            <label class="field"><span>City or town</span>
                <input class="input" name="city" id="city" value="<?= old('city', $me['city']) ?>" maxlength="60" required>
                <small class="field-error" data-error-for="city"></small></label>
        </section>

        <!-- 3. How fast: prices fill in once the map knows the distance -->
        <section class="card p-6">
            <h2 class="font-display font-bold text-xl mb-4"><span class="step-dot">3</span>Delivery speed</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                <?php foreach ([
                    'standard' => ['Standard delivery', 'Reliable and affordable for regular orders.', 'GH₵' . number_format(DELIVERY_BASE, 0) . ' + GH₵' . number_format(DELIVERY_PER_KM, 2) . ' per km'],
                    'express'  => ['Express rush', 'Priority production and delivery for urgent orders.', rtrim(rtrim(number_format(DELIVERY_EXPRESS_FACTOR, 2), '0'), '.') . '× the standard price'],
                ] as $opt => [$t, $d, $how]): ?>
                    <label class="speed-card">
                        <input type="radio" name="delivery_option" value="<?= $opt ?>" <?= $chosen === $opt ? 'checked' : '' ?> class="sr-only">
                        <span class="flex items-start justify-between gap-3">
                            <span class="font-semibold"><?= $t ?></span>
                            <span class="speed-check" aria-hidden="true"></span>
                        </span>
                        <span class="speed-price is-empty tabular-nums" data-price-for="<?= $opt ?>">Pick a spot</span>
                        <span class="text-sm text-muted"><?= $d ?></span>
                        <span class="text-xs text-muted"><?= e($how) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <aside class="card p-6 flex flex-col gap-3 lg:sticky lg:top-24">
        <p class="label">Order summary</p>
        <ul class="text-sm divide-y divide-line">
            <?php foreach ($items as $it): ?>
                <li class="py-2 flex justify-between gap-3"><span><?= e($it['name']) ?> × <?= (int) $it['quantity'] ?></span><span class="tabular-nums"><?= money($it['line_total']) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <div class="flex justify-between tabular-nums border-t border-line pt-3"><span>Subtotal</span><span id="subtotal" data-value="<?= $subtotal ?>"><?= money($subtotal) ?></span></div>
        <?php if ($isPro): ?>
            <div class="flex justify-between tabular-nums text-packe-600 font-semibold"><span>Packe Pro: 10% off</span><span id="pro-discount" data-value="<?= $discount ?>">−<?= money($discount) ?></span></div>
        <?php endif; ?>
        <div class="flex justify-between gap-3 tabular-nums"><span>Delivery <span id="delivery-km" class="text-muted text-xs"></span></span><span id="delivery-fee" class="text-right text-muted">Choose a spot</span></div>
        <div class="flex justify-between tabular-nums font-display font-extrabold text-xl"><span>Total</span><span id="grand-total"><?= money($subtotal - $discount) ?></span></div>
        <?php if (!$isPro): ?>
            <a href="<?= url('views/pro.php') ?>" class="pro-hint">With <b>Packe Pro</b> this order would be <b><?= money($subtotal * ProController::DISCOUNT_RATE) ?></b> cheaper, with free standard delivery →</a>
        <?php endif; ?>
        <button class="btn btn-primary mt-2">Continue to payment</button>
        <p class="text-xs text-muted">You'll pay securely in Paystack's window by mobile money or card.</p>
    </aside>
</form>

<?php forget_input(); require __DIR__ . '/layout/footer.php'; ?>
