<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/BaseController.php';

class Leave extends BaseController
{
    protected $public_methods = ['request', 'store', 'update_request', 'delete_request', 'events', 'email_action'];

    public function __construct()
    {
        parent::__construct();
        // Akun yang belum disetujui tidak boleh absen, daftar wajah, atau
        // mengajukan izin -- halaman-halaman ini terbuka untuk semua user
        // yang login, jadi penahannya harus di sini, bukan di pengaturan role.
        $uid_cek = $_SESSION['user']['id'] ?? 0;
        if ($uid_cek) {
            $u_cek = $this->db->select('disetujui')->where('id', $uid_cek)->get('user')->row_array();
            if (!$u_cek) {
                // Akunnya sudah dihapus tapi sesi login di browser masih hidup
                // (sesi berlaku setahun). Putus sesinya, jangan biarkan absen.
                if (isset($this->session)) { $this->session->sess_destroy(); } else { session_destroy(); }
                if ($this->input->is_ajax_request() || $this->input->method() === 'post') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'status' => false,
                                      'message' => 'Akun tidak ditemukan. Silakan login ulang.',
                                      'msg' => 'Akun tidak ditemukan. Silakan login ulang.']);
                    exit;
                }
                redirect('auth/login');
                exit;
            }
            if ((int) $u_cek['disetujui'] === 0) {
                $pesan = 'Akun kamu sedang menunggu persetujuan admin. Absen dan izin bisa dipakai setelah akunmu disetujui.';
                if ($this->input->is_ajax_request() || $this->input->method() === 'post') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'status' => false, 'message' => $pesan, 'msg' => $pesan]);
                    exit;
                }
                $d = ['user' => $_SESSION['user'], 'title' => 'Menunggu Persetujuan'];
                $d['content'] = '<div style="max-width:520px;margin:60px auto;background:#fff;border-radius:14px;padding:32px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.08)">'
                              . '<div style="font-size:42px">&#9203;</div>'
                              . '<h4 style="margin:12px 0 8px;font-weight:700">Menunggu persetujuan</h4>'
                              . '<p style="color:#64748b;line-height:1.6;margin:0">' . $pesan . '</p></div>' . "<script>setInterval(function(){fetch('/auth/status_akun',{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(o){if(o.status==='disetujui'){location.reload();}else if(o.status==='ditolak'){location.href='/auth/login?akun=ditolak';}else if(o.status==='dihapus'||o.status==='keluar'){location.href='/auth/login';}}).catch(function(){});},15000);</script>";
                echo $this->load->view('TemplateDashboard', $d, true);
                exit;
            }
        }

        $this->load->database();
        $this->load->model('mymodel');
        $this->load->model('Leave_model', 'leave_model');
        $this->load->library('app_mailer');
        $this->load->library('template');
        $this->load->library('upload');
        $this->load->library('session');

        // Public methods handled via class property
    }

    public function index()
    {
        $data['user'] = $_SESSION['user'];

        if (!$this->can_manage_leave($data['user'])) {
            redirect(base_url() . 'dashboard');
        }

        $start_date = $this->input->get('start_date') ?? '';
        $end_date = $this->input->get('end_date') ?? '';
        $until_date = $this->input->get('until_date') ?? '';
        $name = trim((string) ($this->input->get('name') ?? ''));
        $leave_type_id = trim((string) ($this->input->get('leave_type_id') ?? ''));
        if (!empty($until_date)) {
            $end_date = $until_date;
        }

        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['until_date'] = $end_date;
        $data['name'] = $name;
        $data['leave_type_id'] = $leave_type_id;

        $qry = "1=1";
        if (!empty($start_date)) {
            $qry .= " AND lr.start_date >= " . $this->db->escape($start_date);
        }
        if (!empty($end_date)) {
            $qry .= " AND lr.end_date <= " . $this->db->escape($end_date);
        }
        if ($name !== '') {
            $name_like = $this->db->escape('%' . $this->db->escape_like_str($name) . '%');
            $qry .= " AND u.full_name LIKE {$name_like} ESCAPE '!'";
        }
        if ($leave_type_id !== '' && ctype_digit($leave_type_id)) {
            $qry .= " AND lr.leave_type_id = " . intval($leave_type_id);
        }

        $count_query = $this->mymodel->selectWithQuery("SELECT COUNT(lr.id) AS count
            FROM leave_requests lr
            LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
            LEFT JOIN user u ON lr.user_id = u.id
            WHERE $qry");
        $total = !empty($count_query) ? intval($count_query[0]['count']) : 0;

        $limit = 10;
        $current_page = intval($this->input->get('page') ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }
        $offset = ($current_page - 1) * $limit;

        $data['page'] = CEIL($total / $limit);
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['title'] = 'Leave Management - ' . $this->template->title();
        $data['leave_types'] = $this->leave_model->get_leave_types();
        $data['requests'] = $this->mymodel->selectWithQuery("SELECT lr.*, lt.name as leave_type_name, u.full_name as user_name
            FROM leave_requests lr
            LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
            LEFT JOIN user u ON lr.user_id = u.id
            WHERE $qry
            ORDER BY lr.id DESC
            LIMIT $offset, $limit");
        $data['events'] = $this->leave_model->list_events();
        $data['leave_counts'] = $this->leave_model->get_leave_counts_by_date();
        $data['work_calendar_summary'] = $this->leave_model->get_work_calendar_summary();
        $request_ids = array_map(function ($row) { return $row['id']; }, $data['requests']);
        $data['approvals_map'] = $this->leave_model->get_approvals_for_requests($request_ids);
        if (isset($data['user']['role']) && $data['user']['role'] == '1') {
            $data['pending_leader'] = $this->leave_model->list_pending_for_leader();
            $data['pending_hr'] = $this->leave_model->list_pending_for_hr();
        } else {
            $data['pending_leader'] = $this->leave_model->list_pending_for_leader($data['user']['id']);
            $data['pending_hr'] = $this->leave_model->list_pending_for_hr($data['user']['id']);
        }

        $data['content'] = $this->load->view('leave/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function request()
    {
        $data['user'] = $_SESSION['user'];
        $profile = $this->leave_model->get_user_profile($data['user']['id']);

        $data['title'] = 'Ajukan Pengajuan - ' . $this->template->title();
        $data['leave_types'] = $this->leave_model->get_leave_types();
        $data['balance'] = $this->leave_model->get_leave_balance($data['user']['id']);
        $data['is_leave_balance_unset'] = $this->is_leave_balance_unset($profile);
        $data['requests'] = $this->leave_model->list_user_requests($data['user']['id']);
        $data['events'] = $this->leave_model->list_events();

        $data['content'] = $this->load->view('leave/request', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function approvals()
    {
        redirect(base_url() . 'leave');
    }

    public function approve()
    {
        $user = $_SESSION['user'];
        $request_id = $this->input->get('id');
        $note = $this->input->post('note');

        if (empty($request_id)) {
            $this->session->set_flashdata('error', 'ID pengajuan tidak valid.');
            redirect(base_url() . 'leave/approvals');
        }

        $request = $this->leave_model->get_request($request_id);
        if (empty($request)) {
            $this->session->set_flashdata('error', 'Data pengajuan tidak ditemukan.');
            redirect(base_url() . 'leave/approvals');
        }
        if (in_array($request['status'], ['approved', 'rejected'])) {
            $this->session->set_flashdata('error', 'Status pengajuan sudah final.');
            redirect(base_url() . 'leave/approvals');
        }

        $approver_role = $this->get_approver_role($user, $request);
        if (!$approver_role) {
            $this->session->set_flashdata('error', 'Anda tidak berhak menyetujui pengajuan ini.');
            redirect(base_url() . 'leave/approvals');
        }

        $result = $this->process_leave_approval_action($request, $approver_role, 'approved', $note);
        if (!$result['success']) {
            $this->session->set_flashdata('error', 'Gagal menyetujui pengajuan.');
        } else {
            $this->session->set_flashdata('success', 'Pengajuan berhasil disetujui.');
        }

        redirect(base_url() . 'leave/approvals');
    }

    public function reject()
    {
        $user = $_SESSION['user'];
        $request_id = $this->input->get('id');
        $note = $this->input->post('note');

        if (empty($request_id)) {
            $this->session->set_flashdata('error', 'ID pengajuan tidak valid.');
            redirect(base_url() . 'leave/approvals');
        }

        $request = $this->leave_model->get_request($request_id);
        if (empty($request)) {
            $this->session->set_flashdata('error', 'Data pengajuan tidak ditemukan.');
            redirect(base_url() . 'leave/approvals');
        }
        if (in_array($request['status'], ['approved', 'rejected'])) {
            $this->session->set_flashdata('error', 'Status pengajuan sudah final.');
            redirect(base_url() . 'leave/approvals');
        }

        $approver_role = $this->get_approver_role($user, $request);
        if (!$approver_role) {
            $this->session->set_flashdata('error', 'Anda tidak berhak menolak pengajuan ini.');
            redirect(base_url() . 'leave/approvals');
        }

        $result = $this->process_leave_approval_action($request, $approver_role, 'rejected', $note);
        if (!$result['success']) {
            $this->session->set_flashdata('error', 'Gagal menolak pengajuan.');
        } else {
            $this->session->set_flashdata('success', 'Pengajuan berhasil ditolak.');
        }

        redirect(base_url() . 'leave/approvals');
    }

    public function email_action()
    {
        $token = trim((string) $this->input->get('token', true));
        if ($token === '') {
            $this->render_email_action_result('Link approval tidak valid.', false);
            return;
        }

        if (!$this->db->table_exists('leave_email_approval_tokens')) {
            $this->render_email_action_result('Tabel token approval email belum tersedia.', false);
            return;
        }

        $token_row = $this->leave_model->get_email_approval_token($token);
        if (empty($token_row)) {
            $this->render_email_action_result('Token approval tidak ditemukan.', false);
            return;
        }

        if (($token_row['status'] ?? '') !== 'active') {
            $this->render_email_action_result('Token approval ini sudah tidak aktif.', false);
            return;
        }

        $expires_at = trim((string) ($token_row['expires_at'] ?? ''));
        if ($expires_at !== '' && strtotime($expires_at) !== false && strtotime($expires_at) < time()) {
            $this->leave_model->update_email_approval_token(intval($token_row['id']), [
                'status' => 'expired',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $this->render_email_action_result('Token approval sudah kedaluwarsa.', false);
            return;
        }

        $request = $this->leave_model->get_request(intval($token_row['leave_request_id'] ?? 0));
        if (empty($request)) {
            $this->render_email_action_result('Data pengajuan tidak ditemukan.', false);
            return;
        }

        if (!$this->is_email_token_still_authorized($request, $token_row)) {
            $this->render_email_action_result('Token ini sudah tidak sesuai dengan approver aktif untuk pengajuan tersebut.', false);
            return;
        }

        if (!$this->is_request_waiting_for_role($request, (string) ($token_row['approver_role'] ?? ''))) {
            $this->render_email_action_result('Pengajuan ini sudah tidak menunggu aksi dari approver tersebut.', false);
            return;
        }

        $action = (string) ($token_row['action'] ?? '');
        if (!in_array($action, ['approved', 'rejected'], true)) {
            $this->render_email_action_result('Aksi token tidak valid.', false);
            return;
        }

        $result = $this->process_leave_approval_action($request, (string) $token_row['approver_role'], $action, null, $token_row);
        $this->render_email_action_result($result['message'], $result['success']);
    }

    public function store()
    {
        $user = $_SESSION['user'];

        $leave_type_id = $this->input->post('leave_type_id');
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $reason = $this->input->post('reason');

        // Izin setengah hari: jam diisi kalau izinnya hanya sebagian
        // hari. Dikosongkan berarti izin sehari penuh, seperti biasa.
        $jam_mulai   = $this->input->post('jam_mulai') ?: NULL;
        $jam_selesai = $this->input->post('jam_selesai') ?: NULL;
        if ($jam_mulai && $jam_selesai && $jam_selesai <= $jam_mulai) {
            $this->session->set_flashdata('error', 'Jam selesai harus lebih akhir dari jam mulai.');
            redirect(base_url() . 'leave/request');
        }

        if (empty($leave_type_id) || empty($start_date) || empty($end_date)) {
            $this->session->set_flashdata('error', 'Mohon lengkapi form pengajuan.');
            redirect(base_url() . 'leave/request');
        }

        if (strtotime($end_date) < strtotime($start_date)) {
            $this->session->set_flashdata('error', 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.');
            redirect(base_url() . 'leave/request');
        }

        $leave_type = $this->mymodel->selectWithQuery("SELECT * FROM leave_types WHERE id = '$leave_type_id' LIMIT 1");
        if (empty($leave_type)) {
            $this->session->set_flashdata('error', 'Tipe pengajuan tidak ditemukan.');
            redirect(base_url() . 'leave/request');
        }
        $leave_type = $leave_type[0];

        $profile = $this->leave_model->get_user_profile($user['id']);
        if (empty($profile)) {
            $this->session->set_flashdata('error', 'Profil belum lengkap. Mohon lengkapi profil terlebih dahulu.');
            redirect(base_url() . 'profile');
        }
        if ($this->leave_model->is_deducting_leave_type($leave_type_id) && $this->is_leave_balance_unset($profile)) {
            $this->session->set_flashdata('error', 'Sisa cuti belum diatur. Hanya pengajuan Cuti Tahunan yang diblokir sampai HR memperbarui join date dan leave balance.');
            redirect(base_url() . 'leave/request');
        }

        // Tolak pengajuan yang sama persis dalam semenit terakhir.
        // Tombol kirim yang ditekan berulang menghasilkan beberapa
        // pengajuan sekaligus, dan tiap pengajuan mengirim pesan
        // WhatsApp sendiri. Pengunci di browser bisa dilewati kalau
        // halamannya dimuat ulang, jadi pemeriksaan ini yang menjamin.
        $kembar = $this->db->where('user_id', $user['id'])
                           ->where('leave_type_id', $leave_type_id)
                           ->where('start_date', $start_date)
                           ->where('submitted_at >=', date('Y-m-d H:i:s', time() - 60))
                           ->count_all_results('leave_requests');
        if ($kembar > 0) {
            $this->session->set_flashdata('error', 'Pengajuan serupa baru saja terkirim. Periksa riwayat di bawah.');
            redirect(base_url() . 'leave/request');
        }

        $total_days = $this->calculate_total_days($start_date, $end_date);

        $attachment_path = null;
        if (!empty($_FILES['attachment']['name'])) {
            $dir = FCPATH . "uploads/leave/";
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $config['upload_path'] = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png|pdf';
            $config['overwrite'] = true;
            $config['file_name'] = 'leave_' . $user['id'] . '_' . date('YmdHis');
            $config['max_size'] = 4096;

            $this->upload->initialize($config);
            if (!$this->upload->do_upload('attachment')) {
                $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
                redirect(base_url() . 'leave/request');
            }
            $file = $this->upload->data();
            $attachment_path = 'uploads/leave/' . $file['file_name'];
        }

        // Berkas kedua, dipakai untuk struk obat pada pengajuan sakit.
        $attachment2_path = null;
        if (!empty($_FILES['attachment2']['name'])) {
            $dir2 = FCPATH . 'uploads/leave/';
            if (!is_dir($dir2)) { mkdir($dir2, 0755, true); }
            $this->upload->initialize([
                'upload_path'   => $dir2,
                'allowed_types' => 'jpg|jpeg|png|pdf',
                'overwrite'     => true,
                'file_name'     => 'leave2_' . $user['id'] . '_' . date('YmdHis'),
                'max_size'      => 4096,
            ]);
            if ($this->upload->do_upload('attachment2')) {
                $f2 = $this->upload->data();
                $attachment2_path = 'uploads/leave/' . $f2['file_name'];
            }
        }

        $approver_ids = $this->resolve_leave_submission_approver_ids($profile);
        $leader_id = $approver_ids['leader_id'];
        $hr_id = $approver_ids['hr_id'];

        $request_data = [
            'user_id' => $user['id'],
            'user_profile_id' => $profile['id'],
            'leave_type_id' => $leave_type_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'jam_mulai' => $jam_mulai,
            'jam_selesai' => $jam_selesai,
            'attachment2_path' => $attachment2_path,
            'total_days' => $total_days,
            'reason' => $reason,
            'attachment_path' => $attachment_path,
            'status' => 'pending_leader',
            'leader_id' => $leader_id,
            'hr_id' => $hr_id,
            'submitted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $approvals = [
            [
                'approver_id' => $leader_id,
                'approver_role' => 'leader',
                'status' => 'pending'
            ],
            [
                'approver_id' => $hr_id,
                'approver_role' => 'hr',
                'status' => 'pending'
            ]
        ];

        $request_id = $this->leave_model->create_request($request_data, $approvals);
        $this->send_submission_email_notifications($request_id);
        $this->send_submission_in_app_notifications($request_id);
        $this->_antre_izin_wa($request_id);
        $this->session->set_flashdata('success', 'Pengajuan berhasil dikirim.');
        redirect(base_url() . 'leave/request');
    }

    public function update_request()
    {
        $user = $_SESSION['user'];
        $request_id = intval($this->input->post('request_id'));

        if (empty($request_id)) {
            $this->session->set_flashdata('error', 'ID pengajuan tidak valid.');
            redirect(base_url() . 'leave/request');
        }

        $request = $this->leave_model->get_request($request_id);
        if (empty($request) || intval($request['user_id']) !== intval($user['id'])) {
            $this->session->set_flashdata('error', 'Data pengajuan tidak ditemukan.');
            redirect(base_url() . 'leave/request');
        }

        if (in_array($request['status'], ['approved', 'rejected'], true)) {
            $this->session->set_flashdata('error', 'Pengajuan final tidak dapat diubah.');
            redirect(base_url() . 'leave/request');
        }

        $leave_type_id = $this->input->post('leave_type_id');
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $reason = $this->input->post('reason');

        if (empty($leave_type_id) || empty($start_date) || empty($end_date)) {
            $this->session->set_flashdata('error', 'Mohon lengkapi form pengajuan.');
            redirect(base_url() . 'leave/request');
        }

        if (strtotime($end_date) < strtotime($start_date)) {
            $this->session->set_flashdata('error', 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.');
            redirect(base_url() . 'leave/request');
        }

        $leave_type = $this->mymodel->selectWithQuery("SELECT * FROM leave_types WHERE id = '$leave_type_id' LIMIT 1");
        if (empty($leave_type)) {
            $this->session->set_flashdata('error', 'Tipe pengajuan tidak ditemukan.');
            redirect(base_url() . 'leave/request');
        }

        $profile = $this->leave_model->get_user_profile($user['id']);
        if ($this->leave_model->is_deducting_leave_type($leave_type_id) && $this->is_leave_balance_unset($profile)) {
            $this->session->set_flashdata('error', 'Sisa cuti belum diatur. Hanya pengajuan Cuti Tahunan yang diblokir sampai HR memperbarui join date dan leave balance.');
            redirect(base_url() . 'leave/request');
        }

        if (empty($profile)) {
            $this->session->set_flashdata('error', 'Profil belum lengkap. Mohon lengkapi profil terlebih dahulu.');
            redirect(base_url() . 'profile');
        }

        $attachment_path = $request['attachment_path'];
        if (!empty($_FILES['attachment']['name'])) {
            $dir = FCPATH . "uploads/leave/";
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $config['upload_path'] = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png|pdf';
            $config['overwrite'] = true;
            $config['file_name'] = 'leave_' . $user['id'] . '_' . date('YmdHis');
            $config['max_size'] = 4096;

            $this->upload->initialize($config);
            if (!$this->upload->do_upload('attachment')) {
                $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
                redirect(base_url() . 'leave/request');
            }

            $file = $this->upload->data();
            $attachment_path = 'uploads/leave/' . $file['file_name'];

            if (!empty($request['attachment_path']) && file_exists(FCPATH . $request['attachment_path'])) {
                @unlink(FCPATH . $request['attachment_path']);
            }
        }

        $total_days = $this->calculate_total_days($start_date, $end_date);
        $approver_ids = $this->resolve_leave_submission_approver_ids($profile);
        $leader_id = $approver_ids['leader_id'];
        $hr_id = $approver_ids['hr_id'];

        $this->db->trans_begin();

        $this->db->update('leave_requests', [
            'leave_type_id' => $leave_type_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_days' => $total_days,
            'reason' => $reason,
            'attachment_path' => $attachment_path,
            'status' => 'pending_leader',
            'leader_id' => $leader_id,
            'hr_id' => $hr_id,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $request_id]);

        $this->db->update('leave_approvals', [
            'approver_id' => $leader_id,
            'status' => 'pending',
            'note' => null,
            'decided_at' => null
        ], [
            'leave_request_id' => $request_id,
            'approver_role' => 'leader'
        ]);

        $this->db->update('leave_approvals', [
            'approver_id' => $hr_id,
            'status' => 'pending',
            'note' => null,
            'decided_at' => null
        ], [
            'leave_request_id' => $request_id,
            'approver_role' => 'hr'
        ]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Gagal memperbarui pengajuan.');
        } else {
            $this->db->trans_commit();
            if ($this->db->table_exists('leave_email_approval_tokens')) {
                $this->leave_model->invalidate_email_approval_tokens($request_id);
            }
            $this->send_submission_email_notifications($request_id);
            $this->send_submission_in_app_notifications($request_id);
            $this->session->set_flashdata('success', 'Pengajuan berhasil diperbarui.');
        }

        redirect(base_url() . 'leave/request');
    }

    /**
     * Hapus pengajuan dari sisi HRD.
     *
     * Berbeda dari delete_request() yang dipakai karyawan: yang ini
     * boleh menghapus pengajuan berstatus apa pun, termasuk yang sudah
     * disetujui. Karyawan sendiri tetap tidak bisa menghapus pengajuan
     * yang sudah final, karena jejaknya dipakai untuk perhitungan
     * kehadiran.
     */
    /**
     * Masukkan pengajuan izin ke antrean WhatsApp.
     *
     * Hanya menulis baris; pengirimannya dikerjakan proses terpisah,
     * supaya karyawan tidak menunggu di layar dan pengajuannya tetap
     * tersimpan kalau WhatsApp sedang bermasalah.
     */
    private function _antre_izin_wa($request_id)
    {
        try {
            $r = $this->db->select('lr.*, u.full_name, u.role_text, lt.name AS jenis')
                          ->from('leave_requests lr')
                          ->join('user u', 'u.id = lr.user_id', 'left')
                          ->join('leave_types lt', 'lt.id = lr.leave_type_id', 'left')
                          ->where('lr.id', (int)$request_id)
                          ->get()->row_array();
            if (!$r) return;

            $this->db->insert('wa_izin_antrean', [
                'request_id'    => (int)$request_id,
                'nama'          => $r['full_name'] ?: 'Karyawan',
                'jabatan'       => $r['role_text'] ?: null,
                'jenis'         => $r['jenis'] ?: 'Izin',
                'tanggal_mulai' => $r['start_date'],
                'tanggal_akhir' => $r['end_date'],
                'jam_mulai'     => $r['jam_mulai'],
                'jam_selesai'   => $r['jam_selesai'],
                'alasan'        => $r['reason'],
                'lampiran1'     => $r['attachment_path'],
                'lampiran2'     => $r['attachment2_path'] ?? null,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'antrean izin wa gagal: ' . $e->getMessage());
        }
    }

    public function hapus_admin()
    {
        $user = $_SESSION['user'];
        $peran = strtolower($user['role_text'] ?? '');
        $boleh = in_array($peran, ['developer', 'super_admin', 'head_admin', 'owner', 'hr', 'hrd'], TRUE);

        if (!$boleh) {
            $this->session->set_flashdata('error', 'Anda tidak berwenang menghapus pengajuan.');
            redirect(base_url() . 'leave');
        }

        $request_id = intval($this->input->post('request_id'));
        if (empty($request_id)) {
            $this->session->set_flashdata('error', 'ID pengajuan tidak valid.');
            redirect(base_url() . 'leave');
        }

        $this->db->where('id', $request_id)->delete('leave_requests');
        $this->session->set_flashdata('success', 'Pengajuan dihapus.');
        redirect(base_url() . 'leave');
    }

    public function delete_request()
    {
        $user = $_SESSION['user'];
        $request_id = intval($this->input->post('request_id'));

        if (empty($request_id)) {
            $this->session->set_flashdata('error', 'ID pengajuan tidak valid.');
            redirect(base_url() . 'leave/request');
        }

        $request = $this->leave_model->get_request($request_id);
        if (empty($request) || intval($request['user_id']) !== intval($user['id'])) {
            $this->session->set_flashdata('error', 'Data pengajuan tidak ditemukan.');
            redirect(base_url() . 'leave/request');
        }

        if (in_array($request['status'], ['approved', 'rejected'], true)) {
            $this->session->set_flashdata('error', 'Pengajuan final tidak dapat dihapus.');
            redirect(base_url() . 'leave/request');
        }

        $this->db->trans_begin();
        $this->db->delete('leave_approvals', ['leave_request_id' => $request_id]);
        if ($this->db->table_exists('leave_email_approval_tokens')) {
            $this->db->delete('leave_email_approval_tokens', ['leave_request_id' => $request_id]);
        }
        $this->db->delete('leave_requests', ['id' => $request_id]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Gagal menghapus pengajuan.');
        } else {
            $this->db->trans_commit();
            if (!empty($request['attachment_path']) && file_exists(FCPATH . $request['attachment_path'])) {
                @unlink(FCPATH . $request['attachment_path']);
            }
            $this->session->set_flashdata('success', 'Pengajuan berhasil dihapus.');
        }

        redirect(base_url() . 'leave/request');
    }

    private function calculate_total_days($start_date, $end_date)
    {
        try {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $diff = $start->diff($end);
            return $diff->days + 1;
        } catch (Exception $e) {
            return 1;
        }
    }

    public function events()
    {
        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Kalender Event - ' . $this->template->title();
        $data['events'] = $this->leave_model->list_events();
        $data['can_manage'] = $this->can_manage_leave($data['user']);
        $data['content'] = $this->load->view('leave/events', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function store_event()
    {
        $user = $_SESSION['user'];
        if (!$this->can_manage_leave($user)) {
            redirect(base_url() . 'dashboard');
        }

        $title = $this->input->post('title');
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $description = $this->input->post('description');
        $is_blocking = $this->input->post('is_blocking') ? 1 : 0;

        if (empty($title) || empty($start_date) || empty($end_date)) {
            $this->session->set_flashdata('error', 'Mohon lengkapi data event.');
            redirect(base_url() . 'leave/events');
        }

        $payload = [
            'title' => $title,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'description' => $description,
            'is_blocking' => $is_blocking,
            'created_by' => $user['id']
        ];

        $this->leave_model->create_event($payload);
        $this->session->set_flashdata('success', 'Event berhasil ditambahkan.');
        redirect(base_url() . 'leave/events');
    }

    public function update_event()
    {
        $user = $_SESSION['user'];
        if (!$this->can_manage_leave($user)) {
            redirect(base_url() . 'dashboard');
        }

        $event_id = $this->input->post('id');
        $title = $this->input->post('title');
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $description = $this->input->post('description');
        $is_blocking = $this->input->post('is_blocking') ? 1 : 0;

        if (empty($event_id) || empty($title) || empty($start_date) || empty($end_date)) {
            $this->session->set_flashdata('error', 'Mohon lengkapi data event.');
            redirect(base_url() . 'leave/events');
        }

        $payload = [
            'title' => $title,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'description' => $description,
            'is_blocking' => $is_blocking
        ];

        $this->leave_model->update_event($event_id, $payload);
        $this->session->set_flashdata('success', 'Event berhasil diperbarui.');
        redirect(base_url() . 'leave/events');
    }

    public function update_approval_inline()
    {
        $user = $_SESSION['user'];
        if (!$this->can_manage_leave($user)) {
            $this->output->set_status_header(403);
            $this->output->set_output(json_encode(['success' => false, 'message' => 'Forbidden']));
            return;
        }

        $request_id = $this->input->post('request_id');
        $role = $this->input->post('role');
        $status = $this->input->post('status');

        if (empty($request_id) || !in_array($role, ['leader', 'hr']) || !in_array($status, ['pending', 'approved', 'rejected'])) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(['success' => false, 'message' => 'Invalid input']));
            return;
        }

        $request = $this->leave_model->get_request($request_id);
        if (empty($request)) {
            $this->output->set_status_header(404);
            $this->output->set_output(json_encode(['success' => false, 'message' => 'Request not found']));
            return;
        }

        $this->db->trans_begin();

        $this->leave_model->update_approval($request_id, $role, $status, null);

        $approvals = $this->leave_model->get_request_approvals($request_id);
        $leader_status = 'pending';
        $hr_status = 'pending';
        if (!empty($approvals)) {
            foreach ($approvals as $approval_row) {
                if (($approval_row['approver_role'] ?? '') === 'leader') {
                    $leader_status = $approval_row['status'] ?? 'pending';
                }
                if (($approval_row['approver_role'] ?? '') === 'hr') {
                    $hr_status = $approval_row['status'] ?? 'pending';
                }
            }
        }

        $final_status = 'pending_leader';
        if ($leader_status === 'rejected' || $hr_status === 'rejected') {
            $final_status = 'rejected';
            $this->create_notification($request['user_id'], 'Pengajuan ditolak', 'Pengajuan Anda cuti ditolak.', 'danger');
        } elseif ($leader_status === 'approved' && $hr_status === 'approved') {
            $final_status = 'approved';
            $this->create_notification($request['user_id'], 'Pengajuan disetujui', 'Pengajuan Anda cuti telah disetujui.', 'success');
        } elseif ($leader_status === 'approved') {
            $final_status = 'pending_hr';
        } else {
            $final_status = 'pending_leader';
        }

        $this->leave_model->update_request_status($request_id, $final_status);
        $this->leave_model->sync_balance_for_request($request, $final_status);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->output->set_status_header(500);
            $this->output->set_output(json_encode(['success' => false, 'message' => 'Update failed']));
            return;
        }

        $this->db->trans_commit();

        $updated_request = $this->leave_model->get_request($request_id);
        $updated_status = !empty($updated_request) ? $updated_request['status'] : null;

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'request_id' => intval($request_id),
                'status' => $updated_status,
                'status_label' => $this->get_status_label($updated_status)
            ]));
    }

    public function get_leave_types_manage()
    {
        $user = $_SESSION['user'];
        if (!$this->can_manage_leave($user)) {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Forbidden']));
            return;
        }

        $types = $this->mymodel->selectWithQuery("SELECT * FROM leave_types WHERE is_active = 1 ORDER BY name ASC");
        $html = '';
        foreach ($types as $type) {
            $requires_attachment = intval($type['requires_attachment'] ?? 0) === 1;
            $deducts_leave = intval($type['deducts_leave'] ?? 0) === 1;
            $badges = [];
            if ($requires_attachment) {
                $badges[] = '<span class="badge bg-warning text-dark ms-2">Lampiran wajib</span>';
            }
            if ($deducts_leave) {
                $badges[] = '<span class="badge bg-danger ms-2">Mengurangi cuti</span>';
            }
            $html .= '
                <div class="p-2 bg-primary text-white rounded-2 d-flex align-items-center leave-type-item" data-id="'.intval($type['id']).'">
                    <span class="me-2">'.htmlspecialchars($type['name'] ?? '-', ENT_QUOTES, 'UTF-8').'</span>
                    '.implode('', $badges).'
                    <a href="#" class="text-white lh-1 delete-leave-type ms-2"
                    style="text-decoration: none; opacity: 0.7;">
                        <i class="bi bi-x-circle" style="font-size: 0.9rem;"></i>
                    </a>
                </div>
            ';
        }

        echo $html;
    }

    public function save_leave_type()
    {
        $user = $_SESSION['user'];
        if (!$this->can_manage_leave($user)) {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Forbidden']));
            return;
        }

        $name = trim((string) $this->input->post('name'));
        $requires_attachment = intval($this->input->post('requires_attachment')) === 1 ? 1 : 0;
        $deducts_leave = intval($this->input->post('deducts_leave')) === 1 ? 1 : 0;

        if ($name === '') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Nama tipe cuti tidak boleh kosong.'
            ]);
            return;
        }

        $name_sql = $this->db->escape_str(strtolower($name));
        $exists = $this->mymodel->selectWithQuery("
            SELECT id
            FROM leave_types
            WHERE LOWER(name) = '$name_sql'
              AND is_active = 1
            LIMIT 1
        ");
        if (!empty($exists)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tipe cuti sudah ada.'
            ]);
            return;
        }

        $code = $this->build_leave_type_code($name);
        $base_code = $code;
        $suffix = 2;
        while (!empty($this->mymodel->selectWithQuery("SELECT id FROM leave_types WHERE code = " . $this->db->escape($code) . " LIMIT 1"))) {
            $code = $base_code . '_' . $suffix;
            $suffix++;
        }

        $insert = [
            'name' => $name,
            'code' => $code,
            'requires_attachment' => $requires_attachment,
            'deducts_leave' => $deducts_leave,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('leave_types', $insert);

        echo json_encode([
            'status' => 'success',
            'message' => 'Tipe cuti berhasil ditambahkan.',
            'data' => [
                'id' => $this->db->insert_id(),
                'name' => $name,
                'code' => $code,
                'requires_attachment' => $requires_attachment,
                'deducts_leave' => $deducts_leave,
            ]
        ]);
    }

    public function delete_leave_type($id)
    {
        $user = $_SESSION['user'];
        if (!$this->can_manage_leave($user)) {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Forbidden']));
            return;
        }

        $id = intval($id);
        if ($id <= 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'ID tipe cuti tidak valid.'
            ]);
            return;
        }

        $existing = $this->mymodel->selectWithQuery("SELECT id FROM leave_types WHERE id = '$id' AND is_active = 1 LIMIT 1");
        if (empty($existing)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tipe cuti tidak ditemukan.'
            ]);
            return;
        }

        $this->db->where('id', $id);
        $this->db->update('leave_types', [
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($this->db->affected_rows() > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Tipe cuti berhasil dihapus.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal menghapus tipe cuti.'
            ]);
        }
    }

    public function delete_event()
    {
        $user = $_SESSION['user'];
        if (!$this->can_manage_leave($user)) {
            redirect(base_url() . 'dashboard');
        }

        $event_id = $this->input->get('id');
        if (empty($event_id)) {
            $this->session->set_flashdata('error', 'ID event tidak valid.');
            redirect(base_url() . 'leave/events');
        }

        $this->leave_model->delete_event($event_id);
        $this->session->set_flashdata('success', 'Event berhasil dihapus.');
        redirect(base_url() . 'leave/events');
    }

    private function can_manage_leave($user)
    {
        if (isset($user['role']) && $user['role'] == '1') {
            return true;
        }

        return in_array($user['id'], [2, 5, 42]) || (isset($user['role_text']) && strtolower($user['role_text']) === 'ceo');
    }

    private function build_leave_type_code($name)
    {
        $code = strtolower(trim((string) $name));
        $code = preg_replace('/[^a-z0-9]+/', '_', $code);
        $code = trim($code, '_');
        return $code !== '' ? $code : ('leave_type_' . time());
    }

    private function resolve_leave_submission_approver_ids($profile)
    {
        $position_id = intval($profile['position_id'] ?? 0);

        if ($position_id === 28) {
            return [
                'leader_id' => 42,
                'hr_id' => 42,
            ];
        }

        return [
            'leader_id' => $position_id === 3 ? 5 : 2,
            'hr_id' => 42,
        ];
    }

    private function get_approver_role($user, $request)
    {
        if ($request['status'] === 'pending_leader' && $user['id'] == $request['leader_id']) {
            return 'leader';
        }

        if ($request['status'] === 'pending_hr' && $user['id'] == $request['hr_id']) {
            return 'hr';
        }

        if (isset($user['role']) && $user['role'] == '1') {
            return $request['status'] === 'pending_hr' ? 'hr' : 'leader';
        }

        return null;
    }

    private function is_request_waiting_for_role($request, $approver_role)
    {
        if ($approver_role === 'leader') {
            return ($request['status'] ?? '') === 'pending_leader';
        }

        if ($approver_role === 'hr') {
            return ($request['status'] ?? '') === 'pending_hr';
        }

        return false;
    }

    private function is_email_token_still_authorized($request, $token_row)
    {
        $role = (string) ($token_row['approver_role'] ?? '');
        $approver_id = intval($token_row['approver_id'] ?? 0);

        if ($role === 'leader') {
            return intval($request['leader_id'] ?? 0) === $approver_id;
        }

        if ($role === 'hr') {
            return intval($request['hr_id'] ?? 0) === $approver_id;
        }

        return false;
    }

    private function process_leave_approval_action($request, $approver_role, $decision, $note = null, $token_row = null)
    {
        $request_id = intval($request['id'] ?? 0);
        if ($request_id <= 0) {
            return ['success' => false, 'message' => 'ID pengajuan tidak valid.'];
        }

        if (!in_array($decision, ['approved', 'rejected'], true)) {
            return ['success' => false, 'message' => 'Aksi approval tidak valid.'];
        }

        if (!$this->is_request_waiting_for_role($request, $approver_role)) {
            return ['success' => false, 'message' => 'Pengajuan ini sudah tidak menunggu aksi tersebut.'];
        }

        $this->db->trans_begin();

        $this->leave_model->update_approval($request_id, $approver_role, $decision, $note);

        if ($decision === 'approved') {
            if ($approver_role === 'leader') {
                $this->leave_model->update_request_status($request_id, 'pending_hr');
            } else {
                $this->leave_model->update_request_status($request_id, 'approved');
                $this->leave_model->sync_balance_for_request($request, 'approved');
                $this->create_notification($request['user_id'], 'Pengajuan disetujui', 'Pengajuan Anda cuti telah disetujui.', 'success');
            }
        } else {
            $this->leave_model->update_request_status($request_id, 'rejected');
            $this->create_notification($request['user_id'], 'Pengajuan ditolak', 'Pengajuan Anda cuti ditolak.', 'danger');
        }

        if ($this->db->table_exists('leave_email_approval_tokens')) {
            if (!empty($token_row['id'])) {
                $this->leave_model->update_email_approval_token(intval($token_row['id']), [
                    'status' => 'used',
                    'used_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->leave_model->invalidate_email_approval_tokens(
                $request_id,
                !empty($token_row['approver_id']) ? intval($token_row['approver_id']) : null,
                $approver_role,
                null,
                !empty($token_row['id']) ? intval($token_row['id']) : null
            );

            if ($decision === 'rejected' || $approver_role === 'hr') {
                $this->leave_model->invalidate_email_approval_tokens($request_id);
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => 'Gagal memproses approval.'];
        }

        $this->db->trans_commit();

        if ($decision === 'approved' && $approver_role === 'leader') {
            $this->send_submission_email_notifications($request_id);
            $this->send_submission_in_app_notifications($request_id);
        }

        return [
            'success' => true,
            'message' => $decision === 'approved'
                ? 'Pengajuan berhasil disetujui.'
                : 'Pengajuan berhasil ditolak.',
        ];
    }

    private function render_email_action_result($message, $success)
    {
        $title = $success ? 'Aksi Berhasil' : 'Aksi Gagal';
        $accent = $success ? '#166534' : '#991b1b';
        $background = $success ? '#f0fdf4' : '#fef2f2';
        $border = $success ? '#86efac' : '#fca5a5';

        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head>';
        echo '<body style="margin:0;padding:32px;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#111827;">';
        echo '<div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:18px;overflow:hidden;">';
        echo '<div style="padding:24px 28px;background:' . $background . ';border-bottom:1px solid ' . $border . ';">';
        echo '<div style="font-size:12px;letter-spacing:1.2px;text-transform:uppercase;color:' . $accent . ';font-weight:700;">BHSKIN Leave Approval</div>';
        echo '<h1 style="margin:10px 0 0;font-size:24px;color:' . $accent . ';">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
        echo '</div><div style="padding:28px;"><p style="margin:0;font-size:15px;line-height:1.8;">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></div></div></body></html>';
    }

    private function create_notification($user_id, $title, $message, $type = 'info', $category = 'team', $subcategory = 'pengajuan_cuti')
    {
        try {
            $this->db->insert('notifications', [
                'user_id' => $user_id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'category' => $category,
                'subcategory' => $subcategory,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Silent fail if notifications table not available
        }
    }

    private function send_submission_in_app_notifications($request_id)
    {
        $request = $this->leave_model->get_request($request_id);
        if (empty($request)) {
            return;
        }

        $recipients = $this->get_leave_submission_recipients($request);
        if (empty($recipients)) {
            return;
        }

        $requester_name = trim((string) ($request['user_name'] ?? 'Karyawan'));
        $leave_type_name = trim((string) ($request['leave_type_name'] ?? 'leave'));

        foreach ($recipients as $recipient) {
            $this->create_notification(
                intval($recipient['id']),
                'Pengajuan ' . $leave_type_name . ' baru',
                $requester_name . ' mengajukan ' . $leave_type_name . ' dan menunggu approval Anda.',
                'info',
                'team',
                'pengajuan_cuti'
            );
        }
    }

    private function get_status_label($status)
    {
        $status_map = [
            'pending_leader' => 'Menunggu Leader',
            'pending_hr' => 'Menunggu HR',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak'
        ];

        return $status_map[$status] ?? $status;
    }

    private function is_leave_balance_unset($profile)
    {
        if (empty($profile)) {
            return false;
        }

        if (!empty($profile['is_probation']) && intval($profile['is_probation']) === 1) {
            return false;
        }

        $join_date = trim((string)($profile['join_date'] ?? ''));
        $accrual_start = trim((string)($profile['leave_accrual_start'] ?? ''));
        $has_accrual_start = $accrual_start !== '' && $accrual_start !== '0000-00-00';
        $has_join_date = $join_date !== '' && $join_date !== '0000-00-00';
        $has_manual_balance = isset($profile['leave_balance'])
            && $profile['leave_balance'] !== null
            && $profile['leave_balance'] !== '';

        return !$has_accrual_start && !$has_join_date && !$has_manual_balance;
    }

    private function send_submission_email_notifications($request_id)
    {
        $request = $this->leave_model->get_request($request_id);
        if (empty($request)) {
            return;
        }

        $recipients = $this->get_leave_submission_recipients($request);
        if (empty($recipients)) {
            log_message('error', 'Leave submission email skipped: no valid recipients for request #' . $request_id);
            return;
        }

        $app_name = trim((string) $this->template->title());
        $requester_name = htmlspecialchars((string) ($request['user_name'] ?? 'Karyawan'), ENT_QUOTES, 'UTF-8');
        $leave_type_name = htmlspecialchars((string) ($request['leave_type_name'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $start_date = htmlspecialchars((string) ($request['start_date'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $end_date = htmlspecialchars((string) ($request['end_date'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $total_days = intval($request['total_days'] ?? 0);
        $reason = trim((string) ($request['reason'] ?? ''));
        $reason_html = nl2br(htmlspecialchars($reason !== '' ? $reason : '-', ENT_QUOTES, 'UTF-8'));
        $approval_url = htmlspecialchars(base_url('leave'), ENT_QUOTES, 'UTF-8');

        foreach ($recipients as $recipient) {
            $approver_role = $this->resolve_recipient_approver_role($request, intval($recipient['id']));
            $approval_stage_label = $this->get_approval_stage_label($approver_role);
            $subject = 'Pengajuan ' . ($request['leave_type_name'] ?? 'Leave') . ' - Tahap ' . $approval_stage_label . ' - ' . $app_name;
            $approve_action_url = $this->build_email_action_url($request, intval($recipient['id']), $approver_role, 'approved');
            $reject_action_url = $this->build_email_action_url($request, intval($recipient['id']), $approver_role, 'rejected');
            $approve_action_url_html = htmlspecialchars($approve_action_url ?: base_url('leave'), ENT_QUOTES, 'UTF-8');
            $reject_action_url_html = htmlspecialchars($reject_action_url ?: base_url('leave'), ENT_QUOTES, 'UTF-8');
            $recipient_name = htmlspecialchars((string) ($recipient['full_name'] ?? 'Tim Approval'), ENT_QUOTES, 'UTF-8');
            $body_html = '
                <div style="margin:0; padding:24px 0; background:#f5f7fb; font-family:Arial,Helvetica,sans-serif; color:#1f2937;">
                    <div style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:18px; overflow:hidden;">
                        <div style="padding:24px 28px; background:linear-gradient(135deg,#111827 0%,#1f2937 100%); color:#ffffff;">
                            <div style="font-size:12px; letter-spacing:1.4px; text-transform:uppercase; opacity:.78;">BHSKIN Leave Notification</div>
                            <h1 style="margin:10px 0 0; font-size:24px; line-height:1.3;">Pengajuan ' . $leave_type_name . ' - Tahap ' . htmlspecialchars($approval_stage_label, ENT_QUOTES, 'UTF-8') . '</h1>
                            <p style="margin:8px 0 0; font-size:14px; line-height:1.7; color:rgba(255,255,255,.82);">
                                Ada pengajuan baru yang membutuhkan peninjauan Anda pada tahap ' . htmlspecialchars($approval_stage_label, ENT_QUOTES, 'UTF-8') . '.
                            </p>
                        </div>

                        <div style="padding:28px;">
                            <p style="margin:0 0 18px; font-size:15px; line-height:1.8;">Halo ' . $recipient_name . ',</p>

                            <div style="margin-bottom:20px; padding:16px 18px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:14px;">
                                <div style="font-size:12px; text-transform:uppercase; letter-spacing:1.2px; color:#6b7280; margin-bottom:8px;">Pengajuan Oleh</div>
                                <div style="font-size:22px; font-weight:700; color:#111827; margin-bottom:6px;">' . $requester_name . '</div>
                                <div style="display:inline-block; padding:6px 10px; border-radius:999px; background:#fef3c7; color:#92400e; font-size:12px; font-weight:700;">
                                    Menunggu Approval ' . htmlspecialchars($approval_stage_label, ENT_QUOTES, 'UTF-8') . '
                                </div>
                            </div>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse; margin-bottom:20px;">
                                <tr>
                                    <td style="padding:12px 0; border-bottom:1px solid #eef2f7; width:160px; color:#6b7280; font-size:14px;">Jenis Pengajuan</td>
                                    <td style="padding:12px 0; border-bottom:1px solid #eef2f7; color:#111827; font-size:14px; font-weight:600;">' . $leave_type_name . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0; border-bottom:1px solid #eef2f7; color:#6b7280; font-size:14px;">Periode</td>
                                    <td style="padding:12px 0; border-bottom:1px solid #eef2f7; color:#111827; font-size:14px; font-weight:600;">' . $start_date . ' s/d ' . $end_date . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0; border-bottom:1px solid #eef2f7; color:#6b7280; font-size:14px;">Total Hari</td>
                                    <td style="padding:12px 0; border-bottom:1px solid #eef2f7; color:#111827; font-size:14px; font-weight:600;">' . $total_days . ' hari</td>
                                </tr>
                            </table>

                            <div style="margin-bottom:24px;">
                                <div style="font-size:13px; text-transform:uppercase; letter-spacing:1px; color:#6b7280; margin-bottom:10px;">Alasan Pengajuan</div>
                                <div style="padding:16px 18px; background:#fffaf0; border:1px solid #fde68a; border-radius:14px; font-size:14px; line-height:1.8; color:#374151;">
                                    ' . $reason_html . '
                                </div>
                            </div>

                            <div style="text-align:center; margin-bottom:18px;">
                                <a href="' . $approve_action_url_html . '" style="display:inline-block; margin:0 6px 12px; padding:14px 24px; background:#166534; color:#ffffff; text-decoration:none; border-radius:12px; font-size:14px; font-weight:700;">
                                    Approve ' . htmlspecialchars($approval_stage_label, ENT_QUOTES, 'UTF-8') . '
                                </a>
                                <a href="' . $reject_action_url_html . '" style="display:inline-block; margin:0 6px 12px; padding:14px 24px; background:#b91c1c; color:#ffffff; text-decoration:none; border-radius:12px; font-size:14px; font-weight:700;">
                                    Reject ' . htmlspecialchars($approval_stage_label, ENT_QUOTES, 'UTF-8') . '
                                </a>
                                <div style="margin-top:6px;">
                                    <a href="' . $approval_url . '" style="display:inline-block; padding:12px 18px; background:#111827; color:#ffffff; text-decoration:none; border-radius:12px; font-size:13px; font-weight:700;">
                                        Buka Leave Management
                                    </a>
                                </div>
                            </div>

                            <p style="margin:0; font-size:13px; line-height:1.8; color:#6b7280;">
                                Jika tombol tidak bisa dibuka, gunakan tautan berikut:<br>
                                Approve: <a href="' . $approve_action_url_html . '" style="color:#2563eb; text-decoration:none;">' . $approve_action_url_html . '</a><br>
                                Reject: <a href="' . $reject_action_url_html . '" style="color:#2563eb; text-decoration:none;">' . $reject_action_url_html . '</a>
                            </p>
                        </div>
                    </div>
                </div>
            ';

            $sent = $this->send_notification_email(
                $recipient['email'],
                $subject,
                $body_html,
                'BHSKIN Leave Notification'
            );

            if (!$sent) {
                log_message(
                    'error',
                    'Leave submission email failed for request #' . $request_id . ' to ' . $recipient['email']
                );
            }
        }
    }

    private function get_leave_submission_recipients($request)
    {
        $recipient_ids = $this->get_current_leave_recipient_ids($request);

        $recipient_ids = array_values(array_unique(array_filter($recipient_ids)));
        if (empty($recipient_ids)) {
            return [];
        }

        $rows = $this->mymodel->selectWithQuery("SELECT id, full_name, email
            FROM user
            WHERE id IN (" . implode(',', $recipient_ids) . ")");

        $recipients = [];
        foreach ($rows as $row) {
            $email = trim((string) ($row['email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $recipients[] = [
                'id' => intval($row['id']),
                'full_name' => $row['full_name'] ?? '',
                'email' => $email,
            ];
        }

        return $recipients;
    }

    private function get_current_leave_recipient_ids($request)
    {
        $status = (string) ($request['status'] ?? '');

        if ($status === 'pending_leader' && !empty($request['leader_id'])) {
            return [intval($request['leader_id'])];
        }

        if ($status === 'pending_hr' && !empty($request['hr_id'])) {
            return [intval($request['hr_id'])];
        }

        return [];
    }

    private function resolve_recipient_approver_role($request, $recipient_id)
    {
        if (intval($request['leader_id'] ?? 0) === intval($recipient_id)) {
            return 'leader';
        }

        if (intval($request['hr_id'] ?? 0) === intval($recipient_id)) {
            return 'hr';
        }

        return null;
    }

    private function get_approval_stage_label($approver_role)
    {
        if ($approver_role === 'leader') {
            return 'Leader';
        }

        if ($approver_role === 'hr') {
            return 'HR';
        }

        return 'Approval';
    }

    private function build_email_action_url($request, $approver_id, $approver_role, $action)
    {
        if ($approver_id <= 0 || !in_array($approver_role, ['leader', 'hr'], true) || !in_array($action, ['approved', 'rejected'], true)) {
            return null;
        }

        if (!$this->db->table_exists('leave_email_approval_tokens')) {
            log_message('error', 'Leave email action URL skipped: leave_email_approval_tokens table is missing.');
            return null;
        }

        $request_id = intval($request['id'] ?? 0);
        if ($request_id <= 0) {
            return null;
        }

        $this->leave_model->invalidate_email_approval_tokens($request_id, $approver_id, $approver_role, $action);

        $token = bin2hex(random_bytes(32));
        $created_at = date('Y-m-d H:i:s');
        $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));

        $saved = $this->leave_model->create_email_approval_token([
            'leave_request_id' => $request_id,
            'approver_id' => $approver_id,
            'approver_role' => $approver_role,
            'action' => $action,
            'token' => $token,
            'status' => 'active',
            'expires_at' => $expires_at,
            'used_at' => null,
            'created_at' => $created_at,
            'updated_at' => $created_at,
        ]);

        if (!$saved) {
            log_message('error', 'Leave email action URL failed to save token for request #' . $request_id);
            return null;
        }

        return base_url('leave/email_action?token=' . rawurlencode($token));
    }

    private function send_notification_email($to_email, $subject, $body_html, $from_name = 'BHSKIN Notification')
    {
        return $this->app_mailer->send_html($to_email, $subject, $body_html, [
            'from_name' => $from_name,
            'reply_to_email' => 'hr@bhskin.co.id',
            'reply_to_name' => 'BHSKIN HR',
        ]);
    }
}
