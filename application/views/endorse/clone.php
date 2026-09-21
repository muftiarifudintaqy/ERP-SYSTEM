<div class="form-message"></div>
<form action="<?= base_url() ?>/endorse/clone-process" method="POST" id="form-modal">
    <input type="hidden" name="id" value="<?= $data['id'] ?>">
    <p>Apakah kamu yakin ingin melakukan kloning data?</p>
    
    <div class="col-md-12 mt-3">
        <div class="form-group">
            <label for="clone_count">Jumlah Kloning</label>
            <input type="number" 
                   name="clone_count" 
                   id="clone_count" 
                   class="form-control" 
                   value="1" 
                   min="1" 
                   max="100"
                   required>
            <small class="form-text text-muted">Masukkan jumlah data yang ingin dikloning (1-10)</small>
        </div>
    </div>
    
    <div class="col-md-12 mt-3">
        <button type="submit" class="btn btn-primary btn-send">Kloning Data</button>
    </div>
</form>

<script type="text/javascript">
    $("#form-modal").submit(function() {
        var form = $(this);
        var mydata = new FormData(this);
        
        // Validasi jumlah kloning
        var cloneCount = parseInt($("#clone_count").val());
        if (cloneCount < 1 || cloneCount > 100) {
            alert("Jumlah kloning harus antara 1-100");
            return false;
        }
        
        $.ajax({
            type: "POST",
            url: form.attr("action"),
            data: mydata,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $(".btn-send").addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                var str = response;
                console.log(str);
                if (str.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "";
                        $(".btn-send").removeClass("disabled").html('Kloning Data').attr('disabled', false);
                    }, 2500);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('Kloning Data').attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $(".btn-send").removeClass("disabled").html('Kloning Data').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });
</script>