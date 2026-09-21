<?php
// Get current routing info for active state
$current_class = $this->router->fetch_class();
$current_method = $this->router->fetch_method();
$seg1 = $this->uri->segment(1);
$seg2 = $this->uri->segment(2);

// Helper function to check if link is active
function is_active($class, $method = 'index', $current_class, $current_method) {
    return ($current_class === $class && $current_method === $method);
}

// Helper function to check if parent is active (for collapsible groups)
function parent_active($routes, $seg1, $seg2) {
    foreach ($routes as $route) {
        $parts = explode('/', trim($route, '/'));
        if (count($parts) >= 2 && $parts[0] === $seg1 && (empty($seg2) || $parts[1] === $seg2)) {
            return true;
        }
    }
    return false;
}

// Check if advertiser group is active
$advertiser_active = parent_active([
    'marketing/advertiser',
    'marketing/advertiser_campaign'
], $seg1, $seg2);

// Check if advertiser campaign nested group is active
$advertiser_campaign_active = parent_active([
    'marketing/advertiser_campaign'
], $seg1, $seg2);

// Check if endorsement group is active
$endorsement_active = parent_active([
    'marketing/influencer',
    'marketing/influencer_listing',
    'marketing/endorse_campaign',
    'marketing/endorse_calendar'
], $seg1, $seg2);
?>

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 z-40 w-60 h-screen transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
    <div class="h-full flex flex-col bg-white border-r border-gray-200">
        
        <!-- Brand Header -->
        <div class="px-3 py-3 border-b border-gray-200">
            <div class="flex items-center space-x-2">
                <div class="w-7 h-7 bg-gray-900 rounded-md flex items-center justify-center">
                    <span class="text-white font-bold text-xs">BH</span>
                </div>
                <span class="text-sm font-semibold text-gray-900">MONTERA</span>
            </div>
        </div>

        <!-- Navigation -->
        <div class="flex-1 overflow-y-auto px-2 py-3">
            <nav class="space-y-1">
                
                <!-- Top Level Items -->
                <a href="<?= site_url('dashboard') ?>" 
                   class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($current_class === 'dashboard') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="<?= site_url('report') ?>" 
                   class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($current_class === 'report') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Report</span>
                </a>

                <a href="<?= site_url('pengeluaran') ?>" 
                   class="flex items-center space-x-2 px-2 py-1.5 text-sm rounded-md transition-colors <?= ($current_class === 'pengeluaran') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span>Pengeluaran</span>
                </a>

                <!-- Separator -->
                <div class="pt-3 pb-2">
                    <div class="border-t border-gray-200"></div>
                </div>

                <!-- Marketing Section -->
                <div class="pt-2">
                    <div class="px-2 mb-2">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Marketing</span>
                    </div>

                    <!-- Marketing Overview -->
                    <a href="<?= site_url('marketing/overview') ?>" 
                       class="flex items-center px-2 py-1.5 text-sm rounded-md transition-colors <?= ($seg1 === 'marketing' && $seg2 === 'overview') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <span>Overview</span>
                    </a>

                    <!-- Advertiser Collapsible -->
                    <div class="mt-1">
                        <button type="button" 
                                data-collapse-toggle="advertiser-dropdown"
                                class="flex items-center justify-between w-full px-2 py-1.5 text-sm rounded-md transition-colors <?= $advertiser_active ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Advertiser</span>
                            </div>
                            <svg class="w-3.5 h-3.5 transition-transform" id="advertiser-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="advertiser-dropdown" class="<?= $advertiser_active ? '' : 'hidden' ?> mt-1 ml-3 pl-3 border-l border-gray-200 space-y-0.5">
                            
                            <!-- Direct Link -->
                            <a href="<?= site_url('marketing/advertiser') ?>" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($seg1 === 'marketing' && $seg2 === 'advertiser' && empty($this->uri->segment(3))) ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Overview
                            </a>
                            
                            <!-- Nested Collapse: Campaign -->
                            <div>
                                <button type="button" 
                                        data-collapse-toggle="advertiser-campaign-dropdown"
                                        class="flex items-center justify-between w-full px-2 py-1.5 text-sm rounded-md transition-colors <?= $advertiser_campaign_active ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                    <span>Campaign</span>
                                    <svg class="w-3 h-3 transition-transform" id="advertiser-campaign-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                
                                <!-- Level 3: Sub-items -->
                                <div id="advertiser-campaign-dropdown" class="<?= $advertiser_campaign_active ? '' : 'hidden' ?> mt-0.5 ml-3 pl-3 border-l border-gray-200 space-y-0.5">
                                    <a href="<?= site_url('marketing/advertiser_campaign/list') ?>" 
                                       class="block px-2 py-1 text-sm rounded-md transition-colors <?= ($seg2 === 'advertiser_campaign' && $this->uri->segment(3) === 'list') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                        List
                                    </a>
                                    <a href="<?= site_url('marketing/advertiser_campaign/create') ?>" 
                                       class="block px-2 py-1 text-sm rounded-md transition-colors <?= ($seg2 === 'advertiser_campaign' && $this->uri->segment(3) === 'create') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                        Create
                                    </a>
                                    <a href="<?= site_url('marketing/advertiser_campaign/analytics') ?>" 
                                       class="block px-2 py-1 text-sm rounded-md transition-colors <?= ($seg2 === 'advertiser_campaign' && $this->uri->segment(3) === 'analytics') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                        Analytics
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Direct Link -->
                            <a href="<?= site_url('marketing/advertiser/settings') ?>" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($seg1 === 'marketing' && $seg2 === 'advertiser' && $this->uri->segment(3) === 'settings') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Settings
                            </a>
                        </div>
                    </div>

                    <!-- Endorsement Collapsible -->
                    <div class="mt-1">
                        <button type="button" 
                                data-collapse-toggle="endorsement-dropdown"
                                class="flex items-center justify-between w-full px-2 py-1.5 text-sm rounded-md transition-colors <?= $endorsement_active ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                </svg>
                                <span>Endorsement</span>
                            </div>
                            <svg class="w-3.5 h-3.5 transition-transform" id="endorsement-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="endorsement-dropdown" class="<?= $endorsement_active ? '' : 'hidden' ?> mt-1 ml-3 pl-3 border-l border-gray-200 space-y-0.5">
                            <a href="<?= site_url('marketing/influencer') ?>" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($seg1 === 'marketing' && $seg2 === 'influencer' && empty($this->uri->segment(3))) ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Influencer
                            </a>
                            <a href="<?= site_url('marketing/influencer_listing') ?>" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($seg1 === 'marketing' && $seg2 === 'influencer_listing') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Influencer Listing
                            </a>
                            <a href="<?= site_url('marketing/endorse_campaign') ?>" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($seg1 === 'marketing' && $seg2 === 'endorse_campaign') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Endorse Campaign
                            </a>
                            <a href="<?= site_url('marketing/endorse_calendar') ?>" 
                               class="block px-2 py-1.5 text-sm rounded-md transition-colors <?= ($seg1 === 'marketing' && $seg2 === 'endorse_calendar') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                                Endorse Calendar
                            </a>
                        </div>
                    </div>
                </div>

            </nav>
        </div>

        <!-- User Profile (Bottom) -->
        <div class="p-2">
            <button type="button" class="flex items-center justify-between w-full px-2 py-1.5 rounded-md hover:bg-gray-50 transition-colors group">
                <div class="flex items-center space-x-2 min-w-0">
                    <div class="w-7 h-7 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-semibold text-xs">
                            <?php 
                            $user_name = isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'User';
                            $initials = strtoupper(substr($user_name, 0, 1));
                            echo $initials;
                            ?>
                        </span>
                    </div>
                    <div class="flex-1 min-w-0 text-left">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            <?= isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'User Name' ?>
                        </p>
                        <p class="text-xs text-gray-500 truncate">
                            <?= isset($_SESSION['user']['role_text']) ? $_SESSION['user']['role_text'] : 'Admin' ?>
                        </p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                </svg>
            </button>
        </div>

    </div>
</aside>

<!-- Mobile Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 z-30 bg-gray-900/50 hidden sm:hidden" onclick="toggleSidebar()"></div>

<script>
// Toggle sidebar for mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
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
            const chevron = this.querySelector('svg:last-child');
            
            if (target.classList.contains('hidden')) {
                target.classList.remove('hidden');
                if (chevron) chevron.style.transform = 'rotate(180deg)';
            } else {
                target.classList.add('hidden');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
            }
        });
        
        // Set initial chevron state
        const targetId = button.getAttribute('data-collapse-toggle');
        const target = document.getElementById(targetId);
        const chevron = button.querySelector('svg:last-child');
        if (target && !target.classList.contains('hidden') && chevron) {
            chevron.style.transform = 'rotate(180deg)';
        }
    });
});
</script>
