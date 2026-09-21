<div class="modal-header">
    <h5 class="modal-title">Hapus Peminjaman</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="alert alert-warning">
        Data peminjaman ini akan dihapus permanen dan tidak dapat dikembalikan.
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="button" class="btn btn-danger" onclick="deleteLoan('<?= $data['id'] ?>')">Hapus</button>
</div>

<script type="text/javascript">
    function deleteLoan(id) {
        $.ajax({
            type: "POST",
            url: "<?= base_url() ?>/asset_management/loan_delete",
            data: {id: id},
            success: function(response) {
                $("#popupModal").modal('hide');
                if (response.indexOf("success") != -1) {
                    location.reload();
                } else {
                    alert(response.replace(/<[^>]+>/g, ''));
                }
            },
            error: function() {
                alert('Terjadi kesalahan.');
            }
        });
    }
</script>
