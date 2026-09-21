<?php if (empty($items)): ?>
    <div class="alert alert-info mb-3">Tidak ada data.</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($items as $item): ?>
    <div class="col-12">
        <div class="card mb-1">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="me-3">
                        <div class="divCircle" style="background:<?= crm_escape($item['bg_color']) ?>">
                            <div class="centeredElement fw-700 fs-16">
                                <?= crm_escape($item['initials']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                    <span class="fw-700 fs-14 text-blue">#<?= (int) $item['index'] ?></span>
                                    <a class="fw-700 fs-16 a-none text-dark" href="<?= base_url() ?>crm/detail?id=<?= crm_escape($item['id']) ?>&brand=<?= crm_escape($item['brand']) ?>">
                                        <?= crm_escape($item['full_name']) ?>
                                    </a>
                                    <span class="badge bg-light text-dark border"><?= crm_escape($item['cb_cl']) ?></span>
                                    <span class="badge bg-light text-dark border"><?= $item['marketplace'] ?></span>
                                </div>
                                
                                <div class="row g-2 small text-muted">
                                    <div class="col-auto">
                                        <strong>User:</strong> <?= $item['username'] ?>
                                    </div>
                                    <div class="col-auto">
                                        <strong>CS:</strong> <?= $item['cs'] ?>
                                    </div>
                                    <div class="col-auto">
                                        <strong>Status:</strong> <?= crm_escape($item['order_status_text']) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="checkbox-wrapper-13 d-inline-block">
                                    <input
                                        class="checkItem"
                                        type="checkbox"
                                        value="<?= crm_escape($item['id']) ?>"
                                        data-id="<?= crm_escape($item['raw_index']) ?>"
                                        name="list_id"
                                        form="form-action">
                                </div>
                                <div class="mt-2">
                                    <a href="#!" class="text-blue me-2 history-trigger" 
                                       data-id="<?= crm_escape($item['id']) ?>" 
                                       data-bs-toggle="tooltip" 
                                       title="Lihat History Order">
                                        <i class="bi bi-clock-history text-icon"></i>
                                    </a>
                                    <a href="#!" onclick="edit('<?= crm_escape($item['id']) ?>')" class="text-blue me-2"><i class="bi bi-pen text-icon"></i></a>
                                    <a href="#!" onclick="remove('<?= crm_escape($item['id']) ?>')" class="text-red"><i class="bi bi-trash text-icon"></i></a>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="small">
                                        <p class="mb-1"><strong><i class="bi bi-telephone me-1"></i>HP:</strong>
                                            <?php if ($item['wa_link']): ?>
                                                <a href="<?= crm_escape($item['wa_link']) ?>" target="_blank" class="text-decoration-none"><?= crm_escape($item['phone_display']) ?></a>
                                            <?php else: ?>
                                                <?= crm_escape($item['phone_display']) ?>
                                            <?php endif; ?>
                                        </p>
                                        <p class="mb-2"><strong><i class="bi bi-calendar me-1"></i>Tgl Lahir:</strong> <?= crm_escape($item['birth_date']) ?></p>
                                        <p class="mb-2"><strong><i class="bi bi-house me-1"></i>Alamat:</strong> <?= $item['address'] ?></p>
                                        <p class="mb-2"><strong><i class="bi bi-house me-1"></i>Domisili:</strong> <?= $item['city_text'] ?></p>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="small">
                                        <p class="mb-1"><strong><i class="bi bi-card-text me-1"></i>Keterangan:</strong> 
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?= crm_escape($item['description']) ?>">
                                                <?= $item['description'] ?>
                                            </span>
                                        </p>
                                        <p class="mb-1"><strong><i class="bi bi-gift me-1"></i>Gift:</strong> <?= $item['gift_html'] ?></p>
                                        <p class="mb-1"><strong><i class="bi bi-activity me-1"></i>Progres:</strong> <?= $item['testimoni_html'] ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-2">

                        <div class="row g-2 small text-muted">
                            <div class="col-6 col-md-3">
                                <strong>Created:</strong> <?= $item['created_at'] ?>
                            </div>
                            <div class="col-6 col-md-3">
                                <strong>First Order:</strong> <?= $item['first_order'] ?>
                            </div>
                            <div class="col-6 col-md-3">
                                <strong>Last Order:</strong> <?= $item['last_order'] ?>
                            </div>
                            <div class="col-6 col-md-3">
                                <strong>Total Order:</strong> <?= $item['count_order'] ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden inputs -->
                <input type="hidden" value="<?= crm_escape($item['is_manual']) ?>" name="is_manual[<?= crm_escape($item['raw_index']) ?>]" form="form-action">
                <input type="hidden" value="<?= crm_escape($item['marketplace_raw']) ?>" name="marketplace[<?= crm_escape($item['raw_index']) ?>]" form="form-action">
                <input type="hidden" value="<?= crm_escape($item['brand']) ?>" name="brand[<?= crm_escape($item['raw_index']) ?>]" form="form-action">
                <input type="hidden" value="<?= crm_escape($item['order_id_raw']) ?>" name="order_id[<?= crm_escape($item['raw_index']) ?>]" form="form-action">
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
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
</style>
<?php endif; ?>

<!-- Tambahkan script tippyjs -->
<script>
$(document).ready(function() {
    $('.history-trigger').each(function() {
        const trigger = this;
        const itemId = $(this).data('id');
        
        tippy(trigger, {
            content: '<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat history order...</div></div>',
            allowHTML: true,
            interactive: true,
            placement: 'right',
            theme: 'light',
            maxWidth: 500,
            appendTo: () => document.body,
            zIndex: 2100,
            onShow(instance) {
                $.ajax({
                    url: '<?= base_url() ?>crm/get_order_history',
                    type: 'POST',
                    data: { 
                        id: itemId,
                        brand: '<?= isset($_GET['brand']) ? crm_escape($_GET['brand']) : "" ?>'
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.success) {
                            let historyContent = '';
                            
                            if (data.history && data.history.length > 0) {
                                data.history.forEach(function(history) {
                                    historyContent += `
                                        <div class="border rounded p-2 mb-2">
                                            <p class="mb-1 fw-600">
                                                ${history.order_url ? 
                                                    `<a target="_blank" href="${history.order_url}">${history.order_id}</a>` : 
                                                    history.order_id
                                                }
                                            </p>
                                            <p class="mb-1 small text-muted">Tanggal: ${history.date}</p>
                                            <p class="mb-1 small">Status: ${history.order_status}</p>`;
                                    
                                    // PERBAIKAN: Handle history.json dengan parsing yang benar
                                    if (history.json) {
                                        try {
                                            // Parse JSON jika masih string
                                            const items = typeof history.json === 'string' ? JSON.parse(history.json) : history.json;
                                            console.log('Parsed order items:', items);
                                            
                                            if (items && Object.keys(items).length > 0) {
                                                Object.values(items).forEach(function(item) {
                                                    // Tambahkan pengecekan untuk memastikan item ada
                                                    if (item && item.qty && item.product_text) {
                                                        historyContent += `<p class="mb-1 small">${item.qty} x ${item.product_text}</p>`;
                                                    }
                                                });
                                            } else {
                                                historyContent += `<p class="mb-1 small text-muted">Tidak ada item</p>`;
                                            }
                                        } catch (error) {
                                            console.error('Error parsing JSON:', error, history.json);
                                            historyContent += `<p class="mb-1 small text-danger">Error loading items</p>`;
                                        }
                                    } else {
                                        historyContent += `<p class="mb-1 small text-muted">Tidak ada item</p>`;
                                    }
                                    
                                    historyContent += `</div>`;
                                });
                            } else {
                                historyContent = '<p class="text-danger mb-0 text-center">Belum ada history order!</p>';
                            }
                            
                            const tooltipContent = `
                                <div class="history-tooltip p-2" style="min-width: 300px; max-height: 400px; overflow-y: auto;">
                                    <h6 class="fw-bold mb-3 text-center">History Order</h6>
                                    ${historyContent}
                                </div>
                            `;
                            instance.setContent(tooltipContent);
                        } else {
                            instance.setContent(`
                                <div class="p-2 text-danger text-center">
                                    Gagal memuat history order
                                </div>
                            `);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        instance.setContent(`
                            <div class="p-2 text-danger text-center">
                                Gagal memuat history order
                                <div class="small mt-2">${error}</div>
                            </div>
                        `);
                    }
                });
            }
        });
    });
});
</script>
