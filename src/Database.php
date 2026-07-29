<?php
class Database
{
    private static ?\PDO $connection = null;

    public static function getConnection(): \PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $config = require __DIR__ . '/config.php';
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s',
            $config['db_host'],
            $config['db_name'],
            $config['db_charset']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        self::$connection = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
        self::ensureSchema();
        return self::$connection;
    }

    public static function ensureSchema(): void
    {
        $pdo = self::$connection;
        if ($pdo === null) {
            return;
        }

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS service_call_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_call_id INT UNSIGNED NOT NULL,
    changed_by_user_id INT UNSIGNED DEFAULT NULL,
    changed_by_name VARCHAR(150) DEFAULT NULL,
    field_name VARCHAR(100) NOT NULL,
    old_value TEXT DEFAULT NULL,
    new_value TEXT DEFAULT NULL,
    note TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (service_call_id),
    INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL
        );

        $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'technician_id'")->fetchAll();
        if (empty($columns)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN technician_id INT UNSIGNED DEFAULT NULL AFTER role');
        }

        $roleColumns = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetchAll();
        if (!empty($roleColumns)) {
            $definition = $roleColumns[0]['Type'] ?? '';
            if (stripos($definition, "'Technician'") === false) {
                $pdo->exec("ALTER TABLE users MODIFY role ENUM('Administrator','Office Staff','Technician') NOT NULL DEFAULT 'Office Staff'");
            }
        }

        $failedAttemptColumns = $pdo->query("SHOW COLUMNS FROM users LIKE 'failed_login_attempts'")->fetchAll();
        if (empty($failedAttemptColumns)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0 AFTER active');
        }

        $lockUntilColumns = $pdo->query("SHOW COLUMNS FROM users LIKE 'lock_until'")->fetchAll();
        if (empty($lockUntilColumns)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN lock_until DATETIME DEFAULT NULL AFTER failed_login_attempts');
        }
    }

    public static function isInstallationMissingException(\PDOException $exception): bool
    {
        $code = (string)$exception->getCode();
        $message = $exception->getMessage();

        return str_contains($message, 'SQLSTATE[HY000] [1049]')
            || $code === '42S02'
            || str_contains($message, 'Base table or view not found');
    }
}
