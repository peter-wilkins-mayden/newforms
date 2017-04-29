<?php


namespace NewForms;


class toContainArray
{
    public static function match($actual, $expected)
    {
        $match = false;
        foreach ($actual as $anArray) {
            if($anArray == $expected){
                $match = true;
            }
        }
        return $match;
    }

    public static function description()
    {
        return "to contain expected array.";
    }
}