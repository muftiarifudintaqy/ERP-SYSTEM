<?php
/**
 * Rincian jawaban peserta. Halaman ini hanya untuk HRD ke atas --
 * peserta tidak pernah melihatnya, karena menampilkan kunci kepada
 * orang yang mengerjakan berarti membocorkan tes ke peserta berikutnya.
 */
?>
<style>
  .jw-kartu{background:#fff;border-radius:12px;padding:20px;margin-bottom:16px;
            box-shadow:0 1px 3px rgba(0,0,0,.06)}
  .jw-soal{border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px;
           margin-bottom:10px;background:#fff}
  .jw-soal.benar{border-left:4px solid #16a34a}
  .jw-soal.salah{border-left:4px solid #dc2626}
  .jw-nomor{display:inline-block;min-width:26px;height:26px;line-height:26px;
            text-align:center;border-radius:6px;background:#1F4696;color:#fff;
            font-size:12px;font-weight:700;margin-right:8px}
  .jw-tanya{font-weight:600;color:#1f2937;margin-bottom:8px;line-height:1.5}
  .jw-baris{font-size:13.5px;margin:4px 0;color:#374151}
  .jw-label{display:inline-block;min-width:96px;color:#6b7280;font-size:12.5px}
  .jw-ok{color:#15803d;font-weight:600}
  .jw-no{color:#b91c1c;font-weight:600}
  .jw-bahas{margin-top:8px;padding:8px 12px;background:#f8fafc;border-radius:8px;
            font-size:12.5px;color:#475569;line-height:1.55}
  .jw-pil{display:inline-block;padding:2px 9px;border-radius:999px;
          font-size:11.5px;font-weight:700}
  .jw-D{background:#FEE2E2;color:#991B1B}
  .jw-I{background:#FEF3C7;color:#92400E}
  .jw-S{background:#DCFCE7;color:#166534}
  .jw-C{background:#DBEAFE;color:#1E40AF}
  .jw-bobot{display:inline-block;padding:2px 9px;border-radius:999px;
            font-size:11.5px;font-weight:700;background:#EEF3FC;color:#1F4696}
  .jw-tab{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
  .jw-tab a{padding:8px 16px;border-radius:8px;text-decoration:none;
            font-size:13.5px;font-weight:600;background:#f1f5f9;color:#475569}
  .jw-tab a.aktif{background:#1F4696;color:#fff}
  .jw-kosong{text-align:center;padding:30px;color:#94a3b8}
</style>

<div class="container-fluid py-3">

  <div class="jw-kartu">
    <a href="<?= base_url('hrd/asesmen_detail/' . $a['id']) ?>"
       style="font-size:13px;color:#1F4696;text-decoration:none">&larr; Kembali ke hasil</a>
    <h4 style="margin:8px 0 4px"><?= html_escape($a['nama']) ?></h4>
    <p style="color:#64748b;font-size:13px;margin:0">
      Rincian jawaban &middot; hanya terlihat oleh HRD dan atasan
      <?php $tab = (int)($a['pindah_tab'] ?? 0); ?>
      <?php if ($tab > 0): ?>
        &middot; <span style="color:<?= $tab >= 10 ? '#b45309' : '#64748b' ?>">
          keluar halaman <b><?= $tab ?>&times;</b> saat mengerjakan</span>
      <?php endif; ?>
    </p>
  </div>

  <div class="jw-tab">
    <a href="#iq" class="aktif">Test IQ</a>
    <a href="#karakteristik">Karakteristik</a>
    <a href="#disc">DISC</a>
  </div>

  <!-- ================= TEST IQ ================= -->
  <div class="jw-kartu" id="iq">
    <h5 style="margin:0 0 12px">Test IQ
      <?php if ($iq): ?>
        <span style="font-weight:400;color:#64748b;font-size:13.5px">
          &mdash; benar <?= (int)$iq['benar'] ?> dari <?= (int)$iq['total_soal'] ?>
        </span>
      <?php endif; ?>
    </h5>

    <?php
      /* Kunci Test IQ diacak ulang pada 17 September 2026 karena pola
         lamanya (A-B-C-D berulang) bisa dijawab benar tanpa membaca soal.
         Asesmen sebelum tanggal itu dinilai dengan kunci lama, jadi
         perbandingan di bawah tidak berlaku -- dan skornya sendiri tidak
         bisa dipercaya. */
      $sebelum_acak = strtotime($a['created_at']) < strtotime('2026-09-17 17:00:00');
    ?>
    <?php if ($sebelum_acak && !empty($iq_baris)): ?>
      <div style="background:#FEF3C7;border:1px solid #FCD34D;color:#92400E;
                  padding:12px 16px;border-radius:10px;margin-bottom:14px;
                  font-size:13px;line-height:1.6">
        Asesmen ini dikerjakan sebelum kunci Test IQ diacak. Saat itu
        kuncinya berpola A-B-C-D berulang, sehingga nilai sempurna bisa
        didapat tanpa membaca soal. Perbandingan di bawah memakai kunci
        baru, jadi tidak cocok &mdash; dan skornya sebaiknya tidak dipakai.
        Minta peserta mengulang tesnya.
      </div>
    <?php endif; ?>

    <?php if (empty($iq_baris)): ?>
      <div class="jw-kosong">Belum dikerjakan.</div>
    <?php else: ?>
      <?php foreach ($iq_baris as $b): ?>
        <div class="jw-soal <?= $b['benar'] ? 'benar' : 'salah' ?>">
          <div class="jw-tanya">
            <span class="jw-nomor"><?= $b['nomor'] ?></span>
            <?= html_escape($b['soal']) ?>
            <span style="font-weight:400;color:#94a3b8;font-size:12px">
              (<?= html_escape($b['kategori']) ?>)</span>
          </div>
          <div class="jw-baris">
            <span class="jw-label">Dijawab</span>
            <span class="<?= $b['benar'] ? 'jw-ok' : 'jw-no' ?>">
              <?= $b['pilih'] ? html_escape($b['pilih'] . '. ' . $b['pilih_teks']) : '(kosong)' ?>
              <?= $b['benar'] ? '&check;' : '&times;' ?>
            </span>
          </div>
          <?php if (!$b['benar']): ?>
            <div class="jw-baris">
              <span class="jw-label">Jawaban benar</span>
              <span class="jw-ok"><?= html_escape($b['kunci'] . '. ' . $b['kunci_teks']) ?></span>
            </div>
          <?php endif; ?>
          <?php if (!empty($b['pembahasan'])): ?>
            <div class="jw-bahas"><?= html_escape($b['pembahasan']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ============== KARAKTERISTIK ============== -->
  <div class="jw-kartu" id="karakteristik">
    <h5 style="margin:0 0 4px">Karakteristik</h5>
    <p style="color:#64748b;font-size:12.5px;margin:0 0 12px;line-height:1.55">
      Tidak ada jawaban salah di sini. Tiap pilihan punya bobot 1&ndash;5;
      makin tinggi bobotnya, makin sesuai dengan standar kerja yang
      diharapkan. Pilihan berbobot 5 ditampilkan sebagai pembanding, bukan
      sebagai kunci.
    </p>

    <?php if (empty($kar_baris)): ?>
      <div class="jw-kosong">Belum dikerjakan.</div>
    <?php else: ?>
      <?php foreach ($kar_baris as $b): ?>
        <div class="jw-soal">
          <div class="jw-tanya">
            <span class="jw-nomor"><?= $b['nomor'] ?></span>
            <?= html_escape($b['situasi']) ?>
          </div>
          <div class="jw-baris">
            <span class="jw-label">Dipilih</span>
            <?= html_escape($b['pilih_teks']) ?>
            <span class="jw-bobot">bobot <?= $b['pilih_bobot'] ?></span>
          </div>
          <?php if ($b['pilih_bobot'] < $b['terbaik_bobot']): ?>
            <div class="jw-baris">
              <span class="jw-label">Bobot tertinggi</span>
              <span style="color:#15803d"><?= html_escape($b['terbaik_teks']) ?></span>
              <span class="jw-bobot">bobot <?= $b['terbaik_bobot'] ?></span>
            </div>
          <?php endif; ?>
          <?php if ($b['aspek']): ?>
            <div class="jw-baris" style="color:#94a3b8;font-size:12px">
              Aspek: <?= html_escape($b['aspek']) ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- =================== DISC =================== -->
  <div class="jw-kartu" id="disc">
    <h5 style="margin:0 0 4px">DISC</h5>
    <p style="color:#64748b;font-size:12.5px;margin:0 0 12px;line-height:1.55">
      DISC bukan ujian &mdash; tidak ada jawaban benar atau salah. Tiap
      pilihan mewakili satu dimensi gaya kerja, dan yang dibaca adalah
      kecenderungannya, bukan skor.
    </p>

    <?php if (empty($disc_baris)): ?>
      <div class="jw-kosong">Belum dikerjakan.</div>
    <?php else: ?>
      <?php foreach ($disc_baris as $b): ?>
        <div class="jw-soal">
          <div class="jw-tanya">
            <span class="jw-nomor"><?= $b['nomor'] ?></span>
            <?= html_escape($b['situasi']) ?>
          </div>
          <div class="jw-baris">
            <span class="jw-label">Dipilih</span>
            <?= html_escape($b['pilih_teks']) ?>
            <?php if ($b['dimensi']): ?>
              <span class="jw-pil jw-<?= html_escape($b['dimensi']) ?>">
                <?= html_escape($b['dimensi']) ?></span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>
