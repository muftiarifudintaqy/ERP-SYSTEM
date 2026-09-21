<?php
$k = $start;
$is_internal = (int)($is_internal ?? 0) === 1;
function separator_only($angka) {
    $number = doubleval($angka);
    return number_format(round($number), 0, ',', '.');
}

function normalize_whatsapp_link($val) {
    $val = trim((string)$val);

    if (preg_match('~^\+?\d{8,15}$~', $val)) {
        return 'https://wa.me/' . ltrim($val, '+');
    }

    if (preg_match('~^(wa\.me/|api\.whatsapp\.com/)~i', $val)) {
        return 'https://' . $val;
    }

    if (!preg_match('~^https?://~i', $val)) {
        return 'https://' . $val;
    }

    return $val;
}

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


$allowed_views = ['card', 'table'];
$view_param = $_GET['view'] ?? 'table';
$view = in_array($view_param, $allowed_views, true) ? $view_param : 'table';

if (isset($notif)) {
    echo '<div id="item-notif" style="display:none;">' . $notif . '</div>';
}

if ($view == 'table') {
    $this->load->view('endorse/item_table', $data);
    ?>
    <?php
} else {
    // CARD VIEW (default)
    foreach ($data as $v) {
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
            $v['barang_dikirim_at'] = DATE("d/m/Y H:i", strtotime($v['barang_dikirim_at']));
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
        } else if (in_array($v['status_endorse'], array('Reject'))) {
            $bg = '#ea7b7b';
        }

        if (in_array($v['status_payment'], array('DP'))) {
            $bg_payment = '#60bb55';
            $clr_payment = '#000';
        } else if (in_array($v['status_payment'], array('FP'))) {
            $bg_payment = '#8CCDEB';
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
            $v['img_creator_1'] = '<a href="' . $creator_url . '" target="_blank" rel="noopener noreferrer"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img_creator_1'] . '"></a>';
        } else {
            $v['img_creator_1'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img_creator_1'] . '">';
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
            $v['img_creator_2'] = '<a href="' . htmlspecialchars($contactUrl, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img_creator_2'] . '"></a>';
        } else {
            $v['img_creator_2'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img_creator_2'] . '">';
        }

        $current_url = $_SERVER['REQUEST_URI'];

        if (strpos($current_url, 'endorse/item-endorse') !== false) {
            $display_k = $k + 1;
        } else {
            $display_k = $k;
        }
    ?>
    <div class="card mb-3" style="padding-bottom:0px">
        <div class="row">
            <div class="col-lg-4">
                <a class="mb-1 text-blue fw-700 fs-16 a-none" href="<?= base_url() ?>endorse/detail?id=<?= $v['id'] ?>">#<?= $display_k ?></a>
                <p class="mb-1 text-black fw-700 fs-16"><?= $v['nama_creator'] ?> <i class="bi bi-eye text-blue eye-card-trigger" data-influencer-id="<?= $v['influencer'] ?>"></i></p>

                <div class="col-lg-12 mt-0 mb-3">
                    <?= $v['img_creator_1'] ?>
                    <?= $v['img_creator_2'] ?>
                </div>
                <p class="mb-1 text-black">PIC : <?= $v['pic'] ?></p>
                <div class="d-flex align-items-center" style="gap: 5px; margin-top:6px!important;">
                    <?php if (!$is_internal): ?>
                    <p class="mb-0 br-10 fs-12 text-white" style="background-color:<?= $bg ?>!important; color:<?= $clr ?>!important; padding: 5px 10px;">
                        <?= strtoupper(strtolower($v['status_endorse'])) ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($v['status_payment'])): ?>
                        <p class="mb-0 br-10 fs-12 text-white" style="background-color:<?= $bg_payment ?>!important; color:<?= $clr_payment ?>!important; padding: 5px 10px;">
                            <?= strtoupper(strtolower($v['status_payment'])) ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($v['pengajuan_status_payment'])): ?>
                        <p class="mb-0 br-10 fs-12 text-white" style="background-color:<?= $bg_payment ?>!important; color:<?= $clr_payment ?>!important; padding: 5px 10px;">
                            <?= strtoupper(strtolower($v['pengajuan_status_payment'])) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4 mt-lg-5 mt-0">
                <?php if (!$is_internal): ?>
                <p class="mb-1 text-black fw-600">CPM : <?= separator_only($creator['cpm_2']) ?></p>
                <?php endif; ?>
                <p class="mb-1 text-black">AVG Interaksi : <?= separator_only($creator['avg_interaksi_2']) ?></p>
                <p class="mb-1 text-black">AVG View : <?= separator_only($creator['avg_view_2']) ?></p>
            </div>
            <div class="col-lg-4 text-lg-end text-start">
                <?php if (!in_array($v['status_payment'], ['DP','FP'], true)) : ?>
                <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn btn-delete mt-0 mb-2"><i class="bi bi-trash fs-16"></i> Delete Data</a>
                <?php endif; ?>

                <?php if ($is_limited_refresh_locked): ?>
                <a href="#!" class="btn btn-sync disabled ms-1 mt-0 mb-2" aria-disabled="true" tabindex="-1" title="Refresh dinonaktifkan karena konten sudah melewati batas update campaign"><i class="bi bi-bootstrap-reboot fs-16"></i> Refresh</a>
                <?php elseif ($v['status'] == "Aktif" && $v['status_campaign'] == "Aktif" && $v['link_upload'] != "" && $v['is_manual_update'] == 0): ?>
                <a href="#!" onclick="sync('<?= $v['id'] ?>')" class="btn btn-sync ms-1 mt-0 mb-2"><i class="bi bi-bootstrap-reboot fs-16"></i> Refresh</a>
                <?php endif; ?>
                <a href="#!" onclick="clone('<?= $v['id'] ?>')" class="btn btn-copy ms-1 mt-0 mb-2"><i class="bi bi-copy fs-16"></i> Kloning</a>
                <a href="#!" onclick="edit('<?= $v['id'] ?>')" class="btn btn-edit  mt-0 ms-1 mb-2"><i class="bi bi-pencil-square fs-16"></i> Edit Data</a>
                <?php if (!$is_internal && $v['link_mou'] == '-' && $v['is_generated_mou'] == 0) { ?>
                    <a href="#!" onclick="generate_mou('<?= $v['id'] ?>')" class="btn btn-sync  mt-0 ms-1 mb-2"><i class="bi bi-clipboard2-plus fs-16"></i> Generate MOU</a>
                <?php } ?>
                <?php if (!$is_internal): ?>
                <a href="#!" onclick="set_payment(<?= $v['id'] ?>)" class="btn btn-sync mt-0 mb-2">
                    <i class="bi bi-clipboard2-check fs-16"></i> Ajukan Payment
                </a>
                <?php endif; ?>

                <?php if (!empty($v['pengajuan_status_payment'])): ?>
                    <a href="#!" onclick="set_batalkan_payment(<?= $v['id'] ?>)" class="btn btn-delete mt-0 mb-2">
                        <i class="bi bi-clipboard2-x fs-16"></i> Batalkan Payment
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-lg-12">
                <hr>
            </div>
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-md-4">
                        <?php if (!$is_internal): ?>
                        <p class="mb-1 text-black">Total Cost : <?= separator_only($v['total_cost']) ?></p>
                        <p class="mb-1 text-black">
                            Barang Dikirim :
                            <span class="badge <?= $v['barang_dikirim_at'] != '-' ? 'bg-success text-success' : 'bg-secondary' ?>">
                                <?= $v['barang_dikirim_at'] != '-' ? $v['barang_dikirim_at'] : 'Belum Dikirim' ?>
                            </span>
                        </p>
                        <p class="mb-1 text-black">Rencana Upload : <?= $v['rencana_at'] ?></p>
                        <?php endif; ?>
                        <p class="mb-1 text-black">Tanggal Posting : <?= $v['posting_at'] ?></p>
                        <p class="mb-1 text-black">Status : <?= $v['status'] ?></p>
                        <p class="mb-1">Produk : <?= $v['product_text'] ?></p>
                        <?php if (!$is_internal): ?>
                        <p class="mb-1">Ket : <?= $v['desc'] ?></p>
                        <p class="mb-1">Ads : <?= $v['kode_ads'] ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-black fw-600">Views : <span id="views_val_<?= $k ?>"><?= separator_only($v['views']) ?></span></p>
                        <?php if (!$is_internal): ?>
                        <p class="mb-1 text-black fw-600">CPM : <span id="cpm_val_<?= $k ?>"><?= separator_only($v['cpm']) ?></span></p>
                        <?php endif; ?>
                        <p class="mb-1 text-black">Likes : <span id="likes_val_<?= $k ?>"><?= separator_only($v['likes']) ?></span></p>
                        <p class="mb-1 text-black">Comments : <span id="comment_val_<?= $k ?>"><?= separator_only($v['comment']) ?></span></p>
                        <p class="mb-1 text-black">Save & Share : <span id="share_save_val_<?= $k ?>"><?= separator_only($v['share_save']) ?></span></p>
                        <p class="mb-1 text-black">Tanggal Dibuat : <?= $v['created_at'] ?></p>
                        <p class="mb-1 text-black">Tanggal Diupdate : <?= $v['sync_at'] ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-black">Link Brief : <br><?= $v['link_brief'] ?></p>
                        <p class="mb-1 text-black">
                            Link MOU : <br>
                            <?= $v['link_mou'] ?>
                            <?php if ($v['is_generated_mou'] == 1): ?>
                                <span class="badge bg-success text-success ms-2">MOU Generated</span>
                            <?php endif; ?>
                        </p>


                        <p class="mb-1 text-black">Ket. Payment : <br><?= $v['keterangan_payment'] ?></p>
                        <a href="<?= base_url('endorse/payment_logs?id_campaign=' . $v['id_campaign'] . '&nama_creator=' . urlencode($v['nama_creator'])) ?>"
                            target="_blank">
                            Lihat Logs Payment
                        </a>
                    </div>
                    <div class="col-lg-12 mt-3">
                        <div class="row">
                            <div class="col-12 mb-2" style="position:relative">
                                <div class="row">
                                    <div class="firstDivImg" style="position: relative;">
                                        <?= $v['img'] ?>
                                    </div>
                                    <div class="secondDivImg" style="margin-top: -10px;margin-left:-20px;">
                                        <a href="#!" onclick="change_fyp_<?= $k ?>()" id="fyp_<?= $k ?>" class="">
                                            <?php if ($v['is_fyp'] == 1) { ?>
                                                <i class="bi bi-star-fill" style="font-size:40px;color:#ffd250"></i>
                                            <?php } else { ?>
                                                <i class="bi bi-star" style="font-size:40px;color:#000"></i>
                                            <?php } ?>
                                            <script>
                                                function change_fyp_<?= $k ?>() {
                                                    $('#fyp_<?= $k ?>').html('<i style="margin-top: 10px;margin-bottom:10px;font-size:40px;color:#000" class="fa fa-circle-o-notch fa-spin"></i>');
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
                                    <div class="">
                                        <?php if ($v['status_endorse'] === 'Posted Content' && (int)$v['views'] === 0 || $v['is_manual_update'] == 1): ?>
                                        <a href="#!" id="update_stats_<?= $k ?>" onclick="toggle_update_stats(<?= $k ?>)" class="btn btn-sync me-3" style="position:absolute; top:0; right:0;">
                                            <i class="bi bi-database-add text-success" style="font-size:18px"></i>
                                        </a>
                                        <div id="update_stats_popover_<?= $k ?>" class="update-stats-popover" style="display:none; position:absolute; top:-150px; right:70px; z-index:20; width:230px; background:#fff; border:1px solid #e5e7eb; box-shadow:0 6px 16px rgba(0,0,0,0.12); border-radius:8px; padding:10px;">
                                            <div class="mb-2 fw-600 fs-12">Update Stats</div>
                                            <div class="mb-2">
                                                <label class="fs-12 mb-1">Views</label>
                                                <input type="text" inputmode="numeric" class="form-control stats-input" style="height:25px; !important" id="update_views_<?= $k ?>" value="<?= separator_only($v['views']) ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="fs-12 mb-1">Likes</label>
                                                <input type="text" inputmode="numeric" class="form-control stats-input" style="height:25px; !important" id="update_likes_<?= $k ?>" value="<?= separator_only($v['likes']) ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="fs-12 mb-1">Comments</label>
                                                <input type="text" inputmode="numeric" class="form-control stats-input" style="height:25px; !important" id="update_comment_<?= $k ?>" value="<?= separator_only($v['comment']) ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="fs-12 mb-1">Save & Share</label>
                                                <input type="text" inputmode="numeric" class="form-control stats-input" style="height:25px; !important" id="update_share_save_<?= $k ?>" value="<?= separator_only($v['share_save']) ?>">
                                            </div>
                                            <div class="d-flex justify-content-end" style="gap:6px;">
                                                <button type="button" class="btn btn-light fs-12" style="height: 36px !important; padding: 0 12px !important;" onclick="toggle_update_stats(<?= $k ?>)">Batal</button>
                                                <button type="button" class="btn btn-primary fs-12" style="height: 36px !important; padding: 0 12px !important;" onclick="save_update_stats(<?= $k ?>, <?= (int)$v['id'] ?>)">Simpan</button>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <div class="checkbox-wrapper-13 d-inline">
                                <input class="checkItem" style="" type="checkbox" value="<?= $v['id'] ?>" data-id="<?= $k ?>" name="list_id" form="form-action">
                            </div>
                            <input type="hidden" value="<?= $v['is_manual'] ?>" name="is_manual[<?= $k - $start ?>]" form="form-action">
                            <input type="hidden" value="<?= $v['marketplace'] ?>" name="marketplace[<?= $k - $start ?>]" form="form-action">
                            <input type="hidden" value="<?= $v['brand'] ?>" name="brand[<?= $k - $start ?>]" form="form-action">
                            <input type="hidden" value="<?= $v['order_id'] ?>" name="order_id[<?= $k - $start ?>]" form="form-action">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php $k += 1;
    }
}
?>

<style>
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
</style>

<script type="text/javascript">
    var endorseIsInternal = <?= $is_internal ? 'true' : 'false' ?>;

    function format_number_id(val) {
        const num = Number(val || 0);
        if (Number.isNaN(num)) return '0';
        return new Intl.NumberFormat('id-ID').format(num);
    }

    function toggle_update_stats(k) {
        const pop = $('#update_stats_popover_' + k);
        $('.update-stats-popover').not(pop).hide();
        pop.toggle();
    }

    function normalize_stats_value(val) {
        return String(val || '').replace(/[^\d]/g, '');
    }

    function apply_thousands_separators(el) {
        const raw = normalize_stats_value(el.value);
        const num = raw === '' ? 0 : Number(raw);
        el.value = format_number_id(num);
    }

    function save_update_stats(k, idEndorse) {
        const payload = {
            id_endorse: idEndorse,
            views: normalize_stats_value($('#update_views_' + k).val()),
            likes: normalize_stats_value($('#update_likes_' + k).val()),
            comment: normalize_stats_value($('#update_comment_' + k).val()),
            share_save: normalize_stats_value($('#update_share_save_' + k).val())
        };

        const $btn = $('#update_stats_popover_' + k).find('button.btn-primary');
        $btn.prop('disabled', true).text('Menyimpan...');

        $.ajax({
            type: 'POST',
            url: '<?= base_url() ?>endorse/update_stats',
            dataType: 'json',
            data: payload,
            success: function(res) {
                if (res && res.status) {
                    $('#views_val_' + k).text(format_number_id(res.data.views));
                    $('#likes_val_' + k).text(format_number_id(res.data.likes));
                    $('#comment_val_' + k).text(format_number_id(res.data.comment));
                    $('#share_save_val_' + k).text(format_number_id(res.data.share_save));
                    $('#cpm_val_' + k).text(format_number_id(res.data.cpm));
                    toggle_update_stats(k);
                    $('#update_stats_' + k).hide();
                } else {
                    alert((res && res.message) ? res.message : 'Gagal menyimpan data.');
                }
            },
            error: function() {
                alert('Gagal menyimpan data.');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Simpan');
            }
        });
    }

    $('input[name="list_id"]').change(function() {
        get_id();
    });

    $(document).on('input', '.stats-input', function() {
        apply_thousands_separators(this);
    });

    function extract_tiktok_video_id(rawUrl) {
        if (!rawUrl) return '';
        const url = String(rawUrl).trim();
        const match =
            url.match(/\/video\/(\d+)/i) ||
            url.match(/\/v\/(\d+)/i) ||
            url.match(/\/(\d+)\.html/i);
        return match ? match[1] : '';
    }

    function extract_tiktok_photo_id(rawUrl) {
        if (!rawUrl) return '';
        const url = String(rawUrl).trim();
        const match = url.match(/\/photo\/(\d+)/i);
        return match ? match[1] : '';
    }

    function build_tiktok_video_html(playUrl) {
        if (!playUrl) return '';
        return `
            <div style="width:320px; height:520px; overflow:hidden; border-radius:8px; background:#fff;">
                <video
                    src="${playUrl}"
                    style="width:100%; height:100%; object-fit:cover;"
                    controls
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="metadata"
                ></video>
            </div>
        `;
    }

    function fetch_tiktok_video_with_retry(url, attempt, onSuccess, onFail) {
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
    }

    function init_tiktok_photo_carousel(container, autoSlideMs = 2500) {
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
    }

    $(document).ready(function() {
        const tiktokTriggers = document.querySelectorAll('.tiktok-embed-trigger');
        if (tiktokTriggers.length) {
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
                onShow(instance) {
                    const url = instance.reference.getAttribute('data-tiktok-url');
                    const photoId = extract_tiktok_photo_id(url);
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
                                    instance.setContent(build_tiktok_photo_carousel(res.data, uid));
                                    setTimeout(function() {
                                        init_tiktok_photo_carousel(instance.popper, 2500);
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
        }

        $('.eye-card-trigger').each(function() {
            const eyeIcon = this;
            const influencerId = $(eyeIcon).data('influencer-id');
            
            tippy(eyeIcon, {
                content: '<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat data...</div></div>',
                allowHTML: true,
                interactive: true,
                placement: 'right',
                theme: 'light',
                maxWidth: 350,
                onShow(instance) {
                    $.ajax({
                        url: '<?= base_url() ?>endorse/get_influencer_data',
                        type: 'POST',
                        data: { id_influencer: influencerId },
                        dataType: 'json',
                        success: function(data) {
                            if (data) {
                                const cpmRow = endorseIsInternal ? '' : `
                                        <div style="display: flex; justify-content: space-between;">
                                            <span class="text-secondary fw-bold">CPM</span>
                                            <span>${data.cpm || 0}</span>
                                        </div>`;
                                const tooltipContent = `
                                    <div class="report-card p-2" style="min-width: 250px; line-height: 1.5; font-size: 13px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <span class="text-secondary fw-bold">Total Endorse</span>
                                            <span>${data.endorse_count || 0}</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <span class="text-secondary fw-bold">Avg. Views</span>
                                            <span>${data.avg_views || 0}</span>
                                        </div>
                                        ${cpmRow}
                                    </div>
                                `;
                                instance.setContent(tooltipContent);
                            } else {
                                throw new Error("Data tidak valid");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            instance.setContent(`
                                <div class="p-2 text-danger">
                                    Gagal memuat data influencer
                                    <div class="small mt-2">${error}</div>
                                </div>
                            `);
                        }
                    });
                }
            });
        });

        $('.eye-list-trigger').each(function() {
            const eyeIcon = this;
            const influencerId = $(eyeIcon).data('influencer-id');
            
            tippy(eyeIcon, {
                content: '<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat data...</div></div>',
                allowHTML: true,
                interactive: true,
                placement: 'right',
                theme: 'light',
                maxWidth: 700,
                onShow(instance) {
                    $.ajax({
                        url: '<?= base_url() ?>endorse/get_influencer_data_all',
                        type: 'POST',
                        data: { id_influencer: influencerId },
                        dataType: 'json',
                        success: function(data) {
                            if (data) {
                                const cpmInternalRow = endorseIsInternal ? '' : `
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span class="text-secondary">CPM</span>
                                                    <span style="white-space: nowrap;">${data.cpm || 0}</span>
                                                </div>`;
                                const cpmEksternalRow = endorseIsInternal ? '' : `
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span class="text-secondary">CPM</span>
                                                    <span style="white-space: nowrap;">${data.cpm_2 || 0}</span>
                                                </div>`;
                                const tooltipContent = `
                                    <div class="report-card p-2" style="width: 100%; max-width: 600px; line-height: 1.5; font-size: 13px;">
                                        <div style="display: flex; flex-direction: row; align-items: flex-start; gap: 10px;">

                                            <!-- Internal Section -->
                                            <div style="flex: 1; min-width: 150px;">
                                                <div class="text-secondary fw-bold" style="margin-bottom: 5px;">Internal (${data.endorse_count || 0})</div>
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span class="text-secondary">Avg. Views</span>
                                                    <span style="white-space: nowrap;">${data.avg_views || 0}</span>
                                                </div>
                                                ${cpmInternalRow}
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span class="text-secondary">ER</span>
                                                    <span style="white-space: nowrap;">${data.er || 0}</span>
                                                </div>
                                            </div>

                                            <!-- Eksternal Section -->
                                            <div style="flex: 1; min-width: 150px; border-left: 1px solid #ccc; padding-left: 15px;">
                                                <div class="text-secondary fw-bold" style="margin-bottom: 5px;">Eksternal</div>
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span class="text-secondary">Avg. Views</span>
                                                    <span style="white-space: nowrap;">${data.avg_views_2 || 0}</span>
                                                </div>
                                                ${cpmEksternalRow}
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span class="text-secondary">ER</span>
                                                    <span style="white-space: nowrap;">${data.er_2 || 0}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    `;



                                instance.setContent(tooltipContent);
                            } else {
                                throw new Error("Data tidak valid");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            instance.setContent(`
                                <div class="p-2 text-danger">
                                    Gagal memuat data influencer
                                    <div class="small mt-2">${error}</div>
                                </div>
                            `);
                        }
                    });
                }
            });
        });
    });

</script>
