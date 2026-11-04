<?php
/**
 * PaymentController
 * Layer: Application | Owner: Donna Omaboe
 * Processes payment requests and records payment results.
 */
require_once __DIR__ . '/../config/db.php';

class PaymentController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function initiate()
    {
        // TODO
    }

    public function handleCallback()
    {
        // TODO
    }

    public function recordPayment()
    {
        // TODO
    }
}
