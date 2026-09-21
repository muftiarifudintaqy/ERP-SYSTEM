<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Attendance extends BaseController
{
    protected $public_methods = ['me', 'my_status', 'check_in', 'check_out', 'my_item', 'punch', 'punch_status'];
    protected $method_permissions = [
        'devices' => 'view',
        'device_item' => 'view',
        'approve_device' => 'view',
        'reject_device' => 'view',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');

        // Self-service absensi: bisa diakses semua user yang login (bukan hanya HR).
        // index()/item() tetap dilindungi RBAC modul 'attendance' (khusus HR).
        $this->set_public_methods($this->public_methods);
    }

    /**
     * Halaman absensi harian milik user sendiri (self-service).
     * Menampilkan status hari ini + riwayat absensi user yang login.
     */
    public function me()
    {
        if (empty($_SESSION['user'])) {
            redirect(base_url('auth/login'));
            return;
        }
        $user = $_SESSION['user'];
        $data['user']  = $user;
        $data['title'] = 'Absensi Saya - ' . $this->template->title();

        $status = $this->today_status($user['id'], date('Y-m-d'));
        $data['checked_in']       = $status['checked_in'];
        $data['checked_out']      = $status['checked_out'];
        $data['on_leave']         = $status['on_leave'];
        $data['today_attendance'] = $status['attendance'];

        $data['content'] = $this->load->view('attendance/me', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /** JSON status absensi hari ini untuk user yang login. */
    public function my_status()
    {
        if ($this->guard_ajax_login()) {
            return;
        }
        $user   = $_SESSION['user'];
        $status = $this->today_status($user['id'], date('Y-m-d'));

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'checked_in' => $status['checked_in'],
                'checked_out' => $status['checked_out'],
                'on_leave'   => $status['on_leave'],
            ]));
    }

    /** Proses check-in mandiri (AJAX). IP dibaca dari server, GPS/device dari client. */
    /** Status 4 titik absen hari ini untuk user login (untuk UI baru di me.php). */
    public function punch_status()
    {
        if ($this->guard_ajax_login()) { return; }
        $user  = $_SESSION['user'];
        $today = date('Y-m-d');
        $this->load->library('Attendance_service');
        $att = $this->mymodel->selectWithQuery("SELECT * FROM attendances WHERE user_id=".intval($user['id'])." AND attendance_date='".$this->db->escape_str($today)."' LIMIT 1");
        $att = !empty($att) ? $att[0] : null;

        $face = $this->mymodel->selectWithQuery("SELECT id FROM user_face_descriptors WHERE user_id=".intval($user['id'])." LIMIT 1");
        $photo_masuk = null;
        if (!empty($att)) {
            $pm = $this->mymodel->selectWithQuery("SELECT photo_path FROM attendance_punches WHERE attendance_id=".intval($att['id'])." AND kind='masuk' AND photo_path IS NOT NULL LIMIT 1");
            if (!empty($pm)) { $photo_masuk = $pm[0]['photo_path']; }
        }
        // avatar ikut data wajah: kalau wajah dihapus admin, avatar ikut hilang
        $fw = $this->mymodel->selectWithQuery("SELECT photo_path FROM user_face_descriptors WHERE user_id=".intval($user['id'])." LIMIT 1");
        if (empty($fw)) {
            $photo_masuk = null;
        } elseif (!empty($fw[0]['photo_path'])) {
            $photo_masuk = $fw[0]['photo_path'];
        }
        if (false) {
        }

        $this->output->set_content_type('application/json')->set_output(json_encode([
            'has_face_enrolled' => !empty($face),
            'masuk'            => $att['check_in_at']  ?? null,
            'istirahat_keluar' => $att['break_out_at'] ?? null,
            'istirahat_masuk'  => $att['break_in_at']  ?? null,
            'pulang'           => $att['check_out_at'] ?? null,
            'next_kind'        => $this->next_kind_label($att, $user['id']),
            // Jendela jam dikirim dari server karena bergantung jadwal
            // orangnya, hari Sabtu, dan izin setengah hari. Kalau
            // dipatok di JavaScript, tombolnya terkunci walau server
            // sudah menyatakan gilirannya tiba.
            'windows'          => $this->attendance_service->jendela_jam((int)$user['id']),
            'shift_live'       => $this->attendance_service->info_shift_live((int)$user['id']),
            'photo_masuk'      => $photo_masuk,
        ]));
    }

    private function next_kind_label($att, $user_id = 0)
    {
        if (empty($att) || empty($att['check_in_at']))  return 'masuk';

        // Jadwal tanpa istirahat, misalnya host live, hari Sabtu yang
        // pulang siang, atau yang punya izin setengah hari: langsung
        // dari masuk ke pulang. Tanpa ini tombol Absen Pulang tertahan
        // di 'belum giliran' karena menunggu absen istirahat yang
        // memang tidak pernah ada.
        if ($user_id) {
            $this->load->library('Attendance_service');
            if (!$this->attendance_service->pakai_istirahat((int)$user_id)) {
                return empty($att['check_out_at']) ? 'pulang' : null;
            }
        }

        if (empty($att['break_out_at'])) return 'istirahat_keluar';
        if (empty($att['break_in_at']))  return 'istirahat_masuk';
        if (empty($att['check_out_at'])) return 'pulang';
        return null;
    }

    /** Absen 4x sehari (masuk/istirahat_keluar/istirahat_masuk/pulang), device-aware, wajah untuk mobile. */
    public function punch()
    {
        if ($this->guard_ajax_login()) { return; }
        $user = $_SESSION['user'];
        $this->load->library('attendance_service');


        $result = $this->attendance_service->attempt_punch($user['id'], [
            'latitude'     => $this->input->post('latitude'),
            'longitude'    => $this->input->post('longitude'),
            'accuracy'     => $this->input->post('accuracy'),
            'device_token' => $this->input->post('device_token'),
            'ip'           => $this->input->ip_address(),
            'user_agent'   => $this->input->user_agent(),
            'descriptor'   => $this->input->post('descriptor'),
            'photo_base64' => $this->input->post('photo_base64'),
            'has_camera'   => $this->input->post('has_camera'),

        ]);

        $label_map = [
            'masuk' => 'Absen Masuk', 'istirahat_keluar' => 'Absen Istirahat',
            'istirahat_masuk' => 'Absen Masuk Kembali', 'pulang' => 'Absen Pulang',
        ];

        $officeName = null;
        if (!empty($result['office_location_id'])) {
            $off = $this->mymodel->selectWithQuery("SELECT name FROM office_locations WHERE id=".intval($result['office_location_id'])." LIMIT 1");
            if (!empty($off)) { $officeName = $off[0]['name']; }
        }

        $status_label = [
            'tepat'             => 'Hadir',
            'telat'             => 'Terlambat',
            'take_break'        => 'Take Break',
            'after_break'       => 'After Take Break',
            'telat_after_break' => 'Terlambat After Break',
            'back_to_home'      => 'Back To Home',
        ];

        $response = [
            'success'   => !empty($result['success']),
            'done_today'=> !empty($result['done_today']),
            'kind'      => $result['kind'] ?? null,
            'jenis'     => isset($result['kind']) ? ($label_map[$result['kind']] ?? '-') : '-',
            'waktu'     => date('H:i \W\I\B'),
            'device_type' => $result['device_type'] ?? '-',
            'status_text' => !empty($result['success']) ? ($status_label[$result['late_status'] ?? 'tepat'] ?? 'Tervalidasi') : '-',
            'lokasi'    => $officeName ? ($officeName . (isset($result['distance_m']) ? ' | ' . $result['distance_m'] . 'm' : '')) : '-',
            'shift'     => '-',
            'reasons'   => $result['reasons'] ?? [],
        ];

        if ($response['done_today']) {
            $response['message'] = 'Absensi hari ini sudah lengkap (4/4).';
        } elseif ($response['success']) {
            $response['message'] = $response['jenis'] . ' berhasil direkam.';
        } else {
            $response['message'] = 'Absensi gagal. ' . implode(' ', $response['reasons']);
        }

        $this->output->set_content_type('application/json')->set_output(json_encode($response));
    }

    public function check_in()
    {
        if ($this->guard_ajax_login()) {
            return;
        }
        $user = $_SESSION['user'];
        $this->load->library('attendance_service');

        $result = $this->attendance_service->attempt_check_in($user['id'], [
            'latitude'     => $this->input->post('latitude'),
            'longitude'    => $this->input->post('longitude'),
            'accuracy'     => $this->input->post('accuracy'),
            'device_token' => $this->input->post('device_token'),
            'ip'           => $this->input->ip_address(),
            'user_agent'   => $this->input->user_agent(),
        ]);

        $officeName = null;
        if (!empty($result['office_location_id'])) {
            $off = $this->mymodel->selectWithQuery("SELECT name FROM office_locations WHERE id=".intval($result['office_location_id'])." LIMIT 1");
            if (!empty($off)) { $officeName = $off[0]['name']; }
        }

        $response = [
            'success'            => !empty($result['success']),
            'already_checked_in' => !empty($result['already_checked_in']),
            'gps_valid'          => !empty($result['gps_valid']),
            'device_valid'       => !empty($result['device_valid']),
            'ip_valid'           => !empty($result['ip_valid']),
            'reasons'            => $result['reasons'] ?? [],
            'jenis'              => 'Absen Masuk',
            'waktu'              => date('H:i \W\I\B'),
            'status_text'        => (!empty($result['success']) || !empty($result['already_checked_in'])) ? 'Tepat Waktu' : '-',
            'lokasi'             => $officeName ? ($officeName . (isset($result['distance_m']) ? ' | ' . $result['distance_m'] . 'm' : '')) : '-',
            'shift'              => '-',
        ];

        if ($response['already_checked_in']) {
            $response['message'] = 'Kamu sudah absen hari ini.';
        } elseif ($response['success']) {
            $response['message'] = 'Absensi berhasil direkam. Selamat bekerja!';
        } else {
            $response['message'] = 'Absensi gagal. ' . implode(' ', $response['reasons']);
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /** Proses clock-out mandiri (AJAX). Rule validasi mengikuti check-in. */
    public function check_out()
    {
        if ($this->guard_ajax_login()) {
            return;
        }
        $user = $_SESSION['user'];
        $this->load->library('attendance_service');

        $result = $this->attendance_service->attempt_check_out($user['id'], [
            'latitude'     => $this->input->post('latitude'),
            'longitude'    => $this->input->post('longitude'),
            'accuracy'     => $this->input->post('accuracy'),
            'device_token' => $this->input->post('device_token'),
            'ip'           => $this->input->ip_address(),
            'user_agent'   => $this->input->user_agent(),
        ]);

        $officeName = null;
        if (!empty($result['office_location_id'])) {
            $off = $this->mymodel->selectWithQuery("SELECT name FROM office_locations WHERE id=".intval($result['office_location_id'])." LIMIT 1");
            if (!empty($off)) { $officeName = $off[0]['name']; }
        }

        $response = [
            'success'             => !empty($result['success']),
            'not_checked_in'      => !empty($result['not_checked_in']),
            'already_checked_out' => !empty($result['already_checked_out']),
            'gps_valid'           => !empty($result['gps_valid']),
            'device_valid'        => !empty($result['device_valid']),
            'ip_valid'            => !empty($result['ip_valid']),
            'reasons'             => $result['reasons'] ?? [],
            'jenis'               => 'Absen Pulang',
            'waktu'               => date('H:i \W\I\B'),
            'status_text'         => (!empty($result['success']) || !empty($result['already_checked_out'])) ? 'Tepat Waktu' : '-',
            'lokasi'              => $officeName ? ($officeName . (isset($result['distance_m']) ? ' | ' . $result['distance_m'] . 'm' : '')) : '-',
            'shift'               => '-',
        ];

        if ($response['not_checked_in']) {
            $response['message'] = 'Kamu belum check-in hari ini.';
        } elseif ($response['already_checked_out']) {
            $response['message'] = 'Kamu sudah clock-out hari ini.';
        } elseif ($response['success']) {
            $response['message'] = 'Clock-out berhasil direkam.';
        } else {
            $response['message'] = 'Clock-out gagal. ' . implode(' ', $response['reasons']);
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /** Riwayat absensi user yang login (AJAX, untuk halaman me()). */
    public function my_item()
    {
        if ($this->guard_ajax_login()) {
            return;
        }
        $user = $_SESSION['user'];
        $uid  = (int) $user['id'];

        $limit        = 15;
        $current_page = (int) ($_GET['page'] ?? 1);
        $offset       = $current_page > 1 ? ($current_page - 1) * $limit : 0;

        $query = $this->mymodel->selectWithQuery("SELECT a.*, o.name AS office_name,
                (SELECT COUNT(*) FROM leave_requests lr
                    WHERE lr.user_id = a.user_id
                    AND lr.status = 'approved'
                    AND lr.jam_mulai IS NULL
                    AND a.attendance_date BETWEEN lr.start_date AND lr.end_date) AS on_leave
            FROM attendances a
            LEFT JOIN office_locations o ON a.office_location_id = o.id
            WHERE a.user_id = $uid
            ORDER BY a.attendance_date DESC, a.check_in_at DESC
            LIMIT $offset, $limit");

        $data['data']  = $query;
        $data['start'] = $offset;

        $this->load->view('attendance/my_item', $data);
    }

    /** Pastikan request AJAX punya sesi login; balas 401 JSON bila tidak. */
    private function guard_ajax_login()
    {
        if (!empty($_SESSION['user'])) {
            return false;
        }
        $this->output
            ->set_status_header(401)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => false,
                'message' => 'Sesi berakhir. Silakan login ulang.',
            ]));
        return true;
    }

    /** Status absensi & cuti untuk satu user pada satu tanggal. */
    private function today_status($user_id, $today)
    {
        $uid  = (int) $user_id;
        $date = $this->db->escape_str($today);

        $att = $this->mymodel->selectWithQuery("SELECT * FROM attendances
            WHERE user_id = $uid AND attendance_date = '$date' LIMIT 1");
        $attendance = !empty($att) ? $att[0] : null;

        $leave = $this->mymodel->selectWithQuery("SELECT COUNT(*) AS c FROM leave_requests
            WHERE user_id = $uid AND status = 'approved'
            AND jam_mulai IS NULL
            AND '$date' BETWEEN start_date AND end_date");
        $on_leave = !empty($leave) && intval($leave[0]['c']) > 0;

        return [
            'checked_in' => !empty($attendance),
            'checked_out' => !empty($attendance) && !empty($attendance['check_out_at']),
            'on_leave'   => $on_leave,
            'attendance' => $attendance,
        ];
    }

    /**
     * Hanya role pengelola (developer/super_admin/head_admin/owner) yang boleh
     * melihat rekap absensi SELURUH karyawan. Karyawan biasa hanya bisa
     * mengakses attendance/me (data dirinya sendiri).
     */
    private function _boleh_rekap()
    {
        $uid = intval($_SESSION['user']['id'] ?? 0);
        if (!$uid) return false;
        $r = $this->mymodel->selectWithQuery("SELECT ur.id FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=$uid AND r.name IN ('developer','super_admin','head_admin','owner') LIMIT 1");
        return !empty($r);
    }

    public function index()
    {
        if (!$this->_boleh_rekap()) {
            redirect(base_url('attendance/me'));
            return;
        }
        $data['user'] = $_SESSION['user'];

        // Permission check handled by BaseController middleware

        $keyword = $_GET['keyword'] ?? "";
        $date_filter = $_GET['date_filter'] ?? "";
        $status_filter = $_GET['status_filter'] ?? "";

        $data['keyword'] = $keyword;
        $data['date_filter'] = $date_filter;
        $data['status_filter'] = $status_filter;
        $data['title'] = 'Attendance Management - ' . $this->template->title();

        $unit_filter = $_GET['unit_filter'] ?? "";
        $data['unit_filter'] = $unit_filter;
        $qry = $this->build_filter($keyword, $date_filter, $status_filter, $unit_filter);

        // JOIN ke positions wajib ada di sini juga: build_filter bisa
        // menambahkan syarat p.department, dan tanpa JOIN-nya query
        // gagal diam-diam lalu halaman menyatakan tidak ada data.
        $query = $this->mymodel->selectWithQuery("SELECT COUNT(a.id) AS count
            FROM attendances a
            LEFT JOIN user u ON a.user_id = u.id
            LEFT JOIN user_profile up ON u.id = up.user_id
            LEFT JOIN positions p ON p.id = up.position_id
            WHERE $qry");
        $total = !empty($query) ? intval($query[0]['count']) : 0;

        $pending_device = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM user_devices WHERE status = 'pending'");
        $data['pending_device_count'] = !empty($pending_device) ? intval($pending_device[0]['count']) : 0;

        $date_for_stat = $date_filter !== '' ? $this->db->escape_str($date_filter) : $this->db->escape_str(date('Y-m-d'));
        $stat = $this->mymodel->selectWithQuery("
            SELECT
              (SELECT COUNT(*) FROM user WHERE status='Aktif') AS total_karyawan,
              (SELECT COUNT(*) FROM attendances WHERE attendance_date='$date_for_stat' AND check_in_at IS NOT NULL) AS hadir,
              (SELECT COUNT(*) FROM attendance_punches WHERE kind='masuk' AND late_status='telat' AND DATE(punched_at)='$date_for_stat') AS terlambat,
              (SELECT COUNT(*) FROM user_face_descriptors ufd
                 JOIN attendance_punches ap ON ap.user_id=ufd.user_id AND ap.kind='masuk' AND DATE(ap.punched_at)='$date_for_stat'
                 WHERE ap.face_distance IS NOT NULL AND ap.face_distance > 0.42) AS wajah_review
        ");
        $data['stat_total']    = !empty($stat) ? intval($stat[0]['total_karyawan']) : 0;
        $data['stat_hadir']    = !empty($stat) ? intval($stat[0]['hadir']) : 0;
        $data['stat_telat']    = !empty($stat) ? intval($stat[0]['terlambat']) : 0;
        $data['stat_tidak_hadir'] = max(0, $data['stat_total'] - $data['stat_hadir']);
        $data['stat_wajah_review'] = !empty($stat) ? intval($stat[0]['wajah_review']) : 0;
        $data['date_for_stat'] = $date_for_stat;

        $units = $this->mymodel->selectWithQuery("SELECT DISTINCT p.department FROM positions p WHERE p.department IS NOT NULL AND p.department <> '' ORDER BY p.department");
        $data['units'] = $units;

        $data['page'] = CEIL($total / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($total) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("attendance/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {
        if (!$this->_boleh_rekap()) {
            echo '<tr><td colspan="8" class="text-center text-danger">Akses ditolak.</td></tr>';
            return;
        }
        $data['template'] = $this->template;

        $keyword = $_GET['keyword'] ?? "";
        $date_filter = $_GET['date_filter'] ?? "";
        $status_filter = $_GET['status_filter'] ?? "";
        // Filter unit ikut dibaca di sini. Tanpa ini hitungan di halaman
        // memakai filter unit tapi daftar barisnya tidak, sehingga
        // angkanya benar tapi tabelnya menampilkan semua orang.
        $unit_filter = $_GET['unit_filter'] ?? "";

        $qry = $this->build_filter($keyword, $date_filter, $status_filter, $unit_filter);

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;

        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT a.*, u.full_name,
                p.name AS position_name, p.department AS department_name, o.name AS office_name, up.is_wfo,
                pm.late_status AS masuk_late_status, pm.photo_path AS masuk_photo, pm.distance_m AS distance_m, pm.face_distance AS masuk_face_distance, pm.device_type AS masuk_device_type, pm.device_os AS masuk_device_os, pm.face_valid AS masuk_face_valid, pm.suspect_reason AS masuk_suspect, pb.late_status AS break_late_status,
                pm.mode AS masuk_mode, jd.jam_masuk AS jadwal_masuk, jd.nama_jadwal AS jadwal_nama,
                pp.suspect_reason AS pulang_suspect,
                (SELECT COUNT(*) FROM leave_requests lr
                    WHERE lr.user_id = a.user_id
                    AND lr.status = 'approved'
                    AND lr.jam_mulai IS NULL
                    AND a.attendance_date BETWEEN lr.start_date AND lr.end_date) AS on_leave
            FROM attendances a
            LEFT JOIN user u ON a.user_id = u.id
            LEFT JOIN user_profile up ON u.id = up.user_id
            LEFT JOIN positions p ON up.position_id = p.id
            LEFT JOIN office_locations o ON a.office_location_id = o.id
            LEFT JOIN attendance_punches pm ON pm.attendance_id = a.id AND pm.kind = 'masuk'
            LEFT JOIN attendance_punches pb ON pb.attendance_id = a.id AND pb.kind = 'istirahat_masuk'
            LEFT JOIN hrd_jadwal jd ON jd.user_id = a.user_id AND jd.aktif = 1
            LEFT JOIN attendance_punches pp ON pp.attendance_id = a.id AND pp.kind = 'pulang'
            WHERE $qry
            ORDER BY a.attendance_date DESC, a.check_in_at DESC
            LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;

        $this->load->view("attendance/item", $data);
    }

    /**
     * Siapa saja yang hari ini belum absen masuk.
     *
     * Yang sedang cuti disetujui tidak ikut ditampilkan, karena mereka
     * memang tidak diharapkan absen. Begitu juga yang hari ini bukan
     * hari kerjanya menurut jadwal.
     */
    public function belum()
    {
        if (!$this->_boleh_rekap()) { show_404(); }

        $tgl = $this->input->get('tgl');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$tgl)) { $tgl = date('Y-m-d'); }
        $hari = (int)date('N', strtotime($tgl));

        $bulan = $this->input->get('bulan');
        if (!preg_match('/^\d{4}-\d{2}$/', (string)$bulan)) { $bulan = substr($tgl, 0, 7); }

        // Hari libur dilewati: tanggal merah bukan bolos.
        $libur = [];
        foreach ($this->mymodel->selectWithQuery(
            "SELECT tanggal, keterangan FROM hrd_hari_libur") as $l) {
            $libur[$l['tanggal']] = $l['keterangan'];
        }

        $rows = $this->mymodel->selectWithQuery("
            SELECT u.id, u.full_name, u.role_text,
                   p.name AS position_name,
                   j.nama_jadwal, j.jam_masuk, j.hari_kerja, j.hari_wfh,
                   (SELECT COUNT(*) FROM user_face_descriptors f WHERE f.user_id = u.id) AS ada_wajah,
                   (SELECT COUNT(*) FROM user_devices d WHERE d.user_id = u.id AND d.status = 'approved') AS ada_device,
                   (SELECT COUNT(*) FROM leave_requests lr
                     WHERE lr.user_id = u.id AND lr.status = 'approved'
                       AND '$tgl' BETWEEN lr.start_date AND lr.end_date) AS sedang_cuti
            FROM user u
            LEFT JOIN user_profile up ON up.user_id = u.id
            LEFT JOIN positions p ON p.id = up.position_id
            LEFT JOIN hrd_jadwal j ON j.user_id = u.id AND j.aktif = 1
            WHERE u.status IN ('active','1','aktif')
              AND NOT EXISTS (
                    SELECT 1 FROM attendances a
                     WHERE a.user_id = u.id
                       AND a.attendance_date = '$tgl'
                       AND a.check_in_at IS NOT NULL)
            ORDER BY u.full_name ASC");

        // Shift host live ditentukan harian, bukan disimpan di hrd_jadwal.
        // Tanpa ini halaman menulis "jadwal 08:00" untuk host yang
        // sebenarnya kebagian sore, dan mereka mengira sudah telat.
        $shift = [];
        foreach ($this->mymodel->selectWithQuery(
            "SELECT user_id, shift FROM live_shift_harian WHERE tanggal = '$tgl'") as $sh) {
            $shift[(int)$sh['user_id']] = (int)$sh['shift'];
        }
        $slot1 = count(array_filter($shift, function ($v) { return $v === 1; }));

        $daftar = [];
        if (!isset($libur[$tgl])) {
            foreach ($rows as $r) {
                $hk = $r['hari_kerja'] ?: '1,2,3,4,5';
                if (!in_array((string)$hari, array_map('trim', explode(',', $hk)), TRUE)) continue;

                if (($r['nama_jadwal'] ?? '') === 'Host Live') {
                    // Yang sudah terkunci dipakai apa adanya. Yang belum
                    // absen memakai perkiraan: slot Shift 1 penuh berarti
                    // dia kebagian Shift 2.
                    $sl = isset($shift[(int)$r['id']])
                        ? $shift[(int)$r['id']]
                        : (($slot1 >= 2) ? 2 : 1);
                    $r['jam_masuk']   = ($sl === 2) ? '15:00:00' : '08:00:00';
                    $r['shift_live']  = $sl;
                    $r['shift_pasti'] = isset($shift[(int)$r['id']]);
                }

                $daftar[] = $r;
            }
        }

        /* ---------- rekap sebulan ---------- */
        $awal_bln  = $bulan . '-01';
        $akhir_bln = date('Y-m-t', strtotime($awal_bln));
        $hari_ini  = date('Y-m-d');
        // Tanggal yang belum lewat tidak dihitung: orang belum punya
        // kesempatan absen di hari yang belum datang.
        if ($akhir_bln > $hari_ini) { $akhir_bln = $hari_ini; }

        $semua = $this->mymodel->selectWithQuery("
            SELECT u.id, u.full_name, p.name AS position_name,
                   j.hari_kerja
            FROM user u
            LEFT JOIN user_profile up ON up.user_id = u.id
            LEFT JOIN positions p ON p.id = up.position_id
            LEFT JOIN hrd_jadwal j ON j.user_id = u.id AND j.aktif = 1
            WHERE u.status IN ('active','1','aktif')
            ORDER BY u.full_name ASC");

        $hadir = [];
        foreach ($this->mymodel->selectWithQuery("
            SELECT user_id, attendance_date FROM attendances
            WHERE attendance_date BETWEEN '$awal_bln' AND '$akhir_bln'
              AND check_in_at IS NOT NULL") as $a) {
            $hadir[$a['user_id']][$a['attendance_date']] = TRUE;
        }

        $cuti = [];
        foreach ($this->mymodel->selectWithQuery("
            SELECT lr.user_id, lr.start_date, lr.end_date
            FROM leave_requests lr
            WHERE lr.status = 'approved'
              AND lr.start_date <= '$akhir_bln' AND lr.end_date >= '$awal_bln'") as $c) {
            $d = strtotime($c['start_date']);
            $s = strtotime($c['end_date']);
            while ($d <= $s) { $cuti[$c['user_id']][date('Y-m-d', $d)] = TRUE; $d = strtotime('+1 day', $d); }
        }

        $rekap = [];
        foreach ($semua as $u) {
            $hk = array_map('trim', explode(',', $u['hari_kerja'] ?: '1,2,3,4,5'));
            $bolos = 0; $izin = 0; $kerja = 0; $tanggal_bolos = [];

            $d = strtotime($awal_bln);
            $s = strtotime($akhir_bln);
            while ($d <= $s) {
                $td = date('Y-m-d', $d);
                $hn = (string)(int)date('N', $d);
                $d  = strtotime('+1 day', $d);

                if (isset($libur[$td])) continue;               // tanggal merah
                if (!in_array($hn, $hk, TRUE)) continue;        // bukan hari kerjanya
                $kerja++;

                if (!empty($hadir[$u['id']][$td])) continue;    // hadir
                if (!empty($cuti[$u['id']][$td]))  { $izin++; continue; }
                $bolos++;
                $tanggal_bolos[] = $td;
            }

            if ($kerja === 0) continue;
            $rekap[] = [
                'nama'          => $u['full_name'],
                'posisi'        => $u['position_name'] ?: '-',
                'hari_kerja'    => $kerja,
                'hadir'         => $kerja - $bolos - $izin,
                'izin'          => $izin,
                'bolos'         => $bolos,
                'tanggal_bolos' => $tanggal_bolos,
            ];
        }

        // Yang paling sering tidak absen ditaruh di atas: itu yang
        // perlu ditindaklanjuti lebih dulu.
        usort($rekap, function ($a, $b) {
            if ($a['bolos'] === $b['bolos']) return strcasecmp($a['nama'], $b['nama']);
            return $b['bolos'] - $a['bolos'];
        });

        $this->load->view('attendance/belum', [
            'template'    => $this->template,
            'rows'        => $daftar,
            'hari'        => $hari,
            'tgl'         => $tgl,
            'bulan'       => $bulan,
            'libur_hari'  => $libur[$tgl] ?? NULL,
            'rekap'       => $rekap,
            'akhir_rekap' => $akhir_bln,
        ]);
    }

    public function devices()
    {
        $data['user'] = $_SESSION['user'];

        $keyword = $_GET['keyword'] ?? "";
        $status_filter = $_GET['status_filter'] ?? "";

        $data['keyword'] = $keyword;
        $data['status_filter'] = $status_filter;
        $data['title'] = 'Attendance Device Management - ' . $this->template->title();

        $qry = $this->build_device_filter($keyword, $status_filter);

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(ud.id) AS count
            FROM user_devices ud
            LEFT JOIN user u ON ud.user_id = u.id
            WHERE $qry");
        $total = !empty($query) ? intval($query[0]['count']) : 0;

        $pending = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM user_devices WHERE status = 'pending'");
        $data['pending_count'] = !empty($pending) ? intval($pending[0]['count']) : 0;

        $data['page'] = CEIL($total / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($total) . ' perangkat ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("attendance/devices", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function device_item()
    {
        $keyword = $_GET['keyword'] ?? "";
        $status_filter = $_GET['status_filter'] ?? "";

        $qry = $this->build_device_filter($keyword, $status_filter);

        $limit = 10;
        $current_page = intval($_GET['page'] ?? 1);
        $offset = $current_page <= 1 ? 0 : ($current_page - 1) * $limit;

        $query = $this->mymodel->selectWithQuery("SELECT ud.*, u.full_name,
                approver.full_name AS approved_by_name,
                active_device.id AS active_device_id
            FROM user_devices ud
            LEFT JOIN user u ON ud.user_id = u.id
            LEFT JOIN user approver ON ud.approved_by = approver.id
            LEFT JOIN (
                SELECT d1.user_id, d1.id
                FROM user_devices d1
                INNER JOIN (
                    SELECT user_id, MAX(CONCAT(COALESCE(DATE_FORMAT(approved_at, '%Y%m%d%H%i%s'), '00000000000000'), LPAD(id, 10, '0'))) AS active_key
                    FROM user_devices
                    WHERE status = 'approved'
                    GROUP BY user_id
                ) d2 ON d1.user_id = d2.user_id
                    AND CONCAT(COALESCE(DATE_FORMAT(d1.approved_at, '%Y%m%d%H%i%s'), '00000000000000'), LPAD(d1.id, 10, '0')) = d2.active_key
            ) active_device ON ud.user_id = active_device.user_id
            WHERE $qry
            ORDER BY FIELD(ud.status, 'pending', 'approved', 'rejected'), ud.last_used_at DESC, ud.created_at DESC
            LIMIT $offset, $limit");

        $data['data'] = $query;
        $data['start'] = $offset;

        $this->load->view("attendance/device_item", $data);
    }

    public function approve_device()
    {
        $this->process_device_status('approved');
    }

    public function reject_device()
    {
        $this->process_device_status('rejected');
    }

    private function process_device_status($status)
    {
        $id = intval($this->input->post('id'));
        $hr_id = intval($_SESSION['user']['id'] ?? 0);

        if ($id <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
            return $this->json_response(false, 'Data perangkat tidak valid.', 400);
        }

        $device = $this->db
            ->where('id', $id)
            ->get('user_devices')
            ->row_array();

        if (empty($device)) {
            return $this->json_response(false, 'Perangkat tidak ditemukan.', 404);
        }

        $this->db->trans_begin();

        if ($status === 'approved') {
            $this->db
                ->where('user_id', $device['user_id'])
                ->where('id !=', $id)
                ->where('status', 'approved')
                ->update('user_devices', [
                    'status' => 'rejected',
                ]);

            $this->db
                ->where('id', $id)
                ->update('user_devices', [
                    'status' => 'approved',
                    'approved_by' => $hr_id,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]);

            $message = 'Perangkat disetujui. Browser approved lama user ini otomatis dinonaktifkan.';
        } else {
            $this->db
                ->where('id', $id)
                ->update('user_devices', [
                    'status' => 'rejected',
                    'approved_by' => null,
                    'approved_at' => null,
                ]);

            $message = 'Perangkat ditolak.';
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return $this->json_response(false, 'Gagal menyimpan status perangkat.', 500);
        }

        $this->db->trans_commit();
        return $this->json_response(true, $message);
    }

    private function json_response($success, $message, $status_code = 200)
    {
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => $success,
                'message' => $message,
            ]));
    }

    /**
     * Bangun klausa WHERE dari filter pencarian (nama, tanggal, status).
     */
    private function build_filter($keyword, $date_filter, $status_filter, $unit_filter = "")
    {
        $qry = "1=1";

        if ($keyword !== "") {
            $keyword = $this->db->escape_str($keyword);
            $qry .= " AND u.full_name LIKE '%$keyword%'";
        }

        // rentang_tabel
        // Penyaring rentang. Dipakai tombol Terapkan pada pemilih periode
        // supaya isi tabel sama dengan isi berkas yang diunduh. Penyaring
        // tanggal tunggal di bawah tetap berlaku seperti biasa ketika
        // rentangnya tidak dikirim.
        $r_dari   = (string)$this->input->get('dari');
        $r_sampai = (string)$this->input->get('sampai');
        $pola_tgl = '/^\d{4}-\d{2}-\d{2}$/';

        if (preg_match($pola_tgl, $r_dari) && preg_match($pola_tgl, $r_sampai)) {
            if (strtotime($r_dari) > strtotime($r_sampai)) {
                $tukar = $r_dari; $r_dari = $r_sampai; $r_sampai = $tukar;
            }
            $qry .= " AND a.attendance_date BETWEEN '"
                  . $this->db->escape_str($r_dari) . "' AND '"
                  . $this->db->escape_str($r_sampai) . "'";
            $date_filter = '';
        }

        if ($date_filter !== "") {
            // Terima dua format tanggal: yang dikirim <input type=date>
            // (2026-09-07) dan yang diketik manusia (07/09/2026).
            // Tanpa ini penyaring tanggal diam-diam tidak pernah cocok.
            if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $date_filter, $m)) {
                $date_filter = $m[3] . '-' . $m[2] . '-' . $m[1];
            }
            $date_filter = $this->db->escape_str($date_filter);
            $qry .= " AND a.attendance_date = '$date_filter'";
        }

        if ($unit_filter !== "") {
            $unit_filter = $this->db->escape_str($unit_filter);
            $qry .= " AND p.department = '$unit_filter'";
        }

        if ($status_filter === "hadir") {
            $qry .= " AND a.check_in_at IS NOT NULL";
        } else if ($status_filter === "telat") {
            $qry .= " AND EXISTS (SELECT 1 FROM attendance_punches ap WHERE ap.attendance_id=a.id AND ap.kind='masuk' AND ap.late_status='telat')";
        } else if ($status_filter === "alfa") {
            $qry .= " AND a.check_in_at IS NULL";
        } else if ($status_filter === "izin") {
            $qry .= " AND EXISTS (SELECT 1 FROM leave_requests lr WHERE lr.user_id=a.user_id AND lr.status='approved' AND lr.jam_mulai IS NULL AND a.attendance_date BETWEEN lr.start_date AND lr.end_date)";
        } else if ($status_filter === "counted") {
            $qry .= " AND a.status = 'valid' AND up.is_wfo = 1";
            $qry .= " AND NOT EXISTS (SELECT 1 FROM leave_requests lr
                        WHERE lr.user_id = a.user_id
                        AND lr.status = 'approved'
                        AND a.attendance_date BETWEEN lr.start_date AND lr.end_date)";
        } else if ($status_filter !== "") {
            $status_filter = $this->db->escape_str($status_filter);
            $qry .= " AND a.status = '$status_filter'";
        }

        return $qry;
    }

    private function build_device_filter($keyword, $status_filter)
    {
        $qry = "1=1";

        if ($keyword !== "") {
            $keyword = $this->db->escape_str($keyword);
            $qry .= " AND (u.full_name LIKE '%$keyword%' OR ud.user_agent LIKE '%$keyword%' OR ud.device_token LIKE '%$keyword%')";
        }

        if ($status_filter !== "") {
            $status_filter = $this->db->escape_str($status_filter);
            $qry .= " AND ud.status = '$status_filter'";
        }

        return $qry;
    }

    /* ===== PENDAFTARAN WAJAH ===== */
    public function enroll_face()
    {
        $uid = $_SESSION['user']['id'];
        $sudah = $this->mymodel->selectWithQuery("SELECT id FROM user_face_descriptors WHERE user_id=".intval($uid)." LIMIT 1");
        $data['sudah_daftar'] = !empty($sudah);
        $data['nama_user']    = $_SESSION['user']['full_name'];
        $this->load->view('attendance/enroll_face', $data);
    }

    // Ambang untuk menolak pendaftaran ganda. Sengaja lebih ketat dari
    // ambang absen (0.5): dua orang berbeda bisa berjarak 0.4-0.5, jadi
    // memakai 0.5 di sini menolak orang yang sebenarnya bukan duplikat.
    const ENROLL_DISTANCE_THRESHOLD = 0.35;

    public function save_face_descriptor()
    {
        header('Content-Type: application/json');
        $uid = $_SESSION['user']['id'];
        $desc = $this->input->post('descriptor');
        $arr = json_decode($desc, true);
        if (!is_array($arr) || count($arr) !== 128) {
            echo json_encode(array('success'=>false,'message'=>'Data wajah tidak valid, coba ulangi.'));
            return;
        }

        // Cegah 1 wajah didaftarkan ke lebih dari satu akun: bandingkan ke SEMUA
        // descriptor milik user LAIN, tolak kalau ada yang mirip di bawah threshold.
        $lain = $this->mymodel->selectWithQuery("SELECT user_id, descriptor, (SELECT full_name FROM user WHERE id=user_face_descriptors.user_id) AS nama FROM user_face_descriptors WHERE user_id != ".intval($uid));
        foreach ($lain as $row) {
            $known = json_decode($row['descriptor'], true);
            if (!is_array($known)) continue;
            $jarak = $this->_face_distance($arr, $known);
            if ($jarak <= self::ENROLL_DISTANCE_THRESHOLD) {
                echo json_encode(array('success'=>false,'message'=>'Wajah ini sudah terdaftar untuk akun lain. Tidak bisa didaftarkan ulang ke akun berbeda.'));
                return;
            }
        }

        $json = json_encode($arr);
        $ada = $this->mymodel->selectWithQuery("SELECT id FROM user_face_descriptors WHERE user_id=".intval($uid)." LIMIT 1");
        if (!empty($ada)) {
            $upd = array('descriptor'=>$json);
            $fp = $this->_simpan_foto_wajah($this->input->post('photo_base64'), $uid);
            if ($fp) { $upd['photo_path'] = $fp; }
            $this->db->where('user_id', $uid)->update('user_face_descriptors', $upd);
        } else {
            $this->db->insert('user_face_descriptors', array('user_id'=>$uid, 'descriptor'=>$json, 'photo_path'=>$this->_simpan_foto_wajah($this->input->post('photo_base64'), $uid), 'created_at'=>date('Y-m-d H:i:s')));
        }
        echo json_encode(array('success'=>true,'message'=>'Wajah berhasil didaftarkan.'));
    }

    private function _simpan_foto_wajah($base64, $uid)
    {
        if (empty($base64)) return null;
        if (strpos($base64, 'base64,') !== false) { $base64 = substr($base64, strpos($base64, 'base64,') + 7); }
        $bin = base64_decode($base64);
        if ($bin === false || strlen($bin) < 500) return null;
        $dir = FCPATH . 'uploads/face_enroll/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $nama = 'face_u' . intval($uid) . '_' . date('Ymd_His') . '.jpg';
        if (@file_put_contents($dir . $nama, $bin) === false) return null;
        return 'uploads/face_enroll/' . $nama;
    }

    private function _face_distance($a, $b)
    {
        if (count($a) !== count($b)) return 999;
        $sum = 0;
        for ($i = 0; $i < count($a); $i++) { $sum += pow(floatval($a[$i]) - floatval($b[$i]), 2); }
        return sqrt($sum);
    }

    /* ===== ADMIN: DAFTAR WAJAH TERDAFTAR ===== */
    public function face_list()
    {
        if (!$this->_boleh_rekap()) { redirect(base_url('attendance/me')); return; }
        $data['user']  = $_SESSION['user'];
        $data['title'] = 'Daftar Wajah Karyawan - ' . $this->template->title();
        $data['baris'] = $this->mymodel->selectWithQuery("
            SELECT f.id, f.user_id, f.photo_path, f.created_at, f.updated_at,
                   u.full_name, p.name AS position_name, p.department AS department_name
            FROM user_face_descriptors f
            LEFT JOIN user u ON u.id = f.user_id
            LEFT JOIN user_profile up ON up.user_id = u.id
            LEFT JOIN positions p ON p.id = up.position_id
            ORDER BY u.full_name");
        $data['belum'] = $this->mymodel->selectWithQuery("
            SELECT u.id, u.full_name FROM user u
            WHERE u.status='Aktif' AND u.id NOT IN (SELECT user_id FROM user_face_descriptors)
            ORDER BY u.full_name");
        $data['content'] = $this->load->view('attendance/face_list', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function delete_face()
    {
        header('Content-Type: application/json');
        if (!$this->_boleh_rekap()) { echo json_encode(array('success'=>false,'message'=>'Ditolak.')); return; }
        $uid = intval($this->input->post('user_id'));
        $r = $this->mymodel->selectWithQuery("SELECT photo_path FROM user_face_descriptors WHERE user_id=$uid LIMIT 1");
        if (!empty($r) && !empty($r[0]['photo_path'])) {
            $p = FCPATH . $r[0]['photo_path'];
            if (is_file($p)) @unlink($p);
        }
        $this->db->query("DELETE FROM user_face_descriptors WHERE user_id=$uid");
        echo json_encode(array('success'=>true,'message'=>'Data wajah dihapus. User bisa daftar ulang.'));
    }

    /* ===== ADMIN: EDIT JAM ABSEN ===== */
    public function attendance_detail()
    {
        header('Content-Type: application/json');
        if (!$this->_boleh_rekap()) { echo json_encode(array('success'=>false)); return; }
        $id = intval($this->input->get('id'));
        $r = $this->mymodel->selectWithQuery("SELECT a.id, a.attendance_date, a.check_in_at, a.break_out_at, a.break_in_at, a.check_out_at, u.full_name FROM attendances a LEFT JOIN user u ON u.id=a.user_id WHERE a.id=$id LIMIT 1");
        if (empty($r)) { echo json_encode(array('success'=>false)); return; }
        echo json_encode(array('success'=>true,'data'=>$r[0]));
    }

    /**
     * Buat baris absen untuk orang yang belum punya sama sekali di tanggal itu.
     *
     * Dipakai kalau seseorang benar hadir tapi tidak sempat absen -- akunnya
     * terkunci, HP-nya bermasalah, atau lupa. Sengaja TIDAK menyentuh
     * wa_antrean: notifikasi grup adalah kabar bahwa seseorang baru saja
     * menekan tombol absen, dan itu tidak terjadi di sini. Mengirimnya
     * justru membuat catatan WA berbeda dari kenyataan.
     *
     * Ditandai di suspect_reason supaya siapa pun yang membaca rekap tahu
     * baris ini diisi tangan, lengkap dengan siapa yang mengisinya.
     */
    public function tambah_absen_manual()
    {
        header('Content-Type: application/json');
        if (!$this->_boleh_rekap()) { echo json_encode(['success'=>false,'message'=>'Ditolak.']); return; }

        $uid = (int) $this->input->post('user_id');
        $tgl = trim((string) $this->input->post('tanggal'));
        $alasan = trim((string) $this->input->post('alasan'));

        if (!$uid || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
            echo json_encode(['success'=>false,'message'=>'Karyawan dan tanggal wajib diisi.']); return;
        }
        if ($alasan === '') {
            echo json_encode(['success'=>false,'message'=>'Alasan wajib diisi.']); return;
        }

        $ada = $this->mymodel->selectWithQuery(
            "SELECT id FROM attendances WHERE user_id=$uid AND attendance_date='"
            . $this->db->escape_str($tgl) . "' LIMIT 1");
        if (!empty($ada)) {
            echo json_encode(['success'=>false,'message'=>'Orang ini sudah punya data absen di tanggal tersebut. Pakai tombol edit jam.']); return;
        }

        $map = ['check_in_at'=>'masuk', 'break_out_at'=>'istirahat_keluar',
                'break_in_at'=>'istirahat_masuk', 'check_out_at'=>'pulang'];
        $jam = [];
        foreach ($map as $kolom => $post) {
            $v = trim((string) $this->input->post($post));
            if ($v === '') { $jam[$kolom] = null; continue; }
            if (!preg_match('/^\d{2}:\d{2}$/', $v)) {
                echo json_encode(['success'=>false,'message'=>'Format jam harus HH:MM.']); return;
            }
            $jam[$kolom] = $tgl . ' ' . $v . ':00';
        }

        if (empty($jam['check_in_at']) && empty($jam['check_out_at'])) {
            echo json_encode(['success'=>false,'message'=>'Isi minimal jam masuk atau jam pulang.']); return;
        }

        $oleh = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['username'] ?? 'admin');
        $ket  = 'Diisi manual oleh ' . $oleh . ' - ' . $alasan;

        $this->db->insert('attendances', array_merge($jam, [
            'user_id' => $uid, 'attendance_date' => $tgl,
            'gps_valid' => 0, 'device_valid' => 0, 'ip_valid' => 0, 'score' => 0,
            'status' => 'valid', 'created_at' => date('Y-m-d H:i:s'),
        ]));
        $att_id = $this->db->insert_id();

        foreach ($map as $kolom => $kind) {
            if (empty($jam[$kolom])) continue;
            $this->db->insert('attendance_punches', [
                'attendance_id' => $att_id, 'user_id' => $uid, 'kind' => $kind,
                'punched_at' => $jam[$kolom], 'device_type' => 'desktop',
                'device_os' => 'input manual', 'suspect_reason' => $ket,
                'gps_valid' => 0, 'device_valid' => 0, 'ip_valid' => 0, 'face_valid' => 0,
                'status' => 'valid', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        echo json_encode(['success'=>true,'message'=>'Data absen dibuat.']);
    }

    public function update_attendance_time()
    {
        header('Content-Type: application/json');
        if (!$this->_boleh_rekap()) { echo json_encode(array('success'=>false,'message'=>'Ditolak.')); return; }
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT id, attendance_date FROM attendances WHERE id=$id LIMIT 1");
        if (empty($r)) { echo json_encode(array('success'=>false,'message'=>'Data tidak ditemukan.')); return; }
        $tgl = $r[0]['attendance_date'];

        $map = array('check_in_at'=>'masuk', 'break_out_at'=>'istirahat_keluar', 'break_in_at'=>'istirahat_masuk', 'check_out_at'=>'pulang');
        $upd = array();
        foreach ($map as $kolom => $post) {
            $jam = trim($this->input->post($post));
            if ($jam === '') { $upd[$kolom] = null; continue; }
            if (!preg_match('/^\d{2}:\d{2}$/', $jam)) { echo json_encode(array('success'=>false,'message'=>'Format jam harus HH:MM.')); return; }
            $upd[$kolom] = $tgl . ' ' . $jam . ':00';
        }
        $this->db->where('id', $id)->update('attendances', $upd);
        echo json_encode(array('success'=>true,'message'=>'Jam absensi diperbarui.'));
    }

        /* ===== ADMIN: EXPORT BULANAN (XLSX rapi) ===== */
    public function export_bulanan()
    {
        if (!$this->_boleh_rekap()) { redirect(base_url('attendance/me')); return; }

        require_once FCPATH . 'vendor/autoload.php';

        // Dua cara memilih periode. Kalau 'dari' dan 'sampai' dikirim,
        // rekapnya mengikuti rentang tanggal bebas itu — satu hari pun
        // boleh. Kalau tidak, jatuh ke cara lama per bulan, supaya
        // tautan lama yang sudah dipakai HRD tidak rusak.
        $dari   = $this->input->get('dari');
        $sampai = $this->input->get('sampai');
        $tgl_ok = '/^\d{4}-\d{2}-\d{2}$/';

        if (preg_match($tgl_ok, (string)$dari) && preg_match($tgl_ok, (string)$sampai)) {
            $awal  = $dari;
            $akhir = $sampai;
            if (strtotime($awal) > strtotime($akhir)) {
                $tukar = $awal; $awal = $akhir; $akhir = $tukar;
            }
            $bulan = substr($awal, 0, 7);
            $label_periode = ($awal === $akhir)
                ? date('j F Y', strtotime($awal))
                : date('j M', strtotime($awal)) . ' - ' . date('j M Y', strtotime($akhir));
            $label_file = ($awal === $akhir) ? $awal : ($awal . '_sd_' . $akhir);
        } else {
            $bulan = $this->input->get('bulan');
            if (!preg_match('/^\d{4}-\d{2}$/', (string)$bulan)) { $bulan = date('Y-m'); }
            $awal  = $bulan . '-01';
            $akhir = date('Y-m-t', strtotime($awal));
            $label_periode = NULL;
            $label_file    = $bulan;
        }

        $rows = $this->mymodel->selectWithQuery("
            SELECT a.user_id, a.attendance_date, a.check_in_at, a.break_out_at, a.break_in_at, a.check_out_at,
                   u.id AS uid, u.full_name, p.department AS divisi, p.name AS jabatan,
                   j.jam_masuk AS jadwal_masuk, j.toleransi_menit, j.jam_pulang AS jadwal_pulang,
                   j.jam_pulang_sabtu, j.pakai_istirahat,
                   lt.name AS izin_jenis, lr.jam_mulai AS izin_mulai, lr.jam_selesai AS izin_selesai
            FROM attendances a
            LEFT JOIN user u ON u.id = a.user_id
            LEFT JOIN user_profile up ON up.user_id = u.id
            LEFT JOIN positions p ON p.id = up.position_id
            LEFT JOIN hrd_jadwal j ON j.user_id = a.user_id AND j.aktif = 1
            LEFT JOIN leave_requests lr ON lr.user_id = a.user_id
                 AND lr.status = 'approved'
                 AND a.attendance_date BETWEEN lr.start_date AND lr.end_date
            LEFT JOIN leave_types lt ON lt.id = lr.leave_type_id
            WHERE a.attendance_date BETWEEN '$awal' AND '$akhir'
            ORDER BY u.full_name, a.attendance_date");

        $nama_bulan = $label_periode ?: date('F Y', strtotime($awal));

        // === ambang waktu ===
        $B_MASUK   = '08:00';
        $B_KEMBALI = '13:00';
        $B_PULANG  = '17:00';

        $HIJAU  = 'D1FAE5';
        $KUNING = 'FEF3C7';
        $MERAH  = 'FEE2E2';
        $ABU    = 'F8FAFC';

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        /* ================= SHEET 1: DETAIL ================= */
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Detail Kehadiran');

        $sh->setCellValue('A1', 'SESI KEHADIRAN PT MONTERA GROUP');
        $sh->mergeCells('A1:K1');
        $sh->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sh->getStyle('A1')->getAlignment()->setHorizontal('center');

        $sh->setCellValue('A2', 'Periode: ' . $nama_bulan);
        $sh->mergeCells('A2:K2');
        $sh->getStyle('A2')->getAlignment()->setHorizontal('center');
        $sh->getStyle('A2')->getFont()->setItalic(true);

        $head = array('No','Nama Karyawan','Divisi','Tanggal','Jam Masuk','Status Masuk',
                      'Istirahat','Masuk Kembali','Status After Break','Jam Pulang','Status Pulang');
        $sh->fromArray($head, null, 'A4');
        $sh->getStyle('A4:K4')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sh->getStyle('A4:K4')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF1F2937');
        $sh->getStyle('A4:K4')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sh->getRowDimension(4)->setRowHeight(24);

        // shift_live_export
        // Shift host live ditentukan harian di live_shift_harian, bukan di
        // hrd_jadwal yang hanya menyimpan patokan dasar 08:00. Tanpa peta
        // ini host yang kebagian sesi sore tercatat terlambat tujuh jam
        // setiap hari, baik di kolom Status Masuk maupun di sheet Rekap.
        $shift_live = array();
        foreach ($this->mymodel->selectWithQuery(
            "SELECT tanggal, user_id, shift FROM live_shift_harian
             WHERE tanggal BETWEEN '$awal' AND '$akhir'") as $sl) {
            $shift_live[(int)$sl['user_id']][$sl['tanggal']] = (int)$sl['shift'];
        }

        $baris = 5;
        $no = 1;
        $no_dalam = 1;
        $nama_kini = NULL;
        $skor = array();

        foreach ($rows as $r) {
            $jm = !empty($r['check_in_at'])  ? date('H:i', strtotime($r['check_in_at']))  : '';
            $jb = !empty($r['break_out_at']) ? date('H:i', strtotime($r['break_out_at'])) : '';
            $jk = !empty($r['break_in_at'])  ? date('H:i', strtotime($r['break_in_at']))  : '';
            $jp = !empty($r['check_out_at']) ? date('H:i', strtotime($r['check_out_at'])) : '';

            // Ambang diambil dari jadwal orang itu pada tanggal itu, bukan
            // angka tetap. Tanpa ini host live yang masuk 14:00 tercatat
            // terlambat setiap hari, dan semua orang tercatat pulang cepat
            // setiap Sabtu.
            $sabtu = (date('N', strtotime($r['attendance_date'])) == 6);

            $jam_masuk = $r['jadwal_masuk'] ?: '08:00:00';
            $tol       = ($r['toleransi_menit'] === NULL) ? 5 : (int)$r['toleransi_menit'];

            $jam_pulang = $sabtu
                ? ($r['jam_pulang_sabtu'] ?: '12:00:00')
                : ($r['jadwal_pulang'] ?: '17:00:00');

            // Host live: jam masuk dan pulang mengikuti shift hari itu.
            $uid_r = (int)($r['user_id'] ?? 0);
            $sh_r  = $shift_live[$uid_r][$r['attendance_date']] ?? NULL;
            if ($sh_r !== NULL) {
                $jam_masuk  = ($sh_r === 2) ? '15:00:00' : '08:00:00';
                $jam_pulang = ($sh_r === 2) ? '22:00:00' : '15:00:00';
            }

            $b_masuk = date('H:i', strtotime($jam_masuk) + $tol * 60);

            // Izin setengah hari menggeser jam yang dibandingkan.
            if (!empty($r['izin_mulai']) && !empty($r['izin_selesai'])) {
                if ($r['izin_mulai'] <= $jam_masuk) {
                    $b_masuk = substr($r['izin_selesai'], 0, 5);
                } elseif ($r['izin_selesai'] >= $jam_pulang) {
                    $jam_pulang = $r['izin_mulai'];
                }
            }
            $b_pulang = substr($jam_pulang, 0, 5);

            $izin_penuh = !empty($r['izin_jenis'])
                       && empty($r['izin_mulai']);

            if ($jm === '' && $izin_penuh) {
                $st_masuk = 'Izin - ' . $r['izin_jenis'];
                $w_masuk  = $ABU;
            }
            elseif ($jm === '') { $st_masuk = 'Alfa'; $w_masuk = $MERAH; }
            elseif ($jm <= $b_masuk) { $st_masuk = 'Hadir'; $w_masuk = $HIJAU; }
            else { $st_masuk = 'Terlambat'; $w_masuk = $KUNING; }

            if (!empty($r['izin_jenis']) && !empty($r['izin_mulai'])) {
                $st_masuk .= ' (izin ' . substr($r['izin_mulai'], 0, 5)
                          . '-' . substr($r['izin_selesai'], 0, 5) . ')';
            }

            if ($jk === '' && isset($r['pakai_istirahat']) && (int)$r['pakai_istirahat'] === 0) {
                $st_break = 'Tanpa istirahat'; $w_break = '';
            }
            elseif ($jk === '') { $st_break = '-'; $w_break = ''; }
            else {
                // Istirahat dan masuk kembali dicatat sistem otomatis pada
                // 12:00 dan 13:00, sama untuk semua orang. Tidak ada yang
                // menekan tombolnya, jadi tidak ada yang bisa terlambat.
                $st_break = 'After Take Break'; $w_break = $HIJAU;
            }

            if ($jp === '') { $st_pulang = '-'; $w_pulang = ''; }
            elseif ($jp >= $b_pulang) { $st_pulang = 'Back to Home'; $w_pulang = $HIJAU; }
            else { $st_pulang = 'Pulang Cepat'; $w_pulang = $KUNING; }

            // Baris dikelompokkan per karyawan: nama ditulis sekali
            // sebagai judul, lalu tanggal-tanggalnya di bawahnya. Tanpa ini
            // 27 karyawan dikali sehari sebulan menjadi ratusan baris rata
            // yang sulit ditelusuri.
            if ($nama_kini !== $r['full_name']) {
                if ($nama_kini !== NULL) { $baris++; }   // pemisah antar orang
                $sh->setCellValue('A' . $baris, $r['full_name']
                    . '  -  ' . ($r['divisi'] ?: ($r['jabatan'] ?: '-')));
                $sh->mergeCells('A' . $baris . ':K' . $baris);
                $sh->getStyle('A' . $baris)->getFont()->setBold(true)->setSize(11);
                $sh->getStyle('A' . $baris)->getFill()->setFillType('solid')
                   ->getStartColor()->setARGB('FFE8EEF7');
                $sh->getStyle('A' . $baris)->getAlignment()->setHorizontal('left');
                $baris++;
                $nama_kini = $r['full_name'];
                $no_dalam  = 1;
            }

            $sh->fromArray(array(
                $no_dalam,
                $r['full_name'],
                $r['divisi'] ?: ($r['jabatan'] ?: '-'),
                date('d/m/Y', strtotime($r['attendance_date'])),
                $jm !== '' ? $jm : '-',
                $st_masuk,
                $jb !== '' ? 'Take Break (' . $jb . ')' : '-',
                $jk !== '' ? $jk : '-',
                $st_break,
                $jp !== '' ? $jp : '-',
                $st_pulang,
            ), null, 'A' . $baris);

            if ($no % 2 == 0) {
                $sh->getStyle('A'.$baris.':K'.$baris)->getFill()
                   ->setFillType('solid')->getStartColor()->setARGB('FF'.$ABU);
            }
            if ($w_masuk !== '')  { $sh->getStyle('F'.$baris)->getFill()->setFillType('solid')->getStartColor()->setARGB('FF'.$w_masuk); }
            if ($w_break !== '')  { $sh->getStyle('I'.$baris)->getFill()->setFillType('solid')->getStartColor()->setARGB('FF'.$w_break); }
            if ($w_pulang !== '') { $sh->getStyle('K'.$baris)->getFill()->setFillType('solid')->getStartColor()->setARGB('FF'.$w_pulang); }

            $nm = $r['full_name'];
            if (!isset($skor[$nm])) { $skor[$nm] = array('divisi'=>$r['divisi'] ?: '-', 'hari'=>0, 'tepat'=>0, 'telat'=>0, 'alfa'=>0, 'izin'=>0); }
            $skor[$nm]['hari']++;
            // Ambang yang dipakai harus sama dengan yang dipakai sheet
            // Detail di atas ($b_masuk, dari jadwal orangnya). Sebelumnya
            // di sini memakai $B_MASUK yang tetap 08:00, sehingga orang
            // yang jadwalnya bukan 08:00 tercatat Hadir di Detail tapi
            // Telat di Rekap.
            if ($jm === '' && $izin_penuh) { $skor[$nm]['izin']++; }
            elseif ($jm === '')            { $skor[$nm]['alfa']++; }
            elseif ($jm > $b_masuk)        { $skor[$nm]['telat']++; }
            else                           { $skor[$nm]['tepat']++; }

            $baris++; $no++; $no_dalam++;
        }

        $akhirBaris = max(5, $baris - 1);
        $sh->getStyle('A4:K'.$akhirBaris)->getBorders()->getAllBorders()
           ->setBorderStyle('thin')->getColor()->setARGB('FFCBD5E1');
        $sh->getStyle('A5:A'.$akhirBaris)->getAlignment()->setHorizontal('center');
        $sh->getStyle('D5:K'.$akhirBaris)->getAlignment()->setHorizontal('center');
        foreach (range('A','K') as $c) { $sh->getColumnDimension($c)->setAutoSize(true); }
        $sh->freezePane('A5');

        /* ================= SHEET 2: REKAP ================= */
        $s2 = $ss->createSheet();
        $s2->setTitle('Rekap Skor');

        $s2->setCellValue('A1', 'REKAP SKOR PER KARYAWAN - ' . strtoupper($nama_bulan));
        $s2->mergeCells('A1:G1');
        $s2->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $s2->getStyle('A1')->getAlignment()->setHorizontal('center');

        $h2 = array('No','Nama Karyawan','Divisi','Total Hari','Tepat Waktu','Telat','Alfa','Izin');
        $s2->fromArray($h2, null, 'A3');
        $s2->setCellValue('I3', 'Skor (0-100)');
        $s2->getStyle('A3:I3')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $s2->getStyle('A3:I3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF1F2937');
        $s2->getStyle('A3:I3')->getAlignment()->setHorizontal('center')->setVertical('center');
        $s2->getRowDimension(3)->setRowHeight(24);

        $b2 = 4; $n2 = 1;
        foreach ($skor as $nm => $v) {
            // Hari yang dipakai membagi tidak termasuk hari izin: orang
            // yang izin resmi tidak seharusnya kehilangan poin karenanya.
            $hari_nilai = $v['hari'] - $v['izin'];
            $nilai = $hari_nilai > 0
                ? round((($v['tepat'] * 100) + ($v['telat'] * 60)) / $hari_nilai)
                : 0;
            $s2->fromArray(array($n2, $nm, $v['divisi'], $v['hari'], $v['tepat'],
                                 $v['telat'], $v['alfa'], $v['izin'], $nilai), null, 'A'.$b2);
            $warna = $nilai >= 85 ? $HIJAU : ($nilai >= 60 ? $KUNING : $MERAH);
            $s2->getStyle('I'.$b2)->getFill()->setFillType('solid')->getStartColor()->setARGB('FF'.$warna);
            $s2->getStyle('I'.$b2)->getFont()->setBold(true);
            $b2++; $n2++;
        }
        $akhir2 = max(4, $b2 - 1);
        $s2->getStyle('A3:H'.$akhir2)->getBorders()->getAllBorders()
           ->setBorderStyle('thin')->getColor()->setARGB('FFCBD5E1');
        $s2->getStyle('A4:A'.$akhir2)->getAlignment()->setHorizontal('center');
        $s2->getStyle('D4:H'.$akhir2)->getAlignment()->setHorizontal('center');
        foreach (range('B','I') as $c) { $s2->getColumnDimension($c)->setAutoSize(true); }
        // Kolom No disetel manual. Kalau ikut autoSize, dia melebar
        // mengikuti teks keterangan panjang yang ditulis di baris bawah.
        $s2->getColumnDimension('A')->setAutoSize(false);
        $s2->getColumnDimension('A')->setWidth(6);
        $s2->freezePane('A4');

        $s2->setCellValue('A'.($akhir2 + 2), 'Cara hitung skor: tiap hari kerja dinilai — tepat waktu 100, telat 60, alfa 0. Hari izin resmi tidak ikut dihitung. Nilai akhir adalah rata-ratanya. Contoh: 8 hari tepat + 2 hari telat = (800+120)/10 = 92.');
        $s2->getStyle('A'.($akhir2 + 2))->getFont()->setItalic(true)->setSize(10);

        /* ================= SHEET 3: MATRIKS TANGGAL =================
           Satu baris per orang, tanggal memanjang ke samping, supaya
           terlihat sekali pandang siapa bolong di tanggal berapa.

           Penulisan selnya memakai koordinat huruf, bukan
           setCellValueByColumnAndRow(); fungsi itu sudah dihapus
           PhpSpreadsheet sejak versi 2. */
        $s3 = $ss->createSheet();
        $s3->setTitle('Matriks Tanggal');

        $HURUF = function ($i) {
            return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
        };

        // Hari libur dan hari kerja tiap orang tidak ada di $rows.
        // Tanpa ini yang liburnya Sabtu terbaca alfa setiap Sabtu.
        $libur_m = array();
        foreach ($this->mymodel->selectWithQuery(
            "SELECT tanggal FROM hrd_hari_libur") as $l) {
            $libur_m[$l['tanggal']] = TRUE;
        }

        $jadwal_m = array();
        foreach ($this->mymodel->selectWithQuery(
            "SELECT u.full_name, j.hari_kerja
             FROM user u LEFT JOIN hrd_jadwal j ON j.user_id=u.id AND j.aktif=1
             WHERE u.status IN ('active','1','aktif','Aktif')") as $j) {
            $jadwal_m[$j['full_name']] = $j['hari_kerja'] ?: '1,2,3,4,5';
        }

        $peta = array();
        $divisi_m = array();
        foreach ($rows as $r) {
            $nm = isset($r['full_name']) ? $r['full_name'] : '-';
            $td = $r['attendance_date'];

            // Kehadiran menang atas izin: sejak izin tidak lagi menutup
            // tombol absen, orang yang izin beberapa hari bisa masuk lebih
            // awal dan mengabsen. Kalau izin diperiksa lebih dulu, hari itu
            // tercatat 'I' padahal orangnya benar-benar bekerja.
            if (!empty($r['check_in_at'])) {
                $k = 'H';
                $uid_m = (int)(isset($r['user_id']) ? $r['user_id'] : 0);
                $sh_m  = isset($shift_live[$uid_m][$td]) ? $shift_live[$uid_m][$td] : NULL;
                $jm_m  = ($sh_m !== NULL)
                    ? (($sh_m === 2) ? '15:00:00' : '08:00:00')
                    : ($r['jadwal_masuk'] ?: '08:00:00');
                $tol_m = ($r['toleransi_menit'] === NULL) ? 5 : (int)$r['toleransi_menit'];
                $bm_m  = date('H:i', strtotime($jm_m) + $tol_m * 60);
                if (date('H:i', strtotime($r['check_in_at'])) > $bm_m) { $k = 'T'; }
            }
            elseif (!empty($r['izin_mulai']) || !empty($r['on_leave'])) { $k = 'I'; }
            else                                                       { $k = 'A'; }

            $peta[$nm][$td] = $k;
            if (!isset($divisi_m[$nm])) {
                $divisi_m[$nm] = isset($r['position_name']) ? $r['position_name'] : '-';
            }
        }

        $tgl_m = array();
        $dd = strtotime($awal);
        $dz = strtotime($akhir);
        while ($dd <= $dz) {
            $td = date('Y-m-d', $dd);
            if (!isset($libur_m[$td])) { $tgl_m[] = $td; }
            $dd = strtotime('+1 day', $dd);
        }

        $s3->setCellValue('A1', 'MATRIKS KEHADIRAN - ' . strtoupper($nama_bulan));
        $s3->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $s3->setCellValue('A2', 'H = Hadir    T = Terlambat    I = Izin    A = Alfa    - = bukan hari kerjanya');
        $s3->getStyle('A2')->getFont()->setItalic(true)->setSize(10);

        $s3->setCellValue('A4', 'Nama Karyawan');
        $s3->setCellValue('B4', 'Divisi');

        $kol = 3;
        foreach ($tgl_m as $td) {
            $h = $HURUF($kol);
            $s3->setCellValue($h . '4', date('d/m', strtotime($td)));
            $s3->getColumnDimension($h)->setWidth(5.5);
            $kol++;
        }
        foreach (array('H','T','I','A') as $lbl) {
            $h = $HURUF($kol);
            $s3->setCellValue($h . '4', $lbl);
            $s3->getColumnDimension($h)->setWidth(5);
            $kol++;
        }
        $h_akhir = $HURUF($kol - 1);

        $s3->getStyle('A4:' . $h_akhir . '4')->getFont()->setBold(true)
           ->getColor()->setARGB('FFFFFFFF');
        $s3->getStyle('A4:' . $h_akhir . '4')->getFill()->setFillType('solid')
           ->getStartColor()->setARGB('FF1F2937');
        $s3->getStyle('A4:' . $h_akhir . '4')->getAlignment()
           ->setHorizontal('center')->setVertical('center');
        $s3->getRowDimension(4)->setRowHeight(22);

        $WARNA_M = array('H' => $HIJAU, 'T' => $KUNING, 'A' => $MERAH, 'I' => 'BFDBFE');

        $b3 = 5;
        ksort($peta);
        foreach ($peta as $nm => $isi) {
            $s3->setCellValue('A' . $b3, $nm);
            $s3->setCellValue('B' . $b3, isset($divisi_m[$nm]) ? $divisi_m[$nm] : '-');

            $hk_m = array_map('trim', explode(',',
                isset($jadwal_m[$nm]) ? $jadwal_m[$nm] : '1,2,3,4,5'));
            $jml = array('H' => 0, 'T' => 0, 'I' => 0, 'A' => 0);
            $k = 3;

            foreach ($tgl_m as $td) {
                $h  = $HURUF($k);
                $hn = (string)(int)date('N', strtotime($td));
                if (!in_array($hn, $hk_m, TRUE)) {
                    $s3->setCellValue($h . $b3, '-');
                    $k++;
                    continue;
                }
                $v = isset($isi[$td]) ? $isi[$td] : 'A';
                $jml[$v]++;
                $s3->setCellValue($h . $b3, $v);
                $s3->getStyle($h . $b3)->getFill()->setFillType('solid')
                   ->getStartColor()->setARGB('FF' . $WARNA_M[$v]);
                $k++;
            }

            foreach (array('H','T','I','A') as $lbl) {
                $s3->setCellValue($HURUF($k) . $b3, $jml[$lbl]);
                $k++;
            }
            $s3->getStyle('A' . $b3 . ':B' . $b3)->getFont()->setBold(true);
            $b3++;
        }

        $akhir3 = max(5, $b3 - 1);
        $s3->getStyle('A4:' . $h_akhir . $akhir3)->getBorders()->getAllBorders()
           ->setBorderStyle('thin')->getColor()->setARGB('FFCBD5E1');
        $s3->getStyle('C5:' . $h_akhir . $akhir3)->getAlignment()
           ->setHorizontal('center')->setVertical('center');
        $s3->getColumnDimension('A')->setWidth(26);
        $s3->getColumnDimension('B')->setWidth(20);
        $s3->freezePane('C5');

        $ss->setActiveSheetIndex(0);

        $file = 'Kehadiran_Montera_' . $label_file . '.xlsx';
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $writer->save('php://output');
        exit;
    }


    public function delete_attendance()
    {
        header('Content-Type: application/json');
        if (!$this->_boleh_rekap()) { echo json_encode(array('success'=>false,'message'=>'Ditolak.')); return; }
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT id FROM attendances WHERE id=$id LIMIT 1");
        if (empty($r)) { echo json_encode(array('success'=>false,'message'=>'Data tidak ditemukan.')); return; }
        $fotos = $this->mymodel->selectWithQuery("SELECT photo_path FROM attendance_punches WHERE attendance_id=$id AND photo_path IS NOT NULL");
        foreach ($fotos as $f) { $p = FCPATH . $f['photo_path']; if (is_file($p)) @unlink($p); }
        $this->db->query("DELETE FROM attendance_punches WHERE attendance_id=$id");
        $this->db->query("DELETE FROM attendances WHERE id=$id");
        echo json_encode(array('success'=>true,'message'=>'Data absensi dihapus.'));
    }

    /* ===== ADMIN: HAPUS PERANGKAT ABSENSI ===== */
    public function delete_device()
    {
        header('Content-Type: application/json');
        if (!$this->_boleh_rekap()) { echo json_encode(array('success'=>false,'message'=>'Ditolak.')); return; }
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT id, user_id FROM user_devices WHERE id=$id LIMIT 1");
        if (empty($r)) { echo json_encode(array('success'=>false,'message'=>'Perangkat tidak ditemukan.')); return; }
        $this->db->query("DELETE FROM user_devices WHERE id=$id");
        echo json_encode(array('success'=>true,'message'=>'Perangkat dihapus. User akan terdaftar ulang saat membuka absensi lagi.'));
    }
}
