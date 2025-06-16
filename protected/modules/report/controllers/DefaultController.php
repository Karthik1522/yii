<?php

Yii::import('application.modules.inventory.models.Product');
Yii::import('application.modules.inventory.models.Category');
Yii::import('application.components.helpers.ReportHelper');

class DefaultController extends Controller
{
    public $layout = 'application.modules.inventory.views.layouts.main';

    public function filters()
    {
        return array(
            'accessControl',
        );
    }

    
    public function accessRules()
    {


        return array(

            array('allow',
                'actions' => array('index', 'stockLevel', 'productsPerCategory', 'lowStockReport', 'priceRangeReport'),
                'expression' => 'Yii::app()->user->hasRole(array("admin", "manager"))',
            ),
            array('deny',
                'users' => array('*'),
            ),
        );
    }

    public function actionIndex()
    {
        Yii::log("Starting actionIndex", CLogger::LEVEL_INFO, 'application.report.default.index');

        try {
            Yii::log("Successfully loaded report index view", CLogger::LEVEL_INFO, 'application.report.default.index');

            $this->render('index');
        } catch (Exception $e) {
            Yii::log("Error in actionIndex: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.default.index');
            throw new CHttpException(500, 'Error loading reports dashboard.');
        }
    }

    public function actionStockLevel()
    {
        Yii::log("Starting actionStockLevel", CLogger::LEVEL_INFO, 'application.report.default.stockLevel');

        try {
            $result = ReportHelper::generateStockLevelReport();

            ReportHelper::handleReportFlashMessages($result);

            if ($result['success']) {
                $dataProvider = ReportHelper::createReportDataProvider($result['data'], [
                    'id' => 'stock-level-report',
                    'keyField' => '_id',
                    'sort' => [
                        'attributes' => ['_id', 'sku', 'name', 'category_id', 'categoryName', 'quantity', 'price', 'stock_value'],
                        'defaultOrder' => ['name' => CSort::SORT_ASC],
                    ],
                    'pagination' => ['pageSize' => 20],
                ]);

                Yii::log("Successfully generated stock level report", CLogger::LEVEL_INFO, 'application.report.default.stockLevel');

                $this->render('stockLevel', [
                    'dataProvider' => $dataProvider,
                    'totalStockValue' => $result['totalValue'],
                ]);
            } else {
                throw new CHttpException(500, $result['message'] ?? 'Error generating stock level report.');
            }
        } catch (Exception $e) {
            Yii::log("Error in actionStockLevel: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.default.stockLevel');
            throw new CHttpException(500, 'Error generating stock level report.');
        }
    }

    public function actionProductsPerCategory()
    {
        Yii::log("Starting actionProductsPerCategory", CLogger::LEVEL_INFO, 'application.report.default.productsPerCategory');

        try {
            $result = ReportHelper::generateProductsPerCategoryReport();

            ReportHelper::handleReportFlashMessages($result);

            if ($result['success']) {
                $dataProvider = ReportHelper::createReportDataProvider($result['data'], [
                    'keyField' => 'categoryId',
                    'sort' => ['attributes' => ['categoryId', 'productCount', 'categoryName']],
                    'pagination' => ['pageSize' => 20],
                ]);

                Yii::log("Successfully generated products per category report", CLogger::LEVEL_INFO, 'application.report.default.productsPerCategory');

                $this->render('productsPerCategory', [
                    'dataProvider' => $dataProvider,
                ]);
            } else {
                throw new CHttpException(500, $result['message'] ?? 'Error generating products per category report.');
            }
        } catch (Exception $e) {
            Yii::log("Error in actionProductsPerCategory: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.default.productsPerCategory');
            throw new CHttpException(500, 'Error generating products per category report.');
        }
    }

    public function actionPriceRangeReport()
    {
        Yii::log("Starting actionPriceRangeReport", CLogger::LEVEL_INFO, 'application.report.default.priceRangeReport');

        try {
            $result = ReportHelper::generatePriceRangeReport();

            ReportHelper::handleReportFlashMessages($result);

            if ($result['success']) {
                $dataProvider = ReportHelper::createReportDataProvider($result['data'], [
                    'id' => 'price-range-report',
                    'keyField' => 'priceRange',
                    'sort' => ['attributes' => ['priceRange', 'productCount', 'totalStockValueInBucket']],
                    'pagination' => ['pageSize' => 10],
                ]);

                Yii::log("Successfully generated price range report", CLogger::LEVEL_INFO, 'application.report.default.priceRangeReport');

                $this->render('priceRangeReport', [
                    'dataProvider' => $dataProvider,
                ]);
            } else {
                throw new CHttpException(500, $result['message'] ?? 'Error generating price range report.');
            }
        } catch (Exception $e) {
            Yii::log("Error in actionPriceRangeReport: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.report.default.priceRangeReport');
            throw new CHttpException(500, 'Error generating price range report.');
        }
    }
}
