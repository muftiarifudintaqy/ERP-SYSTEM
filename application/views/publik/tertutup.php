<div class="publik-kepala">
  <div class="garis"></div>
  <h1><?= html_escape($f['judul']) ?></h1>
  <p><?= $f['status'] === 'ditutup' ? 'Formulir ini sudah ditutup dan tidak menerima jawaban baru.' : 'Formulir ini belum dibuka. Tunggu pengumuman dari HRD.' ?></p>
</div>
