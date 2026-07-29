<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireAdmin();
$user = Auth::currentUser();
$stats = ServiceCall::getSummaryStats();
$recentActivity = ServiceCall::findRecentActivity(8);

Template::render('pages/admin_dashboard', [
    'title' => 'Administration Dashboard',
    'user' => $user,
    'stats' => $stats,
    'recentActivity' => $recentActivity,
], 'layouts/app');
