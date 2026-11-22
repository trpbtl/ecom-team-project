<?php
/**
 * Controller: Packe Pro, a monthly membership (recurring revenue).
 *
 * Members pay GH₵99 for 30 days and get:
 *   - 10% off all packaging (applied at checkout, on the server)
 *   - free standard delivery (express is still charged)
 * Payments go through Paystack and are verified on the server, the same way as orders.
 */
require_once __DIR__ . '/../classes/SubscriptionClass.php';
require_once __DIR__ . '/Validate.php';
require_once __DIR__ . '/PaystackGateway.php';

class ProController
{
    public const PRICE = 99.00;          // GH₵ per month
    public const DAYS = 30;
    public const DISCOUNT_RATE = 0.10;   // 10% off packaging
    public const PLAN = 'pro_monthly';

    private SubscriptionClass $model;
    private static array $activeCache = [];   // one lookup per customer per page

    public function __construct()
    {
        $this->model = new SubscriptionClass();
    }

    /** The running membership, or null. */
    public function getActive(int $customerId): ?array
    {
        if ($customerId <= 0) return null;
        if (!array_key_exists($customerId, self::$activeCache)) {
            self::$activeCache[$customerId] = $this->model->getActive($customerId);
        }
        return self::$activeCache[$customerId];
    }

    public function isActive(int $customerId): bool
    {
        return $this->getActive($customerId) !== null;
    }

    /** Packe Pro's 10% off the packaging subtotal (0 for everyone else). */
    public function discountFor(float $subtotal, int $customerId): float
    {
        return $this->isActive($customerId) ? round($subtotal * self::DISCOUNT_RATE, 2) : 0.0;
    }

    /** Members get standard delivery free; the quote is changed here so the map, checkout and payment all agree. */
    public function applyToQuote(array $quote, int $customerId): array
    {
        if (!empty($quote['success']) && $this->isActive($customerId)) {
            $quote['standard_full'] = $quote['standard'];
            $quote['standard'] = 0.0;
            $quote['pro'] = true;
        }
        return $quote;
    }

    /** Start paying for a month: our own reference and the price, kept in the session and checked after Paystack. */
    public function startCheckout(): array
    {
        return ['reference' => 'PRO-' . time() . '-' . bin2hex(random_bytes(4)), 'amount' => self::PRICE];
    }

    /** Verify the Paystack payment on the server, then add 30 days of membership. */
    public function completePayment(string $reference, ?array $expected, int $customerId): array
    {
        if (!$expected || !preg_match('/^[A-Za-z0-9_-]{6,100}$/', $reference) || $reference !== $expected['reference']) {
            return Validate::fail('That payment reference does not match your Packe Pro checkout.');
        }
        if ($this->model->referenceExists($reference)) {
            return ['success' => true];   // already recorded (e.g. the page was refreshed)
        }
        $data = PaystackGateway::verify($reference);
        if ($problem = PaystackGateway::mismatch($data, $reference, (float) $expected['amount'])) return Validate::fail($problem);

        $this->model->add($customerId, self::PLAN, $data['amount'] / 100, (string) ($data['channel'] ?? 'paystack'), $reference, self::DAYS);
        unset(self::$activeCache[$customerId]);
        return ['success' => true];
    }

    public function getHistory(int $customerId): array
    {
        return $this->model->getHistory($customerId);
    }
}
