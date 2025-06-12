<?php

class TestCommand extends CConsoleCommand
{
    public function run($args)
    {
        echo "Hello from Yii Console Application!\n";
        print_r($args);
        $this->test();
    }

    public function test()
    {
        $record = Task::model()->find();
        
        UtilityHelpers::prettyPrint($record);
    }
}
