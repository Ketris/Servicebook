<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/AppSettings.php';
require_once __DIR__ . '/../src/BackupManager.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireAdmin();
$user = Auth::currentUser();

$pdo = Database::getConnection();
$errors = [];
$settings = AppSettings::all();
$originalSettings = $settings;
$projectRoot = dirname(__DIR__);
$logoDirectoryAbsolute = $projectRoot . '/public/assets/branding';
$logoDirectoryRelative = 'public/assets/branding';
$allowedLogoMimeTypes = [
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];
$allowedBackupCadence = ['daily', 'weekly', 'monthly'];
$maxLogoBytes = 2 * 1024 * 1024;
$maxBackupUploadBytes = BackupManager::maxUploadBytes();

$successMessage = trim((string)($_SESSION['admin_settings_success'] ?? ''));
unset($_SESSION['admin_settings_success']);

$resolveStoredLogoAbsolutePath = static function (string $storedPath) use ($projectRoot): string {
    $normalized = ltrim(str_replace('\\', '/', trim($storedPath)), '/');
    if ($normalized === '') {
        return '';
    }

    if (preg_match('/^public\/assets\/branding\/[A-Za-z0-9._-]+$/', $normalized)) {
        return $projectRoot . '/' . $normalized;
    }
    if (preg_match('/^assets\/branding\/[A-Za-z0-9._-]+$/', $normalized)) {
        return $projectRoot . '/public/' . $normalized;
    }

    return '';
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please reload and try again.';
    } else {
        $action = trim((string)($_POST['action'] ?? 'save_settings'));

        try {
            if ($action === 'create_backup') {
                $retentionDays = max(1, min((int)($settings['backup_retention_days'] ?? '60'), 3650));
                $backup = BackupManager::createBackup('manual', $retentionDays);

                Logger::info('Admin created manual backup', [
                    'admin_user_id' => $user['id'] ?? null,
                    'filename' => $backup['filename'] ?? null,
                ]);
                ServiceCall::logSystemEvent(
                    $user,
                    'backup_create',
                    null,
                    (string)($backup['filename'] ?? ''),
                    'Manual backup created'
                );

                $_SESSION['admin_settings_success'] = 'Manual backup created: ' . ($backup['filename'] ?? 'backup file');
                header('Location: ' . url('admin/settings.php'));
                exit;
            }

            if ($action === 'download_backup') {
                $backupFile = trim((string)($_POST['backup_file'] ?? ''));
                BackupManager::streamBackupDownload($backupFile);
                exit;
            }

            if ($action === 'restore_backup') {
                $backupFile = trim((string)($_POST['backup_file'] ?? ''));
                $result = BackupManager::restoreFromStoredBackup($backupFile);

                ServiceCall::logSystemEvent(
                    $user,
                    'backup_restore',
                    null,
                    $backupFile,
                    'Backup restored from stored file'
                );

                $_SESSION['admin_settings_success'] = 'Backup restored from ' . $backupFile
                    . ' (' . (int)($result['tables_restored'] ?? 0) . ' tables, '
                    . (int)($result['rows_restored'] ?? 0) . ' rows).';
                header('Location: ' . url('admin/settings.php'));
                exit;
            }

            if ($action === 'upload_restore') {
                $uploadError = $_FILES['backup_file']['error'] ?? UPLOAD_ERR_NO_FILE;
                if (!is_int($uploadError) || $uploadError === UPLOAD_ERR_NO_FILE) {
                    throw new InvalidArgumentException('Choose a compressed backup file to restore.');
                }
                if ($uploadError !== UPLOAD_ERR_OK) {
                    throw new InvalidArgumentException('Backup upload failed. Please try again.');
                }

                $tmpPath = (string)($_FILES['backup_file']['tmp_name'] ?? '');
                $originalName = trim((string)($_FILES['backup_file']['name'] ?? 'uploaded-backup.json.gz'));
                $fileSize = (int)($_FILES['backup_file']['size'] ?? 0);

                if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                    throw new InvalidArgumentException('Uploaded backup file could not be validated.');
                }
                if ($fileSize <= 0 || $fileSize > $maxBackupUploadBytes) {
                    throw new InvalidArgumentException('Backup file must be between 1 byte and ' . (int)($maxBackupUploadBytes / (1024 * 1024)) . ' MB.');
                }
                if (!preg_match('/\.json\.gz$/i', $originalName)) {
                    throw new InvalidArgumentException('Backup file must use the .json.gz format.');
                }

                $result = BackupManager::restoreFromUploadedBackup($tmpPath, $originalName);
                ServiceCall::logSystemEvent(
                    $user,
                    'backup_restore_upload',
                    null,
                    $originalName,
                    'Backup restored from uploaded file'
                );

                $_SESSION['admin_settings_success'] = 'Uploaded backup restored ('
                    . (int)($result['tables_restored'] ?? 0) . ' tables, '
                    . (int)($result['rows_restored'] ?? 0) . ' rows).';
                header('Location: ' . url('admin/settings.php'));
                exit;
            }

            $settings['site_title'] = trim((string)($_POST['site_title'] ?? ''));
            $settings['saved_views_enabled'] = isset($_POST['saved_views_enabled']) ? '1' : '0';
            $settings['backup_auto_enabled'] = isset($_POST['backup_auto_enabled']) ? '1' : '0';

            $backupCadence = trim((string)($_POST['backup_cadence'] ?? 'daily'));
            if (!in_array($backupCadence, $allowedBackupCadence, true)) {
                $backupCadence = 'daily';
            }
            $settings['backup_cadence'] = $backupCadence;

            $retentionDays = (int)($_POST['backup_retention_days'] ?? 60);
            if ($retentionDays < 1 || $retentionDays > 3650) {
                $errors['backup_retention_days'] = 'Retention must be between 1 and 3650 days.';
            }
            $settings['backup_retention_days'] = (string)max(1, min($retentionDays, 3650));

            $existingLogoPath = (string)($settings['site_logo_path'] ?? '');
            $removeLogo = isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1';
            $uploadError = $_FILES['site_logo']['error'] ?? UPLOAD_ERR_NO_FILE;
            $hasUploadedLogo = $uploadError !== UPLOAD_ERR_NO_FILE;

            if ($settings['site_title'] === '') {
                $errors['site_title'] = 'Site title cannot be empty.';
            }

            if ($hasUploadedLogo) {
                if (!is_int($uploadError)) {
                    $errors['site_logo'] = 'Logo upload failed. Please try again.';
                } elseif ($uploadError !== UPLOAD_ERR_OK) {
                    $errors['site_logo'] = match ($uploadError) {
                        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Logo exceeds the upload size limit.',
                        UPLOAD_ERR_PARTIAL => 'Logo upload was interrupted. Please try again.',
                        UPLOAD_ERR_NO_TMP_DIR => 'The server is missing a temporary upload directory.',
                        UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file.',
                        UPLOAD_ERR_EXTENSION => 'A server extension blocked this upload.',
                        default => 'Logo upload failed. Please try again.',
                    };
                } else {
                    $tmpPath = (string)($_FILES['site_logo']['tmp_name'] ?? '');
                    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                        $errors['site_logo'] = 'Logo upload failed validation. Please try again.';
                    } else {
                        $fileSize = (int)($_FILES['site_logo']['size'] ?? 0);
                        if ($fileSize <= 0 || $fileSize > $maxLogoBytes) {
                            $errors['site_logo'] = 'Logo must be between 1 byte and 2 MB.';
                        } else {
                            $imageInfo = getimagesize($tmpPath);
                            if ($imageInfo === false) {
                                $errors['site_logo'] = 'Logo file is not a valid image.';
                            } else {
                                $mimeType = (string)($imageInfo['mime'] ?? '');
                                if (!isset($allowedLogoMimeTypes[$mimeType])) {
                                    $errors['site_logo'] = 'Logo must be PNG, JPG, GIF, or WEBP.';
                                }
                            }
                        }
                    }
                }
            }

            if (empty($errors)) {
                $newLogoPath = '';
                $newLogoAbsolutePath = '';
                $oldLogoAbsolutePathToDelete = '';

                try {
                    $stmt = $pdo->prepare(
                        'INSERT INTO settings (name, value) VALUES (:name, :insert_value)
                         ON DUPLICATE KEY UPDATE value = :update_value'
                    );
                    $pdo->beginTransaction();

                    if ($hasUploadedLogo) {
                        if (!is_dir($logoDirectoryAbsolute) && !mkdir($logoDirectoryAbsolute, 0775, true) && !is_dir($logoDirectoryAbsolute)) {
                            throw new RuntimeException('Could not create logo upload directory.');
                        }

                        $tmpPath = (string)($_FILES['site_logo']['tmp_name'] ?? '');
                        $imageInfo = getimagesize($tmpPath);
                        $mimeType = (string)($imageInfo['mime'] ?? '');
                        $extension = $allowedLogoMimeTypes[$mimeType] ?? '';
                        if ($extension === '') {
                            throw new RuntimeException('Uploaded logo has unsupported MIME type.');
                        }

                        $newFilename = 'site-logo-' . bin2hex(random_bytes(8)) . '.' . $extension;
                        $newLogoPath = $logoDirectoryRelative . '/' . $newFilename;
                        $newLogoAbsolutePath = $projectRoot . '/' . $newLogoPath;

                        if (!move_uploaded_file($tmpPath, $newLogoAbsolutePath)) {
                            throw new RuntimeException('Could not store uploaded logo file.');
                        }
                        if (!is_file($newLogoAbsolutePath)) {
                            throw new RuntimeException('Uploaded logo could not be verified on disk.');
                        }

                        $settings['site_logo_path'] = $newLogoPath;
                        if ($existingLogoPath !== '' && $existingLogoPath !== $newLogoPath) {
                            $oldLogoAbsolutePathToDelete = $resolveStoredLogoAbsolutePath($existingLogoPath);
                        }
                    } elseif ($removeLogo) {
                        $settings['site_logo_path'] = '';
                        if ($existingLogoPath !== '') {
                            $oldLogoAbsolutePathToDelete = $resolveStoredLogoAbsolutePath($existingLogoPath);
                        }
                    }

                    foreach ($settings as $name => $value) {
                        $stmt->execute([
                            ':name' => $name,
                            ':insert_value' => $value,
                            ':update_value' => $value,
                        ]);
                    }
                    $pdo->commit();

                    if ($oldLogoAbsolutePathToDelete !== '' && is_file($oldLogoAbsolutePathToDelete)) {
                        if (!unlink($oldLogoAbsolutePathToDelete)) {
                            Logger::warning('Logo file could not be removed after settings update', [
                                'admin_user_id' => $user['id'] ?? null,
                                'path' => $oldLogoAbsolutePathToDelete,
                            ]);
                        }
                    }

                    Logger::info('Admin updated system settings', [
                        'admin_user_id' => $user['id'] ?? null,
                    ]);
                    ServiceCall::logSystemEvent(
                        $user,
                        'system_settings',
                        $originalSettings['site_title'] ?? null,
                        $settings['site_title'] ?? null,
                        'System settings updated'
                    );

                    $_SESSION['admin_settings_success'] = 'Settings saved successfully.';
                    header('Location: ' . url('admin/settings.php'));
                    exit;
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    if ($newLogoAbsolutePath !== '' && is_file($newLogoAbsolutePath)) {
                        if (!unlink($newLogoAbsolutePath)) {
                            Logger::warning('Temporary logo cleanup failed after settings error', [
                                'admin_user_id' => $user['id'] ?? null,
                                'path' => $newLogoAbsolutePath,
                            ]);
                        }
                    }
                    if ($hasUploadedLogo) {
                        $errors['site_logo'] = 'Logo upload failed while saving. Please try again.';
                    } else {
                        $errors['form'] = 'Unable to save settings right now.';
                    }
                    Logger::error('Unexpected error updating settings', [
                        'admin_user_id' => $user['id'] ?? null,
                        'exception' => $exception->getMessage(),
                    ]);
                }
            }
        } catch (InvalidArgumentException $exception) {
            $errors['form'] = $exception->getMessage();
        } catch (Throwable $exception) {
            $errors['form'] = 'Unable to complete that backup action right now.';
            Logger::error('Unexpected backup/settings action error', [
                'admin_user_id' => $user['id'] ?? null,
                'action' => $action,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}

$backupFiles = [];
try {
    $backupFiles = BackupManager::listBackups();
} catch (Throwable $exception) {
    if (!isset($errors['form'])) {
        $errors['form'] = 'Backup storage is unavailable right now. Check server permissions for storage/backups.';
    }
    Logger::error('Unable to list backup files', [
        'admin_user_id' => $user['id'] ?? null,
        'exception' => $exception->getMessage(),
    ]);
}

Template::render('pages/admin_settings', [
    'title' => 'System Settings',
    'user' => $user,
    'settings' => $settings,
    'errors' => $errors,
    'successMessage' => $successMessage,
    'backupFiles' => $backupFiles,
    'maxBackupUploadBytes' => $maxBackupUploadBytes,
], 'layouts/app');
