<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/ReusableRecord.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/User.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireLogin();
$user = Auth::currentUser();
$technicians = User::findAllActiveTechnicians();
$recordSuggestions = ReusableRecord::getFormData();
$statuses = ServiceCall::getStatusOptions();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$call = ServiceCall::findById($id);
if (!$call) {
    header('Location: ' . url('public/index.php'));
    exit;
}

$isTechnician = ($user['role'] ?? '') === 'Technician';
$canDelete = in_array((string)($user['role'] ?? ''), ['Administrator', 'Office Staff'], true);
$canManage = !$isTechnician || (
    !empty($call['assigned_tech'])
    && !empty($user['technician_id'])
    && (int)$call['assigned_tech'] === (int)$user['technician_id']
);
$canSelfAssign = $isTechnician
    && !empty($user['technician_id'])
    && empty($call['assigned_tech'])
    && !in_array((string)$call['status'], ['Complete', 'Cancelled'], true);
$canEditDetails = !$isTechnician || $canManage || $canSelfAssign;

$errors = [];
$values = [
    'received_date' => date('Y-m-d\TH:i', strtotime($call['received_date'])),
    'customer' => $call['customer'],
    'location' => $call['location'],
    'contact' => $call['contact'],
    'phone' => $call['phone'],
    'email' => $call['email'],
    'po_number' => $call['po_number'],
    'reported_issue' => $call['reported_issue'],
    'internal_notes' => $call['internal_notes'],
    'assigned_tech' => $call['assigned_tech'],
    'status' => $call['status'],
    'technician_note' => '',
    'expected_updated_at' => (string)($call['updated_at'] ?? $call['created_at'] ?? ''),
];
$history = ServiceCall::findHistory($id);
$relatedCalls = ServiceCall::findRelatedCalls($id, $call['location'] ?? null);
$lastModifiedAt = $call['updated_at'] ?? $call['created_at'];
$lastModifiedBy = !empty($history) ? ($history[0]['changed_by_name'] ?? 'System') : 'System';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? 'save_call'));
    $expectedUpdatedAt = trim((string)($_POST['expected_updated_at'] ?? ($call['updated_at'] ?? $call['created_at'] ?? '')));
    $values['expected_updated_at'] = $expectedUpdatedAt;

    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please reload and try again.';
    } else {
        if ($action === 'delete_call') {
            if (!$canDelete) {
                $errors['form'] = 'You are not allowed to delete this job.';
            } else {
                try {
                    $deleteResult = ServiceCall::delete($id, $user);
                    if ($deleteResult === 'deleted') {
                        $_SESSION['success_message'] = 'Service call permanently deleted.';
                    } else {
                        $_SESSION['success_message'] = 'Service call was not the newest entry, so it was marked Cancelled.';
                    }
                    header('Location: ' . url('public/index.php'));
                    exit;
                } catch (InvalidArgumentException $exception) {
                    $errors['form'] = $exception->getMessage();
                    Logger::warning('Service call delete validation failed', [
                        'user_id' => $user['id'] ?? null,
                        'call_id' => $id,
                        'error' => $exception->getMessage(),
                    ]);
                } catch (Throwable $exception) {
                    $errors['form'] = 'Unable to delete this service call right now.';
                    Logger::error('Unexpected error deleting service call', [
                        'user_id' => $user['id'] ?? null,
                        'call_id' => $id,
                        'exception' => $exception->getMessage(),
                    ]);
                }
            }
        } elseif ($isTechnician && isset($_POST['claim_job'])) {
            if (!$canSelfAssign) {
                $errors['claim_job'] = 'This job cannot be claimed right now.';
            } else {
                try {
                    ServiceCall::claimForTechnician($id, (int)($user['technician_id'] ?? 0), $user, $expectedUpdatedAt);
                    header('Location: ' . url('public/technician_dashboard.php'));
                    exit;
                } catch (InvalidArgumentException $exception) {
                    $errors['form'] = $exception->getMessage();
                    Logger::warning('Claim job validation failed', [
                        'user_id' => $user['id'] ?? null,
                        'call_id' => $id,
                        'error' => $exception->getMessage(),
                    ]);
                } catch (Throwable $exception) {
                    $errors['form'] = 'Unable to save service call right now.';
                    Logger::error('Unexpected error claiming service call', [
                        'user_id' => $user['id'] ?? null,
                        'call_id' => $id,
                        'exception' => $exception->getMessage(),
                    ]);
                }
            }
        } elseif ($isTechnician && $canManage) {
            $values['status'] = trim($_POST['status'] ?? $values['status']);
            $values['technician_note'] = trim($_POST['technician_note'] ?? '');

            if (empty($errors)) {
                try {
                    ServiceCall::updateAssignedTechnicianJob(
                        $id,
                        (int)($user['technician_id'] ?? 0),
                        $values['status'],
                        $values['technician_note'],
                        $user,
                        $expectedUpdatedAt
                    );
                    header('Location: ' . url('public/technician_dashboard.php'));
                    exit;
                } catch (InvalidArgumentException $exception) {
                    $errors['form'] = $exception->getMessage();
                    Logger::warning('Technician update validation failed', [
                        'user_id' => $user['id'] ?? null,
                        'call_id' => $id,
                        'error' => $exception->getMessage(),
                    ]);
                } catch (Throwable $exception) {
                    $errors['form'] = 'Unable to save service call right now.';
                    Logger::error('Unexpected error in technician service call update', [
                        'user_id' => $user['id'] ?? null,
                        'call_id' => $id,
                        'exception' => $exception->getMessage(),
                    ]);
                }
            }
        } else {
            if ($isTechnician && !$canEditDetails) {
                $errors['form'] = 'You are not allowed to edit this job.';
            }

            foreach ($values as $key => $default) {
                $values[$key] = trim($_POST[$key] ?? $default);
            }

            if ($values['customer'] === '') {
                $errors['customer'] = 'Customer name is required.';
            }
            if ($values['location'] === '') {
                $errors['location'] = 'Location is required.';
            }
            if ($values['reported_issue'] === '') {
                $errors['reported_issue'] = 'Reported issue is required.';
            }
            if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email must be a valid address.';
            }
            if ($values['phone'] !== '' && !preg_match('/^[0-9+()\-.\s]{7,30}$/', $values['phone'])) {
                $errors['phone'] = 'Phone number format is invalid.';
            }
            if (mb_strlen($values['customer']) > 255) {
                $errors['customer'] = 'Customer name cannot exceed 255 characters.';
            }
            if (mb_strlen($values['location']) > 255) {
                $errors['location'] = 'Location cannot exceed 255 characters.';
            }
            if (mb_strlen($values['contact']) > 150) {
                $errors['contact'] = 'Customer contact cannot exceed 150 characters.';
            }
            if (mb_strlen($values['phone']) > 100) {
                $errors['phone'] = 'Phone number cannot exceed 100 characters.';
            }
            if (mb_strlen($values['email']) > 255) {
                $errors['email'] = 'Email cannot exceed 255 characters.';
            }
            if (mb_strlen($values['po_number']) > 100) {
                $errors['po_number'] = 'PO number cannot exceed 100 characters.';
            }
            if (!in_array($values['status'], $statuses, true)) {
                $errors['status'] = 'Invalid status selected.';
            }

            if (empty($errors)) {
                $data = $values;
                $data['created_by'] = $call['created_by'];
                $data['assigned_tech'] = $data['assigned_tech'] ?: null;
                if ($values['technician_note'] !== '') {
                    $data['internal_notes'] = ServiceCall::appendTechnicianNote(
                        (string)($values['internal_notes'] ?? ''),
                        $values['technician_note'],
                        $user
                    );
                }
                try {
                    ServiceCall::save($data, $id, $user, $expectedUpdatedAt);
                    header('Location: ' . url('public/index.php'));
                    exit;
                } catch (InvalidArgumentException $exception) {
                    $errors['form'] = $exception->getMessage();
                    Logger::warning('Service call edit validation failed', [
                        'user_id' => $user['id'] ?? null,
                        'call_id' => $id,
                        'error' => $exception->getMessage(),
                    ]);
                } catch (Throwable $exception) {
                    $errors['form'] = 'Unable to save service call right now.';
                    Logger::error('Unexpected error updating service call', [
                        'user_id' => $user['id'] ?? null,
                        'call_id' => $id,
                        'exception' => $exception->getMessage(),
                    ]);
                }
            }
        }
    }
}

Template::render('pages/edit_call', [
    'title' => 'Edit Service Call',
    'user' => $user,
    'call' => $call,
    'technicians' => $technicians,
    'statuses' => $statuses,
    'errors' => $errors,
    'values' => $values,
    'history' => $history,
    'relatedCalls' => $relatedCalls,
    'lastModifiedAt' => $lastModifiedAt,
    'lastModifiedBy' => $lastModifiedBy,
    'isTechnician' => $isTechnician,
    'canDelete' => $canDelete,
    'canManage' => $canManage,
    'canSelfAssign' => $canSelfAssign,
    'canEditDetails' => $canEditDetails,
    'backUrl' => $isTechnician ? url('public/technician_dashboard.php') : url('public/index.php'),
    'recordSuggestions' => $recordSuggestions,
], 'layouts/app');
