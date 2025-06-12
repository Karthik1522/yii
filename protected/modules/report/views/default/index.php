<?php
/* @var $this DefaultController */

$this->breadcrumbs=array(
    'Reports',
);
?>

<h1>Inventory Reports</h1>

<p>Please select a report from the list below:</p>

<ul>
<li style="margin: 10px 0; border: 1px solid #ccc; padding: 10px; border-radius: 6px;">
    <span style="margin-right: 8px;">📦</span>
    <?php echo CHtml::link('<strong>Stock Level Report</strong>', array('stockLevel'), array('escape' => false)); ?>
</li>

<li style="margin: 10px 0; border: 1px solid #ccc; padding: 10px; border-radius: 6px;">
    <span style="margin-right: 8px;">📊</span>
    <?php echo CHtml::link('<strong>Products Per Category Report</strong>', array('productsPerCategory'), array('escape' => false)); ?>
</li>

<li style="margin: 10px 0; border: 1px solid #ccc; padding: 10px; border-radius: 6px;">
    <span style="margin-right: 8px;">💰</span>
    <?php echo CHtml::link('<strong>Price Range Report</strong>', array('priceRangeReport'), array('escape' => false)); ?>
</li>

    
</ul>

<?php if(Yii::app()->user->hasFlash('error')): ?>
    <div class="flash-error">
        <?php echo Yii::app()->user->getFlash('error'); ?>
    </div>
<?php endif; ?>
<?php if(Yii::app()->user->hasFlash('success')): ?>
    <div class="flash-success">
        <?php echo Yii::app()->user->getFlash('success'); ?>
    </div>
<?php endif; ?>