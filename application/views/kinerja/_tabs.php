<style>
.kb-nav{display:flex;gap:6px;border-bottom:1px solid #e2e8f0;margin-bottom:16px;overflow-x:auto}
.kb-nav a{white-space:nowrap;padding:10px 16px;font-size:.86rem;color:#64748b;text-decoration:none;border-bottom:2px solid transparent}
.kb-nav a:hover{color:#6E4FA8}
.kb-nav a.on{color:#6E4FA8;border-bottom-color:#6E4FA8;font-weight:600}
.kb-tabs{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;margin-bottom:16px}
.kb-tab{white-space:nowrap;padding:8px 16px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#475569;font-size:.85rem;text-decoration:none}
.kb-tab.on{background:#6E4FA8;border-color:#6E4FA8;color:#fff;font-weight:600}
</style>
<div class="kb-nav">
  <a href="<?= base_url() ?>kinerja/ringkasan?team=<?= $team_id ?>" class="<?= $halaman=='ringkasan'?'on':'' ?>"><i class="bi bi-grid-1x2 me-1"></i>Ringkasan</a>
  <a href="<?= base_url() ?>kinerja?team=<?= $team_id ?>"            class="<?= $halaman=='tugas'?'on':'' ?>"><i class="bi bi-kanban me-1"></i>Tugas</a>
  <a href="<?= base_url() ?>kinerja/pengumuman?team=<?= $team_id ?>" class="<?= $halaman=='pengumuman'?'on':'' ?>"><i class="bi bi-megaphone me-1"></i>Pengumuman</a>
  <a href="<?= base_url() ?>kinerja/dokumen?team=<?= $team_id ?>"    class="<?= $halaman=='dokumen'?'on':'' ?>"><i class="bi bi-folder me-1"></i>Dokumen &amp; File</a>
  <a href="<?= base_url() ?>kinerja/chat?team=<?= $team_id ?>"       class="<?= $halaman=='chat'?'on':'' ?>"><i class="bi bi-chat-dots me-1"></i>Chat Grup</a>
  <a href="<?= base_url() ?>kinerja/jadwal?team=<?= $team_id ?>"     class="<?= $halaman=='jadwal'?'on':'' ?>"><i class="bi bi-calendar3 me-1"></i>Jadwal</a>
  <a href="<?= base_url() ?>kinerja/pertanyaan?team=<?= $team_id ?>" class="<?= $halaman=='pertanyaan'?'on':'' ?>"><i class="bi bi-patch-question me-1"></i>Pertanyaan</a>
</div>
<div class="kb-tabs">
  <?php foreach ($teams as $t): ?>
    <a class="kb-tab <?= $t['id']==$team_id?'on':'' ?>" href="<?= base_url().'kinerja'.($halaman=='tugas'?'':'/'.$halaman) ?>?team=<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></a>
  <?php endforeach; ?>
</div>
