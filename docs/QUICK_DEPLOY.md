# Quick Deployment Guide
## Deploy to scraper.staging-ptd.com in 10 Minutes

This is a condensed version for experienced developers. For detailed instructions, see [DEPLOYMENT_NAMECHEAP.md](DEPLOYMENT_NAMECHEAP.md).

---

## ⚡ Quick Steps

### 1. Local Prep (2 minutes)
```bash
cd C:\xampp\htdocs\ScrapingToolsAutoSync
composer install --no-dev --optimize-autoloader
```

### 2. Database Setup (2 minutes)
**cPanel → MySQL Databases:**
1. Create DB: `scraperman_db`
2. Create User: `scraperman_user` + password
3. Assign ALL PRIVILEGES
4. **phpMyAdmin** → Import `database/schema.sql`

### 3. Upload Files (3 minutes)
**Choose one:**
- **File Manager:** Upload via cPanel File Manager to `public_html/`
- **FTP:** Use FileZilla to upload all files
- **Git:** `git clone <repo>` then `composer install`

**DON'T upload:**
- `.git/`
- `chromedriver.exe`
- `*.bat` files
- `testing.php`, `test.html`

### 4. Configure (2 minutes)

**Update files via File Manager:**

#### `config/config.php` (copy from config.production.php)
```php
'database' => [
    'database' => 'username_scraperman_db',  // Your actual DB name
    'username' => 'username_scraperman_user', // Your actual username
    'password' => 'YOUR_PASSWORD',
],
'app' => [
    'url' => 'https://scraper.staging-ptd.com',
    'debug' => false,
],
'session' => [
    'secure' => true,
],
```

#### `.htaccess` (copy from .htaccess.production)
Ensure `RewriteBase /` is set correctly.

#### `bootstrap.php` (copy from bootstrap.production.php)
```php
define('BASE_URL', '');  // Empty for root domain
```

### 5. Permissions (1 minute)
**cPanel File Manager → Set Permissions:**
- `logs/` → 755
- `temp/` → 755
- `ScrapeFile/` → 755 (recursive)
- `config/config.php` → 644

### 6. Enable PHP Extensions
**cPanel → Select PHP Version → Extensions:**
- ✅ PDO, pdo_mysql, mbstring, curl, openssl, json, xml

### 7. SSL Setup
**cPanel → SSL/TLS:**
- Install **Let's Encrypt** certificate

### 8. Test
Visit: `https://scraper.staging-ptd.com`

**Login:**
- Username: `admin`
- Password: `admin123`

**Run check:** `https://scraper.staging-ptd.com/utils/check.php`

### 9. Secure
- Change admin password immediately
- Verify config files blocked: `/config/config.php` (should 403)

### 10. Done! 🎉

---

## 🚨 Quick Troubleshooting

| Issue | Solution |
|-------|----------|
| 500 Error | Check PHP error logs, verify config.php syntax |
| Database Error | Check credentials, test in phpMyAdmin |
| 404 on pages | Verify .htaccess uploaded, mod_rewrite enabled |
| White screen | Enable debug temporarily, check permissions |
| Can't login | Reset password via phpMyAdmin or utils/fix-admin.php |

---

## 📁 Files to Update for Production

1. **config/config.php** ← config.production.php
2. **bootstrap.php** ← bootstrap.production.php
3. **.htaccess** ← .htaccess.production

All production template files are included!

---

## 🔗 Important URLs

- **Login:** https://scraper.staging-ptd.com/login
- **Dashboard:** https://scraper.staging-ptd.com/dashboard
- **Check:** https://scraper.staging-ptd.com/utils/check.php
- **Fix Admin:** https://scraper.staging-ptd.com/utils/fix-admin.php

---

## 📞 Need Help?

- **Detailed Guide:** [DEPLOYMENT_NAMECHEAP.md](DEPLOYMENT_NAMECHEAP.md)
- **Checklist:** [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)
- **Namecheap Support:** 24/7 Live Chat in cPanel

---

**Deployment Time:** ~10 minutes for experienced users
**Difficulty:** ⭐⭐ Easy-Medium
