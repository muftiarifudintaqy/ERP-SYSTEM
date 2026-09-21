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
 * Absen VALID hanya jika KETIGANYA terpenuhi: gps_valid DAN device_valid DAN ip_valid.
 * Skor tetap dicatat untuk pelaporan, tetapi bukan penentu valid/rejected.
 *
 * Aturan device : device pertama milik user auto-approve, berikutnya 'pending' (ACC HR).
 *                 Hanya satu browser approved yang aktif untuk tiap user.
 * Aturan IP     : auto-learning -- IP dipelajari saat absen GPS+device valid
 *                 dari IP yang belum dikenal (source = 'auto').
 */
class Attendance
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
    private function learn_ip($ip, $user_id)
    {
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
