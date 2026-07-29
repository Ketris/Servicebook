<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
Auth::requireAdmin();

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
?>
<?php include __DIR__ . '/../public/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3">System Settings</h1>
                <p class="text-muted mb-0">Configure basic application defaults.</p>
            </div>
            <a class="btn btn-secondary" href="<?= url('admin/index.php') ?>">Back</a>
        </div>
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Settings saved successfully.</div>
        <?php endif; ?>
        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label" for="site_title">Site Title</label>
                <input id="site_title" name="site_title" class="form-control" type="text" value="<?= escape($settings['site_title']) ?>" required>
                <?php if (isset($errors['site_title'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['site_title']) ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="default_priority">Default Priority</label>
                <select id="default_priority" name="default_priority" class="form-select">
                    <?php foreach (['Low', 'Normal', 'High', 'Emergency'] as $priority): ?>
                        <option value="<?= escape($priority) ?>" <?= $priority === $settings['default_priority'] ? 'selected' : '' ?>><?= escape($priority) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['default_priority'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['default_priority']) ?></div>
                <?php endif; ?>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../public/footer.php'; ?>
