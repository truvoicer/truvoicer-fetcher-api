<?php
namespace App\Enums\Api;

use App\Traits\Enum\EnumUtillityTrait;

enum ApiResponseFormat: string {

    use EnumUtillityTrait;

    case JSON = 'json';
    case XML = 'xml';
}
