<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

/**
 * Contract
 *
 * Manajemen & riwayat kontrak karyawan (Probation / PKWT / PKWTT).
 * - index()/item()  : daftar karyawan + status kontrak aktif
 * - history()        : timeline seluruh kontrak satu karyawan
 * - create_page()/store() : perbarui/perpanjang kontrak (kontrak lama jadi history)
 * - edit_page()/update()  : koreksi data kontrak
 * - detail()/remove()/delete()/bulk_delete()
 * - cron_contract_expiry() : reminder kontrak yang akan/sudah berakhir
 *
 * Modul RBAC: 'contract' (lihat BaseController + Roles).
 */
class Contract extends BaseController
{
    private $contract_types = ['Probation', 'PKWT', 'PKWTT'];

    // Harus berupa properti (dibaca parent::__construct sebelum constructor anak):
    // cron bersifat publik (token-guarded), tanpa sesi login.
    protected $public_methods = ['cron_contract_expiry'];
    protected $method_permissions = ['history' => 'view'];

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
        $this->load->model('mymodel');
        $this->load->model('leave_model');
        $this->load->library('permission');
        $this->load->library('template');
    }

    // =====================================================================
    // LIST
    // =====================================================================
    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];

        $data['can_create'] = $this->permission->check_permission($user_id, 'contract', 'create');
        $data['can_edit']   = $this->permission->check_permission($user_id, 'contract', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'contract', 'delete');

        $data['keyword'] = $_GET['keyword'] ?? '';
        $data['status_filter'] = $_GET['status_filter'] ?? '';

        $data['title'] = 'Riwayat Kontrak - ' . $this->template->title();
        $data['content'] = $this->load->view('contract/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * AJAX: baris tabel daftar karyawan + kontrak aktifnya.
     */
    public function item()
    {
        $data['template'] = $this->template;
        $user_id = $_SESSION['user']['id'];
        $data['can_create'] = $this->permission->check_permission($user_id, 'contract', 'create');
        $data['can_edit']   = $this->permission->check_permission($user_id, 'contract', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'contract', 'delete');

        $keyword = $this->db->escape_str($_GET['keyword'] ?? '');
        $status_filter = $this->db->escape_str($_GET['status_filter'] ?? '');

        $qry = "u.status = 'Aktif'";
        if ($keyword !== '') {
            $qry .= " AND (u.full_name LIKE '%$keyword%' OR u.email LIKE '%$keyword%')";
        }
        if ($status_filter !== '') {
            $qry .= " AND ec.status = '$status_filter'";
        }

        $limit = 10;
        $current_page = max(1, intval($_GET['page'] ?? 1));
        $offset = ($current_page - 1) * $limit;

        // ambil user beserta kontrak current-nya (jika ada)
        $rows = $this->mymodel->selectWithQuery("
            SELECT u.id AS user_id, u.full_name, u.email,
                   ec.id AS contract_id, ec.contract_type, ec.start_date, ec.end_date,
                   ec.status, ec.duration_text,
                   p.name AS position_name,
                   (SELECT COUNT(*) FROM employee_contracts c2 WHERE c2.user_id = u.id) AS contract_count
            FROM user u
            LEFT JOIN employee_contracts ec ON ec.user_id = u.id AND ec.is_current = 1
            LEFT JOIN positions p ON p.id = ec.position_id
            WHERE $qry
            ORDER BY u.full_name ASC
            LIMIT $offset, $limit
        ");

        $data['data'] = $rows;
        $data['start'] = $offset;
        $this->load->view('contract/item', $data);
    }

    // =====================================================================
    // HISTORY (timeline per karyawan)
    // =====================================================================
    public function history($user_id = null)
    {
        $user_id = (int) ($user_id ?: ($_GET['user_id'] ?? 0));
        if ($user_id <= 0) {
            redirect(base_url() . 'contract');
        }

        $data['user'] = $_SESSION['user'];
        $uid = $data['user']['id'];
        $data['can_create'] = $this->permission->check_permission($uid, 'contract', 'create');
        $data['can_edit']   = $this->permission->check_permission($uid, 'contract', 'edit');
        $data['can_delete'] = $this->permission->check_permission($uid, 'contract', 'delete');

        $emp = $this->mymodel->selectWithQuery("SELECT id, full_name, email FROM user WHERE id = '$user_id' LIMIT 1");
        if (empty($emp)) {
            redirect(base_url() . 'contract');
        }
        $data['employee'] = $emp[0];

        $data['contracts'] = $this->mymodel->selectWithQuery("
            SELECT ec.*, p.name AS position_name, cu.full_name AS created_by_name
            FROM employee_contracts ec
            LEFT JOIN positions p ON p.id = ec.position_id
            LEFT JOIN user cu ON cu.id = ec.created_by
            WHERE ec.user_id = '$user_id'
            ORDER BY ec.start_date DESC, ec.id DESC
        ");
        $leave_profile = $this->leave_model->get_user_profile($user_id);
        $leave_accrual_start = $this->leave_model->get_leave_accrual_start($leave_profile);
        $data['leave_balance'] = $this->leave_model->get_leave_balance($user_id);
        $data['leave_accrual_start'] = $leave_accrual_start;
        $data['leave_accrued'] = $this->leave_model->calculate_months_since($leave_accrual_start);
        $data['leave_used'] = $this->leave_model->get_used_leave_days($user_id, $leave_accrual_start);
        $data['leave_requests'] = $this->leave_model->list_user_requests($user_id);
        foreach ($data['leave_requests'] as &$leave_request) {
            $leave_start = $this->db->escape($leave_request['start_date'] ?? '');
            $contract_at_leave = $this->mymodel->selectWithQuery("
                SELECT contract_type, start_date, end_date
                FROM employee_contracts
                WHERE user_id = '$user_id'
                  AND start_date <= $leave_start
                  AND (end_date IS NULL OR end_date >= $leave_start)
                ORDER BY start_date DESC, id DESC
                LIMIT 1");
            $leave_request['contract_at_leave'] = !empty($contract_at_leave) ? $contract_at_leave[0] : null;
        }
        unset($leave_request);
        $data['leave_balance_is_manual'] = is_array($leave_profile)
            && isset($leave_profile['leave_balance'])
            && $leave_profile['leave_balance'] !== null
            && $leave_profile['leave_balance'] !== '';
        $data['is_probation'] = is_array($leave_profile)
            && !empty($leave_profile['is_probation'])
            && intval($leave_profile['is_probation']) === 1;

        $data['title'] = 'Riwayat Kontrak - ' . $data['employee']['full_name'];
        $data['content'] = $this->load->view('contract/history', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    // =====================================================================
    // CREATE / RENEW
    // =====================================================================
    public function create_page()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = (int) ($_GET['user_id'] ?? 0);
        if ($user_id <= 0) {
            redirect(base_url() . 'contract');
        }

        $emp = $this->mymodel->selectWithQuery("SELECT id, full_name FROM user WHERE id = '$user_id' LIMIT 1");
        if (empty($emp)) {
            redirect(base_url() . 'contract');
        }

        $data['employee'] = $emp[0];
        $data['data'] = [];
        $data['contract_types'] = $this->contract_types;
        $data['positions'] = $this->mymodel->selectWithQuery("
            SELECT p.id, p.name, ql.name AS level_name
            FROM positions p
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            ORDER BY COALESCE(ql.level_order, 999), p.name ASC
        ");

        // kontrak terakhir sebagai acuan perpanjangan
        $current = $this->mymodel->selectWithQuery("
            SELECT * FROM employee_contracts WHERE user_id = '$user_id' AND is_current = 1 LIMIT 1");
        $data['current'] = !empty($current) ? $current[0] : null;
        $data['is_modal'] = $this->input->is_ajax_request() || (($_GET['modal'] ?? '') === '1');

        if (!empty($data['is_modal'])) {
            $this->load->view('contract/create_page', $data);
            return;
        }

        $data['title'] = 'Perbarui Kontrak - ' . $emp[0]['full_name'] . ' - ' . $this->template->title();
        $data['content'] = $this->load->view('contract/create_page', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function store()
    {
        $user = $_SESSION['user'];
        $dt = $_POST['dt'] ?? [];
        $user_id = (int) ($dt['user_id'] ?? 0);

        $error = $this->validate_contract($dt, $user_id);
        if ($error !== null) {
            echo $this->template->alert_danger($error);
            return;
        }

        $payload = $this->build_contract_payload($dt);
        $payload['user_id'] = $user_id;
        $payload['is_current'] = 1;
        $payload['status'] = 'active';
        $payload['created_by'] = $user['id'];
        $payload['created_at'] = date('Y-m-d H:i:s');

        $this->db->trans_start();

        // kontrak lama -> history
        $this->db->update(
            'employee_contracts',
            ['is_current' => 0, 'status' => 'renewed', 'updated_at' => date('Y-m-d H:i:s')],
            ['user_id' => $user_id, 'is_current' => 1]
        );

        $this->db->insert('employee_contracts', $payload);

        // sinkron snapshot ke user_profile (dipakai Payroll & Leave)
        $this->sync_user_profile($user_id, $payload);
        $this->sync_contract_leave_balance($user_id, $payload);

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            echo $this->template->alert_danger('Gagal menyimpan kontrak!');
            return;
        }

        $this->notify_contract_saved($user_id, $payload, $user['id']);

        echo $this->template->alert_success('Kontrak berhasil diperbarui!');
    }

    // =====================================================================
    // EDIT (koreksi, bukan perpanjangan)
    // =====================================================================
    public function edit_page()
    {
        $id = (int) ($_GET['id'] ?? 0);
        $query = $this->mymodel->selectWithQuery("SELECT * FROM employee_contracts WHERE id = '$id' LIMIT 1");
        if (empty($query)) {
            redirect(base_url() . 'contract');
        }

        $data['user'] = $_SESSION['user'];
        $data['data'] = $query[0];
        $data['contract_types'] = $this->contract_types;
        $data['positions'] = $this->mymodel->selectWithQuery("
            SELECT p.id, p.name, ql.name AS level_name
            FROM positions p
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            ORDER BY COALESCE(ql.level_order, 999), p.name ASC
        ");

        $emp = $this->mymodel->selectWithQuery("SELECT id, full_name FROM user WHERE id = '" . (int) $query[0]['user_id'] . "' LIMIT 1");
        $data['employee'] = !empty($emp) ? $emp[0] : ['id' => $query[0]['user_id'], 'full_name' => '-'];

        $data['title'] = 'Edit Kontrak - ' . $this->template->title();
        $data['content'] = $this->load->view('contract/edit_page', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function update()
    {
        $user = $_SESSION['user'];
        $id = (int) ($_POST['id'] ?? 0);
        $dt = $_POST['dt'] ?? [];

        $existing = $this->mymodel->selectWithQuery("SELECT * FROM employee_contracts WHERE id = '$id' LIMIT 1");
        if (empty($existing)) {
            echo $this->template->alert_danger('Kontrak tidak ditemukan!');
            return;
        }
        $existing = $existing[0];
        $user_id = (int) $existing['user_id'];

        $error = $this->validate_contract($dt, $user_id);
        if ($error !== null) {
            echo $this->template->alert_danger($error);
            return;
        }

        $payload = $this->build_contract_payload($dt);
        $payload['updated_by'] = $user['id'];
        $payload['updated_at'] = date('Y-m-d H:i:s');

        $this->db->update('employee_contracts', $payload, ['id' => $id]);

        // bila kontrak ini current, perbarui juga snapshot user_profile
        if ((int) $existing['is_current'] === 1) {
            $this->sync_user_profile($user_id, $payload);
            $this->sync_contract_leave_balance($user_id, $payload);
        }

        echo $this->template->alert_success('Kontrak berhasil diupdate!');
    }

    // =====================================================================
    // DETAIL
    // =====================================================================
    public function detail()
    {
        $id = (int) ($_GET['id'] ?? 0);
        $query = $this->mymodel->selectWithQuery("
            SELECT ec.*, u.full_name AS employee_name, u.email,
                   p.name AS position_name, cu.full_name AS created_by_name
            FROM employee_contracts ec
            LEFT JOIN user u ON u.id = ec.user_id
            LEFT JOIN positions p ON p.id = ec.position_id
            LEFT JOIN user cu ON cu.id = ec.created_by
            WHERE ec.id = '$id' LIMIT 1");
        if (empty($query)) {
            redirect(base_url() . 'contract');
        }

        $data['user'] = $_SESSION['user'];
        $data['data'] = $query[0];
        $data['title'] = 'Detail Kontrak - ' . $this->template->title();
        $data['content'] = $this->load->view('contract/detail_page', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    // =====================================================================
    // DELETE
    // =====================================================================
    public function remove()
    {
        $data['data']['id'] = (int) ($_GET['id'] ?? 0);
        $this->load->view('contract/delete', $data);
    }

    public function delete()
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo $this->template->alert_danger('ID kontrak tidak valid!');
            return;
        }

        $row = $this->mymodel->selectWithQuery("SELECT * FROM employee_contracts WHERE id = '$id' LIMIT 1");
        if (empty($row)) {
            echo $this->template->alert_danger('Kontrak tidak ditemukan!');
            return;
        }
        $row = $row[0];

        $this->db->trans_start();
        $this->db->delete('employee_contracts', ['id' => $id]);

        // bila yang dihapus adalah kontrak current, promosikan kontrak terbaru berikutnya
        if ((int) $row['is_current'] === 1) {
            $this->promote_latest_contract((int) $row['user_id']);
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            echo $this->template->alert_danger('Hapus data tidak berhasil!');
            return;
        }
        echo $this->template->alert_success('Hapus data berhasil!');
    }

    public function bulk_delete()
    {
        header('Content-Type: application/json');
        $user_id = $_SESSION['user']['id'];
        try {
            $this->permission->enforce_permission($user_id, 'contract', 'delete');
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki izin menghapus kontrak.']);
            return;
        }

        if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada item yang dipilih.']);
            return;
        }

        $ids = array_filter(array_map('intval', $_POST['ids']), function ($v) {
            return $v > 0;
        });
        $deleted = 0;
        $affected_users = [];

        $this->db->trans_start();
        foreach ($ids as $id) {
            $row = $this->mymodel->selectWithQuery("SELECT user_id, is_current FROM employee_contracts WHERE id = '$id' LIMIT 1");
            if (empty($row)) {
                continue;
            }
            $this->db->delete('employee_contracts', ['id' => $id]);
            if ((int) $row[0]['is_current'] === 1) {
                $affected_users[(int) $row[0]['user_id']] = true;
            }
            $deleted++;
        }
        foreach (array_keys($affected_users) as $uid) {
            $this->promote_latest_contract($uid);
        }
        $this->db->trans_complete();

        echo json_encode([
            'success' => $deleted > 0,
            'deleted_count' => $deleted,
            'message' => $deleted > 0 ? "$deleted kontrak berhasil dihapus." : 'Tidak ada kontrak yang dihapus.'
        ]);
    }

    // =====================================================================
    // CRON: reminder kontrak akan/sudah berakhir
    // Route: cronjob/contract-expiry  (token-guarded)
    // =====================================================================
    public function cron_contract_expiry()
    {
        // proteksi sederhana: butuh token kecuali via CLI
        if (!$this->input->is_cli_request()) {
            $token = $_GET['token'] ?? '';
            if ($token !== app_env('CONTRACT_CRON_TOKEN')) {
                $this->output->set_status_header(403);
                echo 'Forbidden';
                return;
            }
        }

        $today = date('Y-m-d');
        $threshold = date('Y-m-d', strtotime('+30 days'));
        $notified = 0;

        // tandai yang sudah lewat sebagai expired
        $this->db->query("
            UPDATE employee_contracts
            SET status = 'expired', updated_at = NOW()
            WHERE is_current = 1 AND end_date IS NOT NULL
              AND end_date < '$today' AND status NOT IN ('expired', 'terminated', 'renewed')");

        // kontrak yang berakhir <= 30 hari -> expiring + notifikasi (sekali per kontrak)
        $expiring = $this->mymodel->selectWithQuery("
            SELECT ec.id, ec.user_id, ec.contract_type, ec.end_date, u.full_name
            FROM employee_contracts ec
            LEFT JOIN user u ON u.id = ec.user_id
            WHERE ec.is_current = 1 AND ec.end_date IS NOT NULL
              AND ec.end_date >= '$today' AND ec.end_date <= '$threshold'
              AND ec.status NOT IN ('terminated', 'renewed')
              AND (ec.expiry_notified_at IS NULL OR ec.expiry_notified_at < '$today')");

        // penerima HR/Admin
        $hr_users = $this->mymodel->selectWithQuery("SELECT id FROM user WHERE role IN ('1','2')");

        foreach ($expiring as $c) {
            $days = (int) ((strtotime($c['end_date']) - strtotime($today)) / 86400);
            $this->db->update('employee_contracts',
                ['status' => 'expiring', 'expiry_notified_at' => $today, 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => $c['id']]);

            $title = 'Kontrak akan berakhir';
            $msg = 'Kontrak ' . $c['contract_type'] . ' a/n ' . $c['full_name']
                . ' berakhir pada ' . date('d M Y', strtotime($c['end_date'])) . ' (' . $days . ' hari lagi).';

            // notifikasi ke HR
            foreach ($hr_users as $hr) {
                $this->push_notif($hr['id'], $title, $msg, 'warning');
            }
            // notifikasi ke karyawan ybs
            $this->push_notif($c['user_id'], 'Kontrak Anda akan berakhir',
                'Kontrak ' . $c['contract_type'] . ' Anda berakhir pada ' . date('d M Y', strtotime($c['end_date'])) . ' (' . $days . ' hari lagi). Silakan hubungi HR.',
                'warning');

            $notified++;
        }

        if ($this->input->is_cli_request()) {
            echo "Contract expiry cron done. Notified: $notified\n";
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'notified' => $notified]);
        }
    }

    // =====================================================================
    // HELPERS
    // =====================================================================
    private function validate_contract($dt, $user_id)
    {
        if ($user_id <= 0) {
            return 'Karyawan tidak valid!';
        }
        if (empty($dt['contract_type']) || !in_array($dt['contract_type'], $this->contract_types, true)) {
            return 'Jenis kontrak tidak valid!';
        }
        if (empty($dt['start_date'])) {
            return 'Tanggal mulai harus diisi!';
        }
        // PKWTT permanen -> end_date boleh kosong; selain itu wajib
        if ($dt['contract_type'] !== 'PKWTT' && empty($dt['end_date'])) {
            return 'Tanggal berakhir harus diisi untuk kontrak ' . $dt['contract_type'] . '!';
        }
        if (!empty($dt['end_date']) && !empty($dt['start_date']) && $dt['end_date'] < $dt['start_date']) {
            return 'Tanggal berakhir tidak boleh sebelum tanggal mulai!';
        }
        return null;
    }

    private function build_contract_payload($dt)
    {
        $end_date = (!empty($dt['end_date']) && $dt['contract_type'] !== 'PKWTT') ? $dt['end_date'] : null;
        if ($dt['contract_type'] === 'PKWTT') {
            $end_date = !empty($dt['end_date']) ? $dt['end_date'] : null; // PKWTT umumnya null
        }

        return [
            'contract_type'   => $dt['contract_type'],
            'contract_number' => !empty($dt['contract_number']) ? trim($dt['contract_number']) : null,
            'position_id'     => !empty($dt['position_id']) ? (int) $dt['position_id'] : null,
            'start_date'      => $dt['start_date'],
            'end_date'        => $end_date,
            'duration_text'   => !empty($dt['duration_text']) ? trim($dt['duration_text']) : null,
            'salary'          => (isset($dt['salary']) && $dt['salary'] !== '') ? (float) $dt['salary'] : null,
            'document_url'    => !empty($dt['document_url']) ? trim($dt['document_url']) : null,
            'notes'           => !empty($dt['notes']) ? trim($dt['notes']) : null,
        ];
    }

    /**
     * Sinkron snapshot kontrak current ke user_profile agar Payroll & Leave tetap konsisten.
     */
    private function sync_user_profile($user_id, $payload)
    {
        $profile_update = [
            'jenis_kontrak' => $payload['contract_type'],
            'lama_kontrak'  => $payload['duration_text'],
            // Akrual cuti dihitung per kontrak terbaru (mulai dari tanggal kontrak ini).
            'leave_accrual_start' => $payload['start_date'],
        ];
        // Probation -> set flag is_probation
        $profile_update['is_probation'] = ($payload['contract_type'] === 'Probation') ? 1 : 0;

        $existing = $this->mymodel->selectWithQuery("SELECT id FROM user_profile WHERE user_id = '" . (int) $user_id . "' LIMIT 1");
        if (!empty($existing)) {
            $this->db->update('user_profile', $profile_update, ['user_id' => $user_id]);
        } else {
            $profile_update['user_id'] = $user_id;
            $this->db->insert('user_profile', $profile_update);
        }
    }

    /**
     * Jadikan kontrak terbaru (start_date terakhir) sebagai current setelah penghapusan.
     */
    private function promote_latest_contract($user_id)
    {
        $latest = $this->mymodel->selectWithQuery("
            SELECT * FROM employee_contracts WHERE user_id = '" . (int) $user_id . "'
            ORDER BY start_date DESC, id DESC LIMIT 1");
        if (empty($latest)) {
            return;
        }
        $this->db->update('employee_contracts',
            ['is_current' => 1, 'status' => 'active', 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $latest[0]['id']]);
        $this->sync_user_profile($user_id, $latest[0]);
        $this->sync_contract_leave_balance($user_id, $latest[0]);
    }

    /**
     * Aturan saldo cuti per kontrak:
     * - Probation: 0
     * - PKWT: saldo fix sesuai durasi kontrak, dibulatkan per 30 hari.
     *   Contoh 2026-07-01 s/d 2026-08-31 = 62 hari = ceil(62/30) = 3.
     * - PKWTT: null agar kembali ke perhitungan auto dari leave_accrual_start.
     */
    private function sync_contract_leave_balance($user_id, $payload)
    {
        $leave_balance = null;
        $contract_type = $payload['contract_type'] ?? '';

        if ($contract_type === 'Probation') {
            $leave_balance = 0;
        } elseif ($contract_type === 'PKWT') {
            $allocation = $this->calculate_pkwt_leave_balance(
                $payload['start_date'] ?? null,
                $payload['end_date'] ?? null
            );
            $used = $this->leave_model->get_used_leave_days((int) $user_id, $payload['start_date'] ?? null);
            $leave_balance = max(0, $allocation - $used);
        }

        $this->db->update('user_profile', ['leave_balance' => $leave_balance], ['user_id' => (int) $user_id]);
    }

    private function calculate_pkwt_leave_balance($start_date, $end_date)
    {
        if (empty($start_date) || empty($end_date)) {
            return 0;
        }

        try {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            if ($end < $start) {
                return 0;
            }

            $days = ((int) $start->diff($end)->days) + 1;
            return (int) ceil($days / 30);
        } catch (Exception $e) {
            return 0;
        }
    }

    private function notify_contract_saved($user_id, $payload, $actor_id)
    {
        $end = $payload['end_date'] ? (' s/d ' . date('d M Y', strtotime($payload['end_date']))) : ' (permanen)';
        $msg = 'Kontrak ' . $payload['contract_type'] . ' Anda diperbarui: mulai '
            . date('d M Y', strtotime($payload['start_date'])) . $end . '.';
        $this->push_notif($user_id, 'Kontrak diperbarui', $msg, 'info');
    }

    /**
     * Insert notifikasi in-app (mandiri, mengikuti struktur tabel notifications).
     */
    private function push_notif($user_id, $title, $message, $type = 'info')
    {
        $this->db->insert('notifications', [
            'user_id'     => (int) $user_id,
            'title'       => $title,
            'message'     => $message,
            'type'        => $type,
            'category'    => 'Team',
            'subcategory' => 'contract',
            'is_read'     => 0,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
