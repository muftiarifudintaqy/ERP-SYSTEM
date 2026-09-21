<div class="form-message"></div>
<form action="<?= base_url() ?>booking_fbs/shipping_process_execute" method="POST" id="form-shipping-process">
    <input type="hidden" name="id_selected" value="<?= $id_selected ?>">
    
    <p>Proses pengiriman untuk data terpilih?</p>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label>Toko</label>
                <select name="shop_id" class="form-control" required id="shop-select">
                    <option value="">Pilih Toko</option>
                    <?php foreach ($shops as $shopId => $shopData): ?>
                        <?php
                            $ordersJson = htmlspecialchars(
                                json_encode(array_values($shopData['orders'] ?? [])),
                                ENT_NOQUOTES,
                                'UTF-8'
                            );
                            $selectedIdsJson = htmlspecialchars(
                                json_encode(array_values($shopData['selected_ids'] ?? [])),
                                ENT_NOQUOTES,
                                'UTF-8'
                            );
                            $orderCount = count($shopData['orders'] ?? []);
                        ?>
                        <option value="<?= $shopId ?>" data-orders='<?= $ordersJson ?>' data-selected-ids='<?= $selectedIdsJson ?>'>
                            <?= htmlspecialchars($shopData['shop_name']) ?> (<?= $orderCount ?> orders)
                        </option>
                    <?php endforeach; ?>
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

// Load Shopee shipping parameters ketika shop dipilih
$('#shop-select').change(function() {
    var shopId = $(this).val();
    var selectedOption = $(this).find('option:selected');
    var bookingIds = parseShopOrders(selectedOption);
    var firstBookingId = bookingIds.length ? bookingIds[0] : null;

    if (shopId) {
        $('#shopee-shipping-params').slideDown();
    } else {
        $('#shopee-shipping-params').slideUp();
        return;
    }

    if (!firstBookingId) {
        $('#pickup-address-select').html('<option value="">Tidak ada booking valid untuk toko ini</option>');
        $('#pickup-time-select').html('<option value="">Tidak ada booking valid untuk toko ini</option>');
        return;
    }

    loadShopeeShippingParams(shopId, firstBookingId);

});


function parseShopOrders(optionElement) {
    if (!optionElement || optionElement.length === 0) {
        return [];
    }

    var idsRaw = optionElement.attr('data-selected-ids');
    if (!idsRaw) {
        return [];
    }

    try {
        var parsed = JSON.parse(idsRaw);
        if (!Array.isArray(parsed)) {
            return [];
        }
        return parsed.map(function(id) {
            return String(id || '').trim();
        }).filter(Boolean);
    } catch (error) {
        console.error('Gagal parsing data selected ids:', error);
        return [];
    }
}

function loadShopeeShippingParams(shopId, bookingId) {
    if (!bookingId) {
        return;
    }
    $('#pickup-address-select').html('<option value="">Loading...</option>');
    $('#pickup-time-select').html('<option value="">Pilih alamat terlebih dahulu</option>');
    
    $.ajax({
        url: '<?= base_url() ?>api_v2/shopee_get_booking_shipping_parameter',
        type: 'POST',
        data: {
            shop_id: shopId,
            booking_id: bookingId
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

$("#form-shipping-process").submit(function() {
    var form = $(this);
    var mydata = new FormData(this);
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
            $(".btn-send").removeClass("disabled").html('Proses Pengiriman').attr('disabled', false);

            var isSuccess = !!response.status;
            var successCount = response.summary?.orders_success || 0; 
            var totalOrders = response.summary?.orders_total || 0;    
            var failedCount = response.summary?.orders_failed || 0;   
            var totalShops = response.summary?.total_shops || 0;    

            if (isSuccess) {
                if (totalShops > 1 && successCount < totalShops) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Diproses',
                        html: `Order berhasil diproses untuk ${successCount} toko. ${failedCount > 0 ? `${failedCount} toko gagal. ` : ''}Lanjutkan untuk toko lain?`,
                        showCancelButton: true,
                        confirmButtonText: 'Lanjutkan',
                        cancelButtonText: 'Tidak',
                        reverseButtons: true
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        html: response.message || 
                            `Berhasil memproses ${successCount} dari ${totalOrders} order` +
                            (failedCount > 0 ? `<br><small>${failedCount} order gagal</small>` : ''),
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    html: response.message || `Gagal memproses order. ${successCount} berhasil, ${failedCount} gagal.`,
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
