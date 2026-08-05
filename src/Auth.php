<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Logger.php';

class Auth
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_MINUTES = 15;
    private static string $lastError = '';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
            $isHttps = $isHttps || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

            ini_set('session.use_strict_mode', '1');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function login(string $username, string $password): bool
    {
        self::$lastError = '';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, display_name, role, technician_id, active, failed_login_attempts, lock_until FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user || !$user['active']) {
            self::$lastError = 'Invalid username or password.';
            Logger::warning('Login failed for unknown or inactive user', [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
            return false;
        }

        if (!empty($user['lock_until']) && strtotime((string)$user['lock_until']) > time()) {
            self::$lastError = 'Too many failed login attempts. Try again in 15 minutes.';
            Logger::warning('Login blocked due to lockout', [
                'user_id' => (int)$user['id'],
                'username' => (string)$user['username'],
                'lock_until' => (string)$user['lock_until'],
            ]);
            return false;
        }

        if (password_verify($password, $user['password_hash'])) {
            self::start();
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'role' => $user['role'],
                'technician_id' => $user['technician_id'] ?? null,
            ];

            $resetStmt = $pdo->prepare('UPDATE users SET failed_login_attempts = 0, lock_until = NULL WHERE id = :id');
            $resetStmt->execute([':id' => $user['id']]);

            Logger::info('Login success', [
                'user_id' => (int)$user['id'],
                'username' => (string)$user['username'],
                'role' => (string)$user['role'],
            ]);

            return true;
        }

        $attempts = ((int)($user['failed_login_attempts'] ?? 0)) + 1;
        $lockUntil = null;
        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $attempts = self::MAX_LOGIN_ATTEMPTS;
            $lockUntil = date('Y-m-d H:i:s', strtotime('+' . self::LOGIN_LOCKOUT_MINUTES . ' minutes'));
            self::$lastError = 'Too many failed login attempts. Try again in 15 minutes.';
            Logger::warning('Account locked due to failed login attempts', [
                'user_id' => (int)$user['id'],
                'username' => (string)$user['username'],
                'lock_until' => $lockUntil,
            ]);
        } else {
            self::$lastError = 'Invalid username or password.';
            Logger::warning('Login failed', [
                'user_id' => (int)$user['id'],
                'username' => (string)$user['username'],
                'attempts' => $attempts,
            ]);
        }

        $failStmt = $pdo->prepare('UPDATE users SET failed_login_attempts = :attempts, lock_until = :lock_until WHERE id = :id');
        $failStmt->bindValue(':attempts', $attempts, PDO::PARAM_INT);
        $failStmt->bindValue(':lock_until', $lockUntil, $lockUntil === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $failStmt->bindValue(':id', (int)$user['id'], PDO::PARAM_INT);
        $failStmt->execute();

        return false;
    }

    public static function lastError(): string
    {
        return self::$lastError;
    }

    public static function requireLogin(): void
    {
        self::start();
        if (empty($_SESSION['user'])) {
            header('Location: ' . url('public/login.php'));
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        $user = self::currentUser();
        if (!$user || $user['role'] !== 'Administrator') {
            header('Location: ' . url('public/index.php'));
            exit;
        }
    }

    public static function logout(): void
    {
        self::start();
        $current = $_SESSION['user'] ?? null;
        if (is_array($current)) {
            Logger::info('Logout', [
                'user_id' => $current['id'] ?? null,
                'username' => $current['username'] ?? null,
            ]);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public static function currentUser(): array|null
    {
        self::start();
        return $_SESSION['user'] ?? null;
    }
}
