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

    .bulk-toolbar {
        display: none;
        gap: 8px;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        padding: 12px;
        background: rgba(255, 255, 255, 0.98);
        border: 1px solid #d9d9d9;
        border-radius: 10px;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
        position: fixed;
        left: 50%;
        bottom: 24px;
        transform: translateX(-50%);
        z-index: 1055;
        min-width: 720px;
        max-width: calc(100vw - 32px);
    }

    .bulk-counter {
        font-size: 13px;
        color: rgba(0, 0, 0, 0.65);
        min-width: 140px;
        text-align: center;
    }

    .bulk-toolbar.is-visible {
        display: flex;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }

        .bulk-toolbar {
            min-width: auto;
            width: calc(100vw - 24px);
            left: 12px;
            right: 12px;
            bottom: 12px;
            transform: none;
        }
    }
</style>

<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">User Management</h5>
                <a href="<?= base_url() ?>/user/create_page" class="btn btn-primary">
                    <i class="bi bi-plus me-1"></i> Tambah Data
                </a>
            </div>
        </div>
        <div class="card-body">
            <form action="" class="search-form" id="user-search-form">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="input-group" style="box-shadow: 0 2px 0 rgba(0,0,0,0.02);">
                            <div class="dropdown" style="margin-right: 10px;">
                                <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false"
                                    style="border: 1px solid #d9d9d9; border-radius: 2px; color: rgba(0,0,0,0.65); background-color: #fff; height: 32px; padding: 4px 11px;">
                                    <span style="margin-right: 8px;"><?= $keyword_category ?></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="position: absolute; background-color: white; min-width: 160px; z-index: 9999; border-radius: 2px; box-shadow: 0 3px 6px -4px rgba(0,0,0,0.12), 0 6px 16px 0 rgba(0,0,0,0.08), 0 9px 28px 8px rgba(0,0,0,0.05); padding: 4px 0; margin-top: 2px; left: 0;">
                                    <?php
                                    $arr = array();
                                    $arr[] = 'Nama Lengkap';
                                    $arr[] = 'Email';
                                    $arr[] = 'Role';
                                    $arr[] = 'Keterangan';
                                    foreach ($arr as $k => $val) {
                                        $active = ($keyword_category == $val) ? 'background-color: #e6f7ff; color: #1890ff;' : '';
                                    ?>
                                        <li><a class="dropdown-item" href="<?= $url_2 ?>&keyword_category=<?= $val ?>"
                                                style="padding: 5px 12px; font-size: 14px; line-height: 22px; <?= $active ?>">
                                                <?= $val ?>
                                            </a></li>
                                    <?php }  ?>
                                </ul>
                            </div>
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?>">
                            <input type="text" name="keyword" class="form-control" placeholder="Search..." value="<?= isset($_GET['keyword']) ? $_GET['keyword'] : '' ?>"
                                style="border: 1px solid #d9d9d9; box-shadow: none; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
                            <button class="btn btn-primary" type="submit"
                                style="border-radius: 2px; height: 32px; padding: 0 15px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (!empty($notif)): ?>
                    <div class="alert alert-info" style="display: flex; align-items: center;">
                        <i class="bi bi-info-circle me-2"></i>
                        <span><?= strip_tags($notif) ?></span>
                    </div>
                <?php endif; ?>
            </form>

            <div class="bulk-toolbar" id="bulk-toolbar">
                <span class="bulk-counter">0 user dipilih</span>
                <select class="form-control" id="bulk-role-id" style="max-width: 260px; margin-top: 9px !important;">
                    <option value="">-- Pilih role untuk bulk update --</option>
                    <?php foreach ($bulk_roles as $role_item): ?>
                        <option value="<?= $role_item['id'] ?>"><?= $role_item['display_name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-primary" id="btn-bulk-update-role">
                    <i class="bi bi-person-gear me-1"></i> Ganti Role
                </button>
                <button type="button" class="btn btn-outline-danger" id="btn-bulk-delete">
                    <i class="bi bi-trash me-1"></i> Hapus Akun
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 40px;">
                                <input type="checkbox" class="form-check-input" id="check-all-users">
                            </th>
                            <th class="text-start">#</th>
                            <th class="text-start">Nama Lengkap</th>
                            <th class="text-start">Username</th>
                            <th class="text-start">Email</th>
                            <th class="text-start">Role</th>
                            <th class="text-start">Ket</th>
                            <th class="text-start">Status</th>
                            <th class="text-start">Jenis Kontrak</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                <div class="d-flex justify-content-start">
                    <?= $pagination ?>
                </div>
                <div>
                    <select class="form-control" id="limit-selector" name="limit" form="user-search-form" style="min-width: 180px;">
                        <?php foreach ([10, 25, 50, 100] as $limit_option): ?>
                            <option value="<?= $limit_option ?>" <?= (int) $limit === $limit_option ? 'selected' : '' ?>>
                                <?= $limit_option ?> / halaman
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Removed floating action button and hidden input for selected IDs -->


<script>
    /**
     * Delete user function
     */
    function remove(id) {
        Swal.fire({
            title: 'Hapus Data',
            text: 'Apakah Anda yakin ingin menghapus data ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4d4f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= base_url() ?>/user/delete",
                    data: {
                        id: id
                    },
                    success: function(response) {
                        if (response.indexOf("success") != -1) {
                            Swal.fire(
                                'Terhapus!',
                                'Data berhasil dihapus.',
                                'success'
                            ).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                'Gagal menghapus data.',
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'Terjadi kesalahan saat menghapus data.',
                            'error'
                        );
                    }
                });
            }
        });
    }
</script>

<script>
    /**
     * Loads user data via AJAX
     */
    function loadUserData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/user/item<?= $param ?>",
            beforeSend: function() {
                $('#tbody').html('<tr><td colspan="10" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
            },
            success: function(data) {
                $('#tbody').html(data);
                $('#check-all-users').prop('checked', false);
                updateBulkCounter();
                if (typeof select3 === 'function') {
                    select3();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading data:', error);
                $('#tbody').html('<tr><td colspan="10" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
            }
        });
    }

    function getSelectedUserIds() {
        return $('.user-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
    }

    function updateBulkCounter() {
        var total = getSelectedUserIds().length;
        $('.bulk-counter').text(total + ' user dipilih');
        $('#bulk-toolbar').toggleClass('is-visible', total > 0);
    }

    function extractResponseMessage(response, fallbackMessage) {
        var htmlText = $('<div>').html(response).text().trim();
        if (htmlText && htmlText.indexOf('$.toast') === -1 && htmlText.indexOf('$( document ).ready') === -1) {
            return htmlText;
        }

        var toastMatch = response.match(/text:\s*"([^"]+)"/);
        if (toastMatch && toastMatch[1]) {
            return toastMatch[1];
        }

        return fallbackMessage;
    }

    function bulkDeleteUsers() {
        var ids = getSelectedUserIds();
        if (ids.length === 0) {
            Swal.fire('Pilih Data', 'Pilih minimal 1 user terlebih dahulu.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Hapus User Terpilih',
            text: 'User yang dipilih akan dihapus secara permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4d4f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                type: "POST",
                url: "<?= base_url() ?>/user/bulk-delete",
                data: {
                    ids: ids
                },
                success: function(response) {
                    if (response.indexOf("success") != -1) {
                        Swal.fire('Berhasil', extractResponseMessage(response, 'Bulk hapus berhasil.'), 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', extractResponseMessage(response, 'Bulk hapus gagal.'), 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Terjadi kesalahan saat bulk hapus.', 'error');
                }
            });
        });
    }

    function bulkUpdateRole() {
        var ids = getSelectedUserIds();
        var roleId = $('#bulk-role-id').val();
        var roleText = $('#bulk-role-id option:selected').text();

        if (ids.length === 0) {
            Swal.fire('Pilih Data', 'Pilih minimal 1 user terlebih dahulu.', 'warning');
            return;
        }

        if (!roleId) {
            Swal.fire('Pilih Role', 'Pilih role tujuan terlebih dahulu.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Ganti Role Massal',
            text: 'Semua user terpilih akan diubah ke role ' + roleText + '.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1890ff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Ubah',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                type: "POST",
                url: "<?= base_url() ?>/user/bulk-update-role",
                data: {
                    ids: ids,
                    role_id: roleId
                },
                success: function(response) {
                    if (response.indexOf("success") != -1) {
                        Swal.fire('Berhasil', extractResponseMessage(response, 'Bulk update role berhasil.'), 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', extractResponseMessage(response, 'Bulk update role gagal.'), 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Terjadi kesalahan saat bulk update role.', 'error');
                }
            });
        });
    }

    // Initialize the page
    $(document).ready(function() {
        loadUserData();

        $('#limit-selector').on('change', function() {
            $('#user-search-form').trigger('submit');
        });

        $(document).on('change', '#check-all-users', function() {
            $('.user-checkbox').prop('checked', $(this).is(':checked'));
            updateBulkCounter();
        });

        $(document).on('change', '.user-checkbox', function() {
            var total = $('.user-checkbox').length;
            var checked = $('.user-checkbox:checked').length;
            $('#check-all-users').prop('checked', total > 0 && total === checked);
            updateBulkCounter();
        });

        $('#btn-bulk-delete').on('click', bulkDeleteUsers);
        $('#btn-bulk-update-role').on('click', bulkUpdateRole);
    });
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
