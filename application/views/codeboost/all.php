<div class="w-100">
<style>
    .select2-container .select2-selection--multiple {
        min-height: 45px;
        border-radius: 0.5rem !important;
        border: 1px solid #ced4da;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        padding: 6px;
        background-color: #f8f9fa;
        color: #212529;
        border-radius: 0.25rem;
        margin-right: 4px;
    }

    .select2-container .select2-search--inline .select2-search__field {
        padding-top: 6px !important;
        font-size: 14px;
    }
</style>
<style>
    .btn-platform {
        min-width: 100px;
    }
    
    .copy-btn {
        padding: 0.15rem 0.3rem;
        font-size: 0.8rem;
    }
    
    .btn-youtube {
        color: #FF0000;
        border-color: #FF0000;
    }
    .btn-instagram {
        color: #E1306C;
        border-color: #E1306C;
    }
    .btn-tiktok {
        color: #000000;
        border-color: #000000;
    }
    .btn-facebook {
        color: #1877F2;
        border-color: #1877F2;
    }
</style>
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">CODEBOOST</h3>
        </div>
        <div class="col-lg-12">
            <form id="filterForm">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="input-with-button">
                            <input type="hidden" name="product_name" id="productNameHidden">

                            <select class="form-control select2" id="productSearch" multiple>
                                <?php
                                $productNames = isset($_GET['product_name']) ? explode(',', $_GET['product_name']) : [];
                                ?>
                                <?php if (!empty($productNames)): ?>
                                    <?php foreach ($productNames as $name): ?>
                                        <option value="<?= htmlspecialchars($name) ?>" selected><?= htmlspecialchars($name) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                                    
                            <script>
                                $(function () {
                                    $('#productSearch').select2({
                                        minimumInputLength: 1,
                                        placeholder: 'Cari produk...',
                                        ajax: {
                                            dataType: 'json',
                                            url: '<?= base_url() ?>/ajax/get-product-list',
                                            delay: 100,
                                            data: function (params) {
                                                return { search: params.term };
                                            },
                                            processResults: function (data) {
                                                return { results: data };
                                            }
                                        },
                                        language: {
                                            inputTooShort: function () {
                                                return "Masukkan 1 karakter atau lebih";
                                            }
                                        }
                                    });
                                });
                            </script>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="d-flex">
                            <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                            <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                            <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                        </div>
                        <script>
                            get_filter();
                            function get_filter() {
                                $.ajax({
                                    dataType: "json",
                                    url: '<?= base_url() ?>/ajax/get-filter',
                                    data: {
                                        start_date: "<?= $_GET['start_date'] ?? $start_date ?>",
                                        until_date: "<?= $_GET['until_date'] ?? $until_date ?>",
                                    },
                                    success: function(response) {
                                        $("#tanggal").after(response.html);
                                        $('#tanggal').on('apply.daterangepicker', function() {
                                            table.ajax.reload();
                                        });
                                    },
                                    error: function(xhr, status, error) {
                                        console.error("Error loading filter:", error);
                                    }
                                });
                            }
                        </script>
                    </div>
                    <div class="col-lg-4">
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-edit-active" type="submit">
                            <i class="bi bi-search fs-16"></i> Cari Data
                        </button>
                        <button id="exportExcel" class="btn btn-success" type="button">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="col-lg-12">
    <div class="form-message"></div>
    <div class="table-responsive">
        <table id="codeboostTable" class="table table-bordered table-striped" style="width:100%">
            <thead>
                <tr class="bg-blue-2 text-white">
                    <th class="text-center">#</th>
                    <th class="text-start">NAMA KOL</th>
                    <th class="text-center">PRODUK</th>
                    <th class="text-center">TANGGAL POSTING</th>
                    <th class="text-center">VIEWS</th>
                    <th class="text-center">LINK VT</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data akan diisi melalui DataTables -->
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    var table;
    table = $('#codeboostTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "<?= base_url('codeboost/get_data') ?>",
            "type": "GET",
            "data": function(d) {
                d.start_date = $('#start_date').val();
                d.until_date = $('#end_date').val();
                d.product_name = $('#productNameHidden').val();
            }
        },
        "columns": [
            {
                "data": null,
                "render": function(data, type, row, meta) {
                    const nomor = meta.row + meta.settings._iDisplayStart + 1;
                    return '<span class="text-blue fw-600">#' + nomor + '</span>';
                },
                "orderable": false
            },
            { "data": "1" },
            { "data": "2" },
            { 
                "data": "3",
                "render": function(data, type, row) {
                    return '<span data-sort="'+data+'">'+data+'</span>';
                }
            },
            { 
                "data": "4",
                "className": "text-end",
                "render": function(data, type, row) {
                    return '<span data-sort="'+data.replace(/,/g, '')+'">'+data+'</span>';
                }
            },
            { 
                "data": "5",
                "orderable": false,
                "render": function(data, type, row) {
                    return data;
                }
            },
        ],
        "order": [[0, 'desc']],
        "language": {
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "zeroRecords": "Tidak ada data yang ditemukan",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
            "infoFiltered": "(disaring dari _MAX_ total data)",
            "paginate": {
                "first": '<i class="bi bi-chevron-double-left"></i>',
                "last": '<i class="bi bi-chevron-double-right"></i>',
                "next": '<i class="bi bi-chevron-right"></i>',
                "previous": '<i class="bi bi-chevron-left"></i>'
            }
        },
        "dom": "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",

        "lengthMenu": [10, 20, 50, 100, 500],
        "pageLength": <?= $_GET['limit'] ?? 10 ?>,
        "drawCallback": function() {
            initTiktokPreviewInCodeboost();
        }
    });

    $('#filterForm').on('submit', function (e) {
        e.preventDefault();
        const selectedTexts = $('#productSearch').select2('data').map(item => item.text);
        $('#productNameHidden').val(selectedTexts.length ? selectedTexts.join(',') : '');
        table.ajax.reload();
    });

    $(document).on('click', '.copy-btn', function () {
        const btn = $(this);
        const icon = btn.find('i');
        const originalColor = btn.css('color');

        navigator.clipboard.writeText(btn.data('clipboard-text')).then(function () {
            icon.removeClass('bi-clipboard').addClass('bi-check');
            btn.css('color', 'green');

            setTimeout(function () {
                icon.removeClass('bi-check').addClass('bi-clipboard');
                btn.css('color', originalColor);
            }, 1000);
        }).catch(function (err) {
            console.error('Gagal menyalin teks: ', err);
        });
    });

    $('#exportExcel').on('click', function () {
        const selectedTexts = $('#productSearch').select2('data').map(item => item.text);
        $('#productNameHidden').val(selectedTexts.length ? selectedTexts.join(',') : '');

        const params = new URLSearchParams({
            start_date: $('#start_date').val() || '',
            until_date: $('#end_date').val() || '',
            product_name: $('#productNameHidden').val() || '',
            search: ($('#codeboostTable_filter input').val() || '')
        });

        window.open("<?= base_url('codeboost/export') ?>?" + params.toString(), "_blank");
    });



});
</script>

<style>
.tippy-box[data-theme~='light'] {
    background-color: #ffffff !important;
    color: #333333 !important;
    box-shadow: 0 4px 14px rgba(0,0,0,0.1) !important;
    border: 1px solid #e5e7eb !important;
    z-index: 2100 !important;
}

.tippy-box[data-theme~='light'] .tippy-arrow {
    color: #ffffff !important;
}

.tippy-box[data-theme~='light'] .tippy-content {
    color: inherit !important;
}
.tippy-box,
.tippy-root,
.tippy-popper {
    z-index: 99999 !important;
}
.tt-carousel-frame {
    width: 320px;
    height: 520px;
    overflow: hidden;
    border-radius: 8px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
}
.tt-carousel-frame img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.tt-carousel-controls {
    margin-top: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.tt-btn {
    border: 1px solid #e5e7eb;
    background: #fff;
    border-radius: 6px;
    width: 28px;
    height: 28px;
    line-height: 24px;
    font-size: 18px;
    cursor: pointer;
}
.tt-dots {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: center;
    flex: 1;
}
.tt-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #d1d5db;
    cursor: pointer;
}
.tt-dot.active {
    background: #111827;
}
.tt-counter {
    margin-top: 6px;
    font-size: 12px;
    color: #6b7280;
    text-align: center;
}
</style>

<script>
    function extract_tiktok_video_id(rawUrl) {
        if (!rawUrl) return '';
        var url = String(rawUrl).trim();
        var match =
            url.match(/\/video\/(\d+)/i) ||
            url.match(/\/v\/(\d+)/i) ||
            url.match(/\/(\d+)\.html/i);
        return match ? match[1] : '';
    }

    function extract_tiktok_photo_id(rawUrl) {
        if (!rawUrl) return '';
        var url = String(rawUrl).trim();
        var match = url.match(/\/photo\/(\d+)/i);
        return match ? match[1] : '';
    }

    function build_tiktok_video_html(playUrl) {
        if (!playUrl) return '';
        return '' +
            '<div style="width:320px; height:520px; overflow:hidden; border-radius:8px; background:#fff;">' +
            '  <video src="' + playUrl + '" style="width:100%; height:100%; object-fit:cover;" controls autoplay muted loop playsinline preload="metadata"></video>' +
            '</div>';
    }

    function build_tiktok_photo_carousel(images, uid) {
        if (!images || !images.length) return '';
        var dots = images.map(function(_, i) {
            return '<span class="tt-dot ' + (i === 0 ? 'active' : '') + '" data-idx="' + i + '"></span>';
        }).join('');
        var imagesHtml = images.map(function(u) {
            return '<span data-src="' + u + '"></span>';
        }).join('');
        return '' +
            '<div class="tt-carousel" data-uid="' + uid + '" data-total="' + images.length + '" data-index="0" style="width:320px;">' +
            '  <div class="tt-carousel-frame"><img src="' + images[0] + '" alt="TikTok Photo" /></div>' +
            '  <div class="tt-carousel-controls">' +
            '    <button type="button" class="tt-btn" data-dir="-1">‹</button>' +
            '    <div class="tt-dots">' + dots + '</div>' +
            '    <button type="button" class="tt-btn" data-dir="1">›</button>' +
            '  </div>' +
            '  <div class="tt-counter">1 / ' + images.length + '</div>' +
            '  <div class="tt-images" style="display:none;">' + imagesHtml + '</div>' +
            '</div>';
    }

    function init_tiktok_photo_carousel(container, autoSlideMs = 2500) {
        var root = container.querySelector('.tt-carousel');
        if (!root) return;
        var images = Array.prototype.slice.call(root.querySelectorAll('.tt-images span')).map(function(s) {
            return s.getAttribute('data-src');
        });
        var frameImg = root.querySelector('.tt-carousel-frame img');
        var dots = Array.prototype.slice.call(root.querySelectorAll('.tt-dot'));
        var counter = root.querySelector('.tt-counter');
        var total = images.length;
        var timerId = null;

        function setIndex(nextIndex) {
            var idx = nextIndex;
            if (idx < 0) idx = total - 1;
            if (idx >= total) idx = 0;
            root.setAttribute('data-index', String(idx));
            frameImg.src = images[idx];
            dots.forEach(function(d) {
                d.classList.toggle('active', Number(d.dataset.idx) === idx);
            });
            counter.textContent = (idx + 1) + ' / ' + total;
        }

        root.querySelectorAll('.tt-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var dir = Number(this.getAttribute('data-dir')) || 0;
                var current = Number(root.getAttribute('data-index')) || 0;
                setIndex(current + dir);
            });
        });

        dots.forEach(function(dot) {
            dot.addEventListener('click', function() {
                setIndex(Number(this.getAttribute('data-idx')) || 0);
            });
        });

        if (total > 1 && autoSlideMs > 0) {
            timerId = setInterval(function() {
                var current = Number(root.getAttribute('data-index')) || 0;
                setIndex(current + 1);
            }, autoSlideMs);
            root.dataset.timerId = String(timerId);
        }
    }

    function fetch_tiktok_video_with_retry(url, attempt, onSuccess, onFail) {
        var maxAttempts = 5;
        $.ajax({
            url: '<?= base_url() ?>endorse/get_tiktok_video_play',
            type: 'POST',
            data: { url: url },
            dataType: 'json',
            success: function(res) {
                if (res && res.status && res.data && res.data.play) {
                    onSuccess(res.data.play);
                } else if (attempt < maxAttempts) {
                    setTimeout(function() {
                        fetch_tiktok_video_with_retry(url, attempt + 1, onSuccess, onFail);
                    }, 400);
                } else {
                    onFail();
                }
            },
            error: function() {
                if (attempt < maxAttempts) {
                    setTimeout(function() {
                        fetch_tiktok_video_with_retry(url, attempt + 1, onSuccess, onFail);
                    }, 400);
                } else {
                    onFail();
                }
            }
        });
    }

    function initTiktokPreviewInCodeboost() {
        var anchors = document.querySelectorAll('#codeboostTable a[href*="tiktok.com"], #codeboostTable a[href*="vt.tiktok.com"]');
        if (!anchors.length) return;
        anchors.forEach(function(a) {
            if (!a.classList.contains('tiktok-embed-trigger')) {
                a.classList.add('tiktok-embed-trigger');
                a.setAttribute('data-tiktok-url', a.getAttribute('href'));
            }
        });

        var tiktokTriggers = document.querySelectorAll('#codeboostTable .tiktok-embed-trigger');
        if (!tiktokTriggers.length) return;
        tiktokTriggers.forEach(function(el) {
            if (el._tippy) el._tippy.destroy();
        });

        tippy(tiktokTriggers, {
            content: '<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat video...</div></div>',
            allowHTML: true,
            interactive: true,
            placement: 'right',
            theme: 'light',
            appendTo: function() { return document.body; },
            popperOptions: {
                strategy: 'fixed',
                modifiers: [
                    { name: 'preventOverflow', options: { boundary: 'viewport' } },
                    { name: 'flip', options: { boundary: 'viewport' } }
                ]
            },
            maxWidth: 360,
            onShow: function(instance) {
                var url = instance.reference.getAttribute('data-tiktok-url');
                var photoId = extract_tiktok_photo_id(url);
                if (photoId) {
                    instance.setContent('<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat foto...</div></div>');
                    $.ajax({
                        url: '<?= base_url() ?>endorse/get_tiktok_photo_images',
                        type: 'POST',
                        data: { content_id: photoId, url: url },
                        dataType: 'json',
                        success: function(res) {
                            if (res && res.status && Array.isArray(res.data) && res.data.length) {
                                var uid = 'ttc-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
                                instance.setContent(build_tiktok_photo_carousel(res.data, uid));
                                setTimeout(function() {
                                    init_tiktok_photo_carousel(instance.popper, 2500);
                                }, 0);
                            } else {
                                instance.setContent('<div class="p-2 text-muted">Foto TikTok tidak ditemukan dari link upload.</div>');
                            }
                        },
                        error: function() {
                            instance.setContent('<div class="p-2 text-danger">Gagal memuat foto TikTok.</div>');
                        }
                    });
                    return;
                }

                instance.setContent('<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat video...</div></div>');
                fetch_tiktok_video_with_retry(url, 1, function(playUrl) {
                    instance.setContent(build_tiktok_video_html(playUrl));
                    setTimeout(function() {
                        var vid = instance.popper.querySelector('video');
                        if (vid) vid.play().catch(function(){});
                    }, 0);
                }, function() {
                    instance.setContent('<div class="p-2 text-muted">Video TikTok tidak ditemukan dari link upload.</div>');
                });
            },
            onHidden: function(instance) {
                var root = instance.popper.querySelector('.tt-carousel');
                if (root && root.dataset.timerId) {
                    clearInterval(Number(root.dataset.timerId));
                    delete root.dataset.timerId;
                }
            }
        });
    }
</script>
