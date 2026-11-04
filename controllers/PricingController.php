<?php
/**
 * PricingController
 * Layer: Application | Owner: Josephine Allan
 * Calculates product prices and customization costs.
 */
require_once __DIR__ . '/../config/db.php';

class PricingController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function calculatePrice()
    {
        // TODO
    }

    public function getMaterialCost()
    {
        // TODO
    }

    public function getQuantityDiscount()
    {
        // TODO
    }
}
