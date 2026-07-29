<?php
require_once __DIR__ . '/Database.php';

class SavedView
{
    public static function listVisibleForUser(array $user, string $context = 'calls'): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT id, view_name, page_context, search_term, filter_value, user_id, role_scope, is_default, created_at
             FROM saved_views
             WHERE page_context = :page_context
               AND (
                   user_id = :user_id
                   OR (role_scope = :role_scope_match AND is_default = 1)
               )
             ORDER BY
               CASE WHEN role_scope = :role_scope_order AND is_default = 1 THEN 0 ELSE 1 END,
               view_name ASC, id DESC'
        );
        $stmt->execute([
            ':page_context' => $context,
            ':user_id' => (int)($user['id'] ?? 0),
            ':role_scope_match' => (string)($user['role'] ?? ''),
            ':role_scope_order' => (string)($user['role'] ?? ''),
        ]);
        return $stmt->fetchAll();
    }

    public static function findVisibleById(int $id, array $user, string $context = 'calls'): array|null
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT id, view_name, page_context, search_term, filter_value, user_id, role_scope, is_default, created_at
             FROM saved_views
             WHERE id = :id
               AND page_context = :page_context
               AND (
                   user_id = :user_id
                   OR (role_scope = :role_scope_match AND is_default = 1)
               )
             LIMIT 1'
        );
        $stmt->execute([
            ':id' => $id,
            ':page_context' => $context,
            ':user_id' => (int)($user['id'] ?? 0),
            ':role_scope_match' => (string)($user['role'] ?? ''),
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function createPersonal(int $userId, string $context, string $viewName, string $searchTerm, string $filterValue): int
    {
        $viewName = trim($viewName);
        if ($userId <= 0 || $viewName === '') {
            throw new InvalidArgumentException('View name is required.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO saved_views
             (view_name, page_context, search_term, filter_value, user_id, role_scope, is_default, created_by, created_at, updated_at)
             VALUES
             (:view_name, :page_context, :search_term, :filter_value, :user_id, NULL, 0, :created_by, NOW(), NOW())'
        );
        $stmt->execute([
            ':view_name' => mb_substr($viewName, 0, 100),
            ':page_context' => $context,
            ':search_term' => mb_substr($searchTerm, 0, 120),
            ':filter_value' => mb_substr($filterValue, 0, 60),
            ':user_id' => $userId,
            ':created_by' => $userId,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function createRoleDefault(int $adminUserId, string $roleScope, string $context, string $viewName, string $searchTerm, string $filterValue): int
    {
        $allowedRoles = ['Administrator', 'Office Staff', 'Technician'];
        if (!in_array($roleScope, $allowedRoles, true)) {
            throw new InvalidArgumentException('Invalid role selected for default view.');
        }

        $viewName = trim($viewName);
        if ($viewName === '') {
            throw new InvalidArgumentException('Default view name is required.');
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $clearStmt = $pdo->prepare(
                'UPDATE saved_views
                 SET is_default = 0, updated_at = NOW()
                 WHERE page_context = :page_context AND role_scope = :role_scope AND is_default = 1'
            );
            $clearStmt->execute([
                ':page_context' => $context,
                ':role_scope' => $roleScope,
            ]);

            $insertStmt = $pdo->prepare(
                'INSERT INTO saved_views
                 (view_name, page_context, search_term, filter_value, user_id, role_scope, is_default, created_by, created_at, updated_at)
                 VALUES
                 (:view_name, :page_context, :search_term, :filter_value, NULL, :role_scope, 1, :created_by, NOW(), NOW())'
            );
            $insertStmt->execute([
                ':view_name' => mb_substr($viewName, 0, 100),
                ':page_context' => $context,
                ':search_term' => mb_substr($searchTerm, 0, 120),
                ':filter_value' => mb_substr($filterValue, 0, 60),
                ':role_scope' => $roleScope,
                ':created_by' => $adminUserId,
            ]);

            $newId = (int)$pdo->lastInsertId();
            $pdo->commit();
            return $newId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function deleteForUser(int $id, array $user): bool
    {
        if ($id <= 0) {
            return false;
        }

        $pdo = Database::getConnection();
        $rowStmt = $pdo->prepare('SELECT id, user_id, role_scope FROM saved_views WHERE id = :id LIMIT 1');
        $rowStmt->execute([':id' => $id]);
        $row = $rowStmt->fetch();
        if (!$row) {
            return false;
        }

        $currentUserId = (int)($user['id'] ?? 0);
        $currentRole = (string)($user['role'] ?? '');
        $ownerId = (int)($row['user_id'] ?? 0);
        $isRoleDefault = !empty($row['role_scope']);

        $canDelete = ($ownerId > 0 && $ownerId === $currentUserId)
            || ($currentRole === 'Administrator' && $isRoleDefault);

        if (!$canDelete) {
            return false;
        }

        $deleteStmt = $pdo->prepare('DELETE FROM saved_views WHERE id = :id');
        $deleteStmt->execute([':id' => $id]);
        return $deleteStmt->rowCount() > 0;
    }
}