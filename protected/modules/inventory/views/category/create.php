<?php
/* @var $this CategoryController */
/* @var $model Category */
/* @var $parentCategories array */

$this->breadcrumbs = array(
    'Categories' => array('admin'),
    'Create',
);

?>

<h1>Create Category</h1>

<?php $this->renderPartial('_form', array(
    'model' => $model,
    'parentCategories' => $parentCategories,
)); ?>