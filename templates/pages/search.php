<div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-4 gap-3">
    <div>
        <h1 class="h3">Search Service Calls</h1>
        <p class="text-muted mb-0">Search by job number, customer, location, PO number, or issue.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <?php
        $searchCsvUrl = url('public/data_view.php?' . http_build_query([
            'source' => 'search',
            'format' => 'csv',
            'search' => $search,
        ]));
        $searchPrintUrl = url('public/data_view.php?' . http_build_query([
            'source' => 'search',
            'format' => 'print',
            'search' => $search,
        ]));
        ?>
        <a class="btn btn-outline-secondary" href="<?= escape($searchCsvUrl) ?>">Export CSV</a>
        <a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="<?= escape($searchPrintUrl) ?>">Print View</a>
        <form class="d-flex" method="get" action="<?= url('public/search.php') ?>">
            <input class="form-control me-2" type="search" name="search" placeholder="Search calls" value="<?= escape($search) ?>">
            <select class="form-select me-2" name="per_page" style="max-width: 110px;">
                <?php foreach (($allowedPerPage ?? [25, 50, 100, 250]) as $size): ?>
                    <option value="<?= escape((string)$size) ?>" <?= (int)$perPage === (int)$size ? 'selected' : '' ?>><?= escape((string)$size) ?>/page</option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
    </div>
</div>
<?php
$searchCurrentCount = count($calls);
$searchStart = $searchCurrentCount > 0 ? ((($page - 1) * $perPage) + 1) : 0;
$searchEnd = $searchCurrentCount > 0 ? ($searchStart + $searchCurrentCount - 1) : 0;
$searchQueryBase = [
    'search' => $search,
    'per_page' => $perPage,
];
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="small text-muted">Showing <?= escape((string)$searchStart) ?>-<?= escape((string)$searchEnd) ?> of <?= escape((string)($totalCalls ?? $searchCurrentCount)) ?> results</div>
    <div class="d-flex align-items-center gap-2">
        <?php
        $searchPrevParams = $searchQueryBase;
        $searchPrevParams['page'] = max(1, $page - 1);
        $searchNextParams = $searchQueryBase;
        $searchNextParams['page'] = min($totalPages, $page + 1);
        ?>
        <a class="btn btn-sm btn-outline-secondary <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $page <= 1 ? '#' : escape(url('public/search.php?' . http_build_query($searchPrevParams))) ?>">Previous</a>
        <a class="btn btn-sm btn-outline-secondary <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : escape(url('public/search.php?' . http_build_query($searchNextParams))) ?>">Next</a>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
        <tr>
            <th>Job #</th>
            <th>Received</th>
            <th>Customer</th>
            <th>Location</th>
            <th>Technician</th>
            <th>Status</th>
            <th>Issue</th>
            <th>PO Number</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($calls)): ?>
            <tr>
                <td colspan="8" class="text-center text-muted">No calls found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($calls as $call): ?>
                <tr onclick="window.location='<?= url('public/edit_call.php?id=' . $call['id']) ?>'" style="cursor:pointer;">
                    <td><?= escape($call['job_number']) ?></td>
                    <td><?= escape(date('Y-m-d H:i', strtotime($call['received_date']))) ?></td>
                    <td><?= escape($call['customer']) ?></td>
                    <td><?= escape($call['location']) ?></td>
                    <td><?= escape($call['assigned_tech_name'] ?: 'Unassigned') ?></td>
                    <td><?= escape($call['status']) ?></td>
                    <td><div class="truncate-2"><?= escape(truncate($call['reported_issue'], 120)) ?></div></td>
                    <td><?= escape($call['po_number']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
