<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/User.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/UserPreference.php';

Auth::requireLogin();
$user = Auth::currentUser();
$userId = (int)($user['id'] ?? 0);
$isAdministrator = (string)($user['role'] ?? '') === 'Administrator';

$filterOptions = [
    'all' => 'All Calls',
    'incomplete' => 'Incomplete',
    'unassigned' => 'Unassigned',
    'completed_today' => 'Completed Today',
    'completed_week' => 'Completed This Week',
    'received_this_week' => 'Received This Week',
    'received_this_month' => 'Received This Month',
];
$perPageOptions = [25, 50, 100, 250];
$defaultFilter = $isAdministrator ? 'all' : 'incomplete';

$prefs = UserPreference::getMany($userId, [
    'ui_theme' => 'light',
    'calls.default_filter' => $defaultFilter,
    'calls.default_per_page' => '50',
]);
$values = [
    'theme' => in_array($prefs['ui_theme'], ['light', 'dark'], true) ? $prefs['ui_theme'] : 'light',
    'default_filter' => array_key_exists($prefs['calls.default_filter'], $filterOptions) ? $prefs['calls.default_filter'] : $defaultFilter,
    'default_per_page' => in_array((int)$prefs['calls.default_per_page'], $perPageOptions, true) ? (int)$prefs['calls.default_per_page'] : 50,
];

$errors = [];
$success = trim((string)($_SESSION['settings_success'] ?? ''));
unset($_SESSION['settings_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please try again.';
    } elseif ($action === 'save_preferences') {
        $theme = (string)($_POST['theme'] ?? '');
        $filter = (string)($_POST['default_filter'] ?? '');
        $perPage = (int)($_POST['default_per_page'] ?? 0);

        if (!in_array($theme, ['light', 'dark'], true)) {
            $errors['theme'] = 'Choose a valid theme.';
        }
        if (!array_key_exists($filter, $filterOptions)) {
            $errors['default_filter'] = 'Choose a valid default filter.';
        }
        if (!in_array($perPage, $perPageOptions, true)) {
            $errors['default_per_page'] = 'Choose a valid rows-per-page value.';
        }

        if (!$errors) {
            UserPreference::setMany($userId, [
                'ui_theme' => $theme,
                'calls.default_filter' => $filter,
                'calls.default_per_page' => (string)$perPage,
            ]);
            $_SESSION['settings_success'] = 'Your preferences were saved.';
            header('Location: ' . url('public/settings.php'));
            exit;
        }
        $values = ['theme' => $theme, 'default_filter' => $filter, 'default_per_page' => $perPage];
    } elseif ($action === 'change_password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if ($current === '') {
            $errors['current_password'] = 'Enter your current password.';
        }
        if (strlen($new) < 10) {
            $errors['new_password'] = 'Use at least 10 characters.';
        } elseif ($new === $current) {
            $errors['new_password'] = 'The new password must differ from the current one.';
        }
        if ($new !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (!$errors) {
            if (User::changeOwnPassword($userId, $current, $new)) {
                session_regenerate_id(true);
                Logger::info('User changed own password', ['user_id' => $userId]);
                ServiceCall::logSystemEvent($user, 'user_password', null, 'changed', 'User changed their own password');
                $_SESSION['settings_success'] = 'Your password was changed.';
                header('Location: ' . url('public/settings.php'));
                exit;
            }
            Logger::warning('Own password change failed: wrong current password', ['user_id' => $userId]);
            $errors['current_password'] = 'Current password is incorrect.';
        }
    } else {
        $errors['form'] = 'Unknown action requested.';
    }
}

Template::render('pages/settings', [
    'title' => 'Settings',
    'user' => $user,
    'values' => $values,
    'filterOptions' => $filterOptions,
    'perPageOptions' => $perPageOptions,
    'errors' => $errors,
    'success' => $success,
    'savedTheme' => in_array($prefs['ui_theme'], ['light', 'dark'], true) ? $prefs['ui_theme'] : 'light',
], 'layouts/app');
