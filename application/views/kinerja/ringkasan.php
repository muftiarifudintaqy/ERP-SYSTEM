<?php $halaman='ringkasan'; $this->load->view('kinerja/_tabs', array('teams'=>$teams,'team_id'=>$team_id,'halaman'=>$halaman)); ?>
<style>
.rk-item{overflow:hidden;border-radius:9px}
.rk-cover{width:100%;height:96px;object-fit:cover;display:block;border-radius:9px;margin-bottom:8px;background:#e2e8f0}

.rk-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px}
.rk-card{background:#fff;border:1px solid #e5e8f0;border-radius:16px;padding:20px 18px;display:flex;flex-direction:column;min-height:190px;text-decoration:none;color:inherit;
  opacity:0;transform:translateY(14px);animation:rkIn .45s ease forwards;transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease}
.rk-card:hover{transform:translateY(-4px);box-shadow:0 16px 34px rgba(30,41,59,.12);border-color:#c7ccf7;text-decoration:none;color:inherit;cursor:pointer}
.rk-grid .rk-card:nth-child(1){animation-delay:.02s}
.rk-grid .rk-card:nth-child(2){animation-delay:.08s}
.rk-grid .rk-card:nth-child(3){animation-delay:.14s}
.rk-grid .rk-card:nth-child(4){animation-delay:.20s}
.rk-grid .rk-card:nth-child(5){animation-delay:.26s}
.rk-grid .rk-card:nth-child(6){animation-delay:.32s}
@keyframes rkIn{to{opacity:1;transform:translateY(0)}}
.rk-head{text-align:center;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #f1f5f9}
.rk-head .ic{width:38px;height:38px;border-radius:11px;background:#eef2ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:1.05rem;margin:0 auto 8px}
.rk-head h6{font-size:.92rem;font-weight:700;color:#1e293b;letter-spacing:.2px;margin:0}
.rk-head .arrow{font-size:.66rem;color:#94a3b8;margin-top:3px;font-weight:500}
.rk-body{flex:1;overflow:hidden}
.rk-item{padding:8px 2px;border-bottom:1px solid #f8fafc;font-size:.82rem;color:#334155;overflow:hidden}
.rk-item:last-child{border-bottom:0}
.rk-item .txt{overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;word-break:break-word;overflow-wrap:anywhere;line-height:1.4}
.rk-sub{font-size:.7rem;color:#94a3b8;margin-top:2px}
.rk-empty{color:#94a3b8;font-size:.8rem;text-align:center;padding:26px 10px}
.rk-empty i{display:block;font-size:1.7rem;opacity:.35;margin-bottom:8px}
</style>

<?php if (!$team_id): ?>
  <div class="card"><div class="card-body text-center py-5 text-muted">Belum ada tim.</div></div>
<?php else: ?>
<div class="rk-grid">

  <a class="rk-card" href="<?= base_url() ?>kinerja?team=<?= $team_id ?>">
    <div class="rk-head"><div class="ic"><i class="bi bi-list-check"></i></div><h6>Tugas</h6><div class="arrow">buka halaman &rarr;</div></div>
    <div class="rk-body">
      <?php if (empty($tugas)): ?><div class="rk-empty"><i class="bi bi-list-check"></i>Belum ada tugas.</div>
      <?php else: foreach ($tugas as $t): ?>
        <div class="rk-item">
          <?php if (!empty($t['cover_path'])): ?>
            <img class="rk-cover" src="<?= base_url().$t['cover_path'] ?>" loading="lazy" alt="">
          <?php endif; ?>
          <div class="txt"><?= htmlspecialchars($t['title']) ?></div>
          <div class="rk-sub"><?= htmlspecialchars($t['assignee_name'] ?: 'belum ditugaskan') ?><?php if (!empty($t['list_nama'])): ?> &middot; <?= htmlspecialchars($t['list_nama']) ?><?php endif; ?></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </a>

  <a class="rk-card" href="<?= base_url() ?>kinerja/chat?team=<?= $team_id ?>">
    <div class="rk-head"><div class="ic"><i class="bi bi-chat-dots"></i></div><h6>Chat Grup</h6><div class="arrow">buka chat &rarr;</div></div>
    <div class="rk-body">
      <?php if (empty($chat)): ?><div class="rk-empty"><i class="bi bi-chat-dots"></i>Belum ada percakapan.</div>
      <?php else: foreach ($chat as $c): ?>
        <div class="rk-item"><div class="rk-sub" style="margin-top:0;font-weight:600;color:#6366f1"><?= htmlspecialchars($c['nama'] ?: 'Pengguna') ?></div><div class="txt"><?= htmlspecialchars($c['message'] ?: ($c['file_name'] ? '📎 '.$c['file_name'] : '')) ?></div></div>
      <?php endforeach; endif; ?>
    </div>
  </a>

  <a class="rk-card" href="<?= base_url() ?>kinerja/pengumuman?team=<?= $team_id ?>">
    <div class="rk-head"><div class="ic"><i class="bi bi-megaphone"></i></div><h6>Pengumuman</h6><div class="arrow">buka halaman &rarr;</div></div>
    <div class="rk-body">
      <?php if (!$pengumuman): ?><div class="rk-empty"><i class="bi bi-megaphone"></i>Belum ada pengumuman.</div>
      <?php else: ?>
        <div class="rk-item">
          <div class="txt" style="font-weight:600"><?= htmlspecialchars($pengumuman['title']) ?></div>
          <div class="rk-sub"><?= htmlspecialchars($pengumuman['penulis'] ?: '-') ?> &middot; <?= date('j M Y', strtotime($pengumuman['created_at'])) ?></div>
          <div class="txt mt-1"><?= htmlspecialchars(mb_strimwidth($pengumuman['body'], 0, 120, '...')) ?></div>
        </div>
      <?php endif; ?>
    </div>
  </a>

  <a class="rk-card" href="<?= base_url() ?>kinerja/dokumen?team=<?= $team_id ?>">
    <div class="rk-head"><div class="ic"><i class="bi bi-folder"></i></div><h6>Dokumen &amp; File</h6><div class="arrow">buka halaman &rarr;</div></div>
    <div class="rk-body">
      <?php if (empty($dokumen)): ?><div class="rk-empty"><i class="bi bi-folder"></i>Belum ada berkas.</div>
      <?php else: foreach ($dokumen as $d): ?>
        <div class="rk-item"><div class="txt"><?= htmlspecialchars($d['name']) ?></div><div class="rk-sub"><?= htmlspecialchars($d['pengunggah'] ?: '-') ?> &middot; <?= date('j M Y', strtotime($d['created_at'])) ?></div></div>
      <?php endforeach; endif; ?>
    </div>
  </a>

  <a class="rk-card" href="<?= base_url() ?>kinerja/jadwal?team=<?= $team_id ?>">
    <div class="rk-head"><div class="ic"><i class="bi bi-calendar3"></i></div><h6>Jadwal</h6><div class="arrow">buka halaman &rarr;</div></div>
    <div class="rk-body"><div class="rk-empty"><i class="bi bi-calendar3"></i>Buka halaman untuk lihat agenda bulan ini.</div></div>
  </a>

  <a class="rk-card" href="<?= base_url() ?>kinerja/pertanyaan?team=<?= $team_id ?>">
    <div class="rk-head"><div class="ic"><i class="bi bi-question-circle"></i></div><h6>Pertanyaan</h6><div class="arrow">buka halaman &rarr;</div></div>
    <div class="rk-body"><div class="rk-empty"><i class="bi bi-question-circle"></i>Buka halaman untuk lihat check-in tim.</div></div>
  </a>

</div>
<?php endif; ?>
