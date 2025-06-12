<?php

use MongoDB\BSON\ObjectId;

// Import the helper class
Yii::import('application.components.helpers.DashboardHelper');

class DashboardController extends InventoryBaseController
{
    public function accessRules()
    {
        return [
            [
                'allow',
                'actions' => array('index'),
                'expression' => 'Yii::app()->user->isAdmin() || Yii::app()->user->isManager() || Yii::app()->user->isStaff()'
            ],
        ];
    }

    public function actionIndex()
    {
        try {
            Yii::log("Starting dashboard index", CLogger::LEVEL_INFO, 'application.inventory.dashboard.index');

            // Get dashboard data from helper
            $dashboardData = DashboardHelper::getDashboardData();

            // Get additional data for dashboard widgets
            $lowStockProducts = DashboardHelper::getLowStockProducts();
            $recentActivities = DashboardHelper::getRecentProductActivities();

            Yii::log("Successfully loaded dashboard index view", CLogger::LEVEL_INFO, 'application.inventory.dashboard.index');

            $this->render('index', [
                'dashboardData' => $dashboardData,
                'lowStockProducts' => $lowStockProducts,
                'recentActivities' => $recentActivities
            ]);
        } catch (Exception $e) {
            Yii::log("Error in actionIndex: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.dashboard.index');
            throw new CHttpException(500, 'Error loading dashboard.');
        }
    }
}
