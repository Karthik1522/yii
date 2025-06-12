<?php

class ReportModule extends CWebModule
{
    public $defaultController = "default";
    public function init()
    {
        $this->setImport([
            'report.models.*',
            'report.components.*',
            'application.modules.inventory.models.*',

        ]);
    }

    public function beforeControllerAction($controller, $action)
    {
        if (parent::beforeControllerAction($controller, $action)) {
        

            return true;
        }
        return false;
    }

}