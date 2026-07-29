<div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-4 gap-3">
    <div>
        <h1 class="h3">Search Service Calls</h1>
        <p class="text-muted mb-0">Search by job number, customer, location, PO number, or issue.</p>
    </div>
    <form class="d-flex" method="get" action="<?= url('public/search.php') ?>">
        <input class="form-control me-2" type="search" name="search" placeholder="Search calls" value="<?= escape($search) ?>">
        <button class="btn btn-primary" type="submit">Search</button>
    </form>
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
            <th>Priority</th>
            <th>Issue</th>
            <th>PO Number</th>
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
                    <td><?= escape($call['job_number']) ?></td>
                    <td><?= escape(date('Y-m-d H:i', strtotime($call['received_date']))) ?></td>
                    <td><?= escape($call['customer']) ?></td>
                    <td><?= escape($call['location']) ?></td>
                    <td><?= escape($call['assigned_tech_name'] ?: 'Unassigned') ?></td>
                    <td><?= escape($call['status']) ?></td>
                    <td><?= escape($call['priority']) ?></td>
                    <td><div class="truncate-2"><?= escape(truncate($call['reported_issue'], 120)) ?></div></td>
                    <td><?= escape($call['po_number']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
