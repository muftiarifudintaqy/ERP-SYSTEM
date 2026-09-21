<?php
$campaign = isset($campaign) && is_array($campaign) ? $campaign : array();
$campaignName = trim((string)($campaign['name'] ?? 'Detail Campaign'));
$sheetName = trim((string)($sheet_name ?? ($campaign['sheets_sheet_name'] ?? 'CRM Broadcast')));
if ($sheetName === '') {
    $sheetName = 'CRM Broadcast';
}
$headers = isset($sheet_headers) && is_array($sheet_headers) ? $sheet_headers : array();
$rows = isset($sheet_rows) && is_array($sheet_rows) ? $sheet_rows : array();
$sheetStatus = !empty($sheet_status);
$sheetMessage = trim((string)($sheet_message ?? ''));
$authUrl = trim((string)($sheet_auth_url ?? ''));
$spreadsheetId = trim((string)($campaign['sheets_spreadsheet_id'] ?? ''));
$spreadsheetUrl = $spreadsheetId !== '' ? 'https://docs.google.com/spreadsheets/d/' . rawurlencode($spreadsheetId) . '/edit' : '';
$campaignMetrics = isset($campaign_metrics) && is_array($campaign_metrics) ? $campaign_metrics : array();
$targetCount = isset($campaignMetrics['target_count']) ? (int)$campaignMetrics['target_count'] : 0;
$crmResponseCount = isset($campaignMetrics['crm_response_count']) ? (int)$campaignMetrics['crm_response_count'] : 0;
$outsideResponseCount = isset($campaignMetrics['outside_response_count']) ? (int)$campaignMetrics['outside_response_count'] : 0;
$focusCustomer = isset($focus_customer) && is_array($focus_customer) ? $focus_customer : array();
$focusCustomerName = trim((string)($focusCustomer['full_name'] ?? ''));
$focusCustomerPhone = trim((string)($focusCustomer['phone'] ?? ''));

if (empty($headers)) {
    $headers = array('Waktu', 'Campaign ID', 'Campaign', 'Customer ID', 'Full Name', 'Username', 'Phone', 'Status', 'Request ID', 'Message ID', 'Source', 'Synced At');
}

$columns = array();
foreach ($headers as $idx => $header) {
    $label = trim((string)$header);
    if ($label === '') {
        $label = 'Kolom ' . ($idx + 1);
    }
    $columns[] = array(
        'field' => 'col_' . $idx,
        'headerName' => $label,
    );
}

$gridRows = array();
foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $item = array();
    foreach ($columns as $idx => $column) {
        $item[$column['field']] = isset($row[$idx]) ? (string)$row[$idx] : '';
    }
    $gridRows[] = $item;
}

$responseCount = 0;
foreach ($gridRows as $item) {
    foreach ($item as $value) {
        if (trim((string)$value) !== '') {
            $responseCount++;
            break;
        }
    }
}
$responseRate = $targetCount > 0 ? round(($responseCount / $targetCount) * 100, 1) : 0;
$responseRateDisplay = rtrim(rtrim(number_format($responseRate, 1, ',', '.'), '0'), ',') . '%';

if (!function_exists('crm_campaign_detail_escape')) {
    function crm_campaign_detail_escape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
?>

<div class="crm-campaign-detail-page">
    <div class="crm-campaign-detail-header">
        <div class="crm-campaign-detail-title">
            <a href="<?= base_url('crm') ?>" class="crm-icon-btn" title="Kembali ke CRM">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div class="crm-campaign-title-text">
                <div class="crm-campaign-eyebrow">Detail Campaign / Broadcast</div>
                <h4><?= crm_campaign_detail_escape($campaignName) ?></h4>
            </div>
        </div>
        <div class="crm-campaign-actions">
            <?php if ($spreadsheetUrl !== ''): ?>
                <a href="<?= crm_campaign_detail_escape($spreadsheetUrl) ?>" class="btn btn-sm btn-outline-success" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Sheets
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="crm-campaign-meta-row">
        <div class="crm-campaign-meta-item">
            <span class="label">Target Broadcast</span>
            <span class="value"><?= number_format($targetCount, 0, ',', '.') ?> customer</span>
            <span class="hint">Customer yang masuk campaign ini</span>
        </div>
        <div class="crm-campaign-meta-item">
            <span class="label">Respons Masuk</span>
            <span class="value"><span id="crmCampaignResponseTotal"><?= number_format($responseCount, 0, ',', '.') ?></span> pengisi</span>
            <span class="hint"><?= number_format($crmResponseCount, 0, ',', '.') ?> dari CRM, <?= number_format($outsideResponseCount, 0, ',', '.') ?> dari luar CRM</span>
        </div>
        <div class="crm-campaign-meta-item">
            <span class="label">Rasio Respons</span>
            <span class="value"><?= crm_campaign_detail_escape($responseRateDisplay) ?></span>
            <span class="hint">Respons dibanding target broadcast</span>
        </div>
    </div>

    <?php if (!empty($focusCustomer)): ?>
        <div class="crm-campaign-focus-note">
            <div>
                <span class="label">Respons difilter untuk</span>
                <span class="value"><?= crm_campaign_detail_escape($focusCustomerName !== '' ? $focusCustomerName : 'Customer #' . (int)$focusCustomer['id']) ?></span>
                <?php if ($focusCustomerPhone !== ''): ?>
                    <span class="phone"><?= crm_campaign_detail_escape($focusCustomerPhone) ?></span>
                <?php endif; ?>
            </div>
            <a href="<?= base_url('crm/campaign-detail?id=' . (int)($campaign['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary">Tampilkan semua respons</a>
        </div>
    <?php endif; ?>

    <?php if (!$sheetStatus): ?>
        <div class="crm-campaign-empty-state">
            <div class="crm-campaign-empty-icon"><i class="bi bi-table"></i></div>
            <div class="crm-campaign-empty-copy">
                <div class="fw-600 mb-1">Data Google Sheets belum bisa ditampilkan.</div>
                <div><?= crm_campaign_detail_escape($sheetMessage !== '' ? $sheetMessage : 'Konfigurasi Google Sheets belum siap.') ?></div>
            </div>
            <?php if ($authUrl !== ''): ?>
                <a href="<?= crm_campaign_detail_escape($authUrl) ?>" class="btn btn-sm btn-primary" target="_blank" rel="noopener">
                    <i class="bi bi-google me-1"></i> Login Google
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="crm-table-toolbar mb-2 d-flex flex-wrap align-items-center gap-2 justify-content-between">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button id="crmCampaignDetailBtnColumns" type="button" class="btn btn-sm btn-outline-primary"
                    style="padding: 0px 18px !important; font-size: 13px !important; line-height: 1.2 !important; height: 36px !important;">
                    <i class="bi bi-layout-three-columns me-1"></i>
                    Tampilkan Kolom
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" id="crmCampaignDetailResetFilters"
                    style="padding: 0px 18px !important; font-size: 13px !important; line-height: 1.2 !important; height: 36px !important;">
                    <i class="bi bi-x-circle me-1"></i>
                    Reset Filter
                </button>
            </div>
            <div class="small text-muted" id="crmCampaignDetailTotalLabel">
                Total data: <span id="crmCampaignDetailTotalToolbar"><?= number_format(count($gridRows), 0, ',', '.') ?></span>
            </div>
        </div>
        <div id="crmCampaignDetailGridWrapper" class="ag-theme-quartz">
            <div id="crmCampaignDetailGrid"></div>
        </div>
        <div id="crmCampaignDetailCustomScrollbar" class="crm-custom-scrollbar">
            <div class="scrollbar-track">
                <div class="scrollbar-thumb"></div>
            </div>
        </div>
        <div id="crmCampaignDetailFallback" class="table-responsive d-none">
            <table class="table table-sm align-middle mb-0 crm-campaign-fallback-table">
                <thead>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <th><?= crm_campaign_detail_escape($column['headerName']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($gridRows)): ?>
                        <tr>
                            <td colspan="<?= max(count($columns), 1) ?>" class="text-muted">Belum ada data di Google Sheets.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($gridRows as $row): ?>
                            <tr>
                                <?php foreach ($columns as $column): ?>
                                    <td><?= crm_campaign_detail_escape($row[$column['field']] ?? '') ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
    .crm-campaign-detail-page {
        color: #202734;
    }

    .crm-campaign-detail-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        margin-bottom: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }

    .crm-campaign-detail-title {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 0;
    }

    .crm-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        border: 1px solid #d8dee8;
        border-radius: 6px;
        color: #334155;
        background: #f8fafc;
        text-decoration: none;
    }

    .crm-icon-btn:hover {
        color: #0d6efd;
        border-color: #9cc7ff;
        background: #f0f7ff;
    }

    .crm-campaign-title-text {
        min-width: 0;
    }

    .crm-campaign-eyebrow {
        margin-bottom: 2px;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0;
    }

    .crm-campaign-title-text h4 {
        margin: 0;
        color: #111827;
        font-size: 22px;
        font-weight: 700;
        line-height: 1.25;
        word-break: break-word;
    }

    .crm-campaign-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }

    .crm-campaign-meta-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 14px;
    }

    .crm-campaign-meta-item {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        min-height: 76px;
        padding: 12px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: #fff;
    }

    .crm-campaign-meta-item .label {
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0;
    }

    .crm-campaign-meta-item .value {
        min-width: 0;
        margin-top: 4px;
        color: #1f2937;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.2;
        text-align: left;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .crm-campaign-meta-item .hint {
        margin-top: 4px;
        color: #64748b;
        font-size: 12px;
        line-height: 1.3;
    }

    .crm-campaign-focus-note {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
        padding: 10px 12px;
        border: 1px solid #dbeafe;
        border-radius: 6px;
        background: #eff6ff;
    }

    .crm-campaign-focus-note .label {
        display: block;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 600;
    }

    .crm-campaign-focus-note .value {
        color: #1f2937;
        font-size: 14px;
        font-weight: 700;
    }

    .crm-campaign-focus-note .phone {
        margin-left: 8px;
        color: #64748b;
        font-size: 13px;
    }

    #crmCampaignDetailGridWrapper {
        height: 70vh;
        position: relative;
        width: 100%;
    }

    #crmCampaignDetailGrid {
        width: 100%;
        height: 100%;
    }

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

    #crmCampaignDetailGridWrapper .ag-body-horizontal-scroll {
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

    .crm-campaign-detail-page .hdr-filter {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .crm-campaign-detail-page .hdr-filter .hdr-title {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .crm-campaign-detail-page .hdr-filter .btn-filter {
        border: none;
        background: transparent;
        padding: 0;
        cursor: pointer;
        color: #6c757d;
        font-size: 14px;
    }

    .crm-campaign-detail-page .hdr-filter .btn-filter.active {
        color: #0d6efd;
    }

    .crm-campaign-status {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        min-height: 24px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }

    .crm-campaign-status.success {
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #166534;
    }

    .crm-campaign-status.failed {
        border: 1px solid #fecaca;
        background: #fef2f2;
        color: #991b1b;
    }

    .crm-campaign-status.pending {
        border: 1px solid #fde68a;
        background: #fffbeb;
        color: #92400e;
    }

    .crm-campaign-source {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        padding: 3px 9px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }

    .crm-campaign-source.crm {
        border: 1px solid #b9d9f2;
        background: #eef8ff;
        color: #1f4f70;
    }

    .crm-campaign-source.outside {
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        color: #475569;
    }

    .crm-campaign-detail-page .ag-row.crm-row-in-system {
        background-color: #f0fdf4 !important;
    }

    .crm-campaign-detail-page .ag-row.crm-row-in-system:hover {
        background-color: #dcfce7 !important;
    }

    .crm-campaign-detail-page .ag-row.crm-row-in-system .ag-cell {
        border-color: #d8f3df;
    }

    .crm-campaign-empty-state {
        display: flex;
        align-items: center;
        gap: 14px;
        min-height: 120px;
        padding: 18px;
        border: 1px solid #fde68a;
        border-radius: 6px;
        background: #fffbeb;
        color: #3f3430;
    }

    .crm-campaign-empty-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        border-radius: 6px;
        background: #fff7d6;
        color: #b45309;
        font-size: 20px;
    }

    .crm-campaign-empty-copy {
        flex: 1 1 auto;
        min-width: 0;
    }

    .crm-sheet-filter-panel {
        position: absolute;
        z-index: 1100;
        width: 260px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 12px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18);
    }

    .crm-sheet-filter-panel input.form-control {
        height: 32px;
        border-radius: 6px;
        font-size: 13px;
    }

    .crm-sheet-filter-panel .list {
        max-height: 260px;
        overflow: auto;
        padding: 4px 0;
    }

    .crm-sheet-filter-panel label {
        display: flex !important;
        align-items: center;
        gap: 7px;
        min-height: 28px;
        margin: 0;
        padding: 4px 6px;
        border-radius: 5px;
        color: #334155;
    }

    .crm-sheet-filter-panel label:hover {
        background: #f8fafc;
    }

    .crm-campaign-fallback-table {
        border: 1px solid #e5e7eb;
        background: #fff;
    }

    .crm-campaign-fallback-table thead th {
        background: #f8fafc;
        color: #263244;
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .crm-campaign-detail-header,
        .crm-campaign-empty-state {
            align-items: stretch;
            flex-direction: column;
        }

        .crm-campaign-actions {
            justify-content: flex-start;
        }

        .crm-campaign-focus-note {
            align-items: stretch;
            flex-direction: column;
        }

        .crm-campaign-meta-row {
            grid-template-columns: 1fr;
        }

    }
</style>

<?php if ($sheetStatus): ?>
<script>
(function () {
    const rowData = <?= json_encode($gridRows, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const columnMeta = <?= json_encode($columns, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const filters = Object.create(null);
    const headerInstances = Object.create(null);

    columnMeta.forEach(col => {
        filters[col.field] = new Set();
    });

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

    function distinctValues(field) {
        const values = new Set();
        rowData.forEach(row => {
            values.add(String(row[field] == null ? '' : row[field]));
        });
        return Array.from(values).sort((a, b) => a.localeCompare(b, 'id'));
    }

    function filteredRows() {
        return rowData.filter(row => {
            return Object.keys(filters).every(field => {
                const selected = filters[field];
                if (!selected || selected.size === 0) {
                    return true;
                }
                return selected.has(String(row[field] == null ? '' : row[field]));
            });
        });
    }

    function refreshTotal(rows) {
        const total = document.getElementById('crmCampaignDetailTotal');
        if (total) {
            total.textContent = Number(rows.length || 0).toLocaleString('id-ID');
        }
        const totalToolbar = document.getElementById('crmCampaignDetailTotalToolbar');
        if (totalToolbar) {
            totalToolbar.textContent = Number(rows.length || 0).toLocaleString('id-ID');
        }
    }

    function applyFilters() {
        const rows = filteredRows();
        if (window.crmCampaignDetailGridApi) {
            if (typeof window.crmCampaignDetailGridApi.setGridOption === 'function') {
                window.crmCampaignDetailGridApi.setGridOption('rowData', rows);
            } else if (typeof window.crmCampaignDetailGridApi.setRowData === 'function') {
                window.crmCampaignDetailGridApi.setRowData(rows);
            }
        }
        refreshTotal(rows);
        setTimeout(() => {
            if (typeof window.updateCrmCampaignDetailScrollbar === 'function') {
                window.updateCrmCampaignDetailScrollbar();
            }
        }, 80);
        Object.values(headerInstances).forEach(instance => {
            if (instance && typeof instance.updateActiveState === 'function') {
                instance.updateActiveState();
            }
        });
    }

    function closeAllDropdowns(exceptKey) {
        Object.keys(headerInstances).forEach(key => {
            if (key !== exceptKey && headerInstances[key] && typeof headerInstances[key].closeDropdown === 'function') {
                headerInstances[key].closeDropdown();
            }
        });
    }

    function statusClass(value) {
        const text = String(value || '').toLowerCase();
        if (text.includes('success') || text.includes('sent') || text.includes('terkirim') || text.includes('berhasil')) {
            return 'success';
        }
        if (text.includes('fail') || text.includes('gagal') || text.includes('error')) {
            return 'failed';
        }
        return 'pending';
    }

    function sourceClass(value) {
        return String(value || '').toLowerCase().includes('customer crm') ? 'crm' : 'outside';
    }

    function HeaderFilterComponent() {}
    HeaderFilterComponent.prototype.init = function (params) {
        this.params = params;
        const def = params.column.getColDef();
        this.field = def.field;
        this.title = params.displayName || def.headerName || this.field;
        this.pending = new Set(filters[this.field] ? Array.from(filters[this.field]) : []);
        this.dropdown = null;
        this.isOpen = false;

        const eGui = this.eGui = document.createElement('div');
        eGui.className = 'hdr-filter';

        const titleEl = document.createElement('span');
        titleEl.className = 'hdr-title';
        titleEl.textContent = this.title;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-filter bi bi-funnel';
        btn.addEventListener('click', event => {
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
        headerInstances[this.field] = this;
        this.updateActiveState();
    };

    HeaderFilterComponent.prototype.getGui = function () {
        return this.eGui;
    };

    HeaderFilterComponent.prototype.updateActiveState = function () {
        if (!this.btn) {
            return;
        }
        const selected = filters[this.field];
        this.btn.classList.toggle('active', !!selected && selected.size > 0);
    };

    HeaderFilterComponent.prototype.openDropdown = function (anchor) {
        closeAllDropdowns(this.field);
        this.pending = new Set(filters[this.field] ? Array.from(filters[this.field]) : []);
        const values = distinctValues(this.field);
        const panel = document.createElement('div');
        panel.className = 'crm-sheet-filter-panel';
        panel.innerHTML = `
            <div class="mb-2 small fw-600">${escHtml(this.title)}</div>
            <input type="text" class="form-control form-control-sm mb-2" placeholder="Cari...">
            <div class="list"></div>
            <div class="d-flex justify-content-between gap-2 mt-2">
                <button type="button" class="btn btn-sm btn-light" data-role="clear">Bersihkan</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light" data-role="cancel">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" data-role="apply">Terapkan</button>
                </div>
            </div>
        `;
        document.body.appendChild(panel);

        const rect = anchor.getBoundingClientRect();
        panel.style.top = `${rect.bottom + window.scrollY + 6}px`;
        panel.style.left = `${Math.max(8, rect.left + window.scrollX - 220)}px`;

        const searchInput = panel.querySelector('input');
        const listEl = panel.querySelector('.list');
        const renderList = () => {
            const keyword = String(searchInput.value || '').toLowerCase();
            const shown = values.filter(value => value.toLowerCase().includes(keyword)).slice(0, 300);
            listEl.innerHTML = shown.map(value => {
                const checked = this.pending.has(value) ? 'checked' : '';
                const label = value === '' ? '-' : value;
                return `
                    <label class="d-block mb-1 small">
                        <input type="checkbox" value="${escHtml(value)}" ${checked}>
                        ${escHtml(label)}
                    </label>
                `;
            }).join('') || '<div class="small text-muted">Tidak ada opsi.</div>';
            listEl.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.addEventListener('change', event => {
                    const value = event.target.value;
                    if (event.target.checked) {
                        this.pending.add(value);
                    } else {
                        this.pending.delete(value);
                    }
                });
            });
        };

        searchInput.addEventListener('input', renderList);
        panel.querySelector('[data-role="clear"]').addEventListener('click', () => {
            this.pending.clear();
            renderList();
        });
        panel.querySelector('[data-role="cancel"]').addEventListener('click', () => this.closeDropdown());
        panel.querySelector('[data-role="apply"]').addEventListener('click', () => {
            filters[this.field] = new Set(this.pending);
            this.closeDropdown();
            applyFilters();
        });

        this.dropdown = panel;
        this.isOpen = true;
        renderList();

        setTimeout(() => {
            this.outsideHandler = event => {
                if (this.dropdown && !this.dropdown.contains(event.target) && !anchor.contains(event.target)) {
                    this.closeDropdown();
                }
            };
            document.addEventListener('click', this.outsideHandler);
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
        if (this.outsideHandler) {
            document.removeEventListener('click', this.outsideHandler);
            this.outsideHandler = null;
        }
    };

    HeaderFilterComponent.prototype.destroy = function () {
        this.closeDropdown();
        delete headerInstances[this.field];
    };

    function initFallback() {
        const wrapper = document.getElementById('crmCampaignDetailGridWrapper');
        const fallback = document.getElementById('crmCampaignDetailFallback');
        if (wrapper) {
            wrapper.classList.add('d-none');
        }
        if (fallback) {
            fallback.classList.remove('d-none');
        }
    }

    if (!window.agGrid) {
        initFallback();
        return;
    }

    const sourceColumn = columnMeta.find(col => String(col.headerName || '').toLowerCase() === 'asal data');
    const sourceField = sourceColumn ? sourceColumn.field : '';
    const columnDefs = columnMeta.map(col => {
        const isStatusColumn = String(col.headerName || '').toLowerCase() === 'status';
        const isSourceColumn = String(col.headerName || '').toLowerCase() === 'asal data';
        const isCustomerIdColumn = String(col.headerName || '').toLowerCase() === 'id customer crm';
        return {
            field: col.field,
            headerName: col.headerName,
            minWidth: isStatusColumn ? 130 : 150,
            flex: isStatusColumn ? 0.8 : 1,
            sortable: false,
            resizable: true,
            filter: false,
            headerComponent: HeaderFilterComponent,
            cellRenderer: params => {
                const value = params.value || '';
                if (isStatusColumn && value) {
                    return `<span class="crm-campaign-status ${statusClass(value)}">${escHtml(value)}</span>`;
                }
                if (isSourceColumn && value) {
                    return `<span class="crm-campaign-source ${sourceClass(value)}">${escHtml(value)}</span>`;
                }
                if (isCustomerIdColumn && value) {
                    return `<a href="<?= base_url('crm/detail') ?>?id=${encodeURIComponent(value)}&brand=" class="text-blue">${escHtml(value)}</a>`;
                }
                return `<div class="text-wrap">${escHtml(value)}</div>`;
            },
            autoHeight: true,
            wrapText: true
        };
    });

    const gridElement = document.getElementById('crmCampaignDetailGrid');
    if (!gridElement) {
        initFallback();
        return;
    }

    window.crmCampaignDetailGridApi = agGrid.createGrid(gridElement, {
        theme: 'legacy',
        rowData: rowData,
        columnDefs: columnDefs,
        defaultColDef: {
            resizable: true,
            sortable: false,
            filter: false,
            floatingFilter: false
        },
        animateRows: true,
        suppressCellFocus: true,
        overlayNoRowsTemplate: '<span class="text-muted">Belum ada data di Google Sheets.</span>',
        getRowClass: params => {
            if (!sourceField || !params.data) {
                return '';
            }
            return String(params.data[sourceField] || '').toLowerCase().includes('customer crm') ? 'crm-row-in-system' : '';
        },
        onGridReady: params => {
            setTimeout(() => {
                params.api.sizeColumnsToFit({ defaultMinWidth: 120 });
                if (typeof window.updateCrmCampaignDetailScrollbar === 'function') {
                    window.updateCrmCampaignDetailScrollbar();
                }
            }, 50);
        }
    });

    const resetBtn = document.getElementById('crmCampaignDetailResetFilters');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            Object.keys(filters).forEach(field => filters[field].clear());
            applyFilters();
        });
    }

    (function initCustomScrollbar() {
        const gridWrapper = document.getElementById('crmCampaignDetailGridWrapper');
        const customScrollbar = document.getElementById('crmCampaignDetailCustomScrollbar');
        const scrollbarTrack = customScrollbar ? customScrollbar.querySelector('.scrollbar-track') : null;
        const scrollbarThumb = customScrollbar ? customScrollbar.querySelector('.scrollbar-thumb') : null;
        if (!gridWrapper || !customScrollbar || !scrollbarTrack || !scrollbarThumb) {
            return;
        }

        function getGridScrollViewport() {
            return gridWrapper.querySelector('.ag-body-horizontal-scroll-viewport')
                || gridWrapper.querySelector('.ag-center-cols-viewport');
        }

        let boundViewport = null;
        function updateScrollbar() {
            const viewport = getGridScrollViewport();
            if (!viewport) {
                customScrollbar.style.display = 'none';
                return;
            }
            if (viewport !== boundViewport) {
                if (boundViewport) {
                    boundViewport.removeEventListener('scroll', updateScrollbar);
                }
                viewport.addEventListener('scroll', updateScrollbar);
                boundViewport = viewport;
            }

            const scrollWidth = viewport.scrollWidth;
            const clientWidth = viewport.clientWidth;
            if (scrollWidth <= clientWidth) {
                customScrollbar.style.display = 'none';
                return;
            }

            customScrollbar.style.display = 'block';

            const thumbWidthPercent = (clientWidth / scrollWidth) * 100;
            scrollbarThumb.style.width = thumbWidthPercent + '%';

            const maxScroll = scrollWidth - clientWidth;
            const scrollLeft = viewport.scrollLeft;
            const scrollPercent = maxScroll > 0 ? (scrollLeft / maxScroll) * 100 : 0;
            const maxThumbLeft = 100 - thumbWidthPercent;
            const thumbLeft = (scrollPercent / 100) * maxThumbLeft;
            scrollbarThumb.style.left = thumbLeft + '%';
        }

        let isDragging = false;
        let startX = 0;
        let startLeft = 0;

        scrollbarThumb.addEventListener('mousedown', event => {
            isDragging = true;
            startX = event.clientX;
            startLeft = parseFloat(scrollbarThumb.style.left) || 0;
            event.preventDefault();
        });

        document.addEventListener('mousemove', event => {
            if (!isDragging) {
                return;
            }

            const viewport = getGridScrollViewport();
            if (!viewport) {
                return;
            }

            const trackWidth = scrollbarTrack.offsetWidth;
            const deltaX = event.clientX - startX;
            const deltaPercent = (deltaX / trackWidth) * 100;
            let newLeft = startLeft + deltaPercent;

            const thumbWidthPercent = parseFloat(scrollbarThumb.style.width) || 100;
            const maxLeftPercent = Math.max(0, 100 - thumbWidthPercent);
            newLeft = Math.max(0, Math.min(newLeft, maxLeftPercent));
            scrollbarThumb.style.left = newLeft + '%';

            const scrollPercent = maxLeftPercent > 0 ? (newLeft / maxLeftPercent) * 100 : 0;
            const maxScroll = viewport.scrollWidth - viewport.clientWidth;
            viewport.scrollLeft = (scrollPercent / 100) * maxScroll;
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
        });

        scrollbarTrack.addEventListener('click', event => {
            if (event.target === scrollbarThumb) {
                return;
            }

            const viewport = getGridScrollViewport();
            if (!viewport) {
                return;
            }

            const trackRect = scrollbarTrack.getBoundingClientRect();
            const clickX = event.clientX - trackRect.left;
            const trackWidth = trackRect.width;
            const thumbWidth = scrollbarThumb.offsetWidth;

            let newThumbLeft = clickX - (thumbWidth / 2);
            newThumbLeft = Math.max(0, Math.min(newThumbLeft, trackWidth - thumbWidth));

            const newLeftPercent = (newThumbLeft / trackWidth) * 100;
            const thumbWidthPercent = parseFloat(scrollbarThumb.style.width) || 100;
            const maxLeftPercent = Math.max(0, 100 - thumbWidthPercent);
            const clampedLeft = Math.max(0, Math.min(newLeftPercent, maxLeftPercent));
            scrollbarThumb.style.left = clampedLeft + '%';

            const scrollPercent = maxLeftPercent > 0 ? (clampedLeft / maxLeftPercent) * 100 : 0;
            const maxScroll = viewport.scrollWidth - viewport.clientWidth;
            viewport.scrollLeft = (scrollPercent / 100) * maxScroll;
        });

        updateScrollbar();
        setTimeout(updateScrollbar, 50);
        setTimeout(updateScrollbar, 200);
        setTimeout(updateScrollbar, 500);
        window.addEventListener('resize', updateScrollbar);
        window.updateCrmCampaignDetailScrollbar = updateScrollbar;
    })();

    (function initColumnChooser() {
        const btn = document.getElementById('crmCampaignDetailBtnColumns');
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
            if (!window.crmCampaignDetailGridApi) {
                return;
            }
            if (isOpen) {
                closePanel();
                return;
            }

            const columns = window.crmCampaignDetailGridApi.getColumns()
                .filter(col => {
                    const def = col.getColDef();
                    return def && def.headerName;
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
                    cb.addEventListener('change', ev => {
                        const colId = ev.target.getAttribute('data-col');
                        pendingState.set(colId, !!ev.target.checked);
                    });
                });
            };

            renderList();

            const toggleAll = panel.querySelector('input[data-role="toggle-all"]');
            if (toggleAll) {
                toggleAll.checked = !Array.from(pendingState.values()).some(val => !val);
                toggleAll.addEventListener('change', ev => {
                    const visible = !!ev.target.checked;
                    columns.forEach(col => pendingState.set(col.getColId(), visible));
                    renderList();
                });
            }

            panel.querySelector('[data-role="cancel"]').addEventListener('click', closePanel);
            panel.querySelector('[data-role="apply"]').addEventListener('click', () => {
                if (!window.crmCampaignDetailGridApi) {
                    closePanel();
                    return;
                }
                const state = [];
                pendingState.forEach((visible, colId) => {
                    state.push({ colId: colId, hide: !visible });
                });
                window.crmCampaignDetailGridApi.applyColumnState({ state, applyOrder: false });
                setTimeout(() => {
                    window.crmCampaignDetailGridApi.sizeColumnsToFit({ defaultMinWidth: 120 });
                    if (typeof window.updateCrmCampaignDetailScrollbar === 'function') {
                        window.updateCrmCampaignDetailScrollbar();
                    }
                }, 80);
                closePanel();
            });

            document.body.appendChild(panel);
            isOpen = true;

            setTimeout(() => {
                outsideClickHandler = ev => {
                    if (panel && !panel.contains(ev.target) && !btn.contains(ev.target)) {
                        closePanel();
                    }
                };
                document.addEventListener('click', outsideClickHandler);
            }, 0);
        });
    })();
})();
</script>
<?php endif; ?>
