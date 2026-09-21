<?php
// Fragmen konten modul HRD - dirender di dalam TemplateDashboard ERP.
$menu = [
    ['dashboard',  'hrd',            'Ringkasan'],
    ['karyawan',   'hrd/karyawan',   'Data karyawan'],
    ['inventaris', 'hrd/inventaris', 'Inventaris'],
    ['jadwal',    'hrd/jadwal',    'Jadwal Kerja'],
    ['libur',    'hrd/libur',    'Hari Libur'],
    ['asesmen',   'hrd/asesmen',   'Asesmen'],
    ['disc',       'hrd/disc',       'Hasil DISC'],
    ['disc_soal',  'hrd/disc_soal',  'Susun DISC test'],
    ['aptitude_soal', 'hrd/aptitude_soal', 'Susun Test IQ'],
    ['karakteristik_soal', 'hrd/karakteristik_soal', 'Susun Karakteristik'],
    ['formulir',   'hrd/formulir',   'Formulir'],
];
?>
<link rel="stylesheet" href="<?= base_url('assets/css/hrd.css') ?>?v=3">
<div class="hrd-modul">
  <nav class="tab-modul">
    <?php foreach ($menu as $m): ?>
      <a href="<?= base_url($m[1]) ?>" class="<?= $menu_aktif === $m[0] ? 'aktif' : '' ?>"><?= $m[2] ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if ($p = $this->session->flashdata('sukses')): ?>
    <div class="pesan pesan-ok"><?= html_escape($p) ?></div>
  <?php endif; ?>
  <?php if ($p = $this->session->flashdata('gagal')): ?>
    <div class="pesan pesan-gagal"><?= html_escape($p) ?></div>
  <?php endif; ?>
