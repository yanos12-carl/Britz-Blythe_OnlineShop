-- Migration WITHOUT FK constraints (to avoid InnoDB issues)
USE britz_blythe;

-- Columns
ALTER TABLE products ADD COLUMN IF NOT EXISTS sold_count INT DEFAULT 0 AFTER stock;
ALTER TABLE products ADD COLUMN IF NOT EXISTS rating_avg DECIMAL(3,2) DEFAULT 0.00 AFTER sold_count;

-- Tables WITHOUT FK
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    INDEX(product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS variation_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    INDEX(product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS variation_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT NOT NULL,
    value VARCHAR(50) NOT NULL,
    image_path VARCHAR(255) NULL,
    INDEX(type_id),
    UNIQUE KEY unique_type_value (type_id, value)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS variation_combos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    options JSON NOT NULL,
    price DECIMAL(9,2) NOT NULL,
    stock INT DEFAULT 0,
    image_path VARCHAR(255) NULL,
    sku VARCHAR(50) NULL,
    INDEX(product_id),
    UNIQUE KEY unique_combo (product_id, options)
) ENGINE=InnoDB;

-- Reviews handled in functions.php (skip if table missing)

-- Sample data (safe)
-- Sample test-shirt (skip if slug column issue)
-- INSERT IGNORE INTO products (slug, name, category, price, stock, image, excerpt, description, sold_count) VALUES
-- ('test-shirt', 'Test Variation Shirt', 'living', 29.99, 100, 'assets/images/products/placeholder.svg', 'Test product with variations', 'Full variations test product', 25);

-- Sample images (skip)
-- INSERT IGNORE INTO product_images (product_id, image_path, is_primary, sort_order) VALUES
-- ((SELECT id FROM products WHERE slug='test-shirt'), 'assets/images/products/prod_69e99cdebdacf9.61823759.jpeg', 1, 1),
-- ((SELECT id FROM products WHERE slug='test-shirt'), 'assets/images/products/prod_69e99cf4ecfbd9.45554262.jpg', 0, 2);

INSERT IGNORE INTO product_images (product_id, image_path, is_primary, sort_order) VALUES
((SELECT id FROM products WHERE slug='test-shirt'), 'assets/images/products/prod_69e99cdebdacf9.61823759.jpeg', 1, 1),
((SELECT id FROM products WHERE slug='test-shirt'), 'assets/images/products/prod_69e99cf4ecfbd9.45554262.jpg', 0, 2);

SELECT 'Migration complete! Tables created. Run DESCRIBE products; SHOW TABLES LIKE \"vari%\";' as next_step;

