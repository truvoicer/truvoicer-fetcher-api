<?php
namespace App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Ai;

use InvalidArgumentException;

class AiPopulatePrompt {

    /**
     * Convert array to JSON with error handling
     */
    protected function toJson(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Failed to encode data to JSON: ' . json_last_error_msg());
        }

        return $json;
    }

}
