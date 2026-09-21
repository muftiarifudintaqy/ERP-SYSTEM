<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['protocol'] = app_env('SMTP_PROTOCOL', 'smtp');
$config['smtp_host'] = app_env('SMTP_HOST');
$config['smtp_user'] = app_env('SMTP_USER');
$config['smtp_pass'] = app_env('SMTP_PASS', app_env('RESEND_API_KEY'));
$config['smtp_port'] = intval(app_env('SMTP_PORT', 465));
$config['smtp_crypto'] = app_env('SMTP_CRYPTO', 'ssl');
$config['mailtype'] = 'html';
$config['charset'] = 'utf-8';
$config['newline'] = "\r\n";
$config['smtp_timeout'] = 10;
$config['wordwrap'] = TRUE;
$config['smtp_from_email'] = app_env('SMTP_FROM_EMAIL');
$config['smtp_from_name'] = app_env('SMTP_FROM_NAME');
$config['smtp_reply_to_email'] = app_env('SMTP_REPLY_TO_EMAIL');
