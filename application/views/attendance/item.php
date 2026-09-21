<?php
if (empty($data)) {
    echo '<tr><td colspan="12" class="text-center text-muted">Tidak ada data</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;
        $on_leave = !empty($value['on_leave']);

        // Status badge
        // --- status kehadiran (Hadir / Terlambat / dst) ---
        $ml = $value['masuk_late_status'] ?? null;
        $bl = $value['break_late_status'] ?? null;

        if (empty($value['check_in_at'])) {
            $status_badge = '<span class="badge badge-hadir" style="background:#fafafa;color:rgba(0,0,0,.45);border:1px solid #d9d9d9;">Belum Absen</span>';
        } elseif ($ml === 'telat') {
            $status_badge = '<span class="badge badge-hadir" style="background:#fffbe6;color:#d48806;border:1px solid #ffe58f;">&#9200; Terlambat</span>';
        } else {
            $status_badge = '<span class="badge badge-hadir" style="background:#f6ffed;color:#389e0d;border:1px solid #b7eb8f;">&#10003; Hadir</span>';
        }

        if ($bl === 'telat_after_break') {
            $status_badge .= '<br><span class="badge badge-hadir mt-1" style="background:#fff2f0;color:#cf1322;border:1px solid #ffccc7;">Terlambat After Break</span>';
        } elseif ($bl === 'after_break') {
            $status_badge .= '<br><span class="badge badge-hadir mt-1" style="background:#f0f5ff;color:#2f54eb;border:1px solid #adc6ff;">After Take Break</span>';
        }

        if (!empty($value['check_out_at'])) {
            // Pulang yang dicatat sistem karena lupa menekan tombol
            // ditandai berbeda, supaya HRD bisa membedakannya dari
            // yang benar-benar absen sendiri.
            $lupa = !empty($value['pulang_suspect'])
                 && strpos($value['pulang_suspect'], 'lupa absen') !== FALSE;
            if ($lupa) {
                $status_badge .= '<br><span class="badge badge-hadir mt-1 lupa-absen" style="background:#fff7e6;color:#d46b08;border:1px solid #ffd591;">Lupa Absen Pulang</span>';
            } else {
                $status_badge .= '<br><span class="badge badge-hadir mt-1" style="background:#f9f0ff;color:#722ed1;border:1px solid #d3adf7;">Back To Home</span>';
            }
        }

        if ($value['status'] !== 'valid' && !empty($value['check_in_at'])) {
            $status_badge .= '<br><span class="badge badge-hadir mt-1" style="background:#fff2f0;color:#ff4d4f;border:1px solid #ffccc7;">Tidak Tervalidasi</span>';
        }
        // Tandai jika karyawan sedang cuti di tanggal tsb (tidak dihitung hadir).
        if ($on_leave) {
            $status_badge .= ' <span class="badge" style="background-color: #fffbe6; color: #faad14; border: 1px solid #ffe58f;">Cuti</span>';
        }
        // Absen dari rumah pada hari WFH terjadwal.
        if (($value['masuk_mode'] ?? '') === 'wfh') {
            $status_badge .= ' <span class="badge badge-wfh" style="background:#fff7e6;color:#d46b08;border:1px solid #ffd591;">&#127968; WFH</span>';
        }

        // Jadwal di luar jam reguler, misalnya host live. Ditampilkan
        // supaya jam masuk 14:00 tidak terbaca sebagai keterlambatan.
        if (!empty($value['jadwal_masuk']) && substr($value['jadwal_masuk'], 0, 5) !== '08:00') {
            $status_badge .= ' <span class="badge" style="background:#f0f5ff;color:#2f54eb;border:1px solid #adc6ff;">Jadwal '
                . substr($value['jadwal_masuk'], 0, 5) . '</span>';
        }


        // Validation pills
        $dtype = $value['masuk_device_type'] ?? null;
        $is_pc = ($dtype === 'desktop');

        $flag_pill = !empty($value['masuk_suspect'])
            ? '<span class="val-pill val-flag" title="' . htmlspecialchars($value['masuk_suspect']) . '">&#128681; Curiga</span>'
            : '';
        $os_label = $value['masuk_device_os'] ?? null;
        if (!$os_label && $dtype) { $os_label = $is_pc ? 'PC' : 'HP'; }
        $tipe_pill = $os_label
            ? '<span class="val-pill val-pc" title="Perangkat yang dipakai saat absen">' . htmlspecialchars($os_label) . '</span>'
            : '';

        $dev_pill = '<span class="val-pill ' . ($value['device_valid'] ? 'val-ok' : 'val-no') . '" title="Perangkat terdaftar &amp; disetujui">Device</span>';
        $ip_pill  = '<span class="val-pill ' . ($value['ip_valid'] ? 'val-ok' : 'val-no') . '" title="IP jaringan kantor">IP</span>';

        // Penanda mengikuti apa yang benar-benar diperiksa pada absen itu,
        // bukan jenis perangkatnya. Laptop punya kamera, jadi wajahnya tetap
        // dicocokkan; menandainya abu membuat HRD mengira pemeriksaan itu
        // dilewati padahal tidak.
        $ada_gps   = isset($value['distance_m']) && $value['distance_m'] !== NULL;
        $ada_wajah = !empty($value['masuk_photo']) || !empty($value['masuk_face_valid']);

        if ($ada_gps) {
            $gps_pill = '<span class="val-pill ' . ($value['gps_valid'] ? 'val-ok' : 'val-no')
                      . '" title="GPS dalam radius kantor">GPS</span>';
        } else {
            $gps_pill = '<span class="val-pill val-na" title="Perangkat tidak mengirim lokasi">GPS -</span>';
        }

        if ($ada_wajah) {
            $face_pill = '<span class="val-pill ' . (!empty($value['masuk_face_valid']) ? 'val-ok' : 'val-no')
                       . '" title="Wajah cocok dengan data terdaftar">Wajah</span>';
        } else {
            $face_pill = '<span class="val-pill val-na" title="Absen tanpa foto wajah">Wajah -</span>';
        }

        $break_out = !empty($value['break_out_at']) ? date('H:i', strtotime($value['break_out_at'])) : '-';
        $break_in  = !empty($value['break_in_at'])  ? date('H:i', strtotime($value['break_in_at']))  : '-';
        $check_in = !empty($value['check_in_at']) ? date('H:i', strtotime($value['check_in_at'])) : '-';
        $check_out = !empty($value['check_out_at']) ? date('H:i', strtotime($value['check_out_at'])) : '-';
        $att_date = !empty($value['attendance_date']) ? date('d M Y', strtotime($value['attendance_date'])) : '-';
?>
        <tr>
            <td><?= $num ?></td>
            <td><?= $att_date ?></td>
            <td class="col-nama">
                <strong><?= htmlspecialchars($value['full_name'] ?? 'Tidak diketahui') ?></strong>
            </td>
            <td class="col-posisi"><?= htmlspecialchars($value['position_name'] ?? '-') ?></td>
            <td><?= $check_in ?></td>
            <td><?= $break_out ?></td>
            <td><?= $break_in ?></td>
            <td><?= $check_out ?></td>
            <td class="col-validasi"><?= $flag_pill . $tipe_pill . $gps_pill . $face_pill . $dev_pill . $ip_pill ?></td>
            <td>
                <?php if (!empty($value['masuk_photo'])): ?>
                    <img src="<?= base_url() . $value['masuk_photo'] ?>" class="fo-thumb" alt=""
                         data-nm="<?= htmlspecialchars($value['full_name'] ?? '') ?>"
                         data-tg="<?= $att_date ?>" title="Klik untuk perbesar">
                    <?php if (!empty($value['masuk_face_distance'])):
                        $fd = (float)$value['masuk_face_distance']; ?>
                        <span class="fo-dist <?= $fd > 0.42 ? 'fo-warn' : 'fo-ok' ?>"><?= number_format($fd, 2) ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted" style="font-size:.75rem">-</span>
                <?php endif; ?>
            </td>
            <td><?= $status_badge ?></td>
            <td><button class="btn btn-sm btn-outline-primary btn-edit-jam me-1" data-id="<?= $value['id'] ?>" title="Edit jam"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-danger btn-hapus-absen" data-id="<?= $value['id'] ?>" data-nm="<?= htmlspecialchars($value['full_name'] ?? '-') ?>" data-tg="<?= $att_date ?>" title="Hapus"><i class="bi bi-trash3"></i></button></td>
        </tr>
<?php
    }
}
?>
