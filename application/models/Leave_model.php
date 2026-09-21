<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leave_model extends CI_Model
{
    private $default_leave_type_definitions = [
        [
            'code' => 'annual_leave',
            'name' => 'Cuti Tahunan',
            'requires_attachment' => 0,
            'deducts_leave' => 1,
            'aliases' => ['cuti', 'cuti tahunan', 'cuti (tahunan)'],
        ],
        [
            'code' => 'marriage_leave',
            'name' => 'Cuti Menikah',
            'requires_attachment' => 0,
            'deducts_leave' => 0,
            'aliases' => ['cuti menikah'],
        ],
        [
            'code' => 'maternity_leave',
            'name' => 'Cuti Melahirkan',
            'requires_attachment' => 0,
            'deducts_leave' => 0,
            'aliases' => ['cuti melahirkan', 'cuti melahirkan (3 bulan)', 'maternity'],
        ],
        [
            'code' => 'wfh_permit',
            'name' => 'Izin WFH',
            'requires_attachment' => 0,
            'deducts_leave' => 0,
            'aliases' => ['izin wfh', 'wfh'],
        ],
        [
            'code' => 'late_permit',
            'name' => 'Izin Keterlambatan',
            'requires_attachment' => 0,
            'deducts_leave' => 0,
            'aliases' => ['izin keterlambatan', 'izin telat', 'terlambat'],
        ],
        [
            'code' => 'half_day_permit',
            'name' => 'Izin Setengah Hari',
            'requires_attachment' => 0,
            'deducts_leave' => 0,
            'aliases' => ['izin setengah hari', 'setengah hari', 'half day'],
        ],
        [
            'code' => 'absence_permit',
            'name' => 'Izin Tidak Hadir',
            'requires_attachment' => 0,
            'deducts_leave' => 0,
            'aliases' => ['izin tidak hadir', 'tidak hadir', 'absen'],
        ],
    ];

    public function __construct()
    {
        $this->load->database();
        $this->load->model('mymodel');
    }

    public function get_user_profile($user_id)
    {
        $query = $this->mymodel->selectWithQuery("SELECT * FROM user_profile WHERE user_id = '$user_id' LIMIT 1");
        return !empty($query) ? $query[0] : null;
    }

    public function get_leave_types()
    {
        $rows = $this->mymodel->selectWithQuery("SELECT * FROM leave_types WHERE is_active = 1 ORDER BY name ASC");
        if (empty($rows)) {
            return $rows;
        }

        $indexed_rows = [];
        $custom_rows = [];
        foreach ($rows as $row) {
            $definition = $this->match_leave_type_definition($row);
            if (empty($definition)) {
                $row['display_name'] = $row['name'] ?? '-';
                $custom_rows[] = $row;
                continue;
            }
            $row['display_name'] = $definition['name'];
            $indexed_rows[$definition['code']] = $row;
        }

        $ordered_rows = [];
        foreach ($this->default_leave_type_definitions as $definition) {
            if (isset($indexed_rows[$definition['code']])) {
                $ordered_rows[] = $indexed_rows[$definition['code']];
            }
        }

        foreach ($custom_rows as $row) {
            $ordered_rows[] = $row;
        }

        return $ordered_rows;
    }

    public function get_leave_type_id_by_code($code)
    {
        $query = $this->mymodel->selectWithQuery("SELECT id FROM leave_types WHERE code = '$code' LIMIT 1");
        return !empty($query) ? $query[0]['id'] : null;
    }

    public function get_annual_leave_type_id()
    {
        $by_code = $this->get_leave_type_id_by_code('annual_leave');
        if (!empty($by_code)) {
            return $by_code;
        }

        $query = $this->mymodel->selectWithQuery("SELECT id
            FROM leave_types
            WHERE LOWER(COALESCE(name, '')) IN ('cuti', 'cuti tahunan', 'cuti (tahunan)')
            ORDER BY id ASC
            LIMIT 1");

        return !empty($query) ? $query[0]['id'] : null;
    }

    public function is_deducting_leave_type($leave_type_id)
    {
        $row = $this->mymodel->selectWithQuery("SELECT deducts_leave FROM leave_types WHERE id = '$leave_type_id' LIMIT 1");
        if (empty($row)) {
            return false;
        }

        return intval($row[0]['deducts_leave'] ?? 0) === 1;
    }

    public function calculate_months_since($join_date)
    {
        if (empty($join_date) || $join_date == '0000-00-00') {
            return 0;
        }

        try {
            $start = new DateTime($join_date);
            $end = new DateTime(date('Y-m-d'));
            if ($end < $start) {
                return 0;
            }
            $diff = $start->diff($end);
            return ($diff->y * 12) + $diff->m;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Tanggal mulai akrual cuti = tanggal mulai kontrak terbaru (leave_accrual_start),
     * fallback ke join_date untuk karyawan tanpa kontrak.
     */
    public function get_leave_accrual_start($profile)
    {
        if (!empty($profile['leave_accrual_start']) && $profile['leave_accrual_start'] != '0000-00-00') {
            return $profile['leave_accrual_start'];
        }
        return $profile['join_date'] ?? null;
    }

    public function get_used_leave_days($user_id, $since = null)
    {
        $since_clause = '';
        if (!empty($since) && $since != '0000-00-00') {
            $since_safe = $this->db->escape_str($since);
            // Cuti yang diambil sebelum kontrak terbaru tidak mengurangi saldo kontrak baru.
            $since_clause = " AND lr.start_date >= '$since_safe'";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COALESCE(SUM(lr.total_days), 0) as used_days
            FROM leave_requests lr
            LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
            WHERE lr.user_id = '$user_id'
              AND lr.status = 'approved'
              AND COALESCE(lt.deducts_leave, 0) = 1$since_clause");
        return !empty($query) ? intval($query[0]['used_days']) : 0;
    }

    public function get_leave_balance($user_id)
    {
        $profile = $this->get_user_profile($user_id);
        if (empty($profile)) {
            return 0;
        }

        if (!empty($profile['is_probation']) && intval($profile['is_probation']) === 1) {
            return 0;
        }

        if (isset($profile['leave_balance']) && $profile['leave_balance'] !== null && $profile['leave_balance'] !== '') {
            return max(0, intval($profile['leave_balance']));
        }

        $accrual_start = $this->get_leave_accrual_start($profile);
        $months = $this->calculate_months_since($accrual_start);
        $used = $this->get_used_leave_days($user_id, $accrual_start);
        $balance = $months - $used;

        return $balance > 0 ? $balance : 0;
    }

    public function list_user_requests($user_id)
    {
        return $this->mymodel->selectWithQuery("SELECT lr.*, lt.name as leave_type_name
            FROM leave_requests lr
            LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
            WHERE lr.user_id = '$user_id'
            ORDER BY lr.id DESC");
    }

    public function list_all_requests()
    {
        return $this->mymodel->selectWithQuery("SELECT lr.*, lt.name as leave_type_name, u.full_name as user_name
            FROM leave_requests lr
            LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
            LEFT JOIN user u ON lr.user_id = u.id
            ORDER BY lr.id DESC");
    }

    public function get_approvals_for_requests($request_ids)
    {
        if (empty($request_ids)) {
            return [];
        }
        $ids = array_map('intval', $request_ids);
        $id_list = implode(',', $ids);

        $rows = $this->mymodel->selectWithQuery("SELECT leave_request_id, approver_role, status
            FROM leave_approvals
            WHERE leave_request_id IN ($id_list)");

        $map = [];
        foreach ($rows as $row) {
            $rid = $row['leave_request_id'];
            if (!isset($map[$rid])) {
                $map[$rid] = ['leader' => 'pending', 'hr' => 'pending'];
            }
            $map[$rid][$row['approver_role']] = $row['status'];
        }
        return $map;
    }

    public function list_pending_for_leader($leader_id = null)
    {
        $where = "lr.status = 'pending_leader'";
        if (!empty($leader_id)) {
            $where .= " AND lr.leader_id = '$leader_id'";
        }

        return $this->mymodel->selectWithQuery("SELECT lr.*, lt.name as leave_type_name, u.full_name as user_name
            FROM leave_requests lr
            LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
            LEFT JOIN user u ON lr.user_id = u.id
            WHERE $where
            ORDER BY lr.id DESC");
    }

    public function list_pending_for_hr($hr_id = null)
    {
        $where = "lr.status = 'pending_hr'";
        if (!empty($hr_id)) {
            $where .= " AND lr.hr_id = '$hr_id'";
        }

        return $this->mymodel->selectWithQuery("SELECT lr.*, lt.name as leave_type_name, u.full_name as user_name
            FROM leave_requests lr
            LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
            LEFT JOIN user u ON lr.user_id = u.id
            WHERE $where
            ORDER BY lr.id DESC");
    }

    public function get_request($request_id)
    {
        $query = $this->mymodel->selectWithQuery("SELECT lr.*, lt.name as leave_type_name, u.full_name as user_name
            FROM leave_requests lr
            LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
            LEFT JOIN user u ON lr.user_id = u.id
            WHERE lr.id = '$request_id'
            LIMIT 1");
        return !empty($query) ? $query[0] : null;
    }

    public function get_request_approvals($request_id)
    {
        return $this->mymodel->selectWithQuery("SELECT * FROM leave_approvals WHERE leave_request_id = '$request_id'");
    }

    public function create_request($data, $approvals)
    {
        $this->db->insert('leave_requests', $data);
        $request_id = $this->db->insert_id();

        foreach ($approvals as $approval) {
            $approval['leave_request_id'] = $request_id;
            $this->db->insert('leave_approvals', $approval);
        }

        return $request_id;
    }

    public function update_approval($request_id, $approver_role, $status, $note = null)
    {
        $data = [
            'status' => $status,
            'note' => $note,
            'decided_at' => date('Y-m-d H:i:s')
        ];
        $this->db->update('leave_approvals', $data, [
            'leave_request_id' => $request_id,
            'approver_role' => $approver_role
        ]);
    }

    public function update_request_status($request_id, $status)
    {
        $this->db->update('leave_requests', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $request_id]);
    }

    public function create_email_approval_token($data)
    {
        return (bool) $this->db->insert('leave_email_approval_tokens', $data);
    }

    public function get_email_approval_token($token)
    {
        return $this->db
            ->where('token', $token)
            ->limit(1)
            ->get('leave_email_approval_tokens')
            ->row_array();
    }

    public function update_email_approval_token($token_id, $data)
    {
        return (bool) $this->db->update('leave_email_approval_tokens', $data, ['id' => $token_id]);
    }

    public function invalidate_email_approval_tokens($request_id, $approver_id = null, $approver_role = null, $action = null, $exclude_token_id = null)
    {
        $this->db->where('leave_request_id', $request_id);
        $this->db->where('status', 'active');

        if ($approver_id !== null) {
            $this->db->where('approver_id', $approver_id);
        }
        if ($approver_role !== null) {
            $this->db->where('approver_role', $approver_role);
        }
        if ($action !== null) {
            $this->db->where('action', $action);
        }
        if ($exclude_token_id !== null) {
            $this->db->where('id !=', $exclude_token_id);
        }

        return (bool) $this->db->update('leave_email_approval_tokens', [
            'status' => 'revoked',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function list_events()
    {
        return $this->mymodel->selectWithQuery("SELECT * FROM leave_events ORDER BY start_date DESC");
    }

    public function create_event($data)
    {
        $this->db->insert('leave_events', $data);
        return $this->db->insert_id();
    }

    public function update_event($event_id, $data)
    {
        return $this->db->update('leave_events', $data, ['id' => $event_id]);
    }

    public function delete_event($event_id)
    {
        return $this->db->delete('leave_events', ['id' => $event_id]);
    }

    public function get_leave_counts_by_date()
    {
        $leave_type_case_sql = $this->get_leave_type_display_case_sql('lt');

        // Get count of approved leave requests grouped by date
        $query = $this->mymodel->selectWithQuery("
            SELECT 
                DATE(dates.date) as leave_date,
                COUNT(DISTINCT lr.user_id) as people_count,
                GROUP_CONCAT(DISTINCT u.full_name ORDER BY u.full_name SEPARATOR ', ') as people_names,
                GROUP_CONCAT(DISTINCT {$leave_type_case_sql} ORDER BY {$leave_type_case_sql} SEPARATOR ', ') as leave_types
            FROM (
                SELECT CURDATE() - INTERVAL 365 DAY + INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as date
                FROM (SELECT 0 AS a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                      UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) AS a
                CROSS JOIN (SELECT 0 AS a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
                            UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 
                            UNION SELECT 8 UNION SELECT 9) AS b
                CROSS JOIN (SELECT 0 AS a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
                            UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7) AS c
                WHERE CURDATE() - INTERVAL 365 DAY + INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY <= CURDATE() + INTERVAL 365 DAY
            ) dates
            LEFT JOIN leave_requests lr ON dates.date BETWEEN lr.start_date AND lr.end_date
                AND lr.status = 'approved'
            LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
            LEFT JOIN user u ON lr.user_id = u.id
            WHERE dates.date IS NOT NULL
            GROUP BY dates.date
            HAVING people_count > 0
            ORDER BY leave_date
        ");
        
        $result = [];
        if (!empty($query)) {
            foreach ($query as $row) {
                $result[$row['leave_date']] = [
                    'count' => (int)$row['people_count'],
                    'names' => $row['people_names'],
                    'types' => $row['leave_types']
                ];
            }
        }
        return $result;
    }

    public function get_leave_details_by_date()
    {
        $leave_type_case_sql = $this->get_leave_type_display_case_sql('lt');

        // Get individual approved leave requests grouped by date (one entry per employee)
        $query = $this->mymodel->selectWithQuery("
            SELECT
                DATE(dates.date) as leave_date,
                lr.id as request_id,
                u.full_name as user_name,
                {$leave_type_case_sql} as leave_type,
                lr.start_date,
                lr.end_date,
                lr.reason
            FROM (
                SELECT CURDATE() - INTERVAL 365 DAY + INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as date
                FROM (SELECT 0 AS a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
                      UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) AS a
                CROSS JOIN (SELECT 0 AS a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3
                            UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7
                            UNION SELECT 8 UNION SELECT 9) AS b
                CROSS JOIN (SELECT 0 AS a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3
                            UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7) AS c
                WHERE CURDATE() - INTERVAL 365 DAY + INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY <= CURDATE() + INTERVAL 365 DAY
            ) dates
            INNER JOIN leave_requests lr ON dates.date BETWEEN lr.start_date AND lr.end_date
                AND lr.status = 'approved'
            LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
            LEFT JOIN user u ON lr.user_id = u.id
            WHERE dates.date IS NOT NULL
            ORDER BY dates.date, u.full_name
        ");

        $result = [];
        if (!empty($query)) {
            foreach ($query as $row) {
                $result[$row['leave_date']][] = [
                    'request_id' => $row['request_id'],
                    'user_name' => $row['user_name'],
                    'leave_type' => $row['leave_type'],
                    'start_date' => $row['start_date'],
                    'end_date' => $row['end_date'],
                    'reason' => $row['reason'],
                ];
            }
        }
        return $result;
    }

    public function get_work_calendar_summary($start_date = null, $end_date = null)
    {
        $start = $start_date ?: date('Y-m-d', strtotime('-365 days'));
        $end = $end_date ?: date('Y-m-d', strtotime('+365 days'));

        $users = $this->mymodel->selectWithQuery("
            SELECT u.id, u.full_name, COALESCE(up.is_wfo, 0) AS is_wfo
            FROM user u
            LEFT JOIN user_profile up ON up.user_id = u.id
                AND up.id = (
                    SELECT MAX(up2.id)
                    FROM user_profile up2
                    WHERE up2.user_id = u.id
                )
            WHERE u.status = 'Aktif'
            ORDER BY u.full_name ASC
        ");

        $leave_rows = $this->mymodel->selectWithQuery("
            SELECT lr.user_id, lr.start_date, lr.end_date, lt.name AS leave_type_name, lt.code AS leave_type_code
            FROM leave_requests lr
            LEFT JOIN leave_types lt ON lt.id = lr.leave_type_id
            WHERE lr.status = 'approved'
              AND lr.start_date <= " . $this->db->escape($end) . "
              AND lr.end_date >= " . $this->db->escape($start) . "
        ");

        $leave_by_user_date = [];
        foreach ($leave_rows as $leave) {
            $user_id = (int)($leave['user_id'] ?? 0);
            if ($user_id <= 0) {
                continue;
            }

            $leave_start = max($start, (string)($leave['start_date'] ?? $start));
            $leave_end = min($end, (string)($leave['end_date'] ?? $end));
            $type_name = (string)($leave['leave_type_name'] ?? '-');
            $type_code = strtolower(trim((string)($leave['leave_type_code'] ?? '')));
            $type_name_lower = strtolower(trim($type_name));
            $category = (
                strpos($type_code, 'permit') !== false
                || strpos($type_name_lower, 'izin') !== false
                || strpos($type_name_lower, 'wfh') !== false
            ) ? 'izin' : 'cuti';

            $period = new DatePeriod(
                new DateTime($leave_start),
                new DateInterval('P1D'),
                (new DateTime($leave_end))->modify('+1 day')
            );

            foreach ($period as $date) {
                $date_key = $date->format('Y-m-d');
                if (!isset($leave_by_user_date[$user_id])) {
                    $leave_by_user_date[$user_id] = [];
                }

                if (
                    !isset($leave_by_user_date[$user_id][$date_key])
                    || $category === 'cuti'
                ) {
                    $leave_by_user_date[$user_id][$date_key] = [
                        'category' => $category,
                        'leave_type' => $type_name,
                    ];
                }
            }
        }

        $result = [];
        $period = new DatePeriod(
            new DateTime($start),
            new DateInterval('P1D'),
            (new DateTime($end))->modify('+1 day')
        );

        foreach ($period as $date) {
            $date_key = $date->format('Y-m-d');
            $result[$date_key] = [
                'wfo' => ['count' => 0, 'people' => []],
                'izin' => ['count' => 0, 'people' => []],
                'cuti' => ['count' => 0, 'people' => []],
            ];

            foreach ($users as $user) {
                $user_id = (int)($user['id'] ?? 0);
                $name = trim((string)($user['full_name'] ?? ''));
                if ($user_id <= 0 || $name === '') {
                    continue;
                }

                $override = $leave_by_user_date[$user_id][$date_key] ?? null;
                if (!empty($override)) {
                    $category = $override['category'];
                    $person = [
                        'name' => $name,
                        'leave_type' => $override['leave_type'] ?? '-',
                    ];
                } else {
                    if ((int)($user['is_wfo'] ?? 0) !== 1) {
                        continue;
                    }
                    $category = 'wfo';
                    $person = [
                        'name' => $name,
                        'leave_type' => '',
                    ];
                }

                $result[$date_key][$category]['count']++;
                $result[$date_key][$category]['people'][] = $person;
            }
        }

        return $result;
    }

    public function sync_balance_for_request($request, $next_status)
    {
        if (empty($request) || empty($request['user_id']) || empty($request['leave_type_id'])) {
            return;
        }

        if (!$this->is_deducting_leave_type($request['leave_type_id'])) {
            return;
        }

        $previous_status = strtolower(trim((string)($request['status'] ?? '')));
        $next_status = strtolower(trim((string)$next_status));
        if ($previous_status === $next_status) {
            return;
        }

        $profile = $this->get_user_profile($request['user_id']);
        if (empty($profile)) {
            return;
        }

        if (!empty($profile['is_probation']) && intval($profile['is_probation']) === 1) {
            $this->db->update('user_profile', [
                'leave_balance' => 0
            ], ['user_id' => $request['user_id']]);
            return;
        }

        $has_manual_balance = isset($profile['leave_balance']) && $profile['leave_balance'] !== null && $profile['leave_balance'] !== '';
        if (!$has_manual_balance) {
            return;
        }

        $days = max(0, intval($request['total_days'] ?? 0));
        if ($days === 0) {
            return;
        }

        $current_balance = max(0, intval($profile['leave_balance']));
        if ($previous_status !== 'approved' && $next_status === 'approved') {
            $current_balance -= $days;
        } elseif ($previous_status === 'approved' && $next_status !== 'approved') {
            $current_balance += $days;
        } else {
            return;
        }

        $this->db->update('user_profile', [
            'leave_balance' => max(0, $current_balance)
        ], ['user_id' => $request['user_id']]);
    }

    private function ensure_default_leave_types()
    {
        $rows = $this->mymodel->selectWithQuery("SELECT * FROM leave_types");
        $used_ids = [];

        foreach ($this->default_leave_type_definitions as $definition) {
            $matched_row = null;

            foreach ($rows as $row) {
                if (in_array(intval($row['id']), $used_ids, true)) {
                    continue;
                }

                $matched_definition = $this->match_leave_type_definition($row);
                if (!empty($matched_definition) && $matched_definition['code'] === $definition['code']) {
                    $matched_row = $row;
                    break;
                }
            }

            if (empty($matched_row)) {
                $this->db->insert('leave_types', [
                    'code' => $definition['code'],
                    'name' => $definition['name'],
                    'is_active' => 1,
                    'requires_attachment' => intval($definition['requires_attachment']),
                    'deducts_leave' => intval($definition['deducts_leave']),
                ]);

                $insert_id = $this->db->insert_id();
                if (!empty($insert_id)) {
                    $used_ids[] = intval($insert_id);
                }
                continue;
            }

            $used_ids[] = intval($matched_row['id']);
            $updates = [];

            if (($matched_row['code'] ?? '') !== $definition['code']) {
                $updates['code'] = $definition['code'];
            }
            if (($matched_row['name'] ?? '') !== $definition['name']) {
                $updates['name'] = $definition['name'];
            }
            if (intval($matched_row['is_active'] ?? 0) !== 1) {
                $updates['is_active'] = 1;
            }
            if (array_key_exists('requires_attachment', $matched_row)
                && intval($matched_row['requires_attachment'] ?? 0) !== intval($definition['requires_attachment'])) {
                $updates['requires_attachment'] = intval($definition['requires_attachment']);
            }
            if (intval($matched_row['deducts_leave'] ?? 0) !== intval($definition['deducts_leave'])) {
                $updates['deducts_leave'] = intval($definition['deducts_leave']);
            }

            if (!empty($updates)) {
                $this->db->update('leave_types', $updates, ['id' => $matched_row['id']]);
            }
        }
    }

    private function match_leave_type_definition($row)
    {
        $code = strtolower(trim($row['code'] ?? ''));
        $name = strtolower(trim($row['name'] ?? ''));

        foreach ($this->default_leave_type_definitions as $definition) {
            if ($code !== '' && $code === strtolower($definition['code'])) {
                return $definition;
            }
            if ($name !== '' && $name === strtolower($definition['name'])) {
                return $definition;
            }
            if ($name !== '' && in_array($name, $definition['aliases'], true)) {
                return $definition;
            }
        }

        return null;
    }

    private function get_leave_type_display_case_sql($alias = 'lt')
    {
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$alias);
        if ($alias === '') {
            $alias = 'lt';
        }

        $conditions = [];
        foreach ($this->default_leave_type_definitions as $definition) {
            $checks = [];
            $code = $this->db->escape(strtolower($definition['code']));
            $checks[] = "LOWER(COALESCE({$alias}.code, '')) = {$code}";

            $name = $this->db->escape(strtolower($definition['name']));
            $checks[] = "LOWER(COALESCE({$alias}.name, '')) = {$name}";

            foreach ($definition['aliases'] as $alias_name) {
                $escaped_alias_name = $this->db->escape(strtolower($alias_name));
                $checks[] = "LOWER(COALESCE({$alias}.name, '')) = {$escaped_alias_name}";
            }

            $display_name = $this->db->escape($definition['name']);
            $conditions[] = 'WHEN ' . implode(' OR ', $checks) . " THEN {$display_name}";
        }

        return "CASE " . implode(' ', $conditions) . " ELSE COALESCE({$alias}.name, '-') END";
    }

}
