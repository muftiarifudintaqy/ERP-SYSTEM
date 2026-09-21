<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Laporan keterlambatan harian ke grup WhatsApp.
 *
 * Patokannya BUKAN late_status, melainkan jam masuk murni tanpa
 * toleransi. Toleransi 5 menit tetap berlaku di sistem absensi untuk
 * penilaian internal, tetapi laporan yang dibagikan ke grup memakai
 * jam masuk apa adanya: lewat 08:00 berarti tercatat terlambat.
 *
 * Kode unik per tanggal membuat laporan yang sama tidak pernah masuk
 * antrean dua kali, walau cronnya kebetulan jalan berulang.
 */
class Laporantelat extends CI_Controller
{
    public function kirim()
    {
        if (!$this->input->is_cli_request()) show_404();

        $tgl  = date('Y-m-d');
        $kode = 'telat-' . $tgl;

        if ($this->db->where('kode', $kode)->count_all_results('wa_pesan_teks')) {
            echo "Sudah ada di antrean.\n";
            return;
        }

        // Semua absen masuk hari ini; telat atau tidak dihitung di bawah.
        $masuk = $this->db->select('p.user_id, p.punched_at, u.full_name,
                                    pos.name AS posisi, u.role_text,
                                    j.nama_jadwal, j.jam_masuk')
            ->from('attendance_punches p')
            ->join('user u', 'u.id = p.user_id')
            ->join('user_profile up', 'up.user_id = u.id', 'left')
            ->join('positions pos', 'pos.id = up.position_id', 'left')
            ->join('hrd_jadwal j', 'j.user_id = u.id AND j.aktif = 1', 'left')
            ->where('p.kind', 'masuk')
            ->where('p.status', 'valid')
            ->where('DATE(p.punched_at)', $tgl)
            ->order_by('p.punched_at', 'ASC')
            ->get()->result_array();

        // Shift host live hari ini. hrd_jadwal cuma menyimpan patokan
        // dasar 08:00, sedangkan yang shift 2 masuknya 15:00.
        $shift = [];
        foreach ($this->db->where('tanggal', $tgl)
                          ->get('live_shift_harian')->result_array() as $s) {
            $shift[(int)$s['user_id']] = (int)$s['shift'];
        }

        $telat = [];
        foreach ($masuk as $m) {
            $uid    = (int)$m['user_id'];
            $divisi = $m['posisi'] ?: ($m['role_text'] ?: '-');
            $jam    = $m['jam_masuk'] ?: '08:00:00';

            if (($m['nama_jadwal'] ?? '') === 'Host Live' && isset($shift[$uid])) {
                $jam    = ($shift[$uid] === 2) ? '15:00:00' : '08:00:00';
                $divisi = 'Host Live Shift ' . $shift[$uid];
            }

            // Detik dipotong, bukan dibulatkan. Absen 08:03:30 harus
            // terbaca telat 3 menit sesuai jam yang tertulis di layar,
            // bukan 4 menit karena pembulatan ke atas.
            $selisih = strtotime(date('H:i:s', strtotime($m['punched_at'])))
                     - strtotime($jam);

            // Yang menentukan telat atau tidak tetap menitnya, bukan
            // detiknya: absen 08:00:43 masih menit ke-08:00, jadi belum
            // terlambat. Detik hanya ditampilkan supaya jelas seberapa
            // tipis selisihnya -- telat 1 menit 3 detik berbeda rasanya
            // dengan telat 1 menit 58 detik, dan yang menilai orang.
            $menit = (int)floor($selisih / 60);
            if ($menit <= 0) continue;

            $telat[] = [
                'nama'   => $m['full_name'],
                'divisi' => $divisi,
                'jam'    => date('H.i.s', strtotime($m['punched_at'])),
                'menit'  => $menit,
                'detik'  => $selisih % 60,
            ];
        }

        // Administrator (id 1) akun sistem, bukan orang yang absen,
        // jadi tidak ikut dihitung sebagai karyawan.
        $aktif = (int)$this->db->where('status', 'Aktif')
                               ->where('id !=', 1)
                               ->count_all_results('user');

        $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $bln  = ['','Januari','Februari','Maret','April','Mei','Juni','Juli',
                 'Agustus','September','Oktober','November','Desember'];

        $b   = [];
        $b[] = '*LAPORAN KETERLAMBATAN*';
        $b[] = $hari[(int)date('w')] . ', ' . date('j') . ' '
             . $bln[(int)date('n')] . ' ' . date('Y');
        $b[] = '';

        if (empty($telat)) {
            $b[] = 'Hari ini tidak ada keterlambatan.';
        } else {
            foreach ($telat as $t) {
                $b[] = $t['nama'] . ' - ' . $t['divisi'];
                $b[] = 'Absen ' . $t['jam'] . ' ( telat ' . $t['menit'] . ' menit '
                     . $t['detik'] . ' detik )';
                $b[] = '';
            }
            $b[] = 'Total ' . count($telat) . ' karyawan yang terlambat.';
        }

        $this->db->insert('wa_pesan_teks', ['kode' => $kode, 'isi' => implode("\n", $b)]);
        echo 'Masuk antrean: ' . count($telat) . " terlambat.\n";
    }
}
