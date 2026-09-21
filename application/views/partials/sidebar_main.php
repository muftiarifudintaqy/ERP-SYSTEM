<?php
// Get current routing info for active state
$uri_1 = $this->uri->segment(1);
$uri_2 = $this->uri->segment(2);
$uri_3 = $this->uri->segment(3);
$m = $this->input->get('m');

// User and permissions
$user_id = $_SESSION['user']['id'];
$is_super_admin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] == '1';
$is_leave_manager = $is_super_admin || in_array($user_id, ['2', '5', '42']);

// Load permission library
$CI =& get_instance();
$CI->load->library('permission');

// Check permissions (already defined in TemplateDashboard)
// Using $modules_permissions, $can_view_* variables from parent

// Active menu helpers
function check_menu_active($segments, $uri_1, $uri_2 = '', $uri_3 = '') {
    if (is_array($segments)) {
        foreach ($segments as $segment) {
            $parts = explode('/', $segment);
            if ($parts[0] === $uri_1 && (empty($parts[1]) || $parts[1] === $uri_2) && (empty($parts[2]) || $parts[2] === $uri_3)) {
                return true;
            }
        }
    }
    return false;
}

// Define active states for collapsible groups
$marketing_active = in_array($uri_1, ['overview', 'ads', 'influencer', 'influencer-dummy', 'endorse-campaign', 'calendar', 'payment', 'review-endorse', 'endorse', 'codeboost']);
$advertiser_active = ($uri_1 === 'ads');
$endorsement_active = in_array($uri_1, ['influencer', 'influencer-dummy', 'endorse-campaign', 'calendar', 'payment', 'review-endorse', 'endorse', 'codeboost']);
$order_customer_active = in_array($uri_1, ['marketplace-account', 'transaction', 'booking-fbs', 'transaction-item', 'crm', 'group-wa']);
$operasional_active = in_array($uri_1, ['stock', 'product', 'marketplace', 'marketplace-account', 'shipping', 'channel']);
$hr_management_active = in_array($uri_1, ['quest_level', 'position', 'benefit', 'quest', 'milestone', 'recruitment', 'manpower_planning', 'onboarding', 'asset_management', 'user', 'leave', 'contract']);
$talent_acquisition_active = in_array($uri_1, ['recruitment', 'manpower_planning']);
$talent_development_active = in_array($uri_1, ['quest_level', 'benefit', 'quest', 'milestone']);
$leave_section_active = ($uri_1 === 'leave');
$administration_hr_active = in_array($uri_1, ['user', 'position', 'onboarding', 'asset_management', 'contract']);
$akun_active = in_array($uri_1, ['leave', 'roles', 'modules', 'profile']) || ($uri_1 === 'auth' && $uri_2 === 'logout-process');
?>

<!-- Sidebar -->
<aside id="sidebar-main" class="fixed top-0 left-0 z-40 w-60 h-screen transition-transform -translate-x-full md:translate-x-0 bg-white border-r border-gray-200" aria-label="Sidebar">
    <div class="h-full flex flex-col">
        
        <!-- Brand Header -->
        <div class="px-3 py-3 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 bg-gray-900 rounded-md flex items-center justify-center">
                        <span class="text-white font-bold text-xs">BH</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">MONTERA</span>
                </div>
                <!-- Mobile close button -->
                <button type="button" onclick="toggleSidebarMain()" class="md:hidden p-1 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Navigation -->
        <div class="flex-1 overflow-y-auto px-2 py-3">
            <nav class="space-y-1">
                
                <!-- Dashboard -->
                <?php if ($modules_permissions['dashboard']): ?>
                <a href="<?= base_url() ?>dashboard" 
                   class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'dashboard') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <?php endif; ?>

                <!-- Report -->
                <?php if ($modules_permissions['report']): ?>
                <a href="<?= base_url() ?>report"
                   class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'report') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Report</span>
                </a>
                <?php endif; ?>

                <!-- Report Aset -->
                <?php if (!empty($modules_permissions['report_aset'])): ?>
                <a href="<?= base_url() ?>report_aset"
                   class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'report_aset') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span>Aset</span>
                </a>
                <?php endif; ?>

                <!-- Pengeluaran -->
                <?php if ($modules_permissions['expense']): ?>
                <a href="<?= base_url() ?>expense" 
                   class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'expense') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span>Pengeluaran</span>
                </a>
                <?php endif; ?>

                <!-- Separator -->
                <?php if ($can_view_marketing): ?>
                <div class="pt-3 pb-2">
                    <div class="border-t border-gray-200"></div>
                </div>

                <!-- Marketing Section -->
                <div class="pt-2">
                    <div class="px-2 mb-2">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Marketing</span>
                    </div>

                    <!-- Overview -->
                    <?php if ($modules_permissions['overview']): ?>
                    <a href="<?= base_url() ?>overview" 
                       class="flex items-center px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'overview') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <span>Overview</span>
                    </a>
                    <?php endif; ?>

                    <!-- Advertiser Collapsible -->
                    <?php if ($can_view_advertiser): ?>
                    <div class="mt-1">
                        <button type="button" 
                                data-collapse-toggle="advertiser-dropdown-main"
                                class="flex items-center justify-between w-full px-2 py-1.5 text-sm rounded-md transition-colors <?= $advertiser_active ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Advertiser</span>
                            </div>
                            <svg class="w-3.5 h-3.5 transition-transform chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="advertiser-dropdown-main" class="<?= $advertiser_active ? '' : 'hidden' ?> mt-1 ml-3 pl-3 border-l border-gray-200 space-y-0.5">
                            <?php if ($modules_permissions['ads_tiktok']): ?>
                            <a href="<?= base_url() ?>ads?m=tiktok" 
                               class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'ads' && $m === 'tiktok') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                <img src="<?= base_url() ?>assets/img/marketplace/3.png" alt="TikTok" class="w-5 h-5 rounded-full border">
                                <span>TikTok</span>
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['ads_meta']): ?>
                            <a href="<?= base_url() ?>ads?m=meta" 
                               class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'ads' && $m === 'meta') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                <img src="<?= base_url() ?>assets/img/marketplace/5.png" alt="Meta" class="w-5 h-5 rounded-full border">
                                <span>Meta</span>
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['ads_shopee']): ?>
                            <a href="<?= base_url() ?>ads?m=shopee" 
                               class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'ads' && $m === 'shopee') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                <img src="<?= base_url() ?>assets/img/marketplace/1.png" alt="Shopee" class="w-5 h-5 rounded-full border">
                                <span>Shopee</span>
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['ads_lazada']): ?>
                            <a href="<?= base_url() ?>ads?m=lazada" 
                               class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'ads' && $m === 'lazada') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                <img src="<?= base_url() ?>assets/img/marketplace/2.png" alt="Lazada" class="w-5 h-5 rounded-full border">
                                <span>Lazada</span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Endorsement Collapsible -->
                    <?php if ($can_view_endorsement): ?>
                    <div class="mt-1">
                        <button type="button" 
                                data-collapse-toggle="endorsement-dropdown-main"
                                class="flex items-center justify-between w-full px-2 py-1.5 text-sm rounded-md transition-colors <?= $endorsement_active ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                </svg>
                                <span>Endorsement</span>
                            </div>
                            <svg class="w-3.5 h-3.5 transition-transform chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="endorsement-dropdown-main" class="<?= $endorsement_active ? '' : 'hidden' ?> mt-1 ml-3 pl-3 border-l border-gray-200 space-y-0.5">
                            <?php if ($modules_permissions['influencer']): ?>
                            <a href="<?= base_url() ?>influencer" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'influencer') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Influencer
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['influencer_dummy']): ?>
                            <a href="<?= base_url() ?>influencer-dummy" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'influencer-dummy') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Influencer Listing
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['endorse_campaign']): ?>
                            <a href="<?= base_url() ?>endorse-campaign" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= (in_array($uri_1, ['endorse-campaign', 'endorse'])) ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Endorse Campaign
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['calendar']): ?>
                            <a href="<?= base_url() ?>calendar?group_by[]=rencana_at&group_by[]=posting_at" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'calendar') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Endorse Calendar
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['payment']): ?>
                            <a href="<?= base_url() ?>payment" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= (in_array($uri_1, ['payment', 'review-endorse'])) ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Payment & Review
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['codeboost']): ?>
                            <a href="<?= base_url() ?>codeboost" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'codeboost') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Codeboost
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Order & Customer -->
                <?php if ($can_view_order_customer): ?>
                <div class="pt-3 pb-2">
                    <div class="border-t border-gray-200"></div>
                </div>
                <div class="pt-2">
                    <div class="px-2 mb-2">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Order & Customer</span>
                    </div>

                    <?php if ($modules_permissions['transaction']): ?>
                    <a href="<?= base_url() ?>transaction" 
                       class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'transaction') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span>Order</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($modules_permissions['booking_fbs']): ?>
                    <a href="<?= base_url() ?>booking-fbs" 
                       class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'booking-fbs') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                        <span>Reservasi Shopee</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($modules_permissions['transaction_item']): ?>
                    <a href="<?= base_url() ?>transaction-item" 
                       class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'transaction-item') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                        </svg>
                        <span>Order Item</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($modules_permissions['crm_mg']): ?>
                    <a href="<?= base_url() ?>crm" 
                       class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'crm') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        <span>CRM</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($modules_permissions['group_wa']): ?>
                    <a href="<?= base_url() ?>group-wa" 
                       class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'group-wa') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        <span>Grup WA</span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Operasional -->
                <?php if ($can_view_operasional): ?>
                <div class="pt-3 pb-2">
                    <div class="border-t border-gray-200"></div>
                </div>
                <div class="pt-2">
                    <div class="px-2 mb-2">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Operasional</span>
                    </div>

                    <?php if ($modules_permissions['stock']): ?>
                    <a href="<?= base_url() ?>stock" 
                       class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'stock') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                        </svg>
                        <span>Stok</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($modules_permissions['product'] || $modules_permissions['marketplace-account']): ?>
                    <a href="<?= base_url() ?>product" 
                       class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'product') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>Konfigurasi</span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- HR Management -->
                <?php if ($can_view_hr_management): ?>
                <div class="pt-3 pb-2">
                    <div class="border-t border-gray-200"></div>
                </div>
                <div class="pt-2">
                    <div class="px-2 mb-2">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">HR Management</span>
                    </div>

                    <!-- Talent Acquisition -->
                    <?php if ($can_view_talent_acquisition): ?>
                    <div class="mt-1">
                        <button type="button" 
                                data-collapse-toggle="talent-acquisition-dropdown"
                                class="flex items-center justify-between w-full px-2 py-1.5 text-sm rounded-md transition-colors <?= $talent_acquisition_active ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span>Talent Acquisition</span>
                            </div>
                            <svg class="w-3.5 h-3.5 transition-transform chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="talent-acquisition-dropdown" class="<?= $talent_acquisition_active ? '' : 'hidden' ?> mt-1 ml-3 pl-3 border-l border-gray-200 space-y-0.5">
                            <?php if ($modules_permissions['recruitment']): ?>
                            <a href="<?= base_url() ?>recruitment" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'recruitment') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Recruitment
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['manpower_planning']): ?>
                            <a href="<?= base_url() ?>manpower_planning" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'manpower_planning') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Manpower Planning
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Talent Development -->
                    <?php if ($can_view_talent_development): ?>
                    <div class="mt-1">
                        <button type="button" 
                                data-collapse-toggle="talent-development-dropdown"
                                class="flex items-center justify-between w-full px-2 py-1.5 text-sm rounded-md transition-colors <?= $talent_development_active ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                <span>Talent Development</span>
                            </div>
                            <svg class="w-3.5 h-3.5 transition-transform chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="talent-development-dropdown" class="<?= $talent_development_active ? '' : 'hidden' ?> mt-1 ml-3 pl-3 border-l border-gray-200 space-y-0.5">
                            <?php if ($modules_permissions['quest_level']): ?>
                            <a href="<?= base_url() ?>quest_level" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'quest_level') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Quest Levels
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['benefit']): ?>
                            <a href="<?= base_url() ?>benefit" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'benefit') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Benefits
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['quest']): ?>
                            <a href="<?= base_url() ?>quest" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'quest') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Quest Management
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['milestone']): ?>
                            <a href="<?= base_url() ?>milestone" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'milestone') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Milestone & Leaderboard
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Attendance Management -->
                    <?php if ($is_leave_manager): ?>
                    <div class="mt-1">
                        <button type="button" 
                                data-collapse-toggle="attendance-dropdown"
                                class="flex items-center justify-between w-full px-2 py-1.5 text-sm rounded-md transition-colors <?= $leave_section_active ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Attendance</span>
                            </div>
                            <svg class="w-3.5 h-3.5 transition-transform chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="attendance-dropdown" class="<?= $leave_section_active ? '' : 'hidden' ?> mt-1 ml-3 pl-3 border-l border-gray-200 space-y-0.5">
                            <a href="<?= base_url() ?>leave" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'leave' && empty($uri_2)) ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Leave Management
                            </a>
                            <a href="<?= base_url() ?>leave/events" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'leave' && $uri_2 === 'events') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Calendar Event
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Administration HR -->
                    <?php if ($can_view_administration_hr): ?>
                    <div class="mt-1">
                        <button type="button" 
                                data-collapse-toggle="administration-hr-dropdown"
                                class="flex items-center justify-between w-full px-2 py-1.5 text-sm rounded-md transition-colors <?= $administration_hr_active ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Administration HR</span>
                            </div>
                            <svg class="w-3.5 h-3.5 transition-transform chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="administration-hr-dropdown" class="<?= $administration_hr_active ? '' : 'hidden' ?> mt-1 ml-3 pl-3 border-l border-gray-200 space-y-0.5">
                            <?php if ($modules_permissions['user']): ?>
                            <a href="<?= base_url() ?>user" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'user') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Database Management
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['position']): ?>
                            <a href="<?= base_url() ?>position" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'position') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Position Management
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['user']): ?>
                            <a href="<?= base_url() ?>onboarding" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'onboarding') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Onboarding & Offboarding
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($modules_permissions['contract'])): ?>
                            <a href="<?= base_url() ?>contract"
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'contract') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Riwayat Kontrak
                            </a>
                            <?php endif; ?>
                            <?php if ($modules_permissions['asset_management']): ?>
                            <a href="<?= base_url() ?>asset_management"
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'asset_management') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Manajemen Aset
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Akun -->
                <?php if ($can_view_akun): ?>
                <div class="pt-3 pb-2">
                    <div class="border-t border-gray-200"></div>
                </div>
                <div class="pt-2">
                    <div class="px-2 mb-2">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Akun</span>
                    </div>

                    <a href="<?= base_url() ?>attendance/me"
                       class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'attendance' && $uri_2 === 'me') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0 0v2.5m0-2.5h2.5M7 14H4.5M14 7a4 4 0 11-8 0 4 4 0 018 0zm6.5 13a8.5 8.5 0 00-8.5-8.5"/>
                        </svg>
                        <span>Absensi</span>
                    </a>

                    <a href="<?= base_url() ?>leave/request"
                       class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'leave' && $uri_2 === 'request') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <span>Leave Request</span>
                    </a>

                    <?php if ($modules_permissions['roles']): ?>
                    <a href="<?= base_url() ?>roles" 
                       class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'roles') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span>Role Management</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($modules_permissions['modules']): ?>
                    <a href="<?= base_url() ?>modules" 
                       class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'modules') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>Modules & Permissions</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($modules_permissions['profile']): ?>
                    <a href="<?= base_url() ?>profile" 
                       class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($uri_1 === 'profile') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Akun Saya</span>
                    </a>
                    <?php endif; ?>

                    <a href="<?= base_url() ?>auth/logout-process" 
                       class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors text-gray-700 hover:bg-gray-50">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Keluar</span>
                    </a>
                </div>
                <?php endif; ?>

            </nav>
        </div>

        <!-- User Profile (Bottom) -->
        <div class="p-2 border-t border-gray-200">
            <div class="flex items-center space-x-2 px-2 py-1.5">
                <div class="w-7 h-7 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-white font-semibold text-xs">
                        <?php 
                        $user_name = isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'User';
                        $initials = strtoupper(substr($user_name, 0, 1));
                        echo $initials;
                        ?>
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                        <?= isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'User Name' ?>
                    </p>
                    <p class="text-xs text-gray-500 truncate">
                        <?= isset($_SESSION['user']['role_text']) ? $_SESSION['user']['role_text'] : 'Admin' ?>
                    </p>
                </div>
            </div>
        </div>

    </div>
</aside>

<!-- Mobile Overlay -->
<div id="sidebar-overlay-main" class="fixed inset-0 z-30 bg-gray-900/50 hidden md:hidden" onclick="toggleSidebarMain()"></div>

<script>
// Toggle sidebar for mobile
function toggleSidebarMain() {
    const sidebar = document.getElementById('sidebar-main');
    const overlay = document.getElementById('sidebar-overlay-main');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

// Rotate chevron icons on collapse toggle
document.addEventListener('DOMContentLoaded', function() {
    const collapseButtons = document.querySelectorAll('[data-collapse-toggle]');
    
    collapseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-collapse-toggle');
            const target = document.getElementById(targetId);
            const chevron = this.querySelector('.chevron-icon');
            
            if (target && chevron) {
                if (target.classList.contains('hidden')) {
                    target.classList.remove('hidden');
                    chevron.style.transform = 'rotate(180deg)';
                } else {
                    target.classList.add('hidden');
                    chevron.style.transform = 'rotate(0deg)';
                }
            }
        });
        
        // Set initial chevron state
        const targetId = button.getAttribute('data-collapse-toggle');
        const target = document.getElementById(targetId);
        const chevron = button.querySelector('.chevron-icon');
        if (target && !target.classList.contains('hidden') && chevron) {
            chevron.style.transform = 'rotate(180deg)';
        }
    });
});
</script>
