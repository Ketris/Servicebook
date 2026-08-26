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
    service_call_id INT UNSIGNED DEFAULT NULL,
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

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS customer_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_key VARCHAR(255) NOT NULL UNIQUE,
    customer_name VARCHAR(255) NOT NULL,
    default_contact VARCHAR(150) DEFAULT NULL,
    default_phone VARCHAR(100) DEFAULT NULL,
    default_email VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (customer_name),
    INDEX (last_used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS location_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_key VARCHAR(255) NOT NULL UNIQUE,
    location_name VARCHAR(255) NOT NULL,
    customer_record_id INT UNSIGNED DEFAULT NULL,
    default_contact VARCHAR(150) DEFAULT NULL,
    default_phone VARCHAR(100) DEFAULT NULL,
    default_email VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (location_name),
    INDEX (customer_record_id),
    INDEX (last_used_at),
    FOREIGN KEY (customer_record_id) REFERENCES customer_records(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS saved_views (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    view_name VARCHAR(100) NOT NULL,
    page_context VARCHAR(50) NOT NULL,
    search_term VARCHAR(120) DEFAULT NULL,
    filter_value VARCHAR(60) DEFAULT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    role_scope ENUM('Administrator','Office Staff','Technician') DEFAULT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (page_context),
    INDEX (user_id),
    INDEX (role_scope),
    INDEX (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT UNSIGNED NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, preference_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL
    );

        $preferenceKeyColumns = $pdo->query("SHOW COLUMNS FROM user_preferences LIKE 'preference_key'")->fetchAll();
        if (empty($preferenceKeyColumns)) {
            $pdo->exec(<<<'SQL'
CREATE TABLE user_preferences_migrated (
    user_id INT UNSIGNED NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, preference_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL
            );
            $pdo->exec("INSERT INTO user_preferences_migrated (user_id, preference_key, preference_value)
                        SELECT user_id, 'calls.default_filter', default_filter FROM user_preferences");
            $pdo->exec("INSERT INTO user_preferences_migrated (user_id, preference_key, preference_value)
                        SELECT user_id, 'calls.default_per_page', CAST(default_per_page AS CHAR) FROM user_preferences");
            $pdo->exec('DROP TABLE user_preferences');
            $pdo->exec('RENAME TABLE user_preferences_migrated TO user_preferences');
        }

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

        $historyCallIdColumns = $pdo->query("SHOW COLUMNS FROM service_call_history LIKE 'service_call_id'")->fetchAll();
        if (!empty($historyCallIdColumns)) {
            $isNullable = strtoupper((string)($historyCallIdColumns[0]['Null'] ?? 'NO')) === 'YES';
            if (!$isNullable) {
                $pdo->exec('ALTER TABLE service_call_history MODIFY service_call_id INT UNSIGNED NULL');
            }
        }

        $statusColumns = $pdo->query("SHOW COLUMNS FROM service_calls LIKE 'status'")->fetchAll();
        if (!empty($statusColumns)) {
            $statusDefinition = $statusColumns[0]['Type'] ?? '';
            if (stripos($statusDefinition, "'Cancelled'") === false) {
                $pdo->exec("ALTER TABLE service_calls MODIFY status ENUM('New','Dispatched','In Progress','Waiting Parts','On Hold','Complete','Cancelled') NOT NULL DEFAULT 'New'");
            }
        }

        $priorityColumns = $pdo->query("SHOW COLUMNS FROM service_calls LIKE 'priority'")->fetchAll();
        if (!empty($priorityColumns)) {
            $pdo->exec('ALTER TABLE service_calls DROP COLUMN priority');
        }

        $defaultPrioritySetting = $pdo->query("SELECT COUNT(*) AS setting_count FROM settings WHERE name = 'default_priority'")->fetch();
        if ((int)($defaultPrioritySetting['setting_count'] ?? 0) > 0) {
            $stmt = $pdo->prepare('DELETE FROM settings WHERE name = :name');
            $stmt->execute([':name' => 'default_priority']);
        }

        $isTechnicianColumns = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_technician'")->fetchAll();
        if (empty($isTechnicianColumns)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN is_technician TINYINT(1) NOT NULL DEFAULT 0 AFTER role');
        }

        $userPhoneColumns = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'")->fetchAll();
        if (empty($userPhoneColumns)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN phone VARCHAR(100) DEFAULT NULL AFTER display_name');
        }

        $assignedUserColumns = $pdo->query("SHOW COLUMNS FROM service_calls LIKE 'assigned_user_id'")->fetchAll();
        if (empty($assignedUserColumns)) {
            $pdo->exec('ALTER TABLE service_calls ADD COLUMN assigned_user_id INT UNSIGNED DEFAULT NULL AFTER assigned_tech');
        }

        self::migrateTechniciansIntoUsers($pdo);
    }

    // One-time fold of the standalone technicians table into users (is_technician flag + phone);
    // becomes a no-op once the technicians table no longer exists.
    private static function migrateTechniciansIntoUsers(\PDO $pdo): void
    {
        $techniciansTableExists = $pdo->query("SHOW TABLES LIKE 'technicians'")->fetchAll();
        if (empty($techniciansTableExists)) {
            return;
        }

        $technicianRows = $pdo->query('SELECT id, name, phone, active FROM technicians')->fetchAll();
        $technicianIdToUserId = [];

        foreach ($technicianRows as $technicianRow) {
            $linkedUserStmt = $pdo->prepare('SELECT id FROM users WHERE technician_id = :technician_id LIMIT 1');
            $linkedUserStmt->execute([':technician_id' => $technicianRow['id']]);
            $linkedUser = $linkedUserStmt->fetch();

            if ($linkedUser) {
                $updateUserStmt = $pdo->prepare(
                    'UPDATE users SET is_technician = 1, phone = COALESCE(phone, :phone) WHERE id = :id'
                );
                $updateUserStmt->execute([
                    ':phone' => $technicianRow['phone'],
                    ':id' => $linkedUser['id'],
                ]);
                $technicianIdToUserId[(int)$technicianRow['id']] = (int)$linkedUser['id'];
                continue;
            }

            // No login was ever linked to this technician; create one so past call assignments survive.
            $baseUsername = trim((string)preg_replace('/[^a-z0-9]+/i', '.', trim((string)$technicianRow['name'])), '.');
            $baseUsername = $baseUsername !== '' ? strtolower($baseUsername) : 'technician';
            $candidateUsername = $baseUsername;
            $suffix = 1;
            while (true) {
                $existsStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
                $existsStmt->execute([':username' => $candidateUsername]);
                if ((int)$existsStmt->fetchColumn() === 0) {
                    break;
                }
                $suffix++;
                $candidateUsername = $baseUsername . $suffix;
            }

            $insertUserStmt = $pdo->prepare(
                'INSERT INTO users (username, password_hash, display_name, role, is_technician, phone, active, created_at)
                 VALUES (:username, :password_hash, :display_name, :role, 1, :phone, :active, NOW())'
            );
            $insertUserStmt->execute([
                ':username' => $candidateUsername,
                ':password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                ':display_name' => $technicianRow['name'],
                ':role' => 'Technician',
                ':phone' => $technicianRow['phone'],
                ':active' => $technicianRow['active'],
            ]);
            $technicianIdToUserId[(int)$technicianRow['id']] = (int)$pdo->lastInsertId();
        }

        foreach ($technicianIdToUserId as $technicianId => $userId) {
            $assignStmt = $pdo->prepare(
                'UPDATE service_calls SET assigned_user_id = :user_id WHERE assigned_tech = :technician_id'
            );
            $assignStmt->execute([':user_id' => $userId, ':technician_id' => $technicianId]);
        }

        $fkConstraint = $pdo->query(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'service_calls'
               AND COLUMN_NAME = 'assigned_tech' AND REFERENCED_TABLE_NAME = 'technicians'"
        )->fetch();
        if ($fkConstraint) {
            $pdo->exec('ALTER TABLE service_calls DROP FOREIGN KEY `' . $fkConstraint['CONSTRAINT_NAME'] . '`');
        }

        $pdo->exec('ALTER TABLE service_calls DROP COLUMN assigned_tech');
        $pdo->exec('ALTER TABLE users DROP COLUMN technician_id');
        $pdo->exec('DROP TABLE technicians');
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
