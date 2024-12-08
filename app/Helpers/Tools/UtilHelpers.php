<?php
namespace App\Helpers\Tools;

use Illuminate\Database\Eloquent\Collection;

class UtilHelpers
{
    public static function labelToName(string $string, ?bool $toUpper = false, ?string $spaceReplace = '_'): string
    {
        $stringReplace = str_replace(" ", $spaceReplace, str_replace("-", $spaceReplace, trim($string)));
        if ($toUpper) {
            return strtoupper($stringReplace);
        }
        return strtolower($stringReplace);
    }

    public static function findInArrayByKey(string $key, $value, array $data, ?bool $returnIndex = false)
    {
        $findIndex = array_search($value, array_column($data, $key));
        if ($findIndex === false) {
            return false;
        }
        if ($returnIndex) {
            return $findIndex;
        }
        return $data[$findIndex];
    }
    public static function findInCollectionByKey(string $key, $value, Collection $data)
    {
        return $data->where($key, '=', $value);
    }


    public static function buildSelectSaveArray(array $data)
    {
        $data = array_map(function ($item) {
            if (!empty($item['id']) && is_numeric($item['id'])) {
                return $item['id'];
            }
            if (!empty($item['value']) && is_numeric($item['value'])) {
                return $item['value'];
            }
            return false;
        }, $data);
        return array_filter($data, function ($item) {
            return $item !== false;
        });
    }

    public static function isArrayItemNumeric(string $key, array $data): bool
    {
        return (!empty($data[$key]) && is_numeric($data[$key]));
    }


    public static function deepFindInNestedEntity(array $data, array $conditions, array $childrenKeys, \Closure $itemToMatchHandler): array|null {
        foreach ($data as $item) {
            $matchItem = $itemToMatchHandler($item);
            if (!is_array($matchItem)) {
                continue;
            }

            foreach ($matchItem as $match) {
                $matches = array_filter($conditions, function ($condition, $key) use ($match) {
                    return $match[$key] === $condition;
                }, ARRAY_FILTER_USE_BOTH);
                if (count($matches) === count($conditions)) {
                    return $match;
                }
            }

            foreach ($childrenKeys as $childrenKey) {
                if (empty($item[$childrenKey]) || !is_array($item[$childrenKey])) {
                    continue;
                }

                $value = self::deepFindInNestedEntity($item[$childrenKey], $conditions, $childrenKeys, $itemToMatchHandler);
                if (!empty($value)) {
                    return $value;
                }
            }
        }
        return null;
    }
}
