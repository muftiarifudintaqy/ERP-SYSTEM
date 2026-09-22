<?php
$btn_1 = "btn-edit"; // PRODUK
$btn_2 = "btn-edit"; // CHANNEL
$btn_3 = "btn-edit"; // EKSPEDISI
$btn_4 = "btn-edit"; // AKUN MARKETPLACE
$btn_5 = "btn-edit"; // SKU BELUM DIKENALI

$url = current_url();
if ($url == base_url() . "product") {
    $btn_1 = "btn-primary";
} else if ($url == base_url() . "marketplace") {
    $btn_2 = "btn-primary";
} else if ($url == base_url() . "shipping") {
    $btn_3 = "btn-primary";
} else if ($url == base_url() . "marketplace-account") {
    $btn_4 = "btn-primary";
} else if ($url == base_url() . "product/sku_belum") {
    $btn_5 = "btn-primary";
}

?>

<div class="col-md-12">
    <a href="<?= base_url() ?>product" class="btn <?= $btn_1 ?> me-1 mb-3" style="min-width:90px!important">PRODUK</a>
    <a href="<?= base_url() ?>marketplace-account" class="btn <?= $btn_4 ?> me-1 mb-3" style="min-width:90px!important">AKUN TOKO</a>
    <a href="<?= base_url() ?>marketplace" class="btn <?= $btn_2 ?> me-1 mb-3" style="min-width:90px!important">CHANNEL</a>
    <a href="<?= base_url() ?>shipping" class="btn <?= $btn_3 ?> me-1 mb-3" style="min-width:90px!important">EKSPEDISI</a>
    <a href="<?= base_url() ?>product/sku_belum" class="btn <?= $btn_5 ?> mb-3" style="min-width:90px!important">SKU BELUM DIKENALI</a>
</div>