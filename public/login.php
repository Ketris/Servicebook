<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Template.php';

Auth::start();
$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (Auth::login($username, $password)) {
        header('Location: ' . url('public/index.php'));
        exit;
    } else {
        $error = Auth::lastError();
    }
}

Template::render('pages/login', [
    'title' => 'Servicebook Login',
    'error' => $error,
    'username' => $username,
], 'layouts/auth');
