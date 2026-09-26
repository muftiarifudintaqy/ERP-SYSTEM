<?php
if ($_SESSION['is_login']) {
  redirect(base_url() . 'dashboard');
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
  <link href="<?= base_url() ?>assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <script src="<?= base_url() ?>assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
  <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>assets/img/fav.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preload" as="image" href="<?= base_url() ?>assets/img/bg-login.jpg" fetchpriority="high">
  <link rel="stylesheet" href="<?= base_url() ?>assets/vendor/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= base_url() ?>assets/css/style.css?v=1.0.1" type="text/css" media="screen" />

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

  <script src="<?= base_url() ?>assets/vendor/jquery/jquery-3.6.1.min.js"></script>
  <link rel="stylesheet" href="<?= base_url() ?>assets/toast/jquery.toast.css">
  <script src="<?= base_url() ?>assets/toast/jquery.toast.js"></script>

  <style {csp-style-nonce}>
    * {
      transition: background-color 300ms ease, color 300ms ease;
    }

    *:focus {
      background-color: rgba(221, 72, 20, .2);
      outline: none;
    }

    html,
    body {
      /* font-size: 16px; */
      margin: 0;
      padding: 0;
      /* background: linear-gradient(90deg, #012062 50%, #de6602 50%); */
    }

    .form-control {
      margin-bottom: 15px;
    }

    .form-control {
      /* height:55px; */
      margin-bottom: 15px;
      margin-top: 5px;
    }

    .fw-500 {
      font-weight: 500;
    }

    .fw-600 {
      font-weight: 600;
    }

    .fw-700 {
      font-weight: 700;
    }

    .div-login {
      background: #FFF;
      height: 100vh;
      border-top-left-radius: 100px;
      border-top-right-radius: 0px;
      border-bottom-left-radius: 0px;
      border-bottom-right-radius: 100px;
    }

    .container-form {
      left: 50%;
      top: 50%;
      width: 450px;
      position: absolute;
      transform: translate(-50%, -50%);
    }

    .div-icon {
      position: relative;
    }

    .icon-right {
      position: absolute;
      right: 10px;
      font-size: 20px;
      padding-top: 8px;
    }

    .xbanner {
      background-size: cover;
      background-position: top;
      background-repeat: no-repeat;
      height: 100vh;
      display: flex;
    }

    @media only screen and (max-width: 600px) {
      .container-form {
        padding-left: 30px;
        padding-right: 30px;
        width: 100%;
      }

      .xbanner {
        height: 50vh !important;
      }
    }

    body {
      background: unset !important;
    }

    .w-100 {
      width: 100% !important;
    }
  </style>

  <link rel="stylesheet" href="<?= base_url() ?>assets/css/mobile.css?v=<?= @filemtime(FCPATH . 'assets/css/mobile.css') ?>" type="text/css" media="screen" />

  <link rel="manifest" href="<?= base_url() ?>manifest.json">
  <meta name="theme-color" content="#6E4FA8">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="Montera">
  <link rel="apple-touch-icon" href="<?= base_url() ?>assets/img/fav.png">
  <script>
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
                .catch(function(){});
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
  <?= $content ?>
  <script>
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
  </script>
</body>

</html>