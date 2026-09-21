<?php
$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$until_date = !empty($_GET['until_date']) ? $_GET['until_date'] : date('Y-m-d');
$compare_mode = $compare_mode ?? 'off';
$compare_start_date = $compare_start_date ?? date('Y-m-d', strtotime($start_date . ' -1 day'));
$compare_until_date = $compare_until_date ?? date('Y-m-d', strtotime($until_date . ' -1 day'));

$summary_main = $tiktok_summary_main ?? [];
$summary_compare = $tiktok_summary_compare ?? [];
$daily_main = $tiktok_daily_main ?? [];
$daily_compare = $tiktok_daily_compare ?? [];
$tad_table = $tiktok_tad_table ?? [];
$asp_table = $tiktok_asp_table ?? [];
$asp_campaign_map = $tiktok_asp_campaign_map ?? [];
$campaign_map = $tiktok_campaign_map ?? [];
$product_breakdown = $tiktok_product_breakdown ?? [];

if (!function_exists('tt_num')) {
    function tt_num($val, $dec = 0)
    {
        $num = (float)$val;
        if (abs($num) >= 1000000) {
            $jt = $num / 1000000;
            $jt_text = number_format($jt, 1, ',', '.');
            if (substr($jt_text, -2) === ',0') {
                $jt_text = substr($jt_text, 0, -2);
            }
            return $jt_text . ' JT';
        }
        return number_format((float)$val, $dec, ',', '.');
    }
}
if (!function_exists('tt_money')) {
    function tt_money($val)
    {
        return 'Rp ' . tt_num($val, 0);
    }
}
if (!function_exists('tt_pct_change')) {
    function tt_pct_change($main, $compare)
    {
        if ((float)$compare == 0.0) return null;
        return (((float)$main - (float)$compare) / (float)$compare) * 100;
    }
}

$selected_ids = $this->input->get('ids_advertiser') ?? [];
if (!is_array($selected_ids)) {
    $selected_ids = explode(',', (string)$selected_ids);
}
$selected_ids = array_map('intval', $selected_ids);

$metric_defs = [
    'gmv' => [
        'title' => 'GMV',
        'format' => 'currency'
    ],
    'cost_spent' => [
        'title' => 'Total Spent',
        'format' => 'currency'
    ],
    'qty_purchase' => [
        'title' => 'Qty Purchase',
        'format' => 'number'
    ],
    'roas' => [
        'title' => 'ROAS/ROI',
        'format' => 'ratio'
    ],
    'cpa' => [
        'title' => 'CPA',
        'format' => 'currency'
    ]
];
?>

<style>
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

    .tt-ads-page {
        padding-bottom: 28px;
    }

    .tt-ads-page .page-title {
        font-size: 1.6rem;
        font-weight: 700;
        color: #1b2a3d;
        margin-bottom: 4px;
    }

    .tt-ads-page .page-subtitle {
        color: #5b6b7a;
        font-size: 0.95rem;
    }

    .tt-ads-page .filter-card,
    .tt-ads-page .section-card {
        background: #fff;
        border-radius: 16px;
        padding: 16px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        border: 1px solid #e8eef4;
    }

    .tt-ads-page .filter-label {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #8b9ab0;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .tt-ads-page .compare-box {
        background: #f8fbfd;
        border: 1px solid #e3ebf3;
        border-radius: 12px;
        padding: 10px 12px;
    }

    .tt-ads-page .toggle-switch {
        position: relative;
        width: 54px;
        height: 28px;
        border-radius: 999px;
        background: #dbe7f1;
        cursor: pointer;
        display: inline-block;
    }

    .tt-ads-page .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .tt-ads-page .toggle-switch .knob {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.2);
        transition: transform .2s ease;
    }

    .tt-ads-page .toggle-switch input:checked + .knob {
        transform: translateX(26px);
    }

    .tt-ads-page .toggle-switch .bg {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        z-index: -1;
        transition: background .2s ease;
        background: #dbe7f1;
    }

    .tt-ads-page .toggle-switch input:checked ~ .bg {
        background: #0f8b8d;
    }

    .tt-ads-page .summary-grid {
        display: flex;
        flex-wrap: nowrap;
        gap: 10px;
        margin-top: 14px;
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: 6px;
        -webkit-overflow-scrolling: touch;
        scroll-snap-type: x proximity;
    }

    .tt-ads-page .metric-card {
        border-radius: 16px;
        background: #fff;
        padding: 12px;
        border: 2px solid #e8eef4;
        box-shadow: 0 8px 20px rgba(12, 30, 58, 0.06);
        transition: all .2s ease;
        cursor: pointer;
        min-width: 215px;
        max-width: 215px;
        flex: 0 0 215px;
        scroll-snap-align: start;
    }

    .tt-ads-page .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 26px rgba(12, 30, 58, 0.1);
    }

    .tt-ads-page .metric-card.is-active {
        border-color: #0f8b8d;
        box-shadow: 0 14px 30px rgba(15, 139, 141, 0.2);
    }

    .tt-ads-page .metric-title {
        font-size: 0.86rem;
        font-weight: 700;
        color: #1b2a3d;
        margin-bottom: 4px;
        line-height: 1.35;
    }
    
    .tt-ads-page .metric-value {
        font-size: 1.22rem;
        font-weight: 700;
        color: #0f172a;
        margin-top: 6px;
    }

    .tt-ads-page .metric-compare {
        font-size: 0.76rem;
        color: #64748b;
        margin-top: 2px;
    }

    .tt-ads-page .metric-change {
        margin-top: 5px;
        font-size: 0.76rem;
        font-weight: 700;
    }

    .tt-ads-page .metric-change.up { color: #16a34a; }
    .tt-ads-page .metric-change.down { color: #ef4444; }

    .tt-ads-page .metric-actions {
        margin-top: 8px;
    }

    .tt-ads-page .detail-btn {
        border: 1px solid #bde8e8;
        background: #e6f6f6;
        color: #0f8b8d;
        border-radius: 999px;
        padding: 4px 9px;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .tt-ads-page .detail-btn:hover {
        background: #d6f0f0;
        color: #0f7e80;
    }

    .tt-ads-page .chart-card {
        margin-top: 16px;
        background: #fff;
        border-radius: 18px;
        padding: 16px;
        box-shadow: 0 10px 28px rgba(12, 30, 58, 0.08);
        border: 1px solid #e8eef4;
    }

    .tt-ads-page .chart-title {
        font-size: 1.08rem;
        font-weight: 700;
        color: #1b2a3d;
        margin-bottom: 2px;
    }

    .tt-ads-page .chart-subtitle {
        color: #7b8ca4;
        font-size: .85rem;
        margin-bottom: 10px;
    }

    .tt-ads-page .chart-wrap {
        height: 360px;
    }

    .tt-ads-page .table-card {
        margin-top: 16px;
    }

    .tt-ads-page .account-row-main {
        background: #fbfdff;
    }

    .tt-ads-page .expand-campaign-btn {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        border: 1px solid #d7e1ec;
        background: #fff;
        color: #4b5d73;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        line-height: 1;
    }

    .tt-ads-page .expand-campaign-btn:hover {
        background: #f3f7fb;
        color: #1f3b5c;
        border-color: #c8d7e6;
    }

    .tt-ads-page tr.collapse > td {
        padding: 8px 10px;
        background: #fbfdff;
    }

    .tt-ads-page .campaign-subtable-wrap {
        background: #fff;
        border: 1px solid #e4ebf3;
        border-radius: 10px;
        padding: 8px;
    }

    .tt-ads-page .campaign-subtable th,
    .tt-ads-page .campaign-subtable td {
        font-size: 0.9rem;
        padding: 5px 6px;
        vertical-align: middle;
    }

    .tt-ads-page .campaign-subtable thead th {
        font-size: 0.72rem;
        font-weight: 700;
        color: #475569;
        background: #f8fafc;
    }

    .tt-ads-page .campaign-subtable tbody tr td {
        background: #fff;
    }

    .tt-ads-page .breakdown-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 14px;
    }

    .tt-ads-page .donut-card {
        border: 1px solid #e5edf5;
        border-radius: 14px;
        padding: 12px;
        background: #fff;
    }

    .tt-ads-page .donut-title {
        font-size: 0.92rem;
        font-weight: 700;
        color: #1b2a3d;
        margin-bottom: 8px;
    }

    .tt-ads-page .donut-wrap {
        height: 240px;
    }

    .select2-container .select2-selection--multiple {
        min-height: 45px;
        border-radius: 0.5rem !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        padding: 6px;
    }

    .select2-container .select2-search--inline .select2-search__field {
        padding-top: 6px !important;
    }

    .tt-ads-page .endorse-data-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        color: #64748b;
        font-weight: 600;
        background: #f8fafc;
        margin-right: 6px;
    }

    .tt-ads-page .endorse-data-tabs .nav-link:hover {
        color: #0f8b8d;
        background: #eef7f7;
    }

    .tt-ads-page .endorse-data-tabs .nav-link.active {
        color: #0f8b8d;
        background: #ffffff;
        border-bottom-color: #0f8b8d;
    }
</style>

<div class="w-100 tt-ads-page">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-2">
            <div class="page-title">Dashboard Tiktok Ads</div>
            <div class="page-subtitle">Performa ads GMV Max + Ads Campaign.</div>
        </div>

        <div class="col-lg-12 mb-3">
            <div class="filter-card">
                <form action="<?= $url ?>?m=tiktok" method="GET" id="ttFilterForm">
                    <input type="hidden" name="m" value="tiktok">
                    <input type="hidden" name="compare_mode" id="compare_mode" value="<?= htmlspecialchars($compare_mode) ?>">
                    <input type="hidden" name="compare_start_date" id="compare_start_date" value="<?= htmlspecialchars($compare_start_date) ?>">
                    <input type="hidden" name="compare_until_date" id="compare_until_date" value="<?= htmlspecialchars($compare_until_date) ?>">

                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <div class="filter-label">Advertiser</div>
                            <select class="form-control select2" name="ids_advertiser[]" id="advertiser" multiple>
                                <?php foreach (($advertiser ?? []) as $val) :
                                    $selected = in_array((int)$val['id'], $selected_ids, true) ? 'selected' : '';
                                ?>
                                    <option <?= $selected ?> value="<?= (int)$val['id'] ?>"><?= htmlspecialchars($val['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <div class="filter-label">Periode Utama</div>
                            <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                            <input type="hidden" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>">
                            <input type="hidden" name="until_date" id="end_date" value="<?= htmlspecialchars($until_date) ?>">
                        </div>

                        <div class="col-md-3">
                            <div class="filter-label">Bandingkan (VS)</div>
                            <div class="compare-box">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <label class="toggle-switch mb-0">
                                        <input type="checkbox" id="compareToggle" <?= $compare_mode === 'on' ? 'checked' : '' ?>>
                                        <span class="knob"></span>
                                        <span class="bg"></span>
                                    </label>
                                    <span class="small text-muted" id="compareStateLabel"><?= $compare_mode === 'on' ? 'ON' : 'OFF' ?></span>
                                </div>
                                <div id="comparePickerWrap" style="<?= $compare_mode === 'on' ? '' : 'display:none;' ?>">
                                    <input type="text" class="form-control" id="tanggal_compare" placeholder="Pilih rentang perbandingan...">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Terapkan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="summary-grid">
                <?php foreach ($metric_defs as $metric_key => $meta) :
                    $main_val = (float)($summary_main[$metric_key] ?? 0);
                    $compare_val = (float)($summary_compare[$metric_key] ?? 0);
                    $change = tt_pct_change($main_val, $compare_val);
                    $change_class = ($change !== null && $change >= 0) ? 'up' : 'down';

                    if ($meta['format'] === 'currency') {
                        $main_text = tt_money($main_val);
                        $compare_text = tt_money($compare_val);
                    } else if ($meta['format'] === 'ratio') {
                        $main_text = tt_num($main_val, 2);
                        $compare_text = tt_num($compare_val, 2);
                    } else {
                        $main_text = tt_num($main_val, 0);
                        $compare_text = tt_num($compare_val, 0);
                    }
                ?>
                    <div class="metric-card <?= $metric_key === 'gmv' ? 'is-active' : '' ?>" data-metric="<?= $metric_key ?>">
                        <div class="metric-title"><?= $meta['title'] ?></div>
                        <div class="metric-value"><?= $main_text ?></div>
                        <div class="metric-compare">Periode 1: <?= $compare_text ?></div>
                        <div class="metric-change <?= $change_class ?>">
                            <?php if ($change === null) : ?>N/A vs Periode 1<?php else : ?><?= tt_num(abs($change), 2) ?>% vs Periode 1<?php endif; ?>
                        </div>
                        <div class="metric-actions">
                            <button type="button" class="detail-btn js-metric-detail" data-metric="<?= $metric_key ?>">Lihat Detail</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="chart-card">
                <div class="chart-title" id="ttChartTitle">Trend GMV</div>
                <div class="chart-subtitle">Klik card metrik untuk memilih maksimal 2 metrik. Bandingkan akan tampil jika mode VS aktif.</div>
                <div class="chart-wrap">
                    <canvas id="ttOverviewChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-12 table-card">
            <div class="section-card">
                <ul class="nav nav-tabs endorse-data-tabs mb-3" id="ttDataTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tt-gmv-max-tab" data-bs-toggle="tab" data-bs-target="#tt-gmv-max-panel" type="button" role="tab" aria-controls="tt-gmv-max-panel" aria-selected="true">Tabel GMV Max</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tt-ads-data-tab" data-bs-toggle="tab" data-bs-target="#tt-ads-data-panel" type="button" role="tab" aria-controls="tt-ads-data-panel" aria-selected="false">Tabel TikTok Ads Data</button>
                    </li>
                </ul>
                <div class="tab-content" id="ttDataTabContent">
                    <div class="tab-pane fade show active" id="tt-gmv-max-panel" role="tabpanel" aria-labelledby="tt-gmv-max-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Account</th>
                                        <th class="text-end">Spend After Tax</th>
                                        <th class="text-end">Orders</th>
                                        <th class="text-end">Cost / Order</th>
                                        <th class="text-end">Gross Revenue</th>
                                        <th class="text-end">ROI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($asp_table)) : ?>
                                        <tr><td colspan="8" class="text-center text-muted">Tidak ada data.</td></tr>
                                    <?php else : ?>
                                        <?php foreach ($asp_table as $row) : ?>
                                            <?php
                                            $asp_adv_id = (string)($row['advertiser_id'] ?? '');
                                            $asp_rows = $asp_campaign_map[$asp_adv_id] ?? [];
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['start_date_ref']) ?> - <?= htmlspecialchars($row['end_date_ref']) ?></td>
                                                <td>
                                                    <div class="fw-700"><?= htmlspecialchars($row['account']) ?></div>
                                                    <div class="small text-muted">
                                                        <a href="#!" class="js-open-asp-campaign-modal" data-adv-id="<?= (int)$asp_adv_id ?>">Lihat Campaign (<?= count($asp_rows) ?>)</a>
                                                    </div>
                                                </td>
                                                <td class="text-end"><?= tt_money($row['spend_after_tax']) ?></td>
                                                <td class="text-end"><?= tt_num($row['orders']) ?></td>
                                                <td class="text-end"><?= tt_money($row['cost_per_order']) ?></td>
                                                <td class="text-end"><?= tt_money($row['gross_revenue']) ?></td>
                                                <td class="text-end"><?= tt_num($row['roi'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tt-ads-data-panel" role="tabpanel" aria-labelledby="tt-ads-data-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Account</th>
                                        <th class="text-end">Spend IDR</th>
                                        <th class="text-end">Clicks</th>
                                        <th class="text-end">Onsite Add to Cart</th>
                                        <th class="text-end">Total Onsite Add to Cart</th>
                                        <th class="text-end">Onsite Shopping</th>
                                        <th class="text-end">Gross Revenue</th>
                                        <th class="text-end">Frequency</th>
                                        <th class="text-end">Reach</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tad_table)) : ?>
                                        <tr><td colspan="10" class="text-center text-muted">Tidak ada data.</td></tr>
                                    <?php else : ?>
                                        <?php
                                        $gt_spend = 0;
                                        $gt_clicks = 0;
                                        $gt_atc = 0;
                                        $gt_total_atc = 0;
                                        $gt_shop = 0;
                                        $gt_gross = 0;
                                        $gt_reach = 0;
                                        $gt_freq_sum = 0;
                                        $gt_freq_count = 0;
                                        ?>
                                        <?php foreach ($tad_table as $idx => $row) :
                                            $adv_id = (int)$row['advertiser_id'];
                                            $rows = $campaign_map[(string)$adv_id] ?? [];
                                            $gt_spend += (float)$row['spend_idr'];
                                            $gt_clicks += (float)$row['clicks'];
                                            $gt_atc += (float)$row['onsite_add_to_cart'];
                                            $gt_total_atc += (float)$row['total_onsite_add_to_cart'];
                                            $gt_shop += (float)$row['onsite_shopping'];
                                            $gt_gross += (float)$row['gross_revenue'];
                                            $gt_reach += (float)$row['reach'];
                                            $gt_freq_sum += (float)$row['frequency'];
                                            $gt_freq_count++;
                                        ?>
                                            <tr class="account-row-main">
                                                <td><?= htmlspecialchars($row['start_date_ref']) ?> - <?= htmlspecialchars($row['end_date_ref']) ?></td>
                                                <td>
                                                    <div class="fw-700"><?= htmlspecialchars($row['account']) ?></div>
                                                    <div class="small text-muted">
                                                        <a href="#!" class="js-open-campaign-modal" data-adv-id="<?= $adv_id ?>">Lihat Campaign (<?= count($rows) ?>)</a>
                                                    </div>
                                                </td>
                                                <td class="text-end"><?= tt_money($row['spend_idr']) ?></td>
                                                <td class="text-end"><?= tt_num($row['clicks']) ?></td>
                                                <td class="text-end"><?= tt_num($row['onsite_add_to_cart']) ?></td>
                                                <td class="text-end"><?= tt_money($row['total_onsite_add_to_cart']) ?></td>
                                                <td class="text-end"><?= tt_num($row['onsite_shopping']) ?></td>
                                                <td class="text-end"><?= tt_money($row['gross_revenue']) ?></td>
                                                <td class="text-end"><?= tt_num($row['frequency'], 2) ?></td>
                                                <td class="text-end"><?= tt_num($row['reach']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="fw-700 bg-light">
                                            <td>Grand Total</td>
                                            <td></td>
                                            <td class="text-end"><?= tt_money($gt_spend) ?></td>
                                            <td class="text-end"><?= tt_num($gt_clicks) ?></td>
                                            <td class="text-end"><?= tt_num($gt_atc) ?></td>
                                            <td class="text-end"><?= tt_money($gt_total_atc) ?></td>
                                            <td class="text-end"><?= tt_num($gt_shop) ?></td>
                                            <td class="text-end"><?= tt_money($gt_gross) ?></td>
                                            <td class="text-end"><?= tt_num($gt_freq_count > 0 ? ($gt_freq_sum / $gt_freq_count) : 0, 2) ?></td>
                                            <td class="text-end"><?= tt_num($gt_reach) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12 mt-3">
            <div class="section-card">
                <div class="chart-title">Breakdown Subdata Performa Ads</div>
                <div class="chart-subtitle">Spent ads based on produk, GMV ads based on produk, dan Qty based on produk.</div>
                <div class="breakdown-grid">
                    <div class="donut-card">
                        <div class="donut-title">Spent Ads per Produk</div>
                        <div class="donut-wrap"><canvas id="donutSpent"></canvas></div>
                    </div>
                    <div class="donut-card">
                        <div class="donut-title">GMV Ads per Produk</div>
                        <div class="donut-wrap"><canvas id="donutGmv"></canvas></div>
                    </div>
                    <div class="donut-card">
                        <div class="donut-title">Qty Purchase per Produk</div>
                        <div class="donut-wrap"><canvas id="donutQty"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ttMetricDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ttMetricDetailTitle">Detail Metric</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="ttMetricDetailBody"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="ttCampaignDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ttCampaignDetailTitle">Detail Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="ttCampaignDetailBody"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="ttAspCampaignDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ttAspCampaignDetailTitle">Detail Campaign GMV Max</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="ttAspCampaignDetailBody"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const state = {
        compareMode: <?= json_encode($compare_mode) ?>,
        metrics: <?= json_encode($metric_defs) ?>,
        summaryMain: <?= json_encode($summary_main) ?>,
        summaryCompare: <?= json_encode($summary_compare) ?>,
        dailyMain: <?= json_encode($daily_main) ?>,
        dailyCompare: <?= json_encode($daily_compare) ?>,
        aspCampaignMap: <?= json_encode($asp_campaign_map) ?>,
        campaignMap: <?= json_encode($campaign_map) ?>,
        breakdown: <?= json_encode($product_breakdown) ?>,
        activeMetrics: ['gmv', 'cost_spent']
    };

    const colors = ['#0f8b8d', '#1d4ed8', '#ea580c', '#9333ea', '#059669', '#dc2626', '#ca8a04'];
    const metricColors = {
        gmv: '#0f8b8d',
        cost_spent: '#1d4ed8',
        qty_purchase: '#ea580c',
        roas: '#9333ea',
        cpa: '#059669'
    };

    function hexToRgba(hex, alpha) {
        if (!hex || typeof hex !== 'string') return `rgba(15,139,141,${alpha})`;
        const h = hex.replace('#', '');
        const safe = h.length === 3 ? h.split('').map(ch => ch + ch).join('') : h;
        const num = parseInt(safe, 16);
        if (Number.isNaN(num)) return `rgba(15,139,141,${alpha})`;
        const r = (num >> 16) & 255;
        const g = (num >> 8) & 255;
        const b = num & 255;
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    function fmtNumber(v, dec = 0) {
        const num = Number(v || 0);
        if (Math.abs(num) >= 1000000) {
            const jt = num / 1000000;
            const fixed = (Math.round(jt * 10) / 10).toFixed(1);
            const text = fixed.endsWith('.0')
                ? new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(fixed))
                : new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(Number(fixed));
            return `${text} JT`;
        }
        return new Intl.NumberFormat('id-ID', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(num);
    }

    function fmtValue(metric, value) {
        if (metric === 'roas') return fmtNumber(value, 2);
        if (metric === 'qty_purchase') return fmtNumber(value, 0);
        return `Rp ${fmtNumber(value, 0)}`;
    }

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

    function initCompareDatePicker() {
        if (typeof $.fn.daterangepicker !== 'function') return;
        const compStart = moment($('#compare_start_date').val(), 'YYYY-MM-DD');
        const compEnd = moment($('#compare_until_date').val(), 'YYYY-MM-DD');
        $('#tanggal_compare').daterangepicker({
            startDate: compStart,
            endDate: compEnd,
            autoUpdateInput: true,
            locale: { format: 'DD/MM/YYYY' }
        }, function(start, end) {
            $('#compare_start_date').val(start.format('YYYY-MM-DD'));
            $('#compare_until_date').val(end.format('YYYY-MM-DD'));
        });
    }

    function initCompareToggle() {
        const toggle = document.getElementById('compareToggle');
        const wrap = document.getElementById('comparePickerWrap');
        const label = document.getElementById('compareStateLabel');
        const modeInput = document.getElementById('compare_mode');
        if (!toggle) return;

        toggle.addEventListener('change', function() {
            const on = this.checked;
            modeInput.value = on ? 'on' : 'off';
            label.textContent = on ? 'ON' : 'OFF';
            wrap.style.display = on ? '' : 'none';
        });
    }

    let overviewChart = null;
    function renderOverviewChart() {
        const canvas = document.getElementById('ttOverviewChart');
        if (!canvas || typeof Chart === 'undefined') return;
        const ctx = canvas.getContext('2d');
        const gradientHeight = canvas.height || 320;

        const activeList = (state.activeMetrics || []).slice(0, 2);
        const title = activeList.map(function(metricKey) {
            return state.metrics[metricKey] ? state.metrics[metricKey].title : metricKey;
        }).join(' & ') || 'Metric';
        document.getElementById('ttChartTitle').textContent = `Trend ${title}`;

        const labels = state.dailyMain.map(r => {
            const m = moment(r.date, 'YYYY-MM-DD');
            return m.isValid() ? m.format('D MMM') : r.date;
        });
        const datasets = [];
        activeList.forEach(function(metricKey, idx) {
            const baseColor = metricColors[metricKey] || colors[idx % colors.length];
            const metricTitle = state.metrics[metricKey] ? state.metrics[metricKey].title : metricKey;
            const axisId = idx === 0 ? 'y' : 'y1';
            const mainValues = state.dailyMain.map(r => Number(r[metricKey] || 0));
            const compareValues = state.dailyCompare.map(r => Number(r[metricKey] || 0));
            const fillGradient = ctx.createLinearGradient(0, 0, 0, gradientHeight);
            fillGradient.addColorStop(0, hexToRgba(baseColor, 0.28));
            fillGradient.addColorStop(1, hexToRgba(baseColor, 0.02));

            datasets.push({
                label: `${metricTitle} (Utama)`,
                data: mainValues,
                borderColor: baseColor,
                backgroundColor: fillGradient,
                fill: true,
                tension: 0.35,
                borderWidth: 2,
                pointRadius: 0,
                yAxisID: axisId,
                metricKey: metricKey
            });

            if (state.compareMode === 'on') {
                datasets.push({
                    label: `${metricTitle} (Banding)`,
                    data: compareValues,
                    borderColor: baseColor,
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.35,
                    borderWidth: 2,
                    borderDash: [6, 6],
                    pointRadius: 0,
                    yAxisID: axisId,
                    metricKey: metricKey
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
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const metricKey = context.dataset.metricKey || activeList[0] || 'gmv';
                                return `${context.dataset.label}: ${fmtValue(metricKey, context.raw)}`;
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(v) {
                                const key = activeList[0] || 'gmv';
                                if (key === 'roas') return fmtNumber(v, 2);
                                if (key === 'qty_purchase') return fmtNumber(v, 0);
                                return fmtNumber(v, 0);
                            }
                        }
                    },
                    y1: {
                        display: activeList.length > 1,
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: {
                            callback: function(v) {
                                const key = activeList[1] || activeList[0] || 'gmv';
                                if (key === 'roas') return fmtNumber(v, 2);
                                if (key === 'qty_purchase') return fmtNumber(v, 0);
                                return fmtNumber(v, 0);
                            }
                        }
                    }
                }
            }
        });
    }

    function syncMetricCardActiveState() {
        const activeSet = new Set(state.activeMetrics || []);
        document.querySelectorAll('.metric-card[data-metric]').forEach(function(c) {
            c.classList.toggle('is-active', activeSet.has(c.getAttribute('data-metric')));
        });
    }

    function initMetricCards() {
        document.querySelectorAll('.metric-card[data-metric]').forEach(function(card) {
            card.addEventListener('click', function(e) {
                if (e.target.closest('.js-metric-detail')) return;
                const metric = this.getAttribute('data-metric');
                if (!metric) return;

                const list = Array.isArray(state.activeMetrics) ? state.activeMetrics.slice() : [];
                const idx = list.indexOf(metric);
                if (idx >= 0) {
                    if (list.length > 1) list.splice(idx, 1);
                } else {
                    if (list.length >= 2) list.shift();
                    list.push(metric);
                }

                state.activeMetrics = list;
                syncMetricCardActiveState();
                renderOverviewChart();
            });
        });
    }

    function buildFormula(metric) {
        if (metric === 'gmv') return 'GMV = SUM(gross_revenue) + SUM(total_onsite_shopping_value)';
        if (metric === 'cost_spent') return 'Total Cost = SUM(cost_after_tax) + SUM(spend_idr_after_tax)';
        if (metric === 'qty_purchase') return 'Qty = SUM(orders) + SUM(onsite_shopping)';
        if (metric === 'roas') return 'ROAS = (SUM(gross_revenue) + SUM(total_onsite_shopping_value)) ÷ (SUM(net_cost) + SUM(spend_idr_after_tax))';
        if (metric === 'cpa') return 'CPA = (SUM(net_cost) + SUM(spend_idr_after_tax)) ÷ (SUM(orders) + SUM(onsite_shopping))';
        return '';
    }

    function buildDetailRows(metric, gmvComp, costComp, netComp, qtyComp) {
        const rowGMV = `<tr><td>GMV</td><td class="text-end">Rp ${fmtNumber(gmvComp.asp, 0)}</td><td class="text-end">Rp ${fmtNumber(gmvComp.tad, 0)}</td><td class="text-end">Rp ${fmtNumber(gmvComp.asp + gmvComp.tad, 0)}</td></tr>`;
        const rowSpend = `<tr><td>Cost Spent (+Tax)</td><td class="text-end">Rp ${fmtNumber(costComp.asp, 0)}</td><td class="text-end">Rp ${fmtNumber(costComp.tad, 0)}</td><td class="text-end">Rp ${fmtNumber(costComp.asp + costComp.tad, 0)}</td></tr>`;
        const rowNet = `<tr><td>Net Cost</td><td class="text-end">Rp ${fmtNumber(netComp.asp, 0)}</td><td class="text-end">Rp ${fmtNumber(netComp.tad, 0)}</td><td class="text-end">Rp ${fmtNumber(netComp.asp + netComp.tad, 0)}</td></tr>`;
        const rowQty = `<tr><td>Qty Purchase</td><td class="text-end">${fmtNumber(qtyComp.asp, 0)}</td><td class="text-end">${fmtNumber(qtyComp.tad, 0)}</td><td class="text-end">${fmtNumber(qtyComp.asp + qtyComp.tad, 0)}</td></tr>`;

        if (metric === 'gmv') return rowGMV;
        if (metric === 'cost_spent') return rowSpend;
        if (metric === 'qty_purchase') return rowQty;
        if (metric === 'roas') return `${rowGMV}${rowNet}`;
        if (metric === 'cpa') return `${rowNet}${rowQty}`;
        return `${rowGMV}${rowSpend}${rowNet}${rowQty}`;
    }

    function initMetricDetailModal() {
        const modalEl = document.getElementById('ttMetricDetailModal');
        if (!modalEl) return;
        const modal = new bootstrap.Modal(modalEl);

        document.querySelectorAll('.js-metric-detail').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const metric = this.getAttribute('data-metric');
                const title = state.metrics[metric] ? state.metrics[metric].title : 'Detail Metric';
                const mainVal = Number(state.summaryMain[metric] || 0);
                const compareVal = Number(state.summaryCompare[metric] || 0);
                const comps = state.summaryMain.components || {};

                const gmvComp = comps.gmv || { asp: 0, tad: 0 };
                const costComp = comps.cost_spent || { asp: 0, tad: 0 };
                const netComp = comps.net_cost || { asp: 0, tad: 0 };
                const qtyComp = comps.qty_purchase || { asp: 0, tad: 0 };

                document.getElementById('ttMetricDetailTitle').textContent = title;
                document.getElementById('ttMetricDetailBody').innerHTML = `
                    <div class="mb-3"><strong>Nilai Periode Utama:</strong> ${fmtValue(metric, mainVal)}<br><strong>Nilai Periode Banding:</strong> ${fmtValue(metric, compareVal)}</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="bg-light">
                                <tr><th>Komponen</th><th class="text-end">GMV Max</th><th class="text-end">TikTok Ads Data</th><th class="text-end">Total</th></tr>
                            </thead>
                            <tbody>
                                ${buildDetailRows(metric, gmvComp, costComp, netComp, qtyComp)}
                            </tbody>
                        </table>
                    </div>
                `;
                modal.show();
            });
        });
    }

    function initCampaignDetailModal() {
        const modalEl = document.getElementById('ttCampaignDetailModal');
        if (!modalEl) return;
        const modal = new bootstrap.Modal(modalEl);

        $(document).on('click', '.js-open-campaign-modal', function(e) {
            e.preventDefault();
            const advId = String($(this).data('adv-id') || '').trim();
            const rows = (state.campaignMap && state.campaignMap[advId]) ? state.campaignMap[advId] : [];
            document.getElementById('ttCampaignDetailTitle').textContent = `Detail Campaign`;

            if (!rows.length) {
                document.getElementById('ttCampaignDetailBody').innerHTML = '<div class="text-muted">Tidak ada data campaign.</div>';
                modal.show();
                return;
            }

            const bodyRows = rows.map(function(r) {
                return `
                    <tr>
                        <td>${r.start_date_ref || '-'} - ${r.end_date_ref || '-'}</td>
                        <td>${r.campaign_name || '-'}</td>
                        <td class="text-end">${fmtNumber(r.impressions || 0, 0)}</td>
                        <td class="text-end">${fmtNumber(r.clicks || 0, 0)}</td>
                        <td class="text-end">${fmtNumber(r.ctr || 0, 2)}%</td>
                        <td class="text-end">Rp ${fmtNumber(r.cpc || 0, 0)}</td>
                        <td class="text-end">Rp ${fmtNumber(r.cpm || 0, 0)}</td>
                        <td class="text-end">${fmtNumber(r.reach || 0, 0)}</td>
                        <td class="text-end">${fmtNumber(r.frequency || 0, 2)}</td>
                        <td class="text-end">Rp ${fmtNumber(r.gmv || 0, 0)}</td>
                        <td class="text-end">Rp ${fmtNumber(r.cost_spent || 0, 0)}</td>
                        <td class="text-end">${fmtNumber(r.qty_purchase || 0, 0)}</td>
                        <td class="text-end">${fmtNumber(r.roas || 0, 2)}</td>
                        <td class="text-end">Rp ${fmtNumber(r.cpa || 0, 0)}</td>
                    </tr>
                `;
            }).join('');

            document.getElementById('ttCampaignDetailBody').innerHTML = `
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Date</th>
                                <th>Campaign</th>
                                <th class="text-end">Impressions</th>
                                <th class="text-end">Clicks</th>
                                <th class="text-end">CTR</th>
                                <th class="text-end">CPC</th>
                                <th class="text-end">CPM</th>
                                <th class="text-end">Reach</th>
                                <th class="text-end">Frequency</th>
                                <th class="text-end">GMV</th>
                                <th class="text-end">Cost Spent</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">ROAS</th>
                                <th class="text-end">CPA</th>
                            </tr>
                        </thead>
                        <tbody>${bodyRows}</tbody>
                    </table>
                </div>
            `;
            modal.show();
        });
    }

    function initAspCampaignDetailModal() {
        const modalEl = document.getElementById('ttAspCampaignDetailModal');
        if (!modalEl) return;
        const modal = new bootstrap.Modal(modalEl);

        $(document).on('click', '.js-open-asp-campaign-modal', function(e) {
            e.preventDefault();
            const advId = String($(this).data('adv-id') || '').trim();
            const rows = (state.aspCampaignMap && state.aspCampaignMap[advId]) ? state.aspCampaignMap[advId] : [];
            document.getElementById('ttAspCampaignDetailTitle').textContent = `Detail Campaign GMV Max`;

            if (!rows.length) {
                document.getElementById('ttAspCampaignDetailBody').innerHTML = '<div class="text-muted">Tidak ada data campaign.</div>';
                modal.show();
                return;
            }

            const bodyRows = rows.map(function(r) {
                return `
                    <tr>
                        <td>${r.start_date_ref || '-'} - ${r.end_date_ref || '-'}</td>
                        <td>${r.campaign_id || '-'}</td>
                        <td>${r.campaign_name || '-'}</td>
                        <td>${r.operation_status || '-'}</td>
                        <td>${r.schedule_type || '-'}</td>
                        <td class="text-end">Rp ${fmtNumber(r.cost_spent || 0, 0)}</td>
                        <td class="text-end">${fmtNumber(r.qty_purchase || 0, 0)}</td>
                        <td class="text-end">Rp ${fmtNumber(r.cpo || 0, 0)}</td>
                        <td class="text-end">Rp ${fmtNumber(r.gmv || 0, 0)}</td>
                        <td class="text-end">${fmtNumber(r.roi || 0, 2)}</td>
                    </tr>
                `;
            }).join('');

            document.getElementById('ttAspCampaignDetailBody').innerHTML = `
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Date</th>
                                <th>Campaign ID</th>
                                <th>Campaign</th>
                                <th>Status</th>
                                <th>Schedule</th>
                                <th class="text-end">Cost Spent</th>
                                <th class="text-end">Orders</th>
                                <th class="text-end">Cost / Order</th>
                                <th class="text-end">Gross Revenue</th>
                                <th class="text-end">ROI</th>
                            </tr>
                        </thead>
                        <tbody>${bodyRows}</tbody>
                    </table>
                </div>
            `;
            modal.show();
        });
    }

    function renderDonut(id, field, titlePrefix) {
        const canvas = document.getElementById(id);
        if (!canvas || typeof Chart === 'undefined') return;
        const labels = [];
        const values = [];
        state.breakdown.forEach(function(row) {
            labels.push(row.product_name || 'Other');
            values.push(Number(row[field] || 0));
        });

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const val = Number(context.raw || 0);
                                if (field === 'qty') return `${context.label}: ${fmtNumber(val, 0)}`;
                                return `${context.label}: Rp ${fmtNumber(val, 0)}`;
                            }
                        }
                    }
                }
            }
        });
    }

    $(function() {
        if ($.fn.select2) {
            $('#advertiser').select2({ width: '100%', placeholder: 'Pilih advertiser' });
        }

        get_filter();
        initCompareDatePicker();
        initCompareToggle();
        initMetricCards();
        syncMetricCardActiveState();
        initMetricDetailModal();
        initCampaignDetailModal();
        initAspCampaignDetailModal();
        renderOverviewChart();
        renderDonut('donutSpent', 'spent');
        renderDonut('donutGmv', 'gmv');
        renderDonut('donutQty', 'qty');
    });
})();
</script>
