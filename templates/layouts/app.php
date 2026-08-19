<?php
/** @var string $title */
/** @var string $__content */
/** @var array|null $user */
/** @var string $app_site_title */

apply_security_headers();

$pageTitle = trim((string)($title ?? ''));
$siteTitle = trim((string)($app_site_title ?? 'Servicebook'));
$documentTitle = $pageTitle === '' ? $siteTitle : $pageTitle . ' · ' . $siteTitle;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape($documentTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --app-bg: #f4f7fb;
            --app-surface: #ffffff;
            --app-surface-muted: #f8fafc;
            --app-border: #d9e2ef;
            --app-text: #1f2937;
            --app-accent: #0d6efd;
        }

        body {
            background: var(--app-bg);
            color: var(--app-text);
        }

        .navbar {
            background: rgba(255, 255, 255, 0.96) !important;
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
            border-bottom-color: #e8edf5;
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
            color: #64748b;
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
