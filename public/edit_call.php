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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$call = ServiceCall::findById($id);
if (!$call) {
    header('Location: ' . url('public/index.php'));
    exit;
}

$isTechnician = ($user['role'] ?? '') === 'Technician';
$canManage = !$isTechnician || ((int)($call['assigned_tech'] ?? 0) === (int)($user['technician_id'] ?? 0));
$canSelfAssign = $isTechnician && !empty($user['technician_id']) && empty($call['assigned_tech']) && $call['status'] !== 'Complete';
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
    'priority' => $call['priority'],
    'technician_note' => '',
];
$history = ServiceCall::findHistory($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isTechnician && isset($_POST['claim_job'])) {
        if (!$canSelfAssign) {
            $errors['claim_job'] = 'This job cannot be claimed right now.';
        } else {
            $claimedTechId = (int)($user['technician_id'] ?? 0);
            $data = $values;
            $data['customer'] = $call['customer'];
            $data['location'] = $call['location'];
            $data['contact'] = $call['contact'];
            $data['phone'] = $call['phone'];
            $data['email'] = $call['email'];
            $data['po_number'] = $call['po_number'];
            $data['reported_issue'] = $call['reported_issue'];
            $data['received_date'] = date('Y-m-d\TH:i', strtotime($call['received_date']));
            $data['priority'] = $call['priority'];
            $data['assigned_tech'] = $claimedTechId > 0 ? $claimedTechId : null;
            $data['status'] = $call['status'];
            $data['created_by'] = $call['created_by'];
            $data['internal_notes'] = $call['internal_notes'] ?? '';
            $timestamp = date('Y-m-d H:i');
            $prefix = $user['display_name'] ?? 'Technician';
            $data['internal_notes'] = trim($data['internal_notes'] . "\n\n[{$timestamp}] {$prefix}: claimed this job");
            ServiceCall::save($data, $id, $user);
            header('Location: ' . url('public/index.php'));
            exit;
        }
    } elseif ($isTechnician && $canManage) {
        $values['status'] = trim($_POST['status'] ?? $values['status']);
        $values['technician_note'] = trim($_POST['technician_note'] ?? '');

        if (!in_array($values['status'], $statuses, true)) {
            $errors['status'] = 'Invalid status selected.';
        }

        if (empty($errors)) {
            $data = $values;
            $data['customer'] = $call['customer'];
            $data['location'] = $call['location'];
            $data['contact'] = $call['contact'];
            $data['phone'] = $call['phone'];
            $data['email'] = $data['email'] ?? $call['email'];
            $data['po_number'] = $call['po_number'];
            $data['reported_issue'] = $call['reported_issue'];
            $data['received_date'] = date('Y-m-d\TH:i', strtotime($call['received_date']));
            $data['priority'] = $call['priority'];
            $data['assigned_tech'] = $call['assigned_tech'];
            $data['created_by'] = $call['created_by'];
            $data['internal_notes'] = $call['internal_notes'] ?? '';
            if ($values['technician_note'] !== '') {
                $timestamp = date('Y-m-d H:i');
                $prefix = $user['display_name'] ?? 'Technician';
                $data['internal_notes'] = trim($data['internal_notes'] . "\n\n[{$timestamp}] {$prefix}: {$values['technician_note']}");
            }
            ServiceCall::save($data, $id, $user);
            header('Location: ' . url('public/index.php'));
            exit;
        }
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
        if (!in_array($values['status'], $statuses, true)) {
            $errors['status'] = 'Invalid status selected.';
        }
        if (!in_array($values['priority'], $priorities, true)) {
            $errors['priority'] = 'Invalid priority selected.';
        }

        if (empty($errors)) {
            $data = $values;
            $data['created_by'] = $call['created_by'];
            $data['assigned_tech'] = $data['assigned_tech'] ?: null;
            ServiceCall::save($data, $id, $user);
            header('Location: ' . url('public/index.php'));
            exit;
        }
    }
}

Template::render('pages/edit_call', [
    'title' => 'Edit Service Call',
    'user' => $user,
    'call' => $call,
    'technicians' => $technicians,
    'statuses' => $statuses,
    'priorities' => $priorities,
    'errors' => $errors,
    'values' => $values,
    'history' => $history,
    'isTechnician' => $isTechnician,
    'canManage' => $canManage,
    'canSelfAssign' => $canSelfAssign,
    'canEditDetails' => $canEditDetails,
], 'layouts/app');
