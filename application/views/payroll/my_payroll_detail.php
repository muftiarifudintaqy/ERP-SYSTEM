<div class="container-fluid py-3">
    <?php $current = 'my_payroll'; include APPPATH . 'views/payroll/_menu.php'; ?>
    <?php $status_labels = ['draft' => 'Draft', 'reviewed' => 'Ditinjau', 'finalized' => 'Final', 'paid' => 'Dibayar']; ?>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Detail Payroll - <?= $slip['period_label'] ?></h5>
            <a href="<?= base_url('payroll/my_payroll') ?>" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><small>Status</small><p><span class="badge bg-light text-dark border"><?= $status_labels[$slip['status']] ?? ucfirst($slip['status']) ?></span></p></div>
                <div class="col-md-3"><small>Tanggal Bayar</small><p><?= !empty($slip['pay_date']) ? $slip['pay_date'] : '-' ?></p></div>
                <div class="col-md-3"><small>Bruto</small><p>Rp <?= number_format((float) $slip['gross_amount'], 0, ',', '.') ?></p></div>
                <div class="col-md-3"><small>Potongan</small><p>Rp <?= number_format((float) $slip['deduction_amount'], 0, ',', '.') ?></p></div>
            </div>
            <p class="mb-0">Gaji Bersih Diterima: <strong>Rp <?= number_format((float) $slip['net_amount'], 0, ',', '.') ?></strong></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Breakdown Komponen</h5>
            <a href="<?= base_url('payroll/my_payroll_pdf?id=' . $slip['id']) ?>" class="btn btn-outline-primary btn-sm" target="_blank">Unduh Slip Gaji (PDF)</a>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>Komponen</th>
                        <th>Tipe</th>
                        <th>Calculation</th>
                        <th>Qty</th>
                        <th>Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= $item['component_name_snapshot'] ?></td>
                                <td><?= ucfirst($item['component_type_snapshot']) ?></td>
                                <td><?= $item['calculation_type_snapshot'] ?></td>
                                <td><?= $item['qty'] ?></td>
                                <td>Rp <?= number_format((float) $item['amount'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">Belum ada detail komponen.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
