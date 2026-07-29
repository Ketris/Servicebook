<?php
/** @var array<int, array<string, mixed>> $activity */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Activity Log</h1>
        <p class="text-muted mb-0">Most recent service-call changes and audit events.</p>
    </div>
    <a class="btn btn-secondary" href="<?= url('admin/index.php') ?>">Back to Dashboard</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>When</th>
                    <th>User</th>
                    <th>Job</th>
                    <th>Field</th>
                    <th>Change</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activity)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No activity found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activity as $entry): ?>
                        <tr>
                            <td class="text-nowrap"><?= escape(date('Y-m-d H:i', strtotime($entry['created_at']))) ?></td>
                            <td><?= escape($entry['changed_by_name'] ?? 'System') ?></td>
                            <td>
                                <?php if (!empty($entry['service_call_id'])): ?>
                                    <a href="<?= url('public/edit_call.php?id=' . $entry['service_call_id']) ?>" class="text-decoration-none"><?= escape($entry['job_number'] ?? ('Call #' . $entry['service_call_id'])) ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= escape($entry['field_name'] ?? '') ?></td>
                            <td>
                                <div class="small text-muted">From: <?= escape($entry['old_value'] ?? '-') ?></div>
                                <div class="small">To: <?= escape($entry['new_value'] ?? '-') ?></div>
                            </td>
                            <td class="small"><?= escape($entry['note'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
