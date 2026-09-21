<?php
$allowed_view_modes = array('card', 'table');
$current_view_mode = isset($_GET['view']) && in_array($_GET['view'], $allowed_view_modes, true) ? $_GET['view'] : 'table';
$selected_channels = isset($selected_channels) && is_array($selected_channels) ? $selected_channels : array();
$selected_shop_ids = isset($selected_shop_ids) && is_array($selected_shop_ids) ? $selected_shop_ids : array();
$selected_shipping = isset($selected_shipping) && is_array($selected_shipping) ? $selected_shipping : array();
$selected_brand_multi = isset($selected_brand_multi) && is_array($selected_brand_multi) ? $selected_brand_multi : array();
$selected_segment_multi = isset($selected_segment_multi) && is_array($selected_segment_multi) ? $selected_segment_multi : array();
$selected_product_filter = isset($product_filter) && is_array($product_filter) ? $product_filter : array();
$order_start_date = isset($order_start_date) && trim((string)$order_start_date) !== '' ? (string)$order_start_date : (isset($_GET['order_start_date']) && trim((string)$_GET['order_start_date']) !== '' ? (string)$_GET['order_start_date'] : '');
$order_until_date = isset($order_until_date) && trim((string)$order_until_date) !== '' ? (string)$order_until_date : (isset($_GET['order_until_date']) && trim((string)$_GET['order_until_date']) !== '' ? (string)$_GET['order_until_date'] : '');
$repeat_start_date = isset($repeat_start_date) && trim((string)$repeat_start_date) !== '' ? (string)$repeat_start_date : (isset($_GET['repeat_start_date']) && trim((string)$_GET['repeat_start_date']) !== '' ? (string)$_GET['repeat_start_date'] : '');
$repeat_until_date = isset($repeat_until_date) && trim((string)$repeat_until_date) !== '' ? (string)$repeat_until_date : (isset($_GET['repeat_until_date']) && trim((string)$_GET['repeat_until_date']) !== '' ? (string)$_GET['repeat_until_date'] : '');
?>
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

    /* Select2 Styling */
    .select2-container .select2-selection--single {
        box-sizing: border-box;
        cursor: pointer;
        display: block;
        height: 32px;
        user-select: none;
        -webkit-user-select: none;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: rgba(0, 0, 0, 0.85);
        line-height: 32px;
        padding-left: 11px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px;
        position: absolute;
        top: 0px;
        right: 1px;
        width: 20px;
    }

    .select2 {
        height: 32px !important;
        min-width: 100% !important;
        margin-bottom: 8px;
    }

    /* Ant Design-like Table Styling */
    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #f0f0f0;
        border-radius: 2px;
    }

    .table thead th {
        background-color: #fafafa;
        color: rgba(0, 0, 0, 0.85);
        font-weight: 500;
        text-align: left;
        padding: 12px 8px;
        font-size: 14px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table tbody td {
        padding: 12px 8px !important;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table-hover tbody tr:hover {
        background-color: #fafafa;
    }

    /* Card Styling - Ant Design-like */
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
        margin-bottom: 16px;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
        height: 56px;
    }

    .card-body {
        padding: 16px;
    }

    /* Button Styling - Ant Design-like */
    .btn {
        border-radius: 2px;
        padding: 4px 15px;
        font-size: 14px;
        height: 32px;
        line-height: 1.5;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
    }

    .btn-outline-secondary {
        color: rgba(0, 0, 0, 0.65);
        border-color: #d9d9d9;
        background: #fff;
    }

    .btn-outline-secondary:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Badge Styling - Ant Design-like */
    .badge {
        font-size: 12px;
        height: 22px;
        padding: 0 8px;
        line-height: 22px;
        border-radius: 2px;
        font-weight: normal;
    }

    .crm-campaign-preview-box {
        border: 1px solid #d9e6f2;
        background: #f7fbff;
        border-radius: 4px;
        padding: 10px 12px;
    }

    .crm-campaign-preview-message {
        white-space: pre-wrap;
        max-height: 160px;
        overflow: auto;
        color: #2f3a4a;
    }

    .crm-broadcast-time-options {
        display: grid;
        gap: 8px;
    }

    .crm-broadcast-time-option {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        color: #1f2937;
        font-size: 14px;
    }

    .crm-broadcast-time-option input[type="radio"] {
        flex: 0 0 auto;
    }

    .crm-broadcast-time-option .form-control {
        width: 190px;
        height: 34px;
        margin-left: 4px;
    }

    /* Pagination Styling - Ant Design-like */
    .pagination {
        margin-top: 16px;
        justify-content: flex-end;
    }

    .page-item {
        margin-right: 8px;
    }

    .page-item:last-child {
        margin-right: 0;
    }

    .page-item.active .page-link {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .page-link {
        min-width: 32px;
        height: 32px;
        line-height: 30px;
        text-align: center;
        color: rgba(0, 0, 0, 0.65);
        border-radius: 2px;
        padding: 0;
        margin: 0;
        border: 1px solid #d9d9d9;
    }

    .page-link:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Search Form Styling - Ant Design-like */
    .search-form {
        margin-bottom: 16px;
    }

    .search-form .input-group {
        border-radius: 2px;
        display: flex;
        justify-content: space-between;
    }

    .form-control {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-control:hover {
        border-color: #40a9ff;
    }

    .form-control:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .form-select {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-select:hover {
        border-color: #40a9ff;
    }

    .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    /* Alert Styling - Ant Design-like */
    .alert {
        padding: 8px 15px;
        border-radius: 2px;
        font-size: 14px;
    }

    .alert-info {
        background-color: #e6f7ff;
        border-color: #91d5ff;
        color: rgba(0, 0, 0, 0.65);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }

    .crm-filter-check {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 13px;
        background: #fff;
    }

    .crm-filter-input {
        margin: 0;
    }

    .crm-marketplace-icon {
        width: 18px;
        height: 18px;
        object-fit: contain;
    }

    .crm-table-loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.78);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 30;
        border-radius: 8px;
    }

    .crm-table-loading-overlay.active {
        display: flex;
    }

    .crm-table-loading-box {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 10px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        box-shadow: 0 6px 22px rgba(15, 23, 42, 0.12);
        color: #334155;
        font-size: 13px;
        font-weight: 600;
    }
</style>
</style>
<div class="form-message"></div>
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12">
            <form action="" id="form-search">
                <div class="row">
                    <input type="hidden" name="ids" value="<?= $ids ?>">
                    <div class="col-lg-4">
                        <!-- <label for="">KEYWORD</label> -->
                        <div class="input-group">
                            <button class="btn btn-outline-secondary-category dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-top-right-radius: 0px !important;
                            border-bottom-right-radius: 0px !important;"><?= $keyword_category ?></button>
                            <ul class="dropdown-menu">
                                <?php
                                $arr = array();
                                $arr[] = 'Order ID';
                                $arr[] = 'Username';
                                $arr[] = 'Nama Customer';
                                $arr[] = 'Nomer Pesanan';
                                $arr[] = 'Nama Produk';
                                foreach ($arr as $k => $val) {
                                    $class = "btn-default";
                                    if ($_GET['order_status'] == $val) {
                                        $class = "btn-default-selected";
                                    }
                                ?>
                                    <li><a class="dropdown-item" href="<?= $url ?>&keyword_category=<?= $val ?>"><?= $val ?></a></li>
                                <?php }  ?>
                            </ul>
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?>">
                            <input type="text" name="keyword" class="form-control" value="<?= $_GET['keyword'] ?>" style="border-top-left-radius: 0px !important;
                            border-bottom-left-radius: 0px !important;">
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="d-flex justify-content-end align-items-center gap-2">
                            <div style="max-width: 200px; margin-top: 11px !important; width: 100%;">
                                <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                                <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                                <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                            </div>

                            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#crmFilterModal">
                                <i class="bi bi-funnel me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                    <script>
                        function updateCrmDateWithAjax() {
                            var params = new URLSearchParams(window.location.search);
                            var startDate = $('#start_date').val() || "<?= $_GET['start_date'] ?? $start_date ?>";
                            var endDate = $('#end_date').val() || "<?= $_GET['until_date'] ?? $until_date ?>";
                            params.set('start_date', startDate);
                            params.set('until_date', endDate);
                            params.set('page', '1');
                            params.set('view', 'table');
                            var newUrl = window.location.pathname + '?' + params.toString();
                            window.history.replaceState({}, '', newUrl);
                            if (typeof loadMoreData === 'function') {
                                loadMoreData();
                            }
                        }

                        function initCrmDateFilterInput(config) {
                            var inputId = config.inputId;
                            var startId = config.startId;
                            var endId = config.endId;
                            var defaultStartDate = config.defaultStartDate || '';
                            var defaultEndDate = config.defaultEndDate || '';
                            var onApply = typeof config.onApply === 'function' ? config.onApply : function() {};

                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>/ajax/get-filter',
                                data: {
                                    start_date: $('#' + startId).val() || defaultStartDate,
                                    until_date: $('#' + endId).val() || defaultEndDate,
                                    input_id: inputId,
                                    start_id: startId,
                                    end_id: endId,
                                },
                                success: function(response) {
                                    var $input = $('#' + inputId);
                                    $input.next('.dropdown').remove();
                                    $input.after(response.html);

                                    $('#' + inputId).off('apply.daterangepicker.crmDate_' + inputId).on('apply.daterangepicker.crmDate_' + inputId, function(ev, picker) {
                                        $('#' + startId).val(picker.startDate.format('YYYY-MM-DD'));
                                        $('#' + endId).val(picker.endDate.format('YYYY-MM-DD'));
                                        onApply(picker);
                                    });
                                },
                                error: function(xhr, status, error) {
                                    console.error("Error loading filter:", error);
                                }
                            });
                        }

                        initCrmDateFilterInput({
                            inputId: 'tanggal',
                            startId: 'start_date',
                            endId: 'end_date',
                            defaultStartDate: "<?= $_GET['start_date'] ?? $start_date ?>",
                            defaultEndDate: "<?= $_GET['until_date'] ?? $until_date ?>",
                            onApply: function() {
                                updateCrmDateWithAjax();
                            }
                        });

                        // Event handler untuk dropdown kategori tanggal
                        $(document).ready(function() {
                            $('.dropdown-item[data-type]').on('click', function(e) {
                                e.preventDefault();
                                var type = $(this).data('type');
                                var label = $(this).text();
                                
                                // Update teks tombol dropdown
                                $(this).closest('.input-group').find('.dropdown-toggle').text(label);
                                
                                // Update nilai hidden field
                                $('#date_type').val(type);
                                
                                // Refresh filter dengan tipe tanggal yang baru
                                initCrmDateFilterInput({
                                    inputId: 'tanggal',
                                    startId: 'start_date',
                                    endId: 'end_date',
                                    defaultStartDate: "<?= $_GET['start_date'] ?? $start_date ?>",
                                    defaultEndDate: "<?= $_GET['until_date'] ?? $until_date ?>",
                                    onApply: function() {
                                        updateCrmDateWithAjax();
                                    }
                                });
                            });
                        });
                    </script>
                    <input type="hidden" name="view" value="<?= $current_view_mode ?>">
                </div>
                
            </form>
            <div class="col-lg-12">
                <a href="<?= base_url() ?>crm/create<?= $param ?>" class="btn btn-primary mb-2"><i class="bi bi-plus-circle-dotted fs-16"></i> Tambah Data</a>
                <a href="<?= base_url() ?>scraper" class="btn btn-primary mb-2"><i class="bi bi-bootstrap-reboot fs-16"></i> Scraper Data</a>
                <a target="_blank" id="crmDownloadBtn" href="<?= base_url() ?>crm/download<?= $param ?>" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-download fs-16"></i> Download</a>
                <button type="button" class="btn mb-2 btn-outline-primary px-2 mt-0 ms-1" data-bs-toggle="modal" data-bs-target="#crmCampaignManagerModal">
                    <i class="bi bi-plus-circle fs-16"></i> Campaign/Broadcast
                </button>
                <button type="button" class="btn mb-2 btn-primary px-2 mt-0 ms-1" id="crmBroadcastOpenBtn">
                    <i class="bi bi-send fs-16"></i> Kirim Broadcast (<span id="crmBroadcastSelectedCount">0</span>)
                </button>
                <a href="<?= base_url() ?>crm/chat" class="btn mb-2 btn-outline-primary px-2 mt-0 ms-1"><i class="bi bi-chat-dots fs-16"></i> Customer Chat</a>
                <a href="<?= base_url() ?>crm/analytics<?= $param ?>" class="btn mb-2 btn-outline-primary px-2 mt-0 ms-1"><i class="bi bi-bar-chart-line fs-16"></i> Customer Analytics</a>
                <!-- <a href="<?= base_url() ?>crm/kpi-logs" class="btn mb-2 btn-outline-secondary px-2 mt-0 ms-1"><i class="bi bi-journal-check fs-16"></i> KPI Logs</a> -->
            </div>
            <?php
            $query_params_card = $_GET;
            $query_params_card['view'] = 'card';
            $card_query = http_build_query($query_params_card);
            $card_url = base_url() . 'crm' . ($card_query ? '?' . $card_query : '');

            $query_params_table = $_GET;
            $query_params_table['view'] = 'table';
            $table_query = http_build_query($query_params_table);
            $table_url = base_url() . 'crm' . ($table_query ? '?' . $table_query : '');
            ?>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div class="me-auto" id="crmFoundCountLabel"><?= $notif ?></div>
                <div class="d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownView" data-bs-toggle="dropdown" aria-expanded="false">
                            Pilih Tampilan
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownView">
                            <li>
                                <a class="dropdown-item <?= $current_view_mode === 'card' ? 'active' : '' ?>" href="<?= $card_url ?>">Tampilan Kartu</a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= $current_view_mode === 'table' ? 'active' : '' ?>" href="<?= $table_url ?>">Tampilan Tabel</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>

        <div class="col-lg-12 mb-3">
            <div class="checkbox-wrapper-13">
                <input id="c1-13" type="checkbox" value="1" class="checkAll">
                <label for="c1-13">Pilih Semua Data</label>
            </div>
        </div>

        <div class="col-lg-12 position-relative">
            <div id="item-container">
                <div id="tbody-loading" class="mt-3">
                    <?php $this->load->view('loading', true) ?>
                </div>
            </div>
            <div id="crmTableLoadingOverlay" class="crm-table-loading-overlay">
                <div class="crm-table-loading-box">
                    <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
                    <span>Memuat data CRM...</span>
                </div>
            </div>
        </div>

    <div class="mt-3">
        <?= $pagination ?>
    </div>

</div>



<div class="floating-div">
    <button class="btn mb-2 btn-edit-active dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-gear fs-16"></i> Aksi
    </button>
    <ul class="dropdown-menu text-end" style="padding:0px;background:unset;border:unset">


        <!-- <li><a class="dropdown-items" href="#!" style="padding:0px;">
                            <button type="button" class="btn mb-2 btn-edit-active" onclick="refresh_data()">
                            <i class="bi bi-bootstrap-reboot fs-16"></i> Refresh Data
                            </button>
                            </a></li> -->

        <li><a class="dropdown-items" href="#!" style="padding:0px;">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="tampilkan_data()">
                    <i class="bi bi-eye fs-16"></i> Tampilkan Data
                </button>
            </a></li>

        <li><a class="dropdown-items" href="#!" style="padding:0px">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="hapus_data()">
                    <i class="bi bi-trash fs-16"></i> Hapus Data
                </button>
            </a></li>

    </ul>

</div>


<div class="modal fade" id="crmFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-600">Pengaturan Filter CRM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <h6 class="fw-600 mb-2">Channel</h6>
                    <div class="row g-2">
                        <?php
                        $channelOptions = array(
                            array('value' => 'shopee', 'label' => 'Shopee', 'icon' => base_url() . 'assets/img/icon/icon-shopee.png'),
                            array('value' => 'tiktok', 'label' => 'TikTok', 'icon' => base_url() . 'assets/img/icon/icon-tiktok.png'),
                            array('value' => 'lazada', 'label' => 'Lazada', 'icon' => base_url() . 'assets/img/icon/icon-lazada.png'),
                            array('value' => 'wa', 'label' => 'WA', 'icon' => base_url() . 'assets/img/icon/icon-wa.png'),
                        );
                        foreach ($channelOptions as $channelOpt) {
                            $isChecked = in_array($channelOpt['value'], $selected_channels, true) ? 'checked' : '';
                        ?>
                            <div class="col-md-3 col-6">
                                <label class="crm-filter-check">
                                    <input type="checkbox" class="crm-filter-input" name="channel[]" value="<?= $channelOpt['value'] ?>" <?= $isChecked ?>>
                                    <img src="<?= $channelOpt['icon'] ?>" alt="<?= $channelOpt['label'] ?>" class="crm-marketplace-icon">
                                    <span><?= $channelOpt['label'] ?></span>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-600 mb-2">Brand</h6>
                    <div class="row g-2">
                        <?php
                        $brandOptions = array('MG', 'POME', 'ALL');
                        foreach ($brandOptions as $brandOption) {
                            $isChecked = in_array($brandOption, $selected_brand_multi, true) ? 'checked' : '';
                        ?>
                            <div class="col-md-3 col-6">
                                <label class="crm-filter-check">
                                    <input type="checkbox" class="crm-filter-input" name="brand_filter[]" value="<?= $brandOption ?>" <?= $isChecked ?>>
                                    <span><?= $brandOption ?></span>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="mb-2">
                    <h6 class="fw-600 mb-2">Customer Segment</h6>
                    <div class="row g-2">
                        <?php foreach (($segment_options ?? array()) as $segmentOpt) {
                            $isChecked = in_array($segmentOpt, $selected_segment_multi, true) ? 'checked' : '';
                        ?>
                            <div class="col-md-4 col-6">
                                <label class="crm-filter-check">
                                    <input type="checkbox" class="crm-filter-input" name="segment[]" value="<?= htmlspecialchars($segmentOpt, ENT_QUOTES, 'UTF-8') ?>" <?= $isChecked ?>>
                                    <span><?= htmlspecialchars($segmentOpt, ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-600 mb-2">Kontak</h6>
                    <div class="row g-2">
                        <div class="col-md-4 col-6">
                            <label class="crm-filter-check">
                                <input type="checkbox" class="crm-filter-input" id="crmPhoneValidFilter" value="1" <?= !empty($phone_valid) ? 'checked' : '' ?>>
                                <span>No HP valid</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-600 mb-2">Tanggal Order</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Dari</label>
                            <input type="date" class="form-control" id="crmOrderStartDate" value="<?= htmlspecialchars($order_start_date, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Sampai</label>
                            <input type="date" class="form-control" id="crmOrderUntilDate" value="<?= htmlspecialchars($order_until_date, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-600 mb-2">Repeat Order</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Dari</label>
                            <input type="date" class="form-control" id="crmRepeatStartDate" value="<?= htmlspecialchars($repeat_start_date, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Sampai</label>
                            <input type="date" class="form-control" id="crmRepeatUntilDate" value="<?= htmlspecialchars($repeat_until_date, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-2">
                    <h6 class="fw-600 mb-2">Cari Produk (All Produk)</h6>
                    <select class="form-select" id="crmProductFilter" multiple size="8">
                        <?php foreach (($product ?? array()) as $productOpt) {
                            $productVal = trim((string)($productOpt['product_text'] ?? ''));
                            if ($productVal === '') {
                                $productVal = trim((string)($productOpt['name'] ?? ''));
                            }
                            if ($productVal === '') {
                                $productVal = trim((string)($productOpt['sku'] ?? ''));
                            }
                            if ($productVal === '') {
                                continue;
                            }
                            $isSelected = in_array($productVal, $selected_product_filter, true) ? 'selected' : '';
                            $productLabel = $productVal;
                            $productSku = trim((string)($productOpt['sku'] ?? ''));
                            if ($productSku !== '' && stripos($productLabel, $productSku) === false) {
                                $productLabel .= ' (' . $productSku . ')';
                            }
                        ?>
                            <option value="<?= htmlspecialchars($productVal, ENT_QUOTES, 'UTF-8') ?>" <?= $isSelected ?>>
                                <?= htmlspecialchars($productLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="crmFilterResetBtn">Reset</button>
                <button type="button" class="btn btn-primary" id="crmFilterApplyBtn">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="crmCampaignManagerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-600">Kelola Campaign / Broadcast CRM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="crmCampaignForm" enctype="multipart/form-data">
                    <input type="hidden" id="crmCampaignId" name="id" value="">
                    <input type="hidden" id="crmCampaignRemoveAttachment" name="remove_attachment" value="0">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-8">
                            <label class="form-label mb-1">Nama Campaign / Broadcast</label>
                            <input type="text" class="form-control" id="crmCampaignNewName" name="name" placeholder="Contoh: Broadcast Promo Maret">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">Delay</label>
                            <input type="text" class="form-control" id="crmCampaignDelay" name="delay_value" value="2" placeholder="2 / 5-10">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">Kode Negara</label>
                            <input type="text" class="form-control" id="crmCampaignCountryCode" name="country_code" value="62">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label mb-1">Jenis Broadcast</label>
                        <div class="d-flex flex-wrap gap-3">
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="broadcast_type" value="text" checked>
                                <span class="form-check-label">Teks / Wording</span>
                            </label>
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="broadcast_type" value="polling">
                                <span class="form-check-label">Polling WhatsApp</span>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label mb-1" id="crmCampaignMessageLabel">Wording Broadcast</label>
                        <textarea class="form-control" id="crmCampaignMessageHtml" name="message_html" rows="7"></textarea>
                    </div>
                    <div class="border rounded p-2 mb-3 d-none" id="crmCampaignPollingPanel">
                        <div class="row g-2">
                            <div class="col-md-8">
                                <label class="form-label small mb-1">Judul Polling</label>
                                <input type="text" class="form-control" id="crmCampaignPollName" name="poll_name" placeholder="Contoh: Pilihan hadiah">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Mode Pilihan</label>
                                <select class="form-control" id="crmCampaignPollSelect" name="poll_select">
                                    <option value="single">Satu pilihan</option>
                                    <option value="multiple">Banyak pilihan</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Opsi Polling</label>
                                <textarea class="form-control" id="crmCampaignPollChoices" name="poll_choices" rows="4" placeholder="Tulis satu opsi per baris, minimal 2 dan maksimal 12."></textarea>
                                <div class="small text-muted mt-1">Contoh: Paket A, Paket B, Paket C.</div>
                            </div>
                        </div>
                    </div>
                    <div class="border rounded p-2 mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="1" id="crmCampaignIncludeSheets" name="include_sheets">
                            <label class="form-check-label" for="crmCampaignIncludeSheets">Tampilkan data respons dari Google Sheets</label>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-8">
                                <label class="form-label small mb-1">Link / ID Spreadsheet</label>
                                <input type="text" class="form-control" id="crmCampaignSheetsSpreadsheetId" name="sheets_spreadsheet_id" placeholder="https://docs.google.com/spreadsheets/d/...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Nama Sheet</label>
                                <input type="text" class="form-control" id="crmCampaignSheetsSheetName" name="sheets_sheet_name" value="CRM Broadcast">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="crmCampaignAttachmentRow">
                        <label class="form-label mb-1">Lampiran</label>
                        <input type="file" class="form-control" id="crmCampaignAttachment" name="attachment">
                        <div class="small text-muted mt-1" id="crmCampaignAttachmentInfo">Maksimal 4MB.</div>
                        <button type="button" class="btn btn-sm btn-outline-danger mt-2 d-none" id="crmCampaignRemoveAttachmentBtn">Hapus lampiran</button>
                    </div>
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-outline-secondary w-50" id="crmCampaignResetBtn">Baru</button>
                        <button type="button" class="btn btn-primary w-50" id="crmCampaignCreateBtn">Simpan</button>
                    </div>
                    <div class="crm-campaign-preview-box mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-600">Preview</span>
                            <span class="small text-muted" id="crmCampaignPreviewMeta">Delay 2 detik, CC 62</span>
                        </div>
                        <div class="fw-600 mb-1" id="crmCampaignPreviewName">Nama campaign</div>
                        <div class="crm-campaign-preview-message mb-2" id="crmCampaignPreviewMessage">Wording broadcast akan tampil di sini.</div>
                        <div class="small text-muted" id="crmCampaignPreviewAttachment">Lampiran: -</div>
                        <div class="small text-muted" id="crmCampaignPreviewSheets">Data respons Sheets: Tidak aktif</div>
                    </div>
                </form>
                <div>
                    <label class="form-label mb-1">Daftar Campaign</label>
                    <div id="crmCampaignList" class="small text-muted">Memuat data...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="crmBroadcastSendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-600">Kirim Broadcast CRM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <span id="crmBroadcastModalCount">0</span> customer dipilih.
                </div>
                <div class="mb-3">
                    <label class="form-label mb-1">Pilih Campaign</label>
                    <select class="form-control" id="crmBroadcastCampaignSelect">
                        <option value="">-- Pilih Campaign --</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label mb-1">Waktu Kirim</label>
                    <div class="crm-broadcast-time-options">
                        <label class="crm-broadcast-time-option">
                            <input type="radio" name="crm_broadcast_send_mode" value="now" checked>
                            <span>Kirim sekarang</span>
                        </label>
                        <label class="crm-broadcast-time-option">
                            <input type="radio" name="crm_broadcast_send_mode" value="scheduled">
                            <span>Nanti</span>
                            <input type="text" class="form-control form-control-sm" id="crmBroadcastSendAt" placeholder="Pilih waktu" autocomplete="off" disabled>
                        </label>
                    </div>
                    <div class="small text-muted mt-1" id="crmBroadcastScheduleHint">Jika pilih nanti, pesan akan masuk jadwal Fonnte pada waktu tersebut.</div>
                </div>
                <div class="small text-muted" id="crmBroadcastPreview">Pilih campaign untuk melihat ringkasan wording dan lampiran.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="crmBroadcastSendBtn">Kirim Broadcast</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade bd-example-modal-sm" tabindex="-1" varietas="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-form">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="title-form"></h5>
                <a class="close a-link" data-bs-dismiss="modal"><i class="bi bi-x-circle fs-24"></i></a>
            </div>
            <div class="modal-body">
                <div id="load-form"></div>
            </div>
        </div>
    </div>
</div>


<input type="hidden" id="id_selected" name="id_selected" form="form-action">

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    var list_id_v2 = '';
    var crmCampaignCache = [];
    var crmCampaignCurrentAttachment = null;
    var crmCampaignUploadBaseUrl = '<?= base_url() ?>assets/uploads/crm_campaign/';

    function get_id() {
        list_id_v2 = '';
        var selectedValues = [];

        if (window.crmGridOptions && window.crmGridOptions.api) {
            var rows = window.crmGridOptions.api.getSelectedRows() || [];
            selectedValues = rows.map(function(row) {
                return String(row.id);
            });
        } else {
            $('input[name="list_id"]').each(function() {
                if ($(this).is(":checked")) {
                    selectedValues.push($(this).val());
                }
            });
        }

        list_id_v2 = selectedValues.join(',');
        $('#id_selected').val(list_id_v2);
        updateCrmBroadcastSelectionCount();
    }

    function updateCrmBroadcastSelectionCount() {
        var count = list_id_v2 ? list_id_v2.split(',').filter(Boolean).length : 0;
        $('#crmBroadcastSelectedCount').text(count);
        $('#crmBroadcastModalCount').text(count);
    }

    $(document).off('change.crmList', 'input[name="list_id"]').on('change.crmList', 'input[name="list_id"]', function() {
        get_id();
    });

    $(document).off('change.crmList', '.checkAll').on('change.crmList', '.checkAll', function() {
        if (window.crmGridOptions && window.crmGridOptions.api) {
            if (this.checked) {
                window.crmGridOptions.api.selectAll();
            } else {
                window.crmGridOptions.api.deselectAll();
            }
        } else {
            $('input[name="list_id"]').prop('checked', this.checked).trigger('change');
        }
    });

    function tampilkan_data(id) {
        window.location.href = "<?= base_url() ?>/crm?&brand=<?= $_GET['brand'] ?>&ids=" + list_id_v2;
    }

    function hapus_data(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>crm/action?code=hapus_data&id=" + id);
    }
    // function refresh_data(id) {
    //     $("#load-form").html('Loading...');
    //     $("#modal-form").modal('show');
    //     $("#title-form").html('Refresh Data');
    //     $("#load-form").load("<?= base_url() ?>crm/action?code=refresh_data&id=" + id);
    // }
    function create() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Create Data');
        $("#load-form").load("<?= base_url() ?>crm/create");
    }

    function edit(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Edit Data');
        $("#load-form").load("<?= base_url() ?>crm/edit?id=" + id);
    }

    function refresh(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh Data');
        $("#load-form").load("<?= base_url() ?>crm/update-customer-order?id=" + id);
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>crm/remove?id=" + id);
    }

    function fu(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('FU H+10 Perkembangan');
        $("#load-form").load("<?= base_url() ?>crm/fu?id=" + id);
    }

    function fu_2(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('FU H-7');
        $("#load-form").load("<?= base_url() ?>crm/fu_2?id=" + id);
    }

    function removeUrlArrayParams(params, key) {
        params.delete(key);
        params.delete(key + '[]');
    }

    function appendUrlArrayParams(params, key, values) {
        values.forEach(function(value) {
            params.append(key + '[]', value);
        });
    }

    function applyCrmModalFilter() {
        var params = new URLSearchParams(window.location.search);
        var groups = ['channel', 'brand_filter', 'segment'];
        var scalarKeys = ['order_start_date', 'order_until_date', 'repeat_start_date', 'repeat_until_date'];
        params.delete('marketplace');
        params.delete('brand');
        params.delete('cb_cl');
        params.delete('phone_valid');
        removeUrlArrayParams(params, 'product_filter');
        scalarKeys.forEach(function(key) {
            params.delete(key);
        });
        groups.forEach(function(groupKey) {
            removeUrlArrayParams(params, groupKey);
            var values = [];
            $('#crmFilterModal input[name="' + groupKey + '[]"]:checked').each(function() {
                values.push($(this).val());
            });
            appendUrlArrayParams(params, groupKey, values);
        });

        var selectedProducts = $('#crmProductFilter').val() || [];
        appendUrlArrayParams(params, 'product_filter', selectedProducts);

        var orderStartDate = ($('#crmOrderStartDate').val() || '').trim();
        var orderUntilDate = ($('#crmOrderUntilDate').val() || '').trim();
        var repeatStartDate = ($('#crmRepeatStartDate').val() || '').trim();
        var repeatUntilDate = ($('#crmRepeatUntilDate').val() || '').trim();

        if (orderStartDate !== '') params.set('order_start_date', orderStartDate);
        if (orderUntilDate !== '') params.set('order_until_date', orderUntilDate);
        if (repeatStartDate !== '') params.set('repeat_start_date', repeatStartDate);
        if (repeatUntilDate !== '') params.set('repeat_until_date', repeatUntilDate);
        if ($('#crmPhoneValidFilter').is(':checked')) params.set('phone_valid', '1');

        params.set('view', 'table');
        params.set('page', '1');
        window.location.href = window.location.pathname + '?' + params.toString();
    }

    function resetCrmModalFilter() {
        window.location.href = "<?= base_url() ?>crm";
    }

    $('#crmFilterApplyBtn').on('click', function() {
        applyCrmModalFilter();
    });

    $('#crmFilterResetBtn').on('click', function() {
        resetCrmModalFilter();
    });

    function crmEscapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function crmShowGoogleLoginRequired(message, authUrl) {
        var msg = message || 'Google belum terhubung. Silakan login Google terlebih dahulu.';
        if (authUrl && typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'Login Google Dibutuhkan',
                text: msg,
                confirmButtonText: 'Login Google',
                showCancelButton: true,
                cancelButtonText: 'Nanti Saja'
            }).then(function(result) {
                if (result.isConfirmed) {
                    window.open(authUrl, '_blank', 'noopener');
                }
            });
            return;
        }

        alert(msg);
        if (authUrl && confirm('Buka halaman login Google sekarang?')) {
            window.open(authUrl, '_blank', 'noopener');
        }
    }

    function getCrmCampaignEditorHtml() {
        if (typeof tinymce !== 'undefined' && tinymce.get('crmCampaignMessageHtml')) {
            return tinymce.get('crmCampaignMessageHtml').getContent();
        }
        return $('#crmCampaignMessageHtml').val() || '';
    }

    function getCrmCampaignEditorText() {
        if (typeof tinymce !== 'undefined' && tinymce.get('crmCampaignMessageHtml')) {
            return tinymce.get('crmCampaignMessageHtml').getContent({ format: 'text' });
        }
        return $('<div>').html($('#crmCampaignMessageHtml').val() || '').text();
    }

    function crmCampaignAttachmentUrl(fileName) {
        return crmCampaignUploadBaseUrl + encodeURIComponent(fileName || '');
    }

    function getCrmCampaignBroadcastType() {
        return $('input[name="broadcast_type"]:checked').val() === 'polling' ? 'polling' : 'text';
    }

    function normalizeCrmPollChoices(value) {
        return String(value || '')
            .split(/\r?\n|,/)
            .map(function(item) {
                return item.trim();
            })
            .filter(Boolean);
    }

    function toggleCrmCampaignTypeFields() {
        var isPolling = getCrmCampaignBroadcastType() === 'polling';
        $('#crmCampaignPollingPanel').toggleClass('d-none', !isPolling);
        $('#crmCampaignAttachmentRow').toggleClass('d-none', isPolling);
        $('#crmCampaignMessageLabel').text(isPolling ? 'Pertanyaan / Pengantar Polling' : 'Wording Broadcast');
        if (isPolling) {
            $('#crmCampaignRemoveAttachment').val('1');
        }
        renderCrmCampaignFormPreview();
    }

    function renderCrmCampaignAttachmentInfo() {
        var removeAttachment = $('#crmCampaignRemoveAttachment').val() === '1';
        var selectedFile = ($('#crmCampaignAttachment')[0].files || [])[0] || null;

        if (selectedFile) {
            $('#crmCampaignAttachmentInfo').html('Lampiran baru: <strong>' + crmEscapeHtml(selectedFile.name) + '</strong>.');
            $('#crmCampaignRemoveAttachmentBtn').toggleClass('d-none', !crmCampaignCurrentAttachment);
            return;
        }

        if (removeAttachment) {
            $('#crmCampaignAttachmentInfo').html('<span class="text-danger">Lampiran akan dihapus saat campaign disimpan.</span>');
            $('#crmCampaignRemoveAttachmentBtn').addClass('d-none');
            return;
        }

        if (crmCampaignCurrentAttachment && crmCampaignCurrentAttachment.file) {
            var link = '<a href="' + crmEscapeHtml(crmCampaignAttachmentUrl(crmCampaignCurrentAttachment.file)) + '" target="_blank">'
                + crmEscapeHtml(crmCampaignCurrentAttachment.name || crmCampaignCurrentAttachment.file)
                + '</a>';
            $('#crmCampaignAttachmentInfo').html('Lampiran saat ini: <strong>' + link + '</strong>. Upload file baru untuk mengganti.');
            $('#crmCampaignRemoveAttachmentBtn').removeClass('d-none');
            return;
        }

        $('#crmCampaignAttachmentInfo').text('Maksimal 4MB.');
        $('#crmCampaignRemoveAttachmentBtn').addClass('d-none');
    }

    function renderCrmCampaignFormPreview() {
        var type = getCrmCampaignBroadcastType();
        var name = ($('#crmCampaignNewName').val() || '').trim();
        var delay = ($('#crmCampaignDelay').val() || '2').trim();
        var countryCode = ($('#crmCampaignCountryCode').val() || '62').trim();
        var message = (getCrmCampaignEditorText() || '').trim();
        var selectedFile = ($('#crmCampaignAttachment')[0].files || [])[0] || null;
        var removeAttachment = $('#crmCampaignRemoveAttachment').val() === '1';
        var includeSheets = $('#crmCampaignIncludeSheets').is(':checked');
        var sheetName = ($('#crmCampaignSheetsSheetName').val() || 'CRM Broadcast').trim();
        var spreadsheetId = ($('#crmCampaignSheetsSpreadsheetId').val() || '').trim();
        var pollChoices = normalizeCrmPollChoices($('#crmCampaignPollChoices').val());
        var pollName = ($('#crmCampaignPollName').val() || name || 'Polling').trim();
        var pollSelect = $('#crmCampaignPollSelect').val() === 'multiple' ? 'Banyak pilihan' : 'Satu pilihan';
        var attachmentHtml = '-';

        if (type === 'polling') {
            attachmentHtml = 'Tidak digunakan untuk polling';
        } else if (selectedFile) {
            attachmentHtml = crmEscapeHtml(selectedFile.name);
        } else if (!removeAttachment && crmCampaignCurrentAttachment && crmCampaignCurrentAttachment.file) {
            attachmentHtml = '<a href="' + crmEscapeHtml(crmCampaignAttachmentUrl(crmCampaignCurrentAttachment.file)) + '" target="_blank">'
                + crmEscapeHtml(crmCampaignCurrentAttachment.name || crmCampaignCurrentAttachment.file)
                + '</a>';
        }

        $('#crmCampaignPreviewName').text(name || 'Nama campaign');
        if (type === 'polling') {
            $('#crmCampaignPreviewMessage').text(
                (message || 'Pertanyaan polling akan tampil di sini.')
                + '\n\nPolling: ' + pollName
                + '\nMode: ' + pollSelect
                + '\nOpsi: ' + (pollChoices.length ? pollChoices.join(', ') : '-')
            );
        } else {
            $('#crmCampaignPreviewMessage').text(message || 'Wording broadcast akan tampil di sini.');
        }
        $('#crmCampaignPreviewAttachment').html('Lampiran: ' + attachmentHtml);
        $('#crmCampaignPreviewMeta').text((type === 'polling' ? 'Polling WhatsApp' : 'Teks / Wording') + ' - Delay ' + (delay || '2') + ' detik, CC ' + (countryCode || '62'));
        $('#crmCampaignPreviewSheets').text(includeSheets ? ('Data respons Sheets: Aktif - ' + (sheetName || 'CRM Broadcast') + (spreadsheetId ? '' : ' (link belum diisi)')) : 'Data respons Sheets: Tidak aktif');
        renderCrmCampaignAttachmentInfo();
    }

    function renderCrmCampaignList(list) {
        if (!Array.isArray(list) || list.length === 0) {
            $('#crmCampaignList').html('<div class="text-muted">Belum ada campaign.</div>');
            return;
        }
        var html = list.map(function(row) {
            var id = crmEscapeHtml(row.id);
            var name = crmEscapeHtml(row.name || '');
            var message = crmEscapeHtml(row.message_text || '');
            var attachment = row.attachment_original_name ? crmEscapeHtml(row.attachment_original_name) : '';
            var includeSheets = String(row.include_sheets || '0') === '1';
            var isPolling = String(row.broadcast_type || 'text') === 'polling';
            var detailUrl = '<?= base_url() ?>crm/campaign-detail?id=' + encodeURIComponent(row.id || '');
            return '<div class="d-flex justify-content-between align-items-center border-bottom py-2">'
                + '<div style="min-width:0;">'
                + '<div class="fw-600">' + name + (isPolling ? ' <span class="badge bg-info ms-1">Polling</span>' : ' <span class="badge bg-secondary ms-1">Teks</span>') + '</div>'
                + '<div class="text-muted text-truncate" style="max-width:520px;">' + (message || '-') + '</div>'
                + (isPolling ? '<div class="text-muted"><i class="bi bi-bar-chart"></i> Opsi: ' + crmEscapeHtml(row.poll_choices || '-') + '</div>' : '')
                + (attachment ? '<div class="text-muted"><i class="bi bi-paperclip"></i> ' + attachment + '</div>' : '')
                + (includeSheets ? '<div class="text-muted"><i class="bi bi-table"></i> Data respons Sheets: ' + crmEscapeHtml(row.sheets_sheet_name || 'CRM Broadcast') + '</div>' : '')
                + '</div>'
                + '<div class="d-flex gap-1">'
                + (includeSheets ? '<a class="btn btn-sm btn-outline-info" href="' + crmEscapeHtml(detailUrl) + '">Detail</a>' : '')
                + '<button type="button" class="btn btn-sm btn-outline-primary crm-campaign-edit-btn" data-id="' + id + '">Edit</button>'
                + '<button type="button" class="btn btn-sm btn-outline-danger crm-campaign-delete-btn" data-id="' + id + '">Hapus</button>'
                + '</div>'
                + '</div>';
        }).join('');
        $('#crmCampaignList').html(html);
    }

    function renderCrmCampaignTargetOptions(list) {
        var html = ['<option value="">-- Pilih Campaign --</option>'];
        (list || []).forEach(function(row) {
            html.push('<option value="' + crmEscapeHtml(row.id) + '">' + crmEscapeHtml(row.name || '') + '</option>');
        });
        $('#crmBroadcastCampaignSelect').html(html.join(''));
    }

    function resetCrmCampaignForm() {
        $('#crmCampaignId').val('');
        $('#crmCampaignNewName').val('');
        $('#crmCampaignDelay').val('2');
        $('#crmCampaignCountryCode').val('62');
        $('input[name="broadcast_type"][value="text"]').prop('checked', true);
        $('#crmCampaignPollName').val('');
        $('#crmCampaignPollChoices').val('');
        $('#crmCampaignPollSelect').val('single');
        $('#crmCampaignIncludeSheets').prop('checked', false);
        $('#crmCampaignSheetsSpreadsheetId').val('');
        $('#crmCampaignSheetsSheetName').val('CRM Broadcast');
        $('#crmCampaignAttachment').val('');
        $('#crmCampaignRemoveAttachment').val('0');
        crmCampaignCurrentAttachment = null;
        $('#crmCampaignAttachmentInfo').text('Maksimal 4MB.');
        $('#crmCampaignRemoveAttachmentBtn').addClass('d-none');
        if (typeof tinymce !== 'undefined' && tinymce.get('crmCampaignMessageHtml')) {
            tinymce.get('crmCampaignMessageHtml').setContent('');
        } else {
            $('#crmCampaignMessageHtml').val('');
        }
        toggleCrmCampaignTypeFields();
        renderCrmCampaignFormPreview();
    }

    function initCrmCampaignEditor() {
        if (typeof tinymce === 'undefined') {
            return;
        }
        if (tinymce.get('crmCampaignMessageHtml')) {
            return;
        }
        tinymce.init({
            selector: '#crmCampaignMessageHtml',
            height: 260,
            menubar: false,
            branding: false,
            plugins: 'lists link emoticons',
            toolbar: 'undo redo | bold italic underline strikethrough | bullist numlist | link emoticons | removeformat',
            forced_root_block: 'p',
            convert_urls: false,
            entity_encoding: 'raw',
            valid_elements: 'p,br,strong/b,em/i,u,s,strike,ul,ol,li,a[href|target],span[style],div',
            setup: function(editor) {
                editor.on('keyup change input SetContent', function() {
                    renderCrmCampaignFormPreview();
                });
            }
        });
    }

    function fillCrmCampaignForm(row) {
        $('#crmCampaignId').val(row.id || '');
        $('#crmCampaignNewName').val(row.name || '');
        $('#crmCampaignDelay').val(row.delay_value || '2');
        $('#crmCampaignCountryCode').val(row.country_code || '62');
        $('input[name="broadcast_type"][value="' + (row.broadcast_type === 'polling' ? 'polling' : 'text') + '"]').prop('checked', true);
        $('#crmCampaignPollName').val(row.poll_name || '');
        $('#crmCampaignPollChoices').val(row.poll_choices || '');
        $('#crmCampaignPollSelect').val(row.poll_select === 'multiple' ? 'multiple' : 'single');
        $('#crmCampaignIncludeSheets').prop('checked', String(row.include_sheets || '0') === '1');
        $('#crmCampaignSheetsSpreadsheetId').val(row.sheets_spreadsheet_id || '');
        $('#crmCampaignSheetsSheetName').val(row.sheets_sheet_name || 'CRM Broadcast');
        $('#crmCampaignAttachment').val('');
        $('#crmCampaignRemoveAttachment').val('0');
        crmCampaignCurrentAttachment = row.attachment_file ? {
            file: row.attachment_file,
            name: row.attachment_original_name || row.attachment_file
        } : null;
        var html = row.message_html || '';
        if (typeof tinymce !== 'undefined' && tinymce.get('crmCampaignMessageHtml')) {
            tinymce.get('crmCampaignMessageHtml').setContent(html);
        } else {
            $('#crmCampaignMessageHtml').val(html);
        }
        toggleCrmCampaignTypeFields();
        renderCrmCampaignFormPreview();
    }

    function getCrmCampaignById(id) {
        id = String(id || '');
        return (crmCampaignCache || []).find(function(row) {
            return String(row.id) === id;
        }) || null;
    }

    function renderCrmBroadcastPreview() {
        var campaign = getCrmCampaignById($('#crmBroadcastCampaignSelect').val());
        if (!campaign) {
            $('#crmBroadcastPreview').html('Pilih campaign untuk melihat ringkasan wording dan lampiran.');
            return;
        }
        var isPolling = String(campaign.broadcast_type || 'text') === 'polling';
        var message = crmEscapeHtml(campaign.message_text || '-');
        var attachment = isPolling ? 'Tidak digunakan untuk polling' : (campaign.attachment_original_name ? crmEscapeHtml(campaign.attachment_original_name) : '-');
        $('#crmBroadcastPreview').html(
            '<div class="border rounded p-2">'
            + '<div class="fw-600 mb-1">' + crmEscapeHtml(campaign.name || '-') + (isPolling ? ' <span class="badge bg-info ms-1">Polling</span>' : ' <span class="badge bg-secondary ms-1">Teks</span>') + '</div>'
            + '<div class="mb-2" style="white-space:pre-wrap;max-height:140px;overflow:auto;">' + message + '</div>'
            + (isPolling ? '<div class="small text-muted">Polling: ' + crmEscapeHtml(campaign.poll_name || campaign.name || 'Polling') + '</div>' : '')
            + (isPolling ? '<div class="small text-muted">Opsi: ' + crmEscapeHtml(campaign.poll_choices || '-') + '</div>' : '')
            + (isPolling ? '<div class="small text-muted">Mode: ' + (campaign.poll_select === 'multiple' ? 'Banyak pilihan' : 'Satu pilihan') + '</div>' : '')
            + '<div class="small text-muted">Lampiran: ' + attachment + '</div>'
            + '<div class="small text-muted">Delay: ' + crmEscapeHtml(campaign.delay_value || '2') + ' detik, Country Code: ' + crmEscapeHtml(campaign.country_code || '62') + '</div>'
            + '</div>'
        );
    }

    function initCrmBroadcastSchedulePicker() {
        var input = document.getElementById('crmBroadcastSendAt');
        if (!input || input.dataset.ready === '1') {
            return;
        }
        input.dataset.ready = '1';
        if (window.flatpickr) {
            flatpickr(input, {
                enableTime: true,
                dateFormat: 'Y-m-d H:i',
                time_24hr: true,
                minDate: 'today',
                minuteIncrement: 5
            });
        }
    }

    function resetCrmBroadcastSchedule() {
        $('input[name="crm_broadcast_send_mode"][value="now"]').prop('checked', true);
        $('#crmBroadcastSendAt').prop('disabled', true).val('');
        $('#crmBroadcastScheduleHint').text('Jika pilih nanti, pesan akan masuk jadwal Fonnte pada waktu tersebut.');
    }

    function loadCrmCampaignList() {
        $.ajax({
            type: 'GET',
            url: '<?= base_url() ?>crm/crm_campaign_options',
            dataType: 'json',
            success: function(res) {
                if (!res || !res.status) {
                    var msg = (res && res.msg) ? res.msg : 'Gagal memuat campaign.';
                    $('#crmCampaignList').html('<div class="text-danger">' + crmEscapeHtml(msg) + '</div>');
                    return;
                }
                var data = Array.isArray(res.data) ? res.data : [];
                crmCampaignCache = data;
                renderCrmCampaignList(data);
                renderCrmCampaignTargetOptions(data);
                renderCrmBroadcastPreview();
            },
            error: function() {
                $('#crmCampaignList').html('<div class="text-danger">Gagal memuat campaign.</div>');
            }
        });
    }

    $('#crmCampaignManagerModal').on('shown.bs.modal', function() {
        initCrmCampaignEditor();
        loadCrmCampaignList();
        renderCrmCampaignFormPreview();
        $('#crmCampaignNewName').trigger('focus');
    });

    $('#crmCampaignResetBtn').on('click', function() {
        resetCrmCampaignForm();
    });

    $('#crmCampaignNewName, #crmCampaignDelay, #crmCampaignCountryCode, #crmCampaignMessageHtml, #crmCampaignIncludeSheets, #crmCampaignSheetsSpreadsheetId, #crmCampaignSheetsSheetName, #crmCampaignPollName, #crmCampaignPollChoices, #crmCampaignPollSelect').on('input change keyup', function() {
        renderCrmCampaignFormPreview();
    });

    $(document).on('change', 'input[name="broadcast_type"]', function() {
        toggleCrmCampaignTypeFields();
    });

    $('#crmCampaignAttachment').on('change', function() {
        $('#crmCampaignRemoveAttachment').val('0');
        renderCrmCampaignFormPreview();
    });

    $('#crmCampaignRemoveAttachmentBtn').on('click', function() {
        $('#crmCampaignAttachment').val('');
        $('#crmCampaignRemoveAttachment').val('1');
        renderCrmCampaignFormPreview();
    });

    $('#crmCampaignCreateBtn').on('click', function() {
        var name = ($('#crmCampaignNewName').val() || '').trim();
        if (name === '') {
            alert('Nama campaign wajib diisi.');
            return;
        }
        if (name.indexOf(',') !== -1) {
            alert('Nama campaign tidak boleh mengandung tanda koma (,).');
            return;
        }
        if ($('#crmCampaignIncludeSheets').is(':checked') && (($('#crmCampaignSheetsSpreadsheetId').val() || '').trim() === '')) {
            alert('Link / ID Google Sheets wajib diisi.');
            return;
        }
        if (getCrmCampaignBroadcastType() === 'polling') {
            var pollChoices = normalizeCrmPollChoices($('#crmCampaignPollChoices').val());
            if (pollChoices.length < 2 || pollChoices.length > 12) {
                alert('Opsi polling minimal 2 dan maksimal 12.');
                return;
            }
            if ((getCrmCampaignEditorText() || '').trim() === '') {
                alert('Pertanyaan / pengantar polling wajib diisi.');
                return;
            }
        }
        if (typeof tinymce !== 'undefined' && tinymce.get('crmCampaignMessageHtml')) {
            tinymce.triggerSave();
        }
        var formEl = document.getElementById('crmCampaignForm');
        var formData = new FormData(formEl);
        var messageHtml = getCrmCampaignEditorHtml();
        var messageText = getCrmCampaignEditorText();
        formData.set('message_html', messageHtml);
        formData.set('message_text', messageText);
        $.ajax({
            type: 'POST',
            url: '<?= base_url() ?>crm/crm_campaign_create',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (!res || !res.status) {
                    alert((res && res.msg) ? res.msg : 'Gagal menambah campaign.');
                    return;
                }
                resetCrmCampaignForm();
                loadCrmCampaignList();
            },
            error: function() {
                alert('Gagal menambah campaign.');
            }
        });
    });

    $(document).on('click', '.crm-campaign-edit-btn', function() {
        var campaign = getCrmCampaignById($(this).data('id'));
        if (!campaign) return;
        initCrmCampaignEditor();
        setTimeout(function() {
            fillCrmCampaignForm(campaign);
        }, 50);
    });

    $(document).on('click', '.crm-campaign-delete-btn', function() {
        var id = $(this).data('id');
        if (!id) return;
        if (!confirm('Hapus campaign ini?')) return;
        $.ajax({
            type: 'POST',
            url: '<?= base_url() ?>crm/crm_campaign_delete',
            dataType: 'json',
            data: { id: id },
            success: function(res) {
                if (!res || !res.status) {
                    alert((res && res.msg) ? res.msg : 'Gagal menghapus campaign.');
                    return;
                }
                loadCrmCampaignList();
            },
            error: function() {
                alert('Gagal menghapus campaign.');
            }
        });
    });

    $('#crmBroadcastOpenBtn').on('click', function() {
        get_id();
        var count = list_id_v2 ? list_id_v2.split(',').filter(Boolean).length : 0;
        if (count <= 0) {
            alert('Pilih customer yang mau dikirim broadcast terlebih dahulu.');
            return;
        }
        updateCrmBroadcastSelectionCount();
        loadCrmCampaignList();
        initCrmBroadcastSchedulePicker();
        resetCrmBroadcastSchedule();
        $('#crmBroadcastSendModal').modal('show');
    });

    $('#crmBroadcastCampaignSelect').on('change', function() {
        renderCrmBroadcastPreview();
    });

    $(document).on('change', 'input[name="crm_broadcast_send_mode"]', function() {
        var scheduled = $('input[name="crm_broadcast_send_mode"]:checked').val() === 'scheduled';
        $('#crmBroadcastSendAt').prop('disabled', !scheduled);
        if (scheduled) {
            initCrmBroadcastSchedulePicker();
            $('#crmBroadcastSendAt').focus();
            $('#crmBroadcastScheduleHint').text('Waktu mengikuti timezone Asia/Jakarta.');
        } else {
            $('#crmBroadcastSendAt').val('');
            $('#crmBroadcastScheduleHint').text('Jika pilih nanti, pesan akan masuk jadwal Fonnte pada waktu tersebut.');
        }
    });

    $('#crmBroadcastSendBtn').on('click', function() {
        get_id();
        var campaignId = $('#crmBroadcastCampaignSelect').val();
        var customerIds = list_id_v2 ? list_id_v2.split(',').filter(Boolean) : [];
        var sendMode = $('input[name="crm_broadcast_send_mode"]:checked').val() || 'now';
        var sendAt = ($('#crmBroadcastSendAt').val() || '').trim();
        if (!campaignId) {
            alert('Pilih campaign terlebih dahulu.');
            return;
        }
        if (customerIds.length === 0) {
            alert('Tidak ada customer terpilih.');
            return;
        }
        if (sendMode === 'scheduled' && !sendAt) {
            alert('Pilih waktu kirim terlebih dahulu.');
            return;
        }
        var confirmText = sendMode === 'scheduled'
            ? 'Jadwalkan broadcast ke ' + customerIds.length + ' customer pada ' + sendAt + '?'
            : 'Kirim broadcast ke ' + customerIds.length + ' customer terpilih?';
        if (!confirm(confirmText)) {
            return;
        }
        var $btn = $('#crmBroadcastSendBtn');
        $btn.prop('disabled', true).text(sendMode === 'scheduled' ? 'Menjadwalkan...' : 'Mengirim...');
        $.ajax({
            type: 'POST',
            url: '<?= base_url() ?>crm/crm_campaign_broadcast_send',
            dataType: 'json',
            data: {
                campaign_id: campaignId,
                customer_ids: customerIds,
                send_mode: sendMode,
                send_at: sendAt
            },
            success: function(res) {
                if (!res || !res.status) {
                    if (res && res.auth_url) {
                        crmShowGoogleLoginRequired(res.msg, res.auth_url);
                    } else {
                        alert((res && res.msg) ? res.msg : 'Broadcast gagal.');
                    }
                    return;
                }
                var msg = res.msg || 'Broadcast berhasil.';
                if (Array.isArray(res.skipped) && res.skipped.length > 0) {
                    msg += '\n\nNomor dilewati: ' + res.skipped.length + ' customer.';
                }
                alert(msg);
                $('#crmBroadcastSendModal').modal('hide');
                if (typeof loadMoreData === 'function') {
                    loadMoreData();
                }
            },
            error: function() {
                alert('Broadcast gagal diproses.');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Kirim Broadcast');
            }
        });
    });

    $('#crmFilterModal').on('shown.bs.modal', function() {
        if ($.fn.select2 && !$('#crmProductFilter').hasClass('select2-hidden-accessible')) {
            $('#crmProductFilter').select2({
                width: '100%',
                placeholder: 'Cari produk...',
                dropdownParent: $('#crmFilterModal')
            });
        }

    });

    function syncCrmDownloadHref() {
        var downloadBtn = document.getElementById('crmDownloadBtn');
        if (!downloadBtn) {
            return;
        }
        var query = window.location.search || '';
        downloadBtn.setAttribute('href', "<?= base_url() ?>crm/download" + query);
    }

    function applyCrmSearch() {
        var params = new URLSearchParams(window.location.search);
        var formData = $('#form-search').serializeArray();
        var keys = ['keyword', 'keyword_category', 'start_date', 'until_date', 'ids', 'view'];

        keys.forEach(function(key) {
            params.delete(key);
        });

        formData.forEach(function(field) {
            if (keys.indexOf(field.name) !== -1) {
                params.set(field.name, field.value || '');
            }
        });

        params.set('page', '1');
        var newUrl = window.location.pathname + '?' + params.toString();
        window.history.replaceState({}, '', newUrl);
        syncCrmDownloadHref();

        if (typeof loadMoreData === 'function') {
            loadMoreData();
        } else {
            window.location.href = newUrl;
        }
    }

    $('#form-search').off('submit.crmSearch').on('submit.crmSearch', function(e) {
        e.preventDefault();
        applyCrmSearch();
    });

    $('#form-search input[name="keyword"]').off('keydown.crmSearch').on('keydown.crmSearch', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#form-search').trigger('submit');
        }
    });

    $('#crmDownloadBtn').off('click.crmDownload').on('click.crmDownload', function() {
        syncCrmDownloadHref();
    });

    syncCrmDownloadHref();
</script>


<script>
    function setCrmTableLoading(isLoading) {
        var overlay = document.getElementById('crmTableLoadingOverlay');
        if (!overlay) {
            return;
        }
        if (isLoading) {
            overlay.classList.add('active');
        } else {
            overlay.classList.remove('active');
        }
    }

    function loadMoreData() {
        const queryString = window.location.search ? window.location.search : '';
        setCrmTableLoading(true);
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>crm/item" + queryString,
            success: function(data) {
                if (window.crmGridOptions && window.crmGridOptions.api) {
                    try { window.crmGridOptions.api.destroy(); } catch (e) {}
                    window.crmGridOptions = null;
                }
                if (window.crmHeaderInstances) {
                    window.crmHeaderInstances = {};
                }
                $('#item-container').html(data);
                var totalText = $('#item-container #crmTotalLabel').text() || '';
                var matchTotal = totalText.match(/([\d\.,]+)/);
                if (matchTotal && $('#crmFoundCountLabel').length) {
                    $('#crmFoundCountLabel').html('<p class="mb-1"><label class="text-notif">' + matchTotal[1] + ' data ditemukan!</label></p>');
                }
                if (typeof select_5 === 'function') {
                    select_5();
                }
                window.list_id_v2 = '';
                $('#id_selected').val('');
                setTimeout(function() {
                    get_id();
                }, 0);
            },
            error: function(xhr, status, error) {
                console.error("Error loading data:", error);
            },
            complete: function() {
                setCrmTableLoading(false);
            }
        });
    }

    loadMoreData();
</script>
