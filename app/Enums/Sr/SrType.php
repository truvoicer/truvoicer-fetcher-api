<?php
namespace App\Enums\Sr;

use App\Traits\Enum\EnumUtillityTrait;

enum SrType: string {

    use EnumUtillityTrait;

    case LIST = 'list';
    case SINGLE = 'single';
    case DETAIL = 'detail';
    case MIXED = 'mixed';
}
