<?php
/** Invoice for one order. Expects $order (from OrderController::getOrderForViewer). */
require_once __DIR__ . '/../../controllers/OrderController.php';
?>
<section class="card p-6 md:p-8">
    <div class="flex flex-wrap justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="label">Invoice</p>
            <p class="font-display font-extrabold text-2xl tabular-nums"><?= e($order['invoice_no']) ?></p>
            <p class="text-sm text-muted mt-1"><?= e(date('j M Y, g:i a', strtotime($order['created_at']))) ?></p>
        </div>
        <div class="text-sm text-right">
            <span class="badge status-<?= e($order['order_status']) ?>"><?= e(OrderController::statusLabel($order['order_status'])) ?></span>
            <p class="mt-2 text-muted">Paid with Paystack<?= $order['channel'] ? ' (' . e(str_replace('_', ' ', $order['channel'])) . ')' : '' ?></p>
            <p class="text-muted break-all">Ref: <?= e($order['payment_reference']) ?></p>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-6 py-5 border-b border-line text-sm">
        <div>
            <p class="label mb-1">Customer</p>
            <p><?= e($order['full_name']) ?><?= $order['business_name'] ? ', ' . e($order['business_name']) : '' ?></p>
            <p class="text-muted"><?= e($order['email']) ?> · <?= e($order['phone']) ?></p>
        </div>
        <div>
            <p class="label mb-1"><?= $order['delivery_option'] === 'express' ? 'Express delivery' : 'Standard delivery' ?></p>
            <p><?= e($order['recipient_name']) ?> · <?= e($order['recipient_phone']) ?></p>
            <p class="text-muted"><?= e($order['address']) ?>, <?= e($order['city']) ?></p>
            <?php if ($order['rider_name']): ?><p class="text-muted">Rider: <?= e($order['rider_name']) ?></p><?php endif; ?>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm mt-4">
            <thead>
                <tr class="text-left text-muted">
                    <th class="py-2 font-medium">Item</th>
                    <th class="py-2 font-medium text-right">Qty</th>
                    <th class="py-2 font-medium text-right">Unit price</th>
                    <th class="py-2 font-medium text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $it): ?>
                    <tr class="border-t border-line align-top">
                        <td class="py-3">
                            <p class="font-medium"><?= e($it['name']) ?></p>
                            <?php include __DIR__ . '/design_summary.php'; ?>
                        </td>
                        <td class="py-3 text-right tabular-nums"><?= (int) $it['quantity'] ?></td>
                        <td class="py-3 text-right tabular-nums"><?= money($it['unit_price']) ?></td>
                        <td class="py-3 text-right tabular-nums"><?= money($it['line_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="tabular-nums">
                <tr class="border-t border-line"><td colspan="3" class="pt-3 text-right text-muted">Subtotal</td><td class="pt-3 text-right"><?= money($order['subtotal']) ?></td></tr>
                <tr><td colspan="3" class="text-right text-muted">Delivery</td><td class="text-right"><?= money($order['delivery_fee']) ?></td></tr>
                <tr><td colspan="3" class="pt-2 text-right font-semibold">Total paid</td><td class="pt-2 text-right font-display font-extrabold text-lg"><?= money($order['total_amount']) ?></td></tr>
            </tfoot>
        </table>
    </div>
</section>
