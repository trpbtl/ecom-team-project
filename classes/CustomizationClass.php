<?php
/**
 * Model: boxes designed in the 3D designer.
 */
require_once __DIR__ . '/../core/db_class.php';

class CustomizationClass extends Database
{
    public function addCustomization(array $d): int
    {
        $this->execute(
            'INSERT INTO customizations (customer_id, cart_key, product_id, length_cm, width_cm, height_cm,
                                         material, print_colour, logo_file, brand_text, quantity, unit_price)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'isidddssssid',
            [$d['customer_id'], $d['cart_key'], $d['product_id'], $d['length_cm'], $d['width_cm'], $d['height_cm'],
             $d['material'], $d['print_colour'], $d['logo_file'], $d['brand_text'], $d['quantity'], $d['unit_price']]
        );
        return $this->conn->insert_id;
    }

    public function getCustomizationById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM customizations WHERE customization_id = ?', 'i', [$id]);
    }

    public function getCustomizationsByCustomer(int $customerId): array
    {
        return $this->fetchAll(
            'SELECT cz.*, p.name AS product_name FROM customizations cz
             JOIN products p ON p.product_id = cz.product_id
             WHERE cz.customer_id = ? ORDER BY cz.created_at DESC',
            'i', [$customerId]
        );
    }
}
