<?php
/* @var $this ProductController */
/* @var $model Product */

$this->breadcrumbs=array(
    'Products'=>array('admin'),
    'Create',
);

$this->menu=array(
    array('label'=>'List Products', 'url'=>array('admin'), 'visible'=>Yii::app()->user->hasRole(array('admin','manager','staff'))),
    array('label'=>'Manage Products', 'url'=>array('admin'), 'visible'=>Yii::app()->user->hasRole(array('admin','manager'))),
);
?>

<h1>Create Product</h1>

<?php $this->renderPartial('_form', array(
    'model' => $model,
    'categories' => $categories,
)); ?>

