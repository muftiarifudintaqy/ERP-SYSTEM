<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Onboarding extends BaseController
{
    private $task_template_fields = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');
        $this->load->library('permission');

        $this->set_public_methods([]);
        $this->set_method_permissions([
            'add_employees' => 'create',
            'save_task_settings' => 'edit',
            'remove_employee' => 'delete',
            'save_task_item' => 'edit',
            'delete_task_item' => 'delete',
            'add_custom_task' => 'create',
            'update_detail_task_status' => 'edit'
        ]);
    }

    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];

        $data['can_create'] = $this->permission->check_permission($user_id, 'user', 'create');
        $data['can_edit'] = $this->permission->check_permission($user_id, 'user', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'user', 'delete');

        $data['schema_ready'] = $this->transition_tables_ready();
        $data['active_tab'] = $this->input->get('tab') === 'offboarding' ? 'offboarding' : 'onboarding';

        $flash = $_SESSION['onboarding_flash'] ?? null;
        unset($_SESSION['onboarding_flash']);
        $data['flash'] = $flash;

        $data['transitions_onboarding'] = [];
        $data['transitions_offboarding'] = [];
        $data['task_templates_onboarding'] = [];
        $data['task_templates_offboarding'] = [];
        $data['employees'] = [];

        if ($data['schema_ready']) {
            $this->ensure_default_task_templates();

            $keyword_onboarding = trim((string) $this->input->get('keyword_onboarding'));
            $status_onboarding = (string) $this->input->get('status_onboarding');
            $keyword_offboarding = trim((string) $this->input->get('keyword_offboarding'));
            $status_offboarding = (string) $this->input->get('status_offboarding');

            $data['keyword_onboarding'] = $keyword_onboarding;
            $data['status_onboarding'] = $status_onboarding;
            $data['keyword_offboarding'] = $keyword_offboarding;
            $data['status_offboarding'] = $status_offboarding;

            $data['transitions_onboarding'] = $this->get_transitions('onboarding', $keyword_onboarding, $status_onboarding);
            $data['transitions_offboarding'] = $this->get_transitions('offboarding', $keyword_offboarding, $status_offboarding);

            $data['task_templates_onboarding'] = $this->get_task_templates('onboarding');
            $data['task_templates_offboarding'] = $this->get_task_templates('offboarding');

            $data['employees'] = $this->mymodel->selectWithQuery("\n                SELECT u.id, u.full_name, u.email,\n                       COALESCE(p.name, '-') as position_name\n                FROM user u\n                LEFT JOIN user_profile up ON up.user_id = u.id\n                LEFT JOIN positions p ON p.id = up.position_id\n                WHERE u.id IS NOT NULL\n                ORDER BY u.full_name ASC\n            ");
        } else {
            $data['keyword_onboarding'] = '';
            $data['status_onboarding'] = '';
            $data['keyword_offboarding'] = '';
            $data['status_offboarding'] = '';
        }

        $data['title'] = 'Onboarding & Offboarding - ' . $this->template->title();
        $data['content'] = $this->load->view('onboarding/all', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function task_settings()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];
        $data['can_edit'] = $this->permission->check_permission($user_id, 'user', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'user', 'delete');
        $data['schema_ready'] = $this->transition_tables_ready();
        $data['transition_type'] = $this->normalize_transition_type($this->input->get('type'));

        $flash = $_SESSION['onboarding_flash'] ?? null;
        unset($_SESSION['onboarding_flash']);
        $data['flash'] = $flash;
        $data['tasks'] = [];

        if ($data['schema_ready']) {
            $data['tasks'] = $this->get_task_templates($data['transition_type']);
        }

        $data['title'] = 'Task Settings ' . ucfirst($data['transition_type']) . ' - ' . $this->template->title();
        $data['content'] = $this->load->view('onboarding/task_settings', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function add_employees()
    {
        if (!$this->transition_tables_ready()) {
            $this->set_flash('danger', 'Tabel onboarding/offboarding belum tersedia. Jalankan SQL schema terlebih dahulu.');
            redirect(base_url('onboarding'));
            return;
        }

        $transition_type = $this->input->post('transition_type') === 'offboarding' ? 'offboarding' : 'onboarding';
        $user_ids = $this->input->post('user_ids');

        if (empty($user_ids) || !is_array($user_ids)) {
            $this->set_flash('warning', 'Pilih minimal 1 karyawan terlebih dahulu.');
            redirect(base_url('onboarding?tab=' . $transition_type));
            return;
        }

        $inserted = 0;
        $updated = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($user_ids as $user_id) {
            $user_id = (int) $user_id;
            if ($user_id <= 0) {
                continue;
            }

            $user_exists = $this->db->get_where('user', ['id' => $user_id])->row_array();
            if (empty($user_exists)) {
                continue;
            }

            $existing = $this->db->get_where('employee_transition', [
                'user_id' => $user_id,
                'transition_type' => $transition_type
            ])->row_array();

            if (!empty($existing)) {
                $this->db->update('employee_transition', [
                    'status' => $existing['status'] === 'completed' ? 'completed' : 'ready',
                    'invitation_date' => date('Y-m-d'),
                    'last_updated_at' => $now,
                    'updated_at' => $now
                ], ['id' => $existing['id']]);
                $updated++;
                continue;
            }

            $this->db->insert('employee_transition', [
                'user_id' => $user_id,
                'transition_type' => $transition_type,
                'status' => 'ready',
                'invitation_date' => date('Y-m-d'),
                'last_updated_at' => $now,
                'created_by' => $_SESSION['user']['id'] ?? null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
            $inserted++;
        }

        $this->set_flash('success', "Berhasil menambahkan {$inserted} data dan memperbarui {$updated} data {$transition_type}.");
        redirect(base_url('onboarding?tab=' . $transition_type));
    }

    public function save_task_settings()
    {
        if (!$this->transition_tables_ready()) {
            $this->set_flash('danger', 'Tabel onboarding/offboarding belum tersedia. Jalankan SQL schema terlebih dahulu.');
            redirect(base_url('onboarding'));
            return;
        }

        $transition_type = $this->input->post('transition_type') === 'offboarding' ? 'offboarding' : 'onboarding';
        $tasks = $this->input->post('tasks');
        $tasks = is_array($tasks) ? $tasks : [];

        $existing = $this->db->select('id')
            ->from('employee_transition_task_template')
            ->where('transition_type', $transition_type)
            ->get()
            ->result_array();

        $existing_ids = array_map('intval', array_column($existing, 'id'));
        $submitted_ids = [];
        $now = date('Y-m-d H:i:s');
        $upload_errors = [];

        $has_description = $this->task_template_has_field('description_html');
        $has_attachment = $this->task_template_has_field('attachment_path') && $this->task_template_has_field('attachment_name');

        foreach ($tasks as $index => $task) {
            $task_name = trim((string) ($task['task_name'] ?? ''));
            if ($task_name === '') {
                continue;
            }

            $task_id = (int) ($task['id'] ?? 0);
            $sort_order = (int) ($task['sort_order'] ?? ($index + 1));
            $is_required = isset($task['is_required']) ? 1 : 0;
            $is_active = isset($task['is_active']) ? 1 : 0;
            $description_html = (string) ($task['description_html'] ?? '');
            $existing_attachment_path = trim((string) ($task['existing_attachment_path'] ?? ''));
            $existing_attachment_name = trim((string) ($task['existing_attachment_name'] ?? ''));

            $data = [
                'transition_type' => $transition_type,
                'task_name' => $task_name,
                'sort_order' => $sort_order,
                'is_required' => $is_required,
                'is_active' => $is_active,
                'updated_at' => $now
            ];

            if ($has_description) {
                $data['description_html'] = trim($description_html) === '' ? null : $description_html;
            }

            if ($has_attachment) {
                $data['attachment_path'] = $existing_attachment_path !== '' ? $existing_attachment_path : null;
                $data['attachment_name'] = $existing_attachment_name !== '' ? $existing_attachment_name : null;

                $upload_result = $this->upload_task_attachment((int) $index);
                if (!empty($upload_result['error'])) {
                    $upload_errors[] = $task_name . ': ' . $upload_result['error'];
                } elseif (!empty($upload_result['path'])) {
                    if (!empty($data['attachment_path']) && $data['attachment_path'] !== $upload_result['path']) {
                        $this->remove_attachment_file($data['attachment_path']);
                    }
                    $data['attachment_path'] = $upload_result['path'];
                    $data['attachment_name'] = $upload_result['name'];
                }
            }

            if ($task_id > 0 && in_array($task_id, $existing_ids, true)) {
                $this->db->update('employee_transition_task_template', $data, ['id' => $task_id]);
                $submitted_ids[] = $task_id;
            } else {
                $data['created_at'] = $now;
                $this->db->insert('employee_transition_task_template', $data);
                $submitted_ids[] = (int) $this->db->insert_id();
            }
        }

        $deleted_ids = array_diff($existing_ids, $submitted_ids);
        if (!empty($deleted_ids)) {
            $this->db->where_in('id', $deleted_ids);
            $this->db->update('employee_transition_task_template', [
                'is_active' => 0,
                'updated_at' => $now
            ]);
        }

        $this->refresh_all_transition_status($transition_type);

        if (!empty($upload_errors)) {
            $this->set_flash('warning', 'Task settings tersimpan, tetapi ada lampiran yang gagal diupload: ' . implode(' | ', $upload_errors));
        } else {
            $this->set_flash('success', 'Task settings berhasil disimpan.');
        }
        redirect(base_url('onboarding?tab=' . $transition_type));
    }

    public function remove_employee()
    {
        if (!$this->transition_tables_ready()) {
            $this->set_flash('danger', 'Tabel onboarding/offboarding belum tersedia. Jalankan SQL schema terlebih dahulu.');
            redirect(base_url('onboarding'));
            return;
        }

        $transition_id = (int) $this->input->post('transition_id');
        $transition_type = $this->input->post('transition_type') === 'offboarding' ? 'offboarding' : 'onboarding';

        if ($transition_id > 0) {
            $this->db->update('employee_transition', [
                'status' => 'cancelled',
                'last_updated_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $transition_id]);
            $this->set_flash('success', 'Karyawan berhasil dihapus dari daftar.');
        } else {
            $this->set_flash('warning', 'Data transition tidak valid.');
        }

        redirect(base_url('onboarding?tab=' . $transition_type));
    }

    public function save_task_item()
    {
        if (!$this->transition_tables_ready()) {
            $this->set_flash('danger', 'Tabel onboarding/offboarding belum tersedia. Jalankan SQL schema terlebih dahulu.');
            redirect(base_url('onboarding'));
            return;
        }

        $transition_type = $this->normalize_transition_type($this->input->post('transition_type'));
        $id = (int) $this->input->post('id');
        $task_name = trim((string) $this->input->post('task_name'));
        $description_html = (string) $this->input->post('description_html');
        $is_required = $this->input->post('is_required') ? 1 : 0;
        $is_active = $this->input->post('is_active') ? 1 : 0;

        if ($task_name === '') {
            $this->set_flash('warning', 'Nama task wajib diisi.');
            redirect(base_url('onboarding/task-settings?type=' . $transition_type));
            return;
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'transition_type' => $transition_type,
            'task_name' => $task_name,
            'is_required' => $is_required,
            'is_active' => $is_active,
            'updated_at' => $now
        ];

        if ($this->task_template_has_field('description_html')) {
            $payload['description_html'] = trim($description_html) === '' ? null : $description_html;
        }

        $upload_warning = null;
        if ($this->task_template_has_field('attachment_path') && $this->task_template_has_field('attachment_name')) {
            $existing_path = trim((string) $this->input->post('existing_attachment_path'));
            $existing_name = trim((string) $this->input->post('existing_attachment_name'));
            $payload['attachment_path'] = $existing_path !== '' ? $existing_path : null;
            $payload['attachment_name'] = $existing_name !== '' ? $existing_name : null;

            $upload = $this->upload_single_attachment('attachment_file');
            if (!empty($upload['error'])) {
                $upload_warning = $upload['error'];
            } elseif (!empty($upload['path'])) {
                if (!empty($payload['attachment_path']) && $payload['attachment_path'] !== $upload['path']) {
                    $this->remove_attachment_file($payload['attachment_path']);
                }
                $payload['attachment_path'] = $upload['path'];
                $payload['attachment_name'] = $upload['name'];
            }
        }

        if ($id > 0) {
            $this->db->update('employee_transition_task_template', $payload, ['id' => $id]);
        } else {
            $max_row = $this->mymodel->selectWithQuery("
                SELECT MAX(sort_order) as max_order
                FROM employee_transition_task_template
                WHERE transition_type = '{$transition_type}'
            ");
            $payload['sort_order'] = !empty($max_row) ? ((int) $max_row[0]['max_order'] + 1) : 1;
            $payload['created_at'] = $now;
            $this->db->insert('employee_transition_task_template', $payload);
        }

        $this->refresh_all_transition_status($transition_type);

        if (!empty($upload_warning)) {
            $this->set_flash('warning', 'Task tersimpan, tetapi upload attachment gagal: ' . $upload_warning);
        } else {
            $this->set_flash('success', 'Task berhasil disimpan.');
        }
        redirect(base_url('onboarding/task-settings?type=' . $transition_type));
    }

    public function delete_task_item()
    {
        if (!$this->transition_tables_ready()) {
            $this->set_flash('danger', 'Tabel onboarding/offboarding belum tersedia. Jalankan SQL schema terlebih dahulu.');
            redirect(base_url('onboarding'));
            return;
        }

        $transition_type = $this->normalize_transition_type($this->input->post('transition_type'));
        $id = (int) $this->input->post('id');
        if ($id > 0) {
            $this->db->update('employee_transition_task_template', [
                'is_active' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $id]);
            $this->refresh_all_transition_status($transition_type);
            $this->set_flash('success', 'Task berhasil dihapus.');
        } else {
            $this->set_flash('warning', 'Task tidak valid.');
        }

        redirect(base_url('onboarding/task-settings?type=' . $transition_type));
    }

    public function detail()
    {
        if (!$this->transition_tables_ready()) {
            $this->set_flash('danger', 'Tabel onboarding/offboarding belum tersedia. Jalankan SQL schema terlebih dahulu.');
            redirect(base_url('onboarding'));
            return;
        }

        $id = (int) $this->input->get('id');
        if ($id <= 0) {
            show_404();
            return;
        }

        $transition = $this->mymodel->selectWithQuery("
            SELECT et.*, u.full_name, u.email, u.img, up.join_date, up.jenis_kontrak,
                   p.name as position_name, ql.name as level_name
            FROM employee_transition et
            INNER JOIN user u ON u.id = et.user_id
            LEFT JOIN user_profile up ON up.user_id = u.id
            LEFT JOIN positions p ON p.id = up.position_id
            LEFT JOIN quest_levels ql ON ql.id = p.level_id
            WHERE et.id = '{$id}'
            LIMIT 1
        ");

        if (empty($transition)) {
            show_404();
            return;
        }

        $transition = $transition[0];
        if ($transition['status'] === 'cancelled') {
            $this->set_flash('warning', 'Data transition sudah dihapus.');
            redirect(base_url('onboarding'));
            return;
        }

        $progress = $this->get_transition_progress_stats((int) $transition['id'], (string) $transition['transition_type']);

        $task_fields = [];
        $task_fields[] = "ett.id as task_id";
        $task_fields[] = "'template' as task_source";
        $task_fields[] = 'ett.task_name';
        $task_fields[] = 'ett.sort_order';
        $task_fields[] = 'COALESCE(etc.is_checked, 0) as is_checked';
        $task_fields[] = 'ett.is_required';
        $task_fields[] = ($this->task_template_has_field('description_html') ? 'ett.description_html' : 'NULL as description_html');
        $task_fields[] = ($this->task_template_has_field('attachment_path') ? 'ett.attachment_path' : 'NULL as attachment_path');
        $task_fields[] = ($this->task_template_has_field('attachment_name') ? 'ett.attachment_name' : 'NULL as attachment_name');

        $custom_status_select = $this->custom_task_has_field('task_status')
            ? "COALESCE(ect.task_status, CASE WHEN ect.is_checked = 1 THEN 'done' ELSE 'todo' END) as task_status"
            : "CASE WHEN ect.is_checked = 1 THEN 'done' ELSE 'todo' END as task_status";

        $template_status_select = $this->task_check_has_field('task_status')
            ? "COALESCE(etc.task_status, CASE WHEN COALESCE(etc.is_checked, 0) = 1 THEN 'done' ELSE 'todo' END) as task_status"
            : "CASE WHEN COALESCE(etc.is_checked, 0) = 1 THEN 'done' ELSE 'todo' END as task_status";

        $template_tasks = $this->mymodel->selectWithQuery("
            SELECT " . implode(', ', $task_fields) . ",
                   {$template_status_select}
            FROM employee_transition_task_template ett
            LEFT JOIN employee_transition_task_check etc
                ON etc.task_template_id = ett.id
               AND etc.transition_id = '{$transition['id']}'
            WHERE ett.transition_type = '{$transition['transition_type']}'
              AND ett.is_active = 1
            ORDER BY ett.sort_order ASC, ett.id ASC
        ");

        $custom_tasks = $this->mymodel->selectWithQuery("
            SELECT ect.id as task_id,
                   'custom' as task_source,
                   ect.task_name,
                   ect.sort_order,
                   ect.is_checked,
                   {$custom_status_select},
                   0 as is_required,
                   ect.description_html,
                   ect.attachment_path,
                   ect.attachment_name
            FROM employee_transition_custom_task ect
            WHERE ect.transition_id = '{$transition['id']}'
              AND ect.is_active = 1
            ORDER BY ect.sort_order ASC, ect.id ASC
        ");

        $all_tasks = array_merge($template_tasks, $custom_tasks);
        usort($all_tasks, function ($a, $b) {
            if ((int) $a['sort_order'] === (int) $b['sort_order']) {
                return strcmp((string) $a['task_source'], (string) $b['task_source']);
            }
            return ((int) $a['sort_order']) <=> ((int) $b['sort_order']);
        });

        $data['user'] = $_SESSION['user'];
        $data['transition'] = $transition;
        $data['progress'] = $progress;
        $data['tasks'] = $all_tasks;
        $data['flash'] = $_SESSION['onboarding_flash'] ?? null;
        unset($_SESSION['onboarding_flash']);

        $data['title'] = 'Detail ' . ucfirst($transition['transition_type']) . ' - ' . $this->template->title();
        $data['content'] = $this->load->view('onboarding/detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function add_custom_task()
    {
        if (!$this->transition_tables_ready()) {
            $this->set_flash('danger', 'Tabel onboarding/offboarding belum tersedia. Jalankan SQL schema terlebih dahulu.');
            redirect(base_url('onboarding'));
            return;
        }

        $transition_id = (int) $this->input->post('transition_id');
        $task_name = trim((string) $this->input->post('task_name'));
        $description_html = (string) $this->input->post('description_html');
        $sort_order = (int) $this->input->post('sort_order');

        if ($transition_id <= 0 || $task_name === '') {
            $this->set_flash('warning', 'Data task tidak valid.');
            redirect(base_url('onboarding'));
            return;
        }

        $transition = $this->db->get_where('employee_transition', ['id' => $transition_id])->row_array();
        if (empty($transition)) {
            $this->set_flash('warning', 'Transition tidak ditemukan.');
            redirect(base_url('onboarding'));
            return;
        }

        if ($sort_order <= 0) {
            $max = $this->mymodel->selectWithQuery("SELECT MAX(sort_order) as max_order FROM employee_transition_custom_task WHERE transition_id = '{$transition_id}'");
            $sort_order = !empty($max) ? ((int) $max[0]['max_order'] + 1) : 1;
        }

        $upload = $this->upload_single_attachment('custom_attachment');
        if (!empty($upload['error'])) {
            $this->set_flash('warning', 'Task tersimpan tanpa lampiran: ' . $upload['error']);
        }

        $now = date('Y-m-d H:i:s');
        $custom_payload = [
            'transition_id' => $transition_id,
            'task_name' => $task_name,
            'description_html' => trim($description_html) === '' ? null : $description_html,
            'attachment_path' => $upload['path'] ?? null,
            'attachment_name' => $upload['name'] ?? null,
            'sort_order' => $sort_order,
            'is_checked' => 0,
            'is_active' => 1,
            'created_by' => $_SESSION['user']['id'] ?? null,
            'created_at' => $now,
            'updated_at' => $now
        ];
        if ($this->custom_task_has_field('task_status')) {
            $custom_payload['task_status'] = 'todo';
        }
        $this->db->insert('employee_transition_custom_task', $custom_payload);

        $this->refresh_transition_status($transition_id);
        if (empty($upload['error'])) {
            $this->set_flash('success', 'Task custom berhasil ditambahkan.');
        }
        redirect(base_url('onboarding/detail?id=' . $transition_id));
    }

    public function update_detail_task_status()
    {
        if (!$this->transition_tables_ready()) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Schema belum siap.'
            ]));
            return;
        }

        $transition_id = (int) $this->input->post('transition_id');
        $task_id = (int) $this->input->post('task_id');
        $task_source = $this->input->post('task_source') === 'custom' ? 'custom' : 'template';
        $task_status = (string) $this->input->post('task_status');
        if (!in_array($task_status, ['todo', 'in_progress', 'done'], true)) {
            $task_status = ((int) $this->input->post('is_checked') === 1) ? 'done' : 'todo';
        }
        $is_checked = $task_status === 'done' ? 1 : 0;

        if ($transition_id <= 0 || $task_id <= 0) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Data task tidak valid.'
            ]));
            return;
        }

        $transition = $this->db->get_where('employee_transition', ['id' => $transition_id])->row_array();
        if (empty($transition)) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false,
                'message' => 'Transition tidak ditemukan.'
            ]));
            return;
        }

        $now = date('Y-m-d H:i:s');
        if ($task_source === 'custom') {
            $custom_payload = [
                'is_checked' => $is_checked,
                'checked_by' => $_SESSION['user']['id'] ?? null,
                'checked_at' => $is_checked ? $now : null,
                'updated_at' => $now
            ];
            if ($this->custom_task_has_field('task_status')) {
                $custom_payload['task_status'] = $task_status;
            }

            $this->db->update('employee_transition_custom_task', $custom_payload, [
                'id' => $task_id,
                'transition_id' => $transition_id
            ]);
        } else {
            $existing = $this->db->get_where('employee_transition_task_check', [
                'transition_id' => $transition_id,
                'task_template_id' => $task_id
            ])->row_array();

            $payload = [
                'is_checked' => $is_checked,
                'checked_by' => $_SESSION['user']['id'] ?? null,
                'checked_at' => $is_checked ? $now : null,
                'updated_at' => $now
            ];
            if ($this->task_check_has_field('task_status')) {
                $payload['task_status'] = $task_status;
            }
            if (!empty($existing)) {
                $this->db->update('employee_transition_task_check', $payload, ['id' => $existing['id']]);
            } else {
                $payload['transition_id'] = $transition_id;
                $payload['task_template_id'] = $task_id;
                $payload['created_at'] = $now;
                $this->db->insert('employee_transition_task_check', $payload);
            }
        }

        $this->refresh_transition_status($transition_id);
        $stats = $this->get_transition_progress_stats($transition_id, (string) $transition['transition_type']);

        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => true,
            'task_status' => $task_status,
            'progress' => $stats['progress'],
            'checked' => $stats['checked'],
            'total' => $stats['total']
        ]));
    }

    private function transition_tables_ready()
    {
        return $this->db->table_exists('employee_transition')
            && $this->db->table_exists('employee_transition_task_template')
            && $this->db->table_exists('employee_transition_task_check')
            && $this->db->table_exists('employee_transition_custom_task');
    }

    private function ensure_default_task_templates()
    {
        $defaults = [
            'onboarding' => [
                'Melengkapi informasi data diri di aplikasi Bhskin',
                'Mempelajari Basic Rules',
                'Mempelajari Guideline Anti Bingung',
                'Buku Skill With People',
                'Company Profile',
                'Penjelasan Jobdesk + Rules Divisi',
                'Good Communication, Great Relation',
                'How To Be Happy',
                'Problem Solver',
                'Storytelling Session - menjadi orang yang sharing',
                'Device',
                'Task tambahan dari leader'
            ],
            'offboarding' => [
                'Penyelesaian tugas akhir (detail dari leader)',
                'Mengembalikan aset perusahaan',
                'Menandatangani surat kerahasiaan akhir',
                'Menginformasikan manual book / transfer ilmu kepada penggantinya'
            ]
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($defaults as $type => $tasks) {
            $count = $this->db->from('employee_transition_task_template')
                ->where('transition_type', $type)
                ->count_all_results();
            if ($count > 0) {
                continue;
            }

            foreach ($tasks as $index => $task_name) {
                $this->db->insert('employee_transition_task_template', [
                    'transition_type' => $type,
                    'task_name' => $task_name,
                    'sort_order' => $index + 1,
                    'is_active' => 1,
                    'is_required' => 1,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }
        }
    }

    private function get_task_templates($transition_type)
    {
        $select = '*';
        if (!$this->task_template_has_field('description_html')) {
            $select = "id, transition_type, task_name, sort_order, is_active, is_required, created_at, updated_at, NULL as description_html, NULL as attachment_path, NULL as attachment_name";
        }

        return $this->db->select($select, false)
            ->from('employee_transition_task_template')
            ->where('transition_type', $transition_type)
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get()
            ->result_array();
    }

    private function upload_task_attachment($index)
    {
        if (!isset($_FILES['tasks_attachment']['name'][$index])) {
            return [];
        }

        $original_name = $_FILES['tasks_attachment']['name'][$index];
        if ($original_name === null || trim($original_name) === '') {
            return [];
        }

        $upload_dir = FCPATH . 'assets/uploads/onboarding_tasks/';
        if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0755, true)) {
            return ['error' => 'Gagal membuat folder upload'];
        }

        $_FILES['single_task_attachment'] = [
            'name' => $_FILES['tasks_attachment']['name'][$index],
            'type' => $_FILES['tasks_attachment']['type'][$index],
            'tmp_name' => $_FILES['tasks_attachment']['tmp_name'][$index],
            'error' => $_FILES['tasks_attachment']['error'][$index],
            'size' => $_FILES['tasks_attachment']['size'][$index]
        ];

        $config = [
            'upload_path' => $upload_dir,
            'allowed_types' => 'pdf|doc|docx|xls|xlsx|csv|jpg|jpeg|png|zip|rar|txt',
            'max_size' => 10240,
            'encrypt_name' => true
        ];

        $this->load->library('upload');
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('single_task_attachment')) {
            return ['error' => strip_tags((string) $this->upload->display_errors('', ''))];
        }

        $uploaded = $this->upload->data();
        return [
            'path' => 'assets/uploads/onboarding_tasks/' . $uploaded['file_name'],
            'name' => $_FILES['single_task_attachment']['name']
        ];
    }

    private function upload_single_attachment($field_name)
    {
        if (!isset($_FILES[$field_name]) || empty($_FILES[$field_name]['name'])) {
            return [];
        }

        $upload_dir = FCPATH . 'assets/uploads/onboarding_tasks/';
        if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0755, true)) {
            return ['error' => 'Gagal membuat folder upload'];
        }

        $config = [
            'upload_path' => $upload_dir,
            'allowed_types' => 'pdf|doc|docx|xls|xlsx|csv|jpg|jpeg|png|zip|rar|txt',
            'max_size' => 10240,
            'encrypt_name' => true
        ];

        $this->load->library('upload');
        $this->upload->initialize($config);
        if (!$this->upload->do_upload($field_name)) {
            return ['error' => strip_tags((string) $this->upload->display_errors('', ''))];
        }

        $uploaded = $this->upload->data();
        return [
            'path' => 'assets/uploads/onboarding_tasks/' . $uploaded['file_name'],
            'name' => $_FILES[$field_name]['name']
        ];
    }

    private function task_check_has_field($field_name)
    {
        $list = $this->db->list_fields('employee_transition_task_check');
        return is_array($list) && in_array($field_name, $list, true);
    }

    private function custom_task_has_field($field_name)
    {
        $list = $this->db->list_fields('employee_transition_custom_task');
        return is_array($list) && in_array($field_name, $list, true);
    }

    private function remove_attachment_file($relative_path)
    {
        if (!is_string($relative_path) || $relative_path === '') {
            return;
        }

        $full_path = FCPATH . ltrim($relative_path, '/');
        $allowed_root = realpath(FCPATH . 'assets/uploads/onboarding_tasks');
        $real_full = realpath($full_path);

        if ($allowed_root && $real_full && strpos($real_full, $allowed_root) === 0 && is_file($real_full)) {
            @unlink($real_full);
        }
    }

    private function task_template_has_field($field_name)
    {
        $fields = $this->get_task_template_fields();
        return in_array($field_name, $fields, true);
    }

    private function get_task_template_fields()
    {
        if (is_array($this->task_template_fields)) {
            return $this->task_template_fields;
        }

        $this->task_template_fields = [];
        $list = $this->db->list_fields('employee_transition_task_template');
        if (is_array($list)) {
            $this->task_template_fields = $list;
        }

        return $this->task_template_fields;
    }

    private function get_transitions($transition_type, $keyword = '', $status_filter = '')
    {
        $keyword = trim((string) $keyword);
        $status_filter = trim((string) $status_filter);

        $keyword_sql = '';
        if ($keyword !== '') {
            $escaped_keyword = $this->db->escape_like_str($keyword);
            $keyword_sql = "\n                AND (u.full_name LIKE '%{$escaped_keyword}%' ESCAPE '!'\n                OR u.email LIKE '%{$escaped_keyword}%' ESCAPE '!')\n            ";
        }

        $type = $this->db->escape($transition_type);

        $query = "
            SELECT et.*, u.full_name, u.email,
                   COALESCE(p.name, '-') as position_name
            FROM employee_transition et
            INNER JOIN user u ON u.id = et.user_id
            LEFT JOIN user_profile up ON up.user_id = u.id
            LEFT JOIN positions p ON p.id = up.position_id
            WHERE et.transition_type = {$type}
              AND et.status != 'cancelled'
              {$keyword_sql}
            ORDER BY et.last_updated_at DESC, et.id DESC
        ";

        $rows = $this->mymodel->selectWithQuery($query);

        foreach ($rows as &$row) {
            $stats = $this->get_transition_progress_stats((int) $row['id'], (string) $row['transition_type']);
            $total = (int) $stats['total'];
            $checked = (int) $stats['checked'];
            $progress = $total > 0 ? (int) round(($checked / $total) * 100) : 0;

            $status = 'ready';
            if ($progress >= 100 && $total > 0) {
                $status = 'completed';
            } elseif ($checked > 0) {
                $status = 'in_progress';
            }

            $row['total_tasks'] = $total;
            $row['checked_tasks'] = $checked;
            $row['progress'] = $progress;
            $row['status_calculated'] = $status;
        }
        unset($row);

        if ($status_filter !== '' && $status_filter !== 'all') {
            $rows = array_values(array_filter($rows, function ($row) use ($status_filter) {
                return $row['status_calculated'] === $status_filter;
            }));
        }

        return $rows;
    }

    private function refresh_all_transition_status($transition_type)
    {
        $rows = $this->db->select('id')
            ->from('employee_transition')
            ->where('transition_type', $transition_type)
            ->where('status !=', 'cancelled')
            ->get()
            ->result_array();

        foreach ($rows as $row) {
            $this->refresh_transition_status((int) $row['id']);
        }
    }

    private function refresh_transition_status($transition_id)
    {
        $transition = $this->db->get_where('employee_transition', ['id' => $transition_id])->row_array();
        if (empty($transition) || $transition['status'] === 'cancelled') {
            return;
        }

        $stats = $this->get_transition_progress_stats((int) $transition_id, (string) $transition['transition_type']);
        $total_tasks = (int) $stats['total'];
        $checked_tasks = (int) $stats['checked'];

        $new_status = 'ready';
        $started_at = null;
        $completed_at = null;

        if ($total_tasks > 0 && $checked_tasks >= $total_tasks) {
            $new_status = 'completed';
            $completed_at = date('Y-m-d H:i:s');
            $started_at = $transition['started_at'] ?: date('Y-m-d H:i:s');
        } elseif ($checked_tasks > 0) {
            $new_status = 'in_progress';
            $started_at = $transition['started_at'] ?: date('Y-m-d H:i:s');
        }

        $data = [
            'status' => $new_status,
            'last_updated_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($started_at !== null) {
            $data['started_at'] = $started_at;
        }

        if ($new_status === 'completed') {
            $data['completed_at'] = $completed_at;
        } else {
            $data['completed_at'] = null;
        }

        $this->db->update('employee_transition', $data, ['id' => $transition_id]);
    }

    private function get_transition_progress_stats($transition_id, $transition_type)
    {
        $transition_id = (int) $transition_id;
        $transition_type = $transition_type === 'offboarding' ? 'offboarding' : 'onboarding';

        $template_total = (int) $this->db->from('employee_transition_task_template')
            ->where('transition_type', $transition_type)
            ->where('is_active', 1)
            ->count_all_results();

        $template_checked = 0;
        if ($template_total > 0) {
            $checked_row = $this->mymodel->selectWithQuery("
                SELECT COUNT(*) as cnt
                FROM employee_transition_task_check etc
                INNER JOIN employee_transition_task_template ett ON ett.id = etc.task_template_id
                WHERE etc.transition_id = '{$transition_id}'
                  AND etc.is_checked = 1
                  AND ett.is_active = 1
            ");
            $template_checked = !empty($checked_row) ? (int) $checked_row[0]['cnt'] : 0;
        }

        $custom_total = (int) $this->db->from('employee_transition_custom_task')
            ->where('transition_id', $transition_id)
            ->where('is_active', 1)
            ->count_all_results();

        $custom_checked = (int) $this->db->from('employee_transition_custom_task')
            ->where('transition_id', $transition_id)
            ->where('is_active', 1)
            ->where('is_checked', 1)
            ->count_all_results();

        $total = $template_total + $custom_total;
        $checked = $template_checked + $custom_checked;
        $progress = $total > 0 ? (int) round(($checked / $total) * 100) : 0;

        return [
            'total' => $total,
            'checked' => $checked,
            'progress' => $progress
        ];
    }

    private function set_flash($type, $message)
    {
        $_SESSION['onboarding_flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    private function normalize_transition_type($type)
    {
        return $type === 'offboarding' ? 'offboarding' : 'onboarding';
    }
}
