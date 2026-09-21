<div class="container-fluid py-3">
    <?php $current = 'dashboard'; include APPPATH . 'views/payroll/_menu.php'; ?>
    <?php $status_labels = ['draft' => 'Draft', 'reviewed' => 'Ditinjau', 'finalized' => 'Final', 'paid' => 'Dibayar']; ?>

    <?php
    $period_summary = !empty($period_summary) ? $period_summary[0] : [
        'total_period' => 0,
        'draft_period' => 0,
        'reviewed_period' => 0,
        'finalized_period' => 0,
        'paid_period' => 0
    ];
    $slip_summary = !empty($slip_summary) ? $slip_summary[0] : [
        'total_slip' => 0,
        'draft_slip' => 0,
        'reviewed_slip' => 0,
        'finalized_slip' => 0,
        'paid_slip' => 0,
        'total_net' => 0
    ];
    ?>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card"><div class="card-body"><small>Total Periode</small><h4><?= (int) $period_summary['total_period'] ?></h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small>Draft Periode</small><h4><?= (int) $period_summary['draft_period'] ?></h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small>Reviewed Periode</small><h4><?= (int) $period_summary['reviewed_period'] ?></h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small>Paid Periode</small><h4><?= (int) $period_summary['paid_period'] ?></h4></div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card"><div class="card-body"><small>Jumlah Slip (Periode Terbaru)</small><h4><?= (int) $slip_summary['total_slip'] ?></h4></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><small>Slip Terselesaikan</small><h4><?= (int) $slip_summary['finalized_slip'] ?></h4></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><small>Total Pembayaran Bersih</small><h4>Rp <?= number_format((float) $slip_summary['total_net'], 0, ',', '.') ?></h4></div></div></div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Periode Terbaru</h5>
            <a href="<?= base_url('payroll/run_payroll') ?>" class="btn btn-primary btn-sm">Proses Payroll</a>
        </div>
        <div class="card-body">
            <?php if (!empty($latest_period)): ?>
                <p class="mb-1"><strong><?= $latest_period['period_label'] ?></strong> (<?= $latest_period['period_key'] ?>)</p>
                <p class="mb-1">Tanggal: <?= $latest_period['period_start'] ?> s/d <?= $latest_period['period_end'] ?></p>
                <p class="mb-0">Status: <span class="badge bg-info text-dark"><?= $status_labels[$latest_period['status']] ?? ucfirst($latest_period['status']) ?></span></p>
            <?php else: ?>
                <p class="mb-0 text-muted">Belum ada periode payroll.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h5 class="mb-0">Riwayat Periode</h5></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Tanggal</th>
                        <th>Tanggal Bayar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_periods)): ?>
                        <?php foreach ($recent_periods as $row): ?>
                            <tr>
                                <td><?= $row['period_label'] ?></td>
                                <td><?= $row['period_start'] ?> - <?= $row['period_end'] ?></td>
                                <td><?= !empty($row['pay_date']) ? $row['pay_date'] : '-' ?></td>
                                <td><span class="badge bg-light text-dark border"><?= $status_labels[$row['status']] ?? ucfirst($row['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted">Belum ada data.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
