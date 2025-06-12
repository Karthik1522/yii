<?php

$this->breadcrumbs = array(
    'Categories' => array('admin'),
    $model->name,
);

$this->menu = array(
    array('label' => 'List Categories' , 'url' => array('admin')),
    array('label' => 'Create Category' , 'url' => array('create') , 'visible' => Yii::app()->user->hasRole(array('manager' ,'admin'))),
    array('label' => 'Update Category', 'url' => array('update', 'id' => $model->_id), 'visible' => Yii::app()->user->hasRole(array("manager", "admin"))),
    array('label' => 'Delete Category', 'url' => '#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->_id), 'confirm' => 'Are you sure you want to delete this item?'), 'visible' => Yii::app()->user->hasRole(array("manager", "admin"))),
);

?>

<h1>View Category: <?php echo CHtml::encode($model->name); ?></h1>

<?php

$this->widget('zii.widgets.CDetailView', array(
    'data' => $model,
    'attributes' => [
        array(
            'name' => '_id',
            'header' => 'ID'
        ),
        'name',
        'description',
        array(
            'name' => 'parent_id',
            'value' => $model->getParentName() . ($model->parent_id ? '  (ID : ' . CHtml::encode((string)$model->parent_id) . ')' : '')
        ),
        'slug',
        array(
            'name' => 'created_at',
            'header' => 'Created At',
            'value' =>  date('Y-m-d H:i:s', is_object($model->created_at) ? $model->created_at->toDateTime()->getTimestamp() : (int)$model->created_at)
        ),
        array(
            'name' => 'updated_at',
            'header' => 'Updated At',
            'value' =>  date('Y-m-d H:i:s', is_object($model->updated_at) ? $model->updated_at->toDateTime()->getTimestamp() : (int)$model->updated_at)
        )

    ],

));

?>