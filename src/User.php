<?php
require_once __DIR__ . '/Database.php';

class User
{
    private const ALLOWED_ROLES = ['Administrator', 'Office Staff', 'Technician'];

    public static function findAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT id, username, display_name, role, technician_id, active, failed_login_attempts, lock_until, created_at FROM users ORDER BY username');
        return $stmt->fetchAll();
    }

    public static function findById(int $id): array|null
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, username, display_name, role, technician_id, active, failed_login_attempts, lock_until FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function save(array $data, int|null $id = null): int
    {
        $pdo = Database::getConnection();
        $role = $data['role'] ?? 'Office Staff';
        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new InvalidArgumentException('Invalid role selected.');
        }

        $technicianId = !empty($data['technician_id']) ? (int)$data['technician_id'] : null;

        if ($id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, password_hash, display_name, role, technician_id, active, created_at)
                 VALUES (:username, :password_hash, :display_name, :role, :technician_id, :active, NOW())'
            );
            $stmt->execute([
                ':username' => $data['username'],
                ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':display_name' => $data['display_name'],
                ':role' => $role,
                ':technician_id' => $technicianId,
                ':active' => $data['active'],
            ]);
            return (int)$pdo->lastInsertId();
        }

        $params = [
            ':display_name' => $data['display_name'],
            ':role' => $role,
            ':technician_id' => $technicianId,
            ':active' => $data['active'],
            ':id' => $id,
        ];

        $sql = 'UPDATE users SET display_name = :display_name, role = :role, technician_id = :technician_id, active = :active';

        if (!empty($data['password'])) {
            $sql .= ', password_hash = :password_hash';
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id = :id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $id;
    }

    public static function usernameExists(string $username, int|null $excludeId = null): bool
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT COUNT(*) FROM users WHERE username = :username';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :excludeId';
        }
        $stmt = $pdo->prepare($sql);
        $params = [':username' => $username];
        if ($excludeId !== null) {
            $params[':excludeId'] = $excludeId;
        }
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function clearLockout(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET failed_login_attempts = 0, lock_until = NULL WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function resetPassword(int $id): ?string
    {
        $user = self::findById($id);
        if (!$user) {
            return null;
        }

        $temporaryPassword = bin2hex(random_bytes(6));
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE users
             SET password_hash = :password_hash,
                 failed_login_attempts = 0,
                 lock_until = NULL
             WHERE id = :id'
        );
        $stmt->execute([
            ':password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
            ':id' => $id,
        ]);

        return $temporaryPassword;
    }
}
