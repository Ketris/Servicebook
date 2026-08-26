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
            <?= csrf_field() ?>
            <?php if (isset($errors['form'])): ?>
                <div class="alert alert-danger" role="alert"><?= escape($errors['form']) ?></div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input id="username" name="username" class="form-control" type="text" value="<?= escape($values['username']) ?>" required autofocus maxlength="100">
                <?php if (isset($errors['username'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['username']) ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="display_name">Display Name</label>
                <input id="display_name" name="display_name" class="form-control" type="text" value="<?= escape($values['display_name']) ?>" required maxlength="150">
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
                <?php if (isset($errors['role'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['role']) ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="phone">Phone</label>
                <input id="phone" name="phone" class="form-control" type="text" value="<?= escape($values['phone']) ?>" maxlength="100">
                <?php if (isset($errors['phone'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['phone']) ?></div>
                <?php endif; ?>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="is_technician" name="is_technician" value="1" <?= !empty($values['is_technician']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_technician">Technician</label>
                <div class="form-text">Grants access to the "My Dashboard" queue and makes this person assignable to service calls.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password <?= $id ? '(leave blank to keep current)' : '' ?></label>
                <input id="password" name="password" class="form-control" type="password" autocomplete="new-password" minlength="10">
                <div class="form-text">Use at least 10 characters.</div>
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
