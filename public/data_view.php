<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Technician.php';
require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/UserPreference.php';

Auth::requireLogin();
$user = Auth::currentUser() ?? [];
$userId = (int)($user['id'] ?? 0);

// The column-picker form posts back to this same script, replicating the original
// query string as hidden fields, so read from POST data on that request instead of GET.
$params = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_columns') ? $_POST : $_GET;

$format = strtolower(trim((string)($params['format'] ?? 'csv')));
if (!in_array($format, ['csv', 'print'], true)) {
    $format = 'csv';
}

$source = strtolower(trim((string)($params['source'] ?? 'calls')));
if (!in_array($source, ['calls', 'search', 'technician', 'activity'], true)) {
    $source = 'calls';
}

$search = trim((string)($params['search'] ?? ''));
$filter = trim((string)($params['filter'] ?? 'incomplete'));
$allowedFilters = ['all', 'incomplete', 'unassigned', 'completed_today', 'completed_week'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'incomplete';
}

if ($source === 'activity' && ($user['role'] ?? '') !== 'Administrator') {
    header('Location: ' . url('public/index.php'));
    exit;
}

$title = 'Service Calls';
$columns = [];
$rows = [];

if ($source === 'calls') {
    $title = 'Service Calls';
    $rows = ServiceCall::findAll($search, $filter);
    $columns = [
        'job_number' => 'Job #',
        'received_date' => 'Received',
        'customer' => 'Customer',
        'location' => 'Location',
        'contact' => 'Contact',
        'phone' => 'Phone',
        'email' => 'Email',
        'po_number' => 'PO Number',
        'assigned_tech_name' => 'Technician',
        'status' => 'Status',
        'reported_issue' => 'Issue',
        'internal_notes' => 'Internal Notes',
        'updated_at' => 'Last Updated',
    ];
} elseif ($source === 'search') {
    $title = 'Search Results';
    $rows = ServiceCall::findAll($search, 'all');
    $columns = [
        'job_number' => 'Job #',
        'received_date' => 'Received',
        'customer' => 'Customer',
        'location' => 'Location',
        'contact' => 'Contact',
        'phone' => 'Phone',
        'email' => 'Email',
        'po_number' => 'PO Number',
        'assigned_tech_name' => 'Technician',
        'status' => 'Status',
        'reported_issue' => 'Issue',
        'internal_notes' => 'Internal Notes',
        'updated_at' => 'Last Updated',
    ];
} elseif ($source === 'technician') {
    $title = 'Technician Queue';
    $techId = 0;

    if (($user['role'] ?? '') === 'Technician') {
        $techId = (int)($user['technician_id'] ?? 0);
    } else {
        $techId = (int)($params['technician_id'] ?? 0);
    }

    $techName = 'Unlinked Technician';
    if ($techId > 0) {
        $technician = Technician::findById($techId);
        if ($technician) {
            $techName = (string)$technician['name'];
        }
    }

    $title = $techId > 0 ? ('Technician Queue - ' . $techName) : 'Unassigned Open Queue';

    $active = $techId > 0 ? ServiceCall::findActiveByTechnician($techId, 500) : [];
    $claimable = ServiceCall::findClaimableOpenJobs(500);

    foreach ($active as $row) {
        $row['queue_bucket'] = 'Assigned';
        $rows[] = $row;
    }

    foreach ($claimable as $row) {
        $row['queue_bucket'] = 'Unassigned';
        $rows[] = $row;
    }

    $columns = [
        'queue_bucket' => 'Queue',
        'job_number' => 'Job #',
        'received_date' => 'Received',
        'customer' => 'Customer',
        'location' => 'Location',
        'contact' => 'Contact',
        'phone' => 'Phone',
        'email' => 'Email',
        'po_number' => 'PO Number',
        'assigned_tech_name' => 'Technician',
        'status' => 'Status',
        'reported_issue' => 'Issue',
        'internal_notes' => 'Internal Notes',
        'updated_at' => 'Last Updated',
    ];
} else {
    $title = 'Activity Log';
    $activityFilters = [
        'query' => trim((string)($params['query'] ?? '')),
        'actor' => trim((string)($params['actor'] ?? '')),
        'event_type' => trim((string)($params['event_type'] ?? 'all')),
        'field_name' => trim((string)($params['field_name'] ?? '')),
        'date_from' => trim((string)($params['date_from'] ?? '')),
        'date_to' => trim((string)($params['date_to'] ?? '')),
    ];

    if (!in_array($activityFilters['event_type'], ['all', 'service_call', 'system'], true)) {
        $activityFilters['event_type'] = 'all';
    }

    foreach (['date_from', 'date_to'] as $dateField) {
        if ($activityFilters[$dateField] === '') {
            continue;
        }
        $date = DateTime::createFromFormat('Y-m-d', $activityFilters[$dateField]);
        if (!($date instanceof DateTime) || $date->format('Y-m-d') !== $activityFilters[$dateField]) {
            $activityFilters[$dateField] = '';
        }
    }

    if ($activityFilters['date_from'] !== '' && $activityFilters['date_to'] !== '' && $activityFilters['date_from'] > $activityFilters['date_to']) {
        [$activityFilters['date_from'], $activityFilters['date_to']] = [$activityFilters['date_to'], $activityFilters['date_from']];
    }

    $rows = ServiceCall::findRecentActivity(500, 0, $activityFilters);
    foreach ($rows as &$row) {
        $actorName = trim((string)($row['changed_by_name'] ?? ''));
        $row['changed_by_name'] = $actorName !== '' ? $actorName : 'System';
    }
    unset($row);

    $columns = [
        'created_at' => 'When',
        'changed_by_name' => 'User',
        'job_number' => 'Job #',
        'customer' => 'Customer',
        'location' => 'Location',
        'field_name' => 'Field',
        'old_value' => 'Old Value',
        'new_value' => 'New Value',
        'note' => 'Note',
    ];
}

$timestamp = date('Ymd_His');

if ($format === 'csv') {
    $filename = 'servicebook_' . $source . '_' . $timestamp . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'wb');
    if ($output === false) {
        http_response_code(500);
        echo 'Could not create export stream.';
        exit;
    }

    fputcsv($output, array_values($columns));

    foreach ($rows as $row) {
        $line = [];
        foreach ($columns as $field => $label) {
            $value = $row[$field] ?? '';
            if (is_string($value)) {
                $value = trim($value);
            }
            $line[] = $value;
        }
        fputcsv($output, $line);
    }

    fclose($output);
    exit;
}

$prefKey = 'print_view.columns.' . $source;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_columns') {
    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        http_response_code(400);
        echo 'Your session expired. Please reload and try again.';
        exit;
    }

    $selectedColumns = array_values(array_intersect(array_keys($columns), (array)($_POST['columns'] ?? [])));
    if (empty($selectedColumns)) {
        $selectedColumns = array_keys($columns);
    }
    UserPreference::set($userId, $prefKey, implode(',', $selectedColumns));

    $redirectParams = $params;
    unset($redirectParams['action'], $redirectParams['columns'], $redirectParams['_csrf_token']);
    $redirectParams['format'] = 'print';
    header('Location: ' . url('public/data_view.php') . '?' . http_build_query($redirectParams));
    exit;
}

// Determine which columns this user has chosen to show for this view (defaults to all).
$visibleColumns = $columns;
$savedColumnsPref = UserPreference::get($userId, $prefKey, '');
if ($savedColumnsPref !== null && trim($savedColumnsPref) !== '') {
    $savedColumnKeys = array_filter(array_map('trim', explode(',', $savedColumnsPref)));
    $orderedColumns = [];
    foreach ($savedColumnKeys as $columnKey) {
        if (isset($columns[$columnKey])) {
            $orderedColumns[$columnKey] = $columns[$columnKey];
        }
    }
    if (!empty($orderedColumns)) {
        $visibleColumns = $orderedColumns;
    }
}

apply_security_headers();

$summaryParts = [];
if ($search !== '') {
    $summaryParts[] = 'Search: "' . $search . '"';
}
if ($source === 'calls') {
    $summaryParts[] = 'Filter: ' . $filter;
}
if ($source === 'technician' && isset($techName) && $techId > 0) {
    $summaryParts[] = 'Technician: ' . $techName;
}
if ($source === 'activity' && isset($activityFilters)) {
    if ($activityFilters['event_type'] !== 'all') {
        $summaryParts[] = 'Event Type: ' . $activityFilters['event_type'];
    }
    if ($activityFilters['actor'] !== '') {
        $summaryParts[] = 'Actor: ' . $activityFilters['actor'];
    }
    if ($activityFilters['field_name'] !== '') {
        $summaryParts[] = 'Field: ' . $activityFilters['field_name'];
    }
    if ($activityFilters['date_from'] !== '') {
        $summaryParts[] = 'From: ' . $activityFilters['date_from'];
    }
    if ($activityFilters['date_to'] !== '') {
        $summaryParts[] = 'To: ' . $activityFilters['date_to'];
    }
}

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape($title) ?> Print View</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111;
            margin: 16px;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 22px;
        }

        .meta {
            margin-bottom: 12px;
            color: #444;
            font-size: 13px;
        }

        .meta-line {
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid #cfcfcf;
            padding: 6px;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #f2f2f2;
            font-weight: 600;
        }

        .columns-picker {
            border: 1px solid #cfcfcf;
            background: #f9f9f9;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .columns-picker summary {
            cursor: pointer;
            font-weight: 600;
        }

        .columns-picker .columns-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 16px;
            margin: 10px 0;
        }

        .columns-picker label {
            font-weight: normal;
            white-space: nowrap;
        }

        @media print {
            .print-actions,
            .columns-picker {
                display: none;
            }

            body {
                margin: 8mm;
            }
        }
    </style>
</head>
<body>
<div class="print-actions" style="margin-bottom: 12px;">
    <button type="button" onclick="window.print();">Print</button>
</div>
<details class="columns-picker">
    <summary>Show/Hide Columns</summary>
    <form method="post" action="<?= escape(url('public/data_view.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_columns">
        <?php foreach ($_GET as $paramName => $paramValue): ?>
            <?php if ($paramName === 'columns'): continue; endif; ?>
            <?php if (is_array($paramValue)): ?>
                <?php foreach ($paramValue as $paramItem): ?>
                    <input type="hidden" name="<?= escape((string)$paramName) ?>[]" value="<?= escape((string)$paramItem) ?>">
                <?php endforeach; ?>
            <?php else: ?>
                <input type="hidden" name="<?= escape((string)$paramName) ?>" value="<?= escape((string)$paramValue) ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <div class="columns-grid">
            <?php foreach ($columns as $columnField => $columnLabel): ?>
                <label>
                    <input type="checkbox" name="columns[]" value="<?= escape($columnField) ?>"
                        <?= isset($visibleColumns[$columnField]) ? 'checked' : '' ?>>
                    <?= escape($columnLabel) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <button type="submit">Apply</button>
    </form>
</details>
<h1><?= escape($title) ?></h1>
<div class="meta">
    <div>Generated: <?= escape(date('Y-m-d H:i')) ?></div>
    <div class="meta-line">Rows: <?= count($rows) ?></div>
    <?php if (!empty($summaryParts)): ?>
        <div class="meta-line"><?= escape(implode(' | ', $summaryParts)) ?></div>
    <?php endif; ?>
</div>
<table>
    <thead>
    <tr>
        <?php foreach ($visibleColumns as $label): ?>
            <th><?= escape($label) ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
        <tr>
            <td colspan="<?= count($visibleColumns) ?>">No records found.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($visibleColumns as $field => $label): ?>
                    <td><?= nl2br(escape((string)($row[$field] ?? ''))) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</body>
</html>
