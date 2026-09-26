<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Announcement extends BaseController
{
    public function __construct()
    {
        // IMPORTANT: must be set BEFORE parent::__construct(), because BaseController
        // runs its permission middleware inside its own constructor. If set afterwards
        // (via set_public_methods), it is too late and these endpoints get a 403 for
        // non-admin roles. get_active + mark_seen power the popup for ALL logged-in
        // users on every page, so they must bypass the module permission check.
        $this->public_methods = ['get_active', 'mark_seen'];

        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');

        $this->set_method_permissions([
            'remove' => 'delete'
        ]);
    }

    public function index()
    {
        $data['user'] = $_SESSION['user'];

        $keyword = $_GET['keyword'] ?? "";
        $data['keyword'] = $keyword;
        $data['title'] = 'Announcement Management - ' . $this->template->title();

        $qry = "1=1";
        if ($keyword) {
            $keyword = $this->db->escape_str($keyword);
            $qry .= " AND (title LIKE '%$keyword%' OR description LIKE '%$keyword%')";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM announcements WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("announcement/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {
        $data['template'] = $this->template;
        $keyword = $_GET['keyword'] ?? "";

        $qry = "1=1";
        if ($keyword) {
            $keyword = $this->db->escape_str($keyword);
            $qry .= " AND (title LIKE '%$keyword%' OR description LIKE '%$keyword%')";
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;
        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT * FROM announcements WHERE $qry ORDER BY id DESC LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;

        $this->load->view("announcement/item", $data);
    }

    public function create_page()
    {
        $data['user'] = $_SESSION['user'];
        $data['data'] = array();
        $data['title'] = 'Tambah Announcement - ' . $this->template->title();
        $data['content'] = $this->load->view("announcement/create_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function store()
    {
        $user = $_SESSION['user'];
        $dt = $_POST['dt'];

        // Popup is image-only, so the banner is required
        if (empty($_FILES['image']['name'])) {
            echo $this->template->alert_danger('Gambar banner wajib diunggah!');
            die;
        }

        // Normalize checkbox / optional fields
        $dt['title'] = isset($dt['title']) ? trim($dt['title']) : '';
        $dt['is_active'] = isset($_POST['dt']['is_active']) ? 1 : 0;
        $dt['start_date'] = !empty($dt['start_date']) ? $dt['start_date'] : null;
        $dt['end_date'] = !empty($dt['end_date']) ? $dt['end_date'] : null;
        $dt['created_by'] = $user['id'];
        $dt['created_at'] = date('Y-m-d H:i:s');
        $dt['updated_at'] = date('Y-m-d H:i:s');

        $image = $this->handle_upload();
        if ($image === false) {
            return; // error already echoed
        }
        if ($image !== null) {
            $dt['image'] = $image;
        }

        if ($this->db->insert('announcements', $dt)) {
            echo $this->template->alert_success('Tambah data berhasil!');
        } else {
            echo $this->template->alert_danger('Tambah data tidak berhasil!');
        }
    }

    public function edit_page()
    {
        $data['user'] = $_SESSION['user'];

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM announcements WHERE id = '" . $this->db->escape_str($id) . "'");

        if (empty($query)) {
            redirect(base_url() . 'announcement');
        }

        $data['data'] = $query[0];
        $data['title'] = 'Edit Announcement - ' . $this->template->title();
        $data['content'] = $this->load->view("announcement/edit_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function update()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];
        $dt = $_POST['dt'];

        $dt['title'] = isset($dt['title']) ? trim($dt['title']) : '';
        $dt['is_active'] = isset($_POST['dt']['is_active']) ? 1 : 0;
        $dt['start_date'] = !empty($dt['start_date']) ? $dt['start_date'] : null;
        $dt['end_date'] = !empty($dt['end_date']) ? $dt['end_date'] : null;
        $dt['updated_at'] = date('Y-m-d H:i:s');

        // Optional new banner image (keep existing if none uploaded)
        $image = $this->handle_upload();
        if ($image === false) {
            return; // error already echoed
        }
        if ($image !== null) {
            // remove old image
            $old = $this->mymodel->selectWithQuery("SELECT image FROM announcements WHERE id = '" . $this->db->escape_str($id) . "'");
            if (!empty($old) && !empty($old[0]['image'])) {
                $old_path = FCPATH . 'assets/uploads/announcements/' . $old[0]['image'];
                if (is_file($old_path)) {
                    @unlink($old_path);
                }
            }
            $dt['image'] = $image;
        }

        if ($this->db->update('announcements', $dt, array('id' => $id))) {
            echo $this->template->alert_success('Update data berhasil!');
        } else {
            echo $this->template->alert_danger('Update data tidak berhasil!');
        }
    }

    public function detail()
    {
        $data['user'] = $_SESSION['user'];

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM announcements WHERE id = '" . $this->db->escape_str($id) . "'");

        if (empty($query)) {
            redirect(base_url() . 'announcement');
        }

        $data['data'] = $query[0];
        $data['title'] = 'Detail Announcement - ' . $this->template->title();
        $data['content'] = $this->load->view("announcement/detail_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("announcement/delete", $data);
    }

    public function delete()
    {
        $id = $_POST['id'];

        // Remove banner image file if present
        $row = $this->mymodel->selectWithQuery("SELECT image FROM announcements WHERE id = '" . $this->db->escape_str($id) . "'");
        if (!empty($row) && !empty($row[0]['image'])) {
            $path = FCPATH . 'assets/uploads/announcements/' . $row[0]['image'];
            if (is_file($path)) {
                @unlink($path);
            }
        }

        if ($this->db->delete('announcements', array('id' => $id))) {
            echo $this->template->alert_success('Hapus data berhasil!');
        } else {
            echo $this->template->alert_danger('Hapus data tidak berhasil!');
        }
    }

    /**
     * Public endpoint consumed by the popup on every authenticated page.
     * Returns the single active announcement (if any) within its date window.
     */
    public function get_active()
    {
        $this->output->set_content_type('application/json');

        // Must be logged in to see announcements
        if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
            echo json_encode(['announcements' => []]);
            return;
        }

        $today = date('Y-m-d');
        $uid = (int) $_SESSION['user']['id'];
        // Stable per-login token (survives CI's periodic session-id regeneration,
        // unlike session_id() which rotates every 5 min and caused the popup to
        // reappear on every tab/page switch).
        $sid = $this->db->escape_str($this->login_token());
        // Token sudah tersimpan di sesi; sisanya hanya membaca. Kunci sesi
        // dilepas supaya permintaan lain dari user yang sama tidak antre.
        if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

        $rows = $this->mymodel->selectWithQuery("
            SELECT id, title, description, image, cta_label, cta_url, frequency, updated_at
            FROM announcements
            WHERE is_active = 1
              AND (start_date IS NULL OR start_date <= '$today')
              AND (end_date IS NULL OR end_date >= '$today')
            ORDER BY updated_at DESC, id DESC
        ");

        $announcements = [];
        foreach ($rows as $row) {
            $aid = (int) $row['id'];

            // Decide if this user still needs to see it, based on frequency + view log
            if ($row['frequency'] === 'once') {
                $seen = $this->mymodel->selectWithQuery(
                    "SELECT id FROM announcement_views WHERE user_id = $uid AND announcement_id = $aid LIMIT 1"
                );
            } elseif ($row['frequency'] === 'every_login') {
                $seen = $this->mymodel->selectWithQuery(
                    "SELECT id FROM announcement_views WHERE user_id = $uid AND announcement_id = $aid AND session_id = '$sid' LIMIT 1"
                );
            } else { // daily
                $seen = $this->mymodel->selectWithQuery(
                    "SELECT id FROM announcement_views WHERE user_id = $uid AND announcement_id = $aid AND seen_date = '$today' LIMIT 1"
                );
            }

            if (!empty($seen)) {
                continue; // already seen for this period
            }

            $row['image_url'] = !empty($row['image'])
                ? base_url() . 'assets/uploads/announcements/' . $row['image']
                : null;
            $announcements[] = $row;
        }

        echo json_encode(['announcements' => $announcements]);
    }

    /**
     * Records that the current user has seen an announcement (called by the popup).
     */
    public function mark_seen()
    {
        $this->output->set_content_type('application/json');

        if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
            echo json_encode(['ok' => false]);
            return;
        }

        $aid = (int) $this->input->post('announcement_id');
        if (!$aid) {
            echo json_encode(['ok' => false]);
            return;
        }

        $this->db->insert('announcement_views', [
            'announcement_id' => $aid,
            'user_id'         => (int) $_SESSION['user']['id'],
            'session_id'      => $this->login_token(),
            'seen_date'       => date('Y-m-d'),
            'seen_at'         => date('Y-m-d H:i:s'),
        ]);

        echo json_encode(['ok' => true]);
    }

    /**
     * Returns a token that is stable for the whole login session and only changes
     * on a fresh login. Used for the "every_login" frequency. We cannot use
     * session_id() because CI regenerates it every few minutes (sess_time_to_update),
     * which made the popup reappear constantly.
     */
    private function login_token()
    {
        if (empty($_SESSION['ann_login_token'])) {
            $_SESSION['ann_login_token'] = md5(uniqid((string) mt_rand(), true));
        }
        return $_SESSION['ann_login_token'];
    }

    /**
     * Shared banner upload handler.
     * @return string|null|false  filename on success, null when no file sent,
     *                            false when an upload error occurred (alert echoed).
     */
    private function handle_upload()
    {
        if (empty($_FILES['image']['name'])) {
            return null;
        }

        $upload_path = FCPATH . 'assets/uploads/announcements/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
        $config['max_size'] = 2048; // 2MB
        $config['file_name'] = 'announcement_' . time() . '_' . uniqid();

        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        if ($this->upload->do_upload('image')) {
            $upload_data = $this->upload->data();
            return $upload_data['file_name'];
        }

        echo $this->template->alert_danger('Upload gambar gagal: ' . strip_tags($this->upload->display_errors()));
        return false;
    }
}
