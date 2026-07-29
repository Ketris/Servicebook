<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireAdmin();
$user = Auth::currentUser();

$allowedEventTypes = ['all', 'service_call', 'system'];
$activityFilters = [
    'query' => trim((string)($_GET['query'] ?? '')),
    'actor' => trim((string)($_GET['actor'] ?? '')),
    'event_type' => trim((string)($_GET['event_type'] ?? 'all')),
    'field_name' => trim((string)($_GET['field_name'] ?? '')),
    'date_from' => trim((string)($_GET['date_from'] ?? '')),
    'date_to' => trim((string)($_GET['date_to'] ?? '')),
];

if (!in_array($activityFilters['event_type'], $allowedEventTypes, true)) {
    $activityFilters['event_type'] = 'all';
}

foreach (['date_from', 'date_to'] as $dateField) {
    if ($activityFilters[$dateField] === '') {
        continue;
    }
    $date = DateTime::createFromFormat('Y-m-d', $activityFilters[$dateField]);
    if (!($date instanceof DateTime) || $date->format('Y-m-d') !== $activityFilters[$dateField]) {
        $activityFilters[$dateField] = '';
    }
}

if ($activityFilters['date_from'] !== '' && $activityFilters['date_to'] !== '' && $activityFilters['date_from'] > $activityFilters['date_to']) {
    [$activityFilters['date_from'], $activityFilters['date_to']] = [$activityFilters['date_to'], $activityFilters['date_from']];
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

$totalActivity = ServiceCall::countRecentActivity($activityFilters);
$totalPages = max(1, (int)ceil($totalActivity / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;
$activity = ServiceCall::findRecentActivity($perPage, $offset, $activityFilters);

Template::render('pages/admin_activity', [
    'title' => 'Activity Log',
    'user' => $user,
    'activity' => $activity,
    'page' => $page,
    'perPage' => $perPage,
    'totalPages' => $totalPages,
    'totalActivity' => $totalActivity,
    'activityFilters' => $activityFilters,
], 'layouts/app');
