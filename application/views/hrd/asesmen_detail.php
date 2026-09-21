<?php
$disc_nama = ['D'=>'Dominance','I'=>'Influence','S'=>'Steadiness','C'=>'Compliance'];
$bf_nama   = ['O'=>'Keterbukaan','C'=>'Kehati-hatian','E'=>'Ekstraversi','A'=>'Keramahan','N'=>'Kepekaan Emosi'];

$ai = function ($baris) {
    if (empty($baris['ai_json'])) return [];
    $j = json_decode($baris['ai_json'], TRUE);
    return is_array($j) ? $j : [];
};

$batang = function ($nilai, $warna) {
    $h = '';
    foreach ($nilai as $k => $v) {
        $w = max(2, min(100, (int)$v));
        $h .= '<div class="as-hrd-baris">'
            . '<span class="as-hrd-kode" style="background:' . $warna[$k] . '">' . $k . '</span>'
            . '<span class="as-hrd-jalur"><span class="as-hrd-isi" style="--w:' . $w
            . '%;background:' . $warna[$k] . '"></span></span>'
            . '<b class="as-hrd-angka">' . (int)$v . '%</b></div>';
    }
    return $h;
};

/* Baris berlabel: dipakai untuk aspek karakteristik dan kategori soal.
   Bentuknya sama dengan batang di atas supaya seluruh halaman seragam. */
$batang_label = function ($baris) {
    $h = '<div style="margin-top:12px">';
    foreach ($baris as $b) {
        list($label, $nilai, $maks, $warna) = $b;
        $p = $maks > 0 ? (int)round($nilai / $maks * 100) : 0;
        $h .= '<div class="as-hrd-baris">'
            . '<span class="as-hrd-label">' . $label . '</span>'
            . '<span class="as-hrd-jalur"><span class="as-hrd-isi" style="--w:'
            . max(2, min(100, $p)) . '%;background:' . $warna . '"></span></span>'
            . '<b class="as-hrd-angka">' . $nilai . '/' . $maks . '</b></div>';
    }
    return $h . '</div>';
};

$blok_ai = function ($n, $judul = 'Analisis') {
    if (empty($n['summary'])) return '';
    $h = '<h3 style="margin:22px 0 8px;font-size:15px">' . $judul . '</h3>';
    if (!empty($n['headline'])) $h .= '<p><b>' . html_escape($n['headline']) . '</b></p>';
    $h .= '<p>' . html_escape($n['summary']) . '</p>';
    foreach ([
        'strengths' => 'Kekuatan',
        'development_areas' => 'Perlu dikembangkan',
        'traits' => 'Ciri khas',
        'tips' => 'Saran langkah',
    ] as $k => $label) {
        if (empty($n[$k]) || !is_array($n[$k])) continue;
        $h .= '<p class="label" style="margin:12px 0 4px"><b>' . $label . '</b></p><ul style="margin:0;padding-left:18px">';
        foreach ($n[$k] as $t) $h .= '<li>' . html_escape($t) . '</li>';
        $h .= '</ul>';
    }
    foreach (['work_style' => 'Gaya kerja', 'cocok_di_peran' => 'Cocok di peran',
              'kehidupan_sehari' => 'Sehari-hari'] as $k => $label) {
        if (empty($n[$k])) continue;
        $h .= '<p style="margin:12px 0 0"><b>' . $label . '.</b> ' . html_escape($n[$k]) . '</p>';
    }
    return $h;
};
?>


<style>
/* Batang nilai. Semua baris memakai bentuk yang sama supaya keempat
   kartu terbaca seragam. Lebar diisi lewat --w dan baru dijalankan
   setelah kelas as-hrd-siap dipasang, sehingga batangnya terlihat
   naik dari nol ke nilainya. */
.as-hrd-baris { display: flex; align-items: center; gap: 10px; margin-bottom: 9px; }
.as-hrd-kode {
  flex: 0 0 26px; height: 26px; border-radius: 7px; color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800;
}
.as-hrd-label { flex: 0 0 118px; font-size: 13px; color: #4B5563; }
.as-hrd-jalur { flex: 1; height: 9px; border-radius: 99px; background: #ECEAF6; overflow: hidden; }
.as-hrd-isi {
  display: block; height: 100%; width: 0; border-radius: 99px;
  transition: width .9s cubic-bezier(.22,1,.36,1);
}
.as-hrd-siap .as-hrd-isi { width: var(--w); }
.as-hrd-angka { flex: 0 0 52px; text-align: right; font-size: 14px; }

@media (prefers-reduced-motion: reduce) {
  .as-hrd-isi { transition: none; width: var(--w); }
}
@media print { .as-hrd-isi { transition: none; width: var(--w); } }
</style>

<div class="topbar">
  <div>
    <h1><?= html_escape($a['nama']) ?></h1>
    <a href="<?= base_url('hrd/asesmen_jawaban/' . $a['id']) ?>"
       style="display:inline-block;margin:6px 0 10px;padding:7px 15px;
              background:#1F4696;color:#fff;border-radius:8px;
              text-decoration:none;font-size:13px;font-weight:600">
      Lihat rincian jawaban
    </a>
    <p class="sub">
      <?= html_escape($a['divisi'] ?: '-') ?>
      &middot; <?= html_escape($a['email'] ?: '-') ?>
      &middot; mulai <?= date('d F Y', strtotime($a['created_at'])) ?>
    </p>
  </div>
  <div class="aksi">
    <a class="btn" href="<?= base_url('hrd/asesmen') ?>">Kembali</a>
    <button class="btn" type="button" onclick="window.print()">Cetak</button>
    <a class="btn btn-bahaya" href="<?= base_url('hrd/asesmen_hapus/' . $a['id']) ?>" data-konfirmasi="Hapus seluruh hasil asesmen orang ini?">Hapus</a>
  </div>
</div>

<!-- ============ STIFIn: khusus HRD ============ -->
<div class="kartu" style="border-left:5px solid #B45309;background:#FFFBF5">
  <h2 style="color:#92400E">STIFIn &mdash; hanya untuk HRD</h2>
  <?php if ($stifin): ?>
    <p style="font-size:22px;font-weight:800;margin:6px 0">
      <?= html_escape($stifin['singkatan']) ?> &mdash; <?= html_escape($stifin['nama']) ?>
    </p>
    <p class="label">
      Tanggal lahir <?= $a['tanggal_lahir'] ? date('d F Y', strtotime($a['tanggal_lahir'])) : '-' ?>
      &middot; golongan darah <b><?= $a['gol_darah'] ? html_escape($a['gol_darah']) : '-' ?></b>
      <?php
        /* Berapa kali peserta meninggalkan halaman saat mengerjakan.
           Ditampilkan apa adanya tanpa kesimpulan: berpindah tab bisa
           berarti membuka AI, bisa juga notifikasi masuk atau baterai
           lemah. Yang menilai tetap orang, bukan angka ini. */
        $tab = (int)($a['pindah_tab'] ?? 0);
      ?>
      <?php if ($tab > 0): ?>
        &middot; <span style="color:<?= $tab >= 10 ? '#b45309' : '#64748b' ?>">
          keluar halaman <b><?= $tab ?>&times;</b> saat mengerjakan
        </span>
      <?php endif; ?>
      &middot; angka <?= (int)$a['stifin_kode'] ?>
      <?php if ($a['angka_nama']): ?>&middot; angka nama <?= (int)$a['angka_nama'] ?><?php endif; ?>
    </p>
    <?php if (!empty($stifin['keterangan'])): ?>
      <p style="margin-top:10px"><?= html_escape($stifin['keterangan']) ?></p>
    <?php endif; ?>
  <?php else: ?>
    <p class="label">Belum bisa dihitung karena tanggal lahir kosong.</p>
  <?php endif; ?>
  <p class="label" style="margin-top:14px">
    Tipe ini diturunkan dari tanggal lahir secara numerologi, bukan dari jawaban tes.
    Jawaban peserta di keempat tes tidak memengaruhinya sama sekali. Peserta tidak melihat bagian ini,
    dan bagian ini tidak dikirim ke analisis AI. Pakai sebagai catatan tambahan, bukan sebagai dasar penilaian.
  </p>
</div>

<!-- ============ RINGKASAN ============ -->
<div class="grid g2" style="margin-top:16px;align-items:start">

  <div class="kartu">
    <h2>DISC</h2>
    <?php if ($disc): ?>
      <p class="label">Tertinggi: <b><?= html_escape($disc['tipe_utama']) ?> &mdash;
        <?= html_escape($disc_nama[$disc['tipe_utama']] ?? '') ?></b></p>
      <?= $batang(
            ['D'=>$disc['net_d'],'I'=>$disc['net_i'],'S'=>$disc['net_s'],'C'=>$disc['net_c']],
            ['D'=>'#E5484D','I'=>'#F2820D','S'=>'#30A46C','C'=>'#3E63DD']) ?>
      <?= $blok_ai($ai($disc)) ?>
    <?php else: ?><p class="label">Belum dikerjakan.</p><?php endif; ?>
  </div>

  

  <div class="kartu">
    <h2>Karakteristik</h2>
    <?php if ($karakteristik): ?>
      <p style="font-size:26px;font-weight:800;margin:4px 0"><?= (int)$karakteristik['persen'] ?>%</p>
      <p class="label"><?= html_escape($karakteristik['kategori']) ?> &middot;
        <?= (int)$karakteristik['skor'] ?> dari <?= (int)$karakteristik['skor_maks'] ?> poin</p>
      <?= $batang_label([
            ['Integritas',     (int)$karakteristik['skor_integritas'],  20, '#4520A6'],
            ['Tanggung jawab', (int)$karakteristik['skor_tanggungjwb'], 15, '#3E63DD'],
            ['Kerja sama',     (int)$karakteristik['skor_kerjasama'],   15, '#30A46C'],
          ]) ?>
      <p class="label" style="margin-top:10px">Skor tinggi menunjukkan kesadaran akan standar yang diharapkan, bukan bukti integritas. Skor rendah lebih bermakna.</p>
      <?= $blok_ai($ai($karakteristik)) ?>
    <?php else: ?><p class="label">Belum dikerjakan.</p><?php endif; ?>
  </div>

  <div class="kartu">
    <h2>Test IQ</h2>
    <?php if ($aptitude): ?>
      <p style="font-size:26px;font-weight:800;margin:4px 0">
        <?= (int)$aptitude['benar'] ?>/<?= (int)$aptitude['total_soal'] ?>
      </p>
      <p class="label">Estimasi <?= (int)$aptitude['iq'] ?> &middot; <?= html_escape($aptitude['kategori_iq']) ?>
        <?php if ($aptitude['durasi_detik']): ?>
          &middot; <?= round($aptitude['durasi_detik'] / 60) ?> menit
        <?php endif; ?>
      </p>
      <?= $batang_label([
            ['Numerik', (int)$aptitude['benar_numerik'], 8, '#3E63DD'],
            ['Verbal',  (int)$aptitude['benar_verbal'],  8, '#8B5CF6'],
            ['Logika',  (int)$aptitude['benar_logika'],  8, '#F2820D'],
            ['Spasial', (int)$aptitude['benar_spasial'], 6, '#30A46C'],
          ]) ?>
      <p class="label" style="margin-top:10px">Estimasi internal dari 30 soal tanpa kelompok norma pembanding. Bukan tes IQ resmi. Selisih antar kategori lebih berguna daripada angka totalnya.</p>
      <?= $blok_ai($ai($aptitude)) ?>
    <?php else: ?><p class="label">Belum dikerjakan.</p><?php endif; ?>
  </div>
</div>

<!-- ============ GABUNGAN ============ -->
<?php $g = $ai($a); ?>
<?php if (!empty($g['summary'])): ?>
  <div class="kartu" style="margin-top:16px;border-left:5px solid #4520A6">
    <h2>Gambaran Menyeluruh</h2>
    <?= $blok_ai($g, '') ?>
    <?php if (!empty($g['catatan_hrd'])): ?>
      <div style="margin-top:18px;padding:14px 16px;border-radius:11px;background:#F6F4FF">
        <p class="label" style="margin:0 0 6px"><b>Catatan untuk atasan</b></p>
        <p style="margin:0"><?= html_escape($g['catatan_hrd']) ?></p>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<p class="label" style="margin-top:16px">
  Hasil asesmen menggambarkan kecenderungan gaya kerja pada saat pengisian. Pakai sebagai bahan diskusi
  pengembangan, bukan sebagai penilaian akhir atas kelayakan seseorang.
</p>

<script>
/* Dua frame jeda supaya browser sempat menggambar keadaan awal.
   Tanpa itu transisinya dilewati dan batangnya langsung penuh. */
(function () {
  requestAnimationFrame(function () {
    requestAnimationFrame(function () {
      document.body.classList.add('as-hrd-siap');
    });
  });
})();
</script>
