<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ImportException extends Exception
{
    public function __construct(
        string $message = 'Import error',
        private ?array $errors = [],
        private ?int $statusCode = 400,
    ) {
        parent::__construct($message);
    }

    public function render($request): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ], $this->statusCode);
    }
}
