<div class="topbar">
  <div>
    <h1>Jawaban: <?= html_escape($f['judul']) ?></h1>
    <p class="sub"><?= count($rows) ?> jawaban masuk.</p>
  </div>
  <div class="aksi">
    <a class="btn" href="<?= base_url('hrd/formulir_builder/' . $f['id']) ?>">Susun pertanyaan</a>
    <a class="btn btn-utama" href="<?= base_url('hrd/export_jawaban/' . $f['id']) ?>">Unduh Excel</a>
  </div>
</div>

<?php if ($rows && !empty($ringkas)): ?>
  <div class="kartu" style="margin-bottom:16px">
    <h2>Nilai rata-rata</h2>
    <p class="label" style="margin-bottom:14px">Dihitung dari <?= count($rows) ?> jawaban. Angka rendah menandakan hal yang perlu dibenahi lebih dulu.</p>
    <?php
    uasort($ringkas, function ($a, $b) { return ($a['rata'] ?? 99) <=> ($b['rata'] ?? 99); });
    foreach ($ringkas as $r):
      if ($r['rata'] === null) continue;
      $rentang = max(1, $r['maks'] - $r['min']);
      $persen  = (($r['rata'] - $r['min']) / $rentang) * 100;
      $warna   = $persen < 40 ? 'var(--merah)' : ($persen < 65 ? 'var(--kuning)' : 'var(--hijau)');
    ?>
      <div style="margin-bottom:13px">
        <div style="display:flex; justify-content:space-between; gap:12px; font-size:.88rem; margin-bottom:4px">
          <span><?= html_escape($r['label']) ?></span>
          <b style="color:<?= $warna ?>; white-space:nowrap"><?= number_format($r['rata'], 1) ?> / <?= $r['maks'] ?></b>
        </div>
        <div class="disc-track"><div class="disc-fill" style="width:<?= max(3, $persen) ?>%; background:<?= $warna ?>"></div></div>
        <div class="label" style="font-size:.75rem; margin-top:3px">terendah <?= $r['terendah'] ?> &middot; tertinggi <?= $r['tertinggi'] ?> &middot; <?= $r['jumlah'] ?> jawaban</div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!$rows): ?>
  <div class="kartu kosong"><b>Belum ada yang mengisi</b>Bagikan tautan <?= base_url('f/' . $f['slug']) ?> ke tim.</div>
<?php else: ?>
<div class="tabel-bungkus">
  <table>
    <thead>
      <tr>
        <th>Waktu</th><th>Nama</th>
        <?php foreach ($fields as $fd): if ($fd['tipe'] === 'judul') continue; ?>
          <th><?= html_escape($fd['label']) ?></th>
        <?php endforeach; ?>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td class="rapat"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
        <td>
          <?php if ($r['karyawan_id']): ?>
            <a href="<?= base_url('hrd/karyawan_detail/' . $r['karyawan_id']) ?>" style="font-weight:600;text-decoration:none"><?= html_escape($r['nama']) ?></a>
          <?php else: ?><b><?= html_escape($r['nama']) ?></b><?php endif; ?>
          <small style="display:block;color:var(--tinta-3)"><?= html_escape($r['email']) ?></small>
        </td>
        <?php foreach ($fields as $fd): if ($fd['tipe'] === 'judul') continue;
          $sel = $isi[$r['id']][$fd['id']] ?? null; ?>
          <td>
            <?php if ($sel && $sel['berkas']): ?>
              <a href="<?= base_url('hrd/berkas/form/' . $sel['berkas']) ?>" target="_blank">Lihat berkas</a>
            <?php else: ?>
              <?= html_escape($sel['nilai'] ?? '-') ?>
            <?php endif; ?>
          </td>
        <?php endforeach; ?>
        <td class="rapat"><a class="btn btn-kecil btn-bahaya" href="<?= base_url('hrd/jawaban_hapus/' . $f['id'] . '/' . $r['id']) ?>" data-konfirmasi="Hapus jawaban ini?">Hapus</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
