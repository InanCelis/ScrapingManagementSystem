# Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Database Setup (2 minutes)

1. Open **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Click "New" to create database
3. Database name: `scraper_management`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"
6. Select the database
7. Go to "Import" tab
8. Choose file: `database/schema.sql`
9. Click "Go"

### Step 2: Configuration (1 minute)

1. Open `config/config.php`
2. Update if needed (default settings work for XAMPP):
   ```php
   'username' => 'root',
   'password' => '',
   ```

### Step 3: Verify Installation (1 minute)

1. Open: `http://localhost/ScrapingToolsAutoSync/setup.php`
2. Check for any errors
3. If all green, you're good to go!

### Step 4: Login (1 minute)

1. Open: `http://localhost/ScrapingToolsAutoSync/login.php`
2. Username: `admin`
3. Password: `admin123`
4. Click "Sign In"

### Step 5: Start Using (ongoing)

#### Add Your First Configuration

1. Click "Configurations" in sidebar
2. Click "Add Configuration"
3. Fill in the form:

**For Website Scraper:**
```
Name: Holiday Homes Spain
Type: Website
Website URL: https://holiday-homes-spain.com
URL Pattern: /property-search-results/?page_num={$page}
Count of Pages: 10
Start Page: 1
End Page: 10
Folder Name: HolidayHomesSpain
Filename: Properties.json
File Path: Executable/HolidayHomesSpain.php
Enable Upload: Yes
Testing Mode: No
```

**For XML Processor:**
```
Name: Nilsott Feed
Type: XML
XML Link: https://web3930:9a42ded9cb@www.nilsott.com/xml/kyero.xml
Count of Properties: 0 (all)
Folder Name: KyeroXML
Filename: properties.json
File Path: ExecutableXML/KyeroXML.php
Enable Upload: Yes
Testing Mode: No
```

4. Click "Create Configuration"

#### Run Your First Scraper

1. Go to "Running Tools"
2. Find your configuration
3. Click the **Play** button (▶)
4. Wait for it to start
5. Click the **Eye** icon (👁) to watch progress
6. See live console output!

## 🎯 Common Tasks

### View Dashboard
- Click "Dashboard" in sidebar
- See statistics, charts, recent activity

### Monitor Running Scrapers
- Click "Running Tools"
- See all running processes
- Start/Stop any scraper
- View live progress

### Manage Configurations
- Click "Configurations"
- Edit, Delete, or Duplicate any config
- Search and filter

### View Activity Logs
- Click "Activity Log"
- See all system activities
- Track user actions

## 🔧 Troubleshooting

### Can't connect to database?
1. Make sure XAMPP MySQL is running
2. Check database exists: `scraper_management`
3. Verify credentials in `config/config.php`

### Tables not found?
1. Import `database/schema.sql` in phpMyAdmin

### Can't login?
1. Default username: `admin`
2. Default password: `admin123`
3. Check if `users` table has data

### Scraper won't start?
1. Check file path is correct
2. Verify the PHP class file exists
3. Check `logs/` directory for errors

## 📚 Next Steps

- Read [README.md](README.md) for full documentation
- Check [INSTALLATION.md](INSTALLATION.md) for detailed setup
- Review [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md) for technical details

## 🆘 Need Help?

1. Run `setup.php` to verify installation
2. Check `logs/app.log` for errors
3. Enable debug mode in `config/config.php`
4. Review error messages in browser console

## ✅ Checklist

- [ ] Database created: `scraper_management`
- [ ] Schema imported from `database/schema.sql`
- [ ] Config updated in `config/config.php`
- [ ] Setup verification passed: `setup.php`
- [ ] Can login with admin/admin123
- [ ] Dashboard loads successfully
- [ ] First configuration created
- [ ] First scraper run successfully
- [ ] Changed default password

**You're all set! Happy scraping! 🎉**
