<?php
/** @var array<int, array<string, mixed>> $activity */
/** @var int $page */
/** @var int $perPage */
/** @var int $totalPages */
/** @var int $totalActivity */
/** @var array<string, string> $activityFilters */

$startRow = $totalActivity === 0 ? 0 : (($page - 1) * $perPage) + 1;
$endRow = min($totalActivity, $page * $perPage);
$previousPage = max(1, $page - 1);
$nextPage = min($totalPages, $page + 1);

$filterParams = [
    'query' => trim((string)($activityFilters['query'] ?? '')),
    'actor' => trim((string)($activityFilters['actor'] ?? '')),
    'event_type' => trim((string)($activityFilters['event_type'] ?? 'all')),
    'field_name' => trim((string)($activityFilters['field_name'] ?? '')),
    'date_from' => trim((string)($activityFilters['date_from'] ?? '')),
    'date_to' => trim((string)($activityFilters['date_to'] ?? '')),
];

$exportParams = array_merge([
    'source' => 'activity',
    'format' => 'csv',
], $filterParams);

$printParams = array_merge([
    'source' => 'activity',
    'format' => 'print',
], $filterParams);

$paginationBaseParams = array_merge(['per_page' => $perPage], $filterParams);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Activity Log</h1>
        <p class="text-muted mb-0">Most recent service-call changes and audit events.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php
        $activityCsvUrl = url('public/data_view.php?' . http_build_query($exportParams));
        $activityPrintUrl = url('public/data_view.php?' . http_build_query($printParams));
        ?>
        <a class="btn btn-outline-secondary" href="<?= escape($activityCsvUrl) ?>">Export CSV</a>
        <a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="<?= escape($activityPrintUrl) ?>">Print View</a>
        <a class="btn btn-secondary" href="<?= url('admin/index.php') ?>">Back to Dashboard</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label small" for="activity-query">Search</label>
                <input id="activity-query" name="query" class="form-control form-control-sm" type="search" value="<?= escape($filterParams['query']) ?>" placeholder="Job, field, note, value...">
            </div>
            <div class="col-lg-2 col-md-4">
                <label class="form-label small" for="activity-actor">Actor</label>
                <input id="activity-actor" name="actor" class="form-control form-control-sm" type="text" value="<?= escape($filterParams['actor']) ?>" placeholder="Admin, System...">
            </div>
            <div class="col-lg-2 col-md-4">
                <label class="form-label small" for="activity-event-type">Event Type</label>
                <select id="activity-event-type" name="event_type" class="form-select form-select-sm">
                    <option value="all" <?= $filterParams['event_type'] === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="service_call" <?= $filterParams['event_type'] === 'service_call' ? 'selected' : '' ?>>Service Call</option>
                    <option value="system" <?= $filterParams['event_type'] === 'system' ? 'selected' : '' ?>>System</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <label class="form-label small" for="activity-field-name">Field</label>
                <input id="activity-field-name" name="field_name" class="form-control form-control-sm" type="text" value="<?= escape($filterParams['field_name']) ?>" placeholder="status, user_account...">
            </div>
            <div class="col-lg-1 col-md-6">
                <label class="form-label small" for="activity-date-from">From</label>
                <input id="activity-date-from" name="date_from" class="form-control form-control-sm" type="date" value="<?= escape($filterParams['date_from']) ?>">
            </div>
            <div class="col-lg-1 col-md-6">
                <label class="form-label small" for="activity-date-to">To</label>
                <input id="activity-date-to" name="date_to" class="form-control form-control-sm" type="date" value="<?= escape($filterParams['date_to']) ?>">
            </div>
            <div class="col-lg-1 d-grid">
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="per_page" value="<?= escape((string)$perPage) ?>">
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
            </div>
        </form>
        <div class="mt-2">
            <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/activity.php') ?>">Clear Filters</a>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div class="small text-muted">
        Showing <?= escape((string)$startRow) ?>-<?= escape((string)$endRow) ?> of <?= escape((string)$totalActivity) ?> events.
    </div>
    <form method="get" class="d-flex align-items-center gap-2">
        <input type="hidden" name="page" value="1">
        <label class="small text-muted mb-0" for="per-page">Rows</label>
        <select id="per-page" name="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <?php foreach ([25, 50, 100, 250] as $option): ?>
                <option value="<?= $option ?>" <?= $option === $perPage ? 'selected' : '' ?>><?= $option ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>When</th>
                    <th>User</th>
                    <th>Job</th>
                    <th>Field</th>
                    <th>Change</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activity)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No activity found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activity as $entry): ?>
                        <tr>
                            <td class="text-nowrap"><?= escape(date('Y-m-d H:i', strtotime($entry['created_at']))) ?></td>
                            <td><?= escape($entry['changed_by_name'] ?? 'System') ?></td>
                            <td>
                                <?php if (!empty($entry['service_call_id'])): ?>
                                    <a href="<?= url('public/edit_call.php?id=' . $entry['service_call_id']) ?>" class="text-decoration-none"><?= escape($entry['job_number'] ?? ('Call #' . $entry['service_call_id'])) ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= escape($entry['field_name'] ?? '') ?></td>
                            <td>
                                <div class="small text-muted">From: <?= escape($entry['old_value'] ?? '-') ?></div>
                                <div class="small">To: <?= escape($entry['new_value'] ?? '-') ?></div>
                            </td>
                            <td class="small"><?= escape($entry['note'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <a class="btn btn-outline-secondary btn-sm <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $page <= 1 ? '#' : url('admin/activity.php?' . http_build_query(array_merge($paginationBaseParams, ['page' => $previousPage]))) ?>" <?= $page <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Previous</a>
        <div class="small text-muted">Page <?= escape((string)$page) ?> of <?= escape((string)$totalPages) ?></div>
        <a class="btn btn-outline-secondary btn-sm <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : url('admin/activity.php?' . http_build_query(array_merge($paginationBaseParams, ['page' => $nextPage]))) ?>" <?= $page >= $totalPages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Next</a>
    </div>
<?php endif; ?>
