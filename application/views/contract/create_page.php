<?php
$is_modal = !empty($is_modal);
$history_url = base_url() . 'contract/history/' . $employee['id'];
?>
<?php if (!$is_modal): ?>
<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-3">
        <a href="<?= $history_url ?>" class="text-decoration-none text-muted me-2"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Perbarui / Perpanjang Kontrak</h4>
    </div>

    <div class="card">
        <div class="card-header"><strong><?= htmlspecialchars($employee['full_name']) ?></strong></div>
        <div class="card-body">
<?php else: ?>
<form id="contractForm" enctype="multipart/form-data" data-history-url="<?= $history_url ?>" data-modal="<?= $is_modal ? '1' : '0' ?>">
    <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Perbarui / Perpanjang Kontrak</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
    </div>
    <div class="modal-body">
        <div class="fw-semibold mb-3"><?= htmlspecialchars($employee['full_name']) ?></div>
<?php endif; ?>
            <?php if (!empty($current)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-1"></i>Kontrak saat ini: <strong><?= htmlspecialchars($current['contract_type']) ?></strong>
                    (<?= date('d M Y', strtotime($current['start_date'])) ?><?= !empty($current['end_date']) ? ' &ndash; ' . date('d M Y', strtotime($current['end_date'])) : ' &ndash; permanen' ?>).
                    Menyimpan akan menjadikan kontrak ini sebagai <strong>riwayat</strong> dan membuat kontrak baru yang aktif.
                </div>
            <?php endif; ?>

            <div id="formAlert"></div>

            <?php if (!$is_modal): ?><form id="contractForm" enctype="multipart/form-data" data-history-url="<?= $history_url ?>" data-modal="0"><?php endif; ?>
                <input type="hidden" name="dt[user_id]" value="<?= $employee['id'] ?>">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Jenis Kontrak <span class="text-danger">*</span></label>
                        <select class="form-control" name="dt[contract_type]" id="contract_type" required>
                            <option value="">-- Pilih --</option>
                            <?php foreach ($contract_types as $t): ?>
                                <option value="<?= $t ?>"><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">PKWTT = tetap (tanpa tanggal berakhir)</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nomor Kontrak</label>
                        <input type="text" class="form-control" name="dt[contract_number]" placeholder="opsional">
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
                                <option value="<?= $p['id'] ?>" <?= (!empty($current['position_id']) && $current['position_id'] == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($position_label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="dt[start_date]" required>
                    </div>
                    <div class="col-md-4" id="end_date_wrap">
                        <label class="form-label">Tanggal Berakhir <span class="text-danger" id="end_req">*</span></label>
                        <input type="date" class="form-control" name="dt[end_date]" id="end_date">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Durasi</label>
                        <input type="text" class="form-control" name="dt[duration_text]" placeholder="mis. 12 bulan">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Gaji (Rp)</label>
                        <input type="number" step="0.01" class="form-control" name="dt[salary]" placeholder="opsional">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Link Dokumen Kontrak <span class="text-muted">(Google Drive, dll)</span></label>
                        <input type="url" class="form-control" name="dt[document_url]" placeholder="https://drive.google.com/...">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-control" name="dt[notes]" rows="2" placeholder="Catatan internal HR (opsional)"></textarea>
                </div>

<?php if ($is_modal): ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Kontrak</button>
    </div>
</form>
<?php else: ?>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Kontrak</button>
                    <a href="<?= $history_url ?>" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    const isModal = <?= $is_modal ? 'true' : 'false' ?>;
    const historyUrl = '<?= $history_url ?>';

    function toggleEndDate() {
        const t = $('#contract_type').val();
        if (t === 'PKWTT') {
            $('#end_date').prop('required', false).val('');
            $('#end_date_wrap').css('opacity', 0.5);
            $('#end_req').hide();
        } else {
            $('#end_date').prop('required', true);
            $('#end_date_wrap').css('opacity', 1);
            $('#end_req').show();
        }
    }

    function initContractForm() {
        const $form = $('#contractForm');
        if (!$form.length || $form.data('contract-form-ready')) return;
        $form.data('contract-form-ready', true);
        toggleEndDate();
        $('#contract_type').on('change', toggleEndDate);

        $form.on('submit', function (e) {
            e.preventDefault();
            const fd = new FormData(this);
            const $btn = $(this).find('button[type=submit]');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');
            $.ajax({
                url: '<?= base_url() ?>contract/store',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function (res) {
                    $('#formAlert').html(res);
                    if (res.indexOf('success') !== -1) {
                        setTimeout(function () {
                            if (isModal) {
                                const modalEl = document.getElementById('popupModal');
                                if (modalEl && window.bootstrap) {
                                    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                                    modal.hide();
                                }
                                if (typeof load_data === 'function') {
                                    load_data();
                                } else {
                                    window.location.reload();
                                }
                            } else {
                                window.location.href = historyUrl;
                            }
                        }, 800);
                    } else {
                        $btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Simpan Kontrak');
                    }
                },
                error: function () {
                    $('#formAlert').html('<div class="alert alert-danger">Terjadi kesalahan.</div>');
                    $btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Simpan Kontrak');
                }
            });
        });
    }

    initContractForm();
    $(initContractForm);
})();
</script>
