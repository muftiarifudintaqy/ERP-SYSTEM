<?php
$freq_label = [
    'once' => 'Sekali saja',
    'daily' => '1x per hari',
    'every_login' => 'Setiap login'
];
$start_date = !empty($data['start_date']) ? date('d M Y', strtotime($data['start_date'])) : '-';
$end_date = !empty($data['end_date']) ? date('d M Y', strtotime($data['end_date'])) : '-';
?>
<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Detail Announcement</h5>
                <div>
                    <a href="<?= base_url() ?>announcement/edit_page?id=<?= $data['id'] ?>" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <a href="<?= base_url() ?>announcement" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($data['image'])): ?>
                <div class="mb-3 text-center">
                    <img src="<?= base_url() ?>assets/uploads/announcements/<?= $data['image'] ?>" alt="banner"
                        style="max-width: 100%; max-height: 320px; border-radius: 8px; border: 1px solid #f0f0f0;">
                </div>
            <?php endif; ?>

            <table class="table">
                <tbody>
                    <tr><th style="width: 220px;">Judul / Label</th><td><?= !empty($data['title']) ? htmlspecialchars($data['title']) : '<span class="text-muted">(tanpa judul)</span>' ?></td></tr>
                    <tr><th>Periode Tayang</th><td><?= $start_date ?> s/d <?= $end_date ?></td></tr>
                    <tr><th>Frekuensi</th><td><?= $freq_label[$data['frequency']] ?? $data['frequency'] ?></td></tr>
                    <tr><th>Status</th><td><?= $data['is_active'] == 1 ? '<span style="color:#52c41a;">Aktif</span>' : '<span style="color:#ff4d4f;">Nonaktif</span>' ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
