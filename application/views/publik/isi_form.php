<?php $lama = $lama ?? []; ?>
<div class="publik-kepala">
  <div class="garis"></div>
  <h1><?= html_escape($f['judul']) ?></h1>
  <?php if ($f['deskripsi']): ?><p><?= nl2br(html_escape($f['deskripsi'])) ?></p><?php endif; ?>
</div>

<style>
/* skala penilaian - ditanam di halaman supaya tidak terpengaruh cache */
.publik .skala{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
.publik .skala-butir{flex:1 1 46px;min-width:46px;margin:0;cursor:pointer}
.publik .skala-butir input{position:absolute;opacity:0;width:0;height:0}
.publik .skala-kotak{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;
  text-align:center;padding:11px 4px;border:1px solid #DCE3DF;border-radius:9px;background:#fff;
  font-weight:650;font-size:.95rem;color:#16211E;transition:.12s;min-height:44px}
.publik .skala-kotak em{font-style:normal;font-weight:500;font-size:.68rem;line-height:1.2;color:#7C8A85}
.publik .skala-butir:hover .skala-kotak{border-color:#1F6F5C;background:#E4F0EB}
.publik .skala-butir input:checked + .skala-kotak{background:#1F6F5C;border-color:#1F6F5C;color:#fff}
.publik .skala-butir input:checked + .skala-kotak em{color:rgba(255,255,255,.85)}
.publik .skala-butir input:focus-visible + .skala-kotak{outline:2px solid #1F6F5C;outline-offset:2px}
.publik .skala-berlabel .skala-butir{flex:1 1 84px;min-width:84px}
.publik .skala-arti{display:flex;justify-content:space-between;gap:14px;margin-top:7px;
  font-size:.78rem;color:#7C8A85;line-height:1.3}
.publik .skala-arti span:last-child{text-align:right}
@media (max-width:560px){
  .publik .skala-butir{flex:1 1 40px;min-width:40px}
  .publik .skala-kotak{padding:9px 2px;font-size:.85rem;min-height:38px}
  .publik .skala-berlabel .skala-butir{flex:1 1 100%;min-width:100%}
  .publik .skala-berlabel .skala-kotak{flex-direction:row;gap:9px;justify-content:flex-start;padding:10px 12px}
}
</style>

<form method="post" enctype="multipart/form-data">
  <div class="kartu">
    <div class="grid g2">
      <div class="isian"><label>Nama lengkap <span class="wajib">*</span></label><input type="text" name="_nama" value="<?= html_escape($lama['_nama'] ?? '') ?>" required></div>
      <div class="isian"><label>Email <span class="wajib">*</span></label><input type="email" name="_email" value="<?= html_escape($lama['_email'] ?? '') ?>" required></div>
    </div>
  </div>

  <?php if (!$fields): ?>
    <div class="kartu kosong"><b>Formulir ini belum punya pertanyaan</b>Hubungi HRD.</div>
  <?php else: ?>
    <div class="kartu">
      <?php foreach ($fields as $fd):
        $key  = 'f_' . $fd['id'];
        $nilai = $lama[$key] ?? '';
        $opsi  = array_filter(array_map('trim', explode("\n", (string)$fd['opsi'])));
        $wajib = $fd['wajib'] ? 'required' : '';
      ?>
        <?php if ($fd['tipe'] === 'judul'): ?>
          <h2 style="margin-top:22px; padding-top:16px; border-top:1px solid var(--garis)"><?= html_escape($fd['label']) ?></h2>
          <?php if ($fd['bantuan']): ?><p class="label"><?= html_escape($fd['bantuan']) ?></p><?php endif; ?>
          <?php continue; ?>
        <?php endif; ?>

        <div class="isian">
          <label><?= html_escape($fd['label']) ?> <?= $fd['wajib'] ? '<span class="wajib">*</span>' : '' ?></label>
          <?php if ($fd['bantuan']): ?><p class="bantu"><?= html_escape($fd['bantuan']) ?></p><?php endif; ?>

          <?php if ($fd['tipe'] === 'textarea'): ?>
            <textarea name="<?= $key ?>" <?= $wajib ?>><?= html_escape($nilai) ?></textarea>

          <?php elseif ($fd['tipe'] === 'select'): ?>
            <select name="<?= $key ?>" <?= $wajib ?>>
              <option value="">- pilih -</option>
              <?php foreach ($opsi as $o): ?>
                <option <?= $nilai === $o ? 'selected' : '' ?>><?= html_escape($o) ?></option>
              <?php endforeach; ?>
            </select>

          <?php elseif ($fd['tipe'] === 'radio'): ?>
            <div class="pilihan" style="flex-direction:column; gap:8px">
              <?php foreach ($opsi as $o): ?>
                <label><input type="radio" name="<?= $key ?>" value="<?= html_escape($o) ?>" <?= $nilai === $o ? 'checked' : '' ?> <?= $wajib ?>> <?= html_escape($o) ?></label>
              <?php endforeach; ?>
            </div>

          <?php elseif ($fd['tipe'] === 'checkbox'): ?>
            <div class="pilihan" style="flex-direction:column; gap:8px">
              <?php foreach ($opsi as $o): ?>
                <label><input type="checkbox" name="<?= $key ?>[]" value="<?= html_escape($o) ?>"> <?= html_escape($o) ?></label>
              <?php endforeach; ?>
            </div>

          <?php elseif ($fd['tipe'] === 'skala'):
            $min  = (int)($fd['skala_min'] ?: 1);
            $maks = (int)($fd['skala_maks'] ?: 10);
            $per  = array_values(array_filter(array_map('trim', explode("\n", (string)$fd['opsi']))));
            $pakai_per = count($per) === ($maks - $min + 1);
          ?>
            <div class="skala<?= $pakai_per ? ' skala-berlabel' : '' ?>">
              <?php for ($n = $min; $n <= $maks; $n++): ?>
                <label class="skala-butir">
                  <input type="radio" name="<?= $key ?>" value="<?= $n ?>" <?= $nilai === (string)$n ? 'checked' : '' ?> <?= $wajib ?>>
                  <span class="skala-kotak">
                    <b><?= $n ?></b>
                    <?php if ($pakai_per): ?><em><?= html_escape($per[$n - $min]) ?></em><?php endif; ?>
                  </span>
                </label>
              <?php endfor; ?>
            </div>
            <?php if (!$pakai_per && ($fd['label_rendah'] || $fd['label_tinggi'])): ?>
              <div class="skala-arti">
                <span><?= (int)$fd['skala_min'] ?: 1 ?> &mdash; <?= html_escape($fd['label_rendah']) ?></span>
                <span><?= html_escape($fd['label_tinggi']) ?> &mdash; <?= (int)$fd['skala_maks'] ?: 10 ?></span>
              </div>
            <?php endif; ?>

          <?php elseif ($fd['tipe'] === 'file'): ?>
            <input type="file" name="<?= $key ?>" <?= $wajib ?>>

          <?php else: ?>
            <input type="<?= $fd['tipe'] ?>" name="<?= $key ?>" value="<?= html_escape($nilai) ?>" <?= $wajib ?>>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <button class="btn btn-utama" type="submit" style="width:100%; justify-content:center; padding:13px">Kirim jawaban</button>
  <?php endif; ?>
</form>
