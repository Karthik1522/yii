<?php
class Dimensions extends EMongoEmbeddedDocument // Or CModel if simpler
{
    public $length;
    public $width;
    public $height;
    public $unit = 'cm'; // Default unit

    public function rules()
    {
        return array(
            array('length, width, height', 'required'),
            array('length, width, height', 'numerical', 'min' => 0, 'integerOnly' => false),
            array('length, width, height', 'filter', 'filter' => 'intval'), // Or required based on your needs
            array('unit', 'in', 'range' => array('cm', 'mm', 'in', 'ft')),
            array('length, width, height, unit', 'safe'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'length' => 'Length',
            'width' => 'Width',
            'height' => 'Height',
            'unit' => 'Unit',
        );
    }
}