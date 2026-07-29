<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/AppSettings.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Technician.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireLogin();
$user = Auth::currentUser();
$technicians = Technician::findAllActive();
$statuses = ServiceCall::getStatusOptions();
$priorities = ServiceCall::getPriorityOptions();
$defaultPriority = AppSettings::get('default_priority');
if (!in_array($defaultPriority, $priorities, true)) {
    $defaultPriority = 'Normal';
}

$errors = [];
$values = [
    'received_date' => date('Y-m-d\TH:i'),
    'customer' => '',
    'location' => '',
    'contact' => '',
    'phone' => '',
    'email' => '',
    'po_number' => '',
    'reported_issue' => '',
    'internal_notes' => '',
    'assigned_tech' => '',
    'status' => 'New',
    'priority' => $defaultPriority,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please reload and try again.';
    } else {
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
        if (!in_array($values['priority'], $priorities, true)) {
            $errors['priority'] = 'Invalid priority selected.';
        }

        if (empty($errors)) {
            $data = $values;
            $data['created_by'] = $user['id'];
            $data['assigned_tech'] = $data['assigned_tech'] ?: null;
            try {
                ServiceCall::save($data);
                header('Location: ' . url('public/index.php'));
                exit;
            } catch (InvalidArgumentException $exception) {
                $errors['form'] = $exception->getMessage();
                Logger::warning('New call validation failed', [
                    'user_id' => $user['id'] ?? null,
                    'error' => $exception->getMessage(),
                ]);
            } catch (Throwable $exception) {
                $errors['form'] = 'Unable to save service call right now.';
                Logger::error('Unexpected error creating service call', [
                    'user_id' => $user['id'] ?? null,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
    }
}

Template::render('pages/new_call', [
    'title' => 'New Service Call',
    'user' => $user,
    'technicians' => $technicians,
    'statuses' => $statuses,
    'priorities' => $priorities,
    'errors' => $errors,
    'values' => $values,
], 'layouts/app');
