<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';
class User extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->model('Leave_model', 'leave_model');
        $this->load->library('permission');
        $this->load->library('template');

        // Set public methods (no permission required)
        $this->set_public_methods([]);

        // Override method-to-action mapping if needed
        $this->set_method_permissions([
            'remove' => 'delete',
            'sync' => 'create',
            'sync_process' => 'create',
            'action' => 'edit',
            'action_process' => 'edit',
            'bulk_delete' => 'delete',
            'bulk_update_role' => 'edit',
            'setujui' => 'edit',
            'setujui_proses' => 'edit',
            'tolak' => 'delete'
        ]);
    }
    
    /**
     * Tinjau pendaftar baru dari tautan japri WA: data pendaftar + tombol
     * Setujui / Tolak. Membuka tautan saja TIDAK mengubah apa pun, supaya akun
     * tidak tersetujui karena tersentuh atau dibuka hanya untuk melihat.
     * Semua alamat ditulis relatif: di belakang Cloudflare base_url() jadi
     * http://, dan POST ke http:// dialihkan jadi GET -- tombol tidak bekerja.
     */
    public function setujui($id = 0)
    {
        $u = $this->_pendaftar($id);
        if (!$u) { $this->_kartu('&#10060;', 'Pendaftar tidak ditemukan', '<p style="color:#64748b">Akun ini sudah ditolak atau dihapus.</p>'); return; }
        if ((int) $u['disetujui'] >= 1) {
            $this->_kartu('&#9989;', 'Sudah disetujui', $this->_ringkas($u), $this->_tombol_edit($u['id']));
            return;
        }
        $aksi = '<form method="post" action="/user/setujui_proses/' . (int) $u['id'] . '" style="display:inline">'
              . '<button type="submit" style="' . $this->_gaya_tombol('#16a34a') . '">Setujui</button></form>'
              . '<form method="post" action="/user/tolak/' . (int) $u['id'] . '" style="display:inline"'
              . ' onsubmit="return confirm(\'Tolak dan hapus akun ini beserta semua datanya?\')">'
              . '<button type="submit" style="' . $this->_gaya_tombol('#dc2626') . '">Tolak</button></form>';
        $this->_kartu('&#9203;', 'Menunggu persetujuan', $this->_ringkas($u), $aksi);
    }

    public function setujui_proses($id = 0)
    {
        if ($this->input->method() !== 'post') { redirect('/user/setujui/' . (int) $id); return; }
        $u = $this->_pendaftar($id);
        if (!$u) { $this->_kartu('&#10060;', 'Pendaftar tidak ditemukan', '<p style="color:#64748b">Akun ini sudah ditolak atau dihapus.</p>'); return; }
        $this->db->where('id', (int) $u['id'])->update('user', ['disetujui' => 2]);
        $this->_kartu('&#9989;', 'Disetujui', $this->_ringkas($u)
            . '<p style="color:#64748b;margin-top:12px">Sekarang sudah bisa daftar wajah, absen, dan mengajukan izin.</p>',
            $this->_tombol_edit($u['id']));
    }

    public function tolak($id = 0)
    {
        if ($this->input->method() !== 'post') { redirect('/user/setujui/' . (int) $id); return; }
        $u = $this->_pendaftar($id);
        if (!$u) { $this->_kartu('&#10060;', 'Pendaftar tidak ditemukan', '<p style="color:#64748b">Akun ini sudah ditolak atau dihapus.</p>'); return; }
        // Hanya pendaftar yang BELUM disetujui -- akun karyawan aktif tidak
        // boleh terhapus karena salah klik tautan lama di WA.
        if ((int) $u['disetujui'] >= 1) {
            $this->_kartu('&#9888;&#65039;', 'Tidak bisa ditolak', '<p style="color:#64748b">Akun ini sudah disetujui. Hapus lewat menu kelola user kalau memang perlu.</p>');
            return;
        }
        $uid = (int) $u['id'];
        $this->db->trans_start();
        // Tabel wajah, perangkat, dan absen tidak dikunci foreign key ke user,
        // jadi tidak ikut terhapus otomatis -- dihapus satu per satu.
        foreach (['user_face_descriptors', 'user_devices', 'attendance_punches', 'attendances'] as $t) {
            $this->db->where('user_id', $uid)->delete($t);
        }
        $fk = $this->db->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
                                WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = 'user'
                                  AND REFERENCED_COLUMN_NAME = 'id'")->result_array();
        foreach ($fk as $r) {
            $this->db->where($r['COLUMN_NAME'], $uid)->delete($r['TABLE_NAME']);
        }
        $this->db->where('id', $uid)->delete('user');
        // Hanya nomor akun yang diingat -- supaya dia masih bisa diberi tahu
        // bahwa pendaftarannya ditolak. Data pribadinya sudah terhapus di atas.
        $this->db->query('INSERT IGNORE INTO akun_ditolak (user_id, ditolak_at) VALUES (?, NOW())', [$uid]);
        $this->db->trans_complete();
        $this->_kartu('&#128465;&#65039;', 'Ditolak dan dihapus', $this->_ringkas($u)
            . '<p style="color:#64748b;margin-top:12px">Akun dan semua datanya sudah dihapus. Kalau dia sedang login, sesinya otomatis berakhir.</p>');
    }

    private function _pendaftar($id)
    {
        return $this->db->select('id, full_name, username, email, disetujui, created_at')
                        ->where('id', (int) $id)->get('user')->row_array();
    }

    private function _ringkas($u)
    {
        $e = function ($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); };
        return '<p style="margin:0;font-weight:600;font-size:1.05rem">' . $e($u['full_name']) . '</p>'
             . '<p style="margin:2px 0 0;color:#64748b">' . $e($u['username']) . ' &middot; ' . $e($u['email']) . '</p>'
             . '<p style="margin:2px 0 0;color:#94a3b8;font-size:.85rem">Mendaftar ' . $e($u['created_at']) . '</p>';
    }

    private function _gaya_tombol($warna)
    {
        return "display:inline-block;margin:16px 4px 0;padding:10px 26px;border:0;border-radius:8px;"
             . "background:$warna;color:#fff;font-weight:600;cursor:pointer;text-decoration:none";
    }

    private function _tombol_edit($id)
    {
        return '<a href="/user/edit_page?id=' . (int) $id . '" style="' . $this->_gaya_tombol('#1F4696') . '">Atur role &amp; divisi</a>';
    }

    private function _kartu($ikon, $judul, $isi, $aksi = '')
    {
        $d = ['user' => $_SESSION['user'], 'title' => $judul];
        $d['content'] = '<div style="max-width:520px;margin:48px auto;background:#fff;border-radius:14px;padding:32px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.08)">'
            . '<div style="font-size:44px">' . $ikon . '</div>'
            . '<h4 style="margin:10px 0 14px;font-weight:700">' . $judul . '</h4>'
            . $isi . '<div>' . $aksi . '</div></div>';
        $this->load->view('TemplateDashboard', $d);
    }

    function hasDuplicates($arr)
    {
        $counts = array_count_values($arr);
        $duplicates = array_filter($counts, function ($count) {
            return $count > 1;
        });

        return count($duplicates) > 0;
    }
    public function index()
    {
        $keyword_category = isset($_GET['keyword_category']) ? $_GET['keyword_category'] : "Nama Lengkap";
        $data['keyword_category'] = $keyword_category;
        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }

        $start_date = isset($_GET['start_date']) && $_GET['start_date'] != "" ? $_GET['start_date'] : DATE("Y-m-01");
        $until_date = isset($_GET['until_date']) && $_GET['until_date'] != "" ? $_GET['until_date'] : DATE("Y-m-d");
        $brand = isset($_GET['brand']) ? $_GET['brand'] : '';

        $data['role'] = $this->mymodel->selectWithQuery("SELECT * FROM role");
        if (in_array($_SESSION['user']['role'], array('1'))) {
            $data['bulk_roles'] = $this->mymodel->selectWithQuery("SELECT id, name, display_name FROM roles WHERE is_active = 1 ORDER BY display_name ASC");
        } else if (in_array($_SESSION['user']['role'], array('2', '7'))) {
            $data['bulk_roles'] = $this->mymodel->selectWithQuery("SELECT id, name, display_name FROM roles WHERE is_active = 1 AND name != 'super_admin' ORDER BY display_name ASC");
        } else {
            $data['bulk_roles'] = [];
        }
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;
        $data['limit'] = $limit;

        $data['title'] = 'User - ' . $this->template->title();

        $qry = "";
        $qry = " 1=1 ";

        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        if ($status) {
            $qry .= " AND status_reach = '$status' ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Lengkap") {
                $qry .= " AND LOWER(full_name) LIKE LOWER('%$keyword%') ";
            } else if ($keyword_category == "Email") {
                $qry .= " AND LOWER(email) LIKE LOWER('%$keyword%') ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND LOWER(desc) LIKE LOWER('%$keyword%') ";
            } else if ($keyword_category == "Role") {
                $qry .= " AND LOWER(role_text) LIKE LOWER('%$keyword%') ";
            }
        }

        $data['user'] = $_SESSION['user'];
        if (in_array($data['user']['role'], array('1'))) {
            $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count
            FROM user
            WHERE $qry
            ");
        } else if (in_array($data['user']['role'], array('2', '7'))) {
            $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count
            FROM user
            WHERE $qry AND role NOT IN ('1','2')
            ");
        }

        $data['page'] = CEIL($query[0]['count'] / $limit);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $item = '';

        $current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/user/' . $this->template->get_param();
        $data['url_1'] = $this->template->get_param_without_status($url);
        $data['url_2'] = $this->template->get_param_without_keyword_category($url);
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("user/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {



        $data['template'] = $this->template;

        $keyword_category = isset($_GET['keyword_category']) ? $_GET['keyword_category'] : "Nama Lengkap";
        $data['keyword_category'] = $keyword_category;
        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }

        $start_date = isset($_GET['start_date']) && $_GET['start_date'] != '' ? $_GET['start_date'] : DATE('Y-m-d');
        $until_date = isset($_GET['until_date']) && $_GET['until_date'] != '' ? $_GET['until_date'] : DATE('Y-m-d');
        $brand = isset($_GET['brand']) ? $_GET['brand'] : '';
        $qry = "";
        $qry = " 1=1 ";

        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }


        $status = isset($_GET['status']) ? $_GET['status'] : '';
        if ($status) {
            $qry .= " AND status_reach = '$status' ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Lengkap") {
                $qry .= " AND LOWER(u.full_name) LIKE LOWER('%$keyword%') ";
            } else if ($keyword_category == "Email") {
                $qry .= " AND LOWER(u.email) LIKE LOWER('%$keyword%') ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND LOWER(u.desc) LIKE LOWER('%$keyword%') ";
            } else if ($keyword_category == "Role") {
                $qry .= " AND LOWER(u.role_text) LIKE LOWER('%$keyword%') ";
            }
        }

        $current_page = isset($_GET['page']) ? $_GET['page'] : 1;

        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $data['user'] = $_SESSION['user'];

        if (in_array($data['user']['role'], array('1'))) {
            $query = $this->mymodel->selectWithQuery("SELECT u.*, up.jenis_kontrak, up.lama_kontrak FROM user u
            LEFT JOIN user_profile up ON u.id = up.user_id
            WHERE $qry
            ORDER BY u.full_name ASC
            LIMIT $offset, $limit
            ");
        } else if (in_array($data['user']['role'], array('2', '7'))) {
            $query = $this->mymodel->selectWithQuery("SELECT u.*, up.jenis_kontrak, up.lama_kontrak FROM user u
            LEFT JOIN user_profile up ON u.id = up.user_id
            WHERE $qry  AND u.role NOT IN ('1','2')
            ORDER BY u.full_name ASC
            LIMIT $offset, $limit
            ");
        }


        $data['data'] = $query;

        $data['start'] = $offset;
        $this->load->view("user/item", $data);
    }

    public function edit()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : '';

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE id = '$id'");

        $data['data'] = $query[0];

        $data['user'] = $_SESSION['user'];
        if (in_array($data['user']['role'], array('1'))) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM role ORDER BY id ASC");
        } else if (in_array($data['user']['role'], array('2', '7'))) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM role WHERE id NOT IN ('1','2') ORDER BY id ASC");
        }

        $data['role'] = $query;

        $this->load->view("user/edit", $data);
    }

    public function edit_page()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : '';

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE id = '$id'");

        $data['data'] = $query[0];
        
        // Get user profile data
        $profile_query = $this->mymodel->selectWithQuery("SELECT * FROM user_profile WHERE user_id = '$id'");
        $data['profile'] = !empty($profile_query) ? $profile_query[0] : array();

        $data['user'] = $_SESSION['user'];
        // Use new RBAC roles table instead of old role table
        if (in_array($data['user']['role'], array('1'))) {
            // Super Admin can assign any role
            $query = $this->mymodel->selectWithQuery("SELECT id, name, display_name FROM roles WHERE is_active = 1 ORDER BY display_name ASC");
        } else if (in_array($data['user']['role'], array('2', '7'))) {
            // Admin and HR can assign most roles (excluding super_admin)
            $query = $this->mymodel->selectWithQuery("SELECT id, name, display_name FROM roles WHERE is_active = 1 AND name != 'super_admin' ORDER BY display_name ASC");
        } else {
            // Other users cannot assign roles
            $query = [];
        }

        $data['role'] = $query;
        
        // Get positions for profile dropdown
        $data['positions'] = $this->mymodel->selectWithQuery("SELECT p.*, ql.name as level_name FROM positions p LEFT JOIN quest_levels ql ON p.level_id = ql.id ORDER BY ql.id ASC, p.name ASC");

        // Get leave balance info for edit form (source of truth: user_profile.leave_balance)
        $data['leave_balance'] = null;
        $data['leave_balance_manual'] = null;
        $data['leave_balance_auto'] = 0;
        $data['leave_balance_used'] = 0;
        $data['leave_balance_accrued'] = 0;
        $data['leave_balance_effective'] = 0;

        $leave_profile = $this->leave_model->get_user_profile($id);
        $is_probation = !empty($leave_profile['is_probation']) && intval($leave_profile['is_probation']) === 1;
        if (!$is_probation) {
            $accrual_start = $this->leave_model->get_leave_accrual_start($leave_profile);
            $data['leave_balance_accrued'] = $this->leave_model->calculate_months_since($accrual_start);
            $data['leave_balance_used'] = $this->leave_model->get_used_leave_days($id, $accrual_start);
            $data['leave_balance_auto'] = max(0, intval($data['leave_balance_accrued']) - intval($data['leave_balance_used']));
        }

        $data['leave_balance_manual'] = (isset($leave_profile['leave_balance']) && $leave_profile['leave_balance'] !== '')
            ? $leave_profile['leave_balance']
            : null;
        $data['leave_balance'] = $data['leave_balance_manual'];
        $data['leave_balance_effective'] = $data['leave_balance_manual'] !== null
            ? intval($data['leave_balance_manual'])
            : intval($data['leave_balance_auto']);

        $data['title'] = 'Edit User - ' . $this->template->title();
        $data['content'] = $this->load->view("user/edit_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function update()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];
        $dt['code'] = strtoupper(isset($dt['username']) ? $dt['username'] : '');

        if (!empty($dt['password'])) {
            $dt['password'] = MD5($dt['password']);
        } else {
            unset($dt['password']);
        }

        $email = $dt['email'];
        $username = $dt['username'];

        $other_user = $this->mymodel->selectWithQuery("SELECT id FROM user
        WHERE username = '$username' AND id != '$id' ");

        if ($other_user) {
            $msg = 'Username sudah digunakan user lain!';
            echo $this->template->alert_danger($msg);
            die;
        }

        // $other_user = $this->mymodel->selectWithQuery("SELECT id FROM user
        // WHERE email = '$email' AND id != '$id' ");

        // if($other_user){
        //     $msg = 'Email sudah digunakan user lain!';
        //     echo $this->template->alert_danger($msg);
        //     die;
        // }

        $role = $dt['role'];

        // Sebutan jabatan yang diketik sendiri dihormati; nama peran
        // hanya dipakai kalau kolomnya dikosongkan. Dua hal ini memang
        // berbeda: 'role' menentukan menu apa yang boleh dibuka, sementara
        // 'role_text' yang muncul di laporan keterlambatan dan notifikasi
        // izin. Menulis "Guest" di pesan WhatsApp untuk seorang Admin ADS
        // membingungkan atasan yang membacanya.
        $isian = trim((string)($dt['role_text'] ?? ''));

        if ($isian !== '') {
            $dt['role_text'] = $isian;
        } else {
            $query = $this->mymodel->selectWithQuery("SELECT display_name FROM roles WHERE id = '$role'");
            if (!empty($query)) {
                $dt['role_text'] = strval($query[0]['display_name']);
            } else {
                $query = $this->mymodel->selectWithQuery("SELECT role FROM role WHERE id = '$role'");
                $dt['role_text'] = !empty($query) ? strval($query[0]['role']) : '';
            }
        }

        if (!empty($_FILES['file']['name'])) {
            $dir  = "./assets/img/user/";
            $config['upload_path']   = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['overwrite']     = TRUE;
            $config['file_name']     = $id;
            $config['max_size']      = 2048;
            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('file')) {
                $error = $this->upload->display_errors();
                echo $this->template->alert_danger($error);
                die;
            } else {
                $file = $this->upload->data();
                $dt['img'] = $file['file_name'];
            }
        }



        if ($this->db->update('user', $dt, array('id' => $id))) {
            // Update user role in RBAC system
            // First, remove existing role assignments
            $this->db->delete('user_roles', array('user_id' => $id));
            
            // Then add the new role assignment
            $role_assignment = array(
                'user_id' => $id,
                'role_id' => $role,
                'assigned_at' => date('Y-m-d H:i:s'),
                'assigned_by' => $user['id']
            );
            $this->db->insert('user_roles', $role_assignment);
            
            // Handle user profile data
            $profile_data = $_POST['profile'] ?? array();
            if (!empty($profile_data)) {
                // Handle KTP photo upload
                if (!empty($_FILES['ktp_photo']['name'])) {
                    $dir = "./assets/img/ktp/";
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $config['upload_path'] = $dir;
                    $config['allowed_types'] = 'jpg|jpeg|png';
                    $config['overwrite'] = TRUE;
                    $config['file_name'] = 'ktp_' . $id . '_' . DATE("Ymdhis");
                    $config['max_size'] = 2048;
                    $this->load->library('upload', $config);
                    if ($this->upload->do_upload('ktp_photo')) {
                        $file = $this->upload->data();
                        $profile_data['ktp_photo'] = $file['file_name'];
                    }
                }
                
                try {
                    // Check if profile exists
                    $existing_profile = $this->mymodel->selectWithQuery("SELECT id FROM user_profile WHERE user_id = '$id'");
                    if (!empty($existing_profile)) {
                        // Update existing profile
                        $this->db->update('user_profile', $profile_data, array('user_id' => $id));
                    } else {
                        // Insert new profile
                        $profile_data['user_id'] = $id;
                        $this->db->insert('user_profile', $profile_data);
                    }
                } catch (Exception $e) {
                    // Handle database errors gracefully
                    $error_message = $e->getMessage();
                    if (strpos($error_message, 'position_id') !== false) {
                        $msg = 'Harap pilih posisi jabatan terlebih dahulu untuk melengkapi profil karyawan.';
                        echo $this->template->alert_danger($msg);
                        return;
                    } else {
                        $msg = 'Terjadi kesalahan saat menyimpan profil. Silakan coba lagi.';
                        echo $this->template->alert_danger($msg);
                        return;
                    }
                }
            }

            // Handle leave balance update (source of truth: user_profile.leave_balance)
            $leave_data = $_POST['leave'] ?? [];
            if (array_key_exists('manual_balance', $leave_data)) {
                $manual_balance = trim($leave_data['manual_balance']);
                $manual_balance = $manual_balance === '' ? null : intval($manual_balance);
                $existing_profile = $this->mymodel->selectWithQuery("SELECT id FROM user_profile WHERE user_id = '$id' LIMIT 1");
                if (!empty($existing_profile)) {
                    $this->db->update('user_profile', ['leave_balance' => $manual_balance], ['user_id' => $id]);
                } else {
                    $this->db->insert('user_profile', [
                        'user_id' => $id,
                        'leave_balance' => $manual_balance
                    ]);
                }
            }
            
            $msg = 'Update data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function create()
    {
        $data['data'] = array();

        $data['user'] = $_SESSION['user'];
        if (in_array($data['user']['role'], array('1'))) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM role ORDER BY id ASC");
        } else if (in_array($data['user']['role'], array('2', '7'))) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM role WHERE id NOT IN ('1','2') ORDER BY id ASC");
        }

        $data['role'] = $query;

        $this->load->view("user/create", $data);
    }

    public function create_page()
    {
        $data['data'] = array();

        $data['user'] = $_SESSION['user'];
        // Use new RBAC roles table instead of old role table
        if (in_array($data['user']['role'], array('1'))) {
            // Super Admin can assign any role
            $query = $this->mymodel->selectWithQuery("SELECT id, name, display_name FROM roles WHERE is_active = 1 ORDER BY display_name ASC");
        } else if (in_array($data['user']['role'], array('2', '7'))) {
            // Admin and HR can assign most roles (excluding super_admin)
            $query = $this->mymodel->selectWithQuery("SELECT id, name, display_name FROM roles WHERE is_active = 1 AND name != 'super_admin' ORDER BY display_name ASC");
        } else {
            // Other users cannot assign roles
            $query = [];
        }

        $data['role'] = $query;
        
        // Get positions for profile dropdown
        $data['positions'] = $this->mymodel->selectWithQuery("SELECT p.*, ql.name as level_name FROM positions p LEFT JOIN quest_levels ql ON p.level_id = ql.id ORDER BY ql.id ASC, p.name ASC");

        $data['title'] = 'Tambah User - ' . $this->template->title();
        $data['content'] = $this->load->view("user/create_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }


    public function store()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];
        $dt['code'] = strtoupper(isset($dt['username']) ? $dt['username'] : '');

        if (!empty($dt['password'])) {
            $dt['password'] = MD5($dt['password']);
        } else {
            unset($dt['password']);
        }
        $email = $dt['email'];
        $username = $dt['username'];

        $other_user = $this->mymodel->selectWithQuery("SELECT id FROM user
        WHERE username = '$username' AND id != '$id' ");

        if ($other_user) {
            $msg = 'Username sudah digunakan user lain!';
            echo $this->template->alert_danger($msg);
            die;
        }

        $role = $dt['role'];

        $query = $this->mymodel->selectWithQuery("SELECT display_name FROM roles WHERE id = '$role'");
        if (!empty($query)) {
            $dt['role_text'] = strval($query[0]['display_name']);
        } else {
            $query = $this->mymodel->selectWithQuery("SELECT role FROM role WHERE id = '$role'");
            $dt['role_text'] = !empty($query) ? strval($query[0]['role']) : '';
        }
        if (!empty($_FILES['file']['name'])) {
            $dir  = "./assets/img/user/";
            $config['upload_path']   = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['overwrite']     = TRUE;
            $config['file_name']     = DATE("Ymdhis");
            $config['max_size']      = 2048;
            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('file')) {
                $error = $this->upload->display_errors();
                echo $this->template->alert_danger($error);
                die;
            } else {
                $file = $this->upload->data();
                $dt['img'] = $file['file_name'];
            }
        }

        if ($this->db->insert('user', $dt)) {
            $user_id = $this->db->insert_id();
            
            // Add user to RBAC system - assign role
            $role_assignment = array(
                'user_id' => $user_id,
                'role_id' => $role,
                'assigned_at' => date('Y-m-d H:i:s'),
                'assigned_by' => $user['id']
            );
            $this->db->insert('user_roles', $role_assignment);
            
            // Handle user profile data
            $profile_data = $_POST['profile'] ?? array();
            if (!empty($profile_data)) {
                $profile_data['user_id'] = $user_id;
                
                // Handle KTP photo upload
                if (!empty($_FILES['ktp_photo']['name'])) {
                    $dir = "./assets/img/ktp/";
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $config['upload_path'] = $dir;
                    $config['allowed_types'] = 'jpg|jpeg|png';
                    $config['overwrite'] = TRUE;
                    $config['file_name'] = 'ktp_' . $user_id . '_' . DATE("Ymdhis");
                    $config['max_size'] = 2048;
                    $this->load->library('upload', $config);
                    if ($this->upload->do_upload('ktp_photo')) {
                        $file = $this->upload->data();
                        $profile_data['ktp_photo'] = $file['file_name'];
                    }
                }
                
                try {
                    $this->db->insert('user_profile', $profile_data);
                } catch (Exception $e) {
                    // Handle database errors gracefully
                    $error_message = $e->getMessage();
                    if (strpos($error_message, 'position_id') !== false) {
                        $msg = 'Harap pilih posisi jabatan terlebih dahulu untuk melengkapi profil karyawan.';
                        echo $this->template->alert_danger($msg);
                        return;
                    } else {
                        $msg = 'Terjadi kesalahan saat menyimpan profil. Silakan coba lagi.';
                        echo $this->template->alert_danger($msg);
                        return;
                    }
                }
            }
            
            $msg = 'Tambah data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function sync()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : '';

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE id = '$id'");

        $data['data'] = $query[0];
        $this->load->view("user/sync", $data);
    }

    public function sync_process()
    {

        $user = $_SESSION['user'];
        $id = $_POST['id'];


        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE id = '$id'");

        $v = $query[0];
        $response = $this->template->get_social_media($v['type'], $v['url']);
        $dt = array();
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];
        if ($response['data']['like'] > 0) {
            $dt['like'] = $response['data']['like'];
            $dt['comment'] = $response['data']['comment'];
            $dt['collect'] = $response['data']['collect'];
            $dt['share'] = $response['data']['share'];
            $dt['view'] = $response['data']['view'];
            if ($v['cost'] > 0 && $dt['view'] > 0) {
                $dt['cpm'] = $v['cost'] / $dt['view'] * 1000;
            }
        }

        $this->db->update('user', $dt, array('id' => $v['id']));

        if ($response['status'] == true) {
            $msg = 'Sync data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            if ($response) {
                $msg = $response['msg'];
            } else {
                $msg = 'Data user belum tersedia!';
            }
            echo $this->template->alert_danger($msg);
        }
    }

    public function remove()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        $data['data']['id'] = $id;
        $this->load->view("user/delete", $data);
    }

    public function delete()
    {
        $id = (int) $this->input->post('id');
        if (!$id) {
            echo $this->template->alert_danger('ID tidak valid.');
            return;
        }

        if (!$this->can_manage_target_user($id)) {
            echo $this->template->alert_danger('Anda tidak memiliki akses untuk menghapus user ini.');
            return;
        }

        // Get user_profile.id before deleting (needed for quest submission FK)
        $profile = $this->mymodel->selectWithQuery("SELECT id FROM user_profile WHERE user_id = '$id' LIMIT 1");
        if (!empty($profile)) {
            $profile_id = (int) $profile[0]['id'];
            $this->db->delete('main_quest_submissions', ['user_profile_id' => $profile_id]);
            $this->db->delete('side_quest_submissions', ['user_profile_id' => $profile_id]);
        }
        // Delete quests & submissions created by this user (created_by is NOT NULL FK)
        $main_quest_ids = $this->mymodel->selectWithQuery("SELECT id FROM main_quests WHERE created_by = $id");
        if (!empty($main_quest_ids)) {
            $mq_ids = implode(',', array_column($main_quest_ids, 'id'));
            $this->db->query("DELETE FROM main_quest_submissions WHERE quest_id IN ($mq_ids)");
        }
        $this->db->query("DELETE FROM main_quests WHERE created_by = $id");
        $side_quest_ids = $this->mymodel->selectWithQuery("SELECT id FROM side_quests WHERE created_by = $id");
        if (!empty($side_quest_ids)) {
            $sq_ids = implode(',', array_column($side_quest_ids, 'id'));
            $this->db->query("DELETE FROM side_quest_submissions WHERE quest_id IN ($sq_ids)");
        }
        $this->db->query("DELETE FROM side_quests WHERE created_by = $id");
        // Delete career_progressions created/updated by this user (created_by is NOT NULL FK)
        $this->db->query("DELETE FROM career_progressions WHERE created_by = $id OR updated_by = $id");
        $this->db->delete('user_profile', ['user_id' => $id]);
        $this->db->delete('user_roles', ['user_id' => $id]);

        $ok = $this->db->delete('user', ['id' => $id]);

        if ($ok) {
            echo $this->template->alert_success('Hapus data berhasil!');
        } else {
            $err = $this->db->error();
            echo $this->template->alert_danger('Hapus data tidak berhasil! '.$err['message']);
        }
    }


    public function action()
    {
        $id_selected_v2 = $_POST['id_selected_v2'];
        $id_selected = $_POST['id_selected'];
        if ($id_selected) {
            $id = explode(',', $id_selected);
        }
        $code = isset($_GET['code']) ? $_GET['code'] : '';
        $data['data']['id'] = $id;
        $data['data']['code'] = $code;
        if ($code == "hapus_data") {
            $data['question'] = "Apakah kamu yakin ingin menghapus data user ini?";
            $data['btn'] = "Hapus Data";
        }
        $this->load->view("user/action", $data);
    }

    public function action_process()
    {
        $list_id = "";
        $code = $_POST['code'];
        $user = $_SESSION['user'];

        $id_selected = $_POST['id_selected'];
        if ($id_selected) {
            $id = explode(',', $id_selected);
        }
        if ($code == "hapus_data") {
            foreach ($id as $k => $v) {
                $list_id .= "'" . $v . "',";
            }

            $list_id = substr($list_id, 0, -1);

            if ($list_id) {
                $dt = array();
                $this->db->delete('user', "id IN ($list_id)");
                $msg = 'Hapus data berhasil!';
                echo $this->template->alert_success($msg);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
        }
    }

    public function bulk_delete()
    {
        $ids = $this->normalize_bulk_ids($this->input->post('ids'));
        if (empty($ids)) {
            echo $this->template->alert_danger('Pilih minimal 1 user.');
            return;
        }

        $manageable_ids = $this->filter_manageable_user_ids($ids);
        if (empty($manageable_ids)) {
            echo $this->template->alert_danger('Tidak ada user yang bisa diproses.');
            return;
        }

        // Get user_profile IDs before deleting (needed for quest submission FK)
        $id_list = implode(',', array_map('intval', $manageable_ids));
        $profiles = $this->mymodel->selectWithQuery("SELECT id FROM user_profile WHERE user_id IN ($id_list)");
        $profile_ids = array_column($profiles, 'id');

        $this->db->trans_start();
        if (!empty($profile_ids)) {
            $this->db->where_in('user_profile_id', $profile_ids)->delete('main_quest_submissions');
            $this->db->where_in('user_profile_id', $profile_ids)->delete('side_quest_submissions');
        }
        // Delete quests & their submissions created by these users (created_by is NOT NULL FK)
        $main_quest_ids = $this->mymodel->selectWithQuery("SELECT id FROM main_quests WHERE created_by IN ($id_list)");
        if (!empty($main_quest_ids)) {
            $mq_ids = implode(',', array_column($main_quest_ids, 'id'));
            $this->db->query("DELETE FROM main_quest_submissions WHERE quest_id IN ($mq_ids)");
        }
        $this->db->query("DELETE FROM main_quests WHERE created_by IN ($id_list)");
        $side_quest_ids = $this->mymodel->selectWithQuery("SELECT id FROM side_quests WHERE created_by IN ($id_list)");
        if (!empty($side_quest_ids)) {
            $sq_ids = implode(',', array_column($side_quest_ids, 'id'));
            $this->db->query("DELETE FROM side_quest_submissions WHERE quest_id IN ($sq_ids)");
        }
        $this->db->query("DELETE FROM side_quests WHERE created_by IN ($id_list)");
        // Delete career_progressions created/updated by these users (created_by is NOT NULL FK)
        $this->db->query("DELETE FROM career_progressions WHERE created_by IN ($id_list) OR updated_by IN ($id_list)");
        $this->db->where_in('user_id', $manageable_ids)->delete('user_profile');
        $this->db->where_in('user_id', $manageable_ids)->delete('user_roles');
        $this->db->where_in('id', $manageable_ids)->delete('user');
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $err = $this->db->error();
            echo $this->template->alert_danger('Bulk hapus gagal! ' . $err['message']);
            return;
        }

        $msg = count($manageable_ids) . ' user berhasil dihapus.';
        if (count($manageable_ids) !== count($ids)) {
            $msg .= ' Sebagian user dilewati karena tidak memiliki akses.';
        }
        echo $this->template->alert_success($msg);
    }

    public function bulk_update_role()
    {
        $ids = $this->normalize_bulk_ids($this->input->post('ids'));
        $role_id = (int) $this->input->post('role_id');
        $current_user = $_SESSION['user'];

        if (empty($ids)) {
            echo $this->template->alert_danger('Pilih minimal 1 user.');
            return;
        }

        if (!$role_id) {
            echo $this->template->alert_danger('Role tujuan wajib dipilih.');
            return;
        }

        if (!$this->can_assign_role_id($role_id)) {
            echo $this->template->alert_danger('Anda tidak memiliki akses untuk assign role tersebut.');
            return;
        }

        $manageable_ids = $this->filter_manageable_user_ids($ids);
        if (empty($manageable_ids)) {
            echo $this->template->alert_danger('Tidak ada user yang bisa diproses.');
            return;
        }

        $role_query = $this->mymodel->selectWithQuery("SELECT id, display_name FROM roles WHERE id = '$role_id' AND is_active = 1 LIMIT 1");
        if (empty($role_query)) {
            echo $this->template->alert_danger('Role tidak ditemukan.');
            return;
        }

        $role_display_name = $role_query[0]['display_name'];

        $this->db->trans_start();
        $this->db->where_in('id', $manageable_ids)->update('user', [
            'role' => $role_id,
            'role_text' => $role_display_name,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $current_user['id'],
        ]);
        $this->db->where_in('user_id', $manageable_ids)->delete('user_roles');

        foreach ($manageable_ids as $user_id) {
            $this->db->insert('user_roles', [
                'user_id' => $user_id,
                'role_id' => $role_id,
                'assigned_at' => date('Y-m-d H:i:s'),
                'assigned_by' => $current_user['id']
            ]);
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $err = $this->db->error();
            echo $this->template->alert_danger('Bulk update role gagal! ' . $err['message']);
            return;
        }

        $msg = count($manageable_ids) . ' user berhasil diubah ke role ' . $role_display_name . '.';
        if (count($manageable_ids) !== count($ids)) {
            $msg .= ' Sebagian user dilewati karena tidak memiliki akses.';
        }
        echo $this->template->alert_success($msg);
    }

    private function normalize_bulk_ids($ids)
    {
        if (is_string($ids) && $ids !== '') {
            $ids = explode(',', $ids);
        }

        if (!is_array($ids)) {
            return [];
        }

        $normalized = array_map('intval', $ids);
        $normalized = array_filter($normalized, function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($normalized));
    }

    private function filter_manageable_user_ids($ids)
    {
        $current_user = $_SESSION['user'];
        $current_role = (string) $current_user['role'];
        $current_user_id = (int) $current_user['id'];
        $ids = $this->normalize_bulk_ids($ids);

        if (empty($ids)) {
            return [];
        }

        $query = $this->db->select('id, role')
            ->from('user')
            ->where_in('id', $ids)
            ->get()
            ->result_array();

        $allowed = [];
        foreach ($query as $row) {
            $target_id = (int) $row['id'];
            $target_role = (string) $row['role'];

            if ($target_id === $current_user_id) {
                continue;
            }

            if ($current_role === '1') {
                $allowed[] = $target_id;
                continue;
            }

            if (in_array($current_role, ['2', '7'], true) && !in_array($target_role, ['1', '2'], true)) {
                $allowed[] = $target_id;
            }
        }

        return $allowed;
    }

    private function can_manage_target_user($id)
    {
        $ids = $this->filter_manageable_user_ids([$id]);
        return !empty($ids);
    }

    private function can_assign_role_id($role_id)
    {
        $current_role = (string) $_SESSION['user']['role'];
        $role_id = (int) $role_id;

        if ($current_role === '1') {
            return true;
        }

        if (in_array($current_role, ['2', '7'], true)) {
            $role_query = $this->mymodel->selectWithQuery("SELECT name FROM roles WHERE id = '$role_id' LIMIT 1");
            if (empty($role_query)) {
                return false;
            }

            return $role_query[0]['name'] !== 'super_admin';
        }

        return false;
    }

    public function detail()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : '';

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE id = '$id'");

        if (empty($query)) {
            redirect(base_url() . 'user');
        }

        $data['data'] = $query[0];
        
        // Get user profile data with position information
        $profile_query = $this->mymodel->selectWithQuery("SELECT up.*, p.name as position_name, ql.name as level_name FROM user_profile up LEFT JOIN positions p ON up.position_id = p.id LEFT JOIN quest_levels ql ON p.level_id = ql.id WHERE up.user_id = '$id'");
        $data['profile'] = !empty($profile_query) ? $profile_query[0] : array();
        $data['leave_balance_effective'] = $this->leave_model->get_leave_balance($id);
        $data['is_probation'] = !empty($data['profile']['is_probation']) && intval($data['profile']['is_probation']) === 1;

        // Get created_by and updated_by user info
        $created_by_id = $data['data']['created_by'];
        $updated_by_id = $data['data']['updated_by'];

        $created_by_query = $this->mymodel->selectWithQuery("SELECT full_name FROM user WHERE id = '$created_by_id'");
        $updated_by_query = $this->mymodel->selectWithQuery("SELECT full_name FROM user WHERE id = '$updated_by_id'");

        $data['created_by'] = !empty($created_by_query) ? $created_by_query[0] : null;
        $data['updated_by'] = !empty($updated_by_query) ? $updated_by_query[0] : null;

        $data['title'] = 'Detail User - ' . $this->template->title();
        $data['content'] = $this->load->view("user/detail_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }
}
