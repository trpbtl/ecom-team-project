<?php
/**
 * Controller: checkout, Paystack payment verification and orders.
 * Owners: Donna Omaboe (payment), Eyram Awoye (orders)
 */
require_once __DIR__ . '/../classes/OrderClass.php';
require_once __DIR__ . '/../classes/CartClass.php';
require_once __DIR__ . '/Validate.php';
require_once __DIR__ . '/DeliveryPricing.php';
require_once __DIR__ . '/PaystackGateway.php';
require_once __DIR__ . '/ProController.php';

class OrderController
{
    private OrderClass $model;

    public function __construct()
    {
        $this->model = new OrderClass();
    }

    /**
     * Validates the checkout form. Returns the delivery details to keep in the session.
     * The delivery fee is worked out here from the pin's distance to Packe (DeliveryPricing), never taken from the browser.
     */
    public function prepareCheckout(array $in, string $cartKey, int $customerId = 0): array
    {
        $d = [
            'recipient_name'  => Validate::clean($in['recipient_name'] ?? ''),
            'recipient_phone' => Validate::clean($in['recipient_phone'] ?? ''),
            'address'         => Validate::clean($in['address'] ?? ''),
            'city'            => Validate::clean($in['city'] ?? ''),
            'option'          => ($in['delivery_option'] ?? '') === 'express' ? 'express' : 'standard',
        ];
        $cart = new CartClass();
        if ($cart->getCartCount($cartKey) === 0) return Validate::fail('Your cart is empty.');
        if (!Validate::text($d['recipient_name'], 2, 100)) return Validate::fail('Enter the name of the person receiving the order.');
        if (!Validate::phone($d['recipient_phone']))       return Validate::fail('Enter a valid contact number for delivery.');
        if (!Validate::text($d['address'], 5, 255))        return Validate::fail('Enter the delivery address.');
        if (!Validate::text($d['city'], 2, 60))            return Validate::fail('Enter the city or town.');

        $pro = new ProController();
        $quote = $pro->applyToQuote((new DeliveryPricing())->quote($in['delivery_lat'] ?? '', $in['delivery_lng'] ?? ''), $customerId);
        if (!$quote['success']) return Validate::fail($quote['error']);
        $d['latitude'] = round((float) $in['delivery_lat'], 6);
        $d['longitude'] = round((float) $in['delivery_lng'], 6);
        $d['distance_km'] = $quote['km'];
        $d['fee'] = $quote[$d['option']];
        $subtotal = $cart->getCartTotal($cartKey);
        $d['discount'] = $pro->discountFor($subtotal, $customerId);   // Packe Pro: 10% off packaging
        return ['success' => true, 'delivery' => $d, 'amount' => round($subtotal - $d['discount'] + $d['fee'], 2)];
    }

    /**
     * Verify a Paystack payment server-side, then record the order.
     * Never trusts the browser: the reference, amount and currency must all match what we expected.
     */
    public function completePayment(string $reference, array $expected, int $customerId, string $cartKey): array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{6,100}$/', $reference) || $reference !== ($expected['reference'] ?? null)) {
            return Validate::fail('That payment reference does not match your checkout.');
        }
        if ($existing = $this->model->getOrderIdByReference($reference)) {
            return ['success' => true, 'order_id' => $existing];   // already recorded (e.g. page refreshed)
        }
        $data = PaystackGateway::verify($reference);
        if ($problem = PaystackGateway::mismatch($data, $reference, (float) $expected['amount'])) return Validate::fail($problem);

        try {
            $orderId = $this->model->createOrderFromCart(
                $customerId, $cartKey, $reference, $data['amount'] / 100,
                (string) ($data['channel'] ?? 'paystack'), $expected['delivery']
            );
        } catch (Throwable $e) {
            log_error('Order creation failed for ' . $reference . ': ' . $e->getMessage());
            return Validate::fail('Your payment went through but we could not save the order. Please contact us with reference ' . $reference . '.');
        }
        return ['success' => true, 'order_id' => $orderId];
    }

    /** An order with its items, only for its owner or an admin. */
    public function getOrderForViewer($orderId, int $viewerId, bool $isAdmin): ?array
    {
        $orderId = Validate::positiveInt($orderId);
        $order = $orderId ? $this->model->getOrderById($orderId) : null;
        if (!$order || (!$isAdmin && (int) $order['customer_id'] !== $viewerId && (int) $order['assigned_to'] !== $viewerId)) {
            return null;
        }
        $order['items'] = $this->model->getOrderItems($orderId);
        return $order;
    }

    public function getOrdersByCustomer(int $customerId): array
    {
        return $this->model->getOrdersByCustomer($customerId);
    }

    public function getAllOrders(string $status = ''): array
    {
        return $this->model->getAllOrders(in_array($status, OrderClass::STATUSES, true) ? $status : '');
    }

    public function updateOrderStatus($orderId, string $status): array
    {
        $orderId = Validate::positiveInt($orderId);
        if (!$orderId || !$this->model->getOrderById($orderId)) return Validate::fail('That order was not found.');
        return $this->model->updateOrderStatus($orderId, $status)
            ? ['success' => true] : Validate::fail('Choose a valid status.');
    }

    public function getSummary(): array
    {
        return $this->model->getSummary();
    }

    public static function statuses(): array
    {
        return OrderClass::STATUSES;
    }

    public static function statusLabel(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }
}
