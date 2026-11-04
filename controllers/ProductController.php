<?php
/**
 * ProductController
 * Layer: Application | Owner: Hagen Dickson
 * Retrieves, adds, updates and deletes packaging products.
 */
require_once __DIR__ . '/../config/db.php';

class ProductController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getAll()
    {
        // TODO
    }

    public function getById()
    {
        // TODO
    }

    public function getFeatured()
    {
        // TODO
    }

    public function create()
    {
        // TODO
    }

    public function update()
    {
        // TODO
    }

    public function delete()
    {
        // TODO
    }
}
