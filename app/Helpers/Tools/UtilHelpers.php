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


    public static function deepFindInNestedEntity(
        array $data,
        array $conditions,
        array $childrenKeys,
        \Closure $itemToMatchHandler,
        ?string $operation = 'AND'
    ): array|null {
        $matches = [];
        foreach ($data as $item) {
            $itemsToMatch = $itemToMatchHandler($item);
            if (!is_array($itemsToMatch)) {
                continue;
            }
            foreach ($itemsToMatch as $itemToMatch) {
                $conditionsMatch = array_filter($conditions, function ($condition) use ($itemToMatch, $item, $data) {
                    $key = key($condition);
                    if (empty($itemToMatch[$key])) {
                        return false;
                    }
                    if (empty($condition[$key])) {
                        return false;
                    }
                    return $itemToMatch[$key] === $condition[$key];
                }, ARRAY_FILTER_USE_BOTH);

                switch ($operation) {
                    case 'AND':
                        if (count($conditionsMatch) === count($conditions)) {
                            $matches[] = $itemToMatch;
                        }
                        break;
                    case 'OR':
                        if (count($conditionsMatch) > 0) {
                            $matches[] = $itemToMatch;
                        }
                        break;
                }
            }
            if ($operation === 'AND' && count($matches) > 0) {
                return $matches;
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
        return $matches;
    }
}
