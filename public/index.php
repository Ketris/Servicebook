<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Installation.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Technician.php';
require_once __DIR__ . '/../src/Template.php';

Installation::redirectToInstallerIfNeeded();
Auth::requireLogin();
$user = Auth::currentUser();
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'incomplete');
if (!in_array($filter, ['all', 'incomplete', 'unassigned', 'completed_today', 'completed_week'], true)) {
    $filter = 'incomplete';
}
$calls = ServiceCall::findAll($search, $filter);
$stats = ServiceCall::getSummaryStats();
$technicians = Technician::findAllActive();

Template::render('pages/index', [
    'title' => 'Service Calls',
    'user' => $user,
    'search' => $search,
    'filter' => $filter,
    'calls' => $calls,
    'stats' => $stats,
    'technicians' => $technicians,
], 'layouts/app');
