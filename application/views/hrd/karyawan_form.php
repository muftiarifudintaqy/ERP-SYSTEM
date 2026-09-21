<?php $v = function ($f, $d = '') use ($k) { return html_escape($k[$f] ?? $d); }; ?>
<div class="topbar">
  <div>
    <h1><?= $judul ?></h1>
    <p class="sub">Isian yang bertanda <span class="wajib">*</span> dipakai untuk administrasi dan penggajian.</p>
  </div>
  <a class="btn" href="<?= base_url('hrd/karyawan') ?>">Batal</a>
</div>

<form method="post" action="<?= base_url('hrd/karyawan_simpan') ?>" enctype="multipart/form-data">
  <input type="hidden" name="id" value="<?= $v('id') ?>">

  <fieldset>
    <legend>Identitas</legend>
    <div class="grid g2">
      <div class="isian"><label>Nama lengkap <span class="wajib">*</span></label><input type="text" name="nama" value="<?= $v('nama') ?>" required></div>
      <div class="isian"><label>NIK (KTP)</label><input type="text" name="nik" value="<?= $v('nik') ?>" inputmode="numeric"></div>
      <div class="isian"><label>Tempat lahir</label><input type="text" name="tempat_lahir" value="<?= $v('tempat_lahir') ?>"></div>
      <div class="isian"><label>Tanggal lahir</label><input type="date" name="tanggal_lahir" value="<?= $v('tanggal_lahir') ?>"></div>
      <div class="isian"><label>Jenis kelamin</label>
        <select name="jenis_kelamin">
          <option value="">- pilih -</option>
          <?php foreach (['Laki-Laki','Perempuan'] as $o): ?>
            <option <?= ($k['jenis_kelamin'] ?? '') === $o ? 'selected' : '' ?>><?= $o ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="isian"><label>Agama</label><input type="text" name="agama" value="<?= $v('agama') ?>"></div>
      <div class="isian"><label>Status pernikahan</label>
        <select name="status_pernikahan">
          <option value="">- pilih -</option>
          <?php foreach (['Belum Menikah','Menikah','Cerai Hidup','Cerai Mati'] as $o): ?>
            <option <?= ($k['status_pernikahan'] ?? '') === $o ? 'selected' : '' ?>><?= $o ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="isian"><label>Jumlah anak</label><input type="number" name="jumlah_anak" min="0" value="<?= $v('jumlah_anak', 0) ?>"></div>
      <div class="isian"><label>Pendidikan terakhir</label><input type="text" name="pendidikan_terakhir" value="<?= $v('pendidikan_terakhir') ?>"></div>
      <div class="isian"><label>NPWP</label><input type="text" name="npwp" value="<?= $v('npwp') ?>"></div>
    </div>
    <div class="isian"><label>Alamat sesuai KTP</label><textarea name="alamat_ktp"><?= $v('alamat_ktp') ?></textarea></div>
    <div class="isian"><label>Alamat domisili</label><textarea name="alamat_domisili"><?= $v('alamat_domisili') ?></textarea></div>
  </fieldset>

  <fieldset>
    <legend>Kepegawaian</legend>
    <div class="grid g2">
      <div class="isian"><label>Jabatan</label><input type="text" name="jabatan" value="<?= $v('jabatan') ?>"></div>
      <div class="isian"><label>Divisi</label><input type="text" name="divisi" value="<?= $v('divisi') ?>"></div>
      <div class="isian"><label>Tanggal masuk kerja</label><input type="date" name="tanggal_masuk" value="<?= $v('tanggal_masuk') ?>"></div>
      <div class="isian"><label>Status karyawan</label>
        <select name="status_karyawan">
          <?php foreach (['Aktif','Resign','Cuti Panjang'] as $o): ?>
            <option <?= ($k['status_karyawan'] ?? 'Aktif') === $o ? 'selected' : '' ?>><?= $o ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Kontak</legend>
    <div class="grid g2">
      <div class="isian"><label>No. HP</label><input type="tel" name="no_hp" value="<?= $v('no_hp') ?>"></div>
      <div class="isian"><label>Email</label><input type="email" name="email" value="<?= $v('email') ?>"></div>
      <div class="isian"><label>Nama kontak darurat</label><input type="text" name="nama_kontak_darurat" value="<?= $v('nama_kontak_darurat') ?>"></div>
      <div class="isian"><label>Hubungan</label><input type="text" name="hubungan_kontak_darurat" value="<?= $v('hubungan_kontak_darurat') ?>"></div>
      <div class="isian"><label>No. telp kontak darurat</label><input type="tel" name="telp_kontak_darurat" value="<?= $v('telp_kontak_darurat') ?>"></div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Rekening &amp; berkas</legend>
    <div class="grid g3">
      <div class="isian"><label>Nama bank</label><input type="text" name="nama_bank" value="<?= $v('nama_bank') ?>"></div>
      <div class="isian"><label>Nama terdaftar di bank</label><input type="text" name="nama_rekening" value="<?= $v('nama_rekening') ?>"></div>
      <div class="isian"><label>No. rekening</label><input type="text" name="no_rekening" value="<?= $v('no_rekening') ?>" inputmode="numeric"></div>
    </div>
    <div class="isian">
      <label>Foto KTP</label>
      <?php if (!empty($k['foto_ktp'])): ?>
        <p class="bantu">Sudah ada berkas. Unggah baru hanya jika ingin mengganti.</p>
      <?php endif; ?>
      <input type="file" name="foto_ktp" accept="image/*,.pdf">
    </div>
    <div class="isian"><label>Catatan HRD (tidak terlihat karyawan)</label><textarea name="catatan_hrd"><?= $v('catatan_hrd') ?></textarea></div>
  </fieldset>

  <div class="aksi">
    <button class="btn btn-utama" type="submit">Simpan data</button>
    <a class="btn" href="<?= base_url('hrd/karyawan') ?>">Batal</a>
  </div>
</form>
