<link rel="stylesheet" href="<?= base_url() ?>assets/css/hpp-table.css?v=<?= @filemtime(FCPATH . 'assets/css/hpp-table.css') ?>">
<?php
$__uid = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0;
$this->load->library('permission');
$bolehUbahHpp = $__uid ? $this->permission->check_permission($__uid,'hpp_edit','edit') : false;
?>
<?php
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date("Y-m-01");
$until_date = isset($_GET['until_date']) ? $_GET['until_date'] : date("Y-m-d");
$selected_brand = isset($_GET['brand']) ? $_GET['brand'] : '';
$selected_jenis_produk = isset($_GET['jenis_produk']) ? $_GET['jenis_produk'] : '';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <?php $this->load->view('dashboard/menu_hpp'); ?>
    </div>

    <form action="<?= $url ?>" method="GET">
        <div class="row">
            <div class="col-md-2">
                <select class="form-control select2" name="brand" id="brand">
                    <option value="">Semua Brand</option>
                    <?php foreach ($brands as $val) :
                        $selected = ($selected_brand == $val["code"]) ? "selected" : "";
                    ?>
                        <option <?= $selected ?> value="<?= $val["code"] ?>"><?= $val["code"] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-control select2" name="jenis_produk" id="jenis_produk">
                    <option value="Semua" <?= $selected_jenis_produk == 'Semua' ? 'selected' : '' ?>>Semua Jenis Produk</option>
                    <option value="Produk Jual" <?= $selected_jenis_produk == 'Produk Jual' ? 'selected' : '' ?>>Produk Jual</option>
                    <option value="Produk Operasional" <?= $selected_jenis_produk == 'Produk Operasional' ? 'selected' : '' ?>>Produk Operasional</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100 form-control" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
            </div>
            <div class="col-md-2 mt-2 mt-md-0 ms-auto">
                <?php
                $exportParams = [
                    'brand'        => $selected_brand,
                    'jenis_produk' => $selected_jenis_produk,
                    'start_date'   => $_GET['start_date'] ?? $start_date,
                    'until_date'   => $_GET['until_date'] ?? $until_date,
                    'export'       => 'excel',
                ];
                $exportUrl = base_url('dashboard/hpp') . '?' . http_build_query($exportParams);
                ?>
                <a class="btn btn-success w-100 form-control" href="<?= $exportUrl ?>">
                    <i class="bi bi-download fs-16"></i> Export Excel
                </a>
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
    </form>

    <!-- Statistic Cards -->
    <div class="row mb-3">
        <?php
        // Calculate totals from HPP data
        $total_hpp = array_sum(array_column($hpp, 'total_hpp'));
        $total_persentase_hpp = array_sum(array_column($hpp, 'persentase_hpp'));
        
        // Calculate profit
        $profit = $net_sales - $total_hpp;
        
        // Calculate percentages (for comparison purposes - you might want to compare with previous period)
        // For now I'll just show the values without comparison
        ?>
        
        <style>
            .card {
                flex: 1;
                background: #fff;
                border: none;
                border-top: 5px solid;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            
            .card.net-sales {
                border-color: #4e73df;
            }
            
            .card.hpp {
                border-color: #1cc88a;
            }
            
            .card.profit {
                border-color: #36b9cc;
            }

            .card.table {
                border-color: #fff;
            }
            
            .icon-container {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        </style>

        <style>
            .sortable {
                cursor: pointer;
                position: relative;
                user-select: none;
            }

            .sortable:hover {
                background-color: #f8f9fa;
            }

            .sortable i {
                font-size: 0.8em;
                margin-left: 5px;
                opacity: 0.5;
            }

            .sortable.asc i, .sortable.desc i {
                opacity: 1;
            }

            .sortable.asc i {
                transform: rotate(180deg);
            }

            #hppTable th {
                white-space: nowrap;
            }
        </style>

        <div class="col-md-4 mt-2">
            <div class="card net-sales p-36 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <!-- Left Section -->
                    <div>
                        <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                            Penjualan Bersih
                        </h6>
                        <h3 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                            Rp <?= number_format($net_sales, 0, ',', '.') ?>
                        </h3>
                        <div class="d-flex justify-content-start align-items-center">
                            <small class="fw-bold text-muted">100%</small>
                            <i class="ms-2 bi bi-caret-right-fill text-start text-muted fw-bold"></i>
                        </div>
                    </div>

                    <!-- Right Section -->
                    <div>
                        <div class="icon-container" style="background-color: #4e73df;">
                            <i class="bi bi-cash-stack text-white" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mt-2">
            <div class="card hpp p-36 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <!-- Left Section -->
                    <div>
                        <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                            Total HPP
                        </h6>
                        <h3 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                            Rp <?= number_format($total_hpp, 0, ',', '.') ?>
                        </h3>
                        <div class="d-flex justify-content-start align-items-center">
                            <small class="fw-bold text-muted">
                                <?= $net_sales > 0 ? number_format(($total_hpp/$net_sales)*100, 2) : 0 ?>%
                            </small>
                            <i class="ms-2 bi bi-caret-right-fill text-start text-muted fw-bold"></i>
                        </div>
                    </div>

                    <!-- Right Section -->
                    <div>
                        <div class="icon-container" style="background-color: #1cc88a;">
                            <i class="bi bi-box-seam text-white" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mt-2">
            <div class="card profit p-36 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <!-- Left Section -->
                    <div>
                        <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                            Profit
                        </h6>
                        <h3 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                            Rp <?= number_format($profit, 0, ',', '.') ?>
                        </h3>
                        <div class="d-flex justify-content-start align-items-center">
                            <small class="fw-bold text-muted">
                                <?= $net_sales > 0 ? number_format(($profit/$net_sales)*100, 2) : '0' ?>%
                            </small>
                            <i class="ms-2 bi bi-caret-right-fill text-start text-muted fw-bold"></i>
                        </div>
                    </div>

                    <!-- Right Section -->
                    <div>
                        <div class="icon-container" style="background-color: #36b9cc;">
                            <i class="bi bi-graph-up text-white" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- HPP Table -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card table">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="hppTable">
                            <?php if ($bolehUbahHpp): ?>
<div class="alert alert-info d-flex align-items-start gap-2 mb-3" style="font-size:.86rem">
  <i class="bi bi-info-circle-fill mt-1"></i>
  <div>
    <b>Cara mengisi:</b> ketik harga modal 1 pcs di kolom <b>Modal per pcs</b>, isi alasan bila perlu, lalu klik tombol hijau <b>Simpan HPP</b> di kanan bawah.
    Semua laporan laba akan menyesuaikan otomatis. Setiap perubahan tercatat &mdash; klik ikon jam untuk melihat riwayat.
  </div>
</div>
<?php endif; ?>
<thead>
                                <tr>
                                    <th>No</th>
                                    <th class="sortable">Produk</th>
                                    <th class="sortable num">Qty Out</th>
                                    <th class="sortable">HPP (per unit)</th>
                                    <?php if ($bolehUbahHpp): ?><th style="min-width:170px">Catatan</th><th style="width:90px">Riwayat</th><?php endif; ?>
                                    <th class="sortable num">Total HPP</th>
                                    <th class="sortable num">Persentase HPP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $grand_total_hpp = 0;
                                foreach($hpp as $item): 
                                    $grand_total_hpp += $item['total_hpp'];
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="col-produk" data-sort="<?= strtolower($item['name']) ?>"><span class="sku-badge"><?= $item['sku'] ?></span>
                                        <?= $item['name'] ?>
                                    </td>
                                    <td class="num" data-sort="<?= $item['qty_out_pos'] + $item['qty_out'] ?>"><?= number_format($item['qty_out_pos'] + $item['qty_out'], 0, ',', '.') ?></td>
                                    <td data-sort="<?= $item['price_buy'] ?>">
                                      <?php if ($bolehUbahHpp): ?>
                                        <div class="input-group input-group-sm" style="min-width:150px">
                                          <span class="input-group-text">Rp</span>
                                          <input type="text" inputmode="numeric" autocomplete="off"
                                                 class="form-control form-control-sm hpp-input text-end"
                                                 data-id="<?= $item['id'] ?>"
                                                 data-awal="<?= (float)$item['price_buy'] ?>"
                                                 value="<?= number_format((float)$item['price_buy'], 0, ',', '.') ?>">
                                        </div>
                                      <?php else: ?>
                                        <?= 'Rp ' . number_format($item['price_buy'], 0, ',', '.') ?>
                                      <?php endif; ?>
                                    </td>
                                    <?php if ($bolehUbahHpp): ?>
                                    <td><input type="text" class="form-control form-control-sm hpp-note"
                                               data-id="<?= $item['id'] ?>" placeholder="alasan (opsional)"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-secondary btn-riwayat"
                                                data-id="<?= $item['id'] ?>" data-nama="<?= htmlspecialchars($item['name']) ?>">
                                          <i class="bi bi-clock-history"></i></button></td>
                                    <?php endif; ?>
                                    <td class="num" data-sort="<?= $item['total_hpp'] ?>"><?= 'Rp ' . number_format($item['total_hpp'], 0, ',', '.') ?></td>
                                    <td class="num" data-sort="<?= $item['persentase_hpp'] ?>"><?= number_format($item['persentase_hpp'], 2) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="fw-bold">
                                    <td colspan="<?= $bolehUbahHpp ? 6 : 4 ?>" class="text-end">Grand Total</td>
                                    <td><?= 'Rp ' . number_format($grand_total_hpp, 0, ',', '.') ?></td>
                                    <td><?= number_format($total_persentase_hpp, 2) ?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('hppTable');
    const headers = table.querySelectorAll('th.sortable'); // Hanya ambil header yang sortable
    const grandTotalRow = table.querySelector('tr.fw-bold');

    updateRowNumbers();

    headers.forEach(header => {
        header.innerHTML += ' <i class="bi bi-arrow-down-up"></i>';
        header.addEventListener('click', () => {
            sortTable(header);
        });
    });

    function sortTable(header) {
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const rows = Array.from(table.querySelectorAll('tbody tr:not(.fw-bold)'));
        const isAscending = !header.classList.contains('asc');

        headers.forEach(h => {
            h.classList.remove('asc', 'desc');
            h.querySelector('i').className = 'bi bi-arrow-down-up';
        });

        rows.sort((a, b) => {
            let aValue, bValue;

            if (a.children[columnIndex].hasAttribute('data-sort')) {
                aValue = a.children[columnIndex].getAttribute('data-sort');
                bValue = b.children[columnIndex].getAttribute('data-sort');
            } else {
                aValue = a.children[columnIndex].textContent.trim();
                bValue = b.children[columnIndex].textContent.trim();
            }

            if (columnIndex === 1) {
                return isAscending 
                    ? aValue.localeCompare(bValue, 'id', { sensitivity: 'base' })
                    : bValue.localeCompare(aValue, 'id', { sensitivity: 'base' });
            }

            const aNum = parseFloat(aValue.toString().replace(/[^\d.-]/g, ''));
            const bNum = parseFloat(bValue.toString().replace(/[^\d.-]/g, ''));

            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? aNum - bNum : bNum - aNum;
            }

            return isAscending 
                ? aValue.toString().localeCompare(bValue.toString())
                : bValue.toString().localeCompare(aValue.toString());
        });

        const tbody = table.querySelector('tbody');
        rows.forEach(row => tbody.insertBefore(row, grandTotalRow));

        updateRowNumbers();

        header.classList.add(isAscending ? 'asc' : 'desc');
        header.querySelector('i').className = isAscending 
            ? 'bi bi-arrow-up' 
            : 'bi bi-arrow-down';
    }

    function updateRowNumbers() {
        const rows = table.querySelectorAll('tbody tr:not(.fw-bold)');
        rows.forEach((row, index) => {
            row.cells[0].textContent = index + 1;
        });
    }
});

</script>

<?php if ($bolehUbahHpp): ?>
<div class="position-fixed bottom-0 end-0 m-4" style="z-index:1050">
  <button type="button" id="btnSimpanHpp" class="btn btn-success shadow" style="display:none">
    <i class="bi bi-save me-1"></i> Simpan HPP (<span id="hppCount">0</span>)
  </button>
</div>

<div class="modal fade" id="modalRiwayatHpp" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Riwayat Perubahan HPP</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-semibold" id="riwayatNama"></div>
          <button type="button" class="btn btn-sm btn-outline-danger" id="btnHapusRiwayatProduk">
            <i class="bi bi-trash"></i> Bersihkan riwayat produk ini
          </button>
        </div>
        <div id="riwayatIsi" class="text-muted">Memuat...</div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var ubah = {};
  function rupiah(n){ return 'Rp ' + Number(n||0).toLocaleString('id-ID'); }

  function refreshBtn(){
    var n = Object.keys(ubah).length;
    document.getElementById('hppCount').textContent = n;
    document.getElementById('btnSimpanHpp').style.display = n > 0 ? 'inline-block' : 'none';
  }

  function angkaMurni(t){ return parseFloat(String(t||'').replace(/[^0-9]/g,'')) || 0; }
  function formatRibuan(n){ return Number(n||0).toLocaleString('id-ID'); }

  document.querySelectorAll('.hpp-input').forEach(function(inp){
    inp.addEventListener('input', function(){
      var pos = this.value.length - this.selectionStart;
      var murni = angkaMurni(this.value);
      this.value = murni ? formatRibuan(murni) : '';
      try { this.selectionStart = this.selectionEnd = Math.max(0, this.value.length - pos); } catch(e){}
      var id = this.dataset.id, awal = parseFloat(this.dataset.awal||0), val = murni;
      if (val !== awal) {
        var note = document.querySelector('.hpp-note[data-id="'+id+'"]');
        ubah[id] = { id: id, hpp: val, catatan: note ? note.value : '' };
        this.classList.add('border-warning','bg-warning-subtle');
      } else { delete ubah[id]; this.classList.remove('border-warning','bg-warning-subtle'); }
      refreshBtn();
    });
  });

  document.querySelectorAll('.hpp-note').forEach(function(n){
    n.addEventListener('input', function(){ if (ubah[this.dataset.id]) ubah[this.dataset.id].catatan = this.value; });
  });

  document.getElementById('btnSimpanHpp').addEventListener('click', function(){
    var list = Object.values(ubah);
    if (!list.length) return;
    if (!confirm('Simpan ' + list.length + ' perubahan HPP?\n\nSemua laporan laba akan menyesuaikan otomatis.')) return;
    var btn = this; btn.disabled = true; btn.innerHTML = 'Menyimpan...';

    var fd = new FormData();
    list.forEach(function(it,i){
      fd.append('items['+i+'][id]', it.id);
      fd.append('items['+i+'][hpp]', it.hpp);
      fd.append('items['+i+'][catatan]', it.catatan || '');
    });

    fetch('<?= base_url() ?>dashboard/hpp_update', {method:'POST', body: fd, credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(d){
        alert(d.msg || 'Selesai');
        if (d.status) location.reload();
        else { btn.disabled=false; btn.innerHTML='<i class="bi bi-save me-1"></i> Simpan HPP (<span id="hppCount">'+list.length+'</span>)'; }
      })
      .catch(function(e){ alert('Gagal menyimpan: '+e); btn.disabled=false; });
  });

  document.querySelectorAll('.btn-riwayat').forEach(function(b){
    b.addEventListener('click', function(){
      var id = this.dataset.id;
      document.getElementById('riwayatNama').textContent = this.dataset.nama || '';
      document.getElementById('riwayatIsi').innerHTML = 'Memuat...';
      new bootstrap.Modal(document.getElementById('modalRiwayatHpp')).show();

      fetch('<?= base_url() ?>dashboard/hpp_history?product_id='+id, {credentials:'same-origin'})
        .then(function(r){ return r.json(); })
        .then(function(d){
          var rows = d.data || [];
          if (!rows.length) { document.getElementById('riwayatIsi').innerHTML = '<div class="text-muted">Belum ada perubahan tercatat.</div>'; return; }
          var h = '<div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr>'
                + '<th>Waktu</th><th class="text-end">Dari</th><th class="text-end">Jadi</th><th class="text-end">Selisih</th><th>Catatan</th><th>Diubah oleh</th><th></th></tr></thead><tbody>';
          rows.forEach(function(r){
            var s = parseFloat(r.selisih||0);
            var warna = s > 0 ? 'text-danger' : (s < 0 ? 'text-success' : 'text-muted');
            h += '<tr data-hid="'+(r.id||'')+'"><td>'+r.created_at+'</td><td class="text-end">'+rupiah(r.hpp_lama)+'</td><td class="text-end fw-semibold">'+rupiah(r.hpp_baru)+'</td>'
               + '<td class="text-end '+warna+'">'+(s>0?'+':'')+rupiah(s)+'</td><td>'+(r.catatan||'-')+'</td><td>'+(r.user_name||'-')+'</td></tr>';
          });
          document.getElementById('riwayatIsi').innerHTML = h + '</tbody></table></div>';
        })
        .catch(function(){ document.getElementById('riwayatIsi').innerHTML = '<div class="text-danger">Gagal memuat riwayat.</div>'; });
    });
  });

  var pidAktif = null;
  document.querySelectorAll('.btn-riwayat').forEach(function(b){
    b.addEventListener('click', function(){ pidAktif = this.dataset.id; });
  });

  function muatUlangRiwayat(){
    var b = document.querySelector('.btn-riwayat[data-id="'+pidAktif+'"]');
    if (b) b.click();
  }

  document.getElementById('riwayatIsi').addEventListener('click', function(e){
    var t = e.target.closest('.hapus-baris'); if (!t) return;
    if (!confirm('Hapus 1 baris riwayat ini?')) return;
    var fd = new FormData(); fd.append('id', t.dataset.hid);
    fetch('<?= base_url() ?>dashboard/hpp_history_delete', {method:'POST', body:fd, credentials:'same-origin'})
      .then(function(r){return r.json();}).then(function(d){
        alert(d.msg);
        if (d.status) {
          var mi = bootstrap.Modal.getInstance(document.getElementById('modalRiwayatHpp'));
          if (mi) mi.hide();
          setTimeout(function(){
            document.querySelectorAll('.modal-backdrop').forEach(function(b){ b.remove(); });
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
          }, 300);
        }
      });
  });

  document.getElementById('btnHapusRiwayatProduk').addEventListener('click', function(){
    if (!pidAktif) return;
    if (!confirm('Hapus SELURUH riwayat perubahan HPP produk ini?\n\nTidak bisa dikembalikan.')) return;
    var fd = new FormData(); fd.append('mode','produk'); fd.append('product_id', pidAktif);
    fetch('<?= base_url() ?>dashboard/hpp_history_delete', {method:'POST', body:fd, credentials:'same-origin'})
      .then(function(r){return r.json();}).then(function(d){
        alert(d.msg);
        if (d.status) {
          var mi = bootstrap.Modal.getInstance(document.getElementById('modalRiwayatHpp'));
          if (mi) mi.hide();
          setTimeout(function(){
            document.querySelectorAll('.modal-backdrop').forEach(function(b){ b.remove(); });
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
          }, 300);
        }
      });
  });

  // bersihkan sisa lapisan gelap modal
  var modalRw = document.getElementById('modalRiwayatHpp');
  if (modalRw) {
    modalRw.addEventListener('hidden.bs.modal', function(){
      document.querySelectorAll('.modal-backdrop').forEach(function(b){ b.remove(); });
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('overflow');
      document.body.style.removeProperty('padding-right');
    });
  }
  window.addEventListener('pageshow', function(){
    document.querySelectorAll('.modal-backdrop').forEach(function(b){ b.remove(); });
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
  });
})();
</script>
<?php endif; ?>
