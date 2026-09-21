<?php
$nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
               7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$nama_hari  = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'];

$tulis = function ($t) use ($nama_bulan, $nama_hari) {
    $w = strtotime($t);
    return $nama_hari[(int)date('N', $w)] . ', ' . (int)date('j', $w)
         . ' ' . $nama_bulan[(int)date('n', $w)] . ' ' . date('Y', $w);
};
$tulis_pendek = function ($t) use ($nama_bulan) {
    $w = strtotime($t);
    return (int)date('j', $w) . ' ' . substr($nama_bulan[(int)date('n', $w)], 0, 3);
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Belum Absen</title>
<style>
:root { --garis:#E9E7F2; --redup:#6B6B7B; --utama:#4520A6; }
* { box-sizing:border-box; }
body { margin:0; padding:24px 16px 60px; background:#F7F6FB; color:#1A1A2E;
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }
.bungkus { max-width:940px; margin:0 auto; }
.kartu { background:#fff; border:1px solid var(--garis); border-radius:14px;
  padding:22px 24px; margin-bottom:18px; }
h1 { font-size:26px; margin:0 0 6px; }
h2 { font-size:18px; margin:0 0 4px; }
.label { color:var(--redup); font-size:13px; margin:0; }
.balik { display:inline-block; padding:10px 18px; border:1.5px solid var(--garis);
  border-radius:10px; background:#fff; text-decoration:none; color:var(--utama);
  font-weight:700; font-size:14px; margin-bottom:18px; }
.alat { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; margin-top:16px; }
.alat label { font-size:12px; font-weight:700; color:var(--redup); display:block; }
.alat input, .alat select { display:block; margin-top:5px; padding:9px 11px;
  border:1.5px solid var(--garis); border-radius:9px; font-size:14px; font-family:inherit; }
.baris { display:flex; justify-content:space-between; align-items:center; gap:14px;
  padding:14px 16px; border-radius:11px; background:#FAFAFD; margin-bottom:10px; }
.baris b { display:block; font-size:15px; }
.baris small { color:var(--redup); }
.tanda { padding:4px 11px; border-radius:99px; font-size:12px; font-weight:700; white-space:nowrap; }
.t-belum { background:#FDECEC; color:#9B1C1C; }
.t-wajah { background:#FFF4E6; color:#B45309; }
.t-alat  { background:#EFEBFB; color:#4520A6; }
.t-cuti  { background:#E7F7ED; color:#146C43; }
table { width:100%; border-collapse:collapse; font-size:14px; }
th { text-align:left; font-size:12px; text-transform:uppercase; letter-spacing:.4px;
  color:var(--redup); padding:0 10px 10px; border-bottom:2px solid var(--garis); }
td { padding:11px 10px; border-bottom:1px solid var(--garis); vertical-align:middle; }
td.angka, th.angka { text-align:center; }
.pil { display:inline-block; min-width:26px; padding:3px 9px; border-radius:99px;
  font-size:12px; font-weight:700; }
.p-nol   { background:#E7F7ED; color:#146C43; }
.p-satu  { background:#FFF4E6; color:#B45309; }
.p-banyak{ background:#FDECEC; color:#9B1C1C; }
.tgl-kecil { color:var(--redup); font-size:12px; margin-top:3px; }
.kosong { text-align:center; padding:30px 10px; color:var(--redup); }
.catatan { color:var(--redup); font-size:13px; text-align:center; margin-top:16px; line-height:1.6; }
.libur { background:#FFF4E6; border:1px solid #FFD591; color:#874D00;
  padding:12px 14px; border-radius:10px; margin-top:14px; font-size:14px; }
</style>
  <!-- Halaman ini berdiri sendiri, tidak memakai TemplateDashboard,
       jadi ikon tabnya harus dipasang sendiri. -->
  <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>assets/img/fav.png">
</head>
<body>
<div class="bungkus">

  <a class="balik" href="<?= base_url('attendance') ?>">&larr; Kembali ke Attendance Management</a>

  <div class="kartu">
    <h1>Belum Absen</h1>
    <p class="label"><?= html_escape($tulis($tgl)) ?></p>

    <form method="get" action="<?= base_url('attendance/belum') ?>" class="alat">
      <label>Lihat tanggal
        <input type="date" name="tgl" value="<?= html_escape($tgl) ?>"
               max="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
      </label>
      <label>Rekap bulan
        <input type="month" name="bulan" value="<?= html_escape($bulan) ?>"
               max="<?= date('Y-m') ?>" onchange="this.form.submit()">
      </label>
      <noscript><button type="submit">Tampilkan</button></noscript>
    </form>

    <?php if (!empty($libur_hari)): ?>
      <div class="libur">
        Tanggal ini hari libur &mdash; <b><?= html_escape($libur_hari) ?></b>.
        Tidak ada yang dihitung tidak hadir.
      </div>
    <?php endif; ?>
  </div>

  <?php if (empty($libur_hari)): ?>
  <div class="kartu">
    <h2><?= count($rows) ?> orang belum absen masuk</h2>
    <p class="label" style="margin-bottom:16px">Pada <?= html_escape($tulis($tgl)) ?></p>

    <?php if (empty($rows)): ?>
      <div class="kosong">Semua karyawan sudah absen masuk pada tanggal ini.</div>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <div class="baris">
          <div>
            <b><?= html_escape($r['full_name']) ?></b>
            <small><?= html_escape($r['position_name'] ?: ($r['role_text'] ?: '-')) ?></small>
          </div>
          <div style="display:flex;gap:10px;align-items:center;white-space:nowrap">
            <?php if ((int)$r['sedang_cuti'] > 0): ?>
              <span class="tanda t-cuti">Izin disetujui</span>
            <?php elseif ((int)$r['ada_wajah'] === 0): ?>
              <span class="tanda t-wajah">Wajah belum didaftarkan</span>
            <?php elseif ((int)$r['ada_device'] === 0): ?>
              <span class="tanda t-alat">Perangkat belum disetujui</span>
            <?php else: ?>
              <span class="tanda t-belum">Belum absen</span>
            <?php endif; ?>
            <small>jadwal <?= html_escape(substr($r['jam_masuk'] ?: '08:00:00', 0, 5)) ?><?php
                if (!empty($r['shift_live'])) {
                    echo ' (Shift ' . (int)$r['shift_live']
                       . (empty($r['shift_pasti']) ? ', sementara' : '') . ')';
                }
            ?></small>
            <button type="button" class="btn-isi-absen"
                    data-uid="<?= (int)$r['id'] ?>"
                    data-nama="<?= html_escape($r['full_name']) ?>"
                    data-jam="<?= html_escape(substr($r['jam_masuk'] ?: '08:00:00', 0, 5)) ?>"
                    style="border:1px solid #1F4696;background:#fff;color:#1F4696;
                           border-radius:8px;padding:5px 12px;font-size:.78rem;
                           font-weight:600;cursor:pointer">+ Isi absen</button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="kartu">
    <?php
      $b = strtotime($bulan . '-01');
      $total_bolos = 0;
      foreach ($rekap as $x) { $total_bolos += $x['bolos']; }
    ?>
    <h2>Rekap <?= $nama_bulan[(int)date('n', $b)] ?> <?= date('Y', $b) ?></h2>
    <p class="label" style="margin-bottom:16px">
      Dihitung sampai <?= html_escape($tulis($akhir_rekap)) ?>.
      Hari libur dan hari yang bukan hari kerjanya tidak dihitung.
    </p>

    <?php if (empty($rekap)): ?>
      <div class="kosong">Belum ada data untuk bulan ini.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Nama</th>
            <th class="angka">Hari kerja</th>
            <th class="angka">Hadir</th>
            <th class="angka">Izin</th>
            <th class="angka">Tidak absen</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rekap as $x): ?>
          <tr>
            <td>
              <b><?= html_escape($x['nama']) ?></b><br>
              <span class="tgl-kecil"><?= html_escape($x['posisi']) ?></span>
              <?php if (!empty($x['tanggal_bolos'])): ?>
                <div class="tgl-kecil">
                  <?php
                    $daftar_tgl = array_map($tulis_pendek, array_slice($x['tanggal_bolos'], 0, 8));
                    echo html_escape(implode(', ', $daftar_tgl));
                    if (count($x['tanggal_bolos']) > 8) {
                        echo ' +' . (count($x['tanggal_bolos']) - 8) . ' lagi';
                    }
                  ?>
                </div>
              <?php endif; ?>
            </td>
            <td class="angka"><?= (int)$x['hari_kerja'] ?></td>
            <td class="angka"><?= (int)$x['hadir'] ?></td>
            <td class="angka"><?= (int)$x['izin'] ?: '-' ?></td>
            <td class="angka">
              <span class="pil <?= $x['bolos'] === 0 ? 'p-nol' : ($x['bolos'] === 1 ? 'p-satu' : 'p-banyak') ?>">
                <?= (int)$x['bolos'] ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <p class="catatan">
        Total <?= (int)$total_bolos ?> hari tidak absen dari <?= count($rekap) ?> karyawan.
        Yang paling sering ada di urutan atas.
      </p>
    <?php endif; ?>
  </div>

  <p class="catatan">
    Yang bertanda "Wajah belum didaftarkan" atau "Perangkat belum disetujui" tidak akan
    bisa absen sampai hal itu diselesaikan, bukan karena mereka tidak hadir.<br>
    Tanggal merah diatur di <a href="<?= base_url('hrd/libur') ?>">halaman Hari Libur</a>.
  </p>

</div>
</body>
</html>

<!-- SweetAlert dipakai kotak isian di bawah; halaman ini berdiri sendiri
     dan tidak memuatnya dari template, jadi diimpor di sini. -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Isi absen untuk orang yang benar hadir tapi tidak sempat menekan tombol.
// Tidak mengirim apa pun ke grup WhatsApp: notifikasi di sana menandakan
// seseorang baru saja absen, dan itu tidak terjadi di sini.
document.addEventListener('click', function (e) {
  var b = e.target.closest('.btn-isi-absen');
  if (!b) return;

  var uid  = b.dataset.uid;
  var nama = b.dataset.nama;
  var jam  = b.dataset.jam || '08:00';
  var tgl  = new URLSearchParams(location.search).get('tgl')
             || new Date().toISOString().slice(0, 10);

  Swal.fire({
    title: 'Isi absen - ' + nama,
    html:
      '<div style="text-align:left;font-size:.86rem">'
      + '<div style="font-size:.78rem;color:#64748b;margin-bottom:10px">Tanggal ' + tgl + '</div>'
      + '<label>Jam masuk</label>'
      + '<input type="time" id="m_masuk" class="swal2-input" style="width:100%;margin:4px 0 10px" value="' + jam + '">'
      + '<label>Jam pulang</label>'
      + '<input type="time" id="m_pulang" class="swal2-input" style="width:100%;margin:4px 0 10px">'
      + '<label>Alasan</label>'
      + '<input type="text" id="m_alasan" class="swal2-input" style="width:100%;margin:4px 0 4px" placeholder="mis. akun terkunci, HP bermasalah">'
      + '<div style="font-size:.75rem;color:#8c8c8c;margin-top:6px">Kosongkan jam pulang kalau orangnya masih bekerja - dia tetap bisa absen pulang sendiri nanti.</div>'
      + '</div>',
    showCancelButton: true, confirmButtonText: 'Simpan', cancelButtonText: 'Batal',
    confirmButtonColor: '#1F4696',
    preConfirm: function () {
      var alasan = document.getElementById('m_alasan').value.trim();
      if (!alasan) { Swal.showValidationMessage('Alasan wajib diisi.'); return false; }
      return {
        masuk:  document.getElementById('m_masuk').value,
        pulang: document.getElementById('m_pulang').value,
        alasan: alasan
      };
    }
  }).then(function (r) {
    if (!r.isConfirmed) return;
    var fd = new FormData();
    fd.append('user_id', uid);
    fd.append('tanggal', tgl);
    fd.append('masuk',  r.value.masuk);
    fd.append('pulang', r.value.pulang);
    fd.append('alasan', r.value.alasan);

    fetch('<?= base_url("attendance/tambah_absen_manual") ?>',
          { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (x) { return x.json(); })
      .then(function (o) {
        if (o.success) {
          Swal.fire('Tersimpan', o.message, 'success').then(function () { location.reload(); });
        } else {
          Swal.fire('Gagal', o.message, 'error');
        }
      })
      .catch(function () { Swal.fire('Gagal', 'Kesalahan jaringan.', 'error'); });
  });
});
</script>
