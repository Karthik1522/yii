<?php
/* @var $this ProductController */
/* @var $model Product */

$this->breadcrumbs=array(
    'Products'=>array('admin'),
    $model->name ? $model->name : (string)$model->_id,
);

$this->menu=array(
    array('label'=>'List Products', 'url'=>array('admin')),
    array('label'=>'Create Product', 'url'=>array('create')),
    array('label'=>'Update Product', 'url'=>array('update', 'id'=>(string)$model->_id)),
    array('label'=>'Delete Product', 'url'=>'#', 'linkOptions'=>array(
        'submit'=>array('delete','id'=>(string)$model->_id), // This is for non-AJAX POST
        'confirm' => 'Are you sure you want to delete this product?',
        'class' => 'delete-product-link' 
    )),
);
?>

<u><h1 style="font-weight: 900;">View Product #<?php echo CHtml::encode($model->name ? $model->name : (string)$model->_id); ?></h1></u>

<?php if(Yii::app()->user->hasFlash('success')): ?>
    <div class="flash-success">
        <?php echo Yii::app()->user->getFlash('success'); ?>
    </div>
<?php endif; ?>
<?php if(Yii::app()->user->hasFlash('error')): ?>
    <div class="flash-error">
        <?php echo Yii::app()->user->getFlash('error'); ?>
    </div>
<?php endif; ?>


<?php $this->widget('zii.widgets.CDetailView', array(
    'data' => $model,
    'attributes' => array(
        '_id',
        'name',
        'sku',
        'description:html', // Use :html if description can contain HTML
        array(
            'name' => 'category_id',
            'value' => $model->getCategoryName(), // Using helper method from Product model
        ),
        'price',
        'quantity',
        array(
            'name' => 'image_url',
            'header' => 'Product Image',
            'type' => 'raw',
            'value' => ($productImageUrl) ? CHtml::image($productImageUrl, $model->name, array("style"=>"max-width:300px; max-height:300px;")) : 'No image',
        ),
        array(
            'name' => 'tags',
            'value' => is_array($model->tags) ? implode(', ', $model->tags) : '',
        ),
        array(
            'name' => 'created_at',
            'value' => date('Y-m-d H:i:s', is_object($model->created_at) ? $model->created_at->toDateTime()->getTimestamp() : (int)$model->created_at),
        ),
        array(
            'name' => 'updated_at',
            'value' => date('Y-m-d H:i:s', is_object($model->updated_at) ? $model->updated_at->toDateTime()->getTimestamp() : (int)$model->updated_at),
        ),
        
    ),
)); ?>

<?php if (!empty($model->dimensions) && is_object($model->dimensions)): ?>
    <h3 style="font-weight: 900;"><u>Dimensions</u></h3>
    <?php $this->widget('zii.widgets.CDetailView', array(
        'data' => $model->dimensions,
        'attributes' => array(
            'length',
            'width',
            'height',
            'unit',
        ),
    )); ?>
<?php endif; ?>

<?php if (!empty($model->variants)): ?>
    <h3 style="font-weight: 900;"><u>Variants</u></h3>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>SKU</th>
                <th>Additional Price</th>
                <th>Quantity</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($model->variants as $variant): ?>
                <tr>
                    <td><?php echo CHtml::encode($variant->name); ?></td>
                    <td><?php echo CHtml::encode($variant->sku); ?></td>
                    <td><?php echo CHtml::encode($variant->additional_price); ?></td>
                    <td><?php echo CHtml::encode($variant->quantity); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>This product has no variants defined.</p>
<?php endif; ?>


<script type="text/javascript">
jQuery(function($) {
    $(document).on('click', '.delete-product-link', function(e) {
    e.preventDefault();
    if (!confirm('Are you sure you want to delete this product?')) return false;
    var url = $(this).attr('href'); // Or $(this).data('url') if using data attributes
    $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert(response.message);
                window.location.href = "<?php echo $this->createUrl('admin'); ?>";

            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('An error occurred: ' + error);
        }
    });
    return false;
});
});
</script>