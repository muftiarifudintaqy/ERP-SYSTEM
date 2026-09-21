<?php
$lama = isset($lama) && is_array($lama) ? $lama : [];
$isi = function ($k) use ($lama) { return html_escape($lama[$k] ?? ''); };
?>
<link rel="stylesheet" href="<?= base_url('assets/css/asesmen.css') ?>">

<div class="as">
  <div class="as-kartu as-mulai">
    <p class="as-label">Asesmen Karyawan</p>
    <h1>PT Montera Strategic Group</h1>
    <p class="as-sub">Tiga test, sekitar 30&ndash;40 menit. Pada DISC dan Karakteristik tidak ada jawaban yang salah &mdash; jawab sesuai diri Anda yang sebenarnya.</p>

    <?php if ($p = $this->session->flashdata('gagal')): ?>
      <div class="as-alert as-alert-merah"><?= html_escape($p) ?></div>
    <?php endif; ?>
    <?php if ($p = $this->session->flashdata('pesan')): ?>
      <div class="as-alert as-alert-biru"><?= html_escape($p) ?></div>
    <?php endif; ?>

    <ol class="as-tahap">
      <li><b>1</b> Test DISC <span>40 soal</span></li>
      <li><b>2</b> Test Karakteristik <span>30 soal</span></li>
      <li><b>3</b> Test IQ <span>30 soal</span></li>
    </ol>

    <form method="post" action="<?= base_url('asesmen/mulai') ?>" class="as-form">
      <label>
        Nama lengkap
        <input type="text" name="nama" value="<?= $isi('nama') ?>" required autocomplete="name">
      </label>

      <label>
        Divisi / jabatan
        <input type="text" name="divisi" value="<?= $isi('divisi') ?>" placeholder="mis. Marketing" autocomplete="organization-title">
      </label>

      <label>
        Email
        <input type="email" name="email" value="<?= $isi('email') ?>" required autocomplete="email">
        <small>Dipakai untuk mengenali hasil Anda. Satu email satu kali pengisian.</small>
      </label>

      <label>
        Tanggal lahir
        <input type="date" name="tanggal_lahir" value="<?= $isi('tanggal_lahir') ?>" required max="<?= date('Y-m-d') ?>">
      </label>

      <label>Golongan darah
        <select name="gol_darah" required>
          <option value="">Pilih golongan darah</option>
          <?php foreach (['A','B','AB','O','Tidak tahu'] as $g): ?>
            <option value="<?= $g ?>" <?= $isi('gol_darah') === $g ? 'selected' : '' ?>><?= $g ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <button type="submit" class="as-btn as-btn-utama">Mulai asesmen</button>
    </form>

    <p class="as-nota">Hasil asesmen dipakai untuk pengembangan karyawan dan penempatan peran, bukan sebagai penilaian kelayakan seseorang.</p>
  </div>
</div>
