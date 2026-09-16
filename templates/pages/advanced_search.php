<?php
$filters = $filters ?? [];
$statusOptions = $statusOptions ?? [];
$technicians = $technicians ?? [];
$sort = $sort ?? 'job_number';
$dir = $dir ?? 'desc';

function advField(array $filters, string $key): string
{
    return escape((string)($filters[$key] ?? ''));
}
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-4 gap-3">
    <div>
        <h1 class="h3">Advanced Search</h1>
        <p class="text-muted mb-0">Search service calls using any combination of detailed criteria.</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= url('public/advanced_search.php') ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="adv-job-number">Job Number</label>
                    <input class="form-control" type="text" id="adv-job-number" name="job_number" value="<?= advField($filters, 'job_number') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="adv-customer">Customer</label>
                    <input class="form-control" type="text" id="adv-customer" name="customer" value="<?= advField($filters, 'customer') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="adv-location">Location</label>
                    <input class="form-control" type="text" id="adv-location" name="location" value="<?= advField($filters, 'location') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="adv-contact">Contact</label>
                    <input class="form-control" type="text" id="adv-contact" name="contact" value="<?= advField($filters, 'contact') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="adv-phone">Phone</label>
                    <input class="form-control" type="text" id="adv-phone" name="phone" value="<?= advField($filters, 'phone') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="adv-email">Email</label>
                    <input class="form-control" type="text" id="adv-email" name="email" value="<?= advField($filters, 'email') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="adv-po-number">PO Number</label>
                    <input class="form-control" type="text" id="adv-po-number" name="po_number" value="<?= advField($filters, 'po_number') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="adv-status">Status</label>
                    <select class="form-select" id="adv-status" name="status">
                        <option value="">Any</option>
                        <option value="open" <?= ($filters['status'] ?? '') === 'open' ? 'selected' : '' ?>>Open (not closed)</option>
                        <option value="closed" <?= ($filters['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed (Complete/Cancelled)</option>
                        <?php foreach ($statusOptions as $statusOption): ?>
                            <option value="<?= escape($statusOption) ?>" <?= ($filters['status'] ?? '') === $statusOption ? 'selected' : '' ?>><?= escape($statusOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="adv-assigned-tech">Technician</label>
                    <select class="form-select" id="adv-assigned-tech" name="assigned_tech">
                        <option value="">Any</option>
                        <option value="unassigned" <?= ($filters['assigned_tech'] ?? '') === 'unassigned' ? 'selected' : '' ?>>Unassigned</option>
                        <?php foreach ($technicians as $technician): ?>
                            <option value="<?= escape((string)$technician['id']) ?>" <?= (string)($filters['assigned_tech'] ?? '') === (string)$technician['id'] ? 'selected' : '' ?>><?= escape($technician['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="adv-reported-issue">Reported Issue Contains</label>
                    <input class="form-control" type="text" id="adv-reported-issue" name="reported_issue" value="<?= advField($filters, 'reported_issue') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="adv-internal-notes">Internal Notes Contains</label>
                    <input class="form-control" type="text" id="adv-internal-notes" name="internal_notes" value="<?= advField($filters, 'internal_notes') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="adv-received-from">Received From</label>
                    <input class="form-control" type="date" id="adv-received-from" name="received_from" value="<?= advField($filters, 'received_from') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="adv-received-to">Received To</label>
                    <input class="form-control" type="date" id="adv-received-to" name="received_to" value="<?= advField($filters, 'received_to') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="adv-updated-from">Last Updated From</label>
                    <input class="form-control" type="date" id="adv-updated-from" name="updated_from" value="<?= advField($filters, 'updated_from') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="adv-updated-to">Last Updated To</label>
                    <input class="form-control" type="date" id="adv-updated-to" name="updated_to" value="<?= advField($filters, 'updated_to') ?>">
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <a class="btn btn-outline-secondary" href="<?= url('public/advanced_search.php') ?>">Clear</a>
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>
    </div>
</div>

<?php if (!($hasCriteria ?? false)): ?>
    <p class="text-muted">Enter at least one search criterion above to see results.</p>
<?php else: ?>
    <?php
    $advCurrentCount = count($calls);
    $advStart = $advCurrentCount > 0 ? ((($page - 1) * $perPage) + 1) : 0;
    $advEnd = $advCurrentCount > 0 ? ($advStart + $advCurrentCount - 1) : 0;
    $advQueryBase = array_merge($filters, [
        'per_page' => $perPage,
        'sort' => $sort,
        'dir' => $dir,
    ]);
    ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="small text-muted">Showing <?= escape((string)$advStart) ?>-<?= escape((string)$advEnd) ?> of <?= escape((string)$totalCalls) ?> results</div>
        <nav class="d-flex align-items-center gap-2" aria-label="Advanced search pagination">
            <?php
            $advPrevParams = $advQueryBase;
            $advPrevParams['page'] = max(1, $page - 1);
            $advNextParams = $advQueryBase;
            $advNextParams['page'] = min($totalPages, $page + 1);
            ?>
            <?php if ($page <= 1): ?>
                <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true">Previous</span>
            <?php else: ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= escape(url('public/advanced_search.php?' . http_build_query($advPrevParams))) ?>" rel="prev">Previous</a>
            <?php endif; ?>
            <span class="small text-muted">Page <?= escape((string)$page) ?> of <?= escape((string)$totalPages) ?></span>
            <?php if ($page >= $totalPages): ?>
                <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true">Next</span>
            <?php else: ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= escape(url('public/advanced_search.php?' . http_build_query($advNextParams))) ?>" rel="next">Next</a>
            <?php endif; ?>
        </nav>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                            <tr onclick="window.location='<?= url('public/view_call.php?id=' . $call['id']) ?>'" style="cursor:pointer;">
                                <td><?= escape($call['job_number']) ?></td>
                                <td><?= escape(format_datetime($call['received_date'])) ?></td>
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
        </div>
    </div>
<?php endif; ?>
