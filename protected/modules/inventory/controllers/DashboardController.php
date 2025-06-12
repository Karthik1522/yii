<?php

use MongoDB\BSON\ObjectId;

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
            Yii::log("Successfully loaded dashboard index view", CLogger::LEVEL_INFO, 'application.inventory.dashboard.index');

            $this->render('index');
        } catch (Exception $e) {
            Yii::log("Error in actionIndex: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.dashboard.index');
            throw new CHttpException(500, 'Error loading dashboard.');
        }
    }
}
