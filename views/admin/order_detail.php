<?php
require_once __DIR__ . '/../../core/core.php';
require_admin();
require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';
require_once __DIR__ . '/../../controllers/DeliveryController.php';
require_once __DIR__ . '/../../controllers/Couriers.php';

$order = (new OrderController())->getOrderForViewer($_GET['order_id'] ?? null, (int) $_SESSION['customer_id'], true);
if (!$order) {
    flash('error', 'That order was not found.');
    redirect('views/admin/orders.php');
}
$staff = (new CustomerController())->getDeliveryStaff();
$pageTitle = 'Order ' . $order['invoice_no'];
require __DIR__ . '/../layout/header.php';
?>

<a href="<?= url('views/admin/orders.php') ?>" class="text-sm text-muted hover:underline">← All orders</a>
<h1 class="font-display font-extrabold text-3xl mt-2 mb-6">Order <?= e($order['invoice_no']) ?></h1>

<div class="grid lg:grid-cols-[1fr_340px] gap-6 items-start">
    <div class="min-w-0">
        <?php require __DIR__ . '/../partials/invoice.php'; ?>
        <?php $designs = array_filter($order['items'], fn($i) => $i['logo_file']); if ($designs): ?>
            <section class="card p-6 mt-6">
                <h2 class="font-display font-bold text-xl mb-3">Customer artwork for production</h2>
                <div class="flex flex-wrap gap-4">
                    <?php foreach ($designs as $it): ?>
                        <a href="<?= url('images/designs/' . rawurlencode($it['logo_file'])) ?>" target="_blank" rel="noopener" class="block w-32 h-32 bg-sand rounded-xl p-2 border border-line">
                            <img src="<?= url('images/designs/' . rawurlencode($it['logo_file'])) ?>" alt="Logo for <?= e($it['name']) ?>" class="w-full h-full object-contain">
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <aside class="flex flex-col gap-4">
        <form action="<?= url('actions/update_order_status_action.php') ?>" method="POST" class="card p-5 flex flex-col gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= (int) $order['order_id'] ?>">
            <label class="field"><span class="label">Order status</span>
                <select name="order_status" class="input">
                    <?php foreach (OrderController::statuses() as $st): ?>
                        <option value="<?= $st ?>" <?= $order['order_status'] === $st ? 'selected' : '' ?>><?= e(OrderController::statusLabel($st)) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <button class="btn btn-dark">Update status</button>
        </form>

        <?php $courier = Couriers::get($order['carrier'] ?? 'packe'); ?>
        <?php if (Couriers::isPartner($order['carrier'] ?? 'packe')): ?>
        <!-- Partner delivery: book the ride in the partner's app, then save the driver here for the customer -->
        <form action="<?= url('actions/book_partner_action.php') ?>" method="POST" class="card p-5 flex flex-col gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= (int) $order['order_id'] ?>">
            <div class="flex items-center gap-3">
                <img src="<?= asset_url($courier['logo']) ?>" alt="<?= e($courier['name']) ?>" class="w-16 h-11 object-cover rounded-md">
                <div><p class="label">Delivery partner</p><p class="font-semibold"><?= e($courier['name']) ?></p></div>
            </div>
            <ol class="text-sm text-muted list-decimal pl-5 flex flex-col gap-1">
                <li>Book a delivery in the <?= e($courier['short']) ?> app from Packe (Ashesi) to the customer’s pin.</li>
                <li>Copy the driver’s name, phone and trip link below.</li>
            </ol>
            <a href="<?= e($courier['app']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-ghost">Open <?= e($courier['short']) ?> ↗</a>
            <label class="field"><span>Driver’s name</span>
                <input class="input" name="driver_name" value="<?= e($order['driver_name'] ?? '') ?>" maxlength="100" required></label>
            <label class="field"><span>Driver’s phone</span>
                <input class="input" type="tel" name="driver_phone" value="<?= e($order['driver_phone'] ?? '') ?>" maxlength="20" required></label>
            <label class="field"><span>Trip tracking link (optional)</span>
                <input class="input" type="url" name="tracking_url" value="<?= e($order['tracking_url'] ?? '') ?>" maxlength="255" placeholder="https://…"></label>
            <button class="btn btn-dark"><?= empty($order['driver_name']) ? 'Save driver' : 'Update driver' ?></button>
        </form>
        <?php else: ?>
        <form action="<?= url('actions/assign_delivery_action.php') ?>" method="POST" class="card p-5 flex flex-col gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= (int) $order['order_id'] ?>">
            <label class="field"><span class="label">Delivery rider</span>
                <?php if (!$staff): ?>
                    <span class="text-sm text-muted">No riders yet. <a href="<?= url('views/admin/riders.php') ?>" class="text-packe-600 hover:underline">Add one on the Riders page</a>.</span>
                <?php else: ?>
                    <select name="staff_id" class="input">
                        <option value="">Choose…</option>
                        <?php foreach ($staff as $s): ?>
                            <option value="<?= (int) $s['customer_id'] ?>" <?= (int) $order['assigned_to'] === (int) $s['customer_id'] ? 'selected' : '' ?>><?= e($s['full_name']) ?> · <?= e($s['phone']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?></label>
            <?php if ($staff): ?><button class="btn btn-dark">Assign rider</button><?php endif; ?>
            <a href="<?= url('views/admin/riders.php') ?>" class="text-sm text-packe-600 hover:underline">Manage riders →</a>
        </form>
        <?php endif; ?>

        <?php if ($order['delivery_id']): ?>
        <form action="<?= url('actions/update_delivery_status_action.php') ?>" method="POST" class="card p-5 flex flex-col gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="delivery_id" value="<?= (int) $order['delivery_id'] ?>">
            <input type="hidden" name="order_id" value="<?= (int) $order['order_id'] ?>">
            <label class="field"><span class="label">Delivery status</span>
                <select name="status" class="input">
                    <?php foreach (DeliveryController::statuses() as $st): ?>
                        <option value="<?= $st ?>" <?= $order['delivery_status'] === $st ? 'selected' : '' ?>><?= e(DeliveryController::statusLabel($st)) ?></option>
                    <?php endforeach; ?>
                </select></label>
            <button class="btn btn-dark">Update delivery</button>
        </form>
        <?php endif; ?>
    </aside>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
