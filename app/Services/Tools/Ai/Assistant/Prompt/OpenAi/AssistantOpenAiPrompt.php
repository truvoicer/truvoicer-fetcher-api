<?php

namespace App\Services\Tools\Ai\Assistant\Prompt\OpenAi;

use App\Enums\Ai\AiClient;
use App\Services\Tools\Ai\Assistant\Prompt\AiAssistantPrompt;

class AssistantOpenAiPrompt extends AiAssistantPrompt
{

    protected ?AiClient $aiClient = AiClient::OPEN_AI;

}
