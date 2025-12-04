<?php

namespace App\Services\Ai\Gemini;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    /**
     * The Gemini API key.
     * @var string
     */
    protected string $apiKey;

    /**
     * The Gemini API endpoint.
     * @var string
     */
    protected string $apiEndpoint;

    protected string $prompt;

    private UploadedFile $file;

    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiEndpoint(string $apiEndpoint): static
    {
        $this->apiEndpoint = $apiEndpoint;
        return $this;
    }

    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    public function makeRequest(): array
    {

        $promptText = mb_convert_encoding($this->getPrompt(), 'UTF-8', 'UTF-8');

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $promptText],
                    ]
                ]
            ],
        ];
        if (isset($this->file)) {
            $mimeType = $this->file->getMimeType();
            $mimeType = mb_convert_encoding($mimeType, 'UTF-8', 'UTF-8');
            $fileContent = base64_encode($this->file->get());
            $data['contents'][0]['parts'][] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $fileContent
                ]
            ];
        }
        $response = Http::withOptions(['timeout' => 120]) // Increase timeout for large files
            ->post($this->apiEndpoint . '?key=' . $this->apiKey, $data);

        if ($response->failed()) {
            Log::error('Gemini API request failed', ['response' => json_encode($response->body())]);
            throw new Exception('Failed to parse resume due to an API error.');
        }

        return $this->formatApiResponse($response->json());
    }

    /**
     * Extracts, cleans, and decodes the JSON from the Gemini API response.
     *
     * @param array $responseBody The raw response body from the API.
     * @return array
     * @throws Exception
     */
    private function formatApiResponse(array $responseBody): array
    {
        if (empty($responseBody['candidates'][0]['content']['parts'][0]['text'])) {
            Log::warning('Gemini API returned an empty response.', ['response' => $responseBody]);
            throw new Exception('The resume could not be read or parsed.');
        }

        $rawText = $responseBody['candidates'][0]['content']['parts'][0]['text'];

        // Clean the response: Gemini often wraps JSON in ```json ... ```
        if (preg_match('/```json\s*([\s\S]*?)\s*```/', $rawText, $matches)) {
            $jsonString = $matches[1];
        } else {
            $jsonString = $rawText;
        }

        $decoded = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to decode JSON from Gemini.', ['raw_text' => $rawText]);
            throw new Exception('Failed to process the parsed resume data.');
        }

        return $decoded;
    }

    /**
     * Returns the prompt used for the Gemini API.
     *
     * @return string
     */
    private function getPrompt(): string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }
}
