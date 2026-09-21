<style>
    /* Select2 Styling */
    .select2-container .select2-selection--single {
        box-sizing: border-box;
        cursor: pointer;
        display: block;
        height: 32px;
        user-select: none;
        -webkit-user-select: none;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: rgba(0, 0, 0, 0.85);
        line-height: 32px;
        padding-left: 11px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px;
        position: absolute;
        top: 0px;
        right: 1px;
        width: 20px;
    }

    .select2 {
        height: 32px !important;
        min-width: 100% !important;
        margin-bottom: 8px;
    }

    /* Ant Design-like Table Styling */
    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #f0f0f0;
        border-radius: 2px;
    }

    .table thead th {
        background-color: #fafafa;
        color: rgba(0, 0, 0, 0.85);
        font-weight: 500;
        text-align: left;
        padding: 12px 8px;
        font-size: 14px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table tbody td {
        padding: 12px 8px !important;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table-hover tbody tr:hover {
        background-color: #fafafa;
    }

    /* Card Styling - Ant Design-like */
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
        margin-bottom: 16px;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
        height: 56px;
    }

    .card-body {
        padding: 16px;
    }

    /* Button Styling - Ant Design-like */
    .btn {
        border-radius: 2px;
        padding: 4px 15px;
        font-size: 14px;
        height: 32px;
        line-height: 1.5;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
    }

    .btn-outline-secondary {
        color: rgba(0, 0, 0, 0.65);
        border-color: #d9d9d9;
        background: #fff;
    }

    .btn-outline-secondary:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Badge Styling - Ant Design-like */
    .badge {
        font-size: 12px;
        height: 22px;
        padding: 0 8px;
        line-height: 22px;
        border-radius: 2px;
        font-weight: normal;
    }

    .reviewer-inactive-row td {
        background: #fafafa;
        color: rgba(0, 0, 0, 0.45);
    }

    .reviewer-active-badge {
        color: #52c41a;
        background: #f6ffed;
        border: 1px solid #b7eb8f;
    }

    .reviewer-inactive-badge {
        color: #8c8c8c;
        background: #fafafa;
        border: 1px solid #d9d9d9;
    }

    /* Pagination Styling - Ant Design-like */
    .pagination {
        margin-top: 16px;
        justify-content: flex-end;
    }

    .page-item {
        margin-right: 8px;
    }

    .page-item:last-child {
        margin-right: 0;
    }

    .page-item.active .page-link {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .page-link {
        min-width: 32px;
        height: 32px;
        line-height: 30px;
        text-align: center;
        color: rgba(0, 0, 0, 0.65);
        border-radius: 2px;
        padding: 0;
        margin: 0;
        border: 1px solid #d9d9d9;
    }

    .page-link:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Search Form Styling - Ant Design-like */
    .search-form {
        margin-bottom: 16px;
    }

    .search-form .input-group {
        border-radius: 2px;
        display: flex;
        justify-content: space-between;
    }

    .form-control {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-control:hover {
        border-color: #40a9ff;
    }

    .form-control:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .form-select {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-select:hover {
        border-color: #40a9ff;
    }

    .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    /* Alert Styling - Ant Design-like */
    .alert {
        padding: 8px 15px;
        border-radius: 2px;
        font-size: 14px;
    }

    .alert-info {
        background-color: #e6f7ff;
        border-color: #91d5ff;
        color: rgba(0, 0, 0, 0.65);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }
</style>
<?php $r = $review; ?>
<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-3">
        <a href="<?= base_url() ?>performance_review/review_detail?id=<?= $r['id'] ?>" class="text-decoration-none text-muted me-2"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Final - <?= htmlspecialchars($r['reviewee_name']) ?></h4>
    </div>
    <p class="text-muted"><?= htmlspecialchars($r['template_name']) ?> &middot; <?= htmlspecialchars($r['period_label']) ?>. Skor di sini menjadi dasar PDF hasil akhir.</p>

    <?php if (!empty($assignments)): ?>
    <div class="alert alert-light border">
        <strong><i class="bi bi-eye me-1"></i>Referensi reviewer:</strong>
        <?php foreach ($assignments as $a): ?>
            <span class="badge bg-light text-dark me-1"><?= htmlspecialchars($a['reviewer_name']) ?> (<?= $a['reviewer_role'] ?>): <?= $a['status'] === 'submitted' ? 'terisi' : 'belum' ?></span>
        <?php endforeach; ?>
        <a href="<?= base_url() ?>performance_review/review_detail?id=<?= $r['id'] ?>" class="small ms-2">lihat detail isian</a>
    </div>
    <?php endif; ?>

    <div id="formAlert"></div>

    <form id="finalForm">
        <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
        <?php
        $current_cat = null;
        foreach ($indicators as $ind):
            if ($ind['category'] !== $current_cat):
                if ($current_cat !== null) echo '</div></div>';
                $current_cat = $ind['category'];
        ?>
            <div class="card mb-3"><div class="card-header"><strong><?= htmlspecialchars($current_cat ?: 'Indikator') ?></strong></div><div class="card-body">
        <?php endif; ?>
            <div class="mb-3 pb-3 border-bottom">
                <div class="fw-semibold mb-2"><?= htmlspecialchars($ind['name']) ?>
                    <span class="badge bg-light text-dark ms-1"><?= $ind['input_type'] ?></span>
                </div>
                <?php $this->load->view('performance_review/_indicator_input', ['ind' => $ind, 'existing' => $final_scores[$ind['id']] ?? null]); ?>
            </div>
        <?php endforeach; ?>
        <?php if ($current_cat !== null) echo '</div></div>'; ?>

        <div class="card mb-3"><div class="card-body">
            <label class="form-label">Sumber / Bukti</label>
            <textarea class="form-control" name="sources" rows="2" placeholder="Tautan/keterangan bukti (opsional)"><?= htmlspecialchars($r['sources'] ?? '') ?></textarea>
        </div></div>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Final</button>
            <a href="<?= base_url() ?>performance_review/pdf/<?= $r['id'] ?>" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-eye me-1"></i>Preview PDF</a>
            <a href="<?= base_url() ?>performance_review/review_detail?id=<?= $r['id'] ?>" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </form>
</div>
<script>
const BASE = '<?= base_url() ?>';
$('#finalForm').on('submit', function (e) {
    e.preventDefault();
    const $btn = $(this).find('button[type=submit]').prop('disabled', true);
    $.post(BASE + 'performance_review/final_store', $(this).serialize(), function (res) {
        $('#formAlert').html(res);
        $btn.prop('disabled', false);
        if (res.indexOf('success') !== -1) {
            setTimeout(() => window.location.href = BASE + 'performance_review/review_detail?id=<?= $r['id'] ?>', 700);
        }
    });
});
</script>
