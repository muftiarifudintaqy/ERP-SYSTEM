<?php
  $per_brand = [];
  foreach ($produk as $p) { $per_brand[$p['brand']][] = $p; }
  $total_baris = array_sum(array_column($rows, 'baris'));
?>
<style>
  .skub{background:#fff;border-radius:14px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
  .skub h4{margin:0;font-weight:700}
  .skub .ket{color:#64748b;font-size:.85rem;margin:6px 0 18px;line-height:1.55}
  .skub table{width:100%;border-collapse:collapse;font-size:.84rem}
  .skub th{text-align:left;color:#64748b;font-weight:600;padding:10px 8px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
  .skub td{padding:10px 8px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
  .skub .sku{font-family:ui-monospace,Menlo,monospace;font-weight:600;color:#0f172a}
  .skub .contoh{color:#64748b;font-size:.76rem;margin-top:2px}
  .skub .angka{text-align:right;font-variant-numeric:tabular-nums}
  .skub .brand{display:inline-block;padding:2px 8px;border-radius:999px;font-size:.72rem;font-weight:600;background:#eef2ff;color:#4338ca}
  .skub input.tujuan{width:150px;padding:6px 9px;border:1px solid #cbd5e1;border-radius:8px;font-family:ui-monospace,Menlo,monospace;text-transform:uppercase}
  .skub button.simpan{padding:6px 14px;border:0;border-radius:8px;background:#1F4696;color:#fff;font-weight:600;cursor:pointer}
  .skub button.simpan:disabled{opacity:.5;cursor:wait}
  .skub .kosong{text-align:center;color:#64748b;padding:40px 0}
</style>

<?php $this->load->view('operasional/menu'); ?>

<div class="skub">
  <h4>SKU belum dikenali</h4>
  <p class="ket">
    SKU di bawah ini terjual di marketplace tetapi tidak ada di master produk,
    sehingga harga pokoknya <b>tidak masuk HPP</b>.
    Total <b id="sisaBaris"><?= number_format($total_baris, 0, ',', '.') ?></b> baris order.<br>
    Isi SKU resmi tujuannya lalu tekan Simpan. Boleh juga pola bundel seperti
    <code>1FS+1NS</code> atau <code>3BC</code> &mdash; dibuat otomatis kalau semua komponennya sudah punya harga beli.
    Begitu disimpan, HPP order lama langsung dihitung ulang.
  </p>

  <?php foreach ($per_brand as $brand => $daftar): ?>
    <datalist id="sku-<?= htmlspecialchars($brand) ?>">
      <?php foreach ($daftar as $p): ?>
        <option value="<?= htmlspecialchars($p['sku']) ?>"><?= htmlspecialchars(mb_strimwidth($p['name'], 0, 60, '...')) ?> &middot; Rp <?= number_format($p['price_buy'], 0, ',', '.') ?></option>
      <?php endforeach; ?>
    </datalist>
  <?php endforeach; ?>

  <?php if (empty($rows)): ?>
    <div class="kosong">Semua SKU yang terjual sudah dikenali. HPP lengkap.</div>
  <?php else: ?>
  <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>Brand</th><th>SKU marketplace</th><th class="angka">Baris order</th>
          <th>Periode</th><th>SKU tujuan</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr data-brand="<?= htmlspecialchars($r['brand']) ?>"
            data-sku="<?= htmlspecialchars($r['sku']) ?>"
            data-baris="<?= (int) $r['baris'] ?>">
          <td><span class="brand"><?= htmlspecialchars($r['brand']) ?></span></td>
          <td>
            <div class="sku"><?= htmlspecialchars($r['sku']) ?></div>
            <div class="contoh"><?= htmlspecialchars(mb_strimwidth((string) $r['contoh'], 0, 70, '...')) ?></div>
            <?php if ((int) $r['jumlah_nama'] > 1): ?>
              <div title="<?= htmlspecialchars($r['semua_nama']) ?>"
                   style="margin-top:4px;display:inline-block;padding:2px 8px;border-radius:6px;
                          background:#fef3c7;color:#92400e;font-size:.72rem;font-weight:600;cursor:help">
                Dipakai di <?= (int) $r['jumlah_nama'] ?> produk berbeda &mdash; arahkan kursor untuk melihat
              </div>
            <?php endif; ?>
          </td>
          <td class="angka"><?= number_format($r['baris'], 0, ',', '.') ?></td>
          <td style="white-space:nowrap;color:#64748b;font-size:.76rem">
            <?= htmlspecialchars($r['pertama']) ?><br><?= htmlspecialchars($r['terakhir']) ?>
          </td>
          <td><input class="tujuan" list="sku-<?= htmlspecialchars($r['brand']) ?>" placeholder="mis. 1MS"></td>
          <td><button type="button" class="simpan">Simpan</button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('click', function (e) {
  var btn = e.target.closest('.skub button.simpan');
  if (!btn) return;
  var tr = btn.closest('tr');
  var tujuan = tr.querySelector('input.tujuan').value.trim();
  if (!tujuan) { Swal.fire('SKU tujuan kosong', 'Isi SKU resmi dulu, misalnya 1MS.', 'warning'); return; }

  var fd = new FormData();
  fd.append('brand', tr.dataset.brand);
  fd.append('sku_marketplace', tr.dataset.sku);
  fd.append('sku_internal', tujuan);

  btn.disabled = true; btn.textContent = 'Menyimpan...';
  fetch('<?= base_url("product/sku_peta_simpan") ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .then(function (o) {
      if (!o.success) {
        btn.disabled = false; btn.textContent = 'Simpan';
        Swal.fire('Belum bisa', o.message, 'error');
        return;
      }
      var sisa = document.getElementById('sisaBaris');
      var n = parseInt(sisa.textContent.replace(/\./g, ''), 10) - parseInt(tr.dataset.baris, 10);
      sisa.textContent = Math.max(n, 0).toLocaleString('id-ID');
      tr.style.transition = 'opacity .3s'; tr.style.opacity = '0';
      setTimeout(function () { tr.remove(); }, 300);
      Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: o.message,
                  showConfirmButton: false, timer: 3500 });
    })
    .catch(function () {
      btn.disabled = false; btn.textContent = 'Simpan';
      Swal.fire('Gagal', 'Kesalahan jaringan.', 'error');
    });
});
</script>
