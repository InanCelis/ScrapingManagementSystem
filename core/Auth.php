<?php
/**
 * Authentication Class
 * Handles user authentication, session management, and authorization
 */

class Auth {
    private Database $db;
    private array $config;
    private ?array $user = null;

    public function __construct() {
        $this->db = Database::getInstance();
        $configFile = __DIR__ . '/../config/config.php';
        $appConfig = require $configFile;
        $this->config = $appConfig['auth'];

        $this->startSession();
        $this->loadUser();
    }

    private function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function loadUser(): void {
        if (isset($_SESSION['user_id'])) {
            $this->user = $this->getUserById($_SESSION['user_id']);
        }
    }

    public function login(string $username, string $password, bool $remember = false): array {
        try {
            // Find user by username or email
            $user = $this->db->fetchOne(
                'SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1',
                [$username, $username]
            );

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Verify password
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $this->user = $user;

            // Update last login timestamp (only if column exists)
            try {
                $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
            } catch (Exception $e) {
                // Ignore if last_login column doesn't exist yet
            }

            // Handle remember me
            if ($remember) {
                $this->createRememberToken($user['id']);
            }

            // Log activity
            $this->logActivity($user['id'], 'login', 'user', $user['id'], 'User logged in');

            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $this->sanitizeUser($user)
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    public function logout(): void {
        if ($this->user) {
            $this->logActivity($this->user['id'], 'logout', 'user', $this->user['id'], 'User logged out');
        }

        // Clear remember me token
        if (isset($_COOKIE['remember_token'])) {
            $this->db->delete('user_sessions', 'session_token = ?', [$_COOKIE['remember_token']]);
            setcookie('remember_token', '', time() - 3600, '/');
        }

        session_destroy();
        $this->user = null;
    }

    public function register(array $data): array {
        try {
            // Validate required fields
            $required = ['username', 'email', 'password'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => ucfirst($field) . ' is required'];
                }
            }

            // Validate password length
            if (strlen($data['password']) < $this->config['password_min_length']) {
                return [
                    'success' => false,
                    'message' => 'Password must be at least ' . $this->config['password_min_length'] . ' characters'
                ];
            }

            // Check if username exists
            $existing = $this->db->fetchOne('SELECT id FROM users WHERE username = ?', [$data['username']]);
            if ($existing) {
                return ['success' => false, 'message' => 'Username already exists'];
            }

            // Check if email exists
            $existing = $this->db->fetchOne('SELECT id FROM users WHERE email = ?', [$data['email']]);
            if ($existing) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // Insert user
            $userId = $this->db->insert('users', [
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'full_name' => $data['full_name'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    private function createRememberToken(int $userId): void {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $this->config['remember_me_lifetime']);

        $this->db->insert('user_sessions', [
            'user_id' => $userId,
            'session_token' => $token,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'expires_at' => $expiresAt,
        ]);

        setcookie('remember_token', $token, time() + $this->config['remember_me_lifetime'], '/');
    }

    public function checkRememberMe(): bool {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }

        $session = $this->db->fetchOne(
            'SELECT * FROM user_sessions WHERE session_token = ? AND expires_at > NOW()',
            [$_COOKIE['remember_token']]
        );

        if (!$session) {
            return false;
        }

        $user = $this->getUserById($session['user_id']);
        if (!$user) {
            return false;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $this->user = $user;

        return true;
    }

    public function check(): bool {
        return $this->user !== null;
    }

    public function user(): ?array {
        return $this->user ? $this->sanitizeUser($this->user) : null;
    }

    public function getUserById(int $id): ?array {
        return $this->db->fetchOne('SELECT * FROM users WHERE id = ? AND is_active = 1', [$id]);
    }

    private function sanitizeUser(array $user): array {
        unset($user['password']);
        unset($user['remember_token']);
        return $user;
    }

    public function logActivity(int $userId, string $action, string $entityType = null, int $entityId = null, string $description = null): void {
        $this->db->insert('activity_logs', [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    public function requireAuth(): void {
        if (!$this->check()) {
            header('Location: /ScrapingToolsAutoSync/login.php');
            exit;
        }
    }
}
