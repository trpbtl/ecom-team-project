<?php
/**
 * DeliveryController
 * Layer: Application | Owner: Eyram Awoye
 * Calculates delivery charges and manages delivery assignments and tracking.
 */
require_once __DIR__ . '/../config/db.php';

class DeliveryController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function calculateFee()
    {
        // TODO
    }

    public function create()
    {
        // TODO
    }

    public function assign()
    {
        // TODO
    }

    public function updateStatus()
    {
        // TODO
    }

    public function getAssigned()
    {
        // TODO
    }
}
