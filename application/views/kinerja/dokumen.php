<?php $halaman='dokumen'; $this->load->view('kinerja/_tabs', array('teams'=>$teams,'team_id'=>$team_id,'halaman'=>$halaman)); ?>
<style>
.dk-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;flex-wrap:wrap}
.dk-add{background:#6E4FA8;color:#fff;border:0;border-radius:11px;padding:10px 20px;font-size:.86rem;font-weight:500;cursor:pointer;transition:.18s;box-shadow:0 3px 10px rgba(110,79,168,.28)}
.dk-add:hover{background:#5b3f95;transform:translateY(-1px);box-shadow:0 6px 16px rgba(110,79,168,.35)}
.dk-jml{font-size:.8rem;color:#94a3b8}

.dk-drop{border:2px dashed #cbd5e1;border-radius:14px;padding:34px 20px;text-align:center;cursor:pointer;transition:.2s;background:#fafbfe}
.dk-drop:hover,.dk-drop.aktif{border-color:#6E4FA8;background:#f7f4fd}
.dk-drop i{font-size:2.4rem;color:#94a3b8;display:block;margin-bottom:10px}
.dk-drop .t{font-size:.9rem;color:#334155;font-weight:500}
.dk-drop .s{font-size:.76rem;color:#94a3b8;margin-top:5px}

.dk-prev{display:flex;align-items:center;gap:14px;background:#f8fafc;border:1px solid #e2e6ee;border-radius:12px;padding:12px 14px}
.dk-prev img{width:64px;height:64px;object-fit:cover;border-radius:9px;flex:0 0 auto}
.dk-prev .ic{width:64px;height:64px;border-radius:9px;background:#eef2ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:1.7rem;flex:0 0 auto}
.dk-prev .nm{font-size:.86rem;font-weight:600;color:#1e293b;word-break:break-word;line-height:1.35}
.dk-prev .sz{font-size:.74rem;color:#94a3b8;margin-top:3px}
.dk-prev .x{margin-left:auto;border:0;background:none;color:#ef4444;cursor:pointer;font-size:1.1rem;flex:0 0 auto}

.fl-card{position:relative;text-align:left !important;padding:0 !important;overflow:hidden;border-radius:14px !important}
.fl-media{display:block;position:relative;background:#f1f5f9}
.fl-media img,.fl-media video{width:100%;height:158px;object-fit:cover;display:block}
.fl-media .box{height:158px;display:flex;align-items:center;justify-content:center}
.fl-media .box i{font-size:2.8rem}
.fl-media .play{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:2.3rem;text-shadow:0 3px 10px rgba(0,0,0,.6);pointer-events:none}
.fl-body{padding:13px 15px}
.fl-judul{font-size:.95rem;font-weight:600;color:#1e293b;line-height:1.4;margin-bottom:7px;word-break:break-word}
.fl-oleh{display:flex;align-items:center;gap:7px;font-size:.74rem;color:#64748b}
.fl-oleh .av{width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,#6E4FA8,#4aa8ff);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:700;flex:0 0 auto}
.fl-info{font-size:.71rem;color:#94a3b8;margin-top:6px;display:flex;gap:8px;flex-wrap:wrap}
.fl-note{font-size:.77rem;color:#64748b;margin-top:8px;padding-top:8px;border-top:1px solid #eef1f6;line-height:1.6;white-space:pre-wrap}
.fl-aksi{display:flex;gap:6px;position:absolute;top:9px;right:9px;opacity:0;transition:.18s}
.fl-card:hover .fl-aksi{opacity:1}
.fl-aksi button{border:0;background:rgba(255,255,255,.95);border-radius:8px;width:29px;height:29px;font-size:.8rem;cursor:pointer;box-shadow:0 2px 7px rgba(0,0,0,.2);transition:.15s}
.fl-aksi .ubah{color:#475569}
.fl-aksi .ubah:hover{background:#fff;color:#6E4FA8}
.fl-aksi .hapus{color:#ef4444}
.fl-aksi .hapus:hover{background:#fff}
@media(max-width:767.98px){.fl-aksi{opacity:1}}
.fl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px}
.fl-card{background:#fff;border:1px solid #e5e8f0;border-radius:12px;padding:16px;text-align:center;transition:.2s;position:relative}
.fl-card:hover{box-shadow:0 4px 14px rgba(0,0,0,.09)}
.fl-ic{font-size:2.1rem;margin-bottom:8px}
.fl-thumb{width:100%;height:118px;object-fit:cover;border-radius:8px;margin-bottom:8px;background:#f1f5f9;display:block}
.fl-box{height:118px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border-radius:8px;margin-bottom:8px}
.fl-box i{font-size:2.6rem}
.fl-nm{font-size:.8rem;color:#1e293b;font-weight:500;word-break:break-word;line-height:1.35}
.fl-mt{font-size:.7rem;color:#94a3b8;margin-top:6px}
.fl-del{position:absolute;top:8px;right:8px;opacity:0;transition:.2s}
.fl-card:hover .fl-del{opacity:1}
#lbBox{position:fixed;inset:0;background:rgba(8,11,20,.94);z-index:20000;display:none;align-items:center;justify-content:center;padding:40px 20px}
#lbBox.on{display:flex}
#lbBox img,#lbBox video{max-width:92vw;max-height:82vh;object-fit:contain;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,.6);background:#000}
#lbBox iframe{width:92vw;height:82vh;border:0;border-radius:10px;background:#fff}
#lbTutup{position:absolute;top:18px;right:24px;background:rgba(255,255,255,.14);border:0;color:#fff;width:42px;height:42px;border-radius:50%;font-size:1.5rem;cursor:pointer;line-height:1}
#lbTutup:hover{background:rgba(255,255,255,.28)}
#lbNama{position:absolute;bottom:22px;left:0;right:0;text-align:center;color:#cbd5e1;font-size:.84rem;padding:0 20px}
#lbUnduh{position:absolute;top:18px;right:80px;background:rgba(255,255,255,.14);border:0;color:#fff;padding:9px 16px;border-radius:9px;font-size:.82rem;cursor:pointer;text-decoration:none}
#lbUnduh:hover{background:rgba(255,255,255,.28);color:#fff}
.fl-card a{cursor:zoom-in}
.mk-it{border-bottom:1px solid #eef1f6;padding:8px 0}
.mk-it:last-child{border-bottom:0}
.mk-nm{font-size:.78rem;font-weight:600;color:#1e293b}
.mk-tg{font-size:.66rem;color:#94a3b8;margin-left:6px}
.mk-tx{font-size:.82rem;color:#334155;margin-top:2px;white-space:pre-wrap;word-break:break-word}
.mk-hp{font-size:.7rem;color:#ef4444;cursor:pointer;border:0;background:none;padding:0;margin-top:2px}
.mk-av{width:32px;height:32px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.76rem;font-weight:700;flex:0 0 auto}
.mk-it{display:flex;gap:10px}
.mk-emo-btn{border:1px solid #e2e6ee;background:#fff;border-radius:999px;width:36px;height:36px;font-size:1.05rem;cursor:pointer;line-height:1}
.mk-emo-btn.aktif{background:#eef2ff;border-color:#6366f1}
.mk-cact{font-size:.7rem;cursor:pointer;border:0;background:none;padding:0;margin-top:2px;margin-right:10px}
.mk-cdel{color:#ef4444}
.mk-cedit{color:#64748b}
#mkConfirmBox{position:fixed;inset:0;background:rgba(15,18,28,.55);z-index:30000;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .18s ease}
#mkConfirmBox.on{opacity:1;pointer-events:auto}
.mk-cf-card{background:#fff;border-radius:16px;padding:26px 26px 20px;width:340px;max-width:90vw;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.28);transform:translateY(14px) scale(.94);transition:transform .2s cubic-bezier(.2,.9,.3,1.2)}
#mkConfirmBox.on .mk-cf-card{transform:translateY(0) scale(1)}
.mk-cf-ic{width:52px;height:52px;border-radius:50%;background:#fee2e2;color:#ef4444;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.4rem}
.mk-cf-title{font-size:.98rem;font-weight:600;color:#1e293b;margin-bottom:6px}
.mk-cf-sub{font-size:.8rem;color:#94a3b8;margin-bottom:18px}
.mk-cf-btns{display:flex;gap:10px}
.mk-cf-btns button{flex:1;border-radius:9px;padding:8px 0;font-size:.85rem;border:0;cursor:pointer}
.mk-cf-cancel{background:#f1f5f9;color:#334155}
.mk-cf-ok{background:#ef4444;color:#fff}
#mkMedia img,#mkMedia video{max-width:100%;max-height:60vh;width:auto;height:auto;object-fit:contain;display:block;margin:0 auto;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.12)}
#mkMedia iframe{width:100%;max-width:640px;height:60vh;border:0;margin:0 auto}
@media(max-width:767.98px){ #mkMedia img,#mkMedia video{max-height:42vh} }
.fl-empty{text-align:center;padding:60px 20px;color:#94a3b8}
</style>

<style>
#dkConfirmBox{position:fixed;inset:0;z-index:30000;background:rgba(24,19,45,.5);backdrop-filter:blur(5px) saturate(120%);-webkit-backdrop-filter:blur(5px) saturate(120%);display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s ease}
#dkConfirmBox.on{opacity:1;pointer-events:auto}
.dk-cf-card{width:340px;max-width:calc(100vw - 40px);background:#fff;border-radius:20px;padding:30px 26px 22px;text-align:center;box-shadow:0 30px 70px -20px rgba(20,15,60,.45),0 0 0 1px rgba(255,255,255,.6) inset;transform:translateY(16px) scale(.93);opacity:0;transition:transform .36s cubic-bezier(.2,1.1,.3,1),opacity .25s ease}
#dkConfirmBox.on .dk-cf-card{transform:translateY(0) scale(1);opacity:1}
.dk-cf-card.dk-cf-shake{animation:dkShake .4s ease}
@keyframes dkShake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}
.dk-cf-icwrap{position:relative;width:64px;height:64px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center}
.dk-cf-ring{position:absolute;inset:0;border-radius:50%;background:#fdeeed}
.dk-cf-ring.pulse{animation:dkPulse 1.8s ease-out infinite}
@keyframes dkPulse{0%{box-shadow:0 0 0 0 rgba(224,72,61,.28)}70%{box-shadow:0 0 0 13px rgba(224,72,61,0)}100%{box-shadow:0 0 0 0 rgba(224,72,61,0)}}
.dk-cf-ic{position:relative;width:40px;height:40px;border-radius:50%;background:linear-gradient(155deg,#e0483d,#c23a30);display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;box-shadow:0 8px 18px -6px rgba(194,58,48,.6);opacity:0;transform:scale(.4) rotate(-25deg);transition:transform .5s cubic-bezier(.2,1.4,.4,1) .12s,opacity .3s ease .12s}
#dkConfirmBox.on .dk-cf-ic{opacity:1;transform:scale(1) rotate(0)}
.dk-cf-done{width:40px;height:40px;border-radius:50%;background:linear-gradient(155deg,#3fb87e,#2e9563);display:none;align-items:center;justify-content:center;color:#fff;font-size:16px;box-shadow:0 8px 18px -6px rgba(46,149,99,.6)}
.dk-cf-title{font-size:1.02rem;font-weight:700;color:#1b1730;margin-bottom:6px;letter-spacing:-.01em}
.dk-cf-sub{font-size:.8rem;line-height:1.5;color:#6b6584;margin-bottom:20px}
.dk-cf-btns{display:flex;gap:10px}
.dk-cf-btns button{flex:1;border:none;cursor:pointer;font-size:.82rem;font-weight:700;padding:11px 12px;border-radius:11px;transition:transform .15s ease,box-shadow .2s ease,background .2s ease}
.dk-cf-btns button:active{transform:scale(.96)}
.dk-cf-cancel{background:#f2f0fb;color:#372a80}
.dk-cf-cancel:hover{background:#e8e4f9}
.dk-cf-ok{background:linear-gradient(155deg,#e0483d,#c23a30);color:#fff;box-shadow:0 10px 20px -8px rgba(194,58,48,.55)}
.dk-cf-ok:hover{filter:brightness(1.05)}
.dk-cf-ok.loading{pointer-events:none}
.dk-cf-ok .spin{width:13px;height:13px;border-radius:50%;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;animation:dkSpin .7s linear infinite;display:none;margin-right:7px;vertical-align:-2px}
.dk-cf-ok.loading .spin{display:inline-block}
.dk-cf-ok.loading .txt{display:none}
@keyframes dkSpin{to{transform:rotate(360deg)}}
</style>

<div id="dkConfirmBox">
  <div class="dk-cf-card">
    <div class="dk-cf-icwrap">
      <div class="dk-cf-ring pulse"></div>
      <div class="dk-cf-ic" id="dkCfIc"><i class="bi bi-trash3-fill"></i></div>
      <div class="dk-cf-done" id="dkCfDone"><i class="bi bi-check-lg"></i></div>
    </div>
    <div class="dk-cf-title" id="dkCfTitle">Hapus berkas ini?</div>
    <div class="dk-cf-sub" id="dkCfSub">Tindakan ini tidak bisa dibatalkan.</div>
    <div class="dk-cf-btns" id="dkCfBtns">
      <button class="dk-cf-cancel" id="dkCfCancel">Batal</button>
      <button class="dk-cf-ok" id="dkCfOk"><span class="spin"></span><span class="txt">Hapus</span></button>
    </div>
  </div>
</div>

<?php if (!$team_id): ?>
  <div class="card"><div class="card-body fl-empty">Belum ada tim.</div></div>
<?php else: ?>
  <div class="dk-bar">
    <button class="dk-add" id="btnBukaUnggah"><i class="bi bi-cloud-arrow-up me-1"></i>Tambah Berkas</button>
    <span class="dk-jml"><?= count($items) ?> berkas tersimpan</span>
  </div>

  <?php if (empty($items)): ?>
    <div class="card"><div class="card-body fl-empty">
      <i class="bi bi-folder2-open" style="font-size:2.2rem;opacity:.35"></i>
      <p class="mt-3 mb-0">Belum ada berkas di tim ini.</p></div></div>
  <?php else: ?>
    <div class="fl-grid">
      <?php foreach ($items as $f):
        $e = strtolower($f['file_type']);
        $img = in_array($e, array('jpg','jpeg','png','gif','webp'));
        $vid = in_array($e, array('mp4','webm','mov','avi','mkv'));
        $ic = 'bi-file-earmark'; $wr = '#94a3b8';
        if ($e=='pdf') { $ic='bi-file-earmark-pdf'; $wr='#ef4444'; }
        elseif (in_array($e,array('doc','docx'))) { $ic='bi-file-earmark-word'; $wr='#2563eb'; }
        elseif (in_array($e,array('xls','xlsx','csv'))) { $ic='bi-file-earmark-excel'; $wr='#16a34a'; }
        elseif (in_array($e,array('ppt','pptx'))) { $ic='bi-file-earmark-ppt'; $wr='#ea580c'; }
        elseif (in_array($e,array('mp3','wav'))) { $ic='bi-file-earmark-music'; $wr='#a855f7'; }
        elseif ($e=='zip') { $ic='bi-file-earmark-zip'; $wr='#78716c'; }
        $kb = $f['file_size'] > 1048576 ? round($f['file_size']/1048576,1).' MB' : round($f['file_size']/1024).' KB';
        $judul = $f['judul'] ?: $f['name'];
        $ini = strtoupper(substr(trim($f['pengunggah'] ?: '?'), 0, 1));
      ?>
        <div class="fl-card">
          <div class="fl-aksi">
            <button class="ubah fl-ubah" data-json='<?= htmlspecialchars(json_encode($f), ENT_QUOTES) ?>' title="ubah judul"><i class="bi bi-pencil"></i></button>
            <button class="hapus fl-del" data-id="<?= $f['id'] ?>" title="hapus"><i class="bi bi-trash3"></i></button>
          </div>

          <a href="<?= base_url().$f['file_path'] ?>" class="fl-media" data-lb="1"
             data-id="<?= $f['id'] ?>"
             data-path="<?= $f['file_path'] ?>"
             data-nama="<?= htmlspecialchars($judul) ?>"
             data-jenis="<?= $e ?>"
             data-pengunggah="<?= htmlspecialchars($f['pengunggah'] ?: 'Pengguna') ?>"
             data-tanggal="<?= date('j M Y', strtotime($f['created_at'])) ?>">
            <?php if ($img): ?>
              <img src="<?= base_url().$f['file_path'] ?>" loading="lazy" alt="">
            <?php elseif ($vid): ?>
              <video src="<?= base_url().$f['file_path'] ?>" preload="metadata"></video>
              <i class="bi bi-play-circle-fill play"></i>
            <?php else: ?>
              <div class="box"><i class="bi <?= $ic ?>" style="color:<?= $wr ?>"></i></div>
            <?php endif; ?>
          </a>

          <div class="fl-body">
            <div class="fl-judul"><?= htmlspecialchars($judul) ?></div>
            <?php if (!empty($f['project_url'])): ?>
              <a href="<?= htmlspecialchars($f['project_url']) ?>" target="_blank" rel="noopener noreferrer"
                 onclick="event.stopPropagation()"
                 style="display:inline-flex;align-items:center;gap:5px;margin:4px 0 6px;padding:4px 10px;
                        background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:7px;
                        font-size:.74rem;font-weight:500;text-decoration:none;max-width:100%">
                <i class="bi bi-link-45deg"></i><span>Buka Project</span>
              </a>
            <?php endif; ?>
            <div class="fl-oleh">
              <span class="av"><?= $ini ?></span>
              <span><?= htmlspecialchars($f['pengunggah'] ?: 'Pengguna') ?></span>
            </div>
            <div class="fl-info">
              <span><i class="bi bi-calendar3"></i> <?= date('j M Y', strtotime($f['created_at'])) ?></span>
              <span><i class="bi bi-hdd"></i> <?= $kb ?></span>
              <span class="text-uppercase"><?= $e ?></span>
            </div>
            <?php if ($f['note']): ?><div class="fl-note"><?= nl2br(htmlspecialchars($f['note'])) ?></div><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<script>
(function(){
  var BASE='<?= base_url() ?>', TEAM=<?= intval($team_id) ?>;
  var inp = document.getElementById('flInput');
  if (inp) inp.addEventListener('change', function(){
    var f = this.files[0];
    var box = document.getElementById('flPreview');
    if (!f) { box.style.display='none'; return; }
    document.getElementById('flPrevNama').textContent = f.name;
    document.getElementById('flPrevSize').textContent =
      (f.size > 1048576 ? (f.size/1048576).toFixed(1)+' MB' : Math.round(f.size/1024)+' KB');
    var img = document.getElementById('flPrevImg');
    if (f.type.indexOf('image/') === 0) {
      var rd = new FileReader();
      rd.onload = function(e){ img.src = e.target.result; img.style.display='block'; };
      rd.readAsDataURL(f);
    } else { img.style.display='none'; }
    box.style.display='block';
  });

  var b = document.getElementById('btnUpload');
  if (b) b.addEventListener('click', function(){
    var f = document.getElementById('flInput').files[0];
    if (!f) { dkToast('Pilih berkas dulu.','info'); return; }
    var fd = new FormData();
    fd.append('team_id', TEAM);
    fd.append('berkas', f);
    fd.append('note', document.getElementById('flNote').value);
    this.disabled = true; this.textContent = 'Mengunggah...';
    fetch(BASE+'kinerja/upload_file',{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ if(d.status) location.reload(); else { dkToast(d.msg || 'Gagal mengunggah.','err'); b.disabled=false; b.textContent='Unggah'; } })
      .catch(function(e){ dkToast('Gagal mengunggah berkas.','err'); b.disabled=false; b.textContent='Unggah'; });
  });
  var dkBox = document.getElementById('dkConfirmBox');
  var dkOk = document.getElementById('dkCfOk');
  var dkCancel = document.getElementById('dkCfCancel');
  var dkIc = document.getElementById('dkCfIc');
  var dkDone = document.getElementById('dkCfDone');
  var dkTitle = document.getElementById('dkCfTitle');
  var dkSub = document.getElementById('dkCfSub');
  var dkBtns = document.getElementById('dkCfBtns');

  function dkConfirmHapus(cb, opts){
    opts = opts || {};
    dkTitle.textContent = opts.judul || 'Hapus berkas ini?';
    dkSub.textContent = opts.sub || 'Tindakan ini tidak bisa dibatalkan.';
    dkIc.style.display='flex'; dkDone.style.display='none'; dkBtns.style.display='flex'; dkOk.classList.remove('loading');
    dkBox.classList.add('on');
    function reset(){
      dkBox.classList.remove('on');
      setTimeout(function(){
        dkOk.classList.remove('loading');
        dkIc.style.display='flex'; dkDone.style.display='none';
        dkBtns.style.display='flex';
      }, 280);
      dkOk.removeEventListener('click', onOk);
      dkCancel.removeEventListener('click', onCancel);
    }
    function onCancel(){
      var card = dkBox.querySelector('.dk-cf-card');
      card.classList.add('dk-cf-shake');
      setTimeout(function(){
        card.classList.remove('dk-cf-shake');
        reset(); cb(false);
      }, 220);
    }
    function onOk(){
      dkOk.classList.add('loading');
      cb(true, function selesai(sukses, pesan){
        if (!sukses){
          dkOk.classList.remove('loading');
          dkToast(pesan || 'Gagal menghapus.', 'err');
          reset();
          return;
        }
        dkIc.style.display='none'; dkDone.style.display='flex';
        dkTitle.textContent = opts.sukJudul || 'Berkas dihapus';
        dkSub.textContent = opts.sukSub || 'Berkas berhasil dihapus.';
        dkBtns.style.display='none';
        setTimeout(reset, 950);
      });
    }
    dkOk.addEventListener('click', onOk);
    dkCancel.addEventListener('click', onCancel);
  }

  document.querySelectorAll('.fl-del').forEach(function(x){
    x.addEventListener('click', function(){
      var id = this.dataset.id;
      dkConfirmHapus(function(ok, selesai){
        if (!ok) return;
        var fd=new FormData(); fd.append('id', id);
        fetch(BASE+'kinerja/delete_file',{method:'POST',body:fd,credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(d){
            selesai(!!d.status, d.msg);
            if (d.status) setTimeout(function(){ location.reload(); }, 950);
          })
          .catch(function(){ selesai(false, 'Gagal menghubungi server.'); });
      }, {judul:'Hapus berkas ini?', sub:'Berkas ini akan dihapus secara permanen dari daftar dokumen tim.', sukJudul:'Berkas dihapus', sukSub:'Berkas berhasil dihapus.'});
    });
  });
})();
</script>

<div id="lbBox">
  <button id="lbTutup" title="tutup">&times;</button>
  <a id="lbUnduh" href="#" download><i class="bi bi-download"></i> Unduh</a>
  <div id="lbIsi"></div>
  <div id="lbNama"></div>
</div>

<script>
(function(){
  var BASE2 = '<?= base_url() ?>';
  var box   = document.getElementById('lbBox');
  var isi   = document.getElementById('lbIsi');
  var nama  = document.getElementById('lbNama');
  var unduh = document.getElementById('lbUnduh');

  function buka(path, judul, jenis){
    var url = BASE2 + path;
    var e = (jenis||'').toLowerCase();
    if (['jpg','jpeg','png','gif','webp'].indexOf(e) >= 0) {
      isi.innerHTML = '<img src="'+url+'" alt="">';
    } else if (['mp4','webm','mov'].indexOf(e) >= 0) {
      isi.innerHTML = '<video src="'+url+'" controls autoplay playsinline></video>';
    } else if (e === 'pdf') {
      isi.innerHTML = '<iframe src="'+url+'"></iframe>';
    } else {
      window.open(url, '_blank');
      return;
    }
    nama.textContent = judul || '';
    unduh.href = url;
    box.classList.add('on');
    document.body.style.overflow = 'hidden';
  }

  function tutup(){
    box.classList.remove('on');
    isi.innerHTML = '';
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.fl-card a[data-lb]').forEach(function(a){
    a.addEventListener('click', function(e){
      e.preventDefault();
      bukaGabung(this.dataset.id, this.dataset.path, this.dataset.jenis, this.dataset.nama, this.dataset.pengunggah, this.dataset.tanggal);
    });
  });

  document.getElementById('lbTutup').addEventListener('click', tutup);
  box.addEventListener('click', function(e){ if (e.target === box) tutup(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') tutup(); });
})();
</script>

<div class="modal fade" id="mdKomen" tabindex="-1">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header py-2">
        <button type="button" id="mkBack" data-bs-dismiss="modal" title="Kembali"
                style="display:none;border:0;background:none;color:#6E4FA8;font-size:1.25rem;
                       padding:4px 10px 4px 0;line-height:1;cursor:pointer;flex:0 0 auto">
          <i class="bi bi-arrow-left"></i>
        </button>
        <div class="flex-grow-1 min-width-0">
          <h6 class="modal-title mb-0 text-truncate" id="mkJudul">Dokumen</h6>
          <div id="mkInfo" class="text-muted small"></div>
        </div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0 mk-feed" style="overflow-y:auto;background:#eef0f4">
        <div id="mkMedia" style="background:transparent;display:flex;align-items:center;justify-content:center;overflow:hidden;width:100%;padding:24px 16px"></div>
        <div class="mx-auto w-100 p-3" style="max-width:640px">
          <div id="mkReactBar" class="d-flex align-items-center gap-2 mb-3"></div>
          <div class="bg-white rounded-3 shadow-sm p-3" style="border:1px solid #e2e6ee">
            <div class="mk-sec-label" style="font-size:.86rem;font-weight:600;color:#334155;margin-bottom:12px">Komentar &amp; Aktifitas</div>
            <div id="mkList" class="mb-0">
              <div class="text-muted small">Belum ada komentar disini...</div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer p-2 justify-content-center" style="background:#eef0f4;border-top:1px solid #e2e6ee">
        <div class="d-flex align-items-center gap-2 mx-auto" style="max-width:640px;width:100%">
          <div class="mk-av" style="background:#94a3b8"><i class="bi bi-person-fill"></i></div>
          <div class="input-group input-group-sm flex-grow-1">
            <input type="text" class="form-control" id="mkInput" placeholder="tulis kritik / saran...">
            <button class="btn btn-primary" id="mkKirim">Kirim</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="mkConfirmBox">
  <div class="mk-cf-card">
    <div class="mk-cf-ic"><i class="bi bi-trash3"></i></div>
    <div class="mk-cf-title">Hapus komentar ini?</div>
    <div class="mk-cf-sub">Tindakan ini tidak bisa dibatalkan.</div>
    <div class="mk-cf-btns">
      <button class="mk-cf-cancel" id="mkCfCancel">Batal</button>
      <button class="mk-cf-ok" id="mkCfOk">Hapus</button>
    </div>
  </div>
</div>

<script>
(function(){
  var BASE3 = '<?= base_url() ?>';

  var cfBox = document.getElementById('mkConfirmBox');
  var cfOk = document.getElementById('mkCfOk');
  var cfCancel = document.getElementById('mkCfCancel');
  function mkConfirmHapus(cb){
    cfBox.classList.add('on');
    function bersih(){
      cfBox.classList.remove('on');
      cfOk.removeEventListener('click', onOk);
      cfCancel.removeEventListener('click', onCancel);
    }
    function onOk(){ bersih(); cb(true); }
    function onCancel(){ bersih(); cb(false); }
    cfOk.addEventListener('click', onOk);
    cfCancel.addEventListener('click', onCancel);
  }
  var modalEl = document.getElementById('mdKomen');
  var modal = new bootstrap.Modal(modalEl);
  var fidAktif = 0, uidSaya = 0, bolehHapusSemua = false;

  modalEl.addEventListener('hidden.bs.modal', function(){
    document.getElementById('mkMedia').innerHTML = '';
  });

  function renderMedia(path, jenis){
    var url = BASE3 + path;
    var e = (jenis||'').toLowerCase();
    var el = document.getElementById('mkMedia');
    if (['jpg','jpeg','png','gif','webp'].indexOf(e) >= 0) {
      el.innerHTML = '<img src="'+url+'" alt="">';
    } else if (['mp4','webm','mov'].indexOf(e) >= 0) {
      el.innerHTML = '<video src="'+url+'" controls playsinline></video>';
    } else if (e === 'pdf') {
      el.innerHTML = '<iframe src="'+url+'"></iframe>';
    } else {
      el.innerHTML = '<a href="'+url+'" target="_blank" class="d-block text-center text-white py-5"><i class="bi bi-file-earmark" style="font-size:3rem"></i><div class="small mt-2">Buka berkas</div></a>';
    }
  }

  function jadikanTautan(teks){
    var div = document.createElement('div');
    div.textContent = teks;
    var aman = div.innerHTML;
    return aman.replace(/(https?:\/\/[^\s<]+)/g, function(u){
      var bersih = u.replace(/[.,;:!?)\]]+$/, '');
      var ekor = u.slice(bersih.length);
      var tampil = bersih.length > 45 ? bersih.slice(0, 45) + '\u2026' : bersih;
      return '<a href="' + bersih + '" target="_blank" rel="noopener noreferrer" '
           + 'style="color:#2563eb;text-decoration:underline;word-break:break-all">'
           + tampil + '</a>' + ekor;
    }).replace(/\n/g, '<br>');
  }

  function render(rows){
    var el = document.getElementById('mkList');
    if (!rows.length) { el.innerHTML = '<div class="text-muted small">Belum ada komentar.</div>'; return; }
    var h = '';
    var palet = ['#6366f1','#f59e0b','#10b981','#ef4444','#0ea5e9','#a855f7','#ec4899'];
    function warna(nm){ var n=(nm||'?').charCodeAt(0)||0; return palet[n % palet.length]; }
    rows.forEach(function(c){
      var bisaHapus = bolehHapusSemua || (parseInt(c.user_id) === uidSaya);
      var inisial = (c.nama||'?').charAt(0).toUpperCase();
      var waktu = c.edited_at ? c.edited_at : c.created_at;
      var tagEdit = c.edited == 1 ? ' &middot; <i>diedit</i>' : '';
      h += '<div class="mk-it mb-3" data-crow="'+c.id+'">'
         + '<div class="mk-av" style="background:'+warna(c.nama)+'">'+inisial+'</div>'
         + '<div class="flex-grow-1">'
         + '<span class="mk-nm">'+(c.nama||'Pengguna')+'</span><span class="mk-tg">'+waktu+tagEdit+'</span>'
         + '<div class="mk-tx"></div>'
         + (bisaHapus ? '<button class="mk-cact mk-cedit" data-id="'+c.id+'">edit</button><button class="mk-cact mk-cdel" data-id="'+c.id+'">hapus</button>' : '')
         + '</div></div>';
    });
    el.innerHTML = h;
    var txEls = el.querySelectorAll('.mk-tx');
    rows.forEach(function(c, i){
      txEls[i].innerHTML = jadikanTautan(c.comment || '');
      txEls[i].dataset.teks = c.comment;
    });

    el.querySelectorAll('.mk-cdel').forEach(function(b){
      b.addEventListener('click', function(){
        var id = this.dataset.id;
        mkConfirmHapus(function(ok){
          if (!ok) return;
          var fd = new FormData(); fd.append('id', id);
          fetch(BASE3+'kinerja/delete_doc_comment', {method:'POST', body:fd, credentials:'same-origin'})
            .then(function(r){return r.json();})
            .then(function(d){ if (d.status) muat(fidAktif); else dkToast(d.msg, 'err'); });
        });
      });
    });

    el.querySelectorAll('.mk-cedit').forEach(function(b){
      b.addEventListener('click', function(){
        var id = this.dataset.id;
        var row = document.querySelector('.mk-it[data-crow="'+id+'"]');
        var txtEl = row.querySelector('.mk-tx');
        var asli = txtEl.dataset.teks;
        row.querySelectorAll('.mk-cact').forEach(function(x){ x.style.display='none'; });
        var wrap = document.createElement('div');
        wrap.className = 'mt-1';
        wrap.innerHTML = '<div class="input-group input-group-sm">'
          + '<input type="text" class="form-control mk-eedit" value="'+asli.replace(/"/g,'&quot;')+'">'
          + '<button class="btn btn-primary mk-esave">Simpan</button>'
          + '<button class="btn btn-outline-secondary mk-ecancel">Batal</button></div>';
        txtEl.style.display = 'none';
        txtEl.insertAdjacentElement('afterend', wrap);

        wrap.querySelector('.mk-ecancel').addEventListener('click', function(){
          wrap.remove(); txtEl.style.display='';
          row.querySelectorAll('.mk-cact').forEach(function(x){ x.style.display=''; });
        });
        wrap.querySelector('.mk-esave').addEventListener('click', function(){
          var baru = wrap.querySelector('.mk-eedit').value.trim();
          if (!baru) return;
          var fd = new FormData(); fd.append('id', id); fd.append('comment', baru);
          fetch(BASE3+'kinerja/edit_doc_comment', {method:'POST', body:fd, credentials:'same-origin'})
            .then(function(r){return r.json();})
            .then(function(d){ if (d.status) muat(fidAktif); else dkToast(d.msg, 'err'); });
        });
      });
    });
  }

  function muat(fid){
    fetch(BASE3+'kinerja/list_doc_comments?file_id='+fid, {credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        if (!d.status) { dkToast(d.msg, 'err'); return; }
        uidSaya = parseInt(d.uid); bolehHapusSemua = !!d.boleh;
        render(d.data || []);
      });
  }

  var EMOJI = ['👍','❤️','😂','🔥','🎉'];

  function renderReactBar(rows, mine){
    var el = document.getElementById('mkReactBar');
    var peta = {}; (rows||[]).forEach(function(r){ peta[r.emoji] = r.jml; });
    var h = '';
    EMOJI.forEach(function(e){
      var jml = peta[e] || 0;
      h += '<button class="mk-emo-btn'+(mine===e?' aktif':'')+'" data-e="'+e+'">'+e+(jml?(' <small>'+jml+'</small>'):'')+'</button>';
    });
    el.innerHTML = h;
    el.querySelectorAll('.mk-emo-btn').forEach(function(b){
      b.addEventListener('click', function(){
        var fd = new FormData(); fd.append('file_id', fidAktif); fd.append('emoji', this.dataset.e);
        fetch(BASE3+'kinerja/toggle_doc_reaction', {method:'POST', body:fd, credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(d){ if (d.status) muatReaksi(fidAktif); });
      });
    });
  }

  function muatReaksi(fid){
    fetch(BASE3+'kinerja/list_doc_reactions?file_id='+fid, {credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ if (d.status) renderReactBar(d.data||[], d.mine||null); });
  }

  window.bukaGabung = function(fid, path, jenis, judul, pengunggah, tanggal){
    fidAktif = fid;
    document.getElementById('mkJudul').textContent = judul || 'Dokumen';
    document.getElementById('mkInfo').textContent = 'Diunggah oleh ' + (pengunggah||'-') + (tanggal ? ' · '+tanggal : '');
    document.getElementById('mkList').innerHTML = '<div class="text-muted small">memuat...</div>';
    document.getElementById('mkReactBar').innerHTML = '';
    document.getElementById('mkInput').value = '';
    renderMedia(path, jenis);
    modal.show();
    muat(fidAktif);
    muatReaksi(fidAktif);
  };

  document.querySelectorAll('.fl-komen').forEach(function(b){
    b.addEventListener('click', function(){
      bukaGabung(this.dataset.id, this.dataset.path, this.dataset.jenis, this.dataset.nama, this.dataset.pengunggah, this.dataset.tanggal);
    });
  });

  document.getElementById('mkKirim').addEventListener('click', function(){
    var txt = document.getElementById('mkInput').value.trim();
    if (!txt) return;
    var btn = this; btn.disabled = true;
    var fd = new FormData(); fd.append('file_id', fidAktif); fd.append('comment', txt);
    fetch(BASE3+'kinerja/add_doc_comment', {method:'POST', body:fd, credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        btn.disabled = false;
        if (d.status) { document.getElementById('mkInput').value = ''; muat(fidAktif); }
        else dkToast(d.msg, 'err');
      });
  });

  document.getElementById('mkInput').addEventListener('keydown', function(e){
    if (e.key === 'Enter') document.getElementById('mkKirim').click();
  });
})();
</script>

<div class="modal fade" id="mdUnggah" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-cloud-arrow-up me-2"></i>Tambah Berkas</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="dk-drop mb-3" id="dkDrop">
      <i class="bi bi-cloud-arrow-up"></i>
      <div class="t">Klik atau seret berkas ke sini</div>
      <div class="s">Gambar, video, PDF, dokumen, audio &middot; maksimal 100 MB</div>
    </div>
    <div class="dk-prev mb-3" id="dkPrev" style="display:none">
      <div id="dkPrevThumb"></div>
      <div><div class="nm" id="dkPrevNm"></div><div class="sz" id="dkPrevSz"></div></div>
      <button class="x" id="dkPrevX"><i class="bi bi-x-circle-fill"></i></button>
    </div>
    <input type="file" id="flInput" style="display:none">
    <div class="mb-3"><label class="form-label">Judul berkas *</label>
      <input type="text" class="form-control" id="dkJudul" placeholder="mis. Laporan Penjualan Agustus"></div>
    <div class="mb-3"><label class="form-label">Link Project <span class="text-muted" style="font-weight:400">(opsional)</span></label>
      <input type="url" class="form-control" id="dkProjectUrl" placeholder="https://drive.google.com/..."></div>
    <div class="mb-2"><label class="form-label">Keterangan</label>
      <textarea class="form-control" id="flNote" rows="2" placeholder="isi singkat berkas ini..."></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button class="btn btn-primary" id="btnUpload"><i class="bi bi-upload me-1"></i>Unggah</button></div>
</div></div></div>

<div class="modal fade" id="mdUbahFile" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Ubah Judul Berkas</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" id="ubId">
    <div class="mb-3"><label class="form-label">Judul *</label><input type="text" class="form-control" id="ubJudul"></div>
    <div class="mb-3"><label class="form-label">Link Project</label><input type="url" class="form-control" id="ubProjectUrl" placeholder="https://drive.google.com/..."></div>
    <div class="mb-2"><label class="form-label">Keterangan</label><textarea class="form-control" id="ubNote" rows="2"></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button class="btn btn-primary" id="btnSimpanUbah">Simpan</button></div>
</div></div></div>

<script>
(function(){
  var BASE = '<?= base_url() ?>', TEAM = <?= intval($team_id) ?>;

  function dkToast(pesan, jenis){
    if (!document.getElementById('dkToastGaya')) {
      var st = document.createElement('style');
      st.id = 'dkToastGaya';
      st.textContent =
        '#dkToastWrap{position:fixed;top:22px;left:50%;transform:translateX(-50%);z-index:40000;display:flex;flex-direction:column;gap:9px;align-items:center;pointer-events:none}'
      + '.dkToast{display:flex;align-items:center;gap:11px;background:#fff;border-radius:12px;padding:13px 20px;box-shadow:0 12px 34px rgba(0,0,0,.18);font-size:.86rem;color:#1e293b;opacity:0;transform:translateY(-14px);transition:opacity .25s,transform .28s cubic-bezier(.2,.9,.3,1.3);max-width:90vw}'
      + '.dkToast.on{opacity:1;transform:none}'
      + '.dkToast i{font-size:1.15rem}'
      + '.dkToast.ok{border-left:4px solid #22c55e}.dkToast.ok i{color:#22c55e}'
      + '.dkToast.err{border-left:4px solid #ef4444}.dkToast.err i{color:#ef4444}'
      + '.dkToast.info{border-left:4px solid #3b82f6}.dkToast.info i{color:#3b82f6}';
      document.head.appendChild(st);
    }
    var wrap = document.getElementById('dkToastWrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.id = 'dkToastWrap';
      document.body.appendChild(wrap);
    }
    var j = jenis || 'info';
    var ik = j === 'ok' ? 'bi-check-circle-fill' : (j === 'err' ? 'bi-exclamation-circle-fill' : 'bi-info-circle-fill');
    var t = document.createElement('div');
    t.className = 'dkToast ' + j;
    t.innerHTML = '<i class="bi ' + ik + '"></i><span></span>';
    t.querySelector('span').textContent = pesan;
    wrap.appendChild(t);
    requestAnimationFrame(function(){ t.classList.add('on'); });
    setTimeout(function(){
      t.classList.remove('on');
      setTimeout(function(){ if (t.parentNode) t.parentNode.removeChild(t); }, 300);
    }, 3200);
  }

  var mdU = document.getElementById('mdUnggah') ? new bootstrap.Modal(document.getElementById('mdUnggah')) : null;
  var mdE = document.getElementById('mdUbahFile') ? new bootstrap.Modal(document.getElementById('mdUbahFile')) : null;
  if (!mdU) return;

  var inp = document.getElementById('flInput');
  var drop = document.getElementById('dkDrop');
  var prev = document.getElementById('dkPrev');
  function fmt(b){ return b > 1048576 ? (b/1048576).toFixed(1)+' MB' : Math.round(b/1024)+' KB'; }

  function tampilPrev(f){
    if (!f) { prev.style.display='none'; drop.style.display='block'; return; }
    document.getElementById('dkPrevNm').textContent = f.name;
    document.getElementById('dkPrevSz').textContent = fmt(f.size);
    var th = document.getElementById('dkPrevThumb');
    if (f.type.indexOf('image/') === 0) {
      var rd = new FileReader();
      rd.onload = function(e){ th.innerHTML = '<img src="'+e.target.result+'">'; };
      rd.readAsDataURL(f);
    } else {
      var ik = f.type.indexOf('video/') === 0 ? 'bi-camera-video' : 'bi-file-earmark-text';
      th.innerHTML = '<div class="ic"><i class="bi '+ik+'"></i></div>';
    }
    var ju = document.getElementById('dkJudul');
    if (!ju.value.trim()) ju.value = f.name.replace(/\.[^.]+$/, '');
    prev.style.display='flex'; drop.style.display='none';
  }

  document.getElementById('btnBukaUnggah').addEventListener('click', function(){
    inp.value=''; document.getElementById('dkJudul').value='';
    document.getElementById('flNote').value=''; tampilPrev(null); mdU.show();
  });
  drop.addEventListener('click', function(){ inp.click(); });
  inp.addEventListener('change', function(){ tampilPrev(this.files[0]); });
  document.getElementById('dkPrevX').addEventListener('click', function(){ inp.value=''; tampilPrev(null); });

  ['dragenter','dragover'].forEach(function(ev){
    drop.addEventListener(ev, function(e){ e.preventDefault(); this.classList.add('aktif'); });
  });
  ['dragleave','drop'].forEach(function(ev){
    drop.addEventListener(ev, function(e){ e.preventDefault(); this.classList.remove('aktif'); });
  });
  drop.addEventListener('drop', function(e){
    if (e.dataTransfer.files.length) { inp.files = e.dataTransfer.files; tampilPrev(inp.files[0]); }
  });

  document.getElementById('btnUpload').addEventListener('click', function(){
    var f = inp.files[0];
    if (!f) { dkToast('Pilih berkas dulu.','info'); return; }
    var ju = document.getElementById('dkJudul').value.trim();
    if (!ju) { dkToast('Judul wajib diisi.','info'); return; }
    var fd = new FormData();
    fd.append('team_id', TEAM); fd.append('berkas', f);
    fd.append('judul', ju); fd.append('note', document.getElementById('flNote').value);
    fd.append('project_url', (document.getElementById('dkProjectUrl')||{value:''}).value.trim());
    var b = this; b.disabled = true; b.innerHTML = 'Mengunggah...';
    fetch(BASE+'kinerja/upload_file',{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ if(d.status) location.reload();
        else { dkToast(d.msg || 'Gagal mengunggah.','err'); b.disabled=false; b.innerHTML='<i class="bi bi-upload me-1"></i>Unggah'; } })
      .catch(function(e){ dkToast('Gagal mengunggah berkas.','err'); b.disabled=false; b.innerHTML='<i class="bi bi-upload me-1"></i>Unggah'; });
  });

  document.querySelectorAll('.fl-ubah').forEach(function(x){
    x.addEventListener('click', function(e){
      e.preventDefault(); e.stopPropagation();
      var d = JSON.parse(this.dataset.json||'{}');
      document.getElementById('ubId').value = d.id;
      document.getElementById('ubJudul').value = d.judul || d.name || '';
      var pu = document.getElementById('ubProjectUrl'); if (pu) pu.value = d.project_url || '';
      document.getElementById('ubNote').value = d.note || '';
      mdE.show();
    });
  });

  document.getElementById('btnSimpanUbah').addEventListener('click', function(){
    var ju = document.getElementById('ubJudul').value.trim();
    if (!ju) { dkToast('Judul tidak boleh kosong.','info'); return; }
    var fd = new FormData();
    fd.append('id', document.getElementById('ubId').value);
    fd.append('judul', ju);
    fd.append('project_url', (document.getElementById('ubProjectUrl')||{value:''}).value.trim());
    fd.append('note', document.getElementById('ubNote').value);
    fetch(BASE+'kinerja/edit_file_judul',{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();}).then(function(d){ if(d.status) location.reload(); else dkToast(d.msg || 'Gagal menyimpan.','err'); });
  });
})();
</script>

<style>@media(max-width:767.98px){#mkBack{display:inline-flex!important;align-items:center}}</style>
<script>
(function(){
  // buka berkas otomatis kalau datang dari notifikasi (?file=ID)
  var fid = new URLSearchParams(location.search).get('file');
  if (!fid) return;
  function coba(){
    var el = document.querySelector('.fl-media[data-id="' + fid + '"]');
    if (!el) { setTimeout(coba, 400); return; }
    el.click();
    setTimeout(function(){
      var inp = document.getElementById('mkInput') || document.querySelector('#mdKomen input[type=text], #mdKomen textarea');
      if (inp) inp.focus();
    }, 700);
  }
  if (document.readyState === 'complete') setTimeout(coba, 400);
  else window.addEventListener('load', function(){ setTimeout(coba, 400); });
})();
</script>
