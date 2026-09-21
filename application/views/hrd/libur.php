<?php
$nama_jenis = [
    'nasional'     => 'Libur Nasional',
    'cuti_bersama' => 'Cuti Bersama',
    'internal'     => 'Libur Kantor',
];
$nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
               7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$nama_hari  = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'];

$tulis_tanggal = function ($t) use ($nama_bulan, $nama_hari) {
    $w = strtotime($t);
    return $nama_hari[(int)date('N', $w)] . ', ' . (int)date('j', $w)
         . ' ' . $nama_bulan[(int)date('n', $w)] . ' ' . date('Y', $w);
};

// Tanggal libur disusun jadi peta supaya kalender bisa mencarinya cepat.
$peta = [];
foreach ($rows as $r) { $peta[$r['tanggal']] = $r; }

$hari_ini    = date('Y-m-d');
$bulan_awal  = ((int)$tahun === (int)date('Y')) ? (int)date('n') : 1;
?>

<style>
.lb-tabel td, .lb-tabel th { vertical-align: middle; }
.lb-tag { display:inline-block; padding:3px 9px; border-radius:99px; font-size:11px; font-weight:700; white-space:nowrap; }
.lb-nasional     { background:#FEE2E2; color:#991B1B; }
.lb-cuti_bersama { background:#FFF4E6; color:#B45309; }
.lb-internal     { background:#E9F2FE; color:#1F4696; }
.lb-lewat td { opacity:.5; }

.lb-form { display:grid; grid-template-columns:1fr 2fr 1fr auto; gap:14px; align-items:end; }
.lb-form label { display:block; font-size:13px; font-weight:700; margin-bottom:0; }
.lb-form input, .lb-form select {
  display:block; width:100%; box-sizing:border-box; margin-top:6px;
  padding:9px 11px; border:1.5px solid #E3E1F0; border-radius:9px;
  font-size:14px; font-family:inherit;
}
.lb-tahun { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.lb-tahun a { padding:6px 14px; border-radius:99px; border:1.5px solid #E3E1F0;
  font-size:13px; font-weight:600; text-decoration:none; color:#444; }
.lb-tahun a.aktif { background:#4520A6; border-color:#4520A6; color:#fff; }

/* kalender satu bulan dengan panah geser */
.kal-kepala { display:flex; align-items:center; justify-content:center; gap:18px; margin-bottom:16px; }
.kal-panah {
  width:40px; height:40px; border-radius:50%; border:1.5px solid #E3E1F0;
  background:#fff; cursor:pointer; font-size:19px; line-height:1; color:#4520A6;
  display:inline-flex; align-items:center; justify-content:center; font-family:inherit;
}
.kal-panah:hover:not(:disabled) { background:#F3F0FC; border-color:#C9B8F0; }
.kal-panah:disabled { opacity:.3; cursor:default; }
.kal-nama { font-size:19px; font-weight:800; min-width:210px; text-align:center; }

.kal-bulan { display:none; max-width:520px; margin:0 auto; }
.kal-bulan.tampil { display:block; }
.kal-tabel { width:100%; border-collapse:separate; border-spacing:4px; table-layout:fixed; }
.kal-tabel th { font-size:11px; color:#8A8A9A; font-weight:700; padding:0 0 6px; text-align:center; border:0; }
.kal-tabel td { text-align:center; padding:0; border:0; }
.kal-sel {
  display:block; padding:11px 0; font-size:14px; border-radius:9px; line-height:1.2;
  border:1.5px solid transparent; cursor:pointer; font-family:inherit; width:100%;
  background:#FAFAFD; color:#1A1A2E;
}
.kal-sel:hover { border-color:#4520A6; }
.kal-minggu       { color:#C0392B; }
.kal-nasional     { background:#FEE2E2; color:#991B1B; font-weight:800; }
.kal-cuti_bersama { background:#FFF4E6; color:#B45309; font-weight:800; }
.kal-internal     { background:#E9F2FE; color:#1F4696; font-weight:800; }
.kal-kini { outline:2px solid #4520A6; outline-offset:1px; }
.kal-ket { display:flex; gap:16px; flex-wrap:wrap; justify-content:center; margin-bottom:18px; font-size:12px; }
.kal-ket span { display:inline-flex; align-items:center; gap:6px; }
.kal-kotak { width:14px; height:14px; border-radius:4px; display:inline-block; }
.kal-pesan { text-align:center; margin-top:14px; font-size:13px; color:#6B6B7B; min-height:20px; }
@media (max-width:820px){ .lb-form { grid-template-columns:1fr; } }
</style>

<div class="card" style="margin-bottom:18px">
  <div class="card-body">
    <h2 style="margin-top:0">Tambah hari libur</h2>
    <p class="label" style="margin-bottom:14px">
      Tanggal di daftar ini tidak dihitung sebagai bolos di halaman Belum Absen
      maupun rekap kehadiran. Pakai <b>Libur Kantor</b> untuk libur yang
      ditetapkan sendiri di luar tanggal merah nasional.
    </p>

    <?php if ($this->session->flashdata('pesan')): ?>
      <div style="background:#E7F7ED;border:1px solid #A7E3BF;color:#146C43;padding:10px 12px;border-radius:8px;margin-bottom:14px">
        <?= html_escape($this->session->flashdata('pesan')) ?>
      </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('gagal')): ?>
      <div style="background:#FDECEC;border:1px solid #F5B5B5;color:#9B1C1C;padding:10px 12px;border-radius:8px;margin-bottom:14px">
        <?= html_escape($this->session->flashdata('gagal')) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('hrd/libur_simpan') ?>" class="lb-form" id="lbForm">
      <label>Tanggal
        <input type="date" name="tanggal" id="lbTanggal" required>
      </label>
      <label>Keterangan
        <input type="text" name="keterangan" id="lbKeterangan" placeholder="mis. Libur HUT perusahaan" required>
      </label>
      <label>Jenis
        <select name="jenis" id="lbJenis">
          <option value="internal">Libur Kantor</option>
          <option value="nasional">Libur Nasional</option>
          <option value="cuti_bersama">Cuti Bersama</option>
        </select>
      </label>
      <button class="btn btn-utama" type="submit" style="height:42px">Simpan</button>
    </form>
  </div>
</div>

<div class="card" style="margin-bottom:18px">
  <div class="card-body">
    <h2 style="margin-top:0;text-align:center">Kalender <?= (int)$tahun ?></h2>

    <?php if (!empty($tahun_ada)): ?>
      <div class="lb-tahun" style="justify-content:center">
        <?php foreach ($tahun_ada as $t): ?>
          <a href="<?= base_url('hrd/libur?tahun=' . (int)$t['th']) ?>"
             class="<?= ((int)$t['th'] === (int)$tahun) ? 'aktif' : '' ?>"><?= (int)$t['th'] ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="kal-ket">
      <span><i class="kal-kotak" style="background:#FEE2E2"></i> Libur Nasional</span>
      <span><i class="kal-kotak" style="background:#FFF4E6"></i> Cuti Bersama</span>
      <span><i class="kal-kotak" style="background:#E9F2FE"></i> Libur Kantor</span>
      <span><i class="kal-kotak" style="background:#fff;border:1px solid #E3E1F0"></i> <span style="color:#C0392B">Minggu</span></span>
    </div>

    <div class="kal-kepala">
      <button type="button" class="kal-panah" id="kalMundur" title="Bulan sebelumnya">&#8249;</button>
      <div class="kal-nama" id="kalNama"></div>
      <button type="button" class="kal-panah" id="kalMaju" title="Bulan berikutnya">&#8250;</button>
    </div>

    <?php for ($m = 1; $m <= 12; $m++): ?>
      <?php
        $awal  = mktime(0, 0, 0, $m, 1, (int)$tahun);
        $jml   = (int)date('t', $awal);
        $geser = (int)date('N', $awal) - 1;   // Senin = kolom 0
      ?>
      <div class="kal-bulan" data-bulan="<?= $m ?>" data-nama="<?= $nama_bulan[$m] ?> <?= (int)$tahun ?>">
        <table class="kal-tabel">
          <thead>
            <tr><th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th><th>Min</th></tr>
          </thead>
          <tbody>
          <?php
            $sel = 0;
            echo '<tr>';
            for ($i = 0; $i < $geser; $i++) { echo '<td></td>'; $sel++; }
            for ($d = 1; $d <= $jml; $d++) {
                if ($sel % 7 === 0 && $sel > 0) { echo '</tr><tr>'; }
                $td    = sprintf('%04d-%02d-%02d', (int)$tahun, $m, $d);
                $dow   = (int)date('N', strtotime($td));
                $kelas = 'kal-sel';
                $ket   = '';
                $jenis = '';
                if (isset($peta[$td])) {
                    $jenis  = $peta[$td]['jenis'];
                    $kelas .= ' kal-' . $jenis;
                    $ket    = $peta[$td]['keterangan'];
                } elseif ($dow === 7) {
                    $kelas .= ' kal-minggu';
                }
                if ($td === $hari_ini) { $kelas .= ' kal-kini'; }
                echo '<td><button type="button" class="' . $kelas . '"'
                   . ' data-tgl="' . $td . '"'
                   . ' data-ket="' . html_escape($ket) . '"'
                   . ' data-jenis="' . html_escape($jenis) . '"'
                   . ($ket ? ' title="' . html_escape($ket) . '"' : '')
                   . '>' . $d . '</button></td>';
                $sel++;
            }
            while ($sel % 7 !== 0) { echo '<td></td>'; $sel++; }
            echo '</tr>';
          ?>
          </tbody>
        </table>
      </div>
    <?php endfor; ?>

    <div class="kal-pesan" id="kalPesan">
      Klik tanggal untuk mengisi formulir di atas.
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h2 style="margin-top:0">Daftar hari libur <?= (int)$tahun ?></h2>

    <?php if (empty($rows)): ?>
      <p class="label">Belum ada hari libur tercatat untuk tahun ini.</p>
    <?php else: ?>
      <table class="table lb-tabel">
        <thead>
          <tr>
            <th style="width:60px">#</th>
            <th>Tanggal</th>
            <th>Keterangan</th>
            <th style="width:150px">Jenis</th>
            <th style="width:90px">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php $n = 1; foreach ($rows as $r): ?>
          <tr class="<?= ($r['tanggal'] < $hari_ini) ? 'lb-lewat' : '' ?>">
            <td><?= $n++ ?></td>
            <td><?= html_escape($tulis_tanggal($r['tanggal'])) ?></td>
            <td><?= html_escape($r['keterangan']) ?></td>
            <td><span class="lb-tag lb-<?= html_escape($r['jenis']) ?>">
                  <?= html_escape($nama_jenis[$r['jenis']] ?? $r['jenis']) ?>
                </span></td>
            <td>
              <a class="btn btn-bahaya" href="<?= base_url('hrd/libur_hapus/' . (int)$r['id']) ?>"
                 data-konfirmasi="Hapus hari libur ini? Tanggal itu akan dihitung sebagai hari kerja biasa.">Hapus</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <p class="label" style="margin-top:14px">
        <?= count($rows) ?> tanggal tercatat. Yang sudah lewat ditampilkan pudar.
      </p>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  var bulan  = document.querySelectorAll('.kal-bulan');
  var nama   = document.getElementById('kalNama');
  var mundur = document.getElementById('kalMundur');
  var maju   = document.getElementById('kalMaju');
  var pesan  = document.getElementById('kalPesan');
  if (!bulan.length) return;

  // Dibuka di bulan berjalan kalau tahunnya tahun ini, selain itu Januari.
  var kini = <?= (int)$bulan_awal ?>;

  function tampilkan(m) {
    kini = m;
    for (var i = 0; i < bulan.length; i++) {
      var b = bulan[i];
      var cocok = parseInt(b.getAttribute('data-bulan'), 10) === m;
      b.classList.toggle('tampil', cocok);
      if (cocok) { nama.textContent = b.getAttribute('data-nama'); }
    }
    mundur.disabled = (m <= 1);
    maju.disabled   = (m >= 12);
  }

  mundur.addEventListener('click', function () { if (kini > 1)  tampilkan(kini - 1); });
  maju.addEventListener('click',   function () { if (kini < 12) tampilkan(kini + 1); });

  // Panah kiri-kanan di papan ketik ikut menggeser bulan.
  document.addEventListener('keydown', function (e) {
    if (e.target && /INPUT|SELECT|TEXTAREA/.test(e.target.tagName)) return;
    if (e.key === 'ArrowLeft'  && kini > 1)  tampilkan(kini - 1);
    if (e.key === 'ArrowRight' && kini < 12) tampilkan(kini + 1);
  });

  // Klik tanggal mengisi formulir di atas. Tanggal yang sudah jadi hari
  // libur ikut membawa keterangannya, jadi mengubahnya tinggal edit lalu
  // simpan ulang.
  var isiTgl = document.getElementById('lbTanggal');
  var isiKet = document.getElementById('lbKeterangan');
  var isiJen = document.getElementById('lbJenis');

  document.querySelectorAll('.kal-sel').forEach(function (sel) {
    sel.addEventListener('click', function () {
      var tgl   = this.getAttribute('data-tgl');
      var ket   = this.getAttribute('data-ket');
      var jenis = this.getAttribute('data-jenis');

      isiTgl.value = tgl;
      isiKet.value = ket || '';
      if (jenis) { isiJen.value = jenis; }

      pesan.textContent = ket
        ? tgl + ' sudah tercatat: ' + ket + '. Ubah lalu Simpan untuk memperbaruinya.'
        : tgl + ' dipilih. Isi keterangannya lalu tekan Simpan.';

      document.getElementById('lbForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (!ket) { isiKet.focus(); }
    });
  });

  tampilkan(kini);
})();
</script>
