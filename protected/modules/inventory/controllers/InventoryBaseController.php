<?php

class InventoryBaseController extends Controller
{
    public $layout = 'inventory.views.layouts.main';

    public function init()
    {
        parent::init();
    }

    public function filters()
    {
        return [
            'accessControl'
        ];
    }

}
