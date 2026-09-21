<?php
if (empty($data)) {
    echo '<tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;

        $status = strtolower($value['status'] ?? '');
        $status_color = 'bg-secondary';
        if ($status == 'borrowed' || $status == 'dipinjam') {
            $status_color = 'bg-warning';
        } else if ($status == 'returned' || $status == 'dikembalikan') {
            $status_color = 'bg-success';
        }
?>
        <tr>
            <td><?= $num ?></td>
            <td>
                <div class="fw-semibold"><?= $value['asset_name'] ?></div>
            </td>
            <td><?= $value['borrower_name'] ?></td>
            <td><?= $value['loan_date'] ?></td>
            <td><?= $value['return_date'] ? $value['return_date'] : '-' ?></td>
            <td>
                <?php if (!empty($value['status'])): ?>
                    <span class="badge <?= $status_color ?>"><?= $value['status'] ?></span>
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
            <td class="text-end">
                <a href="<?= base_url() ?>/asset_management/loan_edit_page?id=<?= $value['id'] ?>"
                    class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <a href="#" onclick="removeLoan('<?= $value['id'] ?>')"
                    style="color: #ff4d4f; font-size: 14px;" title="Hapus">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>
<?php
    }
}
?>
