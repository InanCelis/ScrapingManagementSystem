# Quick Start: API Settings Configuration

## ✅ The Problem is Fixed!

The database table has been created and the API settings are now ready to use.

## 🚀 Quick Access

**Settings Page:**
```
http://localhost/ScrapingToolsAutoSync/views/settings.php
```

**Verification Page:**
```
http://localhost/ScrapingToolsAutoSync/verify-api-setup.php
```

## 📝 How to Edit API Domain (3 Easy Steps)

### Step 1: Login
Navigate to: `http://localhost/ScrapingToolsAutoSync/`

### Step 2: Go to Settings
Click **Settings** in the sidebar or go directly to:
`http://localhost/ScrapingToolsAutoSync/views/settings.php`

### Step 3: Edit & Save
1. Find the **"API Settings"** card (dark header at top)
2. Change the **API Base Domain** field to your desired URL
3. Click **"Save API Settings"**
4. Done! ✨

## 🔧 Database Setup (Already Completed)

The database table `system_settings` has been successfully created with these default values:

| Setting | Value |
|---------|-------|
| API Base Domain | https://internationalpropertyalerts.com |
| API Token | eyJpYXQiOjE3NTk4NDI5OTYsImV4cCI6MTc2MDAxNTc5Nn0= |
| Max Retries | 3 |
| Timeout | 600 seconds |
| Connect Timeout | 60 seconds |
| Debug Mode | Disabled |

## 🎯 Current Status

✅ Database table created
✅ Default settings inserted
✅ UI ready to use
✅ ApiSender configured to read from database

## 📋 Available Settings

You can now edit these settings through the UI:

1. **API Base Domain** - The main URL for API requests (e.g., https://example.com)
2. **API Token** - Authentication token (password protected with show/hide)
3. **Max Retries** - Number of retry attempts (1-10)
4. **Timeout** - Request timeout in seconds (30-3600)
5. **Connect Timeout** - Connection timeout in seconds (5-300)
6. **Debug Mode** - Enable detailed logging (checkbox)

## 🔍 Verify Your Setup

Run the verification page to check everything is working:
```
http://localhost/ScrapingToolsAutoSync/verify-api-setup.php
```

This page will show you:
- ✓ Database connection status
- ✓ Table existence
- ✓ Current API settings
- ✓ ApiSender configuration
- ✓ Settings page access

## 🛠️ Tools Provided

### 1. Installation Script
**File:** `install-api-settings.bat`
**Purpose:** Reinstall/repair the database table
**Usage:** Double-click to run

### 2. Verification Page
**URL:** `http://localhost/ScrapingToolsAutoSync/verify-api-setup.php`
**Purpose:** Check if everything is set up correctly

### 3. Settings UI
**URL:** `http://localhost/ScrapingToolsAutoSync/views/settings.php`
**Purpose:** Edit API settings through user interface

## 📖 Documentation

Full documentation available at:
- [API Settings Guide](docs/API_SETTINGS_GUIDE.md) - Complete user guide
- [API Configuration README](Api/README_API_CONFIG.md) - Developer reference

## ⚡ Quick Examples

### Change Domain via UI (Recommended)
1. Go to Settings page
2. Update "API Base Domain" field
3. Click "Save API Settings"

### Check Current Domain in Code
```php
require_once 'Api/ApiSender.php';
$apiSender = new ApiSender();
echo $apiSender->getBaseDomain();
```

### Override Domain Temporarily
```php
$apiSender = new ApiSender(false, 'https://temp-domain.com');
```

## 🔐 Security Features

- ✅ Password-protected token field
- ✅ User authentication required
- ✅ Activity logging for all changes
- ✅ Audit trail in database
- ✅ Show/hide token toggle

## ❓ Common Questions

**Q: Where are settings stored?**
A: In the `system_settings` database table (primary) and `config/config.php` (fallback)

**Q: Do I need to edit code files?**
A: No! Just use the Settings page UI.

**Q: Can I change the domain per scraper?**
A: Yes, you can override it in code using `setBaseDomain()` method.

**Q: What happens if I break something?**
A: Run `install-api-settings.bat` to restore defaults.

## 🆘 Need Help?

1. Run the verification page: `verify-api-setup.php`
2. Check the [full documentation](docs/API_SETTINGS_GUIDE.md)
3. Review error messages in the Settings page

## 🎉 You're All Set!

The API domain is now fully configurable through the database and UI.
No more manual file editing required!

---

**Status:** ✅ Ready to Use
**Last Updated:** 2025-10-16
