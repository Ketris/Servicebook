<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/User.php';
Auth::requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$user = $id ? User::findById($id) : null;
$errors = [];
$values = [
    'username' => $user['username'] ?? '',
    'display_name' => $user['display_name'] ?? '',
    'role' => $user['role'] ?? 'Office Staff',
    'password' => '',
    'active' => $user['active'] ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['username'] = trim($_POST['username'] ?? '');
    $values['display_name'] = trim($_POST['display_name'] ?? '');
    $values['role'] = $_POST['role'] ?? 'Office Staff';
    $values['password'] = $_POST['password'] ?? '';
    $values['active'] = isset($_POST['active']) ? 1 : 0;

    if ($values['username'] === '') {
        $errors['username'] = 'Username is required.';
    } elseif (User::usernameExists($values['username'], $id)) {
        $errors['username'] = 'This username is already in use.';
    }

    if ($values['display_name'] === '') {
        $errors['display_name'] = 'Display name is required.';
    }

    if (!$id && $values['password'] === '') {
        $errors['password'] = 'Password is required for new users.';
    }

    if (empty($errors)) {
        User::save($values, $id);
        header('Location: ' . url('admin/users.php'));
        exit;
    }
}
?>
<?php include __DIR__ . '/../public/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3"><?= $id ? 'Edit User' : 'New User' ?></h1>
                <p class="text-muted mb-0">Manage login accounts and roles.</p>
            </div>
            <a class="btn btn-secondary" href="<?= url('admin/users.php') ?>">Back</a>
        </div>
        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input id="username" name="username" class="form-control" type="text" value="<?= escape($values['username']) ?>" required autofocus>
                <?php if (isset($errors['username'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['username']) ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="display_name">Display Name</label>
                <input id="display_name" name="display_name" class="form-control" type="text" value="<?= escape($values['display_name']) ?>" required>
                <?php if (isset($errors['display_name'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['display_name']) ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="role">Role</label>
                <select id="role" name="role" class="form-select">
                    <option value="Administrator" <?= $values['role'] === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                    <option value="Office Staff" <?= $values['role'] === 'Office Staff' ? 'selected' : '' ?>>Office Staff</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password <?= $id ? '(leave blank to keep current)' : '' ?></label>
                <input id="password" name="password" class="form-control" type="password" autocomplete="new-password">
                <?php if (isset($errors['password'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['password']) ?></div>
                <?php endif; ?>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= $values['active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="active">Active</label>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary"><?= $id ? 'Save User' : 'Create User' ?></button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../public/footer.php'; ?>
