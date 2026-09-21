<?php
$is_edit = !empty($data);
$bands = (!empty($data['grade_bands'])) ? json_decode($data['grade_bands'], true) : [];
?>
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
<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-3">
        <a href="<?= base_url() ?>performance_review" class="text-decoration-none text-muted me-2"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0"><i class="bi bi-files me-2"></i><?= $is_edit ? 'Edit' : 'Tambah' ?> Template Penilaian</h4>
    </div>

    <div id="formAlert"></div>

    <form id="tplForm">
        <?php if ($is_edit): ?><input type="hidden" name="id" value="<?= $data['id'] ?>"><?php endif; ?>

        <div class="card mb-3"><div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Template <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="dt[name]" value="<?= htmlspecialchars($data['name'] ?? '') ?>" required placeholder="mis. Performance & Culture Review">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipe (label)</label>
                    <select class="form-control" name="dt[type_label]">
                        <?php foreach (['Rubrik','Ya/Tidak','Skala','% Pencapaian','Teks','Campuran'] as $tl): ?>
                            <option <?= (($data['type_label'] ?? 'Campuran') == $tl) ? 'selected' : '' ?>><?= $tl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-control" name="dt[status]">
                        <option value="active" <?= (($data['status'] ?? 'active') == 'active') ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= (($data['status'] ?? '') == 'inactive') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="form-label">Deskripsi</label>
                <textarea class="form-control" name="dt[description]" rows="2"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-list-check me-1"></i>Indikator Penilaian</strong>
            <button type="button" class="btn btn-sm btn-primary" onclick="addIndicator()"><i class="bi bi-plus-lg me-1"></i>Tambah Indikator</button>
        </div><div class="card-body">
            <div id="indicators"></div>
            <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Tipe <strong>Rubrik</strong> = deskripsi level 1–4 + standar + pencapaian (seperti form MONTERA). Tipe lain: Ya/Tidak, Skala, % Pencapaian, Teks.</p>
        </div></div>

        <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-bar-chart me-1"></i>Konversi Penilaian (opsional)</strong>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addBand()"><i class="bi bi-plus-lg me-1"></i>Tambah Rentang</button>
        </div><div class="card-body">
            <p class="text-muted small">Rentang dalam <strong>persen</strong> dari target (total ÷ standar × 100). Mis. 70–100 = Baik.</p>
            <div id="bands"></div>
        </div></div>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Template</button>
            <a href="<?= base_url() ?>performance_review" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>

<script>
const BASE = '<?= base_url() ?>';
const existingIndicators = <?= json_encode($indicators ?? []) ?>;
const existingBands = <?= json_encode($bands ?: []) ?>;
let idx = 0;

function indicatorRow(d) {
    d = d || {};
    const i = idx++;
    const t = d.input_type || 'rubric';
    const esc = s => (s || '').replace(/"/g, '&quot;');
    return `
    <div class="border rounded p-3 mb-3 indicator-row" data-i="${i}" style="background:#fafafa;">
      <div class="d-flex justify-content-between mb-2">
        <span class="badge bg-secondary">Indikator</span>
        <a href="#!" class="text-danger" onclick="$(this).closest('.indicator-row').remove()"><i class="bi bi-x-circle"></i> Hapus</a>
      </div>
      <div class="row g-2 mb-2">
        <div class="col-md-3"><label class="form-label small mb-0">Kategori</label>
          <input class="form-control form-control-sm" name="ind[${i}][category]" value="${esc(d.category)}" placeholder="mis. Culture"></div>
        <div class="col-md-5"><label class="form-label small mb-0">Nama Indikator *</label>
          <input class="form-control form-control-sm" name="ind[${i}][name]" value="${esc(d.name)}" placeholder="mis. Discipline"></div>
        <div class="col-md-4"><label class="form-label small mb-0">Tipe Input</label>
          <select class="form-control form-control-sm itype" name="ind[${i}][input_type]" onchange="toggleType(${i})">
            ${['rubric','yesno','scale','percent','text'].map(x => `<option value="${x}" ${t===x?'selected':''}>${x}</option>`).join('')}
          </select></div>
      </div>
      <div class="rubric-fields" data-for="${i}" style="display:${t==='rubric'?'block':'none'}">
        <div class="row g-2 mb-2">
          ${[1,2,3,4].map(n => `<div class="col-md-3"><label class="form-label small mb-0">Level ${n}</label>
            <textarea class="form-control form-control-sm" rows="3" name="ind[${i}][level${n}_desc]" placeholder="Deskripsi level ${n}">${esc(d['level'+n+'_desc'])}</textarea></div>`).join('')}
        </div>
      </div>
      <div class="options-field" data-for="${i}" style="display:${t==='scale'?'block':'none'}">
        <label class="form-label small mb-0">Opsi Skala (satu per baris)</label>
        <textarea class="form-control form-control-sm mb-2" rows="2" name="ind[${i}][options]" placeholder="1 - Kurang&#10;2 - Cukup&#10;3 - Baik">${esc(d.options)}</textarea>
      </div>
      <div class="row g-2 score-fields" data-for="${i}" style="display:${t==='text'?'none':'flex'}">
        <div class="col-md-4"><label class="form-label small mb-0">Standar (target)</label>
          <input type="number" step="0.01" class="form-control form-control-sm" name="ind[${i}][standard_score]" value="${d.standard_score ?? ''}" placeholder="mis. 3"></div>
        <div class="col-md-4"><label class="form-label small mb-0">Skor Maks</label>
          <input type="number" step="0.01" class="form-control form-control-sm" name="ind[${i}][max_score]" value="${d.max_score ?? ''}" placeholder="rubrik=4"></div>
        <div class="col-md-4"><label class="form-label small mb-0">Bobot % (opsional)</label>
          <input type="number" step="0.01" class="form-control form-control-sm" name="ind[${i}][weight]" value="${d.weight ?? ''}"></div>
      </div>
    </div>`;
}

function toggleType(i) {
    const t = $(`.indicator-row[data-i="${i}"] .itype`).val();
    $(`.rubric-fields[data-for="${i}"]`).toggle(t === 'rubric');
    $(`.options-field[data-for="${i}"]`).toggle(t === 'scale');
    $(`.score-fields[data-for="${i}"]`).toggle(t !== 'text');
}
function addIndicator(d) { $('#indicators').append(indicatorRow(d)); }

let bidx = 0;
function bandRow(b) {
    b = b || {};
    const i = bidx++;
    return `<div class="row g-2 mb-2 align-items-end band-row">
      <div class="col-md-3"><label class="form-label small mb-0">Min %</label><input type="number" step="0.01" class="form-control form-control-sm" name="band[${i}][min]" value="${b.min ?? ''}"></div>
      <div class="col-md-3"><label class="form-label small mb-0">Max %</label><input type="number" step="0.01" class="form-control form-control-sm" name="band[${i}][max]" value="${b.max ?? ''}"></div>
      <div class="col-md-5"><label class="form-label small mb-0">Label</label><input class="form-control form-control-sm" name="band[${i}][label]" value="${(b.label||'').replace(/"/g,'&quot;')}" placeholder="mis. Baik"></div>
      <div class="col-md-1"><a href="#!" class="text-danger" onclick="$(this).closest('.band-row').remove()"><i class="bi bi-x-circle"></i></a></div>
    </div>`;
}
function addBand(b) { $('#bands').append(bandRow(b)); }

$(function () {
    if (existingIndicators.length) existingIndicators.forEach(addIndicator); else addIndicator();
    if (existingBands.length) existingBands.forEach(addBand);

    $('#tplForm').on('submit', function (e) {
        e.preventDefault();
        const url = <?= $is_edit ? "'performance_review/template_update'" : "'performance_review/template_store'" ?>;
        const $btn = $(this).find('button[type=submit]').prop('disabled', true);
        $.post(BASE + url, $(this).serialize(), function (res) {
            $('#formAlert').html(res);
            if (res.indexOf('success') !== -1) {
                setTimeout(() => window.location.href = BASE + 'performance_review', 700);
            } else { $btn.prop('disabled', false); }
        });
    });
});
</script>
