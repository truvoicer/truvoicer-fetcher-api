<?php
namespace App\Enums;

use App\Traits\Enum\EnumUtillityTrait;

enum FormatOptions: string {

    use EnumUtillityTrait;

    case JSON_DECODE = 'json_decode';
    case PREG_MATCH = 'preg_match';

    public function label() {
        return match($this) {
            self::JSON_DECODE => 'Json Decode',
            self::PREG_MATCH => 'Preg Match',
        };
    }
}
