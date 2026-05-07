@echo off
echo ========================================
echo Fixing Orders Table - Adding Missing Columns
echo ========================================
echo.

REM Check if XAMPP MySQL is running
netstat -an | findstr :3306 >nul
if errorlevel 1 (
    echo ERROR: MySQL not running on port 3306. Start XAMPP Apache+MySQL first.
    echo.
    pause
    exit /b 1
)

REM Run the SQL fix
echo Running SQL fix...
mysql -u root -h 127.0.0.1 -P 3306 britz_blythe < "sql\fix-orders-columns.sql"

if %errorlevel% equ 0 (
    echo.
    echo SUCCESS: Orders table columns added/verified!
    echo.
    echo You can now test:
    echo   - Visit http://localhost/ecommerce/public/profile.php  
    echo   - Visit http://localhost/ecommerce/public/orders.php
    echo.
) else (
    echo.
    echo ERROR: Fix failed. Check XAMPP MySQL is running.
    echo.
)

echo.
pause
