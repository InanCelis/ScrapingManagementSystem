# Deployment Checklist for scraper.staging-ptd.com

Use this checklist to ensure a successful deployment to Namecheap cPanel.

---

## 📋 Pre-Deployment

### Local Preparation
- [ ] Run `composer install --no-dev --optimize-autoloader`
- [ ] Test application locally
- [ ] Ensure all scrapers work correctly
- [ ] Review and commit all changes to git
- [ ] Create backup of current local database
- [ ] Document any custom configurations

### File Preparation
- [ ] Remove development files (testing.php, test.html)
- [ ] Remove Windows-specific files (chromedriver.exe, *.bat)
- [ ] Keep vendor/ directory with dependencies
- [ ] Prepare production config files

---

## 🗄️ Database Setup

- [ ] Login to Namecheap cPanel
- [ ] Navigate to MySQL Databases
- [ ] Create new database: `scraperman_db`
- [ ] Note full database name (e.g., `username_scraperman_db`)
- [ ] Create database user: `scraperman_user`
- [ ] Generate strong password and save it securely
- [ ] Note full username (e.g., `username_scraperman_user`)
- [ ] Assign user to database with ALL PRIVILEGES
- [ ] Access phpMyAdmin
- [ ] Import `database/schema.sql`
- [ ] Verify all tables created successfully
- [ ] Test database connection

---

## 📤 File Upload

### Choose Upload Method
- [ ] Option A: cPanel File Manager (easiest)
- [ ] Option B: FTP (FileZilla/WinSCP)
- [ ] Option C: Git/SSH (advanced)

### Upload Files
- [ ] Navigate to `public_html/` directory
- [ ] Upload all project files
- [ ] Verify vendor/ directory uploaded
- [ ] Verify all folders exist:
  - [ ] Executable/
  - [ ] ExecutableXML/
  - [ ] Helpers/
  - [ ] ScrapeFile/
  - [ ] config/
  - [ ] core/
  - [ ] views/
  - [ ] api/
  - [ ] logs/
  - [ ] temp/
  - [ ] vendor/
  - [ ] public/

---

## ⚙️ Configuration

### Update config/config.php
- [ ] Copy `config/config.production.php` to `config/config.php`
- [ ] Update database name
- [ ] Update database username
- [ ] Update database password
- [ ] Update app URL to: `https://scraper.staging-ptd.com`
- [ ] Set `debug` to `false`
- [ ] Set `secure` to `true` in session config
- [ ] Update API token if needed
- [ ] Save and upload

### Update bootstrap.php
- [ ] Copy `bootstrap.production.php` to `bootstrap.php`
- [ ] Set `BASE_URL` to empty string `''` (for root domain)
- [ ] OR set to subdirectory path if not using root
- [ ] Verify error logging configured
- [ ] Save and upload

### Update .htaccess
- [ ] Copy `.htaccess.production` to `.htaccess`
- [ ] Verify `RewriteBase` is set to `/`
- [ ] OR update to subdirectory if applicable
- [ ] Ensure HTTPS redirect enabled
- [ ] Verify security rules in place
- [ ] Save and upload

---

## 🔐 File Permissions

### Set Directory Permissions (755)
- [ ] logs/
- [ ] temp/
- [ ] ScrapeFile/ and all subdirectories
- [ ] uploads/ (if exists)

### Set File Permissions (644)
- [ ] config/config.php
- [ ] bootstrap.php
- [ ] .htaccess

### Via cPanel File Manager
- [ ] Select directory/file
- [ ] Click "Permissions"
- [ ] Set appropriate permissions
- [ ] Apply recursively for directories

---

## 🔍 PHP Configuration

### Check PHP Version
- [ ] Go to cPanel > Select PHP Version
- [ ] Ensure PHP 8.0 or higher selected
- [ ] Save changes

### Enable PHP Extensions
- [ ] Click "Extensions" in PHP Selector
- [ ] Enable required extensions:
  - [ ] PDO
  - [ ] pdo_mysql
  - [ ] mbstring
  - [ ] curl
  - [ ] openssl
  - [ ] json
  - [ ] xml
  - [ ] gd (optional, for image processing)

### PHP Settings (if allowed)
- [ ] max_execution_time: 600
- [ ] memory_limit: 512M
- [ ] upload_max_filesize: 10M
- [ ] post_max_size: 10M

---

## 🌐 Domain & SSL

### Domain Configuration
- [ ] Verify domain points to hosting
- [ ] Check DNS propagation (use whatsmydns.net)
- [ ] Wait for propagation if needed (up to 48 hours)

### SSL Certificate
- [ ] Go to cPanel > SSL/TLS
- [ ] Install Let's Encrypt SSL certificate
- [ ] OR upload custom SSL certificate
- [ ] Verify SSL is active
- [ ] Test HTTPS access

---

## ✅ Testing

### Basic Access Tests
- [ ] Visit: https://scraper.staging-ptd.com
- [ ] Verify homepage loads
- [ ] Check for SSL padlock in browser
- [ ] Test: https://scraper.staging-ptd.com/login
- [ ] Verify login page displays correctly
- [ ] No 404 errors

### Installation Check
- [ ] Visit: https://scraper.staging-ptd.com/utils/check.php
- [ ] All checks should pass:
  - [ ] PHP version
  - [ ] PHP extensions
  - [ ] Database connection
  - [ ] File permissions
  - [ ] Configuration loaded

### Login Test
- [ ] Login with default credentials:
  - Username: `admin`
  - Password: `admin123`
- [ ] Login successful
- [ ] Dashboard loads correctly

### Functionality Tests
- [ ] Navigate to Configurations
- [ ] Create test configuration
- [ ] View configurations list
- [ ] Check Activity Log
- [ ] Test API endpoint: `/api/scraper.php`
- [ ] Upload test file (if applicable)
- [ ] Run a test scraper (if safe to do)

### Security Tests
- [ ] Try accessing: `/config/config.php` (should be denied)
- [ ] Try accessing: `/logs/app.log` (should be denied)
- [ ] Try accessing: `/.git/` (should be denied)
- [ ] Try accessing: `/database/schema.sql` (should be denied)
- [ ] Directory listing disabled (try `/vendor/`)

---

## 🔒 Security Hardening

### Change Admin Password
- [ ] Login to dashboard
- [ ] Go to Profile/Settings
- [ ] Change admin password to strong password
- [ ] Save new password securely
- [ ] Logout and login with new password

### Secure Config Files
- [ ] Verify config.php has 644 permissions
- [ ] Ensure .htaccess protecting config files
- [ ] Remove any .env files if not used

### Review Logs
- [ ] Check logs/ directory exists and is writable
- [ ] Review app.log for errors
- [ ] Check PHP error log

### Backup
- [ ] Create database backup via phpMyAdmin
- [ ] Download ScrapeFile/ directory
- [ ] Save config/config.php locally (encrypted)
- [ ] Document any custom settings

---

## 🤖 Automation (Optional)

### Cron Jobs
- [ ] Go to cPanel > Cron Jobs
- [ ] Set up automated scrapers (if needed)
- [ ] Example: Daily scraping at 2 AM
  ```
  0 2 * * * /usr/bin/php /home/username/public_html/scraper-runner.php
  ```
- [ ] Set up log cleanup (weekly)
  ```
  0 0 * * 0 find /home/username/public_html/logs -name "*.log" -mtime +30 -delete
  ```
- [ ] Test cron job execution

---

## 📊 Monitoring

### Setup Monitoring
- [ ] Configure email notifications (if implemented)
- [ ] Set up uptime monitoring (e.g., UptimeRobot)
- [ ] Configure error alerting
- [ ] Set up log rotation

### Regular Checks
- [ ] Review logs weekly
- [ ] Check disk space monthly
- [ ] Monitor database size
- [ ] Review activity logs for suspicious activity

---

## 📝 Documentation

### Document Details
- [ ] Record database credentials (store securely)
- [ ] Save cPanel login info
- [ ] Document FTP/SSH credentials
- [ ] Note SSL certificate expiry date
- [ ] Record deployment date
- [ ] List any custom configurations
- [ ] Create admin user guide

### Knowledge Transfer
- [ ] Document scraper configurations
- [ ] Create usage guide for team
- [ ] List maintenance procedures
- [ ] Document backup procedures

---

## 🎉 Post-Deployment

### Immediate Tasks
- [ ] Send login credentials to authorized users
- [ ] Create additional user accounts if needed
- [ ] Import existing configurations
- [ ] Test all scrapers in production
- [ ] Monitor for first 24 hours

### Week 1 Tasks
- [ ] Review logs daily
- [ ] Check for any errors
- [ ] Verify scrapers running correctly
- [ ] Test performance under load
- [ ] Optimize if needed

### Ongoing Maintenance
- [ ] Weekly log review
- [ ] Monthly database backup
- [ ] Quarterly security audit
- [ ] Update dependencies periodically

---

## ❌ Rollback Plan

If deployment fails:
- [ ] Keep local version running
- [ ] Document all errors
- [ ] Restore database backup if needed
- [ ] Review error logs
- [ ] Contact Namecheap support if hosting issue
- [ ] Refer to troubleshooting in DEPLOYMENT_NAMECHEAP.md

---

## 📞 Support Contacts

**Namecheap Support:**
- Live Chat: 24/7 available in cPanel
- Phone: Check Namecheap account for support number
- Ticket System: Via cPanel

**Application Issues:**
- Check: `/logs/app.log`
- Check: `/logs/php_errors.log`
- Use: `/utils/debug.php` for diagnostics

---

## ✍️ Sign-Off

**Deployment Completed By:** _______________

**Date:** _______________

**Time:** _______________

**Domain:** scraper.staging-ptd.com

**Status:** ☐ Success  ☐ Issues (document below)

**Notes:**
_______________________________________________________
_______________________________________________________
_______________________________________________________

---

**This checklist should be completed in order and all items checked before considering deployment complete.**
