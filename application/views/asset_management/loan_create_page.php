<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Tambah Peminjaman</h5>
                <a href="<?= base_url() ?>/asset_management/loans" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-message"></div>
            <form action="<?= base_url() ?>/asset_management/loan_store" method="POST" id="form-create">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="asset_id" class="form-label">Aset <span class="text-danger">*</span></label>
                        <select class="form-select" id="asset_id" name="dt[asset_id]" required>
                            <option value="">Pilih aset</option>
                            <?php foreach ($assets as $asset): ?>
                                <option value="<?= $asset['id'] ?>"><?= $asset['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="dt[status]">
                            <option value="Borrowed">Borrowed</option>
                            <option value="Returned">Returned</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="borrower_user_id" class="form-label">Nama Peminjam</label>
                        <select class="form-select" id="borrower_user_id" name="dt[borrower_user_id]" required>
                            <option value="">Pilih user</option>
                            <?php foreach ($borrowers as $borrower): ?>
                                <option value="<?= $borrower['id'] ?>"><?= $borrower['full_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="loan_date" class="form-label">Tanggal Pinjam</label>
                        <input type="date" class="form-control" id="loan_date" name="dt[loan_date]">
                    </div>
                    <div class="col-md-6">
                        <label for="return_date" class="form-label">Tanggal Kembali</label>
                        <input type="date" class="form-control" id="return_date" name="dt[return_date]">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="notes" class="form-label">Catatan</label>
                        <textarea class="form-control" id="notes" name="dt[notes]" rows="3" placeholder="Catatan tambahan"></textarea>
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
                        window.location.href = "<?= base_url() ?>/asset_management/loans";
                    }, 2000);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Simpan Data').attr('disabled', false);
                }
            },
            error: function(xhr) {
                $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Simpan Data').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });
</script>
