<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

$cc = new CartController();
$items = $cc->getCartItems(get_cart_key());
$total = $cc->getCartTotal(get_cart_key());
$pageTitle = 'Your cart';
require __DIR__ . '/layout/header.php';
?>

<h1 class="font-display font-extrabold text-3xl mb-6">Your cart</h1>

<?php if (!$items): ?>
    <div class="card p-10 text-center">
        <p class="text-lg">Your cart is empty.</p>
        <div class="flex justify-center gap-3 mt-4">
            <a href="<?= url('views/customize.php') ?>" class="btn btn-primary">Design your packaging</a>
            <a href="<?= url('views/all_products.php') ?>" class="btn btn-ghost">Browse products</a>
        </div>
    </div>
<?php else: ?>
<div class="grid lg:grid-cols-[1fr_320px] gap-6 items-start">
    <!-- One form for the table; the two buttons send it to different actions (remove / update quantities) -->
    <form method="POST" action="<?= url('actions/update_qty_action.php') ?>" class="card overflow-hidden min-w-0" data-validate="cart">
        <?= csrf_field() ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-sand text-left text-muted">
                    <tr><th class="p-3 w-10"><span class="sr-only">Remove</span></th><th class="p-3">Item</th><th class="p-3 w-28">Quantity</th><th class="p-3 text-right">Unit price</th><th class="p-3 text-right">Total</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr class="border-t border-line align-top">
                        <td class="p-3"><input type="checkbox" name="remove[]" value="<?= (int) $it['cart_id'] ?>" aria-label="Select <?= e($it['name']) ?> to remove" class="w-4 h-4 accent-[#c2410c]"></td>
                        <td class="p-3">
                            <div class="flex gap-3">
                                <img src="<?= $it['logo_file'] ? url('images/designs/' . rawurlencode($it['logo_file'])) : product_image_url($it['image']) ?>" alt="" class="w-14 h-14 rounded-lg object-<?= $it['logo_file'] ? 'contain bg-sand p-1' : 'cover' ?>">
                                <div>
                                    <p class="font-medium"><?= e($it['name']) ?><?= $it['customization_id'] ? ' <span class="badge status-processing ml-1">Your design</span>' : '' ?></p>
                                    <?php include __DIR__ . '/partials/design_summary.php'; ?>
                                </div>
                            </div>
                        </td>
                        <td class="p-3">
                            <?php if ($it['customization_id']): ?>
                                <span class="tabular-nums"><?= (int) $it['quantity'] ?></span>
                                <span class="block text-xs text-muted">Priced for this quantity. Redesign to change it.</span>
                            <?php else: ?>
                                <input type="number" name="qty[<?= (int) $it['cart_id'] ?>]" value="<?= (int) $it['quantity'] ?>" min="1" max="10000" step="1" class="input !py-1.5 tabular-nums" aria-label="Quantity of <?= e($it['name']) ?>">
                            <?php endif; ?>
                        </td>
                        <td class="p-3 text-right tabular-nums"><?= money($it['unit_price']) ?></td>
                        <td class="p-3 text-right tabular-nums font-medium"><?= money($it['line_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap gap-3 justify-between p-4 border-t border-line">
            <button type="submit" formaction="<?= url('actions/remove_from_cart_action.php') ?>" class="btn btn-ghost" data-needs-selection>Remove selected</button>
            <div class="flex gap-3">
                <a href="<?= url('views/all_products.php') ?>" class="btn btn-ghost">Continue shopping</a>
                <button type="submit" class="btn btn-dark">Update quantities</button>
            </div>
        </div>
    </form>

    <aside class="card p-6 flex flex-col gap-3">
        <p class="label">Order summary</p>
        <div class="flex justify-between tabular-nums"><span>Subtotal</span><span><?= money($total) ?></span></div>
        <p class="text-sm text-muted">Delivery is added at checkout: standard or express.</p>
        <a href="<?= url('views/checkout.php') ?>" class="btn btn-primary mt-2">Checkout</a>
        <?php if (!is_logged_in()): ?><p class="text-xs text-muted text-center">You'll be asked to log in. Your cart stays.</p><?php endif; ?>
    </aside>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>
