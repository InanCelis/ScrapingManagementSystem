@echo off
cd /d "%~dp0"

echo Creating required directories...

if not exist "temp" mkdir temp && echo Created temp directory
if not exist "logs" mkdir logs && echo Created logs directory
if not exist "ScrapeFile" mkdir ScrapeFile && echo Created ScrapeFile directory
if not exist "database\migrations" mkdir "database\migrations" && echo Created database\migrations directory

echo.
echo All directories created successfully!
echo.
pause
