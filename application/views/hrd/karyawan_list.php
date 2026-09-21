<div class="topbar">
  <div>
    <h1>Data karyawan</h1>
    <p class="sub"><?= count($rows) ?> orang ditampilkan. Klik nama untuk melihat KTP, rekening, dan barang yang dipegang.</p>
  </div>
  <div class="aksi">
    <a class="btn" href="<?= base_url('hrd/export_karyawan?' . http_build_query($f)) ?>">Unduh Excel</a>
    <a class="btn" href="<?= base_url('hrd/karyawan_impor') ?>" data-konfirmasi="Tarik semua akun user ERP yang belum tercatat ke data karyawan? Data yang sudah ada tidak akan tertimpa.">Tarik dari akun ERP</a>
    <a class="btn btn-utama" href="<?= base_url('hrd/karyawan_form') ?>">Tambah karyawan</a>
  </div>
</div>

<form class="filter" method="get" action="<?= base_url('hrd/karyawan') ?>">
  <input type="text" name="q" value="<?= html_escape($f['q']) ?>" placeholder="Cari nama, jabatan, NIK, no. HP">
  <select name="divisi">
    <option value="">Semua divisi</option>
    <?php foreach ($divisi as $d): ?>
      <option value="<?= html_escape($d) ?>" <?= $f['divisi'] === $d ? 'selected' : '' ?>><?= html_escape($d) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status">
    <option value="">Semua status</option>
    <?php foreach (['Aktif','Resign','Cuti Panjang'] as $s): ?>
      <option value="<?= $s ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn" type="submit">Cari</button>
  <?php if ($f['q'] || $f['divisi'] || $f['status']): ?>
    <a class="btn" href="<?= base_url('hrd/karyawan') ?>">Reset</a>
  <?php endif; ?>
</form>

<?php if (!$rows): ?>
  <div class="kartu kosong">
    <b>Belum ada data yang cocok</b>
    Tekan <b>Tarik dari akun ERP</b> untuk memasukkan semua karyawan yang sudah punya akun,
    lalu bagikan tautan <a href="<?= base_url('f/data-karyawan') ?>" target="_blank">form data karyawan</a> supaya mereka melengkapi sendiri KTP dan rekeningnya.
  </div>
<?php else: ?>
<div class="tabel-bungkus">
  <table>
    <thead>
      <tr>
        <th>Nama</th><th>Jabatan / Divisi</th><th>No. HP</th><th>Tanggal masuk</th>
        <th>Bank</th><th>KTP</th><th>Status</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $k): ?>
      <tr>
        <td>
          <div class="baris-nama">
            <span class="avatar"><?= strtoupper(mb_substr($k['nama'], 0, 2)) ?></span>
            <span>
              <a href="<?= base_url('hrd/karyawan_detail/' . $k['id']) ?>" style="font-weight:600;text-decoration:none"><?= html_escape($k['nama']) ?></a>
              <small><?= html_escape($k['email'] ?: '-') ?></small>
            </span>
          </div>
        </td>
        <td><?= html_escape($k['jabatan'] ?: '-') ?><small style="display:block;color:var(--tinta-3)"><?= html_escape($k['divisi'] ?: '') ?></small></td>
        <td class="rapat"><?= html_escape($k['no_hp'] ?: '-') ?></td>
        <td class="rapat"><?= $k['tanggal_masuk'] ? date('d M Y', strtotime($k['tanggal_masuk'])) : '-' ?></td>
        <td><?= html_escape($k['nama_bank'] ?: '-') ?></td>
        <td><?= $k['foto_ktp'] ? '<span class="tag tag-hijau">Ada</span>' : '<span class="tag tag-merah">Belum</span>' ?></td>
        <td><span class="tag <?= $k['status_karyawan'] === 'Aktif' ? 'tag-hijau' : 'tag-abu' ?>"><?= $k['status_karyawan'] ?></span></td>
        <td class="rapat"><a class="btn btn-kecil" href="<?= base_url('hrd/karyawan_form/' . $k['id']) ?>">Ubah</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
