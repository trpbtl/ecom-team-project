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

    /** A partner delivery (Uber, Yango, Bolt) that Packe booked: save the driver's details and mark it assigned. */
    public function setPartnerDriver(int $orderId, string $name, string $phone, ?string $trackingUrl): bool
    {
        return $this->execute(
            "UPDATE deliveries SET driver_name = ?, driver_phone = ?, tracking_url = ?,
                    status = IF(status = 'pending', 'assigned', status)
             WHERE order_id = ? AND carrier <> 'packe'",
            'sssi', [$name, $phone, $trackingUrl, $orderId]
        ) >= 0;
    }

    /** Every rider (role 3) with how many deliveries they have on the go and have completed. */
    public function getRidersWithStats(): array
    {
        return $this->fetchAll(
            "SELECT c.customer_id, c.full_name, c.phone, c.city, c.email, (c.password_hash IS NOT NULL) AS can_log_in,
                    COALESCE(SUM(d.status IN ('assigned', 'in_transit')), 0) AS active,
                    COALESCE(SUM(d.status = 'delivered'), 0) AS delivered
             FROM customers c LEFT JOIN deliveries d ON d.assigned_to = c.customer_id
             WHERE c.user_role = 3
             GROUP BY c.customer_id, c.full_name, c.phone, c.city, c.email, c.password_hash
             ORDER BY active ASC, c.full_name"
        );
    }

    /** Paid orders delivered by Packe riders that nobody has been assigned to yet. */
    public function getUnassigned(): array
    {
        return $this->fetchAll(
            "SELECT o.order_id, o.invoice_no, o.created_at, d.recipient_name, d.city, d.delivery_option, d.distance_km
             FROM deliveries d JOIN orders o ON o.order_id = d.order_id
             WHERE d.assigned_to IS NULL AND d.carrier = 'packe' AND d.status = 'pending' AND o.order_status <> 'cancelled'
             ORDER BY d.delivery_option = 'express' DESC, o.created_at ASC"
        );
    }

    public function riderEmailExists(string $email): bool
    {
        return $this->fetchOne('SELECT customer_id FROM customers WHERE email = ?', 's', [$email]) !== null;
    }

    /** A new rider account (role 3) with no password; an admin can set one later in Businesses. */
    public function addRider(string $name, string $email, string $phone, string $area): int
    {
        $this->execute(
            "INSERT INTO customers (full_name, email, password_hash, phone, business_name, city, address, user_role)
             VALUES (?, ?, NULL, ?, 'Packe Delivery', ?, '', 3)",
            'ssss', [$name, $email, $phone, $area]
        );
        return $this->conn->insert_id;
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
