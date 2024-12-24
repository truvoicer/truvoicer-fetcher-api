<?php

namespace App\Helpers\Tools;

use Carbon\Carbon;

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

    public static function parseDateStringToCarbon(string $date, ?string $format = null): ?Carbon
    {
        try {
            if ($format) {
                return Carbon::createFromFormat($format, $date);
            } else {
                return Carbon::parse($date);
            }
        } catch (\Exception $e) {
            return null;
        }
    }
    public static function parseDateString(string $date, ?string $format = null): ?Carbon
    {
        $types = [];
        if ($format) {
            $types[] = 'format';
        }
        $types[] = 'default';
        $types[] = 'replaceSlash';

        foreach ($types as $type) {
            $parsedDate = match ($type) {
                'default' => self::parseDateStringToCarbon($date),
                'replaceSlash' => self::parseDateStringToCarbon(str_replace('/', '-', $date)),
                default => null,
            };
            if ($parsedDate) {
                return $parsedDate;
            }
        }
        return null;
    }

    public static function isValidDateString(string $date): bool
    {
        return self::parseDateString($date) instanceof Carbon;
    }
}
