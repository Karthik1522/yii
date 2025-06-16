<?php

use MongoDB\BSON\ObjectId;

class ProductHelper
{
    /**
     * Validates and loads a product model by ID
     *
     * @param string|ObjectId $id Product ID
     * @return Product The loaded product model
     * @throws CHttpException if product not found or invalid ID format
     */
    public static function loadProductById($id)
    {
        Yii::log("Loading product model with ID: {$id}", CLogger::LEVEL_TRACE, 'application.inventory.product.helper.loadProductById');

        try {
            if (!preg_match('/^[a-f\d]{24}$/i', (string)$id)) {
                Yii::log("Invalid product ID format: {$id}", CLogger::LEVEL_WARNING, 'application.inventory.product.helper.loadProductById');
                throw new CHttpException(400, 'Invalid Product ID format.');
            }

            $model = Product::model()->findByPk(new ObjectId($id));

            if ($model === null) {
                Yii::log("Product not found with ID: {$id}", CLogger::LEVEL_WARNING, 'application.inventory.product.helper.loadProductById');
                throw new CHttpException(404, 'The requested product does not exist.');
            }

            Yii::log("Successfully loaded product model: {$model->name}", CLogger::LEVEL_TRACE, 'application.inventory.product.helper.loadProductById');
            return $model;
        } catch (CHttpException $e) {
            throw $e;
        } catch (Exception $e) {
            Yii::log("Error loading product with ID {$id}: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.helper.loadProductById');
            throw new CHttpException(500, 'Error retrieving product data.');
        }
    }

    /**
     * Processes image upload for a product
     *
     * @param CUploadedFile $uploadedFile The uploaded file
     * @return array Result array with 'success' boolean, 'imageUrl' string, and 's3Key' string
     */
    public static function processImageUpload($uploadedFile)
    {
        if (!$uploadedFile) {
            return ['success' => false, 'imageUrl' => null, 's3Key' => null];
        }

        try {
            Yii::log("Processing image upload: {$uploadedFile->name}", CLogger::LEVEL_INFO, 'application.inventory.product.helper.processImageUpload');

            $s3Key = 'products/' . time() . '_' . UtilityHelpers::sanitizie($uploadedFile->name);
            $s3Url = Yii::app()->s3uploader->uploadFile($uploadedFile->tempName, $s3Key);

            if ($s3Url) {
                Yii::log("Successfully uploaded image to S3: {$s3Key}", CLogger::LEVEL_INFO, 'application.inventory.product.helper.processImageUpload');
                return ['success' => true, 'imageUrl' => $s3Url, 's3Key' => $s3Key];
            } else {
                Yii::log("Failed to upload image to S3", CLogger::LEVEL_ERROR, 'application.inventory.product.helper.processImageUpload');
                return ['success' => false, 'imageUrl' => null, 's3Key' => null, 'error' => 'Failed to upload image to S3.'];
            }
        } catch (Exception $e) {
            Yii::log("Error processing image upload: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.helper.processImageUpload');
            return ['success' => false, 'imageUrl' => null, 's3Key' => null, 'error' => 'Error processing image upload.'];
        }
    }

    /**
     * Creates a new product with the provided data
     *
     * @param array $data Product data from form submission
     * @param CUploadedFile|null $uploadedFile Optional uploaded image file
     * @return array Result array with 'success' boolean, 'model' object, 'message' string, and optional 's3Key'
     */
    public static function createProduct($data, $uploadedFile = null)
    {
        Yii::log("Processing product creation", CLogger::LEVEL_INFO, 'application.inventory.product.helper.createProduct');

        try {
            $model = new Product();
            $model->attributes = $data;

            $s3FileKey = null;
            if ($uploadedFile) {
                $uploadResult = self::processImageUpload($uploadedFile);
                if ($uploadResult['success']) {
                    $model->image_url = $uploadResult['imageUrl'];
                    $s3FileKey = $uploadResult['s3Key'];
                } else {
                    $model->addError('image_filename_upload', $uploadResult['error']);
                }
            }

            $isValid = $model->validate();

            if ($isValid && $model->save(false)) {
                self::recordStockLog($model, 'create');
                Yii::log("Successfully created product: {$model->name} with ID: {$model->_id}", CLogger::LEVEL_INFO, 'application.inventory.product.helper.createProduct');
                return [
                    'success' => true,
                    'model' => $model,
                    'message' => 'Product created successfully.',
                    's3Key' => $s3FileKey
                ];
            } else {
                // Clean up uploaded file if validation failed
                if ($s3FileKey && Yii::app()->s3uploader->fileExists($s3FileKey)) {
                    Yii::app()->s3uploader->deleteFile($s3FileKey);
                    $model->image_url = null;
                }
                Yii::log("Product creation failed. Errors: " . CVarDumper::dumpAsString($model->getErrors()), CLogger::LEVEL_ERROR, 'application.inventory.product.helper.createProduct');
                return [
                    'success' => false,
                    'model' => $model,
                    'message' => 'Error creating product. Please check the form for errors.'
                ];
            }
        } catch (Exception $e) {
            Yii::log("Error in createProduct: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.helper.createProduct');
            throw new CHttpException(500, 'Error creating product.');
        }
    }

    /**
     * Updates an existing product with the provided data
     *
     * @param Product $model The product model to update
     * @param array $data Product data from form submission
     * @param CUploadedFile|null $uploadedFile Optional uploaded image file
     * @param bool $clearImage Whether to clear the existing image
     * @return array Result array with 'success' boolean, 'model' object, and 'message' string
     */
    public static function updateProduct($model, $data, $uploadedFile = null, $clearImage = false)
    {
        Yii::log("Processing product update for ID: {$model->_id}", CLogger::LEVEL_INFO, 'application.inventory.product.helper.updateProduct');

        try {
            $imageUrl = $model->image_url;
            $oldS3Key = Yii::app()->s3uploader->getS3KeyFromUrl($imageUrl);

            $model->attributes = $data;
            $model->image_url = $imageUrl; // Preserve original image URL

            $newS3FileKey = null;

            if ($uploadedFile) {
                $uploadResult = self::processImageUpload($uploadedFile);
                if ($uploadResult['success']) {
                    $model->image_url = $uploadResult['imageUrl'];
                    $newS3FileKey = $uploadResult['s3Key'];
                } else {
                    $model->addError('image_filename_upload', $uploadResult['error']);
                }
            } elseif ($clearImage) {
                if (Yii::app()->s3uploader->fileExists($oldS3Key)) {
                    Yii::app()->s3uploader->deleteFile($oldS3Key);
                    $model->image_url = null;
                    Yii::log("Cleared product image for ID: {$model->_id}", CLogger::LEVEL_INFO, 'application.inventory.product.helper.updateProduct');
                }
            }

            $isValid = $model->validate();

            if ($isValid && $model->save(false)) {
                self::recordStockLog($model, 'update');

                // Clean up old image if a new one was uploaded or image was cleared
                if (($newS3FileKey && !empty($oldS3Key) && $oldS3Key !== $newS3FileKey) || ($clearImage && $oldS3Key)) {
                    if (Yii::app()->s3uploader->fileExists($oldS3Key)) {
                        Yii::app()->s3uploader->deleteFile($oldS3Key);
                    }
                }

                Yii::log("Successfully updated product: {$model->name} with ID: {$model->_id}", CLogger::LEVEL_INFO, 'application.inventory.product.helper.updateProduct');
                return [
                    'success' => true,
                    'model' => $model,
                    'message' => 'Product updated successfully.'
                ];
            } else {
                // Clean up new file if validation failed
                if ($newS3FileKey && Yii::app()->s3uploader->fileExists($newS3FileKey)) {
                    Yii::app()->s3uploader->deleteFile($newS3FileKey);
                    $model->image_url = Yii::app()->s3uploader->s3Bucket . '.s3.' . Yii::app()->s3uploader->getRegion() . '.amazonaws.com/' . $oldS3Key;
                }
                Yii::log("Product update failed. ID: {$model->_id}. Errors: " . CVarDumper::dumpAsString($model->getErrors()), CLogger::LEVEL_ERROR, 'application.inventory.product.helper.updateProduct');
                return [
                    'success' => false,
                    'model' => $model,
                    'message' => 'Error updating product. Please check the form for errors.'
                ];
            }
        } catch (Exception $e) {
            Yii::log("Error in updateProduct for product ID {$model->_id}: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.helper.updateProduct');
            throw new CHttpException(500, 'Error updating product.');
        }
    }

    /**
     * Deletes a product and cleans up associated resources
     *
     * @param Product $model The product model to delete
     * @return array Result array with 'success' boolean and 'message' string
     */
    public static function deleteProduct($model)
    {
        Yii::log("Processing product deletion for ID: {$model->_id}", CLogger::LEVEL_INFO, 'application.inventory.product.helper.deleteProduct');

        try {
            $s3KeyToDelete = Yii::app()->s3uploader->getS3KeyFromUrl($model->image_url);

            if ($model->delete()) {
                // Clean up S3 file
                if ($s3KeyToDelete && Yii::app()->s3uploader->fileExists($s3KeyToDelete)) {
                    Yii::app()->s3uploader->deleteFile($s3KeyToDelete);
                    Yii::log("Deleted S3 file: {$s3KeyToDelete}", CLogger::LEVEL_INFO, 'application.inventory.product.helper.deleteProduct');
                }

                Yii::log("Successfully deleted product: {$model->name} with ID: {$model->_id}", CLogger::LEVEL_INFO, 'application.inventory.product.helper.deleteProduct');
                return [
                    'success' => true,
                    'message' => 'Product deleted successfully.'
                ];
            } else {
                $errorMessage = 'Error deleting product from database.';
                if (YII_DEBUG && $model->hasErrors()) {
                    $errorMessage .= ' Details: ' . CHtml::errorSummary($model);
                }
                Yii::log($errorMessage . " Product ID: " . $model->_id, CLogger::LEVEL_ERROR, 'application.inventory.product.helper.deleteProduct');
                return [
                    'success' => false,
                    'message' => $errorMessage
                ];
            }
        } catch (Exception $e) {
            Yii::log("Error in deleteProduct for product ID {$model->_id}: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.helper.deleteProduct');
            throw new CHttpException(500, 'Error deleting product.');
        }
    }

    /**
     * Prepares product data for admin/listing view
     *
     * @param array $searchData Optional search criteria
     * @return Product The search model for data provider
     */
    public static function prepareProductSearch($searchData = null)
    {
        Yii::log("Preparing product search", CLogger::LEVEL_INFO, 'application.inventory.product.helper.prepareProductSearch');

        try {
            $model = new Product('search');
            $model->unsetAttributes();

            if ($searchData) {
                $model->attributes = $searchData;
                Yii::log("Applied search filters for product search", CLogger::LEVEL_INFO, 'application.inventory.product.helper.prepareProductSearch');
            }

            return $model;
        } catch (Exception $e) {
            Yii::log("Error in prepareProductSearch: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.helper.prepareProductSearch');
            throw new CHttpException(500, 'Error preparing product search.');
        }
    }

    /**
     * Gets category options for form dropdowns
     *
     * @return array Category options for dropdown
     */
    public static function getCategoryOptions()
    {
        try {
            return Category::model()->getCategoryOptions();
        } catch (Exception $e) {
            Yii::log("Error getting category options: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.helper.getCategoryOptions');
            return array();
        }
    }

    /**
     * Gets product image URL for display
     *
     * @param Product $model The product model
     * @return string|null The image URL or null if no image
     */
    public static function getProductImageUrl($model)
    {
        return $model->image_url ? $model->image_url : null;
    }

    /**
     * Records stock log for product operations
     *
     * @param Product $model The product model
     * @param string $method The operation method ('create' or 'update')
     * @return void
     */
    public static function recordStockLog($model, $method)
    {
        try {
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
        } catch (Exception $e) {
            Yii::log("Error recording stock log: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.product.helper.recordStockLog');
            // Don't throw exception as this is not critical for product operations
        }
    }

    /**
     * Handles AJAX response formatting
     *
     * @param array $result Result array from business logic operations
     * @param bool $isAjax Whether the request is AJAX
     * @return void
     */
    public static function handleAjaxResponse($result, $isAjax = false)
    {
        if ($isAjax) {
            $status = $result['success'] ? 'success' : 'error';
            echo CJSON::encode([
                'status' => $status,
                'message' => $result['message']
            ]);
            Yii::app()->end();
        }
    }
}
