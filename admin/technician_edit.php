<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Template.php';

Auth::requireAdmin();
$user = Auth::currentUser();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$pdo = Database::getConnection();
$errors = [];

$values = [
    'name' => '',
    'phone' => '',
    'active' => 1,
];

$formatTechnicianSummary = static function (array $data): string {
    $name = trim((string)($data['name'] ?? ''));
    $phone = trim((string)($data['phone'] ?? ''));
    $active = (int)($data['active'] ?? 0);

    return implode('; ', [
        'Name=' . ($name !== '' ? $name : '-'),
        'Phone=' . ($phone !== '' ? $phone : '-'),
        'Active=' . ($active === 1 ? 'Yes' : 'No'),
    ]);
};

if ($id !== null) {
    $stmt = $pdo->prepare('SELECT id, name, phone, active FROM technicians WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $existing = $stmt->fetch();
    if ($existing) {
        $values = [
            'name' => $existing['name'],
            'phone' => $existing['phone'] ?? '',
            'active' => (int)$existing['active'],
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please reload and try again.';
    } else {
        $values['name'] = trim($_POST['name'] ?? '');
        $values['phone'] = trim($_POST['phone'] ?? '');
        $values['active'] = isset($_POST['active']) ? 1 : 0;

        if ($values['name'] === '') {
            $errors['name'] = 'Technician name is required.';
        }
        if (mb_strlen($values['name']) > 150) {
            $errors['name'] = 'Technician name cannot exceed 150 characters.';
        }
        if (mb_strlen($values['phone']) > 100) {
            $errors['phone'] = 'Phone cannot exceed 100 characters.';
        }
        if ($values['phone'] !== '' && !preg_match('/^[0-9+()\-.\s]{7,30}$/', $values['phone'])) {
            $errors['phone'] = 'Phone number format is invalid.';
        }

        if (empty($errors)) {
            try {
                $wasNewTechnician = $id === null;
                $beforeSummary = $existing
                    ? $formatTechnicianSummary($existing)
                    : null;
                $savedTechnicianId = $id;
                if ($id === null) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO technicians (name, phone, active, created_at) VALUES (:name, :phone, :active, NOW())'
                    );
                    $stmt->execute([
                        ':name' => $values['name'],
                        ':phone' => $values['phone'] !== '' ? $values['phone'] : null,
                        ':active' => $values['active'],
                    ]);
                    $savedTechnicianId = (int)$pdo->lastInsertId();
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE technicians SET name = :name, phone = :phone, active = :active WHERE id = :id'
                    );
                    $stmt->execute([
                        ':name' => $values['name'],
                        ':phone' => $values['phone'] !== '' ? $values['phone'] : null,
                        ':active' => $values['active'],
                        ':id' => $id,
                    ]);
                }

                Logger::info('Admin saved technician record', [
                    'admin_user_id' => $user['id'] ?? null,
                    'technician_id' => $savedTechnicianId,
                ]);
                ServiceCall::logSystemEvent(
                    $user,
                    'technician_record',
                    $beforeSummary,
                    $formatTechnicianSummary($values),
                    $wasNewTechnician
                        ? 'Technician created (ID ' . $savedTechnicianId . ')'
                        : 'Technician updated (ID ' . $savedTechnicianId . ')'
                );
                header('Location: ' . url('admin/technicians.php'));
                exit;
            } catch (Throwable $exception) {
                $errors['form'] = 'Unable to save technician right now.';
                Logger::error('Unexpected error saving technician', [
                    'admin_user_id' => $user['id'] ?? null,
                    'technician_id' => $id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
    }
}

Template::render('pages/admin_technician_edit', [
    'title' => $id ? 'Edit Technician' : 'New Technician',
    'user' => $user,
    'id' => $id,
    'values' => $values,
    'errors' => $errors,
], 'layouts/app');
