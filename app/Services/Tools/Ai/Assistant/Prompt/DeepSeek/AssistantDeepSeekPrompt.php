<?php

namespace App\Services\Tools\Ai\Assistant\Prompt\DeepSeek;

use App\Enums\Ai\AiClient;
use App\Services\Tools\Ai\Assistant\Prompt\AiAssistantPrompt;
use InvalidArgumentException;

class AssistantDeepSeekPrompt extends AiAssistantPrompt
{

    protected ?AiClient $aiClient = AiClient::DEEP_SEEK;

}
