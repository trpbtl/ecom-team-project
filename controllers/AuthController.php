<?php
/**
 * AuthController
 * Layer: Application | Owner: Hagen Dickson
 * Validates login credentials and manages user sessions.
 */
require_once __DIR__ . '/../config/db.php';

class AuthController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function register()
    {
        // TODO
    }

    public function login()
    {
        // TODO
    }

    public function logout()
    {
        // TODO
    }

    public function requireLogin()
    {
        // TODO
    }
}
