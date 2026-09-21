<?php
/**
 * Halaman pengerjaan tes, satu soal per layar.
 *
 * Semua soal tetap dimuat sekaligus ke halaman lalu disembunyikan, dan
 * yang dikirim ke server tetap field j_<nomor> seperti sebelumnya.
 * Dengan begitu jawaban tidak perlu bolak-balik ke server tiap klik:
 * sinyal yang putus di tengah tidak membuat pengerjaan hilang, dan
 * penilaian di sisi server tidak berubah sedikit pun.
 */
?>
<?php
// Penanda pilihan ditulis di sini, bukan dikirim dari controller.
// Sebelumnya variabel $huruf tidak pernah ada sehingga tampilannya
// jatuh ke nomor urut, dan peserta melihat 1 2 3 4 alih-alih A B C D.
$HURUF_PILIHAN = ['A','B','C','D','E','F'];
?>
<link rel="stylesheet" href="<?= base_url('assets/css/asesmen.css') ?>?v=12">

<style>
  /* Teks soal tidak bisa disalin: menyalin pertanyaan ke AI lalu
     menempel jawabannya adalah cara mencontek yang paling mudah di
     tes daring. Ini tidak menutup semua jalan -- orang masih bisa
     mengetik ulang atau memotret layar -- tetapi menaikkan usahanya
     cukup jauh untuk tes sepanjang ini. Tombol jawaban tetap bisa
     diklik seperti biasa. */
  .as-soal, .as-situasi, .as-opsi, .as-kartu, .as-nomor {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    -webkit-touch-callout: none;
  }
  input[type="radio"], button, label { -webkit-user-select: none; user-select: none; }

/* Satu soal per layar. Soal lain tetap ada di halaman tapi disembunyikan. */
/* Semua soal ditampilkan berurutan ke bawah, seperti tes DISC pada
   umumnya. Peserta menggulir dan bisa membaca ulang jawabannya. */
.as-soal { display: block; }
.as-nav { display: none; }
.as-peta { display: none; }

.as-lacak {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 10px; font-size: 13px; color: #6B7280; font-weight: 600;
}
.as-jalur { height: 7px; border-radius: 99px; background: #ECEAF6; overflow: hidden; }
.as-jalur span {
  display: block; height: 100%; border-radius: 99px; background: #4520A6;
  width: 0; transition: width .25s ease-out;
}

.as-nav { display: flex; gap: 10px; margin-top: 18px; }
.as-nav .as-btn { flex: 1; }
.as-btn[disabled] { opacity: .45; cursor: not-allowed; }

/* Pilihan yang sedang dipilih diberi penanda jelas, karena di layar
   sempit lingkaran radio saja mudah terlewat. */
.as-pilih { cursor: pointer; }
/* Soal yang terlewat diberi garis merah saat tombol Selesai ditekan. */

.as-info { padding-bottom: 6px; }
.as-info-judul {
  margin: 0 0 14px; font-size: 21px; font-weight: 800; color: #1F2937;
  padding-bottom: 12px; border-bottom: 2px solid #EFEDF7;
}
.as-info-baris {
  display: flex; align-items: center; justify-content: space-between;
  padding: 9px 0; border-bottom: 1px solid #F4F2FB; font-size: 14px;
}
.as-info-baris:last-of-type { border-bottom: 0; }
.as-info-baris span { color: #6B7280; }
.as-info-baris b { color: #111827; text-align: right; }
.as-info-nota {
  margin-top: 12px; padding-top: 12px; border-top: 1px solid #F4F2FB;
}


/* Kartu kepala. Bagian atas berwarna biru Prepare, bagian bawah putih,
   keduanya menyatu dalam satu kartu supaya nama tes dan identitas
   peserta terbaca sebagai satu kesatuan. */
.as-hero {
  border-radius: 14px; overflow: hidden; margin-bottom: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,.07); background: #fff;
}
.as-hero-atas {
  background: linear-gradient(135deg, #1F4696 0%, #2D5FC4 100%);
  color: #fff; padding: 26px 20px 22px; text-align: center;
}
.as-hero-label {
  margin: 0 0 6px; font-size: 11px; font-weight: 800;
  letter-spacing: .12em; text-transform: uppercase; opacity: .75;
}
.as-hero-judul { margin: 0; font-size: 27px; font-weight: 800; line-height: 1.2; }
.as-hero-ringkas {
  margin: 10px auto 0; font-size: 13.5px; line-height: 1.65;
  opacity: .92; max-width: 100%;
  /* Tanpa ini kalimatnya patah di tengah tanda kurung, misalnya
     "S (Steadiness), C" lalu "(Compliance)." turun sendiri. */
  text-wrap: balance;
}
.as-hero-bawah { padding: 6px 20px 18px; }
.as-hero-baris {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 0; border-bottom: 1px solid #F1EFFA; font-size: 14px;
}
.as-hero-baris span { color: #6B7280; }
.as-hero-baris b { color: #111827; text-align: right; }
.as-hero-petunjuk { margin-top: 14px; }
.as-hero-pj {
  margin: 0 0 7px; font-size: 12px; font-weight: 800;
  letter-spacing: .05em; text-transform: uppercase; color: #1F4696;
}
.as-hero-petunjuk ul { margin: 0; padding-left: 18px; }
.as-hero-petunjuk li {
  font-size: 13px; color: #4B5563; line-height: 1.7; margin-bottom: 3px;
}


/* Kartu penutup sempat ikut aturan .as-soal sehingga menempel di
   tengah daftar soal. Posisinya dikembalikan ke aliran biasa supaya
   selalu berada di paling bawah. */
.as-kirim {
  position: static !important;
  display: block !important;
  margin-top: 18px;
  text-align: center;
}
.as-kirim .as-btn { width: 100%; margin-top: 10px; }

.as-soal.as-kosong {
  border: 2px solid #E5484D;
  box-shadow: 0 0 0 4px rgba(229,72,77,.10);
}
.as-pilih.as-terpilih {
  border-color: #4520A6;
  background: #F6F4FF;
  box-shadow: 0 0 0 1px #4520A6 inset;
}

.as-peta {
  display: flex; flex-wrap: wrap; gap: 6px; margin-top: 16px;
  padding-top: 16px; border-top: 1px solid #EFEDF7;
}
.as-peta button {
  width: 30px; height: 30px; border-radius: 8px; cursor: pointer;
  border: 1.5px solid #E3E1F0; background: #fff;
  font-size: 12px; font-weight: 700; color: #6B7280; font-family: inherit;
}
.as-peta button.sudah { background: #4520A6; border-color: #4520A6; color: #fff; }
.as-peta button.aktif { box-shadow: 0 0 0 2px #C7B9F5; }
.as-peta-ket { font-size: 12px; color: #9CA3AF; margin-top: 10px; }
</style>

<div class="as">
<?php
/**
 * Keterangan tiap tes ditulis di sini, bukan di controller, supaya
 * penjelasan yang dibaca peserta bisa disesuaikan tanpa menyentuh
 * logika penilaian.
 */
$INFO_TES = [
    'disc' => [
        'ringkas'  => 'Ukur gaya kepribadian D (Dominance), I (Influence), '
                    . 'S (Steadiness), C (Compliance).',
        'petunjuk' => [
            'Ada 40 pertanyaan yang harus dijawab',
            'Pilih jawaban yang paling menggambarkan diri Anda dalam situasi yang diberikan',
            'Jawab dengan jujur untuk hasil yang akurat',
            'Setiap pertanyaan memiliki 4 pilihan jawaban (D, I, S, C)',
        ],
    ],
    'karakteristik' => [
        'ringkas'  => 'Analisis karakter dan perilaku kerja berdasarkan '
                    . '30 pertanyaan pilihan ganda.',
        'petunjuk' => [
            'Ada 30 pertanyaan yang harus dijawab',
            'Pilih tindakan yang paling mendekati apa yang benar-benar Anda lakukan',
            'Tidak ada jawaban yang mustahil dipilih, semuanya masuk akal',
            'Setiap pertanyaan memiliki 5 pilihan jawaban (A sampai E)',
        ],
    ],
    'aptitude' => [
        'ringkas'  => 'Mengukur kemampuan numerik, verbal, logika, dan spasial.',
        'petunjuk' => [
            'Ada 30 pertanyaan yang harus dijawab',
            'Setiap pertanyaan hanya punya satu jawaban yang benar',
            'Kerjakan tanpa terburu-buru, tidak ada batas waktu',
            'Setiap pertanyaan memiliki 4 pilihan jawaban (A sampai D)',
        ],
    ],
];
$info = $INFO_TES[$jenis] ?? NULL;
?>

  <div class="as-hero">
    <div class="as-hero-atas">
      <p class="as-hero-label">Asesmen Karyawan</p>
      <?php
        // Judul diambil dari daftar di atas kalau controller tidak
        // mengirim $judul_tes, supaya nama tesnya tidak pernah kosong.
        $NAMA_TES = ['disc' => 'Test DISC',
                     'karakteristik' => 'Test Karakteristik',
                     'aptitude' => 'Test IQ'];
        $tampil = !empty($judul_tes) ? $judul_tes : ($NAMA_TES[$jenis] ?? 'Asesmen');
      ?>
      <h1 class="as-hero-judul"><?= html_escape($tampil) ?></h1>
      <?php if ($info): ?>
        <p class="as-hero-ringkas"><?= html_escape($info['ringkas']) ?></p>
      <?php endif; ?>
    </div>

    <div class="as-hero-bawah">
      <div class="as-hero-baris">
        <span>Nama</span><b><?= html_escape($a['nama']) ?></b>
      </div>
      <div class="as-hero-baris">
        <span>Divisi</span><b><?= html_escape($a['divisi'] ?: '-') ?></b>
      </div>
      <div class="as-hero-baris">
        <span>Jumlah soal</span><b><?= count($soal) ?> soal</b>
      </div>

      <?php if ($info): ?>
        <div class="as-hero-petunjuk">
          <p class="as-hero-pj">Petunjuk pengerjaan</p>
          <ul>
            <?php foreach ($info['petunjuk'] as $p): ?>
              <li><?= html_escape($p) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>
  </div>



  <div class="as-kartu">
    <div class="as-lacak">
      <span>Soal <b id="as-kini">1</b> dari <?= count($soal) ?></span>
      <span id="as-persen">0%</span>
    </div>
    <div class="as-jalur"><span id="as-isi"></span></div>
  </div>

  <?php if ($p = $this->session->flashdata('gagal')): ?>
    <div class="as-alert as-alert-merah"><?= html_escape($p) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= base_url('asesmen/simpan/' . $a['kode'] . '/' . $jenis) ?>" id="as-form">
    <?php foreach ($soal as $i => $s): $n = (int)$s['nomor']; ?>
      <div class="as-kartu as-soal<?= $i === 0 ? ' as-tampil' : '' ?>" data-nomor="<?= $n ?>">
        <div class="as-soal-kepala">
          <span class="as-angka"><?= $i + 1 ?></span>
          <div>
            <?php if (!empty($s['kategori'])): ?>
              <span class="as-chip"><?= html_escape($s['kategori']) ?></span>
            <?php endif; ?>
            <p class="as-situasi"><?= html_escape($s['situasi']) ?></p>
          </div>
        </div>

        <div class="as-opsi">
          <?php foreach ($s['opsi'] as $k => $o): ?>
            <label class="as-pilih">
              <input type="radio" name="j_<?= $n ?>" value="<?= html_escape($o['nilai']) ?>" required>
              <span class="as-huruf"><?= $HURUF_PILIHAN[$k] ?? ($k + 1) ?></span>
              <span class="as-teks"><?= html_escape($o['teks']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>


      </div>
    <?php endforeach; ?>

    <div class="as-kartu as-kirim">
      <p class="as-hitung"><span id="as-terisi">0</span> dari <?= count($soal) ?> soal terjawab</p>
      <button type="submit" class="as-btn as-btn-utama" id="as-tombol">Selesai &amp; lihat hasil</button>
    </div>
  </form>
</div>

<script>
(function () {
  var form = document.getElementById('as-form');
  if (!form) return;

  var kartu  = Array.prototype.slice.call(form.querySelectorAll('.as-soal'));
  var isi    = document.getElementById('as-isi');
  var persen = document.getElementById('as-persen');
  var kini   = document.getElementById('as-kini');
  var terisi = document.getElementById('as-terisi');

  function perbarui() {
    var j = form.querySelectorAll('input[type=radio]:checked').length;
    var p = kartu.length ? Math.round(j / kartu.length * 100) : 0;
    if (isi)    isi.style.width = p + '%';
    if (persen) persen.textContent = p + '%';
    if (terisi) terisi.textContent = j;
    if (kini)   kini.textContent = Math.min(j + 1, kartu.length);
  }

  form.addEventListener('change', function (e) {
    if (e.target.type !== 'radio') return;
    var kotak = e.target.closest('.as-soal');
    kotak.querySelectorAll('.as-pilih').forEach(function (l) {
      l.classList.toggle('as-terpilih', l.contains(e.target));
    });
    kotak.classList.remove('as-kosong');
    perbarui();
  });

  form.addEventListener('submit', function (e) {
    // Soal yang belum dijawab ditandai lalu digulir ke yang pertama,
    // supaya orang tidak perlu menelusuri sendiri empat puluh soal
    // mencari mana yang terlewat.
    var kurang = kartu.filter(function (k) {
      return !k.querySelector('input:checked');
    });

    if (kurang.length) {
      e.preventDefault();
      kurang.forEach(function (k) { k.classList.add('as-kosong'); });
      kurang[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
      alert('Masih ada ' + kurang.length + ' soal yang belum dijawab. '
          + 'Soal yang terlewat ditandai merah.');
      return;
    }

    var b = document.getElementById('as-tombol');
    b.disabled = true;
    b.textContent = 'Menyimpan...';
  });

  perbarui();
})();
</script>

<script>
// Klik kanan dan Ctrl+C dimatikan di halaman tes. Sama seperti CSS di
// atas: bukan penghalang mutlak, hanya menambah usaha bagi yang ingin
// menyalin soal ke luar.
document.addEventListener('contextmenu', function(e){ e.preventDefault(); });
document.addEventListener('copy', function(e){ e.preventDefault(); });
document.addEventListener('cut', function(e){ e.preventDefault(); });
document.addEventListener('keydown', function(e){
  if ((e.ctrlKey || e.metaKey) && ['c','x','a','u','s','p'].indexOf(e.key.toLowerCase()) !== -1) {
    e.preventDefault();
  }
});
</script>

<script>
// Berapa kali peserta meninggalkan halaman selama mengerjakan. Tidak
// memblokir apa pun -- browser memang tidak bisa mencegah orang membuka
// tab lain, apalagi membuka AI di ponsel terpisah. Yang bisa dilakukan
// hanya mencatatnya, supaya HRD punya satu petunjuk lagi saat menilai,
// bukan menuduh dari perasaan.
(function(){
  var n = 0;
  var medan = document.createElement('input');
  medan.type = 'hidden';
  medan.name = 'pindah_tab';
  medan.value = '0';
  var form = document.querySelector('form');
  if (form) form.appendChild(medan);

  document.addEventListener('visibilitychange', function(){
    if (document.hidden) { n++; medan.value = String(n); }
  });
  window.addEventListener('blur', function(){ n++; medan.value = String(n); });
})();
</script>
