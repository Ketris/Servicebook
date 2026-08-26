<?php
require_once __DIR__ . '/Database.php';

class User
{
    private const ALLOWED_ROLES = ['Administrator', 'Office Staff', 'Technician'];

    public static function findAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT id, username, display_name, role, is_technician, phone, active, failed_login_attempts, lock_until, created_at FROM users ORDER BY username');
        return $stmt->fetchAll();
    }

    public static function findById(int $id): array|null
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, username, display_name, role, is_technician, phone, active, failed_login_attempts, lock_until FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Technicians assignable to service calls; used by dashboards and call forms.
    public static function findAllActiveTechnicians(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, display_name AS name, phone FROM users WHERE is_technician = 1 AND active = 1 ORDER BY display_name");
        return $stmt->fetchAll();
    }

    public static function save(array $data, int|null $id = null): int
    {
        $pdo = Database::getConnection();
        $role = $data['role'] ?? 'Office Staff';
        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new InvalidArgumentException('Invalid role selected.');
        }

        // The Technician role always implies the technician flag; the flag can also be set independently.
        $isTechnician = (!empty($data['is_technician']) || $role === 'Technician') ? 1 : 0;
        $phone = trim((string)($data['phone'] ?? ''));
        $phone = $phone !== '' ? $phone : null;

        if ($id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, password_hash, display_name, role, is_technician, phone, active, created_at)
                 VALUES (:username, :password_hash, :display_name, :role, :is_technician, :phone, :active, NOW())'
            );
            $stmt->execute([
                ':username' => $data['username'],
                ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':display_name' => $data['display_name'],
                ':role' => $role,
                ':is_technician' => $isTechnician,
                ':phone' => $phone,
                ':active' => $data['active'],
            ]);
            return (int)$pdo->lastInsertId();
        }

        $params = [
            ':display_name' => $data['display_name'],
            ':role' => $role,
            ':is_technician' => $isTechnician,
            ':phone' => $phone,
            ':active' => $data['active'],
            ':id' => $id,
        ];

        $sql = 'UPDATE users SET display_name = :display_name, role = :role, is_technician = :is_technician, phone = :phone, active = :active';

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
