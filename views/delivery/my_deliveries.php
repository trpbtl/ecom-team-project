<?php
require_once __DIR__ . '/../../core/core.php';
require_delivery();
require_once __DIR__ . '/../../controllers/DeliveryController.php';

$deliveries = (new DeliveryController())->getDeliveriesForStaff((int) $_SESSION['customer_id']);
$next = ['assigned' => 'in_transit', 'in_transit' => 'delivered'];
$nextLabel = ['assigned' => 'Picked up, on the way', 'in_transit' => 'Mark as delivered'];
$pageTitle = 'My deliveries';
require __DIR__ . '/../layout/header.php';
?>

<h1 class="font-display font-extrabold text-3xl mb-1">My deliveries</h1>
<p class="text-muted mb-6">Express orders are listed first.</p>

<?php if (!$deliveries): ?>
    <p class="card p-8 text-center text-muted">No deliveries are assigned to you right now.</p>
<?php else: ?>
    <div class="grid gap-4 md:grid-cols-2">
        <?php foreach ($deliveries as $d): ?>
            <article class="card p-5 flex flex-col gap-3 <?= $d['status'] === 'delivered' ? 'opacity-70' : '' ?>">
                <div class="flex justify-between gap-3">
                    <p class="font-display font-bold text-lg tabular-nums"><?= e($d['invoice_no']) ?></p>
                    <span class="badge status-<?= e($d['status']) ?>"><?= e(DeliveryController::statusLabel($d['status'])) ?></span>
                </div>
                <?php if ($d['delivery_option'] === 'express'): ?><p class="badge status-out_for_delivery self-start">Express rush</p><?php endif; ?>
                <div class="text-sm">
                    <p class="font-medium"><?= e($d['recipient_name']) ?></p>
                    <p><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $d['recipient_phone'])) ?>" class="text-packe-600 hover:underline"><?= e($d['recipient_phone']) ?></a></p>
                    <p class="text-muted"><?= e($d['address']) ?>, <?= e($d['city']) ?></p>
                    <?php if (!empty($d['distance_km'])): ?><p class="text-muted"><?= e(number_format((float) $d['distance_km'], 1)) ?> km from Packe</p><?php endif; ?>
                </div>
                <?php if ($d['status'] !== 'delivered'): ?>
                    <!-- Free: opens Google Maps navigation from the rider's position to the customer's pin -->
                    <a href="<?= e(maps_directions_url($d['latitude'] ?? null, $d['longitude'] ?? null, $d['address'] . ', ' . $d['city'])) ?>"
                       target="_blank" rel="noopener" class="btn btn-ghost w-full">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 11l18-8-8 18-2-8-8-2z"/></svg>
                        Directions in Google Maps
                    </a>
                <?php endif; ?>
                <?php if (isset($next[$d['status']])): ?>
                    <form action="<?= url('actions/update_delivery_status_action.php') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delivery_id" value="<?= (int) $d['delivery_id'] ?>">
                        <input type="hidden" name="status" value="<?= $next[$d['status']] ?>">
                        <button class="btn btn-primary w-full"><?= $nextLabel[$d['status']] ?></button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
