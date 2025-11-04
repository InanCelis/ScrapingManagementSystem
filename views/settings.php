<?php
session_start();
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

$auth = new Auth();
$auth->requireAuth();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

$pageTitle = 'System Settings';
$success = '';
$error = '';

// Load current settings from database (priority) or config file (fallback)
$configFile = __DIR__ . '/../config/config.php';
$currentSettings = [];
$apiSettings = [];

// Load API settings from database
try {
    $apiSettingsDb = $db->fetchAll("SELECT setting_key, setting_value FROM system_settings WHERE category = 'api'");
    foreach ($apiSettingsDb as $setting) {
        $apiSettings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    // Table might not exist yet, ignore
    $apiSettings = [];
}

// Set defaults if not in database
if (empty($apiSettings)) {
    $config = file_exists($configFile) ? include $configFile : [];
    $apiSettings = [
        'api_base_domain' => $config['api']['base_domain'] ?? 'https://internationalpropertyalerts.com',
        'api_token' => $config['api']['token'] ?? '',
        'api_max_retries' => $config['api']['max_retries'] ?? 3,
        'api_timeout' => $config['api']['timeout'] ?? 600,
        'api_connect_timeout' => $config['api']['connect_timeout'] ?? 60,
        'api_debug' => $config['api']['debug'] ?? false
    ];
}

if (file_exists($configFile)) {
    $config = include $configFile;

    // Handle both array config and define() style config
    if (is_array($config)) {
        $currentSettings = [
            'db_host' => $config['database']['host'] ?? 'localhost',
            'db_name' => $config['database']['database'] ?? '',
            'db_user' => $config['database']['username'] ?? 'root',
            'db_pass' => $config['database']['password'] ?? '',
            'app_name' => $config['app']['name'] ?? 'Scraper Manager',
            'timezone' => $config['app']['timezone'] ?? 'UTC',
            'items_per_page' => 10, // Not in config yet
            'enable_notifications' => true, // Not in config yet
            'max_concurrent_scrapers' => $config['scraper']['max_concurrent_processes'] ?? 5,
            'default_timeout' => $config['scraper']['default_timeout'] ?? 300,
            'log_retention_days' => $config['scraper']['log_retention_days'] ?? 30
        ];
    } else {
        // Fallback to constants if config returns something else
        $currentSettings = [
            'db_host' => defined('DB_HOST') ? DB_HOST : 'localhost',
            'db_name' => defined('DB_NAME') ? DB_NAME : '',
            'app_name' => defined('APP_NAME') ? APP_NAME : 'Scraper Manager',
            'timezone' => defined('TIMEZONE') ? TIMEZONE : 'UTC',
            'items_per_page' => defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 10,
            'enable_notifications' => defined('ENABLE_NOTIFICATIONS') ? ENABLE_NOTIFICATIONS : true,
            'max_concurrent_scrapers' => defined('MAX_CONCURRENT_SCRAPERS') ? MAX_CONCURRENT_SCRAPERS : 5,
            'default_timeout' => defined('DEFAULT_TIMEOUT') ? DEFAULT_TIMEOUT : 300,
            'log_retention_days' => defined('LOG_RETENTION_DAYS') ? LOG_RETENTION_DAYS : 30
        ];
    }
} else {
    // Default settings if config file doesn't exist
    $currentSettings = [
        'db_host' => 'localhost',
        'db_name' => 'scraper_management',
        'app_name' => 'Scraper Manager',
        'timezone' => 'UTC',
        'items_per_page' => 10,
        'enable_notifications' => true,
        'max_concurrent_scrapers' => 5,
        'default_timeout' => 300,
        'log_retention_days' => 30
    ];
}

// Handle API settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_api_settings') {
    $apiBaseDomain = trim($_POST['api_base_domain'] ?? '');
    $apiToken = trim($_POST['api_token'] ?? '');
    $apiMaxRetries = (int)($_POST['api_max_retries'] ?? 3);
    $apiTimeout = (int)($_POST['api_timeout'] ?? 600);
    $apiConnectTimeout = (int)($_POST['api_connect_timeout'] ?? 60);
    $apiDebug = isset($_POST['api_debug']) ? 1 : 0;

    // Validate
    if (empty($apiBaseDomain)) {
        $error = 'API Base Domain is required.';
    } elseif (!filter_var($apiBaseDomain, FILTER_VALIDATE_URL)) {
        $error = 'API Base Domain must be a valid URL.';
    } elseif ($apiMaxRetries < 1 || $apiMaxRetries > 10) {
        $error = 'Max retries must be between 1 and 10.';
    } elseif ($apiTimeout < 30 || $apiTimeout > 3600) {
        $error = 'API timeout must be between 30 and 3600 seconds.';
    } elseif ($apiConnectTimeout < 5 || $apiConnectTimeout > 300) {
        $error = 'Connect timeout must be between 5 and 300 seconds.';
    } else {
        try {
            // Update or insert API settings
            $apiSettingsData = [
                'api_base_domain' => $apiBaseDomain,
                'api_token' => $apiToken,
                'api_max_retries' => $apiMaxRetries,
                'api_timeout' => $apiTimeout,
                'api_connect_timeout' => $apiConnectTimeout,
                'api_debug' => $apiDebug
            ];

            foreach ($apiSettingsData as $key => $value) {
                $existing = $db->fetchOne("SELECT id FROM system_settings WHERE setting_key = ?", [$key]);

                if ($existing) {
                    $db->update('system_settings', [
                        'setting_value' => (string)$value,
                        'updated_by' => $userId
                    ], 'setting_key = ?', [$key]);
                } else {
                    $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string');
                    $db->insert('system_settings', [
                        'setting_key' => $key,
                        'setting_value' => (string)$value,
                        'setting_type' => $type,
                        'category' => 'api',
                        'updated_by' => $userId
                    ]);
                }
            }

            // Log activity
            $db->insert('activity_logs', [
                'user_id' => $userId,
                'action' => 'api_settings_updated',
                'description' => 'Updated API settings',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            $success = 'API settings updated successfully!';
            $apiSettings = $apiSettingsData;
        } catch (Exception $e) {
            $error = 'Failed to update API settings: ' . $e->getMessage();
        }
    }
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    $appName = trim($_POST['app_name'] ?? '');
    $timezone = trim($_POST['timezone'] ?? 'UTC');
    $itemsPerPage = (int)($_POST['items_per_page'] ?? 10);
    $enableNotifications = isset($_POST['enable_notifications']) ? 1 : 0;
    $maxConcurrent = (int)($_POST['max_concurrent_scrapers'] ?? 5);
    $defaultTimeout = (int)($_POST['default_timeout'] ?? 300);
    $logRetention = (int)($_POST['log_retention_days'] ?? 30);

    // Validate
    if (empty($appName)) {
        $error = 'Application name is required.';
    } elseif ($itemsPerPage < 5 || $itemsPerPage > 100) {
        $error = 'Items per page must be between 5 and 100.';
    } elseif ($maxConcurrent < 1 || $maxConcurrent > 20) {
        $error = 'Max concurrent scrapers must be between 1 and 20.';
    } elseif ($defaultTimeout < 30 || $defaultTimeout > 3600) {
        $error = 'Default timeout must be between 30 and 3600 seconds.';
    } elseif ($logRetention < 1 || $logRetention > 365) {
        $error = 'Log retention must be between 1 and 365 days.';
    } else {
        // Build new config content
        $configContent = "<?php\n";
        $configContent .= "// Database Configuration\n";
        $configContent .= "define('DB_HOST', '{$currentSettings['db_host']}');\n";
        $configContent .= "define('DB_NAME', '{$currentSettings['db_name']}');\n";
        $configContent .= "define('DB_USER', '{$currentSettings['db_user']}');\n";
        $configContent .= "define('DB_PASS', '{$currentSettings['db_pass']}');\n\n";
        $configContent .= "// Application Settings\n";
        $configContent .= "define('APP_NAME', '" . addslashes($appName) . "');\n";
        $configContent .= "define('TIMEZONE', '{$timezone}');\n";
        $configContent .= "define('ITEMS_PER_PAGE', {$itemsPerPage});\n";
        $configContent .= "define('ENABLE_NOTIFICATIONS', " . ($enableNotifications ? 'true' : 'false') . ");\n\n";
        $configContent .= "// Scraper Settings\n";
        $configContent .= "define('MAX_CONCURRENT_SCRAPERS', {$maxConcurrent});\n";
        $configContent .= "define('DEFAULT_TIMEOUT', {$defaultTimeout});\n";
        $configContent .= "define('LOG_RETENTION_DAYS', {$logRetention});\n";

        // Try to write config file
        $configDir = __DIR__ . '/../config';
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        if (file_put_contents($configFile, $configContent)) {
            // Log activity
            $db->insert('activity_logs', [
                'user_id' => $userId,
                'action' => 'settings_updated',
                'description' => 'Updated system settings',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            $success = 'Settings updated successfully!';
            $currentSettings = [
                'db_host' => $currentSettings['db_host'],
                'db_name' => $currentSettings['db_name'],
                'app_name' => $appName,
                'timezone' => $timezone,
                'items_per_page' => $itemsPerPage,
                'enable_notifications' => $enableNotifications,
                'max_concurrent_scrapers' => $maxConcurrent,
                'default_timeout' => $defaultTimeout,
                'log_retention_days' => $logRetention
            ];
        } else {
            $error = 'Failed to write configuration file. Check file permissions.';
        }
    }
}

// Handle cleanup old logs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanup_logs') {
    $days = (int)($_POST['cleanup_days'] ?? 30);

    try {
        $result = $db->query(
            "DELETE FROM scraper_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );

        $db->insert('activity_logs', [
            'user_id' => $userId,
            'action' => 'logs_cleaned',
            'description' => "Cleaned logs older than {$days} days",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        $success = 'Old logs cleaned successfully!';
    } catch (Exception $e) {
        $error = 'Failed to clean logs: ' . $e->getMessage();
    }
}

// Get database statistics
$dbStats = [
    'configs' => $db->fetchOne('SELECT COUNT(*) as count FROM scraper_configs')['count'],
    'processes' => $db->fetchOne('SELECT COUNT(*) as count FROM scraper_processes')['count'],
    'logs' => $db->fetchOne('SELECT COUNT(*) as count FROM scraper_logs')['count'],
    'activity_logs' => $db->fetchOne('SELECT COUNT(*) as count FROM activity_logs')['count'],
    'users' => $db->fetchOne('SELECT COUNT(*) as count FROM users')['count']
];

// Get disk usage (if possible)
$diskUsage = [
    'total' => disk_total_space('.'),
    'free' => disk_free_space('.'),
    'used' => disk_total_space('.') - disk_free_space('.')
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-fill">
        <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- API Settings -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="fas fa-plug me-2"></i>API Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_api_settings">

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-globe me-1"></i>API Base Domain <span class="text-danger">*</span></label>
                                    <input type="url" name="api_base_domain" class="form-control"
                                           value="<?php echo htmlspecialchars($apiSettings['api_base_domain'] ?? 'https://internationalpropertyalerts.com'); ?>"
                                           placeholder="https://example.com" required>
                                    <small class="text-muted">The base URL for all API requests (e.g., https://internationalpropertyalerts.com)</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-key me-1"></i>API Token</label>
                                    <div class="input-group">
                                        <input type="password" name="api_token" id="apiToken" class="form-control"
                                               value="<?php echo htmlspecialchars($apiSettings['api_token'] ?? ''); ?>"
                                               placeholder="Enter API authentication token">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleToken">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Authentication token for API requests</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label"><i class="fas fa-redo me-1"></i>Max Retries</label>
                                        <input type="number" name="api_max_retries" class="form-control"
                                               value="<?php echo $apiSettings['api_max_retries'] ?? 3; ?>"
                                               min="1" max="10" required>
                                        <small class="text-muted">1-10 attempts</small>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label"><i class="fas fa-clock me-1"></i>Timeout (sec)</label>
                                        <input type="number" name="api_timeout" class="form-control"
                                               value="<?php echo $apiSettings['api_timeout'] ?? 600; ?>"
                                               min="30" max="3600" required>
                                        <small class="text-muted">30-3600 seconds</small>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label"><i class="fas fa-link me-1"></i>Connect Timeout</label>
                                        <input type="number" name="api_connect_timeout" class="form-control"
                                               value="<?php echo $apiSettings['api_connect_timeout'] ?? 60; ?>"
                                               min="5" max="300" required>
                                        <small class="text-muted">5-300 seconds</small>
                                    </div>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" name="api_debug" class="form-check-input" id="apiDebug"
                                           <?php echo (!empty($apiSettings['api_debug']) && $apiSettings['api_debug'] != '0') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="apiDebug">
                                        <i class="fas fa-bug me-1"></i>Enable Debug Mode
                                    </label>
                                    <div><small class="text-muted">Log detailed API request/response information</small></div>
                                </div>

                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-dark">
                                        <i class="fas fa-save me-2"></i>Save API Settings
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                        <i class="fas fa-undo me-2"></i>Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Application Settings -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Application Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_settings">

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-tag me-1"></i>Application Name</label>
                                    <input type="text" name="app_name" class="form-control"
                                           value="<?php echo htmlspecialchars($currentSettings['app_name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-clock me-1"></i>Timezone</label>
                                    <select name="timezone" class="form-select">
                                        <?php
                                        $timezones = ['UTC', 'America/New_York', 'America/Chicago', 'America/Los_Angeles',
                                                     'Europe/London', 'Europe/Paris', 'Asia/Tokyo', 'Asia/Dubai',
                                                     'Australia/Sydney', 'Pacific/Auckland'];
                                        foreach ($timezones as $tz) {
                                            $selected = $currentSettings['timezone'] === $tz ? 'selected' : '';
                                            echo "<option value=\"{$tz}\" {$selected}>{$tz}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-list me-1"></i>Items Per Page</label>
                                    <input type="number" name="items_per_page" class="form-control"
                                           value="<?php echo $currentSettings['items_per_page']; ?>"
                                           min="5" max="100" required>
                                    <small class="text-muted">Number of items to display per page (5-100)</small>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" name="enable_notifications" class="form-check-input"
                                           id="enableNotifications"
                                           <?php echo $currentSettings['enable_notifications'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enableNotifications">
                                        Enable Notifications
                                    </label>
                                </div>

                                <hr class="my-4">
                                <h6 class="mb-3"><i class="fas fa-robot me-2"></i>Scraper Settings</h6>

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-layer-group me-1"></i>Max Concurrent Scrapers</label>
                                    <input type="number" name="max_concurrent_scrapers" class="form-control"
                                           value="<?php echo $currentSettings['max_concurrent_scrapers']; ?>"
                                           min="1" max="20" required>
                                    <small class="text-muted">Maximum number of scrapers that can run simultaneously (1-20)</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-hourglass-half me-1"></i>Default Timeout (seconds)</label>
                                    <input type="number" name="default_timeout" class="form-control"
                                           value="<?php echo $currentSettings['default_timeout']; ?>"
                                           min="30" max="3600" required>
                                    <small class="text-muted">Default timeout for scraper operations (30-3600 seconds)</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-calendar me-1"></i>Log Retention (days)</label>
                                    <input type="number" name="log_retention_days" class="form-control"
                                           value="<?php echo $currentSettings['log_retention_days']; ?>"
                                           min="1" max="365" required>
                                    <small class="text-muted">Number of days to keep scraper logs (1-365 days)</small>
                                </div>

                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Settings
                                    </button>
                                    <a href="/ScrapingToolsAutoSync/dashboard" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Maintenance -->
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Maintenance</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-3">Clean Old Logs</h6>
                            <p class="text-muted">Remove scraper logs older than specified number of days to free up database space.</p>

                            <form method="POST" action="" class="d-flex gap-2 align-items-end">
                                <input type="hidden" name="action" value="cleanup_logs">
                                <div class="flex-grow-1">
                                    <label class="form-label">Remove logs older than (days)</label>
                                    <input type="number" name="cleanup_days" class="form-control" value="30" min="1" max="365">
                                </div>
                                <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to delete old logs? This cannot be undone.')">
                                    <i class="fas fa-trash me-2"></i>Clean Logs
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>System Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="text-muted small">PHP Version</label>
                                <p class="mb-0 fw-bold"><?php echo PHP_VERSION; ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small">Database Host</label>
                                <p class="mb-0 fw-bold"><?php echo htmlspecialchars($currentSettings['db_host']); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small">Database Name</label>
                                <p class="mb-0 fw-bold"><?php echo htmlspecialchars($currentSettings['db_name']); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small">Server Software</label>
                                <p class="mb-0 fw-bold"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                            </div>
                            <div class="mb-0">
                                <label class="text-muted small">Document Root</label>
                                <p class="mb-0 fw-bold small text-break"><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-database me-2"></i>Database Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Configurations:</span>
                                <strong><?php echo number_format($dbStats['configs']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Process Runs:</span>
                                <strong><?php echo number_format($dbStats['processes']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Scraper Logs:</span>
                                <strong><?php echo number_format($dbStats['logs']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Activity Logs:</span>
                                <strong><?php echo number_format($dbStats['activity_logs']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Users:</span>
                                <strong><?php echo number_format($dbStats['users']); ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-hdd me-2"></i>Disk Usage</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small">Used</span>
                                    <strong class="small"><?php echo number_format($diskUsage['used'] / 1024 / 1024 / 1024, 2); ?> GB</strong>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-primary"
                                         style="width: <?php echo ($diskUsage['used'] / $diskUsage['total']) * 100; ?>%">
                                        <?php echo number_format(($diskUsage['used'] / $diskUsage['total']) * 100, 1); ?>%
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-2 small text-muted">
                                <span>Total: <?php echo number_format($diskUsage['total'] / 1024 / 1024 / 1024, 2); ?> GB</span>
                                <span>Free: <?php echo number_format($diskUsage['free'] / 1024 / 1024 / 1024, 2); ?> GB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>

<script>
// Toggle API Token visibility
document.addEventListener('DOMContentLoaded', function() {
    const toggleButton = document.getElementById('toggleToken');
    if (toggleButton) {
        toggleButton.addEventListener('click', function() {
            const tokenInput = document.getElementById('apiToken');
            const icon = this.querySelector('i');

            if (tokenInput.type === 'password') {
                tokenInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                tokenInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }
});
</script>
