<?php
namespace App\Enums\Api;

use App\Traits\Enum\EnumUtillityTrait;

enum ApiType: string {

    use EnumUtillityTrait;

    case QUERY_STRING = 'query_string';
    case QUERY_SCHEMA = 'query_schema';
    case AI_GEMINI = 'ai_gemini';
    case AI_OPEN_AI = 'ai_gpt';
    case AI_GROK = 'ai_grok';
    case AI_DEEP_SEEK = 'ai_deepseek';
}
