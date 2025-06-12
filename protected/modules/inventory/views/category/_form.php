<?php
/* @var $this CategoryController */
/* @var $model Category */
/* @var $form CActiveForm */
/* @var $parentCategories array */
?>

<style>
    .form input[type="text"],
    .form textarea,
    .form select {
        border: 1px solid #ccc;
        padding: 8px;
        width: 100%;
        box-sizing: border-box;
        border-radius: 4px;
    }

    .form .row {
        margin-bottom: 15px;
    }

    .form label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }

    .form .errorMessage {
        color: red;
        font-size: 0.9em;
    }

    .button.primary, .btn { /* Generic button styling, .btn for bootstrap compatibility */
    padding: 8px 15px;
    color: white;
    border-radius: 3px;
    cursor: pointer;
    text-decoration: none;
    border: 1px solid transparent;
    font-size: 0.95em;
}
.button.primary { background-color: #337ab7; border-color: #2e6da4; }
.button.primary:hover { background-color: #286090; border-color: #204d74; }


</style>



<div class="form">

<?php $form = $this->beginWidget('CActiveForm', array(
    'id' => 'category-form',
    'enableClientValidation' => true, 
)); ?>
<p class="note">Fields with <span class="required">*</span> are required.</p>

<div class="row">
    <?php echo $form->labelEx($model, 'name'); ?>
    <?php echo $form->textField($model, 'name', array('size' => 60, 'maxlength' => 100)); ?>
    <?php echo $form->error($model, 'name'); ?>
</div>


<div class="row">
    <?php echo $form->labelEx($model, 'description'); ?>
    <?php echo $form->textArea($model, 'description', array('rows' => 6, 'cols' => 50)); ?>
    <?php echo $form->error($model, 'description'); ?>
</div>


<div class="row">
    <?php echo $form->labelEx($model, 'parent_id'); ?>
    <?php echo $form->dropDownList($model, 'parent_id', $parentCategories, array('prompt' => 'Select Parent (optional)')); ?>
    <?php echo $form->error($model, 'parent_id'); ?>
</div>

<div class="row">
    <?php echo $form->labelEx($model, 'slug'); ?>
    <?php echo $form->textField($model, 'slug', array('size' => 60, 'maxlength' => 120, 'placeholder' => 'Leave blank to auto-generate')); ?>
    <?php echo $form->error($model, 'slug'); ?>
</div>

<div class="row buttons">
    <?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save', array('class' => 'button primary')); ?>
</div>

<?php $this->endWidget(); ?>


</div>