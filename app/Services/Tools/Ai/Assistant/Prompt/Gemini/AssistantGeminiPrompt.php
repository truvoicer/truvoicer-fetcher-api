<?php

namespace App\Services\Tools\Ai\Assistant\Prompt\Gemini;

use App\Enums\Ai\AiClient;
use App\Services\Tools\Ai\Assistant\Prompt\AiAssistantPrompt;

class AssistantGeminiPrompt extends AiAssistantPrompt
{
    protected ?AiClient $aiClient = AiClient::GEMINI;
}
