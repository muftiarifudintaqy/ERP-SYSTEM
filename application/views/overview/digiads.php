<style>
    .overview-general {
        padding-bottom: 24px;
    }

    .overview-general .page-title {
        font-size: 1.6rem;
        font-weight: 700;
        color: #1b2a3d;
        margin-bottom: 6px;
    }

    .overview-general .page-subtitle {
        color: #5b6b7a;
        font-size: 0.95rem;
    }

    .overview-general .filter-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 16px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    }

    .overview-general .filter-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #8b9ab0;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .overview-general .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 16px;
        margin-top: 14px;
        margin-bottom: 18px;
    }

    .overview-general .metric-card {
        border-radius: 16px;
        background: #fff;
        padding: 18px 18px 14px;
        border: 2px solid #e8eef4;
        box-shadow: 0 10px 24px rgba(12, 30, 58, 0.06);
        cursor: default;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        position: relative;
    }

    .overview-general .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 32px rgba(12, 30, 58, 0.12);
    }

    .overview-general .metric-card.is-active {
        border-color: #0f8b8d;
        box-shadow: 0 16px 36px rgba(15, 139, 141, 0.2);
    }

    .overview-general .metric-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .overview-general .metric-title {
        font-weight: 700;
        color: #1b2a3d;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .overview-general .metric-desc {
        color: #6b7c93;
        font-size: 0.78rem;
    }

    .overview-general .metric-toggle {
        width: 26px;
        height: 26px;
        border-radius: 8px;
        border: 2px solid #d6dee8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: transparent;
        transition: all 0.2s ease;
        background: #f5f9fc;
    }

    .overview-general .metric-checkbox {
        width: 18px;
        height: 18px;
        accent-color: #0f8b8d;
        cursor: pointer;
    }

    .overview-general .metric-card.is-active .metric-toggle {
        background: #0f8b8d;
        border-color: #0f8b8d;
        color: #fff;
    }

    .overview-general .metric-value {
        font-size: 1.45rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 6px;
    }

    .overview-general .metric-value-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .overview-general .metric-period {
        color: #6b7c93;
        font-size: 0.82rem;
        margin-bottom: 6px;
    }

    .overview-general .metric-change {
        font-size: 0.9rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .overview-general .trend-up {
        color: #1d9b5c;
    }

    .overview-general .trend-down {
        color: #d64545;
    }

    .overview-general .chart-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 18px 18px 32px;
        box-shadow: 0 10px 28px rgba(12, 30, 58, 0.08);
        height: 400px;
    }

    .overview-general .chart-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1b2a3d;
        margin-bottom: 4px;
    }

    .overview-general .chart-subtitle {
        color: #7b8ca4;
        font-size: 0.85rem;
        margin-bottom: 12px;
    }

    .overview-general .section-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1b2a3d;
        margin-bottom: 12px;
    }

    .overview-general .product-section {
        margin-top: 28px;
    }

    .overview-general .product-card {
        padding: 16px 18px;
        cursor: default;
    }

    .overview-general .product-value-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.9rem;
        color: #1f2937;
        margin-bottom: 6px;
        font-weight: 600;
    }

    .overview-general .product-value-row:last-child {
        margin-bottom: 0;
    }

    .overview-general .detail-link {
        font-size: 0.85rem;
        color: #0f8b8d;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
    }

    .overview-general .summary-detail {
        display: flex;
        margin-top: 10px;
    }

    .overview-general .traffic-detail-btn {
        background: #e6f6f6;
        border: 1px solid #bde8e8;
        color: #0f8b8d;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        margin-top: 0;
    }

    .overview-general .traffic-detail-btn:hover {
        background: #d6f0f0;
        color: #0f7e80;
    }

    .overview-general .compare-section {
        background: #f8fbfd;
        border: 1px solid #e3ebf3;
        border-radius: 14px;
        padding: 12px 14px;
    }

    .overview-general .toggle-row {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .overview-general .toggle-switch {
        position: relative;
        width: 54px;
        height: 28px;
        background: #dbe7f1;
        border-radius: 999px;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .overview-general .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .overview-general .toggle-switch .knob {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.2);
        transition: transform 0.2s ease;
    }

    .overview-general .toggle-switch input:checked + .knob {
        transform: translateX(26px);
    }

    .overview-general .toggle-switch input:checked ~ .toggle-bg {
        background: #0f8b8d;
    }

    .overview-general .toggle-label {
        font-weight: 600;
        color: #4b5b70;
        font-size: 0.9rem;
    }

    .overview-general .toggle-bg {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        background: #dbe7f1;
        transition: background 0.2s ease;
        z-index: -1;
    }

    .overview-general .loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        font-weight: 600;
        color: #66788a;
        font-size: 0.9rem;
        z-index: 3;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    .overview-general .loading-overlay.is-visible {
        opacity: 1;
        pointer-events: all;
    }

    .overview-general .chart-wrapper {
        position: relative;
        height: calc(100% - 52px);
    }

    .overview-general #digiadsOverviewChart {
        height: 100% !important;
    }

    .digiads-modal-table {
        width: 100%;
        border-collapse: collapse;
    }

    .digiads-modal-table th,
    .digiads-modal-table td {
        padding: 10px 8px;
        border-bottom: 1px solid #e5edf5;
    }

    .digiads-modal-table th {
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7c93;
    }

    .digiads-modal-table td:nth-child(2),
    .digiads-modal-table td:nth-child(3),
    .digiads-modal-table td:nth-child(4) {
        text-align: right;
    }

    .digiads-subvalue {
        display: block;
        margin-top: 4px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #7b8ca4;
        line-height: 1.3;
    }

    .traffic-breakdown-item {
        padding: 14px 0;
        border-bottom: 1px solid #e5edf5;
    }

    .traffic-breakdown-item:last-child {
        border-bottom: none;
    }

    .traffic-breakdown-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .traffic-breakdown-value {
        margin-top: 6px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        font-size: 1.55rem;
        font-weight: 700;
        color: #0f172a;
    }

    .traffic-breakdown-change {
        font-size: 1rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .traffic-breakdown-change.up {
        color: #16a34a;
    }

    .traffic-breakdown-change.down {
        color: #ef4444;
    }

    .traffic-card-chart-wrap {
        height: 170px;
        margin-top: 4px;
    }

    .traffic-card-chart-subtitle {
        font-size: 0.76rem;
        color: #64748b;
        font-weight: 600;
        margin-top: 4px;
    }

    .qty-top-list {
        margin-top: 8px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .qty-top-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 6px 8px;
        border: 1px solid #e5edf5;
        border-radius: 10px;
        background: #fbfdff;
        font-size: 0.78rem;
        font-weight: 600;
        color: #334155;
    }

    .qty-top-item .name {
        flex: 1;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .qty-top-item .value {
        color: #0f172a;
        font-weight: 700;
    }

    .qty-modal-table-wrap {
        max-height: 76vh;
        overflow: auto;
        border: 1px solid #dbe5f0;
        border-radius: 14px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        padding: 8px;
    }

    .qty-modal-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .qty-modal-table thead th {
        position: sticky;
        top: 0;
        background: #eff6ff;
        color: #1e3a8a;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        border-bottom: 1px solid #dbe5f0;
        padding: 10px 10px;
        z-index: 1;
    }

    .qty-modal-table tbody td {
        padding: 10px 10px;
        border-bottom: 1px solid #e5edf5;
        font-size: 0.86rem;
        color: #1f2937;
        vertical-align: top;
    }

    .qty-modal-table tbody tr:hover {
        background: #f8fbff;
    }

    .qty-modal-table td:nth-child(1) {
        width: 54px;
        text-align: center;
        color: #64748b;
        font-weight: 700;
    }

    .qty-modal-table td:nth-child(2) {
        font-weight: 600;
        color: #0f172a;
    }

    .qty-modal-table td:nth-child(3) {
        text-align: right;
        font-weight: 700;
        color: #0f8b8d;
        white-space: nowrap;
    }

    .distribusi-modal .distribusi-legend {
        justify-content: center;
        text-align: center;
    }

    .modal-dialog.digiads-modal-wide {
        max-width: 1040px;
    }

    .traffic-tippy {
        max-width: 340px;
    }

    .traffic-tippy .tippy-title {
        font-size: 0.9rem;
        font-weight: 700;
        margin-bottom: 8px;
        color: #1f2937;
    }

    .traffic-tippy .tippy-search {
        width: 100%;
        border: 1px solid #dbe3ee;
        border-radius: 8px;
        padding: 6px 9px;
        font-size: 0.76rem;
        color: #0f172a;
        margin-bottom: 8px;
        outline: none;
        background: #fff;
    }

    .traffic-tippy .tippy-search:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.12);
    }

    .traffic-tippy .tippy-wrap {
        max-height: 260px;
        overflow: auto;
    }

    .traffic-tippy .tippy-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .traffic-tippy .tippy-item {
        border: 1px solid #e5edf5;
        border-radius: 10px;
        padding: 7px 9px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
    }

    .traffic-tippy .tippy-item-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 4px;
    }

    .traffic-tippy .tippy-rank {
        font-size: 0.68rem;
        font-weight: 700;
        color: #0f766e;
        min-width: 30px;
        background: #d1fae5;
        border: 1px solid #a7f3d0;
        border-radius: 999px;
        padding: 2px 7px;
        text-align: center;
    }

    .traffic-tippy .tippy-shop {
        font-size: 0.75rem;
        font-weight: 700;
        color: #1e3a8a;
        text-align: right;
    }

    .traffic-tippy .tippy-product {
        font-size: 0.75rem;
        color: #0f172a;
        line-height: 1.35;
        font-weight: 600;
        white-space: normal;
        word-break: break-word;
        overflow-wrap: anywhere;
        margin-bottom: 6px;
    }

    .traffic-tippy .tippy-metrics {
        font-size: 0.7rem;
        color: #475569;
        line-height: 1.35;
        font-weight: 600;
        white-space: normal;
        word-break: break-word;
    }

    .traffic-tippy .tippy-empty {
        font-size: 0.74rem;
        font-weight: 600;
        color: #64748b;
        text-align: center;
        padding: 10px 6px;
        display: none;
    }

    .traffic-tippy .tippy-empty.is-visible {
        display: block;
    }

    .daterangepicker {
        width: auto !important;
        max-width: 600px !important;
        padding: 10px !important;
        font-size: 13px;
    }
    .daterangepicker .calendar {
        width: 100% !important;
        max-width: 250px !important;
    }
    .daterangepicker .drp-calendar {
        margin: 0 5px !important;
    }
    .daterangepicker .calendar-table {
        table-layout: fixed !important;
        width: 100% !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
    }
    .daterangepicker .calendar-table th,
    .daterangepicker .calendar-table td {
        width: 30% !important;
        max-width: 30% !important;
        height: 15px !important;
        line-height: 15px !important;
        margin: 0 !important;
        text-align: center !important;
        vertical-align: middle !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
        white-space: nowrap !important;
        text-overflow: ellipsis !important;
        font-size: 11px !important;
        font-weight: normal !important;
    }
    .daterangepicker td.in-range {
        background-color: #f0f8ff !important;
        color: #000 !important;
    }
    .daterangepicker td.active,
    .daterangepicker td.active:hover,
    .daterangepicker td.start-date,
    .daterangepicker td.end-date {
        background-color: #357ebd !important;
        color: #fff !important;
        width: 30% !important;
        max-width: 30% !important;
        height: 15px !important;
        line-height: 15px !important;
        margin: 0 !important;
        box-sizing: border-box !important;
        text-align: center !important;
        overflow: hidden !important;
        white-space: nowrap !important;
    }
    .daterangepicker .drp-buttons {
        margin-top: 10px !important;
        padding-top: 10px !important;
        border-top: 1px solid #eee !important;
    }
    .daterangepicker .ranges {
        display: none !important;
    }
    .custom-ranges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 10px 10px 0;
        justify-content: center;
    }
    .custom-ranges button {
        padding: 5px 10px;
        font-size: 12px;
        border: 1px solid #ccc;
        background: #f9f9f9;
        border-radius: 4px;
        cursor: pointer;
    }
    .custom-ranges button.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }
</style>

<?php
$period_main = $digiads_period_1['totals'];
$period_compare = $digiads_period_2['totals'];

$metrics = [
    'gmv' => ['label' => 'GMV', 'desc' => 'Total omset', 'format' => 'currency'],
    'spent' => ['label' => 'Spent', 'desc' => 'Total Biaya Iklan + Pajak', 'format' => 'currency'],
    'ratio' => ['label' => 'Rasio', 'desc' => 'Total Spent / Total Omset * 100%', 'format' => 'percent']
];

if (!function_exists('format_digiads_metric_value')) {
    function format_digiads_metric_value($value, $format)
    {
        if ($format === 'currency') return 'Rp ' . number_format($value, 0, ',', '.');
        if ($format === 'percent') return number_format($value, 2, ',', '.') . '%';
        return number_format($value, 0, ',', '.');
    }
}

if (!function_exists('calc_digiads_change')) {
    function calc_digiads_change($v1, $v2)
    {
        if ($v1 == 0) return null;
        return (($v2 - $v1) / $v1) * 100;
    }
}
?>

<div class="w-100 overview-general">
    <div class="row align-items-center">
        <?php $this->load->view('overview/menu') ?>

        <div class="col-lg-12 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <div class="page-title">Data Digiads</div>
                    <div class="page-subtitle">Ringkasan performa digiads dengan breakdown harian.</div>
                </div>
                <div class="text-muted small">
                    <span id="mainRangeText"><?= $this->template->date_format_indo($start_date) ?> - <?= $this->template->date_format_indo($until_date) ?></span>
                    dibanding
                    <span id="compareRangeText"><?= $this->template->date_format_indo($start_date_2) ?> - <?= $this->template->date_format_indo($until_date_2) ?></span>
                    <i class="bi bi-question-circle text-muted ms-1"
                        style="cursor: help;"
                        title="Periode 1 (pembanding) dihitung otomatis: jumlah hari sama dengan periode utama, mundur dari tanggal awal.&#10;&#10;Contoh: periode utama 1-31 Mei (31 hari) → pembanding = 31 hari sebelum 1 Mei = 31 Mar - 30 Apr (bukan 1-30 April).&#10;&#10;Aktifkan toggle 'Bandingkan (VS)' untuk memilih range pembanding sendiri."></i>
                </div>
            </div>
        </div>

        <div class="col-lg-12 mb-3">
            <div class="filter-card">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <div class="filter-label">Periode</div>
                        <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                        <input type="hidden" id="start_date" value="<?= $start_date ?>">
                        <input type="hidden" id="end_date" value="<?= $until_date ?>">
                    </div>
                    <div class="col-md-7">
                        <div class="filter-label">Bandingkan (VS)</div>
                        <div class="compare-section">
                            <div class="toggle-row">
                                <span class="toggle-label">Bandingkan (VS)</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="compareToggle" <?= ($compare_mode ?? 'off') === 'on' ? 'checked' : '' ?>>
                                    <span class="knob"></span>
                                    <span class="toggle-bg"></span>
                                </label>
                                <span class="text-muted small" id="compareStateLabel"><?= ($compare_mode ?? 'off') === 'on' ? 'ON' : 'OFF' ?></span>
                            </div>
                            <div class="mt-3" id="comparePickerWrap" style="<?= ($compare_mode ?? 'off') === 'on' ? '' : 'display:none;' ?>">
                                <div class="filter-label">Periode Perbandingan</div>
                                <input type="text" class="form-control" id="tanggal_compare" placeholder="Pilih rentang tanggal perbandingan...">
                                <input type="hidden" id="compare_start_date" value="<?= $start_date_2 ?>">
                                <input type="hidden" id="compare_end_date" value="<?= $until_date_2 ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="summary-grid position-relative">
                <div class="loading-overlay" id="summaryLoading">Memuat data...</div>
                <?php foreach ($metrics as $key => $meta) :
                    $value_main = $period_main[$key] ?? 0;
                    $value_compare = $period_compare[$key] ?? 0;
                    $change = calc_digiads_change($value_compare, $value_main);
                    $is_up = $change !== null && $change >= 0;
                    $trend_class = $is_up ? 'trend-up' : 'trend-down';
                    $trend_icon = $is_up ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
                    $change_text = $change === null ? 'N/A' : number_format(abs($change), 2, ',', '.') . '%';
                    $active = in_array($key, ['gmv', 'spent']) ? 'is-active' : '';
                ?>
                    <div class="metric-card <?= $active ?>" data-metric="<?= $key ?>">
                        <div class="metric-header">
                            <div>
                                <div class="metric-title"><?= $meta['label'] ?></div>
                                <div class="metric-desc"><?= $meta['desc'] ?></div>
                            </div>
                            <div class="metric-toggle">
                                <input type="checkbox" class="metric-checkbox" data-metric-checkbox="<?= $key ?>" <?= $active ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <div class="metric-value-row">
                            <div class="metric-value" data-role="metric-value"><?= format_digiads_metric_value($value_main, $meta['format']) ?></div>
                            <div class="metric-change <?= $trend_class ?>">
                                <i class="bi <?= $trend_icon ?>"></i>
                                <span data-role="metric-change"><?= $change_text ?> vs Periode 1</span>
                            </div>
                        </div>
                        <div class="metric-period" data-role="metric-compare">Periode 1: <?= format_digiads_metric_value($value_compare, $meta['format']) ?></div>
                        <div class="detail-link summary-detail" data-detail="summary-<?= $key ?>">Lihat Detail <i class="bi bi-arrow-right"></i></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="chart-card position-relative">
                <div class="loading-overlay" id="chartLoading">Memuat chart...</div>
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                    <div>
                        <div class="chart-title">Trend Harian</div>
                        <div class="chart-subtitle">Periode utama dengan opsi perbandingan.</div>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="digiadsOverviewChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="w-100 overview-general product-section">
    <div class="summary-grid position-relative">
        <div class="loading-overlay" id="matrixLoading">Memuat data...</div>

        <div class="metric-card product-card" data-card="traffic">
            <div class="metric-header">
                <div class="metric-title">
                    Traffic
                    <i class="bi bi-question-circle text-muted" id="trafficBreakdownInfo" style="cursor:pointer;"></i>
                </div>
                <div class="detail-link matrix-detail traffic-detail-btn" data-detail="traffic">Lihat Detail <i class="bi bi-arrow-right"></i></div>
            </div>
            <div class="traffic-card-chart-subtitle">Total traffic per hari</div>
            <div class="traffic-card-chart-wrap">
                <canvas id="trafficMiniChart"></canvas>
            </div>
        </div>

        <div class="metric-card product-card" data-card="qty">
            <div class="metric-header">
                <div class="metric-title">Qty Produk Terjual</div>
                <div class="detail-link matrix-detail traffic-detail-btn" data-detail="qty">Lihat Detail <i class="bi bi-arrow-right"></i></div>
            </div>
            <div class="traffic-card-chart-subtitle">Top 5 produk dengan penjualan tertinggi</div>
            <div class="qty-top-list" id="qtyTopList">
                <div class="qty-top-item"><span class="name">-</span><span class="value">0</span></div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('component/distribusi_channel_modal'); ?>
<link rel="stylesheet" href="<?= base_url() ?>assets/css/distribusi_channel_modal.css?v=<?= @filemtime(FCPATH . 'assets/css/distribusi_channel_modal.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light-border.css">
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>

<script>
    const metricConfig = {
        gmv: { label: 'GMV', color: '#0f8b8d', format: 'currency' },
        spent: { label: 'Spent', color: '#f0b429', format: 'currency' },
        ratio: { label: 'Rasio', color: '#4b7f52', format: 'percent' }
    };

    const platformLabels = {
        tiktok: 'TikTok',
        shopee: 'Shopee',
        meta: 'Meta',
        lazada: 'Lazada',
        manual: 'Manual'
    };

    const platformColors = {
        tiktok: '#0f8b8d',
        shopee: '#3b82f6',
        meta: '#2563eb',
        lazada: '#8b5cf6',
        manual: '#94a3b8'
    };

    const state = {
        start_date: "<?= $start_date ?>",
        end_date: "<?= $until_date ?>",
        compare_mode: "<?= $compare_mode ?? 'off' ?>",
        compare_start_date: "<?= $start_date_2 ?>",
        compare_end_date: "<?= $until_date_2 ?>"
    };

    let summaryBreakdown = { gmv: {}, spent: {}, ratio: {} };
    let matrixData = { cards: {}, traffic: { main: {}, compare: {} }, qty: { products: [] } };
    let trafficBreakdownTippy = null;
    let trafficMiniChart = null;

    const activeMetrics = new Set(['gmv', 'spent']);
    const cards = document.querySelectorAll('.metric-card[data-metric]');
    const metricCheckboxes = document.querySelectorAll('.metric-checkbox');
    const summaryLoading = document.getElementById('summaryLoading');
    const chartLoading = document.getElementById('chartLoading');
    const matrixLoading = document.getElementById('matrixLoading');
    const mainRangeText = document.getElementById('mainRangeText');
    const compareRangeText = document.getElementById('compareRangeText');
    const compareStateLabel = document.getElementById('compareStateLabel');

    const formatShort = (value) => {
        const absValue = Math.abs(value || 0);
        if (absValue >= 1_000_000_000) {
            const val = value / 1_000_000_000;
            return `${val.toFixed(val % 1 === 0 ? 0 : 1).replace('.', ',')} M`;
        }
        if (absValue >= 1_000_000) {
            const val = value / 1_000_000;
            return `${val.toFixed(val % 1 === 0 ? 0 : 1).replace('.', ',')} JT`;
        }
        return new Intl.NumberFormat('id-ID').format(value || 0);
    };

    const formatMetricValue = (value, format) => {
        if (format === 'currency') return `Rp ${formatShort(value)}`;
        if (format === 'percent') return `${(value || 0).toFixed(2).replace('.', ',')}%`;
        return formatShort(value);
    };

    const calcChange = (compareValue, mainValue) => {
        if (!compareValue) return null;
        return ((mainValue - compareValue) / compareValue) * 100;
    };

    const showLoading = (isLoading) => {
        summaryLoading.classList.toggle('is-visible', isLoading);
        chartLoading.classList.toggle('is-visible', isLoading);
    };

    const updateCards = (totalsMain, totalsCompare) => {
        cards.forEach(card => {
            const metric = card.dataset.metric;
            const meta = metricConfig[metric];
            const mainValue = totalsMain[metric] ?? 0;
            const compareValue = totalsCompare[metric] ?? 0;
            const change = calcChange(compareValue, mainValue);
            const trendUp = change !== null && change >= 0;

            card.querySelector('[data-role="metric-value"]').textContent = formatMetricValue(mainValue, meta.format);
            card.querySelector('[data-role="metric-compare"]').textContent = `Periode 1: ${formatMetricValue(compareValue, meta.format)}`;

            const changeNode = card.querySelector('[data-role="metric-change"]');
            changeNode.textContent = `${change === null ? 'N/A' : Math.abs(change).toFixed(2).replace('.', ',')}% vs Periode 1`;

            const wrap = changeNode.closest('.metric-change');
            wrap.classList.toggle('trend-up', change !== null && trendUp);
            wrap.classList.toggle('trend-down', change !== null && !trendUp);
            wrap.querySelector('i').className = `bi ${change === null ? 'bi-dash' : (trendUp ? 'bi-arrow-up-right' : 'bi-arrow-down-right')}`;
        });
    };

    const ctx = document.getElementById('digiadsOverviewChart').getContext('2d');
    let overviewChart;

    const hexToRgba = (hex, alpha) => {
        const clean = hex.replace('#', '');
        const int = parseInt(clean, 16);
        const r = (int >> 16) & 255;
        const g = (int >> 8) & 255;
        const b = int & 255;
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    };

    const buildGradient = (color) => {
        const gradient = ctx.createLinearGradient(0, 0, 0, ctx.canvas.clientHeight);
        gradient.addColorStop(0, hexToRgba(color, 0.25));
        gradient.addColorStop(0.75, hexToRgba(color, 0));
        return gradient;
    };

    const updateChart = (dailyMain, dailyCompare) => {
        const labels = dailyMain.map(item => moment(item.date, 'YYYY-MM-DD').format('D MMM'));
        const datasets = [];
        const activeList = Array.from(activeMetrics);

        const metricMaxes = activeList.map(metricKey => ({
            key: metricKey,
            max: Math.max(...dailyMain.map(item => Number(item[metricKey] || 0)))
        })).sort((a, b) => b.max - a.max);

        let dualAxis = false;
        if (metricMaxes.length >= 2) {
            const maxA = metricMaxes[0].max;
            const maxB = metricMaxes[1].max;
            const ratio = Math.max(maxA, maxB) / Math.max(1, Math.min(maxA, maxB));
            dualAxis = ratio >= 3;
        }

        const primaryKey = metricMaxes[0]?.key;
        const secondaryKey = metricMaxes[1]?.key;
        const axisMeta = {
            y: primaryKey ? metricConfig[primaryKey] : { format: 'number', label: '' },
            y1: secondaryKey ? metricConfig[secondaryKey] : { format: 'number', label: '' }
        };

        activeList.forEach((key) => {
            const config = metricConfig[key];
            const axisId = dualAxis && key !== primaryKey ? 'y1' : 'y';

            datasets.push({
                label: config.label,
                data: dailyMain.map(item => item[key] ?? 0),
                borderColor: config.color,
                backgroundColor: buildGradient(config.color),
                fill: 'start',
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 3,
                tension: 0.35,
                yAxisID: axisId
            });

            if (state.compare_mode === 'on') {
                datasets.push({
                    label: `${config.label} (Banding)`,
                    data: dailyCompare.map(item => item[key] ?? 0),
                    borderColor: config.color,
                    borderDash: [6, 6],
                    fill: false,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 3,
                    tension: 0.35,
                    yAxisID: axisId
                });
            }
        });

        if (overviewChart) overviewChart.destroy();
        overviewChart = new Chart(ctx, {
            type: 'line',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                const key = Object.keys(metricConfig).find(k => context.dataset.label.startsWith(metricConfig[k].label));
                                return `${context.dataset.label}: ${formatMetricValue(context.raw, metricConfig[key]?.format || 'number')}`;
                            },
                            labelColor: function(context) {
                                const ds = context.dataset || {};
                                const isCompare = Array.isArray(ds.borderDash) && ds.borderDash.length > 0;
                                return {
                                    borderColor: ds.borderColor,
                                    backgroundColor: isCompare ? '#ffffff' : ds.borderColor,
                                    borderWidth: 2,
                                    borderDash: isCompare ? [3, 3] : [],
                                    borderRadius: 0
                                };
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 8 } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (axisMeta.y.format === 'percent') return `${Number(value).toFixed(0)}%`;
                                return formatShort(value);
                            }
                        },
                        title: {
                            display: !!axisMeta.y.label,
                            text: axisMeta.y.format === 'currency' ? `${axisMeta.y.label} (Rp)` : axisMeta.y.label,
                            color: axisMeta.y.color || '#0f172a'
                        },
                        grid: { color: 'rgba(15,23,42,0.08)', drawBorder: false }
                    },
                    y1: {
                        display: dualAxis,
                        position: 'right',
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (axisMeta.y1.format === 'percent') return `${Number(value).toFixed(0)}%`;
                                return formatShort(value);
                            }
                        },
                        title: {
                            display: dualAxis && !!axisMeta.y1.label,
                            text: axisMeta.y1.format === 'currency' ? `${axisMeta.y1.label} (Rp)` : axisMeta.y1.label,
                            color: axisMeta.y1.color || '#0f172a'
                        },
                        grid: { drawOnChartArea: false, drawBorder: false }
                    }
                }
            }
        });
    };

    const updateMatrixCards = (cardsData) => {
        const qtyCard = document.querySelector('.product-card[data-card="qty"]');
        if (qtyCard) {
            const topList = document.getElementById('qtyTopList');
            if (topList) {
                const items = (matrixData.qty?.products || []).slice(0, 5);
                topList.innerHTML = items.length ? items.map((item) => `
                    <div class="qty-top-item">
                        <span class="name">${item.product_name || '-'}</span>
                        <span class="value">${formatShort(item.qty || 0)}</span>
                    </div>
                `).join('') : '<div class="qty-top-item"><span class="name">Tidak ada data</span><span class="value">0</span></div>';
            }
        }
    };

    const renderTrafficMiniChart = () => {
        const canvas = document.getElementById('trafficMiniChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        const daily = (matrixData.traffic?.daily || []);
        const labels = daily.map(item => moment(item.date, 'YYYY-MM-DD').format('D MMM'));
        const values = daily.map(item => Number(item.total || 0));

        if (trafficMiniChart) {
            trafficMiniChart.destroy();
        }

        const chartCtx = canvas.getContext('2d');
        const barGradient = chartCtx.createLinearGradient(0, 0, 0, canvas.height || 120);
        barGradient.addColorStop(0, 'rgba(15, 139, 141, 0.7)');
        barGradient.addColorStop(1, 'rgba(15, 139, 141, 0.24)');

        trafficMiniChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Traffic',
                    data: values,
                    backgroundColor: barGradient,
                    borderColor: 'rgba(15, 139, 141, 0.58)',
                    borderWidth: 1,
                    borderRadius: 5,
                    maxBarThickness: 14
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#ffffff',
                        titleColor: '#1f2937',
                        bodyColor: '#1f2937',
                        borderColor: 'rgba(148, 163, 184, 0.35)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                return `Traffic: ${formatShort(context.raw || 0)}`;
                            }
                        }
                    }
                },
                onClick: function(evt, elements) {
                    if (!elements || !elements.length) return;
                    const idx = elements[0].index;
                    const dailyPoint = daily[idx];
                    if (!dailyPoint || !dailyPoint.date) return;
                    openTrafficDetailByDate(dailyPoint.date);
                },
                scales: {
                    x: {
                        ticks: {
                            maxTicksLimit: 6,
                            font: { size: 9 }
                        },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            maxTicksLimit: 4,
                            font: { size: 9 },
                            callback: function(value) {
                                return formatShort(value);
                            }
                        },
                        grid: {
                            color: 'rgba(15, 23, 42, 0.08)',
                            drawBorder: false
                        }
                    }
                }
            }
        });
    };

    const renderTrafficDetailModal = (trafficData, startDateText, endDateText) => {
        const main = trafficData?.main || {};
        const compare = trafficData?.compare || {};
        const live = Number(main.live_impression || 0);
        const video = Number(main.video_impression || 0);
        const pcard = Number(main.pcard_impression || 0);

        const liveCompare = Number(compare.live_impression || 0);
        const videoCompare = Number(compare.video_impression || 0);
        const pcardCompare = Number(compare.pcard_impression || 0);

        const growth = (mainVal, compareVal) => {
            if (!compareVal) return { text: '0,00%', up: true };
            const change = ((mainVal - compareVal) / compareVal) * 100;
            return {
                text: `${Math.abs(change).toFixed(2).replace('.', ',')}%`,
                up: change >= 0
            };
        };

        const liveGrowth = growth(live, liveCompare);
        const videoGrowth = growth(video, videoCompare);
        const pcardGrowth = growth(pcard, pcardCompare);

        const labels = ['Impresi Live', 'Impresi Video', 'Impresi Kartu Produk'];
        const values = [live, video, pcard];
        const colors = ['#14b8a6', '#2563eb', '#8b5cf6'];
        const legend = buildLegend(labels, values, colors);

        const rowHtml = (label, value, chg, metricKey) => `
            <div class="traffic-breakdown-item">
                <div class="traffic-breakdown-title">${label} <i class="bi bi-question-circle text-muted traffic-top5-trigger" data-metric="${metricKey}" style="font-size:0.85rem;cursor:pointer;"></i></div>
                <div class="traffic-breakdown-value">
                    <span>${formatShort(value)}</span>
                    <span class="traffic-breakdown-change ${chg.up ? 'up' : 'down'}">
                        <i class="bi ${chg.up ? 'bi-caret-up-fill' : 'bi-caret-down-fill'}"></i> ${chg.text}
                    </span>
                </div>
            </div>
        `;

        setModal(
            'Detail Traffic Produk',
            `Sumber: Tiktok Produk Analytics (${startDateText}${startDateText === endDateText ? '' : ' - ' + endDateText})`,
            `
            <div class="distribusi-layout">
                <div class="distribusi-left">
                    <div class="distribusi-donut-wrap"><canvas id="detailDonut"></canvas></div>
                    <div class="distribusi-legend">${legend}</div>
                </div>
                <div class="distribusi-panel">
                    ${rowHtml('Impresi Live', live, liveGrowth, 'live')}
                    ${rowHtml('Impresi Video', video, videoGrowth, 'video')}
                    ${rowHtml('Impresi Kartu Produk', pcard, pcardGrowth, 'pcard')}
                </div>
            </div>`
        );
        renderDonut('detailDonut', labels, values, colors, 'number');
        initTrafficMetricTippies();
        modal.show();
    };

    const openTrafficDetailByDate = (dateStr) => {
        const selected = moment(dateStr, 'YYYY-MM-DD');
        if (!selected.isValid()) return;

        setModal('Detail Traffic Produk', '', '<div class="distribusi-loading"><div class="spinner-border spinner-border-sm" role="status"></div><span>Memuat data...</span></div>');
        modal.show();

        $.ajax({
            url: '<?= base_url() ?>ajax/get_overview_digiads_matrix',
            dataType: 'json',
            data: {
                start_date: dateStr,
                end_date: dateStr,
                compare_start_date: selected.clone().subtract(1, 'day').format('YYYY-MM-DD'),
                compare_end_date: selected.clone().subtract(1, 'day').format('YYYY-MM-DD')
            },
            success: function(res) {
                const trafficData = res?.traffic || {};
                matrixData.traffic = trafficData;
                renderTrafficDetailModal(trafficData, selected.format('DD MMM YYYY'), selected.format('DD MMM YYYY'));
            },
            error: function() {
                setModal('Detail Traffic Produk', '', '<div class="text-danger">Gagal memuat data.</div>');
            }
        });
    };

    const initTrafficBreakdownTippy = () => {
        const el = document.getElementById('trafficBreakdownInfo');
        if (!el || typeof window.tippy === 'undefined') {
            return;
        }

        const rows = (matrixData.traffic?.products || []);
        const bodyRows = rows.map((row, idx) => `
            <div class="tippy-item">
                <div class="tippy-item-head">
                    <span class="tippy-rank">#${idx + 1}</span>
                    <span class="tippy-shop">${row.shop_name || '-'}</span>
                </div>
                <div class="tippy-product">${row.product_name || '-'}</div>
                <div class="tippy-metrics">Live: ${formatShort(row.live_impression || 0)} | Video: ${formatShort(row.video_impression || 0)} | Kartu Produk: ${formatShort(row.pcard_impression || 0)}<br>Total: ${formatShort(row.total_traffic || 0)}</div>
            </div>
        `).join('');

        const content = `
            <div class="traffic-tippy">
                <div class="tippy-title">Breakdown Traffic per Produk</div>
                <input type="text" class="tippy-search traffic-breakdown-search" placeholder="Cari toko / produk...">
                <div class="tippy-wrap">
                    <div class="tippy-list">${bodyRows || '<div class="tippy-item">Tidak ada data</div>'}</div>
                    <div class="tippy-empty">Data tidak ditemukan</div>
                </div>
            </div>
        `;

        if (trafficBreakdownTippy) {
            trafficBreakdownTippy.setContent(content);
            return;
        }

        const instances = tippy(el, {
            content,
            allowHTML: true,
            interactive: true,
            trigger: 'mouseenter focus',
            placement: 'right',
            theme: 'light-border',
            maxWidth: 560,
            popperOptions: {
                modifiers: [
                    {
                        name: 'flip',
                        options: {
                            fallbackPlacements: []
                        }
                    }
                ]
            }
        });
        trafficBreakdownTippy = Array.isArray(instances) ? instances[0] : instances;
    };

    const initTrafficBreakdownSearch = () => {
        if (window.__trafficBreakdownSearchBound) {
            return;
        }
        window.__trafficBreakdownSearchBound = true;

        document.addEventListener('input', function(e) {
            const input = e.target;
            if (!input.classList.contains('traffic-breakdown-search')) {
                return;
            }

            const keyword = (input.value || '').toLowerCase().trim();
            const root = input.closest('.traffic-tippy');
            if (!root) {
                return;
            }

            const items = root.querySelectorAll('.tippy-list .tippy-item');
            let visibleCount = 0;
            items.forEach((item) => {
                const haystack = (item.textContent || '').toLowerCase();
                const show = keyword === '' || haystack.includes(keyword);
                item.style.display = show ? '' : 'none';
                if (show) {
                    visibleCount++;
                }
            });

            const emptyNode = root.querySelector('.tippy-empty');
            if (emptyNode) {
                emptyNode.classList.toggle('is-visible', visibleCount === 0);
            }
        });
    };

    const initTrafficMetricTippies = () => {
        if (typeof window.tippy === 'undefined') {
            return;
        }

        const buildTop5Content = (metric) => {
            const items = (matrixData.traffic?.top5?.[metric] || []).slice(0, 5);
            const metricLabel = metric === 'live' ? 'Impresi Live' : (metric === 'video' ? 'Impresi Video' : 'Impresi Kartu Produk');
            const metricField = metric === 'live' ? 'live_impression' : (metric === 'video' ? 'video_impression' : 'pcard_impression');

            const rows = items.map((item, idx) => `
                <div class="tippy-item">
                    <div class="tippy-item-head">
                        <span class="tippy-rank">#${idx + 1}</span>
                        <span class="tippy-shop">${item.shop_name || '-'}</span>
                    </div>
                    <div class="tippy-product">${item.product_name || '-'}</div>
                    <div class="tippy-metrics">${metricLabel}: ${formatShort(item[metricField] || 0)}</div>
                </div>
            `).join('');

            return `
                <div class="traffic-tippy">
                    <div class="tippy-title">Top 5 ${metricLabel}</div>
                    <div class="tippy-wrap">
                        <div class="tippy-list">${rows || '<div class="tippy-item">Tidak ada data</div>'}</div>
                    </div>
                </div>
            `;
        };

        document.querySelectorAll('.traffic-top5-trigger').forEach((el) => {
            const metric = el.getAttribute('data-metric');
            if (!metric) return;
            if (el._tippy) {
                el._tippy.destroy();
            }
            tippy(el, {
                content: buildTop5Content(metric),
                allowHTML: true,
                interactive: true,
                trigger: 'mouseenter focus',
                placement: 'right',
                theme: 'light-border',
                maxWidth: 600,
                popperOptions: {
                    modifiers: [
                        {
                            name: 'flip',
                            options: {
                                fallbackPlacements: []
                            }
                        }
                    ]
                }
            });
        });
    };

    const fetchMatrixData = () => {
        matrixLoading.classList.add('is-visible');
        $.ajax({
            url: '<?= base_url() ?>ajax/get_overview_digiads_matrix',
            dataType: 'json',
            data: {
                start_date: state.start_date,
                end_date: state.end_date,
                compare_start_date: state.compare_start_date,
                compare_end_date: state.compare_end_date
            },
            success: function(res) {
                matrixData = res || { cards: {}, traffic: { main: {}, compare: {} }, qty: { products: [] } };
                updateMatrixCards(matrixData.cards || {});
                renderTrafficMiniChart();
                initTrafficBreakdownTippy();
            },
            complete: function() {
                matrixLoading.classList.remove('is-visible');
            }
        });
    };

    const fetchOverviewData = () => {
        showLoading(true);
        $.ajax({
            url: '<?= base_url() ?>ajax/get_overview_digiads',
            dataType: 'json',
            data: {
                start_date: state.start_date,
                end_date: state.end_date,
                compare_mode: state.compare_mode,
                compare_start_date: state.compare_start_date,
                compare_end_date: state.compare_end_date,
                metrics: Array.from(activeMetrics)
            },
            success: function(res) {
                state.compare_mode = res.compare_mode;
                state.compare_start_date = res.compare_range.start;
                state.compare_end_date = res.compare_range.end;

                mainRangeText.textContent = `${moment(res.main_range.start).format('DD MMM YYYY')} - ${moment(res.main_range.end).format('DD MMM YYYY')}`;
                compareRangeText.textContent = `${moment(res.compare_range.start).format('DD MMM YYYY')} - ${moment(res.compare_range.end).format('DD MMM YYYY')}`;

                updateCards(res.period_main.totals, res.period_compare.totals);
                updateChart(res.period_main.daily, res.period_compare.daily);
                summaryBreakdown = res.period_main.breakdown || { gmv: {}, spent: {}, ratio: {} };
                fetchMatrixData();

                if (state.compare_mode === 'on') {
                    $('#comparePickerWrap').show();
                } else {
                    $('#comparePickerWrap').hide();
                }
                compareStateLabel.textContent = state.compare_mode === 'on' ? 'ON' : 'OFF';

                const comparePicker = $('#tanggal_compare').data('daterangepicker');
                if (comparePicker) {
                    const startMoment = moment(state.compare_start_date, 'YYYY-MM-DD');
                    const endMoment = moment(state.compare_end_date, 'YYYY-MM-DD');
                    comparePicker.setStartDate(startMoment);
                    comparePicker.setEndDate(endMoment);
                    if (typeof comparePicker.updateInput === 'function') {
                        comparePicker.updateInput();
                    } else {
                        $('#tanggal_compare').val(`${startMoment.format('DD/MM/YYYY')} - ${endMoment.format('DD/MM/YYYY')}`);
                    }
                }
            },
            complete: function() {
                showLoading(false);
            }
        });
    };

    const presetRanges = {
        "Hari Ini": [moment(), moment()],
        "Kemarin": [moment().subtract(1, "days"), moment().subtract(1, "days")],
        "7 Hari Terakhir": [moment().subtract(6, "days"), moment()],
        "30 Hari Terakhir": [moment().subtract(29, "days"), moment()],
        "Bulan Ini": [moment().startOf("month"), moment().endOf("month")],
        "Bulan Lalu": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")]
    };

    const updateYearDropdowns = (picker) => {
        const currentYear = moment().year();
        const yearsToShow = [currentYear - 2, currentYear - 1, currentYear, currentYear + 1, currentYear + 2];
        setTimeout(() => {
            const $picker = picker.container;
            $picker.find('.right .yearselect').each(function() {
                const $select = $(this);
                const currentValue = parseInt($select.val(), 10);
                $select.empty();
                yearsToShow.forEach(year => $select.append($('<option>', { value: year, text: year })));
                $select.val(yearsToShow.includes(currentValue) ? currentValue : currentYear);
            });
            $picker.find('.left .yearselect').each(function() {
                const $select = $(this);
                const currentValue = parseInt($select.val(), 10);
                $select.empty();
                yearsToShow.forEach(year => $select.append($('<option>', { value: year, text: year })));
                $select.val(yearsToShow.includes(currentValue) ? currentValue : currentYear);
            });
        }, 50);
    };

    const initRangePicker = (inputId, startValue, endValue, onApply) => {
        const $input = $('#' + inputId);
        $input.daterangepicker({
            showDropdowns: true,
            ranges: {},
            alwaysShowCalendars: true,
            startDate: moment(startValue, "YYYY-MM-DD"),
            endDate: moment(endValue, "YYYY-MM-DD"),
            minDate: moment().subtract(5, "years"),
            maxDate: moment().add(5, "years"),
            opens: "center",
            drops: "auto",
            autoUpdateInput: true,
            locale: {
                format: "DD/MM/YYYY",
                separator: " - ",
                applyLabel: "Terapkan",
                cancelLabel: "Batal",
                fromLabel: "Dari",
                toLabel: "Sampai",
                customRangeLabel: "Custom",
                daysOfWeek: ["Mg", "Sn", "Sl", "Rb", "Km", "Jm", "Sb"],
                monthNames: [
                    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
                    "Juli", "Agustus", "September", "Oktober", "November", "Desember"
                ],
                firstDay: 1
            }
        }, function(start, end) {
            onApply(start, end);
        });

        const picker = $input.data('daterangepicker');
        const rangeContainer = $("<div class='custom-ranges'></div>");

        const updateActiveButton = (start, end) => {
            rangeContainer.find('button').removeClass('active');
            Object.entries(presetRanges).forEach(([label, dates]) => {
                if (start.isSame(dates[0], 'day') && end.isSame(dates[1], 'day')) {
                    rangeContainer.find('button').filter(function() {
                        return $(this).text() === label;
                    }).addClass("active");
                }
            });
        };

        $.each(presetRanges, function(label, dates) {
            const btn = $("<button type='button'></button>").text(label);
            btn.on("click", function() {
                picker.setStartDate(dates[0]);
                picker.setEndDate(dates[1]);
                picker.updateCalendars();
                picker.updateInput();
                updateActiveButton(dates[0], dates[1]);
                onApply(dates[0], dates[1]);
                updateYearDropdowns(picker);
            });
            rangeContainer.append(btn);
        });

        picker.container.prepend(rangeContainer);
        updateActiveButton(picker.startDate, picker.endDate);
        updateYearDropdowns(picker);

        $input.on('show.daterangepicker', function() {
            updateYearDropdowns(picker);
        });
    };

    metricCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const metric = this.dataset.metricCheckbox;
            const card = document.querySelector(`.metric-card[data-metric="${metric}"]`);
            if (!card) return;

            if (this.checked) {
                activeMetrics.add(metric);
                card.classList.add('is-active');
            } else {
                if (activeMetrics.size === 1) {
                    this.checked = true;
                    return;
                }
                activeMetrics.delete(metric);
                card.classList.remove('is-active');
            }
            fetchOverviewData();
        });
    });

    initRangePicker('tanggal', state.start_date, state.end_date, (start, end) => {
        state.start_date = start.format("YYYY-MM-DD");
        state.end_date = end.format("YYYY-MM-DD");
        $('#start_date').val(state.start_date);
        $('#end_date').val(state.end_date);
        fetchOverviewData();
    });

    initRangePicker('tanggal_compare', state.compare_start_date, state.compare_end_date, (start, end) => {
        state.compare_start_date = start.format("YYYY-MM-DD");
        state.compare_end_date = end.format("YYYY-MM-DD");
        $('#compare_start_date').val(state.compare_start_date);
        $('#compare_end_date').val(state.compare_end_date);
        if (state.compare_mode === 'on') {
            fetchOverviewData();
        }
    });

    $('#compareToggle').on('change', function() {
        state.compare_mode = this.checked ? 'on' : 'off';
        if (state.compare_mode === 'on') {
            $('#comparePickerWrap').slideDown(150);
        } else {
            $('#comparePickerWrap').slideUp(150);
        }
        compareStateLabel.textContent = state.compare_mode === 'on' ? 'ON' : 'OFF';
        fetchOverviewData();
    });

    const modalEl = document.getElementById('productDetailModal');
    const modal = new bootstrap.Modal(modalEl);
    const modalDialog = modalEl.querySelector('.modal-dialog');
    if (modalDialog) {
        modalDialog.classList.remove('modal-xl');
        modalDialog.classList.remove('modal-md');
        modalDialog.classList.add('modal-lg');
        modalDialog.classList.add('digiads-modal-wide');
    }
    const modalTitle = document.getElementById('productDetailModalLabel');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const modalContent = document.getElementById('modalContent');
    let modalDonut;

    const setModal = (title, subtitle, html) => {
        modalTitle.textContent = title;
        modalSubtitle.textContent = subtitle || '';
        modalContent.innerHTML = html;
    };

    const buildLegend = (labels, values, colors) => {
        const total = values.reduce((acc, val) => acc + val, 0) || 1;
        return labels.map((label, idx) => {
            const percent = ((values[idx] / total) * 100).toFixed(1).replace('.', ',');
            return `<div class="distribusi-legend-item"><span class="distribusi-legend-dot" style="background:${colors[idx]}"></span>${label} ${percent}%</div>`;
        }).join('');
    };

    const buildPanelItems = (labels, values, valueFormat = 'currency') => {
        const total = values.reduce((acc, val) => acc + val, 0) || 1;
        return labels.map((label, idx) => {
            const value = Number(values[idx] || 0);
            const percent = ((value / total) * 100).toFixed(1).replace('.', ',');
            const textVal = valueFormat === 'percent'
                ? `${value.toFixed(2).replace('.', ',')}%`
                : `Rp ${formatShort(value)}`;
            return `
                <div class="distribusi-panel-item">
                    <div class="distribusi-panel-title">${label}</div>
                    <div class="distribusi-panel-value">
                        <span>${textVal}</span>
                        <span class="distribusi-panel-change up">${percent}%</span>
                    </div>
                </div>
            `;
        }).join('');
    };

    const buildSpentPanelItems = (labels, values, tiktokSources = {}) => {
        const total = values.reduce((acc, val) => acc + val, 0) || 1;
        const tiktokAds = Number(tiktokSources.tiktok_ads_data || 0);
        const gmvMax = Number(tiktokSources.advertiser_spend || 0);
        return labels.map((label, idx) => {
            const value = Number(values[idx] || 0);
            const percent = ((value / total) * 100).toFixed(1).replace('.', ',');
            const isTikTok = label.toLowerCase() === 'tiktok';
            const mainText = `Rp ${formatShort(value)}`;
            const subText = isTikTok
                ? `<span class="digiads-subvalue">GMV Max Rp ${formatShort(gmvMax)} | Ads Rp ${formatShort(tiktokAds)}</span>`
                : '';
            return `
                <div class="distribusi-panel-item">
                    <div class="distribusi-panel-title">${label}</div>
                    <div class="distribusi-panel-value">
                        <span>${mainText}${subText}</span>
                        <span class="distribusi-panel-change up">${percent}%</span>
                    </div>
                </div>
            `;
        }).join('');
    };

    const renderDonut = (id, labels, values, colors, format = 'currency') => {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        if (modalDonut) modalDonut.destroy();
        const total = values.reduce((a, b) => a + b, 0) || 1;
        modalDonut = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }]
            },
            options: {
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const val = ctx.raw || 0;
                                const pct = ((val / total) * 100).toFixed(1).replace('.', ',');
                                const textVal = format === 'percent'
                                    ? `${val.toFixed(2).replace('.', ',')}%`
                                    : (format === 'number' ? `${formatShort(val)}` : `Rp ${formatShort(val)}`);
                                return `${ctx.label}: ${textVal} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    };

    $(document).on('click', '.summary-detail', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const detailType = ($(this).data('detail') || '').replace('summary-', '');
        const source = summaryBreakdown[detailType] || {};
        const keys = detailType === 'gmv'
            ? ['tiktok', 'shopee', 'lazada', 'manual']
            : (detailType === 'spent' ? ['tiktok', 'shopee', 'meta'] : Object.keys(platformLabels));
        const labels = keys.map(key => platformLabels[key]);
        const values = keys.map(key => Number(source[key] || 0));
        const colors = keys.map(key => platformColors[key]);
        const legend = buildLegend(labels, values, colors);
        const tiktokSources = summaryBreakdown.spent_tiktok_sources || {};
        const panel = detailType === 'spent'
            ? buildSpentPanelItems(labels, values, tiktokSources)
            : buildPanelItems(labels, values, detailType === 'ratio' ? 'percent' : 'currency');

        setModal(
            `Detail ${metricConfig[detailType]?.label || ''} per Platform`,
            `${moment(state.start_date).format('DD MMM YYYY')} - ${moment(state.end_date).format('DD MMM YYYY')}`,
            `
            <div class="distribusi-layout">
                <div class="distribusi-left">
                    <div class="distribusi-donut-wrap"><canvas id="detailDonut"></canvas></div>
                    <div class="distribusi-legend">${legend}</div>
                </div>
                <div class="distribusi-panel">
                    ${panel}
                </div>
            </div>`
        );

        renderDonut('detailDonut', labels, values, colors, detailType === 'ratio' ? 'percent' : 'currency');
        modal.show();
    });

    $(document).on('click', '.matrix-detail', function(e) {
        e.preventDefault();
        const detailType = $(this).data('detail');
        if (detailType === 'traffic') {
            renderTrafficDetailModal(
                matrixData.traffic,
                moment(state.start_date).format('DD MMM YYYY'),
                moment(state.end_date).format('DD MMM YYYY')
            );
            return;
        }

        const products = (matrixData.qty?.products || []).slice();
        products.sort((a, b) => Number(b.qty || 0) - Number(a.qty || 0));

        const rows = products.map((item, idx) => `
            <tr>
                <td>${idx + 1}</td>
                <td>${item.product_name}</td>
                <td>${formatShort(item.qty)}</td>
            </tr>
        `).join('');

        setModal(
            'Detail Qty Produk Terjual',
            `Breakdown produk terjual (${moment(state.start_date).format('DD MMM YYYY')} - ${moment(state.end_date).format('DD MMM YYYY')})`,
            `
            <div class="qty-modal-table-wrap">
                <table class="qty-modal-table">
                    <thead>
                        <tr>
                            <th style="width:52px;">#</th>
                            <th>Produk</th>
                            <th>Qty</th>
                        </tr>
                    </thead>
                    <tbody>${rows || '<tr><td colspan="3" style="text-align:center;">Tidak ada data</td></tr>'}</tbody>
                </table>
            </div>`
        );
        modal.show();
    });

    fetchOverviewData();
    initTrafficBreakdownSearch();
</script>
