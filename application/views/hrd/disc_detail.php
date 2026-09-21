<?php
$u  = $profil[$d['tipe_utama']] ?? reset($profil);
$k2 = $profil[$d['tipe_kedua']] ?? reset($profil);

$ai     = (isset($ai) && is_array($ai)) ? $ai : [];
$sumber = $ai['_sumber'] ?? 'cadangan';

$nilai = [
    'D' => (int)$d['net_d'], 'I' => (int)$d['net_i'],
    'S' => (int)$d['net_s'], 'C' => (int)$d['net_c'],
];
$poin = [
    'D' => (int)$d['most_d'], 'I' => (int)$d['most_i'],
    'S' => (int)$d['most_s'], 'C' => (int)$d['most_c'],
];
$sebar  = max($nilai) - min($nilai);
$rerata = array_sum($nilai) / 4;

$cadanganWarna = ['D' => '#ef4444', 'I' => '#f97316', 'S' => '#22c55e', 'C' => '#3b82f6'];
$warna = function ($h) use ($profil, $cadanganWarna) {
    return $profil[$h]['warna'] ?? $cadanganWarna[$h];
};

$daftar = function ($k) use ($ai) {
    return (!empty($ai[$k]) && is_array($ai[$k])) ? $ai[$k] : [];
};
?>

<style>
/* Semua aturan dikurung .dsc supaya tidak bocor ke halaman lain */
.dsc { max-width: 760px; margin: 0 auto; }
.dsc-kartu {
  background: #fff; border-radius: 18px; padding: 34px 30px;
  box-shadow: 0 10px 40px rgba(69, 32, 166, .07);
  border: 1px solid #EFEDF8; margin-bottom: 20px;
}
.dsc-atas { text-align: center; padding-bottom: 26px; }
.dsc-tile {
  display: inline-flex; align-items: center; justify-content: center;
  width: 92px; height: 92px; border-radius: 20px;
  color: #fff; font-size: 44px; font-weight: 800; line-height: 1;
  margin-bottom: 18px; box-shadow: 0 14px 30px -12px rgba(0,0,0,.45);
}
.dsc-atas h2 { font-size: 26px; margin: 0 0 6px; }
.dsc-atas .dsc-julukan { color: #6B7280; margin: 0; }
.dsc-atas .dsc-ringkas { max-width: 560px; margin: 16px auto 0; color: #4B5563; line-height: 1.7; }

.dsc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.dsc-dim {
  border: 2px solid #F1F1F6; border-radius: 14px; padding: 16px 18px;
  transition: border-color .2s;
}
.dsc-dim.dsc-utama { box-shadow: 0 6px 18px -10px rgba(0,0,0,.3); }
.dsc-dim-kepala { display: flex; align-items: center; gap: 9px; margin-bottom: 11px; }
.dsc-huruf {
  width: 30px; height: 30px; border-radius: 8px; color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 14px; flex: 0 0 auto;
}
.dsc-dim-nama { font-weight: 600; color: #1F2937; font-size: 14px; flex: 1; }
.dsc-dim-angka { font-weight: 800; font-size: 17px; color: #111827; }
.dsc-jalur { height: 11px; border-radius: 99px; background: #E9E9F0; overflow: hidden; }
.dsc-isi {
  height: 100%; border-radius: 99px; width: 0;
  transition: width 1.2s cubic-bezier(.22,1,.36,1);
}
.dsc-siap .dsc-isi { width: var(--lebar); }
.dsc-kecil { font-size: 12px; color: #9CA3AF; margin: 7px 0 0; }

.dsc-pisah { border: 0; border-top: 1px solid #EFEFF4; margin: 30px 0 24px; }
.dsc-judul { font-size: 17px; font-weight: 700; color: #111827; margin: 0 0 14px; }
.dsc-topik {
  font-size: 12px; font-weight: 800; letter-spacing: .06em;
  text-transform: uppercase; margin: 26px 0 10px;
}
.dsc-hijau { color: #15803D; }
.dsc-jingga { color: #C2410C; }

.dsc-ceklis { display: grid; grid-template-columns: 1fr 1fr; gap: 9px 22px; margin: 0; padding: 0; }
.dsc-ceklis li, .dsc-titik li {
  list-style: none; display: flex; gap: 9px; align-items: flex-start;
  font-size: 14px; color: #374151; line-height: 1.65;
}
.dsc-titik { margin: 0; padding: 0; }
.dsc-tanda { color: #22C55E; font-weight: 800; flex: 0 0 auto; }
.dsc-titik li { margin-bottom: 6px; }
.dsc-bulat { color: #9CA3AF; flex: 0 0 auto; }

.dsc-tips { display: flex; gap: 12px; align-items: flex-start;
  padding: 13px 15px; margin-bottom: 9px; border-radius: 11px;
  background: #F6F4FF; font-size: 14px; color: #374151; line-height: 1.6; }
.dsc-nomor { flex: 0 0 26px; height: 26px; border-radius: 50%;
  color: #fff; display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; }

.dsc-peringatan { background: #FFF8F0; border-left: 5px solid #E0954B; }
.dsc-hrd { border-left: 5px solid #4520A6; }
.dsc-nota { font-size: 12.5px; color: #9CA3AF; line-height: 1.7; margin: 10px 0 0; }
.dsc-asal { text-align: right; font-size: 12px; color: #9CA3AF; margin: 4px 4px 24px; }

@media (max-width: 720px) {
  .dsc-grid, .dsc-ceklis { grid-template-columns: 1fr; }
  .dsc-kartu { padding: 24px 18px; }
}
@media print {
  .topbar .aksi { display: none; }
  .dsc-isi { transition: none; width: var(--lebar); }
}
</style>

<div class="topbar">
  <div>
    <h1><?= html_escape($d['nama']) ?></h1>
    <p class="sub"><?= html_escape($d['jabatan'] ?: '-') ?> &middot; diisi <?= date('d F Y H:i', strtotime($d['created_at'])) ?></p>
  </div>
  <div class="aksi">
    <a class="btn" href="<?= base_url('hrd/disc') ?>">Kembali</a>
    <a class="btn" href="<?= base_url('hrd/disc_ai_ulang/' . $d['id']) ?>" data-konfirmasi="Susun ulang analisis AI untuk hasil ini?">Analisis ulang</a>
    <button class="btn" type="button" onclick="window.print()">Cetak</button>
    <a class="btn btn-bahaya" href="<?= base_url('hrd/disc_hapus/' . $d['id']) ?>" data-konfirmasi="Hapus hasil DISC ini?">Hapus</a>
  </div>
</div>

<div class="dsc" id="dsc">

<?php if ($sebar < 8): ?>
  <div class="dsc-kartu dsc-peringatan">
    <p class="dsc-judul" style="color:#B76E24">Hasil ini kurang membedakan</p>
    <p style="margin:0;font-size:14px;line-height:1.7;color:#4B5563">Selisih dimensi tertinggi dan terendah cuma <b><?= $sebar ?> poin persen</b>. Responden memberi nilai hampir sama rata ke semua pernyataan, jadi tipe dominannya tidak bisa dipastikan. Pakai sebagai bahan obrolan, atau minta yang bersangkutan mengulang tes.</p>
  </div>
<?php endif; ?>

  <!-- ===== KARTU UTAMA ===== -->
  <div class="dsc-kartu">
    <div class="dsc-atas">
      <div class="dsc-tile" style="background:<?= $warna($d['tipe_utama']) ?>"><?= $d['tipe_utama'] ?></div>
      <h2><?= $u['nama'] ?></h2>
      <p class="dsc-julukan"><?= html_escape($ai['subtitle'] ?? $u['julukan']) ?></p>
      <?php if (!empty($ai['summary'])): ?>
        <p class="dsc-ringkas"><?= html_escape($ai['summary']) ?></p>
      <?php endif; ?>
    </div>

    <div class="dsc-grid">
      <?php foreach (['D','I','S','C'] as $h):
        $w     = $warna($h);
        $n     = $nilai[$h];
        $utama = ($h === $d['tipe_utama']);
      ?>
        <div class="dsc-dim<?= $utama ? ' dsc-utama' : '' ?>"<?= $utama ? ' style="border-color:' . $w . '"' : '' ?>>
          <div class="dsc-dim-kepala">
            <span class="dsc-huruf" style="background:<?= $w ?>"><?= $h ?></span>
            <span class="dsc-dim-nama"><?= $profil[$h]['nama'] ?? $h ?></span>
            <span class="dsc-dim-angka"><?= $n ?>%</span>
          </div>
          <div class="dsc-jalur">
            <div class="dsc-isi" style="--lebar:<?= max(2, min(100, $n)) ?>%;background:<?= $w ?>"></div>
          </div>
          <p class="dsc-kecil"><?= $poin[$h] ?> poin &middot; <?= sprintf('%+.0f', $n - $rerata) ?> dari rata-ratanya</p>
        </div>
      <?php endforeach; ?>
    </div>

    <hr class="dsc-pisah">

    <p class="dsc-judul">&#11088; Karakteristik Utama</p>
    <?php $traits = $daftar('traits'); ?>
    <?php if ($traits): ?>
      <ul class="dsc-ceklis">
        <?php foreach ($traits as $t): ?>
          <li><span class="dsc-tanda">&check;</span><span><?= html_escape($t) ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p style="font-size:14px;color:#374151"><?= $u['ringkas'] ?></p>
    <?php endif; ?>

    <p class="dsc-topik dsc-hijau">Kekuatan</p>
    <?php $kuat = $daftar('strengths'); ?>
    <?php if ($kuat): ?>
      <ul class="dsc-titik">
        <?php foreach ($kuat as $t): ?>
          <li><span class="dsc-bulat">&bull;</span><span><?= html_escape($t) ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p style="font-size:14px;color:#374151"><?= $u['kuat'] ?></p>
    <?php endif; ?>

    <p class="dsc-topik dsc-jingga">Area Pengembangan</p>
    <?php $kembang = $daftar('development_areas'); ?>
    <?php if ($kembang): ?>
      <ul class="dsc-titik">
        <?php foreach ($kembang as $t): ?>
          <li><span class="dsc-bulat">&bull;</span><span><?= html_escape($t) ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p style="font-size:14px;color:#374151"><?= $u['hati'] ?></p>
    <?php endif; ?>

    <?php if (!empty($ai['work_style']) || !empty($ai['cocok_di_peran'])): ?>
      <hr class="dsc-pisah">
      <?php if (!empty($ai['work_style'])): ?>
        <p class="dsc-topik" style="color:#4520A6">Gaya Kerja</p>
        <p style="font-size:14px;color:#374151;line-height:1.7;margin:0"><?= html_escape($ai['work_style']) ?></p>
      <?php endif; ?>
      <?php if (!empty($ai['cocok_di_peran'])): ?>
        <p class="dsc-topik" style="color:#4520A6">Cocok di Peran</p>
        <p style="font-size:14px;color:#374151;line-height:1.7;margin:0"><?= html_escape($ai['cocok_di_peran']) ?></p>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- ===== TIPS ===== -->
  <?php $tips = $daftar('tips'); ?>
  <?php if ($tips): ?>
    <div class="dsc-kartu">
      <p class="dsc-judul">&#128161; Tips Pengembangan Diri</p>
      <?php foreach ($tips as $i => $t): ?>
        <div class="dsc-tips">
          <span class="dsc-nomor" style="background:<?= $warna($d['tipe_utama']) ?>"><?= $i + 1 ?></span>
          <span><?= html_escape($t) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- ===== CATATAN HRD ===== -->
  <div class="dsc-kartu dsc-hrd">
    <p class="dsc-judul">Catatan untuk Atasan</p>
    <p style="font-size:14px;color:#374151;line-height:1.7;margin:0">
      <?= html_escape($ai['catatan_hrd'] ?? '') ?: $u['cara_kerja'] ?>
    </p>
    <p class="dsc-nota">Bagian ini untuk manajer, bukan untuk ditunjukkan ke yang bersangkutan.</p>
    <p class="dsc-nota">Kecenderungan kedua: <b><?= $d['tipe_kedua'] ?> &mdash; <?= $k2['nama'] ?></b>, <?= strtolower($k2['ringkas']) ?></p>
    <p class="dsc-nota">DISC menggambarkan gaya perilaku di tempat kerja, bukan mengukur kecerdasan atau kelayakan seseorang. Pakai sebagai bahan diskusi, bukan penilaian akhir.</p>
  </div>

  <p class="dsc-asal">
    <?php if ($sumber === 'ai'): ?>
      Analisis disusun AI <?= html_escape($d['ai_model'] ?: '') ?> barusan.
    <?php elseif ($sumber === 'tersimpan'): ?>
      Analisis AI <?= html_escape($d['ai_model'] ?: '') ?>, disusun <?= $d['ai_at'] ? date('d M Y H:i', strtotime($d['ai_at'])) : '-' ?>.
    <?php else: ?>
      Teks bawaan &mdash; AI tidak terpakai<?= !empty($ai['_alasan']) ? ' (' . html_escape($ai['_alasan']) . ')' : '' ?>.
    <?php endif; ?>
  </p>
</div>

<script>
/* Bar mulai dari nol lalu naik ke nilainya. Dijalankan setelah satu frame
   supaya browser sempat menggambar keadaan awal, kalau tidak transisinya
   dilewati dan bar langsung penuh tanpa animasi. */
(function () {
  var w = document.getElementById('dsc');
  if (!w) return;
  requestAnimationFrame(function () {
    requestAnimationFrame(function () { w.classList.add('dsc-siap'); });
  });
})();
</script>
