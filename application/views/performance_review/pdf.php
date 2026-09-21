<?php
$r = $review;
$fmt = function ($v) {
    if ($v === null || $v === '') return '';
    return rtrim(rtrim((string) $v, '0'), '.');
};
$leader = '';
$hr = '';
foreach ($approvers as $a) {
    if ($a['reviewer_role'] === 'leader' && $leader === '') $leader = $a['full_name'];
    if ($a['reviewer_role'] === 'hr' && $hr === '') $hr = $a['full_name'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 24px 28px; }
    * { font-family: "DejaVu Sans", sans-serif; }
    body { color: #1a1a1a; font-size: 10px; }
    .head { border-bottom: 3px solid #1a1a1a; padding-bottom: 8px; margin-bottom: 14px; }
    .head .company { font-size: 13px; font-weight: bold; }
    .head .addr { font-size: 9px; color: #333; }
    h2.title { font-size: 13px; margin: 0 0 12px; }
    .meta td { padding: 2px 4px; font-size: 10px; }
    table.grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.grid th, table.grid td { border: 1px solid #888; padding: 5px 6px; vertical-align: top; font-size: 9px; }
    table.grid th { background: #f0f0f0; text-align: center; font-weight: bold; }
    .ind { width: 11%; font-weight: bold; }
    .lvl { width: 17%; }
    .std, .ach { width: 7%; text-align: center; vertical-align: middle; }
    .total-row td { font-weight: bold; background: #f7f7f7; text-align: center; }
    .sign { width: 100%; margin-top: 28px; }
    .sign td { width: 33%; text-align: center; font-size: 10px; vertical-align: top; padding: 4px; }
    .sign .name { margin-top: 48px; font-weight: bold; text-decoration: underline; }
    .grade td { padding: 3px 6px; font-size: 9px; }
    .muted { color: #555; }
</style>
</head>
<body>
    <div class="head">
        <div class="company">PT MONTERA SINERGI BERSAMA</div>
        <div class="addr">Jl. KH. Wahid Hasyim, Dusun Maron, Genteng Kulon, Kec. Genteng, Kabupaten Banyuwangi, Jawa Timur 68465</div>
        <div class="addr">Hp. 085162567742 / Email : kontak@Montera.co.id</div>
    </div>

    <h2 class="title">PERFORMANCE &amp; CULTURE REVIEW FORM</h2>
    <table class="meta">
        <tr><td style="width:120px;">Nama Karyawan</td><td>: <?= htmlspecialchars($r['reviewee_name']) ?></td></tr>
        <tr><td>Periode Review</td><td>: <?= htmlspecialchars($r['period_label']) ?></td></tr>
        <tr><td>Template</td><td>: <?= htmlspecialchars($r['template_name']) ?></td></tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th rowspan="2" class="ind">Indikator Penilaian</th>
                <th colspan="4">Detail Penilaian</th>
                <th rowspan="2" class="std">Standar Penilaian</th>
                <th rowspan="2" class="ach">Pencapaian</th>
            </tr>
            <tr>
                <th class="lvl">1</th><th class="lvl">2</th><th class="lvl">3</th><th class="lvl">4</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($indicators as $ind):
                $s = $finals[$ind['id']] ?? null;
                $ach = ($s && $s['score'] !== null) ? $fmt($s['score']) : '';
                $ans = $s['answer_text'] ?? '';
            ?>
            <tr>
                <td class="ind"><?= htmlspecialchars($ind['name']) ?>
                    <?php if (!empty($ind['category'])): ?><br><span class="muted" style="font-weight:normal;font-size:8px;"><?= htmlspecialchars($ind['category']) ?></span><?php endif; ?>
                </td>
                <?php if ($ind['input_type'] === 'rubric'): ?>
                    <td class="lvl"><?= nl2br(htmlspecialchars(trim($ind['level1_desc'] ?? ''))) ?></td>
                    <td class="lvl"><?= nl2br(htmlspecialchars(trim($ind['level2_desc'] ?? ''))) ?></td>
                    <td class="lvl"><?= nl2br(htmlspecialchars(trim($ind['level3_desc'] ?? ''))) ?></td>
                    <td class="lvl"><?= nl2br(htmlspecialchars(trim($ind['level4_desc'] ?? ''))) ?></td>
                <?php else: ?>
                    <td colspan="4">
                        <span class="muted">Tipe: <?= htmlspecialchars($ind['input_type']) ?></span>
                        <?php if (!empty($ind['options'])): ?><br><?= nl2br(htmlspecialchars($ind['options'])) ?><?php endif; ?>
                        <?php if (!empty($ans)): ?><br><strong>Catatan:</strong> <?= nl2br(htmlspecialchars($ans)) ?><?php endif; ?>
                    </td>
                <?php endif; ?>
                <td class="std"><?= $ind['standard_score'] !== null ? $fmt($ind['standard_score']) : '' ?></td>
                <td class="ach"><?= $ind['input_type'] === 'percent' && $ach !== '' ? $ach . '%' : $ach ?></td>
            </tr>
            <?php if ($ind['input_type'] === 'rubric' && !empty($ans)): ?>
                <tr><td></td><td colspan="4" class="muted"><strong>Catatan:</strong> <?= nl2br(htmlspecialchars($ans)) ?></td><td></td><td></td></tr>
            <?php endif; ?>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="5">Total Penilaian Performance &amp; Culture</td>
                <td class="std"><?= $fmt($totals['target']) ?></td>
                <td class="ach"><?= $fmt($totals['total']) ?></td>
            </tr>
        </tbody>
    </table>

    <?php if (!empty($bands)): ?>
    <table class="grid" style="margin-top:6px;width:50%;">
        <tr><th colspan="2">Konversi Penilaian</th></tr>
        <?php foreach ($bands as $b): ?>
            <tr class="grade"><td style="text-align:center;width:40%;"><?= $fmt($b['min']) ?>% &ndash; <?= $fmt($b['max']) ?>%</td><td><?= htmlspecialchars($b['label']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!empty($r['final_grade'])): ?><tr class="grade"><td style="text-align:center;"><strong>Hasil</strong></td><td><strong><?= htmlspecialchars($r['final_grade']) ?></strong></td></tr><?php endif; ?>
    </table>
    <?php endif; ?>

    <p style="margin-top:12px;font-size:10px;"><strong>Sumber/Bukti:</strong><br><?= !empty($r['sources']) ? nl2br(htmlspecialchars($r['sources'])) : '-' ?></p>

    <p style="margin-top:8px;font-size:10px;"><strong>Persetujuan</strong></p>
    <table class="sign">
        <tr>
            <td>Mengetahui,<div class="name"><?= htmlspecialchars($r['reviewee_name']) ?></div>Karyawan</td>
            <td>Menyetujui,<div class="name"><?= $leader !== '' ? htmlspecialchars($leader) : '&nbsp;' ?></div>Leader</td>
            <td>Menyetujui,<div class="name"><?= $hr !== '' ? htmlspecialchars($hr) : '&nbsp;' ?></div>HR</td>
        </tr>
    </table>
</body>
</html>
