<?php
/** @var array<string, string> $settings */
/** @var array<string, string> $errors */
/** @var string[] $priority_options */
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3">System Settings</h1>
                <p class="text-muted mb-0">Configure the app title and default values used when creating new calls.</p>
            </div>
            <a class="btn btn-secondary" href="<?= url('admin/index.php') ?>">Back</a>
        </div>
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Settings saved successfully.</div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <?php if (isset($errors['form'])): ?>
                <div class="alert alert-danger" role="alert"><?= escape($errors['form']) ?></div>
            <?php endif; ?>
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
                    <?php foreach ($priority_options as $priority): ?>
                        <option value="<?= escape($priority) ?>" <?= $priority === $settings['default_priority'] ? 'selected' : '' ?>><?= escape($priority) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['default_priority'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['default_priority']) ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="site_logo">Title Image (Logo)</label>
                <input id="site_logo" name="site_logo" class="form-control" type="file" accept="image/png,image/jpeg,image/gif,image/webp">
                <div class="form-text">Optional. PNG, JPG, GIF, or WEBP. Max 2 MB. When uploaded, this image replaces the text site title.</div>
                <?php if (isset($errors['site_logo'])): ?>
                    <div class="invalid-feedback d-block"><?= escape($errors['site_logo']) ?></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($settings['site_logo_path'])): ?>
                <div class="mb-3">
                    <div class="border rounded p-3 bg-light">
                        <div class="small text-muted mb-2">Current Logo Preview</div>
                        <img src="<?= escape(url($settings['site_logo_path'])) ?>" alt="Current title image" style="max-height: 72px; max-width: 100%;">
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" value="1" id="remove_logo" name="remove_logo">
                        <label class="form-check-label" for="remove_logo">
                            Remove current title image
                        </label>
                    </div>
                </div>
            <?php endif; ?>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
