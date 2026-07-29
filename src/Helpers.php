<?php
if (!function_exists('escape')) {
    function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('truncate')) {
    function truncate(string $value, int $length = 100): string
    {
        if (mb_strlen($value) <= $length) {
            return $value;
        }
        return mb_substr($value, 0, $length - 3) . '...';
    }
}

if (!function_exists('base_url')) {
    function base_url(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptPath = str_replace('\\', '/', $scriptName);
        $dir = rtrim(str_replace('\\', '/', dirname($scriptPath)), '/');

        if (preg_match('@/public(?:/|$)@', $dir)) {
            $dir = preg_replace('@/public(?:/.*)?$@', '', $dir);
        }

        if (preg_match('@/admin(?:/|$)@', $dir)) {
            $dir = preg_replace('@/admin(?:/.*)?$@', '', $dir);
        }

        return $dir === '' || $dir === '/' ? '' : $dir;
    }
}

if (!function_exists('url')) {
    function url(string $path): string
    {
        $base = base_url();
        $path = ltrim($path, '/');
        return $base === '' ? '/' . $path : $base . '/' . $path;
    }
}
