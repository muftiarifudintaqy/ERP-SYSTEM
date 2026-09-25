<style>
 .rk{max-width:1000px;margin:0 auto}
 .rk-kartu{background:#fff;border-radius:16px;padding:20px 24px;box-shadow:0 1px 3px rgba(0,0,0,.08);margin-bottom:16px}
 .rk-kartu h5{margin:0 0 14px;font-weight:700}
 .rk-alat{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap}
 .rk-alat label{display:block;font-size:.8rem;color:#64748b;margin-bottom:4px}
 .rk-alat input{padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px}
 .rk-alat button{padding:9px 18px;border:0;border-radius:8px;background:#1F4696;color:#fff;font-weight:600;cursor:pointer}
 .rk-orang{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
 @media(max-width:900px){.rk-orang{grid-template-columns:repeat(2,1fr)}}
 @media(max-width:520px){.rk-orang{grid-template-columns:1fr}}
 .rk-box{border:1px solid #e2e8f0;border-radius:14px;padding:16px 18px;
         display:flex;flex-direction:column;align-items:flex-start;min-width:0}
 .rk-box .n{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
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
 .rk-foto{width:56px;height:56px;border-radius:12px;object-fit:cover;border:2px solid #1F4696;margin-bottom:10px;background:#f1f5f9}
 .rk-box.kosong{opacity:.55}
 .rk-box.kosong .a{color:#94a3b8}
</style>

<div class="rk">
  <div class="rk-kartu">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:6px">
      <h5 style="margin:0">Rekap Packing</h5>
      <span style="color:#16a34a;font-size:.8rem">&#9679; Update real-time</span>
    </div>
    <div id="rkJam" style="font-size:2rem;font-weight:700;letter-spacing:1px;color:#0f172a;
         font-variant-numeric:tabular-nums;line-height:1">--:--:--</div>
    <div id="rkTgl" style="color:#64748b;font-size:.85rem;margin-bottom:14px"></div>
    <div class="rk-alat">
      <div style="flex:1;min-width:280px">
        <label>Rentang tanggal</label>
        <input type="text" id="rkRange" readonly
               style="width:100%;cursor:pointer;background:#fff;padding:9px 12px;
                      border:1px solid #cbd5e1;border-radius:8px">
        <input type="hidden" id="rkDari"><input type="hidden" id="rkSampai">
      </div>
      <button type="button" id="rkTampil">Tampilkan</button>
    </div>
  </div>

  <div class="rk-kartu" style="background:linear-gradient(135deg,#1F4696,#2563eb);color:#fff">
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;text-align:center">
      <div>
        <div style="opacity:.8;font-size:.8rem;margin-bottom:4px">Total paket dipacking</div>
        <div id="rkTotalPaket" style="font-size:3rem;font-weight:800;line-height:1;font-variant-numeric:tabular-nums">0</div>
      </div>
      <div>
        <div style="opacity:.8;font-size:.8rem;margin-bottom:4px">Total barang (pcs)</div>
        <div id="rkTotalPcs" style="font-size:2rem;font-weight:700;line-height:1.3;font-variant-numeric:tabular-nums">0</div>
      </div>
      <div>
        <div style="opacity:.8;font-size:.8rem;margin-bottom:4px">Yang sudah mulai</div>
        <div id="rkTotalOrang" style="font-size:2rem;font-weight:700;line-height:1.3">0 / 4</div>
      </div>
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
  var selisih = 0;
  function detak() {
    var d = new Date(Date.now() + selisih), dua = function (x) { return String(x).padStart(2, '0'); };
    el('rkJam').textContent = dua(d.getHours()) + ':' + dua(d.getMinutes()) + ':' + dua(d.getSeconds());
    el('rkTgl').textContent = d.toLocaleDateString('id-ID',
      { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) + ' (WIB)';
  }
  function tglLokal(d) {
    var dua = function (x) { return String(x).padStart(2, '0'); };
    return d.getFullYear() + '-' + dua(d.getMonth() + 1) + '-' + dua(d.getDate());
  }
  var hariIni = tglLokal(new Date());
  el('rkDari').value = hariIni; el('rkSampai').value = hariIni;

  function muat(){
    fetch('/packing/rekap_data?dari=' + el('rkDari').value + '&sampai=' + el('rkSampai').value,
          { credentials:'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(o){
        if (!o.ok) { el('rkOrang').innerHTML = '<div class="rk-kosong">Tidak punya akses.</div>'; return; }
        if (o.server) selisih = new Date(o.server).getTime() - Date.now();
        el('rkOrang').innerHTML = (o.per_orang || []).length
          ? o.per_orang.map(function(p){
              var f = p.foto ? '/assets/img/user/' + esc(p.foto) : '/assets/img/user/default.png';
              return '<div class="rk-box' + (Number(p.paket) ? '' : ' kosong') + '" data-uid="' + esc(p.id) + '" data-nama="' + esc(p.nama || '-') + '">' +
                     '<img class="rk-foto" src="' + f + '" onerror="this.src=\'/assets/img/user/default.png\'">' +
                     '<div class="n">' + esc(p.nama || '-') + '</div>' +
                     '<div class="a">' + esc(p.paket) + '</div><div class="k">paket &middot; ' + esc(p.pcs) + ' pcs</div>' +
                     '<div class="k" style="margin-top:6px">' +
                     (p.mulai ? esc(p.mulai.slice(0,5)) + ' &ndash; ' + esc((p.selesai||'').slice(0,5)) : 'belum mulai') +
                     '</div></div>';
            }).join('')
          : '<div class="rk-kosong">Belum ada scan pada rentang ini.</div>';

        var tp = 0, tc = 0, to = 0;
        (o.per_orang || []).forEach(function (p) {
          tp += Number(p.paket) || 0; tc += Number(p.pcs) || 0;
          if (Number(p.paket)) to++;
        });
        el('rkTotalPaket').textContent = tp.toLocaleString('id-ID');
        el('rkTotalPcs').textContent = tc.toLocaleString('id-ID');
        el('rkTotalOrang').textContent = to + ' / ' + (o.per_orang || []).length;

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
  // Pemilih rentang sama seperti halaman Order: sekali klik pintasan, data
  // langsung termuat -- tidak perlu menggulir kalender dari awal tiap kali.
  function pasangRange() {
    var $r = window.jQuery && jQuery('#rkRange');
    if (!$r || !$r.daterangepicker) {
      el('rkRange').type = 'date';
      el('rkRange').addEventListener('change', function () {
        el('rkDari').value = el('rkSampai').value = this.value; muat();
      });
      return;
    }
    var m = window.moment;
    $r.daterangepicker({
      locale: {
        format: 'DD/MM/YYYY', applyLabel: 'Terapkan', cancelLabel: 'Batal',
        customRangeLabel: 'Pilih sendiri',
        daysOfWeek: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
        monthNames: ['Januari','Februari','Maret','April','Mei','Juni',
                     'Juli','Agustus','September','Oktober','November','Desember'],
        firstDay: 1
      },
      opens: 'right', showDropdowns: true,
      startDate: m(), endDate: m(),
      maxDate: m(),
      ranges: {
        'Hari Ini':          [m(), m()],
        'Kemarin':           [m().subtract(1,'days'), m().subtract(1,'days')],
        '7 Hari Terakhir':   [m().subtract(6,'days'), m()],
        '30 Hari Terakhir':  [m().subtract(29,'days'), m()],
        'Bulan Ini':         [m().startOf('month'), m()],
        'Bulan Lalu':        [m().subtract(1,'month').startOf('month'),
                              m().subtract(1,'month').endOf('month')]
      }
    }, function (mulai, akhir) {
      el('rkDari').value   = mulai.format('YYYY-MM-DD');
      el('rkSampai').value = akhir.format('YYYY-MM-DD');
      muat();
    });
    $r.val(m().format('DD/MM/YYYY') + ' - ' + m().format('DD/MM/YYYY'));
  }
  pasangRange();
  el('rkCari').addEventListener('click', cari);
  el('rkResi').addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); cari(); } });
  detak(); muat();
  setInterval(detak, 1000);
  // Angka disegarkan tiap 30 detik supaya HR melihat perkembangan tanpa refresh.
  // Tiap 5 detik: angka orang, total, dan rincian yang sedang terbuka ikut
  // menyegarkan diri, jadi HR tidak perlu memuat ulang halaman sama sekali.
  setInterval(function () {
    if (el('rkSampai').value < hariIni) return;
    if (document.hidden) return;
    muat();
    var aktif = document.querySelector('.rk-box.aktif');
    if (aktif) aktif.click();
  }, 5000);
})();
</script>
