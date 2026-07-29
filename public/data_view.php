<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Technician.php';
require_once __DIR__ . '/../src/Helpers.php';

Auth::requireLogin();
$user = Auth::currentUser() ?? [];

$format = strtolower(trim((string)($_GET['format'] ?? 'csv')));
if (!in_array($format, ['csv', 'print'], true)) {
    $format = 'csv';
}

$source = strtolower(trim((string)($_GET['source'] ?? 'calls')));
if (!in_array($source, ['calls', 'search', 'technician', 'activity'], true)) {
    $source = 'calls';
}

$search = trim((string)($_GET['search'] ?? ''));
$filter = trim((string)($_GET['filter'] ?? 'incomplete'));
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
        $techId = (int)($_GET['technician_id'] ?? 0);
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
        'query' => trim((string)($_GET['query'] ?? '')),
        'actor' => trim((string)($_GET['actor'] ?? '')),
        'event_type' => trim((string)($_GET['event_type'] ?? 'all')),
        'field_name' => trim((string)($_GET['field_name'] ?? '')),
        'date_from' => trim((string)($_GET['date_from'] ?? '')),
        'date_to' => trim((string)($_GET['date_to'] ?? '')),
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

        @media print {
            .print-actions {
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
        <?php foreach ($columns as $label): ?>
            <th><?= escape($label) ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
        <tr>
            <td colspan="<?= count($columns) ?>">No records found.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($columns as $field => $label): ?>
                    <td><?= nl2br(escape((string)($row[$field] ?? ''))) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</body>
</html>
