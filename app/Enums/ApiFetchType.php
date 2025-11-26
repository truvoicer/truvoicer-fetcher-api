<?php

namespace App\Enums;

enum ApiFetchType: string
{
    case DATABASE = 'database';
    case API_DIRECT = 'api_direct';

    public function label(): string
    {
        return match ($this) {
            self::DATABASE => 'Database',
            self::API_DIRECT => 'API Direct',
        };
    }
}
