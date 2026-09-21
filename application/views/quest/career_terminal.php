<style>
#cxStage{position:fixed;inset:0;z-index:99999;background:#04060d;overflow:hidden;font-family:'JetBrains Mono','SF Mono',Menlo,Consolas,monospace}
#cxStage.done{position:relative;inset:auto;z-index:1;border-radius:18px;height:auto;min-height:78vh;margin:6px 0 26px;box-shadow:0 30px 90px rgba(0,0,0,.5);border:1px solid #16324a}
#cxCanvas{position:absolute;inset:0;width:100%;height:100%}
.cx-vig{position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse at center,transparent 42%,rgba(0,0,0,.82) 100%)}
.cx-scanlines{position:absolute;inset:0;pointer-events:none;opacity:.16;background:repeating-linear-gradient(0deg,rgba(0,255,180,.09) 0 1px,transparent 1px 3px)}

/* --- OPENING --- */
#cxIntro{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:22px;z-index:5}
.cx-ring{position:relative;width:180px;height:180px}
.cx-ring i{position:absolute;inset:0;border-radius:50%;border:1.5px solid rgba(61,220,151,.28);border-top-color:#3ddc97;animation:cxSpin 1.5s linear infinite}
.cx-ring i:nth-child(2){inset:18px;border-top-color:transparent;border-right-color:#4aa8ff;animation-duration:2.2s;animation-direction:reverse}
.cx-ring i:nth-child(3){inset:36px;border-top-color:transparent;border-left-color:#a78bfa;animation-duration:3s}
@keyframes cxSpin{to{transform:rotate(360deg)}}
.cx-core{position:absolute;inset:60px;border-radius:50%;background:radial-gradient(circle,#3ddc97 0%,rgba(61,220,151,.1) 65%,transparent 72%);animation:cxPulse 1.6s ease-in-out infinite}
@keyframes cxPulse{0%,100%{transform:scale(1);opacity:.85}50%{transform:scale(1.18);opacity:1}}
#cxIntroTxt{color:#3ddc97;font-size:.92rem;letter-spacing:5px;text-transform:uppercase;text-shadow:0 0 22px rgba(61,220,151,.65);min-height:22px}
#cxIntroSub{color:#3c5a78;font-size:.74rem;letter-spacing:2.5px}
.cx-prog{width:270px;height:2px;background:#0f2135;border-radius:99px;overflow:hidden}
.cx-prog i{display:block;height:100%;width:0;background:linear-gradient(90deg,#3ddc97,#4aa8ff,#a78bfa);transition:width .35s ease;box-shadow:0 0 14px rgba(61,220,151,.75)}

/* --- KONTEN --- */
#cxMain{position:relative;z-index:4;opacity:0;transition:opacity .8s ease;padding:30px 34px;max-height:100%;overflow-y:auto}
#cxMain.show{opacity:1}
#cxMain::-webkit-scrollbar{width:6px}
#cxMain::-webkit-scrollbar-thumb{background:#1b3a57;border-radius:9px}
.cx-hd{display:flex;align-items:center;gap:14px;margin-bottom:6px}
.cx-hd .av{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#3ddc97,#4aa8ff);display:flex;align-items:center;justify-content:center;color:#04060d;font-weight:800;font-size:1.25rem;box-shadow:0 0 26px rgba(61,220,151,.42)}
.cx-hd h3{color:#eaf3ff;margin:0;font-size:1.28rem;letter-spacing:.4px}
.cx-hd .sub{color:#5c7a9c;font-size:.78rem;letter-spacing:1.2px}
.cx-sep{height:1px;background:linear-gradient(90deg,#1b3a57,transparent);margin:18px 0}
.cx-log{color:#4d6b8a;font-size:.8rem;line-height:2}
.cx-log b{color:#3ddc97;font-weight:400}
.cx-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(158px,1fr));gap:14px;margin:20px 0}
.cx-stat{position:relative;background:linear-gradient(160deg,#0b1524,#080e1a);border:1px solid #17324c;border-radius:14px;padding:18px;overflow:hidden;opacity:0;transform:translateY(14px);animation:cxUp .6s ease forwards}
.cx-stat::after{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,#3ddc97,transparent)}
@keyframes cxUp{to{opacity:1;transform:none}}
.cx-stat .n{color:#3ddc97;font-size:2rem;font-weight:800;line-height:1;text-shadow:0 0 20px rgba(61,220,151,.4)}
.cx-stat .l{color:#4d6b8a;font-size:.66rem;letter-spacing:1.4px;text-transform:uppercase;margin-top:7px}
.cx-card{position:relative;background:linear-gradient(160deg,#0b1524,#080e1a);border:1px solid #17324c;border-left:3px solid #3ddc97;border-radius:14px;padding:20px 22px;margin:16px 0;opacity:0;transform:translateX(-16px);animation:cxSlide .6s ease forwards}
@keyframes cxSlide{to{opacity:1;transform:none}}
.cx-card h6{color:#eaf3ff;margin:0 0 10px;font-size:1.02rem}
.cx-badge{display:inline-block;background:#0f2135;color:#4aa8ff;border:1px solid #1b3a57;padding:3px 12px;border-radius:99px;font-size:.71rem;margin:0 7px 5px 0}
.cx-bar-bg{height:8px;background:#0f2135;border-radius:99px;overflow:hidden;margin-top:11px}
.cx-bar-fill{height:100%;width:0;border-radius:99px;background:linear-gradient(90deg,#3ddc97,#4aa8ff);transition:width 1.7s cubic-bezier(.16,.84,.3,1);box-shadow:0 0 16px rgba(61,220,151,.5)}
.cx-cursor{display:inline-block;width:9px;height:15px;background:#3ddc97;vertical-align:-2px;animation:cxBlink 1s step-end infinite}
@keyframes cxBlink{50%{opacity:0}}
.cx-back{background:transparent;border:1px solid #1b3a57;color:#4aa8ff;border-radius:10px;padding:11px 22px;font-family:inherit;font-size:.84rem;cursor:pointer;transition:.25s;margin-top:22px}
.cx-back:hover{background:#0f2135;border-color:#3ddc97;color:#3ddc97;box-shadow:0 0 22px rgba(61,220,151,.25)}
@media(max-width:767px){#cxMain{padding:18px 15px}.cx-hd h3{font-size:1.05rem}.cx-stat .n{font-size:1.6rem}}
</style>

<div id="cxStage">
  <canvas id="cxCanvas"></canvas>
  <div class="cx-scanlines"></div>
  <div class="cx-vig"></div>

  <div id="cxIntro">
    <div class="cx-ring"><i></i><i></i><i></i><div class="cx-core"></div></div>
    <div id="cxIntroTxt"></div>
    <div class="cx-prog"><i id="cxProg"></i></div>
    <div id="cxIntroSub">MONTERA CAREER ENGINE</div>
  </div>

  <div id="cxMain"></div>
</div>

<script>
(function(){
  var D   = <?= json_encode($payload ?? []) ?>;
  var p   = D.user_profile || {};
  var st  = D.performance_stats || {};
  var rec = (D.recommendations && D.recommendations.length) ? D.recommendations : (D.available_paths || []);

  /* ---------- latar: partikel + garis ---------- */
  var cv = document.getElementById('cxCanvas'), cx = cv.getContext('2d'), W, H, dots = [];
  function ukur(){
    var r = window.devicePixelRatio || 1;
    W = cv.clientWidth; H = cv.clientHeight;
    cv.width = W * r; cv.height = H * r; cx.setTransform(r,0,0,r,0,0);
  }
  function isiDots(){
    dots = [];
    var n = Math.min(90, Math.round(W*H/16000));
    for (var i=0;i<n;i++) dots.push({x:Math.random()*W,y:Math.random()*H,vx:(Math.random()-.5)*.32,vy:(Math.random()-.5)*.32,r:Math.random()*1.7+.5});
  }
  function gambar(){
    cx.clearRect(0,0,W,H);
    for (var i=0;i<dots.length;i++){
      var a=dots[i]; a.x+=a.vx; a.y+=a.vy;
      if(a.x<0||a.x>W)a.vx*=-1; if(a.y<0||a.y>H)a.vy*=-1;
      cx.beginPath(); cx.arc(a.x,a.y,a.r,0,6.283);
      cx.fillStyle='rgba(61,220,151,.55)'; cx.fill();
      for (var j=i+1;j<dots.length;j++){
        var b=dots[j], dx=a.x-b.x, dy=a.y-b.y, d=dx*dx+dy*dy;
        if(d<15000){ cx.beginPath(); cx.moveTo(a.x,a.y); cx.lineTo(b.x,b.y);
          cx.strokeStyle='rgba(74,168,255,'+(0.14*(1-d/15000))+')'; cx.lineWidth=.6; cx.stroke(); }
      }
    }
    requestAnimationFrame(gambar);
  }
  ukur(); isiDots(); gambar();
  window.addEventListener('resize', function(){ ukur(); isiDots(); });

  /* ---------- opening ---------- */
  var langkah = [
    ['MENGHUBUNGKAN', 18],
    ['MEMUAT PROFIL', 40],
    ['MEMBACA JENJANG', 62],
    ['MENGANALISIS PERFORMA', 84],
    ['MENYUSUN REKOMENDASI', 100]
  ];
  var el = document.getElementById('cxIntroTxt'), pr = document.getElementById('cxProg'), k = 0;

  function ketik(teks, sel){
    var i = 0; sel.textContent = '';
    var iv = setInterval(function(){
      sel.textContent = teks.slice(0, ++i);
      if (i >= teks.length) clearInterval(iv);
    }, 32);
  }

  function jalan(){
    if (k >= langkah.length) { setTimeout(tampil, 620); return; }
    ketik(langkah[k][0], el);
    pr.style.width = langkah[k][1] + '%';
    k++;
    setTimeout(jalan, 720);
  }
  setTimeout(jalan, 380);

  /* ---------- konten ---------- */
  function tampil(){
    var ini = (p.full_name||'M').trim().charAt(0).toUpperCase();
    var h = '<div class="cx-hd"><div class="av">'+ini+'</div><div>'
          + '<h3>'+(p.full_name||'-')+'</h3>'
          + '<div class="sub">'+(p.current_position||'-')+' &nbsp;&middot;&nbsp; '+(p.department||'-')+'</div>'
          + '</div></div><div class="cx-sep"></div>'
          + '<div class="cx-log">'
          + '<div>montera@career:~$ <b>analyze --deep</b></div>'
          + '<div>[<b>OK</b>] jenjang saat ini: '+(p.current_level||'-')+'</div>'
          + '<div>[<b>OK</b>] '+rec.length+' jalur karier terdeteksi</div>'
          + '</div>';

    h += '<div class="cx-grid">'
       + kartu(st.total_completed||0, 'Quest Selesai', 0)
       + kartu(Math.round(st.avg_performance||0), 'Rata Performa', .1)
       + kartu(p.score||0, 'Total Skor', .2)
       + kartu(rec.length, 'Jalur Tersedia', .3)
       + '</div>';

    if (!rec.length) {
      var belum = (p.department||'') === 'Belum Ditentukan';
      if (belum) {
        h += '<div class="cx-card" style="border-left-color:#4aa8ff;animation-delay:.4s">'
           + '<h6>&#9432; Divisi belum ditentukan</h6>'
           + '<div style="color:#4d6b8a;font-size:.85rem;line-height:1.75">'
           + 'Kamu sudah terdaftar, tapi divisimu belum ditetapkan. Hubungi HR atau atasanmu untuk penempatan.<br><br>'
           + 'Begitu divisimu diatur, jalur karier dan target quest akan muncul otomatis di halaman ini.'
           + '</div></div>';
      } else {
        h += '<div class="cx-card" style="border-left-color:#f0b429;animation-delay:.4s">'
           + '<h6>&#9873; Puncak jenjang tercapai</h6>'
           + '<div style="color:#4d6b8a;font-size:.85rem;line-height:1.75">Belum ada posisi di atas jabatan saat ini. '
           + 'Fokus berikutnya: memperdalam kompetensi, mentoring tim, dan memimpin inisiatif lintas divisi.</div>'
           + '</div>';
      }
    } else {
      rec.forEach(function(r, i){
        var d = r.path || r;
        var s = Math.round(r.readiness_score || 0);
        var w = s>=80 ? '#3ddc97' : (s>=60 ? '#4aa8ff' : '#f0b429');
        h += '<div class="cx-card" style="border-left-color:'+w+';animation-delay:'+(.4+i*.13)+'s">'
           + '<h6>&#9656; '+(d.target_position||'Posisi')+'</h6>'
           + '<span class="cx-badge">'+(d.target_level||'-')+'</span>'
           + '<span class="cx-badge">'+(d.target_department||'-')+'</span>'
           + (d.promotion_quest_title ? '<span class="cx-badge">quest: '+d.promotion_quest_title+'</span>' : '')
           + '<div style="color:#4d6b8a;font-size:.82rem;margin-top:10px">Kesiapan <span style="color:'+w+';font-weight:700">'+s+'%</span></div>'
           + '<div class="cx-bar-bg"><div class="cx-bar-fill" data-w="'+s+'" style="background:linear-gradient(90deg,'+w+',#4aa8ff)"></div></div>'
           + ((r.reasoning&&r.reasoning.length) ? '<div style="color:#3c5a78;font-size:.76rem;margin-top:10px">&bull; '+r.reasoning.join('<br>&bull; ')+'</div>' : '')
           + '</div>';
      });
    }

    h += '<div class="cx-log" style="margin-top:8px">montera@career:~$ <b>analisis selesai</b> <span class="cx-cursor"></span></div>'
       + '<button class="cx-back" onclick="location.href=\'<?= base_url() ?>profile\'">&larr; kembali ke Akun Saya</button>';

    var m = document.getElementById('cxMain');
    m.innerHTML = h;
    document.getElementById('cxIntro').style.transition='opacity .55s ease';
    document.getElementById('cxIntro').style.opacity='0';

    setTimeout(function(){
      document.getElementById('cxIntro').style.display='none';
      document.getElementById('cxStage').classList.add('done');
      m.classList.add('show');
      ukur(); isiDots();
      setTimeout(function(){
        m.querySelectorAll('.cx-bar-fill').forEach(function(b){ b.style.width=(b.dataset.w||0)+'%'; });
      }, 550);
      angka(m);
    }, 560);
  }

  function kartu(n,l,dl){
    return '<div class="cx-stat" style="animation-delay:'+dl+'s"><div class="n" data-n="'+n+'">0</div><div class="l">'+l+'</div></div>';
  }

  function angka(root){
    root.querySelectorAll('.cx-stat .n').forEach(function(e){
      var akhir = parseInt(e.dataset.n||0), mulai = performance.now(), dur = 1100;
      function tik(t){
        var v = Math.min(1,(t-mulai)/dur), ease = 1-Math.pow(1-v,3);
        e.textContent = Math.round(akhir*ease);
        if (v<1) requestAnimationFrame(tik);
      }
      requestAnimationFrame(tik);
    });
  }
})();
</script>
