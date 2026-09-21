<div class="container-fluid py-3">
    <?php $current = 'payroll_period'; include APPPATH . 'views/payroll/_menu.php'; ?>
    <?php $status_labels = ['draft' => 'Draft', 'reviewed' => 'Ditinjau', 'finalized' => 'Final', 'paid' => 'Dibayar']; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Periode Payroll</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPeriodModal">
                <i class="bi bi-plus me-1"></i>Buat Periode Payroll
            </button>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Rentang Tanggal</th>
                        <th>Tanggal Bayar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($periods)): ?>
                        <?php foreach ($periods as $row): ?>
                            <tr>
                                <td><?= $row['period_label'] ?> (<?= $row['period_key'] ?>)</td>
                                <td><?= $row['period_start'] ?> - <?= $row['period_end'] ?></td>
                                <td><?= !empty($row['pay_date']) ? $row['pay_date'] : '-' ?></td>
                                <td>
                                    <div class="period-status-editable" data-period-id="<?= (int) $row['id'] ?>" data-status="<?= htmlspecialchars($row['status']) ?>">
                                        <span class="badge bg-light text-dark border period-status-text"><?= $status_labels[$row['status']] ?? ucfirst($row['status']) ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted">Belum ada periode payroll.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createPeriodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('payroll/create_period') ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Buat Periode Payroll</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Bulan</label>
                            <select name="month" class="form-select" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= (int) date('n') === $m ? 'selected' : '' ?>><?= str_pad($m, 2, '0', STR_PAD_LEFT) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tahun</label>
                            <input type="number" name="year" class="form-control" min="2000" max="2100" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Bayar</label>
                            <input type="date" name="pay_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Buat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const updatePeriodStatusInlineUrl = '<?= base_url('payroll/update_period_status_inline') ?>';
    const periodStatusLabelMap = {
        draft: 'Draft',
        reviewed: 'Ditinjau',
        finalized: 'Final',
        paid: 'Dibayar'
    };

    $(document).on('click', '.period-status-editable', function() {
        const cell = $(this);
        if (cell.hasClass('editing')) {
            return;
        }

        const periodId = cell.data('period-id');
        const currentStatus = String(cell.data('status') || 'draft');
        const select = $('<select class="form-select form-select-sm"><option value="draft">Draft</option><option value="reviewed">Ditinjau</option><option value="finalized">Final</option><option value="paid">Dibayar</option></select>');
        select.val(currentStatus);

        cell.addClass('editing').empty().append(select);
        select.trigger('focus');

        let committed = false;
        function cancelEdit() {
            cell.removeClass('editing').html('<span class="badge bg-light text-dark border period-status-text">' + (periodStatusLabelMap[currentStatus] || currentStatus) + '</span>');
        }

        function commitEdit() {
            if (committed) {
                return;
            }
            committed = true;

            const newStatus = String(select.val() || 'draft');
            if (newStatus === currentStatus) {
                cancelEdit();
                return;
            }

            $.ajax({
                url: updatePeriodStatusInlineUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    id: periodId,
                    status: newStatus
                }
            }).done(function(resp) {
                if (!resp || !resp.success) {
                    alert((resp && resp.message) ? resp.message : 'Gagal mengubah status periode.');
                    cancelEdit();
                    return;
                }
                cell.data('status', newStatus);
                cell.removeClass('editing').html('<span class="badge bg-light text-dark border period-status-text">' + (periodStatusLabelMap[newStatus] || newStatus) + '</span>');
            }).fail(function() {
                alert('Gagal mengubah status periode.');
                cancelEdit();
            });
        }

        select.on('change', function() {
            commitEdit();
        });

        select.on('blur', function() {
            if (!committed) {
                cancelEdit();
            }
        });
    });
</script>
