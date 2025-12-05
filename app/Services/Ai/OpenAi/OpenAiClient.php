<?php

namespace App\Services\Ai\OpenAi;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiClient
{
    /**
     * The OpenAI API key.
     * @var string
     */
    protected string $apiKey;

    /**
     * The OpenAI API endpoint.
     * Defaults to Chat Completions.
     * @var string
     */
    protected string $apiEndpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * The model to use (e.g., gpt-4o, gpt-3.5-turbo).
     * @var string
     */
    protected string $model = 'gpt-4o';

    protected int $timeout = 120;

    protected string $prompt;

    private ?UploadedFile $file = null;

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
     * @return $this
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function setApiEndpoint(string $apiEndpoint): static
    {
        $this->apiEndpoint = $apiEndpoint;
        return $this;
    }

    /**
     * Set the model (e.g., 'gpt-4o', 'gpt-4-turbo').
     * @param string $model
     * @return $this
     */
    public function setModel(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function setPrompt(string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    /**
     * Set the file for upload (Images for Vision API).
     * @param UploadedFile $file
     * @return $this
     */
    public function setFile(UploadedFile $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function makeRequest(): Response
    {
        // 1. Prepare the User Message Content
        $userContent = [];

        // Add the text prompt
        $userContent[] = [
            'type' => 'text',
            'text' => $this->prompt
        ];

        // 2. Handle File (Vision API / Multimodal)
        // Note: OpenAI expects images as Base64 Data URLs or public HTTP links.
        if (isset($this->file)) {
            $mimeType = $this->file->getMimeType();
            $base64Data = base64_encode($this->file->get());

            $userContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:{$mimeType};base64,{$base64Data}",
                    'detail' => 'auto' // Options: low, high, auto
                ]
            ];
        }

        // 3. Construct Payload
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $userContent
                ]
            ],
            // Optional: Force JSON mode if you want stricter JSON responses
            // 'response_format' => ['type' => 'json_object']
        ];

        // 4. Send Request
        return Http::withOptions(['timeout' => $this->timeout])
            ->withToken($this->apiKey) // OpenAI uses Bearer Token
            ->post($this->apiEndpoint, $data);
    }

    /**
     * Extracts, cleans, and decodes the JSON from the OpenAI API response.
     *
     * @param Response $response The Response object.
     * @return array
     * @throws Exception
     */
    public function formatApiResponse(Response $response): array
    {
        if ($response->failed()) {
             Log::warning('Open AI API returned an empty response.', ['response' => $response->json()]);
             throw $response->toException();
        }

        $responseBody = $response->json();
        if (!empty($responseBody['error'])) {
            throw new Exception(
                (!empty($responseBody['error']['message']))
                ? $responseBody['error']['message']
                : 'Error in open ai request'
            );
        }

        // Check for OpenAI specific error structure or empty choices
        if (empty($responseBody['choices'][0]['message']['content'])) {
            Log::warning('OpenAI API returned an empty or invalid response.', ['response' => $responseBody]);
            throw new Exception('The response could not be read or parsed.');
        }

        $rawText = $responseBody['choices'][0]['message']['content'];

        // Clean the response: OpenAI also wraps JSON in ```json ... ```
        if (preg_match('/```json\s*([\s\S]*?)\s*```/', $rawText, $matches)) {
            $jsonString = $matches[1];
        } else {
            $jsonString = $rawText;
        }

        $decoded = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to decode JSON from OpenAI.', ['raw_text' => $rawText, 'json_error' => json_last_error_msg()]);
            throw new Exception('Failed to process the parsed data.');
        }

        return $decoded;
    }
}
