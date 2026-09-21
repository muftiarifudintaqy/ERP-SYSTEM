<div class="container-fluid py-3">
    <!-- Header -->
    <div class="row align-items-center mb-4">
        <div class="col-lg-8">
            <h3 class="text-primary fw-500">RIWAYAT QUEST</h3>
            <p class="text-muted mb-0">Lihat semua aktivitas quest yang pernah Anda ajukan</p>
        </div>
        <div class="col-lg-4 text-end">
            <a href="<?= base_url() ?>profile" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card text-center border-success h-100">
                <div class="card-body p-3">
                    <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                    <h4 class="text-success mt-2 mb-0">
                        <?php
                        $approved = array_filter($submissions, function ($s) {
                            return $s['status'] == 'approved';
                        });
                        echo count($approved);
                        ?>
                    </h4>
                    <small class="text-muted">Disetujui</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card text-center border-warning h-100">
                <div class="card-body p-3">
                    <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                    <h4 class="text-warning mt-2 mb-0">
                        <?php
                        $pending = array_filter($submissions, function ($s) {
                            return $s['status'] == 'pending';
                        });
                        echo count($pending);
                        ?>
                    </h4>
                    <small class="text-muted">Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card text-center border-danger h-100">
                <div class="card-body p-3">
                    <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                    <h4 class="text-danger mt-2 mb-0">
                        <?php
                        $denied = array_filter($submissions, function ($s) {
                            return $s['status'] == 'denied';
                        });
                        echo count($denied);
                        ?>
                    </h4>
                    <small class="text-muted">Ditolak</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card text-center border-secondary h-100">
                <div class="card-body p-3">
                    <i class="bi bi-slash-circle text-secondary" style="font-size: 2rem;"></i>
                    <h4 class="text-secondary mt-2 mb-0">
                        <?php
                        $canceled = array_filter($submissions, function ($s) {
                            return $s['status'] == 'canceled';
                        });
                        echo count($canceled);
                        ?>
                    </h4>
                    <small class="text-muted">Dibatalkan</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card text-center border-dark h-100">
                <div class="card-body p-3">
                    <i class="bi bi-list-check text-dark" style="font-size: 2rem;"></i>
                    <h4 class="text-dark mt-2 mb-0"><?= count($submissions) ?></h4>
                    <small class="text-muted">Total Quest</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card text-center border-info h-100">
                <div class="card-body p-3">
                    <i class="bi bi-star text-info" style="font-size: 2rem;"></i>
                    <h4 class="text-info mt-2 mb-0"><?= !empty($profile['score']) ? $profile['score'] : 0 ?></h4>
                    <small class="text-muted">Total Poin</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quest History Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                <i class="bi bi-list-ul me-2"></i>Semua Riwayat Quest
            </h5>
        </div>
        <div class="card-body">
            <?php if (!empty($submissions)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="8%" class="text-center">Tipe</th>
                                <th width="28%">Nama Quest</th>
                                <th width="12%" class="text-center">Status</th>
                                <th width="13%" class="text-center">Tanggal Submit</th>
                                <th width="13%" class="text-center">Tanggal Review</th>
                                <th width="12%" class="text-center">Benefit</th>
                                <th width="14%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $index => $submission): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge <?= $submission['quest_type'] == 'main' ? 'bg-primary' : 'bg-warning text-dark' ?>">
                                            <?= $submission['quest_type'] == 'main' ? '🎯 Main' : '⭐ Side' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="d-block mb-1"><?= htmlspecialchars($submission['quest_title']) ?></strong>
                                        <small class="text-muted">
                                            <i class="bi bi-hash"></i> ID: <?= $submission['quest_id'] ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        $status_icon = '';
                                        switch ($submission['status']) {
                                            case 'pending':
                                                $status_class = 'bg-warning text-dark';
                                                $status_text = '🟡 Pending';
                                                $status_icon = 'bi-clock';
                                                break;
                                            case 'approved':
                                                $status_class = 'bg-success';
                                                $status_text = '✅ Disetujui';
                                                $status_icon = 'bi-check-circle';
                                                break;
                                            case 'denied':
                                                $status_class = 'bg-danger';
                                                $status_text = '❌ Ditolak';
                                                $status_icon = 'bi-x-circle';
                                                break;
                                            case 'canceled':
                                                $status_class = 'bg-secondary';
                                                $status_text = '⭕ Dibatalkan';
                                                $status_icon = 'bi-slash-circle';
                                                break;
                                            default:
                                                $status_class = 'bg-light text-dark';
                                                $status_text = 'Unknown';
                                                $status_icon = 'bi-question-circle';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $status_class ?>">
                                            <i class="bi <?= $status_icon ?> me-1"></i><?= $status_text ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted d-block">
                                            <i class="bi bi-calendar3"></i>
                                            <?= date('d M Y', strtotime($submission['submitted_at'])) ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i>
                                            <?= date('H:i', strtotime($submission['submitted_at'])) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($submission['approved_at'])): ?>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-calendar3"></i>
                                                <?= date('d M Y', strtotime($submission['approved_at'])) ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i>
                                                <?= date('H:i', strtotime($submission['approved_at'])) ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($submission['benefit_type'])): ?>
                                            <?php
                                            $benefit_icon = '';
                                            $benefit_class = '';
                                            switch ($submission['benefit_type']) {
                                                case 'promotion':
                                                    $benefit_icon = 'bi-arrow-up-circle';
                                                    $benefit_class = 'bg-primary';
                                                    break;
                                                case 'bonus':
                                                    $benefit_icon = 'bi-currency-dollar';
                                                    $benefit_class = 'bg-warning text-dark';
                                                    break;
                                                case 'salary':
                                                    $benefit_icon = 'bi-cash-stack';
                                                    $benefit_class = 'bg-success';
                                                    break;
                                                case 'leave':
                                                    $benefit_icon = 'bi-calendar-heart';
                                                    $benefit_class = 'bg-info';
                                                    break;
                                                case 'wfa':
                                                    $benefit_icon = 'bi-house';
                                                    $benefit_class = 'bg-secondary';
                                                    break;
                                                default:
                                                    $benefit_icon = 'bi-gift';
                                                    $benefit_class = 'bg-info';
                                            }
                                            ?>
                                            <span class="badge <?= $benefit_class ?>">
                                                <i class="bi <?= $benefit_icon ?> me-1"></i><?= ucfirst($submission['benefit_type']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary view-details"
                                            data-bs-toggle="modal"
                                            data-bs-target="#detailModal<?= $index ?>"
                                            title="Lihat Detail">
                                            <i class="bi bi-eye me-1"></i> Detail
                                        </button>
                                    </td>
                                </tr>

                                <!-- Detail Modal for each submission -->
                                <div class="modal fade" id="detailModal<?= $index ?>" tabindex="-1" aria-labelledby="detailModalLabel<?= $index ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg">
                                            <!-- Modal Header with Status Color -->
                                            <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                <div class="w-100">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <h5 class="modal-title text-white mb-2" id="detailModalLabel<?= $index ?>">
                                                                <i class="bi bi-file-earmark-text me-2"></i>Detail Quest
                                                            </h5>
                                                            <h6 class="text-white-50 mb-0"><?= htmlspecialchars($submission['quest_title']) ?></h6>
                                                        </div>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="mt-3">
                                                        <span class="badge <?= $submission['quest_type'] == 'main' ? 'bg-primary' : 'bg-warning text-dark' ?> me-2">
                                                            <?= $submission['quest_type'] == 'main' ? '🎯 Main Quest' : '⭐ Side Quest' ?>
                                                        </span>
                                                        <span class="badge <?= $status_class ?>">
                                                            <i class="bi <?= $status_icon ?> me-1"></i><?= $status_text ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Modal Body with Cards -->
                                            <div class="modal-body p-4" style="background-color: #f8f9fa;">
                                                <!-- Quest Info Card -->
                                                <div class="card border-0 shadow-sm mb-3">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-primary mb-3">
                                                            <i class="bi bi-info-circle me-2"></i>Informasi Quest
                                                        </h6>
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <div class="d-flex align-items-start">
                                                                    <div class="me-3">
                                                                        <i class="bi bi-hash text-muted" style="font-size: 1.5rem;"></i>
                                                                    </div>
                                                                    <div>
                                                                        <small class="text-muted d-block">Quest ID</small>
                                                                        <strong><?= $submission['quest_id'] ?></strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="d-flex align-items-start">
                                                                    <div class="me-3">
                                                                        <i class="bi bi-tag text-muted" style="font-size: 1.5rem;"></i>
                                                                    </div>
                                                                    <div>
                                                                        <small class="text-muted d-block">Tipe Quest</small>
                                                                        <strong><?= $submission['quest_type'] == 'main' ? 'Main Quest' : 'Side Quest' ?></strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Timeline Card -->
                                                <div class="card border-0 shadow-sm mb-3">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-primary mb-3">
                                                            <i class="bi bi-clock-history me-2"></i>Timeline
                                                        </h6>
                                                        <div class="timeline-container">
                                                            <div class="timeline-item mb-3">
                                                                <div class="d-flex align-items-start">
                                                                    <div class="timeline-icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                                        <i class="bi bi-send"></i>
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <small class="text-muted d-block">Tanggal Submit</small>
                                                                        <strong><?= date('d M Y', strtotime($submission['submitted_at'])) ?></strong>
                                                                        <small class="text-muted ms-2"><?= date('H:i', strtotime($submission['submitted_at'])) ?></small>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <?php if (!empty($submission['approved_at'])): ?>
                                                                <div class="timeline-item">
                                                                    <div class="d-flex align-items-start">
                                                                        <div class="timeline-icon bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                                            <i class="bi bi-check2"></i>
                                                                        </div>
                                                                        <div class="flex-grow-1">
                                                                            <small class="text-muted d-block">Tanggal Review</small>
                                                                            <strong><?= date('d M Y', strtotime($submission['approved_at'])) ?></strong>
                                                                            <small class="text-muted ms-2"><?= date('H:i', strtotime($submission['approved_at'])) ?></small>
                                                                            <?php if (!empty($submission['approver_name'])): ?>
                                                                                <div class="mt-1">
                                                                                    <small class="text-muted">Reviewer:</small>
                                                                                    <span class="badge bg-light text-dark ms-1">
                                                                                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($submission['approver_name']) ?>
                                                                                    </span>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Benefit Card -->
                                                <?php if (!empty($submission['benefit_type'])): ?>
                                                    <?php
                                                    $benefit_icon_modal = '';
                                                    $benefit_class_modal = '';
                                                    $benefit_desc = '';
                                                    switch ($submission['benefit_type']) {
                                                        case 'promotion':
                                                            $benefit_icon_modal = 'bi-arrow-up-circle';
                                                            $benefit_class_modal = 'primary';
                                                            $benefit_desc = 'Promosi jabatan ke level yang lebih tinggi';
                                                            break;
                                                        case 'bonus':
                                                            $benefit_icon_modal = 'bi-currency-dollar';
                                                            $benefit_class_modal = 'warning';
                                                            $benefit_desc = 'Bonus moneter sebagai reward atas pencapaian';
                                                            break;
                                                        case 'salary':
                                                            $benefit_icon_modal = 'bi-cash-stack';
                                                            $benefit_class_modal = 'success';
                                                            $benefit_desc = 'Kenaikan gaji sebagai apresiasi kinerja';
                                                            break;
                                                        case 'leave':
                                                            $benefit_icon_modal = 'bi-calendar-heart';
                                                            $benefit_class_modal = 'info';
                                                            $benefit_desc = 'Cuti tambahan berbayar untuk work-life balance';
                                                            break;
                                                        case 'wfa':
                                                            $benefit_icon_modal = 'bi-house';
                                                            $benefit_class_modal = 'secondary';
                                                            $benefit_desc = 'Hak kerja dari mana saja (Work From Anywhere)';
                                                            break;
                                                        default:
                                                            $benefit_icon_modal = 'bi-gift';
                                                            $benefit_class_modal = 'info';
                                                            $benefit_desc = 'Benefit khusus';
                                                    }
                                                    ?>
                                                    <div class="card border-0 shadow-sm mb-3 border-start border-<?= $benefit_class_modal ?> border-4">
                                                        <div class="card-body">
                                                            <h6 class="card-title text-<?= $benefit_class_modal ?> mb-3">
                                                                <i class="bi <?= $benefit_icon_modal ?> me-2"></i>Benefit yang Diterima
                                                            </h6>
                                                            <div class="d-flex align-items-center p-3 rounded" style="background-color: rgba(var(--bs-<?= $benefit_class_modal ?>-rgb), 0.1);">
                                                                <div class="me-3">
                                                                    <div class="bg-<?= $benefit_class_modal ?> text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                                        <i class="bi <?= $benefit_icon_modal ?>" style="font-size: 1.5rem;"></i>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <h6 class="mb-1 text-<?= $benefit_class_modal ?>"><?= ucfirst($submission['benefit_type']) ?></h6>
                                                                    <p class="mb-0 text-muted small"><?= $benefit_desc ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- HR Notes Card -->
                                                <?php if (!empty($submission['hr_notes'])): ?>
                                                    <div class="card border-0 shadow-sm mb-3 border-start border-info border-4">
                                                        <div class="card-body">
                                                            <h6 class="card-title text-info mb-3">
                                                                <i class="bi bi-chat-left-quote me-2"></i>Catatan HR
                                                            </h6>
                                                            <div class="p-3 rounded" style="background-color: #e7f3ff;">
                                                                <p class="mb-0"><?= nl2br(htmlspecialchars($submission['hr_notes'])) ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Modal Footer -->
                                            <div class="modal-footer border-0 bg-white">
                                                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                                                    <i class="bi bi-x-circle me-2"></i>Tutup
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (!empty($pagination)): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        Menampilkan halaman <?= isset($_GET['page']) ? intval($_GET['page']) : 1 ?> dari <?= $page ?> (<?= $total_submissions ?> total quest)
                    </div>
                    <div>
                        <?= $pagination ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-list-ul" style="font-size: 4rem; color: #ccc;"></i>
                    <h4 class="text-muted mt-3">Belum Ada Riwayat Quest</h4>
                    <p class="text-muted">Anda belum pernah mengajukan quest apapun.</p>
                    <a href="<?= base_url() ?>profile" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Mulai Apply Quest
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .table th {
        border-top: none;
        font-weight: 600;
        background-color: #f8f9fa;
    }

    .table td {
        vertical-align: middle;
    }

    .card {
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .badge {
        font-size: 0.75rem;
    }

    .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .alert {
        border-radius: 8px;
    }

    .btn-sm {
        padding: 0.25rem 0.5rem;
    }

    /* Pagination Styling */
    .pagination {
        margin: 0;
        justify-content: flex-end;
    }

    .page-item {
        margin: 0 2px;
    }

    .page-link {
        border: 1px solid #d9d9d9;
        color: rgba(0, 0, 0, 0.85);
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 14px;
        min-width: 32px;
        text-align: center;
    }

    .page-link:hover {
        border-color: #1890ff;
        color: #1890ff;
        background-color: #fff;
    }

    .page-item.active .page-link {
        background-color: #1890ff;
        border-color: #1890ff;
        color: white;
        font-weight: 500;
    }

    .page-item.disabled .page-link {
        background-color: #f5f5f5;
        border-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.25);
        cursor: not-allowed;
    }
</style>