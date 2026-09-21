<style>
    /* Table Responsive for Custom Scrollbar */
    .table-responsive[data-custom-scrollbar] {
        overflow-x: auto;
        white-space: nowrap;
    }

    .table-responsive[data-custom-scrollbar] table {
        min-width: max-content;
    }
    
    /* Sticky Left Columns */
    .table thead th:nth-child(1),
    .table tbody td:nth-child(1) {
        position: sticky;
        left: 0;
        background: #fafafa;
        z-index: 6;
        box-shadow: 2px 0 4px rgba(0,0,0,0.05);
    }
    
    .table thead th:nth-child(2),
    .table tbody td:nth-child(2) {
        position: sticky;
        left: 50px;
        background: #fafafa;
        z-index: 6;
        box-shadow: 2px 0 4px rgba(0,0,0,0.05);
    }
    
    .table thead th:nth-child(3),
    .table tbody td:nth-child(3) {
        position: sticky;
        left: 170px;
        background: #fafafa;
        z-index: 6;
        box-shadow: 2px 0 4px rgba(0,0,0,0.05);
    }
    
    .table tbody td:nth-child(1),
    .table tbody td:nth-child(2),
    .table tbody td:nth-child(3) {
        background: #fff;
    }
    
    .table thead th:nth-child(1),
    .table thead th:nth-child(2),
    .table thead th:nth-child(3) {
        z-index: 11;
    }
    
    /* Action Column Sticky Right */
    .table thead th.action-col,
    .table tbody td.action-col {
        position: sticky;
        right: 0;
        background: #fff;
        z-index: 5;
        box-shadow: -2px 0 8px rgba(0,0,0,0.05);
    }
    
    .table thead th.action-col {
        z-index: 10;
        background: #fafafa;
    }
    
    /* Action Buttons Hover Effects */
    .btn-reject:hover {
        background: #ff4d4f !important;
        color: white !important;
        border-color: #ff4d4f !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(255, 77, 79, 0.3);
    }
    
    .btn-notes:hover {
        background: #1890ff !important;
        color: white !important;
        border-color: #1890ff !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(24, 144, 255, 0.3);
    }
    
    .btn-reject, .btn-notes {
        transition: all 0.2s ease;
    }
    
    /* Tippy Theme */
    .tippy-box[data-theme~='recruitment-notes'] {
        background-color: #ffffff;
        color: #333333;
        font-size: 13px;
        border-radius: 6px;
        padding: 8px 12px;
        max-width: 350px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: 1px solid #e0e0e0;
    }
    
    .tippy-box[data-theme~='recruitment-notes'][data-placement^='top'] > .tippy-arrow::before {
        border-top-color: #ffffff;
    }
    
    .tippy-box[data-theme~='recruitment-notes'][data-placement^='bottom'] > .tippy-arrow::before {
        border-bottom-color: #ffffff;
    }
    
    .tippy-box[data-theme~='recruitment-notes'][data-placement^='left'] > .tippy-arrow::before {
        border-left-color: #ffffff;
    }
    
    .tippy-box[data-theme~='recruitment-notes'][data-placement^='right'] > .tippy-arrow::before {
        border-right-color: #ffffff;
    }
    
    /* Tab styling enhancements */
    .recruitment-tabs .nav-link {
        transition: all 0.3s ease;
    }
    
    .recruitment-tabs .nav-link:hover:not(.active) {
        background: #f5f5f5;
        transform: translateY(-2px);
    }

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

    .history-card {
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .history-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
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

    /* Status Badges */
    .badge-primary {
        background-color: #1890ff;
        color: white;
    }
    .badge-success {
        background-color: #52c41a;
        color: white;
    }
    .badge-warning {
        background-color: #faad14;
        color: white;
    }
    .badge-danger {
        background-color: #f5222d;
        color: white;
    }
    .badge-default {
        background-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.65);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }
    .task-settings-table .desc-preview {
        max-width: 420px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .task-settings-table td,
    .task-settings-table th {
        vertical-align: middle;
    }
</style>

<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Task Settings</h5>
                <small class="text-muted">Atur task untuk onboarding/offboarding</small>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url() ?>onboarding" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
                <?php if ($can_edit): ?>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-open-add-task" data-bs-toggle="modal" data-bs-target="#taskItemModal">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Task
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($flash)): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
            <?php endif; ?>

            <?php if (!$schema_ready): ?>
                <div class="alert alert-warning mb-0">
                    Tabel onboarding/offboarding belum tersedia. Jalankan SQL di <code>database/onboarding_offboarding.sql</code> terlebih dahulu.
                </div>
            <?php else: ?>
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link <?= $transition_type === 'onboarding' ? 'active' : '' ?>" href="<?= base_url() ?>onboarding/task-settings?type=onboarding">Onboarding</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $transition_type === 'offboarding' ? 'active' : '' ?>" href="<?= base_url() ?>onboarding/task-settings?type=offboarding">Offboarding</a>
                    </li>
                </ul>

                <div class="table-responsive task-settings-table">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="60">No</th>
                                <th>Task</th>
                                <th>Description</th>
                                <th width="180">Attachment</th>
                                <th width="100">Required</th>
                                <th width="90">Active</th>
                                <th width="160">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tasks)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada task untuk <?= htmlspecialchars($transition_type) ?>.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tasks as $index => $task): ?>
                                    <?php
                                    $task_json = htmlspecialchars(json_encode([
                                        'id' => (int) $task['id'],
                                        'task_name' => (string) $task['task_name'],
                                        'description_html' => (string) ($task['description_html'] ?? ''),
                                        'attachment_path' => (string) ($task['attachment_path'] ?? ''),
                                        'attachment_name' => (string) ($task['attachment_name'] ?? ''),
                                        'is_required' => (int) ($task['is_required'] ?? 0),
                                        'is_active' => (int) ($task['is_active'] ?? 0)
                                    ]), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($task['task_name']) ?></td>
                                        <td>
                                            <div class="desc-preview text-muted">
                                                <?= trim(strip_tags((string) ($task['description_html'] ?? ''))) !== '' ? htmlspecialchars(mb_substr(trim(strip_tags((string) ($task['description_html'] ?? ''))), 0, 120)) : '-' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($task['attachment_path'])): ?>
                                                <a href="<?= base_url() . htmlspecialchars($task['attachment_path']) ?>" target="_blank"><?= htmlspecialchars($task['attachment_name'] ?: 'Lihat attachment') ?></a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= (int) $task['is_required'] === 1 ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                                        <td><?= (int) $task['is_active'] === 1 ? '<span class="badge bg-primary">On</span>' : '<span class="badge bg-light text-dark border">Off</span>' ?></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <?php if ($can_edit): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-task" data-task="<?= $task_json ?>" data-bs-toggle="modal" data-bs-target="#taskItemModal">
                                                        Edit
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($can_delete): ?>
                                                    <form method="post" action="<?= base_url() ?>onboarding/delete-task-item" onsubmit="return confirm('Hapus task ini?')">
                                                        <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                                        <input type="hidden" name="transition_type" value="<?= htmlspecialchars($transition_type) ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($schema_ready && $can_edit): ?>
<div class="modal fade" id="taskItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" method="post" action="<?= base_url() ?>onboarding/save-task-item" enctype="multipart/form-data" id="task-item-form">
            <div class="modal-header">
                <h5 class="modal-title" id="task-item-modal-title">Tambah Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="task-item-id" value="">
                <input type="hidden" name="transition_type" id="task-item-transition-type" value="<?= htmlspecialchars($transition_type) ?>">
                <input type="hidden" name="existing_attachment_path" id="task-item-existing-path" value="">
                <input type="hidden" name="existing_attachment_name" id="task-item-existing-name" value="">

                <div class="mb-3">
                    <label class="form-label">Nama Task</label>
                    <input type="text" class="form-control" name="task_name" id="task-item-name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description_html" id="task-description-editor"></textarea>
                </div>

                <div class="mb-3" id="task-item-current-attachment" style="display:none;">
                    <label class="form-label">Attachment Saat Ini</label>
                    <div><a href="#" target="_blank" id="task-item-current-attachment-link"></a></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Upload Attachment (opsional)</label>
                    <input type="file" class="form-control" name="attachment_file" id="task-item-attachment">
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="is_required" id="task-item-required" value="1" checked>
                            <label class="form-check-label" for="task-item-required">Required</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="task-item-active" value="1" checked>
                            <label class="form-check-label" for="task-item-active">Active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function() {
    function initEditor() {
        if (typeof tinymce === 'undefined') {
            return;
        }

        if (tinymce.get('task-description-editor')) {
            return;
        }

        tinymce.init({
            selector: '#task-description-editor',
            height: 260,
            menubar: false,
            statusbar: true,
            plugins: 'lists link code autoresize',
            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | code',
            placeholder: 'Masukkan deskripsi task...'
        });
    }

    function setEditorContent(content) {
        const editor = tinymce.get('task-description-editor');
        if (editor) {
            editor.setContent(content || '');
        } else {
            $('#task-description-editor').val(content || '');
        }
    }

    function resetForm() {
        $('#task-item-modal-title').text('Tambah Task');
        $('#task-item-id').val('');
        $('#task-item-name').val('');
        $('#task-item-required').prop('checked', true);
        $('#task-item-active').prop('checked', true);
        $('#task-item-attachment').val('');
        $('#task-item-existing-path').val('');
        $('#task-item-existing-name').val('');
        $('#task-item-current-attachment').hide();
        setEditorContent('');
    }

    $('#btn-open-add-task').on('click', function() {
        resetForm();
    });

    $(document).on('click', '.btn-edit-task', function() {
        resetForm();
        const raw = $(this).attr('data-task') || '{}';
        let task = {};
        try { task = JSON.parse(raw); } catch (e) { task = {}; }

        $('#task-item-modal-title').text('Edit Task');
        $('#task-item-id').val(task.id || '');
        $('#task-item-name').val(task.task_name || '');
        $('#task-item-required').prop('checked', parseInt(task.is_required || 0, 10) === 1);
        $('#task-item-active').prop('checked', parseInt(task.is_active || 0, 10) === 1);
        $('#task-item-existing-path').val(task.attachment_path || '');
        $('#task-item-existing-name').val(task.attachment_name || '');

        if (task.attachment_path) {
            $('#task-item-current-attachment-link')
                .attr('href', '<?= base_url() ?>' + task.attachment_path)
                .text(task.attachment_name || 'Lihat attachment');
            $('#task-item-current-attachment').show();
        } else {
            $('#task-item-current-attachment').hide();
        }

        setEditorContent(task.description_html || '');
    });

    $('#task-item-form').on('submit', function() {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }
    });

    $('#taskItemModal').on('shown.bs.modal', function() {
        initEditor();
    });

    initEditor();
})();
</script>
<?php endif; ?>
