<?php
/** Admin: Packe's delivery riders, their workload, orders waiting for a rider, and adding a new rider. */
require_once __DIR__ . '/../../core/core.php';
require_admin();
require_once __DIR__ . '/../../controllers/DeliveryController.php';

$dc = new DeliveryController();
$riders = $dc->getRiders();
$waiting = $dc->getUnassigned();
$pageTitle = 'Riders';
require __DIR__ . '/../layout/header.php';
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="font-display font-extrabold text-3xl">Riders</h1>
        <p class="text-muted">Packe’s own delivery team. Assign a rider to any order delivered by Packe; partner orders (Uber, Yango, Bolt) are booked on the order page.</p>
    </div>
</div>

<div class="grid xl:grid-cols-[minmax(0,1fr)_340px] gap-6 items-start">
    <div class="flex flex-col gap-6 min-w-0">
        <section class="card p-6 min-w-0">
            <h2 class="font-display font-bold text-xl mb-4">Our riders <span class="text-muted text-base font-normal">· <?= count($riders) ?></span></h2>
            <?php if (!$riders): ?>
                <p class="text-muted">No riders yet. Add one with the form, or open <span class="font-mono">/setup.php</span> to add the starter team.</p>
            <?php else: ?>
                <div class="grid sm:grid-cols-2 gap-3">
                    <?php foreach ($riders as $r): $busy = (int) $r['active']; ?>
                        <article class="rounded-xl border border-line p-4 flex gap-3 items-start">
                            <span class="rider-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($r['full_name'], 0, 1))) ?></span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold"><?= e($r['full_name']) ?></p>
                                <p class="text-sm text-muted"><?= e($r['city']) ?> · <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $r['phone'])) ?>" class="hover:underline"><?= e($r['phone']) ?></a></p>
                                <p class="text-sm mt-1">
                                    <span class="badge <?= $busy ? 'status-out_for_delivery' : 'status-delivered' ?>"><?= $busy ? $busy . ' on the go' : 'Available' ?></span>
                                    <span class="text-muted text-xs ml-1"><?= (int) $r['delivered'] ?> delivered</span>
                                </p>
                                <?php if (!(int) $r['can_log_in']): ?><p class="text-xs text-muted mt-1">No login yet: set a password in <a href="<?= url('views/admin/businesses.php?edit_id=' . (int) $r['customer_id']) ?>" class="text-packe-600 hover:underline">Businesses</a> to let them use My deliveries.</p><?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="card p-6 min-w-0">
            <h2 class="font-display font-bold text-xl mb-1">Waiting for a rider <span class="text-muted text-base font-normal">· <?= count($waiting) ?></span></h2>
            <p class="text-sm text-muted mb-4">Paid orders delivered by Packe with no rider yet, express first.</p>
            <?php if (!$waiting): ?>
                <p class="text-muted">Every Packe delivery has a rider.</p>
            <?php else: ?>
                <div class="flex flex-col gap-3">
                    <?php foreach ($waiting as $w): ?>
                        <form action="<?= url('actions/assign_delivery_action.php') ?>" method="POST" class="rounded-xl border border-line p-4 flex flex-wrap items-center gap-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= (int) $w['order_id'] ?>">
                            <input type="hidden" name="back" value="riders">
                            <div class="min-w-[180px] flex-1">
                                <a href="<?= url('views/admin/order_detail.php?order_id=' . (int) $w['order_id']) ?>" class="font-semibold tabular-nums hover:underline"><?= e($w['invoice_no']) ?></a>
                                <?php if ($w['delivery_option'] === 'express'): ?><span class="badge status-out_for_delivery ml-1">Express</span><?php endif; ?>
                                <p class="text-sm text-muted"><?= e($w['recipient_name']) ?> · <?= e($w['city']) ?><?= $w['distance_km'] ? ' · ' . e(number_format((float) $w['distance_km'], 1)) . ' km' : '' ?></p>
                            </div>
                            <select name="staff_id" class="input !w-auto min-w-[220px]" required aria-label="Rider for <?= e($w['invoice_no']) ?>">
                                <option value="">Choose a rider…</option>
                                <?php foreach ($riders as $r): ?>
                                    <option value="<?= (int) $r['customer_id'] ?>"><?= e($r['full_name']) ?> · <?= e($r['city']) ?><?= (int) $r['active'] ? ' (' . (int) $r['active'] . ' on the go)' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-dark">Assign</button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <form action="<?= url('actions/add_rider_action.php') ?>" method="POST" class="card p-6 flex flex-col gap-4" novalidate>
        <?= csrf_field() ?>
        <h2 class="font-display font-bold text-xl">Add a rider</h2>
        <label class="field"><span>Full name</span>
            <input class="input" name="full_name" value="<?= old('full_name') ?>" maxlength="100" placeholder="e.g. Kwame Owusu" required></label>
        <label class="field"><span>Phone number</span>
            <input class="input" type="tel" name="phone" value="<?= old('phone') ?>" maxlength="15" placeholder="024 123 4567" required></label>
        <label class="field"><span>Area they cover</span>
            <input class="input" name="area" value="<?= old('area') ?>" maxlength="60" placeholder="e.g. Adenta" required></label>
        <button class="btn btn-primary">Add rider</button>
        <p class="text-xs text-muted">The rider appears in every order’s rider list straight away. To let them log in to My deliveries, set a password for them in Businesses.</p>
    </form>
</div>

<?php forget_input(); require __DIR__ . '/../layout/footer.php'; ?>
