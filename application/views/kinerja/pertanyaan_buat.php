<style>
.pb-wrap{background:#fff;border:1px solid #e5e8f0;border-radius:16px;padding:32px 36px;max-width:760px}
.pb-label{font-size:1rem;font-weight:600;color:#1e293b;margin-bottom:10px}
.pb-input{width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:13px 16px;font-size:.92rem;margin-bottom:26px}
.pb-input:focus{outline:0;border-color:#6E4FA8;box-shadow:0 0 0 3px rgba(110,79,168,.14)}
.pb-hari{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:26px}
.pb-hari button{border:1px solid #cbd5e1;background:#fff;color:#475569;border-radius:9px;padding:9px 18px;font-size:.85rem;cursor:pointer;transition:.15s}
.pb-hari button.on{background:#16a34a;border-color:#16a34a;color:#fff;font-weight:600}
.pb-jam{width:220px;border:1px solid #cbd5e1;border-radius:10px;padding:11px 14px;font-size:.9rem;margin-bottom:26px}
.pb-pen{display:flex;align-items:center;gap:8px;margin-bottom:26px;flex-wrap:wrap}
.pb-av{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#6E4FA8,#4aa8ff);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;position:relative;cursor:pointer}
.pb-av .rm{position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;border-radius:50%;width:16px;height:16px;font-size:.6rem;display:flex;align-items:center;justify-content:center}
.pb-add-av{width:38px;height:38px;border-radius:50%;background:#f1f5f9;color:#64748b;border:1px dashed #cbd5e1;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1.1rem}
.pb-rahasia{display:flex;align-items:center;gap:12px;margin-bottom:30px}
.pb-tombol{display:flex;gap:12px}
.pb-tombol button{border:0;border-radius:10px;padding:11px 26px;font-size:.88rem;font-weight:600;cursor:pointer}
.pb-simpan{background:#16a34a;color:#fff}
.pb-simpan:hover{background:#15803d}
.pb-batal{background:#ef4444;color:#fff}
.pb-batal:hover{background:#dc2626}
.pb-pilihpanel{position:relative}
#pbPenerimaList{position:absolute;top:44px;left:0;background:#fff;border:1px solid #e2e6ee;border-radius:12px;box-shadow:0 14px 40px rgba(0,0,0,.16);padding:8px;width:240px;z-index:20;display:none;max-height:260px;overflow-y:auto}
#pbPenerimaList.on{display:block}
.pb-arow{display:flex;align-items:center;gap:9px;padding:8px;border-radius:8px;cursor:pointer;font-size:.83rem;color:#334155}
.pb-arow:hover{background:#f4f6fa}
.pb-arow input{margin:0}
.pb-hasil{position:fixed;inset:0;z-index:42000;display:flex;align-items:center;justify-content:center;background:rgba(15,18,28,.5);backdrop-filter:blur(3px);opacity:0;pointer-events:none;transition:opacity .22s ease}
.pb-hasil.on{opacity:1;pointer-events:auto}
.pb-hbox{background:#fff;border-radius:18px;padding:34px 38px;text-align:center;box-shadow:0 28px 70px rgba(0,0,0,.3);transform:scale(.7);opacity:0;animation:pbPop .42s cubic-bezier(.18,.9,.32,1.4) forwards}
@keyframes pbPop{to{transform:scale(1);opacity:1}}
.pb-hic{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:2rem;color:#fff}
.pb-hic.ok{background:linear-gradient(135deg,#22c55e,#16a34a);box-shadow:0 10px 26px rgba(34,197,94,.4)}
.pb-hic.no{background:linear-gradient(135deg,#94a3b8,#64748b);box-shadow:0 10px 26px rgba(100,116,139,.35)}
.pb-hic i{animation:pbCk .5s .15s cubic-bezier(.18,.9,.32,1.6) both}
@keyframes pbCk{from{transform:scale(0) rotate(-30deg)}to{transform:scale(1) rotate(0)}}
.pb-ht{font-size:1.05rem;font-weight:700;color:#1e293b}
.pb-hs{font-size:.82rem;color:#94a3b8;margin-top:5px}
</style>

<div class="pb-hasil" id="pbHasil">
  <div class="pb-hbox">
    <div class="pb-hic" id="pbHic"><i class="bi"></i></div>
    <div class="pb-ht" id="pbHt"></div>
    <div class="pb-hs" id="pbHs"></div>
  </div>
</div>

<div class="pb-wrap">
  <div class="pb-label">Pertanyaan rutin apa yang ingin kamu tanyakan?</div>
  <input type="text" class="pb-input" id="pbTeks" placeholder="Berapa data penjualan hari ini? Apa yang kamu kerjakan minggu ini?, dll">

  <div class="pb-label">Pada hari apa aja pertanyaan ini dikirim?</div>
  <div class="pb-hari">
    <?php foreach (array('Sen','Sel','Rab','Kam','Jum','Sab','Min') as $h): ?>
      <button type="button" class="pb-hbtn" data-h="<?= $h ?>"><?= $h ?></button>
    <?php endforeach; ?>
  </div>

  <div class="pb-label">Jam berapa?</div>
  <input type="time" class="pb-jam" id="pbJam" value="09:00">

  <div class="pb-label">Siapa aja Penerimanya?</div>
  <div class="pb-pen pb-pilihpanel">
    <div id="pbAvatarWrap" class="d-flex gap-2 flex-wrap"></div>
    <div class="pb-add-av" id="pbBukaPilih"><i class="bi bi-plus-lg"></i></div>
    <div id="pbPenerimaList">
      <?php foreach ($anggota as $a): ?>
        <label class="pb-arow">
          <input type="checkbox" class="pb-chk" value="<?= $a['id'] ?>" data-nm="<?= htmlspecialchars($a['full_name']) ?>">
          <span><?= htmlspecialchars($a['full_name']) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="pb-label">Lampirkan gambar (opsional)</div>
  <input type="file" id="pbGambarInput" accept="image/*" multiple style="margin-bottom:12px">
  <div id="pbGambarPrev" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:22px"></div>

  <div class="pb-label" style="margin-bottom:8px">Apakah pertanyaan ini Rahasia untuk Penerima aja?</div>
  <div class="pb-rahasia">
    <div class="form-check form-switch m-0">
      <input class="form-check-input" type="checkbox" id="pbRahasia" style="width:2.6em;height:1.4em">
    </div>
    <span>Rahasia</span>
  </div>

  <div class="pb-tombol">
    <button type="button" class="pb-simpan" id="btnPublikasikan">Publikasikan</button>
    <button type="button" class="pb-batal" id="btnBatalPt">Batal</button>
  </div>
</div>

<script>
(function(){
  var BASE = '<?= base_url() ?>', TEAM = <?= intval($team_id) ?>;
  function pbTampilHasil(ok, judul, sub, tujuan){
    var ov = document.getElementById('pbHasil');
    document.getElementById('pbHic').className = 'pb-hic ' + (ok ? 'ok' : 'no');
    document.getElementById('pbHic').innerHTML = '<i class="bi ' + (ok ? 'bi-check-lg' : 'bi-x-lg') + '"></i>';
    document.getElementById('pbHt').textContent = judul;
    document.getElementById('pbHs').textContent = sub;
    ov.classList.add('on');
    setTimeout(function(){ location.href = tujuan; }, 1100);
  }

  document.getElementById('pbGambarInput').addEventListener('change', function(){
    var box = document.getElementById('pbGambarPrev');
    box.innerHTML = '';
    Array.from(this.files).forEach(function(f){
      if (!f.type.startsWith('image/')) return;
      var rd = new FileReader();
      rd.onload = function(e){
        var img = document.createElement('img');
        img.src = e.target.result;
        img.style.cssText = 'width:64px;height:64px;object-fit:cover;border-radius:9px;border:1px solid #e2e6ee';
        box.appendChild(img);
      };
      rd.readAsDataURL(f);
    });
  });

  var hariAktif = {};
  var penerimaAktif = {};

  document.querySelectorAll('.pb-hbtn').forEach(function(b){
    b.addEventListener('click', function(){
      var h = this.dataset.h;
      this.classList.toggle('on');
      if (this.classList.contains('on')) hariAktif[h] = true; else delete hariAktif[h];
    });
  });
  // default Senin aktif seperti contoh
  var senBtn = document.querySelector('.pb-hbtn[data-h="Sen"]');
  if (senBtn) { senBtn.classList.add('on'); hariAktif['Sen'] = true; }

  var pilihBtn = document.getElementById('pbBukaPilih');
  var pilihList = document.getElementById('pbPenerimaList');
  pilihBtn.addEventListener('click', function(e){
    e.stopPropagation();
    pilihList.classList.toggle('on');
  });
  document.addEventListener('click', function(e){
    if (!pilihList.contains(e.target) && e.target !== pilihBtn) pilihList.classList.remove('on');
  });

  function gambarAvatar(){
    var wrap = document.getElementById('pbAvatarWrap');
    var keys = Object.keys(penerimaAktif);
    wrap.innerHTML = keys.map(function(uid){
      var nm = penerimaAktif[uid];
      var ini = (nm||'?').trim().charAt(0).toUpperCase();
      return '<div class="pb-av" data-uid="'+uid+'" title="'+nm+'">'+ini+'<span class="rm" data-uid="'+uid+'">&times;</span></div>';
    }).join('');
    wrap.querySelectorAll('.rm').forEach(function(x){
      x.addEventListener('click', function(e){
        e.stopPropagation();
        var uid = this.dataset.uid;
        delete penerimaAktif[uid];
        var chk = document.querySelector('.pb-chk[value="'+uid+'"]');
        if (chk) chk.checked = false;
        gambarAvatar();
      });
    });
  }

  document.querySelectorAll('.pb-chk').forEach(function(c){
    c.addEventListener('change', function(){
      if (this.checked) penerimaAktif[this.value] = this.dataset.nm;
      else delete penerimaAktif[this.value];
      gambarAvatar();
    });
  });

  document.getElementById('btnBatalPt').addEventListener('click', function(){
    uiConfirm('Batalkan pertanyaan ini?', 'Semua isian yang sudah kamu tulis akan hilang.', function(){
      pbTampilHasil(false, 'Dibatalkan', 'Pertanyaan tidak jadi dibuat.', BASE + 'kinerja/pertanyaan?team=' + TEAM);
    }, 'bi-x-lg');
  });

  document.getElementById('btnPublikasikan').addEventListener('click', function(){
    var teks = document.getElementById('pbTeks').value.trim();
    if (!teks) { uiToast('Pertanyaan wajib diisi.','info'); return; }
    var hari = Object.keys(hariAktif);
    if (!hari.length) { uiToast('Pilih minimal satu hari.','info'); return; }

    uiConfirm('Publikasikan pertanyaan ini?', 'Pertanyaan akan langsung aktif dan dikirim ke penerima terpilih.', function(){
      kirimPertanyaan();
    }, 'bi-send');
  });

  function kirimPertanyaan(){
    var b = document.getElementById('btnPublikasikan');
    if (b.disabled) return;
    var teksAsli = b.textContent;
    var teks = document.getElementById('pbTeks').value.trim();
    var hari = Object.keys(hariAktif);
    var fd = new FormData();
    fd.append('team_id', TEAM);
    fd.append('pertanyaan', teks);
    hari.forEach(function(h){ fd.append('hari[]', h); });
    fd.append('jam', document.getElementById('pbJam').value || '09:00');
    var fGambar = document.getElementById('pbGambarInput').files;
    for (var gi = 0; gi < fGambar.length; gi++) fd.append('gambar[]', fGambar[gi]);
    if (document.getElementById('pbRahasia').checked) fd.append('rahasia', 1);
    Object.keys(penerimaAktif).forEach(function(uid){ fd.append('penerima[]', uid); });

    b.disabled = true; b.textContent = 'Memublikasikan...';
    fetch(BASE+'kinerja/save_pertanyaan', {method:'POST', body:fd, credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        if (d.status) {
          pbTampilHasil(true, 'Berhasil dipublikasikan!', 'Pertanyaan sudah aktif untuk tim ini.', BASE + 'kinerja/pertanyaan?team=' + TEAM);
        } else { uiToast(d.msg,'err'); b.disabled=false; b.textContent=teksAsli; }
      })
      .catch(function(){ uiToast('Gagal menghubungi server.','err'); b.disabled=false; b.textContent=teksAsli; });
  }
})();
</script>
