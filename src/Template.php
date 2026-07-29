<?php
require_once __DIR__ . '/Helpers.php';

class Template
{
    public static function render(string $template, array $data = [], ?string $layout = null): void
    {
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
