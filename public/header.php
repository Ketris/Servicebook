<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Helpers.php';
Auth::requireLogin();
$user = Auth::currentUser();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Servicebook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-white">
<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= url('index.php') ?>">Servicebook</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= url('public/new_call.php') ?>">New Call</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('public/index.php') ?>">Open Calls</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('public/search.php') ?>">Search</a></li>
                <?php if ($user['role'] === 'Administrator'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Administration</a>
                        <ul class="dropdown-menu" aria-labelledby="adminMenu">
                            <li><a class="dropdown-item" href="<?= url('admin/index.php') ?>">Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?= url('admin/users.php') ?>">Users</a></li>
                            <li><a class="dropdown-item" href="<?= url('admin/settings.php') ?>">Settings</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center">
                <span class="me-3 text-muted">Signed in as <?= escape($user['display_name']) ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="<?= url('logout.php') ?>">Logout</a>
            </div>
        </div>
    </div>
</nav>
<div class="container-fluid py-4">
