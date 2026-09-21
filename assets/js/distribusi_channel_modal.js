(function () {
  if (typeof bootstrap === "undefined") return;

  const modalEl = document.getElementById("productDetailModal");
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);
  const baseUrl = window.base_url || "";
  let dailyChart = null;
  let secondaryChart = null;

  const formatShort =
    window.overviewFormatShort ||
    function (value) {
      const absValue = Math.abs(value || 0);
      if (absValue >= 1_000_000_000) {
        const val = value / 1_000_000_000;
        return `${val.toFixed(val % 1 === 0 ? 0 : 1).replace(".", ",")} M`;
      }
      if (absValue >= 1_000_000) {
        const val = value / 1_000_000;
        return `${val.toFixed(val % 1 === 0 ? 0 : 1).replace(".", ",")} JT`;
      }
      return new Intl.NumberFormat("id-ID", { maximumFractionDigits: 0 }).format(Math.round(value || 0));
    };

  const channelConfig =
    window.overviewChannelConfig || [
      { key: "tiktok", label: "TikTok", color: "#0f8b8d" },
      { key: "shopee", label: "Shopee", color: "#3b82f6" },
      { key: "lazada", label: "Lazada", color: "#a855f7" },
      { key: "manual", label: "Manual", color: "#f59e0b" },
    ];

  const setModalContent = (title, subtitle, html) => {
    document.getElementById("productDetailModalLabel").textContent = title;
    document.getElementById("modalSubtitle").textContent = subtitle || "";
    document.getElementById("modalContent").innerHTML = html;
  };

  const getCurrentData = () => window.overviewProductDistData || null;
  const getEndorseGroupedData = () => window.overviewEndorseGroupedData || [];
  const getAdsGroupedData = () => window.overviewAdsGroupedData || [];
  const getAffiliateGroupedData = () => window.overviewAffiliateGroupedData || [];

  const findProduct = (payload, productId, productName) => {
    if (!payload || !Array.isArray(payload.items)) return null;
    const pid = String(productId || "");
    const pname = String(productName || "").trim().toLowerCase();

    if (pid !== "") {
      const byId = payload.items.find((it) => String(it.id) === pid);
      if (byId) return byId;
    }

    if (pname !== "") {
      const byName = payload.items.find((it) => String(it.name || "").trim().toLowerCase() === pname);
      if (byName) return byName;
    }

    return null;
  };

  const destroyChart = () => {
    if (dailyChart) {
      dailyChart.destroy();
      dailyChart = null;
    }
    if (secondaryChart) {
      secondaryChart.destroy();
      secondaryChart = null;
    }
  };

  const renderProductDetail = (product) => {
    destroyChart();

    if (!product) {
      setModalContent("Detail Produk", "", '<div class="text-danger">Data produk tidak ditemukan.</div>');
      return;
    }

    const channels = product.gmv_channels || {};
    const channelRows = channelConfig
      .map((channel) => {
        return `
          <div class="distribusi-channel-item">
            <span>${channel.label}</span>
            <strong>Rp ${formatShort(channels[channel.key] || 0)}</strong>
          </div>
        `;
      })
      .join("");

    const html = `
      <div class="distribusi-gmv-summary">${channelRows}</div>
      <div class="distribusi-daily-chart-wrap">
        <canvas id="productDailyChannelChart"></canvas>
      </div>
    `;

    setModalContent(product.name || "Detail Produk", `GMV: Rp ${formatShort(product.gmv_total || 0)}`, html);

    const daily = Array.isArray(product.daily) ? product.daily : [];
    if (!daily.length || typeof Chart === "undefined") {
      return;
    }

    const canvas = document.getElementById("productDailyChannelChart");
    if (!canvas) {
      return;
    }

    const labels = daily.map((row) => {
      if (typeof moment !== "undefined") {
        return moment(row.date, "YYYY-MM-DD").format("D MMM");
      }
      return row.date;
    });

    const datasets = channelConfig.map((channel) => ({
      label: channel.label,
      data: daily.map((row) => Number(row[channel.key] || 0)),
      borderColor: channel.color,
      borderWidth: 2,
      pointRadius: 0,
      pointHoverRadius: 3,
      tension: 0.3,
      fill: false,
    }));

    dailyChart = new Chart(canvas.getContext("2d"), {
      type: "line",
      data: {
        labels,
        datasets,
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: "index",
          intersect: false,
        },
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              boxWidth: 10,
              boxHeight: 10,
              usePointStyle: true,
            },
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return `${context.dataset.label}: Rp ${formatShort(context.raw || 0)}`;
              },
            },
          },
        },
        scales: {
          x: {
            grid: {
              display: false,
            },
          },
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return formatShort(value);
              },
            },
          },
        },
      },
    });
  };

  const renderEndorseDetail = (item) => {
    destroyChart();

    if (!item) {
      setModalContent("Detail Spent Endorse", "", '<div class="text-danger">Data produk tidak ditemukan.</div>');
      return;
    }

    const html = `
      <div class="distribusi-gmv-summary">
        <div class="distribusi-channel-item">
          <span>Total Spend Endorse</span>
          <strong>Rp ${formatShort(item.endorse_total || 0)}</strong>
        </div>
      </div>
      <div class="distribusi-daily-chart-wrap">
        <canvas id="productDailyChannelChart"></canvas>
      </div>
    `;

    setModalContent(item.name || "Detail Spent Endorse", "Breakdown harian berdasarkan konten ter-posting.", html);

    const daily = Array.isArray(item.endorse_daily) ? item.endorse_daily : [];
    if (!daily.length || typeof Chart === "undefined") {
      return;
    }

    const canvas = document.getElementById("productDailyChannelChart");
    if (!canvas) {
      return;
    }

    const labels = daily.map((row) => {
      if (typeof moment !== "undefined") {
        return moment(row.date, "YYYY-MM-DD").format("D MMM");
      }
      return row.date;
    });

    dailyChart = new Chart(canvas.getContext("2d"), {
      type: "line",
      data: {
        labels,
        datasets: [
          {
            label: "Spent Endorse",
            data: daily.map((row) => Number(row.value || 0)),
            borderColor: "#0f8b8d",
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 3,
            tension: 0.3,
            fill: false,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: "index",
          intersect: false,
        },
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return `Spent Endorse: Rp ${formatShort(context.raw || 0)}`;
              },
            },
          },
        },
        scales: {
          x: {
            grid: {
              display: false,
            },
          },
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return formatShort(value);
              },
            },
          },
        },
      },
    });
  };

  const sourceLabelMap = {
    tiktok_advertiser: "TikTok Advertiser",
    shopee_ads: "Shopee Ads",
    meta_ads: "Meta Ads",
    tiktok_general: "TikTok General",
  };

  const renderAdsDetail = (item) => {
    destroyChart();

    if (!item) {
      setModalContent("Detail Spent Ads", "", '<div class="text-danger">Data produk tidak ditemukan.</div>');
      return;
    }

    const details = Array.isArray(item.details) ? item.details : [];
    const platformTotals = {
      tiktok: 0,
      shopee: 0,
      meta: 0,
    };
    details.forEach((row) => {
      const amount = Number(row.amount || 0);
      if (amount <= 0) return;
      if (row.source === "tiktok_advertiser" || row.source === "tiktok_general") {
        platformTotals.tiktok += amount;
      } else if (row.source === "shopee_ads") {
        platformTotals.shopee += amount;
      } else if (row.source === "meta_ads") {
        platformTotals.meta += amount;
      }
    });

    const html = `
      <div class="distribusi-gmv-summary">
        <div class="distribusi-channel-item">
          <span>Total Spend Ads</span>
          <strong>Rp ${formatShort(item.ads_total || 0)}</strong>
        </div>
      </div>
      <div class="distribusi-daily-chart-wrap">
        <canvas id="adsDailyChart"></canvas>
      </div>
      <div class="ads-donut-layout mt-3">
        <div class="distribusi-daily-chart-wrap ads-donut-wrap">
          <canvas id="adsChannelDonutChart"></canvas>
        </div>
        <div class="ads-platform-stack">
          <div class="distribusi-channel-item"><span>TikTok</span><strong>Rp ${formatShort(platformTotals.tiktok)}</strong></div>
          <div class="distribusi-channel-item"><span>Shopee</span><strong>Rp ${formatShort(platformTotals.shopee)}</strong></div>
          <div class="distribusi-channel-item"><span>Meta</span><strong>Rp ${formatShort(platformTotals.meta)}</strong></div>
        </div>
      </div>
    `;

    setModalContent(item.name || "Detail Spent Ads", "Komposisi pengeluaran per channel.", html);
    if (typeof Chart === "undefined") {
      return;
    }

    const daily = Array.isArray(item.ads_daily) ? item.ads_daily : [];
    const dailyCanvas = document.getElementById("adsDailyChart");
    const donutCanvas = document.getElementById("adsChannelDonutChart");
    if (!dailyCanvas || !donutCanvas) {
      return;
    }

    const dailyLabels = daily.map((row) => {
      if (typeof moment !== "undefined") {
        return moment(row.date, "YYYY-MM-DD").format("D MMM");
      }
      return row.date;
    });

    dailyChart = new Chart(dailyCanvas.getContext("2d"), {
      type: "line",
      data: {
        labels: dailyLabels,
        datasets: [
          {
            label: "Spent Ads",
            data: daily.map((row) => Number(row.value || 0)),
            borderColor: "#3b82f6",
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 3,
            tension: 0.3,
            fill: false,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: "index",
          intersect: false,
        },
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return `Spent Ads: Rp ${formatShort(context.raw || 0)}`;
              },
            },
          },
        },
        scales: {
          x: {
            grid: {
              display: false,
            },
          },
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return formatShort(value);
              },
            },
          },
        },
      },
    });

    secondaryChart = new Chart(donutCanvas.getContext("2d"), {
      type: "doughnut",
      data: {
        labels: ["TikTok", "Shopee", "Meta"],
        datasets: [
          {
            data: [platformTotals.tiktok, platformTotals.shopee, platformTotals.meta],
            backgroundColor: ["#0f8b8d", "#3b82f6", "#a855f7"],
            borderColor: "#ffffff",
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              boxWidth: 10,
              boxHeight: 10,
              usePointStyle: true,
            },
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return `${context.label}: Rp ${formatShort(context.raw || 0)}`;
              },
            },
          },
        },
      },
    });
  };

  const renderAffiliateDetail = (item) => {
    destroyChart();

    if (!item) {
      setModalContent("Detail Spent Affiliate", "", '<div class="text-danger">Data produk tidak ditemukan.</div>');
      return;
    }

    const totalHpp = Number(item.hpp_total || 0);
    const totalOngkir = Number(item.ongkir_total || 0);
    const totalTrx = Number(item.trx_count || 0);
    const totalSpend = Number(item.affiliate_total || 0);

    const html = `
      <div class="distribusi-gmv-summary distribusi-gmv-summary-3">
        <div class="distribusi-channel-item"><span>HPP</span><strong>Rp ${formatShort(totalHpp)}</strong></div>
        <div class="distribusi-channel-item"><span>Ongkir</span><strong>Rp ${formatShort(totalOngkir)}</strong></div>
        <div class="distribusi-channel-item"><span>Jumlah Transaksi</span><strong>${new Intl.NumberFormat("id-ID").format(totalTrx)}</strong></div>
      </div>
      <div class="distribusi-daily-chart-wrap">
        <canvas id="affiliateDailyChart"></canvas>
      </div>
    `;

    setModalContent(item.name || "Detail Spent Affiliate", `Total Spend Affiliate: Rp ${formatShort(totalSpend)}`, html);

    const daily = Array.isArray(item.affiliate_daily) ? item.affiliate_daily : [];
    if (!daily.length || typeof Chart === "undefined") {
      return;
    }

    const canvas = document.getElementById("affiliateDailyChart");
    if (!canvas) {
      return;
    }

    const labels = daily.map((row) => {
      if (typeof moment !== "undefined") {
        return moment(row.date, "YYYY-MM-DD").format("D MMM");
      }
      return row.date;
    });

    dailyChart = new Chart(canvas.getContext("2d"), {
      type: "line",
      data: {
        labels,
        datasets: [
          {
            label: "HPP",
            data: daily.map((row) => Number(row.hpp || 0)),
            borderColor: "#0f8b8d",
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 3,
            tension: 0.3,
            fill: false,
            yAxisID: "y",
          },
          {
            label: "Ongkir",
            data: daily.map((row) => Number(row.ongkir || 0)),
            borderColor: "#3b82f6",
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 3,
            tension: 0.3,
            fill: false,
            yAxisID: "y",
          },
          {
            label: "Jumlah Transaksi",
            data: daily.map((row) => Number(row.trx_count || 0)),
            borderColor: "#f59e0b",
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 3,
            tension: 0.3,
            fill: false,
            yAxisID: "y1",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: "index",
          intersect: false,
        },
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              boxWidth: 10,
              boxHeight: 10,
              usePointStyle: true,
            },
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                if (context.dataset.yAxisID === "y1") {
                  return `${context.dataset.label}: ${new Intl.NumberFormat("id-ID").format(context.raw || 0)}`;
                }
                return `${context.dataset.label}: Rp ${formatShort(context.raw || 0)}`;
              },
            },
          },
        },
        scales: {
          x: {
            grid: {
              display: false,
            },
          },
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return formatShort(value);
              },
            },
          },
          y1: {
            beginAtZero: true,
            position: "right",
            grid: {
              drawOnChartArea: false,
            },
            ticks: {
              callback: function (value) {
                return new Intl.NumberFormat("id-ID").format(value || 0);
              },
            },
          },
        },
      },
    });
  };

  const fetchAndOpen = (productId, productName) => {
    setModalContent(
      "Detail Produk",
      "",
      '<div class="distribusi-loading"><div class="spinner-border spinner-border-sm" role="status"></div><span>Memuat data...</span></div>',
    );
    modal.show();

    const existing = findProduct(getCurrentData(), productId, productName);
    if (existing && Array.isArray(existing.daily) && existing.daily.length) {
      renderProductDetail(existing);
      return;
    }

    $.ajax({
      url: baseUrl + "ajax/get_overview_product_detail",
      dataType: "json",
      data: {
        start_date: window.overviewState ? window.overviewState.start_date : "",
        end_date: window.overviewState ? window.overviewState.end_date : "",
        product_ids:
          window.overviewState && Array.isArray(window.overviewState.product_ids)
            ? window.overviewState.product_ids.join(",")
            : "",
      },
      success: function (res) {
        const payload = res.products || {};
        window.overviewProductDistData = payload;
        renderProductDetail(findProduct(payload, productId, productName));
      },
      error: function () {
        renderProductDetail(findProduct(getCurrentData(), productId, productName));
      },
    });
  };

  modalEl.addEventListener("hidden.bs.modal", function () {
    destroyChart();
  });

  $(document).on("click", ".product-card[data-product-id]", function () {
    const productId = $(this).data("product-id");
    const productName = $(this).data("product-name");
    fetchAndOpen(productId, productName);
  });

  $(document).on("click", ".product-mini-item[data-detail-type='endorse']", function () {
    const endorseKey = String($(this).data("endorse-key") || "");
    const item = getEndorseGroupedData().find((it) => String(it.key || "") === endorseKey) || null;
    setModalContent(
      "Detail Spent Endorse",
      "",
      '<div class="distribusi-loading"><div class="spinner-border spinner-border-sm" role="status"></div><span>Memuat data...</span></div>',
    );
    modal.show();
    renderEndorseDetail(item);
  });

  $(document).on("click", ".product-mini-item[data-detail-type='ads']", function () {
    const adsKey = String($(this).data("ads-key") || "");
    const item = getAdsGroupedData().find((it) => String(it.key || "") === adsKey) || null;
    setModalContent(
      "Detail Spent Ads",
      "",
      '<div class="distribusi-loading"><div class="spinner-border spinner-border-sm" role="status"></div><span>Memuat data...</span></div>',
    );
    modal.show();
    renderAdsDetail(item);
  });

  $(document).on("click", ".product-mini-item[data-detail-type='affiliate']", function () {
    const affiliateKey = String($(this).data("affiliate-key") || "");
    const item = getAffiliateGroupedData().find((it) => String(it.key || "") === affiliateKey) || null;
    setModalContent(
      "Detail Spent Affiliate",
      "",
      '<div class="distribusi-loading"><div class="spinner-border spinner-border-sm" role="status"></div><span>Memuat data...</span></div>',
    );
    modal.show();
    renderAffiliateDetail(item);
  });
})();
