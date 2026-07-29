<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3">New Service Call</h1>
                <p class="text-muted mb-0">Enter a new job using the quick form.</p>
            </div>
            <a class="btn btn-secondary" href="<?= url('public/index.php') ?>">Cancel</a>
        </div>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <?php if (isset($errors['form'])): ?>
                <div class="alert alert-danger" role="alert"><?= escape($errors['form']) ?></div>
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="customer">Customer Name</label>
                    <input id="customer" name="customer" class="form-control" type="text" value="<?= escape($values['customer']) ?>" required autofocus maxlength="255">
                    <?php if (isset($errors['customer'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['customer']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="location">Location</label>
                    <input id="location" name="location" class="form-control" type="text" value="<?= escape($values['location']) ?>" required maxlength="255">
                    <?php if (isset($errors['location'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['location']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="received_date">Date / Time Received</label>
                    <input id="received_date" name="received_date" class="form-control" type="datetime-local" value="<?= escape($values['received_date']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="assigned_tech">Assigned Technician</label>
                    <select id="assigned_tech" name="assigned_tech" class="form-select">
                        <option value="">Unassigned</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?= escape($tech['id']) ?>" <?= $tech['id'] == $values['assigned_tech'] ? 'selected' : '' ?>><?= escape($tech['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= escape($status) ?>" <?= $status === $values['status'] ? 'selected' : '' ?>><?= escape($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="priority">Priority</label>
                    <select id="priority" name="priority" class="form-select">
                        <?php foreach ($priorities as $priority): ?>
                            <option value="<?= escape($priority) ?>" <?= $priority === $values['priority'] ? 'selected' : '' ?>><?= escape($priority) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="contact">Customer Contact</label>
                    <input id="contact" name="contact" class="form-control" type="text" value="<?= escape($values['contact']) ?>" maxlength="150">
                    <?php if (isset($errors['contact'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['contact']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input id="phone" name="phone" class="form-control" type="text" value="<?= escape($values['phone']) ?>" maxlength="100">
                    <?php if (isset($errors['phone'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['phone']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" name="email" class="form-control" type="email" value="<?= escape($values['email']) ?>" maxlength="255">
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="po_number">Customer PO Number</label>
                    <input id="po_number" name="po_number" class="form-control" type="text" value="<?= escape($values['po_number']) ?>" maxlength="100">
                    <?php if (isset($errors['po_number'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['po_number']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="reported_issue">Reported Issue</label>
                    <textarea id="reported_issue" name="reported_issue" class="form-control" rows="5" required><?= escape($values['reported_issue']) ?></textarea>
                    <?php if (isset($errors['reported_issue'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['reported_issue']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="internal_notes">Internal Notes</label>
                    <textarea id="internal_notes" name="internal_notes" class="form-control" rows="3"><?= escape($values['internal_notes']) ?></textarea>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">Save Call</button>
                </div>
            </div>
        </form>
    </div>
</div>
