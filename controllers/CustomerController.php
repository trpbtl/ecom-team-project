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
        if ($row) return ['success' => true, 'customer' => $row];
        $existing = $this->model->getCustomerByEmail($email);
        if ($existing && $existing['password_hash'] === null) {
            return Validate::fail('This account signs in with Google. Use the "Continue with Google" button below.');
        }
        return Validate::fail('That email and password do not match.');
    }

    /* ----- Sign in with Google ----- */

    /**
     * Someone came back from Google with a verified identity.
     * - Already linked: log them in.
     * - Their verified email matches a Packe account: link it and log them in.
     * - Otherwise: they are new; return the details so they can finish signing up (phone and city are needed).
     * $who = ['provider', 'sub', 'email', 'email_verified', 'name']
     */
    public function socialSignIn(array $who): array
    {
        $customer = $this->model->getCustomerBySocial($who['provider'], $who['sub']);
        if ($customer) return ['success' => true, 'customer' => $customer];

        $email = strtolower(trim((string) $who['email']));
        if ($email !== '' && $who['email_verified'] && Validate::email($email)) {
            $existing = $this->model->getCustomerByEmail($email);
            if ($existing) {
                $this->model->linkSocial((int) $existing['customer_id'], $who['provider'], $who['sub'], $email);
                return ['success' => true, 'customer' => $this->model->getCustomerById((int) $existing['customer_id'])];
            }
        }
        return ['success' => false, 'new' => true];
    }

    /** Finish signing up after Google: $pending comes from the session, $in from the form. */
    public function registerSocial(array $pending, array $in): array
    {
        $d = $this->cleanProfile($in);
        // A verified email from Google can't be changed here; otherwise the customer types one
        if ($pending['email'] !== '' && $pending['email_verified']) $d['email'] = strtolower($pending['email']);
        if ($err = $this->checkProfile($d)) return Validate::fail($err);
        if ($this->model->emailExists($d['email'])) {
            return Validate::fail('That email already has a Packe account. Log in with your password first, then you can use '
                . ucfirst($pending['provider']) . ' next time.');
        }
        $id = $this->model->addSocialCustomer($d);
        $this->model->linkSocial($id, $pending['provider'], $pending['sub'], $pending['email'] ?: null);
        return ['success' => true, 'customer' => $this->model->getCustomerById($id)];
    }

    public function hasPassword(int $id): bool
    {
        return $this->model->hasPassword($id);
    }

    public function getSocialProviders(int $id): array
    {
        return $this->model->getSocialProviders($id);
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

    /** Accounts made with Google have no password yet: they set one without a current password. */
    public function changePassword(int $id, string $current, string $new, string $confirm): array
    {
        if ($this->model->hasPassword($id) && !$this->model->verifyPassword($id, $current)) {
            return Validate::fail('Your current password is not correct.');
        }
        if (!Validate::password($new)) return Validate::fail('New password must be at least 8 characters and include a number.');
        if ($new !== $confirm) return Validate::fail('The two new passwords do not match.');
        $this->model->changePassword($id, $new);
        return ['success' => true];
    }

    /** Accounts without a password (Google only) confirm by typing DELETE, checked in the action. */
    public function deleteAccount(int $id, string $password): array
    {
        if ($this->model->hasPassword($id) && !$this->model->verifyPassword($id, $password)) {
            return Validate::fail('Your password is not correct.');
        }
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
