<?php
if (!$_SESSION['is_login']) {
  redirect(base_url() . 'auth/login');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= $title ?></title>
  <meta name="description" content="We are Building Legacy, that Impactfull to the Society">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta charset="utf-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url() ?>assets/css/bootstrap-datepicker.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
  
  <!-- Career Tree Visualization CSS -->
  <!-- career-tree.css tidak ada di server, dinonaktifkan supaya tidak 404 -->

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-quartz.css" />
  <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>

  <!-- Include jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script> -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- <script src="<?= base_url() ?>assets/js/bootstrap-datepicker.min.js"></script> -->

  <!-- Font Awesome 5.15.4 (Free) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
    integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">


  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css">
  <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/id.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- D3.js v7 for Career Tree Visualization -->
  <script src="https://d3js.org/d3.v7.min.js"></script>
  <!-- career-tree-visualization.js tidak ada di server, dinonaktifkan supaya tidak 404 -->
  
  <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script> -->
  <!-- Firebase SDK -->
  <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-database-compat.js"></script>
  <script src="https://unpkg.com/html5-qrcode"></script>
  <!-- Toastr -->
  <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>

  <!-- Load daterangepicker -->

  <!-- <script>
    // Konfigurasi Firebase
    const firebaseConfig = {
      apiKey: "<?= htmlspecialchars(app_env('FIREBASE_API_KEY', ''), ENT_QUOTES, 'UTF-8') ?>",
      authDomain: "<?= htmlspecialchars(app_env('FIREBASE_AUTH_DOMAIN', ''), ENT_QUOTES, 'UTF-8') ?>",
      databaseURL: "<?= htmlspecialchars(app_env('FIREBASE_DATABASE_URL', ''), ENT_QUOTES, 'UTF-8') ?>",
      projectId: "<?= htmlspecialchars(app_env('FIREBASE_PROJECT_ID', ''), ENT_QUOTES, 'UTF-8') ?>",
      storageBucket: "<?= htmlspecialchars(app_env('FIREBASE_STORAGE_BUCKET', ''), ENT_QUOTES, 'UTF-8') ?>",
      messagingSenderId: "<?= htmlspecialchars(app_env('FIREBASE_MESSAGING_SENDER_ID', ''), ENT_QUOTES, 'UTF-8') ?>",
      appId: "<?= htmlspecialchars(app_env('FIREBASE_APP_ID', ''), ENT_QUOTES, 'UTF-8') ?>",
      measurementId: "<?= htmlspecialchars(app_env('FIREBASE_MEASUREMENT_ID', ''), ENT_QUOTES, 'UTF-8') ?>"
    };

    // Inisialisasi Firebase
    firebase.initializeApp(firebaseConfig);

    // Akses database
    const database = firebase.database();

    console.log("Firebase berhasil diinisialisasi!");
  </script> -->

  <script>
    $(document).ready(function() {
      // Inisialisasi daterangepicker
      $('#date-range').daterangepicker({
        locale: {
          format: 'YYYY-MM-DD',
          applyLabel: 'Terapkan',
          cancelLabel: 'Batal',
          daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
          monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
          ],
          firstDay: 1
        },
        opens: 'right',
        autoUpdateInput: false,
        showDropdowns: true
      });

      $('#date-range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
      });
    });
  </script>



  <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>assets/img/fav.png">

  <link rel="stylesheet" href="<?= base_url() ?>assets/css/style.css?v=1.0.4" type="text/css" media="screen" />
  <link rel="stylesheet" href="<?= base_url() ?>assets/css/reward-effects.css?v=<?= @filemtime(FCPATH . 'assets/css/reward-effects.css') ?>" type="text/css" media="screen" />

  <?php
  if ($data[0]['img']) {
    $img = base_url() . '/assets/webfile/home/' . $data[0]['img'];
  } else {
    $img = base_url() . '/assets/img/logo.png';
  }
  $img = base_url() . '/assets/img/fav.png';
  ?>
  <meta property="og:image" content="<?= $img ?>" />
  <meta property="og:image:width" content="1000" />
  <meta property="og:image:height" content="1000" />

  <link rel="stylesheet" href="https://icons.getbootstrap.com/assets/font/bootstrap-icons.css">
  <link href="https://pictogrammers.github.io/@mdi/font/2.0.46/css/materialdesignicons.min.css" media="all" rel="stylesheet" type="text/css" />


  <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js@3.3.2/dist/chart.min.js"></script> -->
  <script src="<?= base_url() ?>assets/chart/chart.js"></script>
  <script src="<?= base_url() ?>assets/chart/gauge.min.js"></script>
  <script src="<?= base_url() ?>assets/js/reward-effects.js?v=<?= @filemtime(FCPATH . 'assets/js/reward-effects.js') ?>"></script>

  <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
  <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script> -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

  <link rel="stylesheet" href="<?= base_url() ?>assets/toast/jquery.toast.css">
  <script src="<?= base_url() ?>assets/toast/jquery.toast.js"></script>

  <link rel="stylesheet" href="https://owlcarousel2.github.io/OwlCarousel2/assets/owlcarousel/assets/owl.theme.default.min.css">
  <link rel="stylesheet" href="https://owlcarousel2.github.io/OwlCarousel2/assets/owlcarousel/assets/owl.carousel.min.css">
  <script src="https://owlcarousel2.github.io/OwlCarousel2/assets/owlcarousel/owl.carousel.js"></script>




  <!-- <link href='<?= base_url() ?>assets/fullcalendar/fullcalendar.min.css' rel='stylesheet' />
  <link href='<?= base_url() ?>assets/fullcalendar/fullcalendar.css' rel='stylesheet' />
  <link href='<?= base_url() ?>assets/fullcalendar/fullcalendar.print.min.css' rel='stylesheet' media='print' />
  <script src='<?= base_url() ?>assets/fullcalendar/moment.min.js'></script>
  <script src='<?= base_url() ?>assets/fullcalendar/fullcalendar.min.js'></script> -->

  <!-- <link rel="stylesheet" href="<?= base_url() ?>assets/fullcalendar/fullcalendar.css">
  <script src="<?= base_url() ?>assets/fullcalendar/moment.min.js"></script>
  <script src="<?= base_url() ?>assets/fullcalendar/fullcalendar.min.js"></script>
  <script src="<?= base_url() ?>assets/fullcalendar/jquery-ui.min.js"></script>
  <script src="<?= base_url() ?>assets/fullcalendar/jquery.min.js"></script> -->

  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <script src="https://unpkg.com/@popperjs/core@2"></script>
  <script src="https://unpkg.com/tippy.js@6"></script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

  <style>
    #calendar {
      border-radius: 10px;
      /* padding:20px 15px; */
      max-width: 100%;
      margin: 0 auto;
      background: #FFF;
    }

    .fc-center h2 {
      font-size: 21px;
    }

    input[type="file"]::-webkit-file-upload-button {
      height: 45px;
    }

    .form-table-1 {
      border: unset;
      background: transparent;
      min-width: 100px !important;
    }

    .btn-action {
      font-size: 15px;
    }

    .btn-action {
      font-size: 11px;
      height: 32px;
      padding-top: 0px !important;
    }

    .content-body {
      padding: 32px;
      min-height: 101vh;
      background-color: #F2F2F2;
    }

    .box-legend {
      width: 7px !important;
      height: 7px !important;
      margin-right: 5px !important;
      margin-top: 4px !important;
    }

    .select2 {
      height: 45px !important;
      margin-top: 0px !important;
      margin-bottom: 10px !important;
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.07) !important;
      /* min-width: 100% !important; */
      /* max-width: 100% !important; */
      /* width: 100% !important; */
    }

    .select2-container .select2-selection--single {
      box-sizing: border-box;
      cursor: pointer;
      display: block;
      height: 45px !important;
      user-select: none;
      -webkit-user-select: none;
      border: 1px solid #ced4da;
      border-radius: 0.5rem !important;
    }


    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: #444;
      line-height: 45px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 45px !important;
      position: absolute;
      top: 0px;
      right: 1px;
      width: 20px;
    }

    .floating-div {
      position: fixed;
      bottom: 10px;
      right: 30px;
      max-width: fit-content;
    }
  </style>


  <style>
    @supports (-webkit-appearance: none) or (-moz-appearance: none) {
      .checkbox-wrapper-13 input[type=checkbox] {
        --active: #275EFE;
        --active-inner: #fff;
        --focus: 2px rgba(39, 94, 254, .3);
        --border: #BBC1E1;
        --border-hover: #275EFE;
        --background: #fff;
        --disabled: #F6F8FF;
        --disabled-inner: #E1E6F9;
        -webkit-appearance: none;
        -moz-appearance: none;
        height: 21px;
        outline: none;
        display: inline-block;
        vertical-align: top;
        position: relative;
        cursor: pointer;
        border: 1px solid var(--bc, var(--border));
        background: var(--b, var(--background));
        transition: background 0.3s, border-color 0.3s, box-shadow 0.2s;
        /* padding-left: 1rem !important; */
      }

      .checkbox-wrapper-13 input[type=checkbox]:after {
        content: "";
        display: block;
        left: 0;
        top: 0;
        position: absolute;
        transition: transform var(--d-t, 0.3s) var(--d-t-e, ease), opacity var(--d-o, 0.2s);
      }

      .checkbox-wrapper-13 input[type=checkbox]:checked {
        --b: var(--active);
        --bc: var(--active);
        --d-o: .3s;
        --d-t: .6s;
        --d-t-e: cubic-bezier(.2, .85, .32, 1.2);
      }

      .checkbox-wrapper-13 input[type=checkbox]:disabled {
        --b: var(--disabled);
        cursor: not-allowed;
        opacity: 0.9;
      }

      .checkbox-wrapper-13 input[type=checkbox]:disabled:checked {
        --b: var(--disabled-inner);
        --bc: var(--border);
      }

      .checkbox-wrapper-13 input[type=checkbox]:disabled+label {
        cursor: not-allowed;
      }

      .checkbox-wrapper-13 input[type=checkbox]:hover:not(:checked):not(:disabled) {
        --bc: var(--border-hover);
      }

      .checkbox-wrapper-13 input[type=checkbox]:focus {
        box-shadow: 0 0 0 var(--focus);
      }

      .checkbox-wrapper-13 input[type=checkbox]:not(.switch) {
        width: 21px;
      }

      .checkbox-wrapper-13 input[type=checkbox]:not(.switch):after {
        opacity: var(--o, 0);
      }

      .checkbox-wrapper-13 input[type=checkbox]:not(.switch):checked {
        --o: 1;
      }

      .checkbox-wrapper-13 input[type=checkbox]+label {
        display: inline-block;
        vertical-align: middle;
        cursor: pointer;
        margin-left: 2px;
      }

      .checkbox-wrapper-13 input[type=checkbox]:not(.switch) {
        border-radius: 7px;
      }

      .checkbox-wrapper-13 input[type=checkbox]:not(.switch):after {
        width: 5px;
        height: 9px;
        border: 2px solid var(--active-inner);
        border-top: 0;
        border-left: 0;
        left: 7px;
        top: 4px;
        transform: rotate(var(--r, 20deg));
      }

      .checkbox-wrapper-13 input[type=checkbox]:not(.switch):checked {
        --r: 43deg;
      }
    }

    .checkbox-wrapper-13 * {
      box-sizing: inherit;
    }

    .checkbox-wrapper-13 *:before,
    .checkbox-wrapper-13 *:after {
      box-sizing: inherit;
    }

    .d-inline {
      display: inline;
    }
  </style>

  <style>
    .box-main {
      border: 1px #ced4da solid;
      padding: 16px;
      background: #FFF;
      border-radius: 12px;
      margin-bottom: 10px;
    }

    .card {
      border-radius: 12px;
      border: #FFF 1px solid;
      padding: 10px;
    }

    /* input[readonly] {
      background-color: rgba(0, 0, 0, 0.05);
    } */

    /* .modal-dialog {
        min-height: 100vh;
    } */


    .bg-b {
      background: #ebf6f6;
    }

    .bg-o {
      background: #ffe8e7;
    }

    .sidebar,
    .img-logo {
      z-index: 100 !important;
    }

    .td-breakline {
      word-wrap: break-word;
    }

    .tr-search td {
      padding-top: 20px !important;
      padding-bottom: 20px !important;
    }

    .summary th {
      font-size: 12px !important;
      padding: 5px !important;
    }

    .summary td {
      font-size: 12px !important;
      padding: 5px !important;
    }

    .summary table tr:first-child th:first-child {
      font-size: 12px !important;
      padding: 5px !important;
    }

    .summary table tr:first-child th:last-child {
      font-size: 12px !important;
      padding: 5px !important;
    }

    .dropdown-items {
      display: block;
      width: 100%;
      padding: 0px;
      clear: both;
      font-weight: 400;
      color: var(--bs-dropdown-link-color);
      text-align: inherit;
      text-decoration: none;
      white-space: nowrap;
      background-color: transparent;
      border: 0;
    }

    .w-100 {
      width: 100% !important;
    }

    .select2-container {
      width: 100% !important;
    }

    :root {
      --ui-scale: 1;
    }

    body,
    html {
      min-height: 100vh;
      background: #f2f2f2;
    }

    .content,
    .sidebar {
      zoom: var(--ui-scale);
    }

    .content {
      min-height: calc(100vh / var(--ui-scale));
    }

    .sidebar {
      top: 0;
      bottom: 0;
      height: auto !important;
      min-height: calc(100vh / var(--ui-scale)) !important;
      background: #fff !important;
    }

    .topnav-search {
      display: none;
      min-width: 260px;
    }

    .topnav-search.is-visible {
      display: block;
    }

    .topnav-search .form-control {
      font-size: 13px;
      border-radius: 10px;
      padding: 8px 12px;
      border: 1px solid #e2e8f0;
      box-shadow: none;
    }

    .topnav-search .form-control:focus {
      border-color: #94a3b8;
      box-shadow: 0 0 0 2px rgba(148, 163, 184, 0.2);
    }

    .sidebar-search-hidden {
      display: none !important;
    }

    /* width */
    ::-webkit-scrollbar {
      width: 9px !important;
      height: 5px;
    }

    /* Track */
    ::-webkit-scrollbar-track {
      background: #f1f1f1;
    }
  </style>

  <style>
    .btn-copy {
      background: #c2b5071a;
      border-color: #c2b507;
    }

    .btn-copy:hover {
      background: #c2b507;
      border-color: #c2b507;
      color: #fff !important;
    }

    /* 
    .item-menu {
      padding: 14px 30px 14px 30px !important;
    } */

    .icon-side {
      margin-left: auto;
      /* Membuat ikon chevron otomatis menempel di sisi kanan */
      padding-right: 1rem;
      /* Jarak kanan ikon dari batas elemen */
    }
  </style>

  <link rel="stylesheet" href="<?= base_url() ?>assets/css/mobile.css?v=<?= @filemtime(FCPATH . 'assets/css/mobile.css') ?>" type="text/css" media="screen" />

  <link rel="stylesheet" href="<?= base_url() ?>assets/css/mobile-fix.css?v=<?= @filemtime(FCPATH . 'assets/css/mobile-fix.css') ?>">
  <link rel="manifest" href="<?= base_url() ?>manifest.json">
  <meta name="theme-color" content="#6E4FA8">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="Montera">
  <link rel="apple-touch-icon" href="<?= base_url() ?>assets/img/fav.png">
  <script>
    // === APK (Capacitor): daftarkan token FCM ===
    (function(){
      function siapFcm(){
        if (!window.Capacitor || !window.Capacitor.Plugins || !window.Capacitor.Plugins.PushNotifications) return false;
        if (window.Capacitor.isNativePlatform && !window.Capacitor.isNativePlatform()) return false;
        return true;
      }
      function jalanFcm(){
        var PN = window.Capacitor.Plugins.PushNotifications;

        PN.addListener('registration', function(t){
          var fd = new FormData();
          fd.append('token', t.value);
          fd.append('device_os', 'android');
          fd.append('device_model', navigator.userAgent.substring(0, 80));
          fetch('<?= base_url() ?>api/fcm/daftar-token', {
            method: 'POST', body: fd, credentials: 'same-origin'
          });
        });

        PN.addListener('registrationError', function(e){
          console.log('FCM gagal daftar', e);
        });

        PN.addListener('pushNotificationActionPerformed', function(a){
          var d = a && a.notification && a.notification.data;
          if (d && d.url) window.location.href = d.url;
        });

        PN.requestPermissions().then(function(r){
          if (r.receive === 'granted') PN.register();
        });
      }
      if (siapFcm()) { jalanFcm(); }
      else { document.addEventListener('deviceready', function(){ if (siapFcm()) jalanFcm(); }); }
    })();

    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function(){
        navigator.serviceWorker.register('<?= base_url() ?>sw.js').then(function(reg){
          if (!('PushManager' in window) || !('Notification' in window)) return;
          if (Notification.permission === 'denied') return;

          function urlBase64ToUint8Array(base64String) {
            var padding = '='.repeat((4 - base64String.length % 4) % 4);
            var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            var raw = window.atob(base64);
            var out = new Uint8Array(raw.length);
            for (var i = 0; i < raw.length; ++i) out[i] = raw.charCodeAt(i);
            return out;
          }

          function daftarPush(){
            reg.pushManager.getSubscription().then(function(existing){
              // langganan sudah ada di browser -> tetap kirim ke server,
              // karena datanya bisa saja hilang di sisi server.
              if (existing) {
                var r0 = existing.toJSON();
                var f0 = new FormData();
                f0.append('endpoint', r0.endpoint);
                f0.append('p256dh', r0.keys.p256dh);
                f0.append('auth', r0.keys.auth);
                fetch('<?= base_url() ?>kinerja/push_subscribe', { method: 'POST', body: f0, credentials: 'same-origin' });
                return;
              }
              fetch('<?= base_url() ?>kinerja/push_vapid_key', { credentials: 'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(d){
                  if (!d.status || !d.key) return;
                  return reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(d.key)
                  });
                })
                .then(function(sub){
                  if (!sub) return;
                  var raw = sub.toJSON();
                  var fd = new FormData();
                  fd.append('endpoint', raw.endpoint);
                  fd.append('p256dh', raw.keys.p256dh);
                  fd.append('auth', raw.keys.auth);
                  fetch('<?= base_url() ?>kinerja/push_subscribe', { method: 'POST', body: fd, credentials: 'same-origin' });
                })
                .catch(function(e){ console.error('[push] gagal daftar:', e); });
            });
          }

          if (Notification.permission === 'granted') {
            daftarPush();
          } else if (Notification.permission === 'default') {
            Notification.requestPermission().then(function(perm){
              if (perm === 'granted') daftarPush();
            });
          }
        }).catch(function(){});
      });
    }
  </script>
</head>

<body>

  <nav class="sidebar offcanvas-md offcanvas-start <?php if ($_SESSION['minimize_sidebar']) {
                                                      echo 'active';
                                                    } ?>" data-bs-scroll="true" data-bs-backdrop="false">
    <div class="d-flex justify-content-end m-3 d-block d-md-none">
      <button aria-label="Close" data-bs-dismiss="offcanvas" data-bs-target=".sidebar" class="btn p-0 border-0 fs-4" style="background:transparent!important;color:#fff!important">
        <i class="fa fa-close"></i>
      </button>
    </div>
    <?php
    $this->load->library('permission');
    
    $uri_1  = $this->uri->segment(1);
    $uri_2  = $this->uri->segment(2);
    $m = $this->input->get('m');
    $t = $this->input->get('t');
    $brand = $this->input->get('brand');
    
    $user_id = $_SESSION['user']['id'];
    $is_super_admin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] == '1';
    $is_leave_manager = $is_super_admin || in_array($user_id, ['2', '5', '42']);
    
    $CI =& get_instance();
    $CI->load->library('permission');
    
    $can_view_dashboard = $CI->permission->check_permission($user_id, 'dashboard', 'view');
    ?>

    <div class="d-flex mb-3 img-logo" style="padding-left:15px;padding-top:20px;padding-bottom:20px;position:sticky!important;top:0;background:#FFF;z-index:100;
        z-index: 100;box-shadow: 4px 4px 4px #adb5bd1A;">
        <?php if ($can_view_dashboard): ?>
          <a href="<?= base_url() ?>">
        <?php endif; ?>
            <img src="<?= base_url() ?>assets/img/logo-sidebar-v5.png" alt="Logo" style="width:214px;padding-left:10px" />
        <?php if ($can_view_dashboard): ?>
          </a>
        <?php endif; ?>
    </div>
    <!-- <div class="d-flex justify-content-start mb-3">
        <h1 class="sidebar-title text-white">DNX1SCREEN</h1>
      </div> -->
    <?php
    $menu_marketing = $menu_overview = $menu_overview_ads = $menu_overview_kol = $menu_overview_influencer = '';
    $menu_ads = $menu_ads_tiktok = $menu_ads_meta = $menu_ads_shopee = $menu_ads_lazada = '';
    $menu_endorsement = $menu_influencer = $menu_influencer_dummy = $menu_calendar = $menu_payment_fee = $menu_codeboost = '';
    $menu_order_customer = $menu_toko = $menu_order_item = $menu_crm_mg = $menu_crm_pome = $menu_grup_wa = '';
    $menu_operasional = $menu_stock = $menu_product = $menu_product_3rd = $menu_discount = $menu_marketplace = $menu_shipping = '';
    $menu_hr_management = $menu_talent_acquisition = $menu_talent_development = $menu_administration_hr = $menu_quest_level = $menu_position = $menu_benefit = $menu_quest = $menu_milestone = $menu_recruitment_dashboard = $menu_recruitment = $menu_manpower_planning = $menu_onboarding = $menu_asset_management = $menu_contract = $menu_roles = $menu_leave_section = $menu_leave_dashboard = $menu_leave_events = $menu_leave_request = $menu_attendance = $menu_attendance_me = $menu_kpi = $menu_performance_review = $menu_payroll = '';
    $menu_akun = $menu_user = $menu_profile = $menu_logout = $menu_announcement = '';
    
    // Get user permissions for menu visibility using module names from clear_and_replace_modules.sql
    // Access permission library through CodeIgniter instance
    $CI =& get_instance();
    $CI->load->library('permission');
    
    // System Management
    $can_view_report = $CI->permission->check_permission($user_id, 'report', 'view');
    $can_view_expense = $CI->permission->check_permission($user_id, 'expense', 'view');
    
    // Marketing Category - show if user has access to any marketing module
    $can_view_marketing = $CI->permission->check_permission($user_id, 'overview', 'view') ||
                         $CI->permission->check_permission($user_id, 'ads_tiktok', 'view') ||
                         $CI->permission->check_permission($user_id, 'ads_meta', 'view') ||
                         $CI->permission->check_permission($user_id, 'ads_shopee', 'view') ||
                         $CI->permission->check_permission($user_id, 'ads_lazada', 'view') ||
                         $CI->permission->check_permission($user_id, 'influencer', 'view') ||
                         $CI->permission->check_permission($user_id, 'influencer_dummy', 'view') ||
                         $CI->permission->check_permission($user_id, 'endorse_campaign', 'view') ||
                         $CI->permission->check_permission($user_id, 'calendar', 'view') ||
                         $CI->permission->check_permission($user_id, 'payment', 'view') ||
                         $CI->permission->check_permission($user_id, 'codeboost', 'view');
    
    // Marketing Sub-modules
    $can_view_overview = $CI->permission->check_permission($user_id, 'overview', 'view');
    $can_view_advertiser = $CI->permission->check_permission($user_id, 'ads_tiktok', 'view') ||
                          $CI->permission->check_permission($user_id, 'ads_meta', 'view') ||
                          $CI->permission->check_permission($user_id, 'ads_shopee', 'view') ||
                          $CI->permission->check_permission($user_id, 'ads_lazada', 'view');
    $can_view_endorsement = $CI->permission->check_permission($user_id, 'influencer', 'view') ||
                           $CI->permission->check_permission($user_id, 'influencer_dummy', 'view') ||
                           $CI->permission->check_permission($user_id, 'endorse_campaign', 'view') ||
                           $CI->permission->check_permission($user_id, 'calendar', 'view') ||
                           $CI->permission->check_permission($user_id, 'payment', 'view') ||
                           $CI->permission->check_permission($user_id, 'codeboost', 'view');
    
    // Order & Customer Management - show if user has access to any module
    $can_view_order_customer = $CI->permission->check_permission($user_id, 'marketplace_account', 'view') ||
                               $CI->permission->check_permission($user_id, 'transaction', 'view') ||
                               $CI->permission->check_permission($user_id, 'transaction_item', 'view') ||
                               $CI->permission->check_permission($user_id, 'booking_fbs', 'view') ||
                               $CI->permission->check_permission($user_id, 'crm_mg', 'view') ||
                               $CI->permission->check_permission($user_id, 'crm_pome', 'view') ||
                               $CI->permission->check_permission($user_id, 'group_wa', 'view');
    
    // Operations - show if user has access to any operations module
    $can_view_operasional = $CI->permission->check_permission($user_id, 'stock', 'view') ||
                           $CI->permission->check_permission($user_id, 'product', 'view');
    
    // HR Management - show if user has access to any HR module
    $can_view_hr_management = $CI->permission->check_permission($user_id, 'quest_level', 'view') ||
                              $CI->permission->check_permission($user_id, 'position', 'view') ||
                              $CI->permission->check_permission($user_id, 'benefit', 'view') ||
                              $CI->permission->check_permission($user_id, 'quest', 'view') ||
                              $CI->permission->check_permission($user_id, 'milestone', 'view') ||
                              $CI->permission->check_permission($user_id, 'recruitment', 'view') ||
                              $CI->permission->check_permission($user_id, 'manpower_planning', 'view') ||
                              $CI->permission->check_permission($user_id, 'asset_management', 'view') ||
                              $CI->permission->check_permission($user_id, 'user', 'view') ||
                              $CI->permission->check_permission($user_id, 'kpi', 'view');

    // HR Management Sub-modules
    $can_view_talent_acquisition = $CI->permission->check_permission($user_id, 'recruitment', 'view') ||
                                   $CI->permission->check_permission($user_id, 'manpower_planning', 'view') ||
                                   $CI->permission->check_permission($user_id, 'user', 'view');
    $can_view_talent_development = $CI->permission->check_permission($user_id, 'quest_level', 'view') ||
                                   $CI->permission->check_permission($user_id, 'quest', 'view') ||
                                   $CI->permission->check_permission($user_id, 'milestone', 'view') ||
                                   $CI->permission->check_permission($user_id, 'benefit', 'view') ||
                                   $CI->permission->check_permission($user_id, 'kpi', 'view') ||
                                   $CI->permission->check_permission($user_id, 'performance_review', 'view');
    $can_view_administration_hr = $CI->permission->check_permission($user_id, 'user', 'view') ||
                                  $CI->permission->check_permission($user_id, 'position', 'view') ||
                                  $CI->permission->check_permission($user_id, 'asset_management', 'view') ||
                                  $CI->permission->check_permission($user_id, 'payroll', 'view');
    
    // Account Management - show if user has access to any account module  
    $can_view_akun = $CI->permission->check_permission($user_id, 'profile', 'view') ||
                    $CI->permission->check_permission($user_id, 'roles', 'view') ||
                    $CI->permission->check_permission($user_id, 'modules', 'view');
    
    // Individual module permissions for detailed checks
    // Peran packing hanya untuk mencetak label; absen dan izin mereka
    // pakai akun masing-masing. Dashboard, profil, dan modul dasar lain
    // selalu lolos check_permission(), jadi disaring di sini saja --
    // mengubah aturan itu di Permission.php berdampak ke seluruh sistem.
    $peran_packing = FALSE;
    $cek_peran = $CI->db->query(
        "SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id
         WHERE ur.user_id = ? AND r.name = 'packing' LIMIT 1", [$user_id]);
    if ($cek_peran && $cek_peran->num_rows() > 0) { $peran_packing = TRUE; }

    $modules_permissions = [
        // System Management
        'dashboard' => $CI->permission->check_permission($user_id, 'dashboard', 'view'),
        'report' => $CI->permission->check_permission($user_id, 'report', 'view'),
        'report_aset' => $CI->permission->check_permission($user_id, 'report_aset', 'view'),
        'expense' => $CI->permission->check_permission($user_id, 'expense', 'view'),
        'kinerja' => $CI->permission->check_permission($user_id, 'kinerja', 'view'),
        'label' => $CI->permission->check_permission($user_id, 'label', 'view'),
        
        // Marketing
        'overview' => $CI->permission->check_permission($user_id, 'overview', 'view'),
        'ads_tiktok' => $CI->permission->check_permission($user_id, 'ads_tiktok', 'view'),
        'ads_meta' => $CI->permission->check_permission($user_id, 'ads_meta', 'view'),
        'ads_shopee' => $CI->permission->check_permission($user_id, 'ads_shopee', 'view'),
        'ads_lazada' => $CI->permission->check_permission($user_id, 'ads_lazada', 'view'),
        'influencer' => $CI->permission->check_permission($user_id, 'influencer', 'view'),
        'influencer_dummy' => $CI->permission->check_permission($user_id, 'influencer_dummy', 'view'),
        'endorse_campaign' => $CI->permission->check_permission($user_id, 'endorse_campaign', 'view'),
        'calendar' => $CI->permission->check_permission($user_id, 'calendar', 'view'),
        'payment' => $CI->permission->check_permission($user_id, 'payment', 'view'),
        'codeboost' => $CI->permission->check_permission($user_id, 'codeboost', 'view'),
        
        // Order & Customer Management
        'marketplace_account' => $CI->permission->check_permission($user_id, 'marketplace_account', 'view'),
        'transaction' => $CI->permission->check_permission($user_id, 'transaction', 'view'),
        'transaction_item' => $CI->permission->check_permission($user_id, 'transaction_item', 'view'),
        'booking_fbs' => $CI->permission->check_permission($user_id, 'booking_fbs', 'view'),
        'crm_mg' => $CI->permission->check_permission($user_id, 'crm_mg', 'view'),
        'crm_pome' => $CI->permission->check_permission($user_id, 'crm_pome', 'view'),
        'group_wa' => $CI->permission->check_permission($user_id, 'group_wa', 'view'),
        
        // Operations
        'stock' => $CI->permission->check_permission($user_id, 'stock', 'view'),
        'product' => $CI->permission->check_permission($user_id, 'product', 'view'),
        
        // HR Management
        'quest_level' => $CI->permission->check_permission($user_id, 'quest_level', 'view'),
        'position' => $CI->permission->check_permission($user_id, 'position', 'view'),
        'contract' => $CI->permission->check_permission($user_id, 'contract', 'view'),
        'roles' => $CI->permission->check_permission($user_id, 'roles', 'view'),
        'benefit' => $CI->permission->check_permission($user_id, 'benefit', 'view'),
        'quest' => $CI->permission->check_permission($user_id, 'quest', 'view'),
        'milestone' => $CI->permission->check_permission($user_id, 'milestone', 'view'),
        'kpi' => $CI->permission->check_permission($user_id, 'kpi', 'view'),
        'performance_review' => $CI->permission->check_permission($user_id, 'performance_review', 'view'),
        'payroll' => $CI->permission->check_permission($user_id, 'payroll', 'view'),
        'recruitment' => $CI->permission->check_permission($user_id, 'recruitment', 'view'),
        'manpower_planning' => $CI->permission->check_permission($user_id, 'manpower_planning', 'view'),
        'asset_management' => $CI->permission->check_permission($user_id, 'asset_management', 'view'),
        'modules' => $CI->permission->check_permission($user_id, 'modules', 'view'),
        
        // Account Management
        'user' => $CI->permission->check_permission($user_id, 'user', 'view'),
        'profile' => $CI->permission->check_permission($user_id, 'profile', 'view'),
        'announcement' => $CI->permission->check_permission($user_id, 'announcement', 'view'),
        
        // Additional
        'scraper' => $CI->permission->check_permission($user_id, 'scraper', 'view')
    ];

    if ($uri_1 == 'dashboard') {
      $menu_dashboard = 'active';
    } else if ($uri_1 == 'report') {
      $menu_report = 'active';
    } else if ($uri_1 == 'report_aset') {
      $menu_report_aset = 'active';
    } else if ($uri_1 == 'label') {
      $menu_label = 'active';
    } else if ($uri_1 == 'group-wa') {
      $menu_group = 'active';
    } else if ($uri_1 == 'material') {
      $menu_material = 'active';
    } else if ($uri_1 == 'digger') {
      $menu_digger = 'active';
    } else if ($uri_1 == 'layer') {
      $menu_layer = 'active';
    } else if ($uri_1 == 'master-plan') {
      $menu_master_plan = 'active';
    } else if ($uri_1 == 'pricelist-kurs-idr') {
      $menu_kurs = 'active';
    } else if ($uri_1 == 'pricelist-product') {
      $menu_pricelist_product = 'active';
    } else if ($uri_1 == 'pricelist-ammonium-nitrate') {
      $menu_pricelist_ammonium = 'active';
    } else if ($uri_1 == 'rate') {
      $menu_rate = 'active';
    } else if ($uri_1 == 'equipment') {
      $menu_equipment = 'active';
    } else if ($uri_1 == 'site') {
      $menu_site = 'active';
    } else if ($uri_1 == 'customer') {
      $menu_customer = 'active';
    } else if ($uri_1 == 'customer-location') {
      $menu_customer_location = 'active';
    } else if ($uri_1 == 'loading-sheet') {
      $menu_loading_sheet = 'active';
    } else {
      $menu_ads = $menu_endorsement = $menu_marketing = '';
    }



    if ($uri_1 == 'overview') {
      $menu_marketing = 'show';
      $menu_overview = 'active';
    } else if ($uri_1 == 'ads') {
      $menu_marketing = 'show';
      $menu_ads = 'show';
      if ($m == 'tiktok') {
        $menu_ads_tiktok = 'active';
      } else if ($m == 'meta') {
        $menu_ads_meta = 'active';
      } else if ($m == 'shopee') {
        $menu_ads_shopee = 'active';
      } else if ($m == 'lazada') {
        $menu_ads_lazada = 'active';
      }
    } else if ($uri_1 == 'influencer') {
      $menu_marketing = 'show';
      $menu_endorsement = 'show';
      $menu_influencer = 'active';
    } else if ($uri_1 == 'influencer-dummy') {
      $menu_marketing = 'show';
      $menu_endorsement = 'show';
      $menu_influencer_dummy = 'active';
    } else if ($uri_1 == 'payment' || $uri_1 == 'review-endorse') {
      $menu_marketing = 'show';
      $menu_endorsement = 'show';
      $menu_payment_fee = 'active';
    } else if ($uri_1 == 'codeboost') {
      $menu_marketing = 'show';
      $menu_endorsement = 'show';
      $menu_codeboost = 'active';
    } else if ($uri_1 == 'calendar') {
      $menu_marketing = 'show';
      $menu_endorsement = 'show';
      $menu_calendar = 'active';
    } else if ($uri_1 == 'endorse' || $uri_1 == 'endorse-campaign') {
      $menu_marketing = 'show';
      $menu_endorsement = 'show';
      $menu_endorse_campaign = 'active';
    } else if ($uri_1 == 'transaction' || $uri_1 == 'transaction-item' || $uri_1 == 'crm' || $uri_1 == 'group-wa' || $uri_1 == 'booking-fbs') {
      $menu_order_customer = 'show';
      if ($uri_1 == 'transaction') {
        $menu_order = 'active';
      } else if ($uri_1 == 'transaction-item') {
        $menu_order_item = 'active';
      } else if ($uri_1 == 'booking-fbs') {
        $menu_booking_fbs = 'active';
      } else if ($uri_1 == 'crm') {
        $menu_crm = 'active';
      } elseif ($uri_1 == 'group-wa') {
        $menu_grup_wa = 'active';
      }
    } else if ($uri_1 == 'stock' || $uri_1 == 'product' ||  $uri_1 == 'marketplace' || $uri_1 == 'marketplace-account' || $uri_1 == 'shipping' || $uri_1 == 'channel') {
      $menu_operasional = 'show';
      if ($uri_1 == 'stock') {
        $menu_stock = 'active';
      } else if ($uri_1 == 'product' || $uri_1 == 'marketplace' || $uri_1 == 'shipping' || $uri_1 == 'marketplace-account') {
        $menu_product = 'active';
      }
    } else if ($uri_1 == 'attendance' && $uri_2 == 'me') {
      $menu_akun = 'show';
      $menu_attendance_me = 'active';
    } else if ($uri_1 == 'quest_level' || $uri_1 == 'position' || $uri_1 == 'benefit' || $uri_1 == 'quest' || $uri_1 == 'milestone' || $uri_1 == 'kpi' || $uri_1 == 'performance_review' || $uri_1 == 'payroll' || $uri_1 == 'recruitment' || $uri_1 == 'manpower_planning' || $uri_1 == 'onboarding' || $uri_1 == 'asset_management' || $uri_1 == 'contract' || $uri_1 == 'user' || $uri_1 == 'leave' || $uri_1 == 'attendance') {
      $menu_hr_management = 'show';
      if ($uri_1 == 'quest_level') {
        $menu_talent_development = 'show';
        $menu_quest_level = 'active';
      } else if ($uri_1 == 'position') {
        $menu_administration_hr = 'show';
        $menu_position = 'active';
      } else if ($uri_1 == 'benefit') {
        $menu_talent_development = 'show';
        $menu_benefit = 'active';
      } else if ($uri_1 == 'quest') {
        $menu_talent_development = 'show';
        $menu_quest = 'active';
      } else if ($uri_1 == 'milestone') {
        $menu_talent_development = 'show';
        $menu_milestone = 'active'; 
      } else if ($uri_1 == 'kpi') {
        $menu_talent_development = 'show';
        $menu_kpi = 'active';
      } else if ($uri_1 == 'performance_review') {
        $menu_talent_development = 'show';
        $menu_performance_review = 'active';
      } else if ($uri_1 == 'payroll') {
        $menu_administration_hr = 'show';
        $menu_payroll = 'active';
      } else if ($uri_1 == 'recruitment') {
        if ($uri_2 == 'dashboard') {
          $menu_recruitment_dashboard = 'active';
        } else {
          $menu_talent_acquisition = 'show';
          $menu_recruitment = 'active';
        }
      } else if ($uri_1 == 'manpower_planning') {
        $menu_talent_acquisition = 'show';
        $menu_manpower_planning = 'active';
      } else if ($uri_1 == 'onboarding') {
        $menu_administration_hr = 'show';
        $menu_onboarding = 'active';
      } else if ($uri_1 == 'asset_management') {
        $menu_administration_hr = 'show';
        $menu_asset_management = 'active';
      } else if ($uri_1 == 'contract') {
        $menu_administration_hr = 'show';
        $menu_contract = 'active';
      } else if ($uri_1 == 'user') {
        $menu_administration_hr = 'show';
        $menu_user = 'active';
      } else if ($uri_1 == 'leave') {
        $menu_administration_hr = 'show';
        $menu_leave_section = 'show';
        if ($uri_2 == 'events') {
          $menu_leave_events = 'active';
        } else if ($uri_2 == 'request') {
          $menu_leave_request = 'active';
        } else {
          $menu_leave_dashboard = 'active';
        }
      } else if ($uri_1 == 'attendance') {
        $menu_leave_section = 'show';
        $menu_attendance = 'active';
      }
    } else if ($uri_1 == 'profile' || $uri_1 == 'roles' || $uri_1 == 'modules' || $uri_1 == 'announcement') {
      $menu_akun = 'show';
      if ($uri_1 == 'profile') {
        $menu_profile = 'active';
      } else if ($uri_1 == 'roles') {
        $menu_roles = 'active';
      } else if ($uri_1 == 'modules') {
        $menu_modules = 'active';
      } else if ($uri_1 == 'announcement') {
        $menu_announcement = 'active';
      }
    } else {
      $menu_marketing = $menu_overview = '';
      $menu_ads = $menu_ads_tiktok = $menu_ads_meta = $menu_ads_shopee = $menu_ads_lazada = '';
      $menu_endorsement = $menu_influencer = $menu_influencer_dummy = $menu_calendar = $menu_payment_fee = $menu_codeboost = '';
      $menu_order_customer = $menu_toko = $menu_order_item = $menu_booking_fbs = $menu_crm = $menu_grup_wa = '';
      $menu_operasional = $menu_stock = $menu_product = '';
      $menu_hr_management = $menu_talent_acquisition = $menu_talent_development = $menu_administration_hr = $menu_quest_level = $menu_position = $menu_benefit = $menu_quest = $menu_milestone = $menu_kpi = $menu_performance_review = $menu_recruitment_dashboard = $menu_recruitment = $menu_manpower_planning = $menu_onboarding = $menu_asset_management = $menu_contract = $menu_payroll = $menu_roles = $menu_leave_section = $menu_leave_dashboard = $menu_leave_events = $menu_leave_request = '';
      $menu_akun = $menu_user = $menu_profile = $menu_logout = '';
    }
    ?>


    <div class="pt-0 d-flex flex-column gap-5">
      <div class="menu p-0">
        <?php if ($modules_permissions['dashboard'] && !$peran_packing): ?>
          <a href="<?= base_url() ?>dashboard" class="item-menu <?= $menu_dashboard ?>">
            <i class="icon bi bi-house"></i>
            DASHBOARD
          </a>
        <?php endif; ?>
        
        <?php if ($modules_permissions['report']): ?>
          <a href="<?= base_url() ?>report" class="item-menu <?= $menu_report ?>">
            <i class="icon bi bi-graph-up-arrow"></i>
            REPORT
          </a>
        <?php endif; ?>

        <?php if (!empty($modules_permissions['report_aset'])): ?>
          <a href="<?= base_url() ?>report_aset" class="item-menu <?= $menu_report_aset ?? '' ?>">
            <i class="icon bi bi-box-seam"></i>
            ASET
          </a>
        <?php endif; ?>

        <?php if ($modules_permissions['expense']): ?>
          <a href="<?= base_url() ?>expense" class="item-menu <?= $menu_expense ?>">
            <i class="icon bi bi-credit-card"></i>
            PENGELUARAN
          </a>
        <?php endif; ?>

        <?php if (!empty($modules_permissions['kinerja'])): ?>
          <a href="<?= base_url() ?>kinerja" class="item-menu <?= (isset($menu_kinerja) ? $menu_kinerja : (strpos(uri_string(),'kinerja')!==false ? 'active' : '')) ?>">
            <i class="icon bi bi-kanban"></i>
            VALUE OF KINERJA
          </a>
        <?php endif; ?>

        <?php if (!empty($modules_permissions['label'])): ?>
          <a href="<?= base_url() ?>label" class="item-menu <?= (isset($menu_label) ? $menu_label : (strpos(uri_string(),'label')!==false ? 'active' : '')) ?>">
            <i class="icon bi bi-tag"></i>
            LABEL PENGIRIMAN
          </a>
        <?php endif; ?>

        <?php if ($can_view_marketing): ?>
          <a class="item-menu fw-bold <?= $menu_marketing ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between"
            data-bs-toggle="collapse"
            href="#submenu-report"
            role="button"
            aria-expanded="<?= $menu_marketing ? 'true' : 'false'; ?>"
            aria-controls="submenu-report">
            MARKETING
            <i class="bi <?= $menu_marketing ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
          </a>

          <div class="collapse <?= $menu_marketing ? 'show' : '' ?>" id="submenu-report">
            <?php if ($modules_permissions['overview']): ?>
              <a href="<?= base_url() ?>overview" class="ms-2 item-menu <?= $menu_overview ?>">
                OVERVIEW
              </a>
            <?php endif; ?>

            <?php if ($can_view_advertiser): ?>
              <a class="item-menu <?= $menu_ads ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between ms-2"
                data-bs-toggle="collapse"
                href="#submenu-advertiser"
                role="button"
                aria-expanded="<?= $menu_ads ? 'true' : 'false'; ?>"
                aria-controls="submenu-advertiser">
                ADVERTISER
                <i class="bi <?= $menu_ads ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
              </a>
              <div class="collapse <?= $menu_ads ? 'show' : '' ?>" id="submenu-advertiser">
                <?php if ($modules_permissions['ads_tiktok']): ?>
                  <a href="<?= base_url() ?>ads?m=tiktok" class="ms-2 item-menu <?= $menu_ads_tiktok ?>">
                    <i class="icon">
                      <img src="<?= base_url() ?>assets/img/marketplace/3.png" alt="TikTok" class="rounded-circle border" style="width: 35px; height: 35px;">
                    </i>
                    TIKTOK
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['ads_meta']): ?>
                  <a href="<?= base_url() ?>ads?m=meta" class="ms-2 item-menu <?= $menu_ads_meta ?>">
                    <i class="icon">
                      <img src="<?= base_url() ?>assets/img/marketplace/5.png" alt="Meta" class="rounded-circle border" style="width: 35px; height: 35px;">
                    </i>
                    META
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['ads_shopee']): ?>
                  <a href="<?= base_url() ?>ads?m=shopee" class="ms-2 item-menu <?= $menu_ads_shopee ?>">
                    <i class="icon">
                      <img src="<?= base_url() ?>assets/img/marketplace/1.png" alt="Shopee" class="rounded-circle border" style="width: 35px; height: 35px;">
                    </i>
                    SHOPEE
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['ads_lazada']): ?>
                  <a href="<?= base_url() ?>ads?m=lazada" class="ms-2 item-menu <?= $menu_ads_lazada ?>">
                    <i class="icon">
                      <img src="<?= base_url() ?>assets/img/marketplace/2.png" alt="Lazada" class="rounded-circle border" style="width: 35px; height: 35px;">
                    </i>
                    LAZADA
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($can_view_endorsement): ?>
              <a class="item-menu <?= $menu_endorsement ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between ms-2"
                data-bs-toggle="collapse"
                href="#submenu-endorse"
                role="button"
                aria-expanded="<?= $menu_endorsement ? 'true' : 'false'; ?>"
                aria-controls="submenu-endorse">
                ENDORSEMENT
                <i class="bi <?= $menu_endorsement ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
              </a>
              <div class="collapse <?= $menu_endorsement ? 'show' : '' ?>" id="submenu-endorse">
                <?php if ($modules_permissions['influencer']): ?>
                  <a href="<?= base_url() ?>influencer" class="ms-3 item-menu <?= $menu_influencer ?>">
                    <i class="icon bi bi-person-bounding-box"></i>
                    INFLUENCER
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['influencer_dummy']): ?>
                  <a href="<?= base_url() ?>influencer-dummy" class="ms-3 item-menu <?= $menu_influencer_dummy ?>">
                    <i class="icon bi bi-person-lines-fill"></i>
                    INFLUENCER LISTING
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['endorse_campaign']): ?>
                  <a href="<?= base_url() ?>endorse-campaign" class="ms-3 item-menu <?= $menu_endorse_campaign ?>">
                    <i class="icon bi bi-person-video2"></i>
                    ENDORSE CAMPAIGN
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['calendar']): ?>
                  <a href="<?= base_url() ?>calendar?group_by[]=rencana_at&group_by[]=posting_at" class="ms-3 item-menu <?= $menu_calendar ?>">
                    <i class="icon bi bi-calendar-week"></i>
                    ENDORSE CALENDAR
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['payment']): ?>
                  <a href="<?= base_url() ?>payment" class="ms-3 item-menu <?= $menu_payment_fee ?>">
                    <i class="icon bi bi-wallet2"></i>
                    PAYMENT & REVIEW
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['codeboost']): ?>
                  <a href="<?= base_url() ?>codeboost" class="ms-3 item-menu <?= $menu_codeboost ?>">
                    <i class="icon bi bi-box-arrow-in-up"></i>
                    CODEBOOST
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($can_view_order_customer): ?>
          <a class="item-menu fw-bold <?= $menu_order_customer ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between"
            data-bs-toggle="collapse"
            href="#submenu-order-customer"
            role="button"
            aria-expanded="<?= $menu_order_customer ? 'true' : 'false'; ?>"
            aria-controls="submenu-order-customer">
            ORDER & CUSTOMER
            <i class="bi <?= $menu_order_customer ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
          </a>

          <div class="collapse <?= $menu_order_customer ? 'show' : '' ?>" id="submenu-order-customer">
            <!-- <?php if ($modules_permissions['marketplace_account']): ?>
              <a href="<?= base_url() ?>marketplace-account" class="ms-3 item-menu <?= $menu_toko ?>">
                <i class="icon bi bi-houses"></i>
                TOKO
              </a>
            <?php endif; ?> -->

            <?php if ($modules_permissions['transaction']): ?>
              <a href="<?= base_url() ?>transaction" class="ms-3 item-menu <?= $menu_order ?>">
                <i class="icon bi bi-handbag"></i>
                ORDER
              </a>
            <?php endif; ?>

            <?php if ($modules_permissions['booking_fbs']): ?>
              <a href="<?= base_url() ?>booking-fbs" class="ms-3 item-menu <?= $menu_booking_fbs ?>">
                <i class="icon bi bi-truck"></i>
                RESERVASI SHOPEE
              </a>
            <?php endif; ?>

            <?php if ($modules_permissions['transaction_item']): ?>
              <a href="<?= base_url() ?>transaction-item" class="ms-3 item-menu <?= $menu_order_item ?>">
                <i class="icon bi bi-arrow-left-right"></i>
                ORDER ITEM
              </a>
            <?php endif; ?>

            <?php if ($modules_permissions['crm_mg']): ?>
              <a href="<?= base_url() ?>crm" class="ms-3 item-menu <?= $menu_crm ?>">
                <i class="icon bi bi-person-heart"></i>
                CRM
              </a>
            <?php endif; ?>
            
            <?php if ($modules_permissions['group_wa']): ?>
              <a href="<?= base_url() ?>group-wa" class="ms-3 item-menu <?= $menu_group ?>">
                <i class="icon bi bi-whatsapp"></i>
                GRUP WA
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($can_view_operasional): ?>
          <a class="item-menu fw-bold <?= $menu_operasional ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between"
            data-bs-toggle="collapse"
            href="#submenu-operasional"
            role="button"
            aria-expanded="<?= $menu_operasional ? 'true' : 'false'; ?>"
            aria-controls="submenu-operasional">
            OPERASIONAL
            <i class="bi <?= $menu_operasional ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
          </a>

          <div class="collapse <?= $menu_operasional ? 'show' : '' ?>" id="submenu-operasional">
            <?php if ($modules_permissions['stock']): ?>
              <a href="<?= base_url() ?>stock" class="ms-3 item-menu <?= $menu_stock ?>">
                <i class="icon bi bi-arrow-left-right"></i>
                STOK
              </a>
            <?php endif; ?>
            <?php if ($modules_permissions['product'] || $modules_permissions['marketplace-account']): ?>
              <a href="<?= base_url() ?>product" class="ms-3 item-menu <?= $menu_product ?>">
                <i class="icon bi bi-box"></i>
                KONFIGURASI
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($can_view_hr_management): ?>
          <?php if ($CI->uri->segment(1) === "hrd") $menu_hr_management = "show"; ?>
          <a class="item-menu fw-bold <?= $menu_hr_management ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between"
            data-bs-toggle="collapse"
            href="#submenu-hr-management"
            role="button"
            aria-expanded="<?= $menu_hr_management ? 'true' : 'false'; ?>"
            aria-controls="submenu-hr-management">
            HR MANAGEMENT
            <i class="bi <?= $menu_hr_management ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
          </a>

          <div class="collapse <?= $menu_hr_management ? 'show' : '' ?>" id="submenu-hr-management">

            <?php if ($CI->permission->check_permission($user_id, 'hrd', 'view')): ?>
              <a href="<?= base_url() ?>hrd" class="ms-3 item-menu <?= $CI->uri->segment(1) === 'hrd' ? 'active' : '' ?>">
                <i class="icon bi bi-people-fill"></i>
                DATA KARYAWAN (HRD)
              </a>
            <?php endif; ?>

            <?php if ($modules_permissions['recruitment']): ?>
              <a href="<?= base_url() ?>recruitment/dashboard" class="ms-3 item-menu <?= $menu_recruitment_dashboard ?>">
                OVERVIEW HR
              </a>
            <?php endif; ?>

            <?php if ($can_view_talent_acquisition): ?>
              <a class="item-menu <?= $menu_talent_acquisition ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between ms-3"
                data-bs-toggle="collapse"
                href="#submenu-talent-acquisition"
                role="button"
                aria-expanded="<?= $menu_talent_acquisition ? 'true' : 'false'; ?>"
                aria-controls="submenu-talent-acquisition">
                <span class="d-flex align-items-center gap-2">
                  TALENT ACQUISITION
                </span>
                <i class="bi <?= $menu_talent_acquisition ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
              </a>

              <div class="collapse <?= $menu_talent_acquisition ? 'show' : '' ?>" id="submenu-talent-acquisition">
                <?php if ($modules_permissions['recruitment']): ?>
                  <a href="<?= base_url() ?>recruitment" class="ms-4 item-menu <?= $menu_recruitment ?>">
                    <i class="icon bi bi-person-fill-up"></i>
                    RECRUITMENT
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['manpower_planning']): ?>
                  <a href="<?= base_url() ?>manpower_planning" class="ms-4 item-menu <?= $menu_manpower_planning ?>">
                    <i class="icon bi bi-people"></i>
                    MANPOWER PLANNING
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php if ($can_view_talent_development): ?>
              <a class="item-menu <?= $menu_talent_development ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between ms-3"
                data-bs-toggle="collapse"
                href="#submenu-talent-development"
                role="button"
                aria-expanded="<?= $menu_talent_development ? 'true' : 'false'; ?>"
                aria-controls="submenu-talent-development">
                <span class="d-flex align-items-center gap-2">
                  TALENT DEVELOPMENT
                </span>
                <i class="bi <?= $menu_talent_development ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
              </a>

              <div class="collapse <?= $menu_talent_development ? 'show' : '' ?>" id="submenu-talent-development">
                <?php if ($modules_permissions['quest_level']): ?>
                  <a href="<?= base_url() ?>quest_level" class="ms-4 item-menu <?= $menu_quest_level ?>">
                    <i class="icon bi bi-award"></i>
                    QUEST LEVELS
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['benefit']): ?>
                  <a href="<?= base_url() ?>benefit" class="ms-4 item-menu <?= $menu_benefit ?>">
                    <i class="icon bi bi-gift"></i>
                    BENEFITS
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['quest']): ?>
                  <a href="<?= base_url() ?>quest" class="ms-4 item-menu <?= $menu_quest ?>">
                    <i class="icon bi bi-trophy"></i>
                    QUEST MANAGEMENT
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['milestone']): ?>
                  <a href="<?= base_url() ?>milestone" class="ms-4 item-menu <?= $menu_milestone ?>">
                    <i class="icon bi bi-trophy-fill"></i>
                    MILESTONE & LEADERBOARD
                  </a>
                <?php endif; ?>
                <?php if (!empty($modules_permissions['kpi'])): ?>
                  <a href="<?= base_url() ?>kpi" class="ms-4 item-menu <?= $menu_kpi ?>">
                    <i class="icon bi bi-speedometer2"></i>
                    KPI
                  </a>
                <?php endif; ?>
                <?php if (!empty($modules_permissions['performance_review'])): ?>
                  <a href="<?= base_url() ?>performance_review" class="ms-4 item-menu <?= $menu_performance_review ?>">
                    <i class="icon bi bi-clipboard-check"></i>
                    PENILAIAN KINERJA
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php if ($is_leave_manager): ?>
              <a class="item-menu <?= $menu_leave_section ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between ms-3"
                data-bs-toggle="collapse"
                href="#submenu-leave"
                role="button"
                aria-expanded="<?= $menu_leave_section ? 'true' : 'false'; ?>"
                aria-controls="submenu-leave">
                <span class="d-flex align-items-center gap-2">
                  ATTENDANCE MANAGEMENT
                </span>
                <i class="bi <?= $menu_leave_section ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
              </a>
              <div class="collapse <?= $menu_leave_section ? 'show' : '' ?>" id="submenu-leave">
                <a href="<?= base_url() ?>leave" class="ms-4 item-menu <?= $menu_leave_dashboard ?>">
                  <i class="icon bi bi-calendar-check"></i>
                  LEAVE MANAGEMENT
                </a>
                <a href="<?= base_url() ?>leave/events" class="ms-4 item-menu <?= $menu_leave_events ?>">
                  <i class="icon bi bi-calendar-event"></i>
                  CALENDAR EVENT
                </a>
                <a href="<?= base_url() ?>attendance" class="ms-4 item-menu <?= $menu_attendance ?>">
                  <i class="icon bi bi-fingerprint"></i>
                  KEHADIRAN
                </a>
              </div>
            <?php endif; ?>
            <?php if ($can_view_administration_hr): ?>
              <a class="item-menu <?= $menu_administration_hr ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between ms-3"
                data-bs-toggle="collapse"
                href="#submenu-administration-hr"
                role="button"
                aria-expanded="<?= $menu_administration_hr ? 'true' : 'false'; ?>"
                aria-controls="submenu-administration-hr">
                <span class="d-flex align-items-center gap-2">
                  ADMINISTRATION HR
                </span>
                <i class="bi <?= $menu_administration_hr ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
              </a>

              <div class="collapse <?= $menu_administration_hr ? 'show' : '' ?>" id="submenu-administration-hr">
                <?php if ($modules_permissions['user']): ?>
                  <a href="<?= base_url() ?>user" class="ms-4 item-menu <?= $menu_user ?>">
                    <i class="icon bi bi-database"></i>
                    DATABASE MANAGEMENT
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['position']): ?>
                  <a href="<?= base_url() ?>position" class="ms-4 item-menu <?= $menu_position ?>">
                    <i class="icon bi bi-briefcase"></i>
                    POSITION MANAGEMENT
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['user']): ?>
                  <a href="<?= base_url() ?>onboarding" class="ms-4 item-menu <?= $menu_onboarding ?>">
                    <i class="icon bi bi-person-check"></i>
                    ONBOARDING & OFFBOARDING
                  </a>
                <?php endif; ?>
                <?php if (!empty($modules_permissions['contract'])): ?>
                  <a href="<?= base_url() ?>contract" class="ms-4 item-menu <?= $menu_contract ?>">
                    <i class="icon bi bi-file-earmark-text"></i>
                    RIWAYAT KONTRAK
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['asset_management']): ?>
                  <a href="<?= base_url() ?>asset_management" class="ms-4 item-menu <?= $menu_asset_management ?>">
                    <i class="icon bi bi-box-seam"></i>
                    MANAJEMEN ASET
                  </a>
                <?php endif; ?>
                <?php if (!empty($modules_permissions['payroll'])): ?>
                  <a href="<?= base_url() ?>payroll" class="ms-4 item-menu <?= $menu_payroll ?>">
                    <i class="icon bi bi-receipt"></i>
                    PAYROLL
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($can_view_akun): ?>
          <a class="item-menu fw-bold <?= $menu_akun ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between"
            data-bs-toggle="collapse"
            href="#submenu-akun"
            role="button"
            aria-expanded="<?= $menu_akun ? 'true' : 'false'; ?>"
            aria-controls="submenu-akun">
            AKUN
            <i class="bi <?= $menu_akun ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
          </a>

          <div class="collapse <?= $menu_akun ? 'show' : '' ?>" id="submenu-akun">
            <?php if (!$peran_packing): ?>
              <a href="<?= base_url() ?>attendance/me" class="ms-3 item-menu <?= $menu_attendance_me ?>">
                <i class="icon bi bi-fingerprint"></i>
                ABSENSI
              </a>
              <a href="<?= base_url() ?>leave/request" class="ms-3 item-menu <?= $menu_leave_request ?>">
                <i class="icon bi bi-calendar2-check"></i>
                LEAVE REQUEST
              </a>
            <?php endif; ?>
            <?php if ($modules_permissions['roles']): ?>
              <a href="<?= base_url() ?>roles" class="ms-3 item-menu <?= $menu_roles ?>">
                <i class="icon bi bi-shield-check"></i>
                ROLE MANAGEMENT
              </a>
            <?php endif; ?>
            <?php if ($modules_permissions['modules']): ?>
              <a href="<?= base_url() ?>modules" class="ms-3 item-menu <?= $uri_1 == 'modules' ? 'active' : '' ?>">
                <i class="icon bi bi-shield-lock"></i>
                MODULES & PERMISSIONS
              </a>
            <?php endif; ?>
            <?php if ($CI->permission->check_permission($user_id, 'announcement', 'view')): ?>
              <a href="<?= base_url() ?>announcement" class="ms-3 item-menu <?= $uri_1 == 'announcement' ? 'active' : '' ?>">
                <i class="icon bi bi-megaphone"></i>
                ANNOUNCEMENT
              </a>
            <?php endif; ?>
            <?php if ($modules_permissions['profile']): ?>
              <a href="<?= base_url() ?>profile" class="ms-3 item-menu <?= $menu_profile ?>">
                <i class="icon bi bi-person-circle"></i>
                AKUN SAYA
              </a>
            <?php endif; ?>
            <a href="<?= base_url() ?>auth/logout-process" class="ms-3 item-menu <?= $menu_logout ?>">
              <i class="icon bi bi-door-open"></i>
              KELUAR
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <?php
  $style_1 = '';
  $style_2 = '';
  $style_3 = '';
  ?>


  <!-- Main Content -->
  <main class="content div-dashboard <?php if ($_SESSION['minimize_sidebar']) {
                                        echo 'active';
                                      } ?>">

    <nav class="navbar navbar-expand-lg" style="top: 0;
        position: sticky;
        background: #FFF;
        padding: 10px 30px;
        z-index: 100;box-shadow: 4px 4px 4px #adb5bd1A;
    ">
      <div class="container-fluid" style="padding-right: 0px; padding-left: 0px;">
          <div class="avatar-icon">
              <button class="sidebarCollapseDefault btn p-0 border-0 d-none d-md-block mt-0 mb-0" aria-label="Hamburger Button" style="padding-top:0px!important;">
                  <i class="mdi menu-sidebar mdi-menu"></i>
              </button>
              <button data-bs-toggle="offcanvas" data-bs-target=".sidebar" aria-controls="sidebar" aria-label="Hamburger Button" class="sidebarCollapseMobile btn p-0 border-0 d-block d-md-none" style="padding-top:0px!important;">
                  <i class="mdi menu-sidebar mdi-menu"></i>
              </button>
          </div>
          
          <div class="d-flex align-items-center justify-content-end gap-4">
              <div class="topnav-search" id="topnav-search">
                  <input id="sidebar-search-input" type="text" class="form-control" placeholder="Cari menu... (Cmd+K)" aria-label="Search sidebar menu" autocomplete="off">
              </div>
              <!-- Notification Bell -->
              <div class="notification-container" style="position: relative; margin-right: 10px;">
                  <button class="notification-bell-button" type="button" onclick="toggleNotifications()" aria-label="Buka notifikasi">
                      <i class="bi bi-bell"></i>
                      <span class="notification-bell-badge" id="notificationBadge" style="display: none;">
                          0
                      </span>
                  </button>

                  <!-- Dropdown Notifikasi -->
                  <div class="dropdown-menu p-0 notification-dropdown-panel" id="notificationDropdown" style="display: none;">
                      <div class="notification-preview-header">
                          <div>
                              <h6>Notifikasi</h6>
                              <span id="notificationPreviewCategoryLabel">Finance</span>
                          </div>
                          <button type="button" class="notification-preview-icon-button" onclick="markAllRead()" title="Tandai semua dibaca">
                              <i class="bi bi-check2-all"></i>
                          </button>
                      </div>
                      <div class="notification-preview-tabs" id="notificationCategoryTabs">
                          <button class="notification-preview-tab js-notification-category-tab active" type="button" data-category="finance">
                              <i class="bi bi-wallet2"></i>
                              <span>Finance</span>
                          </button>
                          <button class="notification-preview-tab js-notification-category-tab" type="button" data-category="team">
                              <i class="bi bi-people"></i>
                              <span>Team</span>
                          </button>
                          <button class="notification-preview-tab js-notification-category-tab" type="button" data-category="absensi">
                              <i class="bi bi-fingerprint"></i>
                              <span>Absensi</span>
                          </button>
                      </div>
                      <div class="notification-preview-list" id="notificationList">
                          <div class="notification-preview-empty">
                              <i class="bi bi-bell-slash"></i>
                              <p>Tidak ada notifikasi</p>
                          </div>
                      </div>
                      <div class="notification-preview-footer">
                          <a href="<?= base_url('notifications?category=finance') ?>" id="notificationViewAll">Buka notifikasi</a>
                      </div>
                  </div>
              </div>
                                        
              <!-- Profile Dropdown -->
              <div class="dropdown">
                  <button class="btn p-0" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; background: none;">
                      <?php
                      $img = $_SESSION['user']['img'];
                      if ($img == "") {
                          $img = base_url() . '/assets/img/user/default.png';
                      } else {
                          $img = base_url() . '/assets/img/user/' . $img . '?token=' . DATE("Ymdhis", strtotime($_SESSION['user']['updated_at']));
                      }
                      ?>
                      <img src="<?= $img ?>" class="avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; cursor: pointer;">
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="profileDropdown" style="border: none; border-radius: 12px; padding: 8px; min-width: 200px;">
                      <!-- User Info Header -->
                      <li class="dropdown-header px-3 py-2" style="background-color: #f8f9fa; border-radius: 8px; margin-bottom: 8px;">
                          <div class="d-flex align-items-center">
                              <img src="<?= $img ?>" class="me-2" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                              <div>
                                  <div class="fw-bold text-dark" style="font-size: 14px;"><?= $_SESSION['user']['full_name'] ?></div>
                                  <small class="text-muted" style="font-size: 12px;"><?= $_SESSION['user']['role_text'] ?></small>
                              </div>
                          </div>
                      </li>
                      
                      <!-- Profile Link -->
                      <li>
                          <a class="dropdown-item d-flex align-items-center py-2 px-3" href="<?= base_url() ?>profile" style="border-radius: 8px; transition: all 0.2s;">
                              <i class="bi bi-person-circle me-2 text-primary" style="font-size: 16px;"></i>
                              <span>My Profile</span>
                          </a>
                      </li>
                      
                      <!-- Divider -->
                      <li><hr class="dropdown-divider my-2"></li>
                      
                      <!-- Logout Link -->
                      <li>
                          <a class="dropdown-item d-flex align-items-center py-2 px-3 text-danger" href="javascript:void(0)" style="border-radius: 8px; transition: all 0.2s;" 
                            onclick="showLogoutConfirmation();">
                              <i class="bi bi-box-arrow-right me-2" style="font-size: 16px;"></i>
                              <span>Logout</span>
                          </a>
                      </li>
                  </ul>
              </div>
          </div>
      </div>
  </nav>


    <div class="content-body">

      <div class="w-100 pt-0 pb-5">
        <?= $content ?>
      </div>
    </div>
    <!-- <footer>
        <div class="row">
          <div class="col-lg-6 text-center text-lg-start">
            <p class="mb-0">Copyright <a class="a-green" href="https://karyastudio.com" target="_blank">Karya Studio Teknologi Digital</a> &#169; 2022</p>
          </div>
          <div class="col-lg-6 text-center text-lg-end">
            <p class="mb-0">PT Kargo Maritim Indonesia V.1.0.1</p>
          </div>
        </div>
      </footer> -->
  </main>
  <style>
  .notification-container {
      position: relative;
      margin-right: 15px;
  }

  .notification-bell-button {
      width: 38px;
      height: 38px;
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 0;
      border-radius: 8px;
      background: transparent;
      color: #4b6fae;
      transition: background-color 0.2s ease, color 0.2s ease;
  }

  .notification-bell-button:hover {
      background: #f1f5f9;
      color: #0f766e;
  }

  .notification-bell-button i {
      font-size: 20px;
      line-height: 1;
  }

  .notification-bell-badge {
      position: absolute;
      top: 2px;
      right: 1px;
      min-width: 18px;
      height: 18px;
      padding: 0 5px;
      background: #e95558;
      color: white;
      font-size: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      border: 2px solid #fff;
      border-radius: 999px;
      line-height: 1;
  }

  .notification-dropdown-panel {
      position: absolute;
      top: calc(100% + 8px);
      right: 0 !important;
      left: auto !important;
      width: min(380px, calc(100vw - 32px));
      min-width: 320px;
      background: white;
      border: 1px solid #f0f0f0;
      border-radius: 2px;
      box-shadow: 0 6px 16px -8px rgba(0, 0, 0, 0.08), 0 9px 28px 0 rgba(0, 0, 0, 0.05), 0 12px 48px 16px rgba(0, 0, 0, 0.03);
      z-index: 1100;
      overflow: hidden;
      display: none;
      padding: 0;
  }

  .notification-preview-header {
      padding: 10px 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      border-bottom: 1px solid #f0f0f0;
  }

  .notification-preview-header h6 {
      margin: 0;
      font-weight: 500;
      font-size: 14px;
      line-height: 1.4;
      color: rgba(0, 0, 0, 0.85);
  }

  .notification-preview-header span {
      display: inline;
      margin: 0;
      color: rgba(0, 0, 0, 0.45);
      font-size: 12px;
      line-height: 1.4;
  }

  .notification-preview-icon-button {
      width: 28px;
      height: 28px;
      border: 1px solid #d9d9d9;
      border-radius: 2px;
      background: #fff;
      color: rgba(0, 0, 0, 0.65);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 28px;
      font-size: 13px;
      transition: all 0.3s;
  }

  .notification-preview-icon-button:hover {
      border-color: #40a9ff;
      color: #40a9ff;
  }

  .notification-preview-tabs {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 0 14px;
      border-bottom: 1px solid #f0f0f0;
      overflow-y: auto;
      overflow-x: auto;
  }

  .notification-preview-tab {
      min-height: 36px;
      padding: 0;
      border: 0;
      border-bottom: 2px solid transparent;
      background: transparent;
      color: rgba(0, 0, 0, 0.65);
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-weight: 500;
      font-size: 13px;
      white-space: nowrap;
      cursor: pointer;
      transition: color 0.3s;
  }

  .notification-preview-tab:hover {
      color: #40a9ff;
  }

  .notification-preview-tab.active {
      color: #1890ff;
      border-bottom-color: #1890ff;
  }

  .notification-preview-tab-count {
      min-width: 20px;
      height: 18px;
      padding: 0 6px;
      border-radius: 2px;
      background: #f0f0f0;
      color: rgba(0, 0, 0, 0.65);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: normal;
      line-height: 18px;
  }

  .notification-preview-tab-count.has-unread {
      background: #ff4d4f;
      color: #fff;
  }

  .notification-preview-list {
      max-height: 340px;
      overflow-y: auto;
      background: #fff;
  }

  .notification-preview-item {
      padding: 10px 14px;
      border-bottom: 1px solid #f0f0f0;
      cursor: pointer;
      transition: background-color 0.3s ease;
  }

  .notification-preview-item:hover {
      background: #fafafa;
  }

  .notification-preview-item.unread {
      background: #e6f7ff;
      box-shadow: inset 2px 0 0 #1890ff;
  }

  .notification-preview-item-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 4px;
  }

  .notification-preview-pill {
      max-width: 170px;
      height: 20px;
      padding: 0 7px;
      border-radius: 2px;
      background: #f0f0f0;
      color: rgba(0, 0, 0, 0.65);
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      font-weight: normal;
      line-height: 20px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
  }

  .notification-preview-time {
      color: rgba(0, 0, 0, 0.45);
      font-size: 11px;
      flex: 0 0 auto;
      white-space: nowrap;
  }

  .notification-preview-title {
      color: rgba(0, 0, 0, 0.85);
      font-weight: 500;
      font-size: 13px;
      line-height: 1.4;
      margin-bottom: 2px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
  }

  .notification-preview-message {
      color: rgba(0, 0, 0, 0.65);
      font-size: 12px;
      line-height: 1.45;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
  }

  .notification-preview-empty {
      padding: 28px 14px;
      text-align: center;
      color: rgba(0, 0, 0, 0.45);
  }

  .notification-preview-empty i {
      display: block;
      font-size: 22px;
      margin-bottom: 6px;
  }

  .notification-preview-empty p {
      margin: 0;
      font-size: 12px;
  }

  .notification-preview-footer {
      padding: 8px 14px;
      border-top: 1px solid #f0f0f0;
      background: #fff;
  }

  .notification-preview-footer a {
      height: 30px;
      border-radius: 2px;
      background: #fff;
      border: 1px solid #d9d9d9;
      color: rgba(0, 0, 0, 0.65);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: normal;
      text-decoration: none;
      transition: all 0.3s;
  }

  .notification-preview-footer a:hover {
      border-color: #40a9ff;
      color: #40a9ff;
  }

  @media (max-width: 576px) {
      .notification-dropdown-panel {
          min-width: 0;
          right: -54px !important;
      }

      .notification-preview-header h6 {
          font-size: 20px;
      }

      .notification-preview-tabs {
          gap: 18px;
      }

      .notification-preview-item-top {
          align-items: flex-start;
          flex-direction: column;
          gap: 6px;
      }

      .notification-preview-pill {
          max-width: 100%;
      }
  }
  </style>

  <style>
    .select2-container--open .select2-dropdown {
      z-index: 10000 !important;
    }

    .h-100 {
      height: 100%;
    }

    .divIcon {
      width: 55px;
      height: 55px;
      border-radius: 10px;
      object-fit: cover;
    }
  </style>
  <script>
    $('.select2').select2();
  </script>


  <script>
    let notificationDropdownOpen = false;
    let activeNotificationCategory = 'finance';

    function toggleNotifications() {
        const dropdown = $('#notificationDropdown');

        if (notificationDropdownOpen) {
            dropdown.hide();
            notificationDropdownOpen = false;
        } else {
            dropdown.show();
            notificationDropdownOpen = true;
            loadNotifications(activeNotificationCategory);
        }
    }

    $(document).on('click', function(event) {
        const container = $('.notification-container');
        if (!container.is(event.target) && !container.has(event.target).length && notificationDropdownOpen) {
            $('#notificationDropdown').hide();
            notificationDropdownOpen = false;
        }
    });

    var originalTitle = document.title;

    function loadNotifications(category) {
      $.ajax({
          url: '<?= base_url("notifications/get_notifications") ?>',
          method: 'GET',
          data: {
              limit: 10,
              category: category || activeNotificationCategory
          },
          dataType: 'json',
          success: function(data) {
              if (data.error === 'session_expired') {
                  window.location.href = '<?= base_url("auth/login") ?>';
                  return;
              }

              activeNotificationCategory = data.active_category || activeNotificationCategory;
              renderNotificationTabs(data.categories, data.category_counts, activeNotificationCategory);
              updateNotificationViewAll(activeNotificationCategory);
              displayNotifications(data.notifications || []);
              updateNotificationBadge(data.unread_count);

              if (data.unread_count > 0) {
                  document.title = '(' + data.unread_count + ') ' + originalTitle;
              } else {
                  document.title = originalTitle;
              }
          },
          error: function(xhr, status, error) {
              console.error('Error loading notifications:', error);
          }
      });
    }


    const currentNotificationUserName = <?= json_encode($_SESSION['user']['full_name'] ?? '') ?>;
    let notificationCache = {};

    function getNotificationTargetUrl(notification) {
        if (notification && notification.action_url) {
            return notification.action_url;
        }

        const title = String(notification.title || '').toLowerCase();
        const message = String(notification.message || '').toLowerCase();
        const text = `${title} ${message}`;
        const reviewUrl = '<?= base_url("review-endorse?keyword_category=SPV&keyword=") ?>' + encodeURIComponent(currentNotificationUserName);

        if (text.includes('payment')) {
            return '<?= base_url("payment") ?>';
        }

        if (text.includes('cuti') || text.includes('leave') || text.includes('izin')) {
            return '<?= base_url("leave") ?>';
        }

        if (text.includes('review')) {
            return reviewUrl;
        }

        return '<?= base_url("notifications") ?>';
    }

    async function handleNotificationClick(notification) {
        try {
            if (!notification || !notification.id) {
                return;
            }

            await markRead(notification.id, false);
            window.location.href = getNotificationTargetUrl(notification);
        } catch (error) {
            console.error('Error in notification process:', error);
        }
    }

    $(document).on('click', '.js-notification-item', function() {
        const notificationId = $(this).data('notification-id');
        handleNotificationClick(notificationCache[notificationId]);
    });

    function escapeHtml(value) {
      return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function formatBadgeCount(count) {
        count = Number(count || 0);
        return count > 99 ? '99+' : String(count);
    }

    function renderNotificationTabs(categories, counts, activeCategory) {
        if (!categories) {
            return;
        }

        const tabsContainer = $('#notificationCategoryTabs');
        let html = '';

        Object.keys(categories).forEach(function(categoryKey) {
            const category = categories[categoryKey] || {};
            const categoryCount = counts && counts[categoryKey] ? counts[categoryKey] : {};
            const total = Number(categoryCount.total || 0);
            const unread = Number(categoryCount.unread || 0);
            const displayCount = unread > 0 ? unread : total;
            const activeClass = categoryKey === activeCategory ? 'active' : '';
            const unreadClass = unread > 0 ? 'has-unread' : '';
            const icon = category.icon || 'bi-bell';
            const label = category.label || categoryKey;

            html += `
                <button class="notification-preview-tab js-notification-category-tab ${activeClass}" type="button" data-category="${escapeHtml(categoryKey)}">
                    <i class="bi ${escapeHtml(icon)}"></i>
                    <span>${escapeHtml(label)}</span>
                    ${displayCount > 0 ? `<span class="notification-preview-tab-count ${unreadClass}">${formatBadgeCount(displayCount)}</span>` : ''}
                </button>
            `;
        });

        tabsContainer.html(html);

        const activeMeta = categories[activeCategory] || {};
        $('#notificationPreviewCategoryLabel').text(activeMeta.description || activeMeta.label || 'Notifikasi');
    }

    function updateNotificationViewAll(category) {
        $('#notificationViewAll').attr('href', '<?= base_url("notifications?category=") ?>' + encodeURIComponent(category || 'finance'));
    }

    function switchNotificationCategory(category) {
        if (!category || category === activeNotificationCategory) {
            return;
        }

        activeNotificationCategory = category;
        loadNotifications(category);
    }

    $(document).on('click', '.js-notification-category-tab', function(event) {
        event.preventDefault();
        switchNotificationCategory($(this).data('category'));
    });

    function displayNotifications(notifications) {
      const listContainer = $('#notificationList');
      notificationCache = {};

      if (notifications.length === 0) {
          listContainer.html(`
              <div class="notification-preview-empty">
                  <i class="bi bi-bell-slash"></i>
                  <p>Tidak ada notifikasi</p>
              </div>
          `);
          return;
      }

      let html = '';
      notifications.forEach(notification => {
          notificationCache[notification.id] = notification;
          const unreadClass = notification.is_read == '0' ? 'unread' : '';
          const timeText = formatNotificationDate(notification.created_at);
          const categoryIcon = notification.category_icon || 'bi-bell';
          const subcategory = notification.subcategory || notification.category_label || 'Notifikasi';

          html += `
              <div class="notification-preview-item js-notification-item ${unreadClass}" data-notification-id="${escapeHtml(notification.id)}">
                  <div class="notification-preview-item-top">
                      <span class="notification-preview-pill">
                          <i class="bi ${escapeHtml(categoryIcon)}"></i>
                          ${escapeHtml(subcategory)}
                      </span>
                      <span class="notification-preview-time">${escapeHtml(timeText)}</span>
                  </div>
                  <div class="notification-preview-title">${escapeHtml(notification.title)}</div>
                  <div class="notification-preview-message" style="white-space:pre-line">${escapeHtml(notification.message)}</div>
              </div>
          `;
      });

      listContainer.html(html);
    }

    function updateNotificationBadge(count) {
        const badge = $('#notificationBadge');
        count = Number(count || 0);
        if (count > 0) {
            badge.text(formatBadgeCount(count));
            badge.css('display', 'flex');
        } else {
            badge.hide();
        }
    }

    async function markRead(notificationId, reloadList = true) {
      try {
          const response = await $.ajax({
              url: '<?= base_url("notifications/mark_read") ?>',
              method: 'POST',
              dataType: 'json',
              contentType: 'application/json',
              data: JSON.stringify({
                  notification_id: notificationId
              })
          });

          if (response.success && reloadList) {
              loadNotifications(activeNotificationCategory);
          }
          return response;
      } catch (error) {
          console.error('Error marking notification as read:', error);
          throw error;
      }
    }

    function markAllRead() {
        $.ajax({
            url: '<?= base_url("notifications/mark_all_read") ?>',
            method: 'POST',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    loadNotifications(activeNotificationCategory);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error marking all notifications as read:', error);
            }
        });
    }

    function formatNotificationDate(dateString) {
        const normalizedDate = String(dateString || '').replace(' ', 'T');
        const date = new Date(normalizedDate);
        if (Number.isNaN(date.getTime())) {
            return '';
        }

        const pad = (value) => String(value).padStart(2, '0');
        return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    $(document).ready(function() {
        $.ajax({
            url: '<?= base_url("notifications/get_unread_count") ?>',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                updateNotificationBadge(data.count);
            },
            error: function(xhr, status, error) {
                console.error('Error loading notification count:', error);
            }
        });
    });

    setInterval(function() {
      $.ajax({
          url: '<?= base_url("notifications/get_unread_count") ?>',
          method: 'GET',
          dataType: 'json',
          success: function(data) {
              updateNotificationBadge(data.count);

              if (data.count > 0) {
                  document.title = '(' + data.count + ') ' + originalTitle;
              } else {
                  document.title = originalTitle;
              }
          },
          error: function(xhr, status, error) {
              console.error('Error auto-refreshing notification count:', error);
          }
      });
    }, 30000);
  </script>
  <script>
    $(document).ready(function() {
      $('.sidebarCollapseDefault').on('click', function() {
        $('.sidebar').toggleClass('active');
        $('.content').toggleClass('active');
        $.ajax({
          dataType: "json",
          url: '<?= base_url() ?>ajax/minimize-sidebar',
          success: function(html) {}
        });
      });
    });

    document.addEventListener("DOMContentLoaded", function() {
      const sidebar = document.querySelector('.sidebar');
      const searchWrapper = document.getElementById('topnav-search');
      const searchInput = document.getElementById('sidebar-search-input');
      if (!sidebar || !searchInput) return;

      const menuItems = Array.from(sidebar.querySelectorAll('.menu .item-menu'));
      const collapses = Array.from(sidebar.querySelectorAll('.menu .collapse'));
      const collapseById = new Map();

      collapses.forEach((collapse) => {
        collapse.dataset.initialShow = collapse.classList.contains('show') ? '1' : '0';
        if (collapse.id) {
          collapseById.set(collapse.id, collapse);
        }
      });

      const setToggleIcon = (toggle, isOpen) => {
        if (!toggle) return;
        const icon = toggle.querySelector('.icon-side');
        if (!icon) return;
        icon.classList.toggle('bi-chevron-up', isOpen);
        icon.classList.toggle('bi-chevron-down', !isOpen);
      };

      const setCollapseState = (collapse, shouldShow) => {
        if (!collapse) return;
        if (window.bootstrap && window.bootstrap.Collapse) {
          const instance = bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false });
          if (shouldShow) {
            instance.show();
          } else {
            instance.hide();
          }
        } else {
          collapse.classList.toggle('show', shouldShow);
        }
        const toggle = sidebar.querySelector(`[href="#${collapse.id}"]`);
        setToggleIcon(toggle, shouldShow);
      };

      const resetSearch = () => {
        menuItems.forEach((item) => item.classList.remove('sidebar-search-hidden'));
        collapses.forEach((collapse) => {
          const shouldShow = collapse.dataset.initialShow === '1';
          setCollapseState(collapse, shouldShow);
          delete collapse.dataset.searchShow;
        });
      };

      const runSearch = () => {
        const query = searchInput.value.trim().toLowerCase();
        if (!query) {
          resetSearch();
          return;
        }

        menuItems.forEach((item) => item.classList.add('sidebar-search-hidden'));
        collapses.forEach((collapse) => {
          collapse.dataset.searchShow = '0';
        });

        menuItems.forEach((item) => {
          const text = item.textContent.replace(/\s+/g, ' ').trim().toLowerCase();
          const isMatch = text.includes(query);
          if (!isMatch) return;

          item.classList.remove('sidebar-search-hidden');
          const directCollapseId = item.getAttribute('href');
          if (directCollapseId && directCollapseId.startsWith('#')) {
            const collapse = collapseById.get(directCollapseId.slice(1));
            if (collapse) {
              collapse.dataset.searchShow = '1';
            }
          }

          let parentCollapse = item.closest('.collapse');
          while (parentCollapse) {
            parentCollapse.dataset.searchShow = '1';
            const parentToggle = sidebar.querySelector(`[href="#${parentCollapse.id}"]`);
            if (parentToggle) {
              parentToggle.classList.remove('sidebar-search-hidden');
            }
            parentCollapse = parentCollapse.parentElement.closest('.collapse');
          }
        });

        collapses.forEach((collapse) => {
          const shouldShow = collapse.dataset.searchShow === '1';
          setCollapseState(collapse, shouldShow);
          if (!shouldShow) {
            const toggle = sidebar.querySelector(`[href="#${collapse.id}"]`);
            if (toggle) {
              toggle.classList.add('sidebar-search-hidden');
            }
          }
        });
      };

      searchInput.addEventListener('input', runSearch);
      searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          searchInput.value = '';
          resetSearch();
          if (searchWrapper) {
            searchWrapper.classList.remove('is-visible');
          }
        }
      });
      searchInput.addEventListener('blur', () => {
        if (!searchInput.value.trim() && searchWrapper) {
          searchWrapper.classList.remove('is-visible');
        }
      });

      document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
          event.preventDefault();
          if (searchWrapper) {
            searchWrapper.classList.add('is-visible');
          }
          searchInput.focus();
          searchInput.select();
        }
      });
    });

    function func_pass_1() {
      var x = document.getElementById("password_1");
      var show_eye_1 = document.getElementById("show_eye_1");
      var hide_eye_1 = document.getElementById("hide_eye_1");
      hide_eye_1.classList.remove("d-none");
      if (x.type === "password") {
        x.type = "text";
        show_eye_1.style.display = "none";
        hide_eye_1.style.display = "block";
      } else {
        x.type = "password";
        show_eye_1.style.display = "block";
        hide_eye_1.style.display = "none";
      }
    }

    // document.addEventListener("DOMContentLoaded", function() {
    //   // Prevent collapse from closing when clicking inside
    //   document.querySelectorAll('.collapse').forEach(function(collapse) {
    //     collapse.addEventListener('click', function(event) {
    //       event.stopPropagation();
    //     });
    //   });

    //   // Ensure toggle remains open when active
    //   const toggles = document.querySelectorAll('[data-bs-toggle="collapse"]');
    //   toggles.forEach(function(toggle) {
    //     toggle.addEventListener('click', function(event) {
    //       const target = document.querySelector(toggle.getAttribute('href'));
    //       if (target.classList.contains('show')) {
    //         event.preventDefault(); // Prevent collapsing the already open element
    //       }
    //     });
    //   });
    // });

    document.addEventListener("DOMContentLoaded", function() {
      // Select all elements with `data-bs-toggle="collapse"`
      const toggles = document.querySelectorAll('[data-bs-toggle="collapse"]');

      toggles.forEach(function(toggle) {
        // Get the target collapse element
        const targetSelector = toggle.getAttribute('href');
        const target = document.querySelector(targetSelector);

        if (target) {
          // Add event listener for when the collapse is shown
          target.addEventListener('shown.bs.collapse', function() {
            const icon = toggle.querySelector('.icon-side');
            if (icon) {
              icon.classList.remove('bi-chevron-down');
              icon.classList.add('bi-chevron-up');
            }
          });

          // Add event listener for when the collapse is hidden
          target.addEventListener('hidden.bs.collapse', function() {
            const icon = toggle.querySelector('.icon-side');
            if (icon) {
              icon.classList.remove('bi-chevron-up');
              icon.classList.add('bi-chevron-down');
            }
          });
        }
      });
    });


    function func_pass_2() {
      var y = document.getElementById("password_2");
      var show_eye_2 = document.getElementById("show_eye_2");
      var hide_eye_2 = document.getElementById("hide_eye_2");
      hide_eye_2.classList.remove("d-none");
      if (y.type === "password") {
        y.type = "text";
        show_eye_2.style.display = "none";
        hide_eye_2.style.display = "block";
      } else {
        y.type = "password";
        show_eye_2.style.display = "block";
        hide_eye_2.style.display = "none";
      }
    }

    function func_pass_3() {
      var z = document.getElementById("password_3");
      var show_eye_3 = document.getElementById("show_eye_3");
      var hide_eye_3 = document.getElementById("hide_eye_3");
      hide_eye_3.classList.remove("d-none");
      if (z.type === "password") {
        z.type = "text";
        show_eye_3.style.display = "none";
        hide_eye_3.style.display = "block";
      } else {
        z.type = "password";
        show_eye_3.style.display = "block";
        hide_eye_3.style.display = "none";
      }
    }

    $(document).ready(function() {
      $('#datatable-full').DataTable({
        paging: false, // Disables pagination, showing all rows
        searching: true, // Disables the search box
        ordering: true, // Disables column sorting
        info: false // Hides the table information summary
      });
    });


    // select();

    // function select() {
    //   $(document).ready(function() {
    //     $('.select').select2();
    //   });
    // }

    select2_product();

    function select2_product() {
      $(document).ready(function() {
        $('#select2-product').select2();
      });
    }

    select2();

    function select2() {
      $(document).ready(function() {
        $('#select2').select2();
      });
    }


    function select3() {
      $(document).ready(function() {
        $('#select3').select2();
      });
    }

    // select3();

    // function select3() {
    //   $(document).ready(function() {
    //     $('.form-table-select2').select2();
    //   });
    // }



    select_5();

    function select_5() {
      $(document).ready(function() {
        $('.select-5').select2();
      });
    }


    function copy(id) {
      // Get the text field
      var copyText = document.getElementById("box-order-id-" + id);

      // Select the text field
      copyText.select();
      copyText.setSelectionRange(0, 99999); // For mobile devices

      // Copy the text inside the text field
      navigator.clipboard.writeText(copyText.value);

      // Alert the copied text
      // alert("Copied the text: " + );
      $(document).ready(function() {
        $.toast({
          heading: "Informasi",
          text: "Kode order <b>" + copyText.value + "</b> berhasil disalin!",
          showHideTransition: "slide",
          icon: "success",
          position: "top-right",
          loaderBg: "#def7f0",
          hideAfter: 2500,
        });
      });
    }

    $(document).ready(function() {
      $(".checkAll").click(function() {
        $(".checkItem").prop('checked', $(this).prop('checked'));
        get_id();
      });
    });
  </script>
  <script>
    var refreshTime = 180000; // every 3 minutes in milliseconds
    $(document).ready(function() {
      setInterval(sessionCheck, refreshTime);
    });

    function sessionCheck() {
      $.ajax({
        cache: false,
        type: "GET",
        url: "<?= base_url() ?>ajax/refresh-token",
        success: function(data) {
          console.log("Refresh token");
        }
      });
    }
  </script>
  
  <!-- Logout Confirmation with SweetAlert2 -->
  <script>
    function showLogoutConfirmation() {
      Swal.fire({
        title: 'Logout Confirmation',
        text: 'Are you sure you want to logout from your account?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i> Yes, Logout',
        cancelButtonText: '<i class="bi bi-x-circle me-1"></i> Cancel',
        reverseButtons: true,
        customClass: {
          popup: 'logout-swal-popup',
          title: 'logout-swal-title',
          content: 'logout-swal-content',
          confirmButton: 'logout-swal-confirm',
          cancelButton: 'logout-swal-cancel'
        },
        backdrop: true,
        allowOutsideClick: false,
        allowEscapeKey: true,
        focusConfirm: false,
        showClass: {
          popup: 'animate__animated animate__fadeInDown animate__faster'
        },
        hideClass: {
          popup: 'animate__animated animate__fadeOutUp animate__faster'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Logging out...',
            text: 'Please wait while we sign you out securely.',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });
          
          // Redirect to logout after short delay for better UX
          setTimeout(() => {
            window.location.href = '<?= base_url() ?>auth/logout_process';
          }, 1000);
        }
      });
    }
  </script>
  
  <!-- Profile Dropdown Styling -->
  <style>
    /* Profile dropdown enhanced styling */
    .dropdown-menu {
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
      border: 1px solid rgba(0, 0, 0, 0.05) !important;
    }
    
    .dropdown-item:hover {
      background-color: #f8f9fa !important;
      color: #495057 !important;
      transform: translateX(2px);
    }
    
    .dropdown-item.text-danger:hover {
      background-color: #fee !important;
      color: #dc3545 !important;
    }
    
    .avatar:hover {
      transform: scale(1.05);
      transition: transform 0.2s ease;
    }
    
    /* Animation for dropdown */
    .dropdown-menu.show {
      animation: dropdownFadeIn 0.2s ease-out;
    }
    
    @keyframes dropdownFadeIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    /* Profile button focus state */
    #profileDropdown:focus {
      box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25) !important;
      border-radius: 50% !important;
    }
    
    /* Custom SweetAlert2 Logout Styling */
    .logout-swal-popup {
      border-radius: 16px !important;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
    }
    
    .logout-swal-title {
      color: #495057 !important;
      font-weight: 600 !important;
      font-size: 1.25rem !important;
    }
    
    .logout-swal-content {
      color: #6c757d !important;
      font-size: 0.95rem !important;
    }
    
    .logout-swal-confirm {
      border-radius: 8px !important;
      font-weight: 500 !important;
      padding: 10px 24px !important;
      font-size: 0.9rem !important;
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3) !important;
    }
    
    .logout-swal-cancel {
      border-radius: 8px !important;
      font-weight: 500 !important;
      padding: 10px 24px !important;
      font-size: 0.9rem !important;
      box-shadow: 0 4px 12px rgba(108, 117, 125, 0.2) !important;
    }
    
    /* SweetAlert2 button hover effects */
    .logout-swal-confirm:hover {
      transform: translateY(-1px) !important;
      box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4) !important;
    }
    
    .logout-swal-cancel:hover {
      transform: translateY(-1px) !important;
      box-shadow: 0 6px 16px rgba(108, 117, 125, 0.3) !important;
    }
  </style>

  <!-- ===================== Announcement Popup ===================== -->
  <style>
    #announcementOverlay {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 20000;
      background: rgba(0, 0, 0, 0.55);
      backdrop-filter: blur(5px);
      -webkit-backdrop-filter: blur(5px);
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    #announcementOverlay.ann-open { display: flex; }

    #announcementOverlay .ann-card {
      position: relative;
      width: auto;
      max-width: 94vw;
      max-height: 92vh;
      background: transparent;
      border-radius: 0;
      overflow: visible;
      box-shadow: none;
      animation: annPop 0.25s ease;
    }
    @keyframes annPop { from { transform: scale(0.96); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    #announcementOverlay .ann-close {
      position: absolute;
      top: 14px;
      right: 16px;
      z-index: 6;
      width: 38px;
      height: 38px;
      border: none;
      border-radius: 50%;
      background: rgba(0, 0, 0, 0.5);
      color: #fff;
      font-size: 20px;
      line-height: 38px;
      text-align: center;
      cursor: pointer;
      transition: background 0.2s ease;
    }
    #announcementOverlay .ann-close:hover { background: rgba(0, 0, 0, 0.78); }

    #announcementOverlay .ann-stage {
      position: relative;
    }
    #announcementOverlay .ann-slide { display: none; }
    #announcementOverlay .ann-slide.ann-active { display: block; animation: annFade 0.4s ease; }
    @keyframes annFade { from { opacity: 0; } to { opacity: 1; } }

    #announcementOverlay .ann-img {
      display: block;
      width: auto;
      height: auto;
      max-width: 94vw;
      max-height: 92vh;
      border-radius: 12px;
      box-shadow: 0 24px 70px rgba(0, 0, 0, 0.45);
    }

    /* Prev / next arrows */
    #announcementOverlay .ann-nav {
      display: none;
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 44px;
      height: 44px;
      border: none;
      border-radius: 50%;
      background: rgba(0, 0, 0, 0.45);
      color: #fff;
      font-size: 26px;
      line-height: 44px;
      text-align: center;
      cursor: pointer;
      z-index: 6;
      transition: background 0.2s ease;
    }
    #announcementOverlay .ann-nav:hover { background: rgba(0, 0, 0, 0.7); }
    #announcementOverlay .ann-prev { left: 14px; }
    #announcementOverlay .ann-next { right: 14px; }
    #announcementOverlay.ann-multi .ann-nav { display: block; }

    /* Dots */
    #announcementDots {
      display: none;
      position: absolute;
      bottom: 14px;
      left: 0;
      right: 0;
      text-align: center;
      z-index: 6;
    }
    #announcementOverlay.ann-multi #announcementDots { display: block; }
    #announcementDots span {
      display: inline-block;
      width: 10px;
      height: 10px;
      margin: 0 4px;
      border-radius: 50%;
      background: rgba(0, 0, 0, 0.25);
      cursor: pointer;
      transition: background 0.2s ease;
    }
    #announcementDots span.ann-active { background: #1890ff; }
  </style>

  <div id="announcementOverlay">
    <div class="ann-card">
      <button type="button" class="ann-close" id="annCloseBtn" aria-label="Tutup">&times;</button>
      <div class="ann-stage" id="announcementStage"></div>
      <button type="button" class="ann-nav ann-prev" id="annPrevBtn" aria-label="Sebelumnya">&#8249;</button>
      <button type="button" class="ann-nav ann-next" id="annNextBtn" aria-label="Berikutnya">&#8250;</button>
      <div id="announcementDots"></div>
    </div>
  </div>

  <script>
    (function() {
      // Seen/unseen is tracked server-side (announcement_views table).
      // The server already returns only what this user still needs to see.
      function markSeen(a) {
        $.post('<?= base_url() ?>announcement/mark-seen', { announcement_id: a.id });
      }

      function escapeHtml(str) {
        return String(str == null ? '' : str)
          .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
      }

      function buildSlide(a, isActive) {
        // Pure image only — no title, description, or button.
        var img = a.image_url
          ? '<img class="ann-img" src="' + escapeHtml(a.image_url) + '" alt="">'
          : '';
        return '<div class="ann-slide' + (isActive ? ' ann-active' : '') + '">' + img + '</div>';
      }

      var overlay, stage, dotsWrap, slides, dots, current = 0, total = 0, timer = null;

      function goTo(idx) {
        if (!total) return;
        idx = (idx + total) % total;
        slides[current].classList.remove('ann-active');
        if (dots[current]) dots[current].classList.remove('ann-active');
        current = idx;
        slides[current].classList.add('ann-active');
        if (dots[current]) dots[current].classList.add('ann-active');
        stage.scrollTop = 0;
      }
      function next() { goTo(current + 1); }
      function prev() { goTo(current - 1); }

      function startAuto() {
        if (total > 1) {
          stopAuto();
          timer = setInterval(next, 6000);
        }
      }
      function stopAuto() {
        if (timer) { clearInterval(timer); timer = null; }
      }

      function closePopup() {
        stopAuto();
        overlay.classList.remove('ann-open');
        document.body.style.overflow = '';
      }

      $(document).ready(function() {
        overlay = document.getElementById('announcementOverlay');
        stage = document.getElementById('announcementStage');
        dotsWrap = document.getElementById('announcementDots');

        // Wire static controls once
        document.getElementById('annCloseBtn').addEventListener('click', closePopup);
        document.getElementById('annPrevBtn').addEventListener('click', function() { prev(); startAuto(); });
        document.getElementById('annNextBtn').addEventListener('click', function() { next(); startAuto(); });
        overlay.addEventListener('click', function(e) { if (e.target === overlay) closePopup(); });
        document.addEventListener('keydown', function(e) {
          if (!overlay.classList.contains('ann-open')) return;
          if (e.key === 'Escape') closePopup();
          else if (e.key === 'ArrowRight') { next(); startAuto(); }
          else if (e.key === 'ArrowLeft') { prev(); startAuto(); }
        });
        // pause autoplay on hover
        overlay.querySelector('.ann-card').addEventListener('mouseenter', stopAuto);
        overlay.querySelector('.ann-card').addEventListener('mouseleave', startAuto);

        $.ajax({
          type: 'GET',
          url: '<?= base_url() ?>announcement/get-active',
          dataType: 'json',
          success: function(res) {
            var toShow = (res && res.announcements) ? res.announcements : [];
            if (!toShow.length) return;

            var slidesHtml = '', dotsHtml = '';
            toShow.forEach(function(a, i) {
              slidesHtml += buildSlide(a, i === 0);
              dotsHtml += '<span data-idx="' + i + '"' + (i === 0 ? ' class="ann-active"' : '') + '></span>';
            });

            stage.innerHTML = slidesHtml;
            dotsWrap.innerHTML = dotsHtml;

            slides = stage.querySelectorAll('.ann-slide');
            dots = dotsWrap.querySelectorAll('span');
            total = slides.length;
            current = 0;

            dots.forEach(function(d) {
              d.addEventListener('click', function() {
                goTo(parseInt(this.getAttribute('data-idx'), 10));
                startAuto();
              });
            });

            overlay.classList.toggle('ann-multi', total > 1);
            overlay.classList.add('ann-open');
            document.body.style.overflow = 'hidden';

            toShow.forEach(markSeen);
            startAuto();
          }
        });
      });
    })();
  </script>
  <!-- =================== End Announcement Popup =================== -->
<script src="<?= base_url() ?>assets/js/scan-overflow.js?v=<?= time() ?>"></script>
<script src="<?= base_url() ?>assets/js/reaksi-anim.js?v=<?= @filemtime(FCPATH . 'assets/js/reaksi-anim.js') ?>"></script>
<script src="<?= base_url() ?>assets/js/ui-anim.js?v=<?= @filemtime(FCPATH . 'assets/js/ui-anim.js') ?>"></script>

<!-- =================== Notifikasi Global =================== -->
<!-- Sistem notifikasi lama dihapus 19 Sep 2026.
     Blok ini membaca n.body dan mencocokkan n.type ke "chat"/"task_new",
     padahal tabel notifications sekarang memakai kolom message dan
     type-nya hanya info/success/warning/danger. Akibatnya ia memunculkan
     kartu kedua yang badannya selalu kosong menimpa kartu yang benar --
     itu sumber notifikasi dobel. Penggantinya tampilkanKartu() di bawah. -->
<!-- =================== End Notifikasi Global =================== -->

<script>window.__BASE_URL__ = "<?= base_url() ?>";</script>
<script src="<?= base_url() ?>assets/js/notif-suara.js?v=<?= @filemtime(FCPATH . 'assets/js/notif-suara.js') ?>"></script>
<script id="notif-global-poll">
(function(){
  if (window.__notifPollGlobal) return;
  window.__notifPollGlobal = true;

  var BASE = window.__BASE_URL__;
  var terlihat = {};

  var st = document.createElement('style');
  st.textContent = [
    '#notifStack{position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:340px}',
    '.notifCard{background:#fff;border-radius:14px;padding:14px 16px;box-shadow:0 16px 40px rgba(0,0,0,.22);display:flex;gap:12px;align-items:flex-start;opacity:0;transform:translateX(24px);transition:opacity .25s,transform .3s cubic-bezier(.2,.9,.3,1.2)}',
    '.notifCard.on{opacity:1;transform:none}',
    '.notifCard .ic{width:38px;height:38px;border-radius:50%;background:#eef2ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex:0 0 auto}',
    '.notifCard .body{flex:1;min-width:0}',
    '.notifCard .t{font-size:.86rem;font-weight:700;color:#1e293b;line-height:1.35}',
    '.notifCard .m{font-size:.78rem;color:#64748b;margin-top:2px;line-height:1.4;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}',
    '.notifCard .g{display:flex;gap:8px;margin-top:10px}',
    '.notifCard .g button{border:0;border-radius:8px;padding:7px 13px;font-size:.76rem;font-weight:600;cursor:pointer}',
    '.notifCard .buka{background:#6E4FA8;color:#fff}',
    '.notifCard .buka:hover{background:#5b3f95}',
    '.notifCard .lihat{background:#f1f5f9;color:#475569}',
    '.notifCard .lihat:hover{background:#e2e8f0}',
    '@media(max-width:767.98px){#notifStack{left:12px;right:12px;top:12px;max-width:none}}'
  ].join('');
  document.head.appendChild(st);

  var stack = document.createElement('div');
  stack.id = 'notifStack';
  document.addEventListener('DOMContentLoaded', function(){ document.body.appendChild(stack); });
  if (document.body) document.body.appendChild(stack);

  function tandaiLihat(id, hapusKartu){
    var fd = new FormData(); fd.append('id', id);
    fetch(BASE + 'kinerja/mark_notif_read', { method:'POST', body:fd, credentials:'same-origin' });
    if (hapusKartu) {
      var el = document.querySelector('.notifCard[data-id="' + id + '"]');
      if (el) { el.classList.remove('on'); setTimeout(function(){ el.remove(); }, 260); }
    }
  }

  // Confetti ringan pure CSS/JS, tanpa library. Dipanggil sekali tiap
  // notifikasi ulang tahun muncul.
  function tembakConfetti(){
    var warna = ['#f43f5e','#fbbf24','#34d399','#60a5fa','#a78bfa','#fb923c'];
    var wrap = document.createElement('div');
    wrap.style.cssText = 'position:fixed;inset:0;z-index:2147483600;pointer-events:none;overflow:hidden';
    for (var i = 0; i < 120; i++) {
      var p = document.createElement('div');
      var kiri = Math.random() * 100;
      var ukuran = 6 + Math.random() * 6;
      var durasi = 2.5 + Math.random() * 2;
      var delay = Math.random() * 0.6;
      var putar = Math.random() * 360;
      p.style.cssText =
        'position:absolute;top:-20px;left:' + kiri + 'vw;' +
        'width:' + ukuran + 'px;height:' + (ukuran * 0.4) + 'px;' +
        'background:' + warna[i % warna.length] + ';' +
        'opacity:0.9;border-radius:2px;' +
        'transform:rotate(' + putar + 'deg);' +
        'animation:confettiJatuh ' + durasi + 's ease-in ' + delay + 's forwards';
      wrap.appendChild(p);
    }
    document.body.appendChild(wrap);
    setTimeout(function(){ wrap.remove(); }, 5200);
  }
  (function(){
    if (document.getElementById('confetti-style')) return;
    var st2 = document.createElement('style');
    st2.id = 'confetti-style';
    st2.textContent =
      '@keyframes confettiJatuh{' +
      '0%{transform:translateY(0) rotate(0deg);opacity:1}' +
      '100%{transform:translateY(105vh) rotate(720deg);opacity:0}' +
      '}';
    document.head.appendChild(st2);
  })();

  // Popup khusus ulang tahun: banner besar di tengah + confetti, muncul
  // sekali per sesi tab ini (bukan tiap kali halaman dibuka ulang /
  // di-refresh), supaya tidak mengganggu kalau orang bolak-balik
  // halaman sepanjang hari itu.
  function tampilkanUlangTahun(item){
    // localStorage bertanggal, bukan sessionStorage: dengan sessionStorage
    // animasinya muncul lagi tiap kali membuka tab baru, dan orang yang
    // bolak-balik halaman sepanjang hari akan disambut kembang api terus.
    // Sekali sehari sudah cukup untuk ulang tahun.
    var hariIni = new Date().toISOString().slice(0, 10);
    var kunci = 'ultahDitampilkan_' + item.id + '_' + hariIni;
    if (localStorage.getItem(kunci)) return;
    try { localStorage.setItem(kunci, '1'); } catch (e) {}

    tembakConfetti();

    // Berkas suara, bukan nada bikinan: 90 KB, di-cache setahun jadi
    // cukup diunduh sekali. Dibungkus try/catch dan .catch() karena
    // peramban menolak memutar suara sebelum halaman pernah disentuh --
    // kalau ditolak, animasinya tetap jalan tanpa suara.
    try {
      var nada = new Audio('<?= base_url("assets/audio/ultah.mp3") ?>');
      nada.volume = 0.7;
      var putar = nada.play();
      if (putar && putar.catch) putar.catch(function(){});
    } catch (e) {}


    var ov = document.createElement('div');
    ov.style.cssText =
      'position:fixed;inset:0;z-index:2147483601;display:flex;' +
      'align-items:center;justify-content:center;' +
      'background:rgba(15,23,42,0.5);padding:20px';

    // Tanggal ditulis lengkap: kartunya kadang baru dilihat siang atau
    // sore, dan tanpa tanggal orang ragu apakah ini ucapan hari ini
    // atau sisa kemarin yang belum ditutup.
    var namaHari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    var namaBulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli',
                     'Agustus','September','Oktober','November','Desember'];
    var t = new Date();
    var tanggal = namaHari[t.getDay()] + ', ' + t.getDate() + ' ' +
                  namaBulan[t.getMonth()] + ' ' + t.getFullYear();

    var baris = String(item.message || '').split('\n');
    var doa = baris.slice(1).join(' ').trim();

    ov.innerHTML =
      '<div style="background:#fff;border-radius:20px;padding:34px 44px 30px;' +
      'max-width:560px;width:100%;text-align:center;' +
      'box-shadow:0 20px 50px rgba(15,23,42,0.25)">' +

        '<div style="font-size:38px;line-height:1;letter-spacing:6px">' +
        '🎉🎂🎈</div>' +

        '<div style="font-size:16px;color:#475569;margin-top:18px">' +
          (item.title || 'Selamat Ulang Tahun') + ',</div>' +

        // Baris pertama nama, sisanya ucapan. Yang berulang tahun
        // mendapat keduanya; yang lain hanya namanya, jadi bagian doa
        // ini tidak muncul untuk mereka.
        '<div style="font-size:25px;font-weight:800;color:#0f172a;' +
        'margin-top:4px;line-height:1.3">' +
          baris[0] + '</div>' +

        (doa ? '<div style="font-size:13.5px;color:#64748b;' +
        'margin-top:14px;line-height:1.65">' + doa + '</div>' : '') +

        // Tanggal dihilangkan: yang tampil di sini tanggal hari ini,
        // bukan tanggal lahirnya, dan menuliskan "Jumat" untuk orang
        // yang lahirnya bukan hari Jumat justru menyesatkan. Kartunya
        // toh cuma muncul di hari ulang tahunnya.

        '<button id="ultahOk" style="margin-top:22px;background:#1F4696;' +
        'color:#fff;border:none;padding:11px 34px;border-radius:999px;' +
        'cursor:pointer;font-size:14px;font-weight:700">Asiiik 🎉</button>' +
      '</div>';

    document.body.appendChild(ov);


    ov.querySelector('#ultahOk').addEventListener('click', function(){
      ov.remove();
      tandaiLihat(item.id, true);
    });
  }

  function tampilkanKartu(item){
    if (terlihat[item.id]) return;
    terlihat[item.id] = true;

    // Tanpa return: overlay besar muncul, dan kartu samping tetap
    // dibuat seperti notifikasi lain -- supaya ucapannya juga terbaca
    // di daftar yang menggeser dari kanan, bukan hanya di overlay.
    if (item.category === 'ultah') { tampilkanUlangTahun(item); }

    var card = document.createElement('div');
    card.className = 'notifCard';
    card.dataset.id = item.id;
    card.innerHTML =
        '<div class="ic"><i class="bi bi-bell-fill"></i></div>'
      + '<div class="body">'
      +   '<div class="t"></div><div class="m"></div>'
      +   '<div class="g"><button class="buka">Buka</button><button class="lihat">Sudah dilihat</button></div>'
      + '</div>';
    card.querySelector('.t').textContent = item.title || 'Notifikasi';
    card.querySelector('.m').textContent = item.message || '';
    stack.appendChild(card);
    requestAnimationFrame(function(){ card.classList.add('on'); });

    card.querySelector('.buka').addEventListener('click', function(){
      tandaiLihat(item.id, false);
      if (item.ref_url) location.href = item.ref_url;
    });
    card.querySelector('.lihat').addEventListener('click', function(){ tandaiLihat(item.id, true); });

    // hilang otomatis setelah 25 detik kalau tidak disentuh (tetap tercatat belum dibaca)
    setTimeout(function(){
      if (card.parentNode) { card.classList.remove('on'); setTimeout(function(){ card.remove(); }, 260); }
    }, 25000);

    window.notifBrowser(item.title || 'Notifikasi baru', item.message || '', item.ref_url || '');
  }

  function polling(){
    fetch(BASE + 'kinerja/poll_notifications', { credentials:'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (!d.status || !d.data) return;
        d.data.forEach(tampilkanKartu);
      })
      .catch(function(){});
  }

  polling();
  // 30 detik, bukan 5: dengan 28 orang membuka ERP bersamaan, jeda 5
  // detik berarti sekitar 20 ribu permintaan per jam, dan puncaknya
  // persis di jam absen masuk dan pulang -- saat halaman justru harus
  // paling gesit. Notifikasi tetap sampai, hanya ditanyakan lebih jarang.
  setInterval(polling, 30000);
})();
</script>

<!-- ntf-bersihkan-semua : tombol tandai semua notifikasi sekaligus -->
<style>
  #ntfSemua{
    position:fixed; z-index:2147483000; display:none;
    align-items:center; gap:8px; padding:9px 14px;
    background:#1F1147; color:#fff; border:none; border-radius:10px;
    font:600 13px/1.2 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
    box-shadow:0 6px 20px -6px rgba(0,0,0,.45); cursor:pointer;
  }
  #ntfSemua:hover{background:#2D1A63}
  #ntfSemua b{background:rgba(255,255,255,.22); border-radius:20px; padding:1px 8px; font-weight:700}
  .ntf-sisa{
    text-align:center; font:500 12px/1.2 system-ui,sans-serif; color:#6B7280;
    padding:6px 0 2px;
  }
</style>
<script>
(function () {
  var BASE_NS = '<?= base_url() ?>';
  var MAKS = 3;                     // toast yang tampil sekaligus
  var PILIH = '.notifCard, .ntf-card';

  var tombol = document.createElement('button');
  tombol.id = 'ntfSemua';
  tombol.type = 'button';
  tombol.innerHTML = 'Tandai semua sudah dilihat <b>0</b>';

  var sisa = document.createElement('div');
  sisa.className = 'ntf-sisa';
  sisa.style.display = 'none';

  function pasang() {
    if (!document.body) return;
    if (!tombol.parentNode) document.body.appendChild(tombol);
  }
  if (document.body) pasang();
  document.addEventListener('DOMContentLoaded', pasang);

  function kartu() {
    return Array.prototype.slice.call(document.querySelectorAll(PILIH))
      .filter(function (c) { return c.dataset.ntfHapus !== '1'; });
  }

  function rapikan() {
    var list = kartu();
    var n = list.length;

    // batasi jumlah yang kelihatan
    list.forEach(function (c, i) {
      c.style.display = i < MAKS ? '' : 'none';
    });

    if (n < 2) {
      tombol.style.display = 'none';
      if (sisa.parentNode) sisa.parentNode.removeChild(sisa);
      return;
    }

    tombol.querySelector('b').textContent = n;
    tombol.style.display = 'inline-flex';

    // tempelkan tepat di atas kartu pertama
    var r = list[0].getBoundingClientRect();
    tombol.style.top  = Math.max(8, r.top - 46) + 'px';
    tombol.style.left = Math.round(r.left) + 'px';
    tombol.style.width = Math.round(r.width) + 'px';
    tombol.style.justifyContent = 'center';

    // penanda sisa yang disembunyikan
    if (n > MAKS) {
      sisa.textContent = '+' + (n - MAKS) + ' notifikasi lain';
      var akhir = list[MAKS - 1];
      if (akhir && akhir.parentNode && sisa.previousSibling !== akhir) {
        akhir.parentNode.insertBefore(sisa, akhir.nextSibling);
      }
      sisa.style.display = '';
    } else {
      sisa.style.display = 'none';
    }
  }

  tombol.addEventListener('click', function () {
    var list = kartu();
    tombol.disabled = true;
    tombol.innerHTML = 'Membersihkan...';

    // 1. tandai lewat endpoint massal yang sudah ada
    fetch(BASE_NS + 'notifications/mark_all_read', {
      method: 'POST', credentials: 'same-origin'
    }).catch(function () {});

    // 2. tandai satu per satu untuk notifikasi modul Kinerja
    list.forEach(function (c) {
      var id = c.dataset.id;
      if (!id) return;
      var fd = new FormData();
      fd.append('id', id);
      fetch(BASE_NS + 'kinerja/mark_notif_read', {
        method: 'POST', body: fd, credentials: 'same-origin'
      }).catch(function () {});
    });

    // 3. bersihkan tampilan
    list.forEach(function (c) {
      c.dataset.ntfHapus = '1';
      c.classList.remove('on');
      c.classList.remove('show');
      setTimeout(function () { if (c.parentNode) c.remove(); }, 220);
    });

    setTimeout(function () {
      tombol.disabled = false;
      tombol.innerHTML = 'Tandai semua sudah dilihat <b>0</b>';
      tombol.style.display = 'none';
      if (sisa.parentNode) sisa.parentNode.removeChild(sisa);

      // segarkan lonceng jumlah belum dibaca kalau fungsinya ada
      try { if (typeof loadUnreadCount === 'function') loadUnreadCount(); } catch (e) {}
      try { if (typeof updateNotifBadge === 'function') updateNotifBadge(); } catch (e) {}
    }, 260);
  });

  // ntfLoopAman: tanpa MutationObserver. Observer lama memicu dirinya sendiri
  // karena rapikan() ikut mengubah DOM, akibatnya halaman membeku.
  var sidikTerakhir = '';
  function periksa() {
    var list = kartu();
    var sidik = list.length + '|' + (list[0] ? list[0].dataset.id || '' : '');
    if (sidik === sidikTerakhir) return;   // tidak ada perubahan, tidak usah kerja
    sidikTerakhir = sidik;
    rapikan();
  }
  window.addEventListener('resize', function () { sidikTerakhir = ''; });
  setInterval(periksa, 1000);
  periksa();
})();
</script>

<?php
// Sambutan karyawan baru: tampil sekali, tepat setelah pendaftarannya disetujui
// (disetujui = 2), lalu ditandai selesai (disetujui = 1).
$__ci =& get_instance();
$__uid = (int) ($_SESSION['user']['id'] ?? 0);
if ($__uid && isset($__ci->db)) {
    $__baru = $__ci->db->select('full_name, disetujui')->where('id', $__uid)->get('user')->row_array();
    if ($__baru && (int) $__baru['disetujui'] === 2) {
        $__ci->db->where('id', $__uid)->update('user', ['disetujui' => 1]);
        $__nama = htmlspecialchars(strtok(trim($__baru['full_name']), ' ') ?: $__baru['full_name'], ENT_QUOTES, 'UTF-8');
?>
<div id="msgSambut" style="position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:radial-gradient(circle at center,rgba(31,70,150,.88),rgba(10,10,30,.96));overflow:hidden">
  <div id="msgSambutKartu" style="position:relative;z-index:2;max-width:420px;width:88%;background:#fff;border-radius:22px;padding:34px 26px 28px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.45);opacity:0">
    <div class="msg-lompat" style="font-size:64px;line-height:1">&#127881;</div>
    <div style="margin-top:12px;font-size:.78rem;letter-spacing:.22em;color:#7c3aed;font-weight:800">AKUN DISETUJUI</div>
    <h2 style="margin:8px 0 8px;font-weight:800;font-size:1.65rem;line-height:1.25;background:linear-gradient(90deg,#1F4696,#7c3aed,#db2777);-webkit-background-clip:text;background-clip:text;color:transparent">Selamat Bergabung, <?= $__nama ?>!</h2>
    <p style="margin:0;color:#334155;line-height:1.6">Kamu resmi jadi bagian dari tim<br><b>Montera Strategic Group</b> &#128156;</p>
    <p style="margin:14px 0 0;color:#64748b;font-size:.85rem;line-height:1.5">Langkah pertama: daftarkan wajahmu di menu Absensi, setelah itu kamu sudah bisa absen.</p>
    <button id="msgSambutTutup" style="margin-top:22px;padding:12px 32px;border:0;border-radius:999px;background:linear-gradient(90deg,#1F4696,#7c3aed);color:#fff;font-weight:700;font-size:1rem;cursor:pointer;box-shadow:0 8px 22px rgba(124,58,237,.45)">Ayo Mulai &#128640;</button>
  </div>
</div>
<style>
@keyframes msgLompat{0%,100%{transform:translateY(0) rotate(0)}25%{transform:translateY(-14px) rotate(-10deg)}75%{transform:translateY(-6px) rotate(10deg)}}
.msg-lompat{display:inline-block;animation:msgLompat 1.2s ease-in-out infinite}
</style>
<script>
(function(){
  var lapis=document.getElementById('msgSambut'), kartu=document.getElementById('msgSambutKartu');
  var warna=['#1F4696','#7c3aed','#db2777','#f59e0b','#10b981','#06b6d4','#ef4444','#facc15'];
  kartu.animate([{transform:'scale(.3)',opacity:0},{transform:'scale(1.08)',opacity:1,offset:.7},{transform:'scale(1)',opacity:1}],
                {duration:750,easing:'cubic-bezier(.2,.9,.3,1.3)',fill:'forwards'});
  function hapus(el){return function(){el.remove();};}
  function konfeti(n){
    for(var i=0;i<n;i++){
      var c=document.createElement('div'), s=6+Math.random()*8;
      c.style.cssText='position:absolute;top:-20px;left:'+(Math.random()*100)+'%;width:'+s+'px;height:'+(s*1.6)+'px;background:'+warna[i%warna.length]+';border-radius:2px;z-index:1;pointer-events:none';
      lapis.appendChild(c);
      c.animate([{transform:'translate(0,0) rotate(0)'},
                 {transform:'translate('+(Math.random()*200-100)+'px,'+(window.innerHeight+40)+'px) rotate('+(Math.random()*720)+'deg)'}],
                {duration:2500+Math.random()*2500,delay:Math.random()*1200,easing:'cubic-bezier(.25,.46,.45,.94)'}).onfinish=hapus(c);
    }
  }
  function kembangApi(){
    var x=10+Math.random()*80, y=8+Math.random()*45, w=warna[Math.floor(Math.random()*warna.length)];
    for(var i=0;i<28;i++){
      var p=document.createElement('div'), sudut=Math.PI*2*i/28, jauh=80+Math.random()*80;
      p.style.cssText='position:absolute;left:'+x+'%;top:'+y+'%;width:6px;height:6px;border-radius:50%;background:'+w+';box-shadow:0 0 10px '+w+';z-index:1;pointer-events:none';
      lapis.appendChild(p);
      p.animate([{transform:'translate(0,0) scale(1)',opacity:1},
                 {transform:'translate('+Math.cos(sudut)*jauh+'px,'+Math.sin(sudut)*jauh+'px) scale(.3)',opacity:0}],
                {duration:1100+Math.random()*400,easing:'cubic-bezier(.1,.7,.3,1)'}).onfinish=hapus(p);
    }
  }
  konfeti(160);
  var n=0, t=setInterval(function(){kembangApi(); if(++n%3===0) konfeti(60); if(n>=16) clearInterval(t);},450);
  document.getElementById('msgSambutTutup').onclick=function(){
    konfeti(90);
    lapis.animate([{opacity:1},{opacity:0}],{duration:600,delay:400,fill:'forwards'}).onfinish=function(){lapis.remove();};
  };
})();
</script>
<?php } } ?>

<?php if (!empty($_SESSION['user']['id'])): ?>
<script>
// Akun yang dihapus admin langsung diantar ke halaman perpisahan,
// walau halamannya sedang terbuka dan tidak diklik apa-apa.
setInterval(function(){fetch('/auth/status_akun',{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(o){if(o.status==='ditolak'||o.status==='dikeluarkan'){location.href='/auth/login?akun='+o.status;}else if(o.status==='dihapus'||o.status==='keluar'){location.href='/auth/login';}}).catch(function(){});},30000);
</script>
<?php endif; ?>
</body>

</html>
