<?php
$r = $review;
$a = $assignment;
$roleLabel = ['self' => 'Self (Karyawan)', 'leader' => 'Leader', 'hr' => 'HR', 'peer' => 'Peer'];
?>
<style>
    .pr-answer-shell { max-width: 1180px; margin: 0 auto; }
    .pr-answer-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .pr-answer-title { margin: 4px 0 2px; font-size: 20px; font-weight: 600; color: rgba(0,0,0,0.85); }
    .pr-answer-meta { color: rgba(0,0,0,0.55); font-size: 14px; }
    .pr-answer-card { border: 1px solid #f0f0f0; border-radius: 2px; box-shadow: 0 2px 8px rgba(0,0,0,0.09); margin-bottom: 16px; }
    .pr-answer-shell .form-control[readonly],
    .pr-answer-shell .form-select:disabled,
    .pr-answer-shell .form-check-input:disabled { background-color: #f8f9fa; opacity: 1; }
</style>

<div class="container-fluid py-3">
    <div class="pr-answer-shell">
        <div class="pr-answer-head">
            <div>
                <a href="<?= base_url() ?>performance_review" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                <h4 class="pr-answer-title">Jawaban Reviewer</h4>
                <div class="pr-answer-meta">
                    <strong><?= htmlspecialchars($a['reviewer_name'] ?? '-') ?></strong>
                    <span class="badge bg-light text-dark ms-1"><?= htmlspecialchars($roleLabel[$a['reviewer_role']] ?? $a['reviewer_role']) ?></span>
                    &middot; Menilai <strong><?= htmlspecialchars($r['reviewee_name'] ?? '-') ?></strong>
                    &middot; <?= htmlspecialchars($r['template_name'] ?? '-') ?>
                    &middot; <?= htmlspecialchars($r['period_label'] ?? '-') ?>
                </div>
            </div>
            <div>
                <?= ($a['status'] === 'submitted')
                    ? '<span class="badge" style="color:#52c41a;background:#f6ffed;border:1px solid #b7eb8f;">Terisi</span>'
                    : '<span class="badge" style="color:#faad14;background:#fffbe6;border:1px solid #ffe58f;">Menunggu</span>' ?>
            </div>
        </div>

        <?php
        $current_cat = null;
        foreach ($indicators as $ind):
            if ($ind['category'] !== $current_cat):
                if ($current_cat !== null) echo '</div></div>';
                $current_cat = $ind['category'];
        ?>
            <div class="card pr-answer-card">
                <div class="card-header"><strong><?= htmlspecialchars($current_cat ?: 'Indikator') ?></strong></div>
                <div class="card-body">
        <?php endif; ?>
            <div class="mb-3 pb-3 border-bottom">
                <div class="fw-semibold mb-2"><?= htmlspecialchars($ind['name']) ?>
                    <span class="badge bg-light text-dark ms-1"><?= htmlspecialchars($ind['input_type']) ?></span>
                </div>
                <?php $this->load->view('performance_review/_indicator_input', [
                    'ind' => $ind,
                    'existing' => $scores[(int) $ind['id']] ?? null,
                    'readonly' => true
                ]); ?>
            </div>
        <?php endforeach; ?>
        <?php if ($current_cat !== null) echo '</div></div>'; ?>
    </div>
</div>
