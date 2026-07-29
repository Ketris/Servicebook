<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Technician.php';

Auth::requireLogin();
$search = trim($_GET['search'] ?? '');
$calls = ServiceCall::findAll($search);
$technicians = Technician::findAllActive();
?>
<?php include __DIR__ . '/header.php'; ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-4 gap-3">
    <div>
        <h1 class="h3">Service Calls</h1>
        <p class="text-muted mb-0">Showing all work orders. Search by job number, customer, location, PO number, or issue.</p>
    </div>
    <form class="d-flex" method="get" action="<?= url('index.php') ?>">
        <input class="form-control me-2" type="search" name="search" placeholder="Search calls" value="<?= escape($search) ?>">
        <button class="btn btn-primary" type="submit">Search</button>
    </form>
</div>
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
        <div class="d-flex align-items-center gap-2">
            <label class="small text-muted mb-0" for="status-filter">Filter</label>
            <select class="form-select form-select-sm w-auto" id="status-filter" name="status">
                <option value="all">All</option>
                <option value="open">Open</option>
                <option value="complete">Complete</option>
                <?php foreach (ServiceCall::getStatusOptions() as $status): ?>
                    <?php if ($status === 'Complete') continue; ?>
                    <option value="<?= escape($status) ?>"><?= escape($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <small class="text-muted mb-0">Click headers to sort. Drag the right edge to resize.</small>
        <span id="call-count" class="badge rounded-pill text-bg-light border d-none">Showing <?= count($calls) ?> calls</span>
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
                <tr onclick="window.location='<?= url('edit_call.php?id=' . $call['id']) ?>'" style="cursor:pointer;">
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
        const visibleRows = allRows.filter(row => row.style.display !== 'none');
        const countElement = document.getElementById('call-count');

        if (!countElement) {
            return;
        }

        if (countElement.dataset.shown !== 'true') {
            countElement.classList.add('d-none');
            return;
        }

        countElement.classList.remove('d-none');
        countElement.textContent = visibleRows.length === allRows.length
            ? `Showing ${allRows.length} calls`
            : `Showing ${visibleRows.length} of ${allRows.length} calls`;
    }

    function applyStatusFilter(filterValue) {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
            const statusCell = row.querySelector('[data-column="status"]');
            if (!statusCell) return;

            const status = statusCell.textContent.trim();
            let visible = true;

            if (filterValue === 'open') {
                visible = status !== 'Complete';
            } else if (filterValue === 'complete') {
                visible = status === 'Complete';
            } else if (filterValue !== 'all') {
                visible = status === filterValue;
            }

            row.style.display = visible ? '' : 'none';
        });

        updateVisibleRowCount();
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
        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.value = 'all';
            applyStatusFilter('all');
        }
        sortField = null;
        sortDirection = 1;
    }

    const resetButton = document.getElementById('reset-table-button');
    if (resetButton) {
        resetButton.addEventListener('click', resetTablePreferences);
    }

    const statusFilter = document.getElementById('status-filter');
    const countElement = document.getElementById('call-count');
    if (statusFilter) {
        statusFilter.addEventListener('change', (event) => {
            const selectedValue = event.target.value;
            countElement.dataset.shown = selectedValue !== 'all' ? 'true' : 'false';
            applyStatusFilter(selectedValue);
        });
    }

    if (countElement) {
        countElement.dataset.shown = 'false';
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
<?php include __DIR__ . '/footer.php'; ?>
