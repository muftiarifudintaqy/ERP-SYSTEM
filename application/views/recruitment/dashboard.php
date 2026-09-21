<?php
// Siapkan data chart dari array controller (semua dari tabel job_applications).
$status_labels   = json_encode(array_column($by_status, 'label'));
$status_values   = json_encode(array_map('intval', array_column($by_status, 'total')));
$position_labels = json_encode(array_column($by_position, 'label'));
$position_values = json_encode(array_map('intval', array_column($by_position, 'total')));
$source_labels   = json_encode(array_column($by_source, 'label'));
$source_values   = json_encode(array_map('intval', array_column($by_source, 'total')));
$month_labels    = json_encode(array_column($by_month, 'label'));
$month_values    = json_encode(array_map('intval', array_column($by_month, 'total')));
$gender_labels   = json_encode(array_column($by_gender, 'label'));
$gender_values   = json_encode(array_map('intval', array_column($by_gender, 'total')));
$wfo_labels      = json_encode(array_column($by_wfo, 'label'));
$wfo_values      = json_encode(array_map('intval', array_column($by_wfo, 'total')));
?>
<style>
    .rdash-card {
        border: 1px solid #f0f0f0;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        padding: 18px 20px;
        height: 100%;
    }

    .rdash-kpi .label {
        font-size: 13px;
        color: rgba(0, 0, 0, 0.45);
        margin-bottom: 6px;
    }

    .rdash-kpi .value {
        font-size: 28px;
        font-weight: 700;
        color: rgba(0, 0, 0, 0.85);
        line-height: 1.1;
    }

    .rdash-kpi .icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .rdash-chart-title {
        font-size: 15px;
        font-weight: 600;
        color: rgba(0, 0, 0, 0.85);
        margin-bottom: 14px;
    }

    .rdash-chart-wrap {
        position: relative;
        height: 300px;
    }
</style>

<div class="container-fluid py-3">

    <!-- ===================== KPI CARDS ===================== -->
    <div class="row g-3 mb-1">
        <div class="col-md">
            <div class="rdash-card rdash-kpi d-flex align-items-center justify-content-between">
                <div>
                    <div class="label">Total Pelamar</div>
                    <div class="value"><?= number_format($kpi['total'], 0, ',', '.') ?></div>
                </div>
                <div class="icon" style="background:#e6f7ff;color:#1890ff;"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
        <div class="col-md">
            <div class="rdash-card rdash-kpi d-flex align-items-center justify-content-between">
                <div>
                    <div class="label">Dalam Proses</div>
                    <div class="value"><?= number_format($kpi['in_process'], 0, ',', '.') ?></div>
                </div>
                <div class="icon" style="background:#f9f0ff;color:#722ed1;"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-md">
            <div class="rdash-card rdash-kpi d-flex align-items-center justify-content-between">
                <div>
                    <div class="label">Selected</div>
                    <div class="value"><?= number_format($kpi['selected'], 0, ',', '.') ?></div>
                </div>
                <div class="icon" style="background:#f6ffed;color:#52c41a;"><i class="bi bi-check-circle-fill"></i></div>
            </div>
        </div>
        <div class="col-md">
            <div class="rdash-card rdash-kpi d-flex align-items-center justify-content-between">
                <div>
                    <div class="label">Rejected</div>
                    <div class="value"><?= number_format($kpi['rejected'], 0, ',', '.') ?></div>
                </div>
                <div class="icon" style="background:#fff2f0;color:#ff4d4f;"><i class="bi bi-x-circle-fill"></i></div>
            </div>
        </div>
        <div class="col-md">
            <div class="rdash-card rdash-kpi d-flex align-items-center justify-content-between">
                <div>
                    <div class="label">Lamaran Bulan Ini</div>
                    <div class="value"><?= number_format($kpi['this_month'], 0, ',', '.') ?></div>
                </div>
                <div class="icon" style="background:#e6fffb;color:#13c2c2;"><i class="bi bi-calendar-month"></i></div>
            </div>
        </div>
    </div>

    <!-- ===================== CHARTS ===================== -->
    <div class="row g-3 mt-1">
        <div class="col-lg-7">
            <div class="rdash-card">
                <div class="rdash-chart-title">Tren Lamaran Masuk (12 Bulan Terakhir)</div>
                <div class="rdash-chart-wrap"><canvas id="chartMonth"></canvas></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="rdash-card">
                <div class="rdash-chart-title">Distribusi Status Recruitment</div>
                <div class="rdash-chart-wrap"><canvas id="chartStatus"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="rdash-card">
                <div class="rdash-chart-title">Posisi Paling Banyak Dilamar (Top 10)</div>
                <div class="rdash-chart-wrap"><canvas id="chartPosition"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="rdash-card">
                <div class="rdash-chart-title">Sumber Info Loker (Top 10)</div>
                <div class="rdash-chart-wrap"><canvas id="chartSource"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1 mb-3">
        <div class="col-lg-6">
            <div class="rdash-card">
                <div class="rdash-chart-title">Jenis Kelamin Pelamar</div>
                <div class="rdash-chart-wrap"><canvas id="chartGender"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="rdash-card">
                <div class="rdash-chart-title">Preferensi Kerja (WFO vs WFH)</div>
                <div class="rdash-chart-wrap"><canvas id="chartWfo"></canvas></div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const palette = ['#1890ff', '#52c41a', '#faad14', '#ff4d4f', '#722ed1', '#13c2c2', '#eb2f96', '#fa8c16', '#2f54eb', '#a0d911'];
        const noLabels = { datalabels: { display: false } };

        // Tren lamaran per bulan (line)
        new Chart(document.getElementById('chartMonth'), {
            type: 'line',
            data: {
                labels: <?= $month_labels ?>,
                datasets: [{
                    label: 'Jumlah Lamaran',
                    data: <?= $month_values ?>,
                    borderColor: '#1890ff',
                    backgroundColor: 'rgba(24,144,255,0.12)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointBackgroundColor: '#1890ff'
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, ...noLabels },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        // Status recruitment (donut)
        new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: {
                labels: <?= $status_labels ?>,
                datasets: [{ data: <?= $status_values ?>, backgroundColor: palette }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right' }, datalabels: { display: false } }
            }
        });

        // Posisi dilamar (horizontal bar)
        new Chart(document.getElementById('chartPosition'), {
            type: 'bar',
            data: {
                labels: <?= $position_labels ?>,
                datasets: [{ label: 'Pelamar', data: <?= $position_values ?>, backgroundColor: '#1890ff' }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, ...noLabels },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        // Sumber info loker (bar)
        new Chart(document.getElementById('chartSource'), {
            type: 'bar',
            data: {
                labels: <?= $source_labels ?>,
                datasets: [{ label: 'Pelamar', data: <?= $source_values ?>, backgroundColor: '#52c41a' }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, ...noLabels },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        // Jenis kelamin (pie)
        new Chart(document.getElementById('chartGender'), {
            type: 'pie',
            data: {
                labels: <?= $gender_labels ?>,
                datasets: [{ data: <?= $gender_values ?>, backgroundColor: palette }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right' }, datalabels: { display: false } }
            }
        });

        // WFO vs WFH (donut)
        new Chart(document.getElementById('chartWfo'), {
            type: 'doughnut',
            data: {
                labels: <?= $wfo_labels ?>,
                datasets: [{ data: <?= $wfo_values ?>, backgroundColor: ['#1890ff', '#faad14'] }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right' }, datalabels: { display: false } }
            }
        });
    })();
</script>
