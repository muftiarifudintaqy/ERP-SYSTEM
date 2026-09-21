<div class="form-message"></div>
<form action="<?= base_url() ?>/crm/update" method="POST" id="form-modal" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= (int)$data['id'] ?>">

    <div class="crm-edit-sections">
        <div class="crm-edit-section">
            <h6 class="crm-edit-title">Data Pelanggan</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>CB/CL</label>
                    <select class="form-control" name="dt[cb_cl]">
                        <?php foreach (array('CB', 'CL') as $opt) { ?>
                            <option value="<?= $opt ?>" <?= ($data['cb_cl'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Nama Lengkap</label>
                    <input type="text" class="form-control" name="dt[full_name]" value="<?= htmlspecialchars($data['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label>Username</label>
                    <input type="text" class="form-control" name="dt[username]" value="<?= htmlspecialchars(($data['username'] ?? ($data['user_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label>No HP</label>
                    <input type="text" class="form-control" name="dt[phone]" value="<?= htmlspecialchars($data['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label>Tanggal Lahir</label>
                    <input type="date" class="form-control" name="dt[birth_date]" value="<?= htmlspecialchars($data['birth_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label>Channel</label>
                    <select class="form-control" name="dt[marketplace]">
                        <?php foreach (($marketplace ?? array()) as $row) { ?>
                            <option value="<?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>" <?= ($data['marketplace'] ?? '') === $row['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="crm-edit-section">
            <h6 class="crm-edit-title">Alamat</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Provinsi</label>
                    <input type="text" class="form-control" name="dt[province_text]" value="<?= htmlspecialchars($data['province_text'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label>Kota</label>
                    <input type="text" class="form-control" name="dt[city_text]" value="<?= htmlspecialchars($data['city_text'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label>Kecamatan</label>
                    <input type="text" class="form-control" name="dt[subdistrict_text]" value="<?= htmlspecialchars($data['subdistrict_text'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-12">
                    <label>Alamat Lengkap</label>
                    <textarea class="form-control" name="dt[address]" rows="2"><?= htmlspecialchars($data['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <div class="crm-edit-section">
            <h6 class="crm-edit-title">Catatan CRM</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Keterangan</label>
                    <textarea class="form-control" name="dt[desc]" rows="2"><?= htmlspecialchars($data['desc'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label>Keluhan</label>
                    <textarea class="form-control" name="dt[keluhan]" rows="2"><?= htmlspecialchars($data['keluhan'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label>Customer Experience</label>
                    <textarea class="form-control" name="dt[customer_experience]" rows="2"><?= htmlspecialchars($data['customer_experience'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-md-6 d-flex align-items-center pt-4">
                    <?php $isJoinKomunitas = in_array(strtolower((string)($data['join_komunitas'] ?? '0')), array('1', 'true', 'yes', 'ya', 'on'), true); ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="joinKomunitas" name="dt[join_komunitas]" value="1" <?= $isJoinKomunitas ? 'checked' : '' ?>>
                        <label class="form-check-label" for="joinKomunitas">Join Komunitas</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="crm-edit-section">
            <h6 class="crm-edit-title mb-2">Campaign / Broadcast CRM</h6>

            <div class="row g-3">
                <div class="col-md-8">
                    <label>Pilih Campaign / Broadcast</label>
                    <select class="form-control" id="campaignBroadcastSelect">
                        <option value="">-- Pilih Campaign/Broadcast --</option>
                        <?php foreach (($campaign_options ?? array()) as $opt) { ?>
                            <option value="<?= (int)$opt['id'] ?>" <?= ((int)($selected_campaign_id ?? 0) === (int)$opt['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opt['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php } ?>
                    </select>
                    <input type="hidden" name="campaign_broadcast_id" id="campaignBroadcastId" value="<?= (int)($selected_campaign_id ?? 0) ?>">
                    <input type="hidden" name="dt[campaign_broadcast]" id="campaignBroadcastName" value="<?= htmlspecialchars($data['campaign_broadcast'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="includeCustomerCampaign" <?= ((int)($selected_campaign_id ?? 0) > 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="includeCustomerCampaign">Masukkan customer ke campaign ini</label>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary btn-send">Simpan Data</button>
    </div>
</form>

<style>
    .crm-edit-section {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 12px;
        background: #fff;
    }

    .crm-edit-title {
        font-weight: 600;
        margin-bottom: 10px;
    }

</style>

<script type="text/javascript">
    function syncCampaignFields() {
        var selectedId = $('#campaignBroadcastSelect').val() || '';
        var selectedName = $('#campaignBroadcastSelect option:selected').text() || '';
        var includeCustomer = $('#includeCustomerCampaign').is(':checked');

        if (!includeCustomer || selectedId === '') {
            $('#campaignBroadcastId').val('0');
            $('#campaignBroadcastName').val('');
            return;
        }

        $('#campaignBroadcastId').val(selectedId);
        $('#campaignBroadcastName').val(selectedName.trim());
    }

    $('#campaignBroadcastSelect').on('change', function() {
        syncCampaignFields();
    });

    $('#includeCustomerCampaign').on('change', function() {
        syncCampaignFields();
    });

    $("#form-modal").submit(function() {
        syncCampaignFields();
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
                $(".btn-send").addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response) {
                var str = response;
                if (str.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "";
                        $(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
                    }, 1500);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
                }
            },
            error: function(xhr) {
                $(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
                $(".form-message").hide().html(xhr.responseText || xhr).slideDown("fast");
            }
        });
        return false;
    });

    $(document).ready(function() {
        syncCampaignFields();
    });
</script>
