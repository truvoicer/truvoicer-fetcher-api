<?php

namespace App\Services\Ai\Grok;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GrokClient
{
    /**
     * The Grok API key.
     * @var string
     */
    protected string $apiKey;

    /**
     * The Grok API endpoint.
     * @var string
     */
    protected string $apiEndpoint = 'https://api.x.ai/v1/chat/completions';

    /**
     * Default model to use.
     * @var string
     */
    protected string $model = 'grok-beta';

    /**
     * Operation timeout in seconds.
     * @var int
     */
    protected int $timeout = 120;

    /**
     * Maximum tokens in response.
     * @var int|null
     */
    protected ?int $maxTokens = null;

    /**
     * Temperature for creativity (0.0 to 2.0).
     * @var float
     */
    protected float $temperature = 0.7;

    /**
     * Top-p sampling (0.0 to 1.0).
     * @var float|null
     */
    protected ?float $topP = null;

    /**
     * System prompt/instruction.
     * @var string
     */
    protected string $systemPrompt = 'You are Grok, an AI assistant with a sense of humor and no filters. Be helpful, witty, and direct.';

    /**
     * User prompt/message.
     * @var string
     */
    protected string $userPrompt;

    /**
     * Conversation history/messages.
     * @var array
     */
    protected array $messages = [];

    /**
     * Enable streaming response.
     * @var bool
     */
    protected bool $stream = false;

    /**
     * Grok-specific: Enable web search.
     * @var bool
     */
    protected bool $webSearch = false;

    /**
     * Grok-specific: Enable reasoning.
     * @var bool
     */
    protected bool $reasoning = false;

    /**
     * Grok-specific: Conversation ID for context.
     * @var string|null
     */
    protected ?string $conversationId = null;

    /**
     * Get the current operation timeout value.
     * @return int The timeout in seconds.
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Set the operation timeout value.
     * @param int $timeout The new timeout value in seconds.
     * @return $this Allows for method chaining (fluent interface).
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Set the API key.
     * @param string $apiKey
     * @return $this
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Get the API key.
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Set the API endpoint.
     * @param string $apiEndpoint
     * @return $this
     */
    public function setApiEndpoint(string $apiEndpoint): self
    {
        $this->apiEndpoint = $apiEndpoint;
        return $this;
    }

    /**
     * Get the API endpoint.
     * @return string
     */
    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    /**
     * Set the model to use.
     * @param string $model
     * @return $this
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Get the model.
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Set maximum tokens for response.
     * @param int|null $maxTokens
     * @return $this
     */
    public function setMaxTokens(?int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;
        return $this;
    }

    /**
     * Get maximum tokens.
     * @return int|null
     */
    public function getMaxTokens(): ?int
    {
        return $this->maxTokens;
    }

    /**
     * Set temperature (0.0 to 2.0).
     * @param float $temperature
     * @return $this
     */
    public function setTemperature(float $temperature): self
    {
        $this->temperature = max(0.0, min(2.0, $temperature));
        return $this;
    }

    /**
     * Get temperature.
     * @return float
     */
    public function getTemperature(): float
    {
        return $this->temperature;
    }

    /**
     * Set top-p sampling (0.0 to 1.0).
     * @param float|null $topP
     * @return $this
     */
    public function setTopP(?float $topP): self
    {
        if ($topP !== null) {
            $this->topP = max(0.0, min(1.0, $topP));
        }
        return $this;
    }

    /**
     * Get top-p sampling.
     * @return float|null
     */
    public function getTopP(): ?float
    {
        return $this->topP;
    }

    /**
     * Set system prompt.
     * @param string $systemPrompt
     * @return $this
     */
    public function setSystemPrompt(string $systemPrompt): self
    {
        $this->systemPrompt = $systemPrompt;
        return $this;
    }

    /**
     * Get system prompt.
     * @return string
     */
    public function getSystemPrompt(): string
    {
        return $this->systemPrompt;
    }

    /**
     * Set user prompt.
     * @param string $prompt
     * @return $this
     */
    public function setPrompt(string $prompt): self
    {
        $this->userPrompt = $prompt;
        return $this;
    }

    /**
     * Get user prompt.
     * @return string
     */
    public function getPrompt(): string
    {
        return $this->userPrompt;
    }

    /**
     * Add a message to conversation history.
     * @param string $role (system, user, assistant)
     * @param string $content
     * @return $this
     */
    public function addMessage(string $role, string $content): self
    {
        $this->messages[] = [
            'role' => $role,
            'content' => $content
        ];
        return $this;
    }

    /**
     * Get conversation messages.
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Clear conversation history.
     * @return $this
     */
    public function clearMessages(): self
    {
        $this->messages = [];
        return $this;
    }

    /**
     * Enable or disable streaming.
     * @param bool $stream
     * @return $this
     */
    public function setStream(bool $stream): self
    {
        $this->stream = $stream;
        return $this;
    }

    /**
     * Get streaming status.
     * @return bool
     */
    public function getStream(): bool
    {
        return $this->stream;
    }

    /**
     * Enable or disable web search (Grok-specific).
     * @param bool $webSearch
     * @return $this
     */
    public function setWebSearch(bool $webSearch): self
    {
        $this->webSearch = $webSearch;
        return $this;
    }

    /**
     * Get web search status.
     * @return bool
     */
    public function getWebSearch(): bool
    {
        return $this->webSearch;
    }

    /**
     * Enable or disable reasoning (Grok-specific).
     * @param bool $reasoning
     * @return $this
     */
    public function setReasoning(bool $reasoning): self
    {
        $this->reasoning = $reasoning;
        return $this;
    }

    /**
     * Get reasoning status.
     * @return bool
     */
    public function getReasoning(): bool
    {
        return $this->reasoning;
    }

    /**
     * Set conversation ID (Grok-specific).
     * @param string|null $conversationId
     * @return $this
     */
    public function setConversationId(?string $conversationId): self
    {
        $this->conversationId = $conversationId;
        return $this;
    }

    /**
     * Get conversation ID.
     * @return string|null
     */
    public function getConversationId(): ?string
    {
        return $this->conversationId;
    }

    /**
     * Make API request to Grok.
     * @return Response
     */
    public function makeRequest(): Response
    {
        // Build messages array
        $messages = $this->messages;

        // Add system prompt if not already in messages
        $hasSystemMessage = false;
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $hasSystemMessage = true;
                break;
            }
        }

        if (!$hasSystemMessage && !empty($this->systemPrompt)) {
            array_unshift($messages, [
                'role' => 'system',
                'content' => $this->systemPrompt
            ]);
        }

        // Add current user prompt if not already in messages
        if (!empty($this->userPrompt)) {
            $messages[] = [
                'role' => 'user',
                'content' => $this->userPrompt
            ];
        }

        // Prepare request data
        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'stream' => $this->stream,
        ];

        // Add optional parameters
        if ($this->maxTokens !== null) {
            $data['max_tokens'] = $this->maxTokens;
        }

        if ($this->topP !== null) {
            $data['top_p'] = $this->topP;
        }

        // Add Grok-specific parameters
        if ($this->webSearch) {
            $data['web_search'] = $this->webSearch;
        }

        if ($this->reasoning) {
            $data['reasoning'] = $this->reasoning;
        }

        if ($this->conversationId !== null) {
            $data['conversation_id'] = $this->conversationId;
        }

        // Make the request
        return Http::withOptions([
            'timeout' => $this->timeout,
            'stream' => $this->stream, // Enable HTTP streaming if requested
        ])
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => $this->stream ? 'text/event-stream' : 'application/json',
        ])
        ->post($this->apiEndpoint, $data);
    }

    /**
     * Extracts and formats the response from Grok API.
     *
     * @param Response $response The Response object.
     * @return array
     * @throws Exception
     */
    public function formatApiResponse(Response $response): array
    {
        if ($response->failed()) {
             Log::warning('Grok API returned an empty response.', ['response' => $response->json()]);
             throw $response->toException();
        }

        $responseBody = $response->json();
        // Handle streaming response (if used)
        if ($this->stream) {
            return $this->formatStreamingResponse($responseBody);
        }

        // Handle regular response
        if (!isset($responseBody['choices'][0]['message']['content'])) {
            Log::warning('Grok API returned an empty response.', ['response' => $responseBody]);

            // Check for errors
            if (isset($responseBody['error'])) {
                throw new Exception('Grok API error: ' . $responseBody['error']['message']);
            }

            throw new Exception('The response could not be read or parsed.');
        }

        $rawText = $responseBody['choices'][0]['message']['content'];

        // Extract any metadata
        $metadata = [
            'model' => $responseBody['model'] ?? null,
            'usage' => $responseBody['usage'] ?? null,
            'created' => $responseBody['created'] ?? null,
            'id' => $responseBody['id'] ?? null,
        ];

        // Clean the response if it's JSON wrapped in code blocks
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $rawText, $matches)) {
            $jsonString = $matches[1];
        } else {
            $jsonString = $rawText;
        }

        // Try to parse as JSON, if it's valid JSON return it, otherwise return as text
        $decoded = json_decode($jsonString, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return [
                'content' => $decoded,
                'raw_text' => $rawText,
                'metadata' => $metadata,
                'is_json' => true,
            ];
        }

        // If it's not JSON, return the raw text
        return [
            'content' => $rawText,
            'raw_text' => $rawText,
            'metadata' => $metadata,
            'is_json' => false,
        ];
    }

    /**
     * Format streaming response (if streaming is enabled).
     *
     * @param array $responseBody
     * @return array
     */
    private function formatStreamingResponse(array $responseBody): array
    {
        // This would handle Server-Sent Events (SSE) parsing
        // For simplicity, returning the raw response
        return [
            'content' => $responseBody,
            'is_streaming' => true,
            'raw_response' => $responseBody,
        ];
    }

    /**
     * Convenience method for single message conversation.
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function ask(string $prompt, array $options = []): array
    {
        $this->setPrompt($prompt);

        // Apply options
        if (isset($options['temperature'])) {
            $this->setTemperature($options['temperature']);
        }

        if (isset($options['max_tokens'])) {
            $this->setMaxTokens($options['max_tokens']);
        }

        if (isset($options['system_prompt'])) {
            $this->setSystemPrompt($options['system_prompt']);
        }

        if (isset($options['web_search'])) {
            $this->setWebSearch($options['web_search']);
        }

        if (isset($options['reasoning'])) {
            $this->setReasoning($options['reasoning']);
        }

        if (isset($options['conversation_id'])) {
            $this->setConversationId($options['conversation_id']);
        }

        if (isset($options['stream'])) {
            $this->setStream($options['stream']);
        }

        $response = $this->makeRequest();

        if (!$response->successful()) {
            Log::error('Grok API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('Grok API request failed: ' . $response->body());
        }

        return $this->formatApiResponse($response->json());
    }

    /**
     * Multi-turn conversation helper.
     *
     * @param array $conversation Array of messages with 'role' and 'content'
     * @param array $options Additional options
     * @return array
     */
    public function converse(array $conversation, array $options = []): array
    {
        $this->messages = $conversation;

        // Apply options (same as ask method)
        foreach ($options as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }

        $response = $this->makeRequest();

        if (!$response->successful()) {
            throw new Exception('Grok API request failed: ' . $response->body());
        }

        $result = $this->formatApiResponse($response->json());

        // Add assistant response to conversation history
        $this->addMessage('assistant', $result['content']);

        return $result;
    }

    /**
     * Get available Grok models.
     * Note: This might require a separate API endpoint
     *
     * @return array
     */
    public function getAvailableModels(): array
    {
        // This would typically call a models endpoint
        // For now, returning known Grok models
        return [
            'grok-beta',
            'grok-2',
            'grok-vision', // If vision capability exists
        ];
    }
}
