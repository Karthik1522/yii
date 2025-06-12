<?php

use MongoDB\BSON\ObjectId;

/**
 * CategoryHelper - Business logic helper for Category operations
 *
 * This helper class extracts core business logic from CategoryController
 * to improve testability, maintainability, and reusability across controllers.
 */
class CategoryHelper
{
    /**
     * Validates and loads a category model by ID
     *
     * @param string $id Category ID
     * @return Category The loaded category model
     * @throws CHttpException if category not found or invalid ID format
     */
    public static function loadCategoryById($id)
    {
        Yii::log("Loading category model with ID: {$id}", CLogger::LEVEL_TRACE, 'application.inventory.category.helper.loadCategoryById');

        try {
            if (!preg_match('/^[a-f\d]{24}$/i', (string)$id)) {
                Yii::log("Invalid category ID format: {$id}", CLogger::LEVEL_WARNING, 'application.inventory.category.helper.loadCategoryById');
                throw new CHttpException(400, 'Invalid Category ID format.');
            }

            $model = Category::model()->findByPk(new ObjectId($id));

            if ($model !== null) {
                Yii::log("Successfully loaded category model: {$model->name}", CLogger::LEVEL_TRACE, 'application.inventory.category.helper.loadCategoryById');
                return $model;
            }

            Yii::log("Category not found with ID: {$id}", CLogger::LEVEL_WARNING, 'application.inventory.category.helper.loadCategoryById');
            throw new CHttpException(404, 'The requested category does not exist.');
        } catch (CHttpException $e) {
            throw $e;
        } catch (Exception $e) {
            Yii::log("Error loading category with ID {$id}: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.category.helper.loadCategoryById');
            throw new CHttpException(500, 'Error retrieving category data.');
        }
    }

    /**
     * Creates a new category with the provided data
     *
     * @param array $data Category data from form submission
     * @return array Result array with 'success' boolean, 'model' object, and 'message' string
     */
    public static function createCategory($data)
    {
        Yii::log("Processing category creation", CLogger::LEVEL_INFO, 'application.inventory.category.helper.createCategory');

        try {
            $model = new Category();
            $model->attributes = $data;

            if ($model->save()) {
                Yii::log("Successfully created category: {$model->name} with ID: {$model->_id}", CLogger::LEVEL_INFO, 'application.inventory.category.helper.createCategory');
                return [
                    'success' => true,
                    'model' => $model,
                    'message' => 'Category Created Successfully'
                ];
            } else {
                Yii::log("Category creation failed. Errors: " . CVarDumper::dumpAsString($model->getErrors()), CLogger::LEVEL_ERROR, 'application.inventory.category.helper.createCategory');
                return [
                    'success' => false,
                    'model' => $model,
                    'message' => 'Error creating category. ' . CHtml::errorSummary($model)
                ];
            }
        } catch (Exception $e) {
            Yii::log("Error in createCategory: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.category.helper.createCategory');
            throw new CHttpException(500, 'Error creating category.');
        }
    }

    /**
     * Updates an existing category with the provided data
     *
     * @param Category $model The category model to update
     * @param array $data Category data from form submission
     * @return array Result array with 'success' boolean, 'model' object, and 'message' string
     */
    public static function updateCategory($model, $data)
    {
        Yii::log("Processing category update for ID: {$model->_id}", CLogger::LEVEL_INFO, 'application.inventory.category.helper.updateCategory');

        try {
            $model->attributes = $data;

            if ($model->save()) {
                Yii::log("Successfully updated category: {$model->name} with ID: {$model->_id}", CLogger::LEVEL_INFO, 'application.inventory.category.helper.updateCategory');
                return [
                    'success' => true,
                    'model' => $model,
                    'message' => 'Category Updated Successfully'
                ];
            } else {
                Yii::log("Category update failed. ID: {$model->_id}. Errors: " . CVarDumper::dumpAsString($model->getErrors()), CLogger::LEVEL_ERROR, 'application.inventory.category.helper.updateCategory');
                return [
                    'success' => false,
                    'model' => $model,
                    'message' => 'Error updating category. ' . CHtml::errorSummary($model)
                ];
            }
        } catch (Exception $e) {
            Yii::log("Error in updateCategory for category ID {$model->_id}: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.category.helper.updateCategory');
            throw new CHttpException(500, 'Error updating category.');
        }
    }

    /**
     * Validates if a category can be deleted and performs the deletion
     *
     * @param Category $model The category model to delete
     * @return array Result array with 'success' boolean and 'message' string
     */
    public static function deleteCategory($model)
    {
        Yii::log("Processing category deletion for ID: {$model->_id}", CLogger::LEVEL_INFO, 'application.inventory.category.helper.deleteCategory');

        try {
            // Check for associated products
            $productCount = Product::model()->countByAttributes(array('category_id' => (string)$model->_id));
            if ($productCount > 0) {
                $message = "Cannot delete category '{$model->name}'. It is associated with {$productCount} product(s).";
                Yii::log("Cannot delete category due to associated products. Category: {$model->name}, Product count: {$productCount}", CLogger::LEVEL_WARNING, 'application.inventory.category.helper.deleteCategory');
                return [
                    'success' => false,
                    'message' => $message
                ];
            }

            // Check for child categories
            $childCategoryCount = Category::model()->countByAttributes(array('parent_id' => (string)$model->_id));
            if ($childCategoryCount > 0) {
                $message = "Cannot delete category '{$model->name}'. It is a parent to {$childCategoryCount} other categor(y/ies). Please reassign or delete child categories first.";
                Yii::log("Cannot delete category due to child categories. Category: {$model->name}, Child count: {$childCategoryCount}", CLogger::LEVEL_WARNING, 'application.inventory.category.helper.deleteCategory');
                return [
                    'success' => false,
                    'message' => $message
                ];
            }

            // Perform deletion
            if ($model->delete()) {
                Yii::log("Successfully deleted category: {$model->name} with ID: {$model->_id}", CLogger::LEVEL_INFO, 'application.inventory.category.helper.deleteCategory');
                return [
                    'success' => true,
                    'message' => 'Category deleted successfully.'
                ];
            } else {
                $message = 'Error deleting category.';
                if (YII_DEBUG && $model->hasErrors()) {
                    $message .= ' Details: ' . CHtml::errorSummary($model);
                }
                Yii::log($message . " Category ID: " . $model->_id, CLogger::LEVEL_ERROR, 'application.inventory.category.helper.deleteCategory');
                return [
                    'success' => false,
                    'message' => $message
                ];
            }
        } catch (Exception $e) {
            Yii::log("Error in deleteCategory for category ID {$model->_id}: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.category.helper.deleteCategory');
            throw new CHttpException(500, 'Error deleting category.');
        }
    }

    /**
     * Prepares category data for admin/listing view
     *
     * @param array $searchData Optional search criteria
     * @return Category The search model for data provider
     */
    public static function prepareCategorySearch($searchData = null)
    {
        Yii::log("Preparing category search", CLogger::LEVEL_INFO, 'application.inventory.category.helper.prepareCategorySearch');

        try {
            $model = new Category('search');
            $model->unsetAttributes();

            if ($searchData) {
                $model->attributes = $searchData;
                Yii::log("Applied search filters for category search", CLogger::LEVEL_INFO, 'application.inventory.category.helper.prepareCategorySearch');
            }

            return $model;
        } catch (Exception $e) {
            Yii::log("Error in prepareCategorySearch: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.category.helper.prepareCategorySearch');
            throw new CHttpException(500, 'Error preparing category search.');
        }
    }

    /**
     * Gets parent category options for form dropdowns
     *
     * @param string|null $excludeId Category ID to exclude from options (usually current category being edited)
     * @return array Parent category options for dropdown
     */
    public static function getParentCategoryOptions($excludeId = null)
    {
        try {
            return Category::getCategoryOptions($excludeId);
        } catch (Exception $e) {
            Yii::log("Error getting parent category options: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.inventory.category.helper.getParentCategoryOptions');
            return array();
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
