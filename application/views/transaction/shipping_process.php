<div class="form-message"></div>
<form action="<?= base_url() ?>transaction/shipping_process_execute" method="POST" id="form-shipping-process">
    <input type="hidden" name="id_selected" value="<?= $id_selected ?>">
    
    <p>Proses pengiriman untuk data terpilih?</p>
    
    <?php if (count($marketplaces) > 1): ?>
        <div class="alert alert-warning">
            <strong>Peringatan:</strong> Data berasal dari multiple marketplace. Pilih salah satu marketplace untuk diproses.
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Marketplace</label>
                <select name="marketplace" class="form-control" required id="marketplace-select">
                    <option value="">Pilih Marketplace</option>
                    <?php foreach ($marketplaces as $mp => $shops): ?>
                        <option value="<?= $mp ?>"><?= $mp ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Toko</label>
                <select name="shop_id" class="form-control" required id="shop-select">
                    <option value="">Pilih Toko</option>
                    <?php foreach ($marketplaces as $mp => $shops): ?>
                        <?php foreach ($shops as $shopId => $shopData): ?>
                            <?php
                                $ordersJson = htmlspecialchars(
                                    json_encode(array_values($shopData['orders'])),
                                    ENT_NOQUOTES,
                                    'UTF-8'
                                );
                                $transactionIdsJson = htmlspecialchars(
                                    json_encode(array_values($shopData['transaction_ids'] ?? [])),
                                    ENT_NOQUOTES,
                                    'UTF-8'
                                );
                                $shippingMapJson = htmlspecialchars(
                                    json_encode($shopData['shipping_map'] ?? []),
                                    ENT_NOQUOTES,
                                    'UTF-8'
                                );
                            ?>
                            <option value="<?= $shopId ?>" data-marketplace="<?= $mp ?>" data-orders='<?= $ordersJson ?>' data-transaction-ids='<?= $transactionIdsJson ?>' data-shipping-map='<?= $shippingMapJson ?>'>
                                <?= htmlspecialchars($shopData['shop_name']) ?> (<?= count($shopData['orders']) ?> orders)
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="row" id="courier-row" style="display: none;">
        <div class="col-md-6">
            <div class="form-group">
                <label>Kurir</label>
                <select name="shipping_method" class="form-control" id="courier-select">
                    <option value="">Pilih Kurir</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Form untuk Shopee Shipping Parameters -->
    <div id="shopee-shipping-params" style="display: none;">
        <div class="mt-3">
            <h6 class="mb-0">Pengaturan Pengiriman Shopee</h6>
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Alamat Pickup</label>
                        <select name="pickup_address_id" class="form-control" id="pickup-address-select">
                            <option value="">Pilih Alamat Pickup</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Waktu Pickup</label>
                        <select name="pickup_time_id" class="form-control" id="pickup-time-select">
                            <option value="">Pilih Waktu Pickup</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="tiktok-shipping-params" style="display: none;">
        <div class="mt-3">
            <h6 class="mb-0">Pengaturan Pengiriman Tiktok</h6>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Waktu Pickup</label>
                        <select name="tiktok_pickup_slot" class="form-control" id="tiktok-pickup-time-select">
                            <option value="">Pilih Waktu Pickup</option>
                        </select>
                        <input type="hidden" name="tiktok_pickup_start_time" id="tiktok-pickup-start-time">
                        <input type="hidden" name="tiktok_pickup_end_time" id="tiktok-pickup-end-time">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12 mt-3" id="auto-generate-doc-container" style="display: none;">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="auto-generate-doc" name="auto_generate_documents" value="1" checked>
            <label class="form-check-label" for="auto-generate-doc">
                Generate dokumen pengiriman otomatis setelah proses order
            </label>
        </div>
    </div>

    <div class="col-md-12 mt-3">
        <button type="submit" class="btn btn-primary btn-send">Proses Pengiriman</button>
    </div>
</form>

<script>
function escapeHtml(value) {
    return String(value === undefined || value === null ? '' : value).replace(/[&<>"']/g, function (ch) {
        return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[ch]);
    });
}

var SHIPPING_DOCUMENTS_EXECUTE_URL = '<?= base_url() ?>transaction/shipping_documents_execute';
var PRINT_SHIPPING_DOCS_URL = '<?= base_url('transaction/print-shipping-docs') ?>';
var PRINT_SHIPPING_DOCS_SHOPEE_URL = '<?= base_url('transaction/print-shipping-docs-shopee') ?>';
var autoDocCheckbox = $('#auto-generate-doc');
var autoDocContainer = $('#auto-generate-doc-container');
var idSelectedInput = $('input[name="id_selected"]');
var courierRow = $('#courier-row');
var courierSelect = $('#courier-select');
autoDocCheckbox.data('lastTiktokState', autoDocCheckbox.is(':checked'));

function isShopeeMarketplace(value) {
    return (value || '').toUpperCase().indexOf('SHOPEE') !== -1;
}

function isTiktokMarketplace(value) {
    return (value || '').toUpperCase().indexOf('TIKTOK') !== -1;
}

function isLazadaMarketplace(value) {
    return (value || '').toUpperCase().indexOf('LAZADA') !== -1;
}

// Filter shop berdasarkan marketplace
$('#marketplace-select').change(function() {
    var marketplace = $(this).val() || '';
    
    // Filter shop options
    $('#shop-select option').each(function() {
        var shopMarketplace = $(this).data('marketplace');
        if (!marketplace || shopMarketplace === marketplace) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    $('#shop-select').val('');

    if (isShopeeMarketplace(marketplace)) {
        $('#shopee-shipping-params').show();
    } else {
        $('#shopee-shipping-params').hide();
    }

    if (!isShopeeMarketplace(marketplace)) {
        courierRow.hide();
        courierSelect.val('');
    }

    var isTiktok = isTiktokMarketplace(marketplace);
    var isLazada = isLazadaMarketplace(marketplace);

    if (isTiktok) {
        $('#tiktok-shipping-params').show();
        var lastTiktokState = autoDocCheckbox.data('lastTiktokState');
        autoDocCheckbox.prop('checked', typeof lastTiktokState === 'boolean' ? lastTiktokState : true);
    } else {
        $('#tiktok-shipping-params').hide();
        resetTiktokShippingParams();
    }

    autoDocContainer.toggle(isTiktok || isLazada);
    if (isLazada && !isTiktok) {
        autoDocCheckbox.prop('checked', true);
    }
    if (!isTiktok && !isLazada) {
        autoDocCheckbox.prop('checked', false);
    }
});

// Load Shopee shipping parameters ketika shop dipilih
$('#shop-select').change(function() {
    var marketplace = $('#marketplace-select').val() || '';
    var shopId = $(this).val();
    var selectedOption = $(this).find('option:selected');
    var courierSetup = configureCourierOptions(marketplace, selectedOption);
    var transactionIdsForShop = courierSetup.transactionIds.length ? courierSetup.transactionIds : parseShopTransactionIds(selectedOption);
    if (!courierSetup.transactionIds.length) {
        setIdSelectedValue(transactionIdsForShop);
    }
    var orderIdsForShop = parseShopOrders(selectedOption);
    var firstTransactionId = orderIdsForShop.length ? orderIdsForShop[0] : null;
    
    if (isShopeeMarketplace(marketplace) && shopId && transactionIdsForShop.length) {
        var availableCouriers = Object.keys(courierSetup.shippingMap || {});
        var shippingMethod = courierSetup.selectedCourier;
        if (!shippingMethod && availableCouriers.length === 1) {
            shippingMethod = availableCouriers[0];
        }

        if (shippingMethod || availableCouriers.length <= 1) {
            loadShopeeShippingParams(shopId, transactionIdsForShop.join(','), shippingMethod);
        } else {
            $('#pickup-address-select').html('<option value="">Pilih kurir terlebih dahulu</option>');
            $('#pickup-time-select').html('<option value="">Pilih kurir terlebih dahulu</option>');
        }
    } else if (isTiktokMarketplace(marketplace) && shopId && transactionIdsForShop.length) {
        loadTiktokShippingParams(shopId, firstTransactionId);
    } else {
        resetTiktokShippingParams();
    }

    if (isLazadaMarketplace(marketplace) && transactionIdsForShop.length) {
        setIdSelectedValue(transactionIdsForShop);
    }
});

function parseShopOrders(optionElement) {
    if (!optionElement || optionElement.length === 0) {
        return [];
    }

    var ordersRaw = optionElement.attr('data-orders');
    if (!ordersRaw) {
        return [];
    }

    try {
        return JSON.parse(ordersRaw);
    } catch (error) {
        console.error('Gagal parsing data orders:', error);
        return [];
    }
}

function parseShopTransactionIds(optionElement) {
    if (!optionElement || optionElement.length === 0) {
        return [];
    }

    var transactionIdsRaw = optionElement.attr('data-transaction-ids');
    if (!transactionIdsRaw) {
        return [];
    }

    try {
        return JSON.parse(transactionIdsRaw);
    } catch (error) {
        console.error('Gagal parsing data transaksi:', error);
        return [];
    }
}

function parseShopShippingMap(optionElement) {
    if (!optionElement || optionElement.length === 0) {
        return {};
    }

    var shippingMapRaw = optionElement.attr('data-shipping-map');
    if (!shippingMapRaw) {
        return {};
    }

    try {
        return JSON.parse(shippingMapRaw) || {};
    } catch (error) {
        console.error('Gagal parsing data shipping map:', error);
        return {};
    }
}

function setIdSelectedValue(transactionIds) {
    var ids = Array.isArray(transactionIds) ? transactionIds.filter(Boolean) : [];
    idSelectedInput.val(ids.join(','));
}

function getTransactionIdsForCourier(shippingMap, courier) {
    if (!courier || !shippingMap || typeof shippingMap !== 'object') {
        return [];
    }
    var courierData = shippingMap[courier];
    if (!courierData || !Array.isArray(courierData.transaction_ids)) {
        return [];
    }
    return courierData.transaction_ids;
}

function configureCourierOptions(marketplace, optionElement) {
    var shippingMap = parseShopShippingMap(optionElement);
    var transactionIds = parseShopTransactionIds(optionElement);
    var courierKeys = Object.keys(shippingMap || {});

    courierSelect.html('<option value="">Pilih Kurir</option>');
    courierSelect.val('');
    courierRow.hide();

    if (isShopeeMarketplace(marketplace) && courierKeys.length > 0) {
        courierKeys.forEach(function(key) {
            courierSelect.append(
                $('<option>', {
                    value: key,
                    text: key
                })
            );
        });
        courierRow.show();

        if (courierKeys.length === 1) {
            courierSelect.val(courierKeys[0]);
            transactionIds = getTransactionIdsForCourier(shippingMap, courierKeys[0]);
        } else {
            transactionIds = [];
        }
    }

    setIdSelectedValue(transactionIds);

    return {
        shippingMap: shippingMap,
        selectedCourier: courierSelect.val(),
        transactionIds: transactionIds
    };
}

function loadShopeeShippingParams(shopId, transactionIds, shippingMethod) {
    $('#pickup-address-select').html('<option value="">Loading...</option>');
    $('#pickup-time-select').html('<option value="">Pilih alamat terlebih dahulu</option>');
    
    $.ajax({
        url: '<?= base_url() ?>api_v2/shopee_get_mass_shipping_parameter',
        type: 'POST',
        data: {
            shop_id: shopId,
            transaction_ids: transactionIds,
            shipping_method: shippingMethod || ''
        },
        dataType: 'json',
        success: function(response) {
            if (response.status) {
                var addressSelect = $('#pickup-address-select');
                addressSelect.html('<option value="">Pilih Alamat Pickup</option>');
                
                if (response.pickup_info.address_list && response.pickup_info.address_list.length > 0) {
                    response.pickup_info.address_list.forEach(function(address) {
                        addressSelect.append(
                            $('<option>', {
                                value: address.address_id,
                                text: address.address,
                                'data-time-slots': JSON.stringify(response.pickup_info.time_slot_list || [])
                            })
                        );
                    });
                } else {
                    addressSelect.html('<option value="">Tidak ada alamat tersedia</option>');
                }
                
                $('#pickup-time-select').html('<option value="">Pilih alamat terlebih dahulu</option>');
            } else {
                $('#pickup-address-select').html('<option value="">' + escapeHtml(response.message) + '</option>');
            }
        },
        error: function(xhr) {
            $('#pickup-address-select').html('<option value="">Error loading parameters</option>');
            console.error('Error loading Shopee parameters:', xhr);
        }
    });
}

function resetTiktokShippingParams(message) {
    var tiktokSelect = $('#tiktok-pickup-time-select');
    var placeholder = message || 'Pilih toko untuk memuat waktu pickup';
    tiktokSelect.html('<option value="">' + placeholder + '</option>');
    $('#tiktok-pickup-start-time').val('');
    $('#tiktok-pickup-end-time').val('');
}

function loadTiktokShippingParams(shopId, transactionId) {
    var tiktokSelect = $('#tiktok-pickup-time-select');
    tiktokSelect.html('<option value="">Loading...</option>');
    $('#tiktok-pickup-start-time').val('');
    $('#tiktok-pickup-end-time').val('');

    if (!transactionId) {
        resetTiktokShippingParams('Tidak ada transaksi valid untuk toko ini');
        return;
    }

    $.ajax({
        url: '<?= base_url() ?>api_v2/get_handover_time_slots_by_package',
        type: 'POST',
        data: {
            shop_id: shopId,
            transaction_id: transactionId
        },
        dataType: 'json',
        success: function(response) {
            if (response.status && response.data && Array.isArray(response.data.pickup_slots)) {
                var slots = response.data.pickup_slots.filter(function(slot) {
                    return slot.avaliable === true || slot.available === true;
                });

                if (!slots.length) {
                    resetTiktokShippingParams('Tidak ada slot pickup tersedia');
                    return;
                }

                moment.locale('id');
                tiktokSelect.html('<option value="">Pilih Waktu Pickup</option>');
                slots.forEach(function(slot, index) {
                    var startTime = slot.start_time || slot.startTime;
                    var endTime = slot.end_time || slot.endTime;
                    var startText = startTime ? moment.unix(startTime).format('dddd, D MMMM YYYY HH:mm') : '-';
                    var endText = endTime ? moment.unix(endTime).format('HH:mm') : '-';
                    var label = startText + ' - ' + endText;

                    tiktokSelect.append(
                        $('<option>', {
                            value: startTime && endTime ? startTime + ':' + endTime : 'slot_' + index,
                            text: label,
                            'data-start': startTime || '',
                            'data-end': endTime || ''
                        })
                    );
                });
            } else {
                var errorMessage = response.message || 'Gagal memuat slot pickup';
                resetTiktokShippingParams(errorMessage);
            }
        },
        error: function(xhr) {
            console.error('Error loading TikTok parameters:', xhr);
            resetTiktokShippingParams('Error memuat slot pickup');
        }
    });
}

function parseAjaxErrorMessage(xhr, fallbackMessage) {
    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
        return xhr.responseJSON.message;
    }

    if (xhr && xhr.responseText) {
        try {
            var parsed = JSON.parse(xhr.responseText);
            if (parsed.message) {
                return parsed.message;
            }
        } catch (error) {
            return fallbackMessage;
        }
    }

    return fallbackMessage;
}

function openPrintForm(actionUrl, payloadValue) {
    if (!payloadValue) {
        return;
    }
    var printForm = document.createElement('form');
    printForm.method = 'POST';
    printForm.action = actionUrl;
    printForm.target = '_blank';

    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'payload';
    input.value = payloadValue;

    printForm.appendChild(input);
    document.body.appendChild(printForm);
    printForm.submit();
    document.body.removeChild(printForm);
}

function appendAdditionalMessage(form, html) {
    if (!html) {
        return;
    }
    var container = form.find(".form-message");
    var existingHtml = container.html() || '';
    container.hide().html(existingHtml + html).slideDown("fast");
}

function handleShippingDocumentsResponse(response, form) {
    var isSuccess = !!response.status;
    var message = response.message || (isSuccess ? 'Dokumen pengiriman berhasil diproses' : 'Proses dokumen gagal');

    if (response.open_print && Array.isArray(response.print_payload) && response.print_payload.length) {
        openPrintForm(PRINT_SHIPPING_DOCS_URL, JSON.stringify({
            status: true,
            results: response.print_payload
        }));
    }

    if (response.open_print_shopee && Array.isArray(response.shopee_print_payload) && response.shopee_print_payload.length) {
        openPrintForm(PRINT_SHIPPING_DOCS_SHOPEE_URL, JSON.stringify(response.shopee_print_payload));
    }

    if (Array.isArray(response.download_links) && response.download_links.length) {
        var linksHtml = '<div class="mt-3"><strong>Link Download Dokumen:</strong><ul class="mb-0">';
        response.download_links.forEach(function(url) {
            var safeUrl = escapeHtml(url);
            linksHtml += '<li><a href="' + safeUrl + '" target="_blank" rel="noopener">' + safeUrl + '</a></li>';
        });
        linksHtml += '</ul></div>';
        appendAdditionalMessage(form, linksHtml);
    }

    if (Array.isArray(response.notes) && response.notes.length) {
        var notesHtml = '<div class="mt-3"><strong>Catatan Dokumen:</strong><ul class="mb-0">';
        response.notes.forEach(function(note) {
            notesHtml += '<li>' + escapeHtml(note) + '</li>';
        });
        notesHtml += '</ul></div>';
        appendAdditionalMessage(form, notesHtml);
    }

    Swal.fire({
        icon: isSuccess ? 'success' : 'error',
        title: isSuccess ? 'Dokumen Berhasil' : 'Dokumen Gagal',
        text: message,
        confirmButtonText: 'OK'
    });
}

function showAutoDocumentLoading(successCount, failedCount, totalShops) {
    var summaryParts = [];
    if (totalShops > 0) {
        summaryParts.push('Pengiriman berhasil untuk ' + successCount + ' dari ' + totalShops + ' toko.');
    } else if (successCount > 0) {
        summaryParts.push('Pengiriman berhasil diproses.');
    }
    if (failedCount > 0) {
        summaryParts.push(failedCount + ' toko gagal diproses.');
    }
    var summaryText = summaryParts.join(' ') || 'Pengiriman berhasil diproses.';
    var html = escapeHtml(summaryText) + '<br>Mohon tunggu, dokumen pengiriman sedang disiapkan...';

    Swal.fire({
        title: 'Menyiapkan Dokumen',
        html: html,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: function() {
            Swal.showLoading();
        }
    });
}

function autoGenerateShippingDocuments(form, idSelected) {
    if (!idSelected) {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Dokumen Gagal',
            text: 'Tidak ditemukan data transaksi untuk dokumen pengiriman.',
            confirmButtonText: 'OK'
        });
        return $.Deferred().reject().promise();
    }

    return $.ajax({
        type: 'POST',
        url: SHIPPING_DOCUMENTS_EXECUTE_URL,
        data: { id_selected: idSelected },
        dataType: 'json'
    }).done(function(response) {
        Swal.close();
        handleShippingDocumentsResponse(response, form);
    }).fail(function(xhr) {
        Swal.close();
        var message = parseAjaxErrorMessage(xhr, 'Pengiriman berhasil, namun dokumen gagal diproses.');
        Swal.fire({
            icon: 'error',
            title: 'Dokumen Gagal',
            text: message,
            confirmButtonText: 'OK'
        });
    });
}

autoDocCheckbox.change(function() {
    if (autoDocContainer.is(':visible') && isTiktokMarketplace($('#marketplace-select').val())) {
        $(this).data('lastTiktokState', $(this).is(':checked'));
    }
});

$('#pickup-address-select').change(function() {
    var selectedOption = $(this).find('option:selected');
    var timeSlots = selectedOption.data('time-slots');
    var timeSelect = $('#pickup-time-select');
    
    timeSelect.html('<option value="">Pilih Waktu Pickup</option>');
    
    if (timeSlots && timeSlots.length > 0) {
        timeSlots.forEach(function(timeSlot) {
            moment.locale('id');
            var timeText = moment.unix(timeSlot.date).format('dddd, D MMMM YYYY') + ' ' + timeSlot.time_text || moment.unix(timeSlot.date).format('dddd, D MMMM YYYY HH:mm');
            timeSelect.append(
                $('<option>', {
                    value: timeSlot.pickup_time_id,
                    text: timeText 
                })
            );
        });
    } else {
        timeSelect.html('<option value="">Tidak ada waktu tersedia</option>');
    }
});

$('#tiktok-pickup-time-select').change(function() {
    var selectedOption = $(this).find('option:selected');
    $('#tiktok-pickup-start-time').val(selectedOption.data('start') || '');
    $('#tiktok-pickup-end-time').val(selectedOption.data('end') || '');
});

courierSelect.change(function() {
    var marketplace = $('#marketplace-select').val() || '';
    var shopId = $('#shop-select').val();
    var selectedOption = $('#shop-select').find('option:selected');
    var shippingMap = parseShopShippingMap(selectedOption);
    var courier = $(this).val();
    var transactionIds = getTransactionIdsForCourier(shippingMap, courier);

    setIdSelectedValue(transactionIds);

    if (isShopeeMarketplace(marketplace) && shopId && courier && transactionIds.length) {
        loadShopeeShippingParams(shopId, transactionIds.join(','), courier);
    }
});

$("#form-shipping-process").submit(function() {
    var form = $(this);
    var mydata = new FormData(this);
    
    var marketplace = $('#marketplace-select').val() || '';
    var shippingMethodSelected = courierRow.is(':visible') ? courierSelect.val() : '';

    if (isShopeeMarketplace(marketplace) && courierRow.is(':visible') && !shippingMethodSelected) {
        Swal.fire({
            icon: 'warning',
            title: 'Kurir Belum Dipilih',
            text: 'Pilih kurir yang akan diproses untuk toko ini',
            confirmButtonText: 'OK'
        });
        return false;
    }

    if (isShopeeMarketplace(marketplace)) {
        var addressId = $('select[name="pickup_address_id"]').val();
        var timeId = $('select[name="pickup_time_id"]').val();
        
        if (!addressId || !timeId) {
            Swal.fire({
                icon: 'warning',
                title: 'Data Tidak Lengkap',
                text: 'Untuk marketplace Shopee, pilih alamat dan waktu pickup terlebih dahulu',
                confirmButtonText: 'OK'
            });
            return false;
        }
    } else if (isTiktokMarketplace(marketplace)) {
        var tiktokSlot = $('select[name="tiktok_pickup_slot"]').val();
        var tiktokStart = $('#tiktok-pickup-start-time').val();
        var tiktokEnd = $('#tiktok-pickup-end-time').val();
        
        if (!tiktokSlot || !tiktokStart || !tiktokEnd) {
            Swal.fire({
                icon: 'warning',
                title: 'Data Tidak Lengkap',
                text: 'Untuk marketplace TikTok, pilih waktu pickup terlebih dahulu',
                confirmButtonText: 'OK'
            });
            return false;
        }
    }

    $.ajax({
        type: "POST",
        url: form.attr("action"),
        data: mydata,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        beforeSend: function() {
            $(".btn-send").addClass("disabled")
                .html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>')
                .attr('disabled', true);
            form.find(".form-message").slideUp().html("");
        },
        success: function(response) {
            var autoGenerateDocs = autoDocContainer.is(':visible') && autoDocCheckbox.is(':checked');
            var selectedOption = $('#shop-select').find('option:selected');
            var shippingMap = parseShopShippingMap(selectedOption);
            var selectedCourier = courierSelect.val();
            var transactionIds = selectedCourier ? getTransactionIdsForCourier(shippingMap, selectedCourier) : parseShopTransactionIds(selectedOption);
            var idSelectedValue = transactionIds.length ? transactionIds.join(',') : form.find('input[name="id_selected"]').val();
            var enableFormButton = function() {
                $(".btn-send").removeClass("disabled").html('Proses Pengiriman').attr('disabled', false);
            };

            var isSuccess = !!response.status;
            var successCount = response.summary?.success || 0;
            var totalShops = response.summary?.total_shops || 0;
            var failedCount = response.summary?.failed || 0;

            if (Array.isArray(response.notes) && response.notes.length) {
                var notesHtml = '<ul class="mb-0 mt-2">';
                response.notes.forEach(function(note) {
                    notesHtml += '<li>' + escapeHtml(note) + '</li>';
                });
                notesHtml += '</ul>';
                form.find(".form-message").hide().html(notesHtml).slideDown("fast");
            }

            if (isSuccess && autoGenerateDocs) {
                showAutoDocumentLoading(successCount, failedCount, totalShops);
                autoGenerateShippingDocuments(form, idSelectedValue).always(function() {
                    enableFormButton();
                });
                return;
            }

            enableFormButton();

            if (isSuccess) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Diproses',
                    html: `Order berhasil diproses untuk ${successCount} toko. Lanjutkan untuk toko lain?`,
                    showCancelButton: true,
                    confirmButtonText: 'Lanjutkan',
                    cancelButtonText: 'Tidak',
                    reverseButtons: true
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    html: response.message || 'Proses pengiriman gagal',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function(xhr) {
            $(".btn-send").removeClass("disabled").html('Proses Pengiriman').attr('disabled', false);
            var responseText = xhr.responseText || 'Terjadi kesalahan.';
            try {
                var parsed = JSON.parse(responseText);
                responseText = parsed.message || 'Terjadi kesalahan.';
            } catch (e) {
                responseText = 'Terjadi kesalahan.';
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: responseText,
                confirmButtonText: 'OK'
            });
        }
    });
    return false;
});
</script>
