<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireAdmin();
$user = Auth::currentUser();
$activity = ServiceCall::findRecentActivity(250);

Template::render('pages/admin_activity', [
    'title' => 'Activity Log',
    'user' => $user,
    'activity' => $activity,
], 'layouts/app');
