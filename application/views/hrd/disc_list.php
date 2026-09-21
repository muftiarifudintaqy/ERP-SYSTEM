<div class="topbar">
  <div>
    <h1>Hasil DISC</h1>
    <p class="sub"><?= count($rows) ?> orang sudah mengisi<?= count($belum) ? ', ' . count($belum) . ' belum' : '' ?>. Tautan tes: <a href="<?= base_url('f/disc') ?>" target="_blank"><?= base_url('f/disc') ?></a></p>
  </div>
  <div class="aksi">
    <button class="btn" type="button" onclick="navigator.clipboard.writeText('<?= base_url('f/disc') ?>');this.textContent='Tersalin'">Salin tautan tes</button>
    <a class="btn" href="<?= base_url('hrd/disc_soal') ?>">Susun soal</a>
    <a class="btn btn-utama" href="<?= base_url('hrd/export_disc') ?>">Unduh Excel</a>
  </div>
</div>

<div class="grid g4">
  <?php
  $hitung = ['D' => 0, 'I' => 0, 'S' => 0, 'C' => 0];
  foreach ($rows as $r) if (isset($hitung[$r['tipe_utama']])) $hitung[$r['tipe_utama']]++;
  foreach (['D','I','S','C'] as $h): ?>
    <div class="kartu">
      <div class="angka" style="color:<?= $profil[$h]['warna'] ?>"><?= $hitung[$h] ?></div>
      <div class="label"><?= $h ?> &mdash; <?= $profil[$h]['nama'] ?><br><span style="color:var(--tinta-2)"><?= $profil[$h]['julukan'] ?></span></div>
    </div>
  <?php endforeach; ?>
</div>

<form class="filter" method="get" action="<?= base_url('hrd/disc') ?>" style="margin-top:18px">
  <input type="text" name="q" value="<?= html_escape($f['q']) ?>" placeholder="Cari nama atau email">
  <select name="tipe">
    <option value="">Semua tipe</option>
    <?php foreach (['D','I','S','C'] as $h): ?>
      <option value="<?= $h ?>" <?= $f['tipe'] === $h ? 'selected' : '' ?>><?= $h ?> - <?= $profil[$h]['nama'] ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn" type="submit">Cari</button>
</form>

<?php if (!$rows): ?>
  <div class="kartu kosong"><b>Belum ada hasil</b>
    Susun dulu kelompok katanya di <a href="<?= base_url('hrd/disc_soal') ?>">Susun DISC test</a>, buka statusnya, lalu bagikan tautannya ke tim.
  </div>
<?php else: ?>
<div class="tabel-bungkus">
  <table>
    <thead><tr><th>Nama</th><th>Jabatan</th><th>Tipe</th><th>D</th><th>I</th><th>S</th><th>C</th><th>Waktu isi</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td>
          <div class="baris-nama">
            <span class="avatar" style="background:<?= $profil[$r['tipe_utama']]['warna'] ?>;color:#fff"><?= $r['tipe_utama'] ?></span>
            <span><a href="<?= base_url('hrd/disc_detail/' . $r['id']) ?>" style="font-weight:600;text-decoration:none"><?= html_escape($r['nama']) ?></a>
            <small><?= html_escape($r['email']) ?></small></span>
          </div>
        </td>
        <td><?= html_escape($r['jabatan'] ?: '-') ?></td>
        <td><b><?= $r['tipe_utama'] ?></b><span style="color:var(--tinta-3)">/<?= $r['tipe_kedua'] ?></span> <small style="color:var(--tinta-3)"><?= $profil[$r['tipe_utama']]['julukan'] ?></small></td>
        <?php foreach (['d','i','s','c'] as $x): ?>
          <td><?= $r['net_' . $x] ?>%</td>
        <?php endforeach; ?>
        <td class="rapat"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
        <td class="rapat"><a class="btn btn-kecil" href="<?= base_url('hrd/disc_detail/' . $r['id']) ?>">Detail</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php if ($belum): ?>
<div class="kartu" style="margin-top:16px">
  <h2>Belum mengisi (<?= count($belum) ?>)</h2>
  <p class="label"><?php
    echo html_escape(implode(', ', array_column($belum, 'nama')));
  ?></p>
</div>
<?php endif; ?>
