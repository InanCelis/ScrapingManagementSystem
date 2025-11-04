# Web Scraping Management System

A modern, professional web-based UI for managing automated web scraping tools with real-time monitoring capabilities.

## Features

- **Modern Dashboard**: Overview of scraping activities with charts and statistics
- **Real-time Monitoring**: Live console output and progress tracking for running scrapers
- **Configuration Management**: Easy CRUD operations for scraping configurations
- **Background Processing**: Run multiple scrapers simultaneously in the background
- **Authentication System**: Secure login with remember me functionality
- **Responsive Design**: Works on desktop, tablet, and mobile devices

## Technology Stack

- **Backend**: PHP 7.4+ with OOP architecture
- **Database**: MySQL/MariaDB
- **Frontend**: Bootstrap 5, Chart.js, jQuery
- **Icons**: Font Awesome 6

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Apache/Nginx web server
- Composer (for dependencies)

### Step 1: Install Dependencies

```bash
cd c:\xampp\htdocs\ScrapingToolsAutoSync
composer install
```

### Step 2: Create Database

1. Open phpMyAdmin or your MySQL client
2. Create a new database named `scraper_management`
3. Import the schema:

```bash
mysql -u root -p scraper_management < database/schema.sql
```

Or via phpMyAdmin:
- Select the `scraper_management` database
- Go to "Import" tab
- Choose `database/schema.sql` file
- Click "Go"

### Step 3: Configure Database Connection

Edit `config/config.php` and update the database credentials:

```php
'database' => [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'scraper_management',
    'username' => 'root',      // Update with your username
    'password' => '',          // Update with your password
    'charset' => 'utf8mb4',
]
```

### Step 4: Set Permissions

Create required directories and set permissions:

```bash
mkdir -p logs temp uploads
chmod -R 755 logs temp uploads
```

On Windows (XAMPP), these directories will be created automatically.

### Step 5: Access the Application

1. Open your browser and navigate to:
   ```
   http://localhost/ScrapingToolsAutoSync/login.php
   ```

2. Login with default credentials:
   - **Username**: `admin`
   - **Password**: `admin123`

3. **Important**: Change the default password after first login!

## Directory Structure

```
ScrapingToolsAutoSync/
├── api/                    # AJAX API endpoints
│   ├── config.php
│   └── scraper.php
├── assets/                 # Static assets
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── config/                 # Configuration files
│   └── config.php
├── core/                   # Core PHP classes
│   ├── Auth.php
│   ├── Database.php
│   ├── ScraperLogger.php
│   └── ScraperManager.php
├── database/               # Database schemas
│   └── schema.sql
├── Executable/             # Website scraper classes
├── ExecutableXML/          # XML processor classes
├── Helpers/                # Helper classes
├── includes/               # Reusable UI components
│   ├── header.php
│   ├── sidebar.php
│   ├── topbar.php
│   ├── footer.php
│   └── progress-modal.php
├── logs/                   # Application logs
├── ScrapeFile/             # Scraped data storage
├── temp/                   # Temporary files
├── vendor/                 # Composer dependencies
├── configuration-form.php  # Add/Edit configuration
├── configurations.php      # List configurations
├── dashboard.php           # Main dashboard
├── login.php              # Login page
├── logout.php             # Logout handler
├── running-tools.php      # Running tools monitor
└── README.md              # This file
```

## Usage Guide

### Adding a New Scraper Configuration

#### For Website Scrapers:

1. Go to **Configurations** → **Add Configuration**
2. Fill in the form:
   - **Name of Tool**: e.g., "Holiday Homes Spain"
   - **Type**: Select "Website"
   - **Website URL**: `https://holiday-homes-spain.com`
   - **URL Pattern**: `/property-search-results/?page_num={$page}`
   - **Count of Pages**: Total pages to scrape
   - **Start Page**: Starting page number
   - **End Page**: Ending page number
   - **Folder Name**: e.g., "HolidayHomesSpain"
   - **Filename**: e.g., "Properties.json"
   - **File Path**: `Executable/HolidayHomesSpain.php`
   - **Enable Upload**: Yes/No
   - **Testing Mode**: Yes/No
3. Click "Create Configuration"

#### For XML Processors:

1. Go to **Configurations** → **Add Configuration**
2. Fill in the form:
   - **Name of Tool**: e.g., "Nilsott XML Feed"
   - **Type**: Select "XML"
   - **XML Link**: `https://web3930:9a42ded9cb@www.nilsott.com/xml/kyero.xml`
   - **Count of Properties**: Number to process (0 = all)
   - **Folder Name**: e.g., "KyeroXML"
   - **Filename**: e.g., "properties.json"
   - **File Path**: `ExecutableXML/KyeroXML.php`
   - **Enable Upload**: Yes/No
   - **Testing Mode**: Yes/No
3. Click "Create Configuration"

### Running a Scraper

1. Go to **Running Tools**
2. Find your configuration in the list
3. Click the **Play** button (▶) to start
4. Click the **Eye** icon (👁) to view live progress
5. Click the **Stop** button (⏹) to stop a running scraper

### Monitoring Progress

When you click the eye icon on a running scraper, you'll see:
- Real-time progress bar
- Total items vs items scraped
- Created/Updated counts
- Live console output with color-coded log levels
- Auto-updating statistics

## API Endpoints

### Scraper API (`/api/scraper.php`)

#### Start Scraper
```javascript
POST /api/scraper.php
{
    "action": "start",
    "config_id": 1
}
```

#### Stop Scraper
```javascript
POST /api/scraper.php
{
    "action": "stop",
    "process_id": 1
}
```

#### Get Process Details
```javascript
GET /api/scraper.php?action=get_process&process_id=1
```

#### Get Process Logs
```javascript
GET /api/scraper.php?action=get_logs&process_id=1&limit=100
```

#### Get Running Processes
```javascript
GET /api/scraper.php?action=get_running
```

### Configuration API (`/api/config.php`)

#### Get Configuration
```javascript
GET /api/config.php?action=get&id=1
```

#### List Configurations
```javascript
GET /api/config.php?action=list&type=website&search=holiday
```

#### Duplicate Configuration
```javascript
POST /api/config.php
{
    "action": "duplicate",
    "id": 1
}
```

#### Delete Configuration
```javascript
POST /api/config.php
{
    "action": "delete",
    "id": 1
}
```

## Database Schema

### Main Tables

- **users**: User authentication and profiles
- **scraper_configs**: Scraping tool configurations
- **scraper_processes**: Running/completed scraping sessions
- **scraper_logs**: Real-time console logs
- **activity_logs**: System activity tracking
- **user_sessions**: Remember me token storage

## Troubleshooting

### Database Connection Error

**Error**: "Database connection failed"

**Solution**:
- Check `config/config.php` database credentials
- Ensure MySQL service is running
- Verify database exists: `scraper_management`

### Permission Denied Errors

**Error**: "Failed to create directory" or "Cannot write to file"

**Solution**:
```bash
chmod -R 755 logs temp uploads ScrapeFile
```

### Scraper Won't Start

**Error**: "Failed to start scraper"

**Solution**:
1. Check that the file path exists: `Executable/YourScraper.php`
2. Verify the class name matches the filename
3. Check PHP error logs in `logs/` directory
4. Ensure `temp/` directory is writable

### Background Process Not Running

**Issue**: Process starts but immediately stops

**Solution**:
1. Check `logs/scraper_[process_id].log` for errors
2. Verify the scraper class file exists
3. Test the scraper manually first via `index.php`
4. Check PHP memory limit and max execution time

## Security Recommendations

### Production Deployment

1. **Change Default Password**:
   ```sql
   UPDATE users SET password = '$2y$10$...' WHERE username = 'admin';
   ```

2. **Update Config**:
   - Set `debug` to `false` in `config/config.php`
   - Use strong database password
   - Enable HTTPS
   - Set `secure` to `true` for session cookies

3. **File Permissions**:
   ```bash
   chmod 600 config/config.php
   chmod 755 logs temp uploads
   ```

4. **Database Security**:
   - Create a dedicated database user with limited privileges
   - Use strong password
   - Restrict access to localhost only

## Contributing

This is a custom internal tool. For modifications:
1. Create a backup before making changes
2. Test in development environment first
3. Document any new features or changes

## Support

For issues or questions:
1. Check logs in `logs/app.log`
2. Enable debug mode in `config/config.php`
3. Check browser console for JavaScript errors
4. Review database for data integrity

## License

Proprietary - Internal Use Only

## Changelog

### Version 1.0.0 (2025-10-15)
- Initial release
- User authentication system
- Dashboard with statistics
- Configuration management (CRUD)
- Real-time scraper monitoring
- Background process execution
- Live console output
- Activity logging
