<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireAdmin();
$user = Auth::currentUser();

$pdo = Database::getConnection();
$errors = [];
$settings = [
    'site_title' => 'Servicebook',
    'default_priority' => 'Normal',
];

foreach ($settings as $name => $default) {
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE name = :name LIMIT 1');
    $stmt->execute([':name' => $name]);
    $value = $stmt->fetchColumn();
    $settings[$name] = $value !== false ? $value : $default;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['site_title'] = trim($_POST['site_title'] ?? '');
    $settings['default_priority'] = trim($_POST['default_priority'] ?? 'Normal');

    if ($settings['site_title'] === '') {
        $errors['site_title'] = 'Site title cannot be empty.';
    }

    if (!in_array($settings['default_priority'], ['Low', 'Normal', 'High', 'Emergency'], true)) {
        $errors['default_priority'] = 'Invalid default priority.';
    }

    if (empty($errors)) {
        foreach ($settings as $name => $value) {
            $stmt = $pdo->prepare(
                'INSERT INTO settings (name, value) VALUES (:name, :value)
                 ON DUPLICATE KEY UPDATE value = :value'
            );
            $stmt->execute([':name' => $name, ':value' => $value]);
        }

        header('Location: ' . url('admin/settings.php') . '?updated=1');
        exit;
    }
}

Template::render('pages/admin_settings', [
    'title' => 'System Settings',
    'user' => $user,
    'settings' => $settings,
    'errors' => $errors,
], 'layouts/app');
