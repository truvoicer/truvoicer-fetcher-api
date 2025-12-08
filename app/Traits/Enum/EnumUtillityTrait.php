<?php
namespace App\Traits\Enum;

trait EnumUtillityTrait {

    public static function implodeValues(string $separator = ', '): string
    {
        return implode($separator, array_map(fn($case) => $case->value, static::cases()));
    }

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, static::cases());
    }

}
