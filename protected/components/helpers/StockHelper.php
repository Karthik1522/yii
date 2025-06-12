<?php

/**
 * StockHelper - Business logic helper for Stock operations
 *
 * This helper class extracts core business logic from StockController
 * to improve testability, maintainability, and reusability across controllers.
 */
class StockHelper
{
    /**
     * Prepares stock log data for admin/listing view
     *
     * @param array $searchData Optional search criteria
     * @return StockLog The search model for data provider
     */
    public static function prepareStockLogSearch($searchData = null)
    {
        Yii::log("Preparing stock log search", CLogger::LEVEL_INFO, 'application.inventory.stock.helper.prepareStockLogSearch');

        try {
            $model = new StockLog('search');
            $model->unsetAttributes();

            if ($searchData) {
                $model->attributes = $searchData;
                Yii::log("Applied search filters for stock log search", CLogger::LEVEL_INFO, 'application.inventory.stock.helper.prepareStockLogSearch');
            }

            return $model;
        } catch (Exception $e) {
            Yii::log("Error in prepareStockLogSearch: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.stock.helper.prepareStockLogSearch');
            throw new CHttpException(500, 'Error preparing stock log search.');
        }
    }

    /**
     * Gets stock statistics and summary data
     *
     * @return array Stock statistics including total products, low stock items, etc.
     */
    public static function getStockStatistics()
    {
        try {
            Yii::log("Calculating stock statistics", CLogger::LEVEL_INFO, 'application.inventory.stock.helper.getStockStatistics');

            $totalProducts = Product::model()->count();
            $lowStockThreshold = 10; // This could be configurable

            $criteria = new EMongoCriteria();
            $criteria->addCond('quantity', '<=', $lowStockThreshold);
            $lowStockCount = Product::model()->count($criteria);

            $criteria = new EMongoCriteria();
            $criteria->addCond('quantity', '==', 0);
            $outOfStockCount = Product::model()->count($criteria);

            return [
                'totalProducts' => $totalProducts,
                'lowStockCount' => $lowStockCount,
                'outOfStockCount' => $outOfStockCount,
                'lowStockThreshold' => $lowStockThreshold
            ];
        } catch (Exception $e) {
            Yii::log("Error calculating stock statistics: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.stock.helper.getStockStatistics');
            return [
                'totalProducts' => 0,
                'lowStockCount' => 0,
                'outOfStockCount' => 0,
                'lowStockThreshold' => 10
            ];
        }
    }

    /**
     * Gets recent stock movements
     *
     * @param int $limit Number of recent movements to fetch
     * @return array Recent stock log entries
     */
    public static function getRecentStockMovements($limit = 10)
    {
        try {
            Yii::log("Fetching recent stock movements", CLogger::LEVEL_INFO, 'application.inventory.stock.helper.getRecentStockMovements');

            $criteria = new EMongoCriteria();
            $criteria->sort('created_at', EMongoCriteria::SORT_DESC);
            $criteria->limit($limit);

            return StockLog::model()->findAll($criteria);
        } catch (Exception $e) {
            Yii::log("Error fetching recent stock movements: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.stock.helper.getRecentStockMovements');
            return array();
        }
    }
}
