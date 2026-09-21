<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
        <i class="bi bi-award me-2"></i>Appreciation Score
    </h6>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div style="border: 1px solid #f0f0f0; border-radius: 6px; padding: 16px; background-color: #fff;">
            <form id="appreciation-score-form">
                <div class="mb-3">
                    <label for="appreciation-user-profile-id" class="form-label" style="font-weight: 500; color: rgba(0,0,0,0.85);">
                        Karyawan <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="appreciation-user-profile-id" name="user_profile_id" required>
                        <option value="">Pilih karyawan</option>
                        <?php foreach (($employees ?? []) as $employee): ?>
                            <option value="<?= intval($employee['user_profile_id']) ?>" data-score="<?= intval($employee['current_score'] ?? 0) ?>">
                                <?= htmlspecialchars((string) $employee['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                - <?= htmlspecialchars((string) $employee['position_name'], ENT_QUOTES, 'UTF-8') ?>
                                (<?= intval($employee['current_score'] ?? 0) ?> poin)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted d-block mt-1" id="current-score-preview">Current score akan muncul setelah karyawan dipilih.</small>
                </div>

                <div class="mb-3">
                    <label for="appreciation-score" class="form-label" style="font-weight: 500; color: rgba(0,0,0,0.85);">
                        Score Tambahan <span class="text-danger">*</span>
                    </label>
                    <input type="number" class="form-control" id="appreciation-score" name="score" min="1" step="1" required placeholder="Contoh: 25">
                </div>

                <div class="mb-3">
                    <label for="appreciation-note" class="form-label" style="font-weight: 500; color: rgba(0,0,0,0.85);">
                        Catatan Management
                    </label>
                    <textarea class="form-control" id="appreciation-note" name="note" rows="3" placeholder="Contoh: Apresiasi untuk kontribusi extra di project bulan ini."></textarea>
                </div>

                <div class="alert" id="appreciation-score-message" style="display: none; border-radius: 6px;"></div>

                <button type="submit" class="btn btn-primary" id="appreciation-score-submit">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Score
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <form id="appreciation-score-search-form" class="search-form">
            <div class="input-group mb-3" style="box-shadow: 0 2px 0 rgba(0,0,0,0.02);">
                <input type="text" name="keyword" class="form-control" placeholder="Search employee, email, or note..."
                       value="<?= htmlspecialchars((string) ($keyword ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       style="border: 1px solid #d9d9d9; box-shadow: none; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
                <button class="btn btn-primary" type="submit"
                        style="border-radius: 2px; height: 32px; padding: 0 15px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-search"></i>
                </button>
            </div>

            <?php if (!empty($notif)): ?>
                <div class="alert alert-info" style="display: flex; align-items: center;">
                    <i class="bi bi-info-circle me-2"></i>
                    <span><?= strip_tags($notif) ?></span>
                </div>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table class="table table-hover" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th class="text-start">#</th>
                        <th class="text-start">Karyawan</th>
                        <th class="text-center">Score</th>
                        <th class="text-start">Catatan</th>
                        <th class="text-start">Diberikan Oleh</th>
                        <th class="text-start">Tanggal</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 48px 16px; color: rgba(0,0,0,0.45);">
                                <i class="bi bi-inbox" style="font-size: 36px; display: block; margin-bottom: 10px;"></i>
                                Belum ada score apresiasi.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $k = intval($start ?? 0); ?>
                        <?php foreach ($history as $row): ?>
                            <?php
                            $note = trim((string) ($row['note'] ?? ''));
                            $note_preview = strlen($note) > 90 ? substr($note, 0, 90) . '...' : $note;
                            ?>
                            <tr>
                                <td class="text-start" style="padding: 14px 12px;"><?= $k + 1 ?></td>
                                <td class="text-start" style="padding: 14px 12px;">
                                    <div><strong><?= htmlspecialchars((string) ($row['full_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                    <div style="color: rgba(0,0,0,0.55); font-size: 12px;">
                                        <?= htmlspecialchars((string) ($row['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div style="color: #1890ff; font-size: 12px; margin-top: 2px;">
                                        <?= htmlspecialchars((string) ($row['position_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                        - <?= htmlspecialchars((string) ($row['level_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td class="text-center" style="padding: 14px 12px;">
                                    <span style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; padding: 4px 10px; border-radius: 14px; font-weight: 600;">
                                        +<?= intval($row['score'] ?? 0) ?>
                                    </span>
                                </td>
                                <td class="text-start" style="padding: 14px 12px; color: rgba(0,0,0,0.65);">
                                    <?= $note_preview !== '' ? htmlspecialchars($note_preview, ENT_QUOTES, 'UTF-8') : '<span style="color: rgba(0,0,0,0.35);">-</span>' ?>
                                </td>
                                <td class="text-start" style="padding: 14px 12px;">
                                    <?= htmlspecialchars((string) ($row['giver_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="text-start" style="padding: 14px 12px; color: rgba(0,0,0,0.65);">
                                    <?= !empty($row['given_at']) ? date('d M Y H:i', strtotime($row['given_at'])) : '-' ?>
                                </td>
                                <td class="text-center" style="padding: 14px 12px;">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger appreciation-score-delete"
                                            data-id="<?= intval($row['id'] ?? 0) ?>"
                                            data-name="<?= htmlspecialchars((string) ($row['full_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                            style="height: 30px; border-radius: 2px; padding: 2px 9px;"
                                            title="Hapus score apresiasi">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php $k += 1; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-3" id="appreciation-score-pagination">
            <?= $pagination ?>
        </div>
    </div>
</div>

<script>
$(document).off('change', '#appreciation-user-profile-id').on('change', '#appreciation-user-profile-id', function() {
    const score = $(this).find(':selected').data('score');
    $('#current-score-preview').text(score !== undefined ? 'Current score: ' + score + ' poin' : 'Current score akan muncul setelah karyawan dipilih.');
});

$(document).off('submit', '#appreciation-score-form').on('submit', '#appreciation-score-form', function(e) {
    e.preventDefault();

    const $message = $('#appreciation-score-message');
    const $button = $('#appreciation-score-submit');
    $message.hide().removeClass('alert-success alert-danger').text('');
    $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan');

    $.ajax({
        type: 'POST',
        url: '<?= base_url() ?>quest/appreciation_score_store',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                const successMessage = response.message || 'Score apresiasi berhasil ditambahkan.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Berhasil', successMessage, 'success');
                } else {
                    alert(successMessage);
                }
                $('#appreciation-score-form')[0].reset();
                $('#current-score-preview').text('Current score akan muncul setelah karyawan dipilih.');
                loadAppreciationScoreContent();
            } else {
                $message.addClass('alert-danger').text((response && response.message) || 'Gagal menyimpan score apresiasi.').show();
            }
        },
        error: function() {
            $message.addClass('alert-danger').text('Terjadi kesalahan saat menyimpan score apresiasi.').show();
        },
        complete: function() {
            $button.prop('disabled', false).html('<i class="bi bi-plus-circle me-1"></i> Tambah Score');
        }
    });
});

$(document).off('submit', '#appreciation-score-search-form').on('submit', '#appreciation-score-search-form', function(e) {
    e.preventDefault();
    const queryString = '?' + $(this).serialize();
    loadAppreciationScoreContent(queryString);
});

$(document).off('click', '.appreciation-score-delete').on('click', '.appreciation-score-delete', function(e) {
    e.preventDefault();

    const id = $(this).data('id');
    const name = $(this).data('name') || 'karyawan ini';

    const runDelete = function() {
        $.ajax({
            type: 'POST',
            url: '<?= base_url() ?>quest/appreciation_score_delete',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Berhasil', response.message || 'Score apresiasi berhasil dihapus.', 'success');
                    }
                    loadAppreciationScoreContent();
                } else {
                    const message = (response && response.message) || 'Gagal menghapus score apresiasi.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Gagal', message, 'error');
                    } else {
                        alert(message);
                    }
                }
            },
            error: function() {
                const message = 'Terjadi kesalahan saat menghapus score apresiasi.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Gagal', message, 'error');
                } else {
                    alert(message);
                }
            }
        });
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus score apresiasi?',
            text: 'Score apresiasi untuk ' + name + ' akan dihapus dan total poin akan dihitung ulang.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4d4f',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, hapus'
        }).then(function(result) {
            if (result.isConfirmed) {
                runDelete();
            }
        });
    } else if (confirm('Hapus score apresiasi untuk ' + name + '?')) {
        runDelete();
    }
});

$(document).off('click', '#appreciation-score-content .btn-pagination, #appreciation-score-content .btn-pagination-active')
    .on('click', '#appreciation-score-content .btn-pagination, #appreciation-score-content .btn-pagination-active', function(e) {
        e.preventDefault();
        const url = $(this).attr('href') || '';
        const queryString = url.indexOf('?') >= 0 ? '?' + url.split('?')[1] : '';
        loadAppreciationScoreContent(queryString);
    });
</script>
