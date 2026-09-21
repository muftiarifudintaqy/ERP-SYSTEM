<?php
$brand = $_GET['brand'] ?? '';
$status = $_GET['status'] ?? '';
$is_operational = isset($_GET['is_operational']) ? $_GET['is_operational'] : '0';
$keyword = $_GET['keyword'] ?? '';
$stock_min = $_GET['stock_min'] ?? '';
$assets = $assets ?? [];
?>
<div class="form-message"></div>
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">REPORT</h3>
        </div>
        <?php $this->load->view('report/menu') ?>
        <div class="col-lg-12 mb-3">
            <form action="" id="assetFilterForm">
                <input type="hidden" name="p" value="aset">
                <div class="row g-2">
                    <div class="col-md-2">
                        <select class="form-control select2" name="brand">
                            <option value="">Brand</option>
                            <?php foreach ($brands as $val) :
                                $text = ($brand === $val["code"]) ? "selected" : "";
                            ?>
                                <option <?= $text ?> value="<?= $val["code"] ?>"><?= $val["code"] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="status">
                            <option value="">Status</option>
                            <option value="Aktif" <?= $status === "Aktif" ? "selected" : "" ?>>Aktif</option>
                            <option value="Tidak Aktif" <?= $status === "Tidak Aktif" ? "selected" : "" ?>>Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="is_operational">
                            <option value="">Semua Produk</option>
                            <option value="0" <?= $is_operational === "0" ? "selected" : "" ?>>Produk Jual</option>
                            <option value="1" <?= $is_operational === "1" ? "selected" : "" ?>>Produk Operasional</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="keyword" placeholder="Cari nama / SKU" value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-primary w-100 form-control" type="submit">
                            <i class="bi bi-search fs-16"></i> Cari Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div id="agGridWrapper" class="ag-theme-quartz">
                        <div id="assetGrid" style="width:100%; height:100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #agGridWrapper {
        height: 70vh;
        position: relative;
    }
    #agGridWrapper .ag-header-cell-label {
        justify-content: center;
    }
</style>

<script>
(function(){
    const LIST_URL = `<?= base_url() ?>report/aset_item`;
    const form = document.getElementById('assetFilterForm');
    const gridEl = document.getElementById('assetGrid');

    function debounce(fn, ms=150){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; }

    function getFormParams() {
        const params = new URLSearchParams();
        if (!form) return params;
        const fd = new FormData(form);
        for (const [k, v] of fd.entries()) {
            const val = v == null ? '' : String(v);
            if (k === 'is_operational') {
                params.set(k, val);
                continue;
            }
            if (val.trim() !== '') {
                params.set(k, val);
            }
        }
        return params;
    }

    function applySortParams(params) {
        if (!window.gridOptions?.api) return;
        const state = window.gridOptions.api.getColumnState ? window.gridOptions.api.getColumnState() : [];
        const sorted = (state || []).filter(c => c.sort);
        if (sorted.length > 0) {
            sorted.sort((a, b) => (a.sortIndex ?? 0) - (b.sortIndex ?? 0));
            params.set('sort_column', sorted[0].colId);
            params.set('sort_order', String(sorted[0].sort || 'asc').toUpperCase());
        } else {
            params.delete('sort_column');
            params.delete('sort_order');
        }
    }

    function applyFilterModel(params) {
        if (!window.gridOptions?.api) return;
        const model = window.gridOptions.api.getFilterModel() || {};
        if (Object.keys(model).length > 0) {
            params.set('filter_model', JSON.stringify(model));
        } else {
            params.delete('filter_model');
        }
    }

    function updateFooter(rows) {
        const total = (rows || []).reduce((sum, row) => sum + (Number(row.total_value) || 0), 0);
        if (window.gridOptions?.api) {
            window.gridOptions.api.setGridOption('pinnedBottomRowData', [{
                name: 'TOTAL',
                total_value: total
            }]);
        }
    }

    async function fetchGridData({ updateUrl = true } = {}) {
        const params = getFormParams();
        applySortParams(params);
        applyFilterModel(params);
        try {
            if (updateUrl) {
                const urlParams = new URLSearchParams(params.toString());
                urlParams.delete('filter_model');
                urlParams.delete('sort_column');
                urlParams.delete('sort_order');
                history.pushState({}, '', `${location.pathname}?${urlParams.toString()}`);
            }
            const res = await fetch(`${LIST_URL}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (json && json.ok && window.gridOptions?.api) {
                window.gridOptions.api.setGridOption('rowData', json.rows || []);
                updateFooter(json.rows || []);
            }
        } catch (err) {
            console.error('asset grid fetch error', err);
        }
    }

    const columnDefs = [
        { headerName: 'Nama Barang', field: 'name', filter: 'agTextColumnFilter', flex: 2, minWidth: 220,
          cellClass: params => params.node.rowPinned ? 'fw-bold' : '' },
        { headerName: 'HPP', field: 'hpp', filter: 'agNumberColumnFilter', type: 'numericColumn', flex: 1, minWidth: 120,
          valueFormatter: p => p.value != null ? 'Rp ' + Number(p.value).toLocaleString('id-ID') : '-' },
        { headerName: 'Stock', field: 'stock', filter: 'agNumberColumnFilter', type: 'numericColumn', flex: 1, minWidth: 110,
          valueFormatter: p => p.value != null ? Number(p.value).toLocaleString('id-ID') : '-' },
        { headerName: 'Total Value', field: 'total_value', filter: 'agNumberColumnFilter', type: 'numericColumn', flex: 1, minWidth: 140,
          valueFormatter: p => p.value != null ? 'Rp ' + Number(p.value).toLocaleString('id-ID') : '-',
          cellClass: params => params.node.rowPinned ? 'fw-bold' : '' },
    ];

    if (window.gridOptions?.api) { try { window.gridOptions.api.destroy(); } catch(e){} }

    window.gridOptions = {
        theme: 'legacy',
        defaultColDef: {
            sortable: true,
            filter: true,
            resizable: true,
            floatingFilter: false,
            suppressMenuHide: true,
            menuTabs: ['filterMenuTab'],
            headerClass: 'text-center'
        },
        columnDefs,
        rowData: [],
        pagination: true,
        paginationPageSize: 50,
        paginationPageSizeSelector: [25, 50, 100, 200],
        onGridReady: (params) => {
            window.gridOptions.api = params.api;
            fetchGridData({ updateUrl: false });
        },
        onFilterChanged: debounce(() => fetchGridData({ updateUrl: false }), 200),
        onSortChanged: debounce(() => fetchGridData({ updateUrl: false }), 200),
    };

    if (gridEl) {
        const api = agGrid.createGrid(gridEl, window.gridOptions);
        window.gridOptions.api = api;
    }

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        fetchGridData({ updateUrl: true });
    });
})();
</script>
