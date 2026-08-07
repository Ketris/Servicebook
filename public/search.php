<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireLogin();
$user = Auth::currentUser();
$search = trim($_GET['search'] ?? '');
$allowedPerPage = [25, 50, 100, 250];
$perPage = (int)($_GET['per_page'] ?? 50);
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 50;
}
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}

$totalCalls = ServiceCall::countAll($search, 'all');
$totalPages = max(1, (int)ceil($totalCalls / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$calls = ServiceCall::findAll($search, 'all', $perPage, $offset);

Template::render('pages/search', [
    'title' => 'Search Service Calls',
    'user' => $user,
    'search' => $search,
    'calls' => $calls,
    'page' => $page,
    'perPage' => $perPage,
    'allowedPerPage' => $allowedPerPage,
    'totalCalls' => $totalCalls,
    'totalPages' => $totalPages,
], 'layouts/app');
