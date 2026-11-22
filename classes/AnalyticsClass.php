<?php
/**
 * Model: read-only figures for the admin Analytics page.
 * Each method reads across several tables (orders, order_items, products, categories, customers,
 * payments, deliveries, customizations, cart, subscriptions). Cancelled orders are left out of sales.
 */
require_once __DIR__ . '/../core/db_class.php';

class AnalyticsClass extends Database
{
    private const LIVE = "o.order_status <> 'cancelled'";

    /** Headline numbers from orders. */
    public function orderTotals(): array
    {
        return $this->fetchOne(
            'SELECT COUNT(*) AS orders, COALESCE(SUM(o.total_amount), 0) AS revenue, COALESCE(AVG(o.total_amount), 0) AS avg_order,
                    COALESCE(SUM(o.subtotal), 0) AS product_sales, COALESCE(SUM(o.discount), 0) AS discounts,
                    COALESCE(SUM(o.delivery_fee), 0) AS delivery_fees
             FROM orders o WHERE ' . self::LIVE
        );
    }

    /** Customers (role 2), with how many joined in the last 30 days and how many have ordered. */
    public function customerTotals(): array
    {
        return $this->fetchOne(
            "SELECT COUNT(*) AS customers,
                    COALESCE(SUM(c.created_at >= NOW() - INTERVAL 30 DAY), 0) AS new_30d,
                    (SELECT COUNT(DISTINCT customer_id) FROM orders) AS buyers
             FROM customers c WHERE c.user_role = 2"
        );
    }

    /** Packe Pro: active members and all membership revenue. */
    public function proTotals(): array
    {
        return $this->fetchOne(
            'SELECT (SELECT COUNT(DISTINCT customer_id) FROM subscriptions WHERE ends_at > NOW()) AS active_members,
                    COALESCE(SUM(amount), 0) AS revenue, COUNT(*) AS payments
             FROM subscriptions'
        );
    }

    /** Sales per day for the last $days days (days without sales are filled in by the controller). */
    public function revenueByDay(int $days): array
    {
        return $this->fetchAll(
            'SELECT DATE(o.created_at) AS day, SUM(o.total_amount) AS revenue, COUNT(*) AS orders
             FROM orders o
             WHERE ' . self::LIVE . ' AND o.created_at >= CURDATE() - INTERVAL ? DAY
             GROUP BY DATE(o.created_at) ORDER BY day',
            'i', [$days - 1]
        );
    }

    public function ordersByStatus(): array
    {
        return $this->fetchAll('SELECT order_status AS status, COUNT(*) AS n FROM orders GROUP BY order_status');
    }

    /** Sales per product category: order_items → products → categories, for orders that weren't cancelled. */
    public function revenueByCategory(): array
    {
        return $this->fetchAll(
            'SELECT cat.category_name AS category, SUM(oi.unit_price * oi.quantity) AS revenue, SUM(oi.quantity) AS units
             FROM order_items oi
             JOIN orders o ON o.order_id = oi.order_id
             JOIN products p ON p.product_id = oi.product_id
             JOIN categories cat ON cat.category_id = p.category_id
             WHERE ' . self::LIVE . '
             GROUP BY cat.category_id, cat.category_name ORDER BY revenue DESC'
        );
    }

    public function topProducts(int $limit = 5): array
    {
        return $this->fetchAll(
            'SELECT p.name, SUM(oi.quantity) AS units, SUM(oi.unit_price * oi.quantity) AS revenue,
                    SUM(oi.customization_id IS NOT NULL) AS custom_lines, COUNT(*) AS lines_total
             FROM order_items oi
             JOIN orders o ON o.order_id = oi.order_id
             JOIN products p ON p.product_id = oi.product_id
             WHERE ' . self::LIVE . '
             GROUP BY p.product_id, p.name ORDER BY revenue DESC LIMIT ?',
            'i', [$limit]
        );
    }

    /** Standard vs express: orders, fees and average distance (deliveries joined to orders). */
    public function deliveryOptions(): array
    {
        return $this->fetchAll(
            'SELECT d.delivery_option AS opt, COUNT(*) AS n, COALESCE(SUM(d.delivery_fee), 0) AS fees,
                    AVG(d.distance_km) AS avg_km
             FROM deliveries d JOIN orders o ON o.order_id = d.order_id
             WHERE ' . self::LIVE . ' GROUP BY d.delivery_option'
        );
    }

    public function deliveryStatuses(): array
    {
        return $this->fetchAll('SELECT status, COUNT(*) AS n FROM deliveries GROUP BY status');
    }

    /** Deliveries per rider (deliveries → customers). */
    public function riders(): array
    {
        return $this->fetchAll(
            "SELECT r.full_name AS rider, COUNT(*) AS assigned, SUM(d.status = 'delivered') AS delivered
             FROM deliveries d JOIN customers r ON r.customer_id = d.assigned_to
             GROUP BY r.customer_id, r.full_name ORDER BY delivered DESC"
        );
    }

    public function paymentChannels(): array
    {
        return $this->fetchAll(
            'SELECT COALESCE(channel, \'unknown\') AS channel, COUNT(*) AS n, SUM(amount) AS amount
             FROM payments GROUP BY channel ORDER BY n DESC'
        );
    }

    /** Designs saved in the 3D designer, and how many ended up in a paid order. */
    public function designFunnel(): array
    {
        return $this->fetchOne(
            'SELECT COUNT(*) AS saved,
                    (SELECT COUNT(DISTINCT customization_id) FROM order_items WHERE customization_id IS NOT NULL) AS ordered,
                    (SELECT COUNT(DISTINCT cart_key) FROM cart) AS open_carts,
                    (SELECT COALESCE(SUM(ct.quantity * COALESCE(cz.unit_price, p.base_price)), 0)
                     FROM cart ct JOIN products p ON p.product_id = ct.product_id
                     LEFT JOIN customizations cz ON cz.customization_id = ct.customization_id) AS cart_value
             FROM customizations'
        );
    }

    /** Where orders go: delivery city, by number of orders. */
    public function topCities(int $limit = 5): array
    {
        return $this->fetchAll(
            'SELECT d.city, COUNT(*) AS n, SUM(o.total_amount) AS revenue
             FROM deliveries d JOIN orders o ON o.order_id = d.order_id
             WHERE ' . self::LIVE . ' GROUP BY d.city ORDER BY n DESC LIMIT ?',
            'i', [$limit]
        );
    }
}
