<?php
session_start();
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

$auth = new Auth();
$auth->requireAuth();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Fetch user data
$user = $db->fetchOne('SELECT * FROM users WHERE id = ?', [$userId]);

$pageTitle = 'My Profile';
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($fullName) || empty($email)) {
        $error = 'Full name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check if email is already taken by another user
        $existingUser = $db->fetchOne('SELECT id FROM users WHERE email = ? AND id != ?', [$email, $userId]);
        if ($existingUser) {
            $error = 'Email is already taken by another user.';
        } else {
            // Update profile information
            $updateData = [
                'full_name' => $fullName,
                'email' => $email
            ];

            // Handle password change if provided
            if (!empty($newPassword)) {
                if (empty($currentPassword)) {
                    $error = 'Current password is required to change password.';
                } elseif (!password_verify($currentPassword, $user['password'])) {
                    $error = 'Current password is incorrect.';
                } elseif (strlen($newPassword) < 6) {
                    $error = 'New password must be at least 6 characters.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'New passwords do not match.';
                } else {
                    $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                }
            }

            if (empty($error)) {
                try {
                    $db->update('users', $updateData, 'id = ?', [$userId]);

                    // Log activity
                    $db->insert('activity_logs', [
                        'user_id' => $userId,
                        'action' => 'profile_updated',
                        'description' => 'Updated profile information',
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);

                    $success = 'Profile updated successfully!';

                    // Refresh user data
                    $user = $db->fetchOne('SELECT * FROM users WHERE id = ?', [$userId]);
                } catch (Exception $e) {
                    $error = 'Failed to update profile: ' . $e->getMessage();
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-fill">
        <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

        <div class="content-wrapper">
            <div class="row">
                <div class="col-lg-8 mx-auto">
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

                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="fas fa-user me-1"></i>Username</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                        <small class="text-muted">Username cannot be changed</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="fas fa-id-card me-1"></i>Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-envelope me-1"></i>Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>

                                <hr class="my-4">

                                <h6 class="mb-3"><i class="fas fa-key me-2"></i>Change Password</h6>
                                <p class="text-muted small">Leave blank if you don't want to change your password</p>

                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" autocomplete="current-password">
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control" autocomplete="new-password">
                                        <small class="text-muted">Minimum 6 characters</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" name="confirm_password" class="form-control" autocomplete="new-password">
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="mb-3">
                                    <label class="form-label text-muted">Account Created</label>
                                    <p class="mb-0"><?php echo date('F j, Y, g:i a', strtotime($user['created_at'])); ?></p>
                                </div>

                                <?php if (isset($user['last_login']) && $user['last_login']): ?>
                                <div class="mb-3">
                                    <label class="form-label text-muted">Last Login</label>
                                    <p class="mb-0"><?php echo date('F j, Y, g:i a', strtotime($user['last_login'])); ?></p>
                                </div>
                                <?php endif; ?>

                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                    <a href="/ScrapingToolsAutoSync/dashboard" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Account Statistics -->
                    <div class="card mt-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Account Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="p-3">
                                        <i class="fas fa-cog fa-2x text-primary mb-2"></i>
                                        <h4 class="mb-0"><?php
                                            $configCount = $db->fetchOne('SELECT COUNT(*) as count FROM scraper_configs WHERE created_by = ?', [$userId]);
                                            echo $configCount['count'];
                                        ?></h4>
                                        <p class="text-muted mb-0">Configurations</p>
                                    </div>
                                </div>
                                <div class="col-md-4 border-start border-end">
                                    <div class="p-3">
                                        <i class="fas fa-play-circle fa-2x text-success mb-2"></i>
                                        <h4 class="mb-0"><?php
                                            $processCount = $db->fetchOne('
                                                SELECT COUNT(*) as count
                                                FROM scraper_processes sp
                                                JOIN scraper_configs sc ON sp.config_id = sc.id
                                                WHERE sc.created_by = ?
                                            ', [$userId]);
                                            echo $processCount['count'];
                                        ?></h4>
                                        <p class="text-muted mb-0">Total Runs</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3">
                                        <i class="fas fa-database fa-2x text-info mb-2"></i>
                                        <h4 class="mb-0"><?php
                                            $itemCount = $db->fetchOne('
                                                SELECT SUM(sp.items_created + sp.items_updated) as total
                                                FROM scraper_processes sp
                                                JOIN scraper_configs sc ON sp.config_id = sc.id
                                                WHERE sc.created_by = ?
                                            ', [$userId]);
                                            echo number_format($itemCount['total'] ?? 0);
                                        ?></h4>
                                        <p class="text-muted mb-0">Items Scraped</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
