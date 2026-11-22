<?php
/** Admin analytics: sales, revenue streams, products, deliveries, payments and customers, drawn from many tables. */
require_once __DIR__ . '/../../core/core.php';
require_admin();
require_once __DIR__ . '/../../controllers/AnalyticsController.php';
require_once __DIR__ . '/../../controllers/OrderController.php';

$days = AnalyticsController::period($_GET['days'] ?? 30);
$a = (new AnalyticsController())->dashboard($days);
$o = $a['orders'];
$maxCat = max(array_column($a['categories'], 'revenue') ?: [1]);
$deliveryCount = array_sum(array_map(fn($r) => (int) $r['n'], $a['delivery'])) ?: 1;

$chartData = [
    'daily'    => ['labels' => array_map(fn($d) => date('j M', strtotime($d['day'])), $a['daily']),
                   'revenue' => array_column($a['daily'], 'revenue'), 'orders' => array_column($a['daily'], 'orders')],
    'streams'  => ['labels' => array_keys($a['streams']), 'values' => array_values($a['streams'])],
    'statuses' => ['labels' => array_map(fn($s) => OrderController::statusLabel($s['status']), $a['statuses']),
                   'values' => array_map('intval', array_column($a['statuses'], 'n'))],
];
$pageTitle = 'Analytics';
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>'
              . '<script id="analytics-data" type="application/json">' . json_encode($chartData) . '</script>'
              . '<script src="' . e(asset_url('js/analytics.js')) . '"></script>';
require __DIR__ . '/../layout/header.php';
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="font-display font-extrabold text-3xl">Analytics</h1>
        <p class="text-muted">Live figures from orders, products, customers, payments, deliveries, designs and memberships.</p>
    </div>
    <nav class="flex gap-2" aria-label="Period">
        <?php foreach (AnalyticsController::PERIODS as $p): ?>
            <a href="?days=<?= $p ?>" class="chip <?= $p === $days ? 'chip-active' : '' ?>" <?= $p === $days ? 'aria-current="page"' : '' ?>>Last <?= $p ?> days</a>
        <?php endforeach; ?>
    </nav>
</div>

<!-- Headline numbers -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="card kpi"><span class="kpi-label">Total income</span><span class="kpi-value tabular-nums"><?= money($a['total_income']) ?></span><span class="kpi-sub">Orders + Pro memberships</span></div>
    <div class="card kpi"><span class="kpi-label">Paid orders</span><span class="kpi-value tabular-nums"><?= (int) $o['orders'] ?></span><span class="kpi-sub">Average <?= money($o['avg_order']) ?></span></div>
    <div class="card kpi"><span class="kpi-label">Customers</span><span class="kpi-value tabular-nums"><?= (int) $a['customers']['customers'] ?></span><span class="kpi-sub"><?= (int) $a['customers']['new_30d'] ?> new in 30 days · <?= (int) $a['customers']['buyers'] ?> have ordered</span></div>
    <div class="card kpi"><span class="kpi-label">Packe Pro members</span><span class="kpi-value tabular-nums"><?= (int) $a['pro']['active_members'] ?></span><span class="kpi-sub"><?= money($a['pro']['active_members'] * ProController::PRICE) ?> a month recurring</span></div>
</div>

<div class="grid lg:grid-cols-3 gap-6 mt-6">
    <section class="card p-6 lg:col-span-2">
        <h2 class="font-display font-bold text-lg">Sales, last <?= $days ?> days</h2>
        <div class="chart-box mt-3"><canvas id="chart-daily" aria-label="Sales and orders per day"></canvas></div>
    </section>
    <section class="card p-6">
        <h2 class="font-display font-bold text-lg">Revenue streams</h2>
        <div class="chart-box mt-3" style="height:200px"><canvas id="chart-streams" aria-label="Income by revenue stream"></canvas></div>
        <ul class="mt-3 text-sm flex flex-col gap-1">
            <?php foreach ($a['streams'] as $name => $value): ?>
                <li class="flex justify-between gap-3"><span><?= e($name) ?></span><span class="tabular-nums font-semibold"><?= money($value) ?></span></li>
            <?php endforeach; ?>
            <?php if ($o['discounts'] > 0): ?><li class="flex justify-between gap-3 text-muted"><span>Pro discounts given</span><span class="tabular-nums">−<?= money($o['discounts']) ?></span></li><?php endif; ?>
        </ul>
    </section>
</div>

<div class="grid lg:grid-cols-3 gap-6 mt-6">
    <section class="card p-6">
        <h2 class="font-display font-bold text-lg mb-3">Sales by category</h2>
        <?php if (!$a['categories']): ?><p class="text-muted text-sm">No sales yet.</p><?php endif; ?>
        <div class="flex flex-col gap-3 text-sm">
            <?php foreach ($a['categories'] as $c): ?>
                <div class="bar-row">
                    <span><?= e($c['category']) ?> <span class="text-muted">· <?= (int) $c['units'] ?> units</span></span>
                    <span class="tabular-nums font-semibold"><?= money($c['revenue']) ?></span>
                    <div class="bar-track"><div class="bar-fill" style="width:<?= round(100 * $c['revenue'] / $maxCat) ?>%"></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="card p-6">
        <h2 class="font-display font-bold text-lg">Orders by status</h2>
        <div class="chart-box mt-3" style="height:220px"><canvas id="chart-status" aria-label="Orders by status"></canvas></div>
    </section>
    <section class="card p-6">
        <h2 class="font-display font-bold text-lg mb-3">3D designer funnel</h2>
        <div class="grid grid-cols-2 gap-3">
            <div><p class="kpi-value tabular-nums"><?= (int) $a['funnel']['saved'] ?></p><p class="kpi-sub">designs saved</p></div>
            <div><p class="kpi-value tabular-nums"><?= (int) $a['funnel']['ordered'] ?></p><p class="kpi-sub">designs ordered</p></div>
            <div><p class="kpi-value tabular-nums"><?= $a['conversion'] ?>%</p><p class="kpi-sub">design → order</p></div>
            <div><p class="kpi-value tabular-nums"><?= money($a['funnel']['cart_value']) ?></p><p class="kpi-sub">waiting in <?= (int) $a['funnel']['open_carts'] ?> open carts</p></div>
        </div>
    </section>
</div>

<div class="grid lg:grid-cols-2 gap-6 mt-6">
    <section class="card p-6 min-w-0">
        <h2 class="font-display font-bold text-lg mb-3">Top products</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-muted"><th class="py-2">Product</th><th class="text-right">Units</th><th class="text-right">Custom designs</th><th class="text-right">Revenue</th></tr></thead>
                <tbody>
                <?php foreach ($a['products'] as $p): ?>
                    <tr class="border-t border-line"><td class="py-2"><?= e($p['name']) ?></td><td class="text-right tabular-nums"><?= (int) $p['units'] ?></td>
                        <td class="text-right tabular-nums"><?= round(100 * $p['custom_lines'] / max(1, $p['lines_total'])) ?>%</td><td class="text-right tabular-nums font-semibold"><?= money($p['revenue']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$a['products']): ?><tr><td colspan="4" class="py-3 text-muted">No sales yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="card p-6 min-w-0">
        <h2 class="font-display font-bold text-lg mb-3">Delivery</h2>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <?php foreach (['standard' => 'Standard', 'express' => 'Express rush'] as $key => $label): $r = $a['delivery'][$key] ?? null; ?>
                <div class="rounded-xl border border-line p-4">
                    <p class="font-semibold"><?= $label ?></p>
                    <p class="kpi-value tabular-nums mt-1"><?= round(100 * (int) ($r['n'] ?? 0) / $deliveryCount) ?>%</p>
                    <p class="kpi-sub"><?= (int) ($r['n'] ?? 0) ?> orders · <?= money($r['fees'] ?? 0) ?> in fees<?= isset($r['avg_km']) ? ' · avg ' . number_format((float) $r['avg_km'], 1) . ' km' : '' ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="flex flex-wrap gap-2 mt-4 text-sm">
            <?php foreach ($a['deliveryStatuses'] as $s): ?>
                <span class="badge status-<?= e($s['status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $s['status']))) ?>: <?= (int) $s['n'] ?></span>
            <?php endforeach; ?>
        </div>
        <?php if ($a['riders']): ?>
            <p class="label mt-4 mb-1">Riders</p>
            <ul class="text-sm flex flex-col gap-1">
                <?php foreach ($a['riders'] as $r): ?>
                    <li class="flex justify-between"><span><?= e($r['rider']) ?></span><span class="tabular-nums"><?= (int) $r['delivered'] ?> of <?= (int) $r['assigned'] ?> delivered</span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<div class="grid lg:grid-cols-2 gap-6 mt-6">
    <section class="card p-6 min-w-0">
        <h2 class="font-display font-bold text-lg mb-3">Payment methods</h2>
        <ul class="text-sm flex flex-col gap-2">
            <?php foreach ($a['channels'] as $c): ?>
                <li class="flex justify-between"><span><?= e(ucfirst(str_replace('_', ' ', $c['channel']))) ?></span><span class="tabular-nums"><?= (int) $c['n'] ?> payments · <?= money($c['amount']) ?></span></li>
            <?php endforeach; ?>
            <?php if (!$a['channels']): ?><li class="text-muted">No payments yet.</li><?php endif; ?>
        </ul>
    </section>
    <section class="card p-6 min-w-0">
        <h2 class="font-display font-bold text-lg mb-3">Top delivery cities</h2>
        <ul class="text-sm flex flex-col gap-2">
            <?php foreach ($a['cities'] as $c): ?>
                <li class="flex justify-between"><span><?= e($c['city']) ?></span><span class="tabular-nums"><?= (int) $c['n'] ?> orders · <?= money($c['revenue']) ?></span></li>
            <?php endforeach; ?>
            <?php if (!$a['cities']): ?><li class="text-muted">No deliveries yet.</li><?php endif; ?>
        </ul>
    </section>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
