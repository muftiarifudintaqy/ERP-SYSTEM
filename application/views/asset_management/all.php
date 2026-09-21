<style>
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
    }

    .table tbody td {
        padding: 12px 8px !important;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        border-bottom: 1px solid #f0f0f0;
    }

    .table-hover tbody tr:hover {
        background-color: #fafafa;
    }

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

    .pagination {
        margin-top: 16px;
        justify-content: flex-end;
    }

    .page-item {
        margin-right: 8px;
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

    .form-control, .form-select {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-control:focus, .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

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

    .editable {
        cursor: pointer;
        position: relative;
    }

    .editable .view-mode {
        display: inline-block;
        min-height: 20px;
    }

    .editable .edit-mode {
        display: none;
    }

    .editable.editing .view-mode {
        display: none;
    }

    .editable.editing .edit-mode {
        display: block;
    }
</style>

<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Asset Management</h5>
                <div class="d-flex gap-2">
                    <button type="button" id="addRow" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Aset
                    </button>
                    <a href="<?= base_url() ?>/asset_management/loans" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left-right me-1"></i> Peminjaman Aset
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form action="<?= base_url() ?>/asset_management" method="GET" class="mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <select name="keyword_category" class="form-select">
                            <option value="Nama" <?= ($keyword_category == 'Nama') ? 'selected' : '' ?>>Nama</option>
                            <option value="Catatan" <?= ($keyword_category == 'Catatan') ? 'selected' : '' ?>>Catatan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="keyword" class="form-control" placeholder="Cari..." value="<?= $_GET['keyword'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i> Cari
                        </button>
                    </div>
                </div>
            </form>

            <?= $notif ?>

            <div class="table-responsive">
                <table class="table table-hover" id="assetTable">
                    <thead>
                        <tr>
                            <th style="width: 60px;">No</th>
                            <th>Nama Aset</th>
                            <th>Catatan</th>
                            <th style="width: 120px;" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="asset-tbody"></tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                <?= $pagination ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
            function loadAssetData() {
                $.ajax({
                    url: "<?= base_url() ?>/asset_management/item<?= $param ?>",
                    beforeSend: function() {
                        $('#asset-tbody').html('<tr><td colspan="4" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
                    },
                    success: function(data) {
                        $('#asset-tbody').html(data);
                    },
                    error: function() {
                        $('#asset-tbody').html('<tr><td colspan="4" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
                    }
                });
            }

    $(document).ready(function() {
        loadAssetData();
    });

            $('#addRow').on('click', function() {
                $.post("<?= base_url() ?>/asset_management/save", {}, function(res) {
                    if (res.status === 'success') {
                        loadAssetData();
                    } else {
                        Swal.fire('Gagal', res.message || 'Gagal menambahkan data.', 'error');
                    }
                }, 'json');
            });

            $('#assetTable tbody').on('click', '.editable:not(.editing)', function() {
                const cell = $(this);
                cell.addClass('editing');
                const input = cell.find('.edit-mode :input').first();
                input.focus().select();
            });

            $('#assetTable tbody').on('blur', '.editable .edit-mode :input', function() {
                const cell = $(this).closest('.editable');
                saveCell(cell);
            }).on('keypress', '.editable .edit-mode :input', function(e) {
                if (e.which === 13) {
                    const cell = $(this).closest('.editable');
                    saveCell(cell);
                    return false;
                }
            });

            function saveCell(cell) {
                const field = cell.data('field');
                const input = cell.find('.edit-mode :input');
                const value = input.val();
                const row = cell.closest('tr');
                const id = row.data('id');

                cell.find('.view-mode').text(value || '-');

                if (!id || id === 0 || id === "0") {
                    cell.removeClass('editing');
                    return;
                }

                $.post("<?= base_url() ?>/asset_management/update_field", {
                    id: id,
                    field: field,
                    value: value
                }, function(res) {
                    if (res.status === 'success') {
                        cell.removeClass('editing');
                    } else {
                        Swal.fire('Gagal Menyimpan', res.message || 'Gagal menyimpan perubahan.', 'error');
                    }
                }, 'json');
            }

            $('#assetTable tbody').on('click', '.delete-row', function() {
                const row = $(this).closest('tr');
                const id = row.data('id');

                Swal.fire({
                    title: 'Hapus Data',
                    text: "Apakah Anda yakin ingin menghapus data ini?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Hapus Data',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    customClass: {
                        confirmButton: 'btn btn-primary me-2',
                        cancelButton: 'btn btn-secondary'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("<?= base_url() ?>/asset_management/delete_row", { id: id }, function(res) {
                            if (res.status === 'success') {
                                loadAssetData();
                            } else {
                                Swal.fire('Gagal', res.message || 'Gagal menghapus data.', 'error');
                            }
                        }, 'json');
                    }
                });
            });
        </script>
