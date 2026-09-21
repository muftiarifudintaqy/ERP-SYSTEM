<?php $halaman='jadwal'; $this->load->view('kinerja/_tabs', array('teams'=>$teams,'team_id'=>$team_id,'halaman'=>$halaman)); ?>
<style>
.jd-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.jd-nav{display:flex;align-items:center;gap:10px}
.jd-nav a{width:34px;height:34px;border-radius:9px;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#475569;text-decoration:none;transition:.18s}
.jd-nav a:hover{border-color:#6E4FA8;color:#6E4FA8}
.jd-bln{font-size:1.12rem;font-weight:700;color:#1e293b;min-width:170px;text-align:center}
.jd-kal{background:#fff;border:1px solid #e5e8f0;border-radius:14px;overflow:hidden}
.jd-hd{display:grid;grid-template-columns:repeat(7,1fr);background:#f8fafc;border-bottom:1px solid #e5e8f0}
.jd-hd div{padding:11px 6px;text-align:center;font-size:.72rem;font-weight:600;color:#64748b;letter-spacing:.4px}
.jd-hd div:first-child,.jd-hd div:last-child{color:#ef4444}
.jd-grid{display:grid;grid-template-columns:repeat(7,1fr)}
.jd-sel{min-height:104px;border-right:1px solid #eef1f6;border-bottom:1px solid #eef1f6;padding:7px 8px;position:relative;transition:.15s}
.jd-sel:nth-child(7n){border-right:0}
.jd-sel:hover{background:#fafbfe}
.jd-sel.luar{background:#fcfcfd}
.jd-sel.luar .jd-tgl{color:#cbd5e1}
.jd-tgl{font-size:.82rem;font-weight:600;color:#334155;margin-bottom:5px;display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%}
.jd-sel.ini .jd-tgl{background:#6E4FA8;color:#fff}
.jd-sel.pekan .jd-tgl{color:#ef4444}
.jd-sel.libur{background:#fff5f5}
.jd-sel.libur .jd-tgl{color:#dc2626}
.jd-lb{font-size:.63rem;color:#dc2626;background:#fee2e2;border-radius:5px;padding:2px 6px;margin-bottom:3px;line-height:1.3;display:block}
.jd-ev{font-size:.64rem;border-radius:5px;padding:2px 6px;margin-bottom:3px;line-height:1.35;cursor:pointer;color:#fff;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.jd-tg{font-size:.63rem;background:#fef3c7;color:#92400e;border-radius:5px;padding:2px 6px;margin-bottom:3px;line-height:1.3;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.jd-tg.lewat{background:#fee2e2;color:#991b1b}
.jd-tg.kelar{background:#dcfce7;color:#166534}
.jd-daf{margin-top:22px}
.jd-hari{display:flex;gap:18px;padding:14px 0;border-bottom:1px solid #eef1f6}
.jd-hari .tg{flex:0 0 128px;font-size:.78rem;color:#64748b;font-weight:500;padding-top:3px}
.jd-kartu{flex:1;background:#f8fafc;border-radius:11px;padding:14px 16px;margin-bottom:8px}
.jd-kartu h6{font-size:.94rem;margin:0 0 5px;color:#1e293b}
.jd-jam{font-size:.78rem;color:#64748b}
.jd-ket{font-size:.82rem;color:#475569;margin-top:7px;white-space:pre-wrap;line-height:1.6}
.jd-kosong{text-align:center;padding:44px 20px;color:#94a3b8}
@media(max-width:767.98px){
  .jd-sel{min-height:74px;padding:5px}
  .jd-tgl{font-size:.74rem;width:22px;height:22px}
  .jd-ev,.jd-tg,.jd-lb{font-size:.56rem;padding:1px 4px}
  .jd-hari{flex-direction:column;gap:6px}
  .jd-hari .tg{flex:none}
}
</style>

<?php
  $awal    = $bulan . '-01';
  $ts      = strtotime($awal);
  $jmlHari = date('t', $ts);
  $mulai   = date('w', $ts);
  $prev    = date('Y-m', strtotime($awal . ' -1 month'));
  $next    = date('Y-m', strtotime($awal . ' +1 month'));
  $namaBln = array(1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
  $judulBln = $namaBln[(int)date('n', $ts)] . ' ' . date('Y', $ts);
  $hariIni = date('Y-m-d');

  $pLibur = array(); foreach ($libur as $l)   $pLibur[$l['tanggal']][] = $l;
  $pEvent = array(); foreach ($agenda as $e)  $pEvent[$e['tanggal']][] = $e;
  $pTugas = array(); foreach ($tenggat as $t) $pTugas[$t['due_date']][] = $t;
?>

<div class="jd-head">
  <div class="jd-nav">
    <a href="<?= base_url() ?>kinerja/jadwal?team=<?= $team_id ?>&bulan=<?= $prev ?>"><i class="bi bi-chevron-left"></i></a>
    <div class="jd-bln"><?= $judulBln ?></div>
    <a href="<?= base_url() ?>kinerja/jadwal?team=<?= $team_id ?>&bulan=<?= $next ?>"><i class="bi bi-chevron-right"></i></a>
    <a href="<?= base_url() ?>kinerja/jadwal?team=<?= $team_id ?>&bulan=<?= date('Y-m') ?>" style="width:auto;padding:0 12px;font-size:.8rem">Hari ini</a>
  </div>
  <?php if ($team_id): ?>
  <div class="d-flex gap-2">
    <button class="btn btn-primary btn-sm" id="btnAgenda"><i class="bi bi-plus-lg me-1"></i>Buat Jadwal</button>
    <?php if ($boleh): ?><button class="btn btn-outline-danger btn-sm" id="btnLibur"><i class="bi bi-calendar-x me-1"></i>Hari Libur</button><?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php if (!$team_id): ?>
  <div class="card"><div class="card-body jd-kosong">Belum ada tim.</div></div>
<?php else: ?>

<div class="jd-kal">
  <div class="jd-hd"><div>MIN</div><div>SEN</div><div>SEL</div><div>RAB</div><div>KAM</div><div>JUM</div><div>SAB</div></div>
  <div class="jd-grid">
    <?php
      for ($i = 0; $i < $mulai; $i++) echo '<div class="jd-sel luar"></div>';
      for ($d = 1; $d <= $jmlHari; $d++):
        $tgl = $bulan . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
        $dow = date('w', strtotime($tgl));
        $kel = 'jd-sel';
        if ($tgl === $hariIni) $kel .= ' ini';
        if ($dow == 0 || $dow == 6) $kel .= ' pekan';
        if (isset($pLibur[$tgl])) $kel .= ' libur';
    ?>
      <div class="<?= $kel ?>" data-tgl="<?= $tgl ?>">
        <div class="jd-tgl"><?= $d ?></div>
        <?php if (isset($pLibur[$tgl])) foreach ($pLibur[$tgl] as $x): ?>
          <span class="jd-lb" title="<?= htmlspecialchars($x['nama']) ?>"><?= htmlspecialchars($x['nama']) ?></span>
        <?php endforeach; ?>
        <?php if (isset($pEvent[$tgl])) foreach ($pEvent[$tgl] as $x): ?>
          <span class="jd-ev ev-klik" style="background:<?= $x['warna'] ?>"
                data-json='<?= htmlspecialchars(json_encode($x), ENT_QUOTES) ?>'
                title="<?= htmlspecialchars($x['title']) ?>">
            <?= $x['jam_mulai'] ? substr($x['jam_mulai'],0,5).' ' : '' ?><?= htmlspecialchars($x['title']) ?>
          </span>
        <?php endforeach; ?>
        <?php if (isset($pTugas[$tgl])) foreach ($pTugas[$tgl] as $x):
          $kt = 'jd-tg';
          if ($x['kind'] === 'done') $kt .= ' kelar';
          elseif ($tgl < $hariIni) $kt .= ' lewat'; ?>
          <span class="<?= $kt ?>" title="Tugas: <?= htmlspecialchars($x['title']) ?>"><i class="bi bi-flag-fill"></i> <?= htmlspecialchars($x['title']) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endfor;
      $sisa = (7 - (($mulai + $jmlHari) % 7)) % 7;
      for ($i = 0; $i < $sisa; $i++) echo '<div class="jd-sel luar"></div>';
    ?>
  </div>
</div>

<div class="jd-daf">
  <h6 class="mb-3" style="font-size:.86rem;color:#334155"><i class="bi bi-list-ul me-1"></i>Agenda bulan ini</h6>
  <?php if (empty($agenda)): ?>
    <div class="card"><div class="card-body jd-kosong">
      <i class="bi bi-calendar3" style="font-size:2.1rem;opacity:.35"></i>
      <p class="mt-3 mb-0">Belum ada kegiatan di bulan ini.</p></div></div>
  <?php else:
    $hariNm = array('Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu');
    foreach ($pEvent as $tg => $isi): ?>
    <div class="jd-hari">
      <div class="tg"><?= $hariNm[date('w', strtotime($tg))] ?>,<br><?= date('j', strtotime($tg)) ?> <?= $namaBln[(int)date('n', strtotime($tg))] ?></div>
      <div class="flex-grow-1">
        <?php foreach ($isi as $e): ?>
          <div class="jd-kartu" style="border-left:3px solid <?= $e['warna'] ?>">
            <div class="d-flex justify-content-between align-items-start">
              <h6><?= htmlspecialchars($e['title']) ?></h6>
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-link text-secondary p-0 ev-ubah" data-json='<?= htmlspecialchars(json_encode($e), ENT_QUOTES) ?>'><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-link text-danger p-0 ev-hapus" data-id="<?= $e['id'] ?>"><i class="bi bi-trash3"></i></button>
              </div>
            </div>
            <div class="jd-jam">
              <i class="bi bi-clock"></i>
              <?= $e['jam_mulai'] ? substr($e['jam_mulai'],0,5) : 'sepanjang hari' ?><?= $e['jam_selesai'] ? ' - '.substr($e['jam_selesai'],0,5) : '' ?>
              <?php if ($e['pembuat']): ?> &middot; <?= htmlspecialchars($e['pembuat']) ?><?php endif; ?>
            </div>
            <?php if ($e['description']): ?><div class="jd-ket"><?= nl2br(htmlspecialchars($e['description'])) ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<div class="modal fade" id="mdAgenda" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Jadwal Kegiatan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" id="evId">
    <div class="mb-3"><label class="form-label">Judul kegiatan *</label>
      <input type="text" class="form-control" id="evTitle" placeholder="mis. Rapat mingguan tim"></div>
    <div class="row g-2">
      <div class="col-12 mb-2"><label class="form-label">Tanggal *</label><input type="date" class="form-control" id="evTgl"></div>
      <div class="col-6 mb-2"><label class="form-label">Jam mulai</label><input type="time" class="form-control" id="evJ1"></div>
      <div class="col-6 mb-2"><label class="form-label">Jam selesai</label><input type="time" class="form-control" id="evJ2"></div>
    </div>
    <div class="mb-3"><label class="form-label">Catatan</label><textarea class="form-control" id="evDesc" rows="3"></textarea></div>
    <div class="mb-2"><label class="form-label">Warna</label>
      <select class="form-select" id="evWarna">
        <option value="#6E4FA8">Ungu</option><option value="#3b82f6">Biru</option>
        <option value="#22c55e">Hijau</option><option value="#f0b429">Kuning</option>
        <option value="#ef4444">Merah</option><option value="#64748b">Abu</option>
      </select></div>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button class="btn btn-primary" id="btnSimpanEv">Simpan</button></div>
</div></div></div>

<?php if ($boleh): ?>
<div class="modal fade" id="mdLibur" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Tambah Hari Libur</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="alert alert-warning py-2" style="font-size:.8rem">
      Libur nasional dasar sudah terisi otomatis. Tambahkan sendiri untuk libur keagamaan (Idul Fitri, Nyepi, Imlek) dan cuti bersama sesuai SKB terbaru.
    </div>
    <div class="mb-3"><label class="form-label">Tanggal *</label><input type="date" class="form-control" id="lbTgl"></div>
    <div class="mb-3"><label class="form-label">Nama hari libur *</label><input type="text" class="form-control" id="lbNama" placeholder="mis. Idul Fitri 1447 H"></div>
    <div class="mb-2"><label class="form-label">Jenis</label>
      <select class="form-select" id="lbJenis">
        <option value="nasional">Libur Nasional</option>
        <option value="cuti_bersama">Cuti Bersama</option>
        <option value="perusahaan">Libur Perusahaan</option>
      </select></div>
    <?php if (!empty($libur)): ?>
      <hr><label class="form-label">Libur bulan ini</label>
      <?php foreach ($libur as $l): ?>
        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
          <span style="font-size:.82rem"><?= date('j M', strtotime($l['tanggal'])) ?> &middot; <?= htmlspecialchars($l['nama']) ?></span>
          <button class="btn btn-sm btn-link text-danger p-0 lb-hapus" data-id="<?= $l['id'] ?>"><i class="bi bi-trash3"></i></button>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
    <button class="btn btn-danger" id="btnSimpanLb">Tambah</button></div>
</div></div></div>
<?php endif; ?>

<?php endif; ?>

<script>
(function(){
  var BASE = '<?= base_url() ?>', TEAM = <?= intval($team_id) ?>;

  function jdGaya(){
    if (document.getElementById('jdGayaTag')) return;
    var st = document.createElement('style');
    st.id = 'jdGayaTag';
    st.textContent =
      '.jdOv{position:fixed;inset:0;z-index:30000;display:flex;align-items:center;justify-content:center;background:rgba(15,18,28,.5);backdrop-filter:blur(3px);opacity:0;transition:opacity .2s ease}'
    + '.jdOv.on{opacity:1}'
    + '.jdCf{background:#fff;border-radius:18px;padding:30px 28px 22px;width:352px;max-width:90vw;text-align:center;box-shadow:0 28px 70px rgba(0,0,0,.3);transform:translateY(18px) scale(.92);transition:transform .26s cubic-bezier(.18,.9,.32,1.28)}'
    + '.jdOv.on .jdCf{transform:none}'
    + '.jdIc{width:62px;height:62px;border-radius:50%;background:#fee2e2;color:#ef4444;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.7rem;animation:jdSh .5s ease}'
    + '@keyframes jdSh{0%,100%{transform:rotate(0)}25%{transform:rotate(-9deg)}75%{transform:rotate(9deg)}}'
    + '.jdT{font-size:1.02rem;font-weight:600;color:#1e293b;margin-bottom:7px}'
    + '.jdS{font-size:.82rem;color:#94a3b8;margin-bottom:22px;line-height:1.6}'
    + '.jdB{display:flex;gap:10px}'
    + '.jdB button{flex:1;border-radius:10px;padding:11px 0;font-size:.87rem;font-weight:500;border:0;cursor:pointer;transition:.18s}'
    + '.jdNo{background:#f1f5f9;color:#334155}.jdNo:hover{background:#e2e8f0}'
    + '.jdYa{background:#ef4444;color:#fff}.jdYa:hover{background:#dc2626}';
    document.head.appendChild(st);
  }

  function jdTanya(judul, sub, cb){
    jdGaya();
    var ov = document.createElement('div');
    ov.className = 'jdOv';
    ov.innerHTML = '<div class="jdCf"><div class="jdIc"><i class="bi bi-trash3"></i></div>'
      + '<div class="jdT"></div><div class="jdS"></div>'
      + '<div class="jdB"><button class="jdNo">Batal</button><button class="jdYa">Ya, hapus</button></div></div>';
    ov.querySelector('.jdT').textContent = judul;
    ov.querySelector('.jdS').textContent = sub;
    document.body.appendChild(ov);
    requestAnimationFrame(function(){ ov.classList.add('on'); });

    function buang(){ ov.classList.remove('on'); setTimeout(function(){ if(ov.parentNode) ov.parentNode.removeChild(ov); }, 280); }
    ov.querySelector('.jdYa').addEventListener('click', function(){ buang(); cb(); });
    ov.querySelector('.jdNo').addEventListener('click', buang);
    ov.addEventListener('click', function(e){ if (e.target === ov) buang(); });
    document.addEventListener('keydown', function esc(e){ if (e.key === 'Escape'){ buang(); document.removeEventListener('keydown', esc); } });
  }
  var mdEv = document.getElementById('mdAgenda') ? new bootstrap.Modal(document.getElementById('mdAgenda')) : null;
  var mdLb = document.getElementById('mdLibur') ? new bootstrap.Modal(document.getElementById('mdLibur')) : null;
  if (!mdEv) return;

  function bukaEv(d, tgl){
    document.getElementById('evId').value    = d ? d.id : '';
    document.getElementById('evTitle').value = d ? (d.title||'') : '';
    document.getElementById('evTgl').value   = d ? (d.tanggal||'') : (tgl || '');
    document.getElementById('evJ1').value    = d && d.jam_mulai ? d.jam_mulai.substring(0,5) : '';
    document.getElementById('evJ2').value    = d && d.jam_selesai ? d.jam_selesai.substring(0,5) : '';
    document.getElementById('evDesc').value  = d ? (d.description||'') : '';
    document.getElementById('evWarna').value = d ? (d.warna||'#6E4FA8') : '#6E4FA8';
    mdEv.show();
  }

  var bA = document.getElementById('btnAgenda');
  if (bA) bA.addEventListener('click', function(){ bukaEv(null, ''); });

  document.querySelectorAll('.jd-sel[data-tgl]').forEach(function(sel){
    sel.addEventListener('dblclick', function(){ bukaEv(null, this.dataset.tgl); });
  });
  document.querySelectorAll('.ev-klik, .ev-ubah').forEach(function(x){
    x.addEventListener('click', function(e){ e.stopPropagation(); bukaEv(JSON.parse(this.dataset.json||'{}'), ''); });
  });

  document.getElementById('btnSimpanEv').addEventListener('click', function(){
    var j = document.getElementById('evTitle').value.trim();
    var t = document.getElementById('evTgl').value;
    if (!j || !t) { alert('Judul dan tanggal wajib diisi.'); return; }
    if (this.disabled) return;
    var btn = this;
    var teksAsli = btn.textContent;
    var fd = new FormData();
    fd.append('id', document.getElementById('evId').value);
    fd.append('team_id', TEAM);
    fd.append('title', j);
    fd.append('tanggal', t);
    fd.append('jam_mulai', document.getElementById('evJ1').value);
    fd.append('jam_selesai', document.getElementById('evJ2').value);
    fd.append('description', document.getElementById('evDesc').value);
    fd.append('warna', document.getElementById('evWarna').value);
    btn.disabled = true; btn.textContent = 'Menyimpan...';
    fetch(BASE+'kinerja/save_event',{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ if(d.status) location.reload(); else { alert(d.msg); btn.disabled=false; btn.textContent=teksAsli; } })
      .catch(function(){ alert('Gagal menghubungi server.'); btn.disabled=false; btn.textContent=teksAsli; });
  });

  document.querySelectorAll('.ev-hapus').forEach(function(x){
    x.addEventListener('click', function(e){
      e.stopPropagation();
      var idev = this.dataset.id;
      jdTanya('Hapus kegiatan ini?', 'Jadwal akan hilang dari kalender tim.', function(){
        var fd=new FormData(); fd.append('id', idev);
        fetch(BASE+'kinerja/delete_event',{method:'POST',body:fd,credentials:'same-origin'})
          .then(function(r){return r.json();}).then(function(d){ if(d.status) location.reload(); else alert(d.msg); });
      });
    });
  });

  var bL = document.getElementById('btnLibur');
  if (bL) bL.addEventListener('click', function(){ mdLb.show(); });

  var bSL = document.getElementById('btnSimpanLb');
  if (bSL) bSL.addEventListener('click', function(){
    var t = document.getElementById('lbTgl').value, n = document.getElementById('lbNama').value.trim();
    if (!t || !n) { alert('Tanggal dan nama wajib diisi.'); return; }
    if (bSL.disabled) return;
    var teksAsli = bSL.textContent;
    var fd = new FormData();
    fd.append('tanggal', t); fd.append('nama', n);
    fd.append('jenis', document.getElementById('lbJenis').value);
    bSL.disabled = true; bSL.textContent = 'Menyimpan...';
    fetch(BASE+'kinerja/save_libur',{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ if(d.status) location.reload(); else { alert(d.msg); bSL.disabled=false; bSL.textContent=teksAsli; } })
      .catch(function(){ alert('Gagal menghubungi server.'); bSL.disabled=false; bSL.textContent=teksAsli; });
  });

  document.querySelectorAll('.lb-hapus').forEach(function(x){
    x.addEventListener('click', function(){
      var idlb = this.dataset.id;
      jdTanya('Hapus hari libur ini?', 'Tanggal ini akan kembali dihitung sebagai hari kerja.', function(){
        var fd=new FormData(); fd.append('id', idlb);
        fetch(BASE+'kinerja/delete_libur',{method:'POST',body:fd,credentials:'same-origin'})
          .then(function(r){return r.json();}).then(function(d){ if(d.status) location.reload(); else alert(d.msg); });
      });
    });
  });
})();
</script>
