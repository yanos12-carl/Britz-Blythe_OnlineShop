-- Enhance E-Commerce Schema for Variations, Multi-Images, Ratings
USE britz_blythe;

-- Add missing columns to products (in correct order)
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS sold_count INT DEFAULT 0 AFTER stock;

ALTER TABLE products 
ADD COLUMN IF NOT EXISTS rating_avg DECIMAL(3,2) DEFAULT 0.00 AFTER sold_count;

-- Product Images (multi-images support)
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Variation Types (Color, Size, etc.)
CREATE TABLE IF NOT EXISTS variation_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name VARCHAR(50) NOT NULL, -- 'Color', 'Size'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Variation Options (Red, Blue for Color)
CREATE TABLE IF NOT EXISTS variation_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT NOT NULL,
    value VARCHAR(50) NOT NULL,
    image_path VARCHAR(255) NULL,
    FOREIGN KEY (type_id) REFERENCES variation_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_type_value (type_id, value)
);

-- Variation Combinations (Red-Small combo w/ unique price/stock/image)
CREATE TABLE IF NOT EXISTS variation_combos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    options JSON NOT NULL, -- [{"type_id":1,"option_id":5},{"type_id":2,"option_id":8}]
    price DECIMAL(9,2) NOT NULL,
    stock INT DEFAULT 0,
    image_path VARCHAR(255) NULL,
    sku VARCHAR(50) NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_combo (product_id, options)
);

-- Update reviews to link to products (for product-specific reviews)
ALTER TABLE reviews ADD COLUMN IF NOT EXISTS product_id INT NULL AFTER user_name;
ALTER TABLE reviews ADD FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL;

-- Trigger: Update product rating_avg on review insert/update
DELIMITER //
CREATE TRIGGER update_product_rating_avg AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    UPDATE products p
    SET rating_avg = (
        SELECT AVG(r.rating)
        FROM reviews r 
        WHERE r.product_id = p.id AND r.is_approved = 1
    )
    WHERE p.id = NEW.product_id;
END//

CREATE TRIGGER update_product_rating_avg_update AFTER UPDATE ON reviews
FOR EACH ROW
BEGIN
    IF OLD.is_approved != NEW.is_approved OR OLD.rating != NEW.rating THEN
        UPDATE products p
        SET rating_avg = (
            SELECT AVG(r.rating)
            FROM reviews r 
            WHERE r.product_id = p.id AND r.is_approved = 1
        )
        WHERE p.id = NEW.product_id;
    END IF;
END//
DELIMITER ;

-- Trigger: Update sold_count on order_item insert
DELIMITER //
CREATE TRIGGER update_sold_count AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE products SET sold_count = sold_count + NEW.quantity WHERE id = NEW.product_id;
END//
DELIMITER ;

-- Sample Data for Testing
INSERT INTO products (slug, name, category, price, stock, image, excerpt, description, sold_count) VALUES
('test-shirt', 'Test Variation Shirt', 'living', 29.99, 100, 'assets/images/products/placeholder.svg', 'Test product with variations', 'Full variations test product', 25);

-- Sample Images
INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES
(LAST_INSERT_ID(), 'assets/images/products/prod_69e99cdebdacf9.61823759.jpeg', 1, 1),
(LAST_INSERT_ID(), 'assets/images/products/prod_69e99cf4ecfbd9.45554262.jpg', 0, 2);

-- Sample Variations
INSERT INTO variation_types (product_id, name) VALUES (LAST_INSERT_ID(), 'Color');
INSERT INTO variation_types (product_id, name) VALUES (LAST_INSERT_ID(), 'Size');
SET @color_type = LAST_INSERT_ID()-1, @size_type = LAST_INSERT_ID();

INSERT INTO variation_options (type_id, value) VALUES (@color_type, 'Red'), (@color_type, 'Blue'), (@size_type, 'S'), (@size_type, 'M'), (@size_type, 'L');

-- Sample Combos
INSERT INTO variation_combos (product_id, options, price, stock, image_path) VALUES
(LAST_INSERT_ID(), '[[{\"type_id\":1,\"option_id\":1},{\"type_id\":2,\"option_id\":3}]]', 29.99, 10, 'assets/images/products/prod_69e99cdebdacf9.61823759.jpeg'),
(LAST_INSERT_ID(), '[[{\"type_id\":1,\"option_id\":2},{\"type_id\":2,\"option_id\":4}]]', 34.99, 5, 'assets/images/products/prod_69e99cf4ecfbd9.45554262.jpg');

-- Indexes for performance
CREATE INDEX idx_product_images_product ON product_images(product_id);
CREATE INDEX idx_variation_product ON variation_types(product_id);
CREATE INDEX idx_combo_product ON variation_combos(product_id);
CREATE INDEX idx_reviews_product ON reviews(product_id);

SELECT 'Schema enhanced successfully!' as status;

