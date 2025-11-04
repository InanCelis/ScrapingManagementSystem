@echo off
REM Installation script for API Settings feature
REM Run this to create the system_settings table

echo ========================================
echo   API Settings Installation Script
echo ========================================
echo.

echo Running database migration...
"c:\xampp\mysql\bin\mysql.exe" -u root scraper_management < "database\migrations\add_system_settings_table.sql"

if %errorlevel% equ 0 (
    echo.
    echo [SUCCESS] System settings table created successfully!
    echo.
    echo Verifying installation...
    "c:\xampp\mysql\bin\mysql.exe" -u root scraper_management -e "SELECT COUNT(*) as 'API Settings Count' FROM system_settings WHERE category = 'api';"
    echo.
    echo [INFO] You can now access API settings at:
    echo        http://localhost/ScrapingToolsAutoSync/views/settings.php
    echo.
) else (
    echo.
    echo [ERROR] Failed to create table. Please check your MySQL connection.
    echo.
)

pause
