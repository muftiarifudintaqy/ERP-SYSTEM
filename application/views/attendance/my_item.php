<?php
if (empty($data)) {
    echo '<tr><td colspan="4" class="text-center text-muted">Belum ada riwayat absensi</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;

        $check_in = !empty($value['check_in_at']) ? date('H:i', strtotime($value['check_in_at'])) : '-';
        $check_out = !empty($value['check_out_at']) ? date('H:i', strtotime($value['check_out_at'])) : '-';
        $att_date = !empty($value['attendance_date']) ? date('d M Y', strtotime($value['attendance_date'])) : '-';
?>
        <tr>
            <td><?= $num ?></td>
            <td><?= $att_date ?></td>
            <td><?= $check_in ?></td>
            <td><?= $check_out ?></td>
        </tr>
<?php
    }
}
?>
