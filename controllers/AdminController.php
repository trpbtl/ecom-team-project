<?php
/**
 * AdminController
 * Layer: Application | Owner: Eyram Awoye
 * Manages administrative activities and generates reports.
 */
require_once __DIR__ . '/../config/db.php';

class AdminController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getDashboardStats()
    {
        // TODO
    }

    public function getCustomers()
    {
        // TODO
    }

    public function generateReport()
    {
        // TODO
    }
}
