<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Technician.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireLogin();
$user = Auth::currentUser();
$technicians = Technician::findAllActive();
$statuses = ServiceCall::getStatusOptions();
$priorities = ServiceCall::getPriorityOptions();

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
    'priority' => 'Normal',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        ServiceCall::save($data);
        header('Location: ' . url('public/index.php'));
        exit;
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
