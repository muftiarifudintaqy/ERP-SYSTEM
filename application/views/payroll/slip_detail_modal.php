<?php $status_labels = ['draft' => 'Draft', 'reviewed' => 'Ditinjau', 'finalized' => 'Final', 'paid' => 'Dibayar']; ?>

<div class="p-3">
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">Detail Slip - <?= htmlspecialchars($slip['full_name']) ?></h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><small>Periode</small><p><?= htmlspecialchars($slip['period_label']) ?></p></div>
                <div class="col-md-4"><small>Posisi</small><p><?= htmlspecialchars($slip['position_name']) ?></p></div>
                <div class="col-md-4"><small>Status</small><p><span class="badge bg-light text-dark border"><?= $status_labels[$slip['status']] ?? ucfirst($slip['status']) ?></span></p></div>
            </div>
            <div class="row">
                <div class="col-md-4"><small>Bruto</small><p>Rp <?= number_format((float) $slip['gross_amount'], 0, ',', '.') ?></p></div>
                <div class="col-md-4"><small>Potongan</small><p>Rp <?= number_format((float) $slip['deduction_amount'], 0, ',', '.') ?></p></div>
                <div class="col-md-4"><small>Bersih</small><p><strong>Rp <?= number_format((float) $slip['net_amount'], 0, ',', '.') ?></strong></p></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h6 class="mb-0">Komponen</h6></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm table-hover mb-0">
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
                                <td><?= htmlspecialchars($item['component_name_snapshot']) ?></td>
                                <td><?= ucfirst($item['component_type_snapshot']) ?></td>
                                <td><?= htmlspecialchars($item['calculation_type_snapshot']) ?></td>
                                <td><?= (float) $item['qty'] ?></td>
                                <td>Rp <?= number_format((float) $item['amount'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">Belum ada komponen payroll.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
