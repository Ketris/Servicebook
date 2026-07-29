<?php
require_once __DIR__ . '/src/config.php';

$config = require __DIR__ . '/src/config.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;charset=%s', $config['db_host'], $config['db_charset']),
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $config['db_name'] . '` CHARACTER SET ' . $config['db_charset']);
    $pdo->exec('USE `' . $config['db_name'] . '`');

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    role ENUM('Administrator', 'Office Staff') NOT NULL DEFAULT 'Office Staff',
    active TINYINT(1) NOT NULL DEFAULT 1,
    failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    lock_until DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS technicians (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(100) DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS service_calls (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_number VARCHAR(16) NOT NULL UNIQUE,
    received_date DATETIME NOT NULL,
    customer VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    contact VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(100) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    po_number VARCHAR(100) DEFAULT NULL,
    reported_issue TEXT NOT NULL,
    internal_notes TEXT DEFAULT NULL,
    assigned_tech INT UNSIGNED DEFAULT NULL,
    status ENUM('New','Dispatched','In Progress','Waiting Parts','On Hold','Complete') NOT NULL DEFAULT 'New',
    priority ENUM('Low','Normal','High','Emergency') NOT NULL DEFAULT 'Normal',
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (assigned_tech),
    INDEX (status),
    FOREIGN KEY (assigned_tech) REFERENCES technicians(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL
    );

    $username = 'admin';
    $password = bin2hex(random_bytes(8));
    $displayName = 'Administrator';
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('SELECT COUNT(*) AS count FROM users WHERE username = :username');
    $stmt->execute([':username' => $username]);
    $exists = $stmt->fetchColumn() > 0;

    if (!$exists) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, password_hash, display_name, role, active, created_at)
             VALUES (:username, :password_hash, :display_name, :role, 1, NOW())'
        );
        $stmt->execute([
            ':username' => $username,
            ':password_hash' => $passwordHash,
            ':display_name' => $displayName,
            ':role' => 'Administrator',
        ]);
    }

    echo '<h1>Installation Completed</h1>';
    echo '<p>The database has been created and the initial administrator account is ready.</p>';
    if (!$exists) {
        echo '<p><strong>Login</strong>: admin<br><strong>Temporary Password</strong>: ' . htmlspecialchars($password, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p>Please sign in and change this password immediately.</p>';
    } else {
        echo '<p>The admin account already exists. Use your existing credentials.</p>';
    }
    echo '<p><a href="public/login.php">Go to login</a></p>';
} catch (PDOException $exception) {
    echo '<h1>Installation Failed</h1>';
    echo '<p>' . htmlspecialchars($exception->getMessage()) . '</p>';
}
