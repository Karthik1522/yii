<?php

class MongoDbLogRoute extends CLogRoute
{
    /**
     * Processes log messages and stores them in MongoDB.
     * @param array $logs
     */
    protected function processLogs($logs)
    {
        foreach ($logs as $log) {

            $entry = new MongoLog();
            $entry->level = $log[1];
            $entry->category = $log[2];
            $entry->logtime = new MongoDate((int) $log[3]);
            $entry->message = $log[0];
            $entry->save();
        }
    }

}
