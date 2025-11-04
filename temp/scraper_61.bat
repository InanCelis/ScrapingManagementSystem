@echo off
start /B php "C:\xampp\htdocs\ScrapingToolsAutoSync\core/../temp/scraper_61.php" > "C:\xampp\htdocs\ScrapingToolsAutoSync\core/../logs/scraper_61.log" 2>&1
for /f "tokens=2" %%a in ('wmic process where "commandline like '%%scraper_61.php%%' and name='php.exe'" get processid /format:list ^| find "ProcessId"') do echo %%a > "C:\xampp\htdocs\ScrapingToolsAutoSync\core/../temp/scraper_61.pid"
