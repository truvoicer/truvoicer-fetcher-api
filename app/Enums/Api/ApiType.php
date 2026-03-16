<?php

namespace App\Enums\Api;

use Truvoicer\TfDbReadCore\Traits\Enum\EnumUtilityTrait;

enum ApiType: string
{
    use EnumUtilityTrait;

    case DEFAULT = 'default';
    case AI_GEMINI = 'ai_gemini';
    case AI_OPEN_AI = 'ai_gpt';
    case AI_GROK = 'ai_grok';
    case AI_DEEP_SEEK = 'ai_deepseek';

    public function label(): string
    {
        return match ($this) {
            self::DEFAULT => 'Default',
            self::AI_GEMINI => 'Ai Gemini',
            self::AI_OPEN_AI => 'Ai Open ai',
            self::AI_GROK => 'Ai Grok',
            self::AI_DEEP_SEEK => 'Ai Deep seek',
        };
    }
}
