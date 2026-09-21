<?php
if (empty($data)) {
    echo '<tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;
        
        // Status badge color  
        $status_color = '';
        switch(strtolower($value['status'])) {
            case 'contract':
                $status_color = 'bg-success';
                break;
            case 'internship':
                $status_color = 'bg-primary';
                break;
            default:
                $status_color = 'bg-secondary';
        }
        
        // Progress badge color
        $progress_color = '';
        switch(strtolower($value['progress'])) {
            case 'done':
                $progress_color = 'bg-success';
                break;
            case 'run':
                $progress_color = 'bg-primary';
                break;
            case 'pending':
                $progress_color = 'bg-warning';
                break;
            default:
                $progress_color = 'bg-secondary';
        }
        
        // Approval CEO badge color
        $approval_color = '';
        switch(strtolower($value['approval_ceo'])) {
            case 'done':
                $approval_color = 'bg-success';
                break;
            case 'hold':
                $approval_color = 'bg-warning';
                break;
            default:
                $approval_color = 'bg-secondary';
        }
        
        // Format dates
        $tanggal_request = $value['tanggal_request'] ? date('d M Y', strtotime($value['tanggal_request'])) : '-';
?>
        <tr>
            <td><?= $num ?></td>
            <td><?= $value['requestor'] ?: '-' ?></td>
            <td><?= $tanggal_request ?></td>
            <td><?= $value['posisi'] ?: '-' ?></td>
            <td><?= $value['level'] ?: '-' ?></td>
            <td>
                <span class="badge <?= $status_color ?>"><?= strtoupper($value['status']) ?></span>
            </td>
            <td class="editable-manpower" data-field="progress" data-id="<?= $value['id'] ?>">
                <div class="display-mode">
                    <span class="badge <?= $progress_color ?>"><?= strtoupper($value['progress']) ?></span>
                </div>
                <div class="edit-mode d-none">
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm" style="height: calc(1.5em + .5rem + 2px);">
                            <option value="RUN" <?= $value['progress'] == 'RUN' ? 'selected' : '' ?>>RUN</option>
                            <option value="PENDING" <?= $value['progress'] == 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                            <option value="DONE" <?= $value['progress'] == 'DONE' ? 'selected' : '' ?>>DONE</option>
                        </select>
                        <button class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center btn-cancel-manpower" style="height: calc(1.5em + .5rem + 2px);" title="Cancel">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </td>
            <td class="editable-manpower" data-field="approval_ceo" data-id="<?= $value['id'] ?>">
                <div class="display-mode">
                    <span class="badge <?= $approval_color ?>"><?= ucfirst($value['approval_ceo']) ?></span>
                </div>
                <div class="edit-mode d-none">
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm" style="height: calc(1.5em + .5rem + 2px);">
                            <option value="Done" <?= $value['approval_ceo'] == 'Done' ? 'selected' : '' ?>>Done</option>
                            <option value="Hold" <?= $value['approval_ceo'] == 'Hold' ? 'selected' : '' ?>>Hold</option>
                        </select>
                        <button class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center btn-cancel-manpower" style="height: calc(1.5em + .5rem + 2px);" title="Cancel">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </td>
            <td class="text-end">
                <a href="<?= base_url() ?>/manpower_planning/detail?id=<?= $value['id'] ?>"
                    class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Detail">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="<?= base_url() ?>/manpower_planning/edit_page?id=<?= $value['id'] ?>"
                    class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <a href="#!" onclick="removeManpower('<?= $value['id'] ?>')"
                    style="color: #ff4d4f; font-size: 14px;" title="Hapus">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>
<?php
    }
}
?>
