-- Packe custom packaging platform: MySQL schema and starter data
-- Local (XAMPP):  mysql -u root -p < database/packe.sql   (or import in phpMyAdmin)
-- Aiven:          remove the CREATE DATABASE / USE lines and import into your Aiven database

CREATE DATABASE IF NOT EXISTS packe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE packe;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS social_logins, deliveries, payments, order_items, orders, cart, customizations, products, categories, customers;
SET FOREIGN_KEY_CHECKS = 1;

-- user_role: 1 = admin, 2 = customer (default), 3 = delivery staff
CREATE TABLE customers (
    customer_id    INT AUTO_INCREMENT PRIMARY KEY,
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(100) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NULL,          -- NULL = signs in only with Google
    phone          VARCHAR(20)  NOT NULL,
    business_name  VARCHAR(150) NULL,
    city           VARCHAR(60)  NOT NULL,
    address        VARCHAR(255) NULL,
    user_role      TINYINT      NOT NULL DEFAULT 2,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Google accounts linked to a customer (sign in with Google)
CREATE TABLE social_logins (
    login_id          INT AUTO_INCREMENT PRIMARY KEY,
    customer_id       INT NOT NULL,
    provider          VARCHAR(20) NOT NULL,           -- 'google'
    provider_user_id  VARCHAR(255) NOT NULL,          -- the provider's permanent id for the person ("sub")
    email             VARCHAR(255) NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_link (provider, provider_user_id),
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categories (
    category_id    INT AUTO_INCREMENT PRIMARY KEY,
    category_name  VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE products (
    product_id       INT AUTO_INCREMENT PRIMARY KEY,
    category_id      INT NOT NULL,
    name             VARCHAR(150) NOT NULL,
    description      TEXT NULL,
    material         VARCHAR(100) NULL,
    base_price       DECIMAL(10,2) NOT NULL,
    image            VARCHAR(255) NULL,
    keywords         VARCHAR(255) NULL,
    is_customizable  TINYINT NOT NULL DEFAULT 0,      -- 1 = can be designed in the 3D designer
    design_type      VARCHAR(20) NULL,                -- which designer template (see controllers/DesignTypes.php)
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB;

-- Something a customer designed in the 3D designer (length/width/height mean what the template says: e.g. pouch gusset, catalogue pages)
CREATE TABLE customizations (
    customization_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id      INT NULL,
    cart_key         VARCHAR(64) NOT NULL,
    product_id       INT NOT NULL,
    length_cm        DECIMAL(5,1) NOT NULL,
    width_cm         DECIMAL(5,1) NOT NULL,
    height_cm        DECIMAL(5,1) NOT NULL,
    material         ENUM('kraft','white') NOT NULL DEFAULT 'kraft',
    print_colour     CHAR(7) NULL,
    logo_file        VARCHAR(255) NULL,
    brand_text       VARCHAR(40) NULL,
    font             VARCHAR(20) NULL,                -- font for the text (see DesignTypes::FONTS)
    text_size        SMALLINT NOT NULL DEFAULT 100,   -- text size in % (60–160)
    text_colour      CHAR(7) NULL,                    -- NULL = automatic (dark or light)
    text_italic      TINYINT NOT NULL DEFAULT 0,
    pattern          VARCHAR(30) NULL,                -- standard pattern printed all over (see DesignTypes::PATTERNS)
    pattern_scale    SMALLINT NOT NULL DEFAULT 100,   -- pattern size in % (50–200)
    quantity         INT NOT NULL,
    unit_price       DECIMAL(10,2) NOT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL,
    FOREIGN KEY (product_id)  REFERENCES products(product_id)
) ENGINE=InnoDB;

-- cart_key is a random token kept in a cookie, so guests can shop before logging in
CREATE TABLE cart (
    cart_id          INT AUTO_INCREMENT PRIMARY KEY,
    cart_key         VARCHAR(64) NOT NULL,
    customer_id      INT NULL,
    product_id       INT NOT NULL,
    customization_id INT NULL,
    quantity         INT NOT NULL DEFAULT 1,
    added_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (cart_key),
    FOREIGN KEY (customer_id)      REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id)       REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (customization_id) REFERENCES customizations(customization_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orders (
    order_id          INT AUTO_INCREMENT PRIMARY KEY,
    customer_id       INT NOT NULL,
    invoice_no        VARCHAR(20) NOT NULL UNIQUE,
    subtotal          DECIMAL(10,2) NOT NULL,
    delivery_fee      DECIMAL(10,2) NOT NULL,
    total_amount      DECIMAL(10,2) NOT NULL,
    order_status      ENUM('paid','processing','ready','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'paid',
    payment_reference VARCHAR(100) NOT NULL UNIQUE,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
    order_item_id    INT AUTO_INCREMENT PRIMARY KEY,
    order_id         INT NOT NULL,
    product_id       INT NOT NULL,
    customization_id INT NULL,
    quantity         INT NOT NULL,
    unit_price       DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)         REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id)       REFERENCES products(product_id),
    FOREIGN KEY (customization_id) REFERENCES customizations(customization_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE payments (
    payment_id  INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    currency    CHAR(3) NOT NULL DEFAULT 'GHS',
    channel     VARCHAR(30) NULL,
    reference   VARCHAR(100) NOT NULL UNIQUE,
    paid_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE deliveries (
    delivery_id      INT AUTO_INCREMENT PRIMARY KEY,
    order_id         INT NOT NULL UNIQUE,
    recipient_name   VARCHAR(100) NOT NULL,
    recipient_phone  VARCHAR(20)  NOT NULL,
    address          VARCHAR(255) NOT NULL,
    city             VARCHAR(60)  NOT NULL,
    delivery_option  ENUM('standard','express') NOT NULL DEFAULT 'standard',
    delivery_fee     DECIMAL(10,2) NOT NULL,
    assigned_to      INT NULL,
    status           ENUM('pending','assigned','in_transit','delivered') NOT NULL DEFAULT 'pending',
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)    REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES customers(customer_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Starter data: the categories and products from the Packe CMS site
INSERT INTO categories (category_name) VALUES ('Custom Packages'), ('Custom Labels'), ('Printed Materials'), ('Food Packaging');

INSERT INTO products (category_id, name, description, material, base_price, keywords, is_customizable, design_type, image) VALUES
(1, 'Custom Mailer Box', 'Design your own mailer box in 3D: choose the size, kraft or white board, a print colour and your logo. Price per box depends on size and quantity.', 'Corrugated cardboard (E-flute)', 1.20, 'mailer box custom design 3d branded', 1, 'mailer', 'catalog/mailer-box.svg'),
(1, 'Corrugated Shipping Box', 'Strong corrugated box for shipping orders across Ghana.', 'Corrugated cardboard', 45.00, 'shipping box corrugated carton', 1, 'carton', 'catalog/shipping-box.svg'),
(1, 'Collapsible Apparel Box', 'Folding box for clothing and accessories.', 'Rigid board', 60.00, 'apparel box clothing fashion', 1, 'mailer', 'catalog/apparel-box.svg'),
(1, 'Clear Window Pouch', 'Stand-up pouch with a clear window to show your product.', 'Kraft paper with PET window', 35.00, 'pouch window food snacks', 1, 'pouch', 'catalog/window-pouch.svg'),
(2, 'Tamper Evident Seal', 'Seal that shows if a package has been opened.', 'Vinyl', 22.00, 'seal label security', 1, 'seal', 'catalog/tamper-seal.svg'),
(2, 'Rectangular Address Label', 'Printed address labels for parcels.', 'Paper label', 15.00, 'label address shipping', 1, 'label', 'catalog/address-label.svg'),
(2, 'Circular Product Sticker', 'Round stickers for branding your products.', 'Gloss vinyl', 12.00, 'sticker round logo label', 1, 'sticker', 'catalog/product-sticker.svg'),
(3, 'Business Product Catalog', 'Printed catalogue to showcase your product range.', 'Coated paper', 150.00, 'catalog catalogue print brochure', 1, 'catalog', 'catalog/product-catalog.svg'),
(3, 'Marketing Tri-fold Brochure', 'Tri-fold brochure for promotions and events.', 'Gloss paper', 55.00, 'brochure flyer marketing', 1, 'brochure', 'catalog/trifold-brochure.svg'),
(3, 'Branded Thank-You Card', 'Thank-you cards to put inside every order.', 'Card stock', 28.00, 'card thank you insert', 1, 'card', 'catalog/thank-you-card.svg'),
(4, 'Kraft Food Box', 'Fold-top takeaway box for meals and pastries, sealed with your logo sticker.', 'Food-safe kraft board', 38.50, 'food box takeaway restaurant lunch', 1, 'foodbox', 'catalog/food-box.svg');

-- To make yourself an admin after registering:
--   UPDATE customers SET user_role = 1 WHERE email = 'you@example.com';
-- To make a delivery staff account:
--   UPDATE customers SET user_role = 3 WHERE email = 'rider@example.com';
