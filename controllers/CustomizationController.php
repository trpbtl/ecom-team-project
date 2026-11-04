<?php
/**
 * CustomizationController
 * Layer: Application | Owner: Josephine Allan
 * Processes customer packaging specifications and design requests.
 */
require_once __DIR__ . '/../config/db.php';

class CustomizationController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function save()
    {
        // TODO
    }

    public function getById()
    {
        // TODO
    }

    public function uploadDesign()
    {
        // TODO
    }
}
