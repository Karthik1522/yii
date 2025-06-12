<?php

class InventoryModule extends CWebModule
{

    public $defaultController = "dashboard";
    public function init()
    {
        $this->setImport(array(
            'inventory.models.*',
            'inventory.components.*',
            'inventory.controllers.*',
        ));

    }

    public function beforeControllerAction($controller, $action)
    {
        if (parent::beforeControllerAction($controller, $action)) {
            
            return true;
        }
        return false;
    }

}
