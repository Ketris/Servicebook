<?php
/** @var array<string, mixed> $user */
/** @var array<string, mixed> $values */
/** @var array<string, string> $filterOptions */
/** @var int[] $perPageOptions */
/** @var array<string, string> $errors */
/** @var string $success */
/** @var string $savedTheme */
?>
<div class="row justify-content-center">
    <div class="col-md-9 col-lg-7 col-xl-6">
        <div class="mb-4">
            <h1 class="h3">Settings</h1>
            <p class="text-muted mb-0">Personal preferences for <?= escape($user['display_name'] ?? '') ?>.</p>
        </div>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success" role="alert"><?= escape($success) ?></div>
        <?php endif; ?>
        <?php if (isset($errors['form'])): ?>
            <div class="alert alert-danger" role="alert"><?= escape($errors['form']) ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Preferences</div>
            <div class="card-body">
                <form method="post" action="<?= url('public/settings.php') ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_preferences">
                    <div class="mb-3">
                        <label class="form-label" for="theme">Theme</label>
                        <select id="theme" name="theme" class="form-select">
                            <option value="light" <?= $values['theme'] === 'light' ? 'selected' : '' ?>>Light</option>
                            <option value="dark" <?= $values['theme'] === 'dark' ? 'selected' : '' ?>>Dark</option>
                        </select>
                        <?php if (isset($errors['theme'])): ?>
                            <div class="invalid-feedback d-block"><?= escape($errors['theme']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="default_filter">Default call list filter</label>
                        <select id="default_filter" name="default_filter" class="form-select">
                            <?php foreach ($filterOptions as $key => $label): ?>
                                <option value="<?= escape($key) ?>" <?= $values['default_filter'] === $key ? 'selected' : '' ?>><?= escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['default_filter'])): ?>
                            <div class="invalid-feedback d-block"><?= escape($errors['default_filter']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="default_per_page">Calls per page</label>
                        <select id="default_per_page" name="default_per_page" class="form-select">
                            <?php foreach ($perPageOptions as $option): ?>
                                <option value="<?= (int)$option ?>" <?= (int)$values['default_per_page'] === $option ? 'selected' : '' ?>><?= (int)$option ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['default_per_page'])): ?>
                            <div class="invalid-feedback d-block"><?= escape($errors['default_per_page']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Save Preferences</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Change Password</div>
            <div class="card-body">
                <form method="post" action="<?= url('public/settings.php') ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label" for="current_password">Current password</label>
                        <input id="current_password" name="current_password" class="form-control" type="password" autocomplete="current-password" required>
                        <?php if (isset($errors['current_password'])): ?>
                            <div class="invalid-feedback d-block"><?= escape($errors['current_password']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new_password">New password</label>
                        <input id="new_password" name="new_password" class="form-control" type="password" autocomplete="new-password" minlength="10" required>
                        <div class="form-text">Use at least 10 characters.</div>
                        <?php if (isset($errors['new_password'])): ?>
                            <div class="invalid-feedback d-block"><?= escape($errors['new_password']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="confirm_password">Confirm new password</label>
                        <input id="confirm_password" name="confirm_password" class="form-control" type="password" autocomplete="new-password" minlength="10" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback d-block"><?= escape($errors['confirm_password']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    // The layout prefers the browser-stored theme; keep it in step with the saved preference.
    try { localStorage.setItem('sb-theme', <?= json_encode($savedTheme) ?>); } catch (e) {}
    document.documentElement.setAttribute('data-bs-theme', <?= json_encode($savedTheme) ?>);
</script>
