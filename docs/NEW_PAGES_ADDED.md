# New Pages Added - Profile & Settings

## Overview
Two new pages have been added to the Scraper Management System to complete the user experience:

1. **Profile Page** - User profile management
2. **Settings Page** - System configuration and maintenance

---

## Profile Page (`views/profile.php`)

### Features:
- **View & Edit Profile Information**
  - Username (read-only)
  - Full Name
  - Email Address

- **Change Password**
  - Requires current password for security
  - Minimum 6 characters for new password
  - Password confirmation
  - All changes are logged

- **Account Statistics**
  - Total configurations created
  - Total scraper runs
  - Total items scraped

- **Account Information**
  - Account creation date
  - Last login timestamp

### Access:
- URL: `http://localhost/ScrapingToolsAutoSync/views/profile.php`
- Available from top-right user dropdown menu
- Also available from sidebar navigation

### Security:
- Requires authentication
- Current password verification for password changes
- Email uniqueness validation
- Password hashing using bcrypt
- All changes logged to activity_logs table

---

## Settings Page (`views/settings.php`)

### Features:

#### Application Settings
- **Application Name** - Customize the application title
- **Timezone** - Set timezone for date/time displays (UTC, US, Europe, Asia, etc.)
- **Items Per Page** - Number of items to display in lists (5-100)
- **Enable Notifications** - Toggle system notifications on/off

#### Scraper Settings
- **Max Concurrent Scrapers** - Limit simultaneous scraper processes (1-20)
- **Default Timeout** - Default timeout for scraper operations (30-3600 seconds)
- **Log Retention** - Number of days to keep logs (1-365 days)

#### Maintenance Tools
- **Clean Old Logs** - Remove scraper logs older than specified days
- Helps free up database space
- Confirmation required before deletion

#### System Information Panel
- PHP Version
- Database Host & Name
- Server Software
- Document Root

#### Database Statistics Panel
- Total Configurations
- Total Process Runs
- Total Scraper Logs
- Total Activity Logs
- Total Users

#### Disk Usage Panel
- Visual progress bar
- Used space in GB
- Free space in GB
- Total disk space

### Access:
- URL: `http://localhost/ScrapingToolsAutoSync/views/settings.php`
- Available from top-right user dropdown menu
- Also available from sidebar navigation

### Configuration Storage:
Settings are stored in `config/config.php` file with PHP constants:
```php
define('APP_NAME', 'Scraper Manager');
define('TIMEZONE', 'UTC');
define('ITEMS_PER_PAGE', 10);
define('ENABLE_NOTIFICATIONS', true);
define('MAX_CONCURRENT_SCRAPERS', 5);
define('DEFAULT_TIMEOUT', 300);
define('LOG_RETENTION_DAYS', 30);
```

---

## Navigation Updates

### Sidebar Navigation
The sidebar now includes:
- Dashboard
- Running Tools
- Configurations
- Activity Log
- **--- Divider ---**
- **My Profile** (NEW)
- **Settings** (NEW)
- Logout

### Top Bar User Dropdown
The user dropdown menu includes:
- **Profile** (NEW)
- **Settings** (NEW)
- Logout

---

## Backward Compatibility

Redirect files have been created in the root directory:
- `profile.php` → redirects to `views/profile.php`
- `settings.php` → redirects to `views/settings.php`

Both old and new URLs will work correctly.

---

## Files Created

### View Files
1. `views/profile.php` - User profile page (230 lines)
2. `views/settings.php` - System settings page (390 lines)

### Redirect Files
3. `profile.php` - Root redirect to views/profile.php
4. `settings.php` - Root redirect to views/settings.php

### Documentation
5. `docs/NEW_PAGES_ADDED.md` - This documentation file

### Updated Files
6. `includes/sidebar.php` - Added Profile & Settings links

---

## Testing Checklist

### Profile Page Testing
- [ ] Access profile page from user dropdown
- [ ] Access profile page from sidebar
- [ ] Update full name and email
- [ ] Change password with correct current password
- [ ] Try to change password with wrong current password (should fail)
- [ ] Try to use email already taken by another user (should fail)
- [ ] Verify account statistics are accurate
- [ ] Verify account creation and last login dates display correctly

### Settings Page Testing
- [ ] Access settings page from user dropdown
- [ ] Access settings page from sidebar
- [ ] Update application name and verify it reflects in UI
- [ ] Change timezone
- [ ] Change items per page (test with values 5-100)
- [ ] Toggle notifications on/off
- [ ] Change max concurrent scrapers (test with values 1-20)
- [ ] Change default timeout (test with values 30-3600)
- [ ] Change log retention days (test with values 1-365)
- [ ] Test cleanup logs functionality
- [ ] Verify system information displays correctly
- [ ] Verify database statistics are accurate
- [ ] Verify disk usage shows correctly

### Navigation Testing
- [ ] Verify Profile link in sidebar highlights when on profile page
- [ ] Verify Settings link in sidebar highlights when on settings page
- [ ] Verify Profile link in user dropdown works
- [ ] Verify Settings link in user dropdown works
- [ ] Test backward compatibility with old URLs

---

## Database Tables Used

### Profile Page
- `users` - Read and update user information
- `activity_logs` - Log profile updates
- `scraper_configs` - Count user's configurations
- `scraper_processes` - Count user's runs and scraped items

### Settings Page
- `activity_logs` - Log settings updates and maintenance actions
- `scraper_logs` - Clean old logs
- `scraper_configs` - Statistics
- `scraper_processes` - Statistics
- `users` - Statistics

---

## Security Considerations

### Profile Page
- Password changes require current password
- Email uniqueness is validated
- Passwords are hashed using bcrypt (PASSWORD_DEFAULT)
- All updates are logged with IP and user agent
- Session-based authentication required

### Settings Page
- All settings have validation ranges
- File write permissions checked before saving config
- Maintenance actions require confirmation
- All actions are logged
- Only authenticated users can access

---

## Next Steps

1. **Test all functionality** using the checklist above
2. **Customize settings** according to your needs
3. **Update your profile** with correct information
4. **Configure log retention** based on your storage capacity
5. **Set appropriate timezone** for your location

---

## Troubleshooting

### Profile Page Issues
- **"Email is already taken"** - Choose a different email address
- **"Current password is incorrect"** - Verify you're using the correct current password
- **Statistics showing 0** - This is normal for a fresh database; statistics will populate as you use the system

### Settings Page Issues
- **"Failed to write configuration file"** - Check that `config/` folder has write permissions (755)
- **Settings not taking effect** - Some settings may require page refresh to take effect
- **Disk usage not showing** - This is normal on some hosting environments with restricted filesystem access

### Navigation Issues
- **404 Not Found** - Clear your browser cache and try again
- **Page not highlighting in sidebar** - This is purely visual; functionality is not affected

---

## URLs Quick Reference

| Page | New URL | Old URL (Redirect) |
|------|---------|-------------------|
| Profile | `/views/profile.php` | `/profile.php` |
| Settings | `/views/settings.php` | `/settings.php` |

---

**Last Updated:** 2025-10-15
**Status:** ✅ Complete and Ready to Use
