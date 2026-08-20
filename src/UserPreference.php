<?php
require_once __DIR__ . '/Database.php';

class UserPreference
{
    public static function get(int $userId, string $key, ?string $fallback = null): ?string
    {
        $values = self::getMany($userId, [$key => $fallback]);
        return $values[$key] ?? $fallback;
    }

    public static function getMany(int $userId, array $defaults): array
    {
        if ($userId <= 0 || empty($defaults)) {
            return $defaults;
        }

        $keys = array_keys($defaults);
        $placeholders = [];
        $params = [':user_id' => $userId];
        foreach ($keys as $index => $key) {
            self::validateKey((string)$key);
            $placeholder = ':preference_key_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = (string)$key;
        }
        $stmt = Database::getConnection()->prepare(
            'SELECT preference_key, preference_value
             FROM user_preferences
             WHERE user_id = :user_id AND preference_key IN (' . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($params);
        $values = $defaults;
        foreach ($stmt->fetchAll() as $row) {
            $key = (string)($row['preference_key'] ?? '');
            if (array_key_exists($key, $values)) {
                $values[$key] = (string)($row['preference_value'] ?? '');
            }
        }
        return $values;
    }

    public static function set(int $userId, string $key, string $value): void
    {
        self::setMany($userId, [$key => $value]);
    }

    public static function setMany(int $userId, array $values): void
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('A valid user is required.');
        }
        if (empty($values)) {
            return;
        }

        $stmt = Database::getConnection()->prepare(
            'INSERT INTO user_preferences (user_id, preference_key, preference_value)
             VALUES (:user_id, :preference_key, :preference_value)
             ON DUPLICATE KEY UPDATE preference_value = :updated_value'
        );
        foreach ($values as $key => $value) {
            self::validateKey((string)$key);
            $stmt->execute([
                ':user_id' => $userId,
                ':preference_key' => (string)$key,
                ':preference_value' => (string)$value,
                ':updated_value' => (string)$value,
            ]);
        }
    }

    private static function validateKey(string $key): void
    {
        if ($key === '' || !preg_match('/^[a-z0-9][a-z0-9_.-]{0,99}$/', $key)) {
            throw new InvalidArgumentException('Invalid user preference key.');
        }
    }
}