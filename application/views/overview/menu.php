<?php
$type = $_GET['t'] ?? 'general';
$active_general = ($type == "general");
$active_digiads = ($type == "digiads" || $type == "ads");
$active_endorse = ($type == "endorse" || $type == "kol" || $type == "influencer");
?>
<style>
    .overview-tabs {
        background: #e9f5f5;
        border-radius: 16px;
        padding: 8px;
        display: inline-flex;
        gap: 6px;
    }

    .overview-tabs .nav-link {
        border-radius: 14px;
        padding: 8px 18px;
        font-weight: 600;
        color: #187f7f;
        background: transparent;
        border: none;
    }

    .overview-tabs .nav-link.active {
        background: #0f8b8d;
        color: #fff;
        box-shadow: 0 6px 16px rgba(15, 139, 141, 0.2);
    }
</style>
<div class="col-md-12 mb-3">
    <ul class="nav overview-tabs">
        <li class="nav-item">
            <a class="nav-link <?= $active_general ? 'active' : '' ?>" href="<?= base_url() ?>overview?t=general">General</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_digiads ? 'active' : '' ?>" href="<?= base_url() ?>overview?t=digiads">Digiads</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_endorse ? 'active' : '' ?>" href="<?= base_url() ?>overview?t=endorse">Endorse</a>
        </li>
    </ul>
</div>
