<div class="topbar">
  <div>
    <h1>Inventaris kantor</h1>
    <p class="sub"><?= count($rows) ?> barang tercatat. Kartu yang mendekati masa habis ditandai kuning.</p>
  </div>
  <div class="aksi">
    <a class="btn" href="<?= base_url('hrd/export_inventaris?' . http_build_query($f)) ?>">Unduh Excel</a>
    <a class="btn btn-utama" href="<?= base_url('hrd/inventaris_form') ?>">Tambah barang</a>
  </div>
</div>

<form class="filter" method="get" action="<?= base_url('hrd/inventaris') ?>">
  <input type="text" name="q" value="<?= html_escape($f['q']) ?>" placeholder="Cari pemegang, merek, serial number">
  <select name="kondisi">
    <option value="">Semua kondisi</option>
    <?php foreach (['Baik','Rusak Ringan','Rusak Berat'] as $o): ?>
      <option <?= $f['kondisi'] === $o ? 'selected' : '' ?>><?= $o ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn" type="submit">Cari</button>
  <?php if ($f['q'] || $f['kondisi']): ?><a class="btn" href="<?= base_url('hrd/inventaris') ?>">Reset</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
  <div class="kartu kosong">
    <b>Belum ada barang tercatat</b>
    Bagikan tautan <a href="<?= base_url('f/inventaris') ?>" target="_blank">form inventaris</a> ke tim, atau tambah manual.
  </div>
<?php else: ?>
<div class="tabel-bungkus">
  <table>
    <thead>
      <tr><th>Pemegang</th><th>Barang</th><th>Serial / Tipe</th><th>No. HP kantor</th><th>Masa aktif kartu</th><th>Kondisi</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $b):
      $habis = $b['masa_aktif_kartu'] && strtotime($b['masa_aktif_kartu']) <= strtotime('+30 days');
    ?>
      <tr>
        <td>
          <?php if ($b['karyawan_id']): ?>
            <a href="<?= base_url('hrd/karyawan_detail/' . $b['karyawan_id']) ?>" style="font-weight:600;text-decoration:none"><?= html_escape($b['nama_pemegang']) ?></a>
          <?php else: ?>
            <b><?= html_escape($b['nama_pemegang']) ?></b>
          <?php endif; ?>
          <small style="display:block;color:var(--tinta-3)"><?= html_escape($b['jabatan'] ?: '-') ?></small>
        </td>
        <td><?= html_escape($b['jenis_barang']) ?><small style="display:block;color:var(--tinta-3)"><?= html_escape($b['merek']) ?></small></td>
        <td class="sensitif" style="font-size:.82rem"><?= html_escape($b['serial_number'] ?: $b['tipe_hp'] ?: '-') ?></td>
        <td class="rapat"><?= html_escape($b['no_hp_kantor'] ?: '-') ?><?= $b['nama_wa_kantor'] ? '<small style="display:block;color:var(--tinta-3)">' . html_escape($b['nama_wa_kantor']) . '</small>' : '' ?></td>
        <td class="rapat"><?= $b['masa_aktif_kartu'] ? '<span class="tag ' . ($habis ? 'tag-kuning' : 'tag-abu') . '">' . date('d M Y', strtotime($b['masa_aktif_kartu'])) . '</span>' : '-' ?></td>
        <td><span class="tag <?= $b['kondisi'] === 'Baik' ? 'tag-hijau' : ($b['kondisi'] === 'Rusak Ringan' ? 'tag-kuning' : 'tag-merah') ?>"><?= $b['kondisi'] ?></span></td>
        <td class="rapat">
          <div class="aksi">
            <a class="btn btn-kecil" href="<?= base_url('hrd/inventaris_form/' . $b['id']) ?>">Ubah</a>
            <a class="btn btn-kecil btn-bahaya" href="<?= base_url('hrd/inventaris_hapus/' . $b['id']) ?>" data-konfirmasi="Hapus barang ini dari daftar?">Hapus</a>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
