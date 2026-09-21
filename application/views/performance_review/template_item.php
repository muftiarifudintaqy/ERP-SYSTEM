<?php
if (empty($data)) {
    echo '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada template</td></tr>';
} else {
    foreach ($data as $i => $t) {
        $statusBadge = $t['status'] === 'active'
            ? '<span class="badge" style="color:#52c41a;background:#f6ffed;border:1px solid #b7eb8f;">Aktif</span>'
            : '<span class="badge" style="color:#8c8c8c;background:#fafafa;border:1px solid #d9d9d9;">Nonaktif</span>';
?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><strong><?= htmlspecialchars($t['name']) ?></strong>
            <?php if (!empty($t['description'])): ?><small class="text-muted d-block"><?= htmlspecialchars($t['description']) ?></small><?php endif; ?>
        </td>
        <td><span class="badge" style="color:#1890ff;background:#e6f7ff;border:1px solid #91d5ff;"><?= htmlspecialchars($t['type_label']) ?></span></td>
        <td><?= (int) $t['indicator_count'] ?> indikator</td>
        <td><?= $statusBadge ?></td>
        <td class="text-end">
            <div class="pr-action-group">
                <?php if (!empty($can_edit)): ?>
                    <a href="<?= base_url() ?>performance_review/template_edit_page?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                <?php endif; ?>
                <?php if (!empty($can_delete)): ?>
                    <button type="button" class="pr-icon-btn" onclick="confirmDelete('performance_review/template_delete', <?= $t['id'] ?>, 'Template akan dihapus permanen.')" title="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php }
} ?>
