<div class="topbar">
  <div>
    <h1><?= html_escape($f['judul']) ?></h1>
    <p class="sub">Tautan pengisian: <a href="<?= base_url('f/' . $f['slug']) ?>" target="_blank"><?= base_url('f/' . $f['slug']) ?></a></p>
  </div>
  <div class="aksi">
    <a class="btn" href="<?= base_url('hrd/jawaban/' . $f['id']) ?>">Lihat jawaban</a>
    <a class="btn" href="<?= base_url('f/' . $f['slug']) ?>" target="_blank">Pratinjau</a>
  </div>
</div>

<div class="grid g2" style="align-items:start">
  <div>
    <div class="kartu">
      <h2>Pertanyaan (<?= count($fields) ?>)</h2>
      <?php if (!$fields): ?>
        <div class="kosong"><b>Belum ada pertanyaan</b>Tambahkan lewat panel di sebelah kanan.</div>
      <?php else: ?>
        <table>
          <tbody>
          <?php foreach ($fields as $fd): ?>
            <tr>
              <td style="width:44px;color:var(--tinta-3)"><?= $fd['urutan'] ?></td>
              <td>
                <b><?= html_escape($fd['label']) ?></b> <?= $fd['wajib'] ? '<span class="wajib">*</span>' : '' ?>
                <small style="display:block;color:var(--tinta-3)">
                  <?php
                  $jml_label = count(array_filter(array_map('trim', explode("\n", (string)$fd['opsi']))));
                  if ($fd['tipe'] === 'judul') {
                      echo 'Judul bagian';
                  } elseif ($fd['tipe'] === 'skala') {
                      echo 'Skala ' . (int)$fd['skala_min'] . '-' . (int)$fd['skala_maks'];
                      if ($jml_label) {
                          echo ' &middot; label di tiap angka';
                      } elseif ($fd['label_rendah']) {
                          echo ' (' . html_escape($fd['label_rendah']) . ' &rarr; ' . html_escape($fd['label_tinggi']) . ')';
                      }
                  } else {
                      echo html_escape($fd['tipe']);
                      if ($jml_label) echo ' &middot; ' . $jml_label . ' opsi';
                  }
                  ?>
                </small>
              </td>
              <td class="rapat">
                <a class="btn btn-kecil btn-bahaya" href="<?= base_url('hrd/field_hapus/' . $f['id'] . '/' . $fd['id']) ?>" data-konfirmasi="Hapus pertanyaan ini?">Hapus</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="kartu">
      <h2>Pengaturan formulir</h2>
      <form method="post" action="<?= base_url('hrd/formulir_simpan') ?>">
        <input type="hidden" name="id" value="<?= $f['id'] ?>">
        <div class="isian"><label>Judul</label><input type="text" name="judul" value="<?= html_escape($f['judul']) ?>" required></div>
        <div class="isian"><label>Alamat tautan</label><input type="text" name="slug" value="<?= html_escape($f['slug']) ?>"></div>
        <div class="isian"><label>Keterangan</label><textarea name="deskripsi"><?= html_escape($f['deskripsi']) ?></textarea></div>
        <div class="grid g2">
          <div class="isian"><label>Status</label>
            <select name="status">
              <?php foreach (['draft' => 'Draft (belum bisa diisi)', 'terbuka' => 'Terbuka', 'ditutup' => 'Ditutup'] as $k => $t): ?>
                <option value="<?= $k ?>" <?= $f['status'] === $k ? 'selected' : '' ?>><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="isian"><label>Pesan setelah kirim</label><input type="text" name="pesan_selesai" value="<?= html_escape($f['pesan_selesai']) ?>"></div>
        </div>
        <div class="isian"><label class="pilihan" style="font-weight:400"><input type="checkbox" name="satu_kali" <?= $f['satu_kali'] ? 'checked' : '' ?>> Satu email hanya boleh mengisi sekali</label></div>
        <button class="btn btn-utama" type="submit">Simpan pengaturan</button>
      </form>
    </div>
  </div>

  <div class="kartu">
    <h2>Tambah pertanyaan</h2>
    <form method="post" action="<?= base_url('hrd/field_simpan') ?>">
      <input type="hidden" name="form_id" value="<?= $f['id'] ?>">
      <div class="isian"><label>Pertanyaan</label><input type="text" name="label" required placeholder="Contoh: Berapa lama kamu bekerja di sini?"></div>
      <div class="isian">
        <label>Jenis jawaban</label>
        <select name="tipe" id="tipe-field">
          <option value="text">Isian singkat</option>
          <option value="textarea">Isian panjang</option>
          <option value="email">Email</option>
          <option value="tel">Nomor telepon</option>
          <option value="number">Angka</option>
          <option value="date">Tanggal</option>
          <option value="select">Pilih satu (dropdown)</option>
          <option value="radio">Pilih satu (tombol)</option>
          <option value="checkbox">Pilih banyak</option>
          <option value="skala">Skala penilaian (angka, misal 1-10)</option>
          <option value="file">Unggah berkas</option>
          <option value="judul">Judul bagian (tanpa jawaban)</option>
        </select>
      </div>
      <div id="kotak-skala" style="display:none">
        <div class="grid g2">
          <div class="isian"><label>Nilai terendah</label><input type="number" name="skala_min" value="1" min="0" max="9"></div>
          <div class="isian"><label>Nilai tertinggi</label><input type="number" name="skala_maks" value="10" min="2" max="10"></div>
        </div>
        <div class="grid g2">
          <div class="isian"><label>Arti nilai terendah</label><input type="text" name="label_rendah" placeholder="Sangat tidak setuju"></div>
          <div class="isian"><label>Arti nilai tertinggi</label><input type="text" name="label_tinggi" placeholder="Sangat setuju"></div>
        </div>
        <div class="isian">
          <label>Label tiap angka (opsional)</label>
          <p class="bantu">Kosongkan kalau cukup angka saja. Kalau diisi, tulis satu label per baris
            dan jumlahnya harus sama persis dengan banyaknya angka &mdash; misal untuk skala 1-5 tulis 5 baris.</p>
          <textarea name="opsi" rows="4" placeholder="Sangat tidak setuju&#10;Tidak setuju&#10;Biasa saja&#10;Setuju&#10;Sangat setuju"></textarea>
        </div>
      </div>

      <div class="isian" id="kotak-opsi" style="display:none">
        <label>Daftar pilihan</label>
        <p class="bantu">Satu pilihan per baris.</p>
        <textarea name="opsi" placeholder="Sangat setuju&#10;Setuju&#10;Tidak setuju"></textarea>
      </div>
      <div class="isian"><label>Teks bantuan (opsional)</label><input type="text" name="bantuan"></div>
      <div class="grid g2">
        <div class="isian"><label>Urutan</label><input type="number" name="urutan" value="<?= count($fields) + 1 ?>"></div>
        <div class="isian"><label>&nbsp;</label><label class="pilihan" style="font-weight:400"><input type="checkbox" name="wajib" checked> Wajib diisi</label></div>
      </div>
      <button class="btn btn-utama" type="submit">Tambah pertanyaan</button>
    </form>
  </div>
</div>

<script>
var tipe = document.getElementById('tipe-field');
// jalankan sekali saat halaman dibuka
setTimeout(function(){ tipe.dispatchEvent(new Event('change')); }, 0);
tipe.addEventListener('change', function () {
  document.getElementById('kotak-opsi').style.display =
    ['select','radio','checkbox'].indexOf(this.value) > -1 ? 'block' : 'none';
  document.getElementById('kotak-skala').style.display =
    this.value === 'skala' ? 'block' : 'none';
  // textarea opsi ada di dua tempat - matikan yang bukan milik jenis terpilih
  var opsiUmum  = document.querySelector('#kotak-opsi textarea[name="opsi"]');
  var opsiSkala = document.querySelector('#kotak-skala textarea[name="opsi"]');
  if (opsiUmum)  opsiUmum.disabled  = (this.value === 'skala');
  if (opsiSkala) opsiSkala.disabled = (this.value !== 'skala');
});
</script>
