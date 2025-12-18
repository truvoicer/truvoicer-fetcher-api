<?php

namespace App\Enums\Sr;

use App\Traits\Enum\EnumUtillityTrait;

enum PaginationType: string
{
    use EnumUtillityTrait;

    case PAGE = 'page';
    case OFFSET = 'offset';

    /**
     * Get display name for the pagination type
     */
    public function label(): string
    {
        return match($this) {
            self::PAGE => 'Page-based Pagination',
            self::OFFSET => 'Offset-based Pagination',
        };
    }

}
