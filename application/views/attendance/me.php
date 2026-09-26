<style>
    .table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid #f0f0f0;border-radius:2px}
    .table thead th{background-color:#fafafa;color:rgba(0,0,0,.85);font-weight:500;text-align:left;padding:12px 8px;font-size:14px;border-bottom:1px solid #f0f0f0}
    .table tbody td{padding:12px 8px!important;font-size:14px;color:rgba(0,0,0,.65);border-bottom:1px solid #f0f0f0;vertical-align:middle}
    .table-hover tbody tr:hover{background-color:#fafafa}
    .card{border-radius:2px;border:1px solid #f0f0f0;box-shadow:0 2px 8px rgba(0,0,0,.09);margin-bottom:16px}
    .card-header{background-color:#fff;border-bottom:1px solid #f0f0f0;padding:16px}
    .card-body{padding:16px}
    .att-status-card{border-radius:8px;padding:20px;border:1px solid #f0f0f0}

    .pm-pills{display:flex;gap:8px;flex-wrap:wrap;margin:14px 0}
    .pm-pill{flex:1;min-width:130px;background:#fafafa;border:1px solid #f0f0f0;border-radius:8px;padding:10px 12px}
    .pm-pill.done{background:#f6ffed;border-color:#b7eb8f}
    .pm-pill .lbl{font-size:11px;color:#8c8c8c;text-transform:uppercase;letter-spacing:.4px}
    .pm-pill .val{font-size:14px;font-weight:600;color:#1e293b;margin-top:2px}
    .pm-pill.done .val{color:#389e0d}

    .pm-overlay{position:fixed;inset:0;background:rgba(11,18,32,.92);z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px}
    .pm-card{background:#0b1220;color:#fff;border-radius:16px;padding:24px 22px;max-width:360px;width:100%;text-align:center}
    .pm-camwrap{position:relative;width:230px;height:290px;border-radius:50%/45%;overflow:hidden;margin:0 auto 16px;background:#000}
    .pm-camwrap video{width:100%;height:100%;object-fit:cover;transform:scaleX(-1)}
    .pm-oval{position:absolute;inset:0;border:4px solid #fff;border-radius:50%/45%;pointer-events:none}
    .pm-oval.ok{border-color:#22c55e}
    .pm-msg{font-size:.84rem;color:#facc15;margin-bottom:16px;min-height:20px}
    .pm-btn-cancel{background:none;border:1px solid #334155;color:#94a3b8;border-radius:10px;padding:9px 22px;font-size:.85rem;cursor:pointer}

    .pm-result .pm-ic{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 14px}
    .pm-result.ok .pm-ic{background:#123b22;color:#22c55e}
    .pm-result.fail .pm-ic{background:#3b1414;color:#ef4444}
    .pm-title{font-size:1.1rem;font-weight:700;margin-bottom:4px}
    .pm-sub{font-size:.82rem;color:#9aa5c0;margin-bottom:16px}
    .pm-detail{width:100%;font-size:.82rem;margin-bottom:18px;text-align:left}
    .pm-detail td{padding:6px 0;border-bottom:1px solid #1e293b;color:#cbd5e1}
    .pm-detail td:first-child{color:#64748b;width:40%}
    .pm-btn{display:block;width:100%;background:#e0334f;color:#fff;border:0;border-radius:10px;padding:12px 0;font-weight:700;font-size:.9rem;cursor:pointer;margin-bottom:8px}
    .pm-btn-outline{display:block;width:100%;background:#fff;color:#0b1220;border:0;border-radius:10px;padding:12px 0;font-weight:700;font-size:.9rem;cursor:pointer}
</style>

<div class="container-fluid py-3">

    <div class="card">
        <div class="card-header"><h5 class="mb-0" style="color:rgba(0,0,0,.85)">Absensi Saya</h5></div>
        <div class="card-body">
            <div id="today-status">
                <?php /* izin-tidak-memblokir: keterangan izin ditampilkan
                         sebagai catatan, bukan pengganti tombol absen.
                         Orang yang izin beberapa hari bisa saja masuk lebih
                         awal; kalau tombolnya disembunyikan, kehadirannya
                         hari itu tidak terekam sama sekali. */ ?>
                <?php if ($on_leave): ?>
                    <div class="att-status-card" style="background:#fffbe6;border-color:#ffe58f;margin-bottom:12px">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-calendar-heart" style="color:#faad14;font-size:22px"></i>
                            <div>
                                <div style="font-weight:600;color:#d48806">Kamu sedang izin hari ini</div>
                                <div class="text-muted" style="font-size:13px">Tidak wajib absen. Kalau ternyata masuk, silakan absen seperti biasa.</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (TRUE): ?>
                    <div class="att-status-card" id="pmStatusCard" style="background:#fff2f0;border-color:#ffccc7">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-exclamation-circle-fill" id="pmStatusIc" style="color:#ff4d4f;font-size:22px"></i>
                                <div>
                                    <div style="font-weight:600;color:#cf1322" id="pmStatusTitle">Memuat status...</div>
                                    <div class="text-muted" style="font-size:13px" id="pmStatusSub"></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <img id="pmFotoWajah" src="" alt="" style="display:none;width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #fff;box-shadow:0 0 0 1px #e2e8f0">
                                <a href="<?= base_url() ?>leave/request" class="btn btn-outline-primary" title="Ajukan izin atau cuti">
                                    <i class="bi bi-calendar-check me-1"></i> Ajukan Izin
                                </a>
                                <a href="<?= base_url() ?>attendance/enroll_face" class="btn btn-outline-secondary" id="btn-daftar-wajah" title="Daftar / ganti wajah">
                                    <i class="bi bi-camera me-1"></i> Daftar Wajah
                                </a>
                                <button type="button" class="btn btn-primary" id="btn-punch" style="background-color:#1890ff;border-color:#1890ff;display:none">
                                    <i class="bi bi-fingerprint me-1"></i> <span id="btnPunchLabel">Absen Sekarang</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pm-pills">
                        <div class="pm-pill" id="pillMasuk"><div class="lbl">Masuk</div><div class="val">-</div></div>
                        <div class="pm-pill" id="pillIstKeluar"><div class="lbl">Istirahat Keluar</div><div class="val">-</div></div>
                        <div class="pm-pill" id="pillIstMasuk"><div class="lbl">Istirahat Masuk</div><div class="val">-</div></div>
                        <div class="pm-pill" id="pillPulang"><div class="lbl">Pulang</div><div class="val">-</div></div>
                    </div>
                    <div id="pmBtnGroup" class="d-flex gap-2 flex-wrap mb-1"></div>
                    <div style="display:none">
                    </div>
                <?php endif; ?>
            </div>

            <div class="alert mt-3" style="background:#e6f7ff;border:1px solid #91d5ff;color:rgba(0,0,0,.65);border-radius:2px;font-size:13px">
                <i class="bi bi-info-circle me-1"></i>
                Dari HP: <strong>lokasi (GPS) dalam radius kantor</strong> + <strong>wajah cocok</strong> + <strong>perangkat & Wi-Fi terdaftar</strong>.
                Dari komputer kantor: cukup <strong>perangkat terdaftar</strong> + <strong>Wi-Fi kantor</strong> (tanpa GPS/wajah).
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h6 class="mb-0" style="color:rgba(0,0,0,.85)">Riwayat Absensi</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="my-attendance-table">
                    <thead><tr><th>#</th><th>Tanggal</th><th>Jam Masuk</th><th>Jam Pulang</th></tr></thead>
                    <tbody id="my-attendance-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="pm-overlay" id="pmCamera" style="display:none">
  <div class="pm-card">
    <div style="font-weight:700;margin-bottom:14px">Verifikasi Wajah</div>
    <div class="pm-camwrap"><video id="pmVideo" autoplay muted playsinline></video><div class="pm-oval" id="pmOval"></div></div>
    <div class="pm-msg" id="pmMsg">Memuat model wajah...</div>
    <button class="pm-btn-cancel" id="pmCancel">Batal</button>
  </div>
</div>

<div class="pm-overlay" id="pmResult" style="display:none">
  <div class="pm-card pm-result" id="pmResultCard">
    <div class="pm-ic" id="pmResultIc">✔</div>
    <div class="pm-title" id="pmResultTitle">Absensi Berhasil!</div>
    <div class="pm-sub" id="pmResultSub"></div>
    <table class="pm-detail">
      <tr><td>Jenis</td><td id="pmJenis">-</td></tr>
      <tr><td>Waktu</td><td id="pmWaktu">-</td></tr>
      <tr><td>Status</td><td id="pmStatus">-</td></tr>
      <tr><td>Lokasi</td><td id="pmLokasi">-</td></tr>
      <tr><td>Shift</td><td id="pmShift">-</td></tr>
    </table>
    <button class="pm-btn" id="pmTutup">Kembali Ke Beranda</button>
  </div>
</div>

<script src="<?= base_url() ?>assets/js/face-api.min.js"></script>
<script>
(function(){
    var ATT_BASE_URL = "<?= base_url() ?>";
    var ON_LEAVE = <?= $on_leave ? 'true' : 'false' ?>;
    var punchState = null;
    var modelReady = false, modelLoading = false;

    function getDeviceToken() {
        var key = 'montera_device_token';
        // pindahkan token lama sekali jalan supaya perangkat terdaftar tidak hangus
        try {
          var __t = localStorage.getItem('bhskin_device_token');
          if (__t && !localStorage.getItem(key)) localStorage.setItem(key, __t);
        } catch (e) {}
        var token = '';
        try { token = localStorage.getItem(key) || ''; } catch (e) {}
        if (!token) {
            token = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : ('dev-' + Date.now() + '-' + Math.random().toString(36).slice(2, 12));
            try { localStorage.setItem(key, token); } catch (e) {}
        }
        return token;
    }

    function loadMyAttendance() {
        $.ajax({
            type: 'GET', url: ATT_BASE_URL + 'attendance/my_item',
            beforeSend: function() { $('#my-attendance-tbody').html('<tr><td colspan="4" class="text-center"><div class="spinner-border text-primary"></div></td></tr>'); },
            success: function(data) { $('#my-attendance-tbody').html(data); },
            error: function() { $('#my-attendance-tbody').html('<tr><td colspan="4" class="text-center text-danger">Gagal memuat data.</td></tr>'); }
        });
    }

    var LABEL_MAP = { masuk:'Absen Masuk', istirahat_keluar:'Absen Istirahat', istirahat_masuk:'Absen Masuk Kembali', pulang:'Absen Pulang' };
    var PILL_MAP  = { masuk:'pillMasuk', istirahat_keluar:'pillIstKeluar', istirahat_masuk:'pillIstMasuk', pulang:'pillPulang' };

    function fmtJam(v){ return v ? v.substring(11,16) : '-'; }

    function renderStatus(d){
        punchState = d;
        var fotoEl = document.getElementById('pmFotoWajah');
        if (fotoEl) {
            if (d.photo_masuk) { fotoEl.src = ATT_BASE_URL + d.photo_masuk; fotoEl.style.display = ''; }
            else { fotoEl.style.display = 'none'; }
        }
        ['masuk','istirahat_keluar','istirahat_masuk','pulang'].forEach(function(k){
            var el = document.getElementById(PILL_MAP[k]);
            if (!el) return;
            el.querySelector('.val').textContent = fmtJam(d[k]);
            el.classList.toggle('done', !!d[k]);
        });
        var btn = document.getElementById('btn-punch');
        var ic  = document.getElementById('pmStatusIc');
        var card= document.getElementById('pmStatusCard');
        var title = document.getElementById('pmStatusTitle');
        var sub   = document.getElementById('pmStatusSub');
        if (!d.next_kind) {
            btn.style.display = 'none';
            card.style.background = '#f6ffed'; card.style.borderColor = '#b7eb8f';
            ic.className = 'bi bi-check-circle-fill'; ic.style.color = '#52c41a';
            title.textContent = 'Absensi hari ini sudah lengkap'; title.style.color = '#389e0d';
            sub.textContent = 'Semua 4 jenis absen sudah tercatat.';
        } else {
            btn.style.display = '';
            document.getElementById('btnPunchLabel').textContent = LABEL_MAP[d.next_kind];
            card.style.background = '#fff2f0'; card.style.borderColor = '#ffccc7';
            ic.className = 'bi bi-exclamation-circle-fill'; ic.style.color = '#ff4d4f';
            title.textContent = 'Belum ' + LABEL_MAP[d.next_kind]; title.style.color = '#cf1322';
            sub.textContent = 'Klik tombol di bawah untuk absen.';
        }
        btn.style.display = 'none';
        renderTombol(d);
    }

    var WINDOWS = {
        masuk:            ['06:00', '11:59'],
        istirahat_keluar: ['12:00', '13:00'],
        istirahat_masuk:  ['12:30', '16:59'],
        pulang:           ['17:00', '23:59']
    };

    function jamSekarang(){
        var d = new Date();
        return ('0'+d.getHours()).slice(-2) + ':' + ('0'+d.getMinutes()).slice(-2);
    }

    function renderTombol(d){
        var wrap = document.getElementById('pmBtnGroup');
        if (!wrap) return;
        var now = jamSekarang(), h = '';
        // Absen istirahat dicatat sistem otomatis pukul 12:00, jadi
        // tombolnya tidak perlu ada. Kalau dibiarkan, ada yang menekan
        // lebih awal dan fotonya ikut terkirim ke grup.
        var OTOMATIS_SISTEM = ['istirahat_keluar', 'istirahat_masuk'];
        ['masuk','istirahat_keluar','istirahat_masuk','pulang'].forEach(function(k){
            if (OTOMATIS_SISTEM.indexOf(k) !== -1) return;
            var sudah   = !!d[k];
            // Jendela jam diambil dari server: bergantung jadwal orangnya,
            // hari Sabtu, dan izin setengah hari. WINDOWS hanya cadangan.
            var W = (d.windows && d.windows[k]) ? d.windows[k] : WINDOWS[k];
            var dalam   = (now >= W[0] && now <= W[1]);
            var giliran = (d.next_kind === k);
            var aktif   = (!sudah && giliran && dalam);
            var ket = sudah ? 'sudah ' + fmtJam(d[k])
                    : (!giliran ? 'belum giliran'
                    : (!dalam ? W[0] + '-' + W[1] : ''));
            h += '<button type="button" class="btn btn-sm ' + (aktif ? 'btn-primary' : 'btn-outline-secondary') + ' pm-act" '
               + 'data-kind="' + k + '" ' + (aktif ? '' : 'disabled') + '>'
               + '<i class="bi bi-fingerprint me-1"></i>' + LABEL_MAP[k]
               + (ket ? ' <small style="opacity:.75">(' + ket + ')</small>' : '')
               + '</button>';
        });
        // Host live tidak punya jam tetap; tanpa keterangan ini mereka
        // tidak tahu kebagian sesi pagi atau sore sampai mencoba absen.
        if (d.shift_live) {
            var sl = d.shift_live;
            h = '<div style="width:100%;margin-bottom:10px;padding:8px 12px;'
              + 'background:#E8EDF8;border-left:4px solid #1F4696;border-radius:6px;'
              + 'font-size:13px;color:#1F2937">'
              + '<b>Shift ' + sl.shift + '</b> &middot; ' + sl.mulai + '&ndash;' + sl.sampai
              + (sl.pasti ? '' : ' <span style="opacity:.7">(sementara)</span>')
              + '</div>' + h;
        }

        wrap.innerHTML = h;
        wrap.querySelectorAll('.pm-act:not([disabled])').forEach(function(b){
            b.addEventListener('click', mulaiAbsen);
        });
    }

    function loadPunchStatus(){
        // ON_LEAVE sengaja tidak menghentikan pemuatan status lagi.
        $.getJSON(ATT_BASE_URL + 'attendance/punch_status', renderStatus);
    }

    function loadModels(cb){
        if (modelReady) { cb(); return; }
        if (modelLoading) { setTimeout(function(){ loadModels(cb); }, 300); return; }
        modelLoading = true;
        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(ATT_BASE_URL + 'assets/models'),
            faceapi.nets.faceLandmark68Net.loadFromUri(ATT_BASE_URL + 'assets/models'),
            faceapi.nets.faceRecognitionNet.loadFromUri(ATT_BASE_URL + 'assets/models')
        ]).then(function(){ modelReady = true; modelLoading = false; cb(); });
    }

    // panaskan model sejak halaman dibuka, biar tombol absen langsung siap
    setTimeout(function(){
        if (typeof faceapi !== 'undefined' && !modelReady && !modelLoading) {
            loadModels(function(){});
        }
    }, 600);

    function bunyiAbsen(ok){
        var teks = ok ? 'Selamat bekerja' : 'Silahkan coba lagi';
        try {
            if (navigator.vibrate) navigator.vibrate(ok ? 60 : [80, 60, 80]);
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                var u = new SpeechSynthesisUtterance(teks);
                u.lang = 'id-ID'; u.rate = 1; u.pitch = 1; u.volume = 1;
                var sr = window.speechSynthesis.getVoices();
                for (var i = 0; i < sr.length; i++) {
                    if (sr[i].lang && sr[i].lang.toLowerCase().indexOf('id') === 0) { u.voice = sr[i]; break; }
                }
                window.speechSynthesis.speak(u);
                return;
            }
            nadaCadangan(ok);
        } catch (e) { try { nadaCadangan(ok); } catch (e2) {} }
    }

    function nadaCadangan(ok){
        var AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) return;
        var ctx = new AC();
        (ok ? [880, 1320] : [420, 260]).forEach(function(f, i){
            var osc = ctx.createOscillator(), g = ctx.createGain();
            osc.type = 'sine'; osc.frequency.value = f;
            osc.connect(g); g.connect(ctx.destination);
            var t0 = ctx.currentTime + (i * 0.16);
            g.gain.setValueAtTime(0.0001, t0);
            g.gain.exponentialRampToValueAtTime(0.35, t0 + 0.02);
            g.gain.exponentialRampToValueAtTime(0.0001, t0 + 0.15);
            osc.start(t0); osc.stop(t0 + 0.16);
        });
        setTimeout(function(){ try { ctx.close(); } catch(e){} }, 800);
    }

    function tampilkanHasil(r){
        var ok = !!r.success;
        bunyiAbsen(ok);
        var card = document.getElementById('pmResultCard');
        card.className = 'pm-card pm-result ' + (ok ? 'ok' : 'fail');
        document.getElementById('pmResultIc').textContent = ok ? '✔' : '✕';
        document.getElementById('pmResultTitle').textContent = ok ? 'Absensi Berhasil!' : 'Absensi Gagal!';
        document.getElementById('pmResultSub').textContent = r.message || '';
        document.getElementById('pmJenis').textContent  = r.jenis || '-';
        document.getElementById('pmWaktu').textContent  = r.waktu || '-';
        document.getElementById('pmStatus').textContent = r.status_text || '-';
        document.getElementById('pmLokasi').textContent = r.lokasi || '-';
        document.getElementById('pmShift').textContent  = r.shift || '-';
        document.getElementById('pmResult').style.display = 'flex';
    }
    document.getElementById('pmTutup').addEventListener('click', function(){
        document.getElementById('pmResult').style.display = 'none';
        loadPunchStatus(); loadMyAttendance();
    });

    // Penanda "sedang mengirim". Tanpa ini layar kembali normal begitu
    // kamera ditutup, lalu diam sampai jawaban server datang -- di iPhone
    // itu bisa 3-4 detik karena fotonya ikut diunggah. Orang mengira
    // absennya gagal, menekan lagi, dan tampilan berubah jadi "gagal"
    // padahal yang pertama sudah masuk.
    function tampilkanTunggu(){
        var el = document.getElementById('punchTunggu');
        if (el) return el;
        el = document.createElement('div');
        el.id = 'punchTunggu';
        el.style.cssText =
            'position:fixed;inset:0;z-index:2147483000;display:flex;' +
            'align-items:center;justify-content:center;' +
            'background:rgba(15,23,42,0.45)';
        el.innerHTML =
            '<div style="background:#fff;border-radius:16px;padding:26px 32px;' +
            'text-align:center;box-shadow:0 16px 40px rgba(0,0,0,.25)">' +
            '<div style="width:34px;height:34px;margin:0 auto 14px;' +
            'border:3px solid #e2e8f0;border-top-color:#1F4696;border-radius:50%;' +
            'animation:putarTunggu .8s linear infinite"></div>' +
            '<div style="font-size:14px;font-weight:600;color:#0f172a">' +
            'Mengirim absen...</div>' +
            '<div style="font-size:12px;color:#64748b;margin-top:4px">' +
            'Tunggu sebentar, jangan tutup halaman.</div></div>' +
            '<style>@keyframes putarTunggu{to{transform:rotate(360deg)}}</style>';
        document.body.appendChild(el);
        return el;
    }

    function tutupTunggu(){
        var el = document.getElementById('punchTunggu');
        if (el) el.remove();
    }

    function kirimPunch(payload){
        var fd = new FormData();
        Object.keys(payload).forEach(function(k){ if (payload[k] !== null && payload[k] !== undefined) fd.append(k, payload[k]); });

        tampilkanTunggu();

        fetch(ATT_BASE_URL + 'attendance/punch', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(hasil){ tutupTunggu(); tampilkanHasil(hasil); })
            .catch(function(){
                tutupTunggu();
                tampilkanHasil({ success:false, message:'Terjadi kesalahan jaringan, coba lagi.' });
            });
    }

    var stream = null, stabilCount = 0, batalKamera = false;
    function tutupKamera(){
        if (stream) { stream.getTracks().forEach(function(t){ t.stop(); }); stream = null; }
        document.getElementById('pmCamera').style.display = 'none';
    }
    document.getElementById('pmCancel').addEventListener('click', function(){ batalKamera = true; tutupKamera(); });

    function bukaKameraLaluAbsen(lat, lng, acc){
        batalKamera = false; stabilCount = 0;
        document.getElementById('pmOval').classList.remove('ok');
        document.getElementById('pmMsg').textContent = 'Memuat model wajah...';
        document.getElementById('pmCamera').style.display = 'flex';
        loadModels(function(){
            document.getElementById('pmMsg').textContent = 'Arahkan wajah ke kamera...';
            navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }
            }).then(function(s){
                stream = s;
                var video = document.getElementById('pmVideo');
                video.srcObject = s;
                video.setAttribute('playsinline', '');
                video.setAttribute('autoplay', '');
                video.muted = true;

                var mulai = false;
                function jalan() {
                    if (mulai) return;
                    if (!video.videoWidth) return;   // belum ada gambar
                    mulai = true;
                    prosesFrame(lat, lng, acc);
                }

                // Safari iOS tidak selalu memulai pemutaran sendiri meski ada
                // atribut autoplay, dan layarnya jadi hitam padahal stream-nya
                // hidup. play() harus dipanggil manual, dan kesiapan gambar
                // dipantau lewat beberapa kejadian karena tidak semua browser
                // menyalakan yang sama.
                video.onloadedmetadata = jalan;
                video.onloadeddata     = jalan;
                video.onplaying        = jalan;

                var p = video.play();
                if (p && p.catch) { p.catch(function(){ /* diabaikan, ditangani di bawah */ }); }

                // Jaring pengaman: kalau setelah 2,5 detik gambarnya masih
                // belum muncul, beri tahu daripada membiarkan layar hitam.
                setTimeout(function(){
                    if (mulai) return;
                    if (video.videoWidth) { jalan(); return; }
                    document.getElementById('pmMsg').textContent =
                        'Kamera tidak menampilkan gambar. Tutup lalu buka lagi halaman ini, '
                      + 'atau pakai Safari kalau sedang memakai Chrome.';
                }, 2500);
            }).catch(function(e){
                var pesan = 'Gagal mengakses kamera.';
                if (e && e.name === 'NotAllowedError') {
                    pesan = 'Izin kamera ditolak. Buka Pengaturan browser dan izinkan kamera untuk situs ini.';
                } else if (e && e.name === 'NotReadableError') {
                    pesan = 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi itu lalu coba lagi.';
                }
                document.getElementById('pmMsg').textContent = pesan;
                setTimeout(tutupKamera, 3500);
            });
        });
    }

    function prosesFrame(lat, lng, acc){
        if (batalKamera) return;
        var video = document.getElementById('pmVideo');
        var oval  = document.getElementById('pmOval');
        var msg   = document.getElementById('pmMsg');
        // deteksi ringan dulu (tanpa descriptor) — jauh lebih cepat
        var opsi = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });
        faceapi.detectSingleFace(video, opsi)
            .then(function(deteksi){
                if (batalKamera) return;
                if (deteksi) {
                    stabilCount++;
                    oval.classList.add('ok');
                    msg.textContent = 'Wajah terdeteksi, memverifikasi...';
                    if (stabilCount >= 1) {
                        msg.textContent = 'Memverifikasi wajah...';
                        var canvas = document.createElement('canvas');
                        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
                        canvas.getContext('2d').drawImage(video, 0, 0);
                        var photo = canvas.toDataURL('image/jpeg', 0.7);
                        // descriptor dihitung SEKALI saja di sini
                        faceapi.detectSingleFace(video, opsi)
                            .withFaceLandmarks().withFaceDescriptor()
                            .then(function(hasil){
                                if (batalKamera) return;
                                if (!hasil) {
                                    stabilCount = 0;
                                    msg.textContent = 'Wajah kurang jelas, coba lagi...';
                                    setTimeout(function(){ prosesFrame(lat, lng, acc); }, 120);
                                    return;
                                }
                                var descriptor = JSON.stringify(Array.from(hasil.descriptor));
                                tutupKamera();
                                kirimPunch({ latitude: lat, longitude: lng, accuracy: acc, device_token: getDeviceToken(), descriptor: descriptor, photo_base64: photo, has_camera: 1 });
                            });
                        return;
                    }
                } else {
                    stabilCount = 0; oval.classList.remove('ok');
                    msg.textContent = 'Posisikan wajah di dalam bingkai oval';
                }
                setTimeout(function(){ prosesFrame(lat, lng, acc); }, 120);
            });
    }

    // Cek apakah perangkat punya kamera (laptop & HP punya, PC tower biasanya tidak)
    function adaKamera(cb){
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) { cb(false); return; }
        navigator.mediaDevices.enumerateDevices()
            .then(function(list){
                cb(list.some(function(d){ return d.kind === 'videoinput'; }));
            })
            .catch(function(){ cb(false); });
    }

    // Absen setelah istirahat cukup satu tap. Orangnya sudah
    // terverifikasi wajah saat absen masuk pagi tadi, dan memotret
    // ulang tiap siang hanya menambah keberatan tanpa menambah bukti.
    // Lokasi dan perangkat tetap diperiksa, jadi tetap harus di kantor.
    function tanpaWajah(){
        return punchState && punchState.next_kind === 'istirahat_masuk';
    }

    // GPS dipanaskan sejak halaman dibuka. Dulu lokasi baru dicari setelah
    // tombol ditekan dengan maximumAge 0, jadi tiap absen menunggu 8-10 detik.
    // Sekarang posisi terakhir dipakai kalau masih segar (<30 detik) dan cukup
    // akurat (<100 m); kalau tidak, baru minta posisi baru seperti sebelumnya.
    // Validasi radius kantor tetap di server, tidak berubah.
    var posTerakhir = null;
    if (navigator.geolocation && navigator.geolocation.watchPosition) {
        try {
            navigator.geolocation.watchPosition(function(p){ posTerakhir = p; }, function(){},
                { enableHighAccuracy: true, maximumAge: 15000, timeout: 20000 });
        } catch (e) {}
    }
    function ambilLokasi(ok, gagal, batas){
        if (posTerakhir && (Date.now() - posTerakhir.timestamp) < 30000
            && (posTerakhir.coords.accuracy || 9999) <= 100) { ok(posTerakhir); return; }
        navigator.geolocation.getCurrentPosition(function(p){ posTerakhir = p; ok(p); }, gagal,
            { enableHighAccuracy: true, timeout: batas, maximumAge: 30000 });
    }

    function mulaiAbsen(){
        if (tanpaWajah()) {
            if (!navigator.geolocation) {
                kirimPunch({ device_token: getDeviceToken() });
                return;
            }
            Swal.fire({ title: 'Mengambil lokasi...', allowOutsideClick: false,
                        didOpen: function(){ Swal.showLoading(); } });
            ambilLokasi(function(pos){
                Swal.close();
                kirimPunch({
                    latitude: pos.coords.latitude,
                    longitude: pos.coords.longitude,
                    accuracy: Math.round(pos.coords.accuracy || 9999),
                    device_token: getDeviceToken()
                });
            }, function(){
                Swal.close();
                kirimPunch({ device_token: getDeviceToken() });
            }, 10000);
            return;
        }

        if (!navigator.geolocation) {
            adaKamera(function(punyaKamera){
                if (punyaKamera) { bukaKameraLaluAbsen(null, null, null); }
                else { kirimPunch({ device_token: getDeviceToken() }); }
            });
            return;
        }
        Swal.fire({ title: 'Mengambil lokasi...', allowOutsideClick: false, didOpen: function(){ Swal.showLoading(); } });
        ambilLokasi(function(pos){
            Swal.close();
            var acc = Math.round(pos.coords.accuracy || 9999);
            adaKamera(function(punyaKamera){
            if (punyaKamera) {
                if (!punchState || !punchState.has_face_enrolled) {
                    Swal.fire({
                        icon: 'info', title: 'Wajah Belum Terdaftar',
                        text: 'Daftarkan wajah kamu dulu sebelum bisa absen lewat HP.',
                        confirmButtonText: 'Daftarkan Sekarang', showCancelButton: true, cancelButtonText: 'Nanti'
                    }).then(function(r){ if (r.isConfirmed) window.location.href = ATT_BASE_URL + 'attendance/enroll_face'; });
                    return;
                }
                bukaKameraLaluAbsen(pos.coords.latitude, pos.coords.longitude, acc);
            } else {
                kirimPunch({ latitude: pos.coords.latitude, longitude: pos.coords.longitude, accuracy: acc, device_token: getDeviceToken() });
            }
            });
        }, function(){
            // GPS gagal/timeout. Kalau perangkat punya kamera, wajah TETAP wajib -
            // jangan pernah kirim punch tanpa wajah, itu bikin punch dobel.
            Swal.close();
            adaKamera(function(punyaKamera){
                if (punyaKamera) {
                    if (!punchState || !punchState.has_face_enrolled) {
                        Swal.fire({
                            icon: 'info', title: 'Wajah Belum Terdaftar',
                            text: 'Daftarkan wajah kamu dulu sebelum bisa absen.',
                            confirmButtonText: 'Daftarkan Sekarang', showCancelButton: true, cancelButtonText: 'Nanti'
                        }).then(function(r){ if (r.isConfirmed) window.location.href = ATT_BASE_URL + 'attendance/enroll_face'; });
                        return;
                    }
                    bukaKameraLaluAbsen(null, null, null);
                } else {
                    kirimPunch({ device_token: getDeviceToken() });
                }
            });
        }, 8000);
    }

    $(document).ready(function(){
        loadMyAttendance();
        loadPunchStatus();
        $('#btn-punch').on('click', mulaiAbsen);
    });
})();
</script>

<style>
/* ===== PAKSA KARTU HASIL ABSEN TETAP GELAP & TERBACA ===== */
html body .pm-overlay .pm-card.pm-result,
html body .pm-overlay .pm-card{
  background:#0b1220 !important;
  border:0 !important;
  box-shadow:none !important;
  max-width:380px !important;
}
html body .pm-detail,
html body .pm-detail tbody,
html body .pm-detail tr,
html body .pm-detail td{
  background:transparent !important;
  border:0 !important;
  border-radius:0 !important;
  box-shadow:none !important;
}
html body .pm-detail{
  width:100% !important;
  border-collapse:collapse !important;
  table-layout:fixed !important;
  font-size:.86rem !important;
}
html body .pm-detail td{
  padding:9px 0 !important;
  border-bottom:1px solid #1e293b !important;
  color:#e2e8f0 !important;
  font-weight:500 !important;
  white-space:normal !important;
  word-break:break-word !important;
  vertical-align:top !important;
  text-align:right !important;
}
html body .pm-detail td:first-child{
  color:#94a3b8 !important;
  width:38% !important;
  font-weight:400 !important;
  text-align:left !important;
}
html body .pm-title{color:#fff !important;font-size:1.15rem !important}
html body .pm-sub{color:#b6c0d6 !important}
</style>

<script>
(function(){
  // ===== SUARA LEBIH KERAS =====
  window.bunyiAbsen = function(ok){
    var teks = ok ? 'Selamat bekerja' : 'Silahkan coba lagi';
    try { if (navigator.vibrate) navigator.vibrate(ok ? [80,50,80] : [120,80,120,80,120]); } catch(e){}

    // nada pembuka yang diperkuat, biar menarik perhatian
    try {
      var AC = window.AudioContext || window.webkitAudioContext;
      if (AC) {
        var ctx = new AC();
        var g = ctx.createGain();
        g.gain.value = 2.5;                 // diperkuat
        g.connect(ctx.destination);
        (ok ? [880, 1320] : [420, 260]).forEach(function(f, i){
          var osc = ctx.createOscillator(), gg = ctx.createGain();
          osc.type = 'square'; osc.frequency.value = f;
          osc.connect(gg); gg.connect(g);
          var t0 = ctx.currentTime + (i * 0.13);
          gg.gain.setValueAtTime(0.0001, t0);
          gg.gain.exponentialRampToValueAtTime(0.9, t0 + 0.02);
          gg.gain.exponentialRampToValueAtTime(0.0001, t0 + 0.12);
          osc.start(t0); osc.stop(t0 + 0.13);
        });
        setTimeout(function(){ try { ctx.close(); } catch(e){} }, 900);
      }
    } catch(e){}

    // ucapan, diulang 2x biar jelas terdengar
    try {
      if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        var ucap = function(){
          var u = new SpeechSynthesisUtterance(teks);
          u.lang = 'id-ID'; u.volume = 1; u.rate = 0.95; u.pitch = 1.1;
          var sr = window.speechSynthesis.getVoices();
          for (var i = 0; i < sr.length; i++) {
            if (sr[i].lang && sr[i].lang.toLowerCase().indexOf('id') === 0) { u.voice = sr[i]; break; }
          }
          window.speechSynthesis.speak(u);
        };
        setTimeout(ucap, 320);
        setTimeout(ucap, 1600);
      }
    } catch(e){}
  };
})();
</script>
