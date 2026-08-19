<?php
/** @var array<string, string> $settings */
/** @var array<string, string> $errors */
/** @var string $successMessage */
/** @var array<int, array<string, mixed>> $backupFiles */
/** @var int $maxBackupUploadBytes */
$backupRetention = (string)($settings['backup_retention_days'] ?? '60');
$backupCadence = (string)($settings['backup_cadence'] ?? 'daily');
$backupAutoEnabled = (string)($settings['backup_auto_enabled'] ?? '1') === '1';
$savedViewsEnabled = (string)($settings['saved_views_enabled'] ?? '0') === '1';
$bulkManagementEnabled = (string)($settings['bulk_management_enabled'] ?? '0') === '1';
$lastBackupRun = trim((string)($settings['backup_last_run_at'] ?? ''));
$lastBackupAttemptAt = trim((string)($settings['backup_last_attempt_at'] ?? ''));
$lastBackupAttemptStatus = trim((string)($settings['backup_last_attempt_status'] ?? ''));
$lastBackupAttemptError = trim((string)($settings['backup_last_attempt_error'] ?? ''));
?>
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3">System Settings</h1>
                <p class="text-muted mb-0">Configure branding, backup cadence, retention, and restore options.</p>
            </div>
            <a class="btn btn-secondary" href="<?= url('admin/index.php') ?>">Back</a>
        </div>

        <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success" role="alert"><?= escape($successMessage) ?></div>
        <?php endif; ?>
        <?php if (isset($errors['form'])): ?>
            <div class="alert alert-danger" role="alert"><?= escape($errors['form']) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0">Branding and Backup Policy</h2>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_settings">

                    <div class="mb-3">
                        <label class="form-label" for="site_title">Site Title</label>
                        <input id="site_title" name="site_title" class="form-control" type="text" value="<?= escape($settings['site_title']) ?>" required>
                        <?php if (isset($errors['site_title'])): ?>
                            <div class="invalid-feedback d-block"><?= escape($errors['site_title']) ?></div>
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
                                <label class="form-check-label" for="remove_logo">Remove current title image</label>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label" for="saved_views_enabled">Saved Views (Beta)</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="1" id="saved_views_enabled" name="saved_views_enabled" <?= $savedViewsEnabled ? 'checked' : '' ?>>
                            <label class="form-check-label" for="saved_views_enabled">Enable Saved Views beta feature</label>
                        </div>
                        <div class="form-text">When disabled, users will not see or be able to use Saved Views on the calls page.</div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label" for="bulk_management_enabled">Bulk Management (Beta)</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="1" id="bulk_management_enabled" name="bulk_management_enabled" <?= $bulkManagementEnabled ? 'checked' : '' ?>>
                            <label class="form-check-label" for="bulk_management_enabled">Enable Bulk Management beta feature</label>
                        </div>
                        <div class="form-text">When disabled, users will not see or be able to bulk update calls on the calls page.</div>
                    </div>

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="backup_auto_enabled">Automatic Backups</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="backup_auto_enabled" name="backup_auto_enabled" <?= $backupAutoEnabled ? 'checked' : '' ?>>
                                <label class="form-check-label" for="backup_auto_enabled">Enable scheduled automatic backups</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="backup_cadence">Backup Cadence</label>
                            <select id="backup_cadence" name="backup_cadence" class="form-select">
                                <option value="daily" <?= $backupCadence === 'daily' ? 'selected' : '' ?>>Daily</option>
                                <option value="weekly" <?= $backupCadence === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                <option value="monthly" <?= $backupCadence === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="backup_retention_days">Retention (days)</label>
                            <input id="backup_retention_days" name="backup_retention_days" type="number" min="1" max="3650" class="form-control" value="<?= escape($backupRetention) ?>">
                            <?php if (isset($errors['backup_retention_days'])): ?>
                                <div class="invalid-feedback d-block"><?= escape($errors['backup_retention_days']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="small text-muted mt-2">
                        Last automatic backup run: <?= $lastBackupRun !== '' ? escape($lastBackupRun) : 'Never' ?>
                    </div>
                    <?php if ($lastBackupAttemptAt !== ''): ?>
                        <div class="small mt-1 <?= $lastBackupAttemptStatus === 'failed' ? 'text-danger' : 'text-muted' ?>">
                            Last automatic backup attempt: <?= escape($lastBackupAttemptAt) ?>
                            — <?= $lastBackupAttemptStatus === 'failed' ? 'Failed' : 'Succeeded' ?>
                            <?php if ($lastBackupAttemptStatus === 'failed' && $lastBackupAttemptError !== ''): ?>
                                (<?= escape($lastBackupAttemptError) ?>)
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">Manual Backups</h2>
                <form method="post" class="m-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create_backup">
                    <button type="submit" class="btn btn-sm btn-primary">Create Backup Now</button>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Created</th>
                                <th>Size</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($backupFiles)): ?>
                                <tr>
                                    <td colspan="4" class="text-muted text-center">No backups available yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($backupFiles as $backup): ?>
                                    <tr>
                                        <td><?= escape((string)($backup['name'] ?? '')) ?></td>
                                        <td><?= escape((string)($backup['modified_at'] ?? '')) ?></td>
                                        <td><?= escape(number_format(((int)($backup['size_bytes'] ?? 0)) / 1024, 1)) ?> KB</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <form method="post" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="download_backup">
                                                    <input type="hidden" name="backup_file" value="<?= escape((string)($backup['name'] ?? '')) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Download</button>
                                                </form>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Restore this backup now? This will overwrite current database data.');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="restore_backup">
                                                    <input type="hidden" name="backup_file" value="<?= escape((string)($backup['name'] ?? '')) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Restore</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0">Restore From Upload</h2>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="row g-3 align-items-end" onsubmit="return confirm('Restore from uploaded backup? This will overwrite current database data.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload_restore">
                    <div class="col-md-9">
                        <label class="form-label" for="backup_file">Compressed Backup File (.json.gz)</label>
                        <input id="backup_file" name="backup_file" type="file" class="form-control" accept=".gz,.json.gz" required>
                        <div class="form-text">Maximum upload: <?= escape((string)($maxBackupUploadBytes / (1024 * 1024))) ?> MB.</div>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-danger">Upload and Restore</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
