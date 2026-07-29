<?php
/** @var int|null $id */
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3"><?= $id ? 'Edit User' : 'New User' ?></h1>
                <p class="text-muted mb-0">Manage login accounts and roles.</p>
            </div>
            <a class="btn btn-secondary" href="<?= url('admin/users.php') ?>">Back</a>
        </div>
        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input id="username" name="username" class="form-control" type="text" value="<?= escape($values['username']) ?>" required autofocus>
                <?php if (isset($errors['username'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['username']) ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="display_name">Display Name</label>
                <input id="display_name" name="display_name" class="form-control" type="text" value="<?= escape($values['display_name']) ?>" required>
                <?php if (isset($errors['display_name'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['display_name']) ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="role">Role</label>
                <select id="role" name="role" class="form-select">
                    <option value="Administrator" <?= $values['role'] === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                    <option value="Office Staff" <?= $values['role'] === 'Office Staff' ? 'selected' : '' ?>>Office Staff</option>
                    <option value="Technician" <?= $values['role'] === 'Technician' ? 'selected' : '' ?>>Technician</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="technician_id">Linked Technician</label>
                <select id="technician_id" name="technician_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach ($technicians as $technician): ?>
                        <option value="<?= escape((string)$technician['id']) ?>" <?= (string)$values['technician_id'] === (string)$technician['id'] ? 'selected' : '' ?>><?= escape($technician['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password <?= $id ? '(leave blank to keep current)' : '' ?></label>
                <input id="password" name="password" class="form-control" type="password" autocomplete="new-password">
                <?php if (isset($errors['password'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['password']) ?></div>
                <?php endif; ?>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= $values['active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="active">Active</label>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary"><?= $id ? 'Save User' : 'Create User' ?></button>
            </div>
        </form>
    </div>
</div>
