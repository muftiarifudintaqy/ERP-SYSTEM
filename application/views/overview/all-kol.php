<style>
    .owl-theme .owl-dots .owl-dot.active span,
    .owl-theme .owl-dots .owl-dot:hover span {
        background: #1255cc;
    }

    .owl-theme .owl-dots .owl-dot span {
        background: #1155CC1A;
        margin: 3px;
    }

    .owl-theme .owl-nav.disabled+.owl-dots {
        margin-top: 0px;
    }
</style>
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

    .endorse-filter-section {
        background: #ffffff;
        border: 1px solid #e5edf5;
        border-radius: 12px;
        padding: 14px;
        width: 100%;
        margin: 0;
        box-sizing: border-box;
    }

    .endorse-filter-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 6px;
        display: block;
    }

    .endorse-compare-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .endorse-compare-toggle {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.76rem;
        font-weight: 700;
        color: #475569;
    }

    .endorse-compare-switch {
        position: relative;
        width: 44px;
        height: 24px;
        border-radius: 999px;
        background: #dbe7f1;
        cursor: pointer;
    }

    .endorse-compare-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .endorse-compare-switch .knob {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.18);
        transition: transform 0.2s ease;
    }

    .endorse-compare-switch input:checked+.knob {
        transform: translateX(20px);
    }

    .endorse-compare-switch input:checked~.bg {
        background: #0f8b8d;
    }

    .endorse-compare-switch .bg {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        z-index: -1;
        background: #dbe7f1;
    }

    .chip-filter-wrap {
        margin-bottom: 10px;
    }

    .chip-filter-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .chip-filter-list .btn {
        margin: 0 !important;
        border-radius: 10px !important;
        padding: 5px 11px !important;
        font-size: 0.8rem;
        font-weight: 600;
        line-height: 1.1;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 35px !important;
        text-decoration: none !important;
        transition: all 0.2s ease;
    }

    .chip-filter-list .btn-default {
        background: #ffffff !important;
        color: #334155 !important;
        border: 1px solid #cbd5e1 !important;
    }

    .chip-filter-list .btn-default:hover {
        border-color: #0f8b8d !important;
        color: #0f6f71 !important;
    }

    .chip-filter-list .btn-default-selected {
        background: rgba(15, 139, 141, 0.12) !important;
        color: #0f6f71 !important;
        border: 1px solid #0f8b8d !important;
    }

    .chip-filter-list .dot {
        display: none !important;
    }

    .chip-filter-list .dot-active {
        width: 14px;
        height: 14px;
        border-radius: 8px;
        background: #0f8b8d;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .chip-filter-list .dot-active::before {
        content: "";
        width: 6px;
        height: 3px;
        border-left: 1.8px solid #fff;
        border-bottom: 1.8px solid #fff;
        transform: rotate(-45deg);
        margin-top: -1px;
    }

    .skeleton-shimmer {
        position: relative;
        overflow: hidden;
        background: #e9eef5;
    }

    .skeleton-shimmer::after {
        content: "";
        position: absolute;
        inset: 0;
        transform: translateX(-100%);
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.75), transparent);
        animation: skeleton-slide 1.1s infinite;
    }

    @keyframes skeleton-slide {
        100% {
            transform: translateX(100%);
        }
    }

    .skeleton-text {
        height: 16px;
        border-radius: 6px;
    }

    .skeleton-title {
        height: 22px;
        border-radius: 8px;
        width: 140px;
    }

    .skeleton-block {
        height: 220px;
        border-radius: 10px;
    }

    .kol-matrix-section {
        background: #ffffff;
        border: 1px solid #e5edf5;
        border-radius: 14px;
        padding: 14px;
        margin-top: 8px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
    }

    .kol-matrix-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1b2a3d;
        margin-bottom: 10px;
    }

    .kol-matrix-scroll {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        padding-bottom: 8px;
        scroll-behavior: smooth;
    }

    .kol-matrix-scroll::-webkit-scrollbar {
        height: 8px;
    }

    .kol-matrix-scroll::-webkit-scrollbar-thumb {
        background: #cfe7e7;
        border-radius: 999px;
    }

    .kol-matrix-card {
        min-width: 220px;
        max-width: 220px;
        border: 2px solid #e8eef4;
        border-radius: 14px;
        background: #fff;
        padding: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        flex: 0 0 auto;
    }

    .kol-matrix-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(12, 30, 58, 0.1);
    }

    .kol-matrix-card.is-active {
        border-color: #0f8b8d;
        box-shadow: 0 10px 24px rgba(15, 139, 141, 0.18);
    }

    .kol-matrix-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .kol-matrix-name {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1f2937;
    }

    .kol-matrix-check {
        width: 18px;
        height: 18px;
        accent-color: #0f8b8d;
        pointer-events: none;
    }

    .kol-matrix-value {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 5px;
        white-space: nowrap;
    }

    .kol-matrix-sub {
        font-size: 0.78rem;
        color: #6b7c93;
        margin-bottom: 5px;
    }

    .kol-matrix-change {
        font-size: 0.82rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .kol-matrix-change.up {
        color: #1d9b5c;
    }

    .kol-matrix-change.down {
        color: #d64545;
    }

    .kol-matrix-chart-wrap {
        margin-top: 12px;
        border: 1px solid #e8eef4;
        border-radius: 14px;
        background: #fff;
        padding: 12px;
    }

    .kol-matrix-chart-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1b2a3d;
        margin-bottom: 2px;
    }

    .kol-matrix-chart-subtitle {
        font-size: 0.78rem;
        color: #7b8ca4;
        margin-bottom: 8px;
    }

    .kol-matrix-chart-canvas {
        position: relative;
        height: 280px;
    }

    .kol-matrix-table-wrap {
        margin-top: 12px;
        border: 1px solid #e8eef4;
        border-radius: 14px;
        background: #fff;
        padding: 12px;
    }

    .kol-mini-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 11px;
        color: #334155;
    }

    #kolMatrixTableSection .kol-mini-table th,
    #kolMatrixTableSection .kol-mini-table td,
    #kolMatrixDetailModalBody .kol-mini-table th,
    #kolMatrixDetailModalBody .kol-mini-table td {
        border: 1px solid #e5eaf1 !important;
        padding: 4px 6px !important;
        white-space: nowrap !important;
        vertical-align: middle !important;
        line-height: 1.3 !important;
        background: #fff !important;
        font-size: 11px !important;
        color: #334155 !important;
        text-align: center !important;
    }

    #kolMatrixTableSection .kol-mini-table thead th,
    #kolMatrixDetailModalBody .kol-mini-table thead th {
        background: #f7fafc !important;
        font-weight: 700 !important;
        color: #334155 !important;
        position: sticky !important;
        top: 0 !important;
        z-index: 1 !important;
        text-align: center !important;
        border-top: 1px solid #e5eaf1 !important;
    }

    #kolMatrixTableSection .kol-mini-table td.metric-active,
    #kolMatrixDetailModalBody .kol-mini-table td.metric-active {
        background: #eef7f7 !important;
        font-weight: 700 !important;
        color: #0f8b8d !important;
    }

    #kolMatrixTableSection .kol-mini-table th:first-child,
    #kolMatrixTableSection .kol-mini-table td:first-child,
    #kolMatrixDetailModalBody .kol-mini-table th:first-child,
    #kolMatrixDetailModalBody .kol-mini-table td:first-child {
        position: sticky !important;
        left: 0 !important;
        z-index: 2 !important;
        background: #f8fbff !important;
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }

    #kolMatrixTableSection .kol-mini-table th:last-child,
    #kolMatrixTableSection .kol-mini-table td:last-child,
    #kolMatrixDetailModalBody .kol-mini-table th:last-child,
    #kolMatrixDetailModalBody .kol-mini-table td:last-child {
        position: sticky !important;
        left: 0 !important;
        z-index: 2 !important;
        background: #f8fbff !important;
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }

    #kolMatrixTableSection .kol-mini-table thead th:first-child,
    #kolMatrixDetailModalBody .kol-mini-table thead th:first-child {
        z-index: 3 !important;
    }

    #kolMatrixTableSection .kol-mini-table.table-compact th,
    #kolMatrixTableSection .kol-mini-table.table-compact td,
    #kolMatrixDetailModalBody .kol-mini-table.table-compact th,
    #kolMatrixDetailModalBody .kol-mini-table.table-compact td {
        padding: 3px 5px !important;
        font-size: 10px !important;
    }

    .btn-link-matrix {
        border: none;
        padding: 0;
        background: transparent;
        color: inherit;
        font-weight: inherit;
        text-decoration: underline;
        text-decoration-style: dashed;
        text-underline-offset: 2px;
        cursor: pointer;
    }

    .btn-link-matrix:hover {
        color: #0f8b8d;
    }

    .kol-matrix-detail-meta {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 8px;
    }

    .kol-matrix-toggle-text {
        border: none;
        background: transparent;
        padding: 0;
        margin: 0;
        color: #1a73e8;
        text-decoration: underline;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }

    .kol-matrix-toggle-text:hover {
        color: #0f5ecf;
    }

    .subdata-endorse-section .subdata-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(220px, 1fr));
        gap: 12px;
        margin-bottom: 12px;
    }

    .subdata-summary-card {
        border: 2px solid #e8eef4;
        border-radius: 14px;
        background: #fff;
        padding: 12px;
        min-height: 118px;
        transition: all 0.2s ease;
    }

    .subdata-summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(12, 30, 58, 0.08);
    }

    .subdata-summary-card .subdata-icon {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        background: rgba(15, 139, 141, 0.12);
        color: #0f8b8d;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
    }

    .subdata-summary-card .kol-matrix-value {
        margin-top: 8px;
        margin-bottom: 6px;
    }

    .subdata-summary-card .kol-matrix-sub {
        margin-bottom: 0;
    }

    .engagement-breakdown {
        margin-top: 6px;
        font-size: 12px;
        color: #64748b;
        line-height: 1.45;
    }

    .subdata-chart-layout {
        display: block;
    }

    .top-content-sidebar {
        border: 1px solid #e8eef4;
        border-radius: 12px;
        background: #fff;
        padding: 12px;
        min-height: 260px;
    }

    .top-content-head {
        font-size: 0.92rem;
        font-weight: 700;
        color: #1b2a3d;
        margin-bottom: 2px;
    }

    .top-content-sub {
        font-size: 0.76rem;
        color: #7b8ca4;
        margin-bottom: 10px;
    }

    .top-content-list {
        display: grid;
        gap: 10px;
    }

    .top-content-item {
        border: 1px solid #e6edf5;
        border-radius: 10px;
        padding: 10px;
        background: #fcfdff;
    }

    .top-content-rank {
        width: 24px;
        height: 24px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        color: #0f6f71;
        background: rgba(15, 139, 141, 0.15);
    }

    .top-content-title {
        margin-top: 8px;
        margin-bottom: 4px;
        font-size: 0.84rem;
        font-weight: 700;
        color: #1f2937;
        line-height: 1.35;
    }

    .top-content-meta {
        font-size: 0.75rem;
        color: #64748b;
        line-height: 1.5;
    }

    .top-content-media-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 5px;
        font-size: 0.74rem;
        color: #0f8b8d;
        text-decoration: none;
    }

    .top-content-media-link:hover {
        color: #0f6f71;
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

    .tippy-box[data-theme~='light'] {
        background-color: #ffffff !important;
        color: #333333 !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.1) !important;
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

    @media (max-width: 991px) {
        .subdata-endorse-section .subdata-summary-grid {
            grid-template-columns: 1fr;
        }

        .subdata-chart-layout {
            display: block;
        }
    }

    .endorse-data-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        color: #64748b;
        font-weight: 600;
        background: #f8fafc;
        margin-right: 6px;
    }

    .endorse-data-tabs .nav-link:hover {
        color: #0f8b8d;
        background: #eef7f7;
    }

    .endorse-data-tabs .nav-link.active {
        color: #0f8b8d;
        background: #ffffff;
        border-bottom-color: #0f8b8d;
    }
</style>
<?php
$chart_title = "";
$site = $_GET['site'];
$customer = $_GET['customer'];
$selected_pic_filters = $_GET['pic'] ?? [];
if (!is_array($selected_pic_filters)) {
    $selected_pic_filters = explode(',', (string)$selected_pic_filters);
}
$selected_pic_filters = array_values(array_filter(array_map('trim', $selected_pic_filters), 'strlen'));

$selected_product_filters = $_GET['product'] ?? [];
if (!is_array($selected_product_filters)) {
    $selected_product_filters = explode(',', (string)$selected_product_filters);
}
$selected_product_filters = array_values(array_filter(array_map('trim', $selected_product_filters), 'strlen'));
$compare_mode = $_GET['compare_mode'] ?? 'off';
$compare_start_date = $_GET['compare_start_date'] ?? '';
$compare_end_date = $_GET['compare_end_date'] ?? '';

if ($_GET['start_date'] == "") {
    $start_date = DATE("Y-m-01");
    
} else {
    $start_date = $_GET['start_date'];
}
if ($_GET['until_date'] == "") {
    $until_date = DATE("Y-m-d");
} else {
    $until_date = $_GET['until_date'];
}

if (empty($compare_start_date) || empty($compare_end_date)) {
    $range_days_default = (new DateTime($start_date))->diff(new DateTime($until_date))->days + 1;
    $compare_start_date = date('Y-m-d', strtotime("$start_date -$range_days_default days"));
    $compare_end_date = date('Y-m-d', strtotime("$start_date -1 day"));
    $compare_mode = 'off';
}

if ($_GET['start_year'] == "") {
    $start_year = DATE("Y");
} else {
    $start_year = $_GET['start_year'];
}

if ($_GET['until_year'] == "") {
    $until_year = DATE("Y");
} else {
    $until_year = $_GET['until_year'];
}

if ($_GET['start_month'] == "") {
    $start_month = "1";
} else {
    $start_month = $_GET['start_month'];
}

if ($_GET['until_month'] == "") {
    $until_month = DATE("m");
} else {
    $until_month = $_GET['until_month'];
}

if ($_GET['start_week'] == "") {
    $start_week = "1";
} else {
    $start_week = $_GET['start_week'];
}

if ($_GET['until_week'] == "") {
    $until_week = DATE("W", strtotime(DATE('Y-m-d')));
} else {
    $until_week = $_GET['until_week'];
}



$type = $_GET['type'];

if ($_GET['type'] == "Yearly") {
    $chart_title = $start_year . ' - ' . $until_year;
} else if ($_GET['type'] == "Monthly") {
    $chart_title = 'Month ' . $start_month . ' - ' . $until_month . ' ' . $start_year;
} else if ($_GET['type'] == "Weekly") {
    $chart_title = 'Week ' . $start_week . ' - ' . $until_week . ' ' . $start_year;
} else {
    $type = "Daily";
    $chart_title = DATE('d M Y', strtotime($start_date)) . ' - ' . DATE('d M Y', strtotime($until_date));
}
?>

<div class="w-100">
    <div class="row align-items-center">
        <?php $this->load->view('overview/menu') ?>

        <div class="col-lg-12">

            <form id="overviewFilterForm" action="<?= $url ?>?t=kol" method="GET">
                <input type="hidden" name="t" value="kol">
                <div class="row">

                    <div class="col-md-12">
                        <div class="chip-filter-wrap">
                            <div class="chip-filter-list">
                        <?php
                        $arr = array();
                        $arr[] = "Semua Status Endorse";
                        $arr[] = "Acc";
                        $arr[] = "Posted Content";
                        $arr[] = "Problem";
                        $arr[] = "FYP";

                        foreach ($arr as $k => $val) {
                            $class = "btn-default";
                            $class_2 = "dot";

                            $value = $val;
                            if ($k == 0) {
                                $value = '';
                            }
                            $status = isset($_GET['endorse_status']) ? $_GET['endorse_status'] : '';

                            $statusArray = $status ? explode(',', $status) : [];

                            if (($key = array_search($value, $statusArray)) !== false) {
                                unset($statusArray[$key]);
                                $class = "btn-default-selected";
                                $class_2 = "dot-active";
                            } else {
                                $statusArray[] = $value;
                            }

                            $status = implode(',', $statusArray);

                            if ($k == 0) {
                                $status = '';
                            }

                            if ($k == 0 && $_GET['endorse_status'] == "") {
                                $class_2 = "dot-active";
                                $class = "btn-default-selected";
                            }

                        ?>
                            <a
                                href="<?= $url ?>&endorse_status=<?= $status ?>"
                                class="btn <?= $class ?> js-ajax-chip"
                                data-chip-param="endorse_status"
                                data-chip-value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
                                <span class="<?= $class_2 ?>"></span> <?= $val ?>
                            </a>
                        <?php }  ?>
                            </div>
                        </div>

                        <div class="chip-filter-wrap">
                            <div class="chip-filter-list">
                        <?php
                        $arr = array();
                        $arr[] = "Semua Status Pembayaran";
                        $arr[] = "DP";
                        $arr[] = "FP";

                        foreach ($arr as $k => $val) {
                            $class = "btn-default";
                            $class_2 = "dot";

                            $value = $val;
                            if ($k == 0) {
                                $value = '';
                            }
                            $status = isset($_GET['status_payment']) ? $_GET['status_payment'] : '';

                            $statusArray = $status ? explode(',', $status) : [];

                            if (($key = array_search($value, $statusArray)) !== false) {
                                unset($statusArray[$key]);
                                $class = "btn-default-selected";
                                $class_2 = "dot-active";
                            } else {
                                $statusArray[] = $value;
                            }

                            $status = implode(',', $statusArray);

                            if ($k == 0) {
                                $status = '';
                            }

                            if ($k == 0 && $_GET['status_payment'] == "") {
                                $class_2 = "dot-active";
                                $class = "btn-default-selected";
                            }
                        ?>
                            <a
                                href="<?= $url ?>&status_payment=<?= $status ?>"
                                class="btn <?= $class ?> js-ajax-chip"
                                data-chip-param="status_payment"
                                data-chip-value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
                                <span class="<?= $class_2 ?>"></span> <?= $val ?>
                            </a>
                        <?php } ?>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="endorse_status" value="<?= $_GET['endorse_status'] ?>">
                    <input type="hidden" name="category" value="<?= $_GET['category'] ?>">
                    <input type="hidden" name="status_data" value="<?= $_GET['status_data'] ?>">
                    <input type="hidden" name="status_payment" value="<?= $_GET['status_payment'] ?>">
                    <input type="hidden" name="status_konten" value="<?= $_GET['status_konten'] ?>">

                    <div class="col-md-12">
                        <div class="chip-filter-wrap">
                            <div class="chip-filter-list">
                        <?php
                        $arr = array();

                        $arr[] = "Semua Konten";
                        $arr[] = "External";
                        $arr[] = "Internal";
                        foreach ($arr as $k => $val) {
                            $class = "btn-default";
                            $class_2 = "dot";

                            $value = $val;
                            if ($k == 0) {
                                $value = '';
                            }
                            $value = str_replace('&', '', $value);

                            if ($_GET['status_konten'] == $value) {
                                $class = "btn-default-selected";
                                $class_2 = "dot-active";
                            }
                        ?>
                            <a
                                href="<?= $url_2 ?>&status_konten=<?= $value ?>"
                                class="btn <?= $class ?> js-ajax-chip"
                                data-chip-param="status_konten"
                                data-chip-value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
                                <span class="<?= $class_2 ?>"></span> <?= $val ?>
                            </a>
                        <?php }  ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="endorse-filter-section">
                            <div class="row g-2">
                            <div class="col-md-4">
                                <label class="endorse-filter-label mb-1" for="tanggal">Periode Tanggal</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                                    <div class="endorse-compare-toggle flex-shrink-0">
                                        <label class="endorse-compare-switch">
                                            <input type="checkbox" id="compareToggleKOL" <?= $compare_mode === 'on' ? 'checked' : '' ?>>
                                            <span class="knob"></span>
                                            <span class="bg"></span>
                                        </label>
                                        <span id="compareStateLabelKOL"><?= $compare_mode === 'on' ? 'ON' : 'OFF' ?></span>
                                    </div>
                                </div>
                                <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                                <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                                <input type="hidden" name="compare_mode" id="compare_mode" value="<?= $compare_mode ?>">
                                <input type="hidden" name="compare_start_date" id="compare_start_date" value="<?= $compare_start_date ?>">
                                <input type="hidden" name="compare_end_date" id="compare_end_date" value="<?= $compare_end_date ?>">
                                <div class="mt-2 <?= $compare_mode === 'on' ? '' : 'd-none' ?>" id="compareDateWrapKOL">
                                    <label class="endorse-filter-label mb-1" for="tanggal_compare">Periode Perbandingan</label>
                                    <input type="text" class="form-control" id="tanggal_compare" placeholder="Pilih rentang tanggal perbandingan...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="endorse-filter-label" for="filter_pic">PIC</label>
                                <select name="pic[]" id="filter_pic" class="form-control select2" multiple data-placeholder="Pilih PIC">
                                    <?php foreach (($endorse_filter_pics ?? []) as $row):
                                        $picVal = trim((string)($row['pic'] ?? ''));
                                        if ($picVal === '') continue;
                                        $isSelected = in_array($picVal, $selected_pic_filters, true);
                                    ?>
                                        <option value="<?= htmlspecialchars($picVal, ENT_QUOTES, 'UTF-8') ?>" <?= $isSelected ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($picVal, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="endorse-filter-label" for="filter_product">Product</label>
                                <select name="product[]" id="filter_product" class="form-control select2" multiple data-placeholder="Pilih Product">
                                    <?php foreach (($endorse_filter_products ?? []) as $row):
                                        $productVal = trim((string)($row['product_name'] ?? ''));
                                        if ($productVal === '') continue;
                                        $isSelected = in_array($productVal, $selected_product_filters, true);
                                    ?>
                                        <option value="<?= htmlspecialchars($productVal, ENT_QUOTES, 'UTF-8') ?>" <?= $isSelected ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($productVal, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            </div>
                            <script>
                            get_filter();

                            function get_filter() {
                                $.ajax({
                                    dataType: "json",
                                    url: '<?= base_url() ?>/ajax/get-filter',
                                    data: {
                                        start_date: "<?= $_GET['start_date'] ?? $start_date ?>",
                                        until_date: "<?= $_GET['until_date'] ?? $until_date ?>",
                                        page: "endorse"
                                    },
                                    success: function(response) {
                                        $("#tanggal").after(response.html); 
                                    },
                                    error: function(xhr, status, error) {
                                        console.error("Error loading filter:", error);
                                    }
                                });
                            }

                            function parseMultiParam(urlObj, key) {
                                const arrStyle = urlObj.searchParams.getAll(key + '[]');
                                if (arrStyle && arrStyle.length) return arrStyle.filter(Boolean);
                                const raw = urlObj.searchParams.get(key);
                                if (!raw) return [];
                                return raw.split(',').map(v => v.trim()).filter(Boolean);
                            }

                            function setMultiParam(urlObj, key, values) {
                                urlObj.searchParams.delete(key);
                                urlObj.searchParams.delete(key + '[]');
                                if (!values || !values.length) return;
                                urlObj.searchParams.set(key, values.join(','));
                            }

                            function syncChipActiveStateFromUrl(urlObj) {
                                const endorseStatus = (urlObj.searchParams.get('endorse_status') || '')
                                    .split(',')
                                    .map(v => v.trim())
                                    .filter(Boolean);
                                const paymentStatus = (urlObj.searchParams.get('status_payment') || '')
                                    .split(',')
                                    .map(v => v.trim())
                                    .filter(Boolean);
                                const kontenStatus = (urlObj.searchParams.get('status_konten') || '').trim();

                                $('.js-ajax-chip').each(function() {
                                    const $chip = $(this);
                                    const param = $chip.data('chip-param');
                                    const value = ($chip.data('chip-value') || '').toString();
                                    let active = false;

                                    if (param === 'endorse_status') {
                                        active = value === '' ? endorseStatus.length === 0 : endorseStatus.includes(value);
                                    } else if (param === 'status_payment') {
                                        active = value === '' ? paymentStatus.length === 0 : paymentStatus.includes(value);
                                    } else if (param === 'status_konten') {
                                        active = value === '' ? kontenStatus === '' : kontenStatus === value;
                                    }

                                    $chip.toggleClass('btn-default-selected', active);
                                    $chip.toggleClass('btn-default', !active);
                                    $chip.find('span').toggleClass('dot-active', active).toggleClass('dot', !active);
                                });
                            }

                            function toggleChipParam(nextUrl, param, value) {
                                const chipValue = (value || '').toString().trim();
                                const multiParam = ['endorse_status', 'status_payment'];
                                if (multiParam.includes(param)) {
                                    if (chipValue === '') {
                                        nextUrl.searchParams.delete(param);
                                        return;
                                    }

                                    const current = (nextUrl.searchParams.get(param) || '')
                                        .split(',')
                                        .map(v => v.trim())
                                        .filter(Boolean);
                                    const idx = current.indexOf(chipValue);
                                    if (idx >= 0) {
                                        current.splice(idx, 1);
                                    } else {
                                        current.push(chipValue);
                                    }
                                    if (current.length) {
                                        nextUrl.searchParams.set(param, current.join(','));
                                    } else {
                                        nextUrl.searchParams.delete(param);
                                    }
                                    return;
                                }

                                if (param === 'status_konten') {
                                    if (chipValue === '') {
                                        nextUrl.searchParams.delete(param);
                                    } else {
                                        nextUrl.searchParams.set(param, chipValue);
                                    }
                                }
                            }

                            function applyOverviewFilterAjax(sourceHref, chipPayload) {
                                const nextUrl = new URL(window.location.href);

                                if (chipPayload && chipPayload.param) {
                                    toggleChipParam(nextUrl, chipPayload.param, chipPayload.value);
                                } else if (sourceHref) {
                                    const sourceUrl = new URL(sourceHref, window.location.origin);
                                    ['endorse_status', 'status_payment', 'status_konten'].forEach(function(key) {
                                        if (sourceUrl.searchParams.has(key)) {
                                            const val = sourceUrl.searchParams.get(key) || '';
                                            if (val === '') {
                                                nextUrl.searchParams.delete(key);
                                            } else {
                                                nextUrl.searchParams.set(key, val);
                                            }
                                        }
                                    });
                                }

                                nextUrl.searchParams.set('start_date', $('#start_date').val() || '');
                                nextUrl.searchParams.set('until_date', $('#end_date').val() || '');
                                nextUrl.searchParams.set('compare_mode', $('#compare_mode').val() || 'off');
                                nextUrl.searchParams.set('compare_start_date', $('#compare_start_date').val() || '');
                                nextUrl.searchParams.set('compare_end_date', $('#compare_end_date').val() || '');
                                setMultiParam(nextUrl, 'pic', $('#filter_pic').val() || []);
                                setMultiParam(nextUrl, 'product', $('#filter_product').val() || []);

                                history.replaceState({}, '', nextUrl.toString());

                                const picVals = parseMultiParam(nextUrl, 'pic');
                                const productVals = parseMultiParam(nextUrl, 'product');
                                $('#filter_pic').val(picVals).trigger('change.select2');
                                $('#filter_product').val(productVals).trigger('change.select2');

                                $('#start_date').val(nextUrl.searchParams.get('start_date') || $('#start_date').val());
                                $('#end_date').val(nextUrl.searchParams.get('until_date') || $('#end_date').val());
                                $('#compare_mode').val(nextUrl.searchParams.get('compare_mode') || 'off');
                                $('#compare_start_date').val(nextUrl.searchParams.get('compare_start_date') || $('#compare_start_date').val());
                                $('#compare_end_date').val(nextUrl.searchParams.get('compare_end_date') || $('#compare_end_date').val());
                                const isCompareOn = ($('#compare_mode').val() === 'on');
                                $('#compareToggleKOL').prop('checked', isCompareOn);
                                $('#compareStateLabelKOL').text(isCompareOn ? 'ON' : 'OFF');
                                $('#compareDateWrapKOL').toggleClass('d-none', !isCompareOn);

                                const comparePicker = $('#tanggal_compare').data('daterangepicker');
                                if (comparePicker) {
                                    const cStart = moment($('#compare_start_date').val(), 'YYYY-MM-DD');
                                    const cEnd = moment($('#compare_end_date').val(), 'YYYY-MM-DD');
                                    if (cStart.isValid() && cEnd.isValid()) {
                                        comparePicker.setStartDate(cStart);
                                        comparePicker.setEndDate(cEnd);
                                        if (typeof comparePicker.updateInput === 'function') {
                                            comparePicker.updateInput();
                                        } else {
                                            $('#tanggal_compare').val(`${cStart.format('DD/MM/YYYY')} - ${cEnd.format('DD/MM/YYYY')}`);
                                        }
                                    }
                                }
                                $('input[name="endorse_status"]').val(nextUrl.searchParams.get('endorse_status') || '');
                                $('input[name="status_payment"]').val(nextUrl.searchParams.get('status_payment') || '');
                                $('input[name="status_konten"]').val(nextUrl.searchParams.get('status_konten') || '');

                                syncChipActiveStateFromUrl(nextUrl);
                                get_chart();
                                if (typeof get_kol_matrix === 'function') {
                                    get_kol_matrix();
                                }
                            }

                            $(document).ready(function() {
                                $('#filter_pic').select2({
                                    width: '100%',
                                    allowClear: true,
                                    placeholder: $('#filter_pic').data('placeholder') || 'Pilih PIC'
                                });
                                $('#filter_product').select2({
                                    width: '100%',
                                    allowClear: true,
                                    placeholder: $('#filter_product').data('placeholder') || 'Pilih Product'
                                });

                                $('#tanggal_compare').daterangepicker({
                                    showDropdowns: true,
                                    alwaysShowCalendars: true,
                                    startDate: moment($('#compare_start_date').val(), "YYYY-MM-DD"),
                                    endDate: moment($('#compare_end_date').val(), "YYYY-MM-DD"),
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
                                    $('#compare_start_date').val(start.format('YYYY-MM-DD'));
                                    $('#compare_end_date').val(end.format('YYYY-MM-DD'));
                                    if ($('#compare_mode').val() === 'on') {
                                        applyOverviewFilterAjax();
                                    }
                                });

                                const initialUrl = new URL(window.location.href);
                                $('#filter_pic').val(parseMultiParam(initialUrl, 'pic')).trigger('change.select2');
                                $('#filter_product').val(parseMultiParam(initialUrl, 'product')).trigger('change.select2');
                                if (initialUrl.searchParams.get('compare_mode')) {
                                    $('#compare_mode').val(initialUrl.searchParams.get('compare_mode'));
                                }
                                if (initialUrl.searchParams.get('compare_start_date')) {
                                    $('#compare_start_date').val(initialUrl.searchParams.get('compare_start_date'));
                                }
                                if (initialUrl.searchParams.get('compare_end_date')) {
                                    $('#compare_end_date').val(initialUrl.searchParams.get('compare_end_date'));
                                }

                                const initialCompareOn = ($('#compare_mode').val() === 'on');
                                $('#compareToggleKOL').prop('checked', initialCompareOn);
                                $('#compareStateLabelKOL').text(initialCompareOn ? 'ON' : 'OFF');
                                $('#compareDateWrapKOL').toggleClass('d-none', !initialCompareOn);

                                const initialComparePicker = $('#tanggal_compare').data('daterangepicker');
                                if (initialComparePicker) {
                                    const cStart = moment($('#compare_start_date').val(), 'YYYY-MM-DD');
                                    const cEnd = moment($('#compare_end_date').val(), 'YYYY-MM-DD');
                                    if (cStart.isValid() && cEnd.isValid()) {
                                        initialComparePicker.setStartDate(cStart);
                                        initialComparePicker.setEndDate(cEnd);
                                        if (typeof initialComparePicker.updateInput === 'function') {
                                            initialComparePicker.updateInput();
                                        } else {
                                            $('#tanggal_compare').val(`${cStart.format('DD/MM/YYYY')} - ${cEnd.format('DD/MM/YYYY')}`);
                                        }
                                    }
                                }
                                syncChipActiveStateFromUrl(initialUrl);

                                $('#overviewFilterForm').on('submit', function(e) {
                                    e.preventDefault();
                                    applyOverviewFilterAjax();
                                });

                                $(document).on('click', '.js-ajax-chip', function(e) {
                                    e.preventDefault();
                                    applyOverviewFilterAjax(null, {
                                        param: ($(this).data('chip-param') || '').toString(),
                                        value: ($(this).data('chip-value') || '').toString()
                                    });
                                });

                                $('#filter_pic, #filter_product').on('change', function() {
                                    applyOverviewFilterAjax();
                                });

                                $('#tanggal').on('apply.daterangepicker', function() {
                                    applyOverviewFilterAjax();
                                });

                                $('#compareToggleKOL').on('change', function() {
                                    const on = this.checked;
                                    $('#compare_mode').val(on ? 'on' : 'off');
                                    $('#compareStateLabelKOL').text(on ? 'ON' : 'OFF');
                                    $('#compareDateWrapKOL').toggleClass('d-none', !on);
                                    applyOverviewFilterAjax();
                                });
                            });
                            </script>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-12 mt-3">
            <ul class="nav nav-tabs endorse-data-tabs" id="endorseDataTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="endorse-matrix-tab" data-bs-toggle="tab" data-bs-target="#endorse-matrix-panel" type="button" role="tab" aria-controls="endorse-matrix-panel" aria-selected="true">Matriks Performa Endorse</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="endorse-subdata-tab" data-bs-toggle="tab" data-bs-target="#endorse-subdata-panel" type="button" role="tab" aria-controls="endorse-subdata-panel" aria-selected="false">Subdata Informasi Endorsement</button>
                </li>
            </ul>
            <div class="tab-content" id="endorseDataTabContent">
        <div class="tab-pane fade show active" id="endorse-matrix-panel" role="tabpanel" aria-labelledby="endorse-matrix-tab">
            <div class="kol-matrix-section">
                <div class="kol-matrix-scroll" id="kolMatrixCards"></div>
                <div class="kol-matrix-chart-wrap">
                    <div class="kol-matrix-chart-title" id="kolMatrixChartTitle">Trend Views</div>
                    <div class="kol-matrix-chart-subtitle" id="kolMatrixChartSubtitle">Periode utama dibanding periode sebelumnya</div>
                    <div class="kol-matrix-chart-canvas">
                        <canvas id="kolMatrixChart"></canvas>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    <button type="button" class="kol-matrix-toggle-text" id="kolMatrixToggleTableBtn">Lihat Tabel Detail</button>
                </div>
                <div class="kol-matrix-table-wrap d-none" id="kolMatrixTableSection">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="kol-matrix-chart-title mb-0">Tabel Detail Matriks</div>
                        <div class="small text-muted">Klik angka untuk lihat data sumber</div>
                    </div>
                    <div id="kolMatrixDetailTable"></div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="endorse-subdata-panel" role="tabpanel" aria-labelledby="endorse-subdata-tab">
            <div class="kol-matrix-section subdata-endorse-section">
                <div class="subdata-summary-grid">
                <?php
                $sum = array();
                $i = 0;
                $sum[$i]['code'] = "kol-1";
                $sum[$i]['img'] = "bi bi-people";
                $sum[$i]['color_box'] = "#F2994A1A";
                $sum[$i]['color_icon'] = "#f2994a";
                $sum[$i]['title'] = "INFLUENCER";
                $sum[$i]['unit'] = "BCM";
                $i++;
                $sum[$i]['code'] = "kol-2";
                $sum[$i]['img'] = "bi bi-person-video2";
                $sum[$i]['color_box'] = "#F2994A1A";
                $sum[$i]['color_icon'] = "#f2994a";
                $sum[$i]['title'] = "ENDORSE";
                $sum[$i]['unit'] = "BCM";
                $i++;
                $sum[$i]['code'] = "kol-5";
                $sum[$i]['img'] = "bi bi-heart";
                $sum[$i]['color_box'] = "#F2994A1A";
                $sum[$i]['color_icon'] = "#f2994a";
                $sum[$i]['title'] = "ENGAGEMENT";
                $sum[$i]['unit'] = "BCM";
                $i++;

                ?>
                <?php foreach ($sum as $k => $v) { ?>
                    <div class="subdata-summary-card">
                        <div class="kol-matrix-head">
                            <div class="kol-matrix-name"><?= $v['title'] ?></div>
                            <div class="subdata-icon"><i class="<?= $v['img'] ?>"></i></div>
                        </div>
                        <div class="kol-matrix-value" id="summary-<?= $v['code'] ?>"><i class="fa fa-circle-o-notch fa-spin"></i></div>
                        <div class="kol-matrix-sub">Data periode aktif</div>
                        <?php if ($v['code'] === 'kol-5') { ?>
                            <div class="engagement-breakdown" id="summary-kol-5-breakdown">
                                <i class="fa fa-circle-o-notch fa-spin"></i>
                            </div>
                        <?php } ?>
                    </div>
                    <!-- <?php if (in_array($v['code'], array('kol-1', 'kol-2'))) { ?>
                        <script>
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/get_summary_v2?site=<?= $site ?>&id=<?= $v['code'] ?>&brand=<?= $_GET['brand'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                                success: function(html) {
                                    $("#summary-<?= $v['code'] ?>").html(html.html);
                                }
                            });
                        </script>
                    <?php } ?> -->
                <?php } ?>
                </div>

                <div class="card summary">
                    <div class="row">
                        <div class="col-md-3">
                            <input type="text" class="form-control" style="height: 30px !important;" id="chart_tanggal" placeholder="Pilih rentang tanggal...">
                            <input type="hidden" id="chart_start_date" value="<?= $_GET['chart_start_date'] ?? $_GET['start_date'] ?? $start_date ?>">
                            <input type="hidden" id="chart_until_date" value="<?= $_GET['chart_until_date'] ?? $_GET['until_date'] ?? $until_date ?>">
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-primary" style="height: 30px !important; padding: 0px 0px !important; margin-left: -16px !important;" onclick="applyChartFilter()">
                                <i class="bi bi-search fs-16"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-grid d-md-flex d-lg-flex">
                                    <?php
                                    $arr = ["Daily","Views","CPM","Engagement","Cost","Jumlah Konten","Kenaikan Saja"];

                                    foreach ($arr as $k => $v) {
                                        $text = ($k === 0 || $k === 1) ? "checked" : "";
                                        if ($v !== "Kenaikan Saja") {
                                        ?>
                                        <input onclick="checkbox(<?= $k ?>)" <?= $text ?> type="checkbox" id="c-<?= $k ?>" class="me-2 c-checkbox">
                                        <label for="c-<?= $k ?>" class="fw-400 me-2"><?= $v ?></label>
                                    <?php }} ?>
                                </div>
                                <div id="kenaikan-only-wrap" style="margin-left: auto;">
                                    <?php
                                    $kenaikanIndex = array_search("Kenaikan Saja", $arr);
                                    if ($kenaikanIndex !== false) {
                                    ?>
                                    <input onclick="checkbox(<?= $kenaikanIndex ?>)" type="checkbox" id="c-<?= $kenaikanIndex ?>" class="me-2 c-checkbox" style="width: 18px; height: 18px; cursor: pointer;">
                                    <label for="c-<?= $kenaikanIndex ?>" class="fw-400" style="cursor: pointer;">Kenaikan Saja</label>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="subdata-chart-layout">
                        <div class="subdata-chart-main">
                            <div id="summary-chart"><i class="fa fa-circle-o-notch fa-spin"></i> Memuat data ...</div>
                            <div id="summary-table"><i class="fa fa-circle-o-notch fa-spin"></i> Memuat data ...</div>
                        </div>
                    </div>
                    <div class="top-content-sidebar mt-3">
                        <div class="top-content-head">Top 3 Performa Konten</div>
                        <div class="top-content-sub">Diurutkan dari views tertinggi (tabel endorse)</div>
                        <div id="top-content-list" class="top-content-list"><i class="fa fa-circle-o-notch fa-spin"></i> Memuat data ...</div>
                    </div>
                    <script src="https://unpkg.com/@popperjs/core@2"></script>
                    <script src="https://unpkg.com/tippy.js@6"></script>
                    <script>
                        const kolMatrixMetricConfig = {
                            views: { label: 'Views', color: '#0f8b8d', format: 'number' },
                            cpm: { label: 'CPM', color: '#f59e0b', format: 'currency' },
                            uploaded: { label: 'Total Konten Diupload', color: '#3b82f6', format: 'number' },
                            fyp: { label: 'Total Konten FYP', color: '#8b5cf6', format: 'number' },
                            spent: { label: 'Total Cost', color: '#ef4444', format: 'currency' },
                            hpp_ongkir: { label: 'HPP & Ongkir', color: '#0891b2', format: 'currency' }
                        };

                        let kolMatrixPayload = null;
                        let kolMatrixActiveMetric = 'views';
                        let kolMatrixChart = null;
                        let kolMatrixTableVisible = false;
                        let kolMatrixDetailModalInstance = null;
                        window.__pendingEndorseChartRender = false;
                        window.__pendingTopContentRender = false;

                        function formatMatrixShort(value) {
                            const n = Number(value || 0);
                            const abs = Math.abs(n);
                            if (abs >= 1000000000) {
                                const v = n / 1000000000;
                                return v.toFixed(v % 1 === 0 ? 0 : 1).replace('.', ',') + ' M';
                            }
                            if (abs >= 1000000) {
                                const v = n / 1000000;
                                return v.toFixed(v % 1 === 0 ? 0 : 1).replace('.', ',') + ' JT';
                            }
                            return new Intl.NumberFormat('id-ID', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(Math.round(n));
                        }

                        function formatMatrixValue(value, format) {
                            if (format === 'currency') return 'Rp ' + formatMatrixShort(value);
                            return formatMatrixShort(value);
                        }

                        function formatMatrixFull(value, format) {
                            const n = Number(value || 0);
                            if (format === 'currency') {
                                return 'Rp ' + new Intl.NumberFormat('id-ID', {
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                }).format(Math.round(n));
                            }
                            return new Intl.NumberFormat('id-ID', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 2
                            }).format(n);
                        }

                        function escapeHtml(value) {
                            if (value == null) return '';
                            return String(value)
                                .replace(/&/g, '&amp;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/"/g, '&quot;')
                                .replace(/'/g, '&#039;');
                        }

                        function formatTopNumber(value) {
                            return new Intl.NumberFormat('id-ID', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(Number(value || 0));
                        }

                        function platformIcon(platform) {
                            const p = String(platform || '').toLowerCase();
                            if (p === 'tiktok') return '<?= base_url() ?>/assets/img/icon/icon-tiktok.png';
                            if (p === 'instagram') return '<?= base_url() ?>/assets/img/icon/icon-ig.png';
                            if (p === 'youtube') return '<?= base_url() ?>/assets/img/icon/icon-youtube.png';
                            if (p === 'facebook') return '<?= base_url() ?>/assets/img/icon/icon-fb.png';
                            if (p === 'twitter') return '<?= base_url() ?>/assets/img/icon/icon-twitter.png';
                            if (p === 'threads') return '<?= base_url() ?>/assets/img/icon/icon-threads.png';
                            return '<?= base_url() ?>/assets/img/icon/icon-no.png';
                        }

                        function showTopContentSkeleton() {
                            $("#top-content-list").html(`
                                <div class="skeleton-shimmer skeleton-block" style="height:92px;"></div>
                                <div class="skeleton-shimmer skeleton-block" style="height:92px;"></div>
                                <div class="skeleton-shimmer skeleton-block" style="height:92px;"></div>
                            `);
                        }

                        function renderTopContent(rows) {
                            const list = Array.isArray(rows) ? rows : [];
                            if (!list.length) {
                                $("#top-content-list").html('<div class="small text-muted">Belum ada data konten pada periode ini.</div>');
                                return;
                            }

                            const html = list.map(function(row, idx) {
                                const rank = idx + 1;
                                const creator = escapeHtml(row.nama_creator || '-');
                                const campaign = escapeHtml(row.campaign_title || '-');
                                const pic = escapeHtml(row.pic || '-');
                                const platform = escapeHtml(row.platform || '-');
                                const views = formatTopNumber(row.views || 0);
                                const cpm = formatTopNumber(row.cpm || 0);
                                const cost = formatTopNumber(row.total_cost || 0);
                                const icon = platformIcon(row.platform || '');
                                const rawLink = String(row.link_upload || '').trim();
                                const safeLink = escapeHtml(rawLink);
                                const isTikTok = String(row.platform || '').toLowerCase() === 'tiktok';

                                const mediaLink = rawLink
                                    ? (isTikTok
                                        ? `<a href="${safeLink}" target="_blank" rel="noopener noreferrer" class="top-content-media-link top-tiktok-embed-trigger" data-tiktok-url="${safeLink}"><img src="${icon}" style="width:14px;height:14px;"> Preview Konten</a>`
                                        : `<a href="${safeLink}" target="_blank" rel="noopener noreferrer" class="top-content-media-link"><img src="${icon}" style="width:14px;height:14px;"> Buka Konten</a>`)
                                    : '<div class="top-content-meta">Link upload belum tersedia</div>';

                                return `
                                    <div class="top-content-item">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="top-content-rank">#${rank}</span>
                                            <img src="${icon}" style="width:18px;height:18px;border-radius:4px;" alt="${platform}">
                                        </div>
                                        <div class="top-content-title">${creator}</div>
                                        <div class="top-content-meta">Campaign: <b>${campaign}</b></div>
                                        <div class="top-content-meta">PIC: <b>${pic}</b></div>
                                        <div class="top-content-meta">Views: <b>${views}</b> | CPM: <b>${cpm}</b></div>
                                        <div class="top-content-meta">Cost: <b>Rp ${cost}</b></div>
                                        ${mediaLink}
                                    </div>
                                `;
                            }).join('');

                            $("#top-content-list").html(html);
                            initOverviewTopTiktokPreview();
                        }

                        function extractTopTiktokVideoId(rawUrl) {
                            if (!rawUrl) return '';
                            const url = String(rawUrl).trim();
                            const match = url.match(/\/video\/(\d+)/i) || url.match(/\/v\/(\d+)/i) || url.match(/\/(\d+)\.html/i);
                            return match ? match[1] : '';
                        }

                        function extractTopTiktokPhotoId(rawUrl) {
                            if (!rawUrl) return '';
                            const url = String(rawUrl).trim();
                            const match = url.match(/\/photo\/(\d+)/i);
                            return match ? match[1] : '';
                        }

                        function buildTopTiktokVideoHtml(playUrl) {
                            if (!playUrl) return '';
                            return '' +
                                '<div style="width:320px; height:520px; overflow:hidden; border-radius:8px; background:#fff;">' +
                                '<video src="' + playUrl + '" style="width:100%; height:100%; object-fit:cover;" controls autoplay muted loop playsinline preload="metadata"></video>' +
                                '</div>';
                        }

                        function fetchTopTiktokVideoWithRetry(url, attempt, onSuccess, onFail) {
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
                                            fetchTopTiktokVideoWithRetry(url, attempt + 1, onSuccess, onFail);
                                        }, 400);
                                    } else {
                                        onFail();
                                    }
                                },
                                error: function() {
                                    if (attempt < maxAttempts) {
                                        setTimeout(function() {
                                            fetchTopTiktokVideoWithRetry(url, attempt + 1, onSuccess, onFail);
                                        }, 400);
                                    } else {
                                        onFail();
                                    }
                                }
                            });
                        }

                        function buildTopTiktokPhotoCarousel(images, uid) {
                            if (!images || !images.length) return '';
                            const dots = images.map(function(_, i) {
                                return '<span class="tt-dot ' + (i === 0 ? 'active' : '') + '" data-idx="' + i + '"></span>';
                            }).join('');
                            const imagesHtml = images.map(function(u) {
                                return '<span data-src="' + u + '"></span>';
                            }).join('');
                            return '' +
                                '<div class="tt-carousel" data-uid="' + uid + '" data-total="' + images.length + '" data-index="0" style="width:320px;">' +
                                '<div class="tt-carousel-frame"><img src="' + images[0] + '" alt="TikTok Photo" /></div>' +
                                '<div class="tt-carousel-controls">' +
                                '<button type="button" class="tt-btn" data-dir="-1">‹</button>' +
                                '<div class="tt-dots">' + dots + '</div>' +
                                '<button type="button" class="tt-btn" data-dir="1">›</button>' +
                                '</div>' +
                                '<div class="tt-counter">1 / ' + images.length + '</div>' +
                                '<div class="tt-images" style="display:none;">' + imagesHtml + '</div>' +
                                '</div>';
                        }

                        function initTopTiktokPhotoCarousel(container, autoSlideMs = 2500) {
                            const root = container.querySelector('.tt-carousel');
                            if (!root) return;
                            const images = Array.prototype.slice.call(root.querySelectorAll('.tt-images span')).map(function(s) {
                                return s.getAttribute('data-src');
                            });
                            const frameImg = root.querySelector('.tt-carousel-frame img');
                            const dots = Array.prototype.slice.call(root.querySelectorAll('.tt-dot'));
                            const counter = root.querySelector('.tt-counter');
                            const total = images.length;
                            let timerId = null;

                            function setIndex(nextIndex) {
                                let idx = nextIndex;
                                if (idx < 0) idx = total - 1;
                                if (idx >= total) idx = 0;
                                root.setAttribute('data-index', String(idx));
                                frameImg.src = images[idx];
                                dots.forEach(function(d) {
                                    d.classList.toggle('active', Number(d.dataset.idx) === idx);
                                });
                                counter.textContent = (idx + 1) + ' / ' + total;
                            }

                            root.querySelectorAll('.tt-btn').forEach(function(btn) {
                                btn.addEventListener('click', function() {
                                    const dir = Number(this.getAttribute('data-dir')) || 0;
                                    const current = Number(root.getAttribute('data-index')) || 0;
                                    setIndex(current + dir);
                                });
                            });

                            dots.forEach(function(dot) {
                                dot.addEventListener('click', function() {
                                    setIndex(Number(this.getAttribute('data-idx')) || 0);
                                });
                            });

                            if (total > 1 && autoSlideMs > 0) {
                                timerId = setInterval(function() {
                                    const current = Number(root.getAttribute('data-index')) || 0;
                                    setIndex(current + 1);
                                }, autoSlideMs);
                                root.dataset.timerId = String(timerId);
                            }
                        }

                        function initOverviewTopTiktokPreview() {
                            if (typeof tippy === 'undefined') return;
                            const tiktokTriggers = document.querySelectorAll('.top-tiktok-embed-trigger');
                            if (!tiktokTriggers.length) return;
                            tiktokTriggers.forEach(function(el) {
                                if (el._tippy) el._tippy.destroy();
                            });
                            tippy(tiktokTriggers, {
                                content: '<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat video...</div></div>',
                                allowHTML: true,
                                interactive: true,
                                placement: 'left',
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
                                onShow: function(instance) {
                                    const url = instance.reference.getAttribute('data-tiktok-url');
                                    const photoId = extractTopTiktokPhotoId(url);
                                    if (photoId) {
                                        instance.setContent('<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat foto...</div></div>');
                                        $.ajax({
                                            url: '<?= base_url() ?>endorse/get_tiktok_photo_images',
                                            type: 'POST',
                                            data: { content_id: photoId, url: url },
                                            dataType: 'json',
                                            success: function(res) {
                                                if (res && res.status && Array.isArray(res.data) && res.data.length) {
                                                    const uid = 'ovttc-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
                                                    instance.setContent(buildTopTiktokPhotoCarousel(res.data, uid));
                                                    setTimeout(function() {
                                                        initTopTiktokPhotoCarousel(instance.popper, 2500);
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

                                    const videoId = extractTopTiktokVideoId(url);
                                    if (!videoId) {
                                        instance.setContent('<div class="p-2 text-muted">Konten TikTok tidak ditemukan dari link upload.</div>');
                                        return;
                                    }
                                    instance.setContent('<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat video...</div></div>');
                                    fetchTopTiktokVideoWithRetry(url, 1, function(playUrl) {
                                        instance.setContent(buildTopTiktokVideoHtml(playUrl));
                                        setTimeout(function() {
                                            const vid = instance.popper.querySelector('video');
                                            if (vid) vid.play().catch(function(){});
                                        }, 0);
                                    }, function() {
                                        instance.setContent('<div class="p-2 text-muted">Video TikTok tidak ditemukan dari link upload.</div>');
                                    });
                                },
                                onHidden: function(instance) {
                                    const root = instance.popper.querySelector('.tt-carousel');
                                    if (root && root.dataset.timerId) {
                                        clearInterval(Number(root.dataset.timerId));
                                        delete root.dataset.timerId;
                                    }
                                }
                            });
                        }

                        function loadTopPerformanceContent(chartStartDate, chartUntilDate) {
                            const subdataVisible = $('#endorse-subdata-panel').hasClass('show') || $('#endorse-subdata-panel').hasClass('active');
                            if (!subdataVisible) {
                                window.__pendingTopContentRender = true;
                                return;
                            }
                            window.__pendingTopContentRender = false;
                            showTopContentSkeleton();

                            const params = new URLSearchParams(window.location.search);
                            params.set('chart_start_date', chartStartDate || $('#chart_start_date').val() || '');
                            params.set('chart_until_date', chartUntilDate || $('#chart_until_date').val() || '');
                            params.set('is_dashboard', 'true');

                            $.ajax({
                                url: '<?= base_url() ?>ajax/get_overview_top_content?' + params.toString(),
                                dataType: 'json',
                                success: function(res) {
                                    if (!res || res.status !== true) {
                                        $("#top-content-list").html('<div class="small text-danger">Gagal memuat Top 3 konten.</div>');
                                        return;
                                    }
                                    renderTopContent(res.rows || []);
                                },
                                error: function() {
                                    $("#top-content-list").html('<div class="small text-danger">Gagal memuat Top 3 konten.</div>');
                                }
                            });
                        }

                        function matrixChange(compareValue, mainValue) {
                            if (!compareValue) return null;
                            return ((mainValue - compareValue) / compareValue) * 100;
                        }

                        function renderKolMatrixCards() {
                            const container = document.getElementById('kolMatrixCards');
                            if (!container) return;

                            if (!kolMatrixPayload || !kolMatrixPayload.period_main || !kolMatrixPayload.period_compare) {
                                container.innerHTML = `
                                    <div class="kol-matrix-card skeleton-shimmer" style="min-width:220px;height:128px;"></div>
                                    <div class="kol-matrix-card skeleton-shimmer" style="min-width:220px;height:128px;"></div>
                                    <div class="kol-matrix-card skeleton-shimmer" style="min-width:220px;height:128px;"></div>
                                `;
                                return;
                            }

                            const totalsMain = kolMatrixPayload.period_main.totals || {};
                            const totalsCompare = kolMatrixPayload.period_compare.totals || {};
                            const keys = Object.keys(kolMatrixMetricConfig);

                            container.innerHTML = keys.map((key) => {
                                const meta = kolMatrixMetricConfig[key];
                                const mainVal = Number(totalsMain[key] || 0);
                                const compareVal = Number(totalsCompare[key] || 0);
                                const chg = matrixChange(compareVal, mainVal);
                                const up = chg !== null && chg >= 0;
                                const chgText = chg === null ? 'N/A' : `${Math.abs(chg).toFixed(2).replace('.', ',')}% vs Periode 1`;

                                return `
                                    <div class="kol-matrix-card ${kolMatrixActiveMetric === key ? 'is-active' : ''}" data-metric="${key}">
                                        <div class="kol-matrix-head">
                                            <div class="kol-matrix-name">${meta.label}</div>
                                            <input type="checkbox" class="kol-matrix-check" ${kolMatrixActiveMetric === key ? 'checked' : ''}>
                                        </div>
                                        <div class="kol-matrix-value">
                                            <button type="button" class="btn-link-matrix js-kol-matrix-detail" data-metric="${key}" data-scope="total">${formatMatrixValue(mainVal, meta.format)}</button>
                                        </div>
                                        <div class="kol-matrix-sub">Periode 1: ${formatMatrixValue(compareVal, meta.format)}</div>
                                        <div class="kol-matrix-change ${chg !== null && up ? 'up' : 'down'}">
                                            <i class="bi ${chg === null ? 'bi-dash' : (up ? 'bi-arrow-up-right' : 'bi-arrow-down-right')}"></i>
                                            <span>${chgText}</span>
                                        </div>
                                    </div>
                                `;
                            }).join('');
                        }

                        function renderKolMatrixChart() {
                            if (!kolMatrixPayload || !kolMatrixPayload.period_main) return;
                            const canvas = document.getElementById('kolMatrixChart');
                            if (!canvas || typeof Chart === 'undefined') return;

                            const meta = kolMatrixMetricConfig[kolMatrixActiveMetric] || kolMatrixMetricConfig.views;
                            const mainDaily = kolMatrixPayload.period_main.daily || [];
                            const compareDaily = (kolMatrixPayload.period_compare && kolMatrixPayload.period_compare.daily) ? kolMatrixPayload.period_compare.daily : [];
                            const compareOn = ($('#compare_mode').val() === 'on');

                            const labels = mainDaily.map(item => moment(item.date, 'YYYY-MM-DD').format('D MMM'));
                            const mainValues = mainDaily.map(item => Number(item[kolMatrixActiveMetric] || 0));
                            const compareValues = compareDaily.map(item => Number(item[kolMatrixActiveMetric] || 0));

                            const chartTitle = document.getElementById('kolMatrixChartTitle');
                            if (chartTitle) {
                                chartTitle.textContent = `Trend ${meta.label}`;
                            }

                            if (kolMatrixChart) {
                                kolMatrixChart.destroy();
                            }

                            const ctx = canvas.getContext('2d');
                            const hex = (meta.color || '#0f8b8d').replace('#', '');
                            const int = parseInt(hex, 16);
                            const r = (int >> 16) & 255;
                            const g = (int >> 8) & 255;
                            const b = int & 255;

                            const datasets = [{
                                label: meta.label,
                                data: mainValues,
                                borderColor: meta.color,
                                backgroundColor: function(context) {
                                    const chart = context.chart;
                                    const chartArea = chart.chartArea;
                                    if (!chartArea) {
                                        return `rgba(${r}, ${g}, ${b}, 0.22)`;
                                    }
                                    const areaGradient = chart.ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                                    areaGradient.addColorStop(0, `rgba(${r}, ${g}, ${b}, 0.34)`);
                                    areaGradient.addColorStop(0.55, `rgba(${r}, ${g}, ${b}, 0.18)`);
                                    areaGradient.addColorStop(1, `rgba(${r}, ${g}, ${b}, 0.06)`);
                                    return areaGradient;
                                },
                                fill: 'start',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 3,
                                tension: 0.35
                            }];

                            if (compareOn) {
                                datasets.push({
                                    label: `${meta.label} (Banding)`,
                                    data: compareValues,
                                    borderColor: meta.color,
                                    borderDash: [6, 6],
                                    fill: false,
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 3,
                                    tension: 0.35
                                });
                            }

                            kolMatrixChart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels,
                                    datasets
                                },
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
                                                    return `${context.dataset.label}: ${formatMatrixValue(context.raw || 0, meta.format)}`;
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        x: {
                                            grid: { display: false },
                                            ticks: { maxTicksLimit: 8 }
                                        },
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                callback: function(value) {
                                                    return formatMatrixShort(value);
                                                }
                                            },
                                            grid: { color: 'rgba(15,23,42,0.08)', drawBorder: false }
                                        }
                                    }
                                }
                            });
                        }

                        function renderKolMatrixDetailTable() {
                            const container = document.getElementById('kolMatrixDetailTable');
                            if (!container) return;
                            if (!kolMatrixPayload || !kolMatrixPayload.period_main) {
                                container.innerHTML = '<div class="skeleton-shimmer skeleton-block" style="height:160px;"></div>';
                                return;
                            }

                            const mainDaily = kolMatrixPayload.period_main.daily || [];
                            const totalsMain = kolMatrixPayload.period_main.totals || {};
                            if (!Array.isArray(mainDaily) || mainDaily.length === 0) {
                                container.innerHTML = '<div class="small text-muted">Belum ada data pada periode ini.</div>';
                                return;
                            }

                            const keys = Object.keys(kolMatrixMetricConfig);
                            const headCols = ['<th style="min-width:130px;">Metrik</th>'];
                            mainDaily.forEach((row) => {
                                const dateRaw = row.date || '';
                                const dateLabel = moment(dateRaw, 'YYYY-MM-DD').isValid() ? moment(dateRaw, 'YYYY-MM-DD').format('D MMM') : dateRaw;
                                headCols.push(`<th style="min-width:100px;">${escapeHtml(dateLabel)}</th>`);
                            });
                            headCols.push('<th style="min-width:100px;">Total</th>');

                            const rows = keys.map((key) => {
                                const meta = kolMatrixMetricConfig[key];
                                const activeClass = kolMatrixActiveMetric === key ? 'metric-active' : '';
                                const cells = [`<td class="${activeClass}"><strong>${escapeHtml(meta.label)}</strong></td>`];

                                mainDaily.forEach((row) => {
                                    const dateRaw = row.date || '';
                                    const val = Number(row[key] || 0);
                                    cells.push(`
                                        <td class="${activeClass}">
                                            <button type="button" class="btn-link-matrix js-kol-matrix-detail" data-scope="date" data-date="${escapeHtml(dateRaw)}" data-metric="${key}">
                                                ${formatMatrixValue(val, meta.format)}
                                            </button>
                                        </td>
                                    `);
                                });

                                const totalVal = Number(totalsMain[key] || 0);
                                cells.push(`
                                    <td class="${activeClass}">
                                        <button type="button" class="btn-link-matrix js-kol-matrix-detail" data-scope="total" data-metric="${key}">
                                            ${formatMatrixValue(totalVal, meta.format)}
                                        </button>
                                    </td>
                                `);
                                return `<tr>${cells.join('')}</tr>`;
                            });

                            container.innerHTML = `
                                <div class="table-responsive">
                                    <table class="kol-mini-table">
                                        <thead><tr>${headCols.join('')}</tr></thead>
                                        <tbody>${rows.join('')}</tbody>
                                    </table>
                                </div>
                            `;
                        }

                        function buildKolMatrixDetailParams(metric, scope, dateValue) {
                            const params = new URLSearchParams(window.location.search);
                            params.set('start_date', $('#start_date').val() || '');
                            params.set('until_date', $('#end_date').val() || '');
                            params.set('compare_mode', $('#compare_mode').val() || 'off');
                            params.set('compare_start_date', $('#compare_start_date').val() || '');
                            params.set('compare_end_date', $('#compare_end_date').val() || '');
                            params.set('pic', ($('#filter_pic').val() || []).join(','));
                            params.set('product', ($('#filter_product').val() || []).join(','));
                            params.set('metric', metric || kolMatrixActiveMetric || 'views');
                            params.set('scope', scope || 'total');
                            if (scope === 'date' && dateValue) {
                                params.set('date', dateValue);
                            } else {
                                params.delete('date');
                            }
                            return params;
                        }

                        function setKolMatrixTableVisibility(show) {
                            kolMatrixTableVisible = !!show;
                            const section = document.getElementById('kolMatrixTableSection');
                            const btn = document.getElementById('kolMatrixToggleTableBtn');
                            if (section) {
                                if (kolMatrixTableVisible) {
                                    section.classList.remove('d-none');
                                } else {
                                    section.classList.add('d-none');
                                }
                            }
                            if (btn) {
                                btn.textContent = kolMatrixTableVisible ? 'Sembunyikan Tabel Detail' : 'Lihat Tabel Detail';
                            }
                        }

                        function destroyKolMatrixDetailDataTable() {
                            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#kolMatrixDetailDataTable')) {
                                $('#kolMatrixDetailDataTable').DataTable().destroy();
                            }
                        }

                        function initKolMatrixDetailDataTable(sourceType) {
                            if (!$.fn.DataTable) {
                                return;
                            }

                            destroyKolMatrixDetailDataTable();
                            const orderColumn = sourceType === 'transaction' ? 0 : 0;
                            $('#kolMatrixDetailDataTable').DataTable({
                                paging: true,
                                searching: true,
                                ordering: true,
                                info: true,
                                autoWidth: false,
                                pageLength: 25,
                                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
                                order: [[orderColumn, 'desc']],
                                language: {
                                    search: 'Cari:',
                                    lengthMenu: 'Tampilkan _MENU_ data',
                                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                                    infoEmpty: 'Tidak ada data',
                                    infoFiltered: '(difilter dari _MAX_ total data)',
                                    zeroRecords: 'Data tidak ditemukan',
                                    emptyTable: 'Tidak ada data.',
                                    paginate: {
                                        first: 'Pertama',
                                        last: 'Terakhir',
                                        next: 'Berikutnya',
                                        previous: 'Sebelumnya'
                                    }
                                }
                            });
                        }

                        function openKolMatrixDetail(metric, scope, dateValue) {
                            const metricKey = metric || kolMatrixActiveMetric || 'views';
                            const meta = kolMatrixMetricConfig[metricKey] || kolMatrixMetricConfig.views;
                            const title = scope === 'date'
                                ? `Detail ${meta.label} (${moment(dateValue, 'YYYY-MM-DD').format('D MMM YYYY')})`
                                : `Detail Total ${meta.label}`;
                            $('#kolMatrixDetailModalTitle').text(title);
                            destroyKolMatrixDetailDataTable();
                            $('#kolMatrixDetailModalBody').html('<i class="fa fa-circle-o-notch fa-spin"></i> Memuat detail ...');

                            if (!kolMatrixDetailModalInstance) {
                                kolMatrixDetailModalInstance = new bootstrap.Modal(document.getElementById('kolMatrixDetailModal'));
                            }
                            kolMatrixDetailModalInstance.show();

                            const params = buildKolMatrixDetailParams(metricKey, scope, dateValue);
                            $.ajax({
                                url: '<?= base_url() ?>ajax/get_overview_kol_matrix_detail?' + params.toString(),
                                dataType: 'json',
                                success: function(res) {
                                    if (!res || res.status !== true) {
                                        $('#kolMatrixDetailModalBody').html('<div class="text-danger small">Gagal memuat detail.</div>');
                                        return;
                                    }

                                    const summary = res.summary || {};
                                    const metricVal = formatMatrixFull(summary.value || 0, meta.format);
                                    const rowCount = Number(summary.row_count || 0);
                                    const extraInfo = [];
                                    if (res.source_type === 'endorse') {
                                        extraInfo.push(`Total Views: ${formatMatrixFull(summary.total_views || 0, 'number')}`);
                                        extraInfo.push(`Total Cost: ${formatMatrixFull(summary.total_spent || 0, 'currency')}`);
                                        extraInfo.push(`Avg CPM: ${formatMatrixFull(summary.avg_cpm || 0, 'currency')}`);
                                    } else {
                                        extraInfo.push(`Total HPP+Ongkir: ${formatMatrixFull(summary.value || 0, 'currency')}`);
                                    }

                                    let rowsHtml = '';
                                    const rows = Array.isArray(res.rows) ? res.rows : [];
                                    if (res.source_type === 'transaction') {
                                        const tr = rows.map(function(row) {
                                            return `<tr>
                                                <td>${escapeHtml(row.date_ref || '-')}</td>
                                                <td>${escapeHtml(row.order_id || '-')}</td>
                                                <td class="text-end" data-order="${Number(row.hpp || 0)}">${formatMatrixFull(row.hpp || 0, 'currency')}</td>
                                                <td class="text-end" data-order="${Number(row.ongkir || 0)}">${formatMatrixFull(row.ongkir || 0, 'currency')}</td>
                                                <td class="text-end" data-order="${Number(row.hpp_ongkir || 0)}">${formatMatrixFull(row.hpp_ongkir || 0, 'currency')}</td>
                                            </tr>`;
                                        }).join('');
                                        rowsHtml = `
                                            <div class="table-responsive">
                                                <table id="kolMatrixDetailDataTable" class="kol-mini-table table table-striped table-bordered table-hover w-100">
                                                    <thead>
                                                        <tr><th>Tanggal</th><th>Order ID</th><th>HPP</th><th>Ongkir</th><th>Total</th></tr>
                                                    </thead>
                                                    <tbody>${tr}</tbody>
                                                </table>
                                            </div>
                                        `;
                                    } else {
                                        const tr = rows.map(function(row) {
                                            return `<tr>
                                                <td>${escapeHtml(row.date_ref || '-')}</td>
                                                <td>${escapeHtml(row.campaign_name || '-')}</td>
                                                <td>${escapeHtml(row.nama_creator || '-')}</td>
                                                <td>${escapeHtml(row.platform || '-')}</td>
                                                <td class="text-end" data-order="${Number(row.views || 0)}">${formatMatrixFull(row.views || 0, 'number')}</td>
                                                <td class="text-end" data-order="${Number(row.spent || 0)}">${formatMatrixFull(row.spent || 0, 'currency')}</td>
                                                <td class="text-end" data-order="${Number(row.cpm || 0)}">${formatMatrixFull(row.cpm || 0, 'currency')}</td>
                                                <td class="text-center" data-order="${Number(row.fyp || 0)}">${Number(row.fyp || 0) === 1 ? 'Ya' : 'Tidak'}</td>
                                                <td>${escapeHtml(row.product || '-')}</td>
                                            </tr>`;
                                        }).join('');
                                        rowsHtml = `
                                            <div class="table-responsive">
                                                <table id="kolMatrixDetailDataTable" class="kol-mini-table table table-striped table-bordered table-hover w-100">
                                                    <thead>
                                                        <tr><th>Tanggal</th><th>Campaign</th><th>Creator</th><th>Platform</th><th>Views</th><th>Cost</th><th>CPM</th><th>FYP</th><th>Produk</th></tr>
                                                    </thead>
                                                    <tbody>${tr}</tbody>
                                                </table>
                                            </div>
                                        `;
                                    }

                                    $('#kolMatrixDetailModalBody').html(`
                                        <div class="kol-matrix-detail-meta">
                                            <div><strong>Metric:</strong> ${escapeHtml(meta.label)} | <strong>Nilai:</strong> ${metricVal} | <strong>Rows:</strong> ${rowCount}</div>
                                            <div>${escapeHtml(extraInfo.join(' | '))}</div>
                                        </div>
                                        ${rowsHtml}
                                    `);
                                    initKolMatrixDetailDataTable(res.source_type);
                                },
                                error: function() {
                                    destroyKolMatrixDetailDataTable();
                                    $('#kolMatrixDetailModalBody').html('<div class="text-danger small">Gagal memuat detail.</div>');
                                }
                            });
                        }

                        function get_kol_matrix() {
                            const cardsEl = document.getElementById('kolMatrixCards');
                            if (cardsEl) {
                                cardsEl.innerHTML = `
                                    <div class="kol-matrix-card skeleton-shimmer" style="min-width:220px;height:128px;"></div>
                                    <div class="kol-matrix-card skeleton-shimmer" style="min-width:220px;height:128px;"></div>
                                    <div class="kol-matrix-card skeleton-shimmer" style="min-width:220px;height:128px;"></div>
                                    <div class="kol-matrix-card skeleton-shimmer" style="min-width:220px;height:128px;"></div>
                                `;
                            }

                            const params = new URLSearchParams(window.location.search);
                            params.set('start_date', $('#start_date').val() || '');
                            params.set('until_date', $('#end_date').val() || '');
                            params.set('compare_mode', $('#compare_mode').val() || 'off');
                            params.set('compare_start_date', $('#compare_start_date').val() || '');
                            params.set('compare_end_date', $('#compare_end_date').val() || '');
                            params.set('pic', ($('#filter_pic').val() || []).join(','));
                            params.set('product', ($('#filter_product').val() || []).join(','));

                            $.ajax({
                                url: '<?= base_url() ?>ajax/get_overview_kol_matrix?' + params.toString(),
                                dataType: 'json',
                                success: function(res) {
                                    kolMatrixPayload = res || null;
                                    renderKolMatrixCards();
                                    renderKolMatrixChart();
                                    renderKolMatrixDetailTable();
                                },
                                error: function() {
                                    const cards = document.getElementById('kolMatrixCards');
                                    if (cards) {
                                        cards.innerHTML = '<div class="text-danger small">Gagal memuat matriks.</div>';
                                    }
                                    const table = document.getElementById('kolMatrixDetailTable');
                                    if (table) {
                                        table.innerHTML = '<div class="text-danger small">Gagal memuat tabel detail.</div>';
                                    }
                                }
                            });
                        }

                        $(document).on('click', '.kol-matrix-card', function() {
                            const metric = $(this).data('metric');
                            if (!metric || !kolMatrixMetricConfig[metric]) return;
                            kolMatrixActiveMetric = metric;
                            renderKolMatrixCards();
                            renderKolMatrixChart();
                            renderKolMatrixDetailTable();
                        });

                        $(document).on('click', '.js-kol-matrix-detail', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const metric = $(this).data('metric') || kolMatrixActiveMetric;
                            const scope = ($(this).data('scope') || 'total').toString();
                            const dateValue = ($(this).data('date') || '').toString();
                            openKolMatrixDetail(metric, scope, dateValue);
                        });

                        $(document).on('hidden.bs.modal', '#kolMatrixDetailModal', function() {
                            destroyKolMatrixDetailDataTable();
                        });

                        $('#kolMatrixToggleTableBtn').on('click', function() {
                            setKolMatrixTableVisibility(!kolMatrixTableVisible);
                        });

                        function setUrlParams(params) {
                            const url = new URL(window.location);
                            Object.keys(params).forEach(k => {
                                if (params[k] === null || params[k] === undefined || params[k] === '') {
                                    url.searchParams.delete(k);
                                } else {
                                    url.searchParams.set(k, params[k]);
                                }
                            });
                            history.replaceState({}, '', url);
                        }

                        function getUrlParam(name) {
                            return new URL(window.location).searchParams.get(name);
                        }

                        $(document).ready(function() {
                            let urlStart = getUrlParam('chart_start_date');
                            let urlEnd = getUrlParam('chart_until_date');

                            let phpStart = $('#chart_start_date').val();
                            let phpEnd = $('#chart_until_date').val();

                            const useStart = urlStart || phpStart || moment().subtract(30, 'days').format('YYYY-MM-DD');
                            const useEnd = urlEnd || phpEnd || moment().format('YYYY-MM-DD');

                            $('#chart_start_date').val(useStart);
                            $('#chart_until_date').val(useEnd);

                            const startDateMoment = moment(useStart, 'YYYY-MM-DD');
                            const endDateMoment = moment(useEnd, 'YYYY-MM-DD');

                            const presetRanges = {
                                "Hari Ini": [moment(), moment()],
                                "Kemarin": [moment().subtract(1, "days"), moment().subtract(1, "days")],
                                "7 Hari Terakhir": [moment().subtract(6, "days"), moment()],
                                "30 Hari Terakhir": [moment().subtract(29, "days"), moment()],
                                "Bulan Ini": [moment().startOf("month"), moment()],
                                "Bulan Lalu": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")]
                            };

                            function updateActiveButton(start, end) {
                                $(".custom-ranges button").removeClass("active");

                                Object.entries(presetRanges).forEach(([label, dates]) => {
                                    if (start.isSame(dates[0], 'day') && end.isSame(dates[1], 'day')) {
                                        $(".custom-ranges button").filter(function() {
                                            return $(this).text() === label;
                                        }).addClass("active");
                                    }
                                });
                            }

                            const picker = $('#chart_tanggal').daterangepicker({
                                startDate: startDateMoment,
                                endDate: endDateMoment,
                                showDropdowns: true,
                                alwaysShowCalendars: true,
                                opens: "center",
                                drops: "auto",
                                autoUpdateInput: true,
                                locale: {
                                    format: 'DD/MM/YYYY',
                                    separator: " - ",
                                    applyLabel: "Pilih",
                                    cancelLabel: "Batal",
                                    fromLabel: "Dari",
                                    toLabel: "Sampai",
                                    customRangeLabel: "Custom",
                                    daysOfWeek: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
                                    monthNames: ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"],
                                    firstDay: 1
                                }
                            }, function(start, end, label) {
                                $('#chart_start_date').val(start.format('YYYY-MM-DD'));
                                $('#chart_until_date').val(end.format('YYYY-MM-DD'));
                                updateActiveButton(start, end);
                            });

                            $('#chart_tanggal').val(startDateMoment.format('DD/MM/YYYY') + ' - ' + endDateMoment.format('DD/MM/YYYY'));

                            let rangeContainerAdded = false;

                            $('#chart_tanggal').on('show.daterangepicker', function(ev, picker) {
                                if (!rangeContainerAdded) {
                                    const container = $(".daterangepicker");

                                    container.find('.custom-ranges').remove();

                                    const rangeContainer = $("<div class='custom-ranges'></div>");

                                    $.each(presetRanges, function(label, dates) {
                                        const btn = $("<button type='button'></button>").text(label);
                                        btn.on("click", function() {
                                            const drp = $('#chart_tanggal').data("daterangepicker");

                                            drp.setStartDate(dates[0]);
                                            drp.setEndDate(dates[1]);
                                            drp.updateCalendars();

                                            $('#chart_tanggal').val(
                                                dates[0].format('DD/MM/YYYY') + ' - ' + dates[1].format('DD/MM/YYYY')
                                            );

                                            $('#chart_start_date').val(dates[0].format('YYYY-MM-DD'));
                                            $('#chart_until_date').val(dates[1].format('YYYY-MM-DD'));

                                            $(".custom-ranges button").removeClass("active");
                                            $(this).addClass("active");

                                            applyChartFilter();
                                        });
                                        rangeContainer.append(btn);
                                    });

                                    container.prepend(rangeContainer);
                                    updateActiveButton(startDateMoment, endDateMoment);
                                    rangeContainerAdded = true;
                                }
                            });

                            $('#c-0').prop('checked', true);
                            updateKenaikanVisibility();
                            setKolMatrixTableVisibility(false);

                            get_kol_matrix();
                            syncCampaignCheckboxState(function() {
                                get_chart();
                            });

                            $('#endorseDataTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                                const target = $(e.target).attr('data-bs-target') || '';
                                if (target === '#endorse-subdata-panel') {
                                    if (window.__pendingEndorseChartRender) {
                                        get_chart();
                                    } else if (window.__pendingTopContentRender) {
                                        loadTopPerformanceContent($('#chart_start_date').val(), $('#chart_until_date').val());
                                    }
                                }
                            });
                        });

                        function get_chart(options) {
                            options = options || {};
                            const chartOnly = !!options.chartOnly;
                            const subdataVisible = $('#endorse-subdata-panel').hasClass('show') || $('#endorse-subdata-panel').hasClass('active');
                            if (!subdataVisible) {
                                window.__pendingEndorseChartRender = true;
                                window.__pendingTopContentRender = true;
                                return;
                            }
                            window.__pendingEndorseChartRender = false;
                            window.__pendingTopContentRender = false;

                            showOverviewSkeleton(!chartOnly);

                            var chartStartDate = $('#chart_start_date').val();
                            var chartUntilDate = $('#chart_until_date').val();

                            if (!isValidDate(chartStartDate) || !isValidDate(chartUntilDate)) {
                                chartStartDate = '<?= $start_date ?>';
                                chartUntilDate = '<?= $until_date ?>';
                            }

                            setUrlParams({
                                chart_start_date: chartStartDate,
                                chart_until_date: chartUntilDate
                            });

                            var urlParams = new URLSearchParams(window.location.search);
                            urlParams.set('chart_start_date', chartStartDate);
                            urlParams.set('chart_until_date', chartUntilDate);
                            urlParams.set('is_dashboard', 'true');
                            loadTopPerformanceContent(chartStartDate, chartUntilDate);

                            var url = '<?= base_url() ?>ajax/get-chart-campaign?' + urlParams.toString();

                            $.ajax({
                                dataType: "json",
                                url: url,
                                success: function(html) {
                                    $("#summary-chart").html(html.html);
                                    $("#summary-table").html(html.table);
                                    if (!chartOnly) {
                                        $("#summary-kol-8").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                        $("#summary-kol-8").html(html.summary.cpm);
                                        $("#summary-kol-4").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                        $("#summary-kol-4").html(html.summary.views);
                                        $("#summary-kol-5").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                        $("#summary-kol-5").html(html.summary.engagement);
                                        $("#summary-kol-5-breakdown").html(
                                            'Likes: <b>' + (html.summary.engagement_likes || '0') + '</b> | ' +
                                            'Comment: <b>' + (html.summary.engagement_comment || '0') + '</b> | ' +
                                            'Share/Save: <b>' + (html.summary.engagement_share_save || '0') + '</b>'
                                        );
                                        $("#summary-kol-3").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                        $("#summary-kol-3").html(html.summary.cost);
                                        $("#summary-kol-1").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                        $("#summary-kol-1").html(html.summary.influencer);
                                        $("#summary-kol-2").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                        $("#summary-kol-2").html(html.summary.endorse);
                                    }

                                },
                                error: function() {
                                    $("#summary-chart").html('<div class="text-danger small">Gagal memuat chart.</div>');
                                    $("#summary-table").html('<div class="text-danger small">Gagal memuat tabel.</div>');
                                    $("#summary-kol-5-breakdown").html('Likes: <b>0</b> | Comment: <b>0</b> | Share/Save: <b>0</b>');
                                }
                            });
                        }

                        function showOverviewSkeleton(includeSummary) {
                            if (includeSummary === undefined) includeSummary = true;
                            if (includeSummary) {
                                const summaryIds = ['#summary-kol-1', '#summary-kol-2', '#summary-kol-3', '#summary-kol-4', '#summary-kol-5', '#summary-kol-8'];
                                summaryIds.forEach(function(sel) {
                                    $(sel).html('<div class="skeleton-shimmer skeleton-text" style="width:80px; margin-left:auto;"></div>');
                                });
                                $("#summary-kol-5-breakdown").html('<div class="skeleton-shimmer skeleton-text" style="width:170px;"></div>');
                            }
                            showTopContentSkeleton();

                            $("#summary-chart").html(`
                                <div class="mb-2 skeleton-shimmer skeleton-title"></div>
                                <div class="skeleton-shimmer skeleton-block"></div>
                            `);
                            $("#summary-table").html(`
                                <div class="mb-2 skeleton-shimmer skeleton-title" style="width:120px;"></div>
                                <div class="skeleton-shimmer skeleton-block" style="height:160px;"></div>
                            `);
                        }

                        function applyChartFilter() {
                            var dateRange = $('#chart_tanggal').val();
                            var dates = dateRange.split(' - ');

                            if (dates.length === 2) {
                                var startDateParts = dates[0].split('/');
                                var endDateParts = dates[1].split('/');

                                var startDateFormatted = startDateParts[2] + '-' + startDateParts[1] + '-' + startDateParts[0];
                                var endDateFormatted = endDateParts[2] + '-' + endDateParts[1] + '-' + endDateParts[0];

                                $('#chart_start_date').val(startDateFormatted);
                                $('#chart_until_date').val(endDateFormatted);

                                setUrlParams({
                                    chart_start_date: startDateFormatted,
                                    chart_until_date: endDateFormatted
                                });
                            }

                            get_chart();
                        }

                        function isValidDate(dateString) {
                            if (!/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return false;

                            var parts = dateString.split("-");
                            var year = parseInt(parts[0], 10);
                            var month = parseInt(parts[1], 10);
                            var day = parseInt(parts[2], 10);

                            if (year < 1000 || year > 3000 || month == 0 || month > 12) return false;

                            var monthLength = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

                            if (year % 400 == 0 || (year % 100 != 0 && year % 4 == 0))
                                monthLength[1] = 29;

                            return day > 0 && day <= monthLength[month - 1];
                        }

                        function syncCampaignCheckboxState(onDone) {
                            updateKenaikanVisibility();
                            var checkboxStatus = {};
                            var i = 0;
                            $(".c-checkbox").each(function() {
                                var isChecked = $(this).prop("checked");
                                checkboxStatus[i] = isChecked;
                                i++;
                            });

                            var queryParams = $.param(checkboxStatus);
                            console.log(queryParams);
                            $.ajax({
                                type: "GET",
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/checkbox?type=dashboard_campaign&' + queryParams,
                                success: function(response) {
                                    if (typeof onDone === 'function') onDone(response);
                                }
                            });
                        }

                        function checkbox() {
                            syncCampaignCheckboxState(function() {
                                get_chart({ chartOnly: true });
                            });
                        }

                        function updateKenaikanVisibility() {
                            var dailyChecked = $("#c-0").prop("checked");
                            if (dailyChecked) {
                                $("#kenaikan-only-wrap").show();
                            } else {
                                $("#kenaikan-only-wrap .c-checkbox").prop("checked", false);
                                $("#kenaikan-only-wrap").hide();
                            }
                        }

                        updateKenaikanVisibility();
                    </script>
                </div>
            </div>
        </div>
            </div>
        </div>

        <div class="modal fade" id="kolMatrixDetailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="kolMatrixDetailModalTitle">Detail Matriks</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="kolMatrixDetailModalBody"><i class="fa fa-circle-o-notch fa-spin"></i> Memuat detail ...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- <style>
        #calendar table tr th {
        font-size: 12px !important;
        max-width:unset!important;
        min-width:unset!important;
    }
    table tr:first-child th:first-child {
        font-size: 12px !important;
        max-width:unset!important;
        min-width:unset!important;
        width:unser!important;
    }
    </style>

    <h4 class="text-primary fw-500 mt-4">ENDORSEMENT CONTENTS CALENDAR</h4>
    <div class="col-lg-12 mt-3">
        <div class="row">
                <div class="text-start mb-4 col-md-12">
                <div class="card h-100">
                <div id="summary-calendar">
                    <i class="fa fa-circle-o-notch fa-spin"></i> Memuat data ...
                    </div>
                    </div>
                </div>
                <script>
                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get_summary?site=<?= $site ?>&id=calendar&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                        success: function(html) {
                            $("#summary-calendar").html(html.html);
                        }
                    });
                </script>
        </div>
    </div> -->



        <!-- <h4 class="text-primary fw-500 mt-4">LIST CUSTOMER ULANG TAHUN</h4>
    <div class="col-lg-12 mt-3">
        <div class="row">
            <div class="col-lg-3">
                <h5 class="text-primary fw-500 mt-0">HARI INI</h5>
                <div id="birthday-today"><i class="fa fa-spin fa-refresh"></i> Sedang memuat data!</div>
                <script>
                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get-birthday-list?type=today',
                        success: function(html) {
                            $("#birthday-today").html(html.html);
                        }
                    });
                </script>
            </div>
            <div class="col-lg-3">
                <h5 class="text-primary fw-500 mt-0">BESOK</h5>
                <div id="birthday-1"><i class="fa fa-spin fa-refresh"></i> Sedang memuat data!</div>
                <script>
                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get-birthday-list?type=+1',
                        success: function(html) {
                            $("#birthday-1").html(html.html);
                        }
                    });
                </script>
            </div>
            <div class="col-lg-3">
                <h5 class="text-primary fw-500 mt-0">LUSA</h5>
                <div id="birthday-2"><i class="fa fa-spin fa-refresh"></i> Sedang memuat data!</div>
                <script>
                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get-birthday-list?type=+2',
                        success: function(html) {
                            $("#birthday-2").html(html.html);
                        }
                    });
                </script>
            </div>
            <div class="col-lg-3">
                <h5 class="text-primary fw-500 mt-0">YANG AKAN DATANG</h5>
                <div id="birthday-3"><i class="fa fa-spin fa-refresh"></i> Sedang memuat data!</div>
                <script>
                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get-birthday-list?type=+3',
                        success: function(html) {
                            $("#birthday-3").html(html.html);
                        }
                    });
                </script>
            </div>
        </div>
    </div> -->


    </div>
