# Troubleshooting Guide
## Namecheap cPanel Deployment Issues

Common issues and solutions for deploying to scraper.staging-ptd.com

---

## 🔴 Database Connection Errors

### Error: "SQLSTATE[HY000] [1045] Access denied for user"

**Causes:**
- Incorrect database credentials
- User not assigned to database
- Wrong database host

**Solutions:**
1. Verify credentials in `config/config.php`:
   ```php
   'database' => 'username_scraperman_db',  // Full name with prefix
   'username' => 'username_scraperman_user', // Full username with prefix
   'password' => 'correct_password',
   ```

2. In cPanel MySQL Databases:
   - Check user exists
   - Verify user has ALL PRIVILEGES
   - Re-add user to database if needed

3. Test connection via phpMyAdmin with same credentials

4. Check database host (usually 'localhost'):
   ```php
   'host' => 'localhost',
   ```

### Error: "SQLSTATE[HY000] [2002] No such file or directory"

**Cause:** Wrong database host or socket issue

**Solution:**
Try alternative host values in `config/config.php`:
```php
'host' => '127.0.0.1',  // Instead of 'localhost'
```

Or check cPanel for correct MySQL hostname.

### Error: "SQLSTATE[42000]: Database does not exist"

**Cause:** Database name incorrect or not created

**Solutions:**
1. Verify database exists in cPanel MySQL Databases
2. Check full database name (includes cPanel username prefix)
3. Create database if missing
4. Import schema.sql

---

## 🔴 White Screen / Blank Page

### No errors shown, just white screen

**Causes:**
- PHP fatal error with display_errors off
- Syntax error in PHP files
- Missing files or autoload issue

**Solutions:**

1. **Check PHP error log:**
   - cPanel → Errors → View error log
   - OR check `/logs/php_errors.log`

2. **Enable debug temporarily:**
   ```php
   // In config/config.php
   'debug' => true,

   // In bootstrap.php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

3. **Check file upload:**
   - Ensure all files uploaded completely
   - Verify vendor/ directory exists
   - Check for missing core files

4. **Test PHP:**
   Create test.php:
   ```php
   <?php
   phpinfo();
   echo "PHP is working!";
   ```

---

## 🔴 500 Internal Server Error

### Generic 500 error

**Causes:**
- .htaccess syntax error
- PHP version incompatibility
- File permission issues
- Missing PHP extensions

**Solutions:**

1. **Check .htaccess:**
   - Temporarily rename `.htaccess` to `.htaccess.bak`
   - If site works, there's an .htaccess issue
   - Check syntax, especially RewriteBase

2. **Check PHP version:**
   - cPanel → Select PHP Version
   - Ensure PHP 8.0+ selected
   - Check error logs for version-specific errors

3. **Check server error log:**
   - cPanel → Errors
   - Look for specific error message

4. **Check file permissions:**
   - PHP files: 644
   - Directories: 755
   - Don't use 777

5. **Check PHP extensions:**
   - Visit `/utils/check.php`
   - Enable missing extensions in PHP Selector

---

## 🔴 404 Not Found Errors

### Pages not found, showing 404

**Causes:**
- mod_rewrite not enabled
- .htaccess not working
- Wrong RewriteBase

**Solutions:**

1. **Check .htaccess exists:**
   - Verify `.htaccess` in root directory
   - Check it's not named `htaccess.txt`

2. **Fix RewriteBase:**
   ```apache
   # For root domain
   RewriteBase /

   # For subdirectory
   RewriteBase /subdirectory/
   ```

3. **Test mod_rewrite:**
   Contact Namecheap support to enable mod_rewrite

4. **Direct file access:**
   Try accessing files directly:
   - `/views/welcome.php` - Should work
   - If direct access works, it's a rewrite issue

---

## 🔴 Permission Denied Errors

### Error: "Permission denied" when writing files

**Causes:**
- Incorrect directory permissions
- Safe mode restrictions (rare)
- Ownership issues

**Solutions:**

1. **Fix permissions via File Manager:**
   ```
   logs/        → 755
   temp/        → 755
   ScrapeFile/  → 755 (recursive)
   uploads/     → 755
   ```

2. **Check ownership:**
   - Files should be owned by your cPanel user
   - Contact Namecheap if ownership is wrong

3. **Create directories if missing:**
   ```bash
   mkdir -p logs temp ScrapeFile uploads
   chmod 755 logs temp ScrapeFile uploads
   ```

4. **Check PHP open_basedir:**
   - May restrict file operations
   - Contact Namecheap to adjust if needed

---

## 🔴 Login Issues

### Can't login with admin/admin123

**Causes:**
- Database not imported correctly
- Session issues
- Password hash mismatch

**Solutions:**

1. **Reset admin password:**
   Via phpMyAdmin:
   ```sql
   UPDATE users
   SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
   WHERE username = 'admin';
   ```
   This resets to: `admin123`

2. **Use fix-admin utility:**
   Visit: `/utils/fix-admin.php`

3. **Check sessions:**
   - Ensure temp/ directory writable
   - Check session configuration in config.php

4. **Clear browser cookies:**
   - Clear cookies for domain
   - Try incognito/private window

---

## 🔴 CSS/JS Not Loading

### Styles broken, no formatting

**Causes:**
- Incorrect URL paths
- MIME type issues
- File paths wrong in bootstrap.php

**Solutions:**

1. **Check bootstrap.php:**
   ```php
   define('BASE_URL', '');        // For root
   define('PUBLIC_URL', '/public');
   define('ASSETS_URL', '/public/assets');
   ```

2. **Check browser console:**
   - F12 → Console tab
   - Look for 404 errors on CSS/JS files

3. **Verify file paths:**
   - Ensure `/public/assets/css/style.css` exists
   - Check file uploaded correctly

4. **Check .htaccess:**
   - Ensure assets directory not blocked
   - Verify RewriteCond excludes /public

---

## 🔴 Composer/Vendor Issues

### Error: "vendor/autoload.php not found"

**Causes:**
- vendor/ directory not uploaded
- Composer not run
- Autoload file missing

**Solutions:**

1. **Run composer locally before upload:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. **Upload vendor/ directory:**
   - Ensure entire vendor/ folder uploaded
   - May take time due to many files

3. **Run composer on server (if SSH access):**
   ```bash
   cd public_html
   composer install --no-dev
   ```

4. **Check file upload:**
   - Verify `vendor/autoload.php` exists
   - Check `vendor/composer/` directory exists

---

## 🔴 SSL/HTTPS Issues

### SSL certificate errors or mixed content

**Causes:**
- SSL not installed
- HTTP resources in HTTPS page
- Wrong SSL configuration

**Solutions:**

1. **Install SSL:**
   - cPanel → SSL/TLS
   - Install Let's Encrypt (free)
   - Wait 5-10 minutes for activation

2. **Force HTTPS in .htaccess:**
   ```apache
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

3. **Update config.php:**
   ```php
   'app' => [
       'url' => 'https://scraper.staging-ptd.com',
   ],
   'session' => [
       'secure' => true,
   ],
   ```

4. **Check mixed content:**
   - Browser console shows warnings
   - Ensure all resources use HTTPS

---

## 🔴 Scraper Execution Issues

### Scrapers not running or timing out

**Causes:**
- PHP execution timeout
- Memory limit too low
- Missing dependencies
- Puppeteer/Selenium not available

**Solutions:**

1. **Increase PHP limits:**

   In .htaccess:
   ```apache
   php_value max_execution_time 600
   php_value memory_limit 512M
   ```

2. **Check PHP extensions:**
   - curl (required)
   - json (required)
   - xml (required)

3. **Test scraper directly:**
   - Navigate to `/Executable/`
   - Run specific scraper to see error

4. **Check logs:**
   - `/logs/app.log`
   - Look for specific scraper errors

5. **Note about Puppeteer:**
   - May not work on shared hosting
   - Requires Node.js installed
   - May need VPS for full functionality

---

## 🔴 API Connection Issues

### Can't connect to internationalpropertyalerts.com API

**Causes:**
- Firewall blocking outbound connections
- Wrong API token
- API endpoint changed

**Solutions:**

1. **Test API from server:**
   Create test-api.php:
   ```php
   <?php
   $ch = curl_init('https://internationalpropertyalerts.com/wp-json/');
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   $result = curl_exec($ch);
   echo $result;
   ```

2. **Check API token:**
   - Verify token in config.php
   - Check if token expired
   - Test with Postman/curl first

3. **Check curl SSL:**
   ```php
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Temporary test
   ```

4. **Contact Namecheap:**
   - Ask about firewall rules
   - Check if outbound connections allowed

---

## 🔴 File Upload Issues

### Can't upload files through forms

**Causes:**
- Upload directory not writable
- PHP upload limits too low
- Temp directory issues

**Solutions:**

1. **Check upload limits in .htaccess:**
   ```apache
   php_value upload_max_filesize 10M
   php_value post_max_size 10M
   ```

2. **Fix permissions:**
   ```
   uploads/ → 755
   temp/    → 755
   ```

3. **Check PHP temp directory:**
   - Verify temp/ directory exists
   - Should be writable

---

## 🔄 General Debugging Steps

When nothing works:

1. **Start fresh:**
   ```
   ☐ Delete all files
   ☐ Re-upload everything
   ☐ Re-import database
   ☐ Reconfigure files
   ```

2. **Check basics:**
   ```
   ☐ PHP version 8.0+
   ☐ All extensions enabled
   ☐ Database connected
   ☐ Permissions correct
   ☐ .htaccess working
   ```

3. **Enable all logging:**
   ```php
   // config/config.php
   'debug' => true,
   'logging' => ['level' => 'debug'],

   // bootstrap.php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

4. **Check step-by-step:**
   - Does PHP info page work? (`phpinfo()`)
   - Does database connect? (use PDO test)
   - Do static files load? (CSS/JS)
   - Do views load directly? (`/views/welcome.php`)
   - Do rewritten URLs work? (`/dashboard`)

---

## 📞 Getting Help

### Self-Help Tools
1. `/utils/check.php` - Installation verification
2. `/utils/debug.php` - System information
3. `/utils/fix-admin.php` - Reset admin access
4. `/logs/app.log` - Application logs
5. cPanel Error Log - Server errors

### Contact Support

**Namecheap Support:**
- 24/7 Live Chat (cPanel)
- Ticket System
- Phone support (check account)

**When contacting support, provide:**
- Exact error message
- What you were doing
- Server error logs
- Steps to reproduce

---

## ✅ Post-Fix Verification

After fixing any issue:

- [ ] Test login
- [ ] Test main functionality
- [ ] Check all pages load
- [ ] Verify database operations
- [ ] Test scraper (if fixed)
- [ ] Check logs for errors
- [ ] Clear browser cache
- [ ] Test in different browser

---

## 🔍 Advanced Diagnostics

### Create diagnostic script (diagnostic.php):

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>System Diagnostic</h1>";

// PHP Version
echo "<h2>PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// Extensions
echo "<h2>Extensions</h2>";
$required = ['pdo', 'pdo_mysql', 'mbstring', 'curl', 'openssl', 'json'];
foreach ($required as $ext) {
    echo $ext . ": " . (extension_loaded($ext) ? "✅" : "❌") . "<br>";
}

// Database
echo "<h2>Database Connection</h2>";
try {
    $config = require 'config/config.php';
    $db = $config['database'];
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['database']}",
        $db['username'],
        $db['password']
    );
    echo "✅ Connected successfully<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Permissions
echo "<h2>Directory Permissions</h2>";
$dirs = ['logs', 'temp', 'ScrapeFile', 'uploads'];
foreach ($dirs as $dir) {
    $writable = is_writable($dir);
    echo "$dir: " . ($writable ? "✅ Writable" : "❌ Not writable") . "<br>";
}

// .htaccess
echo "<h2>.htaccess</h2>";
echo file_exists('.htaccess') ? "✅ Exists" : "❌ Missing";

phpinfo();
```

Run this to get complete system overview.

---

**Remember:** Most deployment issues are configuration-related. Double-check all config files!
