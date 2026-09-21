<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Shipping Documents</title>
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

  /* LAYOUT GRID 4 KOLOM */
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
    height: 6in;
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
// OPTIMASI: Preload beberapa gambar pertama
$preloadImages = [];
$preloadCount = 0;
$assetBase = rtrim($this->template->endpoint_url(), '/');

foreach ($results as $r) {
    if ($preloadCount >= 3) break;
    
    $docUrl = $r['document_urls']['doc_url'] ?? null;
    if ($docUrl) {
        // Convert relative URL jika perlu
        if (stripos($docUrl, 'http') !== 0) {
            $docUrl = $assetBase . '/' . ltrim($docUrl, '/');
        }
        $preloadImages[] = $docUrl;
        $preloadCount++;
    }
}
?>

<?php if (!empty($preloadImages)): ?>
<!-- Preload gambar pertama -->
<?php foreach ($preloadImages as $imageUrl): ?>
<link rel="preload" as="image" href="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>
<?php endif; ?>

<div class="toolbar">
  <button type="button" onclick="window.print()">🖨️ Print Halaman Ini</button>
  <span>Total label: <?= count($results) ?></span>
  <span class="load-status" data-status>Memuat gambar otomatis</span>
</div>

<div class="label-wrapper">

<?php
// FUNGSI RENDER ITEMS TETAP SAMA SEPERTI SEBELUMNYA
function render_items_table($itemsJson) {
  if (!is_array($itemsJson) || empty($itemsJson)) {
    echo '<div style="color:#666;font-size:12px">Tidak ada item.</div>';
    return;
  }
  echo '<table><thead><tr><th>SKU</th><th>Qty</th></tr></thead><tbody>';
  foreach ($itemsJson as $item) {
    $sku = $item['sku'] ?? '';
    $qty = $item['qty'] ?? '';
    echo '<tr>';
    echo '<td>'.htmlspecialchars($sku).'</td>';
    echo '<td>'.htmlspecialchars((string)$qty).'</td>';
    echo '</tr>';
  }
  echo '</tbody></table>';
}
?>

<?php 
// OPTIMASI: Loop dengan counter untuk lazy loading
$totalResults = count($results);

for ($i = 0; $i < $totalResults; $i++):
  $r = $results[$i];
  $docUrl = $r['document_urls']['doc_url'] ?? null;
  $itemsJson = $itemsByTrx[$r['transaction_id']] ?? [];
  
  // Process URL
  $finalUrl = '';
  if ($docUrl) {
    $finalUrl = $docUrl;
    if (stripos($docUrl, 'http') !== 0) {
      $finalUrl = $assetBase . '/' . ltrim($docUrl, '/');
    }
  }
  
  $docSafe = $finalUrl ? htmlspecialchars($finalUrl, ENT_QUOTES, 'UTF-8') : '';
  $orderIdSafe = htmlspecialchars($r['transaction_id'] ?? '', ENT_QUOTES, 'UTF-8');
  
  // Tentukan lazy loading (3 gambar pertama langsung, sisanya lazy)
  $lazyLoad = $i >= 3;
?>
  <section class="sheet">
    <div class="content">
      <?php if ($docUrl): ?>
        <div class="label-media" data-src="<?= $docSafe ?>">
          <div class="label-skeleton">Memuat label…</div>
          <img
            class="label-img"
            <?php if ($lazyLoad): ?>
            loading="lazy"
            data-src="<?= $docSafe ?>"
            <?php else: ?>
            src="<?= $docSafe ?>"
            <?php endif; ?>
            alt="Shipping Label <?= $orderIdSafe ?>"
            onload="this.classList.add('loaded'); this.parentNode.querySelector('.label-skeleton')?.remove();"
            onerror="handleImageError(this, '<?= $orderIdSafe ?>')">
        </div>
      <?php else: ?>
        <div class="label-empty">Label belum tersedia.</div>
      <?php endif; ?>

      <?php render_items_table($itemsJson); ?>
    </div>
  </section>
<?php endfor; ?>

</div>

<script>
// Cache untuk gambar yang sudah dimuat
const imageCache = new Map();
let loadingActive = false;
let loadedCount = 0;
let errorCount = 0;
const MAX_CONCURRENT = 6;

function handleImageError(img, orderId) {
    const wrapper = img.parentNode;
    const skeleton = wrapper.querySelector('.label-skeleton');
    if (skeleton) {
        skeleton.classList.add('error');
        skeleton.textContent = 'Label gagal dimuat';
    }
    updateStatus('error', orderId);
}

function updateStatus(type, orderId = null) {
    const statusNode = document.querySelector('[data-status]');
    if (!statusNode) return;
    
    const total = document.querySelectorAll('.label-media[data-src]').length;
    
    if (loadingActive) {
        statusNode.textContent = `Memuat ${loadedCount}/${total} label...`;
    } else if (errorCount === 0 && loadedCount === total) {
        statusNode.textContent = '✅ Semua label berhasil dimuat';
    } else if (loadedCount === 0 && errorCount === total) {
        statusNode.textContent = '❌ Semua label gagal dimuat';
    } else {
        statusNode.textContent = `Dimuat: ${loadedCount}, ${errorCount} gagal dari ${total} label`;
    }
}

function loadImage(img) {
    return new Promise((resolve, reject) => {
        if (!img.dataset.src) {
            resolve();
            return;
        }

        // Skip jika sudah pernah dimuat
        if (img.classList.contains('loaded')) {
            resolve();
            return;
        }
        
        // Cek cache dulu
        if (imageCache.has(img.dataset.src)) {
            const alreadyLoaded = img.classList.contains('loaded');
            img.src = img.dataset.src;
            img.classList.add('loaded');
            img.parentNode.querySelector('.label-skeleton')?.remove();
            if (!alreadyLoaded) {
                loadedCount++;
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
            if (!alreadyLoaded) {
                loadedCount++;
            }
            resolve();
        };
        
        tempImg.onerror = () => {
            const skeleton = img.parentNode.querySelector('.label-skeleton');
            if (skeleton) {
                skeleton.classList.add('error');
                skeleton.textContent = 'Label gagal dimuat';
            }
            errorCount++;
            reject();
        };
        
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
        
        // Delay kecil antar batch
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

// Lazy loading dengan Intersection Observer
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
        // Fallback untuk browser lama
        loadImagesBatch(lazyImages, 3);
    }
}

// Auto-start loading saat halaman siap
document.addEventListener('DOMContentLoaded', () => {
    // Load gambar pertama (yang sudah di-preload) langsung
    const firstImages = Array.from(document.querySelectorAll('.label-img[src]'));
    firstImages.forEach(img => {
        img.onload = () => {
            img.classList.add('loaded');
            img.parentNode.querySelector('.label-skeleton')?.remove();
            loadedCount++;
            updateStatus('initial');
        };
        img.onerror = () => {
            handleImageError(img, img.alt.replace('Shipping Label ', ''));
        };
    });
    
    // Inisialisasi lazy loading untuk sisanya
    setTimeout(initLazyLoading, 500);
    // Pastikan semua label tetap dimuat tanpa perlu scroll
    setTimeout(eagerLoadAllImages, 800);
    
    updateStatus('ready');
});

// Handle print event
window.addEventListener('beforeprint', () => {
    eagerLoadAllImages();
});
</script>

</body>
</html>
