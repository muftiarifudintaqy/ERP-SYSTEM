<div class="pd-back">
  <a href="<?= base_url() ?>kinerja/pertanyaan?team=<?= $team_id ?>"><i class="bi bi-arrow-left"></i> Kembali ke Pertanyaan</a>
</div>

<style>
.pd-back{margin-bottom:14px}
.pd-back a{color:#64748b;text-decoration:none;font-size:.82rem}
.pd-back a:hover{color:#6E4FA8}
.pd-card{background:#fff;border:1px solid #e5e8f0;border-radius:16px;padding:26px 28px;max-width:820px}
.pd-meta1{font-size:.78rem;color:#94a3b8;margin-bottom:8px}
.pd-q{font-size:1.35rem;font-weight:700;color:#1e293b;margin-bottom:18px;line-height:1.3}
.pd-pembuat{display:flex;align-items:center;gap:11px;padding-bottom:20px;margin-bottom:20px;border-bottom:1px solid #eef1f6}
.pd-av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#6E4FA8,#4aa8ff);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.92rem;font-weight:700;flex:0 0 auto}
.pd-nm{font-size:.86rem;font-weight:600;color:#1e293b}
.pd-wk{font-size:.72rem;color:#94a3b8}

.pd-sec{font-size:.86rem;font-weight:600;color:#334155;margin-bottom:14px}

.pd-input-wrap{display:flex;gap:11px;margin-bottom:22px}
.pd-input{flex:1;border:1px solid #cbd5e1;border-radius:12px;padding:11px 14px;font-size:.85rem;resize:none;min-height:44px}
.pd-input:focus{outline:0;border-color:#6E4FA8;box-shadow:0 0 0 3px rgba(110,79,168,.14)}
.pd-kirim{border:0;background:#6E4FA8;color:#fff;border-radius:10px;padding:0 18px;font-size:.82rem;font-weight:600;cursor:pointer;transition:.15s}
.pd-kirim:hover{background:#5b3f95}
.pd-kirim:disabled{opacity:.5;cursor:default}

.pd-krow{display:flex;gap:11px;padding:14px 0;border-bottom:1px solid #f4f6fa;opacity:0;transform:translateY(8px);animation:pdIn .35s ease forwards}
@keyframes pdIn{to{opacity:1;transform:none}}
.pd-krow:last-child{border-bottom:0}
.pd-kbody{flex:1;min-width:0}
.pd-khd{display:flex;align-items:center;gap:8px;margin-bottom:4px}
.pd-knm{font-size:.83rem;font-weight:600;color:#1e293b}
.pd-kwk{font-size:.7rem;color:#94a3b8}
.pd-kedited{font-size:.66rem;color:#cbd5e1;font-style:italic}
.pd-ktx{font-size:.85rem;color:#475569;line-height:1.6;white-space:pre-wrap}
.pd-kaksi{display:flex;gap:12px;margin-top:5px}
.pd-kaksi button{border:0;background:none;font-size:.72rem;color:#94a3b8;cursor:pointer;padding:0;transition:.15s}
.pd-kaksi button:hover{color:#6E4FA8}
.pd-kaksi button.pd-hapus:hover{color:#ef4444}
.pd-kosong-k{text-align:center;padding:34px;color:#94a3b8;font-size:.82rem}

.pd-eform textarea{width:100%;border:1px solid #cbd5e1;border-radius:9px;padding:9px 12px;font-size:.84rem;margin-bottom:8px}
.pd-eform .g{display:flex;gap:8px}
.pd-eform button{border:0;border-radius:8px;padding:6px 14px;font-size:.76rem;font-weight:600;cursor:pointer}
.pd-esave{background:#6E4FA8;color:#fff}
.pd-ecancel{background:#f1f5f9;color:#475569}

/* animasi hapus komentar - ledakan di tengah elemen */
.pd-hilang{animation:pdOut .38s ease forwards}
@keyframes pdOut{to{opacity:0;transform:scale(.9) translateY(-6px);max-height:0}}
</style>

<div class="pd-card">
  <div class="pd-meta1">Menanyakan <?= $q['jml_penerima'] ?? 1 ?> orang tiap <?= htmlspecialchars(str_replace(',', ', ', $q['hari'])) ?> pada <?= date('h:i A', strtotime($q['jam'])) ?></div>
  <div class="pd-q"><?= htmlspecialchars($q['pertanyaan']) ?></div>

  <div class="pd-pembuat">
    <div class="pd-av"><?= strtoupper(substr(trim($q['pembuat'] ?: '?'), 0, 1)) ?></div>
    <div>
      <div class="pd-nm"><?= htmlspecialchars($q['pembuat'] ?: 'Pengguna') ?></div>
      <div class="pd-wk">dibuat <?= date('j M Y, H:i', strtotime($q['created_at'])) ?></div>
    </div>
  </div>

  <div class="pd-sec">Komentar &amp; Aktivitas</div>

  <div class="pd-input-wrap">
    <div class="pd-av" style="width:34px;height:34px;font-size:.76rem"><?= strtoupper(substr(trim($_SESSION['user']['full_name'] ?? '?'), 0, 1)) ?></div>
    <textarea class="pd-input" id="pdInput" rows="1" placeholder="Tulis komentar..."></textarea>
    <button class="pd-kirim" id="pdKirim">Kirim</button>
  </div>

  <div id="pdList">
    <?php if (empty($komentar)): ?>
      <div class="pd-kosong-k">Belum ada komentar.</div>
    <?php else: foreach ($komentar as $k):
      $ini = strtoupper(substr(trim($k['nama'] ?: '?'), 0, 1));
      $milik = intval($k['user_id']) === intval($uid);
    ?>
      <div class="pd-krow" data-id="<?= $k['id'] ?>">
        <div class="pd-av" style="width:34px;height:34px;font-size:.76rem"><?= $ini ?></div>
        <div class="pd-kbody">
          <div class="pd-khd">
            <span class="pd-knm"><?= htmlspecialchars($k['nama'] ?: 'Pengguna') ?></span>
            <span class="pd-kwk"><?= date('j M, H:i', strtotime($k['created_at'])) ?></span>
            <?php if ($k['is_edited']): ?><span class="pd-kedited">diedit</span><?php endif; ?>
          </div>
          <div class="pd-ktx" data-isi="<?= htmlspecialchars($k['isi']) ?>"><?= nl2br(htmlspecialchars($k['isi'])) ?></div>
          <?php if ($milik || $boleh): ?>
          <div class="pd-kaksi">
            <?php if ($milik): ?><button class="pd-edit">Edit</button><?php endif; ?>
            <button class="pd-hapus">Hapus</button>
          </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<script>
(function(){
  var BASE = '<?= base_url() ?>', QID = <?= intval($q['id']) ?>, UID = <?= intval($uid) ?>, MY_NAME = '<?= addslashes($_SESSION['user']['full_name'] ?? 'Pengguna') ?>';
  var list = document.getElementById('pdList');
  var inp = document.getElementById('pdInput');
  var btn = document.getElementById('pdKirim');

  function esc(s){ var d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

  inp.addEventListener('input', function(){ this.style.height='auto'; this.style.height=(this.scrollHeight)+'px'; });
  inp.addEventListener('keydown', function(e){ if (e.key==='Enter' && !e.shiftKey){ e.preventDefault(); btn.click(); } });

  function baruBaris(k){
    var ini = (k.nama||'?').trim().charAt(0).toUpperCase();
    var div = document.createElement('div');
    div.className = 'pd-krow';
    div.dataset.id = k.id;
    div.innerHTML =
        '<div class="pd-av" style="width:34px;height:34px;font-size:.76rem">'+ini+'</div>'
      + '<div class="pd-kbody">'
      +   '<div class="pd-khd"><span class="pd-knm">'+esc(k.nama)+'</span><span class="pd-kwk">baru saja</span></div>'
      +   '<div class="pd-ktx" data-isi="'+esc(k.isi)+'">'+esc(k.isi).replace(/\\n/g,'<br>')+'</div>'
      +   '<div class="pd-kaksi"><button class="pd-edit">Edit</button><button class="pd-hapus">Hapus</button></div>'
      + '</div>';
    return div;
  }

  btn.addEventListener('click', function(){
    var isi = inp.value.trim();
    if (!isi) { window.uiToast('Komentar tidak boleh kosong.','info'); return; }
    btn.disabled = true;
    var fd = new FormData(); fd.append('pertanyaan_id', QID); fd.append('isi', isi);
    fetch(BASE+'kinerja/save_komentar', {method:'POST', body:fd, credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        btn.disabled = false;
        if (!d.status) { window.uiToast(d.msg,'err'); return; }
        var kosong = list.querySelector('.pd-kosong-k');
        if (kosong) kosong.remove();
        var baris = baruBaris(d.data);
        list.appendChild(baris);
        pasangAksi(baris);
        inp.value = ''; inp.style.height = 'auto';
        baris.scrollIntoView({behavior:'smooth', block:'nearest'});
      });
  });

  function pasangAksi(row){
    var ed = row.querySelector('.pd-edit');
    var hp = row.querySelector('.pd-hapus');
    var txEl = row.querySelector('.pd-ktx');
    var id = row.dataset.id;

    if (ed) ed.addEventListener('click', function(){
      if (row.querySelector('.pd-eform')) return;
      var asli = txEl.dataset.isi;
      txEl.style.display = 'none';
      row.querySelector('.pd-kaksi').style.display = 'none';
      var form = document.createElement('div');
      form.className = 'pd-eform';
      form.innerHTML = '<textarea rows="2"></textarea><div class="g"><button class="pd-esave">Simpan</button><button class="pd-ecancel">Batal</button></div>';
      txEl.insertAdjacentElement('afterend', form);
      var ta = form.querySelector('textarea');
      ta.value = asli; ta.focus();

      function selesai(){ form.remove(); txEl.style.display=''; row.querySelector('.pd-kaksi').style.display=''; }
      form.querySelector('.pd-ecancel').addEventListener('click', selesai);
      form.querySelector('.pd-esave').addEventListener('click', function(){
        var baru = ta.value.trim();
        if (!baru) { window.uiToast('Komentar tidak boleh kosong.','info'); return; }
        var fd = new FormData(); fd.append('id', id); fd.append('isi', baru);
        fetch(BASE+'kinerja/edit_komentar', {method:'POST', body:fd, credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(d){
            if (!d.status) { window.uiToast(d.msg,'err'); return; }
            txEl.dataset.isi = baru;
            txEl.innerHTML = esc(baru).replace(/\\n/g,'<br>');
            if (d.edited && !row.querySelector('.pd-kedited')) {
              row.querySelector('.pd-khd').insertAdjacentHTML('beforeend', '<span class="pd-kedited">diedit</span>');
            }
            selesai();
          });
      });
    });

    if (hp) hp.addEventListener('click', function(){
      window.uiConfirm('Hapus komentar ini?', 'Komentar akan dihapus permanen.', function(){
        var fd = new FormData(); fd.append('id', id);
        fetch(BASE+'kinerja/delete_komentar', {method:'POST', body:fd, credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(d){
            if (!d.status) { window.uiToast(d.msg,'err'); return; }
            row.classList.add('pd-hilang');
            setTimeout(function(){
              row.remove();
              if (!list.querySelector('.pd-krow')) list.innerHTML = '<div class="pd-kosong-k">Belum ada komentar.</div>';
            }, 380);
          });
      });
    });
  }

  list.querySelectorAll('.pd-krow').forEach(pasangAksi);
})();
</script>
