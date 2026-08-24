<?php
/** @var string $title */
/** @var string $__content */
/** @var string $app_site_title */

apply_security_headers();

$pageTitle = trim((string)($title ?? ''));
$siteTitle = trim((string)($app_site_title ?? 'Servicebook'));
$documentTitle = $pageTitle === '' ? $siteTitle : $pageTitle . ' · ' . $siteTitle;
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('sb-theme');
                if (stored === 'light' || stored === 'dark') {
                    document.documentElement.setAttribute('data-bs-theme', stored);
                }
            } catch (e) {}
        })();
    </script>
    <title><?= escape($documentTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script>
        window.ServicebookTheme = (() => {
            const STORAGE_KEY = 'sb-theme';

            function apply(theme) {
                document.documentElement.setAttribute('data-bs-theme', theme);
                document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
                    button.querySelector('i')?.setAttribute('class', theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill');
                    button.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
                });
            }

            function toggle() {
                const next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                apply(next);
                try {
                    localStorage.setItem(STORAGE_KEY, next);
                } catch (e) {}
            }

            document.addEventListener('DOMContentLoaded', () => {
                apply(document.documentElement.getAttribute('data-bs-theme') || 'light');
                document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
                    button.addEventListener('click', toggle);
                });
            });

            return { toggle };
        })();
    </script>
</head>
<body>
<?= $__content ?>
</body>
</html>
