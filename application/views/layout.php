<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'MONTERA Application' ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Flowbite JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
    
    <!-- Optional: Custom CSS -->
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
                    <h1 class="ml-2 sm:ml-0 text-xl font-semibold text-gray-900">
                        <?= isset($page_title) ? $page_title : 'Dashboard' ?>
                    </h1>
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
            <?php if (isset($content_view)): ?>
                <?php $this->load->view($content_view); ?>
            <?php else: ?>
                <!-- Default Content -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Welcome to MONTERA</h2>
                    <p class="text-gray-600">This is your main content area. Load your views here using the $content_view variable.</p>
                </div>
            <?php endif; ?>
        </main>

    </div>

</body>
</html>
