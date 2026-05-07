-- Fix missing columns in orders table for recipient_name SQL error
-- Safe ALTER TABLE with IF NOT EXISTS and proper defaults

USE britz_blythe;

-- Add shipping address columns (missing from original schema)
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS recipient_name VARCHAR(255) DEFAULT '' AFTER user_id,
ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL AFTER recipient_name,
ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT '' AFTER address,
ADD COLUMN IF NOT EXISTS state VARCHAR(100) DEFAULT '' AFTER city,
ADD COLUMN IF NOT EXISTS zip_code VARCHAR(20) DEFAULT '' AFTER state,
ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) DEFAULT '' AFTER zip_code;

-- Add billing address columns  
ALTER TABLE orders
ADD COLUMN IF NOT EXISTS billing_recipient_name VARCHAR(255) DEFAULT '' AFTER phone_number,
ADD COLUMN IF NOT EXISTS billing_address TEXT DEFAULT NULL AFTER billing_recipient_name,
ADD COLUMN IF NOT EXISTS billing_city VARCHAR(100) DEFAULT '' AFTER billing_address,
ADD COLUMN IF NOT EXISTS billing_state VARCHAR(100) DEFAULT '' AFTER billing_city,
ADD COLUMN IF NOT EXISTS billing_zip_code VARCHAR(20) DEFAULT '' AFTER billing_state,
ADD COLUMN IF NOT EXISTS billing_phone_number VARCHAR(20) DEFAULT '' AFTER billing_zip_code;

-- Verify columns were added
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'britz_blythe' 
  AND TABLE_NAME = 'orders'
  AND COLUMN_NAME IN (
    'recipient_name', 'address', 'city', 'state', 'zip_code', 'phone_number',
    'billing_recipient_name', 'billing_address', 'billing_city', 'billing_state', 
    'billing_zip_code', 'billing_phone_number'
  )
ORDER BY ORDINAL_POSITION;
