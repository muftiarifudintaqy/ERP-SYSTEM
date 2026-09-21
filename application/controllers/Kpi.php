<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Kpi extends BaseController
{
    private const CUSTOM_KPI_POSITION_OFFSET = 1000000;
    private const KOL_SPECIALIST_POSITION_NAME = 'Kol Specialist';

    protected $public_methods = ['index'];
    protected $method_permissions = [
        'update_row' => 'edit',
        'template_rows' => 'edit',
        'save_template' => 'edit',
        'save_selected_template' => 'edit',
        'delete_position_template' => 'edit',
        'save_run' => 'edit',
        'toggle_run_lock' => 'edit',
        'save_bulk_preset' => 'edit'
    ];
    protected $fyp_views = 0;
    protected $fyp_percentage = 0;
    protected $kpi_position_options_cache = null;
    protected $kpi_custom_tables_ready = null;
    protected $kol_specialist_position_ids_cache = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');
        $this->ensure_endorse_pic_user_id_column();
        $this->load_endorse_fyp_config();
    }

    protected function check_method_permission()
    {
        if (isset($_SESSION['user']['role']) && (string) $_SESSION['user']['role'] === '1') {
            return;
        }
        parent::check_method_permission();
    }

    private function ensure_endorse_pic_user_id_column()
    {
        $exists = $this->mymodel->selectWithQuery("SHOW COLUMNS FROM endorse LIKE 'pic_user_id'");
        if (empty($exists)) {
            $this->db->query("ALTER TABLE endorse ADD COLUMN pic_user_id INT(11) NULL DEFAULT NULL AFTER pic");
        }
    }

    private function load_endorse_fyp_config()
    {
        $config = $this->mymodel->selectWithQuery("SELECT title, value FROM endorse_config WHERE title IN ('fyp_views', 'fyp_persentase')");
        $config_map = [];
        foreach ((array) $config as $row) {
            $key = (string) ($row['title'] ?? '');
            if ($key === '') {
                continue;
            }
            $config_map[$key] = $row['value'] ?? null;
        }

        $this->fyp_views = isset($config_map['fyp_views']) ? (int) $config_map['fyp_views'] : 0;
        $this->fyp_percentage = isset($config_map['fyp_persentase']) ? (int) $config_map['fyp_persentase'] : 0;
    }

    private function ensure_kpi_employee_presets_table()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `kpi_employee_presets` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `month_key` CHAR(7) NOT NULL,
                `employee_id` INT(11) NOT NULL,
                `campaign_ids` TEXT NULL,
                `source_rules_json` LONGTEXT NULL,
                `date_from` DATE NULL,
                `date_until` DATE NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_kpi_preset_month_employee` (`month_key`, `employee_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $this->db->query($sql);
        $exists = $this->mymodel->selectWithQuery("SHOW COLUMNS FROM kpi_employee_presets LIKE 'source_rules_json'");
        if (empty($exists)) {
            $this->db->query("ALTER TABLE kpi_employee_presets ADD COLUMN source_rules_json LONGTEXT NULL AFTER campaign_ids");
        }
    }

    private function normalize_source_rules($rules)
    {
        if (!is_array($rules)) {
            return [];
        }

        $allowed_metrics = ['total_views', 'total_fyp', 'total_konten', 'total_cost', 'cpm'];
        $normalized = [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $campaign_id = (int) ($rule['campaign_id'] ?? 0);
            if ($campaign_id <= 0) {
                continue;
            }
            $metrics = $rule['metrics'] ?? [];
            if (!is_array($metrics)) {
                $metrics = $metrics ? explode(',', (string) $metrics) : [];
            }
            $metrics = array_values(array_unique(array_filter(array_map(function ($metric) use ($allowed_metrics) {
                $metric = $this->sanitize_metric_key((string) $metric);
                return in_array($metric, $allowed_metrics, true) ? $metric : '';
            }, $metrics), 'strlen')));
            if (empty($metrics)) {
                continue;
            }
            $normalized[] = [
                'campaign_id' => $campaign_id,
                'metrics' => $metrics,
            ];
        }

        return $normalized;
    }

    private function decode_source_rules_json($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        if (isset($decoded['rules']) && is_array($decoded['rules'])) {
            return $this->normalize_source_rules($decoded['rules']);
        }
        return $this->normalize_source_rules($decoded);
    }

    private function decode_source_config($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [
                'rules' => [],
                'growth_date_from' => null,
                'growth_date_until' => null,
            ];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [
                'rules' => [],
                'growth_date_from' => null,
                'growth_date_until' => null,
            ];
        }
        if (isset($decoded['rules']) && is_array($decoded['rules'])) {
            $growth_date_from = trim((string) ($decoded['growth_date_from'] ?? ''));
            $growth_date_until = trim((string) ($decoded['growth_date_until'] ?? ''));
            return [
                'rules' => $this->normalize_source_rules($decoded['rules']),
                'growth_date_from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $growth_date_from) ? $growth_date_from : null,
                'growth_date_until' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $growth_date_until) ? $growth_date_until : null,
            ];
        }
        return [
            'rules' => $this->normalize_source_rules($decoded),
            'growth_date_from' => null,
            'growth_date_until' => null,
        ];
    }

    private function encode_source_config($rules, $growth_date_from = null, $growth_date_until = null)
    {
        $rules = $this->normalize_source_rules($rules);
        $growth_date_from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $growth_date_from) ? (string) $growth_date_from : null;
        $growth_date_until = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $growth_date_until) ? (string) $growth_date_until : null;
        if (empty($rules) && !$growth_date_from && !$growth_date_until) {
            return null;
        }
        return json_encode([
            'rules' => $rules,
            'growth_date_from' => $growth_date_from,
            'growth_date_until' => $growth_date_until,
        ]);
    }

    private function build_source_rule_map($rules)
    {
        $map = [];
        foreach ($this->normalize_source_rules($rules) as $rule) {
            $campaign_id = (int) ($rule['campaign_id'] ?? 0);
            if ($campaign_id <= 0) {
                continue;
            }
            $map[$campaign_id] = $rule['metrics'] ?? [];
        }
        return $map;
    }

    private function summarize_source_rules($rules, $campaign_title_map = [])
    {
        $summary = [];
        foreach ($this->normalize_source_rules($rules) as $rule) {
            $campaign_id = (int) ($rule['campaign_id'] ?? 0);
            $metrics = array_values(array_filter(array_map(function ($metric) {
                $labels = [
                    'total_views' => 'Views',
                    'total_fyp' => 'FYP',
                    'total_konten' => 'Total Konten',
                    'total_cost' => 'Total Cost',
                    'cpm' => 'CPM',
                ];
                return $labels[$metric] ?? '';
            }, $rule['metrics'] ?? []), 'strlen'));
            $title = trim((string) ($campaign_title_map[$campaign_id] ?? ''));
            $summary[] = [
                'campaign_id' => $campaign_id,
                'campaign_title' => $title !== '' ? $title : 'Campaign tanpa judul',
                'metrics' => $metrics,
            ];
        }
        return $summary;
    }

    private function get_global_preset($month)
    {
        if (empty($month) || !$this->is_valid_period_key($month)) {
            return null;
        }
        $month_esc = $this->db->escape_str((string) $month);
        $period_type = $this->period_type_from_key($month);
        $rows = $this->mymodel->selectWithQuery("
            SELECT id, month_key, employee_id, campaign_ids, source_rules_json, date_from, date_until
            FROM kpi_employee_presets
            WHERE month_key = '$month_esc'
              AND period_type = '" . $this->db->escape_str($period_type) . "'
              AND employee_id = 0
            LIMIT 1
        ");
        if (empty($rows)) {
            return null;
        }
        $row = $rows[0];
        $campaign_ids = [];
        $raw = trim((string) ($row['campaign_ids'] ?? ''));
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $campaign_ids = array_values(array_filter(array_map('intval', $decoded), function ($id) {
                    return $id > 0;
                }));
            }
        }
        $date_from = (string) ($row['date_from'] ?? '');
        $date_until = (string) ($row['date_until'] ?? '');
        $source_config = $this->decode_source_config($row['source_rules_json'] ?? '');
        return [
            'id' => (int) $row['id'],
            'month_key' => (string) $row['month_key'],
            'employee_id' => 0,
            'campaign_ids' => $campaign_ids,
            'source_rules' => $source_config['rules'],
            'growth_date_from' => $source_config['growth_date_from'],
            'growth_date_until' => $source_config['growth_date_until'],
            'date_from' => ($date_from !== '' && $date_from !== '0000-00-00') ? $date_from : null,
            'date_until' => ($date_until !== '' && $date_until !== '0000-00-00') ? $date_until : null,
        ];
    }

    private function get_global_campaign_options_for_cc($month)
    {
        $current_year = (int) date('Y');
        $window_start = sprintf('%04d-01-01', $current_year);
        $window_end = sprintf('%04d-12-31', $current_year);
        $window_start = $this->db->escape_str($window_start);
        $window_end = $this->db->escape_str($window_end);
        return $this->mymodel->selectWithQuery("
            SELECT id, title
            FROM endorse_campaign
            WHERE status = 'Aktif'
                AND COALESCE(is_internal, 0) = 1
                AND DATE(COALESCE(start_at, '1900-01-01')) <= '$window_end'
                AND DATE(COALESCE(until_at, '2999-12-31')) >= '$window_start'
            ORDER BY title ASC
        ");
    }

    private function get_employee_preset($month, $employee_id)
    {
        $employee_id = (int) $employee_id;
        if ($employee_id <= 0 || empty($month) || !$this->is_valid_period_key($month)) {
            return null;
        }
        $month_esc = $this->db->escape_str((string) $month);
        $period_type = $this->period_type_from_key($month);
        $rows = $this->mymodel->selectWithQuery("
            SELECT id, month_key, employee_id, campaign_ids, source_rules_json, date_from, date_until
            FROM kpi_employee_presets
            WHERE month_key = '$month_esc'
              AND period_type = '" . $this->db->escape_str($period_type) . "'
              AND employee_id = '$employee_id'
            LIMIT 1
        ");
        if (empty($rows)) {
            return null;
        }
        $row = $rows[0];
        $campaign_ids = [];
        $raw = trim((string) ($row['campaign_ids'] ?? ''));
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $campaign_ids = array_values(array_filter(array_map('intval', $decoded), function ($id) {
                    return $id > 0;
                }));
            }
        }
        $date_from = (string) ($row['date_from'] ?? '');
        $date_until = (string) ($row['date_until'] ?? '');
        $source_config = $this->decode_source_config($row['source_rules_json'] ?? '');
        return [
            'id' => (int) $row['id'],
            'month_key' => (string) $row['month_key'],
            'employee_id' => (int) $row['employee_id'],
            'campaign_ids' => $campaign_ids,
            'source_rules' => $source_config['rules'],
            'growth_date_from' => $source_config['growth_date_from'],
            'growth_date_until' => $source_config['growth_date_until'],
            'date_from' => ($date_from !== '' && $date_from !== '0000-00-00') ? $date_from : null,
            'date_until' => ($date_until !== '' && $date_until !== '0000-00-00') ? $date_until : null,
        ];
    }

    private function resolve_employee_run_context($month, $employee_id, $position_id, $fallback_campaign_ids = null)
    {
        $period = $this->get_period_range($month);
        $period_type = $this->period_type_from_key($month);
        $use_raw_window = ($period_type === 'quarter');
        $campaign_ids = is_array($fallback_campaign_ids) ? $fallback_campaign_ids : [];
        $campaign_ids = array_values(array_filter(array_map('intval', $campaign_ids), function ($id) {
            return $id > 0;
        }));

        if ((int) $position_id === 7) {
            $preset = $this->get_employee_preset($month, $employee_id);
            if (!empty($preset)) {
                if (!empty($preset['date_from']) && !empty($preset['date_until'])) {
                    $period['start_date'] = (string) $preset['date_from'];
                    $period['until_date'] = (string) $preset['date_until'];
                }
                if (!empty($preset['campaign_ids'])) {
                    $campaign_ids = $preset['campaign_ids'];
                }
            }
        } elseif ($this->is_kol_specialist_position_id($position_id)) {
            $preset = $this->get_employee_preset($month, $employee_id);
            if (!empty($preset['source_rules'])) {
                $campaign_ids = array_values(array_unique(array_map('intval', array_column($preset['source_rules'], 'campaign_id'))));
            }
        }

        return [
            'period' => $period,
            'campaign_ids' => $campaign_ids,
            'use_raw_window' => $use_raw_window,
        ];
    }

    private function build_default_cc_source_data($month, $employee_id, $campaign_options = null)
    {
        $period = $this->get_period_range($month);
        if (!is_array($campaign_options)) {
            $campaign_options = $this->get_campaign_options_by_employee(
                (int) $employee_id,
                $period['start_date'],
                $period['until_date'],
                7
            );
        }

        $campaign_ids = array_values(array_filter(array_map('intval', array_column($campaign_options, 'id')), function ($id) {
            return $id > 0;
        }));
        $campaign_titles = array_values(array_filter(array_map(function ($campaign) {
            return trim((string) ($campaign['title'] ?? ''));
        }, $campaign_options), function ($title) {
            return $title !== '';
        }));

        return [
            'campaign_ids' => $campaign_ids,
            'campaign_titles' => $campaign_titles,
            'date_from' => (string) ($period['start_date'] ?? ''),
            'date_until' => (string) ($period['until_date'] ?? ''),
            'is_default' => true,
        ];
    }

    public function index()
    {
        $this->ensure_kpi_table();
        $this->ensure_kpi_template_table();
        $this->ensure_kpi_run_tables();
        $this->ensure_crm_kpi_content_table();
        $this->ensure_kpi_period_type_columns();

        $raw_month = trim((string) ($this->input->get('month', true) ?? ''));
        $kol_quarter_view = (int) ($this->input->get('kol_quarter_view', true) ?? 0) === 1;
        $campaign_map = $this->normalize_campaign_map($this->input->get('campaign_map'));
        $is_super_admin = isset($_SESSION['user']['role']) && (string) $_SESSION['user']['role'] === '1';
        $can_edit_kpi = $is_super_admin || (bool) $this->permission->check_permission($this->user_id, 'kpi', 'edit');
        $viewer_position_id = $this->get_employee_kpi_position_id((int) $this->user_id);
        $division_groups = $this->get_kpi_employees_by_division();
        if (!$can_edit_kpi && $viewer_position_id > 0) {
            $viewer_division_key = $this->build_kpi_division_key($viewer_position_id);
            foreach ($division_groups as $division_key => $division) {
                if ($division_key !== $viewer_division_key) {
                    unset($division_groups[$division_key]);
                }
            }
        }
        $allowed_tabs = array_merge(['dashboard'], array_keys($division_groups));
        $requested_tab = trim((string) ($this->input->get('tab', true) ?? ''));
        $active_tab_key = 'content_creator';
        if (in_array($requested_tab, $allowed_tabs, true)) {
            $active_tab_key = $requested_tab;
            $_SESSION['kpi_active_tab'] = $active_tab_key;
        } elseif (!empty($_SESSION['kpi_active_tab']) && in_array($_SESSION['kpi_active_tab'], $allowed_tabs, true)) {
            $active_tab_key = (string) $_SESSION['kpi_active_tab'];
        } elseif (!in_array($active_tab_key, $allowed_tabs, true) && !empty($allowed_tabs)) {
            $active_tab_key = (string) $allowed_tabs[0];
            $_SESSION['kpi_active_tab'] = $active_tab_key;
        }

        $month = $this->normalize_period_key_for_division($raw_month, $active_tab_key);
        $period_type = ($active_tab_key === 'kol_specialist' && !$kol_quarter_view) ? 'month' : 'quarter';
        $dashboard_end_month = $this->normalize_quarter_period_key($this->input->get('dashboard_end_month', true), $this->normalize_quarter_period_key($month));
        $dashboard_start_month = $this->normalize_quarter_period_key($this->input->get('dashboard_start_month', true), $this->add_quarters_to_key($dashboard_end_month, -2));
        if ($this->diff_quarter_keys($dashboard_start_month, $dashboard_end_month) < 0) {
            $tmp = $dashboard_start_month;
            $dashboard_start_month = $dashboard_end_month;
            $dashboard_end_month = $tmp;
        }

        $period = $active_tab_key === 'kol_specialist' && $kol_quarter_view
            ? $this->get_period_range($this->normalize_quarter_period_key($month))
            : $this->get_period_range($month);
        $global_preset = $this->get_global_preset($month);

        foreach ($division_groups as $division_key => &$division) {
            $division['employee_count'] = count($division['employees'] ?? []);
            $division['is_loaded'] = $division_key === $active_tab_key;
            if (!$division['is_loaded']) {
                $division['employees'] = [];
                continue;
            }

            $division_employee_ids = array_values(array_filter(array_map(function ($employee) {
                return (int) ($employee['id'] ?? 0);
            }, $division['employees'] ?? [])));
            $kpi_rows_map = $this->get_or_seed_kpi_rows_map($month, $division['employees'] ?? []);
            $saved_run_map = $this->get_saved_run_snapshot_map($month, $division_employee_ids);

            $filtered_employees = [];
            foreach ($division['employees'] as &$employee) {
                $employee_id = (int) $employee['id'];
                $position_id = (int) ($employee['position_id'] ?? 0);
                $is_kol_position = $this->is_kol_specialist_position_id($position_id);
                $employee_period_key = $is_kol_position ? $month : $this->normalize_quarter_period_key($month);
                $employee_period = ($is_kol_position && !$kol_quarter_view)
                    ? $this->get_period_range($employee_period_key)
                    : $period;
                $campaign_options = $this->get_campaign_options_by_employee(
                    $employee_id,
                    $employee_period['start_date'],
                    $employee_period['until_date'],
                    $position_id
                );
                $employee['kpi_rows'] = $kpi_rows_map[$employee_id] ?? [];

                if ($position_id === 7) {
                    $own_preset = $this->get_employee_preset($month, $employee_id);
                    $default_source = $this->build_default_cc_source_data($month, $employee_id, $campaign_options);
                    $effective_preset = $own_preset ?: $default_source;
                    if (!isset($effective_preset['is_default'])) {
                        $effective_preset['is_default'] = false;
                    }
                    if (empty($effective_preset['campaign_titles'])) {
                        $effective_preset['campaign_titles'] = $default_source['campaign_titles'];
                    }
                    $employee['own_preset'] = $own_preset;
                    $employee['preset'] = $effective_preset;
                    $employee['uses_global_preset'] = false;

                    $selected_campaign_ids = $effective_preset['campaign_ids'] ?? [];
                    $metric_start = !empty($effective_preset['date_from']) ? (string) $effective_preset['date_from'] : $period['start_date'];
                    $metric_until = !empty($effective_preset['date_until']) ? (string) $effective_preset['date_until'] : $period['until_date'];

                    if (empty($selected_campaign_ids)) {
                        $selected_campaign_ids = $campaign_map[$employee_id] ?? [];
                    }

                    $effective_campaign_ids = $selected_campaign_ids;
                    if (empty($effective_campaign_ids) && !empty($campaign_options)) {
                        $effective_campaign_ids = array_values(array_map('intval', array_column($campaign_options, 'id')));
                    }

                    if (!empty($effective_campaign_ids)) {
                        $employee['metrics'] = $this->get_kpi_metrics_from_logs(
                            $metric_start,
                            $metric_until,
                            $employee_id,
                            $effective_campaign_ids,
                            $position_id,
                            $employee['kpi_rows'],
                            $period_type === 'quarter',
                            $period_type
                        );
                    } else {
                        $employee['metrics'] = [
                            'total_views' => 0,
                            'total_fyp' => 0,
                            'total_konten' => 0,
                            'total_cost' => 0,
                            'cpm' => 0,
                            'grouped_campaign' => [],
                            'start_date' => $metric_start,
                            'until_date' => $metric_until,
                        ];
                    }
                    $employee['campaigns'] = $campaign_options;
                    $employee['selected_campaign_ids'] = $selected_campaign_ids;
                } else {
                    $own_preset = $is_kol_position ? $this->get_employee_preset($month, $employee_id) : null;
                    if ($is_kol_position && !empty($own_preset['source_rules'])) {
                        $campaign_title_map = [];
                        foreach ((array) $campaign_options as $campaign_option) {
                            $campaign_title_map[(int) ($campaign_option['id'] ?? 0)] = (string) ($campaign_option['title'] ?? '');
                        }
                        $own_preset['source_rules_summary'] = $this->summarize_source_rules($own_preset['source_rules'], $campaign_title_map);
                    }
                    $selected_campaign_ids = $campaign_map[$employee_id] ?? [];
                    if ($is_kol_position && !empty($own_preset['source_rules'])) {
                        $selected_campaign_ids = array_values(array_unique(array_map('intval', array_column($own_preset['source_rules'], 'campaign_id'))));
                    }
                    $effective_campaign_ids = $selected_campaign_ids;
                    if (empty($effective_campaign_ids) && !empty($campaign_options)) {
                        $effective_campaign_ids = array_values(array_map('intval', array_column($campaign_options, 'id')));
                    }
                    $employee['campaigns'] = $campaign_options;
                    $employee['selected_campaign_ids'] = $selected_campaign_ids;
                    $employee['own_preset'] = $own_preset;
                    $employee['preset'] = $own_preset;
                    if ($is_kol_position && $kol_quarter_view) {
                        $employee = $this->build_kol_quarter_employee_view($month, $employee, $campaign_map[$employee_id] ?? []);
                    } else {
                        $metric_period = $employee_period;
                        if ($is_kol_position && !empty($own_preset['date_from']) && !empty($own_preset['date_until'])) {
                            $metric_period = [
                                'start_date' => (string) $own_preset['date_from'],
                                'until_date' => (string) $own_preset['date_until'],
                            ];
                        }
                        $employee['metrics'] = $this->get_kpi_metrics_from_logs(
                            $metric_period['start_date'],
                            $metric_period['until_date'],
                            $employee_id,
                            $effective_campaign_ids,
                            $position_id,
                            $employee['kpi_rows'],
                            false,
                            $is_kol_position ? 'month' : $period_type
                        );
                    }
                }

                $saved_run = $saved_run_map[$employee_id] ?? ['run' => null, 'items' => []];
                if (!empty($employee['is_virtual_quarter'])) {
                    $saved_run = ['run' => null, 'items' => []];
                }
                $employee['saved_run'] = $saved_run['run'] ?? null;
                $employee['is_locked'] = (int) (($saved_run['run']['is_locked'] ?? 0) == 1 ? 1 : 0);
                $employee['kpi_rows'] = $this->apply_saved_capaian_to_rows(
                    $employee['kpi_rows'],
                    $employee['metrics'],
                    $saved_run['items'] ?? [],
                    $employee['is_locked'] === 1 && empty($employee['is_virtual_quarter'])
                );
                $employee['total_score'] = $this->calculate_total_score($employee['kpi_rows'], $employee['metrics']);

                if (!$is_kol_position || (float) $employee['total_score'] > 0) {
                    $filtered_employees[] = $employee;
                }
            }
            unset($employee);
            usort($filtered_employees, function ($a, $b) {
                $score_a = (float) ($a['total_score'] ?? 0);
                $score_b = (float) ($b['total_score'] ?? 0);
                if ($score_a === $score_b) {
                    return strcasecmp((string) ($a['full_name'] ?? ''), (string) ($b['full_name'] ?? ''));
                }
                return $score_b <=> $score_a;
            });
            $division['employees'] = $filtered_employees;
        }
        unset($division);

        $data = [];
        $data['template'] = $this->template;
        $data['title'] = 'KPI - ' . $this->template->title();
        $data['month'] = $month;
        $data['period_type'] = $period_type;
        $data['period_label'] = ($active_tab_key === 'kol_specialist' && $kol_quarter_view)
            ? 'Rata-rata ' . $this->format_period_label($this->normalize_quarter_period_key($month))
            : $this->format_period_label($month);
        $data['period_start_date'] = $period['start_date'];
        $data['period_until_date'] = $period['until_date'];
        $data['kol_quarter_view'] = $kol_quarter_view;
        $data['active_tab_key'] = $active_tab_key;
        $data['can_edit_kpi'] = $can_edit_kpi;
        $data['viewer_position_id'] = $viewer_position_id;
        $data['kpi_position_options'] = $this->get_kpi_position_options();
        $data['kol_specialist_position_ids'] = $this->get_kol_specialist_position_ids();
        $data['system_kpi_position_ids'] = array_values(array_map('intval', array_keys($this->get_default_kpi_position_config())));
        $data['assignable_kpi_employees'] = $this->get_assignable_kpi_employee_options();
        $data['dashboard_start_month'] = $dashboard_start_month;
        $data['dashboard_end_month'] = $dashboard_end_month;
        $data['dashboard_summary'] = $active_tab_key === 'dashboard'
            ? $this->build_dashboard_summary($dashboard_start_month, $dashboard_end_month, $period_type)
            : null;
        $data['division_groups'] = $division_groups;
        $default_position_id = 7;
        if (!empty($division_groups[$active_tab_key])) {
            $first_division = $division_groups[$active_tab_key];
            $default_position_id = (int) ($first_division['position_id'] ?? 7);
        } elseif (!empty($division_groups)) {
            $first_division = reset($division_groups);
            $default_position_id = (int) ($first_division['position_id'] ?? 7);
        }
        $data['default_template_position_id'] = $default_position_id;
        $data['preset_rows'] = $this->get_or_seed_template_rows($month, $default_position_id);
        $data['global_preset'] = $global_preset;

        $data['content'] = $this->load->view('kpi/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    private function build_dashboard_summary($start_month, $end_month, $period_type = 'quarter')
    {
        $start_month = $this->normalize_quarter_period_key($start_month);
        $end_month = $this->normalize_quarter_period_key($end_month, $start_month);
        $period_type = 'quarter';

        if ($this->diff_quarter_keys($start_month, $end_month) < 0) {
            $tmp = $start_month;
            $start_month = $end_month;
            $end_month = $tmp;
        }
        $month_keys = $this->get_quarter_keys_between($start_month, $end_month);

        $division_groups = $this->get_kpi_employees_by_division();
        $employee_map = [];
        foreach ($division_groups as $division) {
            $label = (string) ($division['label'] ?? '-');
            foreach (($division['employees'] ?? []) as $employee) {
                $employee_id = (int) ($employee['id'] ?? 0);
                if ($employee_id <= 0) {
                    continue;
                }
                $employee_map[$employee_id] = [
                    'employee_id' => $employee_id,
                    'full_name' => trim((string) ($employee['full_name'] ?? '-')),
                    'division_label' => $label,
                ];
            }
        }

        if (empty($employee_map)) {
            return [
                'month_keys' => $month_keys,
                'start_month' => $start_month,
                'end_month' => $end_month,
                'top_rankings' => [],
            ];
        }

        $employee_ids = array_map('intval', array_keys($employee_map));
        $run_rows = $this->mymodel->selectWithQuery("
            SELECT employee_id, month_key, total_score
            FROM kpi_runs
            WHERE month_key BETWEEN '" . $this->db->escape_str($start_month) . "' AND '" . $this->db->escape_str($end_month) . "'
              AND period_type = '" . $this->db->escape_str($period_type) . "'
              AND employee_id IN (" . implode(',', $employee_ids) . ")
        ");

        $score_map = [];
        foreach ($run_rows as $row) {
            $employee_id = (int) ($row['employee_id'] ?? 0);
            $month_key = (string) ($row['month_key'] ?? '');
            if ($employee_id <= 0 || $month_key === '') {
                continue;
            }
            if (!isset($score_map[$employee_id])) {
                $score_map[$employee_id] = [];
            }
            $score_map[$employee_id][$month_key] = (float) ($row['total_score'] ?? 0);
        }

        $rankings = [];
        foreach ($employee_map as $employee_id => $employee) {
            $period_total = 0;
            $monthly_scores = [];
            foreach ($month_keys as $month_key) {
                $score = (float) ($score_map[$employee_id][$month_key] ?? 0);
                $period_total += $score;
                $monthly_scores[] = [
                    'month_key' => $month_key,
                    'score' => $score,
                ];
            }

            if ($period_total <= 0) {
                continue;
            }

            $rankings[] = [
                'employee_id' => $employee_id,
                'full_name' => $employee['full_name'],
                'division_label' => $employee['division_label'],
                'period_total_score' => $period_total,
                'period_avg_score' => count($month_keys) > 0 ? ($period_total / count($month_keys)) : 0,
                'monthly_scores' => $monthly_scores,
            ];
        }

        usort($rankings, function ($a, $b) {
            $total_a = (float) ($a['period_total_score'] ?? 0);
            $total_b = (float) ($b['period_total_score'] ?? 0);
            if ($total_a === $total_b) {
                return strcasecmp((string) ($a['full_name'] ?? ''), (string) ($b['full_name'] ?? ''));
            }
            return $total_b <=> $total_a;
        });

        return [
            'month_keys' => $month_keys,
            'start_month' => $start_month,
            'end_month' => $end_month,
            'top_rankings' => array_slice($rankings, 0, 3),
        ];
    }

    private function get_month_keys_between($start_month, $end_month)
    {
        $start_month = trim((string) $start_month);
        $end_month = trim((string) $end_month);
        if (!preg_match('/^\d{4}-\d{2}$/', $start_month)) {
            $start_month = date('Y-m');
        }
        if (!preg_match('/^\d{4}-\d{2}$/', $end_month)) {
            $end_month = $start_month;
        }
        if ($this->diff_month_keys($start_month, $end_month) < 0) {
            $tmp = $start_month;
            $start_month = $end_month;
            $end_month = $tmp;
        }

        $keys = [];
        $diff = $this->diff_month_keys($start_month, $end_month);
        for ($offset = 0; $offset <= $diff; $offset++) {
            $keys[] = $this->add_months_to_key($start_month, $offset);
        }
        return $keys;
    }

    public function update_row()
    {
        $this->ensure_kpi_table();
        $this->ensure_kpi_run_tables();

        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $id = (int) ($this->input->post('id', true) ?? 0);
        $field = trim((string) ($this->input->post('field', true) ?? ''));
        $value = (string) ($this->input->post('value', true) ?? '');

        $allowed_fields = ['indikator', 'target', 'bobot', 'durasi', 'metric_key', 'manual_capaian', 'capaian'];
        if ($id <= 0 || !in_array($field, $allowed_fields, true)) {
            return $this->json_response(false, 'Data tidak valid.');
        }

        $row = $this->mymodel->selectWithQuery("
            SELECT id, month_key, COALESCE(period_type, 'month') AS period_type, template_month_key, employee_id, indikator, target, bobot, metric_key, manual_capaian, durasi, override_capaian, sort_order
            FROM kpi_targets
            WHERE id = '" . (int) $id . "'
            LIMIT 1
        ");
        if (empty($row)) {
            return $this->json_response(false, 'Baris KPI tidak ditemukan.');
        }
        $row = $row[0];

        if ($field === 'target' || $field === 'bobot' || $field === 'manual_capaian' || $field === 'capaian') {
            $clean_numeric = preg_replace('/[^0-9.,-]/', '', $value);
            $clean_numeric = str_replace('.', '', $clean_numeric);
            $clean_numeric = str_replace(',', '.', $clean_numeric);
            $value = is_numeric($clean_numeric) ? (string) ((float) $clean_numeric) : '0';
        } elseif ($field === 'metric_key') {
            $value = $this->sanitize_metric_key($value);
        } else {
            $value = trim($value);
        }

        $update_data = [
            'updated_at' => date('Y-m-d H:i:s')
        ];
        if ($field === 'capaian') {
            $update_data['override_capaian'] = $value;
        } else {
            $update_data[$field] = $value;
        }

        $target_row_id = $id;
        $position_id = $this->get_employee_kpi_position_id((int) ($row['employee_id'] ?? 0));
        $row_period_type = (string) ($row['period_type'] ?? 'month');
        if (
            $position_id === 28
            && $field === 'capaian'
            && $row_period_type === 'month'
            && !empty($row['template_month_key'])
            && (string) ($row['template_month_key'] ?? '') !== (string) ($row['month_key'] ?? '')
        ) {
            $source_month = $this->db->escape_str((string) $row['template_month_key']);
            $source_row = $this->mymodel->selectWithQuery("
                SELECT id
                FROM kpi_targets
                WHERE month_key = '$source_month'
                    AND period_type = 'month'
                    AND employee_id = '" . (int) ($row['employee_id'] ?? 0) . "'
                    AND sort_order = '" . (int) ($row['sort_order'] ?? 0) . "'
                LIMIT 1
            ");
            if (!empty($source_row[0]['id'])) {
                $target_row_id = (int) $source_row[0]['id'];
            }
        }

        $this->db->where('id', $target_row_id);
        $updated = $this->db->update('kpi_targets', $update_data);

        if (!$updated) {
            return $this->json_response(false, 'Gagal menyimpan perubahan.');
        }

        if ($field === 'capaian') {
            $campaign_ids = $this->input->post('campaign_ids');
            if (!is_array($campaign_ids)) {
                $campaign_ids = $campaign_ids ? explode(',', (string) $campaign_ids) : [];
            }
            $campaign_ids = array_values(array_filter(array_map('intval', $campaign_ids), function ($item_id) {
                return $item_id > 0;
            }));

            $locked = $this->lock_employee_kpi_snapshot(
                (string) ($row['month_key'] ?? $this->current_quarter_key()),
                (int) ($row['employee_id'] ?? 0),
                $campaign_ids
            );

            if (!$locked) {
                return $this->json_response(false, 'Override tersimpan, tetapi auto-lock gagal.');
            }

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Override capaian tersimpan dan KPI otomatis di-lock.',
                    'value' => (float) $value,
                    'locked' => true,
                ]));
        }

        return $this->json_response(true, 'Perubahan tersimpan.');
    }

    public function campaign_options()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $employee_id = (int) ($this->input->get('employee_id', true) ?? 0);
        $month = $this->normalize_quarter_period_key($this->input->get('month', true));
        $period = $this->get_period_range($month);

        if ($employee_id <= 0) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'data' => [],
                ]));
        }

        $campaigns = $this->get_campaign_options_by_employee(
            $employee_id,
            $period['start_date'],
            $period['until_date'],
            $this->get_employee_kpi_position_id($employee_id)
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'data' => $campaigns,
            ]));
    }

    public function template_rows()
    {
        $this->ensure_kpi_template_table();
        $this->ensure_kpi_table();
        $this->ensure_kpi_employee_presets_table();
        $this->ensure_kpi_run_tables();
        $this->ensure_kpi_period_type_columns();

        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $position_id = (int) ($this->input->get('position_id', true) ?? 0);
        if (!$this->is_valid_kpi_position_id($position_id)) {
            return $this->json_response(false, 'Posisi template tidak valid.');
        }
        $month = $this->normalize_period_key_for_position($this->input->get('month', true), $position_id);
        $employee_id = (int) ($this->input->get('employee_id', true) ?? 0);

        $rows = $this->get_or_seed_template_rows($month, $position_id, false);
        if ($employee_id > 0) {
            $employee_position_id = $this->get_employee_kpi_position_id($employee_id);
            if ($employee_position_id !== $position_id) {
                return $this->json_response(false, 'Karyawan tidak sesuai dengan posisi template.');
            }
            $rows = $this->get_or_seed_kpi_rows($month, $employee_id);
        }
        $employees = $this->get_template_position_employees($month, $position_id);
        $bulk_campaign_options = $this->get_bulk_campaign_options_for_position($month, $position_id, array_values(array_filter(array_map(function ($employee) {
            return (int) ($employee['id'] ?? 0);
        }, $employees))));
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'data' => $rows,
                'employees' => $employees,
                'campaign_options' => $bulk_campaign_options,
                'employee_id' => $employee_id,
                'custom_position' => $this->is_custom_kpi_position_id($position_id)
                    ? array_merge(
                        $this->get_custom_kpi_position_detail($position_id) ?? [],
                        ['member_employee_ids' => $this->get_custom_kpi_position_member_ids($position_id)]
                    )
                    : null,
            ]));
    }

    private function get_bulk_campaign_options_for_position($month, $position_id, $employee_ids = [])
    {
        $position_id = (int) $position_id;
        if ($position_id === 7) {
            return $this->get_global_campaign_options_for_cc($month);
        }
        if (!$this->is_kol_specialist_position_id($position_id)) {
            return [];
        }

        $employee_ids = array_values(array_filter(array_map('intval', (array) $employee_ids), function ($id) {
            return $id > 0;
        }));
        if (empty($employee_ids)) {
            return [];
        }
        $period = $this->get_period_range($month);
        return $this->mymodel->selectWithQuery("
            SELECT DISTINCT ec.id, ec.title
            FROM endorse e
            INNER JOIN endorse_campaign ec ON ec.id = e.id_campaign
            WHERE e.pic_user_id IN (" . implode(',', $employee_ids) . ")
                AND e.status = 'Aktif'
                AND e.status_endorse = 'Posted Content'
                AND ec.status = 'Aktif'
                AND COALESCE(ec.is_internal, 0) = 0
                AND DATE(COALESCE(ec.start_at, '1900-01-01')) <= '" . $this->db->escape_str($period['until_date']) . "'
                AND DATE(COALESCE(ec.until_at, '2999-12-31')) >= '" . $this->db->escape_str($period['start_date']) . "'
            ORDER BY ec.title ASC
        ");
    }

    public function save_template()
    {
        $this->ensure_kpi_table();
        $this->ensure_kpi_template_table();
        $this->ensure_kpi_period_type_columns();

        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $position_token = trim((string) ($this->input->post('position_id', true) ?? ''));
        $is_new_custom_position = $position_token === '__custom_new__';
        $position_id = $is_new_custom_position ? 0 : (int) $position_token;
        $custom_position_name = trim((string) ($this->input->post('custom_position_name', true) ?? ''));
        $member_employee_ids = $this->input->post('member_employee_ids');
        if (!is_array($member_employee_ids)) {
            $member_employee_ids = $member_employee_ids ? explode(',', (string) $member_employee_ids) : [];
        }
        $member_employee_ids = array_values(array_unique(array_filter(array_map('intval', $member_employee_ids), function ($employee_id) {
            return $employee_id > 0;
        })));

        if (!$is_new_custom_position && !$this->is_valid_kpi_position_id($position_id)) {
            return $this->json_response(false, 'Posisi template tidak valid.');
        }
        if (($is_new_custom_position || $this->is_custom_kpi_position_id($position_id)) && $custom_position_name === '') {
            return $this->json_response(false, 'Nama posisi custom wajib diisi.');
        }
        if (($is_new_custom_position || $this->is_custom_kpi_position_id($position_id)) && empty($member_employee_ids)) {
            return $this->json_response(false, 'Pilih minimal 1 karyawan untuk posisi custom.');
        }
        if (($is_new_custom_position || $this->is_custom_kpi_position_id($position_id)) && !$this->has_kpi_custom_position_tables()) {
            return $this->json_response(false, 'Tabel custom KPI belum tersedia. Jalankan query SQL manual dulu.');
        }

        $month = $is_new_custom_position
            ? $this->normalize_quarter_period_key($this->input->post('month', true), '')
            : $this->normalize_period_key_for_position($this->input->post('month', true), $position_id, '');
        if ($month === '') {
            return $this->json_response(false, ($this->is_kol_specialist_position_id($position_id) && !$is_new_custom_position) ? 'Bulan template tidak valid.' : 'Quarter template tidak valid.');
        }

        $rows = $this->input->post('rows');
        if (!is_array($rows)) {
            return $this->json_response(false, 'Format data template tidak valid.');
        }

        $period_type = ($this->is_kol_specialist_position_id($position_id) && !$is_new_custom_position) ? 'month' : 'quarter';
        $sanitized_rows = $this->sanitize_template_rows_payload($month, $position_id, $period_type, $rows);

        if (empty($sanitized_rows)) {
            return $this->json_response(false, 'Minimal 1 baris indikator wajib diisi.');
        }

        $is_custom_position = $is_new_custom_position || $this->is_custom_kpi_position_id($position_id);
        $previous_member_ids = $is_custom_position ? $this->get_custom_kpi_position_member_ids($position_id) : [];

        $this->db->trans_start();
        if ($is_new_custom_position) {
            $position_id = $this->create_custom_kpi_position($custom_position_name);
            $this->kpi_position_options_cache = null;
            if ($position_id <= 0) {
                $this->db->trans_complete();
                return $this->json_response(false, 'Gagal membuat posisi custom.');
            }
        } elseif ($this->is_custom_kpi_position_id($position_id)) {
            $custom_position_id = $this->decode_custom_kpi_position_id($position_id);
            $this->db->where('id', $custom_position_id)->update('kpi_custom_positions', [
                'name' => $custom_position_name,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $this->kpi_position_options_cache = null;
        }

        if ($is_custom_position) {
            foreach ($sanitized_rows as &$sanitized_row) {
                $sanitized_row['position_id'] = $position_id;
            }
            unset($sanitized_row);
            $this->save_custom_kpi_position_members($position_id, $member_employee_ids);
        }

        $this->db->delete('kpi_templates', ['month_key' => $month, 'period_type' => $period_type, 'position_id' => $position_id]);
        foreach ($sanitized_rows as $row) {
            $this->db->insert('kpi_templates', $row);
        }
        $employee_ids = $this->get_employee_ids_by_position($position_id);
        if ($is_custom_position) {
            $affected_employee_ids = array_values(array_unique(array_merge($previous_member_ids, $employee_ids)));
            $this->sync_custom_position_employee_targets($position_id, $affected_employee_ids);
        } elseif (!empty($employee_ids)) {
            $this->db->where('month_key', $month);
            $this->db->where('period_type', $period_type);
            $this->db->where_in('employee_id', $employee_ids);
            $this->db->delete('kpi_targets');

            foreach ($employee_ids as $employee_id) {
                $sort_order = 1;
                foreach ($sanitized_rows as $template_row) {
                    $this->db->insert('kpi_targets', [
                        'month_key' => $month,
                        'period_type' => $period_type,
                        'template_month_key' => $month,
                        'employee_id' => $employee_id,
                        'indikator' => $template_row['indikator'],
                        'target' => $template_row['target'],
                        'bobot' => $template_row['bobot'],
                        'metric_key' => $template_row['metric_key'],
                        'manual_capaian' => 0,
                        'durasi' => $template_row['dimensi'],
                        'sort_order' => $sort_order++,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return $this->json_response(false, 'Gagal menyimpan template indikator.');
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => $is_custom_position
                    ? 'Template posisi custom berhasil disimpan.'
                    : 'Template indikator berhasil disimpan.',
                'position_id' => $position_id,
                'tab_key' => $this->build_kpi_division_key($position_id),
            ]));
    }

    public function save_selected_template()
    {
        $this->ensure_kpi_table();
        $this->ensure_kpi_run_tables();
        $this->ensure_kpi_period_type_columns();

        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $position_id = (int) ($this->input->post('position_id', true) ?? 0);
        if (!$this->is_valid_kpi_position_id($position_id)) {
            return $this->json_response(false, 'Posisi template tidak valid.');
        }
        $month = $this->normalize_period_key_for_position($this->input->post('month', true), $position_id, '');
        if ($month === '') {
            return $this->json_response(false, $this->is_kol_specialist_position_id($position_id) ? 'Bulan template tidak valid.' : 'Quarter template tidak valid.');
        }

        $rows = $this->input->post('rows');
        if (!is_array($rows)) {
            return $this->json_response(false, 'Format data template tidak valid.');
        }

        $employee_ids = $this->input->post('employee_ids');
        if (!is_array($employee_ids)) {
            $employee_ids = $employee_ids ? explode(',', (string) $employee_ids) : [];
        }
        $employee_ids = array_values(array_filter(array_map('intval', $employee_ids), function ($id) {
            return $id > 0;
        }));
        if (empty($employee_ids)) {
            return $this->json_response(false, 'Pilih minimal 1 karyawan.');
        }

        $period_type = $this->is_kol_specialist_position_id($position_id) ? 'month' : 'quarter';
        $sanitized_rows = $this->sanitize_template_rows_payload($month, $position_id, $period_type, $rows);
        if (empty($sanitized_rows)) {
            return $this->json_response(false, 'Minimal 1 baris indikator wajib diisi.');
        }

        $employee_meta = $this->get_template_position_employees_map($month, $employee_ids);
        $eligible_employee_ids = [];
        foreach ($employee_ids as $employee_id) {
            $employee_position_id = (int) ($employee_meta[$employee_id]['position_id'] ?? 0);
            if ($employee_position_id === $position_id) {
                $eligible_employee_ids[] = $employee_id;
            }
        }
        if (empty($eligible_employee_ids)) {
            return $this->json_response(false, 'Karyawan terpilih tidak sesuai dengan posisi template.');
        }

        $locked_rows = $this->mymodel->selectWithQuery("
            SELECT employee_id
            FROM kpi_runs
            WHERE month_key = '" . $this->db->escape_str($month) . "'
                AND period_type = '" . $this->db->escape_str($period_type) . "'
                AND is_locked = 1
                AND employee_id IN (" . implode(',', $eligible_employee_ids) . ")
        ");
        $locked_map = [];
        foreach ($locked_rows as $row) {
            $locked_map[(int) ($row['employee_id'] ?? 0)] = true;
        }

        $target_employee_ids = [];
        foreach ($eligible_employee_ids as $employee_id) {
            if (empty($locked_map[$employee_id])) {
                $target_employee_ids[] = $employee_id;
            }
        }
        if (empty($target_employee_ids)) {
            return $this->json_response(false, 'Semua karyawan terpilih sedang LOCK. Unlock dulu untuk apply template per karyawan.');
        }

        $this->db->trans_start();
        $this->db->where('month_key', $month);
        $this->db->where('period_type', $period_type);
        $this->db->where_in('employee_id', $target_employee_ids);
        $this->db->delete('kpi_targets');

        foreach ($target_employee_ids as $employee_id) {
            $sort_order = 1;
            foreach ($sanitized_rows as $template_row) {
                $this->db->insert('kpi_targets', [
                    'month_key' => $month,
                    'period_type' => $period_type,
                    'template_month_key' => $month,
                    'employee_id' => $employee_id,
                    'indikator' => $template_row['indikator'],
                    'target' => $template_row['target'],
                    'bobot' => $template_row['bobot'],
                    'metric_key' => $template_row['metric_key'],
                    'manual_capaian' => 0,
                    'durasi' => $template_row['dimensi'],
                    'sort_order' => $sort_order++,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return $this->json_response(false, 'Gagal apply template ke karyawan terpilih.');
        }

        $applied_count = count($target_employee_ids);
        $locked_count = count($eligible_employee_ids) - $applied_count;
        $message = 'Template berhasil di-apply ke ' . $applied_count . ' karyawan.';
        if ($locked_count > 0) {
            $message .= ' ' . $locked_count . ' karyawan dilewati karena status LOCK.';
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => $message,
                'applied_count' => $applied_count,
                'locked_count' => $locked_count,
            ]));
    }

    private function sanitize_template_rows_payload($month, $position_id, $period_type, $rows)
    {
        $month = (string) $month;
        $position_id = (int) $position_id;
        $period_type = (string) $period_type;
        $rows = is_array($rows) ? $rows : [];

        $sanitized_rows = [];
        $sort_order = 1;
        foreach ($rows as $row) {
            $indikator = trim((string) ($row['indikator'] ?? ''));
            if ($indikator === '') {
                continue;
            }

            $sanitized_rows[] = [
                'month_key' => $month,
                'period_type' => $period_type,
                'position_id' => $position_id,
                'indikator' => $indikator,
                'target' => $this->normalize_number($row['target'] ?? 0),
                'bobot' => $this->normalize_number($row['bobot'] ?? 0),
                'metric_key' => $this->sanitize_metric_key($row['metric_key'] ?? ''),
                'dimensi' => $this->normalize_duration_label($row['dimensi'] ?? '1 Bulan'),
                'keterangan' => trim((string) ($row['keterangan'] ?? '')),
                'sort_order' => $sort_order++,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        return $sanitized_rows;
    }

    public function delete_position_template()
    {
        $this->ensure_kpi_template_table();
        $this->ensure_kpi_period_type_columns();

        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $position_id = (int) ($this->input->post('position_id', true) ?? 0);
        if (!$this->is_valid_kpi_position_id($position_id)) {
            return $this->json_response(false, 'Posisi KPI tidak valid.');
        }
        if ($this->is_system_kpi_position($position_id)) {
            return $this->json_response(false, 'Posisi bawaan KPI tidak bisa dihapus.');
        }

        $existing = $this->mymodel->selectWithQuery("
            SELECT id
            FROM kpi_templates
            WHERE position_id = '" . (int) $position_id . "'
            LIMIT 1
        ");
        if (empty($existing)) {
            return $this->json_response(false, 'Belum ada template KPI untuk posisi ini.');
        }

        $affected_employee_ids = $this->is_custom_kpi_position_id($position_id)
            ? $this->get_custom_kpi_position_member_ids($position_id)
            : [];

        $this->db->trans_start();
        if ($this->is_custom_kpi_position_id($position_id)) {
            $custom_position_id = $this->decode_custom_kpi_position_id($position_id);
            $this->db->delete('kpi_custom_position_members', ['custom_position_id' => $custom_position_id]);
            $this->sync_custom_position_employee_targets($position_id, $affected_employee_ids);
            $this->db->delete('kpi_custom_positions', ['id' => $custom_position_id]);
            $this->kpi_position_options_cache = null;
        }
        $this->db->delete('kpi_templates', ['position_id' => $position_id]);
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return $this->json_response(false, 'Gagal menghapus posisi KPI.');
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Posisi KPI berhasil dihapus dari tab KPI.',
                'position_id' => $position_id,
            ]));
    }

    public function save_run()
    {
        $this->ensure_kpi_run_tables();

        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $employee_id = (int) ($this->input->post('employee_id', true) ?? 0);
        if ($employee_id <= 0) {
            return $this->json_response(false, 'Karyawan tidak valid.');
        }
        $position_id = $this->get_employee_kpi_position_id($employee_id);
        $is_kol_position = $this->is_kol_specialist_position_id($position_id);
        $month = $this->normalize_period_key_for_position($this->input->post('month', true), $position_id, '');
        if ($month === '') {
            return $this->json_response(false, $is_kol_position ? 'Bulan KPI tidak valid.' : 'Quarter KPI tidak valid.');
        }

        $rows = $this->input->post('rows');
        if (!is_array($rows) || empty($rows)) {
            return $this->json_response(false, 'Data KPI kosong.');
        }

        $campaign_ids = $this->input->post('campaign_ids');
        if (!is_array($campaign_ids)) {
            $campaign_ids = $campaign_ids ? explode(',', (string) $campaign_ids) : [];
        }
        $campaign_ids = array_values(array_filter(array_map('intval', $campaign_ids), function ($id) {
            return $id > 0;
        }));

        $period_type = $is_kol_position ? 'month' : 'quarter';
        $context = $this->resolve_employee_run_context($month, $employee_id, $position_id, $campaign_ids);
        $period = $context['period'];
        $campaign_ids = $context['campaign_ids'];
        $use_raw_window = $context['use_raw_window'];

        if ($position_id !== 7 && empty($campaign_ids)) {
            $campaign_options = $this->get_campaign_options_by_employee($employee_id, $period['start_date'], $period['until_date'], $position_id);
            $campaign_ids = array_values(array_map('intval', array_column($campaign_options, 'id')));
        }

        $metrics = $this->get_kpi_metrics_from_logs(
            $period['start_date'],
            $period['until_date'],
            $employee_id,
            $campaign_ids,
            $position_id,
            $rows,
            $use_raw_window,
            $period_type
        );
        $campaign_details = $metrics['grouped_campaign'] ?? [];

        $prepared_rows = [];
        $total_score = 0;
        $sort_order = 1;
        foreach ($rows as $row) {
            $indikator = trim((string) ($row['indikator'] ?? ''));
            if ($indikator === '') {
                continue;
            }

            $target = $this->normalize_number($row['target'] ?? 0);
            $bobot = $this->normalize_number($row['bobot'] ?? 0);
            $capaian = $this->normalize_number($row['capaian'] ?? 0);
            $durasi = $this->normalize_duration_label($row['durasi'] ?? '1 Bulan');
            $kpi_target_id = (int) ($row['kpi_target_id'] ?? 0);
            $metric_key = $this->sanitize_metric_key($row['metric_key'] ?? '');
            $score = $target > 0 ? ($capaian / $target) * $bobot : 0;
            $total_score += $score;

            $prepared_rows[] = [
                'kpi_target_id' => $kpi_target_id > 0 ? $kpi_target_id : null,
                'indikator' => $indikator,
                'target' => $target,
                'bobot' => $bobot,
                'metric_key' => $metric_key,
                'dimensi' => $durasi,
                'capaian' => $capaian,
                'score' => $score,
                'sort_order' => $sort_order++,
            ];
        }

        if (empty($prepared_rows)) {
            return $this->json_response(false, 'Minimal 1 baris KPI wajib diisi.');
        }

        $existing_run = $this->mymodel->selectWithQuery("
            SELECT id
            FROM kpi_runs
            WHERE month_key = '" . $this->db->escape_str($month) . "'
                AND period_type = '" . $this->db->escape_str($period_type) . "'
                AND employee_id = '" . (int) $employee_id . "'
            LIMIT 1
        ");

        if (!empty($existing_run)) {
            $locked_check = $this->mymodel->selectWithQuery("
                SELECT is_locked
                FROM kpi_runs
                WHERE id = '" . (int) $existing_run[0]['id'] . "'
                LIMIT 1
            ");
            if (!empty($locked_check) && (int) ($locked_check[0]['is_locked'] ?? 0) === 1) {
                return $this->json_response(false, 'KPI sudah di-lock. Unlock dulu untuk simpan ulang.');
            }
        }

        $now = date('Y-m-d H:i:s');
        $window = $this->get_campaign_period_window(
            $period['start_date'],
            $period['until_date'],
            $use_raw_window ? false : $this->should_include_previous_month($position_id)
        );
        $run_data = [
            'month_key' => $month,
            'period_type' => $period_type,
            'employee_id' => $employee_id,
            'period_start' => $period['start_date'],
            'period_end' => $period['until_date'],
            'campaign_window_start' => $window['start'],
            'campaign_window_end' => $window['end'],
            'total_views' => (float) ($metrics['total_views'] ?? 0),
            'total_fyp' => (int) ($metrics['total_fyp'] ?? 0),
            'total_score' => $total_score,
            'campaign_count' => count($campaign_details),
            'generated_at' => $now,
            'generated_by' => (int) ($_SESSION['user']['id'] ?? 0),
            'updated_at' => $now,
        ];

        $this->db->trans_start();

        if (!empty($existing_run)) {
            $run_id = (int) $existing_run[0]['id'];
            $this->db->where('id', $run_id)->update('kpi_runs', $run_data);
            $this->db->delete('kpi_run_items', ['kpi_run_id' => $run_id]);
            $this->db->delete('kpi_run_campaigns', ['kpi_run_id' => $run_id]);
        } else {
            $run_data['is_locked'] = 0;
            $this->db->insert('kpi_runs', $run_data);
            $run_id = (int) $this->db->insert_id();
        }

        foreach ($prepared_rows as $item) {
            $this->db->insert('kpi_run_items', [
                'kpi_run_id' => $run_id,
                'kpi_target_id' => $item['kpi_target_id'],
                'indikator' => $item['indikator'],
                'target' => $item['target'],
                'bobot' => $item['bobot'],
                'metric_key' => $item['metric_key'],
                'dimensi' => $item['dimensi'],
                'capaian' => $item['capaian'],
                'score' => $item['score'],
                'sort_order' => $item['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($campaign_details as $campaign) {
            $this->db->insert('kpi_run_campaigns', [
                'kpi_run_id' => $run_id,
                'campaign_id' => (int) ($campaign['id_campaign'] ?? 0),
                'campaign_title' => (string) ($campaign['campaign_title'] ?? ''),
                'total_konten' => (int) ($campaign['total_konten'] ?? 0),
                'total_fyp' => (int) ($campaign['total_fyp'] ?? 0),
                'total_views' => (float) ($campaign['total_views'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return $this->json_response(false, 'Gagal menyimpan KPI run.');
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'KPI berhasil disimpan.',
                'data' => [
                    'generated_at' => $now,
                    'is_locked' => 0,
                ],
            ]));
    }

    public function toggle_run_lock()
    {
        $this->ensure_kpi_run_tables();

        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $employee_id = (int) ($this->input->post('employee_id', true) ?? 0);
        $action = strtolower(trim((string) ($this->input->post('action', true) ?? '')));
        if (!in_array($action, ['lock', 'unlock'], true)) {
            return $this->json_response(false, 'Aksi lock tidak valid.');
        }
        if ($employee_id <= 0) {
            return $this->json_response(false, 'Data lock tidak valid.');
        }
        $position_id = $this->get_employee_kpi_position_id($employee_id);
        $is_kol_position = $this->is_kol_specialist_position_id($position_id);
        $month = $this->normalize_period_key_for_position($this->input->post('month', true), $position_id, '');
        if ($month === '') {
            return $this->json_response(false, 'Data lock tidak valid.');
        }

        $period_type = $is_kol_position ? 'month' : 'quarter';
        $run = $this->mymodel->selectWithQuery("
            SELECT id, is_locked
            FROM kpi_runs
            WHERE month_key = '" . $this->db->escape_str($month) . "'
                AND period_type = '" . $this->db->escape_str($period_type) . "'
                AND employee_id = '" . (int) $employee_id . "'
            LIMIT 1
        ");

        $is_locking = $action === 'lock';
        if ($is_locking) {
            $campaign_ids = $this->input->post('campaign_ids');
            if (!is_array($campaign_ids)) {
                $campaign_ids = $campaign_ids ? explode(',', (string) $campaign_ids) : [];
            }
            $campaign_ids = array_values(array_filter(array_map('intval', $campaign_ids), function ($id) {
                return $id > 0;
            }));
            $context = $this->resolve_employee_run_context($month, $employee_id, $position_id, $campaign_ids);
            $period = $context['period'];
            $campaign_ids = $context['campaign_ids'];
            $use_raw_window = $context['use_raw_window'];
            if ($position_id !== 7 && empty($campaign_ids)) {
                $campaign_options = $this->get_campaign_options_by_employee($employee_id, $period['start_date'], $period['until_date'], $position_id);
                $campaign_ids = array_values(array_map('intval', array_column($campaign_options, 'id')));
            }
            $rows = $this->get_or_seed_kpi_rows($month, $employee_id);
            $metrics = $this->get_kpi_metrics_from_logs(
                $period['start_date'],
                $period['until_date'],
                $employee_id,
                $campaign_ids,
                $position_id,
                $rows,
                $use_raw_window,
                $period_type
            );
            $rows = $this->apply_saved_capaian_to_rows($rows, $metrics, [], false);
            $prepared_rows = [];
            $sort_order = 1;
            foreach ($rows as $row) {
                $target = (float) ($row['target'] ?? 0);
                $bobot = (float) ($row['bobot'] ?? 0);
                $metric_key = $this->sanitize_metric_key($row['metric_key'] ?? '');
                $capaian = (float) ($row['capaian'] ?? 0);
                $score = $target > 0 ? ($capaian / $target) * $bobot : 0;
                $prepared_rows[] = [
                    'kpi_target_id' => (int) ($row['id'] ?? 0),
                    'indikator' => (string) ($row['indikator'] ?? ''),
                    'target' => $target,
                    'bobot' => $bobot,
                    'metric_key' => $metric_key,
                    'dimensi' => (string) ($row['durasi'] ?? '1 Bulan'),
                    'capaian' => $capaian,
                    'score' => $score,
                    'sort_order' => $sort_order++,
                ];
            }
            $this->persist_run_snapshot(
                $month,
                $employee_id,
                $period,
                $metrics,
                $prepared_rows,
                $metrics['grouped_campaign'] ?? [],
                $position_id,
                $use_raw_window
            );
            $run = $this->mymodel->selectWithQuery("
                SELECT id, is_locked
                FROM kpi_runs
                WHERE month_key = '" . $this->db->escape_str($month) . "'
                    AND period_type = '" . $this->db->escape_str($period_type) . "'
                    AND employee_id = '" . (int) $employee_id . "'
                LIMIT 1
            ");
        } elseif (empty($run)) {
            return $this->json_response(false, 'Tidak ada data lock untuk dibuka.');
        }

        $update = [
            'is_locked' => $is_locking ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
            'locked_at' => $is_locking ? date('Y-m-d H:i:s') : null,
            'locked_by' => $is_locking ? (int) ($_SESSION['user']['id'] ?? 0) : null,
        ];

        $this->db->where('id', (int) $run[0]['id']);
        $ok = $this->db->update('kpi_runs', $update);
        if (!$ok) {
            return $this->json_response(false, 'Gagal mengubah status lock.');
        }

        return $this->json_response(true, $is_locking ? 'KPI berhasil di-lock.' : 'KPI berhasil di-unlock.');
    }

    public function save_preset()
    {
        $this->ensure_kpi_employee_presets_table();
        $this->ensure_kpi_run_tables();

        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $employee_id = (int) ($this->input->post('employee_id', true) ?? 0);
        if ($employee_id <= 0) {
            return $this->json_response(false, 'Karyawan tidak valid.');
        }

        $position_id = $this->get_employee_kpi_position_id($employee_id);
        $is_kol_position = $this->is_kol_specialist_position_id($position_id);
        if (!$this->is_source_kpi_position_id($position_id)) {
            return $this->json_response(false, 'Preset hanya tersedia untuk Content Creator dan KOL Specialist.');
        }

        $month = $this->normalize_period_key_for_position($this->input->post('month', true), $position_id, '');
        if ($month === '') {
            return $this->json_response(false, $is_kol_position ? 'Bulan tidak valid.' : 'Quarter tidak valid.');
        }

        $period_type = $is_kol_position ? 'month' : 'quarter';

        $campaign_ids = $this->input->post('campaign_ids');
        if (!is_array($campaign_ids)) {
            $campaign_ids = $campaign_ids ? explode(',', (string) $campaign_ids) : [];
        }
        $campaign_ids = array_values(array_filter(array_map('intval', $campaign_ids), function ($id) {
            return $id > 0;
        }));
        $source_rules = $this->normalize_source_rules($this->input->post('source_rules'));
        if ($is_kol_position && !empty($source_rules)) {
            $campaign_ids = array_values(array_unique(array_map('intval', array_column($source_rules, 'campaign_id'))));
        }

        $period_range = $this->get_period_range($month);
        $date_from = trim((string) ($this->input->post('date_from', true) ?? ''));
        $date_until = trim((string) ($this->input->post('date_until', true) ?? ''));
        $date_from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) ? $date_from : null;
        $date_until = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_until) ? $date_until : null;
        $growth_date_from = trim((string) ($this->input->post('growth_date_from', true) ?? ''));
        $growth_date_until = trim((string) ($this->input->post('growth_date_until', true) ?? ''));
        $growth_date_from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $growth_date_from) ? $growth_date_from : null;
        $growth_date_until = preg_match('/^\d{4}-\d{2}-\d{2}$/', $growth_date_until) ? $growth_date_until : null;
        if (($date_from && !$date_until) || (!$date_from && $date_until)) {
            return $this->json_response(false, 'Tanggal mulai dan selesai sumber data harus diisi keduanya.');
        }
        if ((($growth_date_from && !$growth_date_until) || (!$growth_date_from && $growth_date_until)) && $is_kol_position) {
            return $this->json_response(false, 'Tanggal mulai dan selesai kenaikan content harus diisi keduanya.');
        }
        if ($date_from && $date_until && strtotime($date_from) > strtotime($date_until)) {
            return $this->json_response(false, 'Tanggal mulai sumber data harus lebih kecil atau sama dengan tanggal selesai.');
        }
        if ($growth_date_from && $growth_date_until && strtotime($growth_date_from) > strtotime($growth_date_until)) {
            return $this->json_response(false, 'Tanggal mulai kenaikan content harus lebih kecil atau sama dengan tanggal selesai.');
        }
        if ($date_from && $date_until) {
            $period_start_ts = strtotime($period_range['start_date']);
            if (strtotime($date_from) < $period_start_ts) {
                return $this->json_response(false, 'Tanggal mulai sumber data tidak boleh sebelum awal periode ' . $this->format_period_label($month) . '.');
            }
        }

        $locked = $this->mymodel->selectWithQuery("
            SELECT is_locked
            FROM kpi_runs
            WHERE month_key = '" . $this->db->escape_str($month) . "'
                AND period_type = '" . $this->db->escape_str($period_type) . "'
                AND employee_id = '" . (int) $employee_id . "'
            LIMIT 1
        ");
        if (!empty($locked) && (int) ($locked[0]['is_locked'] ?? 0) === 1) {
            return $this->json_response(false, 'KPI sudah di-lock. Unlock dulu untuk ubah preset.');
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'month_key' => $month,
            'period_type' => $period_type,
            'employee_id' => $employee_id,
            'campaign_ids' => json_encode($campaign_ids),
            'source_rules_json' => $this->encode_source_config($source_rules, $growth_date_from, $growth_date_until),
            'date_from' => $date_from,
            'date_until' => $date_until,
            'updated_at' => $now,
        ];

        $existing = $this->mymodel->selectWithQuery("
            SELECT id
            FROM kpi_employee_presets
            WHERE month_key = '" . $this->db->escape_str($month) . "'
                AND period_type = '" . $this->db->escape_str($period_type) . "'
                AND employee_id = '" . (int) $employee_id . "'
            LIMIT 1
        ");
        if (!empty($existing)) {
            $this->db->where('id', (int) $existing[0]['id'])->update('kpi_employee_presets', $payload);
        } else {
            $payload['created_at'] = $now;
            $this->db->insert('kpi_employee_presets', $payload);
        }

        return $this->json_response(true, 'Preset tersimpan.');
    }

    public function save_bulk_preset()
    {
        $this->ensure_kpi_employee_presets_table();
        $this->ensure_kpi_run_tables();

        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $employee_ids = $this->input->post('employee_ids');
        if (!is_array($employee_ids)) {
            $employee_ids = $employee_ids ? explode(',', (string) $employee_ids) : [];
        }
        $employee_ids = array_values(array_filter(array_map('intval', $employee_ids), function ($id) {
            return $id > 0;
        }));
        if (empty($employee_ids)) {
            return $this->json_response(false, 'Pilih minimal 1 karyawan.');
        }

        $employee_meta = $this->get_template_position_employees_map($this->input->post('month', true), $employee_ids);
        $position_id = 0;
        foreach ($employee_ids as $employee_id) {
            $position_id = (int) ($employee_meta[$employee_id]['position_id'] ?? 0);
            if ($position_id > 0) {
                break;
            }
        }
        $is_kol_position = $this->is_kol_specialist_position_id($position_id);
        $month = $this->normalize_period_key_for_position($this->input->post('month', true), $position_id, '');
        if ($month === '') {
            return $this->json_response(false, $is_kol_position ? 'Bulan tidak valid.' : 'Quarter tidak valid.');
        }

        $campaign_ids = $this->input->post('campaign_ids');
        if (!is_array($campaign_ids)) {
            $campaign_ids = $campaign_ids ? explode(',', (string) $campaign_ids) : [];
        }
        $campaign_ids = array_values(array_filter(array_map('intval', $campaign_ids), function ($id) {
            return $id > 0;
        }));
        $source_rules = $this->normalize_source_rules($this->input->post('source_rules'));
        if ($is_kol_position && !empty($source_rules)) {
            $campaign_ids = array_values(array_unique(array_map('intval', array_column($source_rules, 'campaign_id'))));
        }

        $period_range = $this->get_period_range($month);
        $date_from = trim((string) ($this->input->post('date_from', true) ?? ''));
        $date_until = trim((string) ($this->input->post('date_until', true) ?? ''));
        $date_from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) ? $date_from : null;
        $date_until = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_until) ? $date_until : null;
        $growth_date_from = trim((string) ($this->input->post('growth_date_from', true) ?? ''));
        $growth_date_until = trim((string) ($this->input->post('growth_date_until', true) ?? ''));
        $growth_date_from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $growth_date_from) ? $growth_date_from : null;
        $growth_date_until = preg_match('/^\d{4}-\d{2}-\d{2}$/', $growth_date_until) ? $growth_date_until : null;
        if (($date_from && !$date_until) || (!$date_from && $date_until)) {
            return $this->json_response(false, 'Tanggal mulai dan selesai sumber data harus diisi keduanya.');
        }
        if ((($growth_date_from && !$growth_date_until) || (!$growth_date_from && $growth_date_until)) && $is_kol_position) {
            return $this->json_response(false, 'Tanggal mulai dan selesai kenaikan content harus diisi keduanya.');
        }
        if ($date_from && $date_until && strtotime($date_from) > strtotime($date_until)) {
            return $this->json_response(false, 'Tanggal mulai sumber data harus lebih kecil atau sama dengan tanggal selesai.');
        }
        if ($growth_date_from && $growth_date_until && strtotime($growth_date_from) > strtotime($growth_date_until)) {
            return $this->json_response(false, 'Tanggal mulai kenaikan content harus lebih kecil atau sama dengan tanggal selesai.');
        }
        if ($date_from && $date_until) {
            $period_start_ts = strtotime($period_range['start_date']);
            if (strtotime($date_from) < $period_start_ts) {
                return $this->json_response(false, 'Tanggal mulai sumber data tidak boleh sebelum awal periode ' . $this->format_period_label($month) . '.');
            }
        }

        $period_type = $is_kol_position ? 'month' : 'quarter';
        $locked_rows = $this->mymodel->selectWithQuery("
            SELECT employee_id
            FROM kpi_runs
            WHERE month_key = '" . $this->db->escape_str($month) . "'
                AND period_type = '" . $this->db->escape_str($period_type) . "'
                AND is_locked = 1
                AND employee_id IN (" . implode(',', $employee_ids) . ")
        ");
        $locked_map = [];
        foreach ($locked_rows as $row) {
            $locked_map[(int) ($row['employee_id'] ?? 0)] = true;
        }

        $now = date('Y-m-d H:i:s');
        $applied = 0;

        $this->db->trans_start();
        foreach ($employee_ids as $employee_id) {
            if (!empty($locked_map[$employee_id])) {
                continue;
            }
            $position_id = (int) ($employee_meta[$employee_id]['position_id'] ?? 0);
            if (!$this->is_source_kpi_position_id($position_id)) {
                continue;
            }

            $payload = [
                'month_key' => $month,
                'period_type' => $period_type,
                'employee_id' => $employee_id,
                'campaign_ids' => json_encode($campaign_ids),
                'source_rules_json' => $this->encode_source_config($source_rules, $growth_date_from, $growth_date_until),
                'date_from' => $date_from,
                'date_until' => $date_until,
                'updated_at' => $now,
            ];

            $existing = $this->mymodel->selectWithQuery("
                SELECT id
                FROM kpi_employee_presets
                WHERE month_key = '" . $this->db->escape_str($month) . "'
                    AND period_type = '" . $this->db->escape_str($period_type) . "'
                    AND employee_id = '" . (int) $employee_id . "'
                LIMIT 1
            ");
            if (!empty($existing)) {
                $this->db->where('id', (int) $existing[0]['id'])->update('kpi_employee_presets', $payload);
            } else {
                $payload['created_at'] = $now;
                $this->db->insert('kpi_employee_presets', $payload);
            }
            $applied++;
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return $this->json_response(false, 'Gagal apply sumber data bulk.');
        }

        if ($applied <= 0) {
            return $this->json_response(false, 'Tidak ada karyawan yang berhasil di-apply. Cek apakah KPI sedang lock atau posisi bukan Content Creator.');
        }

        return $this->json_response(true, 'Sumber data berhasil di-apply ke ' . $applied . ' karyawan.');
    }

    public function save_global_preset()
    {
        $this->ensure_kpi_employee_presets_table();

        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $month = $this->normalize_quarter_period_key($this->input->post('month', true), '');
        if ($month === '') {
            return $this->json_response(false, 'Quarter tidak valid.');
        }

        $period_type = 'quarter';

        $campaign_ids = $this->input->post('campaign_ids');
        if (!is_array($campaign_ids)) {
            $campaign_ids = $campaign_ids ? explode(',', (string) $campaign_ids) : [];
        }
        $campaign_ids = array_values(array_filter(array_map('intval', $campaign_ids), function ($id) {
            return $id > 0;
        }));

        $now = date('Y-m-d H:i:s');
        $payload = [
            'month_key' => $month,
            'period_type' => $period_type,
            'employee_id' => 0,
            'campaign_ids' => json_encode($campaign_ids),
            'date_from' => null,
            'date_until' => null,
            'updated_at' => $now,
        ];

        $existing = $this->mymodel->selectWithQuery("
            SELECT id
            FROM kpi_employee_presets
            WHERE month_key = '" . $this->db->escape_str($month) . "'
                AND period_type = '" . $this->db->escape_str($period_type) . "'
                AND employee_id = 0
            LIMIT 1
        ");
        if (!empty($existing)) {
            $this->db->where('id', (int) $existing[0]['id'])->update('kpi_employee_presets', $payload);
        } else {
            $payload['created_at'] = $now;
            $this->db->insert('kpi_employee_presets', $payload);
        }

        return $this->json_response(true, 'Preset global tersimpan.');
    }

    private function persist_run_snapshot($month, $employee_id, $period, $metrics, $prepared_rows, $campaign_details, $position_id = null, $use_raw_window = false)
    {
        $period_type = $this->period_type_from_key($month);
        $existing_run = $this->mymodel->selectWithQuery("
            SELECT id
            FROM kpi_runs
            WHERE month_key = '" . $this->db->escape_str($month) . "'
                AND period_type = '" . $this->db->escape_str($period_type) . "'
                AND employee_id = '" . (int) $employee_id . "'
            LIMIT 1
        ");

        $total_score = 0;
        foreach ($prepared_rows as $r) {
            $total_score += (float) ($r['score'] ?? 0);
        }

        $now = date('Y-m-d H:i:s');
        $position_id = $position_id === null ? $this->get_employee_kpi_position_id($employee_id) : (int) $position_id;
        $window = $this->get_campaign_period_window(
            $period['start_date'],
            $period['until_date'],
            $use_raw_window ? false : $this->should_include_previous_month($position_id)
        );
        $run_data = [
            'month_key' => $month,
            'period_type' => $period_type,
            'employee_id' => $employee_id,
            'period_start' => $period['start_date'],
            'period_end' => $period['until_date'],
            'campaign_window_start' => $window['start'],
            'campaign_window_end' => $window['end'],
            'total_views' => (float) ($metrics['total_views'] ?? 0),
            'total_fyp' => (int) ($metrics['total_fyp'] ?? 0),
            'total_score' => $total_score,
            'campaign_count' => count($campaign_details),
            'generated_at' => $now,
            'generated_by' => (int) ($_SESSION['user']['id'] ?? 0),
            'updated_at' => $now,
        ];

        if (!empty($existing_run)) {
            $run_id = (int) $existing_run[0]['id'];
            $this->db->where('id', $run_id)->update('kpi_runs', $run_data);
            $this->db->delete('kpi_run_items', ['kpi_run_id' => $run_id]);
            $this->db->delete('kpi_run_campaigns', ['kpi_run_id' => $run_id]);
        } else {
            $run_data['is_locked'] = 0;
            $this->db->insert('kpi_runs', $run_data);
            $run_id = (int) $this->db->insert_id();
        }

        $now = date('Y-m-d H:i:s');
        foreach ($prepared_rows as $item) {
            $this->db->insert('kpi_run_items', [
                'kpi_run_id' => $run_id,
                'kpi_target_id' => $item['kpi_target_id'] > 0 ? $item['kpi_target_id'] : null,
                'indikator' => $item['indikator'],
                'target' => $item['target'],
                'bobot' => $item['bobot'],
                'metric_key' => $item['metric_key'],
                'dimensi' => $item['dimensi'],
                'capaian' => $item['capaian'],
                'score' => $item['score'],
                'sort_order' => $item['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($campaign_details as $campaign) {
            $this->db->insert('kpi_run_campaigns', [
                'kpi_run_id' => $run_id,
                'campaign_id' => (int) ($campaign['id_campaign'] ?? 0),
                'campaign_title' => (string) ($campaign['campaign_title'] ?? ''),
                'total_konten' => (int) ($campaign['total_konten'] ?? 0),
                'total_fyp' => (int) ($campaign['total_fyp'] ?? 0),
                'total_views' => (float) ($campaign['total_views'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function normalize_campaign_map($campaign_map_input)
    {
        if (!is_array($campaign_map_input)) {
            return [];
        }

        $campaign_map = [];
        foreach ($campaign_map_input as $employee_id => $campaign_ids) {
            $employee_id = (int) $employee_id;
            if ($employee_id <= 0) {
                continue;
            }

            if (!is_array($campaign_ids)) {
                $campaign_ids = $campaign_ids ? explode(',', (string) $campaign_ids) : [];
            }

            $clean_ids = array_values(array_filter(array_map('intval', $campaign_ids), function ($id) {
                return $id > 0;
            }));

            $campaign_map[$employee_id] = $clean_ids;
        }

        return $campaign_map;
    }

    private function normalize_campaign_ids($campaign_ids)
    {
        if (!is_array($campaign_ids)) {
            $campaign_ids = $campaign_ids ? explode(',', (string) $campaign_ids) : [];
        }

        return array_values(array_filter(array_map('intval', $campaign_ids), function ($id) {
            return $id > 0;
        }));
    }

    private function get_default_kpi_position_config()
    {
        $config = [
            7 => [
                'key' => 'content_creator',
                'label' => 'Content Creator',
            ],
            28 => [
                'key' => 'crm',
                'label' => 'CRM',
            ],
        ];

        foreach ($this->get_kol_specialist_position_ids() as $position_id) {
            $config[(int) $position_id] = [
                'key' => 'kol_specialist',
                'label' => self::KOL_SPECIALIST_POSITION_NAME,
            ];
        }

        return $config;
    }

    private function normalize_kpi_position_name($name)
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) $name)));
    }

    private function get_kol_specialist_position_ids()
    {
        if ($this->kol_specialist_position_ids_cache !== null) {
            return $this->kol_specialist_position_ids_cache;
        }

        $position_name = $this->db->escape_str($this->normalize_kpi_position_name(self::KOL_SPECIALIST_POSITION_NAME));
        $rows = $this->mymodel->selectWithQuery("
            SELECT id
            FROM positions
            WHERE LOWER(TRIM(name)) = '$position_name'
            ORDER BY id ASC
        ");

        $this->kol_specialist_position_ids_cache = array_values(array_filter(array_map(function ($row) {
            return (int) ($row['id'] ?? 0);
        }, (array) $rows), function ($position_id) {
            return $position_id > 0;
        }));

        return $this->kol_specialist_position_ids_cache;
    }

    private function is_kol_specialist_position_id($position_id)
    {
        return in_array((int) $position_id, $this->get_kol_specialist_position_ids(), true);
    }

    private function is_source_kpi_position_id($position_id)
    {
        $position_id = (int) $position_id;
        return $position_id === 7 || $this->is_kol_specialist_position_id($position_id);
    }

    private function encode_custom_kpi_position_id($custom_position_id)
    {
        $custom_position_id = (int) $custom_position_id;
        if ($custom_position_id <= 0) {
            return 0;
        }

        return self::CUSTOM_KPI_POSITION_OFFSET + $custom_position_id;
    }

    private function is_custom_kpi_position_id($position_id)
    {
        return (int) $position_id >= self::CUSTOM_KPI_POSITION_OFFSET;
    }

    private function decode_custom_kpi_position_id($position_id)
    {
        $position_id = (int) $position_id;
        if (!$this->is_custom_kpi_position_id($position_id)) {
            return 0;
        }

        return $position_id - self::CUSTOM_KPI_POSITION_OFFSET;
    }

    private function get_custom_kpi_position_rows()
    {
        if (!$this->has_kpi_custom_position_tables()) {
            return [];
        }

        return $this->mymodel->selectWithQuery("
            SELECT id, name, created_at, updated_at
            FROM kpi_custom_positions
            ORDER BY COALESCE(updated_at, created_at) DESC, id DESC
        ");
    }

    private function get_custom_kpi_position_detail($position_id)
    {
        if (!$this->has_kpi_custom_position_tables()) {
            return null;
        }
        $custom_position_id = $this->decode_custom_kpi_position_id($position_id);
        if ($custom_position_id <= 0) {
            return null;
        }

        $rows = $this->mymodel->selectWithQuery("
            SELECT id, name, created_at, updated_at
            FROM kpi_custom_positions
            WHERE id = '" . (int) $custom_position_id . "'
            LIMIT 1
        ");
        if (empty($rows)) {
            return null;
        }

        $row = $rows[0];
        return [
            'custom_position_id' => (int) ($row['id'] ?? 0),
            'position_id' => $this->encode_custom_kpi_position_id($row['id'] ?? 0),
            'name' => trim((string) ($row['name'] ?? 'Posisi Custom')),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    private function get_custom_kpi_position_member_ids($position_id)
    {
        if (!$this->has_kpi_custom_position_tables()) {
            return [];
        }
        $custom_position_id = $this->decode_custom_kpi_position_id($position_id);
        if ($custom_position_id <= 0) {
            return [];
        }

        $rows = $this->mymodel->selectWithQuery("
            SELECT employee_id
            FROM kpi_custom_position_members
            WHERE custom_position_id = '" . (int) $custom_position_id . "'
        ");

        return array_values(array_filter(array_map(function ($row) {
            return (int) ($row['employee_id'] ?? 0);
        }, $rows), function ($employee_id) {
            return $employee_id > 0;
        }));
    }

    private function get_assignable_kpi_employee_options()
    {
        $rows = $this->mymodel->selectWithQuery("
            SELECT
                u.id,
                u.full_name,
                up.position_id,
                COALESCE(p.name, '-') AS position_name
            FROM user u
            INNER JOIN user_profile up
                ON up.user_id = u.id
                AND up.id = (
                    SELECT MAX(up2.id)
                    FROM user_profile up2
                    WHERE up2.user_id = u.id
                )
            LEFT JOIN positions p
                ON p.id = up.position_id
            WHERE COALESCE(u.role, 0) <> 8
            ORDER BY u.full_name ASC
        ");

        $options = [];
        foreach ((array) $rows as $row) {
            $employee_id = (int) ($row['id'] ?? 0);
            if ($employee_id <= 0) {
                continue;
            }

            $full_name = trim((string) ($row['full_name'] ?? ''));
            if ($full_name === '') {
                continue;
            }

            $position_name = trim((string) ($row['position_name'] ?? '-'));
            $options[] = [
                'id' => $employee_id,
                'full_name' => $full_name,
                'position_id' => (int) ($row['position_id'] ?? 0),
                'position_name' => $position_name,
                'label' => $position_name !== '' && $position_name !== '-'
                    ? ($full_name . ' - ' . $position_name)
                    : $full_name,
            ];
        }

        return $options;
    }

    private function create_custom_kpi_position($name)
    {
        if (!$this->has_kpi_custom_position_tables()) {
            return 0;
        }
        $name = trim((string) $name);
        if ($name === '') {
            return 0;
        }

        $duplicate = $this->mymodel->selectWithQuery("
            SELECT id
            FROM kpi_custom_positions
            WHERE LOWER(TRIM(name)) = LOWER(" . $this->db->escape($name) . ")
            LIMIT 1
        ");
        if (!empty($duplicate[0]['id'])) {
            return $this->encode_custom_kpi_position_id($duplicate[0]['id']);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->insert('kpi_custom_positions', [
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->encode_custom_kpi_position_id((int) $this->db->insert_id());
    }

    private function save_custom_kpi_position_members($position_id, $employee_ids)
    {
        if (!$this->has_kpi_custom_position_tables()) {
            return;
        }
        $custom_position_id = $this->decode_custom_kpi_position_id($position_id);
        if ($custom_position_id <= 0) {
            return;
        }

        $employee_ids = array_values(array_unique(array_filter(array_map('intval', (array) $employee_ids), function ($employee_id) {
            return $employee_id > 0;
        })));

        $this->db->delete('kpi_custom_position_members', ['custom_position_id' => $custom_position_id]);
        if (empty($employee_ids)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($employee_ids as $employee_id) {
            // One employee can belong to only one custom KPI position at a time.
            $this->db->delete('kpi_custom_position_members', ['employee_id' => $employee_id]);
            $this->db->insert('kpi_custom_position_members', [
                'custom_position_id' => $custom_position_id,
                'employee_id' => $employee_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function get_custom_position_template_month_keys($position_id)
    {
        if (!$this->is_custom_kpi_position_id($position_id) || !$this->has_kpi_custom_position_tables()) {
            return [];
        }

        $rows = $this->mymodel->selectWithQuery("
            SELECT DISTINCT month_key
            FROM kpi_templates
            WHERE position_id = '" . (int) $position_id . "'
              AND COALESCE(period_type, 'quarter') = 'quarter'
            ORDER BY month_key ASC
        ");

        return array_values(array_filter(array_map(function ($row) {
            return trim((string) ($row['month_key'] ?? ''));
        }, $rows), 'strlen'));
    }

    private function has_kpi_custom_position_tables()
    {
        if ($this->kpi_custom_tables_ready !== null) {
            return (bool) $this->kpi_custom_tables_ready;
        }

        $has_positions = !empty($this->mymodel->selectWithQuery("SHOW TABLES LIKE 'kpi_custom_positions'"));
        $has_members = !empty($this->mymodel->selectWithQuery("SHOW TABLES LIKE 'kpi_custom_position_members'"));
        $this->kpi_custom_tables_ready = $has_positions && $has_members;

        return (bool) $this->kpi_custom_tables_ready;
    }

    private function clear_kpi_run_snapshot($employee_id, $month, $period_type)
    {
        $employee_id = (int) $employee_id;
        $month = trim((string) $month);
        $period_type = trim((string) $period_type);
        if ($employee_id <= 0 || $month === '' || $period_type === '') {
            return;
        }

        $run_rows = $this->mymodel->selectWithQuery("
            SELECT id
            FROM kpi_runs
            WHERE month_key = '" . $this->db->escape_str($month) . "'
              AND period_type = '" . $this->db->escape_str($period_type) . "'
              AND employee_id = '" . $employee_id . "'
        ");

        foreach ($run_rows as $run_row) {
            $run_id = (int) ($run_row['id'] ?? 0);
            if ($run_id <= 0) {
                continue;
            }
            $this->db->delete('kpi_run_items', ['kpi_run_id' => $run_id]);
            $this->db->delete('kpi_run_campaigns', ['kpi_run_id' => $run_id]);
            $this->db->delete('kpi_runs', ['id' => $run_id]);
        }
    }

    private function clear_employee_kpi_period_state($employee_id, $month, $period_type)
    {
        $employee_id = (int) $employee_id;
        $month = trim((string) $month);
        $period_type = trim((string) $period_type);
        if ($employee_id <= 0 || $month === '' || $period_type === '') {
            return;
        }

        $this->db->delete('kpi_targets', [
            'employee_id' => $employee_id,
            'month_key' => $month,
            'period_type' => $period_type,
        ]);
        $this->clear_kpi_run_snapshot($employee_id, $month, $period_type);
    }

    private function sync_custom_position_employee_targets($position_id, $affected_employee_ids)
    {
        $position_id = (int) $position_id;
        if (!$this->is_custom_kpi_position_id($position_id) || !$this->has_kpi_custom_position_tables()) {
            return;
        }

        $affected_employee_ids = array_values(array_unique(array_filter(array_map('intval', (array) $affected_employee_ids), function ($employee_id) {
            return $employee_id > 0;
        })));
        if (empty($affected_employee_ids)) {
            return;
        }

        $template_month_keys = $this->get_custom_position_template_month_keys($position_id);
        if (empty($template_month_keys)) {
            $template_month_keys = [$this->current_quarter_key()];
        }

        foreach ($template_month_keys as $template_month_key) {
            foreach ($affected_employee_ids as $employee_id) {
                $this->clear_employee_kpi_period_state($employee_id, $template_month_key, 'quarter');

                $effective_position_id = $this->get_employee_kpi_position_id($employee_id);
                if ($effective_position_id <= 0) {
                    continue;
                }

                $effective_month = $this->normalize_period_key_for_position($template_month_key, $effective_position_id, '');
                $effective_period_type = $this->is_kol_specialist_position_id($effective_position_id) ? 'month' : 'quarter';
                if ($effective_month === '') {
                    continue;
                }

                $this->clear_employee_kpi_period_state($employee_id, $effective_month, $effective_period_type);
                $this->seed_kpi_rows_for_employee($template_month_key, $employee_id, $effective_position_id);
            }
        }
    }

    private function get_kpi_position_options()
    {
        if ($this->kpi_position_options_cache !== null) {
            return $this->kpi_position_options_cache;
        }

        $rows = $this->mymodel->selectWithQuery("
            SELECT
                up.position_id,
                positions.name,
                COALESCE(ql.name, '-') AS level_name,
                MAX(up.id) AS latest_profile_id
            FROM user_profile up
            INNER JOIN positions
                ON up.position_id = positions.id
            LEFT JOIN quest_levels AS ql
                ON positions.level_id = ql.id
            WHERE up.position_id IS NOT NULL
                AND up.position_id > 0
            GROUP BY up.position_id, positions.name, ql.name
            ORDER BY latest_profile_id DESC
        ");

        $options = [];
        foreach ((array) $rows as $row) {
            $position_id = (int) ($row['position_id'] ?? 0);
            if ($position_id <= 0) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $level_name = trim((string) ($row['level_name'] ?? '-'));
            if ($level_name === '') {
                $level_name = '-';
            }
            $options[$position_id] = [
                'position_id' => $position_id,
                'name' => $name,
                'level_name' => $level_name,
                'is_custom' => false,
                'tab_key' => $this->build_kpi_division_key($position_id),
                'tab_label' => $this->format_kpi_position_label($position_id, $name, $level_name, true),
                'option_label' => $this->format_kpi_position_label($position_id, $name, $level_name, false),
            ];
        }

        $defaults = $this->get_default_kpi_position_config();
        $missing_default_ids = array_values(array_diff(array_keys($defaults), array_keys($options)));
        if (!empty($missing_default_ids)) {
            $default_rows = $this->mymodel->selectWithQuery("
                SELECT
                    positions.id AS position_id,
                    positions.name,
                    COALESCE(ql.name, '-') AS level_name
                FROM positions
                LEFT JOIN quest_levels AS ql
                    ON positions.level_id = ql.id
                WHERE positions.id IN (" . implode(',', array_map('intval', $missing_default_ids)) . ")
            ");
            foreach ((array) $default_rows as $row) {
                $position_id = (int) ($row['position_id'] ?? 0);
                if ($position_id <= 0 || isset($options[$position_id])) {
                    continue;
                }
                $name = trim((string) ($row['name'] ?? ($defaults[$position_id]['label'] ?? 'Posisi')));
                $level_name = trim((string) ($row['level_name'] ?? '-'));
                if ($level_name === '') {
                    $level_name = '-';
                }
                $options[$position_id] = [
                    'position_id' => $position_id,
                    'name' => $name,
                    'level_name' => $level_name,
                    'is_custom' => false,
                    'tab_key' => $this->build_kpi_division_key($position_id),
                    'tab_label' => $this->format_kpi_position_label($position_id, $name, $level_name, true),
                    'option_label' => $this->format_kpi_position_label($position_id, $name, $level_name, false),
                ];
            }
        }

        foreach ($this->get_custom_kpi_position_rows() as $row) {
            $position_id = $this->encode_custom_kpi_position_id($row['id'] ?? 0);
            if ($position_id <= 0) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? 'Posisi Custom'));
            if ($name === '') {
                $name = 'Posisi Custom';
            }
            $options[$position_id] = [
                'position_id' => $position_id,
                'name' => $name,
                'level_name' => 'Custom',
                'is_custom' => true,
                'tab_key' => $this->build_kpi_division_key($position_id),
                'tab_label' => $name,
                'option_label' => $name,
            ];
        }

        $this->kpi_position_options_cache = array_values($options);
        return $this->kpi_position_options_cache;
    }

    private function get_kpi_position_options_map()
    {
        $map = [];
        foreach ($this->get_kpi_position_options() as $option) {
            $position_id = (int) ($option['position_id'] ?? 0);
            if ($position_id > 0) {
                $map[$position_id] = $option;
            }
        }
        return $map;
    }

    private function format_kpi_position_label($position_id, $name, $level_name = '-', $for_tab = false)
    {
        $position_id = (int) $position_id;
        if ($this->is_custom_kpi_position_id($position_id)) {
            return trim((string) $name) !== '' ? trim((string) $name) : 'Posisi Custom';
        }
        $defaults = $this->get_default_kpi_position_config();
        if ($for_tab && isset($defaults[$position_id])) {
            return $defaults[$position_id]['label'];
        }

        $name = trim((string) $name);
        $level_name = trim((string) $level_name);
        if ($level_name === '' || $level_name === '-') {
            return $name;
        }

        return $for_tab ? ($name . ' (' . $level_name . ')') : ($name . ' - ' . $level_name);
    }

    private function build_kpi_division_key($position_id)
    {
        $position_id = (int) $position_id;
        if ($this->is_custom_kpi_position_id($position_id)) {
            return 'custom_position_' . $this->decode_custom_kpi_position_id($position_id);
        }
        if ($this->is_kol_specialist_position_id($position_id)) {
            return 'kol_specialist';
        }
        $defaults = $this->get_default_kpi_position_config();
        if (isset($defaults[$position_id])) {
            return $defaults[$position_id]['key'];
        }
        return 'position_' . $position_id;
    }

    private function is_valid_kpi_position_id($position_id)
    {
        $position_id = (int) $position_id;
        if ($position_id <= 0) {
            return false;
        }
        $options = $this->get_kpi_position_options_map();
        return isset($options[$position_id]);
    }

    private function is_system_kpi_position($position_id)
    {
        $position_id = (int) $position_id;
        return isset($this->get_default_kpi_position_config()[$position_id]);
    }

    private function get_kpi_enabled_position_ids()
    {
        $default_ids = array_keys($this->get_default_kpi_position_config());
        $position_options = $this->get_kpi_position_options_map();
        $enabled_map = [];
        foreach ($default_ids as $position_id) {
            $enabled_map[(int) $position_id] = true;
        }

        $template_rows = $this->mymodel->selectWithQuery("
            SELECT DISTINCT position_id
            FROM kpi_templates
            WHERE position_id > 0
        ");
        $template_map = [];
        foreach ((array) $template_rows as $row) {
            $position_id = (int) ($row['position_id'] ?? 0);
            if ($position_id > 0) {
                $template_map[$position_id] = true;
            }
        }

        foreach ($position_options as $position_id => $option) {
            if (!empty($template_map[$position_id]) || !empty($option['is_custom'])) {
                $enabled_map[(int) $position_id] = true;
            }
        }

        return array_values(array_map('intval', array_keys($enabled_map)));
    }

    private function get_kpi_employee_rows_by_positions($position_ids)
    {
        $position_ids = array_values(array_unique(array_filter(array_map('intval', (array) $position_ids), function ($id) {
            return $id > 0;
        })));
        if (empty($position_ids)) {
            return [];
        }

        $actual_position_ids = [];
        $custom_position_ids = [];
        foreach ($position_ids as $position_id) {
            if ($this->is_custom_kpi_position_id($position_id)) {
                $custom_position_ids[] = $position_id;
            } else {
                $actual_position_ids[] = $position_id;
            }
        }

        $rows = [];
        if (!empty($actual_position_ids)) {
            $kol_position_ids = $this->get_kol_specialist_position_ids();
            $source_position_ids = array_values(array_unique(array_merge([7], $kol_position_ids)));
            $source_position_sql = implode(',', array_map('intval', $source_position_ids));
            $kol_position_sql = implode(',', array_map('intval', $kol_position_ids));
            $kol_pic_condition = $kol_position_sql !== ''
                ? "(up.position_id IN ($kol_position_sql) AND e2.pic_user_id = u.id)"
                : "0 = 1";
            $custom_filter = $this->has_kpi_custom_position_tables() ? "
                    AND NOT EXISTS (
                        SELECT 1
                        FROM kpi_custom_position_members kcpm
                        WHERE kcpm.employee_id = u.id
                    )" : '';
            $rows = $this->mymodel->selectWithQuery("
                SELECT
                    u.id,
                    u.full_name,
                    up.position_id
                FROM user u
                INNER JOIN user_profile up
                    ON up.user_id = u.id
                    AND up.id = (
                        SELECT MAX(up2.id)
                        FROM user_profile up2
                        WHERE up2.user_id = u.id
                )
                WHERE up.position_id IN (" . implode(',', $actual_position_ids) . ")
                    AND COALESCE(u.role, 0) <> 8
                    $custom_filter
                    AND (
                        up.position_id <> 28
                        OR u.id = 56
                    )
                    AND (
                        up.position_id NOT IN ($source_position_sql)
                        OR EXISTS (
                            SELECT 1
                            FROM endorse e2
                            INNER JOIN endorse_campaign ec2 ON ec2.id = e2.id_campaign
                            WHERE e2.status = 'Aktif'
                                AND ec2.status = 'Aktif'
                                AND (
                                    $kol_pic_condition
                                    OR (up.position_id = 7 AND e2.created_by = u.id)
                                )
                        )
                    )
                ORDER BY up.position_id ASC, u.full_name ASC
            ");
        }

        if (!empty($custom_position_ids) && $this->has_kpi_custom_position_tables()) {
            $custom_rows = $this->mymodel->selectWithQuery("
                SELECT
                    u.id,
                    u.full_name,
                    kcpm.custom_position_id
                FROM kpi_custom_position_members kcpm
                INNER JOIN user u
                    ON u.id = kcpm.employee_id
                WHERE kcpm.custom_position_id IN (" . implode(',', array_map(function ($position_id) {
                    return $this->decode_custom_kpi_position_id($position_id);
                }, $custom_position_ids)) . ")
                    AND COALESCE(u.role, 0) <> 8
                ORDER BY kcpm.custom_position_id ASC, u.full_name ASC
            ");

            foreach ($custom_rows as $custom_row) {
                $custom_position_id = $this->encode_custom_kpi_position_id((int) ($custom_row['custom_position_id'] ?? 0));
                if ($custom_position_id <= 0) {
                    continue;
                }
                $rows[] = [
                    'id' => (int) ($custom_row['id'] ?? 0),
                    'full_name' => (string) ($custom_row['full_name'] ?? ''),
                    'position_id' => $custom_position_id,
                ];
            }
        }

        return $rows;
    }

    private function get_campaign_options_by_employee($employee_id, $start_date, $until_date, $position_id = null)
    {
        $employee_id = (int) $employee_id;
        if ($employee_id <= 0) {
            return [];
        }

        $position_id = $position_id === null ? $this->get_employee_kpi_position_id($employee_id) : (int) $position_id;
        if ($position_id === 28) {
            return [];
        }
        $current_year = (int) date('Y');
        $window = [
            'start' => sprintf('%04d-01-01', $current_year),
            'end' => sprintf('%04d-12-31', $current_year),
        ];
        $window_start = $this->db->escape_str($window['start']);
        $window_end = $this->db->escape_str($window['end']);
        $endorse_status_filter = '';
        $employee_field = 'e.created_by';
        $internal_filter = " AND COALESCE(ec.is_internal, 0) = 1";
        if ($this->is_kol_specialist_position_id($position_id)) {
            $endorse_status_filter = " AND e.status_endorse = 'Posted Content'";
            $employee_field = 'e.pic_user_id';
            $internal_filter = " AND COALESCE(ec.is_internal, 0) = 0";
        }

        return $this->mymodel->selectWithQuery("
            SELECT DISTINCT
                e.id_campaign AS id,
                ec.title
            FROM endorse e
            INNER JOIN endorse_campaign ec ON ec.id = e.id_campaign
            WHERE $employee_field = '$employee_id'
                AND e.status = 'Aktif'
                $endorse_status_filter
                AND ec.status = 'Aktif'
                $internal_filter
                AND DATE(COALESCE(ec.start_at, '1900-01-01')) <= '$window_end'
                AND DATE(COALESCE(ec.until_at, '2999-12-31')) >= '$window_start'
            ORDER BY ec.title ASC
        ");
    }

    private function get_kpi_employees_by_division()
    {
        $position_options = $this->get_kpi_position_options_map();
        $default_config = $this->get_default_kpi_position_config();
        $enabled_position_ids = $this->get_kpi_enabled_position_ids();
        $division_groups = [];
        foreach ($enabled_position_ids as $position_id) {
            $position_id = (int) $position_id;
            $option = $position_options[$position_id] ?? [];
            $default = $default_config[$position_id] ?? null;
            $key = $this->build_kpi_division_key($position_id);
            $label = $default['label'] ?? ($option['tab_label'] ?? ('Posisi #' . $position_id));
            $division_groups[$key] = [
                'key' => $key,
                'label' => $label,
                'position_id' => $position_id,
                'level_name' => (string) ($option['level_name'] ?? ''),
                'employees' => [],
            ];
        }

        $employees = $this->get_kpi_employee_rows_by_positions($enabled_position_ids);

        foreach ($employees as $employee) {
            $position_id = (int) ($employee['position_id'] ?? 0);
            $division_key = $this->build_kpi_division_key($position_id);
            if (!isset($division_groups[$division_key])) {
                continue;
            }
            $division_groups[$division_key]['employees'][] = [
                'id' => (int) $employee['id'],
                'full_name' => trim((string) $employee['full_name']),
                'position_id' => $position_id,
            ];
        }

        return $division_groups;
    }

    private function get_template_position_employees($month, $position_id)
    {
        $position_id = (int) $position_id;
        if (!$this->is_valid_kpi_position_id($position_id)) {
            return [];
        }

        $employees = [];
        foreach ($this->get_kpi_employee_rows_by_positions([$position_id]) as $employee) {
            $employees[] = [
                'id' => (int) ($employee['id'] ?? 0),
                'full_name' => trim((string) ($employee['full_name'] ?? '')),
                'position_id' => (int) ($employee['position_id'] ?? 0),
            ];
        }
        if (empty($employees)) {
            return [];
        }

        $employee_ids = array_values(array_filter(array_map(function ($employee) {
            return (int) ($employee['id'] ?? 0);
        }, $employees), function ($id) {
            return $id > 0;
        }));
        if (empty($employee_ids)) {
            return [];
        }

        $period_type = $this->db->escape_str($this->period_type_from_key($month));
        $preset_rows = $this->mymodel->selectWithQuery("
            SELECT employee_id, campaign_ids, source_rules_json, date_from, date_until
            FROM kpi_employee_presets
            WHERE month_key = '" . $this->db->escape_str($month) . "'
                AND period_type = '$period_type'
                AND employee_id IN (" . implode(',', $employee_ids) . ")
        ");
        $preset_map = [];
        $all_campaign_ids = [];
        foreach ($preset_rows as $row) {
            $campaign_ids = [];
            $raw = trim((string) ($row['campaign_ids'] ?? ''));
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $campaign_ids = array_values(array_filter(array_map('intval', $decoded), function ($id) {
                        return $id > 0;
                    }));
                    $all_campaign_ids = array_merge($all_campaign_ids, $campaign_ids);
                }
            }
            $employee_id = (int) ($row['employee_id'] ?? 0);
            if ($employee_id <= 0) {
                continue;
            }
            $preset_map[$employee_id] = [
                'campaign_ids' => $campaign_ids,
                'source_rules' => $this->decode_source_config($row['source_rules_json'] ?? '')['rules'],
                'growth_date_from' => $this->decode_source_config($row['source_rules_json'] ?? '')['growth_date_from'],
                'growth_date_until' => $this->decode_source_config($row['source_rules_json'] ?? '')['growth_date_until'],
                'date_from' => (string) ($row['date_from'] ?? ''),
                'date_until' => (string) ($row['date_until'] ?? ''),
            ];
        }

        $campaign_title_map = [];
        $all_campaign_ids = array_values(array_unique(array_map('intval', $all_campaign_ids)));
        if (!empty($all_campaign_ids)) {
            $campaign_rows = $this->mymodel->selectWithQuery("
                SELECT id, title
                FROM endorse_campaign
                WHERE id IN (" . implode(',', $all_campaign_ids) . ")
            ");
            foreach ($campaign_rows as $campaign_row) {
                $campaign_id = (int) ($campaign_row['id'] ?? 0);
                if ($campaign_id <= 0) {
                    continue;
                }
                $campaign_title_map[$campaign_id] = trim((string) ($campaign_row['title'] ?? ''));
            }
        }

        $locked_rows = $this->mymodel->selectWithQuery("
            SELECT employee_id, is_locked
            FROM kpi_runs
            WHERE month_key = '" . $this->db->escape_str($month) . "'
                AND period_type = '$period_type'
                AND employee_id IN (" . implode(',', $employee_ids) . ")
        ");
        $locked_map = [];
        foreach ($locked_rows as $row) {
            $locked_map[(int) ($row['employee_id'] ?? 0)] = (int) ($row['is_locked'] ?? 0) === 1 ? 1 : 0;
        }

        $items = [];
        foreach ($employees as $employee) {
            $employee_id = (int) ($employee['id'] ?? 0);
            if ($employee_id <= 0) {
                continue;
            }
            $default_source = [];
            if ((int) ($employee['position_id'] ?? 0) === 7) {
                $default_source = $this->build_default_cc_source_data($month, $employee_id);
            }
            $preset = $preset_map[$employee_id] ?? [
                'campaign_ids' => $default_source['campaign_ids'] ?? [],
                'source_rules' => [],
                'date_from' => $default_source['date_from'] ?? '',
                'date_until' => $default_source['date_until'] ?? '',
            ];
            $is_default_source = !isset($preset_map[$employee_id]) && !empty($default_source);
            $campaign_titles = [];
            foreach (($preset['campaign_ids'] ?? []) as $campaign_id) {
                $campaign_id = (int) $campaign_id;
                if ($campaign_id <= 0) {
                    continue;
                }
                $campaign_title = trim((string) ($campaign_title_map[$campaign_id] ?? ''));
                $campaign_titles[] = $campaign_title !== '' ? $campaign_title : '';
            }
            if (empty($campaign_titles) && !empty($default_source['campaign_titles'])) {
                $campaign_titles = $default_source['campaign_titles'];
            }
            $source_rules_summary = [];
            if (!empty($preset['source_rules'])) {
                $source_rules_summary = $this->summarize_source_rules($preset['source_rules'], $campaign_title_map);
            }
            $items[] = [
                'id' => $employee_id,
                'full_name' => trim((string) ($employee['full_name'] ?? '-')),
                'position_id' => (int) ($employee['position_id'] ?? 0),
                'campaign_ids' => $preset['campaign_ids'] ?? [],
                'campaign_count' => count($preset['campaign_ids'] ?? []),
                'campaign_titles' => $campaign_titles,
                'source_rules' => $preset['source_rules'] ?? [],
                'source_rules_summary' => $source_rules_summary,
                'date_from' => (string) ($preset['date_from'] ?? ''),
                'date_until' => (string) ($preset['date_until'] ?? ''),
                'growth_date_from' => (string) ($preset['growth_date_from'] ?? ''),
                'growth_date_until' => (string) ($preset['growth_date_until'] ?? ''),
                'is_default_source' => $is_default_source ? 1 : 0,
                'is_locked' => (int) ($locked_map[$employee_id] ?? 0),
            ];
        }

        return $items;
    }

    private function get_template_position_employees_map($month, $employee_ids)
    {
        $employee_ids = array_values(array_filter(array_map('intval', (array) $employee_ids), function ($id) {
            return $id > 0;
        }));
        if (empty($employee_ids)) {
            return [];
        }

        $rows = $this->mymodel->selectWithQuery("
            SELECT
                u.id,
                u.full_name,
                up.position_id
            FROM user u
            INNER JOIN user_profile up
                ON up.user_id = u.id
                AND up.id = (
                    SELECT MAX(up2.id)
                    FROM user_profile up2
                    WHERE up2.user_id = u.id
                )
            WHERE u.id IN (" . implode(',', $employee_ids) . ")
        ");

        $map = [];
        foreach ($rows as $row) {
            $employee_id = (int) ($row['id'] ?? 0);
            if ($employee_id <= 0) {
                continue;
            }
            $kpi_position_id = $this->get_employee_kpi_position_id($employee_id);
            $map[$employee_id] = [
                'id' => $employee_id,
                'full_name' => trim((string) ($row['full_name'] ?? '-')),
                'position_id' => $kpi_position_id > 0 ? $kpi_position_id : (int) ($row['position_id'] ?? 0),
                'actual_position_id' => (int) ($row['position_id'] ?? 0),
            ];
        }

        return $map;
    }

    private function ensure_kpi_table()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `kpi_targets` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `month_key` CHAR(7) NOT NULL,
                `template_month_key` CHAR(7) NOT NULL DEFAULT '',
                `employee_id` INT(11) NOT NULL,
                `indikator` VARCHAR(100) NOT NULL,
                `target` DECIMAL(18,2) NOT NULL DEFAULT 0,
                `bobot` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `metric_key` VARCHAR(30) NOT NULL DEFAULT '',
                `manual_capaian` DECIMAL(18,2) NOT NULL DEFAULT 0,
                `durasi` VARCHAR(50) NOT NULL DEFAULT '1 Bulan',
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                KEY `idx_kpi_month_employee` (`month_key`, `employee_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $this->db->query($sql);
        $this->ensure_kpi_target_metric_column();
        $this->ensure_kpi_target_manual_capaian_column();
        $this->ensure_kpi_target_override_capaian_column();
        $this->ensure_kpi_target_template_month_column();
    }

    private function ensure_kpi_template_table()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `kpi_templates` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `month_key` CHAR(7) NOT NULL,
                `position_id` INT(11) NOT NULL DEFAULT 0,
                `indikator` VARCHAR(100) NOT NULL,
                `target` DECIMAL(18,2) NOT NULL DEFAULT 0,
                `bobot` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `metric_key` VARCHAR(30) NOT NULL DEFAULT '',
                `dimensi` VARCHAR(100) NOT NULL DEFAULT '1 Bulan',
                `keterangan` TEXT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                KEY `idx_kpi_templates_month` (`month_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $this->db->query($sql);
        $this->ensure_kpi_template_position_column();
        $this->ensure_kpi_template_metric_column();
    }

    private function ensure_kpi_period_type_columns()
    {
        $tables = ['kpi_targets', 'kpi_templates', 'kpi_runs', 'kpi_employee_presets', 'crm_kpi_content_logs'];
        foreach ($tables as $table) {
            $table_exists = $this->mymodel->selectWithQuery("SHOW TABLES LIKE '" . $this->db->escape_str($table) . "'");
            if (empty($table_exists)) {
                continue;
            }
            $col_exists = $this->mymodel->selectWithQuery("SHOW COLUMNS FROM `$table` LIKE 'period_type'");
            if (empty($col_exists)) {
                $this->db->query("ALTER TABLE `$table` ADD COLUMN period_type VARCHAR(8) NOT NULL DEFAULT 'month' AFTER month_key");
            }
        }

        $index_specs = [
            'kpi_targets' => [
                'name' => 'idx_kpi_month_employee',
                'columns' => '(`month_key`, `period_type`, `employee_id`)',
                'unique' => false,
            ],
            'kpi_templates' => [
                'name' => 'idx_kpi_templates_month',
                'columns' => '(`month_key`, `period_type`, `position_id`)',
                'unique' => false,
            ],
            'kpi_runs' => [
                'name' => 'uniq_kpi_run_month_employee',
                'columns' => '(`month_key`, `period_type`, `employee_id`)',
                'unique' => true,
            ],
            'kpi_employee_presets' => [
                'name' => 'uniq_kpi_preset_month_employee',
                'columns' => '(`month_key`, `period_type`, `employee_id`)',
                'unique' => true,
            ],
            'crm_kpi_content_logs' => [
                'name' => 'idx_crm_kpi_month_employee',
                'columns' => '(`month_key`, `period_type`, `employee_id`)',
                'unique' => false,
            ],
        ];

        foreach ($index_specs as $table => $spec) {
            $table_exists = $this->mymodel->selectWithQuery("SHOW TABLES LIKE '" . $this->db->escape_str($table) . "'");
            if (empty($table_exists)) {
                continue;
            }
            $existing = $this->mymodel->selectWithQuery("SHOW INDEX FROM `$table` WHERE Key_name = '" . $this->db->escape_str($spec['name']) . "'");
            $has_period_type = false;
            foreach ($existing as $row) {
                if (isset($row['Column_name']) && (string) $row['Column_name'] === 'period_type') {
                    $has_period_type = true;
                    break;
                }
            }
            if ($has_period_type) {
                continue;
            }
            if (!empty($existing)) {
                $this->db->query("ALTER TABLE `$table` DROP INDEX `" . $spec['name'] . "`");
            }
            $unique_kw = $spec['unique'] ? 'UNIQUE' : '';
            $this->db->query("ALTER TABLE `$table` ADD $unique_kw INDEX `" . $spec['name'] . "` " . $spec['columns']);
        }
    }

    private function ensure_kpi_target_metric_column()
    {
        $exists = $this->mymodel->selectWithQuery("SHOW COLUMNS FROM kpi_targets LIKE 'metric_key'");
        if (empty($exists)) {
            $this->db->query("ALTER TABLE kpi_targets ADD COLUMN metric_key VARCHAR(30) NOT NULL DEFAULT '' AFTER bobot");
        }
    }

    private function ensure_kpi_target_manual_capaian_column()
    {
        $exists = $this->mymodel->selectWithQuery("SHOW COLUMNS FROM kpi_targets LIKE 'manual_capaian'");
        if (empty($exists)) {
            $this->db->query("ALTER TABLE kpi_targets ADD COLUMN manual_capaian DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER metric_key");
        }
    }

    private function ensure_kpi_target_override_capaian_column()
    {
        $exists = $this->mymodel->selectWithQuery("SHOW COLUMNS FROM kpi_targets LIKE 'override_capaian'");
        if (empty($exists)) {
            $this->db->query("ALTER TABLE kpi_targets ADD COLUMN override_capaian DECIMAL(18,2) NULL DEFAULT NULL AFTER manual_capaian");
        }
    }

    private function ensure_kpi_target_template_month_column()
    {
        $exists = $this->mymodel->selectWithQuery("SHOW COLUMNS FROM kpi_targets LIKE 'template_month_key'");
        if (empty($exists)) {
            $this->db->query("ALTER TABLE kpi_targets ADD COLUMN template_month_key CHAR(7) NOT NULL DEFAULT '' AFTER month_key");
        }
    }

    private function ensure_kpi_template_position_column()
    {
        $exists = $this->mymodel->selectWithQuery("SHOW COLUMNS FROM kpi_templates LIKE 'position_id'");
        if (empty($exists)) {
            $this->db->query("ALTER TABLE kpi_templates ADD COLUMN position_id INT(11) NOT NULL DEFAULT 0 AFTER month_key");
        }
    }

    private function ensure_kpi_template_metric_column()
    {
        $exists = $this->mymodel->selectWithQuery("SHOW COLUMNS FROM kpi_templates LIKE 'metric_key'");
        if (empty($exists)) {
            $this->db->query("ALTER TABLE kpi_templates ADD COLUMN metric_key VARCHAR(30) NOT NULL DEFAULT '' AFTER bobot");
        }
    }

    private function ensure_kpi_run_tables()
    {
        $sql_runs = "
            CREATE TABLE IF NOT EXISTS `kpi_runs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `month_key` CHAR(7) NOT NULL,
                `employee_id` INT(11) NOT NULL,
                `period_start` DATE NOT NULL,
                `period_end` DATE NOT NULL,
                `campaign_window_start` DATE NOT NULL,
                `campaign_window_end` DATE NOT NULL,
                `total_views` DECIMAL(18,2) NOT NULL DEFAULT 0,
                `total_fyp` INT(11) NOT NULL DEFAULT 0,
                `total_score` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `campaign_count` INT(11) NOT NULL DEFAULT 0,
                `generated_at` DATETIME NULL,
                `generated_by` INT(11) NULL,
                `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
                `locked_at` DATETIME NULL,
                `locked_by` INT(11) NULL,
                `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_kpi_run_month_employee` (`month_key`, `employee_id`),
                KEY `idx_kpi_runs_month` (`month_key`),
                KEY `idx_kpi_runs_employee` (`employee_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $sql_items = "
            CREATE TABLE IF NOT EXISTS `kpi_run_items` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `kpi_run_id` BIGINT UNSIGNED NOT NULL,
                `kpi_target_id` INT(11) NULL,
                `indikator` VARCHAR(100) NOT NULL,
                `target` DECIMAL(18,2) NOT NULL DEFAULT 0,
                `bobot` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `metric_key` VARCHAR(30) NOT NULL DEFAULT '',
                `dimensi` VARCHAR(100) NOT NULL DEFAULT '1 Bulan',
                `capaian` DECIMAL(18,2) NOT NULL DEFAULT 0,
                `score` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                KEY `idx_kpi_run_items_run` (`kpi_run_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $sql_campaigns = "
            CREATE TABLE IF NOT EXISTS `kpi_run_campaigns` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `kpi_run_id` BIGINT UNSIGNED NOT NULL,
                `campaign_id` INT(11) NOT NULL,
                `campaign_title` VARCHAR(255) NOT NULL,
                `total_konten` INT(11) NOT NULL DEFAULT 0,
                `total_fyp` INT(11) NOT NULL DEFAULT 0,
                `total_views` DECIMAL(18,2) NOT NULL DEFAULT 0,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                KEY `idx_kpi_run_campaigns_run` (`kpi_run_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $this->db->query($sql_runs);
        $this->db->query($sql_items);
        $this->db->query($sql_campaigns);
        $this->ensure_kpi_run_lock_columns();
        $this->ensure_kpi_run_item_metric_column();
    }

    private function ensure_kpi_run_lock_columns()
    {
        $checks = [
            'is_locked' => "ALTER TABLE kpi_runs ADD COLUMN is_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER generated_by",
            'locked_at' => "ALTER TABLE kpi_runs ADD COLUMN locked_at DATETIME NULL AFTER is_locked",
            'locked_by' => "ALTER TABLE kpi_runs ADD COLUMN locked_by INT(11) NULL AFTER locked_at",
        ];

        foreach ($checks as $column => $sql) {
            $exists = $this->mymodel->selectWithQuery("SHOW COLUMNS FROM kpi_runs LIKE '" . $this->db->escape_str($column) . "'");
            if (empty($exists)) {
                $this->db->query($sql);
            }
        }
    }

    private function ensure_kpi_run_item_metric_column()
    {
        $exists = $this->mymodel->selectWithQuery("SHOW COLUMNS FROM kpi_run_items LIKE 'metric_key'");
        if (empty($exists)) {
            $this->db->query("ALTER TABLE kpi_run_items ADD COLUMN metric_key VARCHAR(30) NOT NULL DEFAULT '' AFTER bobot");
        }
    }

    private function get_saved_run_snapshot($month, $employee_id)
    {
        $period_type = $this->period_type_from_key($month);
        $period_type_esc = $this->db->escape_str($period_type);
        $month = $this->db->escape_str($month);
        $employee_id = (int) $employee_id;

        $run = $this->mymodel->selectWithQuery("
            SELECT *
            FROM kpi_runs
            WHERE month_key = '$month'
                AND period_type = '$period_type_esc'
                AND employee_id = '$employee_id'
            LIMIT 1
        ");

        if (empty($run)) {
            return ['run' => null, 'items' => []];
        }

        $run_id = (int) $run[0]['id'];
        $items = $this->mymodel->selectWithQuery("
            SELECT *
            FROM kpi_run_items
            WHERE kpi_run_id = '$run_id'
            ORDER BY sort_order ASC, id ASC
        ");

        return [
            'run' => $run[0],
            'items' => $items,
        ];
    }

    private function get_saved_run_snapshot_map($month, $employee_ids)
    {
        $employee_ids = array_values(array_filter(array_map('intval', (array) $employee_ids), function ($id) {
            return $id > 0;
        }));
        if (empty($employee_ids)) {
            return [];
        }

        $period_type = $this->period_type_from_key($month);
        $period_type_esc = $this->db->escape_str($period_type);
        $month_esc = $this->db->escape_str((string) $month);

        $runs = $this->mymodel->selectWithQuery("
            SELECT *
            FROM kpi_runs
            WHERE month_key = '$month_esc'
                AND period_type = '$period_type_esc'
                AND employee_id IN (" . implode(',', $employee_ids) . ")
        ");

        $map = [];
        $run_ids = [];
        $run_employee_map = [];
        foreach ($runs as $run) {
            $employee_id = (int) ($run['employee_id'] ?? 0);
            $run_id = (int) ($run['id'] ?? 0);
            if ($employee_id <= 0 || $run_id <= 0) {
                continue;
            }
            $map[$employee_id] = [
                'run' => $run,
                'items' => [],
            ];
            $run_ids[] = $run_id;
            $run_employee_map[$run_id] = $employee_id;
        }

        if (!empty($run_ids)) {
            $items = $this->mymodel->selectWithQuery("
                SELECT *
                FROM kpi_run_items
                WHERE kpi_run_id IN (" . implode(',', array_map('intval', $run_ids)) . ")
                ORDER BY sort_order ASC, id ASC
            ");
            foreach ($items as $item) {
                $run_id = (int) ($item['kpi_run_id'] ?? 0);
                $employee_id = (int) ($run_employee_map[$run_id] ?? 0);
                if ($run_id <= 0 || $employee_id <= 0 || !isset($map[$employee_id])) {
                    continue;
                }
                $map[$employee_id]['items'][] = $item;
            }
        }

        foreach ($employee_ids as $employee_id) {
            if (!isset($map[$employee_id])) {
                $map[$employee_id] = ['run' => null, 'items' => []];
            }
        }

        return $map;
    }

    private function get_effective_saved_run_snapshot($month, $employee_id, $position_id, $kpi_rows = [])
    {
        $month = trim((string) $month);
        $employee_id = (int) $employee_id;
        $position_id = (int) $position_id;

        // Carryover logic only applies to CRM monthly mode. Quarterly mode never carries over.
        if ($position_id !== 28 || $this->is_quarter_key($month) || !is_array($kpi_rows) || empty($kpi_rows)) {
            return $this->get_saved_run_snapshot($month, $employee_id);
        }

        $source_month_key = '';
        foreach ($kpi_rows as $row) {
            $template_month_key = trim((string) ($row['template_month_key'] ?? ''));
            $duration_months = $this->extract_duration_span_months((string) ($row['durasi'] ?? '1 Bulan'));
            if ($duration_months <= 1 || !preg_match('/^\d{4}-\d{2}$/', $template_month_key)) {
                continue;
            }

            if ($source_month_key === '' || $this->diff_month_keys($template_month_key, $source_month_key) < 0) {
                $source_month_key = $template_month_key;
            }
        }

        if ($source_month_key !== '' && $source_month_key !== $month) {
            $source_snapshot = $this->get_saved_run_snapshot($source_month_key, $employee_id);
            if (!empty($source_snapshot['run'])) {
                return $source_snapshot;
            }
        }

        return $this->get_saved_run_snapshot($month, $employee_id);
    }

    private function apply_saved_capaian_to_rows($kpi_rows, $metrics, $saved_items, $is_locked = false)
    {
        $map_by_target = [];
        $map_by_indikator = [];
        $cycle_source_row_map = $this->get_cycle_source_row_map($kpi_rows);
        foreach ($saved_items as $item) {
            $kpi_target_id = (int) ($item['kpi_target_id'] ?? 0);
            $indikator_key = strtolower(trim((string) ($item['indikator'] ?? '')));
            $capaian = (float) ($item['capaian'] ?? 0);

            if ($kpi_target_id > 0) {
                $map_by_target[$kpi_target_id] = $capaian;
            }
            if ($indikator_key !== '' && !isset($map_by_indikator[$indikator_key])) {
                $map_by_indikator[$indikator_key] = $capaian;
            }
        }

        foreach ($kpi_rows as &$row) {
            $row_id = (int) ($row['id'] ?? 0);
            $indikator_key = strtolower(trim((string) ($row['indikator'] ?? '')));
            $metric_key = $this->sanitize_metric_key($row['metric_key'] ?? '');
            $source_row = $this->find_cycle_source_row($row, $cycle_source_row_map);
            $base_row = $source_row ?: $row;
            $has_override = isset($base_row['override_capaian']) && $base_row['override_capaian'] !== null && $base_row['override_capaian'] !== '';
            if ($has_override) {
                $live_capaian = (float) $base_row['override_capaian'];
            } elseif ($metric_key === 'manual') {
                $live_capaian = (float) ($base_row['manual_capaian'] ?? 0);
            } else {
                $live_capaian = $this->resolve_metric_value(
                    $metric_key,
                    $row['indikator'] ?? '',
                    $metrics,
                    (string) ($row['durasi'] ?? ''),
                    (string) ($row['template_month_key'] ?? $row['month_key'] ?? '')
                );
            }
            $row['live_capaian'] = $live_capaian;
            $row['final_capaian'] = null;

            if ($is_locked && $row_id > 0 && array_key_exists($row_id, $map_by_target)) {
                $row['capaian'] = (float) $map_by_target[$row_id];
                $row['final_capaian'] = (float) $map_by_target[$row_id];
            } elseif ($is_locked && $indikator_key !== '' && array_key_exists($indikator_key, $map_by_indikator)) {
                $row['capaian'] = (float) $map_by_indikator[$indikator_key];
                $row['final_capaian'] = (float) $map_by_indikator[$indikator_key];
            } else {
                $row['capaian'] = $live_capaian;
            }
        }
        unset($row);

        return $kpi_rows;
    }

    private function get_cycle_source_row_map($kpi_rows)
    {
        if (!is_array($kpi_rows) || empty($kpi_rows)) {
            return [];
        }

        $employee_id = (int) ($kpi_rows[0]['employee_id'] ?? 0);
        if ($employee_id <= 0) {
            return [];
        }

        $source_months = [];
        foreach ($kpi_rows as $row) {
            $source_month = trim((string) ($row['template_month_key'] ?? ''));
            $current_month = trim((string) ($row['month_key'] ?? ''));
            if ($source_month !== '' && $source_month !== $current_month) {
                $source_months[$source_month] = true;
            }
        }

        if (empty($source_months)) {
            return [];
        }

        $map = [];
        foreach (array_keys($source_months) as $source_month) {
            $source_rows = $this->mymodel->selectWithQuery("
                SELECT *
                FROM kpi_targets
                WHERE month_key = '" . $this->db->escape_str($source_month) . "'
                    AND period_type = 'month'
                    AND employee_id = '$employee_id'
                ORDER BY sort_order ASC, id ASC
            ");

            foreach ($source_rows as $source_row) {
                $key = $this->build_cycle_row_key($source_row, $source_month);
                $map[$key] = $source_row;
            }
        }

        return $map;
    }

    private function find_cycle_source_row($row, $cycle_source_row_map)
    {
        if (!is_array($row) || empty($cycle_source_row_map)) {
            return null;
        }

        $source_month = trim((string) ($row['template_month_key'] ?? ''));
        $current_month = trim((string) ($row['month_key'] ?? ''));
        if ($source_month === '' || $source_month === $current_month) {
            return null;
        }

        $key = $this->build_cycle_row_key($row, $source_month);
        return $cycle_source_row_map[$key] ?? null;
    }

    private function build_cycle_row_key($row, $source_month)
    {
        return trim((string) $source_month) . '|' .
            (int) ($row['sort_order'] ?? 0) . '|' .
            strtolower(trim((string) ($row['indikator'] ?? ''))) . '|' .
            $this->sanitize_metric_key($row['metric_key'] ?? $row['indikator'] ?? '');
    }

    private function lock_employee_kpi_snapshot($month, $employee_id, $campaign_ids = [])
    {
        $employee_id = (int) $employee_id;
        if ($employee_id <= 0 || empty($month) || !$this->is_valid_period_key($month)) {
            return false;
        }

        $position_id = $this->get_employee_kpi_position_id($employee_id);
        $campaign_ids = is_array($campaign_ids) ? $campaign_ids : [];
        $context = $this->resolve_employee_run_context($month, $employee_id, $position_id, $campaign_ids);
        $period = $context['period'];
        $campaign_ids = $context['campaign_ids'];
        $use_raw_window = $context['use_raw_window'];
        if ($position_id !== 7 && empty($campaign_ids)) {
            $campaign_options = $this->get_campaign_options_by_employee($employee_id, $period['start_date'], $period['until_date'], $position_id);
            $campaign_ids = array_values(array_map('intval', array_column($campaign_options, 'id')));
        }

        $period_type = $this->period_type_from_key($month);
        $rows = $this->get_or_seed_kpi_rows($month, $employee_id);
        $metrics = $this->get_kpi_metrics_from_logs(
            $period['start_date'],
            $period['until_date'],
            $employee_id,
            $campaign_ids,
            $position_id,
            $rows,
            $use_raw_window,
            $period_type
        );
        $rows = $this->apply_saved_capaian_to_rows($rows, $metrics, [], false);

        $prepared_rows = [];
        $sort_order = 1;
        foreach ($rows as $row) {
            $target = (float) ($row['target'] ?? 0);
            $bobot = (float) ($row['bobot'] ?? 0);
            $capaian = (float) ($row['capaian'] ?? 0);
            $prepared_rows[] = [
                'kpi_target_id' => (int) ($row['id'] ?? 0),
                'indikator' => (string) ($row['indikator'] ?? ''),
                'target' => $target,
                'bobot' => $bobot,
                'metric_key' => $this->sanitize_metric_key($row['metric_key'] ?? ''),
                'dimensi' => (string) ($row['durasi'] ?? '1 Bulan'),
                'capaian' => $capaian,
                'score' => $target > 0 ? ($capaian / $target) * $bobot : 0,
                'sort_order' => $sort_order++,
            ];
        }

        $this->persist_run_snapshot(
            $month,
            $employee_id,
            $period,
            $metrics,
            $prepared_rows,
            $metrics['grouped_campaign'] ?? [],
            $position_id,
            $use_raw_window
        );

        $period_type = $this->period_type_from_key($month);
        $run = $this->mymodel->selectWithQuery("
            SELECT id
            FROM kpi_runs
            WHERE month_key = '" . $this->db->escape_str($month) . "'
                AND period_type = '" . $this->db->escape_str($period_type) . "'
                AND employee_id = '" . $employee_id . "'
            LIMIT 1
        ");
        if (empty($run)) {
            return false;
        }

        $this->db->where('id', (int) $run[0]['id']);
        return (bool) $this->db->update('kpi_runs', [
            'is_locked' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
            'locked_at' => date('Y-m-d H:i:s'),
            'locked_by' => (int) ($_SESSION['user']['id'] ?? 0),
        ]);
    }

    private function get_or_seed_template_rows($month, $position_id, $persist = true)
    {
        $position_id = (int) $position_id;
        if ($position_id <= 0) {
            $position_id = 7;
        }
        $is_kol_position = $this->is_kol_specialist_position_id($position_id);
        $month = $this->normalize_period_key_for_position($month, $position_id);
        $period_type = $is_kol_position ? 'month' : 'quarter';
        $period_type_esc = $this->db->escape_str($period_type);
        $month = $this->db->escape_str($month);
        $rows = $this->mymodel->selectWithQuery("
            SELECT *
            FROM kpi_templates
            WHERE month_key = '$month'
                AND period_type = '$period_type_esc'
                AND position_id = '$position_id'
            ORDER BY sort_order ASC, id ASC
        ");

        if (!empty($rows)) {
            return $rows;
        }

        $previous_periods = $this->mymodel->selectWithQuery("
            SELECT month_key
            FROM kpi_templates
            WHERE position_id = '$position_id'
              AND period_type = '$period_type_esc'
              AND month_key < '$month'
            GROUP BY month_key
            ORDER BY month_key DESC
        ");

        foreach ($previous_periods as $previous_period) {
            $prev_key = $this->db->escape_str((string) ($previous_period['month_key'] ?? ''));
            if ($prev_key === '') {
                continue;
            }

            $prev_rows = $this->mymodel->selectWithQuery("
                SELECT *
                FROM kpi_templates
                WHERE month_key = '$prev_key'
                  AND period_type = '$period_type_esc'
                  AND position_id = '$position_id'
                ORDER BY sort_order ASC, id ASC
            ");

            if (!empty($prev_rows)) {
                $now = date('Y-m-d H:i:s');
                $seeded = [];
                foreach ($prev_rows as $prev_row) {
                    $seed_row = [
                        'month_key' => $month,
                        'period_type' => $period_type,
                        'position_id' => $position_id,
                        'indikator' => trim((string) ($prev_row['indikator'] ?? 'Indikator')),
                        'target' => (float) ($prev_row['target'] ?? 0),
                        'bobot' => (float) ($prev_row['bobot'] ?? 0),
                        'metric_key' => $this->sanitize_metric_key($prev_row['metric_key'] ?? $prev_row['indikator'] ?? ''),
                        'dimensi' => trim((string) ($prev_row['dimensi'] ?? ($is_kol_position ? '1 Bulan' : '3 Bulan'))),
                        'keterangan' => trim((string) ($prev_row['keterangan'] ?? '')),
                        'sort_order' => (int) ($prev_row['sort_order'] ?? 0),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if ($persist) {
                        $this->db->insert('kpi_templates', $seed_row);
                    }
                    $seeded[] = $seed_row;
                }

                if (!$persist) {
                    return $seeded;
                }

                return $this->mymodel->selectWithQuery("
                    SELECT *
                    FROM kpi_templates
                    WHERE month_key = '$month'
                        AND period_type = '$period_type_esc'
                        AND position_id = '$position_id'
                    ORDER BY sort_order ASC, id ASC
                ");
            }
        }

        $now = date('Y-m-d H:i:s');
        if ($this->is_source_kpi_position_id($position_id)) {
            $defaults = [
                [
                    'month_key' => $month,
                    'period_type' => $period_type,
                    'position_id' => $position_id,
                    'indikator' => 'Views',
                    'target' => $is_kol_position ? 2000000 : 6000000,
                    'bobot' => 70,
                    'metric_key' => 'total_views',
                    'dimensi' => $is_kol_position ? '1 Bulan' : '3 Bulan',
                    'keterangan' => '',
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'month_key' => $month,
                    'period_type' => $period_type,
                    'position_id' => $position_id,
                    'indikator' => 'FYP',
                    'target' => $is_kol_position ? 4 : 12,
                    'bobot' => 30,
                    'metric_key' => 'total_fyp',
                    'dimensi' => $is_kol_position ? '1 Bulan' : '3 Bulan',
                    'keterangan' => '',
                    'sort_order' => 2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];
        } elseif ($position_id === 28) {
            $defaults = [
                [
                    'month_key' => $month,
                    'period_type' => $period_type,
                    'position_id' => $position_id,
                    'indikator' => 'Create Content - Official',
                    'target' => 36,
                    'bobot' => 40,
                    'metric_key' => 'manual',
                    'dimensi' => '3 Bulan',
                    'keterangan' => "Jumlah konten yang berhasil di upload, dengan ketentuan berikut:\n\n1. Video Edukasi\n2. Video Menjawab Keraguan Customer",
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'month_key' => $month,
                    'period_type' => $period_type,
                    'position_id' => $position_id,
                    'indikator' => 'Create Campaign',
                    'target' => 3,
                    'bobot' => 20,
                    'metric_key' => 'manual',
                    'dimensi' => '3 Bulan',
                    'keterangan' => 'Campaign yang berimpact, terhitung untuk campaign-campaign yang memiliki engage 80%',
                    'sort_order' => 2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'month_key' => $month,
                    'period_type' => $period_type,
                    'position_id' => $position_id,
                    'indikator' => 'Customer Report',
                    'target' => 1,
                    'bobot' => 40,
                    'metric_key' => 'manual',
                    'dimensi' => '3 Bulan',
                    'keterangan' => 'S&K untuk sampel adalah 200 orang, data survei harus terus di-upgrade sehingga setiap 3 bulan sekali perlu ada data yang dilaporkan (sesuaikan dengan kebutuhan marketing)',
                    'sort_order' => 3,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];
        } else {
            $defaults = [
                [
                    'month_key' => $month,
                    'period_type' => $period_type,
                    'position_id' => $position_id,
                    'indikator' => 'KPI Utama',
                    'target' => 100,
                    'bobot' => 100,
                    'metric_key' => 'manual',
                    'dimensi' => '3 Bulan',
                    'keterangan' => '',
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];
        }

        if (!$persist) {
            return $defaults;
        }

        foreach ($defaults as $row) {
            $this->db->insert('kpi_templates', $row);
        }

        return $this->mymodel->selectWithQuery("
            SELECT *
            FROM kpi_templates
            WHERE month_key = '$month'
                AND period_type = '$period_type_esc'
                AND position_id = '$position_id'
            ORDER BY sort_order ASC, id ASC
        ");
    }

    private function get_or_seed_kpi_rows($month, $employee_id)
    {
        $employee_id = (int) $employee_id;
        $position_id = $this->get_employee_kpi_position_id($employee_id);
        $month = $this->normalize_period_key_for_position($month, $position_id);
        $period_type = $this->is_kol_specialist_position_id($position_id) ? 'month' : 'quarter';
        $period_type_esc = $this->db->escape_str($period_type);
        $month = $this->db->escape_str($month);

        $rows = $this->mymodel->selectWithQuery("SELECT * FROM kpi_targets WHERE month_key = '$month' AND period_type = '$period_type_esc' AND employee_id = '$employee_id' ORDER BY sort_order ASC, id ASC");

        if (!empty($rows)) {
            return $this->backfill_kpi_target_template_month_keys($month, $employee_id, $rows);
        }

        $now = date('Y-m-d H:i:s');
        if ($position_id <= 0) {
            $position_id = 7;
        }
        $templates = $this->get_or_seed_template_rows($month, $position_id);
        foreach ($templates as $index => $tpl) {
            $row = [
                'month_key' => $month,
                'period_type' => $period_type,
                'template_month_key' => (string) ($tpl['month_key'] ?? $month),
                'employee_id' => $employee_id,
                'indikator' => trim((string) ($tpl['indikator'] ?? 'Indikator')),
                'target' => (float) ($tpl['target'] ?? 0),
                'bobot' => (float) ($tpl['bobot'] ?? 0),
                'metric_key' => $this->sanitize_metric_key($tpl['metric_key'] ?? $tpl['indikator'] ?? ''),
                'manual_capaian' => 0,
                'durasi' => trim((string) ($tpl['dimensi'] ?? '1 Bulan')),
                'sort_order' => (int) ($tpl['sort_order'] ?? ($index + 1)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $this->db->insert('kpi_targets', $row);
        }

        return $this->mymodel->selectWithQuery("SELECT * FROM kpi_targets WHERE month_key = '$month' AND period_type = '$period_type_esc' AND employee_id = '$employee_id' ORDER BY sort_order ASC, id ASC");
    }

    private function get_or_seed_kpi_rows_map($month, $employees)
    {
        $employee_map = [];
        foreach ((array) $employees as $employee) {
            $employee_id = (int) ($employee['id'] ?? 0);
            if ($employee_id <= 0) {
                continue;
            }
            $employee_map[$employee_id] = $employee;
        }
        if (empty($employee_map)) {
            return [];
        }
        $rows_map = [];
        foreach ($employee_map as $employee_id => $employee) {
            $rows_map[$employee_id] = $this->get_or_seed_kpi_rows($month, $employee_id);
        }

        return $rows_map;
    }

    private function seed_kpi_rows_for_employee($month, $employee_id, $position_id)
    {
        $month = $this->normalize_period_key_for_position($month, $position_id);
        $period_type = $this->is_kol_specialist_position_id($position_id) ? 'month' : 'quarter';
        $period_type_esc = $this->db->escape_str($period_type);
        $month_esc = $this->db->escape_str($month);
        $employee_id = (int) $employee_id;
        $position_id = (int) $position_id;
        if ($position_id <= 0) {
            $position_id = 7;
        }

        $now = date('Y-m-d H:i:s');
        $templates = $this->get_or_seed_template_rows($month, $position_id);
        foreach ($templates as $index => $tpl) {
            $row = [
                'month_key' => $month,
                'period_type' => $period_type,
                'template_month_key' => (string) ($tpl['month_key'] ?? $month),
                'employee_id' => $employee_id,
                'indikator' => trim((string) ($tpl['indikator'] ?? 'Indikator')),
                'target' => (float) ($tpl['target'] ?? 0),
                'bobot' => (float) ($tpl['bobot'] ?? 0),
                'metric_key' => $this->sanitize_metric_key($tpl['metric_key'] ?? $tpl['indikator'] ?? ''),
                'manual_capaian' => 0,
                'durasi' => trim((string) ($tpl['dimensi'] ?? '1 Bulan')),
                'sort_order' => (int) ($tpl['sort_order'] ?? ($index + 1)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $this->db->insert('kpi_targets', $row);
        }

        return $this->mymodel->selectWithQuery("
            SELECT *
            FROM kpi_targets
            WHERE month_key = '$month_esc'
                AND period_type = '$period_type_esc'
                AND employee_id = '$employee_id'
            ORDER BY sort_order ASC, id ASC
        ");
    }

    private function backfill_kpi_target_template_month_keys($month, $employee_id, $rows)
    {
        $employee_id = (int) $employee_id;
        $needs_backfill = false;
        foreach ($rows as $row) {
            if (trim((string) ($row['template_month_key'] ?? '')) === '') {
                $needs_backfill = true;
                break;
            }
        }

        if (!$needs_backfill) {
            return $rows;
        }

        $position_id = $this->get_employee_kpi_position_id($employee_id);
        if ($position_id <= 0) {
            $position_id = 7;
        }
        $month = $this->normalize_period_key_for_position($month, $position_id);
        $templates = $this->get_or_seed_template_rows($month, $position_id);
        $template_map = [];
        foreach ($templates as $tpl) {
            $template_key = strtolower(trim((string) ($tpl['indikator'] ?? ''))) . '|' .
                $this->sanitize_metric_key($tpl['metric_key'] ?? $tpl['indikator'] ?? '') . '|' .
                (int) ($tpl['sort_order'] ?? 0);
            $template_map[$template_key] = (string) ($tpl['month_key'] ?? $month);
        }

        foreach ($rows as $row) {
            $current_template_month = trim((string) ($row['template_month_key'] ?? ''));
            if ($current_template_month !== '') {
                continue;
            }

            $template_key = strtolower(trim((string) ($row['indikator'] ?? ''))) . '|' .
                $this->sanitize_metric_key($row['metric_key'] ?? $row['indikator'] ?? '') . '|' .
                (int) ($row['sort_order'] ?? 0);
            $template_month_key = $template_map[$template_key] ?? $month;

            $this->db->where('id', (int) ($row['id'] ?? 0));
            $this->db->update('kpi_targets', [
                'template_month_key' => $template_month_key,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $period_type = $this->is_kol_specialist_position_id($position_id) ? 'month' : 'quarter';
        return $this->mymodel->selectWithQuery("SELECT * FROM kpi_targets WHERE month_key = '" . $this->db->escape_str($month) . "' AND period_type = '" . $this->db->escape_str($period_type) . "' AND employee_id = '$employee_id' ORDER BY sort_order ASC, id ASC");
    }

    private function duration_covers_month($source_month, $duration_label, $target_month)
    {
        $source_month = trim((string) $source_month);
        $target_month = trim((string) $target_month);
        if (!preg_match('/^\d{4}-\d{2}$/', $source_month) || !preg_match('/^\d{4}-\d{2}$/', $target_month)) {
            return false;
        }

        $month_diff = $this->diff_month_keys($source_month, $target_month);
        if ($month_diff < 0) {
            return false;
        }

        $duration_months = $this->extract_duration_span_months($duration_label);
        return $month_diff < $duration_months;
    }

    private function diff_month_keys($start_month, $end_month)
    {
        $start_parts = explode('-', (string) $start_month);
        $end_parts = explode('-', (string) $end_month);
        if (count($start_parts) !== 2 || count($end_parts) !== 2) {
            return 0;
        }

        $start_year = (int) ($start_parts[0] ?? 0);
        $start_value = (int) ($start_parts[1] ?? 0);
        $end_year = (int) ($end_parts[0] ?? 0);
        $end_value = (int) ($end_parts[1] ?? 0);

        return (($end_year - $start_year) * 12) + ($end_value - $start_value);
    }

    private function add_months_to_key($month_key, $offset)
    {
        $month_key = trim((string) $month_key);
        $offset = (int) $offset;
        if (!preg_match('/^\d{4}-\d{2}$/', $month_key)) {
            return $month_key;
        }

        return date('Y-m', strtotime(($offset >= 0 ? '+' : '') . $offset . ' month', strtotime($month_key . '-01')));
    }

    private function get_employee_position_id($employee_id)
    {
        $employee_id = (int) $employee_id;
        if ($employee_id <= 0) {
            return 0;
        }

        $row = $this->mymodel->selectWithQuery("
            SELECT up.position_id
            FROM user_profile up
            WHERE up.user_id = '$employee_id'
            ORDER BY up.id DESC
            LIMIT 1
        ");

        return !empty($row) ? (int) ($row[0]['position_id'] ?? 0) : 0;
    }

    private function get_employee_kpi_position_id($employee_id)
    {
        $employee_id = (int) $employee_id;
        if ($employee_id <= 0) {
            return 0;
        }

        if (!$this->has_kpi_custom_position_tables()) {
            return $this->get_employee_position_id($employee_id);
        }

        $custom_row = $this->mymodel->selectWithQuery("
            SELECT custom_position_id
            FROM kpi_custom_position_members
            WHERE employee_id = '" . $employee_id . "'
            LIMIT 1
        ");
        if (!empty($custom_row[0]['custom_position_id'])) {
            return $this->encode_custom_kpi_position_id((int) $custom_row[0]['custom_position_id']);
        }

        return $this->get_employee_position_id($employee_id);
    }

    private function get_employee_ids_by_position($position_id)
    {
        $position_id = (int) $position_id;
        if ($position_id <= 0) {
            return [];
        }

        if ($this->is_custom_kpi_position_id($position_id)) {
            return $this->get_custom_kpi_position_member_ids($position_id);
        }

        $custom_filter = '';
        if ($this->has_kpi_custom_position_tables()) {
            $custom_filter = "
              AND NOT EXISTS (
                  SELECT 1
                  FROM kpi_custom_position_members kcpm
                  WHERE kcpm.employee_id = u.id
              )";
        }

        $rows = $this->mymodel->selectWithQuery("
            SELECT u.id
            FROM user u
            INNER JOIN user_profile up
                ON up.user_id = u.id
                AND up.id = (
                    SELECT MAX(up2.id)
                    FROM user_profile up2
                    WHERE up2.user_id = u.id
                )
            WHERE up.position_id = '$position_id'
              $custom_filter
        ");

        return array_values(array_filter(array_map(function ($row) {
            return (int) ($row['id'] ?? 0);
        }, $rows)));
    }

    private function get_month_period($month)
    {
        $month = $this->db->escape_str($month);
        $start_date = $month . '-01';
        $until_date = date('Y-m-t', strtotime($start_date));
        return [
            'start_date' => $start_date,
            'until_date' => $until_date,
        ];
    }

    private function current_month_key($reference = null)
    {
        return date('Y-m', $reference ? strtotime((string) $reference) : time());
    }

    private function quarter_to_first_month_key($key)
    {
        $parsed = $this->parse_period_key($key);
        if (empty($parsed) || $parsed['type'] !== 'quarter') {
            return $this->current_month_key();
        }

        $start_month = (($parsed['index'] - 1) * 3) + 1;
        return sprintf('%04d-%02d', (int) $parsed['year'], $start_month);
    }

    private function normalize_period_key_for_division($key, $division_key, $fallback = null)
    {
        $division_key = trim((string) $division_key);
        if ($division_key === 'kol_specialist') {
            $key = trim((string) $key);
            if (preg_match('/^\d{4}-\d{2}$/', $key)) {
                return $key;
            }
            if ($this->is_quarter_key($key)) {
                return $this->quarter_to_first_month_key($key);
            }
            if ($fallback !== null) {
                return $this->normalize_period_key_for_division($fallback, $division_key, null);
            }
            return $this->current_month_key();
        }

        return $this->normalize_quarter_period_key($key, $fallback);
    }

    private function normalize_period_key_for_position($key, $position_id, $fallback = null)
    {
        return $this->normalize_period_key_for_division($key, $this->is_kol_specialist_position_id($position_id) ? 'kol_specialist' : 'content_creator', $fallback);
    }

    private function normalize_quarter_period_key($key, $fallback = null)
    {
        $key = trim((string) $key);
        if ($this->is_quarter_key($key)) {
            return $key;
        }
        if (preg_match('/^(\d{4})-(\d{2})$/', $key, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            if ($month >= 1 && $month <= 12) {
                $quarter = (int) ceil($month / 3);
                return sprintf('%04d-Q%d', $year, $quarter);
            }
        }
        if ($fallback !== null) {
            if ($fallback === '') {
                return '';
            }
            $normalized_fallback = $this->normalize_quarter_period_key($fallback, null);
            if ($normalized_fallback !== '') {
                return $normalized_fallback;
            }
        }
        return $this->current_quarter_key();
    }

    private function is_valid_period_key($key)
    {
        if (!is_string($key) || $key === '') {
            return false;
        }
        return (bool) preg_match('/^\d{4}-(?:\d{2}|Q[1-4])$/', $key);
    }

    private function is_quarter_key($key)
    {
        return is_string($key) && (bool) preg_match('/^\d{4}-Q[1-4]$/', $key);
    }

    private function period_type_from_key($key)
    {
        return $this->is_quarter_key($key) ? 'quarter' : 'month';
    }

    private function parse_period_key($key)
    {
        if ($this->is_quarter_key($key)) {
            return [
                'type' => 'quarter',
                'year' => (int) substr($key, 0, 4),
                'index' => (int) substr($key, 6, 1),
            ];
        }
        if (preg_match('/^(\d{4})-(\d{2})$/', $key, $m)) {
            return [
                'type' => 'month',
                'year' => (int) $m[1],
                'index' => (int) $m[2],
            ];
        }
        return null;
    }

    private function get_quarter_period($key)
    {
        $parsed = $this->parse_period_key($key);
        if (empty($parsed) || $parsed['type'] !== 'quarter') {
            return $this->get_month_period(date('Y-m'));
        }
        $year = $parsed['year'];
        $q = $parsed['index'];
        $start_month = (($q - 1) * 3) + 1;
        $end_month = $start_month + 2;
        $start_date = sprintf('%04d-%02d-01', $year, $start_month);
        $until_date = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $end_month)));
        return [
            'start_date' => $start_date,
            'until_date' => $until_date,
        ];
    }

    private function get_period_range($key)
    {
        if ($this->is_quarter_key($key)) {
            return $this->get_quarter_period($key);
        }
        return $this->get_month_period($key);
    }

    private function get_month_keys_in_quarter($key)
    {
        $parsed = $this->parse_period_key($key);
        if (empty($parsed) || $parsed['type'] !== 'quarter') {
            return [];
        }
        $year = $parsed['year'];
        $q = $parsed['index'];
        $start_month = (($q - 1) * 3) + 1;
        $keys = [];
        for ($i = 0; $i < 3; $i++) {
            $keys[] = sprintf('%04d-%02d', $year, $start_month + $i);
        }
        return $keys;
    }

    private function add_quarters_to_key($key, $offset)
    {
        $parsed = $this->parse_period_key($key);
        if (empty($parsed) || $parsed['type'] !== 'quarter') {
            return $key;
        }
        $total = ($parsed['year'] * 4) + ($parsed['index'] - 1) + (int) $offset;
        $new_year = (int) floor($total / 4);
        $new_q = ($total % 4) + 1;
        return sprintf('%04d-Q%d', $new_year, $new_q);
    }

    private function diff_quarter_keys($a, $b)
    {
        $pa = $this->parse_period_key($a);
        $pb = $this->parse_period_key($b);
        if (empty($pa) || empty($pb) || $pa['type'] !== 'quarter' || $pb['type'] !== 'quarter') {
            return 0;
        }
        return (($pb['year'] - $pa['year']) * 4) + ($pb['index'] - $pa['index']);
    }

    private function get_quarter_keys_between($start_key, $end_key)
    {
        if (!$this->is_quarter_key($start_key) || !$this->is_quarter_key($end_key)) {
            return [];
        }
        $diff = $this->diff_quarter_keys($start_key, $end_key);
        if ($diff < 0) {
            $tmp = $start_key;
            $start_key = $end_key;
            $end_key = $tmp;
            $diff = -$diff;
        }
        $keys = [];
        for ($i = 0; $i <= $diff; $i++) {
            $keys[] = $this->add_quarters_to_key($start_key, $i);
        }
        return $keys;
    }

    private function format_period_label($key)
    {
        if ($this->is_quarter_key($key)) {
            $parsed = $this->parse_period_key($key);
            return 'Q' . $parsed['index'] . ' ' . $parsed['year'];
        }
        if (preg_match('/^\d{4}-\d{2}$/', $key)) {
            return date('M Y', strtotime($key . '-01'));
        }
        return (string) $key;
    }

    private function build_kol_quarter_employee_view($base_month, $employee, $selected_campaign_ids = [])
    {
        $employee_id = (int) ($employee['id'] ?? 0);
        if ($employee_id <= 0) {
            return $employee;
        }

        $quarter_key = $this->normalize_quarter_period_key($base_month);
        $quarter_period = $this->get_period_range($quarter_key);
        $month_keys = $this->get_month_keys_in_quarter($quarter_key);
        if (empty($month_keys)) {
            return $employee;
        }

        $selected_campaign_ids = array_values(array_filter(array_map('intval', (array) $selected_campaign_ids), function ($id) {
            return $id > 0;
        }));
        $monthly_rows = [];
        $monthly_scores = [];
        $aggregate_metrics = [
            'total_views' => 0,
            'total_fyp' => 0,
            'total_konten' => 0,
            'total_cost' => 0,
            'cpm' => 0,
        ];
        $grouped_campaign_map = [];

        foreach ($month_keys as $month_key) {
            $period = $this->get_period_range($month_key);
            $month_preset = $this->get_employee_preset($month_key, $employee_id);
            if (!empty($month_preset['date_from']) && !empty($month_preset['date_until'])) {
                $period = [
                    'start_date' => (string) $month_preset['date_from'],
                    'until_date' => (string) $month_preset['date_until'],
                ];
            }
            $campaign_options = $this->get_campaign_options_by_employee($employee_id, $period['start_date'], $period['until_date'], 3);
            $effective_campaign_ids = $selected_campaign_ids;
            if (!empty($month_preset['source_rules'])) {
                $effective_campaign_ids = array_values(array_unique(array_map('intval', array_column($month_preset['source_rules'], 'campaign_id'))));
            }
            if (empty($effective_campaign_ids) && !empty($campaign_options)) {
                $effective_campaign_ids = array_values(array_map('intval', array_column($campaign_options, 'id')));
            }

            $rows = $this->get_or_seed_kpi_rows($month_key, $employee_id);
            $metrics = $this->get_kpi_metrics_from_logs(
                $period['start_date'],
                $period['until_date'],
                $employee_id,
                $effective_campaign_ids,
                3,
                $rows,
                false,
                'month'
            );
            $saved_run = $this->get_saved_run_snapshot($month_key, $employee_id);
            $rows = $this->apply_saved_capaian_to_rows($rows, $metrics, $saved_run['items'] ?? [], (int) (($saved_run['run']['is_locked'] ?? 0) == 1 ? 1 : 0) === 1);
            $monthly_rows[] = $rows;
            $monthly_scores[] = [
                'month_key' => $month_key,
                'score' => $this->calculate_total_score($rows, $metrics),
            ];

            foreach (['total_views', 'total_fyp', 'total_konten', 'total_cost', 'cpm'] as $metric_key) {
                $aggregate_metrics[$metric_key] += (float) ($metrics[$metric_key] ?? 0);
            }
            foreach (($metrics['grouped_campaign'] ?? []) as $campaign) {
                $campaign_key = strtolower(trim((string) ($campaign['campaign_title'] ?? '')));
                if ($campaign_key === '') {
                    $campaign_key = 'campaign-tanpa-judul';
                }
                if (!isset($grouped_campaign_map[$campaign_key])) {
                    $grouped_campaign_map[$campaign_key] = [
                        'id_campaign' => (int) ($campaign['id_campaign'] ?? 0),
                        'campaign_title' => (string) ($campaign['campaign_title'] ?? 'Campaign tanpa judul'),
                        'total_konten' => 0,
                        'total_fyp' => 0,
                        'total_views' => 0,
                    ];
                }
                $grouped_campaign_map[$campaign_key]['total_konten'] += (float) ($campaign['total_konten'] ?? 0);
                $grouped_campaign_map[$campaign_key]['total_fyp'] += (float) ($campaign['total_fyp'] ?? 0);
                $grouped_campaign_map[$campaign_key]['total_views'] += (float) ($campaign['total_views'] ?? 0);
            }
        }

        $month_count = max(1, count($monthly_rows));
        $base_rows = $monthly_rows[0] ?? ($employee['kpi_rows'] ?? []);
        foreach ($base_rows as $index => &$row) {
            $capaian_sum = 0;
            $sample_count = 0;
            foreach ($monthly_rows as $rows) {
                if (!isset($rows[$index])) {
                    continue;
                }
                $capaian_sum += (float) ($rows[$index]['capaian'] ?? 0);
                $sample_count++;
            }
            $row['capaian'] = $sample_count > 0 ? ($capaian_sum / $sample_count) : 0;
        }
        unset($row);

        foreach ($aggregate_metrics as $metric_key => $value) {
            $aggregate_metrics[$metric_key] = $value / $month_count;
        }
        foreach ($grouped_campaign_map as &$campaign) {
            $campaign['total_konten'] = $campaign['total_konten'] / $month_count;
            $campaign['total_fyp'] = $campaign['total_fyp'] / $month_count;
            $campaign['total_views'] = $campaign['total_views'] / $month_count;
        }
        unset($campaign);

        $employee['kpi_rows'] = $base_rows;
        $employee['metrics'] = array_merge($aggregate_metrics, [
            'grouped_campaign' => array_values($grouped_campaign_map),
            'start_date' => $quarter_period['start_date'],
            'until_date' => $quarter_period['until_date'],
        ]);
        $employee['campaigns'] = [];
        $employee['selected_campaign_ids'] = $selected_campaign_ids;
        $employee['is_virtual_quarter'] = 1;
        $employee['virtual_period_label'] = 'Rata-rata ' . $this->format_period_label($quarter_key);
        $employee['monthly_scores'] = $monthly_scores;

        return $employee;
    }

    private function current_quarter_key($reference = null)
    {
        $reference = $reference ?: date('Y-m-d');
        $year = (int) date('Y', strtotime($reference));
        $month = (int) date('n', strtotime($reference));
        $q = (int) ceil($month / 3);
        return sprintf('%04d-Q%d', $year, $q);
    }

    private function get_campaign_period_window($start_date, $until_date, $include_previous_month = true)
    {
        $window_start = $start_date;
        if ($include_previous_month) {
            $start_ts = strtotime($start_date);
            $window_start = date('Y-m-01', strtotime('-1 month', $start_ts));
        }
        return [
            'start' => $window_start,
            'end' => $until_date,
        ];
    }

    private function should_include_previous_month($position_id)
    {
        return !$this->is_kol_specialist_position_id($position_id);
    }

    private function calculate_total_score($kpi_rows, $metrics)
    {
        $total_score = 0;
        foreach ($kpi_rows as $row) {
            $target = (float) ($row['target'] ?? 0);
            $bobot = (float) ($row['bobot'] ?? 0);
            $capaian = isset($row['capaian'])
                ? (float) $row['capaian']
                : $this->resolve_metric_value(
                    $row['metric_key'] ?? '',
                    $row['indikator'] ?? '',
                    $metrics,
                    (string) ($row['durasi'] ?? ''),
                    (string) ($row['template_month_key'] ?? $row['month_key'] ?? '')
                );

            $score = $target > 0 ? ($capaian / $target) * $bobot : 0;
            $total_score += $score;
        }

        return $total_score;
    }

    private function resolve_metric_value($metric_key, $indikator, $metrics, $durasi = '', $template_month_key = '')
    {
        $metric_key = $this->sanitize_metric_key($metric_key);
        if ($metric_key === '') {
            $metric_key = $this->sanitize_metric_key($indikator);
        }

        if ($metric_key === 'total_views') return (float) ($metrics['total_views'] ?? 0);
        if ($metric_key === 'total_fyp') return (float) ($metrics['total_fyp'] ?? 0);
        if ($metric_key === 'total_konten') return (float) ($metrics['total_konten'] ?? 0);
        if ($metric_key === 'total_cost') return (float) ($metrics['total_cost'] ?? 0);
        if ($metric_key === 'cpm') return (float) ($metrics['cpm'] ?? 0);
        if ($metric_key === 'crm_content_official') {
            $months = $this->extract_duration_months($durasi);
            if ($months > 1) {
                $fixed_value = $this->resolve_crm_fixed_duration_value($metrics, $template_month_key, $months);
                if ($fixed_value !== null) {
                    return $fixed_value;
                }
                $map = $metrics['crm_content_official_by_duration'] ?? [];
                if (isset($map[$months])) {
                    return (float) $map[$months];
                }
            }
            return (float) ($metrics['crm_content_official'] ?? 0);
        }
        return 0;
    }

    private function extract_duration_months($durasi)
    {
        return $this->extract_duration_span_months($durasi);
    }

    private function extract_duration_span_months($durasi)
    {
        $text = trim((string) $durasi);
        if ($text === '') {
            return 1;
        }

        $amount = 1;
        $unit = 'bulan';
        if (preg_match('/(\d+)\s*(.*)/iu', $text, $matches)) {
            $amount = (int) ($matches[1] ?? 1);
            $unit = strtolower(trim((string) ($matches[2] ?? 'bulan')));
        }

        if ($amount <= 0) {
            $amount = 1;
        }

        if (strpos($unit, 'tahun') !== false) {
            return $amount * 12;
        }

        if (strpos($unit, 'bulan') !== false) {
            return $amount;
        }

        return 1;
    }

    private function resolve_crm_fixed_duration_value($metrics, $template_month_key, $duration_months)
    {
        $template_month_key = trim((string) $template_month_key);
        $duration_months = (int) $duration_months;
        if ($duration_months <= 1 || !preg_match('/^\d{4}-\d{2}$/', $template_month_key)) {
            return null;
        }

        $count_map = isset($metrics['crm_content_count_map']) && is_array($metrics['crm_content_count_map'])
            ? $metrics['crm_content_count_map']
            : [];
        if (empty($count_map)) {
            return null;
        }

        $sum = 0;
        for ($offset = 0; $offset < $duration_months; $offset++) {
            $month_key = $this->add_months_to_key($template_month_key, $offset);
            $sum += (float) ($count_map[$month_key] ?? 0);
        }

        return $sum;
    }

    private function sanitize_metric_key($value)
    {
        $key = strtolower(trim((string) $value));
        $map = [
            'views' => 'total_views',
            'view' => 'total_views',
            'total_views' => 'total_views',
            'fyp' => 'total_fyp',
            'total_fyp' => 'total_fyp',
            'total konten' => 'total_konten',
            'konten' => 'total_konten',
            'content' => 'total_konten',
            'total_konten' => 'total_konten',
            'cost' => 'total_cost',
            'total cost' => 'total_cost',
            'total_cost' => 'total_cost',
            'cpm' => 'cpm',
            'manual' => 'manual',
            'input manual' => 'manual',
            'crm content official' => 'manual',
            'crm_content_official' => 'manual',
        ];

        return $map[$key] ?? '';
    }

    private function normalize_number($value)
    {
        $clean_numeric = preg_replace('/[^0-9.,-]/', '', (string) $value);
        $clean_numeric = str_replace('.', '', $clean_numeric);
        $clean_numeric = str_replace(',', '.', $clean_numeric);
        return is_numeric($clean_numeric) ? (float) $clean_numeric : 0;
    }

    private function normalize_duration_label($value)
    {
        if (is_array($value)) {
            $amount = $value['amount'] ?? $value['value'] ?? 1;
            $unit = $value['unit'] ?? 'Bulan';
        } else {
            $text = trim((string) $value);
            preg_match('/(\d+)\s*(.*)/u', $text, $matches);
            $amount = $matches[1] ?? 1;
            $unit = $matches[2] ?? 'Bulan';
        }

        $amount = (int) $this->normalize_number($amount);
        if ($amount <= 0) {
            $amount = 1;
        }

        $unit = ucfirst(strtolower(trim((string) $unit)));
        if (!in_array($unit, ['Hari', 'Minggu', 'Bulan', 'Tahun'], true)) {
            $unit = 'Bulan';
        }

        return $amount . ' ' . $unit;
    }

    private function get_kpi_metrics_from_logs($start_date, $until_date, $employee_id, $campaign_ids, $position_id = null, $kpi_rows = [], $use_raw_window = false, $period_type = 'month')
    {
        $start_date = $this->db->escape_str($start_date);
        $until_date = $this->db->escape_str($until_date);
        $employee_id = (int) $employee_id;
        $fyp_views = (int) $this->fyp_views;
        $fyp_percentage = (int) $this->fyp_percentage;
        $position_id = $position_id === null ? $this->get_employee_kpi_position_id($employee_id) : (int) $position_id;
        if ($position_id === 28) {
            return $this->get_crm_kpi_metrics($start_date, $until_date, $employee_id, $kpi_rows, $period_type);
        }
        if ($this->is_kol_specialist_position_id($position_id)) {
            $source_rule_map = [];
            $posted_start_date = (string) $start_date;
            $posted_until_date = (string) $until_date;
            $growth_start_date = (string) $start_date;
            $growth_until_date = (string) $until_date;
            if ($employee_id > 0) {
                $preset = $this->get_employee_preset(substr((string) $start_date, 0, 7), $employee_id);
                if (!empty($preset['source_rules'])) {
                    $source_rule_map = $this->build_source_rule_map($preset['source_rules']);
                }
                if (!empty($preset['date_from']) && !empty($preset['date_until'])) {
                    $posted_start_date = (string) $preset['date_from'];
                    $posted_until_date = (string) $preset['date_until'];
                }
                if (!empty($preset['growth_date_from']) && !empty($preset['growth_date_until'])) {
                    $growth_start_date = (string) $preset['growth_date_from'];
                    $growth_until_date = (string) $preset['growth_date_until'];
                }
            }
            return $this->get_kol_specialist_kpi_metrics(
                $posted_start_date,
                $posted_until_date,
                $employee_id,
                $campaign_ids,
                $source_rule_map,
                $growth_start_date,
                $growth_until_date
            );
        }
        if ($position_id !== 7) {
            return [
                'total_views' => 0,
                'total_fyp' => 0,
                'total_konten' => 0,
                'total_cost' => 0,
                'cpm' => 0,
                'grouped_campaign' => [],
                'start_date' => $start_date,
                'until_date' => $until_date,
            ];
        }
        $window = $this->get_campaign_period_window(
            $start_date,
            $until_date,
            $use_raw_window ? false : $this->should_include_previous_month($position_id)
        );
        $campaign_window_start = $this->db->escape_str($window['start']);
        $campaign_window_end = $this->db->escape_str($window['end']);
        $endorse_status_filter = '';
        if ($this->is_kol_specialist_position_id($position_id)) {
            $endorse_status_filter = " AND e.status_endorse = 'Posted Content'";
        }

        $employee_filter = '';
        if ($employee_id > 0) {
            $employee_filter = " AND e.created_by = '$employee_id'";
        }

        if (!empty($campaign_ids)) {
            $campaign_ids = array_values(array_filter(array_map('intval', $campaign_ids), function ($id) {
                return $id > 0;
            }));
        }

        $campaign_filter = '';
        if (!empty($campaign_ids)) {
            $campaign_filter = " AND e.id_campaign IN (" . implode(',', $campaign_ids) . ")";
        }

        $grouped = $this->mymodel->selectWithQuery("
            SELECT 
                ec.id_campaign,
                COALESCE(NULLIF(TRIM(c.title), ''), '') AS campaign_title,
                COUNT(DISTINCT CASE WHEN COALESCE(c.kpi_include_total_konten, 1) = 1 THEN ec.id_endorse END) AS total_konten,
                COUNT(DISTINCT CASE WHEN COALESCE(c.kpi_include_fyp, 1) = 1 THEN fyp.id_endorse END) AS total_fyp,
                SUM(CASE WHEN COALESCE(c.kpi_include_views, 1) = 1 THEN COALESCE(v.views_until, 0) - COALESCE(v.views_before, 0) ELSE 0 END) AS total_views,
                SUM(CASE WHEN COALESCE(c.kpi_include_total_cost, 1) = 1 THEN COALESCE(e.total_cost, 0) ELSE 0 END) AS total_cost,
                SUM(CASE WHEN COALESCE(c.kpi_include_cpm, 1) = 1 THEN COALESCE(v.views_until, 0) - COALESCE(v.views_before, 0) ELSE 0 END) AS cpm_views,
                SUM(CASE WHEN COALESCE(c.kpi_include_cpm, 1) = 1 THEN COALESCE(e.total_cost, 0) ELSE 0 END) AS cpm_cost,
                GROUP_CONCAT(
                    DISTINCT fyp.first_fyp_date
                    ORDER BY fyp.first_fyp_date
                    SEPARATOR ', '
                ) AS fyp_dates,
                GROUP_CONCAT(
                    DISTINCT CASE 
                        WHEN fyp.id_endorse IS NOT NULL THEN e.link_upload 
                    END
                    ORDER BY e.link_upload
                    SEPARATOR ', '
                ) AS fyp_links
            FROM (
                SELECT e.id AS id_endorse, e.id_campaign
                FROM endorse e
                INNER JOIN endorse_campaign ecf ON ecf.id = e.id_campaign
                WHERE 1=1
                $employee_filter
                $campaign_filter
                AND e.status = 'Aktif'
                $endorse_status_filter
                AND ecf.status = 'Aktif'
                AND DATE(COALESCE(ecf.start_at, '1900-01-01')) <= '$campaign_window_end'
                AND DATE(COALESCE(ecf.until_at, '2999-12-31')) >= '$campaign_window_start'
            ) ec
            LEFT JOIN endorse_campaign c 
                ON c.id = ec.id_campaign
            LEFT JOIN endorse e
                ON e.id = ec.id_endorse
            LEFT JOIN (
                SELECT 
                    l.id_endorse,
                    MAX(CASE WHEN DATE(l.date) <= '$until_date' THEN l.views_after END) AS views_until,
                    MAX(CASE WHEN DATE(l.date) < '$start_date' THEN l.views_after END) AS views_before
                FROM endorse_logs l
                GROUP BY l.id_endorse
            ) v
                ON v.id_endorse = ec.id_endorse
            LEFT JOIN (
                SELECT 
                    id_endorse,
                    MIN(DATE(date)) AS first_fyp_date
                FROM (
                    SELECT
                        l.id_endorse,
                        l.date
                    FROM endorse_logs l
                    INNER JOIN endorse ef
                        ON ef.id = l.id_endorse
                    LEFT JOIN influencer inf
                        ON inf.id = ef.influencer
                    WHERE COALESCE(l.views_after, 0) >= $fyp_views
                        AND COALESCE(inf.follower, 0) > 0
                        AND COALESCE(l.views_after, 0) >= FLOOR(COALESCE(inf.follower, 0) * $fyp_percentage / 100)
                ) fyp_logs
                GROUP BY id_endorse
            ) fyp
                ON fyp.id_endorse = ec.id_endorse
                AND fyp.first_fyp_date BETWEEN '$start_date' AND '$until_date'
            GROUP BY 
                ec.id_campaign, campaign_title
            ORDER BY 
                campaign_title ASC
        ");

        $total_views = 0;
        $total_fyp = 0;
        $total_konten = 0;
        $total_cost = 0;
        $total_cpm_views = 0;
        $total_cpm_cost = 0;
        foreach ($grouped as $item) {
            $total_views += (float) ($item['total_views'] ?? 0);
            $total_fyp += (int) ($item['total_fyp'] ?? 0);
            $total_konten += (int) ($item['total_konten'] ?? 0);
            $total_cost += (float) ($item['total_cost'] ?? 0);
            $total_cpm_views += (float) ($item['cpm_views'] ?? 0);
            $total_cpm_cost += (float) ($item['cpm_cost'] ?? 0);
        }
        $cpm = $total_cpm_views > 0 ? ($total_cpm_cost / $total_cpm_views) * 1000 : 0;

        return [
            'total_views' => $total_views,
            'total_fyp' => $total_fyp,
            'total_konten' => $total_konten,
            'total_cost' => $total_cost,
            'cpm' => $cpm,
            'grouped_campaign' => $grouped,
            'start_date' => $start_date,
            'until_date' => $until_date,
            'query' => $this->db->last_query()
        ];
    }

    private function get_kol_specialist_kpi_metrics($start_date, $until_date, $employee_id, $campaign_ids = [], $source_rule_map = [], $growth_start_date = null, $growth_until_date = null)
    {
        $start_date = $this->db->escape_str($start_date);
        $until_date = $this->db->escape_str($until_date);
        $growth_start_date = $this->db->escape_str($growth_start_date ?: $start_date);
        $growth_until_date = $this->db->escape_str($growth_until_date ?: $until_date);
        $employee_id = (int) $employee_id;
        $fyp_views = (int) $this->fyp_views;
        $fyp_percentage = (int) $this->fyp_percentage;
        $campaign_filter = '';
        if (!empty($campaign_ids)) {
            $campaign_ids = array_values(array_filter(array_map('intval', (array) $campaign_ids), function ($id) {
                return $id > 0;
            }));
            if (!empty($campaign_ids)) {
                $campaign_filter = " AND e.id_campaign IN (" . implode(',', $campaign_ids) . ")";
            }
        }
        $stats_until_date = $growth_until_date;

        $grouped = $this->mymodel->selectWithQuery("
            SELECT
                e.id_campaign AS id_campaign,
                ec.title AS campaign_title,
                COUNT(DISTINCT CASE WHEN COALESCE(ec.kpi_include_total_konten, 1) = 1 THEN e.id END) AS total_konten,
                COUNT(DISTINCT CASE WHEN COALESCE(ec.kpi_include_fyp, 1) = 1 AND fyp.id_endorse IS NOT NULL THEN e.id END) AS total_fyp,
                SUM(CASE WHEN COALESCE(ec.kpi_include_views, 1) = 1 THEN COALESCE(v.views_diff, 0) ELSE 0 END) AS total_views,
                SUM(CASE WHEN COALESCE(ec.kpi_include_total_cost, 1) = 1 THEN COALESCE(e.total_cost, 0) ELSE 0 END) AS total_cost,
                SUM(CASE WHEN COALESCE(ec.kpi_include_cpm, 1) = 1 THEN COALESCE(v.views_diff, 0) ELSE 0 END) AS cpm_views,
                SUM(CASE WHEN COALESCE(ec.kpi_include_cpm, 1) = 1 THEN COALESCE(e.total_cost, 0) ELSE 0 END) AS cpm_cost
            FROM endorse e
            INNER JOIN endorse_campaign ec
                ON ec.id = e.id_campaign
            LEFT JOIN (
                SELECT
                    l.id_endorse,
                    SUM(CASE WHEN DATE(l.date) BETWEEN '$growth_start_date' AND '$stats_until_date' THEN COALESCE(l.views, 0) ELSE 0 END) AS views_diff
                FROM endorse_logs l
                GROUP BY l.id_endorse
            ) v
                ON v.id_endorse = e.id
            LEFT JOIN (
                SELECT
                    id_endorse,
                    MIN(DATE(date)) AS first_fyp_date
                FROM (
                    SELECT
                        l.id_endorse,
                        l.date
                    FROM endorse_logs l
                    INNER JOIN endorse ef
                        ON ef.id = l.id_endorse
                    LEFT JOIN influencer inf
                        ON inf.id = ef.influencer
                    WHERE COALESCE(l.views_after, 0) >= $fyp_views
                        AND COALESCE(inf.follower, 0) > 0
                        AND COALESCE(l.views_after, 0) >= FLOOR(COALESCE(inf.follower, 0) * $fyp_percentage / 100)
                        AND DATE(l.date) <= '$stats_until_date'
                ) fyp_logs
                GROUP BY id_endorse
            ) fyp
                ON fyp.id_endorse = e.id
            WHERE e.pic_user_id = '$employee_id'
                AND e.status = 'Aktif'
                AND e.status_endorse = 'Posted Content'
                AND e.link_upload != ''
                $campaign_filter
                AND ec.status = 'Aktif'
                AND DATE(COALESCE(ec.start_at, '1900-01-01')) <= '$until_date'
                AND DATE(COALESCE(ec.until_at, '2999-12-31')) >= '$start_date'
                AND DATE(e.posting_at) BETWEEN '$start_date' AND '$until_date'
            GROUP BY e.id_campaign, ec.title
            ORDER BY campaign_title ASC
        ");
        // print_r($this->db->last_query());
        // die;

        $total_views = 0;
        $total_fyp = 0;
        $total_konten = 0;
        $total_cost = 0;
        $total_cpm_views = 0;
        $total_cpm_cost = 0;
        foreach ($grouped as &$item) {
            $rule_metrics = $source_rule_map[(int) ($item['id_campaign'] ?? 0)] ?? null;
            if (is_array($rule_metrics) && !empty($rule_metrics)) {
                if (!in_array('total_konten', $rule_metrics, true)) {
                    $item['total_konten'] = 0;
                }
                if (!in_array('total_fyp', $rule_metrics, true)) {
                    $item['total_fyp'] = 0;
                }
                if (!in_array('total_views', $rule_metrics, true)) {
                    $item['total_views'] = 0;
                }
                if (!in_array('total_cost', $rule_metrics, true)) {
                    $item['total_cost'] = 0;
                }
                if (!in_array('cpm', $rule_metrics, true)) {
                    $item['cpm_views'] = 0;
                    $item['cpm_cost'] = 0;
                }
            }
            $total_views += (float) ($item['total_views'] ?? 0);
            $total_fyp += (int) ($item['total_fyp'] ?? 0);
            $total_konten += (int) ($item['total_konten'] ?? 0);
            $total_cost += (float) ($item['total_cost'] ?? 0);
            $total_cpm_views += (float) ($item['cpm_views'] ?? 0);
            $total_cpm_cost += (float) ($item['cpm_cost'] ?? 0);
        }
        unset($item);
        $cpm = $total_cpm_views > 0 ? ($total_cpm_cost / $total_cpm_views) * 1000 : 0;

        return [
            'total_views' => $total_views,
            'total_fyp' => $total_fyp,
            'total_konten' => $total_konten,
            'total_cost' => $total_cost,
            'cpm' => $cpm,
            'grouped_campaign' => $grouped,
            'start_date' => $start_date,
            'until_date' => $until_date,
            'stats_until_date' => $stats_until_date,
            'query' => $this->db->last_query()
        ];
    }

    private function ensure_crm_kpi_content_table()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `crm_kpi_content_logs` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `month_key` CHAR(7) NOT NULL,
                `employee_id` INT(11) NOT NULL,
                `question` TEXT NULL,
                `upload_link` VARCHAR(500) NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                KEY `idx_crm_kpi_month_employee` (`month_key`, `employee_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $this->db->query($sql);
    }

    private function get_crm_kpi_metrics($start_date, $until_date, $employee_id, $kpi_rows = [], $period_type = 'month')
    {
        return [
            'total_views' => 0,
            'total_fyp' => 0,
            'total_konten' => 0,
            'total_cost' => 0,
            'cpm' => 0,
            'crm_content_official' => 0,
            'crm_content_official_3m' => 0,
            'crm_content_official_by_duration' => array_fill_keys(range(1, 12), 0),
            'crm_content_count_map' => [],
            'grouped_campaign' => [],
            'start_date' => $start_date,
            'until_date' => $until_date,
            'query' => '',
        ];
    }

    private function get_crm_kpi_metrics_quarterly($start_date, $until_date, $employee_id)
    {
        return [
            'total_views' => 0,
            'total_fyp' => 0,
            'total_konten' => 0,
            'total_cost' => 0,
            'cpm' => 0,
            'crm_content_official' => 0,
            'crm_content_official_3m' => 0,
            'crm_content_official_by_duration' => array_fill_keys(range(1, 12), 0),
            'crm_content_count_map' => [],
            'grouped_campaign' => [],
            'start_date' => $start_date,
            'until_date' => $until_date,
            'query' => '',
        ];
    }

    private function json_response($status, $message)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => (bool) $status,
                'message' => $message,
            ]));
    }
}
