<?php
/* @var $this CategoryController */
/* @var $model Category */

$this->breadcrumbs = array(
    'Categories' => array('admin'),
    'Manage',
);

$this->menu = array(
    array('label' => 'Create Category', 'url' => array('create'), 'visible' => Yii::app()->user->hasRole(array("manager", "admin"))),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#category-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<h1>Manage Categories</h1>

<p>
You may optionally enter comparison operators (<b><</b>, <b><=</b>, <b>></b>, <b>>=</b>, <b><></b>
or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
You can also search for root categories by entering "null" in the Parent Category field.
</p>

<?php echo CHtml::link('Advanced Search', '#', array('class' => 'search-button')); ?>
<div class="search-form" style="display:none">
<?php $this->renderPartial('_search', array(
    'model' => $model,
)); ?>
</div><!-- search-form -->

<?php $this->widget('zii.widgets.grid.CGridView', array(
    'id' => 'category-grid',
    'dataProvider' => $model->searchProvider(), 
    'filter' => $model, 
    'columns' => array(
        array(
            'name' => '_id',
            'header' => 'ID',
            'value' => '(string)$data->_id',
            'filter' => false
        ),
        'name',
        'description:ntext',
        array(
            'name' => 'parent_id',
            'header' => 'Parent Category',
            'value' => '$data->getParentName()', 
            'filter' => CHtml::activeDropDownList( 
                $model,
                'parent_id',
                Category::getCategoryOptions($model->_id ? (string)$model->_id : null), 
                array('prompt' => '') 
            ),
        ),
        'slug',
        array(
            'name' => 'created_at',
            'header' => 'Created At',
            'value' => '($data->created_at instanceof MongoDate) ? date("Y-m-d H:i:s", $data->created_at->sec) : "N/A"',
            'filter' => false,
        ),
        array(
            'name' => 'updated_at',
            'header' => 'Updated At',
            'value' => '($data->updated_at instanceof MongoDate) ? date("Y-m-d H:i:s", $data->updated_at->sec) : "N/A"',
            'filter' => false,
        ),
        array(
            'class' => 'CButtonColumn',
            'template' => '{view} {update} {delete}', // Standard template
            'viewButtonUrl' => 'Yii::app()->createUrl("/inventory/category/view", array("id"=>(string)$data->_id))',
            'updateButtonUrl' => 'Yii::app()->createUrl("/inventory/category/update", array("id"=>(string)$data->_id))',
            'deleteButtonUrl' => 'Yii::app()->createUrl("/inventory/category/delete", array("id"=>(string)$data->_id))',
            'buttons' => array( // Keep your FontAwesome icons if you have FA setup
                'view' => array(
                    'label' => '<i class="fa fa-eye" style="color: blue;"></i>', 'imageUrl' => false, 'options' => array('title' => 'View', 'style'=>'margin-right:5px;'),
                    'visible' => 'Yii::app()->user->hasRole(array("manager", "admin", "staff"))', // Staff can also view
                ),
                'update' => array(
                    'label' => '<i class="fa fa-pencil" style="color: green;"></i>', 'imageUrl' => false, 'options' => array('title' => 'Update', 'style'=>'margin-right:5px;'),
                    'visible' => 'Yii::app()->user->hasRole(array("manager", "admin"))',
                ),
                'delete' => array(
                    'label' => '<i class="fa fa-trash" style="color: red;"></i>', 'imageUrl' => false, 'options' => array('title' => 'Delete'),
                    'visible' => 'Yii::app()->user->hasRole(array("manager", "admin"))',
                ),
            ),
        ),
    ),
)); ?>

