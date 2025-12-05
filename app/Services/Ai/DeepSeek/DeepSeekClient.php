<?php

namespace App\Services\Ai\DeepSeek;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekClient
{
    /**
     * The DeepSeek API key.
     * @var string
     */
    protected string $apiKey;

    /**
     * The DeepSeek API endpoint.
     * @var string
     */
    protected string $apiEndpoint = 'https://api.deepseek.com/v1/chat/completions';

    /**
     * Default model to use.
     * @var string
     */
    protected string $model = 'deepseek-chat';

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
     * System prompt/instruction.
     * @var string
     */
    protected string $systemPrompt = 'You are a helpful AI assistant.';

    /**
     * User prompt/message.
     * @var string
     */
    protected string $userPrompt;

    /**
     * Uploaded file (if any).
     * @var UploadedFile|null
     */
    protected ?UploadedFile $file = null;

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
     * Set file for vision/upload functionality.
     * @param UploadedFile $file
     * @return $this
     */
    public function setFile(UploadedFile $file): self
    {
        $this->file = $file;
        return $this;
    }

    /**
     * Get file.
     * @return UploadedFile|null
     */
    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    /**
     * Make API request to DeepSeek.
     * @return Response
     */
    public function makeRequest(): Response
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $this->systemPrompt
            ],
            [
                'role' => 'user',
                'content' => []
            ]
        ];

        // Add text content
        $messages[1]['content'][] = [
            'type' => 'text',
            'text' => $this->userPrompt
        ];

        // Add file content if present
        if ($this->file) {
            $mimeType = $this->file->getMimeType();
            $fileContent = base64_encode($this->file->get());

            $messages[1]['content'][] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:{$mimeType};base64,{$fileContent}"
                ]
            ];
        }

        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
        ];

        // Add optional parameters
        if ($this->maxTokens !== null) {
            $data['max_tokens'] = $this->maxTokens;
        }

        return Http::withOptions([
            'timeout' => $this->timeout
        ])
        ->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])
        ->post($this->apiEndpoint, $data);
    }

    /**
     * Extracts and formats the response from DeepSeek API.
     *
     * @param array $responseBody The raw response body from the API.
     * @return array
     * @throws Exception
     */
    public function formatApiResponse(Response $response): array
    {
        if ($response->failed()) {
             Log::warning('DeepSeek API returned an empty response.', ['response' => $response->json()]);
             throw $response->toException();
        }

        $responseBody = $response->json();
        if (!isset($responseBody['choices'][0]['message']['content'])) {
            Log::warning('DeepSeek API returned an empty response.', ['response' => $responseBody]);
            throw new Exception('The response could not be read or parsed.');
        }

        $rawText = $responseBody['choices'][0]['message']['content'];

        // Clean the response if it's JSON wrapped in code blocks
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $rawText, $matches)) {
            $jsonString = $matches[1];
        } else {
            $jsonString = $rawText;
        }

        // Try to parse as JSON, if it's valid JSON return it, otherwise return as text
        $decoded = json_decode($jsonString, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // If it's not JSON, return the raw text
        return [
            'text' => $rawText,
            'raw_response' => $rawText
        ];
    }

    /**
     * Simple text-only request (convenience method).
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function ask(string $prompt, array $options = []): array
    {
        $this->setPrompt($prompt);

        if (isset($options['temperature'])) {
            $this->setTemperature($options['temperature']);
        }

        if (isset($options['max_tokens'])) {
            $this->setMaxTokens($options['max_tokens']);
        }

        if (isset($options['system_prompt'])) {
            $this->setSystemPrompt($options['system_prompt']);
        }

        $response = $this->makeRequest();

        if (!$response->successful()) {
            throw new Exception('API request failed: ' . $response->body());
        }

        return $this->formatApiResponse($response->json());
    }
}
