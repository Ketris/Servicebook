<?php
require_once __DIR__ . '/Database.php';

class AppSettings
{
    private const DEFAULTS = [
        'site_title' => 'Servicebook',
        'site_logo_path' => '',
        'saved_views_enabled' => '0',
        'backup_auto_enabled' => '1',
        'backup_cadence' => 'daily',
        'backup_retention_days' => '60',
        'backup_last_run_at' => '',
    ];

    public static function defaults(): array
    {
        return self::DEFAULTS;
    }

    public static function all(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $settings = self::DEFAULTS;
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT name, value FROM settings');
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $name = (string)($row['name'] ?? '');
            if ($name === '' || !array_key_exists($name, $settings)) {
                continue;
            }
            $settings[$name] = trim((string)($row['value'] ?? ''));
        }

        if ($settings['site_title'] === '') {
            $settings['site_title'] = self::DEFAULTS['site_title'];
        }
        $settings['site_logo_path'] = self::normalizeLogoPath($settings['site_logo_path']);

        $cache = $settings;
        return $cache;
    }

    public static function get(string $name): string
    {
        $settings = self::all();
        if (array_key_exists($name, $settings)) {
            return $settings[$name];
        }
        return self::DEFAULTS[$name] ?? '';
    }

    public static function logoUrl(): string
    {
        $path = self::all()['site_logo_path'] ?? '';
        if ($path === '') {
            return '';
        }

        return url($path);
    }

    private static function normalizeLogoPath(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($normalized === '') {
            return '';
        }

        if (preg_match('/^public\/assets\/branding\/[A-Za-z0-9._-]+$/', $normalized)) {
            $absolutePath = dirname(__DIR__) . '/' . $normalized;
            if (is_file($absolutePath)) {
                return $normalized;
            }
            return '';
        }

        if (preg_match('/^assets\/branding\/[A-Za-z0-9._-]+$/', $normalized)) {
            $absolutePath = dirname(__DIR__) . '/public/' . $normalized;
            if (is_file($absolutePath)) {
                return 'public/' . $normalized;
            }
            return '';
        }

        return '';
    }
}
