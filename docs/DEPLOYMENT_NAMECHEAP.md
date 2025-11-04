# Namecheap cPanel Deployment Guide
## Scraping Management System

This guide will help you deploy the Scraping Management System to Namecheap cPanel hosting at `scraper.staging-ptd.com`.

---

## 📋 Pre-Deployment Checklist

Before starting, ensure you have:

- ✅ Namecheap cPanel access credentials
- ✅ Domain pointed to hosting: `scraper.staging-ptd.com`
- ✅ FTP/SSH access credentials
- ✅ MySQL database access
- ✅ PHP 8.0+ available on hosting
- ✅ Composer installed locally (for vendor dependencies)

---

## 🚀 Step-by-Step Deployment Process

### Step 1: Prepare Files for Upload

#### 1.1 Install Composer Dependencies (Local)
```bash
cd C:\xampp\htdocs\ScrapingToolsAutoSync
composer install --no-dev --optimize-autoloader
```

#### 1.2 Files/Folders to EXCLUDE from Upload
DO NOT upload these:
- `.git/` directory
- `.gitignore`
- `node_modules/` (if any)
- Local test files (testing.php, test.html)
- `chromedriver.exe` (Windows-specific)
- Documentation files (*.md) - optional, but recommended to exclude
- `create-dirs.bat` (Windows batch file)
- Any local database dumps

#### 1.3 Create Production Configuration Files
Before uploading, you need to create production versions of configuration files (see Step 3).

---

### Step 2: Upload Files to cPanel

You have three upload options:

#### Option A: File Manager (Recommended for beginners)
1. Log into Namecheap cPanel
2. Navigate to **File Manager**
3. Go to `public_html/` directory
4. Create a subdirectory (optional): `scraper/` or use root
5. Upload all files via cPanel File Manager
6. Extract if uploaded as ZIP

#### Option B: FTP Upload
1. Use FileZilla or WinSCP
2. Connect to your hosting via FTP:
   - Host: `ftp.scraper.staging-ptd.com` or your FTP hostname
   - Username: Your cPanel username
   - Password: Your cPanel password
   - Port: 21
3. Navigate to `public_html/`
4. Upload all project files

#### Option C: SSH/Git (Advanced)
```bash
cd public_html
git clone <your-repo-url> .
composer install --no-dev --optimize-autoloader
```

---

### Step 3: Configure Database

#### 3.1 Create MySQL Database in cPanel

1. In cPanel, go to **MySQL Databases**
2. Create a new database:
   - Database name: `scraperman_db` (cPanel adds prefix automatically)
   - Note the full database name (e.g., `username_scraperman_db`)

3. Create database user:
   - Username: `scraperman_user`
   - Password: Generate a strong password
   - Note the full username (e.g., `username_scraperman_user`)

4. Add user to database with **ALL PRIVILEGES**

#### 3.2 Import Database Schema

1. In cPanel, go to **phpMyAdmin**
2. Select your newly created database
3. Click **Import** tab
4. Upload `database/schema.sql`
5. Click **Go** to execute

**OR via command line (if SSH access):**
```bash
mysql -u username_scraperman_user -p username_scraperman_db < database/schema.sql
```

---

### Step 4: Update Configuration Files

#### 4.1 Update `config/config.php`

Edit the database configuration:

```php
'database' => [
    'host' => 'localhost',  // Usually localhost on Namecheap
    'port' => 3306,
    'database' => 'username_scraperman_db',  // Your actual database name
    'username' => 'username_scraperman_user', // Your actual username
    'password' => 'your_strong_password',     // Your database password
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
],

// Application Settings
'app' => [
    'name' => 'Scraping Management System',
    'url' => 'https://scraper.staging-ptd.com',  // Update to your domain
    'timezone' => 'UTC',
    'debug' => false,  // Set to false in production
],

// Session Configuration
'session' => [
    'lifetime' => 120,
    'cookie_name' => 'scraper_session',
    'secure' => true,  // Set to true for HTTPS
    'httponly' => true,
],
```

#### 4.2 Update `bootstrap.php`

```php
// Define URL paths - UPDATE THESE
define('BASE_URL', '');  // Empty if root domain, or '/subdirectory'
define('PUBLIC_URL', BASE_URL . '/public');
define('ASSETS_URL', PUBLIC_URL . '/assets');
```

#### 4.3 Update `.htaccess`

```apache
# Apache Configuration for Scraping Management System

# Enable RewriteEngine
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /  # Change if in subdirectory: /scraper/

    # Redirect to welcome page if accessing root
    RewriteRule ^$ views/welcome.php [L]

    # Exclude real files and directories from rewriting
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d

    # Exclude API, assets and other special directories
    RewriteCond %{REQUEST_URI} !^/(Api|api|Executable|ExecutableXML|Helpers|ScrapeFile|vendor|assets|public|logs|temp|includes)/

    # Clean URL routing
    RewriteRule ^([a-zA-Z0-9_-]+)$ views/$1.php [L,QSA]
</IfModule>

# Force HTTPS (recommended)
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Prevent directory listing
Options -Indexes

# Protect config files
<FilesMatch "^(config\.php|\.env)$">
    Require all denied
</FilesMatch>

# Protect log files
<Files ~ "\.log$">
    Require all denied
</Files>

# Protect markdown documentation
<FilesMatch "\.(md|MD)$">
    Require all denied
</FilesMatch>

# Protect JSON data files
<FilesMatch "^.*Properties.*\.json$">
    Require all denied
</FilesMatch>

# PHP Settings (adjust if allowed by host)
<IfModule mod_php.c>
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value max_execution_time 600
    php_value max_input_time 600
    php_value memory_limit 512M
</IfModule>
</IfModule>
```

---

### Step 5: Set File Permissions

Set proper permissions via cPanel File Manager or FTP:

#### Required Permissions:

**Directories (755):**
- `logs/` - Must be writable
- `temp/` - Must be writable
- `ScrapeFile/` - Must be writable
- `uploads/` - Must be writable (if exists)

**Config Files (644):**
- `config/config.php`
- `bootstrap.php`
- `.htaccess`

#### Via SSH:
```bash
# Make directories writable
chmod 755 logs temp ScrapeFile uploads
chmod -R 755 ScrapeFile/*

# Secure config files
chmod 644 config/config.php
chmod 644 bootstrap.php
chmod 644 .htaccess

# Secure vendor
chmod -R 755 vendor
```

---

### Step 6: Test Installation

#### 6.1 Access the Application
Open your browser and visit:
```
https://scraper.staging-ptd.com
```

You should see the welcome page.

#### 6.2 Test Login
Navigate to:
```
https://scraper.staging-ptd.com/login
```

**Default Credentials:**
- Username: `admin`
- Password: `admin123`

⚠️ **IMPORTANT:** Change the admin password immediately after first login!

#### 6.3 Run Installation Check
Visit:
```
https://scraper.staging-ptd.com/utils/check.php
```

This will verify:
- Database connection
- File permissions
- PHP extensions
- Configuration

---

### Step 7: PHP Requirements Check

Ensure your Namecheap hosting has these PHP extensions enabled:

**Required Extensions:**
- ✅ PDO
- ✅ PDO_MySQL
- ✅ mbstring
- ✅ curl
- ✅ openssl
- ✅ json
- ✅ xml

**Check via cPanel:**
1. Go to **Select PHP Version**
2. Click **Extensions**
3. Enable required extensions

**OR create a test file:**
```php
<?php
phpinfo();
```

---

### Step 8: Configure Cron Jobs (Optional)

If you want automated scraping:

#### In cPanel Cron Jobs:

**Example: Run scraper daily at 2 AM**
```bash
0 2 * * * /usr/bin/php /home/username/public_html/index.php > /dev/null 2>&1
```

**Example: Cleanup old logs weekly**
```bash
0 0 * * 0 find /home/username/public_html/logs -name "*.log" -mtime +30 -delete
```

---

### Step 9: Security Hardening

#### 9.1 Change Default Admin Password
```sql
-- Via phpMyAdmin or MySQL command line
UPDATE users
SET password = '$2y$10$NEW_HASHED_PASSWORD'
WHERE username = 'admin';
```

Or use the application's profile page to change password.

#### 9.2 Disable Directory Listing
Already done in `.htaccess` with `Options -Indexes`

#### 9.3 Protect Sensitive Files
Ensure `.htaccess` rules are working by trying to access:
- `https://scraper.staging-ptd.com/config/config.php` (should be denied)
- `https://scraper.staging-ptd.com/logs/app.log` (should be denied)

#### 9.4 Enable HTTPS/SSL
In cPanel:
1. Go to **SSL/TLS**
2. Install SSL certificate (Let's Encrypt is free)
3. Force HTTPS via `.htaccess` (already included in config)

---

### Step 10: Post-Deployment Testing

#### Test Checklist:

- [ ] Homepage loads without errors
- [ ] Login page accessible
- [ ] Can log in with admin credentials
- [ ] Dashboard displays correctly
- [ ] Can create new scraper configuration
- [ ] Can view configurations list
- [ ] Activity log accessible
- [ ] API endpoints responding (test in browser)
- [ ] File upload works
- [ ] Scraper execution works
- [ ] Logs being written to `logs/` directory

---

## 🔧 Troubleshooting

### Issue: White Screen / 500 Error

**Solutions:**
1. Check PHP error logs in cPanel
2. Enable debug mode temporarily in `config/config.php`:
   ```php
   'debug' => true,
   ```
3. Check file permissions
4. Verify `.htaccess` syntax

### Issue: Database Connection Failed

**Solutions:**
1. Verify database credentials in `config/config.php`
2. Ensure database user has proper privileges
3. Check if database exists
4. Try connecting via phpMyAdmin with same credentials

### Issue: 404 Not Found for Clean URLs

**Solutions:**
1. Check if `mod_rewrite` is enabled
2. Verify `.htaccess` file is present
3. Check `RewriteBase` in `.htaccess`
4. Contact Namecheap support to enable mod_rewrite

### Issue: Permission Denied Errors

**Solutions:**
1. Set proper permissions on directories:
   ```bash
   chmod 755 logs temp ScrapeFile
   ```
2. Check if safe_mode is enabled (deprecated)
3. Verify ownership of files

### Issue: Composer Dependencies Missing

**Solutions:**
1. Run `composer install` locally before upload
2. Ensure `vendor/` directory is uploaded
3. Check if Namecheap allows Composer (most do via SSH)

---

## 📞 Support Resources

**Namecheap Support:**
- Live Chat: Available 24/7
- Knowledge Base: https://www.namecheap.com/support/knowledgebase/
- Ticket System: Via cPanel

**Application Support:**
- Check logs in `logs/app.log`
- Use `/utils/debug.php` for system information
- Review documentation in `docs/` folder

---

## 🎉 Success!

Once deployed, your scraping system should be accessible at:
- **Main URL:** https://scraper.staging-ptd.com
- **Login:** https://scraper.staging-ptd.com/login
- **Dashboard:** https://scraper.staging-ptd.com/dashboard

**Next Steps:**
1. Change admin password
2. Create scraper configurations
3. Test scraping functionality
4. Set up cron jobs for automation
5. Monitor logs regularly

---

## 📝 Maintenance Notes

### Regular Maintenance:
- Clean old logs monthly
- Monitor disk space usage
- Update composer dependencies periodically
- Backup database weekly
- Review activity logs for suspicious activity

### Backup Recommendations:
1. **Database:** Export via phpMyAdmin weekly
2. **Files:** Download ScrapeFile directory monthly
3. **Configuration:** Keep local copy of `config/config.php`

---

**Deployment Date:** _______________
**Deployed By:** _______________
**Server Details:** Namecheap cPanel
**Domain:** scraper.staging-ptd.com
