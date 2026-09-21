<?php
$limit = 10;
$current_page = ($start / $limit) + 1;

function contract_status_badge($status, $end_date)
{
    $map = [
        'active'     => ['#52c41a', '#f6ffed', 'Aktif'],
        'expiring'   => ['#faad14', '#fffbe6', 'Akan Berakhir'],
        'expired'    => ['#ff4d4f', '#fff2f0', 'Berakhir'],
        'renewed'    => ['#1890ff', '#e6f7ff', 'Diperpanjang'],
        'terminated' => ['#8c8c8c', '#fafafa', 'Diberhentikan'],
    ];
    $m = $map[$status] ?? ['#d9d9d9', '#fafafa', ucfirst($status ?: '-')];
    return '<span class="badge" style="color:' . $m[0] . ';background:' . $m[1] . ';border:1px solid ' . $m[0] . ';">' . $m[2] . '</span>';
}

if (empty($data)) {
    echo '<tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data karyawan</td></tr>';
} else {
    foreach ($data as $key => $v) {
        $num = $start + $key + 1;
        $has_contract = !empty($v['contract_id']);

        // hitung sisa hari
        $days_label = '';
        if ($has_contract && !empty($v['end_date'])) {
            $days = (int) ((strtotime($v['end_date']) - strtotime(date('Y-m-d'))) / 86400);
            if ($days < 0) {
                $days_label = '<small class="text-danger d-block">Lewat ' . abs($days) . ' hari</small>';
            } elseif ($days <= 30) {
                $days_label = '<small class="text-warning d-block">' . $days . ' hari lagi</small>';
            }
        }
?>
    <tr>
        <td class="text-center">
            <?php if ($has_contract): ?>
                <input type="checkbox" class="form-check-input row-check" value="<?= $v['contract_id'] ?>">
            <?php endif; ?>
        </td>
        <td><?= $num ?></td>
        <td>
            <strong><?= htmlspecialchars($v['full_name']) ?></strong>
            <small class="text-muted d-block"><?= htmlspecialchars($v['email']) ?></small>
        </td>
        <td>
            <?php if ($has_contract): ?>
                <span class="badge" style="color:#1890ff;background:#e6f7ff;border:1px solid #91d5ff;"><?= htmlspecialchars($v['contract_type']) ?></span>
                <?php if (!empty($v['position_name'])): ?><small class="text-muted d-block"><?= htmlspecialchars($v['position_name']) ?></small><?php endif; ?>
            <?php else: ?>
                <span class="text-muted">Belum ada kontrak</span>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($has_contract): ?>
                <?= date('d M Y', strtotime($v['start_date'])) ?>
                <?= !empty($v['end_date']) ? ' &ndash; ' . date('d M Y', strtotime($v['end_date'])) : ' <span class="text-muted">(permanen)</span>' ?>
                <?= $days_label ?>
            <?php else: ?>
                <span class="text-muted">-</span>
            <?php endif; ?>
        </td>
        <td><?= $has_contract ? contract_status_badge($v['status'], $v['end_date']) : '<span class="text-muted">-</span>' ?></td>
        <td class="text-center">
            <a href="<?= base_url() ?>contract/history/<?= $v['user_id'] ?>" title="Lihat riwayat">
                <span class="badge" style="color:#722ed1;background:#f9f0ff;border:1px solid #d3adf7;"><?= (int) $v['contract_count'] ?> kontrak</span>
            </a>
        </td>
        <td class="text-end">
            <a href="<?= base_url() ?>contract/history/<?= $v['user_id'] ?>" class="me-2" style="color:#1890ff;font-size:14px;text-decoration:none;" title="Riwayat"><i class="bi bi-clock-history"></i></a>
            <?php if (!empty($can_create)): ?>
                <a href="#!" onclick="openContractModal(<?= (int) $v['user_id'] ?>)" class="me-2" style="color:#52c41a;font-size:14px;text-decoration:none;" title="Perbarui / Perpanjang Kontrak"><i class="bi bi-arrow-repeat"></i></a>
            <?php endif; ?>
            <?php if ($has_contract && !empty($can_delete)): ?>
                <a href="#!" onclick="removeContract('<?= $v['contract_id'] ?>')" style="color:#ff4d4f;font-size:14px;" title="Hapus kontrak aktif"><i class="bi bi-trash"></i></a>
            <?php endif; ?>
        </td>
    </tr>
<?php
    }
}
?>
<tr>
    <td colspan="8">
        <div class="d-flex justify-content-end gap-2">
            <?php if ($current_page > 1): ?>
                <button class="btn btn-sm btn-outline-secondary" onclick="load_data(<?= $current_page - 1 ?>)"><i class="bi bi-chevron-left"></i> Sebelumnya</button>
            <?php endif; ?>
            <span class="align-self-center text-muted small">Halaman <?= $current_page ?></span>
            <?php if (count($data) >= $limit): ?>
                <button class="btn btn-sm btn-outline-secondary" onclick="load_data(<?= $current_page + 1 ?>)">Berikutnya <i class="bi bi-chevron-right"></i></button>
            <?php endif; ?>
        </div>
    </td>
</tr>
