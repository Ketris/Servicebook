<?php
/** @var int|null $id */
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3"><?= $id ? 'Edit Technician' : 'New Technician' ?></h1>
                <p class="text-muted mb-0">Manage technician availability and contact info.</p>
            </div>
            <a class="btn btn-secondary" href="<?= url('admin/technicians.php') ?>">Back</a>
        </div>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <?php if (isset($errors['form'])): ?>
                <div class="alert alert-danger" role="alert"><?= escape($errors['form']) ?></div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label" for="name">Name</label>
                <input id="name" name="name" class="form-control" type="text" value="<?= escape($values['name']) ?>" required autofocus maxlength="150">
                <?php if (isset($errors['name'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['name']) ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="phone">Phone</label>
                <input id="phone" name="phone" class="form-control" type="text" value="<?= escape($values['phone']) ?>" maxlength="100">
                <?php if (isset($errors['phone'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['phone']) ?></div>
                <?php endif; ?>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= $values['active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="active">Active</label>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary"><?= $id ? 'Save Technician' : 'Create Technician' ?></button>
            </div>
        </form>
    </div>
</div>
