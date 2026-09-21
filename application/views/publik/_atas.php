<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= html_escape($judul) ?> &middot; Montera</title>
<link rel="stylesheet" href="<?= base_url('assets/css/hrd.css') ?>?v=3">
<!-- ntf-favicon-publik -->
<link rel="icon" href="<?= base_url('assets/img/fav.png') ?>">
<link rel="apple-touch-icon" href="<?= base_url('assets/img/fav.png') ?>">
<meta name="theme-color" content="#1F6F5C">
</head>
<body class="hrd-publik">
<div class="publik hrd-modul">
  <?php if ($p = $this->session->flashdata('gagal')): ?>
    <div class="pesan pesan-gagal"><?= html_escape($p) ?></div>
  <?php endif; ?>
