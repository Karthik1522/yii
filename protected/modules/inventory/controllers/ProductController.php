<?php

use MongoDB\BSON\ObjectId;

// Import the helper class
Yii::import('application.components.helpers.ProductHelper');

class ProductController extends InventoryBaseController
{
    public function accessRules()
    {
        return [
            [
                'allow',
                'actions' => array('index', 'admin', 'view', 'create', 'update', 'delete'),
                'expression' => 'Yii::app()->user->isAdmin() || Yii::app()->user->isManager()'
            ],
            [
                'allow',
                'actions' => array('view', 'index', 'admin'),
                'expression' => 'Yii::app()->user->isStaff()'
            ],
            [
                'deny',
                'users' => array('*'),
            ],
        ];
    }

    public function actionView($id)
    {
        Yii::log("Starting actionView for product ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.product.view');

        try {
            $model = ProductHelper::loadProductById($id);
            $productImageUrl = ProductHelper::getProductImageUrl($model);

            Yii::log("Successfully loaded product for view: {$model->name}", CLogger::LEVEL_INFO, 'application.inventory.product.view');

            $this->render('view', array(
                'model' => $model,
                'productImageUrl' => $productImageUrl,
            ));
        } catch (Exception $e) {
            Yii::log("Error in actionView for product ID {$id}: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.view');
            throw new CHttpException(500, 'Error loading product details.');
        }
    }

    public function actionCreate()
    {
        Yii::log("Starting actionCreate", CLogger::LEVEL_INFO, 'application.inventory.product.create');

        try {
            $model = new Product();

            if (isset($_POST['Product'])) {
                Yii::log("Processing product creation form submission", CLogger::LEVEL_INFO, 'application.inventory.product.create');

                $uploadedFile = CUploadedFile::getInstance($model, 'image_filename_upload');
                $result = ProductHelper::createProduct($_POST['Product'], $uploadedFile);
                $model = $result['model'];

                if ($result['success']) {
                    Yii::app()->user->setFlash('success', $result['message']);
                    $this->redirect(array('k', 'id' => (string)$model->_id));
                } else {
                    Yii::app()->user->setFlash('error', $result['message']);
                }
            }

            $categories = ProductHelper::getCategoryOptions();

            $this->render('create', array(
                'model' => $model,
                'categories' => $categories,
            ));
        } catch (Exception $e) {
            Yii::log("Error in actionCreate: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.create');
            throw new CHttpException(500, 'Error creating product.');
        }
    }

    public function actionUpdate($id)
    {
        Yii::log("Starting actionUpdate for product ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.product.update');

        try {
            $model = ProductHelper::loadProductById($id);

            if (isset($_POST['Product'])) {
                Yii::log("Processing product update form submission for ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.product.update');

                $uploadedFile = CUploadedFile::getInstance($model, 'image_filename_upload');
                $clearImage = isset($_POST['clear_image_filename']) && $_POST['clear_image_filename'] == 1;

                $result = ProductHelper::updateProduct($model, $_POST['Product'], $uploadedFile, $clearImage);

                if ($result['success']) {
                    Yii::app()->user->setFlash('success', $result['message']);
                    $this->redirect(['view', 'id' => (string)$model->_id]);
                } else {
                    Yii::app()->user->setFlash('error', $result['message']);
                }
            }

            $categories = ProductHelper::getCategoryOptions();
            $imageUrl = ProductHelper::getProductImageUrl($model);

            $this->render('update', array(
                'model' => $model,
                'categories' => $categories,
                'productImageUrl' => $imageUrl
            ));
        } catch (Exception $e) {
            Yii::log("Error in actionUpdate for product ID {$id}: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.update');
            throw new CHttpException(500, 'Error updating product.');
        }
    }

    public function actionDelete($id)
    {
        Yii::log("Starting actionDelete for product ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.product.delete');

        try {
            $model = ProductHelper::loadProductById($id);
            $result = ProductHelper::deleteProduct($model);

            // Handle AJAX response
            ProductHelper::handleAjaxResponse($result, Yii::app()->request->isAjaxRequest);

            // If not AJAX, handle redirect
            if ($result['success']) {
                Yii::app()->user->setFlash('success', $result['message']);
            } else {
                Yii::app()->user->setFlash('error', $result['message']);
            }
            $this->redirect(['admin']);

        } catch (Exception $e) {
            Yii::log("Error in actionDelete for product ID {$id}: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.delete');

            if (Yii::app()->request->isAjaxRequest) {
                echo CJSON::encode(['status' => 'error', 'message' => 'Error deleting product.']);
                Yii::app()->end();
            }
            throw new CHttpException(500, 'Error deleting product.');
        }
    }

    public function actionIndex()
    {
        Yii::log("Starting actionIndex", CLogger::LEVEL_INFO, 'application.inventory.product.index');

        try {
            $this->actionAdmin();
        } catch (Exception $e) {
            Yii::log("Error in actionIndex: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.index');
            throw new CHttpException(500, 'Error loading product list.');
        }
    }

    public function actionAdmin()
    {
        Yii::log("Starting actionAdmin", CLogger::LEVEL_INFO, 'application.inventory.product.admin');

        try {
            $searchData = isset($_GET['Product']) ? $_GET['Product'] : null;
            $model = ProductHelper::prepareProductSearch($searchData);
            $categories = ProductHelper::getCategoryOptions();

            if ($searchData) {
                Yii::log("Applied search filters for product admin", CLogger::LEVEL_INFO, 'application.inventory.product.admin');
            }

            Yii::log("Successfully loaded product admin view", CLogger::LEVEL_INFO, 'application.inventory.product.admin');

            $this->render('admin', array(
                'model' => $model,
                'categories' => $categories,
            ));
        } catch (Exception $e) {
            Yii::log("Error in actionAdmin: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.admin');
            throw new CHttpException(500, 'Error loading product administration.');
        }
    }

    public function loadModel($id)
    {
        return ProductHelper::loadProductById($id);
    }

    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'product-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }
}
