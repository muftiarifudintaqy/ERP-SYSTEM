<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Attendance Library
 *
 * Sistem absensi dengan skor sebagai informasi (breakdown):
 *   GPS dalam radius kantor   -> 70 poin
 *   Device terdaftar+approved -> 20 poin
 *   IP termasuk IP kantor     -> 10 poin
 *
 * Absen VALID jika lokasi dan wajah cocok. Perangkat tetap dicatat di
 * kolom device_valid untuk keperluan pemeriksaan HRD, tetapi tidak lagi
 * menentukan sah atau tidaknya sebuah absen.
 * Skor tetap dicatat untuk pelaporan, tetapi bukan penentu valid/rejected.
 *
 * Aturan device : device pertama milik user auto-approve, berikutnya 'pending' (ACC HR).
 *                 Hanya satu browser approved yang aktif untuk tiap user.
 * Aturan IP     : auto-learning -- IP dipelajari saat absen GPS+device valid
 *                 dari IP yang belum dikenal (source = 'auto').
 */
class Attendance_service
{
    const GPS_POINTS    = 70;
    const DEVICE_POINTS = 20;
    const IP_POINTS     = 10;
    const THRESHOLD     = 80;

    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
    }

    /**
     * Rekam check-in untuk hari ini. Idempotent: 1 record per user per hari.
     *
     * @param int   $user_id
     * @param array $payload  ['latitude','longitude','accuracy','ip','device_token','user_agent']
     * @return array          ['already_checked_in'=>bool, 'attendance_id'=>int|null, ...breakdown]
     */
    public function record_check_in($user_id, array $payload)
    {
        $today = date('Y-m-d');

        $existing = $this->CI->db
            ->where('user_id', $user_id)
            ->where('attendance_date', $today)
            ->get('attendances')
            ->row_array();

        if (!empty($existing)) {
            return array_merge($existing, ['already_checked_in' => true]);
        }

        $result = $this->evaluate($user_id, $payload);

        $insert = [
            'user_id'            => $user_id,
            'attendance_date'    => $today,
            'check_in_at'        => date('Y-m-d H:i:s'),
            'latitude'           => $result['latitude'],
            'longitude'          => $result['longitude'],
            'gps_accuracy_m'     => $result['gps_accuracy_m'],
            'distance_m'         => $result['distance_m'],
            'office_location_id' => $result['office_location_id'],
            'ip_address'         => $result['ip_address'],
            'device_id'          => $result['device_id'],
            'gps_valid'          => $result['gps_valid'] ? 1 : 0,
            'device_valid'       => $result['device_valid'] ? 1 : 0,
            'ip_valid'           => $result['ip_valid'] ? 1 : 0,
            'score'              => $result['score'],
            'status'             => $result['status'],
            'created_at'         => date('Y-m-d H:i:s'),
        ];

        $this->CI->db->insert('attendances', $insert);
        $result['attendance_id'] = $this->CI->db->insert_id();
        $result['already_checked_in'] = false;

        return $result;
    }

    /**
     * Coba rekam check-in mandiri dari halaman absensi.
     *
     * Berbeda dari record_check_in(): TIDAK menyimpan apa pun bila absen tidak
     * memenuhi syarat. Absensi hanya tersimpan jika GPS + device + IP ketiganya
     * valid. Jika tidak, kembalikan daftar alasan ('reasons') tanpa insert.
     *
     * @param int   $user_id
     * @param array $payload  ['latitude','longitude','accuracy','ip','device_token','user_agent']
     * @return array          ['success'=>bool,'already_checked_in'=>bool,'reasons'=>array, ...breakdown]
     */
    public function attempt_check_in($user_id, array $payload)
    {
        $today = date('Y-m-d');
        $this->ensure_clock_out_column();

        $existing = $this->CI->db
            ->where('user_id', $user_id)
            ->where('attendance_date', $today)
            ->get('attendances')
            ->row_array();

        if (!empty($existing)) {
            return array_merge($existing, [
                'already_checked_in' => true,
                'success'            => true,
                'reasons'            => [],
            ]);
        }

        $result = $this->evaluate($user_id, $payload);

        $all_valid = $result['gps_valid'] && $result['device_valid'] && $result['ip_valid'];

        $result['already_checked_in'] = false;
        $result['reasons']            = $this->failure_reasons($result);

        // Tidak memenuhi syarat -> tidak menyimpan absensi sama sekali.
        if (!$all_valid) {
            $result['success']       = false;
            $result['attendance_id'] = null;
            return $result;
        }

        $insert = [
            'user_id'            => $user_id,
            'attendance_date'    => $today,
            'check_in_at'        => date('Y-m-d H:i:s'),
            'latitude'           => $result['latitude'],
            'longitude'          => $result['longitude'],
            'gps_accuracy_m'     => $result['gps_accuracy_m'],
            'distance_m'         => $result['distance_m'],
            'office_location_id' => $result['office_location_id'],
            'ip_address'         => $result['ip_address'],
            'device_id'          => $result['device_id'],
            'gps_valid'          => 1,
            'device_valid'       => 1,
            'ip_valid'           => 1,
            'score'              => $result['score'],
            'status'             => 'valid',
            'created_at'         => date('Y-m-d H:i:s'),
        ];

        $this->CI->db->insert('attendances', $insert);
        $result['attendance_id'] = $this->CI->db->insert_id();
        $result['success']       = true;

        return $result;
    }

    const FACE_DISTANCE_THRESHOLD = 0.5;
    const MASUK_DEADLINE   = '08:00:00'; // lewat jam ini -> Telat
    const ISTIRAHAT_DEADLINE = '13:00:00'; // batas normal masuk kembali
    const AFTER_BREAK_DEADLINE = '13:15:00'; // lewat jam ini -> Terlambat After Break
    const GPS_MOBILE_ACCURACY_MAX = 100; // meter -- di bawah ini dianggap GPS chip HP asli

    /**
     * Absen 4x sehari: masuk, istirahat_keluar, istirahat_masuk, pulang.
     * Urutan ditentukan SERVER (bukan dipercaya dari client) berdasarkan
     * kolom yang sudah terisi hari ini di tabel attendances.
     *
     * Aturan device-aware:
     *  - mobile (GPS akurat < 100m tersedia)  -> wajib GPS radius kantor + wajah cocok + IP/device valid
     *  - desktop (GPS tidak akurat/tidak ada) -> cukup device terdaftar + IP kantor valid (skip GPS & wajah)
     *
     * @param int   $user_id
     * @param array $payload ['latitude','longitude','accuracy','ip','device_token','user_agent','descriptor','photo_base64']
     * @return array
     */
    public function attempt_punch($user_id, array $payload)
    {
        $today = date('Y-m-d');
        $this->ensure_clock_out_column();
        $this->ensure_break_columns();

        $att = $this->CI->db->where('user_id', $user_id)->where('attendance_date', $today)->get('attendances')->row_array();
        if (empty($att)) {
            $this->CI->db->insert('attendances', [
                'user_id' => $user_id, 'attendance_date' => $today,
                'gps_valid' => 0, 'device_valid' => 0, 'ip_valid' => 0, 'score' => 0,
                'status' => 'rejected', 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $att = $this->CI->db->where('id', $this->CI->db->insert_id())->get('attendances')->row_array();
        }

        $kind = $this->next_kind($att, $user_id);
        if ($kind === null) {
            return ['success' => false, 'done_today' => true, 'reasons' => ['Absensi hari ini sudah lengkap (4/4).'], 'kind' => null];
        }

        $jendela = $this->_cek_jendela($kind, $user_id);
        if ($jendela !== true) {
            return ['success' => false, 'kind' => $kind, 'diluar_jam' => true, 'reasons' => [$jendela]];
        }

        // ---- deteksi jenis perangkat ----
        $accuracy = isset($payload['accuracy']) && $payload['accuracy'] !== '' ? (int) $payload['accuracy'] : null;
        $has_gps  = isset($payload['latitude']) && $payload['latitude'] !== '' && isset($payload['longitude']) && $payload['longitude'] !== '';
        $perangkat   = $this->_deteksi_perangkat((string) ($payload['user_agent'] ?? ''));
        $device_type = $perangkat['tipe'];
        $device_os   = $perangkat['os'];

        // "Request Desktop Website" di iOS membuat Safari dan Chrome
        // mengirim User-Agent Macintosh penuh, tanpa kata iPhone. Tanpa
        // pemeriksaan ini, siapa pun bisa menyalakannya lalu absen dari
        // mana saja tanpa GPS maupun wajah.
        //
        // Komputer tidak punya GPS presisi. Kalau koordinatnya ada dan
        // akurasinya di bawah 200 meter, itu perangkat bergerak apa pun
        // yang diakui User-Agent-nya.
        $akurasi_ada = isset($payload['accuracy']) && $payload['accuracy'] !== ''
                    && (int)$payload['accuracy'] > 0 && (int)$payload['accuracy'] <= 200;
        if ($device_type !== 'mobile'
            && isset($payload['latitude']) && $payload['latitude'] !== ''
            && $akurasi_ada) {
            $device_type = 'mobile';
            $device_os   = $perangkat['os'] . ' (mode desktop)';
        }

        $gps    = $has_gps ? $this->check_gps((float) $payload['latitude'], (float) $payload['longitude']) : ['valid' => false, 'distance_m' => null, 'office_location_id' => null];
        $device = $this->resolve_device($user_id, trim((string) ($payload['device_token'] ?? '')), substr((string) ($payload['user_agent'] ?? ''), 0, 255));
        $ip     = trim((string) ($payload['ip'] ?? ''));
        $ip_valid = $this->is_ip_whitelisted($ip);

        $face_valid    = null;
        $face_distance = null;
        $photo_path    = null;

        $butuh_wajah = ($device_type === 'mobile') || !empty($payload['has_camera']);

        // istirahat_masuk tidak perlu wajah maupun foto: orangnya sudah
        // terverifikasi wajah saat absen masuk pagi tadi, dan memotret
        // ulang setiap orang sepulang makan siang hanya menambah
        // keberatan tanpa menambah bukti. Lokasi dan perangkat tetap
        // diperiksa, jadi tetap harus dari kantor.
        if ($kind === 'istirahat_masuk') { $butuh_wajah = FALSE; }

        // GPS dilewati untuk desktop karena PC kantor tidak bergerak dan
        // sering tidak punya GPS sama sekali. Tapi kalau koordinatnya
        // ADA dan jaraknya jauh, itu laptop dari luar kantor: jaraknya
        // desktop tetap diperiksa, bukan dianggap sah begitu saja.
        if ($device_type !== 'mobile') {
            if (!$has_gps || $gps['distance_m'] === NULL) {
                $gps['valid'] = true;   // tidak ada GPS, andalkan IP kantor
            }
            // kalau ada GPS, biarkan hasil check_gps yang menentukan
        }

        // Hari kerja dari rumah: jarak GPS pasti di luar radius kantor,
        // jadi pemeriksaan jaraknya dilewati. Wajah dan perangkat
        // terdaftar TETAP diperiksa, dan itu yang mencegah orang lain
        // absen menggantikannya. Koordinat aslinya tetap tersimpan apa
        // adanya supaya HRD bisa melihat kalau ada yang absen dari
        // tempat yang jauh.
        $wfh = $this->_izin_wfh($user_id);
        if ($wfh) { $gps['valid'] = true; }

        if ($butuh_wajah) {
            // wajah wajib untuk semua perangkat yang punya kamera (HP & laptop)
            $desc_json = $payload['descriptor'] ?? null;
            $stored = $this->CI->db->where('user_id', $user_id)->get('user_face_descriptors')->row_array();
            if (empty($stored)) {
                $face_valid = false;
            } elseif (empty($desc_json)) {
                $face_valid = false;
            } else {
                $incoming = json_decode($desc_json, true);
                $known    = json_decode($stored['descriptor'], true);
                if (is_array($incoming) && is_array($known)) {
                    $face_distance = $this->face_distance($incoming, $known);
                    $face_valid = $face_distance <= self::FACE_DISTANCE_THRESHOLD;
                } else {
                    $face_valid = false;
                }
            }

            if (!empty($payload['photo_base64'])) {
                $photo_path = $this->save_photo($payload['photo_base64'], $user_id, $kind);
            }
        } else {
            // perangkat tanpa kamera (PC tower): cukup device terdaftar + IP kantor
            $face_valid   = true;
        }

        // IP kantor (IndiHome) berubah-ubah blok, sudah 4x dalam 3 minggu, dan tiap
        // kali berubah SEMUA karyawan gagal absen. Untuk perangkat berkamera,
        // buktinya sudah kuat: wajah cocok + GPS radius kantor + HP terdaftar.
        // IP tetap dicatat untuk audit, tapi tidak lagi menentukan gagal/berhasil.
        // Perangkat tanpa kamera (PC tower) tetap wajib IP kantor.
        // Perangkat tidak lagi menolak absen. Yang benar-benar menahan
        // orang absen mewakili orang lain adalah wajah: satu wajah hanya
        // bisa terdaftar di satu akun, jadi meminjam HP dan akun orang
        // lain tetap gagal di pencocokan wajah. Perangkatnya tetap
        // dicatat supaya HRD bisa melihat siapa memakai apa.
        $all_valid = $gps['valid'] && ($face_valid === true)
                     && ($butuh_wajah ? true : $ip_valid);

        // Saat WFH, IP rumah tidak boleh ikut dipelajari sebagai IP
        // kantor. Kalau dibiarkan, daftar putih jadi longgar untuk
        // semua orang.
        if (!$wfh && $gps['valid'] && $device['valid'] && !$ip_valid && $ip !== '') {
            $this->learn_ip($ip, $user_id);
        }

        $reasons = [];
        if (!$wfh && $device_type === 'mobile' && empty($gps['valid']))    $reasons[] = 'Lokasi (GPS) di luar radius kantor atau lokasi belum dinyalakan.';
        if (empty($ip_valid) && !$butuh_wajah)                    $reasons[] = 'Wi-Fi / IP tidak valid. Pastikan terhubung ke jaringan kantor.';
        if ($butuh_wajah && $face_valid !== true)                  $reasons[] = empty($stored ?? null) ? 'Wajah belum terdaftar. Silakan daftarkan wajah dulu.' : 'Wajah tidak cocok dengan data terdaftar.';

        if (!$all_valid) {
            return [
                'success' => false, 'kind' => $kind, 'device_type' => $device_type,
                'gps_valid' => $gps['valid'], 'device_valid' => $device['valid'], 'ip_valid' => $ip_valid, 'face_valid' => $face_valid,
                'reasons' => $reasons,
            ];
        }

        // Indikasi lokasi palsu: GPS asli tidak pernah menghasilkan koordinat
        // yang sama persis dua kali. Kalau identik -> kemungkinan mock location.
        $suspect = null;
        if ($device_type === 'mobile' && $has_gps) {
            $la = $this->CI->db->escape_str((string) $payload['latitude']);
            $lo = $this->CI->db->escape_str((string) $payload['longitude']);
            $sama = $this->CI->db->query("SELECT id FROM attendance_punches WHERE user_id = " . (int) $user_id . " AND latitude = '$la' AND longitude = '$lo' LIMIT 1")->row_array();
            if (!empty($sama)) { $suspect = 'Koordinat identik dengan absen sebelumnya (indikasi mock GPS)'; }
            elseif ($accuracy !== null && $accuracy <= 3) { $suspect = 'Akurasi GPS tidak wajar (' . $accuracy . 'm)'; }
        }

        // Shift dikunci lebih dulu, karena batas telat host live
        // ikut shift yang baru saja ditentukan di sini.
        if ($kind === 'masuk') $this->_kunci_shift_live($user_id);

        $late_status = $this->determine_late_status($kind, $user_id);
        $this->_notif_absen($user_id, $kind, $late_status);

        $this->CI->db->insert('attendance_punches', [
            'attendance_id' => (int) $att['id'], 'user_id' => $user_id, 'kind' => $kind,
            'punched_at' => date('Y-m-d H:i:s'), 'device_type' => $device_type,
            'latitude' => $payload['latitude'] ?? null, 'longitude' => $payload['longitude'] ?? null,
            'gps_accuracy_m' => $accuracy, 'distance_m' => $gps['distance_m'], 'office_location_id' => $gps['office_location_id'],
            'ip_address' => $ip, 'device_id' => $device['id'], 'device_os' => $device_os, 'photo_path' => $photo_path, 'face_distance' => $face_distance, 'suspect_reason' => $suspect,
            'gps_valid' => $gps['valid'] ? 1 : 0, 'device_valid' => 1, 'ip_valid' => 1, 'face_valid' => 1,
            'late_status' => $late_status,
            // Ditandai supaya HRD tahu absen ini dari rumah, bukan
            // dari kantor. Tanpa penanda ini, absen WFH terlihat sama
            // dengan absen kantor padahal jarak GPS-nya jauh.
            'mode' => $wfh ? 'wfh' : 'kantor',
            'status' => 'valid', 'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Masukkan ke antrean WhatsApp. Sengaja hanya menulis baris,
        // pengiriman dikerjakan proses terpisah. Kalau ditulis langsung
        // ke WhatsApp di sini, karyawan menunggu di layar sampai
        // pengiriman selesai, dan absennya ikut gagal kalau WhatsApp
        // sedang bermasalah.
        $this->_antre_wa((int)$this->CI->db->insert_id(), $user_id, $kind,
                         $late_status, $photo_path);

        $column_map = ['masuk' => 'check_in_at', 'istirahat_keluar' => 'break_out_at', 'istirahat_masuk' => 'break_in_at', 'pulang' => 'check_out_at'];

        // sinkron status attendances biar rekap admin tidak selalu 'rejected'
        $skor = ($gps['valid'] ? self::GPS_POINTS : 0) + self::DEVICE_POINTS + self::IP_POINTS;
        $this->CI->db->where('id', $att['id'])->update('attendances', [
            $column_map[$kind]   => date('Y-m-d H:i:s'),
            'gps_valid'          => $gps['valid'] ? 1 : 0,
            'device_valid'       => 1,
            'ip_valid'           => 1,
            'score'              => $skor,
            'status'             => 'valid',
            'latitude'           => $payload['latitude']  ?? $att['latitude'],
            'longitude'          => $payload['longitude'] ?? $att['longitude'],
            'gps_accuracy_m'     => $accuracy,
            'distance_m'         => $gps['distance_m'],
            'office_location_id' => $gps['office_location_id'],
            'ip_address'         => $ip,
            'device_id'          => $device['id'],
        ]);

        return [
            'success' => true, 'kind' => $kind, 'device_type' => $device_type,
            'office_location_id' => $gps['office_location_id'], 'distance_m' => $gps['distance_m'],
            'late_status' => $late_status,
        ];
    }

    /** Tentukan absen ke berapa yang harus dilakukan berikutnya, berdasar data yang sudah ada hari ini. */
    /** Tentukan status Hadir/Telat/Telat Istirahat berdasar jam punya sekarang. Pulang tidak pernah dianggap telat. */
    /** Jendela waktu tiap jenis absen. Di luar jam ini tombol ditolak server. */
    private function _cek_jendela($kind, $user_id = 0)
    {
        $j = $this->_jadwal_user($user_id);

        // Jendela dihitung dari jadwal orangnya, bukan angka tetap.
        // Host live masuk 14:00; dengan jendela lama 06:00-11:59 mereka
        // ditolak sebelum sempat dinilai telat atau tidak.
        $masuk  = strtotime($j['jam_masuk']);
        $pulang = strtotime($j['jam_pulang']);

        $win = [
            // boleh absen masuk mulai 2 jam sebelum jadwal,
            // sampai satu jam sebelum jam pulang
            'masuk'  => [date('H:i:s', $masuk - 7200), date('H:i:s', $pulang - 3600)],
            'pulang' => [date('H:i:s', $pulang - 1800), '23:59:59'],

            // istirahat memakai jam tetap; hanya berlaku untuk jadwal
            // yang memang memakai istirahat
            'istirahat_keluar' => ['12:00:00', '13:00:59'],
            'istirahat_masuk'  => ['13:00:00', '16:59:59'],
        ];

        // Jadwal tanpa istirahat, misalnya host live: dua tahap itu
        // memang tidak pernah muncul, jadi tidak perlu diperiksa.
        if (empty($j['pakai_istirahat'])
            && in_array($kind, ['istirahat_keluar', 'istirahat_masuk'], TRUE)) {
            return TRUE;
        }
        $label = [
            'masuk' => 'Absen Masuk', 'istirahat_keluar' => 'Absen Istirahat',
            'istirahat_masuk' => 'Absen Masuk Kembali', 'pulang' => 'Absen Pulang',
        ];
        if (!isset($win[$kind])) return true;
        $now = date('H:i:s');
        if ($now < $win[$kind][0] || $now > $win[$kind][1]) {
            return $label[$kind] . ' hanya bisa dilakukan pukul ' . substr($win[$kind][0], 0, 5) . ' - ' . substr($win[$kind][1], 0, 5) . '. Sekarang ' . substr($now, 0, 5) . '.';
        }
        return true;
    }

    /**
     * Apakah hari ini karyawan ini bekerja dari rumah.
     *
     * Dibaca dari kolom hari_wfh di jadwalnya, misalnya '6' untuk
     * setiap Sabtu. Sengaja tidak memakai pengajuan izin per tanggal,
     * karena itu berarti HRD harus memasukkan data tiap minggu untuk
     * tiap orang, dan yang seperti itu pasti terlewat.
     *
     * Pada hari WFH, pemeriksaan jarak GPS dilewati karena pasti gagal
     * dari rumah. Wajah dan perangkat terdaftar TETAP diperiksa, dan
     * itu yang mencegah orang lain absen menggantikannya. Koordinatnya
     * tetap tersimpan apa adanya untuk keperluan HRD.
     */
    private function _izin_wfh($user_id, $tanggal = NULL)
    {
        $jadwal = $this->_jadwal_user($user_id);
        if (empty($jadwal['hari_wfh'])) return FALSE;

        $hari = $tanggal ? date('N', strtotime($tanggal)) : date('N');
        $daftar = array_map('trim', explode(',', $jadwal['hari_wfh']));
        return in_array((string)$hari, $daftar, TRUE);
    }

    /** Apakah jadwal hari ini memakai tahap istirahat. Dipakai controller. */
    /** Apakah jadwal hari ini memakai tahap istirahat. Dipakai controller. */
    /**
     * Jendela jam tiap tahap absen menurut jadwal orang ini hari ini.
     * Sama dengan yang dipakai _cek_jendela, supaya tampilan tombol dan
     * pemeriksaan di server tidak pernah berbeda.
     */
    public function jendela_jam($user_id)
    {
        $j = $this->_jadwal_user($user_id);
        $masuk  = strtotime($j['jam_masuk']);
        $pulang = strtotime($j['jam_pulang']);

        return [
            // Jendela masuk sengaja lebar: 05:00 sampai siang. Yang
            // datang subuh tetap bisa absen, dan yang telat tetap
            // tercatat telat, bukan ditolak.
            // Jendela masuk sengaja lebar. Yang datang subuh tetap bisa
            // absen, host live yang jadwalnya 14:00 juga bisa absen dari
            // pagi kalau datang lebih awal. Yang telat tetap tercatat
            // telat, bukan ditolak.
            'masuk'            => ['05:00', date('H:i', $pulang - 3600)],
            'istirahat_keluar' => ['12:00', '13:00'],
            'istirahat_masuk'  => ['13:00', '16:59'],
            'pulang'           => [date('H:i', $pulang - 1800), '23:59'],
        ];
    }

    /**
     * Keterangan shift untuk ditampilkan di halaman absensi.
     * NULL untuk karyawan biasa yang jam kerjanya tetap.
     */
    public function info_shift_live($user_id)
    {
        $j = $this->_jadwal_user($user_id);
        if (empty($j['shift_live'])) return NULL;

        $sh = (int)$j['shift_live'];
        return [
            'shift'  => $sh,
            'mulai'  => substr($j['jam_masuk'], 0, 5),
            'sampai' => substr($j['jam_pulang'], 0, 5),
            'pasti'  => (bool)$this->CI->db
                ->where('tanggal', date('Y-m-d'))
                ->where('user_id', (int)$user_id)
                ->count_all_results('live_shift_harian'),
        ];
    }

    public function pakai_istirahat($user_id)
    {
        $j = $this->_jadwal_user($user_id);
        return !empty($j['pakai_istirahat']);
    }

    private function _jadwal_user($user_id)
    {
        // 1. jadwal khusus orang ini
        $j = $this->CI->db->where('user_id', (int)$user_id)
                          ->where('aktif', 1)
                          ->get('hrd_jadwal')->row_array();
        if ($j) return $this->_shift_live(
            $this->_sesuaikan_izin($this->_sesuaikan_sabtu($this->_terapkan_jam_harian($j)), $user_id),
            $user_id
        );

        // 2. jadwal per divisi. Dengan cara ini karyawan baru di divisi
        //    Host Live langsung memakai jam 14:00 tanpa perlu didaftarkan
        //    satu per satu, dan tidak ada yang tercatat telat karena lupa
        //    dipasangkan jadwal.
        $u = $this->CI->db->select('role_text')->where('id', (int)$user_id)
                          ->get('user')->row_array();
        if (!empty($u['role_text'])) {
            $j = $this->CI->db->where('divisi', $u['role_text'])
                              ->where('aktif', 1)
                              ->get('hrd_jadwal')->row_array();
            if ($j) return $this->_terapkan_jam_harian($j);
        }

        // 3. jadwal reguler
        return $this->_sesuaikan_izin($this->_sesuaikan_sabtu([
            'nama_jadwal'     => 'Reguler',
            'jam_masuk'       => '08:00:00',
            'toleransi_menit' => 5,
            'jam_pulang'      => '17:00:00',
            'pakai_istirahat' => 1,
            'hari_kerja'      => '1,2,3,4,5,6',
            'hari_wfh'        => NULL,
            'jam_pulang_sabtu'=> '12:00:00',
        ]), $user_id);
    }

    /**
     * Kalau ada jam masuk khusus untuk hari ini (Senin-Minggu) di kolom
     * jam_masuk_harian (JSON, key 1=Senin ... 7=Minggu sesuai date('N')),
     * pakai itu sebagai jam_masuk. Kalau tidak ada / kosong, jadwal
     * tetap memakai jam_masuk defaultnya seperti biasa.
     */
    private function _terapkan_jam_harian(array $jadwal)
    {
        if (!empty($jadwal['jam_masuk_harian'])) {
            $harian   = json_decode($jadwal['jam_masuk_harian'], true);
            $hari_ini = (int)date('N');
            if (is_array($harian) && !empty($harian[$hari_ini])) {
                $jadwal['jam_masuk'] = $harian[$hari_ini];
            }
        }
        return $jadwal;
    }
    /**
     * Sabtu jam pulangnya berbeda: sebagian besar karyawan sampai
     * 12:00, sementara packing tetap sampai 17:00. Diambil dari kolom
     * jam_pulang_sabtu supaya bisa diatur per orang.
     *
     * Kalau pulangnya sebelum jam satu siang, tahap istirahat ikut
     * dilewati. Tidak masuk akal menuntut absen istirahat pada hari
     * yang jam kerjanya berakhir sebelum jam istirahat selesai.
     */
    /**
     * Izin setengah hari yang sudah disetujui untuk hari ini.
     *
     * Dua bentuk yang mungkin:
     *  - izin di awal hari (08:00-12:00) berarti masuknya digeser ke
     *    jam selesai izin, sehingga tidak tercatat terlambat
     *  - izin di akhir hari (12:00-17:00) berarti pulangnya digeser ke
     *    jam mulai izin, sehingga bisa absen pulang lebih awal
     *
     * Hanya yang berstatus approved yang dipakai. Pengajuan yang masih
     * menunggu persetujuan tidak mengubah apa pun.
     */
    private function _izin_setengah($user_id)
    {
        $r = $this->CI->db
            ->select('lr.jam_mulai, lr.jam_selesai')
            ->from('leave_requests lr')
            ->where('lr.user_id', (int)$user_id)
            ->where('lr.status', 'approved')
            ->where('lr.jam_mulai IS NOT NULL')
            ->where('lr.jam_selesai IS NOT NULL')
            ->where('CURDATE() BETWEEN lr.start_date AND lr.end_date')
            ->order_by('lr.id', 'DESC')
            ->limit(1)
            ->get()->row_array();

        return $r ?: NULL;
    }

    /** Geser jam masuk atau jam pulang sesuai izin yang disetujui. */
    private function _sesuaikan_izin($jadwal, $user_id)
    {
        $izin = $this->_izin_setengah($user_id);
        if (!$izin) return $jadwal;

        // Izin dimulai pada atau sebelum jam masuk: orangnya datang
        // belakangan, jadi batas telatnya bergeser ke akhir izin.
        if ($izin['jam_mulai'] <= $jadwal['jam_masuk']) {
            $jadwal['jam_masuk'] = $izin['jam_selesai'];
        }
        // Izin berakhir pada atau sesudah jam pulang: orangnya pulang
        // lebih awal, jadi jam pulangnya bergeser ke awal izin.
        elseif ($izin['jam_selesai'] >= $jadwal['jam_pulang']) {
            $jadwal['jam_pulang'] = $izin['jam_mulai'];
            if ($izin['jam_mulai'] <= '13:00:00') {
                $jadwal['pakai_istirahat'] = 0;
            }
        }

        $jadwal['ada_izin'] = TRUE;
        return $jadwal;
    }

    private function _sesuaikan_sabtu($jadwal)
    {
        if ((int)date('N') !== 6) return $jadwal;

        if (!empty($jadwal['jam_pulang_sabtu'])) {
            $jadwal['jam_pulang'] = $jadwal['jam_pulang_sabtu'];
            if ($jadwal['jam_pulang_sabtu'] <= '13:00:00') {
                $jadwal['pakai_istirahat'] = 0;
            }
        }
        return $jadwal;
    }

    /** Jam kerja tiap shift host live. */
    private function _jam_shift($shift)
    {
        return ((int)$shift === 2)
            ? ['masuk' => '15:00:00', 'pulang' => '22:00:00']
            : ['masuk' => '08:00:00', 'pulang' => '15:00:00'];
    }

    /**
     * Shift host live tidak dijadwalkan di muka. Dua orang pertama yang
     * absen pagi mengisi Shift 1, sisanya otomatis Shift 2. Yang sudah
     * terkunci di live_shift_harian dipakai apa adanya; kalau belum,
     * nilainya sementara dan bisa berubah sampai dia benar-benar absen.
     */
    private function _shift_live_berlaku($user_id, $tanggal = NULL)
    {
        $tanggal = $tanggal ?: date('Y-m-d');

        $r = $this->CI->db->select('shift')
                          ->where('tanggal', $tanggal)
                          ->where('user_id', (int)$user_id)
                          ->get('live_shift_harian')->row_array();
        if ($r) return (int)$r['shift'];

        $terisi = (int)$this->CI->db->where('tanggal', $tanggal)
                                    ->where('shift', 1)
                                    ->count_all_results('live_shift_harian');
        return ($terisi >= 2) ? 2 : 1;
    }

    /** Timpa jam jadwal host live mengikuti shift hari ini. */
    private function _shift_live($jadwal, $user_id)
    {
        if (($jadwal['nama_jadwal'] ?? '') !== 'Host Live') return $jadwal;

        $shift = $this->_shift_live_berlaku($user_id);
        $jam   = $this->_jam_shift($shift);

        $jadwal['jam_masuk']        = $jam['masuk'];
        $jadwal['jam_pulang']       = $jam['pulang'];
        $jadwal['jam_pulang_sabtu'] = $jam['pulang'];
        $jadwal['pakai_istirahat']  = 0;
        $jadwal['shift_live']       = $shift;
        $jadwal['nama_jadwal']      = 'Host Live - Shift ' . $shift;

        return $jadwal;
    }

    /**
     * Kunci shift saat absen masuk. Dipanggil HANYA dari alur absen,
     * bukan dari pembacaan jadwal, supaya membuka halaman saja tidak
     * ikut mengklaim slot Shift 1.
     *
     * Penghitungan dibungkus transaksi dengan FOR UPDATE supaya dua
     * host yang absen pada detik yang sama tidak sama-sama dapat
     * Shift 1.
     */
    private function _kunci_shift_live($user_id)
    {
        $j = $this->CI->db->select('nama_jadwal')
                          ->where('user_id', (int)$user_id)
                          ->where('aktif', 1)
                          ->get('hrd_jadwal')->row_array();
        if (!$j || $j['nama_jadwal'] !== 'Host Live') return NULL;

        $tanggal = date('Y-m-d');

        try {
            $this->CI->db->trans_begin();

            $ada = $this->CI->db->query(
                'SELECT shift FROM live_shift_harian
                 WHERE tanggal = ? AND user_id = ? FOR UPDATE',
                [$tanggal, (int)$user_id]
            )->row_array();

            if ($ada) {
                $this->CI->db->trans_commit();
                return (int)$ada['shift'];
            }

            $terisi = (int)$this->CI->db->query(
                'SELECT COUNT(*) AS n FROM live_shift_harian
                 WHERE tanggal = ? AND shift = 1 FOR UPDATE',
                [$tanggal]
            )->row('n');

            $shift = ($terisi >= 2) ? 2 : 1;

            $this->CI->db->insert('live_shift_harian', [
                'tanggal'       => $tanggal,
                'user_id'       => (int)$user_id,
                'shift'         => $shift,
                'ditentukan_at' => date('Y-m-d H:i:s'),
            ]);

            $this->CI->db->trans_commit();
            return $shift;

        } catch (\Throwable $e) {
            $this->CI->db->trans_rollback();
            log_message('error', 'kunci shift live gagal: ' . $e->getMessage());
            return NULL;
        }
    }

    /** Batas telat = jam masuk + toleransi. */
    private function _batas_telat($jadwal)
    {
        $t = strtotime($jadwal['jam_masuk']) + ((int)$jadwal['toleransi_menit'] * 60);
        return date('H:i:s', $t);
    }

    private function determine_late_status($kind, $user_id = 0)
    {
        $now = date('H:i:s');

        // Batas telat diambil dari jadwal karyawan yang bersangkutan.
        // Host live masuk 14:00, karyawan reguler 08:00. Tanpa ini
        // host live selalu tercatat terlambat enam jam setiap hari.
        if ($kind === 'masuk') {
            $jadwal = $this->_jadwal_user($user_id);
            $batas  = $this->_batas_telat($jadwal);
            return ($now > $batas) ? 'telat' : 'tepat';
        }
        // istirahat keluar : 12:00-13:00 -> Take Break
        if ($kind === 'istirahat_keluar') return 'take_break';
        // masuk kembali : s/d 13:15 After Take Break, lewat itu Terlambat After Break
        if ($kind === 'istirahat_masuk')  return ($now > self::AFTER_BREAK_DEADLINE) ? 'telat_after_break' : 'after_break';
        // pulang
        return 'back_to_home';
    }

    /**
     * Tentukan jenis perangkat dari User-Agent (bukan dari akurasi GPS).
     * mobile  -> Android / iPhone / iPad  : wajib GPS + wajah
     * desktop -> MacBook / Windows / Linux: cukup device terdaftar + IP kantor
     */
    /**
     * Catat satu absen ke antrean WhatsApp.
     *
     * Yang terlambat diberi prioritas 1 supaya dikirim sendiri dan
     * menonjol. Sisanya prioritas 0, digabung jadi album oleh
     * pengirimnya sehingga 30 karyawan tidak berarti 30 pesan.
     *
     * Kegagalan di sini sengaja tidak dilemparkan ke atas. Absen
     * karyawan jauh lebih penting daripada notifikasi grup, jadi
     * kalau tabel antreannya bermasalah, absennya tetap tersimpan.
     */
    private function _antre_wa($punch_id, $user_id, $kind, $late_status, $photo_path)
    {
        try {
            // Jabatan diambil dari posisi resmi, bukan role_text.
            // role_text sering berisi sisa data lama seperti 'Guest'.
            $u = $this->CI->db->select('u.full_name, p.name AS posisi, u.role_text')
                              ->from('user u')
                              ->join('user_profile up', 'up.user_id = u.id', 'left')
                              ->join('positions p', 'p.id = up.position_id', 'left')
                              ->where('u.id', (int)$user_id)
                              ->get()->row_array();

            $telat = in_array($late_status, ['telat', 'telat_after_break'], TRUE);

            $this->CI->db->insert('wa_antrean', [
                'punch_id'    => (int)$punch_id,
                'user_id'     => (int)$user_id,
                'nama'        => $u['full_name'] ?? 'Karyawan',
                'jabatan'     => $u['posisi'] ?? ($u['role_text'] ?? null),
                'kind'        => $kind,
                'punched_at'  => date('Y-m-d H:i:s'),
                'late_status' => $late_status,
                'photo_path'  => $photo_path,
                'prioritas'   => $telat ? 1 : 0,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'antrean wa gagal: ' . $e->getMessage());
        }
    }

    private function _notif_absen($user_id, $kind, $late_status)
    {
        $lk = ['masuk'=>'Absen Masuk','istirahat_keluar'=>'Absen Istirahat','istirahat_masuk'=>'Absen Masuk Kembali','pulang'=>'Absen Pulang'];
        $ls = ['tepat'=>'Hadir','telat'=>'Terlambat','take_break'=>'Take Break','after_break'=>'After Take Break','telat_after_break'=>'Terlambat After Break','back_to_home'=>'Back To Home'];
        $jenis  = $lk[$kind] ?? 'Absensi';
        $status = $ls[$late_status] ?? '-';
        $jam    = date('H:i');
        $tipe   = in_array($late_status, ['telat','telat_after_break']) ? 'warning' : 'success';

        $u = $this->CI->db->select('full_name')->where('id', (int) $user_id)->get('user')->row_array();
        $nama = !empty($u) ? $u['full_name'] : 'Karyawan';

        // notifikasi TIDAK dikirim ke diri sendiri — yang scan cukup dengar suara.
        $this->CI->load->library('PushSender');

        $admins = $this->CI->db->query("SELECT DISTINCT ur.user_id FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE r.name IN ('developer','super_admin','head_admin','owner')")->result_array();
        foreach ($admins as $a) {
            if ((int)$a['user_id'] === (int)$user_id) continue;

            $judulNotif = $nama.' - '.$jenis;
            $isiNotif   = 'Status: '.$status.' - pukul '.$jam;
            $urlNotif   = base_url().'attendance';

            $this->CI->db->insert('notifications', [
                'user_id'=>(int)$a['user_id'], 'title'=>$judulNotif,
                'message'=>$isiNotif,
                'ref_url'=>$urlNotif, 'type'=>$tipe,
                'category'=>'absensi', 'subcategory'=>'absensi',
                'is_read'=>0, 'created_at'=>date('Y-m-d H:i:s'),
            ]);

            try {
                $this->CI->pushsender->kirim_ke_user((int)$a['user_id'], $judulNotif, $isiNotif, $urlNotif);
            } catch (\Throwable $e) {
                log_message('error', 'push absensi gagal: ' . $e->getMessage());
            }
            try {
                $this->CI->load->library('FcmSender');
                $this->CI->fcmsender->kirim((int)$a['user_id'], $judulNotif, $isiNotif, $urlNotif, 'absensi');
            } catch (\Throwable $e) {
                log_message('error', 'fcm absensi gagal: ' . $e->getMessage());
            }
        }
    }

    private function _deteksi_perangkat($ua)
    {
        $u = strtolower($ua);
        if (strpos($u, 'android') !== false)  return ['tipe' => 'mobile',  'os' => 'Android'];
        if (strpos($u, 'iphone') !== false)   return ['tipe' => 'mobile',  'os' => 'iPhone'];
        if (strpos($u, 'ipad') !== false)     return ['tipe' => 'mobile',  'os' => 'iPad'];
        if (strpos($u, 'windows') !== false)  return ['tipe' => 'desktop', 'os' => 'Windows'];
        if (strpos($u, 'macintosh') !== false || strpos($u, 'mac os') !== false) return ['tipe' => 'desktop', 'os' => 'MacBook'];
        if (strpos($u, 'linux') !== false)    return ['tipe' => 'desktop', 'os' => 'Linux'];
        return ['tipe' => 'desktop', 'os' => 'PC'];
    }

    private function next_kind(array $att, $user_id = 0)
    {
        if (empty($att['check_in_at']))  return 'masuk';

        // Jadwal tanpa istirahat, misalnya host live: langsung dari
        // masuk ke pulang. Tanpa ini mereka tertahan di tombol
        // istirahat dan tidak pernah bisa absen pulang.
        $j = $this->_jadwal_user($user_id);
        if (empty($j['pakai_istirahat'])) {
            return empty($att['check_out_at']) ? 'pulang' : null;
        }

        // Lewat jam istirahat: kalau istirahatnya tidak pernah terisi -- orang
        // masuk siang setelah cron istirahat otomatis lewat, atau memang tidak
        // istirahat -- tombolnya tidak boleh macet di istirahat. Sesudah pukul
        // 14:00 langsung ke pulang, karena tidak ada lagi yang bisa dilewatkan.
        if (date('H:i') >= '14:00') {
            return empty($att['check_out_at']) ? 'pulang' : null;
        }

        if (empty($att['break_out_at'])) return 'istirahat_keluar';
        if (empty($att['break_in_at']))  return 'istirahat_masuk';
        if (empty($att['check_out_at'])) return 'pulang';
        return null;
    }

    private function face_distance($a, $b)
    {
        if (count($a) !== count($b)) return 999;
        $sum = 0;
        for ($i = 0; $i < count($a); $i++) { $sum += pow((float) $a[$i] - (float) $b[$i], 2); }
        return sqrt($sum);
    }

    private function save_photo($base64, $user_id, $kind)
    {
        if (strpos($base64, 'base64,') !== false) { $base64 = substr($base64, strpos($base64, 'base64,') + 7); }
        $bin = base64_decode($base64);
        if ($bin === false || strlen($bin) < 500) return null;
        $dir = FCPATH . 'uploads/attendance_photos/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $name = 'u' . (int) $user_id . '_' . $kind . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.jpg';
        if (@file_put_contents($dir . $name, $bin) === false) return null;
        return 'uploads/attendance_photos/' . $name;
    }

    private function ensure_break_columns()
    {
        if (!$this->CI->db->field_exists('break_out_at', 'attendances')) {
            $this->CI->db->query("ALTER TABLE attendances ADD break_out_at DATETIME DEFAULT NULL AFTER check_in_at");
        }
        if (!$this->CI->db->field_exists('break_in_at', 'attendances')) {
            $this->CI->db->query("ALTER TABLE attendances ADD break_in_at DATETIME DEFAULT NULL AFTER break_out_at");
        }
    }

    public function attempt_check_out($user_id, array $payload)
    {
        $today = date('Y-m-d');
        $this->ensure_clock_out_column();

        $existing = $this->CI->db
            ->where('user_id', $user_id)
            ->where('attendance_date', $today)
            ->get('attendances')
            ->row_array();

        if (empty($existing)) {
            return [
                'success' => false,
                'not_checked_in' => true,
                'already_checked_out' => false,
                'reasons' => ['Kamu belum check-in hari ini.'],
            ];
        }

        if (!empty($existing['check_out_at'])) {
            return array_merge($existing, [
                'success' => true,
                'not_checked_in' => false,
                'already_checked_out' => true,
                'reasons' => [],
            ]);
        }

        $result = $this->evaluate($user_id, $payload);
        $all_valid = $result['gps_valid'] && $result['device_valid'] && $result['ip_valid'];

        $result['not_checked_in'] = false;
        $result['already_checked_out'] = false;
        $result['reasons'] = $this->failure_reasons($result);

        if (!$all_valid) {
            $result['success'] = false;
            return $result;
        }

        $this->CI->db
            ->where('id', $existing['id'])
            ->update('attendances', ['check_out_at' => date('Y-m-d H:i:s')]);

        $result['success'] = true;
        $result['attendance_id'] = (int) $existing['id'];

        return $result;
    }

    private function ensure_clock_out_column()
    {
        if (!$this->CI->db->field_exists('check_out_at', 'attendances')) {
            $this->CI->db->query("ALTER TABLE attendances ADD check_out_at DATETIME DEFAULT NULL AFTER check_in_at");
        }
    }

    /** Susun alasan kegagalan absen (untuk pesan ke user). */
    private function failure_reasons(array $r)
    {
        $reasons = [];

        if (empty($r['gps_valid'])) {
            $reasons[] = 'Lokasi (GPS) di luar radius kantor atau location belum dinyalakan.';
        }

        if (empty($r['device_valid'])) {
            if (($r['device_status'] ?? null) === 'pending') {
                $reasons[] = 'Perangkat ini belum disetujui HR. Hubungi HR untuk approval perangkat.';
            } elseif (($r['device_status'] ?? null) === 'approved_inactive') {
                $reasons[] = 'Browser ini bukan browser absensi aktif. Gunakan browser yang sudah disetujui HR.';
            } else {
                $reasons[] = 'Perangkat tidak terdaftar. Gunakan perangkat yang sudah terdaftar.';
            }
        }

        if (empty($r['ip_valid'])) {
            $reasons[] = 'Wi-Fi / IP tidak valid. Pastikan terhubung ke jaringan (Wi-Fi) kantor.';
        }

        return $reasons;
    }

    /**
     * Hitung skor & status tanpa menyimpan ke tabel attendances.
     * Catatan: tetap melakukan upsert device dan auto-learning IP sebagai efek samping.
     */
    public function evaluate($user_id, array $payload)
    {
        $lat          = isset($payload['latitude']) && $payload['latitude'] !== '' ? (float) $payload['latitude'] : null;
        $lng          = isset($payload['longitude']) && $payload['longitude'] !== '' ? (float) $payload['longitude'] : null;
        $accuracy     = isset($payload['accuracy']) && $payload['accuracy'] !== '' ? (int) $payload['accuracy'] : null;
        $ip           = isset($payload['ip']) ? trim((string) $payload['ip']) : '';
        $device_token = isset($payload['device_token']) ? trim((string) $payload['device_token']) : '';
        $user_agent   = isset($payload['user_agent']) ? substr((string) $payload['user_agent'], 0, 255) : null;

        // ---- 1. GPS ----
        $gps = $this->check_gps($lat, $lng);

        // ---- 2. Device ----
        $device = $this->resolve_device($user_id, $device_token, $user_agent);

        // ---- 3. IP (cek whitelist saat ini) ----
        $ip_valid = $this->is_ip_whitelisted($ip);

        // ---- Skor ----
        $score = 0;
        if ($gps['valid'])       $score += self::GPS_POINTS;
        if ($device['valid'])    $score += self::DEVICE_POINTS;
        if ($ip_valid)           $score += self::IP_POINTS;

        // Absen VALID hanya jika ketiganya terpenuhi: GPS + device + IP.
        // Skor tetap dihitung sebagai informasi/breakdown, bukan penentu status.
        $status = ($gps['valid'] && $device['valid'] && $ip_valid) ? 'valid' : 'rejected';

        // ---- Auto-learning IP ----
        // Pelajari IP hanya dari konteks tepercaya: GPS valid + device approved,
        // dan IP belum dikenal. IP yang baru dipelajari TIDAK menambah skor sesi ini.
        if ($gps['valid'] && $device['valid'] && !$ip_valid && $ip !== '') {
            $this->learn_ip($ip, $user_id);
        }

        return [
            'latitude'           => $lat,
            'longitude'          => $lng,
            'gps_accuracy_m'     => $accuracy,
            'distance_m'         => $gps['distance_m'],
            'office_location_id' => $gps['office_location_id'],
            'ip_address'         => $ip,
            'device_id'          => $device['id'],
            'gps_valid'          => $gps['valid'],
            'device_valid'       => $device['valid'],
            'ip_valid'           => $ip_valid,
            'device_status'      => $device['status'],
            'score'              => $score,
            'status'             => $status,
        ];
    }

    // =================================================================
    // GPS
    // =================================================================
    private function check_gps($lat, $lng)
    {
        $out = ['valid' => false, 'distance_m' => null, 'office_location_id' => null];

        if ($lat === null || $lng === null) {
            return $out;
        }

        $offices = $this->CI->db
            ->where('is_active', 1)
            ->get('office_locations')
            ->result_array();

        foreach ($offices as $office) {
            $distance = $this->haversine($lat, $lng, (float) $office['latitude'], (float) $office['longitude']);
            if ($out['distance_m'] === null || $distance < $out['distance_m']) {
                $out['distance_m']         = (int) round($distance);
                $out['office_location_id'] = (int) $office['id'];
                $out['valid']              = $distance <= (int) $office['radius_m'];
                // Catatan: ambil kantor terdekat; 'valid' merefleksikan kantor terdekat itu.
            }
        }

        return $out;
    }

    /** Jarak antar koordinat dalam meter (rumus Haversine). */
    private function haversine($lat1, $lng1, $lat2, $lng2)
    {
        $earth = 6371000; // meter
        $dLat  = deg2rad($lat2 - $lat1);
        $dLng  = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earth * $c;
    }

    // =================================================================
    // DEVICE
    // =================================================================
    private function resolve_device($user_id, $device_token, $user_agent)
    {
        if ($device_token === '') {
            return ['id' => null, 'valid' => false, 'status' => null];
        }

        $device = $this->CI->db
            ->where('user_id', $user_id)
            ->where('device_token', $device_token)
            ->get('user_devices')
            ->row_array();

        if (!empty($device)) {
            $is_active_approved = $this->is_active_approved_device($user_id, (int) $device['id']);

            $this->CI->db
                ->where('id', $device['id'])
                ->update('user_devices', ['last_used_at' => date('Y-m-d H:i:s')]);

            return [
                'id'     => (int) $device['id'],
                'valid'  => $device['status'] === 'approved' && $is_active_approved,
                'status' => ($device['status'] === 'approved' && !$is_active_approved) ? 'approved_inactive' : $device['status'],
            ];
        }

        // Device baru: device PERTAMA user auto-approve, sisanya pending.
        $has_any_device = (int) $this->CI->db
            ->where('user_id', $user_id)
            ->count_all_results('user_devices');

        $is_first = ($has_any_device === 0);

        // Token yang sama sudah dipakai akun lain? Jangan auto-approve.
        // Semua karyawan pakai HP sendiri, jadi ini normalnya tidak pernah kena.
        // Kalau kena, artinya satu perangkat dipakai dua akun - HR perlu tahu.
        $dipakai_akun_lain = (int) $this->CI->db
            ->where('device_token', $device_token)
            ->where('user_id !=', $user_id)
            ->where('status !=', 'rejected')
            ->count_all_results('user_devices');
        if ($dipakai_akun_lain > 0) { $is_first = false; }

        $status   = $is_first ? 'approved' : 'pending';

        $this->CI->db->insert('user_devices', [
            'user_id'      => $user_id,
            'device_token' => $device_token,
            'device_name'  => null,
            'user_agent'   => $user_agent,
            'status'       => $status,
            'approved_by'  => null, // auto-approve sistem, bukan oleh HR tertentu
            'approved_at'  => $is_first ? date('Y-m-d H:i:s') : null,
            'last_used_at' => date('Y-m-d H:i:s'),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return [
            'id'     => (int) $this->CI->db->insert_id(),
            'valid'  => $is_first, // approved kalau device pertama
            'status' => $status,
        ];
    }

    private function is_active_approved_device($user_id, $device_id)
    {
        $active = $this->CI->db
            ->where('user_id', $user_id)
            ->where('status', 'approved')
            ->order_by('approved_at IS NULL', 'ASC', false)
            ->order_by('approved_at', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('user_devices')
            ->row_array();

        return !empty($active) && (int) $active['id'] === (int) $device_id;
    }

    // =================================================================
    // IP
    // =================================================================
    private function is_ip_whitelisted($ip)
    {
        if ($ip === '') {
            return false;
        }

        $entries = $this->CI->db
            ->where('is_active', 1)
            ->get('office_ip_whitelist')
            ->result_array();

        foreach ($entries as $entry) {
            if ($this->ip_match($ip, $entry['ip_address'])) {
                // Update last_seen_at agar mudah dipantau.
                $this->CI->db
                    ->where('id', $entry['id'])
                    ->update('office_ip_whitelist', ['last_seen_at' => date('Y-m-d H:i:s')]);
                return true;
            }
        }

        return false;
    }

    /** Cocokkan IP dengan entry exact atau CIDR (IPv4 & IPv6). */
    private function ip_match($ip, $entry)
    {
        $entry = trim($entry);

        // Tanpa '/': exact match (samakan format dulu via inet_pton agar 0::1 == ::1, dll).
        if (strpos($entry, '/') === false) {
            $a = @inet_pton($ip);
            $b = @inet_pton($entry);
            if ($a !== false && $b !== false) {
                return $a === $b;
            }
            return $ip === $entry;
        }

        // CIDR: cocokkan N bit pertama. Mendukung IPv4 maupun IPv6.
        list($subnet, $bits) = explode('/', $entry, 2);
        $bits = (int) $bits;

        $ipBin  = @inet_pton($ip);
        $subBin = @inet_pton($subnet);
        if ($ipBin === false || $subBin === false) {
            return false;
        }
        // IP dan subnet harus sama keluarga (panjang biner sama: 4 byte IPv4 / 16 byte IPv6).
        if (strlen($ipBin) !== strlen($subBin)) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        $remBits   = $bits % 8;

        // Bandingkan byte penuh.
        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($subBin, 0, $fullBytes)) {
            return false;
        }

        // Bandingkan sisa bit pada byte berikutnya.
        if ($remBits > 0) {
            $mask    = ~(0xff >> $remBits) & 0xff;
            $ipByte  = ord($ipBin[$fullBytes]);
            $subByte = ord($subBin[$fullBytes]);
            if (($ipByte & $mask) !== ($subByte & $mask)) {
                return false;
            }
        }

        return true;
    }

    /** Tambahkan IP ke whitelist sebagai hasil auto-learning. */
    /** Set false = IP kantor HARUS didaftarkan manual admin (anti fake GPS). */
    const AUTO_LEARN_IP = false;

    private function learn_ip($ip, $user_id)
    {
        if (!self::AUTO_LEARN_IP) { return; }
        // Untuk IPv6 kantor, hextet ke-4 dan suffix perangkat bisa berubah-ubah;
        // simpan sebagai prefix /48. IPv4 disimpan apa adanya (exact).
        $entry = $this->normalize_learn_entry($ip);

        // Hindari duplikat (unique key ip_address).
        $exists = $this->CI->db
            ->where('ip_address', $entry)
            ->count_all_results('office_ip_whitelist');

        if ($exists > 0) {
            return;
        }

        $this->CI->db->insert('office_ip_whitelist', [
            'ip_address'          => $entry,
            'label'               => 'Auto-learned',
            'source'              => 'auto',
            'learned_from_user_id' => $user_id,
            'is_active'           => 1,
            'last_seen_at'        => date('Y-m-d H:i:s'),
            'created_at'          => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Tentukan bentuk yang disimpan saat auto-learning.
     * IPv6 -> prefix /48 (mis. 2404:c0:ba03:bb58:abcd::ef -> 2404:c0:ba03::/48).
     * IPv4 -> alamat exact.
     */
    private function normalize_learn_entry($ip)
    {
        $bin = @inet_pton($ip);
        // 16 byte = IPv6.
        if ($bin !== false && strlen($bin) === 16) {
            // Ambil 6 byte pertama (48 bit), sisanya nol, lalu kompres jadi notasi pendek.
            $prefixBin = substr($bin, 0, 6) . str_repeat("\0", 10);
            $prefix    = inet_ntop($prefixBin);
            if ($prefix !== false) {
                return $prefix . '/48';
            }
        }
        return $ip;
    }
}
