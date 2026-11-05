<?php /** Admin orders table. Expects $orders (rows from OrderController::getAllOrders). */ ?>
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="text-left text-muted"><th class="py-2">Invoice</th><th>Customer</th><th>Date</th><th>Delivery</th><th>Order status</th><th>Rider</th><th class="text-right">Total</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr class="border-t border-line">
                <td class="py-3 font-medium tabular-nums"><?= e($o['invoice_no']) ?></td>
                <td><?= e($o['full_name']) ?></td>
                <td class="whitespace-nowrap"><?= e(date('j M, g:i a', strtotime($o['created_at']))) ?></td>
                <td><?= $o['delivery_option'] === 'express' ? '<b class="text-packe-700">Express</b>' : 'Standard' ?></td>
                <td><span class="badge status-<?= e($o['order_status']) ?>"><?= e(OrderController::statusLabel($o['order_status'])) ?></span></td>
                <td><?= $o['rider_name'] ? e($o['rider_name']) : '<span class="text-muted">Not assigned</span>' ?></td>
                <td class="text-right tabular-nums"><?= money($o['total_amount']) ?></td>
                <td class="text-right"><a href="<?= url('views/admin/order_detail.php?order_id=' . (int) $o['order_id']) ?>" class="text-packe-600 font-semibold hover:underline">Manage</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
