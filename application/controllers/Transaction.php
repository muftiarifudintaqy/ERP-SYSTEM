<?php

// PhpSpreadsheet autoload commented out to prevent PHP version conflicts
// Only load when specifically needed for Excel operations
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Google\Client as Google_Client;
use Google\Service\Drive as Google_Service_Drive;
use Google\Service\Oauth2 as Google_Service_Oauth2;
use Google\Service\Sheets as Google_Service_Sheets;
use Google\Service\Sheets\Spreadsheet as Google_Spreadsheet;
use Google\Service\Sheets\ValueRange as Google_ValueRange;

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Transaction extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        
        // Load required libraries and models
        $this->load->database();
        $this->load->model('mymodel');
        
        // Set public methods that don't require permission checks
        $this->set_public_methods([
            'sync_pencairan' // API endpoint for external sync
        ]);
        
        // Set custom method permissions if needed
        $this->set_method_permissions([
            'export_excel' => 'view', // Export requires view permission
            'import_excel' => 'create' // Import requires create permission
        ]);
    }

    function sync_pencairan()
    {
        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM transaction WHERE order_status IN ('COMPLETED','DELIVERED')
        AND dana_pencairan = 0
        ORDER BY updated_at ASC
        LIMIT 10
        ");
        $arr = array();
        foreach ($data as $k => $v) {
            $dt = array();
            $dt['order_id'] = $v['order_id'];
            $dt['marketplace'] = $v['marketplace'];
            $dt['order_status'] = $v['order_status'];
            $dt['updated_at'] = DATE("Y-m-d H:i:s");

            $this->db->update('transaction', $dt, array('id' => $v['id']));

            $url = $this->template->endpoint_url() . 'api/marketplace/order/detail?order_id=' . $dt['order_id'] . '&marketplace=' . $dt['marketplace'];

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
            $response = json_decode($response, true);
            curl_close($curl);

            $dt['msg'] = $response;
            $arr[] = $dt;
        }
        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['code'] = 200;
        $html['status'] = true;
        $html['data'] = $arr;
        echo json_encode($html, true);
    }
    public function refresh_token_process()
    {


        $brand = $_GET['brand'];


        $_SESSION['brand'] = $brand;

        $brand = $_SESSION['brand'];

        $url = base_url() . '/api/auth/refresh-token/shopee?brand=' . $brand;

        if ($_GET['channel'] == "SHOPEE") {
            $url = base_url() . '/api/auth/refresh-token/shopee?brand=' . $brand;
        } else if ($_GET['channel'] == "LAZADA") {
            $url = base_url() . '/api/auth/refresh-token/lazada?brand=' . $brand;
        } else if ($_GET['channel'] == "TIKTOK") {
            $url =  base_url() . '/api/auth/refresh-token/tiktok?brand=' . $brand;
        }

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
            ),
        ));

        $response = curl_exec($curl);
        $response = json_decode($response, true);

        curl_close($curl);
        if ($response['status'] == true && $_GET['channel']) {
            $msg = $response['msg'];
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = $response['msg'];
            echo $this->template->alert_danger($msg);
            die;
        }
    }
    public function index()
    {
        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Order ID";
        }
        $data['keyword_category'] = $keyword_category;
        $today = DATE("Y-m-d");
        if ($_GET['start_date'] == "") {
            $start_date = DATE("Y-m-01");
            
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
        $c_type = $_GET['c_type'];

        $per_page_options = [30, 50, 100, 200, 500];
        $default_limit = 30;

        if (isset($_GET['limit']) && in_array((int)$_GET['limit'], $per_page_options, true)) {
            $limit = (int)$_GET['limit'];
            $_SESSION['transaction_limit'] = $limit;
        } else if (isset($_SESSION['transaction_limit']) && in_array((int)$_SESSION['transaction_limit'], $per_page_options, true)) {
            $limit = (int)$_SESSION['transaction_limit'];
        } else {
            $limit = $default_limit;
            $_SESSION['transaction_limit'] = $limit;
        }

        $allowed_views = ['card', 'table'];
        if (isset($_GET['view']) && in_array($_GET['view'], $allowed_views, true)) {
            $current_view = $_GET['view'];
            $_SESSION['transaction_view'] = $current_view;
        } else {
            $current_view = 'table';
            $_SESSION['transaction_view'] = $current_view;
        }
        $_GET['view'] = $current_view; // pastikan helper get_param tetap menyertakan nilai ini
        $data['current_view'] = $current_view;

        $brand = $_GET['brand'];
        $keyword = $_GET['keyword'];
        $rts_start_raw = $_GET['rts_start'] ?? '';
        $rts_end_raw = $_GET['rts_end'] ?? '';
        $rts_date = $_GET['rts_date'] ?? '';

        $rts_start = '';
        $rts_end = '';

        if ($rts_start_raw) {
            $ts = strtotime($rts_start_raw);
            if ($ts) {
                $rts_start = date('Y-m-d H:i:s', $ts);
            }
        }

        if ($rts_end_raw) {
            $ts = strtotime($rts_end_raw);
            if ($ts) {
                $rts_end = date('Y-m-d H:i:s', $ts);
            }
        }

        if (!$rts_start && !$rts_end && $rts_date) {
            $start_ts = strtotime($rts_date . ' 00:00:00');
            $end_ts = strtotime($rts_date . ' 23:59:59');
            if ($start_ts) {
                $rts_start = date('Y-m-d H:i:s', $start_ts);
            }
            if ($end_ts) {
                $rts_end = date('Y-m-d H:i:s', $end_ts);
            }
        }

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $data['title'] = 'Order - ' . $this->template->title();


        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif' AND is_varian = 0
        ORDER BY sku ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user
        WHERE role IN ('3')
        ORDER BY full_name ASC
        ");

        $data['cs'] = $query;

        $query = $this->mymodel->selectWithQuery("
                    SELECT DISTINCT 
                        CASE 
                            WHEN name LIKE '%DI AMBIL DI KANTOR/COD%' THEN name
                            WHEN name LIKE '%Agen SPX Express%' THEN name
                            WHEN name LIKE '%SHOPEE EX%' THEN name
                            ELSE SUBSTRING_INDEX(name, ' ', 1)
                        END AS name
                    FROM shipping
                    ORDER BY name ASC;
                ");

        $data['shipping'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");

        $data['marketplace'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT id,code as opt FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

        $data['brands'] = $query;

        $data['store'] = $this->mymodel->selectWithQuery("SELECT shop_id as id, shop_name as opt, opt as marketplace FROM marketplace_config WHERE status = 'Aktif' AND LOWER(opt) <> 'meta' ORDER BY marketplace DESC, shop_name ASC");

        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }

        if ($brand == "LAINNYA") {
            $ids = "";
            foreach ($data['brands'] as $k => $v) {
                $ids .= "'" . $v['opt'] . "',";
            }
            $ids = substr($ids, 0, -1);
            $qry .= " AND brand NOT IN ($ids) ";
        } else {
            if ($brand) {
                $qry .= " AND brand = '$brand' ";
            }
        }

        $ekspedisi = $_GET['ekspedisi'];
        if ($ekspedisi) {
            $qry .= " AND shipping LIKE '%$ekspedisi%' ";
        }

        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }

        if ($cs) {
            $qry .= " AND cs = '$cs' ";
        }

        if ($rts_start) {
            $qry .= " AND rts_at >= '".$this->db->escape_str($rts_start)."' ";
        }

        if ($rts_end) {
            $qry .= " AND rts_at <= '".$this->db->escape_str($rts_end)."' ";
        }

        if ($order_status) {
            // Support multi-select: order_status bisa comma-separated
            $status_list = array_filter(array_map('trim', explode(',', $order_status)));

            if (count($status_list) === 1) {
                $single = $status_list[0];
                if ($single == "WEBHOOK") {
                    $qry .= " AND is_webhook = 0 AND is_manual = 0";
                } else if ($single == "ACTIVE") {
                    $qry .= " AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
                } else if ($single == "READY_TO_SHIP") {
                    $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
                } else if ($single == "UNPAID") {
                    $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') AND customer_price > 0";
                } else if ($single == "SETTLEMENT") {
                    $qry .= " AND pencairan_status = 'Settlement' ";
                } else if ($single == "CANCELLED") {
                    $qry .= " AND order_status IN ('CANCELLED','IN_CANCEL') ";
                } else if ($single == "PROCESSED") {
                    $qry .= " AND order_status = 'PROCESSED' ";
                } else if ($single == "RETURN") {
                    $qry .= " AND order_status IN ('RETURN','RETURN_UNSHIPPED') ";
                } else {
                    $qry .= " AND order_status = '".$this->db->escape_str($single)."' ";
                }
            } else {
                $conditions = [];
                foreach ($status_list as $st) {
                    if ($st == "WEBHOOK") {
                        $conditions[] = "(is_webhook = 0 AND is_manual = 0)";
                    } else if ($st == "ACTIVE") {
                        $conditions[] = "(order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID'))";
                    } else if ($st == "READY_TO_SHIP") {
                        $conditions[] = "(order_status IN ('READY_TO_SHIP','PENDING'))";
                    } else if ($st == "UNPAID") {
                        $conditions[] = "(payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') AND customer_price > 0)";
                    } else if ($st == "SETTLEMENT") {
                        $conditions[] = "(pencairan_status = 'Settlement')";
                    } else if ($st == "CANCELLED") {
                        $conditions[] = "(order_status IN ('CANCELLED','IN_CANCEL'))";
                    } else if ($st == "PROCESSED") {
                        $conditions[] = "(order_status = 'PROCESSED')";
                    } else if ($st == "RETURN") {
                        $conditions[] = "(order_status IN ('RETURN','RETURN_UNSHIPPED'))";
                    } else {
                        $conditions[] = "(order_status = '".$this->db->escape_str($st)."')";
                    }
                }
                $qry .= " AND (" . implode(" OR ", $conditions) . ")";
            }
        }

        $printed_status = $_GET['printed_status'] ?? '';
        if ($printed_status) {
            if ($printed_status == "Sudah Dicetak") {
                $qry .= " AND print_at != '' ";
            } else if ($printed_status == "Belum Dicetak") {
                $qry .= " AND print_at = '' ";
            }
        }

        $pencairan = $_GET['pencairan'];
        if ($pencairan) {
            if ($pencairan == "Sudah Pencairan") {
                $qry .= " AND dana_pencairan > 0";
            } else if ($pencairan == "Belum Pencairan") {
                $qry .= " AND order_status IN ('PROCESSED','SHIPPED','COMPLETED', 'READY_TO_SHIP', 'DELIVERED') AND c_type NOT IN ('Affiliate','Endorse','Free') AND dana_pencairan = 0 AND is_disbursement = 0 ";
            }
        }

        $payment_type = $_GET['payment_type'];
        if ($payment_type) {
            $qry .= " AND payment_type = '$payment_type' ";
        }




        if ($keyword) {
            if ($keyword_category == "Order ID") {
                $qry .= " AND order_id LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND c_username LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Pelanggan") {
                $qry .= " AND customer_text LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Pelanggan") {
                $qry .= " AND phone LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Resi") {
                $qry .= " AND awb_number LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Produk") {
                $qry .= " AND `json` LIKE '%$keyword%' ";
            }
        }

        $order_type = $_GET['order_type'];
        $data['order_type'] = $order_type;
        if ($order_type == "Manual") {
            $qry .= " AND is_manual = 1 ";
        } else if ($order_type == "Marketplace") {
            $qry .= " AND is_manual = 0 ";
        } else if ($order_type == "Belum Dikonfigurasi") {
            $qry .= " AND is_configurated = 0 ";
        }

        if ($c_type) {
            $qry .= " AND c_type = '$c_type' ";
        }

        $shop_id = $_GET['shop_id'];
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM transaction
        WHERE $qry AND type_sub = 'POS' ");

        $data['page'] = CEIL($query[0]['count'] / $limit);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/transaction?keyword=' . $_GET['keyword'] . '&brand=' . $_GET['brand'] . '&marketplace=' . $_GET['marketplace'] . '&cs=&start_date=' . $start_date . '&until_date=' . $until_date;

        $url = base_url() . '/transaction/' . $this->template->get_param();
        $data['url_1'] = $this->template->get_param_without_order_status($url);
        $data['url_2'] = $this->template->get_param_without_keyword_category($url);
        $data['url_3'] = $this->template->get_param_without('order_type');
        $data['url_4'] = $this->template->get_param_without('pencairan');
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);


        $filter = $_GET;
        $filter['start_date'] = $start_date;
        $filter['until_date'] = $until_date;

        $_SESSION['filter'] = $filter;

        $data['print_history'] = $this->getShippingPrintHistory(20);

        $data['content'] = $this->load->view('transaction/all', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function item()
    {
        $data['template'] = $this->template;

        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $until_date = $_GET['until_date'] ?? date('Y-m-d');
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;

        $brand = $_GET['brand'] ?? '';
        $marketplace = $_GET['marketplace'] ?? '';
        $cs = $_GET['cs'] ?? '';
        $keyword = $_GET['keyword'] ?? '';
        $id = $_GET['id'] ?? '';
        $order_status = $_GET['order_status'] ?? '';
        $keyword_category = $_GET['keyword_category'] ?? 'Order ID';
        $c_type = $_GET['c_type'] ?? '';
        $rts_start_raw = $_GET['rts_start'] ?? '';
        $rts_end_raw = $_GET['rts_end'] ?? '';
        $rts_date = $_GET['rts_date'] ?? '';

        $allowed_views = ['card', 'table'];
        if (isset($_GET['view']) && in_array($_GET['view'], $allowed_views, true)) {
            $current_view = $_GET['view'];
            $_SESSION['transaction_view'] = $current_view;
        } else {
            $current_view = 'table';
            $_SESSION['transaction_view'] = $current_view;
        }
        $_GET['view'] = $current_view;
        $data['current_view'] = $current_view;

        $rts_start = '';
        $rts_end = '';

        if ($rts_start_raw) {
            $ts = strtotime($rts_start_raw);
            if ($ts) {
                $rts_start = date('Y-m-d H:i:s', $ts);
            }
        }

        if ($rts_end_raw) {
            $ts = strtotime($rts_end_raw);
            if ($ts) {
                $rts_end = date('Y-m-d H:i:s', $ts);
            }
        }

        $per_page_options = [30, 50, 100, 200, 500];
        $default_limit = 30;
        if (isset($_GET['limit']) && in_array((int)$_GET['limit'], $per_page_options, true)) {
            $limit = (int)$_GET['limit'];
            $_SESSION['transaction_limit'] = $limit;
        } else if (isset($_SESSION['transaction_limit']) && in_array((int)$_SESSION['transaction_limit'], $per_page_options, true)) {
            $limit = (int)$_SESSION['transaction_limit'];
        } else {
            $limit = $default_limit;
            $_SESSION['transaction_limit'] = $limit;
        }

        if (!$rts_start && !$rts_end && $rts_date) {
            $start_ts = strtotime($rts_date . ' 00:00:00');
            $end_ts = strtotime($rts_date . ' 23:59:59');
            if ($start_ts) {
                $rts_start = date('Y-m-d H:i:s', $start_ts);
            }
            if ($end_ts) {
                $rts_end = date('Y-m-d H:i:s', $end_ts);
            }
        }
        $ff = $this->input->get('filter_field');
        $fv = $this->input->get('filter_value');
        $fo = $this->input->get('filter_operator');

        $filters = [];
        if (is_array($ff) && is_array($fv)) {
            $n = min(count($ff), count($fv));
            for ($i = 0; $i < $n; $i++) {
                $filters[] = [
                    'field' => (string)$ff[$i],
                    'value' => (string)$fv[$i],
                    'op'    => isset($fo[$i]) ? (string)$fo[$i] : 'equals',
                ];
            }
        } elseif (!empty($ff) && !empty($fv)) {
            $filters[] = [
                'field' => (string)$ff,
                'value' => (string)$fv,
                'op'    => !empty($fo) ? (string)$fo : 'equals',
            ];
        }

        $data['order_status']      = $order_status;
        $data['c_type']            = $c_type;
        $data['keyword']           = $keyword;
        $data['keyword_category']  = $keyword_category;
        $data['brand']             = $brand;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role IN ('3') ORDER BY full_name ASC");
        $data['cs'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif' AND is_varian = 0 ORDER BY sku ASC");
        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("
                    SELECT DISTINCT 
                        CASE 
                            WHEN name LIKE '%DI AMBIL DI KANTOR/COD%' THEN name
                            WHEN name LIKE '%Agen SPX Express%' THEN name
                            WHEN name LIKE '%SHOPEE EX%' THEN name
                            ELSE SUBSTRING_INDEX(name, ' ', 1)
                        END AS name
                    FROM shipping
                    ORDER BY name ASC;
                ");
        $data['shipping'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");
        $data['marketplace'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT id,code as opt FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
        $data['brands'] = $query;

        $qry = "";
        $id_customer = $_GET['id_customer'] ?? '';

        if ($id_customer) {
            $qry = " customer = '".$this->db->escape_str($id_customer)."' ";
        } else {
            $qry = " DATE(date) >= '".$this->db->escape_str($start_date)."'
                    AND DATE(date) <= '".$this->db->escape_str($until_date)."' ";

            if ($brand == "LAINNYA") {
                $ids = "";
                foreach ($data['brands'] as $k => $v) {
                    $ids .= "'" . $this->db->escape_str($v['opt']) . "',";
                }
                $ids = rtrim($ids, ',');
                $qry .= " AND brand NOT IN ($ids) ";
            } elseif ($brand) {
                $qry .= " AND brand = '".$this->db->escape_str($brand)."' ";
            }
        }

        $ids = $_GET['ids'] ?? '';
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id IN ($ids) ";
        }

        $ekspedisi = $_GET['ekspedisi'] ?? '';
        if ($ekspedisi) {
            $qry .= " AND shipping LIKE '%".$this->db->escape_str($ekspedisi)."%' ";
        }

        if ($marketplace) {
            $qry .= " AND marketplace = '".$this->db->escape_str($marketplace)."' ";
        }

        if ($cs) {
            $qry .= " AND cs = '".$this->db->escape_str($cs)."' ";
        }

        if ($rts_start) {
            $qry .= " AND rts_at >= '".$this->db->escape_str($rts_start)."' ";
        }

        if ($rts_end) {
            $qry .= " AND rts_at <= '".$this->db->escape_str($rts_end)."' ";
        }

        if ($order_status) {
            // Support multi-select: order_status bisa comma-separated
            $status_list = array_filter(array_map('trim', explode(',', $order_status)));

            if (count($status_list) === 1) {
                // Single status - gunakan logika khusus yang sudah ada
                $single = $status_list[0];
                if ($single == "WEBHOOK") {
                    $qry .= " AND is_webhook = 0 AND is_manual = 0";
                } else if ($single == "ACTIVE") {
                    $qry .= " AND order_status NOT IN ('RETURN', 'RETURN_UNSHIPPED','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
                } else if ($single == "READY_TO_SHIP") {
                    $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
                } else if ($single == "UNPAID") {
                    $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') AND customer_price > 0";
                } else if ($single == "SETTLEMENT") {
                    $qry .= " AND pencairan_status = 'Settlement' ";
                } else if ($single == "CANCELLED") {
                    $qry .= " AND order_status IN ('CANCELLED','IN_CANCEL') ";
                } else if ($single == "PROCESSED") {
                    $qry .= " AND order_status = 'PROCESSED'";
                } else if ($single == "RETURN") {
                    $qry .= " AND order_status IN ('RETURN','RETURN_UNSHIPPED') ";
                } else {
                    $qry .= " AND order_status = '".$this->db->escape_str($single)."' ";
                }
            } else {
                // Multi-select: gabungkan kondisi tiap status dengan OR
                $conditions = [];
                foreach ($status_list as $st) {
                    if ($st == "WEBHOOK") {
                        $conditions[] = "(is_webhook = 0 AND is_manual = 0)";
                    } else if ($st == "ACTIVE") {
                        $conditions[] = "(order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID'))";
                    } else if ($st == "READY_TO_SHIP") {
                        $conditions[] = "(order_status IN ('READY_TO_SHIP','PENDING'))";
                    } else if ($st == "UNPAID") {
                        $conditions[] = "(payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') AND customer_price > 0)";
                    } else if ($st == "SETTLEMENT") {
                        $conditions[] = "(pencairan_status = 'Settlement')";
                    } else if ($st == "CANCELLED") {
                        $conditions[] = "(order_status IN ('CANCELLED','IN_CANCEL'))";
                    } else if ($st == "PROCESSED") {
                        $conditions[] = "(order_status = 'PROCESSED')";
                    } else if ($st == "RETURN") {
                        $conditions[] = "(order_status IN ('RETURN','RETURN_UNSHIPPED'))";
                    } else {
                        $conditions[] = "(order_status = '".$this->db->escape_str($st)."')";
                    }
                }
                $qry .= " AND (" . implode(" OR ", $conditions) . ")";
            }
        }

        $printed_status = $_GET['printed_status'] ?? '';
        if ($printed_status) {
            if ($printed_status == "Sudah Dicetak") {
                $qry .= " AND print_at != '' ";
            } else if ($printed_status == "Belum Dicetak") {
                $qry .= " AND print_at = '' ";
            }
        }

        $pencairan = $_GET['pencairan'] ?? '';
        if ($pencairan) {
            if ($pencairan == "Sudah Pencairan") {
                $qry .= " AND dana_pencairan > 0";
            } else if ($pencairan == "Belum Pencairan") {
                $qry .= " AND order_status IN ('PROCESSED','SHIPPED','COMPLETED', 'READY_TO_SHIP', 'DELIVERED') AND c_type NOT IN ('Affiliate','Endorse','Free') AND dana_pencairan = 0 AND is_disbursement = 0 ";
            }
        }

        $payment_type = $_GET['payment_type'] ?? '';
        if ($payment_type) {
            $qry .= " AND payment_type = '".$this->db->escape_str($payment_type)."' ";
        }

        if ($keyword) {
            $keyword = $this->db->escape_str($keyword);
            switch ($keyword_category) {
                case "Order ID":        $qry .= " AND order_id LIKE '%$keyword%' "; break;
                case "Username":        $qry .= " AND c_username LIKE '%$keyword%' "; break;
                case "Nama Pelanggan":  $qry .= " AND customer_text LIKE '%$keyword%' "; break;
                case "Nomor Pelanggan": $qry .= " AND phone LIKE '%$keyword%' "; break;
                case "Nomor Resi":      $qry .= " AND awb_number LIKE '%$keyword%' "; break;
                case "Nama Produk":     $qry .= " AND `json` LIKE '%$keyword%' "; break;
            }
        }

        $order_type = $_GET['order_type'] ?? '';
        $data['order_type'] = $order_type;
        if ($order_type) {
            if ($order_type == "Manual") {
                $qry .= " AND is_manual = 1 ";
            } else if ($order_type == "Marketplace") {
                $qry .= " AND is_manual = 0 ";
            } else if ($order_type == "Belum Dikonfigurasi") {
                $qry .= " AND is_configurated = 0 ";
            }
        }

        if ($c_type) {
            $qry .= " AND c_type = '".$this->db->escape_str($c_type)."' ";
        }

        $shop_id = $_GET['shop_id'] ?? '';
        if ($shop_id) {
            $qry .= " AND shop_id = '".$this->db->escape_str($shop_id)."' ";
        }

        // Server-side filter dari AG Grid
        if (isset($_GET['filter_field']) && isset($_GET['filter_value']) && isset($_GET['filter_operator'])) {
            $filter_fields = (array)$_GET['filter_field'];
            $filter_values = (array)$_GET['filter_value'];
            $filter_operators = (array)$_GET['filter_operator'];

            $filterConditions = [];
            
            foreach ($filter_fields as $index => $field) {
                if (isset($filter_values[$index]) && isset($filter_operators[$index])) {
                    $value = $this->db->escape_str($filter_values[$index]);
                    $operator = $this->db->escape_str($filter_operators[$index]);

                    $allowed_fields = [
                        'order_id','customer_text','pesanan_count','customer_price',
                        'pencairan_status',
                        'order_status','awb_number','marketplace','shop_name','brand','phone','is_manual',
                        'payment_type','c_username','cs','shipping','shipping_status','reverse_id',
                        'return_status','payment_status','pay_at','dana_pencairan','omset_kotor',
                        'diskon_penjual','biaya_lainnya','omset_bersih','marketplace_fee','komisi_afiliasi',
                        'hpp','c_type', 'rts_at', 'print_at', 'json', 'pesanan', 'pesanan_sku', 'siap_cetak',
                        'return_condition'
                    ];
                    if (!in_array($field, $allowed_fields)) continue;

                    if ($field === 'siap_cetak') {
                        if ($value === 'Siap Cetak') {
                            $filterConditions[] = "(BINARY order_status = 'PROCESSED')";
                        } else if ($value === 'Belum Siap') {
                            $filterConditions[] = "(order_status IS NULL OR BINARY order_status <> 'PROCESSED')";
                        }
                        continue;
                    }

                    if ($field === 'return_condition') {
                        $conditionValue = strtoupper($value);
                        if ($conditionValue === 'GOOD') {
                            $filterConditions[] = "EXISTS (
                                SELECT 1 FROM stock rs
                                WHERE rs.id_trx = `transaction`.id
                                  AND rs.type_sub = 'POS'
                                  AND rs.order_status LIKE '%RETURN%'
                                  AND rs.type = 'In'
                                  AND COALESCE(rs.qty_in_pos, 0) > 0
                            )";
                        } else if ($conditionValue === 'BAD') {
                            $filterConditions[] = "EXISTS (
                                SELECT 1 FROM stock rs
                                WHERE rs.id_trx = `transaction`.id
                                  AND rs.type_sub = 'POS'
                                  AND rs.order_status LIKE '%RETURN%'
                                  AND rs.type = 'Out'
                                  AND COALESCE(rs.qty_out_retur, 0) > 0
                            )";
                        }
                        continue;
                    }

                    $column = ($field === 'pesanan_sku') ? 'pesanan' : $field;

                    if ($value === '-') {
                        $filterConditions[] = "($column IS NULL OR $column = '' OR $column = '-')";
                    } else if ($operator === 'equals') {
                        $filterConditions[] = "$column = '$value'";
                    } else if ($operator === 'contains') {
                        $filterConditions[] = "$column LIKE '%$value%'";
                    } else if ($operator === 'startsWith') {
                        $filterConditions[] = "$column LIKE '$value%'";
                    } else if ($operator === 'endsWith') {
                        $filterConditions[] = "$column LIKE '%$value'";
                    }
                }
            }

            if (!empty($filterConditions)) {
                $qry .= " AND (" . implode(" OR ", $filterConditions) . ")";
            }
        }

        $data['limit'] = $limit;
        $data['per_page_options'] = $per_page_options;

        $current_page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($current_page - 1) * $limit;

        $allowed_columns = ['id','date','order_id','customer_text','phone','awb_number','order_status','payment_status','pesanan_count','customer_price','hpp','c_type', 'print_at', 'pesanan', 'pesanan_sku', 'siap_cetak'];
        $sort_column_param = $_GET['sort_column'] ?? '';
        if ($sort_column_param === 'pesanan_sku') {
            $sort_column = 'pesanan';
        } else if ($sort_column_param === 'siap_cetak') {
            $sort_column = "(CASE WHEN BINARY order_status = 'PROCESSED' THEN 1 ELSE 0 END)";
        } else {
            $sort_column = in_array($sort_column_param, $allowed_columns) ? $sort_column_param : 'date';
        }
        $sort_order = strtoupper($_GET['sort_order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';
        $sort_by_hpp = ($sort_column_param === 'hpp');

        $count_query = $this->mymodel->selectWithQuery("SELECT COUNT(*) as total FROM transaction WHERE $qry AND type_sub = 'POS'");
        $total_data = $count_query[0]['total'] ?? 0;
        $data['total_data'] = $total_data;
        $data['page'] = ceil($total_data / $limit);
        $data['current_page'] = $current_page;

        if ($sort_by_hpp) {
            // HPP dihitung dari json × price_buy, tidak ada di kolom DB
            // Fetch semua data, hitung HPP, sort di PHP, lalu paginate
            $query = $this->mymodel->selectWithQuery("
                SELECT * FROM transaction
                WHERE $qry AND type_sub = 'POS'
                ORDER BY id DESC
            ");

            // Prefetch price_buy
            $allPids = [];
            foreach ($query as $row) {
                if (empty($row['json'])) continue;
                $obj = json_decode($row['json'], true);
                if (!is_array($obj)) continue;
                foreach ($obj as $it) {
                    if (!empty($it['product'])) $allPids[] = (int)$it['product'];
                }
            }
            $allPids = array_values(array_unique(array_filter($allPids)));
            $pbMap = [];
            if (!empty($allPids)) {
                $in = implode(',', $allPids);
                $rowsPB = $this->mymodel->selectWithQuery("SELECT id, price_buy FROM product WHERE id IN ($in)");
                foreach ($rowsPB as $r) $pbMap[(int)$r['id']] = (float)$r['price_buy'];
            }

            // Hitung HPP per baris
            foreach ($query as &$row) {
                $hppVal = 0.0;
                if (!empty($row['json'])) {
                    $obj = json_decode($row['json'], true);
                    if (is_array($obj)) {
                        foreach ($obj as $it) {
                            $pid = isset($it['product']) ? (int)$it['product'] : 0;
                            $qty = (float)($it['qty'] ?? 0);
                            $hppVal += ($qty * ($pbMap[$pid] ?? 0.0));
                        }
                    }
                }
                $row['_hpp_calc'] = $hppVal;
            }
            unset($row);

            // Sort by calculated HPP
            usort($query, function($a, $b) use ($sort_order) {
                $diff = $a['_hpp_calc'] - $b['_hpp_calc'];
                if ($diff == 0) return $b['id'] - $a['id'];
                return $sort_order === 'ASC' ? ($diff > 0 ? 1 : -1) : ($diff < 0 ? 1 : -1);
            });

            // Paginate
            $query = array_slice($query, $offset, $limit);
        } else {
            $query = $this->mymodel->selectWithQuery("
                SELECT * FROM transaction
                WHERE $qry AND type_sub = 'POS'
                ORDER BY $sort_column $sort_order, id DESC
                LIMIT $offset, $limit
            ");
        }

        $query = $this->appendReturnConditionSummary($query);

        foreach ($query as &$row) {
            $row['pesanan_sku'] = $this->extractSkuFromPesanan($row['pesanan'] ?? '');

            // Dirangkai di sisi server supaya kolom pesanan yang berisi
            // JSON panjang tidak perlu ikut dikirim ke browser tiap baris.
            // Memakai fungsi yang sama dengan item() agar formatnya seragam.
            $row['pesanan_produk'] = $this->extractProdukFromPesanan($row['pesanan'] ?? '');
            $row['pesanan_img']    = $this->extractImgFromPesanan($row['pesanan'] ?? '');
            $row['pesanan_produk_full'] = $this->extractProdukFullFromPesanan($row['pesanan'] ?? '');
        }
        unset($row);

        $data['data'] = $query;
        $data['start'] = $offset + 1;
        $data['end']   = min($offset + $limit, $total_data);

        $data['active_filters'] = [];
        if (isset($_GET['filter_field'])) {
            foreach ((array)$_GET['filter_field'] as $index => $field) {
                if (isset($_GET['filter_value'][$index])) {
                    $data['active_filters'][$field][] = $_GET['filter_value'][$index];
                }
            }
        }

        // === GRAND TOTALS (tanpa LIMIT)
        $sumCols = [
            'pesanan_count','customer_price','dana_pencairan','omset_kotor',
            'diskon_penjual','biaya_lainnya','omset_bersih','marketplace_fee',
            'komisi_afiliasi','hpp'
        ];
        $selectParts = array_map(fn($c) => "SUM(COALESCE($c,0)) AS sum_$c", $sumCols);

        $totalsRow = $this->mymodel->selectWithQuery("
            SELECT ".implode(',', $selectParts)."
            FROM transaction
            WHERE $qry AND type_sub = 'POS'
        ");

        $totals = [];
        if (!empty($totalsRow[0])) {
            foreach ($sumCols as $c) $totals[$c] = (float)($totalsRow[0]["sum_$c"] ?? 0);
        }
        // Penjualan versi Shopee Seller Centre = Subtotal Pesanan
        // = jumlah (qty x harga setelah semua diskon produk) dari isi pesanan.
        $pjRow = $this->mymodel->selectWithQuery("
            SELECT COALESCE(SUM(j.jq * j.jp), 0) AS v
            FROM transaction,
                 JSON_TABLE(IF(JSON_VALID(transaction.pesanan), transaction.pesanan, '[]'),
                            '$[*]' COLUMNS (jq INT PATH '$.qty', jp DOUBLE PATH '$.price')) j
            WHERE $qry AND type_sub = 'POS'
        ");
        $totals['penjualan_shopee'] = (float)($pjRow[0]['v'] ?? 0);
        $data['totals'] = $totals; // <- kirim ke view

        // === JSON mode
        $accept = $this->input->get_request_header('Accept', TRUE);
        if (stripos($accept, 'application/json') !== FALSE) {
            // Prefetch price_buy untuk hitung HPP
            $allProductIds = [];
            foreach ($data['data'] as $v) {
                if (empty($v['json'])) continue;
                $obj = json_decode($v['json'], true);
                if (!is_array($obj)) continue;
                foreach ($obj as $it) {
                    if (!empty($it['product'])) {
                        $allProductIds[] = (int)$it['product'];
                    }
                }
            }
            $allProductIds = array_values(array_unique(array_filter($allProductIds)));
            $priceBuyMap = [];
            if (!empty($allProductIds)) {
                $in = implode(',', $allProductIds);
                $rowsPB = $this->mymodel->selectWithQuery("
                    SELECT id, price_buy FROM product WHERE id IN ($in)
                ");
                foreach ($rowsPB as $r) {
                    $priceBuyMap[(int)$r['id']] = (float)$r['price_buy'];
                }
            }

            $rows = [];
            $hppGrandTotal = 0;
            foreach ($data['data'] as $v) {
                $fmt = function($n){ return is_numeric($n) ? number_format($n,0,'','.') : ($n ?? ''); };
                $marketplaceRow = $this->mymodel->selectWithQuery("SELECT img FROM marketplace WHERE name = '".($v['marketplace']??'')."'");
                $marketplaceImg = !empty($marketplaceRow[0]['img']) ? (base_url().'/assets/img/marketplace/'.$marketplaceRow[0]['img']) : (base_url().'/assets/img/marketplace/default.png');
                $shippingRow = $this->mymodel->selectWithQuery("SELECT img FROM shipping WHERE name = '".($v['shipping']??'')."'");
                $shippingImg = !empty($shippingRow[0]['img']) ? (base_url().'/assets/img/shipping/'.$shippingRow[0]['img']) : (base_url().'/assets/img/shipping/default.png');
                $date_text = !empty($v['date']) ? date('Y-m-d H:i:s', strtotime($v['date'])) : null;
                $customer_price_raw = (float)($v['customer_price'] ?? 0);
                $order_status_raw = trim((string)($v['order_status'] ?? ''));
                $siap_cetak = ($order_status_raw === 'PROCESSED') ? 'Siap Cetak' : 'Belum Siap';

                // Gunakan HPP yang sudah dihitung (sort by hpp) atau hitung dari json × price_buy
                if (isset($v['_hpp_calc'])) {
                    $hppCalc = $v['_hpp_calc'];
                } else {
                    $hppCalc = 0.0;
                    if (!empty($v['json'])) {
                        $obj = json_decode($v['json'], true);
                        if (is_array($obj)) {
                            foreach ($obj as $it) {
                                $pid = isset($it['product']) ? (int)$it['product'] : 0;
                                $qty = (float)($it['qty'] ?? 0);
                                $priceBuy = $priceBuyMap[$pid] ?? 0.0;
                                $hppCalc += ($qty * $priceBuy);
                            }
                        }
                    }
                }
                $hppGrandTotal += $hppCalc;

                $rows[] = [
                    'id' => (int)$v['id'],
                    'order_id' => $v['order_id'],
                    'date_raw' => $v['date'],
                    'date_text' => $date_text,
                    'customer_id' => (int)$v['customer'],
                    'customer_text' => $v['customer_text'] ?: '-',
                    'pesanan_count' => (int)($v['pesanan_count'] ?? 0),
                    // Nama produk dan gambarnya sudah dirangkai saat
                    // pengambilan data. Tanpa dikirim di sini, kolom
                    // Produk di tabel tidak pernah terisi.
                    'pesanan_produk' => $v['pesanan_produk'] ?? '',
                    'pesanan_img' => $v['pesanan_img'] ?? '',
                    'customer_price' => $customer_price_raw,
                    'customer_price_fmt' => $fmt($customer_price_raw),
                    'pencairan_status' => $v['pencairan_status'] ?: '-',
                    'pencairan_at' => $v['pencairan_at'] ?: null,
                    'order_status' => $v['order_status'] ?: '-',
                    'siap_cetak' => $siap_cetak,
                    'reverse_status' => $v['reverse_status'] ?: '',
                    'awb_number' => $v['awb_number'] ?: '-',
                    'marketplace' => $v['marketplace'] ?: '-',
                    'shop_name' => $v['shop_name'] ?: 'Manual',
                    'is_manual' => (int)($v['is_manual'] ?? 0),
                    'brand' => $v['brand'] ?: '',
                    'rts_at' => $v['rts_at'] ?: '',
                    'phone' => $v['phone'] ?: '-',
                    'payment_type' => $v['payment_type'] ?? '-',
                    'c_username' => !empty($v['c_username']) ? $v['c_username'] : '-',
                    'cs' => !empty($v['cs']) ? $v['cs'] : '-',
                    'shipping' => !empty($v['shipping']) ? $v['shipping'] : '-',
                    'shipping_status' => '-',
                    'reverse_id' => !empty($v['reverse_id']) ? $v['reverse_id'] : '',
                    'return_status' => !empty($v['return_status']) ? $v['return_status'] : '',
                    'payment_status' => !empty($v['payment_status']) ? $v['payment_status'] : '-',
                    'pay_at' => !empty($v['pay_at']) ? date('Y-m-d H:i:s', strtotime($v['pay_at'])) : '-',
                    'dana_pencairan' => (float)($v['dana_pencairan'] ?? 0),
                    'dana_pencairan_fmt' => $fmt($v['dana_pencairan'] ?? 0),
                    'omset_kotor' => (float)($v['omset_kotor'] ?? 0),
                    'omset_kotor_fmt' => $fmt($v['omset_kotor'] ?? 0),
                    'diskon_penjual' => (float)($v['diskon_penjual'] ?? 0),
                    'diskon_penjual_fmt' => $fmt($v['diskon_penjual'] ?? 0),
                    'biaya_lainnya' => (float)($v['biaya_lainnya'] ?? 0),
                    'biaya_lainnya_fmt' => $fmt($v['biaya_lainnya'] ?? 0),
                    'omset_bersih' => (float)($v['omset_bersih'] ?? 0),
                    'omset_bersih_fmt' => $fmt($v['omset_bersih'] ?? 0),
                    'marketplace_fee' => (float)($v['marketplace_fee'] ?? 0),
                    'marketplace_fee_fmt' => $fmt($v['marketplace_fee'] ?? 0),
                    'komisi_afiliasi' => (float)($v['komisi_afiliasi'] ?? 0),
                    'komisi_afiliasi_fmt' => $fmt($v['komisi_afiliasi'] ?? 0),
                    'marketplace_img' => $marketplaceImg,
                    'shipping_img' => $shippingImg,
                    'json' => $v['json'] ?? '',
                    'hpp' => $hppCalc,
                    'hpp_fmt' => $fmt($hppCalc),
                    'c_type' => $v['c_type'],
                    'print_at' => $v['print_at'] ?? '',
                    'pesanan' => $v['pesanan'] ?? '',
                    'pesanan_sku' => $this->extractSkuFromPesanan($v['pesanan'] ?? ''),
                    'pesanan_produk' => $this->extractProdukFromPesanan($v['pesanan'] ?? ''),
                    'pesanan_img' => $this->extractImgFromPesanan($v['pesanan'] ?? ''),
                    'pesanan_produk_full' => $this->extractProdukFullFromPesanan($v['pesanan'] ?? ''),
                    'return_good_qty' => (float)($v['return_good_qty'] ?? 0),
                    'return_bad_qty' => (float)($v['return_bad_qty'] ?? 0),
                    'return_condition' => $v['return_condition'] ?? '',
                    'return_condition_items' => $v['return_condition_items'] ?? [],
                ];
            }

            $meta = [
                'total' => (int)$total_data,
                'page' => (int)$current_page,
                'per_page' => (int)$limit,
                'page_count' => (int)$data['page'],
                'sort_column' => $sort_column,
                'sort_order' => $sort_order,
                'start' => (int)$data['start'],
                'end' => (int)$data['end'],
            ];

            // Override hpp total dengan nilai yang dihitung dari price_buy (bukan dari kolom hpp di DB)
            $totals['hpp'] = $hppGrandTotal;

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['ok' => true, 'rows' => $rows, 'meta' => $meta, 'totals' => $totals]));
            return;
        }

        $data['filters'] = $filters;
        $this->load->view('transaction/item', $data);
    }

    private function appendReturnConditionSummary(array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            if (!empty($row['id'])) {
                $ids[] = (int) $row['id'];
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));
        $summaryMap = [];

        if (!empty($ids)) {
            $idList = implode(',', $ids);
            $summaryRows = $this->mymodel->selectWithQuery("
                SELECT
                    id_trx,
                    sku,
                    product_text,
                    SUM(CASE WHEN type = 'In' THEN COALESCE(qty_in_pos, 0) ELSE 0 END) AS return_good_qty,
                    SUM(CASE WHEN type = 'Out' THEN COALESCE(qty_out_retur, 0) ELSE 0 END) AS return_bad_qty
                FROM stock
                WHERE id_trx IN ($idList)
                  AND type_sub = 'POS'
                  AND order_status LIKE '%RETURN%'
                  AND (
                    (type = 'In' AND COALESCE(qty_in_pos, 0) > 0)
                    OR (type = 'Out' AND COALESCE(qty_out_retur, 0) > 0)
                  )
                GROUP BY id_trx, sku, product_text
            ");

            foreach ($summaryRows as $summary) {
                $trxId = (int) $summary['id_trx'];
                if (!isset($summaryMap[$trxId])) {
                    $summaryMap[$trxId] = [
                        'return_good_qty' => 0,
                        'return_bad_qty' => 0,
                        'items' => [],
                    ];
                }

                $goodQty = (float) ($summary['return_good_qty'] ?? 0);
                $badQty = (float) ($summary['return_bad_qty'] ?? 0);

                $summaryMap[$trxId]['return_good_qty'] += $goodQty;
                $summaryMap[$trxId]['return_bad_qty'] += $badQty;
                $summaryMap[$trxId]['items'][] = [
                    'sku' => (string) ($summary['sku'] ?? ''),
                    'product_text' => (string) ($summary['product_text'] ?? ''),
                    'return_good_qty' => $goodQty,
                    'return_bad_qty' => $badQty,
                ];
            }
        }

        foreach ($rows as &$row) {
            $summary = $summaryMap[(int) ($row['id'] ?? 0)] ?? [
                'return_good_qty' => 0,
                'return_bad_qty' => 0,
                'items' => [],
            ];

            $goodQty = (float) $summary['return_good_qty'];
            $badQty = (float) $summary['return_bad_qty'];

            $row['return_good_qty'] = $goodQty;
            $row['return_bad_qty'] = $badQty;
            $row['return_condition_items'] = $summary['items'];
            $row['return_condition'] = $badQty > 0 && $goodQty > 0
                ? 'MIX'
                : ($badQty > 0 ? 'BAD' : ($goodQty > 0 ? 'GOOD' : ''));
        }
        unset($row);

        return $rows;
    }

    /**
     * Nama produk untuk kolom Produk.
     *
     * Dihitung langsung di sini, bukan diwarisi dari index(), karena
     * tabel order mengambil datanya lewat item() yang punya penyusun
     * baris sendiri.
     */
    /** Nama produk versi utuh, dipakai untuk tooltip saat kursor menyentuh sel. */
    private function extractProdukFullFromPesanan($pesanan)
    {
        $isi = json_decode((string)$pesanan, TRUE);
        if (!is_array($isi)) return '';

        $nama = [];
        foreach ($isi as $it) {
            foreach (['model_name', 'item_name', 'model_sku'] as $k) {
                if (!empty($it[$k])) {
                    $nama[] = str_replace('amp;', '', trim((string)$it[$k]));
                    break;
                }
            }
        }
        return implode(' + ', $nama);
    }

    private function extractProdukFromPesanan($pesanan)
    {
        $isi = json_decode((string)$pesanan, TRUE);
        if (!is_array($isi)) return '';

        $nama = [];
        foreach ($isi as $it) {
            $n = '';
            foreach (['model_name', 'item_name', 'model_sku'] as $k) {
                if (!empty($it[$k])) { $n = trim((string)$it[$k]); break; }
            }
            if ($n !== '') {
                $n = str_replace('amp;', '', $n);
                // item_name dari marketplace sering berupa judul SEO yang
                // sangat panjang; dipotong supaya tinggi baris tabel wajar.
                if (mb_strlen($n) > 45) {
                    $n = rtrim(mb_substr($n, 0, 45)) . '...';
                }
                $nama[] = $n;
            }
        }
        return implode(' + ', $nama);
    }

    /** Metrik real-time ala Seller Centre: penjualan (subtotal pesanan setelah
     *  semua diskon) dan jumlah pesanan hari ini, per toko Shopee. */
    public function metrik_realtime()
    {
        header('Content-Type: application/json');
        $sql = <<<'SQL'
SELECT MAX(shop_name) AS toko, COUNT(DISTINCT order_id) AS pesanan, COALESCE(SUM(sub), 0) AS penjualan
FROM (
  SELECT t.shop_id, t.shop_name, t.order_id,
         (SELECT COALESCE(SUM(j.jq * j.jp), 0)
            FROM JSON_TABLE(IF(JSON_VALID(t.pesanan), t.pesanan, '[]'),
                 '$[*]' COLUMNS (jq INT PATH '$.qty', jp DOUBLE PATH '$.price')) j) AS sub
  FROM transaction t
  WHERE t.marketplace = 'SHOPEE' AND t.type_sub = 'POS'
    AND t.date >= CURDATE() AND t.date < CURDATE() + INTERVAL 1 DAY
) x GROUP BY shop_id ORDER BY toko
SQL;
        $rows = $this->db->query($sql)->result_array();
        $jam = $this->db->query("SELECT DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%s') AS j")->row_array();
        echo json_encode(['ok' => true, 'server' => $jam['j'], 'toko' => $rows]);
    }

    /** URL gambar produk pertama, untuk ditampilkan di kolom Produk. */
    private function extractImgFromPesanan($pesanan)
    {
        $isi = json_decode((string)$pesanan, TRUE);
        if (!is_array($isi)) return '';

        foreach ($isi as $it) {
            if (!empty($it['image_url'])) return $it['image_url'];
        }
        // Order yang masuk lewat push Shopee tidak membawa image_url, jadi gambar
        // diambil dari master produk (product_3rd) lewat id produk induknya.
        foreach ($isi as $it) {
            $idp = $it['id_product_parent'] ?? ($it['id_product'] ?? '');
            if ($idp === '') continue;
            $p = $this->db->select('image')->where('id_product', (string) $idp)
                          ->where('image !=', '')->limit(1)->get('product_3rd')->row_array();
            if (!empty($p['image'])) return $p['image'];
        }
        return '';
    }

    private function extractSkuFromPesanan($pesanan)
    {
        if (empty($pesanan)) {
            return '';
        }

        $items = json_decode($pesanan, true);
        if (!is_array($items)) {
            return '';
        }

        // Kunci 'sku' dan 'sku_parent' tidak pernah ada di data Shopee
        // maupun TikTok; yang terisi model_sku. Karena itu kolom Produk
        // selalu kosong dan penyaring SKU tidak pernah menemukan apa pun.
        // Urutan di bawah mencoba yang paling spesifik lebih dulu.
        $skus = [];
        foreach ($items as $item) {
            $sku = '';
            foreach (['model_sku', 'item_sku', 'sku', 'sku_parent', 'model_name'] as $k) {
                if (isset($item[$k]) && trim((string)$item[$k]) !== '') {
                    $sku = trim((string)$item[$k]);
                    break;
                }
            }

            // Jumlah ikut ditulis supaya "1LS x2" bisa dibedakan dari
            // "1LS x1" saat anak packing menyaring per barang.
            if ($sku !== '') {
                $qty = (int)($item['qty'] ?? 1);
                $skus[] = $qty > 1 ? ($sku . ' x' . $qty) : $sku;
            }
        }

        return implode(' + ', $skus);
    }

    function buildInCondition($values, $escape_fn) {
        $valuesArray = array_map(function($item) use ($escape_fn) {
            return "'" . $escape_fn(trim($item)) . "'";
        }, explode(',', $values));
        return implode(',', $valuesArray);
    }

    public function filter_values()
    {
        header('Content-Type: application/json');

        // ===========================
        // 1) Whitelist kolom untuk DISTINCT
        // ===========================
        $allowed = [
            'date','order_id','customer_text','pesanan_count','customer_price','pencairan_status',
            'pencairan_at','order_status','awb_number','marketplace','shop_name','is_manual',
            'brand','phone', 'c_type',
            'payment_type','c_username','cs','shipping','shipping_status','reverse_id','return_status',
            'payment_status','pay_at',
            'dana_pencairan','omset_kotor','diskon_penjual','biaya_lainnya','omset_bersih',
            'marketplace_fee','komisi_afiliasi','rts_at',
            'marketplace_img','shipping_img', 'json', 'print_at', 'return_condition', 'siap_cetak'
        ];

        $field = $_GET['field'] ?? '';
        if (!in_array($field, $allowed, true)) {
            echo json_encode(['ok' => false, 'error' => 'Field not allowed']);
            return;
        }

        if ($field === 'return_condition') {
            echo json_encode(['ok' => true, 'field' => $field, 'values' => ['Good', 'Bad']]);
            return;
        }

        if ($field === 'siap_cetak') {
            echo json_encode(['ok' => true, 'field' => $field, 'values' => ['Belum Siap', 'Siap Cetak']]);
            return;
        }

        // Ketika mengambil distinct values untuk field X, jangan terapkan filter URL untuk field X sendiri.
        // Ini agar semua pilihan yang tersedia tetap tampil meskipun salah satunya sudah aktif.
        $skip_same_field_filter = $field;

        // ===========================
        // 2) Ambil & sanitasi filter (sesuai item())
        // ===========================
        $start_date       = $_GET['start_date'] ?? date('Y-m-01');
        $until_date       = $_GET['until_date'] ?? date('Y-m-d');
        $brand            = $_GET['brand'] ?? '';
        $marketplace      = $_GET['marketplace'] ?? '';
        $cs               = $_GET['cs'] ?? '';
        $keyword          = $_GET['keyword'] ?? '';
        $keyword_category = $_GET['keyword_category'] ?? 'Order ID';
        $order_status     = $_GET['order_status'] ?? '';
        $order_type       = $_GET['order_type'] ?? '';
        $c_type           = $_GET['c_type'] ?? '';
        $shop_id          = $_GET['shop_id'] ?? '';
        $ids              = $_GET['ids'] ?? '';
        $ekspedisi        = $_GET['ekspedisi'] ?? '';
        $pencairan        = $_GET['pencairan'] ?? '';
        $id_customer      = $_GET['id_customer'] ?? '';
        $print_at         = $_GET['print_at'] ?? '';
        $printed_status   = $_GET['printed_status'] ?? '';

        // ===========================
        // 3) Build WHERE
        // ===========================
        $qry = "";
        if ($id_customer) {
            $qry = " customer = '".$this->db->escape_str($id_customer)."' ";
        } else {
            $qry = " DATE(date) >= '".$this->db->escape_str($start_date)."' AND DATE(date) <= '".$this->db->escape_str($until_date)."' ";
            if ($brand == "LAINNYA") {
                $brands   = $this->mymodel->selectWithQuery("SELECT code as opt FROM brand WHERE status = 'ENABLE'");
                $idsBrand = "";
                foreach ($brands as $b) { $idsBrand .= "'".$this->db->escape_str($b['opt'])."',"; }
                $idsBrand = rtrim($idsBrand, ',');
                if ($idsBrand) $qry .= " AND brand NOT IN ($idsBrand) ";
            } elseif ($brand) {
                $qry .= " AND brand = '".$this->db->escape_str($brand)."' ";
            }
        }

        if ($ids) { 
            $qry .= " AND id IN ($ids) "; 
        }
        if ($ekspedisi) { 
            $ekspedisiList = $this->buildInCondition($ekspedisi, [$this->db, 'escape_str']);
            $qry .= " AND shipping IN ($ekspedisiList) "; 
        }
        if ($print_at) { 
            if ($print_at) {
                $printAtList = $this->buildInCondition($print_at, [$this->db, 'escape_str']);
                $qry .= " AND print_at != '' AND print_at IN ($printAtList) ";
            } else if ($print_at == '-') {
                $qry .= " AND print_at IS NULL OR print_at = '' ";
            }
        }
        if ($marketplace && $skip_same_field_filter !== 'marketplace') {
            $marketplaceList = $this->buildInCondition($marketplace, [$this->db, 'escape_str']);
            $qry .= " AND marketplace IN ($marketplaceList) ";
        }
        if ($cs && $skip_same_field_filter !== 'cs') {
            $csList = $this->buildInCondition($cs, [$this->db, 'escape_str']);
            $qry .= " AND cs IN ($csList) ";
        }

        if ($order_status && $skip_same_field_filter !== 'order_status') {
            if ($order_status == "WEBHOOK") {
                $qry .= " AND is_webhook = 0 AND is_manual = 0";
            } else if ($order_status == "ACTIVE") {
                $qry .= " AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
            } else if ($order_status == "READY_TO_SHIP") {
                $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
            } else if ($order_status == "UNPAID") {
                $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') AND customer_price > 0";
            } else if ($order_status == "SETTLEMENT") {
                $qry .= " AND pencairan_status = 'Settlement' ";
            } else if ($order_status == "CANCELLED") {
                $qry .= " AND order_status IN ('CANCELLED','IN_CANCEL') ";
            } else if ($order_status == "PROCESSED") {
                $qry .= " AND order_status = 'PROCESSED' ";
            } else if ($order_status == "PRINTED") {
                $qry .= " AND print_at != '' ";
            } else {
                $qry .= " AND order_status = '".$this->db->escape_str($order_status)."' ";
            }
        }

        if ($printed_status && $skip_same_field_filter !== 'print_at') {
            if ($printed_status == "Sudah Dicetak") {
                $qry .= " AND print_at != '' ";
            } else if ($printed_status == "Belum Dicetak") {
                $qry .= " AND print_at = '' ";
            }
        }

        if ($pencairan) {
            if ($pencairan == "Sudah Pencairan") {
                $qry .= " AND dana_pencairan > 0";
            } else if ($pencairan == "Belum Pencairan") {
                $qry .= " AND order_status IN ('PROCESSED','SHIPPED','COMPLETED', 'READY_TO_SHIP', 'DELIVERED') AND c_type NOT IN ('Affiliate','Endorse','Free') AND dana_pencairan = 0 AND is_disbursement = 0 ";
            }
        }

        if ($order_type && $skip_same_field_filter !== 'is_manual') {
            if ($order_type == "Manual") {
                $qry .= " AND is_manual = 1 ";
            } else if ($order_type == "Marketplace") {
                $qry .= " AND is_manual = 0 ";
            } else if ($order_type == "Belum Dikonfigurasi") {
                $qry .= " AND is_configurated = 0 ";
            }
        }

        if ($c_type && $skip_same_field_filter !== 'c_type') {
            $c_typeList = $this->buildInCondition($c_type, [$this->db, 'escape_str']);
            $qry .= " AND c_type IN ($c_typeList) ";
        }
        if ($shop_id && $skip_same_field_filter !== 'shop_id') {
            $shop_idList = $this->buildInCondition($shop_id, [$this->db, 'escape_str']);
            $qry .= " AND shop_id IN ($shop_idList) ";
        }

        if ($keyword) {
            $kw = $this->db->escape_str($keyword);
            switch ($keyword_category) {
                case "Order ID":        $qry .= " AND order_id LIKE '%$kw%' "; break;
                case "Username":        $qry .= " AND c_username LIKE '%$kw%' "; break;
                case "Nama Pelanggan":  $qry .= " AND customer_text LIKE '%$kw%' "; break;
                case "Nomor Pelanggan": $qry .= " AND phone LIKE '%$kw%' "; break;
                case "Nomor Resi":      $qry .= " AND awb_number LIKE '%$kw%' "; break;
                case "Nama Produk":     $qry .= " AND `json` LIKE '%$kw%' "; break;
            }
        }

        // ===========================
        // 4) DISTINCT values
        // ===========================
        // Catatan:
        // - Untuk field tanggal/teks/angka semua aman di DISTINCT.
        // - Untuk kolom numeric, DISTINCT akan mengembalikan angka unik mentah (JS akan casting ke string).
        // - type_sub = 'POS' sesuai filter kamu.
        $sql  = "SELECT DISTINCT $field AS val FROM transaction WHERE $qry AND type_sub = 'POS' ORDER BY val ASC";
        $rows = $this->mymodel->selectWithQuery($sql);

        $values = [];
        foreach ($rows as $r) {
            $values[] = (is_null($r['val']) || $r['val']==='') ? '-' : (string)$r['val'];
        }

        echo json_encode(['ok' => true, 'field' => $field, 'values' => $values]);
    }



    public function edit()
    {

        $data['title'] = 'Edit Order - ' . $this->template->title();

        $id = $_GET['id'];
        $data['data'] = $this->mymodel->selectWithQuery("SELECT *
        FROM transaction 
        WHERE id = '$id' AND is_manual = 1 AND type_sub = 'POS'");
        $data['data'] = $data['data'][0];

        if (empty($data['data'])) {
            redirect(base_url() . 'transaction');
        }

        $data['brand'] = $this->mymodel->selectWithQuery("SELECT *
        FROM brand
        ORDER BY code ASC");

        $data['marketplace'] = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace
        ORDER BY name ASC");

        $data['shipping'] = $this->mymodel->selectWithQuery("SELECT *
        FROM shipping
        ORDER BY name ASC");

        $data['product'] = $this->mymodel->selectWithQuery("SELECT *
        FROM product WHERE is_varian = 0
        ORDER BY name ASC");

        $data['cs'] = $this->mymodel->selectWithQuery("SELECT *
        FROM user
        WHERE role IN ('3')
        ORDER BY code ASC");

        $data['content'] = $this->load->view("transaction/edit", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }


    public function get_discount()
    {
        $dt = $_GET;
        $code = $dt['code'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM discount WHERE code = '$code'");

        $query = $query[0];

        $text = 0;
        $conf = array();
        $conf = $query;

        if ($dt['total'] >= $conf['min_nominal']) {
            if ($conf['type'] == "Persentase") {
                if (doubleval($conf['nominal']) > 0 && doubleval($dt['total']) > 0) {
                    $text = (doubleval($dt['total']) * doubleval($conf['nominal']) / 100);
                } else {
                    $text = 0;
                }
            } else if ($conf['type'] == "Nominal") {
                $text = doubleval($conf['nominal']);
            }
        }
        $html['type'] = $query['type'];
        $html['min_nominal'] = $query['min_nominal'];
        $html['nominal'] = $query['nominal'];
        $html['html'] = $text;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($html, true);
    }

    public function sync_trx()
    {
        $param = $_GET;
        $this->template->sync_trx($param);
    }
    public function get_marketplace_fee()
    {
        $dt = $_POST;
        $id_marketplace = $dt['id_marketplace'];
        $id_trx = $dt['id_trx'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace WHERE name = '$id_marketplace'");

        $query = $query[0];
        $json = json_decode($query['configuration'], true);
        $text = 0;
        $conf = array();


        if ($json) {
            usort($json, function ($a, $b) {
                return $b['date'] <=> $a['date'];
            });
            foreach ($json as $k => $v) {
                if ($v['date'] <= $dt['date']) {
                    $conf = $v;
                    break;
                }
            }
            if ($conf['type'] == "Persentase") {
                if (doubleval($conf['fee']) > 0 && doubleval($dt['total_2']) > 0) {
                    $text = (doubleval($dt['total_2']) * doubleval($conf['fee']) / 100);
                } else {
                    $text = 0;
                }
            } else if ($conf['type'] == "Nominal") {
                $text = doubleval($conf['fee']);
            }
        }

        $dt = array();
        $dt['marketplace_fee'] = $text;
        if ($query) {
            $dt['jenis_potongan'] = "Admin " . $query['name'];
        }

        $this->db->update('transaction', $dt, array('id' => $id_trx));

        $html['html'] = $text;
        $html['jenis_potongan'] = strval($dt['jenis_potongan']);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($html, true);
    }


    public function set_product()
    {
        $dt = $_POST;
        $id = $dt['id'];
        $id_product = $dt['product'];
        $customer = $dt['customer'];

        if ($id) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id = '$id' ");

            $query = $query[0];
            $json = json_decode($query['json'], true);
        } else {
            $trx = $_SESSION['trx'];
            $json = json_decode($trx['json'], true);
        }


        $text = "";
        $total = 0;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$id_product'");

        $product = $query[0];

        if (empty($product)) {
            die;
        }

        if ($json[$id_product]) {
            $json[$id_product]['product'] = $dt['product'];
            $json[$id_product]['sku'] = $product['sku'];
            $json[$id_product]['product_text'] = $product['name'];
            $json[$id_product]['brand'] = $product['brand'];
            $json[$id_product]['brand_text'] = $product['brand_text'];
            $json[$id_product]['qty'] = $json[$id_product]['qty'] + 1;
            if ($customer == "Pelanggan") {
                $json[$id_product]['price'] = $product['price_normal'];
                $json[$id_product]['price_total'] = ($json[$id_product]['qty']) * $product['price_normal'];
            } else if ($customer == "Reseller") {
                $json[$id_product]['price'] = $product['price_reseller'];
                $json[$id_product]['price_total'] = ($json[$id_product]['qty']) * $product['price_reseller'];
            } else if ($customer == "Distributor") {
                $json[$id_product]['price'] = $product['price_distributor'];
                $json[$id_product]['price_total'] = ($json[$id_product]['qty']) * $product['price_distributor'];
            } else if ($customer == "Free" || $customer == "Affiliate") {
                $json[$id_product]['price'] = 0;
                $json[$id_product]['price_total'] = 0;
            }
        } else {
            $json[$id_product]['product'] = $dt['product'];
            $json[$id_product]['sku'] = $product['sku'];
            $json[$id_product]['product_text'] = $product['name'];
            $json[$id_product]['brand'] = $product['brand'];
            $json[$id_product]['brand_text'] = $product['brand_text'];
            $json[$id_product]['qty'] = 1;
            if ($customer == "Pelanggan") {
                $json[$id_product]['price'] = $product['price_normal'];
                $json[$id_product]['price_total'] = 1 * $product['price_normal'];
            } else if ($customer == "Reseller") {
                $json[$id_product]['price'] = $product['price_reseller'];
                $json[$id_product]['price_total'] = 1 * $product['price_reseller'];
            } else if ($customer == "Distributor") {
                $json[$id_product]['price'] = $product['price_distributor'];
                $json[$id_product]['price_total'] = 1 * $product['price_distributor'];
            } else if ($customer == "Free" || $customer == "Affiliate") {
                $json[$id_product]['price'] = 0;
                $json[$id_product]['price_total'] = 0;
            }
        }

        $price_total = 0;
        foreach ($json as $k => $v) {
            $price_total += $v['price_total'];
        }

        if ($id) {
            $dt = array();
            $dt['price_total'] = $price_total;
            $dt['json'] = json_encode($json, true);
            $this->db->update('transaction', $dt, array('id' => $id));
        } else {
            $dt = array();
            $dt['price_total'] = $price_total;
            $dt['json'] = json_encode($json, true);
            $_SESSION['trx'] = $dt;
        }
    }

    public function formatRupiah($angka)
    {
        return number_format($angka, 0, ',', '.');
    }
    public function get_cart()
    {
        $id = $_GET['id'];

        if ($id) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id = '$id' ");
            $query = $query[0];
            $json = json_decode($query['json'], true);
        } else {
            $trx = $_SESSION['trx'];
            $json = json_decode($trx['json'], true);
        }

        $text = "";
        $total = 0;
        $k = 0;
        foreach ($json as $k2 => $v) {
            $total += $v['price_total'];
            $remove = "'" . $v['product'] . "'";
            $text .= '
                <tr>
                    <td class="text-start pt-3" style="min-width:240px!important; white-space: normal;text-overflow: ellipsis;">
                        ' . $v['sku'] . ' | ' . $v['product_text'] . '
                    </td>
                    <td class="text-end pt-3">
                        ' . $this->formatRupiah($v['price']) . '
                        <input type="hidden" class="form-control text-end m-0 i-price" value="' . $v['price'] . '" id="i-price-' . $k . '">
                         <!-- Tampilkan diskon jika ada -->
                        <p class="text-danger" id="txt-disc-' . $k . '">
                            ' . (isset($v['discount']) && $v['discount'] > 0 ? 'Diskon: ' . $v['discount'] . (isset($v['discount_type']) && $v['discount_type'] === 'Persentase' ? '%' : '') : '') . '
                        </p>
                    </td>
                    <td class="text-end" style="width:80px!important;padding-top:6px!important">
                        <input autocomplete="off" type="text" data-id="' . $k . '" 
                            class="form-control text-end m-0 i-qty" 
                            value="' . $v['qty'] . '" 
                            id="i-qty-' . $k . '" 
                            style="height:35px;width:80px;box-sizing:border-box;display:inline-block;">
                    </td>
                    <td class="text-end pt-3">
                        <span id="txt-price-total-' . $k . '">' . $this->formatRupiah($v['price_total']) . '</span>
                        <input type="hidden" class="form-control text-end m-0 i-price-total" value="' . $v['price_total'] . '" id="i-price-total-' . $k . '">
                    </td>
                    <td class="text-end pt-3">
                        <div class="dropdown">
                            <button style="height: 20px; display: flex; align-items: center; justify-content: center;" 
                                    class="btn bg-transparent border-0 p-0" 
                                    type="button" 
                                    id="dropdownMenuButton' . $k . '" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false">
                                <i class="bi bi-three-dots text-primary"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton' . $k . '">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="showDiscountInput(' . $k . ')">Diskon</a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-red" href="#" onclick="delete_cart_' . $k . '(' . $remove . ')">Hapus</a>
                                </li>
                            </ul>
                        </div>
                        <div id="discount-input-' . $k . '" class="floating-discount text-start" style="display: none; width: 250px;">
                            <div class="discount-header">
                                <label for="discount-value-' . $k . '" style="font-size: 16px;">Disc. Produk</label>
                                <!-- Tombol close -->
                                <span class="close-btn" onclick="hideDiscountInput(' . $k . ')">&times;</span>
                            </div>
                            <div class="discount-content">
                                <label for="">Tipe Diskon</label>
                                <select type="text" class="form-control" id="discount-type-' . $k . '" onchange="updateDiscountedPrice(' . $k . ')">
                                    <option value="Nominal" " . (isset($v["discount_type"]) && $v["discount_type"] === "Nominal" ? "selected" : "") . ">Nominal</option>
                                    <option value="Persentase" " . (isset($v["discount_type"]) && $v["discount_type"] === "Persentase" ? "selected" : "") . ">Persentase</option>
                                </select>
                                <input type="text" class="form-control i-discount" placeholder="Masukkan diskon" 
                                    id="discount-value-' . $k . '" onkeyup="updateDiscountedPrice(' . $k . ')" 
                                    value="' . (isset($v['discount']) ? number_format($v['discount'], 0, ',', '.') : '') . '">
                                <label for="discounted-price-' . $k . '">Harga Setelah Diskon</label>
                                <input type="text" class="form-control text-start mt-2" id="discounted-price-' . $k . '" readonly>
                                <a href="#" class="btn btn-primary btn-sm mt-2" onclick="submitDiscount(' . $k . ')">Terapkan</a>
                            </div>
                        </div>
                    </td>
                </tr>

                <style>
                    .floating-discount {
                        position: absolute;
                        background: white;
                        padding: 15px;
                        border-radius: 10px;
                        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.15);
                        z-index: 100;
                        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
                        width: 250px;
                        max-width: 100%;
                        right: 0; /* Pastikan tetap dalam batas */
                        overflow: hidden; /* Hindari konten meluber */
                        white-space: nowrap; /* Pastikan teks tidak menyebabkan overflow */
                        margin-right: 10px;
                    }


                    .floating-discount {
                        display: flex;
                        align-items: center; /* Sejajarkan vertikal */
                        gap: 8px; /* Beri jarak antara input dan tombol */
                    }
                    .floating-discount input {
                        flex: 1; /* Biar input menyesuaikan lebar */
                        height: 35px; /* Samakan tinggi input */
                    }
                    .floating-discount a {
                        height: 35px; /* Samakan tinggi tombol */
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        white-space: nowrap; /* Agar teks tombol tidak terpotong */
                    }
                    .close-btn {
                        position: absolute;
                        top: 10px;
                        right: 10px;
                        font-size: 20px;
                        cursor: pointer;
                        color: #000;
                    }

                    .close-btn:hover {
                        color: #ff0000;
                    }

                </style>


                <script>
                function hideDiscountInput(rowId) {
                    var discountDiv = document.getElementById("discount-input-" + rowId);
                    discountDiv.classList.add("hide");
                    discountDiv.classList.remove("show");
                    setTimeout(() => {
                        discountDiv.style.display = "none";
                    }, 300);
                }
                function showDiscountInput(rowId) {
                    var discountDiv = document.getElementById("discount-input-" + rowId);
                    if (discountDiv.style.display === "none" || discountDiv.style.display === "") {
                        discountDiv.style.display = "block";
                        setTimeout(() => {
                            discountDiv.classList.add("show");
                            discountDiv.classList.remove("hide");
                        }, 10);
                    } else {
                        discountDiv.classList.add("hide");
                        discountDiv.classList.remove("show");
                        setTimeout(() => {
                            discountDiv.style.display = "none";
                        }, 300);
                    }
                }

                function updateDiscountedPrice(rowId) {
                    var discountInput = document.getElementById("discount-value-" + rowId);
                    var priceElement = document.getElementById("i-price-" + rowId);
                    var discountType = document.getElementById("discount-type-" + rowId).value;
                    
                    // Ambil nilai input dan hapus format ribuan untuk perhitungan
                    var discount = parseFloat(hapusFormatRibuan(discountInput.value)) || 0;
                    var originalPrice = parseFloat(hapusFormatRibuan(priceElement.value));

                    var discountedPrice;
                    
                    if (discountType === "Persentase") {
                        discountedPrice = originalPrice - (originalPrice * (discount / 100));
                    } else {
                        discountedPrice = originalPrice - discount;
                    }

                    // Pastikan harga setelah diskon tidak negatif
                    discountedPrice = discountedPrice < 0 ? 0 : discountedPrice;

                    // Update nilai input harga setelah diskon
                    document.getElementById("discounted-price-" + rowId).value = formatRibuan(discountedPrice);

                    // Format ulang input diskon saat diketik
                    discountInput.value = formatRibuan(discount);
                }

                document.addEventListener("keyup", function (event) {
                    if (event.target.classList.contains("i-discount")) {
                        event.target.value = formatRibuan(hapusFormatRibuan(event.target.value));
                        updateDiscountedPrice(event.target.id.split("-").pop()); // Ambil rowId dari ID input
                    }
                });


                // Fungsi untuk mengubah angka menjadi format ribuan
                function formatRibuan(angka) {
                    return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }

                // Fungsi untuk menghapus format ribuan dan mengembalikan nilai asli
                function hapusFormatRibuan(angka) {
                    return angka.replace(/\./g, "");
                }


                function submitDiscount(rowId) {
                    var discountInput = document.getElementById("discount-value-" + rowId);
                    var discountFormatted = discountInput.value;
                    var discount = parseFloat(discountFormatted.replace(/\./g, "")) || 0; // Hapus titik sebelum parsing
                    var priceElement = document.getElementById("i-price-" + rowId);
                    var totalElement = document.getElementById("txt-price-total-" + rowId);
                    var discountText = document.getElementById("txt-disc-" + rowId);
                    var originalPrice = parseFloat(priceElement.value);
                    var qty = parseFloat($("#i-qty-" + rowId).val()) || 0; // Ambil quantity
                    var discountType = document.getElementById("discount-type-" + rowId).value; // Ambil tipe diskon

                    var discountedPrice;
                    
                    if (discountType === "Persentase") {
                        discountedPrice = originalPrice - (originalPrice * (discount / 100));
                    } else {
                        discountedPrice = originalPrice - discount;
                    }

                    var newTotal = discountedPrice * qty; // Hitung total dengan diskon dan quantity

                    // Update tampilan diskon dan total harga
                    discountText.innerText = "Diskon: " + discountFormatted + (discountType === "Persentase" ? "%" : "");
                    totalElement.innerText = newTotal.toFixed(0);

                    $("#i-price-total-" + rowId).val(newTotal.toFixed(0));
                    $("#txt-price-total-" + rowId).html(newTotal.toFixed(0));

                    $.ajax({
                        url: "' . base_url() . '/transaction/edit-cart",
                        type: "POST",
                        data: {
                            id: "' . $id . '",
                            id_product: ' . $v['product'] . ',
                            price_total: discountedPrice * qty,
                            qty: qty,
                            discount: discount, // Kirim angka tanpa titik
                            discount_type: discountType,
                            price: originalPrice,
                            customer: customer
                        },
                        success: function(response) {
                            var discountDiv = document.getElementById("discount-input-" + rowId);

                            var total = 0;
                            for (var i = 0; i < ' . count($json) . '; i++) {
                                total += parseFloat($("#i-price-total-" + i).val());
                            }
                            $("#total").html(total);
                            $("#total_1_text").text(total);

                            discountDiv.classList.add("hide");
                            discountDiv.classList.remove("show");
                            setTimeout(() => {
                                discountDiv.style.display = "none";
                            }, 300);
                        },
                        error: function(xhr, status, error) {
                            console.error("Gagal mengupdate harga: ", error);
                        }
                    });

                    var discountDiv = document.getElementById("discount-input-" + rowId);
                    discountDiv.classList.add("hide");
                    discountDiv.classList.remove("show");
                    setTimeout(() => {
                        discountDiv.style.display = "none";
                    }, 300);
                }


                function delete_cart_' . $k . '(id_product) {
                    $.ajax({
                        url: "' . base_url() . '/transaction/delete-cart",
                        type: "POST",
                        data: {
                            id: "' . $id . '",
                            id_product: id_product,
                        },
                        success: function(response) {
                            $.ajax({
                                dataType: "json",
                                url: "' . base_url() . '/transaction/get-cart?id=' . $id . '",
                                success: function(html) {
                                    $("#tbody").html(html.html);
                                    $("#total").html(html.total);
                                    
                                    if (html.total == 0) {
                                        $("#total_1_text").text(0);
                                    } else {
                                        $("#total_1_text").text(html.total);
                                    }

                                }
                            });
                        }
                    });
                }

                $("#i-qty-' . $k . '").keyup(function() {
                    var id = $(this).attr("data-id");
                    var qty = parseFloat($("#i-qty-" + id).val());
                    var price = parseFloat($("#i-price-" + id).val());
                    var discount = parseFloat($("#discount-value-" + id).val()) || 0; // Ambil nilai diskon

                    if (isNaN(qty)) {
                        qty = 0;
                    }
                    if (isNaN(price)) {
                        price = 0;
                    }

                    // Hitung total harga dengan memperhitungkan diskon
                    var discountedPrice = price - discount;
                    var val = qty * discountedPrice;

                    // Update tampilan total harga
                    $("#i-price-total-" + id).val(val);
                    $("#txt-price-total-" + id).html(val);

                    get_grand();

                    $.ajax({
                        url: "' . base_url() . '/transaction/edit-cart",
                        type: "POST",
                        data: {
                            id: "' . $id . '",
                            id_product: ' . $v['product'] . ',
                            qty: qty,
                            discount: discount,
                            price: price,
                            price_total: discountedPrice * qty
                        },
                        success: function(response) {
                            var total = 0;
                            for (var i = 0; i < ' . count($json) . '; i++) {
                                total += parseFloat($("#i-price-total-" + i).val());
                            }
                            $("#total").html(total);
                            $("#total_1_text").text(total);
                        }
                    });
                });

            
                function get_grand(){
                    // Implement grand total calculation logic here
                }
                </script>
                ';

            $k += 1;
        }

        $html['html'] = $text;
        $html['total'] = $total;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($html, true);
    }

    function edit_cart()
    {
        $dt = $_POST;
        $id = $dt['id'];
        $id_product = $dt['id_product'];
        $qty = (int)$dt['qty'];
        $discount = (int)$dt['discount'];
        $price = (int)$dt['price'];
        $price_total = (int)$dt['price_total'];
        $discount_type = $dt['discount_type'];

        if ($id) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id = '$id'");
            $query = $query[0];

            $json = json_decode($query['json'], true);

            if (isset($json[$id_product])) {
                $json[$id_product]['qty'] = $qty;
                $json[$id_product]['price'] = $price;
                $json[$id_product]['price_total'] = $price_total;
                $json[$id_product]['discount'] = $discount;
                $json[$id_product]['discount_type'] = $discount_type;
            }

            $data_to_update = array('json' => json_encode($json, true));
            $this->db->update('transaction', $data_to_update, array('id' => $id));
        } else {
            $trx = $_SESSION['trx'];
            $json = json_decode($trx['json'], true);

            if (isset($json[$id_product])) {
                $json[$id_product]['qty'] = $qty;
                $json[$id_product]['price'] = $price;
                $json[$id_product]['price_total'] = $price_total;
                $json[$id_product]['discount'] = $discount;
                $json[$id_product]['discount_type'] = $discount_type;
            }

            $_SESSION['trx'] = array('json' => json_encode($json, true));
        }

        echo json_encode(['status' => 'success', 'updated_data' => $json[$id_product]]);
    }
    function delete_cart()
    {
        $dt = $_POST;
        $id = $dt['id'];
        $id_product = $dt['id_product'];


        $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id = '$id' ");

        $query = $query[0];

        $json = json_decode($query['json'], true);
        unset($json[$id_product]);
        $dt = array();
        $dt['json'] = json_encode($json, true);
        $this->db->update('transaction', $dt, array('id' => $id));
    }
    public function print()
    {
        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id = '$id' AND type_sub = 'POS'");
        $data['data'] = $query[0];

        if (empty($data['data'])) {
            redirect(base_url() . 'transaction');
        }

        $data['template'] = $this->template;

        $data['title'] = 'Print ' . $data['data']['order_id'] . ' - ' . $this->template->title();

        $this->load->view('transaction/print', $data);
    }

    public function print_v2()
    {
        $user = $_SESSION['user'];

        $id_selected = $_POST['id_selected'];
        if ($id_selected) {
            $id = explode(',', $id_selected);
        }

        foreach ($id as $k => $v) {
            $list_id .= "'" . $v . "',";
        }
        $list_id = substr($list_id, 0, -1);



        if ($list_id) {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id IN ($list_id) AND type_sub = 'POS'
            ORDER BY date DESC, id DESC  ");
            $data['datas'] = $query;

            $dt = array();
            $dt['order_status'] = "PROCESSED";
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = strval($user['id']);

            $this->db->update('transaction', $dt, "id IN ($list_id) AND is_manual = '1' AND order_status IN ('UNPAID')");
        } else {
            return redirect(previous_url());
        }


        $data['template'] = $this->template;


        $data['title'] = 'Print Order - ' . $this->template->title();

        $this->load->view('transaction/print_v2', $data);
    }

    public function update()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dtt = $_POST['dtt'];
        $check = $_POST['check'];

        // unset($dt['birth_date']);

        if ($dt['date']) {
            $dt['date'] = DATE("Y-m-d H:i:s", strtotime($dt['date']));
        }
        if ($dt['rts_at']) {
            $dt['rts_at'] = DATE("Y-m-d H:i:s", strtotime($dt['rts_at']));
        }

        if ($dt['customer_text'] == "" && $dt['customer'] < 1) {
            $msg = 'Pelanggan wajib diisi!';
            echo $this->template->alert_danger($msg);
            die;
        }

        // if ($dt['pay_at']) {
        //     $dt['payment_status'] = 'Paid';
        // } else {
        //     $dt['payment_status'] = 'Unpaid';
        // }

        if ($dt['omset_kotor'] == 0) {
            $dt['payment_status'] = "Paid";
        }
        $dt['price_total'] = $dt['omset_kotor'];
        $dt['biaya_lainnya'] = $dt['packing_price'] + $dt['other_price'];
        $dt['dana_pencairan'] = $dt['omset_kotor'] - $dt['diskon_penjual'];
        $dt['omset_bersih'] = $dt['omset_kotor'] - $dt['diskon_penjual'];

        if ($dt['order_status'] == "COMPLETED2") {
            $dt['order_status'] = "COMPLETED";
            $dt['is_disbursement'] = 1;
        } else {
            $dt['is_disbursement'] = 0;
        }

        if ($dt['is_endorse'] == "1" || $dt['c_type'] == "Affiliate" || $dt['c_type'] == "Endorse" || $dt['c_type'] == "Free") {
            $dt['is_endorse'] = "1";
            $dt['dana_pencairan'] = 0 - $dt['ongkir'];
            $dt['pencairan_at'] = '';
            $dt['pencairan_status'] = '';
            $dt['payment_type'] = '';
            $dt['payment_status'] = '';
        } else {
            $dt['is_endorse'] = "0";
        }

        if ($dt['payment_status'] == "Paid" && $dt['c_type'] == "Pelanggan" || $dt['c_type'] == "Reseller" || $dt['c_type'] == "Distributor") {
            $dt['pencairan_status'] = "Settlement";
            $dt['pencairan_at'] = DATE("Y-m-d H:i:s");
            $dt['dana_pencairan'] = $dt['customer_price'];
        }

        if ($check  == '1') {
            $dtc = $customer;
            $dt['c_type'] = "Pelanggan";
            $dtc['akun_type'] = "Pelanggan";
            $dtc['brand'] = $dt['brand'];
            $dtc['marketplace'] = $dt['marketplace'];
            $dtc['status'] = "Aktif";
            $dtc['created_at'] = DATE("Y-m-d H:i:s");
            $dtc['updated_at'] = DATE("Y-m-d H:i:s");
            $dtc['created_by'] = $user['id'];
            $dtc['full_name'] = $dt['customer_text'];
            $dtc['phone'] = $dt['phone'];
            $dtc['username'] = $dt['c_username'];
            $dtc['count_order'] = 1;
            $dtc['return_trx'] = $dt['return'];
            $dtc['customer_price'] = $dt['customer_price'];
            $dtc['price_total'] = $dt['price_total'];
            $dtc['komisi_afiliasi'] = intval($dt['komisi_afiliasi']);
            $dtc['omset_kotor'] = $dt['omset_kotor'];
            $dtc['diskon_penjual'] = $dt['diskon_penjual'];
            $dtc['omset_bersih'] = $dt['omset_bersih'];
            $dtc['dana_pencairan'] = $dt['dana_pencairan'];
            $dtc['marketplace_fee'] = intval($dt['marketplace_fee']);
            $dtc['first_order'] = strval($dt['date']);
            $dtc['last_order'] = strval($dt['date']);
            $dtc['birth_date'] = $dt['birth_date'];
            $dtc['address'] = $dt['address'];
            $dtc['province_text'] = $dt['province_text'];
            $dtc['city_text'] = $dt['city_text'];
            $dtc['subdistrict_text'] = $dt['subdistrict_text'];
            // print_r($dtc);die;
            $this->db->insert('customer', $dtc);
            $dt['customer'] = $this->db->insert_id();
        }

        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];

        $id_customer = $dt['customer'];

        $this->db->update('transaction', $dt, "id = '$id' AND is_manual = 1");

        $this->customer_summary($id_customer);
        if ($_POST['existing_customer'] > 0 && $_POST['existing_customer'] != $dt['customer']) {
            $this->customer_summary($_POST['existing_customer']);
        }


        $detail = $this->mymodel->selectWithQuery("SELECT json FROM transaction WHERE id = '$id'");
        $detail = $detail[0];
        if (empty($detail['json'])) {
            $msg = 'Produk wajib dipilih!';
            echo $this->template->alert_danger($msg);
            die;
        }
        $js = array();
        $i = 0;
        foreach (json_decode($detail['json'], true) as $k4 => $v4) {
            // print_r($v4);die;
            $js[$i]['qty'] += $v4['qty'];
            $js[$i]['item_sku'] = $v4['sku'];
            $js[$i]['item_name'] = $v4['product_text'];
            // $js[$i]['hpp'] = $v4['hpp'];
            $js[$i]['price_total'] = $v4['price_total'];
            $i++;
        }

        $dt['pesanan'] = json_encode($js, true);
        $dt['pesanan_count'] = intval(count($js));
        $dt['json'] = $detail['json'];

        if ($this->db->update('transaction', $dt, "id = '$id' AND is_manual = 1 ")) {
            $msg = 'Update data berhasil!';
            $this->generate_stock($id, $dt);
            // $this->customer_summary($dt['customer']);
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }
    
    function generate_product_stock()
    {
        $data['product'] = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif' AND is_varian = 0
        ORDER BY sku ASC
        ");
        foreach ($data['product'] as $k => $v) {
            $id_product = $v['id'];
            $this->update_stock($id_product);
        }
    }
    function customer_summary($id_customer)
    {



        $dtt = array();
        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count, 
        SUM(omset_kotor) as omset_kotor,
        SUM(komisi_afiliasi) as komisi_afiliasi,
        SUM(diskon_penjual) as diskon_penjual,
        SUM(omset_bersih) as omset_bersih,
        SUM(dana_pencairan) as dana_pencairan,
        SUM(price_total) as price_total,
        SUM(marketplace_fee) as marketplace_fee,
        SUM(customer_price) as customer_price,
        SUM(transaction.return) as returnn FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS'");

        $dtt['count_order'] = strval($query[0]['count']);
        $dtt['return_trx'] = strval($query[0]['returnn']);
        $dtt['omset_kotor'] = strval($query[0]['omset_kotor']);
        $dtt['komisi_afiliasi'] = strval($query[0]['komisi_afiliasi']);
        $dtt['diskon_penjual'] = strval($query[0]['diskon_penjual']);
        $dtt['omset_bersih'] = strval($query[0]['omset_bersih']);
        $dtt['dana_pencairan'] = strval($query[0]['dana_pencairan']);
        $dtt['price_total'] = strval($query[0]['price_total']);
        $dtt['marketplace_fee'] = strval($query[0]['marketplace_fee']);
        $dtt['customer_price'] = strval($query[0]['customer_price']);

        $query = $this->mymodel->selectWithQuery("SELECT id,date FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS' AND order_status NOT IN ('UNPAID','CANCELLED','IN_CANCEL','RETURN','REFUND')  ORDER BY date ASC LIMIT 1");

        $dtt['first_order'] = strval($query[0]['date']);

        $dt = array();
        $dt['cb_cl'] = 'CL';
        $id = $query[0]['id'];
        $this->db->update('transaction', $dt, array('customer' => $id_customer));
        $dt = array();
        $dt['cb_cl'] = 'CB';
        $id = $query[0]['id'];
        $this->db->update('transaction', $dt, array('id' => $id));


        $query = $this->mymodel->selectWithQuery("SELECT date FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS' AND order_status NOT IN ('UNPAID','CANCELLED','IN_CANCEL','RETURN','REFUND')  ORDER BY date DESC LIMIT 1");

        $dtt['last_order'] = strval($query[0]['date']);


        $json = array();
        $query = $this->mymodel->selectWithQuery("SELECT id,date,json,pesanan,is_manual,order_id FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS' AND order_status NOT IN ('UNPAID','CANCELLED','IN_CANCEL','RETURN','REFUND') ORDER BY date ASC");

        foreach ($query as $kk => $vv) {
            $json[$kk]['date'] = $vv['date'];
            $json[$kk]['id'] = $vv['id'];
            $json[$kk]['order_id'] = $vv['order_id'];
            $json[$kk]['is_manual'] = $vv['is_manual'];
            if ($vv['is_manual'] == 1) {
                $json[$kk]['data'] = json_decode($vv['pesanan'], true);
            } else {
                $vv['pesanan'] = array();
                foreach (json_decode($vv['json'], true) as $kkk => $vvv) {
                    $vv['pesanan'][$kkk]['item_name'] = $vvv['product_text'];
                    $vv['pesanan'][$kkk]['item_sku'] = $vvv['sku'];
                    $vv['pesanan'][$kkk]['item_id'] = $vvv['product'];
                    $vv['pesanan'][$kkk]['qty'] = $vvv['qty'];
                }
                $json[$kk]['data'] = $vv['pesanan'];
            }
        }

        $dtt['pesanan'] = json_encode($json, true);


        $mg = '"MG"';
        $check = $this->mymodel->selectWithQuery("SELECT id FROM transaction WHERE customer = '$id_customer' AND json LIKE '%$mg%' AND order_status NOT IN ('UNPAID','CANCELLED','IN_CANCEL','RETURN','REFUND') LIMIT 1");
        if ($check) {
            $dtt['brand'] = "MG";
        } else {
            $dtt['brand'] = "POME";
        }

        $this->db->update('customer', $dtt, array('id' => $id_customer));
        return $dt;
    }

    public function update_stock($id_product)
    {
        $dtp = [];
        $query = $this->mymodel->selectWithQuery("
            SELECT 
                SUM(qty_in) AS qty_in, 
                SUM(qty_in_pos) AS qty_in_pos, 
                SUM(qty_out) AS qty_out, 
                SUM(qty_out_pos) AS qty_out_pos, 
                SUM(qty_out_retur) AS qty_out_retur,
                SUM(qty) AS qty 
            FROM stock 
            WHERE product = '$id_product'
        ");

        $dtp['stock_in'] = strval($query[0]['qty_in']);
        $dtp['stock_in_pos'] = strval($query[0]['qty_in_pos']);
        $dtp['stock_out'] = strval(abs($query[0]['qty_out']) * -1);
        $dtp['stock_out_pos'] = strval(abs($query[0]['qty_out_pos']) * -1);
        $dtp['stock_out_retur'] = strval(abs($query[0]['qty_out_retur']) * -1);
        $dtp['stock'] = strval(doubleval($query[0]['qty']));

        $this->db->update('product', $dtp, ['id' => $id_product]);
    }


    public function create()
    {


        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dtt = $_POST['dtt'];
        $check = $_POST['check'];

        // unset($dt['birth_date']);

        for (;;) {
            $order_id = "BHS" . DATE("Ymdhis") . $this->template->generateNumber(3);
            $check = $this->mymodel->selectWithQuery("SELECT id FROM transaction WHERE order_id = '$order_id' LIMIT 1");
            if (empty($check)) {
                $dt['order_id'] = $order_id;
                break;
            }
        }
        $dt['date'] = DATE("Y-m-d H:i:s");
        $dt['cs'] = $user['code'];
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];
        $dt['marketplace'] = 'WA';
        // $dt['payment_status'] = 'Unpaid';
        $dt['order_status'] = 'UNPAID';
        $dt['type'] = 'Out';
        $dt['type_sub'] = 'POS';
        $dt['is_manual'] = 1;

        $this->db->insert('transaction', $dt);
        $id = $this->db->insert_id();
        return redirect(base_url() . 'transaction/edit?id=' . $id);

        die;

        $user = $_SESSION['user'];
        $data['title'] = 'Buat Order - ' . $this->template->title();
        $data['data'] = array();




        $data['brand'] = $this->mymodel->selectWithQuery("SELECT *
        FROM brand
        ORDER BY code ASC");

        $data['marketplace'] = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace
        ORDER BY name ASC");

        $data['shipping'] = $this->mymodel->selectWithQuery("SELECT *
        FROM shipping
        ORDER BY name ASC");

        $data['product'] = $this->mymodel->selectWithQuery("SELECT *
        FROM product WHERE is_varian = 0
        ORDER BY name ASC");

        $data['content'] = $this->load->view("transaction/create", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }


    public function store()
    {

        $customer = $_POST['customer'];

        if ($dt['customer_text'] == "" && $dt['customer'] == "") {
            $msg = 'Pelanggan wajib diisi!';
            echo $this->template->alert_danger($msg);
            die;
        }

        for (;;) {
            $order_id = "BHS" . DATE("Ymdhis") . $this->template->generateNumber(3);
            $check = $this->mymodel->selectWithQuery("SELECT id FROM transaction WHERE order_id = '$order_id' LIMIT 1");
            if (empty($check)) {
                $dt['order_id'] = $order_id;
                break;
            }
        }


        if ($dt['price_total'] == 0) {
            $dt['payment_status'] = "Paid";
        }
        $dt['price_total'] = $dt['omset_kotor'];
        $dt['biaya_lainnya'] = $dt['packing_price'] + $dt['other_price'];
        $dt['dana_pencairan'] = $dt['omset_kotor'] - $dt['diskon_penjual'];
        $dt['omset_bersih'] = $dt['omset_kotor'] - $dt['diskon_penjual'];

        if ($check  == '1') {
            $dtc = $customer;
            $dt['c_type'] = "Pelanggan";
            $dtc['akun_type'] = "Pelanggan";
            $dtc['brand'] = $dt['brand'];
            $dtc['marketplace'] = $dt['marketplace'];
            $dtc['status'] = "Aktif";
            $dtc['created_at'] = DATE("Y-m-d H:i:s");
            $dtc['updated_at'] = DATE("Y-m-d H:i:s");
            $dtc['created_by'] = $user['id'];
            $dtc['full_name'] = $dt['customer_text'];
            $dtc['phone'] = $dt['phone'];
            $dtc['username'] = $dt['c_username'];
            $dtc['count_order'] = 1;
            $dtc['return_trx'] = $dt['return'];
            $dtc['customer_price'] = $dt['customer_price'];
            $dtc['price_total'] = $dt['price_total'];
            $dtc['komisi_afiliasi'] = intval($dt['komisi_afiliasi']);
            $dtc['omset_kotor'] = $dt['omset_kotor'];
            $dtc['diskon_penjual'] = $dt['diskon_penjual'];
            $dtc['omset_bersih'] = $dt['omset_bersih'];
            $dtc['dana_pencairan'] = $dt['dana_pencairan'];
            $dtc['marketplace_fee'] = intval($dt['marketplace_fee']);
            $dtc['first_order'] = strval($dt['date']);
            $dtc['last_order'] = strval($dt['date']);
            $dtc['birth_date'] = $dt['birth_date'];
            $dtc['address'] = $dt['address'];
            $dtc['province_text'] = $dt['province_text'];
            $dtc['city_text'] = $dt['city_text'];
            $dtc['subdistrict_text'] = $dt['subdistrict_text'];
            // print_r($dtc);die;
            $this->db->insert('customer', $dtc);
            $dt['customer'] = $this->db->insert_id();
        }

        // $dt['updated_at'] = DATE("Y-m-d H:i:s");
        // $dt['updated_by'] = $user['id'];

        $id_customer = $dt['customer'];

        $this->db->insert('transaction', $dt);
        $id = $this->db->insert_id();

        $this->customer_summary($id_customer);
        if ($_POST['existing_customer'] > 0 && $_POST['existing_customer'] != $dt['customer']) {
            $this->customer_summary($_POST['existing_customer']);
        }

        $detail = $this->mymodel->selectWithQuery("SELECT json FROM transaction WHERE id = '$id'");
        $detail = $detail[0];


        $js = array();
        $i = 0;
        foreach (json_decode($detail['json'], true) as $k4 => $v4) {
            $js[$i]['qty'] += $v4['qty'];
            $js[$i]['item_sku'] = $v4['sku'];
            $js[$i]['item_name'] = $v4['product_text'];
            $i++;
        }

        $dt['pesanan'] = json_encode($js, true);

        if ($this->db->update('transaction', $dt, array('id' => $id))) {
            $msg = 'Tambah data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function set_cs()
    {
        $id = $_GET['id'];
        $data['data'] = $this->mymodel->selectWithQuery("SELECT id,cs FROM transaction
        WHERE id = '$id'
        ");
        $data['data'] = $data['data'][0];
        $data['cs'] = $this->mymodel->selectWithQuery("SELECT * FROM user
        WHERE role IN ('3')
        ORDER BY code ASC
        ");
        $this->load->view("transaction/set_cs", $data);
    }

    public function set_cs_process()
    {
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");

        if ($this->db->update('transaction', $dt, array('id' => $id))) {
            $this->db->update('stock', $dt, array('id_trx' => $id));
            $this->db->update('stock_product_3rd', $dt, array('id_trx' => $id));
            $msg = 'Simpan data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Simpan data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function set_return()
    {
        $id = $_GET['id'];
        $data['data'] = $this->mymodel->selectWithQuery("SELECT id,json,return_at FROM transaction
        WHERE id = '$id'
        ");
        $data['data'] = $data['data'][0];
        $this->load->view("transaction/set_return", $data);
    }

    public function set_return_process()
    {
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $date = DATE('Y-m-d H:i:s', strtotime($_POST['date']));

        $this->db->select('pesanan,json,shipping,awb_number,marketplace,shop_id,shop_name,id,order_id');
        $dt  = $this->mymodel->selectDataOne('transaction', array('id' => $id));
        $dt['order_status'] = "RETURN";
        $order_id = $dt['order_id'];
        $marketplace = $dt['marketplace'];
        $shop_id = $dt['shop_id'];
        $user = $_SESSION['user'];
        $id_trx = $dt['id'];

        $dt['stock_product_3rd'] = json_decode($dt['pesanan'], true);
        $dt['stock'] = json_decode($dt['json'], true);

        if ($id) {
            $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'In' ");
            $this->db->delete('stock', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'In' ");
        }

        foreach ($dt['stock_product_3rd'] as $k2 => $v2) {
            $dts = array();
            $dts['shipping'] = strval($dt['shipping']);
            $dts['awb_number'] = strval($dt['awb_number']);
            $dts['order_status'] = strval($dt['order_status']);
            $dts['marketplace'] = strval($dt['marketplace']);
            $dts['shop_id'] = strval($dt['shop_id']);
            $dts['shop_name'] = strval($dt['shop_name']);
            $dts['brand'] = strval($dt['brand']);
            $dts['id_trx'] = strval($dt['id']);
            $dts['qty'] = 0 - abs($v2['qty']);
            // $dts['qty_out'] = abs($v2['qty']);
            $dts['qty_out_pos'] = abs($v2['qty']);
            $dts['qty_in'] = '0';

            $dts['original_price'] = doubleval($v2['original_price']);
            $dts['price'] = doubleval($v2['price']);
            $dts['discount'] = doubleval($v2['discount']);
            $dts['price_total'] = doubleval($v2['price_total']);

            $dts['product_id'] = strval($v2['id_product_parent']);
            $dts['product_sku'] = strval($v2['sku_parent']);
            $dts['product_text'] = strval($v2['name_parent']);
            $dts['varian_id'] = strval($v2['id_product']);
            $dts['varian_sku'] = strval($v2['sku']);
            $dts['varian_text'] = strval($v2['name']);
            $dts['order_id'] = $dt['order_id'];
            $dts['type'] = "Out";
            $dts['type_sub'] = "POS";
            $dts['created_at'] = DATE("Y-m-d H:i:s");
            $dts['date'] = $dt['date'];
            $dts['created_by'] = strval($user['id']);
            $dts['status'] = "Aktif";
            $dts['desc'] = "Penjualan";

            $dts['date'] = DATE("Y-m-d H:i:s", strtotime($date));
            $dts['type'] = "In";
            $dts['qty'] =  abs($v2['qty']);
            $dts['qty_out'] = '0';
            $dts['qty_out_pos'] = '0';
            $dts['qty_in'] = '0';
            $dts['qty_in_pos'] =  abs($v2['qty']);
            $dts['desc'] = "Return";

            $this->db->insert('stock_product_3rd', $dts);
        }
        foreach ($dt['stock'] as $k2 => $v2) {
            $dts = array();
            $dts['shipping'] = strval($dt['shipping']);
            $dts['awb_number'] = strval($dt['awb_number']);
            $dts['order_status'] = strval($dt['order_status']);
            $dts['marketplace'] = strval($dt['marketplace']);
            $dts['shop_id'] = strval($dt['shop_id']);
            $dts['shop_name'] = strval($dt['shop_name']);
            $dts['id_trx'] = strval($dt['id']);
            $dts['qty'] = 0 - abs($v2['qty']);
            $dts['qty_out_pos'] = abs($v2['qty']);
            $dts['qty_in'] = '0';
            $dts['price'] = doubleval($v2['price']);
            $dts['discount'] = doubleval($v2['discount']);
            $dts['hpp'] = doubleval($v2['hpp']);
            $dts['price_total'] = doubleval($v2['price_total']);
            $dts['product'] = $v2['product'];
            $dts['product_text'] = $v2['product_text'];
            $dts['sku'] = $v2['sku'];
            $dts['order_id'] = $dt['order_id'];
            $dts['brand'] = strval($dt['brand']);
            $dts['type'] = "Out";
            $dts['type_sub'] = "POS";
            $dts['created_at'] = DATE("Y-m-d H:i:s");
            $dts['date'] = $dt['date'];
            $dts['created_by'] = strval($user['id']);
            $dts['status'] = "Aktif";
            $dts['date'] = DATE("Y-m-d H:i:s", strtotime($date));
            $dts['type'] = "In";
            $dts['qty'] =  abs($v2['qty']);
            $dts['qty_out'] = '0';
            $dts['qty_out_pos'] = '0';
            $dts['qty_in'] = '0';
            $dts['qty_in_pos'] =  abs($v2['qty']);
            $this->db->insert('stock', $dts);
        }


        foreach ($dt['stock'] as $k => $v) {
            $id_product = $v['product'];
            $this->update_stock($id_product);
        }

        $dt = array();

        $dt['return_at'] = $date;
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['order_status'] = "RETURN";

        $this->db->update('transaction', $dt, array('id' => $id));



        $msg = 'Simpan data berhasil!';
        echo $this->template->alert_success($msg);
    }
    
    public function multi_return()
    {
        $data['return_history'] = $this->getReturnHistory(20);
        $data['content'] = $this->load->view('transaction/multi_return', $data, true);
        $data['title'] = 'Multi Return - ' . $this->template->title();
        $this->load->view('TemplateDashboard', $data);
    }
    
    public function get_transaction_by_awb()
    {
        $awb_number = $this->input->post('awb_number');
        
        $transaction = $this->mymodel->selectWithQuery("
            SELECT id, order_id, awb_number, marketplace, date, order_status, json AS pesanan 
            FROM transaction 
            WHERE awb_number = '".$this->db->escape_str($awb_number)."'
            LIMIT 1
        ");
        
        if (empty($transaction)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Transaksi dengan no resi '.$awb_number.' tidak ditemukan'
            ]);
            return;
        }
        
        $transaction = $transaction[0];
        
        if ($transaction['order_status'] === 'RETURN') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Transaksi dengan no resi '.$awb_number.' sudah berstatus RETURN'
            ]);
            return;
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $transaction
        ]);
    }
    
    // public function process_multi_return()
    // {
    //     // Ambil payload
    //     $returnList = json_decode($this->input->post('return_list') ?? '[]', true);
    //     $returnDateRaw = $this->input->post('return_date');

    //     // Normalisasi tanggal retur
    //     $date = date('Y-m-d H:i:s');
    //     if ($returnDateRaw) {
    //         $tmp = strtotime($returnDateRaw);
    //         if ($tmp !== false) $date = date('Y-m-d H:i:s', $tmp);
    //     }

    //     $user = $_SESSION['user'] ?? ['id' => null];

    //     $success = 0;
    //     $errors  = [];
    //     $queries = [];

    //     // --- Kumpulkan semua trxId yang perlu diproses (dari GOOD + dari trx_ids) ---
    //     $trxIdsFromGood = array_keys($grouped);
    //     $allTrxIds = array_values(array_unique(array_merge($trxIdsFromGood, $extraTrxIds)));

    //     if (empty($allTrxIds)) {
    //         echo json_encode([
    //             'status'  => 'error',
    //             'message' => 'Tidak ada transaksi untuk diproses.',
    //         ]);
    //         return;
    //     }

    //     foreach ($allTrxIds as $trxId) {
    //         $this->db->trans_start();
    //         try {
    //             // Pastikan transaksi ada (untuk referensi order_id/awb dsb bila diperlukan)
    //             $dt = $this->mymodel->selectDataOne('transaction', ['id' => $trxId]);
    //             if (!$dt) {
    //                 throw new Exception("Transaksi ID $trxId tidak ditemukan.");
    //             }

    //             $items = $grouped[$trxId] ?? []; // GOOD items utk trx ini (bisa kosong)

    //             // Jika ada GOOD items: hapus stok lama POS (In/Ongoing) -> reinsert In (RETURN) dgn COPY dari baris Out
    //             if (!empty($items)) {
    //                 // Hapus stok POS yang In / Ongoing agar tidak dobel
    //                 $this->db->where('id_trx', $trxId)
    //                         ->where('type_sub', 'POS')
    //                         ->where_in('type', ['Ongoing','In'])
    //                         ->delete('stock');

    //                 // (opsional) jika ada mirror table dan ingin dibersihkan juga:
    //                 $this->db->where('id_trx', $trxId)
    //                         ->where('type_sub', 'POS')
    //                         ->where_in('type', ['Ongoing','In'])
    //                         ->delete('stock_product_3rd');

    //                 foreach ($items as $it) {
    //                     $sku         = $it['sku'] ?? null;
    //                     $productText = $it['product'] ?? null;

    //                     // === Sumber: cari 1 baris Out yang cocok di tabel stock ===
    //                     $this->db->select('*')
    //                             ->from('stock')
    //                             ->where('id_trx', $trxId)
    //                             ->where('type_sub', 'POS')
    //                             ->where('type', 'Out')
    //                             ->group_start()
    //                                 ->where('sku', $sku)
    //                                 ->or_where('product_text', $productText)
    //                             ->group_end()
    //                             ->order_by('id', 'DESC')
    //                             ->limit(1);
    //                     $src = $this->db->get()->row_array();

    //                     if ($src) {
    //                         // Salin baris sumber dan ubah jadi In (RETURN)
    //                         $copy = $src;
    //                         unset($copy['id']); // auto increment

    //                         $copy['type']         = 'In';
    //                         $copy['order_status'] = 'RETURN';
    //                         $copy['status']       = 'Aktif';

    //                         // Pindahkan kuantitas OUT -> IN (sesuaikan kolom dgn skema Anda)
    //                         $copy['qty_in_pos'] = abs((int)($src['qty_out_pos'] ?? 0));
    //                         $copy['qty_in']     = abs((int)($src['qty_out'] ?? 0));

    //                         // Nol-kan field OUT pada salinan
    //                         $copy['qty_out_pos'] = 0;
    //                         $copy['qty_out']     = 0;

    //                         // Kolom ringkas qty (jika ada)
    //                         if (array_key_exists('qty', $copy)) {
    //                             $copy['qty'] = $copy['qty_in_pos'] ?: $copy['qty_in'];
    //                         }

    //                         // Pastikan referensi produk tetap dari sumber
    //                         $copy['sku']          = $src['sku'];
    //                         $copy['product_text'] = $src['product_text'] ?? ($src['product'] ?? $productText);

    //                         // Tanggal & audit
    //                         // Catatan: tidak mengambil dari transaction; gunakan tanggal retur sebagai timestamp pencatatan.
    //                         $copy['date']       = $date;
    //                         $copy['created_at'] = date('Y-m-d H:i:s');
    //                         $copy['updated_at'] = date('Y-m-d H:i:s');
    //                         $copy['created_by'] = $user['id'];

    //                         $this->db->insert('stock', $copy);
    //                     } else {
    //                         // Fallback jika baris Out tidak ditemukan -> tetap insert minimal
    //                         $fallback = [
    //                             'id_trx'       => $trxId,
    //                             'order_status' => 'RETURN',
    //                             'type'         => 'In',
    //                             'type_sub'     => 'POS',
    //                             'sku'          => $sku,
    //                             'product_text' => $productText,
    //                             'qty_in_pos'   => abs((int)($it['qty'] ?? 0)),
    //                             'qty_in'       => 0,
    //                             'qty_out_pos'  => 0,
    //                             'qty_out'      => 0,
    //                             'status'       => 'Aktif',
    //                             'date'         => $date,
    //                             'created_at'   => date('Y-m-d H:i:s'),
    //                             'updated_at'   => date('Y-m-d H:i:s'),
    //                             'created_by'   => $user['id'],
    //                         ];
    //                         if ($this->db->field_exists('qty', 'stock')) {
    //                             $fallback['qty'] = $fallback['qty_in_pos'];
    //                         }
    //                         $this->db->insert('stock', $fallback);
    //                     }

    //                     // Recalculate stok produk terkait (gunakan nama/ID produk sesuai implementasi Anda)
    //                     // Di kode Anda sebelumnya menerima $it['product'] (teks). Sesuaikan bila perlu.
    //                     $this->update_stock($it['product']);
    //                 }
    //             }

    //             // --- PAKSA STATUS RETURN UNTUK SEMUA BARIS STOCK DI TRANSAKSI INI (BAD ikut) ---
    //             $this->db->set('order_status', 'RETURN');
    //             if ($this->db->field_exists('updated_at', 'stock')) {
    //                 $this->db->set('updated_at', date('Y-m-d H:i:s'));
    //             }
    //             $this->db->where('id_trx', $trxId)->update('stock');

    //             // (Opsional) sinkronisasi di mirror table
    //             $this->db->set('order_status', 'RETURN');
    //             if ($this->db->field_exists('updated_at', 'stock_product_3rd')) {
    //                 $this->db->set('updated_at', date('Y-m-d H:i:s'));
    //             }
    //             $this->db->where('id_trx', $trxId)->update('stock_product_3rd');

    //             // Update status di tabel transaction
    //             $this->db->where('id', $trxId)->update('transaction', [
    //                 'return_at'    => $date,
    //                 'updated_at'   => date('Y-m-d H:i:s'),
    //                 'order_status' => 'RETURN'
    //             ]);

    //             $this->db->trans_complete();
    //             $queries = array_merge($queries, $this->db->queries);

    //             if ($this->db->trans_status() === false) {
    //                 throw new Exception('DB Transaction error');
    //             }

    //             $success++;
    //         } catch (Exception $e) {
    //             $this->db->trans_rollback();
    //             $errors[] = "ID $trxId: " . $e->getMessage();
    //         }
    //     }

    //     $message = "$success transaksi berhasil diproses retur.";
    //     if (!empty($errors)) {
    //         $message .= ' Gagal: ' . implode(' | ', $errors);
    //     }

    //     echo json_encode([
    //         'status'  => $success > 0 ? 'success' : 'error',
    //         'message' => $message,
    //         'queries' => $queries
    //     ]);
    // }

    public function process_multi_return()
    {
        $original_save_queries = $this->db->save_queries;
        $this->db->save_queries = false;

        $ids = $this->input->post('trx_ids'); 
        $return_list = $this->input->post('return_list');
        $payload = json_decode($return_list, true) ?: [];
        $date_raw = $this->input->post('return_date');
        $date = $date_raw ? date('Y-m-d H:i:s', strtotime($date_raw)) : date('Y-m-d H:i:s');

        // Map: $statusMap[order_id_or_trxid][sku] = ['status'=>'GOOD|BAD', 'return_status'=>'RETURN|RETURN_UNSHIPPED', 'qty'=>int]
        $statusMap = [];
        foreach ($payload as $it) {
            $oid   = strval($it['order_id'] ?? '');      // bisa id transaksi dari FE
            $sku   = strval($it['sku'] ?? '');
            $qty   = intval($it['qty'] ?? 0);
            $stt   = strtoupper(strval($it['status'] ?? 'GOOD'));
            $rtn   = strtoupper(strval($it['return_status'] ?? 'RETURN')); // <= penting

            if (in_array($stt, ['SKIP', 'TIDAK_RETUR', 'TIDAK RETURN'], true)) {
                continue;
            }

            if ($oid && $sku) {
                $statusMap[$oid][$sku] = [
                    'status'        => ($stt === 'BAD' ? 'BAD' : 'GOOD'),
                    'return_status' => ($rtn === 'RETURN_UNSHIPPED' ? 'RETURN_UNSHIPPED' : 'RETURN'),
                    'qty'           => abs($qty),
                ];
            }
        }

        if (empty($ids)) {
            $this->db->save_queries = $original_save_queries;
            echo json_encode([
                'status' => 'error',
                'message' => 'Tidak ada transaksi yang dipilih.'
            ]);
            return;
        }

        $user = $_SESSION['user'];
        $success = 0;
        $errors = [];
        $products_to_refresh = [];
        $historyTransactions = [];

        foreach ($ids as $id) {
            $this->db->trans_start();

            try {
                $dt = $this->mymodel->selectDataOne('transaction', ['id' => $id]);

                if (!$dt) {
                    throw new Exception("Transaksi ID $id tidak ditemukan.");
                }

                $dt['order_status'] = "RETURN";
                $dt['stock_product_3rd'] = json_decode($dt['pesanan'], true);
                $dt['stock'] = json_decode($dt['json'], true);
                $dt['date'] = $date;

                $trxReturnTypes = []; // kumpulkan tipe retur yang muncul pada transaksi ini
                $returnStockItems = [];
                $returnSkuMap = [];

                if (is_array($dt['stock'])) {
                    foreach ($dt['stock'] as $prod) {
                        $oidTrx   = trim(strval($dt['id']));        // id transaksi
                        $oidOrder = trim(strval($dt['order_id']));  // order marketplace
                        $skuVar   = trim(strval($prod['sku'] ?? ''));

                        $info = $statusMap[$oidTrx][$skuVar]
                            ?? $statusMap[$oidOrder][$skuVar]
                            ?? null;

                        if (!$info) {
                            continue;
                        }

                        $prod['status'] = strtoupper($info['status']);
                        $prod['return_status'] = strtoupper($info['return_status']);
                        $prod['qty_return'] = abs(intval($info['qty']));

                        $returnStockItems[] = $prod;
                        if ($skuVar !== '') {
                            $returnSkuMap[$skuVar] = true;
                        }
                        $trxReturnTypes[$prod['return_status']] = true;
                    }
                }

                if (empty($returnStockItems)) {
                    throw new Exception("Tidak ada item retur yang dipilih untuk transaksi ID $id.");
                }




                // Hapus stok lama
                $this->db->delete('stock_product_3rd', "id_trx = '$id' AND type_sub = 'POS' AND type IN ('Ongoing', 'In')");
                $this->db->delete('stock', "id_trx = '$id' AND type_sub = 'POS' AND type IN ('Ongoing', 'In')");

                // Insert ke stock_product_3rd
                foreach ($dt['stock_product_3rd'] as $item) {
                    $sku = trim(strval($item['sku'] ?? ''));
                    if ($sku !== '' && !isset($returnSkuMap[$sku])) {
                        continue;
                    }

                    $dts = [
                        'shipping' => strval($dt['shipping']),
                        'awb_number' => strval($dt['awb_number']),
                        'order_status' => "RETURN",
                        'marketplace' => strval($dt['marketplace']),
                        'shop_id' => strval($dt['shop_id']),
                        'shop_name' => strval($dt['shop_name']),
                        'brand' => strval($dt['brand'] ?? ''),
                        'id_trx' => $id,
                        'qty' => abs($item['qty']),
                        'qty_out' => 0,
                        'qty_out_pos' => 0,
                        'qty_in' => 0,
                        'qty_in_pos' => abs($item['qty']),
                        'original_price' => doubleval($item['original_price']),
                        'price' => doubleval($item['price']),
                        'discount' => doubleval($item['discount']),
                        'price_total' => doubleval($item['price_total']),
                        'product_id' => strval($item['id_product_parent']),
                        'product_sku' => strval($item['sku_parent']),
                        'product_text' => strval($item['name_parent']),
                        'varian_id' => strval($item['id_product']),
                        'varian_sku' => strval($item['sku']),
                        'varian_text' => strval($item['name']),
                        'order_id' => $dt['order_id'],
                        'type' => "In",
                        'type_sub' => "POS",
                        'created_at' => date("Y-m-d H:i:s"),
                        'date' => $date,
                        'created_by' => $user['id'],
                        'status' => "Aktif",
                        'desc' => "Return"
                    ];
                    $this->db->insert('stock_product_3rd', $dts);
                }

                $trx_product_ids = [];
                foreach ($returnStockItems as $item) {
                    $stt       = strtoupper($item['status'] ?? 'GOOD');           // GOOD | BAD
                    $retType   = strtoupper($item['return_status'] ?? 'RETURN');  // RETURN | RETURN_UNSHIPPED
                    $qtyAbs    = intval($item['qty_return'] ?? abs(intval($item['qty'])));
                    $productId = intval($item['product'] ?? 0);

                    $isBad     = ($stt === 'BAD');

                    // Jika BAD (barang rusak), hapus jejak Out lama (tetap seperti sebelumnya)
                    if ($isBad) {
                        $this->db->delete(
                            'stock',
                            "id_trx = '$id' AND type_sub = 'POS' AND type = 'Out' AND sku = '{$item['sku']}'"
                        );
                    }

                    // Arah & kolom kuantitas ditentukan HANYA oleh Good/Bad:
                    // - GOOD => stok bertambah (In POS)
                    // - BAD  => stok berkurang sebagai retur (Out, qty_out_retur naik)
                    $type          = $isBad ? "Out" : "In";
                    $qty           = $isBad ? -$qtyAbs : +$qtyAbs;
                    $qty_in_pos    = $isBad ? 0       : $qtyAbs;
                    $qty_out_retur = $isBad ? $qtyAbs : 0;

                    $this->db->insert('stock', [
                        'shipping'       => strval($dt['shipping']),
                        'awb_number'     => strval($dt['awb_number']),
                        'order_status'   => $retType, // label saja (RETURN/RETURN_UNSHIPPED)
                        'marketplace'    => strval($dt['marketplace']),
                        'shop_id'        => strval($dt['shop_id']),
                        'shop_name'      => strval($dt['shop_name']),
                        'brand'          => strval($dt['brand'] ?? ''),
                        'id_trx'         => $id,
                        'qty'            => $qty,
                        'qty_out'        => 0,
                        'qty_out_pos'    => 0,
                        'qty_in'         => 0,
                        'qty_in_pos'     => $qty_in_pos,
                        'qty_out_retur'  => $qty_out_retur,
                        'price'          => doubleval($item['price'] ?? 0),
                        'discount'       => doubleval($item['discount'] ?? 0),
                        'hpp'            => doubleval($item['hpp'] ?? 0),
                        'price_total'    => doubleval($item['price_total'] ?? 0),
                        'product'        => $productId,
                        'product_text'   => $item['product_text'] ?? '',
                        'sku'            => $item['sku'],
                        'order_id'       => $dt['order_id'],
                        'type'           => $type,      // In / Out
                        'type_sub'       => "POS",
                        'created_at'     => date("Y-m-d H:i:s"),
                        'date'           => $date,
                        'created_by'     => $user['id'] ?? 1,
                        'status'         => "Aktif",
                        'desc'           => "Return Scan"
                    ]);

                    if ($productId > 0) {
                        $trx_product_ids[$productId] = true;
                    }
                }


                $trxOrderStatus =
                    (isset($trxReturnTypes['RETURN']) ? 'RETURN' :
                    (isset($trxReturnTypes['RETURN_UNSHIPPED']) ? 'RETURN_UNSHIPPED' : 'RETURN'));

                $this->db->update('transaction', [
                    'return_at'    => $date,
                    'updated_at'   => date("Y-m-d H:i:s"),
                    'order_status' => $trxOrderStatus
                ], ['id' => $id]);


                $this->db->trans_complete();

                if ($this->db->trans_status() === FALSE) {
                    throw new Exception("DB Transaction error");
                }

                foreach (array_keys($trx_product_ids) as $productId) {
                    $products_to_refresh[$productId] = true;
                }

                $historyTransactions[] = [
                    'id' => (int) $dt['id'],
                    'order_id' => (string) ($dt['order_id'] ?? ''),
                    'awb_number' => (string) ($dt['awb_number'] ?? ''),
                    'marketplace' => (string) ($dt['marketplace'] ?? ''),
                    'return_status' => (string) $trxOrderStatus,
                ];
                $success++;
            } catch (Exception $e) {
                $this->db->trans_rollback();

                $db_error = $this->db->error();
                $db_error_msg = $db_error['message'] ?? 'Unknown DB error';

                $errors[] = "ID $id: ".$e->getMessage()." | DB Error: $db_error_msg";
            }
        }

        foreach (array_keys($products_to_refresh) as $productId) {
            $this->update_stock($productId);
        }

        if ($success > 0) {
            $this->saveReturnHistory($payload, $historyTransactions, $date);
        }

        $this->db->save_queries = $original_save_queries;

        $message = "$success transaksi berhasil diproses retur.";
        if (!empty($errors)) {
            $message .= ' Gagal: '.implode(' | ', $errors);
        }

        echo json_encode([
            'status' => $success > 0 ? 'success' : 'error',
            'message' => $message
        ]);

    }

    private function hasReturnHistoryTable(): bool
    {
        static $hasTable = null;
        if ($hasTable !== null) {
            return $hasTable;
        }

        $result = $this->db->query("SHOW TABLES LIKE 'transaction_return_history'");
        $hasTable = $result && $result->num_rows() > 0;
        return $hasTable;
    }

    private function saveReturnHistory(array $payload, array $transactions, string $returnDate): void
    {
        if (!$this->hasReturnHistoryTable()) {
            return;
        }

        $user = $this->session->userdata('user');
        $userId = $user['id'] ?? 0;

        $historyPayload = json_encode(array_values($payload), JSON_UNESCAPED_UNICODE);
        $transactionSummary = json_encode(array_values($transactions), JSON_UNESCAPED_UNICODE);
        $payloadHash = sha1($historyPayload . '|' . $transactionSummary . '|' . $returnDate);

        $exists = $this->db
            ->select('id')
            ->from('transaction_return_history')
            ->where('payload_hash', $payloadHash)
            ->limit(1)
            ->get()
            ->row_array();

        if (!empty($exists)) {
            return;
        }

        $this->db->insert('transaction_return_history', [
            'payload' => $historyPayload,
            'payload_hash' => $payloadHash,
            'transaction_summary' => $transactionSummary,
            'transaction_count' => count($transactions),
            'item_count' => count($payload),
            'return_date' => $returnDate,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $userId,
        ]);
    }

    private function getReturnHistory(int $limit = 30): array
    {
        if (!$this->hasReturnHistoryTable()) {
            return [];
        }

        $rows = $this->db
            ->select('id, transaction_count, item_count, return_date, created_at, transaction_summary')
            ->from('transaction_return_history')
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $labelParts = [];
            if (!empty($row['return_date'])) {
                $labelParts[] = 'Retur ' . date('d M Y H:i', strtotime($row['return_date']));
            } elseif (!empty($row['created_at'])) {
                $labelParts[] = date('d M Y H:i', strtotime($row['created_at']));
            }
            if (!empty($row['transaction_count'])) {
                $labelParts[] = $row['transaction_count'] . ' trx';
            }
            if (!empty($row['item_count'])) {
                $labelParts[] = $row['item_count'] . ' item';
            }

            $summary = json_decode($row['transaction_summary'] ?? '[]', true);
            if (is_array($summary) && !empty($summary[0]['awb_number'])) {
                $labelParts[] = $summary[0]['awb_number'];
            }

            $row['label'] = $labelParts ? implode(' | ', $labelParts) : 'History Return';
        }
        unset($row);

        return $rows;
    }

    public function return_history()
    {
        $id = $this->input->get('id');
        if (empty($id)) {
            show_error('ID history return tidak ditemukan', 400);
            return;
        }

        if (!$this->hasReturnHistoryTable()) {
            show_error('Tabel history return belum tersedia', 500);
            return;
        }

        $row = $this->db
            ->select('payload, transaction_summary, transaction_count, item_count, return_date, created_at')
            ->from('transaction_return_history')
            ->where('id', $id)
            ->get()
            ->row_array();

        if (empty($row)) {
            show_error('History return tidak ditemukan', 404);
            return;
        }

        $payload = json_decode($row['payload'] ?? '[]', true);
        $transactions = json_decode($row['transaction_summary'] ?? '[]', true);

        echo '<!doctype html><html><head><meta charset="utf-8"><title>History Return</title>';
        echo '<style>body{font-family:Arial,sans-serif;margin:20px;color:#222}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{border:1px solid #ddd;padding:8px;vertical-align:top}th{background:#f5f5f5;text-align:left}.meta{margin-bottom:16px}.meta div{margin-bottom:4px}</style>';
        echo '</head><body>';
        echo '<h2>History Return</h2>';
        echo '<div class="meta">';
        echo '<div><strong>Tanggal retur:</strong> ' . (!empty($row['return_date']) ? date('d M Y H:i', strtotime($row['return_date'])) : '-') . '</div>';
        echo '<div><strong>Disimpan:</strong> ' . (!empty($row['created_at']) ? date('d M Y H:i', strtotime($row['created_at'])) : '-') . '</div>';
        echo '<div><strong>Jumlah transaksi:</strong> ' . intval($row['transaction_count'] ?? 0) . '</div>';
        echo '<div><strong>Jumlah item:</strong> ' . intval($row['item_count'] ?? 0) . '</div>';
        echo '</div>';

        if (!empty($transactions) && is_array($transactions)) {
            echo '<h3>Transaksi</h3><table><thead><tr><th>No</th><th>Order ID</th><th>No Resi</th><th>Marketplace</th><th>Status Return</th></tr></thead><tbody>';
            foreach ($transactions as $index => $transaction) {
                echo '<tr>';
                echo '<td>' . ($index + 1) . '</td>';
                echo '<td>' . htmlspecialchars($transaction['order_id'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($transaction['awb_number'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($transaction['marketplace'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($transaction['return_status'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '<h3>Item Return</h3><table><thead><tr><th>No</th><th>Order Ref</th><th>SKU</th><th>Produk</th><th>Qty</th><th>Kondisi</th><th>Tipe Return</th></tr></thead><tbody>';
        if (!empty($payload) && is_array($payload)) {
            foreach ($payload as $index => $item) {
                echo '<tr>';
                echo '<td>' . ($index + 1) . '</td>';
                echo '<td>' . htmlspecialchars((string) ($item['order_id'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($item['sku'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($item['product'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . intval($item['qty'] ?? 0) . '</td>';
                echo '<td>' . htmlspecialchars($item['status'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($item['return_status'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7">Tidak ada item pada history ini.</td></tr>';
        }
        echo '</tbody></table>';
        echo '</body></html>';
    }

    function generate_stock($id, $dt)
    {

        $user = $_SESSION['user'];
        $id_trx = $id;

        if ($id) {
            $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'Out' ");
            $this->db->delete('stock', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'Out' ");
        }

        $json = json_decode($dt['json'], true);
        foreach ($json as $k2 => $v2) {
            $dts = array();
            $dts['shipping'] = strval($dt['shipping']);
            $dts['awb_number'] = strval($dt['awb_number']);
            $dts['order_status'] = strval($dt['order_status']);
            $dts['marketplace'] = strval($dt['marketplace']);

            $dts['brand'] = strval($v2['brand']);
            $dts['id_trx'] = strval($id);
            $dts['qty'] = 0 - abs($v2['qty']);
            $dts['qty_out'] = 0;
            $dts['qty_out_pos'] = abs($v2['qty']);
            $dts['qty_in'] = '0';
            $dts['price'] = $v2['price'];
            $dts['hpp'] = doubleval($v2['hpp']);
            $dts['price_total'] = $v2['price_total'];
            $dts['product'] = $v2['product'];
            $dts['product_text'] = $v2['product_text'];
            $dts['sku'] = $v2['sku'];
            $dts['order_id'] = $dt['order_id'];
            $dts['type'] = "Out";
            $dts['type_sub'] = "POS";
            $dts['created_at'] = DATE("Y-m-d H:i:s");
            $dts['date'] = $dt['date'];
            $dts['desc'] = "Penjualan";
            $dts['created_by'] = strval($user['id']);
            $dts['status'] = "Aktif";
            $this->db->insert('stock', $dts);
        }
        $this->calculate_stock_product();
    }

    function calculate_stock_product()
    {
        $product = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE is_varian = 0
        ORDER BY sku ASC
        ");
        $product_arr = array();
        foreach ($product as $k => $v) {
            $product_arr[$v['id']] = $v;
        }

        foreach ($product_arr as $k => $v) {
            $id_product = $v['id'];
            $this->update_stock($id_product);
        }
    }


    public function set_resi()
    {
        $id = $_GET['id'];
        $data['data'] = $this->mymodel->selectWithQuery("SELECT id,awb_number FROM transaction
        WHERE id = '$id'
        ");
        $data['data'] = $data['data'][0];

        $this->load->view("transaction/set_resi", $data);
    }

    public function set_resi_process()
    {
        // print_r($_POST);die;
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");

        if ($this->db->update('transaction', $dt, array('id' => $id))) {
            $msg = 'Simpan data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Simpan data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }


    public function refresh()
    {
        $dt = $_GET;
        $data['data']['order_id'] = $dt['order_id'];
        $data['data']['marketplace'] = $dt['marketplace'];
        $this->load->view("transaction/refresh", $data);
    }

    public function refresh_process()
    {
        $dt = $_POST;

        $url = $this->template->endpoint_url() . 'api/marketplace/order/detail?order_id=' . $dt['order_id'] . '&marketplace=' . $dt['marketplace'];

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
        $response = json_decode($response, true);
        curl_close($curl);

        if ($response['status'] == true) {
            $msg = 'Refresh data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $errorMessage = isset($response['message']) ? $response['message'] : 'Terjadi kesalahan yang tidak diketahui.';
            $msg = 'Refresh data tidak berhasil! Error: ' . $errorMessage;
            echo $this->template->alert_danger($msg);
        }
    }


    public function action()
    {
        $id_selected = $_POST['id_selected'] ?? '';
        $code = $_GET['code'] ?? '';

        $data['id'] = $id_selected;
        $data['code'] = $code;

        if ($code == "barang_diterima") {
            $data['question'] = "Apakah kamu yakin ingin mengubah status order ini menjadi <b>Barang Diterima</b>?";
            $data['btn'] = "Barang Diterima";
        } else if ($code == "hapus_data") {
            $data['question'] = "Apakah kamu yakin ingin menghapus data order ini?";
            $data['btn'] = "Hapus Data";
        } else if ($code == "refresh_data") {
            $data['question'] = "Apakah kamu yakin ingin merefresh data order ini?";
            $data['btn'] = "Refresh Data";
        }
        $this->load->view("transaction/action", $data);
    }


    public function action_process()
    {
        $code         = $_POST['code'];
        $user         = $_SESSION['user'];
        $id_selected  = $_POST['id_selected'];
        $handover_type         = strtoupper($_POST['handover_type'] ?? 'PICKUP');
        $handover_time_slot_id = $_POST['handover_time_slot_id'] ?? '';
        $doc_type     = strtoupper($_POST['doc_type'] ?? 'ALL');
        $label_size   = $_POST['label_size'] ?? '4x6';
        $file_format  = strtoupper($_POST['file_format'] ?? 'PDF');

        if (!$id_selected) {
            $msg = 'Tidak ada data yang dipilih!';
            echo $this->template->alert_danger($msg);
            return;
        }

        $id = explode(',', $id_selected);
        $id_list = implode("','", array_map([$this->db, 'escape_str'], $id));

        function getListId($db, $id_list, $is_manual) {
            $condition = $is_manual ? "is_manual = 1" : "is_manual = 0";
            $query = "SELECT id FROM transaction WHERE id IN ('$id_list') AND $condition";
            $result = $db->query($query);

            $list_id = '';
            foreach ($result->result_array() as $row) {
                $list_id .= "'" . $row['id'] . "',";
            }
            return $list_id ? substr($list_id, 0, -1) : '';
        }

        // barang diterima
        if ($code == "barang_diterima") {
            $list_id = getListId($this->db, $id_list, true);
            if ($list_id) {
                $dt = [
                    'order_status' => "DELIVERED",
                    'updated_at'   => DATE("Y-m-d H:i:s"),
                    'updated_by'   => strval($user['id']),
                ];

                $this->db->update('transaction', $dt, " id IN ($list_id) AND is_manual = '1' ");
                echo $this->template->alert_success(
                    'Ubah status order menjadi <b>Barang Diterima</b> berhasil!'
                );
            } else {
                echo $this->template->alert_danger('Pastikan kamu sudah memilih minimal 1 data manual!');
            }

        // hapus data
        } else if ($code == "hapus_data") {
            $list_id = getListId($this->db, $id_list, true);
            if ($list_id) {
                $this->db->delete('transaction', " id IN ($list_id) AND is_manual = '1' ");
                $this->db->delete('stock', " id_trx IN ($list_id) ");
                echo $this->template->alert_success('Hapus data berhasil!');
            } else {
                echo $this->template->alert_danger('Pastikan kamu sudah memilih minimal 1 data manual!');
            }

        // refresh data
        } else if ($code == "refresh_data") {

            $list_id = getListId($this->db, $id_list, false);
        
            if ($list_id) {
                $trx = $this->mymodel->selectWithQuery("
                    SELECT id, order_id, marketplace
                    FROM transaction 
                    WHERE id IN ($list_id) AND is_manual = 0
                ");
        
                $batches = array_chunk($trx, 10);
                $totalProcessed = 0;
        
                foreach ($batches as $batch) {
                    $mh = curl_multi_init();
                    $handles = [];
                    $map = []; 
                
                    foreach ($batch as $v) {
                        $url = $this->template->endpoint_url() .
                            'api/marketplace/order/detail?order_id=' . 
                            $v['order_id'] . '&marketplace=' . $v['marketplace'];
                
                        $ch = curl_init();
                        curl_setopt_array($ch, [
                            CURLOPT_URL            => $url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_TIMEOUT        => 0,
                            CURLOPT_HTTPHEADER     => [
                                'Content-Type: application/json',
                                'User-Agent: Mozilla/5.0 (compatible; BHRefreshBot/1.0; +https://bhskin.co.id)'
                            ],
                        ]);
                
                        $handles[] = $ch;
                        $map[(int)$ch] = [
                            'order_id'   => $v['order_id'],
                            'marketplace'=> $v['marketplace'],
                            'url'        => $url
                        ];
                
                        curl_multi_add_handle($mh, $ch);
                    }
                
                    $running = null;
                    do {
                        curl_multi_exec($mh, $running);
                        curl_multi_select($mh);
                    } while ($running > 0);
                
                    foreach ($handles as $ch) {
                        $key = (int)$ch;
                        $info = $map[$key];
                
                        $responseBody = curl_multi_getcontent($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                        // echo "<pre style='background:#222;color:#0f0;padding:10px;margin-bottom:10px'>";
                        // echo "Order ID: {$info['order_id']}\n";
                        // echo "Marketplace: {$info['marketplace']}\n";
                        // echo "URL: {$info['url']}\n";
                        // echo "HTTP Code: {$httpCode}\n";
                        // echo "Response:\n";
                        // echo htmlspecialchars($responseBody);
                        // echo "</pre>";
                
                        curl_multi_remove_handle($mh, $ch);
                        curl_close($ch);
                    }
                
                    curl_multi_close($mh);
                    $totalProcessed += count($batch);
                }

        
                echo $this->template->alert_success("Refresh data berhasil! Diproses {$totalProcessed} data. Silahkan tunggu beberapa saat hingga data diperbarui!");
        
            } else {
                echo $this->template->alert_danger('Pastikan kamu sudah memilih minimal 1 data order marketplace!');
            }
        }
    }


    public function shipping_process()
    {
        $id_selected = $_POST['id_selected'] ?? '';
        if (!$id_selected) {
            echo 'Tidak ada data yang dipilih';
            return;
        }

        $id = explode(',', $id_selected);
        
        $allRows = $this->db->select('id, order_id, marketplace, shop_id, shop_name, shipping, order_status')
            ->from('transaction')
            ->where_in('id', $id)
            ->where('is_manual', 0)
            ->where('is_for_booking', 0)
            ->get()
            ->result_array();

        if (empty($allRows)) {
            echo 'Tidak ada data marketplace yang valid';
            return;
        }

        $skippedNonRts = [];
        $rows = [];
        foreach ($allRows as $r) {
            $st = strtoupper(trim($r['order_status'] ?? ''));
            if ($st === 'READY_TO_SHIP') {
                $rows[] = $r;
            } else {
                $skippedNonRts[] = $r['order_id'];
            }
        }

        $marketplaces = [];
        foreach ($rows as $row) {
            $marketplace = strtoupper(trim($row['marketplace'] ?? ''));
            $shopId = trim($row['shop_id'] ?? '');
            $shopName = trim($row['shop_name'] ?? 'Shop ' . $shopId);
            $orderId = trim($row['order_id'] ?? '');
            $shipping = trim($row['shipping'] ?? '');
            
            if ($marketplace && $shopId && $orderId && $shipping) {
                if (!isset($marketplaces[$marketplace])) {
                    $marketplaces[$marketplace] = [];
                }

                if (!isset($shipping)) {
                    $shippings[$shipping] = [];
                }
                if (!isset($marketplaces[$marketplace][$shopId])) {
                    $marketplaces[$marketplace][$shopId] = [
                        'shop_name' => $shopName,
                        'orders' => [],
                        'transaction_ids' => [],
                        'shipping_map' => []
                    ];
                }
                $marketplaces[$marketplace][$shopId]['orders'][] = $orderId;
                $marketplaces[$marketplace][$shopId]['transaction_ids'][] = $row['id'];

                $shippingMethod = trim($row['shipping'] ?? '');
                if ($shippingMethod !== '') {
                    if (!isset($marketplaces[$marketplace][$shopId]['shipping_map'][$shippingMethod])) {
                        $marketplaces[$marketplace][$shopId]['shipping_map'][$shippingMethod] = [
                            'orders' => [],
                            'transaction_ids' => []
                        ];
                    }
                    $marketplaces[$marketplace][$shopId]['shipping_map'][$shippingMethod]['orders'][] = $orderId;
                    $marketplaces[$marketplace][$shopId]['shipping_map'][$shippingMethod]['transaction_ids'][] = $row['id'];
                }
            }
        }

        $data['marketplaces'] = $marketplaces;
        $data['shippings'] = $shippings ?? [];
        $data['id_selected'] = $id_selected;
        
        $this->load->view("transaction/shipping_process", $data);
    }

    public function shipping_process_execute()
    {
        $id_selected = $_POST['id_selected'] ?? '';
        $marketplace = $_POST['marketplace'] ?? '';
        $shop_id = $_POST['shop_id'] ?? '';
        $shipping_method = $_POST['shipping_method'] ?? '';
        $pickup_address_id = $_POST['pickup_address_id'] ?? '';
        $pickup_time_id = $_POST['pickup_time_id'] ?? '';
        $tiktok_pickup_start_time = $_POST['tiktok_pickup_start_time'] ?? '';
        $tiktok_pickup_end_time = $_POST['tiktok_pickup_end_time'] ?? '';
        
        if (!$id_selected || !$marketplace || !$shop_id) {
            echo json_encode(['status' => false, 'message' => 'Data tidak lengkap']);
            return;
        }

        if (strpos($marketplace, 'SHOPEE') !== false && (!$pickup_address_id || !$pickup_time_id)) {
            echo json_encode(['status' => false, 'message' => 'Alamat dan waktu pickup wajib untuk Shopee']);
            return;
        }
        if (strpos($marketplace, 'TIKTOK') !== false) {
            if (!$tiktok_pickup_start_time || !$tiktok_pickup_end_time) {
                echo json_encode(['status' => false, 'message' => 'Waktu pickup TikTok wajib dipilih']);
                return;
            }
            if (!ctype_digit((string)$tiktok_pickup_start_time) || !ctype_digit((string)$tiktok_pickup_end_time)) {
                echo json_encode(['status' => false, 'message' => 'Format waktu pickup TikTok tidak valid']);
                return;
            }
        }

        $id = explode(',', $id_selected);
        
        $rowsQuery = $this->db->select('id, order_id, marketplace, shop_id, shop_name, shipping')
            ->from('transaction')
            ->where_in('id', $id)
            ->where('is_manual', 0)
            ->where('is_for_booking', 0)
            ->where('shop_id', $shop_id)
            ->where('order_status', 'READY_TO_SHIP');

        if ($shipping_method) {
            $rowsQuery->where('shipping', $shipping_method);
        }

        $rows = $rowsQuery->get()->result_array();

        if (empty($rows)) {
            echo json_encode(['status' => false, 'message' => 'Order tidak ditemukan']);
            return;
        }

        $shippingMethods = array_unique(array_filter(array_map(function($row) {
            return trim($row['shipping'] ?? '');
        }, $rows)));

        if (!$shipping_method && $marketplace !== 'LAZADA') {
            if (count($shippingMethods) > 1) {
                echo json_encode(['status' => false, 'message' => 'Pilih kurir yang akan diproses']);
                return;
            }
            $shipping_method = $shippingMethods[0] ?? '';
        }

        $id = array_column($rows, 'id');

        $shopGroups = [];
        foreach ($rows as $row) {
            $shopId = $row['shop_id'];
            $marketplaceItem = strtoupper($row['marketplace']);
            $shopName = $row['shop_name'] ?? 'Shop ' . $shopId;
            
            if (!isset($shopGroups[$shopId])) {
                $shopGroups[$shopId] = [
                    'marketplace' => $marketplaceItem,
                    'shop_name' => $shopName,
                    'order_ids' => []
                ];
            }
            $shopGroups[$shopId]['order_ids'][] = $row['order_id'];
        }

        $allResults = [];
        $successCount = 0;
        $errorCount = 0;
        $notes = [];

        foreach ($shopGroups as $shopId => $group) {
            if ($shop_id && $shopId != $shop_id) {
                continue;
            }

            $payload = [
                'shop_id' => $shopId,
                'transaction_ids' => implode(',', $group['order_ids'])
            ];

            if (strpos($group['marketplace'], 'SHOPEE') !== false) {
                $payload['pickup_address_id'] = $pickup_address_id;
                $payload['pickup_time_id'] = $pickup_time_id;
            } elseif (strpos($group['marketplace'], 'TIKTOK') !== false) {
                $payload['pickup_start_time'] = $tiktok_pickup_start_time;
                $payload['pickup_end_time'] = $tiktok_pickup_end_time;
            }

            $endpoint = '';
            if (strpos($group['marketplace'], 'TIKTOK') !== false) {
                $endpoint = 'api/tts_ship_packages_bulk';
            } elseif (strpos($group['marketplace'], 'SHOPEE') !== false) {
                $endpoint = 'api/shopee_mass_ship_order_bulk';
            } elseif (strpos($group['marketplace'], 'LAZADA') !== false) {
                $endpoint = 'api/lazada_ship_order';
            } else {
                $notes[] = "Toko {$group['shop_name']}: Marketplace {$group['marketplace']} belum didukung";
                $errorCount++;
                continue;
            }

            // Atur pengiriman Shopee dijalankan langsung lewat Shopee API,
            // tidak lagi lewat endpoint.bhskin.co.id yang tidak mengenali
            // toko Montera. TikTok dan Lazada tetap lewat jalur lama.
            if (strpos($group['marketplace'], 'SHOPEE') !== false) {
                $response = $this->shopee_kirim_lokal(
                    $shopId, $group['order_ids'],
                    $pickup_address_id ?? NULL, $pickup_time_id ?? NULL);
            } else {
                $response = $this->call_marketplace_api($endpoint, $payload);
            }

            if (!empty($response['status'])) {
                $response = $this->prepareDocumentPrintResponse($response, $group['marketplace']);
            }
            
            if ($response['status']) {
                $successCount++;
                $notes[] = "✅ Toko {$group['shop_name']}: Berhasil memproses " . count($group['order_ids']) . " order";
            } else {
                $errorCount++;
                $notes[] = "❌ Toko {$group['shop_name']}: Gagal - " . ($response['message'] ?? 'Unknown error');
            }

            $allResults[] = [
                'shop_id' => $shopId,
                'shop_name' => $group['shop_name'],
                'marketplace' => $group['marketplace'],
                'status' => $response['status'],
                'message' => $response['message'] ?? '',
                'order_count' => count($group['order_ids'])
            ];
        }

        $totalShopsToProcess = $shop_id ? 1 : count($shopGroups);
        $remainingShops = count($shopGroups) - ($shop_id ? 1 : 0);

        $finalResponse = [
            'status' => $successCount > 0,
            'message' => $successCount > 0 ? 
                "Berhasil memproses {$successCount} toko" : 
                "Gagal memproses toko",
            'summary' => [
                'total_shops' => count($shopGroups),
                'success' => $successCount,
                'failed' => $errorCount,
                'remaining_shops' => $remainingShops,
                'processed_shop' => $shop_id
            ],
            'notes' => $notes,
            'results' => $allResults
        ];

        $finalResponse = $this->appendBulkResultAlert($finalResponse, 'ship_orders');
        
        echo json_encode($finalResponse);
    }

    public function shipping_documents()
    {
        $id_selected = $_POST['id_selected'] ?? '';
        if (!$id_selected) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada data yang dipilih']);
            return;
        }

        $data['id_selected'] = $id_selected;
        
        $this->load->view("transaction/shipping_documents", $data);
    }

    public function shipping_documents_execute()
    {
        $id_selected = $_POST['id_selected'] ?? '';
        
        if (!$id_selected) {
            echo json_encode(['status' => false, 'message' => 'Data tidak lengkap']);
            return;
        }

        $id = explode(',', $id_selected);

        // Ambil semua order yang dipilih (termasuk manual) untuk deteksi skip
        $allRows = $this->db->select('id, order_id, marketplace, shop_id, is_manual, pesanan')
            ->from('transaction')
            ->where_in('id', $id)
            ->get()
            ->result_array();

        // Urutkan per isi pesanan supaya halaman label yang keluar
        // dikelompokkan per produk. Anak packing jadi menyiapkan satu
        // jenis barang sekaligus, bukan bolak-balik ganti barang tiap
        // resi. Penanda diambil dari kolom pesanan yang berisi JSON
        // item; pesanan berisi lebih dari satu jenis barang otomatis
        // punya penanda sendiri dan berkumpul di kelompoknya.
        usort($allRows, function ($a, $b) {
            $kunci = function ($row) {
                $isi = json_decode($row['pesanan'] ?? '', TRUE);
                if (!is_array($isi) || empty($isi)) return 'zzz';
                $bagian = [];
                foreach ($isi as $it) {
                    // model_sku yang terisi (mis. 1LS, 2MS), sementara
                    // item_sku hampir selalu kosong di data Shopee.
                    // Tanpa ini pengelompokan label tidak membedakan apa pun.
                    $kode = !empty($it['model_sku']) ? $it['model_sku']
                          : (!empty($it['item_sku']) ? $it['item_sku']
                          : ($it['model_name'] ?? '?'));
                    $bagian[] = $kode . 'x' . ($it['qty'] ?? 1);
                }
                sort($bagian);
                return implode('+', $bagian);
            };
            $ka = $kunci($a);
            $kb = $kunci($b);
            return $ka === $kb
                ? strcmp($a['order_id'], $b['order_id'])
                : strcmp($ka, $kb);
        });

        if (empty($allRows)) {
            echo json_encode(['status' => false, 'message' => 'Order tidak ditemukan']);
            return;
        }

        $rows = [];
        $skippedManual = 0;
        foreach ($allRows as $r) {
            if ($r['is_manual']) {
                $skippedManual++;
            } else {
                $rows[] = $r;
            }
        }

        if (empty($rows)) {
            $msg = $skippedManual > 0
                ? "Semua order yang dipilih adalah order manual dan tidak bisa diproses dokumen pengiriman."
                : 'Order tidak ditemukan';
            echo json_encode(['status' => false, 'message' => $msg]);
            return;
        }

        $shopGroups = [];

        foreach ($rows as $row) {
            $shopId = $row['shop_id'];
            $marketplace = strtoupper($row['marketplace']);
            
            if (!isset($shopGroups[$shopId])) {
                $shopGroups[$shopId] = [
                    'marketplace' => $marketplace,
                    'order_ids' => []
                ];
            }
            $shopGroups[$shopId]['order_ids'][] = $row['order_id'];

        }

        $allResults = [];
        $successCount = 0;
        $errorCount = 0;
        $notes = [];
        $printPayload = [];
        $shopeePrintPayload = [];
        $downloadLinks = [];

        foreach ($shopGroups as $shopId => $group) {
            $marketplace = $group['marketplace'];
            $payload = [
                'transaction_ids' => implode(',', $group['order_ids'])
            ];
            $endpoint = '';

            if (strpos($marketplace, 'TIKTOK') !== false) {
                $endpoint = 'api/tts_get_shipping_documents_bulk';
            } elseif (strpos($marketplace, 'SHOPEE') !== false) {
                $payload['shop_id'] = $shopId;
                $payload['batch_size'] = 20;
                $endpoint = 'api/shopee_get_shipping_document_bulk';
            } elseif (strpos($marketplace, 'LAZADA') !== false) {
                $payload['shop_id'] = $shopId;
                $endpoint = 'api/lazada_get_shipping_document';
            } else {
                $notes[] = "❌ Toko {$shopId}: Marketplace {$marketplace} belum didukung";
                $errorCount++;
                continue;
            }

            // Label Shopee diambil langsung dari Shopee, tidak lagi lewat
            // endpoint.bhskin.co.id. Server itu milik pihak lain dan tidak
            // mengenali toko Montera, sehingga cetak label tidak pernah
            // berhasil. TikTok dan Lazada tetap lewat jalur lama.
            if (strpos($marketplace, 'SHOPEE') !== false) {
                $response = $this->shopee_api_lokal($shopId, $group['order_ids']);
            } else {
                $response = $this->call_marketplace_api($endpoint, $payload);
            }

            if (!empty($response['status'])) {
                $response = $this->prepareDocumentPrintResponse($response, $marketplace);
            }

            if (!empty($response['print_payload']) && is_array($response['print_payload'])) {
                $printPayload = array_merge($printPayload, $response['print_payload']);
            }

            if (!empty($response['shopee_print_payload']) && is_array($response['shopee_print_payload'])) {
                $shopeePrintPayload = array_merge($shopeePrintPayload, $response['shopee_print_payload']);
            }

            if (!empty($response['download_links']) && is_array($response['download_links'])) {
                $downloadLinks = array_merge($downloadLinks, $response['download_links']);
            }

            $successDownload = (int)($response['summary']['success_download'] ?? 0);
            if (!empty($response['status']) && $successDownload > 0) {
                $successCount++;
                $notes[] = "✅ Toko {$shopId}: Berhasil generate dokumen untuk {$successDownload} dari " . count($group['order_ids']) . " order";
            } elseif (!empty($response['status'])) {
                $errorCount++;
                $notes[] = "⚠️ Toko {$shopId}: API berhasil dipanggil tapi 0 dokumen berhasil di-generate dari " . count($group['order_ids']) . " order";
            } else {
                $errorCount++;
                $notes[] = "❌ Toko {$shopId}: Gagal - " . ($response['message'] ?? 'Unknown error');
            }

            $allResults[] = [
                'shop_id' => $shopId,
                'marketplace' => $marketplace,
                'status' => !empty($response['status']),
                'message' => $response['message'] ?? '',
                'order_count' => count($group['order_ids']),
            ];
        }

        if ($skippedManual > 0) {
            $notes[] = "⚠️ {$skippedManual} order manual dilewati (tidak bisa diproses dokumen pengiriman)";
        }

        $finalResponse = [
            'status' => $successCount > 0,
            'message' => $successCount > 0 ?
                "Berhasil memproses dokumen" :
                "Gagal memproses dokumen",
            'summary' => [
                'total_shops' => count($shopGroups),
                'success' => $successCount,
                'failed' => $errorCount,
                'skipped_manual' => $skippedManual,
            ],
            'notes' => $notes
        ];

        if (!empty($printPayload)) {
            $finalResponse['open_print'] = true;
            $finalResponse['print_payload'] = $printPayload;
        }

        if (!empty($shopeePrintPayload)) {
            $finalResponse['open_print_shopee'] = true;
            $finalResponse['shopee_print_payload'] = $shopeePrintPayload;
        }

        if (!empty($downloadLinks)) {
            $finalResponse['download_links'] = $downloadLinks;
        }

        $finalResponse = $this->appendBulkResultAlert($finalResponse, 'ship_documents');
        
        echo json_encode($finalResponse);
    }

    /**
     * Ambil label Shopee memakai library sendiri.
     *
     * Urutan $order_ids sudah dikelompokkan per produk di
     * shipping_documents_execute, dan Shopee menyusun halaman PDF
     * mengikuti urutan permintaan. Jadi labelnya keluar sudah terkumpul
     * per barang tanpa perlu diproses ulang.
     */
    /**
     * Atur pengiriman Shopee lewat API sendiri.
     *
     * Berbeda dengan cetak label yang hanya mengambil berkas, ini
     * tindakan nyata: memesan penjemputan ke kurir. Karena itu hasilnya
     * dilaporkan apa adanya — berapa berhasil, berapa gagal, dan
     * alasannya — supaya tidak ada pesanan yang dikira sudah diproses
     * padahal belum.
     */
    private function shopee_kirim_lokal($shopId, array $order_ids,
                                        $address_id = NULL, $pickup_time_id = NULL): array
    {
        set_time_limit(600);
        ignore_user_abort(true);

        $this->load->library('Shopee_api');

        $hasil = $this->shopee_api->atur_pengiriman_banyak(
            $shopId, $order_ids, $address_id, $pickup_time_id);

        $ok = count($hasil['berhasil']);
        $ng = count($hasil['gagal']);

        if ($ok === 0) {
            $contoh = array_slice($hasil['gagal'], 0, 3);
            $pesan = [];
            foreach ($contoh as $sn => $sebab) {
                $pesan[] = $sn . ': ' . $sebab;
            }
            return [
                'status'  => false,
                'message' => 'Tidak ada pesanan yang berhasil diproses. '
                           . implode(' | ', $pesan),
            ];
        }

        $pesan = "Berhasil memproses {$ok} pesanan";
        if ($ng > 0) {
            $pesan .= ", {$ng} gagal";
        }

        return [
            'status'  => true,
            'message' => $pesan,
            'summary' => [
                'berhasil' => $ok,
                'gagal'    => $ng,
                'catatan'  => array_slice($hasil['gagal'], 0, 5, true),
            ],
        ];
    }

    private function shopee_api_lokal($shopId, array $order_ids): array
    {
        // Batas waktu PHP 30 detik terlalu pendek: tiap pesanan butuh satu
        // panggilan untuk nomor resi, lalu Shopee perlu waktu menyiapkan
        // PDF-nya. Batasnya dinaikkan hanya untuk permintaan ini, tidak
        // mengubah pengaturan PHP secara umum.
        set_time_limit(300);
        ignore_user_abort(true);

        $this->load->library('Shopee_api');

        $catatan = [];
        $pdf = $this->shopee_api->ambil_label($shopId, $order_ids, $catatan);

        if (!$pdf) {
            // Sebab penolakan dari Shopee dicatat utuh. Pesan yang sampai
            // ke layar sudah diringkas, sehingga alasan sebenarnya hilang
            // dan setiap kegagalan hanya terbaca "gagal memproses".
            log_message('error', 'LABEL SHOPEE GAGAL | shop=' . $shopId
                . ' | jml=' . count($order_ids)
                . ' | galat=' . $this->shopee_api->galat()
                . ' | catatan=' . implode(' ;; ', array_slice($catatan, 0, 10)));

            return [
                'status'  => false,
                'message' => $this->shopee_api->galat()
                           . ($catatan ? ' | ' . implode('; ', array_slice($catatan, 0, 3)) : ''),
            ];
        }

        $dir = FCPATH . 'uploads/shipping_labels/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $nama = 'label-' . $shopId . '-' . date('Ymd-His') . '-'
              . substr(md5(implode(',', $order_ids)), 0, 6) . '.pdf';
        file_put_contents($dir . $nama, $pdf);
        $url = base_url('uploads/shipping_labels/' . $nama);

        $waktu_cetak = date('Y-m-d H:i:s');

        foreach ($order_ids as $oid) {
            $this->db->insert('transaction_shipping_doc_logs', [
                'order_id'     => $oid,
                'document_url' => $url,
                'marketplace'  => 'SHOPEE',
                'created_at'   => $waktu_cetak,
                'created_by'   => (int)($this->session->userdata('id') ?? 0),
            ]);
        }

        // tanda-cetak-lokal
        // Penanda pada barisnya sendiri, supaya bisa disaring "sudah"
        // atau "belum dicetak" tanpa menggabung ke tabel riwayat.
        // Ditulis di sini karena inilah titik label benar-benar jadi.
        if (!empty($order_ids)) {
            $in = implode(',', array_map(function ($o) {
                return $this->db->escape($o);
            }, $order_ids));
            $this->db->query(
                "UPDATE transaction SET print_at = " . $this->db->escape($waktu_cetak) .
                " WHERE order_id IN ($in)"
            );
        }

        return [
            'status'         => true,
            'message'        => 'Label siap',
            'download_links' => [$url],
            'open_print'     => true,
            'summary'        => [
                'success_download' => count($order_ids) - count($catatan),
                'dilewati'         => count($catatan),
                'catatan'          => array_slice($catatan, 0, 5),
            ],
        ];
    }

    private function call_marketplace_api(string $endpoint, array $payload): array
    {
        $url = rtrim($this->template->endpoint_url(), '/') . '/' . ltrim($endpoint, '/');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 120,
        ]);

        $rawResponse = curl_exec($ch);
        $curlError   = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'status'  => false,
                'message' => 'CURL Error: ' . $curlError,
            ];
        }
        $decoded = json_decode($rawResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status'  => false,
                'message' => 'Respon API tidak valid',
                'raw'     => $rawResponse,
            ];
        }

        return $decoded;
    }

    private function appendBulkResultAlert(array $response, string $context): array
    {
        if (empty($response['status'])) {
            return $response;
        }

        $summary = isset($response['summary']) && is_array($response['summary']) ? $response['summary'] : [];
        $results = isset($response['results']) && is_array($response['results']) ? $response['results'] : [];

        if ($context === 'ship_orders') {
            $counts = $this->resolveBulkCounts($summary, $results, ['success'], ['failed'], ['success']);
            $title = 'Ship bulk order selesai';
        } else {
            $counts = $this->resolveBulkCounts($summary, $results, ['success_download', 'success'], ['failed'], ['success']);
            $title = 'Download dokumen selesai';
        }

        if ($counts === null) {
            return $response;
        }

        [$successCount, $failedCount] = $counts;
        // $message = sprintf(
        //     '%s. Total sukses: %d | total gagal: %d. Tunggu beberapa saat sampai semua data terupdate.',
        //     $title,
        //     (int)$successCount,
        //     (int)$failedCount
        // );

        $response['message'] = $message;
        $response['alert_html'] = $this->template->alert_success($message);

        return $response;
    }

    private function resolveBulkCounts(array $summary, array $results, array $successKeys, array $failedKeys, array $successMarkers): ?array
    {
        $success = null;
        foreach ($successKeys as $key) {
            if (isset($summary[$key])) {
                $success = (int)$summary[$key];
                break;
            }
        }

        $failed = null;
        foreach ($failedKeys as $key) {
            if (isset($summary[$key])) {
                $failed = (int)$summary[$key];
                break;
            }
        }

        if ($success === null || $failed === null) {
            $counts = $this->countResultsByStatus($results, $successMarkers);
            if ($counts === null) {
                return null;
            }
            [$success, $failed] = $counts;
        }

        return [$success, $failed];
    }

    private function countResultsByStatus(array $results, array $successMarkers): ?array
    {
        if (empty($results)) {
            return null;
        }

        $successCount = 0;
        $failedCount = 0;
        $hasStatus = false;
        $normalizedMarkers = array_map('strtolower', array_filter($successMarkers, 'is_string'));

        foreach ($results as $row) {
            if (!array_key_exists('status', $row)) {
                continue;
            }

            $hasStatus = true;
            $status = $row['status'];
            $isSuccess = false;

            if ($status === true || $status === 1) {
                $isSuccess = true;
            } elseif (is_string($status)) {
                $isSuccess = in_array(strtolower($status), $normalizedMarkers, true);
            }

            if ($isSuccess) {
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        if (!$hasStatus) {
            return null;
        }

        return [$successCount, $failedCount];
    }

    private function prepareDocumentPrintResponse(array $response, string $marketplace): array
    {
        $results = $response['results'] ?? [];
        if (!is_array($results) || empty($results)) {
            return $response;
        }

        $downloadLinks = $response['download_links'] ?? [];

        if (stripos($marketplace, 'TIKTOK') !== false || stripos($marketplace, 'LAZADA') !== false) {
            $printPayload = [];
            foreach ($results as $row) {
                $docUrl = $row['document_urls']['doc_url'] ?? ($row['label_images'][0] ?? '');
                if (empty($docUrl) || empty($row['transaction_id'])) {
                    continue;
                }

                $printPayload[] = [
                    'transaction_id' => $row['transaction_id'],
                    'document_urls'  => ['doc_url' => $docUrl],
                ];

                if (!empty($row['file_path'])) {
                    $fileUrl = $this->buildFileUrl($row['file_path']);
                    if ($fileUrl) {
                        $downloadLinks[] = $fileUrl;
                    }
                }

                if (!empty($row['download_links']) && is_array($row['download_links'])) {
                    $downloadLinks = array_merge($downloadLinks, $row['download_links']);
                }
            }

            if (!empty($printPayload)) {
                $response['open_print']    = true;
                $response['print_payload'] = $printPayload;
            }

            return $response;
        }

        if (stripos($marketplace, 'SHOPEE') !== false) {
            $printPayload = [];
            foreach ($results as $row) {
                $status = strtolower((string)($row['status'] ?? ''));
                $filePath = $row['file_path'] ?? '';
                $labelImages = array_values(array_filter($row['label_images'] ?? []));

                // Skip hanya jika status jelas error DAN tidak ada gambar sama sekali
                if ($status === 'fail' || $status === 'error') {
                    continue;
                }

                // Butuh minimal salah satu: file_path atau label_images
                if (!$filePath && empty($labelImages)) {
                    continue;
                }

                $fileUrl = $filePath ? $this->buildFileUrl($filePath) : '';

                // Fallback file_url dari label_images[0] jika tidak ada file_path
                if (!$fileUrl && !empty($labelImages[0])) {
                    $img0 = $labelImages[0];
                    if (stripos($img0, 'http') === 0) {
                        $fileUrl = $img0;
                    } else {
                        $fileUrl = $this->buildFileUrl($img0);
                    }
                }

                $transactionId = $row['transaction_id'] ?? ($row['order_sn'] ?? '');
                $orderId       = $row['order_sn'] ?? ($row['transaction_id'] ?? '');

                if (empty($transactionId)) {
                    continue;
                }

                $entryLinks = [];
                if ($fileUrl) {
                    $entryLinks[] = $fileUrl;
                }

                $entry = [
                    'transaction_id' => $transactionId,
                    'order_id'       => $orderId,
                    'order_sn'       => $row['order_sn'] ?? '',
                    'package_number' => $row['package_number'] ?? '',
                    'shipping_method'=> $row['shipping_method'] ?? '',
                    'file_url'       => $fileUrl,
                    'file_path'      => $filePath,
                    'download_links' => $entryLinks,
                    'label_images'   => $labelImages,
                    'status'         => $status,
                ];

                $printPayload[] = $entry;
                if ($fileUrl) {
                    $downloadLinks[] = $fileUrl;
                }
            }

            if (!empty($printPayload)) {
                $response['open_print_shopee']   = true;
                $response['shopee_print_payload'] = $printPayload;
            }

        }

        if (!empty($downloadLinks)) {
            $response['download_links'] = array_values(array_unique($downloadLinks));
        }

        return $response;
    }

    private function buildFileUrl(string $path): string
    {
        if (!$path) {
            return '';
        }
        $clean = str_replace('\\', '/', $path);
        $clean = ltrim($clean, '/');
        $clean = preg_replace('#^\./#', '', $clean);
        if ($clean === '') {
            return '';
        }
        return base_url($clean);
    }

    private function hasShippingPrintHistoryTable(): bool
    {
        static $hasTable = null;
        if ($hasTable !== null) {
            return $hasTable;
        }
        $result = $this->db->query("SHOW TABLES LIKE 'transaction_shipping_print_history'");
        $hasTable = $result && $result->num_rows() > 0;
        return $hasTable;
    }

    private function saveShippingPrintHistory(string $payload, string $printUrl, string $marketplace, int $itemCount): void
    {
        if (!$this->hasShippingPrintHistoryTable()) {
            return;
        }

        $user = $this->session->userdata('user');
        $userId = $user['id'] ?? 0;

        $data = [
            'print_url' => $printUrl,
            'payload' => $payload,
            'marketplace' => $marketplace,
            'item_count' => $itemCount,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $userId,
        ];

        $payloadHash = sha1($payload);
        $exists = $this->db
            ->select('id')
            ->from('transaction_shipping_print_history')
            ->where('print_url', $printUrl)
            ->where('payload_hash', $payloadHash)
            ->limit(1)
            ->get()
            ->row_array();

        if (empty($exists)) {
            $data['payload_hash'] = $payloadHash;
            $this->db->insert('transaction_shipping_print_history', $data);
        }
    }

    private function getShippingPrintHistory(int $limit = 30): array
    {
        if (!$this->hasShippingPrintHistoryTable()) {
            return [];
        }

        $todayEnd = date('Y-m-d 23:59:59');
        $rows = $this->db
            ->select('id, print_url, marketplace, item_count, created_at, payload_hash')
            ->from('transaction_shipping_print_history')
            ->where('created_at <=', $todayEnd)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();

        $seen = [];
        $uniqueRows = [];
        foreach ($rows as $row) {
            $hash = $row['payload_hash'] ?? '';
            if ($hash === '') {
                $hash = sha1($row['print_url'] . '|' . ($row['created_at'] ?? ''));
            }
            if (isset($seen[$hash])) {
                continue;
            }
            $seen[$hash] = true;
            $uniqueRows[] = $row;
        }

        foreach ($uniqueRows as &$row) {
            $labelParts = [];
            if (!empty($row['created_at'])) {
                $labelParts[] = date('d M Y H:i', strtotime($row['created_at']));
            }
            if (!empty($row['marketplace'])) {
                $labelParts[] = $row['marketplace'];
            }
            if (!empty($row['item_count'])) {
                $labelParts[] = $row['item_count'] . ' order';
            }
            $row['label'] = $labelParts ? implode(' | ', $labelParts) : 'History Cetak';
        }
        unset($row);

        return $uniqueRows;
    }

    public function print_shipping_docs()
    {
        $payload = $this->input->post('payload');
        if (!$payload) {
            show_error('Payload kosong', 400);
            return;
        }

        $data_api = json_decode($payload, true);
        if (!is_array($data_api) || empty($data_api['results'])) {
            show_error('Payload tidak valid', 400);
            return;
        }

        $trxIds = [];
        foreach ($data_api['results'] as $r) {
            if (!empty($r['transaction_id'])) {
                $trxIds[] = $r['transaction_id'];
            }
        }
        $trxIds = array_unique($trxIds);

        $itemsByTrx = []; 
        if (!empty($trxIds)) {
            $rows = $this->db
                ->select('order_id, json')
                ->where_in('order_id', $trxIds)
                ->get('transaction')
                ->result_array();

            foreach ($rows as $row) {
                $decoded = json_decode($row['json'], true);
                
                if (is_array($decoded) && !empty($decoded)) {
                    $itemsByTrx[$row['order_id']] = $decoded;
                } else {
                    $itemsByTrx[$row['order_id']] = [];
                }
            }
        }

        foreach ($data_api['results'] as $r) {
            $transactionId = $r['transaction_id'];
            if (!isset($itemsByTrx[$transactionId])) {
                $itemsByTrx[$transactionId] = [];
            }
        }

        $rawPayload = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (empty($_POST['skip_history'])) {
            $this->saveShippingPrintHistory($rawPayload ?: '', 'transaction/print-shipping-docs', '', count($data_api['results']));
        }

        $data = [
            'results'     => $data_api['results'], 
            'itemsByTrx'  => $itemsByTrx,          
            'labelSize'   => $this->input->get('label') ?: 'auto'
        ];

        $this->load->view('print/print_shipping_docs', $data);
    }

    public function print_shipping_docs_shopee()
    {
        $payload = $this->input->post('payload');
        
        // OPTIMASI: Validasi cepat
        if (empty($payload)) {
            $this->output->set_status_header(400);
            echo 'Payload tidak ditemukan';
            return;
        }
    
        // OPTIMASI: Decode JSON lebih efisien
        if (is_string($payload)) {
            $decoded = json_decode($payload, true, 512, JSON_BIGINT_AS_STRING);
        } else {
        $decoded = $payload;
    }

        if (!is_array($decoded) || empty($decoded)) {
            $this->output->set_status_header(400);
            echo 'Payload tidak valid';
            return;
        }
    
        $rawPayload = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (empty($_POST['skip_history'])) {
            $this->saveShippingPrintHistory($rawPayload ?: '', 'transaction/print-shipping-docs-shopee', 'SHOPEE', count($decoded));
        }
    
        $labels = [];
        $orderIds = [];
        $orderIdMap = []; // OPTIMASI: Untuk deduplikasi cepat
    
        // OPTIMASI: Batasi jumlah maksimal
        $maxLabels = 500;
        if (count($decoded) > $maxLabels) {
            $decoded = array_slice($decoded, 0, $maxLabels);
        }
    
        // OPTIMASI: Loop dengan optimasi
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
    
            // OPTIMASI: Ambil transaction_id atau order_id
            $transactionId = $row['transaction_id'] ?? ($row['order_id'] ?? '');
            $orderId = $row['order_id'] ?? $transactionId;
            
            // Skip jika kosong atau duplikat
            if (empty($orderId) || isset($orderIdMap[$orderId])) {
                continue;
            }
    
            $orderIdMap[$orderId] = true;
            
            // OPTIMASI: Sanitize panjang
            $orderId = substr(trim($orderId), 0, 50);
            $transactionId = substr(trim($transactionId), 0, 50);
            
            // OPTIMASI: Process data dengan cara yang lebih efisien
            $processedRow = [
                'transaction_id' => $transactionId,
                'order_id' => $orderId,
                'label_images' => [],
                'download_links' => [],
            ];
    
            // Copy fields yang diperlukan
            $fieldsToCopy = [
                'shipping_method', 'file_url', 'file_path', 
                'file_name', 'download_group', 'message', 'status',
                'package_id', 'awb_number', 'pdf_source'
            ];
            
            foreach ($fieldsToCopy as $field) {
                if (isset($row[$field])) {
                    $processedRow[$field] = $row[$field];
                }
            }
    
            // OPTIMASI: Process label_images dengan deduplikasi
            $labelImages = $row['label_images'] ?? [];
            if (!empty($labelImages) && is_array($labelImages)) {
                $uniqueImages = [];
                foreach ($labelImages as $image) {
                    if (!empty($image)) {
                        $uniqueImages[$image] = true;
                    }
                }
                $processedRow['label_images'] = array_keys($uniqueImages);
            }
    
            // OPTIMASI: Process download_links dengan deduplikasi
            $downloadLinks = $row['download_links'] ?? [];
            if (!empty($downloadLinks) && is_array($downloadLinks)) {
                $uniqueLinks = [];
                foreach ($downloadLinks as $link) {
                    if (!empty($link)) {
                        $uniqueLinks[$link] = true;
                    }
                }
                $processedRow['download_links'] = array_keys($uniqueLinks);
            }
    
            // Tambahkan file_url ke download_links jika ada (hindari duplikat)
            if (!empty($processedRow['file_url']) && !in_array($processedRow['file_url'], $processedRow['download_links'], true)) {
                $processedRow['download_links'][] = $processedRow['file_url'];
            }
    
            $labels[] = $processedRow;
            $orderIds[] = $orderId;
        }
    
        if (empty($labels)) {
            $this->output->set_status_header(400);
            echo 'Data label tidak ditemukan';
            return;
        }
    
        $itemsByOrder = [];
        $orderMetaByOrder = [];
        
        // OPTIMASI: Query items dengan chunk
        if (!empty($orderIds)) {
            $chunkSize = 500;
            $orderChunks = array_chunk($orderIds, $chunkSize);
            
            foreach ($orderChunks as $chunk) {
                $rows = $this->db
                    ->select('order_id, json, awb_number, package_id')
                    ->where_in('order_id', $chunk)
                    ->get('transaction', count($chunk))
                    ->result_array();
    
                foreach ($rows as $row) {
                    $orderMetaByOrder[$row['order_id']] = [
                        'awb_number' => $row['awb_number'] ?? '',
                        'package_id' => $row['package_id'] ?? '',
                    ];

                    if (!empty($row['json'])) {
                        // OPTIMASI: Decode JSON dengan flag untuk performa
                        $items = json_decode($row['json'], true, 512, JSON_BIGINT_AS_STRING);
                        $itemsByOrder[$row['order_id']] = is_array($items) ? $items : [];
                    } else {
                        $itemsByOrder[$row['order_id']] = [];
                    }
                }
            }
        }
    
        // OPTIMASI: Fill missing order IDs
        foreach ($orderIds as $id) {
            if (!isset($itemsByOrder[$id])) {
                $itemsByOrder[$id] = [];
            }
            if (!isset($orderMetaByOrder[$id])) {
                $orderMetaByOrder[$id] = [
                    'awb_number' => '',
                    'package_id' => '',
                ];
            }
        }

        foreach ($labels as &$label) {
            $orderId = $label['order_id'] ?? '';
            $meta = $orderMetaByOrder[$orderId] ?? [];

            if (empty($label['awb_number']) && !empty($meta['awb_number'])) {
                $label['awb_number'] = $meta['awb_number'];
            }

            if (empty($label['package_id']) && !empty($meta['package_id'])) {
                $label['package_id'] = $meta['package_id'];
            }
        }
        unset($label);
    
        // OPTIMASI: Build download links dengan deduplikasi
        $downloadLinks = [];
        foreach ($labels as $row) {
            if (!empty($row['download_links'])) {
                foreach ($row['download_links'] as $link) {
                    $downloadLinks[$link] = true;
                }
            }
        }
        $downloadLinks = array_keys($downloadLinks);
    
        // tanda-cetak
        // Sebelumnya jalur ini hanya menampilkan label tanpa mencatat
        // apa pun, sehingga tidak ada cara mengetahui order mana yang
        // sudah dicetak. Penandanya ditulis di sini: waktu cetak pada
        // barisnya sendiri, dan satu baris riwayat per order.
        if (!empty($orderIds)) {
            $waktu = date('Y-m-d H:i:s');
            $oleh  = (int)($this->session->userdata('id') ?? 0);

            // Query ditulis langsung: pembangun query dipakai berkali-kali
            // di atas, dan sisa kondisinya bisa ikut terbawa sehingga
            // UPDATE tidak mengenai baris mana pun.
            $in = implode(',', array_map(function ($o) {
                return $this->db->escape($o);
            }, $orderIds));
            $this->db->query(
                "UPDATE transaction SET print_at = " . $this->db->escape($waktu) .
                " WHERE order_id IN ($in)"
            );

            $baris = [];
            foreach ($orderIds as $oid) {
                $baris[] = [
                    'order_id'     => $oid,
                    'document_url' => 'transaction/print-shipping-docs-shopee',
                    'marketplace'  => 'SHOPEE',
                    'created_at'   => $waktu,
                    'created_by'   => $oleh,
                ];
            }
            if ($baris) {
                $this->db->insert_batch('transaction_shipping_doc_logs', $baris);
            }
        }

        $data = [
            'labels'        => $labels,
            'itemsByOrder'  => $itemsByOrder,
            'downloadLinks' => $downloadLinks,
        ];
    
        // Load view
        $this->load->view('print/print_shipping_docs_shopee', $data);
    }

    public function print_history()
    {
        $id = $this->input->get('id');
        if (empty($id)) {
            show_error('ID history tidak ditemukan', 400);
            return;
        }

        if (!$this->hasShippingPrintHistoryTable()) {
            show_error('Tabel history cetak belum tersedia', 500);
            return;
        }

        $row = $this->db
            ->select('print_url, payload')
            ->from('transaction_shipping_print_history')
            ->where('id', $id)
            ->get()
            ->row_array();

        if (empty($row) || empty($row['print_url']) || empty($row['payload'])) {
            show_error('History cetak tidak ditemukan', 404);
            return;
        }

        $action = base_url($row['print_url']);
        $payload = htmlspecialchars($row['payload'], ENT_QUOTES, 'UTF-8');

        echo '<!doctype html><html><head><meta charset="utf-8"><title>Redirect</title></head><body>';
        echo '<form id="history-form" method="POST" action="' . $action . '">';
        echo '<input type="hidden" name="payload" value="' . $payload . '">';
        echo '<input type="hidden" name="skip_history" value="1">';
        echo '<noscript><button type="submit">Buka History Cetak</button></noscript>';
        echo '</form>';
        echo '<script>document.getElementById("history-form").submit();</script>';
        echo '</body></html>';
    }

    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("transaction/delete", $data);
    }

    public function delete()
    {
        $user = $_SESSION['user'];

        $id = $_POST['id'];

        // $data = $this->mymodel->selectWithQuery("SELECT customer FROM transaction WHERE id = '$id'");
        // $data = $data[0];

        $trx = $this->mymodel->selectDataOne('transaction', array('id' => $id));


        $dt['stock'] = json_decode($trx['json'], true);

        $id_trx = $id;
        if ($id_trx) {
            $this->db->delete('stock_product_3rd', " id_trx = '$id_trx'");
            $this->db->delete('stock', " id_trx = '$id_trx'");
        }


        foreach ($dt['stock'] as $k => $v) {
            $id_product = $v['product'];
            $this->update_stock($id_product);
        }



        if ($this->db->delete('transaction', array('id' => $id))) {

            if ($trx['customer'] > 0) {
                $this->customer_summary($trx['customer']);
            }

            // $msg = 'Hapus data berhasil!';
            // echo $this->template->alert_success($msg);
            redirect(base_url('/transaction'));
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }


    public function sync()
    {

        $data['store'] = $this->mymodel->selectWithQuery("SELECT shop_id as id, shop_name as opt, opt as marketplace FROM marketplace_config WHERE status = 'Aktif' AND LOWER(opt) <> 'meta' ORDER BY marketplace DESC, shop_name ASC");

        $this->load->view("transaction/sync", $data);
    }

    public function sync_process()
    {

        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $shop_id = $dt['shop_id'];
        $start_date = $dt['until_date'];
        $until_date = $dt['until_date'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->template->endpoint_url() . 'api/marketplace/order?marketplace=' . $marketplace . '&shop_id=' . $shop_id . '&start_date=' . $start_date . '&until_date=' . $until_date,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: ci_session=ioddoljecqot0p8sffikifou4ons0j40'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response, true);
        if ($response['status'] == true) {
            $msg = $response['msg'];
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = $response['msg'];
            echo $this->template->alert_danger($msg);
            die;
        }
    }

    public function import_resi()
    {

        $data['param'] = $this->template->get_param();

        $this->load->view("transaction/import_resi", $data);
    }


    public function import_pencairan()
    {

        $data['param'] = $this->template->get_param();

        $this->load->view("transaction/import_pencairan", $data);
    }


    public function import_customer()
    {

        $data = $_SESSION['filter'];

        $this->load->view("transaction/import_customer", $data);
    }

    public function import_customer_process()
    {
        $user = $_SESSION['user'];
        $start_date = $_POST['start_date'];
        $until_date = $_POST['until_date'];
        $marketplace = $_POST['marketplace'];

        $user = $_SESSION['user'];

        $type = ($_FILES['file']['name']);
        $type = substr($type, strrpos($type, '.') + 1);

        if ($type != 'xlsx') {
            $msg = 'Importing data tidak berhasil! Pastikan tipe file adalah xlsx!';
            echo $this->template->alert_danger($msg);
            die;
        }

        // $file = $this->request->getFile('file');

        $this->load->library('upload');

        // Set upload configuration
        $config['upload_path'] = './assets/webfile/excel/';  // Ensure this directory exists and is writable
        $config['allowed_types'] = 'xls|xlsx';
        // $config['max_size'] = 2048;  // 2MB
        $config['encrypt_name'] = TRUE;  // To avoid file name conflicts

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('file')) {
            // Upload failed
            $error = $this->upload->display_errors();
            $msg = $error;
            echo $this->template->alert_danger($msg);
            die;
        } else {
            // Upload success
            $upload_data = $this->upload->data();
            $filepath = $upload_data['full_path'];

            // You can store the file information in the database if needed
            $data = array(
                'uploaded_fileinfo' => $upload_data,
                // other data to be saved in the database
            );

            // Load the file helper to interact with the uploaded file
            $this->load->helper('file');

            // Example of handling the uploaded file
            // $file_content = read_file($filepath);
            // Do something with the file content if needed

            // Display or process the uploaded file information
            // echo "File uploaded successfully: " . $filepath;

        }

        if ($data['uploaded_fileinfo']['file_path']) {
            $filepath = $data['uploaded_fileinfo']['file_path'] . $data['uploaded_fileinfo']['file_name'];

            $qry = '';

            $reader  = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet  = $reader->load($filepath);
            $sheet_data  = $spreadsheet->getActiveSheet()->toArray();


            $column_index = array();
            if ($marketplace == "SHOPEE") {
                foreach ($sheet_data[0] as $k => $v) {
                    if ($v == 'No. Pesanan') {
                        $column_index['id_trx'] = $k;
                    }
                    if ($v == 'Nama Penerima') {
                        $column_index['customer_text'] = $k;
                    }
                    if ($v == 'No. Telepon') {
                        $column_index['phone'] = $k;
                    }
                    if ($v == 'Username (Pembeli)') {
                        $column_index['c_username'] = $k;
                    }
                    if ($v == 'Alamat Pengiriman') {
                        $column_index['address'] = $k;
                    }
                    if ($v == 'Kota/Kabupaten') {
                        $column_index['city_text'] = $k;
                    }
                    if ($v == 'Provinsi') {
                        $column_index['province_text'] = $k;
                    }
                }
            } else if ($marketplace == "LAZADA") {
                foreach ($sheet_data[0] as $k => $v) {
                    if ($v == 'orderNumber') {
                        $column_index['id_trx'] = $k;
                    }
                    if ($v == 'customerName') {
                        $column_index['customer_text'] = $k;
                    }
                    // if($v == 'customerName'){
                    //     $column_index['phone'] = $k;
                    // }
                    // if($v == 'customerName'){
                    //     $column_index['c_username'] = $k;
                    // }
                    // if($v == 'shippingAddresss'){
                    //     $column_index['address'] = $k;
                    // }
                    // if($v == 'Kota/Kabupaten'){
                    //     $column_index['city_text'] = $k;
                    // }
                    // if($v == 'Provinsi'){
                    //     $column_index['province_text'] = $k;
                    // }
                    if ($v == 'shippingProvider') {
                        $column_index['shipping'] = $k;
                    }
                    if ($v == 'trackingCode') {
                        $column_index['awb_number'] = $k;
                    }
                }
                // print_r($column_index);die;
            } else if ($marketplace == "TIKTOK") {
                foreach ($sheet_data[0] as $k => $v) {
                    if ($v == 'Order ID') {
                        $column_index['id_trx'] = $k;
                    }
                    if ($v == 'Buyer Username') {
                        $column_index['customer_text'] = $k;
                    }
                    if ($v == 'Phone #') {
                        $column_index['phone'] = $k;
                    }
                    if ($v == 'Recipient') {
                        $column_index['c_username'] = $k;
                    }
                    // if($v == 'Alamat Pengiriman'){
                    //     $column_index['address'] = $k;
                    // }
                    if ($v == 'Regency and City') {
                        $column_index['city_text'] = $k;
                    }
                    if ($v == 'Province') {
                        $column_index['province_text'] = $k;
                    }
                }
            } else {
                $msg = 'Importing data tidak berhasil! channel tidak ditemukan!';
                echo $this->template->alert_danger($msg);
                die;
            }
            if ($column_index['id_trx'] == '') {
                $msg = 'Importing data tidak berhasil! header excel tidak sesuai!';
                echo $this->template->alert_danger($msg);
                die;
            }
            foreach ($sheet_data as $k => $v) {
                if ($k > 0) {
                    $dt = array();
                    $i = 0;

                    $order_id = $v[$column_index['id_trx']];
                    $query = $this->mymodel->selectWithQuery("SELECT id,customer 
                    FROM transaction WHERE order_id = '$order_id' AND marketplace = '$marketplace'
                    LIMIT 1
                    ");

                    $query = $query[0];

                    $dt = array();
                    $dt['id'] = $query['id'];
                    foreach ($column_index as $k2 => $v2) {
                        $dt[$k2] = strval($v[$v2]);
                    }

                    if ($dt['id']) {
                        $dt['updated_at'] = DATE('Y-m-d H:i:s');
                        $this->db->update('transaction', $dt, array('id' => $dt['id']));

                        $dtt = array();
                        $dtt['updated_at'] = DATE('Y-m-d H:i:s');
                        if ($dt['customer_text']) {
                            $dtt['full_name'] = strval($dt['customer_text']);
                        }
                        if ($dt['phone']) {
                            $dtt['phone'] = strval($dt['phone']);
                        }
                        if ($dt['c_username']) {
                            $dtt['username'] = strval($dt['username']);
                        }
                        if ($dt['address']) {
                            $dtt['address'] = strval($dt['address']);
                        }
                        if ($dt['city_text']) {
                            $dtt['city_text'] = strval($dt['city_text']);
                        }
                        if ($dt['province_text']) {
                            $dtt['province_text'] = strval($dt['province_text']);
                        }
                        $this->db->update('customer', $dtt, array('id' => $query['customer']));
                    }
                }
            }

            // foreach ($data['product'] as $k => $v) {
            //     $id_product = $v['id'];
            //     $this->update_stock($id_product);
            // }

            $msg = 'Importing data berhasil!';
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = 'Importing data tidak berhasil!';
            echo $this->template->alert_danger($msg);
            die;
        }
    }

    public function import_pencairan_process()
    {
        $user = $_SESSION['user'];
        $start_date = $_POST['start_date'];
        $until_date = $_POST['until_date'];
        $marketplace = $_POST['marketplace'];

        $user = $_SESSION['user'];

        $type = ($_FILES['file']['name']);
        $type = substr($type, strrpos($type, '.') + 1);

        if ($type != 'xlsx') {
            $msg = 'Importing data tidak berhasil! Pastikan tipe file adalah xlsx!';
            echo $this->template->alert_danger($msg);
            die;
        }

        // $file = $this->request->getFile('file');

        $this->load->library('upload');

        // Set upload configuration
        $config['upload_path'] = './assets/webfile/excel/';  // Ensure this directory exists and is writable
        $config['allowed_types'] = 'xls|xlsx';
        // $config['max_size'] = 2048;  // 2MB
        $config['encrypt_name'] = TRUE;  // To avoid file name conflicts

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('file')) {
            // Upload failed
            $error = $this->upload->display_errors();
            $msg = $error;
            echo $this->template->alert_danger($msg);
            die;
        } else {
            // Upload success
            $upload_data = $this->upload->data();
            $filepath = $upload_data['full_path'];

            // You can store the file information in the database if needed
            $data = array(
                'uploaded_fileinfo' => $upload_data,
                // other data to be saved in the database
            );

            // Load the file helper to interact with the uploaded file
            $this->load->helper('file');

            // Example of handling the uploaded file
            // $file_content = read_file($filepath);
            // Do something with the file content if needed

            // Display or process the uploaded file information
            // echo "File uploaded successfully: " . $filepath;

        }

        if ($data['uploaded_fileinfo']['file_path']) {
            $filepath = $data['uploaded_fileinfo']['file_path'] . $data['uploaded_fileinfo']['file_name'];

            $qry = '';

            $reader  = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet  = $reader->load($filepath);
            $sheet_data  = $spreadsheet->getActiveSheet()->toArray();



            $column_index = array();
            if ($marketplace == "SHOPEE") {
                foreach ($sheet_data[17] as $k => $v) {
                    if (strpos($v, 'No. Pesanan') !== false) {
                        $column_index['order_id'] = $k;
                    }
                    if (strpos($v, 'Jumlah') !== false) {
                        $column_index['dana_pencairan'] = $k;
                    }
                }
                for ($i = 0; $i <= 17; $i++) {
                    unset($sheet_data[$i]);
                }
            } else if ($marketplace == "LAZADA") {
                foreach ($sheet_data[0] as $k => $v) {
                }
                // print_r($column_index);die;
            } else if ($marketplace == "TIKTOK") {
                foreach ($sheet_data[0] as $k => $v) {
                    if (strpos($v, 'Order/adjustment ID') !== false) {
                        $column_index['order_id'] = $k;
                    }
                    if (strpos($v, 'Total settlement amount') !== false) {
                        $column_index['dana_pencairan'] = $k;
                    }
                    if (strpos($v, 'Subtotal before discounts') !== false) {
                        $column_index['omset_kotor'] = $k;
                    }
                    if (strpos($v, 'Subtotal after seller discounts') !== false) {
                        $column_index['omset_bersih'] = $k;
                    }
                    if (strpos($v, 'Seller discounts') !== false) {
                        $column_index['diskon_penjual'] = abs($k);
                    }
                    if (strpos($v, 'TikTok Shop commission fee') !== false) {
                        $column_index['marketplace_fee'] = abs($k);
                    }
                    if (strpos($v, 'Customer payment') !== false) {
                        $column_index['customer_price'] = abs($k);
                    }
                }
            } else {
                $msg = 'Importing data tidak berhasil! channel tidak ditemukan!';
                echo $this->template->alert_danger($msg);
                die;
            }
            if ($column_index['order_id'] == '') {
                $msg = 'Importing data tidak berhasil! header excel tidak sesuai!';
                echo $this->template->alert_danger($msg);
                die;
            }
            $column_index_data = array();
            foreach ($column_index as $k => $v) {
                if ($k != 'order_id') {
                    $column_index_data[$k] = $v;
                }
            }
            foreach ($sheet_data as $k => $v) {
                if ($k > 0) {
                    $dt = array();
                    $i = 0;

                    $order_id = $v[$column_index['order_id']];

                    $query = $this->mymodel->selectWithQuery("SELECT id 
                    FROM transaction WHERE order_id = '$order_id' AND marketplace = '$marketplace'
                    LIMIT 1
                    ");

                    $query = $query[0];

                    $dt = array();
                    $dt['id'] = $query['id'];
                    foreach ($column_index_data as $k2 => $v2) {
                        $dt[$k2] = strval(abs(doubleval($v[$v2])));
                    }
                    $dt['pencairan_status'] = 'Settlement';



                    if ($dt['id']) {
                        $dt['updated_at'] = DATE('Y-m-d H:i:s');
                        $this->db->update('transaction', $dt, array('id' => $dt['id']));
                    }
                }
            }

            $msg = 'Importing data berhasil!';
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = 'Importing data tidak berhasil!';
            echo $this->template->alert_danger($msg);
            die;
        }
    }


    public function import()
    {

        $param = $_GET;

        $start_date = $_GET['start_date'];
        $until_date = $_GET['until_date'];

        if (empty($start_date)) {
            $start_date = DATE("Y-m-01");
            
        }
        if (empty($until_date)) {
            $until_date = DATE("Y-m-d");
        }
        $site = $_GET['site'];
        $qry = '';

        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];


        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];
        $id = $_GET['id'];
        $order_status = $_GET['order_status'];


        $filename = 'ORDER.';
        if ($marketplace) {
            $filename .= $marketplace . '.';
        }
        $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);

        $data['file_name'] = $filename;

        $data['param'] = $this->template->get_param();

        $this->load->view("transaction/import", $data);
    }

    public function import_check()
    {

        $data['title'] = 'Import Order - ' . $this->template->title();

        $user = $_SESSION['user'];
        $start_date = $_POST['start_date'];
        $until_date = $_POST['until_date'];

        $user = $_SESSION['user'];

        $type = ($_FILES['file']['name']);
        $type = substr($type, strrpos($type, '.') + 1);

        $this->load->library('upload');

        // Set upload configuration
        $config['upload_path'] = './assets/webfile/excel/';  // Ensure this directory exists and is writable
        $config['allowed_types'] = 'xls|xlsx';
        // $config['max_size'] = 2048;  // 2MB
        $config['encrypt_name'] = TRUE;  // To avoid file name conflicts

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('file')) {
            // Upload failed
            $error = $this->upload->display_errors();
            $msg = $error;
            echo $this->template->alert_danger($msg);
            die;
        } else {
            // Upload success
            $upload_data = $this->upload->data();
            $filepath = $upload_data['full_path'];

            // You can store the file information in the database if needed
            $data = array(
                'uploaded_fileinfo' => $upload_data,
                // other data to be saved in the database
            );

            // Load the file helper to interact with the uploaded file
            $this->load->helper('file');

            // Example of handling the uploaded file
            // $file_content = read_file($filepath);
            // Do something with the file content if needed

            // Display or process the uploaded file information
            // echo "File uploaded successfully: " . $filepath;

        }

        if ($data['uploaded_fileinfo']['file_path']) {
            $filepath = $data['uploaded_fileinfo']['file_path'] . $data['uploaded_fileinfo']['file_name'];


            $query = $this->mymodel->selectWithQuery("SELECT * FROM product
            -- ORDER BY brand ASC, sub_name ASC
            WHERE is_gift = 0 AND is_varian = 0
            ORDER BY id ASC
            ");

            $data['product'] = $query;


            $query = $this->mymodel->selectWithQuery("SELECT * FROM product
            -- ORDER BY brand ASC, sub_name ASC
            WHERE is_gift = 1 AND is_varian = 0
            ORDER BY id ASC
            ");

            $data['gift'] = $query;

            $data['header'] = array();

            $header_2 = array();

            foreach ($data['product'] as $k => $v) {
                $header_2[] = strtoupper($v['sub_name']);
            }

            $header_2_gift = array();

            foreach ($data['gift'] as $k => $v) {
                $header_2_gift[] = strtoupper($v['sub_name']);
            }


            $header_3 = array(
                "JENIS PEMBAYARAN",
                "TANGGAL TF",
                "EKSPEDISI",
                "ONGKIR",
                "DISKON",
                "TOTAL BAYAR",
                "ORDER STATUS",
            );

            $header_1 = array(
                "ID",
                "KEBUTUHAN",
                "TANGGAL",
                "NO RESI",
                "BRAND",
                "KET",
                "KODE CS",
                "CB/CL",
                "NAMA",
                "NO HP / NO WA HARUS DIAWALI 62",
                "USERNAME",
                "ALAMAT LENGKAP (SAP MAKS. JAM 3)",
                "KEC",
                "KAB",
                "PROV",
                "PESANAN",
            );


            $reader  = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet  = $reader->load($filepath);
            $data['data']  = $spreadsheet->getActiveSheet()->toArray();
            $data['filepath'] = $filepath;
            $data['param'] = $_POST['param'];

            $data['header_1'] = $header_1;
            $data['header_2'] = $header_2;
            $data['header_2_gift'] = $header_2_gift;
            $data['header_3'] = $header_3;

            $data['template'] = $this->template;

            $data['content'] = $this->load->view("transaction/import_check", $data, true);
            $this->load->view("TemplateDashboard", $data);
        } else {
            $msg = 'Import data tidak berhasil! Pastikan file excel sudah sesuai dengan template!';
            echo $this->template->alert_danger($msg);
            die;
        }
    }

    public function import_process()
    {

        // $msg = 'Fitur masih dalam proses pengembangan!';
        // echo $this->template->alert_danger($msg);
        // die;

        $user = $_SESSION['user'];
        $start_date = $_POST['start_date'];
        $until_date = $_POST['until_date'];

        $user = $_SESSION['user'];

        $type = ($_FILES['file']['name']);
        $type = substr($type, strrpos($type, '.') + 1);

        $dt = $_POST;

        $qry = '';
        $filepath = $dt['filepath'];
        $reader  = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet  = $reader->load($filepath);
        $sheet_data  = $spreadsheet->getActiveSheet()->toArray();

        if (empty($sheet_data)) {
            $msg = 'Importing data tidak berhasil! Pastikan tipe file adalah xlsx!';
            echo $this->template->alert_danger($msg);
            die;
        }

        if (1 == 1) {

            $query = $this->mymodel->selectWithQuery("SELECT * FROM product
            WHERE is_gift = 0 AND is_varian = 0
            ORDER BY id ASC
            ");

            $data['product'] = $query;


            $query = $this->mymodel->selectWithQuery("SELECT * FROM product
            WHERE is_gift = 1 AND is_varian = 0
            ORDER BY id ASC
            ");

            $data['gift'] = $query;


            $marketplace = $_POST['marketplace'];
            $marketplace = "MANUAL";

            if ($marketplace == "MANUAL") {
                $body_1 = array(
                    "id",
                    "c_type",
                    "date",
                    "awb_number",
                    "brand",
                    "marketplace",
                    "cs",
                    "cb_cl",
                    "customer_text",
                    "phone",
                    "c_username",
                    "address",
                    "subdistrict_text",
                    "city_text",
                    "province_text",
                    "pesanan",

                );

                $body_2 = array();

                foreach ($data['product'] as $k => $v) {
                    $body_2[] = $v['id'];
                }


                $body_2_gift = array();

                foreach ($data['gift'] as $k => $v) {
                    $body_2_gift[] = $v['id'];
                }

                $body_3  = array(
                    "payment_type",
                    "pay_at",
                    "shipping",
                    "ongkir",
                    "diskon_penjual",
                    "customer_price",
                    "order_status",
                );
            } else if ($marketplace == "MARKETPLACE") {
                die;
            } else {
                die;
            }

            foreach ($sheet_data as $k => $v) {
                if ($k > 0 && $v['2']) {
                    $dt = array();
                    $i = 0;
                    foreach ($body_1 as $k2 => $v2) {
                        $dt[$v2] = strval($v[$i]);
                        $i++;
                    }

                    foreach ($body_3 as $k2 => $v2) {
                        $dt[$v2] = strval($v[$i]);
                        $i++;
                    }

                    if ($dt['c_type'] == "AFFILIATE" || $dt['c_type'] == "ENDORSE" || $dt['c_type'] == "FREE") {
                        $dt['is_endorse'] = 1;
                    } else {
                        $dt['is_endorse'] = 0;
                    }

                    $json = array();
                    $pesanan = '';
                    $price_total = 0;
                    foreach ($body_2 as $k2 => $v2) {
                        if ($v[$i] > 0) {
                            $id_product = $v2;
                            $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$id_product' ");

                            $product = $query[0];
                            if ($dt['c_type'] == "Pelanggan") {
                                $product['price_normal'] = $product['price_normal'];
                            } else if ($dt['c_type'] == "Distributor") {
                                $product['price_normal'] = $product['price_distributor'];
                            } else if ($dt['c_type'] == "Reseller") {
                                $product['price_normal'] = $product['price_reseller'];
                            } else {
                                $product['price_normal'] = $product['price_normal'];
                            }
                            $json[$id_product]['product']      = $product['id'];
                            $json[$id_product]['sku']          = $product['sku'];
                            $json[$id_product]['product_text'] = $product['name'];
                            $json[$id_product]['brand']        = $product['brand'];
                            $json[$id_product]['brand_text']   = $product['brand_text'];
                            $json[$id_product]['qty']          = $v[$i];
                            $json[$id_product]['hpp']          = $product['price_buy'];
                            $json[$id_product]['price']        = $dt['is_endorse'] == 1 ? 0 : $product['price_normal'];
                            $json[$id_product]['price_total']  = $dt['is_endorse'] == 1 ? 0 : (doubleval($v[$i]) * doubleval($product['price_normal']));
                            $pesanan .= $v[$i] . ' ' . $product['name'];
                            $pesanan .= '<br>';
                            $price_total += $json[$id_product]['price_total'];
                        }
                        $i++;
                    }

                    foreach ($body_2_gift as $k2 => $v2) {
                        if ($v[$i] > 0) {
                            $id_product = $v2;
                            $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE id = '$id_product' ");

                            $product = $query[0];

                            if ($dt['c_type'] == "Pelanggan") {
                                $product['price_normal'] = $product['price_normal'];
                            } else if ($dt['c_type'] == "Distributor") {
                                $product['price_normal'] = $product['price_distributor'];
                            } else if ($dt['c_type'] == "Reseller") {
                                $product['price_normal'] = $product['price_reseller'];
                            } else {
                                $product['price_normal'] = $product['price_normal'];
                            }
                            $json[$id_product]['product'] = $product['id'];
                            $json[$id_product]['sku'] = $product['sku'];
                            $json[$id_product]['product_text'] = $product['name'];
                            $json[$id_product]['brand'] = $product['brand'];
                            $json[$id_product]['brand_text'] = $product['brand_text'];
                            $json[$id_product]['qty'] = $v[$i];
                            $json[$id_product]['hpp'] = $product['price_buy'];
                            $json[$id_product]['price'] = $product['price_normal'];
                            $json[$id_product]['price_total'] = doubleval($v[$i]) * doubleval($product['price_normal']);
                            $pesanan .= $v[$i] . ' ' . $product['name'];
                            $pesanan .= '<br>';
                            $price_total += $json[$id_product]['price_total'];
                        }
                        $i++;
                    }

                    $brand_selected = "MG";
                    $arr_brand = array();
                    foreach ($json as $k4 => $v4) {
                        $arr_brand[$v4['brand']] += 1;
                    }

                    $max = 0;
                    foreach ($arr_brand as $k => $v) {
                        if ($v >= $max) {
                            $max = $v;
                            $brand_selected = $k;
                        }
                    }

                    $dt['brand'] = $brand_selected;

                    $js = array();
                    $i = 0;
                    foreach ($json as $k4 => $v4) {
                        $js[$i]['qty'] += $v4['qty'];
                        $js[$i]['item_sku'] = $v4['sku'];
                        $js[$i]['item_name'] = $v4['product_text'];
                        $i++;
                    }

                    $dt['pesanan'] = json_encode($js, true);
                    $dt['json'] = json_encode($json, true);
                    $full_name = $dt['customer_text'];
                    $username = $dt['c_username'];
                    $phone = $dt['phone'];


                    $dt['date'] = DATE("Y-m-d H:i:s", strtotime($dt['date']));
                    if ($dt['date'] == '1970-01-01 07:00:00') {
                        $dt['date'] = DATE("Y-m-d 23:00:00");
                    }

                    $dt['kebutuhan'] = $dt['c_type'] ?? '';
                    $dt['c_type'] = ucfirst(strtolower((string)($dt['c_type'] ?? '')));

                    if ($dt['kebutuhan']) {
                        $dt['customer_price'] = 0;
                        $price_total = 0;
                    }

                    $dt['customer_price'] = $this->template->separator_number_only($dt['customer_price']);
                    $dt['ongkir'] = $this->template->separator_number_only($dt['ongkir']);

                    $dt['order_id'] = str_replace(' ', '', $dt['order_id']);
                    $dt['omset_kotor'] = $price_total;

                    $dt['diskon_penjual'] = abs(doubleval($dt['diskon_penjual']));
                    $dt['discount_nominal'] = abs(doubleval($dt['diskon_penjual']));
                    $dt['discount_type'] = "Nominal";

                    $dt['dibayar'] = $dt['customer_price'];

                    $dt['omset_bersih'] = $dt['customer_price'];

                    $dt['dana_pencairan'] = $dt['customer_price'];

                    $dt['pesanan_count'] = intval(count($js));

                    if ($dt['pay_at']) {
                        $dt['payment_status'] = "Paid";
                    } else {
                        $dt['payment_status'] = "Unpaid";
                    }

                    if ($dt['c_type'] == "Affiliate" || $dt['c_type'] == "Endorse" || $dt['c_type'] == "Free") {
                        $dt['pencairan_at'] = '';
                        $dt['pencairan_status'] = '';
                        $dt['is_disbursement'] = 0;
                        $dt['pay_at'] = "";
                        $dt['payment_type'] = '';
                        $dt['payment_status'] = '';
                        $dt['dana_pencairan'] = 0 - ($dt['ongkir']);
                    }

                    if (!in_array($dt['payment_type'], array("TF", "COD", ""))) {
                        $dt['bank'] = $dt['payment_type'];
                        $dt['payment_type'] = "TF";
                    }

                    if ($dt['payment_type'] == "TF" && $dt['payment_status'] == "Paid") {
                        $dt['pencairan_status'] = "Settlement";
                        $dt['pencairan_at'] = DATE("Y-m-d H:i:s");
                        $dt['is_disbursement'] = 1;
                        $dt['pay_at'] = DATE("Y-m-d H:i:s");
                        $dt['payment_status'] = "Paid";
                    }

                    if ($dt['id']) {
                        unset($dt['order_id']);
                        $dt['updated_at'] = DATE('Y-m-d H:i:s');
                        $this->db->update('transaction', $dt, array('id' => $dt['id']));
                    } else {
                        for (;;) {
                            $order_id = "BHS" . DATE("Ymdhis") . $this->template->generateNumber(3);
                            $check = $this->mymodel->selectWithQuery("SELECT id FROM transaction WHERE order_id = '$order_id' LIMIT 1");
                            if (empty($check)) {
                                $dt['order_id'] = $order_id;
                                break;
                            }
                        }
                        $dt['is_manual'] = 1;
                        $dt['created_at'] = DATE("Y-m-d H:i:s");
                        $dt['created_by'] = strval($user['id']);
                        $dt['type'] = 'Out';
                        $dt['type_sub'] = 'POS';
                        $dt['status'] = 'ENABLE';

                        unset($dt['id']);
                        $this->db->insert('transaction', $dt);
                        $dt['id'] = $this->db->insert_id();
                    }


                    $id = $dt['id'];

                    if ($id) {
                        $this->db->delete('stock', array('id_trx' => $id));

                        $this->db->delete('stock_product_3rd', array('id_trx' => $id));
                    }

                    $json = json_decode($dt['json'], true);

                    foreach ($json as $k2 => $v2) {
                        $dts = array();
                        $dts['brand'] = strval($v2['brand']);
                        $dts['id_trx'] = strval($id);
                        $dts['qty'] = 0 - abs($v2['qty']);
                        $dts['qty_out'] = 0;
                        $dts['qty_out_pos'] = abs($v2['qty']);
                        $dts['qty_in'] = '0';
                        $dts['price'] = $v2['price'];
                        $dts['hpp'] = doubleval($v2['hpp']);
                        $dts['price_total'] = $v2['price_total'];
                        $dts['product'] = $v2['product'];
                        $dts['product_text'] = $v2['product_text'];
                        $dts['sku'] = $v2['sku'];
                        $dts['order_id'] = $dt['order_id'];
                        $dts['type'] = "Out";
                        $dts['type_sub'] = "POS";
                        $dts['created_at'] = DATE("Y-m-d H:i:s");
                        $dts['date'] = $dt['date'];
                        $dts['created_by'] = strval($user['id']);
                        $dts['status'] = "Aktif";
                        $this->db->insert('stock', $dts);
                        if (in_array($dt['order_status'], array('RETURN'))) {
                            $dts['type'] = "In";
                            $dts['qty'] =  abs($v2['qty']);
                            $dts['qty_out'] = '0';
                            $dts['qty_out_pos'] = '0';
                            $dts['qty_in'] = '0';
                            $dts['qty_in_pos'] =  abs($v2['qty']);
                            $this->db->insert('stock', $dts);
                        }
                    }


                }
            }

            foreach ($data['product'] as $k => $v) {
                $id_product = $v['id'];
                $this->update_stock($id_product);
            }

            $msg = 'Importing data berhasil!';
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = 'Importing data tidak berhasil!';
            echo $this->template->alert_danger($msg);
            die;
        }
    }

    public function import_resi_process()
    {

        // $msg = 'Fitur masih dalam proses pengembangan!';
        // echo $this->template->alert_danger($msg);
        // die;

        $user = $_SESSION['user'];
        $start_date = $_POST['start_date'];
        $until_date = $_POST['until_date'];

        $user = $_SESSION['user'];

        $type = ($_FILES['file']['name']);
        $type = substr($type, strrpos($type, '.') + 1);

        $dt = $_POST;

        // if ($type != 'xlsx') {
        //     $msg = 'Importing data tidak berhasil! Pastikan tipe file adalah xlsx!';
        //     echo $this->template->alert_danger($msg);
        //     die;
        // }

        $this->load->library('upload');

        // Set upload configuration
        $config['upload_path'] = './assets/webfile/excel/';  // Ensure this directory exists and is writable
        $config['allowed_types'] = 'xls|xlsx';
        // $config['max_size'] = 2048;  // 2MB
        $config['encrypt_name'] = TRUE;  // To avoid file name conflicts

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('file')) {
            // Upload failed
            $error = $this->upload->display_errors();
            $msg = $error;
            echo $this->template->alert_danger($msg);
            die;
        } else {
            // Upload success
            $upload_data = $this->upload->data();
            $filepath = $upload_data['full_path'];

            // You can store the file information in the database if needed
            $data = array(
                'uploaded_fileinfo' => $upload_data,
                // other data to be saved in the database
            );

            // Load the file helper to interact with the uploaded file
            $this->load->helper('file');

            // Example of handling the uploaded file
            // $file_content = read_file($filepath);
            // Do something with the file content if needed

            // Display or process the uploaded file information
            // echo "File uploaded successfully: " . $filepath;

        }

        if ($data['uploaded_fileinfo']['file_path']) {

            $qry = '';

            $filepath = $data['uploaded_fileinfo']['file_path'] . $data['uploaded_fileinfo']['file_name'];


            $reader  = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet  = $reader->load($filepath);
            $sheet_data  = $spreadsheet->getActiveSheet()->toArray();

            $body_1 = array(
                "order_id",
                "awb_number",

            );


            foreach ($sheet_data as $k => $v) {
                if ($k > 0 && $v['1']) {
                    $dt = array();
                    $i = 0;

                    $dt['order_id'] = $v['0'];
                    $dt['order_id'] = str_replace(' ', '', $dt['order_id']);
                    $dt['awb_number'] = $v['1'];

                    $dt['updated_at'] = DATE('Y-m-d H:i:s');

                    $this->db->update('transaction', $dt, array('order_id' => $dt['order_id']));
                    // echo "UPDATE transaction SET awb_number = '".$dt['awb_number']."' WHERE order_id = '".$dt['order_id']."';";
                    // echo "<br>";
                }
            }
            $msg = 'Importing data berhasil!';
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = 'Importing data tidak berhasil!';
            echo $this->template->alert_danger($msg);
            die;
        }
    }

    public function download_template()
    {


        $start_date = $_GET['start_date'];
        $start_date = $_GET['start_date'];
        $p = $_GET['p'];

        if (empty($start_date)) {
            $start_date = DATE("Y-m-01");
            
        }
        if (empty($until_date)) {
            $until_date = DATE("Y-m-d");
        }

        $site = $_GET['site'];
        $qry = '';

        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];


        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];
        $keyword_category = $_GET['keyword_category'];
        $id = $_GET['id'];
        $order_status = $_GET['order_status'];


        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        if ($id) {
            $qry .= " AND customer = '$id' ";
        }

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }


        $query = $this->mymodel->selectWithQuery("SELECT id,code as opt FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

        $data['brands'] = $query;


        if ($brand == "LAINNYA") {
            $ids = "";
            foreach ($data['brands'] as $k => $v) {
                $ids .= "'" . $v['opt'] . "',";
            }
            $ids = substr($ids, 0, -1);
            $qry .= " AND brand NOT IN ($ids) ";
        } else {
            if ($brand) {
                $qry .= " AND brand = '$brand' ";
            }
        }


        $ekspedisi = $_GET['ekspedisi'];
        if ($ekspedisi) {
            $qry .= " AND shipping LIKE '%$ekspedisi%' ";
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
                $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') AND customer_price > 0";
            } else if ($order_status == "SETTLEMENT") {
                $qry .= " AND pencairan_status = 'Settlement' ";
            } else if ($order_status == "CANCELLED") {
                $qry .= " AND order_status IN ('CANCELLED','IN_CANCEL') ";
            } else {
                $qry .= " AND order_status = '$order_status' ";
            }
        }

        $pencairan = $_GET['pencairan'];
        if ($pencairan) {
            if ($pencairan == "Sudah Pencairan") {
                $qry .= " AND dana_pencairan > 0";
            } else if ($pencairan == "Belum Pencairan") {
                $qry .= " AND order_status IN ('PROCESSED','SHIPPED','COMPLETED', 'READY_TO_SHIP', 'DELIVERED') AND c_type NOT IN ('Affiliate','Endorse','Free') AND dana_pencairan = 0 AND is_disbursement = 0 ";
            }
        }

        $payment_type = $_GET['payment_type'];
        if ($payment_type) {
            $qry .= " AND payment_type = '$payment_type' ";
        }

        if ($keyword) {
            if ($keyword_category == "Order ID") {
                $qry .= " AND order_id LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND c_username LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Pelanggan") {
                $qry .= " AND customer_text LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Pelanggan") {
                $qry .= " AND phone LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Resi") {
                $qry .= " AND awb_number LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Produk") {
                $qry .= " AND `json` LIKE '%$keyword%' ";
            }
        }

        $order_type = $_GET['order_type'];
        $data['order_type'] = $order_type;
        if ($order_type == "Manual") {
            $qry .= " AND is_manual = 1 ";
        } else if ($order_type == "Marketplace") {
            $qry .= " AND is_manual = 0 ";
        } else if ($order_type == "Belum Dikonfigurasi") {
            $qry .= " AND is_configurated = 0 ";
        }

        if ($p == "MANUAL") {
            $qry .= " AND is_manual = '1' ";
        } else if ($p == "MARKETPLACE") {
            $qry .= " AND is_manual = '0' ";
        }


        if ($p == "MANUAL") {
            $data['data'][] = array();
            $data['data'][] = array();
            $data['data'][] = array();
        } else {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction
            WHERE $qry 
            -- AND order_status NOT IN ('CANCELLED','IN_CANCEL') 
            AND type_sub = 'POS' 
            ORDER BY date DESC, id DESC
            ");
            $data['data'] = $query;
        }

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


        $query = $this->mymodel->selectWithQuery("SELECT * FROM product
        -- ORDER BY brand ASC, sub_name ASC
        WHERE is_gift = 0 AND is_varian = 0
        ORDER BY id ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product
        -- ORDER BY brand ASC, sub_name ASC
        WHERE is_gift = 1 AND is_varian = 0
        ORDER BY id ASC
        ");

        $data['gift'] = $query;

        if ($p == "MANUAL") {
            $data['header'] = array();

            $header_2 = array();

            foreach ($data['product'] as $k => $v) {
                $header_2[] = strtoupper($v['sub_name']);
            }

            foreach ($data['gift'] as $k => $v) {
                $header_2_gift[] = strtoupper($v['sub_name']);
            }



            $header_3 = array(
                "JENIS PEMBAYARAN",
                "TANGGAL TF",
                "EKSPEDISI",
                "ONGKIR",
                "DISKON",
                "TOTAL BAYAR",
                "ORDER STATUS",
            );

            // $data['header'] = array_merge($header_1, $header_2, $header_3);
            $header_1 = array(
                "ID",
                "KEBUTUHAN",
                "TANGGAL",
                "NO RESI",
                "BRAND",
                "KET",
                "KODE CS",
                "CB/CL",
                "NAMA",
                "NO HP / NO WA HARUS DIAWALI 62",
                "USERNAME",
                "ALAMAT LENGKAP (SAP MAKS. JAM 3)",
                "KEC",
                "KAB",
                "PROV",
                "PESANAN",
            );


            $body_1 = array(
                "id",
                "kebutuhan",
                "date",
                "awb_number",
                "brand",
                "marketplace",
                "cs",
                "cb_cl",
                "customer_text",
                "phone",
                "c_username",
                "address",
                "subdistrict_text",
                "city_text",
                "province_text",
                "pesanan",

            );

            $body_2 = array();

            foreach ($data['product'] as $k => $v) {
                $body_2[] = $v['id'];
            }

            $body_3  = array(
                "payment_type",
                "pay_at",
                "shipping",
                "ongkir",
                "diskon_penjual",
                "customer_price",
                "order_status",
            );

            $data['body'] = array_merge($body_1, $body_2, $body_3);



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

            foreach ($header_3 as $k => $v) {
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

            foreach ($header_2 as $kk => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('ffff00');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }

            foreach ($header_2_gift as $kk => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('a5d870');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }


            $column = 2;

            $is_empty = false;

            foreach ($data['data'] as $k => $v) {
                $index = 1;

                foreach ($body_1 as $k2 => $v2) {

                    $v['id'] = '';
                    $v['date'] = DATE("Y-m-d H:i:s");
                    $v['cs'] = 'FIKRI';
                    $v['awb_number'] = 'SPXID047588387014';
                    $v['address'] = 'Jl. Diponegoro No. 61 Genteng Banyuwangi 68465';
                    $v['subdistrict_text'] = 'Genteng';
                    $v['city_text'] = 'Banyuwangi';
                    $v['province_text'] = 'Jawa Timur';
                    $v['payment_type'] = 'TF';
                    $v['pay_at'] = DATE("Y-m-d");
                    $v['cb_cl'] = 'CB';
                    $v['order_status'] = 'PROCESSED';
                    $v['brand'] = 'POME';
                    $v['shipping'] = 'SPX Standard';
                    $v['marketplace'] = 'WA';
                    $v['customer_text'] = 'RIZAL';
                    $v['phone'] = '6282244243948';
                    $v['c_username'] = 'RIZAL';
                    $v['json'] = '{"18":{"product":"18","sku":"LV","product_text":"Lacto-V","brand":"MG","brand_text":"","qty":1,"hpp":"38000","price":"115000","price_total":115000},"3":{"product":"3","sku":"1-MG","product_text":"MISCELLA-G","brand":"MG","brand_text":"Miscella G","qty":2,"hpp":"110000","price":"250000","price_total":500000},"17":{"product":"17","sku":"MV","product_text":"Miscella-V","brand":"MG","brand_text":"Miscella G","qty":3,"hpp":"22000","price":"56000","price_total":168000}}';
                    $v['pesanan'] = '[{"qty":1,"item_sku":"LV","item_name":"Lacto-V","price_total":115000},{"qty":2,"item_sku":"1-MG","item_name":"MISCELLA-G","price_total":500000},{"qty":3,"item_sku":"MV","item_name":"Miscella-V","price_total":0}]';
                    $v['ongkir'] = "0";
                    $v['customer_price'] = "783000";

                    if ($v2 == "order_id") {
                        $v[$v2] = " " . $v[$v2];
                    }
                    if ($v2 == "phone") {
                        $v[$v2] = " " . $v[$v2];
                    }
                    if ($v2 == "pesanan") {
                        $list = json_decode($v[$v2], true);
                        $v[$v2] = '';
                        foreach ($list as $kk => $vv) {
                            $v[$v2] .= $vv['qty'] . "x " . $vv['item_name'] . "
";
                        }
                    }
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                foreach ($body_3 as $k2 => $v2) {
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                $json = json_decode($v['json'], true);

                foreach ($body_2 as $k2 => $v2) {
                    $val = $json[$v2]['qty'];
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $val);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                foreach ($data['header'] as $k2 => $v2) {
                    $code = $this->template->get_name_from_number($k2 + 1) . $column;
                    $this->spreadsheet
                        ->getActiveSheet()
                        ->getStyle($code)
                        ->getBorders()
                        ->getOutline()
                        ->setBorderStyle(Border::BORDER_THIN);
                }
                $column++;
            }

            $sheet = $this->spreadsheet->getActiveSheet();
            foreach ($sheet->getColumnIterator() as $column) {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }

            $writer = new Xlsx($this->spreadsheet);
            $filename = 'TMP ORDER ML.';
            if ($marketplace) {
                $filename .= $marketplace . '.';
            }
            $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename=' . $filename . '.xlsx');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
        } else if ($p == "MARKETPLACE") {
            $data['header'] = array();

            $header_1 = array(
                "ID",
                "TANGGAL",
                "ORDER ID",
                "BRAND",
                "KET",
                "KODE CS",
                "CB/CL",
                "NAMA",
                "NO HP",
                "USERNAME",
                "ALAMAT",
                "KAB",
                "PROV",
                "PESANAN",
            );

            $header_2 = array();

            foreach ($data['product'] as $k => $v) {
                $header_2[] = strtoupper($v['sub_name']);
            }

            $header_3 = array(
                "OMSET KOTOR",
                "DISKON & VOUCHER PENJUAL",
                "BIAYA LAINNYA",
                "OMSET BERSIH",
                "MARKETPLACE FEE",
                "AFFILIATE FEE",
                "TOTAL PENCAIRAN DANA",
                "IS_CAIR",
                "RETURN",
                "JENIS PEMBAYARAN",
                "JUMLAH",
                "TANGGAL TF",
                "TANGGAL CEK",
                "ACC",
                "EKSPEDISI",
                "NO RESI",
                "ALAMAT",
                "PROV",
                "KAB",
                "KEC",
                "CATATAN",
                "STATUS ORDER",
                // "IS_MANUAL",
            );

            // $data['header'] = array_merge($header_1, $header_2, $header_3);


            $body_1 = array(
                "id",
                "date",
                "order_id",
                "brand",
                "marketplace",
                "cs",
                "cb_cl",
                "customer_text",
                "phone",
                "c_username",
                "address",
                "city_text",
                "province_text",
                "pesanan",

            );

            $body_2 = array();

            foreach ($data['product'] as $k => $v) {
                $body_2[] = $v['id'];
            }

            $body_3  = array(
                "omset_kotor",
                "diskon_penjual",
                "biaya_lainnya",
                "omset_bersih",
                "marketplace_fee",
                "komisi_afiliasi",
                "dana_pencairan",
                "is_disbursement",
                "return",
                "payment_type",
                "dibayar",
                "pay_at",
                "check_at",
                "acc",
                "shipping",
                "awb_number",
                "address",
                "province_text",
                "city_text",
                "subdistrict_text",
                "desc",
                "order_status",
                // "is_manual",
            );

            $data['body'] = array_merge($body_1, $body_2, $body_3);



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

            foreach ($header_2 as $kk => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('ffff00');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }

            foreach ($header_3 as $k => $v) {
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

            foreach ($data['data'] as $k => $v) {
                $index = 1;

                foreach ($body_1 as $k2 => $v2) {
                    if ($v2 == "order_id") {
                        $v[$v2] = " " . $v[$v2];
                    }
                    if ($v2 == "phone") {
                        $v[$v2] = " " . $v[$v2];
                    }
                    if ($v2 == "pesanan") {
                        $list = json_decode($v[$v2], true);
                        $v[$v2] = '';
                        foreach ($list as $kk => $vv) {
                            $v[$v2] .= $vv['qty'] . "x " . $vv['item_name'] . "
";
                        }
                    }
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                $json = json_decode($v['json'], true);

                foreach ($body_2 as $k2 => $v2) {
                    $val = $json[$v2]['qty'];
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $val);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }
                foreach ($body_3 as $k2 => $v2) {
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                foreach ($data['header'] as $k2 => $v2) {
                    $code = $this->template->get_name_from_number($k2 + 1) . $column;
                    $this->spreadsheet
                        ->getActiveSheet()
                        ->getStyle($code)
                        ->getBorders()
                        ->getOutline()
                        ->setBorderStyle(Border::BORDER_THIN);
                }
                $column++;
            }

            $sheet = $this->spreadsheet->getActiveSheet();
            foreach ($sheet->getColumnIterator() as $column) {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }

            $writer = new Xlsx($this->spreadsheet);
            $filename = 'TMP ORDER MP.';
            if ($marketplace) {
                $filename .= $marketplace . '.';
            }
            $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename=' . $filename . '.xlsx');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
        } else {
            echo 'Fitur masih dalam pengembangan!';
            die;
            $data['header'] = array();

            $header_1 = array(
                "ID",
                "TANGGAL",
                "ORDER ID",
                "BRAND",
                "KET",
                "KODE CS",
                "CB/CL",
                "NAMA",
                "NO HP",
                "USERNAME",
                "ALAMAT",
                "KAB",
                "PROV",
                "PESANAN",
            );

            $header_2 = array();

            foreach ($data['product'] as $k => $v) {
                $header_2[] = strtoupper($v['sub_name']);
            }

            $header_3 = array(
                "OMSET KOTOR",
                "DISKON & VOUCHER PENJUAL",
                "BIAYA LAINNYA",
                "OMSET BERSIH",
                "MARKETPLACE FEE",
                "AFFILIATE FEE",
                "TOTAL PENCAIRAN DANA",
                "IS_CAIR",
                "RETURN",
                "JENIS PEMBAYARAN",
                "JUMLAH",
                "TANGGAL TF",
                "TANGGAL CEK",
                "ACC",
                "EKSPEDISI",
                "NO RESI",
                "ALAMAT",
                "PROV",
                "KAB",
                "KEC",
                "CATATAN",
                "STATUS ORDER",
                // "IS_MANUAL",
            );

            // $data['header'] = array_merge($header_1, $header_2, $header_3);


            $body_1 = array(
                "id",
                "date",
                "order_id",
                "brand",
                "marketplace",
                "cs",
                "cb_cl",
                "customer_text",
                "phone",
                "c_username",
                "address",
                "city_text",
                "province_text",
                "pesanan",

            );

            $body_2 = array();

            foreach ($data['product'] as $k => $v) {
                $body_2[] = $v['id'];
            }

            $body_3  = array(
                "omset_kotor",
                "diskon_penjual",
                "biaya_lainnya",
                "omset_bersih",
                "marketplace_fee",
                "komisi_afiliasi",
                "dana_pencairan",
                "is_disbursement",
                "return",
                "payment_type",
                "dibayar",
                "pay_at",
                "check_at",
                "acc",
                "shipping",
                "awb_number",
                "address",
                "province_text",
                "city_text",
                "subdistrict_text",
                "desc",
                "order_status",
                // "is_manual",
            );

            $data['body'] = array_merge($body_1, $body_2, $body_3);



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

            foreach ($header_2 as $kk => $v) {
                $code = $this->template->get_name_from_number($i + 1) . '1';
                $this->spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue($code, $v);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
                $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('ffff00');
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
                $i++;
            }

            foreach ($header_3 as $k => $v) {
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

            foreach ($data['data'] as $k => $v) {
                $index = 1;

                foreach ($body_1 as $k2 => $v2) {
                    if ($v2 == "order_id") {
                        $v[$v2] = " " . $v[$v2];
                    }
                    if ($v2 == "phone") {
                        $v[$v2] = " " . $v[$v2];
                    }
                    if ($v2 == "pesanan") {
                        $list = json_decode($v[$v2], true);
                        $v[$v2] = '';
                        foreach ($list as $kk => $vv) {
                            $v[$v2] .= $vv['qty'] . "x " . $vv['item_name'] . "
";
                        }
                    }
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                $json = json_decode($v['json'], true);

                foreach ($body_2 as $k2 => $v2) {
                    $val = $json[$v2]['qty'];
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $val);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }
                foreach ($body_3 as $k2 => $v2) {
                    $index_alpha = $this->template->get_name_from_number($index);
                    $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                    $this->spreadsheet->getActiveSheet()
                        ->getStyle($index_alpha . $column)
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                    $index++;
                }

                foreach ($data['header'] as $k2 => $v2) {
                    $code = $this->template->get_name_from_number($k2 + 1) . $column;
                    $this->spreadsheet
                        ->getActiveSheet()
                        ->getStyle($code)
                        ->getBorders()
                        ->getOutline()
                        ->setBorderStyle(Border::BORDER_THIN);
                }
                $column++;
            }

            $sheet = $this->spreadsheet->getActiveSheet();
            foreach ($sheet->getColumnIterator() as $column) {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }

            $writer = new Xlsx($this->spreadsheet);
            $filename = 'ORDER.';
            if ($marketplace) {
                $filename .= $marketplace . '.';
            }
            $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename=' . $filename . '.xlsx');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
        }
    }

    function download_process()
    {

        $start_date = $_GET['start_date'];
        $until_date = $_GET['until_date'];

        $datediff = strtotime($until_date) - strtotime($start_date);

        $days = round($datediff / (60 * 60 * 24));

        if ($start_date == "" || $until_date == "") {
            $msg = "Buat file order tidak berhasil. Tanggal mulai dan akhir harus diisi!";
            echo $this->template->alert_danger($msg);
            die;
        } else if ($days >= 0 && $days <= 30) {
            // continue...
        } else {
            $msg = "Buat file order tidak berhasil. Buat file order hanya bisa maksimal 31 hari!";
            echo $this->template->alert_danger($msg);
            die;
        }


        $last_data = $this->mymodel->selectWithQuery("SELECT created_at
        FROM download_file
        ORDER BY id DESC
        LIMIT 1");
        $last_data = $last_data[0];
        if ($last_data) {
            $diff =  strtotime(DATE("Y-m-d H:i:s")) - strtotime($last_data['created_at']);
            if ($diff <= 60) {
                $msg = "Buat file order tidak berhasil. Buat file bisa dilakukan " . (61 - $diff) . " detik lagi!";
                echo $this->template->alert_danger($msg);
                die;
            }
        }

        $auth = $this->getGoogleSheetsClient();
        if (!$auth['status']) {
            $msg = $auth['message'];
            if (!empty($auth['auth_url'])) {
                $msg .= ' <a href="' . $auth['auth_url'] . '" target="_blank" rel="noopener">Authorize Google</a>';
            }
            echo $this->template->alert_danger($msg);
            die;
        }

        $result = $this->generateOrderGoogleSheet($auth['client']);
        if (!$result['status']) {
            $msg = $result['message'];
            if (!empty($result['auth_url'])) {
                $msg .= ' <a href="' . $result['auth_url'] . '" target="_blank" rel="noopener">Authorize Google</a>';
            }
            echo $this->template->alert_danger($msg);
            die;
        }

        $msg = "Spreadsheet order berhasil dibuat. <a href=\"" . $result['url'] . "\" target=\"_blank\" rel=\"noopener\">Buka Google Sheet</a>";
        echo $this->template->alert_success($msg);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        @ignore_user_abort(true);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            @ob_end_flush();
            @flush();
        }

        $this->appendOrderGoogleSheetData($auth['client'], $result);
        return;
    }

    private function getGoogleSheetsClient()
    {
        $client = new Google_Client();
        $client->setAuthConfig(google_client_auth_config());
        $client->setScopes([
            Google_Service_Drive::DRIVE,
            Google_Service_Sheets::SPREADSHEETS,
            Google_Service_Oauth2::USERINFO_EMAIL,
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent select_account');
        $client->setRedirectUri(base_url('googlemou/oauth2callback'));

        $raw = $this->session->userdata('access_token');
        if (!$raw) {
            return [
                'status' => false,
                'message' => 'Google belum terhubung untuk generate spreadsheet.',
                'auth_url' => $client->createAuthUrl(),
            ];
        }

        $token = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!$token || empty($token['access_token'])) {
            return [
                'status' => false,
                'message' => 'Token Google tidak valid. Login ulang dibutuhkan.',
                'auth_url' => $client->createAuthUrl(),
            ];
        }

        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            $refresh = $client->getRefreshToken();
            if (!$refresh && !empty($token['refresh_token'])) {
                $refresh = $token['refresh_token'];
            }

            if ($refresh) {
                $new_token = $client->fetchAccessTokenWithRefreshToken($refresh);
                if (!isset($new_token['refresh_token'])) {
                    $new_token['refresh_token'] = $refresh;
                }

                if (!empty($new_token['error'])) {
                    return [
                        'status' => false,
                        'message' => 'Refresh token Google gagal. Login ulang dibutuhkan.',
                        'auth_url' => $client->createAuthUrl(),
                    ];
                }

                $client->setAccessToken($new_token);
                $this->session->set_userdata('access_token', json_encode($client->getAccessToken()));
            } else {
                return [
                    'status' => false,
                    'message' => 'Refresh token Google tidak tersedia.',
                    'auth_url' => $client->createAuthUrl(),
                ];
            }
        }

        return [
            'status' => true,
            'client' => $client,
        ];
    }

    private function getOrderDownloadQueryParams()
    {
        $params = [];
        $params['start_date'] = $_GET['start_date'] ?? DATE('Y-m-d', strtotime(DATE('Y-m-d') . " -31 days"));
        $params['until_date'] = $_GET['until_date'] ?? DATE('Y-m-d');
        $params['brand'] = $_GET['brand'] ?? '';
        $params['marketplace'] = $_GET['marketplace'] ?? '';
        $params['cs'] = $_GET['cs'] ?? '';
        $params['c_type'] = $_GET['c_type'] ?? '';
        $params['keyword'] = $_GET['keyword'] ?? '';
        $params['id'] = $_GET['id'] ?? '';
        $params['order_status'] = $_GET['order_status'] ?? '';
        $params['keyword_category'] = $_GET['keyword_category'] ?? '';
        $params['ids'] = $_GET['ids'] ?? '';
        $params['ekspedisi'] = $_GET['ekspedisi'] ?? '';
        $params['order_type'] = $_GET['order_type'] ?? '';

        return $params;
    }

    private function buildOrderDownloadQuery($params)
    {
        $start_date = $params['start_date'];
        $until_date = $params['until_date'];
        $brand = $params['brand'];
        $marketplace = $params['marketplace'];
        $cs = $params['cs'];
        $c_type = $params['c_type'];
        $keyword = $params['keyword'];
        $id = $params['id'];
        $order_status = $params['order_status'];
        $keyword_category = $params['keyword_category'];
        $ids = $params['ids'];
        $ekspedisi = $params['ekspedisi'];
        $order_type = $params['order_type'];

        $qry = " DATE(date) >= '" . $this->db->escape_str($start_date) . "' AND DATE(date) <= '" . $this->db->escape_str($until_date) . "' ";

        if ($id) {
            $qry .= " AND customer = '" . $this->db->escape_str($id) . "' ";
        }

        if ($ids) {
            $qry .= " AND id IN ($ids) ";
        }

        if ($brand) {
            $qry .= " AND brand = '" . $this->db->escape_str($brand) . "' ";
        }

        if ($ekspedisi) {
            $qry .= " AND shipping = '" . $this->db->escape_str($ekspedisi) . "' ";
        }

        if ($marketplace) {
            $qry .= " AND marketplace = '" . $this->db->escape_str($marketplace) . "' ";
        }

        if ($cs) {
            $qry .= " AND cs = '" . $this->db->escape_str($cs) . "' ";
        }

        if ($c_type) {
            $c_type_list = $this->buildInCondition($c_type, [$this->db, 'escape_str']);
            $qry .= " AND c_type IN ($c_type_list) ";
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
                $qry .= " AND order_status = '" . $this->db->escape_str($order_status) . "' ";
            }
        }

        if ($keyword) {
            $keyword = $this->db->escape_str($keyword);
            if ($keyword_category == "Order ID") {
                $qry .= " AND order_id LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND c_username LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Pelanggan") {
                $qry .= " AND customer_text LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Pelanggan") {
                $qry .= " AND phone LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Resi") {
                $qry .= " AND awb_number LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Produk") {
                $qry .= " AND pesanan LIKE '%$keyword%' ";
            }
        }

        if ($order_type == "Manual") {
            $qry .= " AND is_manual = 1 ";
        } else if ($order_type == "Marketplace") {
            $qry .= " AND is_manual = 0 ";
        }

        return $qry;
    }

    private function generateOrderGoogleSheet($client)
    {
        try {
            $sheets = new Google_Service_Sheets($client);
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Inisialisasi Google Sheets gagal: ' . $e->getMessage(),
            ];
        }

        $params = $this->getOrderDownloadQueryParams();
        $start_date = $params['start_date'];
        $until_date = $params['until_date'];
        $marketplace = $params['marketplace'];
        $qry = $this->buildOrderDownloadQuery($params);

        $filename = 'ORDER';
        if ($marketplace) {
            $filename .= '.' . $marketplace;
        }
        $filename .= '.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);

        $user = $_SESSION['user'] ?? ['id' => 0];
        $insert = [
            'title' => $filename,
            'created_at' => DATE("Y-m-d H:i:s"),
            'created_by' => strval($user['id']),
            'param' => $this->template->get_param(),
        ];
        $this->db->insert('download_file', $insert);
        $download_id = $this->db->insert_id();

        $spreadsheet_title = $filename . '.' . $download_id;

        try {
            $spreadsheet = new Google_Spreadsheet([
                'properties' => ['title' => $spreadsheet_title],
                'sheets' => [
                    ['properties' => ['title' => 'Orders']]
                ],
            ]);
            $created_sheet = $sheets->spreadsheets->create($spreadsheet, ['fields' => 'spreadsheetId,spreadsheetUrl']);
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Gagal membuat Google Sheet: ' . $e->getMessage(),
                'auth_url' => (stripos($e->getMessage(), 'scope') !== false || stripos($e->getMessage(), 'permission') !== false) ? base_url('googlemou') : null,
            ];
        }

        $spreadsheet_id = $created_sheet->spreadsheetId;
        $spreadsheet_url = $created_sheet->spreadsheetUrl;

        try {
            $drive = new Google_Service_Drive($client);
            $permission = new \Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $drive->permissions->create($spreadsheet_id, $permission, [
                'supportsAllDrives' => true,
            ]);
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Google Sheet berhasil dibuat, tapi gagal di-set public: ' . $e->getMessage(),
            ];
        }

        $update = [
            'title' => $spreadsheet_title,
            'file' => $spreadsheet_url,
        ];
        $this->db->update('download_file', $update, ['id' => $download_id]);

        return [
            'status' => true,
            'download_id' => $download_id,
            'spreadsheet_id' => $spreadsheet_id,
            'url' => $spreadsheet_url,
            'params' => $params,
            'qry' => $qry,
        ];
    }

    private function appendOrderGoogleSheetData($client, $sheet_job)
    {
        $batch_size = 500;
        $spreadsheet_id = $sheet_job['spreadsheet_id'];
        $download_id = $sheet_job['download_id'];
        $qry = $sheet_job['qry'];

        try {
            $sheets = new Google_Service_Sheets($client);
        } catch (Exception $e) {
            return false;
        }

        $product_query = $this->mymodel->selectWithQuery("SELECT * FROM product ORDER BY brand ASC, sub_name ASC");
        $products = $product_query ?: [];

        $header_1 = ["ID", "TGL ORDER", "TGL RTS", "ORDER ID", "BRAND", "KET", "KODE CS", "CB/CL", "NAMA", "NO HP", "USERNAME", "ALAMAT", "KAB", "PROV", "PESANAN"];
        $header_2 = [];
        foreach ($products as $product) {
            $header_2[] = strtoupper($product['sub_name']);
        }
        $header_3 = ["OMSET KOTOR", "DISKON & VOUCHER PENJUAL", "BIAYA LAINNYA", "OMSET BERSIH", "MARKETPLACE FEE", "AFFILIATE FEE", "TOTAL PENCAIRAN DANA", "IS_CAIR", "RETURN", "JENIS PEMBAYARAN", "JUMLAH", "TANGGAL TF", "TANGGAL CEK", "ACC", "EKSPEDISI", "NO RESI", "ALAMAT", "PROV", "KAB", "KEC", "CATATAN", "STATUS ORDER"];
        $headers = [array_merge($header_1, $header_2, $header_3)];

        try {
            $sheets->spreadsheets_values->append(
                $spreadsheet_id,
                'Orders!A1',
                new Google_ValueRange(['values' => $headers]),
                ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
            );
        } catch (Exception $e) {
            return false;
        }

        $body_1 = ["id", "date", "rts_at", "order_id", "brand", "marketplace", "cs", "cb_cl", "customer_text", "phone", "c_username", "address", "city_text", "province_text", "pesanan"];
        $body_2 = [];
        foreach ($products as $product) {
            $body_2[] = $product['id'];
        }
        $body_3 = ["omset_kotor", "diskon_penjual", "biaya_lainnya", "omset_bersih", "marketplace_fee", "komisi_afiliasi", "dana_pencairan", "is_disbursement", "return", "payment_type", "dibayar", "pay_at", "check_at", "acc", "shipping", "awb_number", "address", "province_text", "city_text", "subdistrict_text", "desc", "order_status"];

        $count_query = $this->mymodel->selectWithQuery("
            SELECT COUNT(*) AS total
            FROM transaction
            WHERE $qry
            AND type_sub = 'POS'
        ");
        $total_rows = (int)($count_query[0]['total'] ?? 0);

        for ($offset = 0; $offset < $total_rows; $offset += $batch_size) {
            $query = $this->mymodel->selectWithQuery("
                SELECT * FROM transaction
                WHERE $qry
                AND type_sub = 'POS'
                ORDER BY date DESC, id DESC
                LIMIT $offset, $batch_size
            ");

            if (empty($query)) {
                continue;
            }

            $rows = [];
            foreach ($query as $v) {
                $row = [];

                foreach ($body_1 as $field) {
                    if ($field == "order_id" || $field == "phone") {
                        $row[] = (string)($v[$field] ?? '');
                    } else if ($field == "pesanan") {
                        $list = json_decode($v[$field] ?? '', true);
                        $text = '';
                        if (is_array($list)) {
                            foreach ($list as $item) {
                                $text .= ($item['qty'] ?? '') . "x " . ($item['item_name'] ?? '') . "\n";
                            }
                        }
                        $row[] = trim($text);
                    } else {
                        $row[] = $v[$field] ?? '';
                    }
                }

                $json = json_decode($v['json'] ?? '', true);
                if (!is_array($json)) {
                    $json = [];
                }
                foreach ($body_2 as $product_id) {
                    $row[] = isset($json[$product_id]['qty']) ? $json[$product_id]['qty'] : '';
                }

                foreach ($body_3 as $field) {
                    $row[] = $v[$field] ?? '';
                }

                $rows[] = $row;
            }

            try {
                $sheets->spreadsheets_values->append(
                    $spreadsheet_id,
                    'Orders!A1',
                    new Google_ValueRange(['values' => $rows]),
                    ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
                );
            } catch (Exception $e) {
                return false;
            }
        }

        $this->db->update('download_file', ['updated_at' => DATE("Y-m-d H:i:s")], ['id' => $download_id]);
        return true;
    }

    function download_file()
    {
        $data['param'] = $this->template->get_param();
        $data['data'] = $this->mymodel->selectWithQuery("SELECT *
    FROM download_file
    ORDER BY id DESC
    LIMIT 10
    ");
        $this->load->view("transaction/download_file", $data);
    }

    function download_ajax()
    {
        $today = DATE('Y-m-d');
        $yesterday = DATE('Y-m-d', strtotime($today . " -1 days"));
        $data['data'] = $this->mymodel->selectWithQuery("SELECT *
    FROM download_file
    WHERE updated_at != '' OR (updated_at = '' AND DATE(created_at) >= '$yesterday')
    ORDER BY id DESC
    LIMIT 10
        ");
        $html = '';
        foreach ($data['data'] as $k => $v) {
            $btn = '<div style="margin-top:10px;padding-right:30px"><h4><i class="fa fa-circle-o-notch fa-spin"></i></h4></div>';
            $file_path = $v['file'];
            if ($file_path && preg_match('/^https?:\/\//i', $file_path)) {
                $escaped_url = htmlspecialchars($file_path, ENT_QUOTES, 'UTF-8');
                $status_note = $v['updated_at']
                    ? '<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;background:#ecfdf3;border:1px solid #bbf7d0;color:#166534;font-size:11px;font-weight:600;line-height:1.2;letter-spacing:.01em;">Siap dibuka</span>'
                    : '<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:11px;font-weight:600;line-height:1.2;letter-spacing:.01em;">Masih generate</span>';
                $btn = '
                <div class="d-flex flex-column align-items-end gap-1">
                    <a href="' . $escaped_url . '" class="btn btn-primary" target="_blank" rel="noopener">Buka Spreadsheet</a>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-copy-download-link" data-link="' . $escaped_url . '">Copy Link</button>
                    ' . $status_note . '
                </div>';
            } else if ($file_path && file_exists($file_path)) {
                $public_url = base_url() . '/assets/webfile/excel/' . $v['title'];
                $escaped_url = htmlspecialchars($public_url, ENT_QUOTES, 'UTF-8');
                $btn = '
                <div class="d-flex flex-column align-items-end gap-1">
                    <a href="' . $escaped_url . '" class="btn btn-primary" target="_blank" rel="noopener">Buka Link</a>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-copy-download-link" data-link="' . $escaped_url . '">Copy Link</button>
                </div>';
            }
            $html .= '<tr>
        <td class="text-start td-breakline" style="padding-left:0px!important;width:50%!important">' . $v['title'] . '</td>
        <td class="text-start" style="vertical-align:middle">' . DATE("d/m/Y H:i", strtotime($v['created_at'])) . '</td>
        <td class="text-end" style="padding-right:0px!important" id="btn-download-' . $v['id'] . '">
        <h4 class="fw-500 mb-1 text-end" id="summary-order-1" style="vertical-align:middle">
        ' . $btn . '
        </td>
    </tr>';
        }
        $html = '<div class="table-responsive">
    <table class="table table-bordered">
    ' . $html . '
        </table>
        <small class="text-muted d-block mt-2">File yang selesai dibuat akan muncul sebagai link spreadsheet/file dan bisa dibuka atau dicopy tanpa memaksa browser download ulang.</small>
    </div>	';
        $dt['html'] = $html;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dt, true);
        die;
    }

    function tracking()
    {
        $data['title'] = 'Track Order - ' . $this->template->title();
        $id = $_GET['id'];
        $order_id = $_GET['order_id'];
        $package_number = $_GET['package_number'];
        $marketplace = $_GET['marketplace'];
        $data['data'] = $this->mymodel->selectWithQuery("SELECT *
        FROM transaction 
        WHERE id = '$id' AND type_sub = 'POS'");
        $data['data'] = $data['data'][0];
        if (empty($data['data'])) {
            redirect(base_url() . 'transaction');
        }

        $shipping = $data['data']['shipping'];
        $shipping = $this->mymodel->selectWithQuery("SELECT img FROM shipping WHERE name = '$shipping'");
        $data['shipping'] = $shipping[0];

        $data['param'] = $this->template->get_param();
        $data['content'] = $this->load->view('transaction/tracking', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function download_resi()
    {

        $start_date = $_GET['start_date'];
        $start_date = $_GET['start_date'];
        $p = $_GET['p'];

        if (empty($start_date)) {
            $start_date = DATE("Y-m-01");
            
        }
        if (empty($until_date)) {
            $until_date = DATE("Y-m-d");
        }

        $site = $_GET['site'];
        $qry = '';

        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];


        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];
        $keyword_category = $_GET['keyword_category'];
        $id = $_GET['id'];
        $order_status = $_GET['order_status'];


        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

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


        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE is_varian = 0
        ORDER BY brand ASC, sub_name ASC
        ");

        $data['product'] = $query;


        $data['header'] = array();

        $header_1 = array(
            "ORDER ID",
            "NO RESI",
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

        $body_1 = array(
            "order_id",
            "awb_number",

        );

        $column = 2;

        $data['data'][] = array();
        $data['data'][] = array();
        $data['data'][] = array();

        foreach ($data['data'] as $k => $v) {
            $index = 1;

            foreach ($body_1 as $k2 => $v2) {

                $v['order_id'] = 'BHS20240430081453219';
                $v['awb_number'] = 'SPXID047588387014';

                if ($v2 == "order_id") {
                    $v[$v2] = " " . $v[$v2];
                }

                $index_alpha = $this->template->get_name_from_number($index);
                $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                $this->spreadsheet->getActiveSheet()
                    ->getStyle($index_alpha . $column)
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                $index++;
            }


            foreach ($data['header'] as $k2 => $v2) {
                $code = $this->template->get_name_from_number($k2 + 1) . $column;
                $this->spreadsheet
                    ->getActiveSheet()
                    ->getStyle($code)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THIN);
            }
            $column++;
        }

        $sheet = $this->spreadsheet->getActiveSheet();
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $writer = new Xlsx($this->spreadsheet);
        $filename = 'TMP RESI.';
        if ($marketplace) {
            $filename .= $marketplace . '.';
        }
        $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename=' . $filename . '.xlsx');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
    }



    function tracking_ajax()
    {
        // header('Content-Type: application/json; charset=utf-8');

        $dt = $_GET;


        $url = $this->template->endpoint_url() . 'api/marketplace/order/tracking?order_id=' . $dt['order_id'] . '&marketplace=' . $dt['marketplace'];


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
            ),
        ));

        $response = curl_exec($curl);
        $response = json_decode($response, true);

        $text = "";
        if ($response['data']) {
            foreach ($response['data'] as $k => $v) {
                $text .= '
                    
                <div class="timeline-item">
                <div class="timeline-media">
                </div>
                <div class="timeline-content">
                  
                <div class="row mb-2">
                <div class="col-md-2">
                <p class="mb-1">' . $v['update_time'] . '</p>
                </div>
                <div class="col-md-12">
                <p class="mb-1">' . strtoupper($v['detail_type']) . '</p>
                <p class="mb-1">' . $v['title'] . '</p>
                <p class="mb-1">' . $v['description'] . '</p>
                <p class="mb-1">' . $v['datetime'] . '</p>
                </div>
            </div>
                </div>
             </div>
                    ';
            }
        } else {
            $text = '<p class="mb-3">' . $response['msg'] . '</p>';
        }




        $html['html'] = $text;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($html, true);
    }

    function objKeySort($obj)
    {
        $newKey = array_keys($obj);
        sort($newKey);
        $newObj = [];
        foreach ($newKey as $key) {
            $newObj[$key] = $obj[$key];
        }
        return $newObj;
    }

    function getEnvVar($k)
    {
        $v = $_ENV[$k] ?? null;
        if ($v !== null) {
            return $v;
        }
        $v = $_SERVER[$k] ?? null;
        if ($v !== null) {
            return $v;
        }
        return null;
    }

    function calSign($dt)
    {
        $secret = $dt['secret'];
        $ts = $dt['timest'];
        $queryParam = $dt['get'];
        $param = [];
        foreach ($queryParam as $key => $value) {
            if ($key == "timestamp") {
                $v = $ts;
            } else {
                $v = $value;
                if ($v == null || $v == "{{" . $key . "}}") {
                    $v = $this->getEnvVar($key);
                }
            }
            $param[$key] = $v;
        }

        unset($param["sign"]);
        unset($param["access_token"]);
        $sortedObj = $this->objKeySort($param);
        $path = parse_url($dt['url'], PHP_URL_PATH);
        $signstring = $secret . $path;
        foreach ($sortedObj as $key => $value) {
            $signstring .= $key . $value;
        }
        $signstring .= $secret;
        $sign = hash_hmac("sha256", $signstring, $secret);
        return $sign;
    }
}
