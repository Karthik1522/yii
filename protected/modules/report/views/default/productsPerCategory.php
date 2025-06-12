<?php
/* @var $this DefaultController */
/* @var $dataProvider CArrayDataProvider */
/* @var $categories array */

$this->breadcrumbs=array(
    'Reports' => array('index'),
    'Products Per Category',
);
?>

<h1>Products Per Category Report</h1>

<?php if(Yii::app()->user->hasFlash('error')): ?>
    <div class="flash-error">
        <?php echo Yii::app()->user->getFlash('error'); ?>
    </div>
<?php endif; ?>

<?php $this->widget('zii.widgets.grid.CGridView', array(
    'id'=>'products-per-category-grid',
    'dataProvider'=>$dataProvider,
    'columns'=>array(
        array(
            'name' => 'categoryId',
            'header' => 'Category',
            // Assuming $this->grid->controller->categories is available (see note in stockLevel.php)
            'value' => 'isset($data["categoryId"]) && isset($this->grid->controller->categories[(string)$data["categoryId"]]) ? $this->grid->controller->categories[(string)$data["categoryId"]] : ($data["categoryId"] ? $data["categoryId"] : "N/A")',
        ),
        array(
            'name' => 'categoryName',
            'header' => 'Category Name',
        ),
        array(
            'name' => 'productCount',
            'header' => 'Number of Products',
            'htmlOptions' => array('style' => 'text-align:right;'),
        ),
    ),
)); ?>