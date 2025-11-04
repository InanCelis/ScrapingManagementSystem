# Project Structure

## 📁 Organized Directory Layout

```
ScrapingToolsAutoSync/
│
├── 📂 api/                      # API Endpoints
│   ├── config.php              # Configuration CRUD API
│   └── scraper.php             # Scraper control API
│
├── 📂 config/                   # Configuration Files
│   └── config.php              # Main application config
│
├── 📂 core/                     # Core PHP Classes (OOP)
│   ├── Auth.php                # Authentication system
│   ├── Database.php            # Database wrapper
│   ├── ScraperLogger.php       # Process logging
│   └── ScraperManager.php      # Business logic
│
├── 📂 database/                 # Database Files
│   └── schema.sql              # Database schema
│
├── 📂 docs/                     # Documentation
│   ├── README.md               # Full documentation
│   ├── INSTALLATION.md         # Setup guide
│   ├── QUICKSTART.md           # Quick start
│   └── PROJECT_SUMMARY.md      # Technical overview
│
├── 📂 Executable/               # Website Scrapers
│   ├── HolidayHomesSpain.php
│   ├── BaySideRE.php
│   └── ...
│
├── 📂 ExecutableXML/            # XML Processors
│   ├── KyeroXML.php
│   ├── JLL.php
│   └── ...
│
├── 📂 Helpers/                  # Helper Classes
│   ├── ScraperHelpers.php
│   └── XMLHelpers.php
│
├── 📂 includes/                 # Reusable UI Components
│   ├── header.php
│   ├── sidebar.php
│   ├── topbar.php
│   ├── footer.php
│   └── progress-modal.php
│
├── 📂 logs/                     # Application Logs
│   └── *.log
│
├── 📂 public/                   # Public Assets
│   └── assets/
│       ├── css/
│       │   └── style.css
│       └── js/
│           └── main.js
│
├── 📂 ScrapeFile/               # Scraped Data Storage
│   ├── HolidayHomesSpain/
│   ├── BaySideRE/
│   └── ...
│
├── 📂 temp/                     # Temporary Files
│
├── 📂 utils/                    # Utility Scripts
│   ├── check.php               # Installation check
│   ├── debug.php               # Debug information
│   ├── fix-admin.php           # Fix admin user
│   ├── setup.php               # Setup verification
│   └── test.php                # PHP info test
│
├── 📂 views/                    # View Files (UI Pages)
│   ├── activity-log.php
│   ├── configuration-form.php
│   ├── configurations.php
│   ├── dashboard.php
│   ├── login.php
│   ├── logout.php
│   ├── running-tools.php
│   └── welcome.php
│
├── .htaccess                    # Apache configuration
├── bootstrap.php                # Application bootstrap
└── index.php                    # Legacy scraper runner

```

## 🎯 Key Directories

### **Core Application**
- `core/` - All OOP PHP classes
- `config/` - Configuration files
- `api/` - RESTful API endpoints

### **User Interface**
- `views/` - All page views (dashboard, login, etc.)
- `includes/` - Reusable UI components
- `public/assets/` - CSS, JavaScript, images

### **Scraping**
- `Executable/` - Website scrapers
- `ExecutableXML/` - XML processors
- `Helpers/` - Helper functions
- `ScrapeFile/` - Output data storage

### **Utilities**
- `utils/` - Setup, debug, admin tools
- `docs/` - All documentation
- `database/` - Database schemas
- `logs/` - Application logs
- `temp/` - Temporary files

## 🔗 URL Structure

### Main Pages
- `/views/login.php` - Login
- `/views/dashboard.php` - Dashboard
- `/views/running-tools.php` - Running tools
- `/views/configurations.php` - Configurations
- `/views/activity-log.php` - Activity log

### Utilities
- `/utils/check.php` - Installation check
- `/utils/fix-admin.php` - Fix admin user
- `/utils/setup.php` - Setup verification

### API
- `/api/scraper.php` - Scraper API
- `/api/config.php` - Configuration API

## 📝 File Naming Convention

- **Views**: Descriptive names (dashboard.php, running-tools.php)
- **Core Classes**: PascalCase (Database.php, Auth.php)
- **Configs**: Lowercase (config.php)
- **Documentation**: UPPERCASE.md (README.md, INSTALLATION.md)

## 🚀 Quick Access

| What | Where |
|------|-------|
| **Login** | `/views/login.php` |
| **Dashboard** | `/views/dashboard.php` |
| **Check Setup** | `/utils/check.php` |
| **Fix Admin** | `/utils/fix-admin.php` |
| **Documentation** | `/docs/README.md` |
| **API Docs** | `/docs/PROJECT_SUMMARY.md` |

## 📦 Clean Structure Benefits

✅ **Easy to Navigate** - Everything in logical folders
✅ **Easy to Maintain** - Related files grouped together
✅ **Easy to Scale** - Clear separation of concerns
✅ **Professional** - Industry-standard structure
✅ **Version Control** - Better Git organization

## 🔄 Migration Notes

Files have been organized into appropriate folders:
- View files → `views/`
- Utility scripts → `utils/`
- Documentation → `docs/`
- Public assets → `public/assets/`

All files remain functional with their original paths for backward compatibility.
