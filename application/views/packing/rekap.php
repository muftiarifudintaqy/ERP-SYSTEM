<style>
 .rk{max-width:1000px;margin:0 auto}
 .rk-kartu{background:#fff;border-radius:16px;padding:20px 24px;box-shadow:0 1px 3px rgba(0,0,0,.08);margin-bottom:16px}
 .rk-kartu h5{margin:0 0 14px;font-weight:700}
 .rk-alat{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap}
 .rk-alat label{display:block;font-size:.8rem;color:#64748b;margin-bottom:4px}
 .rk-alat input{padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px}
 .rk-alat button{padding:9px 18px;border:0;border-radius:8px;background:#1F4696;color:#fff;font-weight:600;cursor:pointer}
 .rk-orang{display:flex;gap:14px;flex-wrap:wrap}
 .rk-box{flex:1;min-width:190px;border:1px solid #e2e8f0;border-radius:14px;padding:16px 18px}
 .rk-box .n{font-weight:700;color:#334155;margin-bottom:8px}
 .rk-box .a{font-size:2.2rem;font-weight:800;color:#1F4696;line-height:1}
 .rk-box .k{color:#64748b;font-size:.8rem}
 .rk table{width:100%;border-collapse:collapse;font-size:.85rem}
 .rk th{text-align:left;color:#64748b;font-weight:600;padding:8px;border-bottom:1px solid #e2e8f0}
 .rk td{padding:8px;border-bottom:1px solid #f1f5f9}
 .rk .kanan{text-align:right}
 .rk-cari{display:flex;gap:10px;flex-wrap:wrap}
 .rk-cari input{flex:1;min-width:220px;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-family:ui-monospace,Menlo,monospace}
 .rk-hasil{margin-top:12px;padding:14px 16px;border-radius:10px;display:none}
 .rk-hasil.ada{background:#dcfce7;border:1px solid #16a34a}
 .rk-hasil.tidak{background:#fef3c7;border:1px solid #d97706}
 .rk-kosong{text-align:center;color:#94a3b8;padding:24px}
 .rk-box{cursor:pointer;transition:.15s}
 .rk-box:hover{border-color:#1F4696;box-shadow:0 2px 10px rgba(31,70,150,.15)}
 .rk-box.aktif{border-color:#1F4696;background:#f8faff}
 .rk-foto{width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid #1F4696;margin-bottom:8px}
</style>

<div class="rk">
  <div class="rk-kartu">
    <h5>Rekap Packing</h5>
    <div class="rk-alat">
      <div><label>Dari tanggal</label><input type="date" id="rkDari"></div>
      <div><label>Sampai tanggal</label><input type="date" id="rkSampai"></div>
      <button type="button" id="rkTampil">Tampilkan</button>
      <button type="button" id="rkBulan" style="background:#0f766e">Bulan ini</button>
    </div>
  </div>

  <div class="rk-kartu">
    <h5>Per orang</h5>
    <div class="rk-orang" id="rkOrang"><div class="rk-kosong">Memuat...</div></div>
  </div>

  <div class="rk-kartu" id="rkRincian" style="display:none">
    <h5 id="rkRincianJudul">Rincian</h5>
    <div style="overflow-x:auto">
      <table><thead><tr><th>Waktu</th><th>Resi</th><th>Produk</th><th class="kanan">Qty</th></tr></thead>
      <tbody id="rkRincianIsi"></tbody></table>
    </div>
  </div>

  <div class="rk-kartu">
    <h5>Cari resi &mdash; siapa yang packing?</h5>
    <div class="rk-cari">
      <input type="text" id="rkResi" placeholder="Tembak atau ketik nomor resi...">
      <button type="button" id="rkCari" style="padding:10px 20px;border:0;border-radius:8px;background:#1F4696;color:#fff;font-weight:600;cursor:pointer">Cari</button>
    </div>
    <div class="rk-hasil" id="rkHasilCari"></div>
  </div>

  <div class="rk-kartu">
    <h5>Rincian per hari</h5>
    <div style="overflow-x:auto">
      <table><thead><tr><th>Tanggal</th><th>Nama</th><th class="kanan">Paket</th></tr></thead>
      <tbody id="rkHari"><tr><td colspan="3" class="rk-kosong">Memuat...</td></tr></tbody></table>
    </div>
  </div>
</div>

<script>
(function () {
  function el(i){ return document.getElementById(i); }
  function esc(t){ var d=document.createElement('div'); d.textContent=t==null?'':String(t); return d.innerHTML; }
  var hariIni = new Date().toISOString().slice(0,10);
  el('rkDari').value = hariIni; el('rkSampai').value = hariIni;

  function muat(){
    fetch('/packing/rekap_data?dari=' + el('rkDari').value + '&sampai=' + el('rkSampai').value,
          { credentials:'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(o){
        if (!o.ok) { el('rkOrang').innerHTML = '<div class="rk-kosong">Tidak punya akses.</div>'; return; }
        el('rkOrang').innerHTML = (o.per_orang || []).length
          ? o.per_orang.map(function(p){
              var f = p.foto ? '/assets/img/user/' + esc(p.foto) : '/assets/img/user/default.png';
              return '<div class="rk-box" data-uid="' + esc(p.id) + '" data-nama="' + esc(p.nama || '-') + '">' +
                     '<img class="rk-foto" src="' + f + '" onerror="this.src=\'/assets/img/user/default.png\'">' +
                     '<div class="n">' + esc(p.nama || '-') + '</div>' +
                     '<div class="a">' + esc(p.paket) + '</div><div class="k">paket &middot; ' + esc(p.pcs) + ' pcs</div>' +
                     '<div class="k" style="margin-top:6px">' + esc((p.mulai||'').slice(0,5)) + ' &ndash; ' + esc((p.selesai||'').slice(0,5)) + '</div></div>';
            }).join('')
          : '<div class="rk-kosong">Belum ada scan pada rentang ini.</div>';

        el('rkHari').innerHTML = (o.per_hari || []).length
          ? o.per_hari.map(function(h){
              return '<tr><td>' + esc(h.tgl) + '</td><td>' + esc(h.nama || '-') +
                     '</td><td class="kanan">' + esc(h.paket) + '</td></tr>';
            }).join('')
          : '<tr><td colspan="3" class="rk-kosong">Belum ada data</td></tr>';
      })
      .catch(function(){ el('rkOrang').innerHTML = '<div class="rk-kosong">Gagal memuat.</div>'; });
  }

  function cari(){
    var v = el('rkResi').value.trim(); if (!v) return;
    fetch('/packing/cari_resi?resi=' + encodeURIComponent(v), { credentials:'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(o){
        var h = el('rkHasilCari'); h.style.display = 'block';
        if (!o.ok) { h.className = 'rk-hasil tidak'; h.textContent = o.pesan || 'Tidak ditemukan.'; return; }
        var d = o.data;
        h.className = 'rk-hasil ada';
        h.innerHTML = '<b>' + esc(d.nama || '-') + '</b> &middot; ' + esc(d.scanned_at) +
                      '<br>' + esc(d.produk || '-') + (d.qty ? ' &times;' + esc(d.qty) : '') +
                      '<br>Resi ' + esc(d.resi) + ' &middot; Order ' + esc(d.order_id);
      })
      .catch(function(){});
  }

  el('rkOrang').addEventListener('click', function (e) {
    var box = e.target.closest('.rk-box'); if (!box) return;
    Array.prototype.forEach.call(document.querySelectorAll('.rk-box'), function (b) { b.classList.remove('aktif'); });
    box.classList.add('aktif');
    fetch('/packing/rincian_orang?uid=' + box.dataset.uid + '&dari=' + el('rkDari').value + '&sampai=' + el('rkSampai').value,
          { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (o) {
        if (!o.ok) return;
        el('rkRincian').style.display = 'block';
        el('rkRincianJudul').textContent = 'Paket yang dipacking ' + o.nama + ' (' + (o.rows || []).length + ' paket)';
        el('rkRincianIsi').innerHTML = (o.rows || []).length
          ? o.rows.map(function (r) {
              return '<tr><td style="white-space:nowrap">' + esc(r.waktu) + '</td>' +
                     '<td style="font-family:ui-monospace,Menlo,monospace;font-weight:600">' + esc(r.resi) + '</td>' +
                     '<td>' + esc(r.produk || '-') + '</td>' +
                     '<td class="kanan">' + esc(r.qty || '') + '</td></tr>';
            }).join('')
          : '<tr><td colspan="4" class="rk-kosong">Belum ada</td></tr>';
        el('rkRincian').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }).catch(function () {});
  });

  el('rkTampil').addEventListener('click', muat);
  el('rkBulan').addEventListener('click', function(){
    var d = new Date();
    el('rkDari').value = new Date(d.getFullYear(), d.getMonth(), 1).toISOString().slice(0,10);
    el('rkSampai').value = hariIni; muat();
  });
  el('rkCari').addEventListener('click', cari);
  el('rkResi').addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); cari(); } });
  muat();
})();
</script>
