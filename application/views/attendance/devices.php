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
        min-height: 56px;
    }

    .btn,
    .form-control,
    .form-select {
        border-radius: 2px;
        font-size: 14px;
        height: 32px;
    }

    .device-page .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        line-height: 1;
        padding: 0 14px;
        vertical-align: middle;
    }

    .device-page .btn i {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        margin: 0 !important;
    }

    .device-page .input-group .btn {
        width: 40px;
        padding: 0;
    }

    .device-page .btn-sm {
        width: 32px;
        padding: 0;
    }

    .badge {
        font-size: 12px;
        height: 22px;
        padding: 0 8px;
        line-height: 22px;
        border-radius: 2px;
        font-weight: normal;
    }

    .device-token {
        display: inline-block;
        max-width: 190px;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: bottom;
        white-space: nowrap;
    }

    .user-agent {
        max-width: 360px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<div class="container-fluid py-3 device-page">
    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Manajemen Browser Absensi</h5>
                <div class="d-flex gap-2">
                    <a href="<?= base_url() ?>attendance" class="btn btn-outline-secondary">
                        <i class="bi bi-calendar-check"></i>
                    </a>
                    <a href="<?= base_url() ?>attendance/devices?status_filter=pending" class="btn btn-warning">
                        <i class="bi bi-hourglass-split"></i><span>Pending <?= (int) $pending_count ?></span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form action="" class="search-form">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="keyword" class="form-control" placeholder="Cari nama, token, atau browser..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status_filter" class="form-select" onchange="this.form.submit()" title="Filter status">
                            <option value="">Semua Status</option>
                            <option value="pending" <?= (($status_filter ?? '') === 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= (($status_filter ?? '') === 'approved') ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= (($status_filter ?? '') === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                </div>

                <?php if (!empty($notif)): ?>
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="bi bi-info-circle me-2"></i>
                        <span><?= strip_tags($notif) ?></span>
                    </div>
                <?php endif; ?>
            </form>

            <div class="table-responsive">
                <table class="table table-hover" id="attendance-device-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Karyawan</th>
                            <th>Browser</th>
                            <th>Status</th>
                            <th>Terakhir Dipakai</th>
                            <th>Approval</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="attendance-device-tbody"></tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <?= $pagination ?>
            </div>
        </div>
    </div>
</div>

<script>
    function loadAttendanceDevices() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>attendance/device_item<?= $param ?>",
            beforeSend: function() {
                $('#attendance-device-tbody').html('<tr><td colspan="7" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
            },
            success: function(data) {
                $('#attendance-device-tbody').html(data);
            },
            error: function() {
                $('#attendance-device-tbody').html('<tr><td colspan="7" class="text-center text-danger">Gagal memuat data perangkat.</td></tr>');
            }
        });
    }

    function setDeviceStatus(id, action) {
        var isApprove = action === 'approve_device';
        Swal.fire({
            title: isApprove ? 'Approve browser ini?' : 'Tolak browser ini?',
            text: isApprove ? 'Browser approved lama milik user ini akan otomatis dinonaktifkan.' : 'User tidak dapat absensi dari browser ini.',
            icon: isApprove ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonText: isApprove ? 'Approve' : 'Tolak',
            cancelButtonText: 'Batal',
            confirmButtonColor: isApprove ? '#52c41a' : '#ff4d4f'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            $.ajax({
                type: 'POST',
                url: "<?= base_url() ?>attendance/" + action,
                dataType: 'json',
                data: { id: id },
                success: function(res) {
                    Swal.fire(res.success ? 'Berhasil' : 'Gagal', res.message || 'Aksi gagal.', res.success ? 'success' : 'error');
                    loadAttendanceDevices();
                },
                error: function(xhr) {
                    var message = 'Terjadi kesalahan saat menyimpan status perangkat.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    Swal.fire('Gagal', message, 'error');
                }
            });
        });
    }

    $(document).ready(function() {
        loadAttendanceDevices();
    });
</script>

<script>
function hapusDevice(id, nama) {
    Swal.fire({
        icon: 'warning',
        title: 'Yakin hapus perangkat ini?',
        html: 'Perangkat milik <b>' + nama + '</b> akan dihapus permanen.<br><small class="text-muted">Dia akan terdaftar ulang otomatis saat membuka halaman absensi lagi.</small>',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Tidak',
        confirmButtonColor: '#ff4d4f',
        reverseButtons: true
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post("<?= base_url() ?>attendance/delete_device", { id: id }, function (x) {
            var o = (typeof x === 'string') ? JSON.parse(x) : x;
            if (o.success) {
                Swal.fire({
                    icon: 'success', title: 'Berhasil Dihapus',
                    text: 'Perangkat sudah dihapus dari daftar.',
                    timer: 1700, showConfirmButton: false, timerProgressBar: true
                });
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: o.message, background: '#ffffff' });
            }
        });
    });
}
</script>
