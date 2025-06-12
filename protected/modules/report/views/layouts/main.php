<?php /* @var $this Controller */ ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="language" content="en" />
    <title><?php echo CHtml::encode($this->pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    
    <!-- Custom CSS for report styling -->
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .container {
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        
        .header {
            border-bottom: 2px solid #337ab7;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        
        .logo {
            float: left;
            margin-right: 20px;
        }
        
        .company-info {
            float: left;
        }
        
        .report-title {
            clear: both;
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
            font-weight: bold;
            color: #337ab7;
        }
        
        .footer {
            border-top: 1px solid #ddd;
            margin-top: 20px;
            padding-top: 10px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        
        .sidebar {
            background-color: #f8f9fa;
            padding: 15px;
            border-right: 1px solid #ddd;
        }
        
        .nav-list {
            list-style: none;
            padding: 0;
        }
        
        .nav-list li {
            margin-bottom: 5px;
        }
        
        .nav-list li a {
            display: block;
            padding: 8px 15px;
            color: #333;
            text-decoration: none;
            border-left: 3px solid transparent;
        }
        
        .nav-list li a:hover {
            background-color: #e7e7e7;
            border-left: 3px solid #337ab7;
        }
        
        .nav-list li.active a {
            background-color: #e7e7e7;
            border-left: 3px solid #337ab7;
            font-weight: bold;
        }
        
        .content {
            padding: 15px;
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 8px 0;
            margin-bottom: 20px;
        }
        
        .page-actions {
            margin-bottom: 20px;
        }
        
        .data-table {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .data-table th {
            background-color: #337ab7;
            color: white;
            text-align: left;
            padding: 8px;
        }
        
        .data-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .summary {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header clearfix">
            <div class="logo">
                <?php echo CHtml::image(Yii::app()->request->baseUrl.'/images/logo.png', 'Company Logo', array('height'=>'80')); ?>
            </div>
            <div class="company-info">
                <h2>Inventory Pro</h2>
                <p>Inventory Management System</p>
                <p><?php echo date('F j, Y'); ?></p>
            </div>
            <div class="pull-right">
                <?php if(!Yii::app()->user->isGuest): ?>
                    <p>Welcome, <strong><?php echo Yii::app()->user->name; ?></strong> | 
                    <?php echo CHtml::link('Logout', array('/site/logout')); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Report Title (can be set in controller) -->
        <?php if(isset($this->reportTitle)): ?>
            <div class="report-title"><?php echo $this->reportTitle; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Sidebar Navigation -->
            <?php if(!Yii::app()->user->isGuest): ?>
            <div class="col-md-3 sidebar">
                <ul class="nav-list">
                    <li class="<?php echo $this->id=='site'?'active':''; ?>">
                        <?php echo CHtml::link('Dashboard', array('/site/index')); ?>
                    </li>
                    <li class="<?php echo $this->id=='inventory'?'active':''; ?>">
                        <?php echo CHtml::link('Inventory', array('/inventory/index')); ?>
                    </li>
                    <li class="<?php echo $this->id=='items'?'active':''; ?>">
                        <?php echo CHtml::link('Items', array('/items/index')); ?>
                    </li>
                    <li class="<?php echo $this->id=='categories'?'active':''; ?>">
                        <?php echo CHtml::link('Categories', array('/categories/index')); ?>
                    </li>
                    <li class="<?php echo $this->id=='suppliers'?'active':''; ?>">
                        <?php echo CHtml::link('Suppliers', array('/suppliers/index')); ?>
                    </li>
                    <li class="<?php echo $this->id=='transactions'?'active':''; ?>">
                        <?php echo CHtml::link('Transactions', array('/transactions/index')); ?>
                    </li>
                    <li class="<?php echo $this->id=='reports'?'active':''; ?>">
                        <?php echo CHtml::link('Reports', array('/reports/index')); ?>
                    </li>
                    <li class="<?php echo $this->id=='users'?'active':''; ?>">
                        <?php echo CHtml::link('User Management', array('/users/index')); ?>
                    </li>
                </ul>
                
                <!-- Quick Stats Summary -->
                <!-- <div class="summary">
                    <h4>Quick Stats</h4>
                    <p>Total Items: <strong>1,245</strong></p>
                    <p>Low Stock: <strong class="text-danger">42</strong></p>
                    <p>Out of Stock: <strong class="text-danger">15</strong></p>
                </div> -->
            </div>
            <?php endif; ?>
            
            <!-- Main Content Area -->
            <div class="<?php echo Yii::app()->user->isGuest?'col-md-12':'col-md-9'; ?> content">
                <!-- Breadcrumbs -->
                <?php if(isset($this->breadcrumbs)): ?>
                    <?php $this->widget('zii.widgets.CBreadcrumbs', array(
                        'links'=>$this->breadcrumbs,
                        'homeLink'=>CHtml::link('Home', array('/site/index')),
                        'separator'=>' &raquo; ',
                        'htmlOptions'=>array('class'=>'breadcrumb')
                    )); ?>
                <?php endif; ?>
                
               
                <!-- Page content -->
                <?php echo $content; ?>
            </div>
        </div>
        
        <!-- Footer Section -->
        <div class="footer">
            <p>Inventory Pro &copy; <?php echo date('Y'); ?> | <?php echo Yii::powered(); ?></p>
            <p>Page rendered in {elapsed_time} seconds</p>
        </div>
    </div>
    
    <!-- jQuery and Bootstrap JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>