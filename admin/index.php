<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireAdmin();
$user = Auth::currentUser();
$totalCalls = count(ServiceCall::findAll());

Template::render('pages/admin_dashboard', [
    'title' => 'Administration Dashboard',
    'user' => $user,
    'totalCalls' => $totalCalls,
], 'layouts/app');
