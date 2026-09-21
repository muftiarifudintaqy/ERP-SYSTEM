<?php
$recruitment_status_options = $recruitment_status_options ?? [
    'pending' => ['label' => 'Pending', 'badge' => 'badge-default', 'badge_bg' => '#e0e0e0', 'text_color' => '#666'],
    'testcase_1' => ['label' => 'Testcase 1', 'badge' => 'badge-testcase_1', 'badge_bg' => '#d7bdf2', 'text_color' => '#4b0082'],
    'interview_hr' => ['label' => 'Interview HR', 'badge' => 'badge-interview_hr', 'badge_bg' => '#ffe7a0', 'text_color' => '#7a5900'],
    'interview_user' => ['label' => 'Interview User', 'badge' => 'badge-interview_user', 'badge_bg' => '#cde8ff', 'text_color' => '#0d6efd'],
    'selected' => ['label' => 'Selected', 'badge' => 'badge-selected', 'badge_bg' => '#2e7d32', 'text_color' => '#ffffff'],
    'rejected' => ['label' => 'Rejected', 'badge' => 'badge-rejected', 'badge_bg' => '#b71c1c', 'text_color' => '#ffffff'],
    'pertimbangan' => ['label' => 'Pertimbangan', 'badge' => 'badge-pertimbangan', 'badge_bg' => '#f9c998', 'text_color' => '#8b4513'],
];

if (empty($data)) {
    $is_pending_filter = (($status_filter ?? '') === 'pending');
    $is_selected_filter = (($status_filter ?? '') === 'selected');
    $current_status_meta = (!empty($status_filter) && !empty($recruitment_status_options[$status_filter])) ? $recruitment_status_options[$status_filter] : null;
    $hide_approval_column = $is_selected_filter || ($current_status_meta && empty($current_status_meta['requires_approval']));
    $colspan = 9;
    if ($is_pending_filter) {
        $colspan += 1; // action
    } else {
        $colspan += 2; // status recruitment + notes
        if (!$hide_approval_column) {
            $colspan += 1; // status approval
        }
    }
    echo '<tr><td colspan="' . $colspan . '" class="text-center text-muted">Tidak ada data</td></tr>';
} else {
    $is_pending_filter = (($status_filter ?? '') === 'pending');
    $is_selected_filter = (($status_filter ?? '') === 'selected');
    $current_status_meta = (!empty($status_filter) && !empty($recruitment_status_options[$status_filter])) ? $recruitment_status_options[$status_filter] : null;
    $hide_approval_column = $is_selected_filter || ($current_status_meta && empty($current_status_meta['requires_approval']));
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;
        
        $tag_color_class = match($value['tag']) {
            'New Applied' => 'badge-success',
            'Already Apply' => 'badge-danger',
            default => 'badge-default',
        };

        $status = $value['status_recruitment'] ?? null;
        if ($status === null || $status === '') {
            $status = 'pending';
        }
        $status_meta = $recruitment_status_options[$status] ?? [
            'label' => ucwords(str_replace('_', ' ', $status)),
            'badge' => 'badge-default',
            'badge_bg' => '#e0e0e0',
            'text_color' => '#666',
        ];
        $label = $status_meta['label'] ?? ucwords(str_replace('_', ' ', $status));
        $status_badge_style = 'background-color: '.htmlspecialchars($status_meta['badge_bg'] ?? '#e0e0e0').'; color: '.htmlspecialchars($status_meta['text_color'] ?? '#666').';';

        $approval_status_color = match($value['status_approval']) {
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            default => 'badge-default'
        };

        $testcase_label_map = [
            'shared_testcase_1' => 'Shared Testcase 1',
            'done_testcase_1' => 'Done Testcase 1',
            '' => 'Pending'
        ];        
        $testcase_label = isset($testcase_label_map[$value['status_testcase']]) ? $testcase_label_map[$value['status_testcase']] : 'Pending';
        $testcase_status_color = match($value['status_testcase']) {
            'shared_testcase_1' => 'badge-primary',
            'done_testcase_1' => 'badge-success',
            default => 'badge-default'
        };
        
        // Format WhatsApp number
        $wa_number = $value['no_handphone'] ?? '';
        $wa_number_clean = preg_replace('/\D/', '', $wa_number);
        if (substr($wa_number_clean, 0, 1) === '0') {
            $wa_number_clean = '62' . substr($wa_number_clean, 1);
        } else if (substr($wa_number_clean, 0, 3) === '620') {
            $wa_number_clean = '62' . substr($wa_number_clean, 3);
        }

        $full_name = trim((string) ($value['nama_lengkap'] ?? ''));
        $display_name = $full_name;
        if ($full_name !== '') {
            $parts = preg_split('/\s+/', $full_name);
            if (count($parts) > 2) {
                $first_two = array_slice($parts, 0, 2);
                $rest = array_slice($parts, 2);
                $initials = array_map(function ($word) {
                    return strtoupper(mb_substr($word, 0, 1));
                }, $rest);
                if (count($parts) > 3) {
                    $display_name = trim(implode(' ', $first_two) . ' ' . implode('.', $initials) . '.');
                } else {
                    $display_name = trim(implode(' ', $first_two) . ' ' . implode('', $initials));
                }
            }
        }

?>
        <tr data-wa="<?= $wa_number_clean ?>" data-posisi="<?= htmlspecialchars($value['posisi_dilamar'] ?? '') ?>" data-name="<?= htmlspecialchars($value['nama_lengkap'] ?? '') ?>">
            <td><?= $num ?></td>
            <td class="clickable-row" data-id="<?= $value['id'] ?>"><?= date('d M Y', strtotime($value['created_at'])) ?></td>
            <td style="max-width: 200px; white-space: normal; word-wrap: break-word;" class="clickable-row" data-id="<?= $value['id'] ?>" style="cursor: pointer;">
                <?php if (!empty($value['nama_lengkap'])): ?>
                    <span class="badge <?= $tag_color_class ?>">
                        <strong><?= htmlspecialchars($display_name) ?></strong>
                    </span>
                    <div style="color: rgba(0,0,0,0.65); font-size: 12px; word-break: break-word; margin-top: 4px;">
                        <?php
                        $info_parts = [];
                        if (!empty($value['domisili'])) {
                            $info_parts[] = htmlspecialchars($value['domisili']);
                        }
                        if (!empty($value['usia'])) {
                            $info_parts[] = htmlspecialchars($value['usia']);
                        }
                        if (!empty($value['jenis_kelamin'])) {
                            $info_parts[] = htmlspecialchars($value['jenis_kelamin']);
                        }
                        
                        if (!empty($info_parts)) {
                            echo implode(' | ', $info_parts);
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <span class="text-muted" style="font-size: 12px; font-style: italic;">Data tidak tersedia</span>
                <?php endif; ?>
            </td>

            <!-- WhatsApp Column -->
            <td class="text-center">
                <?php if (!empty($wa_number_clean)): ?>
                    <button type="button"
                       class="btn btn-sm btn-success btn-wa-template"
                       data-wa="<?= $wa_number_clean ?>"
                       data-name="<?= htmlspecialchars($value['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       data-posisi="<?= htmlspecialchars($value['posisi_dilamar'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       style="font-size: 12px; display: inline-flex; align-items: center; gap: 4px;"
                       title="Template Pesan">
                        <i class="bi bi-whatsapp"></i>
                    </button>
                <?php else: ?>
                    <span class="text-muted" style="font-size: 12px;">-</span>
                <?php endif; ?>
            </td>

            <td class="clickable-row" data-id="<?= $value['id'] ?>" style="max-width: 180px; white-space: normal; word-wrap: break-word;"><?= $value['posisi_dilamar'] ?></td>
            <td class="clickable-row" data-id="<?= $value['id'] ?>" style="max-width: 180px; white-space: normal; word-wrap: break-word;">
                <?= !empty($value['sumber_info_loker']) ? htmlspecialchars($value['sumber_info_loker']) : '<span class="text-muted" style="font-size: 12px;">-</span>' ?>
            </td>
            
            <!-- Riwayat Pekerjaan Column -->
            <td style="max-width: 200px; white-space: normal; word-wrap: break-word;">
                <?php if (!empty($value['perusahaan_terakhir'])): ?>
                    <div class="company-name-hover" 
                         data-pengalaman="<?= htmlspecialchars($value['pengalaman_terakhir'] ?? '') ?>"
                         style="font-weight: 600; color: #1890ff; font-size: 13px; word-break: break-word; cursor: pointer;">
                        <?= htmlspecialchars($value['perusahaan_terakhir']) ?>
                    </div>
                    <?php if (!empty($value['posisi_terakhir'])): ?>
                        <div style="color: rgba(0,0,0,0.65); font-size: 12px; word-break: break-word; margin-top: 4px;">
                            <?= htmlspecialchars($value['posisi_terakhir']) ?>
                            <?php if (!empty($value['lama_posisi_terakhir'])): ?>
                                - <?= htmlspecialchars($value['lama_posisi_terakhir']) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php elseif (!empty($value['pengalaman_terakhir'])): ?>
                    <div style="color: rgba(0,0,0,0.65); font-size: 12px; word-break: break-word;">
                        <i class="bi bi-briefcase" style="font-size: 10px;"></i> <?= htmlspecialchars($value['pengalaman_terakhir']) ?>
                    </div>
                <?php else: ?>
                    <span class="text-muted" style="font-size: 12px; font-style: italic;">Belum ada pengalaman</span>
                <?php endif; ?>
            </td>
            
            <!-- Mode Kerja Column -->
            <td class="text-center">
                <?php if (isset($value['is_wfo'])): ?>
                    <?php if ($value['is_wfo'] == 1): ?>
                        <span class="badge" style="background: #e6f7ff; color: #1890ff; font-size: 11px; padding: 4px 8px;">
                            <i class="bi bi-building"></i> WFO
                        </span>
                    <?php else: ?>
                        <span class="badge" style="background: #f6ffed; color: #52c41a; font-size: 11px; padding: 4px 8px;">
                            <i class="bi bi-house"></i> WFH
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted" style="font-size: 12px;">-</span>
                <?php endif; ?>
            </td>
            
            <!-- Ekspektasi Gaji Column -->
            <td>
                <?php if (!empty($value['ekspektasi_sallary'])): ?>
                    <div style="font-weight: 600; color: #52c41a; font-size: 13px;">
                        Rp <?= number_format((float)$value['ekspektasi_sallary'], 0, ',', '.') ?>
                    </div>
                <?php else: ?>
                    <span class="text-muted" style="font-size: 12px;">-</span>
                <?php endif; ?>
            </td>
            
            <?php if (!$is_pending_filter): ?>
                <!-- Kolom Status Recruitment dengan edit on click -->
                <td class="editable-status" data-field="status_recruitment" data-id="<?= $value['id'] ?>">
                    <div class="display-mode">
                        <span class="badge <?= htmlspecialchars($status_meta['badge'] ?? 'badge-default') ?>" style="<?= $status_badge_style ?>">
                        <?= $label ?>
                    </span>

                    </div>
                    <div class="edit-mode d-none">
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm status-select" style="height: calc(1.5em + .5rem + 2px);">
                                <?php foreach ($recruitment_status_options as $status_key => $option): ?>
                                    <option value="<?= htmlspecialchars($status_key) ?>" <?= $status == $status_key ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option['label'] ?? ucwords(str_replace('_', ' ', $status_key))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center btn-cancel-status" style="height: calc(1.5em + .5rem + 2px);" title="Cancel">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>

                </td>

                <!-- Kolom Approval Status -->
                <?php if (!$hide_approval_column): ?>
                    <td class="editable-status" data-field="status_approval" data-id="<?= $value['id'] ?>">
                        <div class="display-mode">
                            <span class="badge <?= $approval_status_color ?>">
                                <?= $value['status_approval'] ? ucfirst($value['status_approval']) : 'Pending' ?>
                            </span>
                        </div>
                        <div class="edit-mode d-none">
                            <div class="d-flex align-items-center gap-2">
                                <select class="form-select form-select-sm" style="height: 28px; line-height: 1;">
                                    <option value="" <?= empty($value['status_approval']) ? 'selected' : '' ?>>Pending</option>
                                    <option value="approved" <?= $value['status_approval'] == 'approved' ? 'selected' : '' ?>>Approved</option>
                                    <option value="rejected" <?= $value['status_approval'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                                <button class="btn btn-sm btn-outline-danger p-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                <?php endif; ?>

                <!-- Kolom Notes -->
                <td class="editable-notes" data-id="<?= $value['id'] ?>" data-notes="<?= htmlspecialchars($value['notes_hr'] ?? '') ?>">
                    <?php if (!empty($value['notes_hr'])): ?>
                        <span class="notes-text"><?= htmlspecialchars($value['notes_hr']) ?></span>
                    <?php else: ?>
                        <span class="notes-text text-muted" style="font-size: 12px;">Add Note</span>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
            
            <?php if ($is_pending_filter): ?>
                <!-- Action Column (Fixed Right) -->
                <td class="text-center action-col" style="position: sticky; right: 0; background: #fff; z-index: 5;">
                    <div class="d-flex justify-content-center gap-2" style="min-width: 90px;">
                        <!-- Reject Button -->
                        <button class="btn btn-sm btn-reject" 
                                data-id="<?= $value['id'] ?>" 
                                data-name="<?= htmlspecialchars($value['nama_lengkap']) ?>"
                                data-wa="<?= $wa_number_clean ?>"
                                title="Tolak kandidat"
                                style="padding: 4px 8px; background: #fff1f0; border: 1px solid #ffa39e; color: #f5222d;">
                            <i class="bi bi-x-circle"></i>
                        </button>
                        <?php if (!empty($wa_number_clean)): ?>
                            <a href="javascript:void(0)"
                               class="btn btn-sm btn-success btn-whatsapp" 
                               data-wa="<?= $wa_number_clean ?>"
                               data-id="<?= $value['id'] ?>"
                               title="Chat WhatsApp"
                               style="padding: 4px 8px;">
                                <i class="bi bi-whatsapp"></i>
                            </a>
                        <?php endif; ?>
                        <!-- Notes button removed for Pending status -->
                    </div>
                </td>
            <?php endif; ?>
        </tr>
<?php
    }
}
?>

<style>
    .badge-testcase_1 {
    background-color: #d7bdf2;
        color: #4b0082;
    }

    .badge-interview_hr {
        background-color: #ffe7a0;
        color: #7a5900;
    }

    .badge-interview_user {
        background-color: #cde8ff;
        color: #0d6efd;
    }

    .badge-selected {
        background-color: #2e7d32;
        color: white;
    }

    .badge-rejected {
        background-color: #b71c1c;
        color: white;
    }

    .badge-pertimbangan {
        background-color: #f9c998;
        color: #8b4513;
    }

    .badge-additional {
        background-color: #f5f5f5;
        color: #595959;
    }

    .modal {
        z-index: 99999;
    }
    .editable-notes {
        transition: background-color 0.2s;
    }
    .editable-notes .notes-text {
        display: inline-block;
        max-width: 100px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }
    .editable-notes:hover {
        background-color: #f5f5f5;
    }
</style>

<script>
$(document).ready(function() {
    $(document).on('click', '.editable-status', function(e) {
        if ($(e.target).hasClass('btn-cancel-status') || $(e.target).hasClass('status-select')) {
            return;
        }
        
        $('.edit-mode').addClass('d-none');
        $('.display-mode').removeClass('d-none');
        
        $(this).find('.display-mode').addClass('d-none');
        $(this).find('.edit-mode').removeClass('d-none');
    });
    
    $(document).on('click', '.btn-cancel-status', function(e) {
        e.stopPropagation();
        $(this).closest('.edit-mode').addClass('d-none');
        $(this).closest('.editable-status').find('.display-mode').removeClass('d-none');
    });
    
    $(document).off('change.recruitment-status', '.editable-status select')
        .on('change.recruitment-status', '.editable-status select', function () {
        const cell = $(this).closest('.editable-status');
        const id = cell.data('id');
        const field = cell.data('field');
        const newValue = $(this).val();
        const linkInput = cell.find('.testcase-link-input');
        const row = $(this).closest('tr');
        const waNumber = row.data('wa') || '';
        const posisi = (row.data('posisi') || '').toString().trim();
        const candidateName = (row.data('name') || '').toString().trim();

        if (newValue === 'done_testcase_1') {
            linkInput.removeClass('d-none');

            linkInput.off('change').on('change', function () {
                const linkTestcase = $(this).val();

                sendStatusUpdate(id, field, newValue, linkTestcase, waNumber, posisi, candidateName);
            });

        } else {
            linkInput.addClass('d-none');
            sendStatusUpdate(id, field, newValue, null, waNumber, posisi, candidateName);
        }
    });

    function sendStatusUpdate(id, field, value, link_testcase = null, waNumber = '', posisi = '', candidateName = '') {
        $.ajax({
            url: '<?= base_url() ?>recruitment/update_status',
            method: 'POST',
            dataType: 'json',
            data: {
                id: id,
                field: field,
                value: value,
                link_testcase: link_testcase
            },
            success: function (response) {
                if (response.status === 'success') {
                    if (response.auto_next_status && response.applicant_name) {
                        const label = response.auto_next_status === 'interview_user' ? 'Interview User' : 'Selected';
                        if (typeof showRecruitmentToast === 'function') {
                            showRecruitmentToast(`${response.applicant_name} berhasil diubah ke ${label}`);
                        } else {
                            alert(`${response.applicant_name} berhasil diubah ke ${label}`);
                        }
                    }
                    if (field === 'status_approval' && value === 'rejected' && waNumber) {
                        const fallbackRejected = `*Hai kak ${candidateName || ''}*\n\nTerima kasih banyak sudah meluangkan waktu dan menunjukkan ketertarikan untuk bergabung bersama kami di PT Montera Sinergi Bersama.\n\nSetelah melalui proses seleksi awal, untuk saat ini kami belum bisa melanjutkan lamaran kamu ke tahap berikutnya.\n\n_Semoga kamu segera menemukan ruang yang paling pas untuk tumbuh, berkarya, dan bersinar._\n\nBest Regards,\n*HR Montera*`;
                        const message = (typeof window.getRecruitmentMessage === 'function')
                            ? window.getRecruitmentMessage('rejected', { name: candidateName || 'kak', position: posisi || 'Montera', company: 'PT Montera Sinergi Bersama' })
                            : fallbackRejected;
                        if (typeof window.openRecruitmentWhatsApp === 'function') {
                            window.openRecruitmentWhatsApp(waNumber, 'rejected', { name: candidateName || 'kak', position: posisi || 'Montera', company: 'PT Montera Sinergi Bersama' });
                        } else {
                            const normalizedMessage = String(message || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');
                            window.open(`https://api.whatsapp.com/send/?phone=${waNumber}&text=${encodeURIComponent(normalizedMessage)}&type=phone_number&app_absent=0`, '_blank');
                        }
                    }
                    if (field === 'status_recruitment' && value === 'interview_hr' && waNumber) {
                        const fallbackInterview = `*Halo kak ${candidateName || ''}*\n\n_Terima kasih atas ketertarikan kakak bergabung dengan PT Montera Sinergi Bersama as ${posisi || 'Montera'}_\n\nKami ingin mengundang kak ke sesi #ngobrolseru bareng HR via Google Meet.\n\nJadwalnya:\n\n*Apakah kak berkenan hadir?*`;
                        const message = (typeof window.getRecruitmentMessage === 'function')
                            ? window.getRecruitmentMessage('interview_hr', { name: candidateName || 'kak', position: posisi || 'Montera', company: 'PT Montera Sinergi Bersama' })
                            : fallbackInterview;
                        if (typeof window.openRecruitmentWhatsApp === 'function') {
                            window.openRecruitmentWhatsApp(waNumber, 'interview_hr', { name: candidateName || 'kak', position: posisi || 'Montera', company: 'PT Montera Sinergi Bersama' });
                        } else {
                            const normalizedMessage = String(message || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');
                            window.open(`https://api.whatsapp.com/send/?phone=${waNumber}&text=${encodeURIComponent(normalizedMessage)}&type=phone_number&app_absent=0`, '_blank');
                        }
                    }
                    loadRecruitmentData();
                    loadTabCounts();
                } else {
                    alert('Gagal memperbarui status: ' + response.message);
                }
            },
            error: function () {
                alert('Gagal memperbarui status. Silakan coba lagi.');
            }
        });
    }


    $(document).on('click', '.editable-notes', function() {
        const id = $(this).data('id');
        const currentNotes = $(this).find('.notes-text').text().trim();
        
        $('#notesApplicantId').val(id);
        $('#notesTextarea').val(currentNotes === 'Add Note' ? '' : currentNotes);
        
        $('#notesEditor').modal('show');
    });

    // Handle simpan notes
    $('#saveNotesBtn').click(function() {
        const id = $('#notesApplicantId').val();
        const notes = $('#notesTextarea').val();
        
        $.ajax({
            url: '<?= base_url() ?>recruitment/update_notes',
            method: 'POST',
            dataType: 'json',
            data: {
                id: id,
                notes: notes
            },
            success: function(response) {
                if (response.status === 'success') {
                    $('#notesEditor').modal('hide');
                    loadRecruitmentData();
                } else {
                    alert('Gagal menyimpan notes: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Gagal menyimpan notes. Silakan coba lagi.');
            }
        });
    });
});

function getCurrentPage() {
    let params = new URLSearchParams(window.location.search);
    return params.get('page') || 1; 
}

function loadRecruitmentData(page = null) {
    let currentPage = page || getCurrentPage();
    let baseUrl = "<?= base_url() ?>/recruitment/item<?= $param ?>";
    let separator = baseUrl.includes('?') ? '&' : '?';
    
    $.ajax({
        type: 'GET',
        url: baseUrl + separator + "page=" + currentPage,
        beforeSend: function() {
            $('#tbody').html('<tr><td colspan="9" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
        },
        success: function(data) {
            $('#tbody').html(data);
        },
        error: function(xhr, status, error) {
            console.error('Error loading data:', error);
            $('#tbody').html('<tr><td colspan="9" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
        }
    });
}


</script>
