<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/User.php';
require_once __DIR__ . '/../src/Technician.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireAdmin();
$user = Auth::currentUser();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$record = $id ? User::findById($id) : null;
$technicians = Technician::findAll();
$errors = [];
$values = [
    'username' => $record['username'] ?? '',
    'display_name' => $record['display_name'] ?? '',
    'role' => $record['role'] ?? 'Office Staff',
    'technician_id' => $record['technician_id'] ?? '',
    'password' => '',
    'active' => $record['active'] ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['username'] = trim($_POST['username'] ?? '');
    $values['display_name'] = trim($_POST['display_name'] ?? '');
    $values['role'] = $_POST['role'] ?? 'Office Staff';
    $values['technician_id'] = isset($_POST['technician_id']) && $_POST['technician_id'] !== '' ? (int)$_POST['technician_id'] : '';
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

Template::render('pages/admin_user_edit', [
    'title' => $id ? 'Edit User' : 'New User',
    'user' => $user,
    'id' => $id,
    'technicians' => $technicians,
    'values' => $values,
    'errors' => $errors,
], 'layouts/app');
