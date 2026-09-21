<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['telegram_bot_token'] = app_env('TELEGRAM_BOT_TOKEN');
$config['telegram_group_chat_id'] = app_env('TELEGRAM_GROUP_CHAT_ID');
$config['telegram_parse_mode'] = app_env('TELEGRAM_PARSE_MODE', 'HTML');
