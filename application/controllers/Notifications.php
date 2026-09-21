<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Notifications extends BaseController
{
    protected $require_permissions = true;
    protected $show_403_on_deny = true;
    protected $public_methods = ['get_notifications', 'get_unread_count', 'mark_read', 'mark_all_read'];
    private $notification_categories = [
        'finance' => [
            'label' => 'Finance',
            'description' => 'Pengajuan transaksi, payment KOL, dan kebutuhan finance.',
            'icon' => 'bi-wallet2',
        ],
        'team' => [
            'label' => 'Team',
            'description' => 'Pengajuan cuti, point, challenge, review, dan update tim.',
            'icon' => 'bi-people',
        ],
        'absensi' => [
            'label' => 'Absensi',
            'description' => 'Absen masuk, istirahat, pulang, dan keterlambatan karyawan.',
            'icon' => 'bi-fingerprint',
        ],
        'ultah' => [
            'label' => 'Ulang Tahun',
            'description' => 'Ucapan ulang tahun karyawan.',
            'icon' => 'bi-gift',
        ],
    ];
    private $notification_subcategories = [
        'pengajuan_transaksi' => 'Pengajuan Transaksi',
        'pengajuan_cuti' => 'Pengajuan Cuti',
        'point' => 'Point',
        'challenge' => 'Challenge',
        'approval' => 'Approval',
        'team_update' => 'Team Update',
        'absensi' => 'Absensi',
        'karyawan' => 'Karyawan',
    ];
    
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
        
        // Pass permission data to view
        $data['can_delete'] = $this->permission->check_permission($user_id, 'notifications', 'delete');
        
        $keyword = trim($_GET['keyword'] ?? "");
        $is_read_filter = $_GET['is_read_filter'] ?? "";
        $active_category = strtolower($_GET['category'] ?? "");
        
        $base_qry = $this->build_notification_base_query($user_id, $keyword, $is_read_filter);
        $category_counts = $this->get_notification_category_counts($base_qry);
        $active_category = $this->resolve_active_category($active_category, $category_counts);
        $qry = $base_qry . " AND " . $this->get_notification_category_condition($active_category);

        // Count total notifications for pagination
        $count_query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM notifications WHERE $qry");
        $total_notifications = $count_query[0]['count'];
        
        $data['page'] = CEIL($total_notifications / 20);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($total_notifications) . ' notifikasi ditemukan!</label></p>';

        // Pagination
        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }
        
        $offset = ($current_page - 1) * 20;

        // Get paginated notifications
        $notifications = $this->mymodel->selectWithQuery("SELECT * FROM notifications WHERE $qry ORDER BY created_at DESC LIMIT $offset, 20");
        $data['notifications'] = $this->decorate_notifications($notifications);
        $data['notification_categories'] = $this->notification_categories;
        $data['category_counts'] = $category_counts;
        $data['active_category'] = $active_category;
        $data['keyword'] = $keyword;
        $data['is_read_filter'] = $is_read_filter;
        
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);
        
        $data['title'] = 'Semua Notifikasi - ' . $this->template->title();
        $data['content'] = $this->load->view("notifications/index", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function get_notifications()
    {
        $user_id = $_SESSION['user']['id'];
        $limit = $this->input->get('limit') ? (int)$this->input->get('limit') : 10;
        $active_category = strtolower($this->input->get('category') ?? "");
        if ($limit < 1) {
            $limit = 10;
        }
        if ($limit > 30) {
            $limit = 30;
        }

        $base_qry = $this->build_notification_base_query($user_id);
        $category_counts = $this->get_notification_category_counts($base_qry);
        $active_category = $this->resolve_active_category($active_category, $category_counts);
        $qry = $base_qry . " AND " . $this->get_notification_category_condition($active_category);

        // Get notifications
        $notifications = $this->mymodel->selectWithQuery("
            SELECT * 
            FROM notifications 
            WHERE $qry
            ORDER BY created_at DESC 
            LIMIT $limit
        ");
        
        // Count unread notifications
        $unread_count = $this->mymodel->selectWithQuery("
            SELECT COUNT(id) as count 
            FROM notifications 
            WHERE user_id = " . $this->db->escape($user_id) . " AND is_read = 0
        ")[0]['count'];
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'notifications' => $this->decorate_notifications($notifications),
                'unread_count' => $unread_count,
                'categories' => $this->notification_categories,
                'category_counts' => $category_counts,
                'active_category' => $active_category
            ]));
    }

    // API: Mendapatkan jumlah notifikasi yang belum dibaca
    public function get_unread_count()
    {
        $user_id = $_SESSION['user']['id'];
        
        $count = $this->mymodel->selectWithQuery("
            SELECT COUNT(id) as count 
            FROM notifications 
            WHERE user_id = " . $this->db->escape($user_id) . " AND is_read = 0
        ")[0]['count'];
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'count' => $count
            ]));
    }

    public function mark_read()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $notification_id = $input['notification_id'] ?? null;
        $user_id = $_SESSION['user']['id'];
        
        if (!$notification_id) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false, 
                    'message' => 'Notification ID required'
                ]));
            return;
        }
        
        $result = $this->db->update('notifications', ['is_read' => 1], [
            'id' => $notification_id,
            'user_id' => $user_id
        ]);
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => $result,
                'message' => $result ? 'Notification marked as read' : 'Failed to mark notification as read'
            ]));
    }

    public function mark_all_read()
    {
        $user_id = $_SESSION['user']['id'];
        
        $result = $this->db->update('notifications', ['is_read' => 1], [
            'user_id' => $user_id,
            'is_read' => 0
        ]);
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => $result,
                'message' => $result ? 'All notifications marked as read' : 'Failed to mark notifications as read'
            ]));
    }

    public function delete($id = null)
    {
        if (!$id) {
            $this->session->set_flashdata('error', 'ID notifikasi tidak valid');
            redirect('notifications');
        }
        
        $user_id = $_SESSION['user']['id'];
        
        $result = $this->db->delete('notifications', [
            'id' => $id,
            'user_id' => $user_id
        ]);
        
        if ($result) {
            $this->session->set_flashdata('success', 'Notifikasi berhasil dihapus');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus notifikasi');
        }
        
        redirect('notifications');
    }

    public function clear_read()
    {
        $user_id = $_SESSION['user']['id'];
        
        $result = $this->db->delete('notifications', [
            'user_id' => $user_id,
            'is_read' => 1
        ]);
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => $result,
                'message' => $result ? 'Read notifications cleared' : 'Failed to clear notifications'
            ]));
    }

    public static function send_notification($db, $user_id, $title, $message, $type = 'info', $related_table = null, $related_id = null, $category = null, $subcategory = null)
    {
        $taxonomy = self::infer_notification_taxonomy($title, $message, $related_table, $category, $subcategory);
        $notification_data = [
            'user_id' => $user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'category' => $taxonomy['category'],
            'subcategory' => $taxonomy['subcategory'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $db->insert('notifications', $notification_data);
    }

    private function build_notification_base_query($user_id, $keyword = "", $is_read_filter = "")
    {
        $qry = "user_id = " . $this->db->escape($user_id);

        if ($keyword !== "") {
            $keyword = $this->db->escape_like_str($keyword);
            $qry .= " AND (title LIKE '%$keyword%' ESCAPE '!' OR message LIKE '%$keyword%' ESCAPE '!')";
        }

        if ($is_read_filter !== "" && in_array((string)$is_read_filter, ['0', '1'], true)) {
            $qry .= " AND is_read = " . $this->db->escape($is_read_filter);
        }

        return $qry;
    }

    private function get_notification_category_counts($base_qry)
    {
        $counts = [];

        foreach ($this->notification_categories as $key => $category) {
            $condition = $this->get_notification_category_condition($key);
            $row = $this->mymodel->selectWithQuery("
                SELECT
                    COUNT(id) AS total,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unread
                FROM notifications
                WHERE $base_qry AND $condition
            ");

            $counts[$key] = [
                'total' => intval($row[0]['total'] ?? 0),
                'unread' => intval($row[0]['unread'] ?? 0),
            ];
        }

        return $counts;
    }

    private function resolve_active_category($requested_category, $category_counts)
    {
        if (isset($this->notification_categories[$requested_category])) {
            return $requested_category;
        }

        foreach ($this->notification_categories as $key => $category) {
            if (($category_counts[$key]['unread'] ?? 0) > 0) {
                return $key;
            }
        }

        foreach ($this->notification_categories as $key => $category) {
            if (($category_counts[$key]['total'] ?? 0) > 0) {
                return $key;
            }
        }

        return 'finance';
    }

    private function get_notification_category_condition($category)
    {
        $finance_condition = $this->get_finance_notification_condition();
        $category_expr = "LOWER(COALESCE(category, ''))";
        $subcategory_empty = "(subcategory IS NULL OR subcategory = '')";

        // kategori baru (mis. absensi): dicocokkan lurus, tidak ikut logika finance/team
        if ($category !== 'finance' && $category !== 'team') {
            return "($category_expr = " . $this->db->escape(strtolower($category)) . ")";
        }

        if ($category === 'finance') {
            return "(
                $category_expr = 'finance'
                OR (($category_expr = '' OR ($category_expr = 'team' AND $subcategory_empty)) AND $finance_condition)
            )";
        }

        return "((
            $category_expr = 'team'
            AND NOT (($category_expr = 'team' AND $subcategory_empty) AND $finance_condition)
        ) OR (
            $category_expr = ''
            AND NOT ($finance_condition)
        ))";
    }

    private function get_finance_notification_condition()
    {
        $text_expr = "LOWER(CONCAT(COALESCE(title, ''), ' ', COALESCE(message, ''), ' ', COALESCE(related_table, '')))";
        $keywords = $this->get_finance_keywords();

        $conditions = array_map(function ($keyword) use ($text_expr) {
            return $text_expr . " LIKE " . $this->db->escape('%' . strtolower($keyword) . '%');
        }, $keywords);

        return '(' . implode(' OR ', $conditions) . ')';
    }

    private function decorate_notifications($notifications)
    {
        if (empty($notifications)) {
            return [];
        }

        return array_map(function ($notification) {
            $category = $this->classify_notification($notification);
            $action = $this->get_notification_action($notification, $category);

            $notification['category'] = $category['key'];
            $notification['category_label'] = $category['label'];
            $notification['category_icon'] = $category['icon'];
            $notification['subcategory'] = $category['subcategory'];
            $notification['action_url'] = $action['url'];
            $notification['action_label'] = $action['label'];

            return $notification;
        }, $notifications);
    }

    private function classify_notification($notification)
    {
        $text = strtolower(trim(($notification['title'] ?? '') . ' ' . ($notification['message'] ?? '')));
        $stored_category = strtolower(trim((string)($notification['category'] ?? '')));
        $stored_subcategory = strtolower(trim((string)($notification['subcategory'] ?? '')));
        $stored_category_is_valid = isset($this->notification_categories[$stored_category]);

        if (
            $stored_category_is_valid &&
            !($stored_category === 'team' && $stored_subcategory === '' && $this->text_contains_any($text, $this->get_finance_keywords()))
        ) {
            return [
                'key' => $stored_category,
                'label' => $this->notification_categories[$stored_category]['label'],
                'icon' => $this->notification_categories[$stored_category]['icon'],
                'subcategory' => $this->get_subcategory_label($stored_subcategory, $stored_category),
            ];
        }

        if ($this->text_contains_any($text, $this->get_finance_keywords())) {
            return [
                'key' => 'finance',
                'label' => $this->notification_categories['finance']['label'],
                'icon' => $this->notification_categories['finance']['icon'],
                'subcategory' => $this->notification_subcategories['pengajuan_transaksi'],
            ];
        }

        $subcategory = $this->notification_subcategories['team_update'];
        if ($this->text_contains_any($text, ['cuti', 'leave', 'izin'])) {
            $subcategory = $this->notification_subcategories['pengajuan_cuti'];
        } elseif ($this->text_contains_any($text, ['point', 'poin', 'score', 'skor'])) {
            $subcategory = $this->notification_subcategories['point'];
        } elseif ($this->text_contains_any($text, ['challenge', 'quest', 'tantangan'])) {
            $subcategory = $this->notification_subcategories['challenge'];
        } elseif ($this->text_contains_any($text, ['review', 'approval', 'approve'])) {
            $subcategory = $this->notification_subcategories['approval'];
        }

        return [
            'key' => 'team',
            'label' => $this->notification_categories['team']['label'],
            'icon' => $this->notification_categories['team']['icon'],
            'subcategory' => $subcategory,
        ];
    }

    private function get_notification_action($notification, $category)
    {
        $text = strtolower(trim(($notification['title'] ?? '') . ' ' . ($notification['message'] ?? '')));
        $subcategory = strtolower(trim((string)($notification['subcategory'] ?? '')));
        $review_url = base_url('review-endorse?keyword_category=SPV&keyword=' . rawurlencode($_SESSION['user']['full_name'] ?? ''));

        if ($category['key'] === 'finance') {
            if ($this->text_contains_any($text, ['transaksi']) && !$this->text_contains_any($text, ['payment', 'pembayaran'])) {
                return ['url' => base_url('transaction'), 'label' => 'Lihat Transaksi'];
            }

            return ['url' => base_url('payment'), 'label' => 'Lihat Pengajuan'];
        }

        if ($subcategory === 'pengajuan_cuti' || $this->text_contains_any($text, ['cuti', 'leave', 'izin', 'wfh', 'work from home'])) {
            return ['url' => base_url('leave'), 'label' => 'Lihat Pengajuan'];
        }

        if (in_array($subcategory, ['point', 'challenge'], true) || $this->text_contains_any($text, ['point', 'poin', 'challenge', 'quest', 'tantangan'])) {
            return ['url' => base_url('quest'), 'label' => 'Lihat Quest'];
        }

        if ($subcategory === 'approval' || $this->text_contains_any($text, ['review'])) {
            return ['url' => $review_url, 'label' => 'Lihat Review'];
        }

        return ['url' => base_url('notifications'), 'label' => 'Buka Notifikasi'];
    }

    private function text_contains_any($text, $keywords)
    {
        foreach ($keywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }

    private function get_finance_keywords()
    {
        return [
            'pengajuan payment',
            'payment',
            'pembayaran',
            'pengajuan transaksi',
            'transaksi kol',
            'kol payment',
            'nominal pengajuan',
            'invoice',
            'reimburse',
            'reimbursement',
            'finance',
            'keuangan',
        ];
    }

    private function get_subcategory_label($subcategory, $category)
    {
        if (isset($this->notification_subcategories[$subcategory])) {
            return $this->notification_subcategories[$subcategory];
        }

        return $category === 'finance'
            ? $this->notification_subcategories['pengajuan_transaksi']
            : $this->notification_subcategories['team_update'];
    }

    private static function infer_notification_taxonomy($title, $message, $related_table = null, $category = null, $subcategory = null)
    {
        $category = strtolower(trim((string)$category));
        $subcategory = strtolower(trim((string)$subcategory));

        if (in_array($category, ['finance', 'team'], true)) {
            return [
                'category' => $category,
                'subcategory' => $subcategory !== '' ? $subcategory : ($category === 'finance' ? 'pengajuan_transaksi' : 'team_update'),
            ];
        }

        $text = strtolower(trim((string)$title . ' ' . (string)$message . ' ' . (string)$related_table));
        $finance_keywords = [
            'pengajuan payment',
            'payment',
            'pembayaran',
            'pengajuan transaksi',
            'transaksi kol',
            'kol payment',
            'nominal pengajuan',
            'invoice',
            'reimburse',
            'reimbursement',
            'finance',
            'keuangan',
        ];

        foreach ($finance_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return ['category' => 'finance', 'subcategory' => 'pengajuan_transaksi'];
            }
        }

        if (strpos($text, 'cuti') !== false || strpos($text, 'leave') !== false || strpos($text, 'izin') !== false || strpos($text, 'wfh') !== false) {
            return ['category' => 'team', 'subcategory' => 'pengajuan_cuti'];
        }

        if (strpos($text, 'point') !== false || strpos($text, 'poin') !== false || strpos($text, 'score') !== false || strpos($text, 'skor') !== false) {
            return ['category' => 'team', 'subcategory' => 'point'];
        }

        if (strpos($text, 'challenge') !== false || strpos($text, 'quest') !== false || strpos($text, 'tantangan') !== false) {
            return ['category' => 'team', 'subcategory' => 'challenge'];
        }

        if (strpos($text, 'review') !== false || strpos($text, 'approval') !== false || strpos($text, 'approve') !== false) {
            return ['category' => 'team', 'subcategory' => 'approval'];
        }

        return ['category' => 'team', 'subcategory' => 'team_update'];
    }
}
