<div class="container">
    <?php if ($this->session->flashdata('message')): ?>
        <div class="alert alert-success"><?= $this->session->flashdata('message') ?></div>
    <?php endif; ?>
    
    <?php if ($this->session->flashdata('errors')): ?>
        <div class="alert alert-danger"><?= $this->session->flashdata('errors') ?></div>
    <?php endif; ?>
    
    <form action="<?= site_url('recruitment/save_position') ?>" method="post">
        <div class="mb-3">
            <input type="text" class="form-control" id="position_name" name="position_name" placeholder="Tambah Posisi Baru" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
    
    <div class="mt-4">
        <h5>List Posisi</h5>
        <div class="d-flex flex-wrap gap-2 mt-3" id="positions-list">
            <?php foreach ($positions as $position): ?>
                <div class="p-2 bg-primary text-white rounded-2 d-flex align-items-center position-item" data-id="<?= $position->id ?>">
                    <span class="me-2"><?= htmlspecialchars($position->position_name) ?></span>
                    <a href="#" class="text-white lh-1 delete-position" 
                       style="text-decoration: none; opacity: 0.7;">
                        <i class="bi bi-x-circle" style="font-size: 0.9rem;"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle form submission
    $('form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            type: form.attr('method'),
            url: form.attr('action'),
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Clear input
                    $('#position_name').val('');
                    
                    // Add new position to list
                    var newPosition = `
                        <div class="p-2 bg-primary text-white rounded-2 d-flex align-items-center position-item" data-id="${response.data.id}">
                            <span class="me-2">${response.data.position_name}</span>
                            <a href="#" class="text-white lh-1 delete-position" 
                               style="text-decoration: none; opacity: 0.7;">
                                <i class="bi bi-x-circle" style="font-size: 0.9rem;"></i>
                            </a>
                        </div>
                    `;
                    $('#positions-list').append(newPosition);
                    
                    // Show success message
                    alert('Posisi berhasil ditambahkan');
                } else {
                    alert('Gagal menambahkan posisi: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan saat menyimpan posisi');
            }
        });
    });

    // Handle position deletion
    $(document).on('click', '.delete-position', function(e) {
        e.preventDefault();
        var positionItem = $(this).closest('.position-item');
        var positionId = positionItem.data('id');
        
        if (confirm('Apakah Anda yakin ingin menghapus posisi ini?')) {
            $.ajax({
                url: '<?= site_url('recruitment/delete_position/') ?>' + positionId,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        positionItem.fadeOut(200, function() {
                            $(this).remove();
                        });
                        alert('Posisi berhasil dihapus');
                    } else {
                        alert('Gagal menghapus posisi: ' + response.message);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat menghapus posisi');
                }
            });
        }
    });
});
</script>