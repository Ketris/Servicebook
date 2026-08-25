<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireLogin();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$call = ServiceCall::findById($id);
if (!$call) {
    header('Location: ' . url('public/index.php'));
    exit;
}

Template::render('pages/print_call', [
    'title' => 'Print Service Call',
    'call' => $call,
    'history' => ServiceCall::findHistory($id),
    'printedAt' => date('Y-m-d H:i'),
]);