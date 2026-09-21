<?php
if (empty($data)) {
    echo '<tr><td colspan="4" class="text-center text-muted">Tidak ada data</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;
?>
        <tr data-id="<?= $value['id'] ?>">
            <td><?= $num ?></td>
            <td class="editable" data-field="name">
                <span class="view-mode"><?= $value['name'] ? htmlspecialchars($value['name']) : '-' ?></span>
                <div class="edit-mode">
                    <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($value['name']) ?>">
                </div>
            </td>
            <td class="editable" data-field="notes">
                <span class="view-mode"><?= $value['notes'] ? htmlspecialchars($value['notes']) : '-' ?></span>
                <div class="edit-mode">
                    <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($value['notes']) ?>">
                </div>
            </td>
            <td class="text-end">
                <a href="#" class="delete-row"
                    style="color: #ff4d4f; font-size: 14px;" title="Hapus">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>
<?php
    }
}
?>
