<div class="form-message"></div>
<style>
    .select2-container .select2-selection--multiple {
        min-height: 45px;
        border-radius: 0.5rem !important;
        border: 1px solid #ced4da;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        padding: 6px;
        background-color: #f8f9fa;
        color: #212529;
        border-radius: 0.25rem;
        margin-right: 4px;
    }

    .select2-container .select2-search--inline .select2-search__field {
        padding-top: 6px !important;
        font-size: 14px;
    }

    .kpi-metric-inline {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 20px;
        align-items: center;
    }
</style>

<?php
$kpi_config = [
    'kpi_include_views' => (int) ($data['kpi_include_views'] ?? 1),
    'kpi_include_engagement' => (int) ($data['kpi_include_engagement'] ?? 1),
    'kpi_include_total_konten' => (int) ($data['kpi_include_total_konten'] ?? 1),
    'kpi_include_fyp' => (int) ($data['kpi_include_fyp'] ?? 1),
    'kpi_include_cpm' => (int) ($data['kpi_include_cpm'] ?? 1),
    'kpi_include_total_cost' => (int) ($data['kpi_include_total_cost'] ?? 1),
];
$kpi_metric_labels = [
    'kpi_include_views' => 'Views',
    'kpi_include_engagement' => 'Engagement',
    'kpi_include_total_konten' => 'Total Konten',
    'kpi_include_fyp' => 'FYP',
    'kpi_include_cpm' => 'CPM',
    'kpi_include_total_cost' => 'Total Cost',
];
$update_terbatas = (int) ($data['update_terbatas'] ?? 0);
$update_batas_hari = (int) ($data['update_batas_hari'] ?? 30);
if ($update_batas_hari <= 0) {
    $update_batas_hari = 30;
}
?>

<form action="<?= base_url() ?>/endorse-campaign/store" method="POST" id="form-modal">
    <input type="hidden" name="id" value="<?= $data['id'] ?>">
    <input type="hidden" name="id_campaign" value="<?= $data['id'] ?>">

    <div class="d-flex align-items-center">
        <div class="form-check me-3">
            <input class="form-check-input" type="checkbox" id="internal_cb" name="dt[is_internal]" value="1"
                <?= (isset($_GET['p']) && $_GET['p'] === 'internal') ? 'checked' : '' ?>
                onclick="handleCheckboxChange('internal')">
            <label class="form-check-label" for="internal_cb">
                Internal
            </label>
        </div>

        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="external_cb" name="dt[is_internal]" value="0"
                <?= (isset($_GET['p']) && $_GET['p'] === 'external') ? 'checked' : '' ?>
                onclick="handleCheckboxChange('external')">
            <label class="form-check-label" for="external_cb">
                External
            </label>
        </div>
    </div>

    <div class="mt-3 mb-3" id="kpi-campaign-section" style="display:none;">
        <label class="d-block">KPI Campaign</label>
        <div class="kpi-metric-inline mt-2" id="kpi-metric-options">
            <?php foreach ($kpi_metric_labels as $field => $label): ?>
                <div>
                    <input type="hidden" name="dt[<?= $field ?>]" value="0">
                    <div class="form-check mb-0">
                        <input class="form-check-input kpi-metric-checkbox" type="checkbox" id="<?= $field ?>" name="dt[<?= $field ?>]" value="1" <?= $kpi_config[$field] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="<?= $field ?>"><?= $label ?></label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label>Judul Campaign</label>
            <input type="text" class="form-control" name="dt[title]" value="<?= $data['title'] ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Produk</label>
            <select class="form-control select2" id="product-select" multiple>
                <?php foreach ($produk as $v): ?>
                    <?php $selected = in_array($v['id'], explode(',', $data['product'] ?? '')) ? 'selected' : ''; ?>
                    <option <?= $selected ?> value="<?= $v['id'] ?>" data-product_text="<?= htmlspecialchars($v['name']) ?>">
                        <?= htmlspecialchars($v['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="dt[product]" id="product-hidden">
            <input type="hidden" name="dt[product_text]" id="product-text">
        </div>

        <div class="col-md-6 mb-3">
            <label>Brand</label>
            <select class="form-control" name="dt[brand]">
                <?php foreach ($brand as $v2): ?>
                    <option <?= $data['brand'] == $v2['code'] ? 'selected' : '' ?> value="<?= $v2['code'] ?>">
                        <?= $v2['code'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="brand" value="<?= $data['brand'] ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>PIC</label>
            <select class="form-control select2" name="dt[pic]">
                <?php
                $selectedPic = !empty($data['pic']) ? $data['pic'] : $user['full_name'];
                foreach ($pic as $v2) {
                    $text = $selectedPic == $v2['full_name'] ? 'selected' : '';
                    echo "<option $text value='{$v2['full_name']}'>{$v2['full_name']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Tanggal Mulai</label>
            <input type="date" class="form-control" name="dt[start_at]" value="<?= DATE("Y-m-d") ?>">
        </div>

        <div class="col-md-6">
			<label for="">SPV</label>
			<select type="text" class="form-control select2" name="dt[spv]">
				<?php
				$selectedPic = !empty($data['spv']) ? $data['spv'] : $user['full_name'];

				foreach ($spv as $v2) {
					$text = $selectedPic == $v2['full_name'] ? 'selected' : '';
					echo "<option $text value='{$v2['full_name']}'>{$v2['full_name']}</option>";
				}
				?>
			</select>
		</div>

        <div class="col-md-6 mb-3" id="until-at-wrapper">
            <label>Tanggal Selesai</label>
            <input type="date" class="form-control" name="dt[until_at]" value="<?= DATE("Y-m-d") ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Budget</label>
            <input type="text" class="form-control" id="budget_formatted" value="<?= $data['budget'] ?>">
            <input type="hidden" name="dt[budget]" id="budget" value="<?= $data['budget'] ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Status</label>
            <select class="form-control" name="dt[status]">
                <?php foreach (["Aktif", "Tidak Aktif"] as $v2): ?>
                    <option <?= $data['status'] == $v2 ? 'selected' : '' ?> value="<?= $v2 ?>"><?= $v2 ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <input type="hidden" name="dt[update_terbatas]" value="0">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="update_terbatas" name="dt[update_terbatas]" value="1" <?= $update_terbatas === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="update_terbatas">Update Terbatas</label>
            </div>
            <label>Batas</label>
            <input type="number" class="form-control" id="update_batas_hari" name="dt[update_batas_hari]" min="1" step="1" value="<?= $update_batas_hari ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Deskripsi</label>
            <textarea class="form-control" name="dt[desc]" style="min-height:160px!important"><?= $data['desc'] ?></textarea>
        </div>
    </div>

    <div class="col-12 mt-3">
        <button type="submit" class="btn btn-primary btn-send">Simpan Data</button>
    </div>
</form>

<script type="text/javascript">
    $(document).ready(function () {
        $('.select2').select2();

        $('#product-select').on('change', function () {
            const selectedOptions = $(this).find(':selected');
            const values = selectedOptions.map(function () { return this.value; }).get().join(',');
            const texts = selectedOptions.map(function () { return $(this).text().trim(); }).get().join(',');

            $('#product-hidden').val(values);
            $('#product-text').val(texts);
        });

        $('#product-select').trigger('change');
        syncKpiCampaignSection();
        syncLimitedUpdateFields();

        $('#update_terbatas').on('change', syncLimitedUpdateFields);

    });

    function handleCheckboxChange(value) {
        if (value === 'internal') {
            $('#external_cb').prop('checked', false);
        } else if (value === 'external') {
            $('#internal_cb').prop('checked', false);
        }
        syncKpiCampaignSection();
    }

    function syncKpiCampaignSection() {
        const isExternal = $('#external_cb').is(':checked');
        $('#kpi-campaign-section').toggle(isExternal);
    }

    function syncLimitedUpdateFields() {
        const isLimited = $('#update_terbatas').is(':checked');
        $('#until-at-wrapper').toggle(!isLimited);
        $('#update_batas_hari').prop('disabled', !isLimited);
    }

    document.getElementById('budget_formatted').addEventListener('input', function (e) {
        let value = this.value.replace(/[^0-9]/g, '');
        document.getElementById('budget').value = value;
        if (value.length > 0) value = parseInt(value).toLocaleString('id-ID');
        this.value = value;
    });

    $("#form-modal").submit(function () {
        var form = $(this);
        var mydata = new FormData(this);
        $.ajax({
            type: "POST",
            url: form.attr("action"),
            data: mydata,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $(".btn-send").addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function (response) {
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function () {
                        window.location.href = "";
                    }, 2500);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                }
                $(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
            },
            error: function (xhr) {
                $(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });
</script>
