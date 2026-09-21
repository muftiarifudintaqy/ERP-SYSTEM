<div class="d-flex justify-content-between align-items-center mb-3">
    <form class="row g-2" method="get" action="<?= base_url() ?>onboarding">
        <input type="hidden" name="tab" value="<?= $transition_type ?>">
        <div class="col-auto">
            <input type="text" class="form-control" name="keyword_<?= $transition_type ?>" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari nama / email">
        </div>
        <div class="col-auto">
            <select class="form-select" name="status_<?= $transition_type ?>">
                <option value="">Semua Status</option>
                <option value="ready" <?= $status === 'ready' ? 'selected' : '' ?>>Ready</option>
                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-search me-1"></i> Cari</button>
        </div>
    </form>

    <?php if ($can_create): ?>
        <button type="button" class="btn btn-primary open-add-employee-modal" data-bs-toggle="modal" data-bs-target="#addEmployeeModal" data-type="<?= $transition_type ?>">
            <i class="bi bi-plus-lg me-1"></i>Karyawan
        </button>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th width="40"></th>
                <th>Employee Name</th>
                <th>Invitation Date</th>
                <th>Status</th>
                <th>Last Updated</th>
                <th width="220">Task Progress</th>
                <th width="170">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Belum ada data <?= htmlspecialchars($transition_type) ?>.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $status_class = 'status-ready';
                    $status_text = 'Ready';
                    if ($row['status_calculated'] === 'in_progress') {
                        $status_class = 'status-in-progress';
                        $status_text = 'In Progress';
                    } elseif ($row['status_calculated'] === 'completed') {
                        $status_class = 'status-completed';
                        $status_text = 'Completed';
                    }
                    ?>
                    <tr>
                        <td>
                            <input type="checkbox" disabled>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($row['full_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($row['position_name']) ?></small>
                        </td>
                        <td><?= !empty($row['invitation_date']) ? date('d M Y', strtotime($row['invitation_date'])) : '-' ?></td>
                        <td>
                            <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                        </td>
                        <td><?= !empty($row['last_updated_at']) ? date('d M Y H:i', strtotime($row['last_updated_at'])) : '-' ?></td>
                        <td>
                            <div class="transition-progress mb-1">
                                <div class="bar" style="width: <?= (int) $row['progress'] ?>%;"></div>
                            </div>
                            <small class="text-muted"><?= (int) $row['progress'] ?>% (<?= (int) $row['checked_tasks'] ?>/<?= (int) $row['total_tasks'] ?>)</small>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= base_url() ?>onboarding/detail?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-primary">Lihat Detail</a>
                                <?php if ($can_delete): ?>
                                    <form method="post" action="<?= base_url() ?>onboarding/remove-employee" class="js-remove-employee-form">
                                        <input type="hidden" name="transition_id" value="<?= (int) $row['id'] ?>">
                                        <input type="hidden" name="transition_type" value="<?= htmlspecialchars($transition_type) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
