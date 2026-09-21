<div class="form-message"></div>
<form action="<?= base_url() ?>transaction/shipping_documents_execute" method="POST" id="form-shipping-documents">
    <input type="hidden" name="id_selected" value="<?= $id_selected ?>">
    
    <p>Cetak dokumen pengiriman untuk data terpilih?</p>

    <div class="col-md-12 mt-3">
        <button type="submit" class="btn btn-primary btn-send">Cetak Dokumen</button>
    </div>
</form>

<script>
function escapeHtml(value) {
    return String(value === undefined || value === null ? '' : value).replace(/[&<>"']/g, function (ch) {
        return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[ch]);
    });
}

$("#form-shipping-documents").submit(function() {
    var form = $(this);
    var mydata = new FormData(this);

    $.ajax({
        type: "POST",
        url: form.attr("action"),
        data: mydata,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        beforeSend: function() {
            $(".btn-send").addClass("disabled")
                .html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>')
                .attr('disabled', true);
            form.find(".form-message").slideUp().html("");
        },
        success: function(response) {
            $(".btn-send").removeClass("disabled").html('Cetak Dokumen').attr('disabled', false);

            var isSuccess = !!response.status;
            
            if (isSuccess) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message || 'Proses dokumen berhasil',
                    confirmButtonText: 'OK'
                });

                if (response.open_print && Array.isArray(response.print_payload) && response.print_payload.length) {
                    const url = "<?= base_url('transaction/print-shipping-docs') ?>";
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = url;
                    f.target = '_blank';

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'payload';
                    input.value = JSON.stringify({
                        status: true,
                        results: response.print_payload
                    });
                    f.appendChild(input);

                    document.body.appendChild(f);
                    f.submit();
                    f.remove();
                }

                if (response.open_print_shopee && Array.isArray(response.shopee_print_payload) && response.shopee_print_payload.length) {
                    const url = "<?= base_url('transaction/print-shipping-docs-shopee') ?>";
                    const formPrint = document.createElement('form');
                    formPrint.method = 'POST';
                    formPrint.action = url;
                    formPrint.target = '_blank';

                    const inputPdf = document.createElement('input');
                    inputPdf.type = 'hidden';
                    inputPdf.name = 'payload';
                    inputPdf.value = JSON.stringify(response.shopee_print_payload);
                    formPrint.appendChild(inputPdf);

                    document.body.appendChild(formPrint);
                    formPrint.submit();
                    formPrint.remove();
                }

                if (Array.isArray(response.download_links) && response.download_links.length) {
                    var linksHtml = '<div class="mt-3"><strong>Link Download:</strong><ul class="mb-0">';
                    response.download_links.forEach(function(url) {
                        linksHtml += '<li><a href="' + escapeHtml(url) + '" target="_blank" rel="noopener">' + escapeHtml(url) + '</a></li>';
                    });
                    linksHtml += '</ul></div>';
                    form.find(".form-message").hide().html(linksHtml).slideDown("fast");
                }

            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: response.message || 'Proses cetak dokumen gagal',
                    confirmButtonText: 'OK'
                });
            }

            if (Array.isArray(response.notes) && response.notes.length) {
                var notesHtml = '<ul class="mb-0 mt-2">';
                response.notes.forEach(function(note) {
                    notesHtml += '<li>' + escapeHtml(note) + '</li>';
                });
                notesHtml += '</ul>';
                form.find(".form-message").hide().html(notesHtml).slideDown("fast");
            }
        },
        error: function(xhr) {
            $(".btn-send").removeClass("disabled").html('Cetak Dokumen').attr('disabled', false);
            var responseText = xhr.responseText || 'Terjadi kesalahan.';
            try {
                var parsed = JSON.parse(responseText);
                responseText = parsed.message || 'Terjadi kesalahan.';
            } catch (e) {
                responseText = 'Terjadi kesalahan.';
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: responseText,
                confirmButtonText: 'OK'
            });
        }
    });
    return false;
});
</script>
