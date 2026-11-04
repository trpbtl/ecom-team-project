<?php
/**
 * OrderController
 * Layer: Application | Owner: Eyram Awoye
 * Creates orders and manages order status updates.
 */
require_once __DIR__ . '/../config/db.php';

class OrderController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function create()
    {
        // TODO
    }

    public function getById()
    {
        // TODO
    }

    public function getAll()
    {
        // TODO
    }

    public function updateStatus()
    {
        // TODO
    }
}
