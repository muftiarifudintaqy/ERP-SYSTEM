<style>
.fl-card{background:#fff;border:1px solid #f0f0f0;border-radius:2px;box-shadow:0 2px 8px rgba(0,0,0,.09);margin-bottom:16px}
.fl-head{padding:16px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center}
.fl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px;padding:16px}
.fl-item{border:1px solid #f0f0f0;border-radius:10px;overflow:hidden;background:#fafafa;text-align:center;transition:.18s}
.fl-item:hover{box-shadow:0 6px 18px rgba(0,0,0,.1)}
.fl-foto{width:100%;height:160px;object-fit:cover;background:#e2e8f0;display:block}
.fl-noimg{width:100%;height:160px;display:flex;align-items:center;justify-content:center;background:#f1f5f9;color:#94a3b8;font-size:2rem}
.fl-body{padding:10px 12px}
.fl-nm{font-size:.86rem;font-weight:600;color:#1e293b;line-height:1.3}
.fl-sub{font-size:.72rem;color:#94a3b8;margin-top:2px}
.fl-del{margin-top:8px;border:1px solid #ffccc7;background:#fff2f0;color:#ff4d4f;border-radius:6px;padding:4px 12px;font-size:.74rem;cursor:pointer;width:100%}
.fl-del:hover{background:#ffe1de}
.fl-belum{padding:0 16px 16px}
.fl-chip{display:inline-block;background:#fff7e6;border:1px solid #ffd591;color:#d46b08;border-radius:20px;padding:3px 12px;font-size:.76rem;margin:0 6px 6px 0}
</style>

<div class="container-fluid py-3">
  <div class="fl-card">
    <div class="fl-head">
      <h5 class="mb-0" style="color:rgba(0,0,0,.85)">Daftar Wajah Karyawan</h5>
      <span class="badge" style="background:#e6f7ff;color:#1890ff;border:1px solid #91d5ff"><?= count($baris) ?> terdaftar</span>
    </div>

    <?php if (empty($baris)): ?>
      <div class="text-center text-muted py-5">Belum ada karyawan yang mendaftarkan wajah.</div>
    <?php else: ?>
      <div class="fl-grid">
        <?php foreach ($baris as $b): ?>
          <div class="fl-item" data-uid="<?= $b['user_id'] ?>">
            <?php if (!empty($b['photo_path'])): ?>
              <img src="<?= base_url() . $b['photo_path'] ?>" class="fl-foto" alt="" data-nm="<?= htmlspecialchars($b['full_name'] ?? '') ?>" style="cursor:zoom-in" title="Klik untuk memperbesar">
            <?php else: ?>
              <div class="fl-noimg"><i class="bi bi-person-bounding-box"></i></div>
            <?php endif; ?>
            <div class="fl-body">
              <div class="fl-nm"><?= htmlspecialchars($b['full_name'] ?: 'Tidak diketahui') ?></div>
              <div class="fl-sub"><?= htmlspecialchars($b['department_name'] ?: ($b['position_name'] ?: '-')) ?></div>
              <div class="fl-sub">Didaftarkan <?= !empty($b['created_at']) ? date('j M Y', strtotime($b['created_at'])) : '-' ?></div>
              <button class="fl-del" data-uid="<?= $b['user_id'] ?>" data-nm="<?= htmlspecialchars($b['full_name']) ?>">
                <i class="bi bi-trash3 me-1"></i>Hapus / Reset
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($belum)): ?>
      <div class="fl-belum">
        <div class="fl-sub mb-2" style="font-size:.78rem;color:#64748b">Belum mendaftarkan wajah (<?= count($belum) ?>):</div>
        <?php foreach ($belum as $u): ?>
          <span class="fl-chip"><?= htmlspecialchars($u['full_name']) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
(function(){
  var BASE = '<?= base_url() ?>';
  document.querySelectorAll('.fl-del').forEach(function(b){
    b.addEventListener('click', function(){
      var uid = this.dataset.uid, nm = this.dataset.nm;
      Swal.fire({
        icon:'warning', title:'Hapus data wajah?',
        html:'Wajah <b>'+nm+'</b> akan dihapus. Dia harus daftar ulang untuk bisa absen dari HP.',
        showCancelButton:true, confirmButtonText:'Hapus', cancelButtonText:'Batal', confirmButtonColor:'#ff4d4f'
      }).then(function(r){
        if (!r.isConfirmed) return;
        var fd = new FormData(); fd.append('user_id', uid);
        fetch(BASE+'attendance/delete_face', {method:'POST', body:fd, credentials:'same-origin'})
          .then(function(x){return x.json();})
          .then(function(d){
            if (d.success) { Swal.fire('Berhasil', d.message, 'success').then(function(){ location.reload(); }); }
            else { Swal.fire('Gagal', d.message, 'error'); }
          });
      });
    });
  });
})();
</script>

<style>
/* Pembesar foto wajah. Klik untuk membuka, gulir atau cubit untuk
   memperbesar, klik latar atau tekan Escape untuk menutup. */
#fl-lightbox{position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:9999;
  display:none;align-items:center;justify-content:center;overflow:hidden}
#fl-lightbox.on{display:flex}
#fl-lb-img{max-width:92vw;max-height:88vh;transform-origin:center center;
  transition:transform .15s ease-out;cursor:grab;user-select:none}
#fl-lb-img.geser{cursor:grabbing;transition:none}
#fl-lb-info{position:absolute;bottom:18px;left:0;right:0;text-align:center;
  color:#fff;font-size:14px;font-weight:600;text-shadow:0 1px 4px rgba(0,0,0,.8)}
#fl-lb-tutup{position:absolute;top:14px;right:18px;background:none;border:0;
  color:#fff;font-size:34px;line-height:1;cursor:pointer;opacity:.8}
#fl-lb-tutup:hover{opacity:1}
#fl-lb-bantu{position:absolute;top:18px;left:18px;color:#fff;opacity:.55;
  font-size:12px}
</style>

<div id="fl-lightbox">
  <button id="fl-lb-tutup" type="button">&times;</button>
  <div id="fl-lb-bantu">Gulir untuk memperbesar &middot; seret untuk menggeser</div>
  <img id="fl-lb-img" src="" alt="">
  <div id="fl-lb-info"></div>
</div>

<script>
(function () {
  var lb   = document.getElementById('fl-lightbox');
  var img  = document.getElementById('fl-lb-img');
  var info = document.getElementById('fl-lb-info');
  var skala = 1, geserX = 0, geserY = 0, seret = false, awalX = 0, awalY = 0;

  function terapkan() {
    img.style.transform = 'translate(' + geserX + 'px,' + geserY + 'px) scale(' + skala + ')';
  }
  function buka(src, nama) {
    img.src = src; info.textContent = nama || '';
    skala = 1; geserX = 0; geserY = 0; terapkan();
    lb.classList.add('on');
    document.body.style.overflow = 'hidden';
  }
  function tutup() {
    lb.classList.remove('on');
    document.body.style.overflow = '';
    setTimeout(function(){ img.src = ''; }, 150);
  }

  document.querySelectorAll('.fl-foto').forEach(function (f) {
    f.addEventListener('click', function () { buka(this.src, this.dataset.nm); });
  });

  document.getElementById('fl-lb-tutup').addEventListener('click', tutup);
  lb.addEventListener('click', function (e) { if (e.target === lb) tutup(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && lb.classList.contains('on')) tutup();
  });

  // gulir untuk memperbesar
  lb.addEventListener('wheel', function (e) {
    if (!lb.classList.contains('on')) return;
    e.preventDefault();
    skala += (e.deltaY < 0 ? 0.2 : -0.2);
    if (skala < 1)  { skala = 1; geserX = 0; geserY = 0; }
    if (skala > 6)  { skala = 6; }
    terapkan();
  }, { passive: false });

  // seret untuk menggeser saat diperbesar
  img.addEventListener('mousedown', function (e) {
    if (skala <= 1) return;
    seret = true; awalX = e.clientX - geserX; awalY = e.clientY - geserY;
    img.classList.add('geser'); e.preventDefault();
  });
  window.addEventListener('mousemove', function (e) {
    if (!seret) return;
    geserX = e.clientX - awalX; geserY = e.clientY - awalY; terapkan();
  });
  window.addEventListener('mouseup', function () {
    seret = false; img.classList.remove('geser');
  });

  // klik dua kali untuk mengembalikan ukuran
  img.addEventListener('dblclick', function () {
    skala = 1; geserX = 0; geserY = 0; terapkan();
  });
})();
</script>
