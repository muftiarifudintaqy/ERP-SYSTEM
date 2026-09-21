<?php
defined('BASEPATH') or exit('No direct script access allowed');

class App_mailer
{
    private $ci;
    private $last_error = '';

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->config('email');
        $this->ci->load->library('email');
    }

    public function send_html($to_email, $subject, $body_html, array $options = [])
    {
        $this->last_error = '';

        if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            $this->last_error = 'Invalid recipient email: ' . $to_email;
            log_message('error', 'App mailer skipped invalid recipient: ' . $to_email);
            return false;
        }

        $config = $this->get_config();
        if (empty($config['transport']['smtp_pass'])) {
            $this->last_error = 'SMTP password is not configured.';
            log_message('error', 'App mailer SMTP password is not configured.');
            return false;
        }

        $from_email = trim((string) ($options['from_email'] ?? $config['from_email']));
        $from_name = trim((string) ($options['from_name'] ?? $config['from_name']));
        $reply_to_email = trim((string) ($options['reply_to_email'] ?? $config['reply_to_email']));
        $reply_to_name = trim((string) ($options['reply_to_name'] ?? $from_name));
        $attachments = isset($options['attachments']) && is_array($options['attachments']) ? $options['attachments'] : [];

        if (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
            $this->last_error = 'Sender email is not valid: ' . $from_email;
            log_message('error', 'App mailer sender email is not valid: ' . $from_email);
            return false;
        }

        $temp_files = [];

        try {
            $this->ci->email->clear(true);
            $this->ci->email->initialize($config['transport']);
            $this->ci->email->set_newline("\r\n");
            $this->ci->email->set_crlf("\r\n");
            $this->ci->email->from($from_email, $from_name);

            if (filter_var($reply_to_email, FILTER_VALIDATE_EMAIL)) {
                $this->ci->email->reply_to($reply_to_email, $reply_to_name);
            }

            $this->ci->email->to($to_email);
            $this->ci->email->subject($subject);
            $this->ci->email->message($body_html);

            foreach ($attachments as $attachment) {
                $temp_file = $this->create_temp_attachment($attachment);
                if ($temp_file === null) {
                    continue;
                }

                $temp_files[] = $temp_file['path'];
                $this->ci->email->attach(
                    $temp_file['path'],
                    'attachment',
                    $temp_file['name'],
                    $temp_file['mime']
                );
            }

            if ($this->ci->email->send()) {
                $this->cleanup_temp_files($temp_files);
                return true;
            }

            $this->last_error = trim($this->ci->email->print_debugger(['headers']));
            log_message('error', 'App mailer send failed: ' . $this->last_error);
        } catch (Throwable $e) {
            $this->last_error = $e->getMessage();
            log_message('error', 'App mailer exception: ' . $e->getMessage());
        }

        $this->cleanup_temp_files($temp_files);
        return false;
    }

    public function get_default_from_email()
    {
        $config = $this->get_config();
        return $config['from_email'];
    }

    public function get_last_error()
    {
        return $this->last_error;
    }

    private function get_config()
    {
        return [
            'from_email' => (string) ($this->ci->config->item('smtp_from_email') ?: 'noreply@notif.bhskin.co.id'),
            'from_name' => (string) ($this->ci->config->item('smtp_from_name') ?: 'BHSKIN Notification'),
            'reply_to_email' => (string) ($this->ci->config->item('smtp_reply_to_email') ?: ''),
            'transport' => [
                'protocol' => (string) ($this->ci->config->item('protocol') ?: 'smtp'),
                'smtp_host' => (string) ($this->ci->config->item('smtp_host') ?: 'smtp.resend.com'),
                'smtp_user' => (string) ($this->ci->config->item('smtp_user') ?: 'resend'),
                'smtp_pass' => (string) ($this->ci->config->item('smtp_pass') ?: ''),
                'smtp_port' => intval($this->ci->config->item('smtp_port') ?: 465),
                'smtp_crypto' => (string) ($this->ci->config->item('smtp_crypto') ?: 'ssl'),
                'smtp_timeout' => intval($this->ci->config->item('smtp_timeout') ?: 10),
                'mailtype' => (string) ($this->ci->config->item('mailtype') ?: 'html'),
                'charset' => (string) ($this->ci->config->item('charset') ?: 'utf-8'),
                'newline' => "\r\n",
                'crlf' => "\r\n",
                'wordwrap' => true,
                'validate' => true,
            ],
        ];
    }

    private function create_temp_attachment(array $attachment)
    {
        $content = isset($attachment['content']) ? $attachment['content'] : null;
        $name = trim((string) ($attachment['name'] ?? 'attachment.bin'));
        $mime = trim((string) ($attachment['mime'] ?? 'application/octet-stream'));

        if ($content === null || $name === '') {
            return null;
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $temp_path = tempnam(sys_get_temp_dir(), 'bhs_mail_');
        if ($temp_path === false) {
            return null;
        }

        if ($extension !== '') {
            $renamed_path = $temp_path . '.' . preg_replace('/[^A-Za-z0-9]/', '', $extension);
            if (@rename($temp_path, $renamed_path)) {
                $temp_path = $renamed_path;
            }
        }

        if (file_put_contents($temp_path, $content) === false) {
            @unlink($temp_path);
            return null;
        }

        return [
            'path' => $temp_path,
            'name' => $name,
            'mime' => $mime,
        ];
    }

    private function cleanup_temp_files(array $temp_files)
    {
        foreach ($temp_files as $temp_file) {
            if (is_string($temp_file) && is_file($temp_file)) {
                @unlink($temp_file);
            }
        }
    }
}
