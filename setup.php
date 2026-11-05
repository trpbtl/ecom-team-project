<?php
/**
 * One-time database setup for the hosted site (when the mysql client can't reach the database).
 * Creates the database and loads database/packe.sql ONLY if the database has no tables yet.
 * Once set up, it changes nothing, so it can never wipe data.
 */
require_once __DIR__ . '/core/db_class.php';
header('Content-Type: text/plain; charset=utf-8');

if (!preg_match('/^\w+$/', DB_NAME)) {
    exit('Setup stopped: DB_NAME may only contain letters, numbers and underscores.');
}

try {
    $c = Database::open(null);
    $c->query('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $c->select_db(DB_NAME);

    $tables = (int) $c->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetch_row()[0];
    if ($tables > 0) {
        exit("Already set up: the database has $tables tables. Nothing was changed.");
    }

    // Skip the lines meant for a local import (creating/choosing/dropping), then run the rest
    $sql = file_get_contents(__DIR__ . '/database/packe.sql');
    $sql = preg_replace('/^\s*(CREATE DATABASE|USE |DROP TABLE)[^;]*;\s*$/mi', '', $sql);

    $c->multi_query($sql);
    do {
        if ($r = $c->store_result()) {
            $r->free();
        }
    } while ($c->more_results() && $c->next_result());

    $tables = (int) $c->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetch_row()[0];
    $products = (int) $c->query('SELECT COUNT(*) FROM products')->fetch_row()[0];
    echo "Done: created $tables tables and $products starter products. Open the home page to start.";
} catch (Throwable $e) {
    error_log('Setup failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Setup failed (error ' . $e->getCode() . '). Check the database settings in Render and try again.';
}
