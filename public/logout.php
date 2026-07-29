<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Helpers.php';
Auth::logout();
header('Location: ' . url('public/login.php'));
exit;
