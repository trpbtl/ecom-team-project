<?php
/**
 * Model: Packe Pro memberships (the subscriptions table). All SQL for memberships lives here.
 */
require_once __DIR__ . '/../core/db_class.php';

class SubscriptionClass extends Database
{
    /** The customer's membership that is running now, or null. */
    public function getActive(int $customerId): ?array
    {
        return $this->fetchOne(
            'SELECT subscription_id, plan, amount, starts_at, ends_at FROM subscriptions
             WHERE customer_id = ? AND ends_at > NOW() ORDER BY ends_at DESC LIMIT 1',
            'i', [$customerId]
        );
    }

    public function referenceExists(string $reference): bool
    {
        return $this->fetchOne('SELECT subscription_id FROM subscriptions WHERE reference = ?', 's', [$reference]) !== null;
    }

    /** Record a paid month. It starts when the current membership ends, so renewing early never loses days. */
    public function add(int $customerId, string $plan, float $amount, string $channel, string $reference, int $days): void
    {
        $this->execute(
            'INSERT INTO subscriptions (customer_id, plan, amount, channel, reference, starts_at, ends_at)
             SELECT ?, ?, ?, ?, ?, s.start, DATE_ADD(s.start, INTERVAL ? DAY)
             FROM (SELECT GREATEST(NOW(), COALESCE((SELECT MAX(ends_at) FROM subscriptions WHERE customer_id = ?), NOW())) AS start) s',
            'isdssii', [$customerId, $plan, $amount, $channel, $reference, $days, $customerId]
        );
    }

    /** Every payment for memberships, newest first (for the customer's account page). */
    public function getHistory(int $customerId): array
    {
        return $this->fetchAll(
            'SELECT plan, amount, channel, reference, starts_at, ends_at, created_at FROM subscriptions
             WHERE customer_id = ? ORDER BY created_at DESC',
            'i', [$customerId]
        );
    }
}
