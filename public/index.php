<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Technician.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireLogin();
$user = Auth::currentUser();
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'incomplete');
if (!in_array($filter, ['all', 'incomplete', 'unassigned'], true)) {
    $filter = 'incomplete';
}
$calls = ServiceCall::findAll($search, $filter);
$technicians = Technician::findAllActive();

Template::render('pages/index', [
    'title' => 'Service Calls',
    'user' => $user,
    'search' => $search,
    'filter' => $filter,
    'calls' => $calls,
    'technicians' => $technicians,
], 'layouts/app');
