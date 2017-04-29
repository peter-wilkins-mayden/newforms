<?php

namespace NewForms;

class toBeOneOf
{
    public static function match($actual, $expected)
    {
        return in_array($actual, $expected);
    }

    public static function description()
    {
        return "be in expected array.";
    }
}