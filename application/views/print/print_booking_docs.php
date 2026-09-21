<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Print Shipping Documents - Shopee Booking</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  @page {
    size: 4in 6in;
    margin: 0;
  }

  html, body {
    margin: 0;
    padding: 0;
    background: #f7f7f7;
    font-family: Arial, sans-serif;
    color: #111;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    padding: 12px 16px;
    background: #ffffff;
    border-bottom: 1px solid #e0e0e0;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  .toolbar button,
  .toolbar a {
    background: #2e90ffff;
    color: #fff;
    border: none;
    padding: 10px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
  }

  .toolbar span {
    font-size: 14px;
    color: #444;
  }

  .toolbar input[type="text"] {
    min-width: 240px;
    max-width: 320px;
    padding: 10px 12px;
    border: 1px solid #cfd8dc;
    border-radius: 4px;
    font-size: 14px;
    color: #111;
    background: #fff;
  }

  .label-wrapper {
    padding: 16px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(4in, 1fr));
    gap: 16px;
    justify-content: center;
  }

  .sheet {
    box-sizing: border-box;
    width: 4in;
    height: 5.5in;
    padding: 0.15in 0.12in 0.10in 0.12in;
    background: #fff;
    display: flex;
    flex-direction: column;
    page-break-after: always;
    break-inside: avoid;
  }

  .sheet:last-of-type {
    page-break-after: auto;
  }

  .sheet.is-hidden {
    display: none;
  }

  .content {
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  .label-media {
    position: relative;
    width: 100%;
    min-height: 3.6in;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 0;
  }

  .label-img {
    width: 100%;
    height: auto;
    object-fit: contain;
    opacity: 0;
    transition: opacity 0.3s ease;
    border: 0;
    max-height: calc(6in - 0.15in - 0.10in - 1.1in);
  }

  .label-img.loaded {
    opacity: 1;
  }

  .label-skeleton {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 4px;
    background: linear-gradient(110deg, #ececec 8%, #f5f5f5 18%, #ececec 33%);
    background-size: 200% 100%;
    animation: loading 1.2s linear infinite;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #777;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    z-index: 1;
  }

  .label-skeleton.error {
    background: #fff0f0;
    animation: none;
    color: #b71c1c;
    border: 1px solid #f5b4b4;
  }

  .label-empty {
    width: 100%;
    min-height: 3.6in;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-size: 12px;
    border: 1px dashed #ccc;
    padding: 16px;
    text-align: center;
  }

  @keyframes loading {
    0% {
      background-position: 200% 0;
    }
    100% {
      background-position: -200% 0;
    }
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
    margin-top: 0.12in;
  }

  th, td {
    border: 0.2mm solid #111;
    padding: 0;
    text-align: left;
    word-break: break-word;
  }

  th {
    background: #f2f2f2;
  }

  .booking-info {
    font-size: 10pt;
    margin-bottom: 8px;
    padding: 4px;
    background: #f8f9fa;
    border-radius: 4px;
  }

  .booking-info strong {
    color: #333;
  }

  .empty-state {
    display: none;
    padding: 20px 16px;
    text-align: center;
    color: #666;
    font-size: 14px;
  }

  .empty-state.is-visible {
    display: block;
  }

  /* Intersection Observer fallback untuk browser lama */
  .label-img.visible {
    opacity: 1;
  }

  @media print {
    .toolbar {
      display: none !important;
    }

    .label-wrapper {
      padding: 0;
      grid-template-columns: 1fr !important;
      gap: 0 !important;
    }

    body {
      background: #fff;
    }

    .label-img {
      opacity: 1 !important;
    }

    .label-skeleton {
      display: none !important;
    }
  }

  @media (max-width: 768px) {
    .label-wrapper {
      padding: 12px;
      grid-template-columns: 1fr;
    }
    
    .sheet {
      width: 100%;
      max-width: 4in;
      margin: 0 auto;
    }
  }
</style>
</head>
<body>
<?php
$downloadLinks = $downloadLinks ?? [];
$assetBase     = rtrim($this->template->endpoint_url(), '/');

function render_items_table_shopee_booking($items) {
  if (!is_array($items) || empty($items)) {
    echo '<div style="color:#666;font-size:12px">Tidak ada detail produk.</div>';
    return;
  }

  echo '<table><thead><tr><th>SKU</th><th>Qty</th></tr></thead><tbody>';
  foreach ($items as $item) {
    $rawSku  = trim($item['sku'] ?? '');
    if ($rawSku === '' && isset($item['sku_parent'])) {
      $rawSku = $item['sku_parent'];
    }
    $fallbackQty = $item['qty'] ?? null;

    $parts = array_filter(
      preg_split('/\\s*\\+\\s*/', $rawSku),
      static fn($part) => trim((string)$part) !== ''
    );

    // Jika sku kosong, tetap tampilkan satu baris dengan qty fallback.
    if (empty($parts)) {
      $parts = [$rawSku];
    }

    foreach ($parts as $part) {
      $part = trim((string)$part);

      $matchedQty = null;
      $matchedSku = $part;
      if (preg_match('/^\\s*(\\d+)\\s*-\\s*(.+)$/', $part, $m)) {
        $matchedQty = (int)$m[1];
        $matchedSku = trim($m[2]);
      }

      $skuToShow = htmlspecialchars($matchedSku, ENT_QUOTES, 'UTF-8');
      $qtyValue  = $matchedQty ?? $fallbackQty ?? 1;
      $qtyToShow = htmlspecialchars((string)$qtyValue, ENT_QUOTES, 'UTF-8');

      echo '<tr><td>' . $skuToShow . '</td><td>' . $qtyToShow . '</td></tr>';
    }
  }
  echo '</tbody></table>';
}

function build_product_filter_text_shopee_booking($items) {
  if (!is_array($items) || empty($items)) {
    return '';
  }

  $names = [];
  foreach ($items as $item) {
    foreach (['name', 'item_name', 'name_parent', 'product_name', 'sku', 'sku_parent'] as $key) {
      $value = trim((string)($item[$key] ?? ''));
      if ($value !== '') {
        $names[] = $value;
      }
    }
  }

  return implode(' ', array_unique($names));
}

// Optimasi: Preload gambar pertama untuk setiap batch
$firstImages = [];
foreach ($labels as $index => $label) {
    $images = $label['label_images'] ?? [];
    if (!empty($images[0])) {
        $firstImage = $images[0];
        if (stripos($firstImage, 'http') !== 0) {
            $firstImage = $assetBase . '/' . ltrim($firstImage, '/');
        }
        $firstImages[] = $firstImage;
    }
}

// Limit preload untuk menghindari overload
$preloadLimit = min(5, count($firstImages));
?>

<?php if ($preloadLimit > 0): ?>
<!-- Preload beberapa gambar pertama -->
<?php for ($i = 0; $i < $preloadLimit; $i++): ?>
<link rel="preload" as="image" href="<?= htmlspecialchars($firstImages[$i], ENT_QUOTES, 'UTF-8') ?>">
<?php endfor; ?>
<?php endif; ?>

<div class="toolbar">
  <button type="button" onclick="window.print()">🖨️ Print Halaman Ini</button>
  <input type="text" id="product-filter" placeholder="Filter SKU">
  <span data-count>Total label: <?= count($labels ?? []) ?></span>
  <span class="load-status" data-status>Memuat gambar otomatis</span>
</div>

<div class="label-wrapper">
  <?php foreach ($labels as $index => $label):
    $bookingSn      = $label['booking_sn'] ?? '';
    $shippingMethod = $label['shipping_method'] ?? '';
    $images         = $label['label_images'] ?? [];
    $items          = $itemsByBooking[$bookingSn] ?? [];
    $downloadUrls   = array_values(array_filter($label['download_links'] ?? []));
    $productFilterText = build_product_filter_text_shopee_booking($items);
    $firstImage = !empty($images) ? $images[0] : '';
    if ($firstImage && stripos($firstImage, 'http') !== 0) {
      $firstImage = $assetBase . '/' . ltrim($firstImage, '/');
    }
    $bookingSnSafe = htmlspecialchars($bookingSn, ENT_QUOTES, 'UTF-8');
    $firstImageSafe = $firstImage ? htmlspecialchars($firstImage, ENT_QUOTES, 'UTF-8') : '';
    $productFilterTextSafe = htmlspecialchars(mb_strtolower($productFilterText, 'UTF-8'), ENT_QUOTES, 'UTF-8');
    
    // Tambahkan data attribute untuk lazy loading
    $lazyLoad = $index >= 3; // Load pertama 3 gambar langsung, sisanya lazy
  ?>
    <section class="sheet" data-booking="<?= $bookingSnSafe ?>" data-product-filter="<?= $productFilterTextSafe ?>">
      <div class="content">
        <?php if (!empty($firstImage)): ?>
          <div class="label-media" data-src="<?= $firstImageSafe ?>">
            <div class="label-skeleton">Label <?= $bookingSnSafe ?></div>
            <img
              class="label-img"
              <?php if ($lazyLoad): ?>
              loading="lazy"
              data-src="<?= $firstImageSafe ?>"
              <?php else: ?>
              src="<?= $firstImageSafe ?>"
              <?php endif; ?>
              alt="Label <?= $bookingSnSafe ?>"
              onload="this.classList.add('loaded'); this.parentNode.querySelector('.label-skeleton')?.remove();"
              onerror="handleImageError(this, '<?= $bookingSnSafe ?>')">
          </div>
        <?php else: ?>
          <div class="label-empty">
            Label belum tersedia
          </div>
        <?php endif; ?>
        <?php render_items_table_shopee_booking($items); ?>
      </div>
    </section>
  <?php endforeach; ?>
</div>
<div class="empty-state" data-empty-state>Tidak ada label yang cocok dengan filter produk.</div>

<script>
// Cache untuk gambar yang sudah dimuat
const imageCache = new Map();
let loadingActive = false;
let concurrentLoads = 0;
const MAX_CONCURRENT = 6; // Batasi concurrent loading

function normalizeFilterText(value) {
    return (value || '')
        .toString()
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
}

function applyProductFilter() {
    const input = document.getElementById('product-filter');
    const countNode = document.querySelector('[data-count]');
    const emptyNode = document.querySelector('[data-empty-state]');
    const sheets = Array.from(document.querySelectorAll('.sheet'));
    const keyword = normalizeFilterText(input ? input.value : '');
    let visibleCount = 0;

    sheets.forEach(sheet => {
        const haystack = normalizeFilterText(sheet.dataset.productFilter || '');
        const isMatch = keyword === '' || haystack.includes(keyword);
        sheet.classList.toggle('is-hidden', !isMatch);
        if (isMatch) {
            visibleCount++;
        }
    });

    if (countNode) {
        countNode.textContent = `Total label: ${visibleCount}/${sheets.length}`;
    }

    if (emptyNode) {
        emptyNode.classList.toggle('is-visible', visibleCount === 0);
    }
}

function handleImageError(img, bookingSn) {
    const wrapper = img.parentNode;
    const skeleton = wrapper.querySelector('.label-skeleton');
    if (skeleton) {
        skeleton.classList.add('error');
        skeleton.textContent = 'Label gagal dimuat';
    }
    updateStatus('error', bookingSn);
}

function updateStatus(type, bookingSn = null) {
    const statusNode = document.querySelector('[data-status]');
    if (!statusNode) return;
    
    const loadedCount = document.querySelectorAll('.label-img.loaded').length;
    const errorCount = document.querySelectorAll('.label-skeleton.error').length;
    const total = document.querySelectorAll('.label-media[data-src]').length;
    
    if (loadingActive) {
        statusNode.textContent = `Memuat ${loadedCount}/${total} label...`;
    } else if (loadedCount === total) {
        statusNode.textContent = '✅ Semua label berhasil dimuat';
    } else if (errorCount === total) {
        statusNode.textContent = '❌ Semua label gagal dimuat';
    } else if (loadedCount > 0 || errorCount > 0) {
        statusNode.textContent = `Dimuat: ${loadedCount}, ${errorCount} gagal dari ${total} label`;
    }
}

function loadImage(img) {
    return new Promise((resolve, reject) => {
        if (!img.dataset.src) {
            resolve();
            return;
        }

        // Skip jika sudah dimuat
        if (img.classList.contains('loaded')) {
            resolve();
            return;
        }
        
        // Cek cache
        if (imageCache.has(img.dataset.src)) {
            if (!img.classList.contains('loaded')) {
                img.src = img.dataset.src;
                img.classList.add('loaded');
                img.parentNode.querySelector('.label-skeleton')?.remove();
            }
            resolve();
            return;
        }
        
        const tempImg = new Image();
        tempImg.onload = () => {
            const alreadyLoaded = img.classList.contains('loaded');
            imageCache.set(img.dataset.src, true);
            img.src = img.dataset.src;
            img.classList.add('loaded');
            img.parentNode.querySelector('.label-skeleton')?.remove();
            concurrentLoads--;
            if (!alreadyLoaded) {
                resolve();
            } else {
                resolve();
            }
        };
        
        tempImg.onerror = () => {
            concurrentLoads--;
            reject(new Error('Failed to load image'));
        };
        
        concurrentLoads++;
        tempImg.src = img.dataset.src;
    });
}

async function loadImagesBatch(images, batchSize = MAX_CONCURRENT) {
    const batches = [];
    for (let i = 0; i < images.length; i += batchSize) {
        batches.push(images.slice(i, i + batchSize));
    }
    
    for (const batch of batches) {
        const promises = batch.map(img => loadImage(img));
        await Promise.allSettled(promises);
        updateStatus('batch');
        
        // Delay kecil antar batch untuk menghindari blocking
        if (batch !== batches[batches.length - 1]) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
    }
}

async function eagerLoadAllImages() {
    const lazyImages = Array.from(document.querySelectorAll('.label-img[data-src]'));
    if (!lazyImages.length) return;

    loadingActive = true;
    updateStatus('start');
    await loadImagesBatch(lazyImages);
    loadingActive = false;
    updateStatus('done');
}

// Intersection Observer untuk lazy loading
function initLazyLoading() {
    const lazyImages = Array.from(document.querySelectorAll('.label-img[data-src]'));
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    loadImage(img).then(() => {
                        updateStatus('lazy');
                    }).catch(() => {
                        updateStatus('error');
                    });
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback untuk browser lama: load semua sekaligus
        loadImagesBatch(lazyImages, 3);
    }
}

// Event listener untuk print
window.addEventListener('beforeprint', () => {
    // Pastikan semua gambar sudah dimuat sebelum print
    eagerLoadAllImages();
});

// Auto-start loading gambar pertama saat halaman siap
document.addEventListener('DOMContentLoaded', () => {
    const filterInput = document.getElementById('product-filter');
    if (filterInput) {
        filterInput.addEventListener('input', applyProductFilter);
    }

    // Load gambar pertama (yang sudah di-preload) langsung
    const firstImages = Array.from(document.querySelectorAll('.label-img[src]'));
    firstImages.forEach(img => {
        img.onload = () => {
            img.classList.add('loaded');
            img.parentNode.querySelector('.label-skeleton')?.remove();
            updateStatus('initial');
        };
    });
    
    // Inisialisasi lazy loading untuk sisanya
    setTimeout(initLazyLoading, 500);
    // Pastikan semua label dimuat tanpa perlu scroll
    setTimeout(eagerLoadAllImages, 800);
    
    applyProductFilter();
    updateStatus('ready');
});

</script>

</body>
</html>
