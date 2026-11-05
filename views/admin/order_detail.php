<?php
require_once __DIR__ . '/../../core/core.php';
require_admin();
require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';
require_once __DIR__ . '/../../controllers/DeliveryController.php';

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

        <form action="<?= url('actions/assign_delivery_action.php') ?>" method="POST" class="card p-5 flex flex-col gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= (int) $order['order_id'] ?>">
            <label class="field"><span class="label">Delivery rider</span>
                <?php if (!$staff): ?>
                    <span class="text-sm text-muted">No delivery staff yet. Give an account role 3 in the database (see README).</span>
                <?php else: ?>
                    <select name="staff_id" class="input">
                        <option value="">Choose…</option>
                        <?php foreach ($staff as $s): ?>
                            <option value="<?= (int) $s['customer_id'] ?>" <?= (int) $order['assigned_to'] === (int) $s['customer_id'] ? 'selected' : '' ?>><?= e($s['full_name']) ?> · <?= e($s['phone']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?></label>
            <?php if ($staff): ?><button class="btn btn-dark">Assign rider</button><?php endif; ?>
        </form>

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
