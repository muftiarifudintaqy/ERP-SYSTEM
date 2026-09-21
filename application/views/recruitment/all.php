<!-- Tippy.js for tooltips -->
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
    .recruitment-custom-scrollbar {
        width: 100%;
        height: 12px;
        background: rgba(248, 249, 250, 0.8);
        border: 1px solid rgba(222, 226, 230, 0.6);
        border-radius: 6px;
        margin-top: 8px;
        position: relative;
        cursor: pointer;
        display: none;
    }

    .recruitment-custom-scrollbar .scrollbar-track {
        width: 100%;
        height: 100%;
        position: relative;
    }

    .recruitment-custom-scrollbar .scrollbar-thumb {
        height: 100%;
        background: linear-gradient(180deg, rgba(173, 181, 189, 0.7) 0%, rgba(134, 142, 150, 0.7) 100%);
        border-radius: 6px;
        cursor: grab;
        position: absolute;
        left: 0;
        transition: background 0.2s;
        min-width: 50px;
    }

    .recruitment-custom-scrollbar .scrollbar-thumb:hover {
        background: linear-gradient(180deg, rgba(134, 142, 150, 0.8) 0%, rgba(108, 117, 125, 0.8) 100%);
    }

    .recruitment-custom-scrollbar .scrollbar-thumb:active {
        cursor: grabbing;
        background: linear-gradient(180deg, rgba(108, 117, 125, 0.9) 0%, rgba(73, 80, 87, 0.9) 100%);
    }

    .recruitment-custom-scrollbar .scrollbar-thumb::before {
        content: '\22ee\22ee\22ee';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: rgba(255, 255, 255, 0.5);
        font-size: 8px;
        letter-spacing: 2px;
    }

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
</style>
<style>
    :root {
        --primary-color: #3b82f6;
        --secondary-color: #6b7280;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --light-bg: #f8fafc;
        --border-color: #e5e7eb;
        --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        margin-bottom: 2rem;
        overflow: hidden;
        background: white;
    }

    .card-header {
        background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        border-bottom: 1px solid var(--border-color);
        padding: 1.5rem 2rem;
    }

    .card-header h5 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .card-header h5 i {
        color: var(--primary-color);
        margin-right: 0.75rem;
    }

    .card-body {
        padding: 2rem;
    }

    .info-group {
        margin-bottom: 2rem;
    }

    .info-group:last-child {
        margin-bottom: 0;
    }

    .info-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--secondary-color);
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .info-value {
        font-size: 1rem;
        color: #1f2937;
        margin: 0 0 1rem 0;
        line-height: 1.5;
    }

    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .contact-item {
        display: flex;
        align-items: center;
        font-size: 0.95rem;
        color: #374151;
    }

    .contact-item i {
        color: var(--primary-color);
        margin-right: 0.75rem;
        width: 16px;
    }

    .status-badges {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .badge {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .bg-info {
        background-color: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }

    .bg-warning {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
    }

    .bg-danger {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
    }

    .bg-light {
        background-color: #f3f4f6;
        color: #4b5563;
        border: 1px solid var(--border-color);
    }

    .demographics {
        color: #4b5563;
        line-height: 1.6;
    }

    .work-preference {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        color: #4b5563;
    }

    .salary {
        font-weight: 600;
        color: var(--success-color);
        font-size: 1.125rem;
    }

    .keyword-tags {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .keyword-tag {
        background: #f0f9ff;
        color: #0369a1;
        padding: 0.375rem 0.75rem;
        border-radius: 16px;
        font-size: 0.875rem;
        font-weight: 500;
        border: 1px solid #bae6fd;
    }

    .hr-notes-section {
        background: linear-gradient(135deg, #fafbfc 0%, #f3f4f6 100%);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1.5rem;
    }

    .timestamp {
        font-size: 0.875rem;
        color: var(--secondary-color);
        font-weight: 500;
    }

    .section-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--border-color), transparent);
        margin: 1.5rem 0;
    }

    .empty-state {
        color: #9ca3af;
        font-style: italic;
    }

    @media (max-width: 768px) {
        .card-header {
            padding: 1rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 1rem;
        }
        
        .status-badges {
            justify-content: flex-start;
        }
    }

    .badge-testcase_1 {
        background-color: #d7bdf2;
        color: #4b0082;
    }
    .badge-interview_hr {
        background-color: #ffe7a0;
        color: #7a5900;
    }
    .badge-interview_user {
        background-color: #cde8ff;
        color: #0d6efd;
    }
    .badge-selected {
        background-color: #2e7d32;
        color: white;
    }
    .badge-rejected {
        background-color: #b71c1c;
        color: white;
    }
    .badge-pertimbangan {
        background-color: #f9c998;
        color: #8b4513;
    }
    .badge-default {
        background-color: #e0e0e0;
        color: #666;
    }
    .badge-additional {
        background-color: #f5f5f5;
        color: #595959;
    }
    .status-tabs-pager {
        display: flex;
        align-items: stretch;
        gap: 4px;
    }
    .status-tabs-viewport {
        flex: 1 1 auto;
        min-width: 0;
        overflow-x: hidden;
        overflow-y: hidden;
    }
    #status-tabs {
        flex-wrap: nowrap;
    }
    #status-tabs .nav-item {
        flex: 0 0 auto;
    }
    #status-tabs .nav-link {
        white-space: nowrap;
    }
    .status-tabs-nav {
        flex: 0 0 auto;
        align-self: stretch;
        margin-bottom: 8px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: rgba(0, 0, 0, 0.65);
        border-radius: 8px;
        width: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        line-height: 1;
        transition: background-color 0.15s, color 0.15s;
    }
    .status-tabs-nav:hover {
        background: #f0f0f0;
        color: #1890ff;
    }
    .status-tabs-nav:disabled {
        cursor: not-allowed;
        opacity: 0.45;
    }

</style>

<div class="container-fluid py-3">
    <?php 
        $data['user'] = $_SESSION['user'];
        
        if (in_array($data['user']['role'], array('1', '2', '3'))) {
            $this->load->view('recruitment/menu');
        }
    ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Recruitment Management</h5>
                <div>
                    <form method="GET" action="<?= base_url() ?>recruitment" style="display: inline-flex; gap: 8px; align-items: center;">
                        <?php 
                        // Preserve existing query parameters
                        foreach ($_GET as $key => $value) {
                            if ($key != 'position_filter' && $key != 'info_loker_filter') {
                                echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value).'">';
                            }
                        }
                        ?>
                        <?php if (empty($_GET['status_filter']) && !empty($status_filter_raw)): ?>
                            <input type="hidden" name="status_filter" value="<?= htmlspecialchars($status_filter_raw) ?>">
                        <?php endif; ?>
                        <select name="position_filter" class="form-select" style="min-width: 200px; height: 32px; padding: 4px 11px; font-size: 14px;" onchange="this.form.submit()">
                            <option value="">Semua Posisi</option>
                            <?php
                            $current_position_filter = $_GET['position_filter'] ?? '';
                            if (!empty($positions)) {
                                foreach ($positions as $position) {
                                    $pos_name = is_array($position) ? $position['posisi_dilamar'] : $position->posisi_dilamar;
                                    $selected = ($current_position_filter == $pos_name) ? 'selected' : '';
                                    echo '<option value="'.htmlspecialchars($pos_name).'" '.$selected.'>'.htmlspecialchars($pos_name).'</option>';
                                }
                            }
                            ?>
                        </select>
                        <select name="info_loker_filter" class="form-select" style="min-width: 200px; height: 32px; padding: 4px 11px; font-size: 14px;" onchange="this.form.submit()">
                            <?php $current_info_loker_filter = $_GET['info_loker_filter'] ?? ''; ?>
                            <option value="">Semua Info Loker</option>
                            <?php
                            if (!empty($info_lokers)) {
                                foreach ($info_lokers as $info_loker) {
                                    $info_name = is_array($info_loker) ? ($info_loker['info_loker_name'] ?? '') : ($info_loker->info_loker_name ?? '');
                                    if ($info_name === '') {
                                        continue;
                                    }
                                    $selected = ($current_info_loker_filter == $info_name) ? 'selected' : '';
                                    echo '<option value="'.htmlspecialchars($info_name).'" '.$selected.'>'.htmlspecialchars($info_name).'</option>';
                                }
                            }
                            ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Status Filter Tabs -->
        <?php
        $status_tabs_all = $status_tabs_all ?? [];
        ?>
        <div class="recruitment-tabs" style="background: #fafafa; border-bottom: 2px solid #f0f0f0; padding: 12px 24px 0;">
            <div class="status-tabs-pager">
                <button type="button" class="status-tabs-nav" id="statusTabsPrev" aria-label="Sebelumnya" style="display:none;">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <div class="status-tabs-viewport" id="statusTabsViewport">
                    <ul class="nav nav-tabs border-0" id="status-tabs" style="gap: 8px;"></ul>
                </div>
                <button type="button" class="status-tabs-nav" id="statusTabsNext" aria-label="Berikutnya" style="display:none;">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <form action="" class="search-form">
                <div class="row g-2 mb-3 align-items-center">
                <div class="col-md-8">
                    <div class="input-group" style="box-shadow: 0 2px 0 rgba(0,0,0,0.02);">
                        <div class="dropdown" style="margin-right: 10px;">
                            <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false"
                                style="border: 1px solid #d9d9d9; border-radius: 2px; color: rgba(0,0,0,0.65); background-color: #fff; height: 32px; padding: 4px 11px;">
                                <span style="margin-right: 8px;"><?= $keyword_category ?? 'Nama' ?></span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton"
                                style="position: absolute; background-color: white; min-width: 160px; z-index: 9999; border-radius: 2px; box-shadow: 0 3px 6px -4px rgba(0,0,0,0.12), 0 6px 16px 0 rgba(0,0,0,0.08), 0 9px 28px 8px rgba(0,0,0,0.05); padding: 4px 0; margin-top: 2px; left: 0;">
                                <?php
                                $arr = array('Nama', 'Posisi');
                                foreach ($arr as $k => $val) {
                                    $active = (($keyword_category ?? 'Nama') == $val) ? 'background-color: #e6f7ff; color: #1890ff;' : '';
                                ?>
                                    <li>
                                        <a class="dropdown-item"
                                            href="<?= base_url() ?>/recruitment?keyword_category=<?= $val ?><?= !empty($_GET['keyword']) ? '&keyword=' . $_GET['keyword'] : '' ?><?= !empty($status_filter_raw) ? '&status_filter=' . $status_filter_raw : '' ?><?= !empty($position_filter) ? '&position_filter=' . urlencode($position_filter) : '' ?><?= !empty($info_loker_filter) ? '&info_loker_filter=' . urlencode($info_loker_filter) : '' ?>"
                                            style="padding: 5px 12px; font-size: 14px; line-height: 22px; <?= $active ?>">
                                            <?= $val ?>
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                        <input type="hidden" name="keyword_category" value="<?= $keyword_category ?? 'Nama' ?>">
                        <?php if (!empty($status_filter)): ?>
                            <input type="hidden" name="status_filter" value="<?= htmlspecialchars($status_filter) ?>">
                        <?php elseif (!empty($status_filter_raw) && $status_filter_raw === 'all'): ?>
                            <input type="hidden" name="status_filter" value="all">
                        <?php endif; ?>
                        <?php if (!empty($position_filter)): ?>
                            <input type="hidden" name="position_filter" value="<?= htmlspecialchars($position_filter) ?>">
                        <?php endif; ?>
                        <?php if (!empty($info_loker_filter)): ?>
                            <input type="hidden" name="info_loker_filter" value="<?= htmlspecialchars($info_loker_filter) ?>">
                        <?php endif; ?>
                        <input type="text" name="keyword" class="form-control" placeholder="Search..." value="<?= isset($_GET['keyword']) ? $_GET['keyword'] : '' ?>"
                            style="border: 1px solid #d9d9d9; box-shadow: none; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
                        
                        <button class="btn btn-primary" type="submit"
                            style="border-radius: 2px; height: 32px; padding: 0 15px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>

               <div class="col-md-4 text-end d-flex justify-content-end gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#infoLokerModal"
                        style="height: 32px; line-height: 20px; border-radius: 2px; padding: 0 15px; display: inline-flex; align-items: center;">
                        <i class="bi bi-megaphone me-1"></i> Info Loker
                    </button>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recruitmentStatusModal"
                        style="height: 32px; line-height: 20px; border-radius: 2px; padding: 0 15px; display: inline-flex; align-items: center;">
                        <i class="bi bi-tags me-1"></i> Status
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#positionModal"
                        style="height: 32px; line-height: 20px; border-radius: 2px; padding: 0 15px; display: inline-flex; align-items: center;">
                        <i class="bi bi-plus-lg me-1"></i> Posisi Rekrutmen
                    </button>
                </div>
            </div>


                <?php if (!empty($notif)): ?>
                    <div class="alert alert-info" style="display: flex; align-items: center;">
                        <i class="bi bi-info-circle me-2"></i>
                        <span><?= strip_tags($notif) ?></span>
                    </div>
                <?php endif; ?>
            </form>

            <?php
            $is_pending_filter = (($status_filter ?? '') === 'pending');
            $is_selected_filter = (($status_filter ?? '') === 'selected');
            $current_status_meta = (!empty($status_filter) && !empty($recruitment_status_options[$status_filter])) ? $recruitment_status_options[$status_filter] : null;
            $hide_approval_column = $is_selected_filter || ($current_status_meta && empty($current_status_meta['requires_approval']));
            ?>
            <div id="recruitmentTableWrapper" class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="text-center" style="min-width: 50px;">#</th>
                            <th class="text-center" style="min-width: 120px;">Tanggal Apply</th>
                            <th class="text-center" style="min-width: 180px;">Biodata</th>
                            <th class="text-center" style="min-width: 80px;">WA</th>
                            <th class="text-center" style="min-width: 150px;">Posisi Dilamar</th>
                            <th class="text-center" style="min-width: 150px;">Info Loker</th>
                            <th class="text-center" style="min-width: 200px;">Riwayat Pekerjaan</th>
                            <th class="text-center" style="min-width: 100px;">Mode Kerja</th>
                            <th class="text-center" style="min-width: 130px;">Ekspektasi Gaji</th>
                            <?php if (!$is_pending_filter): ?>
                                <th class="text-center" style="min-width: 150px;">Status Recruitment</th>
                                <?php if (!$hide_approval_column): ?>
                                    <th class="text-center" style="min-width: 140px;">Status Approval</th>
                                <?php endif; ?>
                                <th class="text-center notes-col" style="min-width: 100px;">Notes</th>
                            <?php endif; ?>
                            <?php if ($is_pending_filter): ?>
                                <th class="text-center action-col" style="min-width: 100px;">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="tbody">

                    </tbody>
                </table>
            </div>

            <!-- Custom Horizontal Scrollbar -->
            <div id="recruitmentCustomScrollbar" class="recruitment-custom-scrollbar">
                <div class="scrollbar-track">
                    <div class="scrollbar-thumb"></div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <div>
                    <?= $pagination ?>
                </div>
                <div>
                    <?php
                    $per_page_options = [5, 10, 20, 50, 100, 500];
                    $limit = $_GET['limit'] ?? 10;
                    if (!in_array($limit, $per_page_options)) {
                        $limit = 10;
                    }

                    $query_params = $_GET;
                    unset($query_params['limit']);
                    ?>

                    <form method="GET" action="">
                        <?php foreach ($query_params as $key => $value): ?>
                            <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                        <?php endforeach; ?>

                        <select class="form-control select2" name="limit" id="limit"
                            onchange="this.form.submit()">
                            <?php foreach ($per_page_options as $option): ?>
                                <option value="<?= $option ?>" <?= ($limit == $option) ? 'selected' : '' ?>>
                                    <?= $option ?> / Halaman
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Notes Editor -->
<div id="notesEditor" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Notes HR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea id="notesTextarea" class="form-control" rows="5" style="height: 20vh;"></textarea>
                <input type="hidden" id="notesApplicantId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveNotesBtn">Simpan</button>
            </div>
        </div>
    </div>
</div>
<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-body p-0">
                <!-- Tab Navigation -->
                <ul class="nav nav-pills nav-justified bg-light p-3 mb-0 border-bottom" id="detailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="personal-info-tab" data-bs-toggle="pill" data-bs-target="#personal-info" type="button" role="tab">
                            <i class="bi bi-person-circle me-1"></i>Informasi Pribadi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="resume-tab" data-bs-toggle="pill" data-bs-target="#resume" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1"></i>Resume/CV
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="history-tab" data-bs-toggle="pill" data-bs-target="#history" type="button" role="tab">
                            <i class="bi bi-clock-history me-1"></i>Riwayat Lamaran
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content p-4" id="detailTabContent">
                    <!-- Personal Info Tab -->
                    <div class="tab-pane fade show active" id="personal-info" role="tabpanel">
                        <div id="personal-info-content">
                            <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resume Tab -->
                    <div class="tab-pane fade" id="resume" role="tabpanel">
                        <div id="resume-content">
                            <div class="text-center p-4">
                                <i class="bi bi-file-earmark-text" style="font-size: 3rem; color: #6c757d;"></i>
                                <p class="text-muted mt-2">Klik tab ini setelah memilih pelamar untuk melihat CV/Resume</p>
                            </div>
                        </div>
                    </div>

                    <!-- History Tab -->
                    <div class="tab-pane fade" id="history" role="tabpanel">
                        <div id="history-content">
                            <div class="text-center p-4">
                                <i class="bi bi-clock-history" style="font-size: 3rem; color: #6c757d;"></i>
                                <p class="text-muted mt-2">Klik tab ini setelah memilih pelamar untuk melihat riwayat lamaran</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Template Pesan -->
<div class="modal fade" id="chatTemplateModal" tabindex="-1" aria-labelledby="chatTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chatTemplateModalLabel">
                    <i class="bi bi-chat-left-text me-2"></i>Template Pesan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="message-template-list-view">
                    <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
                        <span class="text-muted small">Pilih template untuk dikirim, atau buat yang baru.</span>
                        <button type="button" class="btn btn-primary btn-sm" id="btnAddMessageTemplate">
                            <i class="bi bi-plus-lg me-1"></i>Tambah template
                        </button>
                    </div>
                    <div id="message-template-list" class="message-template-list">
                        <div class="text-center text-muted py-4">Memuat template...</div>
                    </div>
                </div>
                <div id="message-template-form-view" style="display:none;">
                    <form id="messageTemplateForm" class="p-4">
                        <input type="hidden" id="message_template_key" name="template_key">
                        <div class="mb-3">
                            <label for="message_template_name" class="form-label fw-semibold">Nama Template</label>
                            <input type="text" class="form-control" id="message_template_name" name="template_name" placeholder="Contoh: Follow Up Testcase">
                        </div>
                        <div class="mb-3">
                            <label for="message_template_text" class="form-label fw-semibold">Isi Pesan</label>
                            <textarea class="form-control" id="message_template_text" name="template_text" rows="9" placeholder="Tulis pesan. Placeholder: {name}, {position}, {company}"></textarea>
                        </div>
                        <div class="small text-muted mb-3">
                            Placeholder tersedia: <code>{name}</code>, <code>{position}</code>, <code>{company}</code>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="btnCancelMessageTemplate">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Template</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Kelola Posisi -->
<div class="modal fade" id="positionModal" tabindex="-1" aria-labelledby="positionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="positionModalLabel">
                    <i class="bi bi-briefcase me-2"></i>Kelola Posisi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= site_url('recruitment/save_position') ?>" method="post">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="position_name" name="position_name" placeholder="Tambah Posisi Baru" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
                
                <div class="mt-4">
                    <h6>List Posisi</h6>
                    <div class="d-flex flex-wrap gap-2 mt-3" id="positions-list">
                        <!-- Positions will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Kelola Info Loker -->
<div class="modal fade" id="infoLokerModal" tabindex="-1" aria-labelledby="infoLokerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="infoLokerModalLabel">
                    <i class="bi bi-megaphone me-2"></i>Kelola Info Loker
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= site_url('recruitment/save_info_loker') ?>" method="post">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="info_loker_name" name="info_loker_name" placeholder="Tambah Sumber Info Loker Baru" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
                
                <div class="mt-4">
                    <h6>List Info Loker</h6>
                    <div class="d-flex flex-wrap gap-2 mt-3" id="info-lokers-list">
                        <!-- Info loker will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Kelola Status Recruitment -->
<div class="modal fade" id="recruitmentStatusModal" tabindex="-1" aria-labelledby="recruitmentStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recruitmentStatusModalLabel">
                    <i class="bi bi-tags me-2"></i>Kelola Status Recruitment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= site_url('recruitment/save_recruitment_status') ?>" method="post" id="recruitmentStatusForm">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="status_name" name="status_name" placeholder="Tambah Status Baru" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" class="form-control" id="short_label" name="short_label" placeholder="Label Singkat, contoh: Talent Pool">
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
                
                <div class="mt-4">
                    <h6>List Status Tambahan</h6>
                    <div class="d-flex flex-wrap gap-2 mt-3" id="recruitment-statuses-list">
                        <!-- Status will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<style>
    .badge-testcase_1 {
        background-color: #d7bdf2;
        color: #4b0082;
    }

    .badge-interview_hr {
        background-color: #ffe7a0;
        color: #7a5900;
    }

    .badge-interview_user {
        background-color: #cde8ff;
        color: #0d6efd;
    }

    .badge-selected {
        background-color: #2e7d32;
        color: white;
    }

    .badge-rejected {
        background-color: #b71c1c;
        color: white;
    }

    .badge-pertimbangan {
        background-color: #f9c998;
        color: #8b4513;
    }
    .badge-additional {
        background-color: #f5f5f5;
        color: #595959;
    }
    #chatTemplateModal .modal-content {
        height: 78vh;
        max-height: 720px;
        overflow: hidden;
    }
    #chatTemplateModal .modal-body {
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    #message-template-list-view,
    #message-template-form-view {
        flex: 1;
        min-height: 0;
        flex-direction: column;
    }
    .message-template-list {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
    }
    #message-template-form-view {
        overflow-y: auto;
    }
    .message-template-item {
        display: flex;
        gap: 16px;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }
    .message-template-item:hover {
        background: #f8fafc;
    }
    .message-template-content {
        min-width: 0;
        flex: 1;
        cursor: pointer;
    }
    .message-template-title {
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 6px;
    }
    .message-template-text {
        color: #374151;
        font-size: 14px;
        line-height: 1.5;
    }
    .message-template-text p {
        margin: 0 0 6px;
    }
    .message-template-text p:last-child {
        margin-bottom: 0;
    }
    .message-template-text ul,
    .message-template-text ol {
        margin: 0 0 6px;
        padding-left: 20px;
    }
    .message-template-actions {
        display: flex;
        gap: 10px;
        flex: 0 0 auto;
    }
    .message-template-actions button {
        border: 0;
        background: transparent;
        color: #6b7280;
        padding: 4px;
        font-size: 20px;
        line-height: 1;
    }
    .message-template-actions button:hover {
        color: #0d6efd;
    }

    .modal {
        z-index: 99999;
    }

    #chatTemplateModal .tox-tinymce {
        border: 1px solid #d9d9d9 !important;
        border-radius: 6px !important;
    }

    #chatTemplateModal .tox .tox-edit-area__iframe {
        background: #fff !important;
    }

    .tox-tinymce-aux,
    .tox-menu,
    .tox-dialog-wrap,
    .tox-notifications-container {
        z-index: 100500 !important;
    }
    
    .editable-notes {
        transition: background-color 0.2s;
    }

    .editable-notes .notes-text {
        display: inline-block;
        max-width: 100px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }
    
    .editable-notes:hover {
        background-color: #f5f5f5;
    }

    .clickable-row:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }

    .nav-pills .nav-link {
        color: #495057;
        border-radius: 0.5rem;
        margin: 0 0.25rem;
        font-weight: 500;
    }

    .nav-pills .nav-link.active {
        background-color: #0d6efd;
    }

    .nav-pills .nav-link:hover:not(.active) {
        background-color: #e9ecef;
    }

    .tab-content {
        min-height: 500px;
    }

    .modal-xl {
        max-width: 1200px;
    }

    .iframe-container {
        position: relative;
        width: 100%;
        height: 700px;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .iframe-container iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
</style>

<script>
    /**
     * Loads recruitment data via AJAX
     */
    function loadRecruitmentData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/recruitment/item<?= $param ?>",
            beforeSend: function() {
                $('#tbody').append('<tr><td colspan="13" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
            },
            success: function(data) {
                $('#tbody tr:last').remove(); // Remove loading indicator
                $('#tbody').append(data);
                $('#tbody .editable-notes').each(function () {
                    const $text = $(this).find('.notes-text');
                    const text = $text.text().trim();
                    if (text === 'Klik untuk menambahkan notes') {
                        $text.text('Add Note');
                    }
                });
                
                // Initialize tooltips after data is loaded
                setTimeout(function() {
                    if (typeof window.initializeNotesTooltips === 'function') {
                        window.initializeNotesTooltips();
                    }
                }, 100);
            },
            error: function(xhr, status, error) {
                console.error('Error loading data:', error);
                $('#tbody').append('<tr><td colspan="13" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
            }
        });
        
        // Load tab counts
        loadTabCounts();
    }
    
    /**
     * Load counts for each status tab
     */
        function loadTabCounts() {
            $.ajax({
                type: 'GET',
                url: "<?= base_url() ?>/recruitment/get_status_counts<?= $param ?>",
                success: function(response) {
                if (response.status === 'success') {
                    latestStatusTabCounts = response.data || {};
                    applyStatusTabCounts();
                    if (typeof updateStatusTabsPager === 'function') {
                        updateStatusTabsPager();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading tab counts:', error);
            }
            });
        }

        function showRecruitmentToast(message) {
            if (typeof $.toast === 'function') {
                $.toast({
                    heading: 'Informasi',
                    text: message,
                    showHideTransition: 'slide',
                    icon: 'success',
                    position: 'top-right',
                    loaderBg: '#def7f0',
                    hideAfter: 2500,
                });
            } else {
                alert(message);
            }
        }

        const recruitmentCompanyName = 'PT Montera Sinergi Bersama';
        const defaultChatTemplates = <?= json_encode($chat_templates ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        window.recruitmentChatTemplates = Object.assign({}, defaultChatTemplates);
        let chatTemplateEditorsReady = false;

        function escapeHtmlText(text) {
            return (text || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function textToEditorHtml(text) {
            const normalized = String(text || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');
            let html = escapeHtmlText(normalized);

            // WhatsApp-style markers -> rich preview in TinyMCE
            html = html
                .replace(/\*([^\n*][^*\n]*?)\*/g, '<strong>$1</strong>')
                .replace(/_([^\n_][^_\n]*?)_/g, '<em>$1</em>')
                .replace(/~([^\n~][^~\n]*?)~/g, '<s>$1</s>');

            const paragraphs = html.split(/\n{2,}/).map(function(block) {
                return '<p>' + block.replace(/\n/g, '<br>') + '</p>';
            }).join('');

            return paragraphs || '<p><br></p>';
        }

        function htmlToWhatsAppText(html) {
            const parser = new DOMParser();
            const doc = parser.parseFromString('<div id="wa-root">' + (html || '') + '</div>', 'text/html');
            const root = doc.getElementById('wa-root');
            if (!root) return '';

            const blockTags = new Set(['p', 'div', 'section', 'article', 'blockquote', 'pre']);

            function walk(node) {
                if (!node) return '';
                if (node.nodeType === Node.TEXT_NODE) {
                    return (node.nodeValue || '').replace(/\u00a0/g, ' ');
                }
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return '';
                }

                const tag = node.tagName.toLowerCase();
                if (tag === 'br') return '\n';

                let content = '';
                const children = Array.from(node.childNodes || []);
                for (let i = 0; i < children.length; i++) {
                    content += walk(children[i]);
                }

                if (tag === 'strong' || tag === 'b') return '*' + content + '*';
                if (tag === 'em' || tag === 'i') return '_' + content + '_';
                if (tag === 's' || tag === 'strike' || tag === 'del') return '~' + content + '~';
                if (tag === 'li') return '• ' + content + '\n';
                if (tag === 'ul' || tag === 'ol') return content + (content.endsWith('\n') ? '' : '\n');
                if (blockTags.has(tag)) return content + '\n';
                return content;
            }

            let output = walk(root);
            output = output
                .replace(/\r\n/g, '\n')
                .replace(/\r/g, '\n')
                .replace(/\u200b/g, '')
                .replace(/[ \t]+\n/g, '\n')
                .replace(/\n{3,}/g, '\n\n')
                .trim();

            return output;
        }

        function getChatTemplateEditorText(selector) {
            if (typeof tinymce !== 'undefined') {
                const editor = tinymce.get(selector.replace('#', ''));
                if (editor) {
                    return htmlToWhatsAppText(editor.getContent());
                }
            }
            return ($(selector).val() || '').trim();
        }

        function setChatTemplateEditorText(selector, text) {
            const normalizedText = String(text || '');
            if (typeof tinymce !== 'undefined') {
                const editor = tinymce.get(selector.replace('#', ''));
                if (editor) {
                    editor.setContent(textToEditorHtml(normalizedText));
                    return;
                }
            }
            $(selector).val(normalizedText);
        }

        function initChatTemplateEditors() {
            if (chatTemplateEditorsReady || typeof tinymce === 'undefined') return;
            chatTemplateEditorsReady = true;

            tinymce.init({
                selector: '#chatTemplateInterviewHr, #chatTemplateRejected',
                menubar: false,
                branding: false,
                height: 260,
                plugins: 'lists link emoticons autoresize textpattern',
                toolbar: 'undo redo | bold italic strikethrough | bullist numlist | emoticons | removeformat',
                forced_root_block: 'p',
                textpattern_patterns: [
                    { start: '*', end: '*', format: 'bold' },
                    { start: '_', end: '_', format: 'italic' },
                    { start: '~', end: '~', format: 'strikethrough' }
                ],
                setup: function(editor) {
                    editor.on('init', function() {
                        const currentTemplates = window.recruitmentChatTemplates || defaultChatTemplates;
                        if (editor.id === 'chatTemplateInterviewHr') {
                            editor.setContent(textToEditorHtml(currentTemplates.interview_hr || ''));
                        } else if (editor.id === 'chatTemplateRejected') {
                            editor.setContent(textToEditorHtml(currentTemplates.rejected || ''));
                        }
                    });
                }
            });
        }

        let messageTemplateEditorReady = false;
        function initMessageTemplateEditor() {
            if (messageTemplateEditorReady || typeof tinymce === 'undefined') return;
            messageTemplateEditorReady = true;

            tinymce.init({
                selector: '#message_template_text',
                menubar: false,
                branding: false,
                height: 280,
                plugins: 'lists link emoticons textpattern',
                toolbar: 'undo redo | bold italic strikethrough | bullist numlist | emoticons | removeformat',
                forced_root_block: 'p',
                textpattern_patterns: [
                    { start: '*', end: '*', format: 'bold' },
                    { start: '_', end: '_', format: 'italic' },
                    { start: '~', end: '~', format: 'strikethrough' }
                ]
            });
        }

        // Allow TinyMCE dialogs (emoticons/link) to receive focus inside Bootstrap modal
        document.addEventListener('focusin', function(e) {
            if (e.target.closest && e.target.closest('.tox-tinymce-aux, .tox-dialog, .tox-menu') !== null) {
                e.stopImmediatePropagation();
            }
        });

        function buildRecruitmentMessage(templateKey, context = {}) {
            const templates = window.recruitmentChatTemplates || {};
            const template = templates[templateKey] || defaultChatTemplates[templateKey] || '';
            const merged = Object.assign({
                name: 'kak',
                position: 'Montera',
                company: recruitmentCompanyName
            }, context);

            return template.replace(/\{(name|position|company)\}/g, function(match, key) {
                return (merged[key] !== undefined && merged[key] !== null) ? String(merged[key]) : '';
            });
        }

        function openRecruitmentWhatsApp(waNumber, templateKey, context = {}) {
            if (!waNumber) {
                alert('Nomor WhatsApp tidak ditemukan untuk kandidat ini.');
                return;
            }
            const message = buildRecruitmentMessage(templateKey, context);
            const normalizedMessage = String(message || '')
                .replace(/\r\n/g, '\n')
                .replace(/\r/g, '\n');
            const waUrl = `https://api.whatsapp.com/send/?phone=${waNumber}&text=${encodeURIComponent(normalizedMessage)}&type=phone_number&app_absent=0`;
            window.open(waUrl, '_blank');
        }

        window.getRecruitmentMessage = buildRecruitmentMessage;
        window.openRecruitmentWhatsApp = openRecruitmentWhatsApp;

        const statusTabsAll = <?= json_encode($status_tabs_all) ?>;
        const statusTabsSelected = <?= json_encode($status_tabs_selected ?? []) ?>;
        const currentStatusFilter = <?= json_encode($status_filter_raw ?? 'pending') ?>;
        const statusTabsVisibleLimit = 7;
        let activeStatusTabsSelected = [];
        let statusTabsStartIndex = 0;
        let latestStatusTabCounts = {};

        function buildStatusFilterUrl(status) {
            const params = new URLSearchParams(window.location.search);
            params.set('status_filter', status);
            return `<?= base_url() ?>recruitment?${params.toString()}`;
        }

        function getSelectedStatusTabKeys(selected) {
            const selectedSet = new Set(selected);
            return Object.keys(statusTabsAll).filter((key) => selectedSet.has(key));
        }

        function createStatusTabItem(key, includeBadgeId = true) {
            const meta = statusTabsAll[key];
            const isActive = (currentStatusFilter === key);
            const li = document.createElement('li');
            li.className = 'nav-item';

            const a = document.createElement('a');
            a.className = `nav-link${isActive ? ' active' : ''}`;
            a.href = buildStatusFilterUrl(key);
            const tabLabel = meta.short_label || meta.label;
            a.title = meta.label || tabLabel;
            a.style.cssText = `border-radius: 8px 8px 0 0; border: none; padding: 9px 12px; font-size: 13px; ${isActive ? `background: #fff; color: ${meta.color}; font-weight: 600; box-shadow: 0 2px 0 ${meta.color};` : 'color: rgba(0,0,0,0.65);'}`;

            const badge = document.createElement('span');
            badge.className = 'badge rounded-pill';
            badge.style.cssText = isActive ? `background: ${meta.badge_bg}; color: ${meta.color};` : 'background: #f0f0f0; color: rgba(0,0,0,0.65);';
            if (includeBadgeId) {
                badge.id = meta.badge;
            }
            badge.textContent = latestStatusTabCounts[key] || 0;

            a.appendChild(document.createTextNode(`${tabLabel} `));
            a.appendChild(badge);
            li.appendChild(a);

            return li;
        }

        function applyStatusTabCounts() {
            Object.keys(statusTabsAll).forEach(function(key) {
                const meta = statusTabsAll[key] || {};
                if (meta.badge) {
                    $('#' + meta.badge).text(latestStatusTabCounts[key] || 0);
                }
            });
        }

        function getStatusTabsMaxStart(keys) {
            return Math.max(0, keys.length - statusTabsVisibleLimit);
        }

        function syncStatusTabsWindowToActive(keys) {
            const activeIndex = keys.indexOf(currentStatusFilter);
            if (activeIndex < 0 || activeIndex < statusTabsVisibleLimit) {
                statusTabsStartIndex = 0;
            } else {
                statusTabsStartIndex = activeIndex - statusTabsVisibleLimit + 1;
            }
            statusTabsStartIndex = Math.min(statusTabsStartIndex, getStatusTabsMaxStart(keys));
        }

        function renderStatusTabs(selected, options = {}) {
            const tabsEl = document.getElementById('status-tabs');
            if (!tabsEl) return;
            tabsEl.innerHTML = '';

            activeStatusTabsSelected = selected;
            const selectedKeys = getSelectedStatusTabKeys(selected);
            if (options.syncToActive) {
                syncStatusTabsWindowToActive(selectedKeys);
            }
            statusTabsStartIndex = Math.max(0, Math.min(statusTabsStartIndex, getStatusTabsMaxStart(selectedKeys)));

            selectedKeys.slice(statusTabsStartIndex, statusTabsStartIndex + statusTabsVisibleLimit).forEach((key) => {
                tabsEl.appendChild(createStatusTabItem(key));
            });

            updateStatusTabsPager();
        }

        function updateStatusTabsPager() {
            const prevBtn = document.getElementById('statusTabsPrev');
            const nextBtn = document.getElementById('statusTabsNext');
            if (!prevBtn || !nextBtn) return;

            const selectedKeys = getSelectedStatusTabKeys(activeStatusTabsSelected);
            const maxStart = getStatusTabsMaxStart(selectedKeys);
            if (maxStart <= 0) {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
                return;
            }

            prevBtn.style.display = statusTabsStartIndex <= 0 ? 'none' : '';
            nextBtn.style.display = statusTabsStartIndex >= maxStart ? 'none' : '';
        }

        function shiftStatusTabs(direction) {
            const selectedKeys = getSelectedStatusTabKeys(activeStatusTabsSelected);
            statusTabsStartIndex = Math.max(0, Math.min(statusTabsStartIndex + direction, getStatusTabsMaxStart(selectedKeys)));
            renderStatusTabs(activeStatusTabsSelected, { keepPage: true });
        }

        function persistStatusTabs(selected) {
            return $.ajax({
                url: '<?= base_url() ?>recruitment/update_status_tabs',
                method: 'POST',
                dataType: 'json',
                data: { tabs: selected }
            });
        }

    $(document).ready(function() {
        const initialSelected = statusTabsSelected.length ? statusTabsSelected : ['pending', 'interview_hr', 'interview_user'];
        renderStatusTabs(initialSelected, { syncToActive: true });

        $('#statusTabsNext').on('click', function() { shiftStatusTabs(1); });
        $('#statusTabsPrev').on('click', function() { shiftStatusTabs(-1); });
        $(window).on('resize', function() {
            updateStatusTabsPager();
        });

        $(document).on('change', '.status-tab-toggle', function() {
            const selected = $('.status-tab-toggle:checked').map(function() { return this.value; }).get();
            if (selected.length === 0) {
                $(this).prop('checked', true);
                return;
            }
            renderStatusTabs(selected, { syncToActive: true });
            persistStatusTabs(selected);

            if (!selected.includes(currentStatusFilter)) {
                window.location.href = buildStatusFilterUrl(selected[0]);
            } else {
                loadTabCounts();
            }
        });

        $(document).on('click', '.clickable-row', function(e) {
            if ($(e.target).closest('.editable-status, .editable-notes, .btn').length > 0) {
                return;
            }
            
            const id = $(this).data('id');
            openDetailModal(id);
        });

        $(document).on('click', '.btn-detail', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            openDetailModal(id);
        });
        

        function edit_position() {
            $("#load-form").html('Loading...');
            $("#modal-form").modal('show');
            $("#title-form").html('Ubah Posisi Recruitment');
            $("#load-form").load("<?= base_url() ?>recruitment/edit_position");
        }

        function openDetailModal(id) {
            $('#personal-info-content').html(`
                <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);
            
            $('#resume-content').html(`
                <div class="text-center p-4">
                    <i class="bi bi-file-earmark-text" style="font-size: 3rem; color: #6c757d;"></i>
                    <p class="text-muted mt-2">Loading...</p>
                </div>
            `);

            $('#history-content').html(`
                <div class="text-center p-4">
                    <i class="bi bi-clock-history" style="font-size: 3rem; color: #6c757d;"></i>
                    <p class="text-muted mt-2">Loading...</p>
                </div>
            `);

            $('#detailModal').modal('show');

            loadPersonalInfo(id);
        }

        function loadPersonalInfo(id) {
            $.ajax({
                url: '<?= base_url() ?>recruitment/get_detail_data',
                method: 'GET',
                data: { id: id },
                success: function(response) {
                    if (response.status === 'success') {
                        const data = response.data;
                        const history = response.history || [];
                        
                        $('#detailModalLabel').html(`
                            <i class="bi bi-person-lines-fill me-2"></i>Detail Pelamar - ${data.nama_lengkap}
                        `);
                        
                        $('#personal-info-content').html(generatePersonalInfoHTML(data));
                        
                        loadResumeContent(data.cv_portofolio);
                        loadHistoryContent(data.tag, history);
                        
                    } else {
                        $('#personal-info-content').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Gagal memuat data: ${response.message}
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#personal-info-content').html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Terjadi kesalahan saat memuat data
                        </div>
                    `);
                }
            });
        }

        function formatWaNumber(number) {
            number = number.replace(/\D/g, '');
            if (number.startsWith('620')) {
                number = '62' + number.substring(3);
            }
            else if (number.startsWith('0')) {
                number = '62' + number.substring(1);
            }
            return number;
        }



        function generatePersonalInfoHTML(data) {
            const waNumber = formatWaNumber(data.no_handphone);

            return `
                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Informasi Dasar</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-group">
                                            <div class="info-label">Nama Lengkap</div>
                                            <div class="info-value fw-semibold">${data.nama_lengkap}</div>
                                        </div>
                                        <div class="info-group">
                                            <div class="info-label">Posisi Dilamar</div>
                                            <div class="info-value">${data.posisi_dilamar}</div>
                                        </div>
                                        <div class="info-group">
                                            <div class="info-label">Kontak</div>
                                            <div class="contact-info">
                                                <div class="contact-item">
                                                    <i class="bi bi-envelope-fill"></i>
                                                    <span>${data.email}</span>
                                                </div>
                                                <div class="contact-item d-flex align-items-center">
                                                    <i class="bi bi-telephone-fill"></i>
                                                    <a href="https://wa.me/${waNumber}" target="_blank" 
                                                    style="text-decoration: none; color: #28a745; margin-right:8px;">
                                                        ${waNumber}
                                                    </a>
                                                    <i class="bi bi-copy copy-icon" data-number="${waNumber}" 
                                                    title="Copy nomor" style="cursor:pointer; color:#6c757d;"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="info-group">
                                            <div class="info-label">Demografi</div>
                                            <div class="demographics">
                                                <div>Usia: ${data.usia} tahun</div>
                                                <div>Domisili: ${data.domisili}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="info-group">
                                            <div class="info-label">Dokumen Pendukung</div>
                                            <div class="info-value">
                                                ${data.link_drive_dokumen ? `
                                                    <a href="${data.link_drive_dokumen}" target="_blank" class="btn btn-outline-primary btn-sm">
                                                        <i class="bi bi-google me-1"></i> Lihat Dokumen Pendukung
                                                    </a>
                                                ` : `
                                                    <span class="empty-state">Tidak tersedia</span>
                                                `}
                                            </div>
                                        </div>
                                        
                                        <div class="info-group">
                                            <div class="info-label">Sosial Media</div>
                                            <div class="d-flex flex-wrap gap-2">
                                                ${renderSocialIcon('instagram', 'fab fa-instagram', data.instagram, 'instagram.com', 'https://instagram.com/')}
                                                ${renderSocialIcon('facebook', 'fab fa-facebook-f', data.facebook, 'facebook.com', 'https://facebook.com/')}
                                                ${renderSocialIcon('linkedin', 'fab fa-linkedin-in', data.linkedin, 'linkedin.com', 'https://linkedin.com/in/')}
                                                ${renderSocialIcon('tiktok', 'fab fa-tiktok', data.tiktok, 'tiktok.com', 'https://tiktok.com/@')}
                                            </div>
                                        </div>
                                        
                                        <div class="info-group">
                                            <div class="info-label">Sumber Info Lowongan</div>
                                            <div class="info-value">
                                                ${data.sumber_info_loker ? data.sumber_info_loker : '<span class="empty-state">Tidak tersedia</span>'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Info -->
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-briefcase me-2"></i>Informasi Profesional</h6>
                            </div>
                            <div class="card-body">
                                <div class="info-group">
                                    <div class="info-label">Pengalaman Terakhir</div>
                                    <div class="info-value">
                                        ${data.pengalaman_terakhir ? data.pengalaman_terakhir : (
                                            (data.posisi_terakhir && data.lama_posisi_terakhir)
                                                ? `${data.posisi_terakhir} - ${data.lama_posisi_terakhir}`
                                                : '<span class="empty-state">Tidak tersedia</span>'
                                        )}
                                    </div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Perusahaan Terakhir</div>
                                    <div class="info-value">${data.perusahaan_terakhir}</div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Gaji & Benefit Terakhir</div>
                                    <div class="info-value">${data.gaji_benefit_terakhir}</div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Ekspektasi Gaji</div>
                                    <div class="salary">Rp ${data.ekspektasi_sallary}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Work Preference -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Preferensi Kerja</h6>
                            </div>
                            <div class="card-body">
                                <div class="info-group">
                                    <div class="info-label">Mode Kerja</div>
                                    <div class="info-value">${data.is_wfo ? '🏢 Work From Office' : '🏠 Work From Home'}</div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Tanggal Bisa Join</div>
                                    <div class="info-value">${data.tanggal_bisa_join}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-copy-check me-2"></i>Status Aplikasi</h6>
                            </div>
                            <div class="card-body">
                                <div class="info-group">
                                    <div class="info-label">Status Recruitment</div>
                                    <div class="status-badges">
                                        ${getRecruitmentStatusBadge(data.status_recruitment)}
                                    </div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Status Approval</div>
                                    <div class="status-badges">
                                        ${getApprovalStatusBadge(data.status_approval)}
                                    </div>
                                </div>
                                ${data.link_testcase ? `
                                <div class="info-group">
                                    <div class="info-label">Link Testcase</div>
                                    <div class="info-value">
                                        <a href="${data.link_testcase}" target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-link-45deg me-1"></i>Lihat Testcase
                                        </a>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>

                    <!-- Personal Insights -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Pandangan Pribadi</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="info-group">
                                            <div class="info-label">Alasan Join</div>
                                            <div class="info-value">${data.alasan_join}</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="info-group">
                                            <div class="info-label">Visi Misi</div>
                                            <div class="info-value">${data.visi_misi}</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="info-group">
                                            <div class="info-label">3 Kata Deskripsi Diri</div>
                                            <div class="keyword-tags">
                                                ${data.tiga_kata_diri.split(', ').map(word => `
                                                    <span class="keyword-tag">${word.trim()}</span>
                                                `).join('')}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="info-group">
                                            <div class="info-label">Pantun</div>
                                            <div class="info-value fst-italic">${data.pantun}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- HR Notes -->
                    ${data.notes_hr ? `
                    <div class="col-12">
                        <div class="hr-notes-section">
                            <div class="info-group">
                                <div class="info-label"><i class="bi bi-sticky me-1"></i>Catatan HR</div>
                                <div class="info-value">${data.notes_hr}</div>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
        }

        function renderSocialIcon(platform, iconClass, input, expectedDomain, urlPrefix) {
            if (!input) return '';
            
            input = input.trim();
            input = input.startsWith('@') ? input.substring(1) : input;
            
            let url = input;
            if (!new RegExp(`^(https?:\\/\\/)?(www\\.)?${expectedDomain.replace('.', '\\.')}`).test(input)) {
                url = urlPrefix + input;
            } else if (!input.startsWith('http')) {
                url = 'https://' + input;
            }
            
            return `
                <a href="${url}" target="_blank" class="btn btn-outline-primary rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;" title="${platform.charAt(0).toUpperCase() + platform.slice(1)}">
                    <i class="${iconClass}"></i>
                </a>
            `;
        }

        function loadResumeContent(cvUrl) {
            if (cvUrl) {
                let embedUrl = cvUrl;
                
                if (cvUrl.includes('drive.google.com')) {
                    let fileId;
                    const fileIdMatch = cvUrl.match(/\/d\/([a-zA-Z0-9-_]+)/);
                    const openIdMatch = cvUrl.match(/[?&]id=([a-zA-Z0-9-_]+)/);
                    
                    if (fileIdMatch) {
                        fileId = fileIdMatch[1];
                        embedUrl = `https://drive.google.com/file/d/${fileId}/preview`;
                    } else if (openIdMatch) {
                        fileId = openIdMatch[1];
                        embedUrl = `https://drive.google.com/file/d/${fileId}/preview`;
                    } else {
                        embedUrl = cvUrl;
                    }
                }

                $('#resume-content').html(`
                    <div class="iframe-container">
                        <div class="loading-overlay">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <iframe src="${embedUrl}" 
                                onload="$(this).parent().find('.loading-overlay').hide();"
                                onerror="$(this).parent().html('<div class=\"alert alert-warning\"><i class=\"bi bi-exclamation-triangle me-2\"></i>Tidak dapat memuat CV. <a href=\"${cvUrl}\" target=\"_blank\">Buka di tab baru</a></div>');">
                        </iframe>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="${cvUrl}" target="_blank" class="btn btn-primary">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Buka di Tab Baru
                        </a>
                    </div>
                `);
            } else {
                $('#resume-content').html(`
                    <div class="text-center p-4">
                        <i class="bi bi-file-earmark-x" style="font-size: 3rem; color: var(--secondary-color);"></i>
                        <p class="empty-state mt-2">CV/Resume tidak tersedia</p>
                    </div>
                `);
            }
        }

        function loadHistoryContent(tag, history) {
            if (tag === 'Already Apply' && history && history.length > 0) {
                let historyHtml = `
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="bi bi-info-circle me-2"></i>
                        Kandidat ini pernah melamar sebelumnya. Berikut adalah riwayat aplikasi dengan nama yang sama.
                    </div>
                    <div class="row">
                `;
                
                history.forEach((hist, index) => {
                    historyHtml += `
                        <div class="col-md-6">
                            <div class="card border-left-primary h-100 history-card" data-detail-url="<?= base_url() ?>recruitment/detail?id=${hist.id}">
                                <div class="card-body p-3" style="border-left: 4px solid var(--primary-color);">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-1 fw-semibold">${hist.posisi_dilamar}</h6>
                                        <small class="text-muted">#${hist.id}</small>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <div class="status-badges">
                                            ${getRecruitmentStatusBadge(hist.status_recruitment)}
                                            ${getApprovalStatusBadge(hist.status_approval)}
                                        </div>
                                    </div>
                                    
                                    <div class="small text-muted mb-2">
                                        <div><i class="bi bi-calendar-event me-1"></i>Apply: ${hist.formatted_created_at}</div>
                                        ${hist.formatted_updated_at ? `<div><i class="bi bi-arrow-repeat me-1"></i>Update: ${hist.formatted_updated_at}</div>` : ''}
                                    </div>
                                    
                                    <div class="small mb-2">
                                        <div class="text-muted">Ekspektasi Gaji:</div>
                                        <div class="fw-semibold text-success">Rp ${hist.ekspektasi_sallary}</div>
                                    </div>
                                    
                                    ${hist.notes_hr ? `
                                    <div class="mt-2 p-2 bg-light rounded">
                                        <small class="text-muted d-block">Catatan HR:</small>
                                        <small>${hist.notes_hr}</small>
                                    </div>
                                    ` : ''}
                                    
                                    ${hist.link_testcase ? `
                                    <div class="mt-2">
                                        <a href="${hist.link_testcase}" target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-eye me-1"></i>Lihat Testcase
                                        </a>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                historyHtml += `
                    </div>
                `;
                
                $('#history-content').html(historyHtml);
            } else {
                $('#history-content').html(`
                    <div class="text-center p-4">
                        <i class="bi bi-clock-history" style="font-size: 3rem; color: var(--secondary-color);"></i>
                        <p class="empty-state mt-2">
                            ${tag === 'Already Apply' ? 'Tidak ada riwayat lamaran sebelumnya' : 'Kandidat baru - belum ada riwayat lamaran'}
                        </p>
                    </div>
                `);
            }
        }

        function getRecruitmentStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning"><i class="bi bi-hourglass me-1"></i>Pending</span>',
                'testcase_1': '<span class="badge badge-testcase_1"><i class="bi bi-file-earmark-text me-1"></i>Testcase 1</span>',
                'interview_hr': '<span class="badge badge-interview_hr"><i class="bi bi-people me-1"></i>Interview HR</span>',
                'interview_user': '<span class="badge badge-interview_user"><i class="bi bi-person-check me-1"></i>Interview User</span>',
                'selected': '<span class="badge badge-selected"><i class="bi bi-check-circle me-1"></i>Selected</span>',
                'rejected': '<span class="badge badge-rejected"><i class="bi bi-x-circle me-1"></i>Rejected</span>',
                'pertimbangan': '<span class="badge badge-pertimbangan"><i class="bi bi-question-circle me-1"></i>Pertimbangan</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Pending</span>';
        }

        function getApprovalStatusBadge(status) {
            if (!status) return '<span class="badge bg-secondary">Pending</span>';
            
            const badges = {
                'approved': '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved</span>',
                'rejected': '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Pending</span>';
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }

        $(document).on("click", ".copy-icon", function () {
            let $icon = $(this);
            let number = $icon.data("number");

            navigator.clipboard.writeText(number).then(() => {
                $icon.removeClass("bi-copy").addClass("bi-check-circle");
                $icon.css("color", "#28a745");

                setTimeout(() => {
                    $icon.removeClass("bi-check-circle").addClass("bi-copy");
                    $icon.css("color", "#6c757d");
                }, 1500);
            });
        });

        $(document).on('click', '.history-card', function(e) {
            if ($(e.target).closest('a').length) {
                return;
            }
            const targetUrl = $(this).data('detail-url');
            if (targetUrl) {
                window.location.href = targetUrl;
            }
        });


        $(document).on('click', '.editable-status', function(e) {
            if ($(e.target).hasClass('btn-cancel-status') || $(e.target).hasClass('status-select')) {
                return;
            }
            
            $('.edit-mode').addClass('d-none');
            $('.display-mode').removeClass('d-none');
            
            $(this).find('.display-mode').addClass('d-none');
            $(this).find('.edit-mode').removeClass('d-none');
        });
        
        $(document).on('click', '.btn-cancel-status', function(e) {
            e.stopPropagation();
            $(this).closest('.edit-mode').addClass('d-none');
            $(this).closest('.editable-status').find('.display-mode').removeClass('d-none');
        });
        
        $(document).off('change.recruitment-status', '.editable-status select')
            .on('change.recruitment-status', '.editable-status select', function () {
            const cell = $(this).closest('.editable-status');
            const id = cell.data('id');
            const field = cell.data('field');
            const newValue = $(this).val();
            const linkInput = cell.find('.testcase-link-input');
            const row = cell.closest('tr');
            const waFromRow = row.attr('data-wa') || row.find('.btn-reject').attr('data-wa');
            const waFromLink = row.find('a[href^="https://wa.me/"]').attr('href') || '';
            const waNumber = waFromRow || waFromLink.replace('https://wa.me/', '');
            const posisi = (row.attr('data-posisi') || row.find('td').eq(4).text() || '').trim();
            const candidateName = (row.attr('data-name') || row.find('td').eq(2).text() || '').trim();

            if (newValue === 'done_testcase_1') {
                linkInput.removeClass('d-none');

                linkInput.off('change').on('change', function () {
                    const linkTestcase = $(this).val();

                    sendStatusUpdate(id, field, newValue, linkTestcase, waNumber, posisi, candidateName);
                });

            } else {
                linkInput.addClass('d-none');
                sendStatusUpdate(id, field, newValue, null, waNumber, posisi, candidateName);
            }
        });

        function sendStatusUpdate(id, field, value, link_testcase = null, waNumber = '', posisi = '', candidateName = '') {
            $.ajax({
                url: '<?= base_url() ?>recruitment/update_status',
                method: 'POST',
                dataType: 'json',
                data: {
                    id: id,
                    field: field,
                    value: value,
                    link_testcase: link_testcase
                },
                success: function (response) {
                    if (response.status === 'success') {
                        if (response.auto_next_status && response.applicant_name) {
                            const label = response.auto_next_status === 'interview_user' ? 'Interview User' : 'Selected';
                            showRecruitmentToast(`${response.applicant_name} berhasil diubah ke ${label}`);
                        }
                        if (field === 'status_approval' && value === 'rejected' && waNumber) {
                            openRecruitmentWhatsApp(waNumber, 'rejected', {
                                name: candidateName || 'kak',
                                position: posisi || 'Montera',
                                company: recruitmentCompanyName
                            });
                        }
                        if (field === 'status_recruitment' && value === 'interview_hr') {
                            openRecruitmentWhatsApp(waNumber, 'interview_hr', {
                                name: candidateName || 'kak',
                                position: posisi || 'Montera',
                                company: recruitmentCompanyName
                            });
                        }

                        loadRecruitmentData();
                        loadTabCounts();
                    } else {
                        alert('Gagal memperbarui status: ' + response.message);
                    }
                },
                error: function () {
                    alert('Gagal memperbarui status. Silakan coba lagi.');
                }
            });
        }

        $(document).on('click', '.editable-notes', function() {
            const id = $(this).data('id');
            const currentNotes = $(this).find('.notes-text').text().trim();
            
            $('#notesApplicantId').val(id);
            $('#notesTextarea').val(currentNotes === 'Add Note' ? '' : currentNotes);
            
            $('#notesEditor').modal('show');
        });

        // Load positions when modal is shown
        $('#positionModal').on('show.bs.modal', function () {
            loadPositions();
        });

        $('#infoLokerModal').on('show.bs.modal', function () {
            loadInfoLokers();
        });

        $('#recruitmentStatusModal').on('show.bs.modal', function () {
            loadRecruitmentStatuses();
        });

        let messageTemplates = [];
        let activeWaContext = {
            wa: '',
            name: 'kak',
            position: 'Montera',
            company: recruitmentCompanyName
        };

        function syncRecruitmentChatTemplates(templates) {
            const mapped = {};
            (templates || []).forEach(function(template) {
                mapped[template.key] = template.text || '';
            });
            window.recruitmentChatTemplates = Object.assign({}, defaultChatTemplates, mapped);
        }

        function renderMessageTemplates(templates) {
            messageTemplates = templates || [];
            syncRecruitmentChatTemplates(messageTemplates);

            const list = $('#message-template-list');
            if (!messageTemplates.length) {
                list.html('<div class="text-center text-muted py-4">Belum ada template.</div>');
                return;
            }

            const html = messageTemplates.map(function(template) {
                const preview = textToEditorHtml(String(template.text || '').slice(0, 450));
                const deleteButton = template.is_default ? '' : `
                    <button type="button" class="btn-delete-message-template" data-key="${escapeHtmlText(template.key)}" title="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                `;

                return `
                    <div class="message-template-item" data-key="${escapeHtmlText(template.key)}">
                        <div class="message-template-content btn-use-message-template" data-key="${escapeHtmlText(template.key)}">
                            <div class="message-template-title">${escapeHtmlText(template.name || 'Template')}</div>
                            <div class="message-template-text">${preview}</div>
                        </div>
                        <div class="message-template-actions">
                            <button type="button" class="btn-edit-message-template" data-key="${escapeHtmlText(template.key)}" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            ${deleteButton}
                        </div>
                    </div>
                `;
            }).join('');

            list.html(html);
        }

        function loadMessageTemplates() {
            return $.ajax({
                url: '<?= base_url() ?>recruitment/get_message_templates',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        renderMessageTemplates(response.data || []);
                    }
                }
            });
        }

        function showMessageTemplateListTab() {
            $('#message-template-form-view').css('display', 'none');
            $('#message-template-list-view').css('display', 'flex');
        }

        function showMessageTemplateForm(template) {
            $('#message_template_key').val(template ? template.key : '');
            $('#message_template_name').val(template ? template.name : '');
            $('#message_template_name').prop('readonly', !!template);
            setChatTemplateEditorText('#message_template_text', template ? template.text : '');

            $('#message-template-list-view').css('display', 'none');
            $('#message-template-form-view').css('display', 'flex');
        }

        $(document).on('click', '.btn-wa-template', function(e) {
            e.preventDefault();
            e.stopPropagation();

            activeWaContext = {
                wa: ($(this).data('wa') || '').toString(),
                name: ($(this).data('name') || 'kak').toString(),
                position: ($(this).data('posisi') || 'Montera').toString(),
                company: recruitmentCompanyName
            };

            $('#chatTemplateModal').modal('show');
        });

        $('#chatTemplateModal').on('show.bs.modal', function() {
            initMessageTemplateEditor();
            showMessageTemplateListTab();
            loadMessageTemplates();
        });

        $('#btnAddMessageTemplate').on('click', function() {
            showMessageTemplateForm(null);
        });

        $('#btnCancelMessageTemplate').on('click', function() {
            showMessageTemplateListTab();
        });

        $(document).on('click', '.btn-edit-message-template', function(e) {
            e.stopPropagation();
            const key = ($(this).data('key') || '').toString();
            const template = messageTemplates.find(function(item) { return item.key === key; });
            if (template) {
                showMessageTemplateForm(template);
            }
        });

        $(document).on('click', '.btn-use-message-template', function() {
            const key = ($(this).data('key') || '').toString();
            openRecruitmentWhatsApp(activeWaContext.wa, key, {
                name: activeWaContext.name || 'kak',
                position: activeWaContext.position || 'Montera',
                company: activeWaContext.company || recruitmentCompanyName
            });
        });

        $(document).on('click', '.btn-delete-message-template', function(e) {
            e.stopPropagation();
            const key = ($(this).data('key') || '').toString();
            if (!key || !confirm('Hapus template ini?')) return;

            $.ajax({
                url: '<?= base_url() ?>recruitment/delete_message_template',
                method: 'POST',
                dataType: 'json',
                data: { template_key: key },
                success: function(response) {
                    if (response.status === 'success') {
                        renderMessageTemplates(response.data || []);
                    } else {
                        alert(response.message || 'Gagal menghapus template.');
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat menghapus template.');
                }
            });
        });

        $('#messageTemplateForm').on('submit', function(e) {
            e.preventDefault();
            const payload = {
                template_key: $('#message_template_key').val(),
                template_name: $('#message_template_name').val(),
                template_text: getChatTemplateEditorText('#message_template_text')
            };
            $.ajax({
                url: '<?= base_url() ?>recruitment/save_message_template',
                method: 'POST',
                dataType: 'json',
                data: payload,
                success: function(response) {
                    if (response.status === 'success') {
                        renderMessageTemplates(response.data || []);
                        showMessageTemplateListTab();
                    } else {
                        alert(response.message || 'Gagal menyimpan template.');
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat menyimpan template.');
                }
            });
        });

        function loadPositions() {
            $.ajax({
                url: '<?= site_url('recruitment/get_positions') ?>',
                method: 'GET',
                success: function(response) {
                    $('#positions-list').html(response);
                },
                error: function() {
                    $('#positions-list').html('<div class="text-danger">Gagal memuat data posisi.</div>');
                }
            });
        }

        function loadInfoLokers() {
            $.ajax({
                url: '<?= site_url('recruitment/get_info_lokers') ?>',
                method: 'GET',
                success: function(response) {
                    $('#info-lokers-list').html(response);
                },
                error: function() {
                    $('#info-lokers-list').html('<div class="text-danger">Gagal memuat data info loker.</div>');
                }
            });
        }

        function loadRecruitmentStatuses() {
            $.ajax({
                url: '<?= site_url('recruitment/get_recruitment_statuses') ?>',
                method: 'GET',
                success: function(response) {
                    $('#recruitment-statuses-list').html(response);
                },
                error: function() {
                    $('#recruitment-statuses-list').html('<div class="text-danger">Gagal memuat data status.</div>');
                }
            });
        }

        // Handle form submission
        $('#positionModal form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#position_name').val('');
                        loadPositions();
                        alert('Posisi berhasil ditambahkan');
                    } else {
                        alert('Gagal menambahkan posisi: ' + response.message);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat menyimpan posisi');
                }
            });
        });

        $('#infoLokerModal form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#info_loker_name').val('');
                        loadInfoLokers();
                        alert('Sumber info loker berhasil ditambahkan');
                    } else {
                        alert('Gagal menambahkan sumber info loker: ' + response.message);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat menyimpan sumber info loker');
                }
            });
        });

        $('#recruitmentStatusForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#status_name').val('');
                        $('#short_label').val('');
                        loadRecruitmentStatuses();
                        window.location.reload();
                    } else {
                        alert('Gagal menambahkan status recruitment: ' + response.message);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat menyimpan status recruitment');
                }
            });
        });

        // Handle position deletion
        $(document).on('click', '.delete-position', function(e) {
            e.preventDefault();
            var positionItem = $(this).closest('.position-item');
            var positionId = positionItem.data('id');
            
            if (confirm('Apakah Anda yakin ingin menghapus posisi ini?')) {
                $.ajax({
                    url: '<?= site_url('recruitment/delete_position/') ?>' + positionId,
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            positionItem.fadeOut(200, function() {
                                $(this).remove();
                            });
                        } else {
                            alert('Gagal menghapus posisi: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat menghapus posisi');
                    }
                });
            }
        });

        $(document).on('click', '.delete-info-loker', function(e) {
            e.preventDefault();
            var infoLokerItem = $(this).closest('.info-loker-item');
            var infoLokerId = infoLokerItem.data('id');
            
            if (confirm('Apakah Anda yakin ingin menghapus sumber info loker ini?')) {
                $.ajax({
                    url: '<?= site_url('recruitment/delete_info_loker/') ?>' + infoLokerId,
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            infoLokerItem.fadeOut(200, function() {
                                $(this).remove();
                            });
                        } else {
                            alert('Gagal menghapus sumber info loker: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat menghapus sumber info loker');
                    }
                });
            }
        });

        $(document).on('click', '.delete-recruitment-status', function(e) {
            e.preventDefault();
            var statusItem = $(this).closest('.recruitment-status-item');
            var statusId = statusItem.data('id');
            
            if (confirm('Apakah Anda yakin ingin menghapus status recruitment ini?')) {
                $.ajax({
                    url: '<?= site_url('recruitment/delete_recruitment_status/') ?>' + statusId,
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            statusItem.fadeOut(200, function() {
                                $(this).remove();
                            });
                            window.location.reload();
                        } else {
                            alert('Gagal menghapus status recruitment: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat menghapus status recruitment');
                    }
                });
            }
        });

        $('#saveNotesBtn').click(function() {
            const id = $('#notesApplicantId').val();
            const notes = $('#notesTextarea').val();
            
            $.ajax({
                url: '<?= base_url() ?>recruitment/update_notes',
                method: 'POST',
                dataType: 'json',
                data: {
                    id: id,
                    notes: notes
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#notesEditor').modal('hide');
                        loadRecruitmentData();
                    } else {
                        alert('Gagal menyimpan notes: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('Gagal menyimpan notes. Silakan coba lagi.');
                }
            });
        });

        // Handle Reject Button Click
        $(document).on('click', '.btn-reject', function(e) {
            e.stopPropagation();
            const candidateName = $(this).data('name');
            const waNumber = $(this).data('wa');
            const id = $(this).data('id');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Konfirmasi',
                    text: `Apakah Anda yakin ingin menolak kandidat ${candidateName}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Tolak',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#f5222d'
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    // Update status to rejected
                    $.ajax({
                        url: '<?= base_url() ?>recruitment/update_status',
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            id: id,
                            field: 'status_recruitment',
                            value: 'rejected',
                            force_approval_null: 1
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                openRecruitmentWhatsApp(waNumber, 'rejected', {
                                    name: candidateName || 'kak',
                                    company: recruitmentCompanyName
                                });
                                
                                // Reload data
                                loadRecruitmentData();
                                loadTabCounts();
                            } else {
                                showRecruitmentToast('Gagal memperbarui status: ' + response.message);
                            }
                        },
                        error: function() {
                            showRecruitmentToast('Gagal memperbarui status. Silakan coba lagi.');
                        }
                    });
                });
            } else if (confirm(`Apakah Anda yakin ingin menolak kandidat ${candidateName}?`)) {
                // Fallback confirm
                $.ajax({
                    url: '<?= base_url() ?>recruitment/update_status',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        id: id,
                        field: 'status_recruitment',
                        value: 'rejected',
                        force_approval_null: 1
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            openRecruitmentWhatsApp(waNumber, 'rejected', {
                                name: candidateName || 'kak',
                                company: recruitmentCompanyName
                            });
                            loadRecruitmentData();
                            loadTabCounts();
                        } else {
                            alert('Gagal memperbarui status: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Gagal memperbarui status. Silakan coba lagi.');
                    }
                });
            }
        });

        // Handle WhatsApp Chat Button Click (Pending -> Interview HR)
        $(document).on('click', '.btn-whatsapp', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const row = $(this).closest('tr');
            const waNumber = $(this).data('wa');
            const id = $(this).data('id');
            const posisi = (row.data('posisi') || '').toString().trim();
            const candidateName = (row.data('name') || '').toString().trim();
            openRecruitmentWhatsApp(waNumber, 'interview_hr', {
                name: candidateName || 'kak',
                position: posisi || 'Montera',
                company: recruitmentCompanyName
            });

            $.ajax({
                url: '<?= base_url() ?>recruitment/update_status',
                method: 'POST',
                dataType: 'json',
                data: {
                    id: id,
                    field: 'status_recruitment',
                    value: 'interview_hr',
                    force_approval_null: 1
                },
                success: function(response) {
                    if (response.status === 'success') {
                        loadRecruitmentData();
                        loadTabCounts();
                    } else {
                        showRecruitmentToast('Gagal memperbarui status: ' + response.message);
                    }
                },
                error: function() {
                    showRecruitmentToast('Gagal memperbarui status. Silakan coba lagi.');
                }
            });
        });

        // Handle Notes Button Click
        $(document).on('click', '.btn-notes', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            const currentNotes = $(this).data('notes') || '';
            
            $('#notesApplicantId').val(id);
            $('#notesTextarea').val(currentNotes);
            
            $('#notesEditor').modal('show');
        });

        // Initialize Tippy for notes tooltips
        function initializeNotesTooltips() {
            if (typeof tippy !== 'undefined') {
                // Destroy existing instances first
                const existingNotesInstances = document.querySelectorAll('.btn-notes');
                existingNotesInstances.forEach(el => {
                    if (el._tippy) {
                        el._tippy.destroy();
                    }
                });
                
                const existingCompanyInstances = document.querySelectorAll('.company-name-hover');
                existingCompanyInstances.forEach(el => {
                    if (el._tippy) {
                        el._tippy.destroy();
                    }
                });
                
                // Initialize notes tooltips
                tippy('.btn-notes', {
                    content: function(reference) {
                        const notes = $(reference).data('notes');
                        if (!notes || notes.trim() === '') {
                            return '<div style="font-style: italic; color: #bdc3c7;">Belum ada catatan</div>';
                        }
                        return `<div><strong>Notes HR:</strong><br>${notes}</div>`;
                    },
                    allowHTML: true,
                    theme: 'recruitment-notes',
                    placement: 'left',
                    arrow: true,
                    maxWidth: 350,
                    interactive: false,
                    delay: [300, 0],
                    trigger: 'mouseenter'
                });
                
                // Tippy for company name to show pengalaman_terakhir
                tippy('.company-name-hover', {
                    content: function(reference) {
                        const pengalaman = $(reference).data('pengalaman');
                        if (!pengalaman || pengalaman.trim() === '') {
                            return '<div style="font-style: italic; color: #bdc3c7;">Tidak ada detail pengalaman</div>';
                        }
                        return `<div style="white-space: normal; line-height: 1.4;"><strong><i class="bi bi-briefcase"></i> Pengalaman Terakhir:</strong><br>${pengalaman}</div>`;
                    },
                    allowHTML: true,
                    theme: 'recruitment-notes',
                    placement: 'top',
                    arrow: true,
                    maxWidth: 450,
                    interactive: false,
                    delay: [200, 0],
                    trigger: 'mouseenter'
                });
            } else {
                console.warn('Tippy.js is not loaded yet');
            }
        }
        window.initializeNotesTooltips = initializeNotesTooltips;

        // Initial call when document is ready
        setTimeout(function() {
            if (typeof window.initializeNotesTooltips === 'function') {
                window.initializeNotesTooltips();
            }
        }, 1000);

        if (!$('#modal-custom-css').length) {
            $('head').append(`
                <style id="modal-custom-css">
                    .info-label {
                        font-size: 0.75rem;
                        font-weight: 600;
                        color: #6c757d;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        margin-bottom: 0.25rem;
                    }
                    
                    .info-value {
                        font-size: 0.95rem;
                        color: #333;
                        margin-bottom: 0;
                        line-height: 1.4;
                    }
                    
                    .badge-testcase_1 {
                        background-color: #d7bdf2;
                        color: #4b0082;
                    }
                    
                    .badge-interview_hr {
                        background-color: #ffe7a0;
                        color: #7a5900;
                    }
                    
                    .badge-interview_user {
                        background-color: #cde8ff;
                        color: #0d6efd;
                    }
                    
                    .badge-selected {
                        background-color: #2e7d32;
                        color: white;
                    }
                    
                    .badge-rejected {
                        background-color: #b71c1c;
                        color: white;
                    }
                    
                    .badge-pertimbangan {
                        background-color: #f9c998;
                        color: #8b4513;
                    }
                </style>
            `);
        }
        
        // Initial load
        loadRecruitmentData();
    });

    // ==================== CUSTOM HORIZONTAL SCROLLBAR ====================
    (function initRecruitmentScrollbar() {
        const tableWrapper = document.getElementById('recruitmentTableWrapper');
        const scrollbarThumb = document.querySelector('#recruitmentCustomScrollbar .scrollbar-thumb');
        const scrollbarTrack = document.querySelector('#recruitmentCustomScrollbar .scrollbar-track');
        const customScrollbar = document.getElementById('recruitmentCustomScrollbar');
        
        if (!tableWrapper || !scrollbarThumb || !scrollbarTrack || !customScrollbar) {
            return;
        }

        const updateScrollbar = () => {
            const scrollWidth = tableWrapper.scrollWidth;
            const clientWidth = tableWrapper.clientWidth;

            if (scrollWidth <= clientWidth) {
                customScrollbar.style.display = 'none';
                return;
            }

            customScrollbar.style.display = 'block';

            const thumbWidthPercent = (clientWidth / scrollWidth) * 100;
            scrollbarThumb.style.width = thumbWidthPercent + '%';

            const scrollLeft = tableWrapper.scrollLeft;
            const maxScroll = scrollWidth - clientWidth;
            const scrollPercent = (scrollLeft / maxScroll) * 100;
            const maxThumbLeft = 100 - thumbWidthPercent;
            const thumbLeft = (scrollPercent / 100) * maxThumbLeft;
            
            scrollbarThumb.style.left = thumbLeft + '%';
        };

        tableWrapper.addEventListener('scroll', updateScrollbar);

        let isDragging = false;
        let startX = 0;
        let startLeft = 0;

        scrollbarThumb.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX;
            startLeft = parseFloat(scrollbarThumb.style.left) || 0;
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;

            const trackWidth = scrollbarTrack.offsetWidth;
            const deltaX = e.clientX - startX;
            const deltaPercent = (deltaX / trackWidth) * 100;
            let newLeft = startLeft + deltaPercent;

            const thumbWidthPercent = parseFloat(scrollbarThumb.style.width);
            const maxLeftPercent = 100 - thumbWidthPercent;
            newLeft = Math.max(0, Math.min(newLeft, maxLeftPercent));

            scrollbarThumb.style.left = newLeft + '%';

            const scrollPercent = (newLeft / maxLeftPercent) * 100;
            const scrollWidth = tableWrapper.scrollWidth;
            const clientWidth = tableWrapper.clientWidth;
            const maxScroll = scrollWidth - clientWidth;
            tableWrapper.scrollLeft = (scrollPercent / 100) * maxScroll;
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
        });

        scrollbarTrack.addEventListener('click', (e) => {
            if (e.target === scrollbarThumb) return;

            const trackRect = scrollbarTrack.getBoundingClientRect();
            const clickX = e.clientX - trackRect.left;
            const trackWidth = trackRect.width;
            const thumbWidth = scrollbarThumb.offsetWidth;

            let newThumbLeft = clickX - (thumbWidth / 2);
            newThumbLeft = Math.max(0, Math.min(newThumbLeft, trackWidth - thumbWidth));

            const newLeftPercent = (newThumbLeft / trackWidth) * 100;
            const thumbWidthPercent = parseFloat(scrollbarThumb.style.width);
            const maxLeftPercent = 100 - thumbWidthPercent;
            const clampedLeft = Math.max(0, Math.min(newLeftPercent, maxLeftPercent));

            scrollbarThumb.style.left = clampedLeft + '%';

            const scrollPercent = (clampedLeft / maxLeftPercent) * 100;
            const scrollWidth = tableWrapper.scrollWidth;
            const clientWidth = tableWrapper.clientWidth;
            const maxScroll = scrollWidth - clientWidth;
            tableWrapper.scrollLeft = (scrollPercent / 100) * maxScroll;
        });

        // Initial update - langsung dan setelah delay
        updateScrollbar();
        setTimeout(updateScrollbar, 50);
        setTimeout(updateScrollbar, 200);
        setTimeout(updateScrollbar, 500);
        
        window.addEventListener('resize', updateScrollbar);
        
        // Update setelah DOM changes (untuk AJAX load)
        const observer = new MutationObserver(() => {
            updateScrollbar();
        });
        observer.observe(tableWrapper, { childList: true, subtree: true });
    })();
</script>

<style>
    /* Bootstrap dropdown fix */
    .dropdown-menu {
        z-index: 9999 !important;
    }

    /* Ensure dropdown is visible */
    .dropdown-menu.show {
        display: block !important;
    }
</style>

<script>
    // Initialize Bootstrap dropdowns
    document.addEventListener('DOMContentLoaded', function() {
        // Force initialize Bootstrap dropdowns
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
    });
</script>
