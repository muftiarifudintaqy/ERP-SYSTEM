<?php $v = function ($f, $d = '') use ($b) { return html_escape($b[$f] ?? $d); }; ?>
<div class="topbar">
  <div><h1><?= $judul ?></h1><p class="sub">Satu baris untuk satu barang. Kalau satu orang pegang laptop dan HP, buat dua baris.</p></div>
  <a class="btn" href="<?= base_url('hrd/inventaris') ?>">Batal</a>
</div>

<form method="post" action="<?= base_url('hrd/inventaris_simpan') ?>" enctype="multipart/form-data">
  <input type="hidden" name="id" value="<?= $v('id') ?>">
  <fieldset>
    <legend>Pemegang</legend>
    <div class="grid g2">
      <div class="isian">
        <label>Pilih dari data karyawan</label>
        <select name="karyawan_id" id="pilih-karyawan">
          <option value="">- isi manual di bawah -</option>
          <?php foreach ($karyawan as $k): ?>
            <option value="<?= $k['id'] ?>" data-nama="<?= html_escape($k['nama']) ?>" data-jabatan="<?= html_escape($k['jabatan']) ?>"
              <?= ($b['karyawan_id'] ?? null) == $k['id'] ? 'selected' : '' ?>><?= html_escape($k['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="isian"><label>Nama pemegang <span class="wajib">*</span></label><input type="text" name="nama_pemegang" id="nama-pemegang" value="<?= $v('nama_pemegang') ?>" required></div>
      <div class="isian"><label>Jabatan</label><input type="text" name="jabatan" id="jabatan-pemegang" value="<?= $v('jabatan') ?>"></div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Barang</legend>
    <div class="grid g2">
      <div class="isian"><label>Jenis barang <span class="wajib">*</span></label><input type="text" name="jenis_barang" value="<?= $v('jenis_barang') ?>" placeholder="Laptop, HP, Printer" required></div>
      <div class="isian"><label>Merek</label><input type="text" name="merek" value="<?= $v('merek') ?>"></div>
      <div class="isian"><label>Serial number (PC/Laptop)</label><input type="text" name="serial_number" value="<?= $v('serial_number') ?>"></div>
      <div class="isian"><label>Tipe HP</label><input type="text" name="tipe_hp" value="<?= $v('tipe_hp') ?>"></div>
      <div class="isian"><label>Kondisi</label>
        <select name="kondisi">
          <?php foreach (['Baik','Rusak Ringan','Rusak Berat'] as $o): ?>
            <option <?= ($b['kondisi'] ?? 'Baik') === $o ? 'selected' : '' ?>><?= $o ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="isian"><label>Foto barang</label><input type="file" name="foto_barang" accept="image/*"></div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Kartu &amp; nomor kantor</legend>
    <div class="grid g3">
      <div class="isian"><label>No. HP kantor</label><input type="tel" name="no_hp_kantor" value="<?= $v('no_hp_kantor') ?>"></div>
      <div class="isian"><label>Nama WA kantor</label><input type="text" name="nama_wa_kantor" value="<?= $v('nama_wa_kantor') ?>"></div>
      <div class="isian"><label>Masa aktif kartu</label><input type="date" name="masa_aktif_kartu" value="<?= $v('masa_aktif_kartu') ?>"></div>
    </div>
    <div class="isian"><label>Catatan</label><textarea name="catatan"><?= $v('catatan') ?></textarea></div>
  </fieldset>

  <div class="aksi">
    <button class="btn btn-utama" type="submit">Simpan barang</button>
    <a class="btn" href="<?= base_url('hrd/inventaris') ?>">Batal</a>
  </div>
</form>

<script>
document.getElementById('pilih-karyawan').addEventListener('change', function () {
  var o = this.options[this.selectedIndex];
  if (!o.value) return;
  document.getElementById('nama-pemegang').value = o.dataset.nama || '';
  document.getElementById('jabatan-pemegang').value = o.dataset.jabatan || '';
});
</script>
