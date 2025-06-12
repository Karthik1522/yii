<?php
/* @var $this ProductController */
/* @var $model Product */

$this->breadcrumbs = array(
    // 'Products'=>array('admin'),
    'Manage',
);

$this->menu = array(
    array('label' => 'Create Product', 'url' => array('create')),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
    $('.search-form').toggle();
    return false;
});
$('.search-form form').submit(function(){
    $('#product-grid').yiiGridView('update', {
        data: $(this).serialize()
    });
    return false;
});
");
?>

<h1>Manage Products</h1>

<p>
You may optionally enter comparison operators (<b><</b>, <b><=</b>, <b>></b>, <b>>=</b>, <b><></b>
or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>

<?php echo CHtml::link('Advanced Search', '#', array('class' => 'search-button')); ?>
<div class="search-form" style="display:none">
<?php $this->renderPartial('_search', array(
    'model' => $model,
    'categories' => $categories,
)); ?>
</div><!-- search-form -->

<?php $this->widget('zii.widgets.grid.CGridView', array(
    'id' => 'product-grid',
    'dataProvider' => $model->searchProvider(),
    'filter' => $model,
    'columns' => array(
        array(
            'name' => '_id',
            'header' => 'Product ID',
            'value' => '(string)$data->_id',
            'htmlOptions' => array('width' => '80px'),
            'filter' => false
        ),
        array(
            'name' => 'name',
            'header' => 'Name',
        ),
        array(
            'name' => 'sku',
            'header' => 'SKU',
        ),
        array(
            'name' => 'price',
            'header' => 'Price',
        ),
        array(
            'name' => 'category_id',
            'header' => 'Category ID',
            'htmlOptions' => array('width' => '80px'),
        ),
        array(
            'name' => 'created_at',
            'header' => 'Created At',
            'value' => 'Yii::app()->dateFormatter->formatDateTime($data->created_at->toDateTime()->getTimestamp(), "medium", "short")',
            'filter' => false,
            'htmlOptions' => array('width' => '100px'),
        ),
        array(
            'class' => 'CButtonColumn',
            'template' => '{view}{update}{delete}',
            'htmlOptions' => array('width' => '120px', 'style' => 'text-align: center;'),
            'headerHtmlOptions' => array('style' => 'text-align: center;'),
            'buttons' => array(
                'view' => array(
                    'label' => '<i class="fa fa-eye" style="color: blue;"></i>',
                    'options' => array('title' => 'View', 'style' => 'margin: 0 5px;'),
                    'imageUrl' => false,
                    'url' => 'Yii::app()->createUrl("/inventory/product/view", array("id" => (string)$data->_id))',
                ),
                'update' => array(
                    'label' => '<i class="fa fa-pencil" style="color: green;"></i>',
                    'options' => array('title' => 'Edit', 'style' => 'margin: 0 5px;'),
                    'imageUrl' => false,
                    'url' => 'Yii::app()->createUrl("/inventory/product/update", array("id" => (string)$data->_id))',
                ),
                'delete' => array(
                    'label' => '<i class="fa fa-trash" style="color: red;"></i>',
                    'options' => array('title' => 'Delete', 'style' => 'margin: 0 5px;'),
                    'imageUrl' => false,
                    'url' => 'Yii::app()->createUrl("/inventory/product/delete", array("id" => (string)$data->_id))',
                ),

            ),
        ),
    ),
)); ?>

<script type="text/javascript">
jQuery(function($) {
    $('#product-grid').on('click', '.delete-button', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this item?')) {
            return false;
        }
        var deleteUrl = $(this).attr('href');
        
        $.ajax({
            url: deleteUrl,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Option 1: Show message and refresh grid (more AJAX-like)
                    if(response.message) alert(response.message); // Or use a nicer notification
                    $('#product-grid').yiiGridView('update');
                    
                    // Option 2: Show message and reload page (simpler, as you had)
                    // if(response.message) alert(response.message);
                    // window.location.href = '<?php echo $this->createUrl("admin"); ?>';
                    var row = $(this).closest('tr').remove(); 
                } else {
                    alert('Error: ' + (response.message || 'An unknown error occurred.'));
                }
            },
            error: function(xhr, status, error) {
                alert('AJAX request failed: ' + error);
            }
        });
        return false;
    });
});
</script>