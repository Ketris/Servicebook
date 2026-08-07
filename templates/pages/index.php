<div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-4 gap-3">
    <div>
        <h1 class="h3">Service Calls</h1>
        <p class="text-muted mb-0">Showing <?=
            $filter === 'all' ? 'all' : (
                $filter === 'unassigned' ? 'unassigned' : (
                    $filter === 'completed_today' ? 'closed today' : (
                        $filter === 'completed_week' ? 'closed this week' : 'incomplete'
                    )
                )
            )
        ?> work orders by default. Search by job number, customer, location, PO number, or issue.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <?php
        $listCsvUrl = url('public/data_view.php?' . http_build_query([
            'source' => 'calls',
            'format' => 'csv',
            'search' => $search,
            'filter' => $filter,
        ]));
        $listPrintUrl = url('public/data_view.php?' . http_build_query([
            'source' => 'calls',
            'format' => 'print',
            'search' => $search,
            'filter' => $filter,
        ]));
        ?>
        <a class="btn btn-outline-secondary" href="<?= escape($listCsvUrl) ?>">Export CSV</a>
        <a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="<?= escape($listPrintUrl) ?>">Print View</a>
    </div>
    <form class="d-flex flex-wrap gap-2 align-items-center" method="get" action="<?= url('public/index.php') ?>">
        <input class="form-control" type="search" name="search" placeholder="Search calls" value="<?= escape($search) ?>">
        <?php if ($selectedViewId > 0): ?>
            <input type="hidden" name="saved_view" value="<?= escape((string)$selectedViewId) ?>">
        <?php endif; ?>
        <div class="d-flex align-items-center gap-2">
            <label class="small text-muted mb-0" for="status-filter">Filter</label>
            <select class="form-select form-select-sm w-auto" id="status-filter" name="filter">
                <option value="incomplete" <?= $filter === 'incomplete' ? 'selected' : '' ?>>Incomplete</option>
                <option value="unassigned" <?= $filter === 'unassigned' ? 'selected' : '' ?>>Unassigned</option>
                <option value="completed_today" <?= $filter === 'completed_today' ? 'selected' : '' ?>>Closed Today</option>
                <option value="completed_week" <?= $filter === 'completed_week' ? 'selected' : '' ?>>Closed This Week</option>
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
            </select>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label class="small text-muted mb-0" for="per-page">Rows</label>
            <select class="form-select form-select-sm w-auto" id="per-page" name="per_page">
                <?php foreach (($allowedPerPage ?? [25, 50, 100, 250]) as $size): ?>
                    <option value="<?= escape((string)$size) ?>" <?= (int)$perPage === (int)$size ? 'selected' : '' ?>><?= escape((string)$size) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Search</button>
    </form>
</div>
<?php if (!empty($errors['form'])): ?>
    <div class="alert alert-danger" role="alert"><?= escape($errors['form']) ?></div>
<?php elseif (!empty($success)): ?>
    <div class="alert alert-success" role="alert"><?= escape($success) ?></div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Open Calls</div>
                <div class="h4 mb-0"><?= escape((string)($stats['open_calls'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Unassigned Open</div>
                <div class="h4 mb-0"><?= escape((string)($stats['unassigned_open_calls'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Closed Today</div>
                <div class="h4 mb-0"><?= escape((string)($stats['completed_today'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Closed This Week</div>
                <div class="h4 mb-0"><?= escape((string)($stats['completed_this_week'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
</div>
<?php
$showingStatus = false;
$filterLabel = $filter === 'all' ? 'all' : (
    $filter === 'unassigned' ? 'unassigned' : (
        $filter === 'completed_today' ? 'closed today' : (
            $filter === 'completed_week' ? 'closed this week' : 'incomplete'
        )
    )
);
?>
<?php if ($search !== '' || $filter !== 'incomplete'): ?>
    <div class="mb-3">
        <small class="text-muted">
            <?php if ($search !== ''): ?>
                Showing calls matching <strong><?= escape($search) ?></strong>
                <?php $showingStatus = true; ?>
            <?php endif; ?>
            <?php if ($filter !== 'incomplete'): ?>
                <?php if ($showingStatus): ?>
                    and
                <?php endif; ?>
                using filter <strong><?= escape($filterLabel) ?></strong>
            <?php endif; ?>
            . (<a href="<?= url('public/index.php') ?>" class="text-decoration-none">Reset</a>)
        </small>
    </div>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a class="btn btn-primary" href="<?= url('public/new_call.php') ?>">New Call</a>
    </div>
    <div class="d-flex align-items-center gap-2">
                <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Columns</button>
            <ul class="dropdown-menu p-3" style="min-width: 220px;">
                <?php foreach (['job_number' => 'Job #', 'received_date' => 'Received', 'customer' => 'Customer', 'location' => 'Location', 'reported_issue' => 'Issue', 'po_number' => 'PO Number', 'status' => 'Status', 'assigned_tech_name' => 'Technician'] as $field => $label): ?>
                    <li class="form-check">
                        <input class="form-check-input column-toggle" type="checkbox" data-column="<?= $field ?>" id="col_<?= $field ?>" checked>
                        <label class="form-check-label" for="col_<?= $field ?>"><?= $label ?></label>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <small class="text-muted mb-0">Click headers to sort. Drag the right edge to resize.</small>
        <?php
        $currentCount = count($calls);
        $pageStart = $currentCount > 0 ? ((($page - 1) * $perPage) + 1) : 0;
        $pageEnd = $currentCount > 0 ? ($pageStart + $currentCount - 1) : 0;
        ?>
        <span id="call-count" class="badge rounded-pill text-bg-light border">
            Showing <?= escape((string)$pageStart) ?>-<?= escape((string)$pageEnd) ?> of <?= escape((string)($totalCalls ?? $currentCount)) ?> calls
        </span>
        <button type="button" class="btn btn-sm btn-link text-decoration-none text-muted p-0" id="reset-table-button" title="Reset table preferences" aria-label="Reset table preferences">↺</button>
    </div>
</div>
<div class="table-responsive">
    <?php if (($user['role'] ?? '') === 'Technician'): ?>
        <div class="alert alert-info mb-3" role="alert">
            Bulk updates are limited to office and admin roles.
        </div>
    <?php else: ?>
    <form method="post" id="bulk-action-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="bulk_update">
    <table class="table table-hover align-middle">
        <thead class="table-light">
        <tr>
            <th data-column="bulk_select" style="width:44px;">
                <input type="checkbox" id="select-all-checkbox" onclick="toggleAllRows(this.checked)">
            </th>
            <th data-column="job_number">Job #</th>
            <th data-column="received_date">Received</th>
            <th data-column="customer">Customer</th>
            <th data-column="location">Location</th>
            <th data-column="reported_issue">Issue</th>
            <th data-column="po_number">PO Number</th>
            <th data-column="status">Status</th>
            <th data-column="assigned_tech_name">Technician</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($calls)): ?>
            <tr>
                <td colspan="9" class="text-center text-muted">No calls found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($calls as $call): ?>
                <tr onclick="window.location='<?= url('public/edit_call.php?id=' . $call['id']) ?>'" style="cursor:pointer;">
                    <td data-column="bulk_select">
                        <input type="checkbox" class="row-check" name="call_ids[]" value="<?= escape((string)$call['id']) ?>" onclick="event.stopPropagation();">
                    </td>
                    <td data-column="job_number"><?= escape($call['job_number']) ?></td>
                    <td data-column="received_date"><?= escape(date('Y-m-d H:i', strtotime($call['received_date']))) ?></td>
                    <td data-column="customer"><?= escape($call['customer']) ?></td>
                    <td data-column="location"><?= escape($call['location']) ?></td>
                    <td data-column="reported_issue"><div class="truncate-2"><?= escape(truncate($call['reported_issue'], 120)) ?></div></td>
                    <td data-column="po_number"><?= escape($call['po_number']) ?></td>
                    <td data-column="status"><?= escape($call['status']) ?></td>
                    <td data-column="assigned_tech_name"><?= escape($call['assigned_tech_name'] ?? 'Unassigned') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </form>
    <?php endif; ?>

    <?php if (($user['role'] ?? '') === 'Technician'): ?>
        <table class="table table-hover align-middle">
            <thead class="table-light">
            <tr>
                <th data-column="job_number">Job #</th>
                <th data-column="received_date">Received</th>
                <th data-column="customer">Customer</th>
                <th data-column="location">Location</th>
                <th data-column="reported_issue">Issue</th>
                <th data-column="po_number">PO Number</th>
                <th data-column="status">Status</th>
                <th data-column="assigned_tech_name">Technician</th>
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
                        <td data-column="job_number"><?= escape($call['job_number']) ?></td>
                        <td data-column="received_date"><?= escape(date('Y-m-d H:i', strtotime($call['received_date']))) ?></td>
                        <td data-column="customer"><?= escape($call['customer']) ?></td>
                        <td data-column="location"><?= escape($call['location']) ?></td>
                        <td data-column="reported_issue"><div class="truncate-2"><?= escape(truncate($call['reported_issue'], 120)) ?></div></td>
                        <td data-column="po_number"><?= escape($call['po_number']) ?></td>
                        <td data-column="status"><?= escape($call['status']) ?></td>
                        <td data-column="assigned_tech_name"><?= escape($call['assigned_tech_name'] ?? 'Unassigned') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php
$indexQueryBase = [
    'search' => $search,
    'filter' => $filter,
    'per_page' => $perPage,
];
if ($selectedViewId > 0) {
    $indexQueryBase['saved_view'] = $selectedViewId;
}
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 mb-3">
    <div class="small text-muted">
        Page <?= escape((string)$page) ?> of <?= escape((string)$totalPages) ?>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php
        $prevParams = $indexQueryBase;
        $prevParams['page'] = max(1, $page - 1);
        $nextParams = $indexQueryBase;
        $nextParams['page'] = min($totalPages, $page + 1);
        ?>
        <a class="btn btn-sm btn-outline-secondary <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $page <= 1 ? '#' : escape(url('public/index.php?' . http_build_query($prevParams))) ?>">Previous</a>
        <a class="btn btn-sm btn-outline-secondary <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : escape(url('public/index.php?' . http_build_query($nextParams))) ?>">Next</a>
    </div>
</div>
<?php if (($user['role'] ?? '') !== 'Technician'): ?>
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h6 mb-0">Bulk Management</h2>
                <small class="text-muted">Apply status or assignment changes to selected calls.</small>
            </div>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#bulk-management-panel" aria-expanded="false" aria-controls="bulk-management-panel">
                Toggle
            </button>
        </div>
        <div id="bulk-management-panel" class="collapse">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label small" for="bulk-status">Bulk Status</label>
                        <select class="form-select form-select-sm" id="bulk-status" name="bulk_status" form="bulk-action-form">
                            <option value="">No status change</option>
                            <option value="New">New</option>
                            <option value="Dispatched">Dispatched</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Waiting Parts">Waiting Parts</option>
                            <option value="On Hold">On Hold</option>
                            <option value="Complete">Complete</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label small" for="bulk-assigned-tech">Bulk Assignment</label>
                        <select class="form-select form-select-sm" id="bulk-assigned-tech" name="bulk_assigned_tech" form="bulk-action-form">
                            <option value="">No assignment change</option>
                            <option value="unassign">Unassign</option>
                            <?php foreach ($technicians as $tech): ?>
                                <option value="<?= escape((string)$tech['id']) ?>"><?= escape($tech['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 col-lg-3 d-grid">
                        <button type="submit" class="btn btn-sm btn-outline-primary" form="bulk-action-form" onclick="return confirmBulkUpdate();">Apply to Selected</button>
                    </div>
                    <div class="col-lg-3 text-lg-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllRows(true)">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllRows(false)">Clear</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h6 mb-0">Saved Views</h2>
            <small class="text-muted">Custom filters, role defaults, and recent view shortcuts.</small>
        </div>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#saved-views-panel" aria-expanded="false" aria-controls="saved-views-panel">
            Toggle
        </button>
    </div>
    <div id="saved-views-panel" class="collapse">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4">
                <label class="form-label" for="saved-view-select">Saved Views</label>
                <form method="get" class="d-flex gap-2">
                    <select id="saved-view-select" class="form-select" name="saved_view">
                        <option value="">Select a saved view</option>
                        <?php foreach ($savedViews as $savedView): ?>
                            <?php
                            $ownedByUser = (int)($savedView['user_id'] ?? 0) > 0;
                            $scopeLabel = $ownedByUser ? 'Personal' : ('Default: ' . ($savedView['role_scope'] ?? ''));
                            ?>
                            <option value="<?= escape((string)$savedView['id']) ?>" <?= (int)$savedView['id'] === (int)$selectedViewId ? 'selected' : '' ?>>
                                <?= escape($savedView['view_name'] . ' (' . $scopeLabel . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-primary" type="submit">Apply</button>
                    <a class="btn btn-outline-secondary" href="<?= url('public/index.php') ?>">Clear</a>
                </form>
            </div>
            <div class="col-lg-8">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="view-name">Save Current Filter as View</label>
                        <form method="post" class="d-flex gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="save_view">
                            <input id="view-name" type="text" class="form-control" name="view_name" maxlength="100" placeholder="My open queue" required>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>
                    </div>
                    <?php if (($user['role'] ?? '') === 'Administrator'): ?>
                        <div class="col-md-8">
                            <label class="form-label" for="default-role-scope">Set Role Default View</label>
                            <form method="post" class="row g-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="save_default_view">
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" name="view_name" maxlength="100" placeholder="Office default" required>
                                </div>
                                <div class="col-sm-4">
                                    <select id="default-role-scope" class="form-select" name="default_role_scope">
                                        <option value="Office Staff">Office Staff</option>
                                        <option value="Technician">Technician</option>
                                        <option value="Administrator">Administrator</option>
                                    </select>
                                </div>
                                <div class="col-sm-4 d-grid">
                                    <button type="submit" class="btn btn-outline-primary">Set Default</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if (!empty($savedViews)): ?>
            <div class="mt-3 d-flex flex-wrap gap-2">
                <?php foreach ($savedViews as $savedView): ?>
                    <?php
                    $isPersonal = (int)($savedView['user_id'] ?? 0) > 0;
                    $canDeleteView = $isPersonal || (($user['role'] ?? '') === 'Administrator' && !$isPersonal);
                    ?>
                    <?php if ($canDeleteView): ?>
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_view">
                            <input type="hidden" name="view_id" value="<?= escape((string)$savedView['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete saved view <?= escape($savedView['view_name']) ?>">Delete <?= escape($savedView['view_name']) ?></button>
                        </form>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($recentSearches)): ?>
            <div class="mt-3">
                <div class="small text-muted mb-2">Recent Views</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($recentSearches as $recent): ?>
                        <?php
                        $recentSearchTerm = trim((string)($recent['search'] ?? ''));
                        $recentFilter = trim((string)($recent['filter'] ?? 'incomplete'));
                        $recentSavedView = (int)($recent['saved_view'] ?? 0);
                        $recentViewName = trim((string)($recent['view_name'] ?? ''));
                        $recentLabel = $recentViewName !== ''
                            ? $recentViewName
                            : ($recentSearchTerm !== '' ? $recentSearchTerm : ('Filter: ' . $recentFilter));
                        $recentParams = [
                            'search' => $recentSearchTerm,
                            'filter' => $recentFilter,
                        ];
                        if ($recentSavedView > 0) {
                            $recentParams['saved_view'] = $recentSavedView;
                        }
                        $recentUrl = url('public/index.php?' . http_build_query($recentParams));
                        ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= escape($recentUrl) ?>" title="Recent view"><?= escape($recentLabel) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    </div>
</div>
<script>
(function () {
    const table = document.querySelector('table');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const headers = table.querySelectorAll('thead th');
    let sortField = null;
    let sortDirection = 1;

    headers.forEach(header => {
        header.style.position = 'relative';
        const handle = document.createElement('span');
        handle.className = 'resize-handle';
        header.appendChild(handle);

        header.addEventListener('click', (event) => {
            if (event.target === handle) return;
            const field = header.dataset.column;
            if (!field) return;
            if (sortField === field) {
                sortDirection = -sortDirection;
            } else {
                sortField = field;
                sortDirection = 1;
            }
            state.sort = { field: sortField, direction: sortDirection };
            persistState();
            sortTable(field, sortDirection);
        });

        handle.addEventListener('mousedown', (event) => {
            event.stopPropagation();
            const startX = event.pageX;
            const startWidth = header.offsetWidth;

            const onMouseMove = (moveEvent) => {
                const delta = moveEvent.pageX - startX;
                header.style.width = `${Math.max(80, startWidth + delta)}px`;
            };
            const onMouseUp = () => {
                const field = header.dataset.column;
                if (field) {
                    state.widths[field] = parseInt(header.offsetWidth, 10);
                    persistState();
                }
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            };

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    });

    function sortTable(field, direction) {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            const aCell = a.querySelector(`[data-column="${field}"]`)?.textContent.trim() || '';
            const bCell = b.querySelector(`[data-column="${field}"]`)?.textContent.trim() || '';
            if (field === 'received_date') {
                return direction * (new Date(aCell) - new Date(bCell));
            }
            if (!isNaN(aCell) && !isNaN(bCell)) {
                return direction * ((parseFloat(aCell) || 0) - (parseFloat(bCell) || 0));
            }
            return direction * aCell.localeCompare(bCell, undefined, { numeric: true, sensitivity: 'base' });
        });
        rows.forEach(row => tbody.appendChild(row));
    }

    const storageKey = 'servicebook_index_prefs';
    const savedState = JSON.parse(localStorage.getItem(storageKey) || '{}');
    const state = {
        columns: savedState.columns || {},
        widths: savedState.widths || {},
        sort: savedState.sort || null,
    };

    function persistState() {
        localStorage.setItem(storageKey, JSON.stringify(state));
    }

    function applyColumnVisibility(column, visible) {
        table.querySelectorAll(`[data-column="${column}"]`).forEach(cell => {
            cell.style.display = visible ? '' : 'none';
        });
    }

    function updateVisibleRowCount() {
        const allRows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.querySelector('td[data-column]'));
        const countElement = document.getElementById('call-count');

        if (!countElement) {
            return;
        }
        countElement.textContent = `Showing ${allRows.length} calls`;
    }

    function applyColumnWidth(column, width) {
        const header = table.querySelector(`thead th[data-column="${column}"]`);
        if (header) {
            header.style.width = `${width}px`;
        }
    }

    function applySavedSort() {
        if (!state.sort || !state.sort.field) {
            return;
        }
        sortField = state.sort.field;
        sortDirection = state.sort.direction;
        sortTable(sortField, sortDirection);
    }

    function resetTablePreferences() {
        localStorage.removeItem(storageKey);
        Object.keys(state.columns).forEach(column => delete state.columns[column]);
        Object.keys(state.widths).forEach(column => delete state.widths[column]);
        state.sort = null;
        headers.forEach(header => header.style.width = '');
        document.querySelectorAll('.column-toggle').forEach(input => {
            input.checked = true;
            applyColumnVisibility(input.dataset.column, true);
        });
        sortField = null;
        sortDirection = 1;
        updateVisibleRowCount();
    }

    const resetButton = document.getElementById('reset-table-button');
    if (resetButton) {
        resetButton.addEventListener('click', resetTablePreferences);
    }

    updateVisibleRowCount();

    document.querySelectorAll('.column-toggle').forEach(input => {
        const column = input.dataset.column;
        if (state.columns.hasOwnProperty(column)) {
            input.checked = state.columns[column];
        }
        applyColumnVisibility(column, input.checked);

        input.addEventListener('change', () => {
            const visible = input.checked;
            applyColumnVisibility(column, visible);
            state.columns[column] = visible;
            persistState();
        });
    });

    headers.forEach(header => {
        const field = header.dataset.column;
        if (field && state.widths[field]) {
            applyColumnWidth(field, state.widths[field]);
        }
    });

    applySavedSort();

})();

function toggleAllRows(checked) {
    document.querySelectorAll('.row-check').forEach(function (checkbox) {
        checkbox.checked = !!checked;
    });

    var master = document.getElementById('select-all-checkbox');
    if (master) {
        master.checked = !!checked;
    }
}

function confirmBulkUpdate() {
    var selectedCount = document.querySelectorAll('.row-check:checked').length;
    if (selectedCount === 0) {
        alert('Select at least one call before applying a bulk update.');
        return false;
    }
    return confirm('Apply the selected bulk update to ' + selectedCount + ' call(s)?');
}
</script>
<style>
.resize-handle {
    position: absolute;
    right: 0;
    top: 0;
    width: 8px;
    height: 100%;
    cursor: col-resize;
    user-select: none;
}
</style>
