<?php
$url = current_url();

$active_recruitment = ($url == base_url() . "recruitment");
$active_interview = ($url == base_url() . "interview");
$active_questions = (
    $url == base_url() . "interview/questions" ||
    $url == base_url() . "interview/add_question" ||
    preg_match("#^" . preg_quote(base_url() . "interview/edit_question/") . "\d+$#", $url)
);
?>
<style>
    .recruitment-tabs {
        background: #e6f7ff;
        border-radius: 16px;
        padding: 8px;
        display: inline-flex;
        gap: 6px;
    }

    .recruitment-tabs .nav-link {
        border-radius: 14px;
        padding: 8px 18px;
        font-weight: 600;
        color: #1890ff;
        background: transparent;
        border: none;
    }

    .recruitment-tabs .nav-link.active {
        background: #1890ff;
        color: #fff;
        box-shadow: 0 6px 16px rgba(24, 144, 255, 0.2);
    }
</style>
<div class="col-md-12 mb-3">
    <ul class="nav recruitment-tabs">
        <li class="nav-item">
            <a class="nav-link <?= $active_recruitment ? 'active' : '' ?>" href="<?= base_url() ?>recruitment">Recruitment</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_interview ? 'active' : '' ?>" href="<?= base_url() ?>interview">Interview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_questions ? 'active' : '' ?>" href="<?= base_url() ?>interview/questions">Questions</a>
        </li>
    </ul>
</div>
