@echo off
echo ========================================
echo Creating user_addresses table
echo ========================================
echo.

c:/xampp/mysql/bin/mysql -u root -h127.0.0.1 -P3306 britz_blythe ^< "sql\create-user-addresses-table.sql"

if %errorlevel% equ 0 (
    echo.
    echo ✅ user_addresses table created!
    echo.
    echo Test: http://localhost/ecommerce/public/address-book.php
    echo.
) else (
    echo ❌ Failed to create table
)

pause
