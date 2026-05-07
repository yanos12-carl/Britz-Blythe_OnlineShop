-- Final Minimal Migration (NO reviews reference)
USE britz_blythe;

-- Add columns ONLY
ALTER TABLE products ADD COLUMN IF NOT EXISTS sold_count INT DEFAULT 0 AFTER stock;
ALTER TABLE products ADD COLUMN IF NOT EXISTS rating_avg DECIMAL(3,2) DEFAULT 0.00 AFTER sold_count;

-- Tables ONLY (no indexes/triggers/samples)
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS variation_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS variation_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT NOT NULL,
    value VARCHAR(50) NOT NULL,
    UNIQUE KEY unique_type_value (type_id, value)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS variation_combos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    options JSON NOT NULL,
    price DECIMAL(9,2) NOT NULL,
    stock INT DEFAULT 0,
    image_path VARCHAR(255) NULL,
    UNIQUE KEY unique_combo (product_id, options)
) ENGINE=InnoDB;

SELECT 'DB READY! Columns added, tables created.' as status;

