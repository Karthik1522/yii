<?php

// Import the helper class
Yii::import('application.components.helpers.StockHelper');

class StockController extends InventoryBaseController
{
    public function accessRules()
    {
        return [
            [
                'allow',
                'actions' => array('index', 'admin'),
                'expression' => 'Yii::app()->user->isAdmin() || Yii::app()->user->isManager() || Yii::app()->user->isStaff()'
            ],
            [
                'deny',
                'users' => array("*")
            ]
        ];
    }

    public function actionIndex()
    {
        Yii::log("Starting actionIndex", CLogger::LEVEL_INFO, 'application.inventory.stock.index');

        try {
            $this->actionAdmin();
        } catch (Exception $e) {
            Yii::log("Error in actionIndex: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.stock.index');
            throw new CHttpException(500, 'Error loading stock list.');
        }
    }

    public function actionAdmin()
    {
        Yii::log("Starting actionAdmin", CLogger::LEVEL_INFO, 'application.inventory.stock.admin');

        try {
            $searchData = isset($_GET['StockLog']) ? $_GET['StockLog'] : null;
            $model = StockHelper::prepareStockLogSearch($searchData);

            if ($searchData) {
                Yii::log("Applied search filters for stock admin", CLogger::LEVEL_INFO, 'application.inventory.stock.admin');
            }

            Yii::log("Successfully loaded stock admin view", CLogger::LEVEL_INFO, 'application.inventory.stock.admin');

            $this->render('admin', [
                'model' => $model,
            ]);
        } catch (Exception $e) {
            Yii::log("Error in actionAdmin: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.stock.admin');
            throw new CHttpException(500, 'Error loading stock administration.');
        }
    }
}
