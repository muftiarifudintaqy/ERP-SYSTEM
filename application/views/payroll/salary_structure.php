<?php
function format_salary_position_label($position)
{
    $name = trim((string) ($position['name'] ?? ''));
    $level_name = trim((string) ($position['level_name'] ?? ''));

    if ($level_name === '' || $level_name === '-' || strcasecmp($level_name, 'No Level') === 0) {
        return $name;
    }

    return $name . ' - ' . $level_name;
}

$position_map = [];
foreach ($positions as $position) {
    $position_map[(int) $position['id']] = format_salary_position_label($position);
}
$component_map = [];
foreach ($components as $component) {
    $component_map[(int) $component['id']] = $component['name'];
}

$rows_by_position = [];
foreach ($rows as $row) {
    $position_id = (int) $row['position_id'];
    if (!isset($rows_by_position[$position_id])) {
        $rows_by_position[$position_id] = [
            'position_name' => $position_map[$position_id] ?? ('Posisi #' . $position_id),
            'items' => []
        ];
    }
    $rows_by_position[$position_id]['items'][] = $row;
}
?>

<style>
    .position-collapse-trigger {
        cursor: pointer;
        user-select: none;
    }

    .position-collapse-icon {
        transition: transform 0.2s ease;
    }

    .position-collapse-trigger[aria-expanded="true"] .position-collapse-icon {
        transform: rotate(90deg);
    }

    .salary-inline-editable {
        cursor: pointer;
        min-height: 30px;
        display: flex;
        align-items: center;
        padding: 0.15rem 0.35rem;
        border: 1px solid transparent;
        border-radius: 0.2rem;
    }

    .salary-inline-editable:hover {
        border-color: #dee2e6;
        background: #f8f9fa;
    }

    .salary-inline-editable.editing {
        border-color: #86b7fe;
        background: #fff;
    }

    .salary-inline-display {
        width: 100%;
        white-space: nowrap;
    }

    .salary-inline-input {
        width: 100%;
        border: 0;
        outline: 0;
        padding: 0;
        margin: 0;
        background: transparent;
    }

    .salary-row-saving {
        opacity: 0.6;
    }
</style>

<div class="container-fluid py-3">
    <?php $current = 'salary_structure'; include APPPATH . 'views/payroll/_menu.php'; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Struktur Gaji per Posisi</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" id="btnBulkEditSalary" data-bs-toggle="modal" data-bs-target="#bulkEditSalaryModal" disabled>
                    <i class="bi bi-pencil-square me-1"></i>Bulk Edit
                </button>
                <button class="btn btn-outline-danger" id="btnBulkDeleteSalary" data-bs-toggle="modal" data-bs-target="#bulkDeleteSalaryModal" disabled>
                    <i class="bi bi-trash me-1"></i>Bulk Hapus
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#salaryStructureModal" id="btnAddSalaryStructure">
                    <i class="bi bi-plus me-1"></i>Tambah Struktur
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
                <label class="mb-0 d-flex align-items-center gap-2">
                    <input type="checkbox" id="checkAllSalaryRows">
                    <span>Pilih semua data</span>
                </label>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="expandAllPositions">Buka Semua Posisi</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="collapseAllPositions">Tutup Semua Posisi</button>
                <span class="text-muted" id="salarySelectedInfo">0 data dipilih</span>
            </div>

            <?php if (!empty($rows_by_position)): ?>
                <div class="salary-position-list">
                    <?php $position_index = 0; ?>
                    <?php foreach ($rows_by_position as $position_id => $position_group): ?>
                        <?php $position_index++; ?>
                        <?php $collapse_id = 'salaryPositionCollapse' . (int) $position_id; ?>
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                <div
                                    class="position-collapse-trigger d-flex align-items-center gap-2"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#<?= $collapse_id ?>"
                                    role="button"
                                    tabindex="0"
                                    aria-expanded="<?= $position_index === 1 ? 'true' : 'false' ?>"
                                    aria-controls="<?= $collapse_id ?>"
                                >
                                    <i class="bi bi-chevron-right position-collapse-icon"></i>
                                    <strong><?= htmlspecialchars($position_group['position_name']) ?></strong>
                                    <span class="text-muted">(<?= count($position_group['items']) ?> komponen)</span>
                                </div>

                                <label class="mb-0 d-flex align-items-center gap-2">
                                    <input type="checkbox" class="check-position-group" data-position-id="<?= (int) $position_id ?>">
                                    <span>Pilih posisi ini</span>
                                </label>
                            </div>

                            <div id="<?= $collapse_id ?>" class="collapse <?= $position_index === 1 ? 'show' : '' ?> salary-position-collapse">
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-bordered table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th width="45"></th>
                                                <th>Komponen</th>
                                                <th>Nominal</th>
                                                <th>Aktif</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($position_group['items'] as $row): ?>
                                                <tr data-row-id="<?= (int) $row['id'] ?>">
                                                    <td>
                                                        <input type="checkbox" class="salary-row-check" data-position-id="<?= (int) $position_id ?>" value="<?= (int) $row['id'] ?>">
                                                    </td>
                                                    <td><?= $component_map[(int) $row['payroll_component_id']] ?? '-' ?></td>
                                                    <td>
                                                        <input type="hidden" class="salary-amount-raw" data-row-id="<?= (int) $row['id'] ?>" value="<?= (float) $row['amount'] ?>">
                                                        <div class="salary-inline-editable salary-edit-amount" data-row-id="<?= (int) $row['id'] ?>">
                                                            <span class="salary-inline-display">Rp <?= number_format((float) $row['amount'], 0, ',', '.') ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check form-switch m-0 d-inline-block">
                                                            <input
                                                                type="checkbox"
                                                                class="form-check-input salary-enabled-toggle"
                                                                data-row-id="<?= (int) $row['id'] ?>"
                                                                <?= (int) $row['is_enabled'] === 1 ? 'checked' : '' ?>
                                                            >
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-muted">Belum ada struktur gaji.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="salaryStructureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('payroll/save_salary_structure') ?>" method="POST" id="formSalaryStructureModal">
                <div class="modal-header">
                    <h5 class="modal-title" id="salaryStructureModalTitle">Tambah Struktur Gaji</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Posisi</label>
                            <select name="position_id" id="salaryPositionId" class="form-select" required>
                                <option value="">Pilih posisi</option>
                                <?php foreach ($positions as $position): ?>
                                    <?php $position_label = format_salary_position_label($position); ?>
                                    <option value="<?= $position['id'] ?>"><?= htmlspecialchars($position_label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Komponen</label>
                            <select name="payroll_component_id" id="salaryComponentId" class="form-select" required>
                                <option value="">Pilih komponen</option>
                                <?php foreach ($components as $component): ?>
                                    <option value="<?= $component['id'] ?>"><?= $component['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nominal</label>
                            <input type="number" name="amount" id="salaryAmount" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Aktif</label>
                            <select name="is_enabled" id="salaryEnabled" class="form-select">
                                <option value="1">Ya</option>
                                <option value="0">Tidak</option>
                            </select>
                        </div>
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

<div class="modal fade" id="bulkEditSalaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('payroll/bulk_update_salary_structure') ?>" method="POST" id="formBulkEditSalary">
                <input type="hidden" name="selected_ids" id="bulkEditSelectedIds">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Edit Struktur Gaji</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Data terpilih: <strong id="bulkEditCount">0</strong></p>
                    <small class="text-muted d-block mb-2">Isi salah satu atau keduanya, lalu simpan.</small>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Nominal Baru (opsional)</label>
                            <input type="number" name="amount" class="form-control" min="0" placeholder="Kosongkan jika tidak diubah">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status Aktif (opsional)</label>
                            <select name="is_enabled" class="form-select">
                                <option value="">Tidak diubah</option>
                                <option value="1">Ya</option>
                                <option value="0">Tidak</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="bulkDeleteSalaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('payroll/bulk_delete_salary_structure') ?>" method="POST" id="formBulkDeleteSalary">
                <input type="hidden" name="selected_ids" id="bulkDeleteSelectedIds">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Hapus Struktur Gaji</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Anda akan menghapus <strong id="bulkDeleteCount">0</strong> data.</p>
                    <p class="text-danger mb-0">Aksi ini akan menghapus permanen (hard delete).</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus Permanen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const salarySaveUrl = '<?= base_url('payroll/update_salary_structure_inline') ?>';
    const salarySavingRows = {};

    function formatRupiahInteger(value) {
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

    function refreshSalaryAmountDisplays() {
        $('.salary-edit-amount').each(function() {
            const rowId = $(this).data('row-id');
            const raw = parseFloat($('.salary-amount-raw[data-row-id="' + rowId + '"]').val() || 0);
            $(this).find('.salary-inline-display').text(formatRupiahInteger(raw));
        });
    }

    function saveSalaryRowInline(rowId) {
        if (salarySavingRows[rowId]) {
            return;
        }

        const amount = parseFloat($('.salary-amount-raw[data-row-id="' + rowId + '"]').val() || 0);
        const isEnabled = $('.salary-enabled-toggle[data-row-id="' + rowId + '"]').is(':checked') ? 1 : 0;

        salarySavingRows[rowId] = true;
        const $row = $('tr[data-row-id="' + rowId + '"]');
        $row.addClass('salary-row-saving');

        $.ajax({
            url: salarySaveUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                id: rowId,
                amount: amount,
                is_enabled: isEnabled
            }
        }).always(function() {
            salarySavingRows[rowId] = false;
            $row.removeClass('salary-row-saving');
        }).fail(function() {
            alert('Gagal menyimpan perubahan. Coba lagi.');
        });
    }

    function getSelectedSalaryIds() {
        const ids = [];
        $('.salary-row-check:checked').each(function() {
            ids.push($(this).val());
        });
        return ids;
    }

    function updateGroupSelectionState() {
        $('.check-position-group').each(function() {
            const positionId = $(this).data('position-id');
            const $rowsInGroup = $('.salary-row-check[data-position-id="' + positionId + '"]');
            const checkedCount = $rowsInGroup.filter(':checked').length;
            $(this).prop('checked', $rowsInGroup.length > 0 && checkedCount === $rowsInGroup.length);
        });
    }

    function updateBulkSalarySelectionState() {
        const selectedIds = getSelectedSalaryIds();
        const totalRows = $('.salary-row-check').length;
        const selectedCount = selectedIds.length;

        $('#salarySelectedInfo').text(selectedCount + ' data dipilih');
        $('#btnBulkEditSalary').prop('disabled', selectedCount === 0);
        $('#btnBulkDeleteSalary').prop('disabled', selectedCount === 0);
        $('#checkAllSalaryRows').prop('checked', totalRows > 0 && selectedCount === totalRows);

        updateGroupSelectionState();
    }

    $('#checkAllSalaryRows').on('change', function() {
        $('.salary-row-check').prop('checked', $(this).is(':checked'));
        updateBulkSalarySelectionState();
    });

    $(document).on('change', '.check-position-group', function() {
        const positionId = $(this).data('position-id');
        $('.salary-row-check[data-position-id="' + positionId + '"]').prop('checked', $(this).is(':checked'));
        updateBulkSalarySelectionState();
    });

    $(document).on('change', '.salary-row-check', function() {
        updateBulkSalarySelectionState();
    });

    $('#expandAllPositions').on('click', function() {
        $('.salary-position-collapse').collapse('show');
    });

    $('#collapseAllPositions').on('click', function() {
        $('.salary-position-collapse').collapse('hide');
    });

    $(document).on('keydown', '.position-collapse-trigger', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    $('.salary-position-collapse').on('shown.bs.collapse', function() {
        $('.position-collapse-trigger[data-bs-target="#' + this.id + '"]').attr('aria-expanded', 'true');
    });

    $('.salary-position-collapse').on('hidden.bs.collapse', function() {
        $('.position-collapse-trigger[data-bs-target="#' + this.id + '"]').attr('aria-expanded', 'false');
    });

    $('#btnBulkEditSalary').on('click', function() {
        const ids = getSelectedSalaryIds();
        $('#bulkEditSelectedIds').val(ids.join(','));
        $('#bulkEditCount').text(ids.length);
    });

    $('#btnBulkDeleteSalary').on('click', function() {
        const ids = getSelectedSalaryIds();
        $('#bulkDeleteSelectedIds').val(ids.join(','));
        $('#bulkDeleteCount').text(ids.length);
    });

    $('#btnAddSalaryStructure').on('click', function() {
        $('#salaryStructureModalTitle').text('Tambah Struktur Gaji');
        $('#formSalaryStructureModal')[0].reset();
    });

    $(document).on('click', '.salary-edit-amount', function() {
        const cell = $(this);
        if (cell.hasClass('editing')) {
            return;
        }

        const rowId = cell.data('row-id');
        const raw = $('.salary-amount-raw[data-row-id="' + rowId + '"]').val() || 0;
        const input = $('<input type="text" class="salary-inline-input">').val(raw);

        cell.addClass('editing');
        cell.find('.salary-inline-display').empty().append(input);
        input.trigger('focus').select();

        let committed = false;
        function commitEdit() {
            if (committed) {
                return;
            }
            committed = true;

            const newAmount = cleanCurrencyInput(input.val());
            $('.salary-amount-raw[data-row-id="' + rowId + '"]').val(newAmount);
            cell.removeClass('editing');
            refreshSalaryAmountDisplays();
            saveSalaryRowInline(rowId);
        }

        input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                commitEdit();
            } else if (e.key === 'Escape') {
                committed = true;
                cell.removeClass('editing');
                refreshSalaryAmountDisplays();
            }
        });

        input.on('blur', function() {
            commitEdit();
        });
    });

    $(document).on('change', '.salary-enabled-toggle', function() {
        const rowId = $(this).data('row-id');
        saveSalaryRowInline(rowId);
    });

    refreshSalaryAmountDisplays();
    updateBulkSalarySelectionState();
</script>
