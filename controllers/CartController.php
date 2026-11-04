<?php
/**
 * CartController
 * Layer: Application | Owner: Donna Omaboe
 * Adds, removes and updates products in the shopping cart.
 */
require_once __DIR__ . '/../config/db.php';

class CartController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function add()
    {
        // TODO
    }

    public function remove()
    {
        // TODO
    }

    public function updateQuantity()
    {
        // TODO
    }

    public function getItems()
    {
        // TODO
    }

    public function getTotal()
    {
        // TODO
    }
}
