<?php
function hist_status_badge($status)
{
    $map = [
        'active'     => ['#52c41a', '#f6ffed', '✅ Aktif'],
        'expiring'   => ['#faad14', '#fffbe6', '🟡 Akan Berakhir'],
        'expired'    => ['#ff4d4f', '#fff2f0', '⛔ Berakhir'],
        'renewed'    => ['#1890ff', '#e6f7ff', '🔁 Diperpanjang'],
        'terminated' => ['#8c8c8c', '#fafafa', '⏹ Diberhentikan'],
    ];
    $m = $map[$status] ?? ['#d9d9d9', '#fafafa', ucfirst($status ?: '-')];
    return '<span class="badge" style="color:' . $m[0] . ';background:' . $m[1] . ';border:1px solid ' . $m[0] . ';">' . $m[2] . '</span>';
}

function leave_history_status_badge($status)
{
    $map = [
        'pending_leader' => ['#faad14', '#fffbe6', 'Menunggu Leader'],
        'pending_hr' => ['#faad14', '#fffbe6', 'Menunggu HR'],
        'approved' => ['#52c41a', '#f6ffed', 'Disetujui'],
        'rejected' => ['#ff4d4f', '#fff2f0', 'Ditolak'],
    ];
    $m = $map[$status] ?? ['#8c8c8c', '#fafafa', ucfirst($status ?: '-')];
    return '<span class="badge" style="color:' . $m[0] . ';background:' . $m[1] . ';border:1px solid ' . $m[0] . ';">' . $m[2] . '</span>';
}

function leave_contract_badge($contract)
{
    if (empty($contract)) {
        return '<span class="badge bg-light text-dark">Kontrak tidak ditemukan</span>';
    }
    $start = !empty($contract['start_date']) ? date('d M Y', strtotime($contract['start_date'])) : '-';
    $end = !empty($contract['end_date']) ? date('d M Y', strtotime($contract['end_date'])) : 'permanen';
    return '<span class="badge" style="color:#722ed1;background:#f9f0ff;border:1px solid #d3adf7;">'
        . htmlspecialchars($contract['contract_type'] ?? '-', ENT_QUOTES, 'UTF-8')
        . ' &middot; ' . $start . ' - ' . $end
        . '</span>';
}
?>
<style>
    .leave-history-list { display: grid; gap: 10px; }
    .leave-history-item { border: 1px solid #f0f0f0; border-radius: 4px; padding: 12px; background: #fff; display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: start; }
    .leave-history-meta { display: flex; flex-wrap: wrap; gap: 8px; color: rgba(0,0,0,.45); font-size: 12px; margin-top: 6px; }
    .leave-history-reason { color: rgba(0,0,0,.65); font-size: 13px; margin-top: 8px; white-space: pre-line; }
    .leave-history-duration { min-width: 72px; text-align: center; border: 1px solid #91d5ff; background: #e6f7ff; color: #096dd9; border-radius: 4px; padding: 7px 8px; }
    .leave-history-duration strong { display: block; font-size: 18px; line-height: 1; }
    @media (max-width: 576px) {
        .leave-history-item { grid-template-columns: 1fr; }
        .leave-history-duration { width: fit-content; text-align: left; }
    }
</style>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="<?= base_url() ?>contract" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
            <h4 class="mb-0 mt-1"><i class="bi bi-clock-history me-2"></i>Riwayat Kontrak &mdash; <?= htmlspecialchars($employee['full_name']) ?></h4>
            <small class="text-muted"><?= htmlspecialchars($employee['email'] ?? '') ?></small>
        </div>
        <?php if (!empty($can_create)): ?>
            <button type="button" onclick="openContractModal(<?= (int) $employee['id'] ?>)" class="btn btn-primary">
                <i class="bi bi-arrow-repeat me-1"></i>Perbarui / Perpanjang Kontrak
            </button>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Sisa Cuti Saat Ini</div>
                    <div class="d-flex align-items-end gap-2 mt-1">
                        <h3 class="mb-0"><?= intval($leave_balance ?? 0) ?></h3>
                        <span class="text-muted mb-1">hari</span>
                    </div>
                    <div class="mt-2">
                        <?php if (!empty($is_probation)): ?>
                            <span class="badge bg-warning text-dark">Probation</span>
                        <?php elseif (!empty($leave_balance_is_manual)): ?>
                            <span class="badge bg-info">Override Manual</span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark">Hitung Otomatis</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted small">Mulai Akrual</div>
                            <div class="fw-semibold">
                                <?= !empty($leave_accrual_start) ? date('d M Y', strtotime($leave_accrual_start)) : '-' ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Terakumulasi</div>
                            <div class="fw-semibold"><?= intval($leave_accrued ?? 0) ?> hari</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Terpakai</div>
                            <div class="fw-semibold"><?= intval($leave_used ?? 0) ?> hari</div>
                        </div>
                    </div>
                    <div class="text-muted small mt-2">
                        Terpakai hanya menghitung tipe pengajuan yang ditandai mengurangi sisa cuti.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($contracts)): ?>
        <div class="card"><div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox" style="font-size:48px;"></i>
            <p class="mt-3 mb-0">Belum ada kontrak untuk karyawan ini.</p>
        </div></div>
    <?php else: ?>
        <div class="card"><div class="card-body">
            <div style="position:relative;padding-left:24px;border-left:2px solid #f0f0f0;">
                <?php foreach ($contracts as $c):
                    $is_current = (int) $c['is_current'] === 1;
                    $dot = $is_current ? '#52c41a' : '#d9d9d9';
                ?>
                    <div style="position:relative;margin-bottom:20px;">
                        <span style="position:absolute;left:-31px;top:4px;width:14px;height:14px;border-radius:50%;background:<?= $dot ?>;border:2px solid #fff;box-shadow:0 0 0 2px <?= $dot ?>;"></span>
                        <div class="card" style="border:1px solid <?= $is_current ? '#b7eb8f' : '#f0f0f0' ?>;">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-start flex-wrap">
                                    <div>
                                        <h6 class="mb-1">
                                            <span class="badge" style="color:#1890ff;background:#e6f7ff;border:1px solid #91d5ff;"><?= htmlspecialchars($c['contract_type']) ?></span>
                                            <?= hist_status_badge($c['status']) ?>
                                            <?php if ($is_current): ?><span class="badge" style="background:#52c41a;color:#fff;">CURRENT</span><?php endif; ?>
                                        </h6>
                                        <div class="text-muted small">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?= date('d M Y', strtotime($c['start_date'])) ?>
                                            <?= !empty($c['end_date']) ? ' &ndash; ' . date('d M Y', strtotime($c['end_date'])) : ' &ndash; <em>permanen</em>' ?>
                                            <?php if (!empty($c['duration_text'])): ?> &middot; <?= htmlspecialchars($c['duration_text']) ?><?php endif; ?>
                                        </div>
                                        <div class="text-muted small mt-1">
                                            <?php if (!empty($c['position_name'])): ?><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($c['position_name']) ?> &middot; <?php endif; ?>
                                            <?php if (!empty($c['contract_number'])): ?>No: <?= htmlspecialchars($c['contract_number']) ?> &middot; <?php endif; ?>
                                            <?php if ($c['salary'] !== null && $c['salary'] !== ''): ?>Gaji: Rp <?= number_format((float) $c['salary'], 0, ',', '.') ?> &middot; <?php endif; ?>
                                            dibuat <?= !empty($c['created_at']) ? date('d M Y', strtotime($c['created_at'])) : '-' ?>
                                            <?php if (!empty($c['created_by_name'])): ?> oleh <?= htmlspecialchars($c['created_by_name']) ?><?php endif; ?>
                                        </div>
                                        <?php if (!empty($c['notes'])): ?><div class="small mt-1"><i class="bi bi-sticky me-1"></i><?= nl2br(htmlspecialchars($c['notes'])) ?></div><?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <a href="<?= base_url() ?>contract/detail?id=<?= $c['id'] ?>" class="me-2" style="color:#1890ff;" title="Detail"><i class="bi bi-eye"></i></a>
                                        <?php if (!empty($c['document_url'])): ?>
                                            <a href="<?= htmlspecialchars($c['document_url']) ?>" target="_blank" rel="noopener" class="me-2" style="color:#722ed1;" title="Dokumen"><i class="bi bi-link-45deg"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($can_edit)): ?>
                                            <a href="<?= base_url() ?>contract/edit_page?id=<?= $c['id'] ?>" class="me-2" style="color:#1890ff;" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div></div>
    <?php endif; ?>

    <div class="card mt-3">
        <div class="card-header">
            <strong><i class="bi bi-calendar-check me-1"></i>Riwayat Cuti / Izin</strong>
        </div>
        <div class="card-body">
            <?php if (empty($leave_requests)): ?>
                <div class="text-center text-muted py-4">Belum ada riwayat cuti / izin.</div>
            <?php else: ?>
                <div class="leave-history-list">
                    <?php foreach ($leave_requests as $leave): ?>
                        <div class="leave-history-item">
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <strong><?= htmlspecialchars($leave['leave_type_name'] ?? '-') ?></strong>
                                    <?= leave_history_status_badge($leave['status'] ?? '') ?>
                                    <?= leave_contract_badge($leave['contract_at_leave'] ?? null) ?>
                                </div>
                                <div class="leave-history-meta">
                                    <span><i class="bi bi-calendar3 me-1"></i><?= !empty($leave['start_date']) ? date('d M Y', strtotime($leave['start_date'])) : '-' ?><?= !empty($leave['end_date']) ? ' - ' . date('d M Y', strtotime($leave['end_date'])) : '' ?></span>
                                    <?php if (!empty($leave['created_at'])): ?>
                                        <span><i class="bi bi-clock me-1"></i>Diajukan <?= date('d M Y', strtotime($leave['created_at'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="leave-history-reason"><?= nl2br(htmlspecialchars($leave['reason'] ?: 'Tidak ada alasan')) ?></div>
                            </div>
                            <div class="leave-history-duration">
                                <strong><?= intval($leave['total_days'] ?? 0) ?></strong>
                                <span>hari</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="popupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" id="popupModalContent"></div>
    </div>
</div>

<script>
const BASE = '<?= base_url() ?>';
function openContractModal(userId) {
    $('#popupModalContent').html('<div class="modal-body text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="text-muted mt-3 mb-0">Memuat form kontrak...</p></div>');
    const modal = new bootstrap.Modal(document.getElementById('popupModal'));
    modal.show();
    $.get(BASE + 'contract/create_page', { user_id: userId, modal: 1 }, function (html) {
        $('#popupModalContent').html(html);
        bindContractModalForm();
    }).fail(function () {
        $('#popupModalContent').html('<div class="modal-body text-center py-5 text-danger">Gagal memuat form kontrak.</div>');
    });
}

function bindContractModalForm() {
    const $form = $('#popupModalContent #contractForm');
    if (!$form.length || $form.data('contract-form-ready')) return;
    $form.data('contract-form-ready', true);

    function toggleEndDate() {
        const t = $('#popupModalContent #contract_type').val();
        if (t === 'PKWTT') {
            $('#popupModalContent #end_date').prop('required', false).val('');
            $('#popupModalContent #end_date_wrap').css('opacity', 0.5);
            $('#popupModalContent #end_req').hide();
        } else {
            $('#popupModalContent #end_date').prop('required', true);
            $('#popupModalContent #end_date_wrap').css('opacity', 1);
            $('#popupModalContent #end_req').show();
        }
    }

    toggleEndDate();
    $('#popupModalContent #contract_type').on('change', toggleEndDate);
    $form.on('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        const $btn = $(this).find('button[type=submit]');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');
        $.ajax({
            url: BASE + 'contract/store',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                $('#popupModalContent #formAlert').html(res);
                if (res.indexOf('success') !== -1) {
                    setTimeout(function () {
                        window.location.reload();
                    }, 800);
                } else {
                    $btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Simpan Kontrak');
                }
            },
            error: function () {
                $('#popupModalContent #formAlert').html('<div class="alert alert-danger">Terjadi kesalahan.</div>');
                $btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Simpan Kontrak');
            }
        });
    });
}
</script>
