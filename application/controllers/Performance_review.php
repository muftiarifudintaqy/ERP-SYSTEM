<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

/**
 * Performance_review — Penilaian Kinerja Karyawan
 *
 * 3 lapisan:
 *  - Template penilaian (multi-tipe: rubric/yesno/scale/percent/text)
 *  - Review per karyawan per periode + assignment reviewer (fleksibel)
 *  - Pengisian reviewer (referensi) -> HR input final -> publish PDF ke profil
 *
 * Modul RBAC: 'performance_review'. Metode menghadap-reviewer (my_assignments,
 * fill, submit_fill) dan pdf() dibuat publik dari gate modul lalu dicek manual
 * (assigned reviewer / reviewee terbit / HR).
 */
class Performance_review extends BaseController
{
    private $input_types = ['rubric', 'yesno', 'scale', 'percent', 'text'];
    private $reviewer_roles = ['self', 'leader', 'hr', 'peer'];

    protected $public_methods = ['my_assignments', 'fill', 'submit_fill', 'pdf', 'reviewer_answer'];
    protected $method_permissions = [
        'template_create_page' => 'create', 'template_store' => 'create',
        'template_edit_page' => 'edit', 'template_update' => 'edit',
        'template_delete' => 'delete',
        'review_create_page' => 'create', 'review_store' => 'create',
        'assign_reviewer' => 'edit', 'unassign_reviewer' => 'edit',
        'reviewer_assignments' => 'edit',
        'toggle_reviewer_assignment' => 'edit',
        'final_page' => 'edit', 'final_store' => 'edit',
        'publish' => 'approve', 'unpublish' => 'approve', 'review_delete' => 'delete',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('permission');
        $this->load->library('template');
    }

    // =====================================================================
    // LANDING (tab Template | Penilaian)
    // =====================================================================
    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $uid = $data['user']['id'];
        $data['can_create'] = $this->permission->check_permission($uid, 'performance_review', 'create');
        $data['can_edit']   = $this->permission->check_permission($uid, 'performance_review', 'edit');
        $data['can_delete'] = $this->permission->check_permission($uid, 'performance_review', 'delete');
        $data['can_publish'] = $this->permission->check_permission($uid, 'performance_review', 'approve');
        $data['reviewer_roles'] = $this->reviewer_roles;
        $data['employees'] = $this->mymodel->selectWithQuery("SELECT id, full_name FROM user WHERE status != 'deleted' ORDER BY full_name ASC");

        $data['title'] = 'Penilaian Kinerja - ' . $this->template->title();
        $data['content'] = $this->load->view('performance_review/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    // =====================================================================
    // TEMPLATE
    // =====================================================================
    public function templates()
    {
        $uid = $_SESSION['user']['id'];
        $data['can_edit']   = $this->permission->check_permission($uid, 'performance_review', 'edit');
        $data['can_delete'] = $this->permission->check_permission($uid, 'performance_review', 'delete');

        $keyword = $this->db->escape_str($_GET['keyword'] ?? '');
        $where = "1=1";
        if ($keyword !== '') {
            $where .= " AND t.name LIKE '%$keyword%'";
        }
        $data['data'] = $this->mymodel->selectWithQuery("
            SELECT t.*, (SELECT COUNT(*) FROM pa_indicators i WHERE i.template_id = t.id) AS indicator_count
            FROM pa_templates t WHERE $where ORDER BY t.id DESC");
        $this->load->view('performance_review/template_item', $data);
    }

    public function template_create_page()
    {
        $data['user'] = $_SESSION['user'];
        $data['data'] = [];
        $data['indicators'] = [];
        $data['input_types'] = $this->input_types;
        $data['title'] = 'Tambah Template - ' . $this->template->title();
        $data['content'] = $this->load->view('performance_review/template_form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function template_edit_page()
    {
        $id = (int) ($_GET['id'] ?? 0);
        $tpl = $this->mymodel->selectWithQuery("SELECT * FROM pa_templates WHERE id = '$id' LIMIT 1");
        if (empty($tpl)) {
            redirect(base_url() . 'performance_review');
        }
        $data['user'] = $_SESSION['user'];
        $data['data'] = $tpl[0];
        $data['indicators'] = $this->mymodel->selectWithQuery("SELECT * FROM pa_indicators WHERE template_id = '$id' ORDER BY sort_order ASC, id ASC");
        $data['input_types'] = $this->input_types;
        $data['title'] = 'Edit Template - ' . $this->template->title();
        $data['content'] = $this->load->view('performance_review/template_form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function template_store()
    {
        $this->save_template(null);
        echo $this->template->alert_success('Template berhasil disimpan!');
    }

    public function template_update()
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo $this->template->alert_danger('Template tidak ditemukan!');
            return;
        }
        $this->save_template($id);
        echo $this->template->alert_success('Template berhasil diupdate!');
    }

    /**
     * Simpan template + indikator (replace-all). Return template_id.
     */
    private function save_template($id)
    {
        $user = $_SESSION['user'];
        $dt = $_POST['dt'] ?? [];
        $now = date('Y-m-d H:i:s');

        // grade bands -> JSON
        $bands = [];
        if (!empty($_POST['band']) && is_array($_POST['band'])) {
            foreach ($_POST['band'] as $b) {
                if ($b['label'] === '' && $b['min'] === '' && $b['max'] === '') {
                    continue;
                }
                $bands[] = [
                    'min' => $b['min'] !== '' ? (float) $b['min'] : 0,
                    'max' => $b['max'] !== '' ? (float) $b['max'] : 0,
                    'label' => trim($b['label']),
                ];
            }
        }

        $payload = [
            'name' => trim($dt['name'] ?? ''),
            'description' => trim($dt['description'] ?? ''),
            'type_label' => $dt['type_label'] ?? 'Campuran',
            'grade_bands' => !empty($bands) ? json_encode($bands) : null,
            'status' => $dt['status'] ?? 'active',
            'updated_at' => $now,
        ];

        $this->db->trans_start();
        if ($id) {
            $this->db->update('pa_templates', $payload, ['id' => $id]);
        } else {
            $payload['created_by'] = $user['id'];
            $payload['created_at'] = $now;
            $this->db->insert('pa_templates', $payload);
            $id = $this->db->insert_id();
        }

        // indikator: replace-all
        $this->db->delete('pa_indicators', ['template_id' => $id]);
        if (!empty($_POST['ind']) && is_array($_POST['ind'])) {
            $order = 0;
            foreach ($_POST['ind'] as $ind) {
                if (trim($ind['name'] ?? '') === '') {
                    continue;
                }
                $type = in_array($ind['input_type'] ?? '', $this->input_types, true) ? $ind['input_type'] : 'rubric';
                $this->db->insert('pa_indicators', [
                    'template_id' => $id,
                    'category' => trim($ind['category'] ?? ''),
                    'name' => trim($ind['name']),
                    'input_type' => $type,
                    'level1_desc' => $type === 'rubric' ? trim($ind['level1_desc'] ?? '') : null,
                    'level2_desc' => $type === 'rubric' ? trim($ind['level2_desc'] ?? '') : null,
                    'level3_desc' => $type === 'rubric' ? trim($ind['level3_desc'] ?? '') : null,
                    'level4_desc' => $type === 'rubric' ? trim($ind['level4_desc'] ?? '') : null,
                    'standard_score' => ($ind['standard_score'] ?? '') !== '' ? (float) $ind['standard_score'] : null,
                    'max_score' => ($ind['max_score'] ?? '') !== '' ? (float) $ind['max_score'] : $this->default_max($type),
                    'weight' => ($ind['weight'] ?? '') !== '' ? (float) $ind['weight'] : null,
                    'options' => !empty($ind['options']) ? trim($ind['options']) : null,
                    'sort_order' => $order++,
                    'created_at' => $now,
                ]);
            }
        }
        $this->db->trans_complete();
        return $id;
    }

    private function default_max($type)
    {
        switch ($type) {
            case 'yesno': return 1;
            case 'percent': return 100;
            case 'text': return 0;
            default: return 4; // rubric / scale
        }
    }

    public function template_delete()
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo $this->template->alert_danger('ID tidak valid!');
            return;
        }
        $used = $this->mymodel->selectWithQuery("SELECT COUNT(*) c FROM pa_reviews WHERE template_id = '$id'");
        if ((int) $used[0]['c'] > 0) {
            echo $this->template->alert_danger('Template dipakai pada penilaian dan tidak bisa dihapus.');
            return;
        }
        $this->db->trans_start();
        $this->db->delete('pa_indicators', ['template_id' => $id]);
        $this->db->delete('pa_templates', ['id' => $id]);
        $this->db->trans_complete();
        echo $this->template->alert_success('Template dihapus!');
    }

    // =====================================================================
    // REVIEW (penilaian per karyawan)
    // =====================================================================
    public function reviews()
    {
        $uid = $_SESSION['user']['id'];
        $data['can_edit']   = $this->permission->check_permission($uid, 'performance_review', 'edit');
        $data['can_delete'] = $this->permission->check_permission($uid, 'performance_review', 'delete');
        $data['can_publish'] = $this->permission->check_permission($uid, 'performance_review', 'approve');

        $keyword = $this->db->escape_str($_GET['keyword'] ?? '');
        $status_filter = $this->db->escape_str($_GET['status_filter'] ?? '');
        $where = "1=1";
        if ($keyword !== '') {
            $where .= " AND (u.full_name LIKE '%$keyword%' OR t.name LIKE '%$keyword%')";
        }
        if ($status_filter !== '') {
            $where .= " AND r.status = '$status_filter'";
        }
        $data['data'] = $this->mymodel->selectWithQuery("
            SELECT r.*, u.full_name AS reviewee_name, t.name AS template_name,
                   (SELECT COUNT(*) FROM pa_assignments a WHERE a.review_id = r.id AND a.is_active = 1) AS reviewer_count,
                   (SELECT COUNT(*) FROM pa_assignments a WHERE a.review_id = r.id AND a.status='submitted' AND a.is_active = 1) AS submitted_count
            FROM pa_reviews r
            LEFT JOIN user u ON u.id = r.reviewee_id
            LEFT JOIN pa_templates t ON t.id = r.template_id
            WHERE $where ORDER BY r.id DESC");

        $reviewer_map = [];
        $review_ids = array_map(function ($row) {
            return (int) $row['id'];
        }, $data['data']);
        if (!empty($review_ids)) {
            $ids = implode(',', $review_ids);
            $assignments = $this->mymodel->selectWithQuery("
                SELECT a.id, a.review_id, a.reviewer_role, a.status, a.is_active, u.full_name AS reviewer_name
                FROM pa_assignments a
                LEFT JOIN user u ON u.id = a.reviewer_id
                WHERE a.review_id IN ($ids)
                ORDER BY a.is_active DESC, a.status DESC, a.id ASC");
            foreach ($assignments as $assignment) {
                $reviewer_map[(int) $assignment['review_id']][] = $assignment;
            }
        }
        $data['reviewer_map'] = $reviewer_map;
        $this->load->view('performance_review/review_item', $data);
    }

    public function review_create_page()
    {
        $data['user'] = $_SESSION['user'];
        $data['templates'] = $this->mymodel->selectWithQuery("SELECT id, name FROM pa_templates WHERE status='active' ORDER BY name ASC");
        $data['employees'] = $this->mymodel->selectWithQuery("SELECT id, full_name FROM user WHERE status != 'deleted' ORDER BY full_name ASC");
        $data['title'] = 'Buat Penilaian - ' . $this->template->title();
        $data['content'] = $this->load->view('performance_review/review_form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function review_store()
    {
        $user = $_SESSION['user'];
        $dt = $_POST['dt'] ?? [];
        $template_id = (int) ($dt['template_id'] ?? 0);
        $reviewee_id = (int) ($dt['reviewee_id'] ?? 0);
        $quarter = (int) ($dt['period_quarter'] ?? 0);
        $year = (int) ($dt['period_year'] ?? 0);

        if ($template_id <= 0 || $reviewee_id <= 0 || $quarter < 1 || $quarter > 4 || $year < 2000) {
            echo $this->template->alert_danger('Template, karyawan, dan periode (kuartal/tahun) wajib diisi!');
            return;
        }

        $label = !empty($dt['period_label']) ? trim($dt['period_label']) : $this->period_label($quarter, $year);

        $this->db->insert('pa_reviews', [
            'template_id' => $template_id,
            'reviewee_id' => $reviewee_id,
            'period_quarter' => $quarter,
            'period_year' => $year,
            'period_label' => $label,
            'status' => 'draft',
            'created_by' => $user['id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $review_id = $this->db->insert_id();
        // sisipkan id via komentar HTML (tak terlihat) agar form bisa redirect ke detail
        echo $this->template->alert_success('Penilaian dibuat!') . '<!--id:' . $review_id . '-->';
    }

    private function period_label($q, $year)
    {
        $map = [1 => 'Januari - Maret', 2 => 'April - Juni', 3 => 'Juli - September', 4 => 'Oktober - Desember'];
        return 'Q' . $q . ' (' . ($map[$q] ?? '') . ' ' . $year . ')';
    }

    /**
     * Detail review: assignment reviewer + referensi isian + ringkasan final.
     */
    public function review_detail()
    {
        $id = (int) ($_GET['id'] ?? 0);
        $review = $this->get_review_full($id);
        if (!$review) {
            redirect(base_url() . 'performance_review');
        }
        $uid = $_SESSION['user']['id'];

        $data['user'] = $_SESSION['user'];
        $data['can_edit'] = $this->permission->check_permission($uid, 'performance_review', 'edit');
        $data['can_publish'] = $this->permission->check_permission($uid, 'performance_review', 'approve');
        $data['review'] = $review;
        $data['indicators'] = $this->get_indicators($review['template_id']);
        $data['assignments'] = $this->mymodel->selectWithQuery("
            SELECT a.*, u.full_name AS reviewer_name
            FROM pa_assignments a LEFT JOIN user u ON u.id = a.reviewer_id
            WHERE a.review_id = '$id' ORDER BY a.id ASC");

        // skor reviewer (referensi) di-index [assignment_id][indicator_id]
        $ref = $this->mymodel->selectWithQuery("SELECT * FROM pa_scores WHERE review_id = '$id' AND is_final = 0");
        $ref_map = [];
        foreach ($ref as $s) {
            $ref_map[$s['assignment_id']][$s['indicator_id']] = $s;
        }
        $data['reference_scores'] = $ref_map;

        // skor final
        $data['final_scores'] = $this->get_final_scores($id);
        $data['reviewer_roles'] = $this->reviewer_roles;
        $data['employees'] = $this->mymodel->selectWithQuery("SELECT id, full_name FROM user WHERE status != 'deleted' ORDER BY full_name ASC");

        $data['title'] = 'Detail Penilaian - ' . $this->template->title();
        $data['content'] = $this->load->view('performance_review/review_detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function assign_reviewer()
    {
        $review_id = (int) ($_POST['review_id'] ?? 0);
        $reviewer_id = (int) ($_POST['reviewer_id'] ?? 0);
        $role = in_array($_POST['reviewer_role'] ?? '', $this->reviewer_roles, true) ? $_POST['reviewer_role'] : 'peer';
        if ($review_id <= 0 || $reviewer_id <= 0) {
            echo $this->template->alert_danger('Reviewer tidak valid!');
            return;
        }
        $dupe = $this->mymodel->selectWithQuery("SELECT id, is_active FROM pa_assignments WHERE review_id='$review_id' AND reviewer_id='$reviewer_id' LIMIT 1");
        if (!empty($dupe)) {
            if ((int) ($dupe[0]['is_active'] ?? 1) === 0) {
                $this->db->update('pa_assignments', [
                    'reviewer_role' => $role,
                    'is_active' => 1,
                ], ['id' => (int) $dupe[0]['id']]);
                echo $this->template->alert_success('Reviewer diaktifkan lagi!');
                return;
            }
            echo $this->template->alert_danger('Reviewer ini sudah aktif ditugaskan.');
            return;
        }
        $this->db->insert('pa_assignments', [
            'review_id' => $review_id,
            'reviewer_id' => $reviewer_id,
            'reviewer_role' => $role,
            'status' => 'pending',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        // status review -> in_progress
        $this->db->update('pa_reviews', ['status' => 'in_progress', 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $review_id, 'status' => 'draft']);

        $review = $this->mymodel->selectWithQuery("SELECT r.period_label, u.full_name FROM pa_reviews r LEFT JOIN user u ON u.id=r.reviewee_id WHERE r.id='$review_id' LIMIT 1");
        $label = !empty($review) ? ($review[0]['period_label'] . ' a/n ' . $review[0]['full_name']) : '';
        $this->push_notif($reviewer_id, 'Tugas penilaian kinerja', 'Anda ditugaskan mengisi penilaian kinerja: ' . $label . '. Buka menu Penilaian Saya.', 'info');

        echo $this->template->alert_success('Reviewer ditambahkan!');
    }

    public function unassign_reviewer()
    {
        $id = (int) ($_POST['id'] ?? 0);
        $row = $this->mymodel->selectWithQuery("SELECT review_id FROM pa_assignments WHERE id='$id' LIMIT 1");
        if (empty($row)) {
            echo $this->template->alert_danger('Assignment tidak ditemukan!');
            return;
        }
        $this->db->trans_start();
        $this->db->delete('pa_scores', ['assignment_id' => $id, 'is_final' => 0]);
        $this->db->delete('pa_assignments', ['id' => $id]);
        $this->db->trans_complete();
        echo $this->template->alert_success('Reviewer dihapus!');
    }

    public function reviewer_assignments()
    {
        $review_id = (int) ($_GET['review_id'] ?? 0);
        if ($review_id <= 0) {
            echo '<div class="text-muted small">Penilaian tidak valid.</div>';
            return;
        }

        $data['assignments'] = $this->mymodel->selectWithQuery("
            SELECT a.*, u.full_name AS reviewer_name
            FROM pa_assignments a
            LEFT JOIN user u ON u.id = a.reviewer_id
            WHERE a.review_id = '$review_id'
            ORDER BY a.is_active DESC, a.id ASC");
        $data['roleLabel'] = ['self' => 'Self (Karyawan)', 'leader' => 'Leader', 'hr' => 'HR', 'peer' => 'Peer'];
        $this->load->view('performance_review/reviewer_assignments_list', $data);
    }

    public function toggle_reviewer_assignment()
    {
        $id = (int) ($_POST['id'] ?? 0);
        $is_active = (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
        $row = $this->mymodel->selectWithQuery("SELECT id FROM pa_assignments WHERE id='$id' LIMIT 1");
        if (empty($row)) {
            echo $this->template->alert_danger('Assignment tidak ditemukan!');
            return;
        }

        $this->db->update('pa_assignments', [
            'is_active' => $is_active,
        ], ['id' => $id]);

        echo $this->template->alert_success($is_active ? 'Reviewer diaktifkan!' : 'Reviewer dinonaktifkan!');
    }

    // =====================================================================
    // FINAL (HR menyusun penilaian akhir)
    // =====================================================================
    public function final_page()
    {
        $id = (int) ($_GET['id'] ?? 0);
        $review = $this->get_review_full($id);
        if (!$review) {
            redirect(base_url() . 'performance_review');
        }
        $data['user'] = $_SESSION['user'];
        $data['review'] = $review;
        $data['indicators'] = $this->get_indicators($review['template_id']);
        $data['final_scores'] = $this->get_final_scores($id);

        // referensi reviewer untuk bantu HR
        $data['assignments'] = $this->mymodel->selectWithQuery("
            SELECT a.*, u.full_name AS reviewer_name FROM pa_assignments a LEFT JOIN user u ON u.id=a.reviewer_id
            WHERE a.review_id='$id' ORDER BY a.id ASC");
        $ref = $this->mymodel->selectWithQuery("SELECT * FROM pa_scores WHERE review_id='$id' AND is_final=0");
        $ref_map = [];
        foreach ($ref as $s) {
            $ref_map[$s['assignment_id']][$s['indicator_id']] = $s;
        }
        $data['reference_scores'] = $ref_map;

        $data['title'] = 'Penilaian Final - ' . $this->template->title();
        $data['content'] = $this->load->view('performance_review/final_form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function final_store()
    {
        $review_id = (int) ($_POST['review_id'] ?? 0);
        $review = $this->mymodel->selectWithQuery("SELECT * FROM pa_reviews WHERE id='$review_id' LIMIT 1");
        if (empty($review)) {
            echo $this->template->alert_danger('Penilaian tidak ditemukan!');
            return;
        }
        $review = $review[0];
        $scores = $_POST['score'] ?? [];
        $texts = $_POST['answer'] ?? [];
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();
        $this->db->delete('pa_scores', ['review_id' => $review_id, 'is_final' => 1]);
        foreach ($scores as $indicator_id => $val) {
            $indicator_id = (int) $indicator_id;
            $this->db->insert('pa_scores', [
                'review_id' => $review_id,
                'assignment_id' => null,
                'indicator_id' => $indicator_id,
                'score' => ($val !== '') ? (float) $val : null,
                'answer_text' => isset($texts[$indicator_id]) ? trim($texts[$indicator_id]) : null,
                'is_final' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        // indikator tipe teks (tanpa skor) tetap simpan keterangan
        foreach ($texts as $indicator_id => $txt) {
            $indicator_id = (int) $indicator_id;
            if (isset($scores[$indicator_id])) {
                continue;
            }
            $this->db->insert('pa_scores', [
                'review_id' => $review_id, 'assignment_id' => null, 'indicator_id' => $indicator_id,
                'score' => null, 'answer_text' => trim($txt), 'is_final' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $totals = $this->compute_totals($review_id, $review['template_id']);
        $this->db->update('pa_reviews', [
            'final_total' => $totals['total'],
            'final_target' => $totals['target'],
            'final_grade' => $totals['grade'],
            'sources' => trim($_POST['sources'] ?? ''),
            'status' => ($review['status'] === 'published') ? 'published' : 'completed',
            'updated_at' => $now,
        ], ['id' => $review_id]);
        $this->db->trans_complete();

        echo $this->template->alert_success('Penilaian final disimpan!');
    }

    // =====================================================================
    // PUBLISH
    // =====================================================================
    public function publish()
    {
        $id = (int) ($_POST['id'] ?? 0);
        $review = $this->mymodel->selectWithQuery("SELECT * FROM pa_reviews WHERE id='$id' LIMIT 1");
        if (empty($review)) {
            echo $this->template->alert_danger('Penilaian tidak ditemukan!');
            return;
        }
        $review = $review[0];
        $has_final = $this->mymodel->selectWithQuery("SELECT COUNT(*) c FROM pa_scores WHERE review_id='$id' AND is_final=1");
        if ((int) $has_final[0]['c'] === 0) {
            echo $this->template->alert_danger('Isi penilaian final terlebih dahulu sebelum publish.');
            return;
        }
        $this->db->update('pa_reviews', [
            'status' => 'published',
            'published_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        $this->push_notif((int) $review['reviewee_id'], 'Hasil Penilaian Kinerja terbit',
            'Hasil penilaian kinerja Anda untuk ' . $review['period_label'] . ' telah diterbitkan. Lihat di Profil Anda.',
            'info');

        echo $this->template->alert_success('Penilaian dipublish ke karyawan!');
    }

    public function unpublish()
    {
        $id = (int) ($_POST['id'] ?? 0);
        $this->db->update('pa_reviews', ['status' => 'completed', 'published_at' => null, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $id]);
        echo $this->template->alert_success('Publish dibatalkan.');
    }

    public function review_delete()
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo $this->template->alert_danger('ID tidak valid!');
            return;
        }
        $this->db->trans_start();
        $this->db->delete('pa_scores', ['review_id' => $id]);
        $this->db->delete('pa_assignments', ['review_id' => $id]);
        $this->db->delete('pa_reviews', ['id' => $id]);
        $this->db->trans_complete();
        echo $this->template->alert_success('Penilaian dihapus!');
    }

    // =====================================================================
    // REVIEWER-FACING (publik dari gate modul, cek manual)
    // =====================================================================
    public function my_assignments()
    {
        $this->require_login();
        $uid = (int) $_SESSION['user']['id'];
        $data['user'] = $_SESSION['user'];
        $data['assignments'] = $this->mymodel->selectWithQuery("
            SELECT a.*, r.period_label, r.status AS review_status, t.name AS template_name, u.full_name AS reviewee_name
            FROM pa_assignments a
            LEFT JOIN pa_reviews r ON r.id = a.review_id
            LEFT JOIN pa_templates t ON t.id = r.template_id
            LEFT JOIN user u ON u.id = r.reviewee_id
            WHERE a.reviewer_id = '$uid'
              AND a.is_active = 1
            ORDER BY a.status ASC, a.id DESC");
        $data['title'] = 'Penilaian Saya - ' . $this->template->title();
        $data['content'] = $this->load->view('performance_review/my_assignments', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function fill()
    {
        $this->require_login();
        $assignment_id = (int) ($this->uri->segment(3) ?: ($_GET['id'] ?? 0));
        $a = $this->mymodel->selectWithQuery("SELECT * FROM pa_assignments WHERE id='$assignment_id' LIMIT 1");
        if (empty($a)) {
            show_404();
            return;
        }
        $a = $a[0];
        $uid = (int) $_SESSION['user']['id'];
        $is_hr = $this->permission->check_permission($uid, 'performance_review', 'edit');
        if ((int) $a['reviewer_id'] !== $uid && !$is_hr) {
            $this->output->set_status_header(403);
            echo 'Bukan reviewer untuk penilaian ini.';
            return;
        }
        if ((int) ($a['is_active'] ?? 1) !== 1 && !$is_hr) {
            $this->output->set_status_header(403);
            echo 'Assignment penilaian ini sudah nonaktif.';
            return;
        }
        $review = $this->get_review_full((int) $a['review_id']);
        $data['user'] = $_SESSION['user'];
        $data['assignment'] = $a;
        $data['review'] = $review;
        $data['indicators'] = $this->get_indicators($review['template_id']);
        $existing = $this->mymodel->selectWithQuery("SELECT * FROM pa_scores WHERE review_id='{$a['review_id']}' AND assignment_id='$assignment_id'");
        $map = [];
        foreach ($existing as $s) {
            $map[$s['indicator_id']] = $s;
        }
        $data['scores'] = $map;
        $data['title'] = 'Isi Penilaian - ' . $this->template->title();
        $data['content'] = $this->load->view('performance_review/fill_form', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function submit_fill()
    {
        $this->require_login();
        $assignment_id = (int) ($_POST['assignment_id'] ?? 0);
        $a = $this->mymodel->selectWithQuery("SELECT * FROM pa_assignments WHERE id='$assignment_id' LIMIT 1");
        if (empty($a)) {
            echo $this->template->alert_danger('Assignment tidak ditemukan!');
            return;
        }
        $a = $a[0];
        $uid = (int) $_SESSION['user']['id'];
        $is_hr = $this->permission->check_permission($uid, 'performance_review', 'edit');
        if ((int) $a['reviewer_id'] !== $uid && !$is_hr) {
            echo $this->template->alert_danger('Anda bukan reviewer untuk penilaian ini.');
            return;
        }
        if ((int) ($a['is_active'] ?? 1) !== 1 && !$is_hr) {
            echo $this->template->alert_danger('Assignment penilaian ini sudah nonaktif.');
            return;
        }
        $review_id = (int) $a['review_id'];
        $scores = $_POST['score'] ?? [];
        $texts = $_POST['answer'] ?? [];
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();
        $this->db->delete('pa_scores', ['review_id' => $review_id, 'assignment_id' => $assignment_id, 'is_final' => 0]);
        $indicator_ids = array_unique(array_merge(array_keys($scores), array_keys($texts)));
        foreach ($indicator_ids as $indicator_id) {
            $indicator_id = (int) $indicator_id;
            $this->db->insert('pa_scores', [
                'review_id' => $review_id,
                'assignment_id' => $assignment_id,
                'indicator_id' => $indicator_id,
                'score' => (isset($scores[$indicator_id]) && $scores[$indicator_id] !== '') ? (float) $scores[$indicator_id] : null,
                'answer_text' => isset($texts[$indicator_id]) ? trim($texts[$indicator_id]) : null,
                'is_final' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->db->update('pa_assignments', ['status' => 'submitted', 'submitted_at' => $now], ['id' => $assignment_id]);
        $this->db->trans_complete();

        echo $this->template->alert_success('Penilaian Anda tersimpan!');
    }

    public function reviewer_answer()
    {
        $this->require_login();
        $assignment_id = (int) ($this->uri->segment(3) ?: ($_GET['id'] ?? 0));
        $assignment = $this->mymodel->selectWithQuery("
            SELECT a.*, u.full_name AS reviewer_name
            FROM pa_assignments a
            LEFT JOIN user u ON u.id = a.reviewer_id
            WHERE a.id = '$assignment_id'
            LIMIT 1");
        if (empty($assignment)) {
            show_404();
            return;
        }
        $assignment = $assignment[0];

        $uid = (int) $_SESSION['user']['id'];
        $can_view = $this->permission->check_permission($uid, 'performance_review', 'view');
        if ((int) $assignment['reviewer_id'] !== $uid && !$can_view) {
            $this->output->set_status_header(403);
            echo 'Tidak diizinkan melihat jawaban reviewer ini.';
            return;
        }
        if ((int) ($assignment['is_active'] ?? 1) !== 1 && (int) $assignment['reviewer_id'] === $uid && !$can_view) {
            $this->output->set_status_header(403);
            echo 'Assignment penilaian ini sudah nonaktif.';
            return;
        }

        $review = $this->get_review_full((int) $assignment['review_id']);
        if (!$review) {
            show_404();
            return;
        }

        $existing = $this->mymodel->selectWithQuery("
            SELECT * FROM pa_scores
            WHERE review_id = '" . (int) $assignment['review_id'] . "'
            AND assignment_id = '$assignment_id'
            AND is_final = 0");
        $scores = [];
        foreach ($existing as $score) {
            $scores[(int) $score['indicator_id']] = $score;
        }

        $data['user'] = $_SESSION['user'];
        $data['assignment'] = $assignment;
        $data['review'] = $review;
        $data['indicators'] = $this->get_indicators($review['template_id']);
        $data['scores'] = $scores;
        $data['title'] = 'Jawaban Reviewer - ' . $this->template->title();
        $data['content'] = $this->load->view('performance_review/reviewer_answer', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    // =====================================================================
    // PDF (HR atau reviewee setelah publish)
    // =====================================================================
    public function pdf()
    {
        $this->require_login();
        $id = (int) ($this->uri->segment(3) ?: ($_GET['id'] ?? 0));
        $review = $this->get_review_full($id);
        if (!$review) {
            show_404();
            return;
        }
        $uid = (int) $_SESSION['user']['id'];
        $is_hr = $this->permission->check_permission($uid, 'performance_review', 'view');
        $is_owner = ((int) $review['reviewee_id'] === $uid) && ($review['status'] === 'published');
        if (!$is_hr && !$is_owner) {
            $this->output->set_status_header(403);
            echo 'Tidak diizinkan.';
            return;
        }

        $indicators = $this->get_indicators($review['template_id']);
        $finals = $this->get_final_scores($id);
        $totals = $this->compute_totals($id, $review['template_id']);
        $bands = !empty($review['grade_bands']) ? json_decode($review['grade_bands'], true) : [];

        $approvers = $this->mymodel->selectWithQuery("
            SELECT a.reviewer_role, u.full_name FROM pa_assignments a LEFT JOIN user u ON u.id=a.reviewer_id
            WHERE a.review_id='$id' AND a.reviewer_role IN ('leader','hr') ORDER BY a.reviewer_role");

        $html = $this->load->view('performance_review/pdf', [
            'review' => $review,
            'indicators' => $indicators,
            'finals' => $finals,
            'totals' => $totals,
            'bands' => $bands,
            'approvers' => $approvers,
        ], true);

        $safe_name = preg_replace('/[^A-Za-z0-9]+/', '-', $review['reviewee_name'] ?? 'karyawan');
        $safe_period = preg_replace('/[^A-Za-z0-9]+/', '-', $review['period_label'] ?? '');
        $filename = 'penilaian-' . trim($safe_name, '-') . '-' . trim($safe_period, '-') . '.pdf';
        $this->stream_pdf($html, $filename, isset($_GET['dl']));
    }

    // =====================================================================
    // HELPERS
    // =====================================================================
    private function require_login()
    {
        if (empty($_SESSION['user'])) {
            redirect(base_url('auth/login'));
            exit;
        }
    }

    private function get_review_full($id)
    {
        $id = (int) $id;
        $r = $this->mymodel->selectWithQuery("
            SELECT r.*, u.full_name AS reviewee_name, u.email AS reviewee_email,
                   t.name AS template_name, t.grade_bands, t.type_label,
                   p.name AS position_name
            FROM pa_reviews r
            LEFT JOIN user u ON u.id = r.reviewee_id
            LEFT JOIN pa_templates t ON t.id = r.template_id
            LEFT JOIN user_profile up ON up.user_id = r.reviewee_id
            LEFT JOIN positions p ON p.id = up.position_id
            WHERE r.id = '$id' LIMIT 1");
        return !empty($r) ? $r[0] : null;
    }

    private function get_indicators($template_id)
    {
        $template_id = (int) $template_id;
        return $this->mymodel->selectWithQuery("SELECT * FROM pa_indicators WHERE template_id='$template_id' ORDER BY sort_order ASC, id ASC");
    }

    private function get_final_scores($review_id)
    {
        $review_id = (int) $review_id;
        $rows = $this->mymodel->selectWithQuery("SELECT * FROM pa_scores WHERE review_id='$review_id' AND is_final=1");
        $map = [];
        foreach ($rows as $s) {
            $map[$s['indicator_id']] = $s;
        }
        return $map;
    }

    /**
     * total = sum skor final tipe numerik; target = sum standar; grade via grade_bands (% dari target).
     */
    private function compute_totals($review_id, $template_id)
    {
        $indicators = $this->get_indicators($template_id);
        $finals = $this->get_final_scores($review_id);
        $total = 0;
        $target = 0;
        foreach ($indicators as $ind) {
            if ($ind['input_type'] === 'text') {
                continue;
            }
            if (isset($finals[$ind['id']]) && $finals[$ind['id']]['score'] !== null) {
                $total += (float) $finals[$ind['id']]['score'];
            }
            if ($ind['standard_score'] !== null) {
                $target += (float) $ind['standard_score'];
            }
        }
        $grade = null;
        $tpl = $this->mymodel->selectWithQuery("SELECT grade_bands FROM pa_templates WHERE id='" . (int) $template_id . "' LIMIT 1");
        if (!empty($tpl) && !empty($tpl[0]['grade_bands'])) {
            $bands = json_decode($tpl[0]['grade_bands'], true);
            $pct = $target > 0 ? ($total / $target) * 100 : $total;
            foreach ((array) $bands as $b) {
                if ($pct >= (float) $b['min'] && $pct <= (float) $b['max']) {
                    $grade = $b['label'];
                    break;
                }
            }
        }
        return ['total' => $total, 'target' => $target, 'grade' => $grade];
    }

    private function push_notif($user_id, $title, $message, $type = 'info')
    {
        $this->db->insert('notifications', [
            'user_id' => (int) $user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'category' => 'Team',
            'subcategory' => 'performance_review',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function stream_pdf($html, $filename, $download = false)
    {
        $autoload = FCPATH . 'vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        if (class_exists('\\Dompdf\\Dompdf') && class_exists('\\Dompdf\\Options')) {
            try {
                $cache_dir = APPPATH . 'cache/dompdf';
                if (!is_dir($cache_dir)) {
                    @mkdir($cache_dir, 0755, true);
                }

                $options = new \Dompdf\Options();
                $options->set('isRemoteEnabled', true);
                $options->set('isHtml5ParserEnabled', true);
                $options->setDefaultFont('DejaVu Sans');
                if (is_dir($cache_dir) && is_writable($cache_dir)) {
                    $options->setTempDir($cache_dir);
                    $options->setFontCache($cache_dir);
                }

                $dompdf = new \Dompdf\Dompdf($options);
                $dompdf->loadHtml($html, 'UTF-8');
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $dompdf->stream($filename, ['Attachment' => $download]);
                return;
            } catch (\Throwable $e) {
                log_message('error', 'Performance Review PDF Dompdf error: ' . $e->getMessage());
            } catch (Exception $e) {
                log_message('error', 'Performance Review PDF Dompdf error: ' . $e->getMessage());
            }
        } else {
            log_message('error', 'Performance Review PDF error: Dompdf class not available.');
        }

        if (class_exists('\\TCPDF')) {
            try {
                $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
                $pdf->SetCreator('BHSKIN');
                $pdf->SetAuthor('BHSKIN');
                $pdf->SetTitle($filename);
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->SetMargins(8, 8, 8);
                $pdf->SetAutoPageBreak(true, 8);
                $pdf->AddPage();
                $pdf->writeHTML($html, true, false, true, false, '');
                $pdf->Output($filename, $download ? 'D' : 'I');
                return;
            } catch (\Throwable $e) {
                log_message('error', 'Performance Review PDF TCPDF error: ' . $e->getMessage());
            } catch (Exception $e) {
                log_message('error', 'Performance Review PDF TCPDF error: ' . $e->getMessage());
            }
        }

        $this->output
            ->set_content_type('text/html', 'UTF-8')
            ->set_output($html);
    }
}
