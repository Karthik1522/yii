<?php
/* @var $this DefaultController */
/* @var $dataProvider CArrayDataProvider */
/* @var $threshold int */
/* @var $categories array */


$this->breadcrumbs=array(
    'Reports' => array('index'),
    'Low Stock Report',
);
?>

<h1>Low Stock Report (Threshold: <?php echo CHtml::encode($threshold); ?>)</h1>

<?php if(Yii::app()->user->hasFlash('error')): ?>
    <div class="flash-error">
        <?php echo Yii::app()->user->getFlash('error'); ?>
    </div>
<?php endif; ?>

<?php $this->widget('zii.widgets.grid.CGridView', array(
    'id'=>'low-stock-grid',
    'dataProvider'=>$dataProvider,
    'columns'=>array(
        array(
            'name' => 'sku',
            'header' => 'SKU',
        ),
        array(
            'name' => 'name',
            'header' => 'Product Name',
        ),
        // Uncomment if you project and want to display category_id
        /*
        array(
            'name' => 'category_id',
            'header' => 'Category',
            'value' => 'isset($data["category_id"]) && isset($this->grid->controller->categories[(string)$data["category_id"]]) ? $this->grid->controller->categories[(string)$data["category_id"]] : "N/A"',
        ),
        */
        array(
            'name' => 'quantity',
            'header' => 'Current Quantity',
            'htmlOptions' => array('style' => 'text-align:right;'),
        ),
    ),
)); ?>