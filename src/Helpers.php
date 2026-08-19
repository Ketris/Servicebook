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

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . escape(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_validate')) {
    function csrf_validate(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['_csrf_token']) || !is_string($token)) {
            return false;
        }

        return hash_equals($_SESSION['_csrf_token'], $token);
    }
}

if (!function_exists('apply_security_headers')) {
    function apply_security_headers(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    }
}

if (!function_exists('status_badge_class')) {
    function status_badge_class(string $status): string
    {
        return match ($status) {
            'New' => 'text-bg-secondary',
            'Dispatched' => 'text-bg-primary',
            'In Progress' => 'text-bg-success',
            'Waiting Parts' => 'text-bg-warning',
            'On Hold' => 'text-bg-dark',
            'Complete' => 'text-bg-light border',
            'Cancelled' => 'text-bg-danger',
            default => 'text-bg-secondary',
        };
    }
}

if (!function_exists('humanize_field_name')) {
    function humanize_field_name(string $fieldName): string
    {
        return ucwords(str_replace('_', ' ', $fieldName));
    }
}

if (!function_exists('describe_activity_entry')) {
    function describe_activity_entry(array $entry): string
    {
        $fieldName = (string)($entry['field_name'] ?? '');
        $note = trim((string)($entry['note'] ?? ''));

        if (!empty($entry['service_call_id'])) {
            $job = (string)($entry['job_number'] ?? ('Call #' . $entry['service_call_id']));
            return $job . ' — ' . humanize_field_name($fieldName);
        }

        // System-level events almost always carry a human-readable note; fall back to the field name otherwise.
        return $note !== '' ? $note : humanize_field_name($fieldName);
    }
}
