<style>
    /* Table Responsive for Custom Scrollbar */
    .table-responsive[data-custom-scrollbar] {
        overflow-x: auto;
        white-space: nowrap;
    }

    .table-responsive[data-custom-scrollbar] table {
        min-width: max-content;
    }
    
    /* Sticky Left Columns */
    .table thead th:nth-child(1),
    .table tbody td:nth-child(1) {
        position: sticky;
        left: 0;
        background: #fafafa;
        z-index: 6;
        box-shadow: 2px 0 4px rgba(0,0,0,0.05);
    }
    
    .table thead th:nth-child(2),
    .table tbody td:nth-child(2) {
        position: sticky;
        left: 50px;
        background: #fafafa;
        z-index: 6;
        box-shadow: 2px 0 4px rgba(0,0,0,0.05);
    }
    
    .table thead th:nth-child(3),
    .table tbody td:nth-child(3) {
        position: sticky;
        left: 170px;
        background: #fafafa;
        z-index: 6;
        box-shadow: 2px 0 4px rgba(0,0,0,0.05);
    }
    
    .table tbody td:nth-child(1),
    .table tbody td:nth-child(2),
    .table tbody td:nth-child(3) {
        background: #fff;
    }
    
    .table thead th:nth-child(1),
    .table thead th:nth-child(2),
    .table thead th:nth-child(3) {
        z-index: 11;
    }
    
    /* Action Column Sticky Right */
    .table thead th.action-col,
    .table tbody td.action-col {
        position: sticky;
        right: 0;
        background: #fff;
        z-index: 5;
        box-shadow: -2px 0 8px rgba(0,0,0,0.05);
    }
    
    .table thead th.action-col {
        z-index: 10;
        background: #fafafa;
    }
    
    /* Action Buttons Hover Effects */
    .btn-reject:hover {
        background: #ff4d4f !important;
        color: white !important;
        border-color: #ff4d4f !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(255, 77, 79, 0.3);
    }
    
    .btn-notes:hover {
        background: #1890ff !important;
        color: white !important;
        border-color: #1890ff !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(24, 144, 255, 0.3);
    }
    
    .btn-reject, .btn-notes {
        transition: all 0.2s ease;
    }
    
    /* Tippy Theme */
    .tippy-box[data-theme~='recruitment-notes'] {
        background-color: #ffffff;
        color: #333333;
        font-size: 13px;
        border-radius: 6px;
        padding: 8px 12px;
        max-width: 350px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: 1px solid #e0e0e0;
    }
    
    .tippy-box[data-theme~='recruitment-notes'][data-placement^='top'] > .tippy-arrow::before {
        border-top-color: #ffffff;
    }
    
    .tippy-box[data-theme~='recruitment-notes'][data-placement^='bottom'] > .tippy-arrow::before {
        border-bottom-color: #ffffff;
    }
    
    .tippy-box[data-theme~='recruitment-notes'][data-placement^='left'] > .tippy-arrow::before {
        border-left-color: #ffffff;
    }
    
    .tippy-box[data-theme~='recruitment-notes'][data-placement^='right'] > .tippy-arrow::before {
        border-right-color: #ffffff;
    }
    
    /* Tab styling enhancements */
    .recruitment-tabs .nav-link {
        transition: all 0.3s ease;
    }
    
    .recruitment-tabs .nav-link:hover:not(.active) {
        background: #f5f5f5;
        transform: translateY(-2px);
    }

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

    .history-card {
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .history-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
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

    /* Status Badges */
    .badge-primary {
        background-color: #1890ff;
        color: white;
    }
    .badge-success {
        background-color: #52c41a;
        color: white;
    }
    .badge-warning {
        background-color: #faad14;
        color: white;
    }
    .badge-danger {
        background-color: #f5222d;
        color: white;
    }
    .badge-default {
        background-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.65);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }
    .fc table {
        font-size: 0 !important;
    }

    .fc table tr th,
    .fc table tr td {
        padding: 0px !important;
        border-radius: 0 !important;
        background: transparent !important;
    }

    .fc table tr td:first-child,
    .fc table tr td:last-child,
    .fc table tr th:first-child,
    .fc table tr th:last-child {
        border-radius: 0 !important;
    }

    table tr td {
        background: #fff !important;
        padding: 0px 0px !important;
    }

    table tr:first-child th:last-child {
        padding: 0px 0px !important;
    }

    table tr:first-child td:first-child {
        padding: 0px 0px !important;
    }

    /* === Container Layout === */
    #calendar-container {
        display: flex;
        flex-direction: column;
        height: 100%;
        width: 100%;
        flex: 2;
        position: relative;
    }

    #calendar {
        
        overflow: hidden;
    }

    .fc-daygrid-day.selected-date {
        background-color: #fae0e9ff !important;
        border: 2px solid #ffc8daff !important;
    }

    #event-details {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        background-color: white;
        border-radius: 10px;
    }

    #event-details h4 {
        font-size: 1.3em;
        margin-bottom: 1px;
    }

    #event-details p {
        font-size: 0.9em;
        line-height: 1.4;
    }

    /* === Calendar Toolbar (Month/Year) === */
    .fc-toolbar {
        margin-bottom: 5px !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding: 10px 0;
    }

    .fc-toolbar-title {
        font-size: 1.1em !important;
        margin: 0 15px !important;
        order: 2;
    }

    .fc-prev-button {
        order: 1;
        background-color: transparent !important;
        color: #333 !important;
        border: none !important;
        box-shadow: none !important;
    }

    .fc-next-button {
        order: 3;
        background-color: transparent !important;
        color: #333 !important;
        border: none !important;
        box-shadow: none !important;
    }

    .fc-button {
        padding: 5px 10px !important;
        font-size: 0.9em !important;
    }

    /* Light theme adjustments */
    .fc-theme-standard {
        background-color: white !important;
    }

    .fc-theme-standard .fc-scrollgrid {
        border: 1px solid #e0e0e0 !important;
    }

    .fc-daygrid-day {
        background-color: white !important;
        border: 1px solid #e0e0e0 !important;
    }

    .fc-col-header-cell {
        background-color: #f5f5f5 !important;
    }

    .fc-daygrid-day-number {
        color: #333 !important;
    }

    /* === Day Header (Min-Sab) === */
    .fc-theme-standard .fc-col-header {
        height: 24px !important;
    }

    .fc-col-header-cell {
        padding: 0 !important;
        margin: 0 !important;
        height: 10px !important;
        vertical-align: top !important;
        border: none !important;
    }

    .fc-col-header-cell-cushion {
        all: unset;
        display: block;
        text-align: center;
        font-size: 12px;
        font-weight: normal;
        color: #000;
    }

    /* === Calendar Grid Cells === */
    th.fc-col-header-cell {
        padding: 0 !important;
        margin: 0 !important;
        height: 0px !important;
        line-height: 0 !important;
        vertical-align: middle !important;
        background-color: #fae0e9ff !important;
    }

    .fc-daygrid-day {
        height: 80px !important;
        padding: 1px !important;
        border: 1px solid #ddd !important;
        position: relative;
        box-sizing: border-box;
        cursor: pointer;
    }

    .fc-daygrid-day-frame {
        height: 100% !important;
        min-height: unset !important;
        padding: 0 !important;
    }

    .fc-daygrid-day-top {
        display: flex;
        flex-direction: row-reverse;
        justify-content: space-between;
        align-items: flex-start;
        padding: 2px 4px !important;
        margin-left: auto !important;
    }

    .fc-daygrid-day-number {
        font-size: 12px !important;
        font-weight: 500 !important;
        padding: 0 2px !important;
        margin: 0 !important;
        color: #000 !important;
        text-decoration: none !important;
    }

    .fc-daygrid-body-balanced .fc-daygrid-day-events {
        min-height: 0 !important;
    }

    .fc-daygrid-day:hover {
        background-color: #fce2ebff !important;
        box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* === Grid Container Borders === */
    .fc-scrollgrid {
        border: 1px solid #ddd !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }

    /* === Responsive Adjustments === */
    @media (max-width: 768px) {
        .fc-toolbar {
            flex-direction: column;
            align-items: flex-start;
        }

        .fc-toolbar-chunk {
            justify-content: flex-start;
            width: 100%;
            margin-bottom: 3px;
        }

        .fc-toolbar-title {
            font-size: 14px !important;
        }

        .fc-button {
            font-size: 10px !important;
            padding: 2px 4px !important;
        }

        #calendar {
            
        }
    }
</style>

<div class="container-fluid py-3">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Pengajuan</h5>
            <?php if (!empty($is_leave_balance_unset)): ?>
                <span class="badge bg-warning text-dark">Sisa cuti belum diatur</span>
            <?php else: ?>
                <span class="badge bg-info">Sisa Cuti: <?= intval($balance) ?> hari</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success">
                    <?= $this->session->flashdata('success') ?>
                </div>
            <?php endif; ?>
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger">
                    <?= $this->session->flashdata('error') ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($is_leave_balance_unset)): ?>
                <div class="alert alert-warning">
                    Sisa cuti belum diatur. Anda masih bisa mengajukan izin non-cuti, tetapi Cuti Tahunan akan diblokir sampai HR memperbarui join date dan leave balance.
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-primary" id="btn-open-create-leave">
                    <i class="bi bi-plus-circle me-1"></i> Ajukan Pengajuan
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tipe</th>
                            <th>Tanggal</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th>Lampiran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $index => $row): ?>
                                <?php
                                    $is_final = in_array($row['status'], ['approved', 'rejected'], true);
                                    $status_map = [
                                        'pending_leader' => 'Menunggu Leader',
                                        'pending_hr' => 'Menunggu HR',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak'
                                    ];
                                    $status_label = $status_map[$row['status']] ?? $row['status'];
                                ?>
                                <?php
                                    $attachment_url = '';
                                    if (!empty($row['attachment_path'])) {
                                        $is_absolute_attachment = (strpos($row['attachment_path'], 'http://') === 0 || strpos($row['attachment_path'], 'https://') === 0);
                                        if ($is_absolute_attachment) {
                                            $attachment_url = $row['attachment_path'];
                                        } else {
                                            $attachment_relative = ltrim($row['attachment_path'], '/');
                                            if (file_exists(FCPATH . $attachment_relative)) {
                                                $attachment_url = base_url($attachment_relative);
                                            }
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= $row['leave_type_name'] ?></td>
                                    <td><?= date('d M Y', strtotime($row['start_date'])) ?> - <?= date('d M Y', strtotime($row['end_date'])) ?></td>
                                    <td><?= intval($row['total_days']) ?> hari</td>
                                    <td><span class="badge bg-secondary"><?= $status_label ?></span></td>
                                    <td>
                                        <?php if (!empty($attachment_url)): ?>
                                            <a href="<?= $attachment_url ?>" target="_blank">Lihat</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="d-flex gap-2">
                                        <?php if (!$is_final): ?>
                                            <button type="button"
                                                    class="btn btn-warning btn-sm text-white leave-edit-btn"
                                                    data-id="<?= intval($row['id']) ?>"
                                                    data-leave-type-id="<?= intval($row['leave_type_id']) ?>"
                                                    data-start-date="<?= $row['start_date'] ?>"
                                                    data-end-date="<?= $row['end_date'] ?>"
                                                    data-reason="<?= htmlspecialchars($row['reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-attachment="<?= $attachment_url ?>">
                                                Ubah Pengajuan
                                            </button>
                                            <form action="<?= base_url() ?>leave/delete_request" method="POST" onsubmit="return confirm('Hapus pengajuan ini?')">
                                                <input type="hidden" name="request_id" value="<?= intval($row['id']) ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus Pengajuan</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada pengajuan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Kalender Event Perusahaan</h5>
        </div>
        <div class="card-body">
            <div class="row bg-white" style="height: auto; border-radius: 10px; margin-left: 1px; margin-right: 1px">
                <div class="col-lg-9" id="calendar-container">
                    <div id="calendar" class="p-3" style=" box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border-radius: 10px; border: 2px solid #e9ecef;"></div>
                </div>
                <div class="col-lg-3 bg-white" id="event-details" style="padding: 20px; height: 600px; overflow-y: auto; border: 1px solid #ddd; border-radius: 10px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                    <div style="text-align: center;">
                        <img src="<?= base_url() ?>/assets/img/icon/load.gif" alt="Not Found" style="margin-top: 50px; max-width: 600px;">
                        <p style="font-weight: 600; margin-top: 10px; font-size: 25px;">Pilih tanggal terlebih dahulu</p>
                        <p style="color: gray; margin-top: -10px; font-size: 16px;">Klik tanggal di kalender untuk melihat event.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="leaveRequestModal" tabindex="-1" aria-labelledby="leaveRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= base_url() ?>leave/store" method="POST" enctype="multipart/form-data" id="leaveRequestForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="leaveRequestModalLabel">Ajukan Pengajuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="leave_request_id">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipe Pengajuan</label>
                            <select name="leave_type_id" id="leave_type_id" class="form-control" required>
                                <option value="">Pilih Tipe</option>
                                <?php foreach ($leave_types as $type): ?>
                                    <option value="<?= $type['id'] ?>"
                                            data-code="<?= htmlspecialchars($type['code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-requires-attachment="<?= intval($type['requires_attachment']) ?>"
                                            data-deducts-leave="<?= intval($type['deducts_leave'] ?? 0) ?>">
                                        <?= $type['display_name'] ?? $type['name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted d-block mt-1" id="leave-type-help">
                                Tipe yang ditandai mengurangi sisa cuti akan memotong saldo cuti.
                            </small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="row" id="baris-jam" style="display:none">
                        <div class="col-md-12 mb-2">
                            <div class="alert alert-light border small mb-2">
                                Isi jam kalau izinnya hanya sebagian hari. Datang pagi lalu izin pulang
                                dari jam 12:00, isi 12:00 sampai 17:00. Izin pagi lalu masuk siang,
                                isi 08:00 sampai 12:00. Dikosongkan berarti izin sehari penuh.
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Izin dari jam</label>
                            <input type="time" name="jam_mulai" id="jam_mulai" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sampai jam</label>
                            <input type="time" name="jam_selesai" id="jam_selesai" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan</label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Tulis alasan pengajuan..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lampiran (Opsional)</label>
                        <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            <div class="form-text" id="ket-lampiran">Boleh dikosongkan.</div>

                            <div id="blok-lampiran2" class="mt-3" style="display:none">
                                <label class="form-label" id="label-lampiran2">Lampiran kedua</label>
                                <input type="file" name="attachment2" class="form-control">
                                <div class="form-text" id="ket-lampiran2"></div>
                            </div>
                        <small class="text-muted d-block">Boleh dikosongkan.</small>
                        <small class="text-muted d-block" id="current-attachment-text"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="leaveRequestSubmitBtn">
                        <i class="bi bi-send me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.7/index.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var leaveModalEl = document.getElementById('leaveRequestModal');
        var leaveModal = leaveModalEl ? new bootstrap.Modal(leaveModalEl) : null;
        var leaveForm = document.getElementById('leaveRequestForm');
        var leaveTitle = document.getElementById('leaveRequestModalLabel');
        var leaveSubmitBtn = document.getElementById('leaveRequestSubmitBtn');
        var currentAttachmentText = document.getElementById('current-attachment-text');

        var createBtn = document.getElementById('btn-open-create-leave');
        if (createBtn && leaveModal && leaveForm) {
            createBtn.addEventListener('click', function() {
                leaveForm.reset();
                leaveForm.action = '<?= base_url() ?>leave/store';
                document.getElementById('leave_request_id').value = '';
                leaveTitle.textContent = 'Ajukan Pengajuan';
                leaveSubmitBtn.innerHTML = '<i class="bi bi-send me-1"></i> Kirim Pengajuan';
                currentAttachmentText.textContent = '';
                updateLeaveTypeHelp();
                leaveModal.show();
            });
        }

        document.querySelectorAll('.leave-edit-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!leaveModal || !leaveForm) return;
                leaveForm.action = '<?= base_url() ?>leave/update_request';
                document.getElementById('leave_request_id').value = btn.dataset.id || '';
                document.getElementById('leave_type_id').value = btn.dataset.leaveTypeId || '';
                document.getElementById('start_date').value = btn.dataset.startDate || '';
                document.getElementById('end_date').value = btn.dataset.endDate || '';
                document.getElementById('reason').value = btn.dataset.reason || '';
                leaveTitle.textContent = 'Edit Pengajuan';
                leaveSubmitBtn.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Update Pengajuan';
                currentAttachmentText.innerHTML = btn.dataset.attachment
                    ? 'Lampiran saat ini: <a href="' + btn.dataset.attachment + '" target="_blank">Lihat Lampiran</a>'
                    : '';
                updateLeaveTypeHelp();
                leaveModal.show();
            });
        });

        var leaveTypeSelect = document.getElementById('leave_type_id');
        var leaveTypeHelp = document.getElementById('leave-type-help');

        function updateLeaveTypeHelp() {
            if (!leaveTypeSelect || !leaveTypeHelp) return;
            var selectedOption = leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
            var deductsLeave = selectedOption ? (selectedOption.dataset.deductsLeave === '1') : false;
            leaveTypeHelp.textContent = deductsLeave
                ? 'Tipe ini mengurangi saldo cuti tahunan sesuai flow yang sudah ada.'
                : 'Tipe ini tidak mengurangi saldo cuti.';
        }

        if (leaveTypeSelect) {
            leaveTypeSelect.addEventListener('change', updateLeaveTypeHelp);
            updateLeaveTypeHelp();
        }

        var calendarEl = document.getElementById('calendar');
        var eventDetailsEl = document.getElementById('event-details');
        if (!calendarEl) return;

        var initialView = 'dayGridMonth';
        var events = <?php
            $calendar_events = [];
            if (!empty($events)) {
                foreach ($events as $event) {
                    $end = date('Y-m-d', strtotime($event['end_date'] . ' +1 day'));
                    $calendar_events[] = [
                        'title' => $event['title'],
                        'start' => $event['start_date'],
                        'end' => $end,
                        'allDay' => true,
                        'backgroundColor' => !empty($event['is_blocking']) ? '#ff4d4f' : '#1890ff',
                        'borderColor' => !empty($event['is_blocking']) ? '#ff4d4f' : '#1890ff',
                        'textColor' => '#ffffff',
                        'extendedProps' => [
                            'description' => $event['description'],
                            'is_blocking' => !empty($event['is_blocking']) ? 1 : 0
                        ]
                    ];
                }
            }
            echo json_encode($calendar_events);
        ?>;

        function renderEventDetailsPlaceholder() {
            eventDetailsEl.innerHTML = `
            <div style="text-align: center;">
                <img src="<?= base_url() ?>/assets/img/icon/load.gif" alt="Not Found" style="margin-top: 50px; max-width: 600px;">
                <p style="font-weight: 600; margin-top: 10px; font-size: 25px;">Pilih tanggal terlebih dahulu</p>
                <p style="color: gray; margin-top: -10px; font-size: 16px;">Klik tanggal di kalender untuk melihat event.</p>
            </div>
        `;
        }

        function renderEventDetailsForDate(dateString) {
            const list = events.filter(function(event) {
                const start = event.start;
                const end = event.end || event.start;
                return dateString >= start && dateString < end;
            });

            if (list.length === 0) {
                eventDetailsEl.innerHTML = `
                <div style="text-align: center;">
                    <img src="<?= base_url() ?>/assets/img/icon/load.gif" alt="Not Found" style="margin-top: 50px; max-width: 600px;">
                    <p style="font-weight: 600; margin-top: 10px; font-size: 25px;">Oops... Data Kosong</p>
                    <p style="color: gray; margin-top: -10px; font-size: 16px;">Tidak ada event pada tanggal yang dipilih</p>
                </div>
            `;
                return;
            }

            eventDetailsEl.innerHTML = '';
            const header = document.createElement('div');
            header.style.cssText = 'background-color: #f8f9fa; padding: 8px; margin: 10px 0 5px 0; border-radius: 5px; font-weight: 600; font-size: 13px; text-align: center; color: #495057;';
            header.textContent = new Date(dateString + 'T00:00:00').toLocaleDateString('id-ID', {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
            eventDetailsEl.appendChild(header);

            list.forEach(function(event) {
                const item = document.createElement('div');
                const borderColor = event.extendedProps.is_blocking ? '#ff4d4f' : '#1890ff';
                item.innerHTML = `
                <div style="border-left: 4px solid ${borderColor}; border-top: 1px solid #E8D8E8; border-bottom: 1px solid #E8D8E8; border-right: 1px solid #E8D8E8; position: relative; padding: 10px; background-color: #fff; border-radius: 8px; margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 14px; font-weight: 600;">${event.title}</span>
                        <span class="badge" style="background-color:${event.extendedProps.is_blocking ? '#ff4d4f' : '#1890ff'}; color:#fff;">${event.extendedProps.is_blocking ? 'BLOCKING' : 'EVENT'}</span>
                    </div>
                    <div style="font-size: 12px; color: gray; margin-top: 4px;">${event.extendedProps.description || '-'}
                    </div>
                </div>
                `;
                eventDetailsEl.appendChild(item);
            });
        }

        function clearSelectedDate() {
            document.querySelectorAll('.fc-daygrid-day.selected-date').forEach(function(cell) {
                cell.classList.remove('selected-date');
            });
        }

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: initialView,
            headerToolbar: {
                left: 'prev',
                center: 'title',
                right: 'next'
            },
            locale: 'id',
            height: 'auto',
            contentHeight: 500,
            events: events,
            dateClick: function(info) {
                clearSelectedDate();
                const cell = document.querySelector('.fc-daygrid-day[data-date="' + info.dateStr + '"]');
                if (cell) cell.classList.add('selected-date');
                renderEventDetailsForDate(info.dateStr);
            }
        });

        calendar.render();
        renderEventDetailsPlaceholder();
    });
</script>

<script>
/* FullCalendar HP: paksa hitung ulang lebar setelah layout final */
(function(){
  function hitungUlang(){
    try { window.dispatchEvent(new Event('resize')); } catch(e){}
  }
  function jalan(){
    hitungUlang();
    setTimeout(hitungUlang, 350);
    setTimeout(hitungUlang, 900);
    setTimeout(hitungUlang, 1800);
  }
  if (document.readyState === 'complete') jalan();
  else window.addEventListener('load', jalan);

  window.addEventListener('orientationchange', function(){ setTimeout(hitungUlang, 350); });

  var el = document.getElementById('calendar');
  if (el && window.ResizeObserver){
    var p = el.parentElement;
    if (p){
      var t = null;
      new ResizeObserver(function(){
        clearTimeout(t); t = setTimeout(hitungUlang, 150);
      }).observe(p);
    }
  }
})();
</script>

<script>
(function () {
  var jenis = document.querySelector('[name="leave_type_id"]');
  var baris = document.getElementById('baris-jam');
  if (!jenis || !baris) return;
  function cek() {
    var t = jenis.options[jenis.selectedIndex];
    var setengah = t && /setengah/i.test(t.textContent);
    baris.style.display = setengah ? '' : 'none';
    if (!setengah) {
      document.getElementById('jam_mulai').value = '';
      document.getElementById('jam_selesai').value = '';
    }
  }
  jenis.addEventListener('change', cek);
  cek();
})();
</script>

<script>
/**
 * Label lampiran mengikuti jenis pengajuan. Tanpa ini karyawan tidak
 * tahu berkas apa yang diharapkan, dan HRD menerima pengajuan tanpa
 * bukti yang dibutuhkan.
 */
(function () {
  var ATURAN = {
    'sakit': {
      wajib: true,
      label: 'Surat keterangan dokter',
      ket: 'Foto atau PDF surat dokter, atau surat keterangan berobat. Wajib dilampirkan.',
      label2: 'Struk pembelian obat',
      ket2: 'Kalau ada. Boleh dikosongkan bila tidak menebus obat.'
    },
    'melahirkan': {
      wajib: true,
      label: 'Surat keterangan dokter atau bidan',
      ket: 'Berisi perkiraan tanggal lahir. Wajib dilampirkan.'
    },
    'setengah': {
      wajib: false,
      label: 'Lampiran pendukung',
      ket: 'Undangan, surat panggilan, atau bukti lain kalau ada. Boleh dikosongkan.'
    },
    'tahunan': {
      wajib: false,
      label: 'Lampiran pendukung',
      ket: 'Boleh dikosongkan. Lampirkan bila ada dokumen yang perlu diketahui atasan.'
    },
    'pribadi': {
      wajib: false,
      label: 'Lampiran pendukung',
      ket: 'Undangan, surat panggilan, atau bukti keperluan. Boleh dikosongkan.'
    },
    'gaji': {
      wajib: false,
      label: 'Lampiran pendukung',
      ket: 'Izin di luar jatah cuti, hari tersebut tidak dibayar. Lampirkan bukti bila ada.'
    }
  };

  var jenis  = document.querySelector('[name="leave_type_id"]');
  var input  = document.querySelector('[name="attachment"]');
  var ket    = document.getElementById('ket-lampiran');
  var blok2  = document.getElementById('blok-lampiran2');
  var lbl2   = document.getElementById('label-lampiran2');
  var ket2   = document.getElementById('ket-lampiran2');
  if (!jenis || !input) return;

  // label utama: elemen <label> terdekat sebelum input berkas
  var lbl = input.closest('div') ? input.closest('div').querySelector('label') : null;
  if (!lbl) {
    var p = input.previousElementSibling;
    while (p && p.tagName !== 'LABEL') p = p.previousElementSibling;
    lbl = p;
  }

  function cocok(teks) {
    teks = (teks || '').toLowerCase();
    for (var k in ATURAN) { if (teks.indexOf(k) !== -1) return ATURAN[k]; }
    return null;
  }

  function terapkan() {
    var opt = jenis.options[jenis.selectedIndex];
    var a = cocok(opt ? opt.textContent : '');

    if (!a) {
      if (lbl) lbl.textContent = 'Lampiran (Opsional)';
      if (ket) ket.textContent = 'Boleh dikosongkan.';
      input.removeAttribute('required');
      if (blok2) blok2.style.display = 'none';
      return;
    }

    if (lbl) lbl.textContent = a.label + (a.wajib ? '' : ' (opsional)');
    if (ket) ket.textContent = a.ket;
    if (a.wajib) { input.setAttribute('required', 'required'); }
    else { input.removeAttribute('required'); }

    if (a.label2 && blok2) {
      blok2.style.display = '';
      lbl2.textContent = a.label2 + ' (opsional)';
      ket2.textContent = a.ket2 || '';
    } else if (blok2) {
      blok2.style.display = 'none';
    }
  }

  jenis.addEventListener('change', terapkan);
  terapkan();
})();
</script>

<script>
/**
 * Kunci tombol kirim setelah ditekan sekali. Tanpa ini, tekanan
 * berulang menghasilkan beberapa pengajuan yang sama sekaligus,
 * dan tiap pengajuan mengirim pesan WhatsApp sendiri.
 */
(function () {
  document.querySelectorAll('form').forEach(function (f) {
    if (!f.querySelector('[name="leave_type_id"]')) return;
    f.setAttribute('data-kunci-kirim', '1');
    f.addEventListener('submit', function () {
      var b = f.querySelector('button[type="submit"]');
      if (!b) return;
      if (b.dataset.terkirim) { return false; }
      b.dataset.terkirim = '1';
      b.disabled = true;
      b.textContent = 'Mengirim...';
      setTimeout(function () { b.disabled = false; delete b.dataset.terkirim; }, 15000);
    });
  });
})();
</script>
