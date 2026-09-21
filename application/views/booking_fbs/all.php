<?php
$start_date = $start_date ?? date('Y-m-01');
$until_date = $until_date ?? date('Y-m-d');
$keyword = $keyword ?? '';
$keyword_field = $keyword_field ?? 'booking_sn';
$booking_status = $booking_status ?? '';
$print_status = $print_status ?? '';
$per_page_options = $per_page_options ?? [50, 100, 200, 500];
$limit = $limit ?? 50;
$bookings = $bookings ?? [];
$total_rows = $total_rows ?? 0;
$current_page = $current_page ?? 1;
$page_count = $page_count ?? 1;
$date_type = $date_type ?? 'booking';
$keyword_fields = [
    'booking_sn'       => 'Booking SN',
    'order_sn'         => 'Order SN',
    'recipient_name'   => 'Nama Penerima',
    'recipient_phone'  => 'Telepon Penerima',
    'items'            => 'SKU',
];
$date_type_labels = [
    'booking' => 'Tanggal Booking',
    'pickup' => 'Tanggal Pickup',
    'deadline' => 'Tenggat Pengiriman'
];
$current_date_label = $date_type_labels[$date_type] ?? $date_type_labels['booking'];

if (!function_exists('esc_html')) {
    function esc_html($str) {
        return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
    }
}

$start_date_value = esc_html($_GET['start_date'] ?? $start_date);
$until_date_value = esc_html($_GET['until_date'] ?? $until_date);
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h3 class="text-primary fw-600">RESERVASI SHOPEE</h3>
    </div>
</div>

<div class="mb-3 d-flex flex-wrap gap-2">
    <?php
    $statusButtons = [
        ['value' => '',          'label' => 'Semua Booking'],
        ['value' => 'READY_TO_SHIP', 'label' => 'Menunggu Diproses'],
        ['value' => 'PROCESSED', 'label' => 'Diproses'],
        ['value' => 'MATCHED',   'label' => 'Pengiriman'],
        ['value' => 'SHIPPED',   'label' => 'Terkirim'],
        ['value' => 'CANCELLED', 'label' => 'Dibatalkan'],
    ];
    
    $currentStatus = $_GET['booking_status'] ?? '';
    
    foreach ($statusButtons as $btn):
        $value = $btn['value'];
        $label = $btn['label'];
        $is_active = ($value === '' && $currentStatus === '') || $value === $currentStatus;
        $btn_class = $is_active ? 'btn-default-selected' : 'btn-default';
    ?>
        <button type="button"
            class="btn <?= $btn_class ?> btn-status"
            data-status-value="<?= esc_html($value) ?>">
            <?= esc_html($label) ?>
        </button>
    <?php endforeach; ?>
</div>

<form id="bookingFilters" class="row g-3 align-items-end mb-3" data-current-page="<?= (int)$current_page ?>" data-total-pages="<?= (int)$page_count ?>">
    <div class="col-md-3">
        <div class="input-group">
            <button class="btn btn-outline-secondary dropdown-toggle" 
                    type="button" 
                    data-bs-toggle="dropdown" 
                    aria-expanded="false"
                    style="border-top-right-radius:0 !important; border-bottom-right-radius:0 !important;">
                <?= esc_html($current_date_label) ?>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" data-type="booking">Tanggal Booking</a></li>
                <li><a class="dropdown-item" href="#" data-type="pickup">Tanggal Pickup</a></li>
                <li><a class="dropdown-item" href="#" data-type="deadline">Tenggat Pengiriman</a></li>
            </ul>
            <input type="hidden" name="date_type" id="date_type" value="<?= esc_html($date_type) ?>">
            <input type="text" class="form-control form-control-sm" id="tanggal" placeholder="Pilih rentang tanggal...">
            <input type="hidden" name="start_date" id="start_date" value="<?= $start_date_value ?>">
            <input type="hidden" name="until_date" id="end_date" value="<?= $until_date_value ?>">
        </div>
    </div>

    <script>
        get_filter();

        function get_filter() {
            $.ajax({
                dataType: "json",
                url: '<?= base_url() ?>/ajax/get-filter?page=endorse',
                data: {
                    start_date: "<?= $start_date_value ?>",
                    until_date: "<?= $until_date_value ?>",
                    date_type: $("#date_type").val()
                },
                success: function(response) {
                    $("#tanggal").after(response.html); 
                },
                error: function(xhr, status, error) {
                    console.error("Error loading filter:", error);
                }
            });
        }

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
                get_filter();
            });
        });
    </script>
    <div class="col-lg-4">
        <div class="input-group">
            <button class="btn btn-outline-secondary dropdown-toggle" 
                    type="button" 
                    data-bs-toggle="dropdown" 
                    aria-expanded="false"
                    style="border-top-right-radius:0 !important; border-bottom-right-radius:0 !important;">
                <?= isset($keyword_fields[$keyword_field]) ? $keyword_fields[$keyword_field] : 'Pilih Kategori'; ?>
            </button>

            <ul class="dropdown-menu">
                <?php foreach ($keyword_fields as $key => $label): ?>
                    <li>
                        <a class="dropdown-item" 
                        href="?keyword_field=<?= $key ?>&keyword=<?= urlencode($keyword) ?>">
                            <?= $label ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <input type="hidden" name="keyword_field" value="<?= esc_html($keyword_field) ?>">

            <input type="text" 
                name="keyword" 
                class="form-control" 
                placeholder="Cari..."
                value="<?= esc_html($keyword) ?>"
                style="border-top-left-radius:0 !important; border-bottom-left-radius:0 !important;">
        </div>
    </div>

    <div class="col-md-2">
        <select name="print_status" id="printStatusFilter" class="form-control">
            <option value="" <?= $print_status === '' ? 'selected' : '' ?>>Semua</option>
            <option value="printed" <?= $print_status === 'printed' ? 'selected' : '' ?>>Sudah Cetak</option>
            <option value="not_printed" <?= $print_status === 'not_printed' ? 'selected' : '' ?>>Belum Cetak</option>
        </select>
    </div>

    <div class="col-md-1">
        <button type="submit" class="btn btn-primary w-100" style="margin-top: -46px;">Terapkan</button>
    </div>
    <input type="hidden" name="booking_status" id="bookingStatusInput" value="<?= esc_html($booking_status) ?>">
    <input type="hidden" name="limit" id="limitHidden" value="<?= (int)$limit ?>">
</form>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3" id="tableToolbar">
    <button type="button" id="btnColumns" class="btn btn-sm btn-outline-primary" style="padding: 0px 18px !important; font-size: 13px !important; line-height: 1.2 !important; height: 36px !important;">
        <i class="bi bi-layout-three-columns me-1"></i> Kolom
    </button>
    <button type="button" id="btnResetFilters" class="btn btn-sm btn-outline-danger" style="padding: 0px 18px !important; font-size: 13px !important; line-height: 1.2 !important; height: 36px !important;">
        <i class="bi bi-x-circle me-1"></i> Reset Filter
    </button>
    <button type="button" id="btnRefreshTracking" class="btn btn-sm btn-outline-secondary d-none" style="padding: 0px 18px !important; font-size: 13px !important; line-height: 1.2 !important; height: 36px !important;">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh No Resi
    </button>
    <div class="dropdown history-dropdown d-none" id="historyPrintDropdown">
        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="dropdownHistory" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0px 18px !important; font-size: 13px !important; line-height: 1.2 !important; height: 36px !important;">
            History Cetak
        </button>
        <ul class="dropdown-menu" aria-labelledby="dropdownHistory">
            <?php if (!empty($print_history)): ?>
                <?php foreach ($print_history as $hist): ?>
                    <li>
                        <a class="dropdown-item" href="<?= base_url('booking-fbs/print-history?id=' . $hist['id']) ?>" target="_blank" rel="noopener">
                            <?= esc_html($hist['label'] ?? 'History Cetak') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li><span class="dropdown-item-text">History Kosong.</span></li>
            <?php endif; ?>
        </ul>
    </div>
    <button type="button" id="btnDownloadExcel" class="btn btn-sm btn-success" style="padding: 0px 18px !important; font-size: 13px !important; line-height: 1.2 !important; height: 36px !important;">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download Excel
    </button>
    
</div>

<div class="d-flex flex-wrap align-items-center gap-3 mb-2">
    <div>
        <strong>Total Data:</strong> <span id="totalCounter"><?= number_format((int)$total_rows, 0, ',', '.') ?></span>
    </div>
    <div>|</div>
    <div id="pageInfo">
        Halaman <?= (int)$current_page ?> dari <?= (int)$page_count ?>
    </div>
</div>

<div id="agGridWrapper" class="ag-theme-quartz position-relative" style="height:70vh;">
    <div id="bookingGrid" style="width:100%; height:100%;"></div>
    <div id="filter-portal"></div>
    <div id="gridOverlay" class="grid-overlay d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 mb-0 text-muted small">Memuat data booking...</p>
    </div>
</div>

<?php
$per_page_options = $per_page_options ?? [50, 100, 200, 500];
$currentLimit = (int)($limit ?? 100);
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3">
    <div class="flex-grow-1" id="paginationWrapper">
        <?= $pagination ?? '' ?>
    </div>
    <div class="d-flex align-items-center gap-2">
        <select id="limitSelect" class="form-select form-select-sm" style="min-width: 150px;">
            <?php foreach ($per_page_options as $opt): ?>
                <option value="<?= (int)$opt ?>" <?= $currentLimit === (int)$opt ? 'selected' : '' ?>>
                    <?= (int)$opt ?> / Halaman
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="floating-actions">
    <div id="shipping-action-bar" class="shipping-action-bar">
        <button type="button" class="shipping-action-bar__primary" data-action="ship" onclick="ship_packages_bulk()">Atur pengiriman</button>
        <button type="button" class="shipping-action-bar__secondary" onclick="get_shipping_documents_bulk()">Cetak dokumen</button>
    </div>

    <div id="selected-counter" class="selected-counter-floating">0 data dipilih</div>
</div>

<input type="hidden" id="id_selected" name="id_selected" value="">

<style>
    .grid-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.7);
        z-index: 20;
    }
    .btn-default {
        border: 1px solid #dfe3e7;
        background: #fff;
        color: #495057;
    }
    .btn-default-selected {
        border: 1px solid #0d6efd;
        color: #0d6efd;
        background: rgba(13,110,253,.08);
    }
    .history-dropdown:hover > .dropdown-menu {
        display: block;
    }
    .history-dropdown .dropdown-menu {
        margin-top: 0;
        max-height: 280px;
        overflow: auto;
    }
    .dot, .dot-active {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 6px;
        background: #d0d5dd;
    }
    .dropdown-panel {
        position: absolute;
        width: 280px;
        z-index: 1050;
        background: #fff;
        border: 1px solid #e6e9ec;
        border-radius: 8px;
        box-shadow: 0 12px 30px rgba(15,23,42,.12);
    }
    .dropdown-panel .list {
        max-height: 260px;
        overflow: auto;
    }
    .btn-xs {
        font-size: 0.75rem;
        padding: 2px 6px;
    }
    .hdr-filter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: default;
    }
    .hdr-title {
        cursor: pointer;
        user-select: none;
        font-weight: 600;
    }
    .btn-filter {
        border: none;
        background: transparent;
        cursor: pointer;
        color: #6c757d;
    }
    .hdr-dd-portal {
        position: absolute;
        width: 260px;
        max-height: 320px;
        z-index: 1100;
        background: #fff;
        border: 1px solid #e4e6eb;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(15,23,42,.18);
        display: flex;
        flex-direction: column;
    }
    .hdr-dd-portal .search {
        margin: 8px;
    }
    .hdr-dd-portal .list {
        flex: 1 1 auto;
        padding: 0 12px 12px;
        overflow: auto;
    }
    .hdr-dd-portal .actions {
        border-top: 1px solid #edf0f2;
        padding: 8px;
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }
    .btn-2xs {
        font-size: 12px;
        padding: 8px 16px;
        border-radius: 6px;
    }
    .sort-spinner {
        animation: spin 1s linear infinite;
        display: inline-block;
        color: #0d6efd;
        font-size: 0.9rem;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .floating-actions {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 1100;
    }

    .selected-counter-floating {
        position: static;
        background: #0d6efd;
        color: #fff;
        padding: 10px 14px;
        border-radius: 12px;
        box-shadow: 0 10px 28px rgba(0,0,0,0.12);
        font-weight: 600;
        display: none;
        align-items: center;
        gap: 8px;
    }

    .selected-counter-floating.show {
        display: inline-flex;
    }

    .shipping-action-bar {
        position: static;
        display: none;
        align-items: center;
        gap: 12px;
        background: #ffffff;
        border-radius: 14px;
        padding: 10px 16px;
        box-shadow: 0 10px 28px rgba(0,0,0,0.12);
    }

    .shipping-action-bar.shipping-action-bar--show {
        display: flex;
    }

    .shipping-action-bar button {
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
            padding: 10px 16px;
            cursor: pointer;
            transition: all .2s ease;
        }

    .shipping-action-bar__primary {
        background: #067dbdff;
        color: #ffffff;
    }

    .shipping-action-bar__primary[disabled],
    .shipping-action-bar__primary.shipping-action-bar__primary--disabled {
        background: #c8e2ffff;
        color: #fff;
        cursor: not-allowed;
        opacity: 0.85;
    }

    .shipping-action-bar__primary:hover {
        background: #2ab4ffff;
    }

    .shipping-action-bar__secondary {
        background: #e9ecef;
        color: #333;
    }

    .shipping-action-bar__secondary:hover {
        background: #dfe3e6;
    }

    .shipping-action-bar__more {
        background: #f5f5f5;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .shipping-action-bar__more:hover {
        background: #e9e9e9;
    }
    
    .order-cell {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding-right: 18px;
    }
    .order-link {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        font-size: 12px;
        line-height: 1.3;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: #fff;
        cursor: pointer;
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .order-link:hover {
        background: #f8fafc;
    }
    .order-copy {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        opacity: .7;
        color: #6c757d;
    }
    .order-copy:hover {
        opacity: 1;
        color: #0d6efd;
    }
</style>

<div class="modal fade bd-example-modal-sm" tabindex="-1" varietas="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-form">
    <div class="modal-dialog modal-md">
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


<script>
(function(){
    const baseUrl = '<?= base_url() ?>';
    const bookingGridEl = document.getElementById('bookingGrid');
    const formEl = document.getElementById('bookingFilters');
    const limitSelect = document.getElementById('limitSelect');
    const limitHidden = document.getElementById('limitHidden');
    const bookingStatusInput = document.getElementById('bookingStatusInput');
    const totalCounter = document.getElementById('totalCounter');
    const pageInfo = document.getElementById('pageInfo');
    const overlay = document.getElementById('gridOverlay');
    const btnResetFilters = document.getElementById('btnResetFilters');
    const printStatusSelect = document.getElementById('printStatusFilter');
    const statusButtons = document.querySelectorAll('.btn-status');
    const columnButton = document.getElementById('btnColumns');
    const downloadButton = document.getElementById('btnDownloadExcel');
    const refreshButton = document.getElementById('btnRefreshTracking');
    const historyPrintDropdown = document.getElementById('historyPrintDropdown');
    const paginationWrapper = document.getElementById('paginationWrapper');
    const filterableFields = [
        'booking_sn',
        'tracking_number',
        'booking_status',
        'match_status',
        'shipping_carrier',
        'region',
        'recipient_name',
        'recipient_phone',
        'recipient_city',
        'recipient_state',
        'recipient_region',
        'shop_id',
        'shop_name',
        'print_at',
        'rts_at'
    ];
    const valueFilters = {};
    const distinctCache = {};
    function updateSelectedCounter(total) {
        var el = document.getElementById('selected-counter');
        if (!el) return;
        if (total > 0) {
            el.textContent = total + ' data dipilih';
            el.classList.add('show');
        } else {
            el.textContent = '0 data dipilih';
            el.classList.remove('show');
        }
    }

    let currentPage = parseInt(formEl.dataset.currentPage || '1', 10);
    let totalPages = parseInt(formEl.dataset.totalPages || '1', 10);
    let isLoading = false;

    const initialRows = <?= json_encode($bookings, JSON_UNESCAPED_UNICODE) ?>;
    if (typeof window.list_id_v2 === 'undefined') window.list_id_v2 = '';
    if (typeof window.shippingActionState === 'undefined') {
        window.shippingActionState = { total: 0, ready: 0, processed: 0 };
    }
    window.copyBookingSn = async function(text) {
        text = text ? String(text) : '';
        if (!text) return;
        try {
            await navigator.clipboard.writeText(text);
            Swal.fire({
                icon: 'success',
                title: 'Tersalin!',
                text: 'Booking SN berhasil disalin: ' + text,
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } catch (err) {
            const ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            Swal.fire({
                icon: 'warning',
                title: 'Disalin dengan fallback',
                text: text,
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    };

    function escHtml(value) {
        if (value === null || value === undefined) return '';
        return String(value).replace(/[&<>"']/g, function(c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]);
        });
    }

    const COLSTATE_KEY = `bookingFbs:columns:${location.pathname}`;
    let gridApi = null;
    let defaultColState = [];

    function getParamValues(params, key) {
        return params.getAll(`${key}[]`).concat(params.getAll(key));
    }

    function loadFiltersFromURL() {
        const params = new URLSearchParams(window.location.search);
        const fields = getParamValues(params, 'filter_field');
        const values = getParamValues(params, 'filter_value');
        if (!fields.length || fields.length !== values.length) return;
        fields.forEach((field, idx) => {
            if (!filterableFields.includes(field)) return;
            if (!valueFilters[field]) valueFilters[field] = new Set();
            valueFilters[field].add(values[idx]);
        });
    }

    function clearValueFilters() {
        Object.keys(valueFilters).forEach(key => {
            if (valueFilters[key] instanceof Set) {
                valueFilters[key].clear();
            } else {
                valueFilters[key] = new Set();
            }
        });
    }

    function appendValueFilters(params, skipField = null) {
        Object.entries(valueFilters).forEach(([field, set]) => {
            if (skipField && field === skipField) return;
            if (!(set instanceof Set) || set.size === 0) return;
            set.forEach(val => {
                params.append('filter_field[]', field);
                params.append('filter_value[]', val);
                params.append('filter_operator[]', 'equals');
            });
        });
    }

    async function fetchDistinct(field) {
        if (distinctCache[field]) return distinctCache[field];
        const params = new URLSearchParams(new FormData(formEl));
        params.set('field', field);
        appendValueFilters(params, field);
        const res = await fetch(`${baseUrl}booking-fbs/filter-values?${params.toString()}`, {
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        if (!json.ok) throw new Error(json.error || 'Gagal memuat nilai filter');
        distinctCache[field] = json.values || [];
        return distinctCache[field];
    }

    function clearDistinctCache() {
        Object.keys(distinctCache).forEach(key => delete distinctCache[key]);
    }

    loadFiltersFromURL();

    const refreshButtonDefaultHtml = refreshButton ? refreshButton.innerHTML : '';
    function isProcessedFilterActive() {
        return bookingStatusInput && (bookingStatusInput.value || '').toUpperCase() === 'PROCESSED';
    }

    function updateRefreshButtonVisibility() {
        if (!refreshButton) return;
        const show = isProcessedFilterActive();
        refreshButton.classList.toggle('d-none', !show);
        if (!show) {
            refreshButton.disabled = false;
            refreshButton.innerHTML = refreshButtonDefaultHtml;
        }
    }

    function updateHistoryPrintVisibility() {
        if (!historyPrintDropdown) return;
        const show = isProcessedFilterActive();
        historyPrintDropdown.classList.toggle('d-none', !show);
    }

    updateRefreshButtonVisibility();
    updateHistoryPrintVisibility();

    function headerWithFilter(field, title, extra = {}) {
        return Object.assign({
            headerName: title,
            field,
            filter: false,
            sortable: true,
            headerComponent: class {
                init(params) {
                    this.params = params;
                    this.field = field;
                    this.btn = document.createElement('button');
                    this.btn.className = 'btn-filter bi bi-funnel';
                    this.btn.setAttribute('type', 'button');
                    this.btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.toggle();
                    });

                    const e = this.eGui = document.createElement('div');
                    e.className = 'hdr-filter';

                    const titleEl = document.createElement('span');
                    titleEl.className = 'hdr-title';
                    titleEl.textContent = title;
                    titleEl.addEventListener('click', () => this.progressSort());

                    const sortIcon = document.createElement('i');
                    sortIcon.className = 'bi bi-arrow-down-up';
                    this.sortIcon = sortIcon;

                    e.appendChild(titleEl);
                    e.appendChild(sortIcon);
                    e.appendChild(this.btn);

                    this.updateFilterIndicator = () => {
                        const active = valueFilters[this.field] instanceof Set && valueFilters[this.field].size > 0;
                        this.btn.classList.toggle('text-primary', active);
                    };

                    this.updateSortIcon = () => {
                        const state = params.column.getSort();
                        if (state === 'asc') sortIcon.className = 'bi bi-arrow-up';
                        else if (state === 'desc') sortIcon.className = 'bi bi-arrow-down';
                        else sortIcon.className = 'bi bi-arrow-down-up';
                    };

                    this.onSortChanged = () => this.updateSortIcon();
                    params.api.addEventListener('sortChanged', this.onSortChanged);
                    this.updateSortIcon();
                    this.updateFilterIndicator();
                }

                progressSort() {
                    const current = this.params.column.getSort();
                    let next = 'asc';
                    if (current === 'asc') next = 'desc';
                    else if (current === 'desc') next = null;
                    this.params.api.applyColumnState({
                        state: [{ colId: this.params.column.getColId(), sort: next }],
                        defaultState: { sort: null }
                    });
                }

                async toggle() {
                    if (!this._loaded) {
                        try {
                            const values = await fetchDistinct(this.field);
                            this._allValues = values;
                            this._loaded = true;
                        } catch (err) {
                            console.error('Gagal memuat nilai filter:', err);
                            return;
                        }
                    }
                    document.querySelectorAll('.hdr-dd-portal').forEach(el => el.remove());
                    if (this.isOpen) {
                        this.isOpen = false;
                        return;
                    }
                    this.openDropdown();
                }

                openDropdown() {
                    this.isOpen = true;
                    const wrapper = document.getElementById('agGridWrapper');
                    const portal = document.getElementById('filter-portal');
                    const dd = document.createElement('div');
                    dd.className = 'hdr-dd-portal';
                    dd.innerHTML = `
                        <input type="text" class="form-control form-control-sm search" placeholder="Cari nilai..." style="width: 240px !important; height: 32px !important;">
                        <div class="list my-1"></div>
                        <div class="actions">
                            <button type="button" class="btn btn-2xs btn-light btn-clear" style="width: 240px !important; height: 32px !important;">Reset</button>
                            <button type="button" class="btn btn-2xs btn-primary btn-apply" style="width: 240px !important; height: 32px !important;">Terapkan</button>
                        </div>
                    `;
                    portal.appendChild(dd);

                    const renderList = (items) => {
                        const sel = valueFilters[this.field] || new Set();
                        const html = items.map(val => {
                            const safe = escHtml(String(val));
                            const checked = sel.has(val);
                            return `<label class="d-block mb-1"><input type="checkbox" data-val="${safe}" ${checked ? 'checked' : ''}> ${safe}</label>`;
                        }).join('');
                        dd.querySelector('.list').innerHTML = html || '<div class="text-muted small">Tidak ada data</div>';
                        dd.querySelectorAll('input[type="checkbox"][data-val]').forEach(cb => {
                            cb.addEventListener('change', () => {
                                if (!valueFilters[this.field]) valueFilters[this.field] = new Set();
                                const val = cb.getAttribute('data-val');
                                if (cb.checked) valueFilters[this.field].add(val);
                                else valueFilters[this.field].delete(val);
                            });
                        });
                    };

                    const position = () => {
                        const btnRect = this.btn.getBoundingClientRect();
                        const wrapRect = wrapper.getBoundingClientRect();
                        let left = btnRect.left - wrapRect.left;
                        let top = btnRect.bottom - wrapRect.top;
                        const margin = 8;
                        const width = dd.offsetWidth || 260;
                        const maxLeft = wrapper.clientWidth - width - margin;
                        if (left > maxLeft) left = Math.max(margin, maxLeft);
                        if (left < margin) left = margin;
                        dd.style.left = left + 'px';
                        dd.style.top = top + 'px';
                    };

                    renderList(this._allValues || []);
                    position();

                    dd.querySelector('.search').addEventListener('input', (e) => {
                        const q = e.target.value.toLowerCase();
                        const filtered = (this._allValues || []).filter(v => String(v).toLowerCase().includes(q));
                        renderList(filtered);
                        position();
                    });

                    dd.querySelector('.btn-apply').addEventListener('click', () => {
                        currentPage = 1;
                        fetchData();
                        this.updateFilterIndicator();
                        close();
                    });

                    dd.querySelector('.btn-clear').addEventListener('click', () => {
                        valueFilters[this.field] = new Set();
                        currentPage = 1;
                        fetchData();
                        this.updateFilterIndicator();
                        close();
                    });

                    const onResize = () => position();
                    const onScroll = () => position();
                    const viewport = wrapper.querySelector('.ag-center-cols-viewport');
                    window.addEventListener('resize', onResize, { passive: true });
                    window.addEventListener('scroll', onScroll, true);
                    viewport?.addEventListener('scroll', onScroll, { passive: true });

                    const clickOutside = (ev) => {
                        if (!dd.contains(ev.target) && ev.target !== this.btn) {
                            close();
                        }
                    };
                    setTimeout(() => document.addEventListener('mousedown', clickOutside), 0);

                    const close = () => {
                        if (!this.isOpen) return;
                        this.isOpen = false;
                        dd.remove();
                        window.removeEventListener('resize', onResize);
                        window.removeEventListener('scroll', onScroll, true);
                        viewport?.removeEventListener('scroll', onScroll);
                        document.removeEventListener('mousedown', clickOutside);
                    };

                    this._close = close;
                }

                getGui() {
                    return this.eGui;
                }

                refresh() { return false; }

                destroy() {
                    if (this._close) this._close();
                    this.params.api.removeEventListener('sortChanged', this.onSortChanged);
                }
            }
        }, extra);
    }

    const columnDefs = [
        headerWithFilter('booking_sn', 'Kode Reservasi', { minWidth: 210, cellRenderer: params => {
            if (!params.value) return '-';
            const safe = escHtml(params.value);
            return `
                <div class="order-cell">
                    <button type="button" class="order-link" data-sn="${safe}" onclick="window.copyBookingSn(this.dataset.sn)">
                        ${safe}
                    </button>
                    <i class="bi bi-copy order-copy" title="Copy Booking SN" data-sn="${safe}" onclick="window.copyBookingSn(this.dataset.sn)"></i>
                </div>
            `;
        }}),
        { headerName: 'Order ID', field: 'order_sn', minWidth: 140, hide: true },
        headerWithFilter('tracking_number', 'No Resi', { minWidth: 160 }),
        headerWithFilter('booking_status', 'Status Booking', { minWidth: 160 }),
        headerWithFilter('shipping_carrier', 'Kurir', { minWidth: 140 }),
        { headerName: 'Tanggal Booking', field: 'create_time', minWidth: 160 },
        { headerName: 'Tenggat Pengiriman', field: 'ship_by_date', minWidth: 160, hide: true },
        headerWithFilter('rts_at', 'Tanggal RTS', { minWidth: 160, hide: true }),
        headerWithFilter('recipient_name', 'Nama Penerima', { minWidth: 160 }),
        headerWithFilter('recipient_phone', 'Telepon', { minWidth: 140, hide: true }),
        headerWithFilter('recipient_city', 'Kota', { minWidth: 140, hide: true }),
        headerWithFilter('recipient_state', 'Provinsi', { minWidth: 140, hide: true }),
        headerWithFilter('shop_name', 'Toko', { minWidth: 120, hide: true }),
        headerWithFilter('print_at', 'Tanggal Cetak', { minWidth: 120, hide: true }),
        { headerName: 'Produk', field: 'items_summary', minWidth: 220, flex: 1,
            cellRenderer: params => params.value ? escHtml(params.value) : '-' }
    ];

    const gridOptions = {
        theme: 'legacy',
        columnDefs: columnDefs,
        defaultColDef: {
            sortable: true,
            filter: false,
            resizable: true,
            flex: 1,
            minWidth: 120
        },
        animateRows: true,
        rowData: initialRows,
        suppressCellFocus: true,
        suppressDragLeaveHidesColumns: true,
        onGridReady: function(params) {
            gridApi = params.api;
            defaultColState = (gridApi.getColumns() || []).map((col, idx) => ({
                colId: col.getColId(),
                hide: !col.isVisible(),
                order: idx
            }));
            loadColumnState();
            setTimeout(() => gridApi.sizeColumnsToFit(), 80);
        },
        onSortChanged: function() {
            currentPage = 1;
            fetchData();
        },
        onFilterChanged: function() {
            currentPage = 1;
            fetchData();
        },
        rowSelection: {
            mode: 'multiRow',
            checkboxes: true,
            headerCheckbox: true,
            selectAll: 'filtered',
            enableClickSelection: false
        },
        isRowSelectable: (node) => {
            return !!(node?.data?.id);
        },
        suppressRowClickSelection: true,
        onSelectionChanged: syncSelectedIds,
        onColumnVisible: saveColumnState,
        onColumnMoved: saveColumnState,
        onColumnPinned: saveColumnState,
        onColumnResized: function() {
            clearTimeout(window.__bookingResizeTimer);
            window.__bookingResizeTimer = setTimeout(saveColumnState, 250);
        }
    };

    gridApi = agGrid.createGrid(bookingGridEl, gridOptions);
    syncSelectedIds();

    function getCurrentColumnState() {
        if (!gridApi) return [];
        return (gridApi.getColumnState() || []).map(s => ({
            colId: s.colId,
            hide: !!s.hide,
            width: s.width,
            pinned: s.pinned || null
        }));
    }

    function saveColumnState() {
        try {
            if (!gridApi) return;
            const state = getCurrentColumnState();
            sessionStorage.setItem(COLSTATE_KEY, JSON.stringify(state));
        } catch (err) {
            console.warn('saveColumnState failed:', err);
        }
    }

    function loadColumnState() {
        try {
            if (!gridApi) return false;
            const txt = sessionStorage.getItem(COLSTATE_KEY);
            if (!txt) return false;
            const state = JSON.parse(txt);
            if (!Array.isArray(state) || state.length === 0) return false;
            gridApi.applyColumnState({ state, applyOrder: true });
            return true;
        } catch (err) {
            console.warn('loadColumnState failed:', err);
            return false;
        }
    }

    function syncSelectedIds() {
        if (!gridApi) return;
        const rows = gridApi.getSelectedRows() || [];
        const ids = [];
        let readyCount = 0;
        let processedCount = 0;

        rows.forEach(row => {
            const rowId = row.booking_sn;
            if (rowId) ids.push(rowId);
            const status = String(row.order_status || row.booking_status || '').toUpperCase();
            if (status === 'READY_TO_SHIP') readyCount++;
            if (status === 'PROCESSED') processedCount++;
        });

        window.list_id_v2 = ids.join(',');
        const hiddenInput = document.getElementById('id_selected');
        console.log('Selected IDs:', window.list_id_v2);
        if (hiddenInput) hiddenInput.value = window.list_id_v2;

        window.shippingActionState = {
            total: rows.length,
            ready: readyCount,
            processed: processedCount
        };
        if (typeof applyShippingActionBar === 'function') {
            applyShippingActionBar();
        }
        updateSelectedCounter(rows.length);
    }
    window.get_id = syncSelectedIds;

    function initColumnChooser() {
        if (!columnButton || !bookingGridEl) return;
        const wrapper = document.getElementById('agGridWrapper') || document.body;
        let isOpen = false;
        let panel = null;
        const cleanup = [];

        columnButton.addEventListener('click', () => {
            if (isOpen) closePanel();
            else openPanel();
        });

        function openPanel() {
            if (!gridApi || isOpen) return;
            panel = document.createElement('div');
            panel.className = 'dropdown-panel p-3';
            panel.innerHTML = `
                <div class="mb-2">
                    <input type="text" class="form-control form-control-sm" placeholder="Cari kolom..." data-role="search">
                </div>
                <div class="mb-2 small d-flex align-items-center gap-2">
                    <label class="mb-0"><input type="checkbox" data-role="toggle-all"> Tampilkan semua</label>
                    <button type="button" class="btn btn-xs btn-link p-0 ms-auto" data-role="defaults">Defaults</button>
                </div>
                <div class="list"></div>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-sm btn-light" data-role="cancel">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" data-role="apply">Terapkan</button>
                </div>
            `;
            wrapper.appendChild(panel);
            isOpen = true;

            const columns = (gridApi.getColumns() || [])
                .filter(col => col.getColDef()?.headerName)
                .map(col => ({
                    id: col.getColId(),
                    title: col.getColDef().headerName || col.getColId(),
                    visible: col.isVisible()
                }));

            const listEl = panel.querySelector('.list');
            const renderList = (items) => {
                listEl.innerHTML = items.map(item => `
                    <label class="d-block mb-1">
                        <input type="checkbox" data-col="${escHtml(item.id)}" ${item.visible ? 'checked' : ''}>
                        ${escHtml(item.title)}
                    </label>
                `).join('');
            };
            renderList(columns);

            const positionPanel = () => {
                const btnRect = columnButton.getBoundingClientRect();
                const wrapRect = wrapper.getBoundingClientRect();
                const margin = 12;
                let left = btnRect.left - wrapRect.left;
                let top = btnRect.bottom - wrapRect.top + 6;
                const maxLeft = wrapper.clientWidth - (panel.offsetWidth || 280) - margin;
                if (left > maxLeft) left = Math.max(margin, maxLeft);
                if (left < margin) left = margin;
                panel.style.left = left + 'px';
                panel.style.top = top + 'px';
            };
            positionPanel();

            const onSearch = (e) => {
                const q = (e.target.value || '').toLowerCase();
                const filtered = columns.filter(col =>
                    col.title.toLowerCase().includes(q) || col.id.toLowerCase().includes(q)
                );
                renderList(filtered);
                positionPanel();
            };
            panel.querySelector('[data-role="search"]').addEventListener('input', onSearch);

            const onToggleAll = (e) => {
                const checked = e.target.checked;
                panel.querySelectorAll('input[type="checkbox"][data-col]')
                    .forEach(cb => cb.checked = checked);
            };
            panel.querySelector('[data-role="toggle-all"]').addEventListener('change', onToggleAll);

            const onDefaults = () => {
                if (!defaultColState.length || !gridApi) return;
                gridApi.applyColumnState({
                    state: defaultColState.map(s => ({ colId: s.colId, hide: s.hide })),
                    applyOrder: true
                });
                saveColumnState();
                const visibilityMap = Object.fromEntries(
                    (gridApi.getColumns() || []).map(col => [col.getColId(), col.isVisible()])
                );
                panel.querySelectorAll('input[type="checkbox"][data-col]').forEach(cb => {
                    const id = cb.getAttribute('data-col');
                    cb.checked = !!visibilityMap[id];
                });
            };
            panel.querySelector('[data-role="defaults"]').addEventListener('click', onDefaults);

            panel.querySelector('[data-role="cancel"]').addEventListener('click', closePanel);
            panel.querySelector('[data-role="apply"]').addEventListener('click', () => {
                const checks = Array.from(panel.querySelectorAll('input[type="checkbox"][data-col]'));
                const state = checks.map(cb => ({
                    colId: cb.getAttribute('data-col'),
                    hide: !cb.checked
                }));
                gridApi.applyColumnState({ state, applyOrder: false });
                saveColumnState();
                closePanel();
            });

            const onClickOutside = (ev) => {
                if (panel && !panel.contains(ev.target) && ev.target !== columnButton) {
                    closePanel();
                }
            };
            setTimeout(() => document.addEventListener('mousedown', onClickOutside), 0);

            const onWindowResize = () => positionPanel();
            const onScroll = () => positionPanel();
            const viewport = wrapper.querySelector('.ag-center-cols-viewport');

            window.addEventListener('resize', onWindowResize, { passive: true });
            window.addEventListener('scroll', onScroll, true);
            viewport?.addEventListener('scroll', onScroll, { passive: true });

            cleanup.push(
                () => panel?.remove(),
                () => document.removeEventListener('mousedown', onClickOutside),
                () => window.removeEventListener('resize', onWindowResize),
                () => window.removeEventListener('scroll', onScroll, true),
                () => viewport?.removeEventListener('scroll', onScroll)
            );
        }

        function closePanel() {
            if (!isOpen) return;
            isOpen = false;
            while (cleanup.length) {
                try { cleanup.pop()(); } catch {}
            }
            panel = null;
        }
    }

    initColumnChooser();

    function bindPaginationEvents() {
        if (!paginationWrapper) {
            return;
        }
        const links = paginationWrapper.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (!href) {
                    return;
                }
                e.preventDefault();
                try {
                    const url = new URL(href, window.location.origin);
                    const pageParam = parseInt(url.searchParams.get('page') || '1', 10);
                    if (!Number.isNaN(pageParam)) {
                        currentPage = pageParam;
                        fetchData();
                    }
                } catch (err) {
                    console.warn('Gagal memproses tautan pagination', err);
                }
            });
        });
    }

    function updateMeta(meta) {
        totalCounter.textContent = (meta.total || 0).toLocaleString('id-ID');
        totalPages = meta.page_count || 1;
        currentPage = meta.page || 1;
        pageInfo.textContent = `Halaman ${currentPage} dari ${totalPages}`;
    }

    function getPrimarySort() {
        if (!gridApi) return null;
        const state = gridApi.getColumnState() || [];
        const sorted = state.filter(col => col.sort).sort((a, b) => {
            const ia = typeof a.sortIndex === 'number' ? a.sortIndex : 0;
            const ib = typeof b.sortIndex === 'number' ? b.sortIndex : 0;
            return ia - ib;
        });
        return sorted.length ? sorted[0] : null;
    }

    function buildRequestParams(options = {}) {
        const includePagination = options.includePagination !== false;
        const includeSort = options.includeSort !== false;
        const params = new URLSearchParams();
        const formData = new FormData(formEl);
        formData.forEach((value, key) => {
            if (value !== null && value !== '') {
                params.append(key, value);
            }
        });

        if (includePagination) {
            const targetPage = options.page || currentPage;
            params.set('page', targetPage.toString());
            params.set('limit', limitSelect.value);
        } else {
            params.delete('page');
            params.delete('limit');
        }

        if (includeSort) {
            const sort = getPrimarySort();
            if (sort && sort.sort) {
                params.set('sort_column', sort.colId);
                params.set('sort_order', sort.sort === 'asc' ? 'ASC' : 'DESC');
            }
        }

        appendValueFilters(params);
        return params;
    }

    async function fetchData() {
        if (isLoading) return;
        isLoading = true;
        clearDistinctCache();
        overlay.classList.remove('d-none');
        try {
            const params = buildRequestParams();
            const newUrl = `${window.location.pathname}?${params.toString()}`;
            window.history.replaceState({}, '', newUrl);

            const response = await fetch(`${baseUrl}booking-fbs?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const json = await response.json();
            if (!json.ok) throw new Error(json.error || 'Terjadi kesalahan');

            if (gridApi) {
                gridApi.setGridOption('rowData', json.rows || []);
                gridApi.deselectAll();
                window.list_id_v2 = '';
                const hiddenInput = document.getElementById('id_selected');
                if (hiddenInput) hiddenInput.value = '';
                window.shippingActionState = { total: 0, ready: 0, processed: 0 };
                if (typeof applyShippingActionBar === 'function') {
                    applyShippingActionBar();
                }
                updateSelectedCounter(0);
            }
            if (paginationWrapper && typeof json.pagination !== 'undefined') {
                paginationWrapper.innerHTML = json.pagination || '';
                bindPaginationEvents();
            }
            updateMeta(json.meta || {});
            updateRefreshButtonVisibility();
            updateHistoryPrintVisibility();
        } catch (err) {
            console.error('Gagal memuat data booking:', err);
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Tidak dapat memuat data booking. Silakan coba lagi.',
            });
        } finally {
            overlay.classList.add('d-none');
            isLoading = false;
        }
    }

    let refreshTrackingLoading = false;
    async function refreshTrackingNumber() {
        if (!refreshButton || refreshTrackingLoading) return;
        refreshTrackingLoading = true;
        const originalHtml = refreshButton.innerHTML;
        refreshButton.disabled = true;
        refreshButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Memproses...';

        try {
            const res = await fetch(`${baseUrl}booking-fbs/refresh-tracking`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.ok) {
                const msg = data.message || `Gagal menjalankan refresh no resi (HTTP ${res.status})`;
                throw new Error(msg);
            }
            fetchData();
        } catch (err) {
            console.error('Refresh tracking error:', err);
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: err.message || 'Refresh no resi gagal dijalankan.'
            });
        } finally {
            refreshTrackingLoading = false;
            refreshButton.disabled = false;
            refreshButton.innerHTML = originalHtml;
        }
    }

    formEl.addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        fetchData();
    });

    limitSelect.addEventListener('change', function() {
        if (limitHidden) {
            limitHidden.value = this.value;
        }
        currentPage = 1;
        fetchData();
    });

    if (downloadButton) {
        downloadButton.addEventListener('click', function() {
            const params = buildRequestParams({ includePagination: false });
            const url = `${baseUrl}booking-fbs/download-excel?${params.toString()}`;
            window.location.href = url;
        });
    }

    if (refreshButton) {
        refreshButton.addEventListener('click', refreshTrackingNumber);
    }

    btnResetFilters.addEventListener('click', function() {
        clearValueFilters();
        if (printStatusSelect) {
            printStatusSelect.value = '';
        }
        currentPage = 1;
        fetchData();
        updateRefreshButtonVisibility();
        updateHistoryPrintVisibility();
    });

    statusButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const value = btn.getAttribute('data-status-value') || '';
            bookingStatusInput.value = value;
            statusButtons.forEach(el => {
                el.classList.remove('btn-default-selected');
                el.classList.add('btn-default');
            });
            btn.classList.remove('btn-default');
            btn.classList.add('btn-default-selected');
            currentPage = 1;
            fetchData();
            updateRefreshButtonVisibility();
            updateHistoryPrintVisibility();
        });
    });

    bindPaginationEvents();

    updateRefreshButtonVisibility();
    updateHistoryPrintVisibility();

    updateMeta({
        total: <?= (int)$total_rows ?>,
        page: <?= (int)$current_page ?>,
        page_count: <?= (int)$page_count ?>
    });
})();
</script>

<script>
if (typeof applyShippingActionBar !== 'function') {
    function applyShippingActionBar() {
        var bar = document.getElementById('shipping-action-bar');
        if (!bar) return;
        var btnShip = bar.querySelector('[data-action="ship"]');
        var state = window.shippingActionState || { total: 0, ready: 0, processed: 0 };
        var total = state.total || 0;
        var ready = state.ready || 0;
        var processed = state.processed || 0;
        var show = total > 0 && (ready + processed === total);
        bar.classList.toggle('shipping-action-bar--show', show);
        if (btnShip) {
            var disableShip = !(total > 0 && ready === total && processed === 0);
            btnShip.disabled = disableShip;
            btnShip.classList.toggle('shipping-action-bar__primary--disabled', disableShip);
        }
    }
}

if (typeof ship_packages_bulk !== 'function') {
    function ship_packages_bulk() {
        if (!window.list_id_v2) return;
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Proses Pengiriman');
        $("#load-form").load("<?= base_url() ?>booking_fbs/booking_shipping_process", {
            id_selected: window.list_id_v2,
        });
    }
}

if (typeof get_shipping_documents_bulk !== 'function') {
    function get_shipping_documents_bulk() {
        if (!window.list_id_v2) return;
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Cetak Dokumen Pengiriman');
        $("#load-form").load("<?= base_url() ?>booking_fbs/shipping_documents", {
            id_selected: window.list_id_v2,
        });
    }
}
</script>
