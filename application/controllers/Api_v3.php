<?php

require 'vendor/autoload.php';

use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Lazada\LazopClient;
use Lazada\LazopRequest;

defined('BASEPATH') or exit('No direct script access allowed');
class Api_v3 extends CI_Controller
{
    private $current_tax;
    private $tiktok_bc_advertiser_cache = null;
    private $cron_lock_keys = array();
    private $cron_lock_shutdown_registered = false;

    function __construct()
    {
        parent::__construct();
        $this->app_key_tiktok = app_env('TIKTOK_APP_KEY');
        $this->app_secret_tiktok = app_env('TIKTOK_APP_SECRET');
        $this->app_key_lazada = app_env('LAZADA_APP_KEY');
        $this->app_secret_lazada = app_env('LAZADA_APP_SECRET');
        $this->partner_id_shopee = app_env('SHOPEE_PARTNER_ID');
        $this->partner_key_shopee = app_env('SHOPEE_PARTNER_KEY');
        $this->app_id_meta = app_env('META_APP_ID');
        $this->app_secret_meta = app_env('META_APP_SECRET');

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $tax = $this->mymodel->selectWithQuery("SELECT tax FROM config WHERE id = 'TAX'");
        $this->current_tax = $tax[0]['tax'] ?? 0; // Gunakan 0 jika data tidak ditemukan
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

    public function fonnte_message_status()
    {
        header('Content-Type: application/json; charset=utf-8');

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $messageId = isset($data['id']) ? trim((string)$data['id']) : '';
        $stateId = isset($data['stateid']) ? trim((string)$data['stateid']) : '';
        $status = isset($data['status']) ? trim((string)$data['status']) : '';
        $state = isset($data['state']) ? trim((string)$data['state']) : '';
        $reason = $state !== '' ? $state : $status;

        if ($messageId === '' && $stateId === '') {
            echo json_encode(array('status' => false, 'msg' => 'Message id/state id kosong.'));
            return;
        }

        $whereSql = '';
        if ($messageId !== '') {
            $messageIdSql = $this->db->escape_str($messageId);
            $whereSql = "fonnte_message_id = '$messageIdSql'";
        } else {
            $stateIdSql = $this->db->escape_str($stateId);
            $whereSql = "request_id = '$stateIdSql'";
        }

        $logs = $this->mymodel->selectWithQuery("
            SELECT id, campaign_id, customer_id
            FROM crm_campaign_broadcast_logs
            WHERE $whereSql
            LIMIT 20
        ");

        if (empty($logs)) {
            echo json_encode(array('status' => true, 'msg' => 'Log tidak ditemukan, webhook diterima.'));
            return;
        }

        $successStatuses = array('sent', 'delivered', 'read');
        $statusLower = strtolower($status);
        $stateLower = strtolower($state);
        foreach ($logs as $log) {
            $this->db->update('crm_campaign_broadcast_logs', array(
                'status' => $status !== '' ? $status : ($state !== '' ? $state : 'updated'),
                'reason' => $reason,
                'response_json' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ), array('id' => (int)$log['id']));

            if (in_array($statusLower, $successStatuses, true) || in_array($stateLower, $successStatuses, true)) {
                $campaignId = (int)$log['campaign_id'];
                $customerId = (int)$log['customer_id'];
                if ($campaignId > 0 && $customerId > 0) {
                    $campaignRows = $this->mymodel->selectWithQuery("SELECT name FROM crm_campaign_broadcast WHERE id = '$campaignId' LIMIT 1");
                    if (!empty($campaignRows)) {
                        $this->db->update('customer', array(
                            'campaign_broadcast' => (string)$campaignRows[0]['name'],
                            'updated_at' => DATE("Y-m-d H:i:s"),
                        ), array('id' => $customerId));
                    }
                }
            }
        }

        echo json_encode(array('status' => true, 'msg' => 'Webhook status Fonnte berhasil diproses.'));
    }

    public function meta_whatsapp_webhook()
    {
        date_default_timezone_set('Asia/Jakarta');

        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'GET';

        if ($method === 'GET') {
            $mode = $this->input->get('hub_mode', true);
            $token = $this->input->get('hub_verify_token', true);
            $challenge = $this->input->get('hub_challenge', true);

            if ($mode === null) {
                $mode = $this->input->get('hub.mode', true);
            }
            if ($token === null) {
                $token = $this->input->get('hub.verify_token', true);
            }
            if ($challenge === null) {
                $challenge = $this->input->get('hub.challenge', true);
            }

            $verifyToken = app_env('META_WHATSAPP_VERIFY_TOKEN');

            if ($mode === 'subscribe' && hash_equals((string)$verifyToken, (string)$token)) {
                $this->output
                    ->set_content_type('text/plain')
                    ->set_status_header(200)
                    ->set_output((string)$challenge);
                return;
            }

            $this->output
                ->set_content_type('application/json')
                ->set_status_header(403)
                ->set_output(json_encode(array(
                    'status' => false,
                    'msg' => 'Token verifikasi WhatsApp Meta tidak valid.'
                )));
            return;
        }

        if ($method === 'OPTIONS') {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(array('status' => true)));
            return;
        }

        if ($method !== 'POST') {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(405)
                ->set_output(json_encode(array(
                    'status' => false,
                    'msg' => 'Method tidak didukung.'
                )));
            return;
        }

        header('Content-Type: application/json; charset=utf-8');

        $rawInput = file_get_contents('php://input');
        $payload = json_decode($rawInput, true);
        if (!is_array($payload)) {
            $payload = array();
        }

        $headers = $this->meta_whatsapp_request_headers();
        $now = DATE("Y-m-d H:i:s");
        $inserted = 0;

        $entries = isset($payload['entry']) && is_array($payload['entry']) ? $payload['entry'] : array();
        foreach ($entries as $entry) {
            $wabaId = isset($entry['id']) ? (string)$entry['id'] : '';
            $changes = isset($entry['changes']) && is_array($entry['changes']) ? $entry['changes'] : array();

            foreach ($changes as $change) {
                $field = isset($change['field']) ? (string)$change['field'] : '';
                $value = isset($change['value']) && is_array($change['value']) ? $change['value'] : array();
                $metadata = isset($value['metadata']) && is_array($value['metadata']) ? $value['metadata'] : array();
                $phoneNumberId = isset($metadata['phone_number_id']) ? (string)$metadata['phone_number_id'] : $wabaId;
                $displayPhoneNumber = isset($metadata['display_phone_number']) ? (string)$metadata['display_phone_number'] : '';
                $contacts = $this->meta_whatsapp_contacts_by_wa_id(isset($value['contacts']) ? $value['contacts'] : array());

                $messages = isset($value['messages']) && is_array($value['messages']) ? $value['messages'] : array();
                foreach ($messages as $message) {
                    $senderId = isset($message['from']) ? (string)$message['from'] : '';
                    $contact = isset($contacts[$senderId]) ? $contacts[$senderId] : array();
                    $senderName = isset($contact['name']) ? (string)$contact['name'] : '';
                    $messageId = isset($message['id']) ? (string)$message['id'] : '';
                    $timestamp = isset($message['timestamp']) ? (string)$message['timestamp'] : '';
                    $messageType = isset($message['type']) ? (string)$message['type'] : 'unknown';

                    $row = $this->meta_whatsapp_base_webhook_row($rawInput, $_GET, $_POST, $headers, $now);
                    $row['shop_id'] = $phoneNumberId;
                    $row['order_id'] = $messageId;
                    $row['hit_at'] = $timestamp !== '' ? DATE("Y-m-d H:i:s", (int)$timestamp) : $now;
                    $row['type'] = 'message';
                    $row['message_id'] = $messageId;
                    $row['conversation_id'] = $senderId !== '' ? $senderId : $messageId;
                    $row['sender_id'] = $senderId;
                    $row['sender_name'] = $senderName;
                    $row['receiver_id'] = $phoneNumberId;
                    $row['receiver_name'] = $displayPhoneNumber;
                    $row['message_type'] = $messageType;
                    $row['chat_text'] = $this->meta_whatsapp_message_text($message);
                    $row['raw_created_timestamp'] = $timestamp;
                    $row['key'] = isset($headers['X-Hub-Signature-256']) ? (string)$headers['X-Hub-Signature-256'] : '';

                    if ($this->db->insert('webhook_chat', $row)) {
                        $inserted++;
                    }
                }

                $statuses = isset($value['statuses']) && is_array($value['statuses']) ? $value['statuses'] : array();
                foreach ($statuses as $status) {
                    $statusId = isset($status['id']) ? (string)$status['id'] : '';
                    $recipientId = isset($status['recipient_id']) ? (string)$status['recipient_id'] : '';
                    $timestamp = isset($status['timestamp']) ? (string)$status['timestamp'] : '';
                    $statusText = isset($status['status']) ? (string)$status['status'] : 'status';
                    $conversationId = '';
                    if (isset($status['conversation']['id'])) {
                        $conversationId = (string)$status['conversation']['id'];
                    }

                    $row = $this->meta_whatsapp_base_webhook_row($rawInput, $_GET, $_POST, $headers, $now);
                    $row['shop_id'] = $phoneNumberId;
                    $row['order_id'] = $statusId;
                    $row['hit_at'] = $timestamp !== '' ? DATE("Y-m-d H:i:s", (int)$timestamp) : $now;
                    $row['type'] = 'status';
                    $row['message_id'] = $statusId;
                    $row['conversation_id'] = $conversationId !== '' ? $conversationId : $recipientId;
                    $row['sender_id'] = $phoneNumberId;
                    $row['sender_name'] = $displayPhoneNumber;
                    $row['receiver_id'] = $recipientId;
                    $row['receiver_name'] = $recipientId;
                    $row['message_type'] = $statusText;
                    $row['chat_text'] = 'WhatsApp message status: ' . $statusText;
                    $row['raw_created_timestamp'] = $timestamp;
                    $row['key'] = isset($headers['X-Hub-Signature-256']) ? (string)$headers['X-Hub-Signature-256'] : '';

                    if ($this->db->insert('webhook_chat', $row)) {
                        $inserted++;
                    }
                }

                if (empty($messages) && empty($statuses)) {
                    $row = $this->meta_whatsapp_base_webhook_row($rawInput, $_GET, $_POST, $headers, $now);
                    $row['shop_id'] = $phoneNumberId;
                    $row['type'] = $field !== '' ? $field : 'event';
                    $row['chat_text'] = 'WhatsApp webhook event received.';
                    $row['receiver_id'] = $phoneNumberId;
                    $row['receiver_name'] = $displayPhoneNumber;
                    $row['key'] = isset($headers['X-Hub-Signature-256']) ? (string)$headers['X-Hub-Signature-256'] : '';

                    if ($this->db->insert('webhook_chat', $row)) {
                        $inserted++;
                    }
                }
            }
        }

        if (empty($entries)) {
            $row = $this->meta_whatsapp_base_webhook_row($rawInput, $_GET, $_POST, $headers, $now);
            $row['type'] = 'event';
            $row['chat_text'] = 'WhatsApp webhook payload received.';
            $row['key'] = isset($headers['X-Hub-Signature-256']) ? (string)$headers['X-Hub-Signature-256'] : '';

            if ($this->db->insert('webhook_chat', $row)) {
                $inserted++;
            }
        }

        echo json_encode(array(
            'status' => true,
            'data' => array('inserted' => $inserted),
            'msg' => 'Webhook WhatsApp Meta berhasil diterima.'
        ), JSON_UNESCAPED_UNICODE);
    }

    private function meta_whatsapp_base_webhook_row($rawInput, $get, $post, $headers, $now)
    {
        return array(
            'marketplace' => 'META_WHATSAPP',
            'shop_id' => '',
            'order_id' => '',
            'order_date' => '',
            'hit_at' => $now,
            'brand' => '',
            'key' => '',
            'method' => isset($_SERVER['REQUEST_METHOD']) ? (string)$_SERVER['REQUEST_METHOD'] : 'POST',
            'is_live' => 'true',
            'type' => '',
            'message_id' => '',
            'conversation_id' => '',
            'sender_id' => '',
            'sender_name' => '',
            'receiver_id' => '',
            'receiver_name' => '',
            'message_type' => '',
            'chat_text' => '',
            'raw_created_timestamp' => '',
            'input' => $rawInput,
            'post' => json_encode($post, JSON_UNESCAPED_UNICODE),
            'get' => json_encode($get, JSON_UNESCAPED_UNICODE),
            'header' => json_encode($headers, JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
            'is_booking' => 0
        );
    }

    private function meta_whatsapp_request_headers()
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            return is_array($headers) ? $headers : array();
        }

        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    private function meta_whatsapp_contacts_by_wa_id($contacts)
    {
        $map = array();
        if (!is_array($contacts)) {
            return $map;
        }

        foreach ($contacts as $contact) {
            if (!is_array($contact) || empty($contact['wa_id'])) {
                continue;
            }

            $name = '';
            if (isset($contact['profile']['name'])) {
                $name = (string)$contact['profile']['name'];
            }

            $map[(string)$contact['wa_id']] = array('name' => $name);
        }

        return $map;
    }

    private function meta_whatsapp_message_text($message)
    {
        if (!is_array($message)) {
            return '';
        }

        $type = isset($message['type']) ? (string)$message['type'] : '';
        if ($type === 'text' && isset($message['text']['body'])) {
            return (string)$message['text']['body'];
        }
        if ($type === 'button') {
            if (isset($message['button']['text'])) {
                return (string)$message['button']['text'];
            }
            if (isset($message['button']['payload'])) {
                return (string)$message['button']['payload'];
            }
        }
        if ($type === 'interactive') {
            if (isset($message['interactive']['button_reply']['title'])) {
                return (string)$message['interactive']['button_reply']['title'];
            }
            if (isset($message['interactive']['list_reply']['title'])) {
                return (string)$message['interactive']['list_reply']['title'];
            }
        }
        if (isset($message[$type]['caption'])) {
            return (string)$message[$type]['caption'];
        }
        if ($type === 'location' && isset($message['location'])) {
            $location = $message['location'];
            $name = isset($location['name']) ? (string)$location['name'] : '';
            $address = isset($location['address']) ? (string)$location['address'] : '';
            $lat = isset($location['latitude']) ? (string)$location['latitude'] : '';
            $lng = isset($location['longitude']) ? (string)$location['longitude'] : '';
            return trim($name . ' ' . $address . ' ' . $lat . ',' . $lng);
        }

        return $type !== '' ? '[Customer mengirim ' . $type . ']' : '[Customer mengirim pesan]';
    }

    private function acquire_cron_lock($lock_key, $timeout = 0)
    {
        $row = $this->db->query("SELECT GET_LOCK(?, ?) AS lock_status", array($lock_key, (int)$timeout))->row_array();
        $acquired = isset($row['lock_status']) && (int)$row['lock_status'] === 1;

        if ($acquired) {
            $this->cron_lock_keys[$lock_key] = true;
            if (!$this->cron_lock_shutdown_registered) {
                $this->cron_lock_shutdown_registered = true;
                register_shutdown_function(array($this, 'release_all_cron_locks'));
            }
        }

        return $acquired;
    }

    public function release_all_cron_locks()
    {
        if (!is_array($this->cron_lock_keys) || count($this->cron_lock_keys) === 0) {
            return;
        }

        foreach (array_keys($this->cron_lock_keys) as $lock_key) {
            $this->db->query("SELECT RELEASE_LOCK(?)", array($lock_key));
        }

        $this->cron_lock_keys = array();
    }

    private function respond_cron_locked($lock_key)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'status' => false,
            'message' => 'Skip: job masih berjalan',
            'lock_key' => $lock_key
        ), true);
    }

    private function get_tiktok_bc_advertisers($access_token)
    {
        if (is_array($this->tiktok_bc_advertiser_cache)) {
            return $this->tiktok_bc_advertiser_cache;
        }

        $advertiser_url = "https://business-api.tiktok.com/open_api/v1.3/oauth2/advertiser/get/?app_id=" . urlencode(app_env('TIKTOK_BUSINESS_APP_ID')) . "&secret=" . urlencode(app_env('TIKTOK_BUSINESS_SECRET')) . "&fil";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $advertiser_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Access-Token: $access_token",
        ]);

        $advertiser_response = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            $this->tiktok_bc_advertiser_cache = array();
            return $this->tiktok_bc_advertiser_cache;
        }
        curl_close($ch);

        $advertiser_data = json_decode($advertiser_response, true);
        if (!isset($advertiser_data['data']['list']) || !is_array($advertiser_data['data']['list'])) {
            $this->tiktok_bc_advertiser_cache = array();
            return $this->tiktok_bc_advertiser_cache;
        }

        $this->tiktok_bc_advertiser_cache = $advertiser_data['data']['list'];
        return $this->tiktok_bc_advertiser_cache;
    }

    private function get_tiktok_bc_advertiser_ids_from_db($end_date_ymd)
    {
        $advertiser_ids = array();

        if ($end_date_ymd !== '') {
            $rows = $this->db->select('DISTINCT advertiser_id', false)
                ->where('date', $end_date_ymd)
                ->where('advertiser_id IS NOT NULL', null, false)
                ->where('advertiser_id <>', '')
                ->get('tiktok_ads_data')
                ->result_array();

            foreach ($rows as $row) {
                $advertiser_ids[] = $row['advertiser_id'];
            }
        }

        if (count($advertiser_ids) > 0) {
            return array_values(array_unique($advertiser_ids));
        }

        $latest = $this->db->select('MAX(date) as max_date', false)
            ->get('tiktok_ads_data')
            ->row_array();

        $max_date = isset($latest['max_date']) ? $latest['max_date'] : '';
        if ($max_date === '' || $max_date === null) {
            return array();
        }

        $rows = $this->db->select('DISTINCT advertiser_id', false)
            ->where('date', $max_date)
            ->where('advertiser_id IS NOT NULL', null, false)
            ->where('advertiser_id <>', '')
            ->get('tiktok_ads_data')
            ->result_array();

        foreach ($rows as $row) {
            $advertiser_ids[] = $row['advertiser_id'];
        }

        return array_values(array_unique($advertiser_ids));
    }

    private function get_tiktok_bc_advertiser_info_map($access_token, $advertiser_ids)
    {
        $result = array();
        if (!is_array($advertiser_ids) || count($advertiser_ids) == 0) {
            return $result;
        }

        $advertiser_ids = array_values(array_unique(array_filter(array_map('strval', $advertiser_ids))));
        $chunks = array_chunk($advertiser_ids, 100);

        foreach ($chunks as $id_chunk) {
            $advertiser_ids_json = json_encode($id_chunk);
            $info_url = "https://business-api.tiktok.com/open_api/v1.3/advertiser/info/?advertiser_ids=" . urlencode($advertiser_ids_json);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $info_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Access-Token: $access_token",
            ]);

            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($response === false || $curl_error) {
                continue;
            }

            $decoded = json_decode($response, true);
            if (!is_array($decoded) || !isset($decoded['code']) || (int)$decoded['code'] !== 0) {
                continue;
            }

            $list = isset($decoded['data']['list']) && is_array($decoded['data']['list']) ? $decoded['data']['list'] : array();
            foreach ($list as $row) {
                $advertiser_id = isset($row['advertiser_id']) ? trim((string)$row['advertiser_id']) : '';
                if ($advertiser_id === '') {
                    continue;
                }
                $result[$advertiser_id] = $row;
            }
        }

        return $result;
    }

    function sync_tiktok_bc_advertisers()
    {
        header('Content-Type: application/json; charset=utf-8');
        $dt = $_GET;
        $is_debug = isset($dt['debug']) && in_array(strtolower(trim((string)$dt['debug'])), ['1', 'true', 'yes', 'on'], true);
        $debug_payload = array(
            'advertiser_get' => null,
            'advertiser_info_chunks' => array()
        );

        $table_name = 'tiktok_bc_advertisers';
        if (!$this->db->table_exists($table_name)) {
            $resp = [
                'status' => false,
                'message' => "Table {$table_name} belum tersedia"
            ];
            if ($is_debug) {
                $resp['debug'] = $debug_payload;
            }
            echo json_encode($resp, JSON_PRETTY_PRINT);
            return;
        }

        $accessToken = app_env('TIKTOK_BUSINESS_ACCESS_TOKEN');
        $advertiser_list = array();
        if ($is_debug) {
            $advertiser_url = "https://business-api.tiktok.com/open_api/v1.3/oauth2/advertiser/get/?app_id=" . urlencode(app_env('TIKTOK_BUSINESS_APP_ID')) . "&secret=" . urlencode(app_env('TIKTOK_BUSINESS_SECRET')) . "&fil";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $advertiser_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Access-Token: {$accessToken}",
            ]);

            $advertiser_response = curl_exec($ch);
            $advertiser_curl_error = curl_error($ch);
            $advertiser_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $advertiser_decoded = is_string($advertiser_response) ? json_decode($advertiser_response, true) : null;
            $debug_payload['advertiser_get'] = array(
                'url' => $advertiser_url,
                'http_code' => $advertiser_http_code,
                'curl_error' => $advertiser_curl_error,
                'response_raw' => $advertiser_response,
                'response_json' => $advertiser_decoded
            );

            if ($advertiser_response !== false && $advertiser_curl_error === '' && is_array($advertiser_decoded)) {
                $advertiser_list = isset($advertiser_decoded['data']['list']) && is_array($advertiser_decoded['data']['list']) ? $advertiser_decoded['data']['list'] : array();
            }
        } else {
            $advertiser_list = $this->get_tiktok_bc_advertisers($accessToken);
        }

        if (!is_array($advertiser_list) || count($advertiser_list) == 0) {
            $resp = [
                'status' => false,
                'message' => 'Gagal mengambil advertiser dari TikTok BC'
            ];
            if ($is_debug) {
                $resp['debug'] = $debug_payload;
            }
            echo json_encode($resp, JSON_PRETTY_PRINT);
            return;
        }

        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $saved_success = 0;
        $saved_failed = 0;
        $active_ids = array();
        $inactive_count = 0;
        $removed_confirm_fail = 0;

        $advertiser_ids = array();
        foreach ($advertiser_list as $advertiser) {
            $advertiser_id = isset($advertiser['advertiser_id']) ? trim((string)$advertiser['advertiser_id']) : '';
            if ($advertiser_id !== '') {
                $advertiser_ids[] = $advertiser_id;
            }
        }
        $advertiser_ids = array_values(array_unique($advertiser_ids));

        if ($is_debug) {
            $info_map = array();
            $id_chunks = array_chunk($advertiser_ids, 100);
            foreach ($id_chunks as $chunk_index => $id_chunk) {
                $advertiser_ids_json = json_encode($id_chunk);
                $info_url = "https://business-api.tiktok.com/open_api/v1.3/advertiser/info/?advertiser_ids=" . urlencode($advertiser_ids_json);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $info_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Access-Token: {$accessToken}",
                ]);

                $info_response = curl_exec($ch);
                $info_curl_error = curl_error($ch);
                $info_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $info_decoded = is_string($info_response) ? json_decode($info_response, true) : null;
                $debug_payload['advertiser_info_chunks'][] = array(
                    'chunk_no' => $chunk_index + 1,
                    'advertiser_id_count' => count($id_chunk),
                    'url' => $info_url,
                    'http_code' => $info_http_code,
                    'curl_error' => $info_curl_error,
                    'response_raw' => $info_response,
                    'response_json' => $info_decoded
                );

                if ($info_response === false || $info_curl_error) {
                    continue;
                }
                if (!is_array($info_decoded) || !isset($info_decoded['code']) || (int)$info_decoded['code'] !== 0) {
                    continue;
                }

                $list = isset($info_decoded['data']['list']) && is_array($info_decoded['data']['list']) ? $info_decoded['data']['list'] : array();
                foreach ($list as $row) {
                    $advertiser_id = isset($row['advertiser_id']) ? trim((string)$row['advertiser_id']) : '';
                    if ($advertiser_id === '') {
                        continue;
                    }
                    $info_map[$advertiser_id] = $row;
                }
            }
        } else {
            $info_map = $this->get_tiktok_bc_advertiser_info_map($accessToken, $advertiser_ids);
        }
        if (count($info_map) == 0) {
            $resp = [
                'status' => false,
                'message' => 'Gagal mengambil advertiser info dari TikTok BC'
            ];
            if ($is_debug) {
                $resp['debug'] = $debug_payload;
            }
            echo json_encode($resp, JSON_PRETTY_PRINT);
            return;
        }

        // Reset active flags first; only active accounts from advertiser/info will be reactivated.
        $this->db->where('status_active', 1);
        $this->db->update($table_name, array(
            'status_active' => 0,
            'updated_at' => $now
        ));
        $inactive_count = $this->db->affected_rows();

        // Pastikan akun rejected tidak tersimpan di tabel advertiser master.
        $this->db->where('advertiser_status', 'STATUS_CONFIRM_FAIL');
        $this->db->delete($table_name);
        $removed_confirm_fail = $this->db->affected_rows();

        $sql_upsert = "INSERT INTO {$table_name}
            (advertiser_id, advertiser_name, advertiser_status, advertiser_role, owner_bc_id, timezone, currency, country, company, status_active, sync_date, last_synced_at, raw_info, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                advertiser_name = VALUES(advertiser_name),
                advertiser_status = VALUES(advertiser_status),
                advertiser_role = VALUES(advertiser_role),
                owner_bc_id = VALUES(owner_bc_id),
                timezone = VALUES(timezone),
                currency = VALUES(currency),
                country = VALUES(country),
                company = VALUES(company),
                status_active = 1,
                sync_date = VALUES(sync_date),
                last_synced_at = VALUES(last_synced_at),
                raw_info = VALUES(raw_info),
                updated_at = NOW()";

        foreach ($info_map as $advertiser_id => $info_row) {
            $account_status = isset($info_row['status']) ? (string)$info_row['status'] : '';
            if ($account_status !== 'STATUS_ENABLE') {
                continue;
            }

            $advertiser_name = isset($info_row['name']) ? trim((string)$info_row['name']) : null;
            if ($advertiser_name === '' || $advertiser_name === null) {
                $advertiser_name = isset($info_row['advertiser_name']) ? trim((string)$info_row['advertiser_name']) : null;
            }
            $run = $this->db->query($sql_upsert, array(
                $advertiser_id,
                $advertiser_name,
                $account_status,
                isset($info_row['role']) ? (string)$info_row['role'] : null,
                isset($info_row['owner_bc_id']) ? (string)$info_row['owner_bc_id'] : null,
                isset($info_row['timezone']) ? (string)$info_row['timezone'] : null,
                isset($info_row['currency']) ? (string)$info_row['currency'] : null,
                isset($info_row['country']) ? (string)$info_row['country'] : null,
                isset($info_row['company']) ? (string)$info_row['company'] : null,
                $today,
                $now,
                json_encode($info_row)
            ));

            if ($run) {
                $saved_success++;
                $active_ids[] = $advertiser_id;
            } else {
                $saved_failed++;
            }
        }

        $active_ids = array_values(array_unique($active_ids));

        $resp = [
            'status' => true,
            'message' => 'Sync advertiser TikTok BC selesai',
            'sync_date' => $today,
            'saved_success' => $saved_success,
            'saved_failed' => $saved_failed,
            'deactivated' => $inactive_count,
            'removed_confirm_fail' => $removed_confirm_fail,
            'total_active' => count($active_ids)
        ];
        if ($is_debug) {
            $resp['debug'] = $debug_payload;
        }

        echo json_encode($resp, JSON_PRETTY_PRINT);
    }

    function reset_tiktok_bc_advertisers()
    {
        header('Content-Type: application/json; charset=utf-8');

        $table_name = 'tiktok_bc_advertisers';
        if (!$this->db->table_exists($table_name)) {
            echo json_encode([
                'status' => false,
                'message' => "Table {$table_name} belum tersedia"
            ], JSON_PRETTY_PRINT);
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->where('status_active', 1);
        $this->db->update($table_name, array(
            'status_active' => 0,
            'updated_at' => $now
        ));

        echo json_encode([
            'status' => true,
            'message' => 'Reset advertiser TikTok BC selesai',
            'reset_at' => $now,
            'affected_rows' => $this->db->affected_rows()
        ], JSON_PRETTY_PRINT);
    }

    function marketplace_ads()
    {
        $lock_key = 'cron:ads:marketplace_ads';
        if (!$this->acquire_cron_lock($lock_key, 0)) {
            $this->respond_cron_locked($lock_key);
            return;
        }

        $dt = $_GET;
        $marketplace = strtoupper(isset($dt['marketplace']) ? $dt['marketplace'] : '');
        $shop_id = isset($dt['shop_id']) ? $dt['shop_id'] : '';
        $platform = isset($dt['platform']) ? $dt['platform'] : '';
        $qry = "";
    
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }
        if ($marketplace) {
            $qry .= " AND opt = '$marketplace' ";
        }
    
        $data = $this->mymodel->selectWithQuery("SELECT * FROM marketplace_config WHERE status = 'Aktif' $qry");
    
        $current_tax = $this->current_tax;
    
        $get_start = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] : date('Y-m-d');
        $get_until = isset($_GET['until_date']) && $_GET['until_date'] !== '' ? $_GET['until_date'] : date('Y-m-d');
    
        $toYmd = function ($s) {
            if (!$s) return date('Y-m-d');
            $d = DateTime::createFromFormat('Y-m-d', $s);
            if ($d && $d->format('Y-m-d') === $s) return $s;
            $d = DateTime::createFromFormat('d-m-Y', $s);
            if ($d) return $d->format('Y-m-d');
            return date('Y-m-d');
        };
    
        $toDmy = function ($s) {
            $d = DateTime::createFromFormat('Y-m-d', $s);
            return $d ? $d->format('d-m-Y') : date('d-m-Y');
        };
    
        $start_date_ymd = $toYmd($get_start);
        $end_date_ymd   = $toYmd($get_until);
        $start_date_dmy = $toDmy($start_date_ymd);
        $end_date_dmy   = $toDmy($end_date_ymd);
    
        foreach ($data as $k => $v) {
            if ($v['opt'] == "LAZADA") {
                $marketplace = 'LAZADA';
                $config = json_decode($v['val'], true);
    
                $access_token = $config['access_token'];
                $shop_id = $v['shop_id'];
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $url = 'https://api.lazada.co.id/rest';
    
                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/sponsor/solutions/report/getDiscoveryReportCampaign', 'GET');
    
                $request->addApiParam('startDate', $start_date_ymd);
                $request->addApiParam('endDate', $end_date_ymd);
                $request->addApiParam('pageNo', '1');
                $request->addApiParam('pageSize', '100');
    
                $response = $c->execute($request, $access_token);
                $responseData = json_decode($response, true);
    
                if (isset($responseData['result']['result']) && is_array($responseData['result']['result']) && count($responseData['result']['result']) > 0) {
                    foreach ($responseData['result']['result'] as $data) {
                        $dt = array(
                            'date' => $start_date_ymd,
                            'shop_id' => $shop_id,
                            'ctr' => $data['ctr'],
                            'campaignType' => isset($data['campaignType']) ? $data['campaignType'] : 0,
                            'campaignId' => $data['campaignId'],
                            'storeRevenue' => isset($data['storeRevenue']) ? $data['storeRevenue'] : 0,
                            'storeCvr' => isset($data['storeCvr']) ? $data['storeCvr'] : 0,
                            'storeA2c' => isset($data['storeA2c']) ? $data['storeA2c'] : 0,
                            'storeOrders' => isset($data['storeOrders']) ? $data['storeOrders'] : 0,
                            'productUnitSold' => isset($data['productUnitSold']) ? $data['productUnitSold'] : 0,
                            'impressions' => $data['impressions'],
                            'productCvr' => isset($data['productCvr']) ? $data['productCvr'] : 0,
                            'productOrders' => $data['productOrders'],
                            'storeRoi' => $data['storeRoi'],
                            'cpc' => isset($data['cpc']) ? $data['cpc'] : 0,
                            'spend' => $data['spend'],
                            'clicks' => $data['clicks'],
                            'productRevenue' => $data['productRevenue'],
                            'storeUnitSold' => isset($data['storeUnitSold']) ? $data['storeUnitSold'] : 0,
                            'campaignName' => $data['campaignName'],
                            'productType' => isset($data['productType']) ? $data['productType'] : 'ALL',
                            'dayBudget' => isset($data['dayBudget']) ? $data['dayBudget'] : 0,
                            'productA2c' => isset($data['productA2c']) ? $data['productA2c'] : 0,
                            'created_at' => date('Y-m-d H:i:s')
                        );
                        $sql_check = "SELECT COUNT(*) as count FROM lazada_ads_data WHERE date = ? AND shop_id = ? AND campaignId = ?";
                        $dataCount = $this->db->query($sql_check, [$dt['date'], $dt['shop_id'], $dt['campaignId']])->row()->count;
                        if ($dataCount == 0) {
                            $sql_insert = "INSERT INTO lazada_ads_data (date, shop_id, ctr, campaignType, campaignId, storeRevenue, storeCvr, storeA2c, storeOrders, productUnitSold, impressions, productCvr, productOrders, storeRoi, cpc, spend, clicks, productRevenue, storeUnitSold, campaignName, productType, dayBudget, productA2c, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $this->db->query($sql_insert, [$dt['date'], $dt['shop_id'], $dt['ctr'], $dt['campaignType'], $dt['campaignId'], $dt['storeRevenue'], $dt['storeCvr'], $dt['storeA2c'], $dt['storeOrders'], $dt['productUnitSold'], $dt['impressions'], $dt['productCvr'], $dt['productOrders'], $dt['storeRoi'], $dt['cpc'], $dt['spend'] + $dt['spend'] * $current_tax, $dt['clicks'], $dt['productRevenue'], $dt['storeUnitSold'], $dt['campaignName'], $dt['productType'], $dt['dayBudget'], $dt['productA2c'], $dt['created_at']]);
                        } else {
                            $sql_update = "UPDATE lazada_ads_data SET ctr = ?, campaignType = ?, storeRevenue = ?, storeCvr = ?, storeA2c = ?, storeOrders = ?, productUnitSold = ?, impressions = ?, productCvr = ?, productOrders = ?, storeRoi = ?, cpc = ?, spend = ?, clicks = ?, productRevenue = ?, storeUnitSold = ?, campaignName = ?, productType = ?, dayBudget = ?, productA2c = ?, created_at = ? WHERE date = ? AND shop_id = ? AND campaignId = ?";
                            $this->db->query($sql_update, [$dt['ctr'], $dt['campaignType'], $dt['storeRevenue'], $dt['storeCvr'], $dt['storeA2c'], $dt['storeOrders'], $dt['productUnitSold'], $dt['impressions'], $dt['productCvr'], $dt['productOrders'], $dt['storeRoi'], $dt['cpc'], $dt['spend'] + $dt['spend'] * $current_tax, $dt['clicks'], $dt['productRevenue'], $dt['storeUnitSold'], $dt['campaignName'], $dt['productType'], $dt['dayBudget'], $dt['productA2c'], $dt['created_at'], $dt['date'], $dt['shop_id'], $dt['campaignId']]);
                        }
                    }
                } else {
                    $dt = array(
                        'date' => $start_date_ymd, 
                        'ctr' => 0,
                        'shop_id' => $shop_id,
                        'campaignType' => 0,
                        'campaignId' => 0,
                        'storeRevenue' => 0,
                        'storeCvr' => 0,
                        'storeA2c' => 0,
                        'storeOrders' => 0,
                        'productUnitSold' => 0,
                        'impressions' => 0,
                        'productCvr' => 0,
                        'productOrders' => 0,
                        'storeRoi' => 0,
                        'cpc' => 0,
                        'spend' => 0,
                        'clicks' => 0,
                        'productRevenue' => 0,
                        'storeUnitSold' => 0,
                        'campaignName' => 'Tidak Ada Kampanye Aktif',
                        'productType' => 'ALL',
                        'dayBudget' => 0,
                        'productA2c' => 0,
                        'created_at' => date('Y-m-d H:i:s')
                    );
    
                    $sql_check = "SELECT COUNT(*) as count FROM lazada_ads_data WHERE shop_id = ? AND date = ? AND campaignId = ?";
                    $dataCount = $this->db->query($sql_check, [$dt['shop_id'], $start_date_ymd, $dt['campaignId']])->row()->count;
    
                    if ($dataCount == 0) {
                        $sql_insert = "INSERT INTO lazada_ads_data (date, shop_id, ctr, campaignType, campaignId, storeRevenue, storeCvr, storeA2c, storeOrders, productUnitSold, impressions, productCvr, productOrders, storeRoi, cpc, spend, clicks, productRevenue, storeUnitSold, campaignName, productType, dayBudget, productA2c, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $this->db->query($sql_insert, [
                            $dt['date'],
                            $dt['shop_id'],
                            $dt['ctr'],
                            $dt['campaignType'],
                            $dt['campaignId'],
                            $dt['storeRevenue'],
                            $dt['storeCvr'],
                            $dt['storeA2c'],
                            $dt['storeOrders'],
                            $dt['productUnitSold'],
                            $dt['impressions'],
                            $dt['productCvr'],
                            $dt['productOrders'],
                            $dt['storeRoi'],
                            $dt['cpc'],
                            $dt['spend'] + $dt['spend'] * $current_tax,
                            $dt['clicks'],
                            $dt['productRevenue'],
                            $dt['storeUnitSold'],
                            $dt['campaignName'],
                            $dt['productType'],
                            $dt['dayBudget'],
                            $dt['productA2c'],
                            $dt['created_at']
                        ]);
                    } else {
                        $sql_update = "UPDATE lazada_ads_data SET ctr = ?, campaignType = ?, storeRevenue = ?, storeCvr = ?, storeA2c = ?, storeOrders = ?, productUnitSold = ?, impressions = ?, productCvr = ?, productOrders = ?, storeRoi = ?, cpc = ?, spend = ?, clicks = ?, productRevenue = ?, storeUnitSold = ?, campaignName = ?, productType = ?, dayBudget = ?, productA2c = ?, created_at = ? WHERE shop_id = ? AND date = ? AND campaignId = ?";
                        $this->db->query($sql_update, [$dt['ctr'], $dt['campaignType'], $dt['storeRevenue'], $dt['storeCvr'], $dt['storeA2c'], $dt['storeOrders'], $dt['productUnitSold'], $dt['impressions'], $dt['productCvr'], $dt['productOrders'], $dt['storeRoi'], $dt['cpc'], $dt['spend'], $dt['clicks'], $dt['productRevenue'], $dt['storeUnitSold'], $dt['campaignName'], $dt['productType'], $dt['dayBudget'], $dt['productA2c'], $dt['created_at'], $dt['shop_id'], $dt['date'], $dt['campaignId']]);
                    }
                }
                $success = true;
            } else if ($v['opt'] == "SHOPEE") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $access_token = $config['access_token'];
                $host = 'https://partner.shopeemobile.com';
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $shop_id = $v['shop_id'];
                $shop_name = $v['shop_name'];
    
                $path = "/api/v2/ads/get_all_cpc_ads_daily_performance";
                $timest = time();
                $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                $sign = hash_hmac('sha256', $baseString, $partner_key);

                $start_date = $start_date_dmy;
                $end_date   = $end_date_dmy;

                $existing_shopee = array();
                $existing_rows = $this->db->select('date')
                    ->where('shop_id', $shop_id)
                    ->where('date >=', $start_date_ymd)
                    ->where('date <=', $end_date_ymd)
                    ->get('shopee_ads_data')
                    ->result_array();

                foreach ($existing_rows as $existing_row) {
                    $existing_shopee[$existing_row['date']] = true;
                }
    
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $host . $path . '?partner_id=' . $partner_id . '&timestamp=' . $timest . '&shop_id=' . $shop_id . '&access_token=' . $access_token . '&end_date=' . $end_date . '&sign=' . $sign . '&start_date=' . $start_date,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                ));
    
                $response = curl_exec($curl);
                curl_close($curl);
    
                $responseData = json_decode($response, true);
    
                if (isset($responseData['response'])) {
                    foreach ($responseData['response'] as $data) {
                        $date = $data['date'];
    
                        $dateObj = DateTime::createFromFormat('d-m-Y', $date);
                        if ($dateObj) {
                            $date = $dateObj->format('Y-m-d');
                        }
    
                        $impression = $data['impression'];
                        $clicks = $data['clicks'];
                        $ctr = $data['ctr'];
                        $broad_order = $data['broad_order'];
                        $broad_conversions = $data['broad_conversions'];
                        $broad_item_sold = $data['broad_item_sold'];
                        $broad_gmv = $data['broad_gmv'];
                        $expense = $data['expense'];
                        $expense_after_tax = $data['expense'] + $data['expense'] * $current_tax;
                        $broad_roas = $data['broad_roas'];
    
                        $dt = array(
                            'date' => $date,
                            'shop_id' => $shop_id,
                            'impression' => $impression,
                            'clicks' => $clicks,
                            'ctr' => $ctr,
                            'broad_order' => $broad_order,
                            'broad_conversions' => $broad_conversions,
                            'broad_item_sold' => $broad_item_sold,
                            'broad_gmv' => $broad_gmv,
                            'tax' => $current_tax,
                            'expense' => $expense,
                            'expense_after_tax' => $expense_after_tax,
                            'broad_roas' => $broad_roas
                        );
    
                        if (!isset($existing_shopee[$date])) {
                            $this->db->insert('shopee_ads_data', $dt);
                            $existing_shopee[$date] = true;
                        } else {
                            $this->db->where('date', $date);
                            $this->db->where('shop_id', $shop_id);
                            $this->db->update('shopee_ads_data', $dt);
                        }
                    }
    
                    $success = true;
                }
            } else if ($v['opt'] == "META") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $access_token = isset($config['refresh_token']) ? $config['refresh_token'] : '';
                if ($access_token == '') {
                    continue;
                }
    
                $account_ids =  $this->mymodel->selectWithQuery("SELECT account_id FROM ads_meta_account WHERE status = 1 ORDER BY created_at DESC");
                
                $since = $start_date_ymd;
                $until = $end_date_ymd;

                $existing_meta = array();
                $existing_meta_rows = $this->db->select('date, account_id, campaign_id')
                    ->where('date >=', $since)
                    ->where('date <=', $until)
                    ->get('meta_ads_data')
                    ->result_array();

                foreach ($existing_meta_rows as $existing_meta_row) {
                    $existing_meta[$existing_meta_row['date'] . '|' . $existing_meta_row['account_id'] . '|' . $existing_meta_row['campaign_id']] = true;
                }
    
                foreach ($account_ids as $account_id) {
                    $id = $account_id['account_id'];

                    $paging_url = "https://graph.facebook.com/v21.0/act_$id/insights?" . http_build_query(array(
                        'fields' => 'campaign_id,campaign_name,ctr,impressions,clicks,spend,catalog_segment_value',
                        'time_range' => json_encode(array('since' => $since, 'until' => $until)),
                        'level' => 'campaign',
                        'access_token' => $access_token,
                        'limit' => 500
                    ));

                    while ($paging_url) {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $paging_url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $insights_response = curl_exec($ch);
                        $curl_error = curl_error($ch);
                        curl_close($ch);

                        if ($curl_error) {
                            break;
                        }

                        $insightsData = json_decode($insights_response, true);
                        if (!isset($insightsData['data']) || !is_array($insightsData['data'])) {
                            break;
                        }

                        foreach ($insightsData['data'] as $dataAds) {
                            $campaign_id = isset($dataAds['campaign_id']) ? $dataAds['campaign_id'] : 0;
                            if (!$campaign_id) {
                                continue;
                            }

                            $campaign_name = isset($dataAds['campaign_name']) ? $dataAds['campaign_name'] : '';
                            $ctr = isset($dataAds['ctr']) ? $dataAds['ctr'] : 0;
                            $impressions = isset($dataAds['impressions']) ? $dataAds['impressions'] : 0;
                            $spend = isset($dataAds['spend']) ? $dataAds['spend'] : 0;
                            $spend_after_tax = $spend + ($spend * $current_tax);
                            $clicks = isset($dataAds['clicks']) ? $dataAds['clicks'] : 0;
                            $purchase = 0;
                            $add_to_cart = 0;

                            if (!empty($dataAds['catalog_segment_value']) && is_array($dataAds['catalog_segment_value'])) {
                                foreach ($dataAds['catalog_segment_value'] as $segment) {
                                    if (isset($segment['action_type']) && $segment['action_type'] == 'omni_purchase') {
                                        $purchase = $segment['value'];
                                    }
                                    if (isset($segment['action_type']) && $segment['action_type'] == 'omni_add_to_cart') {
                                        $add_to_cart = $segment['value'];
                                    }
                                }
                            }

                            $date_start = isset($dataAds['date_start']) ? $dataAds['date_start'] : $since;
                            $date_stop = isset($dataAds['date_stop']) ? $dataAds['date_stop'] : $until;

                            $dt = array(
                                'date' => $date_start,
                                'account_id' => $id,
                                'campaign_id' => $campaign_id,
                                'campaign_name' => $campaign_name,
                                'ctr' => $ctr,
                                'impressions' => $impressions,
                                'tax' => $current_tax,
                                'spend' => $spend,
                                'spend_after_tax' => $spend_after_tax,
                                'clicks' => $clicks,
                                'purchases' => $purchase,
                                'add_to_cart' => $add_to_cart,
                                'date_start' => $date_start,
                                'date_stop' => $date_stop
                            );

                            $meta_key = $date_start . '|' . $id . '|' . $campaign_id;
                            if (!isset($existing_meta[$meta_key])) {
                                $this->db->insert('meta_ads_data', $dt);
                                $existing_meta[$meta_key] = true;
                            } else {
                                $this->db->where('date', $date_start);
                                $this->db->where('account_id', $id);
                                $this->db->where('campaign_id', $campaign_id);
                                $this->db->update('meta_ads_data', $dt);
                            }
                        }

                        if (isset($insightsData['paging']['next']) && $insightsData['paging']['next'] !== '') {
                            $paging_url = $insightsData['paging']['next'];
                        } else {
                            $paging_url = null;
                        }
                    }
                }
                $success = true;
            } else if ($v['opt'] == "TIKTOKBC") {
                $advertiser_list = $this->get_tiktok_bc_advertisers(app_env('TIKTOK_BUSINESS_ACCESS_TOKEN'));
                if (empty($advertiser_list)) {
                    echo "No advertiser data available.\n";
                    exit;
                }

                $exchange_rate = 1;
                $exchange_url = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json";
                $ch_exchange = curl_init($exchange_url);
                curl_setopt($ch_exchange, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_exchange, CURLOPT_HTTPHEADER, ['Accept: application/json']);
                $exchange_response = curl_exec($ch_exchange);
                if (!curl_errno($ch_exchange)) {
                    $exchange_data = json_decode($exchange_response, true);
                    if (isset($exchange_data['usd']['idr'])) {
                        $exchange_rate = $exchange_data['usd']['idr'];
                    }
                }
                curl_close($ch_exchange);

                $existing_tiktok = array();
                $existing_tiktok_rows = $this->db->select('advertiser_id')
                    ->where('date', $end_date_ymd)
                    ->get('tiktok_ads_data')
                    ->result_array();

                foreach ($existing_tiktok_rows as $existing_tiktok_row) {
                    $existing_tiktok[$existing_tiktok_row['advertiser_id']] = true;
                }
    
                foreach ($advertiser_list as $advertiser) {
                    $advertiser_id = $advertiser['advertiser_id'];
                    $advertiser_name = $advertiser['advertiser_name'];
    
                    $report_url = "https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/";
    
                    $report_data = [
                        "advertiser_id" => $advertiser_id,
                        "report_type" => "BASIC",
                        "data_level" => "AUCTION_ADVERTISER",
                        "dimensions" => json_encode(["advertiser_id"]),
                        "metrics" => json_encode([
                            "impressions",
                            "clicks",
                            "spend",
                            "currency",
                            "ctr",
                            "cpc",
                            "cpm",
                            "onsite_shopping_roas",
                            "onsite_shopping",
                            "cost_per_onsite_shopping",
                            "onsite_shopping_rate",
                            "value_per_onsite_shopping",
                            "total_onsite_shopping_value",
                            "onsite_initiate_checkout_count",
                            "cost_per_onsite_initiate_checkout_count",
                            "onsite_initiate_checkout_count_rate",
                            "value_per_onsite_initiate_checkout_count",
                            "total_onsite_initiate_checkout_count_value",
                            "onsite_on_web_detail",
                            "cost_per_onsite_on_web_detail",
                            "onsite_on_web_detail_rate",
                            "value_per_onsite_on_web_detail",
                            "total_onsite_on_web_detail_value",
                            "onsite_on_web_cart",
                            "cost_per_onsite_on_web_cart",
                            "onsite_on_web_cart_rate",
                            "value_per_onsite_on_web_cart",
                            "total_onsite_on_web_cart_value",
                            "total_onsite_initiate_checkout_count_value",
                            "total_onsite_shopping_value",
                            "value_per_onsite_initiate_checkout_count",
                            "value_per_onsite_shopping",
                            "value_per_onsite_on_web_cart",
                            "value_per_onsite_on_web_detail",
                            "frequency",
                            "reach"
                        ]),
                        "start_date" => $start_date_ymd,
                        "end_date" => $end_date_ymd
                    ];
    
                    $query_string = http_build_query($report_data);
    
                    $ch = curl_init($report_url . '?' . $query_string);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Content-Type: application/json",
                        "Access-Token: " . app_env('TIKTOK_BUSINESS_ACCESS_TOKEN')
                    ]);
    
                    $report_response = curl_exec($ch);
                    
                    if (curl_errno($ch)) {
                        echo "Error: " . curl_error($ch);
                        curl_close($ch);
                        continue;
                    }
                    curl_close($ch);
    
                    $report_data = json_decode($report_response, true);
    
                    foreach ($report_data['data']['list'] as $metrics_data) {
                        $metrics = $metrics_data['metrics'] ?? [];
                        $dimensions = $metrics_data['dimensions'] ?? [];
    
                        $getMetric = function ($key, $default = 0) use ($metrics) {
                            return $metrics[$key] ?? $default;
                        };
    
                        $dt = [
                            'advertiser_id' => $dimensions['advertiser_id'] ?? null,
                            'advertiser_name' => $advertiser_name,
                            'impressions' => $getMetric('impressions'),
                            'clicks' => $getMetric('clicks'),
                            'spend' => $getMetric('spend'),
                            'spend_idr' => $getMetric('currency') == 'USD' ? ($exchange_rate * $getMetric('spend')) : $getMetric('spend'),
                            'spend_idr_after_tax' => $getMetric('currency') == 'USD' ? ($exchange_rate * $getMetric('spend')) + ($exchange_rate * $getMetric('spend') * $current_tax)  : $getMetric('spend') + ($getMetric('spend') * $current_tax),
                            'tax' => $current_tax,
                            'currency' => $getMetric('currency'),
                            'ctr' => $getMetric('ctr'),
                            'cpc' => $getMetric('cpc'),
                            'cpm' => $getMetric('cpm'),
                            'onsite_shopping_roas' => $getMetric('onsite_shopping_roas'),
                            'onsite_shopping' => $getMetric('onsite_shopping'),
                            'cost_per_onsite_shopping' => $getMetric('cost_per_onsite_shopping'),
                            'onsite_shopping_rate' => $getMetric('onsite_shopping_rate'),
                            'value_per_onsite_shopping' => $getMetric('value_per_onsite_shopping'),
                            'total_onsite_shopping_value' => $getMetric('total_onsite_shopping_value'),
                            'total_onsite_shopping_value_idr' => $getMetric('currency') == 'USD' ? $exchange_rate * $getMetric('total_onsite_shopping_value') : $getMetric('total_onsite_shopping_value'),
                            'onsite_initiate_checkout_count' => $getMetric('onsite_initiate_checkout_count'),
                            'cost_per_onsite_initiate_checkout_count' => $getMetric('cost_per_onsite_initiate_checkout_count'),
                            'onsite_initiate_checkout_count_rate' => $getMetric('onsite_initiate_checkout_count_rate'),
                            'value_per_onsite_initiate_checkout_count' => $getMetric('value_per_onsite_initiate_checkout_count'),
                            'total_onsite_initiate_checkout_count_value' => $getMetric('total_onsite_initiate_checkout_count_value'),
                            'onsite_on_web_detail' => $getMetric('onsite_on_web_detail'),
                            'cost_per_onsite_on_web_detail' => $getMetric('cost_per_onsite_on_web_detail'),
                            'onsite_on_web_detail_rate' => $getMetric('onsite_on_web_detail_rate'),
                            'value_per_onsite_on_web_detail' => $getMetric('value_per_onsite_on_web_detail'),
                            'total_onsite_on_web_detail_value' => $getMetric('total_onsite_on_web_detail_value'),
                            'onsite_on_web_cart' => $getMetric('onsite_on_web_cart'),
                            'cost_per_onsite_on_web_cart' => $getMetric('cost_per_onsite_on_web_cart'),
                            'onsite_on_web_cart_rate' => $getMetric('onsite_on_web_cart_rate'),
                            'value_per_onsite_on_web_cart' => $getMetric('value_per_onsite_on_web_cart'),
                            'total_onsite_on_web_cart_value' => $getMetric('total_onsite_on_web_cart_value'),
                            'total_onsite_on_web_cart_value_idr' => $getMetric('currency') == 'USD' ? $exchange_rate * $getMetric('total_onsite_on_web_cart_value') : $getMetric('total_onsite_on_web_cart_value'),
                            'frequency' => $getMetric('frequency'),
                            'reach' => $getMetric('reach'),
                            'date' => $end_date_ymd
                        ];

                        if (!isset($existing_tiktok[$dt['advertiser_id']])) {
                            $this->db->insert('tiktok_ads_data', $dt);
                            $existing_tiktok[$dt['advertiser_id']] = true;
                        } else {
                            $this->db->where('advertiser_id', $dt['advertiser_id'])
                                ->where('date', $dt['date'])
                                ->update('tiktok_ads_data', $dt);
                        }
                    }
                }
                $success = true;
            }
        }
    }


    function shopee_product_campaign()
    {
        header('Content-Type: application/json; charset=utf-8');
        $lock_key = 'cron:ads:shopee_product_campaign';
        if (!$this->acquire_cron_lock($lock_key, 0)) {
            $this->respond_cron_locked($lock_key);
            return;
        }

        $dt = $_GET;
        $shop_id_filter = isset($dt['shop_id']) ? trim($dt['shop_id']) : '';
        $ad_type = isset($dt['ad_type']) && $dt['ad_type'] !== '' ? strtolower($dt['ad_type']) : 'all';
        $date = isset($dt['date']) ? trim($dt['date']) : '';
        $is_debug = isset($dt['debug']) && in_array(strtolower(trim((string)$dt['debug'])), array('1', 'true', 'yes', 'on'));

        $get_start = isset($dt['start_date']) ? trim($dt['start_date']) : '';
        $get_until = isset($dt['until_date']) ? trim($dt['until_date']) : '';
        if ($date !== '') {
            $get_start = $date;
            $get_until = $date;
        }
        if ($get_until == '' && isset($dt['end_date'])) {
            $get_until = trim($dt['end_date']);
        }
        if ($get_start == '') {
            $get_start = date('Y-m-d');
        }
        if ($get_until == '') {
            $get_until = date('Y-m-d');
        }

        $toYmd = function ($s) {
            if (!$s) return date('Y-m-d');
            $d = DateTime::createFromFormat('Y-m-d', $s);
            if ($d && $d->format('Y-m-d') === $s) return $s;
            $d = DateTime::createFromFormat('d-m-Y', $s);
            if ($d) return $d->format('Y-m-d');
            return date('Y-m-d');
        };

        $toDmy = function ($s) {
            $d = DateTime::createFromFormat('Y-m-d', $s);
            return $d ? $d->format('d-m-Y') : date('d-m-Y');
        };
        $maskSensitiveUrl = function ($url) {
            $parts = parse_url($url);
            if (!$parts || !isset($parts['query'])) return $url;
            parse_str($parts['query'], $query_params);
            if (isset($query_params['access_token'])) $query_params['access_token'] = '***';
            if (isset($query_params['sign'])) $query_params['sign'] = '***';
            $base = '';
            if (isset($parts['scheme'])) $base .= $parts['scheme'] . '://';
            if (isset($parts['host'])) $base .= $parts['host'];
            if (isset($parts['port'])) $base .= ':' . $parts['port'];
            if (isset($parts['path'])) $base .= $parts['path'];
            return $base . '?' . http_build_query($query_params);
        };

        $start_date_ymd = $toYmd($get_start);
        $end_date_ymd = $toYmd($get_until);
        $start_date_dmy = $toDmy($start_date_ymd);
        $end_date_dmy = $toDmy($end_date_ymd);

        $qry = " AND opt = 'SHOPEE' ";
        if ($shop_id_filter) {
            $qry .= " AND shop_id = '$shop_id_filter' ";
        }

        $shops = $this->mymodel->selectWithQuery("SELECT * FROM marketplace_config WHERE opt = 'SHOPEE' AND status = 'Aktif' $qry");

        $host = 'https://partner.shopeemobile.com';
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $current_tax = $this->current_tax;
        $table_logs = 'shopee_ads_campaign';
        $sql_upsert = "INSERT INTO $table_logs (
            shop_id, campaign_id, metric_date, ad_type, campaign_placement, ad_name,
            impression, clicks, ctr, expense, expense_after_tax,
            broad_gmv, broad_order, broad_order_amount, broad_roi, broad_cir,
            cr, cpc, direct_order, direct_order_amount, direct_gmv, direct_roi,
            direct_cir, direct_cr, cpdc, sync_start_date, sync_end_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            ad_type = VALUES(ad_type),
            campaign_placement = VALUES(campaign_placement),
            ad_name = VALUES(ad_name),
            impression = VALUES(impression),
            clicks = VALUES(clicks),
            ctr = VALUES(ctr),
            expense = VALUES(expense),
            expense_after_tax = VALUES(expense_after_tax),
            broad_gmv = VALUES(broad_gmv),
            broad_order = VALUES(broad_order),
            broad_order_amount = VALUES(broad_order_amount),
            broad_roi = VALUES(broad_roi),
            broad_cir = VALUES(broad_cir),
            cr = VALUES(cr),
            cpc = VALUES(cpc),
            direct_order = VALUES(direct_order),
            direct_order_amount = VALUES(direct_order_amount),
            direct_gmv = VALUES(direct_gmv),
            direct_roi = VALUES(direct_roi),
            direct_cir = VALUES(direct_cir),
            direct_cr = VALUES(direct_cr),
            cpdc = VALUES(cpdc),
            sync_start_date = VALUES(sync_start_date),
            sync_end_date = VALUES(sync_end_date),
            updated_at = CURRENT_TIMESTAMP";

        $result = array();

        foreach ($shops as $v) {
            $config = json_decode($v['val'], true);
            $access_token = isset($config['access_token']) ? $config['access_token'] : '';
            $shop_id = $v['shop_id'];
            $debug_payload = array(
                'shop_id' => $shop_id,
                'ad_type' => $ad_type,
                'date_range' => array(
                    'start_date' => $start_date_dmy,
                    'end_date' => $end_date_dmy
                ),
                'campaign_id_pages' => array(),
                'daily_performance' => null
            );

            if (!$access_token) {
                $shop_result = array(
                    'shop_id' => $shop_id,
                    'status' => false,
                    'message' => 'Access token tidak ditemukan',
                    'campaign_list' => array()
                );
                if ($is_debug) {
                    $debug_payload['error'] = 'Access token tidak ditemukan';
                    $shop_result['debug'] = $debug_payload;
                }
                $result[] = $shop_result;
                continue;
            }

            $campaign_ids = array();
            $page_no = 1;
            $has_next_page = true;

            while ($has_next_page && $page_no <= 50) {
                $path_1 = "/api/v2/ads/get_product_level_campaign_id_list";
                $timestamp_1 = time();
                $baseString_1 = sprintf("%s%s%s%s%s", $partner_id, $path_1, $timestamp_1, $access_token, $shop_id);
                $sign_1 = hash_hmac('sha256', $baseString_1, $partner_key);

                $params_1 = array(
                    'partner_id' => $partner_id,
                    'timestamp' => $timestamp_1,
                    'shop_id' => $shop_id,
                    'access_token' => $access_token,
                    'ad_type' => $ad_type,
                    'page_no' => $page_no,
                    'page_size' => 100,
                    'sign' => $sign_1
                );

                $url_1 = $host . $path_1 . '?' . http_build_query($params_1);

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url_1,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json'
                    ),
                ));

                $response_1_raw = curl_exec($curl);
                $curl_error_1 = curl_error($curl);
                curl_close($curl);

                $response_1 = json_decode($response_1_raw, true);
                if ($is_debug) {
                    $debug_payload['campaign_id_pages'][] = array(
                        'page_no' => $page_no,
                        'url' => $maskSensitiveUrl($url_1),
                        'curl_error' => $curl_error_1,
                        'response_raw' => $response_1_raw,
                        'response_json' => $response_1
                    );
                }

                if ($curl_error_1 || !isset($response_1['response'])) {
                    $has_next_page = false;
                    break;
                }

                if (isset($response_1['response']['campaign_list']) && is_array($response_1['response']['campaign_list'])) {
                    foreach ($response_1['response']['campaign_list'] as $campaign_item) {
                        if (isset($campaign_item['campaign_id'])) {
                            $campaign_ids[] = $campaign_item['campaign_id'];
                        }
                    }
                }

                $has_next_page = isset($response_1['response']['has_next_page']) ? (bool)$response_1['response']['has_next_page'] : false;
                $page_no++;
            }

            $campaign_ids = array_values(array_unique($campaign_ids));

            if (count($campaign_ids) == 0) {
                $shop_result = array(
                    'shop_id' => $shop_id,
                    'status' => true,
                    'message' => 'Tidak ada campaign aktif',
                    'campaign_list' => array()
                );
                if ($is_debug) {
                    $debug_payload['campaign_id_count'] = 0;
                    $shop_result['debug'] = $debug_payload;
                }
                $result[] = $shop_result;
                continue;
            }

            $path_2 = "/api/v2/ads/get_product_campaign_daily_performance";
            $campaign_id_chunks = array_chunk($campaign_ids, 100);
            $campaign_list_2 = array();
            $curl_error_2 = '';
            $api_error_2 = '';
            $api_message_2 = '';
            $daily_performance_debug_chunks = array();

            foreach ($campaign_id_chunks as $chunk_index => $campaign_id_chunk) {
                $timestamp_2 = time();
                $baseString_2 = sprintf("%s%s%s%s%s", $partner_id, $path_2, $timestamp_2, $access_token, $shop_id);
                $sign_2 = hash_hmac('sha256', $baseString_2, $partner_key);

                $params_2 = array(
                    'partner_id' => $partner_id,
                    'timestamp' => $timestamp_2,
                    'shop_id' => $shop_id,
                    'access_token' => $access_token,
                    'campaign_id_list' => implode(',', $campaign_id_chunk),
                    'start_date' => $start_date_dmy,
                    'end_date' => $end_date_dmy,
                    'sign' => $sign_2
                );

                $url_2 = $host . $path_2 . '?' . http_build_query($params_2);

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url_2,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json'
                    ),
                ));

                $response_2_raw = curl_exec($curl);
                $chunk_curl_error_2 = curl_error($curl);
                curl_close($curl);

                $response_2 = json_decode($response_2_raw, true);

                if ($is_debug) {
                    $daily_performance_debug_chunks[] = array(
                        'chunk_no' => $chunk_index + 1,
                        'campaign_id_count' => count($campaign_id_chunk),
                        'url' => $maskSensitiveUrl($url_2),
                        'curl_error' => $chunk_curl_error_2,
                        'response_raw' => $response_2_raw,
                        'response_json' => $response_2
                    );
                }

                if ($chunk_curl_error_2) {
                    if ($curl_error_2 === '') {
                        $curl_error_2 = $chunk_curl_error_2;
                    }
                    continue;
                }

                $response_error = isset($response_2['error']) ? trim((string)$response_2['error']) : '';
                if ($response_error !== '') {
                    if ($api_error_2 === '') {
                        $api_error_2 = $response_error;
                        $api_message_2 = isset($response_2['message']) ? (string)$response_2['message'] : '';
                    }
                    continue;
                }

                if (isset($response_2['response']['campaign_list']) && is_array($response_2['response']['campaign_list'])) {
                    $campaign_list_2 = array_merge($campaign_list_2, $response_2['response']['campaign_list']);
                }
            }

            if ($is_debug) {
                $debug_payload['daily_performance'] = array(
                    'chunk_size' => 100,
                    'chunk_count' => count($campaign_id_chunks),
                    'total_campaign_id' => count($campaign_ids),
                    'chunks' => $daily_performance_debug_chunks
                );
            }
            $campaign_list_non_zero = array();
            $saved_attempt = 0;
            $saved_success = 0;
            $saved_failed = 0;

            if (!$curl_error_2 && $api_error_2 === '' && count($campaign_list_2) > 0) {
                foreach ($campaign_list_2 as $campaign_item) {
                    $metrics_non_zero_list = array();
                    $metrics_list = isset($campaign_item['metrics_list']) && is_array($campaign_item['metrics_list']) ? $campaign_item['metrics_list'] : array();
                    $campaign_id = isset($campaign_item['campaign_id']) ? $campaign_item['campaign_id'] : 0;
                    $campaign_ad_type = isset($campaign_item['ad_type']) ? $campaign_item['ad_type'] : null;
                    $campaign_placement = isset($campaign_item['campaign_placement']) ? $campaign_item['campaign_placement'] : null;
                    $campaign_name = isset($campaign_item['ad_name']) ? $campaign_item['ad_name'] : null;

                    foreach ($metrics_list as $metrics_item) {
                        $metric_date_raw = isset($metrics_item['date']) ? $metrics_item['date'] : null;
                        $metric_date = null;
                        if ($metric_date_raw) {
                            $metric_date_obj = DateTime::createFromFormat('d-m-Y', $metric_date_raw);
                            if ($metric_date_obj) {
                                $metric_date = $metric_date_obj->format('Y-m-d');
                            } else {
                                $metric_date_obj = DateTime::createFromFormat('Y-m-d', $metric_date_raw);
                                if ($metric_date_obj) {
                                    $metric_date = $metric_date_obj->format('Y-m-d');
                                }
                            }
                        }

                        $metrics_non_zero = array(
                            'date' => $metric_date_raw
                        );

                        foreach ($metrics_item as $metrics_key => $metrics_value) {
                            if ($metrics_key == 'date') {
                                continue;
                            }

                            if (is_numeric($metrics_value) && (float)$metrics_value != 0) {
                                $metrics_non_zero[$metrics_key] = $metrics_value;
                            }
                        }

                        if (count($metrics_non_zero) > 1) {
                            if ($metric_date) {
                                $saved_attempt++;
                                $expense = isset($metrics_item['expense']) ? (float)$metrics_item['expense'] : 0;
                                $expense_after_tax = $expense + ($expense * $current_tax);
                                $save_params = array(
                                    $shop_id,
                                    $campaign_id,
                                    $metric_date,
                                    $campaign_ad_type,
                                    $campaign_placement,
                                    $campaign_name,
                                    isset($metrics_item['impression']) ? (int)$metrics_item['impression'] : 0,
                                    isset($metrics_item['clicks']) ? (int)$metrics_item['clicks'] : 0,
                                    isset($metrics_item['ctr']) ? (float)$metrics_item['ctr'] : 0,
                                    $expense,
                                    $expense_after_tax,
                                    isset($metrics_item['broad_gmv']) ? (float)$metrics_item['broad_gmv'] : 0,
                                    isset($metrics_item['broad_order']) ? (int)$metrics_item['broad_order'] : 0,
                                    isset($metrics_item['broad_order_amount']) ? (int)$metrics_item['broad_order_amount'] : 0,
                                    isset($metrics_item['broad_roi']) ? (float)$metrics_item['broad_roi'] : 0,
                                    isset($metrics_item['broad_cir']) ? (float)$metrics_item['broad_cir'] : 0,
                                    isset($metrics_item['cr']) ? (float)$metrics_item['cr'] : 0,
                                    isset($metrics_item['cpc']) ? (float)$metrics_item['cpc'] : 0,
                                    isset($metrics_item['direct_order']) ? (int)$metrics_item['direct_order'] : 0,
                                    isset($metrics_item['direct_order_amount']) ? (int)$metrics_item['direct_order_amount'] : 0,
                                    isset($metrics_item['direct_gmv']) ? (float)$metrics_item['direct_gmv'] : 0,
                                    isset($metrics_item['direct_roi']) ? (float)$metrics_item['direct_roi'] : 0,
                                    isset($metrics_item['direct_cir']) ? (float)$metrics_item['direct_cir'] : 0,
                                    isset($metrics_item['direct_cr']) ? (float)$metrics_item['direct_cr'] : 0,
                                    isset($metrics_item['cpdc']) ? (float)$metrics_item['cpdc'] : 0,
                                    $start_date_ymd,
                                    $end_date_ymd
                                );

                                $save_run = $this->db->query($sql_upsert, $save_params);
                                if ($save_run) {
                                    $saved_success++;
                                } else {
                                    $saved_failed++;
                                }
                            }
                            $metrics_non_zero_list[] = $metrics_non_zero;
                        }
                    }

                    if (count($metrics_non_zero_list) > 0) {
                        $campaign_list_non_zero[] = array(
                            'campaign_id' => $campaign_id,
                            'ad_type' => $campaign_ad_type,
                            'campaign_placement' => $campaign_placement,
                            'ad_name' => $campaign_name,
                            'metrics_list' => $metrics_non_zero_list
                        );
                    }
                }
            }

            $shop_status = $curl_error_2 === '' && $api_error_2 === '';
            $shop_message = 'OK';
            if ($curl_error_2 !== '') {
                $shop_message = $curl_error_2;
            } elseif ($api_error_2 !== '') {
                $shop_message = $api_message_2 !== '' ? $api_message_2 : $api_error_2;
            }

            $shop_result = array(
                'shop_id' => $shop_id,
                'status' => $shop_status,
                'message' => $shop_message,
                'saved_attempt' => $saved_attempt,
                'saved_success' => $saved_success,
                'saved_failed' => $saved_failed,
                'campaign_list' => $campaign_list_non_zero
            );
            if ($is_debug) {
                $shop_result['debug'] = $debug_payload;
            }
            $result[] = $shop_result;
        }

        echo json_encode(array(
            'status' => true,
            'start_date' => $start_date_ymd,
            'end_date' => $end_date_ymd,
            'data' => $result
        ), true);
    }


    function meta_product_campaign()
    {
        header('Content-Type: application/json; charset=utf-8');
        $lock_key = 'cron:ads:meta_product_campaign';
        if (!$this->acquire_cron_lock($lock_key, 0)) {
            $this->respond_cron_locked($lock_key);
            return;
        }

        $dt = $_GET;
        $account_filter = isset($dt['account_id']) ? trim($dt['account_id']) : '';
        $date = isset($dt['date']) ? trim($dt['date']) : '';
        $level = 'ad';
        $breakdowns = 'product_id';
        $fields = 'product_id,campaign_id,campaign_name,spend,actions,action_values,catalog_segment_value';
        $action_breakdowns = 'action_type';
        $brand = isset($dt['brand']) && trim($dt['brand']) !== '' ? trim($dt['brand']) : 'Miscella-G';
        $category = isset($dt['category']) && trim($dt['category']) !== '' ? trim($dt['category']) : 'Health|Personal Care';

        $get_start = isset($dt['start_date']) ? trim($dt['start_date']) : '';
        $get_until = isset($dt['until_date']) ? trim($dt['until_date']) : '';
        if ($date !== '') {
            $get_start = $date;
            $get_until = $date;
        }
        if ($get_until == '' && isset($dt['end_date'])) {
            $get_until = trim($dt['end_date']);
        }
        if ($get_start == '') {
            $get_start = date('Y-m-d');
        }
        if ($get_until == '') {
            $get_until = date('Y-m-d');
        }

        $toYmd = function ($s) {
            if (!$s) return date('Y-m-d');
            $d = DateTime::createFromFormat('Y-m-d', $s);
            if ($d && $d->format('Y-m-d') === $s) return $s;
            $d = DateTime::createFromFormat('d-m-Y', $s);
            if ($d) return $d->format('Y-m-d');
            return date('Y-m-d');
        };

        $since = $toYmd($get_start);
        $until = $toYmd($get_until);

        $meta_configs = $this->mymodel->selectWithQuery("SELECT * FROM marketplace_config WHERE status = 'Aktif' AND opt = 'META' ORDER BY id DESC");
        $access_token = '';
        foreach ($meta_configs as $meta_config) {
            $config_val = json_decode($meta_config['val'], true);
            if (isset($config_val['refresh_token']) && $config_val['refresh_token'] !== '') {
                $access_token = $config_val['refresh_token'];
                break;
            }
        }

        if ($access_token == '') {
            echo json_encode(array(
                'status' => false,
                'message' => 'Access token META tidak ditemukan',
                'start_date' => $since,
                'end_date' => $until,
                'data' => array()
            ), true);
            return;
        }

        $qry_account = "SELECT account_id FROM ads_meta_account WHERE status = 1";
        if ($account_filter !== '') {
            $account_filter_clean = preg_replace('/[^0-9,]/', '', $account_filter);
            $account_filter_arr = array_filter(array_map('trim', explode(',', $account_filter_clean)));
            if (count($account_filter_arr) > 0) {
                $account_filter_sql = "'" . implode("','", $account_filter_arr) . "'";
                $qry_account .= " AND account_id IN ($account_filter_sql)";
            }
        }
        $qry_account .= " ORDER BY created_at DESC";

        $accounts = $this->mymodel->selectWithQuery($qry_account);
        $result = array();
        $meta_product_campaign_table = 'meta_ads_product';
        $meta_product_campaign_table_exists = $this->db->table_exists($meta_product_campaign_table);

        foreach ($accounts as $account) {
            $account_id = $account['account_id'];
            $product_map = array();
            $saved_attempt = 0;
            $saved_success = 0;
            $saved_failed = 0;
            $paging_url = "https://graph.facebook.com/v21.0/act_$account_id/insights?" . http_build_query(array(
                'fields' => $fields,
                'time_range' => json_encode(array('since' => $since, 'until' => $until)),
                'level' => $level,
                'breakdowns' => $breakdowns,
                'action_breakdowns' => $action_breakdowns,
                'access_token' => $access_token,
                'limit' => 1000
            ));
            $last_error = '';
            $retry_without_product_id = false;

            while ($paging_url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $paging_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response_raw = curl_exec($ch);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($curl_error) {
                    $last_error = $curl_error;
                    break;
                }

                $response = json_decode($response_raw, true);
                if (isset($response['error'])) {
                    $message = isset($response['error']['message']) ? $response['error']['message'] : 'Meta API error';
                    if (
                        !$retry_without_product_id &&
                        strpos(strtolower($message), 'product_id is not valid for fields') !== false
                    ) {
                        $retry_without_product_id = true;
                        $paging_url = "https://graph.facebook.com/v21.0/act_$account_id/insights?" . http_build_query(array(
                            'fields' => 'campaign_id,campaign_name,spend,actions,action_values,catalog_segment_value',
                            'time_range' => json_encode(array('since' => $since, 'until' => $until)),
                            'level' => $level,
                            'breakdowns' => $breakdowns,
                            'action_breakdowns' => $action_breakdowns,
                            'access_token' => $access_token,
                            'limit' => 1000
                        ));
                        continue;
                    }
                    $last_error = $message;
                    break;
                }

                if (isset($response['data']) && is_array($response['data'])) {
                    foreach ($response['data'] as $row) {
                        $actions = isset($row['actions']) && is_array($row['actions']) ? $row['actions'] : array();
                        $action_values = isset($row['action_values']) && is_array($row['action_values']) ? $row['action_values'] : array();
                        $spend = isset($row['spend']) ? (float)$row['spend'] : 0;
                        $product_id_raw = isset($row['product_id']) ? trim((string)$row['product_id']) : '';
                        $retailer_id = $product_id_raw;
                        $product_name = '';
                        if (strpos($product_id_raw, ',') !== false) {
                            $product_parts = explode(',', $product_id_raw, 2);
                            $retailer_id = trim($product_parts[0]);
                            $product_name = trim($product_parts[1]);
                        }

                        $get_action_value = function ($actions_list, $types = array()) {
                            foreach ($actions_list as $action) {
                                $action_type = isset($action['action_type']) ? $action['action_type'] : '';
                                if (in_array($action_type, $types)) {
                                    return (int)$action['value'];
                                }
                            }
                            return 0;
                        };

                        $get_action_revenue = function ($actions_list, $types = array()) {
                            foreach ($actions_list as $action) {
                                $action_type = isset($action['action_type']) ? $action['action_type'] : '';
                                if (in_array($action_type, $types)) {
                                    return (float)$action['value'];
                                }
                            }
                            return 0;
                        };

                        $campaign_id = isset($row['campaign_id']) ? trim((string)$row['campaign_id']) : '';
                        $campaign_name = isset($row['campaign_name']) ? trim((string)$row['campaign_name']) : '';
                        $add_to_cart = $get_action_value($actions, array('offsite_conversion.fb_pixel_add_to_cart', 'add_to_cart'));
                        $purchases_online = $get_action_value($actions, array('offsite_conversion.fb_pixel_purchase', 'purchase'));
                        $purchases_offline = $get_action_value($actions, array('offline_conversion.purchase'));
                        $online_purchase_value = $get_action_revenue($action_values, array('offsite_conversion.fb_pixel_purchase', 'purchase'));
                        $offline_purchase_value = $get_action_revenue($action_values, array('offline_conversion.purchase'));
                        $spend_after_tax = $spend + ($spend * $this->current_tax);

                        $product_key = $retailer_id !== '' ? $retailer_id : ($product_id_raw !== '' ? $product_id_raw : 'UNKNOWN_PRODUCT');
                        if (!isset($product_map[$product_key])) {
                            $product_map[$product_key] = array(
                                'product_id' => $product_id_raw !== '' ? $product_id_raw : null,
                                'retailer_id' => $retailer_id !== '' ? $retailer_id : null,
                                'product_name' => $product_name,
                                'brand' => $brand,
                                'category' => $category,
                                'summary' => array(
                                    'add_to_cart' => 0,
                                    'purchases_online' => 0,
                                    'purchases_offline' => 0,
                                    'online_purchase_value' => 0.0,
                                    'offline_purchase_value' => 0.0,
                                    'spend' => 0.0,
                                    'spend_after_tax' => 0.0
                                ),
                                'campaign_list_map' => array()
                            );
                        }

                        $product_map[$product_key]['summary']['add_to_cart'] += $add_to_cart;
                        $product_map[$product_key]['summary']['purchases_online'] += $purchases_online;
                        $product_map[$product_key]['summary']['purchases_offline'] += $purchases_offline;
                        $product_map[$product_key]['summary']['online_purchase_value'] += $online_purchase_value;
                        $product_map[$product_key]['summary']['offline_purchase_value'] += $offline_purchase_value;
                        $product_map[$product_key]['summary']['spend'] += $spend;
                        $product_map[$product_key]['summary']['spend_after_tax'] += $spend_after_tax;

                        $campaign_key = $campaign_id !== '' ? $campaign_id : ($campaign_name !== '' ? $campaign_name : 'UNKNOWN_CAMPAIGN');
                        if (!isset($product_map[$product_key]['campaign_list_map'][$campaign_key])) {
                            $product_map[$product_key]['campaign_list_map'][$campaign_key] = array(
                                'campaign_id' => $campaign_id !== '' ? $campaign_id : null,
                                'campaign_name' => $campaign_name !== '' ? $campaign_name : null,
                                'summary' => array(
                                    'add_to_cart' => 0,
                                    'purchases_online' => 0,
                                    'purchases_offline' => 0,
                                    'online_purchase_value' => 0.0,
                                    'offline_purchase_value' => 0.0,
                                    'spend' => 0.0,
                                    'spend_after_tax' => 0.0
                                )
                            );
                        }

                        $product_map[$product_key]['campaign_list_map'][$campaign_key]['summary']['add_to_cart'] += $add_to_cart;
                        $product_map[$product_key]['campaign_list_map'][$campaign_key]['summary']['purchases_online'] += $purchases_online;
                        $product_map[$product_key]['campaign_list_map'][$campaign_key]['summary']['purchases_offline'] += $purchases_offline;
                        $product_map[$product_key]['campaign_list_map'][$campaign_key]['summary']['online_purchase_value'] += $online_purchase_value;
                        $product_map[$product_key]['campaign_list_map'][$campaign_key]['summary']['offline_purchase_value'] += $offline_purchase_value;
                        $product_map[$product_key]['campaign_list_map'][$campaign_key]['summary']['spend'] += $spend;
                        $product_map[$product_key]['campaign_list_map'][$campaign_key]['summary']['spend_after_tax'] += $spend_after_tax;
                    }
                }

                if (isset($response['paging']['next']) && $response['paging']['next'] !== '') {
                    $paging_url = $response['paging']['next'];
                } else {
                    $paging_url = null;
                }
            }

            if ($meta_product_campaign_table_exists) {
                $sql_upsert = "INSERT INTO {$meta_product_campaign_table}
                    (date, date_start, date_stop, account_id, campaign_id, campaign_name, retailer_id, product_name, brand, category, spend, spend_after_tax, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        campaign_name = VALUES(campaign_name),
                        product_name = VALUES(product_name),
                        brand = VALUES(brand),
                        category = VALUES(category),
                        spend = VALUES(spend),
                        spend_after_tax = VALUES(spend_after_tax),
                        date_start = VALUES(date_start),
                        date_stop = VALUES(date_stop),
                        updated_at = NOW()";

                foreach ($product_map as $product_item_for_save) {
                    $save_retailer_id = isset($product_item_for_save['retailer_id']) ? (string)$product_item_for_save['retailer_id'] : '';
                    $save_product_name = isset($product_item_for_save['product_name']) ? (string)$product_item_for_save['product_name'] : '';

                    foreach ($product_item_for_save['campaign_list_map'] as $campaign_item_for_save) {
                        $save_campaign_id = isset($campaign_item_for_save['campaign_id']) ? trim((string)$campaign_item_for_save['campaign_id']) : '';
                        if ($save_campaign_id === '') {
                            continue;
                        }

                        $save_campaign_name = isset($campaign_item_for_save['campaign_name']) ? (string)$campaign_item_for_save['campaign_name'] : '';
                        $save_summary = isset($campaign_item_for_save['summary']) && is_array($campaign_item_for_save['summary']) ? $campaign_item_for_save['summary'] : array();
                        $save_spend = isset($save_summary['spend']) ? (float)$save_summary['spend'] : 0;
                        $save_spend_after_tax = isset($save_summary['spend_after_tax']) ? (float)$save_summary['spend_after_tax'] : 0;

                        $saved_attempt++;
                        $save_run = $this->db->query($sql_upsert, array(
                            $since,
                            $since,
                            $until,
                            $account_id,
                            $save_campaign_id,
                            $save_campaign_name,
                            $save_retailer_id,
                            $save_product_name,
                            $brand,
                            $category,
                            $save_spend,
                            $save_spend_after_tax
                        ));

                        if ($save_run) {
                            $saved_success++;
                        } else {
                            $saved_failed++;
                        }
                    }
                }
            }

            $product_list = array();
            foreach ($product_map as $product_item) {
                $campaign_list = array_values($product_item['campaign_list_map']);
                usort($campaign_list, function ($a, $b) {
                    if ($a['summary']['spend'] == $b['summary']['spend']) {
                        return 0;
                    }
                    return ($a['summary']['spend'] > $b['summary']['spend']) ? -1 : 1;
                });
                unset($product_item['campaign_list_map']);
                $product_item['campaign_list'] = $campaign_list;
                $product_list[] = $product_item;
            }

            usort($product_list, function ($a, $b) {
                if ($a['summary']['spend'] == $b['summary']['spend']) {
                    return 0;
                }
                return ($a['summary']['spend'] > $b['summary']['spend']) ? -1 : 1;
            });

            $result[] = array(
                'account_id' => $account_id,
                'status' => $last_error == '',
                'message' => $last_error == '' ? 'OK' : $last_error,
                'saved_attempt' => $saved_attempt,
                'saved_success' => $saved_success,
                'saved_failed' => $saved_failed,
                'product_list' => $product_list
            );
        }

        echo json_encode(array(
            'status' => true,
            'start_date' => $since,
            'end_date' => $until,
            'level' => $level,
            'breakdowns' => $breakdowns,
            'action_breakdowns' => $action_breakdowns,
            'fields' => $fields,
            'brand' => $brand,
            'category' => $category,
            'data' => $result
        ), true);
    }

    function tiktok_product_campaign()
    {
        header('Content-Type: application/json; charset=utf-8');
        $lock_key = 'cron:ads:tiktok_product_campaign';
        if (!$this->acquire_cron_lock($lock_key, 0)) {
            $this->respond_cron_locked($lock_key);
            return;
        }

        $dt = $_GET;
        $accessToken = app_env('TIKTOK_BUSINESS_ACCESS_TOKEN');
        $endpoint = 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/report/get/';
        $is_debug = isset($dt['debug']) && in_array(strtolower(trim((string)$dt['debug'])), ['1', 'true', 'yes', 'on'], true);

        $date = isset($dt['date']) ? trim($dt['date']) : '';
        $start_date = isset($dt['start_date']) ? trim($dt['start_date']) : '';
        $end_date = isset($dt['end_date']) ? trim($dt['end_date']) : '';
        if ($date !== '') {
            $start_date = $date;
            $end_date = $date;
        }
        if ($start_date === '') {
            $start_date = date('Y-m-d');
        }
        if ($end_date === '') {
            $end_date = $start_date;
        }

        $is_valid_date = function ($value) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return false;
            }
            $parsed = DateTime::createFromFormat('Y-m-d', $value);
            return $parsed && $parsed->format('Y-m-d') === $value;
        };

        if (!$is_valid_date($start_date) || !$is_valid_date($end_date)) {
            echo json_encode([
                'status' => false,
                'message' => 'Parameter tanggal tidak valid. Gunakan format YYYY-MM-DD.',
                'data' => []
            ], JSON_PRETTY_PRINT);
            return;
        }
        if (strtotime($start_date) > strtotime($end_date)) {
            echo json_encode([
                'status' => false,
                'message' => 'start_date tidak boleh lebih besar dari end_date.',
                'data' => []
            ], JSON_PRETTY_PRINT);
            return;
        }

        $page_size = isset($dt['page_size']) ? (int)$dt['page_size'] : 1000;
        if ($page_size <= 0) {
            $page_size = 1000;
        }
        if ($page_size > 1000) {
            $page_size = 1000;
        }

        $shop_filter = isset($dt['shop_id']) ? trim($dt['shop_id']) : '';
        $store_filter = isset($dt['store_id']) ? trim($dt['store_id']) : '';
        if ($shop_filter === '' && $store_filter !== '') {
            $shop_filter = $store_filter;
        }

        $advertiser_filter = isset($dt['advertiser_id']) ? trim($dt['advertiser_id']) : '';
        $campaign_status = isset($dt['campaign_status']) ? trim($dt['campaign_status']) : 'STATUS_ALL';

        $promotion_types_raw = isset($dt['gmv_max_promotion_types']) ? $dt['gmv_max_promotion_types'] : '';
        if (is_array($promotion_types_raw)) {
            $promotion_tokens = $promotion_types_raw;
        } else {
            $promotion_tokens = $promotion_types_raw !== '' ? explode(',', (string)$promotion_types_raw) : array();
        }

        $allowed_promotion_types = array('PRODUCT', 'LIVE');
        $promotion_aliases = array(
            'PRODUCT' => 'PRODUCT',
            'LIVE' => 'LIVE',
            'PRODUCT_GMV_MAX' => 'PRODUCT',
            'LIVE_GMV_MAX' => 'LIVE'
        );

        $promotion_types = array();
        foreach ($promotion_tokens as $token) {
            $key = strtoupper(trim((string)$token));
            if ($key === '') {
                continue;
            }
            if (isset($promotion_aliases[$key])) {
                $mapped = $promotion_aliases[$key];
                if (in_array($mapped, $allowed_promotion_types, true) && !in_array($mapped, $promotion_types, true)) {
                    $promotion_types[] = $mapped;
                }
            }
        }

        if (count($promotion_types) === 0) {
            $promotion_types = $allowed_promotion_types;
        }

        $filtering = [
            'gmv_max_promotion_types' => $promotion_types
        ];
        if ($campaign_status !== 'STATUS_ALL') {
            $filtering['campaign_statuses'] = [$campaign_status];
        }

        $shops_query = $this->db->select('id, shop_id, shop_name')
            ->from('marketplace_config')
            ->where('status', 'Aktif')
            ->where('opt', 'TIKTOK');
        if ($shop_filter !== '') {
            $shops_query->where('shop_id', $shop_filter);
        }
        $shops = $shops_query->order_by('id', 'DESC')->get()->result_array();
        if (count($shops) === 0) {
            echo json_encode([
                'status' => false,
                'message' => 'Marketplace config TIKTOK aktif tidak ditemukan.',
                'data' => []
            ], JSON_PRETTY_PRINT);
            return;
        }

        if (!$this->db->table_exists('advertiser_spend')) {
            echo json_encode([
                'status' => false,
                'message' => 'Tabel advertiser_spend tidak ditemukan.',
                'data' => []
            ], JSON_PRETTY_PRINT);
            return;
        }

        $advertiser_query = $this->db->select('DISTINCT advertiser_id, advertiser_name', false)
            ->from('advertiser_spend')
            ->where('date', $end_date)
            ->where('advertiser_id IS NOT NULL', null, false)
            ->where('advertiser_id <>', '');
        if ($advertiser_filter !== '') {
            $advertiser_ids = array_filter(array_map('trim', explode(',', $advertiser_filter)));
            if (count($advertiser_ids) > 0) {
                $advertiser_query->where_in('advertiser_id', $advertiser_ids);
            }
        }
        $advertisers = $advertiser_query->order_by('advertiser_id', 'ASC')->get()->result_array();
        if (count($advertisers) === 0) {
            echo json_encode([
                'status' => false,
                'message' => 'Advertiser tidak ditemukan di advertiser_spend pada tanggal ' . $end_date . '.',
                'data' => []
            ], JSON_PRETTY_PRINT);
            return;
        }

        $advertiser_currency_map = [];
        $currency_source_table = 'tiktok_bc_advertisers';
        if ($this->db->table_exists($currency_source_table)
            && $this->db->field_exists('advertiser_id', $currency_source_table)
            && $this->db->field_exists('currency', $currency_source_table)) {
            $currency_rows = $this->db->select('advertiser_id, currency')
                ->from($currency_source_table)
                ->where('advertiser_id IS NOT NULL', null, false)
                ->where('advertiser_id <>', '')
                ->get()
                ->result_array();

            foreach ($currency_rows as $currency_row) {
                $currency_advertiser_id = isset($currency_row['advertiser_id']) ? trim((string)$currency_row['advertiser_id']) : '';
                if ($currency_advertiser_id === '') {
                    continue;
                }
                $advertiser_currency_map[$currency_advertiser_id] = strtoupper(trim((string)($currency_row['currency'] ?? '')));
            }
        }

        $usd_to_idr_rate = 1.0;
        $usd_rate_source = null;
        $usd_rate_error = '';
        $needs_usd_conversion = false;
        foreach ($advertisers as $advertiser_row_check) {
            $advertiser_id_check = isset($advertiser_row_check['advertiser_id']) ? trim((string)$advertiser_row_check['advertiser_id']) : '';
            if ($advertiser_id_check !== '' && isset($advertiser_currency_map[$advertiser_id_check]) && $advertiser_currency_map[$advertiser_id_check] === 'USD') {
                $needs_usd_conversion = true;
                break;
            }
        }

        if ($needs_usd_conversion) {
            $usd_rate_source = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json';
            $ch_rate = curl_init($usd_rate_source);
            curl_setopt($ch_rate, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_rate, CURLOPT_TIMEOUT, 20);
            $usd_rate_response = curl_exec($ch_rate);
            $usd_rate_curl_error = curl_error($ch_rate);
            curl_close($ch_rate);

            if (!$usd_rate_curl_error && is_string($usd_rate_response)) {
                $usd_rate_json = json_decode($usd_rate_response, true);
                if (isset($usd_rate_json['usd']['idr']) && is_numeric($usd_rate_json['usd']['idr']) && (float)$usd_rate_json['usd']['idr'] > 0) {
                    $usd_to_idr_rate = (float)$usd_rate_json['usd']['idr'];
                } else {
                    $usd_rate_error = 'Nilai kurs USD ke IDR tidak ditemukan, fallback kurs=1.';
                }
            } else {
                $usd_rate_error = $usd_rate_curl_error !== '' ? $usd_rate_curl_error : 'Gagal mengambil kurs USD ke IDR, fallback kurs=1.';
            }
        }

        $table_data = 'advertiser_spend_product';
        $table_data_exists = $this->db->table_exists($table_data);

        $sql_upsert_data = "INSERT INTO {$table_data}
            (report_date, stat_time_day, date_start, date_end, marketplace_config_id, shop_id, shop_name, advertiser_id, advertiser_name, campaign_id, campaign_name, operation_status, schedule_type, schedule_start_time, schedule_end_time, target_roi_budget, bid_type, max_delivery_budget, roas_bid, cost, net_cost, orders, cost_per_order, gross_revenue, roi, tax, cost_after_tax, raw_dimensions, raw_metrics, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                stat_time_day = VALUES(stat_time_day),
                date_start = VALUES(date_start),
                date_end = VALUES(date_end),
                shop_name = VALUES(shop_name),
                advertiser_name = VALUES(advertiser_name),
                campaign_name = VALUES(campaign_name),
                operation_status = VALUES(operation_status),
                schedule_type = VALUES(schedule_type),
                schedule_start_time = VALUES(schedule_start_time),
                schedule_end_time = VALUES(schedule_end_time),
                target_roi_budget = VALUES(target_roi_budget),
                bid_type = VALUES(bid_type),
                max_delivery_budget = VALUES(max_delivery_budget),
                roas_bid = VALUES(roas_bid),
                cost = VALUES(cost),
                net_cost = VALUES(net_cost),
                orders = VALUES(orders),
                cost_per_order = VALUES(cost_per_order),
                gross_revenue = VALUES(gross_revenue),
                roi = VALUES(roi),
                tax = VALUES(tax),
                cost_after_tax = VALUES(cost_after_tax),
                raw_dimensions = VALUES(raw_dimensions),
                raw_metrics = VALUES(raw_metrics),
                updated_at = NOW()";

        $dimensions = json_encode(['campaign_id', 'stat_time_day']);
        $metrics = json_encode([
            'campaign_id',
            'operation_status',
            'campaign_name',
            'schedule_type',
            'schedule_start_time',
            'schedule_end_time',
            'target_roi_budget',
            'bid_type',
            'max_delivery_budget',
            'roas_bid',
            'cost',
            'net_cost',
            'orders',
            'cost_per_order',
            'gross_revenue',
            'roi'
        ]);

        $summary = [];
        $total_request = 0;
        $total_saved_attempt = 0;
        $total_saved_success = 0;
        $total_saved_failed = 0;
        $total_rows_response = 0;
        $total_skipped_zero_cost = 0;

        foreach ($shops as $shop) {
            $shop_id = (string)$shop['shop_id'];
            $shop_name = isset($shop['shop_name']) ? (string)$shop['shop_name'] : '';
            $marketplace_config_id = (int)$shop['id'];

            foreach ($advertisers as $advertiser) {
                $advertiser_id = trim((string)$advertiser['advertiser_id']);
                if ($advertiser_id === '') {
                    continue;
                }
                $advertiser_name = isset($advertiser['advertiser_name']) ? (string)$advertiser['advertiser_name'] : '';
                $advertiser_currency = isset($advertiser_currency_map[$advertiser_id]) ? $advertiser_currency_map[$advertiser_id] : '';
                $is_usd_advertiser = $advertiser_currency === 'USD';

                $page = 1;
                $total_page = 1;
                $combo_saved_success = 0;
                $combo_saved_failed = 0;
                $combo_rows = 0;
                $combo_skipped_zero_cost = 0;
                $combo_error = '';
                $combo_debug_requests = [];

                while ($page <= $total_page) {
                    $payload = [
                        'advertiser_id' => $advertiser_id,
                        'store_ids' => json_encode([$shop_id]),
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'dimensions' => $dimensions,
                        'metrics' => $metrics,
                        'filtering' => json_encode($filtering),
                        'page' => $page,
                        'page_size' => $page_size
                    ];

                    $url = $endpoint . '?' . http_build_query($payload);
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Access-Token: {$accessToken}"
                    ]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

                    $response = curl_exec($ch);
                    $curl_error = curl_error($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    $total_request++;
                    $decoded = is_string($response) ? json_decode($response, true) : null;
                    if ($is_debug) {
                        $combo_debug_requests[] = [
                            'page' => $page,
                            'url' => $url,
                            'payload' => $payload,
                            'http_code' => $http_code,
                            'curl_error' => $curl_error,
                            'response_raw' => $response,
                            'response_json' => $decoded
                        ];
                    }
                    if ($response === false || $curl_error) {
                        $combo_error = $curl_error !== '' ? $curl_error : 'Request ke TikTok GMV Max Report gagal.';
                        break;
                    }
                    if (!is_array($decoded)) {
                        $combo_error = 'Response TikTok tidak valid JSON.';
                        break;
                    }

                    $response_code = isset($decoded['code']) ? (int)$decoded['code'] : -1;
                    $response_message = isset($decoded['message']) ? (string)$decoded['message'] : 'Unknown response';
                    $page_info = isset($decoded['data']['page_info']) && is_array($decoded['data']['page_info']) ? $decoded['data']['page_info'] : [];
                    $total_page = isset($page_info['total_page']) ? max(1, (int)$page_info['total_page']) : 1;
                    $total_number = isset($page_info['total_number']) ? (int)$page_info['total_number'] : 0;
                    $list = isset($decoded['data']['list']) && is_array($decoded['data']['list']) ? $decoded['data']['list'] : [];

                    if ($response_code !== 0) {
                        $combo_error = $response_message;
                        break;
                    }

                    foreach ($list as $item) {
                        $dimensions_row = isset($item['dimensions']) && is_array($item['dimensions']) ? $item['dimensions'] : [];
                        $metrics_row = isset($item['metrics']) && is_array($item['metrics']) ? $item['metrics'] : [];

                        $campaign_id = isset($dimensions_row['campaign_id']) ? trim((string)$dimensions_row['campaign_id']) : '';
                        if ($campaign_id === '') {
                            $campaign_id = isset($metrics_row['campaign_id']) ? trim((string)$metrics_row['campaign_id']) : '';
                        }
                        if ($campaign_id === '') {
                            continue;
                        }

                        $stat_time_day = isset($dimensions_row['stat_time_day']) ? trim((string)$dimensions_row['stat_time_day']) : '';
                        $report_date = $end_date;
                        if ($stat_time_day !== '') {
                            $date_part = substr($stat_time_day, 0, 10);
                            if ($is_valid_date($date_part)) {
                                $report_date = $date_part;
                            }
                        }

                        $target_roi_budget = isset($metrics_row['target_roi_budget']) ? (float)$metrics_row['target_roi_budget'] : 0;
                        $max_delivery_budget = isset($metrics_row['max_delivery_budget']) ? (float)$metrics_row['max_delivery_budget'] : 0;
                        $cost = isset($metrics_row['cost']) ? (float)$metrics_row['cost'] : 0;
                        $net_cost = isset($metrics_row['net_cost']) ? (float)$metrics_row['net_cost'] : 0;
                        $cost_per_order = isset($metrics_row['cost_per_order']) ? (float)$metrics_row['cost_per_order'] : 0;
                        $gross_revenue = isset($metrics_row['gross_revenue']) ? (float)$metrics_row['gross_revenue'] : 0;

                        if ($is_usd_advertiser) {
                            $target_roi_budget *= $usd_to_idr_rate;
                            $max_delivery_budget *= $usd_to_idr_rate;
                            $cost *= $usd_to_idr_rate;
                            $net_cost *= $usd_to_idr_rate;
                            $cost_per_order *= $usd_to_idr_rate;
                            $gross_revenue *= $usd_to_idr_rate;
                        }
                        $tax = (float)$this->current_tax;
                        $cost_after_tax = $cost + ($cost * $tax);
                        $combo_rows++;
                        $total_rows_response++;
                        if ($cost <= 0) {
                            $combo_skipped_zero_cost++;
                            $total_skipped_zero_cost++;
                            continue;
                        }

                        $total_saved_attempt++;

                        if ($table_data_exists) {
                            $saved = $this->db->query($sql_upsert_data, [
                                $report_date,
                                $stat_time_day,
                                $start_date,
                                $end_date,
                                $marketplace_config_id,
                                $shop_id,
                                $shop_name,
                                $advertiser_id,
                                $advertiser_name,
                                $campaign_id,
                                isset($metrics_row['campaign_name']) ? (string)$metrics_row['campaign_name'] : '',
                                isset($metrics_row['operation_status']) ? (string)$metrics_row['operation_status'] : '',
                                isset($metrics_row['schedule_type']) ? (string)$metrics_row['schedule_type'] : '',
                                isset($metrics_row['schedule_start_time']) ? (string)$metrics_row['schedule_start_time'] : '',
                                isset($metrics_row['schedule_end_time']) ? (string)$metrics_row['schedule_end_time'] : '',
                                $target_roi_budget,
                                isset($metrics_row['bid_type']) ? (string)$metrics_row['bid_type'] : '',
                                $max_delivery_budget,
                                isset($metrics_row['roas_bid']) ? (float)$metrics_row['roas_bid'] : 0,
                                $cost,
                                $net_cost,
                                isset($metrics_row['orders']) ? (int)$metrics_row['orders'] : 0,
                                $cost_per_order,
                                $gross_revenue,
                                isset($metrics_row['roi']) ? (float)$metrics_row['roi'] : 0,
                                $tax,
                                $cost_after_tax,
                                json_encode($dimensions_row),
                                json_encode($metrics_row)
                            ]);
                            if ($saved) {
                                $total_saved_success++;
                                $combo_saved_success++;
                            } else {
                                $total_saved_failed++;
                                $combo_saved_failed++;
                            }
                        }
                    }

                    $page++;
                }

                $summary_item = [
                    'shop_id' => $shop_id,
                    'shop_name' => $shop_name,
                    'advertiser_id' => $advertiser_id,
                    'advertiser_name' => $advertiser_name,
                    'rows' => $combo_rows,
                    'skipped_zero_cost' => $combo_skipped_zero_cost,
                    'saved_success' => $combo_saved_success,
                    'saved_failed' => $combo_saved_failed,
                    'error' => $combo_error,
                    'currency' => $advertiser_currency !== '' ? $advertiser_currency : null,
                    'currency_converted_to_idr' => $is_usd_advertiser
                ];
                if ($is_debug) {
                    $summary_item['debug'] = [
                        'request_count' => count($combo_debug_requests),
                        'requests' => $combo_debug_requests
                    ];
                }
                $summary[] = $summary_item;
            }
        }

        echo json_encode([
            'status' => true,
            'message' => 'Sync tiktok_product_campaign selesai',
            'request' => [
                'endpoint' => $endpoint,
                'method' => 'GET',
                'date' => $date,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'page_size' => $page_size,
                'shop_filter' => $shop_filter,
                'advertiser_filter' => $advertiser_filter,
                'debug_enabled' => $is_debug,
                'filtering_applied' => $filtering,
                'source_table_advertiser' => 'advertiser_spend',
                'source_date_advertiser' => $end_date,
                'currency_source_table' => $currency_source_table,
                'usd_to_idr_rate' => $usd_to_idr_rate,
                'usd_rate_error' => $usd_rate_error
            ],
            'summary_total' => [
                'shop_count' => count($shops),
                'advertiser_count' => count($advertisers),
                'request_count' => $total_request,
                'response_rows' => $total_rows_response,
                'skipped_zero_cost' => $total_skipped_zero_cost,
                'saved_attempt' => $total_saved_attempt,
                'saved_success' => $total_saved_success,
                'saved_failed' => $total_saved_failed,
                'table_data_exists' => $table_data_exists
            ],
            'data' => $summary
        ], JSON_PRETTY_PRINT);
    }


    function get_tiktok_gmv()
    {
        $lock_key = 'cron:ads:get_tiktok_gmv';
        if (!$this->acquire_cron_lock($lock_key, 0)) {
            $this->respond_cron_locked($lock_key);
            return;
        }

        $advertiser_url = "https://business-api.tiktok.com/open_api/v1.3/oauth2/advertiser/get/?app_id=" . urlencode(app_env('TIKTOK_BUSINESS_APP_ID')) . "&secret=" . urlencode(app_env('TIKTOK_BUSINESS_SECRET'));

        $tax = $this->mymodel->selectWithQuery("SELECT tax FROM config WHERE id = 'TAX'");
        $current_tax = $tax[0]['tax'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $advertiser_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Access-Token: " . app_env('TIKTOK_BUSINESS_ACCESS_TOKEN'),
        ]);

        $advertiser_response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "Error: " . curl_error($ch);
            curl_close($ch);
            exit;
        }
        curl_close($ch);

        $advertiser_data = json_decode($advertiser_response, true);

        if (!isset($advertiser_data['data']['list']) || empty($advertiser_data['data']['list'])) {
            echo "No advertiser data available.\n";
            exit;
        }

        foreach ($advertiser_data['data']['list'] as $advertiser) {
            $advertiser_id = $advertiser['advertiser_id'];
            $advertiser_name = $advertiser['advertiser_name'];
            
            $accessToken = app_env('TIKTOK_BUSINESS_ACCESS_TOKEN');
            
            $info_url = "https://business-api.tiktok.com/open_api/v1.3/advertiser/info/?advertiser_ids=" . urlencode('["' . $advertiser_id . '"]');
        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $info_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Access-Token: ' . $accessToken,
                'Content-Type: application/json'
            ]);
        
            $info_response = curl_exec($ch);
            curl_close($ch);
    
            $info_data = json_decode($info_response, true);
            $currency = $info_data['data']['list'][0]['currency'] ?? 'Unknown';

            $apiUrl = 'https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/';
            

            $params = [
                'advertiser_id' => $advertiser_id,
                'service_type' => 'AUCTION',
                'report_type' => 'TT_SHOP',
                'data_level' => 'AUCTION_ADVERTISER',
                'dimensions' => '["advertiser_id"]',
                'metrics' => '["spend"]',
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d'),
                'page' => 1,
                'page_size' => 100
            ];

            $queryString = http_build_query($params);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl . '?' . $queryString);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Access-Token: ' . $accessToken
            ]);

            $response = curl_exec($ch);

            if ($response === false) {
                $error = curl_error($ch);
                echo "cURL Error: " . $error;
            } else {
                $responseData = json_decode($response, true);
                if (!isset($responseData['data']['list']) || empty($responseData['data']['list'])) {
                    echo "No data found for the given request.\n";
                } else {
                    foreach ($responseData['data']['list'] as $data) {
                        $advertiser_id = $data['dimensions']['advertiser_id'];

                        if (isset($data['metrics']['spend'])) {
                            $spend = $data['metrics']['spend'];

                            $url = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json";

                            $ch = curl_init($url);

                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt(
                                $ch,
                                CURLOPT_HTTPHEADER,
                                [
                                    'Accept: application/json',
                                ]
                            );

                            $response = curl_exec($ch);

                            if (curl_errno($ch)) {
                                echo "cURL Error: " . curl_error($ch);
                            } else {
                                $responseData = json_decode($response, true);

                                if (isset($responseData['usd']['idr'])) {
                                    $exchangeRate = $responseData['usd']['idr'];
                                    if ($currency != 'IDR') {
                                        $idr = $spend * $exchangeRate;
                                    } else {
                                        $idr = $spend;
                                    }

                                    $dt = array(
                                        'date' => date('Y-m-d'),
                                        'advertiser_id' => $advertiser_id,
                                        'advertiser_name' => $advertiser_name,
                                        'spend' => $spend,
                                        'spend_idr' => $idr,
                                        'spend_idr_after_tax' => $idr + ($idr * $current_tax),
                                        'tax' => $this->current_tax
                                    );

                                    $this->db->where('advertiser_id', $advertiser_id);
                                    $this->db->where('date', date('Y-m-d'));
                                    $query = $this->db->get('advertiser_spend');

                                    if ($query->num_rows() == 0) {
                                        $this->db->insert('advertiser_spend', $dt);
                                    } else {
                                        $this->db->where('date', date('Y-m-d'));
                                        $this->db->where('advertiser_id', $advertiser_id);
                                        $this->db->update('advertiser_spend', $dt);
                                    }
                                } else {
                                    echo "Error: Unable to retrieve exchange rate.";
                                }
                                curl_close($ch);
                            }
                        } else {
                            echo "No spend data available for advertiser: " . $advertiser_id . "\n";
                        }
                    }
                }
            }

            curl_close($ch);
        }
    }

    
    public function generate_recurring_expense()
    {
        $expenses = $this->mymodel->selectWithQuery("SELECT * FROM expense WHERE is_recurring = 1");
        foreach ($expenses as $expense) {
            $last_generated_at = $expense['last_generated_at'];
            $recurring_type = $expense['recurring_type'];

            $next_date = $this->get_next_date($last_generated_at, $recurring_type, $expense);

            if ($next_date == date('Y-m-d')) {
                $new_expense = [
                    'date'            => $next_date,
                    'brand'           => $expense['brand'],
                    'category'        => $expense['category'],
                    'title'           => $expense['title'],
                    'desc'            => $expense['desc'],
                    'price'           => $expense['price'],
                    'qty'             => $expense['qty'],
                    'customer_text'   => $expense['customer_text'],
                    'customer'        => $expense['customer'],
                    'type'            => $expense['type'],
                    'type_sub'        => $expense['type_sub'],
                    'price_total'     => $expense['price_total'],
                    'price_total_2'   => $expense['price_total_2'],
                    'net_price'       => $expense['net_price'],
                    'created_by'      => $expense['created_by'],
                    'updated_by'      => $expense['updated_by'],
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                    'status'          => $expense['status'],
                    'is_recurring'    => 0,
                    'recurring_type'  => $expense['recurring_type'],
                    'last_generated_at' => $next_date
                ];

                $this->db->insert('expense', $new_expense);

                $this->db->where('id', $expense['id'])->update('expense', ['last_generated_at' => $next_date]);
            }

        }
        echo "Recurring expenses generated successfully.";
        $success = true;
    }

    public function get_next_date($last_date, $recurring_type, $expense)
    {
        if (!$last_date || !strtotime($last_date)) {
            $last_date = date('Y-m-d');
        }

        switch ($recurring_type) {
            case 'Harian':
                return date('Y-m-d', strtotime($last_date . ' +1 day'));
            case 'Mingguan':
                $day_of_week = $this->convertDayToEnglish($expense['recurring_day'] ?? 'Senin');
                return date('Y-m-d', strtotime("next $day_of_week", strtotime($last_date)));
            case 'Bulanan':
                $date_of_month = $expense['recurring_date'] ?? 1;
                $next_date = date('Y-m-d', strtotime($last_date . " +1 month"));
                $next_month = date('m', strtotime($next_date));
                $next_year = date('Y', strtotime($next_date));
                $last_day_of_month = date('t', strtotime("$next_year-$next_month-01"));
                $date_of_month = min($date_of_month, $last_day_of_month); // Batasi tanggal sesuai jumlah hari di bulan
                return date('Y-m-d', strtotime("$next_year-$next_month-$date_of_month"));
            case 'Tahunan':
                return date('Y-m-d', strtotime($last_date . ' +1 year'));
            default:
                return $last_date;
        }
    }

    public function convertDayToEnglish($day)
    {
        $days = [
            'Senin' => 'Monday',
            'Selasa' => 'Tuesday',
            'Rabu' => 'Wednesday',
            'Kamis' => 'Thursday',
            'Jumat' => 'Friday',
            'Sabtu' => 'Saturday',
            'Minggu' => 'Sunday',
        ];
        return $days[$day] ?? 'Monday';
    }
    
    public function update_customer_scrape()
    {
        $customers = $this->mymodel->selectWithQuery("
                SELECT * FROM customer_scrap WHERE is_generated = 0
                LIMIT 30
            ");

        foreach ($customers as $customer) {
            $order_id = $customer['order_id'];
            $marketplace = $customer['marketplace'];

            $query = $this->mymodel->selectWithQuery("
                SELECT id, customer 
                FROM transaction 
                WHERE order_id = '$order_id' AND marketplace = '$marketplace'
                LIMIT 1
            ");

            if (!empty($query)) {
                $query = $query[0];
                $transaction_id = $query['id'];
                $customer_id = $query['customer'];

                $dt = [
                    'updated_at' => date('Y-m-d H:i:s'),
                    'order_id' => $order_id,
                    'marketplace' => $marketplace,
                    'customer_text' => strval($customer['name']),
                    'phone' => strval($customer['phone']),
                    'address' => strval($customer['address']),
                    'city_text' => strval($customer['city_text'] ?? ''),
                    'province_text' => strval($customer['province_text'] ?? ''),
                    'c_username' => strval($customer['username']),
                ];
                $this->db->update('transaction', $dt, ['id' => $transaction_id]);

                $dtt = [
                    'updated_at' => date('Y-m-d H:i:s'),
                    'full_name' => strval($customer['name']),
                    'phone' => strval($customer['phone']),
                    'username' => strval($customer['username']),
                    'address' => strval($customer['address']),
                    'city_text' => strval($customer['city_text'] ?? ''),
                    'province_text' => strval($customer['province_text'] ?? ''),
                ];
                $this->db->update('customer', $dtt, ['id' => $customer_id]);

                $this->db->update('customer_scrap', ['is_generated' => 1], [
                    'order_id' => $order_id,
                    'marketplace' => $marketplace
                ]);

                $success_count++;
            } else {
                $failed_count++;
            }
        }

        $output = "[CRON] Customer Scrape Update - Success: $success_count | Failed: $failed_count";
        log_message('info', $output); 
        echo $output . PHP_EOL; 
    }

    function get_tiktok_campaign()
    {
        $lock_key = 'cron:ads:get_tiktok_campaign';
        if (!$this->acquire_cron_lock($lock_key, 0)) {
            $this->respond_cron_locked($lock_key);
            return;
        }

        $date = $this->input->get('date', true);
        $start_date = $this->input->get('start_date', true);
        $end_date = $this->input->get('end_date', true);

        if ($date) {
            $start_date = $date;
            $end_date = $date;
        }

        if (empty($start_date)) {
            $start_date = date('Y-m-d');
        }
        if (empty($end_date)) {
            $end_date = $start_date;
        }

        $is_valid_date = function ($value) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return false;
            }
            $parsed = DateTime::createFromFormat('Y-m-d', $value);
            return $parsed && $parsed->format('Y-m-d') === $value;
        };

        if (!$is_valid_date($start_date) || !$is_valid_date($end_date)) {
            echo json_encode([
                'status' => false,
                'message' => 'Parameter tanggal tidak valid. Gunakan format YYYY-MM-DD.',
                'data' => []
            ]);
            return;
        }

        if (strtotime($start_date) > strtotime($end_date)) {
            echo json_encode([
                'status' => false,
                'message' => 'start_date tidak boleh lebih besar dari end_date.',
                'data' => []
            ]);
            return;
        }

        $start_date_sql = $this->db->escape($start_date);
        $end_date_sql = $this->db->escape($end_date);

        $advertiser_id_result = $this->mymodel->selectWithQuery("
            SELECT DISTINCT advertiser_id
            FROM tiktok_ads_data
            WHERE DATE(date) BETWEEN {$start_date_sql} AND {$end_date_sql}
        ");
        $advertiser_ids = array_column($advertiser_id_result, 'advertiser_id');
        $access_token = app_env('TIKTOK_BUSINESS_ACCESS_TOKEN');
        $report_url = "https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/";

        $report_date = $end_date;

        foreach ($advertiser_ids as $advertiser_id) {
            $report_data = [
                "advertiser_id" => $advertiser_id,
                "report_type" => "BASIC",
                "data_level" => "AUCTION_CAMPAIGN",
                "dimensions" => json_encode(["campaign_id"]),
                "metrics" => json_encode([
                    "campaign_name",
                    "impressions",
                    "clicks",
                    "spend",
                    "ctr",
                    "cpc",
                    "cpm",
                    "onsite_shopping_roas",
                    "onsite_shopping",
                    "cost_per_onsite_shopping",
                    "onsite_shopping_rate",
                    "value_per_onsite_shopping",
                    "total_onsite_shopping_value",
                    "onsite_on_web_cart",
                    "total_onsite_on_web_cart_value",
                    "frequency",
                    "reach",
                    "currency"
                ]),
                "start_date" => $start_date,
                "end_date" => $end_date
            ];

            $query_string = http_build_query($report_data);
            $ch = curl_init($report_url . '?' . $query_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Access-Token: $access_token"]);
            $report_response = curl_exec($ch);
            if ($report_response === false) {
                echo "Error fetching campaign report: " . curl_error($ch);
                continue;
            }
            curl_close($ch);

            $report_data_decoded = json_decode($report_response, true);
            if (!empty($report_data_decoded['data']['list'])) {
                foreach ($report_data_decoded['data']['list'] as $metrics_data) {
                    $metrics = $metrics_data['metrics'] ?? [];
                    $dimensions = $metrics_data['dimensions'] ?? [];

                    if ((!empty($metrics['spend']) && $metrics['spend'] != 0) || (!empty($metrics['onsite_shopping']) && $metrics['onsite_shopping'] != 0)) {
                        $getMetric = function ($key, $default = 0) use ($metrics) {
                            return $metrics[$key] ?? $default;
                        };

                        $currency = $getMetric('currency', 'USD');
                        $spend = $getMetric('spend');
                        $onsite_shopping = $getMetric('onsite_shopping');
                        $spend_idr = $spend;
                        $onsite_shopping_idr = $onsite_shopping;

                        if ($currency === 'USD') {
                            $url = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json";
                            $ch = curl_init($url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
                            $response = curl_exec($ch);
                            $responseData = json_decode($response, true);
                            curl_close($ch);

                            if (isset($responseData['usd']['idr'])) {
                                $exchangeRate = $responseData['usd']['idr'];
                                $spend_idr = $spend * $exchangeRate;
                                $onsite_shopping_idr = $onsite_shopping * $exchangeRate;
                            }
                        }

                        $dt = [
                            'advertiser_id' => $advertiser_id,
                            'campaign_id' => $dimensions['campaign_id'] ?? '',
                            'campaign_name' => $getMetric('campaign_name'),
                            'impressions' => $getMetric('impressions'),
                            'clicks' => $getMetric('clicks'),
                            'spend' => $spend,
                            'spend_idr' => $spend_idr,
                            'spend_idr_after_tax' => $spend_idr + ($spend_idr * $this->current_tax),
                            'tax' => $this->current_tax,
                            'ctr' => $getMetric('ctr'),
                            'cpc' => $getMetric('cpc'),
                            'cpm' => $getMetric('cpm'),
                            'onsite_shopping_roas' => $getMetric('onsite_shopping_roas'),
                            'onsite_shopping' => $onsite_shopping_idr,
                            'cost_per_onsite_shopping' => $getMetric('cost_per_onsite_shopping'),
                            'onsite_shopping_rate' => $getMetric('onsite_shopping_rate'),
                            'value_per_onsite_shopping' => $getMetric('value_per_onsite_shopping'),
                            'total_onsite_shopping_value' => $getMetric('total_onsite_shopping_value'),
                            'onsite_on_web_cart' => $getMetric('onsite_on_web_cart'),
                            'total_onsite_on_web_cart_value' => $getMetric('total_onsite_on_web_cart_value'),
                            'frequency' => $getMetric('frequency'),
                            'reach' => $getMetric('reach'),
                            'currency' => $currency,
                            'date' => $report_date
                        ];


                        $query = $this->db->where('campaign_id', $dt['campaign_id'])
                            ->where('date', $report_date)
                            ->get('tiktok_ads_campaign');

                        if ($query->num_rows() == 0) {
                            $this->db->insert('tiktok_ads_campaign', $dt);
                        } else {
                            $this->db->where('campaign_id', $dt['campaign_id'])
                                ->where('date', $report_date)
                                ->update('tiktok_ads_campaign', $dt);
                        }
                    }
                }
            }
        }
    }

    public function threads_insights()
    {
        $url_input = $_GET['url'] ?? '';
        $thread_id = $_GET['thread_id'] ?? '';
        $influencer_id = $_GET['influencer_id'] ?? '';
        $metrics = $_GET['metrics'] ?? 'views,likes,replies,reposts,quotes';

        if (empty($url_input) && empty($thread_id)) {
            echo json_encode(['status' => false, 'msg' => 'url atau thread_id wajib diisi']);
            return;
        }

        if (empty($influencer_id)) {
            echo json_encode(['status' => false, 'msg' => 'influencer_id wajib diisi']);
            return;
        }

        $influencer = $this->mymodel->selectDataOne('influencer', ['id' => $influencer_id]);

        if (empty($influencer['threads_access_token'])) {
            echo json_encode(['status' => false, 'msg' => 'Influencer belum connect Threads. Silakan connect di halaman edit influencer.']);
            return;
        }

        if (!empty($influencer['threads_token_expires_at']) && $influencer['threads_token_expires_at'] < date("Y-m-d H:i:s")) {
            echo json_encode(['status' => false, 'msg' => 'Token Threads sudah expired. Silakan reconnect di halaman edit influencer.']);
            return;
        }

        $access_token = $influencer['threads_access_token'];

        // Kalau input URL, extract shortcode lalu resolve ke media ID via /me/threads
        if (!empty($url_input) && empty($thread_id)) {
            // Extract shortcode dari URL: https://www.threads.com/@user/post/SHORTCODE
            if (preg_match('#threads\.(?:com|net)/@[^/]+/post/([A-Za-z0-9_-]+)#', $url_input, $m)) {
                $shortcode = $m[1];
            } else {
                echo json_encode(['status' => false, 'msg' => 'Format URL tidak valid. Contoh: https://www.threads.com/@username/post/SHORTCODE']);
                return;
            }

            // Cari media ID yang cocok dengan shortcode via /me/threads
            $thread_id = $this->resolve_threads_media_id($shortcode, $access_token);

            if (empty($thread_id)) {
                echo json_encode(['status' => false, 'msg' => 'Post dengan shortcode "' . $shortcode . '" tidak ditemukan di akun ini.']);
                return;
            }
        }

        $url = "https://graph.threads.net/v1.0/" . $thread_id . "/insights?metric=" . $metrics . "&access_token=" . $access_token;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);

        if (!empty($data['error'])) {
            echo json_encode(['status' => false, 'msg' => $data['error']['message'] ?? 'Error dari Threads API', 'raw' => $data]);
            return;
        }

        // Parse metrics ke format yang mudah dibaca
        $result = [];
        if (!empty($data['data'])) {
            foreach ($data['data'] as $metric) {
                $name = $metric['name'] ?? '';
                $value = $metric['values'][0]['value'] ?? $metric['total_value']['value'] ?? 0;
                $result[$name] = intval($value);
            }
        }

        echo json_encode([
            'status' => true,
            'thread_id' => $thread_id,
            'url' => $url_input,
            'influencer_id' => $influencer_id,
            'metrics' => $result,
            'raw' => $data,
        ]);
    }

    private function resolve_threads_media_id($shortcode, $access_token)
    {
        $after = '';
        $max_pages = 5; // maksimal 5 halaman pencarian

        for ($page = 0; $page < $max_pages; $page++) {
            $url = "https://graph.threads.net/v1.0/me/threads?fields=id,shortcode&limit=100&access_token=" . $access_token;
            if (!empty($after)) {
                $url .= "&after=" . $after;
            }

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
            ]);
            $response = curl_exec($curl);
            curl_close($curl);

            $data = json_decode($response, true);

            if (empty($data['data'])) break;

            foreach ($data['data'] as $post) {
                if (($post['shortcode'] ?? '') === $shortcode) {
                    return $post['id'];
                }
            }

            // Lanjut ke halaman berikutnya
            $after = $data['paging']['cursors']['after'] ?? '';
            if (empty($after)) break;
        }

        return '';
    }
}

// if ($success) {
//     $html['status'] = true;
//     $html['data'] = array();
//     $html['msg'] = 'Berhasil';
//     echo json_encode($html, true);
//     die;
// } else {
//     $html['status'] = false;
//     $html['data'] = array();
//     $html['msg'] = 'Tidak ada data yang diproses.';
//     echo json_encode($html, true);
//     die;
// }
header('Content-Type: application/json; charset=utf-8');
