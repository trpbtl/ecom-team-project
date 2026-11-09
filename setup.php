<?php
/**
 * Database setup and upgrades for the hosted site (when the mysql client can't reach the database).
 * Creates the database and loads database/packe.sql ONLY if the database has no tables yet.
 * Once set up, it only applies small upgrades (adding columns), so it can never wipe data.
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
        exit("Already set up: the database has $tables tables. " . upgrade($c));
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
    exit('Setup failed (error ' . $e->getCode() . '). Check the database settings in Render and try again.');
}

/**
 * Bring an existing database up to date. Each step checks first, so running it again changes nothing.
 * It only adds things; it never deletes data.
 */
function upgrade(mysqli $c): string
{
    $done = [];
    $hasCol = $c->query("SELECT COUNT(*) FROM information_schema.columns
                         WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'design_type'")->fetch_row()[0];
    if (!$hasCol) {
        // Every packaging type gets its own 3D designer template
        $c->query('ALTER TABLE products ADD COLUMN design_type VARCHAR(20) NULL AFTER is_customizable');
        $map = [
            'Custom Mailer Box' => 'mailer', 'Corrugated Shipping Box' => 'carton', 'Collapsible Apparel Box' => 'mailer',
            'Clear Window Pouch' => 'pouch', 'Tamper Evident Seal' => 'seal', 'Rectangular Address Label' => 'label',
            'Circular Product Sticker' => 'sticker', 'Business Product Catalog' => 'catalog',
            'Marketing Tri-fold Brochure' => 'brochure', 'Branded Thank-You Card' => 'card',
        ];
        $st = $c->prepare('UPDATE products SET design_type = ?, is_customizable = 1 WHERE name = ? AND design_type IS NULL');
        $n = 0;
        foreach ($map as $name => $type) {
            $st->bind_param('ss', $type, $name);
            $st->execute();
            $n += $st->affected_rows;
        }
        $st->close();
        // Any other product an admin had marked as designable keeps working as a mailer box
        $c->query("UPDATE products SET design_type = 'mailer' WHERE is_customizable = 1 AND design_type IS NULL");
        $done[] = "added 3D designer types ($n starter products can now be designed)";
    }
    $hasFoodBox = $c->query("SELECT COUNT(*) FROM products WHERE design_type = 'foodbox'")->fetch_row()[0];
    if (!$hasFoodBox) {
        // The takeaway food box designer: use the existing Kraft Food Box if there is one, otherwise add it
        $c->query("INSERT IGNORE INTO categories (category_name) VALUES ('Food Packaging')");
        $c->query("UPDATE products SET design_type = 'foodbox', is_customizable = 1 WHERE name = 'Kraft Food Box'");
        if ($c->affected_rows === 0) {
            $c->query("INSERT INTO products (category_id, name, description, material, base_price, keywords, is_customizable, design_type, image)
                       SELECT category_id, 'Kraft Food Box', 'Fold-top takeaway box for meals and pastries, sealed with your logo sticker.',
                              'Food-safe kraft board', 38.50, 'food box takeaway restaurant lunch', 1, 'foodbox', 'catalog/food-box.svg'
                       FROM categories WHERE category_name = 'Food Packaging'");
        }
        $done[] = 'added the takeaway food box designer';
    }
    $hasFont = $c->query("SELECT COUNT(*) FROM information_schema.columns
                          WHERE table_schema = DATABASE() AND table_name = 'customizations' AND column_name = 'font'")->fetch_row()[0];
    if (!$hasFont) {
        // Customers choose the font for the text on their packaging
        $c->query('ALTER TABLE customizations ADD COLUMN font VARCHAR(20) NULL AFTER brand_text');
        $done[] = 'added the font choice to designs';
    }
    $hasTextStyle = $c->query("SELECT COUNT(*) FROM information_schema.columns
                               WHERE table_schema = DATABASE() AND table_name = 'customizations' AND column_name = 'text_size'")->fetch_row()[0];
    if (!$hasTextStyle) {
        // Customers set the size, colour and italic style of their text
        $c->query("ALTER TABLE customizations
                   ADD COLUMN text_size SMALLINT NOT NULL DEFAULT 100 AFTER font,
                   ADD COLUMN text_colour CHAR(7) NULL AFTER text_size,
                   ADD COLUMN text_italic TINYINT NOT NULL DEFAULT 0 AFTER text_colour");
        $done[] = 'added text size, colour and italic to designs';
    }
    $hasPattern = $c->query("SELECT COUNT(*) FROM information_schema.columns
                             WHERE table_schema = DATABASE() AND table_name = 'customizations' AND column_name = 'pattern'")->fetch_row()[0];
    if (!$hasPattern) {
        // Customers can print a standard pattern all over their packaging
        $c->query("ALTER TABLE customizations
                   ADD COLUMN pattern VARCHAR(30) NULL AFTER text_italic,
                   ADD COLUMN pattern_scale SMALLINT NOT NULL DEFAULT 100 AFTER pattern");
        $done[] = 'added patterns to designs';
    }
    return $done ? 'Upgraded: ' . implode('; ', $done) . '.' : 'Nothing needed changing.';
}
