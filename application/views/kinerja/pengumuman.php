<?php $halaman='pengumuman'; $this->load->view('kinerja/_tabs', array('teams'=>$teams,'team_id'=>$team_id,'halaman'=>$halaman)); ?>
<style>
.pg-card{background:#fff;border:1px solid #e5e8f0;border-radius:12px;padding:18px 20px;margin-bottom:12px}
.pg-card.pin{border-left:4px solid #f0b429;background:#fffdf6}
.pg-card h6{margin:0 0 6px;font-size:1rem;color:#1e293b}
.pg-meta{font-size:.74rem;color:#94a3b8;margin-bottom:10px}
.pg-body{font-size:.88rem;color:#475569;line-height:1.7;white-space:pre-wrap}
.pg-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(128px,1fr));gap:9px;margin-top:12px}
.pg-it{position:relative;border:1px solid #e5e8f0;border-radius:9px;overflow:hidden;background:#f8fafc}
.pg-it img,.pg-it video{width:100%;height:110px;object-fit:cover;display:block;background:#0f172a}
.pg-it .bx{height:110px;display:flex;align-items:center;justify-content:center}
.pg-it .bx i{font-size:1.9rem;color:#94a3b8}
.pg-it .nm{font-size:.62rem;padding:5px 6px;color:#475569;background:#fff;word-break:break-word;line-height:1.3}
.pg-it .xx{position:absolute;top:4px;right:4px;background:rgba(255,255,255,.94);border:0;border-radius:50%;width:20px;height:20px;color:#ef4444;cursor:pointer;font-size:.85rem;line-height:1;padding:0;opacity:0;transition:.2s}
.pg-it:hover .xx{opacity:1}
.pg-empty{text-align:center;padding:60px 20px;color:#94a3b8}
.pg-av{width:38px;height:38px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.86rem;font-weight:700;flex:0 0 auto}
.pg-av.sm{width:28px;height:28px;font-size:.72rem}
.pg-emo-btn{border:1px solid #e2e6ee;background:#fff;border-radius:999px;padding:4px 10px;font-size:.92rem;cursor:pointer;line-height:1.2}
.pg-emo-btn.aktif{background:#eef2ff;border-color:#6366f1}
.pg-sec-label{font-size:.8rem;font-weight:600;color:#334155}
.pg-cnm{font-size:.78rem;font-weight:600;color:#1e293b}
.pg-ctg{font-size:.66rem;color:#94a3b8;margin-left:6px}
.pg-ctx{font-size:.82rem;color:#334155;margin-top:2px;white-space:pre-wrap;word-break:break-word}
.pg-cact{font-size:.7rem;cursor:pointer;border:0;background:none;padding:0;margin-top:2px;margin-right:10px;transition:opacity .15s ease}
.pg-cact:hover{opacity:.7}
.pg-cdel{color:#e0483d}
.pg-cedit{color:#64748b}
.pg-ctg .tag-edited{color:#4c3aa6;font-style:italic;font-weight:600}
.pg-ctg.flash{animation:pgFlash 1s ease}
@keyframes pgFlash{0%{background:rgba(76,58,166,.10)}100%{background:transparent}}

.pg-editbox{margin-top:6px;display:flex;gap:8px;align-items:flex-start}
.pg-eedit{flex:1;font-size:.85rem;border:1.5px solid #f2f0fb;border-radius:10px;padding:8px 10px;outline:none;resize:none;font-family:inherit;transition:border-color .15s ease,box-shadow .15s ease}
.pg-eedit:focus{border-color:#4c3aa6;box-shadow:0 0 0 3px rgba(76,58,166,.12)}
.pg-ebtns{display:flex;flex-direction:column;gap:6px}
.pg-esave,.pg-ecancel{border:none;cursor:pointer;font-size:.7rem;font-weight:700;padding:7px 12px;border-radius:8px;white-space:nowrap;transition:transform .12s ease,background .15s ease}
.pg-esave{background:#4c3aa6;color:#fff}
.pg-esave:hover{background:#372a80}
.pg-ecancel{background:#f2f0fb;color:#372a80}
.pg-ecancel:hover{background:#e8e4f9}
.pg-esave:active,.pg-ecancel:active{transform:scale(.95)}

#pgLbBox{position:fixed;inset:0;background:rgba(15,12,28,.92);z-index:20000;display:flex;align-items:center;justify-content:center;padding:40px 20px;cursor:zoom-out;opacity:0;pointer-events:none;transition:opacity .25s ease}
#pgLbBox.on{opacity:1;pointer-events:auto}
#pgLbBox img{max-width:92vw;max-height:88vh;object-fit:contain;border-radius:12px;box-shadow:0 30px 80px rgba(0,0,0,.6);transform:scale(.85) translateY(10px);opacity:0;transition:transform .32s cubic-bezier(.2,1.1,.3,1),opacity .25s ease}
#pgLbBox.on img{transform:scale(1) translateY(0);opacity:1}
.pg-lb-close{position:absolute;top:22px;right:26px;width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.1);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px;opacity:0;transform:scale(.6);transition:opacity .25s ease .1s,transform .25s ease .1s,background .15s ease}
#pgLbBox.on .pg-lb-close{opacity:1;transform:scale(1)}
.pg-lb-close:hover{background:rgba(255,255,255,.2)}

#pgConfirmBox{position:fixed;inset:0;z-index:30000;background:rgba(24,19,45,.5);backdrop-filter:blur(5px) saturate(120%);-webkit-backdrop-filter:blur(5px) saturate(120%);display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s ease}
#pgConfirmBox.on{opacity:1;pointer-events:auto}
.pg-cf-card{width:340px;max-width:calc(100vw - 40px);background:#fff;border-radius:20px;padding:30px 26px 22px;text-align:center;box-shadow:0 30px 70px -20px rgba(20,15,60,.45),0 0 0 1px rgba(255,255,255,.6) inset;transform:translateY(16px) scale(.93);opacity:0;transition:transform .36s cubic-bezier(.2,1.1,.3,1),opacity .25s ease}
#pgConfirmBox.on .pg-cf-card{transform:translateY(0) scale(1);opacity:1}
.pg-cf-card.pg-cf-shake{animation:pgShake .4s ease}
@keyframes pgShake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}
.pg-cf-icwrap{position:relative;width:64px;height:64px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center}
.pg-cf-ring{position:absolute;inset:0;border-radius:50%;background:#fdeeed}
.pg-cf-ring.pulse{animation:pgPulse 1.8s ease-out infinite}
@keyframes pgPulse{0%{box-shadow:0 0 0 0 rgba(224,72,61,.28)}70%{box-shadow:0 0 0 13px rgba(224,72,61,0)}100%{box-shadow:0 0 0 0 rgba(224,72,61,0)}}
.pg-cf-ic{position:relative;width:40px;height:40px;border-radius:50%;background:linear-gradient(155deg,#e0483d,#c23a30);display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;box-shadow:0 8px 18px -6px rgba(194,58,48,.6);opacity:0;transform:scale(.4) rotate(-25deg);transition:transform .5s cubic-bezier(.2,1.4,.4,1) .12s,opacity .3s ease .12s}
#pgConfirmBox.on .pg-cf-ic{opacity:1;transform:scale(1) rotate(0)}
.pg-cf-done{width:40px;height:40px;border-radius:50%;background:linear-gradient(155deg,#3fb87e,#2e9563);display:none;align-items:center;justify-content:center;color:#fff;font-size:16px;box-shadow:0 8px 18px -6px rgba(46,149,99,.6)}
.pg-cf-title{font-size:1.02rem;font-weight:700;color:#1b1730;margin-bottom:6px;letter-spacing:-.01em}
.pg-cf-sub{font-size:.8rem;line-height:1.5;color:#6b6584;margin-bottom:20px}
.pg-cf-btns{display:flex;gap:10px}
.pg-cf-btns button{flex:1;border:none;cursor:pointer;font-size:.82rem;font-weight:700;padding:11px 12px;border-radius:11px;transition:transform .15s ease,box-shadow .2s ease,background .2s ease}
.pg-cf-btns button:active{transform:scale(.96)}
.pg-cf-cancel{background:#f2f0fb;color:#372a80}
.pg-cf-cancel:hover{background:#e8e4f9}
.pg-cf-ok{background:linear-gradient(155deg,#e0483d,#c23a30);color:#fff;box-shadow:0 10px 20px -8px rgba(194,58,48,.55)}
.pg-cf-ok:hover{filter:brightness(1.05)}
.pg-cf-ok.loading{pointer-events:none}
.pg-cf-ok .spin{width:13px;height:13px;border-radius:50%;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;animation:pgSpin .7s linear infinite;display:none;margin-right:7px;vertical-align:-2px}
.pg-cf-ok.loading .spin{display:inline-block}
.pg-cf-ok.loading .txt{display:none}
@keyframes pgSpin{to{transform:rotate(360deg)}}
</style>

<?php if (!$team_id): ?>
  <div class="card"><div class="card-body pg-empty">Belum ada tim.</div></div>
<?php else: ?>
  <div class="mb-3"><button class="btn btn-primary btn-sm" id="btnBaruPg"><i class="bi bi-plus-lg me-1"></i>Buat Pengumuman</button></div>
  <?php if (empty($items)): ?>
    <div class="card"><div class="card-body pg-empty">
      <i class="bi bi-megaphone" style="font-size:2.2rem;opacity:.35"></i>
      <p class="mt-3 mb-0">Belum ada pengumuman di tim ini.</p></div></div>
  <?php else: foreach ($items as $it): ?>
    <div class="pg-card <?= $it['is_pinned'] ? 'pin':'' ?>">
      <?php $pal=['#6366f1','#f59e0b','#10b981','#ef4444','#0ea5e9','#a855f7','#ec4899']; $cw=$pal[ord(substr($it['penulis']?:'?',0,1)) % count($pal)]; ?>
      <div class="d-flex justify-content-between align-items-start">
        <div class="d-flex gap-2">
          <div class="pg-av" style="background:<?= $cw ?>"><?= strtoupper(substr($it['penulis'] ?: '?', 0, 1)) ?></div>
          <div>
            <h6 class="mb-1"><?php if($it['is_pinned']): ?><i class="bi bi-pin-angle-fill text-warning me-1"></i><?php endif; ?><?= htmlspecialchars($it['title']) ?></h6>
            <div class="pg-meta mb-0"><?= htmlspecialchars($it['penulis'] ?: '-') ?> &middot; <?= date('j M Y, H:i', strtotime($it['created_at'])) ?></div>
          </div>
        </div>
        <div class="d-flex gap-1">
          <button class="btn btn-sm btn-link p-0 px-1 text-secondary btn-ubah-pg" data-json='<?= htmlspecialchars(json_encode($it), ENT_QUOTES) ?>'><i class="bi bi-pencil"></i></button>
          <button class="btn btn-sm btn-link p-0 px-1 text-danger btn-hapus-pg" data-id="<?= $it['id'] ?>"><i class="bi bi-trash"></i></button>
        </div>
      </div>
      <div class="pg-body"><?= nl2br(htmlspecialchars($it['body'])) ?></div>

      <?php if (!empty($it['files'])): ?>
        <div class="pg-grid">
          <?php foreach ($it['files'] as $ff):
            $e = strtolower($ff['file_type']);
            $img = in_array($e, array('jpg','jpeg','png','gif','webp'));
            $vid = in_array($e, array('mp4','webm'));
            $ic  = $e=='pdf' ? 'bi-file-earmark-pdf' : (in_array($e,array('doc','docx')) ? 'bi-file-earmark-word' : (in_array($e,array('xls','xlsx')) ? 'bi-file-earmark-excel' : 'bi-file-earmark')); ?>
            <div class="pg-it">
              <?php if ($img): ?>
                <a href="<?= base_url().$ff['file_path'] ?>" class="pg-lb" data-src="<?= base_url().$ff['file_path'] ?>"><img src="<?= base_url().$ff['file_path'] ?>" loading="lazy" alt=""></a>
              <?php elseif ($vid): ?>
                <video src="<?= base_url().$ff['file_path'] ?>" controls preload="metadata" playsinline></video>
              <?php else: ?>
                <a href="<?= base_url().$ff['file_path'] ?>" target="_blank" class="bx d-block"><i class="bi <?= $ic ?>"></i></a>
              <?php endif; ?>
              <button class="xx pg-fdel" data-id="<?= $ff['id'] ?>" title="hapus">&times;</button>
              <div class="nm"><?= htmlspecialchars($ff['name']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="input-group input-group-sm mt-2" style="max-width:400px">
        <input type="file" class="form-control pg-file" data-id="<?= $it['id'] ?>">
        <button class="btn btn-outline-primary pg-up" data-id="<?= $it['id'] ?>">Lampirkan</button>
      </div>

      <div class="d-flex align-items-center gap-2 mt-3">
        <?php foreach (array('👍','❤️','😂','🔥','🎉') as $emo):
          $jml = 0; foreach (($it['reactions'] ?: array()) as $rr) { if ($rr['emoji']===$emo) $jml = $rr['jml']; }
          $aktifR = (!empty($it['my_reaction']) && $it['my_reaction']===$emo); ?>
          <button class="pg-emo-btn<?= $aktifR ? ' aktif':'' ?>" data-ann="<?= $it['id'] ?>" data-e="<?= $emo ?>"><?= $emo ?><?php if($jml): ?> <small><?= $jml ?></small><?php endif; ?></button>
        <?php endforeach; ?>
      </div>

      <div class="mt-3 pt-3" style="border-top:1px solid #eef1f6">
        <div class="pg-sec-label">Komentar &amp; Aktifitas</div>
        <?php if (empty($it['comments'])): ?>
          <div class="text-muted small mt-2">Belum ada komentar disini...</div>
        <?php else: foreach ($it['comments'] as $cm):
          $pal2=['#6366f1','#f59e0b','#10b981','#ef4444','#0ea5e9','#a855f7','#ec4899'];
          $cw2 = $pal2[ord(substr($cm['nama']?:'?',0,1)) % count($pal2)];
          $bisaHapus = $boleh || (intval($cm['user_id']) === intval($uid)); ?>
          <div class="d-flex gap-2 mt-2">
            <div class="pg-av sm" style="background:<?= $cw2 ?>"><?= strtoupper(substr($cm['nama'] ?: '?', 0, 1)) ?></div>
            <div class="flex-grow-1" data-crow="<?= $cm['id'] ?>">
              <span class="pg-cnm"><?= htmlspecialchars($cm['nama'] ?: 'Pengguna') ?></span>
              <span class="pg-ctg"><?= !empty($cm['edited_at']) ? $cm['edited_at'] : $cm['created_at'] ?><?php if (!empty($cm['edited'])): ?> &middot; <i>diedit</i><?php endif; ?></span>
              <div class="pg-ctx" data-teks="<?= htmlspecialchars($cm['comment'], ENT_QUOTES) ?>"><?= nl2br(htmlspecialchars($cm['comment'])) ?></div>
              <?php if ($bisaHapus): ?>
                <button class="pg-cact pg-cedit" data-id="<?= $cm['id'] ?>">edit</button>
                <button class="pg-cact pg-cdel" data-id="<?= $cm['id'] ?>">hapus</button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; endif; ?>

        <div class="d-flex gap-2 mt-3">
          <div class="pg-av sm" style="background:#94a3b8"><i class="bi bi-person-fill"></i></div>
          <div class="input-group input-group-sm flex-grow-1">
            <input type="text" class="form-control pg-cin" data-ann="<?= $it['id'] ?>" placeholder="tulis kritik / saran...">
            <button class="btn btn-primary pg-csend" data-ann="<?= $it['id'] ?>">Kirim</button>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
<?php endif; ?>

<div class="modal fade" id="mdPg" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Pengumuman</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" id="pgId">
    <div class="mb-3"><label class="form-label">Judul *</label><input type="text" class="form-control" id="pgTitle"></div>
    <div class="mb-3"><label class="form-label">Isi</label><textarea class="form-control" id="pgBody" rows="6"></textarea></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" id="pgPin"><label class="form-check-label" for="pgPin">Sematkan di atas</label></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button class="btn btn-primary" id="btnSimpanPg">Simpan</button></div>
</div></div></div>

<script>
(function(){
  var BASE='<?= base_url() ?>', TEAM=<?= intval($team_id) ?>;
  var md = new bootstrap.Modal(document.getElementById('mdPg'));
  function buka(d){
    document.getElementById('pgId').value    = d ? d.id : '';
    document.getElementById('pgTitle').value = d ? (d.title||'') : '';
    document.getElementById('pgBody').value  = d ? (d.body||'') : '';
    document.getElementById('pgPin').checked = d ? (d.is_pinned==1) : false;
    md.show();
  }
  var b = document.getElementById('btnBaruPg');
  if (b) b.addEventListener('click', function(){ buka(null); });
  document.querySelectorAll('.btn-ubah-pg').forEach(function(x){
    x.addEventListener('click', function(){ buka(JSON.parse(this.dataset.json||'{}')); });
  });
  document.getElementById('btnSimpanPg').addEventListener('click', function(){
    var j = document.getElementById('pgTitle').value.trim();
    if(!j){ alert('Judul wajib diisi.'); return; }
    if (this.disabled) return;
    var btn = this;
    var teksAsli = btn.textContent;
    var fd = new FormData();
    fd.append('id', document.getElementById('pgId').value);
    fd.append('team_id', TEAM);
    fd.append('title', j);
    fd.append('body', document.getElementById('pgBody').value);
    if (document.getElementById('pgPin').checked) fd.append('is_pinned', 1);
    btn.disabled = true; btn.textContent = 'Menyimpan...';
    fetch(BASE+'kinerja/save_pengumuman',{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ if(d.status) location.reload(); else { alert(d.msg); btn.disabled=false; btn.textContent=teksAsli; } })
      .catch(function(){ alert('Gagal menghubungi server.'); btn.disabled=false; btn.textContent=teksAsli; });
  });
  document.querySelectorAll('.pg-up').forEach(function(b){
    b.addEventListener('click', function(){
      var id = this.dataset.id;
      var inp = document.querySelector('.pg-file[data-id="'+id+'"]');
      if (!inp || !inp.files[0]) { alert('Pilih berkas dulu.'); return; }
      var fd = new FormData(); fd.append('ann_id', id); fd.append('berkas', inp.files[0]);
      var t = this; t.disabled = true; t.textContent = '...';
      fetch(BASE+'kinerja/upload_ann_file',{method:'POST',body:fd,credentials:'same-origin'})
        .then(function(r){return r.json();})
        .then(function(d){ if(d.status) location.reload(); else { alert(d.msg); t.disabled=false; t.textContent='Lampirkan'; } });
    });
  });

  document.querySelectorAll('.pg-fdel').forEach(function(x){
    x.addEventListener('click', function(){
      var id = this.dataset.id;
      pgConfirmHapus(function(ok, selesai){
        if (!ok) return;
        var fd=new FormData(); fd.append('id', id);
        fetch(BASE+'kinerja/delete_ann_file',{method:'POST',body:fd,credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(d){
            selesai(!!d.status, d.msg);
            if (d.status) setTimeout(function(){ location.reload(); }, 950);
          })
          .catch(function(){ selesai(false, 'Gagal menghubungi server.'); });
      }, {judul:'Hapus lampiran ini?', sub:'Gambar/lampiran ini akan dihapus secara permanen.', sukJudul:'Lampiran dihapus', sukSub:'Lampiran berhasil dihapus.'});
    });
  });

  document.querySelectorAll('.btn-hapus-pg').forEach(function(x){
    x.addEventListener('click', function(){
      var id = this.dataset.id;
      pgConfirmHapus(function(ok, selesai){
        if (!ok) return;
        var fd=new FormData(); fd.append('id', id);
        fetch(BASE+'kinerja/delete_pengumuman',{method:'POST',body:fd,credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(d){
            selesai(!!d.status, d.msg);
            if (d.status) setTimeout(function(){ location.reload(); }, 950);
          })
          .catch(function(){ selesai(false, 'Gagal menghubungi server.'); });
      }, {judul:'Hapus pengumuman ini?', sub:'Seluruh isi, lampiran, dan komentar akan ikut terhapus permanen.', sukJudul:'Pengumuman dihapus', sukSub:'Pengumuman berhasil dihapus.'});
    });
  });
})();
</script>

<div id="pgLbBox"><button class="pg-lb-close" id="pgLbClose"><i class="bi bi-x-lg"></i></button><img id="pgLbImg" src="" alt=""></div>

<div id="pgConfirmBox">
  <div class="pg-cf-card">
    <div class="pg-cf-icwrap">
      <div class="pg-cf-ring pulse"></div>
      <div class="pg-cf-ic" id="pgCfIc"><i class="bi bi-trash3-fill"></i></div>
      <div class="pg-cf-done" id="pgCfDone"><i class="bi bi-check-lg"></i></div>
    </div>
    <div class="pg-cf-title" id="pgCfTitle">Hapus komentar ini?</div>
    <div class="pg-cf-sub" id="pgCfSub">Tindakan ini tidak bisa dibatalkan.</div>
    <div class="pg-cf-btns" id="pgCfBtns">
      <button class="pg-cf-cancel" id="pgCfCancel">Batal</button>
      <button class="pg-cf-ok" id="pgCfOk"><span class="spin"></span><span class="txt">Hapus</span></button>
    </div>
  </div>
</div>

<script>
(function(){
  var BASE4 = '<?= base_url() ?>';

  var lb = document.getElementById('pgLbBox');
  var lbImg = document.getElementById('pgLbImg');
  document.querySelectorAll('.pg-lb').forEach(function(a){
    a.addEventListener('click', function(e){
      e.preventDefault();
      lbImg.src = this.dataset.src;
      requestAnimationFrame(function(){ lb.classList.add('on'); });
    });
  });
  function tutupLb(){ lb.classList.remove('on'); setTimeout(function(){ lbImg.src=''; }, 250); }
  lb.addEventListener('click', function(e){ if (e.target === lb) tutupLb(); });
  document.getElementById('pgLbClose').addEventListener('click', tutupLb);
  document.addEventListener('keydown', function(e){ if (e.key==='Escape') tutupLb(); });

  var cfBox = document.getElementById('pgConfirmBox');
  var cfOk = document.getElementById('pgCfOk');
  var cfCancel = document.getElementById('pgCfCancel');
  var cfIc = document.getElementById('pgCfIc');
  var cfDone = document.getElementById('pgCfDone');
  var cfTitle = document.getElementById('pgCfTitle');
  var cfSub = document.getElementById('pgCfSub');
  var cfBtns = document.getElementById('pgCfBtns');

  window.pgConfirmHapus = function(cb, opts){
    opts = opts || {};
    cfTitle.textContent = opts.judul || 'Hapus komentar ini?';
    cfSub.textContent = opts.sub || 'Tindakan ini tidak bisa dibatalkan.';
    cfIc.style.display='flex'; cfDone.style.display='none'; cfBtns.style.display='flex'; cfOk.classList.remove('loading');
    cfBox.classList.add('on');
    function reset(){
      cfBox.classList.remove('on');
      setTimeout(function(){
        cfOk.classList.remove('loading');
        cfIc.style.display='flex'; cfDone.style.display='none';
        cfBtns.style.display='flex';
      }, 280);
      cfOk.removeEventListener('click', onOk);
      cfCancel.removeEventListener('click', onCancel);
    }
    function onCancel(){
      var card = cfBox.querySelector('.pg-cf-card');
      card.classList.add('pg-cf-shake');
      setTimeout(function(){
        card.classList.remove('pg-cf-shake');
        reset(); cb(false);
      }, 220);
    }
    function onOk(){
      cfOk.classList.add('loading');
      cb(true, function selesai(sukses, pesan){
        if (!sukses){
          cfOk.classList.remove('loading');
          alert(pesan || 'Gagal menghapus.');
          reset();
          return;
        }
        cfIc.style.display='none'; cfDone.style.display='flex';
        cfTitle.textContent = opts.sukJudul || 'Berhasil dihapus';
        cfSub.textContent = opts.sukSub || 'Data berhasil dihapus.';
        cfBtns.style.display='none';
        setTimeout(reset, 950);
      });
    }
    cfOk.addEventListener('click', onOk);
    cfCancel.addEventListener('click', onCancel);
  }

  document.querySelectorAll('.pg-cedit').forEach(function(b){
    b.addEventListener('click', function(){
      var id = this.dataset.id;
      var row = document.querySelector('[data-crow="'+id+'"]');
      var txtEl = row.querySelector('.pg-ctx');
      var tgEl = row.querySelector('.pg-ctg');
      var asli = txtEl.dataset.teks;
      row.querySelectorAll('.pg-cact').forEach(function(x){ x.style.display='none'; });

      var wrap = document.createElement('div');
      wrap.className = 'pg-editbox';
      wrap.innerHTML = '<textarea class="pg-eedit" rows="2">'+asli+'</textarea>'
        + '<div class="pg-ebtns">'
        +   '<button class="pg-esave">Simpan</button>'
        +   '<button class="pg-ecancel">Batal</button>'
        + '</div>';
      txtEl.style.display = 'none';
      txtEl.insertAdjacentElement('afterend', wrap);
      wrap.querySelector('textarea').focus();

      wrap.querySelector('.pg-ecancel').addEventListener('click', function(){
        wrap.remove(); txtEl.style.display='';
        row.querySelectorAll('.pg-cact').forEach(function(x){ x.style.display=''; });
      });
      wrap.querySelector('.pg-esave').addEventListener('click', function(){
        var baru = wrap.querySelector('.pg-eedit').value.trim();
        if (!baru) return;
        var fd = new FormData(); fd.append('id', id); fd.append('comment', baru);
        fetch(BASE4+'kinerja/edit_ann_comment', {method:'POST', body:fd, credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(d){
            if (!d.status){ alert(d.msg); return; }
            txtEl.textContent = baru;
            txtEl.dataset.teks = baru;
            if (d.berubah){
              var now = new Date();
              var jam = String(now.getHours()).padStart(2,'0')+':'+String(now.getMinutes()).padStart(2,'0');
              tgEl.innerHTML = 'Hari ini, '+jam+' <span class="tag-edited">&middot; diedit</span>';
              tgEl.classList.add('flash');
              setTimeout(function(){ tgEl.classList.remove('flash'); }, 1000);
            }
            wrap.remove(); txtEl.style.display='';
            row.querySelectorAll('.pg-cact').forEach(function(x){ x.style.display=''; });
          });
      });
    });
  });

  document.querySelectorAll('.pg-emo-btn').forEach(function(b){
    b.addEventListener('click', function(){
      var btn = this;
      if (btn.dataset.kirim === '1') return;
      btn.dataset.kirim = '1';

      /* perbarui tampilan langsung, tanpa muat ulang */
      function ubahJml(el, selisih){
        var k = el.querySelector('small');
        var n = k ? (parseInt(k.textContent) || 0) : 0;
        n += selisih;
        if (n > 0) {
          if (k) k.textContent = n;
          else el.insertAdjacentHTML('beforeend', ' <small>' + n + '</small>');
        } else if (k) { k.remove(); }
      }

      var ann = btn.dataset.ann;
      var sudahAktif = btn.classList.contains('aktif');

      if (sudahAktif) {
        /* klik emoji yang sama = batalkan reaksi */
        btn.classList.remove('aktif');
        ubahJml(btn, -1);
      } else {
        /* matikan reaksi lain di pengumuman yang sama */
        document.querySelectorAll('.pg-emo-btn[data-ann="' + ann + '"].aktif').forEach(function(lain){
          lain.classList.remove('aktif');
          ubahJml(lain, -1);
        });
        btn.classList.add('aktif');
        ubahJml(btn, 1);
      }

      var fd = new FormData(); fd.append('ann_id', btn.dataset.ann); fd.append('emoji', btn.dataset.e);
      fetch(BASE4+'kinerja/toggle_ann_reaction', {method:'POST', body:fd, credentials:'same-origin'})
        .then(function(r){return r.json();})
        .then(function(d){
          btn.dataset.kirim = '';
          if (!d.status) { location.reload(); }
        })
        .catch(function(){ btn.dataset.kirim = ''; });
    });
  });

  document.querySelectorAll('.pg-csend').forEach(function(b){
    b.addEventListener('click', function(){
      var ann = this.dataset.ann;
      var inp = document.querySelector('.pg-cin[data-ann="'+ann+'"]');
      var txt = inp.value.trim();
      if (!txt) return;
      var fd = new FormData(); fd.append('ann_id', ann); fd.append('comment', txt);
      var btn = this; btn.disabled = true;
      fetch(BASE4+'kinerja/add_ann_comment', {method:'POST', body:fd, credentials:'same-origin'})
        .then(function(r){return r.json();})
        .then(function(d){ btn.disabled = false; if (d.status) location.reload(); else alert(d.msg); });
    });
  });

  document.querySelectorAll('.pg-cin').forEach(function(inp){
    inp.addEventListener('keydown', function(e){
      if (e.key === 'Enter') document.querySelector('.pg-csend[data-ann="'+this.dataset.ann+'"]').click();
    });
  });

  document.querySelectorAll('.pg-cdel').forEach(function(b){
    b.addEventListener('click', function(){
      var id = this.dataset.id;
      var row = document.querySelector('[data-crow="'+id+'"]');
      pgConfirmHapus(function(ok, selesai){
        if (!ok) return;
        var fd = new FormData(); fd.append('id', id);
        fetch(BASE4+'kinerja/delete_ann_comment', {method:'POST', body:fd, credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(d){
            selesai(!!d.status, d.msg);
            if (d.status) setTimeout(function(){ row.remove(); }, 950);
          })
          .catch(function(){ selesai(false, 'Gagal menghubungi server.'); });
      }, {judul:'Hapus komentar ini?', sub:'Tindakan ini tidak bisa dibatalkan.', sukJudul:'Komentar dihapus', sukSub:'Komentar berhasil dihapus dari pengumuman.'});
    });
  });
})();
</script>
