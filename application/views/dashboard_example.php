<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MONTERA</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Flowbite JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="bg-gray-50">

    <!-- Sidebar -->
    <?php $this->load->view('partials/sidebar'); ?>

    <!-- Main Content -->
    <div class="sm:ml-60">
        
        <!-- Top Navigation Bar -->
        <nav class="bg-white border-b border-gray-200 px-4 py-3 sticky top-0 z-20">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <!-- Mobile Menu Toggle -->
                    <button type="button" 
                            onclick="toggleSidebar()"
                            class="inline-flex items-center p-2 text-sm text-gray-500 rounded-md sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    
                    <!-- Page Title -->
                    <h1 class="ml-2 sm:ml-0 text-xl font-semibold text-gray-900">Dashboard</h1>
                </div>
                
                <!-- Right Side - User Menu -->
                <div class="flex items-center space-x-3">
                    <button type="button" class="p-2 text-gray-500 rounded-md hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </button>
                    
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-600">U</span>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="p-4 md:p-6">
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Revenue</p>
                            <p class="text-2xl font-semibold text-gray-900 mt-1">$45,231</p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 rounded-md flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-green-600 mt-2">+20.1% from last month</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Active Orders</p>
                            <p class="text-2xl font-semibold text-gray-900 mt-1">2,350</p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 rounded-md flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">+180 from yesterday</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Campaigns</p>
                            <p class="text-2xl font-semibold text-gray-900 mt-1">12</p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 rounded-md flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">8 active campaigns</p>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Conversion Rate</p>
                            <p class="text-2xl font-semibold text-gray-900 mt-1">3.24%</p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 rounded-md flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-green-600 mt-2">+0.3% from last week</p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Recent Activity -->
                <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h2>
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3 pb-4 border-b border-gray-100">
                            <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900 font-medium">New order placed</p>
                                <p class="text-sm text-gray-500">Order #12345 from Customer A</p>
                                <p class="text-xs text-gray-400 mt-1">2 minutes ago</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3 pb-4 border-b border-gray-100">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900 font-medium">Campaign started</p>
                                <p class="text-sm text-gray-500">Summer Sale Campaign is now live</p>
                                <p class="text-xs text-gray-400 mt-1">1 hour ago</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3 pb-4 border-b border-gray-100">
                            <div class="w-2 h-2 bg-yellow-500 rounded-full mt-2"></div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900 font-medium">Stock alert</p>
                                <p class="text-sm text-gray-500">Product XYZ is running low</p>
                                <p class="text-xs text-gray-400 mt-1">3 hours ago</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-2 h-2 bg-purple-500 rounded-full mt-2"></div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900 font-medium">New influencer registered</p>
                                <p class="text-sm text-gray-500">@influencer_name joined the platform</p>
                                <p class="text-xs text-gray-400 mt-1">5 hours ago</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                    <div class="space-y-3">
                        <button class="w-full px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors">
                            Create Campaign
                        </button>
                        <button class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors">
                            Add Product
                        </button>
                        <button class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors">
                            View Reports
                        </button>
                        <button class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors">
                            Export Data
                        </button>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Quick Links</h3>
                        <div class="space-y-2">
                            <a href="<?= site_url('marketing/overview') ?>" class="block text-sm text-gray-600 hover:text-gray-900">
                                Marketing Overview
                            </a>
                            <a href="<?= site_url('marketing/endorse_campaign') ?>" class="block text-sm text-gray-600 hover:text-gray-900">
                                Endorsement Campaigns
                            </a>
                            <a href="<?= site_url('report') ?>" class="block text-sm text-gray-600 hover:text-gray-900">
                                View Reports
                            </a>
                        </div>
                    </div>
                </div>

            </div>

        </main>

    </div>

</body>
</html>
