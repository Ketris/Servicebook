<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/AppSettings.php';
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
$maxLogoBytes = 2 * 1024 * 1024;
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
        $settings['site_title'] = trim($_POST['site_title'] ?? '');
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
                    'System settings updated' . (($originalSettings['site_logo_path'] ?? '') !== ($settings['site_logo_path'] ?? '') ? ' (logo changed)' : '')
                );
                header('Location: ' . url('admin/settings.php') . '?updated=1');
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
    }
}

Template::render('pages/admin_settings', [
    'title' => 'System Settings',
    'user' => $user,
    'settings' => $settings,
    'errors' => $errors,
], 'layouts/app');
