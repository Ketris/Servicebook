<?php
/** @var array<int, array<string, mixed>> $technicians */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3">Technician Management</h1>
        <p class="text-muted mb-0">Manage active and inactive technicians for service calls.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('admin/technician_edit.php') ?>">New Technician</a>
</div>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($technicians as $technician): ?>
                <tr>
                    <td><?= escape($technician['name']) ?></td>
                    <td><?= escape($technician['phone'] ?? '') ?></td>
                    <td><?= !empty($technician['active']) ? 'Active' : 'Inactive' ?></td>
                    <td><?= escape(date('Y-m-d', strtotime($technician['created_at']))) ?></td>
                    <td><a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/technician_edit.php?id=' . $technician['id']) ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
