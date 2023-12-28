<?php

namespace App\Helpers\Tools;

class ClassHelpers
{
    public static function classHasConstant(string $className, string $constant)
    {
        return defined("{$className}::{$constant}");
    }
    public static function getClassConstantValue(string $className, string $constant)
    {
        if (!self::classHasConstant($className, $constant)) {
            return false;
        }
        return constant("{$className}::{$constant}");
    }
}
