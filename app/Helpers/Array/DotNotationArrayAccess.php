<?php
namespace App\Helpers\Array;

class DotNotationArrayAccess
{
    /**
     * Get a value from an array using dot notation
     *
     * @param array $array The array to search in
     * @param string $key The dot notation key (e.g., "user.profile.email", "results.2.name")
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The value or default if not found
     */
    public static function get(array $array, string $key, $default = null)
    {
        // If the key exists directly, return it
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        // Split by dots while handling escaped dots
        $segments = self::parseKey($key);

        // Traverse the array
        $result = $array;
        foreach ($segments as $segment) {
            // Check if segment is trying to access an array index
            if (is_array($result) && array_key_exists($segment, $result)) {
                $result = $result[$segment];
            } else {
                return $default;
            }
        }

        return $result;
    }

    /**
     * Set a value in an array using dot notation
     *
     * @param array &$array The array to modify
     * @param string $key The dot notation key
     * @param mixed $value The value to set
     * @return void
     */
    public static function set(array &$array, string $key, $value): void
    {
        $segments = self::parseKey($key);

        // Traverse the array and create nested arrays if needed
        $current = &$array;
        foreach ($segments as $segment) {
            // If this segment doesn't exist or isn't an array, create it
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                $current[$segment] = [];
            }

            // Move deeper
            $current = &$current[$segment];
        }

        // Set the final value
        $current = $value;
    }

    /**
     * Check if a key exists in an array using dot notation
     *
     * @param array $array The array to check
     * @param string $key The dot notation key
     * @return bool True if key exists
     */
    public static function has(array $array, string $key): bool
    {
        $segments = self::parseKey($key);

        $current = $array;
        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }

        return true;
    }

    /**
     * Parse a dot notation key into segments, handling escaped dots
     *
     * @param string $key The dot notation key
     * @return array Array of key segments
     */
    private static function parseKey(string $key): array
    {
        $segments = [];
        $currentSegment = '';
        $escaping = false;

        for ($i = 0; $i < strlen($key); $i++) {
            $char = $key[$i];

            if ($escaping) {
                $currentSegment .= $char;
                $escaping = false;
            } elseif ($char === '\\') {
                $escaping = true;
            } elseif ($char === '.') {
                $segments[] = self::parseSegment($currentSegment);
                $currentSegment = '';
            } else {
                $currentSegment .= $char;
            }
        }

        // Add the last segment
        if ($currentSegment !== '') {
            $segments[] = self::parseSegment($currentSegment);
        }

        return $segments;
    }

    /**
     * Parse an individual segment, converting numeric strings to integers
     *
     * @param string $segment The segment to parse
     * @return mixed The parsed segment (int if numeric, string otherwise)
     */
    private static function parseSegment(string $segment)
    {
        // If it's a numeric string, convert to integer for array indexing
        if (is_numeric($segment)) {
            return (int) $segment;
        }

        return $segment;
    }
}
