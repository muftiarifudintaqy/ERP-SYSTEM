<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Edit Announcement</h5>
                <a href="<?= base_url() ?>announcement" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-message"></div>
            <form action="<?= base_url() ?>announcement/update" method="POST" id="form-edit" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $data['id'] ?>">

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Banner / Gambar</label>
                        <?php if (!empty($data['image'])): ?>
                            <div class="mb-2">
                                <img src="<?= base_url() ?>assets/uploads/announcements/<?= $data['image'] ?>" alt="banner"
                                    style="max-width: 200px; border-radius: 6px; border: 1px solid #f0f0f0;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="image" accept=".jpg,.jpeg,.png,.gif,.webp">
                        <small class="text-muted">Popup hanya menampilkan gambar ini. Kosongkan jika tidak ingin mengganti. Maks 2MB.</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Judul / Label <small class="text-muted">(opsional, hanya untuk catatan admin)</small></label>
                        <input type="text" class="form-control" name="dt[title]" value="<?= htmlspecialchars($data['title'] ?? '') ?>" placeholder="mis. Promo Lebaran (tidak tampil di popup)">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Mulai Tayang</label>
                        <input type="date" class="form-control" name="dt[start_date]" value="<?= $data['start_date'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Selesai Tayang</label>
                        <input type="date" class="form-control" name="dt[end_date]" value="<?= $data['end_date'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Frekuensi Tampil</label>
                        <select class="form-select" name="dt[frequency]">
                            <option value="daily" <?= $data['frequency'] == 'daily' ? 'selected' : '' ?>>1x per hari</option>
                            <option value="once" <?= $data['frequency'] == 'once' ? 'selected' : '' ?>>Sekali saja</option>
                            <option value="every_login" <?= $data['frequency'] == 'every_login' ? 'selected' : '' ?>>Setiap login</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="dt[is_active]" value="1" <?= $data['is_active'] == 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Aktifkan announcement</label>
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

<script type="text/javascript">
    $("#form-edit").submit(function() {
        var form = $(this);
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
            success: function(response) {
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "<?= base_url() ?>announcement";
                    }, 1500);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Update Data').attr('disabled', false);
                }
            },
            error: function(xhr) {
                $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Update Data').attr('disabled', false);
                $(".form-message").hide().html(xhr.responseText).slideDown("fast");
            }
        });
        return false;
    });
</script>
