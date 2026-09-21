<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Asset_management extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');

        $this->set_public_methods([]);

        $this->set_method_permissions([
            'save' => 'create',
            'update_field' => 'edit',
            'delete_row' => 'delete',
            'remove' => 'delete',
            'delete' => 'delete',
            'loan_create_page' => 'create',
            'loan_store' => 'create',
            'loan_edit_page' => 'edit',
            'loan_update' => 'edit',
            'loan_remove' => 'delete',
            'loan_delete' => 'delete'
        ]);
    }

    public function index()
    {
        $data['user'] = $_SESSION['user'];

        $keyword_category = $_GET['keyword_category'] ?? "Nama";
        $keyword = $_GET['keyword'] ?? "";

        $data['keyword_category'] = $keyword_category;
        $data['title'] = 'Asset Management - ' . $this->template->title();

        $qry = "1=1";

        if ($keyword) {
            if ($keyword_category == "Nama") {
                $qry .= " AND name LIKE '%$keyword%'";
            } else if ($keyword_category == "Catatan") {
                $qry .= " AND notes LIKE '%$keyword%'";
            }
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM assets WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("asset_management/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {
        $data['template'] = $this->template;
        $keyword_category = $_GET['keyword_category'] ?? "Nama";
        $keyword = $_GET['keyword'] ?? "";

        $qry = "1=1";

        if ($keyword) {
            if ($keyword_category == "Nama") {
                $qry .= " AND name LIKE '%$keyword%'";
            } else if ($keyword_category == "Catatan") {
                $qry .= " AND notes LIKE '%$keyword%'";
            }
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;

        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT * FROM assets WHERE $qry ORDER BY name ASC LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;

        $this->load->view("asset_management/item", $data);
    }

    public function save()
    {
        $now = date('Y-m-d H:i:s');
        $dt = [
            'name' => '',
            'notes' => '',
            'created_at' => $now,
            'updated_at' => $now
        ];

        if ($this->db->insert('assets', $dt)) {
            $id = $this->db->insert_id();
            echo json_encode(['status' => 'success', 'id' => $id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan data.']);
        }
    }

    public function update_field()
    {
        $id = $_POST['id'] ?? '';
        $field = $_POST['field'] ?? '';
        $value = $_POST['value'] ?? '';

        $allowed_fields = ['name', 'notes'];
        if (!$id || !in_array($field, $allowed_fields)) {
            echo json_encode(['status' => 'error', 'message' => 'Field tidak valid.']);
            return;
        }

        $dt = [
            $field => $value,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($this->db->update('assets', $dt, array('id' => $id))) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data.']);
        }
    }

    public function delete_row()
    {
        $id = $_POST['id'] ?? '';
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
            return;
        }

        $loan_count = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM asset_loans WHERE asset_id = '$id'");
        if (!empty($loan_count) && $loan_count[0]['count'] > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Aset tidak dapat dihapus karena memiliki riwayat peminjaman.']);
            return;
        }

        if ($this->db->delete('assets', array('id' => $id))) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data.']);
        }
    }

    public function loans()
    {
        $data['user'] = $_SESSION['user'];

        $keyword_category = $_GET['keyword_category'] ?? "Aset";
        $keyword = $_GET['keyword'] ?? "";

        $data['keyword_category'] = $keyword_category;
        $data['title'] = 'Peminjaman Aset - ' . $this->template->title();

        $qry = "1=1";
        if ($keyword) {
            if ($keyword_category == "Aset") {
                $qry .= " AND a.name LIKE '%$keyword%'";
            } else if ($keyword_category == "Peminjam") {
                $qry .= " AND u.full_name LIKE '%$keyword%'";
            } else if ($keyword_category == "Status") {
                $qry .= " AND al.status LIKE '%$keyword%'";
            }
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(al.id) AS count FROM asset_loans al LEFT JOIN assets a ON al.asset_id = a.id LEFT JOIN user u ON al.borrower_user_id = u.id WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("asset_management/loans_all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function loan_item()
    {
        $data['template'] = $this->template;
        $keyword_category = $_GET['keyword_category'] ?? "Aset";
        $keyword = $_GET['keyword'] ?? "";

        $qry = "1=1";
        if ($keyword) {
            if ($keyword_category == "Aset") {
                $qry .= " AND a.name LIKE '%$keyword%'";
            } else if ($keyword_category == "Peminjam") {
                $qry .= " AND u.full_name LIKE '%$keyword%'";
            } else if ($keyword_category == "Status") {
                $qry .= " AND al.status LIKE '%$keyword%'";
            }
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;

        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT al.*, a.name AS asset_name, u.full_name AS borrower_name FROM asset_loans al LEFT JOIN assets a ON al.asset_id = a.id LEFT JOIN user u ON al.borrower_user_id = u.id WHERE $qry ORDER BY al.loan_date DESC LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;

        $this->load->view("asset_management/loans_item", $data);
    }

    public function loan_create_page()
    {
        $data['user'] = $_SESSION['user'];
        $data['assets'] = $this->mymodel->selectWithQuery("SELECT id, name FROM assets ORDER BY name ASC");
        $data['borrowers'] = $this->mymodel->selectWithQuery("SELECT id, full_name FROM user ORDER BY full_name ASC");
        $data['title'] = 'Tambah Peminjaman - ' . $this->template->title();
        $data['content'] = $this->load->view("asset_management/loan_create_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function loan_store()
    {
        $dt = $_POST['dt'];
        $now = date('Y-m-d H:i:s');
        if (!isset($dt['status']) || $dt['status'] == '') {
            $dt['status'] = 'Borrowed';
        }
        $dt['created_at'] = $now;
        $dt['updated_at'] = $now;

        if ($this->db->insert('asset_loans', $dt)) {
            $msg = 'Tambah data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function loan_edit_page()
    {
        $data['user'] = $_SESSION['user'];

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM asset_loans WHERE id = '$id'");
        if (empty($query)) {
            redirect(base_url() . 'asset_management/loans');
        }

        $data['assets'] = $this->mymodel->selectWithQuery("SELECT id, name FROM assets ORDER BY name ASC");
        $data['borrowers'] = $this->mymodel->selectWithQuery("SELECT id, full_name FROM user ORDER BY full_name ASC");
        $data['data'] = $query[0];
        $data['title'] = 'Edit Peminjaman - ' . $this->template->title();
        $data['content'] = $this->load->view("asset_management/loan_edit_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function loan_update()
    {
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = date('Y-m-d H:i:s');

        if ($this->db->update('asset_loans', $dt, array('id' => $id))) {
            $msg = 'Update data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function loan_remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("asset_management/loan_delete", $data);
    }

    public function loan_delete()
    {
        $id = $_POST['id'];

        if ($this->db->delete('asset_loans', array('id' => $id))) {
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }
}
