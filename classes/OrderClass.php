<?php
/**
 * Model: orders, order items and payments.
 */
require_once __DIR__ . '/../core/db_class.php';

class OrderClass extends Database
{
    public const STATUSES = ['paid', 'processing', 'ready', 'out_for_delivery', 'delivered', 'cancelled'];

    /**
     * Turn the cart into a paid order in one transaction:
     * order -> order items -> payment -> delivery -> empty the cart.
     * $paidAmount is what Paystack confirmed; it must equal the cart total plus delivery.
     */
    public function createOrderFromCart(int $customerId, string $cartKey, string $reference, float $paidAmount,
                                        string $channel, array $delivery): int
    {
        $this->conn->begin_transaction();
        try {
            $items = $this->fetchAll(
                'SELECT ct.product_id, ct.customization_id, ct.quantity, COALESCE(cz.unit_price, p.base_price) AS unit_price
                 FROM cart ct JOIN products p ON p.product_id = ct.product_id
                 LEFT JOIN customizations cz ON cz.customization_id = ct.customization_id
                 WHERE ct.cart_key = ? FOR UPDATE',
                's', [$cartKey]
            );
            if (!$items) {
                throw new RuntimeException('The cart is empty.');
            }
            $subtotal = 0.0;
            foreach ($items as $it) {
                $subtotal += $it['unit_price'] * $it['quantity'];
            }
            $subtotal = round($subtotal, 2);
            $fee = round((float) $delivery['fee'], 2);
            $total = round($subtotal + $fee, 2);
            if (abs($total - $paidAmount) > 0.009) {
                throw new RuntimeException("Paid amount $paidAmount does not match order total $total.");
            }

            do {
                $invoice = 'PK-' . mt_rand(100000, 999999);
            } while ($this->fetchOne('SELECT order_id FROM orders WHERE invoice_no = ?', 's', [$invoice]));

            $this->execute(
                'INSERT INTO orders (customer_id, invoice_no, subtotal, delivery_fee, total_amount, order_status, payment_reference)
                 VALUES (?, ?, ?, ?, ?, \'paid\', ?)',
                'isddds', [$customerId, $invoice, $subtotal, $fee, $total, $reference]
            );
            $orderId = $this->conn->insert_id;

            foreach ($items as $it) {
                $this->execute(
                    'INSERT INTO order_items (order_id, product_id, customization_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)',
                    'iiiid', [$orderId, $it['product_id'], $it['customization_id'], $it['quantity'], $it['unit_price']]
                );
            }

            $this->execute(
                'INSERT INTO payments (order_id, amount, currency, channel, reference) VALUES (?, ?, \'GHS\', ?, ?)',
                'idss', [$orderId, $total, $channel, $reference]
            );

            $this->execute(
                'INSERT INTO deliveries (order_id, recipient_name, recipient_phone, address, city, delivery_option, delivery_fee)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                'isssssd', [$orderId, $delivery['recipient_name'], $delivery['recipient_phone'], $delivery['address'],
                            $delivery['city'], $delivery['option'], $fee]
            );

            $this->execute('DELETE FROM cart WHERE cart_key = ?', 's', [$cartKey]);
            $this->conn->commit();
            return $orderId;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function getOrderIdByReference(string $reference): ?int
    {
        $row = $this->fetchOne('SELECT order_id FROM orders WHERE payment_reference = ?', 's', [$reference]);
        return $row ? (int) $row['order_id'] : null;
    }

    /** One order with its customer, delivery and payment details. */
    public function getOrderById(int $orderId): ?array
    {
        return $this->fetchOne(
            'SELECT o.*, cu.full_name, cu.email, cu.phone, cu.business_name,
                    d.delivery_id, d.recipient_name, d.recipient_phone, d.address, d.city, d.delivery_option,
                    d.status AS delivery_status, d.assigned_to, rider.full_name AS rider_name,
                    pay.channel, pay.paid_at
             FROM orders o
             JOIN customers cu ON cu.customer_id = o.customer_id
             LEFT JOIN deliveries d ON d.order_id = o.order_id
             LEFT JOIN customers rider ON rider.customer_id = d.assigned_to
             LEFT JOIN payments pay ON pay.order_id = o.order_id
             WHERE o.order_id = ?',
            'i', [$orderId]
        );
    }

    public function getOrderItems(int $orderId): array
    {
        return $this->fetchAll(
            'SELECT oi.*, p.name, p.image, oi.unit_price * oi.quantity AS line_total,
                    cz.length_cm, cz.width_cm, cz.height_cm, cz.material, cz.print_colour, cz.logo_file, cz.brand_text
             FROM order_items oi
             JOIN products p ON p.product_id = oi.product_id
             LEFT JOIN customizations cz ON cz.customization_id = oi.customization_id
             WHERE oi.order_id = ?',
            'i', [$orderId]
        );
    }

    public function getOrdersByCustomer(int $customerId): array
    {
        return $this->fetchAll(
            'SELECT o.order_id, o.invoice_no, o.total_amount, o.order_status, o.created_at, d.delivery_option
             FROM orders o LEFT JOIN deliveries d ON d.order_id = o.order_id
             WHERE o.customer_id = ? ORDER BY o.created_at DESC',
            'i', [$customerId]
        );
    }

    public function getAllOrders(string $status = ''): array
    {
        $sql = 'SELECT o.order_id, o.invoice_no, o.total_amount, o.order_status, o.created_at,
                       cu.full_name, d.delivery_option, d.status AS delivery_status, rider.full_name AS rider_name
                FROM orders o
                JOIN customers cu ON cu.customer_id = o.customer_id
                LEFT JOIN deliveries d ON d.order_id = o.order_id
                LEFT JOIN customers rider ON rider.customer_id = d.assigned_to';
        if ($status !== '') {
            return $this->fetchAll($sql . ' WHERE o.order_status = ? ORDER BY o.created_at DESC', 's', [$status]);
        }
        return $this->fetchAll($sql . ' ORDER BY o.created_at DESC');
    }

    public function updateOrderStatus(int $orderId, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        $this->execute('UPDATE orders SET order_status = ? WHERE order_id = ?', 'si', [$status, $orderId]);
        return true;
    }

    /** Numbers for the admin dashboard. */
    public function getSummary(): array
    {
        return $this->fetchOne(
            "SELECT COUNT(*) AS orders,
                    COALESCE(SUM(total_amount), 0) AS revenue,
                    COALESCE(SUM(order_status IN ('paid','processing','ready')), 0) AS to_prepare,
                    COALESCE(SUM(order_status = 'out_for_delivery'), 0) AS on_the_road
             FROM orders WHERE order_status <> 'cancelled'"
        );
    }
}
