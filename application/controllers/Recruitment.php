<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';


class Recruitment extends BaseController
{
    // Configure BaseController for automatic permission checking
    protected $require_permissions = true;
    protected $show_403_on_deny = true;
    protected $public_methods = []; // All methods require permissions
    
    public function __construct()
    {
        parent::__construct();
        $this->load->library('permission');
        $this->load->library('template');
    }
    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];

        // BaseController automatically checks 'view' permission for index()
        // Pass permission data to view
        $data['can_create'] = $this->permission->check_permission($user_id, 'recruitment', 'create');
        $data['can_edit'] = $this->permission->check_permission($user_id, 'recruitment', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'recruitment', 'delete');

        $keyword_category = $_GET['keyword_category'] ?? "Nama";
        $keyword = $_GET['keyword'] ?? "";
        $status_filter_raw = $_GET['status_filter'] ?? null;
        $status_testcase = $_GET['status_testcase'] ?? "";
        $info_loker_filter = $_GET['info_loker_filter'] ?? null;
        
        // URL is single source of truth for position/info_loker filter (no session persistence to avoid stale state)
        $position_filter = $_GET['position_filter'] ?? "";
        $info_loker_filter = $_GET['info_loker_filter'] ?? "";

        // Handle status_filter with session persistence and default pending
        if (isset($_GET['status_filter'])) {
            $_SESSION['recruitment_status_filter'] = $status_filter_raw;
        } else {
            $status_filter_raw = $_SESSION['recruitment_status_filter'] ?? null;
        }

        if ($status_filter_raw === null) {
            $_SESSION['recruitment_status_filter'] = 'pending';
            $redirect_params = $_GET;
            $redirect_params['status_filter'] = 'pending';
            redirect(base_url() . 'recruitment?' . http_build_query($redirect_params));
        }
        $status_filter = ($status_filter_raw === 'all') ? "" : ($status_filter_raw ?? "");

        $recruitment_status_tabs_all = $this->get_recruitment_status_tabs();
        $recruitment_status_options = $this->get_recruitment_status_options();
        $allowed_tabs = array_keys($recruitment_status_tabs_all);
        if (!isset($_SESSION['recruitment_status_tabs']) || !is_array($_SESSION['recruitment_status_tabs'])) {
            $_SESSION['recruitment_status_tabs'] = $allowed_tabs;
        } else {
            $_SESSION['recruitment_status_tabs'] = array_values(array_filter(
                $_SESSION['recruitment_status_tabs'],
                fn($t) => in_array($t, $allowed_tabs, true)
            ));
            if (empty($_SESSION['recruitment_status_tabs'])) {
                $_SESSION['recruitment_status_tabs'] = $allowed_tabs;
            }
        }
        $_SESSION['recruitment_status_tabs'] = array_values(array_unique(array_merge(
            $_SESSION['recruitment_status_tabs'],
            $allowed_tabs
        )));
        
        $data['keyword_category'] = $keyword_category;
        $data['status_filter'] = $status_filter;
        $data['status_filter_raw'] = $status_filter_raw;
        $data['status_testcase'] = $status_testcase;
        $data['position_filter'] = $position_filter;
        $data['info_loker_filter'] = $info_loker_filter;
        $data['title'] = 'Recruitment Management - ' . $this->template->title();
        $data['status_tabs_all'] = $recruitment_status_tabs_all;
        $data['status_tabs_selected'] = $_SESSION['recruitment_status_tabs'];
        $data['recruitment_status_options'] = $recruitment_status_options;
        $data['chat_templates'] = $this->get_recruitment_chat_templates();

        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Nama") {
                $qry .= " AND nama_lengkap LIKE '%$keyword%'";
            } else if ($keyword_category == "Posisi") {
                $qry .= " AND posisi_dilamar LIKE '%$keyword%'";
            }
        }
        
        if ($position_filter) {
            $qry .= " AND posisi_dilamar = '" . $this->db->escape_str($position_filter) . "'";
        }

        if ($info_loker_filter) {
            $qry .= $this->build_info_loker_condition($info_loker_filter);
        }
        
        if ($status_filter) {
            if ($status_filter == 'rejected') {
                // For rejected filter: show records where status_recruitment is not empty AND status_approval is rejected
                $qry .= " AND status_recruitment IS NOT NULL AND status_recruitment != '' AND status_approval = 'rejected'";
            } elseif ($status_filter == 'belum_sesuai') {
                // For belum_sesuai filter: show records where status_recruitment is rejected AND status_approval is NULL
                $qry .= " AND status_recruitment = 'rejected' AND status_approval IS NULL";
            } else {
                // For other filters: only show pending/approved status_approval
                $status_filter_sql = $this->db->escape_str($status_filter);
                $qry .= " AND status_recruitment = '$status_filter_sql'";
                $qry .= " AND (status_approval = 'pending' OR status_approval = 'approved' OR status_approval IS NULL)";
            }
        } else {
            // For "Semua" tab: exclude rejected records and belum_sesuai
            $qry .= " AND (status_approval != 'rejected' OR status_approval IS NULL)";
            $qry .= " AND (status_recruitment != 'rejected' OR status_recruitment IS NULL)";
        }

        if ($status_testcase == "shared_testcase_1" || $status_testcase == "done_testcase_1") {
            $qry .= " AND status_testcase = '$status_testcase'";
        } else if ($status_testcase == "pending") {
            $qry .= " AND (status_testcase IS NULL OR status_testcase = '')";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count
            FROM job_applications
            WHERE $qry");
        $total_count = (int)$query[0]['count'];
        $data['page'] = CEIL($total_count / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($total_count) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        // Redirect to last valid page if requested page exceeds total pages
        $max_page = max(1, (int)$data['page']);
        if ($current_page > $max_page) {
            $redirect_params = $_GET;
            $redirect_params['page'] = $max_page;
            redirect(base_url() . 'recruitment?' . http_build_query($redirect_params));
        }

        $data['positions'] = $this->mymodel->selectWithQuery("SELECT DISTINCT posisi_dilamar FROM job_applications WHERE posisi_dilamar IS NOT NULL ORDER BY posisi_dilamar ASC;");
        $data['info_lokers'] = $this->db->table_exists('recruitment_info_loker')
            ? $this->mymodel->selectWithQuery("SELECT id, info_loker_name FROM recruitment_info_loker ORDER BY info_loker_name ASC;")
            : [];

        $url = base_url() . '/recruitment/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("recruitment/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    /**
     * Dashboard statistik recruitment (semua angka diambil dari tabel job_applications).
     */
    public function dashboard()
    {
        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Recruitment Dashboard - ' . $this->template->title();

        // ---- KPI cards ----
        $data['kpi'] = [
            'total'      => $this->dash_count("SELECT COUNT(id) AS c FROM job_applications"),
            'in_process' => $this->dash_count("SELECT COUNT(id) AS c FROM job_applications WHERE status_recruitment IN ('testcase_1','interview_hr','interview_user')"),
            'selected'   => $this->dash_count("SELECT COUNT(id) AS c FROM job_applications WHERE status_recruitment = 'selected'"),
            'rejected'   => $this->dash_count("SELECT COUNT(id) AS c FROM job_applications WHERE status_recruitment = 'rejected' OR status_approval = 'rejected'"),
            'this_month' => $this->dash_count("SELECT COUNT(id) AS c FROM job_applications WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())"),
        ];

        // ---- Distribusi per status_recruitment ----
        $data['by_status'] = $this->mymodel->selectWithQuery(
            "SELECT COALESCE(NULLIF(status_recruitment, ''), 'pending') AS label, COUNT(id) AS total
             FROM job_applications GROUP BY label ORDER BY total DESC"
        );

        // ---- Posisi paling banyak dilamar (top 10) ----
        $data['by_position'] = $this->mymodel->selectWithQuery(
            "SELECT COALESCE(NULLIF(posisi_dilamar, ''), '(Tidak diisi)') AS label, COUNT(id) AS total
             FROM job_applications GROUP BY label ORDER BY total DESC LIMIT 10"
        );

        // ---- Sumber info loker (top 10) ----
        $data['by_source'] = $this->mymodel->selectWithQuery(
            "SELECT COALESCE(NULLIF(sumber_info_loker, ''), '(Tidak diisi)') AS label, COUNT(id) AS total
             FROM job_applications GROUP BY label ORDER BY total DESC LIMIT 10"
        );

        // ---- Tren lamaran masuk per bulan (12 bulan terakhir) ----
        $data['by_month'] = $this->mymodel->selectWithQuery(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS label, COUNT(id) AS total
             FROM job_applications
             WHERE created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
             GROUP BY label ORDER BY label ASC"
        );

        // ---- Demografi: jenis kelamin ----
        $data['by_gender'] = $this->mymodel->selectWithQuery(
            "SELECT COALESCE(NULLIF(jenis_kelamin, ''), '(Tidak diisi)') AS label, COUNT(id) AS total
             FROM job_applications GROUP BY label ORDER BY total DESC"
        );

        // ---- Preferensi kerja: WFO vs WFH ----
        $data['by_wfo'] = $this->mymodel->selectWithQuery(
            "SELECT CASE WHEN is_wfo = 1 THEN 'WFO' ELSE 'WFH' END AS label, COUNT(id) AS total
             FROM job_applications GROUP BY label"
        );

        $data['content'] = $this->load->view("recruitment/dashboard", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    /** Helper kecil: ambil satu angka COUNT dari query. */
    private function dash_count($sql)
    {
        $result = $this->mymodel->selectWithQuery($sql);
        return !empty($result) ? (int) $result[0]['c'] : 0;
    }

    public function item()
    {
        $data['template'] = $this->template;
        $keyword_category = $_GET['keyword_category'] ?? "Nama";
        $keyword = $_GET['keyword'] ?? "";
        $status_filter = $_GET['status_filter'] ?? null;
        $status_testcase = $_GET['status_testcase'] ?? "";

        // URL is single source of truth (no session fallback)
        $position_filter = $_GET['position_filter'] ?? "";
        $info_loker_filter = $_GET['info_loker_filter'] ?? "";

        $status_filter = isset($_GET['status_filter']) ? $status_filter : ($_SESSION['recruitment_status_filter'] ?? "");
        if ($status_filter === 'all') {
            $status_filter = "";
        }
        $data['status_filter'] = $status_filter;
        $data['recruitment_status_options'] = $this->get_recruitment_status_options();

        $qry = "1=1";

        if ($keyword) {
            if ($keyword_category == "Nama") {
                $qry .= " AND nama_lengkap LIKE '%$keyword%'";
            } else if ($keyword_category == "Posisi") {
                $qry .= " AND posisi_dilamar LIKE '%$keyword%'";
            }
        }
        
        if ($position_filter) {
            $qry .= " AND posisi_dilamar = '" . $this->db->escape_str($position_filter) . "'";
        }

        if ($info_loker_filter) {
            $qry .= $this->build_info_loker_condition($info_loker_filter);
        }

        if ($status_filter) {
            if ($status_filter == 'rejected') {
                // For rejected filter: show records where status_recruitment is not empty AND status_approval is rejected
                $qry .= " AND status_recruitment IS NOT NULL AND status_recruitment != '' AND status_approval = 'rejected'";
            } elseif ($status_filter == 'belum_sesuai') {
                // For belum_sesuai filter: show records where status_recruitment is rejected AND status_approval is NULL
                $qry .= " AND status_recruitment = 'rejected' AND status_approval IS NULL";
            } else {
                // For other filters: only show pending/approved status_approval
                $status_filter_sql = $this->db->escape_str($status_filter);
                $qry .= " AND status_recruitment = '$status_filter_sql'";
                $qry .= " AND (status_approval = 'pending' OR status_approval = 'approved' OR status_approval IS NULL)";
            }
        } else {
            // For "Semua" tab: exclude rejected records and belum_sesuai
            $qry .= " AND (status_approval != 'rejected' OR status_approval IS NULL)";
            $qry .= " AND (status_recruitment != 'rejected' OR status_recruitment IS NULL)";
        }

        if ($status_testcase == "shared_testcase_1" || $status_testcase == "done_testcase_1") {
            $qry .= " AND status_testcase = '$status_testcase'";
        } else if ($status_testcase == "pending") {
            $qry .= " AND (status_testcase IS NULL OR status_testcase = '')";
        }

        $per_page_options = [5, 10, 20, 30, 50, 100, 500];
        $limit = $_GET['limit'] ?? 10;
        if (!in_array($limit, $per_page_options)) {
            $limit = 10;
        }
        $data['limit'] = $limit;
        $data['per_page_options'] = $per_page_options;

        $current_page = $_GET['page'] ?? 1;
        $offset = ($current_page > 1) ? ($current_page - 1) * $limit : 0;

        $data['page'] = ceil($total_data / $limit);
        $data['current_page'] = $current_page;

        $query = $this->mymodel->selectWithQuery("
            SELECT created_at, nama_lengkap, posisi_dilamar, status_recruitment, 
                status_approval, status_testcase, level, id, notes_hr, tag, jenis_kelamin,
                no_handphone, pengalaman_terakhir, perusahaan_terakhir, posisi_terakhir, lama_posisi_terakhir,
                is_wfo, ekspektasi_sallary, domisili, usia, sumber_info_loker
            FROM job_applications 
            WHERE $qry
            ORDER BY created_at DESC 
            LIMIT $offset, $limit
        ");
        $data['data'] = $query;

        $data['start'] = $offset;
        $data['end'] = min($offset + $limit, $total_data);

        $data['param'] = $this->template->get_param();
        $this->load->view("recruitment/item", $data);
    }


    public function detail()
    {
        $id = $_GET['id'];
        $interview_type = $_GET['type'] ?? '';

        $qry = "1=1";
        if (!empty($interview_type)) {
            $qry = "AND i.interview_status = '$interview_type'";
        } 
        $query = $this->mymodel->selectWithQuery("SELECT j.*, i.id as id_interview
        FROM job_applications j
        LEFT JOIN interview i ON j.id = i.id_job_applications
        WHERE j.id = '$id' AND $qry");
        
        if (empty($query)) {
            redirect(base_url() . 'recruitment');
        }
        
        $data['data'] = $query[0];
        
        // Check if tag is "Already Apply" and get history
        $data['history'] = array();
        if (isset($data['data']['tag']) && $data['data']['tag'] == 'Already Apply') {
            // Get the reference column from status_logs to match with
            $nama_lengkap = $data['data']['nama_lengkap'];
            
            // Query to get history based on nama_lengkap
            $history_query = $this->mymodel->selectWithQuery("SELECT j.*, 
                DATE_FORMAT(j.created_at, '%d %M %Y %H:%i') as formatted_created_at,
                DATE_FORMAT(j.updated_at, '%d %M %Y %H:%i') as formatted_updated_at
            FROM job_applications j 
            WHERE j.nama_lengkap = '$nama_lengkap' 
            AND j.id != '$id' 
            ORDER BY j.created_at DESC");
            
            if (!empty($history_query)) {
                $data['history'] = $history_query;
            }
        }
        
        $data['title'] = 'Detail Recruitment - ' . $this->template->title();
        $data['content'] = $this->load->view("recruitment/detail_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function get_detail_data()
    {
        header('Content-Type: application/json');
        
        $data['user'] = $_SESSION['user'];
        
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'ID is required']);
            return;
        }
        
        try {
            $query = $this->mymodel->selectWithQuery("SELECT j.*, i.id as id_interview
                FROM job_applications j
                LEFT JOIN interview i ON j.id = i.id_job_applications
                WHERE j.id = '$id'");
            
            if (empty($query)) {
                echo json_encode(['status' => 'error', 'message' => 'Data not found']);
                return;
            }
            
            $main_data = $query[0];
            
            $history = array();
            if (isset($main_data['tag']) && $main_data['tag'] == 'Already Apply') {
                $nama_lengkap = $main_data['nama_lengkap'];
                $no_hp = $main_data['no_hp'];
                $email = $main_data['email'];
                
                $history_query = $this->mymodel->selectWithQuery("SELECT j.*,
                    DATE_FORMAT(j.created_at, '%d %M %Y %H:%i') as formatted_created_at,
                    DATE_FORMAT(j.updated_at, '%d %M %Y %H:%i') as formatted_updated_at
                    FROM job_applications j
                    WHERE j.nama_lengkap = '$nama_lengkap'
                    OR j.no_handphone = '$no_hp'
                    OR j.email = '$email'
                    AND j.id != '$id'
                    ORDER BY j.created_at DESC");
                
                if (!empty($history_query)) {
                    $history = $history_query;
                }
            }
            
            echo json_encode([
                'status' => 'success',
                'data' => $main_data,
                'history' => $history
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function update_status()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $user = $_SESSION['user'];

        $id = $this->input->post('id');
        $field = $this->input->post('field');
        $value = $this->input->post('value');
        $link_testcase = $this->input->post('link_testcase');
        $force_approval_null = $this->input->post('force_approval_null');

        if (empty($id) || empty($field)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            return;
        }

        $allowed_fields = ['status_recruitment', 'status_approval', 'status_testcase'];
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid field']);
            return;
        }

        try {
            // Prepare update data
            $update_data = [$field => $value];
            $auto_selected = false;
            $auto_next_status = null;
            $applicant_name = null;

            if ($field === 'status_testcase' && $value === 'done_testcase_1' && !empty($link_testcase)) {
                $update_data['link_testcase'] = $link_testcase;
            }

            if ($field === 'status_approval' && $value === 'approved') {
                $current = $this->db->select('status_recruitment, nama_lengkap')
                    ->from('job_applications')
                    ->where('id', $id)
                    ->get()
                    ->row_array();
                $current_status = $current['status_recruitment'] ?? null;
                $applicant_name = $current['nama_lengkap'] ?? null;
                if ($current_status === 'interview_hr') {
                    $update_data['status_recruitment'] = 'interview_user';
                    $update_data['status_approval'] = null;
                    $auto_next_status = 'interview_user';
                } else if ($current_status === 'interview_user') {
                    $update_data['status_recruitment'] = 'selected';
                    $auto_selected = true;
                    $auto_next_status = 'selected';
                }
            }

            if ($field === 'status_recruitment') {
                $status_options = $this->get_recruitment_status_options();
                $requires_approval = !isset($status_options[$value]) || !empty($status_options[$value]['requires_approval']);
                if ($force_approval_null && in_array($value, ['interview_hr', 'rejected'], true)) {
                    $update_data['status_approval'] = null;
                } else if (!$requires_approval) {
                    $update_data['status_approval'] = null;
                } else {
                    $update_data['status_approval'] = 'pending';
                }
            }

            $this->db->where('id', $id);
            $this->db->update('job_applications', $update_data);

            $log_data = [
                'user_id' => $user['id'],
                'activity' => 'Update recruitment status',
                'details' => json_encode([
                    'applicant_id' => $id,
                    'field' => $field,
                    'new_value' => $value
                ]),
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->db->insert('activity_logs', $log_data);

            // Use the actual resulting recruitment status so this also runs when
            // approval auto-advances the status (field = status_approval) instead of
            // only on a direct status_recruitment change.
            $effective_recruitment_status = $update_data['status_recruitment'] ?? null;
            if (!empty($effective_recruitment_status) && in_array(strtolower($effective_recruitment_status), ['interview_user', 'interview_hr'])) {
                $this->db->where('id_job_applications', $id);
                $this->db->where('interview_status', $effective_recruitment_status);
                $exists = $this->db->get('interview')->row();

                if (!$exists) {
                    $interview_data = [
                        'id_job_applications' => $id,
                        'interview_datetime' => null,
                        'interview_status' => $effective_recruitment_status,
                        'notes' => null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => null,
                    ];
                    $this->db->insert('interview', $interview_data);
                }
            }


            echo json_encode([
                'status' => 'success',
                'message' => 'Status updated successfully',
                'auto_selected' => $auto_selected,
                'auto_next_status' => $auto_next_status,
                'applicant_name' => $applicant_name
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function update_status_tabs()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $tabs = $this->input->post('tabs');
        if (is_string($tabs)) {
            $tabs = array_filter(array_map('trim', explode(',', $tabs)));
        }
        if (!is_array($tabs)) {
            $tabs = [];
        }

        $allowed_tabs = array_keys($this->get_recruitment_status_tabs());
        $tabs = array_values(array_filter($tabs, fn($t) => in_array($t, $allowed_tabs, true)));
        if (empty($tabs)) {
            $tabs = $allowed_tabs;
        }

        $_SESSION['recruitment_status_tabs'] = $tabs;

        echo json_encode(['status' => 'success', 'tabs' => $tabs]);
    }


    public function update_notes()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $user = $_SESSION['user'];

        $id = $this->input->post('id');
        $notes = $this->input->post('notes');

        try {
            $this->db->where('id', $id);
            $result = $this->db->update('job_applications', ['notes_hr' => $notes]);

            if ($this->db->affected_rows() >= 0) {
                echo json_encode(['status' => 'success', 'message' => 'Notes updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No changes made or record not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function get_positions()
    {
        $positions = $this->db->get('recruitment_positions')->result();
        
        $html = '';
        foreach ($positions as $position) {
            $html .= '
                <div class="p-2 bg-primary text-white rounded-2 d-flex align-items-center position-item" data-id="'.$position->id.'">
                    <span class="me-2">'.htmlspecialchars($position->position_name).'</span>
                    <a href="#" class="text-white lh-1 delete-position" 
                    style="text-decoration: none; opacity: 0.7;">
                        <i class="bi bi-x-circle" style="font-size: 0.9rem;"></i>
                    </a>
                </div>
            ';
        }
        
        echo $html;
    }

    public function save_position()
    {
        $position_name = $this->input->post('position_name');
        
        if (!empty($position_name)) {
            $data = [
                'position_name' => $position_name,
            ];
            
            $this->db->insert('recruitment_positions', $data);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Posisi berhasil ditambahkan',
                'data' => [
                    'id' => $this->db->insert_id(),
                    'position_name' => $position_name
                ]
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Nama posisi tidak boleh kosong'
            ]);
        }
    }

    public function delete_position($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('recruitment_positions');
        
        if ($this->db->affected_rows() > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Posisi berhasil dihapus'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal menghapus posisi'
            ]);
        }
    }

    public function get_info_lokers()
    {
        $info_lokers = $this->db->order_by('info_loker_name', 'ASC')->get('recruitment_info_loker')->result();

        $html = '';
        foreach ($info_lokers as $info_loker) {
            $html .= '
                <div class="p-2 bg-primary text-white rounded-2 d-flex align-items-center info-loker-item" data-id="'.$info_loker->id.'">
                    <span class="me-2">'.htmlspecialchars($info_loker->info_loker_name, ENT_QUOTES, 'UTF-8').'</span>
                    <a href="#" class="text-white lh-1 delete-info-loker"
                    style="text-decoration: none; opacity: 0.7;">
                        <i class="bi bi-x-circle" style="font-size: 0.9rem;"></i>
                    </a>
                </div>
            ';
        }

        echo $html;
    }

    public function save_info_loker()
    {
        $info_loker_name = trim((string) $this->input->post('info_loker_name'));

        if ($info_loker_name === '') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Nama sumber info loker tidak boleh kosong'
            ]);
            return;
        }

        $info_loker_name_sql = $this->db->escape_str(strtolower($info_loker_name));
        $exists = $this->mymodel->selectWithQuery("
            SELECT id
            FROM recruitment_info_loker
            WHERE LOWER(info_loker_name) = '$info_loker_name_sql'
            LIMIT 1
        ");

        if (!empty($exists)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Sumber info loker sudah ada'
            ]);
            return;
        }

        $data = [
            'info_loker_name' => $info_loker_name,
        ];

        $this->db->insert('recruitment_info_loker', $data);

        echo json_encode([
            'status' => 'success',
            'message' => 'Sumber info loker berhasil ditambahkan',
            'data' => [
                'id' => $this->db->insert_id(),
                'info_loker_name' => $info_loker_name
            ]
        ]);
    }

    public function delete_info_loker($id)
    {
        $this->db->where('id', (int) $id);
        $this->db->delete('recruitment_info_loker');

        if ($this->db->affected_rows() > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Sumber info loker berhasil dihapus'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal menghapus sumber info loker'
            ]);
        }
    }

    public function get_recruitment_statuses()
    {
        if (!$this->db->table_exists('recruitment_statuses')) {
            echo '<div class="text-muted small">Tabel recruitment_statuses belum tersedia. Jalankan SQL create table terlebih dahulu.</div>';
            return;
        }

        $statuses = $this->db
            ->order_by('sort_order', 'ASC')
            ->order_by('status_name', 'ASC')
            ->get_where('recruitment_statuses', ['is_active' => 1])
            ->result();

        $html = '';
        foreach ($statuses as $status) {
            $html .= '
                <div class="p-2 bg-primary text-white rounded-2 d-flex align-items-center recruitment-status-item" data-id="'.$status->id.'">
                    <span class="me-2">'.htmlspecialchars($status->status_name, ENT_QUOTES, 'UTF-8').' <small class="opacity-75">('.htmlspecialchars($status->short_label, ENT_QUOTES, 'UTF-8').')</small></span>
                    <a href="#" class="text-white lh-1 delete-recruitment-status"
                    style="text-decoration: none; opacity: 0.7;">
                        <i class="bi bi-x-circle" style="font-size: 0.9rem;"></i>
                    </a>
                </div>
            ';
        }

        echo $html !== '' ? $html : '<div class="text-muted small">Belum ada status tambahan.</div>';
    }

    public function save_recruitment_status()
    {
        if (!$this->db->table_exists('recruitment_statuses')) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tabel recruitment_statuses belum tersedia. Jalankan SQL create table terlebih dahulu.'
            ]);
            return;
        }

        $status_name = trim((string) $this->input->post('status_name'));
        $short_label = trim((string) $this->input->post('short_label'));

        if ($status_name === '') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Nama status tidak boleh kosong'
            ]);
            return;
        }

        if ($short_label === '') {
            $short_label = $this->build_short_status_label($status_name);
        }

        $status_key = $this->build_status_key($status_name);
        $base_statuses = $this->get_base_recruitment_status_options();
        if (isset($base_statuses[$status_key]) || in_array($status_key, ['all', 'belum_sesuai'], true)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Status ini sudah termasuk status bawaan'
            ]);
            return;
        }

        $exists = $this->db
            ->where('status_key', $status_key)
            ->get('recruitment_statuses')
            ->row_array();

        if ($exists) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Status recruitment sudah ada'
            ]);
            return;
        }

        $max_order = $this->db
            ->select('MAX(sort_order) AS max_order')
            ->get('recruitment_statuses')
            ->row_array();
        $sort_order = ((int) ($max_order['max_order'] ?? 100)) + 10;
        $now = date('Y-m-d H:i:s');

        $this->db->insert('recruitment_statuses', [
            'status_key' => $status_key,
            'status_name' => $status_name,
            'short_label' => $short_label,
            'sort_order' => $sort_order,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now
        ]);

        unset($_SESSION['recruitment_status_tabs']);

        echo json_encode([
            'status' => 'success',
            'message' => 'Status recruitment berhasil ditambahkan',
            'data' => [
                'id' => $this->db->insert_id(),
                'status_key' => $status_key,
                'status_name' => $status_name,
                'short_label' => $short_label
            ]
        ]);
    }

    public function delete_recruitment_status($id)
    {
        if (!$this->db->table_exists('recruitment_statuses')) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tabel recruitment_statuses belum tersedia'
            ]);
            return;
        }

        $this->db->where('id', (int) $id);
        $this->db->delete('recruitment_statuses');
        unset($_SESSION['recruitment_status_tabs']);

        if ($this->db->affected_rows() > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Status recruitment berhasil dihapus'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal menghapus status recruitment'
            ]);
        }
    }

    public function get_chat_templates()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        echo json_encode([
            'status' => 'success',
            'data' => $this->get_recruitment_chat_templates()
        ]);
    }

    public function save_chat_templates()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $user = $_SESSION['user'] ?? [];
        $user_id = $user['id'] ?? null;

        $rejected = trim((string) $this->input->post('rejected_template'));
        $interview_hr = trim((string) $this->input->post('interview_hr_template'));

        if ($rejected === '' || $interview_hr === '') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Format chat tidak boleh kosong.'
            ]);
            return;
        }

        if (!$this->db->table_exists('recruitment_chat_templates')) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tabel recruitment_chat_templates belum tersedia. Jalankan SQL setup terlebih dahulu.'
            ]);
            return;
        }

        try {
            $this->upsert_recruitment_chat_template('rejected', $rejected, $user_id);
            $this->upsert_recruitment_chat_template('interview_hr', $interview_hr, $user_id);

            echo json_encode([
                'status' => 'success',
                'message' => 'Format chat berhasil disimpan.',
                'data' => $this->get_recruitment_chat_templates()
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal menyimpan format chat: ' . $e->getMessage()
            ]);
        }
    }

    public function get_message_templates()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        echo json_encode([
            'status' => 'success',
            'data' => $this->get_recruitment_message_templates()
        ]);
    }

    public function save_message_template()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!$this->db->table_exists('recruitment_chat_templates')) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tabel recruitment_chat_templates belum tersedia.'
            ]);
            return;
        }

        $user = $_SESSION['user'] ?? [];
        $user_id = $user['id'] ?? null;
        $template_key = trim((string) $this->input->post('template_key'));
        $template_name = trim((string) $this->input->post('template_name'));
        $template_text = trim((string) $this->input->post('template_text'));

        if ($template_text === '') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Isi template tidak boleh kosong.'
            ]);
            return;
        }

        if ($template_key === '') {
            if ($template_name === '') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Nama template tidak boleh kosong.'
                ]);
                return;
            }
            $template_key = $this->build_status_key($template_name);
        }

        if ($template_key === '') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Key template tidak valid.'
            ]);
            return;
        }

        $existing = $this->db->get_where('recruitment_chat_templates', ['template_key' => $template_key])->row_array();
        if (!$existing && $this->input->post('template_key')) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Template tidak ditemukan.'
            ]);
            return;
        }

        try {
            $this->upsert_recruitment_chat_template($template_key, $template_text, $user_id);
            echo json_encode([
                'status' => 'success',
                'message' => 'Template pesan berhasil disimpan.',
                'data' => $this->get_recruitment_message_templates()
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal menyimpan template: ' . $e->getMessage()
            ]);
        }
    }

    public function delete_message_template()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!$this->db->table_exists('recruitment_chat_templates')) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tabel recruitment_chat_templates belum tersedia.'
            ]);
            return;
        }

        $template_key = trim((string) $this->input->post('template_key'));
        if (in_array($template_key, ['interview_hr', 'rejected'], true)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Template bawaan tidak bisa dihapus.'
            ]);
            return;
        }

        $this->db->where('template_key', $template_key);
        $this->db->delete('recruitment_chat_templates');

        echo json_encode([
            'status' => 'success',
            'message' => 'Template pesan berhasil dihapus.',
            'data' => $this->get_recruitment_message_templates()
        ]);
    }
    
    public function get_status_counts()
    {
        header('Content-Type: application/json');
        
        try {
            $counts = [];
            
            // Get position filter from URL (single source of truth)
            $position_filter = $_GET['position_filter'] ?? "";
            $position_condition = "";
            if (!empty($position_filter)) {
                $position_filter_escaped = $this->db->escape_str($position_filter);
                $position_condition = " AND posisi_dilamar = '$position_filter_escaped'";
            }

            $info_loker_filter = $_GET['info_loker_filter'] ?? "";
            $info_loker_condition = "";
            if (!empty($info_loker_filter)) {
                $info_loker_condition = $this->build_info_loker_condition($info_loker_filter);
            }
            
            // Get all count (exclude rejected records)
            $all_query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM job_applications WHERE (status_approval != 'rejected' OR status_approval IS NULL) AND (status_recruitment != 'rejected' OR status_recruitment IS NULL)" . $position_condition . $info_loker_condition);
            $counts['all'] = $all_query[0]['count'] ?? 0;
            
            // Get count for each real recruitment status, including additional statuses.
            $statuses = array_keys($this->get_recruitment_status_options());
            
            foreach ($statuses as $status) {
                // Only count records with pending/approved status_approval
                $status_sql = $this->db->escape_str($status);
                $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM job_applications WHERE status_recruitment = '$status_sql' AND (status_approval = 'pending' OR status_approval = 'approved' OR status_approval IS NULL)" . $position_condition . $info_loker_condition);
                $counts[$status] = $query[0]['count'] ?? 0;
            }
            
            // Get rejected count (status_recruitment is not empty AND status_approval is rejected)
            $rejected_query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM job_applications WHERE status_recruitment IS NOT NULL AND status_recruitment != '' AND status_approval = 'rejected'" . $position_condition . $info_loker_condition);
            $counts['rejected'] = $rejected_query[0]['count'] ?? 0;
            
            // Get belum_sesuai count (status_recruitment is rejected AND status_approval is NULL)
            $belum_sesuai_query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM job_applications WHERE status_recruitment = 'rejected' AND status_approval IS NULL" . $position_condition . $info_loker_condition);
            $counts['belum_sesuai'] = $belum_sesuai_query[0]['count'] ?? 0;
            
            echo json_encode([
                'status' => 'success',
                'data' => $counts
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    private function build_info_loker_condition($info_loker_filter)
    {
        $filter = strtolower(trim($info_loker_filter));
        if ($filter === 'job_seeker') {
            return " AND (LOWER(sumber_info_loker) LIKE '%glint%'"
                . " OR LOWER(sumber_info_loker) LIKE '%linkedin%'"
                . " OR LOWER(sumber_info_loker) LIKE '%Linkedln%'"
                . " OR LOWER(sumber_info_loker) LIKE '%lindkedin%'"
                . " OR LOWER(sumber_info_loker) LIKE '%dealls%'"
                . " OR LOWER(sumber_info_loker) LIKE '%glins%'"
                . " OR LOWER(sumber_info_loker) LIKE '%jobstreet%'"
                . " OR LOWER(sumber_info_loker) LIKE '%kitalulus%'"
                . " OR LOWER(sumber_info_loker) LIKE '%indeed%'"
                . " OR LOWER(sumber_info_loker) LIKE '%pintarnya%')";
        }

        if ($filter === 'social_media') {
            return " AND (LOWER(sumber_info_loker) LIKE '%ig%'"
                . " OR LOWER(sumber_info_loker) LIKE '%instagram%'"
                . " OR LOWER(sumber_info_loker) LIKE '%tiktok%'"
                . " OR LOWER(sumber_info_loker) LIKE '%loker%'"
                . " OR LOWER(sumber_info_loker) LIKE '%telegram%'"
                . " OR LOWER(sumber_info_loker) LIKE '%twitter%')";
        }

        if ($filter === 'lainnya') {
            return " AND (sumber_info_loker IS NOT NULL AND sumber_info_loker != '')"
                . " AND (LOWER(sumber_info_loker) NOT LIKE '%glint%'"
                . " AND LOWER(sumber_info_loker) NOT LIKE '%linkedin%'"
                . " AND LOWER(sumber_info_loker) NOT LIKE '%lindkedin%'"
                . " AND LOWER(sumber_info_loker) NOT LIKE '%dealls%'"
                . " AND LOWER(sumber_info_loker) NOT LIKE '%jobstreet%'"
                . " AND LOWER(sumber_info_loker) NOT LIKE '%kitalulus%'"
                . " AND LOWER(sumber_info_loker) NOT LIKE '%indeed%'"
                . " AND LOWER(sumber_info_loker) NOT LIKE '%pintarnya%'"
                . " AND LOWER(sumber_info_loker) NOT LIKE '%ig%'"
                . " AND LOWER(sumber_info_loker) NOT LIKE '%instagram%'"
                . " AND LOWER(sumber_info_loker) NOT LIKE '%tiktok%'"
                . " AND LOWER(sumber_info_loker) NOT LIKE '%loker%'"
                . " AND LOWER(sumber_info_loker) NOT LIKE '%telegram%'"
                . " AND LOWER(sumber_info_loker) NOT LIKE '%twitter%')";
        }

        return " AND sumber_info_loker = '" . $this->db->escape_str($info_loker_filter) . "'";
    }

    private function get_base_recruitment_status_options()
    {
        return [
            'pending' => [
                'label' => 'Pending',
                'short_label' => 'Pending',
                'badge' => 'badge-pending',
                'color' => '#faad14',
                'badge_bg' => '#fffbe6',
                'text_color' => '#7a5900',
                'sort_order' => 10,
                'requires_approval' => false,
            ],
            'testcase_1' => [
                'label' => 'Testcase 1',
                'short_label' => 'TC 1',
                'badge' => 'badge-testcase_1',
                'color' => '#722ed1',
                'badge_bg' => '#f9f0ff',
                'text_color' => '#4b0082',
                'sort_order' => 20,
                'requires_approval' => true,
            ],
            'interview_hr' => [
                'label' => 'Interview HR',
                'short_label' => 'Interview HR',
                'badge' => 'badge-interview_hr',
                'color' => '#fa8c16',
                'badge_bg' => '#fff7e6',
                'text_color' => '#7a5900',
                'sort_order' => 30,
                'requires_approval' => true,
            ],
            'interview_user' => [
                'label' => 'Interview User',
                'short_label' => 'Interview User',
                'badge' => 'badge-interview_user',
                'color' => '#1890ff',
                'badge_bg' => '#e6f7ff',
                'text_color' => '#0d6efd',
                'sort_order' => 40,
                'requires_approval' => true,
            ],
            'selected' => [
                'label' => 'Selected',
                'short_label' => 'Selected',
                'badge' => 'badge-selected',
                'color' => '#52c41a',
                'badge_bg' => '#f6ffed',
                'text_color' => '#2e7d32',
                'sort_order' => 50,
                'requires_approval' => false,
            ],
            'pertimbangan' => [
                'label' => 'Pertimbangan',
                'short_label' => 'Pertimbangan',
                'badge' => 'badge-pertimbangan',
                'color' => '#fa541c',
                'badge_bg' => '#fff2e8',
                'text_color' => '#8b4513',
                'sort_order' => 60,
                'requires_approval' => true,
            ],
            'rejected' => [
                'label' => 'Rejected',
                'short_label' => 'Rejected',
                'badge' => 'badge-rejected',
                'color' => '#f5222d',
                'badge_bg' => '#fff1f0',
                'text_color' => '#b71c1c',
                'sort_order' => 70,
                'requires_approval' => true,
            ],
        ];
    }

    private function get_recruitment_status_options()
    {
        $statuses = $this->get_base_recruitment_status_options();

        if ($this->db->table_exists('recruitment_statuses')) {
            $rows = $this->db
                ->order_by('sort_order', 'ASC')
                ->order_by('status_name', 'ASC')
                ->get_where('recruitment_statuses', ['is_active' => 1])
                ->result_array();

            foreach ($rows as $row) {
                $key = trim((string) ($row['status_key'] ?? ''));
                $label = trim((string) ($row['status_name'] ?? ''));
                if ($key === '' || $label === '' || isset($statuses[$key])) {
                    continue;
                }

                $statuses[$key] = [
                    'label' => $label,
                    'short_label' => trim((string) ($row['short_label'] ?? '')) ?: $this->build_short_status_label($label),
                    'badge' => 'badge-' . $key,
                    'color' => '#595959',
                    'badge_bg' => '#f5f5f5',
                    'text_color' => '#595959',
                    'sort_order' => (int) ($row['sort_order'] ?? 100),
                    'requires_approval' => false,
                ];
            }
        }

        uasort($statuses, function ($a, $b) {
            return ((int) ($a['sort_order'] ?? 100)) <=> ((int) ($b['sort_order'] ?? 100));
        });

        return $statuses;
    }

    private function get_recruitment_status_tabs()
    {
        $tabs = [
            'all' => [
                'label' => 'Semua',
                'short_label' => 'Semua',
                'badge' => 'badge-all',
                'color' => '#1890ff',
                'badge_bg' => '#e6f7ff',
                'text_color' => '#0d6efd',
                'sort_order' => 0,
            ],
        ];

        $tabs += $this->get_recruitment_status_options();
        unset($tabs['testcase_1']);
        $tabs['belum_sesuai'] = [
            'label' => 'Belum Sesuai',
            'short_label' => 'Belum Sesuai',
            'badge' => 'badge-belum_sesuai',
            'color' => '#8c8c8c',
            'badge_bg' => '#f5f5f5',
            'text_color' => '#595959',
            'sort_order' => 999,
        ];

        return $tabs;
    }

    private function build_status_key($status_name)
    {
        $key = strtolower(trim((string) $status_name));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim($key, '_');
        return $key !== '' ? $key : 'status_' . time();
    }

    private function build_short_status_label($status_name)
    {
        $words = preg_split('/\s+/', trim((string) $status_name));
        $words = array_values(array_filter($words));
        if (empty($words)) {
            return '';
        }
        if (count($words) === 1) {
            return mb_substr($words[0], 0, 10);
        }

        $short = '';
        foreach ($words as $word) {
            $short .= strtoupper(mb_substr($word, 0, 1));
        }

        return mb_substr($short, 0, 8);
    }

    private function get_default_recruitment_chat_templates()
    {
        return [
            'rejected' => "*Hai kak {name}*\n\nTerima kasih banyak sudah meluangkan waktu dan menunjukkan ketertarikan untuk bergabung bersama kami di {company}.\n\nSetelah melalui proses seleksi awal, untuk saat ini kami belum bisa melanjutkan lamaran kamu ke tahap berikutnya.\n\n_Semoga kamu segera menemukan ruang yang paling pas untuk tumbuh, berkarya, dan bersinar._\n\nBest Regards,\n*HR Bhskin*",
            'interview_hr' => "*Halo kak {name}*\n\n_Terima kasih atas ketertarikan kakak bergabung dengan {company} as {position}_\n\nKami ingin mengundang kak ke sesi #ngobrolseru bareng HR via Google Meet.\n\nJadwalnya:\n\n*Apakah kak berkenan hadir?*"
        ];
    }

    private function upsert_recruitment_chat_template($template_key, $template_text, $user_id = null)
    {
        $existing = $this->db->get_where('recruitment_chat_templates', ['template_key' => $template_key])->row_array();
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $this->db->where('template_key', $template_key);
            $this->db->update('recruitment_chat_templates', [
                'template_text' => $template_text,
                'updated_by' => $user_id,
                'updated_at' => $now
            ]);
            return;
        }

        $this->db->insert('recruitment_chat_templates', [
            'template_key' => $template_key,
            'template_text' => $template_text,
            'updated_by' => $user_id,
            'created_at' => $now,
            'updated_at' => $now
        ]);
    }

    private function get_recruitment_chat_templates()
    {
        $defaults = $this->get_default_recruitment_chat_templates();
        if (!$this->db->table_exists('recruitment_chat_templates')) {
            return $defaults;
        }

        $rows = $this->db->get('recruitment_chat_templates')->result_array();

        $templates = $defaults;
        foreach ($rows as $row) {
            $key = $row['template_key'] ?? '';
            $text = $row['template_text'] ?? '';
            if ($key !== '' && $text !== '') {
                $templates[$key] = $text;
            }
        }

        return $templates;
    }

    private function get_recruitment_message_templates()
    {
        $defaults = $this->get_default_recruitment_chat_templates();
        $labels = [
            'interview_hr' => 'Undangan Interview HR',
            'rejected' => 'Ditolak',
        ];

        $templates = [];
        foreach ($defaults as $key => $text) {
            $templates[$key] = [
                'key' => $key,
                'name' => $labels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                'text' => $text,
                'is_default' => true,
            ];
        }

        if ($this->db->table_exists('recruitment_chat_templates')) {
            $rows = $this->db->order_by('template_key', 'ASC')->get('recruitment_chat_templates')->result_array();
            foreach ($rows as $row) {
                $key = $row['template_key'] ?? '';
                if ($key === '') {
                    continue;
                }

                $templates[$key] = [
                    'key' => $key,
                    'name' => $labels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                    'text' => $row['template_text'] ?? '',
                    'is_default' => isset($defaults[$key]),
                ];
            }
        }

        return array_values($templates);
    }
}
