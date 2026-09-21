<?php
$type_labels = [
    'earning' => 'Pendapatan',
    'deduction' => 'Potongan'
];

$calc_labels = [
    'fixed' => 'Tetap',
    'per_day' => 'Per Hari',
    'manual' => 'Manual',
    'future_kpi_based' => 'KPI'
];

$day_basis_labels = [
    'none' => 'Tidak Ada',
    'working_day' => 'Hari Kerja',
    'attendance_day' => 'Hari Kehadiran',
    'wfo_day' => 'Hari WFO'
];
?>

<style>
    .component-row-saving {
        opacity: 0.6;
    }

    .component-editable-cell {
        cursor: pointer;
        min-height: 31px;
        display: flex;
        align-items: center;
        padding: 0.15rem 0.35rem;
        border: 1px solid transparent;
        border-radius: 0.2rem;
    }

    .component-editable-cell:hover {
        border-color: #dee2e6;
        background: #f8f9fa;
    }

    .component-editable-cell.editing {
        border-color: #86b7fe;
        background: #fff;
    }

    .component-edit-display {
        width: 100%;
        white-space: nowrap;
        text-align: left;
    }

    .component-editable-cell[data-field="amount"] .component-edit-display {
        text-align: right;
    }

    .component-inline-editor {
        width: 100%;
        border: 0;
        outline: 0;
        padding: 0;
        margin: 0;
        background: transparent;
    }
</style>

<div class="container-fluid py-3">
    <?php $current = 'master_components'; include APPPATH . 'views/payroll/_menu.php'; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Master Komponen Payroll</h5>
            <button class="btn btn-primary" id="btnAddComponentInline">
                <i class="bi bi-plus me-1"></i>Tambah Komponen
            </button>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm table-hover mb-0" id="masterComponentsTable">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Kalkulasi</th>
                        <th>Berdasarkan</th>
                        <th>Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="masterComponentsBody">
                    <?php if (!empty($components)): ?>
                        <?php foreach ($components as $row): ?>
                            <tr data-id="<?= (int) $row['id'] ?>">
                                <td>
                                    <input type="hidden" class="component-name-raw" value="<?= htmlspecialchars($row['name']) ?>">
                                    <div class="component-editable-cell" data-field="name"><span class="component-edit-display">-</span></div>
                                </td>
                                <td>
                                    <input type="hidden" class="component-type-raw" value="<?= htmlspecialchars($row['component_type']) ?>">
                                    <div class="component-editable-cell" data-field="component_type"><span class="component-edit-display">-</span></div>
                                </td>
                                <td>
                                    <input type="hidden" class="component-calc-raw" value="<?= htmlspecialchars($row['calculation_type']) ?>">
                                    <div class="component-editable-cell" data-field="calculation_type"><span class="component-edit-display">-</span></div>
                                </td>
                                <td>
                                    <input type="hidden" class="component-day-basis-raw" value="<?= htmlspecialchars($row['day_basis']) ?>">
                                    <div class="component-editable-cell" data-field="day_basis"><span class="component-edit-display">-</span></div>
                                </td>
                                <td>
                                    <input type="hidden" class="component-amount-raw" value="<?= (float) $row['default_amount'] ?>">
                                    <div class="component-editable-cell" data-field="amount"><span class="component-edit-display">Rp 0</span></div>
                                </td>
                                <td class="text-center align-middle">
                                    <div class="form-check form-switch m-0 d-inline-block">
                                        <input type="checkbox" class="form-check-input component-active-toggle" <?= (int) $row['is_active'] === 1 ? 'checked' : '' ?>>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="masterComponentsEmptyRow"><td colspan="6" class="text-center text-muted">Belum ada komponen payroll.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const saveComponentInlineUrl = '<?= base_url('payroll/update_component_inline') ?>';
    const rowSaving = {};

    const componentTypeLabels = {
        earning: 'Pendapatan',
        deduction: 'Potongan'
    };

    const componentCalcLabels = {
        fixed: 'Tetap',
        per_day: 'Per Hari',
        manual: 'Manual',
        future_kpi_based: 'KPI'
    };

    const componentDayBasisLabels = {
        none: 'Tidak Ada',
        working_day: 'Hari Kerja',
        attendance_day: 'Hari Kehadiran',
        wfo_day: 'Hari WFO'
    };

    function formatRupiahNumber(value) {
        const number = Number(value || 0);
        return 'Rp ' + number.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    function cleanCurrencyInput(value) {
        const cleaned = String(value || '').replace(/[^\d]/g, '');
        if (cleaned === '') {
            return 0;
        }
        return parseInt(cleaned, 10) || 0;
    }

    function formatThousandInput(value) {
        const cleaned = String(value || '').replace(/[^\d]/g, '');
        if (cleaned === '') {
            return '';
        }
        return Number(cleaned).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    function getRowFieldRaw($row, field) {
        if (field === 'name') {
            return $row.find('.component-name-raw').val() || '';
        }
        if (field === 'component_type') {
            return $row.find('.component-type-raw').val() || 'earning';
        }
        if (field === 'calculation_type') {
            return $row.find('.component-calc-raw').val() || 'fixed';
        }
        if (field === 'day_basis') {
            return $row.find('.component-day-basis-raw').val() || 'none';
        }
        if (field === 'amount') {
            return $row.find('.component-amount-raw').val() || 0;
        }
        return '';
    }

    function setRowFieldRaw($row, field, value) {
        if (field === 'name') {
            $row.find('.component-name-raw').val(String(value || '').trim());
        } else if (field === 'component_type') {
            $row.find('.component-type-raw').val(value === 'deduction' ? 'deduction' : 'earning');
        } else if (field === 'calculation_type') {
            const allowed = ['fixed', 'per_day', 'manual', 'future_kpi_based'];
            $row.find('.component-calc-raw').val(allowed.includes(value) ? value : 'fixed');
        } else if (field === 'day_basis') {
            const allowed = ['none', 'working_day', 'attendance_day', 'wfo_day'];
            $row.find('.component-day-basis-raw').val(allowed.includes(value) ? value : 'none');
        } else if (field === 'amount') {
            $row.find('.component-amount-raw').val(cleanCurrencyInput(value));
        }
    }

    function refreshRowDisplays($row) {
        $row.find('.component-editable-cell').each(function() {
            const $cell = $(this);
            const field = String($cell.data('field'));
            let display = '-';

            if (field === 'name') {
                display = getRowFieldRaw($row, 'name') || '-';
            } else if (field === 'component_type') {
                const raw = getRowFieldRaw($row, 'component_type');
                display = componentTypeLabels[raw] || componentTypeLabels.earning;
            } else if (field === 'calculation_type') {
                const raw = getRowFieldRaw($row, 'calculation_type');
                display = componentCalcLabels[raw] || componentCalcLabels.fixed;
            } else if (field === 'day_basis') {
                const raw = getRowFieldRaw($row, 'day_basis');
                display = componentDayBasisLabels[raw] || componentDayBasisLabels.none;
            } else if (field === 'amount') {
                display = formatRupiahNumber(getRowFieldRaw($row, 'amount'));
            }

            $cell.find('.component-edit-display').text(display);
        });
    }

    function refreshAllDisplays() {
        $('#masterComponentsBody tr[data-id], #masterComponentsBody tr[data-new="1"]').each(function() {
            refreshRowDisplays($(this));
        });
    }

    function getRowSaveKey($row) {
        if ($row.data('id')) {
            return 'id-' + $row.data('id');
        }
        let key = $row.attr('data-save-key');
        if (!key) {
            key = 'new-' + Date.now() + '-' + Math.floor(Math.random() * 10000);
            $row.attr('data-save-key', key);
        }
        return key;
    }

    function saveComponentRow($row) {
        const saveKey = getRowSaveKey($row);
        if (rowSaving[saveKey]) {
            return;
        }

        const payload = {
            id: $row.data('id') || '',
            name: (getRowFieldRaw($row, 'name') || '').trim(),
            component_type: getRowFieldRaw($row, 'component_type'),
            calculation_type: getRowFieldRaw($row, 'calculation_type'),
            day_basis: getRowFieldRaw($row, 'day_basis'),
            default_amount: getRowFieldRaw($row, 'amount') || 0,
            is_active: $row.find('.component-active-toggle').is(':checked') ? 1 : 0
        };

        if (payload.name === '') {
            return;
        }

        rowSaving[saveKey] = true;
        $row.addClass('component-row-saving');

        $.ajax({
            url: saveComponentInlineUrl,
            method: 'POST',
            dataType: 'json',
            data: payload
        }).done(function(resp) {
            if (!resp || !resp.success) {
                alert((resp && resp.message) ? resp.message : 'Gagal menyimpan komponen.');
                return;
            }

            if (String($row.data('id') || '') === '' && resp.id) {
                $row.attr('data-id', resp.id);
                $row.removeAttr('data-new');
                $row.removeClass('table-warning-subtle');
            }
        }).fail(function() {
            alert('Gagal menyimpan komponen.');
        }).always(function() {
            rowSaving[saveKey] = false;
            $row.removeClass('component-row-saving');
        });
    }

    function buildEmptyComponentRowHtml() {
        return `
            <tr data-id="" data-new="1" class="table-warning-subtle">
                <td>
                    <input type="hidden" class="component-name-raw" value="">
                    <div class="component-editable-cell" data-field="name"><span class="component-edit-display">-</span></div>
                </td>
                <td>
                    <input type="hidden" class="component-type-raw" value="earning">
                    <div class="component-editable-cell" data-field="component_type"><span class="component-edit-display">Pendapatan</span></div>
                </td>
                <td>
                    <input type="hidden" class="component-calc-raw" value="fixed">
                    <div class="component-editable-cell" data-field="calculation_type"><span class="component-edit-display">Tetap</span></div>
                </td>
                <td>
                    <input type="hidden" class="component-day-basis-raw" value="none">
                    <div class="component-editable-cell" data-field="day_basis"><span class="component-edit-display">Tidak Ada</span></div>
                </td>
                <td>
                    <input type="hidden" class="component-amount-raw" value="0">
                    <div class="component-editable-cell" data-field="amount"><span class="component-edit-display">Rp 0</span></div>
                </td>
                <td class="text-center align-middle">
                    <div class="form-check form-switch m-0 d-inline-block">
                        <input type="checkbox" class="form-check-input component-active-toggle" checked>
                    </div>
                </td>
            </tr>
        `;
    }

    $('#btnAddComponentInline').on('click', function() {
        $('#masterComponentsEmptyRow').remove();
        $('#masterComponentsBody').prepend(buildEmptyComponentRowHtml());
        refreshAllDisplays();
    });

    $(document).on('click', '.component-editable-cell', function() {
        const $cell = $(this);
        if ($cell.hasClass('editing')) {
            return;
        }

        const $row = $cell.closest('tr');
        const field = String($cell.data('field'));
        const raw = getRowFieldRaw($row, field);
        let $editor;

        if (field === 'component_type') {
            $editor = $('<select class="component-inline-editor"><option value="earning">Pendapatan</option><option value="deduction">Potongan</option></select>');
            $editor.val(raw === 'deduction' ? 'deduction' : 'earning');
        } else if (field === 'calculation_type') {
            $editor = $('<select class="component-inline-editor"><option value="fixed">Tetap</option><option value="per_day">Per Hari</option><option value="manual">Manual</option><option value="future_kpi_based">KPI</option></select>');
            $editor.val(raw || 'fixed');
        } else if (field === 'day_basis') {
            $editor = $('<select class="component-inline-editor"><option value="none">Tidak Ada</option><option value="working_day">Hari Kerja</option><option value="attendance_day">Hari Kehadiran</option><option value="wfo_day">Hari WFO</option></select>');
            $editor.val(raw || 'none');
        } else if (field === 'amount') {
            $editor = $('<input type="text" class="component-inline-editor">').val(formatThousandInput(raw));
        } else {
            $editor = $('<input type="text" class="component-inline-editor">').val(raw);
        }

        $cell.addClass('editing');
        $cell.find('.component-edit-display').empty().append($editor);
        $editor.trigger('focus').select();

        let committed = false;
        function commitEdit() {
            if (committed) {
                return;
            }
            committed = true;

            let value = $editor.val();
            if (field === 'amount') {
                value = cleanCurrencyInput(value);
            } else if (field === 'name') {
                value = String(value || '').trim();
            }

            setRowFieldRaw($row, field, value);
            $cell.removeClass('editing');
            refreshRowDisplays($row);
            saveComponentRow($row);
        }

        $editor.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                commitEdit();
            } else if (e.key === 'Escape') {
                committed = true;
                $cell.removeClass('editing');
                refreshRowDisplays($row);
            }
        });

        $editor.on('blur', function() {
            commitEdit();
        });

        if (field === 'amount') {
            $editor.on('input', function() {
                $(this).val(formatThousandInput($(this).val()));
            });
        }

        if (field === 'component_type' || field === 'calculation_type' || field === 'day_basis') {
            $editor.on('change', function() {
                commitEdit();
            });
        }
    });

    $(document).on('change', '.component-active-toggle', function() {
        const $row = $(this).closest('tr');
        saveComponentRow($row);
    });

    refreshAllDisplays();
</script>
