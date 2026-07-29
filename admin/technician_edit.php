<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
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
    $values['name'] = trim($_POST['name'] ?? '');
    $values['phone'] = trim($_POST['phone'] ?? '');
    $values['active'] = isset($_POST['active']) ? 1 : 0;

    if ($values['name'] === '') {
        $errors['name'] = 'Technician name is required.';
    }

    if (empty($errors)) {
        if ($id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO technicians (name, phone, active, created_at) VALUES (:name, :phone, :active, NOW())'
            );
            $stmt->execute([
                ':name' => $values['name'],
                ':phone' => $values['phone'] !== '' ? $values['phone'] : null,
                ':active' => $values['active'],
            ]);
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

        header('Location: ' . url('admin/technicians.php'));
        exit;
    }
}

Template::render('pages/admin_technician_edit', [
    'title' => $id ? 'Edit Technician' : 'New Technician',
    'user' => $user,
    'id' => $id,
    'values' => $values,
    'errors' => $errors,
], 'layouts/app');
