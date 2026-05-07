-- Database schema for Britz Blythe

CREATE DATABASE IF NOT EXISTS britz_blythe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE britz_blythe;

DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    profile_image VARCHAR(255) DEFAULT NULL,
    registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL
);

DROP TABLE IF EXISTS products;
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(9,2) NOT NULL,
    stock INT NOT NULL DEFAULT 10,
    image VARCHAR(255) NOT NULL,
    excerpt TEXT NOT NULL,
    description TEXT NOT NULL,
    deleted_at DATETIME DEFAULT NULL
);

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    total DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(9,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

INSERT INTO categories (slug, name) VALUES
('living', 'Living'),
('office', 'Office'),
('travel', 'Travel'),
('gifts', 'Gifts');

-- Insert default admin user
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@britzblythe.local', '$2y$10$/TFXVam1xAJN20MwIhTlD.2JlL.HHd501buuxVOKedrzOlEFOG50C', 'admin');

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO products (slug, name, category, price, stock, image, excerpt, description) VALUES
('artisan-leather-tote', 'Artisan Leather Tote', 'living', 149.00, 15, 'assets/images/products/placeholder.svg', 'Handcrafted carry-all tote with premium vegetable-tanned leather.', 'A structured tote designed for everyday elegance, featuring soft leather, brass hardware, and a spacious interior.'),
('linen-bedroom-set', 'Linen Bedroom Set', 'living', 89.00, 20, 'assets/images/products/placeholder.svg', 'Breathable linen bedding for hotel-inspired comfort.', 'Soft, breathable linen sheets and pillowcases with a relaxed, lived-in finish for every season.'),
('desktop-organizer', 'Desktop Organizer', 'office', 29.00, 50, 'assets/images/products/placeholder.svg', 'Elegant organizer tray for cables, notebooks, and daily essentials.', 'Designed to keep your desk tidy with dedicated compartments for stationery and accessories.'),
('travel-essentials-kit', 'Travel Essentials Kit', 'travel', 49.00, 12, 'assets/images/products/placeholder.svg', 'Compact kit built for modern journeys and weekend escapes.', 'A stylish pouch filled with travel-ready essentials for the road, cabin, or train.');
