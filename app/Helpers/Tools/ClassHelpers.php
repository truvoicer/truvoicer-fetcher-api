<?php

namespace App\Helpers\Tools;

class ClassHelpers
{
    /**
     * Checks if a class directly uses a specific trait.
     *
     * @param string $className The name of the class to check.
     * @param string $traitName The name of the trait to look for.
     * @param bool $checkParents If true, checks parent classes as well.
     * @return bool
     */
    public static function usesTrait(string $className, string $traitName, bool $checkParents = false): bool
    {
        $usedTraits = class_uses($className, $checkParents);

        // class_uses returns false if the class does not exist.
        if ($usedTraits === false) {
            return false;
        }

        return in_array($traitName, $usedTraits);
    }

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
