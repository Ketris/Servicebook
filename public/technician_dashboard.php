<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireLogin();
$user = Auth::currentUser();
if (($user['role'] ?? '') !== 'Technician') {
    header('Location: ' . url('public/index.php'));
    exit;
}

$technicianId = (int)($user['technician_id'] ?? 0);
$statuses = ServiceCall::getStatusOptions();
$errors = [];
$successMessage = '';

$notice = trim($_GET['notice'] ?? '');
if ($notice === 'claimed') {
    $successMessage = 'Job claimed successfully.';
} elseif ($notice === 'updated') {
    $successMessage = 'Job updated successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please reload and try again.';
    } else {
        $action = trim($_POST['action'] ?? '');
        $callId = (int)($_POST['call_id'] ?? 0);

        try {
            if ($action === 'claim_job') {
                ServiceCall::claimForTechnician($callId, $technicianId, $user);
                header('Location: ' . url('public/technician_dashboard.php?notice=claimed'));
                exit;
            } elseif ($action === 'update_job') {
                $status = trim($_POST['status'] ?? '');
                $note = trim($_POST['technician_note'] ?? '');
                ServiceCall::updateAssignedTechnicianJob($callId, $technicianId, $status, $note, $user);
                header('Location: ' . url('public/technician_dashboard.php?notice=updated'));
                exit;
            } else {
                $errors['form'] = 'Unknown dashboard action.';
            }
        } catch (InvalidArgumentException $exception) {
            $errors['form'] = $exception->getMessage();
            Logger::warning('Technician dashboard validation failed', [
                'user_id' => $user['id'] ?? null,
                'call_id' => $callId,
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            $errors['form'] = 'Unable to update the job right now.';
            Logger::error('Unexpected technician dashboard error', [
                'user_id' => $user['id'] ?? null,
                'call_id' => $callId,
                'action' => $action,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}

$stats = ServiceCall::getTechnicianDashboardStats($technicianId);
$activeJobs = ServiceCall::findActiveByTechnician($technicianId);
$claimableJobs = $technicianId > 0 ? ServiceCall::findClaimableOpenJobs() : [];
$workload = ServiceCall::getTechnicianWorkloadSummary();

Template::render('pages/technician_dashboard', [
    'title' => 'My Jobs',
    'user' => $user,
    'statuses' => $statuses,
    'stats' => $stats,
    'activeJobs' => $activeJobs,
    'claimableJobs' => $claimableJobs,
    'errors' => $errors,
    'successMessage' => $successMessage,
    'technicianLinked' => $technicianId > 0,
    'workload' => $workload,
], 'layouts/app');
