<?php
defined('BASEPATH') or exit('No direct script access allowed');

class TiktokAuth extends CI_Controller
{
    public function redirect_to_auth()
    {
        $app_id = app_env('TIKTOK_BUSINESS_APP_ID');
        $redirect_uri = urlencode(app_env('TIKTOK_BUSINESS_REDIRECT_URI'));
        $state = "your_custom_params";

        $auth_url = "https://business-api.tiktok.com/portal/auth?app_id=$app_id&state=$state&redirect_uri=$redirect_uri";

        redirect($auth_url);
    }

    public function callback()
    {
        $auth_code = $this->input->get('auth_code');
        $state = $this->input->get('state');

        if ($auth_code) {
            $access_token = $this->get_access_token($auth_code);

            if ($access_token) {
                $this->save_token_to_database($access_token);
            } else {
                echo "Error: Gagal mendapatkan Access Token.";
            }
        } else {
            echo "Error: auth_code tidak diterima.";
        }
    }

    private function get_access_token($auth_code)
    {
        $app_id = app_env('TIKTOK_BUSINESS_APP_ID');
        $secret = app_env('TIKTOK_BUSINESS_SECRET');
        $redirect_uri = app_env('TIKTOK_BUSINESS_REDIRECT_URI');

        $url = "https://business-api.tiktok.com/open_api/v1.3/oauth2/access_token/";

        $post_data = [
            "app_id" => $app_id,
            "secret" => $secret,
            "auth_code" => $auth_code,
            "redirect_uri" => $redirect_uri
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        log_message('debug', 'TikTok API Response: ' . $response);
        log_message('debug', 'HTTP Code: ' . $http_code);

        $response_data = json_decode($response, true);
        
        if (isset($response_data['data']['access_token'])) {
            return $response_data['data']['access_token'];
        } else {
            log_message('error', 'TikTok OAuth Error: ' . (isset($response_data['message']) ? $response_data['message'] : 'Unknown error'));
            return false;
        }
    }
    
    private function save_token_to_database($access_token)
    {
        $shop_id = app_env('TIKTOK_BUSINESS_SHOP_ID');
        $shop_name = 'Pomeglow';
        $expired_at = strtotime('+1 year');

        $check = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $shop_id));

        $dt = array();
        $config = array();
        $config['access_token'] = $access_token;
        $dt['val'] = json_encode($config, true);
        $dt['opt'] = 'Tiktok Business Center';
        $dt['status'] = "Aktif";
        $dt['shop_id'] = $shop_id;
        $dt['shop_name'] = $shop_name;
        $dt['updated_at'] = date("Y-m-d H:i:s");
        $dt['updated_by'] = 0;
        $dt['expired_at'] = date("Y-m-d H:i:s", $expired_at);

        if ($check) {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = 0;
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->update('marketplace_config', $dt, array('id' => $check['id']));
        } else {
            $dt['created_at'] = date("Y-m-d H:i:s");
            $dt['created_by'] = 0;
            $this->db->insert('marketplace_config', $dt);
        }
    }
}
