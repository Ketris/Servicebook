<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/User.php';
require_once __DIR__ . '/../src/Template.php';
require_once __DIR__ . '/../src/Logger.php';

Auth::requireAdmin();
$user = Auth::currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $_SESSION['admin_users_error'] = 'Your session expired. Please try again.';
        header('Location: ' . url('admin/users.php'));
        exit;
    }

    $action = trim((string)($_POST['action'] ?? ''));
    $targetUserId = (int)($_POST['user_id'] ?? 0);
    if ($targetUserId <= 0) {
        $_SESSION['admin_users_error'] = 'Invalid user selected.';
        header('Location: ' . url('admin/users.php'));
        exit;
    }

    if ($action === 'unlock') {
        $ok = User::clearLockout($targetUserId);
        if ($ok) {
            $_SESSION['admin_users_success'] = 'Account lock has been cleared.';
            Logger::info('Admin cleared account lock', [
                'admin_user_id' => $user['id'] ?? null,
                'target_user_id' => $targetUserId,
            ]);
        } else {
            $_SESSION['admin_users_error'] = 'Unable to clear lock for that user.';
        }
    } elseif ($action === 'reset_password') {
        $tempPassword = User::resetPassword($targetUserId);
        if ($tempPassword === null) {
            $_SESSION['admin_users_error'] = 'Unable to reset password for that user.';
        } else {
            $_SESSION['admin_users_success'] = 'Password was reset. Temporary password shown below.';
            $_SESSION['admin_users_temp_password'] = $tempPassword;
            $_SESSION['admin_users_temp_password_user_id'] = $targetUserId;
            Logger::warning('Admin reset user password', [
                'admin_user_id' => $user['id'] ?? null,
                'target_user_id' => $targetUserId,
            ]);
        }
    } else {
        $_SESSION['admin_users_error'] = 'Unknown action requested.';
    }

    header('Location: ' . url('admin/users.php'));
    exit;
}

$users = User::findAll();

$success = $_SESSION['admin_users_success'] ?? '';
$error = $_SESSION['admin_users_error'] ?? '';
$temporaryPassword = $_SESSION['admin_users_temp_password'] ?? '';
$temporaryPasswordUserId = $_SESSION['admin_users_temp_password_user_id'] ?? null;

unset($_SESSION['admin_users_success'], $_SESSION['admin_users_error'], $_SESSION['admin_users_temp_password'], $_SESSION['admin_users_temp_password_user_id']);

Template::render('pages/admin_users', [
    'title' => 'User Management',
    'user' => $user,
    'users' => $users,
    'success' => $success,
    'error' => $error,
    'temporaryPassword' => $temporaryPassword,
    'temporaryPasswordUserId' => $temporaryPasswordUserId,
], 'layouts/app');
