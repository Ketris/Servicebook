<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3">Edit Service Call</h1>
                <p class="text-muted mb-0">Job #<?= escape($call['job_number']) ?> | Created <?= escape(date('Y-m-d H:i', strtotime($call['created_at']))) ?></p>
            </div>
            <a class="btn btn-secondary" href="<?= url('public/index.php') ?>">Back</a>
        </div>
        <?php if ($isTechnician && $canManage): ?>
            <div class="alert alert-info">You can update the status and add a technician note for this assigned job.</div>
        <?php elseif ($isTechnician): ?>
            <div class="alert alert-warning">This job is not assigned to you, so you can only view it.</div>
        <?php endif; ?>
        <form method="post" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="customer">Customer Name</label>
                    <input id="customer" name="customer" class="form-control" type="text" value="<?= escape($values['customer']) ?>" required autofocus <?= $isTechnician && $canManage ? 'disabled' : '' ?>>
                    <?php if (isset($errors['customer'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['customer']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="location">Location</label>
                    <input id="location" name="location" class="form-control" type="text" value="<?= escape($values['location']) ?>" required <?= $isTechnician && $canManage ? 'disabled' : '' ?>>
                    <?php if (isset($errors['location'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['location']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="received_date">Date / Time Received</label>
                    <input id="received_date" name="received_date" class="form-control" type="datetime-local" value="<?= escape($values['received_date']) ?>" required <?= $isTechnician && $canManage ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="assigned_tech">Assigned Technician</label>
                    <select id="assigned_tech" name="assigned_tech" class="form-select" <?= $isTechnician && $canManage ? 'disabled' : '' ?>>
                        <option value="">Unassigned</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?= escape($tech['id']) ?>" <?= $tech['id'] == $values['assigned_tech'] ? 'selected' : '' ?>><?= escape($tech['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select" <?= $isTechnician && !$canManage ? 'disabled' : '' ?>>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= escape($status) ?>" <?= $status === $values['status'] ? 'selected' : '' ?>><?= escape($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="priority">Priority</label>
                    <select id="priority" name="priority" class="form-select" <?= $isTechnician && $canManage ? 'disabled' : '' ?>>
                        <?php foreach ($priorities as $priority): ?>
                            <option value="<?= escape($priority) ?>" <?= $priority === $values['priority'] ? 'selected' : '' ?>><?= escape($priority) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="contact">Customer Contact</label>
                    <input id="contact" name="contact" class="form-control" type="text" value="<?= escape($values['contact']) ?>" <?= $isTechnician && $canManage ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input id="phone" name="phone" class="form-control" type="text" value="<?= escape($values['phone']) ?>" <?= $isTechnician && $canManage ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" name="email" class="form-control" type="email" value="<?= escape($values['email']) ?>" <?= $isTechnician && $canManage ? 'disabled' : '' ?>>
                </div>
                <div class="col-12">
                    <label class="form-label" for="po_number">Customer PO Number</label>
                    <input id="po_number" name="po_number" class="form-control" type="text" value="<?= escape($values['po_number']) ?>" <?= $isTechnician && $canManage ? 'disabled' : '' ?>>
                </div>
                <div class="col-12">
                    <label class="form-label" for="reported_issue">Reported Issue</label>
                    <textarea id="reported_issue" name="reported_issue" class="form-control" rows="5" required <?= $isTechnician && $canManage ? 'disabled' : '' ?>><?= escape($values['reported_issue']) ?></textarea>
                    <?php if (isset($errors['reported_issue'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['reported_issue']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="technician_note">Technician Note</label>
                    <textarea id="technician_note" name="technician_note" class="form-control" rows="3" <?= $isTechnician && !$canManage ? 'disabled' : '' ?>><?= escape($values['technician_note']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="internal_notes">Internal Notes</label>
                    <textarea id="internal_notes" name="internal_notes" class="form-control" rows="3" <?= $isTechnician && $canManage ? 'disabled' : '' ?>><?= escape($values['internal_notes']) ?></textarea>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>

        <?php if (!empty($history)): ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Change History</h2>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($history as $entry): ?>
                            <li class="border-bottom py-2">
                                <div class="small text-muted">
                                    <?= escape(date('Y-m-d H:i', strtotime($entry['created_at']))) ?> · <?= escape($entry['changed_by_name'] ?? 'System') ?>
                                </div>
                                <div><strong><?= escape($entry['field_name']) ?></strong></div>
                                <?php if (!empty($entry['note'])): ?>
                                    <div class="small text-muted"><?= escape($entry['note']) ?></div>
                                <?php endif; ?>
                                <?php if ($entry['old_value'] !== null || $entry['new_value'] !== null): ?>
                                    <div class="small">From: <?= escape($entry['old_value'] ?? '—') ?> → <?= escape($entry['new_value'] ?? '—') ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
