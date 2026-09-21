<?php
$tahap_label = [
  'data_diri' => 'Data diri', 'disc' => 'Test DISC',   'karakteristik' => 'Karakteristik', 'aptitude' => 'Test IQ', 'selesai' => 'Selesai',
];
?>
<div class="topbar">
  <div>
    <h1>Hasil Asesmen</h1>
    <p class="sub"><?= count($rows) ?> peserta &middot; tautan pengisian: <code><?= base_url('asesmen') ?></code></p>
  </div>
  <div class="aksi">
    <a class="btn" href="<?= base_url('asesmen') ?>" target="_blank">Buka halaman pengisian</a>
  </div>
</div>

<div class="kartu">
  <form method="get" action="<?= base_url('hrd/asesmen') ?>" style="display:flex;gap:8px;margin-bottom:16px">
    <input type="text" name="q" value="<?= html_escape($q ?: '') ?>" placeholder="Cari nama, email, atau divisi" style="flex:1">
    <button class="btn" type="submit">Cari</button>
    <?php if ($q): ?><a class="btn" href="<?= base_url('hrd/asesmen') ?>">Bersihkan</a><?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <p class="label">Belum ada yang mengisi asesmen.</p>
  <?php else: ?>
  <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>Nama</th><th>Divisi</th><th>Tahap</th>
          <th>STIFIn</th><th>DISC</th>
          <th>Karakter</th><th>Kemampuan</th><th>Diisi</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <b><?= html_escape($r['nama']) ?></b>
              <div class="label"><?= html_escape($r['email'] ?: '-') ?></div>
            </td>
            <td><?= html_escape($r['divisi'] ?: '-') ?></td>
            <td>
              <?php $selesai = ($r['tahap'] === 'selesai'); ?>
              <span style="display:inline-block;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:700;
                background:<?= $selesai ? '#E7F6EE' : '#FFF4E6' ?>;color:<?= $selesai ? '#15803D' : '#B45309' ?>">
                <?= html_escape($tahap_label[$r['tahap']] ?? $r['tahap']) ?>
              </span>
            </td>
            <td>
              <?php if ($r['stifin_singkat']): ?>
                <b><?= html_escape($r['stifin_singkat']) ?></b>
                <div class="label"><?= html_escape($r['stifin_nama']) ?></div>
              <?php else: ?>-<?php endif; ?>
            </td>
            <td><?= $r['disc_tipe'] ? '<b>' . html_escape($r['disc_tipe']) . '</b>' : '-' ?></td>
            <td>
              <?php if ($r['kar_persen'] !== NULL): ?>
                <b><?= (int)$r['kar_persen'] ?>%</b>
                <div class="label"><?= html_escape($r['kar_kategori']) ?></div>
              <?php else: ?>-<?php endif; ?>
            </td>
            <td>
              <?php if ($r['apt_benar'] !== NULL): ?>
                <b><?= (int)$r['apt_benar'] ?>/<?= (int)$r['apt_total'] ?></b>
                <div class="label">est. <?= (int)$r['apt_iq'] ?></div>
              <?php else: ?>-<?php endif; ?>
            </td>
            <td class="label"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
            <td><a class="btn" href="<?= base_url('hrd/asesmen_detail/' . $r['id']) ?>">Lihat</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <p class="label" style="margin-top:18px">
    Kolom STIFIn diturunkan dari tanggal lahir, bukan dari jawaban tes. Peserta tidak melihat kolom ini.
    Angka pada Test IQ adalah estimasi internal, bukan hasil tes psikometri resmi.
  </p>
</div>
