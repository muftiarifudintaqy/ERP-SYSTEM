<div class="form-message"></div>
<form action="<?= base_url() ?>/product/update" method="POST" id="form-modal" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $data['id'] ?>">
    <input type="hidden" name="dt[is_operational]" value="<?= $data['is_operational'] ?>">
    
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="">Gift</label>
                <br>
                <input <?= $data['is_gift'] == 1 ? 'checked' : '' ?> name="dt[is_gift]" type="radio" id="gift-1" value="1"> <label for="gift-1">Ya</label>
                <input <?= $data['is_gift'] == 0 ? 'checked' : '' ?> name="dt[is_gift]" type="radio" id="gift-2" value="0"> <label for="gift-2">Tidak</label>
            </div>
            <div class="form-group">
                <div class="form-check form-switch d-inline-block">
                    <input type="hidden" name="dt[is_varian]" value="0">
                    <input class="form-check-input" type="checkbox" id="varian_switch" name="dt[is_varian]" 
                        value="1" <?= $data['is_varian'] ? 'checked' : '' ?> onchange="toggleVarianForm(this)">
                    <label for="varian_switch">Produk Varian</label>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Nama Produk</label>
                <input type="text" class="form-control" name="dt[name]" value="<?= $data['name'] ?>" >
            </div>
            
            <div class="form-group">
                <label for="">SKU Induk</label>
                <input type="text" class="form-control" name="dt[sku]" value="<?= $data['sku'] ?>" >
            </div>
            
            <div class="form-group">
                <label for="">Brand</label>
                <select class="form-control" name="dt[brand]" >
                    <?php foreach ($brand as $v2): ?>
                        <option value="<?= $v2['code'] ?>" <?= $data['brand'] == $v2['code'] ? 'selected' : '' ?>>
                            <?= $v2['code'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Form Produk Biasa -->
            <div id="regular-product-form" style="display: <?= $data['is_varian'] ? 'none' : 'block' ?>;">
                <div class="form-group">
                    <label for="">Berat (gr)</label>
                    <input type="number" class="form-control" name="dt[weight]" 
                        value="<?= $data['weight'] ?>" <?= $data['is_varian'] ? 'disabled' : '' ?> >
                </div>
                
                <div class="form-group">
                    <label for="">HPP</label>
                    <input type="text" class="form-control" name="dt[price_buy]" 
                        value="<?= $data['price_buy'] ?>" <?= $data['is_varian'] ? 'disabled' : '' ?> >
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- Harga untuk produk biasa -->
            <div id="regular-price-form" style="display: <?= $data['is_varian'] ? 'none' : 'block' ?>;">
                <div class="form-group">
                    <label>Harga</label>
                    <div class="price-input-group mb-2">
                        <label class="price-label">Pelanggan</label>
                        <input type="text" class="form-control text-end" name="dt[price_normal]" 
                            value="<?= $data['price_normal'] ?>" <?= $data['is_varian'] ? 'disabled' : '' ?> >
                    </div>
                    <div class="price-input-group mb-2">
                        <label class="price-label">Reseller</label>
                        <input type="text" class="form-control text-end" name="dt[price_reseller]" 
                            value="<?= $data['price_reseller'] ?>" <?= $data['is_varian'] ? 'disabled' : '' ?> >
                    </div>
                    <div class="price-input-group">
                        <label class="price-label">Distributor</label>
                        <input type="text" class="form-control text-end" name="dt[price_distributor]" 
                            value="<?= $data['price_distributor'] ?>" <?= $data['is_varian'] ? 'disabled' : '' ?> >
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="">Keterangan</label>
                <textarea class="form-control" name="dt[desc]"><?= $data['desc'] ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="">Gambar Produk</label>
                <div class="d-flex align-items-start gap-3 flex-wrap">
                    <?php if ($data['img']): ?>
                        <div>
                            <img src="<?= base_url() ?>/assets/img/product/<?= $data['img'] . '?token=' . DATE("Ymdhis", strtotime($data['updated_at'])) ?>" 
                                alt="Gambar Produk" style="max-width: 150px; max-height: 150px;">
                            
                        </div>
                    <?php endif; ?>

                    <div style="min-width: 200px;">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_main_image" id="remove_main_image">
                            <label class="form-check-label" for="remove_main_image">Hapus gambar ini</label>
                        </div>
                        <input type="file" class="form-control" name="file" accept="image/png, image/jpeg, image/jpg">
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Form Varian -->
    <div id="varian-form" style="display: <?= $data['is_varian'] ? 'block' : 'none' ?>;">
        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x: auto;">
                <table class="table table-bordered table-striped" style="min-width: 1120px; white-space: nowrap;">
                    <thead>
                        <tr class="bg-blue-2">
                            <th class="text-center" style="min-width: 200px;">Nama Varian</th>
                            <th class="text-center">SKU Varian</th>
                            <th class="text-center">Stok</th>
                            <th class="text-center">Berat (gr)</th>
                            <th class="text-center">HPP Varian</th>
                            <th class="text-center">Harga</th>
                            <th class="text-center">Gambar Varian</th>
                            <th class="text-center" style="width: 80px;">
                                <button type="button" class="btn btn-sm btn-primary" onclick="addVarianRow()">
                                    <i class="fa fa-plus"></i> Tambah
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="varian-tbody">
                        <?php if (!empty($data['variants'])): ?>
                            <?php foreach ($data['variants'] as $index => $variant): ?>
                                <tr id="varian-row-<?= $index ?>">
                                    <td style="min-width: 200px;">
                                        <input class="form-control" name="variants[<?= $index ?>][name]" 
                                            value="<?= $variant['name'] ?>" >
                                        <input type="hidden" name="variants[<?= $index ?>][id]" value="<?= $variant['id'] ?>">
                                        <input type="hidden" name="variants[<?= $index ?>][current_stock]" value="<?= htmlspecialchars((string)($variant['stock'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
                                    </td>
                                    <td>
                                        <input class="form-control" name="variants[<?= $index ?>][sku]" 
                                            value="<?= $variant['sku'] ?>">
                                    </td>
                                    <td class="text-center align-top">
                                        <span class="badge bg-secondary migration-stock-preview"><?= number_format((float)($variant['stock'] ?? 0), 0, ',', '.') ?></span>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control text-end" 
                                            name="variants[<?= $index ?>][weight]" 
                                            value="<?= $variant['weight'] ?>">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control text-end" 
                                            name="variants[<?= $index ?>][price_buy]" 
                                            value="<?= $variant['price_buy'] ?>">
                                    </td>
                                    <td>
                                        <div class="dropdown variant-price-dropdown">
                                            <button class="btn btn-light w-100 dropdown-toggle text-start text-truncate variant-price-toggle" type="button" aria-expanded="false">
                                                Isi Harga Varian
                                            </button>
                                            <div class="dropdown-menu p-3 variant-price-menu" style="min-width: 250px;">
                                                <div class="price-input-group mb-2">
                                                    <label class="price-label">Pelanggan</label>
                                                    <input type="text" class="form-control format-price" name="variants[<?= $index ?>][price_normal]" value="<?= $variant['price_normal'] ?>">
                                                </div>
                                                <div class="price-input-group mb-2">
                                                    <label class="price-label">Reseller</label>
                                                    <input type="text" class="form-control format-price" name="variants[<?= $index ?>][price_reseller]" value="<?= $variant['price_reseller'] ?>">
                                                </div>
                                                <div class="price-input-group">
                                                    <label class="price-label">Distributor</label>
                                                    <input type="text" class="form-control format-price" name="variants[<?= $index ?>][price_distributor]" value="<?= $variant['price_distributor'] ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($variant['img'])): ?>
                                            <div class="mb-2">
                                                <img src="<?= base_url() ?>/assets/img/product/<?= $variant['img'] ?>" 
                                                    alt="Gambar Varian" style="max-width: 50px; max-height: 50px;">
                                                <input type="hidden" name="variants[<?= $index ?>][existing_img]" 
                                                    value="<?= $variant['img'] ?>">
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" 
                                                        name="variants[<?= $index ?>][remove_img]" 
                                                        id="remove_img_<?= $index ?>">
                                                    <label class="form-check-label" for="remove_img_<?= $index ?>">Hapus</label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control form-control-sm" 
                                            name="variant_img_<?= $index ?>" accept="image/*">
                                        <small class="text-muted">Max 2MB</small>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="removeVarianRow(<?= $index ?>, <?= $variant['id'] ?? 'null' ?>)">
                                            <i class="fa fa-trash"></i> Hapus
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="varian-row-0">
                                <td style="min-width: 200px;">
                                    <input class="form-control" name="variants[0][name]">
                                    <input type="hidden" name="variants[0][current_stock]" value="0">
                                </td>
                                <td>
                                    <input class="form-control" name="variants[0][sku]">
                                </td>
                                <td class="text-center align-top">
                                    <span class="badge bg-secondary migration-stock-preview">0</span>
                                </td>
                                <td>
                                    <input type="number" class="form-control text-end" name="variants[0][weight]">
                                </td>
                                <td>
                                    <input type="text" class="form-control text-end" name="variants[0][price_buy]">
                                </td>
                                <td>
                                    <div class="dropdown variant-price-dropdown">
                                        <button class="btn btn-light w-100 dropdown-toggle text-start text-truncate variant-price-toggle" type="button" aria-expanded="false">
                                            Isi Harga Varian
                                        </button>
                                        <div class="dropdown-menu p-3 variant-price-menu" style="min-width: 250px;">
                                            <div class="price-input-group mb-2">
                                                <label class="price-label">Pelanggan</label>
                                                <input type="text" class="form-control text-end format-price" name="variants[0][price_normal]">
                                            </div>
                                            <div class="price-input-group mb-2">
                                                <label class="price-label">Reseller</label>
                                                <input type="text" class="form-control text-end format-price" name="variants[0][price_reseller]">
                                            </div>
                                            <div class="price-input-group">
                                                <label class="price-label">Distributor</label>
                                                <input type="text" class="form-control text-end format-price" name="variants[0][price_distributor]">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <input type="file" class="form-control form-control-sm" 
                                        name="variant_img_0" accept="image/*">
                                    <small class="text-muted">Max 2MB</small>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="removeVarianRow(0)">
                                        <i class="fa fa-trash"></i> Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-2 text-muted">
                <small>Geser tabel ke kanan/kiri jika tidak semua kolom terlihat</small>
                <small class="d-block">Saat konversi single ke varian, SKU yang sama dengan SKU produk lama akan mewarisi stok histori produk tersebut.</small>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-12 text-end">
            <button type="submit" class="btn btn-primary btn-send">Simpan Perubahan</button>
        </div>
    </div>
</form>

<script>
    var varianIndex = <?= !empty($data['variants']) ? count($data['variants']) : 1 ?>;
    var deletedVariants = [];
    const migrationSourceSku = <?= json_encode((string)($data['sku'] ?? '')) ?>;
    const migrationSourceStock = <?= json_encode((string)($data['stock'] ?? '0')) ?>;
    const canPreviewMigration = <?= (int)!($data['is_varian'] ?? 0) ?> === 1;
    
    function toggleVarianForm(checkbox) {
        const isVarian = checkbox.checked;
        
        document.getElementById('varian-form').style.display = isVarian ? 'block' : 'none';
        document.getElementById('regular-product-form').style.display = isVarian ? 'none' : 'block';
        document.getElementById('regular-price-form').style.display = isVarian ? 'none' : 'block';
        
        const regularInputs = document.querySelectorAll('#regular-product-form input, #regular-price-form input');
        regularInputs.forEach(input => {
            input.disabled = isVarian;
        });

        updateMigrationStockPreview();
    }

    function formatStockPreview(value) {
        const numericValue = parseFloat(value || 0);
        if (Number.isNaN(numericValue)) {
            return '0';
        }
        return new Intl.NumberFormat('id-ID').format(numericValue);
    }

    function positionVariantPriceMenu(toggleButton) {
        const dropdown = toggleButton.closest('.variant-price-dropdown');
        const menu = dropdown ? dropdown.querySelector('.variant-price-menu') : null;
        if (!menu) {
            return;
        }

        const rect = toggleButton.getBoundingClientRect();
        const menuWidth = Math.max(260, menu.offsetWidth || 260);
        const menuHeight = Math.max(180, menu.offsetHeight || 180);
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        let left = rect.left;
        let top = rect.bottom + 8;

        if (left + menuWidth > viewportWidth - 16) {
            left = Math.max(16, viewportWidth - menuWidth - 16);
        }

        if (top + menuHeight > viewportHeight - 16) {
            top = Math.max(16, rect.top - menuHeight - 8);
        }

        menu.style.position = 'fixed';
        menu.style.left = `${left}px`;
        menu.style.top = `${top}px`;
        menu.style.inset = 'auto auto auto auto';
        menu.style.transform = 'none';
        menu.style.zIndex = '2000';
    }

    function initVariantPriceDropdowns(scope = document) {
        scope.querySelectorAll('.variant-price-toggle').forEach(button => {
            if (button.dataset.variantDropdownBound === '1') {
                return;
            }

            button.dataset.variantDropdownBound = '1';
            button.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                const dropdown = this.closest('.variant-price-dropdown');
                const menu = dropdown ? dropdown.querySelector('.variant-price-menu') : null;
                const willOpen = menu && !menu.classList.contains('show');

                closeVariantPriceMenus();
                if (willOpen && menu) {
                    menu.classList.add('show');
                    this.setAttribute('aria-expanded', 'true');
                    positionVariantPriceMenu(this);
                }
            });
        });
    }

    function closeVariantPriceMenus() {
        document.querySelectorAll('.variant-price-menu.show').forEach(function(menu) {
            menu.classList.remove('show');
        });
        document.querySelectorAll('.variant-price-toggle[aria-expanded="true"]').forEach(function(button) {
            button.setAttribute('aria-expanded', 'false');
        });
    }

    function updateMigrationStockPreview() {
        const isVarian = document.getElementById('varian_switch').checked;
        const sourceSku = migrationSourceSku.trim().toUpperCase();
        const migrationPreviewValue = formatStockPreview(migrationSourceStock);

        document.querySelectorAll('#varian-tbody tr').forEach(row => {
            const skuInput = row.querySelector('input[name*="[sku]"]');
            const previewBadge = row.querySelector('.migration-stock-preview');
            const currentStockInput = row.querySelector('input[name*="[current_stock]"]');
            if (!skuInput || !previewBadge) {
                return;
            }

            const rowSku = skuInput.value.trim().toUpperCase();
            const isMatch = canPreviewMigration && isVarian && sourceSku !== '' && rowSku !== '' && rowSku === sourceSku;
            const basePreviewValue = formatStockPreview(currentStockInput ? currentStockInput.value : 0);
            const previewValue = isMatch ? migrationPreviewValue : basePreviewValue;

            previewBadge.textContent = previewValue;
            previewBadge.className = isMatch
                ? 'badge bg-success migration-stock-preview'
                : 'badge bg-secondary migration-stock-preview';
        });
    }
    
    function addVarianRow() {
        const tbody = document.getElementById('varian-tbody');
        const newRow = document.createElement('tr');
        newRow.id = 'varian-row-' + varianIndex;
        
        newRow.innerHTML = `
            <td style="min-width: 200px;">
                <input class="form-control" name="variants[${varianIndex}][name]">
                <input type="hidden" name="variants[${varianIndex}][current_stock]" value="0">
            </td>
            <td>
                <input class="form-control" name="variants[${varianIndex}][sku]">
            </td>
            <td class="text-center align-top">
                <span class="badge bg-secondary migration-stock-preview">0</span>
            </td>
            <td>
                <input type="number" class="form-control text-end" name="variants[${varianIndex}][weight]">
            </td>
            <td>
                <input type="text" class="form-control text-end" name="variants[${varianIndex}][price_buy]">
            </td>
            <td>
                <div class="dropdown variant-price-dropdown">
                    <button class="btn btn-light w-100 dropdown-toggle text-start text-truncate variant-price-toggle" type="button" aria-expanded="false">
                        Isi Harga Varian
                    </button>
                    <div class="dropdown-menu p-3 variant-price-menu" style="min-width: 250px;">
                        <div class="price-input-group mb-2">
                            <label class="price-label">Pelanggan</label>
                            <input type="text" class="form-control format-price" name="variants[${varianIndex}][price_normal]">
                        </div>
                        <div class="price-input-group mb-2">
                            <label class="price-label">Reseller</label>
                            <input type="text" class="form-control format-price" name="variants[${varianIndex}][price_reseller]">
                        </div>
                        <div class="price-input-group">
                            <label class="price-label">Distributor</label>
                            <input type="text" class="form-control format-price" name="variants[${varianIndex}][price_distributor]">
                        </div>
                    </div>
                </div>
            </td>

            <td>
                <input type="file" class="form-control form-control-sm" 
                    name="variant_img_${varianIndex}" accept="image/*">
                <small class="text-muted">Max 2MB</small>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeVarianRow(${varianIndex})">
                    <i class="fa fa-trash"></i> Hapus
                </button>
            </td>
        `;
        
        tbody.appendChild(newRow);
        const skuInput = newRow.querySelector('input[name*="[sku]"]');
        if (skuInput) {
            skuInput.addEventListener('input', updateMigrationStockPreview);
        }
        initVariantPriceDropdowns(newRow);
        updateMigrationStockPreview();
        varianIndex++;
    }
    
    function removeVarianRow(index, variantId = null) {
        const row = document.getElementById('varian-row-' + index);
        if (row) {
            if (confirm('Apakah Anda yakin ingin menghapus varian ini?')) {
                if (variantId) {
                    deletedVariants.push(variantId);
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'deleted_variants[]';
                    input.value = variantId;
                    document.getElementById('form-modal').appendChild(input);
                }
                
                row.remove();
                
                if (document.querySelectorAll('#varian-tbody tr').length === 0) {
                    addVarianRow();
                }

                updateMigrationStockPreview();
            }
        }
    }
</script>

<script type="text/javascript">
    function formatRibuan(input) {
        let value = input.value.replace(/\D/g, '');
        if (!value) {
            input.value = '';
            return;
        }
        // Simpan nilai asli di data attribute
        $(input).data('raw-value', value);
        
        // Tampilkan dengan format ribuan
        input.value = parseInt(value).toLocaleString('id-ID');
    }

    function inisialisasiFormatHarga() {
        const selector = 'input[name*="[price_"], input[name*="[price_buy]"], input[name="dt[price_buy]"], input[name="dt[price_normal]"], input[name="dt[price_reseller]"], input[name="dt[price_distributor]"]';

        $(selector).each(function () {
            $(this).off('keyup.formatRibuan').on('keyup.formatRibuan', function () {
                formatRibuan(this);
            });

            formatRibuan(this);
        });
    }

    function showFormError(message) {
        $(".form-message").hide().html(
            '<div class="alert alert-danger">' + message + '</div>'
        ).slideDown("fast");
    }

    function validateSkuNoWhitespace() {
        let invalid = false;
        const mainSkuInput = $('input[name="dt[sku]"]');
        const skuInputs = [mainSkuInput];

        if ($('#varian_switch').is(':checked')) {
            $('#varian-tbody input[name$="[sku]"]').each(function() {
                skuInputs.push($(this));
            });
        }

        skuInputs.forEach(function(input) {
            const value = (input.val() || '').toString();
            if (/\s/.test(value)) {
                invalid = true;
                input.addClass('is-invalid');
            }
        });

        if (invalid) {
            showFormError('SKU Induk dan SKU Varian tidak boleh mengandung spasi.');
            return false;
        }

        return true;
    }

    $(document).ready(function () {
        inisialisasiFormatHarga();
        document.querySelectorAll('#varian-tbody input[name*="[sku]"]').forEach(function(input) {
            input.addEventListener('input', updateMigrationStockPreview);
        });
        initVariantPriceDropdowns();
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.variant-price-dropdown')) {
                closeVariantPriceMenus();
            }
        });
        window.addEventListener('resize', function() {
            document.querySelectorAll('.variant-price-toggle[aria-expanded="true"]').forEach(function(button) {
                positionVariantPriceMenu(button);
            });
        });
        window.addEventListener('scroll', function() {
            document.querySelectorAll('.variant-price-toggle[aria-expanded="true"]').forEach(function(button) {
                positionVariantPriceMenu(button);
            });
        }, true);
        updateMigrationStockPreview();
    });

    $("#form-modal").submit(function() {
        var form = $(this);

        form.find('.is-invalid').removeClass('is-invalid');
        form.find(".form-message").slideUp().html("");

        if (!validateSkuNoWhitespace()) {
            return false;
        }

        const selector = 'input[name*="price"], input[name*="price_buy"]';
        form.find(selector).each(function () {
            this.value = this.value.replace(/\./g, '');
        });

        var mydata = new FormData(this); 

        if ($('#varian_switch').is(':checked')) {
            mydata.set('dt[is_varian]', '1');
        } else {
            mydata.set('dt[is_varian]', '0');
        }

        $.ajax({
            type: "POST",
            url: form.attr("action"),
            data: mydata,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $(".btn-send").addClass("disabled").html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "<?= base_url() ?>/product";
                    }, 2000);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('Simpan Perubahan').attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $(".btn-send").removeClass("disabled").html('Simpan Perubahan').attr('disabled', false);
                $(".form-message").hide().html(xhr.responseText).slideDown("fast");
            }
        });

        return false;
    });

</script>

<style>
    .price-input-group {
        margin-bottom: 10px;
    }
    .price-label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        font-size: 0.875rem;
    }
    .price-input-group .form-control {
        text-align: right;
    }
    .variant-price-dropdown {
        position: static;
    }
    .variant-price-menu {
        position: fixed !important;
        z-index: 2000 !important;
        width: 320px;
        max-width: calc(100vw - 32px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .variant-price-menu.show {
        display: block;
    }
</style>
