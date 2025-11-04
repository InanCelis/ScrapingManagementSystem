# Scraper Integration Guide

## Overview

The new scraper management system uses an **Adapter Pattern** to bridge your existing scraper classes (in the `Executable/` folder) with the configuration-based management system.

---

## How It Works

### 1. **Configuration Storage**
All scraper settings are stored in the database (`scraper_configs` table):
- Scraper name
- Type (website or XML)
- File path to scraper class
- Page counts, folder names, testing mode, etc.

### 2. **ScraperAdapter**
The `ScraperAdapter` class (new file: `core/ScraperAdapter.php`) acts as a bridge:
- Loads your existing scraper class
- Applies configuration from the database using PHP Reflection
- Calls the `run()` method with appropriate parameters
- Logs all output to the database

### 3. **Background Execution**
When you start a scraper:
1. ScraperManager creates a temporary PHP script in `temp/` folder
2. The script loads ScraperAdapter with your configuration
3. Runs in the background (Windows: `start /B`, Linux: `&`)
4. All output goes to `logs/scraper_{process_id}.log`

---

## Setting Up a Scraper Configuration

### Example: HolidayHomesSpain

To run the existing `HolidayHomesSpain.php` scraper through the management system:

#### 1. Create Configuration via UI

Go to: **http://localhost/ScrapingToolsAutoSync/views/configuration-form.php**

Fill in the form:
- **Name:** `Holiday Homes Spain Scraper`
- **Type:** `Website`
- **Website URL:** `https://holiday-homes-spain.com`
- **URL Pattern:** `/property-search-results/?page_num={page}`
- **Count of Pages:** `100` (or however many pages you want)
- **Start Page:** `1`
- **End Page:** `100`
- **Folder Name:** `HolidayHomesSpain`
- **Filename:** `Properties2.json`
- **File Path:** `Executable/HolidayHomesSpain.php`
- **Enable Upload:** ✓ (checked)
- **Testing Mode:** ☐ (unchecked for production)

#### 2. Start the Scraper

Go to: **http://localhost/ScrapingToolsAutoSync/views/running-tools.php**

Find your configuration and click **Start**.

The adapter will:
- Load `Executable/HolidayHomesSpain.php`
- Set `$foldername = "HolidayHomesSpain"`
- Set `$filename = "Properties2.json"`
- Set `$enableUpload = true`
- Set `$testingMode = false`
- Call `$scraper->run(100, 0)`

---

## Configuration Mapping

### What the Adapter Maps:

| Database Field | Scraper Property | Type |
|----------------|------------------|------|
| `folder_name` | `$foldername` | string |
| `filename` | `$filename` | string |
| `enable_upload` | `$enableUpload` | bool |
| `testing_mode` | `$testingMode` | bool |

The adapter uses PHP Reflection to set these properties even if they're private.

### Method Signatures:

#### Website Scrapers
```php
public function run(int $pageCount, int $limit = 0): void
```

The adapter calls:
```php
$scraper->run($endPage, 0);
```

#### XML Scrapers
```php
public function run(string $xmlLink, int $countProps): void
```

The adapter calls:
```php
$scraper->run($xmlLink, $countProps);
```

---

## Making Your Scrapers Compatible

Your existing scrapers (like `HolidayHomesSpain.php`) are **already compatible**! The adapter handles everything.

However, if you want to make them more flexible, you can:

### Option 1: Accept Configuration in Constructor (Recommended for New Scrapers)

```php
class MyNewScraper {
    private string $foldername;
    private string $filename;
    private bool $enableUpload;
    private bool $testingMode;

    public function __construct(array $config = []) {
        $this->foldername = $config['folder_name'] ?? 'DefaultFolder';
        $this->filename = $config['filename'] ?? 'Properties.json';
        $this->enableUpload = $config['enable_upload'] ?? true;
        $this->testingMode = $config['testing_mode'] ?? false;
    }

    public function run(int $pageCount, int $limit = 0): void {
        // Your scraping logic
    }
}
```

### Option 2: Keep Existing Structure (Current Approach)

Keep your scrapers as-is. The adapter uses Reflection to set properties:

```php
class HolidayHomesSpain {
    private string $foldername = "HolidayHomesSpain"; // Can be overridden by adapter
    private string $filename = "Properties2.json";    // Can be overridden by adapter
    private bool $enableUpload = true;                // Can be overridden by adapter
    private bool $testingMode = false;                // Can be overridden by adapter

    public function run(int $pageCount, int $limit = 0): void {
        // Your existing code stays the same
    }
}
```

---

## Logging Integration

The `ScraperLogger` class logs to the database in real-time.

### In Your Scraper (Optional Enhancement):

If you want database logging in addition to echo statements:

```php
class MyScaper {
    private ScraperLogger $logger;

    public function setLogger(ScraperLogger $logger): void {
        $this->logger = $logger;
    }

    public function run(int $pageCount): void {
        echo "Starting scraper\n";
        if (isset($this->logger)) {
            $this->logger->log("Starting scraper", "info");
        }

        // Your scraping logic...

        echo "✅ Created property\n";
        if (isset($this->logger)) {
            $this->logger->log("Created property", "success");
        }
    }
}
```

The adapter will automatically provide the logger if the method exists.

---

## Directory Structure

Make sure these directories exist (they're created automatically but good to know):

```
ScrapingToolsAutoSync/
├── Executable/              ← Your scraper classes
│   ├── HolidayHomesSpain.php
│   ├── CasaEspanha.php
│   └── ...
├── ExecutableXML/           ← Your XML processor classes
├── ScrapeFile/              ← Output JSON files
│   ├── HolidayHomesSpain/
│   │   └── Properties2.json
│   └── ...
├── temp/                    ← Temporary PHP scripts (auto-created)
│   └── scraper_1.php
├── logs/                    ← Execution logs (auto-created)
│   └── scraper_1.log
└── core/
    ├── ScraperManager.php   ← Manages configs & processes
    ├── ScraperAdapter.php   ← Bridges old scrapers
    └── ScraperLogger.php    ← Database logging
```

---

## Troubleshooting

### Issue: "Scraper file not found"

**Solution:** Make sure `file_path` in configuration matches the actual file:
- Correct: `Executable/HolidayHomesSpain.php`
- Wrong: `HolidayHomesSpain.php` or `/Executable/HolidayHomesSpain.php`

### Issue: "Scraper class not found"

**Solution:** The class name must match the filename:
- File: `HolidayHomesSpain.php`
- Class: `class HolidayHomesSpain`

### Issue: Configuration not being applied

**Solution:** Check that property names match:
- Database: `folder_name`
- Scraper: `$foldername` (camelCase)

The adapter maps these automatically:
- `folder_name` → `foldername`
- `enable_upload` → `enableUpload`
- `testing_mode` → `testingMode`

### Issue: Scraper stops immediately

**Solution:** Check the log file in `logs/scraper_{id}.log` for errors.

Also check `temp/scraper_{id}.php` to see the generated script.

### Issue: Can't see real-time progress

**Solution:** Make sure your scraper uses `echo` statements. These appear in:
1. The log file (`logs/scraper_{id}.log`)
2. Real-time in the UI if you implement the live console feature

---

## Testing a Configuration

### 1. Test Mode

Set **Testing Mode** = ✓ in the configuration form.

This sets `$testingMode = true` in your scraper, which you can use to:
- Process only 1 property
- Save HTML for inspection
- Skip API uploads

### 2. Small Page Count

Start with `Count of Pages = 1` to test quickly.

### 3. Check the Generated Script

After starting the scraper, check `temp/scraper_{id}.php` to see what's being executed.

### 4. Monitor the Log

Check `logs/scraper_{id}.log` for output.

---

## Example Configuration for All Your Scrapers

### HolidayHomesSpain (Website)
```
Name: Holiday Homes Spain
Type: Website
File Path: Executable/HolidayHomesSpain.php
Folder Name: HolidayHomesSpain
Filename: Properties2.json
Count of Pages: 100
Start Page: 1
End Page: 100
```

### CasaEspanha (Website)
```
Name: Casa Espanha
Type: Website
File Path: Executable/CasaEspanha.php
Folder Name: CasaEspanha
Filename: Properties.json
Count of Pages: 50
```

### KyeroXML (XML)
```
Name: Kyero XML Feed
Type: XML
File Path: ExecutableXML/KyeroXML.php
XML Link: https://example.com/feed.xml
Count of Properties: 1000
Folder Name: KyeroXML
Filename: Properties.json
```

---

## Advanced: Monitoring & Statistics

The system automatically tracks:
- **Items Scraped** - From your API success messages
- **Items Created** - New properties
- **Items Updated** - Existing properties
- **Errors** - Failed attempts

These are stored in `scraper_processes` table and displayed in:
- Dashboard charts
- Running Tools progress bars
- Activity Log

---

## Next Steps

1. **Run the database migration** (if you haven't):
   - Visit: http://localhost/ScrapingToolsAutoSync/utils/migrate.php

2. **Create your first configuration**:
   - Visit: http://localhost/ScrapingToolsAutoSync/views/configurations.php
   - Click "Add New Configuration"

3. **Start the scraper**:
   - Visit: http://localhost/ScrapingToolsAutoSync/views/running-tools.php
   - Click "Start" on your configuration

4. **Monitor progress**:
   - Watch the real-time status
   - Check logs in `logs/` folder

---

## Support

If you encounter issues:

1. Check `logs/scraper_{id}.log` for detailed error messages
2. Check `temp/scraper_{id}.php` to see the generated execution script
3. Verify your scraper class works standalone (outside the management system)
4. Check database `scraper_logs` table for logged messages

---

**Last Updated:** 2025-10-15
**Status:** ✅ Adapter System Ready
**Compatibility:** All existing scrapers in Executable/ folder
