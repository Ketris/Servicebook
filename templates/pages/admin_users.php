<?php
/** @var array<int, array<string, mixed>> $users */
/** @var string $success */
/** @var string $error */
/** @var string $temporaryPassword */
/** @var int|null $temporaryPasswordUserId */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">User Management</h1>
        <p class="text-muted mb-0">Create and edit Office Staff and Administrator accounts.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('admin/user_edit.php') ?>">New User</a>
</div>
<?php if (!empty($success)): ?>
    <div class="alert alert-success" role="alert"><?= escape($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert"><?= escape($error) ?></div>
<?php endif; ?>
<?php if (!empty($temporaryPassword)): ?>
    <div class="alert alert-warning" role="alert">
        <div><strong>Temporary password</strong>: <?= escape($temporaryPassword) ?></div>
        <?php if (!empty($temporaryPasswordUserId)): ?>
            <div class="small text-muted">User ID: <?= escape((string)$temporaryPasswordUserId) ?></div>
        <?php endif; ?>
        <div class="small">Share this securely and have the user change it immediately.</div>
    </div>
<?php endif; ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Username</th>
                        <th>Display Name</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Technician</th>
                        <th>Status</th>
                        <th>Lockout</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
            <?php foreach ($users as $user): ?>
                <?php $isLocked = !empty($user['lock_until']) && strtotime((string)$user['lock_until']) > time(); ?>
                <tr>
                    <td><?= escape($user['username']) ?></td>
                    <td><?= escape($user['display_name']) ?></td>
                    <td><?= escape($user['phone'] ?? '') ?></td>
                    <td><?= escape($user['role']) ?></td>
                    <td><?= !empty($user['is_technician']) ? 'Yes' : 'No' ?></td>
                    <td><?= $user['active'] ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <?php if ($isLocked): ?>
                            <span class="badge text-bg-danger">Locked until <?= escape(format_datetime((string)$user['lock_until'])) ?></span>
                        <?php else: ?>
                            <span class="badge text-bg-success">No lock</span>
                        <?php endif; ?>
                        <div class="small text-muted">Failed attempts: <?= escape((string)($user['failed_login_attempts'] ?? 0)) ?></div>
                    </td>
                    <td><?= escape(format_date($user['created_at'])) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/user_edit.php?id=' . $user['id']) ?>">Edit</a>
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="unlock">
                            <input type="hidden" name="user_id" value="<?= escape((string)$user['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-warning" <?= !$isLocked && (int)($user['failed_login_attempts'] ?? 0) === 0 ? 'disabled' : '' ?>>Unlock</button>
                        </form>
                        <form method="post" class="d-inline" onsubmit="return confirm('Reset password for this user?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="user_id" value="<?= escape((string)$user['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Reset Password</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
            </table>
        </div>
    </div>
</div>
