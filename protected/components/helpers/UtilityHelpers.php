<?php

class UtilityHelpers
{
    public static function sanitizie($input)
    {
        $sanitizedInput = preg_replace('/[^a-zA-Z0-9.]/', '_' , $input);
        return $sanitizedInput;
    }

    // public static function assignGrade($score)
    // {
    //     $grade = match (true) {
    //         $score >= 90 => 'A+',
    //         $score >= 80 => 'A',
    //         $score >= 70 => 'B',
    //         $score >= 60 => 'C',
    //         $score >= 50 => 'D',
    //         $score >= 40 => 'E',
    //         default => 'F'
    //     };

    //     return $grade;
    // }

    // public static function prettyPrint($input)
    // {
    //     echo "<pre>";
    //     print_r(json_encode($input, JSON_PRETTY_PRINT));
    //     echo "</pre>";
    // }
}