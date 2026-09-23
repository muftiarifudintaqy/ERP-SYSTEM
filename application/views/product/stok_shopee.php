<?php $this->load->view('operasional/menu'); ?>
<style>
 .stk{background:#fff;border-radius:14px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:80px}
 .stk h4{margin:0;font-weight:700}
 .stk .ket{color:#64748b;font-size:.85rem;margin:6px 0 16px;line-height:1.55}
 .stk .alat{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
 .stk .alat input[type=text]{flex:1;min-width:220px;padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px}
 .stk .alat button{padding:8px 14px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;cursor:pointer}
 .stk table{width:100%;border-collapse:collapse;font-size:.84rem}
 .stk th{text-align:left;color:#64748b;font-weight:600;padding:10px 8px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
 .stk td{padding:9px 8px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
 .stk tr.nol td{background:#fef2f2}
 .stk tr.berubah td{background:#eff6ff}
 .stk .angka{text-align:right;font-weight:600}
 .stk .sku{font-family:monospace;font-size:.78rem;color:#475569}
 .stk input.baru{width:110px;padding:6px 9px;border:1px solid #cbd5e1;border-radius:8px;text-align:right}
 .stk .var{color:#94a3b8;font-size:.75rem}
 .stk .hasil{font-size:.78rem;white-space:nowrap}
 .stk .ok{color:#16a34a;font-weight:600}.stk .gagal{color:#dc2626}
 .stk .kosong{text-align:center;color:#94a3b8;padding:30px}
 .stkbar{position:fixed;left:0;right:0;bottom:0;background:#0f172a;color:#fff;padding:12px 24px;display:none;justify-content:flex-end;align-items:center;gap:16px;z-index:50}
 .stkbar button{padding:10px 22px;border:0;border-radius:8px;background:#16a34a;color:#fff;font-weight:700;cursor:pointer}
</style>
<div class="stk">
  <h4>Stok Shopee</h4>
  <p class="ket">Stok dibaca langsung dari Shopee. Isi kolom <b>Stok baru</b> hanya untuk listing yang mau diubah &mdash; yang dikosongkan <b>tidak disentuh</b>. Baris merah = stok sedang 0.</p>
  <div class="alat">
    <input type="text" id="cari" placeholder="Cari nama produk, SKU, atau ID produk...">
    <label><input type="checkbox" id="hanyaNol"> Hanya yang stoknya 0</label>
    <button type="button" id="muatUlang">Muat ulang</button>
  </div>
  <div id="status" class="ket">Memuat stok dari Shopee...</div>
  <div style="overflow-x:auto"><table>
    <thead><tr><th>Toko</th><th>Produk</th><th>SKU</th><th class="angka">Stok sekarang</th><th>Stok baru</th><th></th></tr></thead>
    <tbody id="isi"></tbody>
  </table></div>
</div>
<div class="stkbar" id="bar"><span id="jumlah"></span><button type="button" id="simpan">Simpan ke Shopee</button></div>
<script>
var STK = { data: [], ubah: {} };
function stkEl(id) { return document.getElementById(id); }
function stkEsc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }
function stkMuat() {
  stkEl('status').textContent = 'Memuat stok dari Shopee...';
  fetch('/product/stok_shopee_data', { credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .then(function (o) {
      STK.data = o.data || []; STK.ubah = {}; stkTampil();
      var nol = STK.data.filter(function (r) { return r.stok === 0; }).length;
      stkEl('status').textContent = STK.data.length + ' listing dimuat, ' + nol + ' di antaranya stok 0.';
    })
    .catch(function () { stkEl('status').textContent = 'Gagal memuat stok dari Shopee. Coba Muat ulang.'; });
}
function stkTampil() {
  var q = stkEl('cari').value.toLowerCase(), nol = stkEl('hanyaNol').checked, h = '';
  STK.data.forEach(function (r) {
    if (q && (r.nama + ' ' + r.sku + ' ' + r.item_id).toLowerCase().indexOf(q) < 0) return;
    if (nol && r.stok !== 0 && !(r.model || []).some(function (m) { return m.stok === 0; })) return;
    var gbr = r.gambar ? '<img src="' + stkEsc(r.gambar) + '" style="width:40px;height:40px;object-fit:cover;border-radius:6px;margin-right:10px;vertical-align:middle">' : '';
    var judul = '<td>' + gbr + '<span style="vertical-align:middle">' + stkEsc(r.nama) +
                '<div style="color:#94a3b8;font-size:.72rem">ID ' + stkEsc(r.item_id) + '</div></span></td>';
    if (r.varian && r.model && r.model.length) {
      h += '<tr><td>' + stkEsc(r.toko) + '</td>' + judul + '<td class="sku">' + stkEsc(r.sku) +
           '</td><td class="angka var">' + r.model.length + ' varian</td><td></td><td></td></tr>';
      r.model.forEach(function (m) {
        var k = r.item_id + '|' + m.model_id;
        var n2 = STK.ubah[k] !== undefined ? STK.ubah[k] : '';
        var kls2 = STK.ubah[k] !== undefined ? 'berubah' : (m.stok === 0 ? 'nol' : '');
        h += '<tr class="' + kls2 + '"><td></td><td style="padding-left:56px;color:#475569">&#8627; ' + stkEsc(m.nama) +
             '</td><td class="sku">' + stkEsc(m.sku) + '</td><td class="angka">' + (m.stok === null ? '?' : m.stok) +
             '</td><td><input type="number" min="1" class="baru" data-id="' + stkEsc(k) + '" value="' + stkEsc(n2) + '"></td>' +
             '<td class="hasil" id="h' + stkEsc(k).replace(/\|/g, '_') + '">' + (STK.hasil && STK.hasil[k] || '') + '</td></tr>';
      });
      return;
    }
    var nilai = STK.ubah[r.item_id] !== undefined ? STK.ubah[r.item_id] : '';
    var kolom = r.varian ? '<span class="var">punya varian</span>'
      : '<input type="number" min="1" class="baru" data-id="' + stkEsc(r.item_id) + '" value="' + stkEsc(nilai) + '">';
    var kls = STK.ubah[r.item_id] !== undefined ? 'berubah' : (r.stok === 0 ? 'nol' : '');
    h += '<tr class="' + kls + '"><td>' + stkEsc(r.toko) + '</td>' + judul + '<td class="sku">' + stkEsc(r.sku) +
         '</td><td class="angka">' + (r.stok === null ? '?' : r.stok) + '</td><td>' + kolom +
         '</td><td class="hasil" id="h' + stkEsc(r.item_id) + '">' + (STK.hasil && STK.hasil[r.item_id] || '') + '</td></tr>';
  });
  stkEl('isi').innerHTML = h || '<tr><td colspan="6" class="kosong">Tidak ada listing</td></tr>';
  stkHitung();
}
function stkHitung() {
  var n = Object.keys(STK.ubah).length;
  stkEl('jumlah').textContent = n + ' listing akan diubah';
  stkEl('bar').style.display = n ? 'flex' : 'none';
}
function stkSimpan() {
  var daftar = Object.keys(STK.ubah).map(function (k) {
    var p = k.split('|');
    return { item_id: p[0], model_id: p[1] || '0', stok: STK.ubah[k] };
  });
  if (daftar.some(function (d) { return !/^\d+$/.test(d.stok); })) { Swal.fire('Ada angka tidak valid', 'Stok harus berupa angka.', 'warning'); return; }
  if (daftar.some(function (d) { return d.stok === '0'; })) {
    Swal.fire('Tidak bisa 0', 'Halaman ini tidak boleh mengosongkan stok. Kalau memang mau dihabiskan, ubah dari Seller Centre.', 'warning');
    return;
  }
  var jadiNol = 0;
  Swal.fire({ title: 'Kirim ' + daftar.length + ' perubahan ke Shopee?',
    html: jadiNol ? '<b style="color:#dc2626">' + jadiNol + ' listing akan dibuat 0 (habis).</b>' : 'Stok di Shopee langsung berubah.',
    icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, kirim', cancelButtonText: 'Batal'
  }).then(function (x) {
    if (!x.isConfirmed) return;
    var fd = new FormData(); fd.append('perubahan', JSON.stringify(daftar));
    stkEl('simpan').disabled = true; stkEl('simpan').textContent = 'Mengirim...';
    fetch('/product/stok_shopee_simpan', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (o) {
        stkEl('simpan').disabled = false; stkEl('simpan').textContent = 'Simpan ke Shopee';
        if (!o.success) { Swal.fire('Gagal', o.message || 'Tidak tersimpan', 'error'); return; }
        STK.hasil = STK.hasil || {}; var ok = 0, gagal = 0;
        Object.keys(o.hasil).forEach(function (id) {
          var h = o.hasil[id];
          if (h.ok) {
            ok++; delete STK.ubah[id];
            var p = id.split('|');
            STK.data.forEach(function (r) {
              if (r.item_id !== p[0]) return;
              if (p[1] && p[1] !== '0') { (r.model || []).forEach(function (m) { if (m.model_id === p[1]) m.stok = h.stok; }); }
              else r.stok = h.stok;
            });
          }
          else gagal++;
          STK.hasil[id] = h.ok ? '<span class="ok">&#10003; Tersimpan</span>' : '<span class="gagal">&#10007; ' + stkEsc(h.pesan) + '</span>';
        });
        stkTampil();
        Swal.fire({ toast: true, position: 'top-end', icon: gagal ? 'warning' : 'success', showConfirmButton: false, timer: 4000,
                    title: ok + ' tersimpan' + (gagal ? ', ' + gagal + ' gagal' : '') });
      })
      .catch(function () { stkEl('simpan').disabled = false; stkEl('simpan').textContent = 'Simpan ke Shopee'; Swal.fire('Gagal', 'Kesalahan jaringan.', 'error'); });
  });
}
stkEl('isi').addEventListener('input', function (e) {
  if (!e.target.classList.contains('baru')) return;
  var id = e.target.dataset.id, v = e.target.value.trim();
  if (v === '') delete STK.ubah[id]; else STK.ubah[id] = v;
  e.target.closest('tr').className = v === '' ? '' : 'berubah';
  stkHitung();
});
stkEl('cari').addEventListener('input', stkTampil);
stkEl('hanyaNol').addEventListener('change', stkTampil);
stkEl('muatUlang').addEventListener('click', stkMuat);
stkEl('simpan').addEventListener('click', stkSimpan);
stkMuat();
</script>
