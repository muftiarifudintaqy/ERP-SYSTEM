<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-3">
        <a href="<?= base_url() ?>contract/history/<?= $employee['id'] ?>" class="text-decoration-none text-muted me-2"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Kontrak</h4>
    </div>

    <div class="card">
        <div class="card-header"><strong><?= htmlspecialchars($employee['full_name']) ?></strong></div>
        <div class="card-body">
            <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>Form ini untuk <strong>mengoreksi</strong> data kontrak yang sudah ada, bukan memperpanjang. Untuk perpanjangan, gunakan tombol "Perbarui Kontrak".</div>

            <div id="formAlert"></div>

            <form id="contractForm" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $data['id'] ?>">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Jenis Kontrak <span class="text-danger">*</span></label>
                        <select class="form-control" name="dt[contract_type]" id="contract_type" required>
                            <option value="">-- Pilih --</option>
                            <?php foreach ($contract_types as $t): ?>
                                <option value="<?= $t ?>" <?= $data['contract_type'] == $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nomor Kontrak</label>
                        <input type="text" class="form-control" name="dt[contract_number]" value="<?= htmlspecialchars($data['contract_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jabatan</label>
                        <select class="form-control" name="dt[position_id]">
                            <option value="">-- Pilih Jabatan --</option>
                            <?php foreach ($positions as $p): ?>
                                <?php
                                $position_label = trim((string) ($p['name'] ?? ''));
                                $level_name = trim((string) ($p['level_name'] ?? ''));
                                if ($level_name !== '' && strcasecmp($level_name, 'No Level') !== 0) {
                                    $position_label .= ' - ' . $level_name;
                                }
                                ?>
                                <option value="<?= $p['id'] ?>" <?= $data['position_id'] == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($position_label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="dt[start_date]" value="<?= $data['start_date'] ?>" required>
                    </div>
                    <div class="col-md-4" id="end_date_wrap">
                        <label class="form-label">Tanggal Berakhir <span class="text-danger" id="end_req">*</span></label>
                        <input type="date" class="form-control" name="dt[end_date]" id="end_date" value="<?= $data['end_date'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Durasi</label>
                        <input type="text" class="form-control" name="dt[duration_text]" value="<?= htmlspecialchars($data['duration_text'] ?? '') ?>" placeholder="mis. 12 bulan">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Gaji (Rp)</label>
                        <input type="number" step="0.01" class="form-control" name="dt[salary]" value="<?= $data['salary'] ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Link Dokumen Kontrak <span class="text-muted">(Google Drive, dll)</span></label>
                        <input type="url" class="form-control" name="dt[document_url]" value="<?= htmlspecialchars($data['document_url'] ?? '') ?>" placeholder="https://drive.google.com/...">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-control" name="dt[notes]" rows="2"><?= htmlspecialchars($data['notes'] ?? '') ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Update Kontrak</button>
                    <a href="<?= base_url() ?>contract/history/<?= $employee['id'] ?>" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleEndDate() {
    const t = $('#contract_type').val();
    if (t === 'PKWTT') {
        $('#end_date').prop('required', false);
        $('#end_date_wrap').css('opacity', 0.5);
        $('#end_req').hide();
    } else {
        $('#end_date').prop('required', true);
        $('#end_date_wrap').css('opacity', 1);
        $('#end_req').show();
    }
}

$(document).ready(function () {
    toggleEndDate();
    $('#contract_type').on('change', toggleEndDate);

    $('#contractForm').on('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        const $btn = $(this).find('button[type=submit]');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');
        $.ajax({
            url: '<?= base_url() ?>contract/update',
            type: 'POST', data: fd, processData: false, contentType: false,
            success: function (res) {
                $('#formAlert').html(res);
                if (res.indexOf('success') !== -1) {
                    setTimeout(function () { window.location.href = '<?= base_url() ?>contract/history/<?= $employee['id'] ?>'; }, 800);
                } else {
                    $btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Update Kontrak');
                }
            },
            error: function () {
                $('#formAlert').html('<div class="alert alert-danger">Terjadi kesalahan.</div>');
                $btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Update Kontrak');
            }
        });
    });
});
</script>
