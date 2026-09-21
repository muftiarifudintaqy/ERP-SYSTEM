<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Edit Aset</h5>
                <a href="<?= base_url() ?>/asset_management" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-message"></div>
            <form action="<?= base_url() ?>/asset_management/update" method="POST" id="form-edit">
                <input type="hidden" name="id" value="<?= $data['id'] ?>">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="code" class="form-label">Kode Aset <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="dt[code]" value="<?= $data['code'] ?>" required>
                    </div>
                    <div class="col-md-8">
                        <label for="name" class="form-label">Nama Aset <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="dt[name]" value="<?= $data['name'] ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="category" class="form-label">Kategori</label>
                        <input type="text" class="form-control" id="category" name="dt[category]" value="<?= $data['category'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="owner_team" class="form-label">Tim Pemilik</label>
                        <input type="text" class="form-control" id="owner_team" name="dt[owner_team]" value="<?= $data['owner_team'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="condition" class="form-label">Kondisi</label>
                        <input type="text" class="form-control" id="condition" name="dt[condition]" value="<?= $data['condition'] ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="location" class="form-label">Lokasi</label>
                        <input type="text" class="form-control" id="location" name="dt[location]" value="<?= $data['location'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="purchase_date" class="form-label">Tanggal Pembelian</label>
                        <input type="date" class="form-control" id="purchase_date" name="dt[purchase_date]" value="<?= $data['purchase_date'] ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="notes" class="form-label">Catatan</label>
                        <textarea class="form-control" id="notes" name="dt[notes]" rows="3"><?= $data['notes'] ?></textarea>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-send">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
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
                $(".btn-send").addClass("disabled").html('<div class="spinner-border spinner-border-sm text-white me-2" role="status"></div>Menyimpan...').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response) {
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "<?= base_url() ?>/asset_management";
                    }, 2000);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Simpan Perubahan').attr('disabled', false);
                }
            },
            error: function(xhr) {
                $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Simpan Perubahan').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });
</script>
