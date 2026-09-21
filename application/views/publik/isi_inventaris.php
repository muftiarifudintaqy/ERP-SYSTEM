<?php $lama = $lama ?? []; $l = function ($f, $d = '') use ($lama) { return html_escape($lama[$f] ?? $d); }; ?>
<div class="publik-kepala">
  <div class="garis"></div>
  <h1>Pendataan Inventaris Kantor</h1>
  <p>Catat aset dan peralatan kantor yang saat ini kamu bawa atau gunakan. Kalau kamu memegang lebih dari satu barang, kirim form ini sekali untuk tiap barang. Ada pertanyaan? Hubungi HRD.</p>
</div>

<form method="post" enctype="multipart/form-data">
  <div class="kartu">
    <h2>Pemegang</h2>
    <div class="grid g2">
      <div class="isian"><label>Nama lengkap <span class="wajib">*</span></label><input type="text" name="nama_pemegang" value="<?= $l('nama_pemegang') ?>" required></div>
      <div class="isian"><label>Jabatan / posisi <span class="wajib">*</span></label><input type="text" name="jabatan" value="<?= $l('jabatan') ?>" required></div>
    </div>
  </div>

  <div class="kartu">
    <h2>Barang</h2>
    <div class="grid g2">
      <div class="isian"><label>Jenis barang <span class="wajib">*</span></label><input type="text" name="jenis_barang" value="<?= $l('jenis_barang') ?>" placeholder="Laptop, HP, printer" required></div>
      <div class="isian"><label>Merek <span class="wajib">*</span></label><input type="text" name="merek" value="<?= $l('merek') ?>" required></div>
      <div class="isian"><label>Serial number (PC/laptop)</label><input type="text" name="serial_number" value="<?= $l('serial_number') ?>"><p class="bantu">Kosongkan kalau barangnya bukan PC atau laptop.</p></div>
      <div class="isian"><label>Tipe HP</label><input type="text" name="tipe_hp" value="<?= $l('tipe_hp') ?>"></div>
    </div>
    <div class="isian">
      <label>Kondisi barang <span class="wajib">*</span></label>
      <div class="pilihan" style="flex-direction:column; gap:8px">
        <?php foreach (['Baik' => 'Baik / berfungsi normal', 'Rusak Ringan' => 'Rusak ringan (masih bisa dipakai)', 'Rusak Berat' => 'Rusak berat (perlu perbaikan atau penggantian)'] as $v => $t): ?>
          <label><input type="radio" name="kondisi" value="<?= $v ?>" <?= ($lama['kondisi'] ?? '') === $v ? 'checked' : '' ?> required> <?= $t ?></label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="isian"><label>Foto barang (opsional)</label><input type="file" name="foto_barang" accept="image/*" capture="environment"></div>
  </div>

  <div class="kartu">
    <h2>Kalau kamu pegang HP kantor</h2>
    <div class="grid g3">
      <div class="isian"><label>Nomor HP kantor</label><input type="tel" name="no_hp_kantor" value="<?= $l('no_hp_kantor') ?>"></div>
      <div class="isian"><label>Nama WA kantor</label><input type="text" name="nama_wa_kantor" value="<?= $l('nama_wa_kantor') ?>"></div>
      <div class="isian"><label>Masa aktif kartu</label><input type="date" name="masa_aktif_kartu" value="<?= $l('masa_aktif_kartu') ?>"></div>
    </div>
    <div class="isian"><label>Catatan tambahan</label><textarea name="catatan"><?= $l('catatan') ?></textarea></div>
  </div>

  <button class="btn btn-utama" type="submit" style="width:100%; justify-content:center; padding:13px">Kirim data barang</button>
</form>
