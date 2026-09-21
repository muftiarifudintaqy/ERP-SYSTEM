<style>
  .lc-kartu{background:#fff;border-radius:12px;padding:20px;margin-bottom:16px;
            box-shadow:0 1px 3px rgba(0,0,0,.06)}
  .lc-alat{display:flex;flex-wrap:wrap;gap:10px;align-items:end}
  .lc-alat label{font-size:12px;color:#64748b;display:block;margin-bottom:4px}
  .lc-baris{display:flex;align-items:center;gap:10px;padding:14px 16px;flex-wrap:wrap;
            border:1px solid #e5e7eb;border-radius:10px;margin-bottom:10px;background:#fff}
  .lc-nama{min-width:180px}
  .lc-aksi{display:flex;gap:8px;margin-left:auto;flex-wrap:wrap}
  .lc-btn{font-size:12.5px;padding:7px 14px}
  .lc-catatan{font-size:11.5px;color:#94a3b8;width:100%;margin-top:4px}
  .lc-baris:hover{border-color:#1F4696;background:#f8fafc}
  .lc-nama{flex:1;font-weight:600;color:#1f2937}
  .lc-jml{font-size:13px;color:#64748b;white-space:nowrap}
  .lc-pil{display:inline-block;padding:2px 10px;border-radius:999px;
          font-size:12px;font-weight:600}
  .p-belum{background:#FEE2E2;color:#991B1B}
  .p-sudah{background:#DCFCE7;color:#166534}
  .lc-btn{background:#1F4696;color:#fff;border:0;border-radius:8px;
          padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
  .lc-btn:disabled{background:#cbd5e1;cursor:not-allowed}
  .lc-rinci{display:none;padding:10px 16px 4px 44px}
  .lc-rinci table{width:100%;font-size:12.5px}
  .lc-rinci td{padding:5px 8px;border-bottom:1px solid #f1f5f9}
  .lc-kosong{text-align:center;padding:40px;color:#94a3b8}
</style>

<div class="container-fluid py-3">

  <?php
    $pesan_ok = $pesan_ok ?? NULL;
    $pesan_no = $pesan_no ?? NULL;
  ?>
  <?php if ($pesan_ok || $pesan_no): ?>
    <div id="lc-toast-overlay" style="position:fixed;inset:0;z-index:9999;
         display:flex;align-items:center;justify-content:center;
         background:rgba(15,23,42,0.35);padding:20px">
      <div style="background:<?= $pesan_ok ? '#EEF3FC' : '#EEF3FC' ?>;
                  border:1px solid <?= $pesan_ok ? '#2D5FC4' : '#2D5FC4' ?>;
                  color:<?= $pesan_ok ? '#1F4696' : '#1F4696' ?>;
                  padding:24px 28px;border-radius:16px;max-width:420px;width:100%;
                  font-size:15px;line-height:1.6;font-weight:600;text-align:center;
                  box-shadow:0 20px 50px rgba(0,0,0,0.25)">
        <?php /* nl2br supaya rincian barang tampil bertingkat, bukan
                 menyambung jadi satu paragraf panjang yang sulit dibaca
                 sambil berdiri di depan rak. */ ?>
        <?= nl2br(html_escape($pesan_ok ?: $pesan_no)) ?>
        <div style="margin-top:18px">
          <button onclick="document.getElementById('lc-toast-overlay').remove()"
                  style="background:<?= $pesan_ok ? '#1F4696' : '#1F4696' ?>;color:#fff;
                         border:none;padding:9px 22px;border-radius:8px;cursor:pointer;
                         font-size:14px;font-weight:600">OK</button>
        </div>
      </div>
    </div>
    <script>
      (function () {
        // Tidak ada auto-tutup lagi: popup cuma hilang kalau tombol OK
        // diklik, supaya pesannya sempat kebaca dulu.
        // Buang pesan_ok/pesan_no dari URL supaya kalau halaman ini
        // di-refresh manual, popupnya tidak muncul ulang terus-terusan.
        if (window.history && window.history.replaceState) {
          var u = new URL(window.location.href);
          u.searchParams.delete('pesan_ok');
          u.searchParams.delete('pesan_no');
          window.history.replaceState({}, '', u);
        }
      })();
    </script>
  <?php endif; ?>

  <div class="lc-kartu">
    <h4 style="margin:0 0 4px">Label Pengiriman</h4>
    <p style="color:#64748b;font-size:13px;margin-bottom:16px">
      Label dikelompokkan per produk. Sekali cetak menghasilkan satu jenis barang,
      jadi tidak perlu memilih satu per satu dari tumpukan.
    </p>

    <form method="get" action="<?= base_url('label') ?>" class="lc-alat">
      <div>
        <label>Dari tanggal</label>
        <input type="date" name="dari" value="<?= html_escape($dari) ?>" class="form-control" style="width:160px">
      </div>
      <div>
        <label>Sampai tanggal</label>
        <input type="date" name="sampai" value="<?= html_escape($sampai) ?>" class="form-control" style="width:160px">
      </div>
      <div>
        <label>Marketplace</label>
        <select name="mp" class="form-control" style="width:150px">
          <option value="">Semua</option>
          <option value="SHOPEE" <?= $mp === 'SHOPEE' ? 'selected' : '' ?>>Shopee</option>
          <option value="TIKTOK" <?= $mp === 'TIKTOK' ? 'selected' : '' ?>>TikTok</option>
        </select>
      </div>
      <div>
        <label>Status cetak</label>
        <select name="status" class="form-control" style="width:150px">
          <option value="">Semua</option>
          <option value="belum" <?= $status === 'belum' ? 'selected' : '' ?>>Belum dicetak</option>
          <option value="sudah" <?= $status === 'sudah' ? 'selected' : '' ?>>Sudah dicetak</option>
        </select>
      </div>
      <div>
        <label>Status order</label>
        <select name="os" class="form-control" style="width:170px">
          <option value="">Siap dikemas</option>
          <?php /* "Semua status" dihapus: pilihan itu memunculkan order yang
                   sudah dikirim atau batal, yang tidak akan pernah bisa
                   dicetak Shopee. Daftar ini sekarang selalu mengikuti apa
                   yang Shopee anggap siap kirim -- sama persis dengan
                   Seller Center, tidak bisa salah pilih. */ ?>
        </select>
      </div>
      <button class="lc-btn" type="submit">Tampilkan</button>
      <a href="<?= base_url('label/segarkan') ?>" class="lc-btn"
         style="background:#0f766e;border-color:#0f766e;color:#fff;
                margin-left:6px;text-decoration:none;display:inline-block"
         onclick="this.innerText='Mengambil...';this.style.pointerEvents='none'">
        Segarkan dari Shopee
      </a>
    </form>
  </div>

  <div class="lc-kartu">
    <?php if (empty($kelompok)): ?>
      <div class="lc-kosong">Tidak ada order pada rentang ini.</div>
    <?php else: ?>
      <?php $i = 0; foreach ($kelompok as $kunci => $k): $i++; ?>
        <?php
          // centang-semua
          // Yang belum dicetak didahulukan, tetapi yang sudah pun tetap
          // bisa dipilih: label kadang perlu dicetak ulang karena rusak,
          // hilang, atau tercetak miring.
          $belum_dulu = [];
          $sudah_juga = [];
          foreach ($k['order'] as $o) {
              if (empty($o['print_at'])) $belum_dulu[] = (int)$o['id'];
              else                       $sudah_juga[] = (int)$o['id'];
          }
          // Tidak dipotong 50 lagi: label/gabung memecah sendiri per 50
          // saat meminta ke Shopee, lalu menyatukan berkasnya jadi satu.
          $potong_awal = array_merge($belum_dulu, $sudah_juga);
        ?>
        <div class="lc-baris">
          <input type="checkbox" class="lc-pilih" style="width:18px;height:18px;flex:0 0 auto;cursor:pointer"
                 data-ids="<?= implode(',', $potong_awal) ?>"
                 data-kurir="<?= html_escape($k['kurir']) ?>"
                 <?= empty($potong_awal) ? 'disabled' : '' ?>>
          <div class="lc-nama">
            <?php $bagian = explode(' :: ', $k['nama']); ?>
            <?= html_escape($bagian[0]) ?>
            <?php if (isset($bagian[1])): ?>
              <div style="font-size:11.5px;color:#64748b;font-weight:400">
                <?= html_escape($bagian[1]) ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="lc-jml">
            <?= count($k['order']) ?> order
            <?php if ($k['belum']): ?>
              <span class="lc-pil p-belum"><?= $k['belum'] ?> belum</span>
            <?php endif; ?>
            <?php if ($k['sudah']): ?>
              <span class="lc-pil p-sudah"><?= $k['sudah'] ?> cetak</span>
            <?php endif; ?>
          </div>
          <?php
            $ids_belum = [];
            $ids_semua = [];
            foreach ($k['order'] as $o) {
                $ids_semua[] = (int)$o['id'];
                if (empty($o['print_at'])) $ids_belum[] = (int)$o['id'];
            }
          ?>
          <?php
            // batas-cetak
            // Shopee menolak permintaan yang terlalu besar, dan menunggu
            // ratusan label dalam satu kali tekan membuat layar seperti
            // menggantung. Dipotong per 50; sisanya dicetak lagi setelah
            // tumpukan pertama selesai dikerjakan.
            // Tidak dibatasi 50 lagi: label/gabung memecah sendiri per 50
            // saat meminta ke Shopee, lalu menyatukan berkasnya jadi satu.
            $BATAS = 9999;
            $potong = $potong_awal;   // ikut daftar yang sama dengan kotak centang
          ?>
          <div class="lc-aksi">
            <?php if ($potong): ?>
              <button class="lc-btn"
                      onclick="cetakKelompok('<?= implode(',', $potong) ?>', this)">
                Cetak <?= count($potong) ?> label
              </button>
            <?php endif; ?>
            <button class="lc-btn" style="background:#94a3b8"
                    onclick="document.getElementById('r<?= $i ?>').style.display =
                             document.getElementById('r<?= $i ?>').style.display==='block'?'none':'block'">
              Rincian
            </button>
          </div>
        </div>

        <div class="lc-rinci" id="r<?= $i ?>">
          <table>
            <?php foreach ($k['order'] as $o): ?>
              <tr>
                <td style="font-family:monospace"><?= html_escape($o['order_id']) ?></td>
                <td><?= html_escape($o['mp']) ?></td>
                <td style="font-family:monospace;font-size:11.5px"><?= html_escape($o['awb']) ?></td>
                <td>
                  <?php if (!empty($o['print_at'])): ?>
                    <span class="lc-pil p-sudah"><?= date('d/m H:i', strtotime($o['print_at'])) ?></span>
                  <?php else: ?>
                    <span class="lc-pil p-belum">belum</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($o['pdf'])): ?>
                    <a href="<?= html_escape($o['pdf']) ?>" target="_blank">Lihat PDF</a>
                  <?php else: ?>
                    <span style="color:#cbd5e1">-</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<div id="lcBatang" style="display:none;position:fixed;left:0;right:0;bottom:0;z-index:1050;
     background:#1F4696;color:#fff;padding:12px 20px;box-shadow:0 -2px 12px rgba(0,0,0,.2);
     align-items:center;gap:16px">
  <span id="lcJumlah" style="font-size:14px;font-weight:600"></span>
  <span id="lcCatatan" style="font-size:12px;opacity:.85"></span>
  <button onclick="cetakTerpilih(this)" style="margin-left:auto;background:#fff;color:#1F4696;
          border:0;border-radius:8px;padding:9px 20px;font-size:13.5px;font-weight:700;cursor:pointer">
    Cetak yang dicentang
  </button>
  <button onclick="document.querySelectorAll('.lc-pilih').forEach(c=>c.checked=false);hitungPilih()"
          style="background:transparent;color:#fff;border:1px solid rgba(255,255,255,.5);
          border-radius:8px;padding:9px 16px;font-size:13px;cursor:pointer">Batal</button>
</div>

<script>
// Alur cetaknya sama dengan halaman Order: id dikirim ke
// shipping-documents-execute, jawabannya berisi muatan yang dibuka
// sebagai satu berkas PDF. Sudah terurut per produk di sisi server,
// jadi satu berkas berisi satu jenis barang berurutan.
var URL_GABUNG = '<?= base_url('label/gabung') ?>';
var URL_EXEC   = '<?= base_url('transaction/shipping-documents-execute') ?>';
var URL_SHOPEE = '<?= base_url('transaction/print-shipping-docs-shopee') ?>';
var URL_UMUM   = '<?= base_url('transaction/print-shipping-docs') ?>';

function bukaBerkas(alamat, muatan, medan) {
  if (!muatan) return;
  var f = document.createElement('form');
  f.method = 'POST'; f.action = alamat; f.target = '_blank';
  var i = document.createElement('input');
  i.type = 'hidden'; i.name = medan || 'payload'; i.value = muatan;
  f.appendChild(i); document.body.appendChild(f); f.submit(); f.remove();
}

function cetakKelompok(ids, tombol) {
  if (!ids) return;

  // Lewat label/gabung, bukan jalur lama: Shopee menolak permintaan
  // berisi lebih dari 50 label sekaligus, dan penggabung di server
  // memecahnya sendiri per 50 lalu menyatukan berkasnya jadi satu.
  bukaBerkas(URL_GABUNG, ids, 'ids');
  setTimeout(function () { location.reload(); }, 4000);
}

// cetakTerpilih
// Beberapa kelompok dicetak sekaligus. Shopee menolak menggabungkan
// label dari jasa kirim berbeda dalam satu berkas, jadi yang dicentang
// dikelompokkan dulu per kurir: satu kurir menghasilkan satu berkas.
function hitungPilih() {
  var pilih = Array.from(document.querySelectorAll('.lc-pilih:checked'));
  var batang = document.getElementById('lcBatang');
  if (!pilih.length) { batang.style.display = 'none'; return; }

  var jml = 0, kurir = {};
  pilih.forEach(function (c) {
    jml += c.dataset.ids.split(',').filter(Boolean).length;
    kurir[c.dataset.kurir] = true;
  });
  var nKurir = Object.keys(kurir).length;

  document.getElementById('lcJumlah').innerText = jml + ' label dipilih';
  document.getElementById('lcCatatan').innerText = nKurir > 1
    ? '(' + nKurir + ' kurir berbeda, keluar ' + nKurir + ' berkas)'
    : '';
  batang.style.display = 'flex';
}

document.querySelectorAll('.lc-pilih').forEach(function (c) {
  c.addEventListener('change', hitungPilih);
});

function cetakTerpilih(tombol) {
  var pilih = Array.from(document.querySelectorAll('.lc-pilih:checked'));
  if (!pilih.length) return;

  var semua = [];
  pilih.forEach(function (c) {
    c.dataset.ids.split(',').filter(Boolean).forEach(function (i) { semua.push(i); });
  });

  // Dikirim ke penggabung: berkas per kurir disatukan di server,
  // sehingga yang terbuka hanya satu tab untuk seluruh tumpukan.
  bukaBerkas(URL_GABUNG, semua.join(','), 'ids');
  setTimeout(function () { location.reload(); }, 4000);
}
</script>
