<?php
$role = (string) ($user['role'] ?? '');
$is_admin_payroll = in_array($role, ['1', '2'], true);
$current = $current ?? '';
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
        min-height: 56px;
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

    /* Tab Styling - Ant Design-like */
    .nav-tabs {
        border-bottom: 1px solid #f0f0f0;
        margin-bottom: 0;
    }

    .nav-tabs .nav-link {
        border-radius: 2px 2px 0 0;
        border: 1px solid transparent;
        padding: 8px 16px;
        color: rgba(0, 0, 0, 0.65);
        background-color: transparent;
        margin-right: 2px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .nav-tabs .nav-link:hover {
        border-color: transparent;
        color: #1890ff;
        background-color: #f0f0f0;
    }

    .nav-tabs .nav-link.active {
        color: #1890ff;
        background-color: #fff;
        border-color: #f0f0f0 #f0f0f0 #fff;
        font-weight: 500;
        border-bottom: 2px solid #1890ff;
    }

    .tab-content {
        background-color: #fff;
        padding: 16px 0 0 0;
    }

    .modal-content {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }

    /* Additional buttons */
    .btn-secondary {
        background-color: #f5f5f5;
        border-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.65);
    }

    .btn-secondary:hover {
        background-color: #e6f7ff;
        border-color: #40a9ff;
        color: #40a9ff;
    }

    .dropdown-menu {
        z-index: 9999 !important;
    }

    .dropdown-menu.show {
        display: block !important;
    }
</style>

<div class="card mb-3">
    <div class="card-header pb-0">
        <ul class="nav nav-tabs card-header-tabs">
            <?php if ($is_admin_payroll): ?>
                <li class="nav-item"><a class="nav-link <?= $current === 'dashboard' ? 'active' : '' ?>" href="<?= base_url('payroll/dashboard') ?>"><i class="bi bi-grid me-1"></i>Dasbor</a></li>
                <li class="nav-item"><a class="nav-link <?= $current === 'payroll_period' ? 'active' : '' ?>" href="<?= base_url('payroll/payroll_period') ?>"><i class="bi bi-calendar3 me-1"></i>Periode Payroll</a></li>
                <li class="nav-item"><a class="nav-link <?= $current === 'run_payroll' ? 'active' : '' ?>" href="<?= base_url('payroll/run_payroll') ?>"><i class="bi bi-play-circle me-1"></i>Proses Payroll</a></li>
                <li class="nav-item"><a class="nav-link <?= $current === 'salary_structure' ? 'active' : '' ?>" href="<?= base_url('payroll/salary_structure') ?>"><i class="bi bi-diagram-3 me-1"></i>Struktur Gaji</a></li>
                <li class="nav-item"><a class="nav-link <?= $current === 'master_components' ? 'active' : '' ?>" href="<?= base_url('payroll/master_components') ?>"><i class="bi bi-sliders me-1"></i>Master Komponen</a></li>
                <li class="nav-item"><a class="nav-link <?= $current === 'my_payroll' ? 'active' : '' ?>" href="<?= base_url('payroll/my_payroll') ?>"><i class="bi bi-file-earmark-text me-1"></i>Payroll Saya</a></li>
            <?php else: ?>
                <li class="nav-item"><a class="nav-link active" href="<?= base_url('payroll/my_payroll') ?>"><i class="bi bi-file-earmark-text me-1"></i>Payroll Saya</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
<?php endif; ?>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>
