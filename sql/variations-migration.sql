-- Migration to add variations system, multiple images, and enhanced reviews
-- Run this after the base schema.sql

USE britz_blythe;

-- Add sold_count to products table
ALTER TABLE products ADD COLUMN sold_count INT NOT NULL DEFAULT 0;

-- Create product_images table for multiple images
DROP TABLE IF EXISTS product_images;
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_primary TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_sort_order (sort_order)
);

-- Create variations table
DROP TABLE IF EXISTS variations;
CREATE TABLE variations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name VARCHAR(100) NOT NULL, -- e.g., "Color", "Size", "Type"
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
);

-- Create variation_options table
DROP TABLE IF EXISTS variation_options;
CREATE TABLE variation_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variation_id INT NOT NULL,
    value VARCHAR(100) NOT NULL, -- e.g., "Red", "Large", "Wireless"
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (variation_id) REFERENCES variations(id) ON DELETE CASCADE,
    INDEX idx_variation_id (variation_id)
);

-- Create variation_items table for combinations
DROP TABLE IF EXISTS variation_items;
CREATE TABLE variation_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    option_combination JSON NOT NULL, -- e.g., {"Color": "Red", "Size": "Large"}
    price DECIMAL(9,2) DEFAULT NULL, -- NULL means use base price
    stock INT DEFAULT NULL, -- NULL means use base stock
    image_path VARCHAR(255) DEFAULT NULL, -- Optional image for this variation
    sku VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_option_combination (option_combination(255))
);

-- Update reviews table to link to products and users
ALTER TABLE reviews ADD COLUMN product_id INT DEFAULT NULL AFTER id;
ALTER TABLE reviews ADD COLUMN user_id INT DEFAULT NULL AFTER product_id;
ALTER TABLE reviews ADD CONSTRAINT fk_reviews_product_id FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;
ALTER TABLE reviews ADD CONSTRAINT fk_reviews_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Insert sample data for existing products
-- For artisan-leather-tote (if it exists)
INSERT INTO product_images (product_id, image_path, sort_order, is_primary)
SELECT id, 'assets/images/products/placeholder.svg', 0, 1 FROM products WHERE slug = 'artisan-leather-tote' LIMIT 1;

-- For linen-bedroom-set
INSERT INTO product_images (product_id, image_path, sort_order, is_primary)
SELECT id, 'assets/images/products/placeholder.svg', 0, 1 FROM products WHERE slug = 'linen-bedroom-set' LIMIT 1;

-- For desktop-organizer
INSERT INTO product_images (product_id, image_path, sort_order, is_primary)
SELECT id, 'assets/images/products/placeholder.svg', 0, 1 FROM products WHERE slug = 'desktop-organizer' LIMIT 1;

-- For travel-essentials-kit
INSERT INTO product_images (product_id, image_path, sort_order, is_primary)
SELECT id, 'assets/images/products/placeholder.svg', 0, 1 FROM products WHERE slug = 'travel-essentials-kit' LIMIT 1;

-- Add some sample variations for demonstration
-- Color variation for leather tote (if it exists)
INSERT INTO variations (product_id, name, sort_order)
SELECT id, 'Color', 0 FROM products WHERE slug = 'artisan-leather-tote' LIMIT 1;

INSERT INTO variation_options (variation_id, value, sort_order)
SELECT LAST_INSERT_ID(), 'Black', 0 UNION ALL
SELECT LAST_INSERT_ID(), 'Brown', 1 UNION ALL
SELECT LAST_INSERT_ID(), 'Tan', 2;

-- Size variation for linen set
INSERT INTO variations (product_id, name, sort_order)
SELECT id, 'Size', 0 FROM products WHERE slug = 'linen-bedroom-set' LIMIT 1;

INSERT INTO variation_options (variation_id, value, sort_order)
SELECT LAST_INSERT_ID(), 'Twin', 0 UNION ALL
SELECT LAST_INSERT_ID(), 'Queen', 1 UNION ALL
SELECT LAST_INSERT_ID(), 'King', 2;

-- Sample variation items
INSERT INTO variation_items (product_id, option_combination, price, stock)
SELECT p.id, '{"Color": "Black"}', 149.00, 8 FROM products p WHERE p.slug = 'artisan-leather-tote' UNION ALL
SELECT p.id, '{"Color": "Brown"}', 149.00, 5 FROM products p WHERE p.slug = 'artisan-leather-tote' UNION ALL
SELECT p.id, '{"Color": "Tan"}', 159.00, 2 FROM products p WHERE p.slug = 'artisan-leather-tote' UNION ALL
SELECT p.id, '{"Size": "Twin"}', 89.00, 10 FROM products p WHERE p.slug = 'linen-bedroom-set' UNION ALL
SELECT p.id, '{"Size": "Queen"}', 109.00, 8 FROM products p WHERE p.slug = 'linen-bedroom-set' UNION ALL
SELECT p.id, '{"Size": "King"}', 129.00, 2 FROM products p WHERE p.slug = 'linen-bedroom-set';