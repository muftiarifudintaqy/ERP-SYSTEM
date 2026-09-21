<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

$route['auth'] = 'auth';
$route['login'] = 'auth/login';
$route['signup'] = 'auth/signup';
$route['signup-process'] = 'auth/signup_process';
$route['forgot-password-process'] = 'auth/forgot_password_process';

$route['privacy-policy'] = 'page/privacy_policy';

$route['default_controller'] = 'home/index';
$route['404_override'] = 'page/error';
$route['translate_uri_dashes'] = TRUE;

// NEW

$route['api/marketplace/callback/tiktok'] = 'Api_v2/marketplace_callback_tiktok';
$route['api/marketplace/callback/shopee'] = 'Api_v2/marketplace_callback_shopee';
$route['api/marketplace/callback/shopee-analytics'] = 'Api_v2/marketplace_callback_shopee_analytics';
$route['api/marketplace/callback/lazada'] = 'Api_v2/marketplace_callback_lazada';
$route['api/marketplace/token/refresh'] = 'Api_v2/marketplace_token_refresh';
$route['api/marketplace/order'] = 'Api_v2/marketplace_order';
$route['api/marketplace/order/detail'] = 'Api_v2/marketplace_order_detail';
$route['api/marketplace/product'] = 'Api_v2/marketplace_product';
$route['api/marketplace/webhook/refresh'] = 'Api_v2/marketplace_webhook_refresh';
$route['api/marketplace/webhook/reset'] = 'Api_v2/marketplace_webhook_reset';
$route['api/fonnte/message-status'] = 'Api_v3/fonnte_message_status';
$route['api/marketplace/order/tracking'] = 'Api_v2/marketplace_order_tracking';
$route['api/marketplace/order/download'] = 'Api_v2/marketplace_order_download';
$route['api/tts_ship_packages_bulk'] = 'Api_v2/tts_ship_packages_bulk';
$route['api/tts_get_shipping_documents_bulk'] = 'Api_v2/tts_get_shipping_documents_bulk';
$route['api/shopee_mass_ship_order_bulk'] = 'Api_v2/shopee_mass_ship_order_bulk';
$route['api/lazada_ship_order'] = 'Api_v2/lazada_ship_order';
$route['api/lazada_get_shipping_document'] = 'Api_v2/lazada_get_shipping_document';
$route['api/shopee_ship_booking_bulk'] = 'Api_v2/shopee_ship_booking_bulk';
$route['api/shopee_get_shipping_document_bulk'] = 'Api_v2/shopee_get_shipping_document_bulk';
$route['api/shopee_get_booking_document_bulk'] = 'Api_v2/shopee_get_booking_document_bulk';
$route['api/tiktok/product_analytics'] = 'Api_v2/tiktok_product_analytics';
$route['booking-fbs/filter-values'] = 'Booking_fbs/filter_values';

$route['kpi'] = 'Kpi/index';
$route['kpi/update-row'] = 'Kpi/update_row';
$route['kpi/campaign-options'] = 'Kpi/campaign_options';
$route['kpi/template-rows'] = 'Kpi/template_rows';
$route['kpi/save-template'] = 'Kpi/save_template';
$route['kpi/delete-position-template'] = 'Kpi/delete_position_template';
$route['kpi/save-run'] = 'Kpi/save_run';
$route['kpi/toggle-run-lock'] = 'Kpi/toggle_run_lock';
$route['kpi/save-preset'] = 'Kpi/save_preset';
$route['kpi/save-global-preset'] = 'Kpi/save_global_preset';
$route['crm/kpi-logs'] = 'Crm/kpi_logs';
$route['crm/save-kpi-logs'] = 'Crm/save_kpi_logs';

// Announcement Popup / In-App Banner
$route['announcement'] = 'Announcement/index';
$route['announcement/item'] = 'Announcement/item';
$route['announcement/create_page'] = 'Announcement/create_page';
$route['announcement/store'] = 'Announcement/store';
$route['announcement/edit_page'] = 'Announcement/edit_page';
$route['announcement/update'] = 'Announcement/update';
$route['announcement/detail'] = 'Announcement/detail';
$route['announcement/remove'] = 'Announcement/remove';
$route['announcement/delete'] = 'Announcement/delete';
$route['announcement/get-active'] = 'Announcement/get_active';
$route['announcement/mark-seen'] = 'Announcement/mark_seen';

$route['api/cronjob/endorse-campaign'] = 'Api_v2/cronjob_endorse_campaign';
$route['api/cronjob/endorse'] = 'Api_v2/cronjob_endorse';
$route['api/cronjob/endorse-threads'] = 'Api_v2/cronjob_endorse_threads';
$route['api/cronjob/influencer'] = 'Api_v2/cronjob_influencer';
$route['api/cronjob/influencer-threads'] = 'Api_v2/cronjob_influencer_threads';
$route['api/cronjob/ads-balance'] = 'Api_v2/cronjob_ads_balance';
$route['api/ads-balance/meta'] = 'Api_v2/ads_balance_meta';
$route['api/ads-balance/tiktok'] = 'Api_v2/ads_balance_tiktok';
$route['api/ads-balance/shopee'] = 'Api_v2/ads_balance_shopee';
$route['api/cronjob/marketplace-unreleased-balance'] = 'Api_v2/cronjob_marketplace_unreleased_balance';

$route['api/webhook'] = 'Api_v2/webhook';
$route['api/customer/summary'] = 'Api_v2/customer_summary';
$route['cronjob/expense'] = 'Expense/generate_recurring_expense';
$route['cronjob/contract-expiry'] = 'Contract/cron_contract_expiry';

$route['endorse/action_generate_mou_pdf_gdocs'] = 'googlemou/action_generate_mou_pdf'; 
$route['googlemou/oauth2callback']              = 'googlemou/oauth2callback';
$route['googlemou']                             = 'googlemou/index';


// Forecast API
$route['api/forecast/stock-current']    = 'Api_forecast/stock_current';
$route['api/forecast/sales-history']    = 'Api_forecast/sales_history';
$route['api/forecast/marketing-impact'] = 'Api_forecast/marketing_impact';
$route['api/forecast/endorse-traffic']  = 'Api_forecast/endorse_traffic';

// OLD

$route['profile'] = 'Profile/index';
$route['profile/update-process'] = 'Profile/update_process';
$route['profile/update-transition-task'] = 'Profile/update_transition_task';
$route['profile/quest-history'] = 'Profile/quest_history';
$route['profile/apply-main-quest'] = 'Profile/apply_main_quest';
$route['profile/apply-side-quest'] = 'Profile/apply_side_quest';

$route['onboarding'] = 'Onboarding/index';
$route['onboarding/detail'] = 'Onboarding/detail';
$route['onboarding/task-settings'] = 'Onboarding/task_settings';
$route['onboarding/add-employees'] = 'Onboarding/add_employees';
$route['onboarding/save-task-item'] = 'Onboarding/save_task_item';
$route['onboarding/save-task-settings'] = 'Onboarding/save_task_settings';
$route['onboarding/delete-task-item'] = 'Onboarding/delete_task_item';
$route['onboarding/remove-employee'] = 'Onboarding/remove_employee';
$route['onboarding/add-custom-task'] = 'Onboarding/add_custom_task';
$route['onboarding/update-detail-task-status'] = 'Onboarding/update_detail_task_status';

$route['api'] = 'Api/index';
$route['api/refresh-order'] = 'Api/refresh_order';
$route['api/refresh-customer'] = 'Api/refresh_customer';
$route['api/reset-webhook'] = 'Api/reset_webhook';
$route['api/get-order'] = 'Api/get_order';
$route['api/get-order-detail'] = 'Api/get_order_detail';
$route['cronjob/remove-duplicate-order'] = 'Api/remove_duplicate_order_v2';
$route['api/fcm/daftar-token'] = 'Fcm/daftar';
$route['api/fcm/hapus-token'] = 'Fcm/hapus';
$route['api/update-order'] = 'Api/update_order';

$route['api/cronjob-order'] = 'Api/cronjob_order';
$route['api/cronjob-finance'] = 'Api/cronjob_finance';
$route['api/cronjob-endorse-campaign'] = 'Api/cronjob_endorse_campaign';
$route['api/cronjob-endorse'] = 'Api/cronjob_endorse';
$route['api/cronjob-influencer'] = 'Api/cronjob_influencer';

$route['api/auth/shopee'] = 'Api/auth_shopee';
$route['api/auth/tiktok'] = 'Api/auth_tiktok';
$route['api/auth/lazada'] = 'Api/auth_lazada';

$route['api/auth/marketplace/shopee'] = 'Api/auth_marketplace_shopee';
$route['api/auth/refresh-token/shopee'] = 'Api/shopee_refresh_token';

$route['api/auth/marketplace/lazada'] = 'Api/auth_marketplace_lazada';
$route['api/auth/refresh-token/lazada'] = 'Api/lazada_refresh_token';

$route['api/auth/marketplace/tiktok'] = 'Api/auth_marketplace_tiktok';
$route['api/auth/refresh-token/tiktok'] = 'Api/tiktok_refresh_token';

$route['api/shopee/get-product'] = 'Api/shopee_get_product';
$route['api/shopee/get-order'] = 'Api/shopee_get_order';
$route['api/shopee/get-finance'] = 'Api/shopee_get_finance';

$route['api/lazada/get-product'] = 'Api/lazada_get_product';
$route['api/lazada/get-order'] = 'Api/lazada_get_order';
$route['api/lazada/get-finance'] = 'Api/lazada_get_finance';

$route['api/tiktok/get-product'] = 'Api/tiktok_get_product';
$route['api/tiktok/get-order'] = 'Api/tiktok_get_order';
$route['api/tiktok/get-finance'] = 'Api/tiktok_get_finance';

// $route['api/webhook'] = 'Api/webhook';
$route['api/webhook-api'] = 'Api/webhook_api';
$route['api/webhook-test'] = 'Api/webhook_test';

// v3
$route['api/marketplace/ads'] = 'Api_v3/marketplace_ads';
$route['api/shopee/product-performance'] = 'Api_v3/get_product_performance';
$route['api/shopee/product-campaign'] = 'Api_v3/shopee_product_campaign';
$route['api/meta/product-campaign'] = 'Api_v3/meta_product_campaign';
$route['api/meta/whatsapp/webhook'] = 'Api_v3/meta_whatsapp_webhook';
$route['api/tiktok/campaign'] = 'Api_v3/get_tiktok_campaign';
$route['api/tiktok/gmv-campaign'] = 'Api_v3/tiktok_product_campaign';
$route['api/tiktok/gmv'] = 'Api_v3/get_tiktok_gmv';
$route['auth/redirect'] = 'TiktokAuth/redirect_to_auth';
$route['auth/callback'] = 'TiktokAuth/callback';        
$route['cronjob/expense'] = 'Api_v3/generate_recurring_expense';
$route['cronjob/sync-product'] = 'Api_v3/sync_all_product';
$route['cronjob/tiktok-bc-advertiser-sync'] = 'Api_v3/sync_tiktok_bc_advertisers';
$route['cronjob/tiktok-bc-advertiser-reset'] = 'Api_v3/reset_tiktok_bc_advertisers';
/* =====================================================================
   TEMPEL ISI DI BAWAH INI ke bagian bawah application/config/routes.php
   (jangan sertakan tag <?php di atas)
   ===================================================================== */

// Halaman pengisian publik - tanpa login
$route['f/selesai']        = 'formulir/selesai';
$route['f/data-karyawan']  = 'formulir/karyawan';
$route['f/inventaris']     = 'formulir/inventaris';
$route['f/disc']           = 'formulir/disc';
$route['f/(:any)']         = 'formulir/isi/$1';

// Area HRD (butuh login) - CodeIgniter sudah otomatis memetakan
// hrd/karyawan, hrd/karyawan_detail/12, dst. Baris di bawah cuma jalan pintas.
$route['hrd']              = 'hrd/index';
