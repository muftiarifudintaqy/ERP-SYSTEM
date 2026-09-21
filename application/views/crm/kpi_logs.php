<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">CRM KPI Content Logs</h5>
            <a href="<?= base_url('kpi') ?>" class="btn btn-outline-secondary btn-sm">Buka KPI</a>
        </div>
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <label class="form-label mb-1">Bulan</label>
                    <input type="month" class="form-control" id="kpi-log-month" value="<?= htmlspecialchars($month, ENT_QUOTES) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1">Karyawan CRM</label>
                    <select class="form-control" id="kpi-log-employee">
                        <?php foreach (($crm_users ?? []) as $u): ?>
                            <option value="<?= (int) $u['id'] ?>" <?= (int) ($employee_id ?? 0) === (int) $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name'] ?? '-', ENT_QUOTES) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5 d-flex align-items-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="kpi-log-reload">Muat Ulang</button>
                    <button type="button" class="btn btn-outline-primary" id="kpi-log-add-row">+ Tambah Row</button>
                    <button type="button" class="btn btn-primary" id="kpi-log-save">Simpan</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th style="width: 45%">Pertanyaan</th>
                            <th style="width: 45%">Link Upload</th>
                            <th style="width: 10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="kpi-log-body">
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td><input type="text" class="form-control form-control-sm" data-field="question"></td>
                                <td><input type="text" class="form-control form-control-sm" data-field="upload_link"></td>
                                <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm kpi-log-remove">Hapus</button></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><input type="text" class="form-control form-control-sm" data-field="question" value="<?= htmlspecialchars($row['question'] ?? '', ENT_QUOTES) ?>"></td>
                                    <td><input type="text" class="form-control form-control-sm" data-field="upload_link" value="<?= htmlspecialchars($row['upload_link'] ?? '', ENT_QUOTES) ?>"></td>
                                    <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm kpi-log-remove">Hapus</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="kpi-log-info" class="text-muted" style="font-size:12px;"></div>
        </div>
    </div>
</div>

<script>
    $(function() {
        function addRow(question, uploadLink) {
            const tr = `
                <tr>
                    <td><input type="text" class="form-control form-control-sm" data-field="question" value="${$('<div>').text(question || '').html()}"></td>
                    <td><input type="text" class="form-control form-control-sm" data-field="upload_link" value="${$('<div>').text(uploadLink || '').html()}"></td>
                    <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm kpi-log-remove">Hapus</button></td>
                </tr>
            `;
            $('#kpi-log-body').append(tr);
        }

        function collectRows() {
            const rows = [];
            $('#kpi-log-body tr').each(function() {
                const question = $(this).find('[data-field="question"]').val() || '';
                const uploadLink = $(this).find('[data-field="upload_link"]').val() || '';
                rows.push({ question: question.trim(), upload_link: uploadLink.trim() });
            });
            return rows;
        }

        $('#kpi-log-add-row').on('click', function() {
            addRow('', '');
        });

        $(document).on('click', '.kpi-log-remove', function() {
            if ($('#kpi-log-body tr').length <= 1) {
                $(this).closest('tr').find('input').val('');
                return;
            }
            $(this).closest('tr').remove();
        });

        $('#kpi-log-reload').on('click', function() {
            const month = $('#kpi-log-month').val();
            const employeeId = $('#kpi-log-employee').val();
            window.location.href = '<?= base_url('crm/kpi-logs') ?>?month=' + encodeURIComponent(month) + '&employee_id=' + encodeURIComponent(employeeId);
        });

        $('#kpi-log-save').on('click', function() {
            const $btn = $(this);
            const oldText = $btn.text();
            $btn.prop('disabled', true).text('Menyimpan...');
            $('#kpi-log-info').text('Menyimpan data...');

            $.ajax({
                url: '<?= base_url('crm/save-kpi-logs') ?>',
                method: 'POST',
                dataType: 'json',
                data: {
                    month: $('#kpi-log-month').val(),
                    employee_id: $('#kpi-log-employee').val(),
                    rows: collectRows()
                },
                success: function(res) {
                    if (res && res.status) {
                        $('#kpi-log-info').text(res.message || 'Tersimpan.');
                    } else {
                        $('#kpi-log-info').text((res && res.message) ? res.message : 'Gagal menyimpan.');
                    }
                },
                error: function() {
                    $('#kpi-log-info').text('Gagal menyimpan.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(oldText);
                }
            });
        });
    });
</script>
