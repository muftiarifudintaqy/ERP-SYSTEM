<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-3">
        <a href="<?= base_url() ?>performance_review" class="text-decoration-none text-muted me-2"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0"><i class="bi bi-clipboard-plus me-2"></i>Buat Penilaian Karyawan</h4>
    </div>
    <div id="formAlert"></div>
    <div class="card"><div class="card-body">
        <form id="reviewForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Karyawan (dinilai) <span class="text-danger">*</span></label>
                    <select class="form-control" name="dt[reviewee_id]" required>
                        <option value="">-- Pilih Karyawan --</option>
                        <?php foreach ($employees as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Template Penilaian <span class="text-danger">*</span></label>
                    <select class="form-control" name="dt[template_id]" required>
                        <option value="">-- Pilih Template --</option>
                        <?php foreach ($templates as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Kuartal <span class="text-danger">*</span></label>
                    <select class="form-control" name="dt[period_quarter]" required>
                        <option value="1">Q1 (Jan-Mar)</option>
                        <option value="2">Q2 (Apr-Jun)</option>
                        <option value="3">Q3 (Jul-Sep)</option>
                        <option value="4">Q4 (Okt-Des)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tahun <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="dt[period_year]" value="<?= date('Y') ?>" min="2000" max="2100" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Label Periode <span class="text-muted">(opsional)</span></label>
                    <input type="text" class="form-control" name="dt[period_label]" placeholder="otomatis, mis. Q2 (April - Juni 2026)">
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Buat & Lanjut Assign Reviewer</button>
                <a href="<?= base_url() ?>performance_review" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div></div>
</div>
<script>
const BASE = '<?= base_url() ?>';
$('#reviewForm').on('submit', function (e) {
    e.preventDefault();
    const $btn = $(this).find('button[type=submit]').prop('disabled', true);
    $.post(BASE + 'performance_review/review_store', $(this).serialize(), function (res) {
        $('#formAlert').html(res);
        if (res.indexOf('success') !== -1) {
            const m = res.match(/<!--id:(\d+)-->/);
            const dest = m ? (BASE + 'performance_review/review_detail?id=' + m[1]) : (BASE + 'performance_review');
            setTimeout(() => window.location.href = dest, 700);
        } else { $btn.prop('disabled', false); }
    });
});
</script>
