<?php
/* =====================================================================
   TEMPEL ISI DI BAWAH INI ke bagian bawah application/config/routes.php
   (jangan sertakan tag <?php di atas)
   ===================================================================== */

// Halaman pengisian publik - tanpa login
$route['f/selesai']        = 'formulir/selesai';
$route['f/data-karyawan']  = 'formulir/karyawan';
$route['f/inventaris']     = 'formulir/inventaris';
$route['f/disc']           = 'formulir/disc';
$route['f/(:any)']         = 'formulir/isi/$1';

// Area HRD (butuh login) - CodeIgniter sudah otomatis memetakan
// hrd/karyawan, hrd/karyawan_detail/12, dst. Baris di bawah cuma jalan pintas.
$route['hrd']              = 'hrd/index';
