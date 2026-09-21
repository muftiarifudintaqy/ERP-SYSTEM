<div class="topbar">
  <div>
    <h1>Ringkasan HRD</h1>
    <p class="sub">Semua data karyawan, barang kantor, dan hasil DISC dalam satu halaman.</p>
  </div>
  <div class="aksi">
    <a class="btn" href="<?= base_url('hrd/export_karyawan') ?>">Unduh Excel karyawan</a>
    <a class="btn btn-utama" href="<?= base_url('hrd/karyawan_form') ?>">Tambah karyawan</a>
  </div>
</div>

<div class="grid g4">
  <div class="kartu">
    <div class="angka"><?= $r['karyawan_aktif'] ?></div>
    <div class="label">Karyawan aktif<?= $r['karyawan_total'] > $r['karyawan_aktif'] ? ' (dari ' . $r['karyawan_total'] . ' total)' : '' ?></div>
  </div>
  <div class="kartu">
    <div class="angka" style="color:<?= $r['belum_lengkap'] ? 'var(--merah)' : 'inherit' ?>"><?= $r['belum_lengkap'] ?></div>
    <div class="label">Data belum lengkap (KTP / NIK / rekening)</div>
  </div>
  <div class="kartu">
    <div class="angka"><?= $r['inventaris'] ?></div>
    <div class="label">Barang tercatat<?= $r['barang_rusak'] ? ' &middot; ' . $r['barang_rusak'] . ' bermasalah' : '' ?></div>
  </div>
  <div class="kartu">
    <div class="angka"><?= $r['disc_terisi'] ?></div>
    <div class="label">DISC sudah diisi<?= count($belum_disc) ? ' &middot; ' . count($belum_disc) . ' belum' : '' ?></div>
  </div>
</div>

<div class="grid g2" style="margin-top:16px; align-items:start">
  <div class="kartu">
    <h2>Belum mengisi DISC</h2>
    <?php if (!$belum_disc): ?>
      <div class="kosong"><b>Semua sudah mengisi</b>Tidak ada yang perlu diingatkan.</div>
    <?php else: ?>
      <div class="tabel-bungkus" style="border:none">
        <p style="margin:0 0 12px">
          <a class="btn btn-kecil"
             href="<?= base_url('hrd/ingatkan_disc') ?>"
             onclick="return confirm('Kirim pengingat ke semua yang belum mengisi DISC?')">
            Ingatkan semua
          </a>
        </p>
        <table>
          <tbody>
          <?php foreach ($belum_disc as $b): ?>
            <tr>
              <td>
                <div class="baris-nama">
                  <span class="avatar"><?= strtoupper(mb_substr($b['nama'], 0, 2)) ?></span>
                  <span><?= html_escape($b['nama']) ?><small><?= html_escape($b['jabatan'] ?: '-') ?></small></span>
                </div>
              </td>
              <td class="rapat" style="text-align:right">
                <?php if (!empty($b['user_id'])): ?>
                  <a class="btn btn-kecil"
                     href="<?= base_url('hrd/ingatkan_disc/' . (int)$b['id']) ?>">Ingatkan</a>
                <?php else: ?>
                  <span class="label">belum punya akun</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Asesmen yang berhenti di tengah. Orang-orang ini tidak muncul di
       daftar "Belum mengisi DISC" karena DISC-nya sudah terisi, padahal
       asesmennya belum kelar -- tanpa kartu ini mereka lolos pantauan. -->
  <div class="kartu">
    <h3>Asesmen belum selesai</h3>
    <?php if (empty($tertunda)): ?>
      <p class="label" style="margin-top:10px">Tidak ada yang tertunda.</p>
    <?php else: ?>
      <div>
        <table>
          <tbody>
          <?php
            $nama_tahap = [
              'data_diri'     => 'Data diri',
              'disc'          => 'DISC',
              'bigfive'       => 'Big Five',
              'karakteristik' => 'Karakteristik',
              'aptitude'      => 'Test IQ',
            ];
          ?>
          <?php foreach ($tertunda as $t): ?>
            <tr>
              <td>
                <div class="baris-nama">
                  <span class="avatar"><?= strtoupper(mb_substr($t['nama'], 0, 2)) ?></span>
                  <span><?= html_escape($t['nama']) ?><small><?= html_escape($t['divisi'] ?: '-') ?></small></span>
                </div>
              </td>
              <td class="rapat" style="text-align:right">
                <span class="label">berhenti di
                  <?= html_escape($nama_tahap[$t['tahap']] ?? $t['tahap']) ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="kartu">
    <h2>Perubahan terakhir</h2>
    <?php if (!$aktivitas): ?>
      <div class="kosong"><b>Belum ada data</b>Bagikan tautan form ke tim untuk mulai mengumpulkan data.</div>
    <?php else: ?>
      <div class="tabel-bungkus" style="border:none">
        <table>
          <tbody>
          <?php foreach ($aktivitas as $a): ?>
            <tr>
              <td><?= html_escape($a['judul']) ?><small style="display:block;color:var(--tinta-3)"><?= html_escape($a['jenis']) ?></small></td>
              <td class="rapat" style="text-align:right;color:var(--tinta-3)"><?= $a['waktu'] ? date('d M Y H:i', strtotime($a['waktu'])) : '-' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="kartu" style="margin-top:16px">
  <h2>Tautan untuk dibagikan ke tim</h2>
  <p class="label">Kirim tautan ini lewat WhatsApp. Jawaban langsung masuk ke halaman ini, tanpa Google Form.</p>
  <div class="grid g3" style="margin-top:12px">
    <?php
    $tautan = [
      ['Informasi data karyawan', base_url('f/data-karyawan')],
      ['Inventaris kantor',       base_url('f/inventaris')],
    ];
    if ($r['disc_soal_grup'] > 0) $tautan[] = ['DISC test', base_url('f/disc')];
    foreach ($tautan as $t): ?>
      <div style="border:1px solid var(--garis); border-radius:8px; padding:12px 14px">
        <b style="font-size:.9rem"><?= $t[0] ?></b>
        <div style="font-size:.78rem;color:var(--tinta-3);word-break:break-all;margin:4px 0 9px"><?= $t[1] ?></div>
        <div class="aksi">
          <button class="btn btn-kecil" type="button" onclick="navigator.clipboard.writeText('<?= $t[1] ?>');this.textContent='Tersalin'">Salin tautan</button>
          <a class="btn btn-kecil" href="<?= $t[1] ?>" target="_blank">Buka</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
