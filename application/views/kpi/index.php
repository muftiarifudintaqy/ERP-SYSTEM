<?php
function kpi_format_number($value, $decimals = 0)
{
    return number_format((float) $value, $decimals, ',', '.');
}

function kpi_metric_value($metric_key, $indikator, $metrics, $durasi = '')
{
    $metric_key = strtolower(trim((string) $metric_key));
    $indikator = strtolower(trim((string) $indikator));
    if ($metric_key === '') {
        if (strpos($indikator, 'view') !== false) $metric_key = 'total_views';
        if (strpos($indikator, 'fyp') !== false) $metric_key = 'total_fyp';
    }
    if ($metric_key === 'views') $metric_key = 'total_views';
    if ($metric_key === 'fyp') $metric_key = 'total_fyp';
    if ($metric_key === 'total_views') return (float) ($metrics['total_views'] ?? 0);
    if ($metric_key === 'total_fyp') return (float) ($metrics['total_fyp'] ?? 0);
    if ($metric_key === 'total_konten') return (float) ($metrics['total_konten'] ?? 0);
    if ($metric_key === 'total_cost') return (float) ($metrics['total_cost'] ?? 0);
    if ($metric_key === 'cpm') return (float) ($metrics['cpm'] ?? 0);
    if ($metric_key === 'crm_content_official') $metric_key = 'manual';
    return 0;
}

function kpi_metric_key_normalize($metric_key, $indikator = '')
{
    $key = strtolower(trim((string) $metric_key));
    if ($key === 'views') return 'total_views';
    if ($key === 'fyp') return 'total_fyp';
    if ($key === 'manual' || $key === 'input manual') return 'manual';
    if (in_array($key, ['total_views', 'total_fyp', 'total_konten', 'total_cost', 'cpm', 'crm_content_official'], true)) {
        return $key;
    }

    $indikator_key = strtolower(trim((string) $indikator));
    if (strpos($indikator_key, 'view') !== false) return 'total_views';
    if (strpos($indikator_key, 'fyp') !== false) return 'total_fyp';
    if (strpos($indikator_key, 'official') !== false && strpos($indikator_key, 'content') !== false) return 'manual';
    if (strpos($indikator_key, 'konten') !== false || strpos($indikator_key, 'content') !== false) return 'total_konten';
    if (strpos($indikator_key, 'cost') !== false) return 'total_cost';
    if (strpos($indikator_key, 'cpm') !== false) return 'cpm';
    return '';
}

$division_keys = array_keys($division_groups ?? []);
$active_division_key = (string) ($active_tab_key ?? (!empty($division_keys) ? $division_keys[0] : ''));
$is_kpi_readonly = !(bool) ($can_edit_kpi ?? false);
?>
<style>
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
        font-size: 12px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    table tr:first-child td:first-child {
		border-top-left-radius: 0 !important;
		border-bottom-left-radius: 0 !important;
		padding: 16px 10px !important;
		max-width: unset !important;
		min-width: unset !important;
		width: unset !important;
	}

    .table tbody td {
        font-size: 12px;
        color: rgba(0, 0, 0, 0.65);
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table tr th {
        font-size: 14px !important;
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
        min-height: 56px;
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

    /* Tab Styling - Ant Design-like */
    .nav-tabs {
        border-bottom: 1px solid #f0f0f0;
        margin-bottom: 0;
    }

    .nav-tabs .nav-link {
        border-radius: 2px 2px 0 0;
        border: 1px solid transparent;
        padding: 8px 16px;
        color: rgba(0, 0, 0, 0.65);
        background-color: transparent;
        margin-right: 2px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .nav-tabs .nav-link:hover {
        border-color: transparent;
        color: #1890ff;
        background-color: #f0f0f0;
    }

    .nav-tabs .nav-link.active {
        color: #1890ff;
        background-color: #fff;
        border-color: #f0f0f0 #f0f0f0 #fff;
        font-weight: 500;
        border-bottom: 2px solid #1890ff;
    }

    .tab-content {
        background-color: #fff;
        padding: 16px 0 0 0;
    }

    .modal-content {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }

    /* Additional buttons */
    .btn-secondary {
        background-color: #f5f5f5;
        border-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.65);
    }

    .btn-secondary:hover {
        background-color: #e6f7ff;
        border-color: #40a9ff;
        color: #40a9ff;
    }

    .dropdown-menu {
        z-index: 9999 !important;
    }

    .dropdown-menu.show {
        display: block !important;
    }
</style>
<style>
    .kpi-tab-btn {
        color: rgba(0, 0, 0, 0.75);
    }

    .kpi-tab-btn.active {
        color: #1890ff !important;
        font-weight: 600;
    }

    .kpi-add-position-btn {
        min-width: 40px;
        font-size: 18px !important;
        line-height: 1;
        font-weight: 600;
    }

    .kpi-tab-delete-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        margin-left: 6px;
        border-radius: 50%;
        color: rgba(0, 0, 0, 0.4);
        font-size: 16px;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .kpi-tab-delete-btn:hover {
        color: #fff;
        background: #ff4d4f;
    }

    .kpi-employee-toggle {
        background: #fff;
        color: rgba(0, 0, 0, 0.85);
        padding: 0.85rem 1rem;
    }

    .kpi-employee-toggle:not(.collapsed) {
        color: inherit;
        background: #fff;
        box-shadow: none;
    }

    .kpi-employee-summary {
        font-size: 11px;
        color: rgba(0, 0, 0, 0.6);
        margin-top: 2px;
        font-weight: 400;
    }

    .kpi-edit-cell {
        cursor: text;
    }

    .kpi-input {
        width: 100%;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        padding: 4px 11px;
        font-size: 14px;
        min-height: 32px;
        line-height: 1.5;
        color: rgba(0, 0, 0, 0.85);
        background: #fff;
    }

    .kpi-edit-display {
        min-height: 32px;
        border: 1px solid transparent;
        border-radius: 2px;
        padding: 4px 11px;
        line-height: 1.5;
        background: transparent;
        display: flex;
        align-items: center;
        color: rgba(0, 0, 0, 0.85);
    }

    .kpi-edit-cell:hover .kpi-edit-display {
        background: #fafafa;
        border-color: #d9d9d9;
    }

    .kpi-edit-cell.is-editing .kpi-edit-display {
        display: none;
    }

    .kpi-edit-cell .kpi-input {
        display: none;
    }

    .kpi-edit-cell.is-editing .kpi-input {
        display: block;
    }

    .kpi-detail-cell {
        font-size: 12px;
        line-height: 1.6;
        color: rgba(0, 0, 0, 0.65);
        padding: 8px 10px !important;
        vertical-align: top !important;
    }

    .kpi-detail-item {
        padding: 7px 10px;
        border-left: 3px solid #adc6ff;
        background: #f5f8ff;
        border-radius: 0 4px 4px 0;
        margin-bottom: 6px;
    }

    .kpi-detail-item:last-child {
        margin-bottom: 0;
    }

    .kpi-detail-item .kpi-detail-title {
        font-weight: 600;
        color: #1890ff;
        font-size: 12px;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 260px;
    }

    .kpi-detail-item .kpi-detail-meta {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 2px;
        justify-content: center;
    }

    .kpi-detail-item .kpi-detail-meta span {
        font-size: 11px;
        color: rgba(0,0,0,0.55);
        background: #e6f0ff;
        border-radius: 3px;
        padding: 1px 6px;
    }

    .kpi-detail-item .kpi-detail-meta span strong {
        color: rgba(0,0,0,0.75);
    }

    .kpi-detail-empty {
        color: rgba(0,0,0,0.4);
        font-style: italic;
        font-size: 12px;
        padding: 4px 0;
    }

    .kpi-total-label {
        background: #e6f0ff;
        font-weight: 600;
        text-align: center;
        color: #1890ff;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-top: 2px solid #adc6ff !important;
    }

    .kpi-total-value {
        font-weight: 700;
        text-align: center;
        font-size: 22px;
        color: #1890ff;
        background: #e6f0ff;
        border-top: 2px solid #adc6ff !important;
    }

    .kpi-total-campaign {
        background: #e6f0ff;
        border-top: 2px solid #adc6ff !important;
        font-size: 12px;
        color: rgba(0,0,0,0.55);
        vertical-align: middle !important;
        text-align: center;
    }

    .kpi-row-meta {
        display: block;
        margin-top: 3px;
        font-size: 10px;
        color: rgba(0, 0, 0, 0.5);
        line-height: 1.3;
    }

    .kpi-lock-badge {
        font-size: 10px;
        margin-left: 6px;
    }

    .kpi-status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 88px;
        height: 20px;
        padding: 0 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .2px;
        margin-left: 6px;
        border: 1px solid transparent;
    }

    .kpi-status-live {
        background: #fff7e6;
        color: #d46b08;
        border-color: #ffd591;
    }

    .kpi-status-final {
        background: #f6ffed;
        color: #389e0d;
        border-color: #b7eb8f;
    }

    .kpi-status-locked {
        background: #fff1f0;
        color: #cf1322;
        border-color: #ffa39e;
    }

    .kpi-save-info {
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        min-height: 18px;
        margin-top: 8px !important;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--multiple {
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        min-height: 32px;
    }

    .kpi-empty {
        border: 1px dashed #d9d9d9;
        border-radius: 4px;
        padding: 24px 12px;
        color: rgba(0, 0, 0, 0.4);
        text-align: center;
        background: #fafafa;
        font-size: 13px;
    }

    .kpi-metric-cards {
        display: flex;
        gap: 12px;
        margin-bottom: 12px;
    }

    .kpi-metric-card {
        flex: 1;
        background: #f5f8ff;
        border: 1px solid #d6e4ff;
        border-radius: 6px;
        padding: 10px 14px;
        text-align: center;
    }

    .kpi-metric-card .kpi-metric-label {
        font-size: 11px;
        color: rgba(0,0,0,0.5);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 2px;
    }

    .kpi-metric-card .kpi-metric-val {
        font-size: 20px;
        font-weight: 700;
        color: rgba(0,0,0,0.8);
        line-height: 1.2;
    }

    .kpi-metric-card.score-card .kpi-metric-val {
        color: #1890ff;
    }

    .kpi-template-table th,
    .kpi-template-table td {
        font-size: 12px;
        vertical-align: middle;
    }

    .kpi-template-edit-cell {
        cursor: text;
    }

    .kpi-template-edit-display {
        min-height: 30px;
        line-height: 30px;
        padding: 0 6px;
        border: 1px solid transparent;
        border-radius: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        outline: none;
    }

    .kpi-template-edit-cell:hover .kpi-template-edit-display {
        background: #fafafa;
        border-color: #d9d9d9;
    }

    .kpi-template-edit-display:focus {
        background: #fff;
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .kpi-template-number .kpi-template-edit-display {
        text-align: right;
    }

    .kpi-dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 16px;
    }

    .kpi-dashboard-card {
        border: 1px solid #f0f0f0;
        border-radius: 8px;
        background: linear-gradient(180deg, #ffffff 0%, #fafcff 100%);
        padding: 18px;
    }

    .kpi-dashboard-rank {
        font-size: 12px;
        font-weight: 700;
        color: #1890ff;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .kpi-dashboard-name {
        font-size: 18px;
        font-weight: 700;
        color: rgba(0, 0, 0, 0.85);
        margin-bottom: 4px;
    }

    .kpi-dashboard-division {
        font-size: 12px;
        color: rgba(0, 0, 0, 0.45);
        margin-bottom: 12px;
    }

    .kpi-dashboard-score {
        font-size: 28px;
        font-weight: 700;
        color: #1890ff;
        line-height: 1;
        margin-bottom: 4px;
    }

    .kpi-dashboard-caption {
        font-size: 12px;
        color: rgba(0, 0, 0, 0.45);
        margin-bottom: 12px;
    }

    .kpi-dashboard-month-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .kpi-dashboard-month {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        color: rgba(0, 0, 0, 0.65);
        border-top: 1px dashed #f0f0f0;
        padding-top: 6px;
    }

</style>

<div class="container-fluid py-3">
<form method="GET" action="<?= base_url('kpi') ?>" id="kpi-filter-form">
    <div id="kpi-result" class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Preview KPI per Karyawan</h5>
            <span class="badge bg-light text-dark border" style="font-size:13px;">Periode: <strong><?= htmlspecialchars((string) ($period_label ?? $month), ENT_QUOTES) ?></strong></span>
        </div>
        <div class="card-body">
            <?php
                $current_period_type = 'quarter';
                $is_quarter_mode = true;
                $current_year = (int) date('Y');
                $current_quarter = (int) ceil(((int) date('n')) / 3);
                $active_is_kol = ($active_division_key ?? '') === 'kol_specialist';
                $quarter_options = [];
                for ($yo = -1; $yo <= 1; $yo++) {
                    for ($qi = 1; $qi <= 4; $qi++) {
                        $quarter_options[] = sprintf('%04d-Q%d', $current_year + $yo, $qi);
                    }
                }
                $month_options = [];
                for ($yo = -1; $yo <= 1; $yo++) {
                    for ($mi = 1; $mi <= 12; $mi++) {
                        $month_options[] = sprintf('%04d-%02d', $current_year + $yo, $mi);
                    }
                }
                $quarter_value = (string) $month;
                $month_value = preg_match('/^\d{4}-\d{2}$/', (string) $month) ? (string) $month : date('Y-m');
                $dashboard_start_month_val = (string) ($dashboard_start_month ?? '');
                $dashboard_end_month_val = (string) ($dashboard_end_month ?? '');
                $dashboard_start_quarter_input = $dashboard_start_month_val;
                $dashboard_end_quarter_input = $dashboard_end_month_val;
            ?>
            <div class="row g-2 align-items-end">
                <div class="col-md-3" id="kpi-filter-month-wrap" style="<?= $active_is_kol ? 'display:none;' : '' ?>">
                    <label class="form-label mb-1">Quarter KPI</label>
                    <input type="hidden" id="kpi-period-type" name="period_type" value="quarter">
                    <input type="hidden" name="month" id="kpi-period-key" value="<?= htmlspecialchars($month, ENT_QUOTES) ?>">
                    <select id="kpi-period-quarter-input" class="form-select">
                        <?php foreach ($quarter_options as $qkey): ?>
                            <option value="<?= htmlspecialchars($qkey, ENT_QUOTES) ?>" <?= $qkey === $quarter_value ? 'selected' : '' ?>><?= htmlspecialchars(str_replace('-Q', ' Q', $qkey), ENT_QUOTES) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3" id="kpi-filter-kol-month-wrap" style="<?= $active_is_kol ? '' : 'display:none;' ?>">
                    <label class="form-label mb-1">Bulan KPI</label>
                    <select id="kpi-period-month-input" class="form-select">
                        <?php foreach ($month_options as $mkey): ?>
                            <option value="<?= htmlspecialchars($mkey, ENT_QUOTES) ?>" <?= $mkey === $month_value ? 'selected' : '' ?>><?= htmlspecialchars(date('M Y', strtotime($mkey . '-01')), ENT_QUOTES) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" value="1" id="kpi-kol-quarter-view" name="kol_quarter_view" <?= !empty($kol_quarter_view) ? 'checked' : '' ?>>
                        <label class="form-check-label small text-muted" for="kpi-kol-quarter-view">Tampilkan data quartal dari rata-rata KPI bulanan</label>
                    </div>
                </div>
                <div class="col-md-5" id="kpi-filter-dashboard-wrap">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1">Quarter Mulai</label>
                            <input type="hidden" name="dashboard_start_month" id="kpi-dash-start-key" value="<?= htmlspecialchars($dashboard_start_month_val, ENT_QUOTES) ?>">
                            <select id="kpi-dash-start-quarter" class="form-select">
                                <?php foreach ($quarter_options as $qkey): ?>
                                    <option value="<?= htmlspecialchars($qkey, ENT_QUOTES) ?>" <?= $qkey === $dashboard_start_quarter_input ? 'selected' : '' ?>><?= htmlspecialchars(str_replace('-Q', ' Q', $qkey), ENT_QUOTES) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">Quarter Selesai</label>
                            <input type="hidden" name="dashboard_end_month" id="kpi-dash-end-key" value="<?= htmlspecialchars($dashboard_end_month_val, ENT_QUOTES) ?>">
                            <select id="kpi-dash-end-quarter" class="form-select">
                                <?php foreach ($quarter_options as $qkey): ?>
                                    <option value="<?= htmlspecialchars($qkey, ENT_QUOTES) ?>" <?= $qkey === $dashboard_end_quarter_input ? 'selected' : '' ?>><?= htmlspecialchars(str_replace('-Q', ' Q', $qkey), ENT_QUOTES) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php if (!$is_kpi_readonly): ?>
                    <div class="col-md-2 ms-auto d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary kpi-open-template-btn" data-bs-toggle="modal" data-bs-target="#kpiTemplateModal">
                                Template
                            </button>
                            <button type="submit" class="btn btn-primary">Generate</button>
                    </div>
                <?php endif; ?>
            </div>

            <ul class="nav nav-tabs" id="kpiDivisionTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link kpi-tab-btn <?= $active_division_key === 'dashboard' ? 'active' : '' ?>"
                        id="tab-dashboard"
                        data-tab-key="dashboard"
                        data-bs-toggle="tab"
                        data-bs-target="#tabpane-dashboard"
                        type="button"
                        role="tab"
                        aria-controls="tabpane-dashboard"
                        aria-selected="<?= $active_division_key === 'dashboard' ? 'true' : 'false' ?>">
                        Dashboard
                    </button>
                </li>
                <?php foreach (($division_groups ?? []) as $division_key => $division): ?>
                    <?php $count_employees = (int) ($division['employee_count'] ?? count($division['employees'] ?? [])); ?>
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link kpi-tab-btn <?= $division_key === $active_division_key ? 'active' : '' ?>"
                            id="tab-<?= htmlspecialchars($division_key, ENT_QUOTES) ?>"
                            data-tab-key="<?= htmlspecialchars($division_key, ENT_QUOTES) ?>"
                            data-position-id="<?= (int) ($division['position_id'] ?? 0) ?>"
                            data-bs-toggle="tab"
                            data-bs-target="#tabpane-<?= htmlspecialchars($division_key, ENT_QUOTES) ?>"
                            type="button"
                            role="tab"
                            aria-controls="tabpane-<?= htmlspecialchars($division_key, ENT_QUOTES) ?>"
                            aria-selected="<?= $division_key === $active_division_key ? 'true' : 'false' ?>">
                            <?= htmlspecialchars($division['label'] ?? '-', ENT_QUOTES) ?>
                            <span class="badge bg-light text-dark ms-1"><?= (int) $count_employees ?></span>
                            <?php if (!$is_kpi_readonly && !in_array((int) ($division['position_id'] ?? 0), array_map('intval', $system_kpi_position_ids ?? [7, 28]), true)): ?>
                                <span
                                    class="kpi-tab-delete-btn"
                                    data-position-id="<?= (int) ($division['position_id'] ?? 0) ?>"
                                    data-position-label="<?= htmlspecialchars($division['label'] ?? '-', ENT_QUOTES) ?>"
                                    role="button"
                                    title="Hapus KPI posisi ini"
                                    aria-label="Hapus KPI posisi">&times;</span>
                            <?php endif; ?>
                        </button>
                    </li>
                <?php endforeach; ?>
                <?php if (!$is_kpi_readonly): ?>
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link kpi-add-position-btn"
                            id="kpi-add-position-btn"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#kpiTemplateModal"
                            title="Tambah KPI posisi"
                            aria-label="Tambah KPI posisi">
                            +
                        </button>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content pt-3" id="kpiDivisionTabsContent">
                <div
                    class="tab-pane fade <?= $active_division_key === 'dashboard' ? 'show active' : '' ?>"
                    id="tabpane-dashboard"
                    data-loaded="<?= $active_division_key === 'dashboard' ? '1' : '0' ?>"
                    role="tabpanel"
                    aria-labelledby="tab-dashboard">
                    <?php $dashboard = $dashboard_summary ?? null; ?>
                    <?php if ($active_division_key !== 'dashboard'): ?>
                        <div class="kpi-empty">Data belum dimuat. Klik tab ini untuk memuat data.</div>
                    <?php elseif (empty($dashboard['top_rankings'])): ?>
                        <div class="kpi-empty">Belum ada data KPI untuk periode dashboard yang dipilih.</div>
                    <?php else: ?>
                        <div class="mb-3 text-muted" style="font-size:12px;">
                            Menampilkan 3 skor KPI tertinggi periode
                            <strong><?= htmlspecialchars((string) ($dashboard['start_month'] ?? $month), ENT_QUOTES) ?></strong>
                            sampai
                            <strong><?= htmlspecialchars((string) ($dashboard['end_month'] ?? $month), ENT_QUOTES) ?></strong>
                            untuk KPI yang sudah di lock.
                        </div>
                        <div class="kpi-dashboard-grid">
                            <?php foreach (($dashboard['top_rankings'] ?? []) as $index => $item): ?>
                                <div class="kpi-dashboard-card">
                                    <div class="kpi-dashboard-rank">Top <?= $index + 1 ?></div>
                                    <div class="kpi-dashboard-name"><?= htmlspecialchars((string) ($item['full_name'] ?? '-'), ENT_QUOTES) ?></div>
                                    <div class="kpi-dashboard-division"><?= htmlspecialchars((string) ($item['division_label'] ?? '-'), ENT_QUOTES) ?></div>
                                    <div class="kpi-dashboard-score"><?= kpi_format_number((float) ($item['period_total_score'] ?? 0), 0) ?></div>
                                    <div class="kpi-dashboard-caption">Total score periode terpilih</div>
                                    <div class="kpi-dashboard-month-list">
                                        <?php foreach (($item['monthly_scores'] ?? []) as $month_score): ?>
                                            <div class="kpi-dashboard-month">
                                                <span><?= htmlspecialchars((string) ($month_score['month_key'] ?? '-'), ENT_QUOTES) ?></span>
                                                <strong><?= kpi_format_number((float) ($month_score['score'] ?? 0), 0) ?></strong>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php foreach (($division_groups ?? []) as $division_key => $division): ?>
                    <div
                        class="tab-pane fade <?= $division_key === $active_division_key ? 'show active' : '' ?>"
                        id="tabpane-<?= htmlspecialchars($division_key, ENT_QUOTES) ?>"
                        data-loaded="<?= (bool) ($division['is_loaded'] ?? true) ? '1' : '0' ?>"
                        role="tabpanel"
                        aria-labelledby="tab-<?= htmlspecialchars($division_key, ENT_QUOTES) ?>">

                        <?php if (!(bool) ($division['is_loaded'] ?? true)): ?>
                            <div class="kpi-empty">Data belum dimuat. Klik tab ini untuk memuat data.</div>
                        <?php elseif (empty($division['employees'])): ?>
                            <div class="kpi-empty">Belum ada karyawan untuk divisi ini.</div>
                        <?php else: ?>
                            <div class="accordion" id="accordion-<?= htmlspecialchars($division_key, ENT_QUOTES) ?>">
                                <?php foreach ($division['employees'] as $index => $employee): ?>
                                    <?php
                                    $employee_id = (int) ($employee['id'] ?? 0);
                                    $collapse_id = 'collapse-' . $division_key . '-' . $employee_id;
                                    $heading_id = 'heading-' . $division_key . '-' . $employee_id;
                                    $metrics = $employee['metrics'] ?? [];
                                    $kpi_rows = $employee['kpi_rows'] ?? [];
                                    $campaigns = $employee['campaigns'] ?? [];
                                    $selected_campaign_ids = array_values(array_map('intval', $employee['selected_campaign_ids'] ?? []));
                                    $campaign_details = $metrics['grouped_campaign'] ?? [];
                                    $total_score = 0;
                                    $saved_run = $employee['saved_run'] ?? null;
                                    $is_locked = (int) ($saved_run['is_locked'] ?? 0) === 1;
                                    $employee_position_id = (int) ($employee['position_id'] ?? 0);
                                    $kol_position_ids = array_map('intval', $kol_specialist_position_ids ?? []);
                                    $is_kol_specialist = in_array($employee_position_id, $kol_position_ids, true) || ($division_key === 'kol_specialist');
                                    $uses_source_data = ($employee_position_id === 7) || $is_kol_specialist;
                                    $is_virtual_quarter = !empty($employee['is_virtual_quarter']);
                                    $card_is_readonly = $is_kpi_readonly || $is_virtual_quarter;
                                    $status_label = 'LIVE';
                                    $status_class = 'kpi-status-live';
                                    if ($is_locked) {
                                        $status_label = 'LOCK';
                                        $status_class = 'kpi-status-locked';
                                    } elseif ($is_virtual_quarter) {
                                        $status_label = 'AVG Q';
                                        $status_class = 'kpi-status-final';
                                    }
                                    ?>

                                    <div
                                        class="accordion-item mb-2 border rounded kpi-employee-card"
                                        data-employee-id="<?= $employee_id ?>"
                                        data-month="<?= htmlspecialchars($month, ENT_QUOTES) ?>"
                                        data-quarter-start="<?= htmlspecialchars((string) ($period_start_date ?? ''), ENT_QUOTES) ?>"
                                        data-quarter-end="<?= htmlspecialchars((string) ($period_until_date ?? ''), ENT_QUOTES) ?>"
                                        data-readonly-card="<?= $card_is_readonly ? '1' : '0' ?>"
                                        data-locked="<?= $is_locked ? '1' : '0' ?>"
                                        data-views="<?= (float) ($metrics['total_views'] ?? 0) ?>"
                                        data-fyp="<?= (float) ($metrics['total_fyp'] ?? 0) ?>"
                                        data-total-konten="<?= (float) ($metrics['total_konten'] ?? 0) ?>"
                                        data-total-cost="<?= (float) ($metrics['total_cost'] ?? 0) ?>"
                                        data-cpm="<?= (float) ($metrics['cpm'] ?? 0) ?>">
                                        <h2 class="accordion-header" id="<?= htmlspecialchars($heading_id, ENT_QUOTES) ?>">
                                            <button
                                                class="accordion-button kpi-employee-toggle collapsed"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#<?= htmlspecialchars($collapse_id, ENT_QUOTES) ?>"
                                                aria-expanded="false"
                                                aria-controls="<?= htmlspecialchars($collapse_id, ENT_QUOTES) ?>">
                                                <div class="d-flex w-100 justify-content-between align-items-center pe-3">
                                                    <div>
                                                        <strong><?= htmlspecialchars($employee['full_name'] ?? '-', ENT_QUOTES) ?></strong>
                                                        <div class="small text-muted"><?= htmlspecialchars($division['label'] ?? '-', ENT_QUOTES) ?></div>
                                                    </div>
                                                    <div class="kpi-employee-summary text-end">
                                                       Total Score: <strong class="kpi-summary-score">0</strong>
                                                       <div class="mt-1">
                                                            <span class="kpi-status-badge kpi-status-text <?= $status_class ?>"><?= $status_label ?></span>
                                                       </div>
                                                    </div>
                                                </div>
                                            </button>
                                        </h2>

                                        <div
                                            id="<?= htmlspecialchars($collapse_id, ENT_QUOTES) ?>"
                                            class="accordion-collapse collapse"
                                            aria-labelledby="<?= htmlspecialchars($heading_id, ENT_QUOTES) ?>"
                                            data-bs-parent="#accordion-<?= htmlspecialchars($division_key, ENT_QUOTES) ?>">
                                            <div class="accordion-body">
                                                <?php if ($uses_source_data):
                                                    $preset = $employee['preset'] ?? null;
                                                    $own_preset = $employee['own_preset'] ?? null;
                                                    $preset_campaign_ids = array_values(array_map('intval', $preset['campaign_ids'] ?? []));
                                                    $preset_date_from = (string) ($preset['date_from'] ?? '');
                                                    $preset_date_until = (string) ($preset['date_until'] ?? '');
                                                    $own_preset_campaign_ids = array_values(array_map('intval', $own_preset['campaign_ids'] ?? []));
                                                    $own_preset_date_from = (string) ($own_preset['date_from'] ?? '');
                                                    $own_preset_date_until = (string) ($own_preset['date_until'] ?? '');
                                                    $own_growth_date_from = (string) ($own_preset['growth_date_from'] ?? '');
                                                    $own_growth_date_until = (string) ($own_preset['growth_date_until'] ?? '');
                                                    $is_default_source = !empty($preset['is_default']);
                                                    $own_source_rules = $own_preset['source_rules'] ?? [];
                                                    $own_source_rules_summary = $own_preset['source_rules_summary'] ?? [];
                                                ?>
                                                <div class="kpi-preset-filter mb-3 p-2" style="background:#f7f9fc; border:1px solid #e6ebf1; border-radius:6px;">
                                                    <div class="row g-2 align-items-end">
                                                        <div class="col-md-10">
                                                            <label class="form-label mb-1 small text-muted"><?= $is_kol_specialist ? 'Sumber Data KPI' : 'Sumber Data Quarter' ?></label>
                                                            <div class="small text-muted">
                                                                <?php if ($is_kol_specialist && empty($own_source_rules)): ?>
                                                                    Belum pilih sumber data KPI. Default: semua campaign pada periode ini dipakai.
                                                                <?php elseif ($is_kol_specialist): ?>
                                                                    <div>Posted: <strong><?= htmlspecialchars($own_preset_date_from !== '' ? $own_preset_date_from : '-', ENT_QUOTES) ?></strong> s/d <strong><?= htmlspecialchars($own_preset_date_until !== '' ? $own_preset_date_until : '-', ENT_QUOTES) ?></strong></div>
                                                                    <div>Kenaikan: <strong><?= htmlspecialchars($own_growth_date_from !== '' ? $own_growth_date_from : '-', ENT_QUOTES) ?></strong> s/d <strong><?= htmlspecialchars($own_growth_date_until !== '' ? $own_growth_date_until : '-', ENT_QUOTES) ?></strong></div>
                                                                    <div class="mt-1">
                                                                        <?php foreach ($own_source_rules_summary as $rule_summary): ?>
                                                                            <div>
                                                                                <?= htmlspecialchars((string) ($rule_summary['campaign_title'] ?? '-'), ENT_QUOTES) ?>
                                                                                -> <?= htmlspecialchars(implode(', ', $rule_summary['metrics'] ?? []), ENT_QUOTES) ?>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php elseif ($is_default_source): ?>
                                                                    Semua campaign internal yang tersedia pada quarter ini akan dipakai di rentang full quarter:
                                                                    <?= htmlspecialchars($preset_date_from, ENT_QUOTES) ?> s/d <?= htmlspecialchars($preset_date_until, ENT_QUOTES) ?>
                                                                <?php elseif (empty($preset_campaign_ids)): ?>
                                                                    Semua campaign internal yang tersedia pada quarter ini akan dipakai di rentang full quarter.
                                                                <?php else: ?>
                                                                    <?= count($preset_campaign_ids) ?> campaign dipilih
                                                                    <?php if ($preset_date_from !== '' && $preset_date_until !== ''): ?>
                                                                        | <?= htmlspecialchars($preset_date_from, ENT_QUOTES) ?> s/d <?= htmlspecialchars($preset_date_until, ENT_QUOTES) ?>
                                                                    <?php else: ?>
                                                                        | Full quarter
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <?php if (!$is_kpi_readonly): ?>
                                                        <div class="col-md-2 d-flex">
                                                            <button
                                                                type="button"
                                                                class="btn btn-outline-secondary btn-sm w-100 kpi-source-data-btn"
                                                                data-position-id="<?= (int) ($employee['position_id'] ?? 0) ?>"
                                                                data-employee-id="<?= $employee_id ?>"
                                                                data-employee-name="<?= htmlspecialchars((string) ($employee['full_name'] ?? '-'), ENT_QUOTES) ?>"
                                                                data-month="<?= htmlspecialchars($month, ENT_QUOTES) ?>"
                                                                data-date-from="<?= htmlspecialchars($own_preset_date_from, ENT_QUOTES) ?>"
                                                                data-date-until="<?= htmlspecialchars($own_preset_date_until, ENT_QUOTES) ?>"
                                                                data-growth-date-from="<?= htmlspecialchars($own_growth_date_from, ENT_QUOTES) ?>"
                                                                data-growth-date-until="<?= htmlspecialchars($own_growth_date_until, ENT_QUOTES) ?>"
                                                                data-campaign-ids='<?= htmlspecialchars(json_encode($own_preset_campaign_ids), ENT_QUOTES) ?>'
                                                                data-source-rules='<?= htmlspecialchars(json_encode($own_source_rules, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'
                                                                data-campaign-options='<?= htmlspecialchars(json_encode(array_values(array_map(function ($campaign) {
                                                                    return [
                                                                        'id' => (int) ($campaign['id'] ?? 0),
                                                                        'title' => (string) ($campaign['title'] ?? ''),
                                                                    ];
                                                                }, $campaigns ?? [])), JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'
                                                                <?= $is_locked ? 'disabled' : '' ?>>
                                                                Sumber Data
                                                            </button>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="small text-muted mt-1">Atur campaign dan tanggal kenaikan data yang dipakai untuk menghitung KPI karyawan ini pada quarter terpilih.</div>
                                                </div>
                                                <?php endif; ?>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 14%;">Indikator</th>
                                                                <th style="width: 13%;" class="text-center">Target</th>
                                                                <th style="width: 9%;" class="text-center">Bobot</th>
                                                                <th style="width: 15%;" class="text-center">Mapping Capaian</th>
                                                                <th style="width: 10%;" class="text-center">Durasi</th>
                                                                <th style="width: 12%;" class="text-center">Capaian</th>
                                                                <th style="width: 10%;" class="text-center">Score</th>
                                                                <?php if ($uses_source_data): ?>
                                                                    <th style="width: 17%;">Keterangan</th>
                                                                <?php endif; ?>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (empty($kpi_rows)): ?>
                                                                <tr>
                                                                    <td colspan="<?= $uses_source_data ? '8' : '7' ?>" class="text-center">Belum ada data KPI.</td>
                                                                </tr>
                                                            <?php else: ?>
                                                                <?php foreach ($kpi_rows as $row_index => $row): ?>
                                                                    <?php
                                                                    $capaian = isset($row['capaian']) ? (float) $row['capaian'] : kpi_metric_value($row['metric_key'] ?? '', $row['indikator'], $metrics, (string) ($row['durasi'] ?? ''));
                                                                    $metric_key = kpi_metric_key_normalize($row['metric_key'] ?? '', $row['indikator'] ?? '');
                                                                    $is_manual_metric = $metric_key === 'manual';
                                                                    $target = (float) ($row['target'] ?? 0);
                                                                    $bobot = (float) ($row['bobot'] ?? 0);
                                                                    $score = $target > 0 ? ($capaian / $target) * $bobot : 0;
                                                                    $total_score += $score;
                                                                    ?>
                                                                    <tr class="kpi-row" data-views="<?= (float) ($metrics['total_views'] ?? 0) ?>" data-fyp="<?= (float) ($metrics['total_fyp'] ?? 0) ?>">
                                                                        <td class="kpi-edit-cell">
                                                                            <div class="kpi-edit-display"><?= htmlspecialchars((string) $row['indikator'], ENT_QUOTES) ?></div>
                                                                            <input type="text" class="kpi-input kpi-edit" data-id="<?= (int) $row['id'] ?>" data-field="indikator" value="<?= htmlspecialchars((string) $row['indikator'], ENT_QUOTES) ?>">
                                                                        </td>
                                                                        <td class="kpi-edit-cell">
                                                                            <div class="kpi-edit-display"><?= kpi_format_number($target, 0) ?></div>
                                                                            <input type="text" class="kpi-input kpi-edit" data-id="<?= (int) $row['id'] ?>" data-field="target" value="<?= kpi_format_number($target, 0) ?>">
                                                                        </td>
                                                                        <td class="kpi-edit-cell">
                                                                            <div class="kpi-edit-display"><?= kpi_format_number($bobot, 0) ?></div>
                                                                            <input type="text" class="kpi-input kpi-edit" data-id="<?= (int) $row['id'] ?>" data-field="bobot" value="<?= kpi_format_number($bobot, 0) ?>">
                                                                        </td>
                                                                        <td class="kpi-edit-cell">
                                                                            <select
                                                                                class="form-select form-select-sm kpi-edit kpi-metric-select"
                                                                                data-id="<?= (int) $row['id'] ?>"
                                                                                data-field="metric_key"
                                                                                <?= $is_locked ? 'disabled' : '' ?>>
                                                                                <option value="total_views" <?= $metric_key === 'total_views' ? 'selected' : '' ?>>Views</option>
                                                                                <option value="cpm" <?= $metric_key === 'cpm' ? 'selected' : '' ?>>CPM</option>
                                                                                <option value="total_konten" <?= $metric_key === 'total_konten' ? 'selected' : '' ?>>Total Konten</option>
                                                                                <option value="total_cost" <?= $metric_key === 'total_cost' ? 'selected' : '' ?>>Total Cost</option>
                                                                                <option value="total_fyp" <?= $metric_key === 'total_fyp' ? 'selected' : '' ?>>FYP</option>
                                                                                <option value="manual" <?= $metric_key === 'manual' ? 'selected' : '' ?>>Input Manual</option>
                                                                            </select>
                                                                        </td>
                                                                        <td class="kpi-edit-cell">
                                                                            <div class="kpi-edit-display"><?= htmlspecialchars((string) $row['durasi'], ENT_QUOTES) ?></div>
                                                                            <input type="text" class="kpi-input kpi-edit" data-id="<?= (int) $row['id'] ?>" data-field="durasi" value="<?= htmlspecialchars((string) $row['durasi'], ENT_QUOTES) ?>">
                                                                        </td>
                                                                        <td class="kpi-edit-cell kpi-capaian-cell">
                                                                            <div class="kpi-edit-display">
                                                                                <?= kpi_format_number($capaian, 0) ?>
                                                                            </div>
                                                                            <input
                                                                                type="text"
                                                                                class="kpi-input kpi-capaian-input kpi-edit"
                                                                                data-id="<?= (int) $row['id'] ?>"
                                                                                data-field="capaian"
                                                                                <?= $is_locked ? 'disabled' : '' ?>
                                                                                value="<?= kpi_format_number($capaian, 0) ?>">
                                                                        </td>
                                                                        <td class="text-center kpi-score"><strong><?= kpi_format_number($score, 0) ?></strong></td>

                                                                        <?php if ($row_index === 0 && $uses_source_data): ?>
                                                                            <td rowspan="<?= count($kpi_rows) ?>" class="kpi-detail-cell">
                                                                                <?php if (empty($campaign_details)): ?>
                                                                                    <span class="kpi-detail-empty"><?= $is_kol_specialist ? 'Tidak ada posted content pada periode ini.' : 'Tidak ada data campaign pada filter ini.' ?></span>
                                                                                <?php else: ?>
                                                                                    <?php foreach ($campaign_details as $detail): ?>
                                                                                        <div class="kpi-detail-item">
                                                                                            <div class="kpi-detail-title" title="<?= htmlspecialchars($detail['campaign_title'], ENT_QUOTES) ?>"><?= htmlspecialchars($detail['campaign_title'], ENT_QUOTES) ?></div>
                                                                                            <div class="kpi-detail-meta">
                                                                                                <span>Views: <strong><?= kpi_format_number($detail['total_views'] ?? 0, 0) ?></strong></span>
                                                                                                <span class="text-center">
                                                                                                    Konten: 
                                                                                                    <strong>
                                                                                                        <?= (int) ($detail['total_fyp'] ?? 0) ?> / <?= (int) ($detail['total_konten'] ?? 0) ?>
                                                                                                    </strong>
                                                                                                </span>

                                                                                            </div>
                                                                                        </div>
                                                                                    <?php endforeach; ?>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        <?php endif; ?>
                                                                    </tr>
                                                                <?php endforeach; ?>

                                                                <tr>
                                                                    <td colspan="<?= $uses_source_data ? '6' : '5' ?>" class="kpi-total-label">TOTAL SCORE</td>
                                                                    <td class="kpi-total-value kpi-total-score"><?= kpi_format_number($total_score, 0) ?></td>
                                                                    <td class="kpi-total-campaign">
                                                                        <?php if (!$card_is_readonly): ?>
                                                                            <button
                                                                                type="button"
                                                                                class="btn btn-outline-secondary btn-sm kpi-lock-btn mt-1"
                                                                                data-employee-id="<?= $employee_id ?>"
                                                                                data-action="<?= $is_locked ? 'unlock' : 'lock' ?>">
                                                                                <?= $is_locked ? 'Unlock' : 'Lock' ?>
                                                                            </button>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</form>
</div>

<div class="modal fade" id="kpiTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Preset Indikator KPI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-muted" style="font-size:12px;">
                            Template periode: <strong id="kpi-template-month-label"><?= htmlspecialchars($month, ENT_QUOTES) ?></strong>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <label for="kpi-template-position" class="mb-0 text-muted" style="font-size:12px;">Posisi:</label>
                            <select id="kpi-template-position" class="form-select form-select-sm" style="min-width:220px;">
                                <?php foreach (($kpi_position_options ?? []) as $position_option): ?>
                                    <?php
                                    $option_position_id = (int) ($position_option['position_id'] ?? 0);
                                    if ($option_position_id <= 0) {
                                        continue;
                                    }
                                    ?>
                                    <option value="<?= $option_position_id ?>" <?= (int) ($default_template_position_id ?? 7) === $option_position_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($position_option['option_label'] ?? $position_option['name'] ?? ('Posisi #' . $option_position_id)), ENT_QUOTES) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="__custom_new__">+ Posisi Custom</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="kpi-template-add-row">+ Tambah Row</button>
                </div>
                <div id="kpi-template-custom-wrap" class="mb-3" style="display:none; background:#f7f9fc; border:1px solid #e6ebf1; border-radius:6px; padding:12px;">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label for="kpi-template-custom-name" class="form-label mb-1">Nama Posisi Custom</label>
                            <input type="text" id="kpi-template-custom-name" class="form-control" placeholder="Contoh: Performance Squad">
                        </div>
                        <div class="col-md-7">
                            <label for="kpi-template-custom-members" class="form-label mb-1">Karyawan dalam Posisi Ini</label>
                            <select id="kpi-template-custom-members" class="form-select" multiple></select>
                        </div>
                    </div>
                    <div class="text-muted mt-2" style="font-size:12px;">Karyawan yang dipilih akan masuk ke tab KPI custom ini dan memakai template KPI dari posisi custom tersebut.</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0 kpi-template-table">
                        <thead>
                            <tr>
                                <th style="width: 17%;">Indikator</th>
                                <th style="width: 14%;">Target</th>
                                <th style="width: 12%;">Bobot</th>
                                <th style="width: 15%;">Mapping Capaian</th>
                                <th style="width: 14%;">Dimensi</th>
                                <th style="width: 14%;">Keterangan</th>
                                <th style="width: 8%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="kpi-template-body"></tbody>
                    </table>
                </div>
                <div id="kpi-template-info" class="mt-2 text-muted" style="font-size:12px;"></div>
                <hr class="my-4">
                <div id="kpi-template-source-section">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="fw-semibold">Bulk Sumber Data KPI</div>
                            <div class="text-muted" style="font-size:12px;">Centang karyawan, lalu kamu bisa apply sumber data atau apply row template yang sedang diedit khusus ke karyawan terpilih.</div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3" id="kpi-template-bulk-simple-wrap">
                        <div class="col-md-6">
                            <label class="form-label mb-1">Campaign</label>
                            <select id="kpi-template-bulk-campaigns" class="form-select" multiple></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">Tanggal Mulai</label>
                            <input type="date" id="kpi-template-bulk-date-from" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">Tanggal Selesai</label>
                            <input type="date" id="kpi-template-bulk-date-until" class="form-control">
                        </div>
                    </div>
                    <div id="kpi-template-bulk-kol-wrap" style="display:none;">
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:40%;">Campaign</th>
                                        <th style="width:48%;">Metric KPI</th>
                                        <th style="width:12%;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="kpi-template-bulk-rule-body">
                                    <tr><td colspan="3" class="text-center text-muted">Belum ada mapping campaign.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="kpi-template-bulk-add-rule">+ Tambah Mapping Campaign</button>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label mb-1">Tanggal Mulai Posted Content</label>
                                <input type="date" id="kpi-template-bulk-kol-date-from" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label mb-1">Tanggal Selesai Posted Content</label>
                                <input type="date" id="kpi-template-bulk-kol-date-until" class="form-control">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label mb-1">Tanggal Mulai Kenaikan Content</label>
                                <input type="date" id="kpi-template-bulk-kol-growth-date-from" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label mb-1">Tanggal Selesai Kenaikan Content</label>
                                <input type="date" id="kpi-template-bulk-kol-growth-date-until" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 6%;" class="text-center">
                                        <input type="checkbox" id="kpi-template-check-all-employees">
                                    </th>
                                    <th style="width: 32%;">Karyawan</th>
                                    <th style="width: 34%;">Campaign</th>
                                    <th style="width: 15%;" class="text-center">Tanggal Mulai</th>
                                    <th style="width: 15%;" class="text-center">Tanggal Selesai</th>
                                    <th style="width: 8%;" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="kpi-template-employee-body">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Memuat data karyawan...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="kpi-template-source-info" class="mt-2 text-muted" style="font-size:12px;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" id="kpi-template-delete-position" style="display:none;">Hapus Posisi KPI</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-outline-dark" id="kpi-template-apply-selected" style="display:none;">Apply Template ke Karyawan</button>
                <button type="button" class="btn btn-outline-primary" id="kpi-template-apply-bulk">Apply Sumber Data</button>
                <button type="button" class="btn btn-primary" id="kpi-template-save">Simpan Template</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="kpiSourceDataModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sumber Data KPI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2 text-muted" style="font-size:12px;">
                    Karyawan: <strong id="kpi-source-employee-name">-</strong><br>
                    Periode: <strong id="kpi-source-month-label"><?= htmlspecialchars($month, ENT_QUOTES) ?></strong>
                </div>
                <input type="hidden" id="kpi-source-employee-id" value="">
                <input type="hidden" id="kpi-source-position-id" value="">
                <input type="hidden" id="kpi-source-month" value="<?= htmlspecialchars($month, ENT_QUOTES) ?>">
                <div class="mb-3" id="kpi-source-simple-wrap">
                    <label class="form-label">Campaign</label>
                    <select id="kpi-source-campaigns" class="form-select" multiple></select>
                </div>
                <div id="kpi-source-kol-wrap" style="display:none;">
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40%;">Campaign</th>
                                    <th style="width:48%;">Metric KPI</th>
                                    <th style="width:12%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="kpi-source-rule-body">
                                <tr><td colspan="3" class="text-center text-muted">Belum ada mapping campaign.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="kpi-source-add-rule">+ Tambah Mapping Campaign</button>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label" id="kpi-source-date-from-label">Tanggal Mulai Kenaikan Data</label>
                        <input type="date" id="kpi-source-date-from" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" id="kpi-source-date-until-label">Tanggal Selesai Kenaikan Data</label>
                        <input type="date" id="kpi-source-date-until" class="form-control">
                    </div>
                </div>
                <div class="row g-2 mt-2" id="kpi-source-kol-growth-wrap" style="display:none;">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Mulai Kenaikan Content</label>
                        <input type="date" id="kpi-source-growth-date-from" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Selesai Kenaikan Content</label>
                        <input type="date" id="kpi-source-growth-date-until" class="form-control">
                    </div>
                </div>
                <div id="kpi-source-info" class="mt-2 text-muted" style="font-size:12px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="kpi-source-save">Apply</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        const isKpiReadonly = <?= $is_kpi_readonly ? 'true' : 'false' ?>;
        const initialActiveTabKey = '<?= htmlspecialchars((string) ($active_division_key ?? 'content_creator'), ENT_QUOTES) ?>';
        const defaultTemplatePositionId = <?= (int) ($default_template_position_id ?? 7) ?>;
        const customTemplateOptionValue = '__custom_new__';
        const kpiPositionOptions = <?= json_encode(array_values(array_map(function ($row) {
            return [
                'position_id' => (int) ($row['position_id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'level_name' => (string) ($row['level_name'] ?? ''),
                'tab_key' => (string) ($row['tab_key'] ?? ''),
                'option_label' => (string) ($row['option_label'] ?? ''),
                'is_custom' => !empty($row['is_custom']),
            ];
        }, $kpi_position_options ?? [])), JSON_UNESCAPED_UNICODE) ?>;
        const assignableKpiEmployees = <?= json_encode(array_values(array_map(function ($row) {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'full_name' => (string) ($row['full_name'] ?? ''),
                'position_id' => (int) ($row['position_id'] ?? 0),
                'position_name' => (string) ($row['position_name'] ?? ''),
                'label' => (string) ($row['label'] ?? ''),
            ];
        }, $assignable_kpi_employees ?? [])), JSON_UNESCAPED_UNICODE) ?>;
        const kolSpecialistPositionIds = <?= json_encode(array_values(array_map('intval', $kol_specialist_position_ids ?? []))) ?>;
        const systemKpiPositionIds = <?= json_encode(array_values(array_map('intval', $system_kpi_position_ids ?? [7, 28]))) ?>;
        const initialPresetRows = <?= json_encode(array_values(array_map(function ($row) {
            return [
                'indikator' => (string) ($row['indikator'] ?? ''),
                'target' => (float) ($row['target'] ?? 0),
                'bobot' => (float) ($row['bobot'] ?? 0),
                'metric_key' => (string) ($row['metric_key'] ?? ''),
                'dimensi' => (string) ($row['dimensi'] ?? '3 Bulan'),
                'keterangan' => (string) ($row['keterangan'] ?? ''),
            ];
        }, $preset_rows ?? [])), JSON_UNESCAPED_UNICODE) ?>;
        let currentTemplateEmployees = [];
        let currentTemplateCampaignOptions = [];
        let currentTemplateCustomPosition = null;
        let templateOpenMode = 'active';

        function parseNumber(input) {
            if (input === null || input === undefined) return 0;
            let v = String(input).trim();
            v = v.replace(/\./g, '').replace(',', '.').replace(/[^0-9.-]/g, '');
            const n = parseFloat(v);
            return isNaN(n) ? 0 : n;
        }

        function escHtml(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function toTemplateNumber(value) {
            const s = String(value == null ? '' : value).trim();
            if (!s) return 0;
            if (/^\d{1,3}(\.\d{3})+(,\d+)?$/.test(s)) {
                const n = parseFloat(s.replace(/\./g, '').replace(',', '.'));
                return isNaN(n) ? 0 : Math.round(n);
            }
            if (/^\d{1,3}(,\d{3})+(\.\d+)?$/.test(s)) {
                const n = parseFloat(s.replace(/,/g, ''));
                return isNaN(n) ? 0 : Math.round(n);
            }
            const n = parseFloat(s.replace(',', '.'));
            return isNaN(n) ? 0 : Math.round(n);
        }

        function formatTemplateInt(value) {
            const n = toTemplateNumber(value);
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(n);
        }

        function parseTemplateDurationParts(value) {
            const raw = String(value == null ? '' : value).trim();
            const match = raw.match(/(\d+)\s*(.*)/);
            let amount = 1;
            let unit = 'Bulan';

            if (match) {
                amount = Math.max(1, parseInt(match[1], 10) || 1);
                const parsedUnit = String(match[2] || '').trim();
                if (parsedUnit) {
                    unit = parsedUnit.charAt(0).toUpperCase() + parsedUnit.slice(1).toLowerCase();
                }
            }

            if (!['Hari', 'Minggu', 'Bulan', 'Tahun'].includes(unit)) {
                unit = 'Bulan';
            }

            return {
                amount: amount,
                unit: unit
            };
        }

        function buildTemplateDurationValue(amount, unit) {
            const safeAmount = Math.max(1, toTemplateNumber(amount) || 1);
            const safeUnit = ['Hari', 'Minggu', 'Bulan', 'Tahun'].includes(String(unit || '').trim())
                ? String(unit).trim()
                : 'Bulan';
            return safeAmount + ' ' + safeUnit;
        }

        function formatTypingInt(value) {
            const digits = String(value == null ? '' : value).replace(/\D/g, '');
            if (!digits) return '';
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(parseInt(digits, 10));
        }

        function formatID(n) {
            return new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 0
            }).format(Math.round(n || 0));
        }

        function resolveCapaian(indikator, views, fyp) {
            const key = String(indikator || '').toLowerCase();
            if (key.indexOf('view') !== -1) return views;
            if (key.indexOf('fyp') !== -1) return fyp;
            return 0;
        }

        function formatDisplayByField(field, value) {
            if (field === 'target' || field === 'bobot' || field === 'capaian' || field === 'manual_capaian') {
                return formatID(parseNumber(value));
            }
            return String(value || '').trim();
        }

        function getCardMetricSnapshot($card) {
            return {
                total_views: parseNumber($card.data('views')),
                total_fyp: parseNumber($card.data('fyp')),
                total_konten: parseNumber($card.data('total-konten')),
                total_cost: parseNumber($card.data('total-cost')),
                cpm: parseNumber($card.data('cpm'))
            };
        }

        function resolveMetricValueForRow(metricKey, metrics) {
            const key = String(metricKey || '').trim().toLowerCase();
            if (key === 'total_views' || key === 'views') return parseNumber(metrics.total_views);
            if (key === 'total_fyp' || key === 'fyp') return parseNumber(metrics.total_fyp);
            if (key === 'total_konten') return parseNumber(metrics.total_konten);
            if (key === 'total_cost') return parseNumber(metrics.total_cost);
            if (key === 'cpm') return parseNumber(metrics.cpm);
            return 0;
        }

        function recalcEmployeeCard($card) {
            let total = 0;
            const metrics = getCardMetricSnapshot($card);

            $card.find('.kpi-row').each(function() {
                const $row = $(this);
                const target = parseNumber($row.find('[data-field="target"]').val());
                const bobot = parseNumber($row.find('[data-field="bobot"]').val());
                const $capaianInput = $row.find('.kpi-capaian-input');
                let capaian = parseNumber($capaianInput.val());
                const metricKey = String($row.find('[data-field="metric_key"]').val() || '').trim();
                if (metricKey && metricKey !== 'manual') {
                    capaian = resolveMetricValueForRow(metricKey, metrics);
                }
                const score = target > 0 ? (capaian / target) * bobot : 0;

                $capaianInput.val(formatID(capaian));
                const $display = $row.find('.kpi-capaian-cell .kpi-edit-display');
                $display.text(formatID(capaian));
                $row.find('.kpi-score strong').text(formatID(score));
                total += score;
            });

            $card.find('.kpi-total-score').text(formatID(total));
            $card.find('.kpi-summary-score').text(formatID(total));
        }

        function recalcAllCards() {
            $('.kpi-employee-card').each(function() {
                recalcEmployeeCard($(this));
            });
        }

        function getSelectedCampaignIds($card) {
            const direct = $card.find('.kpi-campaign-select').val();
            if (Array.isArray(direct) && direct.length) {
                return direct;
            }
            const preset = $card.find('.kpi-preset-campaign').val();
            if (Array.isArray(preset) && preset.length) {
                return preset;
            }
            return [];
        }

        function applyCardLockState($card, nowLocked) {
            const $status = $card.find('.kpi-status-text');
            const $button = $card.find('.kpi-lock-btn');
            $status.removeClass('kpi-status-live kpi-status-final kpi-status-locked')
                .addClass(nowLocked ? 'kpi-status-locked' : 'kpi-status-live')
                .text(nowLocked ? 'LOCK' : 'LIVE');
            $card.attr('data-locked', nowLocked ? '1' : '0');
            $card.data('locked', nowLocked ? 1 : 0);
            $card.find('.kpi-capaian-input').prop('disabled', nowLocked);
            $button.data('action', nowLocked ? 'unlock' : 'lock');
            $button.text(nowLocked ? 'Unlock' : 'Lock');
        }

        function isCardReadonly($card) {
            return String($card.data('readonly-card') || '0') === '1';
        }

        $('.kpi-campaign-select').select2({
            placeholder: 'Pilih campaign',
            allowClear: true,
            width: '100%'
        });

        function initPresetCampaignSelects($scope) {
            const $root = $scope && $scope.length ? $scope : $(document);
            $root.find('.kpi-preset-campaign').each(function() {
                if ($(this).data('select2')) return;
                $(this).select2({
                    placeholder: 'Pilih campaign',
                    allowClear: true,
                    width: '100%'
                });
            });
        }
        initPresetCampaignSelects();

        if ($('#kpi-source-campaigns').length && !$('#kpi-source-campaigns').data('select2')) {
            $('#kpi-source-campaigns').select2({
                placeholder: 'Pilih campaign',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#kpiSourceDataModal')
            });
        }

        if ($('#kpi-template-bulk-campaigns').length && !$('#kpi-template-bulk-campaigns').data('select2')) {
            $('#kpi-template-bulk-campaigns').select2({
                placeholder: 'Pilih campaign',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#kpiTemplateModal')
            });
        }

        if ($('#kpi-template-custom-members').length && !$('#kpi-template-custom-members').data('select2')) {
            $('#kpi-template-custom-members').select2({
                placeholder: 'Pilih karyawan',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#kpiTemplateModal')
            });
        }

        function getCurrentMonth() {
            return $('#kpi-period-key').val() || $('input[name="month"]').val() || '<?= htmlspecialchars($month, ENT_QUOTES) ?>';
        }

        function getActiveTabKey() {
            const $active = $('#kpiDivisionTabs .kpi-tab-btn.active').first();
            return String(($active.data('tab-key')) || initialActiveTabKey || 'content_creator');
        }

        function isKolTabActive() {
            return getActiveTabKey() === 'kol_specialist';
        }

        function getCurrentPeriodType() {
            return isKolTabActive() && !$('#kpi-kol-quarter-view').is(':checked') ? 'month' : 'quarter';
        }

        function syncPeriodPickerHidden() {
            const monthValue = $('#kpi-period-month-input').val() || '';
            const quarterValue = $('#kpi-period-quarter-input').val() || '';
            $('#kpi-period-key').val(isKolTabActive() ? monthValue : quarterValue);
            $('#kpi-dash-start-key').val($('#kpi-dash-start-quarter').val() || '');
            $('#kpi-dash-end-key').val($('#kpi-dash-end-quarter').val() || '');
        }

        function syncPrimaryPeriodVisibility() {
            const showKolMonth = isKolTabActive();
            $('#kpi-filter-month-wrap').toggle(!showKolMonth);
            $('#kpi-filter-kol-month-wrap').toggle(showKolMonth);
        }

        function applyPeriodPickerVisibility() {
            syncPrimaryPeriodVisibility();
            syncPeriodPickerHidden();
        }

        $(document).on('change', '#kpi-period-quarter-input, #kpi-period-month-input, #kpi-kol-quarter-view, #kpi-dash-start-quarter, #kpi-dash-end-quarter', function() {
            syncPeriodPickerHidden();
            $('#kpi-filter-form').trigger('submit');
        });

        applyPeriodPickerVisibility();

        function setActiveTab(tabKey) {
            if (!tabKey) return;
            $('.kpi-tab-btn').each(function() {
                const isActive = String($(this).data('tab-key')) === tabKey;
                $(this).toggleClass('active', isActive);
                $(this).attr('aria-selected', isActive ? 'true' : 'false');
            });
            $('#kpiDivisionTabsContent .tab-pane').each(function() {
                const isActive = this.id === ('tabpane-' + tabKey);
                $(this).toggleClass('show active', isActive);
            });
            syncHeaderFilterVisibility(tabKey);
        }

        function syncHeaderFilterVisibility(tabKey) {
            const tab = String(tabKey || '');
            const isDashboard = tab === 'dashboard';
            const isKol = tab === 'kol_specialist';
            $('#kpi-filter-dashboard-wrap').toggle(isDashboard);
            $('#kpi-filter-month-wrap').toggle(!isDashboard && !isKol);
            $('#kpi-filter-kol-month-wrap').toggle(!isDashboard && isKol);
        }

        function buildFilterParams() {
            const params = new URLSearchParams();
            const formData = new FormData(document.getElementById('kpi-filter-form'));
            formData.forEach(function(value, key) {
                params.append(key, value);
            });
            params.set('month', getCurrentMonth());
            params.set('period_type', getCurrentPeriodType());
            params.set('dashboard_start_month', $('#kpi-dash-start-key').val() || '');
            params.set('dashboard_end_month', $('#kpi-dash-end-key').val() || '');
            return params;
        }

        function restoreCollapseForPane(paneId) {
            const state = readUiState();
            const openMap = state.openCollapseByTab || {};
            const collapseId = openMap[paneId];
            if (!collapseId) return;
            const collapseEl = document.getElementById(collapseId);
            if (!collapseEl) return;
            bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false }).show();
        }

        function getUiStateKey() {
            return 'kpi_ui_state_' + getCurrentMonth();
        }

        function readUiState() {
            try {
                const raw = sessionStorage.getItem(getUiStateKey());
                const parsed = raw ? JSON.parse(raw) : {};
                return (parsed && typeof parsed === 'object') ? parsed : {};
            } catch (e) {
                return {};
            }
        }

        function saveUiState(patch) {
            const next = Object.assign({}, readUiState(), patch || {});
            sessionStorage.setItem(getUiStateKey(), JSON.stringify(next));
        }

        function restoreUiState() {
            const state = readUiState();
            if (state.openCollapseByTab && typeof state.openCollapseByTab === 'object') {
                Object.keys(state.openCollapseByTab).forEach(function(tabPaneId) {
                    const collapseId = state.openCollapseByTab[tabPaneId];
                    const pane = document.getElementById(tabPaneId);
                    if (!pane || !collapseId) return;
                    const collapseEl = document.getElementById(collapseId);
                    if (!collapseEl) return;
                    bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false }).show();
                });
            }
        }

        function getActiveDivisionPositionId() {
            const activeVal = parseInt($('.kpi-tab-btn.active').data('position-id'), 10);
            if (activeVal > 0) return activeVal;
            return defaultTemplatePositionId;
        }

        function getTemplatePositionToken() {
            return String($('#kpi-template-position').val() || '');
        }

        function getTemplatePositionId() {
            const positionToken = getTemplatePositionToken();
            if (isNewCustomTemplatePosition(positionToken)) return 0;
            const selectedVal = parseInt(positionToken, 10);
            if (selectedVal > 0) return selectedVal;
            return defaultTemplatePositionId;
        }

        function getTemplateOptionByPositionId(positionId) {
            const targetId = parseInt(positionId || '0', 10);
            return (kpiPositionOptions || []).find(function(item) {
                return parseInt(item.position_id || '0', 10) === targetId;
            }) || null;
        }

        function isNewCustomTemplatePosition(positionToken) {
            return String(positionToken || getTemplatePositionToken()) === customTemplateOptionValue;
        }

        function isExistingCustomTemplatePosition(positionId) {
            const option = getTemplateOptionByPositionId(positionId);
            return !!(option && option.is_custom);
        }

        function isCustomTemplatePosition(positionValue) {
            if (isNewCustomTemplatePosition(positionValue)) return true;
            return isExistingCustomTemplatePosition(positionValue);
        }

        function getTabKeyForPosition(positionId) {
            const targetId = parseInt(positionId || '0', 10);
            const option = (kpiPositionOptions || []).find(function(item) {
                return parseInt(item.position_id || '0', 10) === targetId;
            });
            if (option && option.tab_key) return String(option.tab_key);
            if (targetId === 7) return 'content_creator';
            if (isKolTemplatePosition(targetId)) return 'kol_specialist';
            if (targetId === 28) return 'crm';
            return 'position_' + targetId;
        }

        function hasTabForPosition(positionId) {
            const targetId = parseInt(positionId || '0', 10);
            return $('#kpiDivisionTabs .kpi-tab-btn').filter(function() {
                return parseInt($(this).data('position-id') || '0', 10) === targetId;
            }).length > 0;
        }

        function getFirstAvailablePositionId(preferUnadded) {
            if (preferUnadded) {
                for (let i = 0; i < kpiPositionOptions.length; i++) {
                    const positionId = parseInt(kpiPositionOptions[i].position_id || '0', 10);
                    if (positionId > 0 && !kpiPositionOptions[i].is_custom && !hasTabForPosition(positionId)) {
                        return positionId;
                    }
                }
                return customTemplateOptionValue;
            }
            const firstOptionVal = parseInt($('#kpi-template-position option:first').val() || '0', 10);
            return firstOptionVal > 0 ? firstOptionVal : defaultTemplatePositionId;
        }

        function isCrmTemplatePosition(positionId) {
            return parseInt(positionId, 10) === 28;
        }

        function isKolTemplatePosition(positionId) {
            return kolSpecialistPositionIds.indexOf(parseInt(positionId || '0', 10)) !== -1;
        }

        function isSourceTemplatePosition(positionId) {
            const targetId = parseInt(positionId || '0', 10);
            return targetId === 7 || isKolTemplatePosition(targetId);
        }

        function getDefaultTemplateMetricKey(positionId) {
            return isSourceTemplatePosition(positionId) ? 'total_views' : 'manual';
        }

        function isSystemKpiPosition(positionId) {
            return systemKpiPositionIds.indexOf(parseInt(positionId || '0', 10)) !== -1;
        }

        function syncTemplateDeleteButton(positionId) {
            const canDelete = !isSystemKpiPosition(positionId) && hasTabForPosition(positionId);
            $('#kpi-template-delete-position').toggle(canDelete);
        }

        function inferMetricKeyFromIndicator(indikator) {
            const key = String(indikator || '').toLowerCase();
            if (key.indexOf('official') !== -1 && key.indexOf('content') !== -1) return 'manual';
            if (key.indexOf('view') !== -1) return 'total_views';
            if (key.indexOf('fyp') !== -1) return 'total_fyp';
            if (key.indexOf('konten') !== -1 || key.indexOf('content') !== -1) return 'total_konten';
            if (key.indexOf('cost') !== -1) return 'total_cost';
            if (key.indexOf('cpm') !== -1) return 'cpm';
            return 'total_views';
        }

        function renderTemplateRows(rows) {
            const $tbody = $('#kpi-template-body');
            $tbody.empty();
            const safeRows = Array.isArray(rows) && rows.length ? rows : [{
                indikator: '',
                target: 0,
                bobot: 0,
                metric_key: getDefaultTemplateMetricKey(getTemplatePositionId()),
                dimensi: '3 Bulan',
                keterangan: ''
            }];

            safeRows.forEach(function(row) {
                const indikator = row.indikator || '';
                const target = formatTemplateInt(row.target || 0);
                const bobot = formatTemplateInt(row.bobot || 0);
                const metricKey = row.metric_key || inferMetricKeyFromIndicator(indikator);
                const dimensi = parseTemplateDurationParts(row.dimensi || '3 Bulan');
                const keterangan = row.keterangan || '';
                const tr = `
                    <tr>
                        <td class="kpi-template-edit-cell">
                            <div class="kpi-template-edit-display kpi-template-input" contenteditable="true" data-field="indikator" data-type="text">${escHtml(indikator)}</div>
                        </td>
                        <td class="kpi-template-edit-cell kpi-template-number">
                            <div class="kpi-template-edit-display kpi-template-input" contenteditable="true" data-field="target" data-type="number">${escHtml(target)}</div>
                        </td>
                        <td class="kpi-template-edit-cell kpi-template-number">
                            <div class="kpi-template-edit-display kpi-template-input" contenteditable="true" data-field="bobot" data-type="number">${escHtml(bobot)}</div>
                        </td>
                        <td>
                            <select class="form-select form-select-sm kpi-template-metric" data-field="metric_key">
                                <option value="total_views" ${metricKey === 'total_views' || metricKey === 'views' ? 'selected' : ''}>Views</option>
                                <option value="cpm" ${metricKey === 'cpm' ? 'selected' : ''}>CPM</option>
                                <option value="total_konten" ${metricKey === 'total_konten' ? 'selected' : ''}>Total Konten</option>
                                <option value="total_cost" ${metricKey === 'total_cost' ? 'selected' : ''}>Total Cost</option>
                                <option value="total_fyp" ${metricKey === 'total_fyp' || metricKey === 'fyp' ? 'selected' : ''}>FYP</option>
                                <option value="manual" ${metricKey === 'manual' || metricKey === 'crm_content_official' ? 'selected' : ''}>Input Manual</option>
                            </select>
                        </td>
                        <td class="kpi-template-edit-cell">
                            <div class="d-flex align-items-center gap-2">
                                <div class="kpi-template-edit-display kpi-template-input flex-grow-1" contenteditable="true" data-field="dimensi_value" data-type="number">${escHtml(formatTemplateInt(dimensi.amount))}</div>
                                <select class="form-select form-select-sm" data-field="dimensi_unit" style="min-width: 92px;">
                                    <option value="Hari" ${dimensi.unit === 'Hari' ? 'selected' : ''}>Hari</option>
                                    <option value="Minggu" ${dimensi.unit === 'Minggu' ? 'selected' : ''}>Minggu</option>
                                    <option value="Bulan" ${dimensi.unit === 'Bulan' ? 'selected' : ''}>Bulan</option>
                                    <option value="Tahun" ${dimensi.unit === 'Tahun' ? 'selected' : ''}>Tahun</option>
                                </select>
                            </div>
                        </td>
                        <td class="kpi-template-edit-cell">
                            <div class="kpi-template-edit-display kpi-template-input" contenteditable="true" data-field="keterangan" data-type="text">${escHtml(keterangan)}</div>
                        </td>
                        <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm kpi-template-remove-row">Hapus</button></td>
                    </tr>
                `;
                $tbody.append(tr);
            });
        }

        function collectTemplateRows() {
            const rows = [];
            $('#kpi-template-body tr').each(function() {
                const $tr = $(this);
                rows.push({
                    indikator: $tr.find('[data-field="indikator"]').text().trim(),
                    target: toTemplateNumber($tr.find('[data-field="target"]').text().trim()),
                    bobot: toTemplateNumber($tr.find('[data-field="bobot"]').text().trim()),
                    metric_key: $tr.find('[data-field="metric_key"]').val() || '',
                    dimensi: buildTemplateDurationValue(
                        $tr.find('[data-field="dimensi_value"]').text().trim(),
                        $tr.find('[data-field="dimensi_unit"]').val()
                    ),
                    keterangan: $tr.find('[data-field="keterangan"]').text().trim()
                });
            });
            return rows;
        }

        function renderCustomMemberOptions(selectedIds) {
            const normalizedSelectedIds = Array.isArray(selectedIds) ? selectedIds.map(function(id) {
                return String(id);
            }) : [];
            const $select = $('#kpi-template-custom-members');
            $select.empty();
            (assignableKpiEmployees || []).forEach(function(employee) {
                const option = new Option(
                    String(employee.label || employee.full_name || ''),
                    String(employee.id || ''),
                    false,
                    normalizedSelectedIds.indexOf(String(employee.id || '')) !== -1
                );
                $select.append(option);
            });
            $select.trigger('change');
        }

        function syncCustomPositionEditor(customPosition) {
            const shouldShow = isCustomTemplatePosition();
            currentTemplateCustomPosition = shouldShow ? (customPosition || currentTemplateCustomPosition || null) : null;
            $('#kpi-template-custom-wrap').toggle(shouldShow);
            if (!shouldShow) {
                $('#kpi-template-custom-name').val('');
                renderCustomMemberOptions([]);
                return;
            }

            const payload = customPosition || currentTemplateCustomPosition || {};
            $('#kpi-template-custom-name').val(String(payload.name || ''));
            renderCustomMemberOptions(payload.member_employee_ids || []);
        }

        function loadTemplateRows(month, positionId) {
            if (isNewCustomTemplatePosition()) {
                renderTemplateRows([]);
                currentTemplateEmployees = [];
                currentTemplateCampaignOptions = [];
                currentTemplateCustomPosition = {
                    name: '',
                    member_employee_ids: []
                };
                renderTemplateEmployeeRows([]);
                renderTemplateBulkCampaignOptions([]);
                resetTemplateBulkEditor(positionId);
                syncCustomPositionEditor(currentTemplateCustomPosition);
                $('#kpi-template-info').text('Isi nama posisi custom, pilih karyawan, lalu simpan template.');
                $('#kpi-template-source-info').text('');
                syncTemplateDeleteButton(positionId);
                return;
            }

            $('#kpi-template-info').text('Memuat template...');
            $('#kpi-template-source-info').text('Memuat data karyawan...');
            syncTemplateDeleteButton(positionId);
            $.ajax({
                url: '<?= base_url('kpi/template-rows') ?>',
                method: 'GET',
                dataType: 'json',
                data: {
                    month: month,
                    position_id: positionId
                },
                success: function(res) {
                    if (res && res.status) {
                        renderTemplateRows(res.data || []);
                        currentTemplateEmployees = Array.isArray(res.employees) ? res.employees : [];
                        currentTemplateCampaignOptions = Array.isArray(res.campaign_options) ? res.campaign_options : [];
                        currentTemplateCustomPosition = res.custom_position || null;
                        renderTemplateEmployeeRows(currentTemplateEmployees);
                        renderTemplateBulkCampaignOptions(currentTemplateCampaignOptions);
                        resetTemplateBulkEditor(positionId);
                        syncCustomPositionEditor(currentTemplateCustomPosition);
                        $('#kpi-template-info').text('');
                    } else {
                        renderTemplateRows([]);
                        currentTemplateEmployees = [];
                        currentTemplateCampaignOptions = [];
                        currentTemplateCustomPosition = null;
                        renderTemplateEmployeeRows([]);
                        renderTemplateBulkCampaignOptions([]);
                        resetTemplateBulkEditor(positionId);
                        syncCustomPositionEditor(null);
                        $('#kpi-template-info').text('Gagal memuat template.');
                        $('#kpi-template-source-info').text('Gagal memuat data karyawan.');
                    }
                },
                error: function() {
                    renderTemplateRows([]);
                    currentTemplateEmployees = [];
                    currentTemplateCampaignOptions = [];
                    currentTemplateCustomPosition = null;
                    renderTemplateEmployeeRows([]);
                    renderTemplateBulkCampaignOptions([]);
                    resetTemplateBulkEditor(positionId);
                    syncCustomPositionEditor(null);
                    $('#kpi-template-info').text('Gagal memuat template.');
                    $('#kpi-template-source-info').text('Gagal memuat data karyawan.');
                }
            });
        }

        function loadTemplateRowsForEmployee(employeeId, done) {
            const month = getCurrentMonth();
            const positionId = getTemplatePositionId();
            const normalizedEmployeeId = parseInt(employeeId || '0', 10);
            const requestData = {
                month: month,
                position_id: positionId
            };
            if (normalizedEmployeeId > 0) {
                requestData.employee_id = normalizedEmployeeId;
            }

            $('#kpi-template-info').text(normalizedEmployeeId > 0 ? 'Memuat indikator KPI karyawan...' : 'Memuat template...');
            $.ajax({
                url: '<?= base_url('kpi/template-rows') ?>',
                method: 'GET',
                dataType: 'json',
                data: requestData,
                success: function(res) {
                    if (res && res.status) {
                        renderTemplateRows(res.data || []);
                        if (typeof done === 'function') done(true, res);
                        return;
                    }
                    if (typeof done === 'function') done(false, res);
                },
                error: function() {
                    if (typeof done === 'function') done(false, null);
                }
            });
        }

        function renderTemplateEmployeeRows(rows) {
            const $tbody = $('#kpi-template-employee-body');
            $tbody.empty();
            const positionId = getTemplatePositionId();
            const isCc = parseInt(positionId, 10) === 7;
            const isKol = isKolTemplatePosition(positionId);
            $('#kpi-template-source-section').toggle(isCc || isKol);
            $('#kpi-template-apply-bulk').toggle(isCc || isKol);
            $('#kpi-template-apply-selected').toggle(isCc || isKol);
            $('#kpi-template-bulk-simple-wrap').toggle(isCc);
            $('#kpi-template-bulk-kol-wrap').toggle(isKol);
            if (!isCc && !isKol) {
                return;
            }
            if (!Array.isArray(rows) || !rows.length) {
                $tbody.append('<tr><td colspan="6" class="text-center text-muted">Belum ada karyawan untuk posisi ini.</td></tr>');
                return;
            }
            rows.forEach(function(row) {
                const locked = parseInt(row.is_locked || 0, 10) === 1;
                const isDefaultSource = parseInt(row.is_default_source || 0, 10) === 1;
                const status = locked ? 'LOCK' : 'LIVE';
                const disabled = locked ? 'disabled' : '';
                let campaignTitles = Array.isArray(row.campaign_titles) && row.campaign_titles.length
                    ? row.campaign_titles.map(function(title) { return escHtml(title); }).join('<br>')
                    : '<span class="text-muted">Semua campaign quarter</span>';
                if (isKol) {
                    const kolRules = Array.isArray(row.source_rules_summary) && row.source_rules_summary.length
                        ? row.source_rules_summary.map(function(rule) {
                            const metrics = Array.isArray(rule.metrics) ? rule.metrics.join(', ') : '';
                            return `${escHtml(rule.campaign_title || '-')} <span class="text-muted">-> ${escHtml(metrics || '-')}</span>`;
                        }).join('<br>')
                        : '<span class="text-muted">Belum pilih sumber data KPI, default semua campaign periode ini.</span>';
                    const postedLine = `Posted: <span class="text-muted">${escHtml(row.date_from || '-')} s/d ${escHtml(row.date_until || '-')}</span>`;
                    const growthLine = `Kenaikan: <span class="text-muted">${escHtml(row.growth_date_from || '-')} s/d ${escHtml(row.growth_date_until || '-')}</span>`;
                    campaignTitles = `${postedLine}<br>${growthLine}<div class="mt-1">${kolRules}</div>`;
                } else if (isDefaultSource) {
                    campaignTitles = `<span class="text-muted">Belum pilih sumber data, default: ${campaignTitles}</span>`;
                }
                $tbody.append(`
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="kpi-template-employee-check" value="${escHtml(row.id)}" ${disabled}>
                        </td>
                        <td>${escHtml(row.full_name || '-')}</td>
                        <td>${campaignTitles}</td>
                        <td class="text-center">${escHtml(row.date_from || '-')}</td>
                        <td class="text-center">${escHtml(row.date_until || '-')}</td>
                        <td class="text-center">${escHtml(status)}</td>
                    </tr>
                `);
            });
        }

        function renderTemplateBulkCampaignOptions(options) {
            const $select = $('#kpi-template-bulk-campaigns');
            $select.empty();
            (options || []).forEach(function(option) {
                $select.append(new Option(String(option.title || ''), String(option.id || '')));
            });
            $select.trigger('change');
            renderRuleRows($('#kpi-template-bulk-rule-body'), options || [], []);
        }

        function renderRuleRows($tbody, options, rules) {
            $tbody.empty();
            const items = Array.isArray(rules) && rules.length ? rules : [];
            if (!items.length) {
                $tbody.append('<tr><td colspan="3" class="text-center text-muted">Belum ada mapping campaign.</td></tr>');
                return;
            }
            items.forEach(function(rule, index) {
                const selectedCampaignId = String(rule.campaign_id || '');
                const metrics = Array.isArray(rule.metrics) ? rule.metrics : [];
                const campaignOptions = (options || []).map(function(option) {
                    return `<option value="${escHtml(option.id)}" ${String(option.id) === selectedCampaignId ? 'selected' : ''}>${escHtml(option.title || '')}</option>`;
                }).join('');
                const metricChecks = [
                    ['total_views', 'Views'],
                    ['total_fyp', 'FYP'],
                    ['total_konten', 'Total Konten'],
                    ['total_cost', 'Total Cost'],
                    ['cpm', 'CPM']
                ].map(function(metric) {
                    const checked = metrics.indexOf(metric[0]) !== -1 ? 'checked' : '';
                    return `<label class="me-2"><input type="checkbox" class="kpi-rule-metric" value="${metric[0]}" ${checked}> ${metric[1]}</label>`;
                }).join('');
                $tbody.append(`
                    <tr data-rule-index="${index}">
                        <td><select class="form-select form-select-sm kpi-rule-campaign"><option value="">Pilih campaign</option>${campaignOptions}</select></td>
                        <td>${metricChecks}</td>
                        <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm kpi-remove-rule">Hapus</button></td>
                    </tr>
                `);
            });
        }

        function collectRuleRows($tbody) {
            const rules = [];
            $tbody.find('tr').each(function() {
                const campaignId = parseInt($(this).find('.kpi-rule-campaign').val() || '0', 10);
                if (!campaignId) return;
                const metrics = [];
                $(this).find('.kpi-rule-metric:checked').each(function() {
                    metrics.push(String($(this).val() || ''));
                });
                if (!metrics.length) return;
                rules.push({ campaign_id: campaignId, metrics: metrics });
            });
            return rules;
        }

        function findTemplateEmployeeById(employeeId) {
            const targetId = parseInt(employeeId || '0', 10);
            if (!targetId) return null;
            return (currentTemplateEmployees || []).find(function(row) {
                return parseInt(row.id || '0', 10) === targetId;
            }) || null;
        }

        function resetTemplateBulkEditor(positionId) {
            const isKol = isKolTemplatePosition(positionId);
            $('#kpi-template-bulk-campaigns').val([]).trigger('change');
            $('#kpi-template-bulk-date-from').val('');
            $('#kpi-template-bulk-date-until').val('');
            $('#kpi-template-bulk-kol-date-from').val('');
            $('#kpi-template-bulk-kol-date-until').val('');
            $('#kpi-template-bulk-kol-growth-date-from').val('');
            $('#kpi-template-bulk-kol-growth-date-until').val('');
            renderRuleRows($('#kpi-template-bulk-rule-body'), currentTemplateCampaignOptions, []);
            if (isKol) {
                $('#kpi-template-source-info').text('Pilih 1 karyawan untuk memuat mapping yang sudah ditautkan, atau centang banyak karyawan untuk apply massal.');
            } else {
                $('#kpi-template-source-info').text('');
            }
        }

        function syncTemplateBulkEditorFromSelection(preferredEmployeeId) {
            const positionId = getTemplatePositionId();
            const isKol = isKolTemplatePosition(positionId);
            const selectedIds = $('#kpi-template-employee-body .kpi-template-employee-check:checked').map(function() {
                return $(this).val();
            }).get();

            if (!selectedIds.length) {
                loadTemplateRowsForEmployee('', function(ok) {
                    $('#kpi-template-info').text(ok ? 'Menampilkan template global untuk posisi ini.' : 'Gagal memuat template global.');
                });
                resetTemplateBulkEditor(positionId);
                return;
            }

            let activeEmployeeId = '';
            if (preferredEmployeeId && selectedIds.indexOf(String(preferredEmployeeId)) !== -1) {
                activeEmployeeId = String(preferredEmployeeId);
            } else {
                activeEmployeeId = String(selectedIds[selectedIds.length - 1] || selectedIds[0] || '');
            }

            const selectedEmployee = findTemplateEmployeeById(activeEmployeeId);
            if (!selectedEmployee) {
                loadTemplateRowsForEmployee('', function(ok) {
                    $('#kpi-template-info').text(ok ? 'Menampilkan template global untuk posisi ini.' : 'Gagal memuat template global.');
                });
                resetTemplateBulkEditor(positionId);
                return;
            }

            loadTemplateRowsForEmployee(activeEmployeeId, function(ok) {
                if (ok) {
                    $('#kpi-template-info').text(`Menampilkan indikator KPI milik ${selectedEmployee.full_name || 'karyawan terpilih'}.`);
                } else {
                    $('#kpi-template-info').text('Gagal memuat indikator KPI karyawan terpilih.');
                }
            });

            if (isKol) {
                $('#kpi-template-bulk-kol-date-from').val(String(selectedEmployee.date_from || ''));
                $('#kpi-template-bulk-kol-date-until').val(String(selectedEmployee.date_until || ''));
                $('#kpi-template-bulk-kol-growth-date-from').val(String(selectedEmployee.growth_date_from || ''));
                $('#kpi-template-bulk-kol-growth-date-until').val(String(selectedEmployee.growth_date_until || ''));
                renderRuleRows(
                    $('#kpi-template-bulk-rule-body'),
                    currentTemplateCampaignOptions,
                    Array.isArray(selectedEmployee.source_rules) ? selectedEmployee.source_rules : []
                );
            } else {
                const campaignIds = Array.isArray(selectedEmployee.campaign_ids) ? selectedEmployee.campaign_ids.map(String) : [];
                $('#kpi-template-bulk-campaigns').val(campaignIds).trigger('change');
                $('#kpi-template-bulk-date-from').val(String(selectedEmployee.date_from || ''));
                $('#kpi-template-bulk-date-until').val(String(selectedEmployee.date_until || ''));
            }
            $('#kpi-template-source-info').text(`Editor bulk dimuat dari sumber data ${selectedEmployee.full_name || 'karyawan terpilih'} dan akan di-apply ke semua karyawan yang dicentang.`);
        }

        renderTemplateRows(initialPresetRows);
        syncHeaderFilterVisibility('<?= htmlspecialchars($active_division_key, ENT_QUOTES) ?>');

        $(document).on('click', '.kpi-open-template-btn', function() {
            templateOpenMode = 'active';
        });

        $(document).on('click', '#kpi-add-position-btn', function() {
            templateOpenMode = 'add';
        });

        $('#kpiTemplateModal').on('show.bs.modal', function(e) {
            const month = getCurrentMonth();
            const $trigger = e.relatedTarget ? $(e.relatedTarget) : $();
            const openMode = $trigger.is('#kpi-add-position-btn') ? 'add' : ($trigger.hasClass('kpi-open-template-btn') ? 'active' : templateOpenMode);
            const activePositionId = openMode === 'add'
                ? getFirstAvailablePositionId(true)
                : getActiveDivisionPositionId();
            $('#kpi-template-position').val(String(activePositionId));
            $('#kpi-template-month-label').text(month);
            syncTemplateDeleteButton(activePositionId);
            loadTemplateRows(month, activePositionId);
        });

        $('#kpi-template-position').on('change', function() {
            const positionId = getTemplatePositionId();
            syncTemplateDeleteButton(positionId);
            loadTemplateRows(getCurrentMonth(), positionId);
        });

        $(document).on('change', '#kpi-template-check-all-employees', function() {
            const checked = $(this).is(':checked');
            $('#kpi-template-employee-body .kpi-template-employee-check:not(:disabled)').prop('checked', checked);
            syncTemplateBulkEditorFromSelection();
        });

        $(document).on('change', '.kpi-template-employee-check', function() {
            syncTemplateBulkEditorFromSelection($(this).val());
        });

        $('#kpi-template-bulk-add-rule').on('click', function() {
            const $tbody = $('#kpi-template-bulk-rule-body');
            const rules = collectRuleRows($tbody);
            rules.push({ campaign_id: '', metrics: ['total_views', 'total_konten'] });
            renderRuleRows($tbody, currentTemplateCampaignOptions, rules);
        });

        $(document).on('click', '.kpi-remove-rule', function() {
            const $tbody = $(this).closest('tbody');
            const rowIndex = parseInt($(this).closest('tr').attr('data-rule-index') || '-1', 10);
            const rules = collectRuleRows($tbody).filter(function(_, index) {
                return index !== rowIndex;
            });
            const options = $tbody.attr('id') === 'kpi-template-bulk-rule-body'
                ? currentTemplateCampaignOptions
                : (window.currentSourceCampaignOptions || []);
            renderRuleRows($tbody, options, rules);
        });

        $('#kpi-template-add-row').on('click', function() {
            const current = collectTemplateRows();
            current.push({ indikator: '', target: 0, bobot: 0, metric_key: getDefaultTemplateMetricKey(getTemplatePositionId()), dimensi: '3 Bulan', keterangan: '' });
            renderTemplateRows(current);
        });

        $(document).on('focus', '.kpi-template-edit-display[data-type="number"]', function() {
            this.dataset.beforeEdit = $(this).text().trim();
        });

        $(document).on('input', '.kpi-template-edit-display[data-type="number"]', function() {
            const formatted = formatTypingInt($(this).text());
            $(this).text(formatted);
            const el = this;
            const range = document.createRange();
            const sel = window.getSelection();
            range.selectNodeContents(el);
            range.collapse(false);
            sel.removeAllRanges();
            sel.addRange(range);
        });

        $(document).on('keydown', '.kpi-template-edit-display', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(this).blur();
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                const before = this.dataset.beforeEdit;
                if (before !== undefined) {
                    $(this).text(before);
                }
                $(this).blur();
            }
        });

        $(document).on('blur', '.kpi-template-edit-display', function() {
            const $cell = $(this);
            const type = $cell.data('type');
            if (type === 'number') {
                $cell.text(formatTemplateInt($cell.text()));
            } else {
                $cell.text($cell.text().trim());
            }
        });

        $(document).on('click', '.kpi-template-remove-row', function() {
            const $rows = $('#kpi-template-body tr');
            if ($rows.length <= 1) {
                $('#kpi-template-info').text('Minimal 1 row template harus ada.');
                return;
            }
            $(this).closest('tr').remove();
            $('#kpi-template-info').text('');
        });

        $('#kpi-template-save').on('click', function() {
            const month = getCurrentMonth();
            const positionToken = getTemplatePositionToken();
            const positionId = isNewCustomTemplatePosition(positionToken) ? 0 : getTemplatePositionId();
            const rows = collectTemplateRows();
            const customPositionName = ($('#kpi-template-custom-name').val() || '').trim();
            const memberEmployeeIds = $('#kpi-template-custom-members').val() || [];
            const $btn = $(this);
            const oldText = $btn.text();

            if (isCustomTemplatePosition(positionToken) && !customPositionName) {
                $('#kpi-template-info').text('Nama posisi custom wajib diisi.');
                return;
            }
            if (isCustomTemplatePosition(positionToken) && !memberEmployeeIds.length) {
                $('#kpi-template-info').text('Pilih minimal 1 karyawan untuk posisi custom.');
                return;
            }

            $btn.prop('disabled', true).text('Menyimpan...');
            $('#kpi-template-info').text('Menyimpan template...');

            $.ajax({
                url: '<?= base_url('kpi/save-template') ?>',
                method: 'POST',
                dataType: 'json',
                data: {
                    month: month,
                    position_id: positionToken,
                    rows: rows,
                    custom_position_name: customPositionName,
                    member_employee_ids: memberEmployeeIds
                },
                success: function(res) {
                    if (res && res.status) {
                        const savedPositionId = parseInt((res && res.position_id) || positionId || '0', 10);
                        const targetTabKey = String((res && res.tab_key) || getTabKeyForPosition(savedPositionId));
                        const shouldReloadForNewTab = !hasTabForPosition(savedPositionId);
                        $('#kpi-template-info').text(shouldReloadForNewTab ? 'Template tersimpan. Memuat tab posisi...' : 'Template tersimpan. Klik "Generate" untuk menerapkan.');
                        if (shouldReloadForNewTab) {
                            const params = buildFilterParams();
                            params.set('tab', targetTabKey);
                            params.set('month', month);
                            setTimeout(function() {
                                window.location.href = '<?= base_url('kpi') ?>?' + params.toString();
                            }, 350);
                        }
                    } else {
                        $('#kpi-template-info').text((res && res.message) ? res.message : 'Gagal menyimpan template.');
                    }
                },
                error: function() {
                    $('#kpi-template-info').text('Gagal menyimpan template.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(oldText);
                }
            });
        });

        $('#kpi-template-apply-selected').on('click', function() {
            const month = getCurrentMonth();
            const positionId = getTemplatePositionId();
            const rows = collectTemplateRows();
            const employeeIds = $('#kpi-template-employee-body .kpi-template-employee-check:checked').map(function() {
                return $(this).val();
            }).get();
            const $btn = $(this);
            const oldText = $btn.text();

            if (!employeeIds.length) {
                $('#kpi-template-info').text('Pilih minimal 1 karyawan untuk apply template per karyawan.');
                return;
            }

            $btn.prop('disabled', true).text('Applying...');
            $('#kpi-template-info').text('Meng-apply row template ke karyawan terpilih...');

            $.ajax({
                url: '<?= base_url('kpi/save-selected-template') ?>',
                method: 'POST',
                dataType: 'json',
                data: {
                    month: month,
                    position_id: positionId,
                    employee_ids: employeeIds,
                    rows: rows
                },
                success: function(res) {
                    if (res && res.status) {
                        $('#kpi-template-info').text((res && res.message) ? res.message : 'Template berhasil di-apply ke karyawan terpilih. Memuat ulang...');
                        setTimeout(function() {
                            window.location.reload();
                        }, 350);
                    } else {
                        $('#kpi-template-info').text((res && res.message) ? res.message : 'Gagal apply template ke karyawan terpilih.');
                    }
                },
                error: function() {
                    $('#kpi-template-info').text('Gagal apply template ke karyawan terpilih.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(oldText);
                }
            });
        });

        $(document).on('click', '.kpi-tab-delete-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const $btn = $(this);
            const positionId = parseInt($btn.data('position-id') || '0', 10);
            const positionLabel = String($btn.data('position-label') || 'posisi ini');

            if (!positionId || isSystemKpiPosition(positionId)) {
                return;
            }

            Swal.fire({
                title: 'Hapus KPI?',
                html: 'Apakah Anda yakin ingin menghapus KPI untuk <strong>' + $('<div>').text(positionLabel).html() + '</strong>?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff4d4f',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                Swal.fire({
                    title: 'Menghapus...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function() {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '<?= base_url('kpi/delete-position-template') ?>',
                    method: 'POST',
                    dataType: 'json',
                    data: { position_id: positionId },
                    success: function(res) {
                        if (res && res.status) {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: res.message || 'Posisi KPI berhasil dihapus.',
                                icon: 'success',
                                timer: 1400,
                                showConfirmButton: false
                            }).then(function() {
                                const currentTabPositionId = getActiveDivisionPositionId();
                                const params = buildFilterParams();
                                if (parseInt(currentTabPositionId || '0', 10) === positionId) {
                                    params.set('tab', 'dashboard');
                                }
                                window.location.href = '<?= base_url('kpi') ?>?' + params.toString();
                            });
                        } else {
                            Swal.fire('Gagal', (res && res.message) ? res.message : 'Gagal menghapus posisi KPI.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Gagal', 'Gagal menghapus posisi KPI.', 'error');
                    }
                });
            });
        });

        $('#kpi-template-delete-position').on('click', function() {
            const positionId = getTemplatePositionId();
            const positionOption = (kpiPositionOptions || []).find(function(item) {
                return parseInt(item.position_id || '0', 10) === parseInt(positionId || '0', 10);
            }) || {};
            const positionLabel = String(positionOption.option_label || positionOption.name || 'posisi ini');
            const $btn = $(this);
            const oldText = $btn.text();

            if (isSystemKpiPosition(positionId)) {
                $('#kpi-template-info').text('Posisi bawaan KPI tidak bisa dihapus.');
                return;
            }

            if (!confirm('Hapus KPI untuk ' + positionLabel + ' dari tab KPI?')) {
                return;
            }

            $btn.prop('disabled', true).text('Menghapus...');
            $('#kpi-template-info').text('Menghapus posisi KPI...');

            $.ajax({
                url: '<?= base_url('kpi/delete-position-template') ?>',
                method: 'POST',
                dataType: 'json',
                data: {
                    position_id: positionId
                },
                success: function(res) {
                    if (res && res.status) {
                        const currentTabPositionId = getActiveDivisionPositionId();
                        const params = buildFilterParams();
                        if (parseInt(currentTabPositionId || '0', 10) === parseInt(positionId || '0', 10)) {
                            params.set('tab', 'dashboard');
                        }
                        $('#kpi-template-info').text(res.message || 'Posisi KPI berhasil dihapus.');
                        setTimeout(function() {
                            window.location.href = '<?= base_url('kpi') ?>?' + params.toString();
                        }, 350);
                    } else {
                        $('#kpi-template-info').text((res && res.message) ? res.message : 'Gagal menghapus posisi KPI.');
                    }
                },
                error: function() {
                    $('#kpi-template-info').text('Gagal menghapus posisi KPI.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(oldText);
                }
            });
        });

        $('#kpi-template-apply-bulk').on('click', function() {
            const employeeIds = $('#kpi-template-employee-body .kpi-template-employee-check:checked').map(function() {
                return $(this).val();
            }).get();
            const campaignIds = $('#kpi-template-bulk-campaigns').val() || [];
            const isKol = isKolTemplatePosition(getTemplatePositionId());
            const sourceRules = isKol ? collectRuleRows($('#kpi-template-bulk-rule-body')) : [];
            const dateFrom = isKol ? ($('#kpi-template-bulk-kol-date-from').val() || '') : ($('#kpi-template-bulk-date-from').val() || '');
            const dateUntil = isKol ? ($('#kpi-template-bulk-kol-date-until').val() || '') : ($('#kpi-template-bulk-date-until').val() || '');
            const growthDateFrom = isKol ? ($('#kpi-template-bulk-kol-growth-date-from').val() || '') : '';
            const growthDateUntil = isKol ? ($('#kpi-template-bulk-kol-growth-date-until').val() || '') : '';
            const month = getCurrentMonth();
            const $btn = $(this);
            const oldText = $btn.text();

            if (!employeeIds.length) {
                $('#kpi-template-source-info').text('Pilih minimal 1 karyawan.');
                return;
            }

            $btn.prop('disabled', true).text('Applying...');
            $('#kpi-template-source-info').text('Meng-apply sumber data...');

            $.ajax({
                url: '<?= base_url('kpi/save-bulk-preset') ?>',
                method: 'POST',
                dataType: 'json',
                data: {
                    month: month,
                    employee_ids: employeeIds,
                    campaign_ids: campaignIds,
                    source_rules: sourceRules,
                    date_from: dateFrom,
                    date_until: dateUntil,
                    growth_date_from: growthDateFrom,
                    growth_date_until: growthDateUntil
                },
                success: function(res) {
                    if (res && res.status) {
                        $('#kpi-template-source-info').text(res.message || 'Sumber data berhasil di-apply.');
                        loadTemplateRows(month, getTemplatePositionId());
                    } else {
                        $('#kpi-template-source-info').text((res && res.message) ? res.message : 'Gagal apply sumber data.');
                    }
                },
                error: function() {
                    $('#kpi-template-source-info').text('Gagal apply sumber data.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(oldText);
                }
            });
        });

        recalcAllCards();

        $(document).on('click', '.kpi-edit-cell', function(e) {
            if (isKpiReadonly) return;
            if ($(e.target).hasClass('kpi-input') || $(e.target).is('select, option')) return;
            const $cell = $(this);
            const $card = $cell.closest('.kpi-employee-card');
            if (isCardReadonly($card)) return;
            if ($card.data('locked') === 1 || $card.data('locked') === '1') return;
            $('.kpi-edit-cell.is-editing').removeClass('is-editing');
            $cell.addClass('is-editing');
            const $input = $cell.find('.kpi-input');
            $input.focus().select();
            $input.data('before-edit', $input.val());
        });

        $(document).on('keydown', '.kpi-input', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(this).blur();
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                const before = $(this).data('before-edit');
                if (before !== undefined) $(this).val(before);
                $(this).closest('.kpi-edit-cell').removeClass('is-editing');
            }
        });

        $(document).on('blur', '.kpi-input', function() {
            const $input = $(this);
            const $cell = $input.closest('.kpi-edit-cell');
            const field = $input.data('field');
            $cell.find('.kpi-edit-display').text(formatDisplayByField(field, $input.val()));
            $cell.removeClass('is-editing');

            if (field === 'capaian' || field === 'manual_capaian') {
                recalcEmployeeCard($input.closest('.kpi-employee-card'));
            }
        });

        $(document).on('change', '.kpi-edit', function() {
            if (isKpiReadonly) return;
            const $el = $(this);
            const $card = $el.closest('.kpi-employee-card');
            if (isCardReadonly($card)) {
                $('#kpi-save-info').text('Mode rata-rata quarter hanya untuk tampilan.');
                return;
            }
            if ($card.data('locked') === 1 || $card.data('locked') === '1') {
                $('#kpi-save-info').text('KPI di-lock. Unlock dulu untuk edit.');
                return;
            }
            const id = $el.data('id');
            const field = $el.data('field');
            const value = $el.val();

            $.ajax({
                url: '<?= base_url('kpi/update-row') ?>',
                method: 'POST',
                dataType: 'json',
                data: {
                    id: id,
                    field: field,
                    value: value,
                    campaign_ids: getSelectedCampaignIds($card)
                },
                success: function(res) {
                    if (res && res.status) {
                        const $cell = $el.closest('.kpi-edit-cell');
                        const $card = $el.closest('.kpi-employee-card');
                        const normalizedValue = (res && res.value !== undefined) ? res.value : value;
                        if (field !== 'metric_key') {
                            $el.val(formatDisplayByField(field, normalizedValue));
                            $cell.find('.kpi-edit-display').text(formatDisplayByField(field, normalizedValue));
                        }
                        recalcEmployeeCard($card);
                        if (field === 'capaian' && res.locked) {
                            applyCardLockState($card, true);
                        }
                        $('#kpi-save-info').text((res && res.message) ? res.message : 'Perubahan tersimpan.');
                    } else {
                        $('#kpi-save-info').text((res && res.message) ? res.message : 'Gagal menyimpan.');
                    }
                },
                error: function() {
                    $('#kpi-save-info').text('Gagal menyimpan perubahan.');
                }
            });
        });

        $(document).on('click', '.kpi-lock-btn', function() {
            if (isKpiReadonly) return;
            const $btn = $(this);
            const $card = $btn.closest('.kpi-employee-card');
            if (isCardReadonly($card)) {
                $('#kpi-save-info').text('Mode rata-rata quarter hanya untuk tampilan.');
                return;
            }
            const employeeId = parseInt($btn.data('employee-id'), 10);
            const month = $card.data('month');
            const action = $btn.data('action');
            const originalText = $btn.text();

            $btn.prop('disabled', true).text(action === 'lock' ? 'Locking...' : 'Unlocking...');
            $('#kpi-save-info').text(action === 'lock' ? 'Mengunci KPI...' : 'Membuka lock KPI...');

            $.ajax({
                url: '<?= base_url('kpi/toggle-run-lock') ?>',
                method: 'POST',
                dataType: 'json',
                data: {
                    month: month,
                    employee_id: employeeId,
                    action: action,
                    campaign_ids: getSelectedCampaignIds($card)
                },
                success: function(res) {
                    if (res && res.status) {
                        const nowLocked = action === 'lock';
                        applyCardLockState($card, nowLocked);
                        $('#kpi-save-info').text(res.message || 'Status lock diperbarui.');
                    } else {
                        $('#kpi-save-info').text((res && res.message) ? res.message : 'Gagal ubah lock.');
                        $btn.text(originalText);
                    }
                },
                error: function() {
                    $('#kpi-save-info').text('Gagal ubah lock.');
                    $btn.text(originalText);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        $(document).on('click', '.kpi-tab-btn', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const tabKey = String($btn.data('tab-key') || '');
            if (!tabKey) return;
            const paneId = 'tabpane-' + tabKey;
            const $pane = $('#' + paneId);
            if (!$pane.length) return;

            saveUiState({ activeTabId: 'tab-' + tabKey });
            setActiveTab(tabKey);
            syncPrimaryPeriodVisibility();
            syncPeriodPickerHidden();

            if (String($pane.data('loaded')) === '1') {
                restoreCollapseForPane(paneId);
                return;
            }

            $pane.html('<div class="kpi-empty">Memuat data...</div>');
            const params = buildFilterParams();
            params.set('tab', tabKey);

            $.ajax({
                url: '<?= base_url('kpi') ?>?' + params.toString(),
                method: 'GET',
                dataType: 'html',
                success: function(html) {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const incoming = doc.getElementById(paneId);
                    if (!incoming) {
                        $pane.html('<div class="kpi-empty">Gagal memuat data tab.</div>');
                        return;
                    }

                    const $incoming = $(incoming);
                    $pane.html($incoming.html());
                    $pane.attr('data-loaded', '1').data('loaded', 1);
                    $pane.find('.kpi-campaign-select').select2({
                        placeholder: 'Pilih campaign',
                        allowClear: true,
                        width: '100%'
                    });
                    initPresetCampaignSelects($pane);
                    $pane.find('.kpi-employee-card').each(function() {
                        recalcEmployeeCard($(this));
                    });
                    restoreCollapseForPane(paneId);
                },
                error: function() {
                    $pane.html('<div class="kpi-empty">Gagal memuat data tab.</div>');
                }
            });
        });

        $(document).on('click', '.kpi-source-data-btn', function() {
            const $btn = $(this);
            const positionId = parseInt($btn.data('position-id') || '0', 10);
            const campaignIds = JSON.parse($btn.attr('data-campaign-ids') || '[]');
            const sourceRules = JSON.parse($btn.attr('data-source-rules') || '[]');
            const campaignOptions = JSON.parse($btn.attr('data-campaign-options') || '[]');
            window.currentSourceCampaignOptions = campaignOptions;
            const defaultQuarterStart = String($btn.closest('.kpi-employee-card').data('quarter-start') || '');
            const defaultQuarterEnd = String($btn.closest('.kpi-employee-card').data('quarter-end') || '');

            $('#kpi-source-employee-id').val(String($btn.data('employee-id') || ''));
            $('#kpi-source-position-id').val(String(positionId || ''));
            $('#kpi-source-employee-name').text(String($btn.data('employee-name') || '-'));
            $('#kpi-source-month').val(String($btn.data('month') || getCurrentMonth()));
            $('#kpi-source-month-label').text(String($btn.data('month') || getCurrentMonth()));
            $('#kpi-source-date-from').val(String($btn.data('date-from') || defaultQuarterStart));
            $('#kpi-source-date-until').val(String($btn.data('date-until') || defaultQuarterEnd));
            $('#kpi-source-growth-date-from').val(String($btn.data('growth-date-from') || ''));
            $('#kpi-source-growth-date-until').val(String($btn.data('growth-date-until') || ''));
            const isKol = isKolTemplatePosition(positionId);
            $('#kpi-source-simple-wrap').toggle(positionId === 7);
            $('#kpi-source-kol-wrap').toggle(isKol);
            $('#kpi-source-kol-growth-wrap').toggle(isKol);
            $('#kpi-source-date-from-label').text(isKol ? 'Tanggal Mulai Posted Content' : 'Tanggal Mulai Kenaikan Data');
            $('#kpi-source-date-until-label').text(isKol ? 'Tanggal Selesai Posted Content' : 'Tanggal Selesai Kenaikan Data');

            const $select = $('#kpi-source-campaigns');
            $select.empty();
            campaignOptions.forEach(function(item) {
                const itemId = parseInt(item.id, 10);
                const selected = campaignIds.indexOf(itemId) !== -1
                    || campaignIds.indexOf(String(itemId)) !== -1;
                const option = new Option(String(item.title || ''), String(itemId), selected, selected);
                $select.append(option);
            });
            $select.trigger('change');
            renderRuleRows($('#kpi-source-rule-body'), campaignOptions, sourceRules);
            $('#kpi-source-info').text('');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('kpiSourceDataModal')).show();
        });

        $('#kpi-source-add-rule').on('click', function() {
            const $tbody = $('#kpi-source-rule-body');
            const rules = collectRuleRows($tbody);
            rules.push({ campaign_id: '', metrics: ['total_views', 'total_konten'] });
            renderRuleRows($tbody, window.currentSourceCampaignOptions || [], rules);
        });

        $('#kpi-source-save').on('click', function() {
            const $btn = $(this);
            const employeeId = $('#kpi-source-employee-id').val() || '';
            const positionId = parseInt($('#kpi-source-position-id').val() || '0', 10);
            const month = $('#kpi-source-month').val() || getCurrentMonth();
            const campaignIds = $('#kpi-source-campaigns').val() || [];
            const isKol = isKolTemplatePosition(positionId);
            const sourceRules = isKol ? collectRuleRows($('#kpi-source-rule-body')) : [];
            const dateFrom = $('#kpi-source-date-from').val() || '';
            const dateUntil = $('#kpi-source-date-until').val() || '';
            const growthDateFrom = isKol ? ($('#kpi-source-growth-date-from').val() || '') : '';
            const growthDateUntil = isKol ? ($('#kpi-source-growth-date-until').val() || '') : '';
            const originalText = $btn.text();

            $btn.prop('disabled', true).text('Menyimpan...');
            $('#kpi-source-info').text('Menyimpan sumber data...');

            $.ajax({
                url: '<?= base_url('kpi/save-preset') ?>',
                method: 'POST',
                dataType: 'json',
                data: {
                    month: month,
                    employee_id: employeeId,
                    campaign_ids: campaignIds,
                    source_rules: sourceRules,
                    date_from: dateFrom,
                    date_until: dateUntil,
                    growth_date_from: growthDateFrom,
                    growth_date_until: growthDateUntil
                },
                success: function(res) {
                    if (res && res.status) {
                        $('#kpi-source-info').text('Sumber data berhasil di-apply. Memuat ulang...');
                        setTimeout(function() { location.reload(); }, 350);
                    } else {
                        $('#kpi-source-info').text((res && res.message) ? res.message : 'Gagal menyimpan sumber data.');
                    }
                },
                error: function() {
                    $('#kpi-source-info').text('Gagal menyimpan sumber data.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });

        $(document).on('shown.bs.collapse', '.accordion-collapse', function() {
            const paneId = $(this).closest('.tab-pane').attr('id');
            if (!paneId) return;
            const state = readUiState();
            const openCollapseByTab = Object.assign({}, state.openCollapseByTab || {});
            openCollapseByTab[paneId] = this.id;
            saveUiState({ openCollapseByTab: openCollapseByTab });
        });

        $(document).on('hidden.bs.collapse', '.accordion-collapse', function() {
            const paneId = $(this).closest('.tab-pane').attr('id');
            if (!paneId) return;
            const state = readUiState();
            const openCollapseByTab = Object.assign({}, state.openCollapseByTab || {});
            if (openCollapseByTab[paneId] === this.id) {
                delete openCollapseByTab[paneId];
                saveUiState({ openCollapseByTab: openCollapseByTab });
            }
        });

        restoreUiState();
    });
</script>
