<?php

// PhpSpreadsheet autoload commented out to prevent PHP version conflicts
// Only load when specifically needed for Excel operations
require FCPATH . 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Google\Client as Google_Client;
use Google\Service\Sheets as Google_Service_Sheets;

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Crm extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');
        $this->load->library('session');

        // Set public methods (no permission required)
        $this->set_public_methods([]);

        // Override method-to-action mapping if needed
        $this->set_method_permissions([
            'remove' => 'delete',
            'action' => 'edit',
            'action_process' => 'edit',
            'export' => 'view',
            'analytics' => 'view',
            'chat' => 'view',
            'update_customer_order' => 'edit',
            'kpi_logs' => 'view',
            'save_kpi_logs' => 'edit',
            'campaign_detail' => 'view',
            'crm_campaign_options' => 'view',
            'crm_campaign_detail' => 'view',
            'crm_campaign_sheet_rows' => 'view',
            'crm_campaign_create' => 'edit',
            'crm_campaign_delete' => 'edit',
            'crm_campaign_customer_search' => 'view',
            'crm_campaign_customer_attach' => 'edit',
            'crm_campaign_broadcast_send' => 'edit'
        ]);
    }

    function update_customer_order()
    {
        $id_customer = $_GET['id'];
        $url = $this->template->endpoint_url() . 'api/customer/summary?id=' . $id_customer;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        // print_r($response);
        $response = json_decode($response, true);
        curl_close($curl);
        echo $response['msg'];
        // if ($response['status'] == true) {
        //     $msg = 'Refresh data berhasil!';
        //     echo $this->template->alert_success($msg);
        // } else {
        //     $msg = 'Refresh data tidak berhasil!';
        //     echo $this->template->alert_danger($msg);
        // }
    }
    function chat()
    {
        $data['title'] = 'CRM Chat - ' . $this->template->title();
        $data['chat_contacts'] = $this->crm_chat_contacts();
        $data['content'] = $this->load->view("crm/chat", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    function analytics()
    {
        $filters = $this->_crm_build_customer_filters();
        $params = $filters['params'];
        $analytics_data = $this->_crm_customer_analytics_data($filters);

        $data['title'] = 'Customer Analytics - ' . $this->template->title();
        $data['start_date'] = $params['start_date'];
        $data['until_date'] = $params['until_date'];
        $data['product_filter'] = $params['product_filter'];
        $data['analytics'] = $analytics_data;
        $data['products'] = $this->_crm_analytics_product_options($params);
        $data['content'] = $this->load->view("crm/analytics", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    private function crm_chat_contacts()
    {
        $shop_rows = $this->mymodel->selectWithQuery("SELECT shop_id, shop_name, opt FROM marketplace_config WHERE status = 'Aktif' AND LOWER(opt) <> 'meta'");
        $shop_map = array();
        foreach ($shop_rows as $shop_row) {
            $shop_map[(string)($shop_row['shop_id'] ?? '')] = array(
                'shop_name' => (string)($shop_row['shop_name'] ?? ''),
                'marketplace' => (string)($shop_row['opt'] ?? '')
            );
        }

        $rows = $this->mymodel->selectWithQuery("
            SELECT id, marketplace, shop_id, conversation_id, sender_id, sender_name, message_type, chat_text, created_at
            FROM webhook_chat
            WHERE type = 'message'
            ORDER BY created_at ASC, id ASC
        ");

        $contacts = array();
        foreach ($rows as $row) {
            $conversation_id = trim((string)($row['conversation_id'] ?? ''));
            $sender_id = trim((string)($row['sender_id'] ?? ''));
            $shop_id = trim((string)($row['shop_id'] ?? ''));
            $marketplace = strtoupper(trim((string)($row['marketplace'] ?? '')));
            $contact_key = $conversation_id !== '' ? $conversation_id : ($marketplace . ':' . $shop_id . ':' . $sender_id);

            $sender_name = trim((string)($row['sender_name'] ?? ''));
            if ($sender_name === '') {
                $sender_name = $sender_id !== '' ? ('Customer ' . $sender_id) : 'Customer';
            }

            $shop_name = '';
            if (isset($shop_map[$shop_id])) {
                $shop_name = trim((string)($shop_map[$shop_id]['shop_name'] ?? ''));
                if ($marketplace === '') {
                    $marketplace = strtoupper(trim((string)($shop_map[$shop_id]['marketplace'] ?? '')));
                }
            }
            if ($shop_name === '') {
                $shop_name = $shop_id !== '' ? ('Shop ' . $shop_id) : '-';
            }

            $message_type = trim((string)($row['message_type'] ?? ''));
            $chat_text = trim((string)($row['chat_text'] ?? ''));
            $message_text = $chat_text !== '' ? $chat_text : ('[Customer mengirim ' . ($message_type !== '' ? $message_type : 'pesan') . ']');
            $created_at = trim((string)($row['created_at'] ?? ''));
            $time_display = $this->crm_chat_time_label($created_at);
            $time_full = $created_at !== '' && strtotime($created_at) ? date('d/m/Y H:i', strtotime($created_at)) : '-';

            if (!isset($contacts[$contact_key])) {
                $contacts[$contact_key] = array(
                    'id' => $contact_key,
                    'conversation_id' => $conversation_id,
                    'sender_id' => $sender_id,
                    'name' => $sender_name,
                    'initials' => $this->crm_chat_initials($sender_name),
                    'shop' => $shop_name,
                    'shop_id' => $shop_id,
                    'channel' => $marketplace !== '' ? $marketplace : '-',
                    'channel_color' => $this->crm_chat_channel_color($marketplace),
                    'preview' => $message_text,
                    'time' => $time_display,
                    'time_full' => $time_full,
                    'unread' => 0,
                    'messages' => array(),
                    'last_timestamp' => $created_at
                );
            }

            $contacts[$contact_key]['messages'][] = array(
                'from' => 'them',
                'text' => $message_text,
                'time' => $time_display,
                'time_full' => $time_full,
                'message_type' => $message_type
            );
            $contacts[$contact_key]['preview'] = $message_text;
            $contacts[$contact_key]['time'] = $time_display;
            $contacts[$contact_key]['time_full'] = $time_full;
            $contacts[$contact_key]['last_timestamp'] = $created_at;
        }

        $contacts = array_values($contacts);
        usort($contacts, function ($a, $b) {
            $a_time = strtotime((string)($a['last_timestamp'] ?? '')) ?: 0;
            $b_time = strtotime((string)($b['last_timestamp'] ?? '')) ?: 0;
            if ($a_time === $b_time) {
                return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
            }
            return $b_time <=> $a_time;
        });

        return $contacts;
    }

    private function _crm_customer_analytics_data($filters)
    {
        $whereMain = $filters['where_with_date'];
        $whereFallback = $filters['where_without_date'];
        $latestTransactionJoin = "(SELECT t.* FROM transaction t INNER JOIN (SELECT customer, MAX(id) AS max_id FROM transaction GROUP BY customer) latest ON latest.customer = t.customer AND latest.max_id = t.id)";

        $countMain = $this->mymodel->selectWithQuery("SELECT COUNT(customer.id) AS count FROM customer INNER JOIN $latestTransactionJoin transaction ON customer.id = transaction.customer WHERE $whereMain");
        $totalRows = isset($countMain[0]['count']) ? (int)$countMain[0]['count'] : 0;

        $selectedWhere = $whereMain;
        $useFallback = false;
        if ($totalRows === 0 && $whereFallback !== $whereMain) {
            $countFallback = $this->mymodel->selectWithQuery("SELECT COUNT(customer.id) AS count FROM customer INNER JOIN $latestTransactionJoin transaction ON customer.id = transaction.customer WHERE $whereFallback");
            $fallbackRows = isset($countFallback[0]['count']) ? (int)$countFallback[0]['count'] : 0;
            if ($fallbackRows > 0) {
                $selectedWhere = $whereFallback;
                $totalRows = $fallbackRows;
                $useFallback = true;
            }
        }

        $province_rows = $this->mymodel->selectWithQuery("
            SELECT TRIM(COALESCE(customer.province_text, '')) AS label, COUNT(customer.id) AS total
            FROM customer INNER JOIN $latestTransactionJoin transaction ON customer.id = transaction.customer
            WHERE $selectedWhere AND TRIM(COALESCE(customer.province_text, '')) != ''
            GROUP BY TRIM(COALESCE(customer.province_text, ''))
            ORDER BY total DESC, label ASC
            LIMIT 10
        ");

        $city_rows = $this->mymodel->selectWithQuery("
            SELECT TRIM(COALESCE(customer.city_text, '')) AS label, COUNT(customer.id) AS total
            FROM customer INNER JOIN $latestTransactionJoin transaction ON customer.id = transaction.customer
            WHERE $selectedWhere AND TRIM(COALESCE(customer.city_text, '')) != ''
            GROUP BY TRIM(COALESCE(customer.city_text, ''))
            ORDER BY total DESC, label ASC
            LIMIT 10
        ");

        $status_rows = $this->mymodel->selectWithQuery("
            SELECT TRIM(COALESCE(transaction.cb_cl, 'Tidak diketahui')) AS label, COUNT(customer.id) AS total
            FROM customer INNER JOIN $latestTransactionJoin transaction ON customer.id = transaction.customer
            WHERE $selectedWhere
            GROUP BY TRIM(COALESCE(transaction.cb_cl, 'Tidak diketahui'))
            ORDER BY total DESC, label ASC
        ");

        $new_customer_rows = $this->mymodel->selectWithQuery("
            SELECT YEAR(customer.first_order) AS period_year, WEEK(customer.first_order, 1) AS period_week, COUNT(customer.id) AS total
            FROM customer INNER JOIN $latestTransactionJoin transaction ON customer.id = transaction.customer
            WHERE $selectedWhere
                AND customer.first_order IS NOT NULL
                AND customer.first_order != ''
            GROUP BY YEAR(customer.first_order), WEEK(customer.first_order, 1)
            ORDER BY period_year ASC, period_week ASC
        ");

        $repeat_buyer_rows = $this->mymodel->selectWithQuery("
            SELECT YEAR(customer.last_order) AS period_year, WEEK(customer.last_order, 1) AS period_week, COUNT(customer.id) AS total
            FROM customer INNER JOIN $latestTransactionJoin transaction ON customer.id = transaction.customer
            WHERE $selectedWhere
                AND customer.last_order IS NOT NULL
                AND customer.last_order != ''
                AND customer.count_order >= 2
            GROUP BY YEAR(customer.last_order), WEEK(customer.last_order, 1)
            ORDER BY period_year ASC, period_week ASC
        ");

        $keluhan_rows = $this->mymodel->selectWithQuery("
            SELECT customer.id, customer.label_keluhan
            FROM customer INNER JOIN $latestTransactionJoin transaction ON customer.id = transaction.customer
            WHERE $selectedWhere
        ");

        $keluhan_counts = array();
        foreach ($keluhan_rows as $row) {
            $label_history = json_decode((string)($row['label_keluhan'] ?? ''), true);
            $customer_labels = array();

            if (is_array($label_history)) {
                foreach ($label_history as $history_row) {
                    if (is_array($history_row) && isset($history_row['labels']) && is_array($history_row['labels'])) {
                        foreach ($history_row['labels'] as $label) {
                            $label = strtolower(trim((string)$label));
                            if ($label !== '') {
                                $customer_labels[$label] = $label;
                            }
                        }
                    } else if (is_string($history_row)) {
                        $label = strtolower(trim($history_row));
                        if ($label !== '') {
                            $customer_labels[$label] = $label;
                        }
                    }
                }
            } else {
                $raw_label = strtolower(trim((string)($row['label_keluhan'] ?? '')));
                if ($raw_label !== '') {
                    $customer_labels[$raw_label] = $raw_label;
                }
            }

            foreach ($customer_labels as $label) {
                if (!isset($keluhan_counts[$label])) {
                    $keluhan_counts[$label] = 0;
                }
                $keluhan_counts[$label]++;
            }
        }

        arsort($keluhan_counts);
        $keluhan_chart_rows = array();
        foreach ($keluhan_counts as $label => $total) {
            $keluhan_chart_rows[] = array(
                'label' => ucwords($label),
                'total' => (int)$total,
            );
        }

        return array(
            'total_rows' => $totalRows,
            'no_date_filter' => $useFallback,
            'province' => $this->_crm_chart_dataset_from_rows($province_rows),
            'city' => $this->_crm_chart_dataset_from_rows($city_rows),
            'status' => $this->_crm_chart_dataset_from_rows($status_rows),
            'keluhan' => $this->_crm_chart_dataset_from_rows($keluhan_chart_rows),
            'trend_customer' => $this->_crm_merge_trend_datasets($new_customer_rows, $repeat_buyer_rows),
        );
    }

    private function _crm_chart_dataset_from_rows($rows)
    {
        $labels = array();
        $values = array();

        foreach ((array)$rows as $row) {
            $label = trim((string)($row['label'] ?? ''));
            $total = isset($row['total']) ? (int)$row['total'] : 0;
            if ($label === '' || $total <= 0) {
                continue;
            }
            $labels[] = $label;
            $values[] = $total;
        }

        return array(
            'labels' => $labels,
            'values' => $values,
        );
    }

    private function _crm_merge_trend_datasets($new_customer_rows, $repeat_buyer_rows)
    {
        $period_map = array();
        $new_map = array();
        $repeat_map = array();

        foreach ((array)$new_customer_rows as $row) {
            $period_key = $this->_crm_build_week_period_key($row);
            $total = isset($row['total']) ? (int)$row['total'] : 0;
            if ($period_key === '') {
                continue;
            }
            $period_map[$period_key] = $period_key;
            $new_map[$period_key] = $total;
        }

        foreach ((array)$repeat_buyer_rows as $row) {
            $period_key = $this->_crm_build_week_period_key($row);
            $total = isset($row['total']) ? (int)$row['total'] : 0;
            if ($period_key === '') {
                continue;
            }
            $period_map[$period_key] = $period_key;
            $repeat_map[$period_key] = $total;
        }

        $periods = array_values($period_map);
        sort($periods);

        $labels = array();
        $new_values = array();
        $repeat_values = array();

        foreach ($periods as $period_key) {
            $labels[] = $this->_crm_format_week_period_label($period_key);
            $new_values[] = isset($new_map[$period_key]) ? (int)$new_map[$period_key] : 0;
            $repeat_values[] = isset($repeat_map[$period_key]) ? (int)$repeat_map[$period_key] : 0;
        }

        return array(
            'labels' => $labels,
            'new_customer' => $new_values,
            'repeat_buyer' => $repeat_values,
        );
    }

    private function _crm_analytics_product_options($params)
    {
        $start_date = isset($params['start_date']) && trim((string)$params['start_date']) !== ''
            ? trim((string)$params['start_date'])
            : date('Y-m-01');
        $until_date = isset($params['until_date']) && trim((string)$params['until_date']) !== ''
            ? trim((string)$params['until_date'])
            : date('Y-m-d');

        $start_sql = $this->db->escape($start_date);
        $until_sql = $this->db->escape($until_date);

        return $this->mymodel->selectWithQuery("
            SELECT
                p.*,
                COALESCE(SUM(s.qty_out_pos), 0) AS qty_sold
            FROM product p
            LEFT JOIN stock s
                ON s.product = p.id
                AND DATE(s.date) >= $start_sql
                AND DATE(s.date) <= $until_sql
                AND s.type_sub = 'POS'
                AND COALESCE(s.is_adjustment, 0) = 0
                AND COALESCE(s.order_status, '') NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')
            WHERE p.status = 'Aktif'
                AND p.is_varian = 0
                AND COALESCE(p.is_operational, 0) = 0
            GROUP BY p.id
            ORDER BY qty_sold DESC, p.name ASC
        ");
    }

    private function _crm_build_week_period_key($row)
    {
        $year = isset($row['period_year']) ? (int)$row['period_year'] : 0;
        $week = isset($row['period_week']) ? (int)$row['period_week'] : 0;
        if ($year <= 0 || $week <= 0) {
            return '';
        }

        return sprintf('%04d-W%02d', $year, $week);
    }

    private function _crm_format_week_period_label($period_key)
    {
        $period_key = trim((string)$period_key);
        if (!preg_match('/^(\d{4})-W(\d{2})$/', $period_key, $matches)) {
            return $period_key;
        }

        $year = (int)$matches[1];
        $week = (int)$matches[2];
        return 'W' . $week . ' ' . $year;
    }

    private function crm_chat_initials($name)
    {
        $name = trim((string)$name);
        if ($name === '') {
            return 'NA';
        }

        $parts = preg_split('/\s+/', $name) ?: array();
        $first = isset($parts[0][0]) ? $parts[0][0] : '';
        $second = isset($parts[1][0]) ? $parts[1][0] : (isset($parts[0][1]) ? $parts[0][1] : '');
        $initials = strtoupper($first . $second);
        return $initials !== '' ? $initials : 'NA';
    }

    private function crm_chat_channel_color($marketplace)
    {
        $marketplace = strtoupper(trim((string)$marketplace));
        if ($marketplace === 'SHOPEE') {
            return '#ee4d2d';
        }
        if ($marketplace === 'TIKTOK') {
            return '#000000';
        }
        if ($marketplace === 'LAZADA') {
            return '#0f146d';
        }
        return '#1890ff';
    }

    private function crm_chat_time_label($datetime)
    {
        $datetime = trim((string)$datetime);
        if ($datetime === '' || !strtotime($datetime)) {
            return '-';
        }

        $timestamp = strtotime($datetime);
        $today = date('Y-m-d');
        if (date('Y-m-d', $timestamp) === $today) {
            return date('H:i', $timestamp);
        }
        return date('d/m', $timestamp);
    }

    function index()
    {


        $filters = $this->_crm_build_customer_filters();
        $params = $filters['params'];

        $keyword_category = $params['keyword_category'] !== '' ? $params['keyword_category'] : 'Username';
        $data['keyword_category'] = $keyword_category;
        $data['title'] = 'CRM ' . $params['brand'] . ' - ' . $this->template->title();

        $data['start_date'] = $params['start_date'];
        $data['until_date'] = $params['until_date'];
        $data['order_start_date'] = $params['order_start_date'];
        $data['order_until_date'] = $params['order_until_date'];
        $data['repeat_start_date'] = $params['repeat_start_date'];
        $data['repeat_until_date'] = $params['repeat_until_date'];
        $data['brand'] = $params['brand'];
        $data['product_filter'] = $params['product_filter'];
        $data['ids'] = implode(',', $params['ids']);
        $data['dtf'] = $params['dtf'];

        $limit = $filters['limit'];
        $current_page = $filters['current_page'];
        if ($current_page < 1) {
            $current_page = 1;
        }

        $whereMain = $filters['where_with_date'];
        $whereFallback = $filters['where_without_date'];

        $latestTransactionJoin = "(SELECT t.* FROM transaction t INNER JOIN (SELECT customer, MAX(id) AS max_id FROM transaction GROUP BY customer) latest ON latest.customer = t.customer AND latest.max_id = t.id)";
        $countTemplate = "SELECT COUNT(customer.id) as count FROM customer INNER JOIN $latestTransactionJoin transaction ON customer.id = transaction.customer WHERE ";

        $countMain = $this->mymodel->selectWithQuery($countTemplate . $whereMain);
        $totalRows = isset($countMain[0]['count']) ? (int)$countMain[0]['count'] : 0;

        $useFallback = false;
        if ($totalRows === 0 && $whereFallback !== $whereMain) {
            $countFallback = $this->mymodel->selectWithQuery($countTemplate . $whereFallback);
            $totalRows = isset($countFallback[0]['count']) ? (int)$countFallback[0]['count'] : 0;
            if ($totalRows > 0) {
                $useFallback = true;
            }
        }

        $page_count = $totalRows > 0 ? (int)ceil($totalRows / $limit) : 1;
        if ($page_count < 1) {
            $page_count = 1;
        }
        if ($current_page > $page_count) {
            $current_page = $page_count;
        }

        $data['page'] = $page_count;
        $data['no_date_filter'] = $useFallback;
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($totalRows) . ' data ditemukan!</label></p>';

        $url = base_url() . '/crm/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without_keyword_category($url);
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'ENABLE' AND is_varian = 0
        ORDER BY sku ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand");
        $data['brand'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user 
        WHERE role IN ('3')
        ORDER BY code ASC
        ");


        $data['cs'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif' AND is_varian = 0 AND is_operational = 0
        ORDER BY sku ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");

        $data['shipping'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");

        $data['marketplace'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

        $data['brands'] = $query;
        $data['stores'] = $this->mymodel->selectWithQuery("SELECT shop_id, shop_name, opt FROM marketplace_config WHERE status = 'Aktif' AND LOWER(opt) <> 'meta' ORDER BY opt ASC, shop_name ASC");
        $data['segment_options'] = array('Customer Baru', 'Repeat Buyer', 'Customer Loyal', 'Customer Pasif', 'Churn / Lost');
        $data['selected_channels'] = $params['channels'];
        $data['selected_shop_ids'] = $params['shop_ids'];
        $data['selected_shipping'] = $params['shipping_multi'];
        $data['selected_brand_multi'] = $params['brand_multi'];
        $data['selected_segment_multi'] = $params['segment_multi'];
        $data['phone_valid'] = (int)($params['phone_valid'] ?? 0);

        $data['content'] = $this->load->view("crm/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    function detail()
    {
        $data['template'] = $this->template;
        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Order ID";
        }
        $data['keyword_category'] = $keyword_category;

        $id = $_GET['id'];
        $brand = $_GET['brand'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM customer
        WHERE id = '$id'");

        $data['customer'] = $query[0];
        if (empty($data['customer'])) {
            redirect(base_url() . 'crm');
        }
        $data['title'] = 'Detail CRM - ' . $this->template->title();
        if ($_GET['start_date'] == "") {
            $start_date = "2021-01-01";
        } else {
            $start_date = $_GET['start_date'];
        }
        if ($_GET['until_date'] == "") {
            $until_date = DATE("Y-m-d");
        } else {
            $until_date = $_GET['until_date'];
        }
        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];
        $id = $_GET['id'];
        $order_status = $_GET['order_status'];

        $brand = $_GET['brand'];
        $keyword = $_GET['keyword'];

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $qry = "";
        $qry = " customer = '$id' AND DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";



        // if ($brand) {
        //     $qry .= " AND brand = '$brand' ";
        // }
        $ekspedisi = $_GET['ekspedisi'];
        if ($ekspedisi) {
            $qry .= " AND shipping = '$ekspedisi' ";
        }

        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }

        if ($cs) {
            $qry .= " AND cs = '$cs' ";
        }

        if ($order_status) {
            if ($order_status == "WEBHOOK") {
                $qry .= " AND is_webhook = 0 AND is_manual = 0";
            } else if ($order_status == "ACTIVE") {
                $qry .= " AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
            } else if ($order_status == "READY_TO_SHIP") {
                $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
            } else if ($order_status == "UNPAID") {
                $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') ";
            } else if ($order_status == "SETTLEMENT") {
                $qry .= " AND dana_pencairan > 0 AND is_disbursement > 0 ";
            } else if ($order_status == "CANCELLED") {
                $qry .= " AND order_status IN ('CANCELLED','IN_CANCEL') ";
            } else {
                $qry .= " AND order_status = '$order_status' ";
            }
        }

        if ($keyword) {
            if ($keyword_category == "Order ID") {
                $qry .= " AND order_id LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND c_username LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND customer_text LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Pelanggan") {
                $qry .= " AND phone LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Resi") {
                $qry .= " AND awb_number LIKE '%$keyword%' ";
            }
        }

        $query_2 = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM testimoni
        WHERE customer = '$id' ");

        $data_2['page'] = CEIL($query_2[0]['count'] / 30);

        $data['notif_2'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query_2[0]['count']) . ' data ditemukan!</label></p>';


        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM transaction
        WHERE $qry AND type_sub = 'POS' ");

        $data['page'] = CEIL($query[0]['count'] / 30);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $limit = 30;

        $url = base_url() . '/crm/' . $this->template->get_param();
        $data['url_1'] = $this->template->get_param_without_order_status($url);
        $data['url_2'] = $this->template->get_param_without_keyword_category($url);
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);



        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'ENABLE' AND is_varian = 0
        ORDER BY sku ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role = '3' 
        ORDER BY full_name ASC
        ");

        $data['cs'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'ENABLE' AND is_varian = 0
        ORDER BY sku ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");

        $data['shipping'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");

        $data['marketplace'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

        $data['brands'] = $query;

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;
        $data['content'] = $this->load->view("crm/detail", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    function item_transaction()
    {
        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = '2021-01-01';
        }
        if ($_GET['until_date']) {
            $until_date = $_GET['until_date'];
        } else {
            $until_date = DATE('Y-m-d');
        }
        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];
        $id = $_GET['id'];
        $order_status = $_GET['order_status'];
        $keyword_category = $_GET['keyword_category'];

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role = '3' 
        ORDER BY full_name ASC
        ");

        $data['cs'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'ENABLE' AND is_varian = 0
        ORDER BY sku ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");

        $data['shipping'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");

        $data['marketplace'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY code ASC");

        $data['brands'] = $query;

        $qry = "";
        $qry = " customer = '$id' AND DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";



        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }
        $ekspedisi = $_GET['ekspedisi'];
        if ($ekspedisi) {
            $qry .= " AND shipping = '$ekspedisi' ";
        }

        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }

        if ($cs) {
            $qry .= " AND cs = '$cs' ";
        }

        if ($order_status) {
            if ($order_status == "WEBHOOK") {
                $qry .= " AND is_webhook = 0 AND is_manual = 0";
            } else if ($order_status == "ACTIVE") {
                $qry .= " AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
            } else if ($order_status == "READY_TO_SHIP") {
                $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
            } else if ($order_status == "UNPAID") {
                $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') ";
            } else if ($order_status == "SETTLEMENT") {
                $qry .= " AND dana_pencairan > 0 AND is_disbursement > 0 ";
            } else if ($order_status == "CANCELLED") {
                $qry .= " AND order_status IN ('CANCELLED','IN_CANCEL') ";
            } else {
                $qry .= " AND order_status = '$order_status' ";
            }
        }

        if ($keyword) {
            if ($keyword_category == "Order ID") {
                $qry .= " AND order_id LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND c_username LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND customer_text LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Pelanggan") {
                $qry .= " AND phone LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Resi") {
                $qry .= " AND awb_number LIKE '%$keyword%' ";
            }
        }

        $limit = 30;

        $current_page = $_GET['page'];

        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction
        WHERE $qry AND type_sub = 'POS' 
        ORDER BY date DESC, id DESC
        LIMIT $offset, $limit
        ");


        $data['data'] = $query;

        $data['start'] = $offset;
        $this->load->view("crm/item_transaction", $data);
    }

    private function _crm_allowed_filter_columns()
    {
        return array(
            'full_name'   => '`customer`.`full_name`',
            'phone'       => '`customer`.`phone`',
            'marketplace' => '`customer`.`marketplace`',
            'shipping'    => '`transaction`.`shipping`',
            'shop_id'     => '`transaction`.`shop_id`',
            'username'    => '`customer`.`username`',
            'address'     => '`customer`.`address`',
            'city_text'   => '`customer`.`city_text`',
            'description' => '`customer`.`desc`',
            'gift'        => '`customer`.`gift`',
            'testimoni'   => '`customer`.`testimoni`',
            'created_at'  => '`customer`.`created_at`',
            'first_order' => '`customer`.`first_order`',
            'last_order'  => '`customer`.`last_order`',
            'count_order' => '`customer`.`count_order`',
            'cs'          => '`customer`.`cs`',
            'brand'       => '`customer`.`brand`',
            'cb_cl'       => '`transaction`.`cb_cl`',
            'is_manual'   => '`customer`.`is_manual`',
            'id_buyer'    => '`customer`.`id_buyer`',
            'shop_name'   => '`customer`.`shop_name`',
            'product_names' => '`customer`.`pesanan`',
            'product_qtys' => '`customer`.`pesanan`',
            'keluhan' => '`customer`.`keluhan`',
            'customer_experience' => '`customer`.`customer_experience`',
            'join_komunitas' => '`customer`.`join_komunitas`',
            'campaign_broadcast' => '`customer`.`campaign_broadcast`',
            'no_pesanan' => '`customer`.`pesanan`',
        );
    }

    private function _crm_extract_pesanan_items($pesananRaw)
    {
        $items = array();
        if (!is_string($pesananRaw) || trim($pesananRaw) === '') {
            return $items;
        }

        $decoded = json_decode($pesananRaw, true);
        if (!is_array($decoded)) {
            return $items;
        }

        foreach ($decoded as $orderRow) {
            if (!is_array($orderRow)) {
                continue;
            }

            if (isset($orderRow['data']) && is_array($orderRow['data'])) {
                $dataRows = $orderRow['data'];
            } else {
                $dataRows = array($orderRow);
            }

            foreach ($dataRows as $itemRow) {
                if (!is_array($itemRow)) {
                    continue;
                }

                $name = '';
                if (isset($itemRow['item_name'])) {
                    $name = trim((string)$itemRow['item_name']);
                }
                if ($name === '' && isset($itemRow['product_text'])) {
                    $name = trim((string)$itemRow['product_text']);
                }

                $qty = null;
                if (isset($itemRow['qty'])) {
                    $qty = $itemRow['qty'];
                }

                $items[] = array(
                    'name' => $name,
                    'qty' => $qty,
                );
            }
        }

        return $items;
    }

    private function _crm_build_customer_filters()
    {
        $input = $this->input;
        $db = $this->db;
        $normalize_list_param = function ($param) {
            $values = array();
            if (is_array($param)) {
                foreach ($param as $rawVal) {
                    $rawVal = trim((string)$rawVal);
                    if ($rawVal === '') {
                        continue;
                    }
                    $values[$rawVal] = $rawVal;
                }
            } else if (is_string($param) && trim($param) !== '') {
                $single = trim($param);
                $values[$single] = $single;
            }
            return array_values($values);
        };
        $build_in_condition = function ($columnExpr, $rawValues) use ($db) {
            if (empty($rawValues)) {
                return '';
            }
            $escapedValues = array();
            foreach ($rawValues as $rawValue) {
                $escaped = $db->escape_str($rawValue);
                $escapedValues[] = "'$escaped'";
            }
            if (empty($escapedValues)) {
                return '';
            }
            return "$columnExpr IN (" . implode(',', $escapedValues) . ")";
        };

        $allowedColumns = $this->_crm_allowed_filter_columns();

        $start_date_raw = $input->get('start_date') ?: date('Y-m-01');
        $until_date_raw = $input->get('until_date') ?: date('Y-m-d');
        $brand_raw = trim((string)$input->get('brand'));
        $marketplace_raw = trim((string)$input->get('marketplace'));
        $cs_raw = trim((string)$input->get('cs'));
        $cb_cl_raw = trim((string)$input->get('cb_cl'));
        $product_filter_param = $input->get('product_filter');
        $product_filter_values = array();
        if (is_array($product_filter_param)) {
            foreach ($product_filter_param as $pfVal) {
                $pfVal = trim((string)$pfVal);
                if ($pfVal === '') {
                    continue;
                }
                $product_filter_values[] = $pfVal;
            }
        } else if (is_string($product_filter_param) && trim($product_filter_param) !== '') {
            $product_filter_values[] = trim($product_filter_param);
        }
        $sort = (string)$input->get('sort');
        $keyword_raw = trim((string)$input->get('keyword'));
        $keyword_category = (string)$input->get('keyword_category');
        $ids_raw = trim((string)$input->get('ids'));
        $order_start_date_raw = trim((string)$input->get('order_start_date'));
        $order_until_date_raw = trim((string)$input->get('order_until_date'));
        $repeat_start_date_raw = trim((string)$input->get('repeat_start_date'));
        $repeat_until_date_raw = trim((string)$input->get('repeat_until_date'));
        $phone_valid = (int)($input->get('phone_valid') ?? 0) === 1;
        $channel_values = $normalize_list_param($input->get('channel'));
        $shop_id_values = $normalize_list_param($input->get('shop_id'));
        $shipping_values = $normalize_list_param($input->get('shipping'));
        $brand_values = $normalize_list_param($input->get('brand_filter'));
        $segment_values = $normalize_list_param($input->get('segment'));
        $current_page = (int)$input->get('page');
        if ($current_page < 1) {
            $current_page = 1;
        }

        $conditionsWithDate = array('1=1');
        $conditionsNoDate = array('1=1');
        $order_by = 'ORDER BY `customer`.`created_at` DESC';

        $start_date_sql = $db->escape_str($start_date_raw);
        $until_date_sql = $db->escape_str($until_date_raw);
        $today_sql = $db->escape_str(date('Y-m-d'));

        // Date-based conditions depending on sort
        if ($sort === 'FU H+10') {
            $conditionsWithDate[] = "DATE(`customer`.`waktu_fu_perkembangan`) != '' AND DATE(`customer`.`waktu_fu_perkembangan`) <= '$today_sql'";
            $conditionsNoDate[] = "DATE(`customer`.`waktu_fu_perkembangan`) != '' AND DATE(`customer`.`waktu_fu_perkembangan`) <= '$today_sql'";
            $order_by = "ORDER BY DATE(`customer`.`waktu_fu_perkembangan`) DESC";
        } else if ($sort === 'FU H-7') {
            $conditionsWithDate[] = "DATE(`customer`.`waktu_fu_ro`) != '' AND DATE(`customer`.`waktu_fu_ro`) <= '$today_sql'";
            $conditionsNoDate[] = "DATE(`customer`.`waktu_fu_ro`) != '' AND DATE(`customer`.`waktu_fu_ro`) <= '$today_sql'";
            $order_by = "ORDER BY `customer`.`waktu_fu_ro` DESC";
        } else if ($sort === 'Tidak Repeat Order') {
            $conditionsWithDate[] = "(DATE(`customer`.`first_order`) = DATE(`customer`.`last_order`) OR DATE(`customer`.`last_order`) = '')";
            $conditionsNoDate[] = "(DATE(`customer`.`first_order`) = DATE(`customer`.`last_order`) OR DATE(`customer`.`last_order`) = '')";
            $order_by = "ORDER BY `customer`.`first_order` DESC";
        } else if ($sort === 'Tidak Repeat Order H+30') {
            $conditionsWithDate[] = "DATE(`customer`.`last_order`) <= '" . $db->escape_str(date('Y-m-d', strtotime('-30 days'))) . "'";
            $conditionsNoDate[] = "DATE(`customer`.`last_order`) <= '" . $db->escape_str(date('Y-m-d', strtotime('-30 days'))) . "'";
            $order_by = "ORDER BY `customer`.`last_order` DESC";
        } else if ($sort === 'Tanggal Dibuat') {
            $conditionsWithDate[] = "DATE(`customer`.`created_at`) >= '$start_date_sql' AND DATE(`customer`.`created_at`) <= '$until_date_sql'";
            $order_by = "ORDER BY `customer`.`created_at` DESC";
        } else {
            $conditionsWithDate[] = "DATE(`customer`.`first_order`) >= '$start_date_sql' AND DATE(`customer`.`first_order`) <= '$until_date_sql'";
        }

        if (!empty($channel_values)) {
            $channelExprs = array();
            foreach ($channel_values as $channelValue) {
                $channelLower = strtolower($channelValue);
                if ($channelLower === 'shopee') {
                    $channelExprs[] = "LOWER(`customer`.`marketplace`) LIKE '%shopee%'";
                } else if ($channelLower === 'tiktok') {
                    $channelExprs[] = "LOWER(`customer`.`marketplace`) LIKE '%tiktok%'";
                } else if ($channelLower === 'lazada') {
                    $channelExprs[] = "LOWER(`customer`.`marketplace`) LIKE '%lazada%'";
                } else if ($channelLower === 'wa') {
                    $channelExprs[] = "(LOWER(`customer`.`marketplace`) LIKE '%whatsapp%' OR LOWER(`customer`.`marketplace`) = 'wa')";
                }
            }
            if (!empty($channelExprs)) {
                $channelCondition = '(' . implode(' OR ', $channelExprs) . ')';
                $conditionsWithDate[] = $channelCondition;
                $conditionsNoDate[] = $channelCondition;
            }
        } else if ($marketplace_raw !== '') {
            $marketplace_sql = $db->escape_str($marketplace_raw);
            $conditionsWithDate[] = "`customer`.`marketplace` = '$marketplace_sql'";
            $conditionsNoDate[] = "`customer`.`marketplace` = '$marketplace_sql'";
        }

        if (!empty($brand_values)) {
            $brandValuesNormalized = array();
            foreach ($brand_values as $brandValue) {
                $brandUpper = strtoupper(trim((string)$brandValue));
                if ($brandUpper === '') {
                    continue;
                }
                $brandValuesNormalized[$brandUpper] = $brandUpper;
            }
            $brandValuesNormalized = array_values($brandValuesNormalized);
            if (!empty($brandValuesNormalized) && !in_array('ALL', $brandValuesNormalized, true)) {
                $brandCondition = $build_in_condition("UPPER(`customer`.`brand`)", $brandValuesNormalized);
                if ($brandCondition !== '') {
                    $conditionsWithDate[] = $brandCondition;
                    $conditionsNoDate[] = $brandCondition;
                }
            }
        } else if ($brand_raw !== '') {
            $brand_sql = $db->escape_str($brand_raw);
            $conditionsWithDate[] = "`customer`.`brand` = '$brand_sql'";
            $conditionsNoDate[] = "`customer`.`brand` = '$brand_sql'";
        }

        if (!empty($segment_values)) {
            $segmentCondition = $build_in_condition("`transaction`.`cb_cl`", $segment_values);
            if ($segmentCondition !== '') {
                $conditionsWithDate[] = $segmentCondition;
                $conditionsNoDate[] = $segmentCondition;
            }
        } else if ($cb_cl_raw !== '') {
            $cb_cl_sql = $db->escape_str($cb_cl_raw);
            $conditionsWithDate[] = "`transaction`.`cb_cl` = '$cb_cl_sql'";
            $conditionsNoDate[] = "`transaction`.`cb_cl` = '$cb_cl_sql'";
        }

        if (!empty($shop_id_values)) {
            $shopCondition = $build_in_condition("`transaction`.`shop_id`", $shop_id_values);
            if ($shopCondition !== '') {
                $conditionsWithDate[] = $shopCondition;
                $conditionsNoDate[] = $shopCondition;
            }
        }

        if (!empty($shipping_values)) {
            $shippingCondition = $build_in_condition("`transaction`.`shipping`", $shipping_values);
            if ($shippingCondition !== '') {
                $conditionsWithDate[] = $shippingCondition;
                $conditionsNoDate[] = $shippingCondition;
            }
        }

        if ($cs_raw !== '') {
            $cs_sql = $db->escape_str($cs_raw);
            $conditionsWithDate[] = "`customer`.`cs` = '$cs_sql'";
            $conditionsNoDate[] = "`customer`.`cs` = '$cs_sql'";
        }

        if ($phone_valid) {
            $phoneValidCondition = "TRIM(COALESCE(`customer`.`phone`, '')) != '' AND `customer`.`phone` NOT LIKE '%*%'";
            $conditionsWithDate[] = $phoneValidCondition;
            $conditionsNoDate[] = $phoneValidCondition;
        }

        if ($order_start_date_raw !== '') {
            $order_start_sql = $db->escape_str($order_start_date_raw);
            $conditionsWithDate[] = "DATE(`customer`.`first_order`) >= '$order_start_sql'";
            $conditionsNoDate[] = "DATE(`customer`.`first_order`) >= '$order_start_sql'";
        }
        if ($order_until_date_raw !== '') {
            $order_until_sql = $db->escape_str($order_until_date_raw);
            $conditionsWithDate[] = "DATE(`customer`.`first_order`) <= '$order_until_sql'";
            $conditionsNoDate[] = "DATE(`customer`.`first_order`) <= '$order_until_sql'";
        }

        if ($repeat_start_date_raw !== '') {
            $repeat_start_sql = $db->escape_str($repeat_start_date_raw);
            $conditionsWithDate[] = "DATE(`customer`.`last_order`) >= '$repeat_start_sql'";
            $conditionsNoDate[] = "DATE(`customer`.`last_order`) >= '$repeat_start_sql'";
        }
        if ($repeat_until_date_raw !== '') {
            $repeat_until_sql = $db->escape_str($repeat_until_date_raw);
            $conditionsWithDate[] = "DATE(`customer`.`last_order`) <= '$repeat_until_sql'";
            $conditionsNoDate[] = "DATE(`customer`.`last_order`) <= '$repeat_until_sql'";
        }

        if (!empty($product_filter_values)) {
            $productLikes = array();
            foreach ($product_filter_values as $productValue) {
                $likeExpr = $db->escape_like_str($productValue);
                $productLikes[] = "`customer`.`pesanan` LIKE '%$likeExpr%' ESCAPE '!'";
            }
            if (!empty($productLikes)) {
                $combined = '(' . implode(' OR ', $productLikes) . ')';
                $conditionsWithDate[] = $combined;
                $conditionsNoDate[] = $combined;
            }
        }

        $ids_clean = array();
        if ($ids_raw !== '') {
            foreach (explode(',', $ids_raw) as $idPart) {
                $idPart = trim($idPart);
                if ($idPart === '') {
                    continue;
                }
                $idVal = (int)$idPart;
                if ($idVal > 0) {
                    $ids_clean[] = $idVal;
                }
            }
            if (!empty($ids_clean)) {
                $ids_in = implode(',', $ids_clean);
                $conditionsWithDate[] = "`customer`.`id` IN ($ids_in)";
                $conditionsNoDate[] = "`customer`.`id` IN ($ids_in)";
            }
        }

        if ($keyword_raw !== '') {
            $keyword_sql = $db->escape_like_str($keyword_raw);
            switch ($keyword_category) {
                case 'Username':
                    $conditionsWithDate[] = "`customer`.`username` LIKE '%$keyword_sql%' ESCAPE '!'";
                    $conditionsNoDate[] = "`customer`.`username` LIKE '%$keyword_sql%' ESCAPE '!'";
                    break;
                case 'Nama Customer':
                    $conditionsWithDate[] = "`customer`.`full_name` LIKE '%$keyword_sql%' ESCAPE '!'";
                    $conditionsNoDate[] = "`customer`.`full_name` LIKE '%$keyword_sql%' ESCAPE '!'";
                    break;
                case 'Order ID':
                    $conditionsWithDate[] = "`customer`.`order_id` LIKE '%$keyword_sql%' ESCAPE '!'";
                    $conditionsNoDate[] = "`customer`.`order_id` LIKE '%$keyword_sql%' ESCAPE '!'";
                    break;
                case 'Nomer Pesanan':
                    $conditionsWithDate[] = "`customer`.`pesanan` LIKE '%$keyword_sql%' ESCAPE '!'";
                    $conditionsNoDate[] = "`customer`.`pesanan` LIKE '%$keyword_sql%' ESCAPE '!'";
                    break;
                case 'Nama Produk':
                    $conditionsWithDate[] = "`customer`.`pesanan` LIKE '%$keyword_sql%' ESCAPE '!'";
                    $conditionsNoDate[] = "`customer`.`pesanan` LIKE '%$keyword_sql%' ESCAPE '!'";
                    break;
            }
        }

        $activeFilters = array();
        $appliedLike = array();

        $addLikeCondition = function ($fieldKey, $value) use (&$conditionsWithDate, &$conditionsNoDate, &$allowedColumns, &$activeFilters, &$appliedLike, $db) {
            if ($value === '' && $value !== '0') {
                return;
            }
            if (!isset($allowedColumns[$fieldKey])) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $fieldKey)) {
                    return;
                }
                $allowedColumns[$fieldKey] = "`customer`.`$fieldKey`";
            }
            $columnExpr = $allowedColumns[$fieldKey];
            $escaped = $db->escape_like_str($value);
            $key = $fieldKey . '|' . $escaped;
            if (isset($appliedLike[$key])) {
                return;
            }
            $appliedLike[$key] = true;
            $conditionsWithDate[] = "$columnExpr LIKE '%$escaped%' ESCAPE '!'";
            $conditionsNoDate[] = "$columnExpr LIKE '%$escaped%' ESCAPE '!'";
            $activeFilters[] = array('field' => $fieldKey, 'value' => $value);
        };

        $dtf_clean = array();
        $dtf_raw = $input->get('dtf');
        if (is_array($dtf_raw)) {
            foreach ($dtf_raw as $key => $value) {
                $value = trim((string)$value);
                if ($value === '' && $value !== '0') {
                    continue;
                }
                if (!isset($allowedColumns[$key]) && !preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                    continue;
                }
                $dtf_clean[$key] = $value;
                $addLikeCondition($key, $value);
            }
        }

        $filter_fields = $input->get('filter_field');
        $filter_values = $input->get('filter_value');
        if (is_array($filter_fields) && is_array($filter_values)) {
            foreach ($filter_fields as $index => $fieldKey) {
                $valueRaw = isset($filter_values[$index]) ? trim((string)$filter_values[$index]) : '';
                if ($valueRaw === '' && $valueRaw !== '0') {
                    continue;
                }
                $addLikeCondition((string)$fieldKey, $valueRaw);
            }
        }

        return array(
            'where_with_date' => implode(' AND ', $conditionsWithDate),
            'where_without_date' => implode(' AND ', $conditionsNoDate),
            'order_by' => $order_by,
            'limit' => 30,
            'current_page' => $current_page,
            'active_filters' => $activeFilters,
            'allowed_columns' => $allowedColumns,
            'params' => array(
                'start_date' => $start_date_raw,
                'until_date' => $until_date_raw,
                'brand' => $brand_raw,
                'marketplace' => $marketplace_raw,
                'cs' => $cs_raw,
                'cb_cl' => $cb_cl_raw,
                'ids' => $ids_clean,
                'sort' => $sort,
                'keyword' => $keyword_raw,
                'keyword_category' => $keyword_category,
                'dtf' => $dtf_clean,
                'order_start_date' => $order_start_date_raw,
                'order_until_date' => $order_until_date_raw,
                'repeat_start_date' => $repeat_start_date_raw,
                'repeat_until_date' => $repeat_until_date_raw,
                'phone_valid' => $phone_valid ? 1 : 0,
                'product_filter' => $product_filter_values,
                'channels' => $channel_values,
                'shop_ids' => $shop_id_values,
                'shipping_multi' => $shipping_values,
                'brand_multi' => $brand_values,
                'segment_multi' => $segment_values,
            ),
        );
    }

    function item()
    {
        $data['template'] = $this->template;

        $filters = $this->_crm_build_customer_filters();
        $params = $filters['params'];

        $data['start_date'] = $params['start_date'];
        $data['until_date'] = $params['until_date'];
        $data['brand'] = $params['brand'];
        $data['ids'] = implode(',', $params['ids']);
        $data['dtf'] = $params['dtf'];
        $data['keyword'] = $params['keyword'];
        $data['keyword_category'] = $params['keyword_category'];
        $data['sort'] = $params['sort'];
        $data['product_filter'] = $params['product_filter'];

        $data['cs'] = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role = '3' ORDER BY full_name ASC");
        $data['group_wa'] = $this->mymodel->selectWithQuery("SELECT * FROM group_wa WHERE status = 'ENABLE' ORDER BY CAST(name AS SIGNED) ASC");
        $data['shipping'] = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");
        $data['marketplace'] = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");
        $data['brands'] = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY code ASC");

        $limit = $filters['limit'];
        $current_page = $filters['current_page'];
        if ($current_page < 1) {
            $current_page = 1;
        }

        $offset = ($current_page - 1) * $limit;
        if ($offset < 0) {
            $offset = 0;
        }

        $whereMain = $filters['where_with_date'];
        $whereFallback = $filters['where_without_date'];
        $order_by = $filters['order_by'];

        $countMain = $this->mymodel->selectWithQuery("SELECT COUNT(customer.id) as count FROM customer INNER JOIN (SELECT t.* FROM transaction t INNER JOIN (SELECT customer, MAX(id) AS max_id FROM transaction GROUP BY customer) latest ON latest.customer = t.customer AND latest.max_id = t.id) transaction ON customer.id = transaction.customer WHERE $whereMain");
        $totalRows = isset($countMain[0]['count']) ? (int)$countMain[0]['count'] : 0;

        $page_count = ($totalRows > 0) ? (int)ceil($totalRows / $limit) : 1;
        if ($page_count < 1) {
            $page_count = 1;
        }
        if ($current_page > $page_count) {
            $current_page = $page_count;
            $offset = ($current_page - 1) * $limit;
            if ($offset < 0) {
                $offset = 0;
            }
        }

        $useFallback = false;
        if ($totalRows > 0) {
            $queryRows = $this->mymodel->selectWithQuery("SELECT customer.*, transaction.cb_cl AS customer_label, (SELECT ccb.id FROM crm_campaign_broadcast ccb WHERE ccb.name = SUBSTRING_INDEX(customer.campaign_broadcast, ',', -1) AND ccb.status = 'ENABLE' ORDER BY ccb.id DESC LIMIT 1) AS campaign_broadcast_id FROM customer INNER JOIN (SELECT t.* FROM transaction t INNER JOIN (SELECT customer, MAX(id) AS max_id FROM transaction GROUP BY customer) latest ON latest.customer = t.customer AND latest.max_id = t.id) transaction ON customer.id = transaction.customer WHERE $whereMain $order_by LIMIT $offset, $limit");
        } else {
            if ($whereFallback !== $whereMain) {
                $countFallback = $this->mymodel->selectWithQuery("SELECT COUNT(customer.id) as count FROM customer INNER JOIN (SELECT t.* FROM transaction t INNER JOIN (SELECT customer, MAX(id) AS max_id FROM transaction GROUP BY customer) latest ON latest.customer = t.customer AND latest.max_id = t.id) transaction ON customer.id = transaction.customer WHERE $whereFallback");
                $totalRows = isset($countFallback[0]['count']) ? (int)$countFallback[0]['count'] : 0;
                $page_count = ($totalRows > 0) ? (int)ceil($totalRows / $limit) : 1;
                if ($page_count < 1) {
                    $page_count = 1;
                }
                if ($current_page > $page_count) {
                    $current_page = $page_count;
                }
                $offset = ($current_page - 1) * $limit;
                if ($offset < 0) {
                    $offset = 0;
                }
                $queryRows = $this->mymodel->selectWithQuery("SELECT customer.*, transaction.cb_cl AS customer_label, (SELECT ccb.id FROM crm_campaign_broadcast ccb WHERE ccb.name = SUBSTRING_INDEX(customer.campaign_broadcast, ',', -1) AND ccb.status = 'ENABLE' ORDER BY ccb.id DESC LIMIT 1) AS campaign_broadcast_id FROM customer INNER JOIN (SELECT t.* FROM transaction t INNER JOIN (SELECT customer, MAX(id) AS max_id FROM transaction GROUP BY customer) latest ON latest.customer = t.customer AND latest.max_id = t.id) transaction ON customer.id = transaction.customer WHERE $whereFallback $order_by LIMIT $offset, $limit");
                $useFallback = true;
            } else {
                $page_count = 1;
                $current_page = 1;
                $offset = 0;
                $queryRows = array();
            }
        }


        $data['no_date_filter'] = $useFallback;
        $data['data'] = $queryRows;
        $data['start'] = $offset;
        $data['page'] = $page_count;
        $data['current_page'] = $current_page;
        $data['total_rows'] = $totalRows;

        $allowed_view_modes = array('card', 'table');
        $current_view = isset($_GET['view']) && in_array($_GET['view'], $allowed_view_modes, true) ? $_GET['view'] : 'table';
        $data['view_mode'] = $current_view;

        $this->load->view("crm/item", $data);
    }
    

    public function filter_values()
    {
        header('Content-Type: application/json');

        $filters = $this->_crm_build_customer_filters();
        $allowed = $filters['allowed_columns'];

        $field = $this->input->get('field');
        if (!$field || !isset($allowed[$field])) {
            echo json_encode(['ok' => false, 'error' => 'Field not allowed']);
            return;
        }

        $columnExpr = $allowed[$field];
        $where = $filters['where_with_date'];

        if ($field === 'product_names' || $field === 'product_qtys' || $field === 'no_pesanan') {
            $rows = $this->mymodel->selectWithQuery("SELECT `customer`.`pesanan` AS pesanan FROM customer INNER JOIN transaction ON customer.id = transaction.customer WHERE $where");
            $values = array();

            foreach ($rows as $row) {
                $pesananRaw = isset($row['pesanan']) ? $row['pesanan'] : '';
                if ($field === 'no_pesanan') {
                    $decoded = json_decode($pesananRaw, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $orderRow) {
                            if (!is_array($orderRow)) {
                                continue;
                            }
                            $orderId = trim((string)($orderRow['order_id'] ?? ''));
                            if ($orderId !== '') {
                                $values[] = $orderId;
                            }
                        }
                    }
                } else {
                    $items = $this->_crm_extract_pesanan_items($pesananRaw);
                    foreach ($items as $item) {
                        if ($field === 'product_names') {
                            $name = trim((string)($item['name'] ?? ''));
                            if ($name !== '') {
                                $values[] = $name;
                            }
                        } else {
                            $qty = $item['qty'] ?? null;
                            if ($qty !== null && $qty !== '') {
                                $values[] = (string)$qty;
                            }
                        }
                    }
                }
            }

            $values = array_values(array_unique($values));
            natcasesort($values);
            $values = array_values($values);

            echo json_encode(['ok' => true, 'field' => $field, 'values' => $values]);
            return;
        }

        $sql = "SELECT DISTINCT $columnExpr AS val FROM customer INNER JOIN transaction ON customer.id = transaction.customer WHERE $where ORDER BY val ASC";
        $rows = $this->mymodel->selectWithQuery($sql);

        $values = array();
        foreach ($rows as $row) {
            if (!array_key_exists('val', $row)) {
                continue;
            }
            $val = $row['val'];
            if ($val === null) {
                $values[] = '';
            } else {
                $values[] = (string)$val;
            }
        }

        $values = array_values(array_unique($values));

        echo json_encode(['ok' => true, 'field' => $field, 'values' => $values]);
    }


    function update_item()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];
        $dt = $_POST['dt'];


        if ($dt['first_trx']) {
            $dt['first_trx'] = DATE("Y-m-d H:i:s", strtotime($dt['first_trx']));
        }
        // if($dt['join']){
        //     $dt['join'] = DATE("Y-m-d H:i:s", strtotime($dt['join']));
        // }
        if ($dt['last_order']) {
            $dt['last_order'] = DATE("Y-m-d H:i:s", strtotime($dt['last_order']));
        }

        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];

        if ($_POST['column'] == 'dt[first_order]') {
            $dt['order_month'] = $this->template->month_format_indo($dt['first_order']);
        }

        if ($_POST['column'] == 'dt[join]') {
            $dt['count_fu'] = 0;
            $dt['last_fu_at'] = '';
            $dt['count_fu_2'] = 0;
            $dt['last_fu_2_at'] = '';
        }
        if (in_array($_POST['column'], array('dt[join]', 'dt[masa_join]'))) {
            if ($dt['join'] && $dt['masa_join']) {
                $dt['batas_join'] = date('Y-m-d', strtotime($dt['join'] . " +" . $dt['masa_join'] . " days"));
                $dt['waktu_fu_ro'] = date('Y-m-d', strtotime($dt['batas_join'] . " -7 days"));
                $dt['waktu_fu_perkembangan'] = date('Y-m-d', strtotime($dt['join'] . " +10 days"));
            }
        }


        $this->db->update('customer', $dt, array('id' => $id));


        if ($_POST['column'] == 'dt[grup]') {
            $query = $this->mymodel->selectWithQuery("SELECT grup FROM customer WHERE id = '$id'");

            $latest_group = $query[0]['grup'];

            $grup = $latest_group;
            if ($grup) {
                $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM customer WHERE grup = '$grup'");

                $query = $query[0];
                $dte = array();
                $dte['customer'] = $query['count'];
                $model2 = $this->db->table('group_wa');
                $model2->where('name', $grup);
                $model2->update($dte);
            }
            $grup = $dt['grup'];
            if ($grup) {
                $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM customer WHERE grup = '$grup'");

                $query = $query[0];
                $dte = array();
                $dte['customer'] = $query['count'];
                $model3 = $this->db->table('group_wa');
                $model3->where('name', $grup);
                $model3->update($dte);
            }
        }
        $msg = 'Update data berhasil!';;

        $html = array();
        $html['status'] = true;
        $html['order_month'] = strval($dt['order_month']);
        $html['batas_join'] = strval($dt['batas_join']);
        $html['waktu_fu_ro'] = strval($dt['waktu_fu_ro']);
        $html['waktu_fu_perkembangan'] = strval($dt['waktu_fu_perkembangan']);
        $html['msg'] = $this->template->alert_success($msg);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($html, true);
    }

    // Di controller CRM
    public function get_order_history()
    {
        $id = $this->input->post('id');
        
        $history = $this->mymodel->selectWithQuery("SELECT *
            FROM transaction 
            WHERE customer = $id
            ORDER BY date DESC");
        
        if ($history) {
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Tidak ada history order'
            ]);
        }
    }

    function edit()
    {
        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM customer WHERE id = '$id'");

        $data['data'] = $query[0];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user ORDER BY full_name ASC");

        $data['pic'] = $query;

        $data['marketplace'] = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace
        ORDER BY name ASC");

        $data['cs'] = $this->mymodel->selectWithQuery("SELECT *
        FROM user
        ORDER BY full_name ASC");

        $data['campaign_options'] = $this->mymodel->selectWithQuery("SELECT id, name FROM crm_campaign_broadcast WHERE status = 'ENABLE' ORDER BY name ASC");
        $selectedCampaignId = 0;
        $campaignListRaw = trim((string)($data['data']['campaign_broadcast'] ?? ''));
        if ($campaignListRaw !== '') {
            $campaignParts = array_values(array_filter(array_map('trim', explode(',', $campaignListRaw)), 'strlen'));
            if (!empty($campaignParts)) {
                $lastNameSql = $this->db->escape_str(end($campaignParts));
                $campaignMap = $this->mymodel->selectWithQuery("SELECT id FROM crm_campaign_broadcast WHERE name = '$lastNameSql' LIMIT 1");
                if (!empty($campaignMap)) {
                    $selectedCampaignId = (int)$campaignMap[0]['id'];
                }
            }
        }
        $data['selected_campaign_id'] = $selectedCampaignId;


        // $query = $this->mymodel->selectWithQuery("SELECT * FROM tag ORDER BY title ASC");
        // 
        // $data['tag'] = $query;

        $this->load->view("crm/edit", $data);
    }

    function update()
    {
        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        if (!isset($dt['join_komunitas'])) {
            $dt['join_komunitas'] = '0';
        }
        $dt['join_komunitas'] = in_array(strtolower((string)$dt['join_komunitas']), array('1', 'true', 'yes', 'ya', 'on'), true) ? '1' : '0';

        $campaignId = isset($_POST['campaign_broadcast_id']) ? intval($_POST['campaign_broadcast_id']) : 0;
        $campaignToAttach = null;
        if ($campaignId > 0) {
            $campaignRow = $this->mymodel->selectWithQuery("SELECT id, name FROM crm_campaign_broadcast WHERE id = '$campaignId' LIMIT 1");
            if (!empty($campaignRow)) {
                $campaignToAttach = $campaignRow[0];
            }
        }
        // campaign_broadcast holds the comma-separated list of ALL campaigns a customer belongs to;
        // it is managed via crm_campaign_attach_customers(). Never overwrite the whole list from the form.
        unset($dt['campaign_broadcast']);

        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];



        $query = $this->mymodel->selectWithQuery("SELECT id FROM transaction WHERE customer = '$id' LIMIT 1");

        $id_trx = $query[0]['id'];

        if ($_FILES['file']['name']) {
            $input = $this->validate([
                'file' => [
                    'uploaded[file]',
                    'mime_in[file,image/jpg,image/jpeg,image/png]',
                    'max_size[file,1024]',
                ]
            ]);

            if (!$input) {
                $msg = 'Pastikan tipe file .jpg, .jpeg atau .png!';
                echo $this->template->alert_danger($msg);
                die;
            } else {
                $img = $this->request->getFile('file');
                $dir = str_replace('public/', '', FCPATH . 'assets/img/crm/');
                $img->move($dir);
                $data = [
                    'name' =>  $img->getName(),
                    'type'  => $img->getClientMimeType()
                ];
                $currentFileName = $dir . $data['name'];
                $newfile = $id . '.' . substr(strrchr($data['name'], "."), 1);
                $newFileName = $dir . $newfile;
                rename($currentFileName, $newFileName);
                $dt['img'] = $newfile;
            }
        }

        if ($this->db->update('customer', $dt, array('id' => $id))) {
            if ($campaignToAttach) {
                $this->crm_campaign_attach_customers((string)$campaignToAttach['name'], array((int)$id));
            }
            $msg = 'Update data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function crm_campaign_options()
    {
        header('Content-Type: application/json; charset=utf-8');
        $rows = $this->mymodel->selectWithQuery("
            SELECT id, name, broadcast_type, message_html, message_text, poll_name, poll_choices, poll_select, attachment_file, attachment_original_name, attachment_mime, attachment_size, delay_value, country_code, include_sheets, sheets_spreadsheet_id, sheets_sheet_name
            FROM crm_campaign_broadcast
            WHERE status = 'ENABLE'
            ORDER BY name ASC
        ");
        echo json_encode(array('status' => true, 'data' => $rows));
    }

    public function crm_campaign_detail()
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = intval($this->input->get('id'));
        if ($id <= 0) {
            echo json_encode(array('status' => false, 'msg' => 'Campaign tidak valid.'));
            return;
        }

        $rows = $this->mymodel->selectWithQuery("
            SELECT id, name, broadcast_type, message_html, message_text, poll_name, poll_choices, poll_select, attachment_file, attachment_original_name, attachment_mime, attachment_size, delay_value, country_code, include_sheets, sheets_spreadsheet_id, sheets_sheet_name
            FROM crm_campaign_broadcast
            WHERE id = '$id' AND status = 'ENABLE'
            LIMIT 1
        ");

        if (empty($rows)) {
            echo json_encode(array('status' => false, 'msg' => 'Campaign tidak ditemukan.'));
            return;
        }

        echo json_encode(array('status' => true, 'data' => $rows[0]));
    }

    public function campaign_detail()
    {
        $id = intval($this->input->get('id'));
        $focusCustomerId = intval($this->input->get('customer_id'));
        if ($id <= 0) {
            show_404();
            return;
        }

        $campaignRows = $this->mymodel->selectWithQuery("
            SELECT id, name, include_sheets, sheets_spreadsheet_id, sheets_sheet_name
            FROM crm_campaign_broadcast
            WHERE id = '$id' AND status = 'ENABLE'
            LIMIT 1
        ");
        if (empty($campaignRows)) {
            show_404();
            return;
        }

        $campaignNameSql = $this->db->escape_str((string)$campaignRows[0]['name']);
        $targetRows = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM customer WHERE FIND_IN_SET('$campaignNameSql', campaign_broadcast)");
        $targetCount = isset($targetRows[0]['count']) ? (int)$targetRows[0]['count'] : 0;

        $focusCustomer = array();
        if ($focusCustomerId > 0) {
            $focusRows = $this->mymodel->selectWithQuery("
                SELECT id, full_name, username, phone
                FROM customer
                WHERE id = '$focusCustomerId'
                LIMIT 1
            ");
            if (!empty($focusRows)) {
                $focusCustomer = $focusRows[0];
            }
        }

        $sheetData = $this->crm_campaign_read_sheet_rows($campaignRows[0]);
        if ($sheetData['status']) {
            $sheetData = $this->crm_campaign_enrich_sheet_customer_status($sheetData, $focusCustomer);
        }
        $data['title'] = 'Detail Campaign - ' . $this->template->title();
        $data['campaign'] = $campaignRows[0];
        $data['focus_customer'] = $focusCustomer;
        $data['campaign_metrics'] = array(
            'target_count' => $targetCount,
            'crm_response_count' => isset($sheetData['crm_response_count']) ? (int)$sheetData['crm_response_count'] : 0,
            'outside_response_count' => isset($sheetData['outside_response_count']) ? (int)$sheetData['outside_response_count'] : 0,
        );
        $data['sheet_status'] = $sheetData['status'];
        $data['sheet_message'] = $sheetData['message'];
        $data['sheet_auth_url'] = isset($sheetData['auth_url']) ? $sheetData['auth_url'] : '';
        $data['sheet_name'] = $sheetData['sheet_name'];
        $data['sheet_headers'] = $sheetData['headers'];
        $data['sheet_rows'] = $sheetData['rows'];
        $data['content'] = $this->load->view("crm/campaign_detail", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function crm_campaign_sheet_rows()
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = intval($this->input->get('id'));
        if ($id <= 0) {
            echo json_encode(array('status' => false, 'msg' => 'Campaign tidak valid.'));
            return;
        }

        $campaignRows = $this->mymodel->selectWithQuery("
            SELECT id, name, include_sheets, sheets_spreadsheet_id, sheets_sheet_name
            FROM crm_campaign_broadcast
            WHERE id = '$id' AND status = 'ENABLE'
            LIMIT 1
        ");
        if (empty($campaignRows)) {
            echo json_encode(array('status' => false, 'msg' => 'Campaign tidak ditemukan.'));
            return;
        }

        $campaign = $campaignRows[0];
        $sheetData = $this->crm_campaign_read_sheet_rows($campaign);
        if (!$sheetData['status']) {
            echo json_encode(array(
                'status' => false,
                'msg' => $sheetData['message'],
                'auth_url' => isset($sheetData['auth_url']) ? $sheetData['auth_url'] : null,
            ));
            return;
        }

        echo json_encode(array(
            'status' => true,
            'campaign' => array(
                'id' => (int)$campaign['id'],
                'name' => (string)$campaign['name'],
                'sheet_name' => $sheetData['sheet_name'],
            ),
            'headers' => $sheetData['headers'],
            'rows' => $sheetData['rows'],
            'total' => count($sheetData['rows']),
        ));
    }

    public function crm_campaign_create()
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = intval($this->input->post('id'));
        $name = trim((string)$this->input->post('name'));
        if ($name === '') {
            echo json_encode(array('status' => false, 'msg' => 'Nama campaign wajib diisi.'));
            return;
        }
        // Comma is the separator for a customer's campaign list, so it cannot appear in a campaign name.
        if (strpos($name, ',') !== false) {
            echo json_encode(array('status' => false, 'msg' => 'Nama campaign tidak boleh mengandung tanda koma (,).'));
            return;
        }

        $messageHtml = trim((string)$this->input->post('message_html'));
        $messageText = trim((string)$this->input->post('message_text'));
        if ($messageText === '' && $messageHtml !== '') {
            $messageText = $this->crm_campaign_html_to_whatsapp_text($messageHtml);
        }
        $broadcastType = (string)$this->input->post('broadcast_type') === 'polling' ? 'polling' : 'text';
        $pollName = trim((string)$this->input->post('poll_name'));
        $pollSelect = (string)$this->input->post('poll_select') === 'multiple' ? 'multiple' : 'single';
        $pollChoices = $this->crm_campaign_normalize_poll_choices((string)$this->input->post('poll_choices'));
        if ($broadcastType === 'polling') {
            if ($messageText === '') {
                echo json_encode(array('status' => false, 'msg' => 'Pertanyaan / pengantar polling wajib diisi.'));
                return;
            }
            if (count($pollChoices) < 2 || count($pollChoices) > 12) {
                echo json_encode(array('status' => false, 'msg' => 'Opsi polling minimal 2 dan maksimal 12.'));
                return;
            }
            if ($pollName === '') {
                $pollName = $name;
            }
        } else {
            $pollName = '';
            $pollChoices = array();
            $pollSelect = 'single';
        }
        $delayValue = trim((string)$this->input->post('delay_value'));
        if ($delayValue === '') {
            $delayValue = '2';
        }
        if (!preg_match('/^\d+(\-\d+)?$/', $delayValue)) {
            echo json_encode(array('status' => false, 'msg' => 'Delay harus angka atau range, contoh: 2 atau 5-10.'));
            return;
        }

        $countryCode = preg_replace('/\D+/', '', (string)$this->input->post('country_code'));
        if ($countryCode === '') {
            $countryCode = '62';
        }

        $includeSheets = (string)$this->input->post('include_sheets') === '1' ? 1 : 0;
        $sheetsSpreadsheetId = $this->crm_campaign_parse_spreadsheet_id((string)$this->input->post('sheets_spreadsheet_id'));
        $sheetsSheetName = trim((string)$this->input->post('sheets_sheet_name'));
        if ($sheetsSheetName === '') {
            $sheetsSheetName = 'CRM Broadcast';
        }
        if ($includeSheets && $sheetsSpreadsheetId === '') {
            echo json_encode(array('status' => false, 'msg' => 'Link/ID Google Sheets wajib diisi jika data respons Sheets aktif.'));
            return;
        }

        $nameSql = $this->db->escape_str($name);
        $idFilter = $id > 0 ? " AND id != '$id'" : '';
        $exists = $this->mymodel->selectWithQuery("SELECT id FROM crm_campaign_broadcast WHERE LOWER(name) = LOWER('$nameSql') $idFilter LIMIT 1");
        if (!empty($exists)) {
            echo json_encode(array('status' => false, 'msg' => 'Campaign sudah ada.'));
            return;
        }

        $userId = isset($_SESSION['user']['id']) ? intval($_SESSION['user']['id']) : 0;

        $payload = array(
            'name' => $name,
            'broadcast_type' => $broadcastType,
            'message_html' => $this->crm_campaign_sanitize_message_html($messageHtml),
            'message_text' => $messageText,
            'poll_name' => $pollName,
            'poll_choices' => implode("\n", $pollChoices),
            'poll_select' => $pollSelect,
            'delay_value' => $delayValue,
            'country_code' => $countryCode,
            'include_sheets' => $includeSheets,
            'sheets_spreadsheet_id' => $sheetsSpreadsheetId,
            'sheets_sheet_name' => $sheetsSheetName,
            'updated_at' => DATE("Y-m-d H:i:s"),
            'updated_by' => $userId,
        );

        $removeAttachment = ($id > 0 && (string)$this->input->post('remove_attachment') === '1') || $broadcastType === 'polling';
        $oldAttachmentFile = '';
        if ($id > 0 && ($removeAttachment || !empty($_FILES['attachment']['name']))) {
            $oldRows = $this->mymodel->selectWithQuery("SELECT attachment_file FROM crm_campaign_broadcast WHERE id = '$id' LIMIT 1");
            if (!empty($oldRows)) {
                $oldAttachmentFile = (string)($oldRows[0]['attachment_file'] ?? '');
            }
        }
        if ($removeAttachment) {
            $payload['attachment_file'] = '';
            $payload['attachment_original_name'] = '';
            $payload['attachment_mime'] = '';
            $payload['attachment_size'] = 0;
        }

        $upload = $this->crm_campaign_handle_attachment_upload();
        if (!$upload['status']) {
            echo json_encode(array('status' => false, 'msg' => $upload['msg']));
            return;
        }
        if (!empty($upload['data'])) {
            $payload = array_merge($payload, $upload['data']);
        }

        if ($id > 0) {
            $oldNameRows = $this->mymodel->selectWithQuery("SELECT name FROM crm_campaign_broadcast WHERE id = '$id' LIMIT 1");
            $oldName = !empty($oldNameRows) ? (string)$oldNameRows[0]['name'] : '';

            $this->db->update('crm_campaign_broadcast', $payload, array('id' => $id));
            if ($oldAttachmentFile !== '' && ($removeAttachment || !empty($upload['data']))) {
                $newAttachmentFile = isset($upload['data']['attachment_file']) ? (string)$upload['data']['attachment_file'] : '';
                if ($oldAttachmentFile !== $newAttachmentFile) {
                    $this->crm_campaign_delete_attachment_file($oldAttachmentFile);
                }
            }
            // If the campaign was renamed, update its name inside every customer's comma-separated list.
            if ($oldName !== '' && $oldName !== $name) {
                $this->crm_campaign_rename_in_customers($oldName, $name);
            }
            echo json_encode(array('status' => true, 'msg' => 'Campaign berhasil diperbarui.'));
            return;
        }

        $payload['status'] = 'ENABLE';
        $payload['created_at'] = DATE("Y-m-d H:i:s");
        $payload['created_by'] = $userId;
        $this->db->insert('crm_campaign_broadcast', $payload);

        echo json_encode(array('status' => true, 'msg' => 'Campaign berhasil ditambahkan.'));
    }

    public function crm_campaign_delete()
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = intval($this->input->post('id'));
        if ($id <= 0) {
            echo json_encode(array('status' => false, 'msg' => 'Campaign tidak valid.'));
            return;
        }

        $campaignRow = $this->mymodel->selectWithQuery("SELECT name FROM crm_campaign_broadcast WHERE id = '$id' LIMIT 1");
        $campaignName = !empty($campaignRow) ? (string)$campaignRow[0]['name'] : '';

        $this->db->delete('crm_campaign_broadcast', array('id' => $id));
        if ($campaignName !== '') {
            $this->crm_campaign_remove_from_customers($campaignName);
        }

        echo json_encode(array('status' => true, 'msg' => 'Campaign berhasil dihapus.'));
    }

    public function crm_campaign_customer_search()
    {
        header('Content-Type: application/json; charset=utf-8');

        $keyword = trim((string)$this->input->get('keyword'));
        $limit = intval($this->input->get('limit'));
        if ($limit <= 0 || $limit > 50) {
            $limit = 20;
        }

        $where = "1=1";
        if ($keyword !== '') {
            $like = $this->db->escape_like_str($keyword);
            $where .= " AND (
                `username` LIKE '%$like%' ESCAPE '!' OR
                `full_name` LIKE '%$like%' ESCAPE '!' OR
                `address` LIKE '%$like%' ESCAPE '!' OR
                `phone` LIKE '%$like%' ESCAPE '!'
            )";
        }

        $rows = $this->mymodel->selectWithQuery("
            SELECT id, full_name, username, phone, address, campaign_broadcast
            FROM customer
            WHERE $where
            ORDER BY id DESC
            LIMIT $limit
        ");

        echo json_encode(array(
            'status' => true,
            'data' => $rows
        ));
    }

    public function crm_campaign_customer_attach()
    {
        header('Content-Type: application/json; charset=utf-8');

        $campaignId = intval($this->input->post('campaign_id'));
        $customerId = intval($this->input->post('customer_id'));
        if ($campaignId <= 0 || $customerId <= 0) {
            echo json_encode(array('status' => false, 'msg' => 'Campaign/customer tidak valid.'));
            return;
        }

        $campaignRow = $this->mymodel->selectWithQuery("SELECT id, name FROM crm_campaign_broadcast WHERE id = '$campaignId' LIMIT 1");
        if (empty($campaignRow)) {
            echo json_encode(array('status' => false, 'msg' => 'Campaign tidak ditemukan.'));
            return;
        }

        $campaignName = (string)$campaignRow[0]['name'];
        $userId = isset($_SESSION['user']['id']) ? intval($_SESSION['user']['id']) : 0;

        $this->crm_campaign_attach_customers($campaignName, array($customerId));
        $this->db->update('customer', array(
            'updated_at' => DATE("Y-m-d H:i:s"),
            'updated_by' => $userId,
        ), array('id' => $customerId));

        echo json_encode(array('status' => true, 'msg' => 'Customer berhasil dimasukkan ke campaign.'));
    }

    public function crm_campaign_broadcast_send()
    {
        header('Content-Type: application/json; charset=utf-8');

        $campaignId = intval($this->input->post('campaign_id'));
        $customerIdsRaw = (array)$this->input->post('customer_ids');
        if (empty($customerIdsRaw)) {
            $customerIdsRaw = explode(',', (string)$this->input->post('customer_ids'));
        }
        $customerIds = array_values(array_unique(array_filter(array_map('intval', $customerIdsRaw), function ($id) {
            return $id > 0;
        })));

        if ($campaignId <= 0 || empty($customerIds)) {
            echo json_encode(array('status' => false, 'msg' => 'Campaign dan customer wajib dipilih.'));
            return;
        }

        $campaignRows = $this->mymodel->selectWithQuery("
            SELECT *
            FROM crm_campaign_broadcast
            WHERE id = '$campaignId' AND status = 'ENABLE'
            LIMIT 1
        ");
        if (empty($campaignRows)) {
            echo json_encode(array('status' => false, 'msg' => 'Campaign tidak ditemukan.'));
            return;
        }

        $campaign = $campaignRows[0];
        $message = trim((string)($campaign['message_text'] ?? ''));
        if ($message === '' && trim((string)($campaign['message_html'] ?? '')) !== '') {
            $message = $this->crm_campaign_html_to_whatsapp_text((string)$campaign['message_html']);
        }
        if ($message === '') {
            echo json_encode(array('status' => false, 'msg' => 'Wording campaign masih kosong.'));
            return;
        }
        $broadcastType = (string)($campaign['broadcast_type'] ?? 'text') === 'polling' ? 'polling' : 'text';
        $pollChoices = $this->crm_campaign_normalize_poll_choices((string)($campaign['poll_choices'] ?? ''));
        if ($broadcastType === 'polling') {
            if (count($pollChoices) < 2 || count($pollChoices) > 12) {
                echo json_encode(array('status' => false, 'msg' => 'Opsi polling minimal 2 dan maksimal 12.'));
                return;
            }
            $campaign['_poll_choices'] = $pollChoices;
        }

        $sendMode = (string)$this->input->post('send_mode') === 'scheduled' ? 'scheduled' : 'now';
        $sendAt = trim((string)$this->input->post('send_at'));
        $scheduleTimestamp = 0;
        $scheduleDisplay = '';
        if ($sendMode === 'scheduled') {
            if ($sendAt === '') {
                echo json_encode(array('status' => false, 'msg' => 'Waktu kirim wajib diisi jika pilih jadwal nanti.'));
                return;
            }
            try {
                $timezone = new DateTimeZone('Asia/Jakarta');
                $scheduleDate = DateTime::createFromFormat('Y-m-d H:i', $sendAt, $timezone);
                if (!$scheduleDate) {
                    $scheduleDate = new DateTime($sendAt, $timezone);
                }
                $now = new DateTime('now', $timezone);
                if ($scheduleDate <= $now) {
                    echo json_encode(array('status' => false, 'msg' => 'Waktu kirim harus lebih besar dari waktu sekarang.'));
                    return;
                }
                $scheduleTimestamp = $scheduleDate->getTimestamp();
                $scheduleDisplay = $scheduleDate->format('Y-m-d H:i');
            } catch (Exception $e) {
                echo json_encode(array('status' => false, 'msg' => 'Format waktu kirim tidak valid.'));
                return;
            }
        }
        $campaign['_schedule_timestamp'] = $scheduleTimestamp;

        $gateway = strtolower(trim((string)$this->config->item('crm_broadcast_gateway')));
        if (!in_array($gateway, array('fonnte', 'waha'), true)) {
            $gateway = 'fonnte';
        }

        $token = '';
        if ($gateway === 'fonnte') {
            $token = trim((string)$this->config->item('fonnte_token'));
            if ($token === '') {
                $token = trim((string)getenv('FONNTE_TOKEN'));
            }
            if ($token === '') {
                echo json_encode(array('status' => false, 'msg' => 'Token Fonnte belum dikonfigurasi. Set FONNTE_TOKEN di environment/config aplikasi.'));
                return;
            }
        }

        $idsSql = implode(',', $customerIds);
        $customers = $this->mymodel->selectWithQuery("
            SELECT id, full_name, username, phone
            FROM customer
            WHERE id IN ($idsSql)
            ORDER BY FIELD(id, $idsSql)
        ");

        $targets = array();
        $targetCustomerMap = array();
        $skipped = array();
        foreach ($customers as $idx => $customer) {
            $phone = $this->crm_campaign_normalize_phone((string)($customer['phone'] ?? ''));
            if ($phone === '') {
                $skipped[] = array(
                    'position' => $idx + 1,
                    'customer_id' => (int)$customer['id'],
                    'phone' => (string)($customer['phone'] ?? ''),
                    'reason' => 'Nomor kosong/tidak valid',
                );
                continue;
            }
            $targets[] = $phone;
            $targetCustomerMap[$phone] = $customer;
        }

        if (empty($targets)) {
            echo json_encode(array('status' => false, 'msg' => 'Tidak ada nomor customer yang valid.', 'skipped' => $skipped));
            return;
        }

        if ($gateway === 'waha') {
            $sendResponse = $this->crm_campaign_send_waha($targets, $message, $campaign);
        } else {
            $sendResponse = $this->crm_campaign_send_fonnte($token, $targets, $message, $campaign);
        }
        $responseJson = json_encode($sendResponse['response'], JSON_UNESCAPED_UNICODE);

        if (!$sendResponse['status']) {
            $failedPhone = isset($sendResponse['response']['failed_target']) ? (string)$sendResponse['response']['failed_target'] : (isset($targets[0]) ? $targets[0] : '-');
            $failedAt = array_search($failedPhone, $targets, true);
            $failedAt = $failedAt === false ? 1 : $failedAt + 1;
            $reason = $sendResponse['reason'] !== '' ? $sendResponse['reason'] : strtoupper($gateway) . ' menolak request broadcast.';
            $this->crm_campaign_insert_broadcast_logs($campaignId, $targetCustomerMap, $targets, array(), $responseJson, 'failed', $reason);
            echo json_encode(array(
                'status' => false,
                'msg' => 'Broadcast gagal di nomor ke-' . $failedAt . ' (' . $failedPhone . '): ' . $reason . '. Silakan cek gateway ' . strtoupper($gateway) . ', lalu coba lagi.',
                'failed_at' => $failedAt,
                'failed_phone' => $failedPhone,
                'reason' => $reason,
                'skipped' => $skipped,
            ));
            return;
        }

        $acceptedTargets = array();
        if (isset($sendResponse['response']['target']) && is_array($sendResponse['response']['target'])) {
            foreach ($sendResponse['response']['target'] as $target) {
                $normalizedTarget = $this->crm_campaign_normalize_phone((string)$target);
                if ($normalizedTarget !== '') {
                    $acceptedTargets[] = $normalizedTarget;
                }
            }
        }
        if (empty($acceptedTargets)) {
            $acceptedTargets = $targets;
        }
        $acceptedTargets = array_values(array_unique($acceptedTargets));
        $acceptedIds = array();
        foreach ($acceptedTargets as $target) {
            if (isset($targetCustomerMap[$target])) {
                $acceptedIds[] = (int)$targetCustomerMap[$target]['id'];
            }
        }

        $processStatus = isset($sendResponse['response']['process']) ? (string)$sendResponse['response']['process'] : 'processing';
        $this->crm_campaign_insert_broadcast_logs($campaignId, $targetCustomerMap, $acceptedTargets, $sendResponse['message_ids'], $responseJson, $processStatus, '');

        if (!empty($acceptedIds)) {
            // Add these customers to the campaign without removing their other campaigns.
            $this->crm_campaign_attach_customers((string)$campaign['name'], $acceptedIds);
            $this->db->where_in('id', $acceptedIds);
            $this->db->update('customer', array(
                'updated_at' => DATE("Y-m-d H:i:s"),
            ));
        }

        $acceptedLookup = array_fill_keys($acceptedTargets, true);
        foreach ($targets as $idx => $target) {
            if (!isset($acceptedLookup[$target])) {
                $this->crm_campaign_insert_broadcast_logs($campaignId, $targetCustomerMap, array($target), array(), $responseJson, 'failed', 'Target tidak diterima ' . strtoupper($gateway) . '.');
                echo json_encode(array(
                    'status' => false,
                    'msg' => 'Broadcast gagal sampai nomor ke-' . ($idx + 1) . ' (' . $target . '): target tidak diterima ' . strtoupper($gateway) . '. Silakan cek gateway, lalu coba lagi.',
                    'failed_at' => $idx + 1,
                    'failed_phone' => $target,
                    'reason' => 'Target tidak diterima ' . strtoupper($gateway) . '.',
                    'skipped' => $skipped,
                ));
                return;
            }
        }

        $gatewayLabel = strtoupper($gateway);
        $successMessage = count($acceptedIds) . ' customer berhasil masuk queue ' . $gatewayLabel . '. Label broadcast sudah diupdate di CRM.';
        if ($scheduleTimestamp > 0) {
            $successMessage = count($acceptedIds) . ' customer berhasil dijadwalkan via ' . $gatewayLabel . ' pada ' . $scheduleDisplay . ' WIB. Label broadcast sudah diupdate di CRM.';
        }

        echo json_encode(array(
            'status' => true,
            'msg' => $successMessage,
            'sent_count' => count($acceptedIds),
            'selected_count' => count($customerIds),
            'skipped' => $skipped,
            'requestid' => $sendResponse['requestid'],
            'sheets_sync_status' => 'disabled',
            'sheets_sync_message' => '',
            'schedule_timestamp' => $scheduleTimestamp,
            'schedule_at' => $scheduleDisplay,
        ));
    }

    // customer.campaign_broadcast holds a comma-separated list of campaign names a customer
    // belongs to. Append a campaign to the given customers without removing existing ones.
    // FIND_IN_SET avoids duplicates; names are joined with a plain comma (no space) so
    // FIND_IN_SET / SUBSTRING_INDEX keep working. Avoid commas inside campaign names.
    private function crm_campaign_attach_customers($campaignName, $customerIds)
    {
        $ids = array();
        foreach ((array)$customerIds as $cid) {
            $cid = (int)$cid;
            if ($cid > 0) {
                $ids[$cid] = $cid;
            }
        }
        $name = trim((string)$campaignName);
        if (empty($ids) || $name === '') {
            return;
        }

        $idsSql = implode(',', $ids);
        $nameSql = $this->db->escape_str($name);
        $this->db->query("
            UPDATE customer
            SET campaign_broadcast = CASE
                WHEN campaign_broadcast IS NULL OR campaign_broadcast = '' THEN '$nameSql'
                WHEN FIND_IN_SET('$nameSql', campaign_broadcast) THEN campaign_broadcast
                ELSE CONCAT(campaign_broadcast, ',', '$nameSql')
            END
            WHERE id IN ($idsSql)
        ");
    }

    // Remove a campaign name from every customer's comma-separated list (used on campaign delete).
    private function crm_campaign_remove_from_customers($campaignName)
    {
        $name = trim((string)$campaignName);
        if ($name === '') {
            return;
        }
        $nameSql = $this->db->escape_str($name);
        $this->db->query("
            UPDATE customer
            SET campaign_broadcast = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', campaign_broadcast, ','), CONCAT(',', '$nameSql', ','), ','))
            WHERE FIND_IN_SET('$nameSql', campaign_broadcast)
        ");
    }

    // Replace an old campaign name with a new one inside every customer's list (used on rename).
    private function crm_campaign_rename_in_customers($oldName, $newName)
    {
        $old = trim((string)$oldName);
        $new = trim((string)$newName);
        if ($old === '' || $old === $new) {
            return;
        }
        $oldSql = $this->db->escape_str($old);
        $newSql = $this->db->escape_str($new);
        $this->db->query("
            UPDATE customer
            SET campaign_broadcast = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', campaign_broadcast, ','), CONCAT(',', '$oldSql', ','), CONCAT(',', '$newSql', ',')))
            WHERE FIND_IN_SET('$oldSql', campaign_broadcast)
        ");
    }

    private function crm_campaign_handle_attachment_upload()
    {
        if (empty($_FILES['attachment']['name'])) {
            return array('status' => true, 'data' => array());
        }

        $allowedExt = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'mp4', 'mp3');
        $originalName = (string)$_FILES['attachment']['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            return array('status' => false, 'msg' => 'Tipe lampiran tidak didukung.');
        }

        $maxSize = 4 * 1024 * 1024;
        $size = isset($_FILES['attachment']['size']) ? (int)$_FILES['attachment']['size'] : 0;
        if ($size > $maxSize) {
            return array('status' => false, 'msg' => 'Ukuran lampiran maksimal 4MB.');
        }

        $uploadDir = FCPATH . 'assets/uploads/crm_campaign/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $safeName = 'crm_campaign_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $targetPath = $uploadDir . $safeName;
        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
            return array('status' => false, 'msg' => 'Lampiran gagal diupload.');
        }

        return array(
            'status' => true,
            'data' => array(
                'attachment_file' => $safeName,
                'attachment_original_name' => $originalName,
                'attachment_mime' => isset($_FILES['attachment']['type']) ? (string)$_FILES['attachment']['type'] : '',
                'attachment_size' => $size,
            )
        );
    }

    private function crm_campaign_delete_attachment_file($filename)
    {
        $filename = basename((string)$filename);
        if ($filename === '') {
            return;
        }
        $path = FCPATH . 'assets/uploads/crm_campaign/' . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function crm_campaign_parse_spreadsheet_id($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9\-_]+)/', $value, $matches)) {
            return $matches[1];
        }
        if (preg_match('/^[a-zA-Z0-9\-_]{20,}$/', $value)) {
            return $value;
        }
        return '';
    }

    private function crm_campaign_sheet_range($sheetName, $range)
    {
        $sheetName = str_replace("'", "''", trim((string)$sheetName));
        if ($sheetName === '') {
            $sheetName = 'CRM Broadcast';
        }
        return "'" . $sheetName . "'!" . $range;
    }

    private function crm_campaign_read_sheet_rows($campaign)
    {
        $sheetName = trim((string)($campaign['sheets_sheet_name'] ?? 'CRM Broadcast'));
        if ($sheetName === '') {
            $sheetName = 'CRM Broadcast';
        }

        $emptyResult = array(
            'status' => false,
            'message' => '',
            'auth_url' => '',
            'sheet_name' => $sheetName,
            'headers' => array(),
            'rows' => array(),
        );

        if ((int)($campaign['include_sheets'] ?? 0) !== 1) {
            $emptyResult['message'] = 'Campaign ini belum aktif menampilkan data respons Google Sheets.';
            return $emptyResult;
        }

        $spreadsheetId = trim((string)($campaign['sheets_spreadsheet_id'] ?? ''));
        if ($spreadsheetId === '') {
            $emptyResult['message'] = 'Spreadsheet campaign belum diisi.';
            return $emptyResult;
        }

        $auth = $this->crm_campaign_get_google_sheets_client();
        if (!$auth['status']) {
            $emptyResult['message'] = $auth['message'];
            $emptyResult['auth_url'] = isset($auth['auth_url']) ? $auth['auth_url'] : '';
            return $emptyResult;
        }

        $prepareSheet = $this->crm_campaign_prepare_google_sheet($auth['client'], $spreadsheetId, $sheetName);
        if (!$prepareSheet['status']) {
            $emptyResult['message'] = $prepareSheet['message'];
            return $emptyResult;
        }

        try {
            $sheets = new Google_Service_Sheets($auth['client']);
            $result = $sheets->spreadsheets_values->get($spreadsheetId, $this->crm_campaign_sheet_range($sheetName, 'A1:L1000'));
            $values = $result->getValues();
        } catch (Exception $e) {
            $emptyResult['message'] = 'Gagal membaca Google Sheets: ' . $e->getMessage();
            return $emptyResult;
        }

        $headers = array();
        $rows = array();
        if (!empty($values)) {
            $headers = array_shift($values);
            $rows = $values;
        }

        return array(
            'status' => true,
            'message' => '',
            'auth_url' => '',
            'sheet_name' => $sheetName,
            'headers' => $headers,
            'rows' => $rows,
        );
    }

    private function crm_campaign_enrich_sheet_customer_status($sheetData, $focusCustomer = array())
    {
        $headers = isset($sheetData['headers']) && is_array($sheetData['headers']) ? $sheetData['headers'] : array();
        $rows = isset($sheetData['rows']) && is_array($sheetData['rows']) ? $sheetData['rows'] : array();

        $customerIdIdx = $this->crm_campaign_find_sheet_column($headers, array('customer id', 'id customer', 'customer_id', 'id crm'));
        $phoneIdx = $this->crm_campaign_find_sheet_column($headers, array('phone', 'no hp', 'nomor hp', 'no_hp', 'whatsapp', 'wa', 'telepon', 'nomor telepon'));
        $nameIdx = $this->crm_campaign_find_sheet_column($headers, array('full name', 'nama', 'name', 'customer name', 'nama lengkap'));

        $customerIds = array();
        $phoneSuffixes = array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if ($customerIdIdx >= 0 && isset($row[$customerIdIdx])) {
                $customerId = intval($row[$customerIdIdx]);
                if ($customerId > 0) {
                    $customerIds[$customerId] = $customerId;
                }
            }
            if ($phoneIdx >= 0 && isset($row[$phoneIdx])) {
                $normalizedPhone = $this->crm_campaign_match_phone((string)$row[$phoneIdx]);
                if ($normalizedPhone !== '') {
                    $phoneSuffixes[substr($normalizedPhone, -9)] = substr($normalizedPhone, -9);
                }
            }
        }

        $customerMapById = array();
        $customerMapByPhone = array();
        $conditions = array();
        if (!empty($customerIds)) {
            $conditions[] = "id IN (" . implode(',', array_map('intval', $customerIds)) . ")";
        }
        foreach (array_values($phoneSuffixes) as $suffix) {
            $suffixSql = $this->db->escape_like_str($suffix);
            $conditions[] = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') LIKE '%$suffixSql%' ESCAPE '!'";
        }

        if (!empty($conditions)) {
            $customerRows = $this->mymodel->selectWithQuery("
                SELECT id, full_name, username, phone
                FROM customer
                WHERE " . implode(' OR ', $conditions) . "
            ");
            foreach ($customerRows as $customer) {
                $customerId = isset($customer['id']) ? (int)$customer['id'] : 0;
                if ($customerId <= 0) {
                    continue;
                }
                $customerMapById[$customerId] = $customer;
                $normalizedPhone = $this->crm_campaign_match_phone((string)($customer['phone'] ?? ''));
                if ($normalizedPhone !== '') {
                    $customerMapByPhone[$normalizedPhone] = $customer;
                    $customerMapByPhone[substr($normalizedPhone, -9)] = $customer;
                }
            }
        }

        $focusId = isset($focusCustomer['id']) ? (int)$focusCustomer['id'] : 0;
        $focusPhone = $this->crm_campaign_match_phone((string)($focusCustomer['phone'] ?? ''));
        $focusName = $this->crm_campaign_match_name((string)($focusCustomer['full_name'] ?? ''));
        $focusUsername = $this->crm_campaign_match_name((string)($focusCustomer['username'] ?? ''));

        $enrichedRows = array();
        $crmResponseCount = 0;
        $outsideResponseCount = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $matchedCustomer = array();
            $rowCustomerId = $customerIdIdx >= 0 && isset($row[$customerIdIdx]) ? intval($row[$customerIdIdx]) : 0;
            if ($rowCustomerId > 0 && isset($customerMapById[$rowCustomerId])) {
                $matchedCustomer = $customerMapById[$rowCustomerId];
            }

            $rowPhone = '';
            if (empty($matchedCustomer) && $phoneIdx >= 0 && isset($row[$phoneIdx])) {
                $rowPhone = $this->crm_campaign_match_phone((string)$row[$phoneIdx]);
                if ($rowPhone !== '' && isset($customerMapByPhone[$rowPhone])) {
                    $matchedCustomer = $customerMapByPhone[$rowPhone];
                } elseif ($rowPhone !== '' && isset($customerMapByPhone[substr($rowPhone, -9)])) {
                    $matchedCustomer = $customerMapByPhone[substr($rowPhone, -9)];
                }
            }

            $rowName = $nameIdx >= 0 && isset($row[$nameIdx]) ? $this->crm_campaign_match_name((string)$row[$nameIdx]) : '';
            if ($focusId > 0) {
                $matchedId = isset($matchedCustomer['id']) ? (int)$matchedCustomer['id'] : 0;
                $sameFocus = $matchedId === $focusId;
                if (!$sameFocus && $focusPhone !== '' && $rowPhone !== '') {
                    $sameFocus = $rowPhone === $focusPhone || substr($rowPhone, -9) === substr($focusPhone, -9);
                }
                if (!$sameFocus && $rowName !== '') {
                    $sameFocus = ($focusName !== '' && $rowName === $focusName) || ($focusUsername !== '' && $rowName === $focusUsername);
                }
                if (!$sameFocus) {
                    continue;
                }
                if (empty($matchedCustomer)) {
                    $matchedCustomer = $focusCustomer;
                }
            }

            if (!empty($matchedCustomer)) {
                $crmResponseCount++;
                $row[] = 'Customer CRM';
                $row[] = (string)($matchedCustomer['full_name'] ?? '');
                $row[] = (string)($matchedCustomer['id'] ?? '');
            } else {
                $outsideResponseCount++;
                $row[] = 'Di luar CRM';
                $row[] = '';
                $row[] = '';
            }
            $enrichedRows[] = $row;
        }

        $headers[] = 'Asal Data';
        $headers[] = 'Customer CRM';
        $headers[] = 'ID Customer CRM';

        $sheetData['headers'] = $headers;
        $sheetData['rows'] = $enrichedRows;
        $sheetData['crm_response_count'] = $crmResponseCount;
        $sheetData['outside_response_count'] = $outsideResponseCount;
        return $sheetData;
    }

    private function crm_campaign_find_sheet_column($headers, $keywords)
    {
        foreach ($headers as $idx => $header) {
            $cleanHeader = strtolower(trim((string)$header));
            $cleanHeader = str_replace(array('-', '_'), ' ', $cleanHeader);
            foreach ($keywords as $keyword) {
                $cleanKeyword = strtolower(trim((string)$keyword));
                $cleanKeyword = str_replace(array('-', '_'), ' ', $cleanKeyword);
                if ($cleanHeader === $cleanKeyword || strpos($cleanHeader, $cleanKeyword) !== false) {
                    return (int)$idx;
                }
            }
        }
        return -1;
    }

    private function crm_campaign_match_phone($phone)
    {
        $digits = preg_replace('/\D+/', '', (string)$phone);
        if ($digits === '') {
            return '';
        }
        if (substr($digits, 0, 1) === '0') {
            $digits = '62' . substr($digits, 1);
        } elseif (substr($digits, 0, 1) === '8') {
            $digits = '62' . $digits;
        }
        return $digits;
    }

    private function crm_campaign_match_name($name)
    {
        $name = strtolower(trim((string)$name));
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }

    private function crm_campaign_get_google_sheets_client()
    {
        $client = new Google_Client();
        $client->setAuthConfig(google_client_auth_config());
        $client->setScopes(array(
            Google_Service_Sheets::SPREADSHEETS,
        ));
        $client->setAccessType('offline');
        $client->setPrompt('consent select_account');
        $client->setRedirectUri(base_url('googlemou/oauth2callback'));

        $raw = $this->session->userdata('access_token');
        if (!$raw) {
            return array(
                'status' => false,
                'message' => 'Google belum terhubung. Klik Login Google untuk menghubungkan akun.',
                'auth_url' => $client->createAuthUrl(),
            );
        }

        $token = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!$token || empty($token['access_token'])) {
            return array(
                'status' => false,
                'message' => 'Token Google tidak valid. Login ulang Google dibutuhkan.',
                'auth_url' => $client->createAuthUrl(),
            );
        }

        $client->setAccessToken($token);
        if ($client->isAccessTokenExpired()) {
            $refresh = $client->getRefreshToken();
            if (!$refresh && !empty($token['refresh_token'])) {
                $refresh = $token['refresh_token'];
            }
            if (!$refresh) {
                return array(
                    'status' => false,
                    'message' => 'Refresh token Google tidak tersedia. Login ulang Google dibutuhkan.',
                    'auth_url' => $client->createAuthUrl(),
                );
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($refresh);
            if (!isset($newToken['refresh_token'])) {
                $newToken['refresh_token'] = $refresh;
            }
            if (!empty($newToken['error'])) {
                return array(
                    'status' => false,
                    'message' => 'Refresh token Google gagal. Login ulang Google dibutuhkan.',
                    'auth_url' => $client->createAuthUrl(),
                );
            }
            $client->setAccessToken($newToken);
            $this->session->set_userdata('access_token', json_encode($client->getAccessToken()));
        }

        return array('status' => true, 'client' => $client);
    }

    private function crm_campaign_prepare_google_sheet($client, $spreadsheetId, $sheetName)
    {
        try {
            $sheets = new Google_Service_Sheets($client);
            $spreadsheet = $sheets->spreadsheets->get($spreadsheetId, array('fields' => 'sheets.properties.title'));
            $exists = false;
            foreach ($spreadsheet->getSheets() as $sheet) {
                if ($sheet->getProperties()->getTitle() === $sheetName) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                return array('status' => false, 'message' => 'Sheet "' . $sheetName . '" tidak ditemukan di spreadsheet campaign.');
            }
        } catch (Exception $e) {
            return array('status' => false, 'message' => 'Google Sheets tidak siap: ' . $e->getMessage());
        }

        return array('status' => true, 'message' => '');
    }

    private function crm_campaign_sanitize_message_html($html)
    {
        $html = (string)$html;
        if ($html === '') {
            return '';
        }
        return strip_tags($html, '<p><br><strong><b><em><i><u><s><strike><ul><ol><li><span><div><a>');
    }

    private function crm_campaign_html_to_whatsapp_text($html)
    {
        $text = (string)$html;
        $text = preg_replace('/<(strong|b)[^>]*>(.*?)<\/\1>/is', '*$2*', $text);
        $text = preg_replace('/<(em|i)[^>]*>(.*?)<\/\1>/is', '_$2_', $text);
        $text = preg_replace('/<(s|strike)[^>]*>(.*?)<\/\1>/is', '~$2~', $text);
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<\/p>|<\/div>|<\/li>/i', "\n", $text);
        $text = preg_replace('/<li[^>]*>/i', '- ', $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    private function crm_campaign_normalize_phone($phone)
    {
        $digits = preg_replace('/\D+/', '', (string)$phone);
        if ($digits === '' || strlen($digits) < 8) {
            return '';
        }
        if (substr($digits, 0, 1) === '0') {
            $digits = '62' . substr($digits, 1);
        }
        return $digits;
    }

    private function crm_campaign_normalize_poll_choices($choices)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string)$choices);
        $result = array();
        foreach ($lines as $line) {
            $choice = trim(strip_tags((string)$line));
            $choice = preg_replace('/\s+/', ' ', $choice);
            if ($choice !== '' && !in_array($choice, $result, true)) {
                $result[] = $choice;
            }
        }
        return $result;
    }

    private function crm_campaign_send_fonnte($token, $targets, $message, $campaign)
    {
        $postFields = array(
            'target' => implode(',', $targets),
            'message' => $message,
            'delay' => (string)($campaign['delay_value'] ?? '2'),
            'countryCode' => (string)($campaign['country_code'] ?? '62'),
            'typing' => true,
            'connectOnly' => true,
        );

        $scheduleTimestamp = isset($campaign['_schedule_timestamp']) ? (int)$campaign['_schedule_timestamp'] : 0;
        if ($scheduleTimestamp > 0) {
            $postFields['schedule'] = $scheduleTimestamp;
        }

        $broadcastType = (string)($campaign['broadcast_type'] ?? 'text') === 'polling' ? 'polling' : 'text';
        if ($broadcastType === 'polling') {
            $pollChoices = isset($campaign['_poll_choices']) && is_array($campaign['_poll_choices'])
                ? $campaign['_poll_choices']
                : $this->crm_campaign_normalize_poll_choices((string)($campaign['poll_choices'] ?? ''));
            $postFields['pollname'] = trim((string)($campaign['poll_name'] ?? '')) !== ''
                ? trim((string)$campaign['poll_name'])
                : (string)($campaign['name'] ?? 'Polling');
            $postFields['choices'] = implode(',', $pollChoices);
            $postFields['select'] = (string)($campaign['poll_select'] ?? 'single') === 'multiple' ? 'multiple' : 'single';
        } else {
            $attachment = trim((string)($campaign['attachment_file'] ?? ''));
            if ($attachment !== '') {
                $attachmentPath = FCPATH . 'assets/uploads/crm_campaign/' . $attachment;
                if (file_exists($attachmentPath)) {
                    $postFields['file'] = new CURLFile($attachmentPath);
                    if (!empty($campaign['attachment_original_name'])) {
                        $postFields['filename'] = (string)$campaign['attachment_original_name'];
                    }
                }
            }
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => array('Authorization: ' . $token),
        ));

        $rawResponse = curl_exec($curl);
        $curlError = curl_errno($curl) ? curl_error($curl) : '';
        curl_close($curl);

        if ($curlError !== '') {
            return array(
                'status' => false,
                'reason' => $curlError,
                'response' => array('curl_error' => $curlError, 'raw' => $rawResponse),
                'message_ids' => array(),
                'requestid' => '',
            );
        }

        $decoded = json_decode((string)$rawResponse, true);
        if (!is_array($decoded)) {
            return array(
                'status' => false,
                'reason' => 'Respons Fonnte tidak valid.',
                'response' => array('raw' => $rawResponse),
                'message_ids' => array(),
                'requestid' => '',
            );
        }

        $status = false;
        if (isset($decoded['status'])) {
            $status = (bool)$decoded['status'];
        } elseif (isset($decoded['Status'])) {
            $status = (bool)$decoded['Status'];
        }

        $reason = '';
        if (!$status) {
            $reason = isset($decoded['reason']) ? (string)$decoded['reason'] : (isset($decoded['detail']) ? (string)$decoded['detail'] : 'Request Fonnte gagal.');
        }

        return array(
            'status' => $status,
            'reason' => $reason,
            'response' => $decoded,
            'message_ids' => isset($decoded['id']) && is_array($decoded['id']) ? $decoded['id'] : array(),
            'requestid' => isset($decoded['requestid']) ? (string)$decoded['requestid'] : '',
        );
    }

    private function crm_campaign_send_waha($targets, $message, $campaign)
    {
        $baseUrl = rtrim(trim((string)$this->config->item('waha_base_url')), '/');
        $apiKey = trim((string)$this->config->item('waha_api_key'));
        $session = trim((string)$this->config->item('waha_session'));
        if ($baseUrl === '' || $apiKey === '' || $session === '') {
            return array(
                'status' => false,
                'reason' => 'Konfigurasi WAHA belum lengkap. Set WAHA_BASE_URL, WAHA_API_KEY, dan WAHA_SESSION.',
                'response' => array(),
                'message_ids' => array(),
                'requestid' => '',
            );
        }

        if (!empty($campaign['_schedule_timestamp'])) {
            return array(
                'status' => false,
                'reason' => 'WAHA local belum mendukung schedule dari CRM. Pilih kirim sekarang.',
                'response' => array('gateway' => 'waha', 'schedule' => (int)$campaign['_schedule_timestamp']),
                'message_ids' => array(),
                'requestid' => '',
            );
        }

        $broadcastType = (string)($campaign['broadcast_type'] ?? 'text') === 'polling' ? 'polling' : 'text';
        if ($broadcastType === 'polling') {
            return array(
                'status' => false,
                'reason' => 'WAHA local CRM saat ini belum mendukung polling.',
                'response' => array('gateway' => 'waha', 'broadcast_type' => 'polling'),
                'message_ids' => array(),
                'requestid' => '',
            );
        }

        $attachment = $this->crm_campaign_waha_attachment_payload($campaign);
        if (!$attachment['status']) {
            return array(
                'status' => false,
                'reason' => $attachment['reason'],
                'response' => array('gateway' => 'waha', 'attachment_file' => (string)($campaign['attachment_file'] ?? '')),
                'message_ids' => array(),
                'requestid' => '',
            );
        }

        $requestId = 'waha-local-' . date('YmdHis') . '-' . substr(md5(implode(',', $targets) . microtime(true)), 0, 8);
        $response = array(
            'status' => true,
            'gateway' => 'waha',
            'process' => 'pending',
            'requestid' => $requestId,
            'target' => array(),
            'id' => array(),
            'items' => array(),
        );

        $delaySeconds = max(0, (int)($campaign['delay_value'] ?? 0));
        $delaySeconds = min($delaySeconds, 10);
        $endpoint = $baseUrl . ($attachment['has_attachment'] ? $attachment['endpoint'] : '/api/sendText');

        foreach ($targets as $index => $target) {
            $chatId = $target . '@c.us';
            if ($attachment['has_attachment']) {
                $payload = $attachment['payload'];
                $payload['session'] = $session;
                $payload['chatId'] = $chatId;
                $payload['caption'] = $message;
            } else {
                $payload = array(
                    'session' => $session,
                    'chatId' => $chatId,
                    'text' => $message,
                );
            }

            $send = $this->crm_campaign_waha_json_request($endpoint, $apiKey, $payload);
            $decoded = $send['decoded'];
            $item = array(
                'target' => $target,
                'chatId' => $chatId,
                'endpoint' => $attachment['has_attachment'] ? $attachment['endpoint'] : '/api/sendText',
                'http_code' => $send['http_code'],
                'response' => $decoded ?: $send['raw'],
            );

            if ($send['curl_error'] !== '' || $send['http_code'] < 200 || $send['http_code'] >= 300 || !is_array($decoded)) {
                $reason = $send['curl_error'] !== ''
                    ? $send['curl_error']
                    : ('WAHA HTTP ' . $send['http_code']);
                if (is_array($decoded) && isset($decoded['message'])) {
                    $reason .= ': ' . (is_array($decoded['message']) ? implode(', ', $decoded['message']) : (string)$decoded['message']);
                }
                $response['status'] = false;
                $response['failed_target'] = $target;
                $response['items'][] = $item;
                return array(
                    'status' => false,
                    'reason' => $reason,
                    'response' => $response,
                    'message_ids' => $response['id'],
                    'requestid' => $requestId,
                );
            }

            $messageId = '';
            if (isset($decoded['id']['_serialized'])) {
                $messageId = (string)$decoded['id']['_serialized'];
            } elseif (isset($decoded['_data']['id']['_serialized'])) {
                $messageId = (string)$decoded['_data']['id']['_serialized'];
            } elseif (isset($decoded['id']['id'])) {
                $messageId = (string)$decoded['id']['id'];
            }

            $ack = null;
            if (isset($decoded['ack'])) {
                $ack = (int)$decoded['ack'];
            } elseif (isset($decoded['_data']['ack'])) {
                $ack = (int)$decoded['_data']['ack'];
            }

            $item['message_id'] = $messageId;
            $item['ack'] = $ack;
            $response['target'][] = $target;
            $response['id'][] = $messageId;
            $response['items'][] = $item;

            if ($delaySeconds > 0 && $index < count($targets) - 1) {
                sleep($delaySeconds);
            }
        }

        return array(
            'status' => true,
            'reason' => '',
            'response' => $response,
            'message_ids' => $response['id'],
            'requestid' => $requestId,
        );
    }

    private function crm_campaign_waha_attachment_payload($campaign)
    {
        $attachment = trim((string)($campaign['attachment_file'] ?? ''));
        if ($attachment === '') {
            return array(
                'status' => true,
                'has_attachment' => false,
                'endpoint' => '',
                'payload' => array(),
                'reason' => '',
            );
        }

        $filename = basename($attachment);
        $path = FCPATH . 'assets/uploads/crm_campaign/' . $filename;
        if (!is_file($path)) {
            return array(
                'status' => false,
                'has_attachment' => true,
                'endpoint' => '',
                'payload' => array(),
                'reason' => 'File attachment campaign tidak ditemukan.',
            );
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return array(
                'status' => false,
                'has_attachment' => true,
                'endpoint' => '',
                'payload' => array(),
                'reason' => 'File attachment campaign gagal dibaca.',
            );
        }

        $originalName = trim((string)($campaign['attachment_original_name'] ?? ''));
        if ($originalName === '') {
            $originalName = $filename;
        }

        $mime = trim((string)($campaign['attachment_mime'] ?? ''));
        if ($mime === '') {
            $mime = $this->crm_campaign_guess_mime_type($path, $originalName);
        }

        $mimeLower = strtolower($mime);
        $endpoint = '/api/sendFile';
        $payload = array(
            'file' => array(
                'mimetype' => $mime,
                'filename' => $originalName,
                'data' => base64_encode($contents),
            ),
        );

        if ($mimeLower === 'image/jpeg' || $mimeLower === 'image/jpg') {
            $endpoint = '/api/sendImage';
        } elseif (strpos($mimeLower, 'video/') === 0) {
            $endpoint = '/api/sendVideo';
            $payload['asNote'] = false;
            $payload['convert'] = false;
        }

        return array(
            'status' => true,
            'has_attachment' => true,
            'endpoint' => $endpoint,
            'payload' => $payload,
            'reason' => '',
        );
    }

    private function crm_campaign_guess_mime_type($path, $filename)
    {
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        $ext = strtolower(pathinfo((string)$filename, PATHINFO_EXTENSION));
        $map = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
        );

        return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
    }

    private function crm_campaign_waha_json_request($url, $apiKey, $payload)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 75,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'X-Api-Key: ' . $apiKey,
            ),
        ));

        $rawResponse = curl_exec($curl);
        $curlError = curl_errno($curl) ? curl_error($curl) : '';
        $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $decoded = json_decode((string)$rawResponse, true);
        return array(
            'raw' => $rawResponse,
            'decoded' => is_array($decoded) ? $decoded : null,
            'curl_error' => $curlError,
            'http_code' => $httpCode,
        );
    }

    private function crm_campaign_insert_broadcast_logs($campaignId, $targetCustomerMap, $targets, $messageIds, $responseJson, $status, $reason)
    {
        $userId = isset($_SESSION['user']['id']) ? intval($_SESSION['user']['id']) : 0;
        $decoded = json_decode((string)$responseJson, true);
        $requestId = is_array($decoded) && isset($decoded['requestid']) ? (string)$decoded['requestid'] : '';
        foreach ($targets as $idx => $target) {
            $customer = isset($targetCustomerMap[$target]) ? $targetCustomerMap[$target] : array();
            $this->db->insert('crm_campaign_broadcast_logs', array(
                'campaign_id' => (int)$campaignId,
                'customer_id' => isset($customer['id']) ? (int)$customer['id'] : 0,
                'phone' => $target,
                'fonnte_message_id' => isset($messageIds[$idx]) ? (string)$messageIds[$idx] : '',
                'request_id' => $requestId,
                'status' => $status,
                'reason' => $reason,
                'response_json' => $responseJson,
                'created_at' => DATE("Y-m-d H:i:s"),
                'created_by' => $userId,
            ));
        }
    }

    function create()
    {

        $user = $_SESSION['user'];

        // $dt['first_order'] = DATE("Y-m-d H:i:s");
        // $dt['order_month'] = $this->template->month_format_indo($dt['first_order']);
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];
        $dt['is_manual'] = 1;
        $dt['brand'] = $_GET['brand'];
        $dt['akun_type'] = 'Pelanggan';
        $dt['status'] = 'Aktif';

        if ($this->db->insert('customer', $dt)) {
            $data['param'] = $this->template->get_param();
            redirect(base_url() . 'crm?brand=' . $_GET['brand']);
        }
    }


    function store()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];

        if ($_FILES['file']['name']) {
            $input = $this->validate([
                'file' => [
                    'uploaded[file]',
                    'mime_in[file,image/jpg,image/jpeg,image/png]',
                    'max_size[file,1024]',
                ]
            ]);

            if (!$input) {
                $msg = 'Pastikan tipe file .jpg, .jpeg atau .png!';
                echo $this->template->alert_danger($msg);
                die;
            } else {
                $img = $this->request->getFile('file');
                $dir = str_replace('public/', '', FCPATH . 'assets/img/crm/');
                $img->move($dir);
                $data = [
                    'name' =>  $img->getName(),
                    'type'  => $img->getClientMimeType()
                ];
                $currentFileName = $dir . $data['name'];
                $newfile = DATE('Ymdhis') . '.' . substr(strrchr($data['name'], "."), 1);
                $newFileName = $dir . $newfile;
                rename($currentFileName, $newFileName);
                $dt['img'] = $newfile;
            }
        }


        $model = $db->table('customer');

        if ($model->insert($dt)) {
            $msg = 'Tambah data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    function sync()
    {
        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM customer WHERE id = '$id'");

        $data['data'] = $query[0];
        $this->load->view("crm/sync", $data);
    }

    function sync_process()
    {


        $id = $_POST['id'];


        $query = $this->mymodel->selectWithQuery("SELECT * FROM customer WHERE id = '$id'");

        $v = $query[0];
        $response = $this->template->get_social_media($v['type'], $v['url']);
        $dt = array();
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $customer['id'];
        if ($response['data']['like'] > 0) {
            $dt['like'] = $response['data']['like'];
            $dt['comment'] = $response['data']['comment'];
            $dt['collect'] = $response['data']['collect'];
            $dt['share'] = $response['data']['share'];
            $dt['view'] = $response['data']['view'];
            if ($v['cost'] > 0 && $dt['view'] > 0) {
                $dt['cpm'] = $v['cost'] / $dt['view'] * 1000;
            }
        }

        $model = $db->table('customer');
        $model->where('id', $v['id']);
        $model->update($dt);

        if ($response['status'] == true) {
            $msg = 'Sync data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            if ($response) {
                $msg = $response['msg'];
            } else {
                $msg = 'Data customer belum tersedia!';
            }
            echo $this->template->alert_danger($msg);
        }
    }

    function fu()
    {
        $id = $_GET['id'];
        $k = $_GET['k'];
        $data['data']['id'] = $id;
        $data['data']['k'] = $k;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM customer WHERE id = '$id'");

        $data['data'] = $query[0];

        $this->load->view("crm/fu", $data);
    }

    function fu_process()
    {

        header('Content-Type: application/json; charset=utf-8');


        $id = $_POST['id'];


        $query = $this->mymodel->selectWithQuery("SELECT * FROM customer WHERE id = '$id'");

        $data['data'] = $query[0];

        if (strpos($data['data']['phone'], '*') !== false || empty($data['data']['phone'])) {
            $msg = 'Pastikan nomor telepon benar!';
            $html['status'] = false;
            $html['url'] = 'https://api.whatsapp.com/send/?phone=' . $data['data']['phone'] . '&text=' . $text;
            $html['msg'] = $this->template->alert_danger($msg);
            echo json_encode($html, true);
        } else {
            $dt = array();
            $dt['count_fu'] = intval($data['data']['count_fu']) + 1;
            $dt['last_fu_at'] = DATE("Y-m-d");
            // $dt['waktu_fu_perkembangan'] = date('Y-m-d', strtotime($data['data']['waktu_fu_perkembangan'] . " +10 days"));
            $dt['waktu_fu_perkembangan'] = date('Y-m-d', strtotime(DATE("Y-m-d") . " +10 days"));

            $this->db->update('customer', $dt, array('id' => $id));

            $msg = 'FU H+10 perkembangan berhasil!';
            $html = array();
            if (substr($data['data']['phone'], 0, 1) === "0") {
                $data['data']['phone'] = "62" . substr($data['data']['phone'], 1);
            }
            $text = $data['data']['full_name'] . ' FU H+10 Perkembangan ke ' . $dt['count_fu'];
            $html['status'] = true;
            $html['url'] = 'https://api.whatsapp.com/send/?phone=' . $data['data']['phone'] . '&text=' . $text;
            $html['msg'] = $this->template->alert_success($msg);
            echo json_encode($html, true);
        }
    }



    function fu_2()
    {
        $id = $_GET['id'];
        $k = $_GET['k'];
        $data['data']['id'] = $id;
        $data['data']['k'] = $k;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM customer WHERE id = '$id'");

        $data['data'] = $query[0];

        $this->load->view("crm/fu_2", $data);
    }

    function fu_2_process()
    {

        header('Content-Type: application/json; charset=utf-8');


        $id = $_POST['id'];


        $query = $this->mymodel->selectWithQuery("SELECT * FROM customer WHERE id = '$id'");

        $data['data'] = $query[0];

        if (strpos($data['data']['phone'], '*') !== false || empty($data['data']['phone'])) {
            $msg = 'Pastikan nomor telepon benar!';
            $html['status'] = false;
            $html['url'] = 'https://api.whatsapp.com/send/?phone=' . $data['data']['phone'] . '&text=' . $text;
            $html['msg'] = $this->template->alert_danger($msg);
            echo json_encode($html, true);
        } else {
            $dt = array();
            $dt['count_fu_2'] = intval($data['data']['count_fu_2']) + 1;
            $dt['last_fu_2_at'] = DATE("Y-m-d");


            $this->db->update('customer', $dt, array('id' => $id));
            $msg = 'FU H-7 berhasil!';
            $html = array();
            if (substr($data['data']['phone'], 0, 1) === "0") {
                $data['data']['phone'] = "62" . substr($data['data']['phone'], 1);
            }
            $text = $data['data']['full_name'] . ' FU H-7';
            $html['status'] = true;
            $html['url'] = 'https://api.whatsapp.com/send/?phone=' . $data['data']['phone'] . '&text=' . $text;
            $html['msg'] = $this->template->alert_success($msg);
            echo json_encode($html, true);
        }
    }



    function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("crm/delete", $data);
    }

    function delete()
    {



        $id = $_POST['id'];

        if ($this->db->delete('customer', array('id' => $id))) {
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    function action()
    {

        $id_selected_v2 = $_POST['id_selected_v2'];

        $id_selected = $_POST['id_selected'];
        if ($id_selected) {
            $id = explode(',', $id_selected);
        }

        $is_manual = $_POST['is_manual'];
        $marketplace = $_POST['marketplace'];
        $brand = $_POST['brand'];
        $order_id = $_POST['order_id'];
        $code = $_GET['code'];
        $data['data']['id'] = $id;
        $data['data']['code'] = $code;
        if ($code == "hapus_data") {
            $data['question'] = "Apakah kamu yakin ingin menghapus data customer ini?";
            $data['btn'] = "Hapus Data";
        } else if ($code == "refresh_data") {
            $data['question'] = "Apakah kamu yakin ingin merefresh data customer ini?";
            $data['btn'] = "Refresh Data";
        }
        $this->load->view("crm/action", $data);
    }

    function action_process()
    {
        $code = $_POST['code'];
        $user = $_SESSION['user'];

        $id_selected = $_POST['id_selected'];
        if ($id_selected) {
            $id = explode(',', $id_selected);
        }
        $is_manual = $_POST['is_manual'];
        $marketplace = $_POST['marketplace'];
        $brand = $_POST['brand'];
        $order_id = $_POST['order_id'];
        if ($code == "hapus_data") {
            foreach ($id as $k => $v) {
                $list_id .= "'" . $v . "',";
            }

            $list_id = substr($list_id, 0, -1);


            if ($list_id) {
                $dt = array();
                $model = $db->table('customer');
                $model->where(" id IN ($list_id)");
                $model->delete();
                $msg = 'Hapus data berhasil!';
                echo $this->template->alert_success($msg);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
        } else if ($code == "refresh_data") {
            foreach ($id as $k => $v) {
                if ($v > 0) {
                    $list_id .= "" . $v . ",";
                }
            }
            $list_id = substr($list_id, 0, -1);
            if ($list_id) {

                $url = base_url() . '/influencer/sync-all-process?mode=refresh_data&ids=' . $list_id;
                $curl = curl_init();

                // echo $url;die;
                // echo '<br>';

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json'
                    ),
                ));

                $response = curl_exec($curl);
                echo $response;
                die;
                curl_close($curl);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
        }
    }


    function download()
    {
        $data['template'] = $this->template;
        $filters = $this->_crm_build_customer_filters();
        $params = $filters['params'];

        $start_date = $params['start_date'];
        $until_date = $params['until_date'];
        $brand = $params['brand'];
        $marketplace = isset($params['marketplace']) ? $params['marketplace'] : '';
        $cs = isset($params['cs']) ? $params['cs'] : '';
        $product_filter_values = isset($params['product_filter']) ? $params['product_filter'] : array();
        $keyword = $params['keyword'];
        $keyword_category = $params['keyword_category'];
        $dtf = $params['dtf'];
        $ids_array = isset($params['ids']) ? $params['ids'] : array();
        $sort = $params['sort'];

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;
        $data['product_filter'] = $product_filter_values;
        $data['dtf'] = $dtf;
        $data['ids'] = !empty($ids_array) ? implode(',', $ids_array) : '';

        $data['group_wa'] = $this->mymodel->selectWithQuery("SELECT * FROM group_wa WHERE status = 'ENABLE' ORDER BY CAST(name AS SIGNED) ASC");
        $data['shipping'] = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");
        $data['marketplace'] = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");
        $data['brands'] = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
        $data['cs'] = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role IN ('3') ORDER BY code ASC");
        $data['product'] = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif' AND is_varian = 0 AND is_operational = 0 ORDER BY sku ASC");

        $whereMain = $filters['where_with_date'];
        $whereFallback = $filters['where_without_date'];
        $order_by = $filters['order_by'];

        $latestTransactionJoin = "customer INNER JOIN (SELECT t.* FROM transaction t INNER JOIN (SELECT customer, MAX(id) AS max_id FROM transaction GROUP BY customer) latest ON latest.customer = t.customer AND latest.max_id = t.id) transaction ON customer.id = transaction.customer";
        $query = $this->mymodel->selectWithQuery("SELECT customer.*, transaction.cb_cl FROM $latestTransactionJoin WHERE $whereMain $order_by");
        if (empty($query) && $whereFallback !== $whereMain) {
            $query = $this->mymodel->selectWithQuery("SELECT customer.*, transaction.cb_cl FROM $latestTransactionJoin WHERE $whereFallback $order_by");
        }

        $data['header'] = array();

        $header_1 = array(
            "NO",
            "CB/CL",
            "TGL DIBUAT",
            "TGL ORDER",
            "BRAND",
            "MARKETPLACE",
            "TOKO",
            "NAMA",
            "NO HP",
            "USERNAME",
            "NO PESANAN",
            "TANGGAL PESANAN",
            "QTY PRODUK",
            "NAMA PRODUK",
            "ALAMAT",
        );

        $body_1 = array(
            "cb_cl",
            "cb_cl",
            "id_buyer",
            "brand",
            "marketplace",
            "shop_name",
            "full_name",
            "phone",
            "username",
            "no_pesanan",
            "tanggal_pesanan",
            "qty_produk",
            "nama_produk",
            "address",
        );

        $this->spreadsheet = new Spreadsheet();

        $this->spreadsheet->getProperties()
            ->setCreator('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setLastModifiedBy('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setTitle('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setSubject('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setDescription('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setKeywords('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date));



        $style_col = array(
            'font' => array('bold' => true),
            'alignment' => array(
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => Border::BORDER_THIN),
                'right' => array('style'  => Border::BORDER_THIN),
                'bottom' => array('style'  => Border::BORDER_THIN),
                'left' => array('style'  => Border::BORDER_THIN)
            ),
            'fill' => array(
                'type' => Fill::FILL_SOLID,
                'color' => array('rgb' => 'aeb5bc')
            ),
        );

        $style_row = array(
            'alignment' => array(
                'vertical' => Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => Border::BORDER_THIN),
                'right' => array('style'  => Border::BORDER_THIN),
                'bottom' => array('style'  => Border::BORDER_THIN),
                'left' => array('style'  => Border::BORDER_THIN)
            )
        );

        $i = 0;
        foreach ($header_1 as $kk => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)
                ->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('dcdcdb');
            $this->spreadsheet
                ->getActiveSheet()
                ->getStyle($code)
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN);
            $i++;
        }
        $column = 2;
        foreach ($query as $k => $v) {
            $index = 1;
            $value = $k + 1;
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            if ($v['cb_cl'] != "CL") {
                $v['cb_cl'] = "CB";
            }
            $value = $v['cb_cl'];
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $value = $v['created_at'];
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $value = $v['last_order'];
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $value = $v['brand'];
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $value = $v['marketplace'];
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $value = $v['shop_name'];
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $value = $v['full_name'];
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $value = $v['phone'];
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $value = $v['username'];
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $order_ids = array();
            $order_dates = array();
            $product_qtys = array();
            $product_names = array();
            $orders_raw = json_decode($v['pesanan'], true);
            if (is_array($orders_raw)) {
                foreach ($orders_raw as $order_row) {
                    if (!is_array($order_row)) {
                        continue;
                    }

                    $order_id = isset($order_row['order_id']) ? trim((string)$order_row['order_id']) : '';
                    $order_date_raw = isset($order_row['date']) ? trim((string)$order_row['date']) : '';
                    $order_date = '';
                    if ($order_date_raw !== '' && strtotime($order_date_raw) !== false) {
                        $order_date = date("d/m/Y", strtotime($order_date_raw));
                    } else if ($order_date_raw !== '') {
                        $order_date = $order_date_raw;
                    }

                    $items = isset($order_row['data']) && is_array($order_row['data']) ? $order_row['data'] : array();
                    if (!empty($items)) {
                        foreach ($items as $item_row) {
                            if (!is_array($item_row)) {
                                continue;
                            }
                            $qty = isset($item_row['qty']) ? trim((string)$item_row['qty']) : '';
                            $name = isset($item_row['item_name']) ? trim((string)$item_row['item_name']) : '';
                            $order_ids[] = $order_id;
                            $order_dates[] = $order_date;
                            $product_qtys[] = $qty;
                            $product_names[] = $name;
                        }
                    } else {
                        $order_ids[] = $order_id;
                        $order_dates[] = $order_date;
                        $product_qtys[] = '';
                        $product_names[] = '';
                    }
                }
            }

            $value = implode("\n", $order_ids);
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $value = implode("\n", $order_dates);
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $value = implode("\n", $product_qtys);
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $value = implode("\n", $product_names);
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $value = $v['address'];
            $index_alpha = $this->template->get_name_from_number($index);
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $value);
            $this->spreadsheet->getActiveSheet()
                ->getStyle($index_alpha . $column)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $index++;

            $column++;
        }

        $sheet = $this->spreadsheet->getActiveSheet();
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $writer = new Xlsx($this->spreadsheet);
        $filename = 'crm-' . DATE("YmdHis") . '.xlsx';
        $file_path = str_replace('public/', '', FCPATH . 'assets/webfile/excel/') . $filename;

        $writer->save($file_path);

        redirect(base_url() . 'assets/webfile/excel/' . $filename);
    }

    public function kpi_logs()
    {
        $this->ensure_crm_kpi_content_table();

        $month = $this->input->get('month', true);
        if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $employee_id = (int) ($this->input->get('employee_id', true) ?? 0);
        if ($employee_id <= 0) {
            $employee_id = (int) ($_SESSION['user']['id'] ?? 0);
        }

        $crm_users = $this->mymodel->selectWithQuery("
            SELECT
                u.id,
                u.full_name
            FROM user u
            INNER JOIN user_profile up ON up.user_id = u.id
                AND up.id = (
                    SELECT MAX(up2.id) FROM user_profile up2 WHERE up2.user_id = u.id
                )
            WHERE up.position_id = 28
            ORDER BY u.full_name ASC
        ");

        if ($employee_id <= 0 && !empty($crm_users)) {
            $employee_id = (int) ($crm_users[0]['id'] ?? 0);
        }

        $rows = [];
        if ($employee_id > 0) {
            $rows = $this->mymodel->selectWithQuery("
                SELECT id, question, upload_link
                FROM crm_kpi_content_logs
                WHERE month_key = '" . $this->db->escape_str($month) . "'
                  AND period_type = 'month'
                  AND employee_id = '" . (int) $employee_id . "'
                ORDER BY id ASC
            ");
        }

        $data = [];
        $data['template'] = $this->template;
        $data['title'] = 'CRM KPI Logs - ' . $this->template->title();
        $data['month'] = $month;
        $data['employee_id'] = $employee_id;
        $data['crm_users'] = $crm_users;
        $data['rows'] = $rows;
        $data['content'] = $this->load->view("crm/kpi_logs", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function save_kpi_logs()
    {
        $this->ensure_crm_kpi_content_table();
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $month = $this->input->post('month', true);
        if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $this->json_response_simple(false, 'Bulan tidak valid.');
        }

        $employee_id = (int) ($this->input->post('employee_id', true) ?? 0);
        if ($employee_id <= 0) {
            return $this->json_response_simple(false, 'Karyawan CRM tidak valid.');
        }

        $rows = $this->input->post('rows');
        if (!is_array($rows)) {
            $rows = [];
        }

        $payload_rows = [];
        foreach ($rows as $row) {
            $question = trim((string) ($row['question'] ?? ''));
            $upload_link = trim((string) ($row['upload_link'] ?? ''));
            if ($question === '' && $upload_link === '') {
                continue;
            }
            $payload_rows[] = [
                'month_key' => $month,
                'period_type' => 'month',
                'employee_id' => $employee_id,
                'question' => $question,
                'upload_link' => $upload_link,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        $this->db->trans_start();
        $this->db->delete('crm_kpi_content_logs', [
            'month_key' => $month,
            'period_type' => 'month',
            'employee_id' => $employee_id,
        ]);
        foreach ($payload_rows as $row) {
            $this->db->insert('crm_kpi_content_logs', $row);
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return $this->json_response_simple(false, 'Gagal menyimpan KPI content log.');
        }

        return $this->json_response_simple(true, 'KPI content log berhasil disimpan.');
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

    private function json_response_simple($status, $message)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => (bool) $status,
                'message' => $message,
            ]));
    }
}
