<?php

namespace App\Enums\Ai;

use App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Ai\DeepSeek\DeepSeekPopulatePrompt;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Ai\Gemini\GeminiPopulatePrompt;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Ai\Grok\GrokPopulatePrompt;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Ai\OpenAi\OpenAiPopulatePrompt;
use Truvoicer\TfDbReadCore\Enums\Api\ApiType;
use Truvoicer\TfDbReadCore\Traits\Enum\EnumUtillityTrait;

enum AiClient: string
{

    use EnumUtillityTrait;

    case GEMINI = 'gemini';
    case DEEP_SEEK = 'deep_seek';
    case OPEN_AI = 'open_ai';
    case GROK = 'grok';

    public function label()
    {
        return match ($this) {
            self::GEMINI => 'Gemini',
            self::DEEP_SEEK => 'Deep Seek',
            self::OPEN_AI => 'Open AI',
            self::GROK => 'Grok',
        };
    }

    public function apiType(): ApiType
    {
        return match ($this) {
            self::GEMINI => ApiType::AI_GEMINI,
            self::DEEP_SEEK => ApiType::AI_DEEP_SEEK,
            self::OPEN_AI => ApiType::AI_OPEN_AI,
            self::GROK => ApiType::AI_GROK,
        };
    }
    public function populatePrompt(array $apiResponse, array $serviceResponseKeys): string
    {
        return match ($this) {
            self::GEMINI => app(GeminiPopulatePrompt::class)->prompt($apiResponse, $serviceResponseKeys),
            self::DEEP_SEEK => app(DeepSeekPopulatePrompt::class)->prompt($apiResponse, $serviceResponseKeys),
            self::OPEN_AI => app(OpenAiPopulatePrompt::class)->prompt($apiResponse, $serviceResponseKeys),
            self::GROK => app(GrokPopulatePrompt::class)->prompt($apiResponse, $serviceResponseKeys),
        };
    }

    public function apiKey()
    {
        return match ($this) {
            self::GEMINI => config('services.ai_client.gemini.api_key'),
            self::DEEP_SEEK => config('services.ai_client.deep_seek.api_key'),
            self::OPEN_AI => config('services.ai_client.open_ai.api_key'),
            self::GROK => config('services.ai_client.grok.api_key'),
        };
    }
}
