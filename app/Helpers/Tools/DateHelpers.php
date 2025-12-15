<?php

namespace App\Helpers\Tools;

use Illuminate\Support\Carbon;

class DateHelpers
{
    private static array $months = [
        'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
        'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
        'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
    ];

    public static function convertMonthToInteger(string $month): int|bool
    {
        return self::$months[strtolower($month)] ?? false;
    }

    public static function parseDateStringToCarbon(string $date, ?string $format = null): ?Carbon
    {
        if ($format) {
            // Use createFromFormat without exceptions for better performance
            $dateObj = Carbon::createFromFormat($format, $date);
            return $dateObj !== false ? $dateObj : null;
        }

        // Fast validation before parsing
        if (!self::looksLikeDate($date)) {
            return null;
        }

        // Use strtotime first for better performance
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return null;
        }

        return Carbon::createFromTimestamp($timestamp);
    }

    public static function parseDateString(string $date, ?string $format = null): ?Carbon
    {
        // Try format first if provided
        if ($format) {
            $parsed = Carbon::createFromFormat($format, $date);
            if ($parsed !== false) {
                return $parsed;
            }
        }

        // Fast pre-check
        if (!self::looksLikeDate($date)) {
            return null;
        }

        // Try standard parsing
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return Carbon::createFromTimestamp($timestamp);
        }

        // Try with slash replacement as last resort
        $timestamp = strtotime(str_replace('/', '-', $date));
        if ($timestamp !== false) {
            return Carbon::createFromTimestamp($timestamp);
        }

        return null;
    }

    public static function isValidDateString(string $date): bool
    {
        // Fast pre-filter to avoid expensive parsing
        if (!self::looksLikeDate($date)) {
            return false;
        }

        // Quick check with strtotime first (much faster than Carbon::parse)
        return strtotime($date) !== false || strtotime(str_replace('/', '-', $date)) !== false;
    }

    private static function looksLikeDate(string $date): bool
    {
        // Quick heuristic checks to filter out obvious non-dates
        $length = strlen($date);

        // Dates are typically between 5 and 30 characters
        if ($length < 5 || $length > 30) {
            return false;
        }

        // Check for common date separators or patterns
        if (!preg_match('/[-\/\.,\s]/', $date)) {
            return false;
        }

        // Check if it contains numbers (all dates do)
        if (!preg_match('/\d/', $date)) {
            return false;
        }

        return true;
    }
}
