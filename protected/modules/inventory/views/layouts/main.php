<?php /* @var $this InventoryBaseController */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo CHtml::encode($this->pageTitle); ?> | Inventory Management</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/screen.css"
		media="screen, projection">
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/print.css"
		media="print">

	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/main.css">
	<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/css/form.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'sidebar-dark': '#1e293b',
                        'sidebar-darker': '#0f172a'
                    }
                }
            }
        }
    </script>
    <style>
        /* Ensure dropdown is above other content */
        .dropdown-menu {
            z-index: 50;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="bg-gradient-to-b from-slate-800 to-slate-900 text-white w-64 min-h-screen transition-all duration-300 shadow-xl flex flex-col">
            <!-- Logo Section -->
            <div class="p-6 border-b border-slate-700">
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-600 p-2 rounded-lg">
                        <i class="fas fa-boxes text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">InventoryPro</h1>
                        <p class="text-xs text-slate-400">Management System</p>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            
            
            <!-- Sidebar footer can be added here if needed in the future -->
            <!-- Removed User Profile Section from here -->
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button id="sidebar-toggle" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-bars text-gray-600"></i>
                        </button>
                        
                        <!-- Breadcrumbs -->
                        <?php if(isset($this->breadcrumbs) && !empty($this->breadcrumbs)): ?>
                        <nav class="hidden md:flex items-center space-x-2 text-sm" aria-label="Breadcrumb">
                            <?php
                            $this->widget('zii.widgets.CBreadcrumbs', array(
                                'links' => $this->breadcrumbs,
                                'homeLink' => CHtml::link('<i class="fas fa-home"></i>', Yii::app()->createUrl('/inventory/dashboard/index'), array('class' => 'text-gray-600 hover:text-blue-600 transition-colors')),
                                'tagName' => 'ol',
                                'htmlOptions' => array('class' => 'flex items-center space-x-2'),
                                'separator' => '<i class="fas fa-chevron-right text-xs text-gray-400"></i>',
                                'activeLinkTemplate' => '<li><a href="{url}" class="text-gray-600 hover:text-blue-600 transition-colors">{label}</a></li>',
                                'inactiveLinkTemplate' => '<li class="text-gray-900 font-medium" aria-current="page">{label}</li>',
                            ));
                            ?>
                        </nav>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Quick Actions -->
                        <?php if (Yii::app()->controller->id === 'product' && Yii::app()->controller->module->id === 'inventory' && Yii::app()->user->hasRole(array("manager", "admin"))): ?>
                            <a href="<?php echo Yii::app()->createUrl('/inventory/product/create'); ?>" 
                            class="flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 shadow-md">
                                <i class="fas fa-plus"></i>
                                <span class="hidden sm:inline">New Product</span>
                            </a>
                        <?php endif; ?>

                        <?php if (Yii::app()->controller->id === 'category' && Yii::app()->controller->module->id === 'inventory' && Yii::app()->user->hasRole(array("manager", "admin")) ): ?>
                            <a href="<?php echo Yii::app()->createUrl('/inventory/category/create'); ?>" 
                            class="flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 shadow-md">
                                <i class="fas fa-plus"></i>
                                <span class="hidden sm:inline">New Category</span>
                            </a>
                        <?php endif; ?>


                        <!-- User Menu -->
                        <div class="relative">
                            <button id="user-menu-button" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="hidden md:inline text-sm font-medium text-gray-700"><?php echo CHtml::encode(Yii::app()->user->name); ?></span>
                                <i class="fas fa-chevron-down text-xs text-gray-500 hidden md:inline"></i>
                            </button>
                            <!-- Dropdown Panel -->
                            <div id="user-menu-dropdown" 
                                 class="dropdown-menu hidden absolute right-0 mt-2 w-56 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" 
                                 role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                                <div class="py-1" role="none">
                                    <div class="px-4 py-3 border-b border-gray-200">
                                        <p class="text-sm font-medium text-gray-900 truncate" role="none">
                                            <?php echo CHtml::encode(Yii::app()->user->name); ?>
                                        </p>
                                        <p class="text-xs text-gray-500 truncate" role="none">
                                            <?php echo CHtml::encode(Yii::app()->user->getRole()); ?>
                                        </p>
                                    </div>
                                    <!-- Active: "bg-gray-100", Not Active: "" -->
                                   
                                    <a href="<?php echo Yii::app()->createUrl('/site/logout'); ?>" 
                                       class="text-gray-700 block w-full text-left px-4 py-2 text-sm hover:bg-gray-100" role="menuitem" tabindex="-1" id="user-menu-item-1">
                                        <i class="fas fa-sign-out-alt mr-2 text-red-500"></i>Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <div class="px-6 pt-4">
                <?php if(Yii::app()->user->hasFlash('success')): ?>
                    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 flex items-center space-x-2">
                        <i class="fas fa-check-circle text-green-600"></i>
                        <span><?php echo Yii::app()->user->getFlash('success'); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if(Yii::app()->user->hasFlash('error')): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4 flex items-center space-x-2">
                        <i class="fas fa-exclamation-circle text-red-600"></i>
                        <span><?php echo Yii::app()->user->getFlash('error'); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-auto px-6 pb-6 pt-2"> <!-- Adjusted padding -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 min-h-full">
                    <div class="p-6">
                        <?php echo $content; ?>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <div>
                        <strong>Copyright © <?php echo date('Y'); ?> InventoryPro.</strong> All rights reserved.
                    </div>
                    <div class="text-gray-500">
                        v1.0.0
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Toggle sidebar for mobile
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('-translate-x-full'); // For small screens
                    sidebar.classList.toggle('w-64'); // Standard width
                    sidebar.classList.toggle('w-0'); // Collapsed width for small screens
                    sidebar.classList.toggle('p-0'); // No padding when collapsed
                    
                    // If you want to hide it completely on small screens instead of making it w-0
                    // sidebar.classList.toggle('hidden');
                    // sidebar.classList.toggle('lg:flex'); // if you use hidden, ensure it's flex on lg
                });
            }

            // Make sidebar responsive, ensure it's shown on larger screens
            window.addEventListener('resize', function() {
                if (sidebar) {
                    if (window.innerWidth >= 1024) { // Tailwind 'lg' breakpoint
                        sidebar.classList.remove('-translate-x-full', 'w-0', 'p-0');
                        sidebar.classList.add('w-64');
                    } else {
                        // Optional: ensure it's hidden if it was toggled off and screen resized small
                        if (!sidebar.classList.contains('w-64')) {
                             sidebar.classList.add('-translate-x-full');
                        }
                    }
                }
            });
             // Initial check on load for sidebar state based on screen size
            if (sidebar && window.innerWidth < 1024) {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-0', 'p-0');

            } else if (sidebar) {
                sidebar.classList.add('w-64');
                sidebar.classList.remove('-translate-x-full', 'w-0', 'p-0');
            }


            // User Menu Dropdown Toggle
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');

            if (userMenuButton && userMenuDropdown) {
                userMenuButton.addEventListener('click', function(event) {
                    userMenuDropdown.classList.toggle('hidden');
                    event.stopPropagation(); // Prevent click from bubbling to document
                });

                // Close dropdown if clicked outside
                document.addEventListener('click', function(event) {
                    if (!userMenuDropdown.contains(event.target) && !userMenuButton.contains(event.target)) {
                        userMenuDropdown.classList.add('hidden');
                    }
                });

                // Close dropdown with Escape key
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape' && !userMenuDropdown.classList.contains('hidden')) {
                        userMenuDropdown.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</body>
</html>