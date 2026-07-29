<div class="app-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
    <div>
        <h1 class="h3 mb-1">My Jobs</h1>
        <p class="text-muted mb-0">A technician-focused view of the work currently assigned to you, with quick updates and optional claim actions.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php
        $techCsvUrl = url('public/data_view.php?' . http_build_query([
            'source' => 'technician',
            'format' => 'csv',
        ]));
        $techPrintUrl = url('public/data_view.php?' . http_build_query([
            'source' => 'technician',
            'format' => 'print',
        ]));
        ?>
        <a class="btn btn-outline-secondary" href="<?= escape($techCsvUrl) ?>">Export CSV</a>
        <a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="<?= escape($techPrintUrl) ?>">Print View</a>
        <a class="btn btn-outline-secondary" href="<?= url('public/index.php') ?>">View All Calls</a>
        <a class="btn btn-primary" href="<?= url('public/new_call.php') ?>">New Call</a>
    </div>
</div>

<?php if (isset($errors['form'])): ?>
    <div class="alert alert-danger mb-4" role="alert"><?= escape($errors['form']) ?></div>
<?php elseif ($successMessage !== ''): ?>
    <div class="alert alert-success mb-4" role="alert"><?= escape($successMessage) ?></div>
<?php endif; ?>

<?php if (!$technicianLinked): ?>
    <div class="alert alert-warning" role="alert">
        Your login is not linked to a technician profile yet. An administrator needs to link this account before you can claim or manage jobs from this dashboard.
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="small text-muted">My Active Jobs</div>
                <div class="stat-value"><?= escape((string)($stats['active_jobs'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="small text-muted">In Progress</div>
                <div class="stat-value"><?= escape((string)($stats['in_progress_jobs'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="small text-muted">Needs Attention</div>
                <div class="stat-value"><?= escape((string)($stats['needs_attention_jobs'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="small text-muted">Closed Today</div>
                <div class="stat-value"><?= escape((string)($stats['completed_today'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xxl-8">
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h2 class="h5 mb-1">Active Jobs</h2>
                <p class="text-muted mb-0">Your open jobs are shown as action cards for quick status updates.</p>
            </div>
            <span class="badge rounded-pill text-bg-light border"><?= count($activeJobs) ?> active</span>
        </div>

        <?php if (empty($activeJobs)): ?>
            <div class="card">
                <div class="card-body py-4">
                    <p class="text-muted mb-0">No active jobs are currently assigned to you.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="d-grid gap-3">
                <?php foreach ($activeJobs as $job): ?>
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="job-card-header mb-3">
                                <div>
                                    <div class="small text-muted mb-1">Job #<?= escape($job['job_number']) ?></div>
                                    <h3 class="h5 mb-1"><?= escape($job['customer']) ?></h3>
                                    <div class="text-muted"><?= escape($job['location']) ?></div>
                                </div>
                                <div class="job-card-meta">
                                    <span class="badge rounded-pill <?= status_badge_class((string)$job['status']) ?>"><?= escape($job['status']) ?></span>
                                </div>
                            </div>

                            <div class="row g-3 mb-3 job-detail-list">
                                <div class="col-sm-6 col-lg-4">
                                    <dt>Received</dt>
                                    <dd><?= escape(date('Y-m-d H:i', strtotime($job['received_date']))) ?></dd>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <dt>PO Number</dt>
                                    <dd><?= escape($job['po_number'] ?: '—') ?></dd>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <dt>Contact</dt>
                                    <dd><?= escape($job['contact'] ?: '—') ?></dd>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <dt>Phone</dt>
                                    <dd><?= escape($job['phone'] ?: '—') ?></dd>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <dt>Email</dt>
                                    <dd class="truncate-2"><?= escape($job['email'] ?: '—') ?></dd>
                                </div>
                                <div class="col-sm-6 col-lg-4">
                                    <dt>Last Updated</dt>
                                    <dd><?= escape(date('Y-m-d H:i', strtotime($job['updated_at']))) ?></dd>
                                </div>
                            </div>

                            <div class="surface-muted rounded-4 p-3 mb-3">
                                <div class="small text-muted mb-1">Reported Issue</div>
                                <div class="truncate-3"><?= nl2br(escape($job['reported_issue'])) ?></div>
                            </div>

                            <form method="post" class="row g-3 align-items-end">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update_job">
                                <input type="hidden" name="call_id" value="<?= escape((string)$job['id']) ?>">
                                <div class="col-md-4">
                                    <label class="form-label" for="status_<?= escape((string)$job['id']) ?>">Status</label>
                                    <select class="form-select" id="status_<?= escape((string)$job['id']) ?>" name="status">
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= escape($status) ?>" <?= $status === $job['status'] ? 'selected' : '' ?>><?= escape($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label" for="note_<?= escape((string)$job['id']) ?>">Technician Note</label>
                                    <textarea class="form-control" id="note_<?= escape((string)$job['id']) ?>" name="technician_note" rows="2" placeholder="Add a quick update, parts note, or completion detail"></textarea>
                                </div>
                                <div class="col-12">
                                    <div class="job-actions">
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="btn btn-outline-secondary" href="<?= url('public/edit_call.php?id=' . $job['id']) ?>">Open Full Job</a>
                                            <?php if (!empty($job['phone'])): ?>
                                                <a class="btn btn-outline-primary" href="tel:<?= escape($job['phone']) ?>">Call Contact</a>
                                            <?php endif; ?>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Save Update</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-xxl-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0">Team Availability</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Technician</th>
                            <th>Open</th>
                            <th>In Progress</th>
                            <th>Load</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($workload as $worker): ?>
                            <tr>
                                <td><?= escape($worker['name']) ?></td>
                                <td><?= escape((string)($worker['open_jobs'] ?? 0)) ?></td>
                                <td><?= escape((string)($worker['in_progress_jobs'] ?? 0)) ?></td>
                                <td>
                                    <?php
                                    $availability = (string)($worker['availability'] ?? 'Normal Load');
                                    $availabilityClass = $availability === 'Heavy Load'
                                        ? 'text-bg-warning'
                                        : ($availability === 'Available' ? 'text-bg-success' : 'text-bg-primary');
                                    ?>
                                    <span class="badge <?= $availabilityClass ?>"><?= escape($availability) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h2 class="h5 mb-1">Unassigned Open Jobs</h2>
                <p class="text-muted mb-0">A short queue of open jobs you can claim quickly.</p>
            </div>
            <span class="badge rounded-pill text-bg-light border"><?= escape((string)($stats['unassigned_open_calls'] ?? 0)) ?> open</span>
        </div>

        <div class="d-grid gap-3">
            <?php if (empty($claimableJobs)): ?>
                <div class="card">
                    <div class="card-body py-4">
                        <p class="text-muted mb-0">There are no unassigned open jobs right now.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($claimableJobs as $job): ?>
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                <div>
                                    <div class="small text-muted mb-1">Job #<?= escape($job['job_number']) ?></div>
                                    <h3 class="h6 mb-1"><?= escape($job['customer']) ?></h3>
                                    <div class="text-muted small"><?= escape($job['location']) ?></div>
                                </div>
                            </div>
                            <div class="small text-muted mb-2">Received <?= escape(date('Y-m-d H:i', strtotime($job['received_date']))) ?></div>
                            <div class="truncate-3 mb-3"><?= nl2br(escape($job['reported_issue'])) ?></div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if ($technicianLinked): ?>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="claim_job">
                                        <input type="hidden" name="call_id" value="<?= escape((string)$job['id']) ?>">
                                        <button type="submit" class="btn btn-success">Claim Job</button>
                                    </form>
                                <?php endif; ?>
                                <a class="btn btn-outline-secondary" href="<?= url('public/edit_call.php?id=' . $job['id']) ?>">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
