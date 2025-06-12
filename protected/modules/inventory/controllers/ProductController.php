<?php

use MongoDB\BSON\ObjectId;

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
            $model = $this->loadModel(new ObjectId($id));

            $productImageUrl = null;
            if ($model->image_url) {
                $productImageUrl = $model->image_url;
            }

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

                $model->attributes = $_POST['Product'];

                $s3FileKey = null;
                $uploadedFile = CUploadedFile::getInstance($model, 'image_filename_upload');

                if ($uploadedFile) {
                    Yii::log("Processing image upload for new product", CLogger::LEVEL_INFO, 'application.inventory.product.create');

                    $s3Key = 'products/' . time() . '_' . UtilityHelpers::sanitizie($uploadedFile->name);
                    $s3Url = Yii::app()->s3uploader->uploadFile($uploadedFile->tempName, $s3Key);

                    if ($s3Url) {
                        $model->image_url = $s3Url;
                        $s3FileKey = $s3Key;
                        Yii::log("Successfully uploaded image to S3: {$s3Key}", CLogger::LEVEL_INFO, 'application.inventory.product.create');
                    } else {
                        $model->addError('image_filename_upload', 'Failed to upload image to S3.');
                        Yii::log("Failed to upload image to S3", CLogger::LEVEL_ERROR, 'application.inventory.product.create');
                    }
                }

                $isValid = $model->validate();

                if ($isValid && $model->save(false)) {
                    $this->recordStockLog($model, 'create');
                    Yii::log("Successfully created product: {$model->name} with ID: {$model->_id}", CLogger::LEVEL_INFO, 'application.inventory.product.create');
                    Yii::app()->user->setFlash('success', 'Product created successfully.');
                    $this->redirect(array('view', 'id' => (string)$model->_id));
                } else {
                    if ($s3FileKey && Yii::app()->s3uploader->fileExists($s3FileKey)) {
                        Yii::app()->s3uploader->deleteFile($s3FileKey);
                        $model->image_url = null;
                    }
                    Yii::log("Product creation failed. Errors: " . CVarDumper::dumpAsString($model->getErrors()), CLogger::LEVEL_ERROR, 'application.inventory.product.create');
                }
            }

            $categories = Category::model()->getCategoryOptions();

            $this->render('create', array(
                'model' => $model,
                'categories' => $categories,
            ));
        } catch (Exception $e) {
            Yii::log("Error in actionCreate: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.create');
            throw new CHttpException(500, 'Error creating product.');
        }
    }

    protected function recordStockLog($model, $method)
    {
        $quantity = $model->quantity ?? 0;

        $variants = $model->variants;

        if (!empty($variants)) {
            foreach ($variants as $variant) {
                $quantity += $variant->quantity;
            }

        }


        if ($quantity > 0) {
            StockLog::add(
                productId: (string)$model->_id,
                type: $method === "create" ? StockLog::TYPE_INITIAL : StockLog::TYPE_ADJUSTED,
                quantityChange: $method === "create" ? $quantity : $quantity - $model->quantity,
                newTotalProductStockLevel: $quantity,
                reason: $method === "create" ? "Initial stock for new product: " . $model->name : "Stock adjustment for product: " . $model->name . " via admin update",
                userId: Yii::app()->user->getState('username')
            );
        }


    }

    public function actionUpdate($id)
    {
        Yii::log("Starting actionUpdate for product ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.product.update');

        try {
            $model = $this->loadModel($id);

            $imageUrl = $model->image_url;
            $oldS3Key = Yii::app()->s3uploader->getS3KeyFromUrl($imageUrl);

            if (isset($_POST['Product'])) {
                Yii::log("Processing product update form submission for ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.product.update');

                $model->attributes = $_POST['Product'];
                $model->image_url = $imageUrl;

                $newS3FileKey = null;
                $clearImage = isset($_POST['clear_image_filename']) && $_POST['clear_image_filename'] == 1;
                $uploadedFile = CUploadedFile::getInstance($model, 'image_filename_upload');

                if (!empty($uploadedFile)) {
                    Yii::log("Processing image upload for product update ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.product.update');

                    $sanitizedFileName = UtilityHelpers::sanitizie($uploadedFile->name);
                    $s3Key = 'products/' . time() . '_' . $sanitizedFileName;
                    $s3Url = Yii::app()->s3uploader->uploadFile($uploadedFile->tempName, $s3Key);

                    if ($s3Url) {
                        $model->image_url = $s3Url;
                        $newS3FileKey = $s3Key;
                        Yii::log("Successfully uploaded new image to S3: {$s3Key}", CLogger::LEVEL_INFO, 'application.inventory.product.update');
                    } else {
                        $model->addError('image_filename_upload', 'Failed to upload new image to S3.');
                        Yii::log("Failed to upload new image to S3 for product ID: {$id}", CLogger::LEVEL_ERROR, 'application.inventory.product.update');
                    }
                } elseif ($clearImage) {
                    if (Yii::app()->s3uploader->fileExists($oldS3Key)) {
                        Yii::app()->s3uploader->deleteFile($oldS3Key);
                        $model->image_url = null;
                        Yii::log("Cleared product image for ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.product.update');
                    }
                }

                $isValid = $model->validate();

                if ($isValid && $model->save(false)) {
                    $this->recordStockLog($model, 'update');

                    if (($newS3FileKey && !empty($oldS3Key) && $oldS3Key !== $newS3FileKey) || ($clearImage && $oldS3Key)) {
                        if (Yii::app()->s3uploader->fileExists($oldS3Key)) {
                            Yii::app()->s3uploader->deleteFile($oldS3Key);
                            $model->image_url = null;
                        }
                    }

                    Yii::log("Successfully updated product: {$model->name} with ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.product.update');
                    Yii::app()->user->setFlash('success', 'Product updated successfully.');
                    $this->redirect(['view', 'id' => (string)$model->_id]);
                } else {
                    if ($newS3FileKey && Yii::app()->s3uploader->fileExists($newS3FileKey)) {
                        Yii::app()->s3uploader->deleteFile($newS3FileKey);
                        $model->image_url =  Yii::app()->s3uploader->s3Bucket . '.s3.' .  Yii::app()->s3uploader->getRegion() . '.amazonaws.com/' . $oldS3Key;
                    }
                    Yii::log("Product update failed. ID: {$id}. Errors: " . CVarDumper::dumpAsString($model->getErrors()), CLogger::LEVEL_ERROR, 'application.inventory.product.update');
                }
            }

            $categories = Category::model()->getCategoryOptions();

            $imageUrl = null;
            if ($model->image_url) {
                $imageUrl = $model->image_url;
            }

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
            $model = $this->loadModel($id);
            $s3KeyToDelete = Yii::app()->s3uploader->getS3KeyFromUrl($model->image_url);

            if ($model->delete()) {
                if ($s3KeyToDelete && Yii::app()->s3uploader->fileExists($s3KeyToDelete)) {
                    Yii::app()->s3uploader->deleteFile($s3KeyToDelete);
                    Yii::log("Deleted S3 file: {$s3KeyToDelete}", CLogger::LEVEL_INFO, 'application.inventory.product.delete');
                }

                Yii::log("Successfully deleted product: {$model->name} with ID: {$id}", CLogger::LEVEL_INFO, 'application.inventory.product.delete');

                $message = 'Product deleted successfully.';
                if (Yii::app()->request->isAjaxRequest) {
                    echo CJSON::encode(['status' => 'success', 'message' => $message]);
                    Yii::app()->end();
                }
            } else {
                $errorMessage = 'Error deleting product from database.';
                if (YII_DEBUG && $model->hasErrors()) {
                    $errorMessage .= ' Details: ' . CHtml::errorSummary($model);
                }
                Yii::log($errorMessage . " Product ID: " . $id, CLogger::LEVEL_ERROR, 'application.inventory.product.delete');

                if (Yii::app()->request->isAjaxRequest) {
                    echo CJSON::encode(['status' => 'error', 'message' => $errorMessage]);
                    Yii::app()->end();
                }
            }
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
            // By default, show the admin grid view for products
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
            $model = new Product('search');
            $model->unsetAttributes(); // Clear any default values
            if (isset($_GET['Product'])) {
                $model->attributes = $_GET['Product'];
                Yii::log("Applied search filters for product admin", CLogger::LEVEL_INFO, 'application.inventory.product.admin');
            }

            $categories = Category::model()->getCategoryOptions();

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
        Yii::log("Loading product model with ID: {$id}", CLogger::LEVEL_TRACE, 'application.inventory.product.loadModel');

        try {
            if (!preg_match('/^[a-f\d]{24}$/i', (string)$id)) {
                Yii::log("Invalid product ID format: {$id}", CLogger::LEVEL_WARNING, 'application.inventory.product.loadModel');
                throw new CHttpException(400, 'Invalid Product ID format.');
            }

            $model = Product::model()->findByPk(new ObjectId($id));

            if ($model === null) {
                Yii::log("Product not found with ID: {$id}", CLogger::LEVEL_WARNING, 'application.inventory.product.loadModel');
                throw new CHttpException(404, 'The requested product does not exist.');
            }

            Yii::log("Successfully loaded product model: {$model->name}", CLogger::LEVEL_TRACE, 'application.inventory.product.loadModel');
            return $model;
        } catch (CHttpException $e) {
            // Re-throw HTTP exceptions as they are
            throw $e;
        } catch (Exception $e) {
            Yii::log("Error loading product with ID {$id}: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.loadModel');
            throw new CHttpException(500, 'Error retrieving product data.');
        }
    }


    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'product-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }
}
