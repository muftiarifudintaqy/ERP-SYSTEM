<?php $l = function ($f, $d = '') use ($lama) { return html_escape($lama[$f] ?? $d); }; $lama = $lama ?? []; ?>
<div class="publik-kepala">
  <div class="garis"></div>
  <h1>Informasi Data Karyawan</h1>
  <p>Isi data di bawah ini dengan benar. Semua informasi bersifat rahasia dan hanya dipakai untuk keperluan administrasi dan pembaruan database karyawan PT Montera Strategic Group. Ada kendala? Hubungi HRD.</p>
</div>

<form method="post" enctype="multipart/form-data">
  <div class="kartu">
    <h2>Identitas</h2>
    <div class="isian"><label>Nama lengkap <span class="wajib">*</span></label><input type="text" name="nama" value="<?= $l('nama') ?>" required></div>
    <div class="grid g2">
      <div class="isian"><label>Jabatan / Divisi <span class="wajib">*</span></label><input type="text" name="jabatan" value="<?= $l('jabatan') ?>" required></div>
      <div class="isian"><label>Tanggal masuk kerja <span class="wajib">*</span></label><input type="date" name="tanggal_masuk" value="<?= $l('tanggal_masuk') ?>" required></div>
      <div class="isian"><label>NIK (sesuai KTP) <span class="wajib">*</span></label><input type="text" name="nik" value="<?= $l('nik') ?>" inputmode="numeric" required></div>
      <div class="isian"><label>No. NPWP</label><input type="text" name="npwp" value="<?= $l('npwp') ?>" inputmode="numeric"></div>
      <div class="isian"><label>Tempat lahir <span class="wajib">*</span></label><input type="text" name="tempat_lahir" value="<?= $l('tempat_lahir') ?>" required></div>
      <div class="isian"><label>Tanggal lahir <span class="wajib">*</span></label><input type="date" name="tanggal_lahir" value="<?= $l('tanggal_lahir') ?>" required></div>
    </div>
    <div class="isian"><label>Alamat sesuai KTP <span class="wajib">*</span></label><textarea name="alamat_ktp" required><?= $l('alamat_ktp') ?></textarea></div>
    <div class="isian"><label>Alamat domisili saat ini <span class="wajib">*</span></label><textarea name="alamat_domisili" required><?= $l('alamat_domisili') ?></textarea></div>
    <div class="grid g2">
      <div class="isian">
        <label>Jenis kelamin <span class="wajib">*</span></label>
        <div class="pilihan">
          <?php foreach (['Laki-Laki','Perempuan'] as $o): ?>
            <label><input type="radio" name="jenis_kelamin" value="<?= $o ?>" <?= ($lama['jenis_kelamin'] ?? '') === $o ? 'checked' : '' ?> required> <?= $o ?></label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="isian"><label>Agama <span class="wajib">*</span></label>
        <select name="agama" required>
          <option value="">- pilih -</option>
          <?php foreach (['Islam','Kristen Protestan','Katolik','Hindu','Buddha','Konghucu','Lainnya'] as $o): ?>
            <option <?= ($lama['agama'] ?? '') === $o ? 'selected' : '' ?>><?= $o ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="isian"><label>Pendidikan terakhir <span class="wajib">*</span></label><input type="text" name="pendidikan_terakhir" value="<?= $l('pendidikan_terakhir') ?>" placeholder="SMA / D3 / S1 Manajemen" required></div>
      <div class="isian"><label>Status <span class="wajib">*</span></label>
        <select name="status_pernikahan" required>
          <option value="">- pilih -</option>
          <?php foreach (['Belum Menikah','Menikah','Cerai Hidup','Cerai Mati'] as $o): ?>
            <option <?= ($lama['status_pernikahan'] ?? '') === $o ? 'selected' : '' ?>><?= $o ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="isian"><label>Jumlah anak</label><input type="number" name="jumlah_anak" min="0" value="<?= $l('jumlah_anak', '0') ?>"></div>
    </div>
  </div>

  <div class="kartu">
    <h2>Kontak</h2>
    <div class="grid g2">
      <div class="isian"><label>No. HP <span class="wajib">*</span></label><input type="tel" name="no_hp" value="<?= $l('no_hp') ?>" required></div>
      <div class="isian"><label>Email <span class="wajib">*</span></label><input type="email" name="email" value="<?= $l('email') ?>" required></div>
      <div class="isian"><label>Nama kontak darurat <span class="wajib">*</span></label><input type="text" name="nama_kontak_darurat" value="<?= $l('nama_kontak_darurat') ?>" required></div>
      <div class="isian"><label>Hubungan dengan kontak darurat <span class="wajib">*</span></label><input type="text" name="hubungan_kontak_darurat" value="<?= $l('hubungan_kontak_darurat') ?>" placeholder="Orang tua / pasangan / saudara" required></div>
      <div class="isian"><label>No. telp kontak darurat <span class="wajib">*</span></label><input type="tel" name="telp_kontak_darurat" value="<?= $l('telp_kontak_darurat') ?>" required></div>
    </div>
  </div>

  <div class="kartu">
    <h2>Rekening &amp; KTP</h2>
    <div class="grid g3">
      <div class="isian"><label>Nama bank <span class="wajib">*</span></label><input type="text" name="nama_bank" value="<?= $l('nama_bank') ?>" required></div>
      <div class="isian"><label>Nama terdaftar di bank <span class="wajib">*</span></label><input type="text" name="nama_rekening" value="<?= $l('nama_rekening') ?>" required></div>
      <div class="isian"><label>No. rekening <span class="wajib">*</span></label><input type="text" name="no_rekening" value="<?= $l('no_rekening') ?>" inputmode="numeric" required></div>
    </div>
    <div class="isian">
      <label>Foto KTP <span class="wajib">*</span></label>
      <p class="bantu">Foto langsung dari HP juga bisa. Maksimal 10 MB, format JPG, PNG, atau PDF.</p>
      <input type="file" name="foto_ktp" accept="image/*,.pdf" capture="environment" required>
    </div>
  </div>

  <button class="btn btn-utama" type="submit" style="width:100%; justify-content:center; padding:13px">Kirim data</button>
</form>
