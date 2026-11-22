<?php
/**
 * Controller: delivery assignment and tracking.
 * Owner: Eyram Awoye
 */
require_once __DIR__ . '/../classes/DeliveryClass.php';
require_once __DIR__ . '/../classes/CustomerClass.php';
require_once __DIR__ . '/Validate.php';

class DeliveryController
{
    private DeliveryClass $model;

    public function __construct()
    {
        $this->model = new DeliveryClass();
    }

    public function assign($orderId, $staffId): array
    {
        $orderId = Validate::positiveInt($orderId);
        $staffId = Validate::positiveInt($staffId);
        $staff = $staffId ? (new CustomerClass())->getCustomerById($staffId) : null;
        if (!$orderId) return Validate::fail('That order was not found.');
        if (!$staff || (int) $staff['user_role'] !== 3) return Validate::fail('Choose a delivery staff member.');
        $this->model->assignDelivery($orderId, $staffId);
        return ['success' => true, 'name' => $staff['full_name']];
    }

    /** Record the Uber / Yango / Bolt driver an admin booked in the partner's app. */
    public function bookPartner($orderId, array $in): array
    {
        $orderId = Validate::positiveInt($orderId);
        if (!$orderId) return Validate::fail('That order was not found.');
        $name = Validate::clean($in['driver_name'] ?? '');
        $phone = Validate::clean($in['driver_phone'] ?? '');
        $url = trim((string) ($in['tracking_url'] ?? ''));
        if (!Validate::text($name, 2, 100)) return Validate::fail('Enter the driver’s name from the partner app.');
        if (!Validate::phone($phone))       return Validate::fail('Enter the driver’s phone number (7 to 15 digits).');
        if ($url !== '' && (strlen($url) > 255 || !preg_match('#^https://#i', $url) || filter_var($url, FILTER_VALIDATE_URL) === false)) {
            return Validate::fail('The tracking link must be a full https:// link from the partner app.');
        }
        $this->model->setPartnerDriver($orderId, $name, $phone, $url === '' ? null : $url);
        return ['success' => true, 'name' => $name];
    }

    public function getDeliveriesForStaff(int $staffId): array
    {
        return $this->model->getDeliveriesForStaff($staffId);
    }

    /** Delivery staff can only update deliveries assigned to them; admins can update any. */
    public function updateStatus($deliveryId, string $status, int $userId, bool $isAdmin): array
    {
        $deliveryId = Validate::positiveInt($deliveryId);
        $d = $deliveryId ? $this->model->getDeliveryById($deliveryId) : null;
        if (!$d || (!$isAdmin && (int) $d['assigned_to'] !== $userId)) return Validate::fail('That delivery was not found.');
        return $this->model->updateStatus($deliveryId, $status)
            ? ['success' => true] : Validate::fail('Choose a valid delivery status.');
    }

    public static function statuses(): array
    {
        return DeliveryClass::STATUSES;
    }

    public static function statusLabel(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }
}
