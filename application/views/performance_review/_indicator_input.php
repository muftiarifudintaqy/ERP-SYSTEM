<?php
/**
 * Partial: input skor untuk satu indikator.
 * Vars: $ind (indicator row), $existing (pa_scores row|null), $readonly (bool|null)
 */
$type = $ind['input_type'];
$cur_score = ($existing && $existing['score'] !== null) ? rtrim(rtrim((string) $existing['score'], '0'), '.') : '';
$cur_text = $existing['answer_text'] ?? '';
$iid = $ind['id'];
$max = (int) ($ind['max_score'] ?: 4);
$readonly = !empty($readonly);
$disabled_attr = $readonly ? ' disabled' : '';
$readonly_attr = $readonly ? ' readonly' : '';
?>
<?php if ($type === 'rubric'): ?>
    <div class="row g-2 mb-2">
        <?php for ($n = 1; $n <= 4; $n++): $d = trim($ind['level' . $n . '_desc'] ?? ''); ?>
            <div class="col-md-3">
                <div class="border rounded p-2 h-100" style="background:#fafafa;font-size:12px;">
                    <strong>Level <?= $n ?></strong><br><?= nl2br(htmlspecialchars($d)) ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>
    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label small mb-0">Pencapaian (skor)</label>
            <select class="form-select form-select-sm" name="score[<?= $iid ?>]"<?= $disabled_attr ?>>
                <option value="">-</option>
                <?php for ($v = 1; $v <= $max; $v++): ?>
                    <option value="<?= $v ?>" <?= ((string) $cur_score === (string) $v) ? 'selected' : '' ?>><?= $v ?></option>
                <?php endfor; ?>
            </select>
            <?php if ($ind['standard_score'] !== null): ?><small class="text-muted">Standar: <?= rtrim(rtrim((string) $ind['standard_score'], '0'), '.') ?></small><?php endif; ?>
        </div>
        <div class="col-md-9">
            <label class="form-label small mb-0">Keterangan</label>
            <input class="form-control form-control-sm" name="answer[<?= $iid ?>]" value="<?= htmlspecialchars($cur_text) ?>"<?= $readonly_attr ?>>
        </div>
    </div>

<?php elseif ($type === 'yesno'): ?>
    <div class="d-flex align-items-center gap-3 mb-2">
        <div class="form-check"><input class="form-check-input" type="radio" name="score[<?= $iid ?>]" value="1" id="y<?= $iid ?>" <?= ($cur_score === '1') ? 'checked' : '' ?><?= $disabled_attr ?>><label class="form-check-label" for="y<?= $iid ?>">Ya</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="score[<?= $iid ?>]" value="0" id="n<?= $iid ?>" <?= ($cur_score === '0') ? 'checked' : '' ?><?= $disabled_attr ?>><label class="form-check-label" for="n<?= $iid ?>">Tidak</label></div>
    </div>
    <input class="form-control form-control-sm" name="answer[<?= $iid ?>]" value="<?= htmlspecialchars($cur_text) ?>" placeholder="Keterangan (opsional)"<?= $readonly_attr ?>>

<?php elseif ($type === 'scale'): ?>
    <?php $opts = array_filter(array_map('trim', explode("\n", (string) $ind['options']))); ?>
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label small mb-0">Pilih Skala</label>
            <select class="form-select form-select-sm" name="score[<?= $iid ?>]"<?= $disabled_attr ?>>
                <option value="">-</option>
                <?php $idx2 = 0; foreach ($opts as $opt): $idx2++;
                    $val = preg_match('/^\s*(\d+(\.\d+)?)/', $opt, $mm) ? $mm[1] : $idx2; ?>
                    <option value="<?= $val ?>" <?= ((string) $cur_score === (string) $val) ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label small mb-0">Keterangan</label>
            <input class="form-control form-control-sm" name="answer[<?= $iid ?>]" value="<?= htmlspecialchars($cur_text) ?>"<?= $readonly_attr ?>>
        </div>
    </div>

<?php elseif ($type === 'percent'): ?>
    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label small mb-0">Pencapaian (%)</label>
            <div class="input-group input-group-sm"><input type="number" step="0.01" min="0" max="100" class="form-control" name="score[<?= $iid ?>]" value="<?= htmlspecialchars($cur_score) ?>"<?= $readonly_attr ?>><span class="input-group-text">%</span></div>
        </div>
        <div class="col-md-9">
            <label class="form-label small mb-0">Keterangan</label>
            <input class="form-control form-control-sm" name="answer[<?= $iid ?>]" value="<?= htmlspecialchars($cur_text) ?>"<?= $readonly_attr ?>>
        </div>
    </div>

<?php else: // text ?>
    <label class="form-label small mb-0">Jawaban / Keterangan</label>
    <textarea class="form-control form-control-sm" rows="2" name="answer[<?= $iid ?>]"<?= $readonly_attr ?>><?= htmlspecialchars($cur_text) ?></textarea>
<?php endif; ?>
