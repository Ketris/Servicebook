<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Installation.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/SavedView.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Technician.php';
require_once __DIR__ . '/../src/Template.php';

Installation::redirectToInstallerIfNeeded();
Auth::requireLogin();
$user = Auth::currentUser();
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'incomplete');
$errors = [];
$success = trim((string)($_SESSION['success_message'] ?? ''));
unset($_SESSION['success_message']);
if (!in_array($filter, ['all', 'incomplete', 'unassigned', 'completed_today', 'completed_week'], true)) {
    $filter = 'incomplete';
}

$selectedViewId = (int)($_GET['saved_view'] ?? 0);
$savedViews = SavedView::listVisibleForUser($user, 'calls');
$activeViewName = '';
if ($selectedViewId > 0) {
    $savedView = SavedView::findVisibleById($selectedViewId, $user, 'calls');
    if ($savedView) {
        $search = trim((string)($savedView['search_term'] ?? ''));
        $candidateFilter = trim((string)($savedView['filter_value'] ?? 'incomplete'));
        if (in_array($candidateFilter, ['all', 'incomplete', 'unassigned', 'completed_today', 'completed_week'], true)) {
            $filter = $candidateFilter;
        }
        $activeViewName = (string)($savedView['view_name'] ?? '');
    }
} elseif ($search === '' && $filter === 'incomplete') {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please reload and try again.';
    } else {
        $action = trim((string)($_POST['action'] ?? ''));

        try {
            if ($action === 'bulk_update') {
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
            } elseif ($action === 'save_view') {
                $viewName = trim((string)($_POST['view_name'] ?? ''));
                SavedView::createPersonal((int)($user['id'] ?? 0), 'calls', $viewName, $search, $filter);
                $success = 'Saved view created.';
            } elseif ($action === 'save_default_view' && ($user['role'] ?? '') === 'Administrator') {
                $viewName = trim((string)($_POST['view_name'] ?? ''));
                $roleScope = trim((string)($_POST['default_role_scope'] ?? 'Office Staff'));
                SavedView::createRoleDefault((int)($user['id'] ?? 0), $roleScope, 'calls', $viewName, $search, $filter);
                $success = 'Role default view updated for ' . $roleScope . '.';
            } elseif ($action === 'delete_view') {
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && ($search !== '' || $filter !== 'incomplete' || $selectedViewId > 0)) {
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

$calls = ServiceCall::findAll($search, $filter);
$stats = ServiceCall::getSummaryStats();
$technicians = Technician::findAllActive();
$savedViews = SavedView::listVisibleForUser($user, 'calls');

Template::render('pages/index', [
    'title' => 'Service Calls',
    'user' => $user,
    'search' => $search,
    'filter' => $filter,
    'calls' => $calls,
    'stats' => $stats,
    'technicians' => $technicians,
    'savedViews' => $savedViews,
    'selectedViewId' => $selectedViewId,
    'errors' => $errors,
    'success' => $success,
    'recentSearches' => $recentSearches,
], 'layouts/app');
