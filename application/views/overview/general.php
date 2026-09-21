<style>
	.select2-container .select2-selection--multiple {
        min-height: 45px;
        /* box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.07) !important; */
        border-radius: 0.5rem !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        padding: 6px;
    }

    .select2-container .select2-search--inline .select2-search__field {
        padding-top: 6px !important;
    }
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
        cursor: pointer;
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

    .overview-general .compare-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        color: #5b6b7a;
        font-weight: 600;
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

    .overview-general .product-cards {
        position: relative;
        margin-bottom: 18px;
    }

    .overview-general .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 12px;
    }

    .overview-general .product-grid.product-grid-compact {
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .overview-general .product-scroll-list {
        max-height: 520px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .overview-general .product-scroll-list::-webkit-scrollbar {
        width: 8px;
    }

    .overview-general .product-scroll-list::-webkit-scrollbar-thumb {
        background: #d2dde9;
        border-radius: 8px;
    }

    .overview-general .product-card {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        border-radius: 14px;
        border: 1px solid #e3ebf3;
        background: #ffffff;
        padding: 12px;
        cursor: pointer;
        transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
    }

    .overview-general .product-card:hover {
        transform: translateY(-2px);
        border-color: #0f8b8d;
        box-shadow: 0 10px 20px rgba(12, 30, 58, 0.1);
    }

    .overview-general .product-card[data-card] {
        display: block;
        height: 400px;
        cursor: default;
        border: 2px solid #e8eef4;
    }

    .overview-general .product-card[data-card]:hover {
        transform: none;
        border-color: #e8eef4;
        box-shadow: 0 10px 24px rgba(12, 30, 58, 0.06);
    }

    .overview-general .product-card[data-card] .product-scroll-list {
        max-height: calc(100% - 24px);
        overflow-y: auto;
    }

    .overview-general .product-card-image {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid #e5edf5;
        flex-shrink: 0;
        background: #f8fafc;
    }

    .overview-general .product-card-content {
        min-width: 0;
        flex: 1;
    }

    .overview-general .product-card-title {
        font-size: 0.84rem;
        color: #1f2937;
        font-weight: 600;
        line-height: 1.3;
        margin-bottom: 2px;
    }

    .overview-general .product-card-total {
        font-size: 0.84rem;
        color: #0f172a;
        font-weight: 700;
        margin-bottom: 0;
    }

    .overview-general .product-channel-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 4px 10px;
        font-size: 0.78rem;
        color: #5b6b7a;
    }

    .overview-general .product-channel-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .overview-general .product-channel-item strong {
        color: #1f2937;
        font-weight: 700;
    }

    .overview-general .product-empty {
        grid-column: 1 / -1;
        border: 1px dashed #cdd8e4;
        border-radius: 12px;
        padding: 18px;
        color: #6b7c93;
        text-align: center;
        font-weight: 600;
        background: #f8fbfd;
    }

    .overview-general .product-static-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 0.9rem;
        color: #44566c;
        font-weight: 600;
        padding: 8px 0;
        border-bottom: 1px solid #edf2f7;
    }

    .overview-general .product-static-row:last-child {
        border-bottom: none;
    }

    .overview-general .product-static-row strong {
        color: #0f172a;
        font-weight: 700;
    }

    .overview-general .best-content-section {
        margin-top: 14px;
    }

    .overview-general .best-content-table-wrap {
        overflow-x: auto;
        border: 1px solid #e3ebf3;
        border-radius: 12px;
        background: #fff;
    }

    .overview-general .best-content-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 760px;
    }

    .overview-general .best-content-table th,
    .overview-general .best-content-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #eef3f8;
        font-size: 0.86rem;
        vertical-align: top;
        text-align: center;
    }

    .overview-general .best-content-table th {
        background: #f8fbfd;
        color: #5b6b7a;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .overview-general .best-content-cell-number {
        text-align: center;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
    }

    .overview-general .best-content-product-cell {
        width: 76px;
        text-align: center;
    }

    .overview-general .best-content-product-thumb {
        width: 110px;
        height: 110px;
        border-radius: 10px;
        border: 1px solid #d9e4ef;
        background: #f3f7fb;
        object-fit: cover;
        display: block;
        margin: 0 auto;
    }

    .overview-general .best-content-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
    }

    .overview-general .best-content-entry {
        width: 86px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: center;
    }

    .overview-general .best-content-thumb {
        position: relative;
        width: 86px;
        height: 120px;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #d9e4ef;
        display: block;
        background: #f3f7fb;
    }

    .overview-general .best-content-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .overview-general .best-content-play {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.28);
        color: #fff;
        font-size: 1.2rem;
    }

    .overview-general .best-content-stats {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 4px;
        align-items: center;
    }

    .overview-general .best-content-stat {
        font-size: 0.7rem;
        color: #5b6b7a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        line-height: 1.2;
        white-space: nowrap;
    }

    .overview-general .best-content-stat i {
        font-size: 0.72rem;
    }

    .overview-general .best-content-empty {
        color: #8a99ad;
        font-weight: 600;
        font-size: 0.82rem;
    }

    .tippy-box[data-theme~='light'] {
        background-color: #ffffff !important;
        color: #333333 !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #e5e7eb !important;
    }

    .tippy-box[data-theme~='light'] .tippy-arrow {
        color: #ffffff !important;
    }

    .tippy-box[data-theme~='light'] .tippy-content {
        color: inherit !important;
    }

    .tippy-box,
    .tippy-root,
    .tippy-popper {
        z-index: 99999 !important;
    }

    .tt-carousel-frame {
        width: 320px;
        height: 520px;
        overflow: hidden;
        border-radius: 8px;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .tt-carousel-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .tt-carousel-controls {
        margin-top: 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .tt-btn {
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 6px;
        width: 28px;
        height: 28px;
        line-height: 24px;
        font-size: 18px;
        cursor: pointer;
    }

    .tt-dots {
        display: flex;
        gap: 6px;
        align-items: center;
        justify-content: center;
        flex: 1;
    }

    .tt-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #d1d5db;
        cursor: pointer;
    }

    .tt-dot.active {
        background: #111827;
    }

    .tt-counter {
        margin-top: 6px;
        font-size: 12px;
        color: #6b7280;
        text-align: center;
    }

    .overview-general .product-mini-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        border: 1px solid #e7eef6;
        border-radius: 10px;
        padding: 8px 10px;
        background: #fff;
    }

    .overview-general .product-mini-item.is-clickable {
        cursor: pointer;
        transition: border-color 0.16s ease, box-shadow 0.16s ease;
    }

    .overview-general .product-mini-item.is-clickable:hover {
        border-color: #0f8b8d;
        box-shadow: 0 6px 16px rgba(12, 30, 58, 0.08);
    }

    .overview-general .product-mini-item .meta {
        flex: 1;
        min-width: 0;
    }

    .overview-general .product-mini-item .name {
        flex: 1;
        min-width: 0;
        font-size: 0.84rem;
        color: #1f2937;
        font-weight: 600;
        line-height: 1.3;
        margin-bottom: 2px;
    }

    .overview-general .product-mini-item .value {
        font-size: 0.84rem;
        color: #0f172a;
        font-weight: 700;
    }

    .overview-general #endorseTotalValue,
    .overview-general #gmvTotalValue,
    .overview-general #adsTotalValue,
    .overview-general #affiliateTotalValue {
        margin-right: 8px;
    }

    .overview-general .donut-layout {
        display: flex;
        gap: 16px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .overview-general .donut-wrap {
        width: 180px;
        height: 180px;
        position: relative;
    }

    .overview-general .donut-legend {
        display: flex;
        flex-direction: column;
        gap: 6px;
        font-size: 0.85rem;
        color: #4b5b70;
        margin-top: 10px;
    }

    .overview-general .donut-legend span {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }

    .overview-general .donut-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }

    .overview-general .channel-summary {
        flex: 1;
        min-width: 180px;
        background: #f9fbfd;
        border-radius: 16px;
        padding: 12px;
        border: 1px solid #e3ebf3;
    }

    .overview-general .channel-item {
        padding: 10px 0;
        border-bottom: 1px solid #e5edf5;
    }

    .overview-general .channel-item:last-child {
        border-bottom: none;
    }

    .overview-general .channel-item h6 {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1b2a3d;
        margin-bottom: 4px;
    }

    .overview-general .channel-item .value {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
    }

    .overview-general .channel-item .percent {
        font-size: 0.85rem;
        color: #1d9b5c;
        font-weight: 600;
        margin-left: 6px;
    }

    .overview-general .product-loading {
        min-height: 180px;
    }

    .overview-general .popup-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 12px;
    }

    .overview-general .popup-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #e5edf5;
        font-weight: 600;
        color: #1f2937;
    }

    .overview-general .popup-row:last-child {
        border-bottom: none;
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

    .overview-general #generalOverviewChart {
        height: 100% !important;
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
$period_main = $general_period_1['totals'];
$period_compare = $general_period_2['totals'];

$metrics = [
    'gmv' => [
        'label' => 'GMV',
        'desc' => 'Total omset',
        'format' => 'currency'
    ],
    'spent' => [
        'label' => 'Spent',
        'desc' => 'Total Marketing (Ads + KOL + Expense Marketing)',
        'format' => 'currency'
    ],
    'traffic' => [
        'label' => 'Traffic Product',
        'desc' => 'Total impression product',
        'format' => 'number'
    ],
    'ratio' => [
        'label' => 'Rasio',
        'desc' => 'Total Spent / Total Omset * 100%',
        'format' => 'percent'
    ]
];

if (!function_exists('format_metric_value')) {
    function format_metric_value($value, $format)
    {
        if ($format === 'currency') {
            return 'Rp ' . number_format($value, 0, ',', '.');
        }
        if ($format === 'percent') {
            return number_format($value, 2, ',', '.') . '%';
        }
        return number_format($value, 0, ',', '.');
    }
}

if (!function_exists('calc_change')) {
    function calc_change($v1, $v2)
    {
        if ($v1 == 0) {
            return null;
        }
        return (($v2 - $v1) / $v1) * 100;
    }
}
?>

<div class="w-100 overview-general">
    <?php $selected_product_ids = $selected_product_ids ?? []; ?>
    <div class="row align-items-center">
        <?php $this->load->view('overview/menu') ?>

        <div class="col-lg-12 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <div class="page-title">Data usaha</div>
                    <div class="page-subtitle">Ringkasan performa usaha dengan breakdown harian.</div>
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
                    <div class="col-md-4">
                        <div class="filter-label">Periode</div>
                        <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                        <input type="hidden" id="start_date" value="<?= $start_date ?>">
                        <input type="hidden" id="end_date" value="<?= $until_date ?>">
                    </div>
                    <div class="col-md-4">
                        <div class="filter-label">Produk</div>
                        <select class="form-control select2" id="product_ids" multiple data-placeholder="Semua Produk">
                            <?php foreach (($general_products ?? []) as $product) :
                                $is_selected = in_array((int)$product['id'], $selected_product_ids, true) ? 'selected' : '';
                            ?>
                                <option value="<?= (int)$product['id'] ?>" <?= $is_selected ?>><?= htmlspecialchars($product['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
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
                    $change = calc_change($value_compare, $value_main);
                    $is_up = $change !== null && $change >= 0;
                    $trend_class = $is_up ? 'trend-up' : 'trend-down';
                    $trend_icon = $is_up ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
                    $change_text = $change === null ? 'N/A' : number_format(abs($change), 2, ',', '.') . '%';
                    $active = in_array($key, ['gmv', 'spent']) ? 'is-active' : '';
                ?>
                    <div class="metric-card <?= $active ?>" data-metric="<?= $key ?>">
                        <div class="metric-header">
                            <div>
                                <div class="metric-title">
                                    <?= $meta['label'] ?>
                                    <i class="bi bi-question-circle text-muted" title="<?= $meta['desc'] ?>"></i>
                                </div>
                                <div class="metric-desc"><?= $meta['desc'] ?></div>
                            </div>
                            <div class="metric-toggle">
                                <i class="bi bi-check-lg"></i>
                            </div>
                        </div>
                        <div class="metric-value" data-role="metric-value"><?= format_metric_value($value_main, $meta['format']) ?></div>
                        <div class="metric-period" data-role="metric-compare">Periode 1: <?= format_metric_value($value_compare, $meta['format']) ?></div>
                        <div class="metric-change <?= $trend_class ?>">
                            <i class="bi <?= $trend_icon ?>"></i>
                            <span data-role="metric-change"><?= $change_text ?> vs Periode 1</span>
                        </div>
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
                    <canvas id="generalOverviewChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="w-100 overview-general product-section">
    <div class="section-title">Distribusi channel produk</div>
    <div class="summary-grid product-cards">
        <div class="loading-overlay product-loading" id="productLoading">Memuat data...</div>
        <div class="metric-card product-card" data-card="gmv">
            <div class="metric-header">
                <div class="metric-title">GMV</div>
                <div class="metric-title" id="gmvTotalValue">Rp 0</div>
            </div>
            <div class="product-scroll-list">
                <div class="product-grid" id="gmvProductGrid"></div>
            </div>
        </div>
        <div class="metric-card product-card" data-card="endorse">
            <div class="metric-header">
                <div class="metric-title">Spent Endorse</div>
                <div class="metric-title" id="endorseTotalValue">Rp 0</div>
            </div>
            <div class="product-scroll-list">
                <div class="product-grid product-grid-compact" id="endorseProductGrid"></div>
            </div>
        </div>
        <div class="metric-card product-card" data-card="ads">
            <div class="metric-header">
                <div class="metric-title">Spent Ads</div>
                <div class="metric-title" id="adsTotalValue">Rp 0</div>
            </div>
            <div class="product-scroll-list">
                <div class="product-grid product-grid-compact" id="adsProductGrid"></div>
            </div>
        </div>
        <div class="metric-card product-card" data-card="affiliate">
            <div class="metric-header">
                <div class="metric-title">Spent Affiliate</div>
                <div class="metric-title" id="affiliateTotalValue">Rp 0</div>
            </div>
            <div class="product-scroll-list">
                <div class="product-grid product-grid-compact" id="affiliateProductGrid"></div>
            </div>
        </div>
    </div>

</div>

<div class="w-100 overview-general best-content-section">
    <div class="section-title">Top Konten FYP</div>
    <div class="best-content-table-wrap">
        <table class="best-content-table">
            <thead>
                <tr>
                    <th style="width:90px;">Produk</th>
                    <th>Top Konten Storyteller</th>
                    <th>Top Konten Endorse</th>
                </tr>
            </thead>
            <tbody id="bestFypTableBody">
                <tr>
                    <td colspan="3" class="product-empty">Memuat data top konten FYP...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php $this->load->view('component/distribusi_channel_modal'); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const metricConfig = {
        gmv: {
            label: 'GMV',
            color: '#0f8b8d',
            format: 'currency'
        },
        spent: {
            label: 'Spent',
            color: '#f0b429',
            format: 'currency'
        },
        traffic: {
            label: 'Traffic Product',
            color: '#e1526a',
            format: 'number'
        },
        ratio: {
            label: 'Rasio',
            color: '#4b7f52',
            format: 'percent'
        }
    };

    const state = {
        start_date: "<?= $start_date ?>",
        end_date: "<?= $until_date ?>",
        compare_mode: "<?= $compare_mode ?? 'off' ?>",
        compare_start_date: "<?= $start_date_2 ?>",
        compare_end_date: "<?= $until_date_2 ?>",
        product_ids: <?= json_encode(array_map('intval', $selected_product_ids)) ?>,
        metrics: ['gmv', 'spent']
    };

    const activeMetrics = new Set(state.metrics);
    const cards = document.querySelectorAll('.metric-card[data-metric]');
    const summaryLoading = document.getElementById('summaryLoading');
    const chartLoading = document.getElementById('chartLoading');
    const gmvProductGrid = document.getElementById('gmvProductGrid');
    const endorseProductGrid = document.getElementById('endorseProductGrid');
    const adsProductGrid = document.getElementById('adsProductGrid');
    const affiliateProductGrid = document.getElementById('affiliateProductGrid');
    const bestFypTableBody = document.getElementById('bestFypTableBody');
    const mainRangeText = document.getElementById('mainRangeText');
    const compareRangeText = document.getElementById('compareRangeText');
    const compareStateLabel = document.getElementById('compareStateLabel');
    const fallbackProductImg = '<?= base_url('assets/img/icon/icon-no.png') ?>';

    const formatShort = (value) => {
        const absValue = Math.abs(value || 0);
        if (absValue >= 1_000_000_000) {
            const val = (value / 1_000_000_000);
            return `${val.toFixed(val % 1 === 0 ? 0 : 1).replace('.', ',')} M`;
        }
        if (absValue >= 1_000_000) {
            const val = (value / 1_000_000);
            return `${val.toFixed(val % 1 === 0 ? 0 : 1).replace('.', ',')} JT`;
        }
        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(value || 0));
    };

    const formatMetricValue = (value, format) => {
        if (format === 'currency') {
            return `Rp ${formatShort(value)}`;
        }
        if (format === 'percent') {
            return `${(value || 0).toFixed(2)}%`;
        }
        return formatShort(value);
    };

    const calcChange = (compareValue, mainValue) => {
        if (!compareValue) return null;
        return ((mainValue - compareValue) / compareValue) * 100;
    };

    const channelConfig = [
        { key: 'tiktok', label: 'TikTok', color: '#0f8b8d' },
        { key: 'shopee', label: 'Shopee', color: '#3b82f6' },
        { key: 'lazada', label: 'Lazada', color: '#a855f7' },
        { key: 'manual', label: 'Manual', color: '#f59e0b' }
    ];

    const donutCharts = {};
    let productDistData = null;
    let bestFypData = null;

    const showLoading = (isLoading) => {
        summaryLoading.classList.toggle('is-visible', isLoading);
        chartLoading.classList.toggle('is-visible', isLoading);
    };

    const updateCards = (totalsMain, totalsCompare) => {
        cards.forEach(card => {
            const metric = card.dataset.metric;
            const meta = metricConfig[metric];
            if (!meta) {
                return;
            }
            const mainValue = totalsMain[metric] ?? 0;
            const compareValue = totalsCompare[metric] ?? 0;
            const change = calcChange(compareValue, mainValue);
            const trendUp = change !== null && change >= 0;

            card.querySelector('[data-role="metric-value"]').textContent = formatMetricValue(mainValue, meta.format);
            card.querySelector('[data-role="metric-compare"]').textContent = `Periode 1: ${formatMetricValue(compareValue, meta.format)}`;

            const changeNode = card.querySelector('[data-role="metric-change"]');
            changeNode.textContent = `${change === null ? 'N/A' : Math.abs(change).toFixed(2).replace('.', ',')}% vs Periode 1`;

            const changeWrapper = changeNode.closest('.metric-change');
            changeWrapper.classList.toggle('trend-up', change !== null && trendUp);
            changeWrapper.classList.toggle('trend-down', change !== null && !trendUp);
            changeWrapper.querySelector('i').className = `bi ${change === null ? 'bi-dash' : (trendUp ? 'bi-arrow-up-right' : 'bi-arrow-down-right')}`;
        });
    };

    const escapeHtml = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const normalizeProductItems = (payload) => {
        if (Array.isArray(payload?.items)) {
            return payload.items;
        }
        return [];
    };

    const renderProductCards = (payload) => {
        if (!gmvProductGrid) {
            return;
        }
        const items = normalizeProductItems(payload);
        if (!items.length) {
            gmvProductGrid.innerHTML = '<div class="product-empty">Belum ada data GMV produk di periode ini.</div>';
            return;
        }

        gmvProductGrid.innerHTML = items.map(item => {
            const productId = Number(item.id || 0) > 0 ? item.id : '';
            return `
                <div class="product-card" data-product-id="${escapeHtml(productId)}" data-product-name="${escapeHtml(item.name)}">
                    <img class="product-card-image" src="${escapeHtml(item.img)}" alt="${escapeHtml(item.name)}">
                    <div class="product-card-content">
                        <div class="product-card-title">${escapeHtml(item.name)}</div>
                        <div class="product-card-total">Rp ${formatShort(item.gmv_total || 0)}</div>
                    </div>
                </div>
            `;
        }).join('');
    };

    const renderEndorseProductCards = (payload) => {
        if (!endorseProductGrid) {
            return;
        }
        const items = Array.isArray(payload?.endorse_items)
            ? payload.endorse_items
                .slice()
                .filter(item => (item.endorse_total || 0) > 0)
                .sort((a, b) => (b.endorse_total || 0) - (a.endorse_total || 0))
            : [];

        if (!items.length) {
            window.overviewEndorseGroupedData = [];
            endorseProductGrid.innerHTML = '<div class="product-empty">Belum ada data spend endorse produk di periode ini.</div>';
            return;
        }

        window.overviewEndorseGroupedData = items;
        endorseProductGrid.innerHTML = items.map(item => `
            <div class="product-mini-item is-clickable" data-detail-type="endorse" data-endorse-key="${escapeHtml(item.key)}">
                <img class="product-card-image" src="${escapeHtml(item.img || fallbackProductImg)}" alt="${escapeHtml(item.name)}">
                <div class="meta">
                    <div class="name">${escapeHtml(item.name)}</div>
                    <div class="value">Rp ${formatShort(item.endorse_total || 0)}</div>
                </div>
            </div>
        `).join('');
    };

    const renderAdsProductCards = (payload) => {
        if (!adsProductGrid) {
            return;
        }
        const items = Array.isArray(payload?.ads_items)
            ? payload.ads_items
                .slice()
                .filter(item => (item.ads_total || 0) > 0)
                .sort((a, b) => {
                    const an = String(a.name || '').toLowerCase();
                    const bn = String(b.name || '').toLowerCase();
                    if (an === 'general' && bn !== 'general') return -1;
                    if (bn === 'general' && an !== 'general') return 1;
                    return (b.ads_total || 0) - (a.ads_total || 0);
                })
            : [];

        if (!items.length) {
            window.overviewAdsGroupedData = [];
            adsProductGrid.innerHTML = '<div class="product-empty">Belum ada data spend ads di periode ini.</div>';
            return;
        }

        window.overviewAdsGroupedData = items;
        const rows = items.map(item => `
            <div class="product-mini-item is-clickable" data-detail-type="ads" data-ads-key="${escapeHtml(item.key || '')}">
                <img class="product-card-image" src="${escapeHtml(item.img || fallbackProductImg)}" alt="${escapeHtml(item.name)}">
                <div class="meta">
                    <div class="name">${escapeHtml(item.name)}</div>
                    <div class="value">Rp ${formatShort(item.ads_total || 0)}</div>
                </div>
            </div>
        `);

        adsProductGrid.innerHTML = rows.join('');
    };

    const renderAffiliateProductCards = (payload) => {
        if (!affiliateProductGrid) {
            return;
        }
        const items = Array.isArray(payload?.affiliate_items)
            ? payload.affiliate_items
                .slice()
                .filter(item => (item.affiliate_total || 0) > 0)
                .sort((a, b) => {
                    const an = String(a.name || '').toLowerCase();
                    const bn = String(b.name || '').toLowerCase();
                    if (an === 'general' && bn !== 'general') return -1;
                    if (bn === 'general' && an !== 'general') return 1;
                    return (b.affiliate_total || 0) - (a.affiliate_total || 0);
                })
            : [];

        if (!items.length) {
            window.overviewAffiliateGroupedData = [];
            affiliateProductGrid.innerHTML = '<div class="product-empty">Belum ada data spend affiliate di periode ini.</div>';
            return;
        }

        window.overviewAffiliateGroupedData = items;
        affiliateProductGrid.innerHTML = items.map(item => `
            <div class="product-mini-item is-clickable" data-detail-type="affiliate" data-affiliate-key="${escapeHtml(item.key || '')}">
                <img class="product-card-image" src="${escapeHtml(item.img || fallbackProductImg)}" alt="${escapeHtml(item.name)}">
                <div class="meta">
                    <div class="name">${escapeHtml(item.name)}</div>
                    <div class="value">Rp ${formatShort(item.affiliate_total || 0)}</div>
                </div>
            </div>
        `).join('');
    };

    const renderBestFypSection = (payload) => {
        if (!bestFypTableBody) {
            return;
        }

        const hasContentItems = (list) => Array.isArray(list) && list.some(item => item && item.cover);

        const items = normalizeProductItems(payload)
            .slice()
            .filter(item => {
                const best = item?.best_fyp_contents || {};
                return hasContentItems(best.internal) || hasContentItems(best.external);
            });

        const isPreSorted = Boolean(payload?.pre_sorted_best_fyp);
        if (!isPreSorted) {
            items.sort((a, b) => (Number(b.gmv_total || 0) - Number(a.gmv_total || 0)));
        }

        const limitedItems = items.slice(0, 10);

        if (!limitedItems.length) {
            bestFypTableBody.innerHTML = '<tr><td colspan="3" class="product-empty">Belum ada data produk untuk periode ini.</td></tr>';
            return;
        }

        const renderContentCell = (list) => {
            const rows = Array.isArray(list) ? list.filter(item => item && item.cover) : [];
            if (!rows.length) {
                return '<div class="best-content-empty">Tidak ada konten</div>';
            }
            return `
                <div class="best-content-list">
                    ${rows.map(item => {
                        const cover = escapeHtml(item.cover || fallbackProductImg);
                        const video = escapeHtml(item.video || '');
                        const link = String(item.link || '').trim();
                        const views = formatShort(Number(item.views || 0));
                        const likes = formatShort(Number(item.likes || 0));
                        const comment = formatShort(Number(item.comment || 0));
                        const shareSave = formatShort(Number(item.share_save || 0));
                        if (!link) {
                            return `
                                <div class="best-content-entry">
                                    <span class="best-content-thumb">
                                        <img src="${cover}" alt="Konten">
                                        <span class="best-content-play"><i class="bi bi-play-fill"></i></span>
                                    </span>
                                    <div class="best-content-stats">
                                        <span class="best-content-stat"><i class="bi bi-eye"></i>${views}</span>
                                        <span class="best-content-stat"><i class="bi bi-heart"></i>${likes}</span>
                                        <span class="best-content-stat"><i class="bi bi-chat-dots"></i>${comment}</span>
                                        <span class="best-content-stat"><i class="bi bi-bookmark"></i>${shareSave}</span>
                                    </div>
                                </div>
                            `;
                        }
                        return `
                            <div class="best-content-entry">
                                <a class="best-content-thumb tiktok-embed-trigger" href="${escapeHtml(link)}" target="_blank" rel="noopener noreferrer" data-tiktok-url="${escapeHtml(link)}">
                                    <img src="${cover}" alt="Konten">
                                    <span class="best-content-play"><i class="bi bi-play-fill"></i></span>
                                </a>
                                <div class="best-content-stats">
                                    <span class="best-content-stat"><i class="bi bi-eye"></i>${views}</span>
                                    <span class="best-content-stat"><i class="bi bi-heart"></i>${likes}</span>
                                    <span class="best-content-stat"><i class="bi bi-chat-dots"></i>${comment}</span>
                                    <span class="best-content-stat"><i class="bi bi-bookmark"></i>${shareSave}</span>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        };

        bestFypTableBody.innerHTML = limitedItems.map((item) => {
            const best = item.best_fyp_contents || {};
            const productImg = escapeHtml(item.img || fallbackProductImg);
            const productName = escapeHtml(item.name || 'Produk');
            return `
                <tr>
                    <td class="best-content-product-cell"><img class="best-content-product-thumb" src="${productImg}" alt="${productName}"></td>
                    <td>${renderContentCell(best.internal)}</td>
                    <td>${renderContentCell(best.external)}</td>
                </tr>
            `;
        }).join('');

        initOverviewBestFypTippy();
    };

    const buildTiktokVideoHtml = (playUrl) => {
        if (!playUrl) return '';
        return `
            <div style="width:320px; height:520px; overflow:hidden; border-radius:8px; background:#fff;">
                <video src="${playUrl}" style="width:100%; height:100%; object-fit:cover;" controls autoplay muted loop playsinline preload="metadata"></video>
            </div>
        `;
    };

    const extractTiktokPhotoId = (rawUrl) => {
        if (!rawUrl) return '';
        const url = String(rawUrl).trim();
        const match = url.match(/\/photo\/(\d+)/i);
        return match ? match[1] : '';
    };

    const fetchTiktokVideoWithRetry = (url, attempt, onSuccess, onFail) => {
        const maxAttempts = 5;
        $.ajax({
            url: '<?= base_url() ?>endorse/get_tiktok_video_play',
            type: 'POST',
            data: { url: url },
            dataType: 'json',
            success: function(res) {
                if (res && res.status && res.data && res.data.play) {
                    onSuccess(res.data.play);
                } else if (attempt < maxAttempts) {
                    setTimeout(function() {
                        fetchTiktokVideoWithRetry(url, attempt + 1, onSuccess, onFail);
                    }, 400);
                } else {
                    onFail();
                }
            },
            error: function() {
                if (attempt < maxAttempts) {
                    setTimeout(function() {
                        fetchTiktokVideoWithRetry(url, attempt + 1, onSuccess, onFail);
                    }, 400);
                } else {
                    onFail();
                }
            }
        });
    };

    const buildTiktokPhotoCarousel = (images, uid) => {
        if (!images || !images.length) return '';
        const first = images[0];
        const dots = images.map((_, i) => `<span class="tt-dot ${i === 0 ? 'active' : ''}" data-idx="${i}"></span>`).join('');
        return `
            <div class="tt-carousel" data-uid="${uid}" data-total="${images.length}" data-index="0" style="width:320px;">
                <div class="tt-carousel-frame">
                    <img src="${first}" alt="TikTok Photo" />
                </div>
                <div class="tt-carousel-controls">
                    <button type="button" class="tt-btn" data-dir="-1">‹</button>
                    <div class="tt-dots">${dots}</div>
                    <button type="button" class="tt-btn" data-dir="1">›</button>
                </div>
                <div class="tt-counter">1 / ${images.length}</div>
                <div class="tt-images" style="display:none;">${images.map(u => `<span data-src="${u}"></span>`).join('')}</div>
            </div>
        `;
    };

    const initTiktokPhotoCarousel = (container, autoSlideMs = 2500) => {
        const root = container.querySelector('.tt-carousel');
        if (!root) return;
        const images = Array.from(root.querySelectorAll('.tt-images span')).map(s => s.getAttribute('data-src'));
        const frameImg = root.querySelector('.tt-carousel-frame img');
        const dots = Array.from(root.querySelectorAll('.tt-dot'));
        const counter = root.querySelector('.tt-counter');
        const total = images.length;
        let timerId = null;

        function setIndex(nextIndex) {
            let idx = nextIndex;
            if (idx < 0) idx = total - 1;
            if (idx >= total) idx = 0;
            root.setAttribute('data-index', String(idx));
            frameImg.src = images[idx];
            dots.forEach(d => d.classList.toggle('active', Number(d.dataset.idx) === idx));
            counter.textContent = `${idx + 1} / ${total}`;
        }

        root.querySelectorAll('.tt-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const dir = Number(this.getAttribute('data-dir')) || 0;
                const current = Number(root.getAttribute('data-index')) || 0;
                setIndex(current + dir);
            });
        });

        dots.forEach(dot => {
            dot.addEventListener('click', function() {
                setIndex(Number(this.getAttribute('data-idx')) || 0);
            });
        });

        if (total > 1 && autoSlideMs > 0) {
            timerId = setInterval(() => {
                const current = Number(root.getAttribute('data-index')) || 0;
                setIndex(current + 1);
            }, autoSlideMs);
            root.dataset.timerId = String(timerId);
        }
    };


    const initOverviewBestFypTippy = () => {
        if (typeof tippy === 'undefined') {
            return;
        }
        const triggers = document.querySelectorAll('.tiktok-embed-trigger');
        if (!triggers.length) {
            return;
        }
        triggers.forEach(function(el) {
            if (el._tippy) el._tippy.destroy();
        });

        tippy(triggers, {
            content: '<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat video...</div></div>',
            allowHTML: true,
            interactive: true,
            placement: 'right',
            theme: 'light',
            appendTo: function() { return document.body; },
            popperOptions: {
                strategy: 'fixed',
                modifiers: [
                    { name: 'preventOverflow', options: { boundary: 'viewport' } },
                    { name: 'flip', options: { boundary: 'viewport' } }
                ]
            },
            maxWidth: 360,
            onShow(instance) {
                const url = instance.reference.getAttribute('data-tiktok-url');
                if (!url) {
                    instance.setContent('<div class="p-2 text-muted">Link upload tidak tersedia.</div>');
                    return;
                }

                const photoId = extractTiktokPhotoId(url);
                if (photoId) {
                    instance.setContent('<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat foto...</div></div>');
                    $.ajax({
                        url: '<?= base_url() ?>endorse/get_tiktok_photo_images',
                        type: 'POST',
                        data: { content_id: photoId, url: url },
                        dataType: 'json',
                        success: function(res) {
                            if (res && res.status && Array.isArray(res.data) && res.data.length) {
                                const uid = 'ttc-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
                                instance.setContent(buildTiktokPhotoCarousel(res.data, uid));
                                setTimeout(function() {
                                    initTiktokPhotoCarousel(instance.popper, 2500);
                                }, 0);
                            } else {
                                instance.setContent('<div class="p-2 text-muted">Foto TikTok tidak ditemukan dari link upload.</div>');
                            }
                        },
                        error: function() {
                            instance.setContent('<div class="p-2 text-danger">Gagal memuat foto TikTok.</div>');
                        }
                    });
                    return;
                }

                instance.setContent('<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat video...</div></div>');
                fetchTiktokVideoWithRetry(url, 1, function(playUrl) {
                    instance.setContent(buildTiktokVideoHtml(playUrl));
                    setTimeout(function() {
                        const vid = instance.popper.querySelector('video');
                        if (vid) vid.play().catch(function(){});
                    }, 0);
                }, function() {
                    instance.setContent('<div class="p-2 text-muted">Video TikTok tidak ditemukan dari link upload.</div>');
                });
            },
            onHidden(instance) {
                const root = instance.popper.querySelector('.tt-carousel');
                if (root && root.dataset.timerId) {
                    clearInterval(Number(root.dataset.timerId));
                    delete root.dataset.timerId;
                }
            }
        });
    };

    const updateSpendingCards = (payload) => {
        const summary = payload?.summary || payload?._summary || {};
        $('#gmvTotalValue').text(`Rp ${formatShort(summary.gmv_total_all || 0)}`);
        $('#endorseTotalValue').text(`Rp ${formatShort(summary.endorse_total_all || 0)}`);
        renderEndorseProductCards(payload);
        $('#adsTotalValue').text(`Rp ${formatShort(summary.ads_total_all || 0)}`);
        renderAdsProductCards(payload);
        $('#affiliateTotalValue').text(`Rp ${formatShort(summary.affiliate_total_all || 0)}`);
        renderAffiliateProductCards(payload);
    };

    const fetchProductDistribution = () => {
        $('#productLoading').addClass('is-visible');
        $.ajax({
            url: '<?= base_url() ?>ajax/get_overview_product_distribution',
            dataType: 'json',
            data: {
                start_date: state.start_date,
                end_date: state.end_date,
                product_ids: (state.product_ids || []).join(',')
            },
            success: function(res) {
                productDistData = res.products || {};
                window.overviewProductDistData = productDistData;
                renderProductCards(productDistData);
                updateSpendingCards(productDistData);
            },
            complete: function() {
                $('#productLoading').removeClass('is-visible');
            }
        });
    };

    const fetchBestFypProducts = () => {
        if (!bestFypTableBody) {
            return;
        }

        bestFypTableBody.innerHTML = '<tr><td colspan="3" class="product-empty">Memuat data top konten FYP...</td></tr>';
        $.ajax({
            url: '<?= base_url() ?>ajax/get_overview_best_fyp_products',
            dataType: 'json',
            data: {
                start_date: state.start_date,
                end_date: state.end_date,
                product_ids: (state.product_ids || []).join(',')
            },
            success: function(res) {
                bestFypData = res.products || {};
                renderBestFypSection(bestFypData);
            },
            error: function() {
                bestFypTableBody.innerHTML = '<tr><td colspan="3" class="product-empty">Gagal memuat data top konten FYP.</td></tr>';
            }
        });
    };


    const alignSeries = (series, length) => {
        const output = series.slice(0, length);
        while (output.length < length) {
            output.push(null);
        }
        return output;
    };

    const getSeries = (data, key) => data.map(item => item[key] ?? 0);

    const ctx = document.getElementById('generalOverviewChart').getContext('2d');
    let overviewChart;

    const hexToRgba = (hex, alpha) => {
        const sanitized = hex.replace('#', '');
        const bigint = parseInt(sanitized, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    };

    const buildGradient = (color) => {
        const gradient = ctx.createLinearGradient(0, 0, 0, ctx.canvas.clientHeight);
        gradient.addColorStop(0, hexToRgba(color, 0.25));
        gradient.addColorStop(0.75, hexToRgba(color, 0));
        return gradient;
    };

    const buildDataset = (metricKey, data, isCompare, axisId) => {
        const config = metricConfig[metricKey];
        if (!config) {
            return null;
        }
        return {
            type: 'line',
            label: `${config.label}${isCompare ? ' (Banding)' : ''}`,
            data,
            borderColor: config.color,
            backgroundColor: buildGradient(config.color),
            fill: 'start',
            borderWidth: 2,
            borderDash: isCompare ? [6, 6] : [],
            cubicInterpolationMode: 'monotone',
            tension: 0.35,
            pointRadius: 0,
            pointHoverRadius: 3,
            pointHoverBorderWidth: 2,
            yAxisID: axisId
        };
    };

    const updateChart = (dailyMain, dailyCompare) => {
        const labels = dailyMain.map(item => moment(item.date, 'YYYY-MM-DD').format('D MMM'));
        const datasets = [];
        const activeList = Array.from(activeMetrics);

        const metricMaxes = activeList.map(metricKey => ({
            key: metricKey,
            max: Math.max(...getSeries(dailyMain, metricKey))
        })).sort((a, b) => b.max - a.max);

        let dualAxis = false;
        if (metricMaxes.length >= 2) {
            const maxA = metricMaxes[0].max;
            const maxB = metricMaxes[1].max;
            const ratio = Math.max(maxA, maxB) / Math.max(1, Math.min(maxA, maxB));
            const gap = Math.abs(maxA - maxB);
            dualAxis = ratio >= 3 || gap >= 30;
        }

        const primaryKey = metricMaxes[0]?.key;
        const secondaryKey = metricMaxes[1]?.key;

        const axisMeta = {
            y: {
                format: primaryKey ? metricConfig[primaryKey].format : 'number',
                label: primaryKey ? metricConfig[primaryKey].label : '',
                color: primaryKey ? metricConfig[primaryKey].color : '#94a3b8'
            },
            y1: {
                format: secondaryKey ? metricConfig[secondaryKey].format : 'number',
                label: secondaryKey ? metricConfig[secondaryKey].label : '',
                color: secondaryKey ? metricConfig[secondaryKey].color : '#94a3b8'
            }
        };

        activeList.forEach((metricKey) => {
            const axisId = dualAxis && metricKey !== primaryKey ? 'y1' : 'y';
            const mainSeries = alignSeries(getSeries(dailyMain, metricKey), labels.length);
            const mainDataset = buildDataset(metricKey, mainSeries, false, axisId);
            if (mainDataset) {
                datasets.push(mainDataset);
            }

            if (state.compare_mode === 'on') {
                const compareSeries = alignSeries(getSeries(dailyCompare, metricKey), labels.length);
                const compareDataset = buildDataset(metricKey, compareSeries, true, axisId);
                if (compareDataset) {
                    datasets.push(compareDataset);
                }
            }
        });

        if (overviewChart) {
            overviewChart.destroy();
        }

        overviewChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 3.1,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: false,
                        labels: { font: { size: 10 } }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#ffffff',
                        titleColor: '#1f2937',
                        bodyColor: '#1f2937',
                        borderColor: 'rgba(148, 163, 184, 0.4)',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const metricKey = Object.keys(metricConfig).find(key => context.dataset.label.startsWith(metricConfig[key].label));
                                const format = metricKey ? metricConfig[metricKey].format : 'number';
                                return `${context.dataset.label}: ${formatMetricValue(context.raw, format)}`;
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
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 8,
                            font: { size: 11 }
                        },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            maxTicksLimit: 8,
                            count: 8,
                            font: { size: 10 },
                            callback: function(value) {
                                if (axisMeta.y.format === 'percent') {
                                    return `${value.toFixed(0)}%`;
                                }
                                return formatShort(value);
                            }
                        },
                        title: {
                            display: !!axisMeta.y.label,
                            text: axisMeta.y.format === 'currency' ? `${axisMeta.y.label} (Rp)` : axisMeta.y.label,
                            color: axisMeta.y.color,
                            font: { weight: '600', size: 11 }
                        },
                        grid: {
                            color: 'rgba(15, 23, 42, 0.08)',
                            drawBorder: false
                        }
                    },
                    y1: {
                        display: dualAxis,
                        position: 'right',
                        beginAtZero: true,
                        ticks: {
                            maxTicksLimit: 8,
                            count: 8,
                            font: { size: 10 },
                            callback: function(value) {
                                if (axisMeta.y1.format === 'percent') {
                                    return `${value.toFixed(0)}%`;
                                }
                                return formatShort(value);
                            }
                        },
                        title: {
                            display: dualAxis && !!axisMeta.y1.label,
                            text: axisMeta.y1.format === 'currency' ? `${axisMeta.y1.label} (Rp)` : axisMeta.y1.label,
                            color: axisMeta.y1.color,
                            font: { weight: '600', size: 11 }
                        },
                        grid: {
                            drawOnChartArea: false,
                            drawBorder: false
                        }
                    }
                }
            }
        });
    };

    const fetchOverviewData = () => {
        showLoading(true);
        fetchProductDistribution();
        fetchBestFypProducts();
        const payload = {
            start_date: state.start_date,
            end_date: state.end_date,
            compare_mode: state.compare_mode,
            compare_start_date: state.compare_start_date,
            compare_end_date: state.compare_end_date,
            product_ids: (state.product_ids || []).join(','),
            metrics: Array.from(activeMetrics)
        };

        $.ajax({
            url: '<?= base_url() ?>ajax/get_overview_general',
            dataType: 'json',
            data: payload,
            success: function(res) {
                state.compare_mode = res.compare_mode;
                state.compare_start_date = res.compare_range.start;
                state.compare_end_date = res.compare_range.end;

                mainRangeText.textContent = `${moment(res.main_range.start).format('DD MMM YYYY')} - ${moment(res.main_range.end).format('DD MMM YYYY')}`;
                compareRangeText.textContent = `${moment(res.compare_range.start).format('DD MMM YYYY')} - ${moment(res.compare_range.end).format('DD MMM YYYY')}`;

                updateCards(res.period_main.totals, res.period_compare.totals);
                updateChart(res.period_main.daily, res.period_compare.daily);

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
            error: function() {
                console.error('Gagal memuat data overview.');
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

    cards.forEach(card => {
        card.addEventListener('click', () => {
            const metric = card.dataset.metric;
            if (activeMetrics.has(metric)) {
                if (activeMetrics.size === 1) {
                    return;
                }
                activeMetrics.delete(metric);
                card.classList.remove('is-active');
            } else {
                activeMetrics.add(metric);
                card.classList.add('is-active');
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

    $('#product_ids').on('change', function() {
        state.product_ids = ($(this).val() || []).map(function(v) {
            return Number(v);
        }).filter(function(v) {
            return Number.isInteger(v) && v > 0;
        });
        fetchOverviewData();
    });

    window.overviewState = state;
    window.overviewFormatShort = formatShort;
    window.overviewChannelConfig = channelConfig;
    window.base_url = window.base_url || "<?= base_url() ?>";

    fetchOverviewData();
</script>

<link rel="stylesheet" href="<?= base_url() ?>assets/css/distribusi_channel_modal.css?v=<?= @filemtime(FCPATH . 'assets/css/distribusi_channel_modal.css') ?>">
<script src="<?= base_url() ?>assets/js/distribusi_channel_modal.js?v=<?= @filemtime(FCPATH . 'assets/js/distribusi_channel_modal.js') ?>"></script>
