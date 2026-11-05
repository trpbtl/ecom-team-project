<?php
/**
 * Model: deliveries (one per order).
 */
require_once __DIR__ . '/../core/db_class.php';

class DeliveryClass extends Database
{
    public const STATUSES = ['pending', 'assigned', 'in_transit', 'delivered'];

    public function assignDelivery(int $orderId, int $staffId): bool
    {
        $this->execute(
            "UPDATE deliveries SET assigned_to = ?, status = IF(status = 'pending', 'assigned', status) WHERE order_id = ?",
            'ii', [$staffId, $orderId]
        );
        return true;
    }

    public function getDeliveriesForStaff(int $staffId): array
    {
        return $this->fetchAll(
            "SELECT d.*, o.invoice_no, o.total_amount, o.created_at
             FROM deliveries d JOIN orders o ON o.order_id = d.order_id
             WHERE d.assigned_to = ?
             ORDER BY (d.status = 'delivered'), d.delivery_option = 'express' DESC, o.created_at ASC",
            'i', [$staffId]
        );
    }

    public function getDeliveryById(int $deliveryId): ?array
    {
        return $this->fetchOne('SELECT * FROM deliveries WHERE delivery_id = ?', 'i', [$deliveryId]);
    }

    /** Updates the delivery and keeps the order status in step with it. */
    public function updateStatus(int $deliveryId, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        $this->conn->begin_transaction();
        try {
            $this->execute('UPDATE deliveries SET status = ? WHERE delivery_id = ?', 'si', [$status, $deliveryId]);
            $orderStatus = ['in_transit' => 'out_for_delivery', 'delivered' => 'delivered'][$status] ?? null;
            if ($orderStatus) {
                $this->execute(
                    'UPDATE orders o JOIN deliveries d ON d.order_id = o.order_id SET o.order_status = ? WHERE d.delivery_id = ?',
                    'si', [$orderStatus, $deliveryId]
                );
            }
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
