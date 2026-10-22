<?php
/** @var array<int, array<string, mixed>> $customers */
/** @var array<int, array<string, mixed>> $locations */
/** @var string $success */
/** @var string $error */
?>
<div class="mb-4">
    <h1 class="h3 mb-1">Record Management</h1>
    <p class="text-muted mb-0">Edit customer and location defaults, and merge duplicates to keep data clean.</p>
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
            <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2">
                <h2 class="h6 mb-0 flex-shrink-0">Customer Records</h2>
                <div class="position-relative filter-box flex-grow-1">
                    <input type="search" class="form-control form-control-sm" id="customerFilterInput" placeholder="Filter records" autocomplete="off">
                    <button type="button" class="filter-clear-btn" id="customerFilterClear" aria-label="Clear filter">&times;</button>
                </div>
                <span class="badge text-bg-light border flex-shrink-0" id="customerShownBadge" data-total="<?= count($customers) ?>"><?= count($customers) ?> shown</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <tbody id="customerTableBody">
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No customers found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                    <?php
                                        $customerSearch = strtolower(
                                            $customer['customer_name']
                                            . ' ' . (string)($customer['default_contact'] ?? '')
                                            . ' ' . (string)($customer['default_phone'] ?? '')
                                            . ' ' . (string)($customer['default_email'] ?? '')
                                        );
                                    ?>
                                    <tr data-search="<?= escape($customerSearch) ?>">
                                        <td colspan="4">
                                            <form method="post" class="row g-3 align-items-end p-3">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="update_customer">
                                                <input type="hidden" name="customer_id" value="<?= escape((string)$customer['id']) ?>">
                                                <div class="col-md-6">
                                                    <label class="form-label">Customer Name</label>
                                                    <input class="form-control" name="customer_name" maxlength="255" value="<?= escape($customer['customer_name']) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Default Contact</label>
                                                    <input class="form-control" name="default_contact" maxlength="150" value="<?= escape((string)($customer['default_contact'] ?? '')) ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Default Phone</label>
                                                    <input class="form-control" name="default_phone" maxlength="100" value="<?= escape((string)($customer['default_phone'] ?? '')) ?>">
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label">Default Email</label>
                                                    <input class="form-control" name="default_email" maxlength="255" value="<?= escape((string)($customer['default_email'] ?? '')) ?>">
                                                </div>
                                                <div class="col-md-1 d-grid">
                                                    <button type="submit" class="btn btn-outline-primary">Save</button>
                                                </div>
                                                <div class="col-12">
                                                    <div class="small text-muted"><?= escape((string)($customer['location_count'] ?? 0)) ?> linked locations · Contact details are customer-wide defaults.</div>
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
            <div class="card-footer bg-white d-flex justify-content-between align-items-center gap-2">
                <span class="small text-muted" id="customerPaginationInfo" aria-live="polite"></span>
                <div class="btn-group btn-group-sm" role="group" aria-label="Customer record pages">
                    <button type="button" class="btn btn-outline-secondary" id="customerPreviousPage">Previous</button>
                    <button type="button" class="btn btn-outline-secondary" id="customerNextPage">Next</button>
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
            <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2">
                <h2 class="h6 mb-0 flex-shrink-0">Location Records</h2>
                <select class="form-select form-select-sm w-auto" id="locationCustomerFilter" aria-label="Filter locations by customer">
                    <option value="">All customers</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= escape((string)$customer['id']) ?>"><?= escape($customer['customer_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="position-relative filter-box flex-grow-1">
                    <input type="search" class="form-control form-control-sm" id="locationFilterInput" placeholder="Filter records" autocomplete="off">
                    <button type="button" class="filter-clear-btn" id="locationFilterClear" aria-label="Clear filter">&times;</button>
                </div>
                <span class="badge text-bg-light border flex-shrink-0" id="locationShownBadge" data-total="<?= count($locations) ?>"><?= count($locations) ?> shown</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <tbody id="locationTableBody">
                            <?php if (empty($locations)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No locations found.</td>
                                </tr>
                            <?php else: ?>
                                <?php $customerNamesById = array_column($customers, 'customer_name', 'id'); ?>
                                <?php foreach ($locations as $location): ?>
                                    <?php
                                        $linkedCustomerName = (string)($customerNamesById[(int)($location['customer_record_id'] ?? 0)] ?? '');
                                        $locationSearch = strtolower(
                                            $location['location_name']
                                            . ' ' . (string)($location['default_contact'] ?? '')
                                            . ' ' . (string)($location['default_phone'] ?? '')
                                            . ' ' . (string)($location['default_email'] ?? '')
                                            . ' ' . (string)($location['city'] ?? '')
                                            . ' ' . $linkedCustomerName
                                        );
                                    ?>
                                    <tr data-search="<?= escape($locationSearch) ?>" data-customer-id="<?= escape((string)($location['customer_record_id'] ?? '')) ?>">
                                        <td colspan="4">
                                            <form method="post" class="row g-3 align-items-end p-3">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="update_location">
                                                <input type="hidden" name="location_id" value="<?= escape((string)$location['id']) ?>">
                                                <div class="col-md-4">
                                                    <label class="form-label">Location Name</label>
                                                    <input class="form-control" name="location_name" maxlength="255" value="<?= escape($location['location_name']) ?>" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Customer Link</label>
                                                    <select class="form-select" name="customer_record_id">
                                                        <option value="">No linked customer</option>
                                                        <?php foreach ($customers as $customer): ?>
                                                            <option value="<?= escape((string)$customer['id']) ?>" <?= (int)$customer['id'] === (int)($location['customer_record_id'] ?? 0) ? 'selected' : '' ?>><?= escape($customer['customer_name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">City</label>
                                                    <input class="form-control" name="city" maxlength="150" value="<?= escape((string)($location['city'] ?? '')) ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Location Contact</label>
                                                    <input class="form-control" name="default_contact" maxlength="150" value="<?= escape((string)($location['default_contact'] ?? '')) ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Location Phone</label>
                                                    <input class="form-control" name="default_phone" maxlength="100" value="<?= escape((string)($location['default_phone'] ?? '')) ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Location Email</label>
                                                    <input class="form-control" name="default_email" maxlength="255" value="<?= escape((string)($location['default_email'] ?? '')) ?>">
                                                </div>
                                                <div class="col-md-1 d-grid">
                                                    <button type="submit" class="btn btn-outline-primary">Save</button>
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
            <div class="card-footer bg-white d-flex justify-content-between align-items-center gap-2">
                <span class="small text-muted" id="locationPaginationInfo" aria-live="polite"></span>
                <div class="btn-group btn-group-sm" role="group" aria-label="Location record pages">
                    <button type="button" class="btn btn-outline-secondary" id="locationPreviousPage">Previous</button>
                    <button type="button" class="btn btn-outline-secondary" id="locationNextPage">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .filter-box {
        max-width: 260px;
    }

    .filter-box .form-control {
        padding-right: 1.75rem;
    }

    #customerTableBody tr > td,
    #locationTableBody tr > td {
        padding: 0.5rem;
    }

    #customerTableBody tr + tr,
    #locationTableBody tr + tr {
        border-top: 0.5rem solid var(--bs-body-bg);
    }

    #locationTableBody .form-control,
    #locationTableBody .form-select {
        min-width: 9rem;
    }

    .filter-clear-btn {
        display: none;
        position: absolute;
        top: 50%;
        right: 0.5rem;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        line-height: 1;
        color: var(--bs-secondary-color);
        font-size: 1rem;
        padding: 0;
    }

    .filter-clear-btn:hover {
        color: var(--app-text);
    }
</style>
<script>
    (function () {
        function setupRecordFilter(inputId, clearId, bodyId, badgeId, customerFilterId, paginationPrefix) {
            var input = document.getElementById(inputId);
            var clearBtn = document.getElementById(clearId);
            var tbody = document.getElementById(bodyId);
            var badge = document.getElementById(badgeId);
            var customerFilter = customerFilterId ? document.getElementById(customerFilterId) : null;
            var previousButton = document.getElementById(paginationPrefix + 'PreviousPage');
            var nextButton = document.getElementById(paginationPrefix + 'NextPage');
            var paginationInfo = document.getElementById(paginationPrefix + 'PaginationInfo');
            if (!input || !clearBtn || !tbody || !badge || !previousButton || !nextButton || !paginationInfo) {
                return;
            }

            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-search]'));
            var total = rows.length;
            var pageSize = 15;
            var currentPage = 0;

            function apply() {
                var term = input.value.trim().toLowerCase();
                var customerId = customerFilter ? customerFilter.value : '';
                clearBtn.style.display = term ? 'block' : 'none';

                var matchingRows = [];
                rows.forEach(function (row) {
                    var matchesSearch = !term || row.getAttribute('data-search').indexOf(term) !== -1;
                    var matchesCustomer = !customerId || row.getAttribute('data-customer-id') === customerId;
                    if (matchesSearch && matchesCustomer) {
                        matchingRows.push(row);
                    }
                });

                var pageCount = Math.max(1, Math.ceil(matchingRows.length / pageSize));
                currentPage = Math.min(currentPage, pageCount - 1);
                var firstVisible = currentPage * pageSize;
                var lastVisible = Math.min(firstVisible + pageSize, matchingRows.length);

                rows.forEach(function (row) {
                    row.style.display = 'none';
                });
                matchingRows.slice(firstVisible, lastVisible).forEach(function (row) {
                    row.style.display = '';
                });

                badge.textContent = matchingRows.length === total
                    ? (total + ' records')
                    : (matchingRows.length + ' of ' + total + ' records');
                paginationInfo.textContent = matchingRows.length === 0
                    ? 'No matching records'
                    : ('Showing ' + (firstVisible + 1) + '–' + lastVisible + ' of ' + matchingRows.length);
                previousButton.disabled = currentPage === 0;
                nextButton.disabled = currentPage >= pageCount - 1;
            }

            input.addEventListener('input', function () {
                currentPage = 0;
                apply();
            });
            if (customerFilter) {
                customerFilter.addEventListener('change', function () {
                    currentPage = 0;
                    apply();
                });
            }
            clearBtn.addEventListener('click', function () {
                input.value = '';
                currentPage = 0;
                apply();
                input.focus();
            });
            previousButton.addEventListener('click', function () {
                currentPage--;
                apply();
            });
            nextButton.addEventListener('click', function () {
                currentPage++;
                apply();
            });

            apply();
        }

        setupRecordFilter('customerFilterInput', 'customerFilterClear', 'customerTableBody', 'customerShownBadge', null, 'customer');
        setupRecordFilter('locationFilterInput', 'locationFilterClear', 'locationTableBody', 'locationShownBadge', 'locationCustomerFilter', 'location');
    })();
</script>
