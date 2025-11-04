<?php
/**
 * API Settings Setup Verification
 * Run this file to verify that API settings are properly configured
 * REQUIRES AUTHENTICATION
 */

session_start();
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';

$auth = new Auth();

// Require authentication to access this page
if (!$auth->check()) {
    header('Location: /ScrapingToolsAutoSync/login');
    exit;
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>API Settings Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #6c757d; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 API Settings Setup Verification</h1>";

// Check 1: Database Connection
echo "<h2>1. Database Connection</h2>";
try {
    require_once __DIR__ . '/core/Database.php';
    $db = Database::getInstance();
    echo "<p class='success'>✓ Database connection successful</p>";
    $dbConnected = true;
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    $dbConnected = false;
}

// Check 2: System Settings Table
echo "<h2>2. System Settings Table</h2>";
if ($dbConnected) {
    try {
        $tableCheck = $db->fetchOne("SHOW TABLES LIKE 'system_settings'");
        if ($tableCheck) {
            echo "<p class='success'>✓ System settings table exists</p>";
            $tableExists = true;
        } else {
            echo "<p class='error'>✗ System settings table does not exist</p>";
            echo "<div class='step'>";
            echo "<strong>To fix this, run:</strong>";
            echo "<div class='code'>install-api-settings.bat</div>";
            echo "Or manually execute:<br>";
            echo "<div class='code'>mysql -u root scraper_management < database/migrations/add_system_settings_table.sql</div>";
            echo "</div>";
            $tableExists = false;
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error checking table: " . htmlspecialchars($e->getMessage()) . "</p>";
        $tableExists = false;
    }
} else {
    echo "<p class='warning'>⊘ Skipped (database not connected)</p>";
    $tableExists = false;
}

// Check 3: API Settings Data
echo "<h2>3. API Settings Data</h2>";
if ($tableExists) {
    try {
        $apiSettings = $db->fetchAll("SELECT setting_key, setting_value, category FROM system_settings WHERE category = 'api'");

        if (!empty($apiSettings)) {
            echo "<p class='success'>✓ Found " . count($apiSettings) . " API settings</p>";

            echo "<table>";
            echo "<tr><th>Setting Key</th><th>Value</th></tr>";
            foreach ($apiSettings as $setting) {
                $value = $setting['setting_value'];
                // Mask token for security
                if ($setting['setting_key'] === 'api_token' && strlen($value) > 20) {
                    $value = substr($value, 0, 10) . '...' . substr($value, -10);
                }
                echo "<tr>";
                echo "<td>" . htmlspecialchars($setting['setting_key']) . "</td>";
                echo "<td>" . htmlspecialchars($value) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>✗ No API settings found in database</p>";
            echo "<div class='step'>";
            echo "<strong>To fix this, run:</strong>";
            echo "<div class='code'>install-api-settings.bat</div>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error reading settings: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='warning'>⊘ Skipped (table does not exist)</p>";
}

// Check 4: ApiSender Class
echo "<h2>4. ApiSender Class</h2>";
try {
    require_once __DIR__ . '/Api/ApiSender.php';
    $apiSender = new ApiSender();
    $domain = $apiSender->getBaseDomain();

    echo "<p class='success'>✓ ApiSender class loaded successfully</p>";
    echo "<p><strong>Current API Domain:</strong> " . htmlspecialchars($domain) . "</p>";

    if ($tableExists && !empty($apiSettings)) {
        $dbDomain = '';
        foreach ($apiSettings as $setting) {
            if ($setting['setting_key'] === 'api_base_domain') {
                $dbDomain = $setting['setting_value'];
                break;
            }
        }

        if ($domain === $dbDomain) {
            echo "<p class='success'>✓ ApiSender is using database settings</p>";
        } else {
            echo "<p class='warning'>⚠ ApiSender domain doesn't match database. Using fallback config.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error loading ApiSender: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check 5: Settings Page Access
echo "<h2>5. Settings Page Access</h2>";
if (file_exists(__DIR__ . '/views/settings.php')) {
    echo "<p class='success'>✓ Settings page exists</p>";
    echo "<div class='info'>";
    echo "<strong>Access the Settings Page:</strong><br>";
    echo "<a href='/ScrapingToolsAutoSync/views/settings.php' target='_blank'>Open Settings Page</a>";
    echo "</div>";
} else {
    echo "<p class='error'>✗ Settings page not found</p>";
}

// Summary
echo "<h2>📊 Summary</h2>";
if ($dbConnected && $tableExists && !empty($apiSettings)) {
    echo "<div class='info' style='border-left-color: #28a745; background: #d4edda;'>";
    echo "<p class='success' style='font-size: 18px;'>✓ All checks passed! Your API settings are properly configured.</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Visit the <a href='/ScrapingToolsAutoSync/views/settings.php'>Settings Page</a></li>";
    echo "<li>Update the API Base Domain as needed</li>";
    echo "<li>Save your changes</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='info' style='border-left-color: #dc3545; background: #f8d7da;'>";
    echo "<p class='error' style='font-size: 18px;'>✗ Setup incomplete. Please fix the errors above.</p>";
    echo "<p><strong>Quick Fix:</strong></p>";
    echo "<ol>";
    echo "<li>Run <code>install-api-settings.bat</code> from the project root</li>";
    echo "<li>Refresh this page to verify</li>";
    echo "</ol>";
    echo "</div>";
}

echo "
        <hr style='margin: 30px 0;'>
        <p style='text-align: center; color: #6c757d;'>
            <small>API Settings Verification Tool v1.0 | Last run: " . date('Y-m-d H:i:s') . "</small>
        </p>
    </div>
</body>
</html>";
?>
