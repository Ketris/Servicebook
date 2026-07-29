<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/User.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireAdmin();
$user = Auth::currentUser();
$users = User::findAll();

Template::render('pages/admin_users', [
    'title' => 'User Management',
    'user' => $user,
    'users' => $users,
], 'layouts/app');
