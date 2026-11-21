<?php
require_once __DIR__ . '/../core/db_class.php';

class BusinessClass extends Database
{
    public function getAll(string $search = '', int $role = 0): array
    {
        $sql = 'SELECT c.customer_id, c.full_name, c.email, c.phone, c.business_name, c.city, c.address, c.user_role, c.created_at,
                       (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.customer_id) AS order_count,
                       (c.password_hash IS NULL) AS google_only
                FROM customers c WHERE 1 = 1';
        $types = '';
        $params = [];
        if ($role > 0) {
            $sql .= ' AND c.user_role = ?';
            $types .= 'i';
            $params[] = $role;
        }
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $sql .= ' AND (c.full_name LIKE ? OR c.business_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.city LIKE ?)';
            $types .= 'sssss';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $sql .= ' ORDER BY c.created_at DESC, c.customer_id DESC';
        return $this->fetchAll($sql, $types, $params);
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT customer_id, full_name, email, phone, business_name, city, address, user_role, created_at
             FROM customers WHERE customer_id = ?',
            'i', [$id]
        );
    }

    public function emailExists(string $email, int $exceptId = 0): bool
    {
        return $this->fetchOne('SELECT customer_id FROM customers WHERE email = ? AND customer_id <> ?', 'si', [$email, $exceptId]) !== null;
    }

    public function add(array $d): int
    {
        $hash = password_hash($d['password'], PASSWORD_BCRYPT);
        $this->execute(
            'INSERT INTO customers (full_name, email, password_hash, phone, business_name, city, address, user_role)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            'sssssssi',
            [$d['full_name'], $d['email'], $hash, $d['phone'], $d['business_name'], $d['city'], $d['address'], $d['user_role']]
        );
        return $this->conn->insert_id;
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            'UPDATE customers SET full_name = ?, email = ?, phone = ?, business_name = ?, city = ?, address = ?, user_role = ?
             WHERE customer_id = ?',
            'ssssssii',
            [$d['full_name'], $d['email'], $d['phone'], $d['business_name'], $d['city'], $d['address'], $d['user_role'], $id]
        );
    }

    public function setPassword(int $id, string $password): void
    {
        $this->execute('UPDATE customers SET password_hash = ? WHERE customer_id = ?', 'si', [password_hash($password, PASSWORD_BCRYPT), $id]);
    }

    public function setRole(int $id, int $role): void
    {
        $this->execute('UPDATE customers SET user_role = ? WHERE customer_id = ?', 'ii', [$role, $id]);
    }

    public function countAdmins(): int
    {
        return (int) $this->fetchOne('SELECT COUNT(*) AS n FROM customers WHERE user_role = 1')['n'];
    }

    public function hasOrders(int $id): bool
    {
        return $this->fetchOne('SELECT order_id FROM orders WHERE customer_id = ? LIMIT 1', 'i', [$id]) !== null;
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM customers WHERE customer_id = ?', 'i', [$id]) === 1;
    }
}