<?php

namespace App\Helpers\Tools;

class DateHelpers
{
    public static function convertMonthToInteger(string $month): int|bool
    {
        $months = [
            'january' => 1,
            'february' => 2,
            'march' => 3,
            'april' => 4,
            'may' => 5,
            'june' => 6,
            'july' => 7,
            'august' => 8,
            'september' => 9,
            'october' => 10,
            'november' => 11,
            'december' => 12,
        ];
        if (!array_key_exists(strtolower($month), $months)) {
            return false;
        }
        return $months[strtolower($month)];
    }
}
