<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\DataType;


use Lazada\LazopClient;
use Lazada\LazopRequest;

defined('BASEPATH') or exit('No direct script access allowed');
class Api_v2 extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->library('Template');
        $this->app_key_tiktok = app_env('TIKTOK_APP_KEY');
        $this->app_secret_tiktok = app_env('TIKTOK_APP_SECRET');
        $this->app_key_lazada = app_env('LAZADA_APP_KEY');
        $this->app_secret_lazada = app_env('LAZADA_APP_SECRET');
        $this->partner_id_shopee = app_env('SHOPEE_PARTNER_ID');
        $this->partner_key_shopee = app_env('SHOPEE_PARTNER_KEY');
        $this->app_id_meta = app_env('META_APP_ID');
        $this->app_secret_meta = app_env('META_APP_SECRET');
        $this->app_id_threads = app_env('THREADS_APP_ID');
        $this->app_secret_threads = app_env('THREADS_APP_SECRET');
        $this->app_token_client_threads = app_env('THREADS_CLIENT_TOKEN');
        $config = $this->mymodel->selectWithQuery("SELECT * FROM endorse_config");
        $config_map = array();
        if (is_array($config)) {
            foreach ($config as $row) {
                if (isset($row['title'])) {
                    $config_map[$row['title']] = isset($row['value']) ? $row['value'] : null;
                }
            }
        }
        $this->fyp_views = isset($config_map['fyp_views']) ? intval($config_map['fyp_views']) : 0;
        $this->fyp_percentage = isset($config_map['fyp_persentase']) ? intval($config_map['fyp_persentase']) : 0;
        
    }

    private function deactivate_limited_update_endorse_content()
    {
        $rows = $this->mymodel->selectWithQuery("
            SELECT e.id
            FROM endorse e
            INNER JOIN endorse_campaign c ON c.id = e.id_campaign
            WHERE e.status = 'Aktif'
              AND e.status_campaign = 'Aktif'
              AND c.update_terbatas = 1
              AND c.update_batas_hari > 0
              AND e.posting_at IS NOT NULL
              AND e.posting_at != ''
              AND DATE_ADD(DATE(e.posting_at), INTERVAL c.update_batas_hari DAY) <= CURDATE()
        ");

        $count = 0;
        $now = DATE("Y-m-d H:i:s");
        foreach ($rows as $row) {
            $this->db->update('endorse', [
                'status' => 'Tidak Aktif',
                'updated_at' => $now,
            ], ['id' => $row['id']]);
            $count++;
        }

        return $count;
    }

    // ============================================
    // Threads OAuth
    // ============================================

    public function threads_authorize()
    {
        $influencer_id = $_GET['influencer_id'] ?? '';
        if (empty($influencer_id)) {
            echo "influencer_id wajib diisi";
            return;
        }

        $redirect_uri = base_url() . 'api_v2/threads_callback';
        $scope = 'threads_basic,threads_manage_insights';

        $url = 'https://threads.net/oauth/authorize'
            . '?client_id=' . $this->app_id_threads
            . '&redirect_uri=' . urlencode($redirect_uri)
            . '&scope=' . $scope
            . '&response_type=code'
            . '&state=' . $influencer_id;

        redirect($url);
    }

    public function threads_callback()
    {
        $code = $_GET['code'] ?? '';
        $influencer_id = $_GET['state'] ?? '';

        if (empty($code) || empty($influencer_id)) {
            echo "OAuth gagal: code atau state tidak ditemukan.";
            return;
        }

        $redirect_uri = base_url() . 'api_v2/threads_callback';

        // Step 1: Tukar code → short-lived token
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://graph.threads.net/oauth/access_token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => $this->app_id_threads,
                'client_secret' => $this->app_secret_threads,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirect_uri,
                'code' => $code,
            ]),
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($response, true);

        if (empty($data['access_token'])) {
            echo "Gagal mendapatkan access token: " . $response;
            return;
        }

        $short_token = $data['access_token'];
        $threads_user_id = $data['user_id'] ?? '';

        // Step 2: Tukar short-lived → long-lived token (60 hari)
        $curl2 = curl_init();
        curl_setopt_array($curl2, [
            CURLOPT_URL => 'https://graph.threads.net/access_token'
                . '?grant_type=th_exchange_token'
                . '&client_secret=' . $this->app_secret_threads
                . '&access_token=' . $short_token,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $response2 = curl_exec($curl2);
        curl_close($curl2);
        $data2 = json_decode($response2, true);

        $long_token = $data2['access_token'] ?? $short_token;
        $expires_in = $data2['expires_in'] ?? 5184000; // default 60 hari

        $expires_at = date("Y-m-d H:i:s", time() + $expires_in);

        // Step 3: Simpan ke database
        $this->db->update('influencer', [
            'threads_user_id' => $threads_user_id,
            'threads_access_token' => $long_token,
            'threads_token_expires_at' => $expires_at,
        ], ['id' => $influencer_id]);

        // Redirect ke halaman edit influencer
        redirect(base_url() . 'influencer?msg=threads_connected&influencer_id=' . $influencer_id);
    }

    public function threads_refresh_token()
    {
        // Refresh long-lived token (harus di-refresh sebelum expired, bisa dipanggil via cron)
        $influencers = $this->mymodel->selectWithQuery(
            "SELECT id, threads_access_token, threads_token_expires_at
             FROM influencer
             WHERE threads_access_token IS NOT NULL
             AND threads_access_token != ''
             AND threads_token_expires_at != ''
             AND threads_token_expires_at <= '" . date("Y-m-d H:i:s", strtotime("+7 days")) . "'"
        );

        $results = [];
        if (!empty($influencers)) {
            foreach ($influencers as $inf) {
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://graph.threads.net/refresh_access_token'
                        . '?grant_type=th_refresh_token'
                        . '&access_token=' . $inf['threads_access_token'],
                    CURLOPT_RETURNTRANSFER => true,
                ]);
                $response = curl_exec($curl);
                curl_close($curl);
                $data = json_decode($response, true);

                if (!empty($data['access_token'])) {
                    $expires_at = date("Y-m-d H:i:s", time() + ($data['expires_in'] ?? 5184000));
                    $this->db->update('influencer', [
                        'threads_access_token' => $data['access_token'],
                        'threads_token_expires_at' => $expires_at,
                    ], ['id' => $inf['id']]);
                    $results[] = ['id' => $inf['id'], 'status' => 'refreshed'];
                } else {
                    $results[] = ['id' => $inf['id'], 'status' => 'failed', 'response' => $response];
                }
            }
        }

        echo json_encode(['status' => true, 'results' => $results]);
    }

    public function threads_deauthorize()
    {
        // Dipanggil Meta saat user uninstall/deauthorize app
        $signed_request = $_POST['signed_request'] ?? '';
        if (!empty($signed_request)) {
            $data = $this->parse_threads_signed_request($signed_request);
            if (!empty($data['user_id'])) {
                $this->db->update('influencer', [
                    'threads_access_token' => '',
                    'threads_user_id' => '',
                    'threads_token_expires_at' => '',
                ], ['threads_user_id' => $data['user_id']]);
            }
        }
        echo json_encode(['url' => base_url(), 'confirmation_code' => uniqid('threads_')]);
    }

    public function threads_delete_data()
    {
        // Dipanggil Meta saat user request data deletion
        $signed_request = $_POST['signed_request'] ?? '';
        if (!empty($signed_request)) {
            $data = $this->parse_threads_signed_request($signed_request);
            if (!empty($data['user_id'])) {
                $this->db->update('influencer', [
                    'threads_access_token' => '',
                    'threads_user_id' => '',
                    'threads_token_expires_at' => '',
                ], ['threads_user_id' => $data['user_id']]);
            }
        }
        $confirmation_code = uniqid('del_');
        echo json_encode([
            'url' => base_url() . 'api_v2/threads_delete_status?code=' . $confirmation_code,
            'confirmation_code' => $confirmation_code,
        ]);
    }

    public function threads_delete_status()
    {
        echo json_encode(['status' => 'completed']);
    }

    private function parse_threads_signed_request($signed_request)
    {
        $parts = explode('.', $signed_request, 2);
        if (count($parts) < 2) return [];
        $payload = base64_decode(strtr($parts[1], '-_', '+/'));
        return json_decode($payload, true) ?: [];
    }

    private function ensure_endorse_fyp_asset_dir()
    {
        $relative_dir = 'assets/uploads/endorse_fyp/';
        $absolute_dir = FCPATH . $relative_dir;

        if (!is_dir($absolute_dir)) {
            @mkdir($absolute_dir, 0755, true);
        }

        return [$relative_dir, $absolute_dir];
    }

    private function normalize_endorse_fyp_asset_url($asset_url)
    {
        $asset_url = trim((string)$asset_url);
        if ($asset_url === '') {
            return '';
        }

        if (strpos($asset_url, 'data:image/') === 0) {
            return $asset_url;
        }

        if (preg_match('#^https?://#i', $asset_url) === 1) {
            return $asset_url;
        }

        return base_url(ltrim($asset_url, '/'));
    }

    private function download_tiktok_fyp_asset($asset_url, $id_endorse, $asset_type)
    {
        $asset_url = trim((string)$asset_url);
        if ($asset_url === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $asset_url) !== 1) {
            return $this->normalize_endorse_fyp_asset_url($asset_url);
        }

        list($relative_dir, $absolute_dir) = $this->ensure_endorse_fyp_asset_dir();
        if (!is_dir($absolute_dir)) {
            return $this->normalize_endorse_fyp_asset_url($asset_url);
        }

        if ($asset_type === 'cover') {
            $ext = 'jpg';
        } else {
            $path = parse_url($asset_url, PHP_URL_PATH);
            $ext = strtolower(pathinfo((string)$path, PATHINFO_EXTENSION));
            if ($ext === '' || strlen($ext) > 5) {
                $ext = 'mp4';
            }
        }

        $filename = sprintf(
            '%s_%s_%s_%s.%s',
            $asset_type,
            (string)$id_endorse,
            date('YmdHis'),
            substr(md5($asset_url), 0, 8),
            $ext
        );

        $absolute_path = $absolute_dir . $filename;
        $relative_path = $relative_dir . $filename;

        $content = @file_get_contents($asset_url);
        if ($content === false || $content === '') {
            return $this->normalize_endorse_fyp_asset_url($asset_url);
        }

        $saved = @file_put_contents($absolute_path, $content);
        if ($saved === false) {
            return $this->normalize_endorse_fyp_asset_url($asset_url);
        }

        return $this->normalize_endorse_fyp_asset_url($relative_path);
    }

    public function index()
    {
        $dt = $_GET;
        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = $dt;
        $html['msg'] = "Bhskin REST API access has been successful!";
        echo json_encode($html, true);
    }

    private function curl_json_request($url, $headers = array(), $method = 'GET', $body = null, $timeout = 60)
    {
        $curl = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
        );

        if (!empty($headers)) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $curl_error = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return array(
            'raw' => $response,
            'json' => is_string($response) ? json_decode($response, true) : null,
            'error' => $curl_error,
            'http_code' => $http_code
        );
    }

    private function get_numeric_value($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return null;
        }

        return doubleval($value);
    }

    private function find_balance_value($data)
    {
        if (!is_array($data)) {
            return null;
        }

        $keys = array('total_balance', 'account_balance', 'valid_account_balance', 'balance', 'cash_balance', 'valid_cash_balance', 'available_balance');
        foreach ($keys as $key) {
            if (isset($data[$key]) && !is_array($data[$key])) {
                return $this->get_numeric_value($data[$key]);
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->find_balance_value($value);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function save_ads_balance_snapshot($payload)
    {
        $now = date('Y-m-d H:i:s');
        $params = array(
            isset($payload['platform']) ? $payload['platform'] : '',
            isset($payload['account_id']) ? $payload['account_id'] : '',
            isset($payload['account_name']) ? $payload['account_name'] : null,
            isset($payload['shop_id']) ? $payload['shop_id'] : null,
            isset($payload['shop_name']) ? $payload['shop_name'] : null,
            isset($payload['bc_id']) ? $payload['bc_id'] : null,
            isset($payload['currency']) ? $payload['currency'] : null,
            isset($payload['balance']) ? $payload['balance'] : null,
            isset($payload['balance_idr']) ? $payload['balance_idr'] : null,
            isset($payload['spend_cap']) ? $payload['spend_cap'] : null,
            isset($payload['amount_spent']) ? $payload['amount_spent'] : null,
            isset($payload['computed_balance']) ? $payload['computed_balance'] : null,
            isset($payload['data_timestamp']) ? $payload['data_timestamp'] : null,
            isset($payload['status']) ? $payload['status'] : 'success',
            isset($payload['error_message']) ? $payload['error_message'] : null,
            isset($payload['raw_response']) ? $payload['raw_response'] : null,
            $now,
            $now,
            $now
        );

        $sql = "INSERT INTO ads_balance_snapshot
            (platform, account_id, account_name, shop_id, shop_name, bc_id, currency, balance, balance_idr, spend_cap, amount_spent, computed_balance, data_timestamp, status, error_message, raw_response, synced_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                account_name = VALUES(account_name),
                shop_id = VALUES(shop_id),
                shop_name = VALUES(shop_name),
                bc_id = VALUES(bc_id),
                currency = VALUES(currency),
                balance = VALUES(balance),
                balance_idr = VALUES(balance_idr),
                spend_cap = VALUES(spend_cap),
                amount_spent = VALUES(amount_spent),
                computed_balance = VALUES(computed_balance),
                data_timestamp = VALUES(data_timestamp),
                status = VALUES(status),
                error_message = VALUES(error_message),
                raw_response = VALUES(raw_response),
                synced_at = VALUES(synced_at),
                updated_at = VALUES(updated_at)";

        return $this->db->query($sql, $params);
    }

    private function get_meta_ads_access_token()
    {
        $config_row = $this->mymodel->selectDataOne('marketplace_config', array('opt' => 'META', 'status' => 'Aktif'));
        if (empty($config_row)) {
            return '';
        }

        $config = json_decode($config_row['val'], true);
        if (!is_array($config)) {
            return '';
        }

        if (!empty($config['refresh_token'])) {
            return $config['refresh_token'];
        }

        return isset($config['access_token']) ? $config['access_token'] : '';
    }

    private function get_tiktok_ads_access_token()
    {
        $config_row = $this->mymodel->selectDataOne('marketplace_config', array('opt' => 'TIKTOKBC', 'status' => 'Aktif'));
        if (!empty($config_row)) {
            $config = json_decode($config_row['val'], true);
            if (is_array($config) && !empty($config['access_token'])) {
                return $config['access_token'];
            }
        }

        return app_env('TIKTOK_BUSINESS_ACCESS_TOKEN');
    }

    private function sync_meta_ads_balance()
    {
        $access_token = $this->get_meta_ads_access_token();
        $result = array();

        if ($access_token == '') {
            return array(
                'platform' => 'META',
                'status' => false,
                'message' => 'Access token Meta tidak ditemukan',
                'results' => $result
            );
        }

        $accounts = $this->mymodel->selectWithQuery("SELECT account_id, account_name FROM ads_meta_account WHERE status = 1 ORDER BY created_at DESC");
        foreach ($accounts as $account) {
            $account_id = trim((string)$account['account_id']);
            if ($account_id == '') {
                continue;
            }

            $url = 'https://graph.facebook.com/v21.0/act_' . $account_id . '?' . http_build_query(array(
                'fields' => 'balance,spend_cap,amount_spent,currency,name',
                'access_token' => $access_token
            ));

            $response = $this->curl_json_request($url);
            $data = is_array($response['json']) ? $response['json'] : array();
            $is_success = $response['error'] == '' && empty($data['error']) && $response['http_code'] >= 200 && $response['http_code'] < 300;

            $balance = isset($data['balance']) ? $this->get_numeric_value($data['balance']) : null;
            $spend_cap = isset($data['spend_cap']) ? $this->get_numeric_value($data['spend_cap']) : null;
            $amount_spent = isset($data['amount_spent']) ? $this->get_numeric_value($data['amount_spent']) : null;
            $computed_balance = null;
            if ($spend_cap !== null && $amount_spent !== null) {
                $computed_balance = $spend_cap - $amount_spent;
                if ($balance === null) {
                    $balance = $computed_balance;
                }
            }

            $error_message = '';
            if (!$is_success) {
                $error_message = $response['error'];
                if ($error_message == '' && isset($data['error']['message'])) {
                    $error_message = $data['error']['message'];
                }
                if ($error_message == '') {
                    $error_message = 'HTTP ' . $response['http_code'];
                }
            }

            $payload = array(
                'platform' => 'META',
                'account_id' => $account_id,
                'account_name' => isset($data['name']) ? $data['name'] : $account['account_name'],
                'currency' => isset($data['currency']) ? $data['currency'] : 'IDR',
                'balance' => $is_success ? $balance : null,
                'balance_idr' => $is_success ? $balance : null,
                'spend_cap' => $is_success ? $spend_cap : null,
                'amount_spent' => $is_success ? $amount_spent : null,
                'computed_balance' => $is_success ? $computed_balance : null,
                'data_timestamp' => date('Y-m-d H:i:s'),
                'status' => $is_success ? 'success' : 'failed',
                'error_message' => $is_success ? null : $error_message,
                'raw_response' => $response['raw']
            );
            $this->save_ads_balance_snapshot($payload);

            $result[] = array(
                'account_id' => $account_id,
                'account_name' => $payload['account_name'],
                'status' => $payload['status'],
                'balance' => $payload['balance'],
                'error_message' => $payload['error_message']
            );
        }

        return array(
            'platform' => 'META',
            'status' => true,
            'message' => 'Sync saldo Meta selesai',
            'results' => $result
        );
    }

    private function sync_tiktok_ads_balance()
    {
        $access_token = $this->get_tiktok_ads_access_token();
        $result = array();

        if ($access_token == '') {
            return array(
                'platform' => 'TIKTOKBC',
                'status' => false,
                'message' => 'Access token TikTok BC tidak ditemukan',
                'results' => $result
            );
        }

        $bc_rows = $this->mymodel->selectWithQuery("
            SELECT id, shop_id, shop_name
            FROM marketplace_config
            WHERE opt = 'TIKTOKBC' AND status = 'Aktif' AND shop_id != ''
            ORDER BY id ASC
        ");

        foreach ($bc_rows as $bc) {
            $bc_id = trim((string)$bc['shop_id']);
            if ($bc_id == '') {
                $result[] = array(
                    'marketplace_config_id' => $bc['id'],
                    'status' => 'failed',
                    'error_message' => 'shop_id/bc_id belum diisi di marketplace_config'
                );
                continue;
            }

            $page = 1;
            $page_size = 50;
            $has_more = true;
            $bc_success = 0;
            $bc_failed = 0;

            while ($has_more && $page <= 50) {
                $url = 'https://business-api.tiktok.com/open_api/v1.3/advertiser/balance/get/?' . http_build_query(array(
                    'bc_id' => $bc_id,
                    'page' => $page,
                    'page_size' => $page_size
                ));
                $response = $this->curl_json_request($url, array('Access-Token: ' . $access_token));
                $data = is_array($response['json']) ? $response['json'] : array();
                $is_success = $response['error'] == '' && isset($data['code']) && intval($data['code']) === 0;

                if (!$is_success) {
                    $error_message = $response['error'];
                    if ($error_message == '' && isset($data['message'])) {
                        $error_message = $data['message'];
                    }
                    if ($error_message == '' && isset($data['msg'])) {
                        $error_message = $data['msg'];
                    }
                    if ($error_message == '') {
                        $error_message = 'HTTP ' . $response['http_code'];
                    }

                    $result[] = array(
                        'bc_id' => $bc_id,
                        'page' => $page,
                        'status' => 'failed',
                        'balance' => null,
                        'currency' => 'IDR',
                        'error_message' => $error_message
                    );
                    $bc_failed++;
                    break;
                }

                $list = array();
                if (isset($data['data']['list']) && is_array($data['data']['list'])) {
                    $list = $data['data']['list'];
                } else if (isset($data['data']['advertiser_account_list']) && is_array($data['data']['advertiser_account_list'])) {
                    $list = $data['data']['advertiser_account_list'];
                } else if (isset($data['data']['advertiser_balance_list']) && is_array($data['data']['advertiser_balance_list'])) {
                    $list = $data['data']['advertiser_balance_list'];
                }

                foreach ($list as $item) {
                    $advertiser_id = '';
                    if (isset($item['advertiser_id'])) {
                        $advertiser_id = trim((string)$item['advertiser_id']);
                    } else if (isset($item['id'])) {
                        $advertiser_id = trim((string)$item['id']);
                    }

                    if ($advertiser_id == '') {
                        continue;
                    }

                    $advertiser_name = isset($item['advertiser_name']) ? $item['advertiser_name'] : '';
                    if ($advertiser_name == '' && isset($item['name'])) {
                        $advertiser_name = $item['name'];
                    }
                    if ($advertiser_name == '') {
                        $advertiser_name = $advertiser_id;
                    }

                    $currency = 'IDR';
                    if (isset($item['currency'])) {
                        $currency = $item['currency'];
                    }

                    $balance = $this->find_balance_value($item);
                    $budget = null;
                    if (isset($item['budget'])) {
                        $budget = $this->get_numeric_value($item['budget']);
                    } else if (isset($item['account_budget'])) {
                        $budget = $this->get_numeric_value($item['account_budget']);
                    }

                    $payload = array(
                        'platform' => 'TIKTOKBC',
                        'account_id' => $advertiser_id,
                        'account_name' => $advertiser_name,
                        'bc_id' => $bc_id,
                        'currency' => $currency,
                        'balance' => $balance,
                        'balance_idr' => strtoupper($currency) == 'IDR' ? $balance : null,
                        'spend_cap' => $budget,
                        'data_timestamp' => date('Y-m-d H:i:s'),
                        'status' => 'success',
                        'error_message' => null,
                        'raw_response' => json_encode($item)
                    );
                    $this->save_ads_balance_snapshot($payload);

                    $result[] = array(
                        'bc_id' => $bc_id,
                        'advertiser_id' => $advertiser_id,
                        'advertiser_name' => $advertiser_name,
                        'status' => 'success',
                        'balance' => $balance,
                        'currency' => $currency,
                        'error_message' => null
                    );
                    $bc_success++;
                }

                $total_page = isset($data['data']['page_info']['total_page']) ? intval($data['data']['page_info']['total_page']) : 0;
                if ($total_page > 0) {
                    $has_more = $page < $total_page;
                } else {
                    $has_more = count($list) >= $page_size;
                }
                $page++;
            }

            if ($bc_success == 0 && $bc_failed == 0) {
                $result[] = array(
                    'bc_id' => $bc_id,
                    'status' => 'success',
                    'balance' => null,
                    'currency' => 'IDR',
                    'error_message' => 'Tidak ada akun iklan di response advertiser balance'
                );
            }
        }

        return array(
            'platform' => 'TIKTOKBC',
            'status' => true,
            'message' => 'Sync saldo TikTok per akun iklan selesai',
            'results' => $result
        );
    }

    private function sync_shopee_ads_balance()
    {
        $shops = $this->mymodel->selectWithQuery("SELECT * FROM marketplace_config WHERE opt = 'SHOPEE' AND status = 'Aktif' ORDER BY shop_name ASC");
        $result = array();
        $host = 'https://partner.shopeemobile.com';
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;

        foreach ($shops as $shop) {
            $config = json_decode($shop['val'], true);
            $access_token = is_array($config) && isset($config['access_token']) ? $config['access_token'] : '';
            $shop_id = $shop['shop_id'];

            if ($access_token == '') {
                $payload = array(
                    'platform' => 'SHOPEE',
                    'account_id' => $shop_id,
                    'account_name' => $shop['shop_name'],
                    'shop_id' => $shop_id,
                    'shop_name' => $shop['shop_name'],
                    'currency' => 'IDR',
                    'data_timestamp' => date('Y-m-d H:i:s'),
                    'status' => 'failed',
                    'error_message' => 'Access token Shopee tidak ditemukan',
                    'raw_response' => ''
                );
                $this->save_ads_balance_snapshot($payload);
                $result[] = array('shop_id' => $shop_id, 'status' => 'failed', 'error_message' => $payload['error_message']);
                continue;
            }

            $path = '/api/v2/ads/get_total_balance';
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $url = $host . $path . '?' . http_build_query(array(
                'partner_id' => $partner_id,
                'timestamp' => $timest,
                'shop_id' => $shop_id,
                'access_token' => $access_token,
                'sign' => $sign
            ));

            $response = $this->curl_json_request($url, array('Content-Type: application/json'));
            $data = is_array($response['json']) ? $response['json'] : array();
            $is_success = $response['error'] == '' && isset($data['response']) && (!isset($data['error']) || $data['error'] == '');
            $balance = $is_success && isset($data['response']['total_balance']) ? $this->get_numeric_value($data['response']['total_balance']) : null;
            $data_timestamp = date('Y-m-d H:i:s');
            if ($is_success && !empty($data['response']['data_timestamp'])) {
                $data_timestamp = date('Y-m-d H:i:s', intval($data['response']['data_timestamp']));
            }

            $error_message = '';
            if (!$is_success) {
                $error_message = $response['error'];
                if ($error_message == '' && isset($data['message'])) {
                    $error_message = $data['message'];
                }
                if ($error_message == '' && isset($data['error'])) {
                    $error_message = $data['error'];
                }
                if ($error_message == '') {
                    $error_message = 'HTTP ' . $response['http_code'];
                }
            }

            $payload = array(
                'platform' => 'SHOPEE',
                'account_id' => $shop_id,
                'account_name' => $shop['shop_name'],
                'shop_id' => $shop_id,
                'shop_name' => $shop['shop_name'],
                'currency' => 'IDR',
                'balance' => $balance,
                'balance_idr' => $balance,
                'data_timestamp' => $data_timestamp,
                'status' => $is_success ? 'success' : 'failed',
                'error_message' => $is_success ? null : $error_message,
                'raw_response' => $response['raw']
            );
            $this->save_ads_balance_snapshot($payload);

            $result[] = array(
                'shop_id' => $shop_id,
                'shop_name' => $shop['shop_name'],
                'status' => $payload['status'],
                'balance' => $payload['balance'],
                'error_message' => $payload['error_message']
            );
        }

        return array(
            'platform' => 'SHOPEE',
            'status' => true,
            'message' => 'Sync saldo Shopee selesai',
            'results' => $result
        );
    }

    public function ads_balance_meta()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->sync_meta_ads_balance(), JSON_PRETTY_PRINT);
    }

    public function ads_balance_tiktok()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->sync_tiktok_ads_balance(), JSON_PRETTY_PRINT);
    }

    public function ads_balance_shopee()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->sync_shopee_ads_balance(), JSON_PRETTY_PRINT);
    }

    public function cronjob_ads_balance()
    {
        header('Content-Type: application/json; charset=utf-8');
        $platform = isset($_GET['platform']) ? strtoupper(trim($_GET['platform'])) : '';
        $response = array(
            'status' => true,
            'synced_at' => date('Y-m-d H:i:s'),
            'results' => array()
        );

        if ($platform == '' || $platform == 'META') {
            $response['results'][] = $this->sync_meta_ads_balance();
        }
        if ($platform == '' || $platform == 'TIKTOKBC' || $platform == 'TIKTOK') {
            $response['results'][] = $this->sync_tiktok_ads_balance();
        }
        if ($platform == '' || $platform == 'SHOPEE') {
            $response['results'][] = $this->sync_shopee_ads_balance();
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    private function estimate_unreleased_amount_from_transaction($trx)
    {
        $dana_pencairan = isset($trx['dana_pencairan']) ? doubleval($trx['dana_pencairan']) : 0;
        if ($dana_pencairan > 0) {
            return $dana_pencairan;
        }

        $omset_bersih = isset($trx['omset_bersih']) ? doubleval($trx['omset_bersih']) : 0;
        $marketplace_fee = isset($trx['marketplace_fee']) ? doubleval($trx['marketplace_fee']) : 0;
        $komisi_afiliasi = isset($trx['komisi_afiliasi']) ? doubleval($trx['komisi_afiliasi']) : 0;
        $estimate = $omset_bersih - $marketplace_fee - $komisi_afiliasi;
        if ($estimate > 0) {
            return $estimate;
        }

        $customer_price = isset($trx['customer_price']) ? doubleval($trx['customer_price']) : 0;
        if ($customer_price > 0) {
            return $customer_price;
        }

        return isset($trx['price_total']) ? doubleval($trx['price_total']) : 0;
    }

    private function save_marketplace_unreleased_balance($payload)
    {
        $now = date('Y-m-d H:i:s');
        $params = array(
            isset($payload['platform']) ? $payload['platform'] : '',
            isset($payload['shop_id']) ? $payload['shop_id'] : '',
            isset($payload['shop_name']) ? $payload['shop_name'] : null,
            isset($payload['currency']) ? $payload['currency'] : 'IDR',
            isset($payload['unreleased_balance']) ? $payload['unreleased_balance'] : 0,
            isset($payload['unreleased_order_count']) ? $payload['unreleased_order_count'] : 0,
            isset($payload['checked_order_count']) ? $payload['checked_order_count'] : 0,
            isset($payload['calculation_method']) ? $payload['calculation_method'] : null,
            isset($payload['status']) ? $payload['status'] : 'success',
            isset($payload['error_message']) ? $payload['error_message'] : null,
            isset($payload['raw_summary']) ? $payload['raw_summary'] : null,
            $now,
            $now,
            $now
        );

        $sql = "INSERT INTO marketplace_unreleased_balance
            (platform, shop_id, shop_name, currency, unreleased_balance, unreleased_order_count, checked_order_count, calculation_method, status, error_message, raw_summary, synced_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                shop_name = VALUES(shop_name),
                currency = VALUES(currency),
                unreleased_balance = VALUES(unreleased_balance),
                unreleased_order_count = VALUES(unreleased_order_count),
                checked_order_count = VALUES(checked_order_count),
                calculation_method = VALUES(calculation_method),
                status = VALUES(status),
                error_message = VALUES(error_message),
                raw_summary = VALUES(raw_summary),
                synced_at = VALUES(synced_at),
                updated_at = VALUES(updated_at)";

        return $this->db->query($sql, $params);
    }

    private function tiktok_shop_finance_request($config, $shop_id, $path, $extra_params = array())
    {
        $app_key = isset($config['app_key']) ? $config['app_key'] : $this->app_key_tiktok;
        $access_token = isset($config['access_token']) ? $config['access_token'] : '';
        $shop_cipher = isset($config['shop']['cipher']) ? $config['shop']['cipher'] : '';
        $app_secret = $this->app_secret_tiktok;
        $timest = time();

        if ($app_key == '' || $access_token == '' || $shop_cipher == '') {
            return array(
                'status' => false,
                'message' => 'Config TikTok tidak lengkap',
                'raw' => ''
            );
        }

        $query_params = array_merge(array(
            'access_token' => $access_token,
            'app_key' => $app_key,
            'shop_cipher' => $shop_cipher,
            'shop_id' => $shop_id,
            'timestamp' => $timest
        ), $extra_params);

        $url = 'https://open-api.tiktokglobalshop.com' . $path . '?' . http_build_query($query_params);

        $sign = $this->tiktok_signature_generator(array(
            'secret' => $app_secret,
            'timest' => $timest,
            'get' => $query_params,
            'post' => '',
            'url' => $url
        ));

        $query_params['sign'] = $sign;
        $url = 'https://open-api.tiktokglobalshop.com' . $path . '?' . http_build_query($query_params);

        $response = $this->curl_json_request($url, array('x-tts-access-token: ' . $access_token), 'GET', null, 20);
        $json = is_array($response['json']) ? $response['json'] : array();
        $success = $response['error'] == '' && isset($json['code']) && intval($json['code']) === 0;

        return array(
            'status' => $success,
            'message' => $success ? 'OK' : ($response['error'] ?: (isset($json['message']) ? $json['message'] : 'Gagal request TikTok finance')),
            'raw' => $response['raw'],
            'json' => $json,
            'http_code' => $response['http_code']
        );
    }

    private function shopee_payment_request($config, $shop_id, $path, $params = array())
    {
        $access_token = isset($config['access_token']) ? $config['access_token'] : '';
        if ($access_token == '') {
            return array(
                'status' => false,
                'message' => 'Access token Shopee tidak ditemukan',
                'raw' => ''
            );
        }

        $host = 'https://partner.shopeemobile.com';
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $timest = time();
        $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
        $sign = hash_hmac('sha256', $baseString, $partner_key);

        $query = array_merge(array(
            'partner_id' => $partner_id,
            'timestamp' => $timest,
            'shop_id' => $shop_id,
            'access_token' => $access_token,
            'sign' => $sign
        ), $params);

        $response = $this->curl_json_request($host . $path . '?' . http_build_query($query), array('Content-Type: application/json'), 'GET', null, 20);
        $json = is_array($response['json']) ? $response['json'] : array();
        $success = $response['error'] == '' && (!isset($json['error']) || $json['error'] == '') && $response['http_code'] >= 200 && $response['http_code'] < 300;

        return array(
            'status' => $success,
            'message' => $success ? 'OK' : ($response['error'] ?: (isset($json['message']) ? $json['message'] : (isset($json['error']) ? $json['error'] : 'Gagal request Shopee payment'))),
            'raw' => $response['raw'],
            'json' => $json,
            'http_code' => $response['http_code']
        );
    }

    private function sync_tiktok_unreleased_balance($start_date, $end_date, $shop_id_filter = '', $limit_per_shop = 100, $verify_api = false, $reset = false)
    {
        $result = array();
        $qry = " AND opt = 'TIKTOK' ";
        if ($shop_id_filter != '') {
            $qry .= " AND shop_id = '" . $this->db->escape_str($shop_id_filter) . "' ";
        }

        $shops = $this->mymodel->selectWithQuery("SELECT * FROM marketplace_config WHERE status = 'Aktif' $qry ORDER BY shop_name ASC");

        foreach ($shops as $shop) {
            $shop_id = $shop['shop_id'];
            $config = json_decode($shop['val'], true);

            if (!$verify_api) {
                $api = $this->tiktok_shop_finance_request($config, $shop_id, '/finance/202507/orders/unsettled', array(
                    'page_size' => 10,
                    'sort_field' => 'order_create_time',
                    'sort_order' => 'DESC'
                ));

                $data = $api['status'] && isset($api['json']['data']) && is_array($api['json']['data']) ? $api['json']['data'] : array();
                $sample_unreleased = array();
                $transactions = isset($data['transactions']) && is_array($data['transactions']) ? $data['transactions'] : array();
                foreach ($transactions as $item) {
                    if (count($sample_unreleased) >= 10) {
                        break;
                    }
                    $sample_unreleased[] = array(
                        'order_id' => isset($item['order_id']) ? $item['order_id'] : '',
                        'amount' => isset($item['est_settlement_amount']) ? $item['est_settlement_amount'] : 0,
                        'status' => isset($item['status']) ? $item['status'] : '',
                        'reason' => isset($item['unsettled_reason']) ? $item['unsettled_reason'] : ''
                    );
                }

                $payload = array(
                    'platform' => 'TIKTOK',
                    'shop_id' => $shop_id,
                    'shop_name' => $shop['shop_name'],
                    'currency' => 'IDR',
                    'unreleased_balance' => isset($data['sum_est_settlement_amount']) ? intval($data['sum_est_settlement_amount']) : 0,
                    'unreleased_order_count' => isset($data['total_count']) ? intval($data['total_count']) : 0,
                    'checked_order_count' => isset($data['total_count']) ? intval($data['total_count']) : 0,
                    'calculation_method' => 'tiktok_unsettled_transactions',
                    'status' => $api['status'] ? 'success' : 'failed',
                    'error_message' => $api['status'] ? null : $api['message'],
                    'raw_summary' => json_encode(array(
                        'date_range' => array('start_date' => $start_date, 'end_date' => $end_date),
                        'verify_api' => false,
                        'source_endpoint' => '/finance/202507/orders/unsettled',
                        'sum_est_revenue_amount' => isset($data['sum_est_revenue_amount']) ? $data['sum_est_revenue_amount'] : null,
                        'sum_est_fee_amount' => isset($data['sum_est_fee_amount']) ? $data['sum_est_fee_amount'] : null,
                        'sum_est_adjustment_amount' => isset($data['sum_est_adjustment_amount']) ? $data['sum_est_adjustment_amount'] : null,
                        'next_page_token' => isset($data['next_page_token']) ? $data['next_page_token'] : '',
                        'sample_unreleased' => $sample_unreleased,
                        'api_message' => $api['message']
                    ))
                );
                $this->save_marketplace_unreleased_balance($payload);
                $result[] = $payload;
                continue;
            }

            $base_where = "
                marketplace = 'TIKTOK'
                AND shop_id = '" . $this->db->escape_str($shop_id) . "'
                AND order_id != ''
                AND DATE(date) BETWEEN '" . $this->db->escape_str($start_date) . "' AND '" . $this->db->escape_str($end_date) . "'
                AND order_status IN ('DELIVERED', 'COMPLETED')
            ";
            $state = array(
                'verify_api' => true,
                'date_range' => array('start_date' => $start_date, 'end_date' => $end_date),
                'offset' => 0,
                'total_candidates' => 0,
                'checked_order_count' => 0,
                'settled_order_count' => 0,
                'failed_order_count' => 0,
                'unreleased_order_count' => 0,
                'unreleased_balance' => 0,
                'sample_unreleased' => array(),
                'errors' => array()
            );

            if (!$reset) {
                $existing = $this->mymodel->selectDataOne('marketplace_unreleased_balance', array('platform' => 'TIKTOK', 'shop_id' => $shop_id));
                if (!empty($existing['raw_summary'])) {
                    $existing_summary = json_decode($existing['raw_summary'], true);
                    if (
                        is_array($existing_summary)
                        && !empty($existing_summary['verify_api'])
                        && isset($existing_summary['date_range']['start_date'])
                        && $existing_summary['date_range']['start_date'] == $start_date
                        && isset($existing_summary['date_range']['end_date'])
                        && $existing_summary['date_range']['end_date'] == $end_date
                        && empty($existing_summary['completed'])
                    ) {
                        $state = array_merge($state, $existing_summary);
                    }
                }
            }

            $total_rows = $this->mymodel->selectWithQuery("SELECT COUNT(*) AS total FROM transaction WHERE $base_where");
            $state['total_candidates'] = isset($total_rows[0]['total']) ? intval($total_rows[0]['total']) : 0;

            $orders = $this->mymodel->selectWithQuery("
                SELECT id, order_id, order_status, customer_price, price_total, omset_bersih, marketplace_fee, komisi_afiliasi, dana_pencairan, pencairan_status, pencairan_at
                FROM transaction
                WHERE $base_where
                ORDER BY date DESC, id DESC
                LIMIT " . intval($limit_per_shop)
                . " OFFSET " . intval($state['offset'])
            );

            $batch_checked = 0;
            $batch_unreleased_count = 0;
            $batch_unreleased_balance = 0;
            $batch_settled_count = 0;
            $batch_failed_count = 0;

            foreach ($orders as $trx) {
                $batch_checked++;
                $state['checked_order_count']++;
                if (doubleval($trx['dana_pencairan']) > 0 || strtolower((string)$trx['pencairan_status']) == 'settlement') {
                    $state['settled_order_count']++;
                    $batch_settled_count++;
                    continue;
                }

                $api = $this->tiktok_shop_finance_request($config, $shop_id, '/finance/202501/orders/' . rawurlencode($trx['order_id']) . '/statement_transactions');
                if ($api['status']) {
                    $data = isset($api['json']['data']) && is_array($api['json']['data']) ? $api['json']['data'] : array();
                    $settlement_amount = isset($data['settlement_amount']) ? doubleval($data['settlement_amount']) : 0;
                    if ($settlement_amount > 0 || !empty($data['settlement_time'])) {
                        $state['settled_order_count']++;
                        $batch_settled_count++;
                        continue;
                    }
                } else {
                    $message = strtolower((string)$api['message']);
                    if (strpos($message, 'permission') !== false || strpos($message, 'access') !== false || strpos($message, 'token') !== false) {
                        $state['failed_order_count']++;
                        $batch_failed_count++;
                        if (count($state['errors']) < 20) {
                            $state['errors'][] = array('order_id' => $trx['order_id'], 'message' => $api['message']);
                        }
                        continue;
                    }
                }

                $amount = $this->estimate_unreleased_amount_from_transaction($trx);
                $state['unreleased_balance'] += $amount;
                $state['unreleased_order_count']++;
                $batch_unreleased_balance += $amount;
                $batch_unreleased_count++;
                if (count($state['sample_unreleased']) < 20) {
                    $state['sample_unreleased'][] = array(
                        'order_id' => $trx['order_id'],
                        'amount' => $amount,
                        'status' => $trx['order_status']
                    );
                }
            }

            $state['offset'] = intval($state['offset']) + $batch_checked;
            $state['completed'] = $state['offset'] >= $state['total_candidates'] || $batch_checked == 0;
            $state['last_batch'] = array(
                'checked_order_count' => $batch_checked,
                'settled_order_count' => $batch_settled_count,
                'failed_order_count' => $batch_failed_count,
                'unreleased_order_count' => $batch_unreleased_count,
                'unreleased_balance' => round($batch_unreleased_balance)
            );

            $status = !empty($state['completed']) ? 'success' : 'processing';
            if ($state['failed_order_count'] > 0 && $state['checked_order_count'] == $state['failed_order_count']) {
                $status = 'failed';
            }
            $error_message = $state['failed_order_count'] > 0 ? 'Sebagian order gagal dicek finance API' : null;

            $payload = array(
                'platform' => 'TIKTOK',
                'shop_id' => $shop_id,
                'shop_name' => $shop['shop_name'],
                'currency' => 'IDR',
                'unreleased_balance' => round($state['unreleased_balance']),
                'unreleased_order_count' => intval($state['unreleased_order_count']),
                'checked_order_count' => intval($state['checked_order_count']),
                'calculation_method' => 'api_batch_statement_transactions',
                'status' => $status,
                'error_message' => $error_message,
                'raw_summary' => json_encode($state)
            );
            $this->save_marketplace_unreleased_balance($payload);
            $result[] = $payload;
        }

        return array('platform' => 'TIKTOK', 'status' => true, 'results' => $result);
    }

    private function sync_shopee_unreleased_balance($start_date, $end_date, $shop_id_filter = '', $limit_per_shop = 100, $verify_api = false, $reset = false)
    {
        $result = array();
        $qry = " AND opt = 'SHOPEE' ";
        if ($shop_id_filter != '') {
            $qry .= " AND shop_id = '" . $this->db->escape_str($shop_id_filter) . "' ";
        }

        $shops = $this->mymodel->selectWithQuery("SELECT * FROM marketplace_config WHERE status = 'Aktif' $qry ORDER BY shop_name ASC");

        foreach ($shops as $shop) {
            $shop_id = $shop['shop_id'];
            $config = json_decode($shop['val'], true);

            if (!$verify_api) {
                $base_where = "
                    marketplace = 'SHOPEE'
                    AND shop_id = '" . $this->db->escape_str($shop_id) . "'
                    AND order_id != ''
                    AND payment_status = 'Paid'
                    AND DATE(date) BETWEEN '" . $this->db->escape_str($start_date) . "' AND '" . $this->db->escape_str($end_date) . "'
                    AND order_status IN ('READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'DELIVERED', 'COMPLETED')
                ";
                $summary_rows = $this->mymodel->selectWithQuery("
                    SELECT
                        COUNT(*) AS checked_order_count,
                        SUM(CASE WHEN dana_pencairan > 0 OR LOWER(pencairan_status) = 'settlement' THEN 0 ELSE 1 END) AS unreleased_order_count,
                        ROUND(SUM(CASE
                            WHEN dana_pencairan > 0 OR LOWER(pencairan_status) = 'settlement' THEN 0
                            WHEN omset_bersih > 0 THEN GREATEST(omset_bersih - marketplace_fee - komisi_afiliasi, 0)
                            WHEN customer_price > 0 THEN customer_price
                            ELSE price_total
                        END)) AS unreleased_balance
                    FROM transaction
                    WHERE $base_where
                ");
                $summary_row = isset($summary_rows[0]) ? $summary_rows[0] : array();
                $sample_unreleased = $this->mymodel->selectWithQuery("
                    SELECT order_id, order_status,
                        ROUND(CASE
                            WHEN omset_bersih > 0 THEN GREATEST(omset_bersih - marketplace_fee - komisi_afiliasi, 0)
                            WHEN customer_price > 0 THEN customer_price
                            ELSE price_total
                        END) AS amount
                    FROM transaction
                    WHERE $base_where
                    AND NOT (dana_pencairan > 0 OR LOWER(pencairan_status) = 'settlement')
                    ORDER BY date DESC
                    LIMIT 10
                ");
                $payload = array(
                    'platform' => 'SHOPEE',
                    'shop_id' => $shop_id,
                    'shop_name' => $shop['shop_name'],
                    'currency' => 'IDR',
                    'unreleased_balance' => isset($summary_row['unreleased_balance']) ? intval($summary_row['unreleased_balance']) : 0,
                    'unreleased_order_count' => isset($summary_row['unreleased_order_count']) ? intval($summary_row['unreleased_order_count']) : 0,
                    'checked_order_count' => isset($summary_row['checked_order_count']) ? intval($summary_row['checked_order_count']) : 0,
                    'calculation_method' => 'local_orders_aggregate',
                    'status' => 'success',
                    'error_message' => null,
                    'raw_summary' => json_encode(array(
                        'date_range' => array('start_date' => $start_date, 'end_date' => $end_date),
                        'verify_api' => false,
                        'sample_unreleased' => $sample_unreleased
                    ))
                );
                $this->save_marketplace_unreleased_balance($payload);
                $result[] = $payload;
                continue;
            }

            $base_where = "
                marketplace = 'SHOPEE'
                AND shop_id = '" . $this->db->escape_str($shop_id) . "'
                AND order_id != ''
                AND payment_status = 'Paid'
                AND DATE(date) BETWEEN '" . $this->db->escape_str($start_date) . "' AND '" . $this->db->escape_str($end_date) . "'
                AND order_status IN ('READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'DELIVERED', 'COMPLETED')
            ";
            $state = array(
                'verify_api' => true,
                'date_range' => array('start_date' => $start_date, 'end_date' => $end_date),
                'offset' => 0,
                'total_candidates' => 0,
                'checked_order_count' => 0,
                'released_order_count' => 0,
                'failed_order_count' => 0,
                'unreleased_order_count' => 0,
                'unreleased_balance' => 0,
                'sample_unreleased' => array(),
                'errors' => array()
            );

            if (!$reset) {
                $existing = $this->mymodel->selectDataOne('marketplace_unreleased_balance', array('platform' => 'SHOPEE', 'shop_id' => $shop_id));
                if (!empty($existing['raw_summary'])) {
                    $existing_summary = json_decode($existing['raw_summary'], true);
                    if (
                        is_array($existing_summary)
                        && !empty($existing_summary['verify_api'])
                        && isset($existing_summary['date_range']['start_date'])
                        && $existing_summary['date_range']['start_date'] == $start_date
                        && isset($existing_summary['date_range']['end_date'])
                        && $existing_summary['date_range']['end_date'] == $end_date
                        && empty($existing_summary['completed'])
                    ) {
                        $state = array_merge($state, $existing_summary);
                    }
                }
            }

            $total_rows = $this->mymodel->selectWithQuery("SELECT COUNT(*) AS total FROM transaction WHERE $base_where");
            $state['total_candidates'] = isset($total_rows[0]['total']) ? intval($total_rows[0]['total']) : 0;

            $orders = $this->mymodel->selectWithQuery("
                SELECT id, order_id, order_status, customer_price, price_total, omset_bersih, marketplace_fee, komisi_afiliasi, dana_pencairan, pencairan_status, pencairan_at
                FROM transaction
                WHERE $base_where
                ORDER BY date DESC, id DESC
                LIMIT " . intval($limit_per_shop)
                . " OFFSET " . intval($state['offset'])
            );

            $batch_checked = 0;
            $batch_unreleased_count = 0;
            $batch_unreleased_balance = 0;
            $batch_released_count = 0;
            $batch_failed_count = 0;

            foreach ($orders as $trx) {
                $batch_checked++;
                $state['checked_order_count']++;
                if (doubleval($trx['dana_pencairan']) > 0 || strtolower((string)$trx['pencairan_status']) == 'settlement') {
                    $state['released_order_count']++;
                    $batch_released_count++;
                    continue;
                }

                $api = $this->shopee_payment_request($config, $shop_id, '/api/v2/payment/get_escrow_detail', array('order_sn' => $trx['order_id']));
                $escrow_amount = 0;
                if ($api['status']) {
                    $income = isset($api['json']['response']['order_income']) && is_array($api['json']['response']['order_income']) ? $api['json']['response']['order_income'] : array();
                    $escrow_amount = isset($income['escrow_amount']) ? doubleval($income['escrow_amount']) : 0;
                } else {
                    $state['failed_order_count']++;
                    $batch_failed_count++;
                    if (count($state['errors']) < 20) {
                        $state['errors'][] = array('order_id' => $trx['order_id'], 'message' => $api['message']);
                    }
                }

                $amount = $escrow_amount > 0 ? $escrow_amount : $this->estimate_unreleased_amount_from_transaction($trx);
                if ($amount <= 0) {
                    continue;
                }

                $state['unreleased_balance'] += $amount;
                $state['unreleased_order_count']++;
                $batch_unreleased_balance += $amount;
                $batch_unreleased_count++;
                if (count($state['sample_unreleased']) < 20) {
                    $state['sample_unreleased'][] = array(
                        'order_id' => $trx['order_id'],
                        'amount' => $amount,
                        'status' => $trx['order_status']
                    );
                }
            }

            $state['offset'] = intval($state['offset']) + $batch_checked;
            $state['completed'] = $state['offset'] >= $state['total_candidates'] || $batch_checked == 0;
            $state['last_batch'] = array(
                'checked_order_count' => $batch_checked,
                'released_order_count' => $batch_released_count,
                'failed_order_count' => $batch_failed_count,
                'unreleased_order_count' => $batch_unreleased_count,
                'unreleased_balance' => round($batch_unreleased_balance)
            );

            $status = !empty($state['completed']) ? 'success' : 'processing';
            if ($state['failed_order_count'] > 0 && $state['checked_order_count'] == $state['failed_order_count']) {
                $status = 'failed';
            }
            $error_message = $state['failed_order_count'] > 0 ? 'Sebagian order gagal dicek payment API' : null;

            $payload = array(
                'platform' => 'SHOPEE',
                'shop_id' => $shop_id,
                'shop_name' => $shop['shop_name'],
                'currency' => 'IDR',
                'unreleased_balance' => round($state['unreleased_balance']),
                'unreleased_order_count' => intval($state['unreleased_order_count']),
                'checked_order_count' => intval($state['checked_order_count']),
                'calculation_method' => 'api_batch_escrow_detail',
                'status' => $status,
                'error_message' => $error_message,
                'raw_summary' => json_encode($state)
            );
            $this->save_marketplace_unreleased_balance($payload);
            $result[] = $payload;
        }

        return array('platform' => 'SHOPEE', 'status' => true, 'results' => $result);
    }

    public function cronjob_marketplace_unreleased_balance()
    {
        header('Content-Type: application/json; charset=utf-8');

        $platform = isset($_GET['platform']) ? strtoupper(trim($_GET['platform'])) : '';
        $shop_id = isset($_GET['shop_id']) ? trim($_GET['shop_id']) : '';
        $days = isset($_GET['days']) ? intval($_GET['days']) : 45;
        if ($days <= 0) {
            $days = 45;
        }
        if ($days > 120) {
            $days = 120;
        }

        $limit_per_shop = isset($_GET['limit_per_shop']) ? intval($_GET['limit_per_shop']) : 100;
        if ($limit_per_shop <= 0) {
            $limit_per_shop = 100;
        }
        $verify_api = isset($_GET['verify_api']) && in_array(strtolower(trim((string)$_GET['verify_api'])), array('1', 'true', 'yes', 'on'));
        $reset = isset($_GET['reset']) && in_array(strtolower(trim((string)$_GET['reset'])), array('1', 'true', 'yes', 'on'));

        $start_date = isset($_GET['start_date']) && $_GET['start_date'] != '' ? $_GET['start_date'] : date('Y-m-d', strtotime('-' . $days . ' days'));
        $end_date = isset($_GET['end_date']) && $_GET['end_date'] != '' ? $_GET['end_date'] : date('Y-m-d');

        $response = array(
            'status' => true,
            'message' => 'Sync saldo marketplace belum cair selesai',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'verify_api' => $verify_api,
            'reset' => $reset,
            'synced_at' => date('Y-m-d H:i:s'),
            'results' => array()
        );

        if ($platform == '' || $platform == 'TIKTOK') {
            $response['results'][] = $this->sync_tiktok_unreleased_balance($start_date, $end_date, $shop_id, $limit_per_shop, $verify_api, $reset);
        }
        if ($platform == '' || $platform == 'SHOPEE') {
            $response['results'][] = $this->sync_shopee_unreleased_balance($start_date, $end_date, $shop_id, $limit_per_shop, $verify_api, $reset);
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    function update_cronjob(){
        $platform = 'Tiktok';
        // $data = $this->mymodel->selectWithQuery("SELECT link_upload FROM `endorse` WHERE DATE(created_at) BETWEEN '2025-05-01' AND '2025-05-25' AND platform = 'Tiktok' AND link_upload != '' AND id_campaign = 54 LIMIT 10");
        
        // foreach ($data as $row) {
        //     $link_upload = $row['link_upload'];
        //     $response = $this->template->get_social_media($platform, $link_upload);
            
        //     // Cetak response jika perlu
        //     print_r($response);
        // }
        $response = $this->template->get_account_id($platform, 'https://www.tiktok.com/@xxmivaxx4');
        print_r($response);
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
    function interpolateVar($value, $env)
    {
        foreach ($env as $key => $val) {
            $value = str_replace("{{" . $key . "}}", $val, $value);
        }
        return $value;
    }

    function tiktok_signature_generator($dt)
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
        // $signstring .=  $secret;
        $signstring .= $dt['post'] . $secret;

        $sign = hash_hmac("sha256", $signstring, $secret);
        return $sign;
    }

    public function marketplace_callback_shopee()
    {
        $marketplace = "SHOPEE";
        $dt = $_GET;

        $host = 'https://partner.shopeemobile.com';
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $code = $dt['code'];
        $shop_id = $dt['shop_id'];
        $path = "/api/v2/auth/token/get";
        $timest = time();
        $body = array("code" => $code,  "shop_id" => intval($shop_id), "partner_id" => intval($partner_id));
        $baseString = sprintf("%s%s%s", $partner_id, $path, $timest);
        $sign = hash_hmac('sha256', $baseString, $partner_key);
        $url = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s", $host, $path, $partner_id, $timest, $sign);

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($c);
        $response = json_decode($response, true);

        $access_token = $response['access_token'];
        $refresh_token = $response['refresh_token'];
        $expired_at = time() + $response['expire_in'];
        if (empty($access_token)) {
            echo 'Koneksi shopee tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $path = "/api/v2/shop/get_profile";

        $timest = time();
        $body = array("partner_id" => intval($partner_id), "shop_id" => intval($shop_id), "refresh_token" => $refresh_token);
        $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
        $sign = hash_hmac('sha256', $baseString, $partner_key);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
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



        $shop_id = $shop_id;
        $shop_name = $response['response']['shop_name'];
        $shop = $response['response'];
        $img_url = $response['response']['shop_logo'];

        if (empty($shop_id)) {
            echo 'Toko shopee tidak ditemukan. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $check = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $shop_id));

        $dt = array();
        if ($img_url) {
            $file_name = $shop_id . '.jpg';
            $img_dir = './assets/img/marketplace_account/' . $file_name;
            file_put_contents($img_dir, file_get_contents($img_url));
            $dt['img'] = $file_name;
        }
        $config = array();
        $config['partner_id'] = $partner_id;
        $config['access_token'] = $access_token;
        $config['refresh_token'] = $refresh_token;
        $config['shop'] = $shop;
        $dt['val'] = json_encode($config, true);
        $dt['opt'] = $marketplace;
        $dt['status'] = "Aktif";
        $dt['shop_id'] = $shop_id;
        $dt['shop_name'] = $shop_name;
        if ($check) {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->update('marketplace_config', $dt, array('id' => $check['id']));
        } else {
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['created_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->insert('marketplace_config', $dt);
        }
        return redirect(base_url() . 'marketplace-account');
    }

    public function marketplace_callback_shopee_analytics()
    {
        $marketplace = "SHOPEE";
        $dt = $_GET;

        $host = 'https://partner.shopeemobile.com';
        $partner_id = app_env('SHOPEE_ANALYTICS_PARTNER_ID');
        $partner_key = app_env('SHOPEE_ANALYTICS_PARTNER_KEY');
        $code = $dt['code'];
        $shop_id = $dt['shop_id'];
        $path = "/api/v2/auth/token/get";
        $timest = time();
        $body = array("code" => $code,  "shop_id" => intval($shop_id), "partner_id" => intval($partner_id));
        $baseString = sprintf("%s%s%s", $partner_id, $path, $timest);
        $sign = hash_hmac('sha256', $baseString, $partner_key);
        $url = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s", $host, $path, $partner_id, $timest, $sign);

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($c);
        $response = json_decode($response, true);

        $access_token = $response['access_token'];
        $refresh_token = $response['refresh_token'];
        $expired_at = time() + $response['expire_in'];
        if (empty($access_token)) {
            echo 'Koneksi shopee tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $path = "/api/v2/shop/get_profile";

        $timest = time();
        $body = array("partner_id" => intval($partner_id), "shop_id" => intval($shop_id), "refresh_token" => $refresh_token);
        $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
        $sign = hash_hmac('sha256', $baseString, $partner_key);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
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



        $shop_id = $shop_id;
        $shop_name = $response['response']['shop_name'];
        $shop = $response['response'];
        $img_url = $response['response']['shop_logo'];

        if (empty($shop_id)) {
            echo 'Toko shopee tidak ditemukan. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $dt = array();
        if ($img_url) {
            $file_name = $shop_id . '.jpg';
            $img_dir = './assets/img/marketplace_account/' . $file_name;
            file_put_contents($img_dir, file_get_contents($img_url));
            $dt['img'] = $file_name;
        }
        $config = array();
        $config['partner_id'] = $partner_id;
        $config['access_token'] = $access_token;
        $config['refresh_token'] = $refresh_token;
        $config['shop'] = $shop;
        $dt['val'] = json_encode($config, true);
        $dt['opt'] = $marketplace;
        $dt['status'] = "Aktif";
        $dt['shop_id'] = $shop_id;
        $dt['shop_name'] = $shop_name;
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $_SESSION['user']['id'];
        $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
        $dt['is_analytics'] = 1;
        print_r($dt);die;
    }


    public function marketplace_callback_lazada()
    {
        $marketplace = "LAZADA";
        $dt = $_GET;
        $code = $dt['code'];
        $app_key = $this->app_key_lazada;
        $app_secret = $this->app_secret_lazada;

        $url = 'https://api.lazada.co.id/rest';

        $c = new LazopClient($url, $app_key, $app_secret);
        $request = new LazopRequest('/auth/token/create');
        $request->addApiParam('code', $code);

        $response = $c->execute($request);
        $response = json_decode($response, true);

        $access_token = $response['access_token'];
        $refresh_token = $response['refresh_token'];
        $expired_at = time() + $response['expires_in'];

        // print_r($response);

        if (empty($access_token)) {
            echo 'Koneksi lazada tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $c = new LazopClient($url, $app_key, $app_secret);
        $request = new LazopRequest('/seller/get', 'GET');
        $response = $c->execute($request, $access_token);
        $response = json_decode($response, true);


        $shop_id = $response['data']['seller_id'];
        $shop_name = $response['data']['name'];
        $shop = $response['data'];
        $img_url = $response['data']['logo_url'];


        if (empty($shop_id)) {
            echo 'Toko lazada tidak ditemukan. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $check = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $shop_id));

        $dt = array();
        if ($img_url) {
            $file_name = $check['id'] . '.jpg';
            $img_dir = './assets/img/marketplace_account/' . $file_name;
            file_put_contents($img_dir, file_get_contents($img_url));
            $dt['img'] = $file_name;
        }
        $config = array();
        $config['app_key'] = $app_key;
        $config['access_token'] = $access_token;
        $config['refresh_token'] = $refresh_token;
        $config['shop'] = $shop;
        $dt['val'] = json_encode($config, true);
        $dt['opt'] = $marketplace;
        $dt['status'] = "Aktif";
        $dt['shop_id'] = $shop_id;
        $dt['shop_name'] = $shop_name;
        if ($check) {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->update('marketplace_config', $dt, array('id' => $check['id']));
        } else {
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['created_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->insert('marketplace_config', $dt);
        }
        return redirect(base_url() . 'marketplace-account');
    }

    public function marketplace_callback_tiktok()
    {
        $marketplace = "TIKTOK";
        $dt = $_GET;
        $app_key = $dt['app_key'];
        $code = $dt['code'];
        $app_secret = $this->app_secret_tiktok;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'auth.tiktok-shops.com/api/v2/token/get?app_key=' . $app_key . '&app_secret=' . $app_secret . '&auth_code=' . $code . '&grant_type=authorized_code',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        $access_token = $response['data']['access_token'];
        $refresh_token = $response['data']['refresh_token'];
        $expired_at = $response['data']['access_token_expire_in'];
        if (empty($access_token)) {
            echo 'Koneksi tiktok tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $url = 'https://open-api.tiktokglobalshop.com/authorization/202309/shops?app_key=' . $app_key . '&sign={{sign}}&timestamp={{timestamp}}';
        $urlParts = parse_url($url);
        $paramGET = [];
        parse_str($urlParts['query'], $paramGET);
        $secret = $this->app_secret_tiktok;
        $timest = strtotime('now');
        $pr = array();
        $pr['secret'] = $secret;
        $pr['timest'] = $timest;
        $pr['get'] = $paramGET;
        $pr['url'] = $url;
        $sign = $this->tiktok_signature_generator($pr);

        $url = str_replace('{{sign}}', $sign, $url);
        $url = str_replace('{{timestamp}}', $timest, $url);

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
                'x-tts-access-token: ' . $access_token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        $shop_id = $response['data']['shops'][0]['id'];
        $shop_name = $response['data']['shops'][0]['name'];
        $shop = $response['data']['shops'][0];

        if (empty($shop_id)) {
            echo 'Toko tiktok tidak ditemukan. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $check = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $shop_id));

        $dt = array();
        $config = array();
        $config['app_key'] = $app_key;
        $config['access_token'] = $access_token;
        $config['refresh_token'] = $refresh_token;
        $config['shop'] = $shop;
        $dt['val'] = json_encode($config, true);
        $dt['opt'] = $marketplace;
        $dt['status'] = "Aktif";
        $dt['shop_id'] = $shop_id;
        $dt['shop_name'] = $shop_name;

        if ($check) {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->update('marketplace_config', $dt, array('id' => $check['id']));
        } else {
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['created_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->insert('marketplace_config', $dt);
        }
        return redirect(base_url() . 'marketplace-account');
    }

    function marketplace_token_refresh()
    {
        header('Content-Type: application/json; charset=utf-8');
        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $marketplace = strtoupper($marketplace);
        $shop_id = $dt['shop_id'];
        $qry = "";
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }
        if ($marketplace) {
            $qry .= " AND opt = '$marketplace' ";
        }
        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace_config
        WHERE status = 'Aktif' $qry");

        $is_error = false;
        $text = '';

        foreach ($data as $k => $v) {
            if ($v['opt'] == "TIKTOK") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $app_key = $this->app_key_tiktok;
                $refresh_token = $config['refresh_token'];
                $app_secret = $this->app_secret_tiktok;

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'auth.tiktok-shops.com/api/v2/token/refresh?app_key=' . $app_key . '&app_secret=' . $app_secret . '&refresh_token=' . $refresh_token . '&grant_type=refresh_token',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                ));

                $response = curl_exec($curl);
                curl_close($curl);

                $response = json_decode($response, true);

                if ($response['data']['access_token']) {

                    $access_token = $response['data']['access_token'];
                    $refresh_token = $response['data']['refresh_token'];
                    $expired_at = $response['data']['access_token_expire_in'];

                    $url = 'https://open-api.tiktokglobalshop.com/authorization/202309/shops?app_key=' . $app_key . '&sign={{sign}}&timestamp={{timestamp}}';
                    $urlParts = parse_url($url);
                    $paramGET = [];
                    parse_str($urlParts['query'], $paramGET);
                    $secret = $this->app_secret_tiktok;
                    $timest = strtotime('now');
                    $pr = array();
                    $pr['secret'] = $secret;
                    $pr['timest'] = $timest;
                    $pr['get'] = $paramGET;
                    $pr['url'] = $url;
                    $sign = $this->tiktok_signature_generator($pr);

                    $url = str_replace('{{sign}}', $sign, $url);
                    $url = str_replace('{{timestamp}}', $timest, $url);

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
                            'x-tts-access-token: ' . $access_token
                        ),
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);

                    $response = json_decode($response, true);

                    $shop_id = $response['data']['shops'][0]['id'];
                    $shop_name = $response['data']['shops'][0]['name'];
                    $shop = $response['data']['shops'][0];

                    $dt = array();
                    $config = array();
                    $config['app_key'] = $app_key;
                    $config['access_token'] = $access_token;
                    $config['refresh_token'] = $refresh_token;
                    $config['shop'] = $shop;
                    $dt['val'] = json_encode($config, true);
                    $dt['opt'] = $marketplace;
                    $dt['status'] = "Aktif";
                    $dt['shop_id'] = $shop_id;
                    $dt['shop_name'] = $shop_name;
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['refresh_token_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = $_SESSION['user']['id'];
                    $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
                    if ($shop_id) {
                        $this->db->update('marketplace_config', $dt, array('id' => $v['id']));
                    } else {
                        $is_error = true;
                        $text .= 'Toko ' . $v['shop_name'] . ' tidak ditemukan!<br>';
                    }
                }
            } else if ($v['opt'] == "SHOPEE") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $refresh_token = $config['refresh_token'];
                $shop_id = $v['shop_id'];

                $host = 'https://partner.shopeemobile.com';
                $path = "/api/v2/auth/access_token/get";
                $timest = time();
                $body = array("partner_id" => intval($partner_id), "shop_id" => intval($shop_id), "refresh_token" => $refresh_token);
                $baseString = sprintf("%s%s%s", $partner_id, $path, $timest);
                $sign = hash_hmac('sha256', $baseString, $partner_key);
                $url = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s", $host, $path, $partner_id, $timest, $sign);

                $c = curl_init($url);
                curl_setopt($c, CURLOPT_POST, 1);
                curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($body));
                curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

                $response = curl_exec($c);
                $response = json_decode($response, true);

                $access_token = $response['access_token'];
                $refresh_token = $response['refresh_token'];
                $expired_at = time() + $response['expire_in'];

                if ($access_token) {
                    $path = "/api/v2/shop/get_profile";

                    $timest = time();
                    $body = array("partner_id" => intval($partner_id), "shop_id" => intval($shop_id), "refresh_token" => $refresh_token);
                    $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                    $sign = hash_hmac('sha256', $baseString, $partner_key);

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
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


                    $shop_id = $shop_id;
                    $shop_name = $response['response']['shop_name'];
                    $shop = $response['response'];
                    $img_url = $response['response']['shop_logo'];
                    if ($shop_id) {
                        $dt = array();
                        if ($img_url) {
                            $file_name = $shop_id . '.jpg';
                            $img_dir = './assets/img/marketplace_account/' . $file_name;
                            file_put_contents($img_dir, file_get_contents($img_url));
                            $dt['img'] = $file_name;
                        }

                        $config = array();
                        $config['partner_id'] = $partner_id;
                        $config['access_token'] = $access_token;
                        $config['refresh_token'] = $refresh_token;
                        $config['shop'] = $shop;
                        $dt['val'] = json_encode($config, true);
                        $dt['opt'] = $marketplace;
                        $dt['status'] = "Aktif";
                        $dt['shop_id'] = $shop_id;
                        $dt['shop_name'] = $shop_name;
                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $dt['refresh_token_at'] = DATE("Y-m-d H:i:s");
                        $dt['updated_by'] = $_SESSION['user']['id'];
                        $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
                        $this->db->update('marketplace_config', $dt, array('id' => $v['id']));
                    } else {
                        $is_error = true;
                        $text .= 'Toko ' . $v['shop_name'] . ' tidak ditemukan!<br>';
                    }
                } else {
                    $is_error = true;
                    $text .= 'Refresh token shopee id : ' . $v['shop_id'] . ' tidak valid!<br>';
                }
            } else if ($v['opt'] == "LAZADA") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $refresh_token = $config['refresh_token'];
                $url = 'https://api.lazada.co.id/rest';

                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/auth/token/refresh');
                $request->addApiParam('refresh_token', $refresh_token);
                $response = $c->execute($request);
                $response = json_decode($response, true);

                $access_token = $response['access_token'];
                $refresh_token = $response['refresh_token'];
                $expired_at = time() + $response['expires_in'];


                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/seller/get', 'GET');
                $response = $c->execute($request, $access_token);
                $response = json_decode($response, true);

                $shop_id = $response['data']['seller_id'];
                $shop_name = $response['data']['name'];
                $shop = $response['data'];
                $img_url = $response['data']['logo_url'];

                $dt = array();
                if ($img_url) {
                    $file_name = $shop_id . '.jpg';
                    $img_dir = './assets/img/marketplace_account/' . $file_name;
                    file_put_contents($img_dir, file_get_contents($img_url));
                    $dt['img'] = $file_name;
                }

                $config = array();
                $config['app_key'] = $app_key;
                $config['access_token'] = $access_token;
                $config['refresh_token'] = $refresh_token;
                $config['shop'] = $shop;
                $dt['val'] = json_encode($config, true);
                $dt['opt'] = $marketplace;
                $dt['status'] = "Aktif";
                $dt['shop_id'] = $shop_id;
                $dt['shop_name'] = $shop_name;
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['refresh_token_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = $_SESSION['user']['id'];
                $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
                if ($shop_id) {
                    $this->db->update('marketplace_config', $dt, array('id' => $v['id']));
                } else {
                    $is_error = true;
                    $text .= 'Toko ' . $v['shop_name'] . ' tidak ditemukan!<br>';
                }
            } else if ($v['opt'] == "META") {
                $marketplace = $v['opt'];
                $app_id = $this->app_id_meta;
                $app_secret = $this->app_secret_meta;
                $config = json_decode($v['val'], true);
                $refresh_token = $config['access_token'];

                $url = "https://graph.facebook.com/v21.0/oauth/access_token";

                $params = array(
                    'grant_type' => 'fb_exchange_token',
                    'client_id' => $app_id,
                    'client_secret' => $app_secret,
                    'fb_exchange_token' => $refresh_token
                );

                $url_with_params = $url . '?' . http_build_query($params);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url_with_params);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                curl_close($ch);

                $response_data = json_decode($response, true);

                $config = array();
                $config['app_id'] = $app_id;
                $config['access_token'] = $refresh_token;
                $config['refresh_token'] = $refresh_token;
                $dt['val'] = json_encode($config, true);
                $dt['opt'] = $marketplace;
                $dt['status'] = "Aktif";
                $dt['shop_id'] = $app_id;
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['refresh_token_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = $_SESSION['user']['id'];
                $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
                if ($app_id) {
                    $sql = "UPDATE marketplace_config 
                            SET val = ? 
                            WHERE shop_id = ?";
                    $this->db->query($sql, array($dt['val'], $app_id));
                } else {
                    $is_error = true;
                    $text .= 'Toko ' . $v['shop_name'] . ' tidak ditemukan!<br>';
                }
            }
        }
        if ($is_error) {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = $text;
            echo json_encode($html, true);
            die;
        } else {
            $html['status'] = true;
            $html['data'] = array();
            $html['msg'] = 'Refresh token berhasil!';
            echo json_encode($html, true);
            die;
        }
    }

    function customer_summary()
    {
        $id_customer = $_GET['id'];
        $this->buyer_summary($id_customer);

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Pembaharuan customer order berhasil!';
        echo json_encode($html, true);
        die;
    }

    function buyer_summary($id_customer, $keluhan_data = null)
    {

        $dtt = array();
        $dt = array();
        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count, 
        SUM(omset_kotor) as omset_kotor,
        SUM(komisi_afiliasi) as komisi_afiliasi,
        SUM(diskon_penjual) as diskon_penjual,
        SUM(omset_bersih) as omset_bersih,
        SUM(dana_pencairan) as dana_pencairan,
        SUM(price_total) as price_total,
        SUM(marketplace_fee) as marketplace_fee,
        SUM(customer_price) as customer_price,
        SUM(transaction.return) as returnn FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS'
        AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')
        ORDER BY transaction.date ASC
        ");

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

        $query = $this->mymodel->selectWithQuery("SELECT
        transaction.marketplace,
        transaction.date,
        order_id,
        order_status,
        pesanan,
        transaction.json,
        is_manual,
        (omset_kotor) as omset_kotor,
        (komisi_afiliasi) as komisi_afiliasi,
        (diskon_penjual) as diskon_penjual,
        (omset_bersih) as omset_bersih,
        (dana_pencairan) as dana_pencairan,
        (price_total) as price_total,
        (marketplace_fee) as marketplace_fee,
        (customer_price) as customer_price,
        (transaction.return) as returnn FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS'
        AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')
        ORDER BY transaction.date ASC");

        $dtt['first_order'] = strval($query[0]['date']);

        $last_query = end($query);
        $today = strtotime(date('Y-m-d'));
        $last_order_ts = !empty($last_query['date']) ? strtotime(date('Y-m-d', strtotime($last_query['date']))) : null;
        $first_order_ts = !empty($dtt['first_order']) ? strtotime(date('Y-m-d', strtotime($dtt['first_order']))) : null;
        $days_since_last_order = ($last_order_ts !== null) ? (int) floor(($today - $last_order_ts) / 86400) : null;
        $days_since_first_order = ($first_order_ts !== null) ? (int) floor(($today - $first_order_ts) / 86400) : null;
        $total_order = count($query);
        $completed_order_count = 0;
        foreach ($query as $order_row) {
            if (strtoupper((string)($order_row['order_status'] ?? '')) === 'COMPLETED') {
                $completed_order_count++;
            }
        }
        $dtt['marketplace'] = $last_query['marketplace'];
        $dtt['last_order'] = "";
        if ($last_query['date'] && $last_query['date'] != $dtt['first_order']) {
            $dtt['last_order'] = strval($last_query['date']);
        }

        if ($days_since_last_order !== null && $days_since_last_order > 180) {
            $segment_cb_cl = "Churn / Lost";
        } elseif ($days_since_last_order !== null && $days_since_last_order >= 90) {
            $segment_cb_cl = "Customer Pasif";
        } elseif ($total_order == 1 && $days_since_first_order !== null && $days_since_first_order <= 30) {
            $segment_cb_cl = "Customer Baru";
        } elseif ($total_order >= 3 && $days_since_last_order !== null && $days_since_last_order <= 90) {
            $segment_cb_cl = "Customer Loyal";
        } elseif ($completed_order_count >= 2 && $days_since_last_order !== null && $days_since_last_order <= 60) {
            $segment_cb_cl = "Repeat Buyer";
        } elseif ($completed_order_count >= 2) {
            $segment_cb_cl = "Repeat Buyer";
        } else {
            $segment_cb_cl = "Customer Baru";
        }

        if ($query) {
            $dt['cb_cl'] = $segment_cb_cl;
            $this->db->update('transaction', $dt, array('customer' => $id_customer));
        }


        $json = array();
        $price_total_hpp = 0;
        foreach ($query as $kk => $vv) {
            $json[$kk]['date'] = $vv['date'];
            $json[$kk]['id'] = $vv['id'];
            $json[$kk]['order_id'] = $vv['order_id'];
            $json[$kk]['order_status'] = $vv['order_status'];
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
        if (empty($query)) {
            $this->db->delete('customer', array('id' => $id_customer));
        } else {
            $dtt['cb_cl'] = $segment_cb_cl;
            if (is_array($keluhan_data) && !empty($keluhan_data['status'])) {
                $existing_customer = $this->mymodel->selectDataOne('customer', array('id' => $id_customer));
                $keluhan_date = trim((string)($keluhan_data['last_chat_at'] ?? date("Y-m-d H:i:s")));
                $keluhan_labels = isset($keluhan_data['labels']) && is_array($keluhan_data['labels']) ? $keluhan_data['labels'] : array();
                $keluhan_summary = trim((string)($keluhan_data['summary'] ?? ''));

                if (!empty($keluhan_labels)) {
                    $dtt['label_keluhan'] = $this->append_customer_history_json(
                        $existing_customer['label_keluhan'] ?? '',
                        array(
                            'date' => $keluhan_date,
                            'source' => 'webhook_chat_summary',
                            'labels' => array_values($keluhan_labels)
                        ),
                        'labels'
                    );
                }

                if ($keluhan_summary !== '') {
                    $dtt['riwayat_keluhan'] = $this->append_customer_history_json(
                        $existing_customer['riwayat_keluhan'] ?? '',
                        array(
                            'date' => $keluhan_date,
                            'source' => 'webhook_chat_summary',
                            'summary' => $keluhan_summary
                        ),
                        'summary'
                    );
                }
            }
            $this->db->update('customer', $dtt, array('id' => $id_customer));
        }

        return $dt;
    }

    function calculate_buyer($dt)
    {
        $user = $_SESSION['user'];
    
        if (is_string($dt['pesanan'])) {
            $dt['pesanan'] = json_decode($dt['pesanan'], true);
        }
    
        $sku_list = [];
        foreach ($dt['pesanan'] as $item) {
            $sku_cleaned = preg_replace('/^\d+-/', '', $item['sku']);
            $sku_list[] = "'" . $sku_cleaned . "'"; 
        }
    
        if (!empty($sku_list)) {
            $sku_values = implode(',', $sku_list); 
            $query = "SELECT DISTINCT p.sku, p.brand FROM product p WHERE p.is_varian = 0 AND p.sku IN ($sku_values)";
            $result = $this->mymodel->selectWithQuery($query);
        } else {
            $result = [];
        }

        $brand_list = array_column($result, 'brand');

        $dt['brand'] = implode(', ', array_unique($brand_list));

        $data = $this->mymodel->selectDataOne('customer', ['id_buyer' => $dt['id_buyer'], 'marketplace' => $dt['marketplace']]);
        

        if (empty($data)) {
            $dtt = [
                'id_buyer' => strval($dt['id_buyer']),
                'akun_type' => $dt['c_type'],
                'brand' => $dt['brand'],
                'marketplace' => $dt['marketplace'],
                'id_buyer' => strval($dt['id_buyer']),
                'status' => "Aktif",
                'created_at' => date("Y-m-d H:i:s"),
                'created_by' => strval($user['id']),
                'full_name' => $dt['customer_text'],
                'phone' => $dt['phone'],
                'username' => strval($dt['c_username']),
                'count_order' => 1,
                'first_order' => strval($dt['date']),
                'last_order' => strval($dt['date']),
                'address' => $dt['address'],
                'province_text' => $dt['province_text'],
                'city_text' => $dt['city_text'],
                'subdistrict_text' => $dt['subdistrict_text'],
                'shop_id' => $dt['shop_id'],
                'shop_name' => $dt['shop_name']
            ];
            $this->db->insert('customer', $dtt);
            $id_buyer = $this->db->insert_id();
        } else {
            $dtt = [
                'id_buyer' => strval($dt['id_buyer']),
                'updated_at' => date("Y-m-d H:i:s"),
                'shop_id' => $dt['shop_id'],
                'shop_name' => $dt['shop_name'],
                'brand' => $dt['brand'],
                'akun_type' => $dt['c_type'],
            ];
            $this->db->update('customer', $dtt, ['id' => $data['id']]);
            $id_buyer = $data['id'];
        }

        $this->db->update('transaction', ['customer' => $id_buyer], ['id' => $dt['id']]);

        $keluhan_data = null;
        if (
            strtoupper((string)($dt['marketplace'] ?? '')) === 'SHOPEE' &&
            in_array(strtoupper((string)($dt['order_status'] ?? '')), array('DELIVERED', 'COMPLETED', 'SHIPPED'), true) &&
            !empty($dt['id_buyer'])
        ) {
            $keluhan_data = $this->request_webhook_chat_summary_by_sender_id($dt['id_buyer']);
        }

        $this->buyer_summary($id_buyer, $keluhan_data);
    }


    public function update_stock($id_product)
    {
        $dtp = array();
        $id_product = $id_product;
        $query = $this->mymodel->selectWithQuery("SELECT SUM(qty_in) as qty_in,SUM(qty_in_pos) as qty_in_pos,SUM(qty_out) as qty_out,SUM(qty_out_pos) as qty_out_pos,SUM(qty) as qty FROM stock WHERE product = '$id_product'");

        $dtp['stock_in'] = strval($query[0]['qty_in']);
        $dtp['stock_in_pos'] = strval($query[0]['qty_in_pos']);
        $dtp['stock_out'] = strval(abs($query[0]['qty_out']) * -1);
        $dtp['stock_out_pos'] = strval(abs($query[0]['qty_out_pos']) * -1);
        $dtp['stock'] = strval(doubleval($query[0]['qty']));
        $this->db->update('product', $dtp, array('id' => $id_product));
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
    function calculate_stock_product_by_id($id)
    {
        $product = $this->mymodel->selectWithQuery("SELECT * FROM product 
        WHERE id = '$id'
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

    private function get_erp_stock_out_url()
    {
        $url = $this->config->item('erp_stock_out_url');
        if (empty($url)) {
            $url = getenv('ERP_STOCK_OUT_URL');
        }
        if (empty($url) && !empty($_ENV['ERP_STOCK_OUT_URL'])) {
            $url = $_ENV['ERP_STOCK_OUT_URL'];
        }
        if (empty($url) && !empty($_SERVER['ERP_STOCK_OUT_URL'])) {
            $url = $_SERVER['ERP_STOCK_OUT_URL'];
        }

        return $url ? trim($url) : 'https://finance.bhskin.co.id/api/erp/stock-out';
    }

    private function get_erp_api_token()
    {
        $token = $this->config->item('erp_api_token');
        if (empty($token)) {
            $token = getenv('ERP_API_TOKEN');
        }
        if (empty($token) && !empty($_ENV['ERP_API_TOKEN'])) {
            $token = $_ENV['ERP_API_TOKEN'];
        }
        if (empty($token) && !empty($_SERVER['ERP_API_TOKEN'])) {
            $token = $_SERVER['ERP_API_TOKEN'];
        }

        return $token ? trim($token) : '';
    }

    private function get_erp_stock_out_allocations($order_ref, $product_ref, $qty)
    {
        $token = $this->get_erp_api_token();
        if ($token === '') {
            log_message('error', 'ERP stock-out token is not configured.');
            return array();
        }

        $payload = json_encode(array(
            'orderRef' => strval($order_ref),
            'productRef' => strval($product_ref),
            'qty' => doubleval($qty)
        ));

        $response = $this->curl_json_request(
            $this->get_erp_stock_out_url(),
            array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ),
            'POST',
            $payload,
            30
        );

        if (!empty($response['error'])) {
            log_message('error', 'ERP stock-out cURL error: ' . $response['error']);
            return array();
        }

        if ($response['http_code'] < 200 || $response['http_code'] >= 300) {
            log_message('error', 'ERP stock-out HTTP ' . $response['http_code'] . ': ' . strval($response['raw']));
            return array();
        }

        if (!is_array($response['json']) || empty($response['json']['allocations']) || !is_array($response['json']['allocations'])) {
            return array();
        }

        return $response['json']['allocations'];
    }

    private function build_stock_rows_from_allocations($base_row, $allocations, $original_qty, $original_price_total)
    {
        $original_qty = abs(doubleval($original_qty));
        if ($original_qty <= 0 || empty($allocations)) {
            return array($base_row);
        }

        $valid_allocations = array();
        foreach ($allocations as $allocation) {
            $allocation_qty = abs(doubleval($allocation['qty'] ?? 0));
            if ($allocation_qty > 0) {
                $allocation['_stock_qty'] = $allocation_qty;
                $valid_allocations[] = $allocation;
            }
        }

        if (empty($valid_allocations)) {
            return array($base_row);
        }

        $rows = array();
        $allocated_qty = 0;
        $allocated_price_total = 0;
        $valid_count = count($valid_allocations);

        foreach ($valid_allocations as $idx => $allocation) {
            $remaining_qty = $original_qty - $allocated_qty;
            if ($remaining_qty <= 0) {
                break;
            }

            $allocation_qty = min($allocation['_stock_qty'], $remaining_qty);
            $row = $base_row;
            $row['qty'] = 0 - $allocation_qty;
            $row['qty_out_pos'] = $allocation_qty;
            $row['po_id'] = strval($allocation['poId'] ?? '');
            $row['po_number'] = strval($allocation['poNumber'] ?? '');
            if (isset($allocation['unitCost']) && $allocation['unitCost'] !== '') {
                $row['hpp'] = doubleval($allocation['unitCost']);
            }

            if ($idx === $valid_count - 1 && ($allocated_qty + $allocation_qty) >= $original_qty) {
                $row['price_total'] = doubleval($original_price_total) - $allocated_price_total;
            } else {
                $row['price_total'] = round(doubleval($original_price_total) * ($allocation_qty / $original_qty), 2);
            }

            $allocated_qty += $allocation_qty;
            $allocated_price_total += doubleval($row['price_total']);
            $rows[] = $row;
        }

        $shortfall_qty = $original_qty - $allocated_qty;
        if ($shortfall_qty > 0) {
            $row = $base_row;
            $row['qty'] = 0 - $shortfall_qty;
            $row['qty_out_pos'] = $shortfall_qty;
            $row['price_total'] = doubleval($original_price_total) - $allocated_price_total;
            $rows[] = $row;
        }

        return empty($rows) ? array($base_row) : $rows;
    }
    
    function calculate_stock($dt)
    {
        $order_id = $dt['order_id'];
        $marketplace = $dt['marketplace'];
        $shop_id = $dt['shop_id'];
        $user = $_SESSION['user'] ?? [];
        $created_by = strval($user['id'] ?? 0);
        $id_trx = $dt['id'];
        if ($order_id) {
            $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'Out' ");
            $this->db->delete('stock', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'Out' ");
        }
        // if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL'))) {
        //     $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' ");
        //     $this->db->delete('stock', " id_trx = '$id_trx' AND type_sub = 'POS' ");
        // }
        // if (!in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL', 'RETURN', 'REFUND'))) {
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
            $dts['price_total'] = doubleval($v2['price_total'] ?? (doubleval($v2['price'] ?? 0) * doubleval($v2['qty'] ?? 0)));



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
            $dts['created_by'] = $created_by;
            $dts['status'] = "Aktif";
            $dts['desc'] = "Penjualan";

            if ($dts['id_trx']) {
                $this->db->insert('stock_product_3rd', $dts);
            }
            
            if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL'))) {
                $dts['date'] = DATE("Y-m-d H:i:s", strtotime($dt['return_at']));
                $dts['type'] = "In";
                $dts['qty'] =  abs($v2['qty']);
                $dts['qty_out'] = '0';
                $dts['qty_out_pos'] = '0';
                $dts['qty_in'] = '0';
                $dts['qty_in_pos'] =  abs($v2['qty']);
                $dts['desc'] = "Return";

                if ($dts['id_trx']) {
                    $this->db->insert('stock_product_3rd', $dts);
                }
            }

            if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL')) && $dt['is_shipped'] == 1) {
                $dts['date'] = DATE("Y-m-d H:i:s", strtotime($dt['return_at']));
                $dts['type'] = "Ongoing";
                $dts['qty'] =  abs($v2['qty']);
                $dts['qty_out'] = '0';
                $dts['qty_out_pos'] = '0';
                $dts['qty_in'] = '0';
                $dts['qty_in_pos'] =  '0';
                $dts['qty_retur'] =  abs($v2['qty']);
                $dts['desc'] = "Return";

                if ($dts['id_trx']) {
                    $this->db->insert('stock_product_3rd', $dts);
                }
            }
        }
        
        // if ($dt['order_status'] == 'CANCELLED' && $dt['is_shipped'] == 0) {
        //     $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' ");
        // }
        
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
            // $dts['qty_out'] = abs($v2['qty']);
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
            $dts['created_by'] = $created_by;
            $dts['status'] = "Aktif";

            $stock_rows = array($dts);
            if ($dts['id_trx']) {
                $allocations = $this->get_erp_stock_out_allocations($dt['order_id'], $v2['product'], abs($v2['qty']));
                $stock_rows = $this->build_stock_rows_from_allocations($dts, $allocations, $v2['qty'], $dts['price_total']);
                foreach ($stock_rows as $stock_row) {
                    $this->db->insert('stock', $stock_row);
                }
            }
            
            if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL'))) {
                if (empty($dt['return_at'])) {
                    $dt['return_at'] = $dt['cancel_at'];
                }
                if ($dts['id_trx']) {
                    foreach ($stock_rows as $stock_row) {
                        $stock_row['date'] = DATE("Y-m-d H:i:s", strtotime($dt['return_at']));
                        $stock_row['type'] = "In";
                        $stock_row['qty'] = abs($stock_row['qty']);
                        $stock_row['qty_out'] = '0';
                        $stock_row['qty_out_pos'] = '0';
                        $stock_row['qty_in'] = '0';
                        $stock_row['qty_in_pos'] = abs($stock_row['qty']);
                        $this->db->insert('stock', $stock_row);
                    }
                }
            }
            
            if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL')) && $dt['is_shipped'] == 1) {
                if (empty($dt['return_at'])) {
                    $dt['return_at'] = $dt['cancel_at'];
                }
                if ($dts['id_trx']) {
                    foreach ($stock_rows as $stock_row) {
                        $stock_row['date'] = DATE("Y-m-d H:i:s", strtotime($dt['return_at']));
                        $stock_row['type'] = "Ongoing";
                        $stock_row['qty'] = abs($stock_row['qty']);
                        $stock_row['qty_out'] = '0';
                        $stock_row['qty_out_pos'] = '0';
                        $stock_row['qty_in'] = '0';
                        $stock_row['qty_in_pos'] = '0';
                        $stock_row['qty_retur'] = abs($stock_row['qty']);
                        $this->db->insert('stock', $stock_row);
                    }
                }
            }
        }
        
        // if ($dt['order_status'] == 'CANCELLED' && $dt['is_shipped'] == 0) {
        //     $this->db->delete('stock', " id_trx = '$id_trx' AND type_sub = 'POS' ");
        // }

        foreach ($dt['stock'] as $k2 => $v2) {
            $this->calculate_stock_product_by_id($v2['product']);
        }
        // }
        $this->update_stock_marketplace($dt);
    }

    
    function update_stock_marketplace($dt) 
    {
        if (empty($dt['stock']) || !is_array($dt['stock'])) {
            return;
        }

        $id_product = array_keys($dt['stock']);
        if (empty($id_product)) {
            return;
        }

        $id_products = implode("','", $id_product);
    
        $products = $this->mymodel->selectWithQuery("SELECT sku, stock as stock_akhir 
                                FROM product
                                WHERE id IN ('$id_products') GROUP BY sku");
        $stock_map = [];
        $sku_set = [];
        foreach ($products as $sku) {
            $uppercase_sku = strtoupper($sku['sku']);
            $sku_set[$uppercase_sku] = true;
            $stock_map[$uppercase_sku] = $sku['stock_akhir'];

            if (preg_match('/^(\d+)-(.+)/', $sku['sku'], $matches)) {
                $base_sku = strtoupper($matches[2]);
                $sku_set[$base_sku] = true;
                if (!isset($stock_map[$base_sku])) {
                    $stock_map[$base_sku] = $sku['stock_akhir'];
                }
            }
        }
        $sku_arr = array_keys($sku_set);
        if (empty($sku_arr)) {
            return;
        }
    
        $like_clauses = array_map(function($sku) {
            return "json_varian LIKE '%$sku%'";
        }, $sku_arr);
        $like_sql = implode(' OR ', $like_clauses);
        if ($like_sql === '') {
            return;
        }
    
        $products_3rd = $this->mymodel->selectWithQuery("SELECT id_product, marketplace, shop_id, shop_name, json_varian FROM product_3rd WHERE $like_sql");

        $normalize_sku = function($sku) {
            $sku = strtoupper(trim($sku));
            if ($sku === '') {
                return '';
            }
            if (preg_match('/^(\d+)-(.+)/', $sku, $m)) {
                $sku = strtoupper($m[2]);
            }
            if (preg_match('/^\[(\d+)\]\s*(.+)$/', $sku, $m)) {
                $sku = strtoupper($m[2]);
            }
            return $sku;
        };
        $split_bundle_parts = function($sku) {
            $sku = strtoupper(trim($sku));
            if ($sku === '') {
                return [];
            }
            $parts = preg_split('/\s*(?:\+|&|\/|,|\s-\s)\s*/', $sku);
            $parts = array_map('trim', $parts);
            $parts = array_filter($parts, function($p) { return $p !== ''; });
            return array_values($parts);
        };
        $parse_part = function($part) {
            $part = strtoupper(trim($part));
            $qty = 1;
            if (preg_match('/^(\d+)-(.+)/', $part, $m)) {
                $qty = (int)$m[1];
                $part = strtoupper($m[2]);
            } elseif (preg_match('/^\[(\d+)\]\s*(.+)$/', $part, $m)) {
                $qty = (int)$m[1];
                $part = strtoupper($m[2]);
            }
            return [$part, $qty];
        };
        $required_skus = [];
        foreach ($products_3rd as $p3_req) {
            $json_varian = json_decode($p3_req['json_varian'], true);
            if (!is_array($json_varian)) {
                continue;
            }
            foreach ($json_varian as $varian) {
                $sku = isset($varian['model_sku']) ? $varian['model_sku'] : ($varian['sku'] ?? '');
                $sku_parent = $varian['sku_parent'] ?? '';
                $bundle_parts = [];
                if ($sku !== '') {
                    $bundle_parts = $split_bundle_parts($sku);
                } elseif ($sku_parent !== '') {
                    $bundle_parts = $split_bundle_parts($sku_parent);
                }
                if (empty($bundle_parts) && $sku !== '') {
                    $bundle_parts = [$sku];
                }
                foreach ($bundle_parts as $part) {
                    list($part_upper, $qty) = $parse_part($part);
                    $normalized = $normalize_sku($part_upper);
                    if ($normalized !== '') {
                        $required_skus[$normalized] = true;
                    }
                }
            }
        }

        $stock_map_upper = array_change_key_case($stock_map, CASE_UPPER);
        if (!empty($required_skus)) {
            $missing = array_diff_key($required_skus, $stock_map_upper);
            if (!empty($missing)) {
                $missing_in = implode("','", array_map('addslashes', array_keys($missing)));
                $missing_rows = $this->mymodel->selectWithQuery("SELECT sku, stock as stock_akhir 
                                    FROM product 
                                    WHERE UPPER(sku) IN ('$missing_in') 
                                    GROUP BY sku");
                foreach ($missing_rows as $row) {
                    $norm_sku = $normalize_sku($row['sku']);
                    if ($norm_sku !== '') {
                        $stock_map_upper[$norm_sku] = $row['stock_akhir'];
                    }
                }
            }
        }
    
        foreach ($products_3rd as &$p3) {
            $json_varian = json_decode($p3['json_varian'], true);
            $p3_variants = []; 
            
            if (is_array($json_varian)) {
                foreach ($json_varian as $varian) {
                    $sku = isset($varian['model_sku']) ? $varian['model_sku'] : ($varian['sku'] ?? '');
                    $sku_parent = $varian['sku_parent'] ?? '';
                    $sku_used = '';
                    $stock_total = null;

        
                    $bundle_parts = [];
                    if ($sku !== '') {
                        $bundle_parts = $split_bundle_parts($sku);
                    } elseif ($sku_parent !== '') {
                        $bundle_parts = $split_bundle_parts($sku_parent);
                    }
        
                    if (count($bundle_parts) > 1) {
                        $stock_candidates = [];
                        foreach ($bundle_parts as $part) {
                            list($part_upper, $qty) = $parse_part($part);
                            $part_upper = $normalize_sku($part_upper);
                            if ($part_upper !== '' && isset($stock_map_upper[$part_upper])) {
                                $stock_candidates[] = floor($stock_map_upper[$part_upper] / max(1, $qty));
                            }
                        }

                        if (!empty($stock_candidates)) {
                            $stock_total = min($stock_candidates);
                            $sku_used = implode(' + ', $bundle_parts);
                        }
                    }
                    elseif (preg_match('/^(\d+)-(.+)/', $sku, $matches)) {
                        $quantity = (int)$matches[1];
                        $base_sku = strtoupper($matches[2]);
                        
                        if (isset($stock_map_upper[$base_sku])) {
                            $stock_total = floor($stock_map_upper[$base_sku] / max(1, $quantity));
                            $sku_used = $sku;
                        }
                    }
                    else {
                        $sku_candidate = !empty($bundle_parts) ? $bundle_parts[0] : strtoupper(trim($sku));
                        $sku_candidate_norm = $normalize_sku($sku_candidate);
                        if ($sku_candidate_norm !== '' && isset($stock_map_upper[$sku_candidate_norm])) {
                            $stock_total = $stock_map_upper[$sku_candidate_norm];
                            $sku_used = $sku_candidate;
                        }
                    }
        
                    if ($stock_total !== null) {
                        $variant_data = [
                            'id_product' => $p3['id_product'],
                            'marketplace' => $p3['marketplace'],
                            'shop_id' => $p3['shop_id'],
                            'shop_name' => $p3['shop_name'],
                            'json_varian' => $p3['json_varian'],
                            'sku' => $sku_used,
                            'stock' => $stock_total,
                            'variant_details' => $varian
                        ];
                        
                        $p3_variants[] = $variant_data;
                    }
                }
            }
            
            if (empty($p3_variants)) {
                $p3_variants[] = [
                    'id_product' => $p3['id_product'],
                    'marketplace' => $p3['marketplace'],
                    'shop_id' => $p3['shop_id'],
                    'shop_name' => $p3['shop_name'],
                    'json_varian' => $p3['json_varian'],
                    'sku' => null,
                    'stock' => 0
                ];
            }
            
            $p3 = $p3_variants;
        }
        unset($p3);
        
        $flattened_products = [];
        foreach ($products_3rd as $product_variants) {
            foreach ($product_variants as $variant) {
                $flattened_products[] = $variant;
            }
        }
        
        $arr_product = [
            'SHOPEE' => [],
            'LAZADA' => [],
            'TIKTOK' => [],
        ];
        
        foreach ($flattened_products as $product) {
            $marketplace = strtoupper($product['marketplace']);
            if (isset($arr_product[$marketplace])) {
                $arr_product[$marketplace][] = $product;
            }
        }
        
        foreach ($arr_product as $marketplace => $products) {
            foreach ($products as $product) {
                $stock = max(0, (int) $product['stock']);
                $product['stock'] = $stock;
                
                switch ($marketplace) {
                    case 'SHOPEE':
                        $this->updateShopeeStock($product, $stock);
                        break;
                    case 'LAZADA':
                        $this->updateLazadaStock($product, $stock);
                        break;
                    case 'TIKTOK':
                        $this->updateTiktokStock($product, $stock);
                        break;
                }
            }
        }
    }

    protected function updateShopeeStock($product, $stock) 
    {
        $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $product['shop_id']));
        if (!$config) return false;
        
        $config = json_decode($config['val'], true);
        $access_token = $config['access_token'];
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $host = 'https://partner.shopeemobile.com';
    
        $path = "/api/v2/product/update_stock";
        $timest = time();
        $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $product['shop_id']);
        $sign = hash_hmac('sha256', $baseString, $partner_key);
        
        $variants = json_decode($product['json_varian'], true) ?? [];
        
        $stock_list = [];
        $target_sku = $product['sku']; 
        
        foreach ($variants as $variant) {
            if (isset($variant['sku']) && strtoupper(trim($variant['sku'])) === strtoupper(trim($target_sku))) {
                if (!empty($variant['id_product']) && $variant['id_product'] != '0') {
                    $stock_list[] = [
                        'model_id' => (int)$variant['id_product'],
                        'seller_stock' => [
                            [
                                'stock' => (int)$stock
                            ]
                        ]
                    ];
                    break; 
                }
            }
        }
        
        if (empty($stock_list)) {
            $stock_list[] = [
                'seller_stock' => [
                    [
                        'stock' => (int)$stock
                    ]
                ]
            ];
        }
    
        $item_id = is_numeric($product['id_product']) ? (int)$product['id_product'] : 0;
        if ($item_id <= 0) {
            echo "Invalid product ID: " . $product['id_product'];
            return false;
        }
    
        $post_data = [
            'item_id' => $item_id,
            'stock_list' => $stock_list
        ];
    
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&partner_id=' . $partner_id . '&shop_id=' . $product['shop_id'] . '&sign=' . $sign . '&timestamp=' . $timest,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($post_data, JSON_NUMERIC_CHECK),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);
        
        return $response;
    }

    protected function updateTiktokStock($product, $stock) 
    {
        
        $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $product['shop_id']));
        if (!$config) return false;
        
        $config = json_decode($config['val'], true);
        $marketplace = "TIKTOK";
        $app_key = $config['app_key'];
        $access_token = $config['access_token'];
        $shop_cipher = $config['shop']['cipher'];
        $shop_id = $product['shop_id'];
        $app_secret = $this->app_secret_tiktok;
    
        $skus = [];
        $target_sku = $product['sku'];
        $variants = json_decode($product['json_varian'], true) ?? [];
    
        foreach ($variants as $variant) {
            if (isset($variant['sku']) && strtoupper(trim($variant['sku'])) === strtoupper(trim($target_sku))) {
                if (!empty($variant['id_product']) && $variant['id_product'] != '0') {
                    $skus[] = [
                        'id' => (string)$variant['id_product'],
                        'inventory' => [
                            [
                                'quantity' => (int)$stock
                            ]
                        ]
                    ];

                    break;
                }
            }
        }
    
        if (empty($skus)) {
            $skus[] = [
                'id' => (string)$product['id_product'],
                'inventory' => [
                    [
                        'quantity' => (int)$stock
                    ]
                ]
            ];
        }

    
        $post_data = [
            'skus' => $skus
        ];

        $base_url = 'https://open-api.tiktokglobalshop.com/product/202309/products/'
          . $product['id_product'] . '/inventory/update';

        $timest = time();

        $body = json_encode($post_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $queryParams = [
            'app_key'     => $app_key,
            'timestamp'   => $timest,     
            'shop_cipher' => $shop_cipher,
        ];

        $sign = $this->tiktok_signature_generator([
            'secret' => $app_secret,
            'timest' => $timest,
            'get'    => $queryParams,
            'url'    => $base_url,
            'post'   => $body,  
        ]);

        $queryParams['sign'] = $sign;

        $url = $base_url . '?' . http_build_query($queryParams);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-tts-access-token: ' . $access_token, 
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
    
        curl_close($curl);
    
        return $response;
    }


    
    protected function updateLazadaStock($product, $stock) 
    {
        $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $product['shop_id']));
        if (!$config) return false;
        
        $config = json_decode($config['val'], true);
        $access_token = $config['access_token'];
        $app_key = $this->app_key_lazada; 
        $app_secret = $this->app_secret_lazada; 
        $url = 'https://api.lazada.co.id/rest'; 
        
        $variants = json_decode($product['json_varian'], true) ?? [];
        
        $sku_payload = '';
        $target_sku = $product['sku']; 
        
        foreach ($variants as $variant) {
            if (isset($variant['sku']) && strtoupper(trim($variant['sku'])) === strtoupper(trim($target_sku))) {
                $sku_id = !empty($variant['id_product']) ? $variant['id_product'] : $product['id_product'];
                
                $sku_payload = '
                <Sku>
                    <ItemId>'.$product['id_product'].'</ItemId>
                    <SkuId>'.$sku_id.'</SkuId>
                    <SellerSku>'.htmlspecialchars($variant['sku']).'</SellerSku>
                    <SellableQuantity>'.(int)$stock.'</SellableQuantity>
                </Sku>';
                break; 
            }
        }
        
        if (empty($sku_payload)) {
            $sku_payload = '
            <Sku>
                <ItemId>'.$product['id_product'].'</ItemId>
                <SkuId>'.$product['id_product'].'</SkuId>
                <SellerSku>'.htmlspecialchars($product['sku']).'</SellerSku>
                <SellableQuantity>'.(int)$stock.'</SellableQuantity>
            </Sku>';
        }
    
        $xml_payload = '<Request>
            <Product>
                <Skus>
                    '.$sku_payload.'
                </Skus>
            </Product>
        </Request>';
        
        $c = new LazopClient($url, $app_key, $app_secret);
        $request = new LazopRequest('/product/stock/sellable/update');
        $request->addApiParam('payload', $xml_payload);
        
        $response = $c->execute($request, $access_token);
        $response = json_decode($response, true);
        return $response;
    }

    private function build_product_variant_mapping_from_sku($sku)
    {
        $sku = strtoupper(trim((string) $sku));
        if ($sku === '') {
            return '';
        }

        $sku_parts = preg_split('/\s*\+\s*/', $sku);
        $sku_parts = array_values(array_filter(array_map('trim', $sku_parts), function ($part) {
            return $part !== '';
        }));

        if (empty($sku_parts)) {
            return '';
        }

        $mapping = [];
        $index = 1;

        foreach ($sku_parts as $sku_part) {
            $sku_escape = $this->db->escape_str($sku_part);
            $product = $this->mymodel->selectWithQuery("
                SELECT id, sku, name, brand
                FROM product
                WHERE UPPER(TRIM(sku)) = '$sku_escape'
                LIMIT 1
            ");

            if (empty($product)) {
                continue;
            }

            $product = $product[0];
            $mapping[strval($index)] = [
                'product' => strval($product['id']),
                'qty' => '1',
                'unit' => 'Pcs',
                'product_text' => strval($product['name']),
                'brand' => strval($product['brand']),
            ];
            $index++;
        }

        if (empty($mapping)) {
            return '';
        }

        return json_encode($mapping, true);
    }

    function marketplace_order_detail()
    {
        header('Content-Type: application/json; charset=utf-8');

        $is_configurated = 1;

        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $order_id = $dt['order_id'];
        $shop_id = $dt['shop_id'];
        $mode = $dt['mode'];

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");
        $arr_product = array();
        foreach ($product as $k => $v) {
            $arr_product[$v['id']] = $v;
        }

        if ($marketplace) {
            $this->db->where('marketplace', $marketplace);
        }

        $this->db->select('id,marketplace,order_id,shop_id,shop_name');
        $trx_existing = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id));

        $is_error = '';
        $msg = '';

        $trx = array();
        $trx['marketplace'] = $dt['marketplace'];
        $trx['order_id'] = $dt['order_id'];
        $trx['shop_id'] = $dt['shop_id'];

        if (empty($shop_id)) {
            $trx = $trx_existing;
        }

        $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
        $shop_name = $config['shop_name'];
        $shop_id = $config['shop_id'];

        if ($trx['marketplace'] == "TIKTOK") {
            $marketplace = $trx['marketplace'];
            $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
            $config = json_decode($config['val'], true);
            $app_key = $config['app_key'];
            $access_token = $config['access_token'];
            $shop_cipher = $config['shop']['cipher'];
            $app_secret = $this->app_secret_tiktok;

            $url = 'https://open-api.tiktokglobalshop.com/order/202309/orders?access_token=' . $access_token . '&app_key=' . $app_key . '&ids=' . $order_id . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

            $urlParts = parse_url($url);
            $paramGET = [];
            parse_str($urlParts['query'], $paramGET);
            $timest = strtotime('now');
            $pr = array();
            $pr['secret'] = $app_secret;
            $pr['timest'] = $timest;
            $pr['get'] = $paramGET;
            $pr['url'] = $url;
            $sign = $this->tiktok_signature_generator($pr);

            $url = str_replace('{{sign}}', $sign, $url);
            $url = str_replace('{{timestamp}}', $timest, $url);

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
                    'x-tts-access-token: ' . $access_token
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);


            if (empty($response['data'])) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }

            $v2 = $response['data']['orders'][0];

            $price_total_hpp = 0;

            $dt = array();
            $dt['type'] = "Out";
            $dt['type_sub'] = "POS";
            $dt['order_id'] = $order_id;
            $dt['shop_id'] = $shop_id;
            $dt['shop_name'] = $shop_name;
            $dt['marketplace'] = $marketplace;
            $dt['date'] = DATE("Y-m-d H:i:s", $v2['create_time']);
            $dt['shipping'] = strval($v2['shipping_provider']);
            $dt['awb_number'] = strval($v2['tracking_number']);
            
            if($v2['is_sample_order'] == true) {
                $dt['c_type'] = "Affiliate";
                $dt['kebutuhan'] = "AFFILIATE";
            } else {
                $dt['c_type'] = "Pelanggan";
            }
            
            $js = array();
            foreach ($v2['line_items'] as $k4 => $v4) {
                $dt['package_id'] = $v4['package_id'];
                $js[$k4]['id_product'] = $v4['sku_id'];
                $js[$k4]['sku'] = $v4['seller_sku'];
                $name = $v4['sku_name'];
                if ($name == "Default") {
                    $name = "";
                }
                $js[$k4]['name'] = $name;
                $js[$k4]['id_product_parent'] = $v4['product_id'];
                $js[$k4]['sku_parent'] = "";
                $js[$k4]['name_parent'] = $v4['product_name'];
                $js[$k4]['qty'] = '1';
                $js[$k4]['price'] = $v4['sale_price'];
                $js[$k4]['original_price'] = $v4['original_price'];
                $js[$k4]['discount'] = $v4['seller_discount'];
            }
            


            $c_type['akun_type'] = "Pelanggan";

            $brand = array();
            $json = array();
            foreach ($js as $k4 => $v4) {
                $id_product = $v4['id_product'];
                $id_product_parent = $v4['id_product_parent'];
                $this->db->select('json');
                $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent));

                if (empty($conf) && $v4['sku']) {
                    $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $v4['sku']));
                }

                $conf = json_decode($conf['json'], true);
                if (empty($conf)) {
                    $js[$k4]['is_empty'] = true;
                    $is_configurated = 0;
                }
                foreach ($conf as $k5 => $v5) {
                    $product = $arr_product[$v5['product']];
                    $brand[$product['brand']] += 1;
                    $price = 0;
                    if ($dt['c_type'] == "Pelanggan") {
                        $price = $product['price_normal'];
                    } else if ($dt['c_type'] == "Distributor") {
                        $price = $product['price_distributor'];
                    } else if ($dt['c_type'] == "Reseller") {
                        $price = $product['price_reseller'];
                    } else {
                        $price = $product['price_normal'];
                    }
                    $json[$product['id']]['sku'] = $product['sku'];
                    $json[$product['id']]['hpp'] = $product['price_buy'];
                    $json[$product['id']]['product'] = $product['id'];
                    $json[$product['id']]['product_text'] = $product['name'];
                    $json[$product['id']]['product_sub'] = $product['sub_name'];
                    $json[$product['id']]['brand'] = $product['brand'];
                    $json[$product['id']]['price'] = $price;
                    $json[$product['id']]['qty'] += (doubleval($v5['qty']) * doubleval($v4['qty']));
                    $json[$product['id']]['price_total'] += (doubleval($json[$product['id']]['qty']) * doubleval($price));
                    $json[$product['id']]['price_total_hpp'] += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));

                    $price_total_hpp += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));
                }
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

            $dt['pesanan'] = json_encode($js, true);
            $dt['pesanan_count'] = count($js);

            $dt['hpp'] = doubleval($price_total_hpp);
            $dt['json'] = json_encode($json, true);

            if ($v2['is_cod'] == true) {
                $dt['payment_type'] = "COD";
            } else {
                $dt['payment_type'] = "TF";
            }
            if ($v2['rts_time']) {
                $dt['rts_at'] = strval(DATE("Y-m-d H:i:s", $v2['rts_time']));
            }
            if ($v2['paid_time']) {
                $dt['payment_status'] = "Paid";
                $dt['pay_at'] = strval(DATE("Y-m-d H:i:s", $v2['paid_time']));
            } else {
                $dt['payment_status'] = "Unpaid";
            }
            
            $order_status = "PENDING";
            $dt['is_shipped'] = 0;
            if (in_array($v2['status'], array('UNPAID'))) {
                $order_status = 'UNPAID';
            } else if (in_array($v2['status'], array('AWAITING_COLLECTION', 'ON_HOLD'))) {
                $order_status = 'PROCESSED';
            } else if (in_array($v2['status'], array('returned'))) {
                $order_status = 'RETURN';
            } else if (in_array($v2['status'], array('CANCELLED'))) {
                $order_status = 'CANCELLED';
            } else if (in_array($v2['status'], array('COMPLETED'))) {
                $order_status = 'COMPLETED';
            } else if (in_array($v2['status'], array('DELIVERED'))) {
                $order_status = 'DELIVERED';
            } else if (in_array($v2['status'], array('IN_TRANSIT', 'PARTIALLY_SHIPPING'))) {
                $order_status = 'SHIPPED';
                $dt['is_shipped'] = 1;
            } else if (in_array($v2['status'], array('AWAITING_SHIPMENT'))) {
                $order_status = 'READY_TO_SHIP';
            }
            
            $dt['order_status'] = $order_status;
            $dt['return_at'] = DATE("Y-m-d H:i:s", $v2['cancel_time']);

            if (in_array($dt['order_status'], array('DELIVERED', 'COMPLETED', 'CANCELLED'))) {

                $url = 'https://open-api.tiktokglobalshop.com/return_refund/202309/returns/search?access_token=' . $access_token . '&app_key=' . $app_key . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

                $urlParts = parse_url($url);
                $paramGET = [];
                parse_str($urlParts['query'], $paramGET);
                $timest = strtotime('now');
                $pr = array();
                $pr['secret'] = $app_secret;
                $pr['timest'] = $timest;
                $pr['get'] = $paramGET;
                $pr['post'] = '{"order_ids":["' . $order_id . '"]}';
                $pr['url'] = $url;
                $sign = $this->tiktok_signature_generator($pr);

                $url = str_replace('{{sign}}', $sign, $url);
                $url = str_replace('{{timestamp}}', $timest, $url);
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $pr['post'],
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'x-tts-access-token: ' . $access_token
                    ),
                ));


                $response = curl_exec($curl);
                $response = json_decode($response, true);

                // if ($response['data']['return_orders']) {
                //     $v3 = $response['data']['return_orders'][0];
                //     $dt['return_at'] = DATE("Y-m-d H:i:s", $v3['create_time']);
                //     $dt['order_status'] = "RETURN";
                // }
                
                $url = 'https://open-api.tiktokglobalshop.com/finance/202501/orders/' . $order_id . '/statement_transactions?access_token=' . $access_token . '&app_key=' . $app_key . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}';

                $urlParts = parse_url($url);
                $paramGET = [];
                parse_str($urlParts['query'], $paramGET);
                $timest = strtotime('now');
                $pr = array();
                $pr['secret'] = $app_secret;
                $pr['timest'] = $timest;
                $pr['get'] = $paramGET;
                $pr['url'] = $url;
                $sign = $this->tiktok_signature_generator($pr);
    
                $url = str_replace('{{sign}}', $sign, $url);
                $url = str_replace('{{timestamp}}', $timest, $url);
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
                        'x-tts-access-token: ' . $access_token
                    ),
                ));
    
                $response = curl_exec($curl);
                $response = json_decode($response, true);
                if ($response['message'] != 'Success') {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = $response['message'];
                    echo json_encode($html, true);
                    die;
                }
    
                $payment = $response['data'];
    
                if (isset($payment['settlement_amount']) && doubleval($payment['settlement_amount']) != 0) {
                    $total_komisi_afiliasi = 0;
                    $total_omset_bersih = 0;
                    $total_platform_commission = 0;
                    $total_sfp_service_fee = 0;
                    
                    foreach ($payment['sku_transactions'] as $sku) {
                        $fee_breakdown = $sku['fee_tax_breakdown']['fee'];
                        $total_komisi_afiliasi += abs(doubleval($fee_breakdown['affiliate_commission_amount']));
                        $total_omset_bersih += doubleval($sku['revenue_breakdown']['subtotal_before_discount_amount']) 
                                            + doubleval($sku['revenue_breakdown']['seller_discount_amount']);
                        
                        $total_platform_commission += abs(doubleval($fee_breakdown['platform_commission_amount']));
                        $total_sfp_service_fee += abs(doubleval($fee_breakdown['sfp_service_fee_amount']));
                    }
                    
                    $dt['komisi_afiliasi'] = $total_komisi_afiliasi;
                    $dt['omset_bersih'] = $total_omset_bersih;
                    $dt['marketplace_fee'] = $total_platform_commission + $total_sfp_service_fee;
                    $dt['dana_pencairan'] = doubleval($payment['settlement_amount']);
                    $dt['pencairan_status'] = '';
                    $dt['pencairan_at'] = '';
                    
                    $dt['pencairan_status'] = 'Settlement';
                    $dt['pencairan_at'] = DATE("Y-m-d H:i:s", intval($payment['settlement_time'])) ?? '';
                }
            }


            $this->db->select('id');
            $customer = $this->mymodel->selectDataOne('customer', array('id_buyer' => $dt['id_buyer'], 'marketplace' => $marketplace));
            if (empty($customer)) {
                $dt['customer_text'] = strval($v2['recipient_address']['name']);
                $dt['phone'] = strval($v2['recipient_address']['phone_number']);
                $dt['address'] = strval($v2['recipient_address']['full_address']);
                $dt['address_2'] = strval($v2['recipient_address']['full_address']);
                $dt['postal_code'] = strval($v2['recipient_address']['postal_code']);
                $dt['province_text'] = strval($v2['recipient_address']['district_info'][1]['address_name']);
                $dt['city_text'] = strval($v2['recipient_address']['district_info'][2]['address_name']);
                $dt['subdistrict_text'] = strval($v2['recipient_address']['district_info'][3]['address_name']);
            }

            $dt['id_buyer'] = $v2['user_id'];
            // $id_buyer = $dt['id_buyer'];
            // $customer = $this->mymodel->selectDataOne("customer", array('id_buyer' => $id_buyer, 'marketplace' => $marketplace));
            // print_r($customer);
            // print_r($dt);
            // die;

            $dt['customer_price'] = doubleval($v2['payment']['total_amount']);
            $dt['omset_kotor'] = doubleval($v2['payment']['original_total_product_price']);
            $dt['omset_bersih'] = doubleval($v2['payment']['original_total_product_price'] - $v2['payment']['seller_discount']);
            $dt['diskon_penjual'] = doubleval($v2['payment']['seller_discount']);

            if ($dt['marketplace_fee'] == 0) {
                $channel = $this->mymodel->selectDataOne('marketplace', array('name' => $marketplace));
                $fee_json = json_decode($channel['configuration'], true);
                $fee = array();
                foreach ($fee_json as $kk => $vv) {
                    if (DATE("Y-m-d", strtotime($dt['date'])) >= $vv['date']) {
                        $fee = $vv;
                    } else {
                        break;
                    }
                }
                $marketplace_fee = 0;
                if ($fee['type'] == "Persentase") {
                    if ($fee['fee'] > 0) {
                        $marketplace_fee = doubleval($dt['omset_bersih']) * $fee['fee'] / 100;
                    }
                } else {
                    $marketplace_fee = $fee['fee'];
                }
                $dt['marketplace_fee'] = $marketplace_fee;
            }

            // print_r($dt);
            // print_r($v2);
            // echo ' --- ';
            // print_r($payment);

            // die;

            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['is_webhook'] = 1;

            if ($mode == "webhook") {
                $dtt = array();
                $dtt['order_date'] = $dt['date'];
                $this->db->update('webhook', $dtt, array('order_id' => $order_id));
            }
        } else if ($trx['marketplace'] == "SHOPEE") {
            $marketplace = $trx['marketplace'];
            $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
            $config = json_decode($config['val'], true);
            $app_key = $config['app_key'];
            $access_token = $config['access_token'];
            $shop_cipher = $config['shop']['cipher'];
            $partner_id = $this->partner_id_shopee;
            $partner_key = $this->partner_key_shopee;
            $host = 'https://partner.shopeemobile.com';

            $path = "/api/v2/order/get_order_detail";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&order_sn_list=' . $order_id . '&response_optional_fields=buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee,goods_to_declare,note,note_update_time,item_list,pay_time,dropshipper,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,buyer_username,invoice_data,no_plastic_packing,order_chargeable_weight_gram,edt,return_due_date&request_order_status_pending=true&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $response = json_decode($response, true);
            if (empty($response['response']['order_list'][0])) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }

            $v2 = $response['response']['order_list'][0];

            $price_total_hpp = 0;

            $dt = array();
            $dt['type'] = "Out";
            $dt['type_sub'] = "POS";
            $dt['order_id'] = $order_id;
            $dt['shop_id'] = $shop_id;
            $dt['shop_name'] = $shop_name;
            $dt['marketplace'] = $marketplace;
            $dt['date'] = DATE("Y-m-d H:i:s", $v2['create_time']);
            $dt['shipping'] = strval($v2['shipping_carrier']);
            if (!empty($v2['package_list']) && is_array($v2['package_list'])) {
                $first_package = $v2['package_list'][0] ?? [];
                $package_id = (string) (
                    $first_package['package_number']
                    ?? $first_package['package_id']
                    ?? ''
                );
                if ($package_id !== '') {
                    $dt['package_id'] = $package_id;
                }
            }
            $js = array();
            foreach ($v2['item_list'] as $k4 => $v4) {
                $js[$k4]['id_product'] = $v4['model_id'];
                $js[$k4]['sku'] = $v4['model_sku'];
                $js[$k4]['name'] = $v4['model_name'];
                $js[$k4]['id_product_parent'] = $v4['item_id'];
                $js[$k4]['sku_parent'] = $v4['item_sku'];
                $js[$k4]['name_parent'] = $v4['item_name'];
                $js[$k4]['qty'] = intval($v4['model_quantity_purchased']);
                $js[$k4]['price'] = intval($v4['model_discounted_price']);
                $js[$k4]['original_price'] = intval($v4['model_original_price']);
                $js[$k4]['discount'] = intval($v4['model_original_price'] - $v4['model_discounted_price']);
            }




            $c_type['akun_type'] = "Pelanggan";
            $dt['c_type'] = "Pelanggan";

            $brand = array();
            $json = array();
            foreach ($js as $k4 => $v4) {
                $id_product = $v4['id_product'];
                $id_product_parent = $v4['id_product_parent'];
                $this->db->select('json');
                $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent));

                if (empty($conf) && $v4['sku']) {
                    $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $v4['sku']));
                }

                $conf = json_decode($conf['json'], true);
                if (empty($conf)) {
                    $js[$k4]['is_empty'] = true;
                    $is_configurated = 0;
                }
                foreach ($conf as $k5 => $v5) {
                    $product = $arr_product[$v5['product']];
                    $price = 0;
                    if ($dt['c_type'] == "Pelanggan") {
                        $price = $product['price_normal'];
                    } else if ($dt['c_type'] == "Distributor") {
                        $price = $product['price_distributor'];
                    } else if ($dt['c_type'] == "Reseller") {
                        $price = $product['price_reseller'];
                    } else {
                        $price = $product['price_normal'];
                    }
                    $json[$product['id']]['sku'] = $product['sku'];
                    $json[$product['id']]['hpp'] = $product['price_buy'];
                    $json[$product['id']]['product'] = $product['id'];
                    $json[$product['id']]['product_text'] = $product['name'];
                    $json[$product['id']]['product_sub'] = $product['sub_name'];
                    $json[$product['id']]['brand'] = $product['brand'];
                    $json[$product['id']]['price'] = $price;
                    $json[$product['id']]['qty'] += (doubleval($v5['qty']) * doubleval($v4['qty']));
                    $json[$product['id']]['price_total'] += (doubleval($json[$product['id']]['qty']) * doubleval($price));
                    $json[$product['id']]['price_total_hpp'] += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));

                    $price_total_hpp += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));
                }
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

            $dt['pesanan'] = json_encode($js, true);
            $dt['pesanan_count'] = count($js);

            $dt['hpp'] = doubleval($price_total_hpp);
            $dt['json'] = json_encode($json, true);

            if ($v2['cod'] == true) {
                $dt['payment_type'] = "COD";
            } else {
                $dt['payment_type'] = "TF";
            }

            $path = "/api/v2/logistics/get_tracking_info";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?partner_id=' . $partner_id . '&order_sn=' . $order_id . '&access_token=' . $access_token . '&timestamp=' . $timest . '&sign=' . $sign . '&shop_id=' . $shop_id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response_shipping = curl_exec($curl);

            $response_shipping = json_decode($response_shipping, true);

            curl_close($curl);

            $dt['rts_at'] = "";
            $dt['is_shipped'] = 0;
            foreach ($response_shipping['response']['tracking_info'] as $k3 => $v3) {
                if ($v3['logistics_status'] == "ORDER_CREATED") {
                    $dt['rts_at'] = DATE("Y-m-d H:i:s", $v3['update_time']);
                } else if ($v3['logistics_status'] == "PICKED_UP") {
                    $dt['is_shipped'] = 1;
                }
            }

            if ($v2['pay_time']) {
                $dt['payment_status'] = "Paid";
                $dt['pay_at'] = strval(DATE("Y-m-d H:i:s", $v2['pay_time']));
            } else {
                $dt['payment_status'] = "Unpaid";
            }

            $dt['order_status'] = $v2['order_status'];
            $dt['id_buyer'] = strval($v2['buyer_user_id']);
            $dt['is_for_booking'] = $v2['advance_package'] == true ? '1' : '0';

            $this->db->select('id');
            $customer = $this->mymodel->selectDataOne('customer', array('id_buyer' => $dt['id_buyer'], 'marketplace' => $marketplace));
            if (empty($customer)) {
                $dt['c_username'] = strval($v2['buyer_username']);
                $dt['customer_text'] = strval($v2['recipient_address']['name']);
                $dt['phone'] = strval($v2['recipient_address']['phone']);
                $dt['address'] = strval($v2['recipient_address']['full_address']);
                $dt['address_2'] = strval($v2['recipient_address']['full_address']);
                $dt['postal_code'] = strval($v2['recipient_address']['zipcode']);
                $dt['province_text'] = strval($v2['recipient_address']['state']);
                $dt['city_text'] = strval($v2['recipient_address']['city']);
                $dt['subdistrict_text'] = strval($v2['recipient_address']['district']);
            }



            $order_status = $v2['order_status'];
            if ($v2['order_status'] == "TO_CONFIRM_RECEIVE") {
                $order_status = "DELIVERED";
            } else if ($v2['order_status'] == "TO_RETURN") {
                $order_status = "RETURN";
            } else if ($v2['order_status'] == "RETRY_SHIP") {
                $order_status = "PROCESSED";
            }

            // if (in_array($v2['order_status'], array('CANCELLED')) && $v2['pickup_done_time']) {
            //     foreach ($response_shipping['response']['tracking_info'] as $k3 => $v3) {
            //         if (in_array($v3['logistics_status'], array('RETURNED', 'RETURN'))) {
            //             $dt['return_at'] = DATE("Y-m-d H:i:s", $v3['update_time']);
            //             break;
            //         }
            //     }
            //     $dt['order_status'] = "RETURN";
            // }


            $curl = curl_init();

            $path = "/api/v2/logistics/get_tracking_number";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&order_sn=' . $order_id . '&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response_awb = curl_exec($curl);
            $response_awb = json_decode($response_awb, true);
            curl_close($curl);

            $dt['awb_number'] = strval($response_awb['response']['tracking_number']);


            $path = "/api/v2/payment/get_escrow_detail";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&order_sn=' . $order_id . '&response_optional_fields=buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee,goods_to_declare,note,note_update_time,item_list,pay_time,dropshipper,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,buyer_username,invoice_data,no_plastic_packing,order_chargeable_weight_gram,edt,return_due_date&request_order_status_pending=true&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);
            // print_r($response);

            curl_close($curl);
            $response = json_decode($response, true);


            $omset_kotor = 0;
            foreach ($v2['item_list'] as $kk => $vv) {
                $omset_kotor += $vv['model_quantity_purchased'] * $vv['model_original_price'];
            }
            $dt['omset_kotor'] = doubleval($omset_kotor);
            $detail = $response['response']['order_income'];
            if ($detail['escrow_amount']) {
                $dt['komisi_afiliasi'] = doubleval($detail['order_ams_commission_fee']);
                $dt['omset_kotor'] = doubleval($dt['omset_kotor']);
                $dt['diskon_penjual'] = doubleval($detail['seller_discount']);
                $dt['omset_bersih'] = doubleval($dt['omset_kotor']) - doubleval($detail['seller_discount']);
                $dt['marketplace_fee'] = doubleval($detail['commission_fee']) + doubleval($detail['service_fee']);

                $dt['pencairan_status'] = '';
                $dt['pencairan_at'] = '';
                $dt['dana_pencairan'] = '';
                if (in_array($dt['order_status'], array('COMPLETED'))) {
                    $dt['dana_pencairan'] = doubleval($detail['escrow_amount']);
                    if ($dt['dana_pencairan']) {
                        $dt['pencairan_status'] = 'Settlement';
                        // $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($detail['settlement_time']));
                    }
                }
            }

            if (in_array($dt['order_status'], array("CANCELLED"))) {
                $dt['cancel_at'] = DATE("Y-m-d H:i:s", $v2['update_time']);
            }

            $dt['customer_price'] = $v2['total_amount'];
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['is_webhook'] = 1;

            if ($mode == "webhook") {
                $dtt = array();
                $dtt['order_date'] = $dt['date'];
                $this->db->update('webhook', $dtt, array('order_id' => $order_id));
            }
        } else if ($trx['marketplace'] == "LAZADA") {
            $marketplace = $trx['marketplace'];
            $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
            $config = json_decode($config['val'], true);
            $access_token = $config['access_token'];
            $shop_cipher = $config['shop']['cipher'];

            $app_key = $this->app_key_lazada;
            $app_secret = $this->app_secret_lazada;
            $url = 'https://api.lazada.co.id/rest';
            $page_size = 100;
            $cursor = '';


            $c = new LazopClient($url, $app_key, $app_secret);
            $request = new LazopRequest('/order/get', 'GET');
            $request->addApiParam('order_id', $order_id);

            $response = $c->execute($request, $access_token);
            $response = json_decode($response, true);

            if ($response['data']) {
                $v2 = $response['data'];

                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/order/items/get', 'GET');
                $request->addApiParam('order_id', $order_id);
                $response_item = $c->execute($request, $access_token);
                $response_item = json_decode($response_item, true);

                $price_total_hpp = 0;

                $dt = array();
                $dt['type'] = "Out";
                $dt['type_sub'] = "POS";
                $dt['order_id'] = $order_id;
                $dt['shop_id'] = $shop_id;
                $dt['shop_name'] = $shop_name;
                $dt['marketplace'] = $marketplace;
                $dt['date'] = DATE("Y-m-d H:i:s", strtotime(strval(substr($v2['created_at'], 0, 19))));
                $dt['id_buyer'] = strval($response_item['data'][0]['buyer_id']);

                $this->db->select('id');
                $customer = $this->mymodel->selectDataOne('customer', array('id_buyer' => $dt['id_buyer'], 'marketplace' => $marketplace));
                if (empty($customer)) {
                    $dt['customer_text'] = strval($v2['customer_first_name']);
                    $dt['phone'] = strval($v2['address_shipping']['phone']);
                    $dt['address'] = strval($v2['address_shipping']['address1']);
                    $dt['address_2'] = strval($v2['address_shipping']['address1']);
                    $dt['postal_code'] = strval($v2['address_shipping']['post_code']);
                    $dt['province_text'] = strval($v2['address_shipping']['address3']);
                    $dt['city_text'] = strval($v2['address_shipping']['city']);
                    $dt['subdistrict_text'] = strval($v2['address_shipping']['address2']);
                }
                if ($v2['payment_method'] == "COD") {
                    $dt['payment_type'] = "COD";
                } else {
                    $dt['payment_type'] = "TF";
                }
                $dt['customer_price'] = $v2['price'] + $v2['shipping_fee'] - $v2['voucher_seller'] - $v2['voucher_platform'];
                $segments = explode(',', $response_item['data'][0]['shipment_provider']);
                $segments = explode(': ', $segments[0]);
                $shipment_provider = $segments[1];
                if (empty($shipment_provider)) {
                    $shipment_provider = $segments[0];
                }
                $dt['shipping'] = strval($shipment_provider);
                $dt['awb_number'] = strval($response_item['data'][0]['tracking_code']);

                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/logistic/order/trace');
                $request->addApiParam('order_id', $order_id);
                $response_shipping = $c->execute($request, $access_token);
                $response_shipping = json_decode($response_shipping, true);

                $dt['rts_at'] = "";
                foreach ($response_shipping['result']['module'][0]['package_detail_info_list'][0]['logistic_detail_info_list'] as $k3 => $v3) {
                    if ($v3['detail_type'] == "ready_to") {
                        $dt['rts_at'] = DATE("Y-m-d H:i:s", (substr($v3['event_time'], 0, 10)));
                    }
                }
                if ($response_item['data'][0]['payment_time']) {
                    $dt['payment_status'] = strval("Paid");
                    $dt['pay_at'] = DATE("Y-m-d H:i:s", (substr($response_item['data'][0]['payment_time'], 0, 10)));
                } else {
                    $dt['payment_status'] = strval("Unpaid");
                }
                $js = array();
                // print_r($response_item['data']);
                foreach ($response_item['data'] as $k4 => $v4) {
                    $js[$k4]['id_product'] = $v4['sku_id'];
                    $js[$k4]['sku'] = $v4['sku'];
                    $parts = explode(":", $v4['variation']);
                    $v4['variation'] = $parts[1];
                    if (empty($v4['variation'])) {
                        $v4['variation'] = $parts[0];
                    }
                    if (empty($v4['variation'])) {
                        $v4['variation'] = $v4['name'];
                    }
                    $js[$k4]['name'] = strval($v4['variation']);
                    $js[$k4]['id_product_parent'] = $v4['product_id'];
                    $js[$k4]['sku_parent'] = "";
                    $js[$k4]['name_parent'] = $v4['name'];
                    $js[$k4]['qty'] = '1';
                    $js[$k4]['price'] = intval($v4['item_price']);
                    $js[$k4]['original_price'] = intval($v4['item_price']);
                    $js[$k4]['discount'] = intval($v4['model_original_price'] - $v4['model_discounted_price']);
                    // print_r($v4);
                }


                $c_type['akun_type'] = "Pelanggan";

                $brand = array();
                $json = array();
                foreach ($js as $k4 => $v4) {
                    $id_product = $v4['id_product'];
                    $id_product_parent = $v4['id_product_parent'];
                    $this->db->select('json');
                    $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent));

                    if (empty($conf) && $v4['sku']) {
                        $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $v4['sku']));
                    }

                    $conf = json_decode($conf['json'], true);
                    if (empty($conf)) {
                        $js[$k4]['is_empty'] = true;
                        $is_configurated = 0;
                    }
                    foreach ($conf as $k5 => $v5) {
                        $product = $arr_product[$v5['product']];
                        $price = 0;
                        if ($dt['c_type'] == "Pelanggan") {
                            $price = $product['price_normal'];
                        } else if ($dt['c_type'] == "Distributor") {
                            $price = $product['price_distributor'];
                        } else if ($dt['c_type'] == "Reseller") {
                            $price = $product['price_reseller'];
                        } else {
                            $price = $product['price_normal'];
                        }
                        $json[$product['id']]['sku'] = $product['sku'];
                        $json[$product['id']]['hpp'] = $product['price_buy'];
                        $json[$product['id']]['product'] = $product['id'];
                        $json[$product['id']]['product_text'] = $product['name'];
                        $json[$product['id']]['product_sub'] = $product['sub_name'];
                        $json[$product['id']]['brand'] = $product['brand'];
                        $json[$product['id']]['price'] = $price;
                        $json[$product['id']]['qty'] += (doubleval($v5['qty']) * doubleval($v4['qty']));
                        $json[$product['id']]['price_total'] += (doubleval($json[$product['id']]['qty']) * doubleval($price));
                        $json[$product['id']]['price_total_hpp'] += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));

                        $price_total_hpp += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));
                    }
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

                $dt['pesanan'] = json_encode($js, true);
                $dt['pesanan_count'] = count($js);

                $dt['hpp'] = doubleval($price_total_hpp);
                $dt['json'] = json_encode($json, true);
                $order_status = "COMPLETED";
                if (in_array($v2['statuses'][0], array('unpaid'))) {
                    $order_status = 'UNPAID';
                } else if (in_array($v2['statuses'][0], array('topack', 'pending'))) {
                    $order_status = 'PROCESSED';
                } else if (in_array($v2['statuses'][0], array('returned', 'shipped_back_success'))) {
                    $order_status = 'RETURN';
                } else if (in_array($v2['statuses'][0], array('canceled', 'failed', 'lost'))) {
                    $order_status = 'CANCELLED';
                } else if (in_array($v2['statuses'][0], array('confirmed'))) {
                    $order_status = 'COMPLETED';
                    $dt['disbursement_at'] = '';
                    $dt['is_disbursement'] = '1';
                } else if (in_array($v2['statuses'][0], array('delivered'))) {
                    $order_status = 'DELIVERED';
                } else if (in_array($v2['statuses'][0], array('shipped'))) {
                    $order_status = 'SHIPPED';
                    $dt['is_shipped'] = 1;
                } else if (in_array($v2['statuses'][0], array('ready_to_ship', 'toship', 'shipping'))) {
                    $order_status = 'READY_TO_SHIP';
                }

                $dt['order_status'] = $order_status;
                

                // foreach ($response_shipping['result']['module'][0]['package_detail_info_list'][0]['logistic_detail_info_list'] as $k3 => $v3) {
                //     if ($v3['status_code'] == '1420') {
                //         $dt['order_status']  = "RETURN";
                //         $dt['return_at'] = DATE("Y-m-d H:i:s", substr($v2['event_time'], 0, 10));
                //     }
                // }


                // if (in_array($dt['order_status'], array('COMPLETED'))) {
                $start_date = date("Y-m-01", strtotime($dt['date']));
                $until_date = date("Y-m-t", strtotime($start_date . " +1 months"));
                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/finance/transaction/details/get', 'GET');
                $request->addApiParam('offset', '0');
                $request->addApiParam('trade_order_id', $order_id);
                $request->addApiParam('limit', '100');
                $request->addApiParam('start_time', $start_date);
                $request->addApiParam('end_time', $until_date);
                $response_vat = $c->execute($request, $config['access_token']);
                // print_r($response_vat);

                $response_vat = json_decode($response_vat, true);
                $price_admin = 0;
                $price_total = 0;
                $diskon_penjual = 0;
                foreach ($response_vat['data'] as $kk => $vv) {
                    if (in_array($vv['fee_type'], array('118', '306'))) {
                        $diskon_penjual += doubleval(str_replace('-', '', str_replace(',', '', $vv['amount'])));
                    }
                    if (in_array($vv['transaction_type'], array('Orders-Sales'))) {
                        $price_total += doubleval(str_replace('-', '', str_replace(',', '', $vv['amount'])));
                    }
                    if (in_array($vv['fee_type'], array('298', '16', '3'))) {
                        $price_admin += doubleval(str_replace('-', '', str_replace(',', '', $vv['amount'])));
                    }
                    if ($vv['transaction_date']) {
                        $dt['pencairan_at'] = DATE("Y-m-d 00:00:01", strtotime($vv['transaction_date']));
                    }
                }
                // print_r($v2);
                if (empty($price_total)) {
                    $price_total = $v2['price'];
                    $dt['customer_price'] = $v2['price'];
                }
                if (doubleval($price_total - $price_admin - $diskon_penjual) > 0) {
                    $dt['komisi_afiliasi'] = doubleval(0);
                    $dt['omset_kotor'] = doubleval($price_total);
                    $dt['diskon_penjual'] = doubleval($diskon_penjual);
                    $dt['omset_bersih'] = doubleval($price_total) - doubleval($diskon_penjual);
                    $dt['marketplace_fee'] = doubleval($price_admin);
                    $dt['dana_pencairan'] = doubleval($price_total - $price_admin - $diskon_penjual);
                    $dt['pencairan_status'] = '';
                    // $dt['pencairan_at'] = '';
                    if ($dt['dana_pencairan']) {
                        $dt['pencairan_status'] = 'Settlement';
                        // $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($detail['settlement_time']));
                    }
                }
                // }

                if ($dt['marketplace_fee'] == 0) {
                    $channel = $this->mymodel->selectDataOne('marketplace', array('name' => $marketplace));
                    $fee_json = json_decode($channel['configuration'], true);
                    $fee = array();
                    foreach ($fee_json as $kk => $vv) {
                        if (DATE("Y-m-d", strtotime($dt['date'])) >= $vv['date']) {
                            $fee = $vv;
                        } else {
                            break;
                        }
                    }
                    $marketplace_fee = 0;
                    if ($fee['type'] == "Persentase") {
                        if ($fee['fee'] > 0) {
                            $marketplace_fee = doubleval($dt['customer_price']) * $fee['fee'] / 100;
                        }
                    } else {
                        $marketplace_fee = $fee['fee'];
                    }
                    $dt['marketplace_fee'] = $marketplace_fee;
                }

                // print_r($dt);
                // print_r($v2);
                // print_r($response_vat['data']);

                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['is_webhook'] = 1;
                if ($mode == "webhook") {
                    $dtt = array();
                    $dtt['order_date'] = $dt['date'];
                    $this->db->update('webhook', $dtt, array('order_id' => $order_id));
                }
            } else {
                $is_error = true;
            }
        }
        if ($v2) {
            if ($trx_existing) {
                $dt['is_configurated'] = $is_configurated;
                // if ($dt['order_status'] == 'CANCELLED' && $dt['is_shipped'] == 0) {
                //     $this->db->delete('transaction', array('id' => $trx_existing['id']));
                // }
                $this->db->update('transaction', $dt, array('id' => $trx_existing['id']));
                $dt['id'] = $trx_existing['id'];
                $dt['stock'] = $json;
                $dt['stock_product_3rd'] = $js;
                $this->calculate_stock($dt);
                $this->calculate_buyer($dt);
                $html['status'] = true;
                $html['data'] = array();
                $html['msg'] = 'Data ' . $order_id . ' berhasil diperbarui!';
                echo json_encode($html, true);
                die;
            } else {
                $dt['is_configurated'] = $is_configurated;
                $this->db->insert('transaction', $dt);
                $dt['id'] = $this->db->insert_id();
                $dt['stock'] = $json;
                $dt['stock_product_3rd'] = $js;
                $this->calculate_stock($dt);
                $this->calculate_buyer($dt);
                $html['status'] = true;
                $html['data'] = array();
                $html['msg'] = 'Data ' . $order_id . ' berhasil ditambahkan!';
                echo json_encode($html, true);
                die;
            }
        } else if (empty($trx)) {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = 'Data tidak ditemukan!';
            echo json_encode($html, true);
            die;
        } else {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = 'Koneksi marketplace bermasalah!';
            echo json_encode($html, true);
            die;
        }
    }

    public function get_booking_shopee()
    {
        $shop_id    = $_GET['shop_id'];

        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $until_date = isset($_GET['until_date']) ? $_GET['until_date'] : date('Y-m-d');

        $time_from  = strtotime($start_date . ' 00:00:00');
        $time_to    = strtotime($until_date . ' 23:59:59');

        $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $shop_id));
        $config = json_decode($config['val'], true);
        $shop_name = $config['shop']['shop_name'];

        $access_token = $config['access_token'];
        $partner_id   = $this->partner_id_shopee;
        $partner_key  = $this->partner_key_shopee;
        $host         = 'https://partner.shopeemobile.com';

        // ================== 1) GET BOOKING LIST (cursor pagination) ==================
        $path = "/api/v2/order/get_booking_list";

        $all_booking = array();
        $cursor      = '';
        $has_more    = true;

        while ($has_more) {
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);

            $url = $host . $path
                . '?access_token=' . $access_token
                . '&partner_id=' . $partner_id
                . '&shop_id=' . $shop_id
                . '&time_range_field=create_time'
                . '&time_from=' . $time_from
                . '&time_to='   . $time_to
                . '&page_size=' . 100
                . '&sign=' . $sign
                . '&timestamp=' . $timest;

            if ($cursor !== '') {
                $url .= '&cursor=' . urlencode($cursor);
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL        => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'GET',
            ));
            $resp_json = curl_exec($curl);
            curl_close($curl);

            $resp_page = json_decode($resp_json, true);

            if (!isset($resp_page['response']['booking_list'])) {
                $has_more = false;
                break;
            }

            $all_booking = array_merge($all_booking, $resp_page['response']['booking_list']);

            $has_more = !empty($resp_page['response']['more']);
            if ($has_more && !empty($resp_page['response']['next_cursor'])) {
                $cursor = $resp_page['response']['next_cursor'];
            } else {
                $has_more = false;
            }
        }

        $resp_list = array(
            'response' => array(
                'booking_list' => $all_booking
            )
        );

        if (!isset($resp_list['response']['booking_list'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array(
                'status'  => false,
                'message' => 'Booking list kosong / gagal',
            ));
            return;
        }

        $booking_list    = $resp_list['response']['booking_list'];
        $booking_sn_list = array_column($booking_list, 'booking_sn');

        if (empty($booking_sn_list)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array(
                'status'        => true,
                'message'       => 'Tidak ada booking pada range tanggal ini',
                'total_booking' => 0,
                'inserted'      => 0,
                'updated'       => 0,
            ));
            return;
        }

        // ================== 2) GET BOOKING TRACKING NUMBER ==================
        $path_tracking = "/api/v2/logistics/get_booking_tracking_number";
        
        $mh_tracking = curl_multi_init();
        $curl_handles_tracking = array();
        $tracking_results = array();
        
        foreach ($booking_sn_list as $booking_sn) {
            $timest_tracking = time();
            $baseString_tracking = sprintf("%s%s%s%s%s", $partner_id, $path_tracking, $timest_tracking, $access_token, $shop_id);
            $sign_tracking = hash_hmac('sha256', $baseString_tracking, $partner_key);
        
            $url_tracking = $host . $path_tracking
                . '?access_token=' . $access_token
                . '&booking_sn=' . urlencode($booking_sn)
                . '&partner_id=' . $partner_id
                . '&shop_id=' . $shop_id
                . '&sign=' . $sign_tracking
                . '&timestamp=' . $timest_tracking;
        
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL            => $url_tracking,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'GET',
                CURLOPT_HTTPHEADER     => array(
                    'Content-Type: application/json'
                ),
            ));
        
            $curl_handles_tracking[$booking_sn] = $ch;
            curl_multi_add_handle($mh_tracking, $ch);
        }
        
        $running = null;
        do {
            curl_multi_exec($mh_tracking, $running);
            curl_multi_select($mh_tracking);
        } while ($running > 0);
        
        foreach ($curl_handles_tracking as $booking_sn => $ch) {
            $resp_tracking_json = curl_multi_getcontent($ch);
            $resp_tracking = json_decode($resp_tracking_json, true);
            
            $tracking_results[$booking_sn] = [
                'tracking_number' => $resp_tracking['response']['tracking_number'] ?? ''
            ];
        
            curl_multi_remove_handle($mh_tracking, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh_tracking);
        
        // ================== 3) GET BOOKING DETAIL ==================
        $path_detail = "/api/v2/order/get_booking_detail";

        $booking_sn_chunks = array_chunk($booking_sn_list, 50);

        $mh = curl_multi_init();
        $curl_handles = array();

        foreach ($booking_sn_chunks as $idx => $chunk) {
            $booking_sn_list_str = implode(',', $chunk);

            $timest2 = time();
            $baseString2 = sprintf("%s%s%s%s%s", $partner_id, $path_detail, $timest2, $access_token, $shop_id);
            $sign2 = hash_hmac('sha256', $baseString2, $partner_key);

            $url_detail = $host . $path_detail
                . '?access_token=' . $access_token
                . '&booking_sn_list=' . $booking_sn_list_str
                . '&response_optional_fields=' . 'item_list,cancel_by,cancel_reason,fulfillment_flag,pickup_done_time,shipping_carrier,recipient_address,dropshipper,dropshipper_phone'
                . '&partner_id=' . $partner_id
                . '&shop_id=' . $shop_id
                . '&sign=' . $sign2
                . '&timestamp=' . $timest2;

            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL            => $url_detail,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'GET',
            ));

            $curl_handles[] = $ch;
            curl_multi_add_handle($mh, $ch);
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        $all_detail = array();

        foreach ($curl_handles as $ch) {
            $resp_detail_json = curl_multi_getcontent($ch);
            $resp_detail = json_decode($resp_detail_json, true);

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            if (isset($resp_detail['response']['booking_list']) && is_array($resp_detail['response']['booking_list'])) {
                $all_detail = array_merge($all_detail, $resp_detail['response']['booking_list']);
            }
        }
        curl_multi_close($mh);

        // ================== 4) INSERT / UPDATE KE DB ==================
        $inserted = 0;
        $updated = 0;
        

        foreach ($all_detail as $v2) {
            $booking_sn = $v2['booking_sn'];
            $tracking_info = $tracking_results[$booking_sn] ?? [];

            $js = [];
            if (isset($v2['item_list'])) {
                foreach ($v2['item_list'] as $k4 => $v4) {
                    $js[$k4]['id_product']        = $v4['model_id'];
                    $js[$k4]['sku']               = $v4['model_sku'];
                    $js[$k4]['name']              = $v4['model_name'];
                    $js[$k4]['id_product_parent'] = $v4['item_id'];
                    $js[$k4]['sku_parent']        = $v4['item_sku'];
                    $js[$k4]['name_parent']       = $v4['item_name'];
                    $js[$k4]['qty']               = intval($v4['model_quantity_purchased']);
                }
            }

            $items_json = json_encode($js, JSON_UNESCAPED_UNICODE);

            $addr      = isset($v2['recipient_address']) ? $v2['recipient_address'] : [];
            $addr_json = json_encode($addr, JSON_UNESCAPED_UNICODE);

            $data = [
                'booking_sn'            => $booking_sn,
                'order_sn'              => $v2['order_sn'],
                'booking_status'        => $v2['booking_status'],
                'shipping_carrier'      => $v2['shipping_carrier'],
                'tracking_number'       => $tracking_info['tracking_number'] ?? null, 

                'create_time'           => (!empty($v2['create_time']) ? date('Y-m-d H:i:s', $v2['create_time']) : null),
                'update_time'           => (!empty($v2['update_time']) ? date('Y-m-d H:i:s', $v2['update_time']) : null),
                'ship_by_date'          => (!empty($v2['ship_by_date']) ? date('Y-m-d H:i:s', $v2['ship_by_date']) : null),
                'pickup_done_time'      => (!empty($v2['pickup_done_time']) ? date('Y-m-d H:i:s', $v2['pickup_done_time']) : null),

                'recipient_name'        => $addr['name']         ?? null,
                'recipient_phone'       => $addr['phone']        ?? null,
                'recipient_town'        => $addr['town']         ?? null,
                'recipient_district'    => $addr['district']     ?? null,
                'recipient_city'        => $addr['city']         ?? null,
                'recipient_state'       => $addr['state']        ?? null,
                'recipient_region'      => $addr['region']       ?? null,
                'recipient_zipcode'     => $addr['zipcode']      ?? null,
                'recipient_full_address'=> $addr['full_address'] ?? null,

                'items_json'            => $items_json,
                'cancel_by'             => $v2['cancel_by'],
                'cancel_reason'         => $v2['cancel_reason'],
                'fulfillment_flag'      => $v2['fulfillment_flag'],
                'pickup_done_time_unix' => $v2['pickup_done_time'],
                'shop_id'               => $shop_id,
                'shop_name'             => $shop_name,
            ];

            $this->db->replace('booking_fbs_orders', $data);

            $existing = $this->db->select('id')->from('booking_fbs_orders')->where('booking_sn', $booking_sn)->get()->row();
            if ($existing) {
                $updated++;
            } else {
                $inserted++;
            }
        }

        $tracking_success = count(array_filter($tracking_results, function($result) {
            return $result['success'];
        }));

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'        => true,
            'message'       => 'Sync booking Shopee selesai',
            'shop_id'       => $shop_id,
            'start_date'    => $start_date,
            'until_date'    => $until_date,
            'total_booking' => count($booking_sn_list),
            'total_detail'  => count($all_detail),
            'tracking_success' => $tracking_success,
            'tracking_failed'  => count($booking_sn_list) - $tracking_success,
            'inserted'      => $inserted,
            'updated'       => $updated,
        ]);
        return;
    }

    function marketplace_order_tracking()
    {
        header('Content-Type: application/json; charset=utf-8');

        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $order_id = $dt['order_id'];
        $mode = $dt['mode'];

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");
        $arr_product = array();
        foreach ($product as $k => $v) {
            $arr_product[$v['id']] = $v;
        }

        if ($marketplace) {
            $this->db->where('marketplace', $marketplace);
        }
        $trx = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id, 'is_manual' => '0'));
        $is_error = '';
        $msg = '';
        if ($trx) {
            if ($trx['marketplace'] == "TIKTOK") {
                $marketplace = $trx['marketplace'];
                $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
                $config = json_decode($config['val'], true);
                $app_key = $config['app_key'];
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $shop_id = $trx['shop_id'];
                $shop_name = $trx['shop_name'];
                $app_secret = $this->app_secret_tiktok;

                $url = 'https://open-api.tiktokglobalshop.com/fulfillment/202309/orders/' . $order_id . '/tracking?access_token=' . $access_token . '&app_key=' . $app_key . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

                $urlParts = parse_url($url);
                $paramGET = [];
                parse_str($urlParts['query'], $paramGET);
                $timest = strtotime('now');
                $pr = array();
                $pr['secret'] = $app_secret;
                $pr['timest'] = $timest;
                $pr['get'] = $paramGET;
                $pr['url'] = $url;
                $sign = $this->tiktok_signature_generator($pr);

                $url = str_replace('{{sign}}', $sign, $url);
                $url = str_replace('{{timestamp}}', $timest, $url);

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
                        'x-tts-access-token: ' . $access_token
                    ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);

                $response = json_decode($response, true);
                if (empty($response['data']['tracking'])) {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = 'Data belum tersedia!';
                    echo json_encode($html, true);
                    die;
                }
                $arr = array();
                foreach ($response['data']['tracking'] as $k2 => $v2) {
                    $arr[$k2]['title'] = $v2['title'];
                    $arr[$k2]['description'] = $v2['description'];
                    $arr[$k2]['datetime'] = DATE("Y-m-d H:i:s", substr($v2['update_time_millis'], 0, 10));
                }
            } else if ($trx['marketplace'] == "SHOPEE") {
                $marketplace = $trx['marketplace'];
                $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
                $config = json_decode($config['val'], true);
                $app_key = $config['app_key'];
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $shop_id = $trx['shop_id'];
                $shop_name = $trx['shop_name'];
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $host = 'https://partner.shopeemobile.com';
                $path = "/api/v2/logistics/get_tracking_info";
                $timest = time();
                $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                $sign = hash_hmac('sha256', $baseString, $partner_key);
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $host . $path . '?partner_id=' . $partner_id . '&order_sn=' . $order_id . '&access_token=' . $access_token . '&timestamp=' . $timest . '&sign=' . $sign . '&shop_id=' . $shop_id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                ));

                $response = curl_exec($curl);

                curl_close($curl);
                $response = json_decode($response, true);
                if (empty($response['response']['tracking_info'])) {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = 'Data belum tersedia!';
                    echo json_encode($html, true);
                    die;
                }
                $arr = array();
                foreach ($response['response']['tracking_info'] as $k2 => $v2) {
                    $arr[$k2]['title'] = $v2['logistics_status'];
                    $arr[$k2]['description'] = $v2['description'];
                    $arr[$k2]['datetime'] = DATE("Y-m-d H:i:s", $v2['update_time']);
                }
            } else if ($trx['marketplace'] == "LAZADA") {
                $marketplace = $trx['marketplace'];
                $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
                $config = json_decode($config['val'], true);
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $shop_id = $trx['shop_id'];
                $shop_name = $trx['shop_name'];
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $url = 'https://api.lazada.co.id/rest';
                $page_size = 100;
                $cursor = '';


                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/logistic/order/trace');
                $request->addApiParam('order_id', $order_id);
                $response = $c->execute($request, $access_token);
                $response = json_decode($response, true);
                if (empty($response['result']['module'][0]['package_detail_info_list'][0]['logistic_detail_info_list'])) {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = 'Data belum tersedia!';
                    echo json_encode($html, true);
                    die;
                }
                $arr = array();
                foreach ($response['result']['module'][0]['package_detail_info_list'][0]['logistic_detail_info_list'] as $k2 => $v2) {
                    $arr[$k2]['title'] = $v2['title'];
                    $arr[$k2]['description'] = $v2['description'];
                    $arr[$k2]['datetime'] = DATE("Y-m-d H:i:s", substr($v2['event_time'], 0, 10));
                }
            }
            if (empty($arr)) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = 'Data belum tersedia!';
                echo json_encode($html, true);
                die;
            } else {
                $html['status'] = true;
                $html['data'] = $arr;
                $html['msg'] = 'Data ' . $order_id . ' ditemukan!';
                echo json_encode($html, true);
                die;
            }
        } else {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = 'Data tidak ditemukan!';
            echo json_encode($html, true);
            die;
        }
    }

    function marketplace_webhook_reset()
    {
        $this->db->delete('webhook', " order_id = '' ");
        $this->db->delete('webhook', " order_date != '' ");

        // $this->db->delete('webhook', " order_date != '' AND order_id != '' ");

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Reset webhook success!';
        echo json_encode($html, true);
        die;
    }

    function marketplace_webhook_refresh()
    {
        $mode = "";
        $data = array();
        $data = $this->mymodel->selectWithQuery("SELECT id,marketplace,order_id,shop_id
            FROM transaction
            WHERE is_webhook = 0 AND is_manual = 0
            ORDER BY updated_at DESC
            LIMIT 30
            ");
        if (empty($data)) {
            $data = $this->mymodel->selectWithQuery("SELECT id,marketplace,order_id,shop_id
            FROM webhook
            WHERE order_id != '' AND order_date = ''
            GROUP BY order_id
            ORDER BY id DESC
            LIMIT 30
            ");
            $mode = "webhook";
        }

        foreach ($data as $k => $v) {

            if ($mode == "webhook") {
                $url = base_url() . 'api/marketplace/order/detail?shop_id=' . $v['shop_id'] . '&marketplace=' . $v['marketplace'] . '&order_id=' . $v['order_id'] . '&mode=webhook';
            } else {
                $url = base_url() . 'api/marketplace/order/detail?shop_id=' . $v['shop_id'] . '&marketplace=' . $v['marketplace'] . '&order_id=' . $v['order_id'] . '';
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 1,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
        }

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = $data;
        $html['mode'] = $mode;
        $html['msg'] = 'Refresh order data success!';
        echo json_encode($html, true);
        die;
    }

    public function shopee_webhook_booking_refresh_tracking()
    {
        $rows = $this->mymodel->selectWithQuery(
            'SELECT * FROM webhook_booking
             WHERE input LIKE \'%"code":24%\'
               AND order_id != ""
               AND shop_id != ""
             ORDER BY created_at DESC'
        );
    
        foreach ($rows as $v) {
            $json = json_decode($v['input'], true);
    
            if (!isset($json['data'])) continue;
    
            $booking_sn   = $json['data']['booking_sn'] ?? '';
            $tracking_no  = $json['data']['tracking_no'] ?? '';
    
            if ($booking_sn != "" && $tracking_no != "") {
                $upd = array();
                $upd['tracking_number'] = $tracking_no;
                $upd['updated_at']      = gmdate('Y-m-d H:i:s', time() + 7 * 3600);
    
                $this->db->where('booking_sn', $booking_sn);
                $this->db->update('booking_fbs_orders', $upd);
    
                $dtt = array();
                $dtt['updated_at'] = gmdate('Y-m-d H:i:s', time() + 7 * 3600);
    
                $this->db->update('webhook_booking', $dtt, ['id' => $v['id']]);
            }
        }
        header('Content-Type: application/json');
        echo json_encode([
            'status' => true,
            'msg' => 'Refresh tracking number success!'
        ]);
        die;
    }

    public function shopee_webhook_orders_refresh_pending()
    {
        $rows = $this->mymodel->selectWithQuery(
            'SELECT t.*, mc.val as config_val 
            FROM transaction t
            LEFT JOIN marketplace_config mc ON t.shop_id = mc.shop_id
            WHERE t.order_status = "PENDING" AND t.date >= DATE_SUB(NOW(), INTERVAL 3 DAY)
            ORDER BY t.created_at DESC'
        );
        
        if (empty($rows)) {
            echo json_encode(['status' => false, 'message' => 'Semua order sudah Ready To Ship']);
            return;
        }
        
        $shop_orders = [];
        foreach ($rows as $v) {
            if (empty($v['shop_id']) || empty($v['config_val'])) {
                continue;
            }
            
            $shop_id = $v['shop_id'];
            if (!isset($shop_orders[$shop_id])) {
                $shop_orders[$shop_id] = [
                    'config' => json_decode($v['config_val'], true),
                    'orders' => [],
                    'shop_id' => $shop_id
                ];
            }
            
            $shop_orders[$shop_id]['orders'][] = [
                'order_id' => $v['order_id'],
                'current_status' => $v['order_status'] ?? null,
                'db_id' => $v['id'] ?? null
            ];
        }
        
        if (empty($shop_orders)) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada konfigurasi shop yang valid']);
            return;
        }
        
        // Setup multi-curl
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $orderMapping = []; // Mapping curl handle ke order data
        
        foreach ($shop_orders as $shop_id => $shop_data) {
            $config = $shop_data['config'];
            $orders = $shop_data['orders'];
            
            // Group order IDs per shop (maksimal 50 per request sesuai limit Shopee)
            $orderChunks = array_chunk(array_column($orders, 'order_id'), 50);
            
            foreach ($orderChunks as $chunkIndex => $orderChunk) {
                if (empty($orderChunk)) continue;
                
                $orderSnList = implode(',', $orderChunk);
                $access_token = $config['access_token'] ?? '';
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $host = 'https://partner.shopeemobile.com';
                $path = "/api/v2/order/get_order_detail";
                $timest = time();
                
                // Buat signature
                $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                $sign = hash_hmac('sha256', $baseString, $partner_key);
                
                // Buat URL dengan parameter yang diperlukan saja
                $url = $host . $path . '?' . http_build_query([
                    'access_token' => $access_token,
                    'order_sn_list' => $orderSnList,
                    'partner_id' => $partner_id,
                    'shop_id' => $shop_id,
                    'sign' => $sign,
                    'timestamp' => $timest,
                    'response_optional_fields' => 'order_status',
                    'request_order_status_pending' => 'true'
                ]);
                
                // Setup curl handle
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_HEADER => false,
                ]);
                
                $curlHandles[] = $ch;
                curl_multi_add_handle($multiHandle, $ch);
                
                // Simpan mapping untuk response
                $orderMapping[(int)$ch] = [
                    'shop_id' => $shop_id,
                    'order_ids' => $orderChunk,
                    'orders_data' => $orders
                ];
            }
        }
        
        $active = null;
        do {
            $mrc = curl_multi_exec($multiHandle, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        
        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($multiHandle) != -1) {
                do {
                    $mrc = curl_multi_exec($multiHandle, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }
        
        $updated_count = 0;
        $batch_updates = [];
        
        foreach ($curlHandles as $ch) {
            $response = curl_multi_getcontent($ch);
            $info = curl_getinfo($ch);
            $handleId = (int)$ch;
            
            if (!isset($orderMapping[$handleId])) {
                continue;
            }
            
            $mapping = $orderMapping[$handleId];
            
            if ($info['http_code'] == 200 && !empty($response)) {
                $json = json_decode($response, true);
                
                if (isset($json['response']['order_list']) && !empty($json['response']['order_list'])) {
                    foreach ($json['response']['order_list'] as $order_detail) {
                        if (!isset($order_detail['order_sn'], $order_detail['order_status'])) {
                            continue;
                        }
                        
                        $order_sn = $order_detail['order_sn'];
                        $new_status = $order_detail['order_status'];
                        
                        foreach ($mapping['orders_data'] as $order_data) {
                            if ($order_data['order_id'] === $order_sn) {
                                if ($order_data['current_status'] !== $new_status) {
                                    $batch_updates[] = [
                                        'order_id' => $order_sn,
                                        'order_status' => $new_status,
                                        'updated_at' => gmdate('Y-m-d H:i:s', time() + 7 * 3600)
                                    ];
                                }
                                break;
                            }
                        }
                    }
                }
            }
            
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($multiHandle);
        
        if (!empty($batch_updates)) {
            $this->db->trans_start();
            
            foreach ($batch_updates as $update) {
                $this->db->where('order_id', $update['order_id']);
                $this->db->update('transaction', [
                    'order_status' => $update['order_status'],
                    'updated_at' => $update['updated_at']
                ]);
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Terjadi kesalahan saat update database'
                ]);
                return;
            }
            
            $updated_count = count($batch_updates);
        }
        
        header('Content-Type: application/json');
        
        if ($updated_count > 0) {
            echo json_encode([
                'status' => true,
                'message' => "Berhasil memperbarui $updated_count order status",
                'updated_count' => $updated_count
            ]);
        } else {
            echo json_encode([
                'status' => true,
                'message' => 'Tidak ada perubahan status order',
                'updated_count' => 0
            ]);
        }
    }

    function marketplace_order()
    {


        header('Content-Type: application/json; charset=utf-8');

        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $marketplace = strtoupper($marketplace);
        $shop_id = $dt['shop_id'];
        $qry = "";
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }
        if ($marketplace) {
            $qry .= " AND opt = '$marketplace' ";
        }

        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace_config
        WHERE status = 'Aktif' $qry");

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");

        $arr_product = array();
        foreach ($product as $k => $v) {
            $arr_product[$v['id']] = $v;
        }



        $start_date = $_GET['start_date'];
        $until_date = $_GET['until_date'];
        if (empty($start_date) || empty($until_date)) {
            $start_date = DATE("Y-m-d 00:00:00");
            $until_date = DATE("Y-m-d 00:00:00");
        } else {
            $start_date .= '00:00:00';
            $until_date .= '00:00:00';
        }

        $start_time = strtotime($start_date);
        $until_time = $until_date . '';
        $until_time = DATE('Y-m-d 00:00:00', strtotime($until_time . " +1 days"));
        $until_time = strtotime($until_time);

        $total_inserted = 0;

        foreach ($data as $k => $v) {
            if ($v['opt'] == "TIKTOK") {
                $marketplace = "TIKTOK";
                $config = json_decode($v['val'], true);
                $app_key = $config['app_key'];
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $app_secret = $this->app_secret_tiktok;
                $shop_id = $v['shop_id'];
                $shop_name = $v['shop_name'];

                $page_size = 100;
                $cursor = "";

                for ($i = 0; $i <= 10; $i++) {

                    $url = 'https://open-api.tiktokglobalshop.com/order/202309/orders/search?access_token=' . $access_token . '&app_key=' . $app_key . '&page_size=' . $page_size . '&page_token=' . urlencode($cursor) . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sort_field=create_time&sort_order=DESC&sign={{sign}}&timestamp={{timestamp}}&version=202309';
                    $urlParts = parse_url($url);
                    $paramGET = [];
                    parse_str($urlParts['query'], $paramGET);
                    $timest = strtotime('now');
                    $pr = array();
                    $pr['secret'] = $app_secret;
                    $pr['timest'] = $timest;
                    $pr['get'] = $paramGET;
                    $pr['post'] = '{"create_time_ge":' . $start_time . ',"create_time_lt":' . $until_time . '}';
                    $pr['url'] = $url;
                    $sign = $this->tiktok_signature_generator($pr);

                    $url = str_replace('{{sign}}', $sign, $url);
                    $url = str_replace('{{timestamp}}', $timest, $url);
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $pr['post'],
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            'x-tts-access-token: ' . $access_token
                        ),
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);
                    $response = json_decode($response, true);

                    $cursor = $response['data']['next_page_token'];

                    if (empty($response['data']['orders'])) {
                        break;
                    }

                    $order_ids = array();
                    foreach ($response['data']['orders'] as $v2) {
                        $order_ids[] = $this->db->escape_str($v2['id']);
                    }

                    $existing_ids = array();
                    if (!empty($order_ids)) {
                        $existing = $this->mymodel->selectWithQuery("SELECT order_id FROM transaction
                            WHERE marketplace = '" . $marketplace . "'
                            AND order_id IN ('" . implode("','", $order_ids) . "')");
                        foreach ($existing as $e) {
                            $existing_ids[$e['order_id']] = true;
                        }
                    }

                    $now = DATE("Y-m-d H:i:s");
                    $new_rows = array();
                    foreach ($response['data']['orders'] as $v2) {
                        $order_id = $v2['id'];
                        if (isset($existing_ids[$order_id])) {
                            continue;
                        }
                        $new_rows[] = array(
                            'type' => "Out",
                            'type_sub' => "POS",
                            'order_id' => $order_id,
                            'shop_id' => strval($shop_id),
                            'shop_name' => strval($shop_name),
                            'marketplace' => $marketplace,
                            'date' => DATE("Y-m-d H:i:s", $v2['create_time']),
                            'created_at' => $now,
                        );
                    }

                    if (!empty($new_rows)) {
                        $this->db->insert_batch('transaction', $new_rows);
                        $total_inserted += count($new_rows);
                    }

                    if (empty($cursor)) {
                        break;
                    }
                }
            } else if ($v['opt'] == "SHOPEE") {
                $marketplace = "SHOPEE";
                $config = json_decode($v['val'], true);
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $host = 'https://partner.shopeemobile.com';
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $shop_id = $v['shop_id'];
                $shop_name = $v['shop_name'];

                $page_size = 100;
                $cursor = '';

                for ($i = 0; $i <= 100; $i++) {
                    $path = "/api/v2/order/get_order_list";
                    $timest = time();
                    $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                    $sign = hash_hmac('sha256', $baseString, $partner_key);

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $host . $path . '?partner_id=' . $partner_id . '&timestamp=' . $timest . '&shop_id=' . $shop_id . '&access_token=' . $access_token . '&sign=' . $sign
                            . '&time_range_field=create_time&time_from=' . $start_time . '&time_to=' . $until_time . '&page_size=' . $page_size . '&cursor=' . $cursor . '&response_optional_fields=order_status',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);
                    $response = json_decode($response, true);

                    $cursor = $response['response']['next_cursor'];

                    foreach ($response['response']['order_list'] as $k2 => $v2) {
                        $order_id = $v2['order_sn'];
                        $this->db->select('id');
                        $trx = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['type'] = "Out";
                        $dt['type_sub'] = "POS";
                        $dt['marketplace'] = strval($marketplace);
                        $dt['shop_id'] = strval($shop_id);
                        $dt['shop_name'] = strval($shop_name);
                        $dt['marketplace'] = $marketplace;
                        $dt['order_id'] = $order_id;
                        $dt['order_status'] = $v2['order_status'];
                        if ($trx) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('transaction', $dt, array('id' => $trx['id']));
                        } else if ($dt['order_status'] !== 'CANCELLED') {
                            $dt['date'] = DATE("Y-m-d 23:00:00", strtotime($start_date));
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('transaction', $dt);
                        }
                    }
                    if (empty($cursor)) {
                        break;
                    }
                }
            } else if ($v['opt'] == "LAZADA") {
                $marketplace = "LAZADA";
                $config = json_decode($v['val'], true);
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $shop_id = $v['shop_id'];
                $shop_name = $v['shop_name'];
                $url = 'https://api.lazada.co.id/rest';
                $page_size = 100;
                $cursor = '';

                $nomor = 0;
                $offset = 0;
                $limit = 100;

                for ($i = 0; $i <= 100; $i++) {
                    $c = new LazopClient($url, $app_key, $app_secret);
                    $request = new LazopRequest('/orders/get', 'GET');
                    $request->addApiParam('sort_direction', 'ASC');
                    $request->addApiParam('offset', $offset);
                    $request->addApiParam('limit', $limit);
                    $request->addApiParam('sort_by', 'created_at');
                    $start_date = DATE("Y-m-d", strtotime($start_date));
                    $until_date = DATE("Y-m-d", strtotime($until_date));
                    $until_date = DATE('Y-m-d', strtotime($until_date . " +1 days"));

                    $request->addApiParam('created_after', $start_date . 'T00:00:00+07:00');
                    $request->addApiParam('created_before', $until_date . 'T00:00:00+07:00');

                    $response = $c->execute($request, $access_token);
                    $response = json_decode($response, true);

                    $total_data = $response['data']['countTotal'];

                    foreach ($response['data']['orders'] as $k2 => $v2) {
                        $nomor++;
                        $order_id = $v2['order_id'];
                        $this->db->select('id');
                        $trx = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['type'] = "Out";
                        $dt['type_sub'] = "POS";
                        $dt['marketplace'] = strval($marketplace);
                        $dt['shop_id'] = strval($shop_id);
                        $dt['shop_name'] = strval($shop_name);
                        $dt['marketplace'] = $marketplace;
                        $dt['order_id'] = $order_id;

                        $order_status = "COMPLETED";
                        $dt['is_shipped'] = 0;
                        if (in_array($v2['statuses'][0], array('unpaid'))) {
                            $order_status = 'UNPAID';
                        } else if (in_array($v2['statuses'][0], array('topack', 'pending'))) {
                            $order_status = 'PROCESSED';
                        } else if (in_array($v2['statuses'][0], array('returned', 'shipped_back_success'))) {
                            $order_status = 'RETURN';
                        } else if (in_array($v2['statuses'][0], array('canceled', 'failed', 'lost'))) {
                            $order_status = 'CANCELLED';
                        } else if (in_array($v2['statuses'][0], array('confirmed'))) {
                            $order_status = 'COMPLETED';
                            $dt['disbursement_at'] = '';
                            $dt['is_disbursement'] = '1';
                        } else if (in_array($v2['statuses'][0], array('delivered'))) {
                            $order_status = 'DELIVERED';
                        } else if (in_array($v2['statuses'][0], array('shipped'))) {
                            $order_status = 'SHIPPED';
                            $dt['is_shipped'] = 1;
                        } else if (in_array($v2['statuses'][0], array('ready_to_ship', 'toship', 'shipping'))) {
                            $order_status = 'READY_TO_SHIP';
                        }

                        $dt['order_status'] = $order_status;

                        $dt['customer_price'] = $v2['price'];
                        
                        if ($trx) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('transaction', $dt, array('id' => $trx['id']));
                        } else if ($dt['order_status'] !== 'CANCELLED') {
                            $dt['date'] = DATE("Y-m-d 23:00:00", strtotime($start_date));
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('transaction', $dt);
                        }
                    }
                    $offset += $limit;
                    if ($nomor >= intval($total_data)) {
                        break;
                    }
                }
            }
        }

        $html['status'] = true;
        $html['data'] = array();
        $html['total_inserted'] = $total_inserted;
        $html['msg'] = 'Sync data order berhasil! ' . $total_inserted . ' data ditambahkan.';
        echo json_encode($html, true);
        die;
    }

    function marketplace_product()
    {

        $msg = "Sync data produk berhasil!";
        $status = true;
        header('Content-Type: application/json; charset=utf-8');

        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $marketplace = strtoupper($marketplace);
        $shop_id = $dt['shop_id'];
        $qry = "";
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }
        if ($marketplace) {
            $qry .= " AND opt = '$marketplace' ";
        }

        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace_config
        WHERE status = 'Aktif' $qry");

        foreach ($data as $k => $v) {
            $shop_name = $v['shop_name'];
            $shop_id = $v['shop_id'];
            if ($v['opt'] == "TIKTOK") {
                $marketplace = "TIKTOK";
                $config = json_decode($v['val'], true);
                $app_key = $config['app_key'];
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $app_secret = $this->app_secret_tiktok;

                $page_size = 100;
                $next_page_token = "";

                for ($i = 0; $i <= 100; $i++) {
                    $url = 'https://open-api.tiktokglobalshop.com/product/202312/products/search?access_token=' . $access_token . '&app_key=' . $app_key . '&page_size=' . $page_size . '&page_token=' . $next_page_token . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202312';
                    $urlParts = parse_url($url);
                    $paramGET = [];
                    parse_str($urlParts['query'], $paramGET);
                    $timest = strtotime('now');
                    $pr = array();
                    $pr['secret'] = $app_secret;
                    $pr['timest'] = $timest;
                    $pr['get'] = $paramGET;
                    $pr['post'] = '{"status":"ACTIVATE"}';
                    $pr['url'] = $url;
                    $sign = $this->tiktok_signature_generator($pr);

                    $url = str_replace('{{sign}}', $sign, $url);
                    $url = str_replace('{{timestamp}}', $timest, $url);
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $pr['post'],
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            'x-tts-access-token: ' . $access_token
                        ),
                    ));

                    $response = curl_exec($curl);

                    $response = json_decode($response, true);

                    if ($response['code']) {
                        $msg = $marketplace . ' ' . $shop_name . ' : ' . $response['message'];
                        $status = false;
                    }

                    $next_page_token = $response['data']['next_page_token'];

                    foreach ($response['data']['products'] as $k2 => $v2) {

                        $id_product = $v2['id'];
                        $this->db->select('id');
                        $product = $this->mymodel->selectDataOne('product_3rd', array('id_product' => $id_product, 'marketplace' => $marketplace));

                        $url = 'https://open-api.tiktokglobalshop.com/product/202309/products/' . $id_product . '?app_key=' . $app_key . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&access_token=' . $access_token . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

                        $urlParts = parse_url($url);
                        $paramGET = [];
                        parse_str($urlParts['query'], $paramGET);
                        $timest = strtotime('now');
                        $pr = array();
                        $pr['secret'] = $app_secret;
                        $pr['timest'] = $timest;
                        $pr['get'] = $paramGET;
                        $pr['post'] = '';
                        $pr['url'] = $url;
                        $sign = $this->tiktok_signature_generator($pr);

                        $url = str_replace('{{sign}}', $sign, $url);
                        $url = str_replace('{{timestamp}}', $timest, $url);
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
                            CURLOPT_POSTFIELDS => $pr['post'],
                            CURLOPT_HTTPHEADER => array(
                                'Content-Type: application/json',
                                'x-tts-access-token: ' . $access_token
                            ),
                        ));

                        $response_detail = curl_exec($curl);

                        $response_detail = json_decode($response_detail, true);

                        $v2 = $response_detail['data'];
                        $dt = array();
                        $dt['marketplace'] = $marketplace;
                        $dt['id_product'] = $id_product;
                        $dt['name'] = strval($v2['title']);
                        $dt['desc'] = strval($v2['description']);
                        $dt['sku'] = strval($v2['sku']);

                        $dt['shop_name'] = $shop_name;
                        $dt['shop_id'] = $shop_id;
                        // $img_url = $v2['main_images'][0]['urls'][0];
                        $img_url = $v2['main_images'][0]['thumb_urls'][0];
                        if ($img_url) {
                            $file_name = $id_product . '.jpg';
                            // $img_dir = '/public_html/app/assets/img/product_3rd/' . $file_name;
                            $img_dir = './assets/img/product_3rd/' . $file_name;
                            file_put_contents($img_dir, file_get_contents($img_url));
                            $dt['img'] = $file_name;
                        }

                        if ($product) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('product_3rd', $dt, array('id' => $product['id']));
                        } else {
                            $dt['created_by'] = strval($_SESSION['user']['id']);
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('product_3rd', $dt);
                            $product['id'] = $this->db->insert_id();
                        }

                        $item = $v2['skus'];
                        $item_list = array();
                        if (empty($item)) {
                            $varian['sku'] = '';
                            $varian['name'] = '';
                            $varian['id_product'] = '0';
                            $varian['sku_parent'] = $dt['sku'];
                            $varian['parent_name'] = $dt['name'];
                            $varian['id_product_parent'] = $dt['id_product'];
                            $varian['id_parent'] = $product['id'];
                            $varian['img'] = $dt['img'];
                            $item_list[] = $varian;
                        } else {
                            foreach ($item as $k3 => $v3) {
                                $varian['sku'] = $v3['seller_sku'];
                                $varian['name'] = strval($v3['sales_attributes'][0]['value_name']);
                                $varian['id_product'] = $v3['id'];
                                $varian['sku_parent'] = $dt['sku'];
                                $varian['parent_name'] = $dt['name'];
                                $varian['id_product_parent'] = $dt['id_product'];
                                $varian['id_parent'] = $product['id'];
                                // $img_url = $v3['sales_attributes'][0]['sku_img']['urls'][0];
                                $img_url = $v3['sales_attributes'][0]['sku_img']['thumb_urls'][0];
                                if ($img_url) {
                                    $file_name = $varian['id_product'] . '.jpg';
                                    $img_dir = './assets/img/product_3rd/' . $file_name;
                                    file_put_contents($img_dir, file_get_contents($img_url));
                                    $varian['img'] = $file_name;
                                }
                                $item_list[] = $varian;
                            }
                        }



                        $dt['json_varian'] = json_encode($item_list, true);
                        $dt['count_varian'] = count($item_list);

                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('product_3rd', $dt, array('id' => $product['id']));

                        $ids = '';
                        foreach ($item_list as $k4 => $v4) {
                            $id_product = $v4['id_product'];
                            $id_product_parent = $v4['id_product_parent'];
                            $this->db->select('id');
                            $product = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent, 'marketplace' => $marketplace));
                            $dtt = array();
                            foreach ($v4 as $k5 => $v5) {
                                $dtt[$k5] = strval($v5);
                            }
                            $dtt['marketplace'] = $marketplace;
                            $dtt['shop_name'] = $shop_name;
                            $dtt['shop_id'] = $shop_id;

                            if ($dtt['sku']) {
                                $dat = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $dtt['sku']));
                                if ($dat) {
                                    $dtt['json'] = strval($dat['json']);
                                }
                            }

                            if ($product) {
                                $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                                $this->db->update('product_variant_3rd', $dtt, array('id' => $product['id']));
                            } else {

                                $dtt['created_by'] = strval($_SESSION['user']['id']);
                                $dtt['created_at'] = DATE("Y-m-d H:i:s");
                                $this->db->insert('product_variant_3rd', $dtt);
                                $product['id'] = $this->db->insert_id();
                            }
                            $ids .= $id_product . ',';
                        }
                        // print_r($v2);
                        // die;
                        // $id_parent = $dt['id_product'];
                        // $ids = substr($ids, 0, -1);
                        // $qry = "";
                        // if ($ids != "") {
                        //     $qry = " AND id_product NOT IN ($ids) ";
                        // }
                        // $this->db->query("DELETE FROM product_variant_3rd
                        //         WHERE id_product_parent = '$id_parent' $qry");
                    }
                    if (empty($next_page_token)) {
                        break;
                    }
                }
            } else if ($v['opt'] == "SHOPEE") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $app_key = $this->app_key_lazada;
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $access_token = $config['access_token'];
                $host = 'https://partner.shopeemobile.com';
                $shop_id = intval($shop_id);

                $offset = 0;
                for ($i = 0; $i <= 3; $i++) {
                    $path = "/api/v2/product/get_item_list";
                    $timest = time();
                    $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                    $sign = hash_hmac('sha256', $baseString, $partner_key);

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $host . $path . '?partner_id=' . $config['partner_id'] . '&timestamp=' . $timest . '&shop_id=' . $shop_id . '&access_token=' . $access_token . '&sign=' . $sign
                            . '&offset=' . $offset . '&page_size=50&item_status=NORMAL',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                    ));
                    $response = curl_exec($curl);
                    curl_close($curl);
                    $response = json_decode($response, true);

                    if ($response['response']['next_offset']) {
                        $offset = $response['response']['next_offset'];
                    } else {
                        $offset = $response['response']['next_offset'];
                    }

                    $list_id = '';
                    foreach ($response['response']['item'] as $k => $v) {
                        $list_id .= $v['item_id'] . ',';
                    }
                    $list_id = substr($list_id, 0, -1);

                    if ($list_id) {
                        $path = "/api/v2/product/get_item_base_info";
                        $timest = time();
                        $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                        $sign = hash_hmac('sha256', $baseString, $partner_key);

                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&item_id_list=' . $list_id . '&need_complaint_policy=true&need_tax_info=true&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'GET',
                        ));

                        $response = curl_exec($curl);
                        curl_close($curl);
                        $response = json_decode($response, true);
                    }
                    foreach ($response['response']['item_list']  as $k2 => $v2) {
                        $id_product = $v2['item_id'];
                        $this->db->select('id');
                        $product = $this->mymodel->selectDataOne('product_3rd', array('id_product' => $id_product, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['marketplace'] = $marketplace;
                        $dt['id_product'] = $id_product;
                        $dt['name'] = strval($v2['item_name']);
                        $dt['desc'] = strval($v2['description']);
                        $dt['sku'] = strval($v2['item_sku']);
                        $dt['shop_name'] = $shop_name;
                        $dt['shop_id'] = $shop_id;
                        $img_url = $v2['image']['image_url_list'][0];
                        if ($img_url) {
                            $file_name = $id_product . '.jpg';
                            $img_dir = './assets/img/product_3rd/' . $file_name;
                            file_put_contents($img_dir, file_get_contents($img_url));
                            $dt['img'] = $file_name;
                        }
                        if ($product) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('product_3rd', $dt, array('id' => $product['id']));
                        } else {
                            $dt['created_by'] = strval($_SESSION['user']['id']);
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('product_3rd', $dt);
                            $product['id'] = $this->db->insert_id();
                        }

                        $item = array();
                        if (intval($v2['has_model']) > 0) {
                            $path = "/api/v2/product/get_model_list";
                            $timest = time();
                            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                            $sign = hash_hmac('sha256', $baseString, $partner_key);

                            // $dt['id_product'] = '3609877442';
                            $curl = curl_init();
                            curl_setopt_array($curl, array(
                                CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&item_id=' . $dt['id_product'] . '&need_complaint_policy=true&need_tax_info=true&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'GET',
                            ));

                            $response = curl_exec($curl);
                            curl_close($curl);
                            $response = json_decode($response, true);
                            print_r($response);

                            $item = $response['response']['model'];
                        }


                        // print_r($v2);
                        // print_r($item);
                        // echo ' --- ';

                        $item_list = array();
                        if (empty($item)) {
                            $varian['sku'] = '';
                            $varian['name'] = '';
                            $varian['id_product'] = '0';
                            $varian['sku_parent'] = $dt['sku'];
                            $varian['parent_name'] = $dt['name'];
                            $varian['id_product_parent'] = $dt['id_product'];
                            $varian['id_parent'] = $product['id'];
                            $varian['img'] = $dt['img'];
                            $item_list[] = $varian;
                        } else {
                            foreach ($item as $k3 => $v3) {
                                $varian['sku'] = $v3['model_sku'];
                                $name = $v3['model_name'];
                                if (empty($name)) {
                                    $name = $dt['name'];
                                }
                                $varian['name'] = strval($name);
                                $varian['id_product'] = $v3['model_id'];
                                $varian['sku_parent'] = $dt['sku'];
                                $varian['parent_name'] = $dt['name'];
                                $varian['id_product_parent'] = $dt['id_product'];
                                $varian['id_parent'] = $product['id'];
                                $img_url = $response['response']['tier_variation'][$k3]['option_list'][0]['image']['image_url'];
                                if ($img_url) {
                                    $file_name = $varian['id_product'] . '.jpg';
                                    $img_dir = './assets/img/product_3rd/' . $file_name;
                                    file_put_contents($img_dir, file_get_contents($img_url));
                                    $varian['img'] = $file_name;
                                }
                                $item_list[] = $varian;
                            }
                        }

                        $dt['json_varian'] = json_encode($item_list, true);
                        $dt['count_varian'] = count($item_list);


                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('product_3rd', $dt, array('id' => $product['id']));

                        $ids = '';
                        foreach ($item_list as $k4 => $v4) {
                            $id_product = $v4['id_product'];
                            $id_product_parent = $v4['id_product_parent'];
                            $this->db->select('id, json');
                            $product = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent, 'marketplace' => $marketplace));
                            $dtt = array();
                            foreach ($v4 as $k5 => $v5) {
                                $dtt[$k5] = strval($v5);
                            }
                            $dtt['marketplace'] = $marketplace;
                            $dtt['shop_name'] = $shop_name;
                            $dtt['shop_id'] = $shop_id;

                            if ($dtt['sku']) {
                                $sku_escape = $this->db->escape_str($dtt['sku']);
                                $marketplace_escape = $this->db->escape_str($marketplace);
                                $donor = $this->mymodel->selectWithQuery("
                                    SELECT id, json
                                    FROM product_variant_3rd
                                    WHERE sku = '$sku_escape'
                                      AND marketplace = '$marketplace_escape'
                                      AND json IS NOT NULL
                                      AND json != ''
                                    ORDER BY id ASC
                                    LIMIT 1
                                ");
                                if (!empty($donor[0]['json']) && (empty($product['json']) || $product['json'] === '')) {
                                    $dtt['json'] = strval($donor[0]['json']);
                                }
                                if (empty($dtt['json']) && (empty($product['json']) || $product['json'] === '')) {
                                    $generated_json = $this->build_product_variant_mapping_from_sku($dtt['sku']);
                                    if ($generated_json !== '') {
                                        $dtt['json'] = $generated_json;
                                    }
                                }
                            }

                            if ($product) {
                                $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                                $this->db->update('product_variant_3rd', $dtt, array('id' => $product['id']));
                            } else {

                                $dtt['created_by'] = strval($_SESSION['user']['id']);
                                $dtt['created_at'] = DATE("Y-m-d H:i:s");
                                $this->db->insert('product_variant_3rd', $dtt);
                                $product['id'] = $this->db->insert_id();
                            }
                            $ids .= $id_product . ',';
                        }
                        // $id_parent = $dt['id_product'];
                        // $ids = substr($ids, 0, -1);
                        // $qry = "";
                        // if ($ids != "") {
                        //     $qry = " AND id_product NOT IN ($ids) ";
                        // }
                        // $this->db->query("DELETE FROM product_variant_3rd
                        //         WHERE id_product_parent = '$id_parent' $qry");
                    }

                    if (empty($offset)) {
                        break;
                    }
                }
            } else if ($v['opt'] == "LAZADA") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $refresh_token = $config['refresh_token'];
                $url = 'https://api.lazada.co.id/rest';

                $nomor = 0;
                $offset = 0;
                $limit = 50;
                for ($i = 0; $i <= 3; $i++) {
                    $nomor++;
                    $c = new LazopClient($url, $app_key, $app_secret);
                    $request = new LazopRequest('/products/get', 'GET');
                    $request->addApiParam('filter', 'all');
                    $request->addApiParam('offset', $offset);
                    $request->addApiParam('limit', $limit);
                    $request->addApiParam('options', '1');
                    $response = $c->execute($request, $config['access_token']);
                    $response = json_decode($response, true);

                    foreach ($response['data']['products'] as $k2 => $v2) {
                        $id_product = $v2['item_id'];
                        $this->db->select('id');
                        $product = $this->mymodel->selectDataOne('product_3rd', array('id_product' => $id_product, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['marketplace'] = $marketplace;
                        $dt['id_product'] = $id_product;
                        $dt['name'] = strval($v2['attributes']['name']);
                        $dt['desc'] = strval($v2['attributes']['description']);
                        $dt['sku'] = "";

                        $dt['shop_name'] = $shop_name;
                        $dt['shop_id'] = $shop_id;
                        $img_url = $v2['images'][0];
                        if ($img_url) {
                            $file_name = $id_product . '.jpg';
                            $img_dir = './assets/img/product_3rd/' . $file_name;
                            file_put_contents($img_dir, file_get_contents($img_url));
                            $dt['img'] = $file_name;
                        }

                        if ($product) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('product_3rd', $dt, array('id' => $product['id']));
                        } else {
                            $dt['created_by'] = strval($_SESSION['user']['id']);
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('product_3rd', $dt);
                            $product['id'] = $this->db->insert_id();
                        }


                        $item = $v2['skus'];
                        $item_list = array();
                        if (empty($item)) {
                            $varian['sku'] = '';
                            $varian['name'] = '';
                            $varian['id_product'] = '0';
                            $varian['sku_parent'] = $dt['sku'];
                            $varian['parent_name'] = $dt['name'];
                            $varian['id_product_parent'] = $dt['id_product'];
                            $varian['id_parent'] = $product['id'];
                            $varian['img'] = $dt['img'];
                            $item_list[] = $varian;
                        } else {
                            foreach ($item as $k3 => $v3) {
                                $varian['sku'] = $v3['SellerSku'];

                                $name = "";
                                if ($v3['saleProp']) {
                                    foreach ($v3['saleProp'] as $k4 => $v4) {
                                        $name = $v4;
                                    }
                                }
                                if (empty($name)) {
                                    $name = $v3['fragrance_family'];
                                }

                                $varian['name'] = strval($name);
                                $varian['id_product'] = $v3['SkuId'];
                                $varian['sku_parent'] = $dt['sku'];
                                $varian['parent_name'] = $dt['name'];
                                $varian['id_product_parent'] = $dt['id_product'];
                                $varian['id_parent'] = $product['id'];
                                $img_url = $v3['Images'][0];
                                if ($img_url) {
                                    $file_name = $varian['id_product'] . '.jpg';
                                    $img_dir = './assets/img/product_3rd/' . $file_name;
                                    file_put_contents($img_dir, file_get_contents($img_url));
                                    $varian['img'] = $file_name;
                                }
                                $item_list[] = $varian;
                            }
                        }

                        $dt['json_varian'] = json_encode($item_list, true);
                        $dt['count_varian'] = count($item_list);

                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('product_3rd', $dt, array('id' => $product['id']));

                        $ids = '';
                        foreach ($item_list as $k4 => $v4) {
                            $id_product = $v4['id_product'];
                            $id_product_parent = $v4['id_product_parent'];
                            $this->db->select('id');
                            $product = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent, 'marketplace' => $marketplace));
                            $dtt = array();
                            foreach ($v4 as $k5 => $v5) {
                                $dtt[$k5] = strval($v5);
                            }
                            $dtt['marketplace'] = $marketplace;
                            $dtt['shop_name'] = $shop_name;
                            $dtt['shop_id'] = $shop_id;
                            if ($product) {
                                $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                                $this->db->update('product_variant_3rd', $dtt, array('id' => $product['id']));
                            } else {

                                $dtt['created_by'] = strval($_SESSION['user']['id']);
                                $dtt['created_at'] = DATE("Y-m-d H:i:s");
                                $this->db->insert('product_variant_3rd', $dtt);
                                $product['id'] = $this->db->insert_id();
                            }
                            $ids .= $id_product . ',';
                        }
                        // $id_parent = $dt['id_product'];
                        // $ids = substr($ids, 0, -1);
                        // $qry = "";
                        // if ($ids != "") {
                        //     $qry = " AND id_product NOT IN ($ids) ";
                        // }
                        // $this->db->query("DELETE FROM product_variant_3rd
                        //         WHERE id_product_parent = '$id_parent' $qry");
                    }

                    $offset += $limit;

                    if ($nomor >= intval($response['data']['total_products'])) {
                        break;
                    }
                }
            }
        }

        $html['status'] = $status;
        $html['data'] = array();
        $html['msg'] = $msg;
        echo json_encode($html, true);
        die;
    }

    function cronjob_influencer()
    {


        $user = $_SESSION['user'];

        $mode = strval($_GET['mode']);

        $target = DATE("Y-m-d 11:00:00");
        $now = DATE("Y-m-d H:i:s");
        if ($mode != 'true') {
            if ($now >= $target) {
                // SKIP
            } else {
                header('Content-Type: application/json; charset=utf-8');
                $html = array();
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = "Bhskin influencer cronjob will be processed at " . $target . "!";
                echo json_encode($html, true);
                die;
            }
        }
        $today = DATE("Y-m-d");
        $today = DATE('Y-m-d', strtotime($today . " -7 days"));
        $todayy = $today;
        $list = $this->mymodel->selectWithQuery("SELECT * FROM influencer WHERE status = 'Aktif' AND platform IN ('Instagram', 'Youtube', 'Tiktok') AND DATE(sync_at) <= '$today' OR DATE(sync_at) IS NULL AND url != '' LIMIT 10");
        foreach ($list as $kl => $vl) {
            $id = $vl['id'];
            $query = $vl;

            $endorse = $this->mymodel->selectWithQuery("SELECT COUNT(id) as frequency, SUM(total_cost) as total_cost, SUM(views) as views, 
            AVG(views) as avg_views, 
            AVG(likes+comment+share_save) as avg_interaksi, 
            SUM(likes) as likes,
            SUM(share_save) as share,
            SUM(comment) as comment
            FROM endorse WHERE influencer = '$id'
            AND link_upload != ''
            ");
            $endorse = $endorse[0];

            $dt = array();
            $dt['sync_at'] = DATE("Y-m-d H:i:s");
            $dt['frequency'] = $endorse['frequency'];
            $dt['total_cost'] = $endorse['total_cost'];
            $dt['view'] = $endorse['views'];
            $dt['like'] = $endorse['likes'];
            $dt['comment'] = $endorse['comment'];
            $dt['collect'] = $endorse['collect'];
            $dt['share'] = $endorse['share'];
            $dt['avg_view'] = $endorse['avg_views'];
            $dt['avg_interaksi'] = $endorse['avg_interaksi'];
            if ($endorse['total_cost'] > 0 && $endorse['views'] > 0) {
                $dt['cpm'] = $endorse['total_cost'] / $endorse['views'] * 1000;
            } else {
                $dt['cpm'] = 0;
            }

            $this->db->update('influencer', $dt, array('id' => $id));

            $url = $query['url'];

            $response = $this->template->get_account_id($query['type'], $query['url']);
            // print_r($response);die;
            if ($response['status'] == false) {
                // $msg = $response['msg'];
                // echo $this->template->alert_danger($msg);
                // die;
            } else {
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = strval($user['id']);
                $dt['account_id'] = $response['data']['account_id'];
                // print_r($response);die;
                $dt['img'] = $response['data']['img'];
                $dt['follower'] = $response['data']['follower'];
                $dt['media_count'] = $response['data']['media_count'];
                // print_r($dt);die;
                $this->db->update('influencer', $dt, array('id' => $id));

                if ($query['type'] == "Tiktok") {
                    $url = $query['url'];
                    $uri = explode("/", parse_url($url, PHP_URL_PATH));
                    $username = $uri[1];
                    $username = str_replace('@', '', $username);
                    $response = $this->template->get_post_list($query['type'], $response['data']['account_id']);
                } else {
                    $response = $this->template->get_post_list($query['type'], $response['data']['account_id']);
                }

                if ($response['status'] == false) {
                    // $msg = $response['msg'];
                    // echo $this->template->alert_danger($msg);
                    // die;
                } else {
                    $dt = array();
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = strval($user['id']);
                    $dt['like'] = 0;
                    $dt['comment'] = 0;
                    $dt['collect'] = 0;
                    $dt['share'] = 0;
                    $dt['view'] = 0;
                    // print_r($response['data']);
                    $i = 0;
                    foreach ($response['data'] as $k => $v) {
                        $dt['like'] += $v['like'];
                        $dt['comment'] += $v['comment'];
                        $dt['collect'] += $v['collect'];
                        $dt['share'] += $v['share'];
                        $dt['view'] += $v['view'];
                        if ($i >= 10) {
                            break;
                        }
                        $i++;
                    }
                    if ($dt['view'] > 0) {
                        $dt['avg_view'] = $dt['view'] / 10;
                    }
                    if (($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share'])  > 0) {
                        $dt['avg_interaksi'] = ($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share']) / 10;
                    }
                    if ($dt['view'] > 0 && $dt['avg_interaksi'] > 0) {
                        $dt['er'] = $dt['avg_interaksi'] / $dt['avg_view'] * 100;
                    }
                    $dt['sync_at'] = DATE("Y-m-d H:i:s");
                    // $this->db->update('influencer', $dt, array('id' => $id));

                    $today = DATE("Y-m-d");
                    $logs = $this->mymodel->selectWithQuery("SELECT id FROM influencer_logs WHERE id_influencer = '$id' AND DATE(date) = '$today' ");
                    $logs = $logs[0];
                    if ($logs) {
                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('influencer_logs', $dt, array('id' => $logs['id']));
                    } else {
                        $dt['id_influencer'] = $id;
                        $dt['date'] = $today;
                        $dt['status'] = "Aktif";
                        $dt['created_at'] = DATE("Y-m-d H:i:s");
                        $this->db->insert('influencer_logs', $dt);
                    }

                    $dt_2 = array();
                    $dt_2['sync_at'] = $dt['sync_at'];
                    $dt_2['frequency_2'] = $i;
                    $dt_2['er'] = $dt['er'];
                    $dt_2['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt_2['updated_by'] = strval($user['id']);
                    $dt_2['view_2'] = $dt['view'];
                    $dt_2['like_2'] = $dt['like'];
                    $dt_2['collect_2'] = $dt['collect'];
                    $dt_2['share_2'] = $dt['share'];
                    $dt_2['comment_2'] = $dt['comment'];
                    $dt_2['avg_view_2'] = $dt['view'] / $i;
                    $dt_2['avg_interaksi_2'] = ($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share']) / $i;

                    if ($query['ratecard'] > 0 && $dt['view'] > 0) {
                        $dt_2['cpm_2'] = $query['ratecard'] / $dt_2['avg_view_2'] * 1000;
                    } else {
                        $dt_2['cpm_2'] = 0;
                    }

                    $this->db->update('influencer', $dt_2, array('id' => $id));

                    // $msg = "Refresh data berhasil!";
                    // echo $this->template->alert_success($msg);
                    // die;
                }
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = count($list) . " data influencer yg di sync <= $todayy berhasil diperbarui";
        echo json_encode($html, true);
        die;
    }

    function maintenance()
    {
        $logs = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse_logs
        WHERE views_after = 0
        AND views < 0
        -- AND id_endorse = '5320'
        ");
        foreach ($logs as $k2 => $v2) {
            $dtt = array();
            $id = $v2['id'];
            $id_endorse = $v2['id_endorse'];
            $dt_before = $this->mymodel->selectWithQuery("SELECT *
            FROM endorse_logs WHERE id_endorse = '$id_endorse'
            AND id < '$id'
            ORDER BY id DESC
            LIMIT 1
            ");
            $dt_before = $dt_before[0];
            if ($dt_before) {
                $dtt['views_after'] = strval($dt_before['views_after']);
                $dtt['likes_after'] = strval($dt_before['likes_after']);
                $dtt['comment_after'] = strval($dt_before['comment_after']);
                $dtt['share_save_after'] = strval($dt_before['share_save_after']);
                $dtt['cpm_after'] = strval($dt_before['cpm_after']);

                $dtt['views'] = 0;
                $dtt['likes'] = 0;
                $dtt['comment'] = 0;
                $dtt['share_save'] = 0;
                $dtt['cpm'] = 0;

                $dtt['views_before'] = strval($dt_before['views_after']);
                $dtt['likes_before'] = strval($dt_before['likes_after']);
                $dtt['comment_before'] = strval($dt_before['comment_after']);
                $dtt['share_save_before'] = strval($dt_before['share_save_after']);
                $dtt['cpm_before'] = strval($dt_before['cpm_after']);

                print_r($dtt);
                $this->db->update('endorse_logs', $dtt, array('id' => $v2['id']));
            }
        }
    }

    function maintenance_2()
    {
        $today = DATE("Y-m-d");
        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse
        -- WHERE id = 5101
        WHERE DATE(updated_at) != '$today'
        -- LIMIT 100
        ");
        foreach ($data as $k2 => $v2) {
            $id = $v2['id'];
            $logs = $this->mymodel->selectWithQuery("SELECT *
            FROM endorse_logs
            WHERE id_endorse = '$id'
            AND views_after > 0
            ORDER BY id DESC
            LIMIT 1");
            $logs = $logs[0];
            $dtt = array();
            $dtt['views'] = strval($logs['views_after']);
            $dtt['likes'] = strval($logs['likes_after']);
            $dtt['comment'] = strval($logs['comment_after']);
            $dtt['share_save'] = strval($logs['share_save_after']);
            $dtt['cpm'] = strval($logs['cpm_after']);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            print_r($dtt);
            $this->db->update('endorse', $dtt, array('id' => $v2['id']));
        }
    }

    function maintenance_3()
    {
        $today = DATE("Y-m-04 H:i:s");
        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse
        WHERE link_upload LIKE '%vt.%'
        AND platform = 'Tiktok' AND DATE(updated_at) != '$today'
        AND status = 'Aktif' AND status_campaign = 'Aktif'
        ORDER BY created_at DESC
        LIMIT 1000
       ");

        foreach ($data as $k => $v) {
            echo $v['id'];
            echo '<br>';
            $dt = array();
            $url = $v['link_upload'];
            echo $url;
            echo '<br>';
            $new_url = $this->getFinalUrl($url);
            if (strpos($new_url, "tiktok.com") !== false) {
                echo $new_url;
                echo '<br>';
                $dt['link_upload'] = $new_url;
            }
            echo '----';
            echo '<br>';
            echo '<br>';
            $dt['updated_at'] = $today;
            $dt['platform'] = 'Tiktok';
            $this->db->update('endorse', $dt, array('id' => $v['id']));
        }
    }

    function getFinalUrl($url)
    {
        // Get headers for the URL, including any redirect headers
        $headers = get_headers($url, 1);

        // Check if there is a 'Location' header, which indicates a redirect
        if (isset($headers['Location'])) {
            // If 'Location' is an array (in case of multiple redirects), get the last one
            $finalUrl = is_array($headers['Location']) ? end($headers['Location']) : $headers['Location'];
            $parsedUrl = parse_url($finalUrl);

            // Reconstruct the URL without query parameters
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
            return $baseUrl;
        }

        // Return the original URL if there is no redirect
        return $url;
    }

    function cronjob_endorse()
    {


        $user = $_SESSION['user'];

        $mode = strval($_GET['mode']);

        $target = DATE("Y-m-d 11:00:00");
        $now = DATE("Y-m-d H:i:s");
        if ($mode != 'true') {
            if ($now >= $target) {
                // SKIP
            } else {
                header('Content-Type: application/json; charset=utf-8');
                $html = array();
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = "Bhskin influencer cronjob will be processed at " . $target . "!";
                echo json_encode($html, true);
                die;shopee_get_shipping_document_bulk();
            }
        }
        $today = DATE("Y-m-d");
        // $today = DATE('Y-m-d', strtotime($today . " -1 days"));
        $todayy = $today;
        $limited_deactivated = $this->deactivate_limited_update_endorse_content();

        $list = $this->mymodel->selectWithQuery("SELECT * FROM endorse WHERE status = 'Aktif' AND status_campaign = 'Aktif' AND platform NOT IN ('Threads', 'Instagram') AND (DATE(sync_at) < '$today' OR DATE(sync_at) IS NULL) AND link_upload != '' LIMIT 10");

        foreach ($list as $kl => $vl) {

            $id_endorse = $vl['id'];
            $v = $vl;
            $today = DATE("Y-m-d");
            $yesterday = DATE('Y-m-d', strtotime($today . " -1 days"));

            $query = $this->mymodel->selectWithQuery("SELECT id
            FROM endorse_logs
            WHERE id_endorse = '$id_endorse' AND date = '$today' ");
            $query = $query[0];
            $query_yesterday = $this->mymodel->selectWithQuery("SELECT * 
            FROM endorse_logs
            WHERE id_endorse = '$id_endorse' AND date < '$today' AND views_after > 0 ORDER BY date DESC LIMIT 1 ");
            $query_yesterday = $query_yesterday[0];


            $dt = array();
            $dt['status'] = strval($v['status']);
            $dt['status_campaign'] = strval($v['status_campaign']);
            $dt['id_endorse'] = strval($v['id']);
            $dt['id_campaign'] = strval($v['id_campaign']);
            $dt['influencer'] = strval($v['influencer']);
            $dt['date'] = $today;

            $platform_is_tiktok = strtolower(strval($v['platform'])) === 'tiktok';
            $tiktok_content_id = strval($v['tiktok_content_id'] ?? '');
            $tiktok_media_type = strtolower(strval($v['tiktok_media_type'] ?? ''));
            $tiktok_cover = strval($v['tiktok_cover'] ?? '');
            $tiktok_content_link = strval($v['tiktok_content_link'] ?? '');
            $need_asset_refresh = $platform_is_tiktok && (
                $tiktok_content_id === '' ||
                $tiktok_media_type === '' ||
                $tiktok_cover === '' ||
                $tiktok_content_link === ''
            );

            $is_manual_update = isset($v['is_manual_update']) && intval($v['is_manual_update']) === 1;

            if ($is_manual_update) {
                $dts = array();
                $dts['sync_at'] = DATE("Y-m-d H:i:s");
                $this->db->update('endorse', $dts, array('id' => $v['id']));

                $dt['likes'] = intval($v['likes']);
                $dt['comment'] = intval($v['comment']);
                $dt['share_save'] = intval($v['share_save']);
                $dt['views'] = intval($v['views']);

                $query_yesterday = [
                    'likes_after' => $dt['likes'],
                    'comment_after' => $dt['comment'],
                    'share_save_after' => $dt['share_save'],
                    'views_after' => $dt['views'],
                ];
            } else {
                $response = $this->template->get_social_media($v['platform'], $v['link_upload'], $need_asset_refresh, $v['influencer'] ?? null);
                $tiktok_content_id = strval($response['data']['content_id'] ?? $tiktok_content_id);
                $tiktok_media_type = strtolower(strval($response['data']['media_type'] ?? $tiktok_media_type));
                $tiktok_cover = strval($response['data']['cover'] ?? $tiktok_cover);
                $tiktok_content_link = strval($response['data']['video_link'] ?? $tiktok_content_link);

                $dts = array();
                $dts['sync_at'] = DATE("Y-m-d H:i:s");
                if ($response['data']['created_at']) {
                    $dts['posting_at'] = $response['data']['created_at'];
                }
                $this->db->update('endorse', $dts, array('id' => $v['id']));

                $dt['likes'] = intval($query_yesterday['likes_after']);
                $dt['comment'] = intval($query_yesterday['comment_after']);
                $dt['share_save'] = intval($query_yesterday['share_save_after']);
                $dt['views'] = intval($query_yesterday['views_after']);

                if ($response['data']['view'] > 0) {
                    $dt['likes'] = $response['data']['like'];
                    $dt['comment'] = $response['data']['comment'];
                    $dt['share_save'] = doubleval($response['data']['share']) + doubleval($response['data']['collect']);
                    $dt['views'] = $response['data']['view'];
                }
            }

            if ($dt['views'] >= $this->fyp_views) {
                $id_influencer = $v['influencer'];
                $creator = $this->mymodel->selectWithQuery("SELECT follower
                    FROM influencer WHERE id = '$id_influencer'");
                $creator = $creator ? $creator[0] : array();
                $follower = isset($creator['follower']) ? intval($creator['follower']) : 0;
                if ($follower > 0) {
                    $batas = intval($follower * $this->fyp_percentage / 100);
                    if ($dt['views'] >= $batas) {
                        $dt['is_fyp'] = "1";
                    }
                }
            }

            $is_fyp_content = (isset($dt['is_fyp']) && strval($dt['is_fyp']) === '1') || intval($v['is_fyp'] ?? 0) === 1;
            if ($platform_is_tiktok && $is_fyp_content) {
                $tiktok_cover = $this->download_tiktok_fyp_asset($tiktok_cover, $id_endorse, 'cover');
            }



            $dtt = $dt;
            unset($dt['is_fyp']);
            unset($dtt['id_endorse']);
            unset($dtt['id_campaign']);
            unset($dtt['date']);
            if ($need_asset_refresh || ($platform_is_tiktok && $is_fyp_content)) {
                $dtt['tiktok_content_id'] = $tiktok_content_id;
                $dtt['tiktok_media_type'] = $tiktok_media_type;
                $dtt['tiktok_cover'] = $tiktok_cover;
                $dtt['tiktok_content_link'] = $tiktok_content_link;
                $dtt['tiktok_fetched_at'] = DATE("Y-m-d H:i:s");
            }
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");

            $this->db->update('endorse', $dtt, array('id' => $id_endorse));


            if ($v['total_cost'] > 0 && $dt['views'] > 0) {
                $dt['cpm'] = doubleval($v['total_cost']) / doubleval($dt['views']) * 1000;
            } else {
                $dt['cpm'] = 0;
            }

            $dtt = array();
            $dtt['likes'] = doubleval($dt['likes']);
            $dtt['comment'] = doubleval($dt['comment']);
            $dtt['share_save'] = doubleval($dt['share_save']);
            $dtt['views'] = doubleval($dt['views']);
            $dtt['cpm'] = doubleval($dt['cpm']);

            $dt['total_cost'] = doubleval($v['total_cost']);

            $dt['link_upload'] = strval($v['link_upload']);
            $dt['platform'] = strval($v['platform']);

            $dt['likes_after'] = intval($dt['likes']);
            $dt['comment_after'] = intval($dt['comment']);
            $dt['share_save_after'] = intval($dt['share_save']);
            $dt['views_after'] = intval($dt['views']);

            if ($v['total_cost'] > 0 && $dt['views_after'] > 0) {
                $dt['cpm_after'] = doubleval($v['total_cost']) / doubleval($dt['views_after']) * 1000;
            } else {
                $dt['cpm_after'] = 0;
            }

            $dt['likes'] -= intval($query_yesterday['likes_after']);
            $dt['comment'] -= intval($query_yesterday['comment_after']);
            $dt['share_save'] -= intval($query_yesterday['share_save_after']);
            $dt['views'] -= intval($query_yesterday['views_after']);

            if ($v['total_cost'] > 0 && $dt['views'] > 0) {
                $dt['cpm'] = doubleval($v['total_cost']) / doubleval($dt['views']) * 1000;
            } else {
                $dt['cpm'] = 0;
            }

            $dt['likes_before'] = intval($query_yesterday['likes_after']);
            $dt['comment_before'] = intval($query_yesterday['comment_after']);
            $dt['share_save_before'] = intval($query_yesterday['share_save_after']);
            $dt['views_before'] = intval($query_yesterday['views_after']);

            if ($v['total_cost'] > 0 && $dt['views_before'] > 0) {
                $dt['cpm_before'] = doubleval($v['total_cost']) / doubleval($dt['views_before']) * 1000;
            } else {
                $dt['cpm_before'] = 0;
            }
            // }

            // $dt['is_cron'] = '1';
            // print_r($dt);die;
            $dt['brand'] = strval($vl['brand']);

            $dt_tmp = array();
            foreach ($dt as $kt => $vt) {
                $dt_tmp[$kt] = strval($vt);
            }
            $dt = $dt_tmp;

            if ($query) {
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = strval($user['id']);
                $this->db->update('endorse_logs', $dt, array('id' => $query['id']));
                $id_parent = $query['id'];
            } else {
                $dt['created_at'] = DATE("Y-m-d H:i:s");
                $dt['created_by'] = strval($user['id']);
                $this->db->insert('endorse_logs', $dt);
                $id_parent = $this->db->insert_id();
            }

            $dt_tmp = array();
            foreach ($dtt as $kt => $vt) {
                $dt_tmp[$kt] = strval($vt);
            }
            $dtt = $dt_tmp;

            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            $dtt['updated_by'] = strval($user['id']);
            $this->db->update('endorse', $dtt, array('id' => $v['id']));
        }

        $limited_deactivated += $this->deactivate_limited_update_endorse_content();

        $data = $this->mymodel->selectWithQuery("SELECT id
        FROM endorse_campaign 
        WHERE status = 'Aktif'");
        foreach ($data as $k => $v) {
            $id_parent = $v['id'];
            $this->update_endorse_parent($id_parent, $v);
        }

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = count($list) . " data endorse yg di sync <= $todayy berhasil diperbarui. $limited_deactivated konten update terbatas dinonaktifkan.";
        echo json_encode($html, true);
        die;
    }

    function update_endorse_parent($id_parent, $detail)
    {
        $v['id'] = $id_parent;
        $today = DATE("Y-m-d");
        $yesterday = DATE('Y-m-d', strtotime($today . " -1 days"));
        $query = $this->mymodel->selectWithQuery("SELECT id
        FROM endorse_campaign_logs
        WHERE id_campaign = '$id_parent' AND date = '$today' ");
        $query = $query[0];

        $query_yesterday = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse_campaign_logs
        WHERE id_campaign = '$id_parent' AND date < '$today' ORDER BY date DESC LIMIT 1 ");
        $query_yesterday = $query_yesterday[0];

        $item_detail = $this->mymodel->selectWithQuery("SELECT SUM(total_cost) as total_cost, COUNT(id) as count_endorse,
        SUM(likes) as likes, SUM(comment) as comment, SUM(share_save) as share_save, SUM(views) as views, AVG(cpm) as cpm
        FROM endorse
        WHERE id_campaign = '$id_parent'  AND link_upload != ''
        AND status = 'Aktif' ");
        $item_detail = $item_detail[0];

        $item = $this->mymodel->selectWithQuery("SELECT SUM(likes) as likes, SUM(comment) as comment, SUM(share_save) as share_save, SUM(views) as views, AVG(cpm) as cpm,
        SUM(likes_after) as likes_after, SUM(comment_after) as comment_after, SUM(share_save_after) as share_save_after, SUM(views_after) as views_after, AVG(cpm_after) as cpm_after,
        SUM(likes_before) as likes_before, SUM(comment_before) as comment_before, SUM(share_save_before) as share_save_before, SUM(views_before) as views_before, AVG(cpm_before) as cpm_before
        FROM endorse_logs
        WHERE id_campaign = '$id_parent';");
        $dt = array();
        $dt['id_campaign'] = $v['id'];
        $dt['total_cost'] = doubleval($item_detail['total_cost']);
        $dt['date'] = $today;
        foreach ($item[0] as $k2 => $v2) {
            $dt[$k2] = doubleval($v2);
        }


        $dtt = array();
        foreach ($item_detail as $k3 => $v3) {
            $dtt[$k3] = doubleval($v3);
        }

        $id_parent = $v['id'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
        FROM endorse WHERE id_campaign = '$id_parent'  ");
        $summary = $summary[0];
        $dtt['count_endorse'] = intval($summary['count']);
        $dt['ce_now'] = $dtt['count_endorse'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
        FROM endorse WHERE id_campaign = '$id_parent' AND status = 'Aktif'  ");
        $summary = $summary[0];
        $dtt['count_endorse_active'] = intval($summary['count']);
        $dt['ce_active_now'] = $dtt['count_endorse_active'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
        FROM endorse WHERE id_campaign = '$id_parent' AND status = 'Aktif' 
        AND link_upload != '' ");
        $summary = $summary[0];
        $dtt['count_endorse_processed'] = intval($summary['count']);
        $dt['ce_processed_now'] = $dtt['count_endorse_processed'];


        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(DISTINCT influencer) as count
        FROM endorse WHERE id_campaign = '$id_parent'  ");
        $summary = $summary[0];
        $dtt['count_influencer'] = intval($summary['count']);
        $dt['ci_now'] = $dtt['count_influencer'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(DISTINCT influencer) as count
        FROM endorse WHERE id_campaign = '$id_parent' AND status = 'Aktif'  ");
        $summary = $summary[0];
        $dtt['count_influencer_active'] = intval($summary['count']);
        $dt['ci_active_now'] = $dtt['count_influencer_active'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(DISTINCT influencer) as count
        FROM endorse WHERE id_campaign = '$id_parent' AND status = 'Aktif'  
        AND link_upload != '' ");
        $summary = $summary[0];
        $dtt['count_influencer_processed'] = intval($summary['count']);
        $dt['ci_processed_now'] = $dtt['count_influencer_processed'];

        // print_r($query_yesterday);die;
        $dt['ci_before'] =  $query_yesterday['ci_now'];
        $dt['ci_active_before'] = $query_yesterday['ci_active_now'];
        $dt['ci_processed_before'] = $query_yesterday['ci_processed_now'];

        $dt['ce_before'] =  $query_yesterday['ce_now'];
        $dt['ce_active_before'] = $query_yesterday['ce_active_now'];
        $dt['ce_processed_before'] = $query_yesterday['ce_processed_now'];

        $dt['ci_before'] =  $query_yesterday['ci_now'];
        $dt['ci_active_before'] = $query_yesterday['ci_active_now'];
        $dt['ci_processed_before'] = $query_yesterday['ci_processed_now'];
        $dt['ce_before'] =  $query_yesterday['ce_now'];
        $dt['ce_active_before'] = $query_yesterday['ce_active_now'];
        $dt['ce_processed_before'] = $query_yesterday['ce_processed_now'];

        $dt['ci_after'] =  $dt['ci_now'];
        $dt['ci_active_after'] = $dt['ci_active_now'];
        $dt['ci_processed_after'] = $dt['ci_processed_now'];
        $dt['ce_after'] =  $dt['ce_now'];
        $dt['ce_active_after'] = $dt['ce_active_now'];
        $dt['ce_processed_after'] = $dt['ce_processed_now'];

        $dt['ci_now'] =  $dt['ci_after'] - $dt['now_before'];
        $dt['ci_active_now'] = $dt['ci_active_after'] - $dt['ci_active_before'];
        $dt['ci_processed_now'] = $dt['ci_processed_after'] - $dt['ci_processed_before'];
        $dt['ce_now'] =  $dt['ce_after'] - $dt['ce_before'];
        $dt['ce_active_now'] = $dt['ce_active_after'] - $dt['ce_active_before'];
        $dt['ce_processed_now'] = $dt['ce_processed_after'] - $dt['ce_processed_before'];

        // $dt['is_cron'] = '1';
        $dt_tmp = array();
        foreach ($dt as $kt => $vt) {
            $dt_tmp[$kt] = strval($vt);
        }
        $dt = $dt_tmp;
        $dt['status'] = strval($detail['status']);

        $campaign = $this->mymodel->selectDataOne('endorse_campaign', array('id' => $id_parent));
        $dt['brand'] = strval($campaign['brand']);


        if ($query) {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = strval($user['id']);
            $this->db->update('endorse_campaign_logs', $dt, array('id' => $query['id']));
            // $id_parent = $query['id'];
        } else {
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['created_by'] = strval($user['id']);
            $this->db->insert('endorse_campaign_logs', $dt);
            // $id_parent = $this->db->insert_id();
        }

        $dt_tmp = array();
        foreach ($dtt as $kt => $vt) {
            $dt_tmp[$kt] = strval($vt);
        }
        $dtt = $dt_tmp;

        $dtt['updated_at'] = DATE("Y-m-d H:i:s");
        $dtt['updated_by'] = strval($user['id']);
        $this->db->update('endorse_campaign', $dtt, array('id' => $v['id']));
    }

    function cronjob_endorse_campaign()
    {


        $user = $_SESSION['user'];

        $mode = strval($_GET['mode']);

        $target = DATE("Y-m-d 11:00:00");
        $now = DATE("Y-m-d H:i:s");
        if ($mode != 'true') {
            if ($now >= $target) {
                // SKIP
            } else {
                header('Content-Type: application/json; charset=utf-8');
                $html = array();
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = "Bhskin endorse campaign cronjob will be processed at " . $target . "!";
                echo json_encode($html, true);
                die;
            }
        }
        $today = DATE("Y-m-d");
        $today = DATE('Y-m-d', strtotime($today . " -1 days"));
        $todayy = $today;
        $limited_deactivated = $this->deactivate_limited_update_endorse_content();

        $list = $this->mymodel->selectWithQuery("SELECT * FROM endorse_campaign WHERE status = 'Aktif' LIMIT 10");

        foreach ($list as $kl => $vl) {
            $id_campaign = $vl['id'];

            $id_parent = $vl['id'];
            $this->update_endorse_parent($id_parent, $vl);
        }



        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = count($list) . " data endorse campaign yg di sync <= $todayy berhasil diperbarui. $limited_deactivated konten update terbatas dinonaktifkan.";
        echo json_encode($html, true);
        die;
    }
    public function webhook()
    {
        date_default_timezone_set('Asia/Jakarta');
        header('Content-Type: application/json; charset=utf-8');
        $dt['marketplace'] = strval($_GET['marketplace']);
        $dt['get'] = json_encode($_GET, true);
        $dt['post'] = json_encode($_POST, true);
        $dt['input'] = file_get_contents("php://input");
        $dt['key'] = strval($_SERVER['HTTP_X_API_KEY']);
        $dt['method'] = strval($_SERVER['REQUEST_METHOD']);
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['is_live'] = 'true';

        $json = json_decode($dt['input'], true);

        $order_id = $json['data']['order_id'];

        if ($order_id == "") {
            $order_id = $json['data']['ordersn'];
        }
        if ($order_id == "") {
            $order_id = $json['data']['content']['content']['source_content'];
        }
        if ($order_id == "") {
            $order_id = $json['data']['trade_order_id'];
        }

        $shop_id = $json['shop_id'];
        if ($shop_id == "") {
            $shop_id = $json['seller_id'];
        }

        $dt['order_id'] = strval($order_id);
        $dt['shop_id'] = strval($shop_id);
        $json = array();
        if ($dt['order_id']) {
            $this->db->insert('webhook', $dt);
            $dtt = array();
            $dtt['order_id'] = strval($order_id);
            $dtt['shop_id'] = strval($shop_id);
            $dtt['marketplace'] = strval($dt['marketplace']);
            $html = array();
            $html['status'] = true;
            $html['data'] = $dtt;
            $html['msg'] = "Bhskin webhook live access has been successful!";
            echo json_encode($html, true);
            die;
        } else {
            $html = array();
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = "Bhskin webhook live access has been unsuccessful!";
            echo json_encode($html, true);
            die;
        }
    }


    function cronjob_influencer_threads()
    {
        $user = $_SESSION['user'];

        $mode = strval($_GET['mode']);

        $target = DATE("Y-m-d 11:00:00");
        $now = DATE("Y-m-d H:i:s");
        if ($mode != 'true') {
            if ($now >= $target) {
                // SKIP
            } else {
                header('Content-Type: application/json; charset=utf-8');
                $html = array();
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = "Bhskin threads influencer cronjob will be processed at " . $target . "!";
                echo json_encode($html, true);
                die;
            }
        }

        $today = DATE("Y-m-d");
        $today = DATE('Y-m-d', strtotime($today . " -7 days"));
        $todayy = $today;

        $list = $this->mymodel->selectWithQuery("SELECT * FROM influencer WHERE status = 'Aktif' AND type = 'Threads' AND (DATE(sync_at) <= '$today' OR sync_at IS NULL) AND url != '' LIMIT 5");

        foreach ($list as $kl => $vl) {
            $id = $vl['id'];
            $query = $vl;

            $endorse = $this->mymodel->selectWithQuery("SELECT COUNT(id) as frequency, SUM(total_cost) as total_cost, SUM(views) as views,
            AVG(views) as avg_views,
            AVG(likes+comment+share_save) as avg_interaksi,
            SUM(likes) as likes,
            SUM(share_save) as share,
            SUM(comment) as comment
            FROM endorse WHERE influencer = '$id'
            AND link_upload != ''
            ");
            $endorse = $endorse[0];

            $dt = array();
            $dt['sync_at'] = DATE("Y-m-d H:i:s");
            $dt['frequency'] = $endorse['frequency'];
            $dt['total_cost'] = $endorse['total_cost'];
            $dt['view'] = $endorse['views'];
            $dt['like'] = $endorse['likes'];
            $dt['comment'] = $endorse['comment'];
            $dt['collect'] = $endorse['collect'];
            $dt['share'] = $endorse['share'];
            $dt['avg_view'] = $endorse['avg_views'];
            $dt['avg_interaksi'] = $endorse['avg_interaksi'];
            if ($endorse['total_cost'] > 0 && $endorse['views'] > 0) {
                $dt['cpm'] = $endorse['total_cost'] / $endorse['views'] * 1000;
            } else {
                $dt['cpm'] = 0;
            }

            $this->db->update('influencer', $dt, array('id' => $id));

            $response = $this->template->get_account_id($query['type'], $query['url'], $id);
            if ($response['status'] == false) {
                continue;
            }

            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = strval($user['id']);
            $dt['account_id'] = $response['data']['account_id'];
            $dt['img'] = $response['data']['img'];
            $dt['follower'] = $response['data']['follower'];
            $dt['media_count'] = $response['data']['media_count'];
            $this->db->update('influencer', $dt, array('id' => $id));

            $response = $this->template->get_post_list($query['type'], $response['data']['account_id'], $id);

            if ($response['status'] == false) {
                continue;
            }

            $dt = array();
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = strval($user['id']);
            $dt['like'] = 0;
            $dt['comment'] = 0;
            $dt['collect'] = 0;
            $dt['share'] = 0;
            $dt['view'] = 0;
            $i = 0;
            foreach ($response['data'] as $k => $v) {
                $dt['like'] += $v['like'];
                $dt['comment'] += $v['comment'];
                $dt['collect'] += $v['collect'];
                $dt['share'] += $v['share'];
                $dt['view'] += $v['view'];
                if ($i >= 10) {
                    break;
                }
                $i++;
            }
            if ($dt['view'] > 0) {
                $dt['avg_view'] = $dt['view'] / 10;
            }
            if (($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share']) > 0) {
                $dt['avg_interaksi'] = ($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share']) / 10;
            }
            if ($dt['view'] > 0 && !empty($dt['avg_interaksi']) && !empty($dt['avg_view'])) {
                $dt['er'] = $dt['avg_interaksi'] / $dt['avg_view'] * 100;
            }
            $dt['sync_at'] = DATE("Y-m-d H:i:s");

            $today = DATE("Y-m-d");
            $logs = $this->mymodel->selectWithQuery("SELECT id FROM influencer_logs WHERE id_influencer = '$id' AND DATE(date) = '$today' ");
            $logs = $logs[0];
            if ($logs) {
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $this->db->update('influencer_logs', $dt, array('id' => $logs['id']));
            } else {
                $dt['id_influencer'] = $id;
                $dt['date'] = $today;
                $dt['status'] = "Aktif";
                $dt['created_at'] = DATE("Y-m-d H:i:s");
                $this->db->insert('influencer_logs', $dt);
            }

            $dt_2 = array();
            $dt_2['sync_at'] = $dt['sync_at'];
            $dt_2['frequency_2'] = $i;
            $dt_2['er'] = $dt['er'] ?? 0;
            $dt_2['updated_at'] = DATE("Y-m-d H:i:s");
            $dt_2['updated_by'] = strval($user['id']);
            $dt_2['view_2'] = $dt['view'];
            $dt_2['like_2'] = $dt['like'];
            $dt_2['collect_2'] = $dt['collect'];
            $dt_2['share_2'] = $dt['share'];
            $dt_2['comment_2'] = $dt['comment'];
            if ($i > 0) {
                $dt_2['avg_view_2'] = $dt['view'] / $i;
                $dt_2['avg_interaksi_2'] = ($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share']) / $i;
            }

            if ($query['ratecard'] > 0 && !empty($dt_2['avg_view_2']) && $dt_2['avg_view_2'] > 0) {
                $dt_2['cpm_2'] = $query['ratecard'] / $dt_2['avg_view_2'] * 1000;
            } else {
                $dt_2['cpm_2'] = 0;
            }

            $this->db->update('influencer', $dt_2, array('id' => $id));
        }

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = count($list) . " data influencer Threads yg di sync <= $todayy berhasil diperbarui";
        echo json_encode($html, true);
        die;
    }

    function cronjob_endorse_threads()
    {
        $user = $_SESSION['user'];

        $mode = strval($_GET['mode']);

        $target = DATE("Y-m-d 11:00:00");
        $now = DATE("Y-m-d H:i:s");
        if ($mode != 'true') {
            if ($now >= $target) {
                // SKIP
            } else {
                header('Content-Type: application/json; charset=utf-8');
                $html = array();
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = "Bhskin threads endorse cronjob will be processed at " . $target . "!";
                echo json_encode($html, true);
                die;
            }
        }

        $today = DATE("Y-m-d");
        $todayy = $today;
        $limited_deactivated = $this->deactivate_limited_update_endorse_content();

        $list = $this->mymodel->selectWithQuery("SELECT * FROM endorse WHERE status = 'Aktif' AND status_campaign = 'Aktif' AND platform = 'Threads' AND (DATE(sync_at) < '$today' OR DATE(sync_at) IS NULL) AND link_upload != '' LIMIT 5");

        foreach ($list as $kl => $vl) {
            $id_endorse = $vl['id'];
            $v = $vl;
            $today = DATE("Y-m-d");
            $yesterday = DATE('Y-m-d', strtotime($today . " -1 days"));

            $query = $this->mymodel->selectWithQuery("SELECT id FROM endorse_logs WHERE id_endorse = '$id_endorse' AND date = '$today' ");
            $query = $query[0];
            $query_yesterday = $this->mymodel->selectWithQuery("SELECT * FROM endorse_logs WHERE id_endorse = '$id_endorse' AND date < '$today' AND views_after > 0 ORDER BY date DESC LIMIT 1 ");
            $query_yesterday = $query_yesterday[0];

            $dt = array();
            $dt['status'] = strval($v['status']);
            $dt['status_campaign'] = strval($v['status_campaign']);
            $dt['id_endorse'] = strval($v['id']);
            $dt['id_campaign'] = strval($v['id_campaign']);
            $dt['influencer'] = strval($v['influencer']);
            $dt['date'] = $today;

            $is_manual_update = isset($v['is_manual_update']) && intval($v['is_manual_update']) === 1;

            if ($is_manual_update) {
                $dts = array();
                $dts['sync_at'] = DATE("Y-m-d H:i:s");
                $this->db->update('endorse', $dts, array('id' => $v['id']));

                $dt['likes'] = intval($v['likes']);
                $dt['comment'] = intval($v['comment']);
                $dt['share_save'] = intval($v['share_save']);
                $dt['views'] = intval($v['views']);

                $query_yesterday = [
                    'likes_after' => $dt['likes'],
                    'comment_after' => $dt['comment'],
                    'share_save_after' => $dt['share_save'],
                    'views_after' => $dt['views'],
                ];
            } else {
                $response = $this->template->get_social_media($v['platform'], $v['link_upload'], false, $v['influencer'] ?? null);

                $dts = array();
                $dts['sync_at'] = DATE("Y-m-d H:i:s");
                if (!empty($response['data']['created_at'])) {
                    $dts['posting_at'] = $response['data']['created_at'];
                }
                $this->db->update('endorse', $dts, array('id' => $v['id']));

                $dt['likes'] = intval($query_yesterday['likes_after']);
                $dt['comment'] = intval($query_yesterday['comment_after']);
                $dt['share_save'] = intval($query_yesterday['share_save_after']);
                $dt['views'] = intval($query_yesterday['views_after']);

                if (!empty($response['status']) && intval($response['data']['view'] ?? 0) > 0) {
                    $dt['likes'] = intval($response['data']['like'] ?? 0);
                    $dt['comment'] = intval($response['data']['comment'] ?? 0);
                    $dt['share_save'] = doubleval($response['data']['share'] ?? 0) + doubleval($response['data']['collect'] ?? 0);
                    $dt['views'] = intval($response['data']['view'] ?? 0);
                }
            }

            if ($dt['views'] >= $this->fyp_views) {
                $id_influencer = $v['influencer'];
                $creator = $this->mymodel->selectWithQuery("SELECT follower FROM influencer WHERE id = '$id_influencer'");
                $creator = $creator ? $creator[0] : array();
                $follower = isset($creator['follower']) ? intval($creator['follower']) : 0;
                if ($follower > 0) {
                    $batas = intval($follower * $this->fyp_percentage / 100);
                    if ($dt['views'] >= $batas) {
                        $dt['is_fyp'] = "1";
                    }
                }
            }

            $dtt = $dt;
            unset($dt['is_fyp']);
            unset($dtt['id_endorse']);
            unset($dtt['id_campaign']);
            unset($dtt['date']);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");

            $this->db->update('endorse', $dtt, array('id' => $id_endorse));

            if ($v['total_cost'] > 0 && $dt['views'] > 0) {
                $dt['cpm'] = doubleval($v['total_cost']) / doubleval($dt['views']) * 1000;
            } else {
                $dt['cpm'] = 0;
            }

            $dt['total_cost'] = doubleval($v['total_cost']);
            $dt['link_upload'] = strval($v['link_upload']);
            $dt['platform'] = strval($v['platform']);

            $dt['likes_after'] = intval($dt['likes']);
            $dt['comment_after'] = intval($dt['comment']);
            $dt['share_save_after'] = intval($dt['share_save']);
            $dt['views_after'] = intval($dt['views']);

            if ($v['total_cost'] > 0 && $dt['views_after'] > 0) {
                $dt['cpm_after'] = doubleval($v['total_cost']) / doubleval($dt['views_after']) * 1000;
            } else {
                $dt['cpm_after'] = 0;
            }

            $dt['likes'] -= intval($query_yesterday['likes_after']);
            $dt['comment'] -= intval($query_yesterday['comment_after']);
            $dt['share_save'] -= intval($query_yesterday['share_save_after']);
            $dt['views'] -= intval($query_yesterday['views_after']);

            if ($v['total_cost'] > 0 && $dt['views'] > 0) {
                $dt['cpm'] = doubleval($v['total_cost']) / doubleval($dt['views']) * 1000;
            } else {
                $dt['cpm'] = 0;
            }

            $dt['likes_before'] = intval($query_yesterday['likes_after']);
            $dt['comment_before'] = intval($query_yesterday['comment_after']);
            $dt['share_save_before'] = intval($query_yesterday['share_save_after']);
            $dt['views_before'] = intval($query_yesterday['views_after']);

            if ($v['total_cost'] > 0 && $dt['views_before'] > 0) {
                $dt['cpm_before'] = doubleval($v['total_cost']) / doubleval($dt['views_before']) * 1000;
            } else {
                $dt['cpm_before'] = 0;
            }

            $dt['brand'] = strval($vl['brand']);

            $dt_tmp = array();
            foreach ($dt as $kt => $vt) {
                $dt_tmp[$kt] = strval($vt);
            }
            $dt = $dt_tmp;

            if ($query) {
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = strval($user['id']);
                $this->db->update('endorse_logs', $dt, array('id' => $query['id']));
                $id_parent = $query['id'];
            } else {
                $dt['created_at'] = DATE("Y-m-d H:i:s");
                $dt['created_by'] = strval($user['id']);
                $this->db->insert('endorse_logs', $dt);
                $id_parent = $this->db->insert_id();
            }

            $dt_tmp = array();
            foreach ($dtt as $kt => $vt) {
                $dt_tmp[$kt] = strval($vt);
            }
            $dtt = $dt_tmp;

            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            $dtt['updated_by'] = strval($user['id']);
            $this->db->update('endorse', $dtt, array('id' => $v['id']));
        }

        $limited_deactivated += $this->deactivate_limited_update_endorse_content();

        $data = $this->mymodel->selectWithQuery("SELECT id FROM endorse_campaign WHERE status = 'Aktif'");
        foreach ($data as $k => $v) {
            $id_parent = $v['id'];
            $this->update_endorse_parent($id_parent, $v);
        }

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = count($list) . " data endorse Threads yg di sync <= $todayy berhasil diperbarui. $limited_deactivated konten update terbatas dinonaktifkan.";
        echo json_encode($html, true);
        die;
    }

    function marketplace_order_download()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $batch_size = 500;

        // Proses inisialisasi parameter
        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE('Y-m-d');
            $start_date = DATE('Y-m-d', strtotime($start_date . " -31 days"));
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

        // Build query
        $qry = " DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' ";

        if ($id) {
            $qry .= " AND customer = '$id' ";
        }

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id IN ($ids) ";
        }

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

        $order_type = $_GET['order_type'];
        $data['order_type'] = $order_type;
        if ($order_type == "Manual") {
            $qry .= " AND is_manual = 1 ";
        } else if ($order_type == "Marketplace") {
            $qry .= " AND is_manual = 0 ";
        }


        // Setup filename dan path
        $filename = 'ORDER.';
        if ($marketplace) {
            $filename .= $marketplace . '.';
        }
        $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);

        $user = $_SESSION['user'];
        $dt = array();
        $dt['title'] = $filename;
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = strval($user['id']);
        $dt['param'] = $this->template->get_param();
        
        $insert_result = $this->db->insert('download_file', $dt);
        if (!$insert_result) {
            return;
        }
        
        $id = $this->db->insert_id();

        $filename .= '.' . $id . '.xlsx';
        $file_path = str_replace('public/', '', FCPATH . 'assets/webfile/excel/') . $filename;

        // Update file path
        $dt = array();
        $dt['title'] = $filename;
        $dt['file'] = $file_path;
        $update_result = $this->db->update('download_file', $dt, array('id' => $id));
        if (!$update_result) {
            return;
        }

        // Inisialisasi Spreadsheet
        try {
            $this->spreadsheet = new Spreadsheet();
        } catch (Exception $e) {
            return;
        }

        $this->spreadsheet->getProperties()
            ->setCreator('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setLastModifiedBy('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setTitle('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setSubject('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setDescription('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setKeywords('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date));

        // Get product data for header
        $product_query = $this->mymodel->selectWithQuery("SELECT * FROM product ORDER BY brand ASC, sub_name ASC");
        if (!$product_query) {
            return;
        }
        $data['product'] = $product_query;

        // Setup headers
        $header_1 = array("ID", "TGL ORDER", "TGL RTS", "ORDER ID", "BRAND", "KET", "KODE CS", "CB/CL", "NAMA", "NO HP", "USERNAME", "ALAMAT", "KAB", "PROV", "PESANAN");
        
        $header_2 = array();
        foreach ($data['product'] as $k => $v) {
            $header_2[] = strtoupper($v['sub_name']);
        }

        $header_3 = array("OMSET KOTOR", "DISKON & VOUCHER PENJUAL", "BIAYA LAINNYA", "OMSET BERSIH", "MARKETPLACE FEE", "AFFILIATE FEE", "TOTAL PENCAIRAN DANA", "IS_CAIR", "RETURN", "JENIS PEMBAYARAN", "JUMLAH", "TANGGAL TF", "TANGGAL CEK", "ACC", "EKSPEDISI", "NO RESI", "ALAMAT", "PROV", "KAB", "KEC", "CATATAN", "STATUS ORDER");


        // Create headers in spreadsheet
        $i = 0;
        foreach ($header_1 as $kk => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('dcdcdb');
            $i++;
        }

        foreach ($header_2 as $kk => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('ffff00');
            $i++;
        }

        foreach ($header_3 as $k => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('dcdcdb');
            $i++;
        }

        $body_1 = array("id", "date", "rts_at", "order_id", "brand", "marketplace", "cs", "cb_cl", "customer_text", "phone", "c_username", "address", "city_text", "province_text", "pesanan");
        $body_2 = array();
        foreach ($data['product'] as $k => $v) {
            $body_2[] = $v['id'];
        }
        $body_3 = array("omset_kotor", "diskon_penjual", "biaya_lainnya", "omset_bersih", "marketplace_fee", "komisi_afiliasi", "dana_pencairan", "is_disbursement", "return", "payment_type", "dibayar", "pay_at", "check_at", "acc", "shipping", "awb_number", "address", "province_text", "city_text", "subdistrict_text", "desc", "order_status");
        $sheet = $this->spreadsheet->getActiveSheet();

        // Set fixed column widths (auto-size is very slow on large datasets)
        $col_widths = [15, 12, 12, 22, 10, 12, 8, 8, 25, 16, 18, 35, 18, 18, 40];
        foreach ($col_widths as $ci => $width) {
            $sheet->getColumnDimensionByColumn($ci + 1)->setWidth($width);
        }
        $total_cols = count($body_1) + count($body_2) + count($body_3);
        for ($ci = count($col_widths); $ci < $total_cols; $ci++) {
            $sheet->getColumnDimensionByColumn($ci + 1)->setWidth(14);
        }

        $count_query = $this->mymodel->selectWithQuery("
            SELECT COUNT(*) AS total
            FROM transaction
            WHERE $qry
            AND type_sub = 'POS'
        ");
        $total_rows = (int)($count_query[0]['total'] ?? 0);

        if ($total_rows <= 0) {
            $dt = array();
            $dt['updated_at'] = date("Y-m-d H:i:s");
            $this->db->update('download_file', $dt, array('id' => $id));
            return;
        }

        $write_row = 2;
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

                foreach ($body_1 as $v2) {
                    if ($v2 == "order_id" || $v2 == "phone") {
                        $row[] = " " . ($v[$v2] ?? '');
                    } elseif ($v2 == "pesanan") {
                        $list = json_decode($v[$v2] ?? '', true);
                        $pesanan_text = '';
                        if (is_array($list)) {
                            foreach ($list as $vv) {
                                $qty = $vv['qty'] ?? '';
                                $item_name = $vv['item_name'] ?? '';
                                $pesanan_text .= $qty . "x " . $item_name . "\n";
                            }
                        }
                        $row[] = trim($pesanan_text);
                    } else {
                        $row[] = $v[$v2] ?? '';
                    }
                }

                $json = json_decode($v['json'] ?? '', true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
                    $json = array();
                }

                foreach ($body_2 as $v2) {
                    $row[] = isset($json[$v2]['qty']) ? $json[$v2]['qty'] : '';
                }

                foreach ($body_3 as $v2) {
                    $row[] = $v[$v2] ?? '';
                }

                $rows[] = $row;
            }

            if (!empty($rows)) {
                $sheet->fromArray($rows, null, 'A' . $write_row, true);
                $write_row += count($rows);
            }
        }

        // Save file
        try {
            $writer = new Xlsx($this->spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save($file_path);

            if (file_exists($file_path)) {
                $dt = array();
                $dt['updated_at'] = date("Y-m-d H:i:s");
                $this->db->update('download_file', $dt, array('id' => $id));
            }
        } catch (Exception $e) {
            return;
        }

    }

    function booking_shopee_download()
    {

        // Proses inisialisasi parameter
        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE('Y-m-d');
            $start_date = DATE('Y-m-d', strtotime($start_date . " -31 days"));
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

        // Build query
        $qry = " DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' ";

        if ($id) {
            $qry .= " AND customer = '$id' ";
        }

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id IN ($ids) ";
        }

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

        $order_type = $_GET['order_type'];
        $data['order_type'] = $order_type;
        if ($order_type == "Manual") {
            $qry .= " AND is_manual = 1 ";
        } else if ($order_type == "Marketplace") {
            $qry .= " AND is_manual = 0 ";
        }


        // Setup filename dan path
        $filename = 'ORDER.';
        if ($marketplace) {
            $filename .= $marketplace . '.';
        }
        $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);

        $user = $_SESSION['user'];
        $dt = array();
        $dt['title'] = $filename;
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = strval($user['id']);
        $dt['param'] = $this->template->get_param();
        
        $insert_result = $this->db->insert('download_file', $dt);
        if (!$insert_result) {
            return;
        }
        
        $id = $this->db->insert_id();

        $filename .= '.' . $id . '.xlsx';
        $file_path = str_replace('public/', '', FCPATH . 'assets/webfile/excel/') . $filename;

        // Update file path
        $dt = array();
        $dt['title'] = $filename;
        $dt['file'] = $file_path;
        $update_result = $this->db->update('download_file', $dt, array('id' => $id));
        if (!$update_result) {
            return;
        }

        // Get data transaction
        $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction
        WHERE $qry 
        AND type_sub = 'POS' 
        ORDER BY date DESC, id DESC
        ");
        
        if (!$query) {
            return;
        }
        
        $data['data'] = $query;

        // Inisialisasi Spreadsheet
        try {
            $this->spreadsheet = new Spreadsheet();
        } catch (Exception $e) {
            return;
        }

        $this->spreadsheet->getProperties()
            ->setCreator('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setLastModifiedBy('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setTitle('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setSubject('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setDescription('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setKeywords('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date));

        // Get product data for header
        $product_query = $this->mymodel->selectWithQuery("SELECT * FROM product ORDER BY brand ASC, sub_name ASC");
        if (!$product_query) {
            return;
        }
        $data['product'] = $product_query;

        // Setup headers
        $header_1 = array("ID", "TGL ORDER", "TGL RTS", "ORDER ID", "BRAND", "KET", "KODE CS", "CB/CL", "NAMA", "NO HP", "USERNAME", "ALAMAT", "KAB", "PROV", "PESANAN");
        
        $header_2 = array();
        foreach ($data['product'] as $k => $v) {
            $header_2[] = strtoupper($v['sub_name']);
        }

        $header_3 = array("OMSET KOTOR", "DISKON & VOUCHER PENJUAL", "BIAYA LAINNYA", "OMSET BERSIH", "MARKETPLACE FEE", "AFFILIATE FEE", "TOTAL PENCAIRAN DANA", "IS_CAIR", "RETURN", "JENIS PEMBAYARAN", "JUMLAH", "TANGGAL TF", "TANGGAL CEK", "ACC", "EKSPEDISI", "NO RESI", "ALAMAT", "PROV", "KAB", "KEC", "CATATAN", "STATUS ORDER");


        // Create headers in spreadsheet
        $i = 0;
        foreach ($header_1 as $kk => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('dcdcdb');
            $i++;
        }

        foreach ($header_2 as $kk => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('ffff00');
            $i++;
        }

        foreach ($header_3 as $k => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('dcdcdb');
            $i++;
        }

        // Fill data
        
        $body_1 = array("id", "date", "rts_at", "order_id", "brand", "marketplace", "cs", "cb_cl", "customer_text", "phone", "c_username", "address", "city_text", "province_text", "pesanan");
        
        $body_2 = array();
        foreach ($data['product'] as $k => $v) {
            $body_2[] = $v['id'];
        }
        
        $body_3 = array("omset_kotor", "diskon_penjual", "biaya_lainnya", "omset_bersih", "marketplace_fee", "komisi_afiliasi", "dana_pencairan", "is_disbursement", "return", "payment_type", "dibayar", "pay_at", "check_at", "acc", "shipping", "awb_number", "address", "province_text", "city_text", "subdistrict_text", "desc", "order_status");

        $column = 2;
        foreach ($data['data'] as $k => $v) {
            $index = 1;
            
            // Body 1
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
                    if (is_array($list)) {
                        foreach ($list as $kk => $vv) {
                            $v[$v2] .= $vv['qty'] . "x " . $vv['item_name'] . "\n";
                        }
                    }
                }
                $index_alpha = $this->template->get_name_from_number($index);
                $val = $v[$v2];

                if (is_string($val)) {
                    $this->spreadsheet->getActiveSheet()->setCellValueExplicit(
                        $index_alpha . $column,
                        $val,
                        DataType::TYPE_STRING
                    );
                } else {
                    $this->spreadsheet->getActiveSheet()->setCellValue($index_alpha . $column, $val);
                }

                $index++;
            }

            // Body 2
            $json = json_decode($v['json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $json = array();
            }

            foreach ($body_2 as $k2 => $v2) {
                $val = isset($json[$v2]['qty']) ? $json[$v2]['qty'] : '';
                $index_alpha = $this->template->get_name_from_number($index);
                $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $val);
                $index++;
            }
            
            // Body 3
            foreach ($body_3 as $k2 => $v2) {
                $index_alpha = $this->template->get_name_from_number($index);
                $val = $v[$v2];
                if (is_string($val)) {
                    $this->spreadsheet->getActiveSheet()->setCellValueExplicit(
                        $index_alpha . $column,
                        $val,
                        DataType::TYPE_STRING
                    );
                } else {
                    $this->spreadsheet->getActiveSheet()->setCellValue($index_alpha . $column, $val);
                }

                $index++;
            }

            $column++;
        }

        // Auto-size columns
        $sheet = $this->spreadsheet->getActiveSheet();
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        // Save file
        try {
            $writer = new Xlsx($this->spreadsheet);
            $writer->save($file_path);
            
            if (file_exists($file_path)) {
                $dt = array();
                $dt['updated_at'] = date("Y-m-d H:i:s");
                $this->db->update('download_file', $dt, array('id' => $id));
            }
        } catch (Exception $e) {
            return;
        }

    }

    public function get_handover_time_slots_by_package()
    {
        header('Content-Type: application/json');

        $transaction_id = $_POST['transaction_id'] ?? '';
        $shop_id = $_POST['shop_id'] ?? '';

        $package_id = '';
        if ($transaction_id) {
            $transaction_row = $this->mymodel->selectDataOne('transaction', ['order_id' => $transaction_id]);
            if ($transaction_row) {
                $package_id = $transaction_row['package_id'];
            }
        }
        if (!$package_id || !$shop_id) {
            echo json_encode(['status' => false, 'message' => 'package_id dan shop_id wajib diisi']);
            return;
        }

        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }

        $config       = json_decode($config_row['val'], true);
        $app_key      = $config['app_key'] ?? '';
        $access_token = $config['access_token'] ?? '';
        $shop_cipher  = $config['shop']['cipher'] ?? '';
        $app_secret   = $this->app_secret_tiktok;
        $shop_id_val  = $config['shop']['id'] ?? $shop_id;

        if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
            echo json_encode(['status' => false, 'message' => 'Config tidak lengkap']);
            return;
        }

        $endpoint_path = '/fulfillment/202309/packages/' . $package_id . '/handover_time_slots';

        $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path
            . '?access_token=' . rawurlencode($access_token)
            . '&app_key='      . rawurlencode($app_key)
            . '&shop_cipher='  . rawurlencode($shop_cipher)
            . '&shop_id='      . rawurlencode($shop_id_val)
            . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

        $urlParts  = parse_url($request_url);
        $paramGET  = [];
        parse_str($urlParts['query'], $paramGET);

        $timest = time();
        $pr = [
            'secret' => $app_secret,
            'timest' => $timest,
            'get'    => $paramGET,
            'post'   => '',
            'url'    => $request_url
        ];

        $sign = $this->tiktok_signature_generator($pr);

        $request_url_signed = str_replace('{{sign}}', $sign, $request_url);
        $request_url_signed = str_replace('{{timestamp}}', $timest, $request_url_signed);

        // Eksekusi request
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $request_url_signed,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-tts-access-token: ' . $access_token
            ],
        ]);

        $response_body = curl_exec($curl);
        $curl_error    = curl_error($curl);
        $http_code     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curl_error) {
            echo json_encode(['status' => false, 'message' => 'CURL Error: ' . $curl_error]);
            return;
        }

        $response_json = json_decode($response_body, true);
        
        if (isset($response_json['code']) && $response_json['code'] == 0) {
            echo json_encode([
                'status' => true,
                'data' => $response_json['data'] ?? [],
                'message' => 'Berhasil mengambil time slots'
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Gagal mengambil time slots: ' . ($response_json['message'] ?? 'Unknown error'),
                'raw' => $response_json
            ]);
        }
    }
    
    public function tts_ship_packages_bulk()
    {
        header('Content-Type: application/json');
    
        $transaction_ids_input = $_POST['transaction_ids'] ?? [];
        if (is_string($transaction_ids_input)) {
            $transaction_ids = array_filter(array_map('trim', explode(',', $transaction_ids_input)));
        } else {
            $transaction_ids = (array) $transaction_ids_input;
        }
    
        if (empty($transaction_ids)) {
            echo json_encode(['status' => false, 'message' => 'transaction_ids wajib (array atau CSV)']);
            return;
        }
    
        $handover_type = 'PICKUP';
        $pickup_start_time = null;
        $pickup_end_time = null;
    
        if ($handover_type === 'PICKUP') {
            $pickup_start_time_input = $_POST['pickup_start_time'] ?? '';
            $pickup_end_time_input = $_POST['pickup_end_time'] ?? '';
    
            if ($pickup_start_time_input === '' || $pickup_end_time_input === '') {
                echo json_encode(['status' => false, 'message' => 'pickup_start_time dan pickup_end_time wajib diisi']);
                return;
            }
    
            if (!ctype_digit((string)$pickup_start_time_input) || !ctype_digit((string)$pickup_end_time_input)) {
                echo json_encode(['status' => false, 'message' => 'pickup_start_time dan pickup_end_time harus berupa angka detik UNIX']);
                return;
            }
    
            $pickup_start_time = (int)$pickup_start_time_input;
            $pickup_end_time = (int)$pickup_end_time_input;
    
            if ($pickup_start_time >= $pickup_end_time) {
                echo json_encode(['status' => false, 'message' => 'pickup_end_time harus lebih besar dari pickup_start_time']);
                return;
            }
        }
    
        // 1. AMBIL SEMUA DATA TRANSAKSI SEKALIGUS DENGAN JOIN
        $placeholders = implode(',', array_fill(0, count($transaction_ids), '?'));
        $sql = "SELECT t.order_id, t.package_id, t.shop_id, mc.val as config_val 
                FROM transaction t 
                LEFT JOIN marketplace_config mc ON t.shop_id = mc.shop_id 
                WHERE t.order_id IN ($placeholders)";
        
        $transactions = $this->db->query($sql, $transaction_ids)->result_array();
        
        // Kelompokkan transaksi berdasarkan shop_id dan buat lookup
        $transactions_by_shop = [];
        $transaction_lookup = [];
        $missing_transactions = [];
        
        foreach ($transaction_ids as $tx_id) {
            $found = false;
            foreach ($transactions as $tx) {
                if ($tx['order_id'] === $tx_id) {
                    $found = true;
                    $shop_id = $tx['shop_id'];
                    if (!isset($transactions_by_shop[$shop_id])) {
                        $transactions_by_shop[$shop_id] = [
                            'transactions' => [],
                            'config_val' => $tx['config_val']
                        ];
                    }
                    $transactions_by_shop[$shop_id]['transactions'][] = $tx;
                    $transaction_lookup[$tx_id] = $tx;
                    break;
                }
            }
            if (!$found) {
                $missing_transactions[] = $tx_id;
            }
        }
    
        $results = [];
    
        // 2. PROSES SETIAP SHOP DENGAN BATCHING (MAKSIMAL 50 PER REQUEST)
        $successful_updates = [];
        
        foreach ($transactions_by_shop as $shop_id => $shop_data) {
            $config_val = json_decode($shop_data['config_val'] ?? '{}', true);
            
            if (empty($config_val)) {
                foreach ($shop_data['transactions'] as $tx) {
                    $results[] = [
                        'transaction_id' => $tx['order_id'],
                        'status' => false,
                        'message' => 'Config toko tidak ditemukan'
                    ];
                }
                continue;
            }
    
            $app_key = $config_val['app_key'] ?? '';
            $access_token = $config_val['access_token'] ?? '';
            $shop_cipher = $config_val['shop']['cipher'] ?? '';
            $app_secret = $this->app_secret_tiktok;
            $shop_id_val = $config_val['shop']['id'] ?? $shop_id;
    
            if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
                foreach ($shop_data['transactions'] as $tx) {
                    $results[] = [
                        'transaction_id' => $tx['order_id'],
                        'status' => false,
                        'message' => 'Config tidak lengkap'
                    ];
                }
                continue;
            }
    
            // Bagi transactions menjadi chunk maksimal 50
            $transaction_chunks = array_chunk($shop_data['transactions'], 50);
            
            foreach ($transaction_chunks as $chunk_index => $transaction_chunk) {
                // Siapkan packages data untuk bulk request (maksimal 50)
                $packages_data = [];
                $package_to_transaction = [];
                
                foreach ($transaction_chunk as $tx) {
                    $package_id = $tx['package_id'] ?? '';
                    if (!$package_id) {
                        $results[] = [
                            'transaction_id' => $tx['order_id'],
                            'status' => false,
                            'message' => 'package_id kosong'
                        ];
                        continue;
                    }
    
                    $package_data = [
                        'id' => $package_id,
                        'handover_method' => $handover_type,
                    ];
    
                    if ($handover_type === 'PICKUP') {
                        $package_data['pickup_slot'] = [
                            'start_time' => (int)$pickup_start_time,
                            'end_time' => (int)$pickup_end_time
                        ];
                    }
    
                    $packages_data[] = $package_data;
                    $package_to_transaction[$package_id] = $tx['order_id'];
                }
    
                if (empty($packages_data)) {
                    continue;
                }
    
                // Persiapkan request untuk chunk ini
                $endpoint_path = '/fulfillment/202309/packages/ship';
                $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path
                    . '?access_token=' . rawurlencode($access_token)
                    . '&app_key='      . rawurlencode($app_key)
                    . '&shop_cipher='  . rawurlencode($shop_cipher)
                    . '&shop_id='      . rawurlencode($shop_id_val)
                    . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';
    
                $request_body_json = json_encode([
                    'packages' => $packages_data
                ], JSON_UNESCAPED_SLASHES);
    
                $urlParts  = parse_url($request_url);
                $paramGET  = [];
                parse_str($urlParts['query'], $paramGET);
    
                $timest = time();
                $pr = [
                    'secret' => $app_secret,
                    'timest' => $timest,
                    'get'    => $paramGET,
                    'post'   => $request_body_json,
                    'url'    => $request_url
                ];
    
                $sign = $this->tiktok_signature_generator($pr);
    
                $request_url_signed = str_replace(['{{sign}}', '{{timestamp}}'], [$sign, $timest], $request_url);
    
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL            => $request_url_signed,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING       => '',
                    CURLOPT_MAXREDIRS      => 10,
                    CURLOPT_TIMEOUT        => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST  => 'POST',
                    CURLOPT_POSTFIELDS     => $request_body_json,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'x-tts-access-token: ' . $access_token
                    ],
                ]);
    
                $response_body = curl_exec($curl);
                $curl_error = curl_error($curl);
                $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
    
                if ($curl_error) {
                    foreach ($transaction_chunk as $tx) {
                        $results[] = [
                            'transaction_id' => $tx['order_id'],
                            'status' => false,
                            'message' => 'CURL Error: ' . $curl_error
                        ];
                    }
                    continue;
                }
    
                $response_json = json_decode($response_body, true);
                
                if (isset($response_json['code']) && $response_json['code'] == 0) {
                    // Semua package dalam chunk ini berhasil
                    foreach ($transaction_chunk as $tx) {
                        $successful_updates[] = $tx['order_id'];
                        $results[] = [
                            'transaction_id' => $tx['order_id'],
                            'status' => true,
                            'message' => 'Ship Package sukses',
                            'package_id' => $tx['package_id'],
                            'raw' => $response_json
                        ];
                    }
                } else {
                    $error_message = $response_json['message'] ?? 'Unknown error';
                    
                    if (isset($response_json['data']['failed_packages'])) {
                        $failed_packages = $response_json['data']['failed_packages'];
                        $success_packages = $response_json['data']['success_packages'] ?? [];
                        
                        // Process failed packages
                        foreach ($failed_packages as $failed_pkg) {
                            $package_id = $failed_pkg['package_id'] ?? '';
                            if (isset($package_to_transaction[$package_id])) {
                                $transaction_id = $package_to_transaction[$package_id];
                                $results[] = [
                                    'transaction_id' => $transaction_id,
                                    'status' => false,
                                    'message' => 'Ship Package gagal: ' . ($failed_pkg['message'] ?? $error_message),
                                    'package_id' => $package_id,
                                    'raw' => $response_json
                                ];
                            }
                        }
                        
                        // Process success packages
                        foreach ($success_packages as $success_pkg) {
                            $package_id = $success_pkg['package_id'] ?? '';
                            if (isset($package_to_transaction[$package_id])) {
                                $transaction_id = $package_to_transaction[$package_id];
                                $successful_updates[] = $transaction_id;
                                $results[] = [
                                    'transaction_id' => $transaction_id,
                                    'status' => true,
                                    'message' => 'Ship Package sukses',
                                    'package_id' => $package_id,
                                    'raw' => $response_json
                                ];
                            }
                        }
                    } else {
                        // Jika tidak ada detail per package, anggap semua dalam chunk gagal
                        foreach ($transaction_chunk as $tx) {
                            $results[] = [
                                'transaction_id' => $tx['order_id'],
                                'status' => false,
                                'message' => 'Ship Package gagal: ' . $error_message,
                                'package_id' => $tx['package_id'],
                                'raw' => $response_json
                            ];
                        }
                    }
                }
    
                // Tambahkan delay kecil antara request untuk menghindari rate limiting
                if (count($transaction_chunks) > 1 && $chunk_index < count($transaction_chunks) - 1) {
                    usleep(500000); // 0.5 detik delay
                }
            }
        }
    
        // 3. BATCH UPDATE UNTUK SEMUA TRANSAKSI YANG BERHASIL
        if (!empty($successful_updates)) {
            $placeholders = implode(',', array_fill(0, count($successful_updates), '?'));
            $update_sql = "UPDATE transaction SET rts_at = ?, order_status = ? WHERE order_id IN ($placeholders)";
            $params = array_merge([date('Y-m-d H:i:s'), 'PROCESSED'], $successful_updates);
            $this->db->query($update_sql, $params);
        }
    
        // 4. HANDLE TRANSAKSI YANG TIDAK DITEMUKAN
        foreach ($missing_transactions as $tx_id) {
            $results[] = [
                'transaction_id' => $tx_id, 
                'status' => false, 
                'message' => 'Transaksi tidak ditemukan'
            ];
        }
    
        echo json_encode(['status' => true, 'results' => $results]);
    }
    
    public function tts_get_shipping_documents_bulk()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Content-Type: application/json');
    
        // Handle preflight request
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit(0);
        }
    
        $transaction_ids_input = $_POST['transaction_ids'] ?? [];
        if (is_string($transaction_ids_input)) {
            $transaction_ids = array_filter(array_map('trim', explode(',', $transaction_ids_input)));
        } else {
            $transaction_ids = (array) $transaction_ids_input;
        }
    
        if (empty($transaction_ids)) {
            echo json_encode(['status' => false, 'message' => 'transaction_ids wajib (array atau CSV)']);
            return;
        }
    
        // 1. AMBIL SEMUA DATA TRANSAKSI SEKALIGUS
        $placeholders = implode(',', array_fill(0, count($transaction_ids), '?'));
        $sql = "SELECT t.order_id, t.package_id, t.shop_id, mc.val as config_val 
                FROM transaction t 
                LEFT JOIN marketplace_config mc ON t.shop_id = mc.shop_id 
                WHERE t.order_id IN ($placeholders)";
        
        $transactions = $this->db->query($sql, $transaction_ids)->result_array();
        
        // Group by shop_id untuk optimasi lebih lanjut
        $transactions_by_shop = [];
        $transaction_lookup = [];
        
        foreach ($transactions as $tx) {
            $transactions_by_shop[$tx['shop_id']][] = $tx;
            $transaction_lookup[$tx['order_id']] = $tx;
        }
    
        $results = [];
        $doc_type = strtoupper('SHIPPING_LABEL_PICTURE');
        $label_size = 'A6';
    
        foreach ($transactions_by_shop as $shop_id => $shop_transactions) {
            $first_tx = $shop_transactions[0];
            $config_val = json_decode($first_tx['config_val'] ?? '{}', true);
            
            if (empty($config_val)) {
                foreach ($shop_transactions as $tx) {
                    $results[] = [
                        'transaction_id' => $tx['order_id'], 
                        'status' => false, 
                        'message' => 'Config toko tidak ditemukan'
                    ];
                }
                continue;
            }
    
            $app_key = $config_val['app_key'] ?? '';
            $access_token = $config_val['access_token'] ?? '';
            $shop_cipher = $config_val['shop']['cipher'] ?? '';
            $app_secret = $this->app_secret_tiktok;
            $shop_id_val = $config_val['shop']['id'] ?? $shop_id;
    
            if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
                foreach ($shop_transactions as $tx) {
                    $results[] = [
                        'transaction_id' => $tx['order_id'],
                        'status' => false,
                        'message' => 'Config tidak lengkap'
                    ];
                }
                continue;
            }
    
            // OPTIMASI: Gunakan batch size lebih besar dengan connection limit
            $batch_size = 40; // Increased batch size
            $transaction_batches = array_chunk($shop_transactions, $batch_size);
            
            $successful_updates = [];
    
            foreach ($transaction_batches as $batch_index => $batch_transactions) {
                $multi_curl = curl_multi_init();
                $curl_handlers = [];
                $package_to_tx = [];
    
                // OPTIMASI: Set konfigurasi multi curl untuk performa lebih baik
                curl_multi_setopt($multi_curl, CURLMOPT_MAXCONNECTS, 30);
                curl_multi_setopt($multi_curl, CURLMOPT_MAX_HOST_CONNECTIONS, 10);
    
                foreach ($batch_transactions as $tx) {
                    $package_id = $tx['package_id'] ?? '';
                    if (!$package_id) {
                        $results[] = [
                            'transaction_id' => $tx['order_id'],
                            'status' => false,
                            'message' => 'package_id kosong'
                        ];
                        continue;
                    }
    
                    $endpoint_path = '/fulfillment/202309/packages/' . $package_id . '/shipping_documents';
                    $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path
                        . '?app_key=' . rawurlencode($app_key)
                        . '&shop_cipher=' . rawurlencode($shop_cipher)
                        . '&document_type=' . rawurlencode($doc_type)
                        . '&document_size=' . rawurlencode($label_size)
                        . '&sign={{sign}}'
                        . '&timestamp={{timestamp}}'
                        . '&version=202309';
    
                    $urlParts = parse_url($request_url);
                    $paramGET = [];
                    parse_str($urlParts['query'], $paramGET);
    
                    $timest = time();
                    $pr = [
                        'secret' => $app_secret,
                        'timest' => $timest,
                        'get' => $paramGET,
                        'post' => '',
                        'url' => $request_url
                    ];
    
                    $sign = $this->tiktok_signature_generator($pr);
                    $request_url_signed = str_replace(['{{sign}}', '{{timestamp}}'], [$sign, $timest], $request_url);
    
                    $curl = curl_init();
                    
                    // OPTIMASI: Kurang timeout dan optimasi curl options
                    curl_setopt_array($curl, [
                        CURLOPT_URL => $request_url_signed,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 5, // Reduced
                        CURLOPT_TIMEOUT => 30,  // Reduced from 60 to 30
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => [
                            'Content-Type: application/json',
                            'x-tts-access-token: ' . $access_token
                        ],
                        CURLOPT_SSL_VERIFYPEER => false, // OPTIONAL: untuk percepatan
                        CURLOPT_SSL_VERIFYHOST => false, // OPTIONAL: untuk percepatan
                    ]);
    
                    $curl_handlers[$package_id] = $curl;
                    $package_to_tx[$package_id] = $tx['order_id'];
                    curl_multi_add_handle($multi_curl, $curl);
                }
    
                // OPTIMASI: Eksekusi multi curl dengan timeout lebih agresif
                $running = null;
                $start_time = microtime(true);
                
                do {
                    $status = curl_multi_exec($multi_curl, $running);
                    if ($running) {
                        // Kurangi timeout untuk respons lebih cepat
                        curl_multi_select($multi_curl, 0.05); // Reduced from 0.1 to 0.05
                    }
                    
                    // Timeout safety: maksimal 15 detik per batch
                    if ((microtime(true) - $start_time) > 15) {
                        break;
                    }
                } while ($running > 0);
    
                // Process responses untuk batch saat ini
                foreach ($curl_handlers as $package_id => $curl) {
                    $tx_id = $package_to_tx[$package_id];
                    $response_body = curl_multi_getcontent($curl);
                    $curl_error = curl_error($curl);
                    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
                    if ($curl_error) {
                        $results[] = ['transaction_id' => $tx_id, 'status' => false, 'message' => $curl_error];
                        continue;
                    }
    
                    $response_json = json_decode($response_body, true);
                    
                    // Handle rate limiting
                    if ($http_code === 429 || (isset($response_json['code']) && $response_json['code'] == 36009002)) {
                        $results[] = [
                            'transaction_id' => $tx_id,
                            'status' => false,
                            'message' => 'Rate limit exceeded',
                        ];
                        continue;
                    }
    
                    if (isset($response_json['code']) && $response_json['code'] != 0) {
                        $results[] = [
                            'transaction_id' => $tx_id,
                            'status' => false,
                            'message' => 'Get Shipping Document gagal: ' . ($response_json['message'] ?? 'Unknown'),
                            'raw' => $response_json
                        ];
                        continue;
                    }
    
                    $document_urls = $response_json['data']['document_urls'] ?? $response_json['data'] ?? [];
                    
                    $successful_updates[] = $tx_id;
                    
                    $results[] = [
                        'transaction_id' => $tx_id,
                        'status' => true,
                        'message' => 'OK',
                        'package_id' => $package_id,
                        'document_urls' => $document_urls
                    ];
    
                    curl_multi_remove_handle($multi_curl, $curl);
                    curl_close($curl);
                }
    
                curl_multi_close($multi_curl);
    
                // OPTIMASI: Kurangi delay antara batch
                if (count($transaction_batches) > 1 && $batch_index < count($transaction_batches) - 1) {
                    // Delay minimal antara batch
                    usleep(100000); // Hanya 0.1 detik delay antara batch
                }
            }
    
            $today = date('Y-m-d H:i:s');
    
            if (!empty($successful_updates)) {
                $data = array(
                    'print_at' => $today
                );
                
                $this->db->where_in('order_id', $successful_updates);
                $this->db->update('transaction', $data);
            }
        }
    
        foreach ($transaction_ids as $tx_id) {
            if (!isset($transaction_lookup[$tx_id])) {
                $results[] = ['transaction_id' => $tx_id, 'status' => false, 'message' => 'Transaksi tidak ditemukan'];
            }
        }
    
        echo json_encode(['status' => true, 'results' => $results]);
    }

    public function shopee_get_mass_shipping_parameter()
    {
        header('Content-Type: application/json');

        $transaction_ids_input = $_POST['transaction_ids'] ?? '';
        if (is_string($transaction_ids_input)) {
            $transaction_ids = array_filter(array_map('trim', explode(',', $transaction_ids_input)));
        } else {
            $transaction_ids = (array) $transaction_ids_input;
        }

        $selected_shipping_method = $_POST['shipping_method'] ?? '';

        if (empty($transaction_ids)) {
            echo json_encode(['status' => false, 'message' => 'transaction_ids wajib']);
            return;
        }

        $shop_id = $_POST['shop_id'] ?? '';
        if (!$shop_id) {
            echo json_encode(['status' => false, 'message' => 'shop_id wajib']);
            return;
        }

        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }

        $config = json_decode($config_row['val'], true);
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $access_token = $config['access_token'];
        $host = 'https://partner.shopeemobile.com';

        $this->refresh_shopee_transactions_for_mass_shipping($transaction_ids, $shop_id, $config);

        $placeholders = implode(',', array_fill(0, count($transaction_ids), '?'));
        $sql = "SELECT id, order_id, package_id, shipping
                FROM transaction
                WHERE id IN ($placeholders)
                  AND shop_id = ?
                  AND marketplace = 'SHOPEE'
                  AND order_status = 'READY_TO_SHIP'
                  AND package_id IS NOT NULL AND package_id != ''";

        $params = $transaction_ids;
        $params[] = $shop_id;
        $results = $this->db->query($sql, $params)->result_array();

        if (empty($results)) {
            echo json_encode([
                'status' => false,
                'message' => 'Tidak ada order dengan status READY_TO_SHIP yang memiliki package number valid setelah refresh data Shopee'
            ]);
            return;
        }

        if ($selected_shipping_method) {
            $results = array_values(array_filter($results, function ($row) use ($selected_shipping_method) {
                return isset($row['shipping']) && $row['shipping'] === $selected_shipping_method;
            }));

            if (empty($results)) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Tidak ada order dengan kurir yang dipilih'
                ]);
                return;
            }
        }

        $shipping_methods = array_unique(array_column($results, 'shipping'));
        
        if (count($shipping_methods) > 1 && !$selected_shipping_method) {
            echo json_encode([
                'status' => false, 
                'message' => 'Kurir harus sama untuk semua order',
                'found_shipping_methods' => array_values($shipping_methods)
            ]);
            return;
        }

        $shipping_method = $selected_shipping_method ?: $shipping_methods[0];

        // Cukup 1 package sebagai sampel — address & time slot bersifat shop-level
        $sample_package = [['package_number' => $results[0]['package_id']]];
        $total_packages = count($results);

        $path = "/api/v2/logistics/get_mass_shipping_parameter";
        $timest = time();
        $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
        $sign = hash_hmac('sha256', $baseString, $partner_key);

        $url = $host . $path .
            '?partner_id=' . $partner_id .
            '&timestamp=' . $timest .
            '&access_token=' . $access_token .
            '&shop_id=' . $shop_id .
            '&sign=' . $sign;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode(['package_list' => $sample_package]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);

        $response   = curl_exec($curl);
        $curl_error = curl_error($curl);
        curl_close($curl);

        if ($curl_error) {
            echo json_encode(['status' => false, 'message' => 'CURL Error: ' . $curl_error]);
            return;
        }

        $shipping_params = json_decode($response, true);

        if (isset($shipping_params['error']) && !empty($shipping_params['error'])) {
            echo json_encode(['status' => false, 'message' => 'Gagal mendapatkan parameter pengiriman: ' . ($shipping_params['message'] ?? 'API Error')]);
            return;
        }

        if (!isset($shipping_params['response'])) {
            echo json_encode(['status' => false, 'message' => 'Gagal mendapatkan parameter pengiriman: Data result tidak ditemukan']);
            return;
        }

        $pickup_response = $shipping_params['response'];

        $formatted_response = [
            'status'          => true,
            'shipping_method' => $shipping_method,
            'total_packages'  => $total_packages,
            'pickup_info'     => [
                'address_list'  => [],
                'time_slot_list' => []
            ]
        ];

        if (isset($pickup_response['pickup']['address_list'])) {
            foreach ($pickup_response['pickup']['address_list'] as $address) {
                $formatted_response['pickup_info']['address_list'][] = [
                    'address_id' => $address['address_id'] ?? '',
                    'address'    => $address['address'] ?? '',
                ];
            }
        }

        if (isset($pickup_response['pickup']['address_list'][0]['time_slot_list'])) {
            foreach ($pickup_response['pickup']['address_list'][0]['time_slot_list'] as $time_slot) {
                $formatted_response['pickup_info']['time_slot_list'][] = [
                    'date'           => $time_slot['date'] ?? '',
                    'pickup_time_id' => $time_slot['pickup_time_id'] ?? '',
                    'time_text'      => $time_slot['time_text'] ?? '',
                ];
            }
        }

        echo json_encode($formatted_response);
    }

    private function refresh_shopee_transactions_for_mass_shipping(array $transaction_ids, $shop_id, array $config)
    {
        if (empty($transaction_ids) || !$shop_id) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($transaction_ids), '?'));
        $params = $transaction_ids;
        $params[] = $shop_id;

        $rows = $this->db->query(
            "SELECT id, order_id, order_status, package_id
             FROM transaction
             WHERE id IN ($placeholders)
               AND shop_id = ?
               AND marketplace = 'SHOPEE'",
            $params
        )->result_array();

        if (empty($rows)) {
            return;
        }

        $access_token = $config['access_token'] ?? '';
        if ($access_token === '') {
            return;
        }

        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $host = 'https://partner.shopeemobile.com';
        $path = "/api/v2/order/get_order_detail";
        $orders_by_sn = [];

        foreach ($rows as $row) {
            $order_sn = trim((string) ($row['order_id'] ?? ''));
            if ($order_sn === '') {
                continue;
            }
            $orders_by_sn[$order_sn] = $row;
        }

        if (empty($orders_by_sn)) {
            return;
        }

        foreach (array_chunk(array_keys($orders_by_sn), 50) as $order_chunk) {
            $timest = time();
            $base_string = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $base_string, $partner_key);

            $url = $host . $path . '?' . http_build_query([
                'access_token' => $access_token,
                'order_sn_list' => implode(',', $order_chunk),
                'partner_id' => $partner_id,
                'shop_id' => $shop_id,
                'sign' => $sign,
                'timestamp' => $timest,
                'response_optional_fields' => 'order_status,package_list,shipping_carrier',
                'request_order_status_pending' => 'true',
            ]);

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);

            $response_raw = curl_exec($curl);
            curl_close($curl);

            if (!$response_raw) {
                continue;
            }

            $response = json_decode($response_raw, true);
            $order_list = $response['response']['order_list'] ?? [];
            if (empty($order_list) || !is_array($order_list)) {
                continue;
            }

            foreach ($order_list as $order_detail) {
                $order_sn = (string) ($order_detail['order_sn'] ?? '');
                if ($order_sn === '' || !isset($orders_by_sn[$order_sn])) {
                    continue;
                }

                $existing_row = $orders_by_sn[$order_sn];
                $new_status = (string) ($order_detail['order_status'] ?? $existing_row['order_status']);
                $new_package_id = (string) ($existing_row['package_id'] ?? '');

                if (!empty($order_detail['package_list']) && is_array($order_detail['package_list'])) {
                    $first_package = $order_detail['package_list'][0] ?? [];
                    $new_package_id = (string) (
                        $first_package['package_number']
                        ?? $first_package['package_id']
                        ?? $new_package_id
                    );
                }

                $update_data = [
                    'order_status' => $new_status,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if ($new_package_id !== '') {
                    $update_data['package_id'] = $new_package_id;
                }

                if (!empty($order_detail['shipping_carrier'])) {
                    $update_data['shipping'] = (string) $order_detail['shipping_carrier'];
                }

                $this->db->where('id', $existing_row['id']);
                $this->db->update('transaction', $update_data);
            }
        }
    }

    private function refresh_shopee_transactions_by_order_ids(array $order_ids, $shop_id, array $config)
    {
        if (empty($order_ids) || !$shop_id) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
        $params = $order_ids;
        $params[] = $shop_id;

        $rows = $this->db->query(
            "SELECT id, order_id, order_status, package_id
             FROM transaction
             WHERE order_id IN ($placeholders)
               AND shop_id = ?
               AND marketplace = 'SHOPEE'",
            $params
        )->result_array();

        if (empty($rows)) {
            return;
        }

        $access_token = $config['access_token'] ?? '';
        if ($access_token === '') {
            return;
        }

        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $host = 'https://partner.shopeemobile.com';
        $path = "/api/v2/order/get_order_detail";
        $orders_by_sn = [];

        foreach ($rows as $row) {
            $order_sn = trim((string) ($row['order_id'] ?? ''));
            if ($order_sn === '') {
                continue;
            }
            $orders_by_sn[$order_sn] = $row;
        }

        if (empty($orders_by_sn)) {
            return;
        }

        foreach (array_chunk(array_keys($orders_by_sn), 50) as $order_chunk) {
            $timest = time();
            $base_string = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $base_string, $partner_key);

            $url = $host . $path . '?' . http_build_query([
                'access_token' => $access_token,
                'order_sn_list' => implode(',', $order_chunk),
                'partner_id' => $partner_id,
                'shop_id' => $shop_id,
                'sign' => $sign,
                'timestamp' => $timest,
                'response_optional_fields' => 'order_status,package_list,shipping_carrier',
                'request_order_status_pending' => 'true',
            ]);

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);

            $response_raw = curl_exec($curl);
            curl_close($curl);

            if (!$response_raw) {
                continue;
            }

            $response = json_decode($response_raw, true);
            $order_list = $response['response']['order_list'] ?? [];
            if (empty($order_list) || !is_array($order_list)) {
                continue;
            }

            foreach ($order_list as $order_detail) {
                $order_sn = (string) ($order_detail['order_sn'] ?? '');
                if ($order_sn === '' || !isset($orders_by_sn[$order_sn])) {
                    continue;
                }

                $existing_row = $orders_by_sn[$order_sn];
                $new_status = (string) ($order_detail['order_status'] ?? $existing_row['order_status']);
                $new_package_id = (string) ($existing_row['package_id'] ?? '');

                if (!empty($order_detail['package_list']) && is_array($order_detail['package_list'])) {
                    $first_package = $order_detail['package_list'][0] ?? [];
                    $new_package_id = (string) (
                        $first_package['package_number']
                        ?? $first_package['package_id']
                        ?? $new_package_id
                    );
                }

                $update_data = [
                    'order_status' => $new_status,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if ($new_package_id !== '') {
                    $update_data['package_id'] = $new_package_id;
                }

                if (!empty($order_detail['shipping_carrier'])) {
                    $update_data['shipping'] = (string) $order_detail['shipping_carrier'];
                }

                $this->db->where('id', $existing_row['id']);
                $this->db->update('transaction', $update_data);
            }
        }
    }
    
    public function shopee_get_booking_shipping_parameter()
    {
        header('Content-Type: application/json');

        $booking_id = $_POST['booking_id'] ?? '';
        if (empty($booking_id)) {
            echo json_encode(['status' => false, 'message' => 'booking_id wajib']);
            return;
        }

        $booking_sn = $this->mymodel->selectDataOne('booking_fbs_orders', ['id' => $booking_id])['booking_sn'];

        $shop_id = $_POST['shop_id'] ?? '';
        if (!$shop_id) {
            echo json_encode(['status' => false, 'message' => 'shop_id wajib']);
            return;
        }

        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }

        $config = json_decode($config_row['val'], true);
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $access_token = $config['access_token'];
        $host = 'https://partner.shopeemobile.com';

        $path = "/api/v2/logistics/get_booking_shipping_parameter";
        $timest = time();

        $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
        $sign = hash_hmac('sha256', $baseString, $partner_key);

        $url = $host . $path .
            '?partner_id=' . $partner_id .
            '&timestamp=' . $timest .
            '&access_token=' . $access_token .
            '&shop_id=' . $shop_id .
            '&sign=' . $sign .
            '&booking_sn=' . $booking_sn;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($curl);
        $curl_error = curl_error($curl);
        curl_close($curl);

        if ($curl_error) {
            echo json_encode([
                'status' => false,
                'message' => 'CURL Error: ' . $curl_error
            ]);
            return;
        }

        $shipping_params = json_decode($response, true);
        
        if (isset($shipping_params['error']) && !empty($shipping_params['error'])) {
            echo json_encode([
                'status' => false,
                'message' => 'API Error: ' . ($shipping_params['message'] ?? 'Unknown error')
            ]);
            return;
        }

        if (!isset($shipping_params['response'])) {
            echo json_encode([
                'status' => false,
                'message' => 'Data result tidak ditemukan'
            ]);
            return;
        }

        $formatted_response = [
            'status' => true,
            'booking_sn' => $booking_sn,
            'total_packages' => 1,
            'pickup_info' => [
                'address_list' => [],
                'time_slot_list' => []
            ]
        ];

        if (isset($shipping_params['response']['pickup']['address_list'])) {
            foreach ($shipping_params['response']['pickup']['address_list'] as $address) {
                $formatted_response['pickup_info']['address_list'][] = [
                    'address_id' => $address['address_id'] ?? null,
                    'address' => $address['address'] ?? 'Tidak ada alamat',
                ];
            }
        }

        if (isset($shipping_params['response']['pickup']['address_list'][0]['time_slot_list'])) {
            foreach ($shipping_params['response']['pickup']['address_list'][0]['time_slot_list'] as $time_slot) {
                $formatted_response['pickup_info']['time_slot_list'][] = [
                    'date' => $time_slot['date'] ?? 'Tidak ada waktu pengiriman',
                    'pickup_time_id' => $time_slot['pickup_time_id'] ?? '',
                    'time_text' => $time_slot['time_text'] ?? '',
                ];
            }
        }

        echo json_encode($formatted_response);
    }



    public function shopee_mass_ship_order_bulk()
    {
        header('Content-Type: application/json');
    
        $transaction_ids_input = $_POST['transaction_ids'] ?? [];
        if (is_string($transaction_ids_input)) {
            $transaction_ids = array_filter(array_map('trim', explode(',', $transaction_ids_input)));
        } else {
            $transaction_ids = (array) $transaction_ids_input;
        }
    
        if (empty($transaction_ids)) {
            echo json_encode(['status' => false, 'message' => 'transaction_ids wajib (array atau CSV)']);
            return;
        }
    
        $shop_id = $_POST['shop_id'] ?? '';
        if (!$shop_id) {
            echo json_encode(['status' => false, 'message' => 'shop_id wajib']);
            return;
        }
    
        $pickup_address_id = $_POST['pickup_address_id'] ?? '';
        $pickup_time_id = $_POST['pickup_time_id'] ?? '';
    
        if (!$pickup_address_id || !$pickup_time_id) {
            echo json_encode(['status' => false, 'message' => 'pickup_address_id dan pickup_time_id wajib']);
            return;
        }
    
        // 1. AMBIL SEMUA DATA SEKALIGUS DENGAN SINGLE QUERY
        $placeholders = implode(',', array_fill(0, count($transaction_ids), '?'));
        $sql = "SELECT order_id, package_id, shipping 
                FROM transaction 
                WHERE order_id IN ($placeholders) AND package_id IS NOT NULL AND package_id != ''";
        
        $transactions = $this->db->query($sql, $transaction_ids)->result_array();
        
        // Buat mapping package_number ke transaction data
        $transaction_map = [];
        $package_list = [];
        
        foreach ($transactions as $tx) {
            $package_number = $tx['package_id'];
            $transaction_map[$package_number] = [
                'order_id' => $tx['order_id'],
                'shipping_method' => $tx['shipping'] ?? 'unknown'
            ];
            $package_list[] = ['package_number' => $package_number];
        }
    
        if (empty($package_list)) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada package_number yang valid']);
            return;
        }
    
        // 2. AMBIL CONFIG TOKO
        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }
    
        $config = json_decode($config_row['val'], true);
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $access_token = $config['access_token'];
        $host = 'https://partner.shopeemobile.com';
        
        // 3. SETUP BATCH PROCESSING DENGAN MULTI CURL
        $batchSizeInput = isset($_POST['batch_size']) ? (int)$_POST['batch_size'] : 10;
        $batch_size = max(1, min(50, $batchSizeInput));
        $batches = array_chunk($package_list, $batch_size);
    
        $all_results = [];
        $successful_orders = [];
        $multi_curl = curl_multi_init();
        $curl_handlers = [];
        $batch_responses = [];
    
        // 4. PREPARE SEMUA REQUEST SECARA PARALEL
        foreach ($batches as $batch_index => $batch_packages) {
            $path_ship = "/api/v2/logistics/mass_ship_order";
            $timest_ship = time() + $batch_index; // Sedikit offset untuk avoid timestamp sama
            
            $baseStringShip = sprintf("%s%s%s%s%s", $partner_id, $path_ship, $timest_ship, $access_token, $shop_id);
            $sign_ship = hash_hmac('sha256', $baseStringShip, $partner_key);
    
            $request_data = [
                'package_list' => $batch_packages,
                'pickup' => [
                    'address_id'     => (int) $pickup_address_id,
                    'pickup_time_id' => $pickup_time_id
                ]
            ];
    
            $url_ship = $host . $path_ship . 
                    '?partner_id=' . $partner_id . 
                    '&timestamp=' . $timest_ship . 
                    '&access_token=' . $access_token . 
                    '&shop_id=' . $shop_id . 
                    '&sign=' . $sign_ship;
    
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url_ship,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($request_data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                ],
            ]);
    
            $curl_handlers[$batch_index] = $curl;
            $batch_responses[$batch_index] = [
                'batch_packages' => $batch_packages,
                'batch_index' => $batch_index
            ];
            
            curl_multi_add_handle($multi_curl, $curl);
        }
    
        // 5. EKSEKUSI SEMUA REQUEST SECARA PARALEL
        $running = null;
        do {
            $status = curl_multi_exec($multi_curl, $running);
            if ($running) {
                curl_multi_select($multi_curl);
            }
        } while ($running > 0);
    
        // 6. PROCESS SEMUA RESPONSE
        foreach ($curl_handlers as $batch_index => $curl) {
            $batch_data = $batch_responses[$batch_index];
            $batch_packages = $batch_data['batch_packages'];
            
            $response_ship = curl_multi_getcontent($curl);
            $curl_error_ship = curl_error($curl);
            
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            if ($curl_error_ship || $http_code !== 200) {
                // Jika CURL error atau HTTP error, catat semua package dalam batch ini sebagai gagal
                foreach ($batch_packages as $package) {
                    $package_number = $package['package_number'];
                    $tx_data = $transaction_map[$package_number] ?? null;
                    
                    $all_results[] = [
                        'transaction_id' => $tx_data['order_id'] ?? null,
                        'package_number' => $package_number,
                        'shipping_method' => $tx_data['shipping_method'] ?? 'unknown',
                        'batch_index' => $batch_index,
                        'status' => false,
                        'message' => $curl_error_ship ?: "HTTP Error: $http_code"
                    ];
                }
                continue;
            }
    
            $ship_result = json_decode($response_ship, true);
            
            if (isset($ship_result['error']) && !empty($ship_result['error'])) {
                foreach ($batch_packages as $package) {
                    $package_number = $package['package_number'];
                    $tx_data = $transaction_map[$package_number] ?? null;
                    
                    $all_results[] = [
                        'transaction_id' => $tx_data['order_id'] ?? null,
                        'package_number' => $package_number,
                        'shipping_method' => $tx_data['shipping_method'] ?? 'unknown',
                        'batch_index' => $batch_index,
                        'status' => false,
                        'message' => 'Ship API Error: ' . ($ship_result['message'] ?? 'Unknown error')
                    ];
                }
                continue;
            }
    
            if (!isset($ship_result['response']['success_list'])) {
                foreach ($batch_packages as $package) {
                    $package_number = $package['package_number'];
                    $tx_data = $transaction_map[$package_number] ?? null;
                    
                    $all_results[] = [
                        'transaction_id' => $tx_data['order_id'] ?? null,
                        'package_number' => $package_number,
                        'shipping_method' => $tx_data['shipping_method'] ?? 'unknown',
                        'batch_index' => $batch_index,
                        'status' => false,
                        'message' => 'Data result ship tidak ditemukan'
                    ];
                }
                continue;
            }
    
            // Process successful and failed orders dari ship result
            foreach ($ship_result['response']['success_list'] as $result) {
                $package_number = $result['package_number'];
                $tx_data = $transaction_map[$package_number] ?? null;
                
                if (isset($result['error'])) {
                    $all_results[] = [
                        'transaction_id' => $tx_data['order_id'] ?? null,
                        'package_number' => $package_number,
                        'shipping_method' => $tx_data['shipping_method'] ?? 'unknown',
                        'batch_index' => $batch_index,
                        'status' => false,
                        'message' => $result['error_description'] ?? $result['error'],
                        'raw_error' => $result
                    ];
                } else {
                    $all_results[] = [
                        'transaction_id' => $tx_data['order_id'] ?? null,
                        'package_number' => $package_number,
                        'shipping_method' => $tx_data['shipping_method'] ?? 'unknown',
                        'batch_index' => $batch_index,
                        'status' => true,
                        'message' => 'Mass Ship Order sukses',
                        'pickup_address_id' => $pickup_address_id,
                        'pickup_time_id' => $pickup_time_id,
                        'raw_success' => $result
                    ];
    
                    // Tambahkan ke array successful_orders untuk update database
                    if ($tx_data['order_id'] ?? null) {
                        $successful_orders[] = $tx_data['order_id'];
                    }
                }
            }
    
            curl_multi_remove_handle($multi_curl, $curl);
            curl_close($curl);
        }
    
        curl_multi_close($multi_curl);
    
        // 7. BATCH UPDATE DATABASE UNTUK SEMUA ORDER YANG BERHASIL
        $rts_updated_count = 0;
        if (!empty($successful_orders)) {
            $successful_orders = array_unique($successful_orders);
            $placeholders = implode(',', array_fill(0, count($successful_orders), '?'));
            
            $update_sql = "UPDATE transaction SET rts_at = ?, order_status = ? WHERE order_id IN ($placeholders)";
            $params = array_merge([date('Y-m-d H:i:s'), 'processed'], $successful_orders);
            
            $update_result = $this->db->query($update_sql, $params);
            $rts_updated_count = $this->db->affected_rows();
        }
    
        // 8. FINAL SUMMARY
        $success_count = 0;
        $failed_count = 0;
        
        foreach ($all_results as $result) {
            if ($result['status'] === true) {
                $success_count++;
            } else {
                $failed_count++;
            }
        }
    
        // 9. HANDLE MISSING TRANSACTIONS
        $processed_order_ids = array_column($all_results, 'transaction_id');
        $missing_transactions = array_diff($transaction_ids, $processed_order_ids);
        
        foreach ($missing_transactions as $missing_tx_id) {
            $all_results[] = [
                'transaction_id' => $missing_tx_id,
                'package_number' => null,
                'shipping_method' => 'unknown',
                'batch_index' => null,
                'status' => false,
                'message' => 'Transaksi tidak ditemukan atau package_number kosong'
            ];
            $failed_count++;
        }
    
        echo json_encode([
            'status' => true, 
            'summary' => [
                'total_orders' => count($transaction_ids),
                'valid_packages' => count($package_list),
                'batches_processed' => count($batches),
                'success' => $success_count,
                'failed' => $failed_count,
                'rts_updated' => $rts_updated_count
            ],
            'pickup_parameters_used' => [
                'pickup_address_id' => $pickup_address_id,
                'pickup_time_id' => $pickup_time_id
            ],
            'results' => $all_results
        ]);
    }
    
    public function shopee_ship_booking_bulk()
    {
        header('Content-Type: application/json');
    
        $booking_ids_input = $_POST['booking_sns'] ?? [];
        $bookingSns = [];
        if (is_string($booking_ids_input)) {
            $bookingSns = array_filter(array_map('trim', explode(',', $booking_ids_input)));
        } elseif (is_array($booking_ids_input)) {
            $bookingSns = array_filter(array_map('trim', $booking_ids_input));
        }
    
        if (empty($bookingSns)) {
            echo json_encode(['status' => false, 'message' => 'booking_sns wajib']);
            return;
        }
    
        $shop_id = $_POST['shop_id'] ?? '';
        if (!$shop_id) {
            echo json_encode(['status' => false, 'message' => 'shop_id wajib']);
            return;
        }
    
        $pickup_address_id = $_POST['pickup_address_id'] ?? '';
        $pickup_time_id = $_POST['pickup_time_id'] ?? '';
    
        if (!$pickup_address_id || !$pickup_time_id) {
            echo json_encode(['status' => false, 'message' => 'pickup_address_id dan pickup_time_id wajib']);
            return;
        }
    
        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }
    
        $config = json_decode($config_row['val'], true);
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $access_token = $config['access_token'];
        $host = 'https://partner.shopeemobile.com';
        $path = "/api/v2/logistics/ship_booking";
    
        // 1. SETUP BATCH PROCESSING DENGAN CHUNK 50 DATA
        $batch_size = 50;
        $booking_batches = array_chunk($bookingSns, $batch_size);
    
        $all_results = [];
        $successful_bookings = [];
        $all_successful_bookings = [];
        $multi_curl = curl_multi_init();
        $curl_handlers = [];
        $batch_responses = [];
    
        // 2. PREPARE SEMUA REQUEST SECARA PARALEL PER BATCH
        foreach ($booking_batches as $batch_index => $batch_bookings) {
            $batch_results = [];
            
            foreach ($batch_bookings as $bookingSn) {
                $bookingSn = trim((string) $bookingSn);
                if ($bookingSn === '') {
                    continue;
                }
    
                $payload = [
                    'booking_sn' => $bookingSn,
                    'pickup' => [
                        'address_id' => (int) $pickup_address_id,
                        'pickup_time_id' => $pickup_time_id
                    ]
                ];
    
                $timest = time() + $batch_index; 
                $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                $sign = hash_hmac('sha256', $baseString, $partner_key);
    
                $url = $host . $path .
                    '?partner_id=' . $partner_id .
                    '&timestamp=' . $timest .
                    '&access_token=' . $access_token .
                    '&shop_id=' . $shop_id .
                    '&sign=' . $sign;
    
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                    ],
                ]);
    
                $curl_handlers[$bookingSn] = $curl;
                $batch_responses[$bookingSn] = [
                    'batch_index' => $batch_index,
                    'booking_sn' => $bookingSn
                ];
                
                curl_multi_add_handle($multi_curl, $curl);
            }
    
            if (empty($curl_handlers)) {
                continue;
            }
    
            // 3. EKSEKUSI REQUEST UNTUK BATCH INI
            $running = null;
            do {
                $status = curl_multi_exec($multi_curl, $running);
                if ($running) {
                    curl_multi_select($multi_curl);
                }
            } while ($running > 0);
    
            // 4. PROCESS RESPONSE UNTUK BATCH INI
            $batch_success_count = 0;
            $batch_failed_count = 0;
    
            foreach ($curl_handlers as $bookingSn => $curl) {
                $batch_data = $batch_responses[$bookingSn];
                $batch_index = $batch_data['batch_index'];
    
                $response = curl_multi_getcontent($curl);
                $curl_error = curl_error($curl);
                
                if ($curl_error) {
                    $all_results[] = [
                        'booking_sn' => $bookingSn,
                        'status' => false,
                        'message' => 'CURL Error: ' . $curl_error,
                        'raw' => null,
                        'batch_index' => $batch_index
                    ];
                    $batch_failed_count++;
                    continue;
                }
    
                $shipResult = json_decode($response, true);
                
                if (isset($shipResult['error']) && $shipResult['error'] === '' && 
                    isset($shipResult['message']) && $shipResult['message'] === '') {
                    $all_results[] = [
                        'booking_sn' => $bookingSn,
                        'status' => true,
                        'message' => 'Berhasil memproses booking',
                        'request_id' => $shipResult['request_id'] ?? '',
                        'raw' => $shipResult,
                        'batch_index' => $batch_index
                    ];
                    $batch_success_count++;
                    $successful_bookings[] = $bookingSn;
                    $all_successful_bookings[] = $bookingSn;
                    
                } elseif (isset($shipResult['error']) && $shipResult['error'] !== '') {
                    $all_results[] = [
                        'booking_sn' => $bookingSn,
                        'status' => false,
                        'message' => 'API Error: ' . ($shipResult['message'] ?? $shipResult['error']),
                        'request_id' => $shipResult['request_id'] ?? '',
                        'raw' => $shipResult,
                        'batch_index' => $batch_index
                    ];
                    $batch_failed_count++;
                } else {
                    $all_results[] = [
                        'booking_sn' => $bookingSn,
                        'status' => false,
                        'message' => 'Response tidak valid dari API',
                        'request_id' => $shipResult['request_id'] ?? '',
                        'raw' => $shipResult,
                        'batch_index' => $batch_index
                    ];
                    $batch_failed_count++;
                }
    
                curl_multi_remove_handle($multi_curl, $curl);
                curl_close($curl);
            }
    
            // Reset curl handlers untuk batch berikutnya
            $curl_handlers = [];
            $batch_responses = [];
    
            // 5. UPDATE DATABASE UNTUK BATCH YANG BERHASIL
            if (!empty($successful_bookings)) {
                $updateData = [
                    'booking_status' => 'PROCESSED',
                    'rts_at' => date('Y-m-d H:i:s')
                ];

                $this->db->where_in('booking_sn', $successful_bookings)
                         ->where('shop_id', $shop_id)
                         ->update('booking_fbs_orders', $updateData);
                
                // Reset untuk batch berikutnya
                $successful_bookings = [];
            }
    
            if (count($booking_batches) > 1 && $batch_index < count($booking_batches) - 1) {
                usleep(100000); 
            }
        }
    
        curl_multi_close($multi_curl);
    
        // 7. FINAL SUMMARY
        $successCount = 0;
        $failedCount = 0;
        
        foreach ($all_results as $result) {
            if ($result['status'] === true) {
                $successCount++;
            } else {
                $failedCount++;
            }
        }
    
        // 8. HANDLE MISSING BOOKING SNS
        $processed_booking_sns = array_column($all_results, 'booking_sn');
        $missing_bookings = array_diff($bookingSns, $processed_booking_sns);
        
        foreach ($missing_bookings as $missing_booking) {
            $all_results[] = [
                'booking_sn' => $missing_booking,
                'status' => false,
                'message' => 'Booking SN tidak diproses',
                'raw' => null,
                'batch_index' => null
            ];
            $failedCount++;
        }
    
        // 9. TRIGGER BOOKING REFRESH
        $booking_check_url = $this->template->endpoint_url() . "/api/booking/shopee/refresh";
        $ch_check = curl_init();
        curl_setopt_array($ch_check, [
            CURLOPT_URL => $booking_check_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);
        curl_exec($ch_check);
        curl_close($ch_check);

        // 10. CREATE BOOKING SHIPPING DOCUMENT
        $booking_document_create = [
            'processed_batches' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'details' => [],
            'message' => ''
        ];

        if (!empty($all_successful_bookings)) {
            $all_successful_bookings = array_values(array_unique($all_successful_bookings));

            $this->db->select('booking_sn, shipping_carrier, tracking_number');
            $this->db->where_in('booking_sn', $all_successful_bookings);
            $this->db->where('shop_id', $shop_id);
            $booking_data = $this->mymodel->selectData('booking_fbs_orders', []);

            $shipping_groups_doc = [];
            foreach ($booking_data as $booking_row) {
                $booking_sn = $booking_row['booking_sn'] ?? '';
                if ($booking_sn === '') {
                    continue;
                }
                $carrier = $booking_row['shipping_carrier'] ?? 'unknown';
                if (!isset($shipping_groups_doc[$carrier])) {
                    $shipping_groups_doc[$carrier] = [];
                }
                $shipping_groups_doc[$carrier][] = [
                    'booking_sn' => $booking_sn,
                    'tracking_number' => $booking_row['tracking_number'] ?? ''
                ];
            }

            $curl_default_options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ];

            foreach ($shipping_groups_doc as $shipping_method => $booking_list) {
                $booking_batches = array_chunk($booking_list, 50);

                foreach ($booking_batches as $batch_index => $currentBookings) {
                    $booking_document_create['processed_batches']++;

                    $path_create = "/api/v2/logistics/create_booking_shipping_document";
                    $timest_create = time() + $batch_index;

                    $baseStringCreate = sprintf("%s%s%s%s%s", $partner_id, $path_create, $timest_create, $access_token, $shop_id);
                    $sign_create = hash_hmac('sha256', $baseStringCreate, $partner_key);

                    $url_create = $host . $path_create .
                        '?partner_id=' . $partner_id .
                        '&timestamp=' . $timest_create .
                        '&access_token=' . $access_token .
                        '&shop_id=' . $shop_id .
                        '&sign=' . $sign_create;

                    $create_payload = [
                        'booking_list' => array_map(function($booking) {
                            return [
                                'booking_sn' => $booking['booking_sn'],
                                'tracking_number' => $booking['tracking_number']
                            ];
                        }, $currentBookings)
                    ];

                    $curl_create = curl_init();
                    curl_setopt_array($curl_create, $curl_default_options + [
                        CURLOPT_URL => $url_create,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => json_encode($create_payload),
                    ]);

                    $response_create = curl_exec($curl_create);
                    $curl_error_create = curl_error($curl_create);
                    $http_code_create = curl_getinfo($curl_create, CURLINFO_HTTP_CODE);
                    curl_close($curl_create);

                    if ($curl_error_create || $http_code_create !== 200) {
                        foreach ($currentBookings as $booking) {
                            $booking_document_create['details'][] = [
                                'booking_sn' => $booking['booking_sn'],
                                'shipping_method' => $shipping_method,
                                'batch_index' => $batch_index,
                                'status' => 'failed',
                                'message' => $curl_error_create ?: "HTTP Error: $http_code_create"
                            ];
                            $booking_document_create['failed_count']++;
                        }
                        continue;
                    }

                    $create_result = json_decode($response_create, true);

                    if (isset($create_result['error']) && !empty($create_result['error'])) {
                        foreach ($currentBookings as $booking) {
                            $booking_document_create['details'][] = [
                                'booking_sn' => $booking['booking_sn'],
                                'shipping_method' => $shipping_method,
                                'batch_index' => $batch_index,
                                'status' => 'failed',
                                'message' => 'API Error: ' . ($create_result['message'] ?? 'Unknown error')
                            ];
                            $booking_document_create['failed_count']++;
                        }
                        continue;
                    }

                    foreach ($currentBookings as $booking) {
                        $booking_document_create['details'][] = [
                            'booking_sn' => $booking['booking_sn'],
                            'shipping_method' => $shipping_method,
                            'batch_index' => $batch_index,
                            'status' => 'success',
                            'message' => 'Create booking shipping document queued'
                        ];
                        $booking_document_create['success_count']++;
                    }
                }
            }

            if (empty($booking_document_create['details'])) {
                $booking_document_create['message'] = 'Data booking untuk dokumen tidak ditemukan';
            }
        } else {
            $booking_document_create['message'] = 'Tidak ada booking sukses untuk dibuatkan dokumen';
        }
    
        $status = $successCount > 0;
    
        echo json_encode([
            'status' => $status,
            'summary' => [
                'total_bookings' => count($bookingSns),
                'batches_processed' => count($booking_batches),
                'success' => $successCount,
                'failed' => $failedCount
            ],
            'results' => $all_results,
            'booking_document_create' => $booking_document_create
        ]);
    }
    
    public function shopee_get_shipping_document_bulk()
    {
        header('Content-Type: application/json');
    
        $transaction_ids_input = $_POST['transaction_ids'] ?? [];
        if (is_string($transaction_ids_input)) {
            $transaction_ids = array_filter(array_map('trim', explode(',', $transaction_ids_input)));
        } else {
            $transaction_ids = (array) $transaction_ids_input;
        }
    
        if (empty($transaction_ids)) {
            echo json_encode(['status' => false, 'message' => 'transaction_ids wajib (array atau CSV)']);
            return;
        }
    
        $shop_id = $_POST['shop_id'] ?? '';
        if (!$shop_id) {
            echo json_encode(['status' => false, 'message' => 'shop_id wajib']);
            return;
        }
    
        // 1. AMBIL CONFIG TOKO
        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }
    
        $config = json_decode($config_row['val'], true);
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $access_token = $config['access_token'];
        $host = 'https://partner.shopeemobile.com';

        // 2. AMBIL SEMUA DATA TRANSAKSI SEKALIGUS
        $placeholders = implode(',', array_fill(0, count($transaction_ids), '?'));
        $sql = "SELECT order_id, package_id, awb_number, shipping 
                FROM transaction 
                WHERE order_id IN ($placeholders)
                  AND shop_id = ?
                  AND marketplace = 'SHOPEE'
                  AND package_id IS NOT NULL
                  AND package_id != ''";

        $transaction_query_params = $transaction_ids;
        $transaction_query_params[] = $shop_id;
        $transaction_data = $this->db->query($sql, $transaction_query_params)->result_array();
    
        // 3. KELOMPOKKAN DATA DAN BUAT MAPPING
        $shipping_groups = [];
        $transaction_map = [];
        $all_orders = [];
    
        foreach ($transaction_data as $tx_row) {
            $order_sn = $tx_row['order_id'];
            $package_number = $tx_row['package_id'];
            $tracking_number = $tx_row['awb_number'] ?? '';
            $shipping_method = $tx_row['shipping'] ?? 'unknown';
            
            $order_key = $order_sn . '_' . $package_number;
            
            if (!isset($shipping_groups[$shipping_method])) {
                $shipping_groups[$shipping_method] = [];
            }
            
            $order_data = [
                'order_sn' => $order_sn,
                'package_number' => $package_number,
                'tracking_number' => $tracking_number
            ];
            
            $shipping_groups[$shipping_method][] = $order_data;
            $transaction_map[$order_key] = [
                'order_id' => $order_sn,
                'shipping_method' => $shipping_method
            ];
            $all_orders[] = $order_data;
        }
    
        if (empty($shipping_groups)) {
            $missing_package_orders = array_values(array_diff($transaction_ids, array_column($transaction_data, 'order_id')));
            echo json_encode([
                'status' => false,
                'message' => 'Tidak ada order yang siap dibuatkan dokumen',
                'summary' => [
                    'total_orders' => count($transaction_ids),
                    'valid_orders' => 0,
                    'shipping_groups' => 0,
                    'success_download' => 0,
                    'failed' => count($transaction_ids),
                    'missing_package' => count($missing_package_orders),
                    'print_updated' => 0,
                ],
                'results' => array_map(function ($order_id) {
                    return [
                        'transaction_id' => $order_id,
                        'order_sn' => $order_id,
                        'package_number' => null,
                        'shipping_method' => 'unknown',
                        'status' => 'failed',
                        'message' => 'Order belum memiliki package_number/package_id terbaru'
                    ];
                }, $missing_package_orders),
            ]);
            return;
        }
    
        // 4. SETUP MULTI CURL UNTUK PARALEL PROCESSING
        $all_results = [];
        $successful_orders = [];
        $multi_curl = curl_multi_init();
        $curl_handlers = [];
        $request_data_map = [];
    
        // 5. PREPARE SEMUA REQUEST CREATE SHIPPING DOCUMENT SECARA PARALEL
        $batch_size = isset($_POST['batch_size']) ? (int) $_POST['batch_size'] : 50;
        $batch_size = max(1, min(50, $batch_size));

        foreach ($shipping_groups as $shipping_method => $orders) {
            $order_batches = array_chunk($orders, $batch_size);

            foreach ($order_batches as $batch_index => $current_orders) {
                $path_create = "/api/v2/logistics/create_shipping_document";
                $timest_create = time() + $batch_index;

                $baseStringCreate = sprintf("%s%s%s%s%s", $partner_id, $path_create, $timest_create, $access_token, $shop_id);
                $sign_create = hash_hmac('sha256', $baseStringCreate, $partner_key);

                $url_create = $host . $path_create .
                    '?partner_id=' . $partner_id .
                    '&timestamp=' . $timest_create .
                    '&access_token=' . $access_token .
                    '&shop_id=' . $shop_id .
                    '&sign=' . $sign_create;

                $create_payload = [
                    'order_list' => array_map(function($order) {
                        $payload = [
                            'order_sn' => $order['order_sn'],
                            'package_number' => $order['package_number'],
                        ];
                        if (!empty($order['tracking_number'])) {
                            $payload['tracking_number'] = $order['tracking_number'];
                        }
                        return $payload;
                    }, $current_orders)
                ];

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url_create,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode($create_payload),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                ]);

                $request_key = sprintf('create_%s_%d', md5($shipping_method), $batch_index);
                $curl_handlers[$request_key] = $curl;
                $request_data_map[$request_key] = [
                    'type' => 'create',
                    'shipping_method' => $shipping_method,
                    'batch_index' => $batch_index,
                    'orders' => $current_orders,
                ];

                curl_multi_add_handle($multi_curl, $curl);
            }
        }
    
        // 6. EKSEKUSI CREATE REQUESTS
        $running = null;
        do {
            $status = curl_multi_exec($multi_curl, $running);
            if ($running) {
                curl_multi_select($multi_curl);
            }
        } while ($running > 0);
    
        // 7. PROCESS CREATE RESPONSES DAN PREPARE GET REQUESTS
        $ready_for_get = [];
        
        foreach ($curl_handlers as $request_key => $curl) {
            $request_data = $request_data_map[$request_key];
            $shipping_method = $request_data['shipping_method'];
            $batch_index = (int) ($request_data['batch_index'] ?? 0);
            $orders = $request_data['orders'];
            
            $response = curl_multi_getcontent($curl);
            $curl_error = curl_error($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            curl_multi_remove_handle($multi_curl, $curl);
            curl_close($curl);
    
            if ($curl_error || $http_code !== 200) {
                foreach ($orders as $order) {
                    $order_key = $order['order_sn'] . '_' . $order['package_number'];
                    $tx_data = $transaction_map[$order_key] ?? null;
    
                    $all_results[] = [
                        'transaction_id' => $tx_data['order_id'] ?? null,
                        'order_sn' => $order['order_sn'],
                        'package_number' => $order['package_number'],
                        'shipping_method' => $shipping_method,
                        'status' => 'create_failed',
                        'message' => $curl_error ?: "HTTP Error: $http_code"
                    ];
                }
                continue;
            }
    
            $create_result = json_decode($response, true);
            
            if (isset($create_result['error']) && !empty($create_result['error'])) {
                foreach ($orders as $order) {
                    $order_key = $order['order_sn'] . '_' . $order['package_number'];
                    $tx_data = $transaction_map[$order_key] ?? null;
    
                    $all_results[] = [
                        'transaction_id' => $tx_data['order_id'] ?? null,
                        'order_sn' => $order['order_sn'],
                        'package_number' => $order['package_number'],
                        'shipping_method' => $shipping_method,
                        'status' => 'create_failed',
                        'message' => 'API Error: ' . ($create_result['message'] ?? 'Unknown error')
                    ];
                }
                continue;
            }
    
            // Jika create berhasil, tambahkan ke ready_for_get
            $batch_key = $shipping_method . '|' . $batch_index;
            $ready_for_get[$batch_key] = [
                'shipping_method' => $shipping_method,
                'batch_index' => $batch_index,
                'orders' => $orders,
            ];
        }
    
        // 8. GET + DOWNLOAD DENGAN RETRY LOOP
        if (!empty($ready_for_get)) {
            $max_get_retries = 6;
            $get_retry_delay = 1000000; // 1 detik per retry
            $pending_for_get = $ready_for_get;
            $ready_for_download = [];

            for ($attempt = 0; $attempt < $max_get_retries && !empty($pending_for_get); $attempt++) {
                usleep($get_retry_delay);

                // 9. PREPARE + EKSEKUSI GET SHIPPING DOCUMENT REQUESTS
                $get_multi = curl_multi_init();
                $get_handles = [];
                $get_data_map = [];

                foreach ($pending_for_get as $batch_key => $batch_data) {
                    $shipping_method = $batch_data['shipping_method'];
                    $batch_index = (int) ($batch_data['batch_index'] ?? 0);
                    $orders = $batch_data['orders'];
                    $path_get = "/api/v2/logistics/get_shipping_document_result";
                    $timest_get = time() + $batch_index;

                    $baseStringGet = sprintf("%s%s%s%s%s", $partner_id, $path_get, $timest_get, $access_token, $shop_id);
                    $sign_get = hash_hmac('sha256', $baseStringGet, $partner_key);

                    $url_get = $host . $path_get .
                        '?partner_id=' . $partner_id .
                        '&timestamp=' . $timest_get .
                        '&access_token=' . $access_token .
                        '&shop_id=' . $shop_id .
                        '&sign=' . $sign_get;

                    $get_payload = [
                        'order_list' => array_map(function($order) {
                            return [
                                'order_sn' => $order['order_sn'],
                                'package_number' => $order['package_number']
                            ];
                        }, $orders)
                    ];

                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => $url_get,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => json_encode($get_payload),
                        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    ]);

                    $get_handles[$batch_key] = $curl;
                    $get_data_map[$batch_key] = [
                        'shipping_method' => $shipping_method,
                        'batch_index' => $batch_index,
                        'orders' => $orders,
                    ];
                    curl_multi_add_handle($get_multi, $curl);
                }

                $running = null;
                do {
                    curl_multi_exec($get_multi, $running);
                    if ($running) {
                        curl_multi_select($get_multi);
                    }
                } while ($running > 0);

                // 10. PROCESS GET RESPONSES
                $still_pending = [];

                foreach ($get_handles as $batch_key => $curl) {
                    $shipping_method = $get_data_map[$batch_key]['shipping_method'];
                    $batch_index = (int) ($get_data_map[$batch_key]['batch_index'] ?? 0);
                    $orders = $get_data_map[$batch_key]['orders'];
                    $response = curl_multi_getcontent($curl);
                    $curl_error = curl_error($curl);
                    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                    curl_multi_remove_handle($get_multi, $curl);
                    curl_close($curl);

                    if ($curl_error || $http_code !== 200) {
                        foreach ($orders as $order) {
                            $order_key = $order['order_sn'] . '_' . $order['package_number'];
                            $tx_data = $transaction_map[$order_key] ?? null;
                            $all_results[] = [
                                'transaction_id' => $tx_data['order_id'] ?? null,
                                'order_sn' => $order['order_sn'],
                                'package_number' => $order['package_number'],
                                'shipping_method' => $shipping_method,
                                'status' => 'get_failed',
                                'message' => $curl_error ?: "HTTP Error: $http_code"
                            ];
                        }
                        continue;
                    }

                    $document_result = json_decode($response, true);

                    if (isset($document_result['error']) && !empty($document_result['error'])) {
                        foreach ($orders as $order) {
                            $order_key = $order['order_sn'] . '_' . $order['package_number'];
                            $tx_data = $transaction_map[$order_key] ?? null;
                            $all_results[] = [
                                'transaction_id' => $tx_data['order_id'] ?? null,
                                'order_sn' => $order['order_sn'],
                                'package_number' => $order['package_number'],
                                'shipping_method' => $shipping_method,
                                'status' => 'get_failed',
                                'message' => 'API Error: ' . ($document_result['message'] ?? 'Unknown error')
                            ];
                        }
                        continue;
                    }

                    if (!isset($document_result['response']['result_list'])) {
                        foreach ($orders as $order) {
                            $order_key = $order['order_sn'] . '_' . $order['package_number'];
                            $tx_data = $transaction_map[$order_key] ?? null;
                            $all_results[] = [
                                'transaction_id' => $tx_data['order_id'] ?? null,
                                'order_sn' => $order['order_sn'],
                                'package_number' => $order['package_number'],
                                'shipping_method' => $shipping_method,
                                'status' => 'get_failed',
                                'message' => 'Data result tidak ditemukan'
                            ];
                        }
                        continue;
                    }

                    // Build order map untuk lookup cepat
                    $order_obj_map = [];
                    foreach ($orders as $order) {
                        $order_obj_map[$order['order_sn'] . '_' . $order['package_number']] = $order;
                    }

                    foreach ($document_result['response']['result_list'] as $result) {
                        $order_sn = $result['order_sn'];
                        $package_number = $result['package_number'];
                        $order_key = $order_sn . '_' . $package_number;
                        $tx_data = $transaction_map[$order_key] ?? null;
                        $result_status = $result['status'] ?? '';

                        if ($result_status === 'READY') {
                            // Siap untuk download
                            if (!isset($ready_for_download[$batch_key])) {
                                $ready_for_download[$batch_key] = [
                                    'shipping_method' => $shipping_method,
                                    'batch_index' => $batch_index,
                                    'orders' => [],
                                ];
                            }
                            $ready_for_download[$batch_key]['orders'][] = [
                                'order_sn' => $order_sn,
                                'package_number' => $package_number,
                                'tx_data' => $tx_data
                            ];
                        } elseif ($result_status === 'GENERATING') {
                            // Masih proses, jadwalkan retry
                            if (!isset($still_pending[$batch_key])) {
                                $still_pending[$batch_key] = [
                                    'shipping_method' => $shipping_method,
                                    'batch_index' => $batch_index,
                                    'orders' => [],
                                ];
                            }
                            $still_pending[$batch_key]['orders'][] = $order_obj_map[$order_key] ?? [
                                'order_sn' => $order_sn,
                                'package_number' => $package_number
                            ];
                            } else {
                                $fail_message = $result['fail_message'] ?? 'Shipping document not ready';
                                $all_results[] = [
                                    'transaction_id' => $tx_data['order_id'] ?? null,
                                    'order_sn' => $order_sn,
                                    'package_number' => $package_number,
                                    'shipping_method' => $shipping_method,
                                    'status' => 'failed',
                                    'message' => $fail_message
                                ];
                            }
                        }
                    }

                curl_multi_close($get_multi);
                $pending_for_get = $still_pending;
            } // end retry loop

            // Tandai sisa yang masih pending sebagai failed setelah semua retry habis
            foreach ($pending_for_get as $batch_key => $batch_data) {
                $shipping_method = $batch_data['shipping_method'];
                $orders = $batch_data['orders'];
                foreach ($orders as $order) {
                    $order_key = ($order['order_sn'] ?? '') . '_' . ($order['package_number'] ?? '');
                    $tx_data = $transaction_map[$order_key] ?? null;
                    $all_results[] = [
                        'transaction_id' => $tx_data['order_id'] ?? null,
                        'order_sn' => $order['order_sn'] ?? null,
                        'package_number' => $order['package_number'] ?? null,
                        'shipping_method' => $shipping_method,
                        'status' => 'failed',
                        'message' => 'Dokumen masih GENERATING setelah ' . $max_get_retries . ' kali percobaan'
                    ];
                }
            }

            // 11. PROCESS DOWNLOADS SECARA PARALEL
            if (!empty($ready_for_download)) {
                $dl_multi = curl_multi_init();
                $dl_handles = [];
                $dl_data_map = [];

                foreach ($ready_for_download as $batch_key => $batch_data) {
                    $shipping_method = $batch_data['shipping_method'];
                    $batch_index = (int) ($batch_data['batch_index'] ?? 0);
                    $ready_orders = $batch_data['orders'];
                    $path_download = "/api/v2/logistics/download_shipping_document";
                    $timest_download = time() + $batch_index;

                    $baseStringDownload = sprintf("%s%s%s%s%s", $partner_id, $path_download, $timest_download, $access_token, $shop_id);
                    $sign_download = hash_hmac('sha256', $baseStringDownload, $partner_key);

                    $url_download = $host . $path_download .
                        '?partner_id=' . $partner_id .
                        '&timestamp=' . $timest_download .
                        '&access_token=' . $access_token .
                        '&shop_id=' . $shop_id .
                        '&sign=' . $sign_download;

                    $download_payload = [
                        'order_list' => array_map(function($order) {
                            return [
                                'order_sn' => $order['order_sn'],
                                'package_number' => $order['package_number']
                            ];
                        }, $ready_orders)
                    ];

                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => $url_download,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => json_encode($download_payload),
                        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    ]);

                    $dl_handles[$batch_key] = $curl;
                    $dl_data_map[$batch_key] = [
                        'shipping_method' => $shipping_method,
                        'batch_index' => $batch_index,
                        'orders' => $ready_orders,
                    ];
                    curl_multi_add_handle($dl_multi, $curl);
                }

                // EKSEKUSI DOWNLOAD REQUESTS
                $running = null;
                do {
                    curl_multi_exec($dl_multi, $running);
                    if ($running) {
                        curl_multi_select($dl_multi);
                    }
                } while ($running > 0);

                // PROCESS DOWNLOAD RESPONSES
                foreach ($dl_handles as $batch_key => $curl) {
                    $shipping_method = $dl_data_map[$batch_key]['shipping_method'];
                    $batch_index = (int) ($dl_data_map[$batch_key]['batch_index'] ?? 0);
                    $orders = $dl_data_map[$batch_key]['orders'];
                    $response = curl_multi_getcontent($curl);
                    $curl_error = curl_error($curl);
                    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                    curl_multi_remove_handle($dl_multi, $curl);
                    curl_close($curl);

                    if ($curl_error) {
                        foreach ($orders as $order) {
                            $all_results[] = [
                                'transaction_id' => $order['tx_data']['order_id'] ?? null,
                                'order_sn' => $order['order_sn'],
                                'package_number' => $order['package_number'],
                                'shipping_method' => $shipping_method,
                                'status' => 'download_failed',
                                'message' => 'Download error: ' . $curl_error
                            ];
                        }
                        continue;
                    }

                    if (strpos($response, '%PDF') === 0 || $http_code === 200) {
                        $safe_shipping_method = preg_replace('/[^A-Za-z0-9]+/', '_', (string) $shipping_method);
                        $file_name = sprintf(
                            'shipping_documents_%s_b%02d_%s.pdf',
                            trim($safe_shipping_method, '_') ?: 'unknown',
                            $batch_index + 1,
                            date('Ymd_His')
                        );
                        $file_path = './assets/shipping_documents/' . $file_name;

                        if (!is_dir('./assets/shipping_documents/')) {
                            mkdir('./assets/shipping_documents/', 0777, true);
                        }

                        file_put_contents($file_path, $response);

                        // PDF tetap dikonversi ke gambar karena dipakai di alur print Shopee.
                        $allLabelImages = $this->convertShopeePdfToImages($file_path, pathinfo($file_name, PATHINFO_FILENAME), $shipping_method);

                        foreach ($orders as $index => $order) {
                            $assignedImages = [];
                            if (isset($allLabelImages[$index])) {
                                $assignedImages[] = $allLabelImages[$index];
                            } elseif (!empty($allLabelImages)) {
                                $assignedImages[] = $allLabelImages[0];
                            }

                            $all_results[] = [
                                'transaction_id' => $order['tx_data']['order_id'] ?? null,
                                'order_sn' => $order['order_sn'],
                                'package_number' => $order['package_number'],
                                'shipping_method' => $shipping_method,
                                'status' => 'success',
                                'message' => 'Shipping document downloaded successfully',
                                'file_path' => $file_path,
                                'file_name' => $file_name,
                                'download_group' => $shipping_method,
                                'label_images' => $assignedImages
                            ];

                            $successful_orders[] = $order['tx_data']['order_id'] ?? null;
                        }
                    } else {
                        $error_data = json_decode($response, true);
                        foreach ($orders as $order) {
                            $all_results[] = [
                                'transaction_id' => $order['tx_data']['order_id'] ?? null,
                                'order_sn' => $order['order_sn'],
                                'package_number' => $order['package_number'],
                                'shipping_method' => $shipping_method,
                                'status' => 'download_failed',
                                'message' => $error_data['message'] ?? 'Unknown download error'
                            ];
                        }
                    }
                }

                curl_multi_close($dl_multi);
            }
        }

        curl_multi_close($multi_curl);
    
        // 13. BATCH UPDATE DATABASE
        $print_updated_count = 0;
        if (!empty($successful_orders)) {
            $successful_orders = array_filter(array_unique($successful_orders));
            
            $data = array(
                'print_at' => date('Y-m-d H:i:s')
            );
            
            $this->db->where_in('order_id', $successful_orders);
            $this->db->update('transaction', $data);
            $print_updated_count = $this->db->affected_rows();
        }
    
        // 14. FINAL SUMMARY
        $success_count = count(array_filter($all_results, function($r) { return $r['status'] === 'success'; }));
        $failed_count = count(array_filter($all_results, function($r) { 
            return in_array($r['status'], ['create_failed', 'get_failed', 'failed', 'download_failed']); 
        }));
    
        // Handle missing transactions
        $processed_order_ids = array_filter(array_column($all_results, 'transaction_id'));
        $missing_transactions = array_diff($transaction_ids, $processed_order_ids);
        
        foreach ($missing_transactions as $missing_tx_id) {
            $all_results[] = [
                'transaction_id' => $missing_tx_id,
                'order_sn' => $missing_tx_id,
                'package_number' => null,
                'shipping_method' => 'unknown',
                'status' => 'failed',
                'message' => 'Order tidak ikut diproses karena package_number kosong atau gagal lolos tahap create/get/download'
            ];
            $failed_count++;
        }

        $missing_package_count = count(array_filter($all_results, function ($row) {
            return ($row['package_number'] ?? null) === null
                && ($row['status'] ?? '') === 'failed'
                && strpos((string) ($row['message'] ?? ''), 'package') !== false;
        }));

        $generating_timeout_count = count(array_filter($all_results, function ($row) {
            return ($row['status'] ?? '') === 'failed'
                && strpos((string) ($row['message'] ?? ''), 'GENERATING') !== false;
        }));
    
        echo json_encode([
            'status' => $success_count > 0,
            'summary' => [
                'total_orders' => count($transaction_ids),
                'valid_orders' => count($all_orders),
                'shipping_groups' => count($shipping_groups),
                'success_download' => $success_count,
                'failed' => $failed_count,
                'missing_package' => $missing_package_count,
                'generating_timeout' => $generating_timeout_count,
                'print_updated' => $print_updated_count
            ],
            'shipping_groups_detail' => array_keys($shipping_groups),
            'results' => $all_results
        ]);
    }
    
    public function shopee_get_booking_document_bulk()
    {
        header('Content-Type: application/json');
    
        $booking_sns_input = $_POST['booking_sns'] ?? [];
        if (is_string($booking_sns_input)) {
            $booking_sns = array_filter(array_map('trim', explode(',', $booking_sns_input)));
        } else {
            $booking_sns = (array) $booking_sns_input;
        }
    
        if (empty($booking_sns)) {
            echo json_encode(['status' => false, 'message' => 'booking_sns wajib (array atau CSV)']);
            return;
        }
    
        $shop_id = $_POST['shop_id'] ?? '';
        if (!$shop_id) {
            echo json_encode(['status' => false, 'message' => 'shop_id wajib']);
            return;
        }
    
        // --- ambil config toko ---
        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }
    
        $config = json_decode($config_row['val'], true);
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $access_token = $config['access_token'];
        $host = 'https://partner.shopeemobile.com';
    
        // --- Ambil semua data sekaligus dengan where_in ---
        $this->db->select('booking_sn, shipping_carrier, tracking_number');
        $this->db->where_in('booking_sn', $booking_sns);
        $booking_data = $this->mymodel->selectData('booking_fbs_orders', []);
    
        // --- Kelompokkan berdasarkan shipping method ---
        $shipping_groups = [];
        $booking_map = [];
    
        foreach ($booking_data as $booking_row) {
            if (empty($booking_row['booking_sn'])) {
                continue;
            }
            
            $booking_sn = $booking_row['booking_sn'];
            $tracking_number = $booking_row['tracking_number'] ?? '';
            $shipping_carrier = $booking_row['shipping_carrier'] ?? 'unknown';
            
            if (!isset($shipping_groups[$shipping_carrier])) {
                $shipping_groups[$shipping_carrier] = [];
            }
            
            $shipping_groups[$shipping_carrier][] = [
                'booking_sn' => $booking_sn,
                'tracking_number' => $tracking_number
            ];
            
            $booking_map[$booking_sn] = [
                'booking_sn' => $booking_row['booking_sn'],
                'shipping_carrier' => $shipping_carrier,
                'tracking_number' => $tracking_number
            ];
        }
    
        if (empty($shipping_groups)) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada booking yang memiliki booking_sn']);
            return;
        }
    
        $all_results = [];
        $successful_bookings = [];
        $total_batches = 0;
    
        // OPTIMASI: Kurangi timeout dan optimasi curl options
        $curl_default_options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 5,  // Reduced from 10
            CURLOPT_TIMEOUT => 15,   // Reduced from 30
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => false, // Optional: for faster SSL
            CURLOPT_SSL_VERIFYHOST => false, // Optional: for faster SSL
        ];
    
        // --- Process setiap kelompok shipping dengan OPTIMASI ---
        foreach ($shipping_groups as $shipping_method => $booking_list) {
            // Bagi booking_list menjadi batch maksimal 50
            $batch_size = 50;
            $booking_batches = array_chunk($booking_list, $batch_size);
    
            foreach ($booking_batches as $batch_index => $currentBookings) {
                $total_batches++;
                
                $path_get = "/api/v2/logistics/get_booking_shipping_document_result";
                $timest_get = time() + $batch_index;
    
                $baseStringGet = sprintf("%s%s%s%s%s", $partner_id, $path_get, $timest_get, $access_token, $shop_id);
                $sign_get = hash_hmac('sha256', $baseStringGet, $partner_key);
    
                $url_get = $host . $path_get .
                    '?partner_id=' . $partner_id .
                    '&timestamp=' . $timest_get .
                    '&access_token=' . $access_token .
                    '&shop_id=' . $shop_id .
                    '&sign=' . $sign_get;
    
                $get_payload = [
                    'booking_list' => array_map(function($booking) {
                        return ['booking_sn' => $booking['booking_sn']];
                    }, $currentBookings)
                ];
    
                $curl_get = curl_init();
                curl_setopt_array($curl_get, $curl_default_options + [
                    CURLOPT_URL => $url_get,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode($get_payload),
                ]);
    
                $response_get = curl_exec($curl_get);
                $curl_error_get = curl_error($curl_get);
                $http_code_get = curl_getinfo($curl_get, CURLINFO_HTTP_CODE);
                curl_close($curl_get);
    
                if ($curl_error_get || $http_code_get !== 200) {
                    foreach ($currentBookings as $booking) {
                        $all_results[] = [
                            'booking_sn' => $booking['booking_sn'],
                            'shipping_method' => $shipping_method,
                            'batch_index' => $batch_index,
                            'status' => 'get_failed',
                            'message' => $curl_error_get ?: "HTTP Error: $http_code_get"
                        ];
                    }
                    continue;
                }
    
                $document_result = json_decode($response_get, true);
    
                if (isset($document_result['error']) && !empty($document_result['error'])) {
                    foreach ($currentBookings as $booking) {
                        $all_results[] = [
                            'booking_sn' => $booking['booking_sn'],
                            'shipping_method' => $shipping_method,
                            'batch_index' => $batch_index,
                            'status' => 'get_failed',
                            'message' => 'API Error: ' . ($document_result['message'] ?? 'Unknown error')
                        ];
                    }
                    continue;
                }
    
                if (!isset($document_result['response']['result_list'])) {
                    foreach ($currentBookings as $booking) {
                        $all_results[] = [
                            'booking_sn' => $booking['booking_sn'],
                            'shipping_method' => $shipping_method,
                            'batch_index' => $batch_index,
                            'status' => 'get_failed',
                            'message' => 'Data result tidak ditemukan'
                        ];
                    }
                    continue;
                }
    
                // --- Process result list untuk kumpulkan yang READY ---
                $ready_bookings = [];
    
                foreach ($document_result['response']['result_list'] as $result) {
                    $booking_sn = $result['booking_sn'];
    
                    if (isset($result['status']) && $result['status'] === 'READY') {
                        $ready_bookings[] = ['booking_sn' => $booking_sn];
                    } else {
                        $fail_message = $result['fail_message'] ?? 'Booking shipping document not ready';
                        $all_results[] = [
                            'booking_sn' => $booking_sn,
                            'shipping_method' => $shipping_method,
                            'batch_index' => $batch_index,
                            'status' => 'failed',
                            'message' => $fail_message,
                        ];
                    }
                }
    
                // --- Step 3: Download Booking Shipping Document untuk yang READY ---
                if (!empty($ready_bookings)) {
                    $path_download = "/api/v2/logistics/download_booking_shipping_document";
                    $timest_download = time() + $batch_index;
    
                    $baseStringDownload = sprintf("%s%s%s%s%s", $partner_id, $path_download, $timest_download, $access_token, $shop_id);
                    $sign_download = hash_hmac('sha256', $baseStringDownload, $partner_key);
    
                    $url_download = $host . $path_download .
                        '?partner_id=' . $partner_id .
                        '&timestamp=' . $timest_download .
                        '&access_token=' . $access_token .
                        '&shop_id=' . $shop_id .
                        '&sign=' . $sign_download;
    
                    $download_payload = ['booking_list' => $ready_bookings];
    
                    $curl_download = curl_init();
                    curl_setopt_array($curl_download, $curl_default_options + [
                        CURLOPT_URL => $url_download,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => json_encode($download_payload),
                        CURLOPT_TIMEOUT => 20, // Slightly longer for downloads
                    ]);
    
                    $response_download = curl_exec($curl_download);
                    $curl_error_download = curl_error($curl_download);
                    $http_code = curl_getinfo($curl_download, CURLINFO_HTTP_CODE);
                    curl_close($curl_download);
    
                    if ($curl_error_download) {
                        foreach ($ready_bookings as $booking) {
                            $all_results[] = [
                                'booking_sn' => $booking['booking_sn'],
                                'shipping_method' => $shipping_method,
                                'batch_index' => $batch_index,
                                'status' => 'download_failed',
                                'message' => 'Download error: ' . $curl_error_download
                            ];
                        }
                    } else {
                        if (strpos($response_download, '%PDF') === 0 || $http_code === 200) {
                            $file_name = 'booking_shipping_documents_' . $shipping_method . '_batch_' . $batch_index . '_' . date('Ymd_His') . '.pdf';
                            $file_path = './assets/booking_shipping_documents/' . $file_name;
    
                            if (!is_dir('./assets/booking_shipping_documents/')) {
                                mkdir('./assets/booking_shipping_documents/', 0777, true);
                            }
    
                            // OPTIMASI: Async file writing (jika memungkinkan)
                            file_put_contents($file_path, $response_download);
    
                            // OPTIMASI: Convert PDF hanya jika benar-benar perlu
                            $allLabelImages = $this->convertShopeePdfToImages($file_path, pathinfo($file_name, PATHINFO_FILENAME));
                            
                            foreach ($ready_bookings as $index => $booking) {
                                $assignedImages = [];
                                if (isset($allLabelImages[$index])) {
                                    $assignedImages[] = $allLabelImages[$index];
                                } elseif (!empty($allLabelImages)) {
                                    $assignedImages[] = $allLabelImages[0];
                                }
    
                                $all_results[] = [
                                    'booking_sn' => $booking['booking_sn'],
                                    'shipping_method' => $shipping_method,
                                    'batch_index' => $batch_index,
                                    'status' => 'success',
                                    'message' => 'Booking shipping document downloaded successfully',
                                    'file_path' => $file_path,
                                    'file_name' => $file_name,
                                    'download_group' => $shipping_method,
                                    'label_images' => $assignedImages
                                ];
    
                                $successful_bookings[] = $booking['booking_sn'];
                            }
                        } else {
                            $error_data = json_decode($response_download, true);
                            foreach ($ready_bookings as $booking) {
                                $all_results[] = [
                                    'booking_sn' => $booking['booking_sn'],
                                    'shipping_method' => $shipping_method,
                                    'batch_index' => $batch_index,
                                    'status' => 'download_failed',
                                    'message' => $error_data['message'] ?? 'Unknown download error',
                                ];
                            }
                        }
                    }
                }
            }
        }
    
        // --- Update database ---
        $print_updated_count = 0;
        if (!empty($successful_bookings)) {
            $successful_bookings = array_filter(array_unique($successful_bookings));
            
            $update_data = ['print_at' => date('Y-m-d H:i:s')];
            
            $this->db->where_in('booking_sn', $successful_bookings);
            $update_result = $this->db->update('booking_fbs_orders', $update_data);
            
            if ($update_result) {
                $print_updated_count = $this->db->affected_rows();
            }
        }
    
        // --- Handle missing bookings ---
        $processed_booking_sns = array_column($all_results, 'booking_sn');
        $missing_bookings = array_diff($booking_sns, $processed_booking_sns);
        
        foreach ($missing_bookings as $missing_booking) {
            $all_results[] = [
                'booking_sn' => $missing_booking,
                'shipping_method' => 'unknown',
                'batch_index' => null,
                'status' => 'failed',
                'message' => 'Booking tidak ditemukan atau tidak diproses'
            ];
        }
    
        // Hitung summary
        $success_download = count(array_filter($all_results, function($r) { return $r['status'] === 'success'; }));
        $failed_count = count(array_filter($all_results, function($r) { 
            return in_array($r['status'], ['create_failed', 'get_failed', 'failed', 'download_failed']); 
        }));
    
        $valid_orders = count(array_filter($booking_sns, function($sn) use ($booking_map) {
            return isset($booking_map[$sn]);
        }));
    
        echo json_encode([
            'status' => true, 
            'summary' => [
                'total_orders' => count($booking_sns),
                'valid_orders' => $valid_orders,
                'shipping_groups' => count($shipping_groups),
                'batches_processed' => $total_batches,
                'success_download' => $success_download,
                'failed' => $failed_count,
                'print_updated' => $print_updated_count
            ],
            'shipping_groups_detail' => array_keys($shipping_groups),
            'results' => $all_results
        ]);
    }
    
    public function lazada_ship_order() 
    {
        header('Content-Type: application/json');

        $transaction_ids_input = $_POST['transaction_ids'] ?? [];
        if (is_string($transaction_ids_input)) {
            $transaction_ids = array_filter(array_map('trim', explode(',', $transaction_ids_input)));
        } else {
            $transaction_ids = array_filter(array_map('trim', (array) $transaction_ids_input));
        }

        if (empty($transaction_ids)) {
            echo json_encode(['status' => false, 'message' => 'transaction_ids wajib (array atau CSV)']);
            return;
        }

        $shop_id = $_POST['shop_id'] ?? '';
        if (!$shop_id) {
            echo json_encode(['status' => false, 'message' => 'shop_id wajib']);
            return;
        }

        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }

        $config       = json_decode($config_row['val'], true);
        $access_token = $config['access_token'] ?? '';
        if (!$access_token) {
            echo json_encode(['status' => false, 'message' => 'Access token Lazada tidak tersedia']);
            return;
        }

        $app_key    = $this->app_key_lazada;
        $app_secret = $this->app_secret_lazada;
        $url        = 'https://api.lazada.co.id/rest';

        $orders = $this->db
            ->where_in('order_id', $transaction_ids)
            ->where('marketplace', 'LAZADA')
            ->get('transaction')
            ->result_array();

        $orderResults   = [];
        $foundOrderIds  = [];

        if (!empty($orders)) {
            foreach ($orders as $row) {
                if (!empty($row['order_id'])) {
                    $foundOrderIds[] = (string) $row['order_id'];
                }
            }
        }

        $missingOrders = array_diff($transaction_ids, $foundOrderIds);
        foreach ($missingOrders as $missingId) {
            $orderResults[(string)$missingId] = [
                'order_id'       => (string)$missingId,
                'transaction_id' => (string)$missingId,
                'status'         => false,
                'message'        => 'Order tidak ditemukan di database'
            ];
        }

        if (empty($orders) && empty($orderResults)) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada order yang ditemukan']);
            return;
        }

        $client          = new LazopClient($url, $app_key, $app_secret);
        $pack_order_list = [];
        $packageMap      = [];

        foreach ($orders as $ord) {
            $order_id = trim((string) ($ord['order_id'] ?? ''));
            if ($order_id === '') {
                continue;
            }

            $reqItems = new LazopRequest('/order/items/get', 'GET');
            $reqItems->addApiParam('order_id', $order_id);

            $respItems    = $client->execute($reqItems, $access_token);
            $respItemsArr = json_decode($respItems, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $orderResults[$order_id] = [
                    'order_id'       => $order_id,
                    'transaction_id' => $order_id,
                    'status'         => false,
                    'message'        => 'Respon order/items tidak valid',
                    'raw'            => $respItems
                ];
                continue;
            }

            if (isset($respItemsArr['code']) && $respItemsArr['code'] !== '0' && $respItemsArr['code'] !== 0) {
                $orderResults[$order_id] = [
                    'order_id'       => $order_id,
                    'transaction_id' => $order_id,
                    'status'         => false,
                    'message'        => $respItemsArr['message'] ?? 'Gagal mengambil order item',
                    'raw'            => $respItemsArr
                ];
                continue;
            }

            $order_items = $respItemsArr['data']['order_items'] ?? $respItemsArr['data'] ?? [];
            if (!is_array($order_items) || empty($order_items)) {
                $orderResults[$order_id] = [
                    'order_id'       => $order_id,
                    'transaction_id' => $order_id,
                    'status'         => false,
                    'message'        => 'Tidak ada order item yang bisa diproses',
                    'raw'            => $respItemsArr
                ];
                continue;
            }

            $order_item_ids = [];
            $packageId      = '';

            foreach ($order_items as $it) {
                if (!empty($it['order_item_id'])) {
                    $order_item_ids[] = (string) $it['order_item_id'];
                }
                if ($packageId === '' && !empty($it['package_id'])) {
                    $packageId = (string) $it['package_id'];
                }
            }

            if (empty($order_item_ids)) {
                $orderResults[$order_id] = [
                    'order_id'       => $order_id,
                    'transaction_id' => $order_id,
                    'status'         => false,
                    'message'        => 'order_item_id tidak ditemukan pada order ini',
                    'raw'            => $respItemsArr
                ];
                continue;
            }

            $packageMap[$order_id] = $packageId;

            $pack_order_list[] = [
                "order_id"        => $order_id,
                "order_item_list" => $order_item_ids,
            ];

            $orderResults[$order_id] = [
                'order_id'        => $order_id,
                'transaction_id'  => $order_id,
                'order_item_list' => $order_item_ids,
                'shipping_method' => $ord['shipping'] ?? '',
                'package_id'      => $packageId,
                'status'          => null,
                'message'         => 'Menunggu respon Lazada'
            ];
        }

        if (empty($pack_order_list)) {
            $successCount = count(array_filter($orderResults, function($r) { return $r['status'] === true; }));
            $failedCount  = count($orderResults) - $successCount;
            echo json_encode([
                'status'  => false,
                'message' => 'Tidak ada order_item_id yang valid untuk diproses',
                'summary' => [
                    'total_orders'  => count($transaction_ids),
                    'valid_orders'  => 0,
                    'success'       => $successCount,
                    'failed'        => $failedCount
                ],
                'results' => array_values($orderResults)
            ]);
            return;
        }

        $payload = [
            "pack_order_list"        => $pack_order_list,
            "delivery_type"          => "dropship", 
            "shipping_allocate_type" => "TFS"     
        ];

        $payload_json = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $request = new LazopRequest('/order/fulfill/pack');
        $request->addApiParam('packReq', $payload_json);

        $responseRaw = $client->execute($request, $access_token);
        $responseArr = json_decode($responseRaw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            foreach ($pack_order_list as $packOrder) {
                $oid = $packOrder['order_id'];
                if (!isset($orderResults[$oid])) {
                    $orderResults[$oid] = [
                        'order_id'       => $oid,
                        'transaction_id' => $oid,
                    ];
                }
                $orderResults[$oid]['status']  = false;
                $orderResults[$oid]['message'] = 'Respon Lazada tidak valid';
                $orderResults[$oid]['raw']     = $responseRaw;
            }

            echo json_encode([
                'status'  => false,
                'message' => 'Respon Lazada tidak valid',
                'summary' => [
                    'total_orders'  => count($transaction_ids),
                    'valid_orders'  => count($pack_order_list),
                    'success'       => 0,
                    'failed'        => count($orderResults)
                ],
                'results' => array_values($orderResults)
            ]);
            return;
        }

        $code             = $responseArr['code'] ?? null;
        $isSuccessResponse = ($code === null || $code === '0' || $code === 0);
        $data             = $responseArr['data'] ?? [];
        $apiMessage       = $responseArr['message'] ?? ($isSuccessResponse ? 'Berhasil memproses order ke Lazada' : 'Gagal memproses order ke Lazada');

        $getOrderId = function($entry) {
            if (is_string($entry) || is_numeric($entry)) {
                return (string) $entry;
            }
            if (is_array($entry)) {
                return (string) ($entry['order_id'] ?? $entry['orderId'] ?? $entry['order'] ?? $entry['orderNo'] ?? '');
            }
            return '';
        };

        $failedOrdersRaw = $data['failed_orders'] ?? $data['failed_order_list'] ?? $data['fail_orders'] ?? $data['fail_order_list'] ?? [];
        $failedMap       = [];
        foreach ((array)$failedOrdersRaw as $fail) {
            $oid = $getOrderId($fail);
            if (!$oid) {
                continue;
            }
            $failedMap[$oid] = [
                'message' => $fail['fail_reason'] ?? $fail['reason'] ?? $fail['message'] ?? $apiMessage,
                'raw'     => $fail
            ];
        }

        $successOrdersRaw = $data['success_orders'] ?? $data['success_order_list'] ?? $data['pack_order_list'] ?? $data['success_list'] ?? [];
        $successOrderIds  = [];
        foreach ((array)$successOrdersRaw as $succ) {
            $oid = $getOrderId($succ);
            if ($oid) {
                $successOrderIds[] = $oid;
            }
        }
        $successOrderIds = array_values(array_unique($successOrderIds));

        if ($isSuccessResponse && empty($successOrderIds) && empty($failedMap)) {
            $successOrderIds = array_map(function($row) {
                return $row['order_id'];
            }, $pack_order_list);
        }

        $ordersToUpdate = []; 

        foreach ($pack_order_list as $packOrder) {
            $oid = $packOrder['order_id'];

            if (!isset($orderResults[$oid])) {
                $orderResults[$oid] = [
                    'order_id'        => $oid,
                    'transaction_id'  => $oid,
                    'order_item_list' => $packOrder['order_item_list'],
                    'shipping_method' => '',
                    'package_id'      => $packageMap[$oid] ?? '',
                    'status'          => null,
                    'message'         => ''
                ];
            }

            if (isset($failedMap[$oid])) {
                $orderResults[$oid]['status']      = false;
                $orderResults[$oid]['message']     = $failedMap[$oid]['message'];
                $orderResults[$oid]['raw_error']   = $failedMap[$oid]['raw'];
                continue;
            }

            $isSuccessOrder = in_array($oid, $successOrderIds, true);

            if ($isSuccessOrder || ($isSuccessResponse && empty($failedMap))) {
                $orderResults[$oid]['status']  = true;
                $orderResults[$oid]['message'] = 'Berhasil mengirim permintaan pengiriman ke Lazada';

                $pkgId = $orderResults[$oid]['package_id'] ?? ($packageMap[$oid] ?? '');
                $ordersToUpdate[$oid] = $pkgId;
            } else {
                $orderResults[$oid]['status']  = false;
                $orderResults[$oid]['message'] = $apiMessage;
            }
        }

        $updatedCount = 0;
        if (!empty($ordersToUpdate)) {
            foreach ($ordersToUpdate as $oid => $pkgId) {
                $this->db->where('order_id', $oid);

                $updateData = [
                    'rts_at'       => date('Y-m-d H:i:s'),
                    'order_status' => 'PROCESSED'
                ];

                if (!empty($pkgId)) {
                    $updateData['package_id'] = $pkgId;
                }

                $this->db->update('transaction', $updateData);
                $updatedCount += $this->db->affected_rows();
            }
        }

        $successCount = count(array_filter($orderResults, function($r) { return $r['status'] === true; }));
        $failedCount  = count($orderResults) - $successCount;

        echo json_encode([
            'status'  => $successCount > 0,
            'message' => $successCount > 0 ? 'Proses ship Lazada selesai' : ($apiMessage ?: 'Proses ship Lazada gagal'),
            'summary' => [
                'total_orders'  => count($transaction_ids),
                'valid_orders'  => count($pack_order_list),
                'success'       => $successCount,
                'failed'        => $failedCount,
                'rts_updated'   => $updatedCount
            ],
            'results' => array_values($orderResults)
        ]);
    }

    public function lazada_get_shipping_document()
    {
        header('Content-Type: application/json');

        $transaction_ids_input = $_POST['transaction_ids'] ?? [];
        if (is_string($transaction_ids_input)) {
            $transaction_ids = array_filter(array_map('trim', explode(',', $transaction_ids_input)));
        } else {
            $transaction_ids = (array) $transaction_ids_input;
        }

        if (empty($transaction_ids)) {
            echo json_encode(['status' => false, 'message' => 'transaction_ids wajib (array atau CSV)']);
            return;
        }

        $shop_id = $_POST['shop_id'] ?? '';
        if (!$shop_id) {
            echo json_encode(['status' => false, 'message' => 'shop_id wajib']);
            return;
        }

        // --- ambil config toko ---
        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }

        $config       = json_decode($config_row['val'], true);
        $app_key      = $this->app_key_lazada;
        $app_secret   = $this->app_secret_lazada;
        $access_token = $config['access_token'];
        $host         = 'https://api.lazada.co.id/rest';

        // --- Ambil semua data sekaligus dengan where_in ---
        $this->db->select('order_id, package_id, awb_number, shipping');
        $this->db->where_in('order_id', $transaction_ids);
        $transaction_data = $this->mymodel->selectData('transaction', []);

        // --- Kelompokkan package_id untuk Lazada ---
        $package_groups = [];

        foreach ($transaction_data as $tx_row) {
            if (empty($tx_row['order_id']) || empty($tx_row['package_id'])) {
                continue;
            }

            $package_groups[] = [
                'package_id'      => $tx_row['package_id'],
                'order_id'        => $tx_row['order_id'],
                'shipping_method' => $tx_row['shipping'] ?? 'unknown'
            ];
        }

        if (empty($package_groups)) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada order yang memiliki package_id']);
            return;
        }

        $max_packages_per_request = 20;
        $package_chunks = array_chunk($package_groups, $max_packages_per_request);

        $results = [];
        $download_links = [];
        $successful_orders = [];
        $error_messages = [];

        foreach ($package_chunks as $chunkIndex => $currentPackages) {
            $package_list = array_map(function($pkg) {
                return ['package_id' => $pkg['package_id']];
            }, $currentPackages);

            $request_payload = [
                'doc_type' => 'PDF',
                'packages' => $package_list
            ];

            $c = new LazopClient($host, $app_key, $app_secret);
            $request = new LazopRequest('/order/package/document/get');
            $request->addApiParam('getDocumentReq', json_encode($request_payload));

            $response_raw = $c->execute($request, $access_token);
            $response = json_decode($response_raw, true);

            $isSuccess = isset($response['code']) ? ((string)$response['code'] === '0') : false;
            $isSuccess = $isSuccess || ($response['result']['success'] ?? false);

            if (!$isSuccess || empty($response['result']['data'])) {
                $message = $response['message'] ?? ($response['error'] ?? 'Gagal mengambil dokumen dari Lazada');
                foreach ($currentPackages as $pkg) {
                    $results[] = [
                        'transaction_id' => $pkg['order_id'],
                        'package_id' => $pkg['package_id'],
                        'shipping_method' => $pkg['shipping_method'],
                        'status' => false,
                        'message' => $message,
                        'raw' => $response
                    ];
                }
                $error_messages[] = $message;
                continue;
            }

            $docData     = $response['result']['data'];
            $pdfUrl      = $docData['pdf_url'] ?? '';
            $fileBase64  = $docData['file'] ?? '';
            $docType     = strtoupper($docData['doc_type'] ?? 'PDF');
            $pdfContent  = '';
            $pdfSource   = $pdfUrl;

            if ($pdfUrl) {
                $pdfContent = @file_get_contents($pdfUrl);
            }

            if (!$pdfContent && $fileBase64) {
                $decodedFile = base64_decode($fileBase64);
                if (strpos($decodedFile, '%PDF') === 0) {
                    $pdfContent = $decodedFile;
                } else {
                    if (preg_match('/src\\s*=\\s*\"([^\"]+)\"/i', $decodedFile, $matches)) {
                        $iframeUrl = html_entity_decode($matches[1], ENT_QUOTES);
                        $pdfSource = $iframeUrl;
                        $pdfContent = @file_get_contents($iframeUrl);
                    }
                }
            }

            if (!$pdfContent) {
                $message = 'Tidak bisa mengambil file PDF dari Lazada';
                foreach ($currentPackages as $pkg) {
                    $results[] = [
                        'transaction_id' => $pkg['order_id'],
                        'package_id' => $pkg['package_id'],
                        'shipping_method' => $pkg['shipping_method'],
                        'status' => false,
                        'message' => $message,
                        'raw' => $response
                    ];
                }
                $error_messages[] = $message;
                continue;
            }

            if (!is_dir('./assets/shipping_documents/')) {
                mkdir('./assets/shipping_documents/', 0777, true);
            }

            $file_name = sprintf(
                'lazada_shipping_%s_%s_%02d.pdf',
                $shop_id,
                date('Ymd_His'),
                $chunkIndex + 1
            );
            $file_path = './assets/shipping_documents/' . $file_name;

            file_put_contents($file_path, $pdfContent);

            $publicPdfUrl = $this->buildEndpointAssetUrl('assets/shipping_documents/' . $file_name);
            $download_links[] = $publicPdfUrl;

            $labelImages = $this->convertLazadaPdfToImages($file_path, pathinfo($file_name, PATHINFO_FILENAME));
            $imageIndex = 0;
            $imageCount = count($labelImages);

            foreach ($currentPackages as $pkg) {
                $assignedImage = null;
                if ($imageCount > 0) {
                    $assignedImage = $labelImages[$imageIndex] ?? end($labelImages);
                    if ($imageIndex < $imageCount - 1) {
                        $imageIndex++;
                    }
                }

                $resultRow = [
                    'transaction_id' => $pkg['order_id'],
                    'package_id' => $pkg['package_id'],
                    'shipping_method' => $pkg['shipping_method'],
                    'status' => true,
                    'message' => 'Shipping document siap',
                    'file_path' => $file_path,
                    'file_name' => $file_name,
                    'download_group' => 'lazada',
                    'label_images' => $assignedImage ? [$assignedImage] : [],
                    'document_urls' => $assignedImage ? ['doc_url' => $assignedImage] : [],
                    'pdf_source' => $pdfSource ?: $pdfUrl,
                ];

                $results[] = $resultRow;
                $successful_orders[] = $pkg['order_id'];
            }
        }

        $print_updated_count = 0;
        if (!empty($successful_orders)) {
            $successful_orders = array_values(array_unique(array_filter($successful_orders)));
            $this->db->where_in('order_id', $successful_orders);
            $this->db->update('transaction', ['print_at' => date('Y-m-d H:i:s')]);
            $print_updated_count = $this->db->affected_rows();
        }

        $success_count = count(array_filter($results, function($r) { return !empty($r['status']); }));
        $failed_count = count($results) - $success_count;

        $printPayload = [];
        foreach ($results as $row) {
            $docUrl = $row['document_urls']['doc_url'] ?? null;
            if ($docUrl && !empty($row['transaction_id'])) {
                $printPayload[] = [
                    'transaction_id' => $row['transaction_id'],
                    'document_urls' => ['doc_url' => $docUrl]
                ];
            }
        }

        $responseBody = [
            'status'  => $success_count > 0,
            'message' => $success_count > 0 ? 'Berhasil mengambil dokumen Lazada' : ($error_messages[0] ?? 'Gagal mengambil dokumen Lazada'),
            'summary' => [
                'total_orders'  => count($transaction_ids),
                'valid_orders'  => count($package_groups),
                'success'       => $success_count,
                'failed'        => $failed_count,
                'print_updated' => $print_updated_count
            ],
            'results' => $results
        ];

        if (!empty($download_links)) {
            $responseBody['download_links'] = array_values(array_unique($download_links));
        }

        if (!empty($printPayload)) {
            $responseBody['open_print'] = true;
            $responseBody['print_payload'] = $printPayload;
        }

        echo json_encode($responseBody);
        return;
    }

    private function convertShopeePdfToImages(string $relativePath, string $identifier, string $shippingMethod = ''): array
    {
        if (!class_exists('Imagick')) {
            log_message('error', 'Imagick extension tidak tersedia untuk konversi PDF Shopee.');
            return [];
        }
    
        $cleanPath = ltrim(str_replace(['\\'], '/', $relativePath), '/');
        $cleanPath = preg_replace('#^\./#', '', $cleanPath);
    
        $absolutePath = rtrim(FCPATH, '/\\') . '/' . $cleanPath;
        if (!file_exists($absolutePath)) {
            log_message('error', 'File PDF Shopee tidak ditemukan: ' . $absolutePath);
            return [];
        }
    
        $outputDir = rtrim(FCPATH, '/\\') . '/assets/shipping_documents/shopee_images';
        if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
            log_message('error', 'Gagal membuat direktori output gambar Shopee: ' . $outputDir);
            return [];
        }
    
        $images = [];
        try {
            $imagick = new Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($absolutePath);
    
            $pageCount = $imagick->getNumberImages();
            for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++) {
                if (!$imagick->setIteratorIndex($pageIndex)) {
                    continue;
                }
    
                $frame = $imagick->getImage();
                $frame->setImageFormat('png');
                $frame->setImageBackgroundColor('white');
                if (method_exists($frame, 'setImageAlphaChannel')) {
                    $frame->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
                }
                $flattened = $frame->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
    
                $width  = $flattened->getImageWidth();
                $height = $flattened->getImageHeight();
                
                if (stripos($shippingMethod, 'ID Express') !== false) {
                    $cropHeight = (int)round($height * 0.57);
                } else {
                    $cropHeight = (int)round($height * 0.61);
                }
                
                if ($cropHeight < 1) {
                    $cropHeight = $height;
                }
                $flattened->cropImage($width, $cropHeight, 0, 0);
    
                $safeId   = preg_replace('/[^A-Za-z0-9]+/', '_', $identifier);
                $filename = sprintf('shopee_label_%s_%02d.png', $safeId, $pageIndex + 1);
                $target   = $outputDir . '/' . $filename;
    
                $flattened->writeImage($target);
                $flattened->destroy();
                $frame->destroy();
    
                $relativeUrl = 'assets/shipping_documents/shopee_images/' . $filename;
                $images[] = $this->buildEndpointAssetUrl($relativeUrl);
            }
    
            $imagick->clear();
            $imagick->destroy();
        } catch (Throwable $th) {
            log_message('error', 'Konversi PDF Shopee gagal: ' . $th->getMessage());
            return [];
        }
    
        return $images;
    }

    private function convertLazadaPdfToImages(string $relativePath, string $identifier): array
    {
        if (!class_exists('Imagick')) {
            log_message('error', 'Imagick extension tidak tersedia untuk konversi PDF Lazada.');
            return [];
        }

        $cleanPath = ltrim(str_replace(['\\'], '/', $relativePath), '/');
        $cleanPath = preg_replace('#^\./#', '', $cleanPath);

        $absolutePath = rtrim(FCPATH, '/\\') . '/' . $cleanPath;
        if (!file_exists($absolutePath)) {
            log_message('error', 'File PDF Lazada tidak ditemukan: ' . $absolutePath);
            return [];
        }

        $outputDir = rtrim(FCPATH, '/\\') . '/assets/shipping_documents/lazada_images';
        if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
            log_message('error', 'Gagal membuat direktori output gambar Lazada: ' . $outputDir);
            return [];
        }

        $images = [];
        try {
            $imagick = new Imagick();
            $imagick->setResolution(300, 300);
            $imagick->readImage($absolutePath);

            $pageCount = $imagick->getNumberImages();
            for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++) {
                if (!$imagick->setIteratorIndex($pageIndex)) {
                    continue;
                }

                $frame = $imagick->getImage();
                $frame->setImageFormat('png');
                $frame->setImageBackgroundColor('white');
                if (method_exists($frame, 'setImageAlphaChannel')) {
                    $frame->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
                }
                $flattened = $frame->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

                $width  = $flattened->getImageWidth();
                $height = $flattened->getImageHeight();
                $cropHeight = (int)round($height * 0.73);
                if ($cropHeight < 1) {
                    $cropHeight = $height;
                }
                $flattened->cropImage($width, $cropHeight, 0, 0);

                $safeId   = preg_replace('/[^A-Za-z0-9]+/', '_', $identifier);
                $filename = sprintf('lazada_label_%s_%02d.png', $safeId, $pageIndex + 1);
                $target   = $outputDir . '/' . $filename;

                $flattened->writeImage($target);
                $flattened->destroy();
                $frame->destroy();

                $relativeUrl = 'assets/shipping_documents/lazada_images/' . $filename;
                $images[] = $this->buildEndpointAssetUrl($relativeUrl);
            }

            $imagick->clear();
            $imagick->destroy();
        } catch (Throwable $th) {
            log_message('error', 'Konversi PDF Lazada gagal: ' . $th->getMessage());
            return [];
        }

        return $images;
    }

    private function buildEndpointAssetUrl(string $relativePath): string
    {
        $base = rtrim(base_url(), '/');
        return $base . '/' . ltrim($relativePath, '/');
    }

    public function get_shop_products_performance()
    {
        header('Content-Type: application/json');

        $shop_id = $_GET['shop_id'] ?? '';
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        
        if (!$shop_id || !$start_date || !$end_date) {
            echo json_encode(['status' => false, 'message' => 'shop_id, start_date, dan end_date wajib diisi']);
            return;
        }

        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }

        $config       = json_decode($config_row['val'], true);
        $app_key      = $config['app_key'] ?? '';
        $access_token = $config['access_token'] ?? '';
        $shop_cipher  = $config['shop']['cipher'] ?? '';
        $app_secret   = $this->app_secret_tiktok;
        $shop_id_val  = $config['shop']['id'] ?? $shop_id;

        if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
            echo json_encode(['status' => false, 'message' => 'Config tidak lengkap']);
            return;
        }

        $timest = time();
        
        $endpoint_path = '/analytics/202509/shop_products/performance';
        
        $params = [
            'sort_order' => 'DESC',
            'sort_field' => 'gmv',
            'currency' => 'LOCAL',
            'page_size' => 10,
            'start_date_ge' => $start_date,
            'end_date_lt' => $end_date,
            'app_key' => $app_key,
            'shop_cipher' => $shop_cipher,
            'shop_id' => $shop_id_val,
            'timestamp' => $timest, 
        ];

        if (!empty($_GET['page_token'])) {
            $params['page_token'] = $_GET['page_token'];
        }

        $pr = [
            'secret' => $app_secret,
            'timest' => $timest,
            'get'    => $params,
            'post'   => '',
            'url'    => 'https://open-api.tiktokglobalshop.com' . $endpoint_path
        ];

        $sign = $this->tiktok_signature_generator($pr);
        
        $params['sign'] = $sign;
        
        $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path . '?' . http_build_query($params);

        // Eksekusi request
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $request_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-tts-access-token: ' . $access_token
            ],
        ]);

        $response_body = curl_exec($curl);
        $curl_error    = curl_error($curl);
        $http_code     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curl_error) {
            echo json_encode(['status' => false, 'message' => 'CURL Error: ' . $curl_error]);
            return;
        }

        $response_json = json_decode($response_body, true);
        
        if (isset($response_json['code']) && $response_json['code'] == 0) {
            echo json_encode([
                'status' => true,
                'data' => $response_json['data'] ?? [],
                'message' => 'Berhasil mengambil data performa produk'
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Gagal mengambil data performa produk: ' . ($response_json['message'] ?? 'Unknown error'),
                'raw' => $response_json
            ]);
        }
    }

    public function get_affiliate_performance()
    {
        header('Content-Type: application/json');

        $shop_id = $_GET['shop_id'] ?? '';
        $creator_id = $_GET['creator_id'] ?? '';

        if (!$shop_id || !$creator_id) {
            echo json_encode(['status' => false, 'message' => 'shop_id dan creator_id wajib diisi']);
            return;
        }

        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }

        $config       = json_decode($config_row['val'], true);
        $app_key      = $config['app_key'] ?? '';
        $access_token = $config['access_token'] ?? '';
        $shop_cipher  = $config['shop']['cipher'] ?? '';
        $app_secret   = $this->app_secret_tiktok;

        if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
            echo json_encode(['status' => false, 'message' => 'Config tidak lengkap']);
            return;
        }

        $timest = time();
        $endpoint_path = '/affiliate_seller/202508/marketplace_creators/' . rawurlencode($creator_id);

        $params = [
            'app_key' => $app_key,
            'shop_cipher' => $shop_cipher,
            'timestamp' => $timest,
        ];

        $pr = [
            'secret' => $app_secret,
            'timest' => $timest,
            'get'    => $params,
            'post'   => '',
            'url'    => 'https://open-api.tiktokglobalshop.com' . $endpoint_path
        ];

        $sign = $this->tiktok_signature_generator($pr);
        $params['sign'] = $sign;

        $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path . '?' . http_build_query($params);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $request_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-tts-access-token: ' . $access_token
            ],
        ]);

        $response_body = curl_exec($curl);
        $curl_error    = curl_error($curl);
        $http_code     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curl_error) {
            echo json_encode(['status' => false, 'message' => 'CURL Error: ' . $curl_error]);
            return;
        }

        $response_json = json_decode($response_body, true);

        if (isset($response_json['code']) && $response_json['code'] == 0) {
            echo json_encode([
                'status' => true,
                'data' => $response_json['data'] ?? [],
                'message' => 'Berhasil mengambil performa affiliate'
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Gagal mengambil performa affiliate: ' . ($response_json['message'] ?? 'Unknown error'),
                'raw' => $response_json
            ]);
        }
    }

    public function search_marketplace_creators()
    {
        header('Content-Type: application/json');

        $shop_id = $_GET['shop_id'] ?? '';
        if (!$shop_id) {
            echo json_encode(['status' => false, 'message' => 'shop_id wajib diisi']);
            return;
        }

        $config_row = $this->mymodel->selectDataOne('marketplace_config', ['shop_id' => $shop_id]);
        if (!$config_row) {
            echo json_encode(['status' => false, 'message' => 'Config toko tidak ditemukan']);
            return;
        }

        $config       = json_decode($config_row['val'], true);
        $app_key      = $config['app_key'] ?? '';
        $access_token = $config['access_token'] ?? '';
        $shop_cipher  = $config['shop']['cipher'] ?? '';
        $app_secret   = $this->app_secret_tiktok;

        if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
            echo json_encode(['status' => false, 'message' => 'Config tidak lengkap']);
            return;
        }

        $raw_input = file_get_contents('php://input');
        $payload = json_decode($raw_input, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $allowed_filters = [
            'search_key',
            'keyword',
            'follower_demographics',
            'gmv_ranges',
            'units_sold_ranges',
            'category',
            'content_performance',
            'affiliate_data',
            'advanced_filters',
        ];

        $filters = [];
        foreach ($allowed_filters as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $value = $payload[$key];
            if ($value === '' || $value === null) {
                continue;
            }
            if (is_array($value) && empty($value)) {
                continue;
            }
            $filters[$key] = $value;
        }

        $timest = time();
        $endpoint_path = '/affiliate_seller/202508/marketplace_creators/search';

        $params = [
            'app_key' => $app_key,
            'shop_cipher' => $shop_cipher,
            'timestamp' => $timest,
        ];

        if (!empty($_GET['page_token'])) {
            $params['page_token'] = $_GET['page_token'];
        }
        if (isset($_GET['page_size']) && $_GET['page_size'] !== '') {
            $params['page_size'] = (int)$_GET['page_size'];
        } else {
            $params['page_size'] = 20;
        }

        $body = $filters
            ? json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '{}';

        $pr = [
            'secret' => $app_secret,
            'timest' => $timest,
            'get'    => $params,
            'post'   => $body,
            'url'    => 'https://open-api.tiktokglobalshop.com' . $endpoint_path
        ];

        $sign = $this->tiktok_signature_generator($pr);
        $params['sign'] = $sign;

        $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path . '?' . http_build_query($params);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $request_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-tts-access-token: ' . $access_token
            ],
        ]);

        $response_body = curl_exec($curl);
        $curl_error    = curl_error($curl);
        $http_code     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curl_error) {
            echo json_encode(['status' => false, 'message' => 'CURL Error: ' . $curl_error]);
            return;
        }

        $response_json = json_decode($response_body, true);

        if (isset($response_json['code']) && $response_json['code'] == 0) {
            echo json_encode([
                'status' => true,
                'data' => $response_json['data'] ?? [],
                'message' => 'Berhasil mengambil data marketplace creators'
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Gagal mengambil data marketplace creators: ' . ($response_json['message'] ?? 'Unknown error'),
                'raw' => $response_json,
                'http_code' => $http_code
            ]);
        }
    }

    public function tiktok_product_analytics()
    {
        header('Content-Type: application/json');

        $date = $_GET['date'] ?? '';
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 day'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        $granularity = $_GET['granularity'] ?? 'ALL';
        $currency = $_GET['currency'] ?? 'LOCAL';
        $product_status = $_GET['product_status'] ?? 'LIVE';
        $shop_id_filter = $_GET['shop_id'] ?? '';
        $product_id_filter = $_GET['product_id'] ?? '';

        if (trim($date) !== '') {
            $start_date = trim($date);
            $date_obj = DateTime::createFromFormat('Y-m-d', $start_date);
            if ($date_obj && $date_obj->format('Y-m-d') === $start_date) {
                $date_obj->modify('+1 day');
                $end_date = $date_obj->format('Y-m-d');
            } else {
                $end_date = $start_date;
            }
        }

        if (trim($start_date) === '') {
            $start_date = date('Y-m-d', strtotime('-1 day'));
        }
        if (trim($end_date) === '') {
            $end_date = date('Y-m-d');
        }

        $is_valid_date = function ($value) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return false;
            }
            $parsed = DateTime::createFromFormat('Y-m-d', $value);
            return $parsed && $parsed->format('Y-m-d') === $value;
        };

        if (!$is_valid_date($start_date) || !$is_valid_date($end_date)) {
            echo json_encode(['status' => false, 'message' => 'Format tanggal harus YYYY-MM-DD']);
            return;
        }
        if (strtotime($start_date) > strtotime($end_date)) {
            echo json_encode(['status' => false, 'message' => 'start_date tidak boleh lebih besar dari end_date']);
            return;
        }

        $shops_query = $this->db->select('id, shop_id, shop_name, val')
            ->from('marketplace_config')
            ->where('opt', 'TIKTOK')
            ->where('status', 'Aktif');
        if ($shop_id_filter !== '') {
            $shops_query->where('shop_id', $shop_id_filter);
        }
        $shops = $shops_query->order_by('id', 'DESC')->get()->result_array();

        if (count($shops) === 0) {
            echo json_encode(['status' => false, 'message' => 'Marketplace config TIKTOK aktif tidak ditemukan']);
            return;
        }

        $table_name = 'tiktok_product_analytics';
        $table_exists = $this->db->table_exists($table_name);
        if (!$table_exists) {
            echo json_encode(['status' => false, 'message' => "Table {$table_name} belum tersedia"]);
            return;
        }

        $sql_upsert = "INSERT INTO {$table_name}
            (date, shop_id, shop_name, product_id, product_name, sales_gmv, sales_item_sold, sales_order, live_impression, live_ctr, live_page_view, video_impression, video_ctr, video_page_view, pcard_impression, pcard_ctr, pcard_page_view, product_response, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                shop_name = VALUES(shop_name),
                product_name = VALUES(product_name),
                sales_gmv = VALUES(sales_gmv),
                sales_item_sold = VALUES(sales_item_sold),
                sales_order = VALUES(sales_order),
                live_impression = VALUES(live_impression),
                live_ctr = VALUES(live_ctr),
                live_page_view = VALUES(live_page_view),
                video_impression = VALUES(video_impression),
                video_ctr = VALUES(video_ctr),
                video_page_view = VALUES(video_page_view),
                pcard_impression = VALUES(pcard_impression),
                pcard_ctr = VALUES(pcard_ctr),
                pcard_page_view = VALUES(pcard_page_view),
                product_response = VALUES(product_response),
                updated_at = NOW()";

        $product_id_filter_list = [];
        if ($product_id_filter !== '') {
            $product_id_filter_list = array_values(array_filter(array_map('trim', explode(',', $product_id_filter))));
        }

        $result = [];
        $total_products = 0;
        $saved_attempt = 0;
        $saved_success = 0;
        $saved_failed = 0;
        $skipped_zero_impression = 0;

        foreach ($shops as $shop) {
            $shop_id = isset($shop['shop_id']) ? (string)$shop['shop_id'] : '';
            $shop_name = isset($shop['shop_name']) ? (string)$shop['shop_name'] : '';
            $config = json_decode($shop['val'], true);

            $app_key = $config['app_key'] ?? '';
            $access_token = $config['access_token'] ?? '';
            $shop_cipher = $config['shop']['cipher'] ?? '';
            $app_secret = $this->app_secret_tiktok;
            $shop_id_val = $config['shop']['id'] ?? $shop_id;

            if (!$app_key || !$access_token || !$shop_cipher || !$app_secret) {
                $result[] = [
                    'shop_id' => $shop_id,
                    'shop_name' => $shop_name,
                    'status' => false,
                    'message' => 'Config toko tidak lengkap',
                    'products' => []
                ];
                continue;
            }

            $product_query = $this->db->select('DISTINCT id_product, name', false)
                ->from('product_3rd')
                ->where('shop_id', $shop_id)
                ->where('id_product IS NOT NULL', null, false)
                ->where('id_product <>', '');

            if ($this->db->field_exists('marketplace', 'product_3rd')) {
                $product_query->where('marketplace', 'TIKTOK');
            }
            if (count($product_id_filter_list) > 0) {
                $product_query->where_in('id_product', $product_id_filter_list);
            }

            $product_rows = $product_query->get()->result_array();
            if (count($product_rows) === 0) {
                $result[] = [
                    'shop_id' => $shop_id,
                    'shop_name' => $shop_name,
                    'status' => true,
                    'message' => 'Tidak ada product_3rd untuk shop ini',
                    'products' => []
                ];
                continue;
            }

            $product_result = [];

            foreach ($product_rows as $product_row) {
                $product_id = isset($product_row['id_product']) ? trim((string)$product_row['id_product']) : '';
                $product_name = isset($product_row['name']) ? trim((string)$product_row['name']) : '';
                if ($product_id === '') {
                    continue;
                }

                $timest = time();
                $endpoint_path = '/analytics/202509/shop_products/' . $product_id . '/performance';

                $params = [
                    'granularity' => $granularity,
                    'currency' => $currency,
                    'product_status' => $product_status,
                    'start_date_ge' => $start_date,
                    'end_date_lt' => $end_date,
                    'app_key' => $app_key,
                    'shop_cipher' => $shop_cipher,
                    'shop_id' => $shop_id_val,
                    'timestamp' => $timest,
                ];

                $pr = [
                    'secret' => $app_secret,
                    'timest' => $timest,
                    'get'    => $params,
                    'post'   => '',
                    'url'    => 'https://open-api.tiktokglobalshop.com' . $endpoint_path
                ];

                $sign = $this->tiktok_signature_generator($pr);
                $params['sign'] = $sign;

                $request_url = 'https://open-api.tiktokglobalshop.com' . $endpoint_path . '?' . http_build_query($params);

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL            => $request_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING       => '',
                    CURLOPT_MAXREDIRS      => 10,
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST  => 'GET',
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'x-tts-access-token: ' . $access_token
                    ],
                ]);

                $response_body = curl_exec($curl);
                $curl_error = curl_error($curl);
                $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                $per_product_result = [
                    'product_id' => $product_id,
                    'product_name' => $product_name
                ];
                $save_date = $start_date;
                $sales_gmv = 0;
                $sales_item_sold = 0;
                $sales_order = 0;
                $live_impression = 0;
                $live_ctr = 0;
                $live_page_view = 0;
                $video_impression = 0;
                $video_ctr = 0;
                $video_page_view = 0;
                $pcard_impression = 0;
                $pcard_ctr = 0;
                $pcard_page_view = 0;

                if ($curl_error) {
                    $per_product_result['status'] = false;
                    $per_product_result['message'] = 'CURL Error: ' . $curl_error;
                    $per_product_result['http_code'] = $http_code;
                    $product_result[] = $per_product_result;
                    $skipped_zero_impression++;
                    continue;
                }

                $response_json = json_decode($response_body, true);
                if (!is_array($response_json)) {
                    $per_product_result['status'] = false;
                    $per_product_result['message'] = 'Response tidak valid JSON';
                    $per_product_result['http_code'] = $http_code;
                    $product_result[] = $per_product_result;
                    $skipped_zero_impression++;
                    continue;
                }

                if (isset($response_json['code']) && (int)$response_json['code'] === 0) {
                    $api_data = $response_json['data'] ?? [];
                    $performance = isset($api_data['performance']) && is_array($api_data['performance']) ? $api_data['performance'] : [];
                    $intervals = isset($performance['intervals']) && is_array($performance['intervals']) ? $performance['intervals'] : [];
                    $interval = count($intervals) > 0 && is_array($intervals[0]) ? $intervals[0] : [];

                    if (isset($interval['start_date']) && $is_valid_date((string)$interval['start_date'])) {
                        $save_date = (string)$interval['start_date'];
                    }

                    $sales = isset($interval['sales']) && is_array($interval['sales']) ? $interval['sales'] : [];
                    $traffic = isset($interval['traffic']) && is_array($interval['traffic']) ? $interval['traffic'] : [];
                    $sales_gmv = isset($sales['gmv']['amount']) ? (float)$sales['gmv']['amount'] : 0;
                    $sales_item_sold = isset($sales['items_sold']) ? (int)$sales['items_sold'] : 0;
                    $sales_order = isset($sales['orders']) ? (int)$sales['orders'] : 0;

                    $traffic_breakdowns = isset($traffic['breakdowns']) && is_array($traffic['breakdowns']) ? $traffic['breakdowns'] : [];
                    foreach ($traffic_breakdowns as $traffic_item) {
                        if (!is_array($traffic_item)) {
                            continue;
                        }
                        $content_type = strtoupper(isset($traffic_item['content_type']) ? (string)$traffic_item['content_type'] : '');
                        $traffic_val = isset($traffic_item['traffic']) && is_array($traffic_item['traffic']) ? $traffic_item['traffic'] : [];
                        if ($content_type === 'LIVE') {
                            $live_impression = isset($traffic_val['impressions']) ? (int)$traffic_val['impressions'] : 0;
                            $live_ctr = isset($traffic_val['ctr']) ? (float)$traffic_val['ctr'] : 0;
                            $live_page_view = isset($traffic_val['page_views']) ? (int)$traffic_val['page_views'] : 0;
                        } else if ($content_type === 'VIDEO') {
                            $video_impression = isset($traffic_val['impressions']) ? (int)$traffic_val['impressions'] : 0;
                            $video_ctr = isset($traffic_val['ctr']) ? (float)$traffic_val['ctr'] : 0;
                            $video_page_view = isset($traffic_val['page_views']) ? (int)$traffic_val['page_views'] : 0;
                        } else if ($content_type === 'PRODUCT_CARD') {
                            $pcard_impression = isset($traffic_val['impressions']) ? (int)$traffic_val['impressions'] : 0;
                            $pcard_ctr = isset($traffic_val['ctr']) ? (float)$traffic_val['ctr'] : 0;
                            $pcard_page_view = isset($traffic_val['page_views']) ? (int)$traffic_val['page_views'] : 0;
                        }
                    }

                    $per_product_result['status'] = true;
                    $per_product_result['message'] = $response_json['message'] ?? 'Success';
                    $per_product_result['data'] = $api_data;
                    $product_result[] = $per_product_result;
                    $total_products++;
                } else {
                    $per_product_result['status'] = false;
                    $per_product_result['message'] = 'Gagal mengambil detail performa produk: ' . ($response_json['message'] ?? 'Unknown error');
                    $per_product_result['http_code'] = $http_code;
                    $per_product_result['raw'] = $response_json;
                    $product_result[] = $per_product_result;
                }

                if ((int)$live_impression > 0 || (int)$video_impression > 0 || (int)$pcard_impression > 0) {
                    $saved_attempt++;
                    $save_run = $this->db->query($sql_upsert, [
                        $save_date,
                        $shop_id,
                        $shop_name,
                        $product_id,
                        $product_name,
                        $sales_gmv,
                        $sales_item_sold,
                        $sales_order,
                        $live_impression,
                        $live_ctr,
                        $live_page_view,
                        $video_impression,
                        $video_ctr,
                        $video_page_view,
                        $pcard_impression,
                        $pcard_ctr,
                        $pcard_page_view,
                        json_encode($per_product_result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    ]);
                    if ($save_run) {
                        $saved_success++;
                    } else {
                        $saved_failed++;
                    }
                } else {
                    $skipped_zero_impression++;
                }
            }

            $result[] = [
                'shop_id' => $shop_id,
                'shop_name' => $shop_name,
                'status' => true,
                'message' => 'OK',
                'products' => $product_result
            ];
        }

        echo json_encode([
            'status' => true,
            'message' => 'Berhasil mengambil detail performa produk per shop',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'shop_count' => count($shops),
            'success_product_count' => $total_products,
            'saved_attempt' => $saved_attempt,
            'saved_success' => $saved_success,
            'saved_failed' => $saved_failed,
            'skipped_zero_impression' => $skipped_zero_impression,
        ]);
    }

    function marketplace_tiktok_settlement_bulk()
    {
        header('Content-Type: application/json; charset=utf-8');

        $params = $_GET;
        $order_ids_raw = isset($params['order_ids']) ? trim($params['order_ids']) : '';
        $start_date = isset($params['start_date']) ? trim($params['start_date']) : '';
        $end_date = isset($params['end_date']) ? trim($params['end_date']) : '';
        $shop_id_param = isset($params['shop_id']) ? trim($params['shop_id']) : '';

        $order_ids = [];
        $source_mode = 'order_ids';
        if (!empty($order_ids_raw)) {
            $order_ids = array_values(array_unique(array_filter(array_map('trim', explode(',', $order_ids_raw)))));
        } else if (!empty($start_date) && !empty($end_date)) {
            $date_start = DateTime::createFromFormat('Y-m-d', $start_date);
            $date_end = DateTime::createFromFormat('Y-m-d', $end_date);
            if (!$date_start || $date_start->format('Y-m-d') !== $start_date || !$date_end || $date_end->format('Y-m-d') !== $end_date) {
                echo json_encode([
                    'status' => false,
                    'data' => [],
                    'msg' => 'Format tanggal harus Y-m-d.'
                ]);
                die;
            }
            if (strtotime($start_date) > strtotime($end_date)) {
                echo json_encode([
                    'status' => false,
                    'data' => [],
                    'msg' => 'start_date tidak boleh lebih besar dari end_date.'
                ]);
                die;
            }

            $this->db->select('order_id');
            $this->db->from('transaction');
            $this->db->where('marketplace', 'TIKTOK');
            $this->db->where('pencairan_status', '');
            $this->db->where('UPPER(kebutuhan)', 'AFFILIATE');
            $this->db->where('DATE(date) >=', $start_date);
            $this->db->where('DATE(date) <=', $end_date);
            if (!empty($shop_id_param)) {
                $this->db->where('shop_id', $shop_id_param);
            }
            $query = $this->db->get()->result_array();
            foreach ($query as $row) {
                if (!empty($row['order_id'])) {
                    $order_ids[] = trim($row['order_id']);
                }
            }
            $order_ids = array_values(array_unique($order_ids));
            $source_mode = 'date_range';
        } else {
            echo json_encode([
                'status' => false,
                'data' => [],
                'msg' => 'Isi salah satu: order_ids, atau start_date dan end_date.'
            ]);
            die;
        }

        if (empty($order_ids)) {
            echo json_encode([
                'status' => false,
                'data' => [],
                'msg' => 'Data order tidak ditemukan untuk diproses.'
            ]);
            die;
        }

        $app_secret = $this->app_secret_tiktok;
        $results = [];
        $success_count = 0;
        $failed_count = 0;
        $config_cache = [];

        $load_config = function ($shop_id) use (&$config_cache) {
            if (isset($config_cache[$shop_id])) {
                return $config_cache[$shop_id];
            }

            $cfg_row = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $shop_id, 'opt' => 'TIKTOK', 'status' => 'Aktif'));
            if (empty($cfg_row) || empty($cfg_row['val'])) {
                $config_cache[$shop_id] = null;
                return null;
            }

            $cfg = json_decode($cfg_row['val'], true);
            if (empty($cfg)) {
                $config_cache[$shop_id] = null;
                return null;
            }

            $config_cache[$shop_id] = [
                'app_key' => isset($cfg['app_key']) ? $cfg['app_key'] : '',
                'access_token' => isset($cfg['access_token']) ? $cfg['access_token'] : '',
                'shop_cipher' => isset($cfg['shop']['cipher']) ? $cfg['shop']['cipher'] : '',
                'shop_id' => $cfg_row['shop_id'],
                'shop_name' => $cfg_row['shop_name'],
            ];
            return $config_cache[$shop_id];
        };

        foreach ($order_ids as $order_id) {
            $result = [
                'order_id' => $order_id,
                'status' => false,
                'msg' => '',
            ];

            $trx = $this->mymodel->selectWithQuery("SELECT id, shop_id, kebutuhan FROM transaction WHERE order_id = " . $this->db->escape($order_id) . " AND marketplace = 'TIKTOK' AND UPPER(kebutuhan) = 'AFFILIATE' LIMIT 1");
            $trx = isset($trx[0]) ? $trx[0] : [];
            $shop_id = $shop_id_param;
            if (empty($shop_id) && !empty($trx['shop_id'])) {
                $shop_id = $trx['shop_id'];
            }

            if (empty($shop_id)) {
                $result['msg'] = 'shop_id tidak ditemukan untuk order ini.';
                $results[] = $result;
                $failed_count++;
                continue;
            }

            if (empty($trx['id'])) {
                $result['msg'] = 'Order tidak ditemukan di transaction dengan kebutuhan Affiliate.';
                $result['shop_id'] = $shop_id;
                $results[] = $result;
                $failed_count++;
                continue;
            }

            $cfg = $load_config($shop_id);
            if (empty($cfg) || empty($cfg['app_key']) || empty($cfg['access_token']) || empty($cfg['shop_cipher'])) {
                $result['msg'] = 'Konfigurasi TikTok shop tidak valid/aktif.';
                $result['shop_id'] = $shop_id;
                $results[] = $result;
                $failed_count++;
                continue;
            }

            $url = 'https://open-api.tiktokglobalshop.com/finance/202501/orders/' . $order_id . '/statement_transactions?access_token=' . $cfg['access_token'] . '&app_key=' . $cfg['app_key'] . '&shop_cipher=' . $cfg['shop_cipher'] . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}';
            $urlParts = parse_url($url);
            $paramGET = [];
            parse_str($urlParts['query'], $paramGET);

            $timest = strtotime('now');
            $pr = array();
            $pr['secret'] = $app_secret;
            $pr['timest'] = $timest;
            $pr['get'] = $paramGET;
            $pr['url'] = $url;
            $sign = $this->tiktok_signature_generator($pr);

            $url = str_replace('{{sign}}', $sign, $url);
            $url = str_replace('{{timestamp}}', $timest, $url);

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
                    'x-tts-access-token: ' . $cfg['access_token']
                ),
            ));
            $response_raw = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response_raw, true);
            if (!is_array($response)) {
                $result['msg'] = 'Response settlement bukan JSON valid.';
                $result['shop_id'] = $shop_id;
                $results[] = $result;
                $failed_count++;
                continue;
            }

            if (!isset($response['code']) || intval($response['code']) !== 0) {
                $result['msg'] = isset($response['message']) ? $response['message'] : 'Gagal ambil settlement.';
                $result['shop_id'] = $shop_id;
                $result['response'] = $response;
                $results[] = $result;
                $failed_count++;
                continue;
            }

            $payment = isset($response['data']) && is_array($response['data']) ? $response['data'] : [];
            $total_komisi_afiliasi = 0;
            $total_platform_commission = 0;
            $total_sfp_service_fee = 0;
            $sku_transactions = isset($payment['sku_transactions']) && is_array($payment['sku_transactions']) ? $payment['sku_transactions'] : [];
            foreach ($sku_transactions as $sku) {
                $fee_breakdown = isset($sku['fee_tax_breakdown']['fee']) && is_array($sku['fee_tax_breakdown']['fee']) ? $sku['fee_tax_breakdown']['fee'] : [];
                $total_komisi_afiliasi += abs(doubleval(isset($fee_breakdown['affiliate_commission_amount']) ? $fee_breakdown['affiliate_commission_amount'] : 0));
                $total_platform_commission += abs(doubleval(isset($fee_breakdown['platform_commission_amount']) ? $fee_breakdown['platform_commission_amount'] : 0));
                $total_sfp_service_fee += abs(doubleval(isset($fee_breakdown['sfp_service_fee_amount']) ? $fee_breakdown['sfp_service_fee_amount'] : 0));
            }

            $update = [];
            $update['updated_at'] = DATE("Y-m-d H:i:s");
            $update['komisi_afiliasi'] = $total_komisi_afiliasi;
            $update['marketplace_fee'] = $total_platform_commission + $total_sfp_service_fee;
            $update['pencairan_status'] = '';
            $update['pencairan_at'] = '';
            if (isset($payment['settlement_amount'])) {
                $update['dana_pencairan'] = doubleval($payment['settlement_amount']);
            }
            $update['pencairan_status'] = 'Settlement';
            $update['pencairan_at'] = DATE("Y-m-d H:i:s", intval($payment['settlement_time'])) ?? '';

            $updated_db = false;
            if (!empty($trx['id'])) {
                $updated_db = $this->db->update('transaction', $update, array('id' => $trx['id']));
            }

            $result['status'] = true;
            $result['msg'] = 'Settlement berhasil diambil.';
            $result['shop_id'] = $shop_id;
            $result['shop_name'] = $cfg['shop_name'];
            $result['settlement_amount'] = isset($payment['settlement_amount']) ? doubleval($payment['settlement_amount']) : null;
            $result['settlement_time'] = !empty($payment['settlement_time']) ? DATE("Y-m-d H:i:s", intval($payment['settlement_time'])) : '';
            $result['transaction_updated'] = $updated_db ? 1 : 0;
            $results[] = $result;
            $success_count++;
        }

        echo json_encode([
            'status' => true,
            'msg' => 'Proses settlement TikTok selesai.',
            'source_mode' => $source_mode,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'input_count' => count($order_ids),
            'success_count' => $success_count,
            'failed_count' => $failed_count,
            'data' => $results
        ]);
    }

    public function openrouter_chat()
    {
        header('Content-Type: application/json; charset=utf-8');

        $raw_input = file_get_contents('php://input');
        $input = json_decode($raw_input, true);

        if (!is_array($input)) {
            $input = $_POST;
        }

        if (!is_array($input)) {
            $input = array();
        }

        $messages = $input['messages'] ?? array();
        $prompt = trim((string)($input['prompt'] ?? ''));

        if ((empty($messages) || !is_array($messages)) && $prompt === '') {
            echo json_encode(array(
                'status' => false,
                'message' => 'messages atau prompt wajib diisi'
            ));
            return;
        }

        $default_model = trim((string)$this->config->item('ai_gateway_model'));
        $model = trim((string)($input['model'] ?? $default_model));
        $default_max_tokens = intval($this->config->item('ai_gateway_max_tokens'));
        if ($default_max_tokens <= 0) {
            $default_max_tokens = 256;
        }

        $params = array(
            'messages' => $messages,
            'prompt' => $prompt,
            'system' => trim((string)($input['system'] ?? '')),
            'temperature' => isset($input['temperature']) ? $input['temperature'] : 0.7,
            'max_tokens' => isset($input['max_tokens']) ? intval($input['max_tokens']) : $default_max_tokens,
            'timeout' => isset($input['timeout']) ? intval($input['timeout']) : 120,
        );

        if ($model !== '') {
            $params['model'] = $model;
        }

        if (array_key_exists('top_p', $input) && $input['top_p'] !== '' && $input['top_p'] !== null) {
            $params['top_p'] = $input['top_p'];
        }

        if (array_key_exists('provider', $input) && is_array($input['provider'])) {
            $params['provider'] = $input['provider'];
        }

        if (array_key_exists('response_format', $input) && is_array($input['response_format'])) {
            $params['response_format'] = $input['response_format'];
        }

        if (array_key_exists('extra', $input) && is_array($input['extra'])) {
            $params['extra'] = $input['extra'];
        }

        $result = $this->template->ai_gateway_chat($params);

        echo json_encode(array(
            'status' => (bool)($result['status'] ?? false),
            'message' => (string)($result['msg'] ?? ''),
            'http_code' => intval($result['http_code'] ?? 0),
            'text' => (string)($result['text'] ?? ''),
            'data' => $result['data'] ?? array(),
            'payload' => $result['payload'] ?? array()
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function webhook_chat_summary()
    {
        header('Content-Type: application/json; charset=utf-8');

        $sender_name = trim((string)($this->input->get('sender_name', true) ?? ''));
        $sender_id = trim((string)($this->input->get('sender_id', true) ?? ''));

        if ($sender_name === '' && $sender_id === '') {
            echo json_encode(array(
                'status' => false,
                'message' => 'sender_name atau sender_id wajib diisi melalui query param'
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $this->db
            ->select('id, marketplace, conversation_id, sender_id, sender_name, message_type, chat_text, created_at')
            ->from('webhook_chat')
            ->where('type', 'message');

        if ($sender_name !== '') {
            $this->db->where('sender_name', $sender_name);
        } else {
            $this->db->where('sender_id', $sender_id);
        }

        $rows = $this->db
            ->order_by('id', 'ASC')
            ->get()
            ->result_array();

        if (empty($rows)) {
            echo json_encode(array(
                'status' => false,
                'message' => 'Chat tidak ditemukan untuk parameter yang diberikan',
                'sender_name' => $sender_name,
                'sender_id' => $sender_id,
                'labels' => array(),
                'summary' => ''
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $chat_lines = array();
        $chat_texts = array();
        foreach ($rows as $row) {
            $message_type = trim((string)($row['message_type'] ?? ''));
            $chat_text = trim((string)($row['chat_text'] ?? ''));

            if ($chat_text !== '') {
                $chat_lines[] = '[' . ($row['created_at'] ?? '') . '] customer: ' . $chat_text;
                $chat_texts[] = $chat_text;
            } elseif ($message_type !== '') {
                $chat_lines[] = '[' . ($row['created_at'] ?? '') . '] customer mengirim ' . $message_type;
            }
        }

        $combined_chat = trim(implode("\n", $chat_lines));
        $combined_text = trim(implode(' ', $chat_texts));

        $fallback = $this->build_webhook_chat_summary_fallback($combined_text, $sender_name);
        $ai_result = null;

        if ($combined_chat !== '') {
            $allowed_labels = array('nyeri haid', 'telat haid', 'keputihan', 'gatal', 'haid tidak lancar', 'bau');
            $system_prompt = "Kamu adalah analis chat customer. Tugasmu membaca chat customer lalu mengembalikan JSON valid tanpa markdown. "
                . "Gunakan label hanya dari daftar ini: " . implode(', ', $allowed_labels) . ". "
                . "Output wajib format: {\"labels\":[...],\"summary\":\"...\"}. "
                . "Summary harus satu kalimat bahasa Indonesia, singkat, jelas, dan diawali kata 'Customer'. "
                . "Kalau customer menanyakan produk wash atau sabun, sebutkan itu jika relevan. "
                . "Jangan tambahkan label di luar daftar.";

            $user_prompt = "sender_name: " . $sender_name . "\n\nChat customer:\n" . $combined_chat;
            if ($sender_id !== '') {
                $user_prompt = "sender_id: " . $sender_id . "\n" . $user_prompt;
            }

            $ai_params = array(
                'system' => $system_prompt,
                'prompt' => $user_prompt,
                'temperature' => 0.2,
                'max_tokens' => 200,
                'timeout' => 120
            );

            $default_model = trim((string)$this->config->item('ai_gateway_model'));
            if ($default_model !== '') {
                $ai_params['model'] = $default_model;
            }

            $ai_result = $this->template->ai_gateway_chat($ai_params);
        }

        $parsed_ai = $this->parse_webhook_chat_summary_ai($ai_result['text'] ?? '');
        $labels = !empty($parsed_ai['labels']) ? $parsed_ai['labels'] : $fallback['labels'];
        $summary = trim((string)($parsed_ai['summary'] ?? ''));
        if ($summary === '') {
            $summary = $fallback['summary'];
        }

        echo json_encode(array(
            'status' => true,
            'message' => 'Summary chat berhasil dibuat',
            'sender_name' => $rows[0]['sender_name'] ?? $sender_name,
            'sender_id' => $rows[0]['sender_id'] ?? $sender_id,
            'marketplace' => $rows[0]['marketplace'] ?? '',
            'conversation_id' => $rows[0]['conversation_id'] ?? '',
            'labels' => array_values($labels),
            'summary' => $summary,
            'last_chat_at' => $rows[count($rows) - 1]['created_at'] ?? '',
            'chat_count' => count($rows),
            'chat_text_count' => count($chat_texts),
            'chat_preview' => $chat_lines,
            'ai_status' => (bool)($ai_result['status'] ?? false),
            'ai_message' => (string)($ai_result['msg'] ?? ''),
            'ai_text' => (string)($ai_result['text'] ?? '')
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function parse_webhook_chat_summary_ai($text)
    {
        $text = trim((string)$text);
        if ($text === '') {
            return array('labels' => array(), 'summary' => '');
        }

        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            if (preg_match('/\{.*\}/s', $text, $matches)) {
                $decoded = json_decode($matches[0], true);
            }
        }

        if (!is_array($decoded)) {
            return array('labels' => array(), 'summary' => '');
        }

        $allowed_labels = array('nyeri haid', 'telat haid', 'keputihan', 'gatal', 'haid tidak lancar', 'bau');
        $labels = array();
        foreach ((array)($decoded['labels'] ?? array()) as $label) {
            $label = strtolower(trim((string)$label));
            if (in_array($label, $allowed_labels, true)) {
                $labels[$label] = $label;
            }
        }

        return array(
            'labels' => array_values($labels),
            'summary' => trim((string)($decoded['summary'] ?? ''))
        );
    }

    private function build_webhook_chat_summary_fallback($combined_text, $sender_name = '')
    {
        $text = strtolower(trim((string)$combined_text));
        $labels = array();

        $label_patterns = array(
            'nyeri haid' => array('nyeri haid', 'sakit haid', 'haid sakit', 'kram haid', 'nyeri mens', 'sakit mens'),
            'telat haid' => array('telat haid', 'haid telat', 'telat mens', 'mens telat', 'belum haid'),
            'keputihan' => array('keputihan', 'putihan'),
            'gatal' => array('gatal', 'gatel'),
            'haid tidak lancar' => array('haid tidak lancar', 'mens tidak lancar', 'haid ga lancar', 'haid gak lancar', 'siklus haid', 'haid tidak teratur', 'mens tidak teratur'),
            'bau' => array('bau', 'berbau', 'bau tidak sedap', 'bau amis')
        );

        foreach ($label_patterns as $label => $patterns) {
            foreach ($patterns as $pattern) {
                if ($pattern !== '' && strpos($text, $pattern) !== false) {
                    $labels[$label] = $label;
                    break;
                }
            }
        }

        $product_interest = '';
        if (strpos($text, 'wash') !== false || strpos($text, 'sabun') !== false || strpos($text, 'v wash') !== false) {
            $product_interest = ' dan ingin produk wash untuk mengatasinya';
        }

        if (!empty($labels)) {
            $summary = 'Customer mengalami ' . $this->format_webhook_chat_labels($labels) . $product_interest . '.';
        } else if ($text !== '') {
            $summary = 'Customer menanyakan produk yang sesuai dengan keluhannya.';
        } else {
            $summary = 'Customer menghubungi toko terkait produk dan keluhannya.';
        }

        return array(
            'labels' => array_values($labels),
            'summary' => $summary
        );
    }

    private function format_webhook_chat_labels($labels)
    {
        $labels = array_values(array_filter(array_map('trim', (array)$labels)));
        $count = count($labels);

        if ($count <= 0) {
            return 'keluhan tertentu';
        }
        if ($count === 1) {
            return $labels[0];
        }
        if ($count === 2) {
            return $labels[0] . ' dan ' . $labels[1];
        }

        $last = array_pop($labels);
        return implode(', ', $labels) . ', dan ' . $last;
    }

    private function request_webhook_chat_summary_by_sender_id($sender_id)
    {
        $sender_id = trim((string)$sender_id);
        if ($sender_id === '') {
            return null;
        }

        $url = rtrim((string)base_url(), '/') . '/api_v2/webhook_chat_summary?sender_id=' . urlencode($sender_id);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $curl_error = curl_error($curl);
        curl_close($curl);

        if ($curl_error) {
            return null;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['status'])) {
            return null;
        }

        return $decoded;
    }

    private function append_customer_history_json($existing_json, $new_entry, $type = 'summary')
    {
        $history = json_decode((string)$existing_json, true);
        if (!is_array($history)) {
            $history = array();
        }

        $normalized_entry = array(
            'date' => trim((string)($new_entry['date'] ?? date("Y-m-d H:i:s"))),
            'source' => trim((string)($new_entry['source'] ?? 'manual'))
        );

        if ($type === 'labels') {
            $labels = isset($new_entry['labels']) && is_array($new_entry['labels']) ? array_values(array_unique(array_filter(array_map('trim', $new_entry['labels'])))) : array();
            if (empty($labels)) {
                return json_encode($history, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $normalized_entry['labels'] = $labels;
        } else {
            $summary = trim((string)($new_entry['summary'] ?? ''));
            if ($summary === '') {
                return json_encode($history, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $normalized_entry['summary'] = $summary;
        }

        foreach ($history as $row) {
            if (!is_array($row)) {
                continue;
            }

            if ($type === 'labels') {
                $existing_labels = isset($row['labels']) && is_array($row['labels']) ? array_values(array_unique(array_filter(array_map('trim', $row['labels'])))) : array();
                if (
                    trim((string)($row['date'] ?? '')) === $normalized_entry['date'] &&
                    json_encode($existing_labels) === json_encode($normalized_entry['labels'])
                ) {
                    return json_encode($history, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } else {
                if (
                    trim((string)($row['date'] ?? '')) === $normalized_entry['date'] &&
                    trim((string)($row['summary'] ?? '')) === $normalized_entry['summary']
                ) {
                    return json_encode($history, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
        }

        $history[] = $normalized_entry;
        return json_encode($history, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

}
