<?php
$rows = [
    'Karyawan'        => htmlspecialchars($data['employee_name'] ?? '-'),
    'Email'           => htmlspecialchars($data['email'] ?? '-'),
    'Jenis Kontrak'   => htmlspecialchars($data['contract_type']),
    'Nomor Kontrak'   => htmlspecialchars($data['contract_number'] ?: '-'),
    'Jabatan'         => htmlspecialchars($data['position_name'] ?: '-'),
    'Tanggal Mulai'   => date('d M Y', strtotime($data['start_date'])),
    'Tanggal Berakhir' => !empty($data['end_date']) ? date('d M Y', strtotime($data['end_date'])) : 'Permanen (PKWTT)',
    'Durasi'          => htmlspecialchars($data['duration_text'] ?: '-'),
    'Gaji'            => ($data['salary'] !== null && $data['salary'] !== '') ? 'Rp ' . number_format((float) $data['salary'], 0, ',', '.') : '-',
    'Status'          => ucfirst($data['status']),
    'Kontrak Aktif?'  => ((int) $data['is_current'] === 1) ? 'Ya' : 'Tidak',
    'Dibuat'          => (!empty($data['created_at']) ? date('d M Y H:i', strtotime($data['created_at'])) : '-') . (!empty($data['created_by_name']) ? ' oleh ' . htmlspecialchars($data['created_by_name']) : ''),
    'Catatan'         => nl2br(htmlspecialchars($data['notes'] ?: '-')),
];
?>
<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-3">
        <a href="<?= base_url() ?>contract/history/<?= $data['user_id'] ?>" class="text-decoration-none text-muted me-2"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Detail Kontrak</h4>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table">
                <tbody>
                    <?php foreach ($rows as $label => $val): ?>
                        <tr>
                            <th style="width:200px;color:rgba(0,0,0,0.65);"><?= $label ?></th>
                            <td><?= $val ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <th style="color:rgba(0,0,0,0.65);">Dokumen</th>
                        <td>
                            <?php if (!empty($data['document_url'])): ?>
                                <a href="<?= htmlspecialchars($data['document_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="bi bi-link-45deg me-1"></i>Buka Link Dokumen</a>
                            <?php else: ?>
                                <span class="text-muted">Tidak ada dokumen</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
