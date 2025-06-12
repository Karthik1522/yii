<?php

use MongoDB\BSON\Timestamp;
class MongoTimestamp implements TypeInterface
{
    private static $globalInc = 0;

    public $sec;

    public $inc;

    /**
     * Creates a new timestamp. If no parameters are given, the current time is used
     * and the increment is automatically provided. The increment is set to 0 when the
     * module is loaded and is incremented every time this constructor is called
     * (without the $inc parameter passed in).
     *
     * @param int $sec [optional] Number of seconds since January 1st, 1970
     * @param int $inc [optional] Increment
     */
    public function __construct($sec = 0, $inc = 0)
    {
        if ($sec instanceof Timestamp) {
            // Only way is to convert is from string: [<inc>:<sec>]
            $parts = explode(':', substr((string) $sec, 1, -1));
            $this->sec = (int) $parts[1];
            $this->inc = (int) $parts[0];

            return;
        }

        if (func_num_args() == 0) {
            $sec = time();
        }

        if (func_num_args() <= 1) {
            $inc = static::$globalInc;
            static::$globalInc++;
        }

        $this->sec = (int) $sec;
        $this->inc = (int) $inc;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->sec;
    }

    /**
     * Converts this MongoTimestamp to the new BSON Timestamp type
     *
     * @return Timestamp
     * @internal This method is not part of the ext-mongo API
     */
    public function toBSONType()
    {
        return new Timestamp($this->inc, $this->sec);
    }
}
