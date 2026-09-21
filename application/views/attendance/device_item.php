<?php
if (empty($data)) {
    echo '<tr><td colspan="7" class="text-center text-muted">Tidak ada data perangkat</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;
        $status = $value['status'] ?? 'pending';
        $is_active = !empty($value['active_device_id']) && (int) $value['active_device_id'] === (int) $value['id'];

        if ($status === 'approved') {
            $status_badge = '<span class="badge" style="background-color:#f6ffed; color:#52c41a; border:1px solid #b7eb8f;">Approved</span>';
        } elseif ($status === 'rejected') {
            $status_badge = '<span class="badge" style="background-color:#fff2f0; color:#ff4d4f; border:1px solid #ffccc7;">Rejected</span>';
        } else {
            $status_badge = '<span class="badge" style="background-color:#fffbe6; color:#faad14; border:1px solid #ffe58f;">Pending</span>';
        }

        if ($is_active) {
            $status_badge .= ' <span class="badge" style="background-color:#e6f7ff; color:#1890ff; border:1px solid #91d5ff;">Browser Aktif</span>';
        }

        $created_at = !empty($value['created_at']) ? date('d M Y H:i', strtotime($value['created_at'])) : '-';
        $last_used_at = !empty($value['last_used_at']) ? date('d M Y H:i', strtotime($value['last_used_at'])) : '-';
        $approved_at = !empty($value['approved_at']) ? date('d M Y H:i', strtotime($value['approved_at'])) : '-';
        $approved_by = !empty($value['approved_by_name']) ? htmlspecialchars($value['approved_by_name']) : '-';
        $token = htmlspecialchars($value['device_token'] ?? '-');
        $user_agent = htmlspecialchars($value['user_agent'] ?? '-');
?>
        <tr>
            <td><?= $num ?></td>
            <td>
                <strong><?= htmlspecialchars($value['full_name'] ?? 'Tidak diketahui') ?></strong>
                <div class="text-muted" style="font-size:12px;">Didaftarkan: <?= $created_at ?></div>
            </td>
            <td>
                <div class="device-token" title="<?= $token ?>"><?= $token ?></div>
                <div class="user-agent text-muted" title="<?= $user_agent ?>" style="font-size:12px;"><?= $user_agent ?></div>
            </td>
            <td><?= $status_badge ?></td>
            <td><?= $last_used_at ?></td>
            <td>
                <div><?= $approved_at ?></div>
                <div class="text-muted" style="font-size:12px;"><?= $approved_by ?></div>
            </td>
            <td>
                <div class="d-flex gap-1">
                    <?php if ($status !== 'approved' || !$is_active): ?>
                        <button type="button" class="btn btn-success btn-sm" title="Approve browser" onclick="setDeviceStatus(<?= (int) $value['id'] ?>, 'approve_device')">
                            <i class="bi bi-check2"></i>
                        </button>
                    <?php endif; ?>
                    <?php if ($status !== 'rejected'): ?>
                        <button type="button" class="btn btn-outline-danger btn-sm" title="Tolak browser" onclick="setDeviceStatus(<?= (int) $value['id'] ?>, 'reject_device')">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-danger btn-sm" title="Hapus perangkat" onclick="hapusDevice(<?= (int) $value['id'] ?>, '<?= htmlspecialchars(addslashes($value['full_name'] ?? '-')) ?>')">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </td>
        </tr>
<?php
    }
}
?>
