<?php

namespace App\Helpers\Tools;

class ClassHelpers
{
    public static function classHasConstant(string $className, string $constant)
    {
        return defined("{$className}::{$constant}");
    }
}
