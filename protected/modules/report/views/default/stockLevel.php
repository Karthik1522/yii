<?php
/* @var $this DefaultController */
/* @var $dataProvider CArrayDataProvider */
/* @var $totalStockValue float */
/* @var $categories array */

$this->breadcrumbs=array(
    'Reports' => array('index'),
    'Stock Level',
);
?>

<h1>Stock Level Report</h1>

<?php if(Yii::app()->user->hasFlash('error')): ?>
    <div class="flash-error">
        <?php echo Yii::app()->user->getFlash('error'); ?>
    </div>
<?php endif; ?>

<p>
    <strong>Total Stock Value:</strong>
    <?php echo Yii::app()->numberFormatter->formatCurrency($totalStockValue, "USD"); // Adjust currency as needed ?>
</p>

<?php $this->widget('zii.widgets.grid.CGridView', array(
    'id'=>'stock-level-grid',
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
        array(
            'name' => 'categoryName',
            'header' => 'Category',
            'value' => 'isset($data["categoryName"]) ? $data["categoryName"] : "N/A"',
            'sortable' => true,
        ),        
        array(
            'name' => 'quantity',
            'header' => 'Quantity',
            'htmlOptions' => array('style' => 'text-align:right;'),
        ),
        array(
            'name' => 'price',
            'header' => 'Unit Price',
            'value' => 'Yii::app()->numberFormatter->formatCurrency($data["price"], "USD")',
            'htmlOptions' => array('style' => 'text-align:right;'),
        ),
        array(
            'name' => 'stock_value',
            'header' => 'Stock Value',
            'value' => 'Yii::app()->numberFormatter->formatCurrency($data["stock_value"], "USD")',
            'htmlOptions' => array('style' => 'text-align:right;'),
        ),
    ),
)); ?>