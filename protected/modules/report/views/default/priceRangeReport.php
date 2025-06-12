<?php
/* @var $this DefaultController */
/* @var $dataProvider CArrayDataProvider */

$this->breadcrumbs=array(
    'Reports' => array('index'),
    'Price Range Report',
);
?>

<h1>Product Price Range Report</h1>

<?php if(Yii::app()->user->hasFlash('error')): ?>
    <div class="flash-error">
        <?php echo Yii::app()->user->getFlash('error'); ?>
    </div>
<?php endif; ?>

<?php $this->widget('zii.widgets.grid.CGridView', array(
    'id'=>'price-range-grid',
    'dataProvider'=>$dataProvider,
    'columns'=>array(
        array(
            'name' => 'priceRange',
            'header' => 'Price Range',
            'value' => 'is_numeric($data["priceRange"]) ? ("$" . $data["priceRange"] . (isset($this->grid->controller->priceBoundaries[$data["priceRange"]]) ? " - $" . ($this->grid->controller->priceBoundaries[$data["priceRange"]]-0.01) : "+")) : $data["priceRange"]',
        ),
        array(
            'name' => 'productCount',
            'header' => 'Number of Products',
            'htmlOptions' => array('style' => 'text-align:right;'),
        ),
        array(
            'name' => 'totalStockValueInBucket',
            'header' => 'Total Stock Value in Range',
            'value' => 'Yii::app()->numberFormatter->formatCurrency($data["totalStockValueInBucket"], "USD")',
            'htmlOptions' => array('style' => 'text-align:right;'),
        ),
    ),
)); ?>