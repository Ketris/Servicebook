<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';
require_once __DIR__ . '/../src/User.php';

Auth::requireLogin();
$user = Auth::currentUser();

$hasCriteria = false;
$criteriaFields = [
    'job_number', 'customer', 'location', 'city', 'contact', 'phone', 'email', 'po_number',
    'reported_issue', 'internal_notes', 'status', 'assigned_tech',
    'received_from', 'received_to', 'updated_from', 'updated_to',
];
$filters = [];
foreach ($criteriaFields as $field) {
    $value = trim((string)($_GET[$field] ?? ''));
    $filters[$field] = $value;
    if ($value !== '') {
        $hasCriteria = true;
    }
}

foreach (['received_from', 'received_to', 'updated_from', 'updated_to'] as $dateField) {
    if ($filters[$dateField] === '') {
        continue;
    }
    $date = DateTime::createFromFormat('Y-m-d', $filters[$dateField]);
    if (!($date instanceof DateTime) || $date->format('Y-m-d') !== $filters[$dateField]) {
        $filters[$dateField] = '';
    }
}

$allowedPerPage = [25, 50, 100, 250];
$perPage = (int)($_GET['per_page'] ?? 50);
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 50;
}
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}

$allowedSortFields = ServiceCall::getSortableFields();
$sort = trim((string)($_GET['sort'] ?? 'job_number'));
if (!in_array($sort, $allowedSortFields, true)) {
    $sort = 'job_number';
}
$dir = strtolower(trim((string)($_GET['dir'] ?? 'desc')));
if (!in_array($dir, ['asc', 'desc'], true)) {
    $dir = 'desc';
}

$calls = [];
$totalCalls = 0;
$totalPages = 1;
if ($hasCriteria) {
    $totalCalls = ServiceCall::countAdvanced($filters);
    $totalPages = max(1, (int)ceil($totalCalls / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;
    $calls = ServiceCall::findAdvanced($filters, $perPage, $offset, $sort, $dir);
}

$technicians = User::findAllActiveTechnicians();

Template::render('pages/advanced_search', [
    'title' => 'Advanced Search',
    'user' => $user,
    'filters' => $filters,
    'hasCriteria' => $hasCriteria,
    'calls' => $calls,
    'page' => $page,
    'perPage' => $perPage,
    'allowedPerPage' => $allowedPerPage,
    'totalCalls' => $totalCalls,
    'totalPages' => $totalPages,
    'sort' => $sort,
    'dir' => $dir,
    'statusOptions' => ServiceCall::getStatusOptions(),
    'technicians' => $technicians,
], 'layouts/app');
