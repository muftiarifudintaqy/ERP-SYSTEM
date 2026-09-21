<?php
$selected_product_filter = isset($product_filter) && is_array($product_filter) ? $product_filter : array();
$analytics = isset($analytics) && is_array($analytics) ? $analytics : array();
$provinceChart = isset($analytics['province']) ? $analytics['province'] : array('labels' => array(), 'values' => array());
$cityChart = isset($analytics['city']) ? $analytics['city'] : array('labels' => array(), 'values' => array());
$statusChart = isset($analytics['status']) ? $analytics['status'] : array('labels' => array(), 'values' => array());
$keluhanChart = isset($analytics['keluhan']) ? $analytics['keluhan'] : array('labels' => array(), 'values' => array());
$trendCustomerChart = isset($analytics['trend_customer']) ? $analytics['trend_customer'] : array('labels' => array(), 'new_customer' => array(), 'repeat_buyer' => array());
$totalRows = isset($analytics['total_rows']) ? (int)$analytics['total_rows'] : 0;
$isFallback = !empty($analytics['no_date_filter']);
?>
<style>
    .crm-analytics-page .card {
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }

    .crm-analytics-page .form-control,
    .crm-analytics-page .form-select {
        min-height: 42px;
        border-radius: 10px;
    }

    .crm-analytics-page .select2-container {
        width: 100% !important;
    }

    .crm-analytics-page .select2-container .select2-selection--multiple {
        min-height: 42px;
        border-radius: 10px;
        border: 1px solid #ced4da;
        padding: 4px 8px;
    }

    .crm-analytics-page .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .crm-analytics-page .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e3a8a;
        border-radius: 999px;
        padding: 2px 8px;
        margin-top: 4px;
    }

    .crm-analytics-filter-label {
        display: block;
        min-height: 24px;
        margin-bottom: 8px;
        font-weight: 600;
        color: #212529;
    }

    .crm-analytics-filter-label.is-placeholder {
        visibility: hidden;
    }

    .crm-analytics-filter-action .btn {
        min-height: 42px;
    }

    .crm-analytics-kpi {
        padding: 18px 20px;
        background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
    }

    .crm-analytics-kpi-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        margin-bottom: 6px;
    }

    .crm-analytics-kpi-value {
        font-size: 28px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.1;
    }

    .crm-chart-wrap {
        position: relative;
        min-height: 320px;
    }

    .crm-chart-wrap.is-tall {
        min-height: 360px;
    }

    .crm-chart-empty {
        min-height: 320px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #64748b;
        font-size: 14px;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
    }
</style>

<div class="crm-analytics-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-700">Customer Analytics</h4>
            <p class="text-muted mb-0">Ringkasan persebaran customer, status, dan label keluhan berdasarkan filter CRM.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url() ?>crm?<?= http_build_query($_GET) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke CRM
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="<?= base_url() ?>crm/analytics">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label crm-analytics-filter-label">Rentang Tanggal</label>
                        <input type="text" class="form-control" id="crmAnalyticsTanggal" placeholder="Pilih rentang tanggal...">
                        <input type="hidden" name="start_date" id="crmAnalyticsStartDate" value="<?= htmlspecialchars($start_date ?? date('Y-m-01'), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="until_date" id="crmAnalyticsUntilDate" value="<?= htmlspecialchars($until_date ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-lg-4 col-md-12">
                        <label class="form-label crm-analytics-filter-label">Produk</label>
                        <select class="form-select" id="crmAnalyticsProductFilter" name="product_filter[]" multiple>
                            <?php foreach (($products ?? array()) as $productRow) {
                                $productName = trim((string)($productRow['name'] ?? ''));
                                if ($productName === '') {
                                    continue;
                                }
                                $isSelected = in_array($productName, $selected_product_filter, true) ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?>" <?= $isSelected ?>><?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-12">
                        <div class="crm-analytics-filter-action">
                            <label class="form-label crm-analytics-filter-label is-placeholder">Aksi</label>
                            <button type="submit" class="btn btn-primary w-100" style="margin-bottom: 12px;">
                                <i class="bi bi-funnel me-1"></i> Terapkan Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4 col-md-6">
            <div class="card crm-analytics-kpi h-100">
                <div class="crm-analytics-kpi-label">Customer Terfilter</div>
                <div class="crm-analytics-kpi-value"><?= number_format($totalRows, 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-lg-8 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center">
                    <div>
                        <div class="fw-600 mb-1">Catatan filter</div>
                        <div class="text-muted small">
                            <?= $isFallback ? 'Data pada rentang tanggal utama kosong, sehingga analytics memakai fallback tanpa pembatas tanggal utama.' : 'Analytics menggunakan filter tanggal utama dari first order dan filter produk yang dipilih.' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-12">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-600">Top Provinsi Customer</div>
                        <small class="text-muted">Ditampilkan sebagai peta Indonesia per provinsi.</small>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($provinceChart['labels'])) { ?>
                        <div class="crm-chart-wrap">
                            <canvas id="crmProvinceChart"></canvas>
                        </div>
                    <?php } else { ?>
                        <div class="crm-chart-empty">Belum ada data provinsi untuk filter ini.</div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="fw-600">Top Kota Customer</div>
                </div>
                <div class="card-body">
                    <?php if (!empty($cityChart['labels'])) { ?>
                        <div class="crm-chart-wrap">
                            <canvas id="crmCityChart"></canvas>
                        </div>
                    <?php } else { ?>
                        <div class="crm-chart-empty">Belum ada data kota untuk filter ini.</div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="fw-600">Distribusi Status Customer</div>
                </div>
                <div class="card-body">
                    <?php if (!empty($statusChart['labels'])) { ?>
                        <div class="crm-chart-wrap">
                            <canvas id="crmStatusChart"></canvas>
                        </div>
                    <?php } else { ?>
                        <div class="crm-chart-empty">Belum ada data status customer untuk filter ini.</div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="fw-600">Distribusi Keluhan Customer</div>
                </div>
                <div class="card-body">
                    <?php if (!empty($keluhanChart['labels'])) { ?>
                        <div class="crm-chart-wrap">
                            <canvas id="crmKeluhanChart"></canvas>
                        </div>
                    <?php } else { ?>
                        <div class="crm-chart-empty">Belum ada data label keluhan untuk filter ini.</div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
                <div class="card h-100">
                <div class="card-header">
                    <div class="fw-600">Trend Customer Baru vs Repeat Buyer</div>
                </div>
                <div class="card-body">
                    <?php if (!empty($trendCustomerChart['labels'])) { ?>
                        <div class="crm-chart-wrap is-tall">
                            <canvas id="crmTrendCustomerChart"></canvas>
                        </div>
                    <?php } else { ?>
                        <div class="crm-chart-empty">Belum ada data trend customer untuk filter ini.</div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-geo@4.3.6/build/index.umd.min.js"></script>
<script>
    $(function() {
        const $productFilter = $('#crmAnalyticsProductFilter');
        if ($productFilter.length && typeof $productFilter.select2 === 'function') {
            $productFilter.select2({
                width: '100%',
                placeholder: 'Pilih produk',
                allowClear: true
            });
        }

        function initAnalyticsDateFilterInput() {
            $.ajax({
                dataType: 'json',
                url: '<?= base_url() ?>/ajax/get-filter',
                data: {
                    start_date: $('#crmAnalyticsStartDate').val() || '<?= htmlspecialchars($start_date ?? date('Y-m-01'), ENT_QUOTES, 'UTF-8') ?>',
                    until_date: $('#crmAnalyticsUntilDate').val() || '<?= htmlspecialchars($until_date ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>',
                    input_id: 'crmAnalyticsTanggal',
                    start_id: 'crmAnalyticsStartDate',
                    end_id: 'crmAnalyticsUntilDate'
                },
                success: function(response) {
                    const $input = $('#crmAnalyticsTanggal');
                    $input.next('.dropdown').remove();
                    $input.after(response.html);

                    $('#crmAnalyticsTanggal')
                        .off('apply.daterangepicker.crmAnalytics')
                        .on('apply.daterangepicker.crmAnalytics', function(ev, picker) {
                            const startValue = picker.startDate.format('YYYY-MM-DD');
                            const endValue = picker.endDate.format('YYYY-MM-DD');
                            $('#crmAnalyticsStartDate').val(startValue);
                            $('#crmAnalyticsUntilDate').val(endValue);
                        });

                    $('#crmAnalyticsTanggal').val(formatAnalyticsDateRange(
                        $('#crmAnalyticsStartDate').val(),
                        $('#crmAnalyticsUntilDate').val()
                    ));
                }
            });
        }

        function formatAnalyticsDateRange(startDate, endDate) {
            if (!startDate || !endDate) {
                return '';
            }

            function formatDisplay(value) {
                const parts = value.split('-');
                if (parts.length !== 3) {
                    return value;
                }
                return parts[2] + '/' + parts[1] + '/' + parts[0];
            }

            return formatDisplay(startDate) + ' - ' + formatDisplay(endDate);
        }

        initAnalyticsDateFilterInput();
    });

    (function() {
        if (typeof Chart === 'undefined') {
            return;
        }

        const chartColors = [
            '#2563eb',
            '#0ea5e9',
            '#14b8a6',
            '#22c55e',
            '#f59e0b',
            '#ef4444',
            '#8b5cf6',
            '#ec4899',
            '#64748b',
            '#0f172a'
        ];

        function buildBarChart(canvasId, labels, values, color) {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !labels.length) return;

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: color,
                        borderRadius: 8,
                        maxBarThickness: 32
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.raw + ' customer';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        },
                        y: {
                            ticks: {
                                autoSkip: false
                            }
                        }
                    }
                }
            });
        }

        function buildDonutChart(canvasId, labels, values) {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !labels.length) return;

            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: labels.map(function(_, index) {
                            return chartColors[index % chartColors.length];
                        }),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.label + ': ' + context.raw;
                                }
                            }
                        }
                    },
                    cutout: '62%'
                }
            });
        }

        function buildTrendChart(canvasId, labels, newValues, repeatValues) {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !labels.length) return;

            const ctx = canvas.getContext('2d');
            const maxNewValue = Math.max.apply(null, [0].concat(newValues || []));
            const maxRepeatValue = Math.max.apply(null, [0].concat(repeatValues || []));
            const newAxisMax = maxNewValue > 0 ? Math.ceil(maxNewValue * 1.1) : 10;

            let repeatAxisMax = maxRepeatValue > 0 ? Math.ceil(maxRepeatValue * 1.2) : 10;
            if (maxNewValue > 0 && maxRepeatValue > 0) {
                const ratio = maxRepeatValue / maxNewValue;
                const desiredPeakRatio = Math.min(0.45, Math.max(0.18, ratio * 6));
                repeatAxisMax = Math.max(repeatAxisMax, Math.ceil(maxRepeatValue / desiredPeakRatio));
            }

            const gradientNew = ctx.createLinearGradient(0, 0, 0, canvas.height || 360);
            gradientNew.addColorStop(0, 'rgba(37, 99, 235, 0.28)');
            gradientNew.addColorStop(1, 'rgba(37, 99, 235, 0.02)');

            const gradientRepeat = ctx.createLinearGradient(0, 0, 0, canvas.height || 360);
            gradientRepeat.addColorStop(0, 'rgba(16, 185, 129, 0.24)');
            gradientRepeat.addColorStop(1, 'rgba(16, 185, 129, 0.02)');

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Customer Baru',
                            data: newValues,
                            borderColor: '#2563eb',
                            backgroundColor: gradientNew,
                            fill: true,
                            tension: 0.32,
                            pointRadius: 4,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#2563eb',
                            borderWidth: 3,
                            yAxisID: 'yNew'
                        },
                        {
                            label: 'Repeat Buyer',
                            data: repeatValues,
                            borderColor: '#10b981',
                            backgroundColor: gradientRepeat,
                            fill: true,
                            tension: 0.32,
                            pointRadius: 4,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#10b981',
                            borderWidth: 3,
                            yAxisID: 'yRepeat'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.dataset.label + ': ' + context.raw + ' customer';
                                }
                            }
                        }
                    },
                    scales: {
                        yNew: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            max: newAxisMax,
                            title: {
                                display: true,
                                text: 'Customer Baru'
                            },
                            ticks: {
                                precision: 0
                            }
                        },
                        yRepeat: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                            max: repeatAxisMax,
                            title: {
                                display: true,
                                text: 'Repeat Buyer'
                            },
                            ticks: {
                                precision: 0
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        function normalizeProvinceName(value) {
            const raw = (value || '').toString().trim().toUpperCase();
            if (!raw) return '';

            const compact = raw
                .replace(/\s+/g, ' ')
                .replace(/\./g, '')
                .replace(/DI YOGYAKARTA/g, 'DAERAH ISTIMEWA YOGYAKARTA')
                .replace(/YOGYAKARTA/g, 'DAERAH ISTIMEWA YOGYAKARTA')
                .replace(/DKI JAKARTA/g, 'DAERAH KHUSUS IBUKOTA JAKARTA')
                .replace(/JAKARTA RAYA/g, 'DAERAH KHUSUS IBUKOTA JAKARTA')
                .replace(/KEP BANGKA BELITUNG/g, 'KEPULAUAN BANGKA BELITUNG')
                .replace(/BANGKA BELITUNG/g, 'KEPULAUAN BANGKA BELITUNG')
                .replace(/KEP RIAU/g, 'KEPULAUAN RIAU')
                .replace(/PAPUA BARAT DAYA/g, 'PAPUA BARAT DAYA')
                .replace(/PAPUA SELATAN/g, 'PAPUA SELATAN')
                .replace(/PAPUA TENGAH/g, 'PAPUA TENGAH')
                .replace(/PAPUA PEGUNUNGAN/g, 'PAPUA PEGUNUNGAN')
                .replace(/PAPUA BARAT/g, 'PAPUA BARAT')
                .replace(/PAPUA$/g, 'PAPUA')
                .trim();

            return compact;
        }

        const crmProvinceValueLabelPlugin = {
            id: 'crmProvinceValueLabelPlugin',
            afterDatasetsDraw(chart) {
                if (!chart || chart.config.type !== 'choropleth') {
                    return;
                }

                const datasetMeta = chart.getDatasetMeta(0);
                if (!datasetMeta || !Array.isArray(datasetMeta.data)) {
                    return;
                }

                const dataset = chart.data?.datasets?.[0];
                if (!dataset || !Array.isArray(dataset.data)) {
                    return;
                }

                const ctx = chart.ctx;
                ctx.save();

                datasetMeta.data.forEach(function(element, index) {
                    const raw = dataset.data[index];
                    const value = Number(raw?.value || 0);
                    if (!value || !element) {
                        return;
                    }

                    const props = typeof element.getProps === 'function'
                        ? element.getProps(['x', 'y'], true)
                        : { x: element.x, y: element.y };

                    const x = Number(props?.x);
                    const y = Number(props?.y);
                    if (!Number.isFinite(x) || !Number.isFinite(y)) {
                        return;
                    }

                    ctx.font = '700 10px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.lineWidth = 2;
                    ctx.strokeStyle = 'rgba(255,255,255,0.7)';
                    ctx.fillStyle = '#000000';
                    ctx.strokeText(String(value), x, y);
                    ctx.fillText(String(value), x, y);
                });

                ctx.restore();
            }
        };

        async function buildProvinceMapChart() {
            const canvas = document.getElementById('crmProvinceChart');
            const labels = <?= json_encode($provinceChart['labels'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const values = <?= json_encode($provinceChart['values'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            if (!canvas || !labels.length || typeof ChartGeo === 'undefined') return;

            const provinceMap = {};
            labels.forEach(function(label, index) {
                provinceMap[normalizeProvinceName(label)] = Number(values[index] || 0);
            });

            const response = await fetch('https://raw.githubusercontent.com/superpikar/indonesia-geojson/master/indonesia-province-simple.json');
            const geojson = await response.json();
            const features = (geojson && geojson.features) ? geojson.features : [];

            const data = features.map(function(feature) {
                const featureName = normalizeProvinceName(feature?.properties?.Propinsi || feature?.properties?.PROVINSI || feature?.properties?.province || '');
                return {
                    feature: feature,
                    value: Number(provinceMap[featureName] || 0)
                };
            });

            const maxValue = data.reduce(function(max, item) {
                return Math.max(max, Number(item.value || 0));
            }, 0);

            const provinceChart = new Chart(canvas, {
                type: 'choropleth',
                data: {
                    labels: features.map(function(feature) {
                        return feature?.properties?.Propinsi || '-';
                    }),
                    datasets: [{
                        label: 'Jumlah Customer',
                        outline: features,
                        data: data
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    showOutline: true,
                    showGraticule: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                title: function(items) {
                                    const feature = items[0]?.raw?.feature;
                                    return feature?.properties?.Propinsi || '-';
                                },
                                label: function(context) {
                                    return ' Customer: ' + Number(context.raw?.value || 0);
                                }
                            }
                        }
                    },
                    scales: {
                        projection: {
                            axis: 'x',
                            projection: 'mercator'
                        },
                        color: {
                            axis: 'x',
                            quantize: 5,
                            interpolate: function(value) {
                                const ratio = maxValue > 0 ? Math.max(0, Math.min(1, value / maxValue)) : 0;
                                const alpha = 0.18 + (ratio * 0.82);
                                return 'rgba(37, 99, 235, ' + alpha.toFixed(3) + ')';
                            },
                            legend: {
                                position: 'bottom-right',
                                align: 'bottom'
                            }
                        }
                    }
                },
                plugins: [crmProvinceValueLabelPlugin]
            });

        }

        buildProvinceMapChart().catch(function(error) {
            console.error('Failed to render province map', error);
        });

        buildTrendChart(
            'crmTrendCustomerChart',
            <?= json_encode($trendCustomerChart['labels'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            <?= json_encode($trendCustomerChart['new_customer'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            <?= json_encode($trendCustomerChart['repeat_buyer'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        );

        buildBarChart(
            'crmCityChart',
            <?= json_encode($cityChart['labels'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            <?= json_encode($cityChart['values'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            '#0f766e'
        );

        buildDonutChart(
            'crmStatusChart',
            <?= json_encode($statusChart['labels'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            <?= json_encode($statusChart['values'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        );

        buildDonutChart(
            'crmKeluhanChart',
            <?= json_encode($keluhanChart['labels'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            <?= json_encode($keluhanChart['values'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        );
    })();
</script>
