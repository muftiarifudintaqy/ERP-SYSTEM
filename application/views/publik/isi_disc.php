<?php $lama = $lama ?? []; $maks = (int)($skala_maks ?: 10); ?>
<style>
.publik .soal-disc{background:#fff;border:1px solid #DCE3DF;border-radius:12px;padding:18px 20px;margin-bottom:14px}
.publik .soal-nomor{font:600 .78rem/1.2 system-ui,sans-serif;color:#7C8A85;margin-bottom:6px}
.publik .soal-tanya{font:650 1rem/1.35 system-ui,sans-serif;color:#16211E;margin-bottom:16px}
.publik .pernyataan{padding:12px 0;border-top:1px solid #EDF1EF}
.publik .pernyataan:first-of-type{border-top:none;padding-top:2px}
.publik .pernyataan-kata{font-weight:600;font-size:.95rem;margin-bottom:8px}
.publik .nilai{display:flex;gap:5px;flex-wrap:wrap}
.publik .nilai label{flex:1 1 38px;min-width:38px;margin:0;cursor:pointer}
.publik .nilai input{position:absolute;opacity:0;width:0;height:0}
.publik .nilai span{display:block;text-align:center;padding:9px 0;border:1px solid #DCE3DF;border-radius:8px;
  background:#fff;font-weight:650;font-size:.9rem;color:#16211E;transition:.12s}
.publik .nilai label:hover span{border-color:#1F6F5C;background:#E4F0EB}
.publik .nilai input:checked + span{background:#1F6F5C;border-color:#1F6F5C;color:#fff}
.publik .nilai input:focus-visible + span{outline:2px solid #1F6F5C;outline-offset:2px}
.publik .nilai-arti{display:flex;justify-content:space-between;margin-top:6px;font-size:.75rem;color:#7C8A85}
@media (max-width:560px){
  .publik .nilai label{flex:1 1 30px;min-width:30px}
  .publik .nilai span{padding:8px 0;font-size:.8rem}
}
</style>

<div class="publik-kepala">
  <div class="garis"></div>
  <h1><?= html_escape($judul) ?></h1>
  <?php if (trim((string)$deskripsi) !== ''): ?>
    <p><?= nl2br(html_escape($deskripsi)) ?></p>
  <?php endif; ?>
</div>

<form method="post" id="form-disc">
  <div class="kartu">
    <div class="grid g2">
      <div class="isian"><label>Nama lengkap <span class="wajib">*</span></label><input type="text" name="nama" value="<?= html_escape($lama['nama'] ?? '') ?>" required></div>
      <div class="isian"><label>Email <span class="wajib">*</span></label><input type="email" name="email" value="<?= html_escape($lama['email'] ?? '') ?>" required></div>
    </div>
    <div class="isian"><label>Jabatan / divisi</label><input type="text" name="jabatan" value="<?= html_escape($lama['jabatan'] ?? '') ?>"></div>
    <p class="label" style="margin:0">Beri nilai <b>1</b> sampai <b><?= $maks ?></b> untuk tiap pernyataan.
      1 berarti sama sekali tidak menggambarkan kamu, <?= $maks ?> berarti sangat menggambarkan kamu.
      Semua pernyataan diberi nilai, boleh sama besar.</p>
  </div>

  <div class="progres">
    <div class="progres-track"><div class="progres-fill" id="progres"></div></div>
    <small id="progres-teks">0 dari <?= count($soal) ?> pertanyaan terisi</small>
  </div>

  <?php $urut = 1; foreach ($soal as $nomor => $kata): ?>
    <div class="soal-disc" data-grup="<?= $nomor ?>">
      <div class="soal-nomor">Pertanyaan <?= $urut ?> dari <?= count($soal) ?></div>
      <div class="soal-tanya"><?= html_escape($kata[0]['pertanyaan'] ?: 'Seberapa menggambarkan kamu?') ?></div>

      <?php foreach ($kata as $i => $k): $nama_input = 'n_' . $nomor . '_' . $i; ?>
        <div class="pernyataan">
          <div class="pernyataan-kata"><?= html_escape($k['kata']) ?></div>
          <div class="nilai">
            <?php for ($n = 1; $n <= $maks; $n++): ?>
              <label>
                <input type="radio" name="<?= $nama_input ?>" value="<?= $n ?>" <?= (($lama[$nama_input] ?? null) === (string)$n) ? 'checked' : '' ?> required>
                <span><?= $n ?></span>
              </label>
            <?php endfor; ?>
          </div>
          <div class="nilai-arti"><span>1 &mdash; bukan saya</span><span>sangat saya &mdash; <?= $maks ?></span></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php $urut++; endforeach; ?>

  <button class="btn btn-utama" type="submit" style="width:100%; justify-content:center; padding:13px">Lihat hasil saya</button>
</form>

<script>
(function () {
  var form  = document.getElementById('form-disc');
  var kotak = Array.prototype.slice.call(document.querySelectorAll('.soal-disc'));

  form.addEventListener('change', function (e) {
    if (e.target.type === 'radio') hitung();
  });

  function hitung() {
    var selesai = 0;
    kotak.forEach(function (k) {
      var baris = k.querySelectorAll('.pernyataan');
      var isi = 0;
      baris.forEach(function (b) { if (b.querySelector('input:checked')) isi++; });
      if (isi === baris.length && baris.length) selesai++;
    });
    document.getElementById('progres').style.width = (selesai / kotak.length * 100) + '%';
    document.getElementById('progres-teks').textContent =
      selesai + ' dari ' + kotak.length + ' pertanyaan terisi';
  }
  hitung();
})();
</script>
