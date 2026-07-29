<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireLogin();
$user = Auth::currentUser();
$search = trim($_GET['search'] ?? '');
$calls = ServiceCall::findAll($search);

Template::render('pages/search', [
    'title' => 'Search Service Calls',
    'user' => $user,
    'search' => $search,
    'calls' => $calls,
], 'layouts/app');
