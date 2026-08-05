<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/User.php';
require_once __DIR__ . '/../src/Technician.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireAdmin();
$user = Auth::currentUser();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$record = $id ? User::findById($id) : null;
$technicians = Technician::findAll();
$allowedRoles = ['Administrator', 'Office Staff', 'Technician'];
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
    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please reload and try again.';
    } else {
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

        if (mb_strlen($values['username']) > 100) {
            $errors['username'] = 'Username cannot exceed 100 characters.';
        }

        if ($values['display_name'] === '') {
            $errors['display_name'] = 'Display name is required.';
        }

        if (mb_strlen($values['display_name']) > 150) {
            $errors['display_name'] = 'Display name cannot exceed 150 characters.';
        }

        if (!in_array($values['role'], $allowedRoles, true)) {
            $errors['role'] = 'Select a valid role.';
        }

        if ($values['role'] === 'Technician' && $values['technician_id'] === '') {
            $errors['technician_id'] = 'Technician role requires a linked technician profile.';
        }

        if (!$id && $values['password'] === '') {
            $errors['password'] = 'Password is required for new users.';
        } elseif ($values['password'] !== '' && mb_strlen($values['password']) < 10) {
            $errors['password'] = 'Password must be at least 10 characters.';
        }

        if (empty($errors)) {
            try {
                $isNewUser = $id === null;
                $savedId = User::save($values, $id);
                Logger::info('Admin saved user account', [
                    'admin_user_id' => $user['id'] ?? null,
                    'target_user_id' => $savedId,
                ]);
                $beforeRole = $record['role'] ?? null;
                $afterRole = (string)($values['role'] ?? 'Office Staff');
                $note = $isNewUser
                    ? 'User account created: ' . $values['username']
                    : 'User account updated: ' . $values['username'];
                ServiceCall::logSystemEvent(
                    $user,
                    'user_account',
                    $beforeRole,
                    $afterRole,
                    $note
                );
                header('Location: ' . url('admin/users.php'));
                exit;
            } catch (Throwable $exception) {
                $errors['form'] = 'Unable to save user right now.';
                Logger::error('Unexpected error saving user account', [
                    'admin_user_id' => $user['id'] ?? null,
                    'target_user_id' => $id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
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
