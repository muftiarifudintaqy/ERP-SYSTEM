<?php
$employee_map = [];
foreach ($employees as $employee) {
    $employee_map[(int) $employee['user_id']] = $employee['full_name'];
}
$component_map = [];
foreach ($components as $component) {
    $component_map[(int) $component['id']] = $component['name'];
}
?>

<div class="container-fluid py-3">
    <?php $current = 'employee_salary_setup'; include APPPATH . 'views/payroll/_menu.php'; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Override Gaji Karyawan</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeOverrideModal" id="btnAddEmployeeOverride">
                <i class="bi bi-plus me-1"></i>Tambah Override
            </button>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>Komponen</th>
                        <th>Nominal</th>
                        <th>Aktif</th>
                        <th width="90">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($overrides)): ?>
                        <?php foreach ($overrides as $row): ?>
                            <tr>
                                <td><?= $employee_map[(int) $row['user_id']] ?? ('User #' . $row['user_id']) ?></td>
                                <td><?= $component_map[(int) $row['payroll_component_id']] ?? ('Komponen #' . $row['payroll_component_id']) ?></td>
                                <td>Rp <?= number_format((float) $row['amount'], 0, ',', '.') ?></td>
                                <td><?= (int) $row['is_enabled'] === 1 ? 'Ya' : 'Tidak' ?></td>
                                <td>
                                    <button
                                        class="btn btn-outline-primary btn-sm btn-edit-employee-override"
                                        data-user-id="<?= $row['user_id'] ?>"
                                        data-component-id="<?= $row['payroll_component_id'] ?>"
                                        data-amount="<?= (float) $row['amount'] ?>"
                                        data-enabled="<?= (int) $row['is_enabled'] ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#employeeOverrideModal"
                                    >Edit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">Belum ada override karyawan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="employeeOverrideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= base_url('payroll/save_employee_override') ?>" method="POST" id="formEmployeeOverrideModal">
                <div class="modal-header">
                    <h5 class="modal-title" id="employeeOverrideTitle">Tambah Override Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Karyawan</label>
                            <select name="user_id" id="overrideUserId" class="form-select" required>
                                <option value="">Pilih karyawan</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= $employee['user_id'] ?>"><?= $employee['full_name'] ?> (<?= $employee['position_name'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Komponen</label>
                            <select name="payroll_component_id" id="overrideComponentId" class="form-select" required>
                                <option value="">Pilih komponen</option>
                                <?php foreach ($components as $component): ?>
                                    <option value="<?= $component['id'] ?>"><?= $component['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nominal</label>
                            <input type="number" name="amount" id="overrideAmount" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Aktif</label>
                            <select name="is_enabled" id="overrideEnabled" class="form-select">
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

<script>
    $('#btnAddEmployeeOverride').on('click', function() {
        $('#employeeOverrideTitle').text('Tambah Override Karyawan');
        $('#formEmployeeOverrideModal')[0].reset();
    });

    $(document).on('click', '.btn-edit-employee-override', function() {
        $('#employeeOverrideTitle').text('Ubah Override Karyawan');
        $('#overrideUserId').val($(this).data('user-id'));
        $('#overrideComponentId').val($(this).data('component-id'));
        $('#overrideAmount').val($(this).data('amount'));
        $('#overrideEnabled').val($(this).data('enabled'));
    });
</script>
