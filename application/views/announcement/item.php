<?php
if (empty($data)) {
    echo '<tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>';
} else {
    $freq_label = [
        'once' => 'Sekali saja',
        'daily' => '1x per hari',
        'every_login' => 'Setiap login'
    ];
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;

        $start_date = !empty($value['start_date']) ? date('d M Y', strtotime($value['start_date'])) : '-';
        $end_date = !empty($value['end_date']) ? date('d M Y', strtotime($value['end_date'])) : '-';
        $periode = ($start_date == '-' && $end_date == '-') ? 'Selalu tayang' : ($start_date . ' s/d ' . $end_date);

        $freq = $freq_label[$value['frequency']] ?? $value['frequency'];

        $is_active = ($value['is_active'] == 1);
?>
        <tr>
            <td><?= $num ?></td>
            <td>
                <?php if (!empty($value['image'])): ?>
                    <img src="<?= base_url() ?>assets/uploads/announcements/<?= $value['image'] ?>" alt="banner"
                        style="width: 56px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #f0f0f0;">
                <?php else: ?>
                    <span class="text-muted"><i class="bi bi-image"></i></span>
                <?php endif; ?>
            </td>
            <td><strong><?= !empty($value['title']) ? htmlspecialchars($value['title']) : '<span class="text-muted fw-normal">(tanpa judul)</span>' ?></strong></td>
            <td><?= $periode ?></td>
            <td><?= $freq ?></td>
            <td>
                <span class="status-badge <?= $is_active ? 'status-active' : 'status-inactive' ?>">
                    <?= $is_active ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </td>
            <td class="text-end">
                <a href="<?= base_url() ?>announcement/detail?id=<?= $value['id'] ?>"
                    class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Detail">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="<?= base_url() ?>announcement/edit_page?id=<?= $value['id'] ?>"
                    class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <a href="#!" onclick="removeAnnouncement('<?= $value['id'] ?>')"
                    style="color: #ff4d4f; font-size: 14px;" title="Hapus">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>
<?php
    }
}
?>
