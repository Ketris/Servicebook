<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(string $username, string $password): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, display_name, role, technician_id, active FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user || !$user['active']) {
            return false;
        }

        if (password_verify($password, $user['password_hash'])) {
            self::start();
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'role' => $user['role'],
                'technician_id' => $user['technician_id'] ?? null,
            ];
            return true;
        }

        return false;
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
