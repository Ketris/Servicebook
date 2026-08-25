<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape($app_site_title) ?> | Job #<?= escape((string)$call['job_number']) ?></title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { color: #171717; font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; margin: 0; }
        main { margin: 18mm auto; max-width: 190mm; }
        h1, h2, p { margin-top: 0; }
        h1 { font-size: 23px; margin-bottom: 4px; }
        h2 { border-bottom: 1px solid #777; font-size: 14px; margin: 20px 0 8px; padding-bottom: 4px; }
        .muted { color: #555; }
        .header, .details { display: grid; gap: 12px; }
        .header { grid-template-columns: 1fr auto; align-items: start; }
        .details { grid-template-columns: repeat(3, 1fr); }
        .detail, .section { break-inside: avoid; }
        .label { color: #555; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .value { margin-top: 2px; overflow-wrap: anywhere; }
        .section { border: 1px solid #aaa; margin-top: 12px; padding: 10px; white-space: pre-wrap; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #aaa; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #eee; font-size: 10px; text-transform: uppercase; }
        @media print {
            body { font-size: 11px; }
            main { margin: 8mm auto; }
        }
    </style>
</head>
<body>
    <main>
        <header class="header">
            <div>
                <h1><?= escape($app_site_title) ?></h1>
                <p class="muted">Service Call Detail</p>
            </div>
            <div class="muted">Printed <?= escape($printedAt) ?></div>
        </header>

        <h2>Job #<?= escape((string)$call['job_number']) ?></h2>
        <div class="details">
            <div class="detail"><div class="label">Customer</div><div class="value"><?= escape($call['customer']) ?></div></div>
            <div class="detail"><div class="label">Location</div><div class="value"><?= escape($call['location']) ?></div></div>
            <div class="detail"><div class="label">Status</div><div class="value"><?= escape($call['status']) ?></div></div>
            <div class="detail"><div class="label">Received</div><div class="value"><?= escape(date('Y-m-d H:i', strtotime($call['received_date']))) ?></div></div>
            <div class="detail"><div class="label">Assigned Technician</div><div class="value"><?= escape((string)($call['assigned_tech_name'] ?? 'Unassigned')) ?></div></div>
            <div class="detail"><div class="label">PO Number</div><div class="value"><?= escape($call['po_number'] ?: '-') ?></div></div>
            <div class="detail"><div class="label">Contact</div><div class="value"><?= escape($call['contact'] ?: '-') ?></div></div>
            <div class="detail"><div class="label">Phone</div><div class="value"><?= escape($call['phone'] ?: '-') ?></div></div>
            <div class="detail"><div class="label">Email</div><div class="value"><?= escape($call['email'] ?: '-') ?></div></div>
        </div>

        <div class="section"><div class="label">Reported Issue</div><div class="value"><?= escape($call['reported_issue']) ?></div></div>
        <div class="section"><div class="label">Internal Notes</div><div class="value"><?= escape($call['internal_notes'] ?: '-') ?></div></div>

        <?php if (!empty($history)): ?>
            <h2>Change History</h2>
            <table>
                <thead><tr><th>When</th><th>By</th><th>Change</th><th>Details</th></tr></thead>
                <tbody>
                    <?php foreach ($history as $entry): ?>
                        <tr>
                            <td><?= escape(date('Y-m-d H:i', strtotime($entry['created_at']))) ?></td>
                            <td><?= escape(trim((string)($entry['changed_by_name'] ?? '')) ?: 'System') ?></td>
                            <td><?= escape($entry['field_name']) ?></td>
                            <td><?php if (!empty($entry['note'])): ?><?= escape($entry['note']) ?><br><?php endif; ?><?php if ($entry['old_value'] !== null || $entry['new_value'] !== null): ?>From: <?= escape($entry['old_value'] ?? '-') ?> to <?= escape($entry['new_value'] ?? '-') ?><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
        window.addEventListener('afterprint', function () {
            window.close();
        });
    </script>
</body>
</html>