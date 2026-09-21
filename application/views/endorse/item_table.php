<?php
$is_internal = (int)($is_internal ?? 0) === 1;
if (!function_exists('endorse_limited_refresh_locked')) {
    function endorse_limited_refresh_locked($row) {
        $is_limited = intval($row['campaign_update_terbatas'] ?? $row['update_terbatas'] ?? 0) === 1;
        $limit_days = intval($row['campaign_update_batas_hari'] ?? $row['update_batas_hari'] ?? 0);
        $posting_at = trim((string)($row['posting_at'] ?? ''));

        if (!$is_limited || $limit_days <= 0 || $posting_at === '' || $posting_at === '-') {
            return false;
        }

        $posting_ts = strtotime($posting_at);
        if (!$posting_ts) {
            return false;
        }

        $expired_ts = strtotime(date('Y-m-d', $posting_ts) . ' +' . $limit_days . ' days');
        return $expired_ts !== false && $expired_ts <= strtotime(date('Y-m-d'));
    }
}
?>
<style>
    th.sortable {
        cursor: pointer;
        position: relative;
    }
    th.sortable:hover {
        background-color: #f1f1f1;
    }
    th.sortable i {
        font-size: 0.8em;
        margin-left: 5px;
    }
    .table-responsive {
        position: relative;
        transition: all 0.3s ease;
    }


    .spinner-border {
        display: inline-block;
        width: 2rem;
        height: 2rem;
        vertical-align: text-bottom;
        border: 0.25em solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spinner-border 0.75s linear infinite;
    }


    @keyframes spinner-border {
        to { transform: rotate(360deg); }
    }

    /* Filter Box Styles */
    .filter-box {
        position: absolute;
        display: none;
        background: white;
        border: 1px solid #f0f0f0;
        padding: 16px;
        width: 220px;
        box-shadow: 0 3px 6px -4px rgba(0,0,0,0.12), 0 6px 16px 0 rgba(0,0,0,0.08), 0 9px 28px 8px rgba(0,0,0,0.05);
        border-radius: 2px;
        z-index: 1000;
    }

    .filter-box label {
        display: block;
        margin-bottom: 8px;
        color: rgba(0, 0, 0, 0.85);
        font-size: 14px;
    }

    .filter-box .form-control {
        margin-bottom: 12px;
    }

    th.with-filter {
        position: relative;
    }
</style>
<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="bg-light">
                <!-- <th data-original-index="<?= $k + 1 ?>"> -->
                <th width="40">#</th>
                <th class="sortable">Nama Influencer 
                    <?php if ($sort_column == 'nama_creator'): ?>
                        <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                    <?php else: ?>
                        <i class="bi bi-arrow-down-up"></i>
                    <?php endif; ?>
                </th>
                <th class="with-filter">
                    PIC 
                    <a href="javascript:void(0);" id="openPICFilter" class="text-decoration-none">
                        <i class="fas fa-filter ms-2 fs-12"></i>
                    </a>
                    
                    <div id="picFilterBox" class="filter-box">
                        <label>Filter PIC:</label>
                        <div style="margin-bottom: 8px;">
                            <label style="display: flex; align-items: center;">
                                <input type="checkbox" id="selectAllPic" class="me-2">
                                Pilih Semua
                            </label>
                        </div>

                        <div id="picOptions" style="max-height: 200px; overflow-y: auto; border: 1px solid #f0f0f0; padding: 5px; margin-bottom: 12px;">
                            <?php foreach ($filter_pic as $item): ?>
                                <div>
                                    <label style="display: flex; align-items: center; margin-bottom: 8px;">
                                        <input type="checkbox" class="pic-checkbox me-2" value="<?= htmlspecialchars($item['pic']) ?>">
                                        <?= htmlspecialchars($item['pic']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn-sm btn-primary w-100" id="applyPicFilter">Terapkan</button>
                    </div>
                </th>
                
                <?php if (!$is_internal): ?>
                <th class="sortable">Total Cost
                    <?php if ($sort_column == 'total_cost'): ?>
                        <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                    <?php else: ?>
                        <i class="bi bi-arrow-down-up"></i>
                    <?php endif; ?>
                </th>
                <?php endif; ?>
                <th class="sortable">Status
                    <?php if ($sort_column == 'status_endorse'): ?>
                        <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                    <?php else: ?>
                        <i class="bi bi-arrow-down-up"></i>
                    <?php endif; ?>
                </th>
                <th class="sortable">Tanggal Posting
                    <?php if ($sort_column == 'posted_at'): ?>
                        <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                    <?php else: ?>
                        <i class="bi bi-arrow-down-up"></i>
                    <?php endif; ?>
                </th>
                <th class="sortable">Views
                    <?php if ($sort_column == 'views'): ?>
                        <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                    <?php else: ?>
                        <i class="bi bi-arrow-down-up"></i>
                    <?php endif; ?>
                </th>
                <?php if (!$is_internal): ?>
                <th class="sortable">CPM
                    <?php if ($sort_column == 'cpm'): ?>
                        <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                    <?php else: ?>
                        <i class="bi bi-arrow-down-up"></i>
                    <?php endif; ?>
                </th>
                <?php endif; ?>
                <th class="sortable">Engagement
                    <?php if ($sort_column == 'likes'): ?>
                        <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                    <?php else: ?>
                        <i class="bi bi-arrow-down-up"></i>
                    <?php endif; ?>
                </th>
                <th>Link Upload</i></th>
                <?php if (!$is_internal): ?>
                <th>Kode Ads</th>
                <th>Keterangan</th>
                <?php endif; ?>
                <th width="120">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $v): 
                $is_limited_refresh_locked = endorse_limited_refresh_locked($v);
                if ($v['platform'] == "Tiktok") {
                    $v['img'] = base_url() . '/assets/img/icon/icon-tiktok.png';
                } else if ($v['platform'] == "Instagram") {
                    $v['img'] = base_url() . '/assets/img/icon/icon-ig.png';
                } else if ($v['platform'] == "Youtube") {
                    $v['img'] = base_url() . '/assets/img/icon/icon-youtube.png';
                } else if ($v['platform'] == "Facebook") {
                    $v['img'] = base_url() . '/assets/img/icon/icon-fb.png';
                } else if ($v['platform'] == "Twitter") {
                    $v['img'] = base_url() . '/assets/img/icon/icon-twitter.png';
                } else if ($v['platform'] == "Threads") {
                    $v['img'] = base_url() . '/assets/img/icon/icon-threads.png';
                } else {
                    $v['img'] = base_url() . '/assets/img/icon/icon-no.png';
                }
                if ($v['nama_creator'] == "") {
                    $v['nama_creator'] = "-";
                }
                if ($v['posting_at']) {
                    $v['posting_at'] = DATE("d/m/Y", strtotime($v['posting_at']));
                } else {
                    $v['posting_at'] = '-';
                }
                if ($v['rencana_at']) {
                    $v['rencana_at'] = DATE("d/m/Y", strtotime($v['rencana_at']));
                } else {
                    $v['rencana_at'] = '-';
                }
                if ($v['barang_dikirim_at']) {
                    $v['barang_dikirim_at'] = DATE("d/m/Y", strtotime($v['barang_dikirim_at']));
                } else {
                    $v['barang_dikirim_at'] = '-';
                }
                if ($v['sync_at']) {
                    $v['sync_at'] = DATE("d/m/Y", strtotime($v['sync_at']));
                } else {
                    $v['sync_at'] = '-';
                }
                if ($v['created_at']) {
                    $v['created_at'] = DATE("d/m/Y", strtotime($v['created_at']));
                } else {
                    $v['created_at'] = '-';
                }
                if ($v['link_brief']) {
                    $v['link_brief'] = '<a href="' . $v['link_brief'] . '" target="_blank">' . $v['link_brief'] . '</a>';
                } else {
                    $v['link_brief'] = '-';
                }
                if ($v['link_mou']) {
                    $v['link_mou'] = '<a href="' . $v['link_mou'] . '" target="_blank">' . $v['link_mou'] . '</a>';
                } else {
                    $v['link_mou'] = '-';
                }
                if ($v['link_upload']) {
                    $link_upload = htmlspecialchars($v['link_upload'], ENT_QUOTES);
                    $tiktok_attr = ($v['platform'] === "Tiktok")
                        ? ' class="tiktok-embed-trigger" data-tiktok-url="' . $link_upload . '"'
                        : '';
                    $v['img'] = '<a href="' . $link_upload . '" target="_blank" rel="noopener noreferrer"' . $tiktok_attr . '><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img'] . '"></a>';
                } else {
                    $v['img'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img'] . '">';
                }
                
                $bg = '#e6e6e6';
                $clr = '#000';
                if (in_array($v['status_endorse'], array('Review'))) {
                    $bg = '#e6e6e6';
                } else if (in_array($v['status_endorse'], array('Hold'))) {
                    $bg = '#ffe599';
                } else if (in_array($v['status_endorse'], array('Acc', 'Pengajuan Payment'))) {
                    $bg = '#f6b26b';
                } else if (in_array($v['status_endorse'], array('Barang Dikirim'))) {
                    $bg = '#ffd0d0';
                } else if (in_array($v['status_endorse'], array('Draft Content'))) {
                    $bg = '#d4edbc';
                } else if (in_array($v['status_endorse'], array('Posted Content'))) {
                    $bg = '#7bd3ea';
                } else if (in_array($v['status_endorse'], array('Reject', 'Problem'))) {
                    $bg = '#ea7b7b';
                }
        
                if (in_array($v['status_payment'], array('DP'))) {
                    $bg_payment = '#60bb55';
                    $clr_payment = '#000';
                } else if (in_array($v['status_payment'], array('FP'))) {
                    $bg_payment = '#1255cc';
                    $clr_payment = '#000';
                } else if (in_array($v['status_payment'], array('Pengajuan Payment'))) {
                    $bg_payment = '#e6e6e6';
                    $clr_payment = '#000';
                } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment DP'))) {
                    $bg_payment = '#C2FFC7';
                    $clr_payment = '#000';
                } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment FP'))) {
                    $bg_payment = '#A5BFCC';
                    $clr_payment = '#000';
                } else if (in_array($v['status_payment'], array(' '))) {
                    $bg_payment = '#fff';
                    $clr_payment = '#fff';
                } else if (in_array($v['pengajuan_status_payment'], array(' '))) {
                    $bg_payment = '#fff';
                    $clr_payment = '#fff';
                }
        
                if (empty($v['desc'])) {
                    $v['desc'] = '-';
                }
        
                $creator = $this->mymodel->selectDataOne('influencer', array('id' => $v['influencer']));
                $v['type'] = $creator['type'];
                $v['tipe_kontak'] = $creator['tipe_kontak'];
                $v['url'] = $creator['url'];
        
                if ($v['type'] == "Tiktok") {
                    $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-tiktok.png';
                } else if ($v['type'] == "Instagram") {
                    $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-ig.png';
                } else if ($v['type'] == "Youtube") {
                    $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-youtube.png';
                } else if ($v['type'] == "Facebook") {
                    $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-fb.png';
                } else if ($v['type'] == "Twitter") {
                    $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-twitter.png';
                } else if ($v['type'] == "Threads") {
                    $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-threads.png';
                } else {
                    $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-no.png';
                }
        
                if ($v['url']) {
                    $creator_url = htmlspecialchars($v['url'], ENT_QUOTES);
                    $v['img_creator_1'] = '<a href="' . $creator_url . '" target="_blank" rel="noopener noreferrer"><img style="width:30px;border-radius:40px;" class="mt-0 me-1" src="' . $v['img_creator_1'] . '"></a>';
                } else {
                    $v['img_creator_1'] = '<img style="width:30px;border-radius:40px; filter: grayscale(100%)!important;" class="mt-0 me-1" src="' . $v['img_creator_1'] . '">';
                }
        
                if ($v['tipe_kontak'] == "WA") {
                    $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-wa.png';
                } else if ($v['tipe_kontak'] == "IG") {
                    $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-ig.png';
                } else if ($v['tipe_kontak'] == "Email") {
                    $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-email.png';
                } else if ($v['tipe_kontak'] == "HP") {
                    $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-phone.png';
                } else {
                    $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-no.png';
                }
        
                $contactUrl = !empty($v['contact']) ? normalize_whatsapp_link($v['contact']) : null;

                if ($contactUrl) {
                    $v['img_creator_2'] = '<a href="' . htmlspecialchars($contactUrl, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer"><img style="width:30px;border-radius:40px;" class="mt-0 me-1" src="' . $v['img_creator_2'] . '"></a>';
                } else {
                    $v['img_creator_2'] = '<img style="width:30px;border-radius:40px; filter: grayscale(100%)!important;" class="mt-0 me-1" src="' . $v['img_creator_2'] . '">';
                }
                $creator = $this->mymodel->selectDataOne('influencer', array('id' => $v['influencer']));
                $v['nama_creator'] = $v['nama_creator'] ?: "-";
                $v['pic'] = $v['pic'] ?: "-";
                $v['desc'] = $v['desc'] ?: "-";
                
                // Status background color
                $bg = '#e6e6e6';
                $clr = '#000';
                if (in_array($v['status_endorse'], array('Hold'))) {
                    $bg = '#ffe599';
                } else if (in_array($v['status_endorse'], array('Acc', 'Pengajuan Payment'))) {
                    $bg = '#f6b26b';
                } else if (in_array($v['status_endorse'], array('Barang Dikirim'))) {
                    $bg = '#ffd0d0';
                } else if (in_array($v['status_endorse'], array('Draft Content'))) {
                    $bg = '#d4edbc';
                } else if (in_array($v['status_endorse'], array('Posted Content'))) {
                    $bg = '#7bd3ea';
                } else if (in_array($v['status_endorse'], array('Reject', 'Problem'))) {
                    $bg = '#ea7b7b';
                }
                
                // Payment status
                $bg_payment = '#fff';
                $clr_payment = '#000';
                if (in_array($v['status_payment'], array('DP'))) {
                    $bg_payment = '#60bb55';
                } else if (in_array($v['status_payment'], array('FP'))) {
                    $bg_payment = '#8CCDEB';
                } else if (in_array($v['status_payment'], array('Pengajuan Payment'))) {
                    $bg_payment = '#e6e6e6';
                } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment DP'))) {
                    $bg_payment = '#C2FFC7';
                } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment FP'))) {
                    $bg_payment = '#A5BFCC';
                }
            ?>
            <tr>
                <!-- <td><?= $k + 1 ?></td> -->
                <td>
                    <div class="checkbox-wrapper-13 d-inline">
                        <input class="checkItem" style="" type="checkbox" value="<?= $v['id'] ?>" data-id="<?= $k ?>" name="list_id" form="form-action">
                    </div>
                </td>
                <td class="text-start">
                    <div>
                        <span class="fw-bold" style="font-size: 16px;"><?= $v['nama_creator'] ?></span>
                        <i class="bi bi-eye text-blue ms-1 eye-list-trigger" data-influencer-id="<?= $v['influencer'] ?>"></i>
                    </div>

                    <div class="d-flex align-items-center mt-1">
                        <?= $v['img_creator_1'] ?><br>
                        <?= $v['img_creator_2'] ?>
                        <a href="#!" onclick="change_fyp_<?= $k ?>()" id="fyp_<?= $k ?>" class="ms-1">
                            <?php if ($v['is_fyp'] == 1) { ?>
                                <i class="bi bi-star-fill" style="font-size:30px;color:#ffd250"></i>
                            <?php } else { ?>
                                <i class="bi bi-star" style="font-size:30px;color:#000"></i>
                            <?php } ?>
                            <script>
                                function change_fyp_<?= $k ?>() {
                                    $('#fyp_<?= $k ?>').html('<i style="font-size:30px;color:#000" class="fa fa-circle-o-notch fa-spin"></i>');
                                    $.ajax({
                                        dataType: "json",
                                        url: '<?= base_url() ?>ajax/change-fyp?id=<?= $v['id'] ?>',
                                        success: function(html) {
                                            $('#fyp_<?= $k ?>').html(html.html);
                                            <?php $v['code'] =  'mar-5' ?>
                                            $.ajax({
                                                dataType: "json",
                                                url: '<?= base_url() ?>ajax/get-summary-campaign<?= $param ?>&id=<?= $v['code'] ?>&id_campaign=<?= $v['id_campaign'] ?>',
                                                success: function(html) {
                                                    $("#summary-<?= $v['code'] ?>").html(html.html);
                                                }
                                            });
                                        }
                                    });
                                }
                            </script>
                        </a>
                    </div>
                </td>

                <td><?= $v['pic'] ?></td>
                <?php if (!$is_internal): ?>
                <td><?= separator_only($v['total_cost']) ?></td>
                <?php endif; ?>
                <td>
                    <?php if (!$is_internal): ?>
                    <!-- Status Endorse -->
                    <div>
                        <span class="badge" style="background-color:<?= $bg ?>; color:<?= $clr ?>">
                            <?= strtoupper(strtolower($v['status_endorse'])) ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <!-- Status Payment -->
                    <div style="margin-top: 5px;">
                        <?php if (!empty($v['status_payment'])): ?>
                            <span class="badge" style="background-color:<?= $bg_payment ?>; color:<?= $clr_payment ?>">
                                <?= strtoupper(strtolower($v['status_payment'])) ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($v['pengajuan_status_payment'])): ?>
                            <span class="badge" style="background-color:<?= $bg_payment ?>; color:<?= $clr_payment ?>">
                                <?= strtoupper(strtolower($v['pengajuan_status_payment'])) ?>
                            </span>
                        <?php endif; ?>

                        <?php if (trim($v['status_payment']) === '' && trim($v['pengajuan_status_payment']) === ''): ?>
                            <span class="badge" style="background-color:<?= $bg_payment ?>; color:<?= $clr_payment ?>">
                                -
                            </span>
                        <?php endif; ?>
                    </div>
                </td>
                <td><?= $v['posting_at'] ? $v['posting_at'] : '-' ?></td>
                <td><?= separator_only($v['views']) ?></td>
                <?php if (!$is_internal): ?>
                <td><?= separator_only($v['cpm']) ?></td>
                <?php endif; ?>
                <td class="text-start"><?= separator_only($v['engagement']) ?></td>
                <td>
                    <div class="firstDivImg">
                        <?= $v['img'] ?>
                    </div>
                </td>

                <?php if (!$is_internal): ?>
                <td>
                    <?php if (empty($v['kode_ads'])): ?>
                        -
                    <?php else: ?>
                        <a href="javascript:void(0)"
                        class="text-decoration-none btn-copy btn-copy-kode btn btn-sm btn-outline-primary"
                        data-kode="<?= htmlspecialchars($v['kode_ads']) ?>"
                        title="Salin Kode">
                            <i class="bi bi-clipboard fs-16"></i>
                        </a>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
                        

                <div class="col-lg-12 mb-3">
                    <input type="hidden" value="<?= $v['is_manual'] ?>" name="is_manual[<?= $k - $start ?>]" form="form-action">
                    <input type="hidden" value="<?= $v['marketplace'] ?>" name="marketplace[<?= $k - $start ?>]" form="form-action">
                    <input type="hidden" value="<?= $v['brand'] ?>" name="brand[<?= $k - $start ?>]" form="form-action">
                    <input type="hidden" value="<?= $v['order_id'] ?>" name="order_id[<?= $k - $start ?>]" form="form-action">
                </div>
                <?php if (!$is_internal): ?>
                <td style="white-space: normal; word-wrap: break-word;">
                    <?= $v['desc'] ?>
                </td>
                <?php endif; ?>

                <td class="text-end">
                    <div class="col-lg-4 text-lg-end text-end">
                        <div class="dropdown">
                            <a href="#" class="text-muted" id="actionDropdown<?= $v['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical fs-16"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end p-2" aria-labelledby="actionDropdown<?= $v['id'] ?>">

                                <?php if (!in_array($v['status_payment'], ['DP','FP'], true)) : ?>
                                <li>
                                    <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="dropdown-item text-danger">
                                        <i class="bi bi-trash me-2"></i> Delete Data
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if ($is_limited_refresh_locked): ?>
                                <li>
                                    <span class="dropdown-item disabled" title="Refresh dinonaktifkan karena konten sudah melewati batas update campaign">
                                        <i class="bi bi-bootstrap-reboot me-2"></i> Refresh
                                    </span>
                                </li>
                                <?php elseif ($v['status'] == "Aktif" && $v['status_campaign'] == "Aktif" && $v['link_upload'] != ""): ?>
                                <li>
                                    <a href="#!" onclick="sync('<?= $v['id'] ?>')" class="dropdown-item">
                                        <i class="bi bi-bootstrap-reboot me-2"></i> Refresh
                                    </a>
                                </li>
                                <?php endif; ?>

                                <li>
                                    <a href="#!" onclick="clone('<?= $v['id'] ?>')" class="dropdown-item">
                                        <i class="bi bi-copy me-2"></i> Kloning
                                    </a>
                                </li>

                                <li>
                                    <a href="#!" onclick="edit('<?= $v['id'] ?>')" class="dropdown-item">
                                        <i class="bi bi-pencil-square me-2"></i> Edit Data
                                    </a>
                                </li>

                                <?php if (!$is_internal && $v['link_mou'] == '-'): ?>
                                <li>
                                    <a href="#!" onclick="generate_mou('<?= $v['id'] ?>')" class="dropdown-item">
                                        <i class="bi bi-clipboard2-plus me-2"></i> Generate MOU
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if (!$is_internal): ?>
                                <li>
                                    <a href="#!" onclick="set_payment(<?= $v['id'] ?>)" class="dropdown-item">
                                        <i class="bi bi-clipboard2-check me-2"></i> Ajukan Payment
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if (!empty($v['pengajuan_status_payment'])): ?>
                                <li>
                                    <a href="#!" onclick="set_batalkan_payment(<?= $v['id'] ?>)" class="dropdown-item text-danger">
                                        <i class="bi bi-clipboard2-x me-2"></i> Batalkan Payment
                                    </a>
                                </li>
                                <?php endif; ?>

                            </ul>
                        </div>
                    </div>
                </td>


            </tr>
            <?php $k += 1; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
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
.tippy-box,
.tippy-root,
.tippy-popper {
    z-index: 99999 !important;
}
</style>

<script>
    function copyKodeAds(text, trigger) {
        if (!text) return;
        var done = function() {
            if (window.$ && $.toast) {
                $.toast({
                    heading: "Informasi",
                    text: "Kode Ads berhasil disalin!",
                    showHideTransition: "slide",
                    icon: "success",
                    position: "top-right",
                    loaderBg: "#def7f0",
                    hideAfter: 2000,
                });
            } else if (trigger) {
                trigger.classList.add('text-success');
                setTimeout(function() { trigger.classList.remove('text-success'); }, 1200);
            }
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done).catch(function() {
                var temp = document.createElement('textarea');
                temp.value = text;
                temp.setAttribute('readonly', '');
                temp.style.position = 'absolute';
                temp.style.left = '-9999px';
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
                done();
            });
        } else {
            var temp = document.createElement('textarea');
            temp.value = text;
            temp.setAttribute('readonly', '');
            temp.style.position = 'absolute';
            temp.style.left = '-9999px';
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            done();
        }
    }

    $(document).off('click', '.btn-copy-kode').on('click', '.btn-copy-kode', function(e) {
        e.preventDefault();
        var kode = $(this).data('kode');
        copyKodeAds(kode, this);
    });
</script>

<script>
    function extract_tiktok_video_id(rawUrl) {
        if (!rawUrl) return '';
        var url = String(rawUrl).trim();
        var match =
            url.match(/\/video\/(\d+)/i) ||
            url.match(/\/v\/(\d+)/i) ||
            url.match(/\/(\d+)\.html/i);
        return match ? match[1] : '';
    }

    function extract_tiktok_photo_id(rawUrl) {
        if (!rawUrl) return '';
        var url = String(rawUrl).trim();
        var match = url.match(/\/photo\/(\d+)/i);
        return match ? match[1] : '';
    }

    function build_tiktok_video_html(playUrl) {
        if (!playUrl) return '';
        return '' +
            '<div style="width:320px; height:520px; overflow:hidden; border-radius:8px; background:#fff;">' +
            '  <video src="' + playUrl + '" style="width:100%; height:100%; object-fit:cover;" controls autoplay muted loop playsinline preload="metadata"></video>' +
            '</div>';
    }

    function fetch_tiktok_video_with_retry(url, attempt, onSuccess, onFail) {
        var maxAttempts = 5;
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
                        fetch_tiktok_video_with_retry(url, attempt + 1, onSuccess, onFail);
                    }, 400);
                } else {
                    onFail();
                }
            },
            error: function() {
                if (attempt < maxAttempts) {
                    setTimeout(function() {
                        fetch_tiktok_video_with_retry(url, attempt + 1, onSuccess, onFail);
                    }, 400);
                } else {
                    onFail();
                }
            }
        });
    }

    function build_tiktok_photo_carousel(images, uid) {
        if (!images || !images.length) return '';
        var dots = images.map(function(_, i) {
            return '<span class="tt-dot ' + (i === 0 ? 'active' : '') + '" data-idx="' + i + '"></span>';
        }).join('');
        var imagesHtml = images.map(function(u) {
            return '<span data-src="' + u + '"></span>';
        }).join('');
        return '' +
            '<div class="tt-carousel" data-uid="' + uid + '" data-total="' + images.length + '" data-index="0" style="width:320px;">' +
            '  <div class="tt-carousel-frame"><img src="' + images[0] + '" alt="TikTok Photo" /></div>' +
            '  <div class="tt-carousel-controls">' +
            '    <button type="button" class="tt-btn" data-dir="-1">‹</button>' +
            '    <div class="tt-dots">' + dots + '</div>' +
            '    <button type="button" class="tt-btn" data-dir="1">›</button>' +
            '  </div>' +
            '  <div class="tt-counter">1 / ' + images.length + '</div>' +
            '  <div class="tt-images" style="display:none;">' + imagesHtml + '</div>' +
            '</div>';
    }

    function init_tiktok_photo_carousel(container, autoSlideMs = 2500) {
        var root = container.querySelector('.tt-carousel');
        if (!root) return;
        var images = Array.prototype.slice.call(root.querySelectorAll('.tt-images span')).map(function(s) {
            return s.getAttribute('data-src');
        });
        var frameImg = root.querySelector('.tt-carousel-frame img');
        var dots = Array.prototype.slice.call(root.querySelectorAll('.tt-dot'));
        var counter = root.querySelector('.tt-counter');
        var total = images.length;
        var timerId = null;

        function setIndex(nextIndex) {
            var idx = nextIndex;
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
                var dir = Number(this.getAttribute('data-dir')) || 0;
                var current = Number(root.getAttribute('data-index')) || 0;
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
                var current = Number(root.getAttribute('data-index')) || 0;
                setIndex(current + 1);
            }, autoSlideMs);
            root.dataset.timerId = String(timerId);
        }
    }

    $(document).ready(function() {
        var tiktokTriggers = document.querySelectorAll('.tiktok-embed-trigger');
        if (!tiktokTriggers.length) return;
        tiktokTriggers.forEach(function(el) {
            if (el._tippy) el._tippy.destroy();
        });

        tippy(tiktokTriggers, {
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
            onShow: function(instance) {
                var url = instance.reference.getAttribute('data-tiktok-url');
                var photoId = extract_tiktok_photo_id(url);
                if (photoId) {
                    instance.setContent('<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat foto...</div></div>');
                    $.ajax({
                        url: '<?= base_url() ?>endorse/get_tiktok_photo_images',
                        type: 'POST',
                        data: { content_id: photoId, url: url },
                        dataType: 'json',
                        success: function(res) {
                            if (res && res.status && Array.isArray(res.data) && res.data.length) {
                                var uid = 'ttc-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
                                instance.setContent(build_tiktok_photo_carousel(res.data, uid));
                                setTimeout(function() {
                                    init_tiktok_photo_carousel(instance.popper);
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
                fetch_tiktok_video_with_retry(url, 1, function(playUrl) {
                    instance.setContent(build_tiktok_video_html(playUrl));
                    setTimeout(function() {
                        var vid = instance.popper.querySelector('video');
                        if (vid) vid.play().catch(function(){});
                    }, 0);
                }, function() {
                    instance.setContent('<div class="p-2 text-muted">Video TikTok tidak ditemukan dari link upload.</div>');
                });
            },
            onHidden: function(instance) {
                var root = instance.popper.querySelector('.tt-carousel');
                if (root && root.dataset.timerId) {
                    clearInterval(Number(root.dataset.timerId));
                    delete root.dataset.timerId;
                }
            }
        });
    });
</script>
