<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <div class="d-flex justify-content-between align-items-start mb-4 gap-3">
            <div>
                <h1 class="h3 mb-1">Service Call</h1>
                <p class="text-muted mb-0">Job #<?= escape($call['job_number']) ?> | Created <?= escape(format_datetime($call['created_at'])) ?></p>
                <p class="text-muted mb-0">Last Modified <?= escape(format_datetime($lastModifiedAt)) ?> by <?= escape((string)$lastModifiedBy) ?></p>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <a class="btn btn-secondary" href="<?= $backUrl ?>">Back</a>
                <button type="button" class="btn btn-outline-secondary" onclick="window.open('<?= url('public/print_call.php?id=' . (int)$call['id']) ?>', 'servicebook_print', 'popup,width=1000,height=800');">Print</button>
                <?php if ($canEditDetails): ?>
                    <a class="btn btn-primary" href="<?= url('public/edit_call.php?id=' . (int)$call['id']) ?>">Edit Call</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success" role="alert"><?= escape($success) ?></div>
        <?php endif; ?>
        <?php if (isset($errors['form'])): ?>
            <div class="alert alert-danger" role="alert"><?= escape($errors['form']) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6"><div class="small text-muted">Customer</div><div><?= escape($call['customer']) ?></div></div>
                    <div class="col-md-6"><div class="small text-muted">Location</div><div><?= escape($call['location']) ?></div></div>
                    <div class="col-md-4"><div class="small text-muted">Received</div><div><?= escape(format_datetime($call['received_date'])) ?></div></div>
                    <div class="col-md-4"><div class="small text-muted">Status</div><div><span class="badge <?= status_badge_class((string)$call['status']) ?>"><?= escape($call['status']) ?></span></div></div>
                    <div class="col-md-4"><div class="small text-muted">Assigned Technician</div><div><?= escape((string)($call['assigned_tech_name'] ?? 'Unassigned')) ?></div></div>
                    <div class="col-md-4"><div class="small text-muted">Customer Contact</div><div><?= escape($call['contact'] ?: '—') ?></div></div>
                    <div class="col-md-4"><div class="small text-muted">Phone</div><div><?= escape($call['phone'] ?: '—') ?></div></div>
                    <div class="col-md-4"><div class="small text-muted">Email</div><div><?= escape($call['email'] ?: '—') ?></div></div>
                    <div class="col-12"><div class="small text-muted">Customer PO Number</div><div><?= escape($call['po_number'] ?: '—') ?></div></div>
                    <div class="col-12"><div class="small text-muted mb-1">Reported Issue</div><div class="text-break"><?= nl2br(escape($call['reported_issue'])) ?></div></div>
                    <div class="col-12"><div class="small text-muted mb-1">Internal Notes</div><div class="text-break"><?= $call['internal_notes'] !== '' ? nl2br(escape($call['internal_notes'])) : '—' ?></div></div>
                </div>
            </div>
        </div>

        <?php if ($canSelfAssign): ?>
            <form method="post" class="card border-success shadow-sm mb-4">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>This open job is unassigned and available to claim.</div>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="claim_job">
                    <input type="hidden" name="expected_updated_at" value="<?= escape((string)($call['updated_at'] ?? $call['created_at'] ?? '')) ?>">
                    <button type="submit" class="btn btn-success">Claim This Job</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($canAddTechnicianNote): ?>
            <form method="post" class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <label class="form-label" for="technician_note">Add Technician Note</label>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_note">
                    <input type="hidden" name="expected_updated_at" value="<?= escape((string)($call['updated_at'] ?? $call['created_at'] ?? '')) ?>">
                    <textarea class="form-control<?= isset($errors['note']) ? ' is-invalid' : '' ?>" id="technician_note" name="technician_note" rows="3"></textarea>
                    <?php if (isset($errors['note'])): ?><div class="invalid-feedback"><?= escape($errors['note']) ?></div><?php endif; ?>
                    <div class="mt-3"><button type="submit" class="btn btn-primary">Add Note</button></div>
                </div>
            </form>
        <?php endif; ?>

        <?php if (!empty($relatedCalls)): ?>
            <div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white"><h2 class="h6 mb-0">Prior Calls For This Location</h2></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Job</th><th>Received</th><th>Status</th><th>Technician</th><th>Issue</th></tr></thead><tbody><?php foreach ($relatedCalls as $relatedCall): ?><tr><td><a href="<?= url('public/view_call.php?id=' . (int)$relatedCall['id']) ?>"><?= escape((string)$relatedCall['job_number']) ?></a></td><td><?= escape(format_datetime((string)$relatedCall['received_date'])) ?></td><td><?= escape((string)$relatedCall['status']) ?></td><td><?= escape((string)($relatedCall['assigned_tech_name'] ?? 'Unassigned')) ?></td><td class="text-break"><?= escape(mb_strimwidth((string)($relatedCall['reported_issue'] ?? ''), 0, 80, '...')) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
        <?php endif; ?>

        <?php if (!empty($history)): ?>
            <div class="card border-0 shadow-sm"><div class="card-header bg-white"><h2 class="h6 mb-0">Change History</h2></div><div class="card-body"><ul class="list-unstyled mb-0"><?php foreach ($history as $entry): ?><li class="border-bottom py-2"><div class="small text-muted"><?= escape(format_datetime($entry['created_at'])) ?> · <?= escape(trim((string)($entry['changed_by_name'] ?? '')) ?: 'System') ?></div><div><strong><?= escape($entry['field_name']) ?></strong></div><?php if (!empty($entry['note'])): ?><div class="small text-muted"><?= escape($entry['note']) ?></div><?php endif; ?><?php if ($entry['old_value'] !== null || $entry['new_value'] !== null): ?><div class="small">From: <?= escape($entry['old_value'] ?? '—') ?> → <?= escape($entry['new_value'] ?? '—') ?></div><?php endif; ?></li><?php endforeach; ?></ul></div></div>
        <?php endif; ?>
    </div>
</div>