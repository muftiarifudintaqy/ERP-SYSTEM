<?php $r = $review; ?>
<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-3">
        <a href="<?= base_url() ?>performance_review/my_assignments" class="text-decoration-none text-muted me-2"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0"><i class="bi bi-pencil me-2"></i>Isi Penilaian Kinerja</h4>
    </div>
    <p class="text-muted">
        Menilai: <strong><?= htmlspecialchars($r['reviewee_name']) ?></strong> &middot;
        <?= htmlspecialchars($r['template_name']) ?> &middot; <?= htmlspecialchars($r['period_label']) ?>
        &middot; Peran Anda: <span class="badge bg-light text-dark"><?= htmlspecialchars($assignment['reviewer_role']) ?></span>
    </p>

    <div id="formAlert"></div>

    <form id="fillForm">
        <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
        <?php
        $current_cat = null;
        foreach ($indicators as $ind):
            if ($ind['category'] !== $current_cat):
                if ($current_cat !== null) echo '</div></div>';
                $current_cat = $ind['category'];
        ?>
            <div class="card mb-3"><div class="card-header"><strong><?= htmlspecialchars($current_cat ?: 'Indikator') ?></strong></div><div class="card-body">
        <?php endif; ?>
            <div class="mb-3 pb-3 border-bottom">
                <div class="fw-semibold mb-2"><?= htmlspecialchars($ind['name']) ?></div>
                <?php $this->load->view('performance_review/_indicator_input', ['ind' => $ind, 'existing' => $scores[$ind['id']] ?? null]); ?>
            </div>
        <?php endforeach; ?>
        <?php if ($current_cat !== null) echo '</div></div>'; ?>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Simpan & Kirim</button>
            <a href="<?= base_url() ?>performance_review/my_assignments" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>
<script>
const BASE = '<?= base_url() ?>';
$('#fillForm').on('submit', function (e) {
    e.preventDefault();
    const $btn = $(this).find('button[type=submit]').prop('disabled', true);
    $.post(BASE + 'performance_review/submit_fill', $(this).serialize(), function (res) {
        $('#formAlert').html(res);
        $btn.prop('disabled', false);
        if (res.indexOf('success') !== -1) setTimeout(() => window.location.href = BASE + 'performance_review/my_assignments', 800);
    });
});
</script>
