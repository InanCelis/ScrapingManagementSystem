# Quick Installation Guide

## Step-by-Step Setup

### 1. Database Setup

Open phpMyAdmin (http://localhost/phpmyadmin) and run:

```sql
-- Create database
CREATE DATABASE IF NOT EXISTS scraper_management
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Use the database
USE scraper_management;
```

Then import the schema file `database/schema.sql` or copy-paste its contents.

### 2. Configure Database Connection

Edit `config/config.php` file (lines 9-14):

```php
'database' => [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'scraper_management',
    'username' => 'root',      // Change if needed
    'password' => '',          // Add your MySQL password
    'charset' => 'utf8mb4',
]
```

### 3. Create Required Directories

The application needs these directories. They will be created automatically, but you can create them manually:

```
logs/
temp/
uploads/
```

On Windows (XAMPP), you don't need to do anything special. The application will create them.

### 4. Access the Application

1. Open your browser
2. Navigate to: `http://localhost/ScrapingToolsAutoSync/login.php`
3. Login with:
   - **Username**: `admin`
   - **Password**: `admin123`

### 5. Change Default Password (IMPORTANT!)

After logging in:
1. Go to Profile (top right menu)
2. Change the default password
3. Save changes

## Verification Checklist

✅ Database `scraper_management` created
✅ All tables imported successfully
✅ `config/config.php` has correct database credentials
✅ Can access login page
✅ Can login with admin/admin123
✅ Dashboard loads without errors

## Common Issues

### Cannot connect to database

**Error Message**: "Database connection failed"

**Fix**:
1. Check if MySQL is running in XAMPP Control Panel
2. Verify database name is `scraper_management`
3. Check username/password in `config/config.php`

### Page shows PHP errors

**Error**: Parse errors or "Class not found"

**Fix**:
1. Make sure PHP version is 7.4 or higher
2. Check if all files are uploaded correctly
3. Verify `vendor/autoload.php` exists (run `composer install` if missing)

### Cannot write to directory

**Error**: "Failed to create directory" or "Permission denied"

**Fix** (Linux/Mac):
```bash
chmod -R 755 logs temp uploads ScrapeFile
```

**Fix** (Windows/XAMPP):
- Usually no action needed
- If problems persist, right-click folder → Properties → Security → Edit → Add "Full Control" for your user

### Login page doesn't load

**Fix**:
1. Make sure you're accessing the correct URL
2. Check Apache is running in XAMPP
3. Verify `.htaccess` settings allow PHP execution

## Next Steps

After successful installation:

1. **Add Your First Configuration**
   - Go to Configurations → Add Configuration
   - Fill in details for your scraper
   - Save

2. **Test a Scraper**
   - Go to Running Tools
   - Click Play button on your configuration
   - Monitor progress in real-time

3. **Explore Dashboard**
   - View statistics
   - Check recent activity
   - Monitor success rates

## Need Help?

Check the full [README.md](README.md) for:
- Detailed usage guide
- API documentation
- Troubleshooting tips
- Security recommendations
