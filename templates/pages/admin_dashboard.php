<?php
/** @var int $totalCalls */
?>
<div class="row">
    <div class="col-12 col-lg-8">
        <h1 class="h3 mb-4">Administration Dashboard</h1>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Open Calls</h2>
                        <p class="display-6 mb-0"><?= escape((string)$totalCalls) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Users</h2>
                        <p class="text-muted mb-0">Manage administrators and office staff.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Technicians</h2>
                        <p class="text-muted mb-0">Manage technician availability and contact info.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
