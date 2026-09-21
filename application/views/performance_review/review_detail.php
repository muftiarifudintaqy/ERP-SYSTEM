<?php
$r = $review;
$roleLabel = ['self' => 'Self (Karyawan)', 'leader' => 'Leader', 'hr' => 'HR', 'peer' => 'Peer'];
$statusMap = [
    'draft' => ['#8c8c8c', 'Draft'], 'in_progress' => ['#faad14', 'Berlangsung'],
    'completed' => ['#1890ff', 'Selesai'], 'published' => ['#52c41a', 'Terbit'],
];
$sm = $statusMap[$r['status']] ?? ['#8c8c8c', $r['status']];
?>
<style>
    /* Select2 Styling */
    .select2-container .select2-selection--single {
        box-sizing: border-box;
        cursor: pointer;
        display: block;
        height: 32px;
        user-select: none;
        -webkit-user-select: none;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: rgba(0, 0, 0, 0.85);
        line-height: 32px;
        padding-left: 11px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px;
        position: absolute;
        top: 0px;
        right: 1px;
        width: 20px;
    }

    .select2 {
        height: 32px !important;
        min-width: 100% !important;
        margin-bottom: 8px;
    }

    /* Ant Design-like Table Styling */
    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #f0f0f0;
        border-radius: 2px;
    }

    .table thead th {
        background-color: #fafafa;
        color: rgba(0, 0, 0, 0.85);
        font-weight: 500;
        text-align: left;
        padding: 12px 8px;
        font-size: 14px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table tbody td {
        padding: 12px 8px !important;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table-hover tbody tr:hover {
        background-color: #fafafa;
    }

    /* Card Styling - Ant Design-like */
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
        margin-bottom: 16px;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
        height: 56px;
    }

    .card-body {
        padding: 16px;
    }

    /* Button Styling - Ant Design-like */
    .btn {
        border-radius: 2px;
        padding: 4px 15px;
        font-size: 14px;
        height: 32px;
        line-height: 1.5;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
    }

    .btn-outline-secondary {
        color: rgba(0, 0, 0, 0.65);
        border-color: #d9d9d9;
        background: #fff;
    }

    .btn-outline-secondary:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Badge Styling - Ant Design-like */
    .badge {
        font-size: 12px;
        height: 22px;
        padding: 0 8px;
        line-height: 22px;
        border-radius: 2px;
        font-weight: normal;
    }

    .reviewer-inactive-row td {
        background: #fafafa;
        color: rgba(0, 0, 0, 0.45);
    }

    .reviewer-active-badge {
        color: #52c41a;
        background: #f6ffed;
        border: 1px solid #b7eb8f;
    }

    .reviewer-inactive-badge {
        color: #8c8c8c;
        background: #fafafa;
        border: 1px solid #d9d9d9;
    }

    /* Pagination Styling - Ant Design-like */
    .pagination {
        margin-top: 16px;
        justify-content: flex-end;
    }

    .page-item {
        margin-right: 8px;
    }

    .page-item:last-child {
        margin-right: 0;
    }

    .page-item.active .page-link {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .page-link {
        min-width: 32px;
        height: 32px;
        line-height: 30px;
        text-align: center;
        color: rgba(0, 0, 0, 0.65);
        border-radius: 2px;
        padding: 0;
        margin: 0;
        border: 1px solid #d9d9d9;
    }

    .page-link:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Search Form Styling - Ant Design-like */
    .search-form {
        margin-bottom: 16px;
    }

    .search-form .input-group {
        border-radius: 2px;
        display: flex;
        justify-content: space-between;
    }

    .form-control {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-control:hover {
        border-color: #40a9ff;
    }

    .form-control:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .form-select {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-select:hover {
        border-color: #40a9ff;
    }

    .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    /* Alert Styling - Ant Design-like */
    .alert {
        padding: 8px 15px;
        border-radius: 2px;
        font-size: 14px;
    }

    .alert-info {
        background-color: #e6f7ff;
        border-color: #91d5ff;
        color: rgba(0, 0, 0, 0.65);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <a href="<?= base_url() ?>performance_review" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
            <h4 class="mb-1 mt-1"><?= htmlspecialchars($r['reviewee_name']) ?></h4>
            <div class="text-muted small">
                <?= htmlspecialchars($r['template_name']) ?> &middot; <?= htmlspecialchars($r['period_label']) ?>
                <?php if (!empty($r['position_name'])): ?> &middot; <?= htmlspecialchars($r['position_name']) ?><?php endif; ?>
                &middot; <span class="badge" style="color:<?= $sm[0] ?>;border:1px solid <?= $sm[0] ?>;background:#fff;"><?= $sm[1] ?></span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <?php if (!empty($can_edit)): ?>
                <a href="<?= base_url() ?>performance_review/final_page?id=<?= $r['id'] ?>" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i>Final</a>
            <?php endif; ?>
            <?php if ($r['status'] === 'published'): ?>
                <a href="<?= base_url() ?>performance_review/pdf/<?= $r['id'] ?>?dl=1" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
                <?php if (!empty($can_publish)): ?><button class="btn btn-outline-danger" onclick="prAction('unpublish', <?= $r['id'] ?>)">Batalkan Terbit</button><?php endif; ?>
            <?php elseif (!empty($can_publish)): ?>
                <a href="<?= base_url() ?>performance_review/pdf/<?= $r['id'] ?>" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-eye me-1"></i>Preview PDF</a>
                <button class="btn btn-success" onclick="prAction('publish', <?= $r['id'] ?>)"><i class="bi bi-send me-1"></i>Publish ke Karyawan</button>
            <?php endif; ?>
        </div>
    </div>

    <div id="prAlert"></div>

    <div class="row">
        <!-- ASSIGNMENTS -->
        <div class="col-lg-5">
            <div class="card mb-3"><div class="card-header"><strong><i class="bi bi-people me-1"></i>Reviewer (Pengisi)</strong></div>
            <div class="card-body">
                <?php if (!empty($can_edit)): ?>
                <div class="row g-2 mb-3">
                    <div class="col-6"><select id="assign_user" class="form-select form-select-sm">
                        <option value="">-- Pilih Karyawan --</option>
                        <?php foreach ($employees as $e): ?><option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['full_name']) ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="col-3"><select id="assign_role" class="form-select form-select-sm">
                        <?php foreach ($reviewer_roles as $rr): ?><option value="<?= $rr ?>"><?= $roleLabel[$rr] ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="col-3"><button class="btn btn-sm btn-primary w-100" onclick="addReviewer(<?= $r['id'] ?>)">Tambah</button></div>
                </div>
                <?php endif; ?>
                <table class="table table-sm">
                    <thead><tr><th>Nama</th><th>Peran</th><th>Status</th><th>Akses</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($assignments)): ?>
                            <tr><td colspan="5" class="text-muted text-center">Belum ada reviewer</td></tr>
                        <?php else: foreach ($assignments as $a): ?>
                            <?php $is_assignment_active = (int) ($a['is_active'] ?? 1) === 1; ?>
                            <tr class="<?= $is_assignment_active ? '' : 'reviewer-inactive-row' ?>">
                                <td><a href="<?= base_url() ?>performance_review/reviewer_answer/<?= $a['id'] ?>" class="text-decoration-none" target="_blank" rel="noopener"><?= htmlspecialchars($a['reviewer_name'] ?? '-') ?></a></td>
                                <td><span class="badge bg-light text-dark"><?= $roleLabel[$a['reviewer_role']] ?? $a['reviewer_role'] ?></span></td>
                                <td><?= $a['status'] === 'submitted'
                                        ? '<span class="badge" style="color:#52c41a;background:#f6ffed;border:1px solid #b7eb8f;">Terisi</span>'
                                        : '<span class="badge" style="color:#faad14;background:#fffbe6;border:1px solid #ffe58f;">Menunggu</span>' ?></td>
                                <td>
                                    <span class="badge <?= $is_assignment_active ? 'reviewer-active-badge' : 'reviewer-inactive-badge' ?>">
                                        <?= $is_assignment_active ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if (!empty($can_edit)): ?>
                                    <div class="form-check form-switch d-inline-flex align-items-center me-2 mb-0" title="Tampilkan/sembunyikan dari Penilaian Saya">
                                        <input class="form-check-input reviewer-active-toggle" type="checkbox" role="switch" data-id="<?= (int) $a['id'] ?>" <?= $is_assignment_active ? 'checked' : '' ?>>
                                    </div>
                                    <a href="<?= base_url() ?>performance_review/fill/<?= $a['id'] ?>" class="me-2" style="color:#1890ff;" title="Isi (atas nama)"><i class="bi bi-pencil"></i></a>
                                    <a href="#!" onclick="removeReviewer(<?= $a['id'] ?>)" style="color:#ff4d4f;" title="Hapus"><i class="bi bi-x-circle"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Reviewer mengisi via menu <strong>Penilaian Saya</strong> di akunnya. Isian mereka jadi referensi (read-only) untuk HR.</p>
            </div></div>
        </div>

        <!-- REFERENCES + FINAL SUMMARY -->
        <div class="col-lg-7">
            <div class="card mb-3"><div class="card-header"><strong><i class="bi bi-eye me-1"></i>Referensi Isian Reviewer (read-only)</strong></div>
            <div class="card-body">
                <?php
                $submitted = array_filter($assignments, function ($a) { return $a['status'] === 'submitted'; });
                if (empty($submitted)): ?>
                    <p class="text-muted mb-0">Belum ada isian reviewer.</p>
                <?php else: ?>
                    <div class="accordion" id="refAcc">
                    <?php foreach ($submitted as $k => $a): $aid = $a['id']; ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ref<?= $aid ?>">
                                <?= htmlspecialchars($a['reviewer_name']) ?> <span class="badge bg-light text-dark ms-2"><?= $roleLabel[$a['reviewer_role']] ?? $a['reviewer_role'] ?></span>
                            </button></h2>
                            <div id="ref<?= $aid ?>" class="accordion-collapse collapse" data-bs-parent="#refAcc">
                                <div class="accordion-body p-0">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>Indikator</th><th width="80">Skor</th><th>Keterangan</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($indicators as $ind):
                                            $s = $reference_scores[$aid][$ind['id']] ?? null; ?>
                                            <tr>
                                                <td><?= htmlspecialchars($ind['name']) ?></td>
                                                <td><?= ($s && $s['score'] !== null) ? rtrim(rtrim((string) $s['score'], '0'), '.') : '-' ?></td>
                                                <td class="small text-muted"><?= $s ? nl2br(htmlspecialchars($s['answer_text'] ?? '')) : '' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div></div>

            <div class="card"><div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Penilaian Final (dasar PDF)</div>
                    <?php if (!empty($final_scores)): ?>
                        <h5 class="mb-0">Total: <?= $r['final_total'] !== null ? rtrim(rtrim((string) $r['final_total'], '0'), '.') : '-' ?>
                            <?php if ($r['final_target']): ?><small class="text-muted">/ target <?= rtrim(rtrim((string) $r['final_target'], '0'), '.') ?></small><?php endif; ?>
                            <?php if (!empty($r['final_grade'])): ?><span class="badge bg-info ms-2"><?= htmlspecialchars($r['final_grade']) ?></span><?php endif; ?>
                        </h5>
                    <?php else: ?>
                        <span class="text-muted">Belum diisi</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($can_edit)): ?>
                    <a href="<?= base_url() ?>performance_review/final_page?id=<?= $r['id'] ?>" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i>Final</a>
                <?php endif; ?>
            </div></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE = '<?= base_url() ?>';
function addReviewer(reviewId) {
    const uid = $('#assign_user').val(); const role = $('#assign_role').val();
    if (!uid) { Swal.fire('', 'Pilih karyawan dulu', 'warning'); return; }
    $.post(BASE + 'performance_review/assign_reviewer', { review_id: reviewId, reviewer_id: uid, reviewer_role: role }, function (res) {
        if (res.indexOf('success') !== -1) location.reload();
        else $('#prAlert').html(res);
    });
}
function removeReviewer(id) {
    Swal.fire({ title: 'Hapus reviewer?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ff4d4f' }).then(r => {
        if (r.isConfirmed) $.post(BASE + 'performance_review/unassign_reviewer', { id: id }, () => location.reload());
    });
}
$(document).on('change', '.reviewer-active-toggle', function () {
    const $toggle = $(this);
    const isActive = $toggle.is(':checked') ? 1 : 0;
    $toggle.prop('disabled', true);
    $.post(BASE + 'performance_review/toggle_reviewer_assignment', {
        id: $toggle.data('id'),
        is_active: isActive
    }, function (res) {
        if (res.indexOf('success') !== -1) {
            location.reload();
            return;
        }
        $('#prAlert').html(res);
        $toggle.prop('checked', !isActive).prop('disabled', false);
    }).fail(function () {
        $toggle.prop('checked', !isActive).prop('disabled', false);
    });
});
function prAction(action, id) {
    const msg = action === 'publish' ? 'Publish hasil penilaian ke karyawan?' : 'Batalkan publikasi?';
    Swal.fire({ title: msg, icon: 'question', showCancelButton: true, confirmButtonColor: '#52c41a' }).then(r => {
        if (r.isConfirmed) $.post(BASE + 'performance_review/' + action, { id: id }, function (res) {
            if (res.indexOf('success') !== -1) location.reload(); else $('#prAlert').html(res);
        });
    });
}
</script>
