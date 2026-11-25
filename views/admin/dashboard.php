<?php
require_once __DIR__ . '/../../core/core.php';
require_admin();
require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/DemoDataController.php';

$oc = new OrderController();
$demo = (new DemoDataController())->status();
$s = $oc->getSummary();
$recent = array_slice($oc->getAllOrders(), 0, 8);
$pageTitle = 'Admin dashboard';
require __DIR__ . '/../layout/header.php';
?>

<h1 class="font-display font-extrabold text-3xl mb-6">Dashboard</h1>

<!-- Demo data for presentations: tagged records that can be removed without touching real orders -->
<section class="card p-5 mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <p class="label">Demo data</p>
        <?php if ((int) $demo['orders'] > 0): ?>
            <p class="text-sm mt-1"><b><?= (int) $demo['orders'] ?> demo orders</b> from <?= (int) $demo['customers'] ?> businesses are loaded (<?= money($demo['revenue']) ?> in sales). They are marked DEMO and can be removed at any time.</p>
        <?php else: ?>
            <p class="text-sm mt-1">Fill the admin pages with sample businesses and orders at every stage (paid to delivered), delivered by Packe riders, Uber, Yango and Bolt.</p>
        <?php endif; ?>
    </div>
    <form action="<?= url('actions/demo_data_action.php') ?>" method="POST"
          <?= (int) $demo['orders'] > 0 ? 'onsubmit="return confirm(\'Remove all demo orders and businesses? Real orders are not touched.\');"' : '' ?>>
        <?= csrf_field() ?>
        <?php if ((int) $demo['orders'] > 0): ?>
            <input type="hidden" name="op" value="remove">
            <button class="btn btn-ghost">Remove demo data</button>
        <?php else: ?>
            <input type="hidden" name="op" value="load">
            <button class="btn btn-primary">Load demo data</button>
        <?php endif; ?>
    </form>
</section>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ([['Orders', (int) $s['orders'], ''], ['Revenue', money($s['revenue']), ''], ['To prepare', (int) $s['to_prepare'], 'Paid, processing or ready'], ['Out for delivery', (int) $s['on_the_road'], '']] as [$label, $value, $hint]): ?>
        <div class="card p-5">
            <p class="label"><?= $label ?></p>
            <p class="font-display font-extrabold text-3xl tabular-nums mt-1"><?= $value ?></p>
            <?php if ($hint): ?><p class="text-xs text-muted mt-1"><?= $hint ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<section class="card p-6 mt-6">
    <div class="flex justify-between items-end mb-4">
        <h2 class="font-display font-bold text-xl">Latest orders</h2>
        <a href="<?= url('views/admin/orders.php') ?>" class="text-packe-600 font-semibold hover:underline">All orders</a>
    </div>
    <?php if (!$recent): ?>
        <p class="text-muted">No orders yet.</p>
    <?php else: ?>
        <?php $orders = $recent; require __DIR__ . '/../partials/orders_table.php'; ?>
    <?php endif; ?>
</section>

<div class="grid sm:grid-cols-3 gap-4 mt-6">
    <a href="<?= url('views/admin/product.php') ?>" class="card p-5 hover:border-ink"><p class="font-semibold">Manage products</p><p class="text-sm text-muted">Add or edit products and prices</p></a>
    <a href="<?= url('views/admin/category.php') ?>" class="card p-5 hover:border-ink"><p class="font-semibold">Manage categories</p><p class="text-sm text-muted">Group products for the shop</p></a>
    <a href="<?= url('views/admin/orders.php?status=paid') ?>" class="card p-5 hover:border-ink"><p class="font-semibold">New paid orders</p><p class="text-sm text-muted">Start production and assign riders</p></a>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
