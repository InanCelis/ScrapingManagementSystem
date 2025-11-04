<?php
/**
 * Fix/Create Admin User
 * This script ensures the admin user exists with correct credentials
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Admin User</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>body { padding: 2rem; background: #f5f5f5; }</style>
</head>
<body>
<div class='container'>
    <div class='card'>
        <div class='card-header bg-primary text-white'>
            <h3>Fix Admin User</h3>
        </div>
        <div class='card-body'>";

try {
    // Load configuration
    $config = require __DIR__ . '/config/config.php';

    // Connect to database
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['database'],
        $config['database']['charset']
    );

    $pdo = new PDO(
        $dsn,
        $config['database']['username'],
        $config['database']['password'],
        $config['database']['options']
    );

    echo "<p class='text-success'>✓ Connected to database</p>";

    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        echo "<p class='text-danger'>✗ Table 'users' does not exist. Please import database/schema.sql first.</p>";
        echo "</div></div></div></body></html>";
        exit;
    }

    echo "<p class='text-success'>✓ Table 'users' exists</p>";

    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();

    // Generate new password hash
    $newPassword = 'admin123';
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    if ($admin) {
        echo "<p class='text-warning'>⚠ Admin user exists, updating password...</p>";

        // Update admin user
        $stmt = $pdo->prepare("UPDATE users SET password = ?, is_active = 1 WHERE username = ?");
        $stmt->execute([$passwordHash, 'admin']);

        echo "<p class='text-success'>✓ Admin password updated successfully</p>";
    } else {
        echo "<p class='text-warning'>⚠ Admin user does not exist, creating...</p>";

        // Create admin user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, is_active, created_at)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute(['admin', 'admin@scraper.local', $passwordHash, 'System Administrator']);

        echo "<p class='text-success'>✓ Admin user created successfully</p>";
    }

    // Verify the user
    $stmt = $pdo->prepare("SELECT id, username, email, full_name FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $verifyUser = $stmt->fetch();

    echo "<hr>";
    echo "<h5>Admin User Details:</h5>";
    echo "<table class='table table-bordered'>";
    echo "<tr><td><strong>ID</strong></td><td>" . $verifyUser['id'] . "</td></tr>";
    echo "<tr><td><strong>Username</strong></td><td>" . $verifyUser['username'] . "</td></tr>";
    echo "<tr><td><strong>Email</strong></td><td>" . $verifyUser['email'] . "</td></tr>";
    echo "<tr><td><strong>Full Name</strong></td><td>" . $verifyUser['full_name'] . "</td></tr>";
    echo "<tr><td><strong>Password</strong></td><td>admin123</td></tr>";
    echo "</table>";

    // Test password verification
    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $user = $stmt->fetch();

    if (password_verify('admin123', $user['password'])) {
        echo "<p class='text-success'>✓ Password verification test: PASSED</p>";
    } else {
        echo "<p class='text-danger'>✗ Password verification test: FAILED</p>";
    }

    echo "<hr>";
    echo "<div class='alert alert-success'>";
    echo "<h5>✓ All Done!</h5>";
    echo "<p>You can now login with:</p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "</ul>";
    echo "</div>";

    echo "<a href='login.php' class='btn btn-primary btn-lg'>Go to Login Page</a>";

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h5>Database Error</h5>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Make sure:</p>";
    echo "<ol>";
    echo "<li>MySQL is running in XAMPP</li>";
    echo "<li>Database 'scraper_management' exists</li>";
    echo "<li>Tables are imported from database/schema.sql</li>";
    echo "</ol>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "        </div>
    </div>
</div>
</body>
</html>";
?>
