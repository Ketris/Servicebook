<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Technician.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireAdmin();
$user = Auth::currentUser();
$technicians = Technician::findAll();

Template::render('pages/admin_technicians', [
    'title' => 'Technician Management',
    'user' => $user,
    'technicians' => $technicians,
], 'layouts/app');
