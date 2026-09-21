<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Edit Side Quest</h5>
                <a href="<?= base_url() ?>quest" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-message"></div>
            <form action="<?= base_url() ?>quest/side_quest_update" method="POST" id="form-edit" novalidate>
                <input type="hidden" name="id" value="<?= $data['id'] ?>">
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="title" class="form-label">Judul Quest <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="dt[title]" 
                               value="<?= $data['title'] ?>" placeholder="Masukkan judul side quest..." required>
                        <small class="text-muted">Judul yang menarik dan jelas untuk quest ini</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="description" class="form-label">Deskripsi Quest <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="dt[description]" rows="5" 
                                  placeholder="Jelaskan detail quest, kriteria penilaian, dan ekspektasi yang diharapkan..." required><?= htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                        <small class="text-muted">Berikan deskripsi yang detail dan jelas tentang apa yang harus dikerjakan</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="points" class="form-label">Points <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="points" name="dt[points]" 
                               min="1" max="1000" value="<?= $data['points'] ?? 0 ?>" placeholder="0" required>
                        <small class="text-muted">Jumlah poin yang akan diberikan saat quest berhasil diselesaikan</small>
                    </div>
                    <div class="col-md-6">
                        <label for="gambar_animasi" class="form-label">Gambar Animasi</label>
                        <input type="file" class="form-control" id="gambar_animasi" name="gambar_animasi" 
                               accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <small class="text-muted">Upload gambar animasi baru (opsional). Format: JPG, PNG, GIF, WEBP. Max: 2MB</small>
                        <?php if (!empty($data['gambar_animasi'])): ?>
                            <div class="mt-2">
                                <small class="text-info">
                                    <i class="bi bi-image me-1"></i>Gambar saat ini: 
                                    <a href="<?= base_url() ?>assets/uploads/side_quest_animations/<?= $data['gambar_animasi'] ?>" 
                                       target="_blank" class="text-decoration-none"><?= $data['gambar_animasi'] ?></a>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="reward" class="form-label">Reward <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reward" name="dt[reward]" rows="3" 
                                  placeholder="Jelaskan reward atau manfaat yang akan didapat setelah menyelesaikan quest ini..." required><?= $data['reward'] ?? '' ?></textarea>
                        <small class="text-muted">Deskripsikan reward menarik yang memotivasi karyawan untuk menyelesaikan quest</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>Informasi Side Quest</h6>
                            <ul class="mb-0">
                                <li>Side quest terbuka untuk semua karyawan tanpa pembatasan level</li>
                                <li>Penilaian akan berdasarkan point system dengan 2 komponen:</li>
                                <ul>
                                    <li><strong>Notes Point:</strong> Kualitas catatan/dokumentasi</li>
                                    <li><strong>Presentation Point:</strong> Kualitas presentasi hasil</li>
                                </ul>
                                <li>Total point akan dihitung dan ditambahkan ke score karyawan</li>
                                <li><strong>Quest Points:</strong> Poin otomatis yang diberikan saat quest diselesaikan</li>
                                <li><strong>Milestone:</strong> Quest dapat berkontribusi pada pencapaian milestone</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-send">
                            <i class="bi bi-save me-1"></i> Update Quest
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script type="text/javascript">
    function getPlainTextFromHtml(html) {
        return $('<div>').html(html || '').text().replace(/\u00a0/g, ' ').trim();
    }

    $(document).ready(function() {
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '#description',
                height: 300,
                min_height: 260,
                max_height: 500,
                menubar: false,
                branding: false,
                placeholder: 'Jelaskan detail quest, kriteria penilaian, dan ekspektasi yang diharapkan...',
                plugins: 'lists link image table code help wordcount fullscreen',
                toolbar: 'styles | bold italic underline | fontfamily fontsize | forecolor | alignleft aligncenter alignright | bullist numlist | link image | table | code fullscreen help',
                font_family_formats: 'Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Helvetica=helvetica; Impact=impact,chicago; Tahoma=tahoma,arial,helvetica,sans-serif; Times New Roman=times new roman,times; Verdana=verdana,geneva',
                font_size_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 36pt',
                content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; padding: 10px; }',
                mobile: {
                    menubar: false,
                    toolbar: 'bold italic | bullist numlist | link'
                }
            });
        }
    });

    $("#form-edit").submit(function() {
        var form = $(this);
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }

        const title = $('#title').val().trim();
        const description = getPlainTextFromHtml($('#description').val());
        const points = parseInt($('#points').val(), 10);
        const reward = $('#reward').val().trim();
        let emptyFields = [];

        if (!title) {
            emptyFields.push('Judul Quest');
        }
        if (!description) {
            emptyFields.push('Deskripsi Quest');
        }
        if (!points || points <= 0) {
            emptyFields.push('Points');
        }
        if (!reward) {
            emptyFields.push('Reward');
        }

        if (emptyFields.length > 0) {
            const errorMsg = `<div class="alert alert-danger">
                <h6><i class="bi bi-exclamation-triangle me-2"></i>Field berikut belum valid:</h6>
                <ul class="mb-0">${emptyFields.map(field => `<li>${field}</li>`).join('')}</ul>
            </div>`;

            $(".form-message").hide().html(errorMsg).slideDown("fast");
            return false;
        }

        var mydata = new FormData(this);
        $.ajax({
            type: "POST",
            url: form.attr("action"),
            data: mydata,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $(".btn-send").addClass("disabled").html('<div class="spinner-border spinner-border-sm text-white me-2" role="status"></div>Menyimpan...').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                console.log(response);
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "<?= base_url() ?>quest";
                    }, 2000);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Update Quest').attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Update Quest').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });
</script>
