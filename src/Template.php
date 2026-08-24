<?php
require_once __DIR__ . '/AppSettings.php';
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/UserPreference.php';

class Template
{
    public static function render(string $template, array $data = [], ?string $layout = null): void
    {
        if (!isset($data['app_settings'])) {
            $data['app_settings'] = AppSettings::all();
        }
        if (!isset($data['app_site_title'])) {
            $data['app_site_title'] = (string)($data['app_settings']['site_title'] ?? AppSettings::get('site_title'));
        }
        if (!isset($data['app_logo_url'])) {
            $logoPath = (string)($data['app_settings']['site_logo_path'] ?? '');
            $data['app_logo_url'] = $logoPath === '' ? '' : url($logoPath);
        }
        if (!isset($data['app_theme'])) {
            $userId = (int)($data['user']['id'] ?? 0);
            $theme = $userId > 0 ? UserPreference::get($userId, 'ui_theme', 'light') : 'light';
            $data['app_theme'] = in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
        }

        $root = dirname(__DIR__);
        $viewPath = $root . '/templates/' . ltrim($template, '/') . '.php';

        if (!is_file($viewPath)) {
            throw new RuntimeException('Template view not found: ' . $template);
        }

        if ($layout !== null) {
            $layoutPath = $root . '/templates/' . ltrim($layout, '/') . '.php';
            if (!is_file($layoutPath)) {
                throw new RuntimeException('Template layout not found: ' . $layout);
            }

            $data['__content'] = self::capture($viewPath, $data);
            extract($data, EXTR_SKIP);
            include $layoutPath;
            return;
        }

        extract($data, EXTR_SKIP);
        include $viewPath;
    }

    private static function capture(string $viewPath, array $data): string
    {
        ob_start();
        extract($data, EXTR_SKIP);
        include $viewPath;
        return (string)ob_get_clean();
    }
}
