@echo off
start /B php "C:\xampp\htdocs\ScrapingToolsAutoSync\core/../temp/scraper_67.php" > "C:\xampp\htdocs\ScrapingToolsAutoSync\core/../logs/scraper_67.log" 2>&1
for /f "tokens=2" %%a in ('wmic process where "commandline like '%%scraper_67.php%%' and name='php.exe'" get processid /format:list ^| find "ProcessId"') do echo %%a > "C:\xampp\htdocs\ScrapingToolsAutoSync\core/../temp/scraper_67.pid"
