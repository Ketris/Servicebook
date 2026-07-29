<?php
/** @var array<int, array<string, mixed>> $users */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3">User Management</h1>
        <p class="text-muted mb-0">Create and edit Office Staff and Administrator accounts.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('admin/user_edit.php') ?>">New User</a>
</div>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Username</th>
                <th>Display Name</th>
                <th>Role</th>
                <th>Status</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= escape($user['username']) ?></td>
                    <td><?= escape($user['display_name']) ?></td>
                    <td><?= escape($user['role']) ?></td>
                    <td><?= $user['active'] ? 'Active' : 'Inactive' ?></td>
                    <td><?= escape(date('Y-m-d', strtotime($user['created_at']))) ?></td>
                    <td><a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/user_edit.php?id=' . $user['id']) ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
