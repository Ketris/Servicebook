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
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape($documentTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?= $__content ?>
</body>
</html>
