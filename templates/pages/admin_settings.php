<?php
/** @var array<string, string> $settings */
/** @var array<string, string> $errors */
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3">System Settings</h1>
                <p class="text-muted mb-0">Configure basic application defaults.</p>
            </div>
            <a class="btn btn-secondary" href="<?= url('admin/index.php') ?>">Back</a>
        </div>
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Settings saved successfully.</div>
        <?php endif; ?>
        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label" for="site_title">Site Title</label>
                <input id="site_title" name="site_title" class="form-control" type="text" value="<?= escape($settings['site_title']) ?>" required>
                <?php if (isset($errors['site_title'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['site_title']) ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="default_priority">Default Priority</label>
                <select id="default_priority" name="default_priority" class="form-select">
                    <?php foreach (['Low', 'Normal', 'High', 'Emergency'] as $priority): ?>
                        <option value="<?= escape($priority) ?>" <?= $priority === $settings['default_priority'] ? 'selected' : '' ?>><?= escape($priority) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['default_priority'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['default_priority']) ?></div>
                <?php endif; ?>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
