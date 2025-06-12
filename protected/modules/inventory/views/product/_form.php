<?php
/* @var $this ProductController */
/* @var $model Product */
/* @var $form CActiveForm */
/* @var $categories array */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
    'id'=>'product-form',
    'enableClientValidation'=>true,
    // 'enableAjaxValidation' => false,
    'clientOptions'=>array('validateOnSubmit'=>true),
    'htmlOptions' => array('enctype' => 'multipart/form-data'),
)); ?>

    <p class="note">Fields with <span class="required">*</span> are required.</p>

    <?php echo $form->errorSummary($model); ?>

    <div class="row">
        <?php echo $form->labelEx($model,'name'); ?>
        <?php echo $form->textField($model,'name',array('maxlength'=>255)); ?>
        <?php echo $form->error($model,'name',array('class' => 'text-danger')); ?>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model,'sku'); ?>
        <?php echo $form->textField($model,'sku',array('maxlength'=>100)); ?>
        <?php echo $form->error($model,'sku',array('class' => 'text-danger')); ?>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model,'description'); ?>
        <?php echo $form->textArea($model,'description',array('rows'=>4, 'cols'=>50)); ?>
        <?php echo $form->error($model,'description',array('class' => 'text-danger')); ?>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model,'category_id'); ?>
        <?php echo $form->dropDownList($model, 'category_id', $categories, array('prompt'=>'Select Category')); ?>
        <?php echo $form->error($model,'category_id',array('class' => 'text-danger')); ?>
    </div>


    <div class="row">
        <?php echo $form->labelEx($model,'quantity'); ?>
        <?php echo $form->textField($model,'quantity'); ?>
        <?php echo $form->error($model,'quantity',array('class' => 'text-danger')); ?>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model,'price'); ?>
        <?php echo $form->textField($model,'price'); ?>
        <?php echo $form->error($model,'price',array('class' => 'text-danger')); ?>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model,'tags_input'); ?>
        <?php echo $form->textField($model,'tags_input',array('maxlength'=>500)); ?>
        <?php echo $form->error($model,'tags_input',array('class' => 'text-danger')); ?>
        <p class="hint">Separate tags with commas.</p>
    </div>

    <fieldset>
        <legend>Dimensions</legend>

        <?php if ($model->dimensions instanceof Dimensions): ?>
            <div class="row">
                <?php echo $form->labelEx($model->dimensions, 'length'); ?>
                <?php echo $form->textField($model->dimensions, 'length', array('name'=>'Product[dimensions][length]')); ?>
                <?php echo $form->error($model->dimensions, 'length',array('class' => 'text-danger errorMessage')); ?>
            </div>

            <div class="row">
                <?php echo $form->labelEx($model->dimensions, 'width'); ?>
                <?php echo $form->textField($model->dimensions, 'width', array('name'=>'Product[dimensions][width]')); ?>
                <?php echo $form->error($model->dimensions, 'width',array('class' => 'text-danger errorMessage')); ?>
            </div>

            <div class="row">
                <?php echo $form->labelEx($model->dimensions, 'height'); ?>
                <?php echo $form->textField($model->dimensions, 'height', array('name'=>'Product[dimensions][height]')); ?>
                <?php echo $form->error($model->dimensions, 'height',array('class' => 'text-danger errorMessage')); ?>
            </div>

            <div class="row">
                <?php echo $form->labelEx($model->dimensions, 'unit', array('label' => 'Unit (Dimensions)')); ?>
                <?php echo $form->textField($model->dimensions, 'unit', array('name' => 'Product[dimensions][unit]', 'placeholder' => 'e.g., cm, inch')); ?>
                <?php echo $form->error($model->dimensions, 'unit', array('class' => 'text-danger errorMessage')); ?>
            </div>
        <?php else: ?>
            <p class="hint">Dimensions not initialized. Check controller setup.</p>
        <?php endif; ?>
    </fieldset>

    
    <div class="row">
        <?php echo $form->labelEx($model, 'image_filename_upload', array('label' => ($model->isNewRecord || !$model->image_url) ? 'Upload Image' : 'Upload New Image (replaces current)')); ?>
        <?php echo $form->fileField($model, 'image_filename_upload', array('class' => 'form-control-file')); ?>
        <?php echo $form->error($model, 'image_filename_upload', array('class' => 'text-danger')); ?>
    </div>

    <?php if (!$model->isNewRecord && $model->image_url): ?>
        <div class="row">
            <label>Current Image</label>
            <div>
                <?php
                    // $imageUrl = Yii::app()->s3uploader->getFileUrl($model->image_filename);
                    echo CHtml::image($model->image_url, "Current Product Image", array("style"=>"max-width:200px; max-height:200px;"));
                ?>
            </div>
            <div class="clear-image">
                <?php echo CHtml::checkBox('clear_image_filename', false); ?>
                <?php echo CHtml::label('Remove current image', 'clear_image_filename'); ?>
            </div>
        </div>
    <?php endif; ?>

    <hr/>
    <h4>Variants</h4>
    <div id="variants-wrapper">
        <?php
        $variantsData = is_array($model->variants) ? $model->variants : array();
        
        foreach ($variantsData as $i => $variant):
        ?>
        <div class="variant-item well well-sm" data-index="<?php echo $i; ?>" style="margin-bottom:15px; padding:10px; border:1px solid #eee;">
        <h5>Variant <?php echo $i + 1; ?> <button type="button" class="btn btn-danger btn-xs remove-variant pull-right">Remove</button></h5>
        <div class="row">
                <div class="col-md-3">
                    <?php echo CHtml::label('Variant Name <span class="required">*</span>', "Product_variants_{$i}_name"); ?>
                    <?php echo CHtml::textField("Product[variants][{$i}][name]", $variant->name, array('class' => 'form-control variant-name', 'id' => "Product_variants_{$i}_name")); ?>
                    <?php // Inline error display for variants is tricky with CActiveForm default errorSummary
                          // You might need custom JS to parse errors from Product model like 'variants_0_name'
                          // For now, rely on the main errorSummary or $form->error($model, "variants_{$i}_name") if that works.
                          echo $form->error($model, "Product_variants_{$i}_name", array('class' => 'text-danger'));
                    ?>
                </div>
                <div class="col-md-3">
                    <?php echo CHtml::label('Variant SKU <span class="required">*</span>', "Product_variants_{$i}_sku"); ?>
                    <?php echo CHtml::textField("Product[variants][{$i}][sku]", $variant->sku, array('class' => 'form-control variant-sku', 'id' => "Product_variants_{$i}sku")); ?>
                    <?php echo $form->error($model, "Product_variants_{$i}_sku", array('class' => 'text-danger')); ?>
                </div>
                <div class="col-md-3">
                    <?php echo CHtml::label('Additional Price', "Product_variants_{$i}_additional_price"); ?>
                    <?php echo CHtml::textField("Product[variants][{$i}][additional_price]", $variant->additional_price, array('class' => 'form-control variant-additional_price', 'placeholder' => 'e.g., 5 or -2.5', 'id' => "Product_variants_{$i}additional_price")); ?>
                     <?php echo $form->error($model, "Product_variants_{$i}_additional_price", array('class' => 'text-danger')); ?>
                </div>
                <div class="col-md-3">
                    <?php echo CHtml::label('Variant Quantity <span class="required">*</span>', "Product_variants_{$i}_quantity"); ?>
                    <?php echo CHtml::textField("Product[variants][{$i}][quantity]", $variant->quantity, array('class' => 'form-control variant-quantity', 'id' => "Product_variants_{$i}_quantity")); ?>
                    <?php echo $form->error($model, "Product_variants_{$i}_quantity", array('class' => 'text-danger')); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="add-variant" class="btn btn-success btn-sm">Add Variant</button>

    <hr/>

    <div class="row buttons">
    <?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class'=>'button primary']); ?>
    </div>

<?php $this->endWidget(); ?>


<script type="text/javascript">
$(document).ready(function(){
    // Initialize variantIndex based on existing items rendered by PHP
    var variantIndex = $('#variants-wrapper .variant-item').length; 

    $('#add-variant').on('click', function() {
        var currentItemIndex = variantIndex; // Use this for the new item's index
        var newVariantHtml = `
        <div class="variant-item well well-sm" data-index="${currentItemIndex}" style="margin-bottom:15px; padding:10px; border:1px solid #eee;">
            <h5>Variant ${currentItemIndex + 1} <button type="button" class="btn btn-danger btn-xs remove-variant pull-right">Remove</button></h5>
            <div class="row variant-fields">
                <div class="col-md-3">
                    <label for="Product_variants_${currentItemIndex}_name">Variant Name <span class="required">*</span></label>
                    <input class="form-control variant-name" id="Product_variants_${currentItemIndex}_name" type="text" name="Product[variants][${currentItemIndex}][name]">
                </div>
                <div class="col-md-3">
                    <label for="Product_variants_${currentItemIndex}_sku">Variant SKU <span class="required">*</span></label>
                    <input class="form-control variant-sku" id="Product_variants_${currentItemIndex}_sku" type="text" name="Product[variants][${currentItemIndex}][sku]">
                </div>
                <div class="col-md-3">
                    <label for="Product_variants_${currentItemIndex}_additional_price">Additional Price</label>
                    <input class="form-control variant-additional_price" id="Product_variants_${currentItemIndex}_additional_price" placeholder="e.g., 5 or -2.5" type="text" name="Product[variants][${currentItemIndex}][additional_price]">
                </div>
                <div class="col-md-3">
                    <label for="Product_variants_${currentItemIndex}_quantity">Variant Quantity <span class="required">*</span></label>
                    <input class="form-control variant-quantity" id="Product_variants_${currentItemIndex}_quantity" type="text" name="Product[variants][${currentItemIndex}][quantity]">
                </div>
            </div>
        </div>`;
        
        $('#variants-wrapper').append(newVariantHtml);
        variantIndex++; // Increment for the *next* item
    });
    
    function renumberVariants() {
        $('#variants-wrapper .variant-item').each(function(newIdx) {
            $(this).data('index', newIdx); // Update data-index attribute
            $(this).attr('data-index', newIdx); // Also set the attribute for easier selection if needed

            // Update the H5 title
            $(this).find('h5').html(`Variant ${newIdx + 1} <button type="button" class="btn btn-danger btn-xs remove-variant pull-right">Remove</button>`);
            
            // Update labels and inputs within this variant item
            $(this).find('.variant-fields').find('label').each(function() {
                var oldFor = $(this).attr('for');
                if (oldFor) {
                    var newFor = oldFor.replace(/Product_Variants_\d+_/, `Product_Variants_${newIdx}_`);
                    $(this).attr('for', newFor);
                }
            });

            $(this).find('.variant-fields').find('input[type="text"], input[type="number"], select, textarea').each(function() {
                var oldName = $(this).attr('name');
                var oldId = $(this).attr('id');

                if (oldName) {
                    var newName = oldName.replace(/\[Variants\]\[\d+\]/, `[Variants][${newIdx}]`);
                    $(this).attr('name', newName);
                }
                if (oldId) {
                    var newId = oldId.replace(/Product_Variants_\d+_/, `Product_Variants_${newIdx}_`);
                    $(this).attr('id', newId);
                }
            });
        });
        // After renumbering, update the global variantIndex to the new count
        variantIndex = $('#variants-wrapper .variant-item').length;
    }

    $('#variants-wrapper').on('click', '.remove-variant', function() {
        $(this).closest('.variant-item').remove();
        renumberVariants();
    });
});
</script>

</div><!-- form -->

<style>
/* General Form Styles */
.form .row { margin-bottom: 10px; clear: both; }
.form .row label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9em; }
.form .row input[type="text"],
.form .row input[type="number"],
.form .row textarea,
.form .row select {
    width: 90%; /* Consider using a class for sizing or more specific selectors */
    padding: 6px 8px;
    border: 1px solid #ccc;
    border-radius: 3px;
    box-sizing: border-box; /* Important for width and padding */
    max-width: 400px; /* Example max-width */
}
.form .row textarea { width: 95%; max-width: 500px; }
.form .row .hint { font-size: 0.85em; color: #777; margin-top: 2px; }
.form .row .errorMessage, .text-danger.errorMessage { /* Combined for consistency */
    color: red;
    font-size: 0.85em;
    margin-top: 3px;
    display: block; /* Ensure it takes its own line */
}

/* Error Summary */
.errorSummary {
    border: 1px solid #c00;
    color: #c00;
    padding: 10px;
    margin-bottom: 15px;
    background-color: #fdd;
    border-radius: 3px;
}
.errorSummary p { margin:0; padding:0; font-weight: bold; }
.errorSummary ul { margin: 5px 0 0 20px; padding:0; list-style-type: disc; }

/* Fieldset */
.form fieldset {
    margin-bottom: 20px;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 4px;
}
.form legend {
    font-weight: bold;
    padding: 0 10px;
    margin-bottom: 10px;
    width: auto; /* Reset Bootstrap's width:100% if it interferes */
    border-bottom: none; /* Reset Bootstrap's border if present */
    font-size: 1.1em;
}

/* Buttons */
.form .buttons { margin-top: 20px; }
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

.btn-success { background-color: #5cb85c; border-color: #4cae4c;}
.btn-success:hover { background-color: #449d44; border-color: #398439;}
.btn-danger { background-color: #d9534f; border-color: #d43f3a;}
.btn-danger:hover { background-color: #c9302c; border-color: #ac2925;}
.btn-xs { padding: 1px 5px; font-size: 12px; line-height: 1.5; border-radius: 3px; }
.pull-right { float: right; }


/* Image specific */
.clear-image { margin-top: 5px; font-size: 0.9em; }
.clear-image label { font-weight: normal; margin-left: 5px; }

/* Variants specific */
.variant-item { background-color: #f9f9f9; }
.variant-item h5 { margin-top: 0; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #e0e0e0;}
.variant-item .col-md-3 { /* Basic grid-like behavior. For true Bootstrap, include Bootstrap CSS */
    width: 23%; /* (100/4) - margin */
    float: left;
    margin-right: 2%;
    margin-bottom: 10px; /* Space for smaller screens */
}
.variant-item .col-md-3:last-child { margin-right: 0; }
.variant-item .row.variant-fields::after { content: ""; display: table; clear: both; } /* Clearfix */

/* Responsive adjustments (simple example) */
@media (max-width: 768px) {
    .variant-item .col-md-3 {
        width: 48%; /* Two columns */
    }
    .variant-item .col-md-3:nth-child(2n) { margin-right: 0; }
    .variant-item .col-md-3:nth-child(2n+1) { clear: left; }
}
@media (max-width: 480px) {
    .variant-item .col-md-3 {
        width: 100%; /* Full width */
        float: none;
        margin-right: 0;
    }
}

.text-danger { color: #a94442; /* Bootstrap's default danger color */ }

</style>