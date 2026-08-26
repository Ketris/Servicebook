<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/User.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireLogin();
$user = Auth::currentUser();
$isAdministrator = ($user['role'] ?? '') === 'Administrator';
$ownTechnicianId = (int)($user['technician_id'] ?? 0);

if (!$isAdministrator && $ownTechnicianId <= 0) {
    header('Location: ' . url('public/index.php'));
    exit;
}

$technicianOptions = $isAdministrator ? User::findAllActiveTechnicians() : [];
$technicianId = $isAdministrator
    ? (int)($_GET['technician_id'] ?? $ownTechnicianId)
    : $ownTechnicianId;

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
        $expectedUpdatedAt = trim((string)($_POST['expected_updated_at'] ?? ''));
        // Admins act on behalf of whichever technician queue they are viewing.
        $actingTechnicianId = $isAdministrator
            ? (int)($_POST['viewing_technician_id'] ?? $technicianId)
            : $ownTechnicianId;
        $technicianId = $actingTechnicianId;
        $redirectSuffix = $isAdministrator && $actingTechnicianId > 0 ? '&technician_id=' . $actingTechnicianId : '';

        try {
            if ($action === 'claim_job') {
                ServiceCall::claimForTechnician($callId, $actingTechnicianId, $user, $expectedUpdatedAt);
                header('Location: ' . url('public/technician_dashboard.php?notice=claimed' . $redirectSuffix));
                exit;
            } elseif ($action === 'update_job') {
                $status = trim($_POST['status'] ?? '');
                $note = trim($_POST['technician_note'] ?? '');
                ServiceCall::updateAssignedTechnicianJob($callId, $actingTechnicianId, $status, $note, $user, $expectedUpdatedAt);
                header('Location: ' . url('public/technician_dashboard.php?notice=updated' . $redirectSuffix));
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

$viewingTechnicianName = '';
foreach ($technicianOptions as $technician) {
    if ((int)$technician['id'] === $technicianId) {
        $viewingTechnicianName = (string)$technician['name'];
        break;
    }
}

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
    'isAdministrator' => $isAdministrator,
    'technicianOptions' => $technicianOptions,
    'technicianId' => $technicianId,
    'isViewingOwnQueue' => $technicianId > 0 && $technicianId === $ownTechnicianId,
    'viewingTechnicianName' => $viewingTechnicianName,
], 'layouts/app');

