<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/AppSettings.php';
require_once __DIR__ . '/../src/Installation.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/SavedView.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Technician.php';
require_once __DIR__ . '/../src/Template.php';
require_once __DIR__ . '/../src/UserPreference.php';

Installation::redirectToInstallerIfNeeded();
Auth::requireLogin();
$user = Auth::currentUser();
$search = trim($_GET['search'] ?? '');
$isAdministrator = (string)($user['role'] ?? '') === 'Administrator';
$defaultFilter = $isAdministrator ? 'all' : 'incomplete';
$userDefaults = UserPreference::getMany((int)($user['id'] ?? 0), [
    'calls.default_filter' => $defaultFilter,
    'calls.default_per_page' => '50',
]);
$userDefaults = [
    'filter' => (string)($userDefaults['calls.default_filter'] ?? $defaultFilter),
    'per_page' => (int)($userDefaults['calls.default_per_page'] ?? 50),
];
if (!in_array($userDefaults['filter'], ['all', 'incomplete', 'unassigned', 'completed_today', 'completed_week'], true)) {
    $userDefaults['filter'] = $defaultFilter;
}
if (!in_array($userDefaults['per_page'], [25, 50, 100, 250], true)) {
    $userDefaults['per_page'] = 50;
}
$defaultFilter = $userDefaults['filter'];
$filter = trim((string)($_GET['filter'] ?? $userDefaults['filter']));
if ($filter === '') {
    $filter = $userDefaults['filter'];
}
$allowedPerPage = [25, 50, 100, 250];
$perPage = (int)($_GET['per_page'] ?? $userDefaults['per_page']);
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = $userDefaults['per_page'];
}
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}
$errors = [];
$success = trim((string)($_SESSION['success_message'] ?? ''));
unset($_SESSION['success_message']);
if (!in_array($filter, ['all', 'incomplete', 'unassigned', 'completed_today', 'completed_week'], true)) {
    $filter = $defaultFilter;
}

$savedViewsEnabled = AppSettings::get('saved_views_enabled') === '1';
$bulkManagementEnabled = AppSettings::get('bulk_management_enabled') === '1';
$selectedViewId = $savedViewsEnabled ? (int)($_GET['saved_view'] ?? 0) : 0;
$savedViews = $savedViewsEnabled ? SavedView::listVisibleForUser($user, 'calls') : [];
$activeViewName = '';
if ($savedViewsEnabled && $selectedViewId > 0) {
    $savedView = SavedView::findVisibleById($selectedViewId, $user, 'calls');
    if ($savedView) {
        $search = trim((string)($savedView['search_term'] ?? ''));
        $candidateFilter = trim((string)($savedView['filter_value'] ?? 'incomplete'));
        if (in_array($candidateFilter, ['all', 'incomplete', 'unassigned', 'completed_today', 'completed_week'], true)) {
            $filter = $candidateFilter;
        }
        $activeViewName = (string)($savedView['view_name'] ?? '');
    }
} elseif ($savedViewsEnabled && $search === '' && $filter === $defaultFilter) {
    foreach ($savedViews as $candidateView) {
        $isRoleDefault = (int)($candidateView['is_default'] ?? 0) === 1
            && empty($candidateView['user_id'])
            && (string)($candidateView['role_scope'] ?? '') === (string)($user['role'] ?? '');
        if ($isRoleDefault) {
            $search = trim((string)($candidateView['search_term'] ?? ''));
            $candidateFilter = trim((string)($candidateView['filter_value'] ?? 'incomplete'));
            if (in_array($candidateFilter, ['all', 'incomplete', 'unassigned', 'completed_today', 'completed_week'], true)) {
                $filter = $candidateFilter;
            }
            $selectedViewId = (int)($candidateView['id'] ?? 0);
            $activeViewName = (string)($candidateView['view_name'] ?? '');
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['poll'] ?? '') === '1') {
    $pollSignature = ServiceCall::getListSignature($search, $filter) . '|' . implode(',', ServiceCall::getSummaryStats());
    header('Content-Type: application/json');
    echo json_encode(['signature' => $pollSignature]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please reload and try again.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));

        try {
            if ($action === 'bulk_update') {
                if (!$bulkManagementEnabled) {
                    throw new InvalidArgumentException('Bulk Management beta feature is currently disabled by an administrator.');
                }
                if (($user['role'] ?? '') === 'Technician') {
                    throw new InvalidArgumentException('Technician accounts cannot run bulk call updates.');
                }

                $callIds = $_POST['call_ids'] ?? [];
                $bulkStatus = trim((string)($_POST['bulk_status'] ?? ''));
                $bulkAssignedTech = trim((string)($_POST['bulk_assigned_tech'] ?? ''));
                $changes = [];
                if ($bulkStatus !== '') {
                    $changes['status'] = $bulkStatus;
                }
                if ($bulkAssignedTech !== '') {
                    $changes['assigned_tech'] = $bulkAssignedTech === 'unassign' ? null : (int)$bulkAssignedTech;
                }

                $updatedCount = ServiceCall::bulkUpdate((array)$callIds, $changes, $user);
                $success = 'Bulk update applied to ' . $updatedCount . ' call(s).';
                Logger::info('Bulk call update applied', [
                    'user_id' => $user['id'] ?? null,
                    'updated_count' => $updatedCount,
                ]);
            } elseif ($action === 'save_list_defaults') {
                $defaultFilterValue = trim((string)($_POST['default_filter'] ?? ''));
                $defaultPerPageValue = (int)($_POST['default_per_page'] ?? 0);
                if (!in_array($defaultFilterValue, ['all', 'incomplete', 'unassigned', 'completed_today', 'completed_week'], true)) {
                    throw new InvalidArgumentException('Invalid default filter setting.');
                }
                if (!in_array($defaultPerPageValue, [25, 50, 100, 250], true)) {
                    throw new InvalidArgumentException('Invalid rows-per-page setting.');
                }
                UserPreference::setMany((int)($user['id'] ?? 0), [
                    'calls.default_filter' => $defaultFilterValue,
                    'calls.default_per_page' => (string)$defaultPerPageValue,
                ]);
                $userDefaults['filter'] = $defaultFilterValue;
                $userDefaults['per_page'] = $defaultPerPageValue;
                $defaultFilter = $defaultFilterValue;
                $filter = $defaultFilterValue;
                $perPage = $defaultPerPageValue;
                $success = 'Your default filter settings were saved.';
            } elseif ($action === 'save_view') {
                if (!$savedViewsEnabled) {
                    throw new InvalidArgumentException('Saved Views beta feature is currently disabled by an administrator.');
                }
                $viewName = trim((string)($_POST['view_name'] ?? ''));
                SavedView::createPersonal((int)($user['id'] ?? 0), 'calls', $viewName, $search, $filter);
                $success = 'Saved view created.';
            } elseif ($action === 'save_default_view' && ($user['role'] ?? '') === 'Administrator') {
                if (!$savedViewsEnabled) {
                    throw new InvalidArgumentException('Saved Views beta feature is currently disabled by an administrator.');
                }
                $viewName = trim((string)($_POST['view_name'] ?? ''));
                $roleScope = trim((string)($_POST['default_role_scope'] ?? 'Office Staff'));
                SavedView::createRoleDefault((int)($user['id'] ?? 0), $roleScope, 'calls', $viewName, $search, $filter);
                $success = 'Role default view updated for ' . $roleScope . '.';
            } elseif ($action === 'delete_view') {
                if (!$savedViewsEnabled) {
                    throw new InvalidArgumentException('Saved Views beta feature is currently disabled by an administrator.');
                }
                $viewId = (int)($_POST['view_id'] ?? 0);
                if (SavedView::deleteForUser($viewId, $user)) {
                    $success = 'Saved view deleted.';
                } else {
                    $errors['form'] = 'Unable to delete that saved view.';
                }
            }
        } catch (InvalidArgumentException $exception) {
            $errors['form'] = $exception->getMessage();
        } catch (Throwable $exception) {
            $errors['form'] = 'Unable to process that action right now.';
            Logger::error('Unexpected index action error', [
                'user_id' => $user['id'] ?? null,
                'action' => $action,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}

$recentSearches = $_SESSION['recent_call_views'] ?? [];
if (!is_array($recentSearches)) {
    $recentSearches = [];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && ($search !== '' || $filter !== $defaultFilter || $selectedViewId > 0)) {
    $entry = [
        'search' => $search,
        'filter' => $filter,
        'saved_view' => $selectedViewId,
        'view_name' => $activeViewName,
    ];
    $entryKey = md5(json_encode($entry));

    $deduped = [];
    $deduped[] = $entry;
    foreach ($recentSearches as $existing) {
        $existingKey = md5(json_encode($existing));
        if ($existingKey === $entryKey) {
            continue;
        }
        $deduped[] = $existing;
        if (count($deduped) >= 6) {
            break;
        }
    }

    $recentSearches = $deduped;
    $_SESSION['recent_call_views'] = $recentSearches;
}

$totalCalls = ServiceCall::countAll($search, $filter);
$totalPages = max(1, (int)ceil($totalCalls / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$calls = ServiceCall::findAll($search, $filter, $perPage, $offset);
$stats = ServiceCall::getSummaryStats();
$technicians = Technician::findAllActive();
$savedViews = $savedViewsEnabled ? SavedView::listVisibleForUser($user, 'calls') : [];

Template::render('pages/index', [
    'title' => 'Service Calls',
    'user' => $user,
    'search' => $search,
    'filter' => $filter,
    'defaultFilter' => $defaultFilter,
    'calls' => $calls,
    'page' => $page,
    'perPage' => $perPage,
    'defaultPerPage' => $userDefaults['per_page'],
    'allowedPerPage' => $allowedPerPage,
    'totalCalls' => $totalCalls,
    'totalPages' => $totalPages,
    'stats' => $stats,
    'technicians' => $technicians,
    'savedViews' => $savedViews,
    'savedViewsEnabled' => $savedViewsEnabled,
    'bulkManagementEnabled' => $bulkManagementEnabled,
    'selectedViewId' => $selectedViewId,
    'errors' => $errors,
    'success' => $success,
    'recentSearches' => $recentSearches,
], 'layouts/app');
