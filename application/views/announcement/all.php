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
        vertical-align: middle;
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
    }

    .card-body {
        padding: 16px;
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
    }

    .status-badge {
        font-size: 12px;
        padding: 3px 10px;
        border-radius: 12px;
        font-weight: 500;
    }

    .status-active {
        background-color: #f6ffed;
        color: #52c41a;
        border: 1px solid #b7eb8f;
    }

    .status-inactive {
        background-color: #fff2f0;
        color: #ff4d4f;
        border: 1px solid #ffccc7;
    }
</style>

<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Announcement Management</h5>
                <a href="<?= base_url() ?>announcement/create_page" class="btn btn-primary">
                    <i class="bi bi-plus me-1"></i> Tambah Announcement
                </a>
            </div>
        </div>
        <div class="card-body">
            <form action="" class="search-form mb-3">
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="keyword" class="form-control" placeholder="Cari judul / deskripsi..."
                                value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '' ?>"
                                style="border: 1px solid #d9d9d9; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
                            <button class="btn btn-primary" type="submit" style="border-radius: 2px; height: 32px; padding: 0 15px;">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php if (!empty($notif)): ?>
                    <div class="alert alert-info mt-2" style="display: flex; align-items: center; background-color:#e6f7ff; border-color:#91d5ff;">
                        <i class="bi bi-info-circle me-2"></i>
                        <span><?= strip_tags($notif) ?></span>
                    </div>
                <?php endif; ?>
            </form>

            <div class="table-responsive">
                <table class="table table-hover" id="announcement-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Banner</th>
                            <th>Judul</th>
                            <th>Periode Tayang</th>
                            <th>Frekuensi</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="announcement-tbody">
                        <!-- Loaded via AJAX -->
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <?= $pagination ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="popupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content"></div>
    </div>
</div>

<script>
    function loadAnnouncementData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>announcement/item<?= $param ?>",
            beforeSend: function() {
                $('#announcement-tbody').html('<tr><td colspan="7" class="text-center"><div class="spinner-border text-primary" role="status"></div></td></tr>');
            },
            success: function(data) {
                $('#announcement-tbody').html(data);
            },
            error: function() {
                $('#announcement-tbody').html('<tr><td colspan="7" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
            }
        });
    }

    function load_data() {
        loadAnnouncementData();
    }

    $(document).ready(function() {
        loadAnnouncementData();
    });

    function removeAnnouncement(id) {
        Swal.fire({
            title: 'Hapus Announcement',
            text: 'Apakah Anda yakin ingin menghapus announcement ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4d4f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= base_url() ?>announcement/delete",
                    data: { id: id },
                    success: function(response) {
                        if (response.indexOf("success") != -1) {
                            Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success').then(() => loadAnnouncementData());
                        } else {
                            Swal.fire('Error!', 'Gagal menghapus data.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Terjadi kesalahan saat menghapus data.', 'error');
                    }
                });
            }
        });
    }
</script>
