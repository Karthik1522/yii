<?php
/* @var $this CategoryController */
/* @var $model Category */
/* @var $parentCategories array */

$this->breadcrumbs = array(
    'Inventory' => array('/inventory'),
    'Categories' => array('admin'),
    $model->name => array('view', 'id' => $model->_id),
    'Update',
);

$this->menu = array(
    array('label' => 'List Categories', 'url' => array('admin')),
    array('label' => 'Create Category', 'url' => array('create'), 'visible' => Yii::app()->user->hasRole(array("manager", "admin"))),
    array('label' => 'View Category', 'url' => array('view', 'id' => $model->_id)),
    array('label' => 'Manage Categories', 'url' => array('admin'), 'visible' => Yii::app()->user->hasRole(array("manager", "admin"))),
);
?>

<h1>Update Category: <?php echo CHtml::encode($model->name); ?></h1>

<?php $this->renderPartial('_form', array(
    'model' => $model,
    'parentCategories' => $parentCategories,
)); ?>