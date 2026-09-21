<style>
    .pr-shell { max-width: 1480px; margin: 0 auto; }
    .pr-page-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; }
    .pr-title { font-size: 20px; font-weight: 600; color: rgba(0,0,0,0.85); margin: 0; line-height: 1.4; }
    .pr-subtitle { color: rgba(0,0,0,0.45); margin: 4px 0 0; font-size: 14px; }
    .pr-card { border: 1px solid #f0f0f0; border-radius: 2px; box-shadow: 0 2px 8px rgba(0,0,0,0.09); }
    .pr-tabs { border-bottom: 1px solid #f0f0f0; gap: 0; }
    .pr-tabs .nav-link { border: 0; border-bottom: 2px solid transparent; border-radius: 0; color: rgba(0,0,0,0.65); font-weight: 500; padding: 12px 16px; background: transparent; }
    .pr-tabs .nav-link:hover { color: #40a9ff; border-bottom-color: #91d5ff; }
    .pr-tabs .nav-link.active { background: transparent; border-bottom-color: #1890ff; color: #1890ff; }
    .pr-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin: 18px 0 12px; }
    .pr-filter { display: flex; gap: 8px; flex-wrap: nowrap; align-items: stretch; width: auto; }
    .pr-filter > * { margin: 0 !important; box-sizing: border-box; }
    .pr-filter .form-control { width: 280px; min-width: 280px; max-width: 280px; }
    .pr-filter .form-select { width: 180px; min-width: 180px; max-width: 180px; }
    .pr-filter .btn { flex: 0 0 auto; width: 40px; padding-left: 0; padding-right: 0; display: inline-flex; align-items: center; justify-content: center; }
    .pr-filter .form-control,
    .pr-filter .form-select,
    .pr-filter .btn { border-radius: 2px; height: 40px !important; min-height: 40px; font-size: 14px; line-height: 1.5; color: rgba(0,0,0,0.65); }
    .pr-filter .form-control,
    .pr-filter .form-select { border-color: #d9d9d9; }
    .pr-filter .form-control:focus,
    .pr-filter .form-select:focus { border-color: #40a9ff; box-shadow: 0 0 0 2px rgba(24,144,255,0.2); }
    .pr-table { border-collapse: separate; border-spacing: 0; border: 1px solid #f0f0f0; border-radius: 2px; overflow: hidden; margin-bottom: 0; }
    .pr-table thead th { background: #fafafa; border: 0; border-bottom: 1px solid #f0f0f0; color: rgba(0,0,0,0.85); font-size: 14px; font-weight: 500; padding: 12px 8px; vertical-align: middle; }
    .pr-table tbody td { background: #fff; border: 0; border-bottom: 1px solid #f0f0f0; color: rgba(0,0,0,0.65); padding: 12px 8px !important; vertical-align: middle; transition: background .3s; }
    .pr-table tbody tr:hover td { background: #fafafa; }
    .pr-table tbody tr:last-child td { border-bottom: 0; }
    .pr-table .pr-reviewer-cell,
    .pr-table .pr-status-cell,
    .pr-table .pr-action-cell { text-align: center !important; }
    .pr-action-group { display: inline-flex; justify-content: center; align-items: center; gap: 6px; flex-wrap: wrap; vertical-align: middle; }
    .pr-shell .btn,
    .pr-action-group .btn,
    .pr-icon-btn { border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px; line-height: 1.5; transition: all .3s cubic-bezier(.645,.045,.355,1); }
    .pr-action-group .btn-sm { height: 28px; padding: 2px 10px; font-size: 12px; }
    .pr-shell .btn-primary,
    .pr-action-group .btn-primary { background: #1890ff; border-color: #1890ff; }
    .pr-shell .btn-primary:hover,
    .pr-action-group .btn-primary:hover { background: #40a9ff; border-color: #40a9ff; }
    .pr-shell .btn-outline-primary,
    .pr-action-group .btn-outline-primary { color: #1890ff; border-color: #1890ff; background: #fff; }
    .pr-shell .btn-outline-primary:hover,
    .pr-action-group .btn-outline-primary:hover { color: #40a9ff; border-color: #40a9ff; background: #e6f7ff; }
    .pr-action-group .btn-success { background: #52c41a; border-color: #52c41a; }
    .pr-action-group .btn-success:hover { background: #73d13d; border-color: #73d13d; }
    .pr-action-group .btn-outline-danger { color: #ff4d4f; border-color: #ff4d4f; background: #fff; }
    .pr-action-group .btn-outline-danger:hover { color: #ff7875; border-color: #ff7875; background: #fff2f0; }
    .pr-icon-btn { width: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #d9d9d9; background: #fff; color: rgba(0,0,0,0.65); }
    .pr-icon-btn:hover { color: #ff4d4f; border-color: #ff4d4f; background: #fff2f0; text-decoration: none; }
    .pr-empty { color: rgba(0,0,0,0.45); padding: 28px 0; }
    .pr-shell .badge,
    .pr-action-group .badge { border-radius: 2px; font-weight: 500; min-height: 24px; padding: 3px 8px; display: inline-flex; align-items: center; justify-content: center; line-height: 1; vertical-align: middle; }
    .pr-reviewer-labels { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; max-width: 260px; margin: 0 auto; }
    .pr-reviewer-label { border: 1px solid #91d5ff; background: #e6f7ff; color: #096dd9; border-radius: 2px; padding: 3px 8px; font-size: 12px; line-height: 1.3; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; max-width: 220px; }
    .pr-reviewer-label:hover { color: #0050b3; border-color: #40a9ff; background: #bae7ff; text-decoration: none; }
    .pr-reviewer-empty { color: rgba(0,0,0,0.45); font-size: 12px; display: block; }
    @media (max-width: 768px) {
        .pr-page-head { display: block; }
        .pr-filter, .pr-filter .form-control, .pr-filter .form-select { width: 100%; min-width: 0; }
        .pr-toolbar > a, .pr-toolbar > .btn { width: 100%; }
    }
</style>
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
    <div class="pr-shell">
        <div class="pr-page-head">
            <a href="<?= base_url() ?>performance_review/my_assignments" class="btn btn-outline-primary">
                <i class="bi bi-person-check me-1"></i> Penilaian Saya
            </a>
        </div>

        <div class="card pr-card">
            <div class="card-body">
            <ul class="nav nav-tabs pr-tabs" id="prTabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-reviews" type="button"><i class="bi bi-people me-1"></i>Penilaian Karyawan</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-templates" type="button"><i class="bi bi-files me-1"></i>Template Penilaian</button></li>
            </ul>

            <div class="tab-content pt-3">
                <!-- REVIEWS -->
                <div class="tab-pane fade show active" id="tab-reviews">
                    <div class="pr-toolbar">
                        <div class="pr-filter">
                            <input type="text" id="r_keyword" class="form-control" placeholder="Cari karyawan / template...">
                            <select id="r_status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="draft">Draft</option>
                                <option value="in_progress">Berlangsung</option>
                                <option value="completed">Selesai</option>
                                <option value="published">Terbit</option>
                            </select>
                            <button class="btn btn-outline-primary" onclick="loadReviews()"><i class="bi bi-search"></i></button>
                        </div>
                        <?php if (!empty($can_create)): ?>
                        <a href="<?= base_url() ?>performance_review/review_create_page" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Penilaian</a>
                        <?php endif; ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table pr-table">
                            <thead><tr>
                                <th>#</th><th>Karyawan</th><th>Template</th><th>Periode</th><th class="pr-reviewer-cell">Reviewer</th><th class="pr-status-cell">Status</th><th class="pr-action-cell">Aksi</th>
                            </tr></thead>
                            <tbody id="reviews-tbody"><tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr></tbody>
                        </table>
                    </div>
                </div>

                <!-- TEMPLATES -->
                <div class="tab-pane fade" id="tab-templates">
                    <div class="pr-toolbar">
                        <div class="pr-filter">
                            <input type="text" id="t_keyword" class="form-control" placeholder="Cari template...">
                            <button class="btn btn-outline-primary" onclick="loadTemplates()"><i class="bi bi-search"></i></button>
                        </div>
                        <?php if (!empty($can_create)): ?>
                        <a href="<?= base_url() ?>performance_review/template_create_page" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Template</a>
                        <?php endif; ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table pr-table">
                            <thead><tr>
                                <th>#</th><th>Nama Template</th><th>Tipe</th><th>Indikator</th><th>Status</th><th class="text-end">Aksi</th>
                            </tr></thead>
                            <tbody id="templates-tbody"><tr><td colspan="6" class="text-center py-4 text-muted">Klik tab untuk memuat</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<div class="modal fade" id="popupModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" id="popupModalContent"></div></div></div>

<?php if (!empty($can_edit)): ?>
<?php $roleLabel = ['self' => 'Self (Karyawan)', 'leader' => 'Leader', 'hr' => 'HR', 'peer' => 'Peer']; ?>
<div class="modal fade" id="assignReviewerModal" tabindex="-1" aria-labelledby="assignReviewerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="assignReviewerForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignReviewerModalLabel"><i class="bi bi-person-plus me-1"></i>Tambah Reviewer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div id="assignReviewerAlert"></div>
                    <input type="hidden" name="review_id" id="assign_review_id">
                    <p class="text-muted small mb-3" id="assignReviewLabel"></p>
                    <div class="mb-3">
                        <label class="form-label">Reviewer</label>
                        <select name="reviewer_id" id="assign_reviewer_id" class="form-select select2" style="width:100%;">
                            <option value="">Pilih karyawan</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= $employee['id'] ?>"><?= htmlspecialchars($employee['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Peran</label>
                        <select name="reviewer_role" id="assign_reviewer_role" class="form-select">
                            <?php foreach ($reviewer_roles as $role): ?>
                                <option value="<?= $role ?>"><?= htmlspecialchars($roleLabel[$role] ?? $role) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label mb-0">Reviewer Ter-assign</label>
                            <span class="small text-muted">Toggle untuk tampil/sembunyi di Penilaian Saya</span>
                        </div>
                        <div id="assignReviewerList" class="small text-muted">Memuat reviewer...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Tambah</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE = '<?= base_url() ?>';
function loadReviews() {
    $('#reviews-tbody').html('<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>');
    $.get(BASE + 'performance_review/reviews', { keyword: $('#r_keyword').val(), status_filter: $('#r_status').val() }, function (h) { $('#reviews-tbody').html(h); });
}
function loadTemplates() {
    $('#templates-tbody').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>');
    $.get(BASE + 'performance_review/templates', { keyword: $('#t_keyword').val() }, function (h) { $('#templates-tbody').html(h); });
}
function confirmDelete(url, id, msg) {
    Swal.fire({ title: 'Hapus?', text: msg, icon: 'warning', showCancelButton: true, confirmButtonColor: '#ff4d4f', confirmButtonText: 'Ya, hapus' })
        .then(r => { if (r.isConfirmed) $.post(BASE + url, { id: id }, function (res) {
            const ok = res.indexOf('success') !== -1;
            Swal.fire('', ok ? 'Berhasil dihapus.' : 'Gagal menghapus (mungkin sedang dipakai).', ok ? 'success' : 'error');
            loadReviews(); loadTemplates();
        }); });
}
function prAction(action, id) {
    const msg = action === 'publish' ? 'Publish hasil penilaian ke karyawan?' : 'Batalkan publikasi penilaian ini?';
    Swal.fire({ title: msg, icon: 'question', showCancelButton: true, confirmButtonColor: action === 'publish' ? '#16a34a' : '#ef4444', confirmButtonText: 'Ya' })
        .then(r => { if (r.isConfirmed) $.post(BASE + 'performance_review/' + action, { id: id }, function (res) {
            const ok = res.indexOf('success') !== -1;
            Swal.fire('', ok ? 'Perubahan tersimpan.' : $(res).text() || 'Aksi gagal.', ok ? 'success' : 'warning');
            loadReviews();
        }); });
}
function openReviewerModal(reviewId, label) {
    $('#assign_review_id').val(reviewId);
    $('#assignReviewLabel').text(label);
    $('#assignReviewerAlert').html('');
    $('#assignReviewerList').html('Memuat reviewer...');
    $('#assign_reviewer_id').val('').trigger('change');
    $('#assign_reviewer_role').val('peer');
    $('#assignReviewerModal').modal('show');
    loadReviewerAssignments(reviewId);
}
function loadReviewerAssignments(reviewId) {
    $.get(BASE + 'performance_review/reviewer_assignments', { review_id: reviewId }, function (html) {
        $('#assignReviewerList').html(html);
    });
}
$(function () {
    loadReviews();
    $('#prTabs button[data-bs-target="#tab-templates"]').on('shown.bs.tab', loadTemplates);
    $('#r_keyword').on('keypress', e => { if (e.which === 13) loadReviews(); });
    $('#r_status').on('change', loadReviews);
    $('#t_keyword').on('keypress', e => { if (e.which === 13) loadTemplates(); });
    if ($.fn.select2 && $('#assign_reviewer_id').length) {
        $('#assign_reviewer_id').select2({
            dropdownParent: $('#assignReviewerModal'),
            width: '100%',
            placeholder: 'Pilih karyawan'
        });
    }
    $('#assignReviewerForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type=submit]');
        const reviewerId = $('#assign_reviewer_id').val();
        if (!reviewerId) {
            $('#assignReviewerAlert').html('<div class="alert alert-warning">Pilih reviewer dulu.</div>');
            return;
        }
        $btn.prop('disabled', true);
        $.post(BASE + 'performance_review/assign_reviewer', $(this).serialize(), function (res) {
            const ok = res.indexOf('success') !== -1;
            $('#assignReviewerAlert').html(res);
            $btn.prop('disabled', false);
            if (ok) {
                setTimeout(function () {
                    $('#assign_reviewer_id').val('').trigger('change');
                    loadReviewerAssignments($('#assign_review_id').val());
                    loadReviews();
                }, 500);
            }
        });
    });
    $(document).on('change', '.pr-assignment-toggle', function () {
        const $toggle = $(this);
        const isActive = $toggle.is(':checked') ? 1 : 0;
        $toggle.prop('disabled', true);
        $.post(BASE + 'performance_review/toggle_reviewer_assignment', {
            id: $toggle.data('id'),
            is_active: isActive
        }, function (res) {
            if (res.indexOf('success') === -1) {
                $('#assignReviewerAlert').html(res);
                $toggle.prop('checked', !isActive).prop('disabled', false);
                return;
            }
            loadReviewerAssignments($('#assign_review_id').val());
            loadReviews();
        }).fail(function () {
            $toggle.prop('checked', !isActive).prop('disabled', false);
        });
    });
});
</script>
