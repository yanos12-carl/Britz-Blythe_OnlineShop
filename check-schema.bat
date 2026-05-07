@echo off
echo Checking address columns status...
cd /d "c:\xampp\htdocs\ecommerce"
"C:\xampp\php\php.exe" sql\check-address-columns.php
pause

