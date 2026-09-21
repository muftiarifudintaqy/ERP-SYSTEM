<?php

$btn_1 = "btn-edit";
$btn_2 = "btn-edit";
$page = $_GET['p'] ?? '';
if ($page == "") {
    $btn_1 = "btn-primary";
} elseif ($page == "operasional") {
    $btn_2 = "btn-primary";
}
?>
<div class="col-md-12">
    <a href="<?= base_url() ?>report" class="btn <?= $btn_1 ?> me-1 mb-3" style="min-width:90px!important">OVERVIEW</a>
    <a href="<?= base_url() ?>report?p=operasional" class="btn <?= $btn_2 ?> me-1 mb-3" style="min-width:90px!important">OPERASIONAL</a>
    <?php
    $user_id_check = $this->session->userdata('user')['id'] ?? 0;
    $has_report_aset = $this->load->is_loaded('permission')
        ? $this->permission->check_permission($user_id_check, 'report_aset', 'view')
        : ($this->session->userdata('user')['role'] == "1");
    ?>
    <?php if ($has_report_aset) : ?>
        <a href="<?= base_url() ?>report_aset" class="btn btn-edit me-1 mb-3" style="min-width:90px!important">ASET</a>
    <?php endif; ?>
</div>
