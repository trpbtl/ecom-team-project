<?php
/**
 * Controller: shopping cart.
 * Owner: Donna Omaboe
 */
require_once __DIR__ . '/../classes/CartClass.php';
require_once __DIR__ . '/../classes/ProductClass.php';
require_once __DIR__ . '/Validate.php';

class CartController
{
    private CartClass $model;

    public function __construct()
    {
        $this->model = new CartClass();
    }

    public function addProduct($productId, $qty, string $cartKey, ?int $customerId): array
    {
        $productId = Validate::positiveInt($productId);
        $qty = Validate::positiveInt($qty) ?? 1;
        $product = $productId ? (new ProductClass())->getProductById($productId) : null;
        if (!$product) return Validate::fail('That product was not found.');
        if ($product['is_customizable']) {
            return ['success' => false, 'design' => true, 'error' => 'Design your box first, then add it to the cart.'];
        }
        if ($qty > 10000) return Validate::fail('Please contact us for orders over 10,000 units.');
        if ($this->model->isInCart($productId, $cartKey)) {
            return Validate::fail('This item is already in your cart. Go to your cart to change the quantity.');
        }
        $this->model->addToCart($productId, $qty, $cartKey, $customerId);
        return ['success' => true, 'name' => $product['name']];
    }

    public function addDesign(int $productId, int $customizationId, int $qty, string $cartKey, ?int $customerId): void
    {
        $this->model->addToCart($productId, $qty, $cartKey, $customerId, $customizationId);
    }

    public function getCartItems(string $cartKey): array
    {
        return $this->model->getCartItems($cartKey);
    }

    public function getCartCount(string $cartKey): int
    {
        return $this->model->getCartCount($cartKey);
    }

    public function getCartTotal(string $cartKey): float
    {
        return $this->model->getCartTotal($cartKey);
    }

    /** $cartIds: the ticked "remove" boxes. */
    public function removeItems(array $cartIds, string $cartKey): array
    {
        $removed = 0;
        foreach ($cartIds as $id) {
            if ($id = Validate::positiveInt($id)) {
                $removed += (int) $this->model->removeFromCart($id, $cartKey);
            }
        }
        return $removed ? ['success' => true, 'count' => $removed] : Validate::fail('Tick the items you want to remove.');
    }

    /** $quantities: cart_id => new quantity. */
    public function updateQuantities(array $quantities, string $cartKey): array
    {
        foreach ($quantities as $cartId => $qty) {
            $cartId = Validate::positiveInt($cartId);
            $qty = Validate::positiveInt($qty);
            if (!$cartId || !$qty || $qty > 10000) {
                return Validate::fail('Quantities must be whole numbers from 1 to 10,000.');
            }
            $this->model->updateQty($cartId, $qty, $cartKey);
        }
        return ['success' => true];
    }

    public function attachCustomer(string $cartKey, int $customerId): void
    {
        $this->model->attachCustomer($cartKey, $customerId);
    }
}
