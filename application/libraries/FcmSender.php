<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pengirim notifikasi lewat Firebase Cloud Messaging (HTTP v1).
 * Butuh file service account JSON dari Firebase Console.
 */
class FcmSender
{
    private $ci;
    private $akun;      // isi service account
    private $berkas;    // lokasi file service account
    private $cacheToken = '/tmp/fcm_access_token.json';

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->berkas = APPPATH . 'config/fcm-service-account.json';
        if (is_file($this->berkas)) {
            $this->akun = json_decode(file_get_contents($this->berkas), true);
        }
    }

    public function siap()
    {
        return !empty($this->akun['client_email']) && !empty($this->akun['private_key']) && !empty($this->akun['project_id']);
    }

    /** Ambil access token OAuth2 (dicache sampai hampir kedaluwarsa). */
    private function accessToken()
    {
        if (is_file($this->cacheToken)) {
            $c = json_decode(file_get_contents($this->cacheToken), true);
            if (!empty($c['token']) && !empty($c['exp']) && time() < ($c['exp'] - 120)) {
                return $c['token'];
            }
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claim  = [
            'iss'   => $this->akun['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ];

        $b64 = function ($d) { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); };
        $isi = $b64(json_encode($header)) . '.' . $b64(json_encode($claim));

        $tandaTangan = '';
        $kunci = openssl_pkey_get_private($this->akun['private_key']);
        if (!$kunci) { log_message('error', 'FCM: private key tidak valid'); return null; }
        openssl_sign($isi, $tandaTangan, $kunci, 'SHA256');
        $jwt = $isi . '.' . $b64($tandaTangan);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
        ]);
        $res = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (empty($res['access_token'])) {
            log_message('error', 'FCM: gagal ambil access token - ' . json_encode($res));
            return null;
        }

        @file_put_contents($this->cacheToken, json_encode([
            'token' => $res['access_token'],
            'exp'   => time() + intval($res['expires_in'] ?? 3600),
        ]));
        @chmod($this->cacheToken, 0600);

        return $res['access_token'];
    }

    /** Kirim ke semua perangkat milik satu user. */
    public function kirim($user_id, $judul, $isi, $url = '/', $jenis = 'umum')
    {
        if (!$this->siap()) { return false; }

        $uid  = intval($user_id);
        $rows = $this->ci->db->select('id, token')
            ->where('user_id', $uid)->where('is_active', 1)
            ->get('fcm_tokens')->result_array();
        if (empty($rows)) { return false; }

        $akses = $this->accessToken();
        if (!$akses) { return false; }

        $endpoint = 'https://fcm.googleapis.com/v1/projects/' . $this->akun['project_id'] . '/messages:send';
        $terkirim = 0;

        foreach ($rows as $r) {
            $pesan = [
                'message' => [
                    'token' => $r['token'],
                    'notification' => [
                        'title' => (string) $judul,
                        'body'  => mb_substr((string) $isi, 0, 200),
                    ],
                    'data' => [
                        'url'   => (string) $url,
                        'jenis' => (string) $jenis,
                    ],
                    'android' => [
                        'priority' => 'HIGH',
                        'ttl'      => '86400s',
                        'notification' => [
                            'channel_id'   => 'montera_penting',
                            'sound'        => 'default',
                            'default_vibrate_timings' => true,
                            'notification_priority'   => 'PRIORITY_MAX',
                        ],
                    ],
                ],
            ];

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($pesan),
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $akses,
                    'Content-Type: application/json',
                ],
            ]);
            $body = curl_exec($ch);
            $kode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($kode >= 200 && $kode < 300) {
                $terkirim++;
                $this->ci->db->where('id', $r['id'])->update('fcm_tokens', ['last_seen_at' => date('Y-m-d H:i:s')]);
            } elseif ($kode == 404 || $kode == 400) {
                // token sudah tidak berlaku
                $this->ci->db->where('id', $r['id'])->update('fcm_tokens', ['is_active' => 0]);
                log_message('error', 'FCM token nonaktif: ' . $body);
            } else {
                log_message('error', 'FCM gagal (' . $kode . '): ' . $body);
            }
        }

        return $terkirim;
    }
}
