<?php
function pr_status_badge($s)
{
    $map = [
        'draft' => ['#8c8c8c', '#fafafa', 'Draft'],
        'in_progress' => ['#faad14', '#fffbe6', 'Berlangsung'],
        'completed' => ['#1890ff', '#e6f7ff', 'Selesai'],
        'published' => ['#52c41a', '#f6ffed', 'Terbit'],
    ];
    $m = $map[$s] ?? ['#d9d9d9', '#fafafa', ucfirst($s)];
    return '<span class="badge" style="color:' . $m[0] . ';background:' . $m[1] . ';border:1px solid ' . $m[0] . ';">' . $m[2] . '</span>';
}
$roleLabel = ['self' => 'Self', 'leader' => 'Leader', 'hr' => 'HR', 'peer' => 'Peer'];
if (empty($data)) {
    echo '<tr><td colspan="7" class="text-center pr-empty">Belum ada penilaian</td></tr>';
} else {
    foreach ($data as $i => $r) {
        $has_final = $r['final_total'] !== null || $r['final_target'] !== null;
        $reviewers = $reviewer_map[(int) $r['id']] ?? [];
        $submitted_reviewers = array_values(array_filter($reviewers, function ($reviewer) {
            return ($reviewer['status'] ?? '') === 'submitted' && (int) ($reviewer['is_active'] ?? 1) === 1;
        }));
        $review_label = trim(($r['reviewee_name'] ?? '-') . ' - ' . ($r['period_label'] ?? '-'));
?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td>
            <strong><?= htmlspecialchars($r['reviewee_name'] ?? '-') ?></strong>
            <?php if ($has_final): ?>
                <div class="small text-muted">Final: <?= rtrim(rtrim((string) $r['final_total'], '0'), '.') ?><?= $r['final_target'] ? ' / ' . rtrim(rtrim((string) $r['final_target'], '0'), '.') : '' ?></div>
            <?php endif; ?>
        </td>
        <td><span class="fw-semibold"><?= htmlspecialchars($r['template_name'] ?? '-') ?></span></td>
        <td><span class="text-muted"><?= htmlspecialchars($r['period_label'] ?? '-') ?></span></td>
        <td class="pr-reviewer-cell">
            <?php if (!empty($submitted_reviewers)): ?>
                <div class="pr-reviewer-labels">
                    <?php foreach ($submitted_reviewers as $reviewer): ?>
                        <a class="pr-reviewer-label" href="<?= base_url() ?>performance_review/reviewer_answer/<?= $reviewer['id'] ?>" target="_blank" rel="noopener" title="Lihat jawaban <?= htmlspecialchars($reviewer['reviewer_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-check-circle"></i>
                            <span><?= htmlspecialchars($reviewer['reviewer_name'] ?? '-') ?></span>
                            <small>(<?= htmlspecialchars($roleLabel[$reviewer['reviewer_role']] ?? $reviewer['reviewer_role']) ?>)</small>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if ((int) $r['submitted_count'] < (int) $r['reviewer_count']): ?>
                    <span class="small text-muted d-block mt-1"><?= (int) $r['submitted_count'] ?>/<?= (int) $r['reviewer_count'] ?> isi</span>
                <?php endif; ?>
            <?php else: ?>
                <span class="pr-reviewer-empty"><?= (int) $r['reviewer_count'] > 0 ? 'Belum ada yang isi' : 'Belum ada reviewer' ?></span>
                <?php if ((int) $r['reviewer_count'] > 0): ?>
                    <span class="small text-muted d-block"><?= (int) $r['submitted_count'] ?>/<?= (int) $r['reviewer_count'] ?> isi</span>
                <?php endif; ?>
            <?php endif; ?>
        </td>
        <td class="pr-status-cell"><?= pr_status_badge($r['status']) ?></td>
        <td class="pr-action-cell">
            <div class="pr-action-group">
                <?php if (!empty($can_edit)): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick='openReviewerModal(<?= $r['id'] ?>, <?= json_encode($review_label, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                        <i class="bi bi-person-plus me-1"></i> Reviewer
                    </button>
                    <a href="<?= base_url() ?>performance_review/final_page?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil-square me-1"></i> Final
                    </a>
                <?php endif; ?>
                <?php if ($has_final): ?>
                    <a href="<?= base_url() ?>performance_review/pdf/<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                    </a>
                <?php endif; ?>
                <?php if (!empty($can_publish) && $has_final && $r['status'] !== 'published'): ?>
                    <button type="button" class="btn btn-sm btn-success" onclick="prAction('publish', <?= $r['id'] ?>)">
                        <i class="bi bi-send me-1"></i> Publish
                    </button>
                <?php elseif (!empty($can_publish) && $r['status'] === 'published'): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="prAction('unpublish', <?= $r['id'] ?>)">
                        <i class="bi bi-x-circle me-1"></i> Unpublish
                    </button>
                <?php endif; ?>
                <?php if (!empty($can_delete)): ?>
                    <button type="button" class="pr-icon-btn" onclick="confirmDelete('performance_review/review_delete', <?= $r['id'] ?>, 'Penilaian beserta isian akan dihapus.')" title="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php }
} ?>
