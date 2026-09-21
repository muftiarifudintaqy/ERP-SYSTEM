<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Profile extends BaseController
{
    private $task_template_fields = null;

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
            'apply_main_quest' => 'create',
            'apply_side_quest' => 'create',
            'cancel_main_quest' => 'edit',
            'cancel_side_quest' => 'edit',
            'claim_milestone' => 'edit',
            'choose_career_path' => 'edit',
            'update_transition_task' => 'edit'
        ]);
    }

    public function index()
    {
        $user = $_SESSION['user'];
        $user_id = $user['id'];

        // Get user basic information
        $user_query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE id = '$user_id'");
        if (empty($user_query)) {
            redirect(base_url() . 'auth/login');
        }
        $data['user_data'] = $user_query[0];

        // Get user profile with position and level information + selected career path
        $profile_query = $this->mymodel->selectWithQuery("
            SELECT up.*,
                   p.name as position_name, p.id as position_id, p.career_paths,
                   ql.name as level_name, ql.id as level_id, ql.level_order,
                   tp.name as target_position_name, tp.id as target_position_id,
                   tql.name as target_level_name, tql.level_order as target_level_order
            FROM user_profile up
            LEFT JOIN positions p ON up.position_id = p.id
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            LEFT JOIN positions tp ON up.preferred_career_path_id = tp.id
            LEFT JOIN quest_levels tql ON tp.level_id = tql.id
            WHERE up.user_id = '$user_id'
        ");
        $data['profile'] = !empty($profile_query) ? $profile_query[0] : array();

        // If user has selected a career path, get path details and progress
        if (!empty($data['profile']['preferred_career_path_id'])) {
            $data['selected_career_path'] = $this->get_selected_career_path_details(
                $user_id,
                $data['profile']['position_id'],
                $data['profile']['preferred_career_path_id']
            );
        } else {
            $data['selected_career_path'] = null;
        }

        // Get all quest levels for dynamic progress calculation
        $quest_levels = $this->mymodel->selectWithQuery("SELECT * FROM quest_levels ORDER BY level_order ASC");
        $data['quest_levels'] = $quest_levels;

        // Get main quests with game-like progression in the user's position family.
        $main_quests = array();
        if (!empty($data['profile']['level_order']) && !empty($data['profile']['id']) && !empty($data['profile']['position_name'])) {
            $main_quest_progress = $this->get_main_quest_game_progress($data['profile']);
            $main_quests = $main_quest_progress['quests'];
            $data['main_quest_progress'] = $main_quest_progress;
        }
        $data['main_quests'] = $main_quests;
        if (!isset($data['main_quest_progress'])) {
            $data['main_quest_progress'] = array(
                'base_position' => '',
                'current_level_name' => $data['profile']['level_name'] ?? '',
                'next_level_name' => '',
                'progress_percent' => 0,
                'current_completed' => 0,
                'current_total' => 0,
                'next_unlocked' => false
            );
        }

        // Get available side quests
        $side_quests = array();
        if (!empty($data['profile']['id'])) {
            $profile_id = $data['profile']['id'];
            $side_quests = $this->mymodel->selectWithQuery("
                SELECT sq.*,
                (SELECT COUNT(*) FROM side_quest_submissions sqs
                 WHERE sqs.quest_id = sq.id AND sqs.user_profile_id = '$profile_id') as already_applied
                FROM side_quests sq
                ORDER BY sq.created_at DESC
            ");
        }
        $data['side_quests'] = $side_quests;

        // Get recent quest submissions (including all types)
        $recent_submissions = array();
        if (!empty($data['profile']['id'])) {
            $profile_id = $data['profile']['id'];
            $recent_submissions = $this->mymodel->selectWithQuery("
                SELECT 'main' as quest_type, mq.title as quest_title, mqs.status, mqs.submitted_at,
                       mqs.benefit_type, mqs.hr_notes, mqs.id as submission_id, mqs.quest_id
                FROM main_quest_submissions mqs
                LEFT JOIN main_quests mq ON mqs.quest_id = mq.id
                WHERE mqs.user_profile_id = '$profile_id'
                AND mqs.status != 'canceled'
                UNION ALL
                SELECT 'side' as quest_type, sq.title as quest_title, sqs.status, sqs.submitted_at,
                       NULL as benefit_type, sqs.hr_notes, sqs.id as submission_id, sqs.quest_id
                FROM side_quest_submissions sqs
                LEFT JOIN side_quests sq ON sqs.quest_id = sq.id
                WHERE sqs.user_profile_id = '$profile_id'
                AND sqs.status != 'canceled'
                ORDER BY submitted_at DESC
                LIMIT 5
            ");
        }
        $data['recent_submissions'] = $recent_submissions;

        // Get my reviews (Film and Book submissions with titles)
        $my_reviews = array();
        if (!empty($data['profile']['id'])) {
            $profile_id = $data['profile']['id'];
            $my_reviews = $this->mymodel->selectWithQuery("
                SELECT sqs.id, sqs.quest_id, sqs.user_profile_id, sqs.submission_title,
                       sqs.submission_image, sqs.hasil as review_content, sqs.hasil_html,
                       sqs.status, sqs.submitted_at, sqs.approved_at, sqs.approved_by, sqs.hr_notes,
                       sq.id as side_quest_id, sq.title as quest_title, sq.description as quest_description
                FROM side_quest_submissions sqs
                INNER JOIN side_quests sq ON sqs.quest_id = sq.id
                WHERE sqs.user_profile_id = '$profile_id'
                AND sqs.submission_title IS NOT NULL
                AND sqs.status != 'canceled'
                AND sqs.submission_title != ''
                AND sqs.quest_id IN (1, 4)
                ORDER BY sqs.submitted_at DESC
            ");
        }
        $data['my_reviews'] = $my_reviews;

        // Get quest stats
        if (!empty($data['profile']['id'])) {
            $profile_id = $data['profile']['id'];
            $quest_stats = $this->mymodel->selectWithQuery("
                SELECT 
                    (SELECT COUNT(*) FROM main_quest_submissions WHERE user_profile_id = '$profile_id' AND status = 'approved') as completed_main_quests,
                    (SELECT COUNT(*) FROM side_quest_submissions WHERE user_profile_id = '$profile_id' AND status = 'approved') as completed_side_quests,
                    (SELECT COUNT(*) FROM main_quest_submissions WHERE user_profile_id = '$profile_id' AND status = 'pending') as pending_main_quests,
                    (SELECT COUNT(*) FROM side_quest_submissions WHERE user_profile_id = '$profile_id' AND status = 'pending') as pending_side_quests
            ");
            $data['quest_stats'] = !empty($quest_stats) ? $quest_stats[0] : array(
                'completed_main_quests' => 0,
                'completed_side_quests' => 0,
                'pending_main_quests' => 0,
                'pending_side_quests' => 0
            );

            // Get leaderboard statistics
            $data['leaderboard_stats'] = $this->getUserLeaderboardStats($profile_id);

            // Get milestone data
            $data['milestone_data'] = $this->getUserMilestones($profile_id);

            // Get career progress based on approved main quests by level
            if (!empty($data['profile']['position_name'])) {
                $user_position_name = $data['profile']['position_name'];
                $base_position = preg_replace('/^(Senior|Junior|Lead|Principal|Chief)\s+/i', '', $user_position_name);
                $base_position = trim($base_position);

                $career_progress = $this->mymodel->selectWithQuery("
                    SELECT ql.level_order, ql.name as level_name,
                    COUNT(mqs.id) as approved_quests_count,
                    (SELECT COUNT(*) FROM main_quests mq2 
                     LEFT JOIN positions p2 ON mq2.required_position_id = p2.id 
                     LEFT JOIN quest_levels ql2 ON p2.level_id = ql2.id 
                     WHERE ql2.level_order = ql.level_order 
                     AND (p2.name LIKE '%$base_position%' OR p2.name = '$user_position_name')) as total_quests_at_level
                    FROM quest_levels ql
                    LEFT JOIN positions p ON p.level_id = ql.id
                    LEFT JOIN main_quests mq ON mq.required_position_id = p.id
                    LEFT JOIN main_quest_submissions mqs ON mqs.quest_id = mq.id 
                        AND mqs.user_profile_id = '$profile_id' AND mqs.status = 'approved'
                    WHERE p.name LIKE '%$base_position%' OR p.name = '$user_position_name'
                    GROUP BY ql.level_order, ql.name
                    ORDER BY ql.level_order ASC
                ");
                $data['career_progress'] = $career_progress;

                // Get position-based career progress
                $data['position_career_progress'] = $this->get_position_career_progress($user_id, $data['profile']);
            } else {
                $data['career_progress'] = array();
                $data['position_career_progress'] = array();
            }
        } else {
            $data['quest_stats'] = array(
                'completed_main_quests' => 0,
                'completed_side_quests' => 0,
                'pending_main_quests' => 0,
                'pending_side_quests' => 0
            );
        }

        $data['transition_profile'] = [
            'onboarding' => [
                'transition' => null,
                'tasks' => [],
                'progress' => 0,
                'checked' => 0,
                'total' => 0,
                'status' => 'ready'
            ],
            'offboarding' => [
                'transition' => null,
                'tasks' => [],
                'progress' => 0,
                'checked' => 0,
                'total' => 0,
                'status' => 'ready'
            ]
        ];
        if ($this->transition_tables_ready()) {
            $data['transition_profile'] = $this->get_profile_transition_data($user_id);
        }

        // Performance review: hasil terbit + tugas penilaian yang harus diisi
        $data['published_reviews'] = [];
        $data['pending_review_tasks'] = 0;
        if ($this->db->table_exists('pa_reviews')) {
            $data['published_reviews'] = $this->mymodel->selectWithQuery("
                SELECT r.id, r.period_label, r.final_total, r.final_grade, r.published_at, t.name AS template_name
                FROM pa_reviews r LEFT JOIN pa_templates t ON t.id = r.template_id
                WHERE r.reviewee_id = '" . (int) $user_id . "' AND r.status = 'published'
                ORDER BY r.published_at DESC");
            $cnt = $this->mymodel->selectWithQuery("SELECT COUNT(*) c FROM pa_assignments WHERE reviewer_id = '" . (int) $user_id . "' AND status = 'pending' AND is_active = 1");
            $data['pending_review_tasks'] = !empty($cnt) ? (int) $cnt[0]['c'] : 0;
        }

        $data['appreciation_reward_alerts'] = [];
        if (!empty($data['profile']['id'])) {
            $data['appreciation_reward_alerts'] = $this->get_unseen_appreciation_rewards((int) $data['profile']['id']);
        }

        $data['title'] = 'Akun Saya - ' . $this->template->title();
        $data['content'] = $this->load->view("profile/index", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    private function get_unseen_appreciation_rewards($profile_id)
    {
        $profile_id = (int) $profile_id;
        if ($profile_id <= 0 || !$this->db->table_exists('management_appreciation_scores')) {
            return [];
        }

        if (!$this->db->field_exists('profile_seen_at', 'management_appreciation_scores')) {
            $this->db->query("ALTER TABLE management_appreciation_scores ADD COLUMN profile_seen_at datetime NULL AFTER given_at");
            $this->db->query("ALTER TABLE management_appreciation_scores ADD KEY idx_profile_seen_at (profile_seen_at)");
        }

        $rewards = $this->mymodel->selectWithQuery("
            SELECT mas.id, mas.score, mas.note, mas.given_at, giver.full_name AS giver_name
            FROM management_appreciation_scores mas
            LEFT JOIN user giver ON giver.id = mas.given_by
            WHERE mas.user_profile_id = '$profile_id'
              AND mas.profile_seen_at IS NULL
            ORDER BY mas.given_at DESC, mas.id DESC
            LIMIT 5
        ");

        if (!empty($rewards)) {
            $ids = array_map(function ($row) {
                return (int) $row['id'];
            }, $rewards);
            $ids = array_filter($ids);

            if (!empty($ids)) {
                $this->db->where_in('id', $ids);
                $this->db->update('management_appreciation_scores', [
                    'profile_seen_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        return $rewards;
    }

    public function update_transition_task()
    {
        $this->output->set_content_type('application/json');

        if (!$this->transition_tables_ready()) {
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => 'Tabel onboarding/offboarding belum tersedia.'
            ]));
            return;
        }

        $user = $_SESSION['user'];
        $user_id = (int) $user['id'];
        $transition_type = $this->input->post('transition_type') === 'offboarding' ? 'offboarding' : 'onboarding';
        $task_id = (int) $this->input->post('task_template_id');
        $task_source = $this->input->post('task_source') === 'custom' ? 'custom' : 'template';
        $task_status = (string) $this->input->post('task_status');
        if (!in_array($task_status, ['todo', 'in_progress', 'done'], true)) {
            $task_status = ((int) $this->input->post('is_checked') === 1) ? 'done' : 'todo';
        }
        $is_checked = $task_status === 'done' ? 1 : 0;

        if ($task_id <= 0) {
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => 'Task tidak valid.'
            ]));
            return;
        }

        $transition = $this->db->get_where('employee_transition', [
            'user_id' => $user_id,
            'transition_type' => $transition_type
        ])->row_array();

        if (empty($transition) || $transition['status'] === 'cancelled') {
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => 'Anda belum terdaftar pada proses ini.'
            ]));
            return;
        }

        $now = date('Y-m-d H:i:s');
        if ($task_source === 'custom') {
            if (!$this->custom_task_table_ready()) {
                $this->output->set_output(json_encode([
                    'success' => false,
                    'message' => 'Task custom belum tersedia di sistem.'
                ]));
                return;
            }

            $custom_task = $this->db->get_where('employee_transition_custom_task', [
                'id' => $task_id,
                'transition_id' => $transition['id'],
                'is_active' => 1
            ])->row_array();
            if (empty($custom_task)) {
                $this->output->set_output(json_encode([
                    'success' => false,
                    'message' => 'Task custom tidak ditemukan.'
                ]));
                return;
            }

            $custom_payload = [
                'is_checked' => $is_checked,
                'updated_at' => $now
            ];
            if ($this->custom_task_has_field('task_status')) {
                $custom_payload['task_status'] = $task_status;
            }
            $this->db->update('employee_transition_custom_task', $custom_payload, ['id' => $task_id]);
        } else {
            $task_template = $this->db->get_where('employee_transition_task_template', [
                'id' => $task_id,
                'transition_type' => $transition_type,
                'is_active' => 1
            ])->row_array();
            if (empty($task_template)) {
                $this->output->set_output(json_encode([
                    'success' => false,
                    'message' => 'Template task tidak ditemukan.'
                ]));
                return;
            }

            $existing = $this->db->get_where('employee_transition_task_check', [
                'transition_id' => $transition['id'],
                'task_template_id' => $task_id
            ])->row_array();

            $payload = [
                'is_checked' => $is_checked,
                'checked_by' => $user_id,
                'checked_at' => $is_checked ? $now : null,
                'updated_at' => $now
            ];
            if ($this->task_check_has_field('task_status')) {
                $payload['task_status'] = $task_status;
            }

            if (!empty($existing)) {
                $this->db->update('employee_transition_task_check', $payload, ['id' => $existing['id']]);
            } else {
                $payload['transition_id'] = $transition['id'];
                $payload['task_template_id'] = $task_id;
                $payload['created_at'] = $now;
                $this->db->insert('employee_transition_task_check', $payload);
            }
        }

        $status_data = $this->refresh_transition_status((int) $transition['id']);

        $this->output->set_output(json_encode([
            'success' => true,
            'message' => 'Checklist berhasil diperbarui.',
            'task_status' => $task_status,
            'progress' => $status_data['progress'],
            'status' => $status_data['status'],
            'checked' => $status_data['checked'],
            'total' => $status_data['total']
        ]));
    }

    private function transition_tables_ready()
    {
        return $this->db->table_exists('employee_transition')
            && $this->db->table_exists('employee_transition_task_template')
            && $this->db->table_exists('employee_transition_task_check');
    }

    private function get_profile_transition_data($user_id)
    {
        $result = [
            'onboarding' => [
                'transition' => null,
                'tasks' => [],
                'progress' => 0,
                'checked' => 0,
                'total' => 0,
                'status' => 'ready'
            ],
            'offboarding' => [
                'transition' => null,
                'tasks' => [],
                'progress' => 0,
                'checked' => 0,
                'total' => 0,
                'status' => 'ready'
            ]
        ];

        $transition_rows = $this->db->select('*')
            ->from('employee_transition')
            ->where('user_id', $user_id)
            ->where('status !=', 'cancelled')
            ->get()
            ->result_array();

        foreach ($transition_rows as $transition) {
            $type = $transition['transition_type'];
            if (!isset($result[$type])) {
                continue;
            }

            $description_select = $this->task_template_has_field('description_html') ? 'ett.description_html' : 'NULL as description_html';
            $attachment_path_select = $this->task_template_has_field('attachment_path') ? 'ett.attachment_path' : 'NULL as attachment_path';
            $attachment_name_select = $this->task_template_has_field('attachment_name') ? 'ett.attachment_name' : 'NULL as attachment_name';
            $custom_status_select = $this->custom_task_has_field('task_status')
                ? "COALESCE(ect.task_status, CASE WHEN ect.is_checked = 1 THEN 'done' ELSE 'todo' END) as task_status"
                : "CASE WHEN ect.is_checked = 1 THEN 'done' ELSE 'todo' END as task_status";
            $template_status_select = $this->task_check_has_field('task_status')
                ? "COALESCE(etc.task_status, CASE WHEN COALESCE(etc.is_checked, 0) = 1 THEN 'done' ELSE 'todo' END) as task_status"
                : "CASE WHEN COALESCE(etc.is_checked, 0) = 1 THEN 'done' ELSE 'todo' END as task_status";

            $template_tasks = $this->mymodel->selectWithQuery("
                SELECT ett.id as task_id,
                       'template' as task_source,
                       ett.task_name,
                       ett.is_required,
                       ett.sort_order,
                       {$description_select},
                       {$attachment_path_select},
                       {$attachment_name_select},
                       {$template_status_select},
                       COALESCE(etc.is_checked, 0) as is_checked
                FROM employee_transition_task_template ett
                LEFT JOIN employee_transition_task_check etc
                    ON etc.task_template_id = ett.id
                   AND etc.transition_id = '{$transition['id']}'
                WHERE ett.transition_type = '{$type}'
                  AND ett.is_active = 1
                ORDER BY ett.sort_order ASC, ett.id ASC
            ");

            $custom_tasks = [];
            if ($this->custom_task_table_ready()) {
                $custom_tasks = $this->mymodel->selectWithQuery("
                    SELECT ect.id as task_id,
                           'custom' as task_source,
                           ect.task_name,
                           0 as is_required,
                           ect.sort_order,
                           ect.description_html,
                           ect.attachment_path,
                           ect.attachment_name,
                           {$custom_status_select},
                           ect.is_checked
                    FROM employee_transition_custom_task ect
                    WHERE ect.transition_id = '{$transition['id']}'
                      AND ect.is_active = 1
                    ORDER BY ect.sort_order ASC, ect.id ASC
                ");
            }

            $tasks = array_merge($template_tasks, $custom_tasks);
            usort($tasks, function ($a, $b) {
                if ((int) $a['sort_order'] === (int) $b['sort_order']) {
                    return strcmp((string) $a['task_source'], (string) $b['task_source']);
                }
                return ((int) $a['sort_order']) <=> ((int) $b['sort_order']);
            });

            $checked = 0;
            foreach ($tasks as $task) {
                if ((int) $task['is_checked'] === 1) {
                    $checked++;
                }
            }
            $total = count($tasks);
            $progress = $total > 0 ? (int) round(($checked / $total) * 100) : 0;
            $status = 'ready';
            if ($progress >= 100 && $total > 0) {
                $status = 'completed';
            } elseif ($checked > 0) {
                $status = 'in_progress';
            }

            $result[$type] = [
                'transition' => $transition,
                'tasks' => $tasks,
                'progress' => $progress,
                'checked' => $checked,
                'total' => $total,
                'status' => $status
            ];
        }

        return $result;
    }

    private function refresh_transition_status($transition_id)
    {
        $transition = $this->db->get_where('employee_transition', ['id' => $transition_id])->row_array();
        if (empty($transition) || $transition['status'] === 'cancelled') {
            return [
                'status' => 'ready',
                'progress' => 0,
                'checked' => 0,
                'total' => 0
            ];
        }

        $type = $transition['transition_type'];
        $total_tasks = (int) $this->db->from('employee_transition_task_template')
            ->where('transition_type', $type)
            ->where('is_active', 1)
            ->count_all_results();

        $checked_tasks = 0;
        if ($total_tasks > 0) {
            $checked_row = $this->mymodel->selectWithQuery("
                SELECT COUNT(*) as cnt
                FROM employee_transition_task_check etc
                INNER JOIN employee_transition_task_template ett ON ett.id = etc.task_template_id
                WHERE etc.transition_id = '{$transition_id}'
                  AND etc.is_checked = 1
                  AND ett.is_active = 1
            ");
            $checked_tasks = !empty($checked_row) ? (int) $checked_row[0]['cnt'] : 0;
        }

        $custom_total_tasks = 0;
        $custom_checked_tasks = 0;
        if ($this->custom_task_table_ready()) {
            $custom_total_tasks = (int) $this->db->from('employee_transition_custom_task')
                ->where('transition_id', $transition_id)
                ->where('is_active', 1)
                ->count_all_results();

            $custom_checked_tasks = (int) $this->db->from('employee_transition_custom_task')
                ->where('transition_id', $transition_id)
                ->where('is_active', 1)
                ->where('is_checked', 1)
                ->count_all_results();
        }

        $total_tasks += $custom_total_tasks;
        $checked_tasks += $custom_checked_tasks;

        $progress = $total_tasks > 0 ? (int) round(($checked_tasks / $total_tasks) * 100) : 0;
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

        return [
            'status' => $new_status,
            'progress' => $progress,
            'checked' => $checked_tasks,
            'total' => $total_tasks
        ];
    }

    private function task_template_has_field($field_name)
    {
        $fields = $this->get_task_template_fields();
        return in_array($field_name, $fields, true);
    }

    private function task_check_has_field($field_name)
    {
        $list = $this->db->list_fields('employee_transition_task_check');
        return is_array($list) && in_array($field_name, $list, true);
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

    private function custom_task_table_ready()
    {
        return $this->db->table_exists('employee_transition_custom_task');
    }

    private function custom_task_has_field($field_name)
    {
        if (!$this->custom_task_table_ready()) {
            return false;
        }
        $list = $this->db->list_fields('employee_transition_custom_task');
        return is_array($list) && in_array($field_name, $list, true);
    }

    public function update_process()
    {
        $user = $_SESSION['user'];
        $user_id = $user['id'];

        $dt = $_POST['dt'];
        $dt['updated_at'] = date("Y-m-d H:i:s");
        $dt['updated_by'] = $user_id;
        $dt['code'] = strtoupper($dt['username']);

        if (!empty($dt['password'])) {
            $dt['password'] = password_hash($dt['password'], PASSWORD_DEFAULT);
        } else {
            unset($dt['password']);
        }

        // Check if username is unique
        $username = $dt['username'];
        $other_user = $this->mymodel->selectWithQuery("SELECT id FROM user WHERE username = '$username' AND id != '$user_id'");

        if ($other_user) {
            $msg = 'Username sudah digunakan user lain!';
            echo $this->template->alert_danger($msg);
            return;
        }

        // Check if email is unique
        $email = $dt['email'];
        $other_email = $this->mymodel->selectWithQuery("SELECT id FROM user WHERE email = '$email' AND id != '$user_id'");

        if ($other_email) {
            $msg = 'Email sudah digunakan user lain!';
            echo $this->template->alert_danger($msg);
            return;
        }

        // Handle file upload
        if (!empty($_FILES['file']['name'])) {
            $dir = "./assets/img/user/";
            $config['upload_path'] = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['overwrite'] = TRUE;
            $config['file_name'] = $user_id;
            $config['max_size'] = 2048;
            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('file')) {
                $error = $this->upload->display_errors();
                echo $this->template->alert_danger($error);
                return;
            } else {
                $file = $this->upload->data();
                $dt['img'] = $file['file_name'];
            }
        }

        $profile_data = isset($_POST['profile']) ? $_POST['profile'] : array();

        if (!empty($_FILES['ktp_photo']['name'])) {
            $dir = "./assets/img/ktp/";
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $config = array();
            $config['upload_path'] = $dir;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['overwrite'] = true;
            $config['file_name'] = 'ktp_' . $user_id . '_' . date("YmdHis");
            $config['max_size'] = 2048;
            $this->load->library('upload');
            $this->upload->initialize($config);

            if (!$this->upload->do_upload('ktp_photo')) {
                $error = $this->upload->display_errors();
                echo $this->template->alert_danger($error);
                return;
            }

            $file = $this->upload->data();
            $profile_data['ktp_photo'] = $file['file_name'];
        }

        if ($this->db->update('user', $dt, array('id' => $user_id))) {
            if (!empty($profile_data)) {
                try {
                    $existing_profile = $this->mymodel->selectWithQuery("SELECT id FROM user_profile WHERE user_id = '$user_id' LIMIT 1");
                    if (!empty($existing_profile)) {
                        $this->db->update('user_profile', $profile_data, array('user_id' => $user_id));
                    } else {
                        $profile_data['user_id'] = $user_id;
                        $this->db->insert('user_profile', $profile_data);
                    }
                } catch (Exception $e) {
                    $msg = 'Profil dasar berhasil diupdate, tetapi profil lengkap gagal disimpan.';
                    echo $this->template->alert_danger($msg);
                    return;
                }
            }

            // Update session data
            $_SESSION['user'] = array_merge($_SESSION['user'], $dt);

            $msg = 'Update profil berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update profil tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    private function get_position_base_name($position_name)
    {
        $base = preg_replace('/^(Junior|Middle|Mid|Senior|Lead|Principal|Chief|Head)\s+(of\s+)?/i', '', (string) $position_name);
        return trim($base);
    }

    private function get_profile_for_main_quest($profile_id)
    {
        $profile_id = intval($profile_id);
        $profile = $this->mymodel->selectWithQuery("
            SELECT up.*, p.name as position_name, ql.name as level_name, ql.id as level_id, ql.level_order
            FROM user_profile up
            LEFT JOIN positions p ON up.position_id = p.id
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            WHERE up.id = '$profile_id'
            LIMIT 1
        ");

        return !empty($profile) ? $profile[0] : null;
    }

    private function get_main_quest_game_progress($profile)
    {
        $profile_id = intval($profile['id'] ?? 0);
        $current_level_order = intval($profile['level_order'] ?? 0);
        $base_position = $this->get_position_base_name($profile['position_name'] ?? '');

        if ($profile_id <= 0 || $base_position === '') {
            return array(
                'base_position' => $base_position,
                'current_level_name' => $profile['level_name'] ?? '',
                'next_level_name' => '',
                'progress_percent' => 0,
                'current_completed' => 0,
                'current_total' => 0,
                'next_unlocked' => false,
                'quests' => array()
            );
        }

        $base_like = $this->db->escape('%' . $this->db->escape_like_str($base_position) . '%');
        $quests = $this->mymodel->selectWithQuery("
            SELECT mq.*, p.name as position_name, ql.name as level_name,
                   ql.id as required_level_id, ql.level_order as required_level_order,
                   0 as is_career_path_quest,
                   (SELECT COUNT(*) FROM main_quest_submissions mqs
                    WHERE mqs.quest_id = mq.id
                      AND mqs.user_profile_id = '$profile_id'
                      AND mqs.status != 'canceled') as already_applied,
                   (SELECT COUNT(*) FROM main_quest_submissions mqs
                    WHERE mqs.quest_id = mq.id
                      AND mqs.user_profile_id = '$profile_id'
                      AND mqs.status = 'approved') as approved_count
                   ,(SELECT mqs.status FROM main_quest_submissions mqs
                    WHERE mqs.quest_id = mq.id
                      AND mqs.user_profile_id = '$profile_id'
                      AND mqs.status != 'canceled'
                    ORDER BY mqs.id DESC
                    LIMIT 1) as submission_status
            FROM main_quests mq
            LEFT JOIN positions p ON mq.required_position_id = p.id
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            WHERE p.name LIKE $base_like ESCAPE '!'
            ORDER BY ql.level_order ASC, mq.created_at DESC, mq.id DESC
        ");

        $levels = array();
        foreach ($quests as $quest) {
            $level_order = intval($quest['required_level_order'] ?? 0);
            if ($level_order <= 0) {
                continue;
            }
            if (!isset($levels[$level_order])) {
                $levels[$level_order] = array(
                    'name' => $quest['level_name'] ?? '',
                    'total' => 0,
                    'approved' => 0
                );
            }
            $levels[$level_order]['total']++;
            if (intval($quest['approved_count'] ?? 0) > 0) {
                $levels[$level_order]['approved']++;
            }
        }
        ksort($levels);

        $unlocked_levels = array();
        $previous_levels_complete = true;
        foreach ($levels as $level_order => $summary) {
            $unlocked_levels[$level_order] = ($level_order <= $current_level_order) || $previous_levels_complete;
            if ($summary['total'] > 0 && $summary['approved'] < $summary['total']) {
                $previous_levels_complete = false;
            }
        }

        foreach ($quests as &$quest) {
            $level_order = intval($quest['required_level_order'] ?? 0);
            $is_unlocked = !empty($unlocked_levels[$level_order]);
            $quest['accessibility_status'] = $is_unlocked ? 'accessible' : 'locked';
            $quest['unlock_note'] = $is_unlocked
                ? 'Quest terbuka untuk jalur ' . $base_position . '.'
                : 'Selesaikan semua quest level sebelumnya di jalur ' . $base_position . ' untuk membuka level ini.';
        }
        unset($quest);

        $current_summary = $levels[$current_level_order] ?? array('total' => 0, 'approved' => 0);
        $next_level_order = null;
        foreach (array_keys($levels) as $level_order) {
            if ($level_order > $current_level_order) {
                $next_level_order = $level_order;
                break;
            }
        }

        $current_total = intval($current_summary['total'] ?? 0);
        $current_completed = intval($current_summary['approved'] ?? 0);
        $progress_percent = $current_total > 0 ? (int) floor(($current_completed / $current_total) * 100) : 0;

        return array(
            'base_position' => $base_position,
            'current_level_name' => $profile['level_name'] ?? '',
            'next_level_name' => $next_level_order !== null ? ($levels[$next_level_order]['name'] ?? '') : '',
            'progress_percent' => min(100, max(0, $progress_percent)),
            'current_completed' => $current_completed,
            'current_total' => $current_total,
            'next_unlocked' => $next_level_order !== null && !empty($unlocked_levels[$next_level_order]),
            'quests' => array_slice($quests, 0, 20)
        );
    }

    private function get_main_quest_unlock_status($quest, $profile)
    {
        $profile_id = intval($profile['id'] ?? 0);
        $full_profile = $this->get_profile_for_main_quest($profile_id);
        if (empty($full_profile)) {
            return array('eligible' => false, 'message' => 'Profil karyawan belum lengkap. Harap lengkapi profil terlebih dahulu.');
        }

        $base_position = $this->get_position_base_name($full_profile['position_name'] ?? '');
        $quest_base_position = $this->get_position_base_name($quest['position_name'] ?? '');
        if ($base_position === '' || $quest_base_position === '' || strcasecmp($base_position, $quest_base_position) !== 0) {
            return array('eligible' => false, 'message' => 'Quest ini bukan bagian dari jalur posisi Anda.');
        }

        $quest_level_order = intval($quest['level_order'] ?? $quest['required_level_order'] ?? 0);
        $current_level_order = intval($full_profile['level_order'] ?? 0);
        if ($quest_level_order <= $current_level_order) {
            return array('eligible' => true, 'message' => '');
        }

        $base_like = $this->db->escape('%' . $this->db->escape_like_str($base_position) . '%');
        $locked_requirements = $this->mymodel->selectWithQuery("
            SELECT COUNT(mq.id) as total_required,
                   COALESCE(SUM(CASE WHEN approved.id IS NOT NULL THEN 1 ELSE 0 END), 0) as approved_required
            FROM main_quests mq
            LEFT JOIN positions p ON mq.required_position_id = p.id
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            LEFT JOIN (
                SELECT DISTINCT quest_id, id
                FROM main_quest_submissions
                WHERE user_profile_id = '$profile_id' AND status = 'approved'
            ) approved ON approved.quest_id = mq.id
            WHERE p.name LIKE $base_like ESCAPE '!'
              AND ql.level_order < '$quest_level_order'
        ");

        $required = $locked_requirements[0] ?? array('total_required' => 0, 'approved_required' => 0);
        if (intval($required['total_required']) > intval($required['approved_required'])) {
            return array(
                'eligible' => false,
                'message' => 'Quest ini masih terkunci. Selesaikan semua main quest level sebelumnya di jalur ' . $base_position . ' terlebih dahulu.'
            );
        }

        return array('eligible' => true, 'message' => '');
    }

    public function apply_main_quest()
    {
        $user = $_SESSION['user'];
        $quest_id = intval($_POST['quest_id'] ?? 0);

        // Get user profile
        $profile_query = $this->mymodel->selectWithQuery("SELECT * FROM user_profile WHERE user_id = '{$user['id']}'");
        if (empty($profile_query)) {
            echo $this->template->alert_danger('Profil karyawan belum lengkap. Harap lengkapi profil terlebih dahulu.');
            return;
        }

        $profile = $profile_query[0];
        $user_profile_id = $profile['id'];

        // Check if already applied
        $existing = $this->mymodel->selectWithQuery("SELECT id FROM main_quest_submissions WHERE quest_id = '$quest_id' AND user_profile_id = '$user_profile_id'");
        if (!empty($existing)) {
            echo $this->template->alert_danger('Anda sudah mengajukan quest ini sebelumnya.');
            return;
        }

        // Check quest eligibility
        $quest_query = $this->mymodel->selectWithQuery("
            SELECT mq.*, p.name as position_name, ql.name as level_name, ql.id as required_level_id, ql.level_order as required_level_order
            FROM main_quests mq 
            LEFT JOIN positions p ON mq.required_position_id = p.id 
            LEFT JOIN quest_levels ql ON p.level_id = ql.id 
            WHERE mq.id = '$quest_id'
        ");

        if (empty($quest_query)) {
            echo $this->template->alert_danger('Quest tidak ditemukan.');
            return;
        }

        $quest = $quest_query[0];

        $eligibility = $this->get_main_quest_unlock_status($quest, $profile);
        if (!$eligibility['eligible']) {
            echo $this->template->alert_danger($eligibility['message']);
            return;
        }

        // Submit application
        $submission_data = array(
            'quest_id' => $quest_id,
            'user_profile_id' => $user_profile_id,
            'submitted_at' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        );

        if ($this->db->insert('main_quest_submissions', $submission_data)) {
            echo $this->template->alert_success('Aplikasi main quest berhasil diajukan! Menunggu persetujuan HR.');
        } else {
            echo $this->template->alert_danger('Gagal mengajukan aplikasi quest.');
        }
    }

    public function apply_side_quest()
    {
        // Enhanced error logging and detailed error handling
        $log_file = APPPATH . 'logs/side_quest_debug_' . date('Y-m-d') . '.log';
        $error_details = array();

        try {
            // Log start of method execution
            $this->log_debug($log_file, "=== apply_side_quest START ===");
            $this->log_debug($log_file, "Memory usage: " . memory_get_usage(true) . " bytes");
            $this->log_debug($log_file, "POST data: " . json_encode($_POST));
            $this->log_debug($log_file, "FILES data: " . json_encode($_FILES));

            // Check session validity
            if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
                $error_msg = 'Session expired or invalid. Please login again.';
                $this->log_debug($log_file, "ERROR: " . $error_msg);
                echo json_encode(['status' => 'error', 'message' => $error_msg, 'error_code' => 'SESSION_INVALID']);
                return;
            }

            $user = $_SESSION['user'];
            $this->log_debug($log_file, "User ID: " . $user['id']);

            // Validate required POST data
            $quest_id = $_POST['quest_id'] ?? null;
            $hasil = $_POST['hasil'] ?? '';
            $hasil_html = $_POST['hasil_html'] ?? '';
            $submission_title = $_POST['submission_title'] ?? '';

            if (empty($quest_id)) {
                $error_msg = 'Quest ID is required but missing from request.';
                $this->log_debug($log_file, "ERROR: " . $error_msg);
                echo json_encode(['status' => 'error', 'message' => $error_msg, 'error_code' => 'MISSING_QUEST_ID']);
                return;
            }

            $this->log_debug($log_file, "CHECKPOINT 1: Basic validation passed");

            // Check database connection
            if (!$this->db->conn_id) {
                $error_msg = 'Database connection failed.';
                $this->log_debug($log_file, "ERROR: " . $error_msg);
                echo json_encode(['status' => 'error', 'message' => $error_msg, 'error_code' => 'DB_CONNECTION_FAILED']);
                return;
            }

            $this->log_debug($log_file, "CHECKPOINT 2: Database connection verified");

            // Get user profile with detailed error handling
            try {
                $profile_query = $this->mymodel->selectWithQuery("SELECT * FROM user_profile WHERE user_id = '{$user['id']}'");
                $this->log_debug($log_file, "Profile query executed, result count: " . count($profile_query));
            } catch (Exception $e) {
                $error_msg = 'Database error during profile query: ' . $e->getMessage();
                $this->log_debug($log_file, "ERROR: " . $error_msg);
                echo json_encode(['status' => 'error', 'message' => $error_msg, 'error_code' => 'DB_PROFILE_QUERY_FAILED']);
                return;
            }

            if (empty($profile_query)) {
                $error_msg = 'User profile not found. Please complete your profile first.';
                $this->log_debug($log_file, "ERROR: " . $error_msg);
                echo json_encode(['status' => 'error', 'message' => $error_msg, 'error_code' => 'PROFILE_NOT_FOUND']);
                return;
            }

            $user_profile_id = $profile_query[0]['id'];
            $this->log_debug($log_file, "CHECKPOINT 3: User profile found, profile_id: " . $user_profile_id);

            // Get quest information with detailed error handling
            try {
                $quest_query = $this->mymodel->selectWithQuery("SELECT title FROM side_quests WHERE id = '$quest_id'");
                $this->log_debug($log_file, "Quest query executed, result count: " . count($quest_query));
            } catch (Exception $e) {
                $error_msg = 'Database error during quest query: ' . $e->getMessage();
                $this->log_debug($log_file, "ERROR: " . $error_msg);
                echo json_encode(['status' => 'error', 'message' => $error_msg, 'error_code' => 'DB_QUEST_QUERY_FAILED']);
                return;
            }

            if (empty($quest_query)) {
                $error_msg = 'Quest not found with ID: ' . $quest_id;
                $this->log_debug($log_file, "ERROR: " . $error_msg);
                echo json_encode(['status' => 'error', 'message' => $error_msg, 'error_code' => 'QUEST_NOT_FOUND']);
                return;
            }

            $quest_title = $quest_query[0]['title'];
            $is_film_quest = ($quest_id == 1); // Quest ID 1 = Film
            $is_book_quest = ($quest_id == 4); // Quest ID 4 = Book
            $this->log_debug($log_file, "CHECKPOINT 4: Quest found - ID: " . $quest_id . ", Title: " . $quest_title . ", Is Film: " . ($is_film_quest ? 'Yes' : 'No') . ", Is Book: " . ($is_book_quest ? 'Yes' : 'No'));

            // Validate hasil field
            if (empty(trim($hasil))) {
                $error_msg = 'Work result (hasil) is required. Please enter your work notes or documentation link.';
                $this->log_debug($log_file, "ERROR: " . $error_msg);
                echo json_encode(['status' => 'error', 'message' => $error_msg, 'error_code' => 'HASIL_REQUIRED']);
                return;
            }

            // Validate title for Film and Book quests
            if (($is_film_quest || $is_book_quest) && empty(trim($submission_title))) {
                $quest_type = $is_film_quest ? 'film' : 'buku';
                $error_msg = "Title is required for $quest_type quest.";
                $this->log_debug($log_file, "ERROR: " . $error_msg);
                echo json_encode(['status' => 'error', 'message' => $error_msg, 'error_code' => 'TITLE_REQUIRED']);
                return;
            }

            $this->log_debug($log_file, "CHECKPOINT 5: Field validation passed");

            // Handle image upload for Film and Book quests with detailed error handling
            $uploaded_filename = null;
            if (($is_film_quest || $is_book_quest) && !empty($_FILES['submission_image']['name'])) {
                $this->log_debug($log_file, "CHECKPOINT 6A: Starting image upload");

                $upload_path = 'assets/uploads/side_quest_user_images/';

                // Create directory if it doesn't exist
                if (!is_dir($upload_path)) {
                    try {
                        mkdir($upload_path, 0755, true);
                        $this->log_debug($log_file, "Created upload directory: " . $upload_path);
                    } catch (Exception $e) {
                        $error_msg = 'Failed to create upload directory: ' . $e->getMessage();
                        $this->log_debug($log_file, "ERROR: " . $error_msg);
                        echo json_encode(['status' => 'error', 'message' => $error_msg, 'error_code' => 'UPLOAD_DIR_FAILED']);
                        return;
                    }
                }

                $config['upload_path'] = $upload_path;
                $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
                $config['max_size'] = 2048; // 2MB
                $config['file_name'] = 'user_' . $user['id'] . '_quest_' . $quest_id . '_' . time() . '_' . uniqid();

                $this->load->library('upload', $config);

                if ($this->upload->do_upload('submission_image')) {
                    $file_data = $this->upload->data();
                    $uploaded_filename = $file_data['file_name'];
                    $this->log_debug($log_file, "CHECKPOINT 6B: Image uploaded successfully - " . $uploaded_filename);
                } else {
                    $upload_error = $this->upload->display_errors('', '');
                    $error_msg = 'Image upload failed: ' . $upload_error;
                    $this->log_debug($log_file, "ERROR: " . $error_msg);
                    echo json_encode(['status' => 'error', 'message' => $error_msg, 'error_code' => 'UPLOAD_FAILED']);
                    return;
                }
            } else {
                $this->log_debug($log_file, "CHECKPOINT 6: No image upload required");
            }

            // Prepare submission data
            $submission_data = array(
                'quest_id' => $quest_id,
                'user_profile_id' => $user_profile_id,
                'hasil' => $hasil,
                'submitted_at' => date('Y-m-d H:i:s'),
                'status' => 'pending'
            );

            // Add HTML content if available
            if (!empty($hasil_html)) {
                $submission_data['hasil_html'] = $hasil_html;
            }

            // Add title and image for Film/Book quests
            if ($is_film_quest || $is_book_quest) {
                $submission_data['submission_title'] = $submission_title;
                if ($uploaded_filename) {
                    $submission_data['submission_image'] = $uploaded_filename;
                }
            }

            $this->log_debug($log_file, "CHECKPOINT 7: Submission data prepared - " . json_encode($submission_data));
            $this->log_debug($log_file, "Memory usage before insert: " . memory_get_usage(true) . " bytes");

            // Check current side quest submissions count for this user
            try {
                $count_query = $this->mymodel->selectWithQuery("SELECT COUNT(*) as count FROM side_quest_submissions WHERE user_profile_id = '$user_profile_id'");
                $current_count = $count_query[0]['count'];
                $this->log_debug($log_file, "Current side quest submissions count for user: " . $current_count);
            } catch (Exception $e) {
                $this->log_debug($log_file, "WARNING: Could not check submission count - " . $e->getMessage());
            }

            // Attempt database insert with comprehensive error handling
            try {
                $this->log_debug($log_file, "CHECKPOINT 8: Starting database insert");

                // Check database connection before insert
                if (!$this->db->conn_id) {
                    throw new Exception("Database connection lost before insert");
                }

                $insert_result = $this->db->insert('side_quest_submissions', $submission_data);

                if ($insert_result) {
                    $insert_id = $this->db->insert_id();
                    $this->log_debug($log_file, "CHECKPOINT 9: Database insert successful - Insert ID: " . $insert_id);

                    $quest_type = $is_film_quest ? 'film' : ($is_book_quest ? 'buku' : 'side quest');
                    $msg = "Aplikasi $quest_type berhasil diajukan! Hasil kerja Anda akan direview oleh HR.";
                    if ($uploaded_filename) {
                        $msg .= " Gambar berhasil diupload.";
                    }

                    // Return JSON success response
                    $response = array(
                        'status' => 'success',
                        'message' => $msg,
                        'insert_id' => $insert_id,
                        'submission_count' => ($current_count + 1)
                    );
                    $this->log_debug($log_file, "=== apply_side_quest SUCCESS ===");
                    echo json_encode($response);
                } else {
                    // Get detailed database error
                    $db_error = $this->db->error();
                    $last_query = $this->db->last_query();

                    $error_msg = "Database insert failed. MySQL Error " . $db_error['code'] . ": " . $db_error['message'];
                    $this->log_debug($log_file, "ERROR: " . $error_msg);
                    $this->log_debug($log_file, "Last Query: " . $last_query);

                    // Clean up uploaded file if database insert fails
                    if ($uploaded_filename && file_exists($upload_path . $uploaded_filename)) {
                        unlink($upload_path . $uploaded_filename);
                        $this->log_debug($log_file, "Cleaned up uploaded file: " . $uploaded_filename);
                    }

                    echo json_encode([
                        'status' => 'error',
                        'message' => $error_msg,
                        'error_code' => 'DB_INSERT_FAILED',
                        'mysql_error_code' => $db_error['code'],
                        'mysql_error_message' => $db_error['message'],
                        'last_query' => $last_query
                    ]);
                }
            } catch (Exception $e) {
                $error_msg = "Exception during database insert: " . $e->getMessage();
                $this->log_debug($log_file, "ERROR: " . $error_msg);

                // Clean up uploaded file if exception occurs
                if ($uploaded_filename && file_exists($upload_path . $uploaded_filename)) {
                    unlink($upload_path . $uploaded_filename);
                    $this->log_debug($log_file, "Cleaned up uploaded file after exception: " . $uploaded_filename);
                }

                echo json_encode([
                    'status' => 'error',
                    'message' => $error_msg,
                    'error_code' => 'DB_INSERT_EXCEPTION'
                ]);
            }
        } catch (Exception $e) {
            // Catch any unexpected exceptions
            $error_msg = "Unexpected error in apply_side_quest: " . $e->getMessage();
            $this->log_debug($log_file, "FATAL ERROR: " . $error_msg);
            $this->log_debug($log_file, "Stack trace: " . $e->getTraceAsString());

            echo json_encode([
                'status' => 'error',
                'message' => $error_msg,
                'error_code' => 'UNEXPECTED_EXCEPTION',
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        } finally {
            $this->log_debug($log_file, "Memory usage at end: " . memory_get_usage(true) . " bytes");
            $this->log_debug($log_file, "=== apply_side_quest END ===\n");
        }
    }

    /**
     * Helper method for detailed debug logging
     */
    private function log_debug($file, $message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$message}\n";
        file_put_contents($file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    public function quest_history()
    {
        $user = $_SESSION['user'];

        // Get user profile
        $profile_query = $this->mymodel->selectWithQuery("SELECT * FROM user_profile WHERE user_id = '{$user['id']}'");
        if (empty($profile_query)) {
            redirect(base_url() . 'profile');
            return;
        }

        $user_profile_id = $profile_query[0]['id'];

        // Get total count for pagination
        $count_query = $this->mymodel->selectWithQuery("
            SELECT COUNT(*) as total FROM (
                SELECT mqs.id
                FROM main_quest_submissions mqs
                WHERE mqs.user_profile_id = '$user_profile_id'
                UNION ALL
                SELECT sqs.id
                FROM side_quest_submissions sqs
                WHERE sqs.user_profile_id = '$user_profile_id'
            ) as combined_submissions
        ");
        $total_submissions = $count_query[0]['total'];

        // Pagination setup
        $limit = 10;
        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $data['page'] = CEIL($total_submissions / $limit);
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        // Get paginated quest submissions
        $submissions = $this->mymodel->selectWithQuery("
            SELECT 'main' as quest_type, mqs.quest_id, mq.title as quest_title, mqs.status,
                   mqs.submitted_at, mqs.approved_at, mqs.hr_notes, mqs.benefit_type,
                   approver.full_name as approver_name
            FROM main_quest_submissions mqs
            LEFT JOIN main_quests mq ON mqs.quest_id = mq.id
            LEFT JOIN user approver ON mqs.approved_by = approver.id
            WHERE mqs.user_profile_id = '$user_profile_id'
            UNION ALL
            SELECT 'side' as quest_type, sqs.quest_id, sq.title as quest_title, sqs.status,
                   sqs.submitted_at, sqs.approved_at, sqs.hr_notes, NULL as benefit_type,
                   approver.full_name as approver_name
            FROM side_quest_submissions sqs
            LEFT JOIN side_quests sq ON sqs.quest_id = sq.id
            LEFT JOIN user approver ON sqs.approved_by = approver.id
            WHERE sqs.user_profile_id = '$user_profile_id'
            ORDER BY submitted_at DESC
            LIMIT $offset, $limit
        ");

        $data['submissions'] = $submissions;
        $data['total_submissions'] = $total_submissions;
        $data['profile'] = $profile_query[0];
        $data['title'] = 'Riwayat Quest - ' . $this->template->title();
        $data['content'] = $this->load->view("profile/quest_history", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    // Leaderboard and Milestone Methods
    private function getSideQuestStatsFromSubmissions($profile_id = null)
    {
        $profile_condition = $profile_id !== null ? "AND sqs.user_profile_id = '" . $this->db->escape_str($profile_id) . "'" : "";
        $group_select = $profile_id === null ? "sqs.user_profile_id," : "";
        $group_by = $profile_id === null ? "GROUP BY sqs.user_profile_id" : "";

        $stats = $this->mymodel->selectWithQuery("
            SELECT
                {$group_select}
                COUNT(sqs.id) as total_completed_quests,
                COALESCE(SUM(COALESCE(sq.points, 0)), 0) as total_points_earned,
                COALESCE(SUM(CASE WHEN DATE_FORMAT(sqs.approved_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') THEN 1 ELSE 0 END), 0) as monthly_completed_quests,
                COALESCE(SUM(CASE WHEN DATE_FORMAT(sqs.approved_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') THEN COALESCE(sq.points, 0) ELSE 0 END), 0) as monthly_points_earned
            FROM side_quest_submissions sqs
            LEFT JOIN side_quests sq ON sqs.quest_id = sq.id
            WHERE sqs.status = 'approved'
            {$profile_condition}
            {$group_by}
        ");

        if ($profile_id !== null) {
            return !empty($stats) ? $stats[0] : [
                'total_completed_quests' => 0,
                'total_points_earned' => 0,
                'monthly_completed_quests' => 0,
                'monthly_points_earned' => 0
            ];
        }

        return $stats;
    }

    private function getUserLeaderboardStats($profile_id)
    {
        try {
            $stats = $this->getSideQuestStatsFromSubmissions($profile_id);
            $has_appreciation_scores = $this->db->table_exists('management_appreciation_scores');
            $profile_score_query = $this->mymodel->selectWithQuery("
                SELECT COALESCE(score, 0) as profile_score
                FROM user_profile
                WHERE id = '$profile_id'
                LIMIT 1
            ");
            $profile_score = !empty($profile_score_query) ? (int) $profile_score_query[0]['profile_score'] : (int) $stats['total_points_earned'];

            $monthly_appreciation_points = 0;
            if ($has_appreciation_scores) {
                $monthly_appreciation = $this->mymodel->selectWithQuery("
                    SELECT COALESCE(SUM(score), 0) as monthly_points
                    FROM management_appreciation_scores
                    WHERE user_profile_id = '$profile_id'
                      AND DATE_FORMAT(given_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                ");
                $monthly_appreciation_points = !empty($monthly_appreciation) ? (int) $monthly_appreciation[0]['monthly_points'] : 0;
            }

            $stats['monthly_points_earned'] = (int) $stats['monthly_points_earned'] + $monthly_appreciation_points;
            $stats['total_points_earned'] = $profile_score;

            $bonus_stats = $this->mymodel->selectWithQuery("
                SELECT COALESCE(milestone_bonus_points, 0) as milestone_bonus_points
                FROM user_side_quest_stats
                WHERE user_profile_id = '$profile_id'
            ");
            $stats['milestone_bonus_points'] = !empty($bonus_stats) ? (int) $bonus_stats[0]['milestone_bonus_points'] : 0;
            $stats['total_all_time_points'] = $profile_score;
            $stats['total_monthly_points'] = (int) $stats['monthly_points_earned'];

            // Get user's monthly ranking
            if ($has_appreciation_scores) {
                $monthly_rank = $this->mymodel->selectWithQuery("
                    SELECT COUNT(*) + 1 as `rank`
                    FROM (
                        SELECT
                            up.id as user_profile_id,
                            COALESCE(side_points.monthly_points, 0) + COALESCE(appreciation_points.monthly_points, 0) as monthly_points
                        FROM user_profile up
                        LEFT JOIN (
                            SELECT sqs.user_profile_id, COALESCE(SUM(COALESCE(sq.points, 0)), 0) as monthly_points
                            FROM side_quest_submissions sqs
                            LEFT JOIN side_quests sq ON sqs.quest_id = sq.id
                            WHERE sqs.status = 'approved'
                              AND DATE_FORMAT(sqs.approved_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                            GROUP BY sqs.user_profile_id
                        ) side_points ON side_points.user_profile_id = up.id
                        LEFT JOIN (
                            SELECT user_profile_id, COALESCE(SUM(score), 0) as monthly_points
                            FROM management_appreciation_scores
                            WHERE DATE_FORMAT(given_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                            GROUP BY user_profile_id
                        ) appreciation_points ON appreciation_points.user_profile_id = up.id
                    ) ranked
                    WHERE ranked.monthly_points > " . (int) $stats['monthly_points_earned'] . "
                ");
            } else {
                $monthly_rank = $this->mymodel->selectWithQuery("
                    SELECT COUNT(*) + 1 as `rank`
                    FROM (
                        SELECT sqs.user_profile_id, COALESCE(SUM(COALESCE(sq.points, 0)), 0) as monthly_points
                        FROM side_quest_submissions sqs
                        LEFT JOIN side_quests sq ON sqs.quest_id = sq.id
                        WHERE sqs.status = 'approved'
                          AND DATE_FORMAT(sqs.approved_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                        GROUP BY sqs.user_profile_id
                    ) ranked
                    WHERE ranked.monthly_points > " . (int) $stats['monthly_points_earned'] . "
                ");
            }
            $stats['monthly_rank'] = !empty($monthly_rank) ? $monthly_rank[0]['rank'] : 1;

            // Get user's all-time ranking
            $alltime_rank = $this->mymodel->selectWithQuery("
                SELECT COUNT(*) + 1 as `rank`
                FROM user_profile
                WHERE COALESCE(score, 0) > " . (int) $profile_score . "
            ");
            $stats['alltime_rank'] = !empty($alltime_rank) ? $alltime_rank[0]['rank'] : 1;

            // Get top 3 performers for comparison
            if ($has_appreciation_scores) {
                $top_performers = $this->mymodel->selectWithQuery("
                    SELECT
                        u.full_name,
                        ranked.total_monthly_points
                    FROM (
                        SELECT
                            up.id as user_profile_id,
                            COALESCE(side_points.monthly_points, 0) + COALESCE(appreciation_points.monthly_points, 0) as total_monthly_points
                        FROM user_profile up
                        LEFT JOIN (
                            SELECT sqs.user_profile_id, COALESCE(SUM(COALESCE(sq.points, 0)), 0) as monthly_points
                            FROM side_quest_submissions sqs
                            LEFT JOIN side_quests sq ON sqs.quest_id = sq.id
                            WHERE sqs.status = 'approved'
                              AND DATE_FORMAT(sqs.approved_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                            GROUP BY sqs.user_profile_id
                        ) side_points ON side_points.user_profile_id = up.id
                        LEFT JOIN (
                            SELECT user_profile_id, COALESCE(SUM(score), 0) as monthly_points
                            FROM management_appreciation_scores
                            WHERE DATE_FORMAT(given_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                            GROUP BY user_profile_id
                        ) appreciation_points ON appreciation_points.user_profile_id = up.id
                    ) ranked
                    LEFT JOIN user_profile up ON ranked.user_profile_id = up.id
                    LEFT JOIN user u ON up.user_id = u.id
                    WHERE ranked.total_monthly_points > 0
                    ORDER BY ranked.total_monthly_points DESC
                    LIMIT 3
                ");
            } else {
                $top_performers = $this->mymodel->selectWithQuery("
                    SELECT
                        u.full_name,
                        ranked.total_monthly_points
                    FROM (
                        SELECT sqs.user_profile_id, COALESCE(SUM(COALESCE(sq.points, 0)), 0) as total_monthly_points
                        FROM side_quest_submissions sqs
                        LEFT JOIN side_quests sq ON sqs.quest_id = sq.id
                        WHERE sqs.status = 'approved'
                          AND DATE_FORMAT(sqs.approved_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                        GROUP BY sqs.user_profile_id
                    ) ranked
                    LEFT JOIN user_profile up ON ranked.user_profile_id = up.id
                    LEFT JOIN user u ON up.user_id = u.id
                    WHERE ranked.total_monthly_points > 0
                    ORDER BY ranked.total_monthly_points DESC
                    LIMIT 3
                ");
            }
            $stats['top_performers'] = $top_performers;

            return $stats;
        } catch (Exception $e) {
            return array(
                'total_completed_quests' => 0,
                'total_points_earned' => 0,
                'monthly_completed_quests' => 0,
                'monthly_points_earned' => 0,
                'milestone_bonus_points' => 0,
                'total_all_time_points' => 0,
                'total_monthly_points' => 0,
                'monthly_rank' => 0,
                'alltime_rank' => 0,
                'top_performers' => array()
            );
        }
    }

    private function getUserMilestones($profile_id)
    {
        try {
            // Get user's current statistics for milestone calculation
            $current_stats = $this->getSideQuestStatsFromSubmissions($profile_id);

            // Get all active milestones with progress calculation and claim status
            $milestones = $this->mymodel->selectWithQuery("
                SELECT 
                    msq.*,
                    ma.status as claim_status,
                    ma.approved_at,
                    ma.delivered_at,
                    ma.proof_image,
                    CASE 
                        WHEN ma.status = 'delivered' THEN 'achieved'
                        WHEN ma.status = 'approved' THEN 'approved'  
                        WHEN ma.status = 'waiting_approval' THEN 'pending'
                        ELSE 'available'
                    END as status
                FROM milestone_side_quests msq
                LEFT JOIN milestone_achievements ma ON msq.id = ma.milestone_quest_id 
                    AND ma.user_profile_id = '$profile_id'
                WHERE msq.is_active = 1
                ORDER BY 
                    CASE WHEN ma.status = 'delivered' THEN 1 
                         WHEN ma.status = 'approved' THEN 2
                         WHEN ma.status = 'waiting_approval' THEN 3
                         ELSE 4 END ASC,
                    msq.target_value ASC
            ");

            // Calculate progress for each milestone
            foreach ($milestones as &$milestone) {
                $target_value = intval($milestone['target_value']);
                $current_value = 0;

                switch ($milestone['milestone_type']) {
                    case 'quest_count':
                        $current_value = intval($current_stats['total_completed_quests']);
                        break;
                    case 'total_points':
                        $current_value = intval($current_stats['total_points_earned']);
                        break;
                    case 'monthly_points':
                        $current_value = intval($current_stats['monthly_points_earned']);
                        break;
                }

                $milestone['current_value'] = $current_value;
                $milestone['progress_percentage'] = $target_value > 0 ? min(100, ($current_value / $target_value) * 100) : 0;
                $milestone['is_achievable'] = $current_value >= $target_value;
            }

            return $milestones;
        } catch (Exception $e) {
            return array();
        }
    }

    public function cancel_main_quest()
    {
        $user = $_SESSION['user'];
        $submission_id = $_POST['submission_id'];

        // Get user profile
        $profile_query = $this->mymodel->selectWithQuery("SELECT * FROM user_profile WHERE user_id = '{$user['id']}'");
        if (empty($profile_query)) {
            echo $this->template->alert_danger('Profil karyawan belum lengkap.');
            return;
        }

        $user_profile_id = $profile_query[0]['id'];

        // Validate submission ownership and status
        $submission = $this->mymodel->selectWithQuery("
            SELECT mqs.*, mq.title as quest_title 
            FROM main_quest_submissions mqs 
            LEFT JOIN main_quests mq ON mqs.quest_id = mq.id
            WHERE mqs.id = '$submission_id' AND mqs.user_profile_id = '$user_profile_id'
        ");

        if (empty($submission)) {
            echo $this->template->alert_danger('Aplikasi quest tidak ditemukan atau bukan milik Anda.');
            return;
        }

        $submission_data = $submission[0];

        // Only allow cancellation of pending submissions
        if ($submission_data['status'] !== 'pending') {
            echo $this->template->alert_warning('Hanya aplikasi dengan status pending yang dapat dibatalkan.');
            return;
        }

        // Update status to canceled
        $update_data = array(
            'status' => 'canceled',
            'canceled_at' => date('Y-m-d H:i:s')
        );

        if ($this->mymodel->updateData('main_quest_submissions', $update_data, array('id' => $submission_id))) {
            echo $this->template->alert_success('Aplikasi main quest "' . $submission_data['quest_title'] . '" berhasil dibatalkan.');
        } else {
            echo $this->template->alert_danger('Gagal membatalkan aplikasi quest.');
        }
    }

    public function cancel_side_quest()
    {
        $user = $_SESSION['user'];
        $submission_id = $_POST['submission_id'];

        // Get user profile
        $profile_query = $this->mymodel->selectWithQuery("SELECT * FROM user_profile WHERE user_id = '{$user['id']}'");
        if (empty($profile_query)) {
            echo $this->template->alert_danger('Profil karyawan belum lengkap.');
            return;
        }

        $user_profile_id = $profile_query[0]['id'];

        // Validate submission ownership and status
        $submission = $this->mymodel->selectWithQuery("
            SELECT sqs.*, sq.title as quest_title 
            FROM side_quest_submissions sqs
            LEFT JOIN side_quests sq ON sqs.quest_id = sq.id
            WHERE sqs.id = '$submission_id' AND sqs.user_profile_id = '$user_profile_id'
        ");

        if (empty($submission)) {
            echo $this->template->alert_danger('Aplikasi quest tidak ditemukan atau bukan milik Anda.');
            return;
        }

        $submission_data = $submission[0];

        // Only allow cancellation of pending submissions
        if ($submission_data['status'] !== 'pending') {
            echo $this->template->alert_warning('Hanya aplikasi dengan status pending yang dapat dibatalkan.');
            return;
        }

        // Update status to canceled
        $update_data = array(
            'status' => 'canceled',
            'canceled_at' => date('Y-m-d H:i:s')
        );

        if ($this->mymodel->updateData('side_quest_submissions', $update_data, array('id' => $submission_id))) {
            echo $this->template->alert_success('Aplikasi side quest "' . $submission_data['quest_title'] . '" berhasil dibatalkan.');
        } else {
            echo $this->template->alert_danger('Gagal membatalkan aplikasi quest.');
        }
    }

    public function claim_milestone()
    {
        $user = $_SESSION['user'];
        $milestone_id = $_POST['milestone_id'];

        // Get user profile
        $profile_query = $this->mymodel->selectWithQuery("SELECT * FROM user_profile WHERE user_id = '{$user['id']}'");
        if (empty($profile_query)) {
            echo $this->template->alert_danger('Profil karyawan belum lengkap.');
            return;
        }

        $user_profile_id = $profile_query[0]['id'];

        // Get milestone details
        $milestone = $this->mymodel->selectWithQuery("SELECT * FROM milestone_side_quests WHERE id = '$milestone_id' AND is_active = 1");
        if (empty($milestone)) {
            echo $this->template->alert_danger('Milestone tidak ditemukan atau tidak aktif.');
            return;
        }

        $milestone_data = $milestone[0];

        // Check if already claimed or has pending request
        $existing = $this->mymodel->selectWithQuery("SELECT id, status FROM milestone_achievements WHERE milestone_quest_id = '$milestone_id' AND user_profile_id = '$user_profile_id'");
        if (!empty($existing)) {
            $status = $existing[0]['status'];
            if ($status == 'waiting_approval') {
                echo $this->template->alert_warning('Anda sudah mengajukan claim untuk milestone ini dan sedang menunggu persetujuan.');
            } else if ($status == 'approved') {
                echo $this->template->alert_warning('Milestone sudah disetujui dan sedang menunggu pengiriman reward.');
            } else if ($status == 'delivered') {
                echo $this->template->alert_warning('Milestone sudah pernah diclaim dan reward telah diterima.');
            }
            return;
        }

        // Verify milestone achievement from approved side quest submissions.
        $stats = $this->getSideQuestStatsFromSubmissions($user_profile_id);
        $current_value = 0;

        switch ($milestone_data['milestone_type']) {
            case 'quest_count':
                $current_value = intval($stats['total_completed_quests']);
                break;
            case 'total_points':
                $current_value = intval($stats['total_points_earned']);
                break;
            case 'monthly_points':
                $current_value = intval($stats['monthly_points_earned']);
                break;
        }

        if ($current_value < intval($milestone_data['target_value'])) {
            echo $this->template->alert_danger('Milestone belum tercapai. Nilai saat ini: ' . $current_value . '/' . $milestone_data['target_value']);
            return;
        }

        // Create milestone claim request record (status: waiting_approval)
        $achievement_data = array(
            'milestone_quest_id' => $milestone_id,
            'user_profile_id' => $user_profile_id,
            'achieved_at' => date('Y-m-d H:i:s'),
            'achievement_value' => $current_value,
            'bonus_points_earned' => $milestone_data['reward_points'],
            'status' => 'waiting_approval',
            'claimed' => 0, // Keep for backward compatibility
            'claimed_at' => NULL
        );

        // Insert claim request
        if ($this->mymodel->insertData('milestone_achievements', $achievement_data)) {
            echo $this->template->alert_success('Permintaan claim milestone berhasil diajukan! Silakan tunggu persetujuan dari admin.');
        } else {
            echo $this->template->alert_danger('Gagal mengajukan claim milestone. Silakan coba lagi.');
        }
    }

    public function get_review_detail()
    {
        $submission_id = $_POST['submission_id'] ?? null;
        $user = $_SESSION['user'];

        if (empty($submission_id)) {
            echo json_encode(['error' => 'Submission ID is required']);
            return;
        }

        // Get user profile
        $profile_query = $this->mymodel->selectWithQuery("SELECT id FROM user_profile WHERE user_id = '{$user['id']}'");
        if (empty($profile_query)) {
            echo json_encode(['error' => 'Profile not found']);
            return;
        }

        $user_profile_id = $profile_query[0]['id'];

        // Get submission details with security check. This is used by both the
        // review cards and the recent quest activity edit action.
        $review = $this->mymodel->selectWithQuery("
            SELECT sqs.*, sq.title as quest_title, sq.description as quest_description
            FROM side_quest_submissions sqs
            LEFT JOIN side_quests sq ON sqs.quest_id = sq.id
            WHERE sqs.id = '$submission_id'
            AND sqs.user_profile_id = '$user_profile_id'
            AND sqs.status != 'canceled'
        ");

        if (!empty($review)) {
            $review_data = $review[0];
            $review_data['submitted_at_formatted'] = date('d M Y H:i', strtotime($review_data['submitted_at']));
            if ($review_data['approved_at']) {
                $review_data['approved_at_formatted'] = date('d M Y H:i', strtotime($review_data['approved_at']));
            }

            echo json_encode($review_data);
        } else {
            echo json_encode(['error' => 'Quest submission not found']);
        }
    }

    public function update_review()
    {
        $user = $_SESSION['user'];
        $submission_id = $_POST['submission_id'] ?? null;
        $submission_title = $_POST['submission_title'] ?? '';
        $hasil = $_POST['hasil'] ?? '';
        $hasil_html = $_POST['hasil_html'] ?? '';

        if (!$submission_id) {
            echo json_encode(['success' => false, 'message' => 'Submission ID is required']);
            return;
        }

        // Get user profile
        $profile_query = $this->mymodel->selectWithQuery("SELECT id FROM user_profile WHERE user_id = '{$user['id']}'");
        if (empty($profile_query)) {
            echo json_encode(['success' => false, 'message' => 'Profile not found']);
            return;
        }

        $user_profile_id = $profile_query[0]['id'];

        // Get submission details with security check. Only pending submissions
        // can be edited from the profile activity list.
        $submission = $this->mymodel->selectWithQuery("
            SELECT sqs.*, sq.title as quest_title
            FROM side_quest_submissions sqs
            LEFT JOIN side_quests sq ON sqs.quest_id = sq.id
            WHERE sqs.id = '$submission_id'
            AND sqs.user_profile_id = '$user_profile_id'
            AND sqs.status = 'pending'
        ");

        if (empty($submission)) {
            echo json_encode(['success' => false, 'message' => 'Submission tidak ditemukan atau sudah tidak pending.']);
            return;
        }

        $quest_id = (int) $submission[0]['quest_id'];
        $quest_title = strtolower((string) $submission[0]['quest_title']);
        $is_review_quest = in_array($quest_id, [1, 4], true)
            || strpos($quest_title, 'nonton film') !== false
            || strpos($quest_title, 'baca buku') !== false;

        // Validate required fields
        if ($is_review_quest && empty(trim($submission_title))) {
            echo json_encode(['success' => false, 'message' => 'Judul harus diisi']);
            return;
        }

        if (empty(trim($hasil))) {
            echo json_encode(['success' => false, 'message' => 'Hasil kerja harus diisi']);
            return;
        }

        // Handle image upload
        $image_filename = $submission[0]['submission_image']; // Keep existing image by default
        $upload_error = '';

        if ($is_review_quest && !empty($_FILES['submission_image']['name'])) {
            // Configure upload
            $config['upload_path'] = './assets/uploads/side_quest_user_images/';
            $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
            $config['max_size'] = 2048; // 2MB
            $config['encrypt_name'] = TRUE;

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('submission_image')) {
                $upload_data = $this->upload->data();

                // Delete old image if it exists and is different
                if ($image_filename && $image_filename !== $upload_data['file_name']) {
                    $old_image_path = './assets/uploads/side_quest_user_images/' . $image_filename;
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }

                $image_filename = $upload_data['file_name'];
            } else {
                $upload_error = $this->upload->display_errors('', '');
                echo json_encode(['success' => false, 'message' => 'Upload error: ' . $upload_error]);
                return;
            }
        }

        // Prepare update data
        $update_data = [
            'hasil' => $hasil,
            'hasil_html' => $hasil_html,
            'submitted_at' => date('Y-m-d H:i:s')
        ];

        if ($is_review_quest) {
            $update_data['submission_title'] = $submission_title;
            $update_data['submission_image'] = $image_filename;
        }

        // Update the submission
        $update_result = $this->mymodel->updateData('side_quest_submissions', $update_data, ['id' => $submission_id]);

        if ($update_result) {
            echo json_encode(['success' => true, 'message' => 'Quest berhasil diupdate!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupdate quest. Silakan coba lagi.']);
        }
    }

    public function delete_review()
    {
        $user = $_SESSION['user'];
        $submission_id = $_POST['submission_id'];

        if (!$submission_id) {
            echo json_encode(['status' => 'error', 'message' => 'Submission ID is required']);
            return;
        }

        // Get user profile ID
        $profile_query = $this->mymodel->selectWithQuery("SELECT id FROM user_profile WHERE user_id = '{$user['id']}'");
        if (empty($profile_query)) {
            echo json_encode(['status' => 'error', 'message' => 'User profile not found']);
            return;
        }
        $profile_id = $profile_query[0]['id'];

        // Verify the submission belongs to the user and is deletable (Film/Book quest, any status)
        $submission_query = $this->mymodel->selectWithQuery("
            SELECT sqs.*, sq.title as quest_title, sq.id as quest_id
            FROM side_quest_submissions sqs 
            LEFT JOIN side_quests sq ON sqs.quest_id = sq.id 
            WHERE sqs.id = '$submission_id' 
            AND sqs.user_profile_id = '$profile_id'
            AND (LOWER(sq.title) LIKE '%nonton film%' OR LOWER(sq.title) LIKE '%baca buku%')
        ");

        if (empty($submission_query)) {
            echo json_encode(['status' => 'error', 'message' => 'Review not found or not deletable (only Film and Book reviews can be deleted)']);
            return;
        }

        $submission = $submission_query[0];

        try {
            // Delete associated image file if exists
            if (!empty($submission['submission_image'])) {
                $image_path = FCPATH . 'assets/uploads/side_quest_user_images/' . $submission['submission_image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            // Delete the submission record
            $delete_result = $this->mymodel->deleteData('side_quest_submissions', array('id' => $submission_id));

            if ($delete_result) {
                echo json_encode(['status' => 'success', 'message' => 'Review berhasil dihapus']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus review dari database']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    // =====================================================
    // CAREER PROGRESSION METHODS FOR EMPLOYEES
    // =====================================================

    /**
     * Get career progression data for profile widget
     */
    public function get_career_widget_data($user_id = null)
    {
        if (!$user_id) {
            $user_id = $_SESSION['user']['id'];
        }

        // Get user profile with position information
        $profile = $this->mymodel->selectWithQuery("
            SELECT 
                up.*,
                p.name as position_name,
                p.department,
                p.id as position_id,
                ql.name as level_name,
                TIMESTAMPDIFF(MONTH, up.created_at, NOW()) as months_in_role
            FROM user_profile up 
            LEFT JOIN positions p ON up.position_id = p.id 
            LEFT JOIN quest_levels ql ON p.level_id = ql.id 
            WHERE up.user_id = '$user_id'
        ");

        if (empty($profile)) {
            return ['error' => 'Profile not found'];
        }

        $profile = $profile[0];
        $profile['time_in_role'] = $this->format_time_in_role($profile['months_in_role']);

        // Get available career paths
        $career_paths = $this->get_user_career_paths($profile['position_id'], $profile['id']);

        // Get promotion quests
        $promotion_quests = $this->get_available_promotion_quests($profile['position_id']);

        // Check for pending quest
        $pending_quest = $this->mymodel->selectWithQuery("
            SELECT mq.title, mqs.created_at
            FROM main_quest_submissions mqs
            JOIN main_quests mq ON mqs.quest_id = mq.id
            WHERE mqs.user_profile_id = '{$profile['id']}'
            AND mqs.status = 'pending'
            ORDER BY mqs.created_at DESC
            LIMIT 1
        ");

        // Calculate overall career progress
        $career_progress = $this->calculate_career_progress($profile['id']);

        return [
            'user_position' => $profile,
            'available_paths' => array_slice($career_paths, 0, 3), // Top 3 paths
            'promotion_quests' => $promotion_quests,
            'pending_quest' => !empty($pending_quest) ? $pending_quest[0] : null,
            'career_progress' => $career_progress
        ];
    }

    /**
     * Full career paths page for employees
     */
    public function career_paths()
    {
        $user_id = $_SESSION['user']['id'];

        // Get current position with career_paths JSON and selected path
        $current_position_data = $this->mymodel->selectWithQuery("
            SELECT
                p.id as position_id,
                p.name,
                p.department,
                p.career_paths,
                ql.name as level_name,
                up.preferred_career_path_id,
                up.career_path_selected_at
            FROM user_profile up
            JOIN positions p ON up.position_id = p.id
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            WHERE up.user_id = '$user_id'
        ");

        if (empty($current_position_data)) {
            redirect(base_url() . 'profile');
        }

        $data['current_position'] = $current_position_data[0];
        $position_id = $current_position_data[0]['position_id'];
        $selected_path_id = $current_position_data[0]['preferred_career_path_id'] ?? null;

        // Parse career_paths JSON to get available advancement options
        $data['career_paths'] = [];
        $data['selected_path_id'] = $selected_path_id;

        if (!empty($current_position_data[0]['career_paths'])) {
            $paths_json = json_decode($current_position_data[0]['career_paths'], true);

            if (isset($paths_json['next_roles']) && is_array($paths_json['next_roles'])) {
                foreach ($paths_json['next_roles'] as $path) {
                    // Check if there's a quest available for this path and fetch its requirements
                    $quest_check = $this->mymodel->selectWithQuery("
                        SELECT id, title, requirements
                        FROM main_quests
                        WHERE target_position_id = '" . $path['position_id'] . "'
                        AND is_promotion_quest = 1
                        LIMIT 1
                    ");

                    // Get requirements ONLY from quest (no fallback to career_paths JSON)
                    $requirements = [];
                    if (!empty($quest_check) && !empty($quest_check[0]['requirements'])) {
                        // Use requirements from main quest
                        $requirements = json_decode($quest_check[0]['requirements'], true) ?? [];
                    }
                    // Note: No fallback to career_paths JSON - requirements come from quests only

                    // Calculate readiness score based on quest requirements
                    $readiness_score = !empty($requirements) ? $this->calculate_path_readiness($user_id, $requirements) : 0;

                    // Get target position details for department
                    $target_position_details = $this->mymodel->selectWhere('positions', ['id' => $path['position_id']]);
                    $target_department = !empty($target_position_details) ? $target_position_details[0]['department'] : $data['current_position']['department'];

                    $data['career_paths'][] = [
                        'id' => $path['position_id'],
                        'target_position' => $path['position_name'],
                        'target_department' => $target_department,
                        'progression_type' => $path['path_type'],
                        'estimated_years' => 'N/A', // No timeline in simplified structure
                        'readiness_score' => $readiness_score,
                        'readiness_level' => $readiness_score >= 75 ? 'high' : ($readiness_score >= 50 ? 'medium' : 'low'),
                        'priority' => $path['path_type'] === 'vertical_technical' ? 'high' : 'medium',
                        'steps' => [
                            ['name' => $data['current_position']['name']],
                            ['name' => $path['position_name']]
                        ],
                        'has_quest' => !empty($quest_check),
                        'quest_title' => $quest_check[0]['title'] ?? null,
                        'quest_id' => $quest_check[0]['id'] ?? null,
                        'requirements' => $requirements,
                        'requirements_check' => !empty($requirements) ? $this->format_requirements_check($requirements) : [],
                        'is_selected' => ($selected_path_id == $path['position_id'])
                    ];
                }
            }
        }

        // If no career_paths JSON, fallback to old career_progressions table
        if (empty($data['career_paths'])) {
            $simple_paths = $this->mymodel->selectWithQuery("
                SELECT
                    cp.*,
                    tp.name as target_position,
                    tp.department as target_department,
                    mq.id as quest_id,
                    mq.title as quest_title
                FROM career_progressions cp
                JOIN positions tp ON cp.target_position_id = tp.id
                LEFT JOIN main_quests mq ON mq.target_position_id = tp.id AND mq.is_promotion_quest = 1
                WHERE cp.source_position_id = '$position_id'
                AND cp.is_active = 1
                ORDER BY cp.progression_type, tp.name
            ");

            foreach ($simple_paths as $path) {
                $data['career_paths'][] = [
                    'id' => $path['id'],
                    'target_position' => $path['target_position'],
                    'target_department' => $path['target_department'] ?: 'Same Department',
                    'progression_type' => $path['progression_type'] ?: 'direct',
                    'estimated_years' => $path['estimated_years'] ?: '2-3',
                    'readiness_score' => 75,
                    'readiness_level' => 'medium',
                    'priority' => 'medium',
                    'steps' => [
                        ['name' => $data['current_position']['name']],
                        ['name' => $path['target_position']]
                    ],
                    'has_quest' => !empty($path['quest_id']),
                    'quest_title' => $path['quest_title'],
                    'requirements_check' => [
                        ['description' => 'Complete current role responsibilities', 'status' => 'partial']
                    ]
                ];
            }
        }

        $data['career_insights'] = $this->generate_career_insights($this->get_user_profile_id($user_id));

        $data['title'] = 'My Career Paths - ' . $this->template->title();
        $data['content'] = $this->load->view("profile/career_paths_full", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    /**
     * Calculate user's readiness for a career path
     */
    private function calculate_path_readiness($user_id, $requirements)
    {
        // Simple scoring based on requirements
        $total_score = 100;
        $deductions = 0;

        // Check if requirements is the direct requirements array or needs to be extracted
        if (isset($requirements['requirements'])) {
            // Old format - requirements wrapped in another 'requirements' key
            $reqs = $requirements['requirements'];
        } else {
            // New format - requirements passed directly
            $reqs = $requirements;
        }

        // Check experience years (placeholder - would need actual user data)
        if (isset($reqs['experience_years'])) {
            // Deduct points if requirement not met
            // This is simplified - you'd check actual user experience
            $deductions += 10;
        }

        // Check projects
        if (isset($reqs['completed_projects'])) {
            $deductions += 10;
        }

        // Check certifications
        if (isset($reqs['certifications']) && !empty($reqs['certifications'])) {
            $deductions += 15;
        }

        return max(0, $total_score - $deductions);
    }

    /**
     * Format requirements into check list
     */
    private function format_requirements_check($requirements)
    {
        $checks = [];

        if (isset($requirements['experience_years'])) {
            $checks[] = [
                'description' => $requirements['experience_years'] . '+ years of experience',
                'status' => 'partial'
            ];
        }

        if (isset($requirements['completed_projects'])) {
            $checks[] = [
                'description' => $requirements['completed_projects'] . ' completed projects',
                'status' => 'unmet'
            ];
        }

        if (isset($requirements['skills']) && is_array($requirements['skills'])) {
            foreach ($requirements['skills'] as $skill) {
                $checks[] = [
                    'description' => $skill,
                    'status' => 'partial'
                ];
            }
        }

        if (isset($requirements['certifications']) && is_array($requirements['certifications'])) {
            foreach ($requirements['certifications'] as $cert) {
                $checks[] = [
                    'description' => $cert,
                    'status' => 'unmet'
                ];
            }
        }

        return $checks;
    }

    /**
     * Choose preferred career path
     */
    public function choose_career_path()
    {
        $user_id = $_SESSION['user']['id'];

        // Get target position ID from request
        $target_position_id = $this->input->post('target_position_id');
        $path_type = $this->input->post('path_type');

        if (!$target_position_id) {
            echo json_encode(['success' => false, 'message' => 'Target position is required']);
            return;
        }

        // Get user profile
        $profile = $this->mymodel->selectWhere('user_profile', ['user_id' => $user_id]);

        if (empty($profile)) {
            echo json_encode(['success' => false, 'message' => 'User profile not found']);
            return;
        }

        $profile_id = $profile[0]['id'];
        $current_position_id = $profile[0]['position_id'];

        // Verify the target position is a valid career path option
        $current_position = $this->mymodel->selectWhere('positions', ['id' => $current_position_id]);

        if (empty($current_position)) {
            echo json_encode(['success' => false, 'message' => 'Current position not found']);
            return;
        }

        $is_valid_path = false;

        if (!empty($current_position[0]['career_paths'])) {
            $paths_json = json_decode($current_position[0]['career_paths'], true);

            if (isset($paths_json['next_roles'])) {
                foreach ($paths_json['next_roles'] as $path) {
                    if ($path['position_id'] == $target_position_id) {
                        $is_valid_path = true;
                        break;
                    }
                }
            }
        }

        if (!$is_valid_path) {
            echo json_encode(['success' => false, 'message' => 'Invalid career path selection']);
            return;
        }

        // Update user profile with selected career path
        $update_data = [
            'preferred_career_path_id' => $target_position_id,
            'career_path_selected_at' => date('Y-m-d H:i:s')
        ];

        $result = $this->mymodel->updateData('user_profile', $update_data, ['id' => $profile_id]);

        if ($result) {
            // Get target position name
            $target_position = $this->mymodel->selectWhere('positions', ['id' => $target_position_id]);
            $target_name = $target_position[0]['name'] ?? 'Unknown Position';

            echo json_encode([
                'success' => true,
                'message' => 'Career path selected successfully!',
                'selected_path' => $target_name,
                'path_type' => $path_type
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save career path selection']);
        }
    }

    /**
     * Career progression recommendations for employee
     */
    public function career_recommendations()
    {
        $user_id = $_SESSION['user']['id'];
        $profile_id = $this->get_user_profile_id($user_id);

        // CI3 tidak punya load->controller(); arahkan ke halaman Quest
        if (empty($profile_id)) {
            $this->session->set_flashdata('msg', 'Data profil karier belum lengkap.');
            redirect(base_url() . 'profile');
            return;
        }
        redirect(base_url() . 'quest/career_progression_recommendations/' . $profile_id);
    }

    // =====================================================
    // PRIVATE HELPER METHODS
    // =====================================================

    /**
     * Get user's career paths with readiness assessment
     */
    private function get_user_career_paths($position_id, $profile_id)
    {
        $paths = $this->mymodel->selectWithQuery("
            SELECT 
                cp.*,
                tp.name as target_position,
                tp.department as target_department,
                tql.name as target_level,
                mq.id as quest_id,
                mq.title as quest_title
            FROM career_progressions cp
            JOIN positions tp ON cp.target_position_id = tp.id
            LEFT JOIN quest_levels tql ON tp.level_id = tql.id
            LEFT JOIN main_quests mq ON mq.target_position_id = tp.id AND mq.is_promotion_quest = 1
            WHERE cp.source_position_id = '$position_id'
            AND cp.is_active = 1
            ORDER BY cp.progression_type, tp.name
        ");

        // Add readiness assessment to each path
        foreach ($paths as &$path) {
            $readiness = $this->assess_path_readiness($profile_id, $path);
            $path['readiness_score'] = $readiness['score'];
            $path['readiness'] = $readiness['level'];
            $path['estimated_timeline'] = $this->format_timeline($path['estimated_years']);
        }

        return $paths;
    }

    /**
     * Get detailed career paths with full progression steps
     */
    private function get_detailed_career_paths($position_id, $profile_id)
    {
        // Get all possible paths using recursive traversal
        $all_paths = $this->get_all_career_paths_recursive($position_id);

        $detailed_paths = [];

        foreach ($all_paths as $path) {
            if (count($path) <= 1) continue; // Skip single-step paths

            $target_position = end($path);

            // Get progression details
            $progression = $this->mymodel->selectWithQuery("
                SELECT cp.*, tp.name as target_name, tp.department
                FROM career_progressions cp
                JOIN positions tp ON cp.target_position_id = tp.id
                WHERE cp.source_position_id = '$position_id'
                AND cp.target_position_id = '{$target_position['id']}'
                AND cp.is_active = 1
                LIMIT 1
            ");

            if (empty($progression)) continue;

            $prog = $progression[0];

            // Assess readiness
            $readiness = $this->assess_path_readiness($profile_id, $prog);

            // Check for promotion quest
            $quest = $this->mymodel->selectWithQuery("
                SELECT id, title 
                FROM main_quests 
                WHERE target_position_id = '{$target_position['id']}'
                AND is_promotion_quest = 1 
                LIMIT 1
            ");

            $detailed_paths[] = [
                'id' => $prog['id'],
                'target_position' => $prog['target_name'],
                'target_department' => $prog['department'],
                'progression_type' => $prog['progression_type'],
                'estimated_years' => $prog['estimated_years'],
                'readiness_score' => $readiness['score'],
                'readiness_level' => $readiness['level'],
                'priority' => $this->get_path_priority($readiness['score']),
                'steps' => $path,
                'has_quest' => !empty($quest),
                'quest_title' => !empty($quest) ? $quest[0]['title'] : null,
                'requirements_check' => $this->check_path_requirements($profile_id, $prog)
            ];
        }

        // Sort by readiness score
        usort($detailed_paths, function ($a, $b) {
            return $b['readiness_score'] - $a['readiness_score'];
        });

        return $detailed_paths;
    }

    /**
     * Get all career paths using recursive traversal
     */
    private function get_all_career_paths_recursive($position_id, $current_path = [], $visited = [])
    {
        if (in_array($position_id, $visited)) {
            return [$current_path]; // Prevent cycles
        }

        $visited[] = $position_id;

        // Get position info
        $position = $this->mymodel->selectWithQuery("
            SELECT id, name FROM positions WHERE id = '$position_id'
        ");

        if (empty($position)) return [];

        $current_path[] = $position[0];

        // Get next positions
        $next_positions = $this->mymodel->selectWithQuery("
            SELECT target_position_id as id, tp.name
            FROM career_progressions cp
            JOIN positions tp ON cp.target_position_id = tp.id
            WHERE cp.source_position_id = '$position_id'
            AND cp.is_active = 1
        ");

        $all_paths = [];

        if (empty($next_positions)) {
            // Terminal node
            $all_paths[] = $current_path;
        } else {
            // Continue recursion
            foreach ($next_positions as $next_pos) {
                $sub_paths = $this->get_all_career_paths_recursive(
                    $next_pos['id'],
                    $current_path,
                    $visited
                );
                $all_paths = array_merge($all_paths, $sub_paths);
            }
        }

        return $all_paths;
    }

    /**
     * Assess readiness for a career path
     */
    private function assess_path_readiness($profile_id, $path)
    {
        $factors = [];

        // Experience factor
        if ($path['estimated_years']) {
            // This is simplified - would need actual time tracking
            $factors['experience'] = 60; // Placeholder
        } else {
            $factors['experience'] = 50;
        }

        // Performance factor (based on quest history) - simplified without score columns
        $performance = $this->mymodel->selectWithQuery("
            SELECT COUNT(*) as total_approved
            FROM main_quest_submissions 
            WHERE user_profile_id = '$profile_id' 
            AND status = 'approved'
        ");

        if (!empty($performance) && $performance[0]['total_approved']) {
            // Simple performance factor based on number of approved quests
            $factors['performance'] = min(100, $performance[0]['total_approved'] * 25); // Each approved quest = 25%
        } else {
            $factors['performance'] = 50;
        }

        // Requirements factor
        if ($path['min_performance_rating']) {
            $factors['requirements'] = 70; // Simplified
        } else {
            $factors['requirements'] = 80;
        }

        $total_score = array_sum($factors) / count($factors);

        $level = 'low';
        if ($total_score >= 80) $level = 'high';
        elseif ($total_score >= 60) $level = 'medium';

        return [
            'score' => round($total_score),
            'level' => $level,
            'factors' => $factors
        ];
    }

    /**
     * Check specific requirements for a career path
     */
    private function check_path_requirements($profile_id, $path)
    {
        $requirements = [];

        // Parse requirements text into checkable items
        if ($path['requirements']) {
            $req_lines = explode("\n", $path['requirements']);
            foreach ($req_lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                $status = 'partial'; // Default status

                // Simple keyword-based assessment
                if (strpos(strtolower($line), 'year') !== false) {
                    $status = 'met'; // Simplified
                } elseif (strpos(strtolower($line), 'performance') !== false) {
                    $status = 'met';
                } elseif (strpos(strtolower($line), 'leadership') !== false) {
                    $status = 'partial';
                }

                $requirements[] = [
                    'description' => $line,
                    'status' => $status
                ];
            }
        }

        if (empty($requirements)) {
            $requirements[] = [
                'description' => 'General qualification requirements apply',
                'status' => 'partial'
            ];
        }

        return $requirements;
    }

    /**
     * Generate career insights for employee
     */
    private function generate_career_insights($profile_id)
    {
        $insights = [];

        // Quest completion insight
        $quest_stats = $this->mymodel->selectWithQuery("
            SELECT 
                COUNT(*) as total_applied,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved
            FROM main_quest_submissions 
            WHERE user_profile_id = '$profile_id'
        ");

        if (!empty($quest_stats) && $quest_stats[0]['total_applied'] > 0) {
            $stats = $quest_stats[0];
            $approval_rate = ($stats['approved'] / $stats['total_applied']) * 100;

            if ($approval_rate >= 80) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => 'trophy',
                    'title' => 'Excellent Quest Performance',
                    'description' => "You have a {$approval_rate}% quest approval rate. Keep up the great work!"
                ];
            } elseif ($approval_rate >= 60) {
                $insights[] = [
                    'type' => 'info',
                    'icon' => 'chart-line',
                    'title' => 'Good Quest Progress',
                    'description' => "Your quest approval rate is {$approval_rate}%. Consider focusing on quality improvements."
                ];
            } else {
                $insights[] = [
                    'type' => 'warning',
                    'icon' => 'exclamation-triangle',
                    'title' => 'Quest Performance Needs Attention',
                    'description' => 'Focus on improving quest submissions for better career advancement opportunities.'
                ];
            }
        }

        // Career path availability
        $available_paths = $this->mymodel->selectWithQuery("
            SELECT COUNT(*) as path_count
            FROM career_progressions cp
            JOIN user_profile up ON cp.source_position_id = up.position_id
            WHERE up.id = '$profile_id' AND cp.is_active = 1
        ");

        if (!empty($available_paths) && $available_paths[0]['path_count'] > 0) {
            $count = $available_paths[0]['path_count'];
            $insights[] = [
                'type' => 'success',
                'icon' => 'route',
                'title' => 'Multiple Career Paths Available',
                'description' => "You have {$count} career progression paths available. Explore your options!"
            ];
        }

        return $insights;
    }

    /**
     * Helper methods
     */
    private function format_time_in_role($months)
    {
        if ($months < 12) {
            return "{$months} month" . ($months != 1 ? 's' : '');
        } else {
            $years = floor($months / 12);
            $remaining_months = $months % 12;
            $text = "{$years} year" . ($years != 1 ? 's' : '');
            if ($remaining_months > 0) {
                $text .= ", {$remaining_months} month" . ($remaining_months != 1 ? 's' : '');
            }
            return $text;
        }
    }

    private function format_timeline($years)
    {
        if (!$years) return 'Timeline varies';
        if ($years == 1) return '1 year';
        return "{$years} years";
    }

    private function get_path_priority($score)
    {
        if ($score >= 80) return 'high';
        if ($score >= 60) return 'medium';
        return 'low';
    }

    private function get_user_profile_id($user_id)
    {
        $profile = $this->mymodel->selectWithQuery("SELECT id FROM user_profile WHERE user_id = '$user_id'");
        return !empty($profile) ? $profile[0]['id'] : null;
    }

    private function get_user_position_id($user_id)
    {
        $profile = $this->mymodel->selectWithQuery("SELECT position_id FROM user_profile WHERE user_id = '$user_id'");
        return !empty($profile) ? $profile[0]['position_id'] : null;
    }

    private function get_available_promotion_quests($position_id)
    {
        return $this->mymodel->selectWithQuery("
            SELECT mq.*, tp.name as target_position
            FROM main_quests mq
            JOIN career_progressions cp ON mq.target_position_id = cp.target_position_id
            JOIN positions tp ON cp.target_position_id = tp.id
            WHERE cp.source_position_id = '$position_id'
            AND mq.is_promotion_quest = 1
            AND cp.is_active = 1
        ");
    }

    private function calculate_career_progress($profile_id)
    {
        // Simple career progress calculation based on completed quests
        $completed = $this->mymodel->selectWithQuery("
            SELECT COUNT(*) as count 
            FROM main_quest_submissions 
            WHERE user_profile_id = '$profile_id' AND status = 'approved'
        ");

        $total_possible = 10; // Simplified assumption
        $completed_count = !empty($completed) ? $completed[0]['count'] : 0;

        return [
            'percentage' => min(100, ($completed_count / $total_possible) * 100),
            'completed' => $completed_count,
            'total' => $total_possible
        ];
    }

    /**
     * Get position-based career progress for user profile
     */
    private function get_position_career_progress($user_id, $profile)
    {
        if (empty($profile['position_id'])) {
            return array();
        }

        $position_id = $profile['position_id'];
        $profile_id = $profile['id'] ?? null;

        // Get available career paths from positions.career_paths JSON
        $position_data = $this->mymodel->selectWithQuery("
            SELECT career_paths FROM positions WHERE id = '$position_id'
        ");

        if (empty($position_data) || empty($position_data[0]['career_paths'])) {
            return array();
        }

        $career_paths_json = json_decode($position_data[0]['career_paths'], true);

        if (empty($career_paths_json['next_roles'])) {
            return array();
        }

        // Build available paths array from JSON
        $available_paths = array();
        foreach ($career_paths_json['next_roles'] as $path) {
            // Get target position details
            $target_position = $this->mymodel->selectWithQuery("
                SELECT p.*, ql.name as target_level, ql.level_order as target_level_order
                FROM positions p
                LEFT JOIN quest_levels ql ON p.level_id = ql.id
                WHERE p.id = '{$path['position_id']}'
            ");

            if (!empty($target_position)) {
                $available_paths[] = [
                    'target_position_id' => $path['position_id'],
                    'target_position_name' => $target_position[0]['name'],
                    'target_department' => $target_position[0]['department'] ?? '',
                    'target_level' => $target_position[0]['target_level'] ?? '',
                    'target_level_order' => $target_position[0]['target_level_order'] ?? 0,
                    'path_type' => $path['path_type'] ?? 'vertical_technical'
                ];
            }
        }

        if (empty($available_paths)) {
            return array();
        }

        // Calculate readiness for each path
        foreach ($available_paths as &$path) {
            $readiness_data = $this->calculate_career_path_readiness($user_id, $profile_id, $path['target_position_id']);
            $path['readiness_percentage'] = $readiness_data['percentage'];
            $path['requirements_status'] = $readiness_data['requirements_status'];
            $path['next_steps'] = $readiness_data['next_steps'];
        }

        // Determine if user has any ready paths
        $ready_paths = array_filter($available_paths, function ($path) {
            return $path['readiness_percentage'] >= 80;
        });

        return [
            'available_paths' => $available_paths,
            'has_ready_paths' => !empty($ready_paths),
            'ready_count' => count($ready_paths),
            'total_count' => count($available_paths)
        ];
    }

    /**
     * Calculate career path readiness based on quest completion
     */
    private function calculate_career_path_readiness($user_id, $profile_id, $target_position_id)
    {
        if (!$profile_id) {
            return [
                'percentage' => 0,
                'requirements_status' => 'Profile not found',
                'next_steps' => ['Complete profile setup']
            ];
        }

        // Get main quests required for target position
        $required_quests = $this->mymodel->selectWithQuery("
            SELECT mq.id, mq.title, mq.description
            FROM main_quests mq
            WHERE mq.target_position_id = '$target_position_id'
            OR mq.required_position_id = '$target_position_id'
        ");

        if (empty($required_quests)) {
            // No specific quests required, check general readiness
            $completed_quests = $this->mymodel->selectWithQuery("
                SELECT COUNT(*) as count 
                FROM main_quest_submissions mqs
                WHERE mqs.user_profile_id = '$profile_id' 
                AND mqs.status = 'approved'
            ");

            $completed_count = !empty($completed_quests) ? intval($completed_quests[0]['count']) : 0;

            // Simple readiness calculation
            $base_percentage = min(100, $completed_count * 20); // Each quest = 20%

            return [
                'percentage' => $base_percentage,
                'requirements_status' => $completed_count > 0 ? "$completed_count approved quests completed" : "No approved quests yet",
                'next_steps' => $completed_count > 0 ? ['Continue excellent work', 'Apply when ready'] : ['Complete available quests', 'Build experience']
            ];
        }

        // Check completion status for required quests
        $required_quest_ids = array_column($required_quests, 'id');
        $quest_ids_string = implode(',', $required_quest_ids);

        $completed_required = $this->mymodel->selectWithQuery("
            SELECT mqs.quest_id
            FROM main_quest_submissions mqs
            WHERE mqs.user_profile_id = '$profile_id' 
            AND mqs.quest_id IN ($quest_ids_string)
            AND mqs.status = 'approved'
        ");

        $completed_ids = array_column($completed_required, 'quest_id');
        $completion_rate = count($completed_ids) / count($required_quest_ids);
        $percentage = intval($completion_rate * 100);

        // Generate requirements status
        $requirements_status = count($completed_ids) . '/' . count($required_quest_ids) . ' required quests completed';

        // Generate next steps
        $next_steps = [];
        if ($percentage >= 80) {
            $next_steps = ['Ready to apply', 'Submit quest application'];
        } elseif ($percentage >= 50) {
            $next_steps = ['Complete remaining quests', 'Build more experience'];
        } else {
            $next_steps = ['Start completing required quests', 'Focus on skill development'];
        }

        return [
            'percentage' => $percentage,
            'requirements_status' => $requirements_status,
            'next_steps' => $next_steps
        ];
    }

    // =====================================================
    // COMMUNITY REVIEWS METHODS
    // =====================================================

    /**
     * Display all approved reviews from all users
     */
    public function reviews()
    {
        // Get all approved reviews from all users with reviewer information
        $all_reviews = $this->mymodel->selectWithQuery("
            SELECT 
                sqs.*, 
                sq.title as quest_title, 
                sq.description as quest_description,
                sqs.submission_title, 
                sqs.submission_image, 
                sqs.hasil as review_content,
                sqs.hasil_html,
                u.username, 
                u.full_name,
                sqs.approved_at
            FROM side_quest_submissions sqs 
            LEFT JOIN side_quests sq ON sqs.quest_id = sq.id 
            LEFT JOIN user_profile up ON sqs.user_profile_id = up.id
            LEFT JOIN user u ON up.user_id = u.id
            WHERE sqs.status = 'approved'
            AND sqs.submission_title IS NOT NULL
            AND sqs.submission_title != ''
            AND (LOWER(sq.title) LIKE '%film%' OR LOWER(sq.title) LIKE '%movie%' OR LOWER(sq.title) LIKE '%book%' OR LOWER(sq.title) LIKE '%buku%')
            ORDER BY sqs.approved_at DESC, sqs.submitted_at DESC
        ");

        $data['all_reviews'] = $all_reviews;
        $data['title'] = 'Community Reviews - ' . $this->template->title();
        $data['content'] = $this->load->view("profile/reviews", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    /**
     * Get review details for community reviews (accessible by all users)
     */
    public function get_community_review_detail()
    {
        $submission_id = $_POST['submission_id'];

        if (!$submission_id) {
            echo json_encode(['error' => 'Submission ID is required']);
            return;
        }

        // Get submission details for approved reviews only (no user restriction)
        $review = $this->mymodel->selectWithQuery("
            SELECT 
                sqs.*, 
                sq.title as quest_title, 
                sq.description as quest_description,
                u.username,
                u.full_name
            FROM side_quest_submissions sqs 
            LEFT JOIN side_quests sq ON sqs.quest_id = sq.id 
            LEFT JOIN user_profile up ON sqs.user_profile_id = up.id
            LEFT JOIN user u ON up.user_id = u.id
            WHERE sqs.id = '$submission_id'
            AND sqs.status = 'approved'
            AND sqs.submission_title IS NOT NULL
            AND (LOWER(sq.title) LIKE '%film%' OR LOWER(sq.title) LIKE '%movie%' OR LOWER(sq.title) LIKE '%book%' OR LOWER(sq.title) LIKE '%buku%')
        ");

        if (!empty($review)) {
            // Format data for JSON response
            $review_data = $review[0];
            $review_data['submitted_at_formatted'] = date('d M Y H:i', strtotime($review_data['submitted_at']));
            if ($review_data['approved_at']) {
                $review_data['approved_at_formatted'] = date('d M Y H:i', strtotime($review_data['approved_at']));
            }

            echo json_encode($review_data);
        } else {
            echo json_encode(['error' => 'Review not found or not approved']);
        }
    }

    /**
     * Get statistics for community reviews
     */
    public function get_community_review_stats()
    {
        $stats = $this->mymodel->selectWithQuery("
            SELECT
                COUNT(*) as total_reviews,
                COUNT(DISTINCT up.user_id) as unique_reviewers,
                SUM(CASE WHEN LOWER(sq.title) LIKE '%nonton film%' THEN 1 ELSE 0 END) as film_reviews,
                SUM(CASE WHEN LOWER(sq.title) LIKE '%baca buku%' THEN 1 ELSE 0 END) as book_reviews,
                DATE(MAX(sqs.approved_at)) as last_review_date
            FROM side_quest_submissions sqs
            LEFT JOIN side_quests sq ON sqs.quest_id = sq.id
            LEFT JOIN user_profile up ON sqs.user_profile_id = up.id
            WHERE sqs.status = 'approved'
            AND sqs.submission_title IS NOT NULL
            AND sqs.submission_title != ''
            AND (LOWER(sq.title) LIKE '%nonton film%' OR LOWER(sq.title) LIKE '%baca buku%')
        ");

        return !empty($stats) ? $stats[0] : [
            'total_reviews' => 0,
            'unique_reviewers' => 0,
            'film_reviews' => 0,
            'book_reviews' => 0,
            'last_review_date' => null
        ];
    }

    /**
     * Get selected career path details with quest status
     * SIMPLIFIED: No requirements, just quest status
     */
    private function get_selected_career_path_details($user_id, $current_position_id, $target_position_id)
    {
        // Get career path from positions.career_paths JSON
        $current_position = $this->mymodel->selectWithQuery("
            SELECT career_paths FROM positions WHERE id = '$current_position_id'
        ");

        if (empty($current_position) || empty($current_position[0]['career_paths'])) {
            return null;
        }

        // Find the selected path in JSON
        $paths_json = json_decode($current_position[0]['career_paths'], true);
        $selected_path = null;

        if (!empty($paths_json['next_roles'])) {
            foreach ($paths_json['next_roles'] as $path) {
                if ($path['position_id'] == $target_position_id) {
                    $selected_path = $path;
                    break;
                }
            }
        }

        if (!$selected_path) {
            return null;
        }

        // Get quest for this career path
        $quest = $this->mymodel->selectWithQuery("
            SELECT id, title
            FROM main_quests
            WHERE target_position_id = '$target_position_id'
            AND is_promotion_quest = 1
            LIMIT 1
        ");

        $quest_id = !empty($quest) ? $quest[0]['id'] : null;
        $quest_status = $this->get_quest_application_status($user_id, $quest_id);

        return [
            'path_type' => $selected_path['path_type'] ?? 'vertical_technical',
            'estimated_timeline' => $selected_path['estimated_timeline'] ?? 'N/A',
            'has_quest' => !empty($quest),
            'quest_title' => $quest[0]['title'] ?? null,
            'quest_id' => $quest_id,
            'quest_status' => $quest_status
        ];
    }

    /**
     * Calculate career path progress based on requirements
     * SIMPLIFIED: No calculations needed - progress is determined by quest completion only
     */
    private function calculate_career_path_progress($user_id, $requirements)
    {
        // No calculations needed
        // Progress is determined by quest completion status only
        return [
            'percentage' => 0,
            'met_count' => 0,
            'total_count' => 0,
            'requirements_status' => []
        ];
    }

    /**
     * Get quest application status for user
     */
    private function get_quest_application_status($user_id, $quest_id)
    {
        if (!$quest_id) {
            return 'no_quest';
        }

        // Get user profile id
        $profile = $this->mymodel->selectWithQuery("
            SELECT id FROM user_profile WHERE user_id = '$user_id'
        ");

        if (empty($profile)) {
            return 'not_applied';
        }

        $profile_id = $profile[0]['id'];

        // Check submission status
        $submission = $this->mymodel->selectWithQuery("
            SELECT status
            FROM main_quest_submissions
            WHERE quest_id = '$quest_id'
            AND user_profile_id = '$profile_id'
            ORDER BY submitted_at DESC
            LIMIT 1
        ");

        if (empty($submission)) {
            return 'not_applied';
        }

        return $submission[0]['status']; // 'pending' or 'approved' or 'denied'
    }
}
