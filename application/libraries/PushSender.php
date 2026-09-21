<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once FCPATH . 'vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushSender
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        if (!defined('VAPID_PUBLIC_KEY')) {
            require_once APPPATH . 'config/vapid.php';
        }
    }

    /**
     * Kirim push ke semua device milik satu user.
     * Otomatis hapus subscription yang sudah expired/invalid.
     */
    public function kirim_ke_user($user_id, $title, $body, $url = '')
    {
        $uid = intval($user_id);
        $rows = $this->ci->mymodel->selectWithQuery("SELECT * FROM push_subscriptions WHERE user_id=$uid");
        if (empty($rows)) return;

        $auth = array(
            'VAPID' => array(
                'subject'    => VAPID_SUBJECT,
                'publicKey'  => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ),
        );
        $webPush = new WebPush($auth);

        $payload = json_encode(array(
            'title' => $title,
            'body'  => mb_substr($body, 0, 160),
            'url'   => $url,
        ));

        foreach ($rows as $r) {
            $sub = Subscription::create(array(
                'endpoint' => $r['endpoint'],
                'keys' => array(
                    'p256dh' => $r['p256dh'],
                    'auth'   => $r['auth'],
                ),
            ));
            $webPush->queueNotification($sub, $payload, array(
                'TTL'     => 86400,   // simpan 24 jam kalau HP sedang offline
                'urgency' => 'high',  // paksa kirim segera, jangan tunggu HP bangun sendiri
            ));
        }

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                $endpoint = $report->getRequest()->getUri()->__toString();
                if ($report->isSubscriptionExpired()) {
                    $this->ci->db->where('endpoint', $endpoint)->delete('push_subscriptions');
                }
            }
        }
    }
}
