<?php

namespace App\Services\Tools\Ai\Assistant\Prompt\Grok;

use App\Enums\Ai\AiClient;
use App\Services\Tools\Ai\Assistant\Prompt\AiAssistantPrompt;
use InvalidArgumentException;

class AssistantGrokPrompt extends AiAssistantPrompt
{

    protected ?AiClient $aiClient = AiClient::GROK;

}
