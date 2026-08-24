<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/UserPreference.php';

Auth::requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_validate($_POST['_csrf_token'] ?? null)) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$theme = (string)($_POST['theme'] ?? '');
if (!in_array($theme, ['light', 'dark'], true)) {
    http_response_code(422);
    echo json_encode(['ok' => false]);
    exit;
}

$user = Auth::currentUser();
$userId = (int)($user['id'] ?? 0);
if ($userId > 0) {
    UserPreference::set($userId, 'ui_theme', $theme);
}

echo json_encode(['ok' => true]);
