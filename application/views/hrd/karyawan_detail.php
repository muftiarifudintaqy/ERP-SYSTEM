<?php
function baris($label, $nilai, $mono = false) {
    $nilai = trim((string)$nilai);
    echo '<dt>' . $label . '</dt><dd' . ($mono ? ' class="sensitif"' : '') . '>'
       . ($nilai !== '' ? html_escape($nilai) : '<span style="color:var(--tinta-3)">belum diisi</span>')
       . '</dd>';
}
?>
<div class="topbar">
  <div>
    <h1><?= html_escape($k['nama']) ?></h1>
    <p class="sub">
      <?= html_escape($k['jabatan'] ?: 'Jabatan belum diisi') ?><?= $k['divisi'] ? ' &middot; ' . html_escape($k['divisi']) : '' ?>
      &middot; <span class="tag <?= $k['status_karyawan'] === 'Aktif' ? 'tag-hijau' : 'tag-abu' ?>"><?= $k['status_karyawan'] ?></span>
    </p>
  </div>
  <div class="aksi">
    <a class="btn" href="<?= base_url('hrd/karyawan') ?>">Kembali</a>
    <a class="btn" href="<?= base_url('hrd/karyawan_form/' . $k['id']) ?>">Ubah data</a>
    <a class="btn btn-bahaya" href="<?= base_url('hrd/karyawan_hapus/' . $k['id']) ?>" data-konfirmasi="Hapus data <?= html_escape($k['nama']) ?>? Tindakan ini tidak bisa dibatalkan.">Hapus</a>
  </div>
</div>

<div class="grid g2" style="align-items:start">
  <div>
    <div class="kartu">
      <h2>Data pribadi</h2>
      <dl class="data-daftar">
        <?php
        baris('NIK', $k['nik'], true);
        baris('NPWP', $k['npwp'], true);
        baris('Tempat, tanggal lahir', trim($k['tempat_lahir'] . ($k['tanggal_lahir'] ? ', ' . date('d M Y', strtotime($k['tanggal_lahir'])) : ''), ', '));
        baris('Jenis kelamin', $k['jenis_kelamin']);
        baris('Agama', $k['agama']);
        baris('Status', $k['status_pernikahan']);
        baris('Jumlah anak', $k['jumlah_anak']);
        baris('Pendidikan terakhir', $k['pendidikan_terakhir']);
        baris('Alamat KTP', $k['alamat_ktp']);
        baris('Alamat domisili', $k['alamat_domisili']);
        ?>
      </dl>
    </div>

    <div class="kartu">
      <h2>Kontak</h2>
      <dl class="data-daftar">
        <?php
        baris('No. HP', $k['no_hp']);
        baris('Email', $k['email']);
        baris('Kontak darurat', $k['nama_kontak_darurat']);
        baris('Hubungan', $k['hubungan_kontak_darurat']);
        baris('Telp kontak darurat', $k['telp_kontak_darurat']);
        ?>
      </dl>
      <?php if ($k['no_hp']): ?>
        <div class="aksi" style="margin-top:12px">
          <a class="btn btn-kecil" target="_blank" href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/\D/', '', $k['no_hp'])) ?>">Chat WhatsApp</a>
        </div>
      <?php endif; ?>
    </div>

    <div class="kartu">
      <h2>Kepegawaian &amp; rekening</h2>
      <dl class="data-daftar">
        <?php
        baris('Tanggal masuk kerja', $k['tanggal_masuk'] ? date('d F Y', strtotime($k['tanggal_masuk'])) : '');
        baris('Masa kerja', $k['tanggal_masuk'] ? (function ($t) {
            $d = date_diff(date_create($t), date_create('today'));
            return $d->y . ' tahun ' . $d->m . ' bulan';
        })($k['tanggal_masuk']) : '');
        baris('Nama bank', $k['nama_bank']);
        baris('Nama di rekening', $k['nama_rekening']);
        baris('No. rekening', $k['no_rekening'], true);
        ?>
      </dl>
      <?php if ($k['no_rekening']): ?>
        <div class="aksi" style="margin-top:12px">
          <button class="btn btn-kecil" type="button" onclick="navigator.clipboard.writeText('<?= html_escape($k['no_rekening']) ?>');this.textContent='Tersalin'">Salin no. rekening</button>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($k['catatan_hrd']): ?>
      <div class="kartu"><h2>Catatan HRD</h2><p><?= nl2br(html_escape($k['catatan_hrd'])) ?></p></div>
    <?php endif; ?>
  </div>

  <div>
    <div class="kartu">
      <h2>Foto KTP</h2>
      <?php if ($k['foto_ktp']): ?>
        <?php
          // tautan-luar
          // Data yang diimpor dari form Google menyimpan tautan Drive
          // utuh, bukan nama berkas di server. Tanpa pemeriksaan ini
          // tautannya ditempel ke alamat ERP dan menghasilkan 404.
          $ktp_isi = $k['foto_ktp'];
          $ktp_luar = (bool)preg_match('#^https?://#i', $ktp_isi);
          $url = $ktp_luar ? $ktp_isi : base_url('hrd/berkas/ktp/' . $ktp_isi);
        ?>
        <?php
          // ktp-zoom
          // Drive punya dua alamat berbeda: satu untuk mengunduh, satu
          // untuk menampilkan gambar. Yang kedua bisa dipasang langsung
          // sebagai gambar, jadi KTP tampil di halaman tanpa pindah ke
          // Drive. Gambarnya hanya muncul bagi yang punya akses ke
          // berkasnya; kalau gagal, tombol ke Drive yang ditampilkan.
          $ktp_drive = '';
          if ($ktp_luar && preg_match('#(?:/d/|[?&]id=)([A-Za-z0-9_-]{20,})#', $ktp_isi, $m)) {
              $ktp_drive = 'https://drive.google.com/thumbnail?id=' . $m[1] . '&sz=w1600';
          }
        ?>
        <?php if ($ktp_drive): ?>
          <img class="ktp-pratinjau ktp-zoomable" src="<?= $ktp_drive ?>"
               alt="KTP <?= html_escape($k['nama']) ?>"
               data-drive="<?= html_escape($url) ?>"
               onerror="this.closest('.kartu').querySelector('.ktp-gagal').style.display='block'; this.style.display='none';">
          <p class="label" style="margin-top:8px">Klik gambar untuk memperbesar. Gulir untuk perbesar, seret untuk geser.</p>
          <p class="ktp-gagal" style="display:none">
            Gambar tidak bisa ditampilkan karena berkasnya terbatas di Google Drive.
            <br><a class="btn" href="<?= $url ?>" target="_blank" style="margin-top:8px">Buka di Google Drive</a>
          </p>
          <p style="margin-top:8px"><a href="<?= $url ?>" target="_blank">Unduh berkas asli di Drive</a></p>
        <?php elseif ($ktp_luar || preg_match('/\.pdf$/i', $ktp_isi)): ?>
          <p><a class="btn" href="<?= $url ?>" target="_blank"><?= $ktp_luar ? 'Buka di Google Drive' : 'Buka berkas PDF' ?></a></p>
        <?php else: ?>
          <a href="<?= $url ?>" target="_blank"><img class="ktp-pratinjau" src="<?= $url ?>" alt="KTP <?= html_escape($k['nama']) ?>"></a>
          <p class="label" style="margin-top:8px">Klik gambar untuk memperbesar.</p>
        <?php endif; ?>
      <?php else: ?>
        <div class="kosong"><b>KTP belum diunggah</b>Minta karyawan mengisi ulang form data karyawan.</div>
      <?php endif; ?>
    </div>

    <div class="kartu">
      <h2>Barang kantor yang dipegang</h2>
      <?php if (!$barang): ?>
        <div class="kosong"><b>Belum ada barang tercatat</b>Tambah lewat menu Inventaris.</div>
      <?php else: ?>
        <table>
          <tbody>
          <?php foreach ($barang as $b): ?>
            <tr>
              <td>
                <b><?= html_escape($b['jenis_barang']) ?></b> <?= html_escape($b['merek']) ?>
                <small style="display:block;color:var(--tinta-3)">
                  <?= html_escape($b['serial_number'] ?: $b['tipe_hp'] ?: '-') ?>
                  <?= $b['no_hp_kantor'] ? ' &middot; ' . html_escape($b['no_hp_kantor']) : '' ?>
                </small>
              </td>
              <td class="rapat" style="text-align:right">
                <span class="tag <?= $b['kondisi'] === 'Baik' ? 'tag-hijau' : ($b['kondisi'] === 'Rusak Ringan' ? 'tag-kuning' : 'tag-merah') ?>"><?= $b['kondisi'] ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="kartu">
      <h2>Profil DISC</h2>
      <?php if (!$disc): ?>
        <div class="kosong"><b>Belum mengisi DISC</b>
          <?php if ($k['email']): ?>
            <a class="btn btn-kecil" style="margin-top:8px" href="mailto:<?= html_escape($k['email']) ?>?subject=DISC%20Test%20Montera&body=<?= rawurlencode(base_url('f/disc')) ?>">Kirim tautan tes</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <?php $u = $profil[$disc['tipe_utama']]; ?>
        <p style="margin-bottom:12px">
          <b style="color:<?= $u['warna'] ?>"><?= $disc['tipe_utama'] ?> &mdash; <?= $u['nama'] ?></b>
          (<?= $u['julukan'] ?>)<br>
          <span class="label"><?= $u['ringkas'] ?></span>
        </p>
        <?php foreach (['D','I','S','C'] as $h): $n = (int)$disc['net_' . strtolower($h)]; $lebar = min(100, max(4, $n)); ?>
          <div class="disc-bar">
            <span class="huruf" style="background:<?= $profil[$h]['warna'] ?>"><?= $h ?></span>
            <span class="disc-track"><span class="disc-fill" style="width:<?= $lebar ?>%;background:<?= $profil[$h]['warna'] ?>"></span></span>
            <span class="disc-nilai"><?= $n ?>%</span>
          </div>
        <?php endforeach; ?>
        <div class="aksi" style="margin-top:12px">
          <a class="btn btn-kecil" href="<?= base_url('hrd/disc_detail/' . $disc['id']) ?>">Lihat hasil lengkap</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div id="ktpLightbox" style="display:none;position:fixed;inset:0;z-index:9999;
     background:rgba(15,23,42,.92);align-items:center;justify-content:center">
  <button id="ktpTutup" style="position:absolute;top:18px;right:22px;width:40px;height:40px;
          border:0;border-radius:50%;background:rgba(255,255,255,.18);color:#fff;
          font-size:1.4rem;cursor:pointer">&times;</button>
  <div style="position:absolute;top:18px;left:22px;color:#fff;font-size:13px;opacity:.8">
    Gulir untuk perbesar &middot; seret untuk geser &middot; klik dua kali untuk kembali
  </div>
  <img id="ktpBesar" src="" alt="" style="max-width:92vw;max-height:88vh;
       border-radius:10px;cursor:grab;transition:transform .12s ease-out">
</div>

<script>
(function () {
  var gambar = document.querySelector('.ktp-zoomable');
  if (!gambar) return;

  var kotak  = document.getElementById('ktpLightbox');
  var besar  = document.getElementById('ktpBesar');
  var skala = 1, geserX = 0, geserY = 0, seret = false, mulaiX = 0, mulaiY = 0;

  function terapkan() {
    besar.style.transform = 'translate(' + geserX + 'px,' + geserY + 'px) scale(' + skala + ')';
  }
  function setel() { skala = 1; geserX = geserY = 0; terapkan(); }

  gambar.addEventListener('click', function () {
    besar.src = gambar.src;
    kotak.style.display = 'flex';
    setel();
  });

  document.getElementById('ktpTutup').onclick = function () { kotak.style.display = 'none'; };
  kotak.addEventListener('click', function (e) {
    if (e.target === kotak) kotak.style.display = 'none';
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') kotak.style.display = 'none';
  });

  // Gulir untuk memperbesar, dengan batas supaya tidak hilang dari layar.
  kotak.addEventListener('wheel', function (e) {
    e.preventDefault();
    skala = Math.min(6, Math.max(1, skala + (e.deltaY < 0 ? 0.18 : -0.18)));
    if (skala === 1) { geserX = geserY = 0; }
    terapkan();
  }, { passive: false });

  besar.addEventListener('dblclick', setel);
  besar.addEventListener('mousedown', function (e) {
    seret = true; mulaiX = e.clientX - geserX; mulaiY = e.clientY - geserY;
    besar.style.cursor = 'grabbing'; e.preventDefault();
  });
  window.addEventListener('mousemove', function (e) {
    if (!seret) return;
    geserX = e.clientX - mulaiX; geserY = e.clientY - mulaiY; terapkan();
  });
  window.addEventListener('mouseup', function () {
    seret = false; besar.style.cursor = 'grab';
  });
})();
</script>
