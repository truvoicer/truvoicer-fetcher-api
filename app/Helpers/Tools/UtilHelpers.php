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
}
