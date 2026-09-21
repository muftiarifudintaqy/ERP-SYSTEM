<?php
if (!function_exists('escv')) {
    function escv($v) {
        if (is_array($v)) {
            return implode(',', array_map('html_escape', $v));
        }
        return html_escape((string)$v);
    }
}

/* Kumpulkan semua product id dari JSON */
$allProductIds = [];
foreach ($data as $v) {
    if (empty($v['json'])) continue;
    $obj = json_decode($v['json'], true);
    if (!is_array($obj)) continue;
    foreach ($obj as $it) {
        if (!empty($it['product'])) {
            $allProductIds[] = (int)$it['product'];
        }
    }
}
$allProductIds = array_values(array_unique(array_filter($allProductIds)));

/* Prefetch price_buy untuk semua product id */
$priceBuyMap = [];
$showRefreshPending = (isset($_GET['order_status']) && strtoupper((string)$_GET['order_status']) === 'READY_TO_SHIP');
if (!empty($allProductIds)) {
    $in = implode(',', $allProductIds);
    $rowsPB = $this->mymodel->selectWithQuery("
        SELECT id, price_buy
        FROM product
        WHERE id IN ($in)
    ");
    foreach ($rowsPB as $r) {
        $priceBuyMap[(int)$r['id']] = (float)$r['price_buy'];
    }
}
?>

<!-- Toolbar -->
<div id="tableToolbar" class="mb-2 d-flex align-items-center gap-2">
    <button id="btnColumns" type="button" class="btn btn-sm btn-outline-primary"
        style="padding: 0px 18px !important; font-size: 13px !important; line-height: 1.2 !important; height: 36px !important;">
        <i class="bi bi-layout-three-columns me-1"></i>
        Tampilkan Kolom
    </button>
    <button id="btnResetFilters" type="button" class="btn btn-sm btn-outline-danger"
        style="padding: 0px 18px !important; font-size: 13px !important; line-height: 1.2 !important; height: 36px !important;">
        <i class="bi bi-x-circle me-1"></i>
        Reset Filter
    </button>
    <?php if ($showRefreshPending): ?>
        <button id="btnRefreshPending" name="btnRefreshPending" type="button" class="btn btn-sm btn-outline-success"
            onclick="window.refreshPending(this)"
            style="padding: 0px 18px !important; font-size: 13px !important; line-height: 1.2 !important; height: 36px !important;">
            <i class="bi bi-arrow-repeat me-1"></i>
            Refresh Pending
        </button>
    <?php endif; ?>
</div>

<?php if (!empty($filters) && is_array($filters)): ?>
    <div class="mb-2 small text-muted">
        <strong>Filter aktif:</strong>
        <?php foreach ($filters as $f): ?>
            <span class="badge bg-light text-dark border">
                <?= escv($f['field']) ?> <?= escv($f['op'] ?? 'equals') ?> "<?= escv($f['value']) ?>"
            </span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div id="ui-portal"></div>

<div id="agGridWrapper" class="ag-theme-quartz">
    <div id="myGrid" style="width:100%; height:100%;"></div>
    <div id="filter-portal"></div>
</div>

<style>
    .dropdown-panel {
        max-width: 320px;
        z-index: 1050;
    }

    .btn-xs {
        font-size: .8rem;
        line-height: 1;
    }

    .hdr-filter .btn-filter {
        background: none;
        border: none;
        margin-left: .5rem;
        cursor: pointer;
    }

    .hdr-dd-portal {
        position: absolute;
        z-index: 1050;
        width: 260px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: .5rem;
        padding: .5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
    }

    .hdr-dd-portal .actions {
        display: flex;
        gap: .5rem;
        justify-content: flex-end;
        margin-top: .5rem;
    }

    .btn-2xs {
        font-size: .75rem;
        padding: .15rem .4rem;
        border: 1px solid #ddd;
        border-radius: .35rem;
        background: #f8f9fa;
    }

    .badge.bg-blue {
        background: #0d6efd !important;
    }

    .badge.bg-green {
        background: #198754 !important;
    }

    .badge.bg-red {
        background: #dc3545 !important;
    }

    .badge.bg-grey {
        background: #6c757d !important;
    }

    .copy-btn {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: 2px 6px;
        font-size: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: #fff;
        cursor: pointer;
    }

    .copy-btn:hover {
        background: #f8fafc;
    }

    .action-menu-portal {
        position: fixed;
        z-index: 3000;
        display: block;
        min-width: 190px;
        max-height: min(360px, calc(100vh - 16px));
        overflow: auto;
    }

    .transaction-action-trigger {
        border: 0;
        background: transparent;
        padding: 2px 6px;
        line-height: 1;
    }

  /* Order ID cell: text klik + ikon copy selalu di kanan */
  .order-cell {
    position: relative;
    display: block;
    padding-right: 16px; /* ruang untuk ikon di kanan */
  }

  /* Teks order sebagai tombol ringan */
  .order-link {
    display: inline-block;
    padding: 2px 8px;
    font-size: 12px;
    line-height: 1.4;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
    white-space: nowrap;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Ikon copy menempel di kanan, selalu di posisi yang sama */
  .order-copy {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    opacity: .7;
  }

  .order-copy:hover {
    opacity: 1;
  }

  /* Tambahkan CSS untuk loading spinner */
  .sort-spinner {
    animation: spin 1s linear infinite;
    display: inline-block !important;
    margin-left: 4px;
    color: #0d6efd;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  .hdr-title.sort-loading {
    color: #0d6efd;
    font-weight: 600;
  }

  .ag-header-cell-sorted .sort-spinner {
    color: #0d6efd;
  }

  /* Pastikan icon terlihat */
  .hdr-filter .bi {
    display: inline-block !important;
    margin-left: 4px;
    font-size: 12px;
  }

  .return-product-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 4px;
    line-height: 1.3;
  }

  .return-product-tags .badge {
    white-space: normal;
    text-align: left;
  }

</style>


<script>
(function(){
    // ===========================
    // Helpers
    // ===========================

    window.syncSelectedIds = function () {
        const rows = gridOptions.api.getSelectedRows() || [];
        const ids = rows.map(function (r) { return String(r.id); });
        document.getElementById('id_selected')?.setAttribute('value', ids.join(','));
        window.list_id_v2 = ids.join(',');

        const ready = rows.filter(function (r) {
            return String((r.order_status || '')).toUpperCase() === 'READY_TO_SHIP' || String((r.order_status || '')).toUpperCase() === 'PENDING';
        }).length;
        const processed = rows.filter(function (r) {
            return String((r.order_status || '')).toUpperCase() === 'PROCESSED';
        }).length;
        window.shippingActionState = { total: rows.length, ready: ready, processed: processed };
        if (typeof applyShippingActionBar === 'function') {
            applyShippingActionBar();
        }
        if (typeof updateSelectedCounter === 'function') {
            updateSelectedCounter(rows.length);
        }
    }

    function keepActionsOnRight() {
        if (!gridOptions?.api) return;
        gridOptions.api.applyColumnState({
            state: [{ colId: 'actions', pinned: 'right' }],
            applyOrder: false
        });
    }

    // ===========================
    // Fungsi Global untuk Order ID
    // ===========================
    window.onOrderClick = function (orderId) {
        const url = '<?= base_url() ?>transaction?ids=&order_type=&pencairan=&keyword_category=Order+ID&order_status=&view=&c_type=&keyword=' + encodeURIComponent(orderId);
        window.open(url, '_blank');
    };

    window.copyToClipboard = async function (text) {
        try {
            await navigator.clipboard.writeText(text);
            Swal.fire({
                icon: 'success',
                title: 'Tersalin!',
                text: 'Order ID berhasil disalin: ' + text,
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                width: 400
            });
        } catch (e) {
            console.warn('Clipboard API gagal, fallback textarea.', e);
            const ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);

            Swal.fire({
                icon: 'warning',
                title: 'Gagal!',
                text: 'Order ID gagal disalin: ' + text,
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                width: 400
            });
        }
    };

    function formatRupiah(n) {
        if (n == null) return '-';
        try {
            return (+n).toLocaleString('id-ID');
        } catch {
            return 'Rp ' + n;
        }
    }

    function escHtml(s) {
        return String(s).replace(/[&<>\"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    function badge(cls, text) {
        if (!text) text = '-';
        return '<span class="badge ' + cls + '">' + text + '</span>';
    }

    function closeTransactionActionMenu() {
        if (window.__transactionActionMenuEl?.parentNode) {
            window.__transactionActionMenuEl.parentNode.removeChild(window.__transactionActionMenuEl);
        }
        window.__transactionActionMenuEl = null;
        window.__transactionActionMenuTrigger = null;
    }

    function positionTransactionActionMenu(menu, trigger) {
        const rect = trigger.getBoundingClientRect();
        const margin = 8;
        const width = menu.offsetWidth || 190;
        const height = menu.offsetHeight || 260;

        let left = rect.right - width;
        let top = rect.bottom + 4;

        if (top + height > window.innerHeight - margin) {
            top = rect.top - height - 4;
        }

        left = Math.max(margin, Math.min(left, window.innerWidth - width - margin));
        top = Math.max(margin, Math.min(top, window.innerHeight - height - margin));

        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
    }

    window.openTransactionActionMenu = function(trigger) {
        if (!trigger) return;
        if (window.__transactionActionMenuTrigger === trigger && window.__transactionActionMenuEl) {
            closeTransactionActionMenu();
            return;
        }

        closeTransactionActionMenu();

        const id = trigger.dataset.id || '';
        const orderId = trigger.dataset.orderId || '';
        const marketplace = trigger.dataset.marketplace || '';
        const awbNumber = trigger.dataset.awbNumber || '';
        const isManual = trigger.dataset.isManual === '1';
        const base = `<?= base_url() ?>transaction`;
        const printUrl = `${base}/print?id=${encodeURIComponent(id)}`;
        const trackUrl = `${base}/tracking?id=${encodeURIComponent(id)}&order_id=${encodeURIComponent(orderId)}&package_number=${encodeURIComponent(awbNumber)}&marketplace=${encodeURIComponent(marketplace)}`;
        const editUrl = `${base}/edit?id=${encodeURIComponent(id)}`;

        const menu = document.createElement('ul');
        menu.className = 'dropdown-menu dropdown-menu-end show action-menu-portal';
        menu.innerHTML = `
            <li><a href="${escHtml(printUrl)}" target="_blank" class="dropdown-item"><i class="bi bi-printer me-2"></i> Print</a></li>
            <li><a href="#!" class="dropdown-item" data-menu-action="set_cs"><i class="bi bi-people me-2"></i> CS</a></li>
            <li><a href="#!" class="dropdown-item" data-menu-action="set_resi"><i class="bi bi-truck me-2"></i> No Resi</a></li>
            <li><a href="#!" class="dropdown-item" data-menu-action="set_return"><i class="bi bi-backspace me-2"></i> Return</a></li>
            ${isManual ? '<li><a href="#!" class="dropdown-item text-danger" data-menu-action="remove"><i class="bi bi-trash me-2"></i> Hapus Order</a></li>' : ''}
            ${!isManual ? '<li><a href="#!" class="dropdown-item" data-menu-action="refresh"><i class="bi bi-newspaper me-2"></i> Refresh</a></li>' : ''}
            <li><a href="${escHtml(trackUrl)}" target="_blank" class="dropdown-item"><i class="bi bi-truck me-2"></i> Lacak Resi</a></li>
            ${isManual ? `<li><a href="${escHtml(editUrl)}" class="dropdown-item"><i class="bi bi-pencil-square me-2"></i> Edit Order</a></li>` : ''}
        `;

        menu.addEventListener('click', function(ev) {
            const item = ev.target.closest('[data-menu-action]');
            if (!item) {
                closeTransactionActionMenu();
                return;
            }

            ev.preventDefault();
            const action = item.dataset.menuAction;
            closeTransactionActionMenu();

            if (action === 'set_cs') set_cs(id);
            if (action === 'set_resi') set_resi(id);
            if (action === 'set_return') set_return(id);
            if (action === 'remove') remove(id);
            if (action === 'refresh') refresh(orderId, marketplace);
        });

        (document.getElementById('ui-portal') || document.body).appendChild(menu);
        window.__transactionActionMenuEl = menu;
        window.__transactionActionMenuTrigger = trigger;
        positionTransactionActionMenu(menu, trigger);
    };

    if (!window.__transactionActionMenuBound) {
        window.__transactionActionMenuBound = true;
        document.addEventListener('click', function(ev) {
            const trigger = ev.target.closest('.transaction-action-trigger');
            if (trigger) {
                ev.preventDefault();
                ev.stopPropagation();
                window.openTransactionActionMenu(trigger);
                return;
            }

            if (window.__transactionActionMenuEl && !ev.target.closest('.action-menu-portal')) {
                closeTransactionActionMenu();
            }
        });

        window.addEventListener('resize', closeTransactionActionMenu);
        window.addEventListener('scroll', closeTransactionActionMenu, true);
    }

    function dateFormat(dateStr) {
        if (!dateStr || dateStr === '-') return null;
        const d = new Date(dateStr);
        if (isNaN(d)) return null;
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function parseProdukLineSingle(jsonStr) {
        if (!jsonStr) return '';
        try {
            const obj = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
            if (!obj || typeof obj !== 'object') return '';
            const parts = [];
            for (const k of Object.keys(obj)) {
                const v2 = obj[k] || {};
                const qty = v2.qty ?? 0;
                // product_text tidak pernah ada di data Shopee maupun
                // TikTok; yang terisi model_name dan item_name. Karena
                // itu kolom Produk selalu kosong. Nama varian dipakai
                // lebih dulu karena itu yang membedakan barangnya.
                const productText = String(
                    v2.model_name || v2.item_name || v2.model_sku
                    || v2.product_text || ''
                ).replace(/amp;/g, '').trim();
                if (qty > 0 && productText) {
                    parts.push(qty + 'x ' + productText);
                }
            }
            return parts.join(' + ');
        } catch {
            return '';
        }
    }

    function formatReturnQty(qty) {
        const number = Number(qty || 0);
        if (!Number.isFinite(number)) return '0';
        return number.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    function renderReturnProductBadges(data) {
        if (!data) return '';

        const items = Array.isArray(data.return_condition_items) ? data.return_condition_items : [];
        const badges = [];

        const addBadge = (cls, label, qty, productName) => {
            const qtyNumber = Number(qty || 0);
            if (!(qtyNumber > 0)) return;

            const product = String(productName || '').replace(/amp;/g, '').trim();
            const text = label + ' ' + formatReturnQty(qtyNumber) + 'x' + (product ? ' ' + product : '');
            badges.push(badge(cls, escHtml(text)));
        };

        items.forEach(item => {
            const productName = String(item?.product_text || item?.sku || 'Produk').trim();
            addBadge('bg-green', 'Good', item?.return_good_qty, productName);
            addBadge('bg-red', 'Bad', item?.return_bad_qty, productName);
        });

        if (badges.length === 0) {
            addBadge('bg-green', 'Good', data.return_good_qty, '');
            addBadge('bg-red', 'Bad', data.return_bad_qty, '');
        }

        return badges.length ? `<div class="return-product-tags">${badges.join('')}</div>` : '';
    }

    // ===========================
    // FOOTER TOTALS
    // ===========================
    let __lastTotals = null;
    const __initialTotals = <?= isset($totals) ? json_encode($totals, JSON_UNESCAPED_UNICODE) : 'null' ?>;
    if (__initialTotals) { __lastTotals = __initialTotals; }

    function formatRupiah(n) {
        if (n == null) return '-';
        try { return (+n).toLocaleString('id-ID'); } catch { return 'Rp ' + n; }
    }

    function buildFooterRowFromTotals(totals) {
        const cols = gridOptions.api.getColumnDefs();
        const displayed = gridOptions.api.getColumns().filter(c => c.isVisible());

        const row = {};
        if (displayed.length) {
            const firstColId = displayed[0].getColId();
            row[firstColId] = 'TOTAL';
        }

        const numericCols = new Set([
            'pesanan_count','customer_price','dana_pencairan','omset_kotor',
            'diskon_penjual','biaya_lainnya','omset_bersih','marketplace_fee',
            'komisi_afiliasi','hpp'
        ]);

        (gridOptions.api.getColumns() || []).forEach(col => {
            const id = col.getColId();
            if (!col.isVisible()) return;
            if (numericCols.has(id)) {
                const val = totals?.[id] ?? 0;
                row[id] = (id === 'pesanan_count') ? val : formatRupiah(val);
            } else {
                if (row[id] == null) row[id] = '';
            }
        });

        return row;
    }

    function renderFooterTotals() {
        if (!window.gridOptions?.api) return;

        if (!__lastTotals) {
            gridOptions.api.setGridOption('pinnedBottomRowData', []);
            return;
        }

        const row = buildFooterRowFromTotals(__lastTotals);
        gridOptions.api.setGridOption('pinnedBottomRowData', [row]);
    }

    function delAll(params, base) {
        params.delete(base);
        params.delete(base + '[]');
    }
    function getAllEither(params, base) {
        const a = params.getAll(base);
        if (a && a.length) return a;
        return params.getAll(base + '[]');
    }
    function appendArr(params, base, value) {
        params.append(base + '[]', value);
    }

    function badge(cls, text) { if (!text) text = '-'; return `<span class="${cls} badge">${text}</span>`; }
    function escHtml(s) {
        return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    }

    function operatorForField(field) {
        const exactFields = new Set([
            'order_status','payment_status','shipping_status','return_status',
            'marketplace','shop_name','brand','cs','is_manual','c_type',
            'pencairan_status','payment_type','return_condition','siap_cetak'
        ]);
        return exactFields.has(field) ? 'equals' : 'contains';
    }

    function buildParamsWithFilters(baseParams = new URLSearchParams(window.location.search)) {
        const params = new URLSearchParams(baseParams.toString());
        delAll(params,'filter_field'); delAll(params,'filter_value'); delAll(params,'filter_operator');
        Object.entries(valueFilters).forEach(([field, values]) => {
            if (values instanceof Set && values.size > 0) {
                for (const v of values) {
                    appendArr(params,'filter_field',field);
                    appendArr(params,'filter_value',v);
                    appendArr(params,'filter_operator', operatorForField(field));
                }
            }
        });
        params.set('page','1');
        return params;
    }

    async function fetchGridDataWithParams(params) {
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.pushState({}, '', newUrl);
        // Reset distinct value cache so filter dropdowns reflect the new dataset
        Object.keys(distinctCache).forEach(key => delete distinctCache[key]);
        const url = `<?= base_url() ?>transaction/item?${params.toString()}`;
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            if (!json.ok) throw new Error(json.error || 'Fetch failed');
            if (window.gridOptions?.api) {
                // Pastikan selection lama tidak ikut terbawa ke dataset baru.
                window.gridOptions.api.deselectAll();
                window.gridOptions.api.setGridOption('rowData', []);
                window.gridOptions.api.setGridOption('rowData', json.rows || []);
                window.gridOptions.api.deselectAll();
                window.list_id_v2 = '';
                const hiddenSelected = document.getElementById('id_selected');
                if (hiddenSelected) {
                    hiddenSelected.value = '';
                    hiddenSelected.setAttribute('value', '');
                }
                window.shippingActionState = { total: 0, ready: 0, processed: 0 };
                if (typeof applyShippingActionBar === 'function') {
                    applyShippingActionBar();
                }
                if (typeof updateSelectedCounter === 'function') {
                    updateSelectedCounter(0);
                }
                if (typeof window.syncSelectedIds === 'function') {
                    window.syncSelectedIds();
                }
                __lastTotals = json.totals || null;
                renderFooterTotals();
            }
        } catch (err) { console.error('fetchGridDataWithParams error:', err); }
    }

    function dateFormat(dateStr) {
        if (!dateStr || dateStr === '-') return null;
        const d = new Date(dateStr); if (isNaN(d)) return null;
        const y=d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), day=String(d.getDate()).padStart(2,'0');
        return `${y}-${m}-${day}`;
    }
    function parseProdukLineSingle(jsonStr) {
        if (!jsonStr) return '';
        try {
            const obj = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
            if (!obj || typeof obj !== 'object') return '';
            const parts = [];
            for (const k of Object.keys(obj)) {
                const v2 = obj[k] || {};
                const qty = v2.qty ?? 0;
                const productText = String(v2.product_text ?? '').replace(/amp;/g, '');
                if (qty > 0 && productText) {
                    parts.push(`${qty}x ${productText}`);
                }
            }
            return parts.join(' + ');
        } catch {
            return '';
        }
    }

    // ===========================
    // SERVER SORTING
    // ===========================
    const SERVER_SORTABLE = new Set([
        'id', 'date', 'order_id', 'customer_text', 'phone', 'awb_number', 
        'order_status', 'payment_status', 'pesanan_count', 'customer_price', 
        'hpp', 'marketplace', 'shop_name', 'brand', 'payment_type', 'c_username',
        'cs', 'shipping', 'reverse_id', 'return_status', 'pay_at', 'dana_pencairan',
        'omset_kotor', 'diskon_penjual', 'biaya_lainnya', 'omset_bersih',
        'marketplace_fee', 'komisi_afiliasi', 'c_type', 'rts_at', 'print_at', 'pesanan_sku', 'siap_cetak'
    ]);

    const loadingSortColumns = new Set();
    let isSortingInProgress = false;

    function showSortLoading(colId, show) {
        const headerElement = document.querySelector(`[col-id="${colId}"] .hdr-title`);
        if (!headerElement) return;

        const sortIcon = headerElement.parentElement.querySelector('.bi');
        if (!sortIcon) return;

        if (show) {
            loadingSortColumns.add(colId);
            headerElement.classList.add('sort-loading');
            sortIcon.className = 'sort-spinner bi bi-arrow-repeat';
        } else {
            loadingSortColumns.delete(colId);
            headerElement.classList.remove('sort-loading');

            const urlParams = new URLSearchParams(window.location.search);
            const currentSortCol = urlParams.get('sort_column');
            const currentSortOrder = urlParams.get('sort_order');

            if (currentSortCol === colId) {
                sortIcon.className = currentSortOrder === 'ASC' ? 'bi bi-arrow-up' : 
                                    currentSortOrder === 'DESC' ? 'bi bi-arrow-down' : 
                                    'bi bi-arrow-down-up';
            } else {
                sortIcon.className = 'bi bi-arrow-down-up';
            }
        }
    }

    function reloadWithSort(colId, order) {
        if (isSortingInProgress) {
            console.log('Sorting already in progress, skipping...');
            return;
        }

        const params = new URLSearchParams(window.location.search);

        params.delete('sort_column');
        params.delete('sort_order');

        if (colId && SERVER_SORTABLE.has(colId) && order) {
            params.set('sort_column', colId);
            params.set('sort_order', order.toUpperCase());
        }

        params.set('page','1');

        if (colId) {
            showSortLoading(colId, true);
        }

        isSortingInProgress = true;
        const p = buildParamsWithFilters(params);

        fetchGridDataWithParams(p).finally(() => {
            if (colId) {
                showSortLoading(colId, false);
            }
            isSortingInProgress = false;
        });
    }

    function handleInitialSortState() {
        const urlParams = new URLSearchParams(window.location.search);
        const col = urlParams.get('sort_column');
        const order = (urlParams.get('sort_order') || '').toLowerCase();

        if (!col || !window.gridOptions?.api) return;

        const headerElement = document.querySelector(`[col-id="${col}"] .hdr-title`);
        if (headerElement) {
            const sortIcon = headerElement.parentElement.querySelector('.bi');
            if (sortIcon) {
                sortIcon.className = order === 'asc' ? 'bi bi-arrow-up' : 
                                    order === 'desc' ? 'bi bi-arrow-down' : 
                                    'bi bi-arrow-down-up';
            }
        }
    }

    // ===========================
    // Column State: save/restore to sessionStorage
    // ===========================
    const COLSTATE_KEY = `agGrid:columns:${location.pathname}`;
    const RETUR_PRODUCT_DEFAULT_HIDDEN_KEY = `${COLSTATE_KEY}:retur_product_default_hidden_applied`;
    const SIAP_CETAK_DEFAULT_HIDDEN_KEY = `${COLSTATE_KEY}:siap_cetak_default_hidden_applied`;

    function _getCurrentColumnState(api) {
        return (api.getColumnState() || []).map(s => ({
            colId: s.colId,
            hide: !!s.hide,
            width: s.width,
            pinned: s.pinned || null,
            sort: s.sort || null,
            sortIndex: (typeof s.sortIndex === 'number') ? s.sortIndex : null
        }));
    }

    function saveColumnStateToSession() {
        try {
            if (!window.gridOptions?.api) return;
            const state = _getCurrentColumnState(window.gridOptions.api);
            sessionStorage.setItem(COLSTATE_KEY, JSON.stringify(state));
        } catch (e) {
            console.warn('saveColumnStateToSession failed:', e);
        }
    }

    function loadColumnStateFromSession() {
        try {
            if (!window.gridOptions?.api) return false;
            const txt = sessionStorage.getItem(COLSTATE_KEY);
            if (!txt) {
                sessionStorage.setItem(RETUR_PRODUCT_DEFAULT_HIDDEN_KEY, '1');
                sessionStorage.setItem(SIAP_CETAK_DEFAULT_HIDDEN_KEY, '1');
                return false;
            }
            let state = JSON.parse(txt);
            if (!Array.isArray(state) || state.length === 0) return false;
            if (!sessionStorage.getItem(SIAP_CETAK_DEFAULT_HIDDEN_KEY)) {
                const siapCetakState = state.find(s => s.colId === 'siap_cetak');
                if (siapCetakState) {
                    siapCetakState.hide = true;
                } else {
                    state.push({ colId: 'siap_cetak', hide: true });
                }
                sessionStorage.setItem(SIAP_CETAK_DEFAULT_HIDDEN_KEY, '1');
            }
            window.gridOptions.api.applyColumnState({ state, applyOrder: true });
            return true;
        } catch (e) {
            console.warn('loadColumnStateFromSession failed:', e);
            return false;
        }
    }

    function clearSavedColumnState() {
        try { sessionStorage.removeItem(COLSTATE_KEY); } catch {}
    }

    // ===========================
    // Data dari PHP
    // ===========================
    const rowData = <?php
        $rows = [];
        foreach ($data as $v) {
            $fmt = function($n){ return is_numeric($n) ? number_format($n,0,'','.') : ($n ?? ''); };

            $marketplaceRow = $this->mymodel->selectWithQuery("SELECT img FROM marketplace WHERE name = '".($v['marketplace']??'')."'");
            $marketplaceImg = !empty($marketplaceRow[0]['img']) ? (base_url().'/assets/img/marketplace/'.$marketplaceRow[0]['img']) : (base_url().'/assets/img/marketplace/default.png');

            $shippingRow = $this->mymodel->selectWithQuery("SELECT img FROM shipping WHERE name = '".($v['shipping']??'')."'");
            $shippingImg = !empty($shippingRow[0]['img']) ? (base_url().'/assets/img/shipping/'.$shippingRow[0]['img']) : (base_url().'/assets/img/shipping/default.png');

            $date_text = $v['date'] ? date('Y-m-d H:i:s', strtotime($v['date'])) : null;
            $customer_price_raw = (float)($v['customer_price'] ?? 0);
            $order_status_raw = trim((string)($v['order_status'] ?? ''));
            $siap_cetak = ($order_status_raw === 'PROCESSED') ? 'Siap Cetak' : 'Belum Siap';

            $hppCalc = 0.0;
            if (!empty($v['json'])) {
                $obj = json_decode($v['json'], true);
                if (is_array($obj)) {
                    foreach ($obj as $it) {
                        $pid = isset($it['product']) ? (int)$it['product'] : 0;
                        $qty = (float)($it['qty'] ?? 0);
                        $priceBuy = $priceBuyMap[$pid] ?? 0.0;
                        $hppCalc += ($qty * $priceBuy);
                    }
                }
            }
            $hppFinal = $hppCalc;

            $rows[] = [
                'id'=>(int)$v['id'],
                'order_id'=>$v['order_id'],
                'date_raw'=>$v['date'],
                'date_text'=>$date_text,
                'customer_id'=>(int)$v['customer'],
                // Shopee menyembunyikan nama pembeli dan mengirimnya
                // sebagai bintang. Username akun dipakai sebagai
                // pengganti supaya barisnya tetap bisa dibedakan.
                'customer_text'=> (preg_match('/^\*+$/', trim((string)$v['customer_text'])) || $v['customer_text'] === '')
                                  ? ($v['c_username'] ?: '-')
                                  : $v['customer_text'],
                'pesanan_count'=>(int)($v['pesanan_count'] ?? 0),
                'customer_price'=>$customer_price_raw,
                'customer_price_fmt'=>$fmt($customer_price_raw),
                'pencairan_status'=>$v['pencairan_status'] ?: '-',
                'pencairan_at'=>$v['pencairan_at'] ?: null,
                'order_status'=>$v['order_status'] ?: '-',
                'siap_cetak'=>$siap_cetak,
                'reverse_status'=>$v['reverse_status'] ?: '',
                'awb_number'=>$v['awb_number'] ?: '-',
                'marketplace'=>$v['marketplace'] ?: '-',
                'shop_name'=>$v['shop_name'] ?: 'Manual',
                'is_manual'=>(int)($v['is_manual'] ?? 0),
                'brand'=>$v['brand'] ?: '',
                'rts_at'=>$v['rts_at'] ?: '-',
                'phone'=>$v['phone'] ?: '-',
                'payment_type'=>$v['payment_type'] ?? '-',
                'c_username'=>!empty($v['c_username']) ? $v['c_username'] : '-',
                'cs'=>!empty($v['cs']) ? $v['cs'] : '-',
                'shipping'=>!empty($v['shipping']) ? $v['shipping'] : '-',
                'shipping_status'=>'-',
                'reverse_id'=>!empty($v['reverse_id']) ? $v['reverse_id'] : '',
                'return_status'=>!empty($v['return_status']) ? $v['return_status'] : '',
                'payment_status'=>!empty($v['payment_status']) ? $v['payment_status'] : '-',
                'pay_at'=>!empty($v['pay_at']) ? date('Y-m-d H:i:s', strtotime($v['pay_at'])) : '-',
                'dana_pencairan'=>(float)($v['dana_pencairan'] ?? 0),
                'dana_pencairan_fmt'=>$fmt($v['dana_pencairan'] ?? 0),
                'omset_kotor'=>(float)($v['omset_kotor'] ?? 0),
                'omset_kotor_fmt'=>$fmt($v['omset_kotor'] ?? 0),
                'diskon_penjual'=>(float)($v['diskon_penjual'] ?? 0),
                'diskon_penjual_fmt'=>$fmt($v['diskon_penjual'] ?? 0),
                'biaya_lainnya'=>(float)($v['biaya_lainnya'] ?? 0),
                'biaya_lainnya_fmt'=>$fmt($v['biaya_lainnya'] ?? 0),
                'omset_bersih'=>(float)($v['omset_bersih'] ?? 0),
                'omset_bersih_fmt'=>$fmt($v['omset_bersih'] ?? 0),
                'marketplace_fee'=>(float)($v['marketplace_fee'] ?? 0),
                'marketplace_fee_fmt'=>$fmt($v['marketplace_fee'] ?? 0),
                'komisi_afiliasi'=>(float)($v['komisi_afiliasi'] ?? 0),
                'komisi_afiliasi_fmt'=>$fmt($v['komisi_afiliasi'] ?? 0),
                'marketplace_img'=>$marketplaceImg,
                'shipping_img'=>$shippingImg,
                'json'=>$v['json'] ?? '',
                'pesanan_sku'=>$v['pesanan_sku'] ?? '',
                'pesanan_produk'=>$v['pesanan_produk'] ?? '',
                'pesanan_img'=>$v['pesanan_img'] ?? '',
                'pesanan_produk_full'=>$v['pesanan_produk_full'] ?? '',
                'is_for_booking'=>(int)($v['is_for_booking'] ?? 0),
                'hpp'=>$hppFinal,
                'hpp_fmt'=>$fmt($hppFinal),
                'c_type'=>$v['c_type'],
                'print_at'=>$v['print_at'] ?? '',
                'return_good_qty'=>(float)($v['return_good_qty'] ?? 0),
                'return_bad_qty'=>(float)($v['return_bad_qty'] ?? 0),
                'return_condition'=>$v['return_condition'] ?? '',
                'return_condition_items'=>$v['return_condition_items'] ?? [],
                'package_id'=>$v['package_id'] ?? 0
            ];
        }
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    ?>;

    // ===========================
    // External Filter State - SERVER SIDE
    // ===========================
    const valueFilters = Object.create(null);
    const filterableFields = [
        'order_id','customer_text','pesanan_count','customer_price',
        'pencairan_status','order_status','awb_number','marketplace',
        'shop_name','brand','phone','is_manual','payment_type',
        'c_username','cs','shipping','shipping_status','reverse_id',
        'return_status','payment_status','pay_at','dana_pencairan',
        'omset_kotor','diskon_penjual','biaya_lainnya','omset_bersih',
        'marketplace_fee','komisi_afiliasi','marketplace_img','shipping_img', 'c_type','hpp', 'rts_at', 'print_at', 'pesanan_sku',
        'return_condition','siap_cetak'
    ];
    const distinctCache = Object.create(null);

    function applyServerSideFilters() {
        const params = new URLSearchParams(window.location.search);
        delAll(params,'filter_field'); delAll(params,'filter_value'); delAll(params,'filter_operator');
        Object.entries(valueFilters).forEach(([field, values]) => {
            if (values instanceof Set && values.size > 0) {
                Array.from(values).forEach(value => {
                    appendArr(params,'filter_field',field);
                    appendArr(params,'filter_value',value);
                    appendArr(params,'filter_operator', operatorForField(field));
                });
            }
        });
        params.set('page','1');
        const p = buildParamsWithFilters(params);
        fetchGridDataWithParams(p);
    }

    function clearAllFilters() {
        filterableFields.forEach(field => { if (valueFilters[field]) valueFilters[field].clear(); });
        const paramsNow = new URLSearchParams(window.location.search);
        delAll(paramsNow,'filter_field'); delAll(paramsNow,'filter_value'); delAll(paramsNow,'filter_operator');
        paramsNow.set('page','1');
        sessionStorage.removeItem('agGridFilters');
        const p = buildParamsWithFilters(paramsNow);
        fetchGridDataWithParams(p);
    }

    function loadFilterStateFromURL() {
        const params = new URLSearchParams(window.location.search);
        const filterFields = getAllEither(params,'filter_field');
        const filterValues = getAllEither(params,'filter_value');
        Object.keys(valueFilters).forEach(key => { if (valueFilters[key] instanceof Set) valueFilters[key].clear(); });

        if (filterFields.length === filterValues.length && filterFields.length > 0) {
            for (let i=0;i<filterFields.length;i++) {
                const field = filterFields[i], value = filterValues[i];
                if (!valueFilters[field]) valueFilters[field] = new Set();
                valueFilters[field].add(value);
            }
        } else if (filterFields.length === 0) {
            try {
                const saved = sessionStorage.getItem('agGridFilters');
                if (saved) {
                    const filters = JSON.parse(saved);
                    Object.entries(filters).forEach(([field, values]) => {
                        if (Array.isArray(values)) {
                            if (!valueFilters[field]) valueFilters[field] = new Set();
                            values.forEach(v => valueFilters[field].add(v));
                        }
                    });
                }
            } catch(e){ console.error('Error loading filters from sessionStorage:', e); }
        }
    }

    function updateFilterIndicators() {
        const filterBtn = document.querySelector('.filter-toggle-btn');
        if (!filterBtn) return;
        const active = Object.values(valueFilters).reduce((c,s)=>c+((s instanceof Set && s.size>0)?1:0),0);
        let badge = filterBtn.querySelector('.filter-badge');
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'filter-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
            badge.style.fontSize = '0.6em';
            filterBtn.appendChild(badge);
        }
        if (active>0) { badge.textContent = active; badge.style.display='block'; }
        else { badge.style.display='none'; }
    }

    const LOCAL_DISTINCT_FIELDS = new Set(['pesanan_sku']);
    const STATIC_DISTINCT_VALUES = {
        return_condition: ['Good', 'Bad'],
        siap_cetak: ['Belum Siap', 'Siap Cetak']
    };

    async function fetchDistinct(field) {
        if (distinctCache[field]) return distinctCache[field];
        if (STATIC_DISTINCT_VALUES[field]) {
            distinctCache[field] = STATIC_DISTINCT_VALUES[field];
            return distinctCache[field];
        }
        if (LOCAL_DISTINCT_FIELDS.has(field)) {
            const values = new Set();
            if (window.gridOptions?.api?.forEachNode) {
                window.gridOptions.api.forEachNode(node => {
                    const value = node.data?.[field];
                    values.add(value !== undefined && value !== null && value !== '' ? String(value) : '-');
                });
            } else if (Array.isArray(rowData)) {
                rowData.forEach(r => {
                    const value = r?.[field];
                    values.add(value !== undefined && value !== null && value !== '' ? String(value) : '-');
                });
            }
            distinctCache[field] = Array.from(values).sort();
            return distinctCache[field];
        }
        try {
            const params = new URLSearchParams(window.location.search);
            params.set('field', field);
            delAll(params,'filter_field'); delAll(params,'filter_value'); delAll(params,'filter_operator');
            const url = `<?= base_url() ?>transaction/filter_values?${params.toString()}`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            if (!json.ok) throw new Error(json.error || 'Failed to fetch distinct values');
            distinctCache[field] = json.values || [];
            return distinctCache[field];
        } catch (error) {
            console.error(`Error fetching distinct values for ${field}:`, error);
            if (window.gridOptions?.api) {
                const values = new Set();
                window.gridOptions.api.forEachNode(node => {
                    const value = node.data[field];
                    values.add(value !== undefined && value !== null && value !== '' ? String(value) : '-');
                });
                distinctCache[field] = Array.from(values).sort();
                return distinctCache[field];
            }
            return [];
        }
    }

    window.refreshPending = async function(btnEl) {
        const btn = btnEl || document.getElementById('btnRefreshPending');
        if (!btn) return;
        const defaultLabel = btn.dataset.defaultLabel || btn.innerHTML;
        btn.dataset.defaultLabel = defaultLabel;
        const loadingLabel = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Merefresh...';
        const setRefreshLoading = (isLoading) => {
            btn.disabled = isLoading;
            btn.innerHTML = isLoading ? loadingLabel : defaultLabel;
        };

        setRefreshLoading(true);
        try {
            const res = await fetch('<?= base_url('api_v2/shopee_webhook_orders_refresh_pending') ?>', {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json().catch(() => null);

            if (!res.ok || !json?.status) {
                const msg = json?.message || 'Gagal refresh pending';
                if (window.Swal) {
                    Swal.fire({ icon: 'info', title: 'Informasi', text: msg });
                } else {
                    alert(msg);
                }
                return;
            }

            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: json.message || 'Refresh pending berhasil',
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    background: '#fff',
                    backdrop: 'rgba(0,0,0,0.4)',
                    customClass: {
                        popup: 'swal-solid'
                    }
                });

            }

            const params = buildParamsWithFilters(new URLSearchParams(window.location.search));
            await fetchGridDataWithParams(params);
        } catch (error) {
            console.error('refresh pending error:', error);
            if (window.Swal) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan saat refresh pending' });
            } else {
                alert('Terjadi kesalahan saat refresh pending');
            }
        } finally {
            setRefreshLoading(false);
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        loadFilterStateFromURL();
        updateFilterIndicators();

        const btnReset = document.getElementById('btnResetFilters');
        if (btnReset) btnReset.addEventListener('click', () => clearAllFilters());

    });

    // ===========================
    // Header renderer dengan dropdown checkbox
    // ===========================
    function headerWithFilter(field, title) {
        return {
            headerName: title,
            field,
            filter: (field === 'pesanan_count' || field === 'customer_price' || field === 'hpp') ? 'agNumberColumnFilter' : 'agTextColumnFilter',
            floatingFilter: false,
            cellRendererParams: {},
            sortable: false,
            headerComponent: class {
                init(params) {
                    this.params = params;
                    const e = this.eGui = document.createElement('div');
                    e.className = 'hdr-filter ag-header-cell';
                    e.setAttribute('col-id', field);

                    const titleEl = document.createElement('span');
                    titleEl.textContent = title;
                    titleEl.className = 'hdr-title';
                    titleEl.tabIndex = 0;
                    titleEl.style.cursor = 'pointer';

                    const sortIcon = document.createElement('i');
                    sortIcon.className = 'bi bi-arrow-down-up';

                    const btn = document.createElement('button');
                    btn.className = 'btn-filter bi bi-funnel';
                    btn.setAttribute('aria-label','Filter');

                    this.btn = btn;
                    this.field = field;
                    this.isOpen = false;

                    const doSort = () => {
                        if (isSortingInProgress) {
                            console.log('Sorting in progress, please wait...');
                            return;
                        }

                        const urlParams = new URLSearchParams(window.location.search);
                        const currentSortCol = urlParams.get('sort_column');
                        const currentSortOrder = urlParams.get('sort_order');

                        let newSort;

                        if (currentSortCol !== field) {
                            newSort = 'asc';
                        } else {
                            if (!currentSortOrder || currentSortOrder === 'ASC') {
                                newSort = 'desc';
                            } else if (currentSortOrder === 'DESC') {
                                newSort = null;
                            } else {
                                newSort = 'asc';
                            }
                        }

                        reloadWithSort(newSort ? field : null, newSort);
                    };

                    titleEl.addEventListener('click', doSort);
                    titleEl.addEventListener('keydown', (ev) => {
                        if (ev.key==='Enter'||ev.key===' ') { ev.preventDefault(); doSort(); }
                    });

                    const updateSortIcon = () => {
                        const urlParams = new URLSearchParams(window.location.search);
                        const currentSortCol = urlParams.get('sort_column');
                        const currentSortOrder = urlParams.get('sort_order');
                        const isLoading = loadingSortColumns.has(this.field);

                        if (isLoading) {
                            sortIcon.className = 'sort-spinner bi bi-arrow-repeat';
                        } else if (currentSortCol === this.field) {
                            sortIcon.className = currentSortOrder === 'ASC' ? 'bi bi-arrow-up' : 
                                                currentSortOrder === 'DESC' ? 'bi bi-arrow-down' : 
                                                'bi bi-arrow-down-up';
                        } else {
                            sortIcon.className = 'bi bi-arrow-down-up';
                        }
                    };

                    updateSortIcon();

                    this.loadingCheckInterval = setInterval(() => {
                        updateSortIcon();
                    }, 100);

                    btn.addEventListener('click', (ev) => { 
                        ev.stopPropagation(); 
                        this.toggle(); 
                    });

                    e.appendChild(titleEl);
                    e.appendChild(sortIcon);
                    e.appendChild(btn);

                    this._ensureLoaded = async () => {
                        if (distinctCache[field]) {
                            this._allValues = distinctCache[field];
                            if (!valueFilters[field]) valueFilters[field] = new Set();
                            return;
                        }
                        const values = await fetchDistinct(field);
                        this._allValues = values;
                        if (!valueFilters[field]) valueFilters[field] = new Set();
                    };

                    this.toggle = async () => {
                        if (!this._loaded) await this._ensureLoaded();
                        document.querySelectorAll('.hdr-dd-portal').forEach(el => el.parentNode.removeChild(el));
                        if (!this.isOpen) this.openDropdown(); else this.isOpen = false;
                    };

                    this.openDropdown = () => {
                        this.isOpen = true;
                        const wrapper = document.getElementById('agGridWrapper');
                        const portal  = document.getElementById('filter-portal');

                        const dd = document.createElement('div');
                        dd.className = 'hdr-dd-portal';
                        dd.innerHTML = `
                            <input type="text" class="form-control-sm search" placeholder="Cari nilai..." />
                            <div class="list"></div>
                            <div class="actions">
                                <button type="button" class="btn-2xs btn-clear">Clear</button>
                                <button type="button" class="btn-2xs btn-apply">Apply</button>
                            </div>
                        `;
                        portal.appendChild(dd);

                        const position = () => {
                            const btnRect  = this.btn.getBoundingClientRect();
                            const wrapRect = wrapper.getBoundingClientRect();
                            let left = btnRect.left - wrapRect.left;
                            let top  = btnRect.bottom - wrapRect.top;
                            const margin = 8;
                            const ddWidth = dd.offsetWidth || 260;
                            const maxLeft = wrapper.clientWidth - ddWidth - margin;
                            if (left > maxLeft) left = Math.max(margin, maxLeft);
                            if (left < margin) left = margin;
                            dd.style.left = left + 'px';
                            dd.style.top  = top  + 'px';
                        };

                        this.renderList(dd, this._allValues || []);

                        const applyBtn = dd.querySelector('.btn-apply');
                        const setApplyLoading = (isLoading) => {
                            if (!applyBtn) return;
                            applyBtn.disabled = isLoading;
                            applyBtn.innerHTML = isLoading
                                ? '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading...'
                                : 'Apply';
                        };

                        applyBtn.addEventListener('click', async () => {
                            setApplyLoading(true);
                            try {
                                const paramsNow = new URLSearchParams(window.location.search);
                                const params    = buildParamsWithFilters(paramsNow);
                                await fetchGridDataWithParams(params);
                                close();
                            } finally {
                                setApplyLoading(false);
                            }
                        });

                        dd.querySelector('.btn-clear').addEventListener('click', async () => {
                            valueFilters[this.field] = new Set();
                            this.renderList(dd, this._allValues || []);
                            const paramsNow = new URLSearchParams(window.location.search);
                            const params    = buildParamsWithFilters(paramsNow);
                            await fetchGridDataWithParams(params);
                        });

                        dd.querySelector('.search').addEventListener('input', (evt) => {
                            const q = evt.target.value.toLowerCase();
                            const values = (this._allValues || []).filter(v => String(v).toLowerCase().includes(q));
                            this.renderList(dd, values);
                            position();
                        });

                        const onResize   = () => position();
                        const onScroll   = () => position();
                        const viewport   = document.querySelector('.ag-center-cols-viewport');
                        window.addEventListener('resize', onResize, { passive: true });
                        window.addEventListener('scroll', onScroll, true);
                        viewport?.addEventListener('scroll', onScroll, { passive: true });

                        const clickOutsideHandler = (ev) => {
                            if (!dd.contains(ev.target) && !this.btn.contains(ev.target)) close();
                        };
                        setTimeout(() => document.addEventListener('click', clickOutsideHandler, { once: true }), 0);

                        position();

                        const close = () => {
                            if (!this.isOpen) return;
                            this.isOpen = false;
                            if (dd.parentNode) dd.parentNode.removeChild(dd);
                            window.removeEventListener('resize', onResize);
                            window.removeEventListener('scroll', onScroll, true);
                            viewport?.removeEventListener('scroll', onScroll);
                        };
                        this._closeDropdown = close;
                    };

                    this.renderList = (dd, values) => {
                        const list = dd.querySelector('.list');
                        const sel = valueFilters[this.field] || new Set();
                        const html = [
                            `<label class="d-block mb-1"><input type="checkbox" data-role="all" ${sel.size === 0 ? 'checked' : ''}> (Semua)</label>`
                        ].concat(values.map(v => {
                            const val = String(v);
                            const checked = sel.size === 0 ? false : sel.has(val);
                            return `<label class="d-block mb-1"><input type="checkbox" data-val="${escHtml(val)}" ${checked ? 'checked' : ''}> ${escHtml(val)}</label>`;
                        })).join('');
                        list.innerHTML = html;

                        list.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                            cb.addEventListener('change', (ev) => {
                                const cbx = ev.target;
                                if (cbx.dataset.role === 'all') {
                                    valueFilters[this.field] = new Set();
                                    list.querySelectorAll('input[type="checkbox"][data-val]').forEach(x => x.checked = false);
                                } else {
                                    const val = cbx.getAttribute('data-val');
                                    if (!valueFilters[this.field]) valueFilters[this.field] = new Set();
                                    if (cbx.checked) valueFilters[this.field].add(val); else valueFilters[this.field].delete(val);
                                    const hasAny = valueFilters[this.field].size > 0;
                                    const allCbx = list.querySelector('input[type="checkbox"][data-role="all"]');
                                    if (allCbx) allCbx.checked = !hasAny;
                                }
                            });
                        });
                    };
                }

                getGui(){ 
                    return this.eGui; 
                }

                refresh(){ 
                    return false; 
                }

                destroy() {
                    if (this.loadingCheckInterval) {
                        clearInterval(this.loadingCheckInterval);
                    }
                }
            }
        };
    }

    function passesReturnConditionFilter(row, setVals) {
        if (!(setVals instanceof Set) || setVals.size === 0) return true;

        const hasGood = Number(row?.return_good_qty || 0) > 0;
        const hasBad = Number(row?.return_bad_qty || 0) > 0;

        if (setVals.has('Good') && hasGood) return true;
        if (setVals.has('Bad') && hasBad) return true;

        return false;
    }

    // ===========================
    // ColumnDefs 
    // ===========================
    const columnDefs = [
        {
            ...headerWithFilter('order_id','Pesanan'),
            flex: 1.2,
            cellRenderer: function (p) {
            if (p.node?.rowPinned === 'bottom') return p.value || '-';

            const oid = p.value || '-';
            if (oid === '-') return '-';
            const safe = escHtml(String(oid));

            const bookingLabel = p.data?.is_for_booking === 1 ? '<span class="badge bg-blue">Booking</span>' : '';
            return `
                <div class="order-cell">
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="order-link" onclick="window.onOrderClick('${safe}')">
                        ${safe}
                    </button>
                    ${bookingLabel}
                </div>
                <i class="bi bi-copy order-copy" title="Copy" aria-label="Copy"
                    onclick="window.copyToClipboard('${safe}')"></i>
                </div>
            `;
            }
        },
        {
            headerName:'Tanggal',
            field:'date_text',
            filter: false,
            sortable: true,
            flex: 0.9,
            minWidth: 80,
            valueFormatter: p => p.value ? new Date(p.value).toLocaleDateString() : '-',
            cellRenderer: p => {
            const dt = p.value ? new Date(p.value) : null;
            if (!dt) return '-';
            const y = dt.getFullYear();
            const m = String(dt.getMonth()+1).padStart(2,'0');
            const d = String(dt.getDate()).padStart(2,'0');
            return `${d}-${m}-${y}`;
            }
        },
        { 
            ...headerWithFilter('customer_text','Customer'),
            flex: 1.5,
            minWidth: 150
        },
        {
            headerName:'Produk',
            // Sebelumnya kolom ini membaca 'json' yang isinya array kosong,
            // jadi selalu tampil '-'. Nama produk sekarang sudah dirangkai
            // di sisi server sebagai pesanan_produk.
            field:'pesanan_produk',
            flex: 2,
            minWidth: 240,
            filter:'agTextColumnFilter',
            autoHeight: true,
            wrapText: true,
            valueGetter: p => p.data?.pesanan_produk || parseProdukLineSingle(p.data?.json) || '',
            tooltipValueGetter: p => p.data?.pesanan_produk_full || p.data?.pesanan_produk || '',
            cellRenderer: p => {
                const line = p.data?.pesanan_produk || parseProdukLineSingle(p.data?.json);
                if (!line) return '-';

                const img = p.data?.pesanan_img || '';
                const teks = `<span class="a-none text-blue fw-700">${escHtml(line)}</span>`;
                if (!img) return teks;

                // Gambar diambil langsung dari server marketplace, tidak
                // disalin ke sini. loading=lazy supaya baris yang belum
                // terlihat tidak ikut mengunduh.
                return `<div style="display:flex;align-items:center;gap:8px">
                    <img src="${escHtml(img)}" loading="lazy" alt=""
                         style="width:34px;height:34px;border-radius:6px;
                                object-fit:cover;flex:0 0 34px;background:#f1f1f4"
                         onerror="this.style.display='none'">
                    ${teks}
                </div>`;
            }
        },
        {
            ...headerWithFilter('return_condition','Retur Produk'),
            field:'return_condition',
            flex: 1.4,
            minWidth: 220,
            sortable: false,
            autoHeight: true,
            wrapText: true,
            cellRenderer: p => {
            const returnTags = renderReturnProductBadges(p.data);
            return returnTags || '-';
            }
        },
        {
            headerName:'SKU',
            field:'pesanan_sku',
            filter:'agTextColumnFilter',
            flex: 1.1,
            minWidth: 150,
            cellRenderer: p => {
            const sku = p.value || p.data?.pesanan_sku || '';
            return sku ? `<span class="fw-700">${escHtml(sku)}</span>` : '-';
            }
        },
        {
            ...headerWithFilter('customer_price','Harga Pesanan'),
            type:'numericColumn', 
            filter:'agNumberColumnFilter', 
            flex: 1,
            minWidth: 200,
            valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.customer_price_fmt || p.value.toLocaleString('id-ID')) : '-',
            cellRenderer:params=>{
            const amt = params.data.customer_price_fmt || (params.value ?? 0).toLocaleString('id-ID');
            const st = params.data.pencairan_status || '-';
            const at = params.data.pencairan_at || '-';
            const stCls = (st === 'Settlement') ? 'bg-green' : 'bg-red';
            return `<div><div class="fw-bold">Rp ${amt}</div><div>${badge(stCls, st)} ${badge('bg-grey', at)}</div></div>`;
            }
        },
        {
            ...headerWithFilter('order_status','Status'),
            flex: 1.2,
            minWidth: 160,
            cellRenderer:p=>{
            const st = p.value || '-';
            const okGreen = ['DELIVERED','COMPLETED'].includes(st);
            const okBlue  = ['READY_TO_SHIP','PENDING','PROCESSED','SHIPPED','TO_CONFIRM_RECEIVE'].includes(st);
            const stCls = okGreen ? 'bg-green' : (okBlue ? 'bg-blue' : 'bg-red');
            const reverse = p.data.reverse_status ? badge('bg-red', p.data.reverse_status) : '';
            return `<div>${badge(stCls, st)} ${reverse}</div>`;
            }
        },
        {
            ...headerWithFilter('siap_cetak','Siap Cetak'),
            flex: 0.9,
            minWidth: 130,
            hide: true,
            cellRenderer:p=>{
            if (p.node?.rowPinned === 'bottom') return p.value || '';
            const st = p.value || 'Belum Siap';
            const stCls = st === 'Siap Cetak' ? 'bg-green' : 'bg-red';
            return badge(stCls, st);
            }
        },
        { 
            ...headerWithFilter('rts_at','RTS'), 
            flex: 0.8, 
            minWidth: 120,
            hide: true 
        },
        { 
            ...headerWithFilter('print_at','Tanggal Print'), 
            flex: 0.8, 
            minWidth: 120,
            hide: true 
        },
        { 
            ...headerWithFilter('package_id','Kode Paket'), 
            flex: 0.8, 
            minWidth: 120,
            hide: true 
        },
        { 
            ...headerWithFilter('marketplace','Marketplace'), 
            flex: 0.8, 
            minWidth: 120,
            hide: true 
        },
        { 
            ...headerWithFilter('shop_name','Toko'), 
            flex: 0.8, 
            minWidth: 120,
            hide: true 
        },
        { 
            ...headerWithFilter('brand','Brand'), 
            flex: 0.8, 
            minWidth: 100,
            hide: true 
        },
        { 
            ...headerWithFilter('phone','Phone'), 
            flex: 0.8, 
            minWidth: 120,
            hide: true 
        },
        { 
            ...headerWithFilter('is_manual','Type'), 
            flex: 0.6, 
            minWidth: 100,
            hide: true, 
            valueFormatter:p=> (p.value===1||p.value==='1')?'Manual':'Marketplace' 
        },
        { 
            ...headerWithFilter('payment_type','Tipe Order'), 
            flex: 0.8, 
            minWidth: 120,
            hide: true 
        },
        { 
            ...headerWithFilter('c_username','Username'), 
            flex: 0.8, 
            minWidth: 120,
            hide: true 
        },
        { 
            ...headerWithFilter('cs','CS'), 
            flex: 0.8, 
            minWidth: 100,
            hide: true 
        },
        {
            ...headerWithFilter('payment_status','Status Bayar'), 
            flex: 0.8, 
            minWidth: 130,
            hide: true,
            cellRenderer:p=>{
            const st = p.value || '-';
            const cls = (st === 'Paid') ? 'bg-green' : 'bg-red';
            const payAt = dateFormat(p.data.pay_at) || '-';
            return `<div>${badge(cls, st)}</div>`;
            }
        },
        { 
            ...headerWithFilter('pay_at','Dibayar Pada'), 
            flex: 0.9, 
            minWidth: 140,
            hide: true 
        },
        { 
            ...headerWithFilter('dana_pencairan','Dana Pencairan'), 
            type:'numericColumn', 
            flex: 0.8, 
            minWidth: 140,
            hide: true,
            valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.dana_pencairan_fmt || p.value.toLocaleString('id-ID')) : '-'
        },
        { 
            ...headerWithFilter('omset_kotor','Omset Kotor'), 
            type:'numericColumn', 
            flex: 0.8, 
            minWidth: 130,
            hide: true,
            valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.omset_kotor_fmt || p.value.toLocaleString('id-ID')) : '-'
        },
        { 
            ...headerWithFilter('diskon_penjual','Diskon/Voucher Penjual'), 
            type:'numericColumn', 
            flex: 0.9, 
            minWidth: 160,
            hide: true,
            valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.diskon_penjual_fmt || p.value.toLocaleString('id-ID')) : '-'
        },
        { 
            ...headerWithFilter('biaya_lainnya','Biaya Lainnya'), 
            type:'numericColumn', 
            flex: 0.8, 
            minWidth: 130,
            hide: true,
            valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.biaya_lainnya_fmt || p.value.toLocaleString('id-ID')) : '-'
        },
        { 
            ...headerWithFilter('omset_bersih','Omset Bersih'), 
            type:'numericColumn', 
            flex: 0.8, 
            minWidth: 130,
            hide: true,
            valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.omset_bersih_fmt || p.value.toLocaleString('id-ID')) : '-'
        },
        { 
            ...headerWithFilter('marketplace_fee','Marketplace Fee'), 
            type:'numericColumn', 
            flex: 0.9, 
            minWidth: 150,
            hide: true,
            valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.marketplace_fee_fmt || p.value.toLocaleString('id-ID')) : '-'
        },
        { 
            ...headerWithFilter('komisi_afiliasi','Affiliate Fee'), 
            type:'numericColumn', 
            flex: 0.9, 
            minWidth: 140,
            hide: true,
            valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.komisi_afiliasi_fmt || p.value.toLocaleString('id-ID')) : '-'
        },
        { 
            ...headerWithFilter('shipping','Kurir'), 
            flex: 0.8, 
            minWidth: 100,
            hide: true 
        },
        { 
            ...headerWithFilter('shipping_status','Status Pengiriman'), 
            flex: 1.0, 
            minWidth: 150,
            hide: true 
        },
        { 
            ...headerWithFilter('reverse_id','No Pengajuan'), 
            flex: 0.8, 
            minWidth: 130,
            hide: true 
        },
        { 
            ...headerWithFilter('return_status','Status Return'), 
            flex: 0.8, 
            minWidth: 130,
            hide: true 
        },
        {
            ...headerWithFilter('marketplace_img','Logo Marketplace'), 
            flex: 0.8, 
            minWidth: 150,
            hide: true, 
            filter:'agTextColumnFilter',
            cellRenderer:p=> p.value ? `<img src="${escHtml(p.value)}" style="height:24px;border-radius:6px">` : '-'
        },
        {
            ...headerWithFilter('shipping_img','Logo Kurir'), 
            flex: 0.8, 
            minWidth: 120,
            hide: true, 
            filter:'agTextColumnFilter',
            cellRenderer:p=> p.value ? `<img src="${escHtml(p.value)}" style="height:24px;border-radius:6px">` : '-'
        },
        { 
            ...headerWithFilter('hpp','HPP'), 
            type:'numericColumn', 
            flex: 0.9, 
            minWidth: 100,
            hide: true,
            valueFormatter:p=> p.value!=null ? 'Rp '+(p.data.hpp_fmt || p.value.toLocaleString('id-ID')) : '-'
        },
        { 
            ...headerWithFilter('c_type','Kategori'), 
            flex: 0.8, 
            minWidth: 150,
            hide: true,
            filter: 'agTextColumnFilter', 
        },
        {
            headerName:'', 
            field:'id', 
            colId:'actions',
            width: 60,
            minWidth: 60,
            maxWidth: 80,
            sortable:false, 
            filter:false,
            cellRenderer:params=>{
            const d = params.data;
            const isManual = +d.is_manual === 1;
            return `
                <button type="button"
                    class="text-muted transaction-action-trigger"
                    data-id="${escHtml(d.id)}"
                    data-is-manual="${isManual ? '1' : '0'}"
                    data-order-id="${escHtml(d.order_id || '')}"
                    data-marketplace="${escHtml(d.marketplace || '')}"
                    data-awb-number="${escHtml(d.awb_number || '')}"
                    aria-label="Aksi order">
                    <i class="bi bi-three-dots-vertical fs-16"></i>
                </button>
            `;
            }
        },
    ];

    // ===========================
    // Grid Options & Mount 
    // ===========================
    if (window.gridOptions?.api) { try { window.gridOptions.api.destroy(); } catch(e){} }
    window._defaultColState = null;

    window.gridOptions = {
        theme: 'legacy',
        defaultColDef: { 
            sortable: false,
            filter: true, 
            resizable: true, 
            floatingFilter: false 
        },
        suppressMultiSort: true,
        suppressDragLeaveHidesColumns: true,
        columnDefs, 
        rowData, 
        animateRows: true,
        rowSelection: { 
            mode: 'multiRow', 
            checkboxes: true, 
            headerCheckbox: true, 
            selectAll: 'filtered', 
            enableClickSelection: false 
        },
        isExternalFilterPresent: () => Object.values(valueFilters).some(s => s instanceof Set && s.size>0),
        doesExternalFilterPass: (node) => {
            for (const [field, setVals] of Object.entries(valueFilters)) {
                if (!(setVals instanceof Set) || setVals.size === 0) continue;
                if (field === 'return_condition') {
                    if (!passesReturnConditionFilter(node.data, setVals)) return false;
                    continue;
                }
                const raw = node.data[field];
                const val = (raw == null || raw === '') ? '-' : String(raw);
                if (!setVals.has(val)) return false;
            }
            return true;
        },
        onSelectionChanged: syncSelectedIds,
        onGridReady: function (params) {
            const cols = params.api.getColumns() || [];
            window._defaultColState = cols.map((c, i) => ({
                    colId: c.getColId(),
                    hide: !c.isVisible(),
                    order: i,
            }));

            loadColumnStateFromSession();

            setTimeout(() => {
                    params.api.sizeColumnsToFit();
                    handleInitialSortState();
                    loadFilterStateFromURL();
                    updateFilterIndicators();
                    renderFooterTotals();
            }, 100);
        },
        onColumnVisible: function() {
            saveColumnStateToSession();
            renderFooterTotals();
        },
        onColumnMoved: function() {
            saveColumnStateToSession();
            renderFooterTotals();
        },
        onColumnPinned: function() {
            saveColumnStateToSession();
            renderFooterTotals();
        },
        onColumnResized: function () {
            clearTimeout(window.__colResizeTimer);
            window.__colResizeTimer = setTimeout(function(){
                saveColumnStateToSession();
                renderFooterTotals();
            }, 300);
        }
    };

    (function(){
        const eGridDiv = document.getElementById('myGrid');
        if (!eGridDiv) return;
        const api = agGrid.createGrid(eGridDiv, window.gridOptions);
        window.gridOptions.api = api;
    })();

    // ===========================
    // Progressive Column Chooser
    // ===========================
    (function initProgressiveColumnChooser(){
        const btn = document.getElementById('btnColumns');
        const wrapper = document.getElementById('agGridWrapper') || document.body;
        if (!btn || !wrapper) return;

        let isOpen = false;
        let ddRef = null;
        let cleanupFns = [];

        btn.addEventListener('click', () => { isOpen ? close() : open(); });

        function open() {
            if (!window.gridOptions?.api || isOpen) return;

            const dd = document.createElement('div');
            dd.className = 'dropdown-panel shadow rounded p-2 bg-white border';
            dd.style.position = 'absolute';
            dd.style.width = '280px';
            dd.style.zIndex = 1050;
            ddRef = dd;

            const cols = gridOptions.api.getColumns()
                .filter(c => {
                    const def = c.getColDef();
                    return def && def.headerName && def.headerName !== '' && def.field !== 'id';
                })
                .map(c => ({
                    id: c.getColId(),
                    title: c.getColDef().headerName || c.getColId(),
                    visible: c.isVisible()
                }));

            dd.innerHTML = `
                <div class="mb-2">
                    <input type="text" class="form-control form-control-sm" placeholder="Cari kolom..." data-role="search">
                </div>
                <div class="mb-2 small d-flex align-items-center gap-2">
                    <label class="mb-0"><input type="checkbox" data-role="toggle-all"> Tampilkan semua</label>
                    <button type="button" class="btn btn-xs btn-link p-0 ms-auto" data-role="defaults">Defaults</button>
                </div>
                <div class="list" style="max-height:260px;overflow:auto"></div>
                <div class="d-flex justify-content-end gap-2 mt-2">
                    <button type="button" class="btn btn-sm btn-light" data-role="cancel">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" data-role="apply">Terapkan</button>
                </div>
            `;

            wrapper.appendChild(dd);
            isOpen = true;

            const listEl = dd.querySelector('.list');
            const render = (items) => {
                listEl.innerHTML = items.map(it => `
                    <label class="d-block mb-1">
                        <input type="checkbox" data-col="${escHtml(it.id)}" ${it.visible ? 'checked' : ''}> ${escHtml(it.title)}
                    </label>
                `).join('');
            };
            render(cols);

            const position = () => {
                const b = btn.getBoundingClientRect();
                const w = wrapper.getBoundingClientRect();
                let left = b.left - w.left;
                let top  = b.bottom - w.top + 6;
                const margin = 8;
                const maxLeft = wrapper.clientWidth - (dd.offsetWidth || 280) - margin;
                if (left > maxLeft) left = Math.max(margin, maxLeft);
                if (left < margin) left = margin;
                dd.style.left = left + 'px';
                dd.style.top  = top  + 'px';
            };
            position();

            const onSearch = (e) => {
                const q = e.target.value.toLowerCase();
                const filtered = cols.filter(c => c.title.toLowerCase().includes(q) || c.id.toLowerCase().includes(q));
                render(filtered);
                position();
            };
            dd.querySelector('[data-role="search"]').addEventListener('input', onSearch);

            const onToggleAll = (e) => {
                const checked = e.target.checked;
                listEl.querySelectorAll('input[type="checkbox"][data-col]').forEach(cb => cb.checked = checked);
            };
            dd.querySelector('[data-role="toggle-all"]').addEventListener('change', onToggleAll);

            const onDefaults = () => {
                if (!window._defaultColState) return;
                gridOptions.api.applyColumnState({
                    state: window._defaultColState.map(s => ({ colId: s.colId, hide: s.hide })),
                    applyOrder: true
                });
                saveColumnStateToSession();
                const currentCols = gridOptions.api.getColumns() || [];
                const vis = Object.fromEntries(currentCols.map(c => [c.getColId(), c.isVisible()]));
                listEl.querySelectorAll('input[type="checkbox"][data-col]').forEach(cb => {
                    const id = cb.getAttribute('data-col');
                    cb.checked = !!vis[id];
                });
            };
            dd.querySelector('[data-role="defaults"]').addEventListener('click', onDefaults);

            const onCancel = () => close();
            dd.querySelector('[data-role="cancel"]').addEventListener('click', onCancel);

            const onApply = () => {
                const checks = Array.from(listEl.querySelectorAll('input[type="checkbox"][data-col]'));
                const state = checks.map(cb => ({ colId: cb.getAttribute('data-col'), hide: !cb.checked }));
                gridOptions.api.applyColumnState({ state, applyOrder: false });

                saveColumnStateToSession();
                close();
            };

            dd.querySelector('[data-role="apply"]').addEventListener('click', onApply);

            const clickOutside = (ev) => {
                if (ddRef && !ddRef.contains(ev.target) && ev.target !== btn) close();
            };
            setTimeout(() => document.addEventListener('mousedown', clickOutside), 0);

            const onResize = () => position();
            const onScroll = () => position();
            const viewport = document.querySelector('.ag-center-cols-viewport');

            window.addEventListener('resize', onResize, { passive: true });
            window.addEventListener('scroll', onScroll, true);
            viewport?.addEventListener('scroll', onScroll, { passive: true });

            cleanupFns.push(
                () => dd.remove(),
                () => document.removeEventListener('mousedown', clickOutside),
                () => window.removeEventListener('resize', onResize),
                () => window.removeEventListener('scroll', onScroll, true),
                () => viewport?.removeEventListener('scroll', onScroll)
            );
        }

        function close() {
            if (!isOpen) return;
            isOpen = false;
            while (cleanupFns.length) { try { cleanupFns.pop()(); } catch(e){} }
            ddRef = null;
        }
    })();

// ===========================
// Sinkronkan selection
// ===========================
})();

$(document).off('change', '.checkAll').on('change', '.checkAll', function() {
    this.checked ? gridOptions.api.selectAll() : gridOptions.api.deselectAll();
});
</script>
