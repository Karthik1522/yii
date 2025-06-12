<?php
/* @var $this ProductController */
/* @var $model Product */
/* @var $form CActiveForm */
?>

<div class="wide form">

<?php $form=$this->beginWidget('CActiveForm', array(
    'action'=>Yii::app()->createUrl($this->route), // Submits to the current controller/action (admin)
    'method'=>'get', // Important for CGridView filtering
)); ?>

    <div class="row">
        <?php echo $form->label($model,'_id', array('label'=>'Product ID')); ?>
        <?php echo $form->textField($model,'_id',array('size'=>24,'maxlength'=>24)); ?>
    </div>

    <div class="row">
        <?php echo $form->label($model,'name'); ?>
        <?php echo $form->textField($model,'name',array('size'=>60,'maxlength'=>255)); ?>
    </div>

    <div class="row">
        <?php echo $form->label($model,'sku'); ?>
        <?php echo $form->textField($model,'sku',array('size'=>50,'maxlength'=>100)); ?>
    </div>

    <div class="row">
        <?php echo $form->label($model,'description'); ?>
        <?php echo $form->textField($model,'description',array('size'=>60,'maxlength'=>255)); ?>
        <p class="hint">Searches for parts of the description.</p>
    </div>

    <div class="row">
        <?php echo $form->label($model,'category_id'); ?>
        <?php echo $form->dropDownList($model, 'category_id',
           $categories,
            array('prompt'=>'All Categories')
        ); ?>
    </div>

    <div class="row">
        <?php echo $form->label($model,'quantity'); ?>
        <?php echo $form->textField($model,'quantity'); ?>
        <p class="hint">Use operators like <=10 or >50.</p>
    </div>

    <div class="row">
        <?php echo $form->label($model,'price'); ?>
        <?php echo $form->textField($model,'price'); ?>
        <p class="hint">Use operators like <=19.99 or >100.</p>
    </div>

    <div class="row">
        <?php echo $form->label($model,'tags_input', array('label'=>'Tags')); ?>
        <?php echo $form->textField($model,'tags_input',array('size'=>60,'maxlength'=>500)); ?>
        <p class="hint">Comma-separated. Searches for products containing ALL specified tags.</p>
    </div> 


    <div class="row buttons">
        <?php echo CHtml::submitButton('Search', array('class'=>'btn btn-primary')); ?>
        <?php echo CHtml::button('Reset', array('class'=>'btn btn-default', 'onclick'=>"$('#product-grid').yiiGridView('update', {data: $('#product-form-search').serialize() + '&Product[_id]=&Product[name]=&Product[sku]=&Product[description]=&Product[category_id]=&Product[quantity]=&Product[price]=&Product[tags_input]=' }); $('.search-form form input[type=text], .search-form form select').val(''); return false;")); ?>
        <?php // A simpler reset:
        // echo CHtml::resetButton('Reset', array('class'=>'btn btn-default'));
        // Or, if you want to clear filters and re-submit to show all:
        // echo CHtml::link('Clear Filters', array($this->route), array('class'=>'btn btn-warning'));
        ?>
    </div>

<?php $this->endWidget(); ?>

</div><!-- wide form -->