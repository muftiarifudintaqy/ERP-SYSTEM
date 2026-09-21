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
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0,0,0,0.85);"><i class="bi bi-file-earmark-text me-2"></i>Riwayat Kontrak Karyawan</h5>
            </div>
        </div>
        <div class="card-body">
            <!-- Search -->
            <form id="searchForm" class="row g-2 mb-3" onsubmit="return false;">
                <div class="col-md-5">
                    <input type="text" id="keyword" class="form-control" placeholder="Cari nama / email karyawan..." value="<?= htmlspecialchars($keyword) ?>">
                </div>
                <div class="col-md-3">
                    <select id="status_filter" class="form-select">
                        <option value="">-- Semua Status Kontrak --</option>
                        <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="expiring" <?= $status_filter == 'expiring' ? 'selected' : '' ?>>Akan Berakhir</option>
                        <option value="expired" <?= $status_filter == 'expired' ? 'selected' : '' ?>>Berakhir</option>
                        <option value="terminated" <?= $status_filter == 'terminated' ? 'selected' : '' ?>>Diberhentikan</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" onclick="load_data()"><i class="bi bi-search me-1"></i>Cari</button>
                </div>
            </form>

            <!-- Bulk action bar -->
            <?php if (!empty($can_delete)): ?>
            <div id="bulkBar" class="alert alert-info d-none align-items-center justify-content-between" style="display:flex;">
                <span><span id="selectedCount">0</span> item dipilih</span>
                <button class="btn btn-danger btn-sm" onclick="bulkDelete()"><i class="bi bi-trash me-1"></i>Hapus Terpilih</button>
            </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="40" class="text-center"><input type="checkbox" id="checkAll" class="form-check-input"></th>
                            <th width="40">#</th>
                            <th>Karyawan</th>
                            <th>Kontrak Aktif</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th class="text-center">Riwayat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="contract-tbody">
                        <tr><td colspan="8" class="text-center text-muted py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
                    </tbody>
                </table>
            </div>
            <div id="pagination-area"></div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="popupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" id="popupModalContent"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE = '<?= base_url() ?>';

function load_data(page = 1) {
    const keyword = $('#keyword').val();
    const status_filter = $('#status_filter').val();
    $('#contract-tbody').html('<tr><td colspan="8" class="text-center text-muted py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');
    $.get(BASE + 'contract/item', { keyword, status_filter, page }, function (html) {
        $('#contract-tbody').html(html);
        $('#checkAll').prop('checked', false);
        updateBulk();
    });
}

function removeContract(id) {
    $('#popupModal .modal-dialog').removeClass('modal-lg');
    $.get(BASE + 'contract/remove', { id: id }, function (html) {
        $('#popupModalContent').html(html);
        new bootstrap.Modal(document.getElementById('popupModal')).show();
    });
}

function openContractModal(userId) {
    $('#popupModal .modal-dialog').addClass('modal-lg');
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
                        const modalEl = document.getElementById('popupModal');
                        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modal.hide();
                        load_data();
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

function updateBulk() {
    const n = $('.row-check:checked').length;
    $('#selectedCount').text(n);
    if (n > 0) $('#bulkBar').removeClass('d-none'); else $('#bulkBar').addClass('d-none');
}

function bulkDelete() {
    const ids = $('.row-check:checked').map(function () { return $(this).val(); }).get();
    if (!ids.length) return;
    Swal.fire({ title: 'Hapus ' + ids.length + ' kontrak?', text: 'Tindakan ini permanen.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ff4d4f', confirmButtonText: 'Ya, hapus' })
        .then((r) => {
            if (r.isConfirmed) {
                $.post(BASE + 'contract/bulk_delete', { ids: ids }, function (res) {
                    Swal.fire('Selesai', res.message, res.success ? 'success' : 'error');
                    load_data();
                }, 'json');
            }
        });
}

$(document).ready(function () {
    load_data();
    $('#keyword').on('keypress', function (e) { if (e.which === 13) load_data(); });
    $('#status_filter').on('change', function () { load_data(); });
    $('#checkAll').on('change', function () { $('.row-check').prop('checked', this.checked); updateBulk(); });
    $(document).on('change', '.row-check', updateBulk);
});
</script>
