<?php
  $sedang = NULL;
  if (!empty($ubah)) {
      foreach ($soal as $s) { if ((int)$s['id'] === (int)$ubah) { $sedang = $s; break; } }
  }
  $n = function ($k, $b = '') use ($sedang) {
      return html_escape($sedang[$k] ?? $b);
  };
?>

<style>
  /* Formulir soal punya tata letak sendiri: aturan label di hrd.css
     membuat teks dan kolom isian terpisah berjauhan. */
  .apt-form label { display:block; margin-bottom:12px; font-size:13px;
                    color:#475569; font-weight:600 }
  .apt-form input[type=text],
  .apt-form select,
  .apt-form textarea {
      display:block; width:100%; margin-top:5px; padding:8px 11px;
      border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;
      font-family:inherit; color:#1f2937; background:#fff; box-sizing:border-box;
  }
  .apt-form textarea { resize:vertical }
  .apt-dua { display:grid; grid-template-columns:1fr 1fr; gap:0 14px }
  .apt-cek { display:flex !important; align-items:center; gap:8px; margin:14px 0 0 }
  .apt-cek input { width:17px; height:17px; margin:0 }

  .apt-btn { display:inline-block; padding:7px 15px; border-radius:7px;
             font-size:12.5px; font-weight:600; text-decoration:none;
             border:1px solid transparent; cursor:pointer }
  .apt-ubah { background:#E8EDF8; color:#1F4696; border-color:#c7d5f0 }
  .apt-ubah:hover { background:#dbe4f6 }
  .apt-hapus { background:#FEE2E2; color:#991B1B; border-color:#f5c6c6 }
  .apt-hapus:hover { background:#fcd5d5 }

  /* Layar sempit: dua kolom pilihan ditumpuk, tabel bisa digulir
     mendatar supaya kolomnya tidak saling menghimpit. */
  @media (max-width:700px) {
    .apt-dua { grid-template-columns:1fr }
    .apt-tabel { overflow-x:auto; -webkit-overflow-scrolling:touch }
    .apt-tabel table { min-width:620px }
    .apt-btn { display:block; margin-bottom:5px; text-align:center }
  }
</style>

<h1>Susun Test IQ</h1>
<p class="label">
  <?= count($soal) ?> soal tersimpan.
  Peserta menjawab satu per satu; kunci dan pembahasan tidak ditampilkan kepada mereka.
</p>

<div class="kartu" style="margin-bottom:18px">
  <h3 style="margin-top:0"><?= $sedang ? 'Ubah soal nomor ' . (int)$sedang['nomor'] : 'Tambah soal baru' ?></h3>

  <form class="apt-form" method="post" action="<?= base_url('hrd/aptitude_simpan') ?>">
    <input type="hidden" name="id" value="<?= (int)($sedang['id'] ?? 0) ?>">

    <label>Kategori
      <select name="kategori">
        <?php foreach (['Numerik','Verbal','Logika','Spasial'] as $k): ?>
          <option value="<?= $k ?>" <?= $n('kategori') === $k ? 'selected' : '' ?>><?= $k ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Pertanyaan
      <textarea name="soal" rows="3" required><?= $n('soal') ?></textarea>
    </label>

    <div class="apt-dua">
      <label>Pilihan A <input type="text" name="opsi_a" value="<?= $n('opsi_a') ?>" required></label>
      <label>Pilihan B <input type="text" name="opsi_b" value="<?= $n('opsi_b') ?>" required></label>
      <label>Pilihan C <input type="text" name="opsi_c" value="<?= $n('opsi_c') ?>" required></label>
      <label>Pilihan D <input type="text" name="opsi_d" value="<?= $n('opsi_d') ?>" required></label>
    </div>

    <label>Jawaban benar
      <select name="kunci">
        <?php foreach (['A','B','C','D'] as $k): ?>
          <option value="<?= $k ?>" <?= $n('kunci') === $k ? 'selected' : '' ?>><?= $k ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Pembahasan <span class="label">(boleh dikosongkan)</span>
      <textarea name="pembahasan" rows="2"><?= $n('pembahasan') ?></textarea>
    </label>

    <label class="apt-cek">
      <input type="checkbox" name="aktif" value="1" <?= ($sedang === NULL || $sedang['aktif']) ? 'checked' : '' ?>>
      Dipakai dalam tes
    </label>

    <div style="margin-top:14px">
      <button class="btn" type="submit"><?= $sedang ? 'Simpan perubahan' : 'Tambah soal' ?></button>
      <?php if ($sedang): ?>
        <a class="btn" style="background:#94a3b8" href="<?= base_url('hrd/aptitude_soal') ?>">Batal</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="kartu">
  <?php if (empty($soal)): ?>
    <div class="kosong">Belum ada soal. Tambahkan lewat formulir di atas.</div>
  <?php else: ?>
    <div class="apt-tabel">
    <table>
      <thead>
        <tr><th>No</th><th>Kategori</th><th>Pertanyaan</th><th>Kunci</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($soal as $s): ?>
        <tr>
          <td><?= (int)$s['nomor'] ?></td>
          <td><?= html_escape($s['kategori']) ?></td>
          <td>
            <?= html_escape(mb_strimwidth($s['soal'], 0, 90, '...')) ?>
            <div class="label" style="font-size:11.5px">
              A. <?= html_escape(mb_strimwidth($s['opsi_a'], 0, 22, '..')) ?> &middot;
              B. <?= html_escape(mb_strimwidth($s['opsi_b'], 0, 22, '..')) ?> &middot;
              C. <?= html_escape(mb_strimwidth($s['opsi_c'], 0, 22, '..')) ?> &middot;
              D. <?= html_escape(mb_strimwidth($s['opsi_d'], 0, 22, '..')) ?>
            </div>
          </td>
          <td><b><?= html_escape($s['kunci']) ?></b></td>
          <td><?= $s['aktif'] ? 'Dipakai' : 'Nonaktif' ?></td>
          <td style="white-space:nowrap">
            <a class="apt-btn apt-ubah" href="<?= base_url('hrd/aptitude_soal?ubah=' . (int)$s['id']) ?>">Ubah</a>
            <a class="apt-btn apt-hapus" href="<?= base_url('hrd/aptitude_hapus/' . (int)$s['id']) ?>"
               onclick="return confirm('Hapus soal nomor <?= (int)$s['nomor'] ?>?')">Hapus</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  <?php endif; ?>
</div>
