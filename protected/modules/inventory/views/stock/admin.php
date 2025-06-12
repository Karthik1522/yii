<?php
/* @var $this StockLogController */
/* @var $model StockLog */

$this->breadcrumbs = array(
    'Stock Logs' => array('admin'),
    'Manage',
);

Yii::app()->clientScript->registerScript('search-stocklog', "
$('.search-button').click(function(){
    $('.search-form').toggle();
    return false;
});
// Ensure your search form has id 'stocklog-search-form' if you use this
$('.search-form form').submit(function(){
    $('#stock-log-grid').yiiGridView('update', {
        data: $(this).serialize()
    });
    return false;
});
");
?>


<h1>Manage Stock Logs</h1>
<p>View and filter stock movement history.</p>

<?php $this->widget('zii.widgets.grid.CGridView', array(
    'id' => 'stock-log-grid',
    'dataProvider' => $model->searchProvider(),
    'filter' => $model,
    'columns' => array(
        array(
            'name' => '_id',
            'header' => 'Log ID',
            'filter' => false
        ),
        array(
            'name' => 'product_id',
            'header' => 'Product',
            'value' => 'CHtml::encode($data->product_id)', 
            'type' => 'raw',
            'htmlOptions' => array('style' => 'width:200px;'),
        ),
        array(
            'name' => 'type',
            'value' => 'StockLog::getTypeName($data->type)',
            'filter' => StockLog::getTypeOptions(),
            'htmlOptions' => array('style' => 'width:120px;'),
        ),
        array(
            'name' => 'quantity_change',
            'htmlOptions' => array('style' => 'width:100px; text-align:right;'),
        ),
        array(
            'name' => 'quantity_after_change',
            'header' => 'Qty After',
            'htmlOptions' => array('style' => 'width:100px; text-align:right;'),
        ),
        array(
            'name' => 'reason',
            'filter' => false
        ),
        array(
            'name' => 'user_id', 
            'header' => 'Username',
            'value' => 'CHtml::encode($data->user_id)',
            'htmlOptions' => array('style' => 'width:150px;'),
        ),
        array(
            'name' => 'updated_at',
            'header' => 'Date Logged',
            // Value expression for MongoDate
            'value' => '($data->updated_at instanceof MongoDate) ? Yii::app()->dateFormatter->formatDateTime($data->updated_at->toDateTime()->getTimestamp(), "medium", "short") : "Invalid Date"',
            'filter' => false,
            'htmlOptions' => array('width' => '160px'),
        ),
    ),
)); ?>