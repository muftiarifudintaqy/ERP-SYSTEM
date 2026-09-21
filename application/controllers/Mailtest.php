<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mailtest extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('app_mailer');
    }

    public function index()
    {
        $to_email = trim((string) $this->input->get('email', true));
        if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            show_error('Query parameter email tidak valid. Contoh: /mailtest?email=nama@domain.com', 400);
            return;
        }

        $subject = 'Tes SMTP BHSKIN - ' . date('Y-m-d H:i:s');
        $body_html = '
            <p>Halo,</p>
            <p>Ini email test dari endpoint <strong>Mailtest::index()</strong>.</p>
            <p>
                <strong>Waktu server:</strong> ' . htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8') . '<br>
                <strong>Penerima:</strong> ' . htmlspecialchars($to_email, ENT_QUOTES, 'UTF-8') . '
            </p>
        ';

        $sent = $this->app_mailer->send_html($to_email, $subject, $body_html, [
            'from_name' => 'BHSKIN Mail Test',
        ]);

        if (!$sent) {
            $error = $this->app_mailer->get_last_error();
            show_error('Mailtest SMTP gagal. ' . ($error ?: 'Cek konfigurasi SMTP dan log aplikasi.'), 500);
            return;
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo "OK\n";
        echo "recipient: " . $to_email . "\n";
        echo "from: " . $this->app_mailer->get_default_from_email() . "\n";
    }
}
