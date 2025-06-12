<?php

use MongoDB\BSON\ObjectId;

// Import the helper class
Yii::import('application.modules.inventory.helpers.CategoryHelper');

class CategoryController extends InventoryBaseController
{
    public function accessRules()
    {
        return [
            [
                'allow',
                'actions' => array('index', 'admin', 'view'),
                'expression' => 'Yii::app()->user->hasRole(array("staff", "manager", "admin"))',
            ],

            [
                'allow',
                'actions' => array('update', 'create', 'delete'),
                'expression' => 'Yii::app()->user->hasRole(array("manager", "admin"))',
            ],

            [
                'deny',
                'users' => array("*"),
            ]
        ];
    }

    public function actionView($id)
    {
        Yii::log("Starting actionView for category ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.category.view');

        try {
            $model = CategoryHelper::loadCategoryById($id);

            Yii::log("Successfully loaded category for view: {$model->name}", CLogger::LEVEL_INFO, 'application.inventory.category.view');

            $this->render('view', array(
                'model' => $model,
            ));
        } catch (Exception $e) {
            Yii::log("Error in actionView for category ID {$id}: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.category.view');
            throw new CHttpException(500, 'Error loading category details.');
        }
    }

    public function loadModel($id)
    {
        return CategoryHelper::loadCategoryById($id);
    }

    public function actionCreate()
    {
        Yii::log("Starting actionCreate", CLogger::LEVEL_INFO, 'application.inventory.category.create');

        try {
            $model = new Category();

            if (isset($_POST['Category'])) {
                Yii::log("Processing category creation form submission", CLogger::LEVEL_INFO, 'application.inventory.category.create');

                $result = CategoryHelper::createCategory($_POST['Category']);
                $model = $result['model'];

                if ($result['success']) {
                    Yii::app()->user->setFlash('success', $result['message']);
                    $this->redirect(['view', 'id' => (string)$model->_id]);
                } else {
                    Yii::app()->user->setFlash('error', $result['message']);
                }
            }

            $parentCategories = CategoryHelper::getParentCategoryOptions();

            $this->render('create', [
                'model' => $model,
                'parentCategories' => $parentCategories
            ]);
        } catch (Exception $e) {
            Yii::log("Error in actionCreate: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.category.create');
            throw new CHttpException(500, 'Error creating category.');
        }
    }

    public function actionUpdate($id)
    {
        Yii::log("Starting actionUpdate for category ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.category.update');

        try {
            $model = CategoryHelper::loadCategoryById($id);

            if (isset($_POST['Category'])) {
                Yii::log("Processing category update form submission for ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.category.update');

                $result = CategoryHelper::updateCategory($model, $_POST['Category']);

                if ($result['success']) {
                    Yii::app()->user->setFlash('success', $result['message']);
                    $this->redirect(['view', 'id' => (string)$model->_id]);
                } else {
                    Yii::app()->user->setFlash('error', $result['message']);
                }
            }

            $parentCategories = CategoryHelper::getParentCategoryOptions((string)$model->_id);

            $this->render('update', array(
                'model' => $model,
                'parentCategories' => $parentCategories,
            ));
        } catch (Exception $e) {
            Yii::log("Error in actionUpdate for category ID {$id}: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.category.update');
            throw new CHttpException(500, 'Error updating category.');
        }
    }

    public function actionDelete($id)
    {
        Yii::log("Starting actionDelete for category ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.category.delete');

        try {
            $model = CategoryHelper::loadCategoryById($id);
            $result = CategoryHelper::deleteCategory($model);

            // Handle AJAX response
            CategoryHelper::handleAjaxResponse($result, Yii::app()->request->isAjaxRequest);

            // If not AJAX and deletion failed, handle accordingly
            if (!$result['success']) {
                Yii::app()->user->setFlash('error', $result['message']);
                $this->redirect(['admin']);
            } else {
                Yii::app()->user->setFlash('success', $result['message']);
                $this->redirect(['admin']);
            }

        } catch (Exception $e) {
            Yii::log("Error in actionDelete for category ID {$id}: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.category.delete');

            if (Yii::app()->request->isAjaxRequest) {
                echo CJSON::encode(['status' => 'error', 'message' => 'Error deleting category.']);
                Yii::app()->end();
            }
            throw new CHttpException(500, 'Error deleting category.');
        }
    }

    public function actionIndex()
    {
        Yii::log("Starting actionIndex", CLogger::LEVEL_INFO, 'application.inventory.category.index');

        try {
            $this->actionAdmin();
        } catch (Exception $e) {
            Yii::log("Error in actionIndex: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.category.index');
            throw new CHttpException(500, 'Error loading category list.');
        }
    }

    public function actionAdmin()
    {
        Yii::log("Starting actionAdmin", CLogger::LEVEL_INFO, 'application.inventory.category.admin');

        try {
            $searchData = isset($_GET['Category']) ? $_GET['Category'] : null;
            $model = CategoryHelper::prepareCategorySearch($searchData);

            if ($searchData) {
                Yii::log("Applied search filters for category admin", CLogger::LEVEL_INFO, 'application.inventory.category.admin');
            }

            Yii::log("Successfully loaded category admin view", CLogger::LEVEL_INFO, 'application.inventory.category.admin');

            $this->render('admin', array(
                'model' => $model,
            ));
        } catch (Exception $e) {
            Yii::log("Error in actionAdmin: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.category.admin');
            throw new CHttpException(500, 'Error loading category administration.');
        }
    }


    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'category-form') { // Ensure form ID matches
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }
}
