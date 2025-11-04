# API Settings Management Guide

## Overview
The API domain and settings are now **fully configurable** through a user-friendly interface in the Settings page. This allows you to easily change the API domain, token, and other settings without editing code files.

## How to Access API Settings

1. **Login to the System**
   - Navigate to: `http://localhost/ScrapingToolsAutoSync/`
   - Login with your credentials

2. **Go to Settings**
   - Click on **Settings** in the sidebar menu
   - Or navigate directly to: `http://localhost/ScrapingToolsAutoSync/views/settings.php`

3. **Find API Settings Section**
   - The API Settings card is at the top of the Settings page
   - It has a dark header with "API Settings" title

## Editable API Settings

### 1. **API Base Domain** (Required)
- **Field:** Text input (URL format)
- **Default:** `https://internationalpropertyalerts.com`
- **Description:** The base URL for all API requests
- **Example:** `https://your-custom-domain.com`
- **Validation:** Must be a valid URL

### 2. **API Token**
- **Field:** Password input (toggleable)
- **Default:** Your current API token
- **Description:** Authentication token for API requests
- **Features:** Click the eye icon to show/hide the token
- **Validation:** Optional, but recommended for security

### 3. **Max Retries**
- **Field:** Number input
- **Default:** `3`
- **Range:** 1-10 attempts
- **Description:** Number of retry attempts for failed API requests

### 4. **Timeout (seconds)**
- **Field:** Number input
- **Default:** `600` (10 minutes)
- **Range:** 30-3600 seconds
- **Description:** Maximum time to wait for API response

### 5. **Connect Timeout (seconds)**
- **Field:** Number input
- **Default:** `60` (1 minute)
- **Range:** 5-300 seconds
- **Description:** Maximum time to wait for initial connection

### 6. **Enable Debug Mode**
- **Field:** Checkbox
- **Default:** Unchecked (disabled)
- **Description:** Log detailed API request/response information

## How to Update API Settings

### Step 1: Navigate to Settings
```
http://localhost/ScrapingToolsAutoSync/views/settings.php
```

### Step 2: Locate API Settings Card
The card is labeled with "API Settings" and has a dark header.

### Step 3: Edit the Domain
1. Click on the **API Base Domain** field
2. Clear the existing value
3. Enter your new domain URL (e.g., `https://new-domain.com`)
4. Make sure it starts with `http://` or `https://`

### Step 4: Update Other Settings (Optional)
- Modify the API token if needed
- Adjust timeout values
- Enable debug mode for troubleshooting

### Step 5: Save Changes
Click the **"Save API Settings"** button at the bottom of the form.

### Step 6: Verify Success
You should see a green success message: **"API settings updated successfully!"**

## Where Settings are Stored

### Priority Order (Highest to Lowest):
1. **Database** (system_settings table) - **PRIMARY SOURCE**
2. **Config File** (config/config.php) - **FALLBACK**
3. **Hardcoded Defaults** - **LAST RESORT**

The system follows this priority:
- If settings exist in the database → Use database values
- If database is empty → Use config file values
- If config file doesn't exist → Use hardcoded defaults

## Database Storage

Settings are stored in the `system_settings` table:

| setting_key             | setting_value                              | category |
|-------------------------|---------------------------------------------|----------|
| api_base_domain         | https://internationalpropertyalerts.com    | api      |
| api_token               | eyJpYXQiOjE3NTk4NDI5OTYsImV4cCI6MTc2...   | api      |
| api_max_retries         | 3                                           | api      |
| api_timeout             | 600                                         | api      |
| api_connect_timeout     | 60                                          | api      |
| api_debug               | 0                                           | api      |
| api_properties_endpoint | /wp-json/houzez/v1/properties              | api      |
| api_links_endpoint      | /wp-json/houzez/v1/links-by-owner          | api      |

## Database Setup

### Run the Migration
Execute this SQL file to create the settings table:
```sql
database/migrations/add_system_settings_table.sql
```

Or manually run:
```bash
mysql -u root -p scraper_management < database/migrations/add_system_settings_table.sql
```

## How ApiSender Uses Settings

The `ApiSender` class automatically:
1. Tries to load settings from the database first
2. Falls back to config file if database is empty
3. Uses hardcoded defaults if neither exists
4. Allows runtime override via constructor or setter methods

### Example Usage:
```php
// Automatically uses database settings
$apiSender = new ApiSender();
$result = $apiSender->sendProperty($propertyData);

// Override domain at runtime
$apiSender->setBaseDomain('https://staging-api.com');
```

## Testing Your Changes

### 1. Update Settings via UI
- Change the API Base Domain to a test URL
- Save the settings

### 2. Verify in Code
```php
require_once 'Api/ApiSender.php';

$apiSender = new ApiSender();
echo $apiSender->getBaseDomain(); // Should show your new domain
```

### 3. Check Database
```sql
SELECT * FROM system_settings WHERE category = 'api';
```

## Troubleshooting

### Settings Not Saving
**Problem:** Error message when clicking Save
**Solution:**
- Check database connection in `config/config.php`
- Ensure the `system_settings` table exists
- Run the migration SQL file

### Settings Not Loading
**Problem:** Changes don't appear in the UI
**Solution:**
- Clear browser cache and refresh the page
- Check if database table exists: `SHOW TABLES LIKE 'system_settings';`
- Verify database credentials

### API Still Using Old Domain
**Problem:** ApiSender uses old domain after updating
**Solution:**
- Restart your PHP server
- Clear any opcache if enabled
- Check that settings were saved to database

## Security Notes

1. **Token Protection**
   - API token is stored as password field (hidden by default)
   - Use the eye icon to toggle visibility when needed
   - Never share your token publicly

2. **Database Security**
   - Settings are stored in database with user tracking
   - Each update logs which user made the change
   - Activity is recorded in `activity_logs` table

3. **Access Control**
   - Only authenticated users can access Settings page
   - Check `views/settings.php:7` for authentication requirement

## Activity Logging

Every time you update API settings:
- Action is logged to `activity_logs` table
- Logs include: user_id, timestamp, IP address, user agent
- View logs in: Activity Log page

## Advanced: Programmatic Access

### Get Current Settings
```php
$apiSender = new ApiSender();
echo $apiSender->getBaseDomain();  // Get domain
echo $apiSender->getToken();       // Get token
```

### Update Settings Programmatically
```php
$apiSender = new ApiSender();
$apiSender->setBaseDomain('https://new-domain.com');
$apiSender->setToken('new-token-here');
```

### Use Custom Domain Temporarily
```php
// Override only for this instance
$apiSender = new ApiSender(false, 'https://temp-domain.com');
```

## File References

- **Settings UI:** [views/settings.php](../views/settings.php:297-373)
- **ApiSender Class:** [Api/ApiSender.php](../Api/ApiSender.php)
- **Config File:** [config/config.php](../config/config.php:65-77)
- **Database Migration:** [database/migrations/add_system_settings_table.sql](../database/migrations/add_system_settings_table.sql)

## Support

If you encounter any issues:
1. Check this guide first
2. Review error messages in the UI
3. Check database connection
4. Verify table structure
5. Contact system administrator

---

**Last Updated:** 2025-10-16
**Version:** 1.0
