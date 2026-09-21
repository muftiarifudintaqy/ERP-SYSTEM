<?php $jml = count($grup); ?>
<div class="topbar">
  <div>
    <h1>Susun DISC test</h1>
    <p class="sub"><?= $jml ?> pertanyaan tersusun<?= $jml ? ' &middot; ' . ($jml * 4) . ' kata' : '' ?>. Tiap pertanyaan berisi 4 kata: satu D, satu I, satu S, dan satu C.</p>
  </div>
  <div class="aksi">
    <a class="btn" href="<?= base_url('hrd/disc') ?>">Lihat hasil</a>
    <?php if ($jml): ?><a class="btn" href="<?= base_url('f/disc') ?>" target="_blank">Pratinjau tes</a><?php endif; ?>
  </div>
</div>

<?php if (!$jml): ?>
  <div class="kartu" style="border-left:5px solid var(--hijau)">
    <h2>Mulai dari mana?</h2>
    <p>Tiap pertanyaan berisi satu kalimat pengantar dan 4 pernyataan. Peserta memberi nilai
      <b>1 sampai 10</b> pada <b>tiap</b> pernyataan, bukan cuma memilih dua. Tiap pernyataan mewakili satu dimensi:</p>
    <div class="grid g4" style="margin:14px 0">
      <?php foreach (['D','I','S','C'] as $h): ?>
        <div style="border-left:3px solid <?= $profil[$h]['warna'] ?>; padding-left:11px">
          <b style="color:<?= $profil[$h]['warna'] ?>"><?= $h ?> &mdash; <?= $profil[$h]['nama'] ?></b>
          <div class="label"><?= $profil[$h]['ringkas'] ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="label" style="border-left:3px solid var(--kuning); padding-left:11px; margin:14px 0">
      Nilai tiap dimensi dihitung dari jumlah poin yang masuk ke dimensi itu, lalu diubah jadi
      persentase terhadap nilai maksimalnya. Untuk survei umum soal perusahaan atau atasan,
      pakai menu <a href="<?= base_url('hrd/formulir') ?>">Formulir</a> — tiap survei punya tautan sendiri.
    </p>
    <p>Susun sendiri kata-katanya pakai bahasa yang biasa dipakai tim kamu, atau pakai 24 pertanyaan contoh sebagai titik awal lalu diedit sesuka hati.</p>
    <div class="aksi">
      <a class="btn btn-utama" href="<?= base_url('hrd/disc_contoh') ?>" data-konfirmasi="Isi dengan 24 pertanyaan contoh? Semuanya masih bisa diubah dan dihapus setelah masuk.">Isi dengan 24 pertanyaan contoh</a>
    </div>
  </div>
<?php endif; ?>

<div class="<?= $jml ? 'grid g2' : '' ?>" style="align-items:start; margin-top:<?= $jml ? '0' : '16px' ?>">
  <div>
    <?php if ($jml): ?>
      <?php foreach ($grup as $nomor => $kata): ?>
        <div class="kartu" style="padding:14px 16px">
          <form method="post" action="<?= base_url('hrd/disc_grup_simpan') ?>">
            <input type="hidden" name="grup" value="<?= $nomor ?>">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px">
              <b style="font-size:.85rem; color:var(--tinta-3)">Pertanyaan <?= $nomor ?></b>
              <a class="btn btn-kecil btn-bahaya" href="<?= base_url('hrd/disc_grup_hapus/' . $nomor) ?>" data-konfirmasi="Hapus pertanyaan <?= $nomor ?>?">Hapus</a>
            </div>
            <div class="isian" style="margin-bottom:11px">
              <input type="text" name="pertanyaan" value="<?= html_escape($kata[0]['pertanyaan'] ?? '') ?>"
                     placeholder="Kalimat pertanyaan, misal: Saat tenggat mepet, seberapa menggambarkan kamu?">
            </div>
            <?php for ($i = 0; $i < 4; $i++): $k = $kata[$i] ?? ['kata' => '', 'dimensi' => 'D']; ?>
              <div style="display:grid; grid-template-columns:1fr 92px; gap:8px; margin-bottom:7px">
                <input type="text" name="kata[]" value="<?= html_escape($k['kata']) ?>" required>
                <select name="dimensi[]" style="border-left:4px solid <?= $profil[$k['dimensi']]['warna'] ?>">
                  <?php foreach (['D','I','S','C'] as $h): ?>
                    <option value="<?= $h ?>" <?= $k['dimensi'] === $h ? 'selected' : '' ?>><?= $h ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endfor; ?>
            <button class="btn btn-kecil" type="submit">Simpan perubahan</button>
          </form>
        </div>
      <?php endforeach; ?>

      <div class="kartu">
        <h2>Kosongkan semua soal</h2>
        <p class="label">Hasil tes yang sudah masuk tidak ikut terhapus, hanya daftar soalnya.</p>
        <a class="btn btn-bahaya" style="margin-top:8px" href="<?= base_url('hrd/disc_kosongkan') ?>" data-konfirmasi="Hapus SEMUA pertanyaan DISC? Hasil tes yang sudah masuk tetap aman.">Kosongkan soal</a>
      </div>
    <?php endif; ?>
  </div>

  <div>
    <div class="kartu">
      <h2>Tambah pertanyaan baru</h2>
      <p class="label" style="margin-bottom:12px">Empat kata yang setara bobotnya, masing-masing satu dimensi berbeda. Contoh: Tegas (D), Ceria (I), Sabar (S), Teliti (C).</p>
      <form method="post" action="<?= base_url('hrd/disc_grup_simpan') ?>">
        <div class="isian">
          <label>Kalimat pertanyaan</label>
          <input type="text" name="pertanyaan" placeholder="Saat tenggat mepet, seberapa menggambarkan kamu?">
        </div>
        <?php foreach (['D','I','S','C'] as $i => $h): ?>
          <div style="display:grid; grid-template-columns:1fr 92px; gap:8px; margin-bottom:8px">
            <input type="text" name="kata[]" placeholder="Kata untuk <?= $profil[$h]['nama'] ?>" required>
            <select name="dimensi[]" style="border-left:4px solid <?= $profil[$h]['warna'] ?>">
              <?php foreach (['D','I','S','C'] as $x): ?>
                <option value="<?= $x ?>" <?= $x === $h ? 'selected' : '' ?>><?= $x ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endforeach; ?>
        <button class="btn btn-utama" type="submit">Tambah pertanyaan</button>
      </form>
    </div>

    <div class="kartu">
      <h2>Pengaturan tes</h2>
      <form method="post" action="<?= base_url('hrd/disc_pengaturan_simpan') ?>">
        <div class="isian"><label>Judul yang dilihat peserta</label><input type="text" name="judul" value="<?= html_escape($pengaturan['judul']) ?>"></div>
        <div class="isian"><label>Kalimat pengantar</label><textarea name="deskripsi"><?= html_escape($pengaturan['deskripsi']) ?></textarea></div>
        <div class="isian">
          <label>Status</label>
          <select name="status">
            <?php foreach (['draft' => 'Draft (belum bisa diisi)', 'terbuka' => 'Terbuka', 'ditutup' => 'Ditutup'] as $v => $t): ?>
              <option value="<?= $v ?>" <?= $pengaturan['status'] === $v ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="isian">
          <label>Nilai tertinggi pada skala</label>
          <select name="skala_maks">
            <?php foreach ([5, 6, 7, 8, 9, 10] as $sm): ?>
              <option value="<?= $sm ?>" <?= (int)$pengaturan['skala_maks'] === $sm ? 'selected' : '' ?>>1 sampai <?= $sm ?></option>
            <?php endforeach; ?>
          </select>
          <p class="bantu">Berlaku untuk semua pertanyaan DISC.</p>
        </div>
        <div class="isian"><label class="pilihan" style="font-weight:400"><input type="checkbox" name="satu_kali" <?= $pengaturan['satu_kali'] === '1' ? 'checked' : '' ?>> Satu email hanya boleh ikut sekali</label></div>
        <button class="btn btn-utama" type="submit">Simpan pengaturan</button>
      </form>

      <?php if ($jml): ?>
        <div style="border-top:1px solid var(--garis); margin-top:16px; padding-top:14px">
          <div class="label" style="margin-bottom:6px">Tautan untuk dibagikan</div>
          <div style="font-size:.8rem; word-break:break-all; margin-bottom:9px"><?= base_url('f/disc') ?></div>
          <button class="btn btn-kecil" type="button" onclick="navigator.clipboard.writeText('<?= base_url('f/disc') ?>');this.textContent='Tersalin'">Salin tautan</button>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($jml && $jml < 8): ?>
      <div class="kartu" style="border-left:5px solid var(--kuning)">
        <p style="margin:0"><b>Baru <?= $jml ?> pertanyaan.</b> Hasil DISC baru stabil kalau pertanyaannya cukup banyak — idealnya 20 sampai 24. Di bawah 8 pertanyaan, skornya gampang berubah cuma karena satu pilihan.</p>
      </div>
    <?php endif; ?>

    <?php if ($jml_jawaban): ?>
      <div class="kartu" style="border-left:5px solid var(--merah)">
        <p style="margin:0"><b>Sudah ada <?= $jml_jawaban ?> hasil tes.</b> Kalau soalnya diubah sekarang, hasil lama tetap tersimpan tapi tidak lagi sebanding dengan hasil baru. Sebaiknya ubah soal sebelum tes dibagikan, atau ulang tes untuk semua orang setelah diubah.</p>
      </div>
    <?php endif; ?>
  </div>
</div>
