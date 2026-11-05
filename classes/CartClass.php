<?php
/**
 * Model: the shopping cart. Rows are tied to a cart key (cookie), so guests can shop too.
 * A designed box keeps the price worked out for its design; other products use their base price.
 */
require_once __DIR__ . '/../core/db_class.php';

class CartClass extends Database
{
    /** Ready-made products only: designed boxes are always their own line. */
    public function isInCart(int $productId, string $cartKey): bool
    {
        return $this->fetchOne(
            'SELECT cart_id FROM cart WHERE product_id = ? AND cart_key = ? AND customization_id IS NULL',
            'is', [$productId, $cartKey]
        ) !== null;
    }

    public function addToCart(int $productId, int $qty, string $cartKey, ?int $customerId = null, ?int $customizationId = null): int
    {
        $this->execute(
            'INSERT INTO cart (cart_key, customer_id, product_id, customization_id, quantity) VALUES (?, ?, ?, ?, ?)',
            'siiii', [$cartKey, $customerId, $productId, $customizationId, $qty]
        );
        return $this->conn->insert_id;
    }

    public function getCartItems(string $cartKey): array
    {
        return $this->fetchAll(
            'SELECT ct.cart_id, ct.product_id, ct.customization_id, ct.quantity,
                    p.name, p.image, p.material AS product_material, c.category_name,
                    COALESCE(cz.unit_price, p.base_price) AS unit_price,
                    COALESCE(cz.unit_price, p.base_price) * ct.quantity AS line_total,
                    cz.length_cm, cz.width_cm, cz.height_cm, cz.material, cz.print_colour, cz.logo_file, cz.brand_text
             FROM cart ct
             JOIN products p ON p.product_id = ct.product_id
             JOIN categories c ON c.category_id = p.category_id
             LEFT JOIN customizations cz ON cz.customization_id = ct.customization_id
             WHERE ct.cart_key = ?
             ORDER BY ct.added_at ASC',
            's', [$cartKey]
        );
    }

    public function getCartCount(string $cartKey): int
    {
        $row = $this->fetchOne('SELECT COALESCE(SUM(quantity), 0) AS n FROM cart WHERE cart_key = ?', 's', [$cartKey]);
        return (int) $row['n'];
    }

    public function getCartTotal(string $cartKey): float
    {
        $row = $this->fetchOne(
            'SELECT COALESCE(SUM(COALESCE(cz.unit_price, p.base_price) * ct.quantity), 0) AS total
             FROM cart ct
             JOIN products p ON p.product_id = ct.product_id
             LEFT JOIN customizations cz ON cz.customization_id = ct.customization_id
             WHERE ct.cart_key = ?',
            's', [$cartKey]
        );
        return round((float) $row['total'], 2);
    }

    public function removeFromCart(int $cartId, string $cartKey): bool
    {
        return $this->execute('DELETE FROM cart WHERE cart_id = ? AND cart_key = ?', 'is', [$cartId, $cartKey]) === 1;
    }

    /** Designed boxes are priced for the quantity chosen in the designer, so only ready-made lines can change here. */
    public function updateQty(int $cartId, int $qty, string $cartKey): bool
    {
        if ($qty < 1) {
            return false;
        }
        $this->execute(
            'UPDATE cart SET quantity = ? WHERE cart_id = ? AND cart_key = ? AND customization_id IS NULL',
            'iis', [$qty, $cartId, $cartKey]
        );
        return true;
    }

    /** After login, the guest cart becomes the customer's cart. */
    public function attachCustomer(string $cartKey, int $customerId): void
    {
        $this->execute('UPDATE cart SET customer_id = ? WHERE cart_key = ?', 'is', [$customerId, $cartKey]);
        $this->execute('UPDATE customizations SET customer_id = ? WHERE cart_key = ? AND customer_id IS NULL', 'is', [$customerId, $cartKey]);
    }
}
