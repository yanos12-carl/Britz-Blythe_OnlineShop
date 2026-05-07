-- Fix missing profile_image column
-- Run: mysql -u root -p britz_blythe < sql/fix-profile-image.sql

USE britz_blythe;
ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER registered_at;

