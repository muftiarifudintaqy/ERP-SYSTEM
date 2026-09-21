<style>
    /* Table Responsive for Custom Scrollbar */
    .table-responsive[data-custom-scrollbar] {
        overflow-x: auto;
        white-space: nowrap;
    }

    .table-responsive[data-custom-scrollbar] table {
        min-width: max-content;
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
    .transition-header {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        background: #fff;
        padding: 14px 18px;
        margin-bottom: 16px;
    }
    .todo-item-card {
        border: 1px solid #eceff5;
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 10px;
        background: #fff;
    }
    .task-pill {
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        border: 1px solid transparent;
    }
    .task-pill.todo {
        background: #eef2ff;
        color: #4f46e5;
        border-color: #c7d2fe;
    }
    .task-pill.done {
        background: #f0fdf4;
        color: #166534;
        border-color: #bbf7d0;
    }
    .task-pill.doing {
        background: #fff7d6;
        color: #8a6a00;
        border-color: #f7d778;
    }

    .detail-progress-track {
        width: 180px;
        height: 8px;
        background: #e9eef8;
        border-radius: 999px;
        overflow: hidden;
        margin-top: 6px;
        margin-left: auto;
    }

    .detail-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #5d7bff 0%, #3f62f5 100%);
        border-radius: 999px;
        transition: width 0.2s ease;
    }

    .todo-list-scroll {
        max-height: min(62vh, 560px);
        overflow-y: auto;
        overscroll-behavior: contain;
        padding-right: 6px;
    }

    @media (max-width: 991.98px) {
        .todo-list-scroll {
            max-height: 58vh;
        }
    }
</style>

<div class="container-fluid py-1">
    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <div class="transition-header d-flex justify-content-between align-items-center">
        <div>
            <a href="<?= base_url() ?>onboarding?tab=<?= htmlspecialchars($transition['transition_type']) ?>" class="text-decoration-none small">
                <?= ucfirst($transition['transition_type']) ?>
            </a>
            <h4 class="mb-0 mt-1"><?= htmlspecialchars($transition['full_name']) ?></h4>
            <small class="text-muted"><?= htmlspecialchars($transition['email']) ?></small>
        </div>
        <div class="text-end">
            <div class="fw-semibold mb-1">Progress</div>
            <div id="detail-progress-text" class="text-primary"><?= (int) $progress['progress'] ?>% (<?= (int) $progress['checked'] ?>/<?= (int) $progress['total'] ?>)</div>
            <div class="detail-progress-track">
                <div id="detail-progress-fill" class="detail-progress-fill" style="width: <?= (int) $progress['progress'] ?>%;"></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><strong>Employee Info</strong></div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr><td width="45%" class="text-muted text-start">Employee name</td><td class="text-start"><?= htmlspecialchars($transition['full_name']) ?></td></tr>
                        <tr><td class="text-muted text-start">Job level</td><td class="text-start"><?= htmlspecialchars($transition['level_name'] ?: '-') ?></td></tr>
                        <tr><td class="text-muted text-start">Position</td><td class="text-start"><?= htmlspecialchars($transition['position_name'] ?: '-') ?></td></tr>
                        <tr><td class="text-muted text-start">Employment Contract</td><td class="text-start"><?= htmlspecialchars($transition['jenis_kontrak'] ?: '-') ?></td></tr>
                        <tr><td class="text-muted text-start">Join date</td><td class="text-start"><?= !empty($transition['join_date']) ? date('d M Y', strtotime($transition['join_date'])) : '-' ?></td></tr>
                        <tr><td class="text-muted text-start">Invitation date</td><td class="text-start"><?= !empty($transition['invitation_date']) ? date('d M Y', strtotime($transition['invitation_date'])) : '-' ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>To do list</strong>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCustomTaskModal">
                        <i class="bi bi-plus-lg me-1"></i>Add task
                    </button>
                </div>
                <div class="card-body todo-list-scroll">
                    <?php if (empty($tasks)): ?>
                        <p class="text-muted mb-0">Belum ada task.</p>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <?php
                                $is_done = (int) $task['is_checked'] === 1;
                                $task_key = $task['task_source'] . '-' . (int) $task['task_id'];
                                $task_status = in_array((string) ($task['task_status'] ?? ''), ['todo', 'in_progress', 'done'], true)
                                    ? (string) $task['task_status']
                                    : ($is_done ? 'done' : 'todo');
                            ?>
                            <div class="todo-item-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($task['task_name']) ?></div>
                                        <small class="text-muted">
                                            <?= ucfirst($task['task_source']) ?> task
                                        </small>
                                    </div>
                                    <span class="task-pill <?= $task_status === 'done' ? 'done' : ($task_status === 'in_progress' ? 'doing' : 'todo') ?> task-pill-state"><?= $task_status === 'done' ? 'Done' : ($task_status === 'in_progress' ? 'In progress' : 'To do') ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-primary open-task-detail"
                                        data-title="<?= htmlspecialchars($task['task_name']) ?>"
                                        data-desc-target="task-desc-<?= $task_key ?>"
                                        data-attachment-path="<?= htmlspecialchars((string) ($task['attachment_path'] ?? '')) ?>"
                                        data-attachment-name="<?= htmlspecialchars((string) ($task['attachment_name'] ?? '')) ?>">
                                        Lihat detail
                                    </button>
                                    <div>
                                        <select class="form-select form-select-sm detail-task-status-select"
                                            data-transition-id="<?= (int) $transition['id'] ?>"
                                            data-task-id="<?= (int) $task['task_id'] ?>"
                                            data-task-source="<?= htmlspecialchars($task['task_source']) ?>"
                                            style="min-width: 140px;">
                                            <option value="todo" <?= $task_status === 'todo' ? 'selected' : '' ?>>To do</option>
                                            <option value="in_progress" <?= $task_status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                            <option value="done" <?= $task_status === 'done' ? 'selected' : '' ?>>Done</option>
                                        </select>
                                    </div>
                                </div>
                                <textarea id="task-desc-<?= $task_key ?>" class="d-none"><?= htmlspecialchars((string) ($task['description_html'] ?? '')) ?></textarea>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="taskDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="task-detail-title">Detail Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="task-detail-description"></div>
                <div id="task-detail-attachment-wrap" class="mt-3" style="display:none;">
                    <h6>Attachment</h6>
                    <a id="task-detail-attachment-link" href="#" target="_blank" class="btn btn-outline-secondary btn-sm"></a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addCustomTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" method="post" action="<?= base_url() ?>onboarding/add-custom-task" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Task Khusus Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="transition_id" value="<?= (int) $transition['id'] ?>">
                <div class="mb-3">
                    <label class="form-label">Nama Task</label>
                    <input type="text" name="task_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description_html" id="custom-task-description" class="form-control" rows="6"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Attachment (opsional)</label>
                    <input type="file" name="custom_attachment" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Task</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    $(document).on('click', '.open-task-detail', function() {
        const title = $(this).data('title') || 'Detail Task';
        const target = $(this).data('desc-target');
        const attachmentPath = $(this).data('attachment-path') || '';
        const attachmentName = $(this).data('attachment-name') || 'Attachment';
        const desc = target ? $('#' + target).val() : '';

        $('#task-detail-title').text(title);
        $('#task-detail-description').html(desc ? desc : '<p class="text-muted mb-0">Belum ada deskripsi.</p>');

        if (attachmentPath) {
            $('#task-detail-attachment-link').attr('href', '<?= base_url() ?>' + attachmentPath).text(attachmentName);
            $('#task-detail-attachment-wrap').show();
        } else {
            $('#task-detail-attachment-wrap').hide();
        }

        new bootstrap.Modal(document.getElementById('taskDetailModal')).show();
    });

    $(document).on('change', '.detail-task-status-select', function() {
        const select = $(this);
        const previousStatus = select.data('previous') || (select.val() === 'done' ? 'todo' : 'done');
        const taskStatus = select.val();
        const card = select.closest('.todo-item-card');
        const state = card.find('.task-pill-state');

        select.prop('disabled', true);
        $.post('<?= base_url() ?>onboarding/update-detail-task-status', {
            transition_id: select.data('transition-id'),
            task_id: select.data('task-id'),
            task_source: select.data('task-source'),
            task_status: taskStatus
        }, function(res) {
            if (!res || !res.success) {
                alert(res && res.message ? res.message : 'Gagal update task');
                select.val(previousStatus);
                return;
            }

            if (taskStatus === 'done') {
                state.removeClass('todo doing').addClass('done').text('Done');
            } else if (taskStatus === 'in_progress') {
                state.removeClass('done todo').addClass('doing').text('In progress');
            } else {
                state.removeClass('done doing').addClass('todo').text('To do');
            }

            $('#detail-progress-text').text(`${res.progress}% (${res.checked}/${res.total})`);
            $('#detail-progress-fill').css('width', `${res.progress}%`);
            select.data('previous', taskStatus);
        }, 'json').fail(function() {
            alert('Terjadi kendala saat update task');
            select.val(previousStatus);
        }).always(function() {
            select.prop('disabled', false);
        });
    });

    $('.detail-task-status-select').each(function() {
        $(this).data('previous', $(this).val());
    });

    if (typeof $.fn.summernote !== 'undefined') {
        $('#custom-task-description').summernote({
            height: 180,
            placeholder: 'Isi deskripsi task...'
        });
    }
})();
</script>
