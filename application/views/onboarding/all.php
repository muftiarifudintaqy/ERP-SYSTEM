<style>
    /* Table Responsive for Custom Scrollbar */
    .table-responsive[data-custom-scrollbar] {
        overflow-x: auto;
        white-space: nowrap;
    }

    .table-responsive[data-custom-scrollbar] table {
        min-width: max-content;
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
    .transition-progress {
        height: 8px;
        background: #f1f3f5;
        border-radius: 10px;
        overflow: hidden;
    }

    .transition-progress .bar {
        height: 100%;
        background: #1677ff;
    }

    .status-badge {
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 12px;
    }

    .status-ready {
        background: #e6f4ff;
        color: #1677ff;
    }

    .status-in-progress {
        background: #fff7e6;
        color: #d48806;
    }

    .status-completed {
        background: #f6ffed;
        color: #389e0d;
    }

    .table td,
    .table th {
        vertical-align: middle;
    }
</style>

<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Onboarding & Offboarding</h5>
            <?php if ($schema_ready): ?>
                <?php if ($can_edit): ?>
                    <a
                        href="<?= base_url() ?>onboarding/task-settings?type=<?= $active_tab ?>"
                        class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-gear me-1"></i>Settings
                    </a>
                <?php endif; ?>
            <?php endif; ?>
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
                <ul class="nav nav-tabs mb-3" id="transitionTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $active_tab === 'onboarding' ? 'active' : '' ?>"
                            id="onboarding-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#onboarding-panel"
                            type="button"
                            role="tab"
                            aria-controls="onboarding-panel"
                            aria-selected="<?= $active_tab === 'onboarding' ? 'true' : 'false' ?>"
                            data-type="onboarding">
                            Onboarding
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $active_tab === 'offboarding' ? 'active' : '' ?>"
                            id="offboarding-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#offboarding-panel"
                            type="button"
                            role="tab"
                            aria-controls="offboarding-panel"
                            aria-selected="<?= $active_tab === 'offboarding' ? 'true' : 'false' ?>"
                            data-type="offboarding">
                            Offboarding
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="transitionTabContent">
                    <div class="tab-pane fade <?= $active_tab === 'onboarding' ? 'show active' : '' ?>" id="onboarding-panel" role="tabpanel" aria-labelledby="onboarding-tab">
                        <?php $this->load->view('onboarding/partial_table', [
                            'transition_type' => 'onboarding',
                            'rows' => $transitions_onboarding,
                            'keyword' => $keyword_onboarding,
                            'status' => $status_onboarding,
                            'can_create' => $can_create,
                            'can_delete' => $can_delete
                        ]); ?>
                    </div>

                    <div class="tab-pane fade <?= $active_tab === 'offboarding' ? 'show active' : '' ?>" id="offboarding-panel" role="tabpanel" aria-labelledby="offboarding-tab">
                        <?php $this->load->view('onboarding/partial_table', [
                            'transition_type' => 'offboarding',
                            'rows' => $transitions_offboarding,
                            'keyword' => $keyword_offboarding,
                            'status' => $status_offboarding,
                            'can_create' => $can_create,
                            'can_delete' => $can_delete
                        ]); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($schema_ready): ?>
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="post" action="<?= base_url() ?>onboarding/add-employees">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="transition_type" id="add-transition-type" value="onboarding">
                    <div class="mb-3">
                        <input type="text" id="employee-search-input" class="form-control" placeholder="Cari nama atau email...">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="40"><input type="checkbox" id="check-all-employee"></th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Posisi</th>
                                </tr>
                            </thead>
                            <tbody id="employee-pick-table">
                                <?php foreach ($employees as $employee): ?>
                                    <tr data-search="<?= strtolower(htmlspecialchars($employee['full_name'] . ' ' . $employee['email'])) ?>">
                                        <td>
                                            <input class="employee-checkbox" type="checkbox" name="user_ids[]" value="<?= (int) $employee['id'] ?>">
                                        </td>
                                        <td><?= htmlspecialchars($employee['full_name']) ?></td>
                                        <td><?= htmlspecialchars($employee['email']) ?></td>
                                        <td><?= htmlspecialchars($employee['position_name']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            document.addEventListener('click', function(e) {
                const addButton = e.target.closest('.open-add-employee-modal');
                if (addButton) {
                    const type = addButton.getAttribute('data-type') || 'onboarding';
                    document.getElementById('add-transition-type').value = type;
                    document.getElementById('check-all-employee').checked = false;
                    document.querySelectorAll('.employee-checkbox').forEach(chk => chk.checked = false);
                }
            });

            document.getElementById('check-all-employee')?.addEventListener('change', function() {
                document.querySelectorAll('.employee-checkbox').forEach(chk => chk.checked = this.checked);
            });

            document.getElementById('employee-search-input')?.addEventListener('input', function() {
                const keyword = this.value.trim().toLowerCase();
                document.querySelectorAll('#employee-pick-table tr').forEach(row => {
                    const haystack = row.getAttribute('data-search') || '';
                    row.style.display = haystack.includes(keyword) ? '' : 'none';
                });
            });

            document.querySelectorAll('#transitionTabs button[data-bs-toggle="tab"]').forEach(btn => {
                btn.addEventListener('shown.bs.tab', function(event) {
                    const type = event.target.getAttribute('data-type');
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', type);
                    window.history.replaceState({}, '', url.toString());
                });
            });

            document.addEventListener('submit', function(event) {
                const form = event.target.closest('.js-remove-employee-form');
                if (!form || form.dataset.confirmed === '1') {
                    return;
                }

                event.preventDefault();

                if (typeof Swal === 'undefined') {
                    if (window.confirm('Hapus data ini dari daftar?')) {
                        form.dataset.confirmed = '1';
                        form.submit();
                    }
                    return;
                }

                Swal.fire({
                    title: 'Hapus employee?',
                    text: 'Data employee akan dihapus dari daftar onboarding/offboarding.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.dataset.confirmed = '1';
                        form.submit();
                    }
                });
            });
        })();
    </script>
<?php endif; ?>
