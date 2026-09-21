<?php
$judul_tes = [
  'disc' => 'Test DISC', 'bigfive' => 'Test Big Five',
  'karakteristik' => 'Test Karakteristik', 'aptitude' => 'Test IQ',
];

/* ---------- siapkan batang nilai per jenis ---------- */
$batang = [];
$ringkas = '';

if ($jenis === 'disc') {
    $w = ['D' => '#E5484D', 'I' => '#F2820D', 'S' => '#30A46C', 'C' => '#3E63DD'];
    $n = ['D' => 'Dominance', 'I' => 'Influence', 'S' => 'Steadiness', 'C' => 'Compliance'];
    $ket = ['D' => 'Tegas, cepat memutuskan', 'I' => 'Ekspresif, membangun relasi',
            'S' => 'Sabar, menjaga stabilitas', 'C' => 'Teliti, berbasis data'];
    foreach (['D','I','S','C'] as $k) {
        $batang[] = ['kode' => $k, 'nama' => $n[$k], 'ket' => $ket[$k], 'warna' => $w[$k],
                     'nilai' => (int)$d['net_' . strtolower($k)],
                     'poin'  => (int)$d['most_' . strtolower($k)] . ' dari 10 pilihan'];
    }
} elseif ($jenis === 'bigfive') {
    $w = ['O' => '#8B5CF6', 'C' => '#3E63DD', 'E' => '#F2820D', 'A' => '#30A46C', 'N' => '#E5484D'];
    $n = ['O' => 'Keterbukaan', 'C' => 'Kehati-hatian', 'E' => 'Ekstraversi',
          'A' => 'Keramahan', 'N' => 'Kepekaan Emosi'];
    $ket = ['O' => 'Terbuka pada ide dan hal baru', 'C' => 'Terencana dan disiplin',
            'E' => 'Mendapat energi dari interaksi', 'A' => 'Kooperatif dan empatik',
            'N' => 'Peka terhadap tekanan dan risiko'];
    foreach (['O','C','E','A','N'] as $k) {
        $batang[] = ['kode' => $k, 'nama' => $n[$k], 'ket' => $ket[$k], 'warna' => $w[$k],
                     'nilai' => (int)$d['persen_' . strtolower($k)],
                     'poin'  => (int)$d['skor_' . strtolower($k)] . ' dari 10 pilihan'];
    }
} elseif ($jenis === 'karakteristik') {
    $ringkas = $d['skor'] . ' dari ' . $d['skor_maks'] . ' poin';
    $aspek = [
      ['I', 'Integritas',     'Kejujuran saat tidak ada yang mengawasi', '#4520A6', (int)$d['skor_integritas']],
      ['T', 'Tanggung jawab', 'Menepati komitmen dan menuntaskan',        '#3E63DD', (int)$d['skor_tanggungjwb']],
      ['K', 'Kerja sama',     'Mendahulukan tim di atas diri sendiri',    '#30A46C', (int)$d['skor_kerjasama']],
    ];
    foreach ($aspek as $a2) {
        $maks = 25;   // 5 soal x bobot 5, kira-kira
        $batang[] = ['kode' => $a2[0], 'nama' => $a2[1], 'ket' => $a2[2], 'warna' => $a2[3],
                     'nilai' => min(100, (int)round($a2[4] / $maks * 100)),
                     'poin'  => $a2[4] . ' poin'];
    }
} else {
    $ringkas = $d['benar'] . ' benar dari ' . $d['total_soal'] . ' soal';
    $kat = [
      ['N', 'Numerik', 'Deret, hitungan, dan pola angka',        '#3E63DD', (int)$d['benar_numerik'], 8],
      ['V', 'Verbal',  'Makna kata, analogi, dan penalaran teks', '#8B5CF6', (int)$d['benar_verbal'],  8],
      ['L', 'Logika',  'Silogisme dan penarikan kesimpulan',      '#F2820D', (int)$d['benar_logika'],  8],
      ['S', 'Spasial', 'Bentuk, ruang, dan bayangan',             '#30A46C', (int)$d['benar_spasial'], 6],
    ];
    foreach ($kat as $c) {
        $batang[] = ['kode' => $c[0], 'nama' => $c[1], 'ket' => $c[2], 'warna' => $c[3],
                     'nilai' => (int)round($c[4] / $c[5] * 100),
                     'poin'  => $c[4] . ' dari ' . $c[5] . ' benar'];
    }
}

/* seri? jangan mengklaim ada yang dominan */
$tertinggi = -1; $sama = [];
foreach ($batang as $b) {
    if ($b['nilai'] > $tertinggi) { $tertinggi = $b['nilai']; $sama = [$b['kode']]; }
    elseif ($b['nilai'] === $tertinggi) $sama[] = $b['kode'];
}
$nama_puncak = '';
foreach ($batang as $b) if ($b['kode'] === $sama[0]) $nama_puncak = $b['nama'];
?>
<link rel="stylesheet" href="<?= base_url('assets/css/asesmen.css') ?>?v=13">

<div class="as">
  <div class="as-kepala">
    <p class="as-label">Tahap <?= $nomor ?> dari <?= $dari ?> selesai</p>
    <h1>Hasil <?= html_escape($judul_tes[$jenis]) ?></h1>
    <div class="as-jalur"><span style="width:<?= (int)round($nomor / $dari * 100) ?>%"></span></div>
  </div>

  <!-- ===== ANGKA UTAMA ===== -->
  <div class="as-kartu as-tengah">
    <?php if ($jenis === 'aptitude'): ?>
      <div class="as-tile" style="background:#4520A6"><?= (int)$d['benar'] ?></div>
      <h2 class="as-utama-nama">Estimasi skor <?= (int)$d['iq'] ?></h2>
      <p class="as-utama-ket"><?= html_escape($d['kategori_iq']) ?> &middot; <?= html_escape($ringkas) ?></p>
    <?php elseif ($jenis === 'karakteristik'): ?>
      <div class="as-tile" style="background:#4520A6"><?= (int)$d['persen'] ?>%</div>
      <h2 class="as-utama-nama"><?= html_escape($d['kategori']) ?></h2>
      <p class="as-utama-ket"><?= html_escape($ringkas) ?></p>
    <?php else: ?>
      <?php $warna_puncak = '#4520A6';
            foreach ($batang as $b) if ($b['kode'] === $sama[0]) $warna_puncak = $b['warna']; ?>
      <div class="as-tile" style="background:<?= $warna_puncak ?>"><?= implode('/', $sama) ?></div>
      <?php if (count($sama) > 1): ?>
        <h2 class="as-utama-nama">Seimbang</h2>
        <p class="as-utama-ket">Beberapa dimensi sama tinggi, tidak ada yang menonjol sendiri</p>
      <?php else: ?>
        <h2 class="as-utama-nama"><?= html_escape($nama_puncak) ?></h2>
        <p class="as-utama-ket">Dimensi yang paling sering Anda pilih</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- ===== RINCIAN ===== -->
  <div class="as-kartu" id="as-batang">
    <h2 class="as-h2">Rincian nilai</h2>
    <?php foreach ($batang as $b): $puncak = in_array($b['kode'], $sama, TRUE); ?>
      <div class="as-dim<?= $puncak ? ' as-dim-puncak' : '' ?>"<?= $puncak ? ' style="border-color:' . $b['warna'] . '"' : '' ?>>
        <div class="as-dim-atas">
          <span class="as-kode" style="background:<?= $b['warna'] ?>"><?= $b['kode'] ?></span>
          <div class="as-dim-teks">
            <p class="as-dim-nama"><?= html_escape($b['nama']) ?></p>
            <p class="as-dim-ket"><?= html_escape($b['ket']) ?></p>
          </div>
          <span class="as-dim-angka"><?= $b['nilai'] ?>%</span>
        </div>
        <div class="as-jalur2">
          <span class="as-isi" style="--w:<?= max(2, min(100, $b['nilai'])) ?>%;background:<?= $b['warna'] ?>"></span>
        </div>
        <p class="as-poin"><?= html_escape($b['poin']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- ===== NARASI AI, dimuat terpisah ===== -->
  <div id="as-ai-wadah">
    <div class="as-kartu">
      <p class="as-sub as-memuat">Menyusun penjelasan&hellip;</p>
    </div>
  </div>

  <!-- ===== LANJUT ===== -->
  <div class="as-kartu as-tengah">
    <?php if ($berikut): ?>
      <p class="as-sub" style="margin-bottom:16px">Masih ada <?= $dari - $nomor ?> test lagi.</p>
      <a class="as-btn as-btn-utama" href="<?= base_url('asesmen/tes/' . $a['kode'] . '/' . $berikut) ?>">Lanjut ke test berikutnya</a>
    <?php else: ?>
      <p class="as-sub" style="margin-bottom:16px">Semua test selesai.</p>
      <a class="as-btn as-btn-utama" href="<?= base_url('asesmen/selesai/' . $a['kode']) ?>">Lihat hasil lengkap</a>
    <?php endif; ?>
    <p class="as-nota">Simpan tautan halaman ini kalau ingin melanjutkan nanti dari perangkat lain.</p>
  </div>
</div>

<script>
(function () {
  var w = document.getElementById('as-batang');
  if (w) {
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { w.classList.add('as-siap'); });
    });
  }

  var wadah = document.getElementById('as-ai-wadah');
  if (!wadah) return;
  var url = <?= json_encode(base_url('asesmen/narasi/' . $a['kode'] . '/' . $jenis)) ?>;

  function aman(t) {
    var e = document.createElement('div');
    e.textContent = String(t == null ? '' : t);
    return e.innerHTML;
  }

  function daftar(judul, isi, kelas) {
    if (!isi || !isi.length) return '';
    var h = '<h3 class="as-h3 ' + (kelas || '') + '">' + judul + '</h3><ul class="as-daftar ' + (kelas || '') + '">';
    isi.forEach(function (t) { h += '<li>' + aman(t) + '</li>'; });
    return h + '</ul>';
  }

  function alinea(judul, teks) {
    if (!teks) return '';
    return '<h3 class="as-h3">' + judul + '</h3><p>' + aman(teks) + '</p>';
  }

  function tips(isi) {
    if (!isi || !isi.length) return '';
    var h = '<h3 class="as-h3">Langkah yang bisa dicoba</h3>';
    isi.forEach(function (t, i) {
      h += '<div class="as-tip"><span class="as-tip-no">' + (i + 1) + '</span><span>' + aman(t) + '</span></div>';
    });
    return h;
  }

  fetch(url, { credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .then(function (j) {
      if (!j.ok || !j.ai || !j.ai.summary) throw new Error('kosong');
      var a = j.ai, h = '';

      h += '<div class="as-kartu">';
      if (a.headline) h += '<p class="as-headline">' + aman(a.headline) + '</p>';
      h += '<p>' + aman(a.summary) + '</p>';
      h += daftar('Ciri khas Anda', a.traits);
      h += daftar('Kekuatan', a.strengths, 'as-hijau');
      h += daftar('Yang perlu dikembangkan', a.development_areas, 'as-jingga');
      h += '</div>';

      if (a.work_style || a.cocok_di_peran || a.kehidupan_sehari) {
        h += '<div class="as-kartu">';
        h += alinea('Gaya kerja', a.work_style);
        h += alinea('Cocok di peran', a.cocok_di_peran);
        h += alinea('Sehari-hari', a.kehidupan_sehari);
        h += '</div>';
      }

      if (a.tips && a.tips.length) h += '<div class="as-kartu">' + tips(a.tips) + '</div>';

      wadah.innerHTML = h;
    })
    .catch(function () {
      wadah.innerHTML = '<div class="as-kartu"><p class="as-sub">Penjelasan belum bisa disusun sekarang. '
        + 'Skor Anda sudah tersimpan, jadi bagian ini bisa dilihat lagi nanti.</p></div>';
    });
})();
</script>
