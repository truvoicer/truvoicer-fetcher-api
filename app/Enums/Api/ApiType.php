<?php
namespace App\Enums\Api;

use Truvoicer\TfDbReadCore\Traits\Enum\EnumUtillityTrait;

enum ApiType: string {

    use EnumUtillityTrait;

    case DEFAULT = 'default';
    case AI_GEMINI = 'ai_gemini';
    case AI_OPEN_AI = 'ai_gpt';
    case AI_GROK = 'ai_grok';
    case AI_DEEP_SEEK = 'ai_deepseek';
}
