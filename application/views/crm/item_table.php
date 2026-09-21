<?php
$rows = isset($grid_rows) && is_array($grid_rows) ? $grid_rows : array();
$totalCount = isset($total_rows) ? (int)$total_rows : count($rows);
$totalDisplay = number_format($totalCount, 0, ',', '.');

if (!function_exists('crm_table_escape')) {
    function crm_table_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>

<div class="crm-table-toolbar mb-2 d-flex flex-wrap align-items-center gap-2 justify-content-between">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <button id="crmBtnColumns" type="button" class="btn btn-sm btn-outline-primary"
            style="padding: 0px 18px !important; font-size: 13px !important; line-height: 1.2 !important; height: 36px !important;">
            <i class="bi bi-layout-three-columns me-1"></i>
            Tampilkan Kolom
        </button>
        <button id="crmBtnResetFilters" type="button" class="btn btn-sm btn-outline-danger"
            style="padding: 0px 18px !important; font-size: 13px !important; line-height: 1.2 !important; height: 36px !important;">
            <i class="bi bi-x-circle me-1"></i>
            Reset Filter
        </button>
    </div>
    <div class="small text-muted" id="crmTotalLabel">
        Total data: <?= crm_table_escape($totalDisplay) ?>
    </div>
</div>

<div id="crmGridWrapper" class="ag-theme-quartz">
    <div id="crmGrid" style="width:100%; height:100%;"></div>
</div>

<!-- Custom Horizontal Scrollbar -->
<div id="crmCustomScrollbar" class="crm-custom-scrollbar">
    <div class="scrollbar-track">
        <div class="scrollbar-thumb"></div>
    </div>
</div>

<style>
    #crmGridWrapper {
        height: 70vh;
        position: relative;
    }

    /* Custom Horizontal Scrollbar */
    .crm-custom-scrollbar {
        width: 100%;
        height: 12px;
        background: rgba(248, 249, 250, 0.8);
        border: 1px solid rgba(222, 226, 230, 0.6);
        border-radius: 6px;
        margin-top: 8px;
        position: relative;
        cursor: pointer;
        display: none;
    }

    .crm-custom-scrollbar .scrollbar-track {
        width: 100%;
        height: 100%;
        position: relative;
    }

    .crm-custom-scrollbar .scrollbar-thumb {
        height: 100%;
        background: linear-gradient(180deg, rgba(173, 181, 189, 0.7) 0%, rgba(134, 142, 150, 0.7) 100%);
        border-radius: 6px;
        cursor: grab;
        position: absolute;
        left: 0;
        transition: background 0.2s;
        min-width: 50px;
    }

    .crm-custom-scrollbar .scrollbar-thumb:hover {
        background: linear-gradient(180deg, rgba(134, 142, 150, 0.8) 0%, rgba(108, 117, 125, 0.8) 100%);
    }

    .crm-custom-scrollbar .scrollbar-thumb:active {
        cursor: grabbing;
        background: linear-gradient(180deg, rgba(108, 117, 125, 0.9) 0%, rgba(73, 80, 87, 0.9) 100%);
    }

    .crm-custom-scrollbar .scrollbar-thumb::before {
        content: '\22ee\22ee\22ee';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: rgba(255, 255, 255, 0.5);
        font-size: 8px;
        letter-spacing: 2px;
    }

    /* Hide native horizontal scrollbar */
    #crmGridWrapper .ag-body-horizontal-scroll {
        overflow-x: hidden !important;
    }

    .dropdown-panel {
        max-width: 320px;
        z-index: 1050;
    }

    .text-wrap {
        white-space: normal !important;
        line-height: 1.4;
    }

    .crm-cell-middle {
        display: flex;
        align-items: center;
        min-height: 100%;
    }

    .crm-campaign-label {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        min-height: 26px;
        padding: 3px 10px;
        border: 1px solid #b9d9f2;
        border-radius: 4px;
        background: #eef8ff;
        color: #1f4f70;
        font-size: 13px;
        line-height: 1.35;
        white-space: normal;
        word-break: break-word;
    }

    a.crm-campaign-label {
        text-decoration: none;
        cursor: pointer;
    }

    a.crm-campaign-label:hover {
        border-color: #6fb4e8;
        color: #123f5f;
        background: #e3f3ff;
    }

    .hdr-filter {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .hdr-filter .btn-filter {
        border: none;
        background: transparent;
        padding: 0;
        cursor: pointer;
        color: #6c757d;
    }

    .hdr-filter .btn-filter.active {
        color: #0d6efd;
    }

    .hdr-dd-portal {
        position: absolute;
        z-index: 1100;
        width: 260px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 12px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18);
    }

    .hdr-dd-portal .list {
        max-height: 260px;
        overflow: auto;
    }

    .tippy-box[data-theme~='light'] {
        background-color: #ffffff;
        color: #1f2933;
        border: 1px solid #e2e8f0;
        box-shadow: 0 14px 45px rgba(15, 23, 42, 0.18);
        z-index: 2100;
    }

    .tippy-box[data-theme~='light'] .tippy-arrow {
        color: #ffffff;
    }
</style>

<script>
(function () {
    if (!window.agGrid) {
        console.warn('agGrid library tidak tersedia.');
        return;
    }

    const rowData = <?= json_encode($rows, JSON_UNESCAPED_UNICODE) ?>;

    function escHtml(value) {
        if (value == null) {
            return '';
        }
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function delAll(params, base) {
        params.delete(base);
        params.delete(base + '[]');
    }

    function appendArr(params, base, value) {
        params.append(base + '[]', value);
    }

    function getAllEither(params, base) {
        const direct = params.getAll(base);
        if (direct && direct.length) {
            return direct;
        }
        return params.getAll(base + '[]');
    }

    function formatFilterLabel(field, value) {
        if (value === '' || value === null) {
            return '-';
        }
        if (field === 'is_manual') {
            return value === '1' ? 'Manual' : 'Marketplace';
        }
        return value;
    }

    function marketplaceIconUrl(rawMarketplace) {
        const value = String(rawMarketplace || '').toLowerCase();
        if (value.includes('shopee')) {
            return '<?= base_url() ?>assets/img/icon/icon-shopee.png';
        }
        if (value.includes('tiktok')) {
            return '<?= base_url() ?>assets/img/icon/icon-tiktok.png';
        }
        if (value.includes('lazada')) {
            return '<?= base_url() ?>assets/img/icon/icon-lazada.png';
        }
        if (value.includes('whatsapp') || value === 'wa') {
            return '<?= base_url() ?>assets/img/icon/icon-wa.png';
        }
        return '';
    }

    const FILTER_FIELDS = [
        'full_name',
        'phone',
        'username',
        'city_text',
        'marketplace',
        'first_order',
        'product_names',
        'product_qtys',
        'last_order',
        'count_order',
        'cb_cl',
        'keluhan',
        'customer_experience',
        'join_komunitas',
        'campaign_broadcast',
        'id_buyer',
        'no_pesanan',
        'brand'
    ];

    const valueFilters = Object.create(null);
    FILTER_FIELDS.forEach(field => {
        valueFilters[field] = new Set();
    });

    const distinctCache = Object.create(null);
    window.crmHeaderInstances = window.crmHeaderInstances || {};
    let crmColResizeTimer = null;

    function loadFilterStateFromURL() {
        FILTER_FIELDS.forEach(field => valueFilters[field].clear());
        const params = new URLSearchParams(window.location.search);
        const fields = getAllEither(params, 'filter_field');
        const values = getAllEither(params, 'filter_value');

        if (fields.length === values.length && fields.length > 0) {
            for (let i = 0; i < fields.length; i += 1) {
                const field = fields[i];
                if (!FILTER_FIELDS.includes(field)) {
                    continue;
                }
                const rawValue = values[i];
                const normalized = rawValue == null ? '' : String(rawValue);
                valueFilters[field].add(normalized);
            }
        }
    }

    loadFilterStateFromURL();

    function refreshHeaderActiveStates() {
        if (!window.crmHeaderInstances) {
            return;
        }
        Object.values(window.crmHeaderInstances).forEach(instance => {
            if (instance && typeof instance.updateActiveState === 'function') {
                instance.updateActiveState();
            }
        });
    }

    async function fetchDistinct(field) {
        if (distinctCache[field]) {
            return distinctCache[field];
        }

        try {
            const params = new URLSearchParams(window.location.search);
            params.delete('page');
            params.delete('format');
            params.set('field', field);

            const existingFields = getAllEither(params, 'filter_field');
            const existingValues = getAllEither(params, 'filter_value');
            delAll(params, 'filter_field');
            delAll(params, 'filter_value');
            delAll(params, 'filter_operator');

            for (let i = 0; i < existingFields.length; i += 1) {
                if (existingFields[i] === field) {
                    continue;
                }
                if (!FILTER_FIELDS.includes(existingFields[i])) {
                    continue;
                }
                appendArr(params, 'filter_field', existingFields[i]);
                appendArr(params, 'filter_value', existingValues[i]);
                appendArr(params, 'filter_operator', 'contains');
            }

            const res = await fetch(`<?= base_url() ?>crm/filter_values?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            const json = await res.json();
            if (!json.ok) {
                throw new Error(json.error || 'Gagal mengambil opsi filter');
            }
            const values = Array.isArray(json.values) ? json.values : [];
            distinctCache[field] = values.map(v => (v == null ? '' : String(v)));
            return distinctCache[field];
        } catch (err) {
            console.error('fetchDistinct', field, err);
            return [];
        }
    }

    function buildParamsWithFilters(baseParams) {
        const params = new URLSearchParams(baseParams ? baseParams.toString() : window.location.search);
        delAll(params, 'filter_field');
        delAll(params, 'filter_value');
        delAll(params, 'filter_operator');

        Object.entries(valueFilters).forEach(([field, set]) => {
            if (!(set instanceof Set) || set.size === 0) {
                return;
            }
            set.forEach(value => {
                appendArr(params, 'filter_field', field);
                appendArr(params, 'filter_value', value);
                appendArr(params, 'filter_operator', 'contains');
            });
        });

        return params;
    }

    function applyServerSideFilters(baseParams) {
        const params = buildParamsWithFilters(baseParams);
        params.set('page', '1');
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.pushState({}, '', newUrl);
        if (typeof loadMoreData === 'function') {
            loadMoreData();
        } else {
            window.location.reload();
        }
    }

    function clearAllFilters(resetColumns = false) {
        if (resetColumns) {
            window.location.href = '<?= base_url() ?>crm';
            return;
        }
        FILTER_FIELDS.forEach(field => valueFilters[field].clear());
        Object.keys(distinctCache).forEach(key => delete distinctCache[key]);
        const params = new URLSearchParams(window.location.search);
        delAll(params, 'filter_field');
        delAll(params, 'filter_value');
        delAll(params, 'filter_operator');
        params.set('page', '1');
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.pushState({}, '', newUrl);
        if (resetColumns && window.crmGridOptions && window.crmGridOptions.api) {
            crmClearColumnState();
            const api = window.crmGridOptions.api;
            if (typeof api.setColumnDefs === 'function') {
                api.setColumnDefs(columnDefs);
            } else if (typeof api.setGridOption === 'function') {
                api.setGridOption('columnDefs', columnDefs);
            } else {
                console.warn('Grid API missing setColumnDefs/setGridOption');
            }
        }
        if (typeof loadMoreData === 'function') {
            loadMoreData();
        } else {
            window.location.reload();
        }
    }

    function HeaderFilterComponent() {}
    HeaderFilterComponent.prototype.init = function (params) {
        this.params = params;
        const def = params.column.getColDef();
        const compParams = def.headerComponentParams || {};
        this.filterKey = compParams.filterKey || def.field;
        this.title = compParams.title || params.displayName || this.filterKey;
        this.pending = new Set(valueFilters[this.filterKey] ? Array.from(valueFilters[this.filterKey]) : []);
        this.dropdown = null;
        this.isOpen = false;

        const eGui = this.eGui = document.createElement('div');
        eGui.className = 'hdr-filter ag-header-cell';

        const titleEl = document.createElement('span');
        titleEl.className = 'hdr-title';
        titleEl.textContent = this.title;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-filter bi bi-funnel';
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (this.isOpen) {
                this.closeDropdown();
            } else {
                this.openDropdown(btn);
            }
        });

        eGui.appendChild(titleEl);
        eGui.appendChild(btn);

        this.btn = btn;
        window.crmHeaderInstances[this.filterKey] = this;
        this.updateActiveState();
    };

    HeaderFilterComponent.prototype.getGui = function () {
        return this.eGui;
    };

    HeaderFilterComponent.prototype.refresh = function () {
        return false;
    };

    HeaderFilterComponent.prototype.updateActiveState = function () {
        if (!this.btn) {
            return;
        }
        const active = valueFilters[this.filterKey] && valueFilters[this.filterKey].size > 0;
        if (active) {
            this.btn.classList.add('active');
        } else {
            this.btn.classList.remove('active');
        }
    };

    HeaderFilterComponent.prototype.openDropdown = async function (anchor) {
        if (this.isOpen) {
            return;
        }
        this.isOpen = true;
        this.pending = new Set(valueFilters[this.filterKey] ? Array.from(valueFilters[this.filterKey]) : []);
        const values = await fetchDistinct(this.filterKey);

        const dropdown = document.createElement('div');
        dropdown.className = 'hdr-dd-portal';
        dropdown.innerHTML = `
            <div class="mb-2 small fw-600">Filter ${escHtml(this.title)}</div>
            <input type="text" class="form-control form-control-sm mb-2" placeholder="Cari..." data-role="search">
            <div class="list"></div>
            <div class="d-flex justify-content-between gap-2 mt-2">
                <button type="button" class="btn btn-sm btn-light" data-role="clear"><i class="bi bi-repeat"></i></button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-role="cancel">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" data-role="apply">Terapkan</button>
                </div>
            </div>
        `;
        document.body.appendChild(dropdown);
        this.dropdown = dropdown;

        const rect = anchor.getBoundingClientRect();
        dropdown.style.top = `${rect.bottom + window.scrollY + 4}px`;
        dropdown.style.left = `${rect.left + window.scrollX}px`;

        const listEl = dropdown.querySelector('.list');
        const searchInput = dropdown.querySelector('input[data-role="search"]');

        const render = () => {
            const term = searchInput.value.trim().toLowerCase();
            const html = values
                .filter(value => {
                    if (!term) {
                        return true;
                    }
                    return String(value || '').toLowerCase().includes(term);
                })
                .map(value => {
                    const raw = value == null ? '' : String(value);
                    const checked = this.pending.has(raw);
                    const label = formatFilterLabel(this.filterKey, raw);
                    return `
                        <label class="d-block mb-1 small">
                            <input type="checkbox" data-val="${escHtml(raw)}" ${checked ? 'checked' : ''}>
                            ${escHtml(label)}
                        </label>
                    `;
                })
                .join('');

            listEl.innerHTML = html || '<div class="small text-muted">Tidak ada data</div>';

            listEl.querySelectorAll('input[data-val]').forEach(cb => {
                cb.addEventListener('change', (ev) => {
                    const val = ev.target.getAttribute('data-val');
                    if (ev.target.checked) {
                        this.pending.add(val);
                    } else {
                        this.pending.delete(val);
                    }
                });
            });
        };

        render();
        searchInput.addEventListener('input', render);

        dropdown.querySelector('[data-role="clear"]').addEventListener('click', () => {
            this.pending.clear();
            render();
        });

        dropdown.querySelector('[data-role="cancel"]').addEventListener('click', () => {
            this.closeDropdown();
        });

        dropdown.querySelector('[data-role="apply"]').addEventListener('click', () => {
            valueFilters[this.filterKey] = new Set(this.pending);
            this.updateActiveState();
            this.closeDropdown();
            applyServerSideFilters();
        });

        setTimeout(() => {
            const handle = (event) => {
                if (dropdown.contains(event.target) || event.target === anchor) {
                    return;
                }
                this.closeDropdown();
                document.removeEventListener('mousedown', handle);
            };
            document.addEventListener('mousedown', handle);
        }, 0);
    };

    HeaderFilterComponent.prototype.closeDropdown = function () {
        if (!this.isOpen) {
            return;
        }
        this.isOpen = false;
        if (this.dropdown && this.dropdown.parentNode) {
            this.dropdown.parentNode.removeChild(this.dropdown);
        }
        this.dropdown = null;
    };

    HeaderFilterComponent.prototype.destroy = function () {
        if (this.dropdown && this.dropdown.parentNode) {
            this.dropdown.parentNode.removeChild(this.dropdown);
        }
        this.dropdown = null;
        if (window.crmHeaderInstances) {
            delete window.crmHeaderInstances[this.filterKey];
        }
    };

    function headerWithFilter(filterKey, title, options = {}) {
        const dataField = options.dataField || filterKey;
        const extra = options.extra || {};
        return Object.assign({
            headerName: title,
            field: dataField,
            filter: false,
            floatingFilter: false,
            sortable: false,
            suppressMenu: true,
            headerComponent: HeaderFilterComponent,
            headerComponentParams: { filterKey: filterKey, title: title }
        }, extra);
    }

    const CRM_COLSTATE_KEY = `crmGrid:columns:${location.pathname}`;

    function crmGetColumnState(api) {
        if (!api) {
            return [];
        }
        return (api.getColumnState() || []).map(state => ({
            colId: state.colId,
            hide: !!state.hide,
            width: state.width,
            pinned: state.pinned || null,
            sort: state.sort || null,
            sortIndex: (typeof state.sortIndex === 'number') ? state.sortIndex : null
        }));
    }

    function crmSaveColumnState() {
        try {
            if (!window.crmGridOptions || !window.crmGridOptions.api) {
                return;
            }
            const state = crmGetColumnState(window.crmGridOptions.api);
            sessionStorage.setItem(CRM_COLSTATE_KEY, JSON.stringify(state));
        } catch (err) {
            console.warn('crmSaveColumnState failed:', err);
        }
    }

    function crmLoadColumnState(targetApi) {
        try {
            const api = targetApi || (window.crmGridOptions && window.crmGridOptions.api);
            if (!api) {
                return false;
            }
            const stored = sessionStorage.getItem(CRM_COLSTATE_KEY);
            if (!stored) {
                return false;
            }
            const parsed = JSON.parse(stored);
            if (!Array.isArray(parsed) || parsed.length === 0) {
                return false;
            }
            api.applyColumnState({ state: parsed, applyOrder: true });
            return true;
        } catch (err) {
            console.warn('crmLoadColumnState failed:', err);
            return false;
        }
    }

    function crmClearColumnState() {
        try {
            sessionStorage.removeItem(CRM_COLSTATE_KEY);
        } catch (err) {
            console.warn('crmClearColumnState failed:', err);
        }
    }

    const columnDefs = [
        {
            headerName: '',
            colId: 'select',
            width: 52,
            minWidth: 52,
            maxWidth: 52,
            pinned: 'left',
            checkboxSelection: true,
            headerCheckboxSelection: true,
            headerCheckboxSelectionFilteredOnly: false,
            sortable: false,
            filter: false,
            suppressMenu: true,
            resizable: false,
            lockPosition: true
        },
        Object.assign(headerWithFilter('full_name', 'Nama Customer'), {
            flex: 1.4,
            minWidth: 190,
            cellRenderer: params => {
                if (!params.data) return '-';
                const url = escHtml(params.data.detail_url || '#');
                const name = escHtml(params.data.full_name || '-');
                return `
                    <div>
                        <a class="fw-600 a-none text-blue" href="${url}" target="_blank">${name}</a>
                    </div>
                `;
            }
        }),
        Object.assign(headerWithFilter('phone', 'No. HP'), {
            flex: 1.0,
            minWidth: 140,
            cellRenderer: params => {
                if (!params.data) return '-';
                const phone = escHtml(params.data.phone || '-');
                const link = params.data.wa_link ? escHtml(params.data.wa_link) : '';
                return link ? `<a href="${link}" target="_blank">${phone}</a>` : phone;
            }
        }),
        Object.assign(headerWithFilter('username', 'User'), {
            flex: 1.0,
            minWidth: 120,
            valueFormatter: params => escHtml(params.value || '-')
        }),
        Object.assign(headerWithFilter('city_text', 'Domisili'), {
            flex: 1.0,
            minWidth: 140,
            valueFormatter: params => escHtml(params.value || '-')
        }),
        Object.assign(headerWithFilter('marketplace', 'Channel'), {
            flex: 1.0,
            minWidth: 120,
            cellRenderer: params => {
                const raw = params.data?.marketplace_raw || params.value || '';
                const icon = marketplaceIconUrl(raw);
                if (!icon) {
                    return `<span>${escHtml(raw || '-')}</span>`;
                }
                return `<img src="${escHtml(icon)}" alt="${escHtml(raw || 'channel')}" title="${escHtml(raw || '-')}" style="width:20px;height:20px;object-fit:contain;">`;
            }
        }),
        Object.assign(headerWithFilter('first_order', 'Tanggal Order'), {
            flex: 1.0,
            minWidth: 150,
            valueFormatter: params => escHtml(params.value || '-'),
            comparator: (a, b, nodeA, nodeB, isAsc) => {
                const rawA = nodeA.data?.first_order_raw || '';
                const rawB = nodeB.data?.first_order_raw || '';
                return (rawA > rawB ? 1 : rawA < rawB ? -1 : 0) * (isAsc ? 1 : -1);
            }
        }),
        Object.assign(headerWithFilter('product_names', 'Produk yang dibeli'), {
            flex: 1.3,
            minWidth: 220,
            autoHeight: true,
            wrapText: true,
            cellRenderer: params => {
                const raw = params.data?.product_names || '-';
                if (!raw || raw === '-') {
                    return `<div class="text-wrap">-</div>`;
                }
                const lines = String(raw).split('\n');
                const html = lines.map((line, idx) => {
                    const safe = escHtml(line);
                    const border = idx < lines.length - 1 ? 'border-bottom:1px solid #d0d0d0;' : '';
                    return `<div style="${border} padding:2px 0;">${safe}</div>`;
                }).join('');
                return `<div class="text-wrap">${html}</div>`;
            }
        }),
        Object.assign(headerWithFilter('product_qtys', 'Jumlah pembelian (qty)'), {
            flex: 0.8,
            minWidth: 150,
            autoHeight: true,
            wrapText: true,
            cellRenderer: params => {
                const raw = params.data?.product_qtys || '-';
                if (!raw || raw === '-') {
                    return `<div class="text-wrap">-</div>`;
                }
                const lines = String(raw).split('\n');
                const html = lines.map((line, idx) => {
                    const safe = escHtml(line);
                    const border = idx < lines.length - 1 ? 'border-bottom:1px solid #b9b9b9;' : '';
                    return `<div style="${border} padding:2px 0;">${safe}</div>`;
                }).join('');
                return `<div class="text-wrap">${html}</div>`;
            }
        }),
        Object.assign(headerWithFilter('last_order', 'Last Order / Repeat Order'), {
            flex: 1.0,
            minWidth: 190,
            valueFormatter: params => escHtml(params.value || '-'),
            comparator: (a, b, nodeA, nodeB, isAsc) => {
                const rawA = nodeA.data?.last_order_raw || '';
                const rawB = nodeB.data?.last_order_raw || '';
                return (rawA > rawB ? 1 : rawA < rawB ? -1 : 0) * (isAsc ? 1 : -1);
            }
        }),
        Object.assign(headerWithFilter('count_order', 'Total Order Lifetime'), {
            flex: 0.9,
            minWidth: 170,
            valueFormatter: params => {
                if (params.value == null || params.value === '') {
                    return '-';
                }
                const num = Number(params.value);
                if (Number.isNaN(num)) {
                    return escHtml(params.value);
                }
                return num.toLocaleString('id-ID');
            }
        }),
        Object.assign(headerWithFilter('cb_cl', 'Status Customer'), {
            flex: 0.9,
            minWidth: 140,
            valueFormatter: params => escHtml(params.value || '-')
        }),
        Object.assign(headerWithFilter('keluhan', 'Keluhan'), {
            flex: 1.2,
            minWidth: 180,
            cellRenderer: params => `<div class="text-wrap crm-cell-middle">${escHtml(params.value || '-')}</div>`
        }),
        Object.assign(headerWithFilter('customer_experience', 'Customer Experience'), {
            flex: 1.2,
            minWidth: 190,
            cellRenderer: params => `<div class="text-wrap crm-cell-middle">${escHtml(params.value || '-')}</div>`
        }),
        Object.assign(headerWithFilter('join_komunitas', 'Join Komunitas'), {
            flex: 0.9,
            minWidth: 140,
            valueFormatter: params => escHtml(params.value || '-')
        }),
        Object.assign(headerWithFilter('campaign_broadcast', 'Campaign / Broadcast'), {
            flex: 1.2,
            minWidth: 190,
            cellRenderer: params => {
                const value = String(params.value || '').trim();
                if (!value || value === '-') {
                    return '<div class="crm-cell-middle text-muted">-</div>';
                }
                const url = String(params.data?.campaign_broadcast_url || '').trim();
                if (!url) {
                    return `<div class="crm-cell-middle"><span class="crm-campaign-label">${escHtml(value)}</span></div>`;
                }
                return `<div class="crm-cell-middle"><a class="crm-campaign-label" href="${escHtml(url)}">${escHtml(value)}</a></div>`;
            }
        }),
        Object.assign(headerWithFilter('id_buyer', 'ID'), {
            flex: 1.0,
            minWidth: 150,
            valueFormatter: params => escHtml(params.value || '-')
        }),
        Object.assign(headerWithFilter('no_pesanan', 'No. pesanan'), {
            flex: 1.0,
            minWidth: 190,
            autoHeight: true,
            wrapText: true,
            cellRenderer: params => {
                const raw = params.data?.no_pesanan || '-';
                if (!raw || raw === '-') {
                    return `<div class="text-wrap">-</div>`;
                }
                const lines = String(raw).split('\n');
                const html = lines.map((line, idx) => {
                    const safe = escHtml(line);
                    const border = idx < lines.length - 1 ? 'border-bottom:1px solid #d0d0d0;' : '';
                    return `<div style="${border} padding:2px 0;">${safe}</div>`;
                }).join('');
                return `<div class="text-wrap">${html}</div>`;
            }
        }),
        Object.assign(headerWithFilter('brand', 'Brand'), {
            flex: 0.8,
            minWidth: 100,
            valueFormatter: params => escHtml(params.value || '-')
        }),
        {
            headerName: 'Aksi',
            colId: 'actions',
            width: 110,
            pinned: 'right',
            filter: false,
            sortable: false,
            suppressMenu: true,
            cellRenderer: params => {
                if (!params.data) return '';
                const id = escHtml(String(params.data.id));
                return `
                    <div class="text-end">
                        <a href="#!" class="text-blue me-2 history-trigger" 
                            data-id="${id}" 
                            data-bs-toggle="tooltip" 
                            title="Lihat History Order">
                            <i class="bi bi-clock-history text-icon"></i>
                        </a>
                        <a href="#!" class="me-2 text-blue" onclick="edit('${id}')"><i class="bi bi-pen text-icon"></i></a>
                        <a href="#!" class="text-red" onclick="remove('${id}')"><i class="bi bi-trash text-icon"></i></a>
                    </div>
                `;
            },
        }
    ];

    if (window.crmGridOptions && window.crmGridOptions.api) {
        try { window.crmGridOptions.api.destroy(); } catch (e) {}
    }

    window.crmGridOptions = {
        theme: 'legacy',
        defaultColDef: {
            resizable: true,
            sortable: false,
            filter: false,
            floatingFilter: false
        },
        columnDefs: columnDefs,
        rowData: rowData,
        animateRows: true,
        rowSelection: 'multiple',
        suppressRowClickSelection: true,
        onSelectionChanged: function () {
            if (typeof get_id === 'function') {
                get_id();
            }
        },
        onGridReady: function (params) {
            window.crmGridOptions.api = params.api;
            refreshHeaderActiveStates();
            const loaded = crmLoadColumnState(params.api);
            if (!loaded) {
                params.api.sizeColumnsToFit({ defaultMinWidth: 120 });
            }
            if (typeof get_id === 'function') {
                get_id();
            }
            // Initialize custom scrollbar
            setTimeout(() => {
                if (typeof window.updateCrmScrollbar === 'function') {
                    window.updateCrmScrollbar();
                }
            }, 100);
        },
        onColumnVisible: function () {
            crmSaveColumnState();
            setTimeout(() => {
                if (typeof window.updateCrmScrollbar === 'function') {
                    window.updateCrmScrollbar();
                }
            }, 50);
        },
        onColumnMoved: function () {
            crmSaveColumnState();
        },
        onColumnPinned: function () {
            crmSaveColumnState();
        },
        onColumnResized: function () {
            if (crmColResizeTimer) {
                clearTimeout(crmColResizeTimer);
            }
            crmColResizeTimer = setTimeout(() => {
                crmSaveColumnState();
                if (typeof window.updateCrmScrollbar === 'function') {
                    window.updateCrmScrollbar();
                }
            }, 400);
        }
    };

    const gridElement = document.getElementById('crmGrid');
    if (!gridElement) {
        return;
    }
    const api = agGrid.createGrid(gridElement, window.crmGridOptions);
    window.crmGridOptions.api = api;

    (function initColumnChooser() {
        const btn = document.getElementById('crmBtnColumns');
        if (!btn) {
            return;
        }

        let panel = null;
        let isOpen = false;
        let pendingState = new Map();
        let outsideClickHandler = null;

        function closePanel() {
            if (!isOpen) {
                return;
            }
            isOpen = false;
            if (panel && panel.parentNode) {
                panel.parentNode.removeChild(panel);
            }
            panel = null;
            pendingState = new Map();
            if (outsideClickHandler) {
                document.removeEventListener('click', outsideClickHandler);
                outsideClickHandler = null;
            }
        }

        btn.addEventListener('click', function (event) {
            event.preventDefault();
            if (!window.crmGridOptions || !window.crmGridOptions.api) {
                return;
            }
            if (isOpen) {
                closePanel();
                return;
            }

            const columns = window.crmGridOptions.api.getColumns()
                .filter(col => {
                    const def = col.getColDef();
                    return def && def.headerName && col.getColId() !== 'actions' && col.getColId() !== 'select';
                });

            pendingState = new Map(columns.map(col => [col.getColId(), col.isVisible()]));

            panel = document.createElement('div');
            panel.className = 'dropdown-panel shadow rounded p-2 bg-white border';
            panel.style.position = 'absolute';
            panel.style.width = '260px';
            panel.style.zIndex = 1050;

            const rect = btn.getBoundingClientRect();
            panel.style.top = `${rect.bottom + window.scrollY + 4}px`;
            panel.style.left = `${rect.left + window.scrollX}px`;

            panel.innerHTML = `
                <div class="mb-2 small fw-600">Pilih Kolom</div>
                <div class="mb-2">
                    <label class="d-block small">
                        <input type="checkbox" data-role="toggle-all">
                        Tampilkan semua
                    </label>
                </div>
                <div class="list" style="max-height:260px; overflow:auto;"></div>
                <div class="d-flex justify-content-end gap-2 mt-2">
                    <button type="button" class="btn btn-sm btn-light" data-role="cancel">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" data-role="apply">Terapkan</button>
                </div>
            `;

            const listEl = panel.querySelector('.list');

            const renderList = () => {
                listEl.innerHTML = columns.map(col => {
                    const colId = escHtml(col.getColId());
                    const title = escHtml(col.getColDef().headerName || col.getColId());
                    const checked = pendingState.get(col.getColId()) ? 'checked' : '';
                    return `
                        <label class="d-block mb-1 small">
                            <input type="checkbox" data-col="${colId}" ${checked}>
                            ${title}
                        </label>
                    `;
                }).join('');

                listEl.querySelectorAll('input[data-col]').forEach(cb => {
                    cb.addEventListener('change', (ev) => {
                        const colId = ev.target.getAttribute('data-col');
                        pendingState.set(colId, !!ev.target.checked);
                    });
                });
            };

            renderList();

            const toggleAll = panel.querySelector('input[data-role="toggle-all"]');
            if (toggleAll) {
                toggleAll.checked = !Array.from(pendingState.values()).some(val => !val);
                toggleAll.addEventListener('change', (ev) => {
                    const visible = !!ev.target.checked;
                    columns.forEach(col => pendingState.set(col.getColId(), visible));
                    renderList();
                });
            }

            panel.querySelector('[data-role="cancel"]').addEventListener('click', closePanel);
            panel.querySelector('[data-role="apply"]').addEventListener('click', () => {
                if (!window.crmGridOptions || !window.crmGridOptions.api) {
                    closePanel();
                    return;
                }
                const state = [];
                pendingState.forEach((visible, colId) => {
                    state.push({ colId: colId, hide: !visible });
                });
                window.crmGridOptions.api.applyColumnState({ state, applyOrder: false });
                setTimeout(() => window.crmGridOptions.api.sizeColumnsToFit({ defaultMinWidth: 120 }), 80);
                crmSaveColumnState();
                closePanel();
            });

            outsideClickHandler = function (ev) {
                if (!panel || panel.contains(ev.target) || ev.target === btn) {
                    if (!panel) {
                        document.removeEventListener('click', outsideClickHandler);
                        outsideClickHandler = null;
                    }
                    return;
                }
                closePanel();
            };

            document.addEventListener('click', outsideClickHandler);

            document.body.appendChild(panel);
            isOpen = true;
        });
    })();

    const resetBtn = document.getElementById('crmBtnResetFilters');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => clearAllFilters(true));
    }

    refreshHeaderActiveStates();

    // ==================== CUSTOM HORIZONTAL SCROLLBAR ====================
    (function initCrmScrollbar() {
        const gridWrapper = document.getElementById('crmGridWrapper');
        const scrollbarThumb = document.querySelector('#crmCustomScrollbar .scrollbar-thumb');
        const scrollbarTrack = document.querySelector('#crmCustomScrollbar .scrollbar-track');
        const customScrollbar = document.getElementById('crmCustomScrollbar');
        
        if (!gridWrapper || !scrollbarThumb || !scrollbarTrack || !customScrollbar) {
            return;
        }

        const getGridScrollViewport = () => {
            const viewport = gridWrapper.querySelector('.ag-body-horizontal-scroll-viewport');
            if (!viewport) {
                return gridWrapper.querySelector('.ag-center-cols-viewport');
            }
            return viewport;
        };

        const updateScrollbar = () => {
            const viewport = getGridScrollViewport();
            if (!viewport) return;

            const scrollWidth = viewport.scrollWidth;
            const clientWidth = viewport.clientWidth;

            if (scrollWidth <= clientWidth) {
                customScrollbar.style.display = 'none';
                return;
            }

            customScrollbar.style.display = 'block';

            const thumbWidthPercent = (clientWidth / scrollWidth) * 100;
            scrollbarThumb.style.width = thumbWidthPercent + '%';

            const scrollLeft = viewport.scrollLeft;
            const maxScroll = scrollWidth - clientWidth;
            const scrollPercent = (scrollLeft / maxScroll) * 100;
            const maxThumbLeft = 100 - thumbWidthPercent;
            const thumbLeft = (scrollPercent / 100) * maxThumbLeft;
            
            scrollbarThumb.style.left = thumbLeft + '%';
        };

        const viewport = getGridScrollViewport();
        if (viewport) {
            viewport.addEventListener('scroll', updateScrollbar);
        }

        let isDragging = false;
        let startX = 0;
        let startLeft = 0;

        scrollbarThumb.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX;
            startLeft = parseFloat(scrollbarThumb.style.left) || 0;
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;

            const viewport = getGridScrollViewport();
            if (!viewport) return;

            const trackWidth = scrollbarTrack.offsetWidth;
            const deltaX = e.clientX - startX;
            const deltaPercent = (deltaX / trackWidth) * 100;
            let newLeft = startLeft + deltaPercent;

            const thumbWidthPercent = parseFloat(scrollbarThumb.style.width);
            const maxLeftPercent = 100 - thumbWidthPercent;
            newLeft = Math.max(0, Math.min(newLeft, maxLeftPercent));

            scrollbarThumb.style.left = newLeft + '%';

            const scrollPercent = (newLeft / maxLeftPercent) * 100;
            const scrollWidth = viewport.scrollWidth;
            const clientWidth = viewport.clientWidth;
            const maxScroll = scrollWidth - clientWidth;
            viewport.scrollLeft = (scrollPercent / 100) * maxScroll;
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
        });

        scrollbarTrack.addEventListener('click', (e) => {
            if (e.target === scrollbarThumb) return;

            const viewport = getGridScrollViewport();
            if (!viewport) return;

            const trackRect = scrollbarTrack.getBoundingClientRect();
            const clickX = e.clientX - trackRect.left;
            const trackWidth = trackRect.width;
            const thumbWidth = scrollbarThumb.offsetWidth;

            let newThumbLeft = clickX - (thumbWidth / 2);
            newThumbLeft = Math.max(0, Math.min(newThumbLeft, trackWidth - thumbWidth));

            const newLeftPercent = (newThumbLeft / trackWidth) * 100;
            const thumbWidthPercent = parseFloat(scrollbarThumb.style.width);
            const maxLeftPercent = 100 - thumbWidthPercent;
            const clampedLeft = Math.max(0, Math.min(newLeftPercent, maxLeftPercent));

            scrollbarThumb.style.left = clampedLeft + '%';

            const scrollPercent = (clampedLeft / maxLeftPercent) * 100;
            const scrollWidth = viewport.scrollWidth;
            const clientWidth = viewport.clientWidth;
            const maxScroll = scrollWidth - clientWidth;
            viewport.scrollLeft = (scrollPercent / 100) * maxScroll;
        });

        // Initial update - langsung dan setelah delay
        updateScrollbar();
        setTimeout(updateScrollbar, 50);
        setTimeout(updateScrollbar, 200);
        setTimeout(updateScrollbar, 500);
        
        window.addEventListener('resize', updateScrollbar);
        
        // Expose update function globally untuk diakses dari event handler
        window.updateCrmScrollbar = updateScrollbar;
    })();
})();
$(document).ready(function() {
    $('.history-trigger').each(function() {
        const trigger = this;
        const itemId = $(this).data('id');
        
        tippy(trigger, {
            content: '<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat history order...</div></div>',
            allowHTML: true,
            interactive: true,
            placement: 'right',
            theme: 'light',
            maxWidth: 500,
            appendTo: () => document.body,
            zIndex: 2100,
            onShow(instance) {
                $.ajax({
                    url: '<?= base_url() ?>crm/get_order_history',
                    type: 'POST',
                    data: { 
                        id: itemId,
                        brand: '<?= isset($_GET['brand']) ? crm_escape($_GET['brand']) : "" ?>'
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.success) {
                            let historyContent = '';
                            
                            if (data.history && data.history.length > 0) {
                                data.history.forEach(function(history) {
                                    historyContent += `
                                        <div class="border rounded p-2 mb-2">
                                            <p class="mb-1 fw-600">
                                                ${history.order_url ? 
                                                    `<a target="_blank" href="${history.order_url}">${history.order_id}</a>` : 
                                                    history.order_id
                                                }
                                            </p>
                                            <p class="mb-1 small text-muted">Tanggal: ${history.date}</p>
                                            <p class="mb-1 small">Status: ${history.order_status}</p>`;
                                    
                                    // PERBAIKAN: Handle history.json dengan parsing yang benar
                                    if (history.json) {
                                        try {
                                            // Parse JSON jika masih string
                                            const items = typeof history.json === 'string' ? JSON.parse(history.json) : history.json;
                                            console.log('Parsed order items:', items);
                                            
                                            if (items && Object.keys(items).length > 0) {
                                                Object.values(items).forEach(function(item) {
                                                    // Tambahkan pengecekan untuk memastikan item ada
                                                    if (item && item.qty && item.product_text) {
                                                        historyContent += `<p class="mb-1 small">${item.qty} x ${item.product_text}</p>`;
                                                    }
                                                });
                                            } else {
                                                historyContent += `<p class="mb-1 small text-muted">Tidak ada item</p>`;
                                            }
                                        } catch (error) {
                                            console.error('Error parsing JSON:', error, history.json);
                                            historyContent += `<p class="mb-1 small text-danger">Error loading items</p>`;
                                        }
                                    } else {
                                        historyContent += `<p class="mb-1 small text-muted">Tidak ada item</p>`;
                                    }
                                    
                                    historyContent += `</div>`;
                                });
                            } else {
                                historyContent = '<p class="text-danger mb-0 text-center">Belum ada history order!</p>';
                            }
                            
                            const tooltipContent = `
                                <div class="history-tooltip p-2" style="min-width: 300px; max-height: 400px; overflow-y: auto;">
                                    <h6 class="fw-bold mb-3 text-center">History Order</h6>
                                    ${historyContent}
                                </div>
                            `;
                            instance.setContent(tooltipContent);
                        } else {
                            instance.setContent(`
                                <div class="p-2 text-danger text-center">
                                    Gagal memuat history order
                                </div>
                            `);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        instance.setContent(`
                            <div class="p-2 text-danger text-center">
                                Gagal memuat history order
                                <div class="small mt-2">${error}</div>
                            </div>
                        `);
                    }
                });
            }
        });
    });
});
</script>
