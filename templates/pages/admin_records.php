<?php
/** @var string $search */
/** @var array<int, array<string, mixed>> $customers */
/** @var array<int, array<string, mixed>> $locations */
/** @var string $success */
/** @var string $error */
?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Record Management</h1>
        <p class="text-muted mb-0">Edit customer and location defaults, and merge duplicates to keep data clean.</p>
    </div>
    <form method="get" class="d-flex gap-2">
        <input type="search" class="form-control" name="search" placeholder="Search records" value="<?= escape($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <a class="btn btn-outline-secondary" href="<?= url('admin/records.php') ?>">Reset</a>
    </form>
</div>

<?php if ($success !== ''): ?>
    <div class="alert alert-success" role="alert"><?= escape($success) ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="alert alert-danger" role="alert"><?= escape($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-xxl-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0">Merge Customer Duplicates</h2>
            </div>
            <div class="card-body">
                <form method="post" class="row g-2 align-items-end" onsubmit="return confirm('Merge the selected customer records? Existing calls tied to the source will use the target name.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="merge_customer">
                    <div class="col-md-5">
                        <label class="form-label">Source</label>
                        <select class="form-select" name="source_customer_id" required>
                            <option value="">Select source</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= escape((string)$customer['id']) ?>"><?= escape($customer['customer_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Target</label>
                        <select class="form-select" name="target_customer_id" required>
                            <option value="">Select target</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= escape((string)$customer['id']) ?>"><?= escape($customer['customer_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-outline-danger">Merge</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">Customer Records</h2>
                <span class="badge text-bg-light border"><?= count($customers) ?> shown</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No customers found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td colspan="4">
                                            <form method="post" class="row g-2 align-items-end">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="update_customer">
                                                <input type="hidden" name="customer_id" value="<?= escape((string)$customer['id']) ?>">
                                                <div class="col-md-4">
                                                    <label class="form-label small">Name</label>
                                                    <input class="form-control form-control-sm" name="customer_name" maxlength="255" value="<?= escape($customer['customer_name']) ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small">Contact</label>
                                                    <input class="form-control form-control-sm" name="default_contact" maxlength="150" value="<?= escape((string)($customer['default_contact'] ?? '')) ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Phone</label>
                                                    <input class="form-control form-control-sm" name="default_phone" maxlength="100" value="<?= escape((string)($customer['default_phone'] ?? '')) ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Email</label>
                                                    <input class="form-control form-control-sm" name="default_email" maxlength="255" value="<?= escape((string)($customer['default_email'] ?? '')) ?>">
                                                </div>
                                                <div class="col-md-1 d-grid">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                                </div>
                                                <div class="col-12">
                                                    <div class="small text-muted">Linked locations: <?= escape((string)($customer['location_count'] ?? 0)) ?></div>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xxl-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0">Merge Location Duplicates</h2>
            </div>
            <div class="card-body">
                <form method="post" class="row g-2 align-items-end" onsubmit="return confirm('Merge the selected location records? Existing calls tied to the source will use the target name.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="merge_location">
                    <div class="col-md-5">
                        <label class="form-label">Source</label>
                        <select class="form-select" name="source_location_id" required>
                            <option value="">Select source</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= escape((string)$location['id']) ?>"><?= escape($location['location_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Target</label>
                        <select class="form-select" name="target_location_id" required>
                            <option value="">Select target</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= escape((string)$location['id']) ?>"><?= escape($location['location_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-outline-danger">Merge</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">Location Records</h2>
                <span class="badge text-bg-light border"><?= count($locations) ?> shown</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <tbody>
                            <?php if (empty($locations)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No locations found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($locations as $location): ?>
                                    <tr>
                                        <td colspan="4">
                                            <form method="post" class="row g-2 align-items-end">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="update_location">
                                                <input type="hidden" name="location_id" value="<?= escape((string)$location['id']) ?>">
                                                <div class="col-md-3">
                                                    <label class="form-label small">Name</label>
                                                    <input class="form-control form-control-sm" name="location_name" maxlength="255" value="<?= escape($location['location_name']) ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small">Customer Link</label>
                                                    <select class="form-select form-select-sm" name="customer_record_id">
                                                        <option value="">No linked customer</option>
                                                        <?php foreach ($customers as $customer): ?>
                                                            <option value="<?= escape((string)$customer['id']) ?>" <?= (int)$customer['id'] === (int)($location['customer_record_id'] ?? 0) ? 'selected' : '' ?>><?= escape($customer['customer_name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Contact</label>
                                                    <input class="form-control form-control-sm" name="default_contact" maxlength="150" value="<?= escape((string)($location['default_contact'] ?? '')) ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Phone</label>
                                                    <input class="form-control form-control-sm" name="default_phone" maxlength="100" value="<?= escape((string)($location['default_phone'] ?? '')) ?>">
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label small">Email</label>
                                                    <input class="form-control form-control-sm" name="default_email" maxlength="255" value="<?= escape((string)($location['default_email'] ?? '')) ?>">
                                                </div>
                                                <div class="col-md-1 d-grid">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
