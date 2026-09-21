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
    <?php
    $transition_profile = isset($transition_profile) && is_array($transition_profile) ? $transition_profile : [];
    $onboarding_profile = $transition_profile['onboarding'] ?? ['transition' => null, 'tasks' => [], 'progress' => 0, 'checked' => 0, 'total' => 0];
    $offboarding_profile = $transition_profile['offboarding'] ?? ['transition' => null, 'tasks' => [], 'progress' => 0, 'checked' => 0, 'total' => 0];
    $show_onboarding = !empty($onboarding_profile['transition']);
    $show_offboarding = !empty($offboarding_profile['transition']);
    $show_transition_tab = $show_onboarding || $show_offboarding;
    $transition_col_class = ($show_onboarding && $show_offboarding) ? 'col-lg-6' : 'col-lg-12';
    $appreciation_reward_alerts = isset($appreciation_reward_alerts) && is_array($appreciation_reward_alerts) ? $appreciation_reward_alerts : [];
    ?>

    <?php if (!empty($appreciation_reward_alerts)): ?>
        <?php
        $reward_total = 0;
        foreach ($appreciation_reward_alerts as $reward) {
            $reward_total += (int) ($reward['score'] ?? 0);
        }
        $primary_reward = $appreciation_reward_alerts[0];
        $reward_note = trim((string) ($primary_reward['note'] ?? ''));
        $reward_name = trim((string) ($user_data['full_name'] ?? ''));
        $reward_name = $reward_name !== '' ? $reward_name : 'Kamu';
        ?>
        <div class="modal fade bhs-reward-modal" id="appreciationRewardModal" tabindex="-1" aria-hidden="true" data-reward-popup="modal" data-reward-effect="balloons">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="bhs-reward-modal-card">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        <div class="bhs-reward-icon">
                            <i class="bi bi-trophy-fill"></i>
                        </div>
                        <div class="bhs-reward-kicker">Reward Unlocked ✨</div>
                        <div class="bhs-reward-title">🎉 Selamat <?= htmlspecialchars($reward_name, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="bhs-reward-message">Kamu berhasil mendapatkan poin tambahan.</div>
                        <?php if ($reward_note !== '' || count($appreciation_reward_alerts) > 1): ?>
                            <div class="bhs-reward-note">
                                <?php if ($reward_note !== ''): ?>
                                    <?= htmlspecialchars($reward_note, ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                                <?php if (count($appreciation_reward_alerts) > 1): ?>
                                    <br><span>🎁 <?= count($appreciation_reward_alerts) ?> apresiasi baru masuk sekaligus.</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="bhs-reward-points">
                            <strong>+<?= (int) $reward_total ?></strong>
                            <span>poin</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Profile Navigation Tabs -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="profile-tabs">
                <li class="nav-item">
                    <a class="nav-link active" id="profile-tab" data-bs-toggle="tab" href="#profile-content" role="tab">
                        <i class="bi bi-person-circle me-2"></i>Profile
                    </a>
                </li>
                <?php if ($show_transition_tab): ?>
                    <li class="nav-item">
                        <a class="nav-link" id="transition-tab" data-bs-toggle="tab" href="#transition-content" role="tab">
                            <i class="bi bi-list-check me-2"></i>Onboarding/Offboarding
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" id="milestone-tab" data-bs-toggle="tab" href="#milestone-content" role="tab">
                        <i class="bi bi-trophy me-2"></i>Milestone
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="performance-tab" data-bs-toggle="tab" href="#performance-content" role="tab">
                        <i class="bi bi-clipboard-check me-2"></i>Penilaian Kinerja
                        <?php if (!empty($pending_review_tasks)): ?><span class="badge bg-danger ms-1"><?= (int) $pending_review_tasks ?></span><?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="card-body">
            <div class="tab-content" id="profile-tab-content">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile-content" role="tabpanel">
                    <!-- User Profile Information Card -->
                    <div class="card mb-4 profile-card-enhanced card-hover-effect">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                                    <i class="bi bi-person-circle me-2"></i>Informasi Akun Saya
                                </h5>
                                <div class="d-flex gap-2">
                                    <a href="<?= base_url() ?>leave/request" class="btn btn-outline-primary">
                                        <i class="bi bi-calendar2-check me-1"></i> Ajukan Cuti
                                    </a>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#profileEditModal">
                                        <i class="bi bi-pencil me-1"></i> Edit Profil
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-4">
                                    <div class="text-center">
                                        <?php
                                        $img = $user_data['img'];
                                        if ($img == "") {
                                            $img_url = base_url() . '/assets/img/user/default.png';
                                        } else {
                                            $img_url = base_url() . '/assets/img/user/' . $img . '?token=' . DATE("Ymdhis", strtotime($user_data['updated_at']));
                                        }
                                        ?>
                                        <div class="profile-image-container">
                                            <img src="<?= $img_url ?>" class="img-fluid rounded-circle mb-3"
                                                style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #e6f7ff;">
                                        </div>
                                        <h4 class="mb-1"><?= $user_data['full_name'] ?></h4>
                                        <p class="text-muted mb-2"><?= !empty($profile['position_name']) ? $profile['position_name'] : 'Posisi belum ditentukan' ?></p>
                                        <?php if (!empty($profile['level_name'])): ?>
                                            <span class="badge" style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;">
                                                <?= $profile['level_name'] ?> Level
                                            </span>

                                            <!-- Position Career Progress -->
                                            <div class="mt-3">
                                                <small class="text-muted">Career Position Progress</small>

                                                <?php if (!empty($position_career_progress)): ?>
                                                    <div class="career-position-flow mt-2">
                                                        <!-- Current Position Status -->
                                                        <div class="current-position-status">
                                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                                <div class="position-info">
                                                                    <strong style="color: #1890ff; font-size: 13px;">
                                                                        <i class="bi bi-geo-alt-fill me-1"></i>Current Position
                                                                    </strong>
                                                                </div>
                                                                <span class="badge bg-primary px-2 py-1" style="font-size: 10px;">
                                                                    <?= !empty($profile['time_in_role']) ? $profile['time_in_role'] : 'Active' ?>
                                                                </span>
                                                            </div>

                                                            <!-- Available Career Paths -->
                                                            <?php if (!empty($position_career_progress['available_paths'])): ?>
                                                                <div class="available-paths mt-3">
                                                                    <small class="text-muted d-block mb-2">
                                                                        <i class="bi bi-arrow-up-right me-1"></i>Available Career Paths (<?= count($position_career_progress['available_paths']) ?>)
                                                                    </small>

                                                                    <?php foreach ($position_career_progress['available_paths'] as $path): ?>
                                                                        <?php
                                                                        // Check if this is the selected career path
                                                                        $is_selected = !empty($profile['preferred_career_path_id']) &&
                                                                                       $path['target_position_id'] == $profile['preferred_career_path_id'];

                                                                        // Special styling for selected path
                                                                        $card_bg = $is_selected ? '#f6ffed' : '#f8f9fa';
                                                                        $border_color = $is_selected ? '#52c41a' : '#1890ff';
                                                                        ?>
                                                                        <div class="career-path-item mb-2 p-2" style="background: <?= $card_bg ?>; border-radius: 6px; border-left: 3px solid <?= $border_color ?>;">
                                                                            <div class="d-flex align-items-center justify-content-between">
                                                                                <div class="flex-grow-1">
                                                                                    <div class="d-flex align-items-center">
                                                                                        <div class="path-target" style="font-size: 12px; font-weight: 600; color: #333;">
                                                                                            <?= $path['target_position_name'] ?>
                                                                                        </div>
                                                                                        <?php if ($is_selected): ?>
                                                                                            <span class="badge ms-2" style="background: linear-gradient(135deg, #52c41a, #389e0d); color: white; font-size: 9px; animation: pulse-career-badge 2s ease-in-out infinite;">
                                                                                                <i class="bi bi-star-fill me-1"></i>Selected
                                                                                            </span>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                    <div class="path-details" style="font-size: 11px; color: #666;">
                                                                                        <?= $path['target_department'] ?>
                                                                                        <?php if (!empty($path['target_level'])): ?>
                                                                                            • <?= $path['target_level'] ?> Level
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <?php if ($is_selected && !empty($selected_career_path['has_quest'])): ?>
                                                                                <!-- Quest Status for Selected Path -->
                                                                                <div class="quest-status mt-2 p-2" style="background: #f0f5ff; border-radius: 4px; border: 1px solid #91d5ff;">
                                                                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                                                                        <div>
                                                                                            <small class="text-muted d-block" style="font-size: 10px;">
                                                                                                <i class="bi bi-flag me-1"></i>Quest:
                                                                                            </small>
                                                                                            <strong style="font-size: 11px; color: #1890ff;">
                                                                                                <?= $selected_career_path['quest_title'] ?>
                                                                                            </strong>
                                                                                        </div>
                                                                                        <div>
                                                                                            <?php
                                                                                            $status_badge = [
                                                                                                'not_applied' => ['bg' => '#f5f5f5', 'color' => '#8c8c8c', 'text' => 'Not Applied'],
                                                                                                'pending' => ['bg' => '#fffbe6', 'color' => '#faad14', 'text' => 'Pending'],
                                                                                                'approved' => ['bg' => '#f6ffed', 'color' => '#52c41a', 'text' => 'Completed'],
                                                                                                'denied' => ['bg' => '#fff2f0', 'color' => '#ff4d4f', 'text' => 'Denied']
                                                                                            ];
                                                                                            $status = $status_badge[$selected_career_path['quest_status']] ?? $status_badge['not_applied'];
                                                                                            ?>
                                                                                            <span class="badge" style="background: <?= $status['bg'] ?>; color: <?= $status['color'] ?>; font-size: 9px; border: 1px solid <?= $status['color'] ?>33;">
                                                                                                <?= $status['text'] ?>
                                                                                            </span>
                                                                                        </div>
                                                                                    </div>

                                                                                    <?php if ($selected_career_path['quest_status'] == 'not_applied'): ?>
                                                                                        <a href="<?= base_url() ?>quest/main_quest_detail?id=<?= $selected_career_path['quest_id'] ?>"
                                                                                           class="btn btn-outline-primary btn-sm w-100" style="font-size: 10px;">
                                                                                            <i class="bi bi-play me-1"></i>View & Apply for Quest
                                                                                        </a>
                                                                                    <?php elseif ($selected_career_path['quest_status'] == 'pending'): ?>
                                                                                        <small class="d-block text-center" style="font-size: 10px; color: #666;">
                                                                                            <i class="bi bi-hourglass-split me-1"></i>Waiting for HR approval
                                                                                        </small>
                                                                                    <?php elseif ($selected_career_path['quest_status'] == 'approved'): ?>
                                                                                        <small class="d-block text-center" style="font-size: 10px; color: #52c41a;">
                                                                                            <i class="bi bi-check-circle me-1"></i>Quest completed! You can advance to next level
                                                                                        </small>
                                                                                    <?php elseif ($selected_career_path['quest_status'] == 'denied'): ?>
                                                                                        <small class="d-block text-center" style="font-size: 10px; color: #ff4d4f;">
                                                                                            <i class="bi bi-x-circle me-1"></i>Quest denied. Please contact HR for feedback
                                                                                        </small>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                            <?php elseif ($is_selected): ?>
                                                                                <div class="no-quest mt-2 p-2" style="background: #fffbe6; border-radius: 4px; border: 1px dashed #faad14; text-align: center;">
                                                                                    <small style="font-size: 10px; color: #666;">
                                                                                        <i class="bi bi-info-circle me-1"></i>No quest available yet for this path
                                                                                    </small>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <!-- Quick Action Buttons -->
                                                    <div class="career-actions mt-3 d-flex flex-column gap-2">
                                                        <a href="<?= base_url() ?>profile/career_paths" class="btn btn-outline-primary btn-sm w-100" style="font-size: 11px;">
                                                            <i class="bi bi-diagram-3 me-1"></i>View Full Paths
                                                        </a>
                                                        <?php if (!empty($position_career_progress['has_ready_paths'])): ?>
                                                            <a href="<?= base_url() ?>profile/apply_main_quest" class="btn btn-primary btn-sm w-100" style="font-size: 11px;">
                                                                <i class="bi bi-rocket me-1"></i>Apply for Quest
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="<?= base_url() ?>profile/career_recommendations" class="btn btn-outline-success btn-sm w-100" style="font-size: 11px;">
                                                                <i class="bi bi-lightbulb me-1"></i>Get Recommendations
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>

                                                <?php else: ?>
                                                    <!-- No Career Paths Available -->
                                                    <div class="no-career-paths mt-2 p-3" style="background: #f8f9fa; border-radius: 6px; text-align: center;">
                                                        <i class="bi bi-info-circle text-muted" style="font-size: 20px;"></i>
                                                        <div class="mt-2" style="font-size: 12px; color: #666;">
                                                            No career paths available from your current position.
                                                        </div>
                                                        <a href="<?= base_url() ?>profile/career_recommendations" class="btn btn-outline-secondary btn-sm mt-2" style="font-size: 11px;">
                                                            <i class="bi bi-lightbulb me-1"></i>Get Career Guidance
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-1">Email</h6>
                                            <p class="mb-3"><?= $user_data['email'] ?></p>

                                            <h6 class="text-muted mb-1">Username</h6>
                                            <p class="mb-3"><?= $user_data['username'] ?></p>

                                            <h6 class="text-muted mb-1">Role</h6>
                                            <p class="mb-3">
                                                <span class="badge" style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;">
                                                    <?= $user_data['role_text'] ?>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <?php if (!empty($profile['phone_number'])): ?>
                                                <h6 class="text-muted mb-1">Nomor Telepon</h6>
                                                <p class="mb-3"><?= $profile['phone_number'] ?></p>
                                            <?php endif; ?>

                                            <?php if (!empty($profile['join_date'])): ?>
                                                <h6 class="text-muted mb-1">Tanggal Bergabung</h6>
                                                <p class="mb-3"><?= date('d M Y', strtotime($profile['join_date'])) ?></p>
                                            <?php endif; ?>

                                            <?php if (!empty($profile['favorite_sport'])): ?>
                                                <h6 class="text-muted mb-1">Olahraga Favorit</h6>
                                                <p class="mb-3"><?= $profile['favorite_sport'] ?></p>
                                            <?php endif; ?>

                                            <h6 class="text-muted mb-1">Total Skor Quest</h6>
                                            <p class="mb-3">
                                                <span class="badge bg-success" style="font-size: 1rem;">
                                                    <?= !empty($profile['score']) ? $profile['score'] : 0 ?> Poin
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Quest Statistics -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="text-center p-3 profile-stats-card">
                                                <h4 class="mb-1 text-success"><?= $quest_stats['completed_main_quests'] ?></h4>
                                                <small class="text-muted">Main Quest Selesai</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-center p-3 profile-stats-card">
                                                <h4 class="mb-1 text-success"><?= $quest_stats['completed_side_quests'] ?></h4>
                                                <small class="text-muted">Side Quest Selesai</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Main Quests Card -->
                        <div class="col-md-6 mb-4" id="main-quests-grid">
                            <div class="card h-100 card-hover-effect">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                                            <i class="bi bi-flag me-2"></i>Main Quests Tersedia
                                        </h5>
                                        <div class="d-flex gap-2">
                                            <?php
                                            $accessible_count = 0;
                                            $locked_count = 0;
                                            $applied_count = 0;

                                            foreach ($main_quests as $quest) {
                                                if ($quest['already_applied'] > 0) {
                                                    $applied_count++;
                                                } elseif ($quest['accessibility_status'] == 'locked') {
                                                    $locked_count++;
                                                } else {
                                                    $accessible_count++;
                                                }
                                            }
                                            ?>
                                            <?php if ($accessible_count > 0): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-unlock me-1"></i><?= $accessible_count ?> Available
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($applied_count > 0): ?>
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-check me-1"></i><?= $applied_count ?> Applied
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($locked_count > 0): ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-lock me-1"></i><?= $locked_count ?> Locked
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $mq_progress = $main_quest_progress ?? [];
                                    $mq_percent = (int) ($mq_progress['progress_percent'] ?? 0);
                                    $mq_current_done = (int) ($mq_progress['current_completed'] ?? 0);
                                    $mq_current_total = (int) ($mq_progress['current_total'] ?? 0);
                                    $mq_current_level = $mq_progress['current_level_name'] ?? '';
                                    $mq_next_level = $mq_progress['next_level_name'] ?? '';
                                    $mq_next_unlocked = !empty($mq_progress['next_unlocked']);
                                    ?>
                                    <div class="mb-3 p-3" style="border:1px solid #bae7ff;background:linear-gradient(135deg,#f0f9ff 0%,#f6ffed 100%);border-radius:8px;">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                            <div>
                                                <div class="fw-bold" style="color:#0958d9;">
                                                    <i class="bi bi-controller me-1"></i>Progress Level Main Quest
                                                </div>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($mq_current_level ?: 'Current') ?><?= $mq_next_level ? ' menuju ' . htmlspecialchars($mq_next_level) : '' ?>
                                                </small>
                                            </div>
                                            <span class="badge" style="background:#e6f4ff;color:#0958d9;border:1px solid #91caff;">
                                                <?= $mq_current_done ?>/<?= $mq_current_total ?> quest
                                            </span>
                                        </div>
                                        <div class="progress" style="height:10px;border-radius:999px;background:#e5e7eb;">
                                            <div class="progress-bar <?= $mq_next_unlocked ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= $mq_percent ?>%;border-radius:999px;" aria-valuenow="<?= $mq_percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2 flex-wrap gap-1">
                                            <small class="<?= $mq_next_unlocked ? 'text-success' : 'text-muted' ?>">
                                                <?= $mq_next_unlocked ? '<i class="bi bi-unlock me-1"></i>Level berikutnya sudah terbuka.' : '<i class="bi bi-lock me-1"></i>Selesaikan quest level ini untuk membuka level berikutnya.' ?>
                                            </small>
                                        </div>
                                    </div>

                                    <?php if (!empty($main_quests)): ?>
                                        <div class="row">
                                            <?php foreach ($main_quests as $quest): ?>
                                                <?php
                                                // Determine card styling based on accessibility
                                                $is_accessible = $quest['accessibility_status'] == 'accessible';
                                                $is_applied = $quest['already_applied'] > 0;
                                                $is_approved = ($quest['submission_status'] ?? '') === 'approved';
                                                $is_locked = $quest['accessibility_status'] == 'locked';
                                                $is_career_path = !empty($quest['is_career_path_quest']) && $quest['is_career_path_quest'] == 1;
                                                $main_description_preview = trim(html_entity_decode(strip_tags((string) ($quest['description'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                                                $main_description_preview = strlen($main_description_preview) > 80 ? substr($main_description_preview, 0, 80) . '...' : $main_description_preview;

                                                if ($is_applied) {
                                                    $card_style = $is_approved
                                                        ? 'border-color: #52c41a !important; background-color: #f6ffed;'
                                                        : 'border-color: #faad14 !important; background-color: #fffbe6;';
                                                    $card_class = $is_approved ? 'border-success' : 'border-warning';
                                                } elseif ($is_locked) {
                                                    $card_style = 'border-color: #ff4d4f !important; background-color: #fff2f0; opacity: 0.7;';
                                                    $card_class = 'border-danger';
                                                } elseif ($is_career_path) {
                                                    $card_style = 'border-color: #52c41a !important; background: linear-gradient(to bottom, #f6ffed 0%, #ffffff 100%); border-width: 2px;';
                                                    $card_class = 'border-success';
                                                } else {
                                                    $card_style = 'border-color: #91d5ff !important;';
                                                    $card_class = 'border-info';
                                                }
                                                ?>
                                                <div class="col-12 mb-3 quest-item">
                                                    <div class="card border <?= $card_class ?>" style="<?= $card_style ?>">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div class="flex-grow-1">
                                                                    <div class="d-flex align-items-center mb-2">
                                                                        <?php if ($is_locked): ?>
                                                                            <i class="bi bi-lock-fill text-danger me-2"></i>
                                                                        <?php elseif ($is_approved): ?>
                                                                            <i class="bi bi-patch-check-fill text-success me-2"></i>
                                                                        <?php elseif ($is_applied): ?>
                                                                            <i class="bi bi-check-circle-fill text-warning me-2"></i>
                                                                        <?php else: ?>
                                                                            <i class="bi bi-star-fill text-primary me-2"></i>
                                                                        <?php endif; ?>
                                                                        <h6 class="card-title mb-0 <?= $is_locked ? 'text-muted' : '' ?>"><?= $quest['title'] ?></h6>

                                                                        <?php if (!empty($quest['is_career_path_quest']) && $quest['is_career_path_quest'] == 1): ?>
                                                                            <span class="badge ms-2" style="background: linear-gradient(135deg, #52c41a, #389e0d); color: white; font-size: 10px; animation: pulse-career-badge 2s ease-in-out infinite;">
                                                                                <i class="bi bi-bullseye me-1"></i>Your Career Path
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <p class="card-text text-muted small mb-2">
                                                                        <?= htmlspecialchars($main_description_preview, ENT_QUOTES, 'UTF-8') ?>
                                                                    </p>
                                                                    <div>
                                                                        <strong class="<?= $is_locked ? 'text-muted' : 'text-primary' ?>"><?= $quest['position_name'] ?></strong>
                                                                        <br>
                                                                        <span class="badge" style="<?= $is_locked ? 'background-color: #f5f5f5; color: #8c8c8c; border: 1px solid #d9d9d9;' : 'background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;' ?>">
                                                                            <?= $quest['level_name'] ?> Level
                                                                        </span>
                                                                        <?php if ($is_locked): ?>
                                                                            <br>
                                                                            <small class="text-danger mt-1">
                                                                                <i class="bi bi-arrow-up"></i> Level up required to unlock
                                                                            </small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="ms-2 d-flex flex-column align-items-end gap-2">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary view-main-quest-detail"
                                                                        data-quest-id="<?= $quest['id'] ?>"
                                                                        data-quest-title="<?= htmlspecialchars($quest['title'], ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-quest-description="<?= htmlspecialchars((string) ($quest['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-position-name="<?= htmlspecialchars((string) ($quest['position_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-level-name="<?= htmlspecialchars((string) ($quest['level_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-accessibility-status="<?= htmlspecialchars((string) ($quest['accessibility_status'] ?? 'unknown'), ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-unlock-note="<?= htmlspecialchars((string) ($quest['unlock_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-already-applied="<?= (int) $quest['already_applied'] ?>"
                                                                        data-submission-status="<?= htmlspecialchars((string) ($quest['submission_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                                        <i class="bi bi-eye me-1"></i>Detail
                                                                    </button>
                                                                    <?php if ($is_applied): ?>
                                                                        <?php if ($is_approved): ?>
                                                                            <span class="badge bg-success">
                                                                                <i class="bi bi-patch-check me-1"></i>Approved
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-warning">
                                                                                <i class="bi bi-hourglass-split me-1"></i>Menunggu Approval
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    <?php elseif ($is_locked): ?>
                                                                        <button class="btn btn-sm btn-outline-secondary" disabled>
                                                                            <i class="bi bi-lock me-1"></i>Locked
                                                                        </button>
                                                                    <?php else: ?>
                                                                        <button class="btn btn-sm btn-primary apply-main-quest"
                                                                            data-quest-id="<?= $quest['id'] ?>"
                                                                            data-quest-title="<?= htmlspecialchars($quest['title'], ENT_QUOTES, 'UTF-8') ?>">
                                                                            <i class="bi bi-play-fill me-1"></i>Apply
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="bi bi-flag" style="font-size: 3rem; color: #ccc;"></i>
                                            <h6 class="text-muted mt-3">Tidak ada main quest tersedia</h6>
                                            <p class="text-muted">Pastikan profil Anda sudah lengkap dengan posisi jabatan</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <nav id="main-quests-pager" class="mt-3"></nav>
                            </div>
                        </div>

                        <!-- Side Quests Card -->
                        <div class="col-md-6 mb-4" id="side-quests-grid">
                            <div class="card h-100 card-hover-effect">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                                            <i class="bi bi-star me-2"></i>Side Quests Tersedia
                                        </h5>
                                        <span class="badge bg-warning"><?= count($side_quests) ?> Quest</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($side_quests)): ?>
                                        <div class="row">
                                            <?php foreach ($side_quests as $quest): ?>
                                                <?php
                                                $side_description_preview = trim(html_entity_decode(strip_tags((string) ($quest['description'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                                                $side_description_preview = strlen($side_description_preview) > 80 ? substr($side_description_preview, 0, 80) . '...' : $side_description_preview;
                                                ?>
                                                <div class="col-12 mb-3 quest-item">
                                                    <div class="card border" style="border-color: #ffd591 !important;">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div class="flex-grow-1">
                                                                    <h6 class="card-title mb-2"><?= $quest['title'] ?></h6>
                                                                    <p class="card-text text-muted small mb-2">
                                                                        <?= htmlspecialchars($side_description_preview, ENT_QUOTES, 'UTF-8') ?>
                                                                    </p>
                                                                    <span class="badge" style="background-color: #fff7e6; color: #fa8c16; border: 1px solid #ffd591;">
                                                                        Terbuka untuk Semua
                                                                    </span>
                                                                </div>
                                                                <div class="ms-2">
                                                                    <?php if ($quest['already_applied'] > 0): ?>
                                                                        <span class="badge bg-info me-2">Applied <?= $quest['already_applied'] ?> times</span>
                                                                    <?php endif; ?>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-warning view-side-quest-detail mt-2"
                                                                        data-quest-id="<?= $quest['id'] ?>"
                                                                        data-quest-title="<?= htmlspecialchars($quest['title'], ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-quest-description="<?= htmlspecialchars((string) ($quest['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-quest-points="<?= isset($quest['points']) ? (int) $quest['points'] : 0 ?>"
                                                                        data-quest-reward="<?= htmlspecialchars((string) ($quest['reward'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-already-applied="<?= (int) $quest['already_applied'] ?>">
                                                                        Detail
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="bi bi-star" style="font-size: 3rem; color: #ccc;"></i>
                                            <h6 class="text-muted mt-3">Tidak ada side quest tersedia</h6>
                                            <p class="text-muted">Side quest akan muncul di sini</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <nav id="side-quests-pager" class="mt-3"></nav>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onboarding & Offboarding Tab -->
                <?php if ($show_transition_tab): ?>
                <div class="tab-pane fade" id="transition-content" role="tabpanel">
                    <style>
                        .transition-todo-card {
                            border: 1px solid #edf2f7;
                            border-radius: 10px;
                            padding: 14px 16px;
                            margin-bottom: 10px;
                            background: #fff;
                        }
                        .transition-todo-card .task-title {
                            font-weight: 600;
                            color: #2b2f4b;
                        }
                        .transition-task-status {
                            border-radius: 999px;
                            padding: 4px 10px;
                            font-size: 12px;
                            font-weight: 600;
                            border: 1px solid transparent;
                        }
                        .transition-task-status.status-todo {
                            color: #4f46e5;
                            background: #eef2ff;
                            border-color: #c7d2fe;
                        }
                        .transition-task-status.status-progress {
                            color: #b45309;
                            background: #fffbeb;
                            border-color: #fde68a;
                        }
                        .transition-task-status.status-done {
                            color: #166534;
                            background: #f0fdf4;
                            border-color: #bbf7d0;
                        }
                        #transitionTaskDetailModal {
                            z-index: 1065;
                        }
                        #transitionTaskDetailModal + .modal-backdrop,
                        .modal-backdrop.show {
                            z-index: 1060;
                        }
                    </style>
                    <div class="row">
                        <?php if ($show_onboarding): ?>
                        <div class="<?= $transition_col_class ?> mb-3">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <strong>Onboarding Checklist</strong>
                                    <small id="onboarding-progress-text" class="text-muted"><?= (int) $onboarding_profile['progress'] ?>% (<?= (int) $onboarding_profile['checked'] ?>/<?= (int) $onboarding_profile['total'] ?>)</small>
                                </div>
                                <div class="card-body">
                                    <div class="progress mb-3" style="height: 8px;">
                                        <div id="onboarding-progress-bar" class="progress-bar bg-primary" style="width: <?= (int) $onboarding_profile['progress'] ?>%;"></div>
                                    </div>
                                    <?php if (empty($onboarding_profile['tasks'])): ?>
                                        <p class="text-muted mb-0">Belum ada task onboarding yang aktif.</p>
                                    <?php else: ?>
                                        <?php foreach ($onboarding_profile['tasks'] as $task): ?>
                                            <?php
                                            $task_state = in_array((string) ($task['task_status'] ?? ''), ['todo', 'in_progress', 'done'], true)
                                                ? (string) $task['task_status']
                                                : ((int) $task['is_checked'] === 1 ? 'done' : 'todo');
                                            $task_status = 'status-todo';
                                            $task_status_label = 'To do';
                                            if ($task_state === 'done') {
                                                $task_status = 'status-done';
                                                $task_status_label = 'Done';
                                            } elseif ($task_state === 'in_progress') {
                                                $task_status = 'status-progress';
                                                $task_status_label = 'In progress';
                                            }
                                            $task_id = (int) ($task['task_id'] ?? $task['task_template_id'] ?? 0);
                                            $task_source = (string) ($task['task_source'] ?? 'template');
                                            $task_key = 'onboarding-' . $task_source . '-' . $task_id;
                                            ?>
                                            <div class="transition-todo-card">
                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                    <div>
                                                        <div class="task-title"><?= htmlspecialchars($task['task_name']) ?></div>
                                                        <small class="text-muted">
                                                            <?php if ((int) $task['is_required'] === 1): ?>Required<?php endif; ?>
                                                        </small>
                                                    </div>
                                                    <span class="transition-task-status <?= $task_status ?>"><?= $task_status_label ?></span>
                                                </div>
                                                <div class="d-flex align-items-center gap-3 mt-2">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary transition-task-detail-btn"
                                                        data-task-title="<?= htmlspecialchars($task['task_name']) ?>"
                                                        data-task-description-target="task-desc-<?= $task_key ?>"
                                                        data-task-attachment-path="<?= htmlspecialchars((string) ($task['attachment_path'] ?? '')) ?>"
                                                        data-task-attachment-name="<?= htmlspecialchars((string) ($task['attachment_name'] ?? '')) ?>">
                                                        Lihat Detail
                                                    </button>
                                                    <div>
                                                        <select class="form-select form-select-sm transition-task-status-select"
                                                            data-type="onboarding"
                                                            data-task-id="<?= $task_id ?>"
                                                            data-task-source="<?= htmlspecialchars($task_source) ?>"
                                                            style="min-width: 130px;">
                                                            <option value="todo" <?= $task_state === 'todo' ? 'selected' : '' ?>>To do</option>
                                                            <option value="in_progress" <?= $task_state === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                                            <option value="done" <?= $task_state === 'done' ? 'selected' : '' ?>>Done</option>
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
                        <?php endif; ?>
                        <?php if ($show_offboarding): ?>
                            <div class="<?= $transition_col_class ?> mb-3">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <strong>Offboarding Checklist</strong>
                                        <small id="offboarding-progress-text" class="text-muted"><?= (int) $offboarding_profile['progress'] ?>% (<?= (int) $offboarding_profile['checked'] ?>/<?= (int) $offboarding_profile['total'] ?>)</small>
                                    </div>
                                    <div class="card-body">
                                        <div class="progress mb-3" style="height: 8px;">
                                            <div id="offboarding-progress-bar" class="progress-bar bg-warning" style="width: <?= (int) $offboarding_profile['progress'] ?>%;"></div>
                                        </div>
                                        <?php if (empty($offboarding_profile['tasks'])): ?>
                                            <p class="text-muted mb-0">Belum ada task offboarding yang aktif.</p>
                                        <?php else: ?>
                                            <?php foreach ($offboarding_profile['tasks'] as $task): ?>
                                                <?php
                                                $task_state = in_array((string) ($task['task_status'] ?? ''), ['todo', 'in_progress', 'done'], true)
                                                    ? (string) $task['task_status']
                                                    : ((int) $task['is_checked'] === 1 ? 'done' : 'todo');
                                                $task_status = 'status-todo';
                                                $task_status_label = 'To do';
                                                if ($task_state === 'done') {
                                                    $task_status = 'status-done';
                                                    $task_status_label = 'Done';
                                                } elseif ($task_state === 'in_progress') {
                                                    $task_status = 'status-progress';
                                                    $task_status_label = 'In progress';
                                                }
                                                $task_id = (int) ($task['task_id'] ?? $task['task_template_id'] ?? 0);
                                                $task_source = (string) ($task['task_source'] ?? 'template');
                                                $task_key = 'offboarding-' . $task_source . '-' . $task_id;
                                                ?>
                                                <div class="transition-todo-card">
                                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                                        <div>
                                                            <div class="task-title"><?= htmlspecialchars($task['task_name']) ?></div>
                                                            <small class="text-muted">
                                                                <?php if ((int) $task['is_required'] === 1): ?>Required<?php endif; ?>
                                                            </small>
                                                        </div>
                                                        <span class="transition-task-status <?= $task_status ?>"><?= $task_status_label ?></span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-3 mt-2">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary transition-task-detail-btn"
                                                            data-task-title="<?= htmlspecialchars($task['task_name']) ?>"
                                                            data-task-description-target="task-desc-<?= $task_key ?>"
                                                            data-task-attachment-path="<?= htmlspecialchars((string) ($task['attachment_path'] ?? '')) ?>"
                                                            data-task-attachment-name="<?= htmlspecialchars((string) ($task['attachment_name'] ?? '')) ?>">
                                                            Lihat Detail
                                                        </button>
                                                        <div>
                                                            <select class="form-select form-select-sm transition-task-status-select"
                                                                data-type="offboarding"
                                                                data-task-id="<?= $task_id ?>"
                                                                data-task-source="<?= htmlspecialchars($task_source) ?>"
                                                                style="min-width: 130px;">
                                                                <option value="todo" <?= $task_state === 'todo' ? 'selected' : '' ?>>To do</option>
                                                                <option value="in_progress" <?= $task_state === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                                                <option value="done" <?= $task_state === 'done' ? 'selected' : '' ?>>Done</option>
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
                        <?php endif; ?>
                    </div>

                    <div class="modal fade" id="transitionTaskDetailModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="transition-task-detail-title">Detail Task</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="transition-task-detail-description" class="mb-3 text-break"></div>
                                    <div id="transition-task-detail-attachment-wrapper" style="display:none;">
                                        <h6 class="mb-1">Attachment</h6>
                                        <a id="transition-task-detail-attachment-link" href="#" target="_blank" class="btn btn-outline-secondary btn-sm"></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Milestone Tab -->
                <div class="tab-pane fade" id="milestone-content" role="tabpanel">
                    <!-- Leaderboard Section -->
                    <div class="mb-4">
                        <?php $this->load->view('profile/leaderboard_widget'); ?>
                    </div>

                    <!-- Milestone Achievement Section -->
                    <div class="mb-4">
                        <?php $this->load->view('profile/milestone_widget'); ?>
                    </div>
                </div>

                <!-- Performance Review Tab -->
                <div class="tab-pane fade" id="performance-content" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Penilaian Kinerja Saya</h5>
                        <a href="<?= base_url() ?>performance_review/my_assignments" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-pencil-square me-1"></i>Penilaian yang Harus Saya Isi
                            <?php if (!empty($pending_review_tasks)): ?><span class="badge bg-danger ms-1"><?= (int) $pending_review_tasks ?></span><?php endif; ?>
                        </a>
                    </div>
                    <p class="text-muted small">Hasil penilaian kinerja yang telah diterbitkan HR untuk Anda.</p>
                    <?php if (empty($published_reviews)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size:42px;"></i>
                            <p class="mt-2 mb-0">Belum ada hasil penilaian yang diterbitkan.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr><th>Periode</th><th>Template</th><th>Total</th><th>Hasil</th><th>Terbit</th><th class="text-end">Dokumen</th></tr></thead>
                                <tbody>
                                    <?php foreach ($published_reviews as $pr): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($pr['period_label']) ?></strong></td>
                                            <td><?= htmlspecialchars($pr['template_name'] ?? '-') ?></td>
                                            <td><?= $pr['final_total'] !== null ? rtrim(rtrim((string) $pr['final_total'], '0'), '.') : '-' ?></td>
                                            <td><?= !empty($pr['final_grade']) ? '<span class="badge bg-info">' . htmlspecialchars($pr['final_grade']) . '</span>' : '-' ?></td>
                                            <td class="small text-muted"><?= !empty($pr['published_at']) ? date('d M Y', strtotime($pr['published_at'])) : '-' ?></td>
                                            <td class="text-end">
                                                <a href="<?= base_url() ?>performance_review/pdf/<?= $pr['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>Lihat</a>
                                                <a href="<?= base_url() ?>performance_review/pdf/<?= $pr['id'] ?>?dl=1" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>PDF</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Activities & Reviews Tabbed Card -->
    <div class="card card-hover-effect" id="activities-reviews-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <nav class="nav nav-tabs card-header-tabs" id="activity-tabs">
                <a class="nav-link active" data-bs-toggle="tab" href="#recent-activities" role="tab">
                    <i class="bi bi-clock-history me-2"></i>Aktifitas Quest Terbaru
                </a>
                <a class="nav-link" data-bs-toggle="tab" href="#my-reviews" role="tab">
                    <i class="bi bi-collection me-2"></i>My Review
                    <?php if (!empty($my_reviews)): ?>
                        <span class="badge bg-primary ms-1"><?= count($my_reviews) ?></span>
                    <?php endif; ?>
                </a>
            </nav>
            <div class="d-flex gap-2">
                <a href="<?= base_url() ?>profile/reviews" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-collection me-1"></i>Community Reviews
                </a>
                <a href="<?= base_url() ?>profile/quest-history" class="btn btn-outline-primary btn-sm">
                    Lihat Semua
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="tab-content" id="activity-tab-content">
                <!-- Recent Activities Tab -->
                <div class="tab-pane fade show active" id="recent-activities" role="tabpanel">
                    <?php if (!empty($recent_submissions)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover recent-activities-table align-middle">
                                <thead>
                                    <tr>
                                        <th width="12%">Tipe</th>
                                        <th width="30%">Quest</th>
                                        <th width="12%">Status</th>
                                        <th width="18%">Tanggal Submit</th>
                                        <th width="15%">Benefit</th>
                                        <th width="13%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_submissions as $submission): ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?= $submission['quest_type'] == 'main' ? 'bg-primary' : 'bg-warning text-dark' ?>">
                                                    <?= $submission['quest_type'] == 'main' ? '🎯 Main' : '⭐ Side' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($submission['quest_title']) ?></strong>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                $status_text = '';
                                                switch ($submission['status']) {
                                                    case 'pending':
                                                        $status_class = 'bg-warning text-dark';
                                                        $status_text = '🟡 Pending';
                                                        break;
                                                    case 'approved':
                                                        $status_class = 'bg-success';
                                                        $status_text = '✅ Disetujui';
                                                        break;
                                                    case 'denied':
                                                        $status_class = 'bg-danger';
                                                        $status_text = '❌ Ditolak';
                                                        break;
                                                    case 'canceled':
                                                        $status_class = 'bg-secondary';
                                                        $status_text = '⭕ Dibatalkan';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($submission['submitted_at'])) ?>
                                                    <br>
                                                    <i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($submission['submitted_at'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if (!empty($submission['benefit_type'])): ?>
                                                    <?php
                                                    $benefit_icon = '';
                                                    $benefit_class = '';
                                                    switch ($submission['benefit_type']) {
                                                        case 'promotion':
                                                            $benefit_icon = 'bi-arrow-up-circle';
                                                            $benefit_class = 'bg-primary';
                                                            break;
                                                        case 'bonus':
                                                            $benefit_icon = 'bi-currency-dollar';
                                                            $benefit_class = 'bg-warning text-dark';
                                                            break;
                                                        case 'salary':
                                                            $benefit_icon = 'bi-cash-stack';
                                                            $benefit_class = 'bg-success';
                                                            break;
                                                        case 'leave':
                                                            $benefit_icon = 'bi-calendar-heart';
                                                            $benefit_class = 'bg-info';
                                                            break;
                                                        case 'wfa':
                                                            $benefit_icon = 'bi-house';
                                                            $benefit_class = 'bg-secondary';
                                                            break;
                                                        default:
                                                            $benefit_icon = 'bi-gift';
                                                            $benefit_class = 'bg-info';
                                                    }
                                                    ?>
                                                    <span class="badge <?= $benefit_class ?>">
                                                        <i class="bi <?= $benefit_icon ?> me-1"></i><?= ucfirst($submission['benefit_type']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($submission['status'] == 'pending'): ?>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php if ($submission['quest_type'] == 'side'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-primary edit-review"
                                                                data-submission-id="<?= $submission['submission_id'] ?>"
                                                                data-quest-id="<?= $submission['quest_id'] ?>"
                                                                data-quest-title="<?= htmlspecialchars($submission['quest_title'], ENT_QUOTES, 'UTF-8') ?>"
                                                                title="Edit hasil quest">
                                                                <i class="bi bi-pencil-square me-1"></i>Edit
                                                            </button>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger cancel-quest"
                                                            data-quest-type="<?= $submission['quest_type'] ?>"
                                                            data-submission-id="<?= $submission['submission_id'] ?>"
                                                            data-quest-title="<?= htmlspecialchars($submission['quest_title'], ENT_QUOTES, 'UTF-8') ?>"
                                                            title="Batalkan aplikasi quest">
                                                            <i class="bi bi-x-circle me-1"></i>Cancel
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- View All Link -->
                        <?php if (count($recent_submissions) >= 5): ?>
                        <div class="text-center mt-3 pb-2">
                            <a href="<?= base_url('profile/quest_history') ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-list-ul me-1"></i> Lihat Semua Riwayat Quest
                            </a>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history" style="font-size: 3rem; color: #ccc;"></i>
                            <h6 class="text-muted mt-3">Belum ada aktifitas quest</h6>
                            <p class="text-muted">Mulai apply quest untuk melihat aktifitas di sini</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- My Reviews Tab -->
                <div class="tab-pane fade" id="my-reviews" role="tabpanel">
                    <?php if (!empty($my_reviews)): ?>
                        <div class="row" id="reviews-grid">
                            <?php foreach ($my_reviews as $review): ?>
                                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                    <div class="review-card" data-submission-id="<?= $review['id'] ?>" style="cursor: pointer;">
                                        <div class="card h-100 review-item-card">
                                            <div class="card-img-wrapper">
                                                <?php
                                                $image_url = 'https://placehold.co/400x200/f0f0f0/666666?text=No+Image'; // Default placeholder

                                                if (!empty($review['submission_image'])) {
                                                    $upload_path = FCPATH . 'assets/uploads/side_quest_user_images/' . $review['submission_image'];
                                                    if (file_exists($upload_path)) {
                                                        $image_url = base_url() . 'assets/uploads/side_quest_user_images/' . $review['submission_image'];
                                                    }
                                                }
                                                ?>
                                                <img src="<?= $image_url ?>" class="card-img-top" alt="<?= htmlspecialchars($review['submission_title']) ?>"
                                                    onerror="this.src='https://placehold.co/400x200/f0f0f0/666666?text=No+Image'">
                                                <div class="card-img-overlay-bottom">
                                                    <h6 class="text-white mb-1"><?= htmlspecialchars($review['submission_title']) ?></h6>
                                                    <small class="text-white-50"><?= htmlspecialchars($review['quest_title']) ?></small>
                                                </div>
                                                <div class="card-status-badge">
                                                    <?php
                                                    $status_class = '';
                                                    $status_text = '';
                                                    switch ($review['status']) {
                                                        case 'pending':
                                                            $status_class = 'bg-warning';
                                                            $status_text = 'Pending';
                                                            break;
                                                        case 'approved':
                                                            $status_class = 'bg-success';
                                                            $status_text = 'Approved';
                                                            break;
                                                        case 'denied':
                                                            $status_class = 'bg-danger';
                                                            $status_text = 'Denied';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                                </div>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted"><?= date('d M Y', strtotime($review['submitted_at'])) ?></small>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-eye text-primary" title="View Details"></i>
                                                        <?php
                                                        // Check if this is a Film or Book quest - now deletable regardless of status
                                                        $isFilmQuest = strpos(strtolower($review['quest_title']), 'nonton film') !== false;
                                                        $isBookQuest = strpos(strtolower($review['quest_title']), 'baca buku') !== false;
                                                        $isDeletable = ($isFilmQuest || $isBookQuest);
                                                        $isEditable = ($isFilmQuest || $isBookQuest) && ($review['status'] == 'pending');

                                                        // Debug: uncomment to see values
                                                        echo "<!-- Quest: {$review['quest_title']}, Film: " . ($isFilmQuest ? 'YES' : 'NO') . ", Book: " . ($isBookQuest ? 'YES' : 'NO') . ", Status: {$review['status']}, Editable: " . ($isEditable ? 'YES' : 'NO') . ", Deletable: " . ($isDeletable ? 'YES' : 'NO') . " -->";
                                                        ?>
                                                        <?php if ($isEditable): ?>
                                                            <i class="bi bi-pencil text-warning edit-review"
                                                               title="Edit Quest"
                                                               style="cursor: pointer;"
                                                               data-submission-id="<?= $review['id'] ?>"
                                                               data-quest-id="<?= $review['quest_id'] ?>"
                                                               data-quest-title="<?= htmlspecialchars($review['quest_title'], ENT_QUOTES, 'UTF-8') ?>"
                                                               data-submission-title="<?= htmlspecialchars($review['submission_title'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                                        <?php endif; ?>
                                                        <?php if ($isDeletable): ?>
                                                            <button class="btn btn-sm btn-outline-danger delete-review"
                                                                style="padding: 2px 6px; font-size: 0.75rem; border-radius: 4px;"
                                                                title="Delete Review"
                                                                data-submission-id="<?= $review['id'] ?>"
                                                                data-quest-title="<?= htmlspecialchars($review['quest_title']) ?>"
                                                                data-submission-title="<?= htmlspecialchars($review['submission_title']) ?>">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-collection" style="font-size: 3rem; color: #ccc;"></i>
                            <h6 class="text-muted mt-3">Belum ada review</h6>
                            <p class="text-muted">Mulai mengerjakan quest Film atau Buku untuk membuat review</p>
                            <div class="mt-3">
                                <small class="text-muted">Review akan muncul setelah Anda mengerjakan quest:</small>
                                <div class="d-flex justify-content-center gap-2 mt-2">
                                    <span class="badge bg-primary">🎬 Nonton Film</span>
                                    <span class="badge bg-success">📚 Baca Buku</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TinyMCE Rich Text Editor -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>

<!-- Profile Edit Modal -->
<div class="modal fade" id="profileEditModal" tabindex="-1" aria-labelledby="profileEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileEditModalLabel">Edit Profil Lengkap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-message"></div>
                <form action="<?= base_url() ?>profile/update_process" method="POST" id="form-profile-edit" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name">Nama Lengkap</label>
                            <input type="text" class="form-control" name="dt[full_name]" value="<?= $user_data['full_name'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" name="dt[username]" value="<?= $user_data['username'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" name="dt[email]" value="<?= $user_data['email'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password">Password Baru</label>
                            <input type="password" class="form-control" name="dt[password]" placeholder="Kosongkan jika tidak diubah">
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="birth_date">Tanggal Lahir</label>
                            <input type="date" class="form-control" name="dt[birth_date]" value="<?= $user_data['birth_date'] ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="file">Foto Profil</label>
                            <input type="file" class="form-control" name="file" accept="image/png, image/jpeg, image/jpg">
                            <?php if ($user_data['img']): ?>
                                <small class="text-muted">Foto saat ini: <a href="<?= $img_url ?>" target="_blank">Lihat foto</a></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="desc">Keterangan</label>
                            <textarea class="form-control" name="dt[desc]" rows="3"><?= $user_data['desc'] ?></textarea>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="join_date">Tanggal Bergabung</label>
                            <input type="date" class="form-control" id="join_date" name="profile[join_date]" value="<?= !empty($profile['join_date']) ? $profile['join_date'] : '' ?>" readonly>
                            <small class="text-muted">Field ini hanya bisa diubah oleh admin/HR.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="birth_place_date">Tempat, Tanggal Lahir</label>
                            <input type="text" class="form-control" id="birth_place_date" name="profile[birth_place_date]" value="<?= !empty($profile['birth_place_date']) ? $profile['birth_place_date'] : '' ?>" placeholder="Jakarta, 15 Januari 1990">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="jenis_kontrak">Jenis Kontrak</label>
                            <select class="form-control" id="jenis_kontrak" disabled>
                                <option value="">-- Pilih Jenis Kontrak --</option>
                                <?php foreach (["PKWT", "PKWTT"] as $type): ?>
                                    <option value="<?= $type ?>" <?= (!empty($profile['jenis_kontrak']) && $profile['jenis_kontrak'] == $type) ? 'selected' : '' ?>>
                                        <?= $type ?> <?= $type == 'PKWT' ? '(Kontrak)' : '(Tetap)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="profile[jenis_kontrak]" value="<?= !empty($profile['jenis_kontrak']) ? $profile['jenis_kontrak'] : '' ?>">
                            <small class="text-muted">Field ini hanya bisa diubah oleh admin/HR.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lama_kontrak">Lama Kontrak</label>
                            <input type="text" class="form-control" id="lama_kontrak" name="profile[lama_kontrak]" value="<?= !empty($profile['lama_kontrak']) ? $profile['lama_kontrak'] : '' ?>" placeholder="Contoh: 12 bulan, 2 tahun" readonly>
                            <small class="text-muted">Field ini hanya bisa diubah oleh admin/HR.</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="is_probation">Probation</label>
                            <?php $probation_value = isset($profile['is_probation']) ? $profile['is_probation'] : ''; ?>
                            <select class="form-control" id="is_probation" disabled>
                                <option value="0" <?= $probation_value === '0' || $probation_value === 0 ? 'selected' : '' ?>>Tidak</option>
                                <option value="1" <?= $probation_value === '1' || $probation_value === 1 ? 'selected' : '' ?>>Ya</option>
                            </select>
                            <input type="hidden" name="profile[is_probation]" value="<?= $probation_value !== '' ? $probation_value : '0' ?>">
                            <small class="text-muted">Field ini hanya bisa diubah oleh admin/HR.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender">Jenis Kelamin</label>
                            <select class="form-control" id="gender" name="profile[gender]">
                                <option value="">-- Pilih Jenis Kelamin --</option>
                                <?php foreach (["Laki-laki", "Perempuan"] as $gender): ?>
                                    <option value="<?= $gender ?>" <?= (!empty($profile['gender']) && $profile['gender'] == $gender) ? 'selected' : '' ?>><?= $gender ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="religion">Agama</label>
                            <select class="form-control" id="religion" name="profile[religion]">
                                <option value="">-- Pilih Agama --</option>
                                <?php foreach (["Islam", "Kristen", "Katolik", "Hindu", "Buddha", "Konghucu"] as $religion): ?>
                                    <option value="<?= $religion ?>" <?= (!empty($profile['religion']) && $profile['religion'] == $religion) ? 'selected' : '' ?>><?= $religion ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="marital_status">Status Pernikahan</label>
                            <select class="form-control" id="marital_status" name="profile[marital_status]">
                                <option value="">-- Pilih Status --</option>
                                <?php foreach (["Belum Menikah", "Menikah", "Cerai"] as $status): ?>
                                    <option value="<?= $status ?>" <?= (!empty($profile['marital_status']) && $profile['marital_status'] == $status) ? 'selected' : '' ?>><?= $status ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone_number">Nomor Telepon</label>
                            <input type="text" class="form-control" id="phone_number" name="profile[phone_number]" value="<?= !empty($profile['phone_number']) ? $profile['phone_number'] : '' ?>" placeholder="+62812345678">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nik">NIK</label>
                            <input type="text" class="form-control" id="nik" name="profile[nik]" value="<?= !empty($profile['nik']) ? $profile['nik'] : '' ?>" placeholder="3171234567890001">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="full_address">Alamat Lengkap</label>
                            <textarea class="form-control" id="full_address" name="profile[full_address]" rows="3" placeholder="Alamat lengkap sesuai KTP"><?= !empty($profile['full_address']) ? $profile['full_address'] : '' ?></textarea>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="bank_name">Nama Bank</label>
                            <input type="text" class="form-control" id="bank_name" name="profile[bank_name]" value="<?= !empty($profile['bank_name']) ? $profile['bank_name'] : '' ?>" placeholder="BCA, Mandiri, BNI, dll">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="bank_account_number">Nomor Rekening</label>
                            <input type="text" class="form-control" id="bank_account_number" name="profile[bank_account_number]" value="<?= !empty($profile['bank_account_number']) ? $profile['bank_account_number'] : '' ?>" placeholder="1234567890">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="account_holder_name">Nama Pemegang Rekening</label>
                            <input type="text" class="form-control" id="account_holder_name" name="profile[account_holder_name]" value="<?= !empty($profile['account_holder_name']) ? $profile['account_holder_name'] : '' ?>" placeholder="Sesuai dengan rekening bank">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="hobby">Hobi</label>
                            <textarea class="form-control" id="hobby" name="profile[hobby]" rows="2" placeholder="Ceritakan hobi Anda"><?= !empty($profile['hobby']) ? $profile['hobby'] : '' ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="hobby_reason">Alasan Hobi</label>
                            <textarea class="form-control" id="hobby_reason" name="profile[hobby_reason]" rows="2" placeholder="Mengapa Anda menyukai hobi tersebut?"><?= !empty($profile['hobby_reason']) ? $profile['hobby_reason'] : '' ?></textarea>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="favorite_food_drink">Makanan/Minuman Favorit</label>
                            <textarea class="form-control" id="favorite_food_drink" name="profile[favorite_food_drink]" rows="2" placeholder="Makanan dan minuman favorit"><?= !empty($profile['favorite_food_drink']) ? $profile['favorite_food_drink'] : '' ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="food_drink_reason">Alasan Favorit</label>
                            <textarea class="form-control" id="food_drink_reason" name="profile[food_drink_reason]" rows="2" placeholder="Mengapa menjadi favorit?"><?= !empty($profile['food_drink_reason']) ? $profile['food_drink_reason'] : '' ?></textarea>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="favorite_color">Warna Favorit</label>
                            <input type="text" class="form-control" id="favorite_color" name="profile[favorite_color]" value="<?= !empty($profile['favorite_color']) ? $profile['favorite_color'] : '' ?>" placeholder="Merah, Biru, Hijau, dll">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="favorite_sport">Olahraga Favorit</label>
                            <input type="text" class="form-control" id="favorite_sport" name="profile[favorite_sport]" value="<?= !empty($profile['favorite_sport']) ? $profile['favorite_sport'] : '' ?>" placeholder="Badminton, Futsal, Renang, dll">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="ktp_photo">Foto KTP</label>
                            <?php if (!empty($profile['ktp_photo'])): ?>
                                <div class="mb-2">
                                    <a href="<?= base_url() ?>/assets/img/ktp/<?= $profile['ktp_photo'] ?>" target="_blank" class="text-primary">
                                        <i class="bi bi-image me-1"></i>Lihat KTP Saat Ini
                                    </a>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="ktp_photo" name="ktp_photo" accept="image/png, image/jpeg, image/jpg">
                            <small class="text-muted">Format: JPG, JPEG, PNG. Maksimal 2MB.</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="form-profile-edit" class="btn btn-primary btn-save">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>

<!-- Main Quest Detail Modal -->
<div class="modal fade" id="mainQuestDetailModal" tabindex="-1" aria-labelledby="mainQuestDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mainQuestDetailModalLabel">Detail Main Quest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5 class="mb-3" id="main-quest-detail-title">-</h5>

                <div class="mb-3">
                    <div class="text-muted small mb-1">Deskripsi</div>
                    <div id="main-quest-detail-description" class="p-3" style="background: #fafafa; border: 1px solid #f0f0f0; border-radius: 6px; white-space: pre-wrap;">-</div>
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="p-3" style="background: #e6f7ff; border: 1px solid #91d5ff; border-radius: 6px;">
                            <div class="text-muted small">Required Position</div>
                            <div id="main-quest-detail-position" class="fw-semibold" style="color: #096dd9;">-</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3" style="background: #f6ffed; border: 1px solid #b7eb8f; border-radius: 6px;">
                            <div class="text-muted small">Quest Level</div>
                            <div id="main-quest-detail-level" class="fw-semibold" style="color: #389e0d;">-</div>
                        </div>
                    </div>
                </div>

                <div id="main-quest-detail-status" class="alert mt-3 mb-0" style="display:none;"></div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <small id="main-quest-detail-applied-note" class="text-muted"></small>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="detail-apply-main-quest-btn" class="btn btn-primary apply-main-quest" data-quest-id="" data-quest-title="">
                        <i class="bi bi-play-fill me-1"></i>Apply Quest
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Quest Apply Modal -->
<div class="modal fade" id="mainQuestModal" tabindex="-1" aria-labelledby="mainQuestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mainQuestModalLabel">Apply Main Quest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-message-quest"></div>
                <p>Apakah Anda yakin ingin mengajukan aplikasi untuk quest "<span id="main-quest-title"></span>"?</p>
                <p class="text-muted small">Setelah diajukan, aplikasi Anda akan direview oleh HR dan tidak dapat dibatalkan.</p>
                <form id="form-main-quest">
                    <input type="hidden" id="main-quest-id" name="quest_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="form-main-quest" class="btn btn-primary btn-apply-main">Apply Quest</button>
            </div>
        </div>
    </div>
</div>

<!-- Side Quest Apply Modal -->
<div class="modal fade" id="sideQuestModal" tabindex="-1" aria-labelledby="sideQuestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sideQuestModalLabel">Apply Side Quest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-message-side-quest"></div>
                <p>Quest: "<span id="side-quest-title"></span>"</p>
                <form id="form-side-quest" enctype="multipart/form-data" action="javascript:void(0);">
                    <input type="hidden" id="side-quest-id" name="quest_id">

                    <!-- Conditional Fields for Film and Book Quests -->
                    <div id="conditional-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="submission_title" class="form-label">
                                    <span id="title-label">Judul</span> <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="submission_title" name="submission_title"
                                    placeholder="Masukkan judul film atau buku">
                                <small class="text-muted" id="title-help">Masukkan judul lengkap</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="submission_image" class="form-label">
                                    <span id="image-label">Gambar</span> <small class="text-muted">(opsional)</small>
                                </label>
                                <input type="file" class="form-control" id="submission_image" name="submission_image"
                                    accept="image/*">
                                <small class="text-muted" id="image-help">Upload cover atau gambar terkait (max 2MB)</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="hasil" class="form-label">Hasil Kerja <span class="text-danger">*</span></label>

                        <!-- Summernote Rich Text Editor -->
                        <div id="hasil-editor"></div>

                        <!-- Hidden field for plain text version -->
                        <input type="hidden" id="hasil" name="hasil">


                        <small class="text-muted" id="hasil-help">
                            <i class="bi bi-info-circle me-1"></i>
                            Jelaskan hasil pekerjaan Anda dengan detail. HR akan mengevaluasi dan memberikan skor berdasarkan kualitas hasil kerja yang Anda submit.
                        </small>

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>Batal
                </button>
                <button type="submit" form="form-side-quest" class="btn btn-warning btn-apply-side">
                    <i class="bi bi-send me-1"></i>Submit Hasil Kerja
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Side Quest Detail Modal -->
<div class="modal fade" id="sideQuestDetailModal" tabindex="-1" aria-labelledby="sideQuestDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sideQuestDetailModalLabel">Detail Side Quest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5 class="mb-3" id="side-quest-detail-title">-</h5>

                <div class="mb-3">
                    <div class="text-muted small mb-1">Deskripsi</div>
                    <div id="side-quest-detail-description" class="p-3" style="background: #fafafa; border: 1px solid #f0f0f0; border-radius: 6px; white-space: pre-wrap;">-</div>
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="p-3" style="background: #fff7e6; border: 1px solid #ffd591; border-radius: 6px;">
                            <div class="text-muted small">Poin Quest</div>
                            <div id="side-quest-detail-points" class="fw-semibold" style="color: #d46b08;">0</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3" style="background: #f6ffed; border: 1px solid #b7eb8f; border-radius: 6px;">
                            <div class="text-muted small">Reward</div>
                            <div id="side-quest-detail-reward" class="fw-semibold" style="color: #389e0d;">-</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <small id="side-quest-detail-applied-note" class="text-muted"></small>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="detail-apply-side-quest-btn" class="btn btn-warning apply-side-quest" data-quest-id="" data-quest-title="">
                        <i class="bi bi-play-fill me-1"></i><span id="detail-apply-side-quest-label">Apply</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quest Cancel Confirmation Modal -->
<div class="modal fade" id="cancelQuestModal" tabindex="-1" aria-labelledby="cancelQuestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 1px solid #f0f0f0;">
                <h5 class="modal-title" id="cancelQuestModalLabel" style="color: rgba(0,0,0,0.85);">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>Batalkan Aplikasi Quest
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div class="form-message-cancel"></div>
                <div class="alert alert-warning" style="background-color: #fff7e6; border: 1px solid #ffd591; border-radius: 6px;">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Perhatian:</strong> Tindakan ini tidak dapat dibatalkan.
                </div>
                <p style="margin-bottom: 16px;">
                    Apakah Anda yakin ingin membatalkan aplikasi untuk quest:
                </p>
                <div style="background-color: #f8f9fa; padding: 12px; border-radius: 6px; border-left: 4px solid #ff4d4f;">
                    <strong id="cancel-quest-title" style="color: rgba(0,0,0,0.85);"></strong>
                </div>
                <form id="form-cancel-quest">
                    <input type="hidden" id="cancel-submission-id" name="submission_id">
                    <input type="hidden" id="cancel-quest-type" name="quest_type">
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f0f0f0;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>Tidak, Kembali
                </button>
                <button type="submit" form="form-cancel-quest" class="btn btn-danger btn-cancel-quest">
                    <i class="bi bi-trash me-1"></i>Ya, Batalkan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Review Detail Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">
                    <i class="bi bi-collection me-2"></i>Review Detail
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- IMAGE (top) -->
                <div class="review-image-container text-center mb-4">
                    <img
                        id="review-modal-image"
                        class="img-fluid rounded"
                        style="max-height: 400px; object-fit: cover; width: 100%;"
                        alt="Review Image" />
                </div>

                <!-- CONTENT (now below image) -->
                <div class="review-content mt-2">
                    <h4 id="review-modal-title" class="mb-1"></h4>
                    <p class="text-muted mb-3" id="review-modal-quest"></p>

                    <div class="review-meta mb-4">
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <span id="review-modal-status" class="badge"></span>
                            </div>
                            <div class="col">
                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i>
                                    Submitted: <span id="review-modal-date"></span>
                                </small>
                                <span id="review-modal-approved-date" class="d-block mt-1 text-success" style="display:none!important;">
                                    <small>
                                        <i class="bi bi-check-circle me-1"></i>
                                        Approved: <span id="review-modal-approved-date-text"></span>
                                    </small>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="review-text-content">
                        <h6 class="mb-2"><i class="bi bi-chat-text me-2"></i>Review Content:</h6>
                        <div id="review-modal-content"
                            class="review-text p-3 bg-light rounded"
                            style="max-height: 320px; overflow-y: auto; line-height: 1.6;"></div>
                    </div>

                    <div class="mt-4" id="review-modal-hr-notes-section" style="display:none;">
                        <h6 class="mb-2"><i class="bi bi-person-badge me-2"></i>HR Notes:</h6>
                        <div class="alert alert-info" id="review-modal-hr-notes"></div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Quest Modal -->
<div class="modal fade" id="editReviewModal" tabindex="-1" aria-labelledby="editReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editReviewModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Quest
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-message-edit-review"></div>
                <p>Quest: "<span id="edit-quest-title"></span>"</p>
                <form id="form-edit-review" enctype="multipart/form-data" action="javascript:void(0);">
                    <input type="hidden" id="edit-submission-id" name="submission_id">
                    <input type="hidden" id="edit-quest-id" name="quest_id">

                    <!-- Title Field for Film and Book Reviews -->
                    <div id="edit-conditional-fields">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_submission_title" class="form-label">
                                    <span id="edit-title-label">Judul</span> <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="edit_submission_title" name="submission_title"
                                    placeholder="Masukkan judul film atau buku">
                                <small class="text-muted" id="edit-title-help">Masukkan judul lengkap</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_submission_image" class="form-label">
                                    <span id="edit-image-label">Gambar</span> <small class="text-muted">(opsional)</small>
                                </label>
                                <input type="file" class="form-control" id="edit_submission_image" name="submission_image"
                                    accept="image/*">
                                <small class="text-muted" id="edit-image-help">Upload cover atau gambar terkait (max 2MB)</small>

                                <!-- Current image preview -->
                                <div id="current-image-preview" class="mt-2" style="display: none;">
                                    <small class="text-muted">Gambar saat ini:</small>
                                    <div class="mt-1">
                                        <img id="current-image" src="" alt="Current Image"
                                             class="img-thumbnail" style="max-height: 100px; max-width: 100px;">
                                        <small class="d-block text-muted mt-1">Upload file baru untuk mengubah gambar</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_hasil" class="form-label">Hasil Kerja <span class="text-danger">*</span></label>

                        <!-- Rich Text Editor -->
                        <div id="edit-hasil-editor"></div>

                        <!-- Hidden field for plain text version -->
                        <input type="hidden" id="edit_hasil" name="hasil">

                        <small class="text-muted" id="edit-hasil-help">
                            <i class="bi bi-info-circle me-1"></i>
                            Jelaskan hasil pekerjaan Anda dengan detail. HR akan mengevaluasi dan memberikan skor berdasarkan kualitas hasil kerja yang Anda submit.
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>Batal
                </button>
                <button type="submit" form="form-edit-review" class="btn btn-primary btn-update-review">
                    <i class="bi bi-check-lg me-1"></i>Update Quest
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        const PER_PAGE = 3;
        const FORCE_SHOW_SINGLE_PAGE = false; // set true to always show pager

        function setupPagination(gridSel, pagerSel, perPage, urlKey) {
            const grid = document.querySelector(gridSel);
            if (!grid) {
                console.warn('Grid not found:', gridSel);
                return;
            }

            // ensure pager exists
            let pager = document.querySelector(pagerSel);
            if (!pager) {
                pager = document.createElement('nav');
                pager.id = pagerSel.replace(/^#/, '');
                grid.parentElement.appendChild(pager);
                console.info('Created missing pager:', pagerSel);
            }

            const items = Array.from(grid.querySelectorAll('.quest-item'));
            const total = items.length;
            if (total === 0) {
                pager.innerHTML = '';
                return;
            }

            const pages = Math.max(1, Math.ceil(total / perPage));

            // read initial page from URL
            const params = new URLSearchParams(window.location.search);
            let current = Math.min(Math.max(parseInt(params.get(urlKey) || '1', 10), 1), pages);

            // show a page
            function show(page) {
                const start = (page - 1) * perPage;
                const end = start + perPage;
                items.forEach((el, idx) => {
                    el.style.display = (idx >= start && idx < end) ? '' : 'none';
                });
            }

            // render pager
            function renderPager() {
                // hide pager if only one page and not forcing
                if (pages === 1 && !FORCE_SHOW_SINGLE_PAGE) {
                    pager.innerHTML = '';
                    return;
                }

                const ul = document.createElement('ul');
                ul.className = 'pagination justify-content-center mb-0';

                const addBtn = (label, page, disabled = false, active = false) => {
                    const li = document.createElement('li');
                    li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                    const a = document.createElement('a');
                    a.className = 'page-link';
                    a.href = 'javascript:void(0)';
                    a.textContent = label;
                    a.addEventListener('click', () => {
                        if (!disabled && !active) goTo(page);
                    });
                    li.appendChild(a);
                    ul.appendChild(li);
                };

                addBtn('Previous', current - 1, current <= 1);

                // compact page window
                const windowSize = 5;
                let start = Math.max(1, current - Math.floor(windowSize / 2));
                let end = Math.min(pages, start + windowSize - 1);
                start = Math.max(1, Math.min(start, pages - windowSize + 1));

                if (start > 1) addBtn('1', 1, false, current === 1);
                if (start > 2) addBtn('…', current, true, false);

                for (let i = start; i <= end; i++) addBtn(String(i), i, false, i === current);

                if (end < pages - 1) addBtn('…', current, true, false);
                if (end < pages) addBtn(String(pages), pages, false, current === pages);

                addBtn('Next', current + 1, current >= pages);

                pager.innerHTML = '';
                pager.appendChild(ul);
            }

            function goTo(page) {
                current = Math.min(Math.max(page, 1), pages);
                show(current);
                renderPager();

                // update URL without reload
                const url = new URL(window.location.href);
                url.searchParams.set(urlKey, current);
                history.replaceState(null, '', url.toString());
            }

            // initial render
            show(current);
            renderPager();

            // diagnostics
            console.info(`[pager] ${gridSel}: total=${total}, pages=${pages}, current=${current}`);
        }

        // init both pagers
        setupPagination('#main-quests-grid', '#main-quests-pager', PER_PAGE, 'mp');
        setupPagination('#side-quests-grid', '#side-quests-pager', PER_PAGE, 'sp');
        // Profile Edit Form
        $("#form-profile-edit").submit(function() {
            var form = $(this);
            var mydata = new FormData(this);
            $.ajax({
                type: "POST",
                url: form.attr("action"),
                data: mydata,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $(".btn-save").addClass("disabled").html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...').attr('disabled', true);
                    form.find(".form-message").slideUp().html("");
                },
                success: function(response) {
                    var str = response;
                    if (str.indexOf("success") != -1) {
                        $(".form-message").hide().html(response).slideDown("fast");
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $(".form-message").hide().html(response).slideDown("fast");
                        $(".btn-save").removeClass("disabled").html('Simpan Perubahan').attr('disabled', false);
                    }
                },
                error: function() {
                    $(".btn-save").removeClass("disabled").html('Simpan Perubahan').attr('disabled', false);
                    $(".form-message").hide().html('<div class="alert alert-danger">Terjadi kesalahan sistem.</div>').slideDown("fast");
                }
            });
            return false;
        });

        function profileEscapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        $(document).on('click', '.view-main-quest-detail', function(e) {
            e.preventDefault();

            var questId = $(this).data('quest-id');
            var questTitle = $(this).data('quest-title') || '-';
            var questDescription = $(this).data('quest-description') || 'Belum ada deskripsi.';
            var positionName = $(this).data('position-name') || '-';
            var levelName = $(this).data('level-name') || '-';
            var accessibilityStatus = $(this).data('accessibility-status') || 'unknown';
            var unlockNote = $(this).data('unlock-note') || '';
            var alreadyApplied = parseInt($(this).data('already-applied') || 0, 10);
            var submissionStatus = $(this).data('submission-status') || '';

            $('#main-quest-detail-title').text(questTitle);
            $('#main-quest-detail-description').html(questDescription);
            $('#main-quest-detail-position').text(positionName);
            $('#main-quest-detail-level').text(levelName + ' Level');
            $('#detail-apply-main-quest-btn')
                .attr('data-quest-id', questId)
                .attr('data-quest-title', questTitle)
                .prop('disabled', false)
                .show();

            if (alreadyApplied > 0) {
                if (submissionStatus === 'approved') {
                    $('#main-quest-detail-applied-note').text('Quest ini sudah disetujui HR.');
                    $('#main-quest-detail-status')
                        .removeClass()
                        .addClass('alert alert-success mt-3 mb-0')
                        .html('<i class="bi bi-patch-check me-2"></i>Quest ini sudah approved.')
                        .show();
                } else {
                    $('#main-quest-detail-applied-note').text('Anda sudah apply quest ini.');
                    $('#main-quest-detail-status')
                        .removeClass()
                        .addClass('alert alert-warning mt-3 mb-0')
                        .html('<i class="bi bi-hourglass-split me-2"></i>Quest ini sudah diajukan dan menunggu approval HR.')
                        .show();
                }
                $('#detail-apply-main-quest-btn').hide();
            } else if (accessibilityStatus === 'locked') {
                $('#main-quest-detail-applied-note').text(unlockNote || 'Quest ini masih terkunci.');
                $('#main-quest-detail-status')
                    .removeClass()
                    .addClass('alert alert-danger mt-3 mb-0')
                    .html('<i class="bi bi-lock me-2"></i>' + profileEscapeHtml(unlockNote || 'Quest ini masih terkunci. Selesaikan level sebelumnya untuk membuka quest ini.'))
                    .show();
                $('#detail-apply-main-quest-btn').prop('disabled', true).show();
            } else {
                $('#main-quest-detail-applied-note').text(unlockNote || 'Quest ini tersedia untuk Anda apply.');
                $('#main-quest-detail-status')
                    .removeClass()
                    .addClass('alert alert-success mt-3 mb-0')
                    .html('<i class="bi bi-unlock me-2"></i>Quest ini tersedia. Posisi tidak berubah otomatis; hasil quest tetap menunggu approval HR.')
                    .show();
            }

            $('#mainQuestDetailModal').modal('show');
        });

        // Main Quest Apply
        $(document).on('click', '.apply-main-quest', function() {
            var questId = $(this).data('quest-id');
            var questTitle = $(this).data('quest-title');

            $('#main-quest-id').val(questId);
            $('#main-quest-title').text(questTitle);

            if ($('#mainQuestDetailModal').hasClass('show')) {
                $('#mainQuestDetailModal').one('hidden.bs.modal', function() {
                    $('#mainQuestModal').modal('show');
                }).modal('hide');
            } else {
                $('#mainQuestModal').modal('show');
            }
        });

        $("#form-main-quest").submit(function() {
            var form = $(this);
            var mydata = form.serialize();
            $.ajax({
                type: "POST",
                url: "<?= base_url() ?>profile/apply-main-quest",
                data: mydata,
                beforeSend: function() {
                    $(".btn-apply-main").addClass("disabled").html('<span class="spinner-border spinner-border-sm me-2"></span>Mengajukan...').attr('disabled', true);
                    $(".form-message-quest").slideUp().html("");
                },
                success: function(response) {
                    var str = response;
                    if (str.indexOf("success") != -1) {
                        $(".form-message-quest").hide().html(response).slideDown("fast");
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $(".form-message-quest").hide().html(response).slideDown("fast");
                        $(".btn-apply-main").removeClass("disabled").html('Apply Quest').attr('disabled', false);
                    }
                },
                error: function() {
                    $(".btn-apply-main").removeClass("disabled").html('Apply Quest').attr('disabled', false);
                    $(".form-message-quest").hide().html('<div class="alert alert-danger">Terjadi kesalahan sistem.</div>').slideDown("fast");
                }
            });
            return false;
        });


        // Global TinyMCE editor instance reference
        var tinyMCEInstance = null;

        // Initialize TinyMCE Editor with improved loading and fallback
        function initializeHasilEditor() {
            try {

                // Show loading indicator
                $('#hasil-editor').before('<div id="editor-loading" class="text-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading editor...</span></div><p class="mt-2 text-muted">Loading rich text editor...</p></div>');

                // Destroy existing TinyMCE instance if it exists
                if (tinymce.get('hasil-editor')) {
                    tinymce.remove('#hasil-editor');
                }

                // Check if TinyMCE is available
                if (typeof tinymce === 'undefined') {

                    // Set up event listeners for TinyMCE loading events
                    $(document).one('tinymce:loaded', function() {
                        $('#editor-loading').remove();
                        initializeTinyMCEInstance();
                    });

                    $(document).one('tinymce:failed', function() {
                        $('#editor-loading').remove();
                        setupFallbackTextarea();
                        return false;
                    });

                    // Set a timeout in case the events never fire
                    setTimeout(function() {
                        if (typeof tinymce === 'undefined') {
                            $('#editor-loading').remove();
                            setupFallbackTextarea();
                            $(document).off('tinymce:loaded');
                        }
                    }, 5000);

                    return false;
                } else {
                    // TinyMCE is already loaded, initialize it directly
                    $('#editor-loading').remove();
                    return initializeTinyMCEInstance();
                }
            } catch (error) {
                $('#editor-loading').remove();
                setupFallbackTextarea();
                return false;
            }
        }

        // Function to initialize TinyMCE instance
        function initializeTinyMCEInstance() {
            try {
                // Initialize TinyMCE
                tinymce.init({
                    selector: '#hasil-editor',
                    height: 300,
                    min_height: 300,
                    max_height: 500,
                    menubar: false,
                    branding: false,
                    placeholder: 'Masukkan dokumentasi hasil kerja Anda...\n\n📝 Catatan atau ringkasan pekerjaan\n🎥 Link video dokumentasi\n📁 Link file atau folder hasil kerja\n✨ Penjelasan proses dan hasil yang dicapai\n\nGunakan toolbar di atas untuk memformat teks!',
                    plugins: 'lists link image table code help wordcount',
                    toolbar: 'styles | bold italic underline | fontfamily fontsize | forecolor | alignleft aligncenter alignright | bullist numlist | link image | table | code fullscreen help',
                    font_family_formats: 'Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Helvetica=helvetica; Impact=impact,chicago; Tahoma=tahoma,arial,helvetica,sans-serif; Times New Roman=times new roman,times; Verdana=verdana,geneva',
                    font_size_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 36pt',
                    setup: function(editor) {
                        // Store the editor instance
                        editor.on('init', function() {
                            tinyMCEInstance = editor;

                            // Add visual feedback
                            setTimeout(() => {
                                const editorContainer = $(editor.getContainer());
                                if (editorContainer.length) {
                                    editorContainer.css('box-shadow', '0 0 10px rgba(24, 144, 255, 0.3)');
                                    setTimeout(() => {
                                        editorContainer.css('box-shadow', '');
                                    }, 1000);
                                }

                                // Focus the editor
                                editor.focus();
                            }, 300);
                        });

                        // Sync content to hidden field on change
                        editor.on('change', function() {
                            const htmlContent = editor.getContent();
                            const textContent = $(editor.getContent({
                                format: 'text'
                            })).text();

                            $('#hasil').val(textContent || editor.getContent({
                                format: 'text'
                            }));
                            // hasil-editor already contains the HTML content
                        });
                    },
                    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; padding: 10px; }',
                    mobile: {
                        menubar: false,
                        toolbar: 'bold italic | bullist numlist | link'
                    }
                }).then(function() {
                    return true;
                }).catch(function(error) {
                    setupFallbackTextarea();
                    return false;
                });

                return true;
            } catch (error) {
                setupFallbackTextarea();
                return false;
            }
        }

        // Function to set up fallback textarea when TinyMCE fails
        function setupFallbackTextarea() {
            const textarea = document.querySelector('#hasil-editor');
            if (textarea) {
                // Show a notification to the user
                $('<div class="alert alert-warning mb-3" id="editor-fallback-notice">' +
                    '<i class="bi bi-exclamation-triangle me-2"></i>' +
                    'Rich text editor could not be loaded. Using simple text editor instead.' +
                    '</div>').insertBefore(textarea);

                // Style the textarea to look better
                textarea.style.display = 'block';
                textarea.style.minHeight = '300px';
                textarea.style.border = '2px solid #1890ff';
                textarea.style.borderRadius = '8px';
                textarea.style.padding = '15px';
                textarea.style.fontSize = '14px';
                textarea.style.fontFamily = 'Arial, sans-serif';
                textarea.placeholder = '📝 Masukkan dokumentasi hasil kerja Anda...\n\n• Catatan atau ringkasan pekerjaan\n• Link video dokumentasi\n• Link file atau folder hasil kerja\n• Penjelasan proses dan hasil yang dicapai\n\n(Simple text editor mode - you can still type here!)';

                // Add event listener to sync with hidden field
                $(textarea).on('input', function() {
                    $('#hasil').val($(this).val());
                });

                // Focus the textarea
                textarea.focus();
            }
        }

        // Side Quest Apply - using event delegation for better reliability
        $(document).on('click', '.view-side-quest-detail', function(e) {
            e.preventDefault();

            var questId = $(this).data('quest-id');
            var questTitle = $(this).data('quest-title') || '-';
            var questDescription = $(this).data('quest-description') || 'Belum ada deskripsi.';
            var questPoints = $(this).data('quest-points');
            var questReward = $(this).data('quest-reward') || '-';
            var alreadyApplied = parseInt($(this).data('already-applied') || 0, 10);

            $('#side-quest-detail-title').text(questTitle);
            $('#side-quest-detail-description').html(questDescription);
            $('#side-quest-detail-points').text((questPoints !== undefined && questPoints !== null && questPoints !== '') ? questPoints : '0');
            $('#side-quest-detail-reward').text(questReward);

            var applyLabel = alreadyApplied > 0 ? 'Apply Again' : 'Apply';
            $('#detail-apply-side-quest-label').text(applyLabel);
            $('#detail-apply-side-quest-btn')
                .attr('data-quest-id', questId)
                .attr('data-quest-title', questTitle);

            if (alreadyApplied > 0) {
                $('#side-quest-detail-applied-note').text('Anda sudah apply quest ini ' + alreadyApplied + ' kali.');
            } else {
                $('#side-quest-detail-applied-note').text('Quest ini belum pernah Anda apply.');
            }

            $('#sideQuestDetailModal').modal('show');
        });

        $(document).on('click', '.apply-side-quest', function(e) {
            e.preventDefault();

            $('#sideQuestDetailModal').modal('hide');

            var questId = $(this).data('quest-id');
            var questTitle = $(this).data('quest-title');

            $('#side-quest-id').val(questId);
            $('#side-quest-title').text(questTitle);
            $('#submission_title').val(''); // Clear title field
            $('#submission_image').val(''); // Clear image field

            // Detect quest type by ID (ID 1 = Book, ID 4 = Film)
            var isBookQuest = (parseInt(questId) === 1);
            var isFilmQuest = (parseInt(questId) === 4);

            // Show conditional fields for all quests
            $('#conditional-fields').show();
            $('#submission_title').prop('required', true);

            if (isFilmQuest) {
                // Configure for film quest
                $('#title-label').text('Judul Film');
                $('#title-help').text('Masukkan judul film yang ditonton');
                $('#image-label').text('Cover/Screenshot Film');
                $('#image-help').text('Upload cover film atau screenshot (max 2MB)');
                $('#submission_title').attr('placeholder', 'Contoh: Parasite (2019)');
            } else if (isBookQuest) {
                // Configure for book quest
                $('#title-label').text('Judul Buku');
                $('#title-help').text('Masukkan judul buku dan nama penulis');
                $('#image-label').text('Cover Buku');
                $('#image-help').text('Upload foto cover buku (max 2MB)');
                $('#submission_title').attr('placeholder', 'Contoh: Atomic Habits - James Clear');
            } else {
                // Configure for other quests with generic labels
                $('#title-label').text('Judul');
                $('#title-help').text('Masukkan judul');
                $('#image-label').text('Cover');
                $('#image-help').text('Upload cover atau gambar terkait (max 2MB)');
                $('#submission_title').attr('placeholder', 'Masukkan judul');
            }

            // Show loading indicator for the editor
            $('#hasil-editor').before('<div id="editor-init-loading" class="text-center p-3 mb-3"><div class="spinner-border text-warning" role="status"><span class="visually-hidden">Preparing editor...</span></div><p class="mt-2 text-muted">Preparing rich text editor...</p></div>');

            // Initialize rich text editor with a slight delay to allow modal to fully render
            setTimeout(() => {
                $('#editor-init-loading').remove();

                // Initialize the editor
                initializeHasilEditor();
            }, 300);

            // Show modal
            $('#sideQuestModal').modal('show');
        });

        $("#form-side-quest").submit(function(e) {
            e.preventDefault(); // Prevent default form submission
            var form = $(this);

            // Extract content from TinyMCE editor - with safer content extraction
            var hasilHtml = '';
            var hasilText = '';

            try {
                if (tinyMCEInstance && tinymce.get('hasil-editor')) {
                    // Get content from TinyMCE
                    hasilHtml = tinymce.get('hasil-editor').getContent();

                    // Get plain text safely
                    try {
                        hasilText = tinymce.get('hasil-editor').getContent({
                            format: 'text'
                        }).trim();
                    } catch (e) {
                        // Fallback: strip tags manually
                        hasilText = $('<div>').html(hasilHtml).text().trim();
                    }
                } else {
                    // Fallback to textarea value
                    hasilText = $('#hasil-editor').val().trim();
                    hasilHtml = hasilText.replace(/\n/g, '<br>');
                }

                // Ensure we have valid content
                if (!hasilText && hasilHtml) {
                    hasilText = $('<div>').html(hasilHtml).text().trim();
                }

            } catch (e) {
                // Emergency fallback
                hasilText = $('#hasil').val() || $('#hasil-editor').val() || 'Content extraction failed';
                hasilHtml = '<p>' + hasilText.replace(/\n/g, '<br>') + '</p>';
            }

            // Validate hasil field - check editor content
            if (!hasilText || hasilText.length < 10) {
                $(".form-message-side-quest").html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Hasil kerja harus diisi minimal 10 karakter!</div>').slideDown("fast");
                return false;
            }

            // Update hidden fields with editor content
            $('#hasil').val(hasilText);

            // Note: hasil_html is already the name of the main textarea, so TinyMCE handles this automatically

            // Additional validation for Film and Book quests (ID 1 = Film, ID 4 = Book)
            var questId = parseInt($('#side-quest-id').val());
            var isFilmQuest = (questId === 1);
            var isBookQuest = (questId === 4);
            var isReviewQuest = (isFilmQuest || isBookQuest);

            if (isReviewQuest && !$('#submission_title').val().trim()) {
                $(".form-message-side-quest").html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Judul ' + (isFilmQuest ? 'film' : 'buku') + ' harus diisi!</div>').slideDown("fast");
                return false;
            }

            // Validate file upload if present
            var fileInput = $('#submission_image')[0];
            if (fileInput.files.length > 0) {
                var file = fileInput.files[0];
                var maxSize = 2 * 1024 * 1024; // 2MB in bytes

                if (file.size > maxSize) {
                    $(".form-message-side-quest").html('<div class="alert alert-danger"><i class="bi bi-file-earmark-x me-2"></i>Ukuran file terlalu besar! Maksimal 2MB.</div>').slideDown("fast");
                    return false;
                }

                // Check file type
                var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    $(".form-message-side-quest").html('<div class="alert alert-danger"><i class="bi bi-file-earmark-x me-2"></i>Format file tidak didukung! Gunakan JPG, PNG, atau WEBP.</div>').slideDown("fast");
                    return false;
                }
            }

            // Use FormData for file upload support
            var formData = new FormData();
            formData.append('quest_id', questId);
            formData.append('hasil', hasilText);
            formData.append('hasil_html', hasilHtml);

            if (isReviewQuest) {
                formData.append('submission_title', $('#submission_title').val());
                if (fileInput.files.length > 0) {
                    formData.append('submission_image', fileInput.files[0]);
                }
            }

            $.ajax({
                type: "POST",
                url: "<?= base_url('profile/apply_side_quest') ?>",
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    $(".btn-apply-side").addClass("disabled").html('<span class="spinner-border spinner-border-sm me-2"></span>Mengirim hasil kerja...').attr('disabled', true);
                    $(".form-message-side-quest").slideUp().html("");

                    // Disable editor during submission - safely check if setMode exists
                    try {
                        if (tinyMCEInstance && tinymce.get('hasil-editor') && typeof tinymce.get('hasil-editor').setMode === 'function') {
                            tinymce.get('hasil-editor').setMode('readonly');
                        } else if (tinyMCEInstance && tinymce.get('hasil-editor')) {
                            // Alternative method to disable editor if setMode is not available
                            tinymce.get('hasil-editor').setContent(tinymce.get('hasil-editor').getContent());
                            tinymce.get('hasil-editor').getBody().setAttribute('contenteditable', false);
                        }
                    } catch (e) {
                        console.log('Error disabling editor:', e);
                    }
                },
                success: function(response) {
                    if (response.status === 'success') {
                        // Show success message
                        $(".form-message-side-quest").hide().html('<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>' + response.message + '</div>').slideDown("fast");

                        // Reload page after delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        // Show error message
                        $(".form-message-side-quest").hide().html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + (response.message || 'Terjadi kesalahan saat menyimpan data.') + '</div>').slideDown("fast");
                        $(".btn-apply-side").removeClass("disabled").html('<i class="bi bi-send me-1"></i>Submit Hasil Kerja').attr('disabled', false);

                        // Re-enable editor on error
                        if (tinyMCEInstance && tinymce.get('hasil-editor')) {
                            tinymce.get('hasil-editor').setMode('design');
                        }
                    }
                },
                error: function(xhr, status, error) {

                    $(".btn-apply-side").removeClass("disabled").html('<i class="bi bi-send me-1"></i>Submit Hasil Kerja').attr('disabled', false);
                    $(".form-message-side-quest").hide().html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Terjadi kesalahan sistem. Silakan coba lagi.</div>').slideDown("fast");

                    // Re-enable editor on error - safely
                    try {
                        if (tinyMCEInstance && tinymce.get('hasil-editor') && typeof tinymce.get('hasil-editor').setMode === 'function') {
                            tinymce.get('hasil-editor').setMode('design');
                        } else if (tinyMCEInstance && tinymce.get('hasil-editor')) {
                            tinymce.get('hasil-editor').getBody().setAttribute('contenteditable', true);
                        }
                    } catch (e) {
                        console.log('Error re-enabling editor:', e);
                    }
                }
            });
            return false;
        });

        // Modal cleanup when side quest modal is closed
        $('#sideQuestModal').on('hidden.bs.modal', function() {
            // Clear form messages
            $('.form-message-side-quest').html('').hide();

            // Clean up TinyMCE editor
            setTimeout(() => {
                if (tinymce.get('hasil-editor')) {
                    try {
                        tinymce.remove('#hasil-editor');
                    } catch (e) {
                        console.log('TinyMCE cleanup error:', e);
                    }
                }

                // Remove any fallback notices
                $('#editor-fallback-notice').remove();
            }, 200);
        });

        // Quest Cancellation
        $('.cancel-quest').click(function() {
            var questType = $(this).data('quest-type');
            var submissionId = $(this).data('submission-id');
            var questTitle = $(this).data('quest-title');

            $('#cancel-submission-id').val(submissionId);
            $('#cancel-quest-type').val(questType);
            $('#cancel-quest-title').text(questTitle);
            $('#cancelQuestModal').modal('show');
        });

        $("#form-cancel-quest").submit(function() {
            var form = $(this);
            var questType = $('#cancel-quest-type').val();
            var submissionId = $('#cancel-submission-id').val();

            // Determine the correct endpoint based on quest type
            var url = questType === 'main' ?
                "<?= base_url() ?>profile/cancel_main_quest" :
                "<?= base_url() ?>profile/cancel_side_quest";

            $.ajax({
                type: "POST",
                url: url,
                data: {
                    submission_id: submissionId
                },
                beforeSend: function() {
                    $(".btn-cancel-quest").addClass("disabled").html('<span class="spinner-border spinner-border-sm me-2"></span>Membatalkan...').attr('disabled', true);
                    $(".form-message-cancel").slideUp().html("");
                },
                success: function(response) {
                    var str = response;
                    if (str.indexOf("success") != -1) {
                        $(".form-message-cancel").hide().html(response).slideDown("fast");
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $(".form-message-cancel").hide().html(response).slideDown("fast");
                        $(".btn-cancel-quest").removeClass("disabled").html('<i class="bi bi-trash me-1"></i>Ya, Batalkan').attr('disabled', false);
                    }
                },
                error: function() {
                    $(".btn-cancel-quest").removeClass("disabled").html('<i class="bi bi-trash me-1"></i>Ya, Batalkan').attr('disabled', false);
                    $(".form-message-cancel").hide().html('<div class="alert alert-danger">Terjadi kesalahan sistem.</div>').slideDown("fast");
                }
            });
            return false;
        });

        // Delete Review Handler
        $(document).on('click', '.delete-review', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent card click event

            var submissionId = $(this).data('submission-id');
            var questTitle = $(this).data('quest-title');
            var submissionTitle = $(this).data('submission-title');
            var reviewCard = $(this).closest('.review-card');

            // Show confirmation dialog
            Swal.fire({
                title: 'Hapus Review?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">Apakah Anda yakin ingin menghapus review ini?</p>
                        <div class="bg-light p-3 rounded mb-3">
                            <strong>Quest:</strong> ${questTitle}<br>
                            <strong>Review:</strong> ${submissionTitle}
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Perhatian:</strong> Tindakan ini tidak dapat dibatalkan!
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash me-1"></i>Ya, Hapus',
                cancelButtonText: '<i class="bi bi-x me-1"></i>Batal',
                customClass: {
                    popup: 'text-start'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Menghapus Review...',
                        text: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // AJAX call to delete review
                    $.ajax({
                        url: '<?= base_url() ?>profile/delete_review',
                        method: 'POST',
                        data: {
                            submission_id: submissionId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: response.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Remove the review card from DOM with animation
                                    reviewCard.fadeOut(300, function() {
                                        $(this).remove();

                                        // Check if no reviews left
                                        if ($('.review-card').length === 0) {
                                            $('#reviews-grid').html(`
                                                <div class="col-12">
                                                    <div class="text-center py-5">
                                                        <i class="bi bi-collection" style="font-size: 3rem; color: #ccc;"></i>
                                                        <h6 class="text-muted mt-3">Belum ada review</h6>
                                                        <p class="text-muted">Mulai mengerjakan quest Film atau Buku untuk membuat review</p>
                                                        <div class="mt-3">
                                                            <small class="text-muted">Review akan muncul setelah Anda mengerjakan quest:</small>
                                                            <div class="d-flex justify-content-center gap-2 mt-2">
                                                                <span class="badge bg-primary">🎬 Nonton Film</span>
                                                                <span class="badge bg-success">📚 Baca Buku</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            `);
                                        }
                                    });
                                });
                            } else {
                                Swal.fire({
                                    title: 'Gagal!',
                                    text: response.message || 'Terjadi kesalahan saat menghapus review',
                                    icon: 'error'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete review error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'Terjadi kesalahan koneksi. Silakan coba lagi.',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

        // Review Card Click Handler (updated to prevent conflict with delete button)
        $(document).on('click', '.review-card', function(e) {
            // Don't trigger if delete button was clicked
            if ($(e.target).hasClass('delete-review') || $(e.target).closest('.delete-review').length > 0) {
                return;
            }
            var submissionId = $(this).data('submission-id');

            // Show loading state
            $('#reviewModal .modal-body').html('<div class="text-center py-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3 text-muted">Loading review details...</p></div>');
            $('#reviewModal').modal('show');

            // AJAX call to get full submission details
            $.ajax({
                url: '<?= base_url() ?>profile/get_review_detail',
                method: 'POST',
                data: {
                    submission_id: submissionId
                },
                success: function(response) {
                    try {
                        var data = JSON.parse(response);

                        if (data.error) {
                            $('#reviewModal .modal-body').html('<div class="text-center py-5"><i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i><h6 class="text-muted mt-3">Error loading review</h6><p class="text-muted">' + data.error + '</p></div>');
                            return;
                        }

                        // Restore original modal structure
                        var modalHTML =
                            '<div class="review-image-container text-center mb-4">' +
                            '<img id="review-modal-image" class="img-fluid rounded" ' +
                            '     style="max-height:400px; object-fit:cover; width:100%;" alt="Review Image">' +
                            '</div>' +

                            '<div class="review-content mt-2">' +
                            '<h4 id="review-modal-title" class="mb-1"></h4>' +
                            '<p class="text-muted mb-3" id="review-modal-quest"></p>' +

                            '<div class="review-meta mb-4">' +
                            '<div class="row g-2 align-items-center">' +
                            '<div class="col-auto">' +
                            '<span id="review-modal-status" class="badge"></span>' +
                            '</div>' +
                            '<div class="col">' +
                            '<small class="text-muted">' +
                            '<i class="bi bi-calendar me-1"></i>Submitted: <span id="review-modal-date"></span>' +
                            '</small>' +
                            '<span id="review-modal-approved-date" class="d-block mt-1 text-success" style="display: none!important;">' +
                            '<small><i class="bi bi-check-circle me-1"></i>Approved: ' +
                            '<span id="review-modal-approved-date-text"></span></small>' +
                            '</span>' +
                            '</div>' +
                            '</div>' +
                            '</div>' +

                            '<div class="review-text-content">' +
                            '<h6 class="mb-2"><i class="bi bi-chat-text me-2"></i>Review Content:</h6>' +
                            '<div id="review-modal-content" class="review-text p-3 bg-light rounded" ' +
                            '     style="max-height:320px; overflow-y:auto; line-height:1.6;"></div>' +
                            '</div>' +

                            '<div class="mt-4" id="review-modal-hr-notes-section" style="display:none;">' +
                            '<h6 class="mb-2"><i class="bi bi-person-badge me-2"></i>HR Notes:</h6>' +
                            '<div class="alert alert-info" id="review-modal-hr-notes"></div>' +
                            '</div>' +
                            '</div>';


                        $('#reviewModal .modal-body').html(modalHTML);

                        // Populate modal with data
                        $('#review-modal-title').text(data.submission_title || 'No Title');
                        $('#review-modal-quest').text(data.quest_title || '');

                        // Display rich text content if available, otherwise fallback to plain text
                        var content = '';
                        if (data.hasil_html && data.hasil_html.trim() !== '') {
                            content = data.hasil_html;
                            $('#review-modal-content').removeClass('review-text').addClass('review-html-content');
                        } else {
                            content = (data.hasil || 'No content').replace(/\n/g, '<br>');
                            $('#review-modal-content').removeClass('review-html-content').addClass('review-text');
                        }
                        $('#review-modal-content').html(content);
                        $('#review-modal-date').text(data.submitted_at_formatted || '');

                        // Set status badge
                        var statusClass = getStatusClass(data.status);
                        var statusText = getStatusText(data.status);
                        $('#review-modal-status').removeClass().addClass('badge ' + statusClass).text(statusText);

                        // Handle image - use placehold.co as fallback
                        var modalImageSrc = data.submission_image ?
                            '<?= base_url() ?>assets/uploads/side_quest_user_images/' + data.submission_image :
                            'https://placehold.co/800x400/f0f0f0/666666?text=No+Image';

                        $('#review-modal-image').attr('src', modalImageSrc)
                            .attr('onerror', "this.src='https://placehold.co/800x400/f0f0f0/666666?text=No+Image'");
                        $('.review-image-container').show();

                        // Handle approved date
                        if (data.approved_at_formatted && data.status === 'approved') {
                            $('#review-modal-approved-date-text').text(data.approved_at_formatted);
                            $('#review-modal-approved-date').show();
                        }

                        // Handle HR notes
                        if (data.hr_notes && data.hr_notes.trim() !== '') {
                            $('#review-modal-hr-notes').text(data.hr_notes);
                            $('#review-modal-hr-notes-section').show();
                        }

                    } catch (e) {
                        $('#reviewModal .modal-body').html('<div class="text-center py-5"><i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i><h6 class="text-muted mt-3">Error parsing data</h6><p class="text-muted">Please try again later.</p></div>');
                    }
                },
                error: function() {
                    $('#reviewModal .modal-body').html('<div class="text-center py-5"><i class="bi bi-wifi-off text-danger" style="font-size: 3rem;"></i><h6 class="text-muted mt-3">Connection error</h6><p class="text-muted">Please check your internet connection and try again.</p></div>');
                }
            });
        });

        // Edit Quest Handler
        $(document).on('click', '.edit-review', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent card click event

            var submissionId = $(this).data('submission-id');
            var questId = parseInt($(this).data('quest-id'), 10);
            var questTitle = $(this).data('quest-title');
            var submissionTitle = $(this).data('submission-title');

            // Set modal data
            $('#edit-submission-id').val(submissionId);
            $('#edit-quest-id').val(questId || '');
            $('#edit-quest-title').text(questTitle);

            // Detect quest type and update labels
            var titleLower = (questTitle || '').toLowerCase();
            var isFilmQuest = questId === 1 || titleLower.includes('nonton film');
            var isBookQuest = questId === 4 || titleLower.includes('baca buku');
            var isReviewQuest = isFilmQuest || isBookQuest;

            if (isReviewQuest) {
                $('#edit-conditional-fields').show();
                $('#edit_submission_title').prop('required', true);

                if (isFilmQuest) {
                    $('#edit-title-label').text('Judul Film');
                    $('#edit-title-help').text('Masukkan judul film lengkap');
                    $('#edit-image-label').text('Poster Film');
                    $('#edit-image-help').text('Upload poster film (max 2MB)');
                } else {
                    $('#edit-title-label').text('Judul Buku');
                    $('#edit-title-help').text('Masukkan judul buku lengkap');
                    $('#edit-image-label').text('Cover Buku');
                    $('#edit-image-help').text('Upload cover buku (max 2MB)');
                }
            } else {
                $('#edit-conditional-fields').hide();
                $('#edit_submission_title').prop('required', false);
            }

            // Clear form
            $('.form-message-edit-review').html('').hide();
            $('#edit_submission_title').val('');
            $('#edit_submission_image').val('');
            $('#current-image-preview').hide();

            // Load existing quest submission data
            $.ajax({
                url: '<?= base_url() ?>profile/get_review_detail',
                method: 'POST',
                data: { submission_id: submissionId },
                success: function(response) {
                    try {
                        var data = JSON.parse(response);
                        if (data.error) {
                            $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + data.error + '</div>').slideDown();
                            return;
                        }

                        var loadedQuestId = parseInt(data.quest_id, 10);
                        if (loadedQuestId) {
                            $('#edit-quest-id').val(loadedQuestId);
                        }

                        // Populate form fields
                        $('#edit_submission_title').val(data.submission_title || '');

                        // Set up current image preview
                        if (data.submission_image) {
                            var imageSrc = '<?= base_url() ?>assets/uploads/side_quest_user_images/' + data.submission_image;
                            $('#current-image').attr('src', imageSrc);
                            $('#current-image-preview').show();
                        }

                        // Initialize TinyMCE for edit modal
                        setTimeout(() => {
                            initializeTinyMCEForEditModal(data.hasil_html || data.hasil || '');
                        }, 300);

                        // Show modal
                        $('#editReviewModal').modal('show');

                    } catch (e) {
                        console.error('Error parsing review data:', e);
                        $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Error loading review data</div>').slideDown();
                    }
                },
                error: function() {
                    $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-wifi-off me-2"></i>Connection error. Please try again.</div>').slideDown();
                }
            });
        });

        // Initialize TinyMCE for Edit Modal
        function initializeTinyMCEForEditModal(content) {
            // Clean up any existing instance
            if (tinymce.get('edit-hasil-editor')) {
                try {
                    tinymce.remove('#edit-hasil-editor');
                } catch (e) {
                    console.log('TinyMCE cleanup error:', e);
                }
            }

            // Initialize TinyMCE
            tinymce.init({
                selector: '#edit-hasil-editor',
                height: 300,
                menubar: false,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | \
                         alignleft aligncenter alignright alignjustify | \
                         bullist numlist outdent indent | link image | help',
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                setup: function (editor) {
                    editor.on('init', function () {
                        editor.setContent(content);
                    });
                },
                init_instance_callback: function(editor) {
                    console.log('TinyMCE Edit Modal initialized');
                }
            });
        }

        // Edit Quest Form Submit
        $('#form-edit-review').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            // Get content from TinyMCE
            var tinyMCEEditInstance = tinymce.get('edit-hasil-editor');
            var hasilHtml = '';
            var hasilText = '';

            try {
                if (tinyMCEEditInstance && tinyMCEEditInstance.getContent) {
                    hasilHtml = tinyMCEEditInstance.getContent();

                    // Convert HTML to plain text for validation
                    try {
                        var tempDiv = document.createElement('div');
                        tempDiv.innerHTML = hasilHtml;
                        hasilText = tempDiv.textContent || tempDiv.innerText || '';
                    } catch (e) {
                        hasilText = $('<div>').html(hasilHtml).text().trim();
                    }
                } else {
                    // Fallback to textarea value
                    hasilText = $('#edit-hasil-editor').val().trim();
                    hasilHtml = hasilText.replace(/\n/g, '<br>');
                }

                // Ensure we have valid content
                if (!hasilText && hasilHtml) {
                    hasilText = $('<div>').html(hasilHtml).text().trim();
                }

            } catch (e) {
                // Emergency fallback
                hasilText = $('#edit_hasil').val() || $('#edit-hasil-editor').val() || 'Content extraction failed';
                hasilHtml = '<p>' + hasilText.replace(/\n/g, '<br>') + '</p>';
            }

            // Validate content
            if (!hasilText || hasilText.length < 10) {
                $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Hasil kerja harus diisi minimal 10 karakter!</div>').slideDown();
                return false;
            }

            var editQuestId = parseInt($('#edit-quest-id').val(), 10);
            var editQuestTitle = ($('#edit-quest-title').text() || '').toLowerCase();
            var isEditReviewQuest = editQuestId === 1 || editQuestId === 4 || editQuestTitle.includes('nonton film') || editQuestTitle.includes('baca buku');

            // Validate title for film/book quests only
            if (isEditReviewQuest && !$('#edit_submission_title').val().trim()) {
                $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Judul harus diisi!</div>').slideDown();
                return false;
            }

            // Validate file upload if present
            var fileInput = $('#edit_submission_image')[0];
            if (fileInput.files.length > 0) {
                var file = fileInput.files[0];
                var maxSize = 2 * 1024 * 1024; // 2MB in bytes

                if (file.size > maxSize) {
                    $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-file-earmark-x me-2"></i>Ukuran file terlalu besar! Maksimal 2MB.</div>').slideDown();
                    return false;
                }

                // Check file type
                var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-file-earmark-x me-2"></i>Tipe file tidak didukung! Gunakan JPEG, PNG, GIF, atau WEBP.</div>').slideDown();
                    return false;
                }
            }

            // Add HTML content to form data
            formData.append('hasil_html', hasilHtml);
            formData.set('hasil', hasilText);

            // Submit form
            $.ajax({
                url: '<?= base_url() ?>profile/update_review',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    $('.btn-update-review').addClass('disabled').html('<span class="spinner-border spinner-border-sm me-2"></span>Updating...').attr('disabled', true);
                    $('.form-message-edit-review').slideUp().html('');
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $('.form-message-edit-review').html('<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>' + (response.message || 'Quest berhasil diupdate!') + '</div>').slideDown();

                        setTimeout(function() {
                            $('#editReviewModal').modal('hide');
                            window.location.reload();
                        }, 2000);
                    } else {
                        // Show error message
                        $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + (response.message || 'Terjadi kesalahan saat mengupdate quest.') + '</div>').slideDown();
                        $('.btn-update-review').removeClass('disabled').html('<i class="bi bi-check-lg me-1"></i>Update Quest').attr('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    $('.btn-update-review').removeClass('disabled').html('<i class="bi bi-check-lg me-1"></i>Update Quest').attr('disabled', false);
                    $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Terjadi kesalahan sistem. Silakan coba lagi.</div>').slideDown();
                }
            });

            return false;
        });

        // Edit Modal cleanup when closed
        $('#editReviewModal').on('hidden.bs.modal', function() {
            // Clear form messages
            $('.form-message-edit-review').html('').hide();

            // Clean up TinyMCE editor
            setTimeout(() => {
                if (tinymce.get('edit-hasil-editor')) {
                    try {
                        tinymce.remove('#edit-hasil-editor');
                    } catch (e) {
                        console.log('TinyMCE cleanup error:', e);
                    }
                }
            }, 200);
        });

        // Helper functions for status
        function getStatusClass(status) {
            switch (status) {
                case 'pending':
                    return 'bg-warning';
                case 'approved':
                    return 'bg-success';
                case 'denied':
                    return 'bg-danger';
                default:
                    return 'bg-secondary';
            }
        }

        function getStatusText(status) {
            switch (status) {
                case 'pending':
                    return 'Pending';
                case 'approved':
                    return 'Approved';
                case 'denied':
                    return 'Denied';
                default:
                    return 'Unknown';
            }
        }


    });


    // Summernote editor initialized above in sideQuestModal shown event
</script>

<!-- TinyMCE verification and fallback -->
<script>
    // Ensure TinyMCE is loaded properly
    $(document).ready(function() {
        // Check if TinyMCE is loaded
        if (typeof tinymce === 'undefined') {

            // Load TinyMCE from CDN
            $.getScript('https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js')
                .done(function() {
                    // Trigger an event that can be listened for elsewhere
                    $(document).trigger('tinymce:loaded');
                })
                .fail(function(jqxhr, settings, exception) {

                    // Try loading from a different CDN as fallback
                    $.getScript('https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.4.2/tinymce.min.js')
                        .done(function() {
                            $(document).trigger('tinymce:loaded');
                        })
                        .fail(function() {
                            $(document).trigger('tinymce:failed');
                        });
                });
        } else {
            $(document).trigger('tinymce:loaded');
        }
    });
</script>

<!-- Profile Tab Management Script -->
<script>
$(document).ready(function() {
    if (window.__profileTransitionScriptInitialized) {
        return;
    }
    window.__profileTransitionScriptInitialized = true;

    function decodeHtmlEntities(input) {
        const txt = document.createElement('textarea');
        txt.innerHTML = input || '';
        return txt.value;
    }

    function sanitizeDescriptionHtml(input) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = input || '';

        wrapper.querySelectorAll('script, style, iframe, object, embed').forEach(function(el) {
            el.remove();
        });

        wrapper.querySelectorAll('*').forEach(function(el) {
            Array.from(el.attributes).forEach(function(attr) {
                const attrName = attr.name.toLowerCase();
                const attrValue = String(attr.value || '').trim().toLowerCase();
                if (attrName.indexOf('on') === 0) {
                    el.removeAttribute(attr.name);
                }
                if ((attrName === 'href' || attrName === 'src') && attrValue.indexOf('javascript:') === 0) {
                    el.removeAttribute(attr.name);
                }
            });
        });

        wrapper.querySelectorAll('a[href]').forEach(function(a) {
            a.setAttribute('target', '_blank');
            a.setAttribute('rel', 'noopener noreferrer');
        });

        return wrapper.innerHTML.trim();
    }

    // Handle tab switching to show/hide Activities & Reviews Card
    $('#profile-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href"); // activated tab

        if (target === '#milestone-content' || target === '#transition-content') {
            // Hide Activities & Reviews Card when milestone tab is active
            $('#activities-reviews-card').hide();
        } else {
            // Show Activities & Reviews Card when profile tab is active
            $('#activities-reviews-card').show();
        }
    });

    // Initially hide Activities & Reviews Card if milestone tab is active on page load
    if ($('#milestone-tab').hasClass('active') || $('#transition-tab').hasClass('active')) {
        $('#activities-reviews-card').hide();
    }

    const transitionDetailModalEl = document.getElementById('transitionTaskDetailModal');
    if (transitionDetailModalEl && transitionDetailModalEl.parentElement !== document.body) {
        document.body.appendChild(transitionDetailModalEl);
    }

    $(document).off('click.transitionDetail', '.transition-task-detail-btn').on('click.transitionDetail', '.transition-task-detail-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const title = $(this).data('task-title') || 'Detail Task';
        const descriptionTarget = $(this).data('task-description-target');
        const attachmentPath = $(this).data('task-attachment-path') || '';
        const attachmentName = $(this).data('task-attachment-name') || 'Attachment';
        const descriptionRaw = descriptionTarget ? $('#' + descriptionTarget).val() : '';
        const descriptionHtml = decodeHtmlEntities(descriptionRaw);
        const safeDescriptionHtml = sanitizeDescriptionHtml(descriptionHtml);

        $('#transition-task-detail-title').text(title);
        if (safeDescriptionHtml) {
            $('#transition-task-detail-description')
                .html(safeDescriptionHtml)
                .removeClass('text-muted')
                .css('white-space', 'normal');
        } else {
            $('#transition-task-detail-description')
                .text('Belum ada deskripsi.')
                .addClass('text-muted')
                .css('white-space', 'normal');
        }

        if (attachmentPath) {
            $('#transition-task-detail-attachment-link').attr('href', '<?= base_url() ?>' + attachmentPath).text(attachmentName);
            $('#transition-task-detail-attachment-wrapper').show();
        } else {
            $('#transition-task-detail-attachment-wrapper').hide();
        }

        if (window.bootstrap && bootstrap.Modal) {
            if (!transitionDetailModalEl) {
                return;
            }
            const modalInstance = bootstrap.Modal.getOrCreateInstance(transitionDetailModalEl);
            if (!transitionDetailModalEl.classList.contains('show')) {
                modalInstance.show();
            }
        }
    });

    $('.transition-task-status-select').each(function() {
        $(this).data('previous', $(this).val());
    });

    $(document).off('change.transitionStatus', '.transition-task-status-select').on('change.transitionStatus', '.transition-task-status-select', function() {
        const select = $(this);
        const previousStatus = select.data('previous') || (select.val() === 'done' ? 'todo' : 'done');
        const transitionType = select.data('type');
        const taskId = select.data('task-id');
        const taskSource = select.data('task-source') === 'custom' ? 'custom' : 'template';
        const taskStatus = select.val();
        const card = select.closest('.transition-todo-card');
        const statusBadge = card.find('.transition-task-status');

        select.prop('disabled', true);

        $.post('<?= base_url() ?>profile/update-transition-task', {
            transition_type: transitionType,
            task_template_id: taskId,
            task_source: taskSource,
            task_status: taskStatus
        }, function(response) {
            if (!response || !response.success) {
                alert(response && response.message ? response.message : 'Gagal memperbarui checklist.');
                select.val(previousStatus);
                return;
            }

            const barId = `#${transitionType}-progress-bar`;
            const textId = `#${transitionType}-progress-text`;
            $(barId).css('width', `${response.progress}%`);
            $(textId).text(`${response.progress}% (${response.checked}/${response.total})`);

            statusBadge.removeClass('status-todo status-progress status-done');
            if (taskStatus === 'done') {
                statusBadge.addClass('status-done').text('Done');
            } else if (taskStatus === 'in_progress') {
                statusBadge.addClass('status-progress').text('In progress');
            } else {
                statusBadge.addClass('status-todo').text('To do');
            }
            select.data('previous', taskStatus);
        }, 'json').fail(function() {
            alert('Terjadi kendala saat memperbarui checklist.');
            select.val(previousStatus);
        }).always(function() {
            select.prop('disabled', false);
        });
    });
});
</script>

<!-- Profile CSS -->
<link rel="stylesheet" href="<?= base_url() ?>application/views/profile/profile.css">
<link rel="stylesheet" href="<?= base_url() ?>assets/css/profile-rich-editor.css">

<!-- Additional CSS for TinyMCE editor -->
<style>
    /* Editor loading indicators */
    #editor-loading,
    #editor-init-loading {
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 1px dashed #d9d9d9;
        margin-bottom: 15px;
    }

    #editor-fallback-notice {
        border-left: 4px solid #faad14;
    }

    /* Fix form visibility within modal */
    #sideQuestModal .modal-body form {
        display: block !important;
        visibility: visible !important;
    }

    #sideQuestModal .modal-body .mb-3 {
        display: block !important;
        margin-bottom: 1rem !important;
    }

    /* Ensure form elements are visible */
    #sideQuestModal #conditional-fields,
    #sideQuestModal .form-label,
    #sideQuestModal .form-control,
    #sideQuestModal .form-text {
        display: block !important;
        visibility: visible !important;
        padding: 5px !important;
    }

    /* TinyMCE editor styling */
    #hasil-editor {
        visibility: visible !important;
        border-radius: 8px !important;
    }

    /* TinyMCE in modal specific styling */
    .tox-tinymce {
        border: 1px solid #91d5ff !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
    }

    .tox .tox-toolbar,
    .tox .tox-toolbar__overflow,
    .tox .tox-toolbar__primary {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #dee2e6 !important;
    }

    .tox .tox-edit-area__iframe {
        background-color: white !important;
    }

    .tox .tox-statusbar {
        border-top: 1px solid #dee2e6 !important;
    }

    /* Fix modal z-index issues for TinyMCE */
    #sideQuestModal {
        z-index: 1055 !important;
    }

    #sideQuestModal .modal-content {
        z-index: 1056 !important;
    }

    .tox-tinymce-aux {
        z-index: 1060 !important;
    }

    /* Improve focus state */
    .tox-edit-focus {
        border-color: #1890ff !important;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2) !important;
    }

    /* Mobile styling */
    @media (max-width: 767.98px) {
        .tox-toolbar__group {
            flex-wrap: wrap !important;
        }
    }
</style>
