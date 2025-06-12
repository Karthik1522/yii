<?php

/**
 * DashboardHelper - Business logic helper for Dashboard operations
 *
 * This helper class extracts core business logic from DashboardController
 * to improve testability, maintainability, and reusability across controllers.
 */
class DashboardHelper
{
    /**
     * Gets dashboard statistics and summary data
     *
     * @return array Dashboard statistics including product counts, recent activities, etc.
     */
    public static function getDashboardData()
    {
        try {
            Yii::log("Fetching dashboard data", CLogger::LEVEL_INFO, 'application.inventory.dashboard.helper.getDashboardData');

            // Get stock statistics
            $stockStats = StockHelper::getStockStatistics();

            // Get category statistics
            $totalCategories = Category::model()->count();

            // Get recent stock movements
            $recentMovements = StockHelper::getRecentStockMovements(5);

            // Get products added in last 7 days
            $weekAgo = new MongoDate(strtotime('-7 days'));
            $criteria = new EMongoCriteria();
            $criteria->addCond('created_at', '>=', $weekAgo);
            $recentProductsCount = Product::model()->count($criteria);

            return [
                'stockStatistics' => $stockStats,
                'totalCategories' => $totalCategories,
                'recentMovements' => $recentMovements,
                'recentProductsCount' => $recentProductsCount,
                'lastUpdated' => new MongoDate()
            ];
        } catch (Exception $e) {
            Yii::log("Error fetching dashboard data: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.dashboard.helper.getDashboardData');

            // Return default/empty data structure
            return [
                'stockStatistics' => [
                    'totalProducts' => 0,
                    'lowStockCount' => 0,
                    'outOfStockCount' => 0,
                    'lowStockThreshold' => 10
                ],
                'totalCategories' => 0,
                'recentMovements' => array(),
                'recentProductsCount' => 0,
                'lastUpdated' => new MongoDate(),
                'error' => 'Error loading dashboard data'
            ];
        }
    }

    /**
     * Gets low stock products for dashboard alerts
     *
     * @param int $threshold Stock threshold level
     * @param int $limit Number of products to return
     * @return array Low stock products
     */
    public static function getLowStockProducts($threshold = 10, $limit = 10)
    {
        try {
            Yii::log("Fetching low stock products", CLogger::LEVEL_INFO, 'application.inventory.dashboard.helper.getLowStockProducts');

            $criteria = new EMongoCriteria();
            $criteria->addCond('quantity', '<=', $threshold);
            $criteria->sort('quantity', EMongoCriteria::SORT_ASC);
            $criteria->limit($limit);

            return Product::model()->findAll($criteria);
        } catch (Exception $e) {
            Yii::log("Error fetching low stock products: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.dashboard.helper.getLowStockProducts');
            return array();
        }
    }

    /**
     * Gets recent product activities for dashboard
     *
     * @param int $limit Number of activities to return
     * @return array Recent product activities
     */
    public static function getRecentProductActivities($limit = 10)
    {
        try {
            Yii::log("Fetching recent product activities", CLogger::LEVEL_INFO, 'application.inventory.dashboard.helper.getRecentProductActivities');

            $criteria = new EMongoCriteria();
            $criteria->sort('updated_at', EMongoCriteria::SORT_DESC);
            $criteria->limit($limit);

            return Product::model()->findAll($criteria);
        } catch (Exception $e) {
            Yii::log("Error fetching recent product activities: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.dashboard.helper.getRecentProductActivities');
            return array();
        }
    }
}
