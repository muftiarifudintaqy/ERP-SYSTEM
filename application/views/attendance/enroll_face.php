<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Wajah - Montera</title>
<script src="<?= base_url() ?>assets/js/face-api.min.js"></script>
<style>
  *{box-sizing:border-box}
  body{margin:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0b1220;min-height:100vh;display:flex;flex-direction:column}
  .fe-bar{background:#0f1830;color:#fff;padding:16px 18px;font-weight:600;font-size:.95rem;display:flex;align-items:center;gap:10px}
  .fe-bar a{color:#fff;text-decoration:none;font-size:1.2rem}
  .fe-step{flex:1;display:none;flex-direction:column;align-items:center;justify-content:center;padding:28px 22px;text-align:center;color:#fff}
  .fe-step.on{display:flex}
  .fe-ic{width:74px;height:74px;border-radius:50%;background:#16233f;display:flex;align-items:center;justify-content:center;font-size:2.1rem;margin-bottom:18px}
  .fe-title{font-size:1.15rem;font-weight:700;margin-bottom:8px}
  .fe-desc{font-size:.86rem;color:#9aa5c0;max-width:340px;line-height:1.5;margin-bottom:18px}
  .fe-note{background:#12336b;border-radius:10px;padding:10px 14px;font-size:.76rem;color:#bcd4ff;max-width:340px;text-align:left;margin-bottom:20px}
  .fe-btn{background:#e0334f;color:#fff;border:0;border-radius:10px;padding:13px 0;width:100%;max-width:340px;font-size:.92rem;font-weight:700;cursor:pointer}
  .fe-btn:disabled{opacity:.5}
  .fe-camwrap{position:relative;width:260px;height:320px;border-radius:50%/45%;overflow:hidden;margin-bottom:18px;background:#000}
  .fe-camwrap video{width:100%;height:100%;object-fit:cover;transform:scaleX(-1)}
  .fe-oval{position:absolute;inset:0;border:4px solid #fff;border-radius:50%/45%;pointer-events:none}
  .fe-oval.ok{border-color:#22c55e}
  .fe-liveness{font-size:.8rem;color:#facc15;margin-bottom:10px;min-height:20px}
  .fe-list{text-align:left;font-size:.78rem;color:#9aa5c0;max-width:320px;margin:0 0 20px}
  .fe-list b{color:#fff;display:block;font-size:.7rem;letter-spacing:.5px;margin-bottom:6px}
</style>
</head>
<body>
<div class="fe-bar"><a href="<?= base_url() ?>attendance/me"><i class="bi bi-arrow-left"></i>&larr;</a> Daftar Wajah</div>

<div class="fe-step on" id="stepKamera">
  <div class="fe-ic">📷</div>
  <div class="fe-title">Izinkan Akses Kamera</div>
  <div class="fe-desc">Aplikasi membutuhkan akses kamera untuk verifikasi wajah saat melakukan absensi. Foto Anda tidak akan disimpan, hanya data terenkripsi wajah yang digunakan.</div>
  <div class="fe-note"><b>PRIVASI TERJAGA</b>Foto tidak disimpan - hanya enkripsi data wajah yang dikirim ke server.</div>
  <button class="fe-btn" id="btnIzinKamera">Izin Akses Kamera</button>
</div>

<div class="fe-step" id="stepWelcome">
  <div class="fe-ic">👋</div>
  <div class="fe-title">Selamat Datang, <?= htmlspecialchars($nama_user) ?></div>
  <div class="fe-desc">Daftarkan wajah Anda 😊<br>Untuk menggunakan fitur absensi, Anda perlu mendaftarkan wajah terlebih dahulu menggunakan kamera depan smartphone Anda.</div>
  <div class="fe-list"><b>PERSIAPAN SEBELUM MULAI</b>
    &bull; Lepas masker dan kacamata hitam<br>
    &bull; Cari tempat dengan cahaya yang cukup<br>
    &bull; Posisikan wajah menghadap kamera dengan jelas
  </div>
  <button class="fe-btn" id="btnLanjutEnroll">Lanjutkan ke Pendaftaran Wajah</button>
</div>

<div class="fe-step" id="stepEnroll">
  <div class="fe-title" style="margin-bottom:16px">Daftar Wajah</div>
  <div class="fe-camwrap"><video id="vidEnroll" autoplay muted playsinline></video><div class="fe-oval" id="ovalEnroll"></div></div>
  <div class="fe-liveness" id="livenessMsg">Memuat model wajah...</div>
  <div class="fe-title" style="font-size:.95rem">Posisikan Wajah Anda</div>
  <div class="fe-desc" style="margin-bottom:8px">Pastikan wajah anda dalam bingkai oval</div>
</div>

<div class="fe-step" id="stepSukses">
  <div class="fe-ic" style="background:#123b22;color:#22c55e">✔</div>
  <div class="fe-title">Wajah Berhasil Didaftarkan!</div>
  <div class="fe-desc">Sekarang Anda bisa menggunakan fitur absensi dengan scan wajah.</div>
  <button class="fe-btn" onclick="window.location.href='<?= base_url() ?>attendance/me'">Mulai Menggunakan App</button>
</div>

<script>
(function(){
  var BASE = '<?= base_url() ?>';
  function goto(id){ document.querySelectorAll('.fe-step').forEach(function(s){ s.classList.remove('on'); }); document.getElementById(id).classList.add('on'); }

  document.getElementById('btnIzinKamera').addEventListener('click', function(){
    navigator.mediaDevices.getUserMedia({ video: true }).then(function(stream){
      stream.getTracks().forEach(function(t){ t.stop(); });
      goto('stepWelcome');
    }).catch(function(){ alert('Izin kamera wajib diaktifkan.'); });
  });

  document.getElementById('btnLanjutEnroll').addEventListener('click', function(){
    goto('stepEnroll');
    mulaiEnroll();
  });

  var video = document.getElementById('vidEnroll');
  var oval = document.getElementById('ovalEnroll');
  var msg = document.getElementById('livenessMsg');
  var modelSiap = false;

  Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri(BASE + 'assets/models'),
    faceapi.nets.faceLandmark68Net.loadFromUri(BASE + 'assets/models'),
    faceapi.nets.faceRecognitionNet.loadFromUri(BASE + 'assets/models')
  ]).then(function(){ modelSiap = true; msg.textContent = 'Arahkan wajah ke kamera...'; });

  function mulaiEnroll(){
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } }).then(function(stream){
      video.srcObject = stream;
      video.onloadedmetadata = function(){ prosesFrame(); };
    }).catch(function(){ msg.textContent = 'Gagal mengakses kamera.'; });
  }

  var stabilCount = 0;
  function prosesFrame(){
    if (!modelSiap) { setTimeout(prosesFrame, 300); return; }
    faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
      .withFaceLandmarks().withFaceDescriptor()
      .then(function(hasil){
        if (hasil) {
          stabilCount++;
          oval.classList.add('ok');
          msg.textContent = 'Wajah terdeteksi, tahan sebentar... (' + stabilCount + '/4)';
          if (stabilCount >= 4) {
            var cnv = document.createElement('canvas');
              cnv.width = video.videoWidth; cnv.height = video.videoHeight;
              cnv.getContext('2d').drawImage(video, 0, 0);
              simpanDescriptor(Array.from(hasil.descriptor), cnv.toDataURL('image/jpeg', 0.75));
            return;
          }
        } else {
          stabilCount = 0;
          oval.classList.remove('ok');
          msg.textContent = 'Posisikan wajah di dalam bingkai oval';
        }
        setTimeout(prosesFrame, 250);
      });
  }

  function simpanDescriptor(arr, foto){
    msg.textContent = 'Menyimpan data wajah...';
    var fd = new FormData();
    fd.append('descriptor', JSON.stringify(arr));
      if (foto) fd.append('photo_base64', foto);
    fetch(BASE + 'attendance/save_face_descriptor', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (video.srcObject) video.srcObject.getTracks().forEach(function(t){ t.stop(); });
        if (d.success) { goto('stepSukses'); }
        else { msg.textContent = d.message || 'Gagal menyimpan, coba lagi.'; stabilCount = 0; setTimeout(prosesFrame, 800); }
      });
  }
})();
</script>
</body>
</html>
