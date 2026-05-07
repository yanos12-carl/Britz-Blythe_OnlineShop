-- Simple Migration (core columns + tables only)
USE britz_blythe;

-- Add columns to products
ALTER TABLE products ADD COLUMN IF NOT EXISTS sold_count INT DEFAULT 0 AFTER stock;
ALTER TABLE products ADD COLUMN IF NOT EXISTS rating_avg DECIMAL(3,2) DEFAULT 0.00 AFTER sold_count;

-- Core tables (InnoDB, no FK, no samples)
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
    INDEX(product_id)
) ENGINE=InnoDB;

-- Indexes
CREATE INDEX IF NOT EXISTS idx_reviews_product ON reviews(product_id);

SELECT 'SUCCESS: Core schema ready!' as status, 'Next: mysql -u root -p -e \"DESCRIBE products; SHOW TABLES LIKE \"%vari%\"; SHOW TABLES LIKE \"%image%\";" britz_blythe' as verify;

