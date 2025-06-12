<?php

use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once (__DIR__ . '/../../../../../vendor/autoload.php');
class UtilityHelpersTest extends MockeryTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSanitize_ReplacesSpecialCharacters()
    {
        $input = "hello@world!";
        $expected = "hello_world_";
        $this->assertEquals($expected, UtilityHelpers::sanitizie($input));
    }

    public function testSanitize_AllowsLettersNumbersDots()
    {
        $input = "abc123.XYZ";
        $expected = "abc123.XYZ";
        $this->assertEquals($expected, UtilityHelpers::sanitizie($input));
    }

    public function testSanitize_MultipleSpecialCharacters()
    {
        $input = "a$%^&*b";
        $expected = "a_____b"; // 5 underscores, not 6
        $this->assertEquals($expected, UtilityHelpers::sanitizie($input));
    }
    

    public function testSanitize_EmptyString()
    {
        $input = "";
        $expected = "";
        $this->assertEquals($expected, UtilityHelpers::sanitizie($input));
    }
}