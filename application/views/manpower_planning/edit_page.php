<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Edit Manpower Planning</h5>
                <a href="<?= base_url() ?>/manpower_planning" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-message"></div>
            <form action="<?= base_url() ?>/manpower_planning/update" method="POST" id="form-edit">
                <input type="hidden" name="id" value="<?= $data['id'] ?>">

                <?php $requestor_users = $requestor_users ?? array(); ?>

                <div class="section-card mb-4">
                    <h6 class="section-title">Informasi Request</h6>
                    <div class="section-content">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="requestor" class="form-label">Requestor <span class="text-danger">*</span></label>
                                <select class="form-select" id="requestor" name="dt[requestor]" required>
                                    <option value="">Pilih Requestor</option>
                                    <?php foreach ($requestor_users as $req): ?>
                                        <?php $selected = ($req['full_name'] ?? '') === $data['requestor'] ? 'selected' : ''; ?>
                                        <option value="<?= $req['full_name'] ?>" <?= $selected ?>><?= $req['full_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="tanggal_request" class="form-label">Tanggal Request <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal_request" name="dt[tanggal_request]"
                                       value="<?= $data['tanggal_request'] ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-card mb-4">
                    <h6 class="section-title">Posisi & Kebutuhan</h6>
                    <div class="section-content">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="posisi" class="form-label">Posisi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="posisi" name="dt[posisi]" 
                                       value="<?= $data['posisi'] ?>" placeholder="e.g. Software Developer, Marketing Executive" required>
                            </div>
                            <div class="col-md-6">
                                <label for="level" class="form-label">Level <span class="text-danger">*</span></label>
                                <select class="form-select" id="level" name="dt[level]" required>
                                    <option value="">Pilih Level</option>
                                    <option value="Internship" <?= $data['level'] == 'Internship' ? 'selected' : '' ?>>Internship</option>
                                    <option value="Junior (Eksekutor)" <?= $data['level'] == 'Junior (Eksekutor)' ? 'selected' : '' ?>>Junior (Eksekutor)</option>
                                    <option value="Senior (Eks+Analyst)" <?= $data['level'] == 'Senior (Eks+Analyst)' ? 'selected' : '' ?>>Senior (Eks+Analyst)</option>
                                    <option value="Leader" <?= $data['level'] == 'Leader' ? 'selected' : '' ?>>Leader</option>
                                    <option value="POV" <?= $data['level'] == 'POV' ? 'selected' : '' ?>>POV</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="jumlah" class="form-label">Jumlah <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="jumlah" name="dt[jumlah]" 
                                       value="<?= $data['jumlah'] ?>" placeholder="Jumlah orang" min="1" required>
                            </div>
                            <div class="col-md-4">
                                <label for="sistem" class="form-label">Sistem</label>
                                <select class="form-select" id="sistem" name="dt[sistem]">
                                    <option value="">Pilih Sistem</option>
                                    <option value="Onsite Banyuwangi" <?= $data['sistem'] == 'Onsite Banyuwangi' ? 'selected' : '' ?>>Onsite Banyuwangi</option>
                                    <option value="Onsite Yogyakarta" <?= $data['sistem'] == 'Onsite Yogyakarta' ? 'selected' : '' ?>>Onsite Yogyakarta</option>
                                    <option value="WFH" <?= $data['sistem'] == 'WFH' ? 'selected' : '' ?>>WFH</option>
                                    <option value="Hybrid" <?= $data['sistem'] == 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="estimasi_join" class="form-label">Estimasi Join</label>
                                <input type="date" class="form-control" id="estimasi_join" name="dt[estimasi_join]" 
                                       value="<?= $data['estimasi_join'] ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-card mb-4">
                    <h6 class="section-title">Kompetensi</h6>
                    <div class="section-content">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="kompetensi_dibutuhkan" class="form-label">Kompetensi yang Dibutuhkan</label>
                                <div id="kompetensi-editor"><?= $data['kompetensi_dibutuhkan'] ?></div>
                                <input type="hidden" id="kompetensi_dibutuhkan" name="dt[kompetensi_dibutuhkan]" value="<?= htmlspecialchars($data['kompetensi_dibutuhkan'] ?? '') ?>">
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle me-1"></i>Gunakan format yang jelas (bullet, link, penjelasan singkat).
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-card mb-4">
                    <h6 class="section-title">Status & Progress</h6>
                    <div class="section-content">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="dt[status]" required>
                                    <option value="">Pilih Status</option>
                                    <option value="CONTRACT" <?= $data['status'] == 'CONTRACT' ? 'selected' : '' ?>>CONTRACT</option>
                                    <option value="INTERNSHIP" <?= $data['status'] == 'INTERNSHIP' ? 'selected' : '' ?>>INTERNSHIP</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="approval_ceo" class="form-label">Approval CEO <span class="text-danger">*</span></label>
                                <select class="form-select" id="approval_ceo" name="dt[approval_ceo]" required>
                                    <option value="">Pilih Approval</option>
                                    <option value="Done" <?= $data['approval_ceo'] == 'Done' ? 'selected' : '' ?>>Done</option>
                                    <option value="Hold" <?= $data['approval_ceo'] == 'Hold' ? 'selected' : '' ?>>Hold</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="progress" class="form-label">Progres</label>
                                <select class="form-select" id="progress" name="dt[progress]">
                                    <option value="">Pilih Progres</option>
                                    <option value="RUN" <?= $data['progress'] == 'RUN' ? 'selected' : '' ?>>RUN</option>
                                    <option value="PENDING" <?= $data['progress'] == 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                                    <option value="DONE" <?= $data['progress'] == 'DONE' ? 'selected' : '' ?>>DONE</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="realisasi" class="form-label">Realisasi</label>
                                <input type="date" class="form-control" id="realisasi" name="dt[realisasi]" 
                                       value="<?= $data['realisasi'] ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-card mb-4">
                    <h6 class="section-title">Lampiran/Catatan</h6>
                    <div class="section-content">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="alasan_penambahan" class="form-label">Alasan Penambahan</label>
                                <textarea class="form-control" id="alasan_penambahan" name="dt[alasan_penambahan]" rows="4" 
                                          placeholder="Jelaskan alasan penambahan tenaga kerja..."><?= $data['alasan_penambahan'] ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-send">
                            <i class="bi bi-save me-1"></i> Update Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .form-label {
        font-weight: 500;
        margin-bottom: 8px;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.85);
    }

    .form-control, .form-select {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-control:hover, .form-select:hover {
        border-color: #40a9ff;
    }

    .form-control:focus, .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    textarea.form-control {
        height: auto;
        min-height: 80px;
    }

    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
    }

    .btn {
        border-radius: 2px;
        padding: 4px 15px;
        font-size: 14px;
        height: 32px;
        line-height: 1.5;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
    }

    .section-card {
        background: #fafafa;
        border: 1px solid #e8e8e8;
        border-radius: 4px;
        padding: 0;
        margin-bottom: 20px;
    }

    .section-title {
        background: #fff;
        padding: 12px 16px;
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: rgba(0, 0, 0, 0.85);
        border-bottom: 1px solid #e8e8e8;
        border-radius: 4px 4px 0 0;
    }

    .section-content {
        padding: 20px;
        background: #fff;
        border-radius: 0 0 4px 4px;
    }

    #kompetensi-editor {
        visibility: visible !important;
        border-radius: 8px !important;
    }

    .tox-tinymce {
        border: 1px solid #91d5ff !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
    }

    .tox .tox-toolbar,
    .tox .tox-toolbar__overflow,
    .tox .tox-toolbar__primary {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #dee2e6 !important;
    }

    .tox .tox-edit-area__iframe {
        background-color: white !important;
    }

    .tox .tox-statusbar {
        border-top: 1px solid #dee2e6 !important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script type="text/javascript">
    var kompetensiEditorInstance = null;

    function updateKompetensiHidden() {
        if (kompetensiEditorInstance) {
            $('#kompetensi_dibutuhkan').val(kompetensiEditorInstance.getContent());
            return;
        }
        var fallback = document.querySelector('#kompetensi-editor-fallback');
        if (fallback) {
            $('#kompetensi_dibutuhkan').val($(fallback).val());
        }
    }

    function setupKompetensiFallback() {
        var container = document.querySelector('#kompetensi-editor');
        if (!container) return;

        $('<div class="alert alert-warning mb-3" id="editor-fallback-notice">' +
            '<i class="bi bi-exclamation-triangle me-2"></i>' +
            'Rich text editor tidak dapat dimuat. Menggunakan editor teks sederhana.' +
        '</div>').insertBefore(container);

        var textarea = document.createElement('textarea');
        textarea.id = 'kompetensi-editor-fallback';
        textarea.className = 'form-control';
        textarea.rows = 8;
        textarea.placeholder = 'Masukkan kompetensi yang dibutuhkan...';
        textarea.value = container.textContent || '';

        container.style.display = 'none';
        container.parentNode.insertBefore(textarea, container.nextSibling);

        $(textarea).on('input', function() {
            $('#kompetensi_dibutuhkan').val($(this).val());
        });
    }

    function initializeKompetensiEditor() {
        try {
            if (typeof tinymce === 'undefined') {
                setupKompetensiFallback();
                return;
            }

            if (tinymce.get('kompetensi-editor')) {
                tinymce.remove('#kompetensi-editor');
            }

            tinymce.init({
                selector: '#kompetensi-editor',
                height: 260,
                min_height: 220,
                max_height: 500,
                menubar: false,
                branding: false,
                placeholder: 'Masukkan kompetensi yang dibutuhkan...\n\n• Tools/tech stack\n• Kemampuan wajib\n• Soft skill\n• Sertifikasi (jika ada)',
                plugins: 'lists link image table code help wordcount',
                toolbar: 'styles | bold italic underline | fontfamily fontsize | forecolor | alignleft aligncenter alignright | bullist numlist | link | code',
                font_family_formats: 'Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Helvetica=helvetica; Impact=impact,chicago; Tahoma=tahoma,arial,helvetica,sans-serif; Times New Roman=times new roman,times; Verdana=verdana,geneva',
                font_size_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 36pt',
                setup: function(editor) {
                    editor.on('init', function() {
                        kompetensiEditorInstance = editor;
                        updateKompetensiHidden();
                    });
                    editor.on('change', function() {
                        updateKompetensiHidden();
                    });
                },
                content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; padding: 10px; }',
                mobile: {
                    menubar: false,
                    toolbar: 'bold italic | bullist numlist | link'
                }
            });
        } catch (error) {
            setupKompetensiFallback();
        }
    }

    initializeKompetensiEditor();

    $("#form-edit").submit(function() {
        var form = $(this);
        updateKompetensiHidden();
        var mydata = new FormData(this);
        $.ajax({
            type: "POST",
            url: form.attr("action"),
            data: mydata,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $(".btn-send").addClass("disabled").html('<div class="spinner-border spinner-border-sm text-white me-2" role="status"></div>Mengupdate...').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                console.log(response);
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "<?= base_url() ?>/manpower_planning";
                    }, 2000);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Update Data').attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Update Data').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });
</script>
