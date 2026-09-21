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
</style>

<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Peminjaman Aset</h5>
                <div class="d-flex gap-2">
                    <a href="<?= base_url() ?>/asset_management/loan_create_page" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Peminjaman
                    </a>
                    <a href="<?= base_url() ?>/asset_management" class="btn btn-outline-secondary">
                        <i class="bi bi-box-seam me-1"></i> Data Aset
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form action="<?= base_url() ?>/asset_management/loans" method="GET" class="mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <select name="keyword_category" class="form-select">
                            <option value="Aset" <?= ($keyword_category == 'Aset') ? 'selected' : '' ?>>Aset</option>
                            <option value="Peminjam" <?= ($keyword_category == 'Peminjam') ? 'selected' : '' ?>>Peminjam</option>
                            <option value="Status" <?= ($keyword_category == 'Status') ? 'selected' : '' ?>>Status</option>
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
                <table class="table table-hover" id="loan-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">No</th>
                            <th>Aset</th>
                            <th style="width: 160px;">Peminjam</th>
                            <th style="width: 140px;">Tgl Pinjam</th>
                            <th style="width: 140px;">Tgl Kembali</th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 120px;" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="loan-tbody"></tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                <?= $pagination ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function loadLoanData() {
        $.ajax({
            url: "<?= base_url() ?>/asset_management/loan_item<?= $param ?>",
            beforeSend: function() {
                $('#loan-tbody').html('<tr><td colspan="7" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
            },
            success: function(data) {
                $('#loan-tbody').html(data);
            },
            error: function() {
                $('#loan-tbody').html('<tr><td colspan="7" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
            }
        });
    }

    $(document).ready(function() {
        loadLoanData();
    });

    function removeLoan(id) {
        $.get("<?= base_url() ?>/asset_management/loan_remove?id=" + id, function(data) {
            $("#popupModal .modal-content").html(data);
            $("#popupModal").modal('show');
        });
    }
</script>
