<?php
namespace App\Enums\Api;

use App\Traits\Enum\EnumUtillityTrait;

enum ApiMethod: string {

    use EnumUtillityTrait;

    case GET = 'get';
    case POST = 'post';
}
