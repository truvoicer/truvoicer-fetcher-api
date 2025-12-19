<?php

namespace App\Enums;

use App\Enums\Ai\AiClient;

enum SelectDataEnum: string
{

    case AI_CLiENT = 'ai_client';

    public function label(): string
    {
        return match ($this) {
            self::AI_CLiENT => 'Ai Client',
        };
    }

    public function getEnumClass(): string
    {
        return match ($this) {
            self::AI_CLiENT => AiClient::class,
        };
    }
}
