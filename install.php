<?php
require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Logger.php';
require_once __DIR__ . '/src/Auth.php';

apply_security_headers();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const INSTALLER_SESSION_KEY = 'installer_setup_data';
const INSTALLER_REQUIRED_TABLES = ['users', 'technicians', 'settings', 'service_calls'];

$configPath = __DIR__ . '/src/config.php';
$existingConfig = require $configPath;
$formData = [
    'db_host' => trim((string)($existingConfig['db_host'] ?? '127.0.0.1')),
    'db_name' => trim((string)($existingConfig['db_name'] ?? 'servicebook')),
    'db_user' => trim((string)($existingConfig['db_user'] ?? 'root')),
    'db_pass' => (string)($existingConfig['db_pass'] ?? ''),
    'db_charset' => trim((string)($existingConfig['db_charset'] ?? 'utf8mb4')),
    'app_timezone' => trim((string)($existingConfig['app_timezone'] ?? date_default_timezone_get())),
    'site_title' => 'Servicebook',
];
$errors = [];
$notice = '';
$result = null;
$showConfirmation = false;

if (isset($_SESSION[INSTALLER_SESSION_KEY]) && is_array($_SESSION[INSTALLER_SESSION_KEY])) {
    foreach ($formData as $key => $value) {
        if (array_key_exists($key, $_SESSION[INSTALLER_SESSION_KEY])) {
            $formData[$key] = (string)$_SESSION[INSTALLER_SESSION_KEY][$key];
        }
    }
}
$detectedState = detectInstallationState($formData);
$installerNeedsAdminAuth = in_array($detectedState['status'], ['installed', 'partial'], true);
$installerLoginUsername = '';

Auth::start();
$currentInstallerUser = Auth::currentUser();
$installerAdminAuthenticated = is_array($currentInstallerUser)
    && (($currentInstallerUser['role'] ?? '') === 'Administrator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please reload and try again.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'installer_login') {
            if (!$installerNeedsAdminAuth) {
                $errors['form'] = 'Installer sign-in is not required before initial setup.';
            } else {
                $installerLoginUsername = trim((string)($_POST['installer_username'] ?? ''));
                $installerPassword = (string)($_POST['installer_password'] ?? '');

                if ($installerLoginUsername === '' || $installerPassword === '') {
                    $errors['form'] = 'Username and password are required.';
                } elseif (!Auth::login($installerLoginUsername, $installerPassword)) {
                    $errors['form'] = Auth::lastError() ?: 'Unable to sign in right now.';
                } else {
                    $signedIn = Auth::currentUser();
                    if (($signedIn['role'] ?? '') !== 'Administrator') {
                        Auth::logout();
                        $errors['form'] = 'Installer access requires an Administrator account.';
                    } else {
                        header('Location: ' . url('install.php'));
                        exit;
                    }
                }
            }
        } elseif ($action === 'installer_logout') {
            Auth::logout();
            header('Location: ' . url('install.php'));
            exit;
        } elseif ($installerNeedsAdminAuth && !$installerAdminAuthenticated) {
            $errors['form'] = 'Administrator sign-in is required before running installer actions on an existing installation.';
        } elseif ($action === 'collect') {
            $formData['db_host'] = trim((string)($_POST['db_host'] ?? ''));
            $formData['db_name'] = trim((string)($_POST['db_name'] ?? ''));
            $formData['db_user'] = trim((string)($_POST['db_user'] ?? ''));
            $formData['db_pass'] = (string)($_POST['db_pass'] ?? '');
            $formData['site_title'] = trim((string)($_POST['site_title'] ?? ''));

            if ($formData['db_host'] === '') {
                $errors['db_host'] = 'SQL server address is required.';
            }
            if ($formData['db_name'] === '') {
                $errors['db_name'] = 'Database name is required.';
            }
            if ($formData['db_user'] === '') {
                $errors['db_user'] = 'Database login is required.';
            }
            if ($formData['site_title'] === '') {
                $errors['site_title'] = 'Initial site title is required.';
            }

            if (empty($errors)) {
                $detectedState = detectInstallationState($formData);
                if ($detectedState['status'] === 'unreachable') {
                    $errors['form'] = $detectedState['message'];
                } elseif (in_array($detectedState['status'], ['installed', 'partial'], true) && !$installerAdminAuthenticated) {
                    $errors['form'] = 'Administrator sign-in is required before updating an existing installation.';
                } else {
                    $_SESSION[INSTALLER_SESSION_KEY] = $formData;
                    $showConfirmation = true;
                }
            }
        } elseif ($action === 'cancel') {
            unset($_SESSION[INSTALLER_SESSION_KEY]);
            $showConfirmation = false;
        } elseif ($action === 'confirm') {
            if (!isset($_SESSION[INSTALLER_SESSION_KEY]) || !is_array($_SESSION[INSTALLER_SESSION_KEY])) {
                $errors['form'] = 'Installation details were lost. Please fill out the form again.';
            } else {
                $formData = array_merge($formData, array_map('strval', $_SESSION[INSTALLER_SESSION_KEY]));
                $detectedState = detectInstallationState($formData);
                if ($detectedState['status'] === 'unreachable') {
                    $errors['form'] = $detectedState['message'];
                } elseif (in_array($detectedState['status'], ['installed', 'partial'], true) && !$installerAdminAuthenticated) {
                    $errors['form'] = 'Administrator sign-in is required before confirming installer updates.';
                } else {
                    $result = runInstallation($formData, $configPath);
                    if (!empty($result['error'])) {
                        $errors['form'] = $result['error'];
                    } else {
                        unset($_SESSION[INSTALLER_SESSION_KEY]);
                        $notice = 'Installation completed successfully.';
                    }
                }
            }
        }
    }
}

function createPdoConnection(string $dsn, string $username, string $password): PDO
{
    return new PDO(
        $dsn,
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function detectInstallationState(array $setup): array
{
    $status = [
        'status' => 'missing',
        'message' => 'No installation was detected for these connection settings.',
        'admin_exists' => false,
    ];

    try {
        $pdo = createPdoConnection(
            sprintf('mysql:host=%s;charset=%s', $setup['db_host'], $setup['db_charset']),
            $setup['db_user'],
            $setup['db_pass']
        );

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :schema_name'
        );
        $stmt->execute([':schema_name' => $setup['db_name']]);
        if ((int)$stmt->fetchColumn() === 0) {
            return $status;
        }

        $pdo->exec('USE `' . str_replace('`', '``', $setup['db_name']) . '`');
        $tableStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema_name AND TABLE_NAME = :table_name'
        );

        $presentCount = 0;
        $usersTablePresent = false;
        foreach (INSTALLER_REQUIRED_TABLES as $tableName) {
            $tableStmt->execute([
                ':schema_name' => $setup['db_name'],
                ':table_name' => $tableName,
            ]);
            $isPresent = (int)$tableStmt->fetchColumn() > 0;
            if ($tableName === 'users') {
                $usersTablePresent = $isPresent;
            }
            $presentCount += $isPresent ? 1 : 0;
        }

        if ($presentCount === 0) {
            return $status;
        }

        $adminExists = false;
        if ($usersTablePresent) {
            $adminStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
            $adminStmt->execute([':username' => 'admin']);
            $adminExists = (int)$adminStmt->fetchColumn() > 0;
        }

        if ($presentCount === count(INSTALLER_REQUIRED_TABLES)) {
            return [
                'status' => 'installed',
                'message' => 'A complete installation was detected for this database.',
                'admin_exists' => $adminExists,
            ];
        }

        return [
            'status' => 'partial',
            'message' => 'A partial installation was detected. Confirm to repair/complete the schema.',
            'admin_exists' => $adminExists,
        ];
    } catch (PDOException $exception) {
        Logger::warning('Installer connection check failed', [
            'db_host' => (string)($setup['db_host'] ?? ''),
            'db_name' => (string)($setup['db_name'] ?? ''),
            'db_user' => (string)($setup['db_user'] ?? ''),
            'exception_code' => (string)$exception->getCode(),
            'exception' => $exception->getMessage(),
        ]);

        return [
            'status' => 'unreachable',
            'message' => installerConnectionErrorMessage($exception),
            'admin_exists' => false,
        ];
    }
}

function installerConnectionErrorMessage(PDOException $exception): string
{
    $message = (string)$exception->getMessage();
    if (str_contains($message, 'SQLSTATE[HY000] [1045]')) {
        return 'Unable to authenticate with SQL using the provided username/password.';
    }
    if (str_contains($message, 'SQLSTATE[HY000] [2002]')) {
        return 'Unable to reach the SQL server at the provided host.';
    }

    return 'Could not connect to SQL server with those settings. Please verify host, database name, and credentials.';
}

function runInstallation(array $setup, string $configPath): array
{
    try {
        $pdo = createPdoConnection(
            sprintf('mysql:host=%s;charset=%s', $setup['db_host'], $setup['db_charset']),
            $setup['db_user'],
            $setup['db_pass']
        );
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $setup['db_name']) . '` CHARACTER SET ' . $setup['db_charset']);
        $pdo->exec('USE `' . str_replace('`', '``', $setup['db_name']) . '`');

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    role ENUM('Administrator','Office Staff','Technician') NOT NULL DEFAULT 'Office Staff',
    technician_id INT UNSIGNED DEFAULT NULL,
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
    status ENUM('New','Dispatched','In Progress','Waiting Parts','On Hold','Complete','Cancelled') NOT NULL DEFAULT 'New',
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (assigned_tech),
    INDEX (status),
    FOREIGN KEY (assigned_tech) REFERENCES technicians(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
SQL
        );

        $siteTitleStmt = $pdo->prepare(
            'INSERT INTO settings (name, value) VALUES (:name, :insert_value)
             ON DUPLICATE KEY UPDATE value = :update_value'
        );
        $siteTitleStmt->execute([
            ':name' => 'site_title',
            ':insert_value' => $setup['site_title'],
            ':update_value' => $setup['site_title'],
        ]);
        $siteTitleStmt->execute([
            ':name' => 'site_logo_path',
            ':insert_value' => '',
            ':update_value' => '',
        ]);

        $temporaryPassword = '';
        $adminStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
        $adminStmt->execute([':username' => 'admin']);
        $adminExists = (int)$adminStmt->fetchColumn() > 0;

        if (!$adminExists) {
            $temporaryPassword = bin2hex(random_bytes(8));
            $insertAdminStmt = $pdo->prepare(
                'INSERT INTO users (username, password_hash, display_name, role, active, created_at)
                 VALUES (:username, :password_hash, :display_name, :role, 1, NOW())'
            );
            $insertAdminStmt->execute([
                ':username' => 'admin',
                ':password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
                ':display_name' => 'Administrator',
                ':role' => 'Administrator',
            ]);
        }

        $configOutput = "<?php\n"
            . "// Database configuration for local installation. Update these values for your environment.\n"
            . '$appTimezone = ' . var_export($setup['app_timezone'], true) . ";\n"
            . "date_default_timezone_set(\$appTimezone);\n\n"
            . "return [\n"
            . "    'db_host' => " . var_export($setup['db_host'], true) . ",\n"
            . "    'db_name' => " . var_export($setup['db_name'], true) . ",\n"
            . "    'db_user' => " . var_export($setup['db_user'], true) . ",\n"
            . "    'db_pass' => " . var_export($setup['db_pass'], true) . ",\n"
            . "    'db_charset' => " . var_export($setup['db_charset'], true) . ",\n"
            . "    'app_timezone' => \$appTimezone,\n"
            . "];\n";

        if (file_put_contents($configPath, $configOutput) === false) {
            return ['error' => 'Database setup succeeded, but config file could not be written.'];
        }

        return [
            'error' => '',
            'admin_created' => !$adminExists,
            'temporary_password' => $temporaryPassword,
        ];
    } catch (PDOException $exception) {
        return ['error' => 'Installation failed: ' . $exception->getMessage()];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Servicebook Installer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-3">Servicebook Installer</h1>
                    <p class="text-muted">Provide installation details, review what will happen, then confirm before setup runs.</p>

                    <?php if ($installerNeedsAdminAuth && $installerAdminAuthenticated): ?>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div class="small text-muted">Installer unlocked for admin: <strong><?= escape((string)($currentInstallerUser['display_name'] ?? $currentInstallerUser['username'] ?? 'Administrator')) ?></strong></div>
                            <form method="post" class="m-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="installer_logout">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Sign out</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php if ($detectedState['status'] === 'installed' && !$showConfirmation && $result === null): ?>
                        <div class="alert alert-info">
                            <strong>Existing installation detected.</strong>
                            <div class="mt-2">Administrator sign-in is required before this installer can update connection settings or verify schema.</div>
                            <div class="mt-2"><a class="btn btn-sm btn-outline-primary" href="<?= escape(url('public/login.php')) ?>">Go to login</a></div>
                        </div>
                    <?php elseif ($detectedState['status'] === 'partial' && !$showConfirmation && $result === null): ?>
                        <div class="alert alert-warning">
                            <strong>Partial installation detected.</strong> Administrator sign-in is required to complete/repair this setup.
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors['form'])): ?>
                        <div class="alert alert-danger"><?= escape($errors['form']) ?></div>
                    <?php endif; ?>

                    <?php if ($installerNeedsAdminAuth && !$installerAdminAuthenticated): ?>
                        <div class="alert alert-warning">
                            Sign in as an <strong>Administrator</strong> to unlock installer updates for this existing installation.
                        </div>
                        <form method="post" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="installer_login">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="installer_username">Administrator Username</label>
                                    <input id="installer_username" name="installer_username" class="form-control" value="<?= escape($installerLoginUsername) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="installer_password">Password</label>
                                    <input id="installer_password" name="installer_password" type="password" class="form-control" required>
                                </div>
                            </div>
                            <div class="mt-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Sign in to installer</button>
                                <a class="btn btn-outline-secondary" href="<?= escape(url('public/login.php')) ?>">Go to login</a>
                            </div>
                        </form>
                    <?php elseif ($result !== null && empty($result['error'])): ?>
                        <div class="alert alert-success">
                            <strong><?= escape($notice) ?></strong>
                        </div>
                        <?php if (!empty($result['admin_created'])): ?>
                            <p><strong>Login:</strong> admin<br><strong>Temporary Password:</strong> <?= escape((string)$result['temporary_password']) ?></p>
                            <p class="text-danger mb-4">Sign in and change this password immediately.</p>
                        <?php else: ?>
                            <p class="mb-4">Admin user already existed. Continue using your existing credentials.</p>
                        <?php endif; ?>
                        <a class="btn btn-primary" href="<?= escape(url('public/login.php')) ?>">Go to login</a>
                    <?php elseif ($showConfirmation): ?>
                        <h2 class="h5 mt-4">Confirm installation</h2>
                        <ul class="list-group mb-3">
                            <li class="list-group-item"><strong>SQL Server:</strong> <?= escape($formData['db_host']) ?></li>
                            <li class="list-group-item"><strong>Database:</strong> <?= escape($formData['db_name']) ?></li>
                            <li class="list-group-item"><strong>Login:</strong> <?= escape($formData['db_user']) ?></li>
                            <li class="list-group-item"><strong>Initial Site Title:</strong> <?= escape($formData['site_title']) ?></li>
                            <li class="list-group-item"><strong>Detected State:</strong> <?= escape($detectedState['message']) ?></li>
                        </ul>
                        <div class="alert alert-secondary">
                            <?php if ($detectedState['status'] === 'installed'): ?>
                                Existing data will be preserved. The installer will verify schema and update the configured site title.
                            <?php else: ?>
                                The installer will create the database/schema and create an admin user if one is missing.
                            <?php endif; ?>
                        </div>
                        <form method="post" class="d-flex gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="confirm">
                            <button type="submit" class="btn btn-primary">Confirm and install</button>
                            <button type="submit" class="btn btn-outline-secondary" name="action" value="cancel">Cancel</button>
                        </form>
                    <?php else: ?>
                        <form method="post" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="collect">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="db_host">SQL Server Address</label>
                                    <input id="db_host" name="db_host" class="form-control<?= isset($errors['db_host']) ? ' is-invalid' : '' ?>" value="<?= escape($formData['db_host']) ?>" required>
                                    <?php if (isset($errors['db_host'])): ?><div class="invalid-feedback"><?= escape($errors['db_host']) ?></div><?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="db_name">Database Name</label>
                                    <input id="db_name" name="db_name" class="form-control<?= isset($errors['db_name']) ? ' is-invalid' : '' ?>" value="<?= escape($formData['db_name']) ?>" required>
                                    <?php if (isset($errors['db_name'])): ?><div class="invalid-feedback"><?= escape($errors['db_name']) ?></div><?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="db_user">Database Login</label>
                                    <input id="db_user" name="db_user" class="form-control<?= isset($errors['db_user']) ? ' is-invalid' : '' ?>" value="<?= escape($formData['db_user']) ?>" required>
                                    <?php if (isset($errors['db_user'])): ?><div class="invalid-feedback"><?= escape($errors['db_user']) ?></div><?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="db_pass">Database Password</label>
                                    <input id="db_pass" name="db_pass" type="password" class="form-control" value="<?= escape($formData['db_pass']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="site_title">Initial Site Title</label>
                                    <input id="site_title" name="site_title" class="form-control<?= isset($errors['site_title']) ? ' is-invalid' : '' ?>" value="<?= escape($formData['site_title']) ?>" required>
                                    <?php if (isset($errors['site_title'])): ?><div class="invalid-feedback"><?= escape($errors['site_title']) ?></div><?php endif; ?>
                                </div>
                            </div>
                            <div class="mt-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Review and continue</button>
                                <a class="btn btn-outline-secondary" href="<?= escape(url('public/login.php')) ?>">Back to login</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
