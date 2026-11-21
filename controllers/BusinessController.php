<?php
require_once __DIR__ . '/../classes/BusinessClass.php';
require_once __DIR__ . '/Validate.php';

class BusinessController
{
    private BusinessClass $model;

    public function __construct()
    {
        $this->model = new BusinessClass();
    }

    public static function roles(): array
    {
        return [1 => 'Admin', 2 => 'Business / customer', 3 => 'Delivery staff'];
    }

    public function getAll(string $search = '', int $role = 0): array
    {
        return $this->model->getAll(Validate::clean($search), $role);
    }

    public function getById(int $id): ?array
    {
        return $this->model->getById($id);
    }

    private function clean(array $in): array
    {
        return [
            'full_name'     => Validate::clean($in['full_name'] ?? ''),
            'email'         => strtolower(Validate::clean($in['email'] ?? '')),
            'phone'         => Validate::clean($in['phone'] ?? ''),
            'business_name' => Validate::clean($in['business_name'] ?? ''),
            'city'          => Validate::clean($in['city'] ?? ''),
            'address'       => Validate::clean($in['address'] ?? ''),
            'user_role'     => (int) ($in['user_role'] ?? 2),
        ];
    }

    private function check(array $d): ?string
    {
        if (!Validate::text($d['full_name'], 2, 100)) return 'Enter the contact person\'s full name (2 to 100 characters).';
        if (!Validate::email($d['email']))            return 'Enter a valid email address.';
        if (!Validate::phone($d['phone']))            return 'Enter a valid phone number (7 to 15 digits).';
        if (!Validate::text($d['city'], 2, 60))       return 'Enter the city.';
        if (mb_strlen($d['business_name']) > 150)     return 'Business name must be 150 characters or fewer.';
        if (mb_strlen($d['address']) > 255)           return 'Address must be 255 characters or fewer.';
        if (!isset(self::roles()[$d['user_role']]))   return 'Choose a valid role.';
        return null;
    }

    public function add(array $in): array
    {
        $d = $this->clean($in);
        $d['password'] = (string) ($in['password'] ?? '');
        if ($err = $this->check($d)) return Validate::fail($err);
        if (!Validate::password($d['password'])) return Validate::fail('Password must be at least 8 characters and include a number.');
        if ($this->model->emailExists($d['email'])) return Validate::fail('That email is already registered.');
        $this->model->add($d);
        return ['success' => true];
    }

    public function update(int $id, array $in, int $actingId): array
    {
        $current = $this->model->getById($id);
        if (!$current) return Validate::fail('That account no longer exists.');
        $d = $this->clean($in);
        if ($err = $this->check($d)) return Validate::fail($err);
        if ($this->model->emailExists($d['email'], $id)) return Validate::fail('Another account already uses that email.');
        if ((int) $current['user_role'] === 1 && $d['user_role'] !== 1) {
            if ($id === $actingId) return Validate::fail('You cannot remove your own admin role.');
            if ($this->model->countAdmins() <= 1) return Validate::fail('There must be at least one admin.');
        }
        $this->model->update($id, $d);

        $newPass = (string) ($in['password'] ?? '');
        if ($newPass !== '') {
            if (!Validate::password($newPass)) return Validate::fail('Details saved, but the new password must be at least 8 characters and include a number.');
            $this->model->setPassword($id, $newPass);
        }
        return ['success' => true];
    }

    public function setRole(int $id, int $role, int $actingId): array
    {
        if (!isset(self::roles()[$role])) return Validate::fail('Choose a valid role.');
        $current = $this->model->getById($id);
        if (!$current) return Validate::fail('That account no longer exists.');
        if ((int) $current['user_role'] === 1 && $role !== 1) {
            if ($id === $actingId) return Validate::fail('You cannot remove your own admin role.');
            if ($this->model->countAdmins() <= 1) return Validate::fail('There must be at least one admin.');
        }
        $this->model->setRole($id, $role);
        return ['success' => true];
    }

    public function delete(int $id, int $actingId): array
    {
        $current = $this->model->getById($id);
        if (!$current) return Validate::fail('That account no longer exists.');
        if ($id === $actingId) return Validate::fail('You cannot delete your own account here.');
        if ((int) $current['user_role'] === 1 && $this->model->countAdmins() <= 1) return Validate::fail('There must be at least one admin.');
        if ($this->model->hasOrders($id)) {
            return Validate::fail('This business has orders, which are kept for the records. Change its role instead, or delete it directly in the database if you are sure.');
        }
        $this->model->delete($id);
        return ['success' => true];
    }
}