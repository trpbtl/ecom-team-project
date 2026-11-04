-- Packe custom packaging platform: MySQL schema (skeleton)
-- Run in phpMyAdmin or: mysql -u root < database/schema.sql

CREATE DATABASE IF NOT EXISTS packe_db;
USE packe_db;

-- Owner: Hagen Dickson
CREATE TABLE customers (
    customer_id   INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    phone         VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    business_name VARCHAR(150),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Owner: Hagen Dickson
CREATE TABLE admins (
    admin_id      INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin', 'delivery') DEFAULT 'admin'
);

-- Owner: Hagen Dickson
CREATE TABLE products (
    product_id  INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    description TEXT,
    category    VARCHAR(50),
    material    VARCHAR(100),
    image       VARCHAR(255),
    base_price  DECIMAL(10,2) NOT NULL
);

-- Owner: Josephine Allan
CREATE TABLE customizations (
    customization_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id      INT NOT NULL,
    product_id       INT NOT NULL,
    length_cm        DECIMAL(6,2),
    width_cm         DECIMAL(6,2),
    height_cm        DECIMAL(6,2),
    colour           VARCHAR(50),
    material         VARCHAR(100),
    quantity         INT NOT NULL DEFAULT 1,
    design_file      VARCHAR(255),
    unit_price       DECIMAL(10,2),
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (product_id)  REFERENCES products(product_id)
);

-- Owner: Donna Omaboe
CREATE TABLE cart (
    cart_id          INT AUTO_INCREMENT PRIMARY KEY,
    customer_id      INT NOT NULL,
    customization_id INT NOT NULL,
    quantity         INT NOT NULL DEFAULT 1,
    FOREIGN KEY (customer_id)      REFERENCES customers(customer_id),
    FOREIGN KEY (customization_id) REFERENCES customizations(customization_id)
);

-- Owner: Eyram Awoye
CREATE TABLE orders (
    order_id     INT AUTO_INCREMENT PRIMARY KEY,
    customer_id  INT NOT NULL,
    order_date   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    status       ENUM('pending','processing','ready','delivered','cancelled') DEFAULT 'pending',
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
);

-- An order can contain multiple customized products
CREATE TABLE order_items (
    order_item_id    INT AUTO_INCREMENT PRIMARY KEY,
    order_id         INT NOT NULL,
    customization_id INT NOT NULL,
    quantity         INT NOT NULL,
    price            DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)         REFERENCES orders(order_id),
    FOREIGN KEY (customization_id) REFERENCES customizations(customization_id)
);

-- Owner: Donna Omaboe
CREATE TABLE payments (
    payment_id     INT AUTO_INCREMENT PRIMARY KEY,
    order_id       INT NOT NULL,
    payment_method VARCHAR(50),
    amount         DECIMAL(10,2) NOT NULL,
    reference      VARCHAR(100),
    status         ENUM('pending','successful','failed') DEFAULT 'pending',
    paid_at        TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

-- Owner: Eyram Awoye
CREATE TABLE deliveries (
    delivery_id     INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT NOT NULL,
    recipient_name  VARCHAR(100) NOT NULL,
    contact_number  VARCHAR(20) NOT NULL,
    destination     VARCHAR(255) NOT NULL,
    delivery_option ENUM('standard','express') DEFAULT 'standard',
    delivery_fee    DECIMAL(10,2),
    assigned_to     INT NULL,
    status          ENUM('pending','assigned','in_transit','delivered') DEFAULT 'pending',
    FOREIGN KEY (order_id)    REFERENCES orders(order_id),
    FOREIGN KEY (assigned_to) REFERENCES admins(admin_id)
);
