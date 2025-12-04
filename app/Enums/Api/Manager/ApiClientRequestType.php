<?php
namespace App\Enums\Api\Manager;

enum ApiClientRequestType: string {
    case DEFAULT = 'default';
    case AI_GEMINI = 'ai_gemini';
    case AI_GPT = 'ai_gpt';
    case AI_GROK = 'ai_grok';
    case AI_DEEP_SEEK = 'ai_deep_seek';
}
