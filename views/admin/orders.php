<?php
require_once __DIR__ . '/../../core/core.php';
require_admin();
require_once __DIR__ . '/../../controllers/OrderController.php';

$status = (string) ($_GET['status'] ?? '');
$orders = (new OrderController())->getAllOrders($status);
$pageTitle = 'Orders';
require __DIR__ . '/../layout/header.php';
?>

<h1 class="font-display font-extrabold text-3xl mb-4">Orders</h1>

<nav class="flex flex-wrap gap-2 mb-6" aria-label="Filter by status">
    <a href="<?= url('views/admin/orders.php') ?>" class="chip <?= $status === '' ? 'chip-active' : '' ?>">All</a>
    <?php foreach (OrderController::statuses() as $st): ?>
        <a href="<?= url('views/admin/orders.php?status=' . $st) ?>" class="chip <?= $status === $st ? 'chip-active' : '' ?>"><?= e(OrderController::statusLabel($st)) ?></a>
    <?php endforeach; ?>
</nav>

<section class="card p-6">
    <?php if (!$orders): ?>
        <p class="text-muted">No orders<?= $status ? ' with this status' : ' yet' ?>.</p>
    <?php else: ?>
        <?php require __DIR__ . '/../partials/orders_table.php'; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
