<?php

namespace App\Exceptions\Api\Response;

use Exception;

class ApiResponseException extends Exception
{
    public function __construct(
        string         $message = "Import error",
        private ?array $errors = [],
        private ?int   $statusCode = 400,
    )
    {
        parent::__construct($message);
    }


    public function render($request): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ], $this->statusCode);
    }
}
