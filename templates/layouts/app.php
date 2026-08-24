<?php
/** @var string $title */
/** @var string $__content */
/** @var array|null $user */
/** @var string $app_site_title */
/** @var string $app_theme */

apply_security_headers();

$pageTitle = trim((string)($title ?? ''));
$siteTitle = trim((string)($app_site_title ?? 'Servicebook'));
$documentTitle = $pageTitle === '' ? $siteTitle : $pageTitle . ' · ' . $siteTitle;
$initialTheme = in_array($app_theme ?? '', ['light', 'dark'], true) ? $app_theme : 'light';
?>
<!doctype html>
<html lang="en" data-bs-theme="<?= escape($initialTheme) ?>">
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
    <style>
        :root {
            --app-bg: #f4f7fb;
            --app-surface: #ffffff;
            --app-surface-muted: #f8fafc;
            --app-border: #d9e2ef;
            --app-text: #1f2937;
            --app-accent: #0d6efd;
            --app-navbar-bg: rgba(255, 255, 255, 0.96);
            --app-table-border: #e8edf5;
            --app-muted-label: #64748b;
        }

        [data-bs-theme="dark"] {
            --app-bg: #10161f;
            --app-surface: #1a222e;
            --app-surface-muted: #202a38;
            --app-border: #2c3949;
            --app-text: #e2e8f0;
            --app-navbar-bg: rgba(26, 34, 46, 0.96);
            --app-table-border: #2c3949;
            --app-muted-label: #94a3b8;
        }

        body {
            background: var(--app-bg);
            color: var(--app-text);
        }

        .navbar {
            background: var(--app-navbar-bg) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            position: relative;
            z-index: 1040;
        }

        .navbar .dropdown-menu {
            z-index: 1050;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .container-fluid {
            max-width: 1520px;
        }

        .card,
        .table-responsive,
        .alert,
        .dropdown-menu {
            border: 1px solid var(--app-border);
            border-radius: 1rem;
        }

        .card {
            background: var(--app-surface);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }

        .card-header,
        .table-light {
            background: var(--app-surface-muted) !important;
        }

        [data-bs-theme="dark"] .table-light {
            --bs-table-bg: var(--app-surface-muted);
            --bs-table-color: var(--app-text);
            --bs-table-border-color: var(--app-border);
        }

        [data-bs-theme="dark"] div.bg-light {
            background-color: var(--app-surface-muted) !important;
            color: var(--app-text);
        }

        .btn,
        .form-control,
        .form-select,
        textarea {
            border-radius: 0.85rem;
        }

        .form-control,
        .form-select {
            border-color: var(--app-border);
        }

        .form-control:focus,
        .form-select:focus,
        textarea:focus {
            border-color: rgba(13, 110, 253, 0.45);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.12);
        }

        .table-responsive {
            background: var(--app-surface);
            overflow: hidden;
        }

        .table > :not(caption) > * > * {
            border-bottom-color: var(--app-table-border);
        }

        .truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .truncate-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .stat-card {
            min-height: 100%;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }

        .surface-muted {
            background: var(--app-surface-muted);
        }

        .job-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .job-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .job-detail-list dt {
            color: var(--app-muted-label);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            margin-bottom: 0.15rem;
            text-transform: uppercase;
        }

        .job-detail-list dd {
            margin-bottom: 0;
        }

        .job-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: space-between;
            align-items: center;
        }

        .app-page-header {
            margin-bottom: 1.5rem;
        }
    </style>
    <script>
        window.ServicebookHotkeys = (() => {
            const shortcuts = new Map();

            function isEditableTarget(target) {
                return target instanceof HTMLElement && (
                    target.isContentEditable ||
                    ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)
                );
            }

            document.addEventListener('keydown', (event) => {
                if (event.isComposing || event.ctrlKey || event.metaKey || event.altKey) {
                    return;
                }

                const shortcut = shortcuts.get(event.key.toLowerCase());
                if (!shortcut || (isEditableTarget(event.target) && !shortcut.allowInEditable)) {
                    return;
                }

                event.preventDefault();
                shortcut.action(event);
            });

            return {
                register(key, action, options = {}) {
                    shortcuts.set(key.toLowerCase(), {
                        action,
                        allowInEditable: options.allowInEditable === true,
                    });
                },
            };
        })();

        window.ServicebookTheme = (() => {
            const STORAGE_KEY = 'sb-theme';
            const csrfToken = <?= json_encode(csrf_token()) ?>;

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

                fetch('<?= url('public/set_theme.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'theme=' + encodeURIComponent(next) + '&_csrf_token=' + encodeURIComponent(csrfToken),
                }).catch(() => {});
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
<?php include __DIR__ . '/../partials/navbar.php'; ?>
<div class="container-fluid py-4">
<?= $__content ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
