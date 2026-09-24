<style>
 .pk{max-width:760px;margin:0 auto}
 .pk-atas{background:#fff;border-radius:16px;padding:22px 24px;box-shadow:0 1px 3px rgba(0,0,0,.08);display:flex;align-items:center;gap:18px;margin-bottom:16px}
 .pk-foto{width:68px;height:68px;border-radius:50%;object-fit:cover;border:3px solid #1F4696}
 .pk-nama{font-size:1.15rem;font-weight:700;color:#0f172a}
 .pk-ket{color:#64748b;font-size:.85rem}
 .pk-angka{margin-left:auto;text-align:right}
 .pk-angka b{display:block;font-size:2.6rem;line-height:1;color:#1F4696}
 .pk-kotak{background:#fff;border-radius:16px;padding:22px 24px;box-shadow:0 1px 3px rgba(0,0,0,.08);margin-bottom:16px}
 .pk-kotak input{width:100%;padding:16px 18px;font-size:1.3rem;border:2px solid #cbd5e1;border-radius:12px;font-family:ui-monospace,Menlo,monospace}
 .pk-kotak input:focus{outline:none;border-color:#1F4696}
 .pk-hasil{margin-top:14px;border-radius:12px;padding:16px 18px;display:none}
 .pk-hasil.ok{background:#dcfce7;border:2px solid #16a34a}
 .pk-hasil.dobel{background:#fee2e2;border:2px solid #dc2626}
 .pk-hasil.asing{background:#fef3c7;border:2px solid #d97706}
 .pk-hasil .j{font-size:1.1rem;font-weight:800;margin-bottom:4px}
 .pk-tabel{background:#fff;border-radius:16px;padding:18px 20px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
 .pk-tabel table{width:100%;border-collapse:collapse;font-size:.85rem}
 .pk-tabel th{text-align:left;color:#64748b;font-weight:600;padding:8px;border-bottom:1px solid #e2e8f0}
 .pk-tabel td{padding:8px;border-bottom:1px solid #f1f5f9}
 .pk-resi{font-family:ui-monospace,Menlo,monospace;font-weight:600}
 .pk-antre{color:#d97706;font-size:.8rem;margin-top:8px;display:none}
</style>

<div class="pk">
  <div class="pk-atas">
    <img class="pk-foto" id="pkFoto" src="<?= !empty($foto) ? base_url('assets/img/user/' . $foto) : base_url('assets/img/user/default.png') ?>" alt="">
    <div>
      <div class="pk-nama"><?= html_escape($user['full_name'] ?? '-') ?></div>
      <div class="pk-ket">Scan barcode resi tiap selesai satu paket</div>
    </div>
    <div class="pk-angka"><b id="pkJumlah">0</b><span class="pk-ket">paket hari ini</span></div>
  </div>

  <div class="pk-kotak">
    <input type="text" id="pkInput" placeholder="Tembak barcode resi di sini..." autocomplete="off" autofocus>
    <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
      <button type="button" id="pkKameraBuka"
        style="padding:10px 18px;border:0;border-radius:10px;background:#0f766e;color:#fff;font-weight:600;cursor:pointer">
        &#128247; Scan pakai kamera
      </button>
      <span id="pkKameraKet" style="color:#64748b;font-size:.8rem;align-self:center"></span>
    </div>
    <div id="pkKamera" style="display:none;margin-top:14px;position:relative;border-radius:14px;overflow:hidden;background:#000">
      <video id="pkVideo" playsinline muted style="width:100%;max-height:340px;object-fit:cover;display:block"></video>
      <div id="pkKameraPesan"
        style="position:absolute;left:0;right:0;bottom:0;background:rgba(15,23,42,.82);color:#fff;
               padding:10px 14px;font-size:.9rem;text-align:center">Arahkan barcode ke kamera...</div>
      <button type="button" id="pkKameraTutup"
        style="position:absolute;top:10px;right:10px;border:0;border-radius:8px;background:#dc2626;color:#fff;
               padding:8px 14px;font-weight:600;cursor:pointer">Tutup kamera</button>
    </div>
    <div class="pk-antre" id="pkAntre"></div>
    <div class="pk-hasil" id="pkHasil"></div>
  </div>

  <div class="pk-tabel">
    <h5 style="margin:0 0 12px;font-weight:700">Paket yang saya packing hari ini</h5>
    <div style="overflow-x:auto">
      <table><thead><tr><th>Jam</th><th>Resi</th><th>Produk</th><th style="text-align:right">Qty</th></tr></thead>
      <tbody id="pkIsi"><tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px">Belum ada</td></tr></tbody></table>
    </div>
  </div>
</div>

<script>
(function () {
  var inp = document.getElementById('pkInput'), hasil = document.getElementById('pkHasil');
  var antre = JSON.parse(localStorage.getItem('pkAntre') || '[]');

  function esc(t){ var d=document.createElement('div'); d.textContent=t==null?'':String(t); return d.innerHTML; }
  function bunyi(ok){
    try{
      var AC=window.AudioContext||window.webkitAudioContext; if(!AC) return;
      var ac=new AC(), o=ac.createOscillator(), g=ac.createGain();
      o.type='square'; o.frequency.value = ok ? 1150 : 320;
      g.gain.setValueAtTime(.18, ac.currentTime);
      g.gain.exponentialRampToValueAtTime(.0001, ac.currentTime + (ok?.13:.42));
      o.connect(g); g.connect(ac.destination); o.start(); o.stop(ac.currentTime + (ok?.15:.45));
    }catch(e){}
  }
  function tampil(kelas, judul, isi){
    hasil.className = 'pk-hasil ' + kelas;
    hasil.innerHTML = '<div class="j">' + judul + '</div><div>' + isi + '</div>';
    hasil.style.display = 'block';
  }
  function tampilAntre(){
    var el = document.getElementById('pkAntre');
    el.style.display = antre.length ? 'block' : 'none';
    el.textContent = antre.length ? antre.length + ' scan menunggu koneksi, akan dikirim otomatis' : '';
    localStorage.setItem('pkAntre', JSON.stringify(antre));
  }

  function kirim(resi, dariAntre) {
    var fd = new FormData(); fd.append('resi', resi);
    return fetch('/packing/scan', { method:'POST', body:fd, credentials:'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(o){
        if (o.ok) {
          bunyi(true);
          document.getElementById('pkJumlah').textContent = o.hari_ini;
          tampil('ok', '&#10003; ' + esc(o.produk || 'Tersimpan') + (o.qty ? ' &times;' + o.qty : ''),
                 '<span class="pk-resi">' + esc(o.resi) + '</span> &middot; ' + esc(o.kurir || '') + ' &middot; ' + esc(o.jam));
          muatDaftar();
        } else {
          bunyi(false);
          tampil(o.jenis === 'dobel' ? 'dobel' : 'asing',
                 (o.jenis === 'dobel' ? '&#10007; SUDAH DISCAN' : '&#9888; RESI TIDAK DIKENAL'),
                 esc(o.pesan) + (o.produk ? '<br>' + esc(o.produk) : '') + '<br><span class="pk-resi">' + esc(resi) + '</span>');
        }
        return true;
      })
      .catch(function(){
        if (!dariAntre) { antre.push(resi); tampilAntre(); bunyi(false);
          tampil('asing', '&#9888; KONEKSI PUTUS', 'Scan disimpan sementara, akan dikirim saat koneksi kembali.<br><span class="pk-resi">' + esc(resi) + '</span>'); }
        return false;
      });
  }

  function prosesAntre(){
    if (!antre.length) return;
    var r = antre[0];
    kirim(r, true).then(function(sukses){ if (sukses) { antre.shift(); tampilAntre(); prosesAntre(); } });
  }

  function muatDaftar(){
    fetch('/packing/milik_saya', { credentials:'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(o){
        if (!o.ok) return;
        document.getElementById('pkJumlah').textContent = o.hari_ini;
        var h = (o.rows || []).map(function(r){
          return '<tr><td>' + esc(r.jam) + '</td><td class="pk-resi">' + esc(r.resi) +
                 '</td><td>' + esc(r.produk || '-') + '</td><td style="text-align:right">' + esc(r.qty || '') + '</td></tr>';
        }).join('');
        document.getElementById('pkIsi').innerHTML = h ||
          '<tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px">Belum ada</td></tr>';
      }).catch(function(){});
  }

  // Scanner mode keyboard: mengetik cepat lalu menekan Enter.
  inp.addEventListener('keydown', function(e){
    if (e.key !== 'Enter') return;
    e.preventDefault();
    var v = inp.value.trim(); inp.value = '';
    if (v) kirim(v, false);
  });
  // Kotak scan harus selalu siap menerima tembakan berikutnya.
  setInterval(function(){ if (!kam.jalan && document.activeElement !== inp) inp.focus(); }, 800);
  document.addEventListener('click', function(e){ if (!kam.jalan && !e.target.closest('button')) inp.focus(); });

  // Mode kamera: dibiarkan menyala sampai ditekan Tutup. Setelah satu barcode
  // terbaca, jeda 2 detik supaya paket berikutnya tidak terbaca dobel, lalu
  // otomatis siap lagi -- tidak perlu buka-tutup kamera tiap paket.
  var kam = { stream: null, detektor: null, jalan: false, jeda: false, terakhir: '' };

  function kamPesan(t, warna) {
    var el = document.getElementById('pkKameraPesan');
    el.textContent = t;
    el.style.background = warna || 'rgba(15,23,42,.82)';
  }

  function kamBaca() {
    if (!kam.jalan) return;
    if (kam.jeda) { setTimeout(kamBaca, 200); return; }
    var v = document.getElementById('pkVideo');
    kam.detektor.detect(v)
      .then(function (hasil) {
        if (hasil && hasil.length) {
          var kode = String(hasil[0].rawValue || '').trim();
          if (kode && kode !== kam.terakhir) {
            kam.terakhir = kode; kam.jeda = true;
            kamPesan('Terbaca: ' + kode, 'rgba(22,163,74,.92)');
            kirim(kode, false);
            setTimeout(function () {
              kam.jeda = false; kam.terakhir = '';
              kamPesan('Siap. Arahkan barcode berikutnya...');
            }, 2000);
          }
        }
        setTimeout(kamBaca, 180);
      })
      .catch(function () { setTimeout(kamBaca, 400); });
  }

  function kamBuka() {
    if (!('BarcodeDetector' in window)) {
      document.getElementById('pkKameraKet').textContent =
        'Kamera tidak didukung peramban ini. Pakai scanner atau ketik manual.';
      return;
    }
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment', width: { ideal: 1280 } } })
      .then(function (st) {
        kam.stream = st;
        var v = document.getElementById('pkVideo');
        v.srcObject = st; v.setAttribute('playsinline', ''); v.muted = true; v.play();
        document.getElementById('pkKamera').style.display = 'block';
        document.getElementById('pkKameraBuka').style.display = 'none';
        kam.detektor = new BarcodeDetector({
          formats: ['code_128', 'code_39', 'ean_13', 'ean_8', 'itf', 'qr_code']
        });
        kam.jalan = true; kam.jeda = false; kam.terakhir = '';
        kamPesan('Arahkan barcode ke kamera...');
        kamBaca();
      })
      .catch(function () {
        document.getElementById('pkKameraKet').textContent = 'Kamera tidak bisa dibuka. Izinkan akses kamera dulu.';
      });
  }

  function kamTutup() {
    kam.jalan = false;
    if (kam.stream) { kam.stream.getTracks().forEach(function (t) { t.stop(); }); kam.stream = null; }
    document.getElementById('pkKamera').style.display = 'none';
    document.getElementById('pkKameraBuka').style.display = 'inline-block';
    inp.focus();
  }

  document.getElementById('pkKameraBuka').addEventListener('click', kamBuka);
  document.getElementById('pkKameraTutup').addEventListener('click', kamTutup);
  window.addEventListener('beforeunload', kamTutup);

  window.addEventListener('online', prosesAntre);
  tampilAntre(); prosesAntre(); muatDaftar();
  setInterval(muatDaftar, 60000);
})();
</script>
