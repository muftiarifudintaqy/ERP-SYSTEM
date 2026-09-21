<div class="container-fluid py-3">
    <?php $current = 'run_payroll'; include APPPATH . 'views/payroll/_menu.php'; ?>
    <?php
    $status_labels = ['draft' => 'Draft', 'reviewed' => 'Ditinjau', 'finalized' => 'Final', 'paid' => 'Dibayar'];
    $component_type_labels = ['earning' => 'Pendapatan', 'deduction' => 'Potongan'];
    ?>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">Proses Payroll</h5></div>
        <div class="card-body">
            <form action="" method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Pilih Periode</label>
                    <select name="period_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= $period['id'] ?>" <?= (int) $selected_period_id === (int) $period['id'] ? 'selected' : '' ?>>
                                <?= $period['period_label'] ?> (<?= $status_labels[$period['status']] ?? ucfirst($period['status']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if (!empty($selected_period)): ?>
                <form action="<?= base_url('payroll/generate_payroll') ?>" method="POST" id="formGeneratePayroll" class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="period_id" value="<?= $selected_period['id'] ?>">
                    <input type="hidden" name="selected_user_ids" id="selectedUserIdsInput" value="">
                    <input type="hidden" name="override_payload_json" id="overridePayloadInput" value="">

                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#previewPayrollModal">
                        <i class="bi bi-eye me-1"></i>Preview & Pilih Karyawan
                    </button>

                    <button class="btn btn-primary" type="submit" id="btnGenerateSelected" disabled>
                        <i class="bi bi-play-circle me-1"></i>Generate Payroll Terpilih
                    </button>

                    <a href="<?= base_url('payroll/payroll_period') ?>" class="btn btn-outline-secondary">Kelola Periode</a>

                    <span class="text-muted" id="selectedUserText">0 karyawan dipilih</span>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Hasil Payroll</h5></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>Posisi</th>
                        <th>Status</th>
                        <th>Bruto</th>
                        <th>Potongan</th>
                        <th>Bersih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($slips)): ?>
                        <?php foreach ($slips as $slip): ?>
                            <tr>
                                <td>
                                    <div>
                                        <a href="<?= base_url('payroll/slip_detail?id=' . $slip['id']) ?>" class="open-slip-detail-modal" data-slip-id="<?= (int) $slip['id'] ?>">
                                            <?= htmlspecialchars($slip['full_name']) ?>
                                        </a>
                                    </div>
                                    <small class="text-muted"><?= !empty($slip['bank_name']) ? $slip['bank_name'] : '-' ?> / <?= !empty($slip['bank_account_number']) ? $slip['bank_account_number'] : '-' ?></small>
                                </td>
                                <td><?= $slip['position_name'] ?></td>
                                <td>
                                    <div class="slip-status-editable" data-slip-id="<?= (int) $slip['id'] ?>" data-status="<?= htmlspecialchars($slip['status']) ?>">
                                        <span class="badge bg-light text-dark border slip-status-text"><?= $status_labels[$slip['status']] ?? ucfirst($slip['status']) ?></span>
                                    </div>
                                </td>
                                <td>Rp <?= number_format((float) $slip['gross_amount'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format((float) $slip['deduction_amount'], 0, ',', '.') ?></td>
                                <td><strong>Rp <?= number_format((float) $slip['net_amount'], 0, ',', '.') ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted">Belum ada data payroll di periode ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="previewPayrollModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Payroll per Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm" id="previewEmployeeSearch" placeholder="Cari nama karyawan...">
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-md-end">
                            <label class="mb-0 d-flex align-items-center gap-2">
                                <input type="checkbox" id="checkAllPreviewUsers">
                                <span>Pilih semua karyawan</span>
                            </label>
                            <span class="text-muted" id="previewSelectedText">0 karyawan dipilih</span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($preview_employees)): ?>
                    <div class="accordion" id="previewPayrollAccordion">
                        <?php foreach ($preview_employees as $index => $employee): ?>
                            <?php
                            $preview_user_id = (int) $employee['user_id'];
                            $items = $preview_items_map[$preview_user_id] ?? [];
                            $totals = $preview_totals_map[$preview_user_id] ?? ['gross' => 0, 'deduction' => 0, 'net' => 0];
                            $heading_id = 'previewHeading' . $preview_user_id;
                            $collapse_id = 'previewCollapse' . $preview_user_id;
                            ?>
                            <div class="accordion-item mb-2 border rounded">
                                <h2 class="accordion-header" id="<?= $heading_id ?>">
                                    <button
                                        class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#<?= $collapse_id ?>"
                                        aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                                        aria-controls="<?= $collapse_id ?>"
                                    >
                                        <div class="d-flex w-100 justify-content-between align-items-center pe-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="checkbox" class="preview-user-check" value="<?= $preview_user_id ?>" onclick="event.stopPropagation();">
                                                <div>
                                                    <strong class="preview-employee-name"><?= htmlspecialchars($employee['full_name']) ?></strong>
                                                    <div class="small text-muted"><?= htmlspecialchars($employee['position_name']) ?></div>
                                                </div>
                                            </div>
                                            <div class="small text-muted">
                                                Estimasi Bersih: <strong>Rp <?= number_format((float) $totals['net'], 0, ',', '.') ?></strong>
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="<?= $collapse_id ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="<?= $heading_id ?>" data-bs-parent="#previewPayrollAccordion">
                                    <div class="accordion-body">
                                        <div class="row mb-2">
                                            <div class="col-md-4"><small class="text-muted">Total Pendapatan</small><div><span class="preview-gross" data-user-id="<?= $preview_user_id ?>">Rp <?= number_format((float) $totals['gross'], 0, ',', '.') ?></span></div></div>
                                            <div class="col-md-4"><small class="text-muted">Total Potongan</small><div><span class="preview-deduction" data-user-id="<?= $preview_user_id ?>">Rp <?= number_format((float) $totals['deduction'], 0, ',', '.') ?></span></div></div>
                                            <div class="col-md-4"><small class="text-muted">Grand Total</small><div><strong><span class="preview-net" data-user-id="<?= $preview_user_id ?>">Rp <?= number_format((float) $totals['net'], 0, ',', '.') ?></span></strong></div></div>
                                        </div>

                                        <div class="table-responsive">
                                            <div class="mb-2 text-end">
                                                <button type="button" class="btn btn-outline-success btn-sm btn-add-preview-component" data-user-id="<?= $preview_user_id ?>">
                                                    + Komponen
                                                </button>
                                            </div>
                                            <table class="table table-bordered table-sm mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Komponen</th>
                                                        <th>Tipe</th>
                                                        <th>Perhitungan</th>
                                                        <th>Nominal</th>
                                                        <th>Qty</th>
                                                        <th>Satuan</th>
                                                        <th>Total</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody data-user-id="<?= $preview_user_id ?>">
                                                    <?php if (!empty($items)): ?>
                                                        <?php foreach ($items as $item): ?>
                                                            <?php
                                                            $item_component_id = (int) $item['payroll_component_id'];
                                                            $item_qty = (float) ($item['qty'] ?? 0);
                                                            $item_unit_amount = (float) ($item['unit_amount'] ?? 0);
                                                            $item_unit_label = (string) ($item['unit_label'] ?? 'periode');
                                                            $item_total = (float) ($item['amount'] ?? 0);
                                                            $formula_badge = (string) ($item['formula_badge'] ?? 'Perhitungan');
                                                            ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($item['component_name_snapshot']) ?></td>
                                                                <td><?= $component_type_labels[$item['component_type_snapshot']] ?? $item['component_type_snapshot'] ?></td>
                                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($formula_badge) ?></span></td>
                                                                <td>
                                                                    <input type="hidden" class="preview-unit-amount" data-user-id="<?= $preview_user_id ?>" data-component-id="<?= $item_component_id ?>" value="<?= (float) $item_unit_amount ?>">
                                                                    <div class="preview-editable-cell" data-field="unit_amount" data-user-id="<?= $preview_user_id ?>" data-component-id="<?= $item_component_id ?>">
                                                                        <span class="preview-edit-display">0</span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <input type="hidden" class="preview-qty" data-user-id="<?= $preview_user_id ?>" data-component-id="<?= $item_component_id ?>" value="<?= (float) $item_qty ?>">
                                                                    <div class="preview-editable-cell" data-field="qty" data-user-id="<?= $preview_user_id ?>" data-component-id="<?= $item_component_id ?>">
                                                                        <span class="preview-edit-display">0</span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <input type="hidden" class="preview-unit" data-user-id="<?= $preview_user_id ?>" data-component-id="<?= $item_component_id ?>" value="<?= htmlspecialchars($item_unit_label) ?>">
                                                                    <div class="preview-editable-cell" data-field="unit" data-user-id="<?= $preview_user_id ?>" data-component-id="<?= $item_component_id ?>">
                                                                        <span class="preview-edit-display">-</span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <input type="hidden" class="preview-total" data-user-id="<?= $preview_user_id ?>" data-component-id="<?= $item_component_id ?>" data-component-type="<?= $item['component_type_snapshot'] ?>" value="<?= (float) $item_total ?>">
                                                                    <div class="preview-editable-cell" data-field="total" data-user-id="<?= $preview_user_id ?>" data-component-id="<?= $item_component_id ?>">
                                                                        <span class="preview-edit-display">0</span>
                                                                    </div>
                                                                </td>
                                                                <td class="text-center text-muted">-</td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr><td colspan="8" class="text-center text-muted">Tidak ada komponen terhitung.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted">Tidak ada data karyawan dari `user_profile` untuk periode ini.</div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="applyPreviewSelection">Gunakan Pilihan Ini</button>
            </div>
        </div>
    </div>
</div>

<style>
    .slip-status-editable {
        cursor: pointer;
        display: inline-block;
        min-width: 110px;
    }

    .slip-status-inline-select {
        min-width: 130px;
    }

    .preview-editable-cell {
        cursor: pointer;
        min-height: 31px;
        display: flex;
        align-items: center;
        padding: 0.15rem 0.35rem;
        border: 1px solid transparent;
        border-radius: 0.2rem;
    }

    .preview-editable-cell:hover {
        border-color: #dee2e6;
        background: #f8f9fa;
    }

    .preview-editable-cell.editing {
        border-color: #86b7fe;
        background: #fff;
    }

    .preview-edit-display {
        width: 100%;
        text-align: right;
        white-space: nowrap;
    }

    .preview-editable-cell[data-field="unit"] .preview-edit-display {
        text-align: left;
    }
    .preview-editable-cell[data-field="component_name"] .preview-edit-display {
        text-align: left;
    }
    .preview-editable-cell[data-field="component_type"] .preview-edit-display {
        text-align: left;
    }
    .preview-inline-editor {
        width: 100%;
        border: 0;
        outline: 0;
        padding: 0;
        margin: 0;
        background: transparent;
    }
</style>

<div class="modal fade" id="processingPayrollModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <div>Sedang memproses payroll...</div>
                <small class="text-muted">Mohon tunggu, jangan tutup halaman.</small>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="slipDetailPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Slip Payroll</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="slipDetailPreviewBody">
                <div class="p-4 text-center text-muted">Pilih karyawan untuk melihat detail slip.</div>
            </div>
        </div>
    </div>
</div>

<script>
    const updateSlipStatusInlineUrl = '<?= base_url('payroll/update_slip_status_inline') ?>';
    const slipDetailModalUrl = '<?= base_url('payroll/slip_detail_modal?id=') ?>';
    const slipStatusLabelMap = {
        draft: 'Draft',
        reviewed: 'Ditinjau',
        finalized: 'Final',
        paid: 'Dibayar'
    };

    function formatIdNumber(value, maxFractionDigits) {
        const number = Number(value || 0);
        return number.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: maxFractionDigits
        });
    }

    function cleanCurrencyInput(value) {
        const cleaned = String(value || '').replace(/[^\d]/g, '');
        if (cleaned === '') {
            return 0;
        }
        return parseInt(cleaned, 10) || 0;
    }

    function cleanQtyInput(value) {
        const normalized = String(value || '').replace(',', '.').replace(/[^\d.]/g, '');
        const parsed = parseFloat(normalized);
        if (isNaN(parsed) || parsed < 0) {
            return 0;
        }
        return parsed;
    }

    function componentTypeLabel(value) {
        return String(value) === 'deduction' ? 'Potongan' : 'Pendapatan';
    }

    function getCellRawValue(cell) {
        const userId = cell.data('user-id');
        const componentId = cell.data('component-id');
        const field = String(cell.data('field'));

        if (field === 'unit_amount') {
            return $('.preview-unit-amount[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || 0;
        }
        if (field === 'qty') {
            return $('.preview-qty[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || 0;
        }
        if (field === 'unit') {
            return $('.preview-unit[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || '';
        }
        if (field === 'total') {
            return $('.preview-total[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || 0;
        }
        if (field === 'component_name') {
            return $('.preview-component-name[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || '';
        }
        if (field === 'component_type') {
            return $('.preview-component-type[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || 'earning';
        }
        return '';
    }

    function setCellRawValue(cell, value) {
        const userId = cell.data('user-id');
        const componentId = cell.data('component-id');
        const field = String(cell.data('field'));

        if (field === 'unit_amount') {
            $('.preview-unit-amount[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val(value);
        } else if (field === 'qty') {
            $('.preview-qty[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val(value);
        } else if (field === 'unit') {
            $('.preview-unit[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val(value);
        } else if (field === 'total') {
            $('.preview-total[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val(value);
        } else if (field === 'component_name') {
            $('.preview-component-name[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val(value);
        } else if (field === 'component_type') {
            const safeType = value === 'deduction' ? 'deduction' : 'earning';
            $('.preview-component-type[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val(safeType);
            $('.preview-total[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').attr('data-component-type', safeType);
        }
    }

    function refreshEditableDisplays() {
        $('.preview-editable-cell').each(function() {
            const cell = $(this);
            const userId = cell.data('user-id');
            const componentId = cell.data('component-id');
            const field = String(cell.data('field'));
            let displayValue = '-';

            if (field === 'unit_amount') {
                const raw = parseFloat($('.preview-unit-amount[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || 0);
                displayValue = formatIdNumber(raw, 0);
            } else if (field === 'qty') {
                const raw = parseFloat($('.preview-qty[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || 0);
                displayValue = formatIdNumber(raw, 2);
            } else if (field === 'unit') {
                displayValue = $('.preview-unit[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || '-';
            } else if (field === 'total') {
                const raw = parseFloat($('.preview-total[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || 0);
                displayValue = formatIdNumber(raw, 0);
            } else if (field === 'component_name') {
                displayValue = $('.preview-component-name[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || '-';
            } else if (field === 'component_type') {
                const rawType = $('.preview-component-type[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || 'earning';
                displayValue = componentTypeLabel(rawType);
            }

            cell.find('.preview-edit-display').text(displayValue);
        });
    }

    function recalcComponentTotal(userId, componentId) {
        const qty = parseFloat($('.preview-qty[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || 0);
        const unitAmount = parseFloat($('.preview-unit-amount[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || 0);
        const total = Math.round(qty * unitAmount);
        $('.preview-total[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val(total);
    }

    function buildCustomComponentRow(userId, componentId) {
        return `
            <tr class="preview-custom-row">
                <td>
                    <input type="hidden" class="preview-component-name" data-user-id="${userId}" data-component-id="${componentId}" value="Komponen Tambahan">
                    <div class="preview-editable-cell" data-field="component_name" data-user-id="${userId}" data-component-id="${componentId}">
                        <span class="preview-edit-display">Komponen Tambahan</span>
                    </div>
                </td>
                <td>
                    <input type="hidden" class="preview-component-type" data-user-id="${userId}" data-component-id="${componentId}" value="earning">
                    <div class="preview-editable-cell" data-field="component_type" data-user-id="${userId}" data-component-id="${componentId}">
                        <span class="preview-edit-display">Pendapatan</span>
                    </div>
                </td>
                <td><span class="badge bg-light text-dark border">Manual (Periode)</span></td>
                <td>
                    <input type="hidden" class="preview-unit-amount" data-user-id="${userId}" data-component-id="${componentId}" value="0">
                    <div class="preview-editable-cell" data-field="unit_amount" data-user-id="${userId}" data-component-id="${componentId}">
                        <span class="preview-edit-display">0</span>
                    </div>
                </td>
                <td>
                    <input type="hidden" class="preview-qty" data-user-id="${userId}" data-component-id="${componentId}" value="1">
                    <div class="preview-editable-cell" data-field="qty" data-user-id="${userId}" data-component-id="${componentId}">
                        <span class="preview-edit-display">1</span>
                    </div>
                </td>
                <td>
                    <input type="hidden" class="preview-unit" data-user-id="${userId}" data-component-id="${componentId}" value="periode">
                    <div class="preview-editable-cell" data-field="unit" data-user-id="${userId}" data-component-id="${componentId}">
                        <span class="preview-edit-display">periode</span>
                    </div>
                </td>
                <td>
                    <input type="hidden" class="preview-total" data-user-id="${userId}" data-component-id="${componentId}" data-component-type="earning" value="0">
                    <div class="preview-editable-cell" data-field="total" data-user-id="${userId}" data-component-id="${componentId}">
                        <span class="preview-edit-display">0</span>
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-preview-component" data-user-id="${userId}" data-component-id="${componentId}">Hapus</button>
                </td>
            </tr>
        `;
    }

    function getSelectedPreviewUserIds() {
        const ids = [];
        $('.preview-user-check:checked').each(function() {
            ids.push($(this).val());
        });
        return ids;
    }

    function updatePreviewSelectionLabel() {
        const selectedIds = getSelectedPreviewUserIds();
        const totalUsers = $('.preview-user-check').length;
        $('#previewSelectedText').text(selectedIds.length + ' karyawan dipilih');
        $('#checkAllPreviewUsers').prop('checked', totalUsers > 0 && selectedIds.length === totalUsers);
    }

    function filterPreviewEmployees(keyword) {
        const search = String(keyword || '').toLowerCase().trim();
        $('#previewPayrollAccordion .accordion-item').each(function() {
            const name = $(this).find('.preview-employee-name').first().text().toLowerCase();
            $(this).toggle(name.indexOf(search) !== -1);
        });
    }

    function applySelectionToForm() {
        const selectedIds = getSelectedPreviewUserIds();
        $('#selectedUserIdsInput').val(selectedIds.join(','));
        $('#selectedUserText').text(selectedIds.length + ' karyawan dipilih');
        $('#btnGenerateSelected').prop('disabled', selectedIds.length === 0);

        const payload = {
            items: {},
            custom_items: {}
        };

        selectedIds.forEach(function(userId) {
            payload.items[userId] = {};
            payload.custom_items[userId] = [];

            $('.preview-total[data-user-id="' + userId + '"]').each(function() {
                const row = $(this).closest('tr');
                const componentId = String($(this).data('component-id'));
                const qty = $('.preview-qty[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val();
                const unitAmount = $('.preview-unit-amount[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val();
                const unit = $('.preview-unit[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val();
                const total = $(this).val();
                const componentType = String($(this).data('component-type') || 'earning');
                const componentName = $('.preview-component-name[data-user-id="' + userId + '"][data-component-id="' + componentId + '"]').val() || '';

                if (row.hasClass('preview-custom-row')) {
                    payload.custom_items[userId].push({
                        component_name: componentName,
                        component_type: componentType,
                        qty: qty,
                        unit_amount: unitAmount,
                        unit: unit,
                        total: total
                    });
                    return;
                }

                payload.items[userId][componentId] = {
                    qty: qty,
                    unit_amount: unitAmount,
                    unit: unit,
                    total: total
                };
            });

        });

        $('#overridePayloadInput').val(JSON.stringify(payload));
    }

    function formatRupiah(value) {
        const number = parseFloat(value || 0);
        return 'Rp ' + number.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    function recalcEmployeePreview(userId) {
        let gross = 0;
        let deduction = 0;

        $('.preview-total[data-user-id="' + userId + '"]').each(function() {
            const amount = parseFloat($(this).val() || 0);
            const type = String($(this).data('component-type') || 'earning');
            if (type === 'deduction') {
                deduction += amount;
            } else {
                gross += amount;
            }
        });

        $('.preview-gross[data-user-id="' + userId + '"]').text(formatRupiah(gross));
        $('.preview-deduction[data-user-id="' + userId + '"]').text(formatRupiah(deduction));
        $('.preview-net[data-user-id="' + userId + '"]').text(formatRupiah(gross - deduction));
    }

    $('#checkAllPreviewUsers').on('change', function() {
        $('.preview-user-check').prop('checked', $(this).is(':checked'));
        updatePreviewSelectionLabel();
    });

    $(document).on('change', '.preview-user-check', function() {
        updatePreviewSelectionLabel();
    });

    $('#previewEmployeeSearch').on('input', function() {
        filterPreviewEmployees($(this).val());
    });

    $('#applyPreviewSelection').on('click', function() {
        applySelectionToForm();
        const modalEl = document.getElementById('previewPayrollModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    });

    $(document).on('click', '.preview-editable-cell', function() {
        const cell = $(this);
        if (cell.hasClass('editing')) {
            return;
        }

        const field = String(cell.data('field'));
        const rawValue = getCellRawValue(cell);
        let input;
        if (field === 'component_type') {
            input = $('<select class="preview-inline-editor"><option value="earning">Pendapatan</option><option value="deduction">Potongan</option></select>');
            input.val(rawValue === 'deduction' ? 'deduction' : 'earning');
        } else {
            input = $('<input type="text" class="preview-inline-editor">').val(rawValue);
        }
        cell.addClass('editing');
        cell.find('.preview-edit-display').empty().append(input);
        input.trigger('focus').select();

        let committed = false;
        function commitInlineEdit() {
            if (committed) {
                return;
            }
            committed = true;

            let value = input.val();
            const userId = cell.data('user-id');
            const componentId = cell.data('component-id');

            if (field === 'unit_amount' || field === 'total') {
                value = cleanCurrencyInput(value);
            } else if (field === 'qty') {
                value = cleanQtyInput(value);
            } else if (field === 'unit' || field === 'component_name') {
                value = String(value || '').trim();
            } else if (field === 'component_type') {
                value = value === 'deduction' ? 'deduction' : 'earning';
            }

            setCellRawValue(cell, value);

            if (field === 'qty' || field === 'unit_amount') {
                recalcComponentTotal(userId, componentId);
            }

            refreshEditableDisplays();
            recalcEmployeePreview(userId);
            cell.removeClass('editing');
        }

        input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                commitInlineEdit();
            } else if (e.key === 'Escape') {
                committed = true;
                cell.removeClass('editing');
                refreshEditableDisplays();
            }
        });

        input.on('blur', function() {
            commitInlineEdit();
        });

        if (field === 'component_type') {
            input.on('change', function() {
                commitInlineEdit();
            });
        }
    });

    $(document).on('click', '.btn-add-preview-component', function() {
        const userId = String($(this).data('user-id'));
        const componentId = 'custom-' + userId + '-' + Date.now();
        const tbody = $('tbody[data-user-id="' + userId + '"]');
        tbody.find('tr').each(function() {
            const td = $(this).find('td[colspan="8"]');
            if (td.length > 0) {
                $(this).remove();
            }
        });
        tbody.append(buildCustomComponentRow(userId, componentId));
        refreshEditableDisplays();
        recalcEmployeePreview(userId);
    });

    $(document).on('click', '.btn-remove-preview-component', function() {
        const userId = String($(this).data('user-id'));
        $(this).closest('tr').remove();
        refreshEditableDisplays();
        recalcEmployeePreview(userId);
    });

    $('#formGeneratePayroll').on('submit', function(e) {
        const selectedValue = $('#selectedUserIdsInput').val().trim();
        if (selectedValue === '') {
            e.preventDefault();
            alert('Pilih minimal 1 karyawan dari modal preview terlebih dahulu.');
            return;
        }

        $('#btnGenerateSelected').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Memproses...');
        const processingModal = new bootstrap.Modal(document.getElementById('processingPayrollModal'));
        processingModal.show();
    });

    $(document).on('click', '.slip-status-editable', function() {
        const cell = $(this);
        if (cell.hasClass('editing')) {
            return;
        }

        const slipId = cell.data('slip-id');
        const currentStatus = String(cell.data('status') || 'draft');
        const select = $('<select class="form-select form-select-sm slip-status-inline-select"><option value="draft">Draft</option><option value="reviewed">Ditinjau</option><option value="finalized">Final</option><option value="paid">Dibayar</option></select>');
        select.val(currentStatus);

        cell.addClass('editing').empty().append(select);
        select.trigger('focus');

        let committed = false;
        function cancelEdit() {
            cell.removeClass('editing').html('<span class="badge bg-light text-dark border slip-status-text">' + (slipStatusLabelMap[currentStatus] || currentStatus) + '</span>');
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
                url: updateSlipStatusInlineUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    slip_id: slipId,
                    status: newStatus
                }
            }).done(function(resp) {
                if (!resp || !resp.success) {
                    alert((resp && resp.message) ? resp.message : 'Gagal mengubah status slip.');
                    cancelEdit();
                    return;
                }
                cell.data('status', newStatus);
                cell.removeClass('editing').html('<span class="badge bg-light text-dark border slip-status-text">' + (slipStatusLabelMap[newStatus] || newStatus) + '</span>');
            }).fail(function() {
                alert('Gagal mengubah status slip.');
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

    $(document).on('click', '.open-slip-detail-modal', function(e) {
        e.preventDefault();
        const slipId = $(this).data('slip-id');
        const modalEl = document.getElementById('slipDetailPreviewModal');
        const bodyEl = $('#slipDetailPreviewBody');
        bodyEl.html('<div class="p-4 text-center text-muted">Memuat detail slip...</div>');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        $.ajax({
            url: slipDetailModalUrl + slipId,
            method: 'GET',
            dataType: 'html'
        }).done(function(html) {
            bodyEl.html(html);
        }).fail(function() {
            bodyEl.html('<div class="p-4 text-center text-danger">Gagal memuat detail slip.</div>');
        });
    });

    $('#slipDetailPreviewModal').on('hidden.bs.modal', function() {
        $('#slipDetailPreviewBody').html('<div class="p-4 text-center text-muted">Pilih karyawan untuk melihat detail slip.</div>');
    });

    refreshEditableDisplays();
    updatePreviewSelectionLabel();
    $('.preview-user-check').each(function() {
        recalcEmployeePreview($(this).val());
    });
</script>
