<?php

if ($_GET['start_date']) {
    $start_date = $_GET['start_date'];
} else {
    $start_date = date('Y-m-01');
}
if ($_GET['until_date']) {
    $until_date = $_GET['until_date'];
} else {
    $until_date = DATE('Y-m-d');
}

$rts_start_raw = $_GET['rts_start'] ?? '';
$rts_end_raw = $_GET['rts_end'] ?? '';
$rts_date_raw = $_GET['rts_date'] ?? '';

$rts_start_value = '';
$rts_end_value = '';

if ($rts_start_raw) {
    $ts = strtotime($rts_start_raw);
    if ($ts) {
        $rts_start_value = date('Y-m-d\TH:i', $ts);
    }
}

if ($rts_end_raw) {
    $ts = strtotime($rts_end_raw);
    if ($ts) {
        $rts_end_value = date('Y-m-d\TH:i', $ts);
    }
}

if (!$rts_start_value && $rts_date_raw) {
    $ts = strtotime($rts_date_raw . ' 00:00');
    if ($ts) {
        $rts_start_value = date('Y-m-d\TH:i', $ts);
    }
}

if (!$rts_end_value && $rts_date_raw) {
    $ts = strtotime($rts_date_raw . ' 23:59');
    if ($ts) {
        $rts_end_value = date('Y-m-d\TH:i', $ts);
    }
}
?>
<script>
(function () {
  // --- helpers URL ---
  function delAll(p, base){ p.delete(base); p.delete(base+'[]'); }
  function hasActiveFiltersSearch(searchStr){
    const p = new URLSearchParams(searchStr || '');
    const ff = p.getAll('filter_field').length || p.getAll('filter_field[]').length;
    const fv = p.getAll('filter_value').length || p.getAll('filter_value[]').length;
    return (ff > 0 && fv > 0);
  }
  function isHardReload(){
    try {
      const nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
      if (nav) return nav.type === 'reload' || nav.type === 'back_forward';
      return performance.navigation && performance.navigation.type === 1; // fallback lama
    } catch { return false; }
  }

  // Hindari prompt berulang kalau user pilih "Tidak"
  const SKIP_KEY = 'allowFilteredReloadOnce';

  // Jalankan seawal mungkin (blocking)
  if (isHardReload() && hasActiveFiltersSearch(location.search) && !sessionStorage.getItem(SKIP_KEY)) {
    var ok = confirm('Filter sedang aktif.\nReset semua filter sebelum memuat ulang?');
    if (ok) {
      // bersihkan filter dari URL lalu ganti halaman (tanpa tambah history)
      const p = new URLSearchParams(location.search);
      delAll(p,'filter_field'); delAll(p,'filter_value'); delAll(p,'filter_operator');
      p.set('page','1');
      // kalau mau sekaligus reset sort: 
      // p.delete('sort_column'); p.delete('sort_order');

      // cegah “kedip”: kosongkan dokumen lalu redirect
      try {
        document.open(); document.write(''); document.close();
      } catch(e){}
      location.replace(location.pathname + '?' + p.toString());
      // stop eksekusi lebih lanjut
      throw new Error('ABORT_INITIAL_LOAD_AFTER_CONFIRM_REDIRECT');
    } else {
      // jangan tanya lagi untuk reload ini (satu kali)
      sessionStorage.setItem(SKIP_KEY, '1');
    }
  } else {
    // reset flag di load normal berikutnya
    if (sessionStorage.getItem(SKIP_KEY)) sessionStorage.removeItem(SKIP_KEY);
  }
})();
</script>

<style>
    #agGridWrapper { height: 70vh; position: relative; margin-bottom: 10px; }
    .badge { padding: 2px 6px; border-radius: 10px; font-size: 12px; }
    .bg-green{background:#28a745;color:#fff}
    .bg-red{background:#dc3545;color:#fff}
    .bg-blue{background:#0d6efd;color:#fff}
    .bg-grey{background:#6c757d;color:#fff}
    /* dropdown header filter */
    .hdr-filter {
      position: relative;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      cursor: default;
    }
    .hdr-filter .btn-filter {
      border: none; background: transparent; padding: 0; cursor: pointer;
    }
    .hdr-dd {
      display:none; position: absolute; top: 100%; left: 0;
      z-index: 1000; background: #fff; border: 1px solid #ddd;
      padding: 8px; min-width: 240px; max-height: 260px; overflow: auto;
      box-shadow: 0 8px 20px rgba(0,0,0,.12); border-radius: 6px;
    }
    .ag-theme-quartz .ag-header-cell.sortable { cursor: pointer; position: relative; }
    .ag-theme-quartz .ag-header-cell.sortable:hover { background-color: #f8f9fa; }
    .ag-theme-quartz .ag-header-cell.sortable i { font-size: 0.8em; margin-left: 6px; }
    .hdr-filter .hdr-title { cursor: pointer; user-select: none; }
    .hdr-dd.open { display:block; }
    .hdr-dd .search { width:80%; margin-bottom:6px; }
    .hdr-dd .actions { display:flex; justify-content: space-between; margin-top:6px; }
    /* Dropdown filter di header */
    .hdr-dd-portal{
    position: absolute;
    z-index: 1000;
    width: 260px;                 /* boleh ubah sesuai selera */
    max-height: 340px;            /* total tinggi dropdown */
    background: #fff;
    border: 1px solid #e6e6e6;
    border-radius: 10px;
    box-shadow: 0 6px 18px rgba(0,0,0,.12);
    display: flex;
    flex-direction: column;
    }

    /* kotak cari di atas */
    .hdr-dd-portal .search{
    margin: 8px !important;
    width: 240px !important;
    }

    /* daftar nilai: auto-scroll, ambil sisa tinggi */
    .hdr-dd-portal .list{
    padding: 8px;
    overflow: auto;
    flex: 1 1 auto;
    }

    /* area tombol: sticky di bawah, selalu terlihat */
    .hdr-dd-portal .actions{
    position: sticky;
    bottom: 0;
    background: #fff;
    padding: 6px 8px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 6px;
    justify-content: flex-end;
    }

    /* tombol ekstra kecil (lebih kecil dari .btn-sm) */
    .btn-2xs{
    padding: 8px 16px !important;
    font-size: 12px !important;
    line-height: 1.2 !important;
    border-radius: 6px !important;
    }

    /* checkbox list rapi */
    .hdr-dd-portal .list label{
    font-size: 12px;
    cursor: pointer;
    }
    .hdr-dd-portal .list input[type="checkbox"]{
    margin-right: 6px;
    }

    
    /* Styling untuk column chooser */
    #colChooser {
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    #colChooser .column-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    #colChooser label {
        display: flex;
        align-items: center;
        margin-bottom: 0;
        cursor: pointer;
        padding: 5px 10px;
        background: white;
        border-radius: 4px;
        border: 1px solid #dee2e6;
    }
    #colChooser label:hover {
        background: #e9ecef;
    }

    .floating-actions {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 1100;
    }

    .selected-counter-floating {
        position: static;
        background: #0d6efd;
        color: #fff;
        padding: 10px 14px;
        border-radius: 12px;
        box-shadow: 0 10px 28px rgba(0,0,0,0.12);
        font-weight: 600;
        display: none;
        align-items: center;
        gap: 8px;
    }

    .selected-counter-floating.show {
        display: inline-flex;
    }

    .shipping-action-bar {
        position: static;
        display: none;
        align-items: center;
        gap: 12px;
        background: #ffffff;
        border-radius: 14px;
        padding: 10px 16px;
        box-shadow: 0 10px 28px rgba(0,0,0,0.12);
    }

    .shipping-action-bar.shipping-action-bar--show {
        display: flex;
    }

    .shipping-action-bar button {
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
            padding: 10px 16px;
            cursor: pointer;
            transition: all .2s ease;
        }

    .shipping-action-bar__primary {
        background: #067dbdff;
        color: #ffffff;
    }

    .shipping-action-bar__primary[disabled],
    .shipping-action-bar__primary.shipping-action-bar__primary--disabled {
        background: #c8e2ffff;
        color: #fff;
        cursor: not-allowed;
        opacity: 0.85;
    }

    .shipping-action-bar__primary:hover {
        background: #2ab4ffff;
    }

    .shipping-action-bar__secondary {
        background: #e9ecef;
        color: #333;
    }

    .shipping-action-bar__secondary:hover {
        background: #dfe3e6;
    }

    .shipping-action-bar__more {
        background: #f5f5f5;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .shipping-action-bar__more:hover {
        background: #e9e9e9;
    }

    /* Tambahkan di CSS Anda */
    .filter-badge {
        font-size: 0.6em !important;
        padding: 0.25em 0.4em !important;
    }

    .hdr-filter .btn-filter {
        background: none;
        border: none;
        padding: 0.25rem;
        margin-left: 0.5rem;
        opacity: 0.6;
        transition: opacity 0.2s;
    }

    .hdr-filter .btn-filter:hover {
        opacity: 1;
    }

    .hdr-filter.active .btn-filter {
        opacity: 1;
        color: #0d6efd;
    }

    .filter-section {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
</style>
<div class="form-message"></div>
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">ORDER</h3>
        </div>
        <div class="col-lg-12">
            <form action="">
                <div class="row">

                    <div class="col-md-12">
                        <?php

                        $arr_val = array();
                        $arr_val[] = '';
                        $arr_val[] = 'ACTIVE';
                        $arr_val[] = 'UNPAID';
                        // $arr_val[] = 'PENDING';
                        $arr_val[] = 'READY_TO_SHIP';
                        $arr_val[] = 'PROCESSED';
                        $arr_val[] = 'SHIPPED';
                        $arr_val[] = 'DELIVERED';
                        $arr_val[] = 'COMPLETED';
                        $arr_val[] = 'CANCELLED';
                        $arr_val[] = 'RETURN';
                        $arr_val[] = 'REFUND';
                        // $arr_val[] = 'WEBHOOK';

                        $arr = array();
                        $arr[] = 'Semua Order';
                        $arr[] = 'Order Aktif';
                        $arr[] = 'Belum Bayar';
                        // $arr[] = 'Order Menunggu Diproses';
                        $arr[] = 'Menunggu Diproses';
                        $arr[] = 'Diproses';
                        $arr[] = 'Pengiriman';
                        $arr[] = 'Diterima';
                        $arr[] = 'Selesai';
                        $arr[] = 'Dibatalkan';
                        $arr[] = 'Return';
                        $arr[] = 'Refund';
                        // $arr[] = 'Webhook';

                        // Support multi-select: order_status bisa comma-separated
                        $current_order_status = $_GET['order_status'] ?? '';
                        $selected_statuses = $current_order_status ? array_map('trim', explode(',', $current_order_status)) : [];

                        foreach ($arr_val as $k => $val) {
                            $is_semua = ($val === '');
                            $is_active = $is_semua
                                ? empty($selected_statuses)
                                : in_array($val, $selected_statuses);
                            $class = $is_active ? "btn-default-selected" : "btn-default";
                            $class_2 = $is_active ? "dot-active" : "dot";
                        ?>
                            <a href="#" class="btn mb-2 <?= $class ?> mb-2 me-2 order-status-btn" data-status="<?= $val ?>" onclick="toggleOrderStatus(this, event)"><span class="<?= $class_2 ?>"></span> <?= $arr[$k] ?></a>
                        <?php }  ?>
                        <script>
                        function toggleOrderStatus(el, e) {
                            e.preventDefault();
                            var status = el.getAttribute('data-status');
                            var params = new URLSearchParams(window.location.search);
                            var current = params.get('order_status') || '';
                            var selected = current ? current.split(',').filter(function(s){ return s !== ''; }) : [];

                            if (status === '') {
                                // "Semua Order" - reset
                                selected = [];
                            } else {
                                var idx = selected.indexOf(status);
                                if (idx > -1) {
                                    selected.splice(idx, 1);
                                } else {
                                    selected.push(status);
                                }
                            }

                            params.set('order_status', selected.join(','));
                            params.set('page', '1');
                            window.location.href = window.location.pathname + '?' + params.toString();
                        }
                        </script>
                        <div class="col-md-12"></div>
                        <?php
                        $arr = array();
                        $arr[] = 'Semua Tipe';
                        $arr[] = 'Manual';
                        $arr[] = 'Marketplace';
                        $arr[] = 'Belum Dikonfigurasi';

                        $arr_val = array();
                        $arr_val[] = '';
                        $arr_val[] = 'Manual';
                        $arr_val[] = 'Marketplace';
                        $arr_val[] = 'Belum Dikonfigurasi';
                        foreach ($arr_val as $k => $val) {
                            $class = "btn-default";
                            $class_2 = "dot";
                            if ($_GET['order_type'] == $val) {
                                $class = "btn-default-selected";
                                $class_2 = "dot-active";
                            }
                        ?>
                            <a href="<?= $url_3 ?>&order_type=<?= $val ?>" class="btn mb-2 <?= $class ?> mb-2 me-2"><span class="<?= $class_2 ?>"></span> <?= $arr[$k] ?></a>
                        <?php }  ?>

                        <?php
                        $arr = array();
                        $arr[] = 'Semua Pencairan';
                        $arr[] = 'Sudah Pencairan';
                        $arr[] = 'Belum Pencairan';

                        foreach ($arr as $k => $val) {
                            $class = "btn-default";
                            $class_2 = "dot";
                            if ($_GET['pencairan'] == $val) {
                                $class = "btn-default-selected";
                                $class_2 = "dot-active";
                            }
                        ?>
                            <a href="<?= $url_4 ?>&pencairan=<?= $val ?>" class="btn mb-2 <?= $class ?> mb-2 me-2"><span class="<?= $class_2 ?>"></span> <?= $val ?></a>
                        <?php }  ?>

                    </div>

                    <div class="col-lg-3">
                        <div class="input-group">
                            <button class="btn mb-2 btn-outline-secondary-category dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-top-right-radius: 0px !important;
                            border-bottom-right-radius: 0px !important;"><?= $keyword_category ?></button>
                            <ul class="dropdown-menu">
                                <?php
                                $arr = array();
                                $arr[] = 'Order ID';
                                $arr[] = 'Username';
                                $arr[] = 'Nama Pelanggan';
                                $arr[] = 'Nomor Pelanggan';
                                $arr[] = 'Nomor Resi';
                                $arr[] = 'Nama Produk';
                                foreach ($arr as $k => $val) {
                                    $class = "btn-default";
                                    if ($_GET['order_status'] == $val) {
                                        $class = "btn-default-selected";
                                    }
                                ?>
                                    <li><a class="dropdown-item" href="<?= $url_2 ?>&keyword_category=<?= $val ?>"><?= $val ?></a></li>
                                <?php }  ?>
                            </ul>
                            <input type="hidden" name="ids" value="<?= $ids ?>">
                            <input type="hidden" name="order_type" value="<?= $_GET['order_type'] ?>">
                            <input type="hidden" name="pencairan" value="<?= $_GET['pencairan'] ?>">
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?>">
                            <input type="hidden" name="order_status" value="<?= htmlspecialchars($_GET['order_status'] ?? '') ?>">
                            <input type="hidden" name="view" value="<?= htmlspecialchars($current_view ?? '') ?>">
                            <input type="hidden" name="c_type" value="<?= $c_type ?>">
                            <input type="text" name="keyword" class="form-control" value="<?= $_GET['keyword'] ?>" style="border-top-left-radius: 0px !important;
                            border-bottom-left-radius: 0px !important;">
                        </div>
                    </div>

                    <div class="col-lg-9 text-lg-end text-start">
                        <a href="#!" onclick="sync_data('<?= $start_date ?>','<?= $until_date ?>')" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-cloud-download fs-16"></i> Sync Data</a>
                        <a href="#!" onclick="download_file()" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-download fs-16"></i> Download</a>
                        <a href="#!" onclick="import_data()" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-cart2 fs-16"></i> Import Order</a>
                        <a href="#!" onclick="import_pencairan()" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-credit-card fs-16"></i> Import Pencairan Dana</a>
                        <a href="#!" onclick="import_resi()" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-truck fs-16"></i> Import Resi</a>
                        <a href="#!" onclick="import_customer()" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-people fs-16"></i> Import Pelanggan</a>
                        <a href="<?= base_url() ?>transaction/multi-return" class="btn mb-2 btn-delete px-2 mt-0 ms-1"><i class="bi bi-bag-dash"></i> Retur Order</a>
                        <a href="<?= base_url() ?>/transaction/create" class="btn mb-2 btn-edit-active px-2 mt-0 ms-1"><i class="bi bi-plus-circle-dotted fs-16"></i> Buat Order</a>
                    </div>
                    <div class="col-lg-12 mb-3">
                        <div class="card">
                            <h3 class="mb-0 text-notif">Filter Order</h3>
                            <hr>

                            <div class="row">
                                <!-- <div class="col-md-1">
                                    <label for="">Brand</label>
                                    <select class="form-control select2" name="brand" id="brand">
                                        <option value="">-</option>
                                        <?php
                                        foreach ($brands as $val) :
                                            $text = '';
                                            if ($_GET['brand'] == $val['code']) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val['code'] ?>"><?= $val['code'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div> -->
                                <div class="col-md-2">
                                    <label for="">Toko</label>
                                    <select class="form-control select2" name="shop_id">
                                        <option value="">-</option>
                                        <?php
                                        foreach ($store as $val) :
                                            $text = '';
                                            if ($_GET['shop_id'] == $val['id']) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val['id'] ?>"><?= $val['opt'] ?> <?= ucwords(strtolower($val['marketplace'])) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="">Channel</label>
                                    <select class="form-control select2" name="marketplace">
                                        <option value="">-</option>
                                        <!-- <option <?php if ($_GET['marketplace'] == 'UNCATEGORIZED') {
                                                            echo 'selected';
                                                        } ?> value="UNCATEGORIZED">UNCATEGORIZED</option> -->
                                        <?php
                                        foreach ($marketplace as $val) :
                                            $text = '';
                                            if ($_GET['marketplace'] == $val['name']) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val['name'] ?>"><?= $val['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="">Ekspedisi</label>
                                    <select class="form-control select2" name="ekspedisi">
                                        <option value="">-</option>
                                        <?php
                                        foreach ($shipping as $val) :
                                            $text = '';
                                            if ($_GET['ekspedisi'] == $val['name']) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val['name'] ?>"><?= $val['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="">CS</label>
                                    <select class="form-control select2" name="cs">
                                        <option value="">-</option>
                                        <?php
                                        foreach ($cs as $val) :
                                            $text = '';
                                            if ($_GET['cs'] == $val['code']) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val['code'] ?>"><?= $val['code'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="">TF/COD</label>
                                    <select class="form-control select2" name="payment_type">
                                        <option value="">-</option>
                                        <?php
                                        $arr = array();
                                        $arr[] = "TF";
                                        $arr[] = "COD";
                                        foreach ($arr as $val) :
                                            $text = '';
                                            if ($_GET['payment_type'] == $val) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val ?>"><?= $val ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="">Brand</label>
                                    <select class="form-control select2" name="brand">
                                        <option value="">-</option>
                                        <?php
                                        $arr = array();
                                        $arr['opt'] = "LAINNYA";
                                        $brands[] = $arr;
                                        foreach ($brands as $val) :
                                            $text = '';
                                            if ($_GET['brand'] == $val['opt']) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val['opt'] ?>"><?= $val['opt'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="">Tanggal Order</label>
                                    <div class="d-flex">
                                        <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                                        <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                                        <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="">Tanggal RTS</label>
                                    <div class="d-flex flex-column flex-md-row gap-2">
                                        <input type="datetime-local" class="form-control" name="rts_start" value="<?= htmlspecialchars($rts_start_value, ENT_QUOTES, 'UTF-8') ?>" aria-label="Tanggal dan jam RTS mulai">
                                        <input type="datetime-local" class="form-control" name="rts_end" value="<?= htmlspecialchars($rts_end_value, ENT_QUOTES, 'UTF-8') ?>" aria-label="Tanggal dan jam RTS selesai">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label for="">Kategori</label>
                                    <select class="form-control select2" name="c_type">
                                        <option value="">-</option>
                                        <?php
                                        $arr = array();
                                        $arr[] = "Pelanggan";
                                        $arr[] = "Reseller";
                                        $arr[] = "Distributor";
                                        $arr[] = "Affiliate";
                                        $arr[] = "Endorse";
                                        $arr[] = "Free";
                                        foreach ($arr as $val) :
                                            $text = '';
                                            if ($_GET['c_type'] == $val) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val ?>"><?= $val ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="">Status Cetak</label>
                                    <select class="form-control select2" name="printed_status">
                                        <option value="">-</option>
                                        <?php
                                        $arr = array();
                                        $arr[] = "Belum Dicetak";
                                        $arr[] = "Sudah Dicetak";
                                        foreach ($arr as $val) :
                                            $text = '';
                                            if ($_GET['printed_status'] == $val) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val ?>"><?= $val ?></option>
                                        <?php endforeach; ?>
                                    </select>
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
                                            },
                                            success: function(response) {
                                                $("#tanggal").after(response.html); 
                                            },
                                            error: function(xhr, status, error) {
                                                console.error("Error loading filter:", error);
                                            }
                                        });
                                    }
                                </script>

                                <div class="col-md-2 text-lg-end text-start mb-2">
                                    <!-- <label for="">&nbsp;</label> -->
                                    <button style="margin-top:25px" class="btn mb-2 btn-edit-active w-100" type="submit">Cari Data</button>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 d-none">
                        <div class="row">
                            <div class="col-md-2">
                                <!-- <label for="">&nbsp;</label> -->
                                <button class="btn mb-2 btn-primary w-100 form-control" type="submit">FILTER DATA</button>
                            </div>
                            <div class="col-md-2">
                                <!-- <label for="">&nbsp;</label> -->
                                <a href="<?= base_url() ?>transaction/create?start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&brand=<?= $brand ?>&marketplace=<?= $_GET['marketplace'] ?>&cs=<?= $_GET['cs'] ?>&keyword=<?= $_GET['keyword'] ?>" class="btn mb-2 btn-primary w-100 form-control">TAMBAH DATA</a>
                            </div>
                            <div class="col-md-2">
                                <!-- <label for="">&nbsp;</label> -->
                                <a href="#!" onclick="import_data('<?= $start_date ?>','<?= $until_date ?>')" class="btn mb-2 btn-primary w-100 form-control">IMPORT DATA</a>
                            </div>
                            <div class="col-md-2">
                                <!-- <label for="">&nbsp;</label> -->
                                <a href="#!" onclick="sync_data('<?= $start_date ?>','<?= $until_date ?>')" class="btn mb-2 btn-primary w-100 form-control">SYNC DATA</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <tr>
        <td>
            <div class="d-flex justify-content-between align-items-center w-100">
                <span><?= $notif ?></span>
                <div class="d-flex align-items-center gap-2">
                    <div class="dropdown history-dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="dropdownHistory" data-bs-toggle="dropdown" aria-expanded="false">
                            History Cetak
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownHistory">
                            <?php if (!empty($print_history)): ?>
                                <?php foreach ($print_history as $hist): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?= base_url('transaction/print-history?id=' . $hist['id']) ?>" target="_blank" rel="noopener">
                                            <?= htmlspecialchars($hist['label'] ?? 'History Cetak', ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><span class="dropdown-item-text">Belum ada history.</span></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownView" data-bs-toggle="dropdown" aria-expanded="false">
                            Pilih Tampilan
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownView">
                        <?php
                        $current_params = $_GET;
                        if (empty($current_params['view']) && !empty($current_view)) {
                            $current_params['view'] = $current_view;
                        }
                        
                        $current_params['view'] = 'card';
                        $card_url = 'transaction?' . http_build_query($current_params);
                        
                        $current_params['view'] = 'table';
                        $table_url = 'transaction?' . http_build_query($current_params);
                        ?>
                        <li><a class="dropdown-item" href="<?= $card_url ?>">Tampilan Kartu</a></li>
                        <li><a class="dropdown-item" href="<?= $table_url ?>">Tampilan List</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </td>
    </tr>
<div class="col-lg-12 mb-3">
    <div class="checkbox-wrapper-13 d-flex align-items-center gap-2 flex-wrap">
        <input id="c1-13" type="checkbox" value="1" class="checkAll">
        <label for="c1-13">Pilih Semua Data</label>
    </div>
</div>

<style>
    .history-dropdown:hover > .dropdown-menu {
        display: block;
    }
    .history-dropdown .dropdown-menu {
        margin-top: 0;
        max-height: 280px;
        overflow: auto;
    }
</style>

    <div class="col-lg-12">
        <div class="col-lg-12">
            
            <div id="tbody">
                <?php $this->load->view('loading', true) ?>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <div>
                <?= $pagination ?>
            </div>
            <div>
                <?php
                $per_page_options = isset($per_page_options) ? (array)$per_page_options : [30, 50, 100, 200, 500];
                $limit_from_session = $_SESSION['transaction_limit'] ?? null;
                $limit = isset($limit) ? (int)$limit : (int)$limit_from_session;
                if (!in_array($limit, $per_page_options, true)) {
                    $limit = $per_page_options[0];
                }

                $query_params = $_GET;
                unset($query_params['limit']);
                ?>

                <form method="GET" action="">
                    <?php foreach ($query_params as $key => $value): ?>
                        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                    <?php endforeach; ?>

                    <select class="form-control select2" name="limit" id="limit"
                        onchange="this.form.submit()">
                        <?php foreach ($per_page_options as $option): ?>
                            <option value="<?= $option ?>" <?= ($limit == $option) ? 'selected' : '' ?>>
                                <?= $option ?> / Halaman
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

            </div>
        </div>
    </div>
</div>

<div class="floating-div">
    <button class="btn mb-2 btn-edit-active dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-gear fs-16"></i> Aksi
    </button>
    <ul class="dropdown-menu text-lg-end text-start" style="padding:0px;background:unset;border:unset">
        <li><a class="dropdown-items" href="#!" style="padding:0px">
                <button type="button" class="btn mb-2 btn-edit-active" data-bs-toggle="modal" data-bs-target="#modal-print">
                    <i class="bi bi-printer fs-16"></i> Print Data
                </button>
            </a></li>
        <li>
            <a class="dropdown-items" href="#!" style="padding:0px;">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="ship_packages_bulk()">
                <i class="bi bi-truck fs-16"></i> Proses Pengiriman
                </button>
            </a>
        </li>

        <li>
            <a class="dropdown-items" href="#!" style="padding:0px;">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="get_shipping_documents_bulk()">
                <i class="bi bi-file-earmark-pdf fs-16"></i> Dokumen Pengiriman
                </button>
            </a>
        </li>


        <li><a class="dropdown-items" href="#!" style="padding:0px;">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="barang_diterima()">
                    <i class="bi bi-house-check fs-16"></i> Barang Diterima
                </button>
            </a></li>

        <li><a class="dropdown-items" href="#!" style="padding:0px;">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="refresh_data()">
                    <i class="bi bi-bootstrap-reboot fs-16"></i> Refresh Data
                </button>
            </a></li>

        <li><a class="dropdown-items" href="#!" style="padding:0px;">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="tampilkan_data()">
                    <i class="bi bi-eye fs-16"></i> Tampilkan Data
                </button>
            </a></li>

        <li><a class="dropdown-items" href="#!" style="padding:0px">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="hapus_data()">
                    <i class="bi bi-trash fs-16"></i> Hapus Data
                </button>
            </a></li>

    </ul>

</div>


<div class="floating-actions">
    <div id="shipping-action-bar" class="shipping-action-bar">
        <button type="button" class="shipping-action-bar__primary" data-action="ship" onclick="ship_packages_bulk()">Atur pengiriman</button>
        <button type="button" class="shipping-action-bar__secondary" onclick="get_shipping_documents_bulk()">Cetak dokumen</button>
    </div>

    <div id="selected-counter" class="selected-counter-floating">0 data dipilih</div>
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

<div class="modal fade bd-example-modal-sm" tabindex="-1" varietas="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-print">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Print Data</h5>
                <a class="close a-link" data-bs-dismiss="modal"><i class="bi bi-x-circle fs-24"></i></a>
            </div>
            <div class="modal-body">
                <p>Apakah kamu yakin ingin melakukan print data?</p>
                <form target="_blank" action="<?= base_url() ?>/transaction/print-v2" method="POST" id="form-action">
                    <button class="btn mb-2 btn-edit-active" type="submit"><i class="bi bi-printer fs-16"></i> Print Data</button>
                </form>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="id_selected" name="id_selected" form="form-action">

<script>
    var list_id_v2 = '';
    window.shippingActionState = { total: 0, ready: 0, processed: 0 };
    function updateSelectedCounter(total) {
        var el = document.getElementById('selected-counter');
        if (!el) return;
        if (total > 0) {
            el.textContent = total + ' data dipilih';
            el.classList.add('show');
        } else {
            el.textContent = '0 data dipilih';
            el.classList.remove('show');
        }
    }

    function applyShippingActionBar() {
        var bar = document.getElementById('shipping-action-bar');
        if (!bar) return;

        var btnShip = bar.querySelector('[data-action="ship"]');
        var state = window.shippingActionState || { total: 0, ready: 0, processed: 0 };
        var total = state.total || 0;
        var ready = state.ready || 0;
        var processed = state.processed || 0;

        var show = total > 0 && (ready + processed === total);

        if (show) {
            bar.classList.add('shipping-action-bar--show');
        } else {
            bar.classList.remove('shipping-action-bar--show');
        }

        if (btnShip) {
            var disableShip = !(total > 0 && ready === total && processed === 0);
            btnShip.disabled = disableShip;
            btnShip.classList.toggle('shipping-action-bar__primary--disabled', disableShip);
        }
    }


    function get_id() {
        list_id_v2 = '';
        var selectedValues = [];
        var readyCount = 0;
        var totalSelected = 0;
        var processedCount = 0;
        $('input[name="list_id"]').each(function() {
            if ($(this).is(":checked")) {
                selectedValues.push($(this).val());
                list_id_v2 += $(this).val() + ',';
                totalSelected++;
                var status = String($(this).data('status') || '').toUpperCase();
                if (status === 'READY_TO_SHIP' || status === 'PENDING') {
                    readyCount++;
                }
                if (status === 'PROCESSED') {
                    processedCount++;
                }
            } else {
                selectedValues.push('0');
            }
        });
        if (list_id_v2.length > 0) {
            list_id_v2 = list_id_v2.slice(0, -1);
        }

        $('#id_selected').val(selectedValues.join(','));


        window.shippingActionState = { total: totalSelected, ready: readyCount, processed: processedCount };
        applyShippingActionBar();
        updateSelectedCounter(totalSelected);
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>transaction/remove?id=" + id);
    }

    function barang_diterima(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Barang Diterima');
        $("#load-form").load("<?= base_url() ?>transaction/action?code=barang_diterima&id=" + id);
    }

    function tampilkan_data(id) {
        window.location.href = "<?= base_url() ?>/transaction?ids=" + list_id_v2;
    }

    function hapus_data(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>transaction/action?code=hapus_data&id=" + id);
    }

    function refresh_data(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh Data');
        $("#load-form").load("<?= base_url() ?>transaction/action?code=refresh_data&id=" + id);
    }

    function set_cs(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Atur CS');
        $("#load-form").load("<?= base_url() ?>transaction/set_cs?id=" + id);
    }

    function set_resi(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Atur No Resi');
        $("#load-form").load("<?= base_url() ?>transaction/set_resi?id=" + id);
    }

    function set_return(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Atur Return');
        $("#load-form").load("<?= base_url() ?>transaction/set_return?id=" + id);
    }


    function refresh(order_id, marketplace) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh');
        $("#load-form").load("<?= base_url() ?>transaction/refresh?order_id=" + order_id + '&marketplace=' + marketplace);
    }


    function import_pencairan() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Import Pencairan Dana');
        $("#load-form").load("<?= base_url() ?>transaction/import-pencairan<?= $param ?>");
    }

    function import_data(start_date, until_date) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Import Data');
        $("#load-form").load("<?= base_url() ?>transaction/import<?= $param ?>");
    }

    function download_file() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Download Order');
        $("#load-form").load("<?= base_url() ?>transaction/download-file<?= $param ?>");
    }

    function import_resi(start_date, until_date) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Import Resi');
        $("#load-form").load("<?= base_url() ?>transaction/import-resi<?= $param ?>");
    }

    function import_customer(start_date, until_date) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Import Pelanggan');
        $("#load-form").load("<?= base_url() ?>transaction/import_customer?start_date=" + start_date + "&until_date=" + until_date);
    }

    function sync_data(start_date, until_date) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Sync Data');
        $("#load-form").load("<?= base_url() ?>transaction/sync?start_date=" + start_date + "&until_date=" + until_date);
    }

    function ship_packages_bulk(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Proses Pengiriman');
        $("#load-form").load("<?= base_url() ?>transaction/shipping_process", {
            id_selected: list_id_v2,            
        });
    }

    function get_shipping_documents_bulk(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Cetak Dokumen Pengiriman');
        $("#load-form").load("<?= base_url() ?>transaction/shipping_documents", {
            id_selected: list_id_v2,            
        });
    }

    function tts_ship_packages_bulk(id) {
        ship_packages_bulk(id);
    }

    function tts_get_shipping_documents_bulk(id) {
        get_shipping_documents_bulk(id);
    }

    function shopee_mass_ship_order_bulk(id) {
        ship_packages_bulk(id);
    }

    function shopee_get_shipping_document_bulk(id) {
        get_shipping_documents_bulk(id);
    }

</script>

<script>
    function loadMoreData() {
        const urlParams = new URLSearchParams(window.location.search);
        const sortColumn = urlParams.get('sort_column') || 'date';
        const sortOrder = urlParams.get('sort_order') || 'DESC';
        
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/transaction/item<?= $param ?>&sort_column=" + sortColumn + "&sort_order=" + sortOrder,
            success: function(data) {
                $('#tbody').html('').append(data);
                select3();
                window.shippingActionState = { total: 0, ready: 0, processed: 0 };
                list_id_v2 = '';
                $('#id_selected').val('');
                applyShippingActionBar();
                updateSelectedCounter(0);
            },
            error: function(xhr, status, error) {
                console.error("Error loading data:", error);
            }
        });
    }

    $(document).ready(function() {
        loadMoreData();
        applyShippingActionBar();
    });
</script>
