<?php
$user = $user ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= url('public/index.php') ?>">Servicebook</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= url('public/new_call.php') ?>">New Call</a></li>
                <?php if (($user['role'] ?? '') === 'Technician'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('public/technician_dashboard.php') ?>">My Jobs</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('public/index.php') ?>">All Calls</a></li>
                <?php if (($user['role'] ?? '') === 'Administrator'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Administration</a>
                        <ul class="dropdown-menu" aria-labelledby="adminMenu">
                            <li><a class="dropdown-item" href="<?= url('admin/index.php') ?>">Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?= url('admin/activity.php') ?>">Activity Log</a></li>
                            <li><a class="dropdown-item" href="<?= url('admin/users.php') ?>">Users</a></li>
                            <li><a class="dropdown-item" href="<?= url('admin/technicians.php') ?>">Technicians</a></li>
                            <li><a class="dropdown-item" href="<?= url('admin/settings.php') ?>">Settings</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center">
                <span class="me-3 text-muted small">Signed in as <?= escape($user['display_name'] ?? '') ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="<?= url('public/logout.php') ?>">Logout</a>
            </div>
        </div>
    </div>
</nav>
