<?php
/**
 * Controller: customer accounts. Validates input, calls CustomerClass, returns result arrays.
 * Owner: Hagen Dickson
 */
require_once __DIR__ . '/../classes/CustomerClass.php';
require_once __DIR__ . '/Validate.php';

class CustomerController
{
    private CustomerClass $model;

    public function __construct()
    {
        $this->model = new CustomerClass();
    }

    /** Shared checks for registration and profile edits. Returns an error message or null. */
    private function checkProfile(array $d): ?string
    {
        if (!Validate::text($d['full_name'], 2, 100)) return 'Enter your full name (2 to 100 characters).';
        if (!Validate::email($d['email']))            return 'Enter a valid email address.';
        if (!Validate::phone($d['phone']))            return 'Enter a valid phone number (7 to 15 digits).';
        if (!Validate::text($d['city'], 2, 60))       return 'Enter your city.';
        if (mb_strlen($d['business_name']) > 150)     return 'Business name must be 150 characters or fewer.';
        if (mb_strlen($d['address']) > 255)           return 'Address must be 255 characters or fewer.';
        return null;
    }

    private function cleanProfile(array $in): array
    {
        return [
            'full_name'     => Validate::clean($in['full_name'] ?? ''),
            'email'         => strtolower(Validate::clean($in['email'] ?? '')),
            'phone'         => Validate::clean($in['phone'] ?? ''),
            'business_name' => Validate::clean($in['business_name'] ?? ''),
            'city'          => Validate::clean($in['city'] ?? ''),
            'address'       => Validate::clean($in['address'] ?? ''),
        ];
    }

    public function register(array $in): array
    {
        $d = $this->cleanProfile($in);
        $d['password'] = (string) ($in['password'] ?? '');
        if ($err = $this->checkProfile($d)) return Validate::fail($err);
        if (!Validate::password($d['password'])) return Validate::fail('Password must be at least 8 characters and include a number.');
        if ($d['password'] !== (string) ($in['confirm_password'] ?? '')) return Validate::fail('The two passwords do not match.');
        if ($this->model->emailExists($d['email'])) return Validate::fail('That email is already registered. Try logging in.');

        $id = $this->model->addCustomer($d);
        return ['success' => true, 'customer' => $this->model->getCustomerById($id)];
    }

    public function login(string $email, string $password): array
    {
        $email = strtolower(Validate::clean($email));
        if (!Validate::email($email) || $password === '') return Validate::fail('Enter your email and password.');
        $row = $this->model->login($email, $password);
        return $row ? ['success' => true, 'customer' => $row] : Validate::fail('That email and password do not match.');
    }

    public function getCustomer(int $id): ?array
    {
        return $this->model->getCustomerById($id);
    }

    public function updateProfile(int $id, array $in): array
    {
        $d = $this->cleanProfile($in);
        if ($err = $this->checkProfile($d)) return Validate::fail($err);
        if ($this->model->emailExists($d['email'], $id)) return Validate::fail('Another account already uses that email.');
        $this->model->updateCustomer($id, $d);
        return ['success' => true, 'customer' => $this->model->getCustomerById($id)];
    }

    public function changePassword(int $id, string $current, string $new, string $confirm): array
    {
        if (!$this->model->verifyPassword($id, $current)) return Validate::fail('Your current password is not correct.');
        if (!Validate::password($new)) return Validate::fail('New password must be at least 8 characters and include a number.');
        if ($new !== $confirm) return Validate::fail('The two new passwords do not match.');
        $this->model->changePassword($id, $new);
        return ['success' => true];
    }

    public function deleteAccount(int $id, string $password): array
    {
        if (!$this->model->verifyPassword($id, $password)) return Validate::fail('Your password is not correct.');
        if ($this->model->hasOrders($id)) {
            return Validate::fail('Accounts with orders are kept for our records. Contact us to close this account.');
        }
        $this->model->deleteCustomer($id);
        return ['success' => true];
    }

    public function getDeliveryStaff(): array
    {
        return $this->model->getDeliveryStaff();
    }
}
