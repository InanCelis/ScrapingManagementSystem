# Scraping Management System - Deployment Package
## Ready for Namecheap cPanel: scraper.staging-ptd.com

---

## 📦 What's Included

This package contains everything needed to deploy the Scraping Management System to Namecheap cPanel hosting.

### Application Files
- **Scrapers:** Website and XML scrapers in `Executable/` and `ExecutableXML/`
- **Core System:** Authentication, database, logging in `core/`
- **Web Interface:** Dashboard, configuration management in `views/`
- **API:** RESTful endpoints in `api/`
- **Helpers:** Utility functions in `Helpers/`

### Deployment Resources
- **DEPLOYMENT_NAMECHEAP.md** - Complete step-by-step deployment guide
- **DEPLOYMENT_CHECKLIST.md** - Comprehensive deployment checklist
- **QUICK_DEPLOY.md** - 10-minute quick deployment guide
- **TROUBLESHOOTING.md** - Common issues and solutions

### Production Configuration Files
- **config.production.php** - Production database and app configuration
- **bootstrap.production.php** - Production bootstrap with correct URLs
- **.htaccess.production** - Production Apache configuration with security

---

## 🚀 Quick Start

### For First-Time Deployers
👉 Start here: [DEPLOYMENT_NAMECHEAP.md](DEPLOYMENT_NAMECHEAP.md)

This guide includes:
- Pre-deployment checklist
- Database setup instructions
- File upload methods
- Configuration steps
- Testing procedures
- Security hardening

### For Experienced Users
👉 Use this: [QUICK_DEPLOY.md](QUICK_DEPLOY.md)

Get deployed in 10 minutes with condensed instructions.

### For Systematic Deployment
👉 Follow: [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)

Complete checklist to ensure nothing is missed.

---

## 📋 Deployment Overview

### 1️⃣ Prepare Locally
```bash
composer install --no-dev --optimize-autoloader
```

### 2️⃣ Setup Database
- Create MySQL database in cPanel
- Import `database/schema.sql`
- Note credentials

### 3️⃣ Upload Files
- Via cPanel File Manager, FTP, or Git
- Upload to `public_html/`

### 4️⃣ Configure
- Update `config/config.php` with database credentials
- Update `bootstrap.php` with domain URL
- Update `.htaccess` with correct paths

### 5️⃣ Set Permissions
- `logs/` → 755
- `temp/` → 755
- `ScrapeFile/` → 755

### 6️⃣ Test
- Visit: https://scraper.staging-ptd.com
- Login: admin / admin123
- Change password!

---

## 🔧 System Requirements

### Server Requirements
- **PHP:** 8.0 or higher
- **MySQL:** 5.7 or higher (or MariaDB 10.2+)
- **Apache:** 2.4+ with mod_rewrite
- **Disk Space:** 500MB minimum
- **Memory:** 256MB PHP memory limit (512MB recommended)

### Required PHP Extensions
- ✅ PDO
- ✅ pdo_mysql
- ✅ mbstring
- ✅ curl
- ✅ openssl
- ✅ json
- ✅ xml

### Optional but Recommended
- SSL certificate (free Let's Encrypt available)
- SSH access for advanced deployment
- Cron job support for automation

---

## 📁 Important Files

### Configuration Files (MUST UPDATE)
```
config/config.php          - Database and app settings
bootstrap.php              - Application bootstrap
.htaccess                  - Apache configuration
```

### Production Templates (Use These!)
```
config/config.production.php    - Copy to config.php
bootstrap.production.php        - Copy to bootstrap.php
.htaccess.production           - Copy to .htaccess
```

### Database
```
database/schema.sql        - Complete database structure
```

### Utilities
```
utils/check.php           - Installation verification
utils/fix-admin.php       - Reset admin password
utils/debug.php           - System diagnostics
```

---

## 🔐 Default Credentials

**After deployment, login with:**
- **URL:** https://scraper.staging-ptd.com/login
- **Username:** admin
- **Password:** admin123

⚠️ **IMPORTANT:** Change this password immediately after first login!

---

## 🛡️ Security Notes

### Before Going Live
- [ ] Change admin password
- [ ] Set `debug => false` in config.php
- [ ] Set `secure => true` in session config
- [ ] Install SSL certificate
- [ ] Verify .htaccess protecting sensitive files
- [ ] Review file permissions

### Protected Files
The `.htaccess` file protects:
- Configuration files (config.php)
- Log files (*.log)
- Documentation (*.md)
- Data files (*.json)
- Git files (.git/)
- Composer files

### Test Protection
Try accessing these URLs (should all be denied):
- /config/config.php
- /logs/app.log
- /database/schema.sql
- /.git/

---

## 📊 Features

### Core Features
- ✅ Web scraping management
- ✅ XML feed processing
- ✅ User authentication
- ✅ Activity logging
- ✅ Configuration management
- ✅ Process monitoring
- ✅ API integration
- ✅ Responsive dashboard

### Scrapers Included
- Real estate websites
- XML property feeds
- Kyero feeds
- Various international property sites

### API Integration
- WordPress REST API support
- Property upload functionality
- Bulk operations
- Retry logic with exponential backoff

---

## 📞 Support & Resources

### Documentation
- [DEPLOYMENT_NAMECHEAP.md](DEPLOYMENT_NAMECHEAP.md) - Full deployment guide
- [QUICK_DEPLOY.md](QUICK_DEPLOY.md) - Quick deployment
- [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md) - Deployment checklist
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Troubleshooting guide
- [STRUCTURE.md](STRUCTURE.md) - Project structure

### Namecheap Support
- **Live Chat:** 24/7 in cPanel
- **Knowledge Base:** https://www.namecheap.com/support/knowledgebase/
- **Ticket System:** Via cPanel

### Getting Help
1. Check [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
2. Review error logs: `/logs/app.log`
3. Run diagnostics: `/utils/check.php`
4. Contact Namecheap support for hosting issues

---

## 🔄 Deployment Process Summary

```
┌─────────────────────┐
│  1. Local Setup     │
│  Run composer       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  2. Database        │
│  Create & Import    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  3. Upload Files    │
│  via cPanel/FTP     │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  4. Configure       │
│  Update configs     │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  5. Permissions     │
│  Set folder perms   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  6. PHP Setup       │
│  Enable extensions  │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  7. SSL Install     │
│  Enable HTTPS       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  8. Test & Verify   │
│  Run checks         │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  9. Secure          │
│  Change password    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  ✅ Live!           │
│  Monitor & Maintain │
└─────────────────────┘
```

---

## ⚡ Quick Commands Reference

### Composer
```bash
# Install dependencies
composer install --no-dev --optimize-autoloader
```

### File Permissions (via SSH)
```bash
# Set directory permissions
chmod 755 logs temp ScrapeFile uploads

# Set file permissions
chmod 644 config/config.php bootstrap.php .htaccess
```

### Database Import (via SSH)
```bash
mysql -u username_scraperman_user -p username_scraperman_db < database/schema.sql
```

---

## 📈 Post-Deployment

### Immediate Tasks
1. Login and change admin password
2. Test all scrapers
3. Configure cron jobs (optional)
4. Set up monitoring

### Regular Maintenance
- **Weekly:** Review logs
- **Monthly:** Database backup
- **Quarterly:** Update dependencies
- **As Needed:** Add new scrapers

### Monitoring
- Check `/logs/app.log` for errors
- Monitor disk space usage
- Review activity logs for suspicious activity
- Test scrapers periodically

---

## 🎯 Success Criteria

Your deployment is successful when:
- ✅ Site loads at https://scraper.staging-ptd.com
- ✅ SSL certificate active (green padlock)
- ✅ Login works with admin credentials
- ✅ Dashboard displays correctly
- ✅ Can create configurations
- ✅ Scrapers execute successfully
- ✅ Logs being written
- ✅ API connections working
- ✅ All security checks pass

---

## 📝 Deployment Notes

**Target Domain:** scraper.staging-ptd.com

**Pointing to:** scraper.internationalpropertyalerts.com (legacy)

**New Deployment:** scraper.staging-ptd.com (Namecheap cPanel)

**Database:** MySQL (cPanel)

**PHP Version:** 8.0+

**SSL:** Let's Encrypt (free)

---

## ✅ Pre-Flight Checklist

Before starting deployment:
- [ ] Have cPanel access credentials
- [ ] Domain DNS pointed to hosting
- [ ] Composer dependencies installed locally
- [ ] Reviewed all deployment documentation
- [ ] Backed up local development database
- [ ] Tested application locally
- [ ] Prepared production configuration values
- [ ] Noted all custom settings

---

## 🚀 Ready to Deploy?

Choose your path:

### **Never deployed before?**
📖 Read: [DEPLOYMENT_NAMECHEAP.md](DEPLOYMENT_NAMECHEAP.md)

### **Experienced developer?**
⚡ Use: [QUICK_DEPLOY.md](QUICK_DEPLOY.md)

### **Want a checklist?**
✓ Follow: [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)

### **Having issues?**
🔧 Check: [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

---

## 📞 Contact Information

**Domain:** scraper.staging-ptd.com

**Hosting:** Namecheap cPanel

**Created:** 2025

**Version:** 1.0

---

**Good luck with your deployment! 🎉**

For questions or issues, refer to the comprehensive documentation included in this package.
