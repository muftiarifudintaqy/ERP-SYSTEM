<div class="container-fluid py-3">
    <?php $current = 'my_payroll'; include APPPATH . 'views/payroll/_menu.php'; ?>
    <?php $status_labels = ['draft' => 'Draft', 'reviewed' => 'Ditinjau', 'finalized' => 'Final', 'paid' => 'Dibayar']; ?>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Daftar Payroll Saya</h5></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Tanggal Bayar</th>
                        <th>Status</th>
                        <th>Gaji Bersih Diterima</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($slips)): ?>
                        <?php foreach ($slips as $slip): ?>
                            <tr>
                                <td><?= !empty($slip['period_label']) ? $slip['period_label'] : '-' ?></td>
                                <td><?= !empty($slip['pay_date']) ? $slip['pay_date'] : '-' ?></td>
                                <td><span class="badge bg-light text-dark border"><?= $status_labels[$slip['status']] ?? ucfirst($slip['status']) ?></span></td>
                                <td><strong>Rp <?= number_format((float) $slip['net_amount'], 0, ',', '.') ?></strong></td>
                                <td>
                                    <a href="<?= base_url('payroll/my_payroll_detail?id=' . $slip['id']) ?>" class="btn btn-outline-primary btn-sm">Detail</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">Belum ada payroll tersedia.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
