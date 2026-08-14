<?php
$customerNames = $recordSuggestions['customer_names'] ?? [];
$locationNames = $recordSuggestions['location_names'] ?? [];
$customerProfiles = $recordSuggestions['customer_profiles'] ?? [];
$locationProfiles = $recordSuggestions['location_profiles'] ?? [];
$customerLocations = $recordSuggestions['customer_locations'] ?? [];
$locationProfilesByCustomer = $recordSuggestions['location_profiles_by_customer'] ?? [];

$selectedCustomerKey = mb_strtolower(trim((string)($values['customer'] ?? '')));
$initialLocationNames = $selectedCustomerKey !== ''
    ? ($customerLocations[$selectedCustomerKey] ?? [])
    : [];
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3">Edit Service Call</h1>
                <p class="text-muted mb-0">Job #<?= escape($call['job_number']) ?> | Created <?= escape(date('Y-m-d H:i', strtotime($call['created_at']))) ?></p>
                <p class="text-muted mb-0">Last Modified <?= escape(date('Y-m-d H:i', strtotime($lastModifiedAt))) ?> by <?= escape((string)$lastModifiedBy) ?></p>
            </div>
            <a class="btn btn-secondary" href="<?= $backUrl ?>">Back</a>
        </div>
        <?php if (!empty($errors['claim_job'])): ?>
            <div class="alert alert-danger"><?= escape($errors['claim_job']) ?></div>
        <?php elseif ($isTechnician && $canSelfAssign): ?>
            <div class="alert alert-info">This open job is still unassigned. You can claim it for yourself below.</div>
        <?php elseif ($isTechnician && $canManage): ?>
            <div class="alert alert-info">You can update the status and add a technician note for this assigned job.</div>
        <?php elseif ($isTechnician): ?>
            <div class="alert alert-warning">This job is not assigned to you, so you can only view it.</div>
        <?php endif; ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="expected_updated_at" value="<?= escape((string)($values['expected_updated_at'] ?? '')) ?>">
            <?php if (isset($errors['form'])): ?>
                <div class="alert alert-danger" role="alert"><?= escape($errors['form']) ?></div>
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="customer">Customer Name</label>
                    <input id="customer" name="customer" class="form-control" type="text" value="<?= escape($values['customer']) ?>" required autofocus maxlength="255" list="customer-options" <?= $isTechnician && !$canEditDetails ? 'disabled' : '' ?>>
                    <?php if (isset($errors['customer'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['customer']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="location">Location</label>
                    <input id="location" name="location" class="form-control" type="text" value="<?= escape($values['location']) ?>" required maxlength="255" list="location-options" <?= $isTechnician && !$canEditDetails ? 'disabled' : '' ?>>
                    <?php if (isset($errors['location'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['location']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="received_date">Date / Time Received</label>
                    <input id="received_date" name="received_date" class="form-control" type="datetime-local" value="<?= escape($values['received_date']) ?>" required <?= $isTechnician && !$canEditDetails ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="assigned_tech">Assigned Technician</label>
                    <select id="assigned_tech" name="assigned_tech" class="form-select" <?= $isTechnician && !$canEditDetails ? 'disabled' : '' ?>>
                        <option value="">Unassigned</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?= escape($tech['id']) ?>" <?= $tech['id'] == $values['assigned_tech'] ? 'selected' : '' ?>><?= escape($tech['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select" <?= $isTechnician && !$canEditDetails ? 'disabled' : '' ?>>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= escape($status) ?>" <?= $status === $values['status'] ? 'selected' : '' ?>><?= escape($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="contact">Customer Contact</label>
                    <input id="contact" name="contact" class="form-control" type="text" value="<?= escape($values['contact']) ?>" maxlength="150" <?= $isTechnician && !$canEditDetails ? 'disabled' : '' ?>>
                    <?php if (isset($errors['contact'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['contact']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input id="phone" name="phone" class="form-control" type="text" value="<?= escape($values['phone']) ?>" maxlength="100" <?= $isTechnician && !$canEditDetails ? 'disabled' : '' ?>>
                    <?php if (isset($errors['phone'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['phone']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" name="email" class="form-control" type="email" value="<?= escape($values['email']) ?>" maxlength="255" <?= $isTechnician && !$canEditDetails ? 'disabled' : '' ?>>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="po_number">Customer PO Number</label>
                    <input id="po_number" name="po_number" class="form-control" type="text" value="<?= escape($values['po_number']) ?>" maxlength="100" <?= $isTechnician && !$canEditDetails ? 'disabled' : '' ?>>
                    <?php if (isset($errors['po_number'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['po_number']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="reported_issue">Reported Issue</label>
                    <textarea id="reported_issue" name="reported_issue" class="form-control" rows="5" required <?= $isTechnician && !$canEditDetails ? 'disabled' : '' ?>><?= escape($values['reported_issue']) ?></textarea>
                    <?php if (isset($errors['reported_issue'])): ?>
                        <div class="invalid-feedback d-block"><?= escape($errors['reported_issue']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="technician_note">Technician Note</label>
                    <textarea id="technician_note" name="technician_note" class="form-control" rows="3" <?= $isTechnician && !$canEditDetails ? 'disabled' : '' ?>><?= escape($values['technician_note']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="internal_notes">Internal Notes</label>
                    <textarea id="internal_notes" name="internal_notes" class="form-control" rows="3" <?= $isTechnician && !$canEditDetails ? 'disabled' : '' ?>><?= escape($values['internal_notes']) ?></textarea>
                </div>
                <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <?php if ($canDelete): ?>
                            <button
                                type="submit"
                                name="action"
                                value="delete_call"
                                class="btn btn-danger"
                                onclick="return confirm('Permanently delete this service call? This cannot be undone.');"
                            >
                                Delete Call
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="ms-auto">
                        <?php if ($isTechnician && $canSelfAssign): ?>
                            <button type="submit" name="claim_job" value="1" class="btn btn-success">Claim This Job</button>
                        <?php endif; ?>
                        <button type="submit" name="action" value="save_call" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </div>
        </form>

        <datalist id="customer-options">
            <?php foreach ($customerNames as $customerName): ?>
                <option value="<?= escape((string)$customerName) ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <datalist id="location-options">
            <?php foreach ($initialLocationNames as $locationName): ?>
                <option value="<?= escape((string)$locationName) ?>"></option>
            <?php endforeach; ?>
        </datalist>

        <?php if (!empty($relatedCalls)): ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h6 mb-0">Prior Calls For This Location</h2>
                        <div class="small text-muted">Recent jobs that share the same location.</div>
                    </div>
                    <span class="badge text-bg-light"><?= count($relatedCalls) ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Job</th>
                                    <th scope="col">Match</th>
                                    <th scope="col">Received</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Technician</th>
                                    <th scope="col">Issue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relatedCalls as $relatedCall): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('public/edit_call.php?id=' . (int)$relatedCall['id']) ?>">
                                                <?= escape((string)$relatedCall['job_number']) ?>
                                            </a>
                                        </td>
                                        <td><span class="badge text-bg-secondary"><?= escape((string)($relatedCall['match_label'] ?? 'Related')) ?></span></td>
                                        <td><?= escape(date('Y-m-d H:i', strtotime((string)$relatedCall['received_date']))) ?></td>
                                        <td><?= escape((string)$relatedCall['status']) ?></td>
                                        <td><?= escape((string)($relatedCall['assigned_tech_name'] ?? 'Unassigned')) ?></td>
                                        <td class="text-break"><?= escape(mb_strimwidth((string)($relatedCall['reported_issue'] ?? ''), 0, 80, '...')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

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
<script>
(function () {
    const customerInput = document.getElementById('customer');
    const locationInput = document.getElementById('location');
    const contactInput = document.getElementById('contact');
    const phoneInput = document.getElementById('phone');
    const emailInput = document.getElementById('email');
    const locationOptions = document.getElementById('location-options');

    if (!customerInput || !locationInput || !contactInput || !phoneInput || !emailInput || !locationOptions) {
        return;
    }

    if (customerInput.disabled || locationInput.disabled) {
        return;
    }

    const customerProfiles = <?= json_encode($customerProfiles, JSON_UNESCAPED_SLASHES) ?>;
    const locationProfiles = <?= json_encode($locationProfiles, JSON_UNESCAPED_SLASHES) ?>;
    const customerLocations = <?= json_encode($customerLocations, JSON_UNESCAPED_SLASHES) ?>;
    const locationProfilesByCustomer = <?= json_encode($locationProfilesByCustomer, JSON_UNESCAPED_SLASHES) ?>;

    function keyFor(value) {
        return String(value || '').trim().toLowerCase();
    }

    function fillIfEmpty(input, value) {
        if (!input || input.value.trim() !== '' || !value) {
            return;
        }
        input.value = value;
    }

    function renderLocationOptions(options) {
        locationOptions.innerHTML = '';
        (options || []).forEach((name) => {
            const option = document.createElement('option');
            option.value = name;
            locationOptions.appendChild(option);
        });
    }

    function syncLocationOptionsForCustomer() {
        const customerKey = keyFor(customerInput.value);
        const options = customerKey !== '' ? (customerLocations[customerKey] || []) : [];
        renderLocationOptions(options);
    }

    function applyCustomerProfile() {
        syncLocationOptionsForCustomer();
        const customerKey = keyFor(customerInput.value);
        const customerLocationOptions = customerKey !== '' ? (customerLocations[customerKey] || []) : [];

        const profile = customerProfiles[customerKey] || null;
        if (!profile) {
            return;
        }

        fillIfEmpty(contactInput, profile.contact || '');
        fillIfEmpty(phoneInput, profile.phone || '');
        fillIfEmpty(emailInput, profile.email || '');
        if (customerLocationOptions.length === 1) {
            fillIfEmpty(locationInput, customerLocationOptions[0] || '');
        }
    }

    function applyLocationProfile() {
        const customerKey = keyFor(customerInput.value);
        const locationKey = keyFor(locationInput.value);
        const profileByCustomer = locationProfilesByCustomer[customerKey] || null;
        const profile = (profileByCustomer && profileByCustomer[locationKey])
            ? profileByCustomer[locationKey]
            : (locationProfiles[locationKey] || null);
        if (!profile) {
            return;
        }

        fillIfEmpty(customerInput, profile.customer || '');
        fillIfEmpty(contactInput, profile.contact || '');
        fillIfEmpty(phoneInput, profile.phone || '');
        fillIfEmpty(emailInput, profile.email || '');
    }

    customerInput.addEventListener('input', syncLocationOptionsForCustomer);
    customerInput.addEventListener('change', applyCustomerProfile);
    customerInput.addEventListener('blur', applyCustomerProfile);
    locationInput.addEventListener('change', applyLocationProfile);
    locationInput.addEventListener('blur', applyLocationProfile);

    syncLocationOptionsForCustomer();
})();
</script>
