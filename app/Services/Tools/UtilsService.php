<?php
namespace App\Services\Tools;

class UtilsService
{
    public static function labelToName(string $string, ?bool $toUpper = false, ?string $spaceReplace = '_'): string
    {
        $stringReplace = str_replace(" ", $spaceReplace, str_replace("-", $spaceReplace, trim($string)));
        if ($toUpper) {
            return strtoupper($stringReplace);
        }
        return strtolower($stringReplace);
    }


}
