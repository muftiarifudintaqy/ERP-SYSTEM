<?php
$k = $start;
if (empty($data)) {
    echo '<tr><td colspan="8" class="text-center">No side quests found.</td></tr>';
} else {
    foreach ($data as $v) {
        $description_preview = trim(html_entity_decode(strip_tags($v['description']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $description_preview = strlen($description_preview) > 100 ? substr($description_preview, 0, 100) . '...' : $description_preview;
?>
    <tr>
        <td class="text-start"><?= $k + 1 ?></td>
        <td class="text-start">
            <div class="d-flex align-items-center">
                <?php if (!empty($v['gambar_animasi'])): ?>
                    <i class="bi bi-image text-success me-2" title="Has animation image"></i>
                <?php endif; ?>
                <strong><?= $v['title'] ?></strong>
            </div>
        </td>
        <td class="text-start">
            <span style="color: rgba(0,0,0,0.65); font-size: 13px;">
                <?= htmlspecialchars($description_preview, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </td>
        <td class="text-center">
            <span class="badge" style="background-color: #fffbe6; color: #faad14; border: 1px solid #ffe58f; font-size: 14px;">
                <i class="bi bi-star-fill me-1"></i><?= $v['points'] ?? 0 ?>
            </span>
        </td>
        <td class="text-start">
            <span style="color: rgba(0,0,0,0.65); font-size: 13px;" title="<?= $v['reward'] ?? 'No reward description' ?>">
                <?= !empty($v['reward']) ? (strlen($v['reward']) > 50 ? substr($v['reward'], 0, 50) . '...' : $v['reward']) : 'No reward' ?>
            </span>
        </td>
        <td class="text-start">
            <span style="color: rgba(0,0,0,0.65); font-size: 13px;"><?= $v['creator_name'] ?></span>
        </td>
        <td class="text-start">
            <span style="color: rgba(0,0,0,0.65); font-size: 13px;">
                <?= date('d M Y', strtotime($v['created_at'])) ?>
            </span>
        </td>
        <td class="text-end">
            <a href="<?= base_url() ?>quest/side_quest_detail?id=<?= $v['id'] ?>" 
               class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Detail">
                <i class="bi bi-eye"></i>
            </a>
            <a href="<?= base_url() ?>quest/side_quest_edit_page?id=<?= $v['id'] ?>" 
               class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Edit">
                <i class="bi bi-pencil"></i>
            </a>
            <a href="#!" onclick="removeSideQuest('<?= $v['id'] ?>')" 
               style="color: #ff4d4f; font-size: 14px;" title="Hapus">
                <i class="bi bi-trash"></i>
            </a>
        </td>
    </tr>
<?php 
    $k += 1;
    } // end foreach
} // end if ?>
