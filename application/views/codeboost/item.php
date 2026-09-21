<?php foreach ($data as $v): 
    if ($v['platform'] == "Tiktok") {
        $v['img'] = base_url() . '/assets/img/icon/icon-tiktok.png';
    } else if ($v['platform'] == "Instagram") {
        $v['img'] = base_url() . '/assets/img/icon/icon-ig.png';
    } else if ($v['platform'] == "Youtube") {
        $v['img'] = base_url() . '/assets/img/icon/icon-youtube.png';
    } else if ($v['platform'] == "Facebook") {
        $v['img'] = base_url() . '/assets/img/icon/icon-fb.png';
    } else if ($v['platform'] == "Twitter") {
        $v['img'] = base_url() . '/assets/img/icon/icon-twitter.png';
    } else if ($v['platform'] == "Threads") {
        $v['img'] = base_url() . '/assets/img/icon/icon-threads.png';
    } else {
        $v['img'] = base_url() . '/assets/img/icon/icon-no.png';
    }
    
    if ($v['link_upload']) {
        $link_upload = htmlspecialchars($v['link_upload'], ENT_QUOTES);
        $tiktok_attr = ($v['platform'] === "Tiktok")
            ? ' class="tiktok-embed-trigger" data-tiktok-url="' . $link_upload . '"'
            : '';
        $v['img'] = '<a href="' . $link_upload . '" target="_blank" rel="noopener noreferrer"' . $tiktok_attr . '><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img'] . '"></a>';
    } else {
        $v['img'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img'] . '">';
    }
?>
<tr>
    <td class="text-start text-blue fw-700">#<?= $k + 1 ?></td>
    <td class="text-start">
        <div>
            <?= $v['nama_creator'] ?>
        </div>
    </td>
    <td class="text-start"><?= $v['product_text'] ? $v['product_text'] : '-' ?></td>
    <td class="text-center" data-sort="<?= strtotime($v['posting_at']) ?>"><?= $v['posting_at'] ?></td>
    <td class="text-center" data-sort="<?= $v['views'] ?>"><?= $this->template->separator_only($v['views']); ?></td>
    <td>
        <?php if (!empty($v['kode_ads'])): ?>
            <p class="mb-1">
                <a href="javascript:void(0)"
                class="text-decoration-none btn-copy"
                data-kode="<?= htmlspecialchars($v['kode_ads']) ?>"
                title="Salin Kode">
                    <i class="bi bi-copy fs-16"></i>
                </a>
            </p>
        <?php endif; ?>
        <div class="firstDivImg">
            <?= $v['img'] ?>
        </div>
    </td>


</tr>
<?php $k += 1; ?>
<?php endforeach; ?>

<script>
    document.querySelectorAll('.btn-copy').forEach(function (button) {
        button.addEventListener('click', function () {
            const kode = this.getAttribute('data-kode');
            const icon = this.querySelector('i');

            if (!kode || !icon) return;

            const originalClass = icon.className;

            icon.className = 'spinner-border spinner-border-sm';

            navigator.clipboard.writeText(kode).then(() => {
                setTimeout(() => {
                    icon.className = 'bi bi-check fs-16 text-success';
                }, 500);

                setTimeout(() => {
                    icon.className = originalClass;
                }, 2000);
            }).catch((err) => {
                console.error('Gagal menyalin:', err);
                icon.className = 'bi bi-x fs-16 text-danger';
                setTimeout(() => {
                    icon.className = originalClass;
                }, 2000);
            });
        });
    });

</script>

<style>
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
    background: #f3f4f6;
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
            '<div style="width:320px; height:520px; overflow:hidden; border-radius:8px; background:#000;">' +
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

    function init_tiktok_photo_carousel(container) {
        var root = container.querySelector('.tt-carousel');
        if (!root) return;
        var images = Array.prototype.slice.call(root.querySelectorAll('.tt-images span')).map(function(s) {
            return s.getAttribute('data-src');
        });
        var frameImg = root.querySelector('.tt-carousel-frame img');
        var dots = Array.prototype.slice.call(root.querySelectorAll('.tt-dot'));
        var counter = root.querySelector('.tt-counter');
        var total = images.length;

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
    }

    $(document).ready(function() {
        var tiktokTriggers = document.querySelectorAll('.tiktok-embed-trigger');
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
                                    init_tiktok_photo_carousel(instance.popper);
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
                $.ajax({
                    url: '<?= base_url() ?>endorse/get_tiktok_video_play',
                    type: 'POST',
                    data: { url: url },
                    dataType: 'json',
                    success: function(res) {
                        if (res && res.status && res.data && res.data.play) {
                            instance.setContent(build_tiktok_video_html(res.data.play));
                        } else {
                            instance.setContent('<div class="p-2 text-muted">Video TikTok tidak ditemukan dari link upload.</div>');
                        }
                    },
                    error: function() {
                        instance.setContent('<div class="p-2 text-danger">Gagal memuat video TikTok.</div>');
                    }
                });
            }
        });
    });
</script>
