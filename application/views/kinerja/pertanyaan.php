<?php $halaman='pertanyaan'; $this->load->view('kinerja/_tabs', array('teams'=>$teams,'team_id'=>$team_id,'halaman'=>$halaman)); ?>
<style>
.pt-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;flex-wrap:wrap}
.pt-add{background:#6E4FA8;color:#fff;border:0;border-radius:11px;padding:10px 20px;font-size:.86rem;font-weight:500;cursor:pointer;transition:.18s;box-shadow:0 3px 10px rgba(110,79,168,.28);text-decoration:none;display:inline-block}
.pt-add:hover{background:#5b3f95;color:#fff;transform:translateY(-1px)}
.pt-card{background:#fff;border:1px solid #e5e8f0;border-radius:14px;padding:18px 20px;margin-bottom:12px}
.pt-q{font-size:.98rem;font-weight:600;color:#1e293b;margin-bottom:9px;line-height:1.4}
.pt-meta{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.pt-badge{font-size:.71rem;padding:3px 10px;border-radius:99px;background:#f1f5f9;color:#64748b;display:inline-flex;align-items:center;gap:5px}
.pt-badge.rahasia{background:#fef3c7;color:#92400e}
.pt-badge.jam{background:#eff6ff;color:#2563eb}
.pt-bawah{display:flex;align-items:center;justify-content:space-between;gap:10px;padding-top:12px;border-top:1px solid #eef1f6}
.pt-progres{font-size:.78rem;color:#64748b}
.pt-progres b{color:#16a34a}
.pt-aksi{display:flex;gap:8px}
.pt-aksi button{border:1px solid #e2e6ee;background:#fff;border-radius:9px;padding:6px 13px;font-size:.78rem;color:#475569;cursor:pointer;transition:.15s}
.pt-aksi button:hover{border-color:#6E4FA8;color:#6E4FA8}
.pt-aksi .del:hover{border-color:#ef4444;color:#ef4444}
.pt-gal{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.pt-gal img{width:76px;height:76px;object-fit:cover;border-radius:9px;cursor:zoom-in;border:1px solid #e2e6ee;transition:.15s}
.pt-gal img:hover{transform:scale(1.04)}
.pt-lb{position:fixed;inset:0;background:rgba(8,11,20,.94);z-index:44000;display:flex;align-items:center;justify-content:center;padding:40px 20px;opacity:0;pointer-events:none;transition:opacity .22s ease}
.pt-lb.on{opacity:1;pointer-events:auto;cursor:zoom-out}
.pt-lb img{max-width:90vw;max-height:86vh;object-fit:contain;border-radius:10px;box-shadow:0 24px 70px rgba(0,0,0,.6);transform:scale(.88);transition:transform .26s cubic-bezier(.18,.9,.32,1.25)}
.pt-lb.on img{transform:scale(1)}
.pt-jimg{width:100%;max-width:280px;border-radius:10px;margin-top:8px;cursor:zoom-in;display:block}
.pt-kosong{text-align:center;padding:60px 20px;color:#94a3b8}

.pt-jwrap{margin-top:12px;padding-top:12px;border-top:1px solid #eef1f6;display:none}
.pt-jwrap.on{display:block}
.pt-jrow{display:flex;gap:11px;padding:12px 0;border-bottom:1px solid #f4f6fa}
.pt-jrow:last-child{border-bottom:0}
.pt-jav{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6E4FA8,#4aa8ff);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.74rem;font-weight:700;flex:0 0 auto}
.pt-jbody{flex:1;background:#f8fafc;border-radius:11px;padding:10px 13px}
.pt-jnm{font-size:.82rem;font-weight:600;color:#1e293b}
.pt-jtgl{font-size:.68rem;color:#94a3b8;font-weight:400}
.pt-jtx{font-size:.85rem;color:#334155;margin-top:5px;white-space:pre-wrap;line-height:1.6}
.pt-jjudul{font-size:.72rem;font-weight:600;color:#94a3b8;letter-spacing:.4px;text-transform:uppercase;margin-bottom:10px}
</style>

<div class="pt-bar">
  <span class="text-muted" style="font-size:.82rem">Pertanyaan check-in rutin untuk tim ini</span>
  <?php if ($boleh && $team_id): ?>
    <a href="<?= base_url() ?>kinerja/pertanyaan_buat?team=<?= $team_id ?>" class="pt-add"><i class="bi bi-plus-lg me-1"></i>Buat Pertanyaan</a>
  <?php endif; ?>
</div>

<?php if (!$team_id): ?>
  <div class="card"><div class="card-body pt-kosong">Belum ada tim.</div></div>
<?php elseif (empty($daftar)): ?>
  <div class="card"><div class="card-body pt-kosong">
    <i class="bi bi-patch-question" style="font-size:2.2rem;opacity:.35"></i>
    <p class="mt-3 mb-0">Belum ada Pertanyaan disini...</p></div></div>
<?php else:
  $hariSingkat = array('Sen'=>'Sen','Sel'=>'Sel','Rab'=>'Rab','Kam'=>'Kam','Jum'=>'Jum','Sab'=>'Sab','Min'=>'Min');
  foreach ($daftar as $q):
    $hariArr = explode(',', $q['hari']);
    $jamTampil = date('H:i', strtotime($q['jam']));
  ?>
  <div class="pt-card" data-id="<?= $q['id'] ?>">
    <a href="<?= base_url() ?>kinerja/pertanyaan_detail/<?= $q['id'] ?>" class="pt-q" style="text-decoration:none;display:block"><?= htmlspecialchars($q['pertanyaan']) ?></a>
    <div class="pt-pembuat-row" style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
      <div style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#6E4FA8,#4aa8ff);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:700;flex:0 0 auto"><?= strtoupper(substr(trim($q['pembuat'] ?: '?'),0,1)) ?></div>
      <span style="font-size:.75rem;color:#94a3b8">dibuat oleh <b style="color:#475569"><?= htmlspecialchars($q['pembuat'] ?: '-') ?></b> &middot; <?= date('j M Y', strtotime($q['created_at'])) ?></span>
    </div>
    <?php if (!empty($q['files'])): ?>
      <div class="pt-gal">
        <?php foreach ($q['files'] as $gf): ?>
          <img src="<?= base_url().$gf['file_path'] ?>" class="pt-lb-open" data-src="<?= base_url().$gf['file_path'] ?>" loading="lazy">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="pt-meta">
      <span class="pt-badge"><i class="bi bi-calendar-week"></i> <?= htmlspecialchars(implode(', ', $hariArr)) ?></span>
      <span class="pt-badge jam"><i class="bi bi-clock"></i> <?= $jamTampil ?></span>
      <span class="pt-badge"><i class="bi bi-people"></i> <?= $q['jml_penerima'] ?> penerima</span>
      <?php if ($q['rahasia']): ?><span class="pt-badge rahasia"><i class="bi bi-eye-slash"></i> Rahasia</span><?php endif; ?>
    </div>
    <div class="pt-bawah">
      <div class="pt-progres">
        <b><?= $q['jml_jawab_hari_ini'] ?></b> / <?= $q['jml_penerima'] ?> menjawab hari ini
        &middot; dibuat oleh <?= htmlspecialchars($q['pembuat'] ?: '-') ?>
      </div>
      <div class="pt-aksi">
        <button class="pt-jawab" data-id="<?= $q['id'] ?>">Jawab</button>
        <button class="pt-lihat" data-id="<?= $q['id'] ?>">Lihat Jawaban</button>
        <?php if ($boleh): ?><button class="del pt-del" data-id="<?= $q['id'] ?>"><i class="bi bi-trash3"></i></button><?php endif; ?>
      </div>
    </div>
    <div class="pt-jwrap" id="jwrap-<?= $q['id'] ?>"></div>
  </div>
  <?php endforeach; endif; ?>

<div class="modal fade" id="mdJawab" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Jawab Pertanyaan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" id="jwId">
    <div id="jwPertanyaan" class="fw-semibold mb-2" style="font-size:.92rem"></div>
    <textarea class="form-control mb-2" id="jwIsi" rows="4" placeholder="tulis jawabanmu..."></textarea>
    <input type="file" id="jwGambar" accept="image/*">
    <div id="jwGambarPrev" class="mt-2"></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button class="btn btn-primary" id="btnKirimJawab">Kirim</button></div>
</div></div></div>

<script>
(function(){
  var BASE = '<?= base_url() ?>', TEAM = <?= intval($team_id) ?>;
  var mdJ = document.getElementById('mdJawab') ? new bootstrap.Modal(document.getElementById('mdJawab')) : null;



  document.querySelectorAll('.pt-jawab').forEach(function(b){
    b.addEventListener('click', function(){
      var card = this.closest('.pt-card');
      document.getElementById('jwId').value = this.dataset.id;
      document.getElementById('jwPertanyaan').textContent = card.querySelector('.pt-q').textContent;
      document.getElementById('jwIsi').value = '';
      mdJ.show();
    });
  });

  document.getElementById('btnKirimJawab').addEventListener('click', function(){
    var isi = document.getElementById('jwIsi').value.trim();
    var fGmb = document.getElementById('jwGambar').files[0];
    if (!isi && !fGmb) { uiToast('Jawaban tidak boleh kosong.','info'); return; }
    if (this.disabled) return;
    var btn = this;
    var teksAsli = btn.textContent;
    var fd = new FormData();
    fd.append('pertanyaan_id', document.getElementById('jwId').value);
    fd.append('jawaban', isi);
    if (fGmb) fd.append('gambar', fGmb);
    btn.disabled = true; btn.textContent = 'Mengirim...';
    fetch(BASE+'kinerja/jawab_pertanyaan', {method:'POST', body:fd, credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ if (d.status) location.reload(); else { uiToast(d.msg,'err'); btn.disabled=false; btn.textContent=teksAsli; } })
      .catch(function(){ uiToast('Gagal menghubungi server.','err'); btn.disabled=false; btn.textContent=teksAsli; });
  });

  document.querySelectorAll('.pt-lihat').forEach(function(b){
    b.addEventListener('click', function(){
      var id = this.dataset.id;
      var wrap = document.getElementById('jwrap-'+id);
      if (wrap.classList.contains('on')) { wrap.classList.remove('on'); return; }
      wrap.innerHTML = '<div class="text-muted small">memuat...</div>';
      wrap.classList.add('on');
      fetch(BASE+'kinerja/list_jawaban?pertanyaan_id='+id, {credentials:'same-origin'})
        .then(function(r){return r.json();})
        .then(function(d){
          var rows = (d.status && d.data) ? d.data : [];
          if (!rows.length) { wrap.innerHTML = '<div class="text-muted small">Belum ada yang menjawab.</div>'; return; }
          var h = '<div class="pt-jjudul">'+rows.length+' Jawaban Masuk</div>';
          h += rows.map(function(r){
            var ini = (r.nama||'?').trim().charAt(0).toUpperCase();
            var gbr = r.gambar ? '<img src="'+BASE+r.gambar+'" class="pt-jimg" data-src="'+BASE+r.gambar+'">' : '';
            return '<div class="pt-jrow"><div class="pt-jav">'+ini+'</div>'
              + '<div class="pt-jbody"><div class="pt-jnm">'+ (r.nama||'-') +' <span class="pt-jtgl">&middot; '+r.tanggal+'</span></div>'
              + '<div class="pt-jtx">'+ (r.jawaban||'').replace(/</g,'&lt;') +'</div>' + gbr + '</div></div>';
          }).join('');
          wrap.innerHTML = h;
        });
    });
  });

  document.querySelectorAll('.pt-del').forEach(function(b){
    b.addEventListener('click', function(e){
      e.preventDefault(); e.stopPropagation();
      var id = this.dataset.id;
      uiConfirm('Hapus pertanyaan ini?', 'Semua jawaban yang tersimpan ikut terhapus.', function(){ hapusPt(id); });
    });
  });
  function hapusPt(id){
    var fd = new FormData(); fd.append('id', id);
    fetch(BASE+'kinerja/delete_pertanyaan', {method:'POST', body:fd, credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ if (d.status) location.reload(); else uiToast(d.msg,'err'); });
  }
})();
</script>

<div class="pt-lb" id="ptLightbox"><img src="" alt=""></div>

<script>
(function(){
  var lb = document.getElementById('ptLightbox');
  var lbImg = lb.querySelector('img');
  function bukaLb(src){ lbImg.src = src; requestAnimationFrame(function(){ lb.classList.add('on'); }); }
  function tutupLb(){ lb.classList.remove('on'); }
  document.addEventListener('click', function(e){
    var t = e.target.closest('.pt-lb-open, .pt-jimg');
    if (t) { bukaLb(t.dataset.src || t.src); return; }
    if (e.target === lb) tutupLb();
  });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') tutupLb(); });

  var jwGambar = document.getElementById('jwGambar');
  if (jwGambar) jwGambar.addEventListener('change', function(){
    var box = document.getElementById('jwGambarPrev');
    box.innerHTML = '';
    var f = this.files[0];
    if (!f) return;
    var rd = new FileReader();
    rd.onload = function(e){
      var img = document.createElement('img');
      img.src = e.target.result;
      img.style.cssText = 'width:100px;height:100px;object-fit:cover;border-radius:9px;border:1px solid #e2e6ee';
      box.appendChild(img);
    };
    rd.readAsDataURL(f);
  });
})();
</script>
