<?php
/* @var $this ProductController */
/* @var $model Product */

$this->breadcrumbs=array(
    'Inventory'=>array('/inventory'),
    'Products'=>array('admin'),
    $model->name=>array('view','id'=>(string)$model->_id),
    'Update',
);

$this->menu=array(
    array('label'=>'List Products', 'url'=>array('admin'), 'visible'=>Yii::app()->user->hasRole(array('admin','manager','staff'))),
    array('label'=>'Create Product', 'url'=>array('create'), 'visible'=>Yii::app()->user->hasRole(array('admin','manager'))),
    array('label'=>'View Product', 'url'=>array('view', 'id'=>(string)$model->_id), 'visible'=>Yii::app()->user->hasRole(array('admin','manager','staff'))),
    array('label'=>'Manage Products', 'url'=>array('admin'), 'visible'=>Yii::app()->user->hasRole(array('admin','manager'))),
);
?>
<h1>Update Product: <?php echo CHtml::encode($model->name); ?></h1>

<?php $this->renderPartial('_form', array(
    'model' => $model,
    'categories' => $categories,
    'productImageUrl' => $productImageUrl, // Pass this to _form
)); ?>


