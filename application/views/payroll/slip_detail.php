<div class="container-fluid py-3">
    <?php $current = 'run_payroll'; include APPPATH . 'views/payroll/_menu.php'; ?>
    <?php $status_labels = ['draft' => 'Draft', 'reviewed' => 'Ditinjau', 'finalized' => 'Final', 'paid' => 'Dibayar']; ?>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Detail Slip - <?= $slip['full_name'] ?></h5>
            <div class="d-flex gap-2">
                <a href="<?= base_url('payroll/slip_pdf?id=' . $slip['id']) ?>" class="btn btn-outline-primary btn-sm" target="_blank">Unduh PDF</a>
                <a href="<?= base_url('payroll/run_payroll?period_id=' . $slip['payroll_period_id']) ?>" class="btn btn-outline-secondary btn-sm">Kembali</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><small>Periode</small><p><?= $slip['period_label'] ?></p></div>
                <div class="col-md-4"><small>Posisi</small><p><?= $slip['position_name'] ?></p></div>
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
        <div class="card-header"><h5 class="mb-0">Komponen</h5></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th>Komponen</th>
                        <th>Tipe</th>
                        <th>Calculation</th>
                        <th>Qty</th>
                        <th>Nominal</th>
                        <th width="120">Edit</th>
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
                                <td>
                                    <?php if ((int) $item['can_be_edited_in_payroll'] === 1): ?>
                                        <button
                                            class="btn btn-outline-primary btn-sm btn-edit-slip-item"
                                            data-item-id="<?= $item['id'] ?>"
                                            data-component-name="<?= htmlspecialchars($item['component_name_snapshot']) ?>"
                                            data-amount="<?= (float) $item['amount'] ?>"
                                            data-note="<?= htmlspecialchars((string) $item['manual_note']) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editSlipItemModal"
                                        >Edit</button>
                                    <?php else: ?>
                                        <small class="text-muted">Terkunci</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted">Belum ada komponen payroll.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editSlipItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('payroll/update_slip_item') ?>" method="POST">
                <input type="hidden" name="item_id" id="slipItemId">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Komponen Slip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Komponen: <strong id="slipItemComponentName">-</strong></p>
                    <div class="mb-2">
                        <label class="form-label">Nominal</label>
                        <input type="number" class="form-control" name="amount" id="slipItemAmount" min="0" required>
                    </div>
                    <div>
                        <label class="form-label">Catatan Manual</label>
                        <input type="text" class="form-control" name="manual_note" id="slipItemNote" placeholder="Catatan perubahan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).on('click', '.btn-edit-slip-item', function() {
        $('#slipItemId').val($(this).data('item-id'));
        $('#slipItemComponentName').text($(this).data('component-name'));
        $('#slipItemAmount').val($(this).data('amount'));
        $('#slipItemNote').val($(this).data('note'));
    });
</script>
