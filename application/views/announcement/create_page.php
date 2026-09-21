<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Tambah Announcement</h5>
                <a href="<?= base_url() ?>announcement" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-message"></div>
            <form action="<?= base_url() ?>announcement/store" method="POST" id="form-create" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Banner / Gambar <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="image" accept=".jpg,.jpeg,.png,.gif,.webp" required>
                        <small class="text-muted">Popup hanya menampilkan gambar ini. Format JPG/PNG/GIF/WEBP, maks 2MB.</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Judul / Label <small class="text-muted">(opsional, hanya untuk catatan admin)</small></label>
                        <input type="text" class="form-control" name="dt[title]" placeholder="mis. Promo Lebaran (tidak tampil di popup)">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Mulai Tayang</label>
                        <input type="date" class="form-control" name="dt[start_date]">
                        <small class="text-muted">Kosongkan = langsung tayang.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Selesai Tayang</label>
                        <input type="date" class="form-control" name="dt[end_date]">
                        <small class="text-muted">Kosongkan = tanpa batas.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Frekuensi Tampil</label>
                        <select class="form-select" name="dt[frequency]">
                            <option value="daily">1x per hari</option>
                            <option value="once">Sekali saja</option>
                            <option value="every_login">Setiap login</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="dt[is_active]" value="1" checked>
                            <label class="form-check-label" for="is_active">Aktifkan announcement</label>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-send">
                            <i class="bi bi-save me-1"></i> Simpan Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    $("#form-create").submit(function() {
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
                $(".btn-send").addClass("disabled").html('<div class="spinner-border spinner-border-sm text-white me-2" role="status"></div>Menyimpan...').attr('disabled', true);
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
                    $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Simpan Data').attr('disabled', false);
                }
            },
            error: function(xhr) {
                $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Simpan Data').attr('disabled', false);
                $(".form-message").hide().html(xhr.responseText).slideDown("fast");
            }
        });
        return false;
    });
</script>
