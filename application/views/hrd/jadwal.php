<?php
$nama_hari = [1=>'Sen', 2=>'Sel', 3=>'Rab', 4=>'Kam', 5=>'Jum', 6=>'Sab', 7=>'Min'];

$nama_user = [];
foreach ($karyawan as $k) $nama_user[$k['id']] = $k['full_name'];

$tulis_hari = function ($daftar) use ($nama_hari) {
    if (empty($daftar)) return '-';
    $out = [];
    foreach (explode(',', $daftar) as $h) {
        $h = (int)trim($h);
        if (isset($nama_hari[$h])) $out[] = $nama_hari[$h];
    }
    return $out ? implode(' ', $out) : '-';
};
?>

<style>
.jd-tabel td, .jd-tabel th { vertical-align: middle; }
.jd-tag {
  display: inline-block; padding: 3px 9px; border-radius: 99px;
  font-size: 11px; font-weight: 700;
}
.jd-tag-divisi { background: #EFEBFB; color: #4520A6; }
.jd-tag-orang  { background: #E9F2FE; color: #1F4696; }
.jd-tag-wfh    { background: #FFF4E6; color: #B45309; }
.jd-form { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.jd-form label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 12px; }
.jd-form input, .jd-form select {
  display: block; width: 100%; box-sizing: border-box; margin-top: 6px;
  padding: 9px 11px; border: 1.5px solid #E3E1F0; border-radius: 9px;
  font-size: 14px; font-weight: 400; font-family: inherit;
}
.jd-hari { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 7px; }
.jd-hari span { display: inline-flex; align-items: center; gap: 5px; font-weight: 500; font-size: 13px; }
.jd-hari input { width: auto; margin: 0; }
.jd-penuh { grid-column: 1 / -1; }
@media (max-width: 720px) { .jd-form { grid-template-columns: 1fr; } }
</style>

<?php
$e = $edit_row ?? NULL;
$jh = [];
if ($e && !empty($e['jam_masuk_harian'])) {
    $jh = json_decode($e['jam_masuk_harian'], TRUE) ?: [];
}
$hari_kerja_dipilih = $e ? array_map('trim', explode(',', $e['hari_kerja'])) : ['1','2','3','4','5'];
$hari_wfh_dipilih   = $e && $e['hari_wfh'] ? array_map('trim', explode(',', $e['hari_wfh'])) : [];
?>

<div class="topbar">
  <div>
    <h1>Jadwal Kerja</h1>
    <p class="sub">Karyawan tanpa jadwal khusus memakai jadwal reguler 08:00&ndash;17:00, telat lewat 08:05.</p>
  </div>
</div>

<?php if ($p = $this->session->flashdata('pesan')): ?>
  <div class="kartu" style="border-left:5px solid #3E63DD"><?= html_escape($p) ?></div>
<?php endif; ?>
<?php if ($p = $this->session->flashdata('gagal')): ?>
  <div class="kartu" style="border-left:5px solid #E5484D"><?= html_escape($p) ?></div>
<?php endif; ?>

<!-- ============ DAFTAR ============ -->
<div class="kartu">
  <h2>Jadwal yang berlaku</h2>

  <?php if (!$rows): ?>
    <p class="label">Belum ada jadwal khusus. Semua karyawan memakai jadwal reguler.</p>
  <?php else: ?>
  <div style="overflow-x:auto">
    <table class="jd-tabel">
      <thead>
        <tr>
          <th>Karyawan</th><th>Jadwal</th><th>Masuk</th>
          <th>Telat</th><th>Pulang</th><th>Pulang Sabtu</th><th>Hari kerja</th><th>WFH</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r):
          $batas = date('H:i', strtotime($r['jam_masuk']) + ((int)$r['toleransi_menit'] * 60)); ?>
          <tr>
            <td>
              <?php if ($r['divisi']): ?>
                <span class="jd-tag jd-tag-divisi">Divisi</span><br>
                <b><?= html_escape($r['divisi']) ?></b>
                <div class="label">Berlaku untuk semua orang di divisi ini</div>
              <?php else: ?>
                <span class="jd-tag jd-tag-orang">Perorangan</span><br>
                <b><?= html_escape($nama_user[$r['user_id']] ?? ('User #' . $r['user_id'])) ?></b>
              <?php endif; ?>
            </td>
            <td><?= html_escape($r['nama_jadwal']) ?></td>
            <td><b><?= substr($r['jam_masuk'], 0, 5) ?></b></td>
            <td><?= $batas ?></td>
            <td><?= substr($r['jam_pulang'], 0, 5) ?></td>
            <td><b><?= substr($r['jam_pulang_sabtu'] ?? '12:00:00', 0, 5) ?></b></td>
            <td><?= $tulis_hari($r['hari_kerja']) ?></td>
            <td>
              <?php if ($r['hari_wfh']): ?>
                <span class="jd-tag jd-tag-wfh"><?= $tulis_hari($r['hari_wfh']) ?></span>
              <?php else: ?>-<?php endif; ?>
            </td>
            <td style="white-space:nowrap">
              <a class="btn" href="<?= base_url('hrd/jadwal?edit=' . $r['id']) ?>">Edit</a>
              <a class="btn btn-bahaya" href="<?= base_url('hrd/jadwal_hapus/' . $r['id']) ?>"
                 data-konfirmasi="Hapus jadwal ini? Yang bersangkutan kembali ke jadwal reguler.">Hapus</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ============ FORM ============ -->
<div class="kartu" style="margin-top:16px">
  <h2><?= $e ? 'Ubah jadwal &mdash; ' . html_escape($nama_user[$e['user_id']] ?? $e['divisi']) : 'Tambah jadwal baru' ?></h2>
  <p class="label" style="margin-bottom:18px">
    Satu baris untuk satu karyawan. Yang tidak terdaftar di sini memakai jadwal reguler 08:00&ndash;17:00.
    Untuk host live, isi jam masuk 14:00, jam pulang 18:00, dan pilih "Tanpa istirahat".
  </p>

  <form method="post" action="<?= base_url('hrd/jadwal_simpan') ?>" class="jd-form">
    <?php if ($e): ?><input type="hidden" name="edit_id" value="<?= (int)$e['id'] ?>"><?php endif; ?>

    <label class="jd-penuh">
      Karyawan
      <select name="user_id" required>
        <option value="">— pilih karyawan —</option>
        <?php foreach ($karyawan as $k): ?>
          <option value="<?= (int)$k['id'] ?>" <?= ($e && (int)$e['user_id'] === (int)$k['id']) ? 'selected' : '' ?>>
            <?= html_escape($k['full_name']) ?><?= $k['role_text'] ? ' — ' . html_escape($k['role_text']) : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
      <input type="hidden" name="untuk" value="user">
    </label>

    <label>
      Nama jadwal
      <input type="text" name="nama_jadwal" placeholder="mis. Host Live" value="<?= html_escape($e['nama_jadwal'] ?? 'Reguler') ?>">
    </label>

    <label>
      Jam masuk
      <input type="time" name="jam_masuk" value="<?= $e ? substr($e['jam_masuk'],0,5) : '08:00' ?>" required>
    </label>

    <label>
      Toleransi telat (menit)
      <input type="number" name="toleransi_menit" value="<?= $e ? (int)$e['toleransi_menit'] : 5 ?>" min="0" max="60">
    </label>

    <label>
      Jam pulang
      <input type="time" name="jam_pulang" value="<?= $e ? substr($e['jam_pulang'],0,5) : '17:00' ?>" required>
    </label>

    <label>
      Jam pulang hari Sabtu
      <input type="time" name="jam_pulang_sabtu" value="<?= $e ? substr($e['jam_pulang_sabtu'],0,5) : '12:00' ?>" required>
      <span class="label" style="font-weight:400;display:block;margin-top:5px">
        Sabtu umumnya sampai 12:00. Untuk packing yang tetap sampai 17:00, isi 17:00.
        Kalau pulangnya sebelum jam 13:00, absen istirahat otomatis dilewati.
      </span>
    </label>

    <label>
      Istirahat
      <select name="pakai_istirahat">
        <option value="1" <?= (!$e || (int)$e['pakai_istirahat']===1) ? 'selected' : '' ?>>Ada istirahat siang</option>
        <option value="0" <?= ($e && (int)$e['pakai_istirahat']===0) ? 'selected' : '' ?>>Tanpa istirahat (masuk lalu langsung pulang)</option>
      </select>
    </label>

    <div class="jd-penuh">
      <label style="margin-bottom:6px">Hari kerja</label>
      <div class="jd-hari">
        <?php foreach ($nama_hari as $n => $h): ?>
          <span>
            <input type="checkbox" name="hari_kerja[]" value="<?= $n ?>" <?= in_array((string)$n, $hari_kerja_dipilih, TRUE) ? 'checked' : '' ?>>
            <?= $h ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="jd-penuh">
      <label style="margin-bottom:6px">Jam masuk per hari (opsional)</label>
      <p class="label" style="margin-bottom:10px">
        Kosongkan hari yang jam masuknya sama dengan "Jam masuk" di atas.
        Isi hanya hari yang jamnya beda &mdash; misalnya Selasa 09:00, Rabu 10:00.
      </p>
      <div class="jd-hari" style="align-items:flex-end">
        <?php foreach ($nama_hari as $n => $h): ?>
          <label style="margin:0">
            <?= $h ?>
            <input type="time" name="jam_harian_<?= $n ?>"
                   value="<?= isset($jh[$n]) ? substr($jh[$n],0,5) : '' ?>"
                   style="width:110px">
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="jd-penuh">
      <label style="margin-bottom:6px">Hari kerja dari rumah</label>
      <div class="jd-hari">
        <?php foreach ($nama_hari as $n => $h): ?>
          <span><input type="checkbox" name="hari_wfh[]" value="<?= $n ?>" <?= in_array((string)$n, $hari_wfh_dipilih, TRUE) ? 'checked' : '' ?>> <?= $h ?></span>
        <?php endforeach; ?>
      </div>
      <p class="label" style="margin-top:8px">
        Pada hari ini pemeriksaan jarak lokasi dilewati, karena absen memang dilakukan dari rumah.
        Verifikasi wajah dan perangkat terdaftar tetap berjalan, dan koordinatnya tetap tercatat.
      </p>
    </div>

    <div class="jd-penuh">
      <button class="btn" type="submit">Simpan jadwal</button>
    </div>
  </form>
</div>

