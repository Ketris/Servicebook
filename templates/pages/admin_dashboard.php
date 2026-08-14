<?php
/** @var array<string, int> $stats */
/** @var array<int, array<string, mixed>> $recentActivity */
?>
<h1 class="h3 mb-4">Administration Dashboard</h1>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Open Calls</div>
                <div class="display-6 mb-0"><?= escape((string)($stats['open_calls'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Unassigned Open</div>
                <div class="display-6 mb-0"><?= escape((string)($stats['unassigned_open_calls'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Closed Today</div>
                <div class="display-6 mb-0"><?= escape((string)($stats['completed_today'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Closed This Week</div>
                <div class="display-6 mb-0"><?= escape((string)($stats['completed_this_week'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0">Quick Actions</h2>
            </div>
            <div class="card-body d-grid gap-2">
                <a class="btn btn-outline-primary" href="<?= url('admin/users.php') ?>">Manage Users</a>
                <a class="btn btn-outline-primary" href="<?= url('admin/technicians.php') ?>">Manage Technicians</a>
                <a class="btn btn-outline-primary" href="<?= url('admin/records.php') ?>">Reusable Records</a>
                <a class="btn btn-outline-primary" href="<?= url('admin/activity.php') ?>">View Activity Log</a>
                <a class="btn btn-outline-primary" href="<?= url('admin/settings.php') ?>">System Settings</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">Recent Activity</h2>
                <a href="<?= url('admin/activity.php') ?>" class="small text-decoration-none">View all</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentActivity)): ?>
                    <p class="text-muted mb-0">No activity recorded yet.</p>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($recentActivity as $entry): ?>
                            <?php $actorName = trim((string)($entry['changed_by_name'] ?? '')); ?>
                            <li class="border-bottom py-2">
                                <div class="small text-muted"><?= escape(date('Y-m-d H:i', strtotime($entry['created_at']))) ?> · <?= escape($actorName !== '' ? $actorName : 'System') ?></div>
                                <div>
                                    <?= escape($entry['job_number'] ?? 'Unknown Job') ?> - <?= escape($entry['field_name']) ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
