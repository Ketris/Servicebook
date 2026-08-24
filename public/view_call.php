<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireLogin();
$user = Auth::currentUser();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$call = ServiceCall::findById($id);
if (!$call) {
    header('Location: ' . url('public/index.php'));
    exit;
}

$hasTechnicianProfile = !empty($user['technician_id']);
$isTechnician = ($user['role'] ?? '') === 'Technician';
$isAssignedTechnician = $hasTechnicianProfile
    && !empty($call['assigned_tech'])
    && (int)$call['assigned_tech'] === (int)$user['technician_id'];
$canManage = !$isTechnician || (
    !empty($call['assigned_tech'])
    && !empty($user['technician_id'])
    && (int)$call['assigned_tech'] === (int)$user['technician_id']
);
$canSelfAssign = $hasTechnicianProfile
    && empty($call['assigned_tech'])
    && !in_array((string)$call['status'], ['Complete', 'Cancelled'], true);
$canEditDetails = !$isTechnician || $canManage;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $expectedUpdatedAt = trim((string)($_POST['expected_updated_at'] ?? ($call['updated_at'] ?? $call['created_at'] ?? '')));

    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please reload and try again.';
    } elseif ($action === 'claim_job') {
        if (!$canSelfAssign) {
            $errors['form'] = 'This job cannot be claimed right now.';
        } else {
            try {
                ServiceCall::claimForTechnician($id, (int)$user['technician_id'], $user, $expectedUpdatedAt);
                $_SESSION['success_message'] = 'Job claimed successfully.';
                header('Location: ' . url('public/view_call.php?id=' . $id));
                exit;
            } catch (InvalidArgumentException $exception) {
                $errors['form'] = $exception->getMessage();
                Logger::warning('View call claim validation failed', ['user_id' => $user['id'] ?? null, 'call_id' => $id, 'error' => $exception->getMessage()]);
            } catch (Throwable $exception) {
                $errors['form'] = 'Unable to claim this job right now.';
                Logger::error('Unexpected error claiming service call from view', ['user_id' => $user['id'] ?? null, 'call_id' => $id, 'exception' => $exception->getMessage()]);
            }
        }
    } elseif ($action === 'add_note') {
        $note = trim((string)($_POST['technician_note'] ?? ''));
        if (!$isAssignedTechnician) {
            $errors['form'] = 'You are not allowed to add a note to this job.';
        } elseif ($note === '') {
            $errors['note'] = 'Enter a note before saving.';
        } else {
            try {
                ServiceCall::updateAssignedTechnicianJob(
                    $id,
                    (int)$user['technician_id'],
                    (string)$call['status'],
                    $note,
                    $user,
                    $expectedUpdatedAt
                );
                $_SESSION['success_message'] = 'Technician note added.';
                header('Location: ' . url('public/view_call.php?id=' . $id));
                exit;
            } catch (InvalidArgumentException $exception) {
                $errors['form'] = $exception->getMessage();
                Logger::warning('View call note validation failed', ['user_id' => $user['id'] ?? null, 'call_id' => $id, 'error' => $exception->getMessage()]);
            } catch (Throwable $exception) {
                $errors['form'] = 'Unable to add the technician note right now.';
                Logger::error('Unexpected error adding technician note from view', ['user_id' => $user['id'] ?? null, 'call_id' => $id, 'exception' => $exception->getMessage()]);
            }
        }
    } else {
        $errors['form'] = 'Unknown service call action.';
    }
}

$history = ServiceCall::findHistory($id);
$relatedCalls = ServiceCall::findRelatedCalls($id, $call['location'] ?? null);
$lastModifiedAt = $call['updated_at'] ?? $call['created_at'];
$lastModifiedBy = !empty($history) ? ($history[0]['changed_by_name'] ?? 'System') : 'System';
$success = trim((string)($_SESSION['success_message'] ?? ''));
unset($_SESSION['success_message']);

Template::render('pages/view_call', [
    'title' => 'View Service Call',
    'user' => $user,
    'call' => $call,
    'errors' => $errors,
    'success' => $success,
    'history' => $history,
    'relatedCalls' => $relatedCalls,
    'lastModifiedAt' => $lastModifiedAt,
    'lastModifiedBy' => $lastModifiedBy,
    'isTechnician' => $isTechnician,
    'canAddTechnicianNote' => $isAssignedTechnician,
    'canManage' => $canManage,
    'canSelfAssign' => $canSelfAssign,
    'canEditDetails' => $canEditDetails,
    'backUrl' => $isTechnician ? url('public/technician_dashboard.php') : url('public/index.php'),
], 'layouts/app');