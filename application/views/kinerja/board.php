<?php $halaman='tugas'; $this->load->view('kinerja/_tabs', array('teams'=>$teams,'team_id'=>$team_id,'halaman'=>$halaman)); ?>
<style>
.ck-row{display:flex;gap:10px;align-items:flex-start;padding:9px 11px;border:1px solid #e8ecf3;border-radius:9px;margin-bottom:7px;background:#fff;transition:.15s}
.ck-row:hover{border-color:#cbd5e1}
.ck-row.done{background:#f6fdf9;border-color:#c9ecd8}
.ck-it{font-size:.84rem;color:#1e293b;font-weight:500;line-height:1.4}
.ck-row.done .ck-it{color:#64748b;text-decoration:line-through}
.ck-ket{font-size:.78rem;color:#64748b;line-height:1.6;margin-top:3px;white-space:pre-wrap}
.ck-akt{display:flex;gap:8px;opacity:0;transition:.18s}
.ck-row:hover .ck-akt{opacity:1}
.ck-akt button{font-size:.9rem;line-height:1}
.ck-wk{font-size:.68rem;color:#16a34a;margin-top:5px}
#tgDrive{font-size:.92rem !important;padding:11px 13px !important;border:1.5px solid #cbd5e1 !important;border-radius:9px !important;box-shadow:0 2px 6px rgba(0,0,0,.07) !important;min-height:46px}
#tgDrive:focus{border-color:#6E4FA8 !important;box-shadow:0 0 0 3px rgba(110,79,168,.15) !important}
.md-kanan .form-label{font-size:.76rem !important;font-weight:600 !important;color:#475569 !important}
.md-cover{width:100%;height:170px;object-fit:cover;display:none;background:#0f172a}
.md-body{display:flex;gap:0;align-items:stretch}
.md-kiri{flex:1 1 auto;padding:20px 22px;min-width:0;max-height:70vh;overflow-y:auto}
.md-kanan{flex:0 0 210px;background:#f8fafc;border-left:1px solid #e5e8f0;padding:18px 16px;max-height:70vh;overflow-y:auto}
.md-kanan h6{font-size:.66rem;letter-spacing:.9px;text-transform:uppercase;color:#94a3b8;margin:0 0 9px;font-weight:600}
.md-kanan .blok{margin-bottom:18px}
.md-kanan .form-label{font-size:.72rem;color:#64748b;margin-bottom:3px}
.md-kanan .form-control,.md-kanan .form-select{font-size:.8rem}
.md-sub{font-size:.76rem;color:#94a3b8;margin-bottom:14px}
.md-sec{font-size:.8rem;font-weight:600;color:#334155;margin:16px 0 8px;display:flex;align-items:center;justify-content:space-between}
@media(max-width:767.98px){
  .md-body{flex-direction:column}
  .md-kanan{flex:1 1 auto;border-left:0;border-top:1px solid #e5e8f0;max-height:none}
  .md-kiri{max-height:none;padding:16px}
}
.bk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(104px,1fr));gap:9px;margin-bottom:10px}
.bk-item{position:relative;border:1px solid #e5e8f0;border-radius:9px;overflow:hidden;background:#f8fafc}
.bk-item img,.bk-item video{width:100%;height:96px;object-fit:cover;display:block;background:#0f172a}
.bk-item .bk-box{height:96px;display:flex;align-items:center;justify-content:center}
.bk-item .bk-box i{font-size:2rem;color:#94a3b8}
.bk-item .bk-nm{font-size:.62rem;padding:5px 6px;color:#475569;line-height:1.3;word-break:break-word;background:#fff}
.bk-item .bk-x{position:absolute;top:4px;right:4px;background:rgba(255,255,255,.94);border:0;border-radius:50%;width:20px;height:20px;line-height:1;color:#ef4444;cursor:pointer;font-size:.85rem;padding:0;opacity:0;transition:.2s}
.bk-item:hover .bk-x{opacity:1}
.bk-item .bk-play{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:1.7rem;pointer-events:none;text-shadow:0 2px 8px rgba(0,0,0,.6)}
.kb-wrap{padding:4px 0 24px}
.kb-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.kb-tabs{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px}
.kb-tab{white-space:nowrap;padding:8px 16px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#475569;font-size:.85rem;text-decoration:none;transition:.2s}
.kb-tab:hover{border-color:#6E4FA8;color:#6E4FA8}
.kb-tab.on{background:#6E4FA8;border-color:#6E4FA8;color:#fff;font-weight:600}
.btn-edit-tim{position:absolute;right:-6px;top:-6px;width:19px;height:19px;border-radius:50%;background:#fff;border:1px solid #e2e8f0;color:#94a3b8;font-size:.55rem;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0;transition:.15s;padding:0}
div:hover > .btn-edit-tim{opacity:1}
.btn-edit-tim:hover{background:#6E4FA8;color:#fff;border-color:#6E4FA8}
.kb-board{display:flex;gap:14px;overflow-x:auto;padding-bottom:12px;align-items:flex-start}
.kb-col{flex:0 0 290px;background:#f1f5f9;border-radius:14px;padding:12px;min-height:120px}
.kb-col-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;padding:0 4px}
.kb-col-hd .nm{display:flex;align-items:center;gap:8px;font-weight:600;font-size:.85rem;color:#334155}
.kb-dot{width:10px;height:10px;border-radius:50%}
.kb-cnt{background:#e2e8f0;color:#475569;font-size:.72rem;padding:1px 8px;border-radius:99px}
.kb-card{background:#fff;border-radius:10px;padding:12px;margin-bottom:9px;box-shadow:0 1px 3px rgba(0,0,0,.08);cursor:grab;border-left:3px solid transparent;transition:.18s}
.kb-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.12);transform:translateY(-1px)}
.kb-card.drag{opacity:.45}
.kb-card{padding:0 !important;overflow:hidden;border-left:3px solid transparent}
.kb-badge{display:inline-flex;align-items:center;gap:4px;font-size:.63rem;font-weight:600;padding:3px 9px;border-radius:99px;letter-spacing:.2px;margin-bottom:7px}

/* To Do — dijadwalkan */
.kb-col[data-kind="todo"] .kb-card{border-left-color:#94a3b8}
.kb-col[data-kind="todo"] .kb-badge{background:#eef2f7;color:#64748b}

/* Dikerjakan — sedang berjalan */
.kb-col[data-kind="doing"] .kb-card{border-left-color:#3b82f6;background:linear-gradient(180deg,#f8fbff,#fff)}
.kb-col[data-kind="doing"] .kb-badge{background:#dbeafe;color:#1d4ed8}
.kb-col[data-kind="doing"] .kb-badge .dot{width:6px;height:6px;border-radius:50%;background:#3b82f6;animation:kbNyala 1.4s ease-in-out infinite}
@keyframes kbNyala{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.35;transform:scale(.7)}}

/* Selesai — tercapai */
.kb-col[data-kind="done"] .kb-card{border-left-color:#22c55e;background:linear-gradient(180deg,#f4fdf7,#fff)}
.kb-col[data-kind="done"] .kb-badge{background:#dcfce7;color:#15803d}
.kb-col[data-kind="done"] .kb-card::after{content:'';position:absolute;top:0;right:0;width:0;height:0;border-style:solid;border-width:0 30px 30px 0;border-color:transparent #22c55e transparent transparent}
.kb-col[data-kind="done"] .kb-card{position:relative}
.kb-col[data-kind="done"] .kb-pin{position:absolute;top:3px;right:3px;color:#fff;font-size:.62rem;z-index:2}
.kb-col[data-kind="done"] .kb-card .t{color:#475569}

/* Batal — dibatalkan */
.kb-col[data-kind="cancel"] .kb-card{border-left-color:#ef4444;background:#fff8f8;opacity:.82}
.kb-col[data-kind="cancel"] .kb-badge{background:#fee2e2;color:#b91c1c}
.kb-col[data-kind="cancel"] .kb-card .t{text-decoration:line-through;color:#94a3b8}
.kb-col[data-kind="cancel"] .kb-cover{filter:grayscale(.85)}

/* perayaan saat tugas dipindah ke Selesai */
#kbRaya{position:fixed;inset:0;z-index:31000;display:flex;align-items:center;justify-content:center;pointer-events:none}
.kbRayaKotak{background:#fff;border-radius:20px;padding:34px 40px;text-align:center;box-shadow:0 30px 80px rgba(0,0,0,.28);transform:scale(.6);opacity:0;animation:kbMuncul .5s cubic-bezier(.18,.9,.32,1.4) forwards}
@keyframes kbMuncul{to{transform:scale(1);opacity:1}}
.kbRayaIc{width:76px;height:76px;border-radius:50%;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;font-size:2.3rem;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 10px 28px rgba(34,197,94,.45)}
.kbRayaIc i{animation:kbCentang .55s .18s cubic-bezier(.18,.9,.32,1.6) both}
@keyframes kbCentang{from{transform:scale(0) rotate(-40deg)}to{transform:scale(1) rotate(0)}}
.kbRayaT{font-size:1.12rem;font-weight:700;color:#15803d;margin-bottom:5px}
.kbRayaS{font-size:.85rem;color:#64748b}
.kbLetup{position:absolute;width:9px;height:9px;border-radius:2px;animation:kbLetup 1.1s ease-out forwards}
@keyframes kbLetup{0%{transform:translate(0,0) rotate(0);opacity:1}100%{transform:translate(var(--x),var(--y)) rotate(540deg);opacity:0}}
.kb-cover{width:100%;height:112px;object-fit:cover;display:block;background:#e2e8f0}
.kb-isi{padding:11px 12px}
.kb-card .t{font-size:.85rem;font-weight:500;color:#1e293b;line-height:1.4;margin-bottom:8px}
.kb-meta{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.kb-chip{font-size:.68rem;padding:2px 8px;border-radius:99px;background:#f1f5f9;color:#64748b}
.kb-chip.due{background:#fef3c7;color:#92400e}
.kb-chip.late{background:#fee2e2;color:#991b1b}
.kb-chip.ok{background:#dcfce7;color:#166534}
.kb-av{width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,#6E4FA8,#4aa8ff);color:#fff;font-size:.62rem;display:inline-flex;align-items:center;justify-content:center;font-weight:700}
.kb-add{width:100%;border:1px dashed #cbd5e1;background:transparent;color:#64748b;border-radius:9px;padding:9px;font-size:.8rem;cursor:pointer;transition:.2s}
.kb-add:hover{border-color:#6E4FA8;color:#6E4FA8;background:#faf9fd}
.kb-col.over{background:#e9e4f5;outline:2px dashed #6E4FA8}
.kb-empty{text-align:center;padding:60px 20px;color:#94a3b8}
@media(max-width:767.98px){.kb-col{flex:0 0 82vw}.kb-head{flex-direction:column;align-items:stretch}}
</style>

<div class="kb-wrap">
  <div class="kb-head">
    <div class="kb-tabs">
      <?php foreach ($teams as $t): ?>
        <div style="position:relative;display:inline-flex;align-items:center">
          <a class="kb-tab <?= $t['id']==$team_id ? 'on':'' ?>" href="<?= base_url() ?>kinerja?team=<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></a>
          <?php if ($boleh): ?>
            <button class="btn-edit-tim" data-id="<?= $t['id'] ?>" data-name="<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>" data-desc="<?= htmlspecialchars($t['description'], ENT_QUOTES) ?>" title="Edit tim"><i class="bi bi-pencil-fill"></i></button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if ($boleh): ?>
        <button class="kb-tab" data-bs-toggle="modal" data-bs-target="#mdTim" style="cursor:pointer">+ Tim Baru</button>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$team_id): ?>
    <div class="card"><div class="card-body kb-empty">
      <i class="bi bi-kanban" style="font-size:2.4rem;opacity:.4"></i>
      <p class="mt-3 mb-0">Belum ada tim. <?= $boleh ? 'Buat tim baru untuk mulai.' : 'Minta admin memasukkan kamu ke sebuah tim.' ?></p>
    </div></div>
  <?php else: ?>
    <div class="kb-board" id="kbBoard">
      <?php foreach ($lists as $l):
        $isi = array_values(array_filter($tasks, function($x) use ($l){ return $x['list_id']==$l['id']; })); ?>
        <div class="kb-col" data-list="<?= $l['id'] ?>" data-kind="<?= $l['kind'] ?>">
          <div class="kb-col-hd">
            <div class="nm"><span class="kb-dot" style="background:<?= $l['color'] ?>"></span><?= htmlspecialchars($l['name']) ?></div>
            <span class="kb-cnt"><?= count($isi) ?></span>
          </div>
          <div class="kb-items">
            <?php foreach ($isi as $k):
              $late = (!empty($k['due_date']) && $k['due_date'] < date('Y-m-d') && $l['kind']!=='done');
              $ini  = $k['assignee_name'] ? strtoupper(substr(trim($k['assignee_name']),0,1)) : '?'; ?>
              <div class="kb-card" draggable="true" data-id="<?= $k['id'] ?>"
                   style="border-left-color:<?= $l['color'] ?>"
                   data-json='<?= htmlspecialchars(json_encode($k), ENT_QUOTES) ?>'>
                <?php if (!empty($k['cover_path'])): ?>
                  <img class="kb-cover" src="<?= base_url().$k['cover_path'] ?>" loading="lazy" alt="">
                <?php endif; ?>
                <?php if ($l['kind']=='done'): ?><i class="bi bi-pin-angle-fill kb-pin"></i><?php endif; ?>
                <div class="kb-isi">
                <?php
                  $lbl = array(
                    'todo'   => array('bi-calendar-check', 'Sedang dijadwalkan'),
                    'doing'  => array('', 'Sedang dikerjakan'),
                    'done'   => array('bi-trophy-fill', 'Selesai'),
                    'cancel' => array('bi-x-octagon-fill', 'Dibatalkan')
                  );
                  $st = isset($lbl[$l['kind']]) ? $lbl[$l['kind']] : array('bi-circle','-');
                ?>
                <span class="kb-badge">
                  <?php if ($l['kind']=='doing'): ?><span class="dot"></span><?php else: ?><i class="bi <?= $st[0] ?>"></i><?php endif; ?>
                  <?= $st[1] ?>
                </span>
                <div class="t"><?= htmlspecialchars($k['title']) ?></div>
                <div class="kb-meta">
                  <?php if ($k['assignee_name']): ?><span class="kb-av" title="<?= htmlspecialchars($k['assignee_name']) ?>"><?= $ini ?></span><?php endif; ?>
                  <?php if ($k['due_date']): ?>
                    <span class="kb-chip <?= $l['kind']==='done' ? 'ok' : ($late ? 'late':'due') ?>">
                      <i class="bi bi-clock"></i> <?= date('j M', strtotime($k['due_date'])) ?></span>
                  <?php endif; ?>
                  <?php if ($k['label']): ?><span class="kb-chip"><?= htmlspecialchars($k['label']) ?></span><?php endif; ?>
                  <?php if (!empty($k['drive_link'])): ?><a href="<?= htmlspecialchars($k['drive_link']) ?>" target="_blank" class="kb-chip" style="color:#2563eb" onclick="event.stopPropagation()"><i class="bi bi-link-45deg"></i> Drive</a><?php endif; ?>
                  <?php if (!empty($k['approval']) && $k['approval']=='disetujui'): ?><span class="kb-chip ok"><i class="bi bi-patch-check-fill"></i> ACC</span>
                  <?php elseif (!empty($k['approval']) && $k['approval']=='revisi'): ?><span class="kb-chip late"><i class="bi bi-arrow-counterclockwise"></i> Revisi</span><?php endif; ?>
                  <?php if (!empty($k['ck_total'])): ?>
                    <span class="kb-chip <?= ($k['ck_selesai']==$k['ck_total']) ? 'ok' : '' ?>"><i class="bi bi-check2-square"></i> <?= $k['ck_selesai'] ?>/<?= $k['ck_total'] ?></span>
                  <?php endif; ?>
                  <?php if (!empty($k['jml_bukti'])): ?>
                    <span class="kb-chip"><i class="bi bi-paperclip"></i> <?= $k['jml_bukti'] ?></span>
                  <?php endif; ?>
                </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <button class="kb-add" data-list="<?= $l['id'] ?>">+ Tambah tugas</button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- modal tugas -->
<div class="modal fade" id="mdTugas" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <img id="mdCover" class="md-cover" alt="">
      <div class="modal-header py-2">
        <h5 class="modal-title" id="mdTugasJudul">Tugas Baru</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="md-body">

          <div class="md-kiri">
            <input type="hidden" id="tgId"><input type="hidden" id="tgList">
            <input type="text" class="form-control form-control-lg border-0 px-0 fw-semibold" id="tgTitle"
                   placeholder="Nama tugas..." style="font-size:1.15rem">
            <div class="md-sub" id="mdLokasi"></div>

            <div class="md-sec"><span><i class="bi bi-text-left me-1"></i>Catatan</span></div>
            <textarea class="form-control" id="tgDesc" rows="3" placeholder="detail pekerjaan..."></textarea>

            <div id="areaDetail" style="display:none">
              <div class="md-sec">
                <span><i class="bi bi-check2-square me-1"></i>Rincian pekerjaan</span>
                <span class="badge bg-light text-secondary" id="ckProgres">0/0</span>
              </div>
              <div id="tgCeklis" class="mb-2"></div>
              <div class="row g-1 mb-3">
                <div class="col-12 col-md-5"><input type="text" class="form-control form-control-sm" id="ckBaru" placeholder="rincian, mis. Riset topik"></div>
                <div class="col-8 col-md-5"><input type="text" class="form-control form-control-sm" id="ckKet" placeholder="keterangan: sudah ngerjain apa..."></div>
                <div class="col-4 col-md-2"><button class="btn btn-outline-secondary btn-sm w-100" id="btnCkTambah">Tambah</button></div>
              </div>

              <div class="md-sec"><span><i class="bi bi-paperclip me-1"></i>Bukti pengerjaan</span></div>
              <div id="tgBukti" class="mb-2"></div>
              <div class="input-group input-group-sm">
                <input type="file" class="form-control" id="bkFile">
                <button class="btn btn-outline-primary" id="btnBkUpload">Unggah</button>
              </div>
              <small class="text-muted">Gambar, video, PDF, atau dokumen. Maksimal 100 MB. Untuk video panjang, gunakan link Drive.</small>
            </div>
          </div>

          <div class="md-kanan">
            <div class="blok">
              <h6>Kelola Tugas</h6>
              <label class="form-label">Penanggung jawab</label>
              <select class="form-select form-select-sm mb-2" id="tgAssignee"><option value="">— pilih —</option>
                <?php foreach ($anggota as $a): ?><option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['full_name']) ?></option><?php endforeach; ?>
              </select>
              <label class="form-label">Tenggat</label>
              <input type="date" class="form-control form-control-sm mb-2" id="tgDue">
              <label class="form-label">Label</label>
              <input type="text" class="form-control form-control-sm mb-2" id="tgLabel" placeholder="mis. Urgent">
              <label class="form-label">Link Google Drive</label>
              <input type="url" class="form-control form-control-sm" id="tgDrive" placeholder="https://drive.google.com/...">
            </div>

            <div class="blok" id="blokAcc" style="display:none">
              <h6>Persetujuan</h6>
              <div id="accStatus" class="mb-2"></div>
              <?php if ($boleh): ?>
                <input type="text" class="form-control form-control-sm mb-2" id="accNote" placeholder="catatan...">
                <button class="btn btn-success btn-sm w-100 mb-1" data-acc="disetujui"><i class="bi bi-check-lg"></i> Setujui</button>
                <button class="btn btn-warning btn-sm w-100 mb-1" data-acc="revisi"><i class="bi bi-arrow-counterclockwise"></i> Minta Revisi</button>
                <button class="btn btn-outline-secondary btn-sm w-100" data-acc="menunggu">Reset</button>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
      <div class="modal-footer py-2">
        <button class="btn btn-outline-danger btn-sm me-auto" id="btnHapusTugas" style="display:none"><i class="bi bi-trash"></i> Hapus</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" id="btnSimpanTugas">Simpan</button>
      </div>
    </div>
  </div>
</div>

<?php if ($boleh): ?>
<!-- modal tim -->
<div class="modal fade" id="mdTim" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="mdTimJudul">Tim Baru</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="tmId">
        <div class="mb-3"><label class="form-label">Nama tim *</label>
          <input type="text" class="form-control" id="tmName" placeholder="mis. Ads Team"></div>
        <div class="mb-3"><label class="form-label">Keterangan</label>
          <input type="text" class="form-control" id="tmDesc"></div>
        <div class="mb-2"><label class="form-label">Anggota</label>
          <select class="form-select" id="tmMembers" multiple size="7">
            <?php foreach ($semua_user as $u): ?><option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option><?php endforeach; ?>
          </select>
          <small class="text-muted">Tahan Ctrl / Cmd untuk memilih beberapa orang.</small></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-danger me-auto" id="btnHapusTim" style="display:none"><i class="bi bi-trash3"></i> Hapus Tim</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" id="btnSimpanTim">Buat Tim</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
(function(){
  var BASE = '<?= base_url() ?>';
  var TEAM = <?= intval($team_id) ?>;
  var mdTugas = document.getElementById('mdTugas') ? new bootstrap.Modal(document.getElementById('mdTugas')) : null;

  function buka(listId, data){
    document.getElementById('tgId').value       = data ? data.id : '';
    document.getElementById('tgList').value     = listId;
    document.getElementById('tgTitle').value    = data ? (data.title||'') : '';
    document.getElementById('tgDesc').value     = data ? (data.description||'') : '';
    document.getElementById('tgAssignee').value = data ? (data.assignee_id||'') : '';
    document.getElementById('tgDue').value      = data ? (data.due_date||'') : '';
    document.getElementById('tgLabel').value    = data ? (data.label||'') : '';
    document.getElementById('tgDrive').value    = data ? (data.drive_link||'') : '';
    document.getElementById('mdTugasJudul').textContent = data ? 'Ubah Tugas' : 'Tugas Baru';
    document.getElementById('btnHapusTugas').style.display = data ? 'inline-block' : 'none';
    var el = document.getElementById('blokAcc');
    if (el) el.style.display = data ? 'block' : 'none';
    var cv = document.getElementById('mdCover');
    if (cv) { cv.style.display='none'; cv.src=''; }
    var lk = document.getElementById('mdLokasi');
    if (lk) lk.textContent = data ? 'Tugas dalam papan tim ini' : '';
    muatDetail(data ? data.id : 0);
    mdTugas.show();
  }

  document.querySelectorAll('.kb-add').forEach(function(b){
    b.addEventListener('click', function(){ buka(this.dataset.list, null); });
  });
  document.querySelectorAll('.kb-card').forEach(function(c){
    c.addEventListener('click', function(){
      var d = JSON.parse(this.dataset.json || '{}');
      buka(this.closest('.kb-col').dataset.list, d);
    });
  });

  document.getElementById('btnSimpanTugas').addEventListener('click', function(){
    var judul = document.getElementById('tgTitle').value.trim();
    if (!judul) { alert('Nama tugas wajib diisi.'); return; }
    var fd = new FormData();
    fd.append('id', document.getElementById('tgId').value);
    fd.append('team_id', TEAM);
    fd.append('list_id', document.getElementById('tgList').value);
    fd.append('title', judul);
    fd.append('description', document.getElementById('tgDesc').value);
    fd.append('assignee_id', document.getElementById('tgAssignee').value);
    fd.append('due_date', document.getElementById('tgDue').value);
    fd.append('label', document.getElementById('tgLabel').value);
    fd.append('drive_link', document.getElementById('tgDrive').value);
    this.disabled = true; this.textContent = 'Menyimpan...';
    fetch(BASE+'kinerja/save_task', {method:'POST', body:fd, credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ if(d.status) location.reload(); else { alert(d.msg); document.getElementById('btnSimpanTugas').disabled=false; document.getElementById('btnSimpanTugas').textContent='Simpan'; } });
  });

  document.getElementById('btnHapusTugas').addEventListener('click', function(){
      var id = document.getElementById('tgId').value;
      if (!id) return;
      kbTanya('Hapus tugas ini?', 'Rincian, bukti, dan riwayat tugas ikut terhapus.', function(selesai){
        var fd = new FormData(); fd.append('id', id);
        fetch(BASE+'kinerja/delete_task', {method:'POST', body:fd, credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(d){
            selesai(!!d.status, d.msg);
            if (d.status) setTimeout(function(){ location.reload(); }, 950);
          })
          .catch(function(){ selesai(false, 'Gagal menghubungi server.'); });
      }, {sukJudul:'Tugas dihapus', sukSub:'Tugas berhasil dihapus dari papan.'});
    });

    var mdTim = document.getElementById('mdTim') ? new bootstrap.Modal(document.getElementById('mdTim')) : null;
    var btnTim = document.getElementById('btnSimpanTim');
    var btnHapusTim = document.getElementById('btnHapusTim');

    function resetModalTim(){
      document.getElementById('tmId').value = '';
      document.getElementById('tmName').value = '';
      document.getElementById('tmDesc').value = '';
      Array.from(document.getElementById('tmMembers').options).forEach(function(o){ o.selected = false; });
      document.getElementById('mdTimJudul').textContent = 'Tim Baru';
      if (btnTim) btnTim.textContent = 'Buat Tim';
      if (btnHapusTim) btnHapusTim.style.display = 'none';
    }
    var btnBaruTimTrigger = document.querySelector('[data-bs-target="#mdTim"]');
    if (btnBaruTimTrigger) btnBaruTimTrigger.addEventListener('click', resetModalTim);

    document.querySelectorAll('.btn-edit-tim').forEach(function(b){
      b.addEventListener('click', function(e){
        e.preventDefault(); e.stopPropagation();
        var id = this.dataset.id;
        document.getElementById('tmId').value = id;
        document.getElementById('tmName').value = this.dataset.name || '';
        document.getElementById('tmDesc').value = this.dataset.desc || '';
        document.getElementById('mdTimJudul').textContent = 'Edit Tim';
        if (btnTim) btnTim.textContent = 'Simpan Perubahan';
        if (btnHapusTim) btnHapusTim.style.display = 'inline-block';

        fetch(BASE+'kinerja/team_members_list?team_id='+id, {credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(d){
            var ids = (d.status && d.data) ? d.data.map(function(x){ return String(x.user_id); }) : [];
            Array.from(document.getElementById('tmMembers').options).forEach(function(o){
              o.selected = ids.indexOf(o.value) !== -1;
            });
          });

        if (mdTim) mdTim.show();
      });
    });

    if (btnTim) btnTim.addEventListener('click', function(){
      var nm = document.getElementById('tmName').value.trim();
      if (!nm) { alert('Nama tim wajib diisi.'); return; }
      var id = document.getElementById('tmId').value;
      var mode = id ? 'update_team' : 'save_team';
      var fd = new FormData();
      if (id) fd.append('id', id);
      fd.append('name', nm);
      fd.append('description', document.getElementById('tmDesc').value);
      Array.from(document.getElementById('tmMembers').selectedOptions).forEach(function(o){ fd.append('members[]', o.value); });
      var labelAsli = btnTim.textContent;
      this.disabled = true; this.textContent = id ? 'Menyimpan...' : 'Membuat...';
      fetch(BASE+'kinerja/'+mode, {method:'POST', body:fd, credentials:'same-origin'})
        .then(function(r){return r.json();})
        .then(function(d){
          if(d.status) location.href = BASE+'kinerja?team='+(d.id || id);
          else { alert(d.msg); btnTim.disabled=false; btnTim.textContent=labelAsli; }
        });
    });

    if (btnHapusTim) btnHapusTim.addEventListener('click', function(){
      var id = document.getElementById('tmId').value;
      if (!id) return;
      kbTanya('Hapus tim ini?', 'Semua tugas, chat, pengumuman, dokumen, dan jadwal di tim ini akan ikut terhapus permanen.', function(selesai){
        var fd = new FormData(); fd.append('id', id);
        fetch(BASE+'kinerja/delete_team', {method:'POST', body:fd, credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(d){
            selesai(!!d.status, d.msg);
            if (d.status) setTimeout(function(){ location.href = BASE+'kinerja'; }, 950);
          })
          .catch(function(){ selesai(false, 'Gagal menghubungi server.'); });
      }, {sukJudul:'Tim dihapus', sukSub:'Tim beserta seluruh datanya berhasil dihapus.'});
    });

  /* ===== ceklis & bukti ===== */
  var TASK_AKTIF = 0;

  /* ---- modal & lightbox: dibuat saat dipakai, dibuang setelah ditutup ---- */
  function kbGaya(){
      if (document.getElementById('kbGayaTag')) return;
      var st = document.createElement('style');
      st.id = 'kbGayaTag';
      st.textContent =
          '.kbOv{position:fixed;inset:0;z-index:30000;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s ease}'
        + '.kbOv.on{opacity:1;pointer-events:auto}'
        + '.kbOv.cf{background:rgba(24,19,45,.5);backdrop-filter:blur(5px) saturate(120%);-webkit-backdrop-filter:blur(5px) saturate(120%)}'
        + '.kbOv.lb{background:rgba(8,11,20,.95);cursor:zoom-out;padding:44px 20px}'
        + '.kbCf{background:#fff;border-radius:20px;padding:30px 28px 22px;width:352px;max-width:calc(100vw - 40px);text-align:center;box-shadow:0 30px 70px -20px rgba(20,15,60,.45),0 0 0 1px rgba(255,255,255,.6) inset;transform:translateY(16px) scale(.93);opacity:0;transition:transform .36s cubic-bezier(.2,1.1,.3,1),opacity .25s ease}'
        + '.kbOv.on .kbCf{transform:translateY(0) scale(1);opacity:1}'
        + '.kbCf.shake{animation:kbShake .4s ease}'
        + '@keyframes kbShake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}'
        + '.kbCfIcwrap{position:relative;width:64px;height:64px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center}'
        + '.kbRing{position:absolute;inset:0;border-radius:50%;background:#fdeeed;animation:kbPulse 1.8s ease-out infinite}'
        + '@keyframes kbPulse{0%{box-shadow:0 0 0 0 rgba(224,72,61,.28)}70%{box-shadow:0 0 0 13px rgba(224,72,61,0)}100%{box-shadow:0 0 0 0 rgba(224,72,61,0)}}'
        + '.kbCfIc{position:relative;width:40px;height:40px;border-radius:50%;background:linear-gradient(155deg,#e0483d,#c23a30);display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;box-shadow:0 8px 18px -6px rgba(194,58,48,.6);opacity:0;transform:scale(.4) rotate(-25deg);transition:transform .5s cubic-bezier(.2,1.4,.4,1) .12s,opacity .3s ease .12s}'
        + '.kbOv.on .kbCfIc{opacity:1;transform:scale(1) rotate(0)}'
        + '.kbCfDone{width:40px;height:40px;border-radius:50%;background:linear-gradient(155deg,#3fb87e,#2e9563);display:none;align-items:center;justify-content:center;color:#fff;font-size:16px;box-shadow:0 8px 18px -6px rgba(46,149,99,.6)}'
        + '.kbCfT{font-size:1.02rem;font-weight:700;color:#1b1730;margin-bottom:6px;letter-spacing:-.01em}'
        + '.kbCfS{font-size:.82rem;line-height:1.6;color:#6b6584;margin-bottom:20px}'
        + '.kbCfB{display:flex;gap:10px}'
        + '.kbCfB button{flex:1;border:none;cursor:pointer;font-size:.85rem;font-weight:700;padding:11px 12px;border-radius:11px;transition:transform .15s ease,box-shadow .2s ease,background .2s ease}'
        + '.kbCfB button:active{transform:scale(.96)}'
        + '.kbNo{background:#f2f0fb;color:#372a80}.kbNo:hover{background:#e8e4f9}'
        + '.kbYa{background:linear-gradient(155deg,#e0483d,#c23a30);color:#fff;box-shadow:0 10px 20px -8px rgba(194,58,48,.55)}.kbYa:hover{filter:brightness(1.05)}'
        + '.kbYa.loading{pointer-events:none}'
        + '.kbYa .spin{width:13px;height:13px;border-radius:50%;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;animation:kbSpin .7s linear infinite;display:none;margin-right:7px;vertical-align:-2px}'
        + '.kbYa.loading .spin{display:inline-block}.kbYa.loading .txt{display:none}'
        + '@keyframes kbSpin{to{transform:rotate(360deg)}}'
        + '.kbOv.lb img,.kbOv.lb video{max-width:92vw;max-height:86vh;object-fit:contain;border-radius:12px;box-shadow:0 24px 70px rgba(0,0,0,.65);transform:scale(.88);transition:transform .28s cubic-bezier(.18,.9,.32,1.25)}'
        + '.kbOv.lb.on img,.kbOv.lb.on video{transform:scale(1)}'
        + '.kbLbNm{position:absolute;bottom:22px;left:0;right:0;text-align:center;color:#cbd5e1;font-size:.82rem;padding:0 20px}';
      document.head.appendChild(st);
    }

    function kbBuang(el){
    el.classList.remove('on');
    setTimeout(function(){ if (el.parentNode) el.parentNode.removeChild(el); }, 280);
  }

  function kbTanya(judul, sub, cb, opts){
      opts = opts || {};
      kbGaya();
      var ov = document.createElement('div');
      ov.className = 'kbOv cf';
      ov.innerHTML = '<div class="kbCf">'
        + '<div class="kbCfIcwrap"><div class="kbRing"></div><div class="kbCfIc"><i class="bi bi-trash3-fill"></i></div><div class="kbCfDone"><i class="bi bi-check-lg"></i></div></div>'
        + '<div class="kbCfT"></div><div class="kbCfS"></div>'
        + '<div class="kbCfB"><button class="kbNo">Batal</button><button class="kbYa"><span class="spin"></span><span class="txt">Ya, hapus</span></button></div></div>';
      ov.querySelector('.kbCfT').textContent = judul;
      ov.querySelector('.kbCfS').textContent = sub;
      document.body.appendChild(ov);
      requestAnimationFrame(function(){ ov.classList.add('on'); });

      var card = ov.querySelector('.kbCf');
      var ic = ov.querySelector('.kbCfIc');
      var done = ov.querySelector('.kbCfDone');
      var btnYa = ov.querySelector('.kbYa');
      var btnNo = ov.querySelector('.kbNo');
      var tEl = ov.querySelector('.kbCfT');
      var sEl = ov.querySelector('.kbCfS');

      btnNo.addEventListener('click', function(){
        card.classList.add('shake');
        setTimeout(function(){ card.classList.remove('shake'); kbBuang(ov); }, 220);
      });
      ov.addEventListener('click', function(e){ if (e.target === ov) kbBuang(ov); });
      document.addEventListener('keydown', function esc(e){
        if (e.key === 'Escape') { kbBuang(ov); document.removeEventListener('keydown', esc); }
      });

      btnYa.addEventListener('click', function(){
        btnYa.classList.add('loading');
        cb(function selesai(sukses, pesan){
          if (!sukses){
            btnYa.classList.remove('loading');
            alert(pesan || 'Gagal menghapus.');
            return;
          }
          ic.style.display='none'; done.style.display='flex';
          tEl.textContent = opts.sukJudul || 'Berhasil dihapus';
          sEl.textContent = opts.sukSub || 'Data berhasil dihapus.';
          btnYa.parentNode.style.display='none';
          setTimeout(function(){ kbBuang(ov); }, 950);
        });
      });
    }

    function kbRayakan(judul){
    kbGaya();
    var ov = document.createElement('div');
    ov.id = 'kbRaya';
    ov.innerHTML = '<div class="kbRayaKotak">'
      + '<div class="kbRayaIc"><i class="bi bi-check-lg"></i></div>'
      + '<div class="kbRayaT">Tugas Selesai!</div>'
      + '<div class="kbRayaS"></div></div>';
    ov.querySelector('.kbRayaS').textContent = judul || 'Kerja bagus, target tercapai.';
    document.body.appendChild(ov);

    var warna = ['#22c55e','#4aa8ff','#f0b429','#a78bfa','#ef4444','#10b981'];
    for (var i = 0; i < 34; i++) {
      var p = document.createElement('div');
      p.className = 'kbLetup';
      p.style.background = warna[i % warna.length];
      p.style.left = '50%'; p.style.top = '46%';
      p.style.setProperty('--x', (Math.random()*440 - 220) + 'px');
      p.style.setProperty('--y', (Math.random()*380 - 130) + 'px');
      p.style.animationDelay = (Math.random()*0.18) + 's';
      ov.appendChild(p);
    }
    setTimeout(function(){
      ov.style.transition = 'opacity .35s ease';
      ov.style.opacity = '0';
      setTimeout(function(){ if (ov.parentNode) ov.parentNode.removeChild(ov); }, 360);
    }, 1500);
  }

  function kbBukaLb(url, nama, jenis){
    kbGaya();
    var e = (jenis||'').toLowerCase();
    var ov = document.createElement('div');
    ov.className = 'kbOv lb';
    ov.innerHTML = (['mp4','webm','mov'].indexOf(e) >= 0
        ? '<video src="'+url+'" controls autoplay playsinline></video>'
        : '<img src="'+url+'" alt="">')
      + '<div class="kbLbNm"></div>';
    ov.querySelector('.kbLbNm').textContent = nama || '';
    document.body.appendChild(ov);
    requestAnimationFrame(function(){ ov.classList.add('on'); });

    ov.addEventListener('click', function(){ kbBuang(ov); });
    document.addEventListener('keydown', function esc(e){
      if (e.key === 'Escape') { kbBuang(ov); document.removeEventListener('keydown', esc); }
    });
  }

  function muatDetail(id){
    TASK_AKTIF = id;
    var area = document.getElementById('areaDetail');
    if (!id) { area.style.display='none'; return; }
    area.style.display='block';
    document.getElementById('tgCeklis').innerHTML = '<div class="text-muted small">memuat...</div>';
    document.getElementById('tgBukti').innerHTML  = '';

    fetch(BASE+'kinerja/task_detail?id='+id, {credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        if(!d.status){ document.getElementById('tgCeklis').innerHTML=''; return; }

        var ck = d.checklist || [], sel = 0, h = '';
        ck.forEach(function(c){
          if (c.is_done == 1) sel++;
          var waktu = '';
          if (c.is_done == 1 && c.done_at) {
            waktu = '<div class="ck-wk"><i class="bi bi-check-circle-fill"></i> selesai '
                  + c.done_at.substring(8,10)+'/'+c.done_at.substring(5,7)+' '+c.done_at.substring(11,16)
                  + (c.pelaku ? ' &middot; '+c.pelaku : '') + '</div>';
          }
          h += '<div class="ck-row '+(c.is_done==1?'done':'')+'" data-row="'+c.id+'"'
             + ' data-item="'+String(c.item||'').replace(/"/g,'&quot;')+'"'
             + ' data-ket="'+String(c.keterangan||'').replace(/"/g,'&quot;')+'">'
             + '<input type="checkbox" class="form-check-input ck-tog mt-1" data-id="'+c.id+'" '+(c.is_done==1?'checked':'')+'>'
             + '<div class="flex-grow-1">'
             +   '<div class="ck-it">'+c.item+'</div>'
             +   (c.keterangan ? '<div class="ck-ket">'+c.keterangan+'</div>' : '')
             +   waktu
             + '</div>'
             + '<div class="ck-akt">'
             +   '<button class="btn btn-sm btn-link text-secondary p-0 ck-edit" data-id="'+c.id+'" title="ubah"><i class="bi bi-pencil"></i></button>'
             +   '<button class="btn btn-sm btn-link text-danger p-0 ck-del" data-id="'+c.id+'" title="hapus"><i class="bi bi-trash3"></i></button>'
             + '</div></div>';
        });
        document.getElementById('tgCeklis').innerHTML = h || '<div class="text-muted small">Belum ada rincian.</div>';
        document.getElementById('ckProgres').textContent = sel+'/'+ck.length;

        var at = d.attachments || [], hb = '';
        var cover = document.getElementById('mdCover');
        if (cover) {
          var pertama = at.find(function(x){ return ['jpg','jpeg','png','gif','webp'].indexOf((x.file_type||'').toLowerCase()) >= 0; });
          if (pertama) { cover.src = BASE + pertama.file_path; cover.style.display = 'block'; }
          else { cover.style.display = 'none'; }
        }
        at.forEach(function(a){
          var t = (a.file_type||'').toLowerCase();
          var isImg = ['jpg','jpeg','png','gif','webp'].indexOf(t) >= 0;
          var isVid = ['mp4','webm','mov'].indexOf(t) >= 0;
          var ic = 'bi-file-earmark';
          if (t=='pdf') ic='bi-file-earmark-pdf';
          else if (['doc','docx'].indexOf(t)>=0) ic='bi-file-earmark-word';
          else if (['xls','xlsx'].indexOf(t)>=0) ic='bi-file-earmark-excel';
          else if (t=='zip') ic='bi-file-earmark-zip';

          var isi;
          if (isImg)      isi = '<img src="'+BASE+a.file_path+'" loading="lazy" class="bk-lb" style="cursor:zoom-in" data-url="'+BASE+a.file_path+'" data-nm="'+a.name+'" data-jn="'+t+'">';
          else if (isVid) isi = '<video src="'+BASE+a.file_path+'" preload="metadata" class="bk-lb" style="cursor:zoom-in" data-url="'+BASE+a.file_path+'" data-nm="'+a.name+'" data-jn="'+t+'"></video>';
          else            isi = '<a href="'+BASE+a.file_path+'" target="_blank" class="bk-box d-block"><i class="bi '+ic+'"></i></a>';

          hb += '<div class="bk-item">'+isi
              + '<button class="bk-x bk-del" data-id="'+a.id+'" title="hapus">&times;</button>'
              + '<div class="bk-nm">'+a.name+'</div></div>';
        });
        document.getElementById('tgBukti').innerHTML = hb
          ? '<div class="bk-grid">'+hb+'</div>'
          : '<div class="text-muted small mb-2">Belum ada bukti diunggah.</div>';

        var st = (d.task && d.task.approval) ? d.task.approval : 'menunggu';
        var nt = (d.task && d.task.approval_note) ? d.task.approval_note : '';
        var warna = st=='disetujui' ? 'success' : (st=='revisi' ? 'warning' : 'secondary');
        var teks  = st=='disetujui' ? 'Disetujui' : (st=='revisi' ? 'Perlu revisi' : 'Menunggu persetujuan');
        var el = document.getElementById('accStatus');
        if (el) el.innerHTML = '<span class="badge bg-'+warna+'">'+teks+'</span>'
                + (nt ? '<div class="small text-muted mt-1">Catatan: '+nt+'</div>' : '');
        var an = document.getElementById('accNote');
        if (an) an.value = nt;

        document.querySelectorAll('.ck-tog').forEach(function(x){
          x.addEventListener('change', function(){
            var fd=new FormData(); fd.append('id', this.dataset.id);
            fetch(BASE+'kinerja/toggle_ceklis',{method:'POST',body:fd,credentials:'same-origin'})
              .then(function(r){return r.json();}).then(function(){ muatDetail(TASK_AKTIF); });
          });
        });
        document.querySelectorAll('.ck-del').forEach(function(x){
            x.addEventListener('click', function(){
              var idc = this.dataset.id;
              kbTanya('Hapus rincian ini?', 'Keterangan dan catatan waktu ikut terhapus.', function(selesai){
                var fd=new FormData(); fd.append('id', idc);
                fetch(BASE+'kinerja/delete_ceklis',{method:'POST',body:fd,credentials:'same-origin'})
                  .then(function(r){return r.json();})
                  .then(function(){ selesai(true); muatDetail(TASK_AKTIF); })
                  .catch(function(){ selesai(false, 'Gagal menghubungi server.'); });
              }, {sukJudul:'Rincian dihapus', sukSub:'Rincian pekerjaan berhasil dihapus.'});
            });
          });

          document.querySelectorAll('.ck-edit').forEach(function(x){
          x.addEventListener('click', function(){
            var id  = this.dataset.id;
            var row = document.querySelector('.ck-row[data-row="'+id+'"]');
            if (!row || row.querySelector('.ck-form')) return;
            var item = row.dataset.item || '';
            var ket  = row.dataset.ket || '';
            var isi  = row.querySelector('.flex-grow-1');
            var akt  = row.querySelector('.ck-akt');
            isi.style.display = 'none';
            if (akt) akt.style.display = 'none';

            var frm = document.createElement('div');
            frm.className = 'ck-form flex-grow-1';
            frm.innerHTML =
                '<input type="text" class="form-control form-control-sm mb-1 ck-e1" value="'+item.replace(/"/g,'&quot;')+'" placeholder="rincian">'
              + '<input type="text" class="form-control form-control-sm mb-1 ck-e2" value="'+ket.replace(/"/g,'&quot;')+'" placeholder="keterangan">'
              + '<div class="d-flex gap-2"><button class="btn btn-primary btn-sm ck-simpan">Simpan</button>'
              + '<button class="btn btn-outline-secondary btn-sm ck-batal">Batal</button></div>';
            isi.insertAdjacentElement('afterend', frm);
            frm.querySelector('.ck-e1').focus();

            frm.querySelector('.ck-batal').addEventListener('click', function(){
              frm.remove(); isi.style.display=''; if (akt) akt.style.display='';
            });
            frm.querySelector('.ck-simpan').addEventListener('click', function(){
              var v1 = frm.querySelector('.ck-e1').value.trim();
              if (!v1) { alert('Rincian tidak boleh kosong.'); return; }
              var fd = new FormData();
              fd.append('id', id);
              fd.append('item', v1);
              fd.append('keterangan', frm.querySelector('.ck-e2').value);
              fetch(BASE+'kinerja/edit_ceklis',{method:'POST',body:fd,credentials:'same-origin'})
                .then(function(r){return r.json();})
                .then(function(d){ if(d.status) muatDetail(TASK_AKTIF); else alert(d.msg); });
            });
          });
        });
        document.querySelectorAll('.bk-lb').forEach(function(x){
          x.addEventListener('click', function(ev){
            ev.preventDefault(); ev.stopPropagation();
            kbBukaLb(this.dataset.url, this.dataset.nm, this.dataset.jn);
          });
        });
        document.querySelectorAll('.bk-del').forEach(function(x){
            x.addEventListener('click', function(ev){
              ev.stopPropagation();
              var idb = this.dataset.id;
              kbTanya('Hapus bukti ini?', 'Berkas akan dihapus permanen dari server.', function(selesai){
                var fd=new FormData(); fd.append('id', idb);
                fetch(BASE+'kinerja/delete_bukti',{method:'POST',body:fd,credentials:'same-origin'})
                  .then(function(r){return r.json();})
                  .then(function(){ selesai(true); muatDetail(TASK_AKTIF); })
                  .catch(function(){ selesai(false, 'Gagal menghubungi server.'); });
              }, {sukJudul:'Bukti dihapus', sukSub:'Berkas berhasil dihapus.'});
            });
          });
        });
    }

    document.getElementById('btnCkTambah').addEventListener('click', function(){
    var v = document.getElementById('ckBaru').value.trim();
    if(!v || !TASK_AKTIF) return;
    var fd=new FormData(); fd.append('task_id',TASK_AKTIF); fd.append('item',v); fd.append('keterangan', document.getElementById('ckKet').value);
    fetch(BASE+'kinerja/save_ceklis',{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ if(d.status){ document.getElementById('ckBaru').value=''; document.getElementById('ckKet').value=''; muatDetail(TASK_AKTIF); } else alert(d.msg); });
  });
  document.getElementById('ckBaru').addEventListener('keypress', function(e){
    if(e.key==='Enter'){ e.preventDefault(); document.getElementById('btnCkTambah').click(); }
  });

  document.getElementById('btnBkUpload').addEventListener('click', function(){
    var f = document.getElementById('bkFile').files[0];
    if(!f || !TASK_AKTIF){ alert('Pilih berkas dulu.'); return; }
    var fd=new FormData(); fd.append('task_id',TASK_AKTIF); fd.append('berkas',f);
    var b=this; b.disabled=true; b.textContent='...';
    fetch(BASE+'kinerja/upload_bukti',{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ b.disabled=false; b.textContent='Unggah';
        if(d.status){ document.getElementById('bkFile').value=''; muatDetail(TASK_AKTIF); } else alert(d.msg); });
  });

  document.querySelectorAll('[data-acc]').forEach(function(b){
    b.addEventListener('click', function(){
      if (!TASK_AKTIF) return;
      var fd=new FormData();
      fd.append('id', TASK_AKTIF);
      fd.append('approval', this.dataset.acc);
      fd.append('note', document.getElementById('accNote').value);
      fetch(BASE+'kinerja/set_approval',{method:'POST',body:fd,credentials:'same-origin'})
        .then(function(r){return r.json();})
        .then(function(d){ if(d.status){ muatDetail(TASK_AKTIF); setTimeout(function(){ location.reload(); }, 600); } else alert(d.msg); });
    });
  });

  /* geser kartu antar kolom */
  var seret = null;
  document.querySelectorAll('.kb-card').forEach(function(c){
    c.addEventListener('dragstart', function(e){ seret = this; this.classList.add('drag'); e.stopPropagation(); });
    c.addEventListener('dragend', function(){ this.classList.remove('drag'); });
  });
  document.querySelectorAll('.kb-col').forEach(function(col){
    col.addEventListener('dragover', function(e){ e.preventDefault(); this.classList.add('over'); });
    col.addEventListener('dragleave', function(){ this.classList.remove('over'); });
    col.addEventListener('drop', function(e){
      e.preventDefault(); this.classList.remove('over');
      if (!seret) return;
      var tujuan = this.dataset.list;
      var jenisKol = this.dataset.kind;
      var judulTugas = '';
      try { judulTugas = (JSON.parse(seret.dataset.json||'{}').title) || ''; } catch(e){}
      this.querySelector('.kb-items').appendChild(seret);
      if (jenisKol === 'done') kbRayakan(judulTugas);
      var fd = new FormData(); fd.append('id', seret.dataset.id); fd.append('list_id', tujuan);
      fetch(BASE+'kinerja/move_task', {method:'POST', body:fd, credentials:'same-origin'})
        .then(function(r){return r.json();})
        .then(function(d){ if(!d.status){ alert(d.msg||'Gagal memindahkan'); location.reload(); } else { setTimeout(function(){ location.reload(); }, jenisKol === 'done' ? 1900 : 250); } });
      seret = null;
    });
  });
})();
</script>
