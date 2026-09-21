<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Manpower_planning extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');

        // Set public methods (no permission required)
        $this->set_public_methods([]);

        // Override method-to-action mapping if needed
        $this->set_method_permissions([
            'remove' => 'delete'
        ]);
    }
    private function get_requestor_users()
    {
        return $this->mymodel->selectWithQuery("SELECT id, full_name, role FROM user WHERE role IN ('1', '2') ORDER BY full_name ASC");
    }

    private function get_requestor_names()
    {
        $users = $this->get_requestor_users();
        return array_values(array_filter(array_map(function ($row) {
            return $row['full_name'] ?? '';
        }, $users)));
    }

    private function get_allowed_options()
    {
        return array(
            'level' => array('Internship', 'Junior (Eksekutor)', 'Senior (Eks+Analyst)', 'Leader', 'POV'),
            'status' => array('CONTRACT', 'INTERNSHIP'),
            'sistem' => array('Onsite Banyuwangi', 'Onsite Yogyakarta', 'WFH', 'Hybrid'),
            'approval_ceo' => array('Done', 'Hold'),
            'progress' => array('RUN', 'PENDING', 'DONE')
        );
    }

    public function index()
    {
        $data['user'] = $_SESSION['user'];
        
        // Permission check handled by BaseController middleware

        $keyword_category = $_GET['keyword_category'] ?? "Posisi";
        $keyword = $_GET['keyword'] ?? "";
        
        $data['keyword_category'] = $keyword_category;
        $data['keyword'] = $keyword;
        $data['title'] = 'Manpower Planning - ' . $this->template->title();

        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Posisi") {
                $qry .= " AND posisi LIKE '%$keyword%'";
            } else if ($keyword_category == "Requestor") {
                $qry .= " AND requestor LIKE '%$keyword%'";
            } else if ($keyword_category == "Status") {
                $qry .= " AND status LIKE '%$keyword%'";
            } else if ($keyword_category == "Level") {
                $qry .= " AND level LIKE '%$keyword%'";
            }
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM manpower_planning WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/manpower_planning/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("manpower_planning/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {
        $data['template'] = $this->template;
        $keyword_category = $_GET['keyword_category'] ?? "Posisi";
        $keyword = $_GET['keyword'] ?? "";
        
        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Posisi") {
                $qry .= " AND posisi LIKE '%$keyword%'";
            } else if ($keyword_category == "Requestor") {
                $qry .= " AND requestor LIKE '%$keyword%'";
            } else if ($keyword_category == "Status") {
                $qry .= " AND status LIKE '%$keyword%'";
            } else if ($keyword_category == "Level") {
                $qry .= " AND level LIKE '%$keyword%'";
            }
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;
        
        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT * FROM manpower_planning WHERE $qry ORDER BY tanggal_request DESC, id DESC LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;
        
        $this->load->view("manpower_planning/item", $data);
    }

    public function create_page()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $data['data'] = array();
        $data['requestor_users'] = $this->get_requestor_users();
        $data['title'] = 'Tambah Manpower Planning - ' . $this->template->title();
        $data['content'] = $this->load->view("manpower_planning/create_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function store()
    {
        $user = $_SESSION['user'];
        $dt = $_POST['dt'];
        
        $allowed_options = $this->get_allowed_options();
        $allowed_requestors = $this->get_requestor_names();
        $default_requestor = $user['full_name'] ?? '';

        if (empty($dt['requestor']) && in_array($default_requestor, $allowed_requestors)) {
            $dt['requestor'] = $default_requestor;
        }

        $validation_errors = array();
        if (empty($dt['requestor']) || !in_array($dt['requestor'], $allowed_requestors)) {
            $validation_errors[] = 'Requestor harus dipilih dari daftar Admin/Super Admin.';
        }
        if (!empty($dt['level']) && !in_array($dt['level'], $allowed_options['level'])) {
            $validation_errors[] = 'Level tidak valid.';
        }
        if (!empty($dt['status']) && !in_array($dt['status'], $allowed_options['status'])) {
            $validation_errors[] = 'Status tidak valid.';
        }
        if (!empty($dt['sistem']) && !in_array($dt['sistem'], $allowed_options['sistem'])) {
            $validation_errors[] = 'Sistem tidak valid.';
        }
        if (!empty($dt['approval_ceo']) && !in_array($dt['approval_ceo'], $allowed_options['approval_ceo'])) {
            $validation_errors[] = 'Approval CEO tidak valid.';
        }
        if (!empty($dt['progress']) && !in_array($dt['progress'], $allowed_options['progress'])) {
            $validation_errors[] = 'Progres tidak valid.';
        }

        if (!empty($validation_errors)) {
            echo $this->template->alert_danger('<ul class="mb-0"><li>' . implode('</li><li>', $validation_errors) . '</li></ul>');
            return;
        }

        // Default tanggal_request to today if empty
        if (empty($dt['tanggal_request'])) {
            $dt['tanggal_request'] = date('Y-m-d');
        }

        // Remove PIC if present
        if (isset($dt['pic'])) {
            unset($dt['pic']);
        }

        // Add created_by
        $dt['created_by'] = $user['id'];

        if ($this->db->insert('manpower_planning', $dt)) {
            $msg = 'Tambah data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function edit_page()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM manpower_planning WHERE id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'manpower_planning');
        }

        $data['data'] = $query[0];
        $data['requestor_users'] = $this->get_requestor_users();
        $data['title'] = 'Edit Manpower Planning - ' . $this->template->title();
        $data['content'] = $this->load->view("manpower_planning/edit_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function update()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        
        $allowed_options = $this->get_allowed_options();
        $allowed_requestors = $this->get_requestor_names();

        $validation_errors = array();
        if (empty($dt['requestor']) || !in_array($dt['requestor'], $allowed_requestors)) {
            $validation_errors[] = 'Requestor harus dipilih dari daftar Admin/Super Admin.';
        }
        if (!empty($dt['level']) && !in_array($dt['level'], $allowed_options['level'])) {
            $validation_errors[] = 'Level tidak valid.';
        }
        if (!empty($dt['status']) && !in_array($dt['status'], $allowed_options['status'])) {
            $validation_errors[] = 'Status tidak valid.';
        }
        if (!empty($dt['sistem']) && !in_array($dt['sistem'], $allowed_options['sistem'])) {
            $validation_errors[] = 'Sistem tidak valid.';
        }
        if (!empty($dt['approval_ceo']) && !in_array($dt['approval_ceo'], $allowed_options['approval_ceo'])) {
            $validation_errors[] = 'Approval CEO tidak valid.';
        }
        if (!empty($dt['progress']) && !in_array($dt['progress'], $allowed_options['progress'])) {
            $validation_errors[] = 'Progres tidak valid.';
        }

        if (!empty($validation_errors)) {
            echo $this->template->alert_danger('<ul class="mb-0"><li>' . implode('</li><li>', $validation_errors) . '</li></ul>');
            return;
        }

        // Remove PIC if present
        if (isset($dt['pic'])) {
            unset($dt['pic']);
        }

        // Add updated_by
        $dt['updated_by'] = $user['id'];

        if ($this->db->update('manpower_planning', $dt, array('id' => $id))) {
            $msg = 'Update data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function detail()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM manpower_planning WHERE id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'manpower_planning');
        }

        $data['data'] = $query[0];
        $data['title'] = 'Detail Manpower Planning - ' . $this->template->title();
        $data['content'] = $this->load->view("manpower_planning/detail_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("manpower_planning/delete", $data);
    }

    public function delete()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];

        if ($this->db->delete('manpower_planning', array('id' => $id))) {
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function update_status()
    {
        $user = $_SESSION['user'];
        if (!in_array($user['role'], array('1', '2'))) {
            echo json_encode(array('status' => 'error', 'message' => 'Unauthorized'));
            return;
        }

        $id = $_POST['id'] ?? null;
        $field = $_POST['field'] ?? null;
        $value = $_POST['value'] ?? null;

        $allowed_fields = array('approval_ceo', 'progress');
        if (empty($id) || !in_array($field, $allowed_fields)) {
            echo json_encode(array('status' => 'error', 'message' => 'Invalid request'));
            return;
        }

        $allowed_options = $this->get_allowed_options();
        if ($field === 'approval_ceo' && !in_array($value, $allowed_options['approval_ceo'])) {
            echo json_encode(array('status' => 'error', 'message' => 'Invalid approval value'));
            return;
        }
        if ($field === 'progress' && !in_array($value, $allowed_options['progress'])) {
            echo json_encode(array('status' => 'error', 'message' => 'Invalid progress value'));
            return;
        }

        $dt = array(
            $field => $value,
            'updated_by' => $user['id']
        );

        if ($this->db->update('manpower_planning', $dt, array('id' => $id))) {
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Update failed'));
        }
    }
}
