<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ServiceCall.php';
require_once __DIR__ . '/../src/Technician.php';

Auth::requireLogin();
$user = Auth::currentUser();
$technicians = Technician::findAllActive();
$statuses = ServiceCall::getStatusOptions();
$priorities = ServiceCall::getPriorityOptions();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$call = ServiceCall::findById($id);
if (!$call) {
        header('Location: ' . url('index.php'));
}

$errors = [];
$values = [
    'received_date' => date('Y-m-d\TH:i', strtotime($call['received_date'])),
    'customer' => $call['customer'],
    'location' => $call['location'],
    'contact' => $call['contact'],
    'phone' => $call['phone'],
    'email' => $call['email'],
    'po_number' => $call['po_number'],
    'reported_issue' => $call['reported_issue'],
    'internal_notes' => $call['internal_notes'],
    'assigned_tech' => $call['assigned_tech'],
    'status' => $call['status'],
    'priority' => $call['priority'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $default) {
        $values[$key] = trim($_POST[$key] ?? $default);
    }

    if ($values['customer'] === '') {
        $errors['customer'] = 'Customer name is required.';
    }
    if ($values['location'] === '') {
        $errors['location'] = 'Location is required.';
    }
    if ($values['reported_issue'] === '') {
        $errors['reported_issue'] = 'Reported issue is required.';
    }
    if (!in_array($values['status'], $statuses, true)) {
        $errors['status'] = 'Invalid status selected.';
    }
    if (!in_array($values['priority'], $priorities, true)) {
        $errors['priority'] = 'Invalid priority selected.';
    }

    if (empty($errors)) {
        $data = $values;
        $data['created_by'] = $call['created_by'];
        $data['assigned_tech'] = $data['assigned_tech'] ?: null;
        ServiceCall::save($data, $id);
        header('Location: ' . url('index.php'));
        exit;
    }
}
?>
<?php include __DIR__ . '/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3">Edit Service Call</h1>
                <p class="text-muted mb-0">Job #<?= escape($call['job_number']) ?> | Created <?= escape(date('Y-m-d H:i', strtotime($call['created_at']))) ?></p>
            </div>
            <a class="btn btn-secondary" href="<?= url('index.php') ?>">Back</a>
        </div>
        <form method="post" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="customer">Customer Name</label>
                    <input id="customer" name="customer" class="form-control" type="text" value="<?= escape($values['customer']) ?>" required autofocus>
                    <?php if (isset($errors['customer'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['customer']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="location">Location</label>
                    <input id="location" name="location" class="form-control" type="text" value="<?= escape($values['location']) ?>" required>
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
                    <input id="contact" name="contact" class="form-control" type="text" value="<?= escape($values['contact']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input id="phone" name="phone" class="form-control" type="text" value="<?= escape($values['phone']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" name="email" class="form-control" type="email" value="<?= escape($values['email']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="po_number">Customer PO Number</label>
                    <input id="po_number" name="po_number" class="form-control" type="text" value="<?= escape($values['po_number']) ?>">
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
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
