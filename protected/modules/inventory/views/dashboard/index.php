<?php
$isAdmin = Yii::app()->user->hasRole(array("admin"));
$isManager = Yii::app()->user->hasRole(array("manager"));
$isStaff = Yii::app()->user->hasRole(array("staff"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InventoryPro Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .card {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Dashboard Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-tachometer-alt mr-2 text-blue-600"></i>
                InventoryPro Dashboard
            </h1>
            <div class="text-sm text-gray-600">
                <?php echo date('l, F j, Y'); ?>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Products Card -->
            <div class="bg-white rounded-lg shadow p-6 card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-box-open text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-500 text-sm">Total Products</h3>
                        <p class="text-2xl font-bold"><?php echo Product::model()->count(); ?></p>
                    </div>
                </div>
            </div>

            <!-- Categories Card -->
            <div class="bg-white rounded-lg shadow p-6 card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-tags text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-500 text-sm">Total Categories</h3>
                        <p class="text-2xl font-bold"><?php echo Category::model()->count(); ?></p>
                    </div>
                </div>
            </div>

            <!-- Out of Stock Card -->
            <div class="bg-white rounded-lg shadow p-6 card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                        <i class="fas fa-times-circle text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-500 text-sm">Out of Stock</h3>
                        <p class="text-2xl font-bold">
                            <?php echo count(array_filter(Product::model()->findAll(), fn ($product) => $product->quantity == 0)); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modules Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6 border-b pb-2">
                <i class="fas fa-th-large mr-2 text-blue-600"></i>
                Inventory Modules
            </h2>

            <div class="flex justify-between gap-6">
                <!-- Products Module -->
                <?php if ($isAdmin || $isManager || $isStaff): ?>
                <a href="<?php echo Yii::app()->createUrl('/inventory/product/admin'); ?>" 
                   class="bg-blue-50 hover:bg-blue-100 rounded-lg p-6 text-center card">
                    <div class="text-blue-600 mb-3">
                        <i class="fas fa-box-open text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Products</h3>
                    <p class="text-sm text-gray-600 mt-2">Manage inventory items</p>
                </a>
                <?php endif; ?>

                <!-- Categories Module -->
                <?php if ($isAdmin || $isManager || $isStaff): ?>
                <a href="<?php echo Yii::app()->createUrl('/inventory/category/admin'); ?>" 
                   class="bg-green-50 hover:bg-green-100 rounded-lg p-6 text-center card">
                    <div class="text-green-600 mb-3">
                        <i class="fas fa-tags text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Categories</h3>
                    <p class="text-sm text-gray-600 mt-2">Organize product categories</p>
                </a>
                <?php endif; ?>

                <!-- Stock Logs Module - Only for Admin/Manager -->
                <?php if ($isAdmin || $isManager): ?>
                <a href="<?php echo Yii::app()->createUrl('/inventory/stock/admin'); ?>" 
                   class="bg-purple-50 hover:bg-purple-100 rounded-lg p-6 text-center card">
                    <div class="text-purple-600 mb-3">
                        <i class="fas fa-clipboard-list text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Stock Logs</h3>
                    <p class="text-sm text-gray-600 mt-2">View inventory transactions</p>
                </a>
                <?php endif; ?>

                <!-- Reports Module - Only for Admin/Manager -->
                <?php if ($isAdmin): ?>
                <a href="<?php echo Yii::app()->createUrl('/report/default/index'); ?>" 
                   class="bg-yellow-50 hover:bg-yellow-100 rounded-lg p-6 text-center card">
                    <div class="text-yellow-600 mb-3">
                        <i class="fas fa-chart-bar text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Reports</h3>
                    <p class="text-sm text-gray-600 mt-2">Generate inventory reports</p>
                </a>
                <?php endif; ?>

               
            </div>
        </div>

      
    </div>
</body>
</html>