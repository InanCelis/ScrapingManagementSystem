<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Debug Information</h1>";

// Test 1: Basic PHP
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Test 2: File existence
echo "<h2>2. File Check</h2>";
$files = [
    'config/config.php',
    'core/Database.php',
    'core/Auth.php',
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    echo "$file: " . (file_exists($path) ? '✓ EXISTS' : '✗ MISSING') . "<br>";
}

// Test 3: Try to load config
echo "<h2>3. Configuration Test</h2>";
try {
    if (file_exists(__DIR__ . '/config/config.php')) {
        $config = require __DIR__ . '/config/config.php';
        echo "✓ Config loaded successfully<br>";
        echo "Database: " . $config['database']['database'] . "<br>";
    } else {
        echo "✗ Config file not found<br>";
    }
} catch (Exception $e) {
    echo "✗ Error loading config: " . $e->getMessage() . "<br>";
}

// Test 4: Try to load Database class
echo "<h2>4. Database Class Test</h2>";
try {
    require_once __DIR__ . '/core/Database.php';
    echo "✓ Database.php loaded<br>";
} catch (Exception $e) {
    echo "✗ Error loading Database.php: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

// Test 5: Try to load Auth class
echo "<h2>5. Auth Class Test</h2>";
try {
    require_once __DIR__ . '/core/Auth.php';
    echo "✓ Auth.php loaded<br>";
} catch (Exception $e) {
    echo "✗ Error loading Auth.php: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

// Test 6: Session test
echo "<h2>6. Session Test</h2>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "✓ Session started<br>";
    } else {
        echo "✓ Session already active<br>";
    }
} catch (Exception $e) {
    echo "✗ Session error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>Recommendations</h2>";
echo "<ol>";
echo "<li><a href='test.php'>Check PHP Info</a></li>";
echo "<li><a href='check.php'>Installation Check</a></li>";
echo "<li>Check Apache error log: <code>c:\\xampp\\apache\\logs\\error.log</code></li>";
echo "</ol>";
?>
