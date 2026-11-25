<?php
/**
 * Model: demo data for presentations. Every demo record is tagged so it can be removed cleanly:
 *   customers  email ends in @demo.packe.test
 *   orders     payment_reference starts with DEMO-   (items, payments and deliveries go with them)
 *   subscriptions reference starts with DEMO-
 */
require_once __DIR__ . '/../core/db_class.php';

class DemoDataClass extends Database
{
    public const EMAIL_DOMAIN = '@demo.packe.test';
    public const REF_PREFIX = 'DEMO-';

    public function counts(): array
    {
        return $this->fetchOne(
            "SELECT (SELECT COUNT(*) FROM customers WHERE email LIKE ?) AS customers,
                    (SELECT COUNT(*) FROM orders WHERE payment_reference LIKE ?) AS orders,
                    (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_reference LIKE ? AND order_status <> 'cancelled') AS revenue",
            'sss', ['%' . self::EMAIL_DOMAIN, self::REF_PREFIX . '%', self::REF_PREFIX . '%']
        );
    }

    public function products(): array
    {
        return $this->fetchAll('SELECT product_id, name, base_price, design_type FROM products');
    }

    public function riders(): array
    {
        return array_column($this->fetchAll('SELECT customer_id FROM customers WHERE user_role = 3'), 'customer_id');
    }

    public function invoiceExists(string $invoice): bool
    {
        return $this->fetchOne('SELECT order_id FROM orders WHERE invoice_no = ?', 's', [$invoice]) !== null;
    }

    public function begin(): void    { $this->conn->begin_transaction(); }
    public function commit(): void   { $this->conn->commit(); }
    public function rollback(): void { $this->conn->rollback(); }

    public function addCustomer(array $c, string $createdAt): int
    {
        $this->execute(
            'INSERT INTO customers (full_name, email, password_hash, phone, business_name, city, address, user_role, created_at)
             VALUES (?, ?, NULL, ?, ?, ?, ?, 2, ?)',
            'sssssss', [$c['contact'], $c['email'], $c['phone'], $c['business'], $c['area'], $c['address'], $createdAt]
        );
        return $this->conn->insert_id;
    }

    public function addSubscription(int $customerId, string $reference, string $start, string $end): void
    {
        $this->execute(
            "INSERT INTO subscriptions (customer_id, plan, amount, channel, reference, starts_at, ends_at, created_at)
             VALUES (?, 'pro_monthly', 99.00, 'mobile_money', ?, ?, ?, ?)",
            'issss', [$customerId, $reference, $start, $end, $start]
        );
    }

    public function addDesign(int $customerId, int $productId, array $d, string $createdAt): int
    {
        $this->execute(
            'INSERT INTO customizations (customer_id, cart_key, product_id, length_cm, width_cm, height_cm, material, print_colour,
                                         brand_text, font, pattern, quantity, unit_price, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'isidddsssssids', [$customerId, 'demo', $productId, $d['L'], $d['W'], $d['H'], $d['material'], $d['colour'],
                               $d['text'], $d['font'], $d['pattern'], $d['qty'], $d['unit_price'], $createdAt]
        );
        return $this->conn->insert_id;
    }

    public function addOrder(array $o): int
    {
        $this->execute(
            'INSERT INTO orders (customer_id, invoice_no, subtotal, discount, delivery_fee, total_amount, order_status, payment_reference, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'isddddsss', [$o['customer_id'], $o['invoice'], $o['subtotal'], $o['discount'], $o['fee'], $o['total'],
                          $o['status'], $o['reference'], $o['created_at']]
        );
        $orderId = $this->conn->insert_id;

        foreach ($o['items'] as $it) {
            $this->execute(
                'INSERT INTO order_items (order_id, product_id, customization_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)',
                'iiiid', [$orderId, $it['product_id'], $it['customization_id'], $it['qty'], $it['unit_price']]
            );
        }
        $this->execute(
            "INSERT INTO payments (order_id, amount, currency, channel, reference, paid_at) VALUES (?, ?, 'GHS', ?, ?, ?)",
            'idsss', [$orderId, $o['total'], $o['channel'], $o['reference'], $o['created_at']]
        );
        $d = $o['delivery'];
        $this->execute(
            'INSERT INTO deliveries (order_id, recipient_name, recipient_phone, address, city, delivery_option, carrier, delivery_fee,
                                     latitude, longitude, distance_km, assigned_to, driver_name, driver_phone, tracking_url, status, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'issssssddddisssss', [$orderId, $d['recipient'], $d['phone'], $d['address'], $d['city'], $d['option'], $d['carrier'],
                                  $o['fee'], $d['lat'], $d['lng'], $d['km'], $d['rider'], $d['driver_name'], $d['driver_phone'],
                                  $d['tracking_url'], $d['status'], $o['created_at']]
        );
        return $orderId;
    }

    /** Remove every tagged demo record. Orders first (their items, payments and deliveries cascade), then designs and customers. */
    public function removeAll(): void
    {
        $this->conn->begin_transaction();
        try {
            $this->execute('DELETE FROM orders WHERE payment_reference LIKE ?', 's', [self::REF_PREFIX . '%']);
            $this->execute('DELETE FROM subscriptions WHERE reference LIKE ?', 's', [self::REF_PREFIX . '%']);
            $this->execute(
                'DELETE cz FROM customizations cz JOIN customers c ON c.customer_id = cz.customer_id WHERE c.email LIKE ?',
                's', ['%' . self::EMAIL_DOMAIN]
            );
            $this->execute('DELETE FROM customers WHERE email LIKE ?', 's', ['%' . self::EMAIL_DOMAIN]);
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
