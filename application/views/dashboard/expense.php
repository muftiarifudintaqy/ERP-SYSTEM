<?php
$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : ($start_date ?? '');
$until_date = !empty($_GET['until_date']) ? $_GET['until_date'] : ($until_date ?? '');

$spend_ads  = $spend_ads  ?? [];
$spend_kol  = $spend_kol  ?? [];
$net_sales  = (float)($net_sales ?? 0);
$total_expense_all_cat = (float)($total_expense_all_cat ?? 0);
$expense_by_category = $expense_by_category ?? [];
$expense_section_map = $expense_section_map ?? [];
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-primary fw-600">Laporan Pengeluaran</h2>
  </div>

  <!-- Filter -->
  <form action="<?= $url ?>" method="GET">
    <div class="row">
      <div class="col-md-3">
        <select class="form-control select2" name="brand" id="brand">
          <option value="">Brand</option>
          <?php foreach ($brands as $val) :
            $text = (($_GET["brand"] ?? '') == $val["code"]) ? "selected" : ""; ?>
            <option <?= $text ?> value="<?= $val["code"] ?>"><?= $val["code"] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
        <input type="hidden" name="start_date" id="start_date" value="<?= html_escape($_GET['start_date'] ?? $start_date) ?>">
        <input type="hidden" name="until_date" id="end_date" value="<?= html_escape($_GET['until_date'] ?? $until_date) ?>">
      </div>

      <div class="col-md-2">
        <button class="btn btn-primary w-100 form-control" type="submit">
          <i class="bi bi-search fs-16"></i> Cari Data
        </button>
      </div>
    </div>

    <script>
      get_filter();
      function get_filter() {
        $.ajax({
          dataType: "json",
          url: '<?= base_url() ?>/ajax/get-filter',
          data: {
            start_date: "<?= html_escape($_GET['start_date'] ?? $start_date) ?>",
            until_date: "<?= html_escape($_GET['until_date'] ?? $until_date) ?>",
          },
          success: function(response) {
            $("#tanggal").after(response.html);
          },
          error: function(xhr, status, error) {
            console.error("Error loading filter:", error);
          }
        });
      }
    </script>
  </form>

  <div class="row g-4 mt-1">
    <div class="col-md-8">
      <div class="report-card">

        <?php
        $ads_total = (float)($spend_ads['total_spend_ads'] ?? 0);
        $kol_total = (float)($spend_kol['total_spend_kol'] ?? 0);
        $all_spend = $ads_total + $kol_total + $total_expense_all_cat;

        $total_percentage = ($net_sales > 0) ? round(($all_spend / $net_sales) * 100, 2) : 0;
        ?>

        <h4 class="fw-bold d-flex justify-content-between align-items-center" style="font-size:18px;">
          <span>Total Pengeluaran</span>
          <span class="text-danger fw-bolder" style="font-size:18px;">
            -<?= number_format($all_spend, 0, ',', '.') ?>
            <small class="text-muted">(<?= $total_percentage ?>%)</small>
          </span>
        </h4>
        <div class="hr-dashed my-3"></div>
        <div id="marketing-section"></div>

        <div class="hr-dashed my-3"></div>
        <div id="category-section"></div>

        <div class="text-end text-muted" style="font-size:12px; margin-top:8px;">
          <?= html_escape($start_date) ?> - <?= html_escape($until_date) ?>
        </div>

      </div>
    </div>
  </div>

  <style>
    .report-card { background:#fff; padding:25px; border-radius:12px; }
    .report-card a { text-decoration:none; }

    .hr-dashed { border-top:1px dashed #d0d7de; }

    .cat-list .cat-row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:8px 0;
      border-top:0;
    }

    .cat-row[draggable="true"] { cursor: grab; }
    .cat-row.dragging { opacity: 0.5; }
    .drop-zone { padding: 4px 0; }
    .drop-zone.drag-over { background: #f8fafc; outline: 1px dashed #cbd5e1; border-radius: 6px; }
  </style>
</div>

<script>
  (function() {
    const data = {
      adsTotal: <?= json_encode($ads_total) ?>,
      kolTotal: <?= json_encode($kol_total) ?>,
      netSales: <?= json_encode($net_sales) ?>,
      categories: <?= json_encode($expense_by_category) ?>,
      sectionMap: <?= json_encode($expense_section_map) ?>,
      startDate: <?= json_encode($start_date) ?>,
      untilDate: <?= json_encode($until_date) ?>,
      brand: <?= json_encode($_GET['brand'] ?? '') ?>
    };

    const pct = (val, denom) => (denom > 0 ? ((val / denom) * 100).toFixed(2) : "0.00");
    const formatCurrency = (num) => {
      const n = Math.round(parseFloat(num || 0));
      return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    };
    const getSection = (cat) => {
      if (data.sectionMap && data.sectionMap[cat]) return data.sectionMap[cat];
      if (cat === '__ads' || cat === '__kol') return 'marketing';
      const name = String(cat || '').trim().toLowerCase();
      if (name === 'affiliate' || name === 'marketing') return 'marketing';
      return 'category';
    };

    const buildRow = (label, value, percentage, href, catKey) => {
      const pctHtml = (percentage !== null) ? `<small class="text-muted">(${percentage}%)</small>` : '';
      const draggable = catKey ? 'draggable="true"' : '';
      const dataAttr = catKey ? `data-cat="${catKey}"` : '';
      return `
        <a href="${href || '#'}" class="cat-row text-secondary fw-bold" ${draggable} ${dataAttr}>
          <span>${label}</span>
          <span class="text-danger">
            -${formatCurrency(value)}
            ${pctHtml}
          </span>
        </a>
      `;
    };

    const render = () => {
      const cats = Array.isArray(data.categories) ? data.categories : [];
      const baseItems = [
        { key: '__ads', label: 'Ads', value: data.adsTotal, href: `<?= base_url() ?>overview?t=ads&start_date=${encodeURIComponent(data.startDate)}&until_date=${encodeURIComponent(data.untilDate)}` },
        { key: '__kol', label: 'KOL', value: data.kolTotal, href: `<?= base_url() ?>payment?start_date=${encodeURIComponent(data.startDate)}&until_date=${encodeURIComponent(data.untilDate)}` }
      ];
      const marketingCats = [];
      const categoryCats = [];
      for (const item of baseItems) {
        const section = getSection(item.key);
        if (section === 'marketing') marketingCats.push(item);
        else categoryCats.push(item);
      }
      for (const row of cats) {
        const rawCat = row.category || 'Lain-lain';
        const section = getSection(rawCat);
        if (section === 'marketing') marketingCats.push(row);
        else categoryCats.push(row);
      }

      let marketingCatsTotal = 0;
      for (const row of marketingCats) {
        const val = row.value !== undefined ? row.value : row.total_spend;
        marketingCatsTotal += Math.round(parseFloat(val) || 0);
      }
      const marketingTotal = marketingCatsTotal;

      const marketingHtml = `
        <div class="fw-bold mb-1 mt-2 d-flex justify-content-between align-items-center" style="font-size:16px;">
          <span>Marketing</span>
          <span class="text-danger fw-bolder" style="font-size:16px;">
            -${formatCurrency(marketingTotal)}
            <small class="text-muted">(${pct(marketingTotal, data.netSales)}%)</small>
          </span>
        </div>
        <div class="cat-list drop-zone" data-section="marketing">
          ${marketingCats.map((row) => {
            if (row.key) {
              const p = pct(row.value, data.netSales);
              return buildRow(row.label, row.value, p, row.href, row.key);
            }
            const cat = row.category || 'Lain-lain';
            const val = Math.round(parseFloat(row.total_spend) || 0);
            const p = pct(val, data.netSales);
            const href = `<?= base_url() ?>expense?brand=${encodeURIComponent(data.brand)}&category=${encodeURIComponent(cat)}&start_date=${encodeURIComponent(data.startDate)}&until_date=${encodeURIComponent(data.untilDate)}`;
            return buildRow(cat, val, p, href, cat);
          }).join('')}
        </div>
      `;

      let categoryHtml = '';
      if (categoryCats.length) {
        categoryHtml = `
          <div class="fw-bold mb-1">Per Kategori</div>
          <div class="cat-list drop-zone" data-section="category">
            ${categoryCats.map((row) => {
              if (row.key) {
                const p = pct(row.value, data.netSales);
                return buildRow(row.label, row.value, p, row.href, row.key);
              }
              const cat = row.category || 'Lain-lain';
              const val = Math.round(parseFloat(row.total_spend) || 0);
              const p = pct(val, data.netSales);
              const href = `<?= base_url() ?>expense?brand=${encodeURIComponent(data.brand)}&category=${encodeURIComponent(cat)}&start_date=${encodeURIComponent(data.startDate)}&until_date=${encodeURIComponent(data.untilDate)}`;
              return buildRow(cat, val, p, href, cat);
            }).join('')}
          </div>
        `;
      } else {
        categoryHtml = `<div class="text-muted mt-2">Tidak ada data pengeluaran pada rentang tanggal ini.</div>`;
      }

      document.getElementById('marketing-section').innerHTML = marketingHtml;
      document.getElementById('category-section').innerHTML = categoryHtml;
      bindDnD();
    };

    const bindDnD = () => {
      const rows = document.querySelectorAll('.cat-row[draggable="true"]');
      const zones = document.querySelectorAll('.drop-zone');
      rows.forEach((row) => {
        row.addEventListener('dragstart', (e) => {
          row.classList.add('dragging');
          e.dataTransfer.setData('text/plain', row.dataset.cat || '');
        });
        row.addEventListener('dragend', () => row.classList.remove('dragging'));
      });
      zones.forEach((zone) => {
        zone.addEventListener('dragover', (e) => {
          e.preventDefault();
          zone.classList.add('drag-over');
        });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
        zone.addEventListener('drop', (e) => {
          e.preventDefault();
          zone.classList.remove('drag-over');
          const cat = e.dataTransfer.getData('text/plain');
          if (!cat) return;
          const targetSection = zone.dataset.section;
          data.sectionMap[cat] = targetSection;
          $.post('<?= base_url() ?>dashboard/update_expense_section_map', {
            category: cat,
            section: targetSection
          });
          render();
        });
      });
    };

    render();
  })();
</script>
