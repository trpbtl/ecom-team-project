<?php
/**
 * Model: customers. All SQL for accounts lives here. No HTML, no $_POST, no echo.
 */
require_once __DIR__ . '/../core/db_class.php';

class CustomerClass extends Database
{
    public function emailExists(string $email, int $exceptId = 0): bool
    {
        return $this->fetchOne(
            'SELECT customer_id FROM customers WHERE email = ? AND customer_id <> ?',
            'si', [$email, $exceptId]
        ) !== null;
    }

    public function addCustomer(array $d): int
    {
        $hash = password_hash($d['password'], PASSWORD_BCRYPT);
        $this->execute(
            'INSERT INTO customers (full_name, email, password_hash, phone, business_name, city, address)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            'sssssss',
            [$d['full_name'], $d['email'], $hash, $d['phone'], $d['business_name'], $d['city'], $d['address']]
        );
        return $this->conn->insert_id;
    }

    public function getCustomerByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM customers WHERE email = ?', 's', [$email]);
    }

    public function getCustomerById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT customer_id, full_name, email, phone, business_name, city, address, user_role, created_at
             FROM customers WHERE customer_id = ?',
            'i', [$id]
        );
    }

    /** Returns the customer row (without the password hash) or null. Google-only accounts (no password) can't log in this way. */
    public function login(string $email, string $password): ?array
    {
        $row = $this->getCustomerByEmail($email);
        if ($row === null || $row['password_hash'] === null || !password_verify($password, $row['password_hash'])) {
            return null;
        }
        unset($row['password_hash']);
        return $row;
    }

    public function updateCustomer(int $id, array $d): bool
    {
        $this->execute(
            'UPDATE customers SET full_name = ?, email = ?, phone = ?, business_name = ?, city = ?, address = ?
             WHERE customer_id = ?',
            'ssssssi',
            [$d['full_name'], $d['email'], $d['phone'], $d['business_name'], $d['city'], $d['address'], $id]
        );
        return true;
    }

    public function verifyPassword(int $id, string $password): bool
    {
        $row = $this->fetchOne('SELECT password_hash FROM customers WHERE customer_id = ?', 'i', [$id]);
        return $row !== null && $row['password_hash'] !== null && password_verify($password, $row['password_hash']);
    }

    /** False for accounts created with Google that have not set a password yet. */
    public function hasPassword(int $id): bool
    {
        $row = $this->fetchOne('SELECT password_hash FROM customers WHERE customer_id = ?', 'i', [$id]);
        return $row !== null && $row['password_hash'] !== null;
    }

    /* ----- Sign in with Google ----- */

    /** The customer linked to this Google account, or null. */
    public function getCustomerBySocial(string $provider, string $providerUserId): ?array
    {
        return $this->fetchOne(
            'SELECT c.customer_id, c.full_name, c.email, c.phone, c.business_name, c.city, c.address, c.user_role, c.created_at
             FROM social_logins s JOIN customers c ON c.customer_id = s.customer_id
             WHERE s.provider = ? AND s.provider_user_id = ?',
            'ss', [$provider, $providerUserId]
        );
    }

    public function linkSocial(int $customerId, string $provider, string $providerUserId, ?string $email): void
    {
        $this->execute(
            'INSERT INTO social_logins (customer_id, provider, provider_user_id, email) VALUES (?, ?, ?, ?)',
            'isss', [$customerId, $provider, $providerUserId, $email]
        );
    }

    /** A new account that signs in with Google: it has no password (password_hash is NULL). */
    public function addSocialCustomer(array $d): int
    {
        $this->execute(
            'INSERT INTO customers (full_name, email, password_hash, phone, business_name, city, address)
             VALUES (?, ?, NULL, ?, ?, ?, ?)',
            'ssssss',
            [$d['full_name'], $d['email'], $d['phone'], $d['business_name'], $d['city'], $d['address']]
        );
        return $this->conn->insert_id;
    }

    /** Which providers this customer has linked, e.g. ['google']. */
    public function getSocialProviders(int $customerId): array
    {
        return array_column($this->fetchAll('SELECT provider FROM social_logins WHERE customer_id = ?', 'i', [$customerId]), 'provider');
    }

    public function changePassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        return $this->execute('UPDATE customers SET password_hash = ? WHERE customer_id = ?', 'si', [$hash, $id]) === 1;
    }

    /** Customers with orders are kept for the records, so only accounts without orders can be deleted. */
    public function hasOrders(int $id): bool
    {
        return $this->fetchOne('SELECT order_id FROM orders WHERE customer_id = ? LIMIT 1', 'i', [$id]) !== null;
    }

    public function deleteCustomer(int $id): bool
    {
        return $this->execute('DELETE FROM customers WHERE customer_id = ?', 'i', [$id]) === 1;
    }

    public function getDeliveryStaff(): array
    {
        return $this->fetchAll(
            'SELECT customer_id, full_name, phone FROM customers WHERE user_role = 3 ORDER BY full_name'
        );
    }
}
