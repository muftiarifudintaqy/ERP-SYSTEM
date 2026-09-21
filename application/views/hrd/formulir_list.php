<div class="topbar">
  <div>
    <h1>Formulir</h1>
    <p class="sub">Bikin form sendiri seperti Google Form, tapi jawabannya langsung masuk ke halaman ini.</p>
  </div>
</div>

<div class="kartu">
  <h2>Buat formulir baru</h2>
  <form method="post" action="<?= base_url('hrd/formulir_simpan') ?>">
    <div class="grid g2">
      <div class="isian"><label>Judul formulir</label><input type="text" name="judul" placeholder="Contoh: Survei Kepuasan Kerja Q3" required></div>
      <div class="isian"><label>Alamat tautan (opsional)</label><input type="text" name="slug" placeholder="survei-kepuasan-q3"><p class="bantu"><?= base_url('f/') ?>...</p></div>
    </div>
    <div class="isian"><label>Keterangan untuk pengisi</label><textarea name="deskripsi" placeholder="Jelaskan tujuan pengisian dan tenggat waktunya."></textarea></div>
    <div class="aksi">
      <button class="btn btn-utama" type="submit">Buat dan susun pertanyaan</button>
      <a class="btn" href="<?= base_url('hrd/formulir_contoh') ?>" data-konfirmasi="Buat survei contoh tentang perusahaan (11 pertanyaan skala 1-10 + 2 isian bebas)? Semuanya bisa diedit setelah dibuat.">Pakai contoh survei perusahaan</a>
    </div>
  </form>
</div>

<?php if (!$rows): ?>
  <div class="kartu kosong" style="margin-top:16px"><b>Belum ada formulir</b>
    Tiap formulir yang kamu buat punya tautan sendiri, jadi survei satu dengan yang lain tidak tercampur.
    Form data karyawan, inventaris, dan DISC sudah tersedia sebagai halaman bawaan.
  </div>
<?php else: ?>
<div class="tabel-bungkus" style="margin-top:16px">
  <table>
    <thead><tr><th>Judul</th><th>Tautan</th><th>Status</th><th>Jawaban</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $f): ?>
      <tr>
        <td><b><?= html_escape($f['judul']) ?></b><small style="display:block;color:var(--tinta-3)"><?= html_escape(character_limiter($f['deskripsi'], 70)) ?></small></td>
        <td style="font-size:.8rem;word-break:break-all"><?= base_url('f/' . $f['slug']) ?></td>
        <td><span class="tag <?= $f['status'] === 'terbuka' ? 'tag-hijau' : ($f['status'] === 'draft' ? 'tag-kuning' : 'tag-abu') ?>"><?= ucfirst($f['status']) ?></span></td>
        <td><?= $f['jml_jawaban'] ?></td>
        <td class="rapat">
          <div class="aksi">
            <a class="btn btn-kecil" href="<?= base_url('hrd/formulir_builder/' . $f['id']) ?>">Susun</a>
            <a class="btn btn-kecil" href="<?= base_url('hrd/jawaban/' . $f['id']) ?>">Jawaban</a>
            <a class="btn btn-kecil btn-bahaya" href="<?= base_url('hrd/formulir_hapus/' . $f['id']) ?>" data-konfirmasi="Hapus formulir beserta seluruh jawabannya?">Hapus</a>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
