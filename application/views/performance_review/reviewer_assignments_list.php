<?php if (empty($assignments)): ?>
    <div class="text-muted small">Belum ada reviewer yang di-assign.</div>
<?php else: ?>
    <div class="list-group list-group-flush border rounded-1">
        <?php foreach ($assignments as $assignment): ?>
            <?php
            $is_active = (int) ($assignment['is_active'] ?? 1) === 1;
            $is_submitted = ($assignment['status'] ?? '') === 'submitted';
            ?>
            <div class="list-group-item px-2 py-2 d-flex align-items-center justify-content-between gap-2 <?= $is_active ? '' : 'bg-light text-muted' ?>">
                <div class="min-w-0">
                    <a href="<?= base_url() ?>performance_review/reviewer_answer/<?= (int) $assignment['id'] ?>" target="_blank" rel="noopener" class="fw-semibold text-decoration-none">
                        <?= htmlspecialchars($assignment['reviewer_name'] ?? '-') ?>
                    </a>
                    <div class="small text-muted">
                        <?= htmlspecialchars($roleLabel[$assignment['reviewer_role']] ?? $assignment['reviewer_role']) ?>
                        &middot;
                        <?= $is_submitted ? 'Terisi' : 'Menunggu' ?>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge <?= $is_active ? 'bg-success' : 'bg-secondary' ?>"><?= $is_active ? 'Aktif' : 'Nonaktif' ?></span>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input pr-assignment-toggle" type="checkbox" role="switch" data-id="<?= (int) $assignment['id'] ?>" <?= $is_active ? 'checked' : '' ?>>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
