<?php
  $sedang = NULL;
  if (!empty($ubah)) {
      foreach ($soal as $s) { if ((int)$s['id'] === (int)$ubah) { $sedang = $s; break; } }
  }
  $opsi_kini = $sedang['opsi'] ?? [];
  $aspek_kini = $opsi_kini[0]['aspek'] ?? 'kerjasama';
?>

<style>
  /* Aturan label di hrd.css memisahkan teks dari kolom isiannya,
     jadi formulir ini memakai tata letaknya sendiri. */
  .kk-form label { display:block; margin-bottom:12px; font-size:13px;
                   color:#475569; font-weight:600 }
  .kk-form input[type=text],
  .kk-form select,
  .kk-form textarea {
      display:block; width:100%; margin-top:5px; padding:8px 11px;
      border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;
      font-family:inherit; color:#1f2937; background:#fff; box-sizing:border-box;
  }
  .kk-form textarea { resize:vertical }

  .kk-opsi { display:grid; grid-template-columns:1fr 92px; gap:0 12px;
             align-items:end; margin-bottom:10px }
  .kk-opsi label { margin-bottom:0 }

  .kk-cek { display:flex !important; align-items:center; gap:8px; margin:14px 0 0 }
  .kk-cek input { width:17px; height:17px; margin:0 }

  .kk-btn { display:inline-block; padding:7px 15px; border-radius:7px;
            font-size:12.5px; font-weight:600; text-decoration:none;
            border:1px solid transparent; cursor:pointer }
  .kk-ubah { background:#E8EDF8; color:#1F4696; border-color:#c7d5f0 }
  .kk-ubah:hover { background:#dbe4f6 }
  .kk-hapus { background:#FEE2E2; color:#991B1B; border-color:#f5c6c6 }
  .kk-hapus:hover { background:#fcd5d5 }

  .kk-daftar { overflow-x:auto }
  .kk-bobot { display:inline-block; min-width:20px; padding:1px 6px; border-radius:5px;
              background:#eef2f7; font-size:11.5px; font-weight:700; color:#475569 }

  @media (max-width:700px) {
    .kk-opsi { grid-template-columns:1fr }
    .kk-opsi label:last-child { margin-top:6px }
    .kk-daftar table { min-width:560px }
    .kk-btn { display:block; margin-bottom:5px; text-align:center }
  }
</style>

<h1>Susun Karakteristik</h1>
<p class="label">
  <?= count($soal) ?> soal tersimpan. Tiap soal berisi satu situasi dengan lima pilihan.
  Bobot 1 sampai 5 menentukan skor; peserta tidak melihat angkanya.
</p>

<div class="kartu" style="margin-bottom:18px">
  <h3 style="margin-top:0"><?= $sedang ? 'Ubah soal nomor ' . (int)$sedang['nomor'] : 'Tambah soal baru' ?></h3>

  <form class="kk-form" method="post" action="<?= base_url('hrd/karakteristik_simpan') ?>">
    <input type="hidden" name="id" value="<?= (int)($sedang['id'] ?? 0) ?>">

    <label>Situasi
      <textarea name="situasi" rows="3" required placeholder="Contoh: Pagi hari ketika Anda berangkat..."><?= html_escape($sedang['situasi'] ?? '') ?></textarea>
    </label>

    <label>Aspek yang dinilai
      <select name="aspek">
        <?php foreach ($aspek as $a): ?>
          <option value="<?= $a ?>" <?= $aspek_kini === $a ? 'selected' : '' ?>><?= $a ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <div style="margin-top:14px;font-size:13px;font-weight:600;color:#475569">
      Lima pilihan &mdash; tiap bobot 1 sampai 5 dipakai sekali
    </div>

    <?php for ($i = 0; $i < 5; $i++): ?>
      <div class="kk-opsi">
        <label>Pilihan <?= $i + 1 ?>
          <input type="text" name="teks[]" required
                 value="<?= html_escape($opsi_kini[$i]['teks'] ?? '') ?>">
        </label>
        <label>Bobot
          <select name="bobot[]">
            <?php for ($b = 1; $b <= 5; $b++): ?>
              <option value="<?= $b ?>" <?= (int)($opsi_kini[$i]['bobot'] ?? ($i + 1)) === $b ? 'selected' : '' ?>><?= $b ?></option>
            <?php endfor; ?>
          </select>
        </label>
      </div>
    <?php endfor; ?>

    <label class="kk-cek">
      <input type="checkbox" name="aktif" value="1" <?= ($sedang === NULL || $sedang['aktif']) ? 'checked' : '' ?>>
      Dipakai dalam tes
    </label>

    <div style="margin-top:14px">
      <button class="btn" type="submit"><?= $sedang ? 'Simpan perubahan' : 'Tambah soal' ?></button>
      <?php if ($sedang): ?>
        <a class="btn" style="background:#94a3b8" href="<?= base_url('hrd/karakteristik_soal') ?>">Batal</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="kartu">
  <?php if (empty($soal)): ?>
    <div class="kosong">Belum ada soal. Tambahkan lewat formulir di atas.</div>
  <?php else: ?>
    <div class="kk-daftar">
    <table>
      <thead>
        <tr><th>No</th><th>Situasi</th><th>Aspek</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($soal as $s): ?>
        <tr>
          <td><?= (int)$s['nomor'] ?></td>
          <td>
            <?= html_escape(mb_strimwidth($s['situasi'], 0, 95, '...')) ?>
            <div class="label" style="font-size:11.5px;margin-top:4px">
              <?php foreach ($s['opsi'] as $o): ?>
                <span class="kk-bobot"><?= (int)$o['bobot'] ?></span>
                <?= html_escape(mb_strimwidth($o['teks'], 0, 26, '..')) ?>&nbsp;
              <?php endforeach; ?>
            </div>
          </td>
          <td><?= html_escape($s['opsi'][0]['aspek'] ?? '-') ?></td>
          <td><?= $s['aktif'] ? 'Dipakai' : 'Nonaktif' ?></td>
          <td style="white-space:nowrap">
            <a class="kk-btn kk-ubah" href="<?= base_url('hrd/karakteristik_soal?ubah=' . (int)$s['id']) ?>">Ubah</a>
            <a class="kk-btn kk-hapus" href="<?= base_url('hrd/karakteristik_hapus/' . (int)$s['id']) ?>"
               onclick="return confirm('Hapus soal nomor <?= (int)$s['nomor'] ?>?')">Hapus</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  <?php endif; ?>
</div>
