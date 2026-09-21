<?php
$chart_title = "";
$site = $_GET['site'];
$customer = $_GET['customer'];
$date = $_GET['date'];

// if ($_GET['start_date'] == "") {
//     $start_date = DATE("Y-m-01");
// } else {
//     $start_date = $_GET['start_date'];
// }
if ($_GET['until_date'] == "") {
    $until_date = DATE("Y-m-d");
} else {
    $until_date = $_GET['until_date'];
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

$only_increase = false;
$checkbox_campaign = $_SESSION['checkbox'] ?? ($_SESSION['checkbox_dashboard_campaign'] ?? null);
if ($checkbox_campaign) {
    if (isset($checkbox_campaign[8])) {
        $only_increase = ($checkbox_campaign[8] === true || $checkbox_campaign[8] === 'true' || $checkbox_campaign[8] === 1 || $checkbox_campaign[8] === '1' || $checkbox_campaign[8] === 'on');
    } else if (isset($checkbox_campaign[6]) && !isset($checkbox_campaign[7])) {
        $only_increase = ($checkbox_campaign[6] === true || $checkbox_campaign[6] === 'true' || $checkbox_campaign[6] === 1 || $checkbox_campaign[6] === '1' || $checkbox_campaign[6] === 'on');
    }
}

// Hitung selisih views dan sort data untuk mendapatkan 5 tertinggi
$data_with_diff = array();
foreach ($data as $k => $v) {
    $v['views_before'] = isset($v['views_before']) ? (float)$v['views_before'] : 0;
    $v['views_after'] = isset($v['views_after']) ? (float)$v['views_after'] : 0;
    $v['views_diff'] = isset($v['views_diff']) ? (float)$v['views_diff'] : ($v['views_after'] - $v['views_before']);
    $v['engagement_before'] = isset($v['engagement_before']) ? (float)$v['engagement_before'] : ((float)$v['likes_before'] + (float)$v['comment_before'] + (float)$v['share_save_before']);
    $v['engagement_after'] = isset($v['engagement_after']) ? (float)$v['engagement_after'] : ((float)$v['likes_after'] + (float)$v['comment_after'] + (float)$v['share_save_after']);
    $v['engagement_diff'] = isset($v['engagement_diff']) ? (float)$v['engagement_diff'] : ($v['engagement_after'] - $v['engagement_before']);
    if ($only_increase) {
        if ($v['views_diff'] < 0) $v['views_diff'] = 0;
        if ($v['engagement_diff'] < 0) $v['engagement_diff'] = 0;
    }
    $data_with_diff[] = $v;
}
$totals = isset($totals) ? $totals : array();

// Sort berdasarkan selisih views tertinggi
usort($data_with_diff, function($a, $b) {
    return $b['views_diff'] - $a['views_diff'];
});

// Ambil 5 tertinggi
$top_5_data = array_slice($data_with_diff, 0, 5);

$sort_column = isset($sort_column) ? $sort_column : 'id';
$sort_order = isset($sort_order) ? strtoupper($sort_order) : 'DESC';
$build_sort_url = function($column) use ($sort_column, $sort_order) {
    $params = $_GET;
    $params['sort_column'] = $column;
    $params['sort_order'] = ($sort_column === $column && strtoupper($sort_order) === 'ASC') ? 'DESC' : 'ASC';
    $params['page'] = 1; // reset to first page on new sort
    return base_url() . 'endorse/logs?' . http_build_query($params);
};
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
        height: 56px;
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

    .thead-loading {
        position: relative;
    }
    .thead-loading::after {
        content: '';
        display: none;
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.7) 50%, rgba(255,255,255,0) 100%);
        z-index: 2;
        animation: shimmer 1s linear infinite;
    }
    .thead-loading.loading::after {
        display: block;
    }
    .spinner {
        display: none;
        position: absolute;
        inset: 0;
        z-index: 3;
        align-items: center;
        justify-content: center;
    }
    .thead-loading.loading .spinner {
        display: flex;
    }
    .spinner-border {
        width: 20px;
        height: 20px;
        border-width: 2px;
    }
    @keyframes shimmer {
        0% { background-position: -200px 0; }
        100% { background-position: 200px 0; }
    }
    .sort-trigger {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .sort-icon {
        font-size: 14px;
        transition: transform 0.2s ease, color 0.2s ease;
        color: rgba(255,255,255,0.8);
    }
    .sort-trigger:hover .sort-icon {
        color: #ffc107;
        transform: translateY(-1px);
    }
    .sort-trigger.active .sort-icon.asc {
        color: #0dcaf0;
        transform: rotate(0deg);
    }
    .sort-trigger.active .sort-icon.desc {
        color: #0dcaf0;
        transform: rotate(180deg);
    }
    .sort-trigger.loading .sort-icon {
        color: #0d6efd;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
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

    /* Responsive Adjustments */
    @media (max-width: 768px) {
    .table-responsive {
        border-radius: 2px;
        overflow: hidden;
        position: relative;
    }
    #thead-spinner {
        top: 0;
        left: 0;
        height: 60px;
        width: 100%;
        pointer-events: none;
    }
    }

    #searchInput, 
    .input-group-text {
        height: 40px; /* samain tinggi */
    }

</style>
<div class="w-100">
    <!-- Top 5 Performance Section -->
    <!-- <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Top 5 Content dengan Kenaikan Views Tertinggi</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Campaign</th>
                                <th>Influencer</th>
                                <th>Views Awal</th>
                                <th>Views Akhir</th>
                                <th>Selisih Views</th>
                                <th>% Kenaikan</th>
                                <th>Engagement Awal</th>
                                <th>Engagement Akhir</th>
                                <th>Selisih Engagement</th>
                                <th>Link</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_5_data as $k => $v) {
                                $this->db->select('title');
                                $campaign = $this->mymodel->selectDataOne('endorse_campaign', array('id' => $v['id_campaign']));
                                $this->db->select('username');
                                $influencer = $this->mymodel->selectDataOne('influencer', array('id' => $v['influencer']));
                                
                                $percentage_increase = $v['views_before'] > 0 ? (($v['views_diff'] / $v['views_before']) * 100) : 0;
                                $campaign_link = '<a href="' . base_url() . 'endorse?id_campaign=' . $v['id_campaign'] . '&ids=' . $v['id_endorse'] . '" target="_blank">' . $campaign['title'] . '</a>';
                                $content_link = '<a href="' . $v['link_upload'] . '" target="_blank" class="btn btn-sm btn-outline-primary">View</a>';
                            ?>
                                <tr class="<?= $k == 0 ? 'table-warning' : '' ?>">
                                    <td class="text-center">
                                        <?php if($k == 0): ?>
                                            <span class="badge bg-warning text-dark fs-6">🏆 #<?= $k + 1 ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success">#<?= $k + 1 ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $campaign_link ?></td>
                                    <td><?= $influencer['username'] ?></td>
                                    <td class="text-end"><?= $this->template->separator_only($v['views_before']) ?></td>
                                    <td class="text-end"><?= $this->template->separator_only($v['views_after']) ?></td>
                                    <td class="text-end">
                                        <span class="badge bg-primary fs-6">+<?= $this->template->separator_only($v['views_diff']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-success fw-bold">+<?= number_format($percentage_increase, 1) ?>%</span>
                                    </td>
                                    <td class="text-end"><?= $this->template->separator_only($v['engagement_before']) ?></td>
                                    <td class="text-end"><?= $this->template->separator_only($v['engagement_after']) ?></td>
                                    <td class="text-end">
                                        <span class="badge <?= $v['engagement_diff'] > 0 ? 'bg-success' : 'bg-danger' ?> fs-6">
                                            <?= $v['engagement_diff'] > 0 ? '+' : '' ?><?= $this->template->separator_only($v['engagement_diff']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?= $content_link ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div> -->
    <!-- Complete Data Table -->
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    Endorse Logs <?= date('d-m-Y', strtotime($_GET['date'])) ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="col-lg-12 mb-3">
                    <form action="<?= $url ?>" method="GET">
                        <input type="hidden" name="ids" value="<?= $ids ?>">
                        <input type="hidden" name="id_campaign" value="<?= isset($id_campaign) ? $id_campaign : ($_GET['id_campaign'] ?? '') ?>">
                        <input type="hidden" name="date" value="<?= $date ?>">
                        <input type="hidden" name="limit" value="<?= $limit ?>">
                        <input type="hidden" name="page" value="1">
                        <input type="hidden" name="sort_column" value="<?= $sort_column ?>">
                        <input type="hidden" name="sort_order" value="<?= $sort_order ?>">
                        <div class="row">

                            <div class="col-lg-4">
                                <div class="d-flex">
                                    <button class="btn btn-outline-secondary-category dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-top-right-radius: 0px !important;
                                    border-bottom-right-radius: 0px !important;min-width:60px!important"><?= $keyword_category ?></button>
                                    <ul class="dropdown-menu">
                                        <?php
                                        $arr = array();
                                        $arr[] = 'Nama Creator';
                                        $arr[] = 'Link Upload';
                                        $arr[] = 'PIC';
                                        $arr[] = 'Platform';
                                        $arr[] = 'Task';
                                        $arr[] = 'Keterangan';
                                        foreach ($arr as $k => $val) {
                                            $class = "btn-default";
                                            if ($_GET['order_status'] == $val) {
                                                $class = "btn-default-selected";
                                            } 
                                        ?>
                                            <li><a class="dropdown-item" href="<?= $url ?>&keyword_category=<?= $val ?>"><?= $val ?></a></li>
                                        <?php }  ?>
                                    </ul>
                                    <input type="hidden" name="keyword_category" value="<?= $keyword_category ?>">
                                    <input type="text" name="keyword" class="form-control me-2" value="<?= $_GET['keyword'] ?>" style="border-top-left-radius: 0px !important;
                                    border-bottom-left-radius: 0px !important;width:400px!important">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <button class="btn btn-edit-active" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
                            </div>

                        </div>
                    </form>
                </div>
                
                <div id="customInfo" class="alert alert-info d-flex align-items-center mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <span>Menampilkan semua data</span>
                </div>

                <div id="tbody">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="endorse-table">
                            <thead>
                                <tr class="text-white thead-loading" id="thead-bar">
                                    <th>#</th>
                                    <th>Aksi</th>
                                    <?php
                                    $icon = function($col) use ($sort_column, $sort_order) {
                                        if ($sort_column === $col) {
                                            return $sort_order === 'ASC' ? '▲' : '▼';
                                        }
                                        return '↕';
                                    };
                                    ?>
                                    <th class="text-start"><a href="#!" class="text-dark text-decoration-none sort-trigger" data-column="campaign">Campaign <span><?= $icon('campaign') ?></span></a></th>
                                    <th class="text-start"><a href="#!" class="text-dark text-decoration-none sort-trigger" data-column="nama_creator">Influencer <span><?= $icon('nama_creator') ?></span></a></th>
                                    <th class=""><a href="#!" class="text-dark text-decoration-none sort-trigger" data-column="total_cost">Cost <span><?= $icon('total_cost') ?></span></a></th>
                                    <th class=""><a href="#!" class="text-dark text-decoration-none sort-trigger" data-column="cpm_after">CPM <span><?= $icon('cpm_after') ?></span></a></th>
                                    <th class=""><a href="#!" class="text-dark text-decoration-none sort-trigger" data-column="views_before">Views Awal <span><?= $icon('views_before') ?></span></a></th>
                                    <th class=""><a href="#!" class="text-dark text-decoration-none sort-trigger" data-column="views_after">Views Akhir <span><?= $icon('views_after') ?></span></a></th>
                                    <th class=""><a href="#!" class="text-dark text-decoration-none sort-trigger" data-column="views_diff">Selisih Views <span><?= $icon('views_diff') ?></span></a></th>
                                    <th class=""><a href="#!" class="text-dark text-decoration-none sort-trigger" data-column="views_diff">% Kenaikan Views <span><?= $icon('views_diff') ?></span></a></th> 
                                    <th class=""><a href="#!" class="text-dark text-decoration-none sort-trigger" data-column="engagement_before">Engagement Awal <span><?= $icon('engagement_before') ?></span></a></th>
                                    <th class=""><a href="#!" class="text-dark text-decoration-none sort-trigger" data-column="engagement_after">Engagement Akhir <span><?= $icon('engagement_after') ?></span></a></th>
                                    <th class=""><a href="#!" class="text-dark text-decoration-none sort-trigger" data-column="engagement_diff">Selisih Engagement <span><?= $icon('engagement_diff') ?></span></a></th>
                                    <th class=""><a href="#!" class="text-dark text-decoration-none sort-trigger" data-column="posting_at">Tanggal Posting <span><?= $icon('posting_at') ?></span></a></th>
                                    <th class="text-start">Link Upload</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($data_with_diff as $k => $v) {
                                    $this->db->select('title');
                                    $campaign = $this->mymodel->selectDataOne('endorse_campaign', array('id' => $v['id_campaign']));
                                    $this->db->select('username');
                                    $influencer = $this->mymodel->selectDataOne('influencer', array('id' => $v['influencer']));

                                    $v['link_upload'] = '<a href="' . $v['link_upload'] . '" target="_blank">' . $v['link_upload'] . '</a>';
                                    $campaign['title'] = '<a href="' . base_url() . 'endorse?id_campaign=' . $v['id_campaign'] . '&ids=' . $v['id_endorse'] . '" target="_blank">' . $campaign['title'] . '</a>';

                                    $percentage_increase = $v['views_before'] > 0 ? (($v['views_diff'] / $v['views_before']) * 100) : 0;
                                ?>
                                    <tr>
                                        <td><?= $k + 1 ?></td>
                                        <td style="padding-top:12px!important">
                                            <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="mt-0 text-red">
                                                <i class="bi bi-trash text-icon"></i>
                                            </a>
                                        </td>
                                        <td class="text-start"><?= $campaign['title'] ?></td>
                                        <td class="text-start"><?= $influencer['username'] ?></td>
                                        <td class="text-start" data-order="<?= $v['total_cost'] ?>"><?= $this->template->separator_only($v['total_cost']) ?></td>
                                        <td class="text-start" data-order="<?= $v['cpm_after'] ?>"><?= $this->template->separator_only($v['cpm_after']) ?></td>
                                        <td class="text-end" data-order="<?= $v['views_before'] ?>"><?= $this->template->separator_only($v['views_before']) ?></td>
                                        <td class="text-end" data-order="<?= $v['views_after'] ?>"><?= $this->template->separator_only($v['views_after']) ?></td>
                                        <td class="text-end" data-order="<?= $v['views_diff'] ?>">
                                            <span class="<?= $v['views_diff'] > 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                                                <?= $v['views_diff'] > 0 ? '+' : '' ?><?= $this->template->separator_only($v['views_diff']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="<?= $percentage_increase >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold' ?>">
                                                <?= $percentage_increase >= 0 ? '+' : '' ?><?= number_format($percentage_increase, 1) ?>%
                                            </span>
                                        </td>
                                        <td class="text-end" data-order="<?= $v['engagement_before'] ?>"><?= $this->template->separator_only($v['engagement_before']) ?></td>
                                        <td class="text-end" data-order="<?= $v['engagement_after'] ?>"><?= $this->template->separator_only($v['engagement_after']) ?></td>
                                        <td class="text-end" data-order="<?= $v['engagement_diff'] ?>">
                                            <span class="<?= $v['engagement_diff'] > 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                                                <?= $v['engagement_diff'] > 0 ? '+' : '' ?><?= $this->template->separator_only($v['engagement_diff']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end" data-order="<?= $v['posting_at'] ?>"><?= $v['posting_at'] ?></td>
                                        <td class="text-start"><?= $v['link_upload'] ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <?php
                                $total_cost = isset($totals['total_cost']) ? $totals['total_cost'] : 0;
                                $total_cpm = isset($totals['total_cpm_after']) ? $totals['total_cpm_after'] : 0;
                                $total_views_before = isset($totals['total_views_before']) ? $totals['total_views_before'] : 0;
                                $total_views_after = isset($totals['total_views_after']) ? $totals['total_views_after'] : 0;
                                $total_views_diff = isset($totals['total_views_diff']) ? $totals['total_views_diff'] : 0;
                                $total_engagement_before = isset($totals['total_engagement_before']) ? $totals['total_engagement_before'] : 0;
                                $total_engagement_after = isset($totals['total_engagement_after']) ? $totals['total_engagement_after'] : 0;
                                $total_engagement_diff = isset($totals['total_engagement_diff']) ? $totals['total_engagement_diff'] : 0;
                                $total_percentage_increase = $total_views_before > 0 ? (($total_views_diff / $total_views_before) * 100) : 0;
                                ?>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="4" class="text-end">Total (semua data):</td>
                                    <td class="text-start"><?= $this->template->separator_only($total_cost) ?></td>
                                    <td class="text-start"><?= $this->template->separator_only($total_cpm) ?></td>
                                    <td class="text-end"><?= $this->template->separator_only($total_views_before) ?></td>
                                    <td class="text-end"><?= $this->template->separator_only($total_views_after) ?></td>
                                    <td class="text-end">
                                        <span class="<?= $total_views_diff > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $total_views_diff > 0 ? '+' : '' ?><?= $this->template->separator_only($total_views_diff) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="<?= $total_percentage_increase >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $total_percentage_increase >= 0 ? '+' : '' ?><?= number_format($total_percentage_increase, 1) ?>%
                                        </span>
                                    </td>
                                    <td class="text-end"><?= $this->template->separator_only($total_engagement_before) ?></td>
                                    <td class="text-end"><?= $this->template->separator_only($total_engagement_after) ?></td>
                                    <td class="text-end">
                                        <span class="<?= $total_engagement_diff > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $total_engagement_diff > 0 ? '+' : '' ?><?= $this->template->separator_only($total_engagement_diff) ?>
                                        </span>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="spinner" id="thead-spinner"><div class="spinner-border text-primary" role="status"></div></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <?= isset($pagination) ? $pagination : '' ?>
                        </div>
                        <div class="d-flex align-items-center" style="gap:8px;">
                            <select class="form-control" id="limit-select">
                                <?php foreach ($per_page_options as $option): ?>
                                    <option value="<?= $option ?>" <?= ($limit == $option) ? 'selected' : '' ?>>
                                        <?= $option ?> / Halaman
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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

</style>

<script>
    const ajaxUrl = "<?= base_url('endorse/logs') ?>";
    const baseLink = "<?= base_url() ?>";
    const ONLY_INCREASE = <?= $only_increase ? 'true' : 'false' ?>;

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>endorse/remove_logs?id=" + id);
    }

    $(document).ready(function() {
        var table = $('#endorse-table').DataTable({
            searching: false,
            paging: false,
            ordering: false,
            info: false,       
            lengthChange: false
        });

        function numberFormat(n) {
            n = Number(n || 0);
            return n.toLocaleString('id-ID');
        }

        function setLoading(state) {
            const thead = document.getElementById('thead-bar');
            const spinner = document.getElementById('thead-spinner');
            if (!thead) return;
            if (state) {
                thead.classList.add('loading');
                if (spinner) spinner.style.display = 'flex';
            } else {
                thead.classList.remove('loading');
                if (spinner) spinner.style.display = 'none';
            }
        }

        function buildParams(extra = {}) {
            const params = new URLSearchParams(window.location.search);
            Object.keys(extra).forEach(k => {
                if (extra[k] === null) {
                    params.delete(k);
                } else {
                    params.set(k, extra[k]);
                }
            });
            if (!params.has('id_campaign')) {
                const idCampaignInput = document.querySelector('input[name="id_campaign"]');
                if (idCampaignInput && idCampaignInput.value !== '') {
                    params.set('id_campaign', idCampaignInput.value);
                }
            }
            return params;
        }

        function renderRows(items) {
            let html = '';
            items.forEach(function(v, idx) {
                const views_before = Number(v.views_before || 0);
                const views_after = Number(v.views_after || 0);
                let views_diff = (v.views_diff !== undefined && v.views_diff !== null) ? Number(v.views_diff) : (views_after - views_before);
                const engagement_before = (v.engagement_before !== undefined && v.engagement_before !== null)
                    ? Number(v.engagement_before)
                    : (Number(v.likes_before || 0) + Number(v.comment_before || 0) + Number(v.share_save_before || 0));
                const engagement_after = (v.engagement_after !== undefined && v.engagement_after !== null)
                    ? Number(v.engagement_after)
                    : (Number(v.likes_after || 0) + Number(v.comment_after || 0) + Number(v.share_save_after || 0));
                let engagement_diff = (v.engagement_diff !== undefined && v.engagement_diff !== null)
                    ? Number(v.engagement_diff)
                    : (engagement_after - engagement_before);
                if (ONLY_INCREASE) {
                    if (views_diff < 0) views_diff = 0;
                    if (engagement_diff < 0) engagement_diff = 0;
                }
                const percentage_increase = views_before > 0 ? (views_diff / views_before * 100) : 0;

                const campaign_title = v.campaign_title ? v.campaign_title : '';
                const influencer_username = v.influencer_username ? v.influencer_username : v.nama_creator;
                const link_upload = v.link_upload ? `<a href="${v.link_upload}" target="_blank">${v.link_upload}</a>` : '';
                const campaign_link = v.id_campaign ? `<a href="${baseLink}endorse?id_campaign=${v.id_campaign}&ids=${v.id_endorse}" target="_blank">${campaign_title}</a>` : campaign_title;

                html += `
                    <tr>
                        <td>${idx + 1}</td>
                        <td style="padding-top:12px!important">
                            <a href="#!" onclick="remove('${v.id}')" class="mt-0 text-red">
                                <i class="bi bi-trash text-icon"></i>
                            </a>
                        </td>
                        <td class="text-start">${campaign_link || ''}</td>
                        <td class="text-start">${influencer_username || ''}</td>
                        <td class="text-start" data-order="${v.total_cost}">${numberFormat(v.total_cost)}</td>
                        <td class="text-start" data-order="${v.cpm_after}">${numberFormat(v.cpm_after)}</td>
                        <td class="text-end" data-order="${views_before}">${numberFormat(views_before)}</td>
                        <td class="text-end" data-order="${views_after}">${numberFormat(views_after)}</td>
                        <td class="text-end" data-order="${views_diff}">
                            <span class="${views_diff > 0 ? 'text-success' : 'text-danger'} fw-bold">
                                ${views_diff > 0 ? '+' : ''}${numberFormat(views_diff)}
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="${percentage_increase >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold'}">
                                ${percentage_increase >= 0 ? '+' : ''}${percentage_increase.toFixed(1)}%
                            </span>
                        </td>
                        <td class="text-end" data-order="${engagement_before}">${numberFormat(engagement_before)}</td>
                        <td class="text-end" data-order="${engagement_after}">${numberFormat(engagement_after)}</td>
                        <td class="text-end" data-order="${engagement_diff}">
                            <span class="${engagement_diff > 0 ? 'text-success' : 'text-danger'} fw-bold">
                                ${engagement_diff > 0 ? '+' : ''}${numberFormat(engagement_diff)}
                            </span>
                        </td>
                        <td class="text-end" data-order="${v.posting_at || ''}">${v.posting_at || ''}</td>
                        <td class="text-start">${link_upload}</td>
                    </tr>
                `;
            });
            $('#endorse-table tbody').html(html);
        }

        function renderTotals(totals) {
            const total_cost = totals.total_cost || 0;
            const total_cpm = totals.total_cpm_after || 0;
            const total_views_before = totals.total_views_before || 0;
            const total_views_after = totals.total_views_after || 0;
            const total_views_diff = totals.total_views_diff || 0;
            const total_engagement_before = totals.total_engagement_before || 0;
            const total_engagement_after = totals.total_engagement_after || 0;
            const total_engagement_diff = totals.total_engagement_diff || 0;
            const total_percentage_increase = total_views_before > 0 ? (total_views_diff / total_views_before * 100) : 0;

            const html = `
                <tr class="fw-bold" style="background-color: #f8f9fa;">
                    <td colspan="4" class="text-end">Total (semua data):</td>
                    <td class="text-start">${numberFormat(total_cost)}</td>
                    <td class="text-start">${numberFormat(total_cpm)}</td>
                    <td class="text-end">${numberFormat(total_views_before)}</td>
                    <td class="text-end">${numberFormat(total_views_after)}</td>
                    <td class="text-end">
                        <span class="${total_views_diff > 0 ? 'text-success' : 'text-danger'}">
                            ${total_views_diff > 0 ? '+' : ''}${numberFormat(total_views_diff)}
                        </span>
                    </td>
                    <td class="text-end">
                        <span class="${total_percentage_increase >= 0 ? 'text-success' : 'text-danger'}">
                            ${total_percentage_increase >= 0 ? '+' : ''}${total_percentage_increase.toFixed(1)}%
                        </span>
                    </td>
                    <td class="text-end">${numberFormat(total_engagement_before)}</td>
                    <td class="text-end">${numberFormat(total_engagement_after)}</td>
                    <td class="text-end">
                        <span class="${total_engagement_diff > 0 ? 'text-success' : 'text-danger'}">
                            ${total_engagement_diff > 0 ? '+' : ''}${numberFormat(total_engagement_diff)}
                        </span>
                    </td>
                    <td></td>
                </tr>
            `;
            $('#endorse-table tfoot').html(html);
        }

        function updateInfo(total, current_page, total_page) {
            var notifText = "Menampilkan halaman " + current_page + " dari " + total_page + " (total " + total + " data)";
            $("#customInfo span").text(notifText);
        }

        function updateSortIndicator(col, ord) {
            $('.sort-trigger span').text('↕');
            $('.sort-trigger').each(function() {
                if ($(this).data('column') === col) {
                    $(this).find('span').text(ord && ord.toUpperCase() === 'ASC' ? '▲' : '▼');
                }
            });
        }

        function fetchData(extra = {}) {
            const params = buildParams(extra);
            params.set('ajax', 1);
            const col = extra.sort_column || (new URLSearchParams(window.location.search).get('sort_column') || 'id');
            const ord = (extra.sort_order || (new URLSearchParams(window.location.search).get('sort_order') || 'DESC')).toUpperCase();
            setLoading(true);
            fetch(ajaxUrl + '?' + params.toString(), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(res => res.json())
                .then(res => {
                    renderRows(res.data || []);
                    renderTotals(res.totals || {});
                    $('.pagination').html(res.pagination || '');
                    updateInfo(res.total_data || 0, res.current_page || 1, res.page || 1);
                    const newParams = buildParams(extra);
                    newParams.delete('ajax');
                    window.history.replaceState(null, '', '?' + newParams.toString());
                    updateSortIndicator(col, ord);
                })
                .catch(() => {
                    alert('Gagal memuat data');
                })
                .finally(() => setLoading(false));
        }

        // Handle limit change (server-side pagination)
        $("#limit-select").on("change", function() {
            fetchData({limit: $(this).val(), page: 1});
        });

        // Handle sort click
        $(document).on('click', '.sort-trigger', function(e) {
            e.preventDefault();
            const column = $(this).data('column');
            const params = new URLSearchParams(window.location.search);
            const currentCol = params.get('sort_column') || 'id';
            const currentOrder = (params.get('sort_order') || 'DESC').toUpperCase();
            let nextOrder = 'DESC';
            if (currentCol === column) {
                nextOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                nextOrder = 'DESC';
            }
            fetchData({sort_column: column, sort_order: nextOrder, page: 1});
        });

        // Handle pagination click
        $(document).on('click', '.pagination a', function(e) {
            e.preventDefault();
            const url = new URL($(this).attr('href'), window.location.origin);
            const page = url.searchParams.get('page') || 1;
            fetchData({page: page});
        });

        // Handle filter submit
        $('form').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const extra = {};
            formData.forEach((v, k) => { extra[k] = v; });
            extra['page'] = 1;
            fetchData(extra);
        });

        // Initial info
        updateInfo(<?= isset($total_data) ? intval($total_data) : 0 ?>, <?= isset($current_page) ? $current_page : 1 ?>, <?= isset($page) ? $page : 1 ?>);
        updateSortIndicator("<?= $sort_column ?>", "<?= $sort_order ?>");
    });



    
</script>
