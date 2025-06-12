<?php

use MongoDB\BSON\Regex;

class MongoRegex implements TypeInterface
{
    /**
     * @var string
     */
    public $regex;

    /**
     * @var string
     */
    public $flags;

    /**
     * Creates a new regular expression.
     *
     * @link http://php.net/manual/en/mongoregex.construct.php
     * @param string|Regex $regex Regular expression string of the form /expr/flags
     */
    public function __construct($regex)
    {
        if ($regex instanceof Regex) {
            $this->regex = $regex->getPattern();
            $this->flags = $regex->getFlags();
            return;
        }

        if (! preg_match('#^/(.*)/([imxslu]*)$#', $regex, $matches)) {
            throw new MongoException('invalid regex', 9);
        }

        $this->regex = $matches[1];
        $this->flags = $matches[2];
    }

    /**
     * Returns a string representation of this regular expression.
     * @return string This regular expression in the form "/expr/flags".
     */
    public function __toString()
    {
        return '/' . $this->regex . '/' . $this->flags;
    }

    /**
     * Converts this MongoRegex to the new BSON Regex type
     *
     * @return Regex
     * @internal This method is not part of the ext-mongo API
     */
    public function toBSONType()
    {
        return new Regex($this->regex, $this->flags);
    }
}