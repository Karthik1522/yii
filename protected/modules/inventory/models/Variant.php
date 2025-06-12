<?php

class Variant extends EMongoEmbeddedDocument
{
    public $name; 
    public $sku;
    public $additional_price = 0; 
    public $quantity; 

  
    public function rules()
    {
        return array(
            array('name, sku, quantity', 'required'),
            array('sku', 'length', 'max' => 100),
            array('additional_price', 'numerical'),
            array('quantity', 'numerical', 'integerOnly' => true, 'min' => 0),
            array('name', 'length', 'max' => 255),
            array('name, sku, additional_price, quantity', 'safe'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'name' => 'Variant Name',
            'sku' => 'Variant SKU',
            'additional_price' => 'Additional Price',
            'quantity' => 'Variant Quantity',
        );
    }

    public function getFinalPrice($basePrice)
    {
        return $basePrice + $this->additional_price;
    }
}