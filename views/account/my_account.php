<?php
require_once __DIR__ . '/../../core/core.php';
require_login();
require_once __DIR__ . '/../../controllers/CustomerController.php';
require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/CustomizationController.php';

$id = (int) $_SESSION['customer_id'];
$me = (new CustomerController())->getCustomer($id);
$orders = (new OrderController())->getOrdersByCustomer($id);
$designs = (new CustomizationController())->getDesignsByCustomer($id);
$pageTitle = 'My account';
require __DIR__ . '/../layout/header.php';
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="font-display font-extrabold text-3xl">My account</h1>
        <p class="text-muted">Member since <?= e(date('F Y', strtotime($me['created_at']))) ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= url('views/account/edit_account.php') ?>" class="btn btn-ghost">Edit details</a>
        <a href="<?= url('views/account/change_pass.php') ?>" class="btn btn-ghost">Change password</a>
    </div>
</div>

<div class="grid lg:grid-cols-[320px_1fr] gap-6 items-start">
    <section class="card p-6 text-sm flex flex-col gap-3">
        <p class="label">Your details</p>
        <p><span class="text-muted block">Name</span><?= e($me['full_name']) ?></p>
        <p><span class="text-muted block">Email</span><?= e($me['email']) ?></p>
        <p><span class="text-muted block">Phone</span><?= e($me['phone']) ?></p>
        <?php if ($me['business_name']): ?><p><span class="text-muted block">Business</span><?= e($me['business_name']) ?></p><?php endif; ?>
        <p><span class="text-muted block">City</span><?= e($me['city']) ?></p>
        <?php if ($me['address']): ?><p><span class="text-muted block">Address</span><?= e($me['address']) ?></p><?php endif; ?>
        <a href="<?= url('views/account/delete_account.php') ?>" class="text-red-700 hover:underline mt-2">Delete my account</a>
    </section>

    <div class="flex flex-col gap-6 min-w-0">
        <section class="card p-6">
            <h2 class="font-display font-bold text-xl mb-4">My orders</h2>
            <?php if (!$orders): ?>
                <p class="text-muted">No orders yet. <a href="<?= url('views/customize.php') ?>" class="text-packe-600 font-semibold hover:underline">Design your first package</a>.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-muted"><th class="py-2">Invoice</th><th>Date</th><th>Delivery</th><th>Status</th><th class="text-right">Total</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr class="border-t border-line">
                                <td class="py-3 font-medium tabular-nums"><?= e($o['invoice_no']) ?></td>
                                <td><?= e(date('j M Y', strtotime($o['created_at']))) ?></td>
                                <td><?= e(ucfirst((string) $o['delivery_option'])) ?></td>
                                <td><span class="badge status-<?= e($o['order_status']) ?>"><?= e(OrderController::statusLabel($o['order_status'])) ?></span></td>
                                <td class="text-right tabular-nums"><?= money($o['total_amount']) ?></td>
                                <td class="text-right"><a href="<?= url('views/account/order.php?order_id=' . (int) $o['order_id']) ?>" class="text-packe-600 font-semibold hover:underline">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card p-6">
            <h2 class="font-display font-bold text-xl mb-4">My designs</h2>
            <?php if (!$designs): ?>
                <p class="text-muted">Designs you save in the 3D designer appear here.</p>
            <?php else: ?>
                <ul class="divide-y divide-line">
                    <?php foreach ($designs as $it): ?>
                        <li class="py-3 flex flex-wrap justify-between gap-2">
                            <div>
                                <p class="font-medium"><?= e($it['product_name']) ?> · <?= (int) $it['quantity'] ?> <?= e(DesignTypes::nounPlural($it['design_type'])) ?></p>
                                <?php include __DIR__ . '/../partials/design_summary.php'; ?>
                            </div>
                            <p class="tabular-nums text-sm"><?= money($it['unit_price']) ?> each · <?= e(date('j M Y', strtotime($it['created_at']))) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
