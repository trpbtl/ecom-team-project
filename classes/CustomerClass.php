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

    /** Returns the customer row (without the password hash) or null. */
    public function login(string $email, string $password): ?array
    {
        $row = $this->getCustomerByEmail($email);
        if ($row === null || !password_verify($password, $row['password_hash'])) {
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
        return $row !== null && password_verify($password, $row['password_hash']);
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
