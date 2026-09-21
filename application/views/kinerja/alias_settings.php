<style>
.as-wrap{max-width:640px}
.as-back{display:inline-flex;align-items:center;gap:6px;font-size:.82rem;color:#64748b;text-decoration:none;margin-bottom:14px}
.as-back:hover{color:#6E4FA8}
.as-card{background:#fff;border:1px solid #e5e8f0;border-radius:12px;padding:16px 18px;margin-bottom:10px;display:flex;align-items:center;gap:14px}
.as-av{width:38px;height:38px;border-radius:50%;background:#6E4FA8;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex:0 0 auto}
.as-info{flex:0 0 180px}
.as-nm{font-size:.86rem;font-weight:600;color:#1e293b}
.as-rl{font-size:.72rem;color:#94a3b8;text-transform:uppercase}
.as-inp{flex:1}
.as-save{flex:0 0 auto;min-width:92px;transition:.2s}
.as-save.ok{background:#16a34a!important;border-color:#16a34a!important}
.as-save i{display:none}
.as-save.ok i{display:inline-block;animation:asPop .35s ease}
@keyframes asPop{from{transform:scale(0)}to{transform:scale(1)}}
</style>
<div class="as-wrap">
  <?php if (!empty($back_team)): ?>
    <a href="<?= base_url() ?>kinerja/chat?team=<?= intval($back_team) ?>" class="as-back"><i class="bi bi-arrow-left"></i> Kembali ke Chat</a>
  <?php else: ?>
    <a href="<?= base_url() ?>kinerja" class="as-back"><i class="bi bi-arrow-left"></i> Kembali</a>
  <?php endif; ?>
  <p class="text-muted small mb-3">Nama di bawah dipakai menggantikan nama asli developer/super admin — <b>hanya untuk orang lain</b>. Kamu sendiri tetap melihat nama aslimu saat login dengan akun itu. Kosongkan untuk memakai "Admin Tim".</p>
  <?php foreach ($baris as $b): ?>
    <div class="as-card">
      <div class="as-av"><?= strtoupper(substr($b['full_name'] ?: '?', 0, 1)) ?></div>
      <div class="as-info">
        <div class="as-nm"><?= htmlspecialchars($b['full_name']) ?></div>
        <div class="as-rl"><?= htmlspecialchars($b['role_name']) ?></div>
      </div>
      <input type="text" class="form-control form-control-sm as-inp" data-id="<?= $b['id'] ?>" placeholder="Admin Tim" value="<?= htmlspecialchars($b['alias_name'] ?: '') ?>">
      <button class="btn btn-primary btn-sm as-save" data-id="<?= $b['id'] ?>"><i class="bi bi-check-lg"></i><span class="txt">Simpan</span></button>
    </div>
  <?php endforeach; ?>
</div>
<script>
(function(){
  var BASE = '<?= base_url() ?>';
  document.querySelectorAll('.as-save').forEach(function(b){
    var lbl = b.querySelector('.txt');
    b.addEventListener('click', function(){
      var id = this.dataset.id;
      var inp = document.querySelector('.as-inp[data-id="'+id+'"]');
      var fd = new FormData(); fd.append('user_id', id); fd.append('alias_name', inp.value.trim());
      b.disabled = true;
      fetch(BASE+'kinerja/save_alias', {method:'POST', body:fd, credentials:'same-origin'})
        .then(function(r){return r.json();})
        .then(function(d){
          b.disabled = false;
          if (!d.status) { alert(d.msg); return; }
          var teksAsli = lbl.textContent;
          lbl.textContent = 'Tersimpan';
          b.classList.add('ok');
          setTimeout(function(){ lbl.textContent = teksAsli; b.classList.remove('ok'); }, 1400);
        });
    });
  });
})();
</script>

<style>
/* ===== KARTU ALIAS DI HP: bertumpuk, bukan berjajar ===== */
@media (max-width:767.98px){
  .as-wrap{padding:0 12px 24px!important;max-width:100%!important;overflow-x:hidden!important}

  .as-card{
    display:grid!important;
    grid-template-columns:auto 1fr!important;
    grid-template-areas:"av info" "inp inp" "btn btn"!important;
    gap:10px 12px!important;
    align-items:center!important;
    width:100%!important;
    max-width:100%!important;
    padding:14px!important;
    margin-bottom:12px!important;
    border-radius:14px!important;
  }
  .as-card .as-av{grid-area:av!important;flex:0 0 auto!important}
  .as-card .as-info{grid-area:info!important;min-width:0!important}
  .as-card .as-nm{font-size:.95rem!important;white-space:normal!important;word-break:break-word!important}
  .as-card .as-rl{font-size:.72rem!important}

  .as-card .as-inp{
    grid-area:inp!important;
    width:100%!important;
    max-width:100%!important;
    min-width:0!important;
    font-size:.9rem!important;
    padding:10px 12px!important;
    height:auto!important;
  }
  .as-card .as-save{
    grid-area:btn!important;
    width:100%!important;
    justify-content:center!important;
    padding:10px!important;
    font-size:.88rem!important;
    display:inline-flex!important;
    align-items:center!important;
    gap:6px!important;
  }
  .as-card .as-save .txt{display:inline!important}
}
</style>
