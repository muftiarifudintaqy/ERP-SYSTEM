<?php $u = $profil[$d['tipe_utama']]; $k2 = $profil[$d['tipe_kedua']]; ?>
<div class="publik-kepala" style="background:<?= $u['warna'] ?>">
  <div class="garis" style="background:#fff"></div>
  <h1><?= $d['tipe_utama'] ?> &mdash; <?= $u['nama'] ?></h1>
  <p style="color:rgba(255,255,255,.88)"><?= html_escape($d['nama']) ?> &middot; <?= $u['julukan'] ?></p>
</div>

<div class="kartu">
  <p><?= $u['ringkas'] ?></p>
  <p class="label">Kecenderungan kedua kamu: <b><?= $d['tipe_kedua'] ?> &mdash; <?= $k2['nama'] ?></b>.</p>
</div>

<div class="kartu">
  <h2>Grafik kamu</h2>
  <?php foreach (['D','I','S','C'] as $h): $n = (int)$d['net_' . strtolower($h)]; $lebar = min(100, max(4, $n)); ?>
    <div class="disc-bar">
      <span class="huruf" style="background:<?= $profil[$h]['warna'] ?>"><?= $h ?></span>
      <span class="disc-track"><span class="disc-fill" style="width:<?= $lebar ?>%;background:<?= $profil[$h]['warna'] ?>"></span></span>
      <span class="disc-nilai"><?= $n ?>%</span>
    </div>
  <?php endforeach; ?>
</div>

<div class="kartu">
  <h2>Yang jadi kekuatan kamu</h2>
  <p><?= $u['kuat'] ?></p>
  <h2 style="margin-top:16px">Yang perlu kamu jaga</h2>
  <p><?= $u['hati'] ?></p>
</div>

<div class="kartu">
  <p class="label" style="margin:0">Hasil ini sudah otomatis tersimpan dan bisa dilihat HRD. DISC menggambarkan gaya kerja, bukan mengukur kemampuan atau kelayakan seseorang. Kalau ada yang mau didiskusikan, hubungi HRD.</p>
</div>
