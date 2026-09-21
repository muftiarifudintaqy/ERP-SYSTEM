<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Detail Manpower Planning</h5>
                <div>
                    <?php if($can_edit): ?>
                    <a href="<?= base_url() ?>/manpower_planning/edit_page/<?= $data['id'] ?>" class="btn btn-primary me-2">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <?php endif; ?>
                    <a href="<?= base_url() ?>/manpower_planning" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Section: Informasi Request -->
            <div class="section-card mb-4">
                <h6 class="section-title"><i class="bi bi-info-circle me-2"></i>Informasi Request</h6>
                <div class="section-content">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="detail-label">Requestor</label>
                            <div class="detail-value"><?= $data['requestor'] ?: '-' ?></div>
                        </div>
                        <div class="col-md-4">
                            <label class="detail-label">Tanggal Request</label>
                            <div class="detail-value">
                                <?= $data['tanggal_request'] ? date('d F Y', strtotime($data['tanggal_request'])) : '-' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Posisi & Kebutuhan -->
            <div class="section-card mb-4">
                <h6 class="section-title"><i class="bi bi-briefcase me-2"></i>Posisi & Kebutuhan</h6>
                <div class="section-content">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="detail-label">Posisi</label>
                            <div class="detail-value"><?= $data['posisi'] ?: '-' ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="detail-label">Level</label>
                            <div class="detail-value">
                                <?php if ($data['level']): ?>
                                    <span class="badge bg-info"><?= $data['level'] ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="detail-label">Jumlah</label>
                            <div class="detail-value">
                                <span class="badge bg-primary"><?= $data['jumlah'] ?> Orang</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="detail-label">Sistem</label>
                            <div class="detail-value"><?= $data['sistem'] ?: '-' ?></div>
                        </div>
                        <div class="col-md-4">
                            <label class="detail-label">Estimasi Join</label>
                            <div class="detail-value">
                                <?= $data['estimasi_join'] ? date('d F Y', strtotime($data['estimasi_join'])) : '-' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Kompetensi -->
            <div class="section-card mb-4">
                <h6 class="section-title"><i class="bi bi-award me-2"></i>Kompetensi</h6>
                <div class="section-content">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="detail-label">Kompetensi yang Dibutuhkan</label>
                            <div class="detail-value kompetensi-content">
                                <?= $data['kompetensi_dibutuhkan'] ?: '-' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Status & Progress -->
            <div class="section-card mb-4">
                <h6 class="section-title"><i class="bi bi-calendar-check me-2"></i>Status & Progress</h6>
                <div class="section-content">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="detail-label">Status</label>
                            <div class="detail-value">
                                <?php if ($data['status'] == 'CONTRACT'): ?>
                                    <span class="badge bg-success">CONTRACT</span>
                                <?php elseif ($data['status'] == 'INTERNSHIP'): ?>
                                    <span class="badge bg-primary">INTERNSHIP</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="detail-label">Progress</label>
                            <div class="detail-value">
                                <?php if ($data['progress'] == 'DONE'): ?>
                                    <span class="badge bg-success">DONE</span>
                                <?php elseif ($data['progress'] == 'RUN'): ?>
                                    <span class="badge bg-primary">RUN</span>
                                <?php elseif ($data['progress'] == 'PENDING'): ?>
                                    <span class="badge bg-warning">PENDING</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="detail-label">Realisasi</label>
                            <div class="detail-value">
                                <?= $data['realisasi'] ? date('d F Y', strtotime($data['realisasi'])) : '-' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Approval -->
            <div class="section-card mb-4">
                <h6 class="section-title"><i class="bi bi-check-circle me-2"></i>Approval</h6>
                <div class="section-content">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="detail-label">Approval CEO</label>
                            <div class="detail-value">
                                <?php if ($data['approval_ceo'] == 'Done'): ?>
                                    <span class="badge bg-success">Done</span>
                                <?php elseif ($data['approval_ceo'] == 'Hold'): ?>
                                    <span class="badge bg-warning">Hold</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Lampiran/Catatan -->
            <div class="section-card mb-4">
                <h6 class="section-title"><i class="bi bi-paperclip me-2"></i>Lampiran/Catatan</h6>
                <div class="section-content">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="detail-label">Alasan Penambahan</label>
                            <div class="detail-value">
                                <?= $data['alasan_penambahan'] ? nl2br(htmlspecialchars($data['alasan_penambahan'])) : '-' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Information -->
            <div class="card bg-light">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="bi bi-person me-1"></i> Dibuat oleh: <?= $data['created_by'] ?: '-' ?> 
                                pada <?= $data['created_at'] ? date('d F Y H:i', strtotime($data['created_at'])) : '-' ?>
                            </small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted">
                                <i class="bi bi-pencil-square me-1"></i> Terakhir diubah oleh: <?= $data['updated_by'] ?: '-' ?> 
                                pada <?= $data['updated_at'] ? date('d F Y H:i', strtotime($data['updated_at'])) : '-' ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #f0f0f0;
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

    /* Section styling */
    .section-card {
        background: #fafafa;
        border: 1px solid #e8e8e8;
        border-radius: 4px;
        padding: 0;
        margin-bottom: 20px;
    }
    
    .section-title {
        background: #fff;
        padding: 12px 16px;
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: rgba(0, 0, 0, 0.85);
        border-bottom: 1px solid #e8e8e8;
        border-radius: 4px 4px 0 0;
    }
    
    .section-content {
        padding: 20px;
        background: #fff;
        border-radius: 0 0 4px 4px;
    }

    .detail-label {
        font-size: 13px;
        color: rgba(0, 0, 0, 0.45);
        margin-bottom: 4px;
        font-weight: 400;
        display: block;
    }

    .detail-value {
        font-size: 14px;
        color: rgba(0, 0, 0, 0.85);
        font-weight: 500;
        margin-bottom: 16px;
    }

    .kompetensi-content {
        background: #f9f9f9;
        padding: 12px;
        border-radius: 4px;
        border: 1px solid #e8e8e8;
        line-height: 1.6;
    }

    .badge {
        padding: 4px 8px;
        font-size: 12px;
        font-weight: 500;
        border-radius: 2px;
    }
</style>
