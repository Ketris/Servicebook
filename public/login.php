<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Template.php';

Auth::start();
$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (Auth::login($username, $password)) {
        header('Location: ' . url('public/index.php'));
        exit;
    }

    $error = 'Invalid username or password.';
}

Template::render('pages/login', [
    'title' => 'Servicebook Login',
    'error' => $error,
    'username' => $username,
], 'layouts/auth');
