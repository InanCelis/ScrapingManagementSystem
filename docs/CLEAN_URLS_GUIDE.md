# Clean URLs Reference Guide

## ✅ Clean URLs are now enabled!

You can now access all pages without `/views/` and `.php` extensions.

## 📋 Available Clean URLs

### Before vs After

| Old URL (with /views/ and .php) | New Clean URL |
|----------------------------------|---------------|
| `/ScrapingToolsAutoSync/views/dashboard.php` | `/ScrapingToolsAutoSync/dashboard` |
| `/ScrapingToolsAutoSync/views/profile.php` | `/ScrapingToolsAutoSync/profile` |
| `/ScrapingToolsAutoSync/views/settings.php` | `/ScrapingToolsAutoSync/settings` |
| `/ScrapingToolsAutoSync/views/running-tools.php` | `/ScrapingToolsAutoSync/running-tools` |
| `/ScrapingToolsAutoSync/views/configurations.php` | `/ScrapingToolsAutoSync/configurations` |
| `/ScrapingToolsAutoSync/views/configuration-form.php` | `/ScrapingToolsAutoSync/configuration-form` |
| `/ScrapingToolsAutoSync/views/activity-log.php` | `/ScrapingToolsAutoSync/activity-log` |
| `/ScrapingToolsAutoSync/views/login.php` | `/ScrapingToolsAutoSync/login` |
| `/ScrapingToolsAutoSync/views/logout.php` | `/ScrapingToolsAutoSync/logout` |
| `/ScrapingToolsAutoSync/views/welcome.php` | `/ScrapingToolsAutoSync/welcome` |

## 🌐 Full URLs (for browser)

Access these URLs directly in your browser:

### Main Pages
- **Home/Welcome:** `http://localhost/ScrapingToolsAutoSync/`
- **Login:** `http://localhost/ScrapingToolsAutoSync/login` ⭐
- **Dashboard:** `http://localhost/ScrapingToolsAutoSync/dashboard`
- **Profile:** `http://localhost/ScrapingToolsAutoSync/profile`
- **Settings:** `http://localhost/ScrapingToolsAutoSync/settings`

### Features
- **Running Tools:** `http://localhost/ScrapingToolsAutoSync/running-tools`
- **Configurations:** `http://localhost/ScrapingToolsAutoSync/configurations`
- **Configuration Form:** `http://localhost/ScrapingToolsAutoSync/configuration-form`
- **Activity Log:** `http://localhost/ScrapingToolsAutoSync/activity-log`

### Authentication
- **Login:** `http://localhost/ScrapingToolsAutoSync/login` ⭐
- **Logout:** `http://localhost/ScrapingToolsAutoSync/logout`

### Utilities
- **API Setup Verification:** `http://localhost/ScrapingToolsAutoSync/verify-api-setup.php`

## 🔧 How it Works

The `.htaccess` file uses Apache's `mod_rewrite` to:
1. Remove the `/views/` directory from URLs
2. Remove the `.php` file extension
3. Automatically route requests to the correct files

### Example:
```
User visits:     /ScrapingToolsAutoSync/profile
Apache rewrites: /ScrapingToolsAutoSync/views/profile.php
```

## 📝 URL Parameters Still Work

Clean URLs work with query parameters too:

- `/ScrapingToolsAutoSync/configuration-form?id=5`
- `/ScrapingToolsAutoSync/configurations?type=xml&search=kyero`

## ⚙️ Technical Details

### .htaccess Configuration

```apache
RewriteEngine On
RewriteBase /ScrapingToolsAutoSync/

# Exclude real files and directories
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Exclude special directories
RewriteCond %{REQUEST_URI} !^/(Api|Executable|ExecutableXML|Helpers|ScrapeFile|vendor|assets|logs|temp)/

# Route to views/ directory
RewriteRule ^([a-zA-Z0-9_-]+)$ views/$1.php [L]
```

### What's Excluded

The following directories are **NOT** affected by clean URLs:
- `/Api/` - API endpoints
- `/Executable/` - Scraper executable files
- `/ExecutableXML/` - XML scraper files
- `/Helpers/` - Helper classes
- `/ScrapeFile/` - Scraped data files
- `/vendor/` - Composer dependencies
- `/assets/` - CSS, JS, images
- `/logs/` - Log files
- `/temp/` - Temporary files

## ✨ Benefits

1. **Cleaner URLs** - Easier to read and remember
2. **Better SEO** - Search engines prefer clean URLs
3. **Professional** - Looks more polished
4. **Flexible** - Easy to change file structure without breaking URLs
5. **User-friendly** - Simpler to type and share

## 🔄 Backward Compatibility

**Old URLs still work!**

If someone bookmarked the old URL, it will still function:
- `http://localhost/ScrapingToolsAutoSync/views/dashboard.php` ✅ Still works
- `http://localhost/ScrapingToolsAutoSync/dashboard` ✅ New clean URL

## 🛠️ Troubleshooting

### Clean URLs not working?

**1. Check if mod_rewrite is enabled:**
```apache
# In httpd.conf or apache2.conf
LoadModule rewrite_module modules/mod_rewrite.so
```

**2. Check .htaccess is allowed:**
```apache
<Directory "C:/xampp/htdocs">
    AllowOverride All
</Directory>
```

**3. Restart Apache:**
- Stop and start XAMPP Apache server

### Getting 404 errors?

- Verify the file exists in `/views/` directory
- Check file permissions
- Look at Apache error logs: `xampp/apache/logs/error.log`

### Links still showing old URLs?

- Clear browser cache
- Check for hardcoded URLs in custom code
- All navigation menus have been updated automatically

## 📚 For Developers

### Creating New Pages

1. **Create the PHP file:**
   ```
   views/my-new-page.php
   ```

2. **Access via clean URL:**
   ```
   http://localhost/ScrapingToolsAutoSync/my-new-page
   ```

3. **No additional configuration needed!**
   The `.htaccess` rules will automatically handle it.

### Linking to Pages

Always use clean URLs in your code:

```php
// Good ✅
<a href="/ScrapingToolsAutoSync/profile">Profile</a>

// Avoid ❌ (still works but not recommended)
<a href="/ScrapingToolsAutoSync/views/profile.php">Profile</a>
```

## 🎯 Examples

### Example 1: Navigation Link
```html
<a href="/ScrapingToolsAutoSync/settings">
    <i class="fas fa-cog"></i> Settings
</a>
```

### Example 2: Form Action
```html
<form action="/ScrapingToolsAutoSync/configuration-form" method="POST">
    <!-- form fields -->
</form>
```

### Example 3: Redirect in PHP
```php
header('Location: /ScrapingToolsAutoSync/dashboard');
exit;
```

### Example 4: With Parameters
```html
<a href="/ScrapingToolsAutoSync/configuration-form?id=<?php echo $id; ?>">
    Edit Configuration
</a>
```

## ✅ All Updated Files

The following files have been updated to use clean URLs:

- ✅ `.htaccess` - Routing rules added
- ✅ `includes/sidebar.php` - Navigation menu
- ✅ `includes/topbar.php` - User dropdown menu
- ✅ `views/settings.php` - Cancel button
- ✅ `views/profile.php` - Cancel button
- ✅ `views/configurations.php` - All links and buttons
- ✅ `views/configuration-form.php` - Back and Cancel buttons

---

**Status:** ✅ Clean URLs Enabled
**Last Updated:** 2025-10-16
