<div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-4 gap-3">
    <div>
        <h1 class="h3">Service Calls</h1>
        <p class="text-muted mb-0">Showing <?=
            $filter === 'all' ? 'all' : (
                $filter === 'unassigned' ? 'unassigned' : (
                    $filter === 'completed_today' ? 'completed today' : (
                        $filter === 'completed_week' ? 'completed this week' : 'incomplete'
                    )
                )
            )
        ?> work orders by default. Search by job number, customer, location, PO number, or issue.</p>
    </div>
    <form class="d-flex flex-wrap gap-2 align-items-center" method="get" action="<?= url('public/index.php') ?>">
        <input class="form-control" type="search" name="search" placeholder="Search calls" value="<?= escape($search) ?>">
        <div class="d-flex align-items-center gap-2">
            <label class="small text-muted mb-0" for="status-filter">Filter</label>
            <select class="form-select form-select-sm w-auto" id="status-filter" name="filter">
                <option value="incomplete" <?= $filter === 'incomplete' ? 'selected' : '' ?>>Incomplete</option>
                <option value="unassigned" <?= $filter === 'unassigned' ? 'selected' : '' ?>>Unassigned</option>
                <option value="completed_today" <?= $filter === 'completed_today' ? 'selected' : '' ?>>Completed Today</option>
                <option value="completed_week" <?= $filter === 'completed_week' ? 'selected' : '' ?>>Completed This Week</option>
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Search</button>
    </form>
</div>
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
                <div class="small text-muted">Completed Today</div>
                <div class="h4 mb-0"><?= escape((string)($stats['completed_today'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Completed This Week</div>
                <div class="h4 mb-0"><?= escape((string)($stats['completed_this_week'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
</div>
<?php
$showingStatus = false;
$filterLabel = $filter === 'all' ? 'all' : (
    $filter === 'unassigned' ? 'unassigned' : (
        $filter === 'completed_today' ? 'completed today' : (
            $filter === 'completed_week' ? 'completed this week' : 'incomplete'
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
        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Columns</button>
            <ul class="dropdown-menu p-3" style="min-width: 220px;">
                <?php foreach (['job_number' => 'Job #', 'received_date' => 'Received', 'customer' => 'Customer', 'location' => 'Location', 'assigned_tech_name' => 'Technician', 'status' => 'Status', 'priority' => 'Priority', 'reported_issue' => 'Issue', 'po_number' => 'PO Number'] as $field => $label): ?>
                    <li class="form-check">
                        <input class="form-check-input column-toggle" type="checkbox" data-column="<?= $field ?>" id="col_<?= $field ?>" checked>
                        <label class="form-check-label" for="col_<?= $field ?>"><?= $label ?></label>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <small class="text-muted mb-0">Click headers to sort. Drag the right edge to resize.</small>
        <span id="call-count" class="badge rounded-pill text-bg-light border">Showing <?= count($calls) ?> calls</span>
        <button type="button" class="btn btn-sm btn-link text-decoration-none text-muted p-0" id="reset-table-button" title="Reset table preferences" aria-label="Reset table preferences">↺</button>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
        <tr>
            <th data-column="job_number">Job #</th>
            <th data-column="received_date">Received</th>
            <th data-column="customer">Customer</th>
            <th data-column="location">Location</th>
            <th data-column="assigned_tech_name">Technician</th>
            <th data-column="status">Status</th>
            <th data-column="priority">Priority</th>
            <th data-column="reported_issue">Issue</th>
            <th data-column="po_number">PO Number</th>
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
                    <td data-column="job_number"><?= escape($call['job_number']) ?></td>
                    <td data-column="received_date"><?= escape(date('Y-m-d H:i', strtotime($call['received_date']))) ?></td>
                    <td data-column="customer"><?= escape($call['customer']) ?></td>
                    <td data-column="location"><?= escape($call['location']) ?></td>
                    <td data-column="assigned_tech_name"><?= escape($call['assigned_tech_name'] ?? 'Unassigned') ?></td>
                    <td data-column="status"><?= escape($call['status']) ?></td>
                    <td data-column="priority"><?= escape($call['priority']) ?></td>
                    <td data-column="reported_issue"><div class="truncate-2"><?= escape(truncate($call['reported_issue'], 120)) ?></div></td>
                    <td data-column="po_number"><?= escape($call['po_number']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
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

        const handle = header.querySelector('.resize-handle');
        if (!handle) return;

        handle.addEventListener('mousedown', (event) => {
            event.stopPropagation();
            const startX = event.pageX;
            const startWidth = header.offsetWidth;

            const onMouseMove = (moveEvent) => {
                const delta = moveEvent.pageX - startX;
                const width = Math.max(80, startWidth + delta);
                header.style.width = `${width}px`;
            };
            const onMouseUp = () => {
                state.widths[field] = parseInt(header.offsetWidth, 10);
                persistState();
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            };

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    });

    headers.forEach(header => {
        const field = header.dataset.column;
        if (!field) return;
        header.addEventListener('click', (event) => {
            if (event.target.classList.contains('resize-handle')) return;
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
    });

    applySavedSort();
})();
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
