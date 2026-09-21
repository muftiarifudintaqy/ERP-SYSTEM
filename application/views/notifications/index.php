<?php
$notification_categories = $notification_categories ?? [
    'finance' => ['label' => 'Finance', 'description' => '', 'icon' => 'bi-wallet2'],
    'team' => ['label' => 'Team', 'description' => '', 'icon' => 'bi-people'],
];
$category_counts = $category_counts ?? [];
$active_category = $active_category ?? 'finance';
$keyword = $keyword ?? ($_GET['keyword'] ?? '');
$is_read_filter = $is_read_filter ?? ($_GET['is_read_filter'] ?? '');
$active_category_meta = $notification_categories[$active_category] ?? reset($notification_categories);
$active_category_total = $category_counts[$active_category]['total'] ?? 0;

$format_count = function ($count) {
    $count = intval($count);
    return $count > 99 ? '99+' : (string) $count;
};

$category_url = function ($category) {
    $params = $_GET;
    $params['category'] = $category;
    unset($params['page']);
    $query = http_build_query($params);
    return base_url('notifications') . ($query ? '?' . $query : '');
};

$reset_filter_url = function () use ($active_category) {
    return base_url('notifications?category=' . urlencode($active_category));
};
?>

<style>
    .notifications-page {
        max-width: 1120px;
        margin: 0 auto;
        color: rgba(0, 0, 0, 0.85);
    }

    .notifications-shell {
        background: #fff;
        border: 1px solid #f0f0f0;
        border-radius: 2px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
    }

    .notifications-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border-bottom: 1px solid #f0f0f0;
        min-height: 56px;
    }

    .notifications-title {
        margin: 0;
        font-size: 16px;
        line-height: 1.4;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.85);
    }

    .notifications-subtitle {
        margin: 2px 0 0;
        font-size: 13px;
        color: rgba(0, 0, 0, 0.45);
    }

    .notifications-page-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }

    .notifications-page-actions .btn,
    .notification-filter-row .btn,
    .notification-card-actions .btn {
        border-radius: 2px;
        height: 32px;
        padding: 4px 15px;
        font-size: 14px;
        line-height: 1.5;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .notifications-category-tabs {
        display: flex;
        align-items: center;
        gap: 24px;
        padding: 0 16px;
        border-bottom: 1px solid #f0f0f0;
        overflow-x: auto;
    }

    .notifications-category-tab {
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-bottom: 2px solid transparent;
        color: rgba(0, 0, 0, 0.65);
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
        transition: color 0.3s;
    }

    .notifications-category-tab:hover {
        color: #40a9ff;
    }

    .notifications-category-tab.active {
        color: #1890ff;
        border-bottom-color: #1890ff;
    }

    .notifications-tab-count {
        min-width: 22px;
        height: 22px;
        padding: 0 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 2px;
        background: #f0f0f0;
        color: rgba(0, 0, 0, 0.65);
        font-size: 12px;
        font-weight: normal;
        line-height: 22px;
    }

    .notifications-tab-count.has-unread {
        background: #ff4d4f;
        color: #fff;
    }

    .notification-filter-row {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) 180px auto;
        gap: 8px;
        padding: 16px;
        border-bottom: 1px solid #f0f0f0;
        background: #fff;
    }

    .notification-filter-row .form-control,
    .notification-filter-row .form-select {
        height: 32px;
        padding: 4px 11px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .notification-filter-row .form-control:hover,
    .notification-filter-row .form-select:hover {
        border-color: #40a9ff;
    }

    .notification-filter-row .form-control:focus,
    .notification-filter-row .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .notifications-list-page {
        background: #fff;
    }

    .notification-card-page {
        display: flex;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        background: #fff;
        transition: background 0.3s ease;
    }

    .notification-card-page:hover {
        background: #fafafa;
    }

    .notification-card-page.unread {
        background: #e6f7ff;
        box-shadow: inset 2px 0 0 #1890ff;
    }

    .notification-card-icon {
        width: 32px;
        height: 32px;
        border-radius: 2px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 32px;
        font-size: 14px;
        background: #f0f0f0;
        color: rgba(0, 0, 0, 0.65);
    }

    .notification-card-icon.type-info {
        background: #e6f7ff;
        color: #1890ff;
    }

    .notification-card-icon.type-success {
        background: #f6ffed;
        color: #52c41a;
    }

    .notification-card-icon.type-warning {
        background: #fffbe6;
        color: #faad14;
    }

    .notification-card-icon.type-danger {
        background: #fff1f0;
        color: #ff4d4f;
    }

    .notification-card-body {
        flex: 1;
        min-width: 0;
    }

    .notification-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 6px;
    }

    .notification-subcategory {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 22px;
        padding: 0 8px;
        border-radius: 2px;
        background: #f0f0f0;
        color: rgba(0, 0, 0, 0.65);
        font-size: 12px;
        font-weight: normal;
        line-height: 22px;
        white-space: nowrap;
    }

    .notification-time-page {
        color: rgba(0, 0, 0, 0.45);
        font-size: 12px;
        white-space: nowrap;
    }

    .notification-title-page {
        color: rgba(0, 0, 0, 0.85);
        font-size: 14px;
        font-weight: 500;
        line-height: 1.4;
        margin-bottom: 2px;
    }

    .notification-message-page {
        color: rgba(0, 0, 0, 0.65);
        font-size: 13px;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .notification-card-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: 8px;
        flex-wrap: wrap;
    }

    .notification-muted-action {
        color: #1890ff;
        text-decoration: none;
        font-size: 13px;
        font-weight: normal;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: color 0.3s;
    }

    .notification-muted-action:hover {
        color: #40a9ff;
    }

    .notification-inline-actions {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .notification-inline-actions .btn {
        height: 28px;
        padding: 0 10px;
        font-size: 13px;
        border-radius: 2px;
    }

    .notification-empty-state {
        padding: 48px 16px;
        text-align: center;
        color: rgba(0, 0, 0, 0.45);
    }

    .notification-empty-state i {
        display: block;
        font-size: 40px;
        margin-bottom: 12px;
    }

    .notification-empty-state h4 {
        margin: 0 0 6px;
        color: rgba(0, 0, 0, 0.65);
        font-size: 15px;
        font-weight: 500;
    }

    .notification-empty-state p {
        margin: 0;
        font-size: 13px;
    }

    .notifications-flash {
        padding: 0 16px;
    }

    .notifications-flash .alert {
        margin: 12px 0 0;
        padding: 8px 15px;
        border-radius: 2px;
        font-size: 13px;
    }

    .notifications-pagination {
        display: flex;
        justify-content: flex-end;
        padding: 12px 16px;
    }

    @media (max-width: 768px) {
        .notifications-page-header,
        .notification-card-top,
        .notification-card-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .notification-filter-row {
            grid-template-columns: 1fr;
        }

        .notifications-page-actions,
        .notification-inline-actions {
            justify-content: flex-start;
        }

        .notifications-title {
            font-size: 15px;
        }

        .notification-card-page {
            padding: 12px;
        }

        .notifications-category-tabs,
        .notifications-page-header,
        .notification-filter-row {
            padding-left: 12px;
            padding-right: 12px;
        }
    }
</style>

<div class="notifications-page py-3">
    <div class="notifications-shell">
        <div class="notifications-page-header">
            <div>
                <h1 class="notifications-title">Kotak Masuk</h1>
                <p class="notifications-subtitle">Notifikasi dipisahkan berdasarkan Finance dan Team.</p>
            </div>
            <div class="notifications-page-actions">
                <button class="btn btn-outline-primary" type="button" onclick="markAllAsRead()">
                    <i class="bi bi-check2-all"></i> Tandai Semua Dibaca
                </button>
                <button class="btn btn-outline-danger" type="button" onclick="clearReadNotifications()">
                    <i class="bi bi-trash"></i> Hapus yang Sudah Dibaca
                </button>
            </div>
        </div>

        <div class="notifications-category-tabs" role="tablist" aria-label="Kategori notifikasi">
            <?php foreach ($notification_categories as $category_key => $category): ?>
                <?php
                $count = intval($category_counts[$category_key]['total'] ?? 0);
                $unread = intval($category_counts[$category_key]['unread'] ?? 0);
                ?>
                <a
                    href="<?= $category_url($category_key) ?>"
                    class="notifications-category-tab <?= $active_category === $category_key ? 'active' : '' ?>"
                    role="tab"
                    aria-selected="<?= $active_category === $category_key ? 'true' : 'false' ?>"
                >
                    <i class="bi <?= htmlspecialchars($category['icon']) ?>"></i>
                    <span><?= htmlspecialchars($category['label']) ?></span>
                    <?php if ($count > 0): ?>
                        <span class="notifications-tab-count <?= $unread > 0 ? 'has-unread' : '' ?>">
                            <?= $format_count($unread > 0 ? $unread : $count) ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form class="notification-filter-row" method="GET" action="<?= base_url('notifications') ?>">
            <input type="hidden" name="category" value="<?= htmlspecialchars($active_category) ?>">
            <input
                type="text"
                class="form-control"
                name="keyword"
                value="<?= htmlspecialchars($keyword) ?>"
                placeholder="Cari judul atau isi notifikasi"
                autocomplete="off"
            >
            <select class="form-select" name="is_read_filter" aria-label="Filter status baca">
                <option value="" <?= $is_read_filter === '' ? 'selected' : '' ?>>Semua status</option>
                <option value="0" <?= (string) $is_read_filter === '0' ? 'selected' : '' ?>>Belum dibaca</option>
                <option value="1" <?= (string) $is_read_filter === '1' ? 'selected' : '' ?>>Sudah dibaca</option>
            </select>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Cari
                </button>
                <a class="btn btn-light" href="<?= $reset_filter_url() ?>">
                    <i class="bi bi-x-lg"></i> Reset
                </a>
            </div>
        </form>

        <?php if ($this->session->flashdata('success') || $this->session->flashdata('error')): ?>
            <div class="notifications-flash">
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?= $this->session->flashdata('success') ?>
                    </div>
                <?php endif; ?>

                <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i> <?= $this->session->flashdata('error') ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($notifications)): ?>
            <div class="notification-empty-state">
                <i class="bi bi-bell-slash"></i>
                <h4>Tidak ada notifikasi</h4>
                <p>Notifikasi <?= htmlspecialchars($active_category_meta['label'] ?? '') ?> akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <div class="notifications-list-page">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $icons = [
                        'info' => 'bi-info-circle',
                        'success' => 'bi-check-circle',
                        'warning' => 'bi-exclamation-triangle',
                        'danger' => 'bi-x-circle'
                    ];
                    $icon = $icons[$notification['type']] ?? 'bi-info-circle';
                    $type_class = in_array($notification['type'], ['info', 'success', 'warning', 'danger'], true) ? $notification['type'] : 'info';
                    ?>
                    <article
                        class="notification-card-page <?= $notification['is_read'] == '0' ? 'unread' : '' ?>"
                        data-notification-id="<?= htmlspecialchars($notification['id']) ?>"
                    >
                        <span class="notification-card-icon type-<?= htmlspecialchars($type_class) ?>">
                            <i class="bi <?= $icon ?>"></i>
                        </span>
                        <div class="notification-card-body">
                            <div class="notification-card-top">
                                <span class="notification-subcategory">
                                    <i class="bi <?= htmlspecialchars($notification['category_icon'] ?? 'bi-bell') ?>"></i>
                                    <?= htmlspecialchars($notification['subcategory'] ?? $notification['category_label'] ?? 'Notifikasi') ?>
                                </span>
                                <span class="notification-time-page"><?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?></span>
                            </div>
                            <div class="notification-title-page"><?= htmlspecialchars($notification['title']) ?></div>
                            <div class="notification-message-page"><?= htmlspecialchars($notification['message']) ?></div>
                            <div class="notification-card-actions">
                                <a href="<?= htmlspecialchars($notification['action_url'] ?? base_url('notifications')) ?>" class="notification-muted-action">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                    <?= htmlspecialchars($notification['action_label'] ?? 'Buka Notifikasi') ?>
                                </a>
                                <span class="notification-inline-actions">
                                    <?php if ($notification['is_read'] == '0'): ?>
                                        <button
                                            class="btn btn-sm btn-outline-success js-mark-read-button"
                                            type="button"
                                            onclick="markAsRead(<?= intval($notification['id']) ?>)"
                                            title="Tandai sudah dibaca"
                                        >
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        type="button"
                                        onclick="deleteNotification(<?= intval($notification['id']) ?>)"
                                        title="Hapus"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($pagination): ?>
                <div class="notifications-pagination">
                    <?= $pagination ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    Swal.fire({
        title: 'Yakin?',
        text: 'Tandai notifikasi ini sebagai sudah dibaca?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('<?= base_url("notifications/mark_read") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (item) {
                        item.classList.remove('unread');
                        const markButton = item.querySelector('.js-mark-read-button');
                        if (markButton) {
                            markButton.remove();
                        }
                    }
                    Swal.fire('Berhasil!', 'Notifikasi ditandai sebagai dibaca.', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', 'Gagal menandai notifikasi sebagai dibaca.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'Terjadi kesalahan.', 'error');
            });
        }
    });
}

function markAllAsRead() {
    Swal.fire({
        title: 'Yakin?',
        text: 'Tandai semua notifikasi sebagai sudah dibaca?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('<?= base_url("notifications/mark_all_read") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Berhasil!', 'Semua notifikasi sudah dibaca.', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', 'Gagal menandai semua notifikasi.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'Terjadi kesalahan.', 'error');
            });
        }
    });
}

function clearReadNotifications() {
    Swal.fire({
        title: 'Yakin?',
        text: 'Hapus semua notifikasi yang sudah dibaca?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('<?= base_url("notifications/clear_read") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Berhasil!', 'Notifikasi terbaca sudah dihapus.', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', 'Gagal menghapus notifikasi.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'Terjadi kesalahan.', 'error');
            });
        }
    });
}

function deleteNotification(notificationId) {
    Swal.fire({
        title: 'Yakin?',
        text: 'Hapus notifikasi ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= base_url("notifications/delete/") ?>' + notificationId;
        }
    });
}
</script>
